<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\EmailLog;
use App\Models\Page;
use App\Models\User;
use App\Models\VaultFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AiContextService
{
    /**
     * Return a lightweight summary for the given module slug.
     * Called before each chat response to inject live data into the system prompt.
     */
    public function getModuleContext(string $module): array
    {
        return match ($module) {
            'pages' => $this->pagesContext(),
            'banners' => $this->bannersContext(),
            'vault' => $this->vaultContext(),
            'users' => $this->usersContext(),
            'email-logs' => $this->emailContext(),
            default => [],
        };
    }

    private function emailContext(): array
    {
        return ['module' => 'email', 'stats' => $this->emailStats()];
    }

    private function emailStats(): array
    {
        return Cache::remember('ai_context_email_stats', 60, function () {
            $total = EmailLog::count();
            $delivered = EmailLog::where('status', 'delivered')->count();
            $opened = EmailLog::whereNotNull('opened_at')->count();
            $bounced = EmailLog::where('status', 'bounced')->count();

            return [
                'total' => $total,
                'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 1) : 0,
                'open_rate' => $total > 0 ? round(($opened / $total) * 100, 1) : 0,
                'bounce_rate' => $total > 0 ? round(($bounced / $total) * 100, 1) : 0,
            ];
        });
    }

    private function pagesContext(): array
    {
        $recent = Page::latest()->limit(5)->get(['id', 'title', 'status', 'slug']);

        return [
            'module' => 'pages',
            'stats' => Cache::remember('ai_context_pages_stats', 60, fn () => [
                'total' => Page::count(),
                'published' => Page::where('status', 'published')->count(),
                'draft' => Page::where('status', 'draft')->count(),
            ]),
            'recent' => $this->formatRecentModels($recent, ['title', 'status', 'slug']),
        ];
    }

    private function bannersContext(): array
    {
        $recent = Banner::latest()->limit(5)->get(['id', 'title', 'status']);

        return [
            'module' => 'banners',
            'stats' => Cache::remember('ai_context_banners_stats', 60, fn () => [
                'total' => Banner::count(),
                'active' => Banner::where('status', 'active')->count(),
                'inactive' => Banner::where('status', 'inactive')->count(),
            ]),
            'recent' => $this->formatRecentModels($recent, ['title', 'status']),
        ];
    }

    private function vaultContext(): array
    {
        return [
            'module' => 'vault',
            'stats' => Cache::remember('ai_context_vault_stats', 60, fn () => [
                'total_files' => VaultFile::count(),
                'images' => VaultFile::where('mime_type', 'like', 'image/%')->count(),
                'documents' => VaultFile::where('mime_type', 'not like', 'image/%')->count(),
            ]),
        ];
    }

    private function usersContext(): array
    {
        return [
            'module' => 'users',
            'stats' => Cache::remember('ai_context_users_stats', 60, fn () => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'inactive' => User::where('is_active', false)->count(),
            ]),
        ];
    }

    private function formatRecentModels(Collection $models, array $fields): array
    {
        return $models->map(function ($model) use ($fields) {
            $data = ['id' => (string) $model->id];
            foreach ($fields as $field) {
                $data[$field] = $model->{$field};
            }

            return $data;
        })->toArray();
    }

    /**
     * Detect which module a URL belongs to.
     */
    public function detectModule(string $url): string
    {
        $segments = [
            '/pages' => 'pages',
            '/banners' => 'banners',
            '/vault' => 'vault',
            '/users' => 'users',
            '/roles' => 'roles',
            '/email-logs' => 'email-logs',
        ];

        foreach ($segments as $prefix => $module) {
            if (str_contains($url, $prefix)) {
                return $module;
            }
        }

        return 'general';
    }

    /**
     * Build a CMS context string injected into every AI system prompt.
     * Always includes full stats so AI gives accurate answers from any page.
     */
    public function buildContextString(string $url): string
    {
        try {
            $pages = $this->pagesContext()['stats'];
            $banners = $this->bannersContext()['stats'];
            $email = $this->emailStats();

            $module = $this->detectModule($url);
            $moduleLine = $module !== 'general'
                ? "The admin is currently on the **{$module}** module."
                : 'The admin is on the CMS dashboard.';

            return "{$moduleLine}\n\nCurrent CMS data (accurate — use these exact numbers):\n- Pages: {$pages['total']} total, {$pages['published']} published, {$pages['draft']} draft\n- Banners: {$banners['total']} total, {$banners['active']} active\n- Email health: {$email['delivery_rate']}% delivery, {$email['open_rate']}% open rate, {$email['bounce_rate']}% bounce rate.";

        } catch (\Exception $e) {
            return 'The admin is on the CMS admin panel.';
        }
    }
}
