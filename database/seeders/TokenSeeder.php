<?php

namespace Database\Seeders;

use App\Models\Token;
use Illuminate\Database\Seeder;

class TokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tokens = [
            [
                'name' => 'L33t Bytes',
                'slug' => 'l33t-bytes',
                'description' => 'Permanent tokens for premium features, upgrades, and persistent actions',
                'default_count' => 100, // New users start with 100 L33t Bytes
                'icon' => '💎',
                'color' => '#6366f1',
                'is_active' => true,
            ],
            [
                'name' => 'Bonus Bytes',
                'slug' => 'bonus-bytes',
                'description' => 'Special reward tokens for achievements and milestones',
                'default_count' => 0,
                'icon' => '🎁',
                'color' => '#f59e0b',
                'is_active' => true,
            ],
        ];

        foreach ($tokens as $token) {
            Token::updateOrCreate(
                ['slug' => $token['slug']],
                $token
            );
        }
    }
}
