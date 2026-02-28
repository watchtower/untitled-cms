<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;

class SitemapController extends Controller
{
    /**
     * Generate a Markdown sitemap for AI agents.
     */
    public function markdown()
    {
        $pages = Page::where('status', 'published')
            ->orderBy('updated_at', 'desc')
            ->cursor();

        $markdown = "# Sitemap for Agents\n\n";
        $markdown .= "This sitemap is optimized for AI agents and LLMs. All links support the `Accept: text/markdown` header for direct content extraction.\n\n";

        $markdown .= "## Pages\n\n";

        foreach ($pages as $page) {
            $url = route('public.page', $page->slug);
            $lastMod = $page->updated_at->format('Y-m-d');
            $markdown .= "- [{$page->title}]({$url}) - Last updated: {$lastMod}\n";
            if ($page->seo_description) {
                $markdown .= "  - *Description: {$page->seo_description}*\n";
            }
        }

        return response($markdown, 200)
            ->header('Content-Type', 'text/markdown; charset=utf-8')
            ->header('X-Robots-Tag', 'noindex, follow');
    }
}
