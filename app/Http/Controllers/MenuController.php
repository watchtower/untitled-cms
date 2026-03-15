<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Menu::class);

        $menus = Menu::orderBy('name', 'asc')->get();

        return Inertia::render('Menus/Index', [
            'menus' => $menus,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Menu::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:menus,slug',
            'is_active' => 'boolean',
        ]);

        $menu = Menu::create($validated);

        ActivityLogger::log('create', "Created menu: {$menu->name}", $menu);

        return redirect()->route('admin.menus.edit', $menu->id)->with('success', 'Menu created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $menu = Menu::findOrFail($id);
        $this->authorize('update', $menu);

        return Inertia::render('Menus/Edit', [
            'menu' => $menu,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $menu = Menu::findOrFail($id);
        $this->authorize('update', $menu);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:menus,slug,'.$id.',_id',
            'items' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $menu->update($validated);

        ActivityLogger::log('update', "Updated menu: {$menu->name}", $menu);

        if ($request->has('stay')) {
            return redirect()->back()->with('success', 'Menu updated successfully.');
        }

        return redirect()->route('admin.menus.index')->with('success', 'Menu updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Menu $menu)
    {
        $this->authorize('delete', $menu);

        $name = $menu->name;
        $menu->delete();

        ActivityLogger::log('delete', "Deleted menu: {$name}", $menu);

        return redirect()->route('admin.menus.index')->with('success', 'Menu deleted successfully.');
    }
}
