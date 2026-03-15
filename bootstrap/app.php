<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRedirects;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireAdminAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            CheckRedirects::class,
            CheckMaintenanceMode::class,
        ]);

        $middleware->alias([
            'can' => CheckPermission::class,
            'admin' => RequireAdminAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            $response = null;

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                if (in_array($status, [403, 404, 500, 503])) {
                    // Always try to render an Inertia response if it's a web request looking for HTML
                    if ($request->header('X-Inertia') || ! $request->wantsJson()) {
                        return Inertia::render('Error', ['status' => $status])
                            ->toResponse($request)
                            ->setStatusCode($status);
                    }
                }
            }

            return null;
        });
    })->create();
