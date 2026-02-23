<?php

namespace App\Http\Controllers;

use App\Models\VaultFile;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AiController extends Controller
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function generateSeo(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
        ]);

        $meta = $this->aiService->generateSeoMeta(
            $request->input('title'),
            $request->input('content')
        );

        return response()->json($meta);
    }

    public function generateAltText(Request $request)
    {
        $request->validate([
            'vault_file_uuid' => 'required|string',
        ]);

        $file = VaultFile::where('uuid', $request->input('vault_file_uuid'))->firstOrFail();

        // Read the file binary directly from storage — avoids public URL requirement
        $diskName = $file->is_public ? 'public' : 'vault';
        $binary = Storage::disk($diskName)->get($file->storage_path);

        if (!$binary) {
            return response()->json(['error' => 'Could not read file from storage.'], 422);
        }

        $base64 = base64_encode($binary);
        $dataUri = 'data:' . $file->mime_type . ';base64,' . $base64;

        try {
            $altText = $this->aiService->generateAltTextFromBase64($dataUri, $file->mime_type);
            return response()->json(['alt_text' => $altText]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function generate(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
        ]);

        try {
            $generatedText = $this->aiService->generateText($request->input('prompt'));
            return response()->json(['generated_text' => $generatedText]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function generateImage(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
            'size' => 'nullable|in:1024x1024,1792x1024,1024x1792',
        ]);

        try {
            $imageUrl = $this->aiService->generateImage(
                $request->input('prompt'),
                $request->input('size', '1024x1024')
            );
            return response()->json(['image_url' => $imageUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
