<?php

namespace App\Services;

use App\Models\SiteMonitor;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SiteMonitorService
{
    private InfluxDBService $influxService;

    public function __construct(InfluxDBService $influxService)
    {
        $this->influxService = $influxService;
    }

    /**
     * Create a new site monitor for a user
     */
    public function createMonitor(User $user, array $data): SiteMonitor
    {
        // Check if user can create more monitors based on subscription
        $this->enforceMonitorLimits($user);

        $monitor = $user->siteMonitors()->create([
            'name' => $data['name'],
            'url' => $this->normalizeUrl($data['url']),
            'check_interval_minutes' => $data['check_interval_minutes'] ?? 5,
            'notifications_enabled' => $data['notifications_enabled'] ?? true,
        ]);

        // Record monitor creation in InfluxDB
        $this->influxService->writeCounterTransaction(
            $user->id,
            'site-monitors',
            1,
            'create_monitor',
            ['monitor_id' => $monitor->id, 'monitor_name' => $monitor->name]
        );

        return $monitor;
    }

    /**
     * Check a site monitor and update its status
     */
    public function checkMonitor(SiteMonitor $monitor): array
    {
        $startTime = microtime(true);
        
        try {
            $response = Http::timeout(30)->get($monitor->url);
            $responseTime = round((microtime(true) - $startTime) * 1000);
            
            $isSuccess = $response->successful();
            
            $updateData = [
                'last_checked_at' => now(),
                'response_time_ms' => $responseTime,
                'status_code' => $response->status(),
                'response_data' => [
                    'headers' => $response->headers(),
                    'size' => strlen($response->body()),
                    'timestamp' => now()->toISOString(),
                ],
            ];

            if ($isSuccess) {
                $updateData['last_success_at'] = now();
                $updateData['consecutive_failures'] = 0;
                $updateData['status'] = 'active';
                $updateData['failure_reason'] = null;
            } else {
                $updateData['last_failure_at'] = now();
                $updateData['consecutive_failures'] = $monitor->consecutive_failures + 1;
                $updateData['failure_reason'] = "HTTP {$response->status()}";
                
                if ($updateData['consecutive_failures'] >= 3) {
                    $updateData['status'] = 'failed';
                }
            }

            $monitor->update($updateData);

            // Record check in InfluxDB for analytics
            $this->influxService->writeCounterTransaction(
                $monitor->user_id,
                'monitor-checks',
                1,
                'monitor_check',
                [
                    'monitor_id' => $monitor->id,
                    'success' => $isSuccess,
                    'response_time' => $responseTime,
                    'status_code' => $response->status(),
                ]
            );

            return [
                'success' => $isSuccess,
                'response_time' => $responseTime,
                'status_code' => $response->status(),
                'consecutive_failures' => $updateData['consecutive_failures'],
            ];

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            
            $monitor->update([
                'last_checked_at' => now(),
                'last_failure_at' => now(),
                'consecutive_failures' => $monitor->consecutive_failures + 1,
                'failure_reason' => $e->getMessage(),
                'status' => $monitor->consecutive_failures >= 2 ? 'failed' : 'active',
                'response_time_ms' => $responseTime,
            ]);

            Log::error('Site monitor check failed', [
                'monitor_id' => $monitor->id,
                'url' => $monitor->url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'response_time' => $responseTime,
                'error' => $e->getMessage(),
                'consecutive_failures' => $monitor->consecutive_failures + 1,
            ];
        }
    }

    /**
     * Clean up old monitor data based on user's subscription retention period
     */
    public function cleanupRetentionData(User $user): int
    {
        if (!$user->subscriptionLevel) {
            return 0;
        }

        $retentionDays = $user->subscriptionLevel->features['site_monitor_retention_days'] ?? 10;
        $cutoffDate = now()->subDays($retentionDays);

        // For this implementation, we'll clean up the response_data field for old records
        // In a full implementation, you might have a separate monitor_checks table
        $affected = SiteMonitor::where('user_id', $user->id)
            ->where('last_checked_at', '<', $cutoffDate)
            ->update([
                'response_data' => null,
                'failure_reason' => null,
            ]);

        Log::info('Cleaned up site monitor retention data', [
            'user_id' => $user->id,
            'retention_days' => $retentionDays,
            'records_cleaned' => $affected,
        ]);

        return $affected;
    }

    /**
     * Check if user can create more monitors based on subscription
     */
    private function enforceMonitorLimits(User $user): void
    {
        $currentCount = $user->siteMonitors()->count();
        $maxMonitors = $this->getMaxMonitorsForUser($user);

        if ($currentCount >= $maxMonitors) {
            throw new \Exception("Monitor limit reached. Your {$user->getSubscriptionLevelName()} plan allows {$maxMonitors} monitors.");
        }
    }

    /**
     * Get maximum monitors allowed for user's subscription level
     */
    private function getMaxMonitorsForUser(User $user): int
    {
        if (!$user->subscriptionLevel) {
            return 1; // Free users get 1 monitor
        }

        return match ($user->subscriptionLevel->slug) {
            'starter' => 3,
            'pro' => 10,
            'elite' => 50,
            default => 1,
        };
    }

    /**
     * Normalize URL to ensure it's properly formatted
     */
    private function normalizeUrl(string $url): string
    {
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }

        return rtrim($url, '/');
    }

    /**
     * Get monitors that need to be checked now
     */
    public function getMonitorsNeedingCheck(): \Illuminate\Database\Eloquent\Collection
    {
        return SiteMonitor::needsCheck()->with('user.subscriptionLevel')->get();
    }

    /**
     * Perform health check on all active monitors
     */
    public function performHealthChecks(): array
    {
        $monitors = $this->getMonitorsNeedingCheck();
        $results = [];

        foreach ($monitors as $monitor) {
            $results[$monitor->id] = $this->checkMonitor($monitor);
        }

        return $results;
    }
}