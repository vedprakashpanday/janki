<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\ActionApprovalController;

/*
|--------------------------------------------------------------------------
| API Routes (Data & Logic)
|--------------------------------------------------------------------------
*/

// Default Sanctum Check
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


/* ========================================================
   ADMIN API ROUTES (Version 1)
   Base URL: /api/v1/admin/...
======================================================== */
Route::prefix('v1/admin')->group(function () {

    // ----------------------------------------------------
    // 1. PUBLIC ROUTES (Bina Login Ke)
    // ----------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('/login-request', [AuthController::class, 'requestLogin']);
    });

    // Email Approval Links (Browser se open honge)
    Route::prefix('action')->group(function () {
        Route::get('/approve/{id}', [ActionApprovalController::class, 'approve'])->name('admin.action.approve');
        Route::get('/reject/{id}', [ActionApprovalController::class, 'reject'])->name('admin.action.reject');
    });

    // ----------------------------------------------------
    // 2. PROTECTED ROUTES (Sirf logged-in Admin ke liye)
    // ----------------------------------------------------
    Route::middleware(['auth:sanctum', 'is_admin'])->group(function () {
        
        // Auth Actions
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        
        // Dashboard APIs (Future)
        // Route::get('/dashboard-stats', [DashboardController::class, 'index']);
        
        // Employee APIs (Future)
        // Route::get('/employees', [EmployeeController::class, 'index']);
        
    });

});