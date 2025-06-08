<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\FundiController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

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

// API Version 1 Routes
Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('otp/send', [OtpController::class, 'send']);

    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Auth routes
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/user', [AuthController::class, 'user']);

        // Fundi routes
        Route::get('fundis', [FundiController::class, 'index']);
        Route::get('fundis/{fundi}', [FundiController::class, 'show']);
        
        // Fundi profile management (requires fundi role and specific permissions)
        Route::middleware('role:fundi')->group(function () {
            Route::put('fundi/profile', [FundiController::class, 'updateProfile'])
                ->middleware('permission:edit own profile');
            Route::get('fundi/service-categories', [FundiController::class, 'getServiceCategories'])
                ->middleware('permission:view own profile');
            Route::put('fundi/service-categories', [FundiController::class, 'updateServiceCategories'])
                ->middleware('permission:manage service categories');
        });

        // Job routes
        Route::get('jobs', [JobController::class, 'index']);
        Route::post('jobs', [JobController::class, 'store'])
            ->middleware('permission:create jobs');
        Route::get('jobs/{job}', [JobController::class, 'show']);
        Route::put('jobs/{job}', [JobController::class, 'update'])
            ->middleware('permission:edit own jobs');
        Route::post('jobs/{job}/cancel', [JobController::class, 'cancel'])
            ->middleware('permission:edit own jobs');
        Route::get('jobs/mine', [JobController::class, 'myJobs'])
            ->middleware('permission:view own jobs');

        // Service Category routes (admin only)
        Route::middleware('role:admin')->group(function () {
            Route::get('service-categories', [ServiceCategoryController::class, 'index'])
                ->middleware('permission:manage categories');
            Route::post('service-categories', [ServiceCategoryController::class, 'store'])
                ->middleware('permission:manage categories');
            Route::get('service-categories/{category}', [ServiceCategoryController::class, 'show'])
                ->middleware('permission:manage categories');
            Route::put('service-categories/{category}', [ServiceCategoryController::class, 'update'])
                ->middleware('permission:manage categories');
            Route::delete('service-categories/{category}', [ServiceCategoryController::class, 'destroy'])
                ->middleware('permission:manage categories');
        });

        // Booking routes
        Route::get('bookings', [BookingController::class, 'index'])
            ->middleware('permission:view bookings');
        Route::post('bookings', [BookingController::class, 'store'])
            ->middleware('permission:create bookings');
        Route::get('bookings/{booking}', [BookingController::class, 'show'])
            ->middleware('permission:view bookings');
        Route::put('bookings/{booking}', [BookingController::class, 'updateStatus'])
            ->middleware('permission:accept bookings|reject bookings|complete bookings');
        Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])
            ->middleware('permission:cancel bookings|cancel own bookings');

        // Review routes
        Route::get('reviews/fundi/{fundi}', [ReviewController::class, 'fundiReviews']);
        Route::post('reviews', [ReviewController::class, 'store'])
            ->middleware('permission:create reviews');
        Route::get('reviews/{review}', [ReviewController::class, 'show']);
        Route::put('reviews/{review}', [ReviewController::class, 'update'])
            ->middleware('permission:edit own reviews');
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])
            ->middleware('permission:delete own reviews');

        // Notification routes
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);

        // Chat routes
        Route::get('chats', [ChatController::class, 'index']);
        Route::get('chats/{chat}', [ChatController::class, 'show']);
        Route::post('chats', [ChatController::class, 'store']);
        Route::post('chats/{chat}/messages', [ChatController::class, 'sendMessage']);
        Route::post('chats/{chat}/read', [ChatController::class, 'markAsRead']);
        Route::get('chats/unread-count', [ChatController::class, 'unreadCount']);

        // Payment routes
        Route::post('payments/initialize', [PaymentController::class, 'initialize']);
        Route::get('payments/history', [PaymentController::class, 'history']);
        Route::get('payments/{payment}', [PaymentController::class, 'show']);
    });

    // Webhook route (no auth required)
    Route::post('webhooks/stripe', [PaymentController::class, 'handleWebhook']);
}); 