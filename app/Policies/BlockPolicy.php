<?php

namespace App\Policies;

use App\Models\Block;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BlockPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Block $block): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Block $block): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Block $block): bool
    {
        return $user->role === 'admin';
    }
}
