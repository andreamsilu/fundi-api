<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\FundiController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceCategoryController;
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

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Fundi routes
    Route::get('/fundis', [FundiController::class, 'index']);
    Route::get('/fundis/{fundi}', [FundiController::class, 'show']);
    Route::middleware('role:fundi')->group(function () {
        Route::put('/fundi/profile', [FundiController::class, 'updateProfile']);
        Route::get('/fundi/service-categories', [FundiController::class, 'getServiceCategories']);
        Route::put('/fundi/service-categories', [FundiController::class, 'updateServiceCategories']);
    });

    // Booking routes
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::put('/bookings/{booking}/status', [BookingController::class, 'updateStatus']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

    // Review routes
    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/reviews/{review}', [ReviewController::class, 'show']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    // Service category routes (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/service-categories', [ServiceCategoryController::class, 'index']);
        Route::post('/service-categories', [ServiceCategoryController::class, 'store']);
        Route::get('/service-categories/{category}', [ServiceCategoryController::class, 'show']);
        Route::put('/service-categories/{category}', [ServiceCategoryController::class, 'update']);
        Route::delete('/service-categories/{category}', [ServiceCategoryController::class, 'destroy']);
    });
}); 