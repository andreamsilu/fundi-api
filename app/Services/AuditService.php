<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log an action
     */
    public static function log(
        string $action,
        string $resourceType,
        ?int $resourceId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        string $status = 'success',
        ?string $errorMessage = null,
        ?Request $request = null
    ): void {
        try {
            $user = Auth::user();
            $request = $request ?? request();

            AuditLog::create([
                'user_id' => $user?->id,
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'metadata' => $metadata,
                'status' => $status,
                'error_message' => $errorMessage,
            ]);
        } catch (\Exception $e) {
            // Log audit failure to Laravel log to prevent infinite loops
            \Log::error('Audit logging failed: ' . $e->getMessage());
        }
    }

    /**
     * Log user authentication
     */
    public static function logAuth(string $action, ?User $user = null, ?string $errorMessage = null): void
    {
        self::log(
            action: $action,
            resourceType: 'User',
            resourceId: $user?->id,
            metadata: [
                'phone' => $user?->phone,
                'role' => $user?->role,
            ],
            status: $errorMessage ? 'failed' : 'success',
            errorMessage: $errorMessage
        );
    }

    /**
     * Log CRUD operations
     */
    public static function logCrud(
        string $action,
        string $resourceType,
        int $resourceId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): void {
        self::log(
            action: $action,
            resourceType: $resourceType,
            resourceId: $resourceId,
            oldValues: $oldValues,
            newValues: $newValues,
            metadata: $metadata
        );
    }

    /**
     * Log file operations
     */
    public static function logFileOperation(
        string $action,
        string $fileType,
        string $filePath,
        ?int $resourceId = null,
        ?array $metadata = null
    ): void {
        self::log(
            action: $action,
            resourceType: 'File',
            resourceId: $resourceId,
            metadata: array_merge([
                'file_type' => $fileType,
                'file_path' => $filePath,
            ], $metadata ?? [])
        );
    }

    /**
     * Log payment operations
     */
    public static function logPayment(
        string $action,
        int $paymentId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): void {
        self::log(
            action: $action,
            resourceType: 'Payment',
            resourceId: $paymentId,
            oldValues: $oldValues,
            newValues: $newValues,
            metadata: $metadata
        );
    }

    /**
     * Log admin actions
     */
    public static function logAdminAction(
        string $action,
        string $resourceType,
        ?int $resourceId = null,
        ?array $metadata = null
    ): void {
        self::log(
            action: $action,
            resourceType: $resourceType,
            resourceId: $resourceId,
            metadata: array_merge([
                'admin_action' => true,
            ], $metadata ?? [])
        );
    }

    /**
     * Log security events
     */
    public static function logSecurityEvent(
        string $event,
        ?array $metadata = null,
        ?string $errorMessage = null
    ): void {
        self::log(
            action: 'SECURITY_EVENT',
            resourceType: 'Security',
            metadata: array_merge([
                'event' => $event,
            ], $metadata ?? []),
            status: $errorMessage ? 'failed' : 'success',
            errorMessage: $errorMessage
        );
    }

    /**
     * Log API errors
     */
    public static function logApiError(
        string $endpoint,
        int $statusCode,
        string $errorMessage,
        ?array $metadata = null
    ): void {
        self::log(
            action: 'API_ERROR',
            resourceType: 'API',
            metadata: array_merge([
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
            ], $metadata ?? []),
            status: 'error',
            errorMessage: $errorMessage
        );
    }

    /**
     * Get audit logs with filters
     */
    public static function getLogs(
        ?string $action = null,
        ?string $resourceType = null,
        ?int $userId = null,
        ?string $status = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15
    ) {
        $query = AuditLog::with('user');

        if ($action) {
            $query->action($action);
        }

        if ($resourceType) {
            $query->resourceType($resourceType);
        }

        if ($userId) {
            $query->user($userId);
        }

        if ($status) {
            $query->status($status);
        }

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get audit statistics
     */
    public static function getStatistics(?string $startDate = null, ?string $endDate = null): array
    {
        $query = AuditLog::query();

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return [
            'total_actions' => $query->count(),
            'successful_actions' => $query->clone()->successful()->count(),
            'failed_actions' => $query->clone()->failed()->count(),
            'actions_by_type' => $query->clone()
                ->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->get(),
            'resources_by_type' => $query->clone()
                ->selectRaw('resource_type, COUNT(*) as count')
                ->groupBy('resource_type')
                ->orderBy('count', 'desc')
                ->get(),
            'users_by_activity' => $query->clone()
                ->selectRaw('user_id, COUNT(*) as count')
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
        ];
    }
}
