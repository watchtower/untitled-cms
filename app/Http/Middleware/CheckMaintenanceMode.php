<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Auth;

class CheckMaintenanceMode
{
    /**
     * Routes that are always accessible regardless of maintenance mode.
     * Keep this list as short as possible to avoid bypassing maintenance for too many routes.
     */
    private const ALLOWED_ROUTES = [
        'login',
        'logout',
        'password.request',
        'password.email',
        'password.reset',
        'password.store',
    ];

    /**
     * Roles that can bypass maintenance mode.
     * Only explicitly trusted admin roles — not arbitrary roles.
     */
    private const BYPASS_ROLES = [
        'super-admin',
        'admin',
    ];

    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Safely read the setting. Use filter_var to strictly cast booleans
        // since MongoDB may store these as strings ("1", "true") or actual booleans.
        $rawValue = $this->settingsService->get('maintenance_mode', false);
        $isMaintenanceMode = filter_var($rawValue, FILTER_VALIDATE_BOOLEAN);

        if (!$isMaintenanceMode) {
            return $next($request);
        }

        // ── Maintenance Mode is ON ──────────────────────────────────────────

        // 1. Allow named auth routes (login, logout, password reset).
        //    We check named routes to prevent bypass via URL manipulation.
        if ($request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        // 2. Allow authenticated users with an explicit admin role.
        //    We do NOT allow "any logged-in user" to prevent privilege escalation.
        //    Only the roles in BYPASS_ROLES list are whitelisted.
        if (Auth::check()) {
            $user = Auth::user();

            foreach (self::BYPASS_ROLES as $role) {
                if ($user->hasRole($role)) {
                    return $next($request);
                }
            }

            // Fallback: check for explicit manage-settings permission
            // (e.g. power users who aren't full admins but can manage the CMS)
            if ($user->hasPermission('manage-settings')) {
                return $next($request);
            }
        }

        // 3. Block everything else with a 503 Service Unavailable.
        //    We do NOT redirect to avoid leaking route information.
        abort(503, 'Service Unavailable');
    }
}
