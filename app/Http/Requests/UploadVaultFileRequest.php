<?php

namespace App\Http\Requests;

use App\Models\VaultFolder;
use Illuminate\Foundation\Http\FormRequest;

class UploadVaultFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('media.create');
    }

    public function rules(): array
    {
        return [
            'files' => 'required|array|max:20',
            'files.*' => 'file|max:'.config('vault.max_upload_kb', 51200),
            'folder_id' => 'nullable|string|exists:'.VaultFolder::class.',_id',
        ];
    }
}
