<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DebitVoucherWebController;
use App\Http\Controllers\Admin\CompanyWebController;
use App\Http\Controllers\Admin\CustomerWebController;






/*
|--------------------------------------------------------------------------
| Web Routes (User Interface / Blade Views)
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. PUBLIC ROUTES & REDIRECTS
// ==========================================
Route::get('/', function () {
  return view('coming_soon');
     //return view('welcome');
});

Route::get('/employee-print', [\App\Http\Controllers\Api\V1\Admin\EmployeeController::class, 'printPage'])->name('employee.print');

Route::get('/adm', function () {
    return redirect('/admin/login');
});


Route::get('/emp', function () {
    return redirect('/employee/login');
});

Route::get('/mem', function () {
    return redirect('/member/login');
});


Route::get('/ceo', function () {
    return redirect('/ceo/login');
});

$sharedWebRoutes = function () {

    // 1. Master Grade Setup (Yahan extra duplication hata diya hai)
    Route::get('/role-manager', function () {
        return view('admin.role_manager.index');
    })->name('roles');

    // 2. Employee Exception Manager (Individual Overrides)
    Route::get('/role-manager/employee', function () {
        return view('admin.role_manager.employee_roles');
    })->name('roles.employee');

    // 3. 🔥 NAYA: Member Master Grade Setup
    Route::get('/role-manager/member-grades', function () {
        return view('admin.role_manager.member_index');
    })->name('roles.member_grades');

    // 3. Member Exception Manager (Individual Overrides)
    Route::get('/role-manager/member', function () {
        return view('admin.role_manager.member_roles');
    })->name('roles.member');

    Route::get('/module-master', function () {
        return view('admin.modules.index');
    })->name('modules');
    Route::get('/action-master', function () {
        return view('admin.actions.index');
    })->name('actions');
    Route::get('/give-access', function () {
        return view('admin.give_access');
    })->name('give_access');
    Route::get('/panel-access', function () {
        return view('admin.panel_access');
    })->name('panel_access');

    // 🔴 Companies & Hierarchy
    Route::get('/companies', [CompanyWebController::class, 'index'])->name('companies.index');
    Route::get('/branches', function () {
        return view('admin.branch');
    })->name('branches');
    Route::get('/departments', function () {
        return view('admin.departments.index');
    })->name('departments');


    Route::get('/department-requests', function () {
        return view('admin.departments.requests'); // Nayi file ka path
    })->name('department_requests');


    Route::get('/designations', function () {
        return view('admin.desigantions');
    })->name('designations');

    // 🔴 Core Executives
    Route::get('/super-admins', function () {
        return view('admin.super_admins');
    })->name('super_admins.index');
    Route::get('/directors', function () {
        return view('admin.directors');
    })->name('directors');

   // 🟢 Finance & Ledgers
    Route::get('/ledgers', function () {
        return view('admin.ledgers');
    })->name('ledgers');
    
    // 🔥 NAYA: Debit Vouchers - Current Date (Slug: dv_*)
    Route::get('/debit-vouchers', [\App\Http\Controllers\Admin\DebitVoucherWebController::class, 'index'])->name('debit_vouchers.index');
    
    // 🔥 NAYA: Debit Vouchers - All Time Directory (Slug: dv_dir_*)
    Route::get('/debit-vouchers-directory', [\App\Http\Controllers\Admin\DebitVoucherWebController::class, 'directory'])->name('debit_vouchers.directory');
    
    Route::get('/debit_vouchers/create', [\App\Http\Controllers\Admin\DebitVoucherWebController::class, 'create'])->name('debit_vouchers.create');
    Route::get('/debit_vouchers/print/{id}', [\App\Http\Controllers\Admin\DebitVoucherWebController::class, 'print'])->name('debit_vouchers.print');

    // 🟢 CRM, Network & Associates
    // Route::get('/customers', function () {
    //     return view('admin.customers');
    // })->name('customers');
    Route::get('/interested-customers', function () {
        return view('admin.interested_customers');
    })->name('interested_customers');

    // 🟢 CRM, Network & Associates (Inside $sharedWebRoutes)
Route::get('/general-leads', function () {
    return view('admin.general_leads');
})->name('general_leads');

Route::get('/interested-leads', function () {
    return view('admin.interested_leads');
})->name('interested_leads');

  // 🟢 CRM, Network & Associates (Inside $sharedWebRoutes)
    Route::get('/phases', function () {
        return view('phases.index'); 
    })->name('phases.index');

    Route::get('/phases/create', function () {
        return view('phases.create'); 
    })->name('phases.create');

    // 👇 YEH NAYA EDIT ROUTE ADD KAREIN 👇
    Route::get('/phases/{id}/edit', function ($id) {
        return view('phases.edit', compact('id')); 
    })->name('phases.edit');



    Route::get('/members', function () {
        return view('admin.members');
    })->name('members');

    Route::get('/member-designations', function () {
        return view('admin.member_designations');
    })->name('member_designations');

    Route::get('/agents', function () {
        return view('admin.agent');
    })->name('agents');

    Route::get('/landowners', function () {
        return view('admin.landowners');
    })->name('landowners');

    Route::get('/vendors', function () {
        return view('admin.vendors');
    })->name('vendors');

    // 🟢 HR & Users
    Route::get('/employees', function () {
        return view('admin.employees');
    })->name('employees');

    // 👇 YAHAN SE NAYA ROUTE ADD KAREIN 👇
    Route::get('/employee-directory', function () {
        return view('admin.employee_directory'); // Nayi file ka naam
    })->name('employee_directory');



    Route::get('/employee-requests', function () {
        return view('admin.employee_requests');
    })->name('employee_requests');
    Route::get('/salaries', function () {
        return view('admin.salaries');
    })->name('salaries');

    // 🟢 Task Management (Shared View for Admin, Employee, Director)
    Route::get('/tasks', function () {
        return view('admin.tasks.index'); 
    })->name('tasks.index');

    // 🔴 Developer Task Tracking Setup (Only Accessible to Developers via API/UI block)
    Route::get('/task-tracking-setup', function () {
        return view('admin.tasks.tracking_setup');
    })->name('tasks.tracking_setup');

// 1. Employee / Staff Tasks (Permission: task_*)
    Route::get('/tasks/staff', function () {
        return view('admin.tasks.employee_tasks'); // Hum iske liye naya blade banayenge
    })->name('tasks.staff');

    // 2. Member / Associate Tasks (Permission: task_mem_*)
    Route::get('/tasks/associates', function () {
        return view('admin.tasks.member_tasks'); // Hum iske liye naya blade banayenge
    })->name('tasks.associates');

    // 🟢 Print & Utilities
    Route::get('/letterheads', function () {
        return view('admin.letterheads');
    })->name('letterheads');
    Route::get('/letterheads/print/{id}', [\App\Http\Controllers\Api\V1\Admin\LetterheadController::class, 'printPreview'])->name('letterheads.print');
    Route::get('/id-cards', function () {
        return view('admin.id_cards');
    })->name('id_cards');
    Route::get('/id-cards/print/{type}', [\App\Http\Controllers\Api\V1\Admin\IdCardController::class, 'printPreview'])->name('id_cards.print');

    Route::get('/terms-conditions', function () {
    return view('admin.terms_conditions');
})->name('terms_conditions');

// 🟢 Task Progress Report Dashboard
    Route::get('/task-reports', function () {
        return view('admin.tasks.report');
    })->name('tasks.report');


    // Shared Web Routes ke andar sabse niche add karein
Route::get('/welcome-letter', function () {
    return view('employee.welcome_letter'); // Path hamne generic/shared folder me rakha hai
})->name('welcome_letter');

// --- Notices Module ---
    Route::get('/notices', function () {
        return view('admin.notices.index');
    })->name('notices.index');

    Route::get('/my-notices', function () {
        return view('admin.notices.my_notices');
    })->name('my_notices');

Route::get('/notices/print/{id}', [\App\Http\Controllers\Admin\NoticeWebController::class, 'printNotice'])->name('notices.print');

// 🟢 Travel Allowance (TA) Management
    Route::get('/travel-allowances', function () {
        return view('admin.travel_allowances.index'); // Phase 3 me yeh blade banayenge
    })->name('travel_allowances.index');
    
    // Print TA Route
    Route::get('/travel-allowances/print/{id}', [\App\Http\Controllers\Api\V1\Admin\TravelAllowanceApiController::class, 'printPreview'])->name('travel_allowances.print');




 // 🟢 Leaves & Applications
    Route::get('/leave-applications', function () {
        return view('admin.leave_applications.index'); 
    })->name('leave_applications.index');

    // 🔥 NAYA: Member Leave Applications ka Route add karein
    Route::get('/member-leave-applications', function () {
        return view('admin.leave_applications.member_index'); 
    })->name('member_leave_applications.index');

   
   Route::get('/leave-applications/print/{id}', [\App\Http\Controllers\Api\V1\Admin\LeaveApplicationApiController::class, 'printPreview'])->name('leave.print');

    Route::get('/fine-penalties', function () {
    return view('admin.fine_penalties.index');
})->name('fine_penalties.index');

Route::get('/fine-penalties/print/{id}', [\App\Http\Controllers\Api\V1\Admin\FinePenaltyApiController::class, 'printPreview'])->name('fine_penalties.print');
Route::view('/attendance', 'admin.attendance.index');

// Auto Task Settings UI
    Route::get('/auto-task-settings', function () {
        return view('admin.tasks.auto_settings');
    })->name('auto_task_settings');

    // Telecaller Calling Portal UI
    Route::get('/my-calling-portal', function () {
        return view('employee.calling_portal'); // Hum ye view banayenge
    })->name('my_calling_portal');

    // Attendance Time Window UI (Shared)
    Route::get('/attendance-windows', function () {
        return view('admin.attendance.time_windows');
    })->name('attendance_windows');

// --- My Penalties (Employee Portal) ---
    Route::get('/my-penalties', function () {
        return view('employee.my_penalties'); // Ye view employee folder me banayenge
    })->name('my_penalties');

    Route::get('/fine-penalties/print-all', [\App\Http\Controllers\Api\V1\Admin\FinePenaltyApiController::class, 'printAllPreview'])->name('fine_penalties.print_all');

   Route::get('/site-allocations', function () {
    return view('admin.site_allocations.index');
})->name('site_allocations');

Route::get('/site-entries', function () {
    return view('site_entries.index'); // Admin aur Employee dono ke liye shared view
})->name('site_entries');

 Route::get('site-entries/print/{id}', [\App\Http\Controllers\Api\V1\Admin\SiteEntryController::class, 'printPreview'])->name('site_entries.print');

 Route::get('/site-allocations/print/{id}', [\App\Http\Controllers\Api\V1\Admin\SiteAllocationController::class, 'printPreview'])->name('site_allocations.print');

// 🔥 Vehicle Trip Slip Routes (Shared for all portals)
    Route::get('/vehicle-trips/generate-blank', [\App\Http\Controllers\Api\V1\Admin\VehicleTripController::class, 'generateBlankSlips'])->name('vehicle_trips.generate_blank');
    Route::get('/vehicle-trips/print', [\App\Http\Controllers\Api\V1\Admin\VehicleTripController::class, 'printPreview'])->name('vehicle_trips.print');

    Route::get('/rules-regulations', function () {
    return view('admin.rules_regulations');
})->name('rules_regulations');

// web.php me $sharedWebRoutes closure ke andar ye route add kar dena:
Route::get('/visiting-cards', function () {
    return view('admin.visiting_cards');
})->name('visiting_cards');

Route::get('/member-device-tracking', function () {
        return view('admin.members.device_tracking'); 
    })->name('member.device.tracking');

    // 🟢 Shared Profile Page
    Route::get('/my-profile', function () {
        // Ye view hum ek shared folder me rakhenge taaki dono use kar sakein
        return view('shared.profile'); 
    })->name('my_profile');
    
// 🟢 Temporary Receipts (Form & Print)
    Route::get('/receipts/create', function () {
        return view('admin.receipts.index'); 
    })->name('admin.receipts.create');

    // 🔥 NAYA PRINT ROUTE (Direct Data Passing) 🔥
    Route::get('/receipts/print/{id}', function ($id) {
        $receipt = \App\Models\TempReceipt::with(['company', 'branch', 'approvedByEmployee', 'authorizedCeo'])->findOrFail($id);
        return view('admin.receipts.print', compact('receipt'));
    })->name('admin.receipts.print');

    Route::get('bank-details/index', function () {
        return view('bank-details.index'); 
    });

    Route::get('/bank-details-print', [\App\Http\Controllers\Api\V1\Admin\BankDetailController::class, 'printPreview'])->name('bank_details.print');


    // Page 1: Daily View
    Route::get('/bank-details-daily', function () {
        return view('bank-details.index'); 
    })->name('bank_details.daily');

    // Page 2: Directory View
    Route::get('/bank-details-directory', function () {
        return view('bank-details.bank-details-directory'); 
    })->name('bank_details.directory');

    // Greeting Templates Settings Page
    Route::get('/greeting-settings', function () {
        return view('admin.greeting_settings');
    })->name('greeting_settings');

// Upcoming Events Dashboard
Route::get('/upcoming-events', function () {
    return view('admin.events_dashboard');
})->name('upcoming_events');

// Individual Greetings Page
    Route::get('/my-greetings', function () {
        return view('shared.my_greetings'); // Ise hum shared folder me banayenge
    })->name('my_greetings');

    // Matrix UI Route
    Route::get('/hr-attendance-matrix', function () {
        return view('admin.attendance.matrix'); // Jo file humne pichle step me banayi thi
    })->name('hr_attendance_matrix');
Route::get('/customers', [CustomerWebController::class, 'index'])->name('customers.index');
Route::get('/cust_dir', [CustomerWebController::class, 'dir'])->name('customers.dir');

// Promotion Templates UI
    Route::get('/promotion-templates', function () {
        return view('admin.promotion_templates.index');
    })->name('promotion_templates');


// Promote Staff UI
    Route::get('/promote-staff', [\App\Http\Controllers\Admin\PromotionController::class, 'index'])->name('promote_staff');

    Route::get('/members-directory', function () {
    return view('shared.members_directory'); 
})->name('members_directory');

Route::get('/salaries/print', [\App\Http\Controllers\Api\V1\Admin\SalaryApiController::class, 'printRegister'])->name('salaries.print');

// Downline Tree UI Route
    Route::get('/my-downline', function () {
        return view('shared.my_downline'); // Ye blade file hum next step me banayenge
    })->name('my_downline');

};

//  Shared routes ends here

// ==========================================
// 2. ADMIN PORTAL (Views)
// ==========================================
Route::prefix('admin')->name('admin.')->group(function () use ($sharedWebRoutes) {
    Route::get('/login', function () {
        return view('admin.auth.login');
    })->name('login');

    // 🔥 THE 500 CRASH FIX: Ye missing route alias add karein 🔥
    Route::get('/fallback-login', function () {
        return redirect('/admin/login');
    })->name('login.view');




    // 🔥 ADD THIS LINE (The Alias) so the system doesn't crash on redirect
    Route::get('/general-leads/export', [\App\Http\Controllers\Api\V1\Admin\InterestedCustomerController::class, 'downloadExport'])->name('general_leads.export');

   
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // 🔥 Admin Letter Edit View
    Route::get('/welcome-letter/edit', function () {
        return view('admin.welcome_letter_edit');
    })->name('welcome_letter_edit');

    
    // Yahan saare routes load ho gaye Admin ke liye
    $sharedWebRoutes();
});


// ==========================================
// 3. EMPLOYEE PORTAL (Views)
// ==========================================
Route::prefix('employee')->name('employee.')->group(function () use ($sharedWebRoutes) {
    Route::get('/login', function () {
        return view('employee.login');
    })->name('login');
    Route::get('/dashboard', function () {
        return view('employee.dashboard');
    })->name('dashboard');

   

    // Yahan saare routes load ho gaye Employee ke liye (jaisa aapne kaha tha)
    $sharedWebRoutes();
});


// ==========================================
// 4. CEO & DIRECTOR PORTALS (Agar Future Me Alag Prefix Chahiye)
// ==========================================
Route::prefix('ceo')->name('ceo.')->group(function () use ($sharedWebRoutes) {
    Route::get('/login', function () { return view('admin.auth.superadmin'); })->name('login');
     Route::get('/dashboard', function () { return view('admin.dashboard'); })->name('dashboard');
    $sharedWebRoutes();
});

Route::prefix('director')->name('director.')->group(function () use ($sharedWebRoutes) {
    // Route::get('/login', function () { return view('admin.auth.login'); })->name('login');
    // Route::get('/dashboard', function () { return view('admin.dashboard'); })->name('dashboard');
    // $sharedWebRoutes();
});

// ==========================================
// 5. CUSTOMER PORTAL
// ==========================================
Route::prefix('customer')->name('customer.')->group(function () use ($sharedWebRoutes) {
    // Route::get('/login', function () { return view('customer.login'); })->name('login');
    // Route::get('/dashboard', function () { return view('customer.dashboard'); })->name('dashboard');
    // $sharedWebRoutes();
});



// ==========================================
// 6. MEMBER PORTAL (Views)
// ==========================================
Route::prefix('member')->name('member.')->group(function () use ($sharedWebRoutes) {
    Route::get('/login', function () { 
        return view('member.login'); // Ye view hum abhi banayenge
    })->name('login');
    
    Route::get('/dashboard', function () { 
        return view('member.dashboard'); // Dashboard view (isme tum total income wagerah dikhaoge)
    })->name('dashboard');

    // Tumhare saare shared routes (Notices, Rules, Welcome Letter) yahan load ho jayenge
    $sharedWebRoutes();
});