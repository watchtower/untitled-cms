<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'content' => fake()->sentence(10),
            'link_url' => fake()->url(),
            'link_text' => 'Click Here',
            'is_active' => true,
            'background_color' => fake()->hexColor(),
            'text_color' => '#ffffff',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
        ];
    }
}
