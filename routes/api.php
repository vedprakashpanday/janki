<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Import Controllers
use App\Http\Controllers\Api\V1\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\V1\Employee\AuthController as EmployeeAuthController;
use App\Http\Controllers\Api\V1\Admin\CompanyApiController;
use App\Http\Controllers\Api\V1\Admin\BranchController;
use App\Http\Controllers\Api\V1\Admin\DepartmentController;
use App\Http\Controllers\Api\V1\Admin\DesignationController;
use App\Http\Controllers\Api\V1\Admin\EmployeeController;
use App\Http\Controllers\Api\V1\Admin\ModuleController;
use App\Http\Controllers\Api\V1\Admin\RolePermissionController;
use App\Http\Controllers\Api\V1\Admin\SystemActionController;
use App\Http\Controllers\Api\V1\Admin\DebitVoucherApiController;
use App\Http\Controllers\Api\V1\Admin\AccessControlController;
use App\Http\Controllers\Api\V1\Admin\CustomerController;
use App\Http\Controllers\Api\V1\Admin\MemberController;
use App\Http\Controllers\Api\V1\Admin\MemberDesignationController;
use App\Http\Controllers\Api\V1\Admin\AgentController;
use App\Http\Controllers\Api\V1\Admin\LandownerController;
use App\Http\Controllers\Api\V1\Admin\VendorController;
use App\Http\Controllers\Api\V1\Admin\SalaryController;
use App\Http\Controllers\Api\V1\Admin\LedgerController;
use App\Http\Controllers\Api\V1\Admin\LetterheadController;
use App\Http\Controllers\Api\V1\Admin\InterestedCustomerController;
use App\Http\Controllers\Api\V1\Admin\IdCardController;
use App\Http\Controllers\Api\V1\Admin\MediaController;
use App\Http\Controllers\Api\V1\Admin\BulkDeleteController;
use App\Http\Controllers\Api\V1\Admin\SuperAdminController;
use App\Http\Controllers\Api\V1\Admin\DirectorController;
use App\Http\Controllers\Api\V1\Admin\TelecallerAccessController;
use App\Http\Controllers\Api\V1\Admin\ActionApprovalController;
use App\Http\Controllers\Api\V1\Admin\TaskTrackingModuleController;
use App\Http\Controllers\Api\V1\Admin\TaskController;

/*
|--------------------------------------------------------------------------
| MULTI-PORTAL UNIFIED API ARCHITECTURE
|--------------------------------------------------------------------------
*/

// ==========================================================================
// SECTION 1: ISOLATED AUTHENTICATION ROUTES (Portal Specific)
// ==========================================================================
Route::prefix('v1/admin')->group(function () {
    Route::post('/auth/login-request', [AdminAuthController::class, 'requestLogin']);
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/auth/me', [AdminAuthController::class, 'me']);
        Route::post('/auth/logout-current', [AdminAuthController::class, 'logoutCurrent']);
        Route::post('/auth/logout-all', [AdminAuthController::class, 'logoutAll']);
        Route::post('/auth/logout-device/{id}', [AdminAuthController::class, 'logoutDevice']);
        Route::get('/auth/sessions', [AdminAuthController::class, 'getActiveSessions']);
        Route::get('/general-leads/export', [\App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class, 'downloadExport'])
    ->name('general_leads.export');
 // 🔥 Admin Template Editor API
        Route::get('/welcome-letter-template', [\App\Http\Controllers\Api\V1\Admin\WelcomeLetterAdminController::class, 'getTemplate']);
        Route::post('/welcome-letter-template', [\App\Http\Controllers\Api\V1\Admin\WelcomeLetterAdminController::class, 'updateTemplate']);

    });
});

Route::prefix('v1/employee')->group(function () {
    Route::post('/verify-id', [EmployeeAuthController::class, 'verifyId']);
    Route::post('/bind-device', [EmployeeAuthController::class, 'bindDevice']);
    Route::post('/verify-otp', [EmployeeAuthController::class, 'verifyOtp']);
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/auth/me', [EmployeeAuthController::class, 'me']);
        Route::post('/mark-attendance', [EmployeeAuthController::class, 'markAttendance']);
        Route::get('/dashboard-data', [EmployeeAuthController::class, 'getDashboardData']);
        Route::post('/logout', [EmployeeAuthController::class, 'logout']);
        Route::get('/welcome-letter', [\App\Http\Controllers\Api\V1\Employee\WelcomeLetterApiController::class, 'getLetter']);
       
    });
});


// ==========================================================================
// SECTION 2: UNIFIED DATA API (Shared Resource Routes)
// ==========================================================================

Route::prefix('v1/action')->group(function () {
    Route::get('/approve/{id}', [ActionApprovalController::class, 'approve'])->name('admin.action.approve');
    Route::get('/reject/{id}', [ActionApprovalController::class, 'reject'])->name('admin.action.reject');
});


// 🛡️ PROTECTED CORE DATA APIs
Route::prefix('v1')->middleware(['auth:sanctum', 'time.matrix'])->group(function () {

    // ====================================================================
    // 🔴 ZONE 1: STRICT DEVELOPER ONLY ROUTES (God Mode Required)
    // ====================================================================
    Route::middleware(['is_developer'])->group(function () {
        // Modules & Core Actions Setup (Sirf yeh Developer ke liye rahega)
        Route::get('modules/parents', [ModuleController::class, 'getParents']);
        Route::apiResource('modules', ModuleController::class);
        Route::apiResource('system-actions', SystemActionController::class);
        Route::apiResource('super-admins', SuperAdminController::class);

        // 🔥 NAYA: Dynamic Task Tracking Setup (Developer Only) 🔥
        Route::get('/developer/tables', [TaskTrackingModuleController::class, 'getTables']);
        Route::post('/developer/columns', [TaskTrackingModuleController::class, 'getColumns']);
        Route::post('/developer/tracking-modules', [TaskTrackingModuleController::class, 'store']);
    });


    // ====================================================================
    // 🟡 ZONE 2: SHARED BUSINESS & HR ROUTES (Role Managed)
    // Ye endpoints CEO, Director, Employee access kar sakte hain
    // ====================================================================

    // 🔥 NAYA FIX: Role Manager, Telecaller aur Access routes yahan aa gaye hain 🔥
    // Role Manager Setup & Write Permissions
    Route::get('role-manager/users', [RolePermissionController::class, 'getUsers']);
    Route::post('role-manager/assign', [RolePermissionController::class, 'assignPowers']);
    Route::post('role-manager/clear', [RolePermissionController::class, 'clearUserPowers']);
    Route::post('role-manager/revoke-permission', [RolePermissionController::class, 'revokeSinglePermission']);
    Route::post('role-manager/revoke-module', [RolePermissionController::class, 'revokeModulePermissions']);

    // Security, Device Blocks & Panel Access
    Route::get('panel-access', [AccessControlController::class, 'index']);
    Route::post('generate-access', [AccessControlController::class, 'generateEmployeeAccess']);
    Route::get('get-employees-list', [AccessControlController::class, 'getEmployeesList']);
    Route::post('grant-emergency-access', [AccessControlController::class, 'grantEmergencyAccess']);
    Route::post('reject-device', [AccessControlController::class, 'rejectDeviceRequest']);
    Route::post('unblock-device', [AccessControlController::class, 'unblockDevice']);
    Route::post('block-device', [AccessControlController::class, 'blockDevice']);
    Route::get('get-session-logs', [AccessControlController::class, 'getSessionLogs']);
    Route::post('/revoke-device', [AccessControlController::class, 'revokeDeviceAccess']);
    Route::post('/hard-reset-access', [AccessControlController::class, 'hardResetDevice']);
    Route::post('/set-device-role', [AccessControlController::class, 'setDeviceRole']);
    Route::post('/smart-unbind-device', [AccessControlController::class, 'processSmartUnbind']);
    Route::post('/update-shift-timings', [AccessControlController::class, 'updateShiftTimings']);
    Route::post('/update-company-shift-timings', [AccessControlController::class, 'updateCompanyShiftTimings']);

    // Telecaller Access & Bulk Actions
    Route::get('telecaller-access', [TelecallerAccessController::class, 'index']);
    Route::post('telecaller-access/toggle', [TelecallerAccessController::class, 'toggleAccess']);
    Route::post('/bulk-delete', [BulkDeleteController::class, 'delete']);

    // --- Masters & Core Setup ---

    Route::apiResource('companies', CompanyApiController::class);
    Route::get('/get-active-companies', [CompanyApiController::class, 'getActiveCompanies']);
    Route::apiResource('departments', DepartmentController::class);
    Route::get('get-active-departments', [DepartmentController::class, 'getActiveDepartments']);
    Route::get('get-departments-by-company', [DepartmentController::class, 'getDepartmentsByCompany']);
    // 🔥 NAYI LINE: Fetch branches on company selection 🔥
    Route::post('get-branches-by-companies', [DepartmentController::class, 'getBranchesByCompanies']);
    Route::get('departments-pending', [DepartmentController::class, 'getPendingRequests']);
    Route::post('departments/{id}/status', [DepartmentController::class, 'updateStatus']);




    Route::apiResource('designations', DesignationController::class);
    Route::get('get-designations-by-dept', [DesignationController::class, 'getDesignationsByDepartment']);
    Route::apiResource('branches', BranchController::class);
    Route::get('role-manager/roles-permissions', [RolePermissionController::class, 'getRolesAndPermissions']);

    // --- Users & Stakeholders ---
    Route::apiResource('directors', DirectorController::class);
    Route::get('/directors/active', [DirectorController::class, 'getActiveDirectors']);
   
    // --- Users & Stakeholders ---
    Route::post('employees/search-transfer', [EmployeeController::class, 'searchForTransfer']); // 🔥 NAYA
    Route::post('employees/next-id', [EmployeeController::class, 'getNextSmartId']);
    Route::get('employees-pending', [EmployeeController::class, 'getPendingRequests']);
    Route::post('employees/{id}/status', [EmployeeController::class, 'updateStatus']);
    Route::apiResource('employees', EmployeeController::class);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('members', MemberController::class);
    Route::apiResource('member-designations', MemberDesignationController::class);
    Route::apiResource('agents', AgentController::class);
    Route::apiResource('landowners', LandownerController::class);
    Route::apiResource('vendors', VendorController::class);

    // --- Finance & Vouchers ---
    Route::apiResource('salaries', SalaryController::class);
    Route::apiResource('ledgers', LedgerController::class);
    Route::apiResource('debit_vouchers', DebitVoucherApiController::class);
    Route::get('/get-branches', [DebitVoucherApiController::class, 'getBranches']);
    Route::get('/get-ledgers', [DebitVoucherApiController::class, 'getLedgers']);
    Route::get('/get-paid-to-list', [DebitVoucherApiController::class, 'getPaidToList']);
    Route::get('/check-dv-no', [DebitVoucherApiController::class, 'checkDvNo']);
    Route::get('/get-member-bank', [DebitVoucherApiController::class, 'getMemberBankDetails']);
    Route::get('/get-sender-bank', [DebitVoucherApiController::class, 'getSenderBankDetails']);
    Route::get('/get-next-dv-no', [DebitVoucherApiController::class, 'getNextDvNo']);

    // NAYA ROUTE: Authorized Signatory fetch karne ke liye 
    Route::get('/get-authorized-signatories', [DebitVoucherApiController::class, 'getAuthorizedSignatories']);

    // --- Leads & CRM ---
  
    Route::apiResource('interested-customers', InterestedCustomerController::class);
    Route::post('interested-customers/assign-telecaller', [InterestedCustomerController::class, 'assignTelecaller']);
    Route::post('interested-customers/filter-reports', [InterestedCustomerController::class, 'filterReports']);
    Route::post('interested-customers/{id}/status', [App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class, 'updateEntryStatus']);
      Route::post('interested-customers/import', [App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class, 'import']);
   // Add this inside your $sharedWebRoutes function or at the bottom of the admin group
Route::get('/general-leads/export', [\App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class, 'downloadExport'])
    ->name('general_leads.export');

    // --- Operations & Utilities ---
    Route::apiResource('letterheads', LetterheadController::class);
    Route::post('letterheads/upload-image', [LetterheadController::class, 'uploadImage']);
    Route::get('/id-cards/staff-list', [IdCardController::class, 'getStaffList']);
    Route::post('/media/upload', [MediaController::class, 'upload']);

    // 🔥 NAYA: Task Management & Daily Operations 🔥
    Route::get('/tracking-modules', [TaskTrackingModuleController::class, 'index']);
    Route::apiResource('tasks', TaskController::class);
    Route::post('tasks/{id}/reply', [\App\Http\Controllers\Api\V1\Admin\TaskController::class, 'addReply']);

    Route::apiResource('terms-conditions', \App\Http\Controllers\Api\V1\Admin\TermConditionController::class);
});
