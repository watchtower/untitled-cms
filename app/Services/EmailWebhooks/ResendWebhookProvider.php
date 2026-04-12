<?php

namespace App\Services\EmailWebhooks;

use App\Services\EmailWebhooks\Contracts\WebhookProvider;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class ResendWebhookProvider implements WebhookProvider
{
    /**
     * Verify the inbound webhook signature using Svix scheme.
     */
    public function verifySignature(Request $request): bool
    {
        $secret = config('services.email_webhook.resend.webhook_secret');

        if (! $secret) {
            Log::error('Resend webhook secret not configured');

            return false;
        }

        $id = $request->header('svix-id');
        $timestamp = $request->header('svix-timestamp');
        $signature = $request->header('svix-signature');

        if (! $id || ! $timestamp || ! $signature) {
            return false;
        }

        // 1. Verify timestamp is not too old (5 minutes)
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        // 2. Prepare signing string: msg_id . timestamp . payload
        $payload = $request->getContent();
        $toSign = "{$id}.{$timestamp}.{$payload}";

        // 3. Verify signature (Standard Webhooks v1)
        $secret = trim($secret);
        if (str_starts_with($secret, 'whsec_')) {
            $secret = substr($secret, 6);
        }

        $decodedSecret = base64_decode($secret, strict: true);
        if ($decodedSecret === false) {
            Log::error('Resend webhook secret is not valid base64');

            return false;
        }

        $expectedSignature = base64_encode(hash_hmac('sha256', $toSign, $decodedSecret, true));

        $signatures = explode(' ', $signature);
        foreach ($signatures as $sig) {
            if (str_starts_with($sig, 'v1,')) {
                $sigValue = substr($sig, 3);
                if (hash_equals($expectedSignature, $sigValue)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extract the provider's message ID from the MessageSent header.
     */
    public function resolveMessageId(MessageSent $event): ?string
    {
        return $event->message->getHeaders()->get('X-Resend-ID')?->getBodyAsString();
    }

    /**
     * Normalise a raw Resend webhook payload.
     *
     * @return array|null shape: ['status' => string, 'message_id' => string, 'recipient' => string|null, 'reason' => string|null, 'raw_type' => string, 'data' => array]
     */
    public function normalizeEvent(array $payload): ?array
    {
        $type = $payload['type'] ?? null;
        $data = $payload['data'] ?? [];
        $messageId = $data['email_id'] ?? null;

        if (! $type || ! $messageId) {
            return null;
        }

        $statusMap = [
            'email.sent' => 'sent',
            'email.delivered' => 'delivered',
            'email.opened' => 'opened',
            'email.clicked' => 'clicked',
            'email.bounced' => 'bounced',
            'email.complained' => 'complained',
        ];

        $status = $statusMap[$type] ?? null;

        if (! $status) {
            return null;
        }

        $reason = match ($type) {
            'email.bounced' => 'bounced_hard',
            'email.complained' => 'complained',
            default => null,
        };

        return [
            'status' => $status,
            'message_id' => $messageId,
            'recipient' => $data['to'][0] ?? null,
            'reason' => $reason,
            'raw_type' => $type,
            'data' => $data,
        ];
    }
}
