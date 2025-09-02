<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\FundiController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\NotificationController;

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\BusinessModelController;
use App\Http\Controllers\Uac\RoleController;
use App\Http\Controllers\Uac\PermissionController;
use App\Http\Controllers\Uac\UserRoleController;
use App\Http\Controllers\Uac\UserPermissionController;
use App\Http\Controllers\Uac\UserRoleSwitchingController;
use App\Http\Controllers\UsersController;
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

// Swagger UI documentation
Route::get('documentation', function () {
    return view('swagger-ui');
});

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

        // User role switching routes (part of UAC)
        Route::get('uac/available-roles', [UserRoleSwitchingController::class, 'getAvailableRoles']);
        Route::post('uac/switch-role', [UserRoleSwitchingController::class, 'switchRole']);
        Route::get('uac/profile-status', [UserRoleSwitchingController::class, 'getProfileStatus']);
        Route::get('uac/role-statistics', [UserRoleSwitchingController::class, 'getRoleStatistics']);
        Route::get('uac/switching-history', [UserRoleSwitchingController::class, 'getSwitchingHistory']);
        Route::get('uac/current-capabilities', [UserRoleSwitchingController::class, 'getCurrentRoleCapabilities']);

        // Fundi routes - public viewing, authenticated interaction
        Route::get('fundis', [FundiController::class, 'index']);
        Route::get('fundis/{fundi}', [FundiController::class, 'show']);
        
        // Fundi profile management (requires fundi role and specific permissions)
        Route::middleware('role:fundi|businessProvider')->group(function () {
            Route::put('fundi/profile', [FundiController::class, 'updateProfile'])
                ->middleware('permission:edit own profile');
            Route::get('fundi/service-categories', [FundiController::class, 'getServiceCategories'])
                ->middleware('permission:view own profile');
            Route::put('fundi/service-categories', [FundiController::class, 'updateServiceCategories'])
                ->middleware('permission:manage service categories');
        });

        // Job routes - role-based access
        Route::get('jobs', [JobController::class, 'index']); // Public viewing
        Route::get('jobs/{job}', [JobController::class, 'show']); // Public viewing
        
        // Job management - customers and business customers can create jobs
        Route::middleware('role:customer|businessCustomer')->group(function () {
            Route::post('jobs', [JobController::class, 'store'])
                ->middleware('permission:create jobs');
            Route::put('jobs/{job}', [JobController::class, 'update'])
                ->middleware('permission:edit own jobs');
            Route::post('jobs/{job}/cancel', [JobController::class, 'cancel'])
                ->middleware('permission:edit own jobs');
            Route::get('jobs/mine', [JobController::class, 'myJobs'])
                ->middleware('permission:view own jobs');
        });

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

        // Users management - admin/moderator only
        Route::middleware('role:admin|moderator')->group(function () {
            Route::get('users', [UsersController::class, 'index'])
                ->middleware('permission:manage users');
            Route::post('users', [UsersController::class, 'store'])
                ->middleware('permission:manage users');
            Route::get('users/{user}', [UsersController::class, 'show'])
                ->middleware('permission:manage users');
            Route::put('users/{user}', [UsersController::class, 'update'])
                ->middleware('permission:manage users');
            Route::delete('users/{user}', [UsersController::class, 'destroy'])
                ->middleware('permission:manage users');
            Route::post('users/{user}/toggle-status', [UsersController::class, 'toggleStatus'])
                ->middleware('permission:manage users');
            Route::post('users/{user}/verify', [UsersController::class, 'verify'])
                ->middleware('permission:manage users');
        });

        // UAC (Roles & Permissions) - admin only
        Route::middleware('role:admin')->group(function () {
            // System Roles Management
            Route::get('uac/roles', [RoleController::class, 'index']);
            Route::post('uac/roles', [RoleController::class, 'store'])
                ->middleware('permission:manage roles');
            Route::get('uac/roles/{role}', [RoleController::class, 'show']);
            Route::put('uac/roles/{role}', [RoleController::class, 'update'])
                ->middleware('permission:manage roles');
            Route::delete('uac/roles/{role}', [RoleController::class, 'destroy'])
                ->middleware('permission:manage roles');
            Route::get('uac/roles-statistics', [RoleController::class, 'statistics']);

            // System Permissions Management
            Route::get('uac/permissions', [PermissionController::class, 'index']);
            Route::post('uac/permissions', [PermissionController::class, 'store'])
                ->middleware('permission:manage permissions');
            Route::get('uac/permissions/{permission}', [PermissionController::class, 'show']);
            Route::put('uac/permissions/{permission}', [PermissionController::class, 'update'])
                ->middleware('permission:manage permissions');
            Route::delete('uac/permissions/{permission}', [PermissionController::class, 'destroy'])
                ->middleware('permission:manage permissions');
            Route::get('uac/permissions-statistics', [PermissionController::class, 'statistics']);
            Route::get('uac/permissions-search', [PermissionController::class, 'search']);

            // User-Role Assignment
            Route::get('uac/user-roles', [UserRoleController::class, 'index']);
            Route::get('uac/user-roles/{user}', [UserRoleController::class, 'show']);
            Route::post('uac/user-roles/{user}/assign-role', [UserRoleController::class, 'assignRole'])
                ->middleware('permission:manage roles');
            Route::post('uac/user-roles/{user}/revoke-role', [UserRoleController::class, 'revokeRole'])
                ->middleware('permission:manage roles');
            Route::post('uac/user-roles/{user}/sync-roles', [UserRoleController::class, 'syncRoles'])
                ->middleware('permission:manage roles');
            Route::get('uac/user-roles-by-role/{role}', [UserRoleController::class, 'getUsersByRole']);
            Route::get('uac/user-roles-statistics', [UserRoleController::class, 'statistics']);

            // User-Permission Assignment
            Route::get('uac/user-permissions', [UserPermissionController::class, 'index']);
            Route::get('uac/user-permissions/{user}', [UserPermissionController::class, 'show']);
            Route::post('uac/user-permissions/{user}/give-permission', [UserPermissionController::class, 'givePermission'])
                ->middleware('permission:manage permissions');
            Route::post('uac/user-permissions/{user}/revoke-permission', [UserPermissionController::class, 'revokePermission'])
                ->middleware('permission:manage permissions');
            Route::post('uac/user-permissions/{user}/sync-permissions', [UserPermissionController::class, 'syncPermissions'])
                ->middleware('permission:manage permissions');
            Route::get('uac/user-permissions/{user}/effective', [UserPermissionController::class, 'getEffectivePermissions']);
            Route::get('uac/user-permissions-by-permission/{permission}', [UserPermissionController::class, 'getUsersByPermission']);
            Route::get('uac/user-permissions-statistics', [UserPermissionController::class, 'statistics']);
            Route::post('uac/user-permissions/{user}/check', [UserPermissionController::class, 'checkPermission']);

            // Role-Permission Linking
            Route::post('uac/roles/{role}/attach-permission', [RoleController::class, 'attachPermission']);
            Route::post('uac/roles/{role}/detach-permission', [RoleController::class, 'detachPermission']);
        });

        // Booking routes - role-based access
        Route::get('bookings', [BookingController::class, 'index'])
            ->middleware('permission:view bookings');
        Route::get('bookings/{booking}', [BookingController::class, 'show'])
            ->middleware('permission:view bookings');
        
        // Customers can create bookings
        Route::middleware('role:customer|businessCustomer')->group(function () {
            Route::post('bookings', [BookingController::class, 'store'])
                ->middleware('permission:create bookings');
            Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])
                ->middleware('permission:cancel own bookings');
        });
        
        // Fundis can manage bookings
        Route::middleware('role:fundi|businessProvider')->group(function () {
            Route::put('bookings/{booking}', [BookingController::class, 'updateStatus'])
                ->middleware('permission:accept bookings|reject bookings|complete bookings');
            Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])
                ->middleware('permission:cancel bookings');
        });

        // Review routes - role-based access
        Route::get('reviews/fundi/{fundi}', [ReviewController::class, 'fundiReviews']); // Public viewing
        Route::get('reviews/{review}', [ReviewController::class, 'show']); // Public viewing
        
        // Customers can create and manage their reviews
        Route::middleware('role:customer|businessCustomer')->group(function () {
            Route::post('reviews', [ReviewController::class, 'store'])
                ->middleware('permission:create reviews');
            Route::put('reviews/{review}', [ReviewController::class, 'update'])
                ->middleware('permission:edit own reviews');
            Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])
                ->middleware('permission:delete own reviews');
        });

        // Notification routes
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);



        // Payment routes
        Route::post('payments/initialize', [PaymentController::class, 'initialize']);
        Route::get('payments/history', [PaymentController::class, 'history']);
        Route::get('payments/{payment}', [PaymentController::class, 'show']);

        // Business Model routes
        Route::get('business-models', [BusinessModelController::class, 'index']);
        Route::get('business-models/{business_model}', [BusinessModelController::class, 'show']);
        Route::get('business-models/{business_model}/jobs', [BusinessModelController::class, 'getJobs']);
        Route::post('business-models/{business_model}/check-compatibility', [BusinessModelController::class, 'checkCompatibility']);
        Route::post('business-models/{business_model}/calculate-fee', [BusinessModelController::class, 'calculateFee']);
        Route::get('business-models/dashboard', [BusinessModelController::class, 'dashboard']);
    });

    // Mobile money callback route (no auth required)
    Route::post('webhooks/mobile-money', [PaymentController::class, 'handleWebhook']);
}); 