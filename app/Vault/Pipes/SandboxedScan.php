<?php

namespace App\Vault\Pipes;

use App\Vault\DTOs\VaultPipelinePayload;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SandboxedScan
{
    public function handle(VaultPipelinePayload $payload, Closure $next)
    {
        if (! config('vault.clamav_enabled')) {
            return $next($payload);
        }

        // Fix #2: getRealPath() returns false if the temp file no longer exists.
        // Guard early so the log and scan receive a usable path.
        $path = $payload->file->getRealPath();
        if ($path === false) {
            Log::warning('ClamAV: temp file no longer exists on disk, scan skipped.');

            return $next($payload);
        }

        try {
            $threat = $this->scanViaClamd($path);

            if ($threat !== null) {
                $payload->validation_status = 'infected';
                $payload->moderation_reason = "ClamAV detected: {$threat}";
                Log::warning("ClamAV: infected file marked as infected and flagged. Threat: {$threat}. Path: {$path}");
            } else {
                Log::info("ClamAV: file clean. Path: {$path}");
            }
        } catch (\Throwable $e) {
            // If clamd is unreachable or timed out, log and continue — don't block the upload.
            // Ops team should be alerted separately if clamd goes down.
            Log::error("ClamAV scan failed (daemon unreachable?): {$e->getMessage()}. Path: {$path}");

            if (config('vault.clamav_fail_closed', false)) {
                throw ValidationException::withMessages([
                    'file' => 'ClamAV scanning failed (service offline or timed out).',
                ]);
            }
        }

        return $next($payload);
    }

    /**
     * Stream the file to clamd via TCP using the INSTREAM protocol.
     * Returns the threat name if infected, null if clean.
     *
     * @throws \RuntimeException if the daemon is unreachable, the file cannot be opened,
     *                           or the socket times out before a response is received.
     */
    private function scanViaClamd(string $filePath): ?string
    {
        $host = (string) config('vault.clamav_host', '127.0.0.1');
        $port = (int) config('vault.clamav_port', 3310);
        $timeout = (int) config('vault.clamav_timeout', 30);

        $socket = @fsockopen($host, $port, $errCode, $errStr, $timeout);

        if (! $socket) {
            throw new \RuntimeException("Cannot connect to ClamAV daemon at {$host}:{$port} — {$errStr} (code {$errCode})");
        }

        try {
            stream_set_timeout($socket, $timeout);

            // Initiate INSTREAM scan (null-terminated command)
            fwrite($socket, "zINSTREAM\0");

            $handle = fopen($filePath, 'rb');
            if (! $handle) {
                throw new \RuntimeException("Cannot open file for ClamAV scan: {$filePath}");
            }

            try {
                // Send file contents in 8 KB chunks, each prefixed with a 4-byte big-endian length
                while (! feof($handle)) {
                    $chunk = fread($handle, 8192);
                    if ($chunk === false || $chunk === '') {
                        break;
                    }
                    fwrite($socket, pack('N', strlen($chunk)).$chunk);
                }
            } finally {
                fclose($handle);
            }

            // Signal end of stream with a zero-length chunk
            fwrite($socket, pack('N', 0));

            $response = fgets($socket, 1024);

            // Fix #1: a timed-out read returns false/empty and must not be treated as "clean".
            // Throw so the outer catch logs the failure rather than silently passing the file.
            $meta = stream_get_meta_data($socket);
            if ($meta['timed_out'] || $response === false) {
                throw new \RuntimeException("ClamAV scan timed out waiting for response. Path: {$filePath}");
            }
        } finally {
            fclose($socket);
        }

        $response = trim((string) $response);

        // clamd responds: "stream: OK" or "stream: MALWARE_NAME FOUND"
        if (str_ends_with($response, 'FOUND')) {
            preg_match('/stream: (.+) FOUND$/', $response, $matches);

            return $matches[1] ?? 'Unknown threat';
        }

        return null;
    }
}
