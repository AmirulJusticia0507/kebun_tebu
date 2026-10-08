<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MonitoringAlertMail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $title,
        public string $message,
        public array $context = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[Monitoring] '.$this->title)
            ->line($this->message)
            ->line('Environment: '.app()->environment())
            ->line('Waktu: '.now()->toIso8601String())
            ->action('Buka dashboard monitoring', route('admin.health'));

        foreach ($this->context as $key => $value) {
            if (is_scalar($value) || is_null($value)) {
                $mail->line($key.': '.(string) $value);
            }
        }

        return $mail->line('Email ini dikirim otomatis oleh sistem monitoring Kebun Tebu.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}
