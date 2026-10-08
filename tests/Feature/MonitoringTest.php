<?php

use App\Models\Notification;
use Illuminate\Support\Facades\Http;

function monitoringFreshHeartbeat(): string
{
    $file = tempnam(sys_get_temp_dir(), 'hb');
    file_put_contents($file, json_encode(['ran_at' => now()->toIso8601String()]));

    return (string) $file;
}

function monitoringStaleHeartbeat(): string
{
    $file = tempnam(sys_get_temp_dir(), 'hb');
    file_put_contents($file, json_encode(['ran_at' => now()->subHours(2)->toIso8601String()]));

    return (string) $file;
}

function monitoringLog(string $content): string
{
    $path = storage_path('logs/testing-monitor-'.uniqid().'.log');
    file_put_contents($path, $content);

    return $path;
}

afterEach(function () {
    foreach (glob(storage_path('logs/testing-monitor-*.log')) ?: [] as $file) {
        @unlink($file);
    }
});

it('passes health checks when the scheduler heartbeat is fresh', function () {
    config([
        'monitoring.heartbeat_file' => monitoringFreshHeartbeat(),
        'monitoring.disk_min_free_mb' => 1,
        'monitoring.storage_min_free_mb' => 1,
    ]);

    $this->artisan('monitor:health')->assertExitCode(0);
    $this->artisan('monitor:health --json')->assertExitCode(0);
});

it('fails health checks when the scheduler heartbeat is stale', function () {
    config([
        'monitoring.heartbeat_file' => monitoringStaleHeartbeat(),
        'monitoring.disk_min_free_mb' => 1,
        'monitoring.storage_min_free_mb' => 1,
    ]);

    $this->artisan('monitor:health')->assertExitCode(1);
});

it('sends health alerts to admins through database, mail, push, and webhook', function () {
    config([
        'monitoring.heartbeat_file' => monitoringStaleHeartbeat(),
        'monitoring.disk_min_free_mb' => 1,
        'monitoring.storage_min_free_mb' => 1,
        'monitoring.webhook_url' => 'https://example.test/webhook',
        'monitoring.alert_cooldown_minutes' => 30,
    ]);
    Http::fake();
    $admin = makeAdmin();

    $this->artisan('monitor:health --alert')->assertExitCode(1);

    expect(Notification::where('type', 'system.health')->where('notifiable_id', $admin->id)->exists())->toBeTrue();
    Http::assertSentCount(1);

    $count = Notification::where('type', 'system.health')->count();
    $this->artisan('monitor:health --alert')->assertExitCode(1);

    expect(Notification::where('type', 'system.health')->count())->toBe($count);
    Http::assertSentCount(1);
});

it('returns 200 on the public health endpoint when everything is healthy', function () {
    config([
        'monitoring.heartbeat_file' => monitoringFreshHeartbeat(),
        'monitoring.disk_min_free_mb' => 1,
        'monitoring.storage_min_free_mb' => 1,
    ]);

    $this->get('/healthz')->assertOk()->assertJson(['status' => 'ok']);
});

it('returns 503 on the public health endpoint when a check fails', function () {
    config([
        'monitoring.heartbeat_file' => monitoringStaleHeartbeat(),
        'monitoring.disk_min_free_mb' => 1,
        'monitoring.storage_min_free_mb' => 1,
    ]);

    $this->get('/healthz')->assertStatus(503)->assertJson(['status' => 'error']);
});

it('exposes detailed health checks to admins only', function () {
    config([
        'monitoring.heartbeat_file' => monitoringFreshHeartbeat(),
        'monitoring.disk_min_free_mb' => 1,
        'monitoring.storage_min_free_mb' => 1,
    ]);
    $admin = makeAdmin();
    $officer = makeFieldOfficer();

    $this->actingAs($admin)->get('/dashboard/health')
        ->assertOk()
        ->assertJsonStructure(['healthy', 'checks', 'queue', 'environment']);

    $this->actingAs($officer)->get('/dashboard/health')->assertForbidden();
});

it('requires authentication for detailed health checks', function () {
    $this->get('/dashboard/health')->assertRedirect();
});

it('reports no application errors when logs are clean', function () {
    $log = monitoringLog('['.now()->format('Y-m-d H:i:s')."] production.INFO: semua baik\n");

    $this->artisan('monitor:errors --path='.str_replace('\\', '/', $log).' --hours=1')->assertExitCode(0);
});

it('detects errors in application logs and alerts admins once per cooldown', function () {
    config([
        'monitoring.webhook_url' => 'https://example.test/webhook',
        'monitoring.error_threshold' => 5,
        'monitoring.alert_cooldown_minutes' => 30,
    ]);
    Http::fake();
    $admin = makeAdmin();

    $recent = now()->subMinutes(5)->format('Y-m-d H:i:s');
    $old = now()->subDays(2)->format('Y-m-d H:i:s');
    $log = monitoringLog(
        "[{$old}] production.ERROR: error lama yang tidak dihitung\n".
        "[{$recent}] production.ERROR: gagal menyimpan laporan\n".
        "[{$recent}] production.CRITICAL: koneksi database terputus\n"
    );
    $path = str_replace('\\', '/', $log);

    $this->artisan("monitor:errors --path={$path} --hours=1 --alert")->assertExitCode(1);

    expect(Notification::where('type', 'system.errors')->where('notifiable_id', $admin->id)->exists())->toBeTrue();
    Http::assertSentCount(1);

    $count = Notification::where('type', 'system.errors')->count();
    $this->artisan("monitor:errors --path={$path} --hours=1 --alert")->assertExitCode(1);

    expect(Notification::where('type', 'system.errors')->count())->toBe($count);
    Http::assertSentCount(1);
});

it('does not trigger error alerts below the configured threshold', function () {
    config([
        'monitoring.webhook_url' => 'https://example.test/webhook',
        'monitoring.error_threshold' => 5,
    ]);
    Http::fake();

    $recent = now()->subMinutes(5)->format('Y-m-d H:i:s');
    $log = monitoringLog("[{$recent}] production.ERROR: satu error saja\n");
    $path = str_replace('\\', '/', $log);

    $this->artisan("monitor:errors --path={$path} --hours=1 --alert")->assertExitCode(0);
    Http::assertSentCount(0);
});
