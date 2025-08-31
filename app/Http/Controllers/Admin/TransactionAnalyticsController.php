<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\InfluxDBService;
use Illuminate\Http\Request;

class TransactionAnalyticsController extends Controller
{
    private $influxService;

    public function __construct(InfluxDBService $influxService)
    {
        $this->middleware('auth');
        $this->influxService = $influxService;
    }

    /**
     * Show token transaction analytics
     */
    public function tokenTransactions(Request $request)
    {
        $validated = $request->validate([
            'range' => 'string|in:-1h,-24h,-7d,-30d,-90d',
            'user_id' => 'nullable|integer|exists:users,id',
            'limit' => 'integer|min:1|max:1000'
        ]);
        
        $timeRange = $validated['range'] ?? '-7d';
        $userId = $validated['user_id'] ?? null;
        $limit = $validated['limit'] ?? 100;

        // Get transaction data from InfluxDB
        $transactions = $this->influxService->queryTokenTransactions($userId, $timeRange, null, $limit);

        // Get transaction statistics
        $stats = $this->influxService->getTransactionStats('token', $timeRange);

        // Format data for display
        $formattedTransactions = $this->formatTokenTransactions($transactions);

        // Get user list for filtering
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return view('admin.analytics.token-transactions', compact(
            'formattedTransactions',
            'stats',
            'timeRange',
            'userId',
            'limit',
            'users'
        ));
    }

    /**
     * Show counter transaction analytics
     */
    public function counterTransactions(Request $request)
    {
        $validated = $request->validate([
            'range' => 'string|in:-1h,-24h,-7d,-30d,-90d',
            'user_id' => 'nullable|integer|exists:users,id',
            'limit' => 'integer|min:1|max:1000'
        ]);
        
        $timeRange = $validated['range'] ?? '-7d';
        $userId = $validated['user_id'] ?? null;
        $limit = $validated['limit'] ?? 100;

        // Get transaction data from InfluxDB
        $transactions = $this->influxService->queryCounterTransactions($userId, $timeRange, null, $limit);

        // Get transaction statistics
        $stats = $this->influxService->getTransactionStats('counter', $timeRange);

        // Format data for display
        $formattedTransactions = $this->formatCounterTransactions($transactions);

        // Get user list for filtering
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return view('admin.analytics.counter-transactions', compact(
            'formattedTransactions',
            'stats',
            'timeRange',
            'userId',
            'limit',
            'users'
        ));
    }

    /**
     * Show transaction dashboard with overview
     */
    public function dashboard(Request $request)
    {
        $validated = $request->validate([
            'range' => 'string|in:-1h,-24h,-7d,-30d,-90d',
        ]);
        
        $timeRange = $validated['range'] ?? '-7d';

        // Get overall statistics
        $stats = $this->influxService->getTransactionStats('both', $timeRange);

        // Get recent transactions from both types
        // Try InfluxDB first, fall back to MySQL if needed
        if ($this->influxService->isAvailable()) {
            $recentTokenTransactions = $this->formatTokenTransactions(
                $this->influxService->queryTokenTransactions(null, $timeRange, null, 10)
            );

            $recentCounterTransactions = $this->formatCounterTransactions(
                $this->influxService->queryCounterTransactions(null, $timeRange, null, 10)
            );
        } else {
            // Fallback to MySQL data
            $recentTokenTransactions = $this->getTokenTransactionsFromMySQL($timeRange, 10);
            $recentCounterTransactions = $this->getCounterTransactionsFromMySQL($timeRange, 10);
        }

        return view('admin.analytics.dashboard', compact(
            'stats',
            'recentTokenTransactions',
            'recentCounterTransactions',
            'timeRange'
        ))->with([
            'isInfluxConnected' => $this->influxService->isAvailable(),
            'isInfluxEnabled' => $this->influxService->isEnabled(),
            'isHybridMode' => $this->influxService->isHybridMode()
        ]);
    }

    /**
     * Get token transactions from MySQL as fallback
     */
    private function getTokenTransactionsFromMySQL(string $timeRange, int $limit): array
    {
        $query = \App\Models\TokenTransaction::with(['user', 'admin', 'token'])
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        // Apply time range filter
        if ($timeRange !== '-1h') {
            $hours = (int) str_replace(['-', 'h', 'd'], '', $timeRange);
            if (str_contains($timeRange, 'd')) {
                $hours *= 24;
            }
            $query->where('created_at', '>=', now()->subHours($hours));
        }

        return $query->get()->map(function ($transaction) {
            return [
                'time' => $transaction->created_at?->toISOString(),
                'user_id' => $transaction->user_id,
                'admin_id' => $transaction->admin_id,
                'token_slug' => $transaction->token?->slug ?? 'unknown',
                'amount' => $transaction->amount,
                'balance_before' => $transaction->balance_before,
                'balance_after' => $transaction->balance_after,
                'reason' => $transaction->reason,
                'type' => $transaction->type,
                'user_name' => $transaction->user?->name ?? 'Unknown',
                'admin_name' => $transaction->admin?->name ?? 'System',
            ];
        })->toArray();
    }

    /**
     * Get counter transactions from MySQL as fallback
     */
    private function getCounterTransactionsFromMySQL(string $timeRange, int $limit): array
    {
        $query = \App\Models\CounterTransaction::with(['user', 'admin', 'counterType'])
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        // Apply time range filter
        if ($timeRange !== '-1h') {
            $hours = (int) str_replace(['-', 'h', 'd'], '', $timeRange);
            if (str_contains($timeRange, 'd')) {
                $hours *= 24;
            }
            $query->where('created_at', '>=', now()->subHours($hours));
        }

        return $query->get()->map(function ($transaction) {
            return [
                'time' => $transaction->created_at?->toISOString(),
                'user_id' => $transaction->user_id,
                'admin_id' => $transaction->admin_id,
                'counter_slug' => $transaction->counterType?->slug ?? 'unknown',
                'count_change' => $transaction->count_change,
                'count_before' => $transaction->count_before,
                'count_after' => $transaction->count_after,
                'reason' => $transaction->reason,
                'type' => $transaction->type,
                'user_name' => $transaction->user?->name ?? 'Unknown',
                'admin_name' => $transaction->admin?->name ?? 'System',
            ];
        })->toArray();
    }

    /**
     * Format token transactions for display
     */
    private function formatTokenTransactions(array $transactions): array
    {
        return array_map(function ($transaction) {
            return [
                'time' => $transaction['_time'] ?? null,
                'user_id' => $transaction['user_id'] ?? null,
                'admin_id' => $transaction['admin_id'] ?? null,
                'token_slug' => $transaction['token_slug'] ?? 'unknown',
                'amount' => (int) ($transaction['amount'] ?? 0),
                'balance_before' => (int) ($transaction['balance_before'] ?? 0),
                'balance_after' => (int) ($transaction['balance_after'] ?? 0),
                'reason' => $transaction['reason'] ?? '',
                'type' => $transaction['type'] ?? 'unknown',
                'reason_category' => $transaction['reason_category'] ?? 'other',
                'is_admin_action' => (bool) ($transaction['is_admin_action'] ?? false),
            ];
        }, $transactions);
    }

    /**
     * Format counter transactions for display
     */
    private function formatCounterTransactions(array $transactions): array
    {
        return array_map(function ($transaction) {
            return [
                'time' => $transaction['_time'] ?? null,
                'user_id' => $transaction['user_id'] ?? null,
                'admin_id' => $transaction['admin_id'] ?? null,
                'counter_slug' => $transaction['counter_slug'] ?? 'unknown',
                'count_change' => (int) ($transaction['count_change'] ?? 0),
                'count_before' => (int) ($transaction['count_before'] ?? 0),
                'count_after' => (int) ($transaction['count_after'] ?? 0),
                'reason' => $transaction['reason'] ?? '',
                'type' => $transaction['type'] ?? 'unknown',
                'reason_category' => $transaction['reason_category'] ?? 'other',
                'is_admin_action' => (bool) ($transaction['is_admin_action'] ?? false),
            ];
        }, $transactions);
    }

    /**
     * Export transactions as JSON
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'both'); // token, counter, or both
        $timeRange = $request->get('range', '-7d');
        $userId = $request->get('user_id');

        $data = [];

        if ($type === 'both' || $type === 'token') {
            $data['token_transactions'] = $this->formatTokenTransactions(
                $this->influxService->queryTokenTransactions($userId, $timeRange, null, 1000)
            );
        }

        if ($type === 'both' || $type === 'counter') {
            $data['counter_transactions'] = $this->formatCounterTransactions(
                $this->influxService->queryCounterTransactions($userId, $timeRange, null, 1000)
            );
        }

        $filename = 'transactions_'.str_replace('-', '', $timeRange).'_'.date('Y-m-d_H-i-s').'.json';

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }
}
