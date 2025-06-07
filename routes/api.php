<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\FundiController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\OtpController;
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
    Route::middleware('auth:sanctum')->group(function () {
        // Auth routes
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/user', [AuthController::class, 'user']);

        // Fundi routes
        Route::get('fundis', [FundiController::class, 'index']);
        Route::get('fundis/{fundi}', [FundiController::class, 'show']);
        Route::middleware('role:fundi')->group(function () {
            Route::put('fundi/profile', [FundiController::class, 'updateProfile']);
            Route::get('fundi/service-categories', [FundiController::class, 'getServiceCategories']);
            Route::put('fundi/service-categories', [FundiController::class, 'updateServiceCategories']);
        });

        // Job routes
        Route::get('jobs', [JobController::class, 'index']);
        Route::post('jobs', [JobController::class, 'store']);
        Route::get('jobs/{job}', [JobController::class, 'show']);
        Route::put('jobs/{job}', [JobController::class, 'update']);
        Route::post('jobs/{job}/cancel', [JobController::class, 'cancel']);
        Route::get('jobs/mine', [JobController::class, 'myJobs']);

        // Service Category routes (admin only)
        Route::middleware('role:admin')->group(function () {
            Route::get('service-categories', [ServiceCategoryController::class, 'index']);
            Route::post('service-categories', [ServiceCategoryController::class, 'store']);
            Route::get('service-categories/{category}', [ServiceCategoryController::class, 'show']);
            Route::put('service-categories/{category}', [ServiceCategoryController::class, 'update']);
            Route::delete('service-categories/{category}', [ServiceCategoryController::class, 'destroy']);
        });

        // Booking routes
        Route::get('bookings', [BookingController::class, 'index']);
        Route::post('bookings', [BookingController::class, 'store']);
        Route::get('bookings/{booking}', [BookingController::class, 'show']);
        Route::put('bookings/{booking}', [BookingController::class, 'updateStatus']);
        Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);

        // Review routes
        Route::get('reviews/fundi/{fundi}', [ReviewController::class, 'fundiReviews']);
        Route::post('reviews', [ReviewController::class, 'store']);
        Route::get('reviews/{review}', [ReviewController::class, 'show']);
        Route::put('reviews/{review}', [ReviewController::class, 'update']);
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy']);
    });
}); 