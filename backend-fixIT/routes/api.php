<?php 

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReportUpdateController;
use Illuminate\Support\Facades\Route;

// AUTH (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// PROTECTED ROUTES (butuh login)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // Report bisa diakses user & admin (logic dibedakan di controller)
    Route::apiResource('reports', ReportController::class);
    Route::get('/reports/{report}/updates', [ReportUpdateController::class, 'index']);

    // Category & Location: hanya admin yang boleh create/update/delete
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/locations', [LocationController::class, 'index']);

    Route::middleware('role:admin')->group(function () {
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        Route::post('/locations', [LocationController::class, 'store']);
        Route::put('/locations/{location}', [LocationController::class, 'update']);
        Route::delete('/locations/{location}', [LocationController::class, 'destroy']);
    });
});