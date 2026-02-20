<?php

namespace App\Services;

use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;

class AiService
{
    public function generateSeoMeta(string $title, string $content): array
    {
        // Mock mode for local dev if API key is not set or explicitly disabled
        if (config('app.env') === 'local' && ! config('openai.api_key')) {
            return [
                'seo_title' => Str::limit($title, 60),
                'seo_description' => Str::limit(strip_tags($content), 160),
            ];
        }

        try {
            $result = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an SEO expert. Generate a meta title (max 60 chars) and meta description (max 160 chars) based on the provided content. Return JSON only: {"seo_title": "...", "seo_description": "..."}'],
                    ['role' => 'user', 'content' => "Title: {$title}\n\nContent: ".Str::limit(strip_tags($content), 2000)],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $json = json_decode($result->choices[0]->message->content, true);

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

    public function generateAltText(string $imageUrl): string
    {
        // Mock mode
        if (config('app.env') === 'local' && ! config('openai.api_key')) {
            return 'Image description for '.basename($imageUrl);
        }

        try {
            $result = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini', // Vision capable model
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Describe this image in 10 words or less for alt text.'],
                            ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]],
                        ],
                    ],
                ],
                'max_tokens' => 20,
            ]);

            return $result->choices[0]->message->content;
        } catch (\Exception $e) {
            return 'Image description';
        }
    }
}
