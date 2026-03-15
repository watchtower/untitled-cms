<?php

namespace App\Vault\Pipes;

use App\Vault\DTOs\VaultPipelinePayload;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SanitizeImage
{
    public function handle(VaultPipelinePayload $payload, Closure $next): mixed
    {
        if (! config('vault.image_washing')) {
            return $next($payload);
        }

        /** @var UploadedFile $file */
        $file = $payload->file;
        $mime = $file->getMimeType();

        if (Str::startsWith($mime, 'image/')) {
            $sourcePath = $file->getRealPath();

            try {
                $image = match ($mime) {
                    'image/jpeg' => @imagecreatefromjpeg($sourcePath),
                    'image/png' => @imagecreatefrompng($sourcePath),
                    'image/gif' => @imagecreatefromgif($sourcePath),
                    'image/webp' => @imagecreatefromwebp($sourcePath),
                    default => null,
                };
            } catch (\Exception $e) {
                Log::warning("Image sanitization failed for {$file->getClientOriginalName()}: ".$e->getMessage());
                throw ValidationException::withMessages([
                    'file' => 'Security violation: Image sanitization failed. The file is corrupt or contains invalid data.',
                ]);
            }

            if (! $image) {
                Log::warning("Image sanitization failed for {$file->getClientOriginalName()}: Invalid image data.");
                throw ValidationException::withMessages([
                    'file' => 'Security violation: Image sanitization failed. The file is corrupt or contains invalid data.',
                ]);
            }

            // Re-save in original format — strips EXIF and trailing data
            match ($mime) {
                'image/jpeg' => imagejpeg($image, $sourcePath, 90),
                'image/png' => imagepng($image, $sourcePath, 9),
                'image/gif' => imagegif($image, $sourcePath),
                'image/webp' => imagewebp($image, $sourcePath, 90),
            };
            imagedestroy($image);
        }

        return $next($payload);
    }
}
