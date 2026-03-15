<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Support\Str;

class FeedController extends Controller
{
    /**
     * Generate an RSS feed.
     */
    public function rss()
    {
        $pages = Page::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->limit(20)
            ->get();

        $siteName = config('app.name', 'Untitled CMS');
        $siteUrl = url('/');
        $siteDesc = 'Latest updates from '.$siteName;

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<rss version=\"2.0\" xmlns:content=\"http://purl.org/rss/1.0/modules/content/\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n";
        $xml .= "  <channel>\n";
        $xml .= "    <title>{$siteName}</title>\n";
        $xml .= "    <link>{$siteUrl}</link>\n";
        $xml .= "    <description>{$siteDesc}</description>\n";
        $xml .= "    <language>en-us</language>\n";
        $xml .= '    <pubDate>'.now()->toRssString()."</pubDate>\n";
        $xml .= '    <atom:link href="'.url('/rss')."\" rel=\"self\" type=\"application/rss+xml\" />\n";

        foreach ($pages as $page) {
            $url = route('public.page', $page->slug);
            $pubDate = $page->published_at ? $page->published_at->toRssString() : $page->created_at->toRssString();
            $author = $page->author ? $page->author->name : 'Admin';

            $xml .= "    <item>\n";
            $xml .= "      <title><![CDATA[{$page->title}]]></title>\n";
            $xml .= "      <link>{$url}</link>\n";
            $xml .= "      <guid isPermaLink=\"false\">{$page->id}</guid>\n";
            $xml .= '      <description><![CDATA['.Str::limit(strip_tags($page->content), 300)."]]></description>\n";
            $xml .= "      <dc:creator>{$author}</dc:creator>\n";
            $xml .= "      <pubDate>{$pubDate}</pubDate>\n";
            $xml .= "    </item>\n";
        }

        $xml .= "  </channel>\n";
        $xml .= '</rss>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
