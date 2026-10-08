<?php

return [
    // URL webhook generik (Slack/Telegram/Discord/ntfy/Uptime Kuma, dll).
    'webhook_url' => env('MONITORING_WEBHOOK_URL'),

    // Alamat email tambahan selain akun admin untuk alert monitoring.
    'alert_mail' => env('MONITORING_ALERT_MAIL'),

    // Batas minimum ruang kosong dalam MB.
    // Vercel exposes a small ephemeral /tmp volume; writability is still probed,
    // but host-style free-space thresholds do not apply to serverless functions.
    'disk_min_free_mb' => (int) env('MONITORING_DISK_MIN_MB', env('VERCEL') ? 0 : 512),
    'storage_min_free_mb' => (int) env('MONITORING_STORAGE_MIN_MB', env('VERCEL') ? 0 : 512),

    // Heartbeat scheduler dianggap basi setelah X menit.
    'scheduler_max_age_minutes' => (int) env('MONITORING_SCHEDULER_MAX_AGE', 5),

    // Batas peringatan jumlah failed jobs dan backlog queue database.
    'max_failed_jobs' => (int) env('MONITORING_MAX_FAILED_JOBS', 10),
    'max_queue_backlog' => (int) env('MONITORING_MAX_QUEUE_BACKLOG', 500),

    // Error log: jumlah minimum error dalam jendela scan untuk memicu alert.
    'error_threshold' => (int) env('MONITORING_ERROR_THRESHOLD', 5),
    'error_scan_max_bytes' => (int) env('MONITORING_ERROR_SCAN_MAX_BYTES', 5242880),

    // Cooldown alert serupa (menit) supaya tidak spam.
    'alert_cooldown_minutes' => (int) env('MONITORING_ALERT_COOLDOWN', 30),

    // Heartbeat scheduler. Set MONITORING_HEARTBEAT_FILE="" di serverless
    // (Vercel/Lambda) karena cron artisan tidak berjalan — check dilewati.
    'heartbeat_file' => env('MONITORING_HEARTBEAT_FILE', env('VERCEL') ? '' : storage_path('framework/schedule-heartbeat.json')),
];
