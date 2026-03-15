<?php

namespace App\Vault\Pipes;

use Closure;
use App\Vault\DTOs\VaultPipelinePayload;
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
                    // Resolve config once — called per upload inside a queue worker
                    $webpConversion = config('vault.webp_conversion');
                    $webpQuality    = config('vault.webp_quality', 82); // 0–100, GD clamps out-of-range values
                    $gdSupportsWebP = (bool) (imagetypes() & IMG_WEBP);

                    $wantsWebP     = $webpConversion && $mime !== 'image/webp' && $mime !== 'image/gif'; // preserve animated GIFs
                    $convertToWebP = $wantsWebP && $gdSupportsWebP;

                    if ($wantsWebP && !$gdSupportsWebP) {
                        \Log::warning('WebP conversion requested (vault.webp_conversion=true) but GD lacks IMG_WEBP support; falling back to original format.');
                    }

                    if ($convertToWebP) {
                        $webpPath = $sourcePath . '.webp';

                        try {
                            $success = imagewebp($image, $webpPath, $webpQuality);

                            if (!$success) {
                                throw new \RuntimeException('imagewebp() returned false — disk full or permission error.');
                            }

                            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.webp';
                            $payload->file = new \Illuminate\Http\UploadedFile(
                                $webpPath,
                                $originalName,
                                'image/webp',
                                null,
                                true
                            );

                            // Clean up the original temp file — the pipeline now owns the .webp file
                            @unlink($sourcePath);
                        } catch (\Exception $e) {
                            // Ensure the partially-written .webp temp file does not linger
                            @unlink($webpPath);
                            throw $e;
                        } finally {
                            imagedestroy($image);
                        }
                    } else {
                        // Re-save in original format — strips EXIF and trailing data
                        match ($mime) {
                            'image/jpeg' => imagejpeg($image, $sourcePath, 90),
                            'image/png'  => imagepng($image, $sourcePath, 9),
                            'image/gif'  => imagegif($image, $sourcePath),
                            'image/webp' => imagewebp($image, $sourcePath, 90),
                        };
                        imagedestroy($image);
                    }
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
