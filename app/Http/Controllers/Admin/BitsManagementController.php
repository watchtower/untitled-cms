<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CounterTransaction;
use App\Models\CounterType;
use App\Models\User;
use App\Models\UserCounter;
use Illuminate\Http\Request;

class BitsManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the Bits management dashboard
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'overview');

        // Get counter type statistics
        $counterTypes = CounterType::withCount('userCounters')->get();
        $totalUsers = User::count();
        $totalTransactions = CounterTransaction::count();

        // Recent transactions
        $recentTransactions = CounterTransaction::with(['user', 'admin', 'counterType'])
            ->latest('created_at')
            ->limit(20)
            ->get();

        // Counter statistics
        $counterStats = [];
        foreach ($counterTypes as $counterType) {
            $counterStats[] = [
                'counterType' => $counterType,
                'total_allocation' => $counterType->getTotalAllocation(),
                'users_with_counters' => $counterType->getUserCount(),
                'recent_transactions' => $counterType->getRecentTransactionCount(7),
            ];
        }

        return view('admin.bits-management.index', compact(
            'activeTab',
            'counterTypes',
            'counterStats',
            'totalUsers',
            'totalTransactions',
            'recentTransactions'
        ));
    }

    /**
     * Show user counter balances
     */
    public function users(Request $request)
    {
        $query = User::with(['userCounters.counterType', 'subscriptionLevel']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by subscription level
        if ($request->filled('subscription')) {
            $query->where('subscription_level_id', $request->subscription);
        }

        $users = $query->paginate(20);
        $subscriptionLevels = \App\Models\SubscriptionLevel::all();
        $counterTypes = CounterType::active()->get();

        return view('admin.bits-management.users', compact(
            'users',
            'subscriptionLevels',
            'counterTypes'
        ));
    }

    /**
     * Update user counter balance
     */
    public function updateBalance(Request $request, User $user)
    {
        $request->validate([
            'counter_type_id' => 'required|exists:counter_types,id',
            'action' => 'required|in:add,deduct,set',
            'amount' => 'required|integer|min:0',
            'reason' => 'required|string|max:255',
        ]);

        $counterType = CounterType::findOrFail($request->counter_type_id);

        // Get or create user counter record
        $userCounter = UserCounter::firstOrCreate([
            'user_id' => $user->id,
            'counter_type_id' => $counterType->id,
        ], [
            'current_count' => 0,
        ]);

        $success = false;
        $message = '';

        switch ($request->action) {
            case 'add':
                $success = $userCounter->addCount(
                    $request->amount,
                    $request->reason,
                    auth()->user(),
                    'admin_grant'
                );
                $message = $success ? "Added {$request->amount} {$counterType->name} to {$user->name}'s balance." : 'Failed to add counters.';
                break;

            case 'deduct':
                $success = $userCounter->deductCount(
                    $request->amount,
                    $request->reason,
                    auth()->user(),
                    'admin_deduct'
                );
                $message = $success ? "Deducted {$request->amount} {$counterType->name} from {$user->name}'s balance." : 'Insufficient balance or failed to deduct counters.';
                break;

            case 'set':
                $success = $userCounter->setCount(
                    $request->amount,
                    $request->reason,
                    auth()->user()
                );
                $message = $success ? "Set {$user->name}'s {$counterType->name} balance to {$request->amount}." : 'Failed to set balance.';
                break;
        }

        if ($success) {
            return redirect()->back()->with('success', $message);
        } else {
            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Show counter transactions
     */
    public function transactions(Request $request)
    {
        $query = CounterTransaction::with(['user', 'admin', 'counterType']);

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by counter type
        if ($request->filled('counter_id')) {
            $query->where('counter_id', $request->counter_id);
        }

        // Filter by transaction type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to.' 23:59:59');
        }

        $transactions = $query->latest('created_at')->paginate(50);
        $counterTypes = CounterType::all();
        $transactionTypes = ['manual', 'automatic_reset', 'usage', 'admin_grant', 'admin_deduct', 'admin_set'];

        return view('admin.bits-management.transactions', compact(
            'transactions',
            'counterTypes',
            'transactionTypes'
        ));
    }

    /**
     * Bulk counter operations
     */
    public function bulkOperation(Request $request)
    {
        \Log::info('Bulk operation started', $request->all());
        
        $request->validate([
            'operation' => 'required|in:grant_to_all,grant_to_subscription,reset_counters',
            'counter_type_id' => 'required|exists:counter_types,id',
            'amount' => 'required_if:operation,grant_to_all,grant_to_subscription|integer|min:0',
            'subscription_level_id' => 'required_if:operation,grant_to_subscription|nullable|exists:subscription_levels,id',
            'reason' => 'required|string|max:255',
        ]);

        $counterType = CounterType::findOrFail($request->counter_type_id);
        $admin = auth()->user();
        $affectedUsers = 0;

        switch ($request->operation) {
            case 'grant_to_all':
                $users = User::all();
                \Log::info('Processing grant_to_all for ' . $users->count() . ' users');
                foreach ($users as $user) {
                    $userCounter = UserCounter::firstOrCreate([
                        'user_id' => $user->id,
                        'counter_type_id' => $counterType->id,
                    ], ['current_count' => 0]);

                    \Log::info('Adding count for user', ['user_id' => $user->id, 'amount' => $request->amount]);
                    if ($userCounter->addCount($request->amount, $request->reason, $admin, 'bulk_grant')) {
                        $affectedUsers++;
                        \Log::info('Successfully added count for user ' . $user->id);
                    } else {
                        \Log::error('Failed to add count for user ' . $user->id);
                    }
                }
                break;

            case 'grant_to_subscription':
                $users = User::where('subscription_level_id', $request->subscription_level_id)->get();
                foreach ($users as $user) {
                    $userCounter = UserCounter::firstOrCreate([
                        'user_id' => $user->id,
                        'counter_type_id' => $counterType->id,
                    ], ['current_count' => 0]);

                    if ($userCounter->addCount($request->amount, $request->reason, $admin, 'bulk_grant')) {
                        $affectedUsers++;
                    }
                }
                break;

            case 'reset_counters':
                $userCounters = UserCounter::where('counter_type_id', $counterType->id)->get();
                foreach ($userCounters as $userCounter) {
                    if ($userCounter->setCount(0, $request->reason, $admin)) {
                        $affectedUsers++;
                    }
                }
                break;
        }

        \Log::info('Bulk operation completed', ['affected_users' => $affectedUsers]);
        return redirect()->back()->with('success', "Bulk operation completed. Affected {$affectedUsers} users.");
    }

    /**
     * Initialize default counters for new users
     */
    public function initializeUserCounters(User $user)
    {
        $counterTypes = CounterType::active()->get();

        foreach ($counterTypes as $counterType) {
            if ($counterType->default_allocation > 0) {
                UserCounter::firstOrCreate([
                    'user_id' => $user->id,
                    'counter_type_id' => $counterType->id,
                ], [
                    'current_count' => $this->getDefaultAllocationForUser($user, $counterType),
                ]);

                // Log initial grant
                CounterTransaction::create([
                    'user_id' => $user->id,
                    'admin_id' => null,
                    'counter_id' => $counterType->id,
                    'count_change' => $this->getDefaultAllocationForUser($user, $counterType),
                    'count_before' => 0,
                    'count_after' => $this->getDefaultAllocationForUser($user, $counterType),
                    'reason' => 'Initial counter allocation for new user',
                    'type' => 'automatic',
                    'created_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reset a specific counter type for all users (Reset Scheduler functionality)
     */
    public function resetCounterType(CounterType $counterType)
    {
        $admin = auth()->user();
        $startTime = now();
        $affectedUsers = 0;
        $errors = [];

        \Log::info('Reset Scheduler: Starting manual reset for counter type', [
            'counter_type' => $counterType->name,
            'counter_type_id' => $counterType->id,
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'reset_frequency' => $counterType->reset_frequency,
            'start_time' => $startTime,
        ]);

        try {
            // Get all user counters for this counter type
            $userCounters = UserCounter::where('counter_type_id', $counterType->id)
                ->with('user')
                ->get();

            foreach ($userCounters as $userCounter) {
                try {
                    $oldCount = $userCounter->current_count;
                    
                    // Reset to the user's default allocation based on subscription level
                    $newCount = $this->getDefaultAllocationForUser($userCounter->user, $counterType);
                    
                    if ($userCounter->setCount($newCount, "Manual reset via Reset Scheduler", $admin)) {
                        $affectedUsers++;
                        
                        \Log::info('Reset Scheduler: Successfully reset user counter', [
                            'user_id' => $userCounter->user_id,
                            'user_name' => $userCounter->user->name,
                            'counter_type' => $counterType->name,
                            'old_count' => $oldCount,
                            'new_count' => $newCount,
                        ]);
                    } else {
                        $errors[] = "Failed to reset counter for user: {$userCounter->user->name}";
                        
                        \Log::error('Reset Scheduler: Failed to reset user counter', [
                            'user_id' => $userCounter->user_id,
                            'user_name' => $userCounter->user->name,
                            'counter_type' => $counterType->name,
                            'old_count' => $oldCount,
                            'attempted_new_count' => $newCount,
                        ]);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error resetting counter for user {$userCounter->user->name}: " . $e->getMessage();
                    
                    \Log::error('Reset Scheduler: Exception during user counter reset', [
                        'user_id' => $userCounter->user_id,
                        'user_name' => $userCounter->user->name,
                        'counter_type' => $counterType->name,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // Update the next reset time based on frequency
            $counterType->updateNextResetTime();

            $endTime = now();
            $duration = $endTime->diffInSeconds($startTime);

            \Log::info('Reset Scheduler: Completed manual reset for counter type', [
                'counter_type' => $counterType->name,
                'counter_type_id' => $counterType->id,
                'affected_users' => $affectedUsers,
                'total_users_processed' => $userCounters->count(),
                'errors_count' => count($errors),
                'duration_seconds' => $duration,
                'end_time' => $endTime,
                'success' => true,
            ]);

            if (count($errors) > 0) {
                return redirect()->back()->with([
                    'success' => "Reset completed for {$counterType->name}. Affected {$affectedUsers} users.",
                    'warning' => 'Some errors occurred: ' . implode(', ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? ' and ' . (count($errors) - 3) . ' more...' : ''),
                ]);
            }

            return redirect()->back()->with('success', "Successfully reset {$counterType->name} for {$affectedUsers} users. Next reset scheduled for " . 
                ($counterType->next_reset_at ? $counterType->next_reset_at->format('M j, g:i A') : 'unscheduled'));

        } catch (\Exception $e) {
            $endTime = now();
            
            \Log::error('Reset Scheduler: Fatal error during counter reset', [
                'counter_type' => $counterType->name,
                'counter_type_id' => $counterType->id,
                'affected_users' => $affectedUsers,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_seconds' => $endTime->diffInSeconds($startTime),
                'success' => false,
            ]);

            return redirect()->back()->with('error', "Failed to reset {$counterType->name}: " . $e->getMessage());
        }
    }

    /**
     * Get the default allocation for a user based on their subscription level
     */
    private function getDefaultAllocationForUser(User $user, CounterType $counterType): int
    {
        if (! $user->subscriptionLevel) {
            return $counterType->default_allocation;
        }

        // Tier-based allocations
        return match ($user->subscriptionLevel->level) {
            1 => $counterType->default_allocation, // Padawan (free)
            2 => $counterType->default_allocation * 2, // Jedi (2x)
            3 => $counterType->default_allocation * 5, // Master (5x)
            default => $counterType->default_allocation,
        };
    }
}
