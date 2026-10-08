<?php

namespace App\Http\Controllers;

use App\Services\HealthMonitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class HealthCheckController extends Controller
{
    /**
     * Endpoint publik ringkas untuk monitoring eksternal (Uptime Kuma, dst).
     * Hanya mengembalikan status; detail check tersedia untuk admin.
     */
    public function status(HealthMonitor $monitor): JsonResponse
    {
        $checks = $monitor->checks();
        $failed = array_values(array_filter($checks, fn (array $check) => $check['status'] === 'fail'));
        $healthy = $failed === [];

        if (! $healthy) {
            Log::warning('Public health check failed.', ['checks' => $failed]);
        }

        return response()->json([
            'status' => $healthy ? 'ok' : 'error',
            'time' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    /**
     * Detail seluruh health check, khusus admin.
     */
    public function show(HealthMonitor $monitor): JsonResponse
    {
        $checks = $monitor->checks();
        $failed = array_values(array_filter($checks, fn (array $check) => $check['status'] === 'fail'));

        return response()->json([
            'healthy' => $failed === [],
            'checks' => $checks,
            'queue' => config('queue.default'),
            'environment' => app()->environment(),
            'time' => now()->toIso8601String(),
        ], $failed === [] ? 200 : 503);
    }
}
