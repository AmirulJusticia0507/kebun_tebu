<?php

use App\Models\Block;
use App\Models\Category;
use App\Models\Report;

it('admin can access dashboard statistics', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)->get('/dashboard')->assertOk();
    $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
});

it('field officer is redirected from dashboard to map', function () {
    $officer = makeFieldOfficer();

    $this->actingAs($officer)->get('/dashboard')->assertRedirect('/map');
});

it('field officer cannot access admin dashboard', function () {
    $officer = makeFieldOfficer();

    $this->actingAs($officer)->get('/admin/dashboard')->assertForbidden();
});

it('field officer only sees own reports in the index', function () {
    $officer = makeFieldOfficer();
    $other = makeFieldOfficer();

    Report::factory()->create(['user_id' => $officer->id, 'title' => 'Laporan Milik Saya']);
    Report::factory()->create(['user_id' => $other->id, 'title' => 'Laporan Orang Lain']);

    $response = $this->actingAs($officer)->get('/reports');

    $response->assertOk();
    $response->assertSee('Laporan Milik Saya');
    $response->assertDontSee('Laporan Orang Lain');
});

it('admin sees all reports in the index', function () {
    $admin = makeAdmin();
    $officer = makeFieldOfficer();
    $other = makeFieldOfficer();

    Report::factory()->create(['user_id' => $officer->id, 'title' => 'Laporan Milik Saya']);
    Report::factory()->create(['user_id' => $other->id, 'title' => 'Laporan Orang Lain']);

    $response = $this->actingAs($admin)->get('/reports');

    $response->assertOk();
    $response->assertSee('Laporan Milik Saya');
    $response->assertSee('Laporan Orang Lain');
});

it('report owner can view own report detail', function () {
    $officer = makeFieldOfficer();
    $report = Report::factory()->create(['user_id' => $officer->id]);

    $this->actingAs($officer)->get("/reports/{$report->id}")->assertOk();
});

it('admin can view any report detail', function () {
    $admin = makeAdmin();
    $officer = makeFieldOfficer();
    $report = Report::factory()->create(['user_id' => $officer->id]);

    $this->actingAs($admin)->get("/reports/{$report->id}")->assertOk();
});

it('field officer cannot view report owned by someone else', function () {
    $officer = makeFieldOfficer();
    $other = makeFieldOfficer();
    $report = Report::factory()->create(['user_id' => $other->id]);

    $this->actingAs($officer)->get("/reports/{$report->id}")->assertForbidden();
});

it('field officer cannot update report status', function () {
    $officer = makeFieldOfficer();
    $report = Report::factory()->create(['user_id' => $officer->id]);

    $response = $this->actingAs($officer)->patch("/reports/{$report->id}/status", [
        'status' => 'CLOSED',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => 'OPEN']);
});

it('export endpoints are admin only', function () {
    $officer = makeFieldOfficer();

    $this->actingAs($officer)->get('/reports/export/csv')->assertForbidden();
    $this->actingAs($officer)->get('/reports/export/geojson')->assertForbidden();
});

it('admin CRUD routes are forbidden for field officers', function () {
    $officer = makeFieldOfficer();

    $this->actingAs($officer)->get('/dashboard/users')->assertForbidden();
    $this->actingAs($officer)->post('/dashboard/users', [
        'name' => 'X',
        'email' => 'x@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'field_officer',
    ])->assertForbidden();

    $this->actingAs($officer)->get('/dashboard/categories')->assertForbidden();
    $this->actingAs($officer)->post('/dashboard/categories', [
        'name' => 'Hama',
        'color_code' => '#ff0000',
    ])->assertForbidden();

    $this->actingAs($officer)->get('/dashboard/blocks')->assertForbidden();
    $this->actingAs($officer)->post('/dashboard/blocks', [
        'code' => 'BLOK-NEW',
        'name' => 'Blok Baru',
    ])->assertForbidden();
});

it('admin can manage users categories and blocks', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)->get('/dashboard/users')->assertOk();
    $this->actingAs($admin)->get('/dashboard/categories')->assertOk();
    $this->actingAs($admin)->get('/dashboard/blocks')->assertOk();

    $this->actingAs($admin)->post('/dashboard/users', [
        'name' => 'Petugas Baru',
        'email' => 'baru@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'field_officer',
    ])->assertRedirect()->assertSessionHas('success');
    $this->assertDatabaseHas('users', ['email' => 'baru@example.com', 'role' => 'field_officer']);

    $this->actingAs($admin)->post('/dashboard/categories', [
        'name' => 'Hama',
        'color_code' => '#ff0000',
    ])->assertRedirect()->assertSessionHas('success');
    $this->assertDatabaseHas('categories', ['name' => 'Hama']);

    $this->actingAs($admin)->post('/dashboard/blocks', [
        'code' => 'BLOK-NEW',
        'name' => 'Blok Baru',
    ])->assertRedirect()->assertSessionHas('success');
    $this->assertDatabaseHas('blocks', ['code' => 'BLOK-NEW']);
});

it('admin cannot delete the last remaining admin', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)->delete("/dashboard/users/{$admin->id}");

    $response->assertSessionHas('error');
    $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
});

it('category with reports cannot be deleted', function () {
    $admin = makeAdmin();
    $category = Category::factory()->create();
    Report::factory()->create(['category_id' => $category->id]);

    $response = $this->actingAs($admin)->delete("/dashboard/categories/{$category->id}");

    $response->assertSessionHas('error');
    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});

it('block delete only deactivates the block', function () {
    $admin = makeAdmin();
    $block = Block::factory()->create();

    $this->actingAs($admin)->delete("/dashboard/blocks/{$block->id}")
        ->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseHas('blocks', ['id' => $block->id, 'is_active' => false]);
});
