<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessEmailWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailWebhookController extends Controller
{
    /**
     * Handle the incoming email webhook.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Dispatch the generic processing job
        ProcessEmailWebhook::dispatch($payload);

        return response()->json(['status' => 'accepted'], 202);
    }
}
