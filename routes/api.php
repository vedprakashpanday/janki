<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\ActionApprovalController;
use App\Http\Controllers\Api\V1\Admin\AgentController;
use App\Http\Controllers\Api\V1\Admin\MediaController;
use App\Http\Controllers\Api\V1\Admin\BranchController;
use App\Http\Controllers\Api\V1\Admin\CustomerController;
use App\Http\Controllers\Api\V1\Admin\DesignationController;
use App\Http\Controllers\Api\V1\Admin\EmployeeController;
use App\Http\Controllers\Api\V1\Admin\LandownerController;
use App\Http\Controllers\Api\V1\Admin\MemberController;
use App\Http\Controllers\Api\V1\Admin\MemberDesignationController;
use App\Http\Controllers\Api\V1\Admin\SalaryController;
use App\Http\Controllers\Api\V1\Admin\VendorController;
use App\Http\Controllers\Api\V1\User\UserApiController;
use App\Http\Controllers\Api\V1\Admin\DebitVoucherApiController;
use App\Http\Controllers\Api\V1\Admin\CompanyApiController;

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
        
        // Branch APIs (Ye AJAX ke liye JSON data dega)
        Route::apiResource('branches', BranchController::class);

        //Employees api
        Route::apiResource('employees', EmployeeController::class);

        //Designation APIS
        Route::apiResource('designations', DesignationController::class);

        // Salary APIs
        Route::apiResource('salaries', SalaryController::class);

        Route::apiResource('customers', CustomerController::class);

        Route::apiResource('members', MemberController::class);

        Route::apiResource('member-designations', MemberDesignationController::class);

        Route::apiResource('agents', AgentController::class);

        Route::apiResource('landowners', LandownerController::class);

        Route::apiResource('vendors', VendorController::class);

        
Route::apiResource('ledgers', \App\Http\Controllers\Api\V1\Admin\LedgerController::class);
Route::get('/id-cards/staff-list', [\App\Http\Controllers\Api\V1\Admin\IdCardController::class, 'getStaffList']);

// Image Upload endpoint upar rakhna zaroori hai
Route::post('letterheads/upload-image', [\App\Http\Controllers\Api\V1\Admin\LetterheadController::class, 'uploadImage']);
Route::apiResource('letterheads', \App\Http\Controllers\Api\V1\Admin\LetterheadController::class);

        // Custom endpoints upar rakhein (Important in Laravel)
Route::post('interested-customers/assign-telecaller', [\App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class, 'assignTelecaller']);
Route::post('interested-customers/filter-reports', [\App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class, 'filterReports']);

Route::apiResource('interested-customers', \App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class);

// Device & Session Management
    Route::get('/auth/sessions', [\App\Http\Controllers\Api\V1\Admin\AuthController::class, 'getActiveSessions']);
    Route::post('/auth/logout-current', [\App\Http\Controllers\Api\V1\Admin\AuthController::class, 'logoutCurrent']);
    Route::post('/auth/logout-all', [\App\Http\Controllers\Api\V1\Admin\AuthController::class, 'logoutAll']);
    Route::post('/auth/logout-device/{id}', [\App\Http\Controllers\Api\V1\Admin\AuthController::class, 'logoutDevice']);



    });

    Route::get('telecaller-access', [\App\Http\Controllers\Api\V1\Admin\TelecallerAccessController::class, 'index']);
Route::post('telecaller-access/toggle', [\App\Http\Controllers\Api\V1\Admin\TelecallerAccessController::class, 'toggleAccess']);

// Store API
  Route::apiResource('debit_vouchers', \App\Http\Controllers\Api\V1\Admin\DebitVoucherApiController::class);
  Route::get('/get-member-bank', [DebitVoucherApiController::class, 'getMemberBankDetails']);

// Ye routes v1/admin group ke andar jayenge
Route::get('/get-branches', [DebitVoucherApiController::class, 'getBranches']);
Route::get('/get-ledgers', [DebitVoucherApiController::class, 'getLedgers']);
Route::get('/get-paid-to-list', [DebitVoucherApiController::class, 'getPaidToList']);
Route::get('/check-dv-no', [DebitVoucherApiController::class, 'checkDvNo']);
Route::get('/get-member-bank', [DebitVoucherApiController::class, 'getMemberBankDetails']);
Route::get('/get-sender-bank', [DebitVoucherApiController::class, 'getSenderBankDetails']);

Route::get('/get-next-dv-no', [DebitVoucherApiController::class, 'getNextDvNo']);

// Naya Dropdown Fetcher API
    Route::get('/get-active-companies', [CompanyApiController::class, 'getActiveCompanies']);
    
    // Resource Route (Ye automatically index, store, show, update, destroy sab map kar dega)
    Route::apiResource('companies', CompanyApiController::class);

});

Route::post('/v1/admin/media/upload', [MediaController::class, 'upload'])->middleware(['auth:sanctum', 'is_admin']);

Route::middleware(['auth:sanctum'])->prefix('v1/user')->group(function () {
    Route::get('/dashboard-data', [UserApiController::class, 'getDashboardData']);
});
