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

    /** Semua user authenticated bisa lihat laporan */
    public function view(User $user, Report $report): bool
    {
        return true;
    }

    /** Semua user bisa buat laporan */
    public function create(User $user): bool
    {
        return true;
    }

    /** Admin atau pemilik laporan bisa update */
    public function update(User $user, Report $report): bool
    {
        return $user->role === 'admin' || $user->id === $report->user_id;
    }

    /** Hanya admin yang bisa hapus */
    public function delete(User $user, Report $report): bool
    {
        return $user->role === 'admin';
    }
}
