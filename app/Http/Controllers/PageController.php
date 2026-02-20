<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Redirect as PageRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $this->authorize('viewAny', Page::class);

        $pages = Page::orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('Pages/Index', [
            'pages' => $pages,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // $this->authorize('create', Page::class);

        return Inertia::render('Pages/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // $this->authorize('create', Page::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:160',
            'featured_image' => 'nullable|string',
            'featured_images' => 'nullable|array',
        ]);

        if (! empty($validated['slug'])) {
            $slug = Str::slug($validated['slug']);
        } else {
            $slug = Str::slug($validated['title']);
        }

        // Ensure unique slug (double check if unique validation handles it, but manual collision handling is good too)
        $count = Page::where('slug', $slug)->count();
        if ($count > 0) {
            $slug .= '-'.($count + 1);
        }

        $slug = Str::slug($validated['title']).($count > 0 ? '-'.($count + 1) : '');

        // Sanitize content
        $validated['content'] = clean($validated['content']);

        $page = Page::create([
            ...$validated,
            'slug' => $slug,
            'author_id' => auth()->id(),
            'published_at' => $validated['status'] === 'published' ? now() : null,
        ]);

        \App\Services\ActivityLogger::log('create', "Created page: {$page->title}", $page);

        return redirect()->route('pages.index')->with('success', 'Page created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Preview or public view logic
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $page = Page::findOrFail($id);
        // $this->authorize('update', $page);

        // Get redirects pointing to this page's current slug
        $redirects = PageRedirect::where('to_path', $page->slug)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Pages/Edit', [
            'page' => $page,
            'redirects' => $redirects,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $page = Page::findOrFail($id);
        // $this->authorize('update', $page);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug,'.$id,
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:160',
            'featured_image' => 'nullable|string',
            'featured_images' => 'nullable|array',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        } else {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        if (isset($validated['status']) && $validated['status'] === 'published' && $page->status !== 'published') {
            $validated['published_at'] = now();
        }

        // Check for slug change and create redirect
        if ($page->slug !== $validated['slug']) {
            // Create a redirect from the old slug to the new slug
            PageRedirect::create([
                'from_path' => $page->slug,
                'to_path' => $validated['slug'],
                'type' => 301,
                'active' => true,
            ]);

            // Update any existing redirects that pointed to the old slug to point to the new slug
            // (Avoid daisy-chaining)
            PageRedirect::where('to_path', $page->slug)->update([
                'to_path' => $validated['slug'],
            ]);
        }

        $validated['content'] = clean($validated['content']);

        $page->update($validated);

        \App\Services\ActivityLogger::log('update', "Updated page: {$page->title}", $page);

        if ($request->has('stay')) {
            return redirect()->back()->with('success', 'Page status updated successfully.');
        }

        return redirect()->route('pages.index')->with('success', 'Page updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        // $this->authorize('delete', $page);

        $title = $page->title;
        $page->delete();

        \App\Services\ActivityLogger::log('delete', "Deleted page: {$title}", $page);

        return redirect()->route('pages.index')->with('success', 'Page deleted successfully.');
    }
}
