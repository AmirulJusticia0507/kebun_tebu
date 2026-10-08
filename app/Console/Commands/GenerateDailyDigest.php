<?php

namespace App\Console\Commands;

use App\Jobs\SendWebPushNotification;
use App\Jobs\SendWhatsAppNotification;
use App\Models\Notification;
use App\Models\Report;
use App\Models\User;
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

        $summary = 'Daily Digest ('.now()->format('Y-m-d')."): {$newCount} Laporan Baru, {$closedCount} Selesai, Total Open: {$totalOpen}";
        $this->info($summary);
        Log::info($summary);

        User::where('role', 'admin')->each(function (User $admin) use ($summary, $newCount, $closedCount, $totalOpen) {
            if (Notification::where('type', 'reports.daily_digest')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $admin->id)
                ->whereDate('created_at', today())
                ->exists()) {
                return;
            }

            Notification::create([
                'type' => 'reports.daily_digest',
                'notifiable_type' => User::class,
                'notifiable_id' => $admin->id,
                'data' => [
                    'title' => 'Ringkasan laporan harian',
                    'message' => $summary,
                    'new_count' => $newCount,
                    'closed_count' => $closedCount,
                    'open_count' => $totalOpen,
                    'url' => route('dashboard'),
                ],
                'channel' => 'database',
                'sent_at' => now(),
            ]);

            SendWhatsAppNotification::dispatch(
                $admin->id,
                'reports.daily_digest',
                $summary,
                ['new_count' => $newCount, 'closed_count' => $closedCount, 'open_count' => $totalOpen],
            );
            SendWebPushNotification::dispatch(
                $admin->id,
                'reports.daily_digest',
                'Ringkasan laporan harian',
                $summary,
                ['url' => route('dashboard')],
            );
        });

        return self::SUCCESS;
    }
}
