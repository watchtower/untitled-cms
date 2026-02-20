<?php

namespace App\Vault\Pipes;

use Closure;
use Illuminate\Support\Facades\Log;

class SandboxedScan
{
    public function handle($payload, Closure $next)
    {
        if (! config('vault.clamav_enabled')) {
            return $next($payload);
        }

        $file = $payload['file'];
        $path = $file->getRealPath();

        // Stub for ClamAV scanning
        // In a real implementation, we'd use a package like sunspikes/clamav-validator
        // $scanner = app(ClamavValidator::class);
        // if ($scanner->isInfected($path)) {
        //     throw ValidationException::withMessages(['file' => 'Malware detected']);
        // }

        Log::info("ClamAV scan skipped (stub) for file: {$path}");

        return $next($payload);
    }
}
