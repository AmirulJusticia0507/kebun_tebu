<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    /** Admin bisa lihat semua laporan */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /** Admin atau pemilik laporan bisa melihat detail */
    public function view(User $user, Report $report): bool
    {
        return $user->role === 'admin' || $user->id === $report->user_id;
    }

    /** Semua user bisa buat laporan */
    public function create(User $user): bool
    {
        return true;
    }

    /** Hanya admin bisa mengubah status laporan */
    public function update(User $user, Report $report): bool
    {
        return $user->role === 'admin';
    }

    /** Hanya admin yang bisa hapus */
    public function delete(User $user, Report $report): bool
    {
        return $user->role === 'admin';
    }
}
