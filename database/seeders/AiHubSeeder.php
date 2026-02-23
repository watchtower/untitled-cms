<?php

namespace Database\Seeders;

use App\Models\AiHub;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AiHubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $providers = [
            [
                'name' => 'OpenAI',
                'default_model' => 'gpt-4o',
            ],
            [
                'name' => 'Anthropic',
                'default_model' => 'claude-3-7-sonnet-latest',
            ],
            [
                'name' => 'Gemini',
                'default_model' => 'gemini-2.5-pro',
            ],
            [
                'name' => 'Deepseek',
                'default_model' => 'deepseek-chat',
            ],
            [
                'name' => 'Groq',
                'default_model' => 'llama3-70b-8192',
            ],
            [
                'name' => 'Mistral',
                'default_model' => 'mistral-large-latest',
            ],
            [
                'name' => 'Ollama',
                'default_model' => 'llama3.1',
            ]
        ];

        foreach ($providers as $provider) {
            AiHub::firstOrCreate(
                ['name' => $provider['name']],
                $provider
            );
        }
    }
}
