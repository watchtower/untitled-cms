<?php

namespace Database\Factories;

use App\Models\AiHub;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiHub>
 */
class AiHubFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'OpenAI',
            'api_key' => 'sk-test-'.fake()->uuid(),
            'default_model' => 'gpt-4o',
            'image_model' => 'dall-e-3',
            'is_active' => true,
            'monthly_quota' => 1000,
            'monthly_usage' => 0,
        ];
    }
}
