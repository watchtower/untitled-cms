<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CounterTransaction;
use App\Models\CounterType;
use App\Models\Token;
use App\Models\TokenTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the main analytics dashboard
     */
    public function index(Request $request)
    {
        $period = $request->get('period', '30'); // Default to 30 days
        $startDate = Carbon::now()->subDays((int) $period);

        // Token Analytics
        $tokenMetrics = $this->getTokenAnalytics($startDate);
        $tokenChartData = $this->getTokenChartData($startDate);
        $tokenDistribution = $this->getTokenDistribution();

        // Counter Analytics
        $counterMetrics = $this->getCounterAnalytics($startDate);
        $counterChartData = $this->getCounterChartData($startDate);
        $counterDistribution = $this->getCounterDistribution();

        // User Engagement Analytics
        $userEngagement = $this->getUserEngagementAnalytics($startDate);
        $subscriptionAnalytics = $this->getSubscriptionAnalytics();

        // Economic Health Metrics
        $economicHealth = $this->getEconomicHealthMetrics($startDate);

        return view('admin.analytics.index', compact(
            'period',
            'tokenMetrics',
            'tokenChartData',
            'tokenDistribution',
            'counterMetrics',
            'counterChartData',
            'counterDistribution',
            'userEngagement',
            'subscriptionAnalytics',
            'economicHealth'
        ));
    }

    /**
     * Get token analytics data
     */
    private function getTokenAnalytics($startDate)
    {
        $tokens = Token::active()->get();
        $metrics = [];

        foreach ($tokens as $token) {
            $totalInCirculation = $token->getTotalInCirculation();
            $activeUsers = $token->getUserCount();
            $transactionVolume = TokenTransaction::where('token_id', $token->id)
                ->where('created_at', '>=', $startDate)
                ->sum('amount');
            $transactionCount = TokenTransaction::where('token_id', $token->id)
                ->where('created_at', '>=', $startDate)
                ->count();

            $metrics[] = [
                'token' => $token,
                'total_in_circulation' => $totalInCirculation,
                'active_users' => $activeUsers,
                'transaction_volume' => $transactionVolume,
                'transaction_count' => $transactionCount,
                'avg_transaction_size' => $transactionCount > 0 ? $transactionVolume / $transactionCount : 0,
            ];
        }

        return $metrics;
    }

    /**
     * Get token chart data for visualizations
     */
    private function getTokenChartData($startDate)
    {
        $days = [];
        $current = $startDate->copy();

        while ($current->lte(Carbon::now())) {
            $days[] = $current->format('Y-m-d');
            $current->addDay();
        }

        $tokens = Token::active()->get();
        $chartData = [];

        foreach ($tokens as $token) {
            $dailyData = [];
            foreach ($days as $day) {
                $dayStart = Carbon::parse($day);
                $dayEnd = $dayStart->copy()->endOfDay();

                $dailyTransactions = TokenTransaction::where('token_id', $token->id)
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->sum('amount');

                $dailyData[] = $dailyTransactions;
            }

            $chartData[] = [
                'label' => $token->name,
                'data' => $dailyData,
                'backgroundColor' => $token->color ?? '#6366f1',
                'borderColor' => $token->color ?? '#6366f1',
            ];
        }

        return [
            'labels' => array_map(fn ($day) => Carbon::parse($day)->format('M j'), $days),
            'datasets' => $chartData,
        ];
    }

    /**
     * Get token distribution data
     */
    private function getTokenDistribution()
    {
        $tokens = Token::active()->get();
        $distribution = [];

        foreach ($tokens as $token) {
            $distribution[] = [
                'token' => $token->name,
                'value' => $token->getTotalInCirculation(),
                'color' => $token->color ?? '#6366f1',
            ];
        }

        return $distribution;
    }

    /**
     * Get counter analytics data
     */
    private function getCounterAnalytics($startDate)
    {
        $counterTypes = CounterType::active()->get();
        $metrics = [];

        foreach ($counterTypes as $counterType) {
            $totalAllocated = $counterType->getTotalAllocation();
            $activeUsers = $counterType->getUserCount();
            $transactionVolume = CounterTransaction::where('counter_id', $counterType->id)
                ->where('created_at', '>=', $startDate)
                ->sum('count_change');
            $transactionCount = CounterTransaction::where('counter_id', $counterType->id)
                ->where('created_at', '>=', $startDate)
                ->count();

            $metrics[] = [
                'counter_type' => $counterType,
                'total_allocated' => $totalAllocated,
                'active_users' => $activeUsers,
                'transaction_volume' => $transactionVolume,
                'transaction_count' => $transactionCount,
                'avg_transaction_size' => $transactionCount > 0 ? $transactionVolume / $transactionCount : 0,
            ];
        }

        return $metrics;
    }

    /**
     * Get counter chart data for visualizations
     */
    private function getCounterChartData($startDate)
    {
        $days = [];
        $current = $startDate->copy();

        while ($current->lte(Carbon::now())) {
            $days[] = $current->format('Y-m-d');
            $current->addDay();
        }

        $counterTypes = CounterType::active()->get();
        $chartData = [];

        foreach ($counterTypes as $counterType) {
            $dailyData = [];
            foreach ($days as $day) {
                $dayStart = Carbon::parse($day);
                $dayEnd = $dayStart->copy()->endOfDay();

                $dailyTransactions = CounterTransaction::where('counter_id', $counterType->id)
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->count();

                $dailyData[] = $dailyTransactions;
            }

            $chartData[] = [
                'label' => $counterType->name,
                'data' => $dailyData,
                'backgroundColor' => $counterType->color ?? '#f59e0b',
                'borderColor' => $counterType->color ?? '#f59e0b',
            ];
        }

        return [
            'labels' => array_map(fn ($day) => Carbon::parse($day)->format('M j'), $days),
            'datasets' => $chartData,
        ];
    }

    /**
     * Get counter distribution data
     */
    private function getCounterDistribution()
    {
        $counterTypes = CounterType::active()->get();
        $distribution = [];

        foreach ($counterTypes as $counterType) {
            $distribution[] = [
                'counter' => $counterType->name,
                'value' => $counterType->getTotalAllocation(),
                'color' => $counterType->color ?? '#f59e0b',
            ];
        }

        return $distribution;
    }

    /**
     * Get user engagement analytics
     */
    private function getUserEngagementAnalytics($startDate)
    {
        $totalUsers = User::count();
        $activeUsers = User::whereHas('tokenTransactions', function ($query) use ($startDate) {
            $query->where('created_at', '>=', $startDate);
        })->orWhereHas('counterTransactions', function ($query) use ($startDate) {
            $query->where('created_at', '>=', $startDate);
        })->count();

        $engagement = $totalUsers > 0 ? ($activeUsers / $totalUsers) * 100 : 0;

        // Get daily active users
        $dailyActiveUsers = [];
        $current = $startDate->copy();

        while ($current->lte(Carbon::now())) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();

            $dailyActive = User::whereHas('tokenTransactions', function ($query) use ($dayStart, $dayEnd) {
                $query->whereBetween('created_at', [$dayStart, $dayEnd]);
            })->orWhereHas('counterTransactions', function ($query) use ($dayStart, $dayEnd) {
                $query->whereBetween('created_at', [$dayStart, $dayEnd]);
            })->count();

            $dailyActiveUsers[] = $dailyActive;
            $current->addDay();
        }

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'engagement_rate' => $engagement,
            'daily_active_users' => $dailyActiveUsers,
        ];
    }

    /**
     * Get subscription level analytics
     */
    private function getSubscriptionAnalytics()
    {
        $subscriptions = \App\Models\SubscriptionLevel::withCount('users')->get();

        return $subscriptions->map(function ($subscription) {
            return [
                'name' => $subscription->name,
                'users' => $subscription->users_count,
                'percentage' => User::count() > 0 ? ($subscription->users_count / User::count()) * 100 : 0,
            ];
        });
    }

    /**
     * Get economic health metrics
     */
    private function getEconomicHealthMetrics($startDate)
    {
        // Token velocity (how often tokens change hands)
        $totalTokenSupply = Token::active()->sum(function ($token) {
            return $token->getTotalInCirculation();
        });

        $totalTokenTransactions = TokenTransaction::where('created_at', '>=', $startDate)
            ->sum('amount');

        $tokenVelocity = $totalTokenSupply > 0 ? $totalTokenTransactions / $totalTokenSupply : 0;

        // Counter utilization rate
        $totalCounterCapacity = CounterType::active()->sum(function ($counterType) {
            return $counterType->getTotalAllocation();
        });

        $totalCounterUsage = CounterTransaction::where('created_at', '>=', $startDate)
            ->where('count_change', '<', 0) // Only count usage (negative changes)
            ->sum('count_change');

        $counterUtilization = $totalCounterCapacity > 0 ? abs($totalCounterUsage) / $totalCounterCapacity * 100 : 0;

        // Economy activity score (composite metric)
        $tokenTransactionCount = TokenTransaction::where('created_at', '>=', $startDate)->count();
        $counterTransactionCount = CounterTransaction::where('created_at', '>=', $startDate)->count();
        $totalTransactions = $tokenTransactionCount + $counterTransactionCount;
        $daysInPeriod = $startDate->diffInDays(Carbon::now());

        $activityScore = $daysInPeriod > 0 ? $totalTransactions / $daysInPeriod : 0;

        return [
            'token_velocity' => $tokenVelocity,
            'counter_utilization' => $counterUtilization,
            'activity_score' => $activityScore,
            'total_transactions' => $totalTransactions,
        ];
    }

    /**
     * Get real-time analytics data (AJAX endpoint)
     */
    public function realtime(Request $request)
    {
        $period = $request->get('period', '7');
        $startDate = Carbon::now()->subDays((int) $period);

        return response()->json([
            'token_metrics' => $this->getTokenAnalytics($startDate),
            'counter_metrics' => $this->getCounterAnalytics($startDate),
            'user_engagement' => $this->getUserEngagementAnalytics($startDate),
            'economic_health' => $this->getEconomicHealthMetrics($startDate),
            'timestamp' => Carbon::now()->toISOString(),
        ]);
    }
}
