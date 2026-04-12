<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Crypt;

class InjectUnsubscribeHeaders
{
    /**
     * Add RFC 8058 List-Unsubscribe headers before the email is sent.
     */
    public function handle(MessageSending $event): void
    {
        $recipients = $event->message->getTo();

        if (empty($recipients)) {
            return;
        }

        $recipient = $recipients[0]->getAddress();
        $expiry = now()->addDays(30)->timestamp;
        $token = Crypt::encryptString("{$recipient}|{$expiry}");
        $url = route('unsubscribe', ['token' => $token]);

        $headers = $event->message->getHeaders();
        $headers->addTextHeader('List-Unsubscribe', "<{$url}>");
        $headers->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        // Passed through to LogSentEmail (MessageSent) for DB persistence
        $headers->addTextHeader('X-Unsubscribe-Token', $token);
    }
}
