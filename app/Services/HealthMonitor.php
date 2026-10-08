<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

class HealthMonitor
{
    /**
     * Jalankan seluruh health check.
     *
     * @return array<int, array{name: string, status: string, message: string}>
     */
    public function checks(): array
    {
        return [
            $this->databaseCheck(),
            $this->storageCheck(),
            $this->diskCheck(),
            $this->schedulerCheck(),
            $this->failedJobsCheck(),
            $this->queueBacklogCheck(),
        ];
    }

    public function healthy(): bool
    {
        foreach ($this->checks() as $check) {
            if ($check['status'] === 'fail') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    public function databaseCheck(): array
    {
        try {
            DB::select('select 1 as ok');

            return $this->pass('database', 'Koneksi database berhasil.');
        } catch (Throwable $e) {
            return $this->fail('database', 'Koneksi database gagal: '.$e->getMessage());
        }
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    public function storageCheck(): array
    {
        $targets = [storage_path('app'), storage_path('framework')];
        foreach ($targets as $dir) {
            if (! is_dir($dir)) {
                return $this->fail('storage', "Direktori storage tidak ditemukan: {$dir}");
            }
            if (! is_writable($dir)) {
                return $this->fail('storage', "Direktori storage tidak dapat ditulis: {$dir}");
            }

            $probe = $dir.DIRECTORY_SEPARATOR.'.health-probe';
            if (@file_put_contents($probe, 'ok') === false) {
                return $this->fail('storage', "Gagal menulis file probe ke: {$dir}");
            }
            @unlink($probe);
        }

        $freeMb = $this->freeMb(storage_path());
        $minMb = (int) config('monitoring.storage_min_free_mb');
        if ($freeMb !== null && $freeMb < $minMb) {
            return $this->fail('storage', "Ruang kosong storage tinggal {$freeMb} MB (minimum {$minMb} MB).");
        }

        return $this->pass('storage', 'Storage dapat ditulis'.($freeMb !== null ? " (sisa {$freeMb} MB)." : '.'));
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    public function diskCheck(): array
    {
        $freeMb = $this->freeMb(base_path());
        $minMb = (int) config('monitoring.disk_min_free_mb');

        if ($freeMb === null) {
            return $this->pass('disk', 'Cek ruang disk tidak didukung di platform ini.');
        }

        if ($freeMb < $minMb) {
            return $this->fail('disk', "Ruang kosong disk tinggal {$freeMb} MB (minimum {$minMb} MB).");
        }

        return $this->pass('disk', "Ruang disk mencukupi (sisa {$freeMb} MB).");
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    public function schedulerCheck(): array
    {
        $file = (string) config('monitoring.heartbeat_file');
        if ($file === '') {
            return $this->pass('scheduler', 'Check scheduler dilewati (MONITORING_HEARTBEAT_FILE kosong; tidak ada cron).');
        }
        if (! is_file($file)) {
            return $this->fail('scheduler', 'Heartbeat scheduler belum ada; pastikan cron menjalankan php artisan schedule:run setiap menit.');
        }

        $payload = json_decode((string) file_get_contents($file), true);
        $ranAt = is_array($payload) ? ($payload['ran_at'] ?? null) : null;
        $timestamp = $ranAt ? strtotime((string) $ranAt) : false;

        if ($timestamp === false) {
            return $this->fail('scheduler', 'Heartbeat scheduler tidak valid.');
        }

        $ageMinutes = (int) floor((time() - $timestamp) / 60);
        $maxMinutes = (int) config('monitoring.scheduler_max_age_minutes');

        if ($ageMinutes > $maxMinutes) {
            return $this->fail('scheduler', "Scheduler terakhir berjalan {$ageMinutes} menit lalu (maksimum {$maxMinutes} menit).");
        }

        return $this->pass('scheduler', "Scheduler berjalan {$ageMinutes} menit lalu.");
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    public function failedJobsCheck(): array
    {
        if (! $this->tableExists('failed_jobs')) {
            return $this->pass('failed_jobs', 'Tabel failed_jobs belum tersedia (belum ada job queue).');
        }

        $count = (int) DB::table('failed_jobs')->count();
        $max = (int) config('monitoring.max_failed_jobs');

        if ($count > $max) {
            return $this->warn('failed_jobs', "{$count} failed jobs melebihi batas {$max}. Jalankan php artisan queue:failed.");
        }

        return $this->pass('failed_jobs', "{$count} failed jobs (batas {$max}).");
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    public function queueBacklogCheck(): array
    {
        if (config('queue.default') !== 'database' || ! $this->tableExists('jobs')) {
            return $this->pass('queue', 'Queue tidak memakai driver database; cek backlog dilewati.');
        }

        $count = (int) DB::table('jobs')->count();
        $max = (int) config('monitoring.max_queue_backlog');

        if ($count > $max) {
            return $this->warn('queue', "Backlog queue {$count} job melebihi batas {$max}; worker mungkin tidak berjalan.");
        }

        return $this->pass('queue', "{$count} job antre (batas {$max}).");
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function pass(string $name, string $message): array
    {
        return ['name' => $name, 'status' => 'ok', 'message' => $message];
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function warn(string $name, string $message): array
    {
        return ['name' => $name, 'status' => 'warn', 'message' => $message];
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function fail(string $name, string $message): array
    {
        return ['name' => $name, 'status' => 'fail', 'message' => $message];
    }

    private function freeMb(string $path): ?int
    {
        $bytes = @disk_free_space($path);
        if ($bytes === false) {
            return null;
        }

        return (int) floor($bytes / 1048576);
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }
}
