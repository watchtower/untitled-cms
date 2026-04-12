<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use Carbon\Carbon;
use Inertia\Inertia;
use MongoDB\BSON\UTCDateTime;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        $startDate = Carbon::today()->subDays(6);

        // Optimized single-trip aggregation
        /** @var \Traversable $statsCursor */
        $statsCursor = EmailLog::raw(function ($collection) use ($startDate) {
            return $collection->aggregate([
                ['$match' => ['created_at' => ['$gte' => new UTCDateTime($startDate->getTimestampMs())]]],
                ['$group' => [
                    '_id' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$created_at']],
                    'sent' => ['$sum' => 1],
                    'delivered' => ['$sum' => ['$cond' => [['$ne' => ['$delivered_at', null]], 1, 0]]],
                ]],
                ['$sort' => ['_id' => 1]],
            ]);
        });

        $stats = iterator_to_array($statsCursor);

        // Map into the format expected by Recharts
        $statsMap = collect($stats)->keyBy('_id');

        $emailHealth = collect(range(6, 0))->map(function ($i) use ($statsMap) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $dayData = $statsMap->get($dateStr);

            return [
                'name' => $date->format('D'),
                'sent' => (int) ($dayData['sent'] ?? 0),
                'delivered' => (int) ($dayData['delivered'] ?? 0),
            ];
        });

        return Inertia::render('Dashboard', [
            'emailHealth' => $emailHealth,
        ]);
    }
}
