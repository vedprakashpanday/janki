<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DebitVoucherWebController;
use App\Http\Controllers\Admin\CompanyWebController;

/*
|--------------------------------------------------------------------------
| Web Routes (User Interface / Blade Views)
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. PUBLIC ROUTES & REDIRECTS
// ==========================================
Route::get('/', function () {
    return view('coming_soon'); // Future mein 'welcome' kar dena
});

Route::get('/adm', function () {
    return redirect('/admin/login');
});


// ==========================================
// 2. ADMIN PORTAL (Views)
// ==========================================
Route::prefix('admin')->name('admin.')->group(function () {

    // Auth & Dashboard
    Route::get('/login', function () {
        return view('admin.auth.login');
    })->name('login');
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // System, Access & Roles
    Route::get('/role-manager', function () {
        return view('admin.role_manager.index');
    })->name('roles');
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

    // Companies & Hierarchy
    Route::get('/companies', [CompanyWebController::class, 'index'])->name('companies.index');
    Route::get('/branches', function () {
        return view('admin.branch');
    })->name('branches');
    Route::get('/departments', function () {
        return view('admin.departments.index');
    })->name('departments');
    Route::get('/designations', function () {
        return view('admin.desigantions');
    })->name('designations');

    // HR & Users
    Route::get('/super-admins', function () {
        return view('admin.super_admins');
    })->name('super_admins.index');
    Route::get('/directors', function () {
        return view('admin.directors');
    })->name('directors');
    Route::get('/employees', function () {
        return view('admin.employees');
    })->name('employees');
    Route::get('/salaries', function () {
        return view('admin.salaries');
    })->name('salaries');

    // CRM, Network & Associates
    Route::get('/customers', function () {
        return view('admin.customers');
    })->name('customers');
    Route::get('/interested-customers', function () {
        return view('admin.interested_customers');
    })->name('interested_customers');
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

    // Finance & Ledgers
    Route::get('/ledgers', function () {
        return view('admin.ledgers');
    })->name('ledgers');
    Route::get('/debit_vouchers', [DebitVoucherWebController::class, 'index'])->name('debit_vouchers.index');
    Route::get('/debit_vouchers/create', [DebitVoucherWebController::class, 'create'])->name('debit_vouchers.create');
    Route::get('/debit_vouchers/print/{id}', [DebitVoucherWebController::class, 'print'])->name('debit_vouchers.print');

    // Print & Utilities
    Route::get('/letterheads', function () {
        return view('admin.letterheads');
    })->name('letterheads');
    Route::get('/letterheads/print/{id}', [\App\Http\Controllers\Api\V1\Admin\LetterheadController::class, 'printPreview'])->name('letterheads.print');

    Route::get('/id-cards', function () {
        return view('admin.id_cards');
    })->name('id_cards');
    Route::get('/id-cards/print/{type}/{id}', [\App\Http\Controllers\Api\V1\Admin\IdCardController::class, 'printPreview'])
        ->where('id', '.*')->name('id_cards.print');
});


// ==========================================
// 3. EMPLOYEE PORTAL (Views)
// ==========================================
Route::prefix('employee')->name('employee.')->group(function () {

    // Auth & Dashboard
    Route::get('/login', function () {
        return view('employee.login');
    })->name('login');
    Route::get('/dashboard', function () {
        return view('employee.dashboard');
    })->name('dashboard');

    // Employee specific views aage yahan add honge, e.g.:
    // Route::get('/my-attendance', function () { return view('employee.attendance'); })->name('attendance');
});


// ==========================================
// 4. CUSTOMER PORTAL (Views - Future setup)
// ==========================================
Route::prefix('customer')->name('customer.')->group(function () {

    // Auth & Dashboard
    // Route::get('/login', function () { return view('customer.login'); })->name('login');
    // Route::get('/dashboard', function () { return view('customer.dashboard'); })->name('dashboard');

});
