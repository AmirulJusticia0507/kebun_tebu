<?php

namespace App\Console\Commands;

use App\Models\Report;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCloseStaleReports extends Command
{
    protected $signature = 'reports:auto-close-stale';
    protected $description = 'Auto-close OPEN reports older than 30 days';

    public function handle(): int
    {
        $staleReports = Report::where('status', 'OPEN')
            ->where('reported_at', '<=', now()->subDays(30))
            ->get();

        $updated = 0;
        foreach ($staleReports as $report) {
            $report->update([
                'status'      => 'CLOSED',
                'admin_note'  => trim(($report->admin_note ?? '') . "\n[System Auto-Closed]: Laporan ditutup otomatis karena > 30 hari tanpa penanganan."),
                'resolved_at' => now(),
            ]);
            $updated++;
        }

        $this->info("Auto-closed {$updated} stale reports.");
        Log::info("AutoCloseStaleReports: {$updated} reports closed.");

        return self::SUCCESS;
    }
}
