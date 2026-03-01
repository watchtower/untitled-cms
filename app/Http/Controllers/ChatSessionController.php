<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatSessionController extends Controller
{
    /**
     * List the last 20 sessions for the authenticated user.
     */
    public function index(Request $request)
    {
        $sessions = ChatSession::forUser($request->user()->id)
            ->limit(20)
            ->get(['id', 'title', 'last_active_at', 'created_at'])
            ->map(fn($s) => [
                'id' => (string) $s->id,
                'title' => $s->title ?: 'New Conversation',
                'last_active_at' => $s->last_active_at?->toISOString(),
            ]);

        return response()->json($sessions);
    }

    /**
     * Create a new empty session.
     */
    public function store(Request $request)
    {
        $session = ChatSession::create([
            'user_id' => $request->user()->id,
            'title' => null,
            'messages' => [],
            'last_active_at' => now(),
        ]);

        return response()->json(['id' => (string) $session->id]);
    }

    /**
     * Load a specific session's full messages.
     */
    public function show(Request $request, string $id)
    {
        $session = ChatSession::forUser($request->user()->id)->findOrFail($id);

        return response()->json([
            'id' => (string) $session->id,
            'title' => $session->title ?: 'New Conversation',
            'messages' => $session->messages ?? [],
        ]);
    }

    /**
     * Append / replace the messages array after each exchange.
     * Also auto-generates a title from the first user message.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'messages' => 'required|array',
            'messages.*.role' => 'required|string|in:user,assistant,system',
            'messages.*.content' => 'required|string',
        ]);

        $session = ChatSession::forUser($request->user()->id)->findOrFail($id);

        $messages = $request->input('messages');

        // Auto-title from first user message (only if not already set)
        if (!$session->title) {
            $firstUser = collect($messages)->firstWhere('role', 'user');
            if ($firstUser) {
                $session->title = Str::limit($firstUser['content'], 60);
            }
        }

        $session->messages = $messages;
        $session->last_active_at = now();
        $session->save();

        return response()->json(['ok' => true, 'title' => $session->title]);
    }

    /**
     * Soft-delete a session.
     */
    public function destroy(Request $request, string $id)
    {
        $session = ChatSession::forUser($request->user()->id)->findOrFail($id);
        $session->delete();

        return response()->json(['ok' => true]);
    }
}
