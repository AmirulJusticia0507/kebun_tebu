<?php

use App\Models\Category;
use App\Models\Notification;

it('completes the offline first journey from login to map', function () {
    $admin = makeAdmin();
    $officer = makeFieldOfficer();
    $category = Category::factory()->create();

    // 1. Login di perangkat mobile
    $this->post('/login', [
        'email' => $officer->email,
        'password' => 'password',
    ])->assertRedirect('/dashboard');
    $this->assertAuthenticated();

    // 2. Online: kirim laporan langsung dari form
    $this->post('/reports', [
        'category_id' => $category->id,
        'title' => 'Laporan online pertama',
        'description' => 'Terlihat dari jalan utama',
        'latitude' => -7.7956,
        'longitude' => 110.3695,
    ])->assertRedirect('/map');

    // 3. Offline: dua draft disimpan lokal di perangkat (tidak terkirim)
    $drafts = [
        [
            'client_uuid' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'title' => 'Draft offline satu',
            'category_id' => $category->id,
            'latitude' => -7.7957,
            'longitude' => 110.3696,
            'created_at' => now()->subMinutes(30)->toIso8601String(),
        ],
        [
            'client_uuid' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'title' => 'Draft offline dua',
            'category_id' => $category->id,
            'latitude' => -7.7958,
            'longitude' => 110.3697,
            'created_at' => now()->subMinutes(20)->toIso8601String(),
        ],
    ];

    // 4. Kembali online: batch draft tersinkron
    $this->postJson('/reports/sync', compact('drafts'))
        ->assertOk()
        ->assertJsonPath('created_count', 2)
        ->assertJsonPath('duplicate_count', 0);

    // 5. Koneksi putus lagi saat menerima respons: retry batch yang sama tetap aman
    $this->postJson('/reports/sync', compact('drafts'))
        ->assertOk()
        ->assertJsonPath('created_count', 0)
        ->assertJsonPath('duplicate_count', 2);

    $this->assertDatabaseCount('reports', 3);

    // 6. Semua laporan tampil di peta
    $this->get('/map')
        ->assertOk()
        ->assertSee('Laporan online pertama')
        ->assertSee('Draft offline satu')
        ->assertSee('Draft offline dua');

    // 7. Laporan tersinkron masuk ke daftar laporan milik petugas
    $this->get('/reports')
        ->assertOk()
        ->assertSee('Laporan online pertama')
        ->assertSee('Draft offline satu')
        ->assertSee('Draft offline dua');

    // 8. Admin menerima notifikasi untuk ketiga laporan
    expect(Notification::where('type', 'report.created')->count())->toBe(3);
});

it('keeps offline drafts isolated between user sessions', function () {
    $category = Category::factory()->create();
    $officerA = makeFieldOfficer();
    $officerB = makeFieldOfficer();

    $draft = [
        'client_uuid' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
        'title' => 'Draft milik A',
        'category_id' => $category->id,
        'latitude' => -7.1,
        'longitude' => 110.1,
    ];

    $this->post('/login', ['email' => $officerA->email, 'password' => 'password'])
        ->assertRedirect('/dashboard');
    $this->postJson('/reports/sync', ['drafts' => [$draft]])->assertJsonPath('created_count', 1);

    $this->post('/logout');
    $this->post('/login', ['email' => $officerB->email, 'password' => 'password']);

    $this->postJson('/reports/sync', ['drafts' => [
        array_merge($draft, ['title' => 'Draft milik B']),
    ]])->assertJsonPath('created_count', 1);

    $this->assertDatabaseCount('reports', 2);

    // Petugas B tidak melihat laporan milik petugas A di daftarnya
    $this->get('/reports')
        ->assertOk()
        ->assertSee('Draft milik B')
        ->assertDontSee('Draft milik A');
});

it('map filters work after sync', function () {
    $admin = makeAdmin();
    $category = Category::factory()->create();
    $officer = makeFieldOfficer();

    $this->actingAs($officer)->postJson('/reports/sync', ['drafts' => [
        [
            'client_uuid' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd',
            'title' => 'Hama setelah sync',
            'category_id' => $category->id,
            'latitude' => -7.7,
            'longitude' => 110.3,
        ],
    ]])->assertOk();

    $this->actingAs($admin)->get('/map?status=OPEN')
        ->assertOk()
        ->assertSee('Hama setelah sync');

    $this->actingAs($admin)->get('/map?status=CLOSED')
        ->assertOk()
        ->assertDontSee('Hama setelah sync');
});
