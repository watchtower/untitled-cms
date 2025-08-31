<?php

namespace Database\Seeders;

use App\Models\SubscriptionLevel;
use Illuminate\Database\Seeder;

class SubscriptionLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subscriptionLevels = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Entry-level, quick testing.',
                'level' => 1,
                'price' => 0.00,
                'features' => [
                    'bits' => 100,
                    'bytes' => 10,
                    'site_monitor_retention_days' => 10,
                    'lookup_history_days' => 10,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Balanced package, sustainable for small teams.',
                'level' => 2,
                'price' => 9.99,
                'features' => [
                    'bits' => 1000,
                    'bytes' => 30,
                    'site_monitor_retention_days' => 30,
                    'lookup_history_days' => 30,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Elite',
                'slug' => 'elite',
                'description' => 'Power users, monitoring at scale.',
                'level' => 3,
                'price' => 19.99,
                'features' => [
                    'bits' => 10000,
                    'bytes' => 90,
                    'site_monitor_retention_days' => 90,
                    'lookup_history_days' => 90,
                ],
                'is_active' => true,
            ],
        ];

        foreach ($subscriptionLevels as $level) {
            SubscriptionLevel::updateOrCreate(
                ['slug' => $level['slug']],
                $level
            );
        }
    }
}