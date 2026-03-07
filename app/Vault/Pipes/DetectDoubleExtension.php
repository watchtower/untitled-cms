<?php

namespace App\Vault\Pipes;

use Closure;
use App\Vault\DTOs\VaultPipelinePayload;
use Illuminate\Validation\ValidationException;

class DetectDoubleExtension
{
    public function handle(VaultPipelinePayload $payload, Closure $next)
    {
        $file = $payload->file;
        $filename = $file->getClientOriginalName();

        // Allow multiple dots unless they precede a dangerous extension
        // Blocks: .php.jpg, .exe.png, .sh.txt
        // Allows: .tar.gz, .user.js, 2026.02.19.jpg

        $dangerousExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'bat', 'pl', 'cgi', 'py', 'rb', 'asp', 'aspx', 'jsp', 'jar'];

        $parts = explode('.', $filename);
        $count = count($parts);

        if ($count > 2) {
            // Check all intermediate extensions (everything except the last one)
            // If any part matches a dangerous extension, block it.
            // Example: shell.php.jpg -> parts[php] -> BLOCK

            // We iterate from 1 to count-2 (skipping first part (name) and last part (real extension))
            // Actually, a file named "my.php.image.jpg" should be blocked too.
            // So we check from index 1 to count-2.

            for ($i = 1; $i < $count - 1; $i++) {
                if (in_array(strtolower($parts[$i]), $dangerousExtensions)) {
                    throw ValidationException::withMessages([
                        'file' => "Potential malicious double extension detected in filename: {$filename}",
                    ]);
                }
            }
        }

        return $next($payload);
    }
}
