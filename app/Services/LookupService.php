<?php

namespace App\Services;

use App\Models\Lookup;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LookupService
{
    private InfluxDBService $influxService;

    public function __construct(InfluxDBService $influxService)
    {
        $this->influxService = $influxService;
    }

    /**
     * Perform a lookup for a user
     */
    public function performLookup(User $user, string $query, string $type, array $metadata = []): Lookup
    {
        // Check subscription limits
        $this->enforceLookupLimits($user);

        // Consume monthly credits (Bits)
        $this->consumeMonthlyCredit($user);

        $startTime = microtime(true);

        try {
            $results = $this->executeLookup($query, $type);
            $responseTime = round((microtime(true) - $startTime) * 1000);

            $lookup = Lookup::create([
                'user_id' => $user->id,
                'query' => $query,
                'type' => $type,
                'results' => $results,
                'status' => 'success',
                'response_time_ms' => $responseTime,
                'source_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Record successful lookup in InfluxDB
            $this->influxService->writeCounterTransaction(
                $user->id,
                'monthly-credits',
                -1, // Deduct 1 credit
                'lookup_performed',
                [
                    'lookup_id' => $lookup->id,
                    'lookup_type' => $type,
                    'query' => $query,
                    'success' => true,
                    'response_time' => $responseTime,
                ]
            );

            return $lookup;

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);

            $lookup = Lookup::create([
                'user_id' => $user->id,
                'query' => $query,
                'type' => $type,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'response_time_ms' => $responseTime,
                'source_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Record failed lookup in InfluxDB (still consumes credit)
            $this->influxService->writeCounterTransaction(
                $user->id,
                'monthly-credits',
                -1,
                'lookup_failed',
                [
                    'lookup_id' => $lookup->id,
                    'lookup_type' => $type,
                    'query' => $query,
                    'success' => false,
                    'error' => $e->getMessage(),
                ]
            );

            Log::warning('Lookup failed', [
                'user_id' => $user->id,
                'query' => $query,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return $lookup;
        }
    }

    /**
     * Execute the actual lookup based on type
     */
    private function executeLookup(string $query, string $type): array
    {
        return match ($type) {
            'domain' => $this->performDomainLookup($query),
            'ip' => $this->performIpLookup($query),
            'email' => $this->performEmailLookup($query),
            'whois' => $this->performWhoisLookup($query),
            default => throw new \InvalidArgumentException("Unsupported lookup type: {$type}")
        };
    }

    /**
     * Perform domain lookup
     */
    private function performDomainLookup(string $domain): array
    {
        // Normalize domain
        $domain = strtolower(trim($domain));
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = preg_replace('/\/.*$/', '', $domain);

        $results = [];

        // DNS Records
        $results['dns'] = [
            'a_records' => dns_get_record($domain, DNS_A) ?: [],
            'mx_records' => dns_get_record($domain, DNS_MX) ?: [],
            'ns_records' => dns_get_record($domain, DNS_NS) ?: [],
        ];

        // HTTP Check
        try {
            $response = Http::timeout(10)->get("https://{$domain}");
            $results['http'] = [
                'status_code' => $response->status(),
                'response_time_ms' => 0, // Would need more precise timing
                'headers' => $response->headers(),
                'ssl_valid' => true, // Simplified
            ];
        } catch (\Exception $e) {
            $results['http'] = [
                'error' => $e->getMessage(),
                'ssl_valid' => false,
            ];
        }

        return $results;
    }

    /**
     * Perform IP lookup
     */
    private function performIpLookup(string $ip): array
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Invalid IP address format');
        }

        // Get hostname
        $hostname = gethostbyaddr($ip);

        // Get geolocation (would typically use a service like MaxMind)
        return [
            'ip' => $ip,
            'hostname' => $hostname !== $ip ? $hostname : null,
            'location' => [
                'country' => 'Unknown',
                'city' => 'Unknown',
                'isp' => 'Unknown',
            ],
            'reverse_dns' => $hostname !== $ip ? $hostname : null,
        ];
    }

    /**
     * Perform email lookup
     */
    private function performEmailLookup(string $email): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address format');
        }

        $domain = substr(strrchr($email, "@"), 1);

        return [
            'email' => $email,
            'domain' => $domain,
            'mx_records' => dns_get_record($domain, DNS_MX) ?: [],
            'domain_exists' => !empty(dns_get_record($domain, DNS_A)),
        ];
    }

    /**
     * Perform WHOIS lookup
     */
    private function performWhoisLookup(string $domain): array
    {
        // Simplified WHOIS - would typically use a proper WHOIS service
        return [
            'domain' => $domain,
            'registrar' => 'Unknown',
            'creation_date' => null,
            'expiration_date' => null,
            'name_servers' => dns_get_record($domain, DNS_NS) ?: [],
        ];
    }

    /**
     * Check if user can perform more lookups based on subscription
     */
    private function enforceLookupLimits(User $user): void
    {
        $monthlyLimit = $user->subscriptionLevel?->features['bits'] ?? 100;
        $currentMonthUsage = $user->getCounterBalance('monthly-credits');

        if ($currentMonthUsage <= 0) {
            throw new \Exception("Monthly credit limit reached. Your {$user->getSubscriptionLevelName()} plan allows {$monthlyLimit} lookups per month.");
        }
    }

    /**
     * Consume one monthly credit for the lookup
     */
    private function consumeMonthlyCredit(User $user): void
    {
        // This would typically update the user's counter balance
        // For now, we'll just record it in InfluxDB as the transaction tracking
        // The actual counter balance would be managed by the existing counter system
    }

    /**
     * Clean up old lookup history based on user's subscription
     */
    public function cleanupLookupHistory(User $user): int
    {
        if (!$user->subscriptionLevel) {
            return 0;
        }

        $historyDays = $user->subscriptionLevel->features['lookup_history_days'] ?? 10;
        $cutoffDate = now()->subDays($historyDays);

        $deleted = Lookup::where('user_id', $user->id)
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        Log::info('Cleaned up lookup history', [
            'user_id' => $user->id,
            'history_days' => $historyDays,
            'records_deleted' => $deleted,
        ]);

        return $deleted;
    }

    /**
     * Get lookup statistics for a user
     */
    public function getUserLookupStats(User $user, int $days = 30): array
    {
        $lookups = Lookup::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        return [
            'total_lookups' => $lookups->count(),
            'successful_lookups' => $lookups->where('status', 'success')->count(),
            'failed_lookups' => $lookups->where('status', 'failed')->count(),
            'average_response_time' => $lookups->where('response_time_ms', '>', 0)->avg('response_time_ms'),
            'lookup_types' => $lookups->groupBy('type')->map->count()->toArray(),
        ];
    }

    /**
     * Get recent lookups for a user within their subscription history limit
     */
    public function getUserRecentLookups(User $user): \Illuminate\Database\Eloquent\Collection
    {
        $historyDays = $user->subscriptionLevel?->features['lookup_history_days'] ?? 10;

        return Lookup::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays($historyDays))
            ->orderBy('created_at', 'desc')
            ->limit(100) // Hard limit for performance
            ->get();
    }
}