<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionLevel;
use App\Models\User;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Show subscription management dashboard
     */
    public function index()
    {
        $subscriptionLevels = SubscriptionLevel::active()->orderBy('level')->get();
        $totalUsers = User::count();
        $subscribedUsers = User::whereNotNull('subscription_level_id')->count();

        $subscriptionStats = SubscriptionLevel::active()
            ->withCount('users')
            ->orderBy('level')
            ->get();

        return view('admin.subscriptions.index', compact(
            'subscriptionLevels',
            'totalUsers',
            'subscribedUsers',
            'subscriptionStats'
        ));
    }

    /**
     * Upgrade user to higher subscription level
     */
    public function upgrade(Request $request, User $user)
    {
        $request->validate([
            'subscription_level_id' => ['required', 'exists:subscription_levels,id'],
        ]);

        $newLevel = SubscriptionLevel::findOrFail($request->subscription_level_id);
        $oldLevel = $user->subscriptionLevel;

        // Validate it's an upgrade
        if ($oldLevel && $newLevel->level <= $oldLevel->level) {
            return back()->withErrors(['subscription_level_id' => 'Selected level must be higher than current level.']);
        }

        $oldSubscriptionLevel = $user->subscription_level_id;

        $user->update([
            'subscription_level_id' => $newLevel->id,
            'subscription_active' => true,
            'subscription_started_at' => now(),
            'subscription_expires_at' => null, // No expiration for admin-assigned subscriptions
        ]);

        // Auto-initialize tokens and counters if subscription level changed
        if ($oldSubscriptionLevel !== $newLevel->id) {
            $this->initializeUserEconomy($user);
        }

        return back()->with('success', "User upgraded to {$newLevel->name} successfully.");
    }

    /**
     * Downgrade user to lower subscription level
     */
    public function downgrade(Request $request, User $user)
    {
        $request->validate([
            'subscription_level_id' => ['nullable', 'exists:subscription_levels,id'],
        ]);

        $currentLevel = $user->subscriptionLevel;
        $newLevelId = $request->subscription_level_id;

        if ($newLevelId) {
            $newLevel = SubscriptionLevel::findOrFail($newLevelId);

            // Validate it's a downgrade
            if ($currentLevel && $newLevel->level >= $currentLevel->level) {
                return back()->withErrors(['subscription_level_id' => 'Selected level must be lower than current level.']);
            }
        }

        $oldSubscriptionLevel = $user->subscription_level_id;

        $user->update([
            'subscription_level_id' => $newLevelId,
            'subscription_active' => $newLevelId ? true : false,
            'subscription_started_at' => $newLevelId ? now() : null,
            'subscription_expires_at' => null,
        ]);

        // Reinitialize tokens and counters for new level
        if ($oldSubscriptionLevel !== $newLevelId) {
            $this->initializeUserEconomy($user);
        }

        $message = $newLevelId
            ? "User downgraded to {$newLevel->name} successfully."
            : 'User subscription removed successfully.';

        return back()->with('success', $message);
    }

    /**
     * Cancel user subscription
     */
    public function cancel(User $user)
    {
        $user->update([
            'subscription_level_id' => null,
            'subscription_active' => false,
            'subscription_started_at' => null,
            'subscription_expires_at' => null,
        ]);

        return back()->with('success', 'User subscription cancelled successfully.');
    }

    /**
     * Bulk subscription operations
     */
    public function bulkOperation(Request $request)
    {
        $request->validate([
            'action' => ['required', 'in:upgrade,downgrade,cancel'],
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'subscription_level_id' => ['required_unless:action,cancel', 'exists:subscription_levels,id'],
        ]);

        $users = User::whereIn('id', $request->user_ids)->get();
        $count = 0;

        foreach ($users as $user) {
            switch ($request->action) {
                case 'upgrade':
                case 'downgrade':
                    $oldLevel = $user->subscription_level_id;
                    $user->update([
                        'subscription_level_id' => $request->subscription_level_id,
                        'subscription_active' => true,
                        'subscription_started_at' => now(),
                        'subscription_expires_at' => null,
                    ]);
                    if ($oldLevel !== $request->subscription_level_id) {
                        $this->initializeUserEconomy($user);
                    }
                    $count++;
                    break;

                case 'cancel':
                    $user->update([
                        'subscription_level_id' => null,
                        'subscription_active' => false,
                        'subscription_started_at' => null,
                        'subscription_expires_at' => null,
                    ]);
                    $count++;
                    break;
            }
        }

        return back()->with('success', "Bulk operation completed for {$count} users.");
    }

    /**
     * Initialize user's L33t economy (tokens and counters) based on subscription level
     */
    private function initializeUserEconomy(User $user)
    {
        // Initialize tokens
        $tokens = \App\Models\Token::active()->get();
        foreach ($tokens as $token) {
            if ($token->default_count > 0) {
                \App\Models\UserToken::updateOrCreate([
                    'user_id' => $user->id,
                    'token_id' => $token->id,
                ], [
                    'balance' => $this->getDefaultTokenAllocation($user, $token),
                ]);
            }
        }

        // Initialize counters
        $counterTypes = \App\Models\CounterType::active()->get();
        foreach ($counterTypes as $counterType) {
            if ($counterType->default_allocation > 0) {
                \App\Models\UserCounter::updateOrCreate([
                    'user_id' => $user->id,
                    'counter_type_id' => $counterType->id,
                ], [
                    'current_count' => $this->getDefaultCounterAllocation($user, $counterType),
                ]);
            }
        }
    }

    /**
     * Get default token allocation based on user's subscription level
     */
    private function getDefaultTokenAllocation(User $user, \App\Models\Token $token): int
    {
        if (! $user->subscriptionLevel) {
            return $token->default_count;
        }

        // Multiply base allocation by subscription level
        return $token->default_count * $user->subscriptionLevel->level;
    }

    /**
     * Get default counter allocation based on user's subscription level
     */
    private function getDefaultCounterAllocation(User $user, \App\Models\CounterType $counterType): int
    {
        if (! $user->subscriptionLevel) {
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
