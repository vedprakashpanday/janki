<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

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
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\Admin\BankDetailController;



/*
|--------------------------------------------------------------------------
| MULTI-PORTAL UNIFIED API ARCHITECTURE
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {


    // ==========================================================================
    // SECTION 1: ISOLATED AUTHENTICATION ROUTES (Portal Specific)
    // ==========================================================================
    Route::prefix('admin')->group(function () {
        Route::post('/auth/login-request', [AdminAuthController::class, 'requestLogin']);
        // 🔥 Ye dono nayi line add karni hai CEO OTP Login ke liye
        Route::post('/auth/super-admin/request-otp', [AdminAuthController::class, 'superAdminRequestOtp']);
        Route::post('/auth/super-admin/verify-otp', [AdminAuthController::class, 'superAdminVerifyOtp']);

        Route::middleware(['auth:sanctum'])->group(function () {
            Route::get('/auth/me', [AdminAuthController::class, 'me']);
            Route::post('/auth/logout-current', [AdminAuthController::class, 'logoutCurrent']);
            Route::post('/auth/logout-all', [AdminAuthController::class, 'logoutAll']);
            Route::post('/auth/logout-device/{id}', [AdminAuthController::class, 'logoutDevice']);
            Route::get('/auth/sessions', [AdminAuthController::class, 'getActiveSessions']);

            Route::get('/general-leads/export', [\App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class, 'downloadExport'])->name('general_leads.export');

            // 🔥 Admin Template Editor API
            Route::get('/welcome-letter-entities', [\App\Http\Controllers\Api\V1\Admin\WelcomeLetterAdminController::class, 'getEntities']);
            Route::get('/welcome-letter-template', [\App\Http\Controllers\Api\V1\Admin\WelcomeLetterAdminController::class, 'getTemplate']);
            Route::post('/welcome-letter-template', [\App\Http\Controllers\Api\V1\Admin\WelcomeLetterAdminController::class, 'updateTemplate']);
        });
    });

    Route::prefix('employee')->group(function () {
        Route::post('/verify-id', [EmployeeAuthController::class, 'verifyId']);
        Route::post('/bind-device', [EmployeeAuthController::class, 'bindDevice']);
        Route::post('/verify-otp', [EmployeeAuthController::class, 'verifyOtp']);
        Route::post('/resend-otp', [\App\Http\Controllers\Api\V1\Employee\AuthController::class, 'resendOtp']);

        Route::middleware(['auth:sanctum'])->group(function () {
            Route::get('/auth/me', [EmployeeAuthController::class, 'me']);
            Route::post('/mark-attendance', [EmployeeAuthController::class, 'markAttendance']);
            Route::get('/dashboard-data', [EmployeeAuthController::class, 'getDashboardData']);
            Route::post('/logout', [EmployeeAuthController::class, 'logout']);
        });
    });

    Route::prefix('member')->group(function () {
        // Open Routes (Bina Token Ke)
        Route::post('/auth/login-request', [\App\Http\Controllers\Api\V1\Member\AuthController::class, 'requestLogin']);
        Route::post('/auth/verify-otp', [\App\Http\Controllers\Api\V1\Member\AuthController::class, 'verifyOtp']);

        // Protected Routes (Token, TimeMatrix, aur DeviceGuard ke saath)
       // Protected Routes (Token, TimeMatrix, aur DeviceGuard ke saath)
        Route::middleware(['auth:sanctum', 'time.matrix', \App\Http\Middleware\SecondaryDeviceGuard::class])->group(function () {
            Route::get('/auth/me', [\App\Http\Controllers\Api\V1\Member\AuthController::class, 'me']);
            
            // 👇 YEH NAYI LINE ADD KAREIN (Status check karne ke liye) 👇
            Route::get('/attendance/today-status', [\App\Http\Controllers\Api\V1\Member\AttendanceController::class, 'getTodayStatus']);
            // 👇 YEH DO LINES ADD KARNI HAIN 👇
            Route::post('/attendance/mark', [\App\Http\Controllers\Api\V1\Member\AttendanceController::class, 'markAttendance']);
            // Route::post('/attendance/ping-location', [\App\Http\Controllers\Api\V1\Member\AttendanceController::class, 'pingLocation']);

            Route::get('/attendance/monthly', [\App\Http\Controllers\Api\V1\Member\AttendanceController::class, 'getMonthlyAttendance']);

            // Logout route abhi yahan define kar dete hain future use ke liye
            Route::post('/auth/logout', function (Illuminate\Http\Request $request) {
                $request->user()->currentAccessToken()->delete();
                return response()->json(['status' => 'success', 'message' => 'Logged out successfully']);
            });

         
        });
    });


    // ==========================================================================
    // SECTION 2: UNIFIED DATA API (Shared Resource Routes)
    // ==========================================================================

    Route::prefix('action')->group(function () {
        Route::get('/approve/{id}', [ActionApprovalController::class, 'approve'])->name('admin.action.approve');
        Route::get('/reject/{id}', [ActionApprovalController::class, 'reject'])->name('admin.action.reject');
    });


    // 🛡️ PROTECTED CORE DATA APIs (Time Matrix applies here)
    Route::middleware(['auth:sanctum', 'time.matrix', \App\Http\Middleware\SecondaryDeviceGuard::class])->group(function () {

        // ====================================================================
        // 🔴 ZONE 1: STRICT DEVELOPER ONLY ROUTES (God Mode Required)
        // ====================================================================
        Route::middleware(['is_developer'])->group(function () {
            // Modules & Core Actions Setup (Sirf yeh Developer ke liye rahega)
            Route::get('modules/parents', [ModuleController::class, 'getParents']);
            Route::apiResource('modules', ModuleController::class);
            Route::apiResource('system-actions', SystemActionController::class);
            // Isko show/update/destroy wale routes se upar add karein
            Route::get('v1/super-admins/next-id', [SuperAdminController::class, 'getNextId']);
            Route::apiResource('super-admins', SuperAdminController::class);

            // Dynamic Task Tracking Setup (Developer Only)
            Route::get('/developer/tables', [TaskTrackingModuleController::class, 'getTables']);
            Route::post('/developer/columns', [TaskTrackingModuleController::class, 'getColumns']);
            Route::post('/developer/tracking-modules', [TaskTrackingModuleController::class, 'store']);
        });


        // ====================================================================
        // 🟡 ZONE 2: SHARED BUSINESS & HR ROUTES (Role Managed)
        // ====================================================================

        // UI Context & Permissions API
        Route::get('/context', function () {
            $controller = new \App\Http\Controllers\Controller();
            return response()->json($controller->getGlobalContext());
        });

         // 🟢 Member HR Attendance Matrix & Cascading Dropdowns
        Route::prefix('member-attendance-matrix-api')->group(function () {
            Route::get('/companies', [\App\Http\Controllers\Api\V1\Admin\MemberAttendanceAdminController::class, 'getCompanies']);
            Route::post('/branches', [\App\Http\Controllers\Api\V1\Admin\MemberAttendanceAdminController::class, 'getBranches']);
            Route::post('/departments', [\App\Http\Controllers\Api\V1\Admin\MemberAttendanceAdminController::class, 'getDepartments']);
            Route::post('/designations', [\App\Http\Controllers\Api\V1\Admin\MemberAttendanceAdminController::class, 'getDesignations']);
            Route::post('/members', [\App\Http\Controllers\Api\V1\Admin\MemberAttendanceAdminController::class, 'getMembers']);
            
            // Final Matrix Data Load
            Route::post('/load-matrix', [\App\Http\Controllers\Api\V1\Admin\MemberAttendanceAdminController::class, 'loadMatrix']);

            // 👇 YEH NAYI LINE ADD KAREIN 👇
            Route::post('/get-route', [\App\Http\Controllers\Api\V1\Admin\MemberAttendanceAdminController::class, 'getMemberRoute']);
        });

        // Role Manager Setup & Write Permissions
        Route::get('role-manager/users', [RolePermissionController::class, 'getUsers']);
        Route::post('role-manager/assign', [RolePermissionController::class, 'assignPowers']);
        Route::post('role-manager/clear', [RolePermissionController::class, 'clearUserPowers']);
        Route::post('role-manager/revoke-permission', [RolePermissionController::class, 'revokeSinglePermission']);
        Route::post('role-manager/revoke-module', [RolePermissionController::class, 'revokeModulePermissions']);
        Route::get('role-manager/roles-permissions', [RolePermissionController::class, 'getRolesAndPermissions']);

        // 🔥 ROLE MANAGER CASCADING DROPDOWNS API
    Route::prefix('role-manager/dropdown')->group(function () {
        Route::get('companies', [\App\Http\Controllers\Api\V1\Admin\RolePermissionController::class, 'getCompanies']);
        Route::get('branches', [\App\Http\Controllers\Api\V1\Admin\RolePermissionController::class, 'getBranches']);
        Route::get('departments', [\App\Http\Controllers\Api\V1\Admin\RolePermissionController::class, 'getDepartments']);
        Route::get('designations', [\App\Http\Controllers\Api\V1\Admin\RolePermissionController::class, 'getDesignations']);
        Route::get('targets', [\App\Http\Controllers\Api\V1\Admin\RolePermissionController::class, 'getTargetUsers']); // Employee ya Member
    });

// --- Dynamic 3-Letter Search APIs ---
Route::get('/companies/search-dynamic', [\App\Http\Controllers\Api\V1\Admin\CompanyApiController::class, 'searchDynamic']);
Route::get('/branches/search-dynamic', [\App\Http\Controllers\Api\V1\Admin\BranchController::class, 'searchDynamic']);
Route::get('/departments/search-dynamic', [\App\Http\Controllers\Api\V1\Admin\DepartmentController::class, 'searchDynamic']);
Route::get('/designations/search-dynamic', [\App\Http\Controllers\Api\V1\Admin\DesignationController::class, 'searchDynamic']); // 🔥 NAYA

// Naya bulk delete permanent for employees (is resource route ke sath add karein)
Route::post('employees/bulk-delete-permanent', [\App\Http\Controllers\Api\V1\Admin\EmployeeController::class, 'bulkDeletePermanent']);


    Route::post('role-manager/grade-matrix/load', [\App\Http\Controllers\Api\V1\Admin\RolePermissionController::class, 'loadGradeMatrix']);
Route::post('role-manager/grade-matrix/save', [\App\Http\Controllers\Api\V1\Admin\RolePermissionController::class, 'saveGradeMatrix']);

    // 🔥 ROLE MANAGER - EXCEPTION MATRIX ROUTES
    Route::post('role-manager/matrix/load', [\App\Http\Controllers\Api\V1\Admin\RolePermissionController::class, 'loadExceptionMatrix']);
    Route::post('role-manager/matrix/save', [\App\Http\Controllers\Api\V1\Admin\RolePermissionController::class, 'saveExceptions']);

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

        // Departments custom actions
        Route::post('departments/bulk-delete', [DepartmentController::class, 'bulkDelete']);
        Route::post('departments/{id}/approve', [DepartmentController::class, 'approve']);
        Route::post('departments/{id}/reject', [DepartmentController::class, 'reject']);
        Route::apiResource('departments', DepartmentController::class);
        Route::get('get-active-departments', [DepartmentController::class, 'getActiveDepartments']);
        Route::get('get-departments-by-company', [DepartmentController::class, 'getDepartmentsByCompany']);
        Route::post('get-branches-by-companies', [DepartmentController::class, 'getBranchesByCompanies']);
        Route::get('departments-pending', [DepartmentController::class, 'getPendingRequests']);
        Route::post('departments/{id}/status', [DepartmentController::class, 'updateStatus']);

        Route::apiResource('designations', DesignationController::class);
        Route::get('get-designations-by-dept', [DesignationController::class, 'getDesignationsByDepartment']);

        // Custom Branch Actions
        Route::get('branches/ui-context', [BranchController::class, 'uiContext']);
        Route::post('branches/bulk-delete', [BranchController::class, 'bulkDelete']);
        Route::post('branches/{id}/approve', [BranchController::class, 'approve']);
        Route::post('branches/{id}/reject', [BranchController::class, 'reject']);
        Route::apiResource('branches', BranchController::class);

        // --- Users & Stakeholders ---
        Route::apiResource('directors', DirectorController::class);
        Route::get('/directors/active', [DirectorController::class, 'getActiveDirectors']);

        Route::post('employees/search-transfer', [EmployeeController::class, 'searchForTransfer']);
        Route::post('employees/next-id', [EmployeeController::class, 'getNextSmartId']);
        Route::get('employees-pending', [EmployeeController::class, 'getPendingRequests']);
        Route::post('employees/{id}/status', [EmployeeController::class, 'updateStatus']);
        Route::apiResource('employees', EmployeeController::class);

        // ✅ SAHI TARIQA (api.php mein):

// 1. Specific static routes hamesha upar rahenge
Route::get('/customers/generate-id', [CustomerController::class, 'generateCredentials']);
Route::get('/customers/search-old', [CustomerController::class, 'searchOldCustomer']);
Route::post('/customers/bulk-delete', [CustomerController::class, 'bulkDelete']);

// 👇 YEH DIRECTORY WALA NAYA ROUTE YAHAN UPAR RAKHEIN 👇
Route::get('/customers/directory', [CustomerController::class, 'directory']);

// 2. Base resource routes
Route::get('/customers', [CustomerController::class, 'index']);
Route::post('/customers', [CustomerController::class, 'store']);

// 3. Wildcard / Dynamic ID wale routes hamesha SABSE NICHE hone chahiye
Route::get('/customers/{id}', [CustomerController::class, 'show']);
Route::put('/customers/{id}', [CustomerController::class, 'update']);
Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);
Route::post('/customers/{id}/restore', [CustomerController::class, 'restore']);
Route::post('/customers/{id}/status', [CustomerController::class, 'updateStatus']);

        Route::apiResource('customers', CustomerController::class);
         Route::get('members/next-id', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'getNextId']);
         Route::post('members/bulk-delete', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'bulkDelete']);

         // routes/api.php में जोड़ें
Route::get('/members/search-dynamic', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'searchDynamic']);

         Route::post('members/{id}/approve', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'approve']);
Route::post('members/{id}/reject', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'reject']);


Route::get('members/available-designations', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'getAvailableDesignations']);
Route::get('members/all-time-records', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'allTimeIndex']);
        Route::get('members/transferred', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'getTransferredMembers']);

        // 🔥 UNIFIED DEPENDENCY SEARCH ROUTES 🔥
Route::get('/members/search-companies', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'searchCompanies']);
Route::get('/members/search-branches', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'searchBranches']);
Route::get('/members/search-departments', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'searchDepartments']);

// 👇 YEH ROUTE MISSING THA, ISE ADD KAREIN 👇
Route::get('/members/search-sponsor', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'searchSponsorDynamic']);

   // Auto-fill Search Routes
Route::get('/search-company', [App\Http\Controllers\Api\V1\Admin\LandownerController::class, 'searchCompany']);
Route::get('/search-branch', [App\Http\Controllers\Api\V1\Admin\LandownerController::class, 'searchBranch']);
Route::get('/search-phase', [App\Http\Controllers\Api\V1\Admin\LandownerController::class, 'searchPhase']);
Route::get('/search-landowners-list', [App\Http\Controllers\Api\V1\Admin\LandownerController::class, 'searchLandownersList']);



          Route::apiResource('members', MemberController::class);
        Route::apiResource('member-designations', MemberDesignationController::class);
        Route::apiResource('agents', AgentController::class);
        Route::apiResource('landowners', LandownerController::class);
        Route::apiResource('vendors', VendorController::class);

        // --- Finance & Vouchers ---
        Route::apiResource('salaries', SalaryController::class);
        Route::get('/ledgers/generate-code', [LedgerController::class, 'generateCode']);
Route::post('/ledgers/bulk-delete', [LedgerController::class, 'bulkDelete']);
Route::post('/ledgers/{id}/status', [LedgerController::class, 'updateStatus']);
// Plus standard API Resource routes for ledgers...
        Route::apiResource('ledgers', LedgerController::class);
        // =======================================================
        // 🔥 DEBIT VOUCHER MODULE API ROUTES
        // =======================================================
        
        // 1. Dynamic 3-Letter Search APIs
        Route::get('/debit_vouchers/search-companies', [DebitVoucherApiController::class, 'searchCompanies']);
        Route::get('/debit_vouchers/search-branches', [DebitVoucherApiController::class, 'searchBranches']);
        Route::get('/debit_vouchers/search-ledgers', [DebitVoucherApiController::class, 'searchLedgers']);
        Route::get('/debit_vouchers/search-paid-to', [DebitVoucherApiController::class, 'searchPaidTo']);
        Route::get('/debit_vouchers/get-salary-details', [DebitVoucherApiController::class, 'fetchSalaryDetails']);
        
        // 2. Action Modifiers & Utilities
        Route::post('/debit_vouchers/{id}/approve', [DebitVoucherApiController::class, 'approve']);
        Route::post('/debit_vouchers/{id}/reject', [DebitVoucherApiController::class, 'reject']);
        Route::post('/debit_vouchers/{id}/cancel', [DebitVoucherApiController::class, 'cancel']);
        Route::post('/debit_vouchers/{id}/restore', [DebitVoucherApiController::class, 'restore']);
        
        Route::get('/get-authorized-signatories', [DebitVoucherApiController::class, 'getAuthorizedSignatories']);
        Route::get('/check-dv-no', [DebitVoucherApiController::class, 'checkDvNo']);
        Route::get('/get-next-dv-no', [DebitVoucherApiController::class, 'getNextDvNo']);
        Route::get('/get-member-bank', [DebitVoucherApiController::class, 'getMemberBankDetails']);
        Route::get('/get-sender-bank', [DebitVoucherApiController::class, 'getSenderBankDetails']);

        Route::get('/debit_vouchers/get-advance-history', [DebitVoucherApiController::class, 'getEmployeeAdvanceHistory']);
        
        // 3. Base API Resource (Hamesha last me rakhein)
        Route::apiResource('debit_vouchers', DebitVoucherApiController::class);
      

        // --- Leads & CRM ---
      Route::post('/phases/bulk-delete', [\App\Http\Controllers\Api\V1\Admin\PhaseApiController::class, 'bulkDelete']);
        Route::get('/phases/form-data', [\App\Http\Controllers\Api\V1\Admin\PhaseApiController::class, 'create']);
        Route::get('/phases/get-branches/{company_id}', [\App\Http\Controllers\Api\V1\Admin\PhaseApiController::class, 'getBranches']);
        Route::get('/phases/search-dynamic-list', [\App\Http\Controllers\Api\V1\Admin\PhaseApiController::class, 'searchDynamicList']);



        Route::apiResource('phases', \App\Http\Controllers\Api\V1\Admin\PhaseApiController::class);

        Route::get('/interested-customers/member-template', [\App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class, 'downloadMemberTemplate']);

        // 🔥 MEMBER CALLING PORTAL API 🔥
        Route::get('/interested-customers/member-portal/leads', [\App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class, 'getMemberPortalLeads']);

       Route::get('/interested-customers/member-summary/{member_id}', [\App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class, 'getMemberLeadsSummary'])->where('member_id', '.*');
        Route::post('/interested-customers/report-employees', [\App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class, 'getReportEmployees']);

        Route::get('/available-providers', [\App\Http\Controllers\Api\V1\Employee\TelecallingController::class, 'getAvailableProviders']);
        Route::get('/interested-customers/next-provider-id', [\App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class, 'getNextProviderId']);
        // Template download route (Web session based)
    Route::get('/interested-customers/import-template', [\App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class, 'downloadImportTemplate']);
        Route::post('/interested-customers/generate-report', [\App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class, 'generatePerformanceReport']);
        Route::post('/interested-customers/check-mobile', [\App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class, 'checkMobile']);
        Route::post('/interested-customers/bulk-delete', [InterestedCustomerController::class, 'bulkDelete']);

        
        
        Route::post('interested-customers/assign-telecaller', [InterestedCustomerController::class, 'assignTelecaller']);
        Route::post('interested-customers/filter-reports', [InterestedCustomerController::class, 'filterReports']);
        Route::post('interested-customers/{id}/status', [InterestedCustomerController::class, 'updateEntryStatus']);
        Route::post('interested-customers/import', [InterestedCustomerController::class, 'import']);
Route::apiResource('interested-customers', InterestedCustomerController::class);
        // --- Operations & Utilities ---

        Route::get('/letterheads/next-ref', [\App\Http\Controllers\Api\V1\Admin\LetterheadController::class, 'getNextRefNo']);
        Route::apiResource('letterheads', LetterheadController::class);
        Route::post('letterheads/upload-image', [LetterheadController::class, 'uploadImage']);
        Route::get('/id-cards/staff-list', [IdCardController::class, 'getStaffList']);
        Route::post('/media/upload', [MediaController::class, 'upload']);

        // 🔥 Task Management & Daily Operations 🔥
        Route::get('/tracking-modules', [TaskTrackingModuleController::class, 'index']);
        Route::apiResource('tasks', TaskController::class);
        Route::post('tasks/{id}/reply', [TaskController::class, 'addReply']);
        Route::get('/task-reports-data', [TaskController::class, 'progressReport']);

        // 👇 YAHAN PAR WO DONO ROUTES PASTE KAR DIJIYE 👇
        Route::post('tasks/logs/{log_id}/edit', [TaskController::class, 'editReply']);
        Route::post('tasks/logs/{log_id}/delete', [TaskController::class, 'deleteReply']);

        Route::apiResource('terms-conditions', \App\Http\Controllers\Api\V1\Admin\TermConditionController::class);

        Route::apiResource('rules-regulations', \App\Http\Controllers\Api\V1\Admin\RulesRegulationController::class);

        // Shared Welcome Letter
        Route::get('/welcome-letter/generate', [\App\Http\Controllers\Api\V1\Employee\WelcomeLetterApiController::class, 'getLetter']);

        // --- Notices & Communications ---
        // 🔥 NEW ROUTE: Dedicated API for Notice Target Audience Dropdown
        Route::get('notices/audience-entities', [\App\Http\Controllers\Api\V1\Admin\NoticeAdminController::class, 'getAudienceEntities']);

        Route::apiResource('notices', \App\Http\Controllers\Api\V1\Admin\NoticeAdminController::class);

        // --- Notices & Communications ---
        Route::apiResource('notices', \App\Http\Controllers\Api\V1\Admin\NoticeAdminController::class);
        // --- User Portal Notices API ---
        Route::get('/my-notices', [\App\Http\Controllers\Api\V1\Employee\NoticeApiController::class, 'index']);
        Route::get('/my-notices/{id}', [\App\Http\Controllers\Api\V1\Employee\NoticeApiController::class, 'show']);
        Route::post('/my-notices/{id}/reply', [\App\Http\Controllers\Api\V1\Employee\NoticeApiController::class, 'submitReply']);

        // 🔥 New Approve & Reject Routes 🔥
        Route::post('notices/{id}/approve', [\App\Http\Controllers\Api\V1\Admin\NoticeAdminController::class, 'approve']);
        Route::post('notices/{id}/reject', [\App\Http\Controllers\Api\V1\Admin\NoticeAdminController::class, 'reject']);

        Route::get('notices/{id}/replies', [\App\Http\Controllers\Api\V1\Admin\NoticeAdminController::class, 'getReplies']);

        // --- Travel Allowance (TA) API ---
        Route::get('travel-allowances/search-filters', [App\Http\Controllers\Api\V1\Admin\TravelAllowanceApiController::class, 'searchFilters']);
        Route::apiResource('travel-allowances', \App\Http\Controllers\Api\V1\Admin\TravelAllowanceApiController::class);
        Route::post('travel-allowances/bulk-delete', [\App\Http\Controllers\Api\V1\Admin\TravelAllowanceApiController::class, 'bulkDelete']);
        Route::post('travel-allowances/{id}/approve', [\App\Http\Controllers\Api\V1\Admin\TravelAllowanceApiController::class, 'approve']);
        Route::post('travel-allowances/{id}/reject', [\App\Http\Controllers\Api\V1\Admin\TravelAllowanceApiController::class, 'reject']);
        Route::post('travel-allowances/{id}/remarks', [\App\Http\Controllers\Api\V1\Admin\TravelAllowanceApiController::class, 'updateRemarks']);

        // --- Leave, Short Leave & Other Applications ---
        Route::apiResource('leave-applications', \App\Http\Controllers\Api\V1\Admin\LeaveApplicationApiController::class);
        // --- Leave Applications API ---
        Route::get('leave-applications/dropdown/users', [\App\Http\Controllers\Api\V1\Admin\LeaveApplicationApiController::class, 'getUsersByDesignation']);
        Route::apiResource('leave-applications', \App\Http\Controllers\Api\V1\Admin\LeaveApplicationApiController::class);
        Route::post('leave-applications/{id}/approve', [\App\Http\Controllers\Api\V1\Admin\LeaveApplicationApiController::class, 'approve']);
        Route::get('leave-applications/dropdown/apply-to', [\App\Http\Controllers\Api\V1\Admin\LeaveApplicationApiController::class, 'getApplyToOptions']);
        Route::post('leave-applications/{id}/reject', [\App\Http\Controllers\Api\V1\Admin\LeaveApplicationApiController::class, 'reject']);
        // 🔥 NAYA ROUTE: Sirf Remark add karne ke liye (Other application type)
        Route::post('leave-applications/{id}/remark', [\App\Http\Controllers\Api\V1\Admin\LeaveApplicationApiController::class, 'addRemark']);
        Route::get('leave-applications/{id}/view', [\App\Http\Controllers\Api\V1\Admin\LeaveApplicationApiController::class, 'viewHtml']);
        // Fine Penalty Module
        Route::apiResource('fine-penalties', \App\Http\Controllers\Api\V1\Admin\FinePenaltyApiController::class);
        Route::post('fine-penalties/bulk-delete', [\App\Http\Controllers\Api\V1\Admin\FinePenaltyApiController::class, 'bulkDelete']);
        Route::post('fine-penalties/{id}/approve', [\App\Http\Controllers\Api\V1\Admin\FinePenaltyApiController::class, 'approve']);
        Route::post('fine-penalties/{id}/reject', [\App\Http\Controllers\Api\V1\Admin\FinePenaltyApiController::class, 'reject']);
        Route::post('fine-penalties/{id}/remark', [\App\Http\Controllers\Api\V1\Admin\FinePenaltyApiController::class, 'updateRemark']);

        // Dependency Dropdowns
        Route::post('get-filtered-departments', [\App\Http\Controllers\Api\V1\Admin\FinePenaltyApiController::class, 'getFilteredDepartments']);
        Route::post('get-filtered-designations', [\App\Http\Controllers\Api\V1\Admin\FinePenaltyApiController::class, 'getFilteredDesignations']);
        Route::post('get-filtered-employees', [\App\Http\Controllers\Api\V1\Admin\FinePenaltyApiController::class, 'getFilteredEmployees']);
        Route::get('/notifications/unread', [NotificationController::class, 'getUnread']);
        Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead']);

        // 🔥 NAYA: Attendance Matrix Routes Yahan Add Karein 🔥
        Route::post('attendance-matrix', [\App\Http\Controllers\Api\V1\Admin\AttendanceAdminController::class, 'getFilteredAttendance']);
        Route::post('attendance-correction', [\App\Http\Controllers\Api\V1\Admin\AttendanceAdminController::class, 'saveCorrection']);

        // Yeh line add karni hai 👇
        Route::post('attendance-verify-punch', [\App\Http\Controllers\Api\V1\Admin\AttendanceAdminController::class, 'verifyPendingPunch']);

       Route::get('attendance-time-windows/dropdown', [\App\Http\Controllers\Api\V1\Admin\AttendanceTimeWindowController::class, 'getDropdownData']);
Route::post('attendance-time-windows/store', [\App\Http\Controllers\Api\V1\Admin\AttendanceTimeWindowController::class, 'store']);
Route::post('attendance-time-windows/bulk-delete', [\App\Http\Controllers\Api\V1\Admin\AttendanceTimeWindowController::class, 'bulkDelete']); // 🔥 NAYA BULK DELETE ROUTE
Route::get('attendance-time-windows', [\App\Http\Controllers\Api\V1\Admin\AttendanceTimeWindowController::class, 'index']);
Route::put('attendance-time-windows/{id}', [\App\Http\Controllers\Api\V1\Admin\AttendanceTimeWindowController::class, 'update']);
Route::delete('attendance-time-windows/{id}', [\App\Http\Controllers\Api\V1\Admin\AttendanceTimeWindowController::class, 'destroy']);

        // Auto Task Settings API
        Route::post('auto-task-settings/{id}/status', [\App\Http\Controllers\Api\V1\Admin\AutoTaskSettingController::class, 'updateStatus']);
        Route::apiResource('auto-task-settings', \App\Http\Controllers\Api\V1\Admin\AutoTaskSettingController::class);

// Telecaller Calling Panel APIs
        Route::get('/telecalling/allocations', [\App\Http\Controllers\Api\V1\Employee\TelecallingController::class, 'getAllocations']);
        Route::post('/telecalling/allocations/{id}/feedback', [\App\Http\Controllers\Api\V1\Employee\TelecallingController::class, 'updateFeedback']);
// 🔥 NAYA: Print Route 🔥
        Route::get('/telecalling/allocations/print', [\App\Http\Controllers\Api\V1\Employee\TelecallingController::class, 'printReport']);


        // API Routes for Time Window
Route::get('attendance-time-windows/dropdown', [\App\Http\Controllers\Api\V1\Admin\AttendanceTimeWindowController::class, 'getDropdownData']);
Route::post('attendance-time-windows/store', [\App\Http\Controllers\Api\V1\Admin\AttendanceTimeWindowController::class, 'store']);

Route::get('/bank-details/daily', [BankDetailController::class, 'getDailyData']);
    Route::get('/bank-details/directory', [BankDetailController::class, 'getDirectoryData']);
    Route::post('/bank-details/{id}/status', [BankDetailController::class, 'updateStatus']);
    Route::get('/bank-details/search-holder', [BankDetailController::class, 'searchAccountHolder']);

Route::apiResource('bank-details', BankDetailController::class);

// ====================================================================
        // 🔥 Site Development & Daily Entries API
        // ====================================================================
        
        // --- Site Allocations (Admin Setup) ---
        Route::apiResource('site-allocations', \App\Http\Controllers\Api\V1\Admin\SiteAllocationController::class);
        Route::post('site-allocations/bulk-delete', [\App\Http\Controllers\Api\V1\Admin\SiteAllocationController::class, 'bulkDelete']);

        // --- Site Daily Entries (Employee Panel) ---
        Route::get('site-entries/allowed-categories', [\App\Http\Controllers\Api\V1\Admin\SiteEntryController::class, 'getAllowedCategories']);
        Route::get('site-entries/shops', [\App\Http\Controllers\Api\V1\Admin\SiteEntryController::class, 'getShops']); // Shop Autofill
        Route::get('site-entries/history/{id}', [\App\Http\Controllers\Api\V1\Admin\SiteEntryController::class, 'getHistory']); // Edit History
        Route::post('site-entries/update/{id}', [\App\Http\Controllers\Api\V1\Admin\SiteEntryController::class, 'update']); // Edit Entry with Files
        Route::post('site-entries/bulk-delete', [\App\Http\Controllers\Api\V1\Admin\SiteEntryController::class, 'bulkDelete']);
       
        Route::get('site-entries/export', [\App\Http\Controllers\Api\V1\Admin\SiteEntryController::class, 'exportExcel'])->name('site_entries.export');
        
        // Resource route sabse last me rakhte hain taki custom routes conflict na karein
        Route::apiResource('site-entries', \App\Http\Controllers\Api\V1\Admin\SiteEntryController::class);
        Route::get('site-entries/{id}', [\App\Http\Controllers\Api\V1\Admin\SiteEntryController::class, 'show']);

        Route::post('/vehicle-trips/store', [\App\Http\Controllers\Api\V1\Admin\VehicleTripController::class, 'store']);

        // Member Print & Export Routes
Route::get('members/export-excel', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'exportExcel']);
Route::get('members/print', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'printMembers']);


// Member Device Tracking (Strict Security)
    Route::get('member-devices', [\App\Http\Controllers\Api\V1\Admin\MemberDeviceController::class, 'index']);
    Route::post('member-devices/{id}/status', [\App\Http\Controllers\Api\V1\Admin\MemberDeviceController::class, 'updateStatus']);
    Route::post('member-devices/{id}/swap', [\App\Http\Controllers\Api\V1\Admin\MemberDeviceController::class, 'swapType']);
    Route::get('member-devices/{id}/logs', [\App\Http\Controllers\Api\V1\Admin\MemberDeviceController::class, 'getLogs']);
Route::post('/profile/update', [\App\Http\Controllers\Api\V1\ProfileController::class, 'updateProfile']);



   // Existing route ke theek neeche add karein
Route::get('/telecalling/allocations/summary', [\App\Http\Controllers\Api\V1\Employee\TelecallingController::class, 'getSummary']);

// 🔥 NAYA ROUTE: Detailed Summary + Interested Leads ke liye
Route::get('/telecalling/allocations/detailed-summary', [\App\Http\Controllers\Api\V1\Employee\TelecallingController::class, 'getDetailedSummary']);

Route::get('/telecalling/allocations/summary/print', [\App\Http\Controllers\Api\V1\Employee\TelecallingController::class, 'printSummary']);

// --- Temp Receipts ---
        Route::get('/receipts/get-customers', [\App\Http\Controllers\Api\V1\Admin\TempReceiptApiController::class, 'getCustomersData']); // 🔥 NAYA ROUTE
        Route::get('/receipts/get-employees', [\App\Http\Controllers\Api\V1\Admin\TempReceiptApiController::class, 'getEmployeesData']); // 🔥 NAYA ROUTE
        
        Route::get('/receipts/form-data', [\App\Http\Controllers\Api\V1\Admin\TempReceiptApiController::class, 'getFormData']);
        Route::get('/receipts/get-branches/{company_id}', [\App\Http\Controllers\Api\V1\Admin\TempReceiptApiController::class, 'getBranches']);
        Route::apiResource('receipts', \App\Http\Controllers\Api\V1\Admin\TempReceiptApiController::class);



        // Greeting Templates Setup
        Route::get('/greeting-templates', [\App\Http\Controllers\Api\V1\Admin\GreetingTemplateController::class, 'index']);
        Route::post('/greeting-templates', [\App\Http\Controllers\Api\V1\Admin\GreetingTemplateController::class, 'store']);

        Route::get('/events-dashboard', [\App\Http\Controllers\Api\V1\Admin\EventDashboardController::class, 'index']);

        Route::get('/my-greetings', [\App\Http\Controllers\Api\V1\MyGreetingController::class, 'getMyGreetings']);


         Route::post('salaries/calculate', [\App\Http\Controllers\Api\V1\Admin\SalaryApiController::class, 'calculateData']);
                Route::post('salaries/store', [\App\Http\Controllers\Api\V1\Admin\SalaryApiController::class, 'store']);

Route::get('salaries', [\App\Http\Controllers\Api\V1\Admin\SalaryApiController::class, 'index']);

// 👇 YEH DONO NAYE ROUTES YAHAN ADD KAREIN 👇
Route::post('salaries/bulk-delete', [\App\Http\Controllers\Api\V1\Admin\SalaryApiController::class, 'bulkDelete']);
Route::delete('salaries/{id}', [\App\Http\Controllers\Api\V1\Admin\SalaryApiController::class, 'destroy']);

// Downline Tree API Route
        Route::get('members/downline/tree', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'getDownline']);



        // =======================================================
        // 🔥 EMPLOYEE INCENTIVES MODULE API ROUTES 🔥
        // =======================================================
        
        // 1. Dynamic 3-Letter Cascading Search APIs
        Route::get('/incentives/search-companies', [\App\Http\Controllers\Api\V1\Admin\IncentiveApiController::class, 'searchCompanies']);
        Route::get('/incentives/search-branches', [\App\Http\Controllers\Api\V1\Admin\IncentiveApiController::class, 'searchBranches']);
        Route::get('/incentives/search-departments', [\App\Http\Controllers\Api\V1\Admin\IncentiveApiController::class, 'searchDepartments']);
        Route::get('/incentives/search-designations', [\App\Http\Controllers\Api\V1\Admin\IncentiveApiController::class, 'searchDesignations']);
        Route::get('/incentives/search-employees', [\App\Http\Controllers\Api\V1\Admin\IncentiveApiController::class, 'searchEmployees']);

        // 2. Incentive Types (Nested Modal ke liye)
        Route::get('/incentive-types/active', [\App\Http\Controllers\Api\V1\Admin\IncentiveTypeApiController::class, 'getActive']);
        Route::post('/incentive-types/store', [\App\Http\Controllers\Api\V1\Admin\IncentiveTypeApiController::class, 'store']);

        // 3. Action Modifiers & Bulk Utilities
        Route::post('/incentives/bulk-delete', [\App\Http\Controllers\Api\V1\Admin\IncentiveApiController::class, 'bulkDeletePermanent']);
        Route::post('/incentives/{id}/approve', [\App\Http\Controllers\Api\V1\Admin\IncentiveApiController::class, 'approve']);
        Route::post('/incentives/{id}/reject', [\App\Http\Controllers\Api\V1\Admin\IncentiveApiController::class, 'reject']);
        
        // 4. Base API Resource (Ise hamesha last me rakhein taaki conflicts na ho)
        Route::apiResource('incentives', \App\Http\Controllers\Api\V1\Admin\IncentiveApiController::class);

    });

    
// Promotion Templates API
        Route::get('/promotion-templates/get', [\App\Http\Controllers\Api\V1\Admin\PromotionTemplateController::class, 'getTemplate']);
        Route::post('/promotion-templates/save', [\App\Http\Controllers\Api\V1\Admin\PromotionTemplateController::class, 'saveTemplate']);

        // Promotion Smart Search API
        Route::post('/promotions/search', [\App\Http\Controllers\Admin\PromotionController::class, 'searchStaff']);

        // Promotion Core Submit API
        Route::post('/promotions/submit', [\App\Http\Controllers\Admin\PromotionController::class, 'submitPromotion']);

     

 
// 🔥 ISOLATED ROUTES FOR TASK CASCADING DROPDOWNS (Prevents breaking other pages) 🔥
        Route::prefix('task-dependencies')->group(function () {
            Route::get('/companies', [\App\Http\Controllers\Api\V1\Admin\TaskDependencyController::class, 'getCompanies']);
            Route::get('/branches', [\App\Http\Controllers\Api\V1\Admin\TaskDependencyController::class, 'getBranches']);
            Route::get('/departments', [\App\Http\Controllers\Api\V1\Admin\TaskDependencyController::class, 'getDepartments']);
            Route::get('/designations', [\App\Http\Controllers\Api\V1\Admin\TaskDependencyController::class, 'getDesignations']);
            Route::get('/employees', [\App\Http\Controllers\Api\V1\Admin\TaskDependencyController::class, 'getEmployees']);
            Route::get('/members', [\App\Http\Controllers\Api\V1\Admin\TaskDependencyController::class, 'getMembers']);
        });
Route::get('/cleanup-future-followups', function() {
    $today = now()->toDateString();
    
    \Illuminate\Support\Facades\DB::beginTransaction();
    try {
        // Aaj ke sabhi pending allocations uthao
        $allocations = \App\Models\TelecallerAllocation::with(['customer', 'task'])
            ->whereDate('created_at', $today)
            ->where('call_status', 'Pending')
            ->get();
            
        $deletedCount = 0;
        
        foreach ($allocations as $alloc) {
            $customer = $alloc->customer;
            
            // Agar customer exist karta hai aur uska followup date future (kal ya uske baad) ka hai
            if ($customer && $customer->followup_date && \Carbon\Carbon::parse($customer->followup_date)->toDateString() > $today) {
                
                // Task ka target count 1 se kam kar do taaki progress bar kharab na ho
                if ($alloc->task && $alloc->task->target_count > 0) {
                    $alloc->task->decrement('target_count');
                }
                
                // Aaj ki allocation list se isko hata do
                $alloc->delete();
                $deletedCount++;
            }
        }
        
        \Illuminate\Support\Facades\DB::commit();
        
        return response()->json([
            'status' => 'Success', 
            'message' => "Total {$deletedCount} future follow-up leads aaj ki list se hata di gayi hain!"
        ]);
        
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\DB::rollBack();
        return response()->json(['status' => 'Error', 'message' => $e->getMessage()]);
    }
});
               

});


Route::get('/fix-21k-leads', function() {
    \Illuminate\Support\Facades\DB::beginTransaction();
    try {
        // 500-500 ke tukdo (chunks) me data uthayega taaki server hang na ho
        \App\Models\TelecallerAllocation::with('assignee')->chunk(500, function($allocations) {
            foreach($allocations as $alloc) {
                if($alloc->assignee) {
                    // Employee ki member_id nikal raha hai
                    $telecallerId = $alloc->assignee->member_id ?? $alloc->assignee->id;
                    
                    // Master table me update kar raha hai
                    \App\Models\InterestedCustomer::where('id', $alloc->customer_id)
                        ->update(['assigned_telecaller' => $telecallerId]);
                }
            }
        });
        \Illuminate\Support\Facades\DB::commit();
        return response()->json(['status' => 'Success', 'message' => 'Saari 21,000+ leads successfully telecallers ke naam par lock ho chuki hain!']);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\DB::rollBack();
        return response()->json(['status' => 'Error', 'message' => $e->getMessage()]);
    }
});

Route::get('/cleanup-bad-allocations', function() {
    $blacklistStatuses = [
        'Number Doesn\'t Exists call',
        'Number Does Not exists',
        'Site Visit Done Call',
        'Site Visit Done',
        'Booking Done',
        'Booking Confirm',
        'Lost',
        'Lost Lead',
        'Not Interested',
        'Not Interested Call',
        'Registry Completed',
        'registry Done'
    ];

    \Illuminate\Support\Facades\DB::beginTransaction();
    try {
        // Aisi allocations dhoondho jo telecaller ke paas Pending hain, 
        // par actual me blacklist status wali hain
        $allocationsToDelete = \App\Models\TelecallerAllocation::where('call_status', 'Pending')
            ->whereHas('customer', function ($query) use ($blacklistStatuses) {
                $query->whereIn('status', $blacklistStatuses);
            })->get();

        $deletedCount = 0;
        $customerIdsToUnlock = [];

        foreach ($allocationsToDelete as $alloc) {
            $customerIdsToUnlock[] = $alloc->customer_id;
            // Telecaller ki list se hata do
            $alloc->delete(); 
            $deletedCount++;
        }

        // Master table se unko free (unlock) kar do
        if (!empty($customerIdsToUnlock)) {
            \App\Models\InterestedCustomer::whereIn('id', $customerIdsToUnlock)
                ->update(['assigned_telecaller' => null]);
        }

        \Illuminate\Support\Facades\DB::commit();
        
        return response()->json([
            'status' => 'Success', 
            'message' => "Total {$deletedCount} galat allocations (Lost/Not Interested) telecallers ke panel se hata di gayi hain!"
        ]);
        
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\DB::rollBack();
        return response()->json(['status' => 'Error', 'message' => $e->getMessage()]);
    }
});

