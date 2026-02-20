<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    protected $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Display a listing of the settings.
     */
    public function index()
    {
        $settings = Setting::all()->groupBy('group');

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the specified setting.
     */
    public function update(Request $request, string $key)
    {
        $request->validate([
            'value' => 'nullable',
        ]);

        $this->settingsService->set($key, $request->input('value'));

        return back()->with('success', 'Setting updated successfully.');
    }
}
