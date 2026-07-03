<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        // Behind a reverse proxy (Apache/nginx/load balancer), read the real
        // client IP from the forwarded headers so attendance IPs are accurate.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->alias([
            'role'                => \App\Http\Middleware\RoleMiddleware::class,
            'registration.status' => \App\Http\Middleware\RegistrationStatusMiddleware::class,
            'profile.complete'    => \App\Http\Middleware\ProfileCompleteMiddleware::class,
        ]);

        $middleware->redirectUsersTo(function ($request) {
            $user = $request->user();
            if ($user) {
                return route($user->redirectRouteName());
            }
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
