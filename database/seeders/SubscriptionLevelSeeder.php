<?php

namespace Database\Seeders;

use App\Models\SubscriptionLevel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
                'name' => 'L33t Padawan',
                'slug' => 'padawan',
                'description' => 'Learning the ropes - Your journey into the L33t universe begins here!',
                'level' => 1,
                'price' => 0.00,
                'features' => [
                    'Basic L33t Bytes allocation',
                    'Limited daily Bits for IP lookups',
                    'Basic tools access',
                    'Community support'
                ],
                'is_active' => true,
            ],
            [
                'name' => 'L33t Jedi',
                'slug' => 'jedi',
                'description' => 'Skilled user - Harness the power of the L33t force with enhanced capabilities!',
                'level' => 2,
                'price' => 9.99,
                'features' => [
                    'Enhanced L33t Bytes allocation',
                    'Increased daily Bits allowance',
                    'Advanced tools access',
                    'Priority support',
                    'API access',
                    'Custom subdomain creation'
                ],
                'is_active' => true,
            ],
            [
                'name' => 'L33t Master',
                'slug' => 'master',
                'description' => 'Top-tier mastery - Unlimited power in the L33t ecosystem!',
                'level' => 3,
                'price' => 19.99,
                'features' => [
                    'Maximum L33t Bytes allocation',
                    'Unlimited daily Bits',
                    'All tools and features',
                    'Premium support',
                    'Advanced API access',
                    'Multiple subdomains',
                    'White-label options',
                    'Custom integrations'
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
