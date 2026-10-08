<?php

use App\Models\Category;
use App\Models\Notification;
use App\Models\Report;
use Illuminate\Support\Facades\Queue;

it('sla escalation warns admins once for reports near deadline', function () {
    Queue::fake();

    $admin = makeAdmin();
    makeFieldOfficer();

    $near = Report::factory()->create(['status' => 'OPEN', 'sla_deadline' => now()->addHour()]);
    Report::factory()->create(['status' => 'OPEN', 'sla_deadline' => now()->addDays(5)]);

    $this->artisan('sla:check-escalation')->assertExitCode(0);

    $this->assertDatabaseCount('notifications', 1);
    $this->assertDatabaseHas('notifications', [
        'type' => 'report.sla_warning',
        'notifiable_id' => $admin->id,
    ]);

    $warning = Notification::where('type', 'report.sla_warning')->first();
    expect($warning->data['report_id'])->toBe($near->id);

    $this->artisan('sla:check-escalation')->assertExitCode(0);
    $this->assertDatabaseCount('notifications', 1);
});

it('sla escalation skips closed reports', function () {
    Queue::fake();
    makeAdmin();

    Report::factory()->closed()->create(['sla_deadline' => now()->subHour()]);

    $this->artisan('sla:check-escalation')->assertExitCode(0);

    $this->assertDatabaseCount('notifications', 0);
});

it('auto close only stale open reports', function () {
    Report::factory()->create([
        'status' => 'OPEN',
        'reported_at' => now()->subDays(40),
        'title' => 'Stale Report',
    ]);
    Report::factory()->create([
        'status' => 'OPEN',
        'reported_at' => now()->subDays(5),
        'title' => 'Fresh Report',
    ]);
    Report::factory()->closed()->create([
        'reported_at' => now()->subDays(40),
        'title' => 'Already Closed',
    ]);

    $this->artisan('reports:auto-close-stale')->assertExitCode(0);

    $stale = Report::where('title', 'Stale Report')->first();
    expect($stale->status)->toBe('CLOSED')
        ->and($stale->resolved_at)->not->toBeNull()
        ->and($stale->admin_note)->toContain('[System Auto-Closed]');

    expect(Report::where('title', 'Fresh Report')->first()->status)->toBe('OPEN');
    expect(Report::where('title', 'Already Closed')->first()->status)->toBe('CLOSED');
});

it('daily digest notifies every admin exactly once per day', function () {
    Queue::fake();

    $adminA = makeAdmin(['email' => 'admin-a@example.com']);
    $adminB = makeAdmin(['email' => 'admin-b@example.com']);
    $officer = makeFieldOfficer();

    Report::factory()->create();

    $this->artisan('reports:daily-digest')->assertExitCode(0);

    $this->assertDatabaseCount('notifications', 2);
    $this->assertDatabaseHas('notifications', [
        'type' => 'reports.daily_digest',
        'notifiable_id' => $adminA->id,
    ]);
    $this->assertDatabaseHas('notifications', [
        'type' => 'reports.daily_digest',
        'notifiable_id' => $adminB->id,
    ]);

    $this->artisan('reports:daily-digest')->assertExitCode(0);
    $this->assertDatabaseCount('notifications', 2);
    expect(Notification::where('type', 'reports.daily_digest')->count())->toBe(2);
});

it('creating a report notifies all admins', function () {
    Queue::fake();

    $adminA = makeAdmin(['email' => 'notifa@example.com']);
    $adminB = makeAdmin(['email' => 'notifb@example.com']);
    $officer = makeFieldOfficer();

    $category = Category::factory()->create();
    $this->actingAs($officer)->post('/reports', [
        'category_id' => $category->id,
        'title' => 'Laporan Baru',
        'latitude' => -7.5,
        'longitude' => 110.5,
    ])->assertRedirect();

    $this->assertDatabaseCount('notifications', 2);
    expect(Notification::where('type', 'report.created')->count())->toBe(2);
});

it('changing report status notifies the reporter once', function () {
    Queue::fake();

    $admin = makeAdmin();
    $officer = makeFieldOfficer();
    $report = Report::factory()->create(['user_id' => $officer->id, 'status' => 'OPEN']);

    $this->actingAs($admin)->patch("/reports/{$report->id}/status", [
        'status' => 'ON_PROGRESS',
        'admin_note' => 'Ditangani',
    ])->assertRedirect();

    expect(Notification::where('type', 'report.status_changed')->count())->toBe(1);

    $this->actingAs($admin)->patch("/reports/{$report->id}/status", [
        'status' => 'CLOSED',
        'admin_note' => 'Selesai',
    ])->assertRedirect();

    expect(Notification::where('type', 'report.status_changed')->count())->toBe(2);
});

it('note only updates do not send status notifications', function () {
    Queue::fake();

    $admin = makeAdmin();
    $officer = makeFieldOfficer();
    $report = Report::factory()->create(['user_id' => $officer->id, 'status' => 'OPEN']);

    $this->actingAs($admin)->patch("/reports/{$report->id}/status", [
        'status' => 'OPEN',
        'admin_note' => 'Catatan tambahan',
    ])->assertRedirect();

    $this->assertDatabaseCount('notifications', 0);
    $this->assertDatabaseHas('reports', [
        'id' => $report->id,
        'status' => 'OPEN',
        'admin_note' => 'Catatan tambahan',
    ]);
});

it('notification center lists only database notifications of the current user', function () {
    $officer = makeFieldOfficer();
    $other = makeFieldOfficer();

    Notification::create([
        'type' => 'report.status_changed',
        'notifiable_type' => get_class($officer),
        'notifiable_id' => $officer->id,
        'data' => ['title' => 'Punya saya', 'message' => 'x'],
        'channel' => 'database',
        'sent_at' => now(),
    ]);
    Notification::create([
        'type' => 'report.status_changed',
        'notifiable_type' => get_class($officer),
        'notifiable_id' => $officer->id,
        'data' => ['title' => 'Push channel', 'message' => 'x'],
        'channel' => 'push',
        'sent_at' => now(),
    ]);
    Notification::create([
        'type' => 'report.status_changed',
        'notifiable_type' => get_class($other),
        'notifiable_id' => $other->id,
        'data' => ['title' => 'Punya orang lain', 'message' => 'x'],
        'channel' => 'database',
        'sent_at' => now(),
    ]);

    $response = $this->actingAs($officer)->getJson('/notifications');

    $response->assertOk()
        ->assertJsonPath('unread_count', 1);

    $titles = array_map(fn ($notification) => $notification['data']['title'], $response->json('notifications'));
    expect($titles)->toBe(['Punya saya']);
});

it('notification center can mark one and all notifications as read', function () {
    $officer = makeFieldOfficer();

    Notification::create([
        'type' => 'report.status_changed',
        'notifiable_type' => get_class($officer),
        'notifiable_id' => $officer->id,
        'data' => ['title' => 'A', 'message' => 'x'],
        'channel' => 'database',
        'sent_at' => now(),
    ]);
    Notification::create([
        'type' => 'report.status_changed',
        'notifiable_type' => get_class($officer),
        'notifiable_id' => $officer->id,
        'data' => ['title' => 'B', 'message' => 'x'],
        'channel' => 'database',
        'sent_at' => now(),
    ]);

    $id = Notification::where('data->title', 'A')->first()->id;

    $this->actingAs($officer)->postJson("/notifications/{$id}/read")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Notification::find($id)->read_at)->not->toBeNull();
    expect(Notification::whereNull('read_at')->count())->toBe(1);

    $this->actingAs($officer)->postJson('/notifications/read-all')
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Notification::whereNull('read_at')->count())->toBe(0);
});
