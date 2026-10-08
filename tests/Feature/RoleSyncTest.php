<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

it('mirrors the role column into spatie roles on create', function () {
    $user = User::factory()->create(['role' => 'field_officer']);

    expect($user->getRoleNames()->all())->toBe(['field_officer'])
        ->and(Role::where('name', 'field_officer')->exists())->toBeTrue();
});

it('updates spatie roles when the role column changes', function () {
    $user = User::factory()->create(['role' => 'field_officer']);

    $user->role = 'admin';
    $user->save();

    expect($user->fresh()->getRoleNames()->all())->toBe(['admin']);
});

it('keeps the role column in sync when assigning spatie roles', function () {
    $user = User::factory()->create(['role' => 'field_officer']);

    $user->assignRole('admin');

    expect($user->fresh()->role)->toBe('admin');
});

it('keeps the role column in sync when syncing spatie roles', function () {
    $user = User::factory()->create(['role' => 'field_officer']);

    $user->syncRoles(['field_officer']);

    expect($user->fresh()->role)->toBe('field_officer')
        ->and($user->fresh()->getRoleNames()->all())->toBe(['field_officer']);
});

it('detects drift with roles:sync dry run', function () {
    $user = User::factory()->create(['role' => 'field_officer']);
    $user->roles()->detach();

    $this->artisan('roles:sync --dry-run')->assertExitCode(1);

    expect($user->fresh()->getRoleNames()->all())->toBe([]);
});

it('repairs drift with roles:sync', function () {
    $user = User::factory()->create(['role' => 'field_officer']);
    $user->roles()->detach();

    $this->artisan('roles:sync')->assertExitCode(0);

    expect($user->fresh()->getRoleNames()->all())->toBe(['field_officer']);
});

it('reports no drift when column and spatie roles match', function () {
    makeAdmin();
    makeFieldOfficer();

    $this->artisan('roles:sync')->assertExitCode(0);
});
