<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JWTAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FundiApplicationController;
use App\Http\Controllers\WorkApprovalController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AdminRoleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\ErrorController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)

// Health check endpoint (no authentication required)
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
        'environment' => config('app.env'),
        'database' => 'connected',
        'cache' => 'operational'
    ]);
});




// Public routes (no authentication required)
Route::get('/categories', [CategoryController::class, 'index']);

// JWT Authentication routes (no authentication required)
Route::post('/auth/login', [JWTAuthController::class, 'login']);
Route::post('/auth/register', [JWTAuthController::class, 'register']);

// Protected routes (JWT authentication required)
// Apply general rate limiting for authenticated API usage
Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {
    // JWT Authentication routes
    Route::post('/auth/logout', [JWTAuthController::class, 'logout']);
    Route::get('/auth/me', [JWTAuthController::class, 'me']);
    Route::post('/auth/refresh', [JWTAuthController::class, 'refresh']);
    Route::post('/auth/change-password', [JWTAuthController::class, 'changePassword']);

    // User routes
    Route::get('/users/me', [UserController::class, 'me']);
    Route::patch('/users/me/profile', [UserController::class, 'updateProfile'])->middleware('jwt.permission:edit_users');
    Route::patch('/users/me/fundi-profile', [UserController::class, 'updateFundiProfile'])->middleware('jwt.permission:edit_users');
    Route::get('/users/fundi/{fundiId}', [UserController::class, 'getFundiProfile'])->middleware('jwt.permission:view_fundis');

    // Fundi Application routes
    Route::get('/fundi-applications/requirements', [FundiApplicationController::class, 'getRequirements'])->middleware('jwt.permission:view_fundis');
    Route::get('/fundi-applications/progress', [FundiApplicationController::class, 'getProgress'])->middleware('jwt.permission:view_fundis');
    Route::get('/fundi-applications/sections/{sectionName}', [FundiApplicationController::class, 'getSection'])->middleware('jwt.permission:view_fundis');
    Route::post('/fundi-applications/sections', [FundiApplicationController::class, 'submitSection'])->middleware('jwt.permission:manage_fundis');
    Route::post('/fundi-applications/submit', [FundiApplicationController::class, 'submitFinalApplication'])->middleware('jwt.permission:manage_fundis');
    Route::post('/fundi-applications', [FundiApplicationController::class, 'store'])->middleware('jwt.permission:manage_fundis');
    Route::get('/fundi-applications/status', [FundiApplicationController::class, 'getStatus'])->middleware('jwt.permission:view_fundis');
    Route::delete('/fundi-applications/{id}', [FundiApplicationController::class, 'destroy'])->middleware('jwt.permission:manage_fundis');


    // Job routes (role/payment checks handled in controllers)
    Route::get('/jobs', [JobController::class, 'index'])->middleware('jwt.permission:view_jobs');
    // Alias for mobile: returns authenticated user's jobs (customers) or scoped jobs
    Route::get('/jobs/my-jobs', [JobController::class, 'index'])->middleware('jwt.permission:view_jobs');
    Route::post('/jobs', [JobController::class, 'store'])->middleware('jwt.permission:create_jobs');
    Route::get('/jobs/{id}', [JobController::class, 'show'])->middleware('jwt.permission:view_jobs');
    Route::patch('/jobs/{id}', [JobController::class, 'update'])->middleware('jwt.permission:edit_jobs');
    Route::delete('/jobs/{id}', [JobController::class, 'destroy'])->middleware('jwt.permission:delete_jobs');

    // Job Application routes
    Route::post('/jobs/{jobId}/apply', [JobApplicationController::class, 'apply'])->middleware('jwt.permission:apply_jobs');
    Route::get('/jobs/{jobId}/applications', [JobApplicationController::class, 'getJobApplications'])->middleware('jwt.permission:manage_job_applications');
    Route::get('/job-applications/my-applications', [JobApplicationController::class, 'getMyApplications'])->middleware('jwt.permission:apply_jobs');

    // Portfolio routes
    Route::post('/portfolio', [PortfolioController::class, 'store'])->middleware('jwt.permission:create_portfolio');
    Route::get('/portfolio/my-portfolio', [PortfolioController::class, 'getMyPortfolio'])->middleware('jwt.permission:view_portfolio');
    Route::get('/portfolio/status', [PortfolioController::class, 'getPortfolioStatus'])->middleware('jwt.permission:view_portfolio');
    Route::get('/portfolio/{fundiId}', [PortfolioController::class, 'getFundiPortfolio'])->middleware('jwt.permission:view_portfolio');
    Route::patch('/portfolio/{id}', [PortfolioController::class, 'update'])->middleware('jwt.permission:edit_portfolio');
    Route::delete('/portfolio/{id}', [PortfolioController::class, 'destroy'])->middleware('jwt.permission:delete_portfolio');

    // Feed routes (role-checked inside controller, no extra permission required)
    Route::get('/feeds/fundis', [FeedController::class, 'getFundiFeed'])->middleware('jwt.permission:view_fundis');
    Route::get('/feeds/jobs', [FeedController::class, 'getJobFeed']);
    Route::get('/feeds/fundis/{id}', [FeedController::class, 'getFundiProfile'])->middleware('jwt.permission:view_fundis');
    Route::get('/feeds/jobs/{id}', [FeedController::class, 'getJobDetails'])->middleware('jwt.permission:view_job_feeds');
    Route::get('/feeds/nearby-fundis', [FeedController::class, 'getNearbyFundis'])->middleware('jwt.permission:view_fundis');

    // Work Approval routes
    Route::get('/work-approval/portfolio-pending', [WorkApprovalController::class, 'getPendingPortfolioItems'])->middleware('jwt.permission:view_work_submissions');
    Route::post('/work-approval/portfolio/{id}/approve', [WorkApprovalController::class, 'approvePortfolioItem'])->middleware('jwt.permission:approve_work');
    Route::post('/work-approval/portfolio/{id}/reject', [WorkApprovalController::class, 'rejectPortfolioItem'])->middleware('jwt.permission:reject_work');
    Route::get('/work-approval/submissions-pending', [WorkApprovalController::class, 'getPendingWorkSubmissions'])->middleware('jwt.permission:view_work_submissions');
    Route::post('/work-approval/submissions/{id}/approve', [WorkApprovalController::class, 'approveWorkSubmission'])->middleware('jwt.permission:approve_work');
    Route::post('/work-approval/submissions/{id}/reject', [WorkApprovalController::class, 'rejectWorkSubmission'])->middleware('jwt.permission:reject_work');

    // Payment routes
    Route::get('/payments/current-plan', [PaymentController::class, 'getCurrentPlan'])->middleware('jwt.permission:view_payments');
    Route::get('/payments/plans', [PaymentController::class, 'getAvailablePlans'])->middleware('jwt.permission:view_payments');
    Route::post('/payments/subscribe', [PaymentController::class, 'subscribe'])->middleware('jwt.permission:process_payments');
    Route::post('/payments/cancel-subscription', [PaymentController::class, 'cancelSubscription'])->middleware('jwt.permission:process_payments');
    Route::get('/payments/history', [PaymentController::class, 'getPaymentHistory'])->middleware('jwt.permission:view_payments');
    // Alias for mobile expecting /payments/user
    Route::get('/payments/user', [PaymentController::class, 'getPaymentHistory'])->middleware('jwt.permission:view_payments');
    Route::post('/payments/check-permission', [PaymentController::class, 'checkActionPermission'])->middleware('jwt.permission:view_payments');
    Route::post('/payments/pay-per-use', [PaymentController::class, 'processPayPerUse'])->middleware('jwt.permission:process_payments');

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->middleware('jwt.permission:view_notifications');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->middleware('jwt.permission:view_notifications');
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->middleware('jwt.permission:view_notifications');
    Route::delete('/notifications/{id}', [NotificationController::class, 'delete'])->middleware('jwt.permission:delete_notifications');
    Route::delete('/notifications/clear-all', [NotificationController::class, 'clearAll'])->middleware('jwt.permission:delete_notifications');
    Route::get('/notifications/settings', [NotificationController::class, 'getSettings'])->middleware('jwt.permission:view_notifications');
    Route::put('/notifications/settings', [NotificationController::class, 'updateSettings'])->middleware('jwt.permission:manage_notifications');
    Route::post('/notifications/test', [NotificationController::class, 'sendTest'])->middleware('jwt.permission:send_notifications');

    // Rating routes
    Route::post('/ratings', [RatingController::class, 'store'])->middleware('jwt.permission:create_ratings');
    Route::get('/ratings/fundi/{fundiId}', [RatingController::class, 'getFundiRatings'])->middleware('jwt.permission:view_ratings');
    Route::get('/ratings/my-ratings', [RatingController::class, 'getMyRatings'])->middleware('jwt.permission:view_ratings');
    Route::put('/ratings/{id}', [RatingController::class, 'update'])->middleware('jwt.permission:edit_ratings');
    Route::delete('/ratings/{id}', [RatingController::class, 'delete'])->middleware('jwt.permission:delete_ratings');

    // Settings routes
    Route::get('/settings', [SettingsController::class, 'index'])->middleware('jwt.permission:view_system_settings');
    Route::put('/settings', [SettingsController::class, 'update'])->middleware('jwt.permission:manage_system_settings');
    Route::patch('/settings/{key}', [SettingsController::class, 'updateKey'])->middleware('jwt.permission:manage_system_settings');
    Route::post('/settings/reset', [SettingsController::class, 'reset'])->middleware('jwt.permission:manage_system_settings');
    Route::get('/settings/export', [SettingsController::class, 'export'])->middleware('jwt.permission:view_system_settings');
    Route::post('/settings/import', [SettingsController::class, 'import'])->middleware('jwt.permission:manage_system_settings');
    Route::get('/settings/themes', [SettingsController::class, 'getThemes'])->middleware('jwt.permission:view_system_settings');
    Route::get('/settings/languages', [SettingsController::class, 'getLanguages'])->middleware('jwt.permission:view_system_settings');
    Route::put('/settings/privacy', [SettingsController::class, 'updatePrivacy'])->middleware('jwt.permission:manage_system_settings');
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotificationSettings'])->middleware('jwt.permission:manage_system_settings');

    // Dashboard routes removed (not required for mobile)

    // Admin routes
    Route::middleware(['role:admin'])->group(function () {
        // User management
        Route::get('/admin/users', [AdminController::class, 'getUsers']);
        Route::get('/admin/users/{id}', [AdminController::class, 'getUser']);
        Route::patch('/admin/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser']);

        // Role management
        Route::get('/admin/roles/users', [AdminRoleController::class, 'getUsersWithRoles']);
        Route::get('/admin/roles/users/{id}', [AdminRoleController::class, 'getUserRoles']);
        Route::post('/admin/roles/users/{id}/add', [AdminRoleController::class, 'addRole']);
        Route::delete('/admin/roles/users/{id}/remove', [AdminRoleController::class, 'removeRole']);
        Route::put('/admin/roles/users/{id}/set', [AdminRoleController::class, 'setRoles']);
        Route::post('/admin/roles/users/{id}/promote-fundi', [AdminRoleController::class, 'promoteToFundi']);
        Route::post('/admin/roles/users/{id}/promote-admin', [AdminRoleController::class, 'promoteToAdmin']);
        Route::post('/admin/roles/users/{id}/demote-customer', [AdminRoleController::class, 'demoteToCustomer']);
        Route::get('/admin/roles/statistics', [AdminRoleController::class, 'getRoleStatistics']);
        Route::get('/admin/roles/available', [AdminRoleController::class, 'getAvailableRoles']);

        // Role management
        Route::get('/admin/roles', [AdminRoleController::class, 'getAllRoles']);
        Route::post('/admin/roles', [AdminRoleController::class, 'createRole']);
        Route::get('/admin/roles/{id}', [AdminRoleController::class, 'getRoleDetails']);
        Route::put('/admin/roles/{id}', [AdminRoleController::class, 'updateRole']);
        Route::delete('/admin/roles/{id}', [AdminRoleController::class, 'deleteRole']);

        // Permission management
        Route::get('/admin/permissions', [AdminRoleController::class, 'getAllPermissions']);
        Route::post('/admin/permissions', [AdminRoleController::class, 'createPermission']);

        // Fundi Application management
        Route::get('/fundi-applications', [FundiApplicationController::class, 'index']);
        Route::patch('/fundi-applications/{id}/status', [FundiApplicationController::class, 'updateStatus']);

        // Job management
        Route::get('/admin/jobs', [AdminController::class, 'getJobs']);
        Route::post('/admin/jobs', [AdminController::class, 'createJob']);
        Route::get('/admin/jobs/{id}', [AdminController::class, 'getJob']);
        Route::patch('/admin/jobs/{id}', [AdminController::class, 'updateJob']);
        Route::delete('/admin/jobs/{id}', [AdminController::class, 'deleteJob']);

        // Job Application management
        Route::get('/admin/job-applications', [AdminController::class, 'getJobApplications']);
        Route::patch('/admin/job-applications/{id}', [AdminController::class, 'updateJobApplication']);

        // Portfolio management
        Route::get('/admin/portfolio', [AdminController::class, 'getPortfolios']);
        Route::patch('/admin/portfolio/{id}', [AdminController::class, 'updatePortfolio']);
        Route::delete('/admin/portfolio/{id}', [AdminController::class, 'deletePortfolio']);

        // Category management
        Route::get('/admin/categories', [AdminController::class, 'getCategories']);
        Route::post('/admin/categories', [AdminController::class, 'createCategory']);
        Route::patch('/admin/categories/{id}', [AdminController::class, 'updateCategory']);
        Route::delete('/admin/categories/{id}', [AdminController::class, 'deleteCategory']);

        // Payment management
        Route::get('/admin/payments', [AdminController::class, 'getPayments']);
        Route::patch('/admin/payments/{id}', [AdminController::class, 'updatePayment']);
        Route::get('/admin/payments/reports', [AdminController::class, 'getPaymentReports']);

        // Notification management
        Route::post('/admin/notifications', [AdminController::class, 'sendNotification']);
        Route::patch('/admin/notifications/{id}', [AdminController::class, 'updateNotification']);
        Route::delete('/admin/notifications/{id}', [AdminController::class, 'deleteNotification']);

        // Payment management
        Route::get('/admin/payment-plans', [AdminPaymentController::class, 'getPaymentPlans']);
        Route::post('/admin/payment-plans', [AdminPaymentController::class, 'createPaymentPlan']);
        Route::put('/admin/payment-plans/{id}', [AdminPaymentController::class, 'updatePaymentPlan']);
        Route::delete('/admin/payment-plans/{id}', [AdminPaymentController::class, 'deletePaymentPlan']);
        Route::patch('/admin/payment-plans/{id}/toggle-status', [AdminPaymentController::class, 'togglePlanStatus']);
        Route::get('/admin/payment-statistics', [AdminPaymentController::class, 'getPaymentStatistics']);
        Route::get('/admin/user-subscriptions', [AdminPaymentController::class, 'getUserSubscriptions']);
        Route::get('/admin/payment-transactions', [AdminPaymentController::class, 'getPaymentTransactions']);

        // System monitoring
        Route::get('/admin/monitor/active-users', [AdminController::class, 'getActiveUsers']);
        Route::get('/admin/monitor/jobs-summary', [AdminController::class, 'getJobsSummary']);
        Route::get('/admin/monitor/payments-summary', [AdminController::class, 'getPaymentsSummary']);
        Route::get('/admin/monitor/system-health', [AdminController::class, 'getSystemHealth']);
        Route::get('/admin/monitor/api-logs', [AdminController::class, 'getApiLogs']);
        Route::get('/admin/monitor/api-logs/export', [AdminController::class, 'exportApiLogs']);
        Route::get('/admin/audit-logs', [AdminController::class, 'getAuditLogs']);
        Route::get('/admin/sessions', [AdminController::class, 'getSessions']);
        Route::delete('/admin/sessions/{id}', [AdminController::class, 'forceLogout']);
        Route::get('/admin/logs', [AdminController::class, 'getLaravelLogs']);
    });
});

// Fallback routes for error handling
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'error' => 'The requested API endpoint does not exist',
        'path' => request()->path(),
        'method' => request()->method(),
        'timestamp' => now()->toISOString()
    ], 404);
});
