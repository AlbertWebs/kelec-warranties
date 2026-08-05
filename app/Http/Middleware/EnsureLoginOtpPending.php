<?php

namespace App\Http\Middleware;

use App\Services\AdminLoginOtpService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLoginOtpPending
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! data_get(session(AdminLoginOtpService::SESSION_KEY), 'user_id')) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Please sign in with your email and password first.']);
        }

        return $next($request);
    }
}
