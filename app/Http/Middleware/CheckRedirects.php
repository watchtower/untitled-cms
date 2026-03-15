<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckRedirects
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        $path = $request->path();
        $redirect = Redirect::where('from_path', $path)
            ->where('active', true)
            ->first();

        if (! $redirect) {
            return $next($request);
        }

        if ($this->isOpenRedirect($redirect->to_path)) {
            Log::warning('Open redirect attempt blocked in CheckRedirects', [
                'from' => $path,
                'to' => $redirect->to_path,
            ]);

            return $next($request);
        }

        return redirect($redirect->to_path, $redirect->type);
    }

    private function isOpenRedirect(string $target): bool
    {
        $normalized = strtolower(trim($target));

        return str_starts_with($normalized, 'http://')
            || str_starts_with($normalized, 'https://')
            || str_starts_with($normalized, '//');
    }
}
