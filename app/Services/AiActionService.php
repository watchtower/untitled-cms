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
     * Model classes that are eligible for AI action revert.
     * Prevents arbitrary class invocation if the activity_log record is tampered with.
     */
    private const REVERTABLE_MODELS = [
        \App\Models\Page::class,
        \App\Models\Banner::class,
    ];

    /**
     * Revert an AI action using the before_state snapshot in ActivityLog.
     */
    public function revert(string $logId): array
    {
        $log = ActivityLog::where('is_ai_action', true)->findOrFail($logId);

        $subjectType = $log->subject_type;
        $subjectId = $log->subject_id;
        $beforeState = $log->before_state;

        // Allowlist to prevent arbitrary static method invocation via a tampered log record
        if (!in_array($subjectType, self::REVERTABLE_MODELS, true)) {
            throw new \Exception("Unsupported model type for revert: {$subjectType}");
        }

        $model = $subjectType::find($subjectId);
        if (!$model) {
            throw new \Exception('Original record no longer exists (may have already been deleted).');
        }

        $actionType = $beforeState
            ? $this->applyRevertUpdate($model, $beforeState, $log)
            : $this->applyRevertCreate($model, $log);

        return [
            'restored' => true,
            'action_type' => $actionType,
            'subject' => class_basename($subjectType),
            'id' => $subjectId,
        ];
    }

    private function applyRevertUpdate(\Illuminate\Database\Eloquent\Model $model, array $beforeState, ActivityLog $log): string
    {
        $model->fill($beforeState)->save();
        ActivityLogger::log('reverted', "Reverted AI update: {$log->description}", $model);
        return 'reverted';
    }

    private function applyRevertCreate(\Illuminate\Database\Eloquent\Model $model, ActivityLog $log): string
    {
        $model->delete();
        ActivityLogger::log('ai_undone', "Undid AI create: {$log->description}", $model);
        return 'deleted';
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
        /** @var Page $page */
        $page = $this->resolveModelByTitle(Page::class, $title, 'page');
        return $page;
    }

    // ─── Page Executors ────────────────────────────────────────────────────────

    private function executeCreatePage(array $proposal): array
    {
        $params = $proposal['params'];

        if (empty($params['title'])) {
            throw new \InvalidArgumentException('Page title is required to create a page.');
        }

        $page = Page::create([
            'title' => $params['title'],
            'slug'  => Page::uniqueSlug(Str::slug($params['title'])),
            'content' => clean($params['content'] ?? ''), // Sanitize AI content (A03)
            'status' => 'draft',
        ]);

        ActivityLogger::log('ai_created', "AI created page: {$page->title}", $page, null, true);

        return ['subject' => 'Page', 'id' => (string) $page->id, 'title' => $page->title, 'url' => route('admin.pages.edit', $page->id)];
    }

    private function executeUpdatePage(array $proposal): array
    {
        /** @var Page $page */
        $page = $this->findAndRestoreIfTrashed(Page::class, $proposal['resolved_id']);

        $beforeState = $page->only(['title', 'content', 'seo_title', 'seo_description', 'status']);
        // Sanitize any AI-generated content fields before persisting (A03)
        $params = $proposal['params'];
        if (isset($params['content'])) {
            $params['content'] = clean($params['content']);
        }
        $page->fill(array_filter($params))->save();

        ActivityLogger::log('ai_updated', "AI updated page: {$page->title}", $page, $beforeState, true);

        return ['subject' => 'Page', 'id' => (string) $page->id, 'title' => $page->title, 'url' => route('admin.pages.edit', $page->id)];
    }

    private function executeUpdatePageStatus(array $proposal): array
    {
        /** @var Page $page */
        $page = $this->findAndRestoreIfTrashed(Page::class, $proposal['resolved_id']);

        $beforeState = $page->only(['status']);
        $page->update(['status' => $proposal['params']['status']]);

        ActivityLogger::log('ai_updated', "AI set page \"{$page->title}\" to {$proposal['params']['status']}", $page, $beforeState, true);

        return ['subject' => 'Page', 'id' => (string) $page->id, 'title' => $page->title, 'status' => $page->status, 'url' => route('admin.pages.edit', $page->id)];
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
        /** @var Banner $banner */
        $banner = $this->resolveModelByTitle(Banner::class, $title, 'banner');
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

        return ['subject' => 'Banner', 'id' => (string) $banner->id, 'title' => $banner->title, 'url' => route('admin.banners.edit', $banner->id)];
    }

    private function executeUpdateBanner(array $proposal): array
    {
        /** @var Banner $banner */
        $banner = $this->findAndRestoreIfTrashed(Banner::class, $proposal['resolved_id']);

        $beforeState = $banner->only(['title', 'content', 'status']);
        // Sanitize any AI-generated content fields before persisting (A03)
        $params = $proposal['params'];
        if (isset($params['content'])) {
            $params['content'] = clean($params['content']);
        }
        $banner->fill(array_filter($params))->save();

        ActivityLogger::log('ai_updated', "AI updated banner: {$banner->title}", $banner, $beforeState, true);

        return ['subject' => 'Banner', 'id' => (string) $banner->id, 'title' => $banner->title, 'url' => route('admin.banners.edit', $banner->id)];
    }

    private function executeUpdateBannerStatus(array $proposal): array
    {
        /** @var Banner $banner */
        $banner = $this->findAndRestoreIfTrashed(Banner::class, $proposal['resolved_id']);

        $beforeState = $banner->only(['status']);
        $banner->update(['status' => $proposal['params']['status']]);

        ActivityLogger::log('ai_updated', "AI set banner \"{$banner->title}\" to {$proposal['params']['status']}", $banner, $beforeState, true);

        return ['subject' => 'Banner', 'id' => (string) $banner->id, 'title' => $banner->title, 'status' => $banner->status, 'url' => route('admin.banners.edit', $banner->id)];
    }

    // ─── Shared Database Helpers ───────────────────────────────────────────────

    private function findAndRestoreIfTrashed(string $modelClass, string $id): \Illuminate\Database\Eloquent\Model
    {
        $model = $modelClass::withTrashed()->findOrFail($id);
        if (method_exists($model, 'trashed') && $model->trashed()) {
            $model->restore();
        }
        return $model;
    }

    private function resolveModelByTitle(string $modelClass, ?string $title, string $entityName): \Illuminate\Database\Eloquent\Model
    {
        if (!$title) {
            throw new \Exception("No {$entityName} title provided.");
        }

        $model = $modelClass::where('title', 'like', "%{$title}%")->first();

        if (!$model) {
            $model = $modelClass::onlyTrashed()->where('title', 'like', "%{$title}%")->first();
        }

        if (!$model) {
            $words = array_filter(explode(' ', $title), fn($w) => strlen($w) > 3);
            if (!empty($words)) {
                $query = $modelClass::query();
                foreach ($words as $word) {
                    $query->orWhere('title', 'like', "%{$word}%");
                }
                $model = $query->first();
            }
        }

        if (!$model) {
            throw new \Exception("No {$entityName} found matching \"{$title}\". If this is a new {$entityName}, say 'create a {$entityName} called {$title}'.");
        }

        return $model;
    }
}
