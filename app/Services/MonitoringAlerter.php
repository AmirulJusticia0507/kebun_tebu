<?php

namespace App\Services;

use App\Jobs\SendMonitoringWebhook;
use App\Jobs\SendWebPushNotification;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\MonitoringAlertMail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as MailNotification;

class MonitoringAlerter
{
    /**
     * Kirim alert monitoring ke seluruh channel yang aktif.
     *
     * @param  array<string, mixed>  $context
     */
    public function alert(
        string $type,
        string $title,
        string $message,
        array $context = [],
        string $severity = 'warning',
        ?string $dedupeKey = null,
        ?int $cooldownMinutes = null,
    ): bool {
        $cooldown = $cooldownMinutes ?? (int) config('monitoring.alert_cooldown_minutes');
        if ($cooldown > 0) {
            $cacheKey = 'monitoring:alert:'.md5($type.'|'.($dedupeKey ?? ''));
            if (Cache::has($cacheKey)) {
                return false;
            }
            Cache::put($cacheKey, now()->toIso8601String(), now()->addMinutes($cooldown));
        }

        $admins = User::where('role', 'admin')->get();
        $payload = $this->payload($type, $title, $message, $severity, $context);

        foreach ($admins as $admin) {
            Notification::create([
                'type' => $type,
                'notifiable_type' => User::class,
                'notifiable_id' => $admin->id,
                'data' => [
                    'title' => $title,
                    'message' => $message,
                    'severity' => $severity,
                    'url' => route('admin.health'),
                    'context' => $context,
                ],
                'channel' => 'database',
                'sent_at' => now(),
            ]);

            SendWebPushNotification::dispatch(
                $admin->id,
                $type,
                $title,
                $message,
                ['url' => route('admin.health')] + $context,
            );
        }

        $this->sendMail($admins, $title, $message, $context);
        $this->sendWebhook($payload);

        Log::info('Monitoring alert dikirim.', $payload);

        return true;
    }

    /**
     * @param  Collection<int, User>  $admins
     * @param  array<string, mixed>  $context
     */
    private function sendMail(Collection $admins, string $title, string $message, array $context): void
    {
        $recipients = $admins->filter(fn (User $admin) => $admin->email && ! $admin->trashed());

        MailNotification::send($recipients->all(), new MonitoringAlertMail($title, $message, $context));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendWebhook(array $payload): void
    {
        if (! config('monitoring.webhook_url')) {
            return;
        }

        SendMonitoringWebhook::dispatch($payload);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function payload(string $type, string $title, string $message, string $severity, array $context): array
    {
        return [
            'app' => config('app.name'),
            'environment' => app()->environment(),
            'host' => gethostname() ?: 'unknown',
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'context' => $context,
            'time' => now()->toIso8601String(),
        ];
    }
}
