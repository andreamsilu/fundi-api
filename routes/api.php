<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\RatingReviewController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\AuditController;

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

// Public routes (only authentication)
Route::prefix('v1')->group(function () {
    
    // Authentication routes (public)
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/token-info', [AuthController::class, 'tokenInfo']);
    
    // Debug route
    Route::get('/debug/auth', function() {
        return response()->json([
            'authenticated' => auth()->check(),
            'user' => auth()->user(),
            'guard' => auth()->getDefaultDriver()
        ]);
    });
    
    // Simple test route
    Route::get('/test', function() {
        return response()->json(['message' => 'API is working']);
    });
    
    // Categories (public - needed for job creation)
    Route::get('/categories', [CategoryController::class, 'index']);
});

// Protected routes (all other routes require authentication)
Route::prefix('v1')->middleware('auth.sanctum')->group(function () {
        
        // Simple protected test route
        Route::get('/test-protected', function() {
            return response()->json(['message' => 'Protected API is working']);
        });
        
        
        // Jobs (authenticated)
        Route::get('/jobs', [JobController::class, 'index']);
        Route::get('/jobs/{id}', [JobController::class, 'show']);
        
        // Fundi profiles (now requires authentication)
        Route::get('/fundi/{id}', [UserController::class, 'getFundiProfile']);
        
        // Portfolio (now requires authentication)
        Route::get('/portfolio/{fundi_id}', [PortfolioController::class, 'getFundiPortfolio']);
        
        // Authentication
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        
        // User management
        Route::get('/users/me', [UserController::class, 'me']);
        Route::patch('/users/me/fundi-profile', [UserController::class, 'updateFundiProfile']);
        
        // Jobs (authenticated)
        Route::middleware('role:customer,admin')->group(function () {
            Route::post('/jobs', [JobController::class, 'store']);
            Route::put('/jobs/{id}', [JobController::class, 'update']);
            Route::delete('/jobs/{id}', [JobController::class, 'destroy']);
        });
        
        // Job applications
        Route::middleware('role:fundi')->group(function () {
            Route::post('/jobs/{job_id}/apply', [JobApplicationController::class, 'apply']);
        });
        
        // Applications accessible by both fundis and customers
        Route::get('/my-applications', [JobApplicationController::class, 'getMyApplications']);
        
        Route::middleware('role:customer,admin')->group(function () {
            Route::get('/jobs/{job_id}/applications', [JobApplicationController::class, 'getJobApplications']);
            Route::patch('/applications/{id}/status', [JobApplicationController::class, 'updateApplicationStatus']);
        });
        
        Route::delete('/applications/{id}', [JobApplicationController::class, 'destroy']);
        
        // Portfolio management
        Route::middleware('role:fundi,admin')->group(function () {
            Route::post('/portfolio', [PortfolioController::class, 'store']);
            Route::put('/portfolio/{id}', [PortfolioController::class, 'update']);
            Route::delete('/portfolio/{id}', [PortfolioController::class, 'destroy']);
            Route::post('/portfolio-media', [PortfolioController::class, 'uploadMedia']);
        });
        
        // Payments
        Route::middleware('role:fundi,customer')->group(function () {
            Route::get('/payments', [PaymentController::class, 'index']);
            Route::post('/payments', [PaymentController::class, 'store']);
            Route::get('/payments/requirements', [PaymentController::class, 'getRequirements']);
            Route::post('/payments/check-required', [PaymentController::class, 'checkPaymentRequired']);
        });
        
        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
        
        // Ratings and Reviews
        Route::post('/ratings', [RatingReviewController::class, 'store'])->middleware('role:customer');
        Route::get('/ratings/my-ratings', [RatingReviewController::class, 'getMyRatings'])->middleware('role:customer');
        Route::get('/ratings/fundi/{fundiId}', [RatingReviewController::class, 'getFundiRatings']);
        Route::put('/ratings/{id}', [RatingReviewController::class, 'update']);
        Route::delete('/ratings/{id}', [RatingReviewController::class, 'destroy']);
        
        // File Uploads
        Route::post('/upload/portfolio-media', [FileUploadController::class, 'uploadPortfolioMedia'])->middleware('role:fundi');
        Route::post('/upload/job-media', [FileUploadController::class, 'uploadJobMedia'])->middleware('role:customer');
        Route::post('/upload/profile-document', [FileUploadController::class, 'uploadProfileDocument'])->middleware('role:fundi');
        Route::delete('/upload/media/{id}', [FileUploadController::class, 'deleteMedia']);
        Route::get('/upload/media/{id}/url', [FileUploadController::class, 'getMediaUrl']);
        
        // Admin routes
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            
            // User management
            Route::get('/users', [AdminController::class, 'getUsers']);
            Route::get('/users/{id}', [AdminController::class, 'getUser']);
            Route::patch('/users/{id}', [AdminController::class, 'updateUser']);
            Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
            
            // Fundi profiles
            Route::get('/fundi_profiles', [AdminController::class, 'getFundiProfiles']);
            Route::patch('/fundi_profiles/{id}/verify', [AdminController::class, 'verifyFundi']);
            
            // Jobs management
            Route::get('/jobs', [AdminController::class, 'getAllJobs']);
            Route::get('/jobs/{id}', [AdminController::class, 'getJob']);
            Route::patch('/jobs/{id}', [AdminController::class, 'updateJob']);
            Route::delete('/jobs/{id}', [AdminController::class, 'deleteJob']);
            
            // Applications management
            Route::get('/job_applications', [AdminController::class, 'getAllApplications']);
            Route::patch('/job_applications/{id}', [AdminController::class, 'updateApplication']);
            Route::delete('/job_applications/{id}', [AdminController::class, 'deleteApplication']);
            
            // Portfolio management
            Route::patch('/portfolio/{id}', [AdminController::class, 'updatePortfolio']);
            Route::delete('/portfolio/{id}', [AdminController::class, 'deletePortfolio']);
            
            // Payments management
            Route::get('/payments', [AdminController::class, 'getAllPayments']);
            Route::patch('/payments/{id}', [AdminController::class, 'updatePayment']);
            Route::get('/payments/reports', [AdminController::class, 'getPaymentReports']);
            
            // Notifications management
            Route::post('/notifications', [AdminController::class, 'sendNotification']);
            Route::patch('/notifications/{id}', [AdminController::class, 'updateNotification']);
            Route::delete('/notifications/{id}', [AdminController::class, 'deleteNotification']);
            
            // Ratings management
            Route::get('/ratings', [RatingReviewController::class, 'getAllRatings']);
            
            // Categories management
            Route::post('/categories', [AdminController::class, 'createCategory']);
            Route::patch('/categories/{id}', [AdminController::class, 'updateCategory']);
            Route::delete('/categories/{id}', [AdminController::class, 'deleteCategory']);
            
            // Settings
            Route::get('/settings', [AdminController::class, 'getSettings']);
            Route::patch('/settings', [AdminController::class, 'updateSettings']);
            
            // Monitoring
            Route::get('/monitor/active-users', [MonitoringController::class, 'getActiveUsers']);
            Route::get('/monitor/jobs-summary', [MonitoringController::class, 'getJobsSummary']);
            Route::get('/monitor/payments-summary', [MonitoringController::class, 'getPaymentsSummary']);
            Route::get('/monitor/system-health', [MonitoringController::class, 'getSystemHealth']);
            Route::get('/monitor/api-logs', [MonitoringController::class, 'getApiLogs']);
            
            // Sessions
            Route::get('/sessions', [AdminController::class, 'getSessions']);
            Route::delete('/sessions/{id}', [AdminController::class, 'forceLogout']);
            
            // Laravel logs
            Route::get('/logs', [MonitoringController::class, 'getLaravelLogs']);
            
            // Audit logs
            Route::get('/audit-logs', [AuditController::class, 'index']);
            Route::get('/audit-logs/{id}', [AuditController::class, 'show']);
            Route::get('/audit-logs/statistics', [AuditController::class, 'statistics']);
            Route::get('/audit-logs/failed-actions', [AuditController::class, 'failedActions']);
            Route::get('/audit-logs/user-activity/{userId}', [AuditController::class, 'userActivity']);
            Route::get('/audit-logs/security-events', [AuditController::class, 'securityEvents']);
            Route::get('/audit-logs/api-errors', [AuditController::class, 'apiErrors']);
            Route::get('/audit-logs/export', [AuditController::class, 'export']);
        });
});
