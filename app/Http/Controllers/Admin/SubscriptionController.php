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

        // Enhanced subscription statistics with detailed breakdowns
        $subscriptionStats = SubscriptionLevel::active()
            ->withCount(['users' => function ($query) {
                $query->whereNotNull('email_verified_at'); // Only verified users
            }])
            ->orderBy('level')
            ->get()
            ->map(function ($level) use ($totalUsers) {
                $levelTotalUsers = $level->users()->count();
                $activeUsers = $level->users()->whereNotNull('email_verified_at')->count();
                $inactiveUsers = $levelTotalUsers - $activeUsers;
                
                // Calculate growth metrics (last 30 days)
                $newUsersThisMonth = $level->users()
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count();
                    
                $previousMonthUsers = $level->users()
                    ->where('created_at', '>=', now()->subDays(60))
                    ->where('created_at', '<', now()->subDays(30))
                    ->count();
                    
                $growthRate = $previousMonthUsers > 0 
                    ? (($newUsersThisMonth - $previousMonthUsers) / $previousMonthUsers) * 100 
                    : ($newUsersThisMonth > 0 ? 100 : 0);

                // Calculate revenue potential (if pricing is available)
                $monthlyRevenue = $level->price * $activeUsers;

                return [
                    'level' => $level,
                    'total_users' => $levelTotalUsers,
                    'active_users' => $activeUsers,
                    'inactive_users' => $inactiveUsers,
                    'activation_rate' => $levelTotalUsers > 0 ? ($activeUsers / $levelTotalUsers) * 100 : 0,
                    'new_users_this_month' => $newUsersThisMonth,
                    'growth_rate' => $growthRate,
                    'monthly_revenue' => $monthlyRevenue,
                    'percentage_of_total' => $totalUsers > 0 ? ($levelTotalUsers / $totalUsers) * 100 : 0,
                ];
            });

        // Overall metrics
        $metrics = [
            'total_users' => $totalUsers,
            'subscribed_users' => $subscribedUsers,
            'free_users' => $totalUsers - $subscribedUsers,
            'subscription_rate' => $totalUsers > 0 ? ($subscribedUsers / $totalUsers) * 100 : 0,
            'total_monthly_revenue' => $subscriptionStats->sum('monthly_revenue'),
            'average_revenue_per_user' => $subscribedUsers > 0 ? $subscriptionStats->sum('monthly_revenue') / $subscribedUsers : 0,
        ];

        // Recent subscription changes (last 30 days)
        $recentChanges = User::with(['subscriptionLevel'])
            ->whereNotNull('subscription_level_id')
            ->where('updated_at', '>=', now()->subDays(30))
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.subscriptions.index', compact(
            'subscriptionLevels',
            'subscriptionStats',
            'metrics',
            'recentChanges',
            'totalUsers',
            'subscribedUsers'
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
     * Initialize user's economy (tokens and counters) based on subscription level
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
