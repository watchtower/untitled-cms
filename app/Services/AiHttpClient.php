<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin HTTP client for hub-configured AI provider APIs.
 *
 * Not SSRF-protected: base URLs are trusted vendor endpoints, not user input.
 * For user- or AI-supplied URLs, use {@see SafeHttpClient} instead.
 */
class AiHttpClient
{
    public static function timeout(int $seconds = 60): PendingRequest
    {
        return Http::timeout($seconds);
    }

    /**
     * @param  array<string, string>  $headers
     */
    public static function withToken(string $token, array $headers = [], int $timeout = 60): PendingRequest
    {
        return self::timeout($timeout)
            ->withToken($token)
            ->withHeaders($headers);
    }

    /**
     * @param  array<string, string>  $headers
     */
    public static function withHeaders(array $headers, int $timeout = 60): PendingRequest
    {
        return self::timeout($timeout)->withHeaders($headers);
    }

    /**
     * Log provider response metadata without secrets.
     */
    public static function logResponse(string $provider, string $operation, Response $response): void
    {
        if ($response->failed()) {
            Log::warning('AI provider request failed', [
                'provider' => $provider,
                'operation' => $operation,
                'status' => $response->status(),
            ]);
        }
    }
}
