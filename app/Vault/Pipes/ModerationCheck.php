<?php

namespace App\Vault\Pipes;

use App\Services\AiService;
use App\Models\Setting;
use App\Vault\DTOs\VaultPipelinePayload;
use Closure;
use Illuminate\Support\Facades\Storage;

class ModerationCheck
{
    public function handle(VaultPipelinePayload $payload, Closure $next)
    {
        $file = $payload->file;

        $moderationEnabled = Setting::get('vault.moderation_enabled', false);

        if ($moderationEnabled && str_starts_with($file->getMimeType(), 'image/')) {
            $aiService = app(AiService::class);

            $binary = file_get_contents($file->getRealPath());
            $base64 = base64_encode($binary);
            $dataUri = 'data:' . $file->getMimeType() . ';base64,' . $base64;

            $result = $aiService->moderateImage($dataUri, $file->getMimeType());

            if ($result['status'] === 'fail') {
                $payload->validation_status = 'failed';
                $payload->moderation_reason = $result['reason'];
                // We don't throw exception here because we want the pipeline to continue 
                // but the file is marked as failed.
                // However, if we want to BLOCK the upload entirely:
                // throw new \Exception("Image failed moderation: " . $result['reason']);
            }
        }

        return $next($payload);
    }
}
