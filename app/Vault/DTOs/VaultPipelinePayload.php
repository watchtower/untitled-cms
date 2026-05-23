<?php

namespace App\Vault\DTOs;

use App\Models\VaultFile;
use App\Models\VaultFolder;
use Illuminate\Http\UploadedFile;

class VaultPipelinePayload
{
    public UploadedFile $file;

    public ?string $folder_id;

    public ?string $uuid = null;

    public ?VaultFile $created_file = null;

    public ?string $moderation_reason = null;

    public ?VaultFolder $folder = null;

    public ?string $validation_status = null;

    public bool $is_public = true;

    public function __construct(UploadedFile $file, ?string $folder_id = null, ?VaultFolder $folder = null)
    {
        $this->file = $file;
        $this->folder_id = $folder_id;
        $this->folder = $folder;
    }
}
