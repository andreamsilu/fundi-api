<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FundiApplicationController;
use App\Http\Controllers\WorkApprovalController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AdminRoleController;

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
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    // Authentication routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // User routes
    Route::get('/users/me', [UserController::class, 'me']);
    Route::patch('/users/me/fundi-profile', [UserController::class, 'updateFundiProfile']);
    Route::get('/users/fundi/{fundiId}', [UserController::class, 'getFundiProfile']);

    // Fundi Application routes
    Route::get('/fundi-applications/requirements', [FundiApplicationController::class, 'getRequirements']);
    Route::get('/fundi-applications/progress', [FundiApplicationController::class, 'getProgress']);
    Route::get('/fundi-applications/sections/{sectionName}', [FundiApplicationController::class, 'getSection']);
    Route::post('/fundi-applications/sections', [FundiApplicationController::class, 'submitSection']);
    Route::post('/fundi-applications/submit', [FundiApplicationController::class, 'submitFinalApplication']);
    Route::post('/fundi-applications', [FundiApplicationController::class, 'store']);
    Route::get('/fundi-applications/status', [FundiApplicationController::class, 'getStatus']);
    Route::delete('/fundi-applications/{id}', [FundiApplicationController::class, 'destroy']);

    // Category routes
    Route::get('/categories', [CategoryController::class, 'index']);

    // Job routes
    Route::get('/jobs', [JobController::class, 'index'])->middleware('permission:view_jobs');
    Route::post('/jobs', [JobController::class, 'store'])->middleware('permission:create_jobs');
    Route::get('/jobs/{id}', [JobController::class, 'show'])->middleware('permission:view_jobs');
    Route::patch('/jobs/{id}', [JobController::class, 'update'])->middleware('permission:edit_jobs');
    Route::delete('/jobs/{id}', [JobController::class, 'destroy'])->middleware('permission:delete_jobs');

    // Job Application routes
    Route::post('/jobs/{jobId}/apply', [JobApplicationController::class, 'apply'])->middleware('permission:apply_jobs');
    Route::get('/jobs/{jobId}/applications', [JobApplicationController::class, 'getJobApplications'])->middleware('permission:manage_job_applications');
    Route::get('/job-applications/my-applications', [JobApplicationController::class, 'getMyApplications'])->middleware('permission:apply_jobs');

    // Portfolio routes
    Route::post('/portfolio', [PortfolioController::class, 'store'])->middleware('permission:create_portfolio');
    Route::get('/portfolio/my-portfolio', [PortfolioController::class, 'getMyPortfolio'])->middleware('permission:view_portfolio');
    Route::get('/portfolio/status', [PortfolioController::class, 'getPortfolioStatus'])->middleware('permission:view_portfolio');
    Route::get('/portfolio/{fundiId}', [PortfolioController::class, 'getFundiPortfolio'])->middleware('permission:view_portfolio');
    Route::patch('/portfolio/{id}', [PortfolioController::class, 'update'])->middleware('permission:edit_portfolio');
    Route::delete('/portfolio/{id}', [PortfolioController::class, 'destroy'])->middleware('permission:delete_portfolio');

    // Feed routes
    Route::get('/feeds/fundis', [FeedController::class, 'getFundiFeed'])->middleware('permission:view_fundi_feeds');
    Route::get('/feeds/jobs', [FeedController::class, 'getJobFeed'])->middleware('permission:view_job_feeds');
    Route::get('/feeds/fundis/{id}', [FeedController::class, 'getFundiProfile'])->middleware('permission:view_fundi_feeds');
    Route::get('/feeds/jobs/{id}', [FeedController::class, 'getJobDetails'])->middleware('permission:view_job_feeds');
    Route::get('/feeds/nearby-fundis', [FeedController::class, 'getNearbyFundis'])->middleware('permission:view_nearby_fundis');

    // Work Approval routes
    Route::get('/work-approval/portfolio-pending', [WorkApprovalController::class, 'getPendingPortfolioItems'])->middleware('permission:view_work_submissions');
    Route::post('/work-approval/portfolio/{id}/approve', [WorkApprovalController::class, 'approvePortfolioItem'])->middleware('permission:approve_work');
    Route::post('/work-approval/portfolio/{id}/reject', [WorkApprovalController::class, 'rejectPortfolioItem'])->middleware('permission:reject_work');
    Route::get('/work-approval/submissions-pending', [WorkApprovalController::class, 'getPendingWorkSubmissions'])->middleware('permission:view_work_submissions');
    Route::post('/work-approval/submissions/{id}/approve', [WorkApprovalController::class, 'approveWorkSubmission'])->middleware('permission:approve_work');
    Route::post('/work-approval/submissions/{id}/reject', [WorkApprovalController::class, 'rejectWorkSubmission'])->middleware('permission:reject_work');

    // Payment routes
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'delete']);
    Route::delete('/notifications/clear-all', [NotificationController::class, 'clearAll']);
    Route::get('/notifications/settings', [NotificationController::class, 'getSettings']);
    Route::put('/notifications/settings', [NotificationController::class, 'updateSettings']);
    Route::post('/notifications/test', [NotificationController::class, 'sendTest']);

    // Rating routes
    Route::post('/ratings', [RatingController::class, 'store']);
    Route::get('/ratings/fundi/{fundiId}', [RatingController::class, 'getFundiRatings']);
    Route::get('/ratings/my-ratings', [RatingController::class, 'getMyRatings']);
    Route::put('/ratings/{id}', [RatingController::class, 'update']);
    Route::delete('/ratings/{id}', [RatingController::class, 'delete']);

    // Settings routes
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::put('/settings', [SettingsController::class, 'update']);
    Route::patch('/settings/{key}', [SettingsController::class, 'updateKey']);
    Route::post('/settings/reset', [SettingsController::class, 'reset']);
    Route::get('/settings/export', [SettingsController::class, 'export']);
    Route::post('/settings/import', [SettingsController::class, 'import']);
    Route::get('/settings/themes', [SettingsController::class, 'getThemes']);
    Route::get('/settings/languages', [SettingsController::class, 'getLanguages']);
    Route::put('/settings/privacy', [SettingsController::class, 'updatePrivacy']);
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotificationSettings']);

    // Admin routes
    Route::middleware('role:admin')->group(function () {
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

        // System monitoring
        Route::get('/admin/monitor/active-users', [AdminController::class, 'getActiveUsers']);
        Route::get('/admin/monitor/jobs-summary', [AdminController::class, 'getJobsSummary']);
        Route::get('/admin/monitor/payments-summary', [AdminController::class, 'getPaymentsSummary']);
        Route::get('/admin/monitor/system-health', [AdminController::class, 'getSystemHealth']);
        Route::get('/admin/monitor/api-logs', [AdminController::class, 'getApiLogs']);
        Route::get('/admin/sessions', [AdminController::class, 'getSessions']);
        Route::delete('/admin/sessions/{id}', [AdminController::class, 'forceLogout']);
        Route::get('/admin/logs', [AdminController::class, 'getLaravelLogs']);
    });
});
