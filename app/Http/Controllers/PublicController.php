<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Page;
use Inertia\Inertia;

class PublicController extends Controller
{
    public function home()
    {
        $banners = Banner::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            })
            ->orderBy('order', 'asc')
            ->get();

        $recentPages = Page::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->take(6)
            ->get(['id', 'title', 'slug', 'seo_description', 'published_at', 'featured_images']);

        return Inertia::render('Public/Home', [
            'banners' => $banners,
            'recentPages' => $recentPages,
        ]);
    }

    public function show($slug)
    {
        $page = Page::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return Inertia::render('Public/Page', [
            'page' => $page,
        ]);
    }
}
