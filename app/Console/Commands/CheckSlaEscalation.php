<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSlaEscalation extends Command
{
    protected $signature = 'sla:check-escalation';
    protected $description = 'Check reports close to SLA deadline and log warnings / notify admins';

    public function handle(): int
    {
        $overdueReports = Report::whereIn('status', ['OPEN', 'ON_PROGRESS'])
            ->whereNotNull('sla_deadline')
            ->where('sla_deadline', '<=', now()->addHours(2))
            ->get();

        $count = $overdueReports->count();
        $this->info("Found {$count} reports requiring SLA escalation.");
        Log::info("SLA Escalation Check: {$count} reports near or past SLA deadline.");

        foreach ($overdueReports as $report) {
            // Log warning for SLA escalation
            Log::warning("SLA Warning: Report #{$report->id} ('{$report->title}') deadline is {$report->sla_deadline}");
        }

        return self::SUCCESS;
    }
}
