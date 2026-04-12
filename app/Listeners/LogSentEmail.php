<?php

namespace App\Listeners;

use App\Models\EmailLog;
use App\Services\EmailWebhooks\Contracts\WebhookProvider;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class LogSentEmail
{
    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        try {
            $provider = app(WebhookProvider::class);
            $messageId = $provider->resolveMessageId($event);

            if (! $messageId) {
                return;
            }

            $message = $event->message;
            $to = $message->getTo();
            if (empty($to)) {
                return;
            }
            $recipient = $to[0]->getAddress();
            $headers = $message->getHeaders();
            $subject = $message->getSubject();
            $mailable = $event->data['mailable'] ?? null;

            // Retrieve the unsubscribe token that was stamped onto the message
            // by InjectUnsubscribeHeaders (fires on MessageSending, before send)
            $unsubscribeToken = $headers->get('X-Unsubscribe-Token')?->getBodyAsString();

            EmailLog::create([
                'provider_message_id' => $messageId,
                'recipient' => $recipient,
                'subject' => $subject,
                'mailable' => $mailable,
                'status' => 'sent',
                'metadata' => array_merge($event->data['metadata'] ?? [], array_filter([
                    'unsubscribe_token' => $unsubscribeToken,
                ])),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log outbound email: '.$e->getMessage());
        }
    }
}
