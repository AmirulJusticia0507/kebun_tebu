<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Models\Notification;
use App\Models\User;
use App\Services\WhatsAppService;
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
            Log::warning("SLA Warning: Report #{$report->id} ('{$report->title}') deadline is {$report->sla_deadline}");

            if (Notification::where('type', 'report.sla_warning')
                ->where('data->report_id', $report->id)
                ->exists()) {
                continue;
            }

            User::where('role', 'admin')->each(function (User $admin) use ($report) {
                Notification::create([
                    'type' => 'report.sla_warning',
                    'notifiable_type' => User::class,
                    'notifiable_id' => $admin->id,
                    'data' => [
                        'title' => 'Peringatan SLA',
                        'message' => "Laporan {$report->title} mendekati atau melewati SLA.",
                        'report_id' => $report->id,
                        'sla_deadline' => $report->sla_deadline?->toIso8601String(),
                        'url' => route('reports.show', $report),
                    ],
                    'channel' => 'database',
                    'sent_at' => now(),
                ]);

                app(WhatsAppService::class)->send(
                    $admin,
                    'report.sla_warning',
                    "Peringatan SLA: {$report->title} mendekati atau melewati batas waktu.",
                    ['report_id' => $report->id],
                );
            });
        }

        return self::SUCCESS;
    }
}
