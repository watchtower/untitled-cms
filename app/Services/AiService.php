<?php

namespace App\Services;

use App\Models\AiHub;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Files\Base64Image;

class AiService
{
    /**
     * Bootstraps the configuration so the `laravel/ai` SDK uses
     * our active dynamic AI Hub and its API key.
     *
     * @return AiHub|null
     *
     * @throws \Exception
     */
    private function configureActiveAi()
    {
        $activeHub = AiHub::where('is_active', true)->whereNotNull('api_key')->first();

        // Local development dev fallback mock
        if (config('app.env') === 'local' && ! $activeHub) {
            return null;
        }

        if (! $activeHub) {
            throw new \Exception('No active AI Integration found with a configured API key.');
        }

        $providerName = strtolower($activeHub->name);

        // The api_key cast is 'encrypted'. If APP_KEY was rotated after the key was stored,
        // decryption fails with DecryptException. Surface a clear error rather than a 500.
        try {
            $apiKey = $activeHub->api_key;
        } catch (DecryptException) {
            throw new \Exception(
                "The API key for \"{$activeHub->name}\" could not be decrypted. ".
                'This usually means APP_KEY was changed after the key was saved. '.
                'Please re-enter the API key in AI Integrations.'
            );
        }

        $supportedProviders = array_keys(config('ai.providers'));
        if (! in_array($providerName, $supportedProviders)) {
            throw new \Exception("Unsupported AI Integration provider by laravel/ai: {$providerName}");
        }

        // Dynamically override the configuration
        config([
            'ai.default' => $providerName,
            "ai.providers.{$providerName}.key" => $apiKey,
        ]);

        return $activeHub;
    }

    public function generateSeoMeta(string $title, string $content): array
    {
        try {
            $activeHub = $this->configureActiveAi();

            if (! $activeHub) {
                return $this->getDefaultSeoMeta($title, $content);
            }

            $agent = new AnonymousAgent(
                'You are an elite SEO expert and copywriter. Generate a highly engaging, click-optimized meta title (maximum 70 characters) and an enticing meta description (maximum 200 characters) based on the provided content. Your output MUST be strictly valid JSON only: {"seo_title": "...", "seo_description": "..."}. Do not include markdown formatting or any other text.',
                [],
                []
            );

            $response = $agent->prompt(
                "Title: {$title}\n\nContent: ".Str::limit(strip_tags($content), 2000),
                [],
                null,
                $activeHub->default_model
            );

            $activeHub->increment('monthly_usage');

            $json = $this->extractJsonFromResponse((string) $response);

            return [
                'seo_title' => $json['seo_title'] ?? Str::limit($title, 60),
                'seo_description' => $json['seo_description'] ?? Str::limit(strip_tags($content), 160),
            ];

        } catch (\Exception $e) {
            return $this->getDefaultSeoMeta($title, $content);
        }
    }

    private function getDefaultSeoMeta(string $title, string $content): array
    {
        return [
            'seo_title' => Str::limit($title, 60),
            'seo_description' => Str::limit(strip_tags($content), 160),
        ];
    }

    public function generateAltTextFromBase64(string $dataUri, string $mimeType): string
    {
        $activeHub = AiHub::where('is_active', true)->whereNotNull('api_key')->first();

        if (! $activeHub) {
            throw new \Exception('No active AI Integration found with a configured API key.');
        }

        $provider = strtolower($activeHub->name);

        return match ($provider) {
            'gemini' => $this->generateAltTextGemini($activeHub, $dataUri, $mimeType),
            'openai' => $this->generateAltTextOpenAi($activeHub, $dataUri),
            'openrouter' => $this->generateAltTextOpenRouter($activeHub, $dataUri),
            default => throw new \Exception("Your active AI Hub \"{$activeHub->name}\" does not support vision. Please use Gemini, OpenAI, or OpenRouter.")
        };
    }

    private function generateAltTextGemini(AiHub $hub, string $dataUri, string $mimeType): string
    {
        $base64 = preg_replace('/^data:[^;]+;base64,/', '', $dataUri);
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$hub->default_model}:generateContent?key={$hub->api_key}";

        $response = Http::post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Describe this image concisely in 15 words or less for use as HTML alt text. Return only the description, no punctuation at the end.'],
                        ['inlineData' => ['mimeType' => $mimeType, 'data' => $base64]],
                    ],
                ],
            ],
        ]);

        if ($response->failed()) {
            throw new \Exception('Gemini alt text generation failed: '.$response->json('error.message', 'Unknown'));
        }

        $hub->increment('monthly_usage');

        return trim($response->json('candidates.0.content.parts.0.text') ?? 'Image description');
    }

    private function generateAltTextOpenAi(AiHub $hub, string $dataUri): string
    {
        $model = $hub->default_model ?: 'gpt-4o';

        $response = Http::withToken($hub->api_key)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Describe this image concisely in 15 words or less for alt text. Return only the description.'],
                            ['type' => 'image_url', 'image_url' => ['url' => $dataUri]],
                        ],
                    ],
                ],
                'max_tokens' => 60,
            ]);

        if ($response->failed()) {
            throw new \Exception('OpenAI vision failed: '.$response->json('error.message', 'Unknown'));
        }

        $hub->increment('monthly_usage');

        return trim($response->json('choices.0.message.content') ?? 'Image description');
    }

    private function generateAltTextOpenRouter(AiHub $hub, string $dataUri): string
    {
        $model = $hub->default_model ?: 'openai/gpt-4o';

        $response = Http::withToken($hub->api_key)
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Describe this image concisely in 15 words or less for alt text. Return only the description.'],
                            ['type' => 'image_url', 'image_url' => ['url' => $dataUri]],
                        ],
                    ],
                ],
                'max_tokens' => 60,
            ]);

        if ($response->failed()) {
            throw new \Exception('OpenRouter vision failed: '.$response->json('error.message', 'Unknown'));
        }

        $hub->increment('monthly_usage');

        return trim($response->json('choices.0.message.content') ?? 'Image description');
    }

    public function generateText(string $prompt): string
    {
        $activeHub = $this->configureActiveAi();

        if (! $activeHub) {
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
            throw new \Exception("{$activeHub->name} API request failed: ".$e->getMessage());
        }
    }

    public function moderateImage(string $base64Image, string $mimeType): array
    {
        try {
            $activeHub = $this->configureActiveAi();

            if (! $activeHub) {
                return ['status' => 'pass', 'reason' => null];
            }

            $agent = new AnonymousAgent(
                'You are a content moderator. Analyze the image and determine if it violates safety policies (Hate, Violence, Harassment, Sexual content). Return a JSON object with: "status" (either "pass" or "fail") and "reason" (a short description if fail, otherwise null). Return ONLY the JSON.',
                [],
                []
            );

            // Strip the data URI prefix if it was passed by ModerationCheck
            // Although we can just change ModerationCheck to pass pure base64. Let's do that or strip it here.
            $pureBase64 = preg_replace('/^data:image\/[a-zA-Z]+;base64,/', '', $base64Image);

            $response = $agent->prompt(
                'Moderate this image.',
                [new Base64Image($pureBase64, $mimeType)],
                null,
                $activeHub->default_model
            );

            $activeHub->increment('monthly_usage');

            $json = $this->extractJsonFromResponse((string) $response);

            return [
                'status' => $json['status'] ?? 'pass',
                'reason' => $json['reason'] ?? null,
            ];

        } catch (\Exception $e) {
            return ['status' => 'pass', 'reason' => null];
        }
    }

    public function generateChatResponse(array $messages, ?string $pageUrl = null): string
    {
        $activeHub = $this->configureActiveAi();

        if (! $activeHub) {
            throw new \Exception('No active AI Integration found with a configured API key.');
        }

        try {
            $systemPrompt = $this->buildSystemPrompt($pageUrl);
            $formattedHistory = $this->formatConversationHistory($messages);

            $agent = new AnonymousAgent($systemPrompt, [], []);

            $response = $agent->prompt(
                "Conversation History:\n{$formattedHistory}\nAssistant:",
                [],
                null,
                $activeHub->default_model
            );

            $activeHub->increment('monthly_usage');

            return (string) $response;
        } catch (\Exception $e) {
            throw new \Exception('Chat failed: '.$e->getMessage());
        }
    }

    /**
     * Low-level prompt for internal use (e.g. AiActionController intent parsing).
     */
    public function rawPrompt(string $systemInstruction, string $userMessage): mixed
    {
        $activeHub = $this->configureActiveAi();

        if (! $activeHub) {
            throw new \Exception('No active AI Integration found.');
        }

        $agent = new AnonymousAgent($systemInstruction, [], []);
        $response = $agent->prompt($userMessage, [], null, $activeHub->default_model);
        $activeHub->increment('monthly_usage');

        return $response;
    }

    public function generateTags(string $title, string $content): array
    {
        try {
            $activeHub = $this->configureActiveAi();

            if (! $activeHub) {
                return [];
            }

            $agent = new AnonymousAgent(
                'You are a content taxonomist. Analyze the provided title and content, and return a JSON array of 3-5 lowercase, SEO-friendly content tags. Return ONLY a JSON array like ["tag1", "tag2"]. No other text or markdown formatting.',
                [],
                []
            );

            $response = $agent->prompt(
                "Title: {$title}\n\nContent: ".Str::limit(strip_tags($content), 2000),
                [],
                null,
                $activeHub->default_model
            );

            $activeHub->increment('monthly_usage');

            return $this->extractJsonFromResponse((string) $response);

        } catch (\Exception $e) {
            return [];
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
        $activeHub = AiHub::where('is_active', true)->whereNotNull('api_key')->first();

        if (! $activeHub) {
            throw new \Exception('No active AI Integration found with a configured API key.');
        }

        $provider = strtolower($activeHub->name);

        return match (true) {
            $provider === 'openai' => $this->generateImageOpenAi($activeHub, $prompt, $size),
            $provider === 'stability' => $this->generateImageStability($activeHub, $prompt),
            $provider === 'gemini' => $this->generateImageGemini($activeHub, $prompt),
            default => throw new \Exception(
                "Your active AI Hub \"{$activeHub->name}\" does not support image generation. ".
                'Please activate an OpenAI, Gemini, or Stability AI hub to use this feature.'
            ),
        };
    }

    private function generateImageOpenAi(AiHub $hub, string $prompt, string $size): string
    {
        $model = $hub->image_model ?: 'dall-e-3';

        $response = Http::withToken($hub->api_key)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => $model,
                'prompt' => $prompt,
                'n' => 1,
                'size' => $size,
            ]);

        if ($response->failed()) {
            throw new \Exception("OpenAI image generation ({$model}) failed: ".$response->json('error.message', 'Unknown error'));
        }

        $hub->increment('monthly_usage');

        return $response->json('data.0.url');
    }

    private function generateImageStability(AiHub $hub, string $prompt): string
    {
        $response = Http::withToken($hub->api_key)
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
            throw new \Exception('Stability AI image generation failed: '.$response->json('message', 'Unknown error'));
        }

        $hub->increment('monthly_usage');

        // Stability returns base64 — convert to data URI
        $base64 = $response->json('artifacts.0.base64');

        return 'data:image/png;base64,'.$base64;
    }

    private function generateImageGemini(AiHub $hub, string $prompt): string
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
    private function generateImageGeminiContent(AiHub $hub, string $model, string $prompt): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$hub->api_key}";

        $response = Http::post($url, [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            // Note: responseModalities is NOT needed in the body for gemini-2.5-flash-image.
            // The model handles image output natively.
        ]);

        if ($response->failed()) {
            $errorMsg = $response->json('error.message', 'Unknown error');
            throw new \Exception("Gemini image generation ({$model}) failed: ".$errorMsg);
        }

        $hub->increment('monthly_usage');

        // Extract the first image part from the response
        $parts = $response->json('candidates.0.content.parts', []);
        foreach ($parts as $part) {
            if (isset($part['inlineData']['data'])) {
                $mimeType = $part['inlineData']['mimeType'] ?? 'image/png';

                return "data:{$mimeType};base64,".$part['inlineData']['data'];
            }
        }

        throw new \Exception("Gemini ({$model}) returned no image in the response.");
    }

    /**
     * Imagen model generation via the predict API endpoint.
     * Supports: imagen-3.0-generate-002, imagen-3.0-fast-generate-001
     */
    private function generateImageGeminiImagen(AiHub $hub, string $model, string $prompt): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:predict?key={$hub->api_key}";

        $response = Http::post($url, [
            'instances' => [['prompt' => $prompt]],
            'parameters' => [
                'sampleCount' => 1,
                'aspectRatio' => '1:1',
                'safetyFilterLevel' => 'block_some',
            ],
        ]);

        if ($response->failed()) {
            $errorMsg = $response->json('error.message', 'Unknown error');
            throw new \Exception("Gemini Imagen ({$model}) failed: ".$errorMsg);
        }

        $hub->increment('monthly_usage');

        $base64 = $response->json('predictions.0.bytesBase64Encoded');
        if (! $base64) {
            throw new \Exception("Gemini Imagen ({$model}) returned an empty image.");
        }

        return 'data:image/png;base64,'.$base64;
    }

    private function buildSystemPrompt(?string $pageUrl): string
    {
        $contextService = app(AiContextService::class);
        $liveContext = $pageUrl ? $contextService->buildContextString($pageUrl) : '';
        $whitelist = implode(', ', app(AiActionService::class)->getWhitelist());

        return <<<PROMPT
You are an expert AI Assistant integrated into the Untitled CMS admin dashboard.
You help admin users with content management, SEO, writing tasks, and answering questions about their CMS data.

You CAN perform the following CMS actions when the admin clearly requests them:
{$whitelist}

For any supported action, respond with a JSON block in this format (inline, not in a code fence):
[ACTION]{"action":"action_key","params":{...}}[/ACTION]

CONTENT WRITING RULES (critical):
- When writing page or banner content, write the COMPLETE, FINAL, READY-TO-PUBLISH content in the "content" field.
- Use proper HTML formatting (<h2>, <p>, <ul>, <strong> etc.).
- NEVER write placeholder text like "insert review here", "provide text", or meta-instructions.
- NEVER ask the user to supply content — use your knowledge to write it based on the title and context.
- Write real, engaging copy. If asked for a review, write an actual review. If asked for a description, write one.

General rules:
- If no action is needed, answer conversationally. Keep answers concise and helpful.
- DO NOT invent record IDs. Always refer to records by title/name only.

{$liveContext}
PROMPT;
    }

    private function formatConversationHistory(array $messages): string
    {
        return collect($messages)
            ->filter(fn ($m) => $m['role'] !== 'system')
            ->map(fn ($m) => ucfirst($m['role']).': '.($m['content'] ?? ''))
            ->join("\n");
    }

    private function extractJsonFromResponse(string $response): array
    {
        $jsonString = preg_replace('/```(?:json)?|```/', '', $response);
        $decoded = json_decode(trim($jsonString), true);

        return is_array($decoded) ? $decoded : [];
    }
}
