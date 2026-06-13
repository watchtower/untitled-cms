<?php

namespace Tests\Unit;

use App\Services\SafeHttpClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SafeHttpClientTest extends TestCase
{
    public function test_safe_http_client_blocks_local_ips()
    {
        $client = new SafeHttpClient;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Fetching from internal or reserved IP addresses is forbidden.');

        // 127.0.0.1 should be blocked
        $client->get('http://127.0.0.1');
    }

    public function test_safe_http_client_blocks_aws_metadata_ip()
    {
        $client = new SafeHttpClient;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Fetching from internal or reserved IP addresses is forbidden.');

        // 169.254.169.254 should be blocked
        $client->get('http://169.254.169.254/latest/meta-data/');
    }

    public function test_safe_http_client_allows_external_ips()
    {
        // Mock the HTTP facade
        Http::fake([
            '8.8.8.8/*' => Http::response('OK', 200),
        ]);

        $client = new SafeHttpClient;

        // This is tricky to test fully offline if DNS is required, but we can simulate passing DNS
        // For the sake of the test, assume SafeHttpClient resolves 8.8.8.8 to 8.8.8.8 which is public
        $response = $client->get('http://8.8.8.8/');

        $this->assertEquals(200, $response->status());
    }
}
