<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request)
    {
        $query = User::withTrashed();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            if ($request->status === 'deleted') {
                $query->onlyTrashed();
            } else {
                $query->where('status', $request->status);
            }
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:super_admin,admin,editor'],
            'status' => ['required', 'in:active,inactive'],
            'email_verified' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => $validated['status'],
            'email_verified_at' => $request->boolean('email_verified') ? now() : null,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $subscriptionLevels = \App\Models\SubscriptionLevel::active()->orderBy('level')->get();
        return view('admin.users.edit', compact('user', 'subscriptionLevels'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:super_admin,admin,editor'],
            'status' => ['required', 'in:active,inactive'],
            'subscription_level_id' => ['nullable', 'exists:subscription_levels,id'],
            'subscription_active' => ['boolean'],
            'email_verified' => ['boolean'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'status' => $validated['status'],
            'subscription_level_id' => $validated['subscription_level_id'],
            'subscription_active' => $request->boolean('subscription_active'),
            'email_verified_at' => $request->boolean('email_verified') ? now() : null,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $oldSubscriptionLevel = $user->subscription_level_id;
        $user->update($updateData);

        // Auto-initialize tokens and counters if subscription level changed
        if ($oldSubscriptionLevel !== $validated['subscription_level_id']) {
            $this->initializeUserEconomy($user);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function activate(User $user)
    {
        $this->authorize('update', $user);

        $user->activate();

        return redirect()->back()
            ->with('success', 'User activated successfully.');
    }

    public function deactivate(User $user)
    {
        $this->authorize('update', $user);

        $user->deactivate();

        return redirect()->back()
            ->with('success', 'User deactivated successfully.');
    }

    public function restore(User $user)
    {
        $this->authorize('restore', $user);

        $user->restore();

        return redirect()->route('admin.users.index')
            ->with('success', 'User restored successfully.');
    }

    public function forceDelete(User $user)
    {
        $this->authorize('forceDelete', $user);

        $user->forceDelete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User permanently deleted.');
    }

    public function verifyEmail(User $user)
    {
        $this->authorize('update', $user);

        $user->update(['email_verified_at' => now()]);

        return redirect()->back()
            ->with('success', 'Email verified successfully.');
    }

    public function unverifyEmail(User $user)
    {
        $this->authorize('update', $user);

        $user->update(['email_verified_at' => null]);

        return redirect()->back()
            ->with('success', 'Email verification removed.');
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
                \App\Models\UserToken::firstOrCreate([
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
                \App\Models\UserCounter::firstOrCreate([
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
        if (!$user->subscriptionLevel) {
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
