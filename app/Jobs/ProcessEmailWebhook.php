<?php

namespace App\Jobs;

use App\Models\EmailLog;
use App\Models\SuppressedEmail;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\EmailWebhooks\Contracts\WebhookProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessEmailWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $payload) {}

    /**
     * Execute the job.
     */
    public function handle(WebhookProvider $provider): void
    {
        // SendGrid sends batched arrays; all other providers (Resend, Mailgun) use
        // associative payloads with string keys, so numeric index 0 is never present.
        // This heuristic is safe: a numeric-keyed array can only come from a SendGrid batch.
        $payloads = isset($this->payload[0]) && is_array($this->payload[0])
            ? $this->payload
            : [$this->payload];

        foreach ($payloads as $raw) {
            $this->processEvent($provider->normalizeEvent($raw));
        }
    }

    private function processEvent(?array $event): void
    {
        if (! $event) {
            return;
        }

        $status = $event['status'];
        $messageId = $event['message_id'];
        $recipient = $event['recipient'] ?? null;
        $reason = $event['reason'] ?? null;

        $updateData = [
            'status' => $status,
            "{$status}_at" => now(),
        ];

        if ($recipient) {
            $updateData['recipient'] = $recipient;
        }

        $log = EmailLog::updateOrCreate(
            ['provider_message_id' => $messageId],
            $updateData
        );

        if ($status === 'bounced' || $status === 'complained') {
            $email = strtolower($recipient ?? $log->recipient ?? '');

            if ($email) {
                SuppressedEmail::updateOrCreate(
                    ['email' => $email],
                    [
                        'reason' => $reason ?? ($status === 'bounced' ? 'bounced_hard' : 'complained'),
                        'metadata' => $event['data'] ?? [],
                    ]
                );

                if ($status === 'bounced' && $reason !== 'bounced_soft') {
                    $user = User::where('email', $email)->first();
                    if ($user) {
                        $user->update(['bounce_hard' => true]);
                    }
                }

                if ($status === 'complained') {
                    ActivityLogger::log(
                        'system',
                        "Spam complaint received for {$email}",
                        null,
                        ['message_id' => $messageId, 'provider' => config('services.email_webhook.provider')]
                    );
                }
            }
        }
    }
}
