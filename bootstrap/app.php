<?php

use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\EnsureLoginOtpPending;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\VerifyIntegrationToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'admin' => EnsureAdminAccess::class,
            'active' => EnsureUserIsActive::class,
            'integration' => VerifyIntegrationToken::class,
            'login.otp' => EnsureLoginOtpPending::class,
        ]);

        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('customer') || $request->is('customer/*')) {
                return route('customer.login');
            }

            return route('login');
        });

        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('customer/login') || $request->is('customer/register')) {
                return route('customer.warranties.index');
            }

            return route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
