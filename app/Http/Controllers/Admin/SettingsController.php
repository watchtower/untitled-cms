<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get()->groupBy('group');

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string',
        ]);

        foreach ($request->settings as $key => $value) {
            $setting = Setting::where('key', $key)->first();

            if ($setting) {
                // Handle different types
                $processedValue = match ($setting->type) {
                    'boolean' => $request->boolean("settings.{$key}"),
                    'integer' => (int) $value,
                    'json' => is_array($value) ? $value : json_decode($value, true),
                    default => $value,
                };

                $setting->update(['value' => $processedValue]);
            }
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }
}
