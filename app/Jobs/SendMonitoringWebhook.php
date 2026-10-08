<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SendMonitoringWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public int $timeout = 20;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload) {}

    public function handle(): void
    {
        $url = config('monitoring.webhook_url');
        if (! $url) {
            return;
        }

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(10)
                ->post($url, $this->payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Monitoring webhook gagal dihubungi: '.$e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            throw new RuntimeException("Monitoring webhook mengembalikan HTTP {$response->status()}.");
        }
    }
}
