<?php

namespace App\Vault\Pipes;

use Closure;
use App\Vault\DTOs\VaultPipelinePayload;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image; // Assuming intervention/image might be available, or use GD directly

class SanitizeImage
{
    public function handle(VaultPipelinePayload $payload, Closure $next): mixed
    {
        if (!config('vault.image_washing')) {
            return $next($payload);
        }

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $payload->file;
        $mime = $file->getMimeType();

        if (Str::startsWith($mime, 'image/')) {
            try {
                // Determine source type and load image
                $sourcePath = $file->getRealPath();

                $image = match ($mime) {
                    'image/jpeg' => @imagecreatefromjpeg($sourcePath),
                    'image/png' => @imagecreatefrompng($sourcePath),
                    'image/gif' => @imagecreatefromgif($sourcePath),
                    'image/webp' => @imagecreatefromwebp($sourcePath),
                    default => null,
                };

                if ($image) {
                    // Re-save to the same path, effectively determining it strips EXIF and trailing data
                    match ($mime) {
                        'image/jpeg' => imagejpeg($image, $sourcePath, 90),
                        'image/png' => imagepng($image, $sourcePath, 9),
                        'image/gif' => imagegif($image, $sourcePath),
                        'image/webp' => imagewebp($image, $sourcePath, 90),
                    };

                    imagedestroy($image);
                } else {
                    \Log::warning("Image sanitization failed for {$file->getClientOriginalName()}: Invalid image data.");
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'file' => "Security violation: Image sanitization failed. The file is corrupt or contains invalid data."
                    ]);
                }
            } catch (\Exception $e) {
                // If sanitization throws an exception, reject the file completely. 
                \Log::warning("Image sanitization failed for {$file->getClientOriginalName()}: " . $e->getMessage());
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'file' => "Security violation: Image sanitization failed. The file is corrupt or contains invalid data."
                ]);
            }
        }

        return $next($payload);
    }
}
