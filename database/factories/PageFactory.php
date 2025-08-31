<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'slug' => $this->faker->unique()->slug,
            'summary' => $this->faker->paragraph,
            'content' => $this->faker->paragraphs(3, true),
            'status' => 'published',
            'meta_title' => $this->faker->sentence,
            'meta_description' => $this->faker->paragraph,
            'published_at' => now(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
