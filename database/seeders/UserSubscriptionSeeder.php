<?php

namespace Database\Seeders;

use App\Models\CounterType;
use App\Models\SubscriptionLevel;
use App\Models\Token;
use App\Models\User;
use App\Models\UserCounter;
use App\Models\UserToken;
use Illuminate\Database\Seeder;

class UserSubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        // Get subscription levels
        $starter = SubscriptionLevel::where('slug', 'starter')->first();
        $pro = SubscriptionLevel::where('slug', 'pro')->first();
        $elite = SubscriptionLevel::where('slug', 'elite')->first();

        // Create demo users with different subscription levels
        $demoUsers = [
            [
                'name' => 'Starter User',
                'email' => 'starter@example.com',
                'password' => bcrypt('password'),
                'role' => 'editor',
                'subscription_level_id' => $starter?->id,
                'subscription_active' => true,
                'email_verified_at' => now(),
                'status' => 'active',
            ],
            [
                'name' => 'Pro User',
                'email' => 'pro@example.com',
                'password' => bcrypt('password'),
                'role' => 'editor',
                'subscription_level_id' => $pro?->id,
                'subscription_active' => true,
                'email_verified_at' => now(),
                'status' => 'active',
            ],
            [
                'name' => 'Elite User',
                'email' => 'elite@example.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'subscription_level_id' => $elite?->id,
                'subscription_active' => true,
                'email_verified_at' => now(),
                'status' => 'active',
            ],
            [
                'name' => 'No Subscription User',
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
                 ($user->subscriptionLevel?->name ?? 'No Subscription')."\n";
        }
    }

    /**
     * Initialize user's economy based on subscription level
     */
    private function initializeUserEconomy(User $user): void
    {
        // Initialize tokens (Permanent Tokens)
        $tokens = Token::active()->get();
        foreach ($tokens as $token) {
            $userToken = UserToken::updateOrCreate([
                'user_id' => $user->id,
                'token_id' => $token->id,
            ], [
                'balance' => $this->getTokenAllocation($user, $token),
            ]);

            echo "  - Initialized {$token->name}: {$userToken->balance}\n";
        }

        // Initialize counters (Monthly Credits)
        $counterTypes = CounterType::active()->get();
        foreach ($counterTypes as $counterType) {
            $userCounter = UserCounter::updateOrCreate([
                'user_id' => $user->id,
                'counter_type_id' => $counterType->id,
            ], [
                'current_count' => $this->getCounterAllocation($user, $counterType),
                'last_reset_at' => now(),
            ]);

            echo "  - Initialized {$counterType->name}: {$userCounter->current_count}\n";
        }
    }

    /**
     * Get token allocation based on user's subscription level
     */
    private function getTokenAllocation(User $user, Token $token): int
    {
        if (! $user->subscriptionLevel) {
            return $token->slug === 'permanent-tokens' ? 10 : 0;
        }

        return match ($user->subscriptionLevel->slug) {
            'starter' => 10,
            'pro' => 30,
            'elite' => 90,
            default => 10,
        };
    }

    /**
     * Get counter allocation based on user's subscription level
     */
    private function getCounterAllocation(User $user, CounterType $counterType): int
    {
        if (! $user->subscriptionLevel) {
            return $counterType->slug === 'monthly-credits' ? 100 : 0;
        }

        return match ($user->subscriptionLevel->slug) {
            'starter' => 100,
            'pro' => 1000,
            'elite' => 10000,
            default => 100,
        };
    }
}