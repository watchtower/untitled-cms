<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Ensure user has permission
        Gate::authorize('viewAny', User::class);

        $users = User::with('roles')->paginate(10);
        $trashedUsers = User::onlyTrashed()->with('roles')->paginate(50, ['*'], 'trashed_page');
        $roles = \App\Models\Role::all();

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'deleted' => User::onlyTrashed()->count(),
        ];

        return Inertia::render('Users/Index', [
            'users' => $users,
            'trashedUsers' => ['data' => $trashedUsers],
            'roles' => $roles,
            'stats' => $stats,
            'canCreate' => $request->user()->can('create', User::class),
            // We check permissions directly for UI flags using a generic model instance
            'canEdit' => $request->user()->can('update', new User),
            'canDelete' => $request->user()->can('delete', new User),
        ]);
    }

    /**
     * Invite a new user.
     */
    public function invite(Request $request)
    {
        Gate::authorize('create', User::class);

        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'role' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'email'             => $validated['email'],
            'name'              => explode('@', $validated['email'])[0], // Temporary name
            'password'          => Hash::make(\Illuminate\Support\Str::random(16)),
            'is_active'         => true,  // Admin-invited users are immediately active
            'email_verified_at' => now(), // Admin-invited users are pre-verified (admin vouches for them)
        ]);

        $user->roles()->attach($validated['role']);

        // In a real app, you would send an email here
        // \Mail::to($user->email)->send(new \App\Mail\UserInvitation($user));

        return redirect()->back()->with('success', 'Invitation sent to '.$validated['email']);
    }

    /**
     * Batch activate users.
     */
    public function batchActivate(Request $request)
    {
        // Treat as update
        // We use a generic user instance to check for general update permissions via the policy
        Gate::authorize('update', new User);

        $request->validate(['user_ids' => 'required|array']);

        $count = User::whereIn('_id', $request->user_ids)->update(['is_active' => true]);

        return redirect()->back()->with('success', "Activated {$count} users.");
    }

    /**
     * Batch deactivate users.
     */
    public function batchDeactivate(Request $request)
    {
        Gate::authorize('update', new User);

        $request->validate(['user_ids' => 'required|array']);

        // Prevent deactivating self
        $ids = array_filter($request->user_ids, fn ($id) => $id !== auth()->id());
        $count = User::whereIn('_id', $ids)->update(['is_active' => false]);

        return redirect()->back()->with('success', "Deactivated {$count} users.");
    }

    /**
     * Batch delete users.
     */
    public function batchDelete(Request $request)
    {
        Gate::authorize('delete', new User);

        $request->validate(['user_ids' => 'required|array']);

        // Prevent deleting self
        $ids = array_filter($request->user_ids, fn ($id) => $id !== auth()->id());
        $count = User::whereIn('_id', $ids)->delete();

        return redirect()->back()->with('success', "Deleted {$count} users.");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', User::class);

        return Inertia::render('Users/Create', [
            'roles' => \App\Models\Role::all(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id', // MongoDB object ID validation might need care, but exists:roles,id usually works if model is set up right
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'email_verified_at' => now(), // Auto-verify for admin-created users
            'is_active' => true, // New users are active by default
        ]);

        $user->roles()->attach($validated['roles']);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::with('roles')->findOrFail($id);
        Gate::authorize('update', $user);

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'roles' => \App\Models\Role::all(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        Gate::authorize('update', $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
            'is_active' => 'boolean',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        // Assuming syncRoles is a method provided by a package like Spatie's laravel-permission
        // If not, use $user->roles()->sync($validated['roles']);
        $user->syncRoles($validated['roles']);

        \App\Services\ActivityLogger::log('update', "Updated user: {$user->name}", $user);

        if ($request->has('stay')) {
            return redirect()->back()->with('success', 'User status updated successfully.');
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user) // Changed to use Route Model Binding
    {
        Gate::authorize('delete', $user);

        // Prevent deleting self (optional but good practice)
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot delete yourself.');
        }

        $name = $user->name; // Store name before deletion for logging

        // If using Spatie's laravel-permission, roles are often detached automatically on user delete.
        // If not, ensure roles are detached: $user->roles()->detach();
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(string $id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        Gate::authorize('restore', $user);

        $user->restore();

        \App\Services\ActivityLogger::log('restore', "Restored user: {$user->name}", $user);

        return redirect()->route('users.index')->with('success', 'User restored successfully.');
    }

    /**
     * Permanently delete a user.
     */
    public function forceDelete(string $id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        Gate::authorize('forceDelete', $user);

        $name = $user->name;
        $user->roles()->detach();
        $user->forceDelete();

        \App\Services\ActivityLogger::log('force_delete', "Permanently deleted user: {$name}");

        return redirect()->route('users.index')->with('success', 'User permanently deleted.');
    }

    /**
     * Logout user from all devices.
     */
    public function logoutAllDevices(string $id)
    {
        $user = User::findOrFail($id);
        // Assuming logout is an update-like action or specific permission
        // Reusing update policy for now as it modifies user session state
        Gate::authorize('update', $user);

        // Delete all sessions for this user except current one
        // This requires session database driver
        \DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', session()->getId())
            ->delete();

        \App\Services\ActivityLogger::log('logout_all_devices', "Logged out user from all devices: {$user->name}", $user);

        return redirect()->back()->with('success', 'User logged out from all devices.');
    }
}
