<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RenameVaultFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // File-level policy checked in controller after UUID resolution.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
}
