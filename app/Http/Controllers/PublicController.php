<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;
use League\HTMLToMarkdown\HtmlConverter;

class PublicController extends Controller
{
    public function home(Request $request)
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

        if ($request->prefers(['text/html', 'text/markdown']) === 'text/markdown') {
            $markdown = "# Welcome to " . config('app.name', 'CMS') . "\n\n";
            $markdown .= "## Featured\n\n";
            foreach ($banners as $banner) {
                $markdown .= "- **" . $banner->title . "**: " . $banner->description . "\n";
            }
            $markdown .= "\n## Recent Pages\n\n";
            foreach ($recentPages as $page) {
                $markdown .= "- [" . $page->title . "](/" . $page->slug . ") - " . $page->seo_description . "\n";
            }

            return response($markdown, 200)
                ->header('Content-Type', 'text/markdown; charset=utf-8')
                ->header('Content-Signal', 'ai-train=yes, search=yes, ai-input=yes')
                ->header('x-markdown-tokens', round(strlen($markdown) / 4));
        }

        return Inertia::render('Public/Home', [
            'banners' => $banners,
            'recentPages' => $recentPages,
        ]);
    }

    public function show(Request $request, $slug)
    {
        $page = Page::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        if ($request->prefers(['text/html', 'text/markdown']) === 'text/markdown') {
            $converter = new HtmlConverter();
            $markdownContent = $converter->convert($page->content ?? '');

            $frontmatter = "---\n";
            $title = str_replace('"', '\"', $page->seo_title ?: $page->title);
            $frontmatter .= "title: \"{$title}\"\n";
            if ($page->seo_description) {
                $description = str_replace('"', '\"', $page->seo_description);
                $frontmatter .= "description: \"{$description}\"\n";
            }
            if (!empty($page->featured_images)) {
                $frontmatter .= "image: " . url($page->featured_images[0]) . "\n";
            }
            $frontmatter .= "---\n\n";

            $markdown = $frontmatter . "# " . $page->title . "\n\n" . $markdownContent;

            return response($markdown, 200)
                ->header('Content-Type', 'text/markdown; charset=utf-8')
                ->header('Content-Signal', 'ai-train=yes, search=yes, ai-input=yes')
                ->header('x-markdown-tokens', round(strlen($markdown) / 4));
        }

        return Inertia::render('Public/Page', [
            'page' => $page,
        ]);
    }
}
