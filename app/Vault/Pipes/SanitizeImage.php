<?php

namespace App\Vault\Pipes;

use App\Vault\DTOs\VaultPipelinePayload;
use Closure;
use Illuminate\Support\Str;

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
                $sourcePath = $file->getRealPath();

                $image = match ($mime) {
                    'image/jpeg' => @imagecreatefromjpeg($sourcePath),
                    'image/png'  => @imagecreatefrompng($sourcePath),
                    'image/gif'  => @imagecreatefromgif($sourcePath),
                    'image/webp' => @imagecreatefromwebp($sourcePath),
                    default      => null,
                };

                if ($image) {
                    // Re-save in original format — strips EXIF and trailing data
                    match ($mime) {
                        'image/jpeg' => imagejpeg($image, $sourcePath, 90),
                        'image/png'  => imagepng($image, $sourcePath, 9),
                        'image/gif'  => imagegif($image, $sourcePath),
                        'image/webp' => imagewebp($image, $sourcePath, 90),
                    };
                    imagedestroy($image);
                } else {
                    \Log::warning("Image sanitization failed for {$file->getClientOriginalName()}: Invalid image data.");
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'file' => 'Security violation: Image sanitization failed. The file is corrupt or contains invalid data.',
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning("Image sanitization failed for {$file->getClientOriginalName()}: " . $e->getMessage());
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'file' => 'Security violation: Image sanitization failed. The file is corrupt or contains invalid data.',
                ]);
            }
        }

        return $next($payload);
    }
}
