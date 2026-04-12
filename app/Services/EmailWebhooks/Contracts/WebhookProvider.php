<?php

namespace App\Services\EmailWebhooks\Contracts;

use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;

interface WebhookProvider
{
    /**
     * Verify the inbound webhook signature.
     * Returns false → middleware rejects with 400.
     */
    public function verifySignature(Request $request): bool;

    /**
     * Extract the provider's own message ID from a MessageSent event.
     * Used by LogSentEmail to record the ID that webhooks will reference.
     * Returns null → no log record is created.
     */
    public function resolveMessageId(MessageSent $event): ?string;

    /**
     * Normalise a raw webhook payload to a common shape.
     * Returns null → payload is ignored (unknown event type).
     *
     * @return array|null shape: ['status' => string, 'message_id' => string, 'recipient' => string|null, 'reason' => string|null, 'raw_type' => string, 'data' => array]
     */
    public function normalizeEvent(array $payload): ?array;
}
