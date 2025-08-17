<?php

namespace App\Console\Commands;

use App\Models\CounterTransaction;
use App\Models\CounterType;
use App\Models\UserCounter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetResettableCounters extends Command
{
    protected $signature = 'counters:reset {--type=daily : Reset type (daily, weekly, monthly)}';

    protected $description = 'Reset resettable counters (Bits) based on their reset schedule';

    public function handle()
    {
        $resetType = $this->option('type');

        $this->info("Starting {$resetType} counter reset...");

        // Get counter types that need to be reset
        $counterTypes = CounterType::where('reset_frequency', $resetType)
            ->where('is_active', true)
            ->get();

        if ($counterTypes->isEmpty()) {
            $this->info("No {$resetType} counter types found to reset.");

            return 0;
        }

        $totalUsersAffected = 0;
        $totalCountersReset = 0;

        DB::transaction(function () use ($counterTypes, &$totalUsersAffected, &$totalCountersReset) {
            foreach ($counterTypes as $counterType) {
                $this->info("Resetting: {$counterType->name}");

                // Get all user counters for this counter type
                $userCounters = UserCounter::where('counter_type_id', $counterType->id)
                    ->with('user')
                    ->get();

                foreach ($userCounters as $userCounter) {
                    $countBefore = $userCounter->current_count;

                    // Reset to default allocation based on subscription level
                    $newCount = $this->getDefaultAllocationForUser($userCounter->user, $counterType);

                    $userCounter->current_count = $newCount;
                    $userCounter->last_reset_at = now();
                    $userCounter->save();

                    // Log the reset transaction
                    CounterTransaction::create([
                        'user_id' => $userCounter->user_id,
                        'admin_id' => null,
                        'counter_id' => $counterType->id,
                        'count_change' => $newCount - $countBefore,
                        'count_before' => $countBefore,
                        'count_after' => $newCount,
                        'reason' => "Automatic {$counterType->reset_frequency} reset",
                        'type' => 'automatic_reset',
                        'created_at' => now(),
                    ]);

                    $totalUsersAffected++;
                }

                $totalCountersReset++;

                // Update counter type's last reset timestamp
                $counterType->update(['last_reset_at' => now()]);

                $this->info("  - Reset {$userCounters->count()} user counters");
            }
        });

        $this->info('Reset complete!');
        $this->info("Counters reset: {$totalCountersReset}");
        $this->info("Users affected: {$totalUsersAffected}");

        return 0;
    }

    /**
     * Get the default allocation for a user based on their subscription level
     */
    private function getDefaultAllocationForUser($user, CounterType $counterType): int
    {
        if (! $user->subscriptionLevel) {
            return $counterType->default_allocation;
        }

        // L33t gaming tier-based allocations
        return match ($user->subscriptionLevel->level) {
            1 => $counterType->default_allocation, // L33t Padawan (free)
            2 => $counterType->default_allocation * 2, // L33t Jedi (2x)
            3 => $counterType->default_allocation * 5, // L33t Master (5x)
            default => $counterType->default_allocation,
        };
    }
}
