<?php

it('gps location permission scenarios', function () {
    $user = User::factory()->create(['role' => 'field_officer']);

    $response = $this->actingAs($user)->post('/reports', [
        'title' => 'Test GPS Scenario',
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

it('camera upload validation', function () {
    $response = $this->actingAs(User::factory()->create())
        ->withHeaders(['CONTENT_TYPE' => 'multipart/form-data'])
        ->post('/reports', [
            'title' => 'Test Photo',
            'category_id' => 1,
            'latitude' => -7.7956,
            'longitude' => 110.3695,
            'photo' => null,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('reports', ['title' => 'Test Photo']);
});

it('report status flow', function () {
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

    $this->assertNotNull($report->sla_deadline);
    $this->assertTrue($report->sla_deadline->gt(now()));
    $this->assertTrue($report->sla_deadline->lte(now()->addHours(25)));
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