<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\PermissionController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('auth/registration', [AuthController::class, 'registrationConfig'])->middleware('throttle:public-api')->name('auth.registration');
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:register')->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('auth.login');
    Route::post('auth/refresh', [AuthController::class, 'refresh'])->middleware('throttle:refresh')->name('auth.refresh');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:forgot-password')->name('auth.forgot-password');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth')->name('auth.reset-password');
    Route::post('auth/email/verification-notification', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:auth')->name('auth.verify.resend');

    Route::get('auth/email/verify/{id}/{hash}', [AuthController::class, 'verify'])
        ->middleware(['signed', 'throttle:auth'])->name('verification.verify');

    Route::middleware(['auth:api', 'token.valid', 'throttle:api'])->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('auth/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');
        Route::patch('auth/password', [AuthController::class, 'changePassword'])->name('auth.change-password');

        Route::middleware('throttle:administration')->group(function (): void {
            Route::apiResource('users', UserController::class);
            Route::patch('users/{user}/status', [UserController::class, 'status'])->name('users.status');
            Route::put('users/{user}/roles', [UserController::class, 'roles'])->name('users.roles');
            Route::apiResource('roles', RoleController::class);
            Route::put('roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
            Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        });
    });
});
