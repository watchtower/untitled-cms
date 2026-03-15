<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'appName' => config('app.name', 'Laravel'),
            'auth' => [
                'user' => $request->user(),
                'permissions' => $request->user() ? $request->user()->getCachedPermissions() : [],
                'canAccessBackend' => $request->user()?->canAccessBackend() ?? false,
            ],
            'tinymce_api_key' => $request->user()?->canAccessBackend() ? config('services.tinymce.api_key') : null,
            'settings' => app(SettingsService::class)->getPublicSettings(),
            'aiChatEnabled' => (bool) Setting::get('ai.chat_enabled', true),
            'menus' => Menu::active()->get()->keyBy('slug'),
        ];
    }
}
