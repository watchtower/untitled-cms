<?php

namespace App\Http\Middleware;

use App\Services\EmailWebhooks\Contracts\WebhookProvider;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyEmailWebhook
{
    public function __construct(protected WebhookProvider $provider) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->provider->verifySignature($request)) {
            return response()->json(['error' => 'Invalid webhook signature'], 400);
        }

        return $next($request);
    }
}
