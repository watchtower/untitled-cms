<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveAiImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // We handle authorization inside the controller method 'authorizeGlobalMediaCreate'
        // or we can handle it here directly. Let's do it here.
        return auth()->check() && (auth()->user()->hasRole('Super Admin') || auth()->user()->hasPermission('media.create'));
    }

    public function rules(): array
    {
        return [
            'image' => 'required|string',
            'filename' => 'nullable|string|max:255',
            'folder_id' => 'nullable|string|exists:\App\Models\VaultFolder,_id',
        ];
    }

    public function getPreparedUploadedFile(): \Illuminate\Http\UploadedFile
    {
        $imageData = $this->input('image');
        $customName = $this->input('filename');

        if (str_starts_with($imageData, 'data:')) {
            return $this->prepareBase64ImageFile($imageData, $customName);
        }

        return $this->prepareRemoteImageFile($imageData, $customName);
    }

    private function prepareBase64ImageFile(string $dataUri, ?string $customName): \Illuminate\Http\UploadedFile
    {
        if (!preg_match('/^data:(image\/[a-zA-Z+]+);base64,(.+)$/', $dataUri, $matches)) {
            throw new \Exception('Invalid image data URI.');
        }

        $mimeType = $matches[1];
        $extension = explode('/', $mimeType)[1] ?? 'png';
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $binaryData = base64_decode($matches[2]);

        return $this->createTempUploadedFile($binaryData, $extension, $mimeType, $customName);
    }

    private function prepareRemoteImageFile(string $url, ?string $customName): \Illuminate\Http\UploadedFile
    {
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            throw new \Exception('Invalid image URL format.');
        }

        $response = \App\Services\SafeHttpClient::get($url, 10);
        if (!$response->successful()) {
            throw new \Exception('Failed to download remote image. Status: ' . $response->status());
        }

        return $this->createTempUploadedFile($response->body(), 'png', 'image/png', $customName);
    }

    private function createTempUploadedFile(string $binaryData, string $extension, string $mimeType, ?string $customName): \Illuminate\Http\UploadedFile
    {
        // tempnam() creates a zero-byte file at the base path; we need a .ext variant.
        // Delete the base file immediately to avoid an orphaned temp file.
        $basePath = tempnam(sys_get_temp_dir(), 'ai_vault_');
        @unlink($basePath);
        $tmpPath = $basePath . '.' . $extension;
        file_put_contents($tmpPath, $binaryData);

        $filename = $customName
            ? preg_replace('/[^a-zA-Z0-9\-_\.]/', '-', $customName) . '.' . $extension
            : 'ai-generated-' . now()->format('Ymd-His') . '.' . $extension;

        return new \Illuminate\Http\UploadedFile(
            $tmpPath,
            $filename,
            $mimeType,
            \UPLOAD_ERR_OK,
            true
        );
    }
}
