<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * A dedicated service for making outbound HTTP requests safely.
 * Focuses on preventing Server-Side Request Forgery (SSRF) and DNS rebinding attacks.
 */
class SafeHttpClient
{
    /**
     * Perform an SSRF-safe GET request to fetch remote content.
     * 
     * Validates that the target hostname does NOT resolve to a private or 
     * reserved IP address (e.g., 127.0.0.1, 10.x.x.x, 192.168.x.x).
     * Pins the resolved IP address to prevent DNS rebinding attacks.
     * Disables following redirects to prevent SSRF via 301/302 to an internal IP.
     *
     * @param string $url The untrusted user-provided or AI-provided URL.
     * @param int $timeout Seconds to wait for the response.
     * @return Response
     * @throws Exception If the URL is invalid, resolves to a private IP, or the download fails.
     */
    public static function get(string $url, int $timeout = 15): Response
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? null;

        if (!$host) {
            throw new Exception('Invalid URL format.');
        }

        // 1. Resolve hostname
        $ip = gethostbyname($host);

        // If gethostbyname cannot resolve, it returns the original host string
        if ($ip === $host || !filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new Exception('Could not resolve host.');
        }

        // 2. Validate it is NOT a private/reserved IP
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            Log::warning('SSRF attempt blocked in SafeHttpClient', [
                'url' => $url,
                'resolved_ip' => $ip,
                'user_id' => auth()->id() ?? 'guest',
            ]);
            throw new Exception('Fetching from internal or reserved IP addresses is forbidden.');
        }

        // 3. Pin the resolved IP via CURLOPT_RESOLVE to prevent DNS rebinding
        $scheme = $parsedUrl['scheme'] ?? 'https';
        $port = $parsedUrl['port'] ?? ($scheme === 'https' ? 443 : 80);
        $pinned = "{$host}:{$port}:{$ip}";

        // 4. Perform the request securely
        // ->withoutRedirecting() prevents SSRF via a malicious server responding 
        // with a 302 redirect pointing to an internal IP (e.g., http://169.254.169.254)
        $response = Http::withOptions([
            'curl' => [CURLOPT_RESOLVE => [$pinned]],
        ])
            ->withoutRedirecting()
            ->timeout($timeout)
            ->get($url);

        return $response;
    }
}
