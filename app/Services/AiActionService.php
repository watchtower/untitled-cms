<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Page;
use App\Models\Banner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AiActionService
{
    /**
     * Whitelist of supported AI actions.
     */
    protected array $whitelist = [
        'create_page',
        'update_page',
        'update_page_status',
        'create_banner',
        'update_banner',
        'update_banner_status',
    ];

    public function isSupported(string $action): bool
    {
        return in_array($action, $this->whitelist);
    }

    public function getWhitelist(): array
    {
        return $this->whitelist;
    }

    /**
     * Parse and resolve an action proposal from the AI.
     * Validates against whitelist and resolves real record IDs server-side.
     * Returns a structured proposal ready for the confirmation card.
     */
    public function resolveProposal(array $raw): array
    {
        $action = $raw['action'] ?? null;

        if (!$action || !$this->isSupported($action)) {
            throw new \Exception("Action \"{$action}\" is not supported. Supported: " . implode(', ', $this->whitelist));
        }

        return match ($action) {
            'create_page' => $this->resolveCreatePage($raw),
            'update_page' => $this->resolveUpdatePage($raw),
            'update_page_status' => $this->resolveUpdatePageStatus($raw),
            'create_banner' => $this->resolveCreateBanner($raw),
            'update_banner' => $this->resolveUpdateBanner($raw),
            'update_banner_status' => $this->resolveUpdateBannerStatus($raw),
            default => throw new \Exception("Unhandled action: {$action}"),
        };
    }

    /**
     * Execute a resolved action proposal. Saves before-state snapshot.
     */
    public function execute(array $proposal): array
    {
        $action = $proposal['action'];

        return match ($action) {
            'create_page' => $this->executeCreatePage($proposal),
            'update_page' => $this->executeUpdatePage($proposal),
            'update_page_status' => $this->executeUpdatePageStatus($proposal),
            'create_banner' => $this->executeCreateBanner($proposal),
            'update_banner' => $this->executeUpdateBanner($proposal),
            'update_banner_status' => $this->executeUpdateBannerStatus($proposal),
            default => throw new \Exception("Unhandled action: {$action}"),
        };
    }

    /**
     * Revert an AI action using the before_state snapshot in ActivityLog.
     */
    public function revert(string $logId): array
    {
        $log = ActivityLog::where('is_ai_action', true)->findOrFail($logId);

        $subjectType = $log->subject_type;
        $subjectId = $log->subject_id;
        $beforeState = $log->before_state;

        $model = $subjectType::find($subjectId);
        if (!$model) {
            throw new \Exception('Original record no longer exists (may have already been deleted).');
        }

        if ($beforeState) {
            // UPDATE action → restore previous field values
            $model->fill($beforeState)->save();
            ActivityLogger::log('reverted', "Reverted AI update: {$log->description}", $model);
            $actionType = 'reverted';
        } else {
            // CREATE action → soft-delete = undo the create
            $model->delete();
            ActivityLogger::log('ai_undone', "Undid AI create: {$log->description}", $model);
            $actionType = 'deleted';
        }

        return [
            'restored' => true,
            'action_type' => $actionType,
            'subject' => class_basename($subjectType),
            'id' => $subjectId,
        ];
    }

    // ─── Page Resolvers ────────────────────────────────────────────────────────

    private function resolveCreatePage(array $raw): array
    {
        return [
            'action' => 'create_page',
            'description' => 'Create a new page',
            'params' => [
                'title' => $raw['params']['title'] ?? null,
                'content' => $raw['params']['content'] ?? '',
                'status' => 'draft',
            ],
        ];
    }

    private function resolveUpdatePage(array $raw): array
    {
        $page = $this->resolvePageByTitle($raw['params']['title'] ?? null);

        return [
            'action' => 'update_page',
            'description' => "Update page \"{$page->title}\"",
            'resolved_id' => (string) $page->id,
            'resolved_title' => $page->title,
            'needs_restore' => $page->trashed(),
            'params' => array_filter([
                'title' => $raw['params']['new_title'] ?? null,
                'seo_title' => $raw['params']['seo_title'] ?? null,
                'seo_description' => $raw['params']['seo_description'] ?? null,
                'content' => $raw['params']['content'] ?? null,
            ]),
        ];
    }

    private function resolveUpdatePageStatus(array $raw): array
    {
        $page = $this->resolvePageByTitle($raw['params']['title'] ?? null);
        $status = $raw['params']['status'] ?? 'draft';

        return [
            'action' => 'update_page_status',
            'description' => "Set page \"{$page->title}\" status to {$status}",
            'resolved_id' => (string) $page->id,
            'resolved_title' => $page->title,
            'needs_restore' => $page->trashed(),
            'params' => ['status' => $status],
        ];
    }

    private function resolvePageByTitle(?string $title): Page
    {
        if (!$title) {
            throw new \Exception('No page title provided.');
        }

        // 1. Exact or like match (Active)
        $page = Page::where('title', 'like', "%{$title}%")->first();

        // 2. Exact or like match (Trashed/Soft-deleted)
        if (!$page) {
            $page = Page::onlyTrashed()->where('title', 'like', "%{$title}%")->first();
        }

        // 3. Fuzzy match (Active) using longer words
        if (!$page) {
            $words = array_filter(explode(' ', $title), fn($w) => strlen($w) > 3);
            if (!empty($words)) {
                $query = Page::query();
                foreach ($words as $word) {
                    $query->orWhere('title', 'like', "%{$word}%");
                }
                $page = $query->first();
            }
        }

        if (!$page) {
            throw new \Exception("No page found matching \"{$title}\". If this is a new page, say 'create a page called {$title}'.");
        }

        return $page;
    }

    // ─── Page Executors ────────────────────────────────────────────────────────

    private function executeCreatePage(array $proposal): array
    {
        $params = $proposal['params'];
        $page = Page::create([
            'title' => $params['title'],
            'slug' => Str::slug($params['title']),
            'content' => clean($params['content'] ?? ''), // Sanitize AI content (A03)
            'status' => 'draft',
        ]);

        ActivityLogger::log('ai_created', "AI created page: {$page->title}", $page, null, true);

        return ['subject' => 'Page', 'id' => (string) $page->id, 'title' => $page->title, 'url' => route('pages.edit', $page->id)];
    }

    private function executeUpdatePage(array $proposal): array
    {
        $page = Page::withTrashed()->findOrFail($proposal['resolved_id']);
        if ($page->trashed()) {
            $page->restore();
        }

        $beforeState = $page->only(['title', 'content', 'seo_title', 'seo_description', 'status']);
        // Sanitize any AI-generated content fields before persisting (A03)
        $params = $proposal['params'];
        if (isset($params['content'])) {
            $params['content'] = clean($params['content']);
        }
        $page->fill(array_filter($params))->save();

        ActivityLogger::log('ai_updated', "AI updated page: {$page->title}", $page, $beforeState, true);

        return ['subject' => 'Page', 'id' => (string) $page->id, 'title' => $page->title, 'url' => route('pages.edit', $page->id)];
    }

    private function executeUpdatePageStatus(array $proposal): array
    {
        $page = Page::withTrashed()->findOrFail($proposal['resolved_id']);
        if ($page->trashed()) {
            $page->restore();
        }

        $beforeState = $page->only(['status']);
        $page->update(['status' => $proposal['params']['status']]);

        ActivityLogger::log('ai_updated', "AI set page \"{$page->title}\" to {$proposal['params']['status']}", $page, $beforeState, true);

        return ['subject' => 'Page', 'id' => (string) $page->id, 'title' => $page->title, 'status' => $page->status, 'url' => route('pages.edit', $page->id)];
    }

    // ─── Banner Resolvers ──────────────────────────────────────────────────────

    private function resolveCreateBanner(array $raw): array
    {
        return [
            'action' => 'create_banner',
            'description' => 'Create a new banner',
            'params' => [
                'title' => $raw['params']['title'] ?? null,
                'status' => 'inactive',
            ],
        ];
    }

    private function resolveUpdateBanner(array $raw): array
    {
        $banner = $this->resolveBannerByTitle($raw['params']['title'] ?? null);

        return [
            'action' => 'update_banner',
            'description' => "Update banner \"{$banner->title}\"",
            'resolved_id' => (string) $banner->id,
            'resolved_title' => $banner->title,
            'needs_restore' => $banner->trashed(),
            'params' => array_filter([
                'title' => $raw['params']['new_title'] ?? null,
                'content' => $raw['params']['content'] ?? null,
            ]),
        ];
    }

    private function resolveUpdateBannerStatus(array $raw): array
    {
        $banner = $this->resolveBannerByTitle($raw['params']['title'] ?? null);
        $status = $raw['params']['status'] ?? 'inactive';

        return [
            'action' => 'update_banner_status',
            'description' => "Set banner \"{$banner->title}\" to {$status}",
            'resolved_id' => (string) $banner->id,
            'resolved_title' => $banner->title,
            'needs_restore' => $banner->trashed(),
            'params' => ['status' => $status],
        ];
    }

    private function resolveBannerByTitle(?string $title): Banner
    {
        if (!$title) {
            throw new \Exception('No banner title provided.');
        }

        // 1. Active match
        $banner = Banner::where('title', 'like', "%{$title}%")->first();

        // 2. Trashed match
        if (!$banner) {
            $banner = Banner::onlyTrashed()->where('title', 'like', "%{$title}%")->first();
        }

        // 3. Fuzzy match
        if (!$banner) {
            $words = array_filter(explode(' ', $title), fn($w) => strlen($w) > 3);
            if (!empty($words)) {
                $query = Banner::query();
                foreach ($words as $word) {
                    $query->orWhere('title', 'like', "%{$word}%");
                }
                $banner = $query->first();
            }
        }

        if (!$banner) {
            throw new \Exception("No banner found matching \"{$title}\". If this is a new banner, say 'create a banner called {$title}'.");
        }

        return $banner;
    }

    // ─── Banner Executors ──────────────────────────────────────────────────────

    private function executeCreateBanner(array $proposal): array
    {
        $params = $proposal['params'];
        $banner = Banner::create([
            'title' => $params['title'],
            'status' => 'inactive',
        ]);

        ActivityLogger::log('ai_created', "AI created banner: {$banner->title}", $banner, null, true);

        return ['subject' => 'Banner', 'id' => (string) $banner->id, 'title' => $banner->title, 'url' => route('banners.edit', $banner->id)];
    }

    private function executeUpdateBanner(array $proposal): array
    {
        $banner = Banner::withTrashed()->findOrFail($proposal['resolved_id']);
        if ($banner->trashed()) {
            $banner->restore();
        }

        $beforeState = $banner->only(['title', 'content', 'status']);
        // Sanitize any AI-generated content fields before persisting (A03)
        $params = $proposal['params'];
        if (isset($params['content'])) {
            $params['content'] = clean($params['content']);
        }
        $banner->fill(array_filter($params))->save();

        ActivityLogger::log('ai_updated', "AI updated banner: {$banner->title}", $banner, $beforeState, true);

        return ['subject' => 'Banner', 'id' => (string) $banner->id, 'title' => $banner->title, 'url' => route('banners.edit', $banner->id)];
    }

    private function executeUpdateBannerStatus(array $proposal): array
    {
        $banner = Banner::withTrashed()->findOrFail($proposal['resolved_id']);
        if ($banner->trashed()) {
            $banner->restore();
        }

        $beforeState = $banner->only(['status']);
        $banner->update(['status' => $proposal['params']['status']]);

        ActivityLogger::log('ai_updated', "AI set banner \"{$banner->title}\" to {$proposal['params']['status']}", $banner, $beforeState, true);

        return ['subject' => 'Banner', 'id' => (string) $banner->id, 'title' => $banner->title, 'status' => $banner->status, 'url' => route('banners.edit', $banner->id)];
    }
}
