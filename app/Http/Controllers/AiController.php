<?php

namespace App\Http\Controllers;

use App\Models\VaultFile;
use App\Services\AiService;
use App\Services\SafeHttpClient;
use App\Services\VaultService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\AnonymousAgent;

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

    public function generateSocialImage(Request $request, VaultService $vaultService)
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
        ]);

        $title = $request->input('title');
        $content = Str::limit(strip_tags($request->input('content')), 1000);

        try {
            $prompt = $this->buildSocialImagePrompt($title, $content);
            $imageUrl = $this->aiService->generateImage($prompt, '1024x1024');

            if (! $imageUrl) {
                throw new \Exception('Image generation failed.');
            }

            $tmpPath = $this->downloadImageToTempFile($imageUrl);
            try {
                $uploadedFile = $this->createUploadedFile($tmpPath, $title);
                $vaultFile = $vaultService->upload($uploadedFile);
            } finally {
                @unlink($tmpPath);
            }

            return response()->json([
                'url' => $vaultFile->url,
                'vault_file_uuid' => $vaultFile->uuid,
                'prompt' => $prompt,
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
        $dataUri = $this->getFileDataUri($file);

        if (! $dataUri) {
            return response()->json(['error' => 'Could not read file from storage.'], 422);
        }

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
            'messages.*.role' => 'required|string|in:user,assistant',
            'messages.*.content' => 'required|string|max:10000',
            'page_url' => 'nullable|string|max:500',
        ]);

        try {
            $response = $this->aiService->generateChatResponse(
                $request->input('messages'),
                $request->input('page_url')
            );

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

    private function buildSocialImagePrompt(string $title, string $content): string
    {
        $promptAgent = new AnonymousAgent(
            'You are a creative director. Analyze the content and write a detailed, high-quality prompt for an image generator (like DALL-E 3) to create a professional, modern blog banner/social preview image. The prompt should be descriptive but concise (under 400 chars). Return ONLY the prompt text.',
            [],
            []
        );

        return (string) $promptAgent->prompt("Title: {$title}\n\nContent: {$content}");
    }

    private function downloadImageToTempFile(string $url): string
    {
        $response = SafeHttpClient::get($url, 15);

        if (! $response->successful()) {
            throw new \Exception('Failed to download AI-generated image.');
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'ai_');
        file_put_contents($tmpPath, $response->body());

        return $tmpPath;
    }

    private function createUploadedFile(string $tmpPath, string $title): UploadedFile
    {
        return new UploadedFile(
            $tmpPath,
            Str::slug($title).'-social-banner.png',
            'image/png',
            null,
            true
        );
    }

    private function getFileDataUri(VaultFile $file): ?string
    {
        $diskName = $file->is_public ? 'public' : 'vault';
        $binary = Storage::disk($diskName)->get($file->storage_path);

        if (! $binary) {
            return null;
        }

        $base64 = base64_encode($binary);

        return 'data:'.$file->mime_type.';base64,'.$base64;
    }
}
