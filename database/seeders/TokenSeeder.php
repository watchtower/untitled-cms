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
                'name' => 'Permanent Tokens',
                'slug' => 'permanent-tokens',
                'description' => 'Permanent tokens (Bytes) for unlockable perks (extra monitors, premium features, subdomains).',
                'default_count' => 10, // New users on the Starter plan get 10
                'icon' => '💎',
                'color' => '#6366f1',
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
