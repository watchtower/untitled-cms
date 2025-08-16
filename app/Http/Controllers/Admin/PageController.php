<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $query = Page::with(['creator', 'updater']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create');
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

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page created successfully.');
    }

    public function show(Page $page)
    {
        return view('admin.pages.show', compact('page'));
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
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
}
