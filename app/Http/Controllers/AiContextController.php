<?php

namespace App\Http\Controllers;

use App\Services\AiContextService;
use Illuminate\Http\Request;

class AiContextController extends Controller
{
    public function __construct(protected AiContextService $contextService)
    {
    }

    /**
     * Return live module context for the AI assistant.
     * Called by the frontend before each chat request when the module changes.
     */
    public function show(Request $request)
    {
        $url = $request->input('url', '/');
        $module = $this->contextService->detectModule($url);
        $data = $this->contextService->getModuleContext($module);

        return response()->json([
            'module' => $module,
            'context' => $data,
            'summary' => $this->contextService->buildContextString($url),
        ]);
    }
}
