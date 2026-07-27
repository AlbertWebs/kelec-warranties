<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyIntegrationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('services.kelec.integration_token', env('INTEGRATION_API_TOKEN'));

        if ($configured === '') {
            // Allow mock/local integrations when no token is configured.
            if (app()->environment('local', 'testing')) {
                return $next($request);
            }

            return response()->json(['message' => 'Integration token is not configured.'], 503);
        }

        $provided = $request->bearerToken()
            ?: $request->header('X-Integration-Token')
            ?: $request->input('api_token');

        if (! hash_equals($configured, (string) $provided)) {
            return response()->json(['message' => 'Unauthorized integration request.'], 401);
        }

        $timestamp = $request->header('X-Request-Timestamp');
        if ($timestamp && abs(now()->timestamp - (int) $timestamp) > 300) {
            return response()->json(['message' => 'Request timestamp expired.'], 401);
        }

        return $next($request);
    }
}
