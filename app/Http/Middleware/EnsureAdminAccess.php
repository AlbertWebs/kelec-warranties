<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(['super_admin', 'warranty_admin', 'customer_support'])) {
            abort(403, 'You do not have access to the administration area.');
        }

        return $next($request);
    }
}
