<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaxonomyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'categories');

        $categories = Category::withCount('pages')
            ->orderBy('name')
            ->get();

        $tags = Tag::withCount('pages')
            ->orderBy('name')
            ->get();

        // Get usage statistics
        $categoryStats = [
            'total' => $categories->count(),
            'used' => $categories->where('pages_count', '>', 0)->count(),
            'unused' => $categories->where('pages_count', 0)->count(),
        ];

        $tagStats = [
            'total' => $tags->count(),
            'used' => $tags->where('pages_count', '>', 0)->count(),
            'unused' => $tags->where('pages_count', 0)->count(),
        ];

        return view('admin.taxonomy.index', compact('categories', 'tags', 'activeTab', 'categoryStats', 'tagStats'));
    }

    public function store(Request $request)
    {
        $type = $request->input('type'); // 'category' or 'tag'

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:category,tag',
        ]);

        $data = [
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ];

        if ($type === 'category') {
            // Check for duplicate category
            if (Category::where('name', $request->name)->exists()) {
                return back()->withErrors(['name' => 'Category already exists.']);
            }

            Category::create($data);
            $message = 'Category created successfully.';
        } else {
            // Check for duplicate tag
            if (Tag::where('name', $request->name)->exists()) {
                return back()->withErrors(['name' => 'Tag already exists.']);
            }

            Tag::create($data);
            $message = 'Tag created successfully.';
        }

        return redirect()->route('admin.taxonomy.index', ['tab' => $type === 'category' ? 'categories' : 'tags'])
            ->with('success', $message);
    }

    public function update(Request $request, $type, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $data = [
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ];

        if ($type === 'category') {
            $item = Category::findOrFail($id);

            // Check for duplicate category (excluding current)
            if (Category::where('name', $request->name)->where('id', '!=', $id)->exists()) {
                return back()->withErrors(['name' => 'Category already exists.']);
            }

            $item->update($data);
            $message = 'Category updated successfully.';
        } else {
            $item = Tag::findOrFail($id);

            // Check for duplicate tag (excluding current)
            if (Tag::where('name', $request->name)->where('id', '!=', $id)->exists()) {
                return back()->withErrors(['name' => 'Tag already exists.']);
            }

            $item->update($data);
            $message = 'Tag updated successfully.';
        }

        return redirect()->route('admin.taxonomy.index', ['tab' => $type === 'category' ? 'categories' : 'tags'])
            ->with('success', $message);
    }

    public function destroy($type, $id)
    {
        if ($type === 'category') {
            $item = Category::findOrFail($id);
            $itemType = 'Category';
        } else {
            $item = Tag::findOrFail($id);
            $itemType = 'Tag';
        }

        // Check if item is in use
        if ($item->pages()->count() > 0) {
            return back()->with('error', $itemType.' cannot be deleted because it is assigned to pages.');
        }

        $item->delete();

        return redirect()->route('admin.taxonomy.index', ['tab' => $type === 'category' ? 'categories' : 'tags'])
            ->with('success', $itemType.' deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'type' => 'required|in:category,tag',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $type = $request->type;
        $ids = $request->ids;
        $deleted = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                if ($type === 'category') {
                    $item = Category::findOrFail($id);
                    if ($item->pages()->count() > 0) {
                        $errors[] = "Category '{$item->name}' is in use and cannot be deleted.";

                        continue;
                    }
                } else {
                    $item = Tag::findOrFail($id);
                    if ($item->pages()->count() > 0) {
                        $errors[] = "Tag '{$item->name}' is in use and cannot be deleted.";

                        continue;
                    }
                }

                $item->delete();
                $deleted++;
            } catch (\Exception $e) {
                $errors[] = "Error deleting item with ID {$id}.";
            }
        }

        $message = $deleted > 0 ? "{$deleted} ".Str::plural(($type === 'category' ? 'category' : 'tag'), $deleted).' deleted successfully.' : '';

        if (! empty($errors)) {
            return back()->with('warning', $message ? $message.' However, some items could not be deleted: '.implode(' ', $errors) : implode(' ', $errors));
        }

        return redirect()->route('admin.taxonomy.index', ['tab' => $type === 'category' ? 'categories' : 'tags'])
            ->with('success', $message);
    }

    public function convert(Request $request)
    {
        $request->validate([
            'from_type' => 'required|in:category,tag',
            'to_type' => 'required|in:category,tag',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        if ($request->from_type === $request->to_type) {
            return back()->with('error', 'Cannot convert to the same type.');
        }

        $converted = 0;
        $errors = [];

        foreach ($request->ids as $id) {
            try {
                if ($request->from_type === 'category') {
                    $sourceItem = Category::findOrFail($id);
                    $targetModel = Tag::class;
                } else {
                    $sourceItem = Tag::findOrFail($id);
                    $targetModel = Category::class;
                }

                // Check if target already exists
                if ($targetModel::where('name', $sourceItem->name)->exists()) {
                    $errors[] = "Target with name '{$sourceItem->name}' already exists.";

                    continue;
                }

                // Create new item in target type
                $newItem = $targetModel::create([
                    'name' => $sourceItem->name,
                    'slug' => $sourceItem->slug,
                ]);

                // Transfer page relationships
                $pages = $sourceItem->pages;
                foreach ($pages as $page) {
                    if ($request->from_type === 'category') {
                        $page->tags()->attach($newItem->id);
                        $page->categories()->detach($sourceItem->id);
                    } else {
                        $page->categories()->attach($newItem->id);
                        $page->tags()->detach($sourceItem->id);
                    }
                }

                // Delete source item
                $sourceItem->delete();
                $converted++;
            } catch (\Exception $e) {
                $errors[] = "Error converting item with ID {$id}.";
            }
        }

        $fromTypeName = $request->from_type === 'category' ? 'category' : 'tag';
        $toTypeName = $request->to_type === 'category' ? 'category' : 'tag';

        $message = $converted > 0 ? "{$converted} ".Str::plural($fromTypeName, $converted).' converted to '.Str::plural($toTypeName, $converted).' successfully.' : '';

        if (! empty($errors)) {
            return back()->with('warning', $message ? $message.' However, some items could not be converted: '.implode(' ', $errors) : implode(' ', $errors));
        }

        return redirect()->route('admin.taxonomy.index')
            ->with('success', $message);
    }
}
