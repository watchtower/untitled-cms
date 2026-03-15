<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\CheckRedirects::class,
            \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);

        $middleware->alias([
            'can' => \App\Http\Middleware\CheckPermission::class,
            'admin' => \App\Http\Middleware\RequireAdminAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            $response = null;

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                $status = $e->getStatusCode();
                if (in_array($status, [403, 404, 500, 503])) {
                    // Always try to render an Inertia response if it's a web request looking for HTML
                    if ($request->header('X-Inertia') || !$request->wantsJson()) {
                        return \Inertia\Inertia::render('Error', ['status' => $status])
                            ->toResponse($request)
                            ->setStatusCode($status);
                    }
                }
            }

            return null;
        });
    })->create();
