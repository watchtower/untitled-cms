<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $this->authorize('viewAny', Role::class);

        $roles = \App\Models\Role::all();

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // $this->authorize('create', Role::class);

        return Inertia::render('Roles/Create', [
            'availablePermissions' => \App\Models\Role::availablePermissions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // $this->authorize('create', Role::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'slug' => 'required|string|max:255|unique:roles',
            'permissions' => 'array',
            'permissions.*' => 'string',
            'is_active' => 'boolean',
        ]);

        \App\Models\Role::create($validated);

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(string $id)
    {
        $role = \App\Models\Role::findOrFail($id);
        // $this->authorize('update', $role);

        return Inertia::render('Roles/Edit', [
            'role' => $role,
            'availablePermissions' => \App\Models\Role::availablePermissions(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $role = \App\Models\Role::findOrFail($id);
        // $this->authorize('update', $role);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$role->id,
            'slug' => 'required|string|max:255|unique:roles,slug,'.$role->id,
            'permissions' => 'array',
            'permissions.*' => 'string',
            'is_active' => 'boolean',
        ]);

        // Protect Admin role from being deactivated
        if ($role->slug === 'admin' && isset($validated['is_active']) && ! $validated['is_active']) {
            return redirect()->back()->with('error', 'The Admin role cannot be deactivated.');
        }

        $role->update($validated);

        if ($request->has('stay')) {
            return redirect()->back()->with('success', 'Role status updated successfully.');
        }

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(string $id)
    {
        $role = \App\Models\Role::findOrFail($id);
        // $this->authorize('delete', $role);

        // Prevent deleting critical roles like Admin if desired, or if assigned to users

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }
}
