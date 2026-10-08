<?php

namespace App\Console\Commands;

use App\Services\MonitoringAlerter;
use Illuminate\Console\Command;

class MonitorErrors extends Command
{
    protected $signature = 'monitor:errors
        {--hours=1 : Jendela waktu scan dalam jam}
        {--path= : File atau direktori log (default: storage/logs)}
        {--alert : Kirim alert ketika error terdeteksi}
        {--json : Output hasil scan dalam format JSON}';

    protected $description = 'Scan application logs for ERROR/CRITICAL entries and alert on new failures';

    public function handle(MonitoringAlerter $alerter): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $path = $this->option('path') ?: storage_path('logs');
        $threshold = (int) config('monitoring.error_threshold');
        $cutoff = now()->subHours($hours)->getTimestamp();

        $files = $this->logFiles($path);
        $matches = [];
        foreach ($files as $file) {
            foreach ($this->scanFile($file, $cutoff) as $match) {
                $matches[] = $match;
            }
        }

        $errors = array_values(array_filter($matches, fn (array $m) => ! $this->isCritical($m['level'])));
        $critical = array_values(array_filter($matches, fn (array $m) => $this->isCritical($m['level'])));
        $triggered = $critical !== [] || count($errors) >= $threshold;

        $summary = sprintf(
            '%d error dan %d critical dalam %d jam terakhir (file log: %d).',
            count($errors),
            count($critical),
            $hours,
            count($files)
        );

        if ($this->option('json')) {
            $this->line(json_encode([
                'triggered' => $triggered,
                'errors' => count($errors),
                'critical' => count($critical),
                'files' => array_map('basename', $files),
                'samples' => array_slice($matches, 0, 5),
                'time' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->line($summary);
            foreach (array_slice($matches, 0, 10) as $match) {
                $this->line("  [{$match['level']}] {$match['time']} {$match['message']}");
            }
            if (count($matches) > 10) {
                $this->line('  ... dan '.(count($matches) - 10).' entri lainnya.');
            }
        }

        if ($triggered && $this->option('alert')) {
            $alerter->alert(
                'system.errors',
                'Error terdeteksi pada log aplikasi',
                $summary,
                [
                    'errors' => count($errors),
                    'critical' => count($critical),
                    'window_hours' => $hours,
                    'samples' => array_slice(array_column($matches, 'message'), 0, 3),
                ],
                $critical !== [] ? 'critical' : 'warning',
                'errors:'.now()->format('YmdH').':'.$hours,
            );
        }

        if ($triggered && ! $this->option('alert')) {
            $this->warn('Jalankan dengan --alert untuk mengirim peringatan ke admin.');
        }

        return $triggered ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function logFiles(string $path): array
    {
        if (is_file($path)) {
            return [$path];
        }

        if (! is_dir($path)) {
            return [];
        }

        $files = glob(rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'*.log') ?: [];
        $files = array_filter($files, fn (string $file) => @filemtime($file) >= now()->subDay()->getTimestamp());

        return array_values($files);
    }

    /**
     * @return array<int, array{time: string, level: string, message: string}>
     */
    private function scanFile(string $file, int $cutoff): array
    {
        $content = $this->tail($file, (int) config('monitoring.error_scan_max_bytes'));
        $results = [];

        foreach (explode("\n", $content) as $line) {
            if (! preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+\S+\.(ERROR|CRITICAL|EMERGENCY|ALERT)\s*:\s*(.+)$/', $line, $m)) {
                continue;
            }

            $timestamp = strtotime($m[1]);
            if ($timestamp === false || $timestamp < $cutoff) {
                continue;
            }

            $results[] = [
                'time' => $m[1],
                'level' => $m[2],
                'message' => mb_substr(trim(explode("\n", $m[3])[0]), 0, 300),
            ];
        }

        return $results;
    }

    private function tail(string $file, int $maxBytes): string
    {
        $size = @filesize($file);
        if ($size === false) {
            return '';
        }

        if ($size <= $maxBytes) {
            return (string) @file_get_contents($file);
        }

        $handle = @fopen($file, 'rb');
        if ($handle === false) {
            return '';
        }

        fseek($handle, -$maxBytes, SEEK_END);
        $content = (string) fread($handle, $maxBytes);
        fclose($handle);

        // Buang baris pertama yang mungkin terpotong.
        $newline = strpos($content, "\n");

        return $newline === false ? $content : substr($content, $newline + 1);
    }

    private function isCritical(string $level): bool
    {
        return in_array($level, ['CRITICAL', 'EMERGENCY', 'ALERT'], true);
    }
}
