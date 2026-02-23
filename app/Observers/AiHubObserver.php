<?php

namespace App\Observers;

use App\Models\AiHub;

class AiHubObserver
{
    /**
     * Handle the AiHub "saving" event.
     */
    public function saving(AiHub $aiHub): void
    {
        if ($aiHub->is_active) {
            // Deactivate all other AI Hubs
            AiHub::where('id', '!=', $aiHub->id)->update(['is_active' => false]);
        }
    }
}
