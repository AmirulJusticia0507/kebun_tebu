<?php

namespace App\Console\Commands;

use App\Services\HealthMonitor;
use App\Services\MonitoringAlerter;
use Illuminate\Console\Command;

class MonitorHealth extends Command
{
    protected $signature = 'monitor:health
        {--alert : Kirim alert ke seluruh channel untuk check yang gagal/perlu perhatian}
        {--json : Output hasil check dalam format JSON}';

    protected $description = 'Run system health checks (database, storage, disk, scheduler, queue)';

    public function handle(HealthMonitor $monitor, MonitoringAlerter $alerter): int
    {
        $checks = $monitor->checks();
        $failed = array_values(array_filter($checks, fn (array $check) => $check['status'] === 'fail'));
        $warning = array_values(array_filter($checks, fn (array $check) => $check['status'] === 'warn'));

        if ($this->option('json')) {
            $this->line(json_encode([
                'healthy' => $failed === [],
                'checks' => $checks,
                'time' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['Check', 'Status', 'Detail'],
                array_map(fn (array $check) => [$check['name'], strtoupper($check['status']), $check['message']], $checks)
            );
        }

        if ($this->option('alert') && ($failed !== [] || $warning !== [])) {
            $lines = array_map(
                fn (array $check) => "[{$check['status']}] {$check['name']}: {$check['message']}",
                array_merge($failed, $warning)
            );

            $alerter->alert(
                'system.health',
                $failed !== [] ? 'Health check sistem gagal' : 'Health check sistem perlu perhatian',
                implode("\n", $lines),
                ['checks' => array_merge($failed, $warning)],
                $failed !== [] ? 'critical' : 'warning',
                'health:'.implode(',', array_map(fn (array $check) => $check['name'], array_merge($failed, $warning))),
            );
        }

        if (! $this->option('alert') && ($failed !== [] || $warning !== [])) {
            $this->newLine();
            $this->warn('Jalankan dengan --alert untuk mengirim peringatan ke admin.');
        }

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }
}
