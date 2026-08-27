<?php

namespace App\Console\Commands;

use App\Models\Report;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateDailyDigest extends Command
{
    protected $signature = 'reports:daily-digest';
    protected $description = 'Generate daily digest summary of reports for management';

    public function handle(): int
    {
        $yesterday = now()->subDay();
        $newCount = Report::whereDate('created_at', '>=', $yesterday)->count();
        $closedCount = Report::where('status', 'CLOSED')->whereDate('resolved_at', '>=', $yesterday)->count();
        $totalOpen = Report::where('status', 'OPEN')->count();

        $summary = "Daily Digest (" . now()->format('Y-m-d') . "): {$newCount} Laporan Baru, {$closedCount} Selesai, Total Open: {$totalOpen}";
        $this->info($summary);
        Log::info($summary);

        return self::SUCCESS;
    }
}
