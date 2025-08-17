<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Category;
use App\Models\Page;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(Page::class, 'page');
    }

    public function index(Request $request)
    {
        $query = Page::with(['creator', 'updater', 'categories', 'tags']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category);
            });
        }

        // Filter by tag
        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tags.id', $request->tag);
            });
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $pages = $query->latest()->paginate(15);

        // Get categories and tags for filter dropdowns
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.pages.index', compact('pages', 'categories', 'tags'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.pages.create', compact('categories', 'tags'));
    }

    public function store(StorePageRequest $request)
    {
        $data = $request->validated();

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);

            // Ensure slug is unique
            $originalSlug = $data['slug'];
            $counter = 1;
            while (Page::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $originalSlug.'-'.$counter;
                $counter++;
            }
        }

        // Set creator
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        // Handle published_at
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $page = Page::create($data);

        // Assign categories
        if ($request->has('categories')) {
            $page->categories()->sync($request->categories);
        }

        // Handle tags
        if ($request->has('tag_ids')) {
            $tagData = json_decode($request->tag_ids, true);
            $tagIds = [];

            if ($tagData) {
                foreach ($tagData as $tagInfo) {
                    if ($tagInfo['isNew']) {
                        // Create new tag
                        $tag = Tag::create([
                            'name' => $tagInfo['name'],
                            'slug' => Str::slug($tagInfo['name']),
                        ]);
                        $tagIds[] = $tag->id;
                    } else {
                        // Use existing tag
                        $tagIds[] = $tagInfo['id'];
                    }
                }
            }

            $page->tags()->sync($tagIds);
        }

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page created successfully.');
    }

    public function show(Page $page)
    {
        return view('admin.pages.show', compact('page'));
    }

    public function edit(Page $page)
    {
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.pages.edit', compact('page', 'categories', 'tags'));
    }

    public function update(UpdatePageRequest $request, Page $page)
    {
        $data = $request->validated();

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);

            // Ensure slug is unique (excluding current page)
            $originalSlug = $data['slug'];
            $counter = 1;
            while (Page::where('slug', $data['slug'])->where('id', '!=', $page->id)->exists()) {
                $data['slug'] = $originalSlug.'-'.$counter;
                $counter++;
            }
        }

        // Set updater
        $data['updated_by'] = auth()->id();

        // Handle published_at
        if ($data['status'] === 'published' && $page->status === 'draft' && empty($data['published_at'])) {
            $data['published_at'] = now();
        } elseif ($data['status'] === 'draft') {
            $data['published_at'] = null;
        }

        $page->update($data);

        // Assign categories
        if ($request->has('categories')) {
            $page->categories()->sync($request->categories);
        } else {
            $page->categories()->sync([]);
        }

        // Handle tags
        if ($request->has('tag_ids')) {
            $tagData = json_decode($request->tag_ids, true);
            $tagIds = [];

            if ($tagData) {
                foreach ($tagData as $tagInfo) {
                    if ($tagInfo['isNew']) {
                        // Create new tag
                        $tag = Tag::create([
                            'name' => $tagInfo['name'],
                            'slug' => Str::slug($tagInfo['name']),
                        ]);
                        $tagIds[] = $tag->id;
                    } else {
                        // Use existing tag
                        $tagIds[] = $tagInfo['id'];
                    }
                }
            }

            $page->tags()->sync($tagIds);
        } else {
            $page->tags()->sync([]);
        }

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page updated successfully.');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page deleted successfully.');
    }

    public function duplicate(Page $page)
    {
        $newPage = $page->replicate();
        $newPage->title = $page->title.' (Copy)';
        $newPage->slug = $page->slug.'-copy';
        $newPage->status = 'draft';
        $newPage->published_at = null;
        $newPage->created_by = auth()->id();
        $newPage->updated_by = auth()->id();

        // Ensure unique slug
        $originalSlug = $newPage->slug;
        $counter = 1;
        while (Page::where('slug', $newPage->slug)->exists()) {
            $newPage->slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        $newPage->save();

        return redirect()->route('admin.pages.edit', $newPage)
            ->with('success', 'Page duplicated successfully.');
    }

    public function publish(Page $page)
    {
        $this->authorize('update', $page);

        $page->update([
            'status' => 'published',
            'published_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()
            ->with('success', 'Page published successfully.');
    }

    public function unpublish(Page $page)
    {
        $this->authorize('update', $page);

        $page->update([
            'status' => 'draft',
            'published_at' => null,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()
            ->with('success', 'Page unpublished successfully.');
    }

    public function preview(Page $page)
    {
        $this->authorize('view', $page);

        return view('pages.show', compact('page'));
    }
}
