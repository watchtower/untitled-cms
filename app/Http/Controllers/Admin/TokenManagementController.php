<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Token;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Http\Request;

class TokenManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the Token management dashboard
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'overview');

        // Get token statistics
        $tokens = Token::withCount('userTokens')->get();
        $totalUsers = User::count();
        $totalTransactions = TokenTransaction::count();

        // Recent transactions
        $recentTransactions = TokenTransaction::with(['user', 'admin', 'token'])
            ->latest('created_at')
            ->limit(20)
            ->get();

        // Token usage statistics
        $tokenStats = [];
        foreach ($tokens as $token) {
            $tokenStats[] = [
                'token' => $token,
                'total_in_circulation' => $token->getTotalInCirculation(),
                'users_with_balance' => $token->getUserCount(),
                'recent_transactions' => TokenTransaction::where('token_id', $token->id)
                    ->recent(7)
                    ->count(),
            ];
        }

        return view('admin.token-management.index', compact(
            'activeTab',
            'tokens',
            'tokenStats',
            'totalUsers',
            'totalTransactions',
            'recentTransactions'
        ));
    }

    /**
     * Show user token balances
     */
    public function users(Request $request)
    {
        $query = User::with(['userTokens.token', 'subscriptionLevel']);

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
        $tokens = Token::active()->get();

        return view('admin.token-management.users', compact(
            'users',
            'subscriptionLevels',
            'tokens'
        ));
    }

    /**
     * Update user token balance
     */
    public function updateBalance(Request $request, User $user)
    {
        $request->validate([
            'token_id' => 'required|exists:tokens,id',
            'action' => 'required|in:add,deduct,set',
            'amount' => 'required|integer|min:0',
            'reason' => 'required|string|max:255',
        ]);

        $token = Token::findOrFail($request->token_id);

        // Get or create user token record
        $userToken = UserToken::firstOrCreate([
            'user_id' => $user->id,
            'token_id' => $token->id,
        ], [
            'balance' => 0,
        ]);

        $success = false;
        $message = '';

        switch ($request->action) {
            case 'add':
                $success = $userToken->addTokens(
                    $request->amount,
                    $request->reason,
                    auth()->user(),
                    'admin_grant'
                );
                $message = $success ? "Added {$request->amount} {$token->name} to {$user->name}'s balance." : 'Failed to add tokens.';
                break;

            case 'deduct':
                $success = $userToken->deductTokens(
                    $request->amount,
                    $request->reason,
                    auth()->user(),
                    'admin_deduct'
                );
                $message = $success ? "Deducted {$request->amount} {$token->name} from {$user->name}'s balance." : 'Insufficient balance or failed to deduct tokens.';
                break;

            case 'set':
                $success = $userToken->setBalance(
                    $request->amount,
                    $request->reason,
                    auth()->user()
                );
                $message = $success ? "Set {$user->name}'s {$token->name} balance to {$request->amount}." : 'Failed to set balance.';
                break;
        }

        if ($success) {
            return redirect()->back()->with('success', $message);
        } else {
            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Show token transactions
     */
    public function transactions(Request $request)
    {
        $query = TokenTransaction::with(['user', 'admin', 'token']);

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by token type
        if ($request->filled('token_id')) {
            $query->where('token_id', $request->token_id);
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
        $tokens = Token::all();
        $transactionTypes = ['manual', 'automatic', 'purchase', 'reward', 'admin_grant', 'admin_deduct', 'admin_set'];

        return view('admin.token-management.transactions', compact(
            'transactions',
            'tokens',
            'transactionTypes'
        ));
    }

    /**
     * Bulk token operations
     */
    public function bulkOperation(Request $request)
    {
        $request->validate([
            'operation' => 'required|in:grant_to_all,grant_to_subscription,reset_balances',
            'token_id' => 'required|exists:tokens,id',
            'amount' => 'required_if:operation,grant_to_all,grant_to_subscription|integer|min:0',
            'subscription_level_id' => 'required_if:operation,grant_to_subscription|nullable|exists:subscription_levels,id',
            'reason' => 'required|string|max:255',
        ]);

        $token = Token::findOrFail($request->token_id);
        $admin = auth()->user();
        $affectedUsers = 0;

        switch ($request->operation) {
            case 'grant_to_all':
                $users = User::all();
                foreach ($users as $user) {
                    $userToken = UserToken::firstOrCreate([
                        'user_id' => $user->id,
                        'token_id' => $token->id,
                    ], ['balance' => 0]);

                    if ($userToken->addTokens($request->amount, $request->reason, $admin, 'bulk_grant')) {
                        $affectedUsers++;
                    }
                }
                break;

            case 'grant_to_subscription':
                $users = User::where('subscription_level_id', $request->subscription_level_id)->get();
                foreach ($users as $user) {
                    $userToken = UserToken::firstOrCreate([
                        'user_id' => $user->id,
                        'token_id' => $token->id,
                    ], ['balance' => 0]);

                    if ($userToken->addTokens($request->amount, $request->reason, $admin, 'bulk_grant')) {
                        $affectedUsers++;
                    }
                }
                break;

            case 'reset_balances':
                $userTokens = UserToken::where('token_id', $token->id)->get();
                foreach ($userTokens as $userToken) {
                    if ($userToken->setBalance(0, $request->reason, $admin)) {
                        $affectedUsers++;
                    }
                }
                break;
        }

        return redirect()->back()->with('success', "Bulk operation completed. Affected {$affectedUsers} users.");
    }

    /**
     * Reset a specific token type for all users (Reset Scheduler functionality)
     */
    public function resetToken(Token $token)
    {
        $admin = auth()->user();
        $startTime = now();
        $affectedUsers = 0;
        $errors = [];

        \Log::info('Token Reset Scheduler: Starting manual reset for token type', [
            'token_name' => $token->name,
            'token_id' => $token->id,
            'token_slug' => $token->slug,
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'start_time' => $startTime,
        ]);

        try {
            // Get all user tokens for this token type
            $userTokens = UserToken::where('token_id', $token->id)
                ->with('user')
                ->get();

            foreach ($userTokens as $userToken) {
                try {
                    $oldBalance = $userToken->balance;
                    
                    // Reset to the default count for this token
                    $newBalance = $token->default_count;
                    
                    if ($userToken->setBalance($newBalance, "Manual reset via Token Reset Scheduler", $admin)) {
                        $affectedUsers++;
                        
                        \Log::info('Token Reset Scheduler: Successfully reset user token balance', [
                            'user_id' => $userToken->user_id,
                            'user_name' => $userToken->user->name,
                            'token_name' => $token->name,
                            'old_balance' => $oldBalance,
                            'new_balance' => $newBalance,
                        ]);
                    } else {
                        $errors[] = "Failed to reset token balance for user: {$userToken->user->name}";
                        
                        \Log::error('Token Reset Scheduler: Failed to reset user token balance', [
                            'user_id' => $userToken->user_id,
                            'user_name' => $userToken->user->name,
                            'token_name' => $token->name,
                            'old_balance' => $oldBalance,
                            'attempted_new_balance' => $newBalance,
                        ]);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error resetting token balance for user {$userToken->user->name}: " . $e->getMessage();
                    
                    \Log::error('Token Reset Scheduler: Exception during user token reset', [
                        'user_id' => $userToken->user_id,
                        'user_name' => $userToken->user->name,
                        'token_name' => $token->name,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            $endTime = now();
            $duration = $endTime->diffInSeconds($startTime);

            \Log::info('Token Reset Scheduler: Completed manual reset for token type', [
                'token_name' => $token->name,
                'token_id' => $token->id,
                'affected_users' => $affectedUsers,
                'total_users_processed' => $userTokens->count(),
                'errors_count' => count($errors),
                'duration_seconds' => $duration,
                'end_time' => $endTime,
                'success' => true,
            ]);

            if (count($errors) > 0) {
                return redirect()->back()->with([
                    'success' => "Reset completed for {$token->name}. Affected {$affectedUsers} users.",
                    'warning' => 'Some errors occurred: ' . implode(', ', array_slice($errors, 0, 3)) . (count($errors) > 3 ? ' and ' . (count($errors) - 3) . ' more...' : ''),
                ]);
            }

            return redirect()->back()->with('success', "Successfully reset {$token->name} for {$affectedUsers} users.");

        } catch (\Exception $e) {
            $endTime = now();
            
            \Log::error('Token Reset Scheduler: Fatal error during token reset', [
                'token_name' => $token->name,
                'token_id' => $token->id,
                'affected_users' => $affectedUsers,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_seconds' => $endTime->diffInSeconds($startTime),
                'success' => false,
            ]);

            return redirect()->back()->with('error', "Failed to reset {$token->name}: " . $e->getMessage());
        }
    }

    /**
     * Initialize default tokens for new users
     */
    public function initializeUserTokens(User $user)
    {
        $tokens = Token::active()->get();

        foreach ($tokens as $token) {
            if ($token->default_count > 0) {
                UserToken::firstOrCreate([
                    'user_id' => $user->id,
                    'token_id' => $token->id,
                ], [
                    'balance' => $token->default_count,
                ]);

                // Log initial grant
                TokenTransaction::create([
                    'user_id' => $user->id,
                    'admin_id' => null,
                    'token_id' => $token->id,
                    'amount' => $token->default_count,
                    'balance_before' => 0,
                    'balance_after' => $token->default_count,
                    'reason' => 'Initial token grant for new user',
                    'type' => 'automatic',
                    'created_at' => now(),
                ]);
            }
        }
    }
}
