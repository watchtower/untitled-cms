<?php

namespace App\Services;

use Illuminate\Support\Str;
use Laravel\Ai\AnonymousAgent;

class AiService
{
    /**
     * Bootstraps the configuration so the `laravel/ai` SDK uses 
     * our active dynamic AI Hub and its API key.
     *
     * @return \App\Models\AiHub|null
     * @throws \Exception
     */
    private function configureActiveAi()
    {
        $activeHub = \App\Models\AiHub::where('is_active', true)->whereNotNull('api_key')->first();

        // Local development dev fallback mock
        if (config('app.env') === 'local' && !$activeHub) {
            return null;
        }

        if (!$activeHub) {
            throw new \Exception('No active AI Integration found with a configured API key.');
        }

        $modelName = strtolower($activeHub->name);
        $apiKey = $activeHub->api_key;

        $supportedProviders = array_keys(config('ai.providers'));
        if (!in_array($modelName, $supportedProviders)) {
            throw new \Exception("Unsupported AI Integration provider by laravel/ai: {$modelName}");
        }

        // Dynamically override the configuration
        config([
            "ai.default" => $modelName,
            "ai.providers.{$modelName}.key" => $apiKey,
        ]);

        return $activeHub;
    }

    public function generateSeoMeta(string $title, string $content): array
    {
        try {
            $activeHub = $this->configureActiveAi();

            // Mock mode for local dev if no API key is active
            if (!$activeHub) {
                return [
                    'seo_title' => Str::limit($title, 60),
                    'seo_description' => Str::limit(strip_tags($content), 160),
                ];
            }

            $agent = new AnonymousAgent(
                'You are an elite SEO expert and copywriter. Generate a highly engaging, click-optimized meta title (maximum 70 characters) and an enticing meta description (maximum 200 characters) based on the provided content. Your output MUST be strictly valid JSON only: {"seo_title": "...", "seo_description": "..."}. Do not include markdown formatting or any other text.',
                [],
                []
            );

            $response = $agent->prompt(
                "Title: {$title}\n\nContent: " . Str::limit(strip_tags($content), 2000),
                [],
                null,
                $activeHub->default_model
            );

            // Increment local tracking usage since an API request was successfully fulfilled
            $activeHub->increment('monthly_usage');

            // Strip out markdown formatting such as "```json" and "```" if present
            $jsonString = preg_replace('/```(?:json)?|```/', '', (string) $response);
            $json = json_decode(trim($jsonString), true);

            return [
                'seo_title' => $json['seo_title'] ?? Str::limit($title, 60),
                'seo_description' => $json['seo_description'] ?? Str::limit(strip_tags($content), 160),
            ];

        } catch (\Exception $e) {
            // Fallback
            return [
                'seo_title' => Str::limit($title, 60),
                'seo_description' => Str::limit(strip_tags($content), 160),
            ];
        }
    }

    /**
     * Generate alt text for an image provided as a base64 data URI.
     * Works with vision-capable models (Gemini, GPT-4o) without requiring a public URL.
     */
    public function generateAltTextFromBase64(string $dataUri, string $mimeType): string
    {
        $activeHub = \App\Models\AiHub::where('is_active', true)->whereNotNull('api_key')->first();

        if (!$activeHub) {
            throw new \Exception('No active AI Integration found with a configured API key.');
        }

        $provider = strtolower($activeHub->name);
        $model = $activeHub->default_model;
        $base64 = preg_replace('/^data:[^;]+;base64,/', '', $dataUri);

        if ($provider === 'gemini') {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$activeHub->api_key}";

            $response = \Illuminate\Support\Facades\Http::post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Describe this image concisely in 15 words or less for use as HTML alt text. Return only the description, no punctuation at the end.'],
                            ['inlineData' => ['mimeType' => $mimeType, 'data' => $base64]],
                        ],
                    ]
                ],
            ]);

            if ($response->failed()) {
                throw new \Exception('Gemini alt text generation failed: ' . $response->json('error.message', 'Unknown'));
            }

            $activeHub->increment('monthly_usage');
            return trim($response->json('candidates.0.content.parts.0.text') ?? 'Image description');
        }

        if ($provider === 'openai') {
            $response = \Illuminate\Support\Facades\Http::withToken($activeHub->api_key)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model ?: 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => 'Describe this image concisely in 15 words or less for alt text. Return only the description.'],
                                ['type' => 'image_url', 'image_url' => ['url' => $dataUri]],
                            ],
                        ]
                    ],
                    'max_tokens' => 60,
                ]);

            if ($response->failed()) {
                throw new \Exception('OpenAI vision failed: ' . $response->json('error.message', 'Unknown'));
            }

            $activeHub->increment('monthly_usage');
            return trim($response->json('choices.0.message.content') ?? 'Image description');
        }

        throw new \Exception("Your active AI Hub \"{$activeHub->name}\" does not support vision. Please use Gemini or OpenAI.");
    }

    public function generateText(string $prompt): string
    {
        $activeHub = $this->configureActiveAi();

        if (!$activeHub) {
            throw new \Exception('No active AI Integration found with a configured API key.');
        }

        try {
            $agent = new AnonymousAgent(
                'You are an expert content creator, marketer, and helpful writing assistant. Provide highly engaging, concise, and direct answers tailored to the user\'s prompt. Use a professional yet conversational tone.',
                [],
                []
            );
            $response = $agent->prompt($prompt, [], null, $activeHub->default_model);

            $activeHub->increment('monthly_usage');

            return (string) $response;
        } catch (\Exception $e) {
            throw new \Exception("{$activeHub->name} API request failed: " . $e->getMessage());
        }
    }

    /**
     * Generate an image from a text prompt.
     * Routes to the appropriate provider's image generation API based on the active AI Hub.
     *
     * Supported providers: openai (DALL-E 3), stability (Stable Diffusion SDXL), gemini (Imagen 3)
     */
    public function generateImage(string $prompt, string $size = '1024x1024'): string
    {
        $activeHub = \App\Models\AiHub::where('is_active', true)->whereNotNull('api_key')->first();

        if (!$activeHub) {
            throw new \Exception('No active AI Integration found with a configured API key.');
        }

        $provider = strtolower($activeHub->name);

        return match (true) {
            $provider === 'openai' => $this->generateImageOpenAi($activeHub, $prompt, $size),
            $provider === 'stability' => $this->generateImageStability($activeHub, $prompt),
            $provider === 'gemini' => $this->generateImageGemini($activeHub, $prompt),
            default => throw new \Exception(
                "Your active AI Hub \"{$activeHub->name}\" does not support image generation. " .
                "Please activate an OpenAI, Gemini, or Stability AI hub to use this feature."
            ),
        };
    }

    private function generateImageOpenAi(\App\Models\AiHub $hub, string $prompt, string $size): string
    {
        $model = $hub->image_model ?: 'dall-e-3';

        $response = \Illuminate\Support\Facades\Http::withToken($hub->api_key)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => $model,
                'prompt' => $prompt,
                'n' => 1,
                'size' => $size,
            ]);

        if ($response->failed()) {
            throw new \Exception("OpenAI image generation ({$model}) failed: " . $response->json('error.message', 'Unknown error'));
        }

        $hub->increment('monthly_usage');

        return $response->json('data.0.url');
    }

    private function generateImageStability(\App\Models\AiHub $hub, string $prompt): string
    {
        $response = \Illuminate\Support\Facades\Http::withToken($hub->api_key)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('https://api.stability.ai/v1/generation/stable-diffusion-xl-1024-v1-0/text-to-image', [
                'text_prompts' => [['text' => $prompt, 'weight' => 1]],
                'cfg_scale' => 7,
                'height' => 1024,
                'width' => 1024,
                'steps' => 30,
                'samples' => 1,
            ]);

        if ($response->failed()) {
            throw new \Exception('Stability AI image generation failed: ' . $response->json('message', 'Unknown error'));
        }

        $hub->increment('monthly_usage');

        // Stability returns base64 — convert to data URI
        $base64 = $response->json('artifacts.0.base64');
        return 'data:image/png;base64,' . $base64;
    }

    private function generateImageGemini(\App\Models\AiHub $hub, string $prompt): string
    {
        // Default: gemini-2.5-flash-image ("Nano Banana" - official Google Imagen model)
        $model = $hub->image_model ?: 'gemini-2.5-flash-image';

        // gemini-* models use the generateContent API
        // imagen-* models use the predict API
        if (str_starts_with($model, 'gemini')) {
            return $this->generateImageGeminiContent($hub, $model, $prompt);
        }

        return $this->generateImageGeminiImagen($hub, $model, $prompt);
    }

    /**
     * Gemini native image generation via generateContent API.
     * Supports: gemini-2.5-flash-image, gemini-2.0-flash-exp-image-generation
     * Docs: https://ai.google.dev/gemini-api/docs/image-generation
     */
    private function generateImageGeminiContent(\App\Models\AiHub $hub, string $model, string $prompt): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$hub->api_key}";

        $response = \Illuminate\Support\Facades\Http::post($url, [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            // Note: responseModalities is NOT needed in the body for gemini-2.5-flash-image.
            // The model handles image output natively.
        ]);

        if ($response->failed()) {
            $errorMsg = $response->json('error.message', 'Unknown error');
            throw new \Exception("Gemini image generation ({$model}) failed: " . $errorMsg);
        }

        $hub->increment('monthly_usage');

        // Extract the first image part from the response
        $parts = $response->json('candidates.0.content.parts', []);
        foreach ($parts as $part) {
            if (isset($part['inlineData']['data'])) {
                $mimeType = $part['inlineData']['mimeType'] ?? 'image/png';
                return "data:{$mimeType};base64," . $part['inlineData']['data'];
            }
        }

        throw new \Exception("Gemini ({$model}) returned no image in the response.");
    }

    /**
     * Imagen model generation via the predict API endpoint.
     * Supports: imagen-3.0-generate-002, imagen-3.0-fast-generate-001
     */
    private function generateImageGeminiImagen(\App\Models\AiHub $hub, string $model, string $prompt): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:predict?key={$hub->api_key}";

        $response = \Illuminate\Support\Facades\Http::post($url, [
            'instances' => [['prompt' => $prompt]],
            'parameters' => [
                'sampleCount' => 1,
                'aspectRatio' => '1:1',
                'safetyFilterLevel' => 'block_some',
            ],
        ]);

        if ($response->failed()) {
            $errorMsg = $response->json('error.message', 'Unknown error');
            throw new \Exception("Gemini Imagen ({$model}) failed: " . $errorMsg);
        }

        $hub->increment('monthly_usage');

        $base64 = $response->json('predictions.0.bytesBase64Encoded');
        if (!$base64) {
            throw new \Exception("Gemini Imagen ({$model}) returned an empty image.");
        }

        return 'data:image/png;base64,' . $base64;
    }
}
