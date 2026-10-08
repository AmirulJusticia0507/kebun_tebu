<?php

use App\Models\Block;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('user can access login page', function () {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertSee('id="app"', false);
});

it('user can register with valid data', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone_number' => '08123456789',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated('web');
    $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'role' => 'field_officer']);
});

it('guest user cannot access dashboard', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

it('field_officer can create a report', function () {
    $user = makeFieldOfficer();
    $category = Category::factory()->create();

    $response = $this->actingAs($user)->post('/reports', [
        'category_id' => $category->id,
        'title' => 'Kebakaran tebu',
        'description' => 'Lapisan api di area BLOK-A',
        'latitude' => -7.7956,
        'longitude' => 110.3695,
        'block_code' => 'BLOK-A12',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('reports', [
        'title' => 'Kebakaran tebu',
        'user_id' => $user->id,
        'status' => 'OPEN',
    ]);
});

it('admin can filter reports by status', function () {
    $admin = makeAdmin();
    $category = Category::factory()->create();

    Report::factory()->create(['category_id' => $category->id, 'status' => 'OPEN']);
    Report::factory()->create(['category_id' => $category->id, 'status' => 'ON_PROGRESS']);
    Report::factory()->create(['category_id' => $category->id, 'status' => 'CLOSED']);

    $response = $this->actingAs($admin)->get('/reports?status=OPEN');

    $response->assertStatus(200);
    $response->assertSee('OPEN');
    $response->assertDontSee('ON_PROGRESS');
    $response->assertDontSee('CLOSED');
});

it('admin can export reports as CSV', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)->get('/reports/export/csv');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    $response->assertHeader('Content-Disposition');
});

it('report status flow', function () {
    $admin = makeAdmin();
    $user = makeFieldOfficer();

    $report = Report::factory()->create([
        'user_id' => $user->id,
        'status' => 'OPEN',
    ]);

    $response = $this->actingAs($admin)
        ->patch("/reports/{$report->id}/status", [
            'status' => 'ON_PROGRESS',
            'admin_note' => 'Sedang ditangani',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('reports', [
        'id' => $report->id,
        'status' => 'ON_PROGRESS',
        'admin_note' => 'Sedang ditangani',
    ]);

    $response = $this->actingAs($admin)
        ->patch("/reports/{$report->id}/status", [
            'status' => 'CLOSED',
            'admin_note' => 'Sudah diselesaikan',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('reports', [
        'id' => $report->id,
        'status' => 'CLOSED',
        'admin_note' => 'Sudah diselesaikan',
    ]);
});

it('report SLA deadline calculation', function () {
    $category = Category::factory()->create(['sla_hours' => 24]);

    $report = Report::factory()->create([
        'category_id' => $category->id,
        'status' => 'OPEN',
        'reported_at' => now(),
    ]);

    $this->assertNotNull($report->sla_deadline);
    $this->assertTrue($report->sla_deadline->gt(now()));
    $this->assertTrue($report->sla_deadline->lte(now()->addHours(25)));
});

it('report block relationship', function () {
    $block = Block::factory()->create([
        'code' => 'BLOK-A12',
        'name' => 'Blok A',
    ]);

    $report = Report::factory()->create([
        'block_id' => $block->id,
        'block_code' => 'BLOK-A12',
    ]);

    $this->assertEquals($block->id, $report->block_id);
    $this->assertEquals('BLOK-A12', $report->block_code);
});

it('gps location data acceptance', function () {
    $user = makeFieldOfficer();
    $category = Category::factory()->create();

    $response = $this->actingAs($user)->post('/reports', [
        'title' => 'Test GPS',
        'category_id' => $category->id,
        'latitude' => -7.7956,
        'longitude' => 110.3695,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('reports', [
        'latitude' => -7.7956,
        'longitude' => 110.3695,
    ]);
});

it('report creation with valid photo', function () {
    Storage::fake('public');

    $user = makeFieldOfficer();
    $category = Category::factory()->create();

    $photo = UploadedFile::fake()->image('test_photo.jpg');

    $response = $this->actingAs($user)->post('/reports', [
        'title' => 'Test Photo Upload',
        'category_id' => $category->id,
        'latitude' => -7.7956,
        'longitude' => 110.3695,
        'photo' => $photo,
    ]);

    $response->assertRedirect();
    $report = Report::where('title', 'Test Photo Upload')->first();
    $this->assertNotNull($report->photo_url);
});

it('report checklist_answers storage', function () {
    $category = Category::factory()->create([
        'checklist_template' => [
            ['label' => 'Api masih menyala?', 'type' => 'boolean'],
            ['label' => 'Jumlah hektar terbakar', 'type' => 'number'],
        ],
    ]);

    $user = makeFieldOfficer();

    $report = Report::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Test Checklist',
        'description' => 'Test checklist storage',
        'latitude' => -7.7956,
        'longitude' => 110.3695,
        'checklist_answers' => [
            'Api masih menyala?' => true,
            'Jumlah hektar terbakar' => 5,
        ],
    ]);

    $this->assertEquals(true, $report->checklist_answers['Api masih menyala?']);
    $this->assertEquals(5, $report->checklist_answers['Jumlah hektar terbakar']);
});

it('admin can export reports as GeoJSON', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)->get('/reports/export/geojson');

    $response->assertOk();
    $response->assertSee('type');
    $response->assertSee('features');
});
