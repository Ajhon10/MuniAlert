<?php

use App\Http\Middleware\RestoreDeviceLogin;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(SecurityHeaders::class);
        $middleware->statefulApi();
        $middleware->appendToGroup('web', RestoreDeviceLogin::class);
        $middleware->appendToGroup('api', RestoreDeviceLogin::class);
        $middleware->prependToPriorityList(AuthenticatesRequests::class, RestoreDeviceLogin::class);
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash(['password', 'password_confirmation', 'current_password', 'code', 'challenge_token', 'token', 'secret', 'recovery_codes']);
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*', 'auth/*') || $request->expectsJson());
    })->create();
