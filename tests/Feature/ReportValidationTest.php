<?php

use App\Models\Block;
use App\Models\Category;
use App\Models\Report;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function validReportPayload(array $overrides = []): array
{
    $category = Category::factory()->create();

    return array_merge([
        'category_id' => $category->id,
        'title' => 'Serangan hama',
        'description' => 'Laporan lapangan',
        'latitude' => -7.7956,
        'longitude' => 110.3695,
        'block_code' => 'BLOK-A1',
    ], $overrides);
}

it('rejects report without required fields', function () {
    $officer = makeFieldOfficer();

    $response = $this->actingAs($officer)->post('/reports', []);

    $response->assertSessionHasErrors(['title', 'category_id', 'latitude', 'longitude']);
    $this->assertDatabaseCount('reports', 0);
});

it('rejects report with title exceeding 150 characters', function () {
    $officer = makeFieldOfficer();

    $response = $this->actingAs($officer)->post('/reports', validReportPayload([
        'title' => str_repeat('a', 151),
    ]));

    $response->assertSessionHasErrors('title');
});

it('rejects report with nonexistent category', function () {
    $officer = makeFieldOfficer();

    $response = $this->actingAs($officer)->post('/reports', validReportPayload([
        'category_id' => 9999,
    ]));

    $response->assertSessionHasErrors('category_id');
});

it('rejects out of range coordinates', function () {
    $officer = makeFieldOfficer();

    $this->actingAs($officer)->post('/reports', validReportPayload(['latitude' => 91]))
        ->assertSessionHasErrors('latitude');

    $this->actingAs($officer)->post('/reports', validReportPayload(['longitude' => 181]))
        ->assertSessionHasErrors('longitude');
});

it('rejects invalid client uuid', function () {
    $officer = makeFieldOfficer();

    $this->actingAs($officer)->post('/reports', validReportPayload([
        'client_uuid' => 'not-a-uuid',
    ]))->assertSessionHasErrors('client_uuid');
});

it('stores a valid report with OPEN status and SLA deadline', function () {
    $officer = makeFieldOfficer();

    $this->actingAs($officer)->post('/reports', validReportPayload())
        ->assertRedirect('/map')
        ->assertSessionHas('success');

    $report = Report::first();
    expect($report)->not->toBeNull()
        ->and($report->status)->toBe('OPEN')
        ->and($report->sla_deadline)->not->toBeNull()
        ->and($report->reported_at->diffInHours($report->sla_deadline, false))->toEqualWithDelta(24, 1);
});

it('does not duplicate report when the same client uuid is submitted again', function () {
    $officer = makeFieldOfficer();

    $payload = validReportPayload(['client_uuid' => '11111111-1111-4111-8111-111111111111']);

    $this->actingAs($officer)->post('/reports', $payload)->assertRedirect('/map');
    $this->actingAs($officer)->post('/reports', $payload)->assertRedirect('/map');

    $this->assertDatabaseCount('reports', 1);
});

it('accepts report without photo and keeps photo_url null', function () {
    $officer = makeFieldOfficer();

    $this->actingAs($officer)->post('/reports', validReportPayload());

    expect(Report::first()->photo_url)->toBeNull();
});

it('stores uploaded photo as sanitized webp', function () {
    Storage::fake('public');

    $officer = makeFieldOfficer();

    $this->actingAs($officer)->post('/reports', validReportPayload([
        'photo' => UploadedFile::fake()->image('lapangan.jpg', 800, 600),
    ]))->assertRedirect();

    $report = Report::first();
    expect($report->photo_url)->not->toBeNull()
        ->and($report->photo_url)->toStartWith('/storage/reports/photos/');
    Storage::disk('public')->assertExists(\Illuminate\Support\Str::after($report->photo_url, '/storage/'));
});

it('rejects photo larger than 5 MB', function () {
    $officer = makeFieldOfficer();

    $photo = UploadedFile::fake()->create('besar.jpg', 6000, 'image/jpeg');

    $this->actingAs($officer)->post('/reports', validReportPayload(['photo' => $photo]))
        ->assertSessionHasErrors('photo');
});

it('rejects non image file as photo', function () {
    $officer = makeFieldOfficer();

    $file = UploadedFile::fake()->create('dokumen.txt', 100, 'text/plain');

    $this->actingAs($officer)->post('/reports', validReportPayload(['photo' => $file]))
        ->assertSessionHasErrors('photo');
});

it('computes SLA deadline from category hours', function () {
    $officer = makeFieldOfficer();
    $category = Category::factory()->create(['sla_hours' => 48]);

    $this->actingAs($officer)->post('/reports', validReportPayload([
        'category_id' => $category->id,
    ]));

    $report = Report::first();
    expect($report->sla_deadline->diffInHours($report->reported_at, false))->toEqualWithDelta(-48, 1);
});

it('leaves SLA deadline null when category has no SLA', function () {
    $officer = makeFieldOfficer();
    $category = Category::factory()->create(['sla_hours' => 0]);

    $this->actingAs($officer)->post('/reports', validReportPayload([
        'category_id' => $category->id,
    ]));

    expect(Report::first()->sla_deadline)->toBeNull();
});

it('rejects invalid report index filters', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)->get('/reports?status=BOGUS')->assertSessionHasErrors('status');
    $this->actingAs($admin)->get('/reports?category_id=9999')->assertSessionHasErrors('category_id');
    $this->actingAs($admin)->get('/reports?search=' . str_repeat('a', 101))->assertSessionHasErrors('search');
});

it('filters reports by status search and category', function () {
    $admin = makeAdmin();
    $category = Category::factory()->create();

    Report::factory()->create(['category_id' => $category->id, 'status' => 'OPEN', 'title' => 'Kebakaran Utara']);
    Report::factory()->create(['category_id' => $category->id, 'status' => 'CLOSED', 'title' => 'Hama Selatan']);

    $response = $this->actingAs($admin)->get('/reports?status=OPEN');
    $response->assertOk();
    $response->assertSee('Kebakaran Utara');
    $response->assertDontSee('Hama Selatan');

    $response = $this->actingAs($admin)->get('/reports?search=Utara');
    $response->assertOk();
    $response->assertSee('Kebakaran Utara');
    $response->assertDontSee('Hama Selatan');

    $response = $this->actingAs($admin)->get("/reports?category_id={$category->id}");
    $response->assertOk();
    $response->assertSee('Kebakaran Utara');
});

it('rejects invalid map filters', function () {
    $officer = makeFieldOfficer();

    $this->actingAs($officer)->get('/map?status=BOGUS')->assertSessionHasErrors('status');
    $this->actingAs($officer)->get('/map?date_from=not-a-date')->assertSessionHasErrors('date_from');
    $this->actingAs($officer)->get('/map?date_from=2026-10-05&date_to=2026-10-01')
        ->assertSessionHasErrors('date_to');
});

it('exports CSV with header row and report data', function () {
    $admin = makeAdmin();
    $report = Report::factory()->create(['title' => 'Laporan CSV']);

    $response = $this->actingAs($admin)->get('/reports/export/csv');

    $response->assertOk();
    $content = $response->streamedContent();
    expect($content)->toContain('ID,Judul,Kategori')
        ->toContain('Laporan CSV')
        ->toContain($report->status);
});

it('protects CSV export from formula injection', function () {
    $admin = makeAdmin();
    Report::factory()->create(['title' => '=SUM(A1:A9)']);

    $content = $this->actingAs($admin)->get('/reports/export/csv')->streamedContent();

    expect($content)->toContain("'=SUM(A1:A9)");
    expect($content)->not->toContain(",=SUM");
});

it('applies filters to CSV export', function () {
    $admin = makeAdmin();

    Report::factory()->create(['status' => 'OPEN', 'title' => 'Tetap Terbuka']);
    Report::factory()->create(['status' => 'CLOSED', 'title' => 'Sudah Tutup']);

    $content = $this->actingAs($admin)->get('/reports/export/csv?status=OPEN')->streamedContent();

    expect($content)->toContain('Tetap Terbuka')
        ->not->toContain('Sudah Tutup');
});

it('rejects invalid export date filters', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)->get('/reports/export/csv?date_from=2026-10-05&date_to=2026-10-01')
        ->assertSessionHasErrors('date_to');
    $this->actingAs($admin)->get('/reports/export/csv?status=BOGUS')
        ->assertSessionHasErrors('status');
});

it('exports GeoJSON FeatureCollection with point geometry', function () {
    $admin = makeAdmin();
    $report = Report::factory()->create([
        'title' => 'Titik Panas',
        'latitude' => -7.7956,
        'longitude' => 110.3695,
        'status' => 'OPEN',
    ]);

    $response = $this->actingAs($admin)->get('/reports/export/geojson');

    $response->assertOk()->assertHeader('Content-Type', 'application/json');
    $json = $response->json();

    expect($json['type'])->toBe('FeatureCollection')
        ->and($json['features'])->toHaveCount(1);

    $feature = $json['features'][0];
    expect($feature['type'])->toBe('Feature')
        ->and($feature['properties']['title'])->toBe('Titik Panas')
        ->and($feature['geometry']['type'])->toBe('Point')
        ->and($feature['geometry']['coordinates'])->toBe([110.3695, -7.7956]);
});

it('applies filters to GeoJSON export', function () {
    $admin = makeAdmin();

    Report::factory()->create(['status' => 'OPEN', 'title' => 'Geo Terbuka']);
    Report::factory()->create(['status' => 'CLOSED', 'title' => 'Geo Tutup']);

    $json = $this->actingAs($admin)->get('/reports/export/geojson?status=OPEN')->json();

    expect($json['features'])->toHaveCount(1)
        ->and($json['features'][0]['properties']['title'])->toBe('Geo Terbuka');
});
