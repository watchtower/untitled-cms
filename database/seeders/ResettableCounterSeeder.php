<?php

namespace Database\Seeders;

use App\Models\CounterType;
use Illuminate\Database\Seeder;

class ResettableCounterSeeder extends Seeder
{
    public function run(): void
    {
        $counters = [
            [
                'name' => 'Monthly Credits',
                'slug' => 'monthly-credits',
                'description' => 'Monthly resettable credits (Bits) for recurring actions.',
                'default_allocation' => 100, // Corresponds to the Starter plan
                'reset_frequency' => 'monthly',
                'icon' => '⚡',
                'color' => '#3B82F6',
                'is_active' => true,
            ],
        ];

        foreach ($counters as $counterData) {
            CounterType::updateOrCreate(
                ['slug' => $counterData['slug']],
                $counterData
            );
        }

        $this->command->info('Counter types seeded successfully!');
    }
}
