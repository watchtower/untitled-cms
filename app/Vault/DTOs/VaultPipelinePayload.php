<?php

namespace App\Vault\DTOs;

use App\Models\VaultFile;
use Illuminate\Http\UploadedFile;

class VaultPipelinePayload
{
    public UploadedFile $file;

    public ?string $folder_id;

    public ?string $uuid = null;

    public ?VaultFile $created_file = null;

    public ?string $moderation_reason = null;

    public ?string $validation_status = null;

    public function __construct(UploadedFile $file, ?string $folder_id = null)
    {
        $this->file = $file;
        $this->folder_id = $folder_id;
    }
}
