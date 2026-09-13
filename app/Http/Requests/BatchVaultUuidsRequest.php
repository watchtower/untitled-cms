<?php

namespace App\Http\Requests;

use App\Models\VaultFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchVaultUuidsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Per-file Gate checks run in the controller after models are loaded.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // Cap bounds the per-item exists queries and per-file policy checks
            'uuids' => 'required|array|max:500',
            'uuids.*' => ['string', Rule::exists(VaultFile::class, 'uuid')],
        ];
    }
}
