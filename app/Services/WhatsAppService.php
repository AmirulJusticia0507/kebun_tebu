<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function send(User $user, string $type, string $message, array $data = []): bool
    {
        if (! config('services.fonnte.token') || ! $user->phone_number) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->withHeaders(['Authorization' => config('services.fonnte.token')])
                ->timeout(10)
                ->retry(2, 500)
                ->post(config('services.fonnte.url'), [
                    'target' => $user->phone_number,
                    'message' => $message,
                ]);

            if (! $response->successful() || $response->json('status') === false) {
                Log::warning('WhatsApp delivery failed.', ['user_id' => $user->id, 'status' => $response->status()]);
                return false;
            }

            Notification::create([
                'type' => $type,
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => $data + ['message' => $message],
                'read_at' => now(),
                'channel' => 'whatsapp',
                'sent_at' => now(),
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::warning('WhatsApp delivery error.', ['user_id' => $user->id, 'error' => $exception->getMessage()]);
            return false;
        }
    }
}
