<?php

use Spatie\Permission\Models\Role;

it('roles are seeded', function () {
    expect(Role::where('name', 'field_officer')->exists())->toBeTrue()
        ->and(Role::where('name', 'admin')->exists())->toBeTrue();
});
