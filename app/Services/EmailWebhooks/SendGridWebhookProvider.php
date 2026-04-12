<?php

namespace App\Services\EmailWebhooks;

use App\Services\EmailWebhooks\Contracts\WebhookProvider;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class SendGridWebhookProvider implements WebhookProvider
{
    /**
     * Verify SendGrid webhook signature using EC public key.
     */
    public function verifySignature(Request $request): bool
    {
        $publicKey = config('services.email_webhook.sendgrid.webhook_public_key');

        if (! $publicKey) {
            Log::error('SendGrid webhook public key not configured');

            return false;
        }

        $signature = $request->header('X-Twilio-Email-Event-Webhook-Signature');
        $timestamp = $request->header('X-Twilio-Email-Event-Webhook-Timestamp');

        if (! $signature || ! $timestamp) {
            return false;
        }

        $payload = $request->getContent();
        $toSign = $timestamp.$payload;

        // Use openssl_verify with ECDSA (SHA256)
        // Public key must be in PEM format
        $pem = $publicKey;
        if (! str_contains($pem, '-----BEGIN PUBLIC KEY-----')) {
            $pem = "-----BEGIN PUBLIC KEY-----\n".wordwrap($pem, 64, "\n", true)."\n-----END PUBLIC KEY-----";
        }

        $publicKeyId = openssl_pkey_get_public($pem);
        if (! $publicKeyId) {
            Log::error('Invalid SendGrid public key format');

            return false;
        }

        $isValid = openssl_verify($toSign, base64_decode($signature), $publicKeyId, 'sha256');
        openssl_pkey_free($publicKeyId);

        return $isValid === 1;
    }

    /**
     * Extract the SendGrid message ID from the MessageSent event.
     */
    public function resolveMessageId(MessageSent $event): ?string
    {
        return $event->message->getHeaders()->get('X-Message-Id')?->getBodyAsString();
    }

    /**
     * Normalise a single SendGrid event.
     * SendGrid sends batched payloads; ProcessEmailWebhook fans them out before calling this.
     */
    public function normalizeEvent(array $payload): ?array
    {
        $type = $payload['event'] ?? null;

        // sg_message_id may carry a ".filter-*" suffix — strip it so the value
        // matches the X-Message-Id header recorded at send time.
        $rawId = $payload['sg_message_id'] ?? $payload['message_id'] ?? null;
        $messageId = $rawId ? explode('.filter-', $rawId)[0] : null;

        if (! $type || ! $messageId) {
            return null;
        }

        // SendGrid event vocabulary
        $statusMap = [
            'delivered' => 'delivered',
            'bounce' => 'bounced',
            'dropped' => 'failed',
            'open' => 'opened',
            'click' => 'clicked',
            'spamreport' => 'complained',
            'unsubscribe' => 'unsubscribed',
        ];

        $status = $statusMap[$type] ?? null;

        if (! $status) {
            return null;
        }

        $reason = match ($status) {
            'bounced' => 'bounced_hard',
            'complained' => 'complained',
            default => null,
        };

        return [
            'status' => $status,
            'message_id' => $messageId,
            'recipient' => $payload['email'] ?? null,
            'reason' => $reason,
            'raw_type' => $type,
            'data' => $payload,
        ];
    }
}
