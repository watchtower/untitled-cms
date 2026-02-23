<?php

namespace App\Vault\Pipes;

use Closure;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image; // Assuming intervention/image might be available, or use GD directly

class SanitizeImage
{
    public function handle($payload, Closure $next)
    {
        if (!config('vault.image_washing')) {
            return $next($payload);
        }

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $payload['file'];
        $mime = $file->getMimeType();

        if (Str::startsWith($mime, 'image/')) {
            try {
                // Determine source type and load image
                $sourcePath = $file->getRealPath();
                $image = null;

                if ($mime === 'image/jpeg') {
                    $image = @imagecreatefromjpeg($sourcePath);
                } elseif ($mime === 'image/png') {
                    $image = @imagecreatefrompng($sourcePath);
                } elseif ($mime === 'image/gif') {
                    $image = @imagecreatefromgif($sourcePath);
                } elseif ($mime === 'image/webp') {
                    $image = @imagecreatefromwebp($sourcePath);
                }

                if ($image) {
                    // Re-save to the same path, effectively determining it strips EXIF and trailing data
                    if ($mime === 'image/jpeg') {
                        imagejpeg($image, $sourcePath, 90);
                    } elseif ($mime === 'image/png') {
                        imagepng($image, $sourcePath, 9);
                    } elseif ($mime === 'image/gif') {
                        imagegif($image, $sourcePath);
                    } elseif ($mime === 'image/webp') {
                        imagewebp($image, $sourcePath, 90);
                    }

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
