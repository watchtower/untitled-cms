<?php

namespace App\Services\EmailWebhooks;

use App\Services\EmailWebhooks\Contracts\WebhookProvider;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class MailgunWebhookProvider implements WebhookProvider
{
    /**
     * Verify Mailgun webhook signature.
     * signature: ['timestamp', 'token', 'signature']
     */
    public function verifySignature(Request $request): bool
    {
        $signingKey = config('services.email_webhook.mailgun.webhook_signing_key');

        if (! $signingKey) {
            Log::error('Mailgun webhook signing key not configured');

            return false;
        }

        $signature = $request->input('signature');

        if (! $signature || ! isset($signature['timestamp'], $signature['token'], $signature['signature'])) {
            return false;
        }

        // Verify timestamp is not too old (5 minutes)
        if (abs(time() - (int) $signature['timestamp']) > 300) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $signature['timestamp'].$signature['token'], $signingKey);

        return hash_equals($expectedSignature, $signature['signature']);
    }

    /**
     * Extract the RFC Message-ID from the sent message.
     * Mailgun webhooks reference this via event-data.message.headers.message-id.
     */
    public function resolveMessageId(MessageSent $event): ?string
    {
        return $event->message->getHeaders()->get('Message-ID')?->getBodyAsString();
    }

    /**
     * Normalise a Mailgun webhook payload.
     */
    public function normalizeEvent(array $payload): ?array
    {
        $eventData = $payload['event-data'] ?? null;

        if (! $eventData) {
            return null;
        }

        $type = $eventData['event'] ?? null;
        // Correlate via the RFC Message-ID header, same value stored at send time
        $messageId = $eventData['message']['headers']['message-id'] ?? null;

        if (! $type || ! $messageId) {
            return null;
        }

        $statusMap = [
            'delivered' => 'delivered',
            'failed' => 'bounced',
            'opened' => 'opened',
            'clicked' => 'clicked',
            'complained' => 'complained',
            'unsubscribed' => 'unsubscribed',
        ];

        $status = $statusMap[$type] ?? null;

        if (! $status) {
            return null;
        }

        $reason = match (true) {
            $status === 'bounced' && ($eventData['severity'] ?? '') === 'temporary' => 'bounced_soft',
            $status === 'bounced' => 'bounced_hard',
            $status === 'complained' => 'complained',
            default => null,
        };

        return [
            'status' => $status,
            'message_id' => $messageId,
            'recipient' => $eventData['recipient'] ?? $eventData['recipient-address'] ?? null,
            'reason' => $reason,
            'raw_type' => $type,
            'data' => $eventData,
        ];
    }
}
