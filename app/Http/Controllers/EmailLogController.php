<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class EmailLogController extends Controller
{
    /**
     * Display a listing of email logs.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', EmailLog::class);

        $query = EmailLog::query()->orderBy('created_at', 'desc');

        // Simple filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('recipient', 'like', "%{$request->search}%")
                    ->orWhere('subject', 'like', "%{$request->search}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        $stats = Cache::remember('email_log_stats', 300, function () {
            /** @var \Traversable $cursor */
            $cursor = EmailLog::raw(function ($collection) {
                return $collection->aggregate([
                    ['$facet' => [
                        'total' => [['$count' => 'count']],
                        'delivered' => [
                            ['$match' => ['status' => 'delivered']],
                            ['$count' => 'count'],
                        ],
                        'opened' => [
                            ['$match' => ['opened_at' => ['$ne' => null]]],
                            ['$count' => 'count'],
                        ],
                        'bounced' => [
                            ['$match' => ['status' => 'bounced']],
                            ['$count' => 'count'],
                        ],
                    ]],
                ]);
            });

            $row = iterator_to_array($cursor)[0] ?? [];
            $total = $row['total'][0]['count'] ?? 0;
            $delivered = $row['delivered'][0]['count'] ?? 0;
            $opened = $row['opened'][0]['count'] ?? 0;
            $bounced = $row['bounced'][0]['count'] ?? 0;

            return [
                'total' => $total,
                'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 1) : 0,
                'open_rate' => $total > 0 ? round(($opened / $total) * 100, 1) : 0,
                'bounce_rate' => $total > 0 ? round(($bounced / $total) * 100, 1) : 0,
            ];
        });

        return Inertia::render('EmailLogs/Index', [
            'logs' => $logs,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }
}
