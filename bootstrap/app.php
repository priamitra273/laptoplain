<?php

use App\Http\Middleware\CheckRoutePermission;
use App\Http\Middleware\EnsureUuidIsValid;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'validate.uuid' => EnsureUuidIsValid::class,
            'route.permission' => CheckRoutePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function ($response, \Throwable $exception, Request $request) {
            if ($response->getStatusCode() === 404) {
                // Cek apakah user sudah login
                $isAuthenticated = auth()->check();

                // Pilih view berdasarkan status autentikasi
                $view = $isAuthenticated ? 'errors/NotFound' : 'errors/404';

                return Inertia::render($view)
                    ->toResponse($request)
                    ->setStatusCode(404);
            }

            return $response;
        });
    })
    ->create();
