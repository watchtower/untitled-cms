<?php

namespace App\Providers;

use App\Listeners\InjectUnsubscribeHeaders;
use App\Listeners\LogSentEmail;
use App\Listeners\StopSuppressedEmail;
use App\Models\EmailLog;
use App\Models\Setting;
use App\Policies\EmailLogPolicy;
use App\Policies\SettingPolicy;
use App\Services\EmailWebhooks\Contracts\WebhookProvider;
use App\Services\EmailWebhooks\MailgunWebhookProvider;
use App\Services\EmailWebhooks\ResendWebhookProvider;
use App\Services\EmailWebhooks\SendGridWebhookProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WebhookProvider::class, function () {
            return match (config('services.email_webhook.provider')) {
                'mailgun' => new MailgunWebhookProvider,
                'sendgrid' => new SendGridWebhookProvider,
                default => new ResendWebhookProvider,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(EmailLog::class, EmailLogPolicy::class);

        // Email Logging & Suppression
        // ORDER MATTERS: StopSuppressedEmail must be registered first.
        // Returning false from it halts event propagation, preventing
        // InjectUnsubscribeHeaders from running on blocked emails.
        Event::listen(MessageSending::class, StopSuppressedEmail::class);
        Event::listen(MessageSending::class, InjectUnsubscribeHeaders::class);
        Event::listen(MessageSent::class, LogSentEmail::class);
    }
}
