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
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
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
            if (! $exception instanceof NotFoundHttpException) {
                return $response;
            }

            if ($request->expectsJson()) {
                return $response;
            }

            $view = Auth::check() ? 'errors/NotFound' : 'errors/404';

            return app(HandleInertiaRequests::class)->handle(
                $request,
                fn (Request $request) => Inertia::render($view)
                    ->toResponse($request)
                    ->setStatusCode(404)
            );
        });
    })
    ->create();
