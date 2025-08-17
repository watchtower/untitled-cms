<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SubscriptionLevel;
use App\Models\Token;
use App\Models\CounterType;
use App\Models\UserToken;
use App\Models\UserCounter;
use Illuminate\Database\Seeder;

class UserSubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        // Get subscription levels
        $padawan = SubscriptionLevel::where('slug', 'padawan')->first();
        $jedi = SubscriptionLevel::where('slug', 'jedi')->first();
        $master = SubscriptionLevel::where('slug', 'master')->first();

        // Create demo users with different subscription levels
        $demoUsers = [
            [
                'name' => 'John Padawan',
                'email' => 'padawan@example.com',
                'password' => bcrypt('password'),
                'role' => 'editor',
                'subscription_level_id' => $padawan?->id,
                'subscription_active' => true,
                'email_verified_at' => now(),
                'status' => 'active',
            ],
            [
                'name' => 'Jane Jedi',
                'email' => 'jedi@example.com',
                'password' => bcrypt('password'),
                'role' => 'editor',
                'subscription_level_id' => $jedi?->id,
                'subscription_active' => true,
                'email_verified_at' => now(),
                'status' => 'active',
            ],
            [
                'name' => 'Bob Master',
                'email' => 'master@example.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'subscription_level_id' => $master?->id,
                'subscription_active' => true,
                'email_verified_at' => now(),
                'status' => 'active',
            ],
            [
                'name' => 'Free User',
                'email' => 'free@example.com',
                'password' => bcrypt('password'),
                'role' => 'editor',
                'subscription_level_id' => null,
                'subscription_active' => false,
                'email_verified_at' => now(),
                'status' => 'active',
            ],
        ];

        foreach ($demoUsers as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // Initialize user's economy based on subscription level
            $this->initializeUserEconomy($user);

            echo "Created/Updated user: {$user->name} with subscription: " . 
                 ($user->subscriptionLevel?->name ?? 'No Subscription') . "\n";
        }
    }

    /**
     * Initialize user's L33t economy based on subscription level
     */
    private function initializeUserEconomy(User $user): void
    {
        // Initialize tokens (L33t Bytes)
        $tokens = Token::active()->get();
        foreach ($tokens as $token) {
            if ($token->default_count > 0) {
                $userToken = UserToken::updateOrCreate([
                    'user_id' => $user->id,
                    'token_id' => $token->id,
                ], [
                    'balance' => $this->getDefaultTokenAllocation($user, $token),
                ]);

                echo "  - Initialized {$token->name}: {$userToken->balance}\n";
            }
        }

        // Initialize counters (Bits)
        $counterTypes = CounterType::active()->get();
        foreach ($counterTypes as $counterType) {
            if ($counterType->default_allocation > 0) {
                $userCounter = UserCounter::updateOrCreate([
                    'user_id' => $user->id,
                    'counter_type_id' => $counterType->id,
                ], [
                    'current_count' => $this->getDefaultCounterAllocation($user, $counterType),
                    'last_reset_at' => now(),
                ]);

                echo "  - Initialized {$counterType->name}: {$userCounter->current_count}\n";
            }
        }
    }

    /**
     * Get default token allocation based on user's subscription level
     */
    private function getDefaultTokenAllocation(User $user, Token $token): int
    {
        if (!$user->subscriptionLevel) {
            return $token->default_count;
        }

        // Multiply base allocation by subscription level
        return $token->default_count * $user->subscriptionLevel->level;
    }

    /**
     * Get default counter allocation based on user's subscription level
     */
    private function getDefaultCounterAllocation(User $user, CounterType $counterType): int
    {
        if (!$user->subscriptionLevel) {
            return $counterType->default_allocation;
        }

        return match ($user->subscriptionLevel->level) {
            1 => $counterType->default_allocation, // Padawan: base allocation
            2 => $counterType->default_allocation * 5, // Jedi: 5x allocation
            3 => 999999, // Master: unlimited
            default => $counterType->default_allocation,
        };
    }
}