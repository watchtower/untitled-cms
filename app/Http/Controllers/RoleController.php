<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::all();

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Role::class);

        return Inertia::render('Roles/Create', [
            'availablePermissions' => Role::availablePermissions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Role::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'slug' => 'required|string|max:255|unique:roles',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => ['string', Rule::in(Role::availablePermissions())],
            'is_active' => 'boolean',
        ]);

        Role::create($validated);

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(string $id)
    {
        $role = Role::findOrFail($id);
        $this->authorize('update', $role);

        return Inertia::render('Roles/Edit', [
            'role' => $role,
            'availablePermissions' => Role::availablePermissions(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);
        $this->authorize('update', $role);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$role->id,
            'slug' => 'required|string|max:255|unique:roles,slug,'.$role->id,
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => ['string', Rule::in(Role::availablePermissions())],
            'is_active' => 'boolean',
        ]);

        $permissionsOverridden = false;
        if ($role->slug === 'admin') {
            if (isset($validated['is_active']) && ! $validated['is_active']) {
                return redirect()->back()->with('error', 'The Admin role cannot be deactivated.');
            }
            // Admin always retains the full permission set — prevent accidental lockout.
            // Use sorted array_diff so order differences don't trigger a false positive.
            $full = Role::availablePermissions();
            $submitted = $validated['permissions'] ?? [];
            $permissionsOverridden = array_diff($full, $submitted) !== []
                || array_diff($submitted, $full) !== [];
            $validated['permissions'] = $full;
        }

        $role->update($validated);

        $message = $permissionsOverridden
            ? 'Admin role updated. Permissions were reset to the full system set to prevent lockout.'
            : 'Role updated successfully.';

        if ($request->has('stay')) {
            return redirect()->back()->with('success', $permissionsOverridden ? $message : 'Role status updated successfully.');
        }

        return redirect()->route('admin.roles.index')->with('success', $message);
    }

    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);
        $this->authorize('delete', $role);

        if ($role->slug === 'admin') {
            return redirect()->back()->with('error', 'The Admin role cannot be deleted.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }
}
