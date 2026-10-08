<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function send(User $user, string $type, string $title, string $message, array $data = []): bool
    {
        if (! config('services.webpush.public_key') || ! config('services.webpush.private_key')) {
            return false;
        }

        $subscriptions = PushSubscription::where('user_id', $user->id)->get();
        if ($subscriptions->isEmpty()) {
            return false;
        }

        $webPush = new WebPush(['VAPID' => [
            'subject' => config('services.webpush.subject'),
            'publicKey' => config('services.webpush.public_key'),
            'privateKey' => config('services.webpush.private_key'),
        ]]);

        $payload = json_encode($data + ['title' => $title, 'body' => $message]);
        foreach ($subscriptions as $record) {
            $webPush->queueNotification(Subscription::create([
                'endpoint' => $record->endpoint,
                'publicKey' => $record->public_key,
                'authToken' => $record->auth_token,
                'contentEncoding' => $record->content_encoding,
            ]), $payload);
        }

        $sent = false;
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent = true;
            } elseif ($report->isSubscriptionExpired()) {
                PushSubscription::where('endpoint', (string) $report->getRequest()->getUri())->delete();
            } else {
                Log::warning('Web Push delivery failed.', ['reason' => $report->getReason()]);
            }
        }

        if ($sent) {
            Notification::create([
                'type' => $type,
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => $data + ['title' => $title, 'message' => $message],
                'read_at' => now(),
                'channel' => 'push',
                'sent_at' => now(),
            ]);
        }

        return $sent;
    }
}
