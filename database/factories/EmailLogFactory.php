<?php

namespace Database\Factories;

use App\Models\EmailLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmailLog>
 */
class EmailLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id' => '<'.Str::random(24).'@example.com>',
            'to' => fake()->safeEmail(),
            'subject' => fake()->sentence(4),
            'status' => 'delivered',
            'opens' => fake()->numberBetween(0, 5),
            'clicks' => fake()->numberBetween(0, 2),
            'provider' => 'mailgun',
        ];
    }
}
