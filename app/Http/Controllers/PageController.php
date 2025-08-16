<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return view('pages.show', compact('page'));
    }

    public function home()
    {
        // Try to find a page with slug 'home' or 'index'
        $page = Page::where('status', 'published')
            ->whereIn('slug', ['home', 'index'])
            ->first();

        if ($page) {
            return view('pages.show', compact('page'));
        }

        // If no home page exists, show a default welcome page
        return view('welcome');
    }
}
