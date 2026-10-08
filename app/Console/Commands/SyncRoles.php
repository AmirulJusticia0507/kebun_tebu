<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SyncRoles extends Command
{
    protected $signature = 'roles:sync
        {--dry-run : Tampilkan perbedaan tanpa mengubah data (keluar dengan status gagal jika ada drift)}';

    protected $description = 'Reconcile the users.role column with Spatie roles (column wins)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $drift = [];

        User::with('roles')->orderBy('id')->each(function (User $user) use (&$drift, $dryRun) {
            $column = $user->role;
            $spatie = $user->getRoleNames()->all();

            if ($spatie === [$column]) {
                return;
            }

            $drift[] = [
                $user->id,
                $user->email,
                $column,
                $spatie === [] ? '-' : implode(', ', $spatie),
            ];

            if (! $dryRun) {
                $user->syncRoleFromColumn();
            }
        });

        if ($drift === []) {
            $this->info('Kolom role dan Spatie roles konsisten untuk seluruh user.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Email', 'Kolom role', 'Spatie roles'], $drift);

        if ($dryRun) {
            $this->warn(count($drift).' user memiliki perbedaan. Jalankan php artisan roles:sync untuk memperbaiki.');

            return self::FAILURE;
        }

        $this->info(count($drift).' user disinkronkan dari kolom role ke Spatie roles.');

        return self::SUCCESS;
    }
}
