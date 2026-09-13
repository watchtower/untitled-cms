<?php

namespace Tests\Unit;

use App\Services\AiHttpClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiHttpClientTest extends TestCase
{
    public function test_with_token_sends_authorization_header(): void
    {
        Http::fake([
            'https://api.example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $response = AiHttpClient::withToken('secret-key')
            ->post('https://api.example.com/v1/test', ['prompt' => 'hi']);

        $this->assertTrue($response->successful());

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer secret-key')
                && $request->url() === 'https://api.example.com/v1/test';
        });
    }

    public function test_with_headers_passes_custom_headers(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response(['ok' => true], 200),
        ]);

        AiHttpClient::withHeaders(['x-goog-api-key' => 'gem-key'])
            ->post('https://generativelanguage.googleapis.com/v1beta/models/x:generateContent', []);

        Http::assertSent(function ($request) {
            return $request->hasHeader('x-goog-api-key', 'gem-key');
        });
    }
}
