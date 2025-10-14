<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JWTAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FundiController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AdminRoleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\WorkApprovalController;
use App\Http\Controllers\FundiApplicationController;
use App\Http\Controllers\AdminSettingController;

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
Route::post('/auth/login', [JWTAuthController::class, 'login']);
Route::post('/auth/register', [JWTAuthController::class, 'register']);
Route::post('/auth/refresh', [JWTAuthController::class, 'refresh']);
Route::post('/auth/forgot-password', [JWTAuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [JWTAuthController::class, 'resetPassword']);
Route::post('/auth/send-otp', [JWTAuthController::class, 'sendOtp']);
Route::post('/auth/verify-otp', [JWTAuthController::class, 'verifyOtp']);

// ZenoPay Webhook (public - called by ZenoPay servers)
Route::post('/payments/zenopay/webhook', [PaymentController::class, 'zenoPayWebhook']);

// Protected routes (require JWT authentication)
Route::middleware('jwt.auth')->group(function () {
    // User routes
    Route::get('/users/me', [UserController::class, 'me']);
    Route::patch('/users/me', [UserController::class, 'update']);
    Route::delete('/users/me', [UserController::class, 'delete']);
    Route::post('/auth/change-password', [JWTAuthController::class, 'changePassword']);
    
    // User Settings routes
    Route::get('/settings', [SettingsController::class, 'getUserSettings']);
    Route::patch('/settings', [SettingsController::class, 'updateUserSettings']);
    Route::get('/settings/{key}', [SettingsController::class, 'getSetting']);
    Route::post('/settings/reset', [SettingsController::class, 'resetToDefaults']);
    Route::get('/settings/export', [SettingsController::class, 'exportSettings']);
    Route::post('/settings/import', [SettingsController::class, 'importSettings']);
    Route::get('/settings/themes', [SettingsController::class, 'getThemes']);
    Route::get('/settings/languages', [SettingsController::class, 'getLanguages']);
    Route::put('/settings/privacy', [SettingsController::class, 'updatePrivacy']);
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotificationPreferences']);
    
    // Job routes (role/payment checks handled in controllers)
    // Available jobs - ONLY for fundis to browse and apply
    Route::get('/jobs', [JobController::class, 'index'])->middleware('jwt.permission:view_jobs');
    // User's own jobs - CUSTOMERS see their posted jobs, FUNDIS see jobs they applied to
    Route::get('/jobs/my-jobs', [JobController::class, 'myJobs']); // No permission check - all users can see their own
    // Create new job - ONLY for customers
    Route::post('/jobs', [JobController::class, 'store'])->middleware('jwt.permission:create_jobs');
    // View specific job - Accessible by job owner OR users with view_jobs permission (checked in controller)
    Route::get('/jobs/{id}', [JobController::class, 'show']); // Permission checked in controller
    // Update job - ONLY job owner (customer who posted it)
    Route::patch('/jobs/{id}', [JobController::class, 'update'])->middleware('jwt.permission:edit_jobs');
    // Delete job - ONLY job owner (customer who posted it)
    Route::delete('/jobs/{id}', [JobController::class, 'destroy'])->middleware('jwt.permission:delete_jobs');
    
    // Feed routes - Job feeds are ONLY for fundis to browse
    Route::get('/feeds/jobs', [FeedController::class, 'getJobFeed'])->middleware('jwt.permission:view_jobs');
    Route::get('/feeds/fundis', [FeedController::class, 'getFundiFeed'])->middleware('jwt.permission:view_fundis');
    Route::get('/feeds/fundis/{id}', [FeedController::class, 'getFundiProfile'])->middleware('jwt.permission:view_fundis');
    Route::get('/feeds/jobs/{id}', [FeedController::class, 'getJobDetails'])->middleware('jwt.permission:view_jobs'); // Fundis only
    
    // Category routes (public)
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    
    // Fundi routes
    Route::get('/fundis', [FundiController::class, 'index'])->middleware('jwt.permission:view_fundis');
    Route::get('/fundis/{id}', [FundiController::class, 'show'])->middleware('jwt.permission:view_fundis');
    Route::post('/fundis', [FundiController::class, 'store'])->middleware('jwt.permission:create_fundis');
    Route::patch('/fundis/{id}', [FundiController::class, 'update'])->middleware('jwt.permission:edit_fundis');
    Route::delete('/fundis/{id}', [FundiController::class, 'destroy'])->middleware('jwt.permission:delete_fundis');
    
    // Application routes
    Route::post('/jobs/{jobId}/apply', [JobApplicationController::class, 'apply'])->middleware('jwt.permission:apply_jobs');
    Route::get('/job-applications/my-applications', [JobApplicationController::class, 'getMyApplications'])->middleware('jwt.permission:view_applications');
    Route::get('/jobs/{jobId}/applications', [JobApplicationController::class, 'getJobApplications'])->middleware('jwt.permission:view_applications');
    Route::patch('/job-applications/{id}/status', [JobApplicationController::class, 'updateApplicationStatus'])->middleware('jwt.permission:manage_applications');
    
    // Payment routes
    Route::get('/payments/plans', [PaymentController::class, 'getPlans'])->middleware('jwt.permission:view_payments');
    Route::get('/payments/current-plan', [PaymentController::class, 'getCurrentPlan'])->middleware('jwt.permission:view_payments');
    Route::post('/payments/subscribe', [PaymentController::class, 'subscribe'])->middleware('jwt.permission:make_payments');
    Route::post('/payments/check-requirement', [PaymentController::class, 'checkRequirement'])->middleware('jwt.permission:view_payments');
    Route::post('/payments/create', [PaymentController::class, 'createPayment'])->middleware('jwt.permission:make_payments');
    Route::post('/payments/cancel', [PaymentController::class, 'cancelPayment'])->middleware('jwt.permission:make_payments');
    Route::post('/payments/cancel-subscription', [PaymentController::class, 'cancelSubscription'])->middleware('jwt.permission:make_payments');
    Route::get('/payments/history', [PaymentController::class, 'getHistory'])->middleware('jwt.permission:view_payments');
    Route::post('/payments/check-permission', [PaymentController::class, 'checkPermission'])->middleware('jwt.permission:view_payments');
    Route::post('/payments/pay-per-use', [PaymentController::class, 'payPerUse'])->middleware('jwt.permission:make_payments');
    Route::get('/payments/user', [PaymentController::class, 'getUserPayments'])->middleware('jwt.permission:view_payments');
    Route::get('/payments/config', [PaymentController::class, 'getConfig'])->middleware('jwt.permission:view_payments');
    Route::get('/payments/verify/{transactionId}', [PaymentController::class, 'verifyPayment'])->middleware('jwt.permission:view_payments');
    Route::get('/payments/receipt/{transactionId}', [PaymentController::class, 'getTransactionReceipt'])->middleware('jwt.permission:view_payments');
    
    // ZenoPay Mobile Money Integration (Tanzania)
    Route::post('/payments/zenopay/initiate', [PaymentController::class, 'initiateMobileMoneyPayment'])->middleware('jwt.permission:make_payments');
    Route::get('/payments/zenopay/status/{orderId}', [PaymentController::class, 'checkZenoPayStatus'])->middleware('jwt.permission:view_payments');
    Route::get('/payments/zenopay/providers', [PaymentController::class, 'getMobileMoneyProviders']);
    
    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->middleware('jwt.permission:view_notifications');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->middleware('jwt.permission:manage_notifications');
    Route::delete('/notifications/{id}', [NotificationController::class, 'delete'])->middleware('jwt.permission:manage_notifications');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->middleware('jwt.permission:manage_notifications');
    Route::delete('/notifications/clear-all', [NotificationController::class, 'clearAll'])->middleware('jwt.permission:manage_notifications');
    Route::get('/notifications/settings', [NotificationController::class, 'getSettings'])->middleware('jwt.permission:view_notifications');
    Route::post('/notifications/test', [NotificationController::class, 'sendTestNotification'])->middleware('jwt.permission:manage_notifications');
    
    // Search suggestions route
    Route::get('/search/suggestions', [FeedController::class, 'getSearchSuggestions']);
    
    // Portfolio routes
    Route::get('/portfolio/my-portfolio', [PortfolioController::class, 'getMyPortfolio'])->middleware('jwt.permission:view_portfolio');
    Route::get('/portfolio/status', [PortfolioController::class, 'getPortfolioStatus'])->middleware('jwt.permission:view_portfolio');
    Route::get('/portfolio/{fundiId}', [PortfolioController::class, 'getFundiPortfolio'])->middleware('jwt.permission:view_portfolio');
    Route::post('/portfolio', [PortfolioController::class, 'store'])->middleware('jwt.permission:create_portfolio');
    Route::patch('/portfolio/{id}', [PortfolioController::class, 'update'])->middleware('jwt.permission:edit_portfolio');
    Route::delete('/portfolio/{id}', [PortfolioController::class, 'destroy'])->middleware('jwt.permission:delete_portfolio');
    
    // Rating routes
    Route::post('/ratings', [RatingController::class, 'store'])->middleware('jwt.permission:create_ratings');
    Route::get('/ratings/my-ratings', [RatingController::class, 'getMyRatings'])->middleware('jwt.permission:view_ratings');
    Route::get('/ratings/fundi/{fundiId}', [RatingController::class, 'getFundiRatings'])->middleware('jwt.permission:view_ratings');
    Route::patch('/ratings/{id}', [RatingController::class, 'update'])->middleware('jwt.permission:edit_ratings');
    Route::delete('/ratings/{id}', [RatingController::class, 'delete'])->middleware('jwt.permission:delete_ratings');
    
    // Work Approval routes
    Route::get('/work-approval/portfolio-pending', [WorkApprovalController::class, 'getPendingPortfolioItems'])->middleware('jwt.permission:approve_work');
    Route::get('/work-approval/submissions-pending', [WorkApprovalController::class, 'getPendingWorkSubmissions'])->middleware('jwt.permission:approve_work');
    Route::post('/work-approval/portfolio/{id}/approve', [WorkApprovalController::class, 'approvePortfolioItem'])->middleware('jwt.permission:approve_work');
    Route::post('/work-approval/portfolio/{id}/reject', [WorkApprovalController::class, 'rejectPortfolioItem'])->middleware('jwt.permission:approve_work');
    Route::post('/work-approval/submissions/{id}/approve', [WorkApprovalController::class, 'approveWorkSubmission'])->middleware('jwt.permission:approve_work');
    Route::post('/work-approval/submissions/{id}/reject', [WorkApprovalController::class, 'rejectWorkSubmission'])->middleware('jwt.permission:approve_work');
    
    // Fundi Application routes
    Route::get('/fundi-applications/requirements', [FundiApplicationController::class, 'getRequirements']);
    Route::get('/fundi-applications/status', [FundiApplicationController::class, 'getStatus'])->middleware('jwt.permission:view_applications');
    Route::get('/fundi-applications/progress', [FundiApplicationController::class, 'getProgress'])->middleware('jwt.permission:view_applications');
    Route::get('/fundi-applications/sections/{sectionName}', [FundiApplicationController::class, 'getSection'])->middleware('jwt.permission:view_applications');
    Route::post('/fundi-applications/sections', [FundiApplicationController::class, 'submitSection'])->middleware('jwt.permission:create_applications');
    Route::post('/fundi-applications/submit', [FundiApplicationController::class, 'submitFinalApplication'])->middleware('jwt.permission:create_applications');
    Route::post('/fundi-applications', [FundiApplicationController::class, 'store'])->middleware('jwt.permission:create_applications');
    Route::get('/fundi-applications', [FundiApplicationController::class, 'index'])->middleware('jwt.permission:view_all_applications');
    Route::get('/fundi-applications/{id}', [FundiApplicationController::class, 'show'])->middleware('jwt.permission:view_applications');
    Route::patch('/fundi-applications/{id}/status', [FundiApplicationController::class, 'updateStatus'])->middleware('jwt.permission:manage_applications');
    Route::delete('/fundi-applications/{id}', [FundiApplicationController::class, 'destroy'])->middleware('jwt.permission:delete_applications');
    
    // Dashboard routes
    Route::get('/dashboard/overview', [DashboardController::class, 'getOverview'])->middleware('jwt.permission:view_dashboard');
    Route::get('/dashboard/job-statistics', [DashboardController::class, 'getJobStatistics'])->middleware('jwt.permission:view_dashboard');
    Route::get('/dashboard/payment-statistics', [DashboardController::class, 'getPaymentStatistics'])->middleware('jwt.permission:view_dashboard');
    Route::get('/dashboard/application-statistics', [DashboardController::class, 'getApplicationStatistics'])->middleware('jwt.permission:view_dashboard');
    
    // File Upload routes
    Route::post('/upload/portfolio-media', [FileUploadController::class, 'uploadPortfolioMedia'])->middleware('jwt.permission:upload_files');
    Route::post('/upload/job-media', [FileUploadController::class, 'uploadJobMedia'])->middleware('jwt.permission:upload_files');
    Route::post('/upload/profile-document', [FileUploadController::class, 'uploadProfileDocument'])->middleware('jwt.permission:upload_files');
    Route::delete('/upload/media/{id}', [FileUploadController::class, 'deleteMedia'])->middleware('jwt.permission:delete_files');
    Route::get('/upload/media/{id}/url', [FileUploadController::class, 'getMediaUrl']);
    
    // Admin routes (admin only)
    Route::middleware('jwt.permission:admin_access')->group(function () {
        // User Management
        Route::get('/admin/users', [AdminController::class, 'getUsers']);
        Route::get('/admin/users/{id}', [AdminController::class, 'getUser']);
        Route::patch('/admin/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser']);
        Route::get('/admin/users/stats', [AdminController::class, 'getUserStats']);
        
        // Job Management
        Route::get('/admin/jobs', [AdminController::class, 'getJobs']);
        Route::get('/admin/jobs/{id}', [AdminController::class, 'getJob']);
        Route::patch('/admin/jobs/{id}', [AdminController::class, 'updateJob']);
        Route::delete('/admin/jobs/{id}', [AdminController::class, 'deleteJob']);
        Route::get('/admin/jobs/stats', [AdminController::class, 'getJobStats']);
        
        // Payment Management
        Route::get('/admin/payments', [AdminController::class, 'getPayments']);
        Route::get('/admin/payments/{id}', [AdminController::class, 'getPayment']);
        Route::patch('/admin/payments/{id}', [AdminController::class, 'updatePayment']);
        Route::get('/admin/payments/revenue', [AdminController::class, 'getRevenueStats']);
        
        // System Monitoring (MonitoringController)
        Route::get('/admin/monitor/active-users', [MonitoringController::class, 'getActiveUsers']);
        Route::get('/admin/monitor/jobs-summary', [MonitoringController::class, 'getJobsSummary']);
        Route::get('/admin/monitor/payments-summary', [MonitoringController::class, 'getPaymentsSummary']);
        Route::get('/admin/monitor/system-health', [MonitoringController::class, 'getSystemHealth']);
        Route::get('/admin/monitor/api-logs', [MonitoringController::class, 'getApiLogs']);
        Route::get('/admin/logs', [MonitoringController::class, 'getLaravelLogs']);
        Route::get('/admin/sessions/active', [AdminController::class, 'getActiveSessions']);
        Route::post('/admin/sessions/{id}/logout', [AdminController::class, 'forceLogout']);
        Route::get('/admin/system/health', [AdminController::class, 'getSystemHealth']);
        Route::get('/admin/api-logs', [AdminController::class, 'getApiLogs']);
        
        // Audit Logs (AuditController)
        Route::get('/admin/audit-logs', [AuditController::class, 'index']);
        Route::get('/admin/audit-logs/{id}', [AuditController::class, 'show']);
        Route::get('/admin/audit-logs/export', [AuditController::class, 'export']);
        Route::get('/admin/audit-logs/statistics', [AuditController::class, 'statistics']);
        Route::get('/admin/audit-logs/failed-actions', [AuditController::class, 'failedActions']);
        Route::get('/admin/audit-logs/user/{userId}', [AuditController::class, 'userActivity']);
        Route::get('/admin/audit-logs/security-events', [AuditController::class, 'securityEvents']);
        Route::get('/admin/audit-logs/api-errors', [AuditController::class, 'apiErrors']);
        
        // Role & Permission Management (AdminRoleController)
        Route::get('/admin/roles', [AdminRoleController::class, 'index']);
        Route::post('/admin/roles', [AdminRoleController::class, 'store']);
        Route::get('/admin/roles/{id}', [AdminRoleController::class, 'show']);
        Route::put('/admin/roles/{id}', [AdminRoleController::class, 'update']);
        Route::delete('/admin/roles/{id}', [AdminRoleController::class, 'destroy']);
        Route::get('/admin/permissions', [AdminRoleController::class, 'getPermissions']);
        Route::post('/admin/permissions', [AdminRoleController::class, 'createPermission']);
        Route::get('/admin/roles/users', [AdminRoleController::class, 'getUsersWithRoles']);
        Route::get('/admin/roles/users/{id}', [AdminRoleController::class, 'getUserRoles']);
        Route::post('/admin/roles/users/{id}/add', [AdminRoleController::class, 'addRoleToUser']);
        Route::delete('/admin/roles/users/{id}/remove', [AdminRoleController::class, 'removeRoleFromUser']);
        Route::put('/admin/roles/users/{id}/set', [AdminRoleController::class, 'setUserRoles']);
        Route::get('/admin/roles/statistics', [AdminRoleController::class, 'getRoleStatistics']);
        Route::get('/admin/roles/available', [AdminRoleController::class, 'getAvailableRoles']);
        
        // Category Management (Admin)
        Route::get('/admin/categories', [CategoryController::class, 'adminIndex']);
        Route::post('/admin/categories', [CategoryController::class, 'store']);
        Route::patch('/admin/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/admin/categories/{id}', [CategoryController::class, 'destroy']);
        
        // Portfolio Management (Admin)
        Route::get('/admin/portfolio', [PortfolioController::class, 'adminIndex']);
        Route::patch('/admin/portfolio/{id}', [PortfolioController::class, 'adminUpdate']);
        Route::delete('/admin/portfolio/{id}', [PortfolioController::class, 'adminDestroy']);
        
        // Payment Plan Management (Admin)
        Route::get('/admin/payment-plans', [AdminPaymentController::class, 'getPlans']);
        Route::post('/admin/payment-plans', [AdminPaymentController::class, 'createPlan']);
        Route::get('/admin/payment-plans/{id}', [AdminPaymentController::class, 'getPlan']);
        Route::put('/admin/payment-plans/{id}', [AdminPaymentController::class, 'updatePlan']);
        Route::delete('/admin/payment-plans/{id}', [AdminPaymentController::class, 'deletePlan']);
        Route::patch('/admin/payment-plans/{id}/toggle-status', [AdminPaymentController::class, 'togglePlanStatus']);
        Route::get('/admin/user-subscriptions', [AdminPaymentController::class, 'getUserSubscriptions']);
        Route::get('/admin/payment-transactions', [AdminPaymentController::class, 'getTransactions']);
        Route::get('/admin/payment-transactions/export', [AdminPaymentController::class, 'exportTransactions']);
        Route::get('/admin/payment-transactions/{id}', [AdminPaymentController::class, 'getTransactionDetails']);
        Route::get('/admin/payment-statistics', [AdminPaymentController::class, 'getStatistics']);
        
        // Notification Management (Admin)
        Route::get('/admin/notifications', [NotificationController::class, 'adminIndex']);
        Route::post('/admin/notifications/send', [NotificationController::class, 'sendNotification']);
        Route::patch('/admin/notifications/{id}', [NotificationController::class, 'adminUpdate']);
        Route::delete('/admin/notifications/{id}', [NotificationController::class, 'adminDelete']);
        
        // Dashboard (Admin - alias for existing routes)
        Route::get('/admin/dashboard/stats', [DashboardController::class, 'getOverview']);
        Route::get('/admin/dashboard/recent-activities', [AdminController::class, 'getRecentActivities']);
        Route::get('/admin/dashboard/charts/{type}', [DashboardController::class, 'getJobStatistics']);
        
        // Settings (Admin)
        Route::get('/admin/settings', [AdminController::class, 'getSettings']);
        Route::patch('/admin/settings', [AdminController::class, 'updateSettings']);
        
        // Admin Settings - Pricing Management
        Route::get('/admin/settings/pricing', [AdminSettingController::class, 'getPricing']);
        Route::patch('/admin/settings/pricing', [AdminSettingController::class, 'updatePricing']);
        Route::post('/admin/settings/reset-defaults', [AdminSettingController::class, 'resetToDefaults']);
    });
});
