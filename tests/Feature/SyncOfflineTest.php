<?php

use App\Models\Category;
use App\Models\Notification;
use App\Models\Report;

function makeDraft(array $overrides = []): array
{
    return array_merge([
        'client_uuid' => fake()->uuid(),
        'title' => 'Laporan offline',
        'category_id' => Category::factory()->create()->id,
        'latitude' => -7.7956,
        'longitude' => 110.3695,
        'created_at' => now()->subHours(2)->toIso8601String(),
    ], $overrides);
}

it('syncs a batch of offline drafts', function () {
    $officer = makeFieldOfficer();
    $drafts = [makeDraft(['title' => 'Pertama']), makeDraft(['title' => 'Kedua'])];

    $response = $this->actingAs($officer)->postJson('/reports/sync', compact('drafts'));

    $response->assertOk()
        ->assertJsonPath('created_count', 2)
        ->assertJsonPath('duplicate_count', 0);

    $this->assertDatabaseCount('reports', 2);
    expect(Report::where('title', 'Pertama')->first()->user_id)->toBe($officer->id);
});

it('is idempotent when the same batch is retried', function () {
    $officer = makeFieldOfficer();
    $drafts = [makeDraft(), makeDraft()];

    $first = $this->actingAs($officer)->postJson('/reports/sync', compact('drafts'));
    $first->assertOk()->assertJsonPath('created_count', 2);

    $second = $this->actingAs($officer)->postJson('/reports/sync', compact('drafts'));
    $second->assertOk()
        ->assertJsonPath('created_count', 0)
        ->assertJsonPath('duplicate_count', 2);

    $this->assertDatabaseCount('reports', 2);
});

it('syncs drafts with only partial optional payload', function () {
    $officer = makeFieldOfficer();
    $drafts = [
        array_filter([
            'client_uuid' => fake()->uuid(),
            'title' => 'Tanpa deskripsi',
            'category_id' => Category::factory()->create()->id,
            'latitude' => -7.1,
            'longitude' => 110.4,
            'block_code' => null,
            'description' => null,
            'checklist_answers' => null,
            'created_at' => null,
        ], fn ($value) => $value !== null),
    ];

    $this->actingAs($officer)->postJson('/reports/sync', compact('drafts'))
        ->assertOk()
        ->assertJsonPath('created_count', 1);

    $report = Report::first();
    expect($report->title)->toBe('Tanpa deskripsi')
        ->and($report->description)->toBeNull()
        ->and($report->block_code)->toBeNull();
});

it('rejects the whole batch when one draft is invalid', function () {
    $officer = makeFieldOfficer();
    $drafts = [
        makeDraft(['title' => 'Valid']),
        makeDraft(['title' => 'Invalid latitude', 'latitude' => 999]),
    ];

    $this->actingAs($officer)->postJson('/reports/sync', compact('drafts'))
        ->assertStatus(422)
        ->assertJsonValidationErrors('drafts.1.latitude');

    $this->assertDatabaseCount('reports', 0);
});

it('allows retrying after a failed batch with corrected payload', function () {
    $officer = makeFieldOfficer();
    $uuid = fake()->uuid();

    $broken = [makeDraft(['client_uuid' => $uuid, 'title' => 'Broken', 'category_id' => 9999])];
    $this->actingAs($officer)->postJson('/reports/sync', ['drafts' => $broken])
        ->assertStatus(422);
    $this->assertDatabaseCount('reports', 0);

    $fixed = [makeDraft(['client_uuid' => $uuid, 'title' => 'Fixed'])];
    $this->actingAs($officer)->postJson('/reports/sync', ['drafts' => $fixed])
        ->assertOk()
        ->assertJsonPath('created_count', 1);

    expect(Report::first()->title)->toBe('Fixed');
});

it('rejects duplicate client uuids inside one batch', function () {
    $officer = makeFieldOfficer();
    $uuid = fake()->uuid();
    $drafts = [makeDraft(['client_uuid' => $uuid]), makeDraft(['client_uuid' => $uuid])];

    $this->actingAs($officer)->postJson('/reports/sync', compact('drafts'))
        ->assertStatus(422)
        ->assertJsonValidationErrors('drafts.1.client_uuid');
});

it('rejects empty draft batches', function () {
    $officer = makeFieldOfficer();

    $this->actingAs($officer)->postJson('/reports/sync', ['drafts' => []])
        ->assertStatus(422)
        ->assertJsonValidationErrors('drafts');
});

it('rejects future draft creation timestamps', function () {
    $officer = makeFieldOfficer();
    $drafts = [makeDraft(['created_at' => now()->addDay()->toIso8601String()])];

    $this->actingAs($officer)->postJson('/reports/sync', compact('drafts'))
        ->assertStatus(422)
        ->assertJsonValidationErrors('drafts.0.created_at');
});

it('computes SLA deadline from offline draft timestamp', function () {
    $officer = makeFieldOfficer();
    $category = Category::factory()->create(['sla_hours' => 24]);
    $createdAt = now()->subHours(5);
    $drafts = [makeDraft(['category_id' => $category->id, 'created_at' => $createdAt->toIso8601String()])];

    $this->actingAs($officer)->postJson('/reports/sync', compact('drafts'))->assertOk();

    $report = Report::first();
    expect($report->reported_at->timestamp)->toBe($createdAt->timestamp)
        ->and($report->sla_deadline->timestamp)->toBe($createdAt->copy()->addHours(24)->timestamp);
});

it('notifies admins about synced offline reports', function () {
    $admin = makeAdmin();
    $officer = makeFieldOfficer();
    $drafts = [makeDraft(['title' => 'Offline penting'])];

    $this->actingAs($officer)->postJson('/reports/sync', compact('drafts'))->assertOk();

    $this->assertDatabaseHas('notifications', [
        'type' => 'report.created',
        'notifiable_id' => $admin->id,
        'channel' => 'database',
    ]);
});

it('sync endpoint requires authentication', function () {
    $this->postJson('/reports/sync', ['drafts' => [makeDraft()]])->assertUnauthorized();
});

it('syncs drafts through the sanctum API', function () {
    $officer = makeFieldOfficer();
    $token = $officer->createToken('test-device')->plainTextToken;
    $drafts = [makeDraft(['title' => 'Via API'])];

    $this->withToken($token)
        ->postJson('/api/v1/reports/sync', compact('drafts'))
        ->assertOk()
        ->assertJsonPath('created_count', 1);

    expect(Report::first()->title)->toBe('Via API');
});

it('isolates drafts per user during sync', function () {
    $officerA = makeFieldOfficer();
    $officerB = makeFieldOfficer();
    $uuid = fake()->uuid();

    $this->actingAs($officerA)->postJson('/reports/sync', ['drafts' => [makeDraft(['client_uuid' => $uuid])]])
        ->assertJsonPath('created_count', 1);

    $this->actingAs($officerB)->postJson('/reports/sync', ['drafts' => [makeDraft(['client_uuid' => $uuid])]])
        ->assertJsonPath('created_count', 1);

    $this->assertDatabaseCount('reports', 2);
    expect(Report::pluck('user_id')->unique()->count())->toBe(2);
});
