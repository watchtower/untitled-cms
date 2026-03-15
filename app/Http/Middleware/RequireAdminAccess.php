<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            if ($request->expectsJson()) {
                abort(401, 'Unauthenticated.');
            }

            return redirect()->route('login');
        }

        if (! $request->user()->canAccessBackend()) {
            if ($request->expectsJson()) {
                abort(403, 'Access denied.');
            }

            return redirect('/');
        }

        return $next($request);
    }
}
