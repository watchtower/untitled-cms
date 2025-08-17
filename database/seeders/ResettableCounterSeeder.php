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
                'name' => 'Daily Bits',
                'slug' => 'daily-bits',
                'description' => 'Daily resettable currency for premium features',
                'default_allocation' => 50,
                'reset_frequency' => 'daily',
                'icon' => '⚡',
                'color' => '#3B82F6',
                'is_active' => true,
            ],
            [
                'name' => 'Weekly Power Bits',
                'slug' => 'weekly-power-bits',
                'description' => 'Weekly resettable currency for advanced features',
                'default_allocation' => 200,
                'reset_frequency' => 'weekly',
                'icon' => '💎',
                'color' => '#8B5CF6',
                'is_active' => true,
            ],
            [
                'name' => 'API Call Credits',
                'slug' => 'api-call-credits',
                'description' => 'Daily API call allowance',
                'default_allocation' => 100,
                'reset_frequency' => 'daily',
                'icon' => '🔌',
                'color' => '#10B981',
                'is_active' => true,
            ],
            [
                'name' => 'Storage Upload Quota',
                'slug' => 'storage-upload-quota',
                'description' => 'Weekly file upload quota in MB',
                'default_allocation' => 500,
                'reset_frequency' => 'weekly',
                'icon' => '💾',
                'color' => '#F59E0B',
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