<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWebPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(
        public int $userId,
        public string $type,
        public string $title,
        public string $message,
        public array $data = [],
    ) {}

    public function handle(WebPushService $service): void
    {
        if ($user = User::find($this->userId)) {
            $service->send($user, $this->type, $this->title, $this->message, $this->data);
        }
    }
}
