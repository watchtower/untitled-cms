<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRedirects
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        $path = $request->path();

        // Check for exact match in redirects
        // We might want to cache this lookup in production
        $redirect = Redirect::where('from_path', $path)
            ->where('active', true)
            ->first();

        if ($redirect) {
            $target = $redirect->to_path;

            // Security: prevent open redirect — only allow relative paths (A01)
            $normalizedTarget = strtolower(trim($target));
            if (str_starts_with($normalizedTarget, 'http://') || str_starts_with($normalizedTarget, 'https://') || str_starts_with($normalizedTarget, '//')) {
                // Log suspicious data and skip the redirect instead of following it
                \Illuminate\Support\Facades\Log::warning('Open redirect attempt blocked in CheckRedirects', [
                    'from' => $path,
                    'to' => $target,
                ]);
                return $next($request);
            }

            return redirect($target, $redirect->type);
        }

        // Also check if the path has a leading slash or not, ensuring consistency
        // The Request::path() returns path without leading slash
        // If we stored with leading slash, we might need to check that too
        // For now, we assume we store without leading slash or normalize it.

        return $next($request);
    }
}
