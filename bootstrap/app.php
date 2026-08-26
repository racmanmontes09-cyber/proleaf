<?php

use App\Http\Middleware\AuthenticateDevice;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withEvents()
        ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'device.auth' => AuthenticateDevice::class,
            'permission' => App\Http\Middleware\RequirePermission::class,
            'active' => EnsureUserIsActive::class,
        ]);

        $middleware->prependToPriorityList([
            ThrottleRequests::class,
            ThrottleRequestsWithRedis::class,
        ], AuthenticateDevice::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || $request->is('dashboard/telemetry/*'),
        );
    })->create();
