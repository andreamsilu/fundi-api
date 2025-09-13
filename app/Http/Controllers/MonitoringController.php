<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Job;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    /**
     * Get active users count
     */
    public function getActiveUsers(): JsonResponse
    {
        try {
            $activeUsers = User::where('status', 'active')->count();
            $fundis = User::whereJsonContains('roles', 'fundi')->where('status', 'active')->count();
            $customers = User::whereJsonContains('roles', 'customer')->where('status', 'active')->count();

            return response()->json([
                'success' => true,
                'message' => 'Active users data retrieved successfully',
                'data' => [
                    'total_active_users' => $activeUsers,
                    'active_fundis' => $fundis,
                    'active_customers' => $customers,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active users data',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving active users data'
            ], 500);
        }
    }

    /**
     * Get jobs summary
     */
    public function getJobsSummary(): JsonResponse
    {
        try {
            $totalJobs = Job::count();
            $openJobs = Job::where('status', 'open')->count();
            $inProgressJobs = Job::where('status', 'in_progress')->count();
            $completedJobs = Job::where('status', 'completed')->count();
            $cancelledJobs = Job::where('status', 'cancelled')->count();

            return response()->json([
                'success' => true,
                'message' => 'Jobs summary retrieved successfully',
                'data' => [
                    'total_jobs' => $totalJobs,
                    'open_jobs' => $openJobs,
                    'in_progress_jobs' => $inProgressJobs,
                    'completed_jobs' => $completedJobs,
                    'cancelled_jobs' => $cancelledJobs,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve jobs summary',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving jobs summary'
            ], 500);
        }
    }

    /**
     * Get payments summary
     */
    public function getPaymentsSummary(): JsonResponse
    {
        try {
            $totalRevenue = Payment::where('status', 'completed')->sum('amount');
            $pendingPayments = Payment::where('status', 'pending')->count();
            $completedPayments = Payment::where('status', 'completed')->count();
            $failedPayments = Payment::where('status', 'failed')->count();

            return response()->json([
                'success' => true,
                'message' => 'Payments summary retrieved successfully',
                'data' => [
                    'total_revenue' => $totalRevenue,
                    'pending_payments' => $pendingPayments,
                    'completed_payments' => $completedPayments,
                    'failed_payments' => $failedPayments,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payments summary',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving payments summary'
            ], 500);
        }
    }

    /**
     * Get system health
     */
    public function getSystemHealth(): JsonResponse
    {
        try {
            $dbStatus = 'healthy';
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                $dbStatus = 'unhealthy';
            }

            $queueSize = 0; // This would be implemented with actual queue monitoring

            return response()->json([
                'success' => true,
                'message' => 'System health data retrieved successfully',
                'data' => [
                    'database_status' => $dbStatus,
                    'queue_size' => $queueSize,
                    'uptime' => 'N/A', // This would be implemented with actual uptime monitoring
                    'storage_usage' => 'N/A', // This would be implemented with actual storage monitoring
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve system health data',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving system health data'
            ], 500);
        }
    }

    /**
     * Get API logs
     */
    public function getApiLogs(): JsonResponse
    {
        try {
            // This would be implemented with actual API logging
            return response()->json([
                'success' => true,
                'message' => 'API logs retrieved successfully',
                'data' => [
                    'message' => 'API logging not implemented yet'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve API logs',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving API logs'
            ], 500);
        }
    }

    /**
     * Get Laravel logs
     */
    public function getLaravelLogs(): JsonResponse
    {
        try {
            $logPath = storage_path('logs/laravel.log');
            
            if (!file_exists($logPath)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Laravel logs retrieved successfully',
                    'data' => [
                        'log_lines' => ['No log file found']
                    ]
                ]);
            }

            $logContent = file_get_contents($logPath);
            $logLines = array_slice(explode("\n", $logContent), -50); // Get last 50 lines

            return response()->json([
                'success' => true,
                'message' => 'Laravel logs retrieved successfully',
                'data' => [
                    'log_lines' => array_filter($logLines) // Remove empty lines
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Laravel logs',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving Laravel logs'
            ], 500);
        }
    }
}
