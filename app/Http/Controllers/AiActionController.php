<?php

namespace App\Http\Controllers;

use App\Services\AiActionService;
use App\Services\AiService;
use Illuminate\Http\Request;

class AiActionController extends Controller
{
    public function __construct(
        protected AiActionService $actionService,
        protected AiService $aiService,
    ) {
    }

    /**
     * Resolve an action JSON already extracted from the AI [ACTION] block.
     * No second AI call — directly validates whitelist and resolves record IDs.
     */
    public function resolve(Request $request)
    {
        $request->validate([
            'action_json' => 'required|array',
            'action_json.action' => 'required|string',
        ]);

        try {
            $proposal = $this->actionService->resolveProposal($request->input('action_json'));
            return response()->json(['proposal' => $proposal]);
        } catch (\Exception $e) {
            return response()->json(['proposal' => null, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * Fallback: parse intent via AI when no [ACTION] block existed.
     */
    public function parse(Request $request)
    {
        $request->validate([
            'messages' => 'required|array',
            'url' => 'nullable|string',
        ]);

        try {
            $messages = $request->input('messages');

            $systemPrompt = <<<PROMPT
You are an action parser for a CMS admin assistant.
Analyze the FULL conversation history and extract the admin's LATEST intent as a JSON action proposal.

Supported actions and their params:
- create_page: { "title": "...", "content": "..." }
- update_page: { "title": "...", "content": "...", "new_title": "...", "seo_title": "...", "seo_description": "..." }
- update_page_status: { "title": "...", "status": "published|draft" }
- create_banner: { "title": "..." }
- update_banner: { "title": "...", "content": "...", "new_title": "..." }
- update_banner_status: { "title": "...", "status": "active|inactive" }

IMPORTANT RULES:
1. For "title" param: use the EXACT page/banner title. If user says "it" or "the page", extract title from earlier messages.
2. For "content" param: if user asks to add or write content, generate appropriate HTML content text.
3. Return ONLY raw JSON (no markdown). If no clear action, return: {"action": null}
PROMPT;

            $formatted = collect($messages)
                ->filter(fn($m) => $m['role'] !== 'system')
                ->map(fn($m) => ucfirst($m['role']) . ': ' . $m['content'])
                ->join("\n");

            $response = $this->aiService->rawPrompt($systemPrompt, $formatted);
            $json = json_decode(preg_replace('/```(?:json)?|```/', '', (string) $response), true);

            if (!$json || !($json['action'] ?? null)) {
                return response()->json(['proposal' => null]);
            }

            $proposal = $this->actionService->resolveProposal($json);
            return response()->json(['proposal' => $proposal]);

        } catch (\Exception $e) {
            return response()->json(['proposal' => null, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * Execute a confirmed action proposal.
     */
    public function execute(Request $request)
    {
        $request->validate([
            'proposal' => 'required|array',
            'proposal.action' => 'required|string',
        ]);

        try {
            $result = $this->actionService->execute($request->input('proposal'));
            return response()->json(['ok' => true, 'result' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Revert an AI action using the before_state snapshot (or undo a create via soft-delete).
     */
    public function revert(Request $request, string $logId)
    {
        try {
            $result = $this->actionService->revert($logId);
            return response()->json(['ok' => true, 'result' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
