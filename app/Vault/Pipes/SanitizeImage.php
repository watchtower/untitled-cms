<?php

namespace App\Vault\Pipes;

use Closure;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image; // Assuming intervention/image might be available, or use GD directly

class SanitizeImage
{
    public function handle($payload, Closure $next)
    {
        if (! config('vault.image_washing')) {
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
                }
            } catch (\Exception $e) {
                // If sanitization fails, we might choose to reject the file or just log warning
                // For high security, we should reject.
                // throw ValidationException::withMessages(['file' => 'Image sanitization failed. File may be corrupt.']);
                \Log::warning("Image sanitization failed for {$file->getClientOriginalName()}: ".$e->getMessage());
            }
        }

        return $next($payload);
    }
}
