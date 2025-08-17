<?php

namespace App\Console\Commands;

use App\Models\Token;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessSubscriptionBenefits extends Command
{
    protected $signature = 'subscription:process-benefits';

    protected $description = 'Process monthly subscription benefits (L33t Bytes allocation)';

    public function handle()
    {
        $this->info('Processing monthly subscription benefits...');

        // Get active paid subscribers (Jedi and Master levels)
        $subscribers = User::whereHas('subscriptionLevel', function ($query) {
            $query->where('level', '>', 1) // Above Padawan (free tier)
                ->where('is_active', true);
        })->where('subscription_active', true)->get();

        if ($subscribers->isEmpty()) {
            $this->info('No active paid subscribers found.');

            return 0;
        }

        $l33tBytesToken = Token::where('slug', 'l33t-bytes')->first();

        if (! $l33tBytesToken) {
            $this->error('L33t Bytes token not found!');

            return 1;
        }

        $totalProcessed = 0;
        $totalTokensGranted = 0;

        DB::transaction(function () use ($subscribers, $l33tBytesToken, &$totalProcessed, &$totalTokensGranted) {
            foreach ($subscribers as $user) {
                $allocation = $this->getMonthlyAllocation($user);

                if ($allocation > 0) {
                    // Get or create user token record
                    $userToken = UserToken::firstOrCreate([
                        'user_id' => $user->id,
                        'token_id' => $l33tBytesToken->id,
                    ], ['balance' => 0]);

                    // Add monthly allocation
                    $success = $userToken->addTokens(
                        $allocation,
                        "Monthly {$user->subscriptionLevel->name} subscription benefit",
                        null,
                        'subscription_benefit'
                    );

                    if ($success) {
                        $totalProcessed++;
                        $totalTokensGranted += $allocation;

                        $this->info("✓ {$user->name} ({$user->subscriptionLevel->name}): +{$allocation} L33t Bytes");
                    } else {
                        $this->error("✗ Failed to process {$user->name}");
                    }
                }
            }
        });

        $this->info('Monthly benefits processing complete!');
        $this->info("Subscribers processed: {$totalProcessed}");
        $this->info("Total L33t Bytes granted: {$totalTokensGranted}");

        return 0;
    }

    /**
     * Get monthly L33t Bytes allocation based on subscription level
     */
    private function getMonthlyAllocation(User $user): int
    {
        if (! $user->subscriptionLevel) {
            return 0;
        }

        return match ($user->subscriptionLevel->level) {
            1 => 0,    // L33t Padawan (free) - no monthly allocation
            2 => 1000, // L33t Jedi - 1,000 L33t Bytes per month
            3 => 3000, // L33t Master - 3,000 L33t Bytes per month
            default => 0,
        };
    }
}
