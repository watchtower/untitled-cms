<?php

namespace App\Http\Controllers;

use App\Models\VaultFile;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    public function generateTags(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
        ]);

        $tags = $this->aiService->generateTags(
            $request->input('title'),
            $request->input('content')
        );

        return response()->json($tags);
    }

    public function generateSocialImage(Request $request, \App\Services\VaultService $vaultService)
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
        ]);

        $title = $request->input('title');
        $content = Str::limit(strip_tags($request->input('content')), 1000);

        // 1. Generate a high-quality descriptive prompt for an image generator
        $promptAgent = new \Laravel\Ai\AnonymousAgent(
            'You are a creative director. Analyze the content and write a detailed, high-quality prompt for an image generator (like DALL-E 3) to create a professional, modern blog banner/social preview image. The prompt should be descriptive but concise (under 400 chars). Return ONLY the prompt text.',
            [],
            []
        );

        $prompt = $promptAgent->prompt("Title: {$title}\n\nContent: {$content}");

        // 2. Generate the actual image
        try {
            $imageUrl = $this->aiService->generateImage((string) $prompt, '1024x1024');

            if (!$imageUrl) {
                throw new \Exception("Image generation failed.");
            }

            // 3. Optional: Upload to Vault automatically so it's persisted
            $imageBinary = file_get_contents($imageUrl);
            $tmpPath = tempnam(sys_get_temp_dir(), 'ai_');
            file_put_contents($tmpPath, $imageBinary);

            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $tmpPath,
                Str::slug($title) . '-social-banner.png',
                'image/png',
                null,
                true
            );

            $vaultFile = $vaultService->upload($uploadedFile);
            unlink($tmpPath);

            return response()->json([
                'url' => $vaultFile->url,
                'vault_file_uuid' => $vaultFile->uuid,
                'prompt' => (string) $prompt
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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

    public function chat(Request $request)
    {
        $request->validate([
            'messages' => 'required|array',
            'messages.*.role' => 'required|string|in:user,assistant,system',
            'messages.*.content' => 'required|string',
        ]);

        try {
            $response = $this->aiService->generateChatResponse($request->input('messages'));
            return response()->json(['message' => $response]);
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
