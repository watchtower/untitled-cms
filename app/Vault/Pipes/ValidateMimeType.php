<?php

namespace App\Vault\Pipes;

use Closure;
use Illuminate\Validation\ValidationException;

class ValidateMimeType
{
    public function handle($payload, Closure $next)
    {
        $file = $payload['file'];
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = config('vault.allowed_extensions', []);

        if (! in_array($extension, $allowedExtensions)) {
            throw ValidationException::withMessages([
                'file' => "File type .{$extension} is not allowed.",
            ]);
        }

        // Verify Magic Bytes via finfo
        // The Laravel UploadedFile `getMimeType()` uses fileinfo under the hood on the temp file
        $detectedMime = $file->getMimeType();

        // Check if detected MIME matches expected MIME for the extension
        // This is a simplified check. For a production system, we'd have a map of ext -> mime
        // For now, we rely on Laravel/Symfony's robust guessing, but we explicitly fail if it detects PHP/Executable

        $dangerousMimes = [
            'application/x-php',
            'text/x-php',
            'application/php',
            'text/php',
            'application/x-httpd-php',
            'application/x-httpd-php-source',
            'application/x-dosexec', // exe
        ];

        if (in_array($detectedMime, $dangerousMimes)) {
            throw ValidationException::withMessages([
                'file' => "Security violation: File detected as {$detectedMime}",
            ]);
        }

        return $next($payload);
    }
}
