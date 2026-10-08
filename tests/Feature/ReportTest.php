<?php

it('user can access login page', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
    $response->assertSee('Masuk ke Akun');
});

it('user can register with valid data', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'field_officer',
        'phone_number' => '08123456789',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated('web');
});

it('guest user cannot access dashboard', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

it('field_officer can create a report', function () {
    $user = User::factory()->create([
        'role' => 'field_officer',
    ]);

    $category = Category::firstOrCreate(['name' => 'Kebakaran']);

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
    $admin = User::factory()->create(['role' => 'admin']);

    // Create reports with different statuses
    Report::factory()->create(['status' => 'OPEN']);
    Report::factory()->create(['status' => 'ON_PROGRESS']);
    Report::factory()->create(['status' => 'CLOSED']);

    $response = $this->actingAs($admin)->get('/reports', ['status' => 'OPEN']);

    $response->assertStatus(200);
    $response->assertSee('OPEN');
    $response->assertDontSee('ON_PROGRESS');
    $response->assertDontSee('CLOSED');
});

it('admin can export reports as CSV', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/reports/export-csv');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv');
    $response->assertHeader('Content-Disposition');
});

it('report status flow', function () {
    // Test status flow: Open -> On Progress -> Closed
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'field_officer']);

    $report = Report::factory()->create([
        'user_id' => $user->id,
        'status' => 'OPEN',
    ]);

    // Admin changes status to On Progress
    $response = $this->actingAs($admin)
        ->put("/reports/{$report->id}/status", [
            'status' => 'ON_PROGRESS',
            'admin_note' => 'Sedang ditangani',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('reports', [
        'id' => $report->id,
        'status' => 'ON_PROGRESS',
        'admin_note' => 'Sedang ditangani',
    ]);

    // Admin changes status to Closed
    $response = $this->actingAs($admin)
        ->put("/reports/{$report->id}/status", [
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
    $category = Category::firstOrCreate([
        'name' => 'Kebakaran',
        'sla_hours' => 24,
    ]);

    $report = Report::factory()->create([
        'category_id' => $category->id,
        'status' => 'OPEN',
    ]);

    // SLA deadline should be 24 hours from reported_at
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
    $user = User::factory()->create(['role' => 'field_officer']);

    $response = $this->actingAs($user)->post('/reports', [
        'title' => 'Test GPS',
        'category_id' => 1,
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
    $user = User::factory()->create(['role' => 'field_officer']);

    $photo = UploadedFile::fake()->image('test_photo.jpg');

    $response = $this->actingAs($user)->post('/reports', [
        'title' => 'Test Photo Upload',
        'category_id' => 1,
        'latitude' => -7.7956,
        'longitude' => 110.3695,
        'photo' => $photo,
    ]);

    $response->assertRedirect();
    $report = Report::where('title', 'Test Photo Upload')->first();
    $this->assertNotNull($report->photo_url);
});

it('report checklist_answers storage', function () {
    $category = Category::firstOrCreate([
        'name' => 'Kebakaran',
        'checklist_template' => json_encode([
            ['label' => 'Api masih menyala?', 'type' => 'boolean'],
            ['label' => 'Jumlah hektar terbakar', 'type' => 'number'],
        ]),
    ]);

    $user = User::factory()->create(['role' => 'field_officer']);

    $report = Report::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Test Checklist',
        'description' => 'Test checklist storage',
        'latitude' => -7.7956,
        'longitude' => 110.3695,
        'checklist_answers' => json_encode([
            'Api masih menyala?' => true,
            'Jumlah hektar terbakar' => 5,
        ]),
    ]);

    $this->assertEquals(true, $report->checklist_answers['Api masih menyala?']);
    $this->assertEquals(5, $report->checklist_answers['Jumlah hektar terbakar']);
});

it('admin can export reports as GeoJSON', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/reports/export-geojson');

    $response->assertOk();
    $response->assertSee('type');
    $response->assertSee('features');
});