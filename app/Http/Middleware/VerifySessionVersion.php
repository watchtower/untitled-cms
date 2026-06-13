<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifySessionVersion
{
    /**
     * Invalidate the session if an admin has incremented the user's session_version
     * (i.e. triggered "logout all devices").
     *
     * The version stored in the PHP session is compared against the current DB value.
     * On first login the session key is seeded. On mismatch the user is logged out.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $dbVersion = (int) ($user->session_version ?? 0);
            $sessionVersion = (int) $request->session()->get('session_version', $dbVersion);

            if ($sessionVersion !== $dbVersion) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Session invalidated. Please log in again.'], 401);
                }

                return redirect()->route('login')->with('status', 'Your session was ended by an administrator. Please log in again.');
            }

            // Seed the version into the session on first load (after login, the
            // session won't have this key yet, so we set it here to the current value).
            if (! $request->session()->has('session_version')) {
                $request->session()->put('session_version', $dbVersion);
            }
        }

        return $next($request);
    }
}
