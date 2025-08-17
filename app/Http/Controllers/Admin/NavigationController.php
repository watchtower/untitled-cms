<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavigationItem;
use App\Models\Page;
use Illuminate\Http\Request;

class NavigationController extends Controller
{
    public function index()
    {
        $navigationItems = NavigationItem::with(['page', 'children.page'])
            ->topLevel()
            ->ordered()
            ->get();

        return view('admin.navigation.index', compact('navigationItems'));
    }

    public function create()
    {
        $pages = Page::published()->get();
        $parentItems = NavigationItem::topLevel()->ordered()->get();

        return view('admin.navigation.create', compact('pages', 'parentItems'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'type' => 'required|in:url,page,custom',
            'url' => 'required_if:type,url,custom|nullable|string',
            'page_id' => 'required_if:type,page|nullable|exists:pages,id',
            'parent_id' => 'nullable|exists:navigation_items,id',
            'is_visible' => 'boolean',
            'opens_new_tab' => 'boolean',
            'css_class' => 'nullable|string|max:255',
        ]);

        $maxSortOrder = NavigationItem::where('parent_id', $request->parent_id)
            ->max('sort_order') ?? 0;

        NavigationItem::create([
            'label' => $request->label,
            'type' => $request->type,
            'url' => $request->type === 'page' ? null : $request->url,
            'page_id' => $request->type === 'page' ? $request->page_id : null,
            'parent_id' => $request->parent_id,
            'sort_order' => $maxSortOrder + 1,
            'is_visible' => $request->boolean('is_visible', true),
            'opens_new_tab' => $request->boolean('opens_new_tab'),
            'css_class' => $request->css_class,
        ]);

        return redirect()->route('admin.navigation.index')
            ->with('success', 'Navigation item created successfully.');
    }

    public function edit(NavigationItem $navigation)
    {
        $pages = Page::published()->get();
        $parentItems = NavigationItem::topLevel()
            ->where('id', '!=', $navigation->id)
            ->ordered()
            ->get();

        return view('admin.navigation.edit', compact('navigation', 'pages', 'parentItems'));
    }

    public function update(Request $request, NavigationItem $navigation)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'type' => 'required|in:url,page,custom',
            'url' => 'required_if:type,url,custom|nullable|string',
            'page_id' => 'required_if:type,page|nullable|exists:pages,id',
            'parent_id' => 'nullable|exists:navigation_items,id',
            'is_visible' => 'boolean',
            'opens_new_tab' => 'boolean',
            'css_class' => 'nullable|string|max:255',
        ]);

        $navigation->update([
            'label' => $request->label,
            'type' => $request->type,
            'url' => $request->type === 'page' ? null : $request->url,
            'page_id' => $request->type === 'page' ? $request->page_id : null,
            'parent_id' => $request->parent_id,
            'is_visible' => $request->boolean('is_visible', true),
            'opens_new_tab' => $request->boolean('opens_new_tab'),
            'css_class' => $request->css_class,
        ]);

        return redirect()->route('admin.navigation.index')
            ->with('success', 'Navigation item updated successfully.');
    }

    public function destroy(NavigationItem $navigation)
    {
        $navigation->delete();

        return redirect()->route('admin.navigation.index')
            ->with('success', 'Navigation item deleted successfully.');
    }

    public function updateOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:navigation_items,id',
            'items.*.sort_order' => 'required|integer',
            'items.*.parent_id' => 'nullable|exists:navigation_items,id',
        ]);

        foreach ($request->items as $item) {
            NavigationItem::where('id', $item['id'])->update([
                'sort_order' => $item['sort_order'],
                'parent_id' => $item['parent_id'],
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function toggleVisibility(NavigationItem $navigation)
    {
        $navigation->update(['is_visible' => ! $navigation->is_visible]);

        return redirect()->route('admin.navigation.index')->with('success', 'Navigation item visibility updated.');
    }

    public function duplicate(NavigationItem $navigation)
    {
        $newItem = $navigation->replicate();
        $newItem->label = $newItem->label.' (copy)';
        $newItem->sort_order = NavigationItem::where('parent_id', $navigation->parent_id)->max('sort_order') + 1;
        $newItem->save();

        return redirect()->route('admin.navigation.index')->with('success', 'Navigation item duplicated.');
    }
}
