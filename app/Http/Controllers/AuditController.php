<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuditController extends Controller
{
    /**
     * Get audit logs with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $logs = AuditService::getLogs(
                action: $request->get('action'),
                resourceType: $request->get('resource_type'),
                userId: $request->get('user_id'),
                status: $request->get('status'),
                startDate: $request->get('start_date'),
                endDate: $request->get('end_date'),
                perPage: $request->get('per_page', 15)
            );

            return response()->json([
                'success' => true,
                'message' => 'Audit logs retrieved successfully',
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve audit logs',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving audit logs'
            ], 500);
        }
    }

    /**
     * Get specific audit log
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $log = AuditLog::with('user')->find($id);

            if (!$log) {
                return response()->json([
                    'success' => false,
                    'message' => 'Audit log not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Audit log retrieved successfully',
                'data' => $log
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve audit log',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving audit log'
            ], 500);
        }
    }

    /**
     * Get audit statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $stats = AuditService::getStatistics(
                startDate: $request->get('start_date'),
                endDate: $request->get('end_date')
            );

            return response()->json([
                'success' => true,
                'message' => 'Audit statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve audit statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving audit statistics'
            ], 500);
        }
    }

    /**
     * Get failed actions
     */
    public function failedActions(Request $request): JsonResponse
    {
        try {
            $logs = AuditLog::with('user')
                ->failed()
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'message' => 'Failed actions retrieved successfully',
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve failed actions',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving failed actions'
            ], 500);
        }
    }

    /**
     * Get user activity
     */
    public function userActivity(Request $request, $userId): JsonResponse
    {
        try {
            $logs = AuditLog::with('user')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'message' => 'User activity retrieved successfully',
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user activity',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving user activity'
            ], 500);
        }
    }

    /**
     * Get security events
     */
    public function securityEvents(Request $request): JsonResponse
    {
        try {
            $logs = AuditLog::with('user')
                ->where('action', 'SECURITY_EVENT')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'message' => 'Security events retrieved successfully',
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve security events',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving security events'
            ], 500);
        }
    }

    /**
     * Get API errors
     */
    public function apiErrors(Request $request): JsonResponse
    {
        try {
            $logs = AuditLog::with('user')
                ->where('action', 'API_ERROR')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'message' => 'API errors retrieved successfully',
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve API errors',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving API errors'
            ], 500);
        }
    }

    /**
     * Export audit logs
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $logs = AuditLog::with('user')
                ->when($request->get('start_date'), function ($query, $startDate) {
                    return $query->where('created_at', '>=', $startDate);
                })
                ->when($request->get('end_date'), function ($query, $endDate) {
                    return $query->where('created_at', '<=', $endDate);
                })
                ->when($request->get('action'), function ($query, $action) {
                    return $query->where('action', $action);
                })
                ->when($request->get('resource_type'), function ($query, $resourceType) {
                    return $query->where('resource_type', $resourceType);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Audit logs exported successfully',
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export audit logs',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while exporting audit logs'
            ], 500);
        }
    }
}