<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->headers->get('X-Request-ID') ?: (string) Str::uuid();

        Log::withContext([
            'request_id' => $requestId,
            'user_id' => $request->user()?->id,
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
