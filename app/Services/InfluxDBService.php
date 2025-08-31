<?php

namespace App\Services;

use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Point;

class InfluxDBService
{
    private $client;

    private $database;

    public function __construct()
    {
        // Skip InfluxDB initialization if disabled
        if (!config('services.influxdb.enabled', false)) {
            \Log::info('InfluxDB is disabled, using MySQL-only mode');
            $this->client = null;
            $this->database = null;
            return;
        }

        try {
            $this->client = new Client(
                config('services.influxdb.url', 'http://localhost:8086'),
                config('services.influxdb.port', 8086),
                config('services.influxdb.username', ''),
                config('services.influxdb.password', ''),
                config('services.influxdb.ssl', false),
                config('services.influxdb.timeout', 5.0)
            );

            $this->database = $this->client->selectDB(config('services.influxdb.database', 'l33t_economy'));
            \Log::info('InfluxDB client initialized successfully');
        } catch (\Exception $e) {
            $message = 'Failed to initialize InfluxDB client: '.$e->getMessage();
            \Log::warning($message);
            
            if (config('services.influxdb.hybrid_mode', true)) {
                \Log::info('InfluxDB hybrid mode enabled, falling back to MySQL-only mode');
            } else {
                \Log::error('InfluxDB hybrid mode disabled, analytics may be limited');
            }
            
            $this->client = null;
            $this->database = null;
        }
    }

    /**
     * Check if InfluxDB is available and enabled
     */
    public function isAvailable(): bool
    {
        return $this->client !== null && $this->database !== null;
    }

    /**
     * Check if hybrid mode is enabled (MySQL fallback)
     */
    public function isHybridMode(): bool
    {
        return config('services.influxdb.hybrid_mode', true);
    }

    /**
     * Check if InfluxDB is enabled in configuration
     */
    public function isEnabled(): bool
    {
        return config('services.influxdb.enabled', false);
    }

    /**
     * Record a token transaction
     */
    public function writeTokenTransaction(
        int $userId,
        ?int $adminId,
        int $tokenId,
        string $tokenSlug,
        int $amount,
        int $balanceBefore,
        int $balanceAfter,
        string $reason,
        string $type,
        ?array $metadata = null
    ): bool {
        try {
            if (! $this->database) {
                \Log::warning('InfluxDB database not available for token transaction recording');

                return false;
            }

            $point = new Point(
                'token_transactions',
                null,
                [
                    'user_id' => (string) $userId,
                    'token_id' => (string) $tokenId,
                    'token_slug' => $tokenSlug,
                    'type' => $type,
                    'reason_category' => $this->categorizeReason($reason),
                    'admin_id' => $adminId ? (string) $adminId : null,
                ],
                [
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'reason' => $reason,
                    'is_admin_action' => $adminId !== null,
                    'metadata' => $metadata ? json_encode($metadata) : null,
                ]
            );

            return $this->database->writePoints([$point], Database::PRECISION_SECONDS);
        } catch (\Exception $e) {
            \Log::error('Failed to write token transaction to InfluxDB: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Record a counter transaction
     */
    public function writeCounterTransaction(
        int $userId,
        ?int $adminId,
        int $counterId,
        string $counterSlug,
        int $countChange,
        int $countBefore,
        int $countAfter,
        string $reason,
        string $type,
        ?array $metadata = null
    ): bool {
        try {
            if (! $this->database) {
                \Log::warning('InfluxDB database not available for counter transaction recording');

                return false;
            }

            $point = new Point(
                'counter_transactions',
                null,
                [
                    'user_id' => (string) $userId,
                    'counter_id' => (string) $counterId,
                    'counter_slug' => $counterSlug,
                    'type' => $type,
                    'reason_category' => $this->categorizeReason($reason),
                    'admin_id' => $adminId ? (string) $adminId : null,
                ],
                [
                    'count_change' => $countChange,
                    'count_before' => $countBefore,
                    'count_after' => $countAfter,
                    'reason' => $reason,
                    'is_admin_action' => $adminId !== null,
                    'metadata' => $metadata ? json_encode($metadata) : null,
                ]
            );

            return $this->database->writePoints([$point], Database::PRECISION_SECONDS);
        } catch (\Exception $e) {
            \Log::error('Failed to write counter transaction to InfluxDB: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Query token transactions from InfluxDB
     */
    public function queryTokenTransactions(
        ?int $userId = null,
        string $timeRange = '-30d',
        ?string $tokenSlug = null,
        int $limit = 100
    ): array {
        try {
            if (! $this->database) {
                return [];
            }

            $whereConditions = [];
            if ($userId) {
                $whereConditions[] = sprintf("user_id = '%s'", addslashes((string)$userId));
            }
            if ($tokenSlug) {
                $whereConditions[] = sprintf("token_slug = '%s'", addslashes($tokenSlug));
            }

            $whereClause = empty($whereConditions) ? '' : 'WHERE '.implode(' AND ', $whereConditions);
            
            // Validate and sanitize limit
            $limit = max(1, min(1000, (int)$limit));

            $query = "SELECT * FROM token_transactions $whereClause ORDER BY time DESC LIMIT $limit";

            $result = $this->database->query($query);

            return $result->getPoints();
        } catch (\Exception $e) {
            \Log::error('InfluxDB token transaction query failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Query counter transactions from InfluxDB
     */
    public function queryCounterTransactions(
        ?int $userId = null,
        string $timeRange = '-30d',
        ?string $counterSlug = null,
        int $limit = 100
    ): array {
        try {
            if (! $this->database) {
                return [];
            }

            $whereConditions = [];
            if ($userId) {
                $whereConditions[] = sprintf("user_id = '%s'", addslashes((string)$userId));
            }
            if ($counterSlug) {
                $whereConditions[] = sprintf("counter_slug = '%s'", addslashes($counterSlug));
            }

            $whereClause = empty($whereConditions) ? '' : 'WHERE '.implode(' AND ', $whereConditions);
            
            // Validate and sanitize limit
            $limit = max(1, min(1000, (int)$limit));

            $query = "SELECT * FROM counter_transactions $whereClause ORDER BY time DESC LIMIT $limit";

            $result = $this->database->query($query);

            return $result->getPoints();
        } catch (\Exception $e) {
            \Log::error('InfluxDB counter transaction query failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get transaction statistics from InfluxDB
     */
    public function getTransactionStats(string $type = 'both', string $timeRange = '-30d'): array
    {
        try {
            if ($type === 'token') {
                return $this->getTokenTransactionStats($timeRange);
            }

            if ($type === 'counter') {
                return $this->getCounterTransactionStats($timeRange);
            }

            // Return both types
            return [
                'token' => $this->getTokenTransactionStats($timeRange),
                'counter' => $this->getCounterTransactionStats($timeRange),
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to get transaction stats: '.$e->getMessage());

            return $type === 'both' ? ['token' => [], 'counter' => []] : [];
        }
    }

    /**
     * Get token transaction statistics
     */
    private function getTokenTransactionStats(string $timeRange): array
    {
        try {
            if (! $this->database) {
                return [];
            }

            $query = "SELECT SUM(amount) FROM token_transactions WHERE time > now() $timeRange GROUP BY type";
            $result = $this->database->query($query);

            $stats = [];
            foreach ($result->getPoints() as $point) {
                $stats[$point['type']] = $point['sum'] ?? 0;
            }

            return $stats;
        } catch (\Exception $e) {
            \Log::error('Token transaction stats query failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get counter transaction statistics
     */
    private function getCounterTransactionStats(string $timeRange): array
    {
        try {
            if (! $this->database) {
                return [];
            }

            $query = "SELECT SUM(count_change) FROM counter_transactions WHERE time > now() $timeRange GROUP BY type";
            $result = $this->database->query($query);

            $stats = [];
            foreach ($result->getPoints() as $point) {
                $stats[$point['type']] = $point['sum'] ?? 0;
            }

            return $stats;
        } catch (\Exception $e) {
            \Log::error('Counter transaction stats query failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Categorize transaction reason for better analytics
     */
    private function categorizeReason(string $reason): string
    {
        $reason = strtolower($reason);

        if (str_contains($reason, 'tool')) {
            return 'tool_usage';
        } elseif (str_contains($reason, 'admin')) {
            return 'admin_action';
        } elseif (str_contains($reason, 'refund')) {
            return 'refund';
        } elseif (str_contains($reason, 'bonus')) {
            return 'bonus';
        } elseif (str_contains($reason, 'daily')) {
            return 'daily_reset';
        } else {
            return 'other';
        }
    }

    /**
     * Check if InfluxDB is connected and accessible
     */
    public function isConnected(): bool
    {
        try {
            if (! $this->client || ! $this->database) {
                \Log::warning('InfluxDB client or database not initialized');
                return false;
            }

            // Test connection by attempting a simple query
            $this->database->query('SHOW DATABASES LIMIT 1');

            return true;
        } catch (\InfluxDB\Exception\ClientException $e) {
            \Log::error('InfluxDB client error: '.$e->getMessage(), [
                'code' => $e->getCode(),
                'type' => 'client_error'
            ]);
            return false;
        } catch (\Exception $e) {
            \Log::error('InfluxDB health check failed: '.$e->getMessage(), [
                'type' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
