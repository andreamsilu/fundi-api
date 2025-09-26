<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JWTAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FundiController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\NotificationController;

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

// Protected routes (require JWT authentication)
Route::middleware('jwt.auth')->group(function () {
    // User routes
    Route::get('/users/me', [UserController::class, 'me']);
    Route::patch('/users/me', [UserController::class, 'update']);
    Route::delete('/users/me', [UserController::class, 'delete']);
    
    // Job routes (role/payment checks handled in controllers)
    // Available jobs - public feed for everyone to view
    Route::get('/jobs', [JobController::class, 'index'])->middleware('jwt.permission:view_jobs');
    // User's own jobs - only job owner can manage
    Route::get('/jobs/my-jobs', [JobController::class, 'myJobs'])->middleware('jwt.permission:view_jobs');
    // Create new job
    Route::post('/jobs', [JobController::class, 'store'])->middleware('jwt.permission:create_jobs');
    // View specific job
    Route::get('/jobs/{id}', [JobController::class, 'show'])->middleware('jwt.permission:view_jobs');
    // Update job - only job owner can update
    Route::patch('/jobs/{id}', [JobController::class, 'update'])->middleware('jwt.permission:edit_jobs');
    // Delete job - only job owner can delete
    Route::delete('/jobs/{id}', [JobController::class, 'destroy'])->middleware('jwt.permission:delete_jobs');
    
    // Feed routes
    Route::get('/feeds/jobs', [FeedController::class, 'getJobFeed'])->middleware('jwt.permission:view_jobs');
    Route::get('/feeds/fundis', [FeedController::class, 'getFundiFeed'])->middleware('jwt.permission:view_fundis');
    Route::get('/feeds/fundis/{id}', [FeedController::class, 'getFundiProfile'])->middleware('jwt.permission:view_fundis');
    Route::get('/feeds/jobs/{id}', [FeedController::class, 'getJobDetails'])->middleware('jwt.permission:view_jobs');
    
    // Category routes
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    
    // Fundi routes
    Route::get('/fundis', [FundiController::class, 'index'])->middleware('jwt.permission:view_fundis');
    Route::get('/fundis/{id}', [FundiController::class, 'show'])->middleware('jwt.permission:view_fundis');
    Route::post('/fundis', [FundiController::class, 'store'])->middleware('jwt.permission:create_fundis');
    Route::patch('/fundis/{id}', [FundiController::class, 'update'])->middleware('jwt.permission:edit_fundis');
    Route::delete('/fundis/{id}', [FundiController::class, 'destroy'])->middleware('jwt.permission:delete_fundis');
    
    // Application routes
    Route::post('/jobs/{jobId}/apply', [ApplicationController::class, 'apply'])->middleware('jwt.permission:apply_jobs');
    Route::get('/job-applications/my-applications', [ApplicationController::class, 'myApplications'])->middleware('jwt.permission:view_applications');
    Route::get('/jobs/{jobId}/applications', [ApplicationController::class, 'jobApplications'])->middleware('jwt.permission:view_applications');
    Route::patch('/job-applications/{id}/status', [ApplicationController::class, 'updateStatus'])->middleware('jwt.permission:manage_applications');
    
    // Payment routes
    Route::get('/payments/plans', [PaymentController::class, 'getPlans'])->middleware('jwt.permission:view_payments');
    Route::get('/payments/current-plan', [PaymentController::class, 'getCurrentPlan'])->middleware('jwt.permission:view_payments');
    Route::post('/payments/subscribe', [PaymentController::class, 'subscribe'])->middleware('jwt.permission:make_payments');
    Route::post('/payments/check-requirement', [PaymentController::class, 'checkRequirement'])->middleware('jwt.permission:view_payments');
    
    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->middleware('jwt.permission:view_notifications');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->middleware('jwt.permission:manage_notifications');
    Route::delete('/notifications/{id}', [NotificationController::class, 'delete'])->middleware('jwt.permission:manage_notifications');
});
