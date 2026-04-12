<?php

namespace App\Listeners;

use App\Models\SuppressedEmail;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;

class StopSuppressedEmail
{
    /**
     * Handle the event.
     * Returning false will stop the email from being sent.
     */
    public function handle(MessageSending $event): bool
    {
        $message = $event->message;
        $recipients = $message->getTo();

        if (empty($recipients)) {
            Log::warning('Email send attempted without any recipients (MessageSending). Skipping suppression check.');

            return true;
        }

        $recipientEmail = $recipients[0]->getAddress();

        if (SuppressedEmail::isSuppressed($recipientEmail)) {
            Log::info("Blocking email send to suppressed recipient: {$recipientEmail}");

            return false;
        }

        return true;
    }
}
