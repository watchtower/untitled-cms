<?php

namespace App\Http\Controllers;

use App\Models\AiHub;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AiHubController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', AiHub::class);

        $integrations = AiHub::all()->map(function ($hub) {
            $quota = $hub->monthly_quota ?: 1000;
            $usage = $hub->monthly_usage ?: 0;
            $percent = $quota > 0 ? min(round(($usage / $quota) * 100), 100) : 0;

            // Accessing api_key triggers the 'encrypted' cast.
            // If APP_KEY was rotated or the record was seeded with a different key,
            // DecryptException is thrown. Treat that as "no valid key stored".
            try {
                $hasKey = !empty($hub->api_key);
            } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                $hasKey = false;
            }

            return [
                'id' => $hub->id,
                'name' => $hub->name,
                'is_active' => $hub->is_active,
                'default_model' => $hub->default_model,
                'image_model' => $hub->image_model,
                // Never expose the decrypted key to the frontend.
                // The UI shows a masked placeholder when has_key is true.
                'has_key' => $hasKey,
                'usage_percent' => $percent,
                'usage_text' => "{$usage} / {$quota} reqs",
            ];
        });

        return Inertia::render('AiHub/Index', [
            'integrations' => $integrations,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AiHub $aiHub)
    {
        $this->authorize('update', $aiHub);

        $validated = $request->validate([
            'is_active' => 'boolean',
            'api_key' => 'nullable|string',
            'default_model' => 'nullable|string',
            'image_model' => 'nullable|string',
        ]);

        $aiHub->update($validated);

        \App\Services\ActivityLogger::log('updated', "Updated AI Integration: {$aiHub->name}", $aiHub);

        return redirect()->route('admin.ai-hubs.index')->with('success', 'AI Integration updated successfully.');
    }

    /**
     * Set the specified standard as active and deactivate others.
     */
    public function activate(AiHub $aiHub)
    {
        $this->authorize('update', $aiHub);

        // Toggle logic: If it's already active, deactivate it.
        if ($aiHub->is_active) {
            $aiHub->update(['is_active' => false]);
            \App\Services\ActivityLogger::log('updated', "Deactivated AI Integration: {$aiHub->name}", $aiHub);
            return back()->with('success', "{$aiHub->name} was deactivated.");
        }

        // Standardize ensuring exactly one activated
        AiHub::where('id', '!=', $aiHub->id)->update(['is_active' => false]);
        $aiHub->update(['is_active' => true]);

        \App\Services\ActivityLogger::log('updated', "Activated AI Integration: {$aiHub->name}", $aiHub);

        return back()->with('success', "{$aiHub->name} is now the active AI provider.");
    }

    /**
     * Resets the monthly usage for the specified AI Hub.
     */
    public function resetUsage(AiHub $aiHub)
    {
        $this->authorize('update', $aiHub);

        $aiHub->update(['monthly_usage' => 0]);

        \App\Services\ActivityLogger::log('updated', "Reset Monthly Usage for AI Integration: {$aiHub->name}", $aiHub);

        return back()->with('success', "Monthly usage for {$aiHub->name} has been reset to 0.");
    }
}
