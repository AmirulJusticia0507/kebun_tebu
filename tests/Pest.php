<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

uses(RefreshDatabase::class)->beforeEach(function () {
    $this->seed(RoleSeeder::class);
})->in('Feature');

function makeAdmin(array $attributes = []): User
{
    $admin = User::factory()->create(array_merge(['role' => 'admin'], $attributes));
    $admin->assignRole('admin');

    return $admin;
}

function makeFieldOfficer(array $attributes = []): User
{
    $officer = User::factory()->create(array_merge(['role' => 'field_officer'], $attributes));
    $officer->assignRole('field_officer');

    return $officer;
}
