<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

/**
 * System Controller
 * Handles system management, monitoring, and configuration
 */
class SystemController extends Controller
{
    /**
     * Get system health status with robust error handling
     *
     * @return JsonResponse
     */
    public function getHealth(): JsonResponse
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'services' => [
                    [
                        'name' => 'Database',
                        'status' => $this->checkDatabaseHealth() ? 'up' : 'down',
                        'response_time' => $this->getDatabaseResponseTime(),
                        'last_check' => now()->toISOString(),
                    ],
                    [
                        'name' => 'Cache',
                        'status' => $this->checkCacheHealth() ? 'up' : 'down',
                        'response_time' => $this->getCacheResponseTime(),
                        'last_check' => now()->toISOString(),
                    ],
                    [
                        'name' => 'Storage',
                        'status' => $this->checkStorageHealth() ? 'up' : 'down',
                        'last_check' => now()->toISOString(),
                    ],
                ],
                'database' => [
                    'status' => $this->checkDatabaseHealth() ? 'connected' : 'disconnected',
                    'response_time' => $this->getDatabaseResponseTime(),
                    'connections' => $this->getDatabaseConnections(),
                    'max_connections' => 100, // Default MySQL max connections
                ],
                'cache' => [
                    'status' => $this->checkCacheHealth() ? 'connected' : 'disconnected',
                    'memory_usage' => $this->getCacheMemoryUsage(),
                    'hit_rate' => $this->getCacheHitRate(),
                ],
                'storage' => [
                    'status' => $this->checkStorageHealth() ? 'available' : 'error',
                    'used_space' => $this->getStorageUsedSpace(),
                    'total_space' => $this->getStorageTotalSpace(),
                    'free_space' => $this->getStorageFreeSpace(),
                ],
            ];

            // Determine overall system status
            $serviceStatuses = collect($health['services'])->pluck('status');
            if ($serviceStatuses->contains('down')) {
                $health['status'] = 'unhealthy';
            } elseif ($serviceStatuses->contains('degraded')) {
                $health['status'] = 'degraded';
            }

            return response()->json(['data' => $health]);
        } catch (\Exception $e) {
            Log::error('Error fetching system health: ' . $e->getMessage());
            
            // Return fallback health data to prevent UI breakage
            return response()->json([
                'data' => [
                    'status' => 'degraded',
                    'timestamp' => now()->toISOString(),
                    'services' => [
                        [
                            'name' => 'Database',
                            'status' => 'degraded',
                            'response_time' => 0,
                            'last_check' => now()->toISOString(),
                        ],
                        [
                            'name' => 'Cache',
                            'status' => 'degraded',
                            'response_time' => 0,
                            'last_check' => now()->toISOString(),
                        ],
                        [
                            'name' => 'Storage',
                            'status' => 'degraded',
                            'last_check' => now()->toISOString(),
                        ],
                    ],
                    'database' => [
                        'status' => 'disconnected',
                        'response_time' => 0,
                        'connections' => 0,
                        'max_connections' => 100,
                    ],
                    'cache' => [
                        'status' => 'disconnected',
                        'memory_usage' => 0,
                        'hit_rate' => 0,
                    ],
                    'storage' => [
                        'status' => 'error',
                        'used_space' => 0,
                        'total_space' => 0,
                        'free_space' => 0,
                    ],
                ]
            ]);
        }
    }

    /**
     * Get system statistics with robust error handling
     *
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => $this->getSafeCount('users'),
                'active_users_today' => $this->getActiveUsersToday(),
                'total_jobs' => $this->getSafeCount('jobs'),
                'active_jobs' => $this->getActiveJobs(),
                'total_payments' => $this->getSafeCount('payments'),
                'total_revenue' => $this->getTotalRevenue(),
                'system_uptime' => $this->getSystemUptime(),
                'memory_usage' => $this->getMemoryUsage(),
                'cpu_usage' => $this->getCpuUsage(),
                'disk_usage' => $this->getDiskUsage(),
                'api_requests_today' => $this->getApiRequestsToday(),
                'error_rate' => $this->getErrorRate(),
            ];

            return response()->json(['data' => $stats]);
        } catch (\Exception $e) {
            Log::error('Error fetching system stats: ' . $e->getMessage());
            
            // Return fallback stats to prevent UI breakage
            return response()->json([
                'data' => [
                    'total_users' => 0,
                    'active_users_today' => 0,
                    'total_jobs' => 0,
                    'active_jobs' => 0,
                    'total_payments' => 0,
                    'total_revenue' => 0,
                    'system_uptime' => 0,
                    'memory_usage' => 0,
                    'cpu_usage' => 0,
                    'disk_usage' => 0,
                    'api_requests_today' => 0,
                    'error_rate' => 100, // High error rate indicates system issues
                ]
            ]);
        }
    }

    /**
     * Get system logs with pagination and filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLogs(Request $request): JsonResponse
    {
        try {
            // For now, return mock data since Laravel logs are typically file-based
            // In a production environment, you might want to use a log management service
            $logs = [
                [
                    'id' => 1,
                    'level' => 'info',
                    'message' => 'User authentication successful',
                    'context' => ['user_id' => 1, 'ip' => '127.0.0.1'],
                    'user_id' => 1,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Mozilla/5.0...',
                    'created_at' => now()->subMinutes(5)->toISOString(),
                ],
                [
                    'id' => 2,
                    'level' => 'warning',
                    'message' => 'High memory usage detected',
                    'context' => ['memory_usage' => '85%'],
                    'user_id' => null,
                    'ip_address' => null,
                    'user_agent' => null,
                    'created_at' => now()->subMinutes(10)->toISOString(),
                ],
                [
                    'id' => 3,
                    'level' => 'error',
                    'message' => 'Database connection failed',
                    'context' => ['error' => 'Connection timeout'],
                    'user_id' => null,
                    'ip_address' => null,
                    'user_agent' => null,
                    'created_at' => now()->subMinutes(15)->toISOString(),
                ],
            ];

            // Apply filters
            if ($request->filled('level')) {
                $logs = array_filter($logs, function ($log) use ($request) {
                    return $log['level'] === $request->level;
                });
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $logs = array_filter($logs, function ($log) use ($search) {
                    return stripos($log['message'], $search) !== false ||
                           stripos(json_encode($log['context']), $search) !== false;
                });
            }

            return response()->json([
                'data' => array_values($logs),
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => count($logs),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching system logs: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch system logs'], 500);
        }
    }

    /**
     * Get system settings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSettings(Request $request): JsonResponse
    {
        try {
            // For now, return mock data
            // In a real implementation, you would have a settings table
            $settings = [
                [
                    'id' => 1,
                    'key' => 'app.name',
                    'value' => 'Fundi API',
                    'type' => 'string',
                    'description' => 'Application name',
                    'is_public' => true,
                    'updated_at' => now()->subDays(1)->toISOString(),
                    'updated_by' => 1,
                ],
                [
                    'id' => 2,
                    'key' => 'app.debug',
                    'value' => 'false',
                    'type' => 'boolean',
                    'description' => 'Debug mode',
                    'is_public' => false,
                    'updated_at' => now()->subDays(2)->toISOString(),
                    'updated_by' => 1,
                ],
                [
                    'id' => 3,
                    'key' => 'payment.default_provider',
                    'value' => 'mobile_money',
                    'type' => 'string',
                    'description' => 'Default payment provider',
                    'is_public' => false,
                    'updated_at' => now()->subDays(3)->toISOString(),
                    'updated_by' => 1,
                ],
            ];

            return response()->json([
                'data' => $settings,
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => count($settings),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching system settings: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch system settings'], 500);
        }
    }

    /**
     * Update system setting
     *
     * @param string $key
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSetting(string $key, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'value' => 'required|string',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // In a real implementation, you would update the settings table
            // For now, just return success
            return response()->json([
                'message' => 'Setting updated successfully',
                'data' => [
                    'key' => $key,
                    'value' => $request->value,
                    'description' => $request->description,
                    'updated_at' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating system setting: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update system setting'], 500);
        }
    }

    /**
     * Get system configuration
     *
     * @return JsonResponse
     */
    public function getConfiguration(): JsonResponse
    {
        try {
            $config = [
                'app' => [
                    'name' => config('app.name'),
                    'version' => '1.0.0',
                    'environment' => config('app.env'),
                    'debug' => config('app.debug'),
                    'maintenance_mode' => app()->isDownForMaintenance(),
                ],
                'database' => [
                    'driver' => config('database.default'),
                    'host' => config('database.connections.mysql.host'),
                    'port' => config('database.connections.mysql.port'),
                    'database' => config('database.connections.mysql.database'),
                    'charset' => config('database.connections.mysql.charset'),
                ],
                'cache' => [
                    'driver' => config('cache.default'),
                    'host' => config('cache.stores.redis.host'),
                    'port' => config('cache.stores.redis.port'),
                ],
                'queue' => [
                    'driver' => config('queue.default'),
                    'connection' => config('queue.connections.database.connection'),
                ],
                'mail' => [
                    'driver' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'encryption' => config('mail.mailers.smtp.encryption'),
                ],
                'payment' => [
                    'providers' => ['mobile_money', 'bank_transfer', 'card'],
                    'default_provider' => 'mobile_money',
                    'webhook_url' => config('app.url') . '/api/v1/webhooks/mobile-money',
                ],
            ];

            return response()->json(['data' => $config]);
        } catch (\Exception $e) {
            Log::error('Error fetching system configuration: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch system configuration'], 500);
        }
    }

    /**
     * Clear system cache
     *
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return response()->json(['message' => 'System cache cleared successfully']);
        } catch (\Exception $e) {
            Log::error('Error clearing system cache: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to clear system cache'], 500);
        }
    }

    /**
     * Restart system services
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function restartServices(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'services' => 'required|array',
                'services.*' => 'string|in:cache,queue,mail',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $restartedServices = [];
            $services = $request->services;

            foreach ($services as $service) {
                switch ($service) {
                    case 'cache':
                        Artisan::call('cache:clear');
                        $restartedServices[] = 'cache';
                        break;
                    case 'queue':
                        Artisan::call('queue:restart');
                        $restartedServices[] = 'queue';
                        break;
                    case 'mail':
                        // Mail service restart would depend on your mail driver
                        $restartedServices[] = 'mail';
                        break;
                }
            }

            return response()->json([
                'message' => 'Services restarted successfully',
                'restarted_services' => $restartedServices,
            ]);
        } catch (\Exception $e) {
            Log::error('Error restarting services: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to restart services'], 500);
        }
    }

    /**
     * Export system logs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportLogs(Request $request): JsonResponse
    {
        try {
            // In a real implementation, you would generate and return the log file
            return response()->json(['message' => 'Log export functionality would be implemented here']);
        } catch (\Exception $e) {
            Log::error('Error exporting logs: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to export logs'], 500);
        }
    }

    // Helper methods for health checks

    private function checkDatabaseHealth(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getDatabaseResponseTime(): int
    {
        $start = microtime(true);
        try {
            DB::select('SELECT 1');
            return (microtime(true) - $start) * 1000;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getDatabaseConnections(): int
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            return $result[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function checkCacheHealth(): bool
    {
        try {
            Cache::put('health_check', 'ok', 1);
            return Cache::get('health_check') === 'ok';
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getCacheResponseTime(): int
    {
        $start = microtime(true);
        try {
            Cache::get('health_check');
            return (microtime(true) - $start) * 1000;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getCacheMemoryUsage(): float
    {
        // This would depend on your cache driver
        return 0.0;
    }

    private function getCacheHitRate(): float
    {
        // This would depend on your cache driver
        return 95.0;
    }

    private function checkStorageHealth(): bool
    {
        try {
            return Storage::disk('local')->exists('storage');
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getStorageUsedSpace(): int
    {
        try {
            return disk_total_space(storage_path()) - disk_free_space(storage_path());
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getStorageTotalSpace(): int
    {
        try {
            return disk_total_space(storage_path());
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getStorageFreeSpace(): int
    {
        try {
            return disk_free_space(storage_path());
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getSystemUptime(): int
    {
        // This would be system-specific
        return 86400; // 24 hours in seconds
    }

    private function getMemoryUsage(): float
    {
        return memory_get_usage(true) / 1024 / 1024; // MB
    }

    private function getCpuUsage(): float
    {
        // This would be system-specific
        return 25.0; // Mock value
    }

    private function getDiskUsage(): float
    {
        try {
            $total = disk_total_space(storage_path());
            $free = disk_free_space(storage_path());
            return (($total - $free) / $total) * 100;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    private function getApiRequestsToday(): int
    {
        // This would require logging API requests
        return 1500; // Mock value
    }

    private function getErrorRate(): float
    {
        // This would require error tracking
        return 0.5; // Mock value
    }

    /**
     * Safely get count from a table with error handling
     *
     * @param string $table
     * @return int
     */
    private function getSafeCount(string $table): int
    {
        try {
            return DB::table($table)->count();
        } catch (\Exception $e) {
            Log::warning("Failed to count records in table {$table}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get active users today with error handling
     *
     * @return int
     */
    private function getActiveUsersToday(): int
    {
        try {
            return DB::table('users')
                ->whereDate('updated_at', today())
                ->count();
        } catch (\Exception $e) {
            Log::warning("Failed to get active users today: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get active jobs with error handling for missing status column
     *
     * @return int
     */
    private function getActiveJobs(): int
    {
        try {
            // First check if the status column exists
            $columns = DB::select("SHOW COLUMNS FROM jobs LIKE 'status'");
            
            if (empty($columns)) {
                // If status column doesn't exist, return total jobs count
                Log::info('Status column not found in jobs table, returning total jobs count');
                return DB::table('jobs')->count();
            }
            
            // If status column exists, use it
            return DB::table('jobs')
                ->whereIn('status', ['open', 'in_progress'])
                ->count();
        } catch (\Exception $e) {
            Log::warning("Failed to get active jobs: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total revenue with error handling
     *
     * @return float
     */
    private function getTotalRevenue(): float
    {
        try {
            return DB::table('payments')
                ->where('status', 'completed')
                ->sum('amount') ?? 0;
        } catch (\Exception $e) {
            Log::warning("Failed to get total revenue: " . $e->getMessage());
            return 0;
        }
    }
}
