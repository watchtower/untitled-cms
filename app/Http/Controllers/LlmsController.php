<?php

namespace App\Http\Controllers;

use App\Models\Page;
use League\HTMLToMarkdown\HtmlConverter;

class LlmsController extends Controller
{
    /**
     * /llms.txt — AI-discoverability index following the llmstxt.org standard.
     *
     * Returns a plain-text Markdown document listing all published pages with
     * their URLs and descriptions, suitable for ingestion by LLMs and AI agents.
     */
    public function index()
    {
        $pages = Page::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->cursor();

        $appName = config('app.name', 'CMS');
        $appUrl = rtrim(config('app.url'), '/');
        $tagline = config('app.description', 'A content management system.');

        $lines = [];
        $lines[] = "# {$appName}";
        $lines[] = '';
        $lines[] = "> {$tagline}";
        $lines[] = '';
        $lines[] = 'This site is powered by Untitled CMS. All pages support `Accept: text/markdown`';
        $lines[] = 'for direct content extraction by AI agents and coding assistants.';
        $lines[] = '';
        $lines[] = '## Discover';
        $lines[] = '';
        $lines[] = "- [Sitemap for Agents]({$appUrl}/sitemap.md): Full list of all published pages with descriptions";
        $lines[] = "- [RSS Feed]({$appUrl}/rss): Latest content updates in RSS format";
        $lines[] = "- [Full LLM Content]({$appUrl}/llms-full.txt): All pages as plain Markdown for LLM ingestion";
        $lines[] = '';
        $lines[] = '## Pages';
        $lines[] = '';

        foreach ($pages as $page) {
            $url = route('public.page', $page->slug);
            $desc = $page->seo_description ? ": {$page->seo_description}" : '';
            $lines[] = "- [{$page->title}]({$url}){$desc}";
        }

        $text = implode("\n", $lines)."\n";

        return response($text, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('X-Robots-Tag', 'noindex, follow')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * /llms-full.txt — Full content dump for LLM ingestion.
     *
     * Returns all published page content converted to plain Markdown.
     * Suitable for building RAG pipelines or bulk LLM ingestion.
     */
    public function full()
    {
        $pages = Page::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->cursor();

        $appName = config('app.name', 'CMS');
        $appUrl = rtrim(config('app.url'), '/');

        $converter = new HtmlConverter(['strip_tags' => true]);

        $lines = [];
        $lines[] = "# {$appName} — Full Content";
        $lines[] = '';
        $lines[] = 'All published pages in plain Markdown. Optimized for LLM ingestion.';
        $lines[] = "Source: {$appUrl}";
        $lines[] = '';
        $lines[] = '---';
        $lines[] = '';

        foreach ($pages as $page) {
            $url = route('public.page', $page->slug);

            $lines[] = "## {$page->title}";
            $lines[] = '';
            $lines[] = "URL: {$url}";

            if ($page->seo_description) {
                $lines[] = "Description: {$page->seo_description}";
            }

            if ($page->published_at) {
                $lines[] = 'Published: '.$page->published_at->format('Y-m-d');
            }

            $lines[] = '';
            $lines[] = $converter->convert($page->content ?? '');
            $lines[] = '';
            $lines[] = '---';
            $lines[] = '';
        }

        $text = implode("\n", $lines);
        $tokens = (int) round(strlen($text) / 4);

        return response($text, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('X-Robots-Tag', 'noindex, follow')
            ->header('x-llms-tokens', $tokens)
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
