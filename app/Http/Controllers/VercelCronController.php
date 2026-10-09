<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class VercelCronController extends Controller
{
    /**
     * Run daily production maintenance from Vercel Cron.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('services.vercel.cron_secret');
        $token = (string) $request->bearerToken();

        if ($secret === '' || $token === '' || ! hash_equals($secret, $token)) {
            abort(401);
        }

        $commands = [
            'sla:check-escalation' => [],
            'reports:auto-close-stale' => [],
            'reports:daily-digest' => [],
            'queue:prune-failed' => ['--hours' => 168],
            'monitor:health' => ['--alert' => true],
        ];

        $results = [];
        $failed = false;

        foreach ($commands as $command => $arguments) {
            try {
                $exitCode = Artisan::call($command, $arguments);
                $results[$command] = $exitCode;
                $failed = $failed || $exitCode !== 0;
            } catch (Throwable $exception) {
                report($exception);
                $results[$command] = 1;
                $failed = true;
            }
        }

        return response()->json([
            'status' => $failed ? 'failed' : 'ok',
            'time' => now()->toIso8601String(),
            'commands' => $results,
        ], $failed ? 500 : 200);
    }
}
