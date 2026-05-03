<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\Admin\DebitVoucherWebController;
use App\Http\Controllers\Admin\CompanyWebController;
/*
|--------------------------------------------------------------------------
| Web Routes (User Interface)
|--------------------------------------------------------------------------
*/

// Agar koi direct base URL khele toh Admin Login par redirect ho
Route::get('/', function () {
    // return view('coming_soon'); 
    return view('welcome'); 
});

Route::get('/admin', function () {
   return redirect('/admin/login'); 
});

// Admin Panel UI Routes Group
Route::prefix('admin')->group(function () {
    
    // Login Page
    Route::get('/login', function () {
        return view('admin.auth.login');
    })->name('admin.login.view');

    // Dashboard Page
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard.view');

    // YAHAN ADD KIYA HAI: Branch Page (Browser yahan aayega HTML dekhne)
    Route::get('/branches', function () {
        return view('admin.branch'); 
    })->name('admin.branches.view');

    Route::get('/employees', function () { return view('admin.employees'); });

    Route::get('/designations', function () { return view('admin.desigantions'); });

    Route::get('/salaries', function () {
    return view('admin.salaries');
    });

    Route::get('/customers', function () {
    return view('admin.customers');
    });

    Route::get('/members', function () {
    return view('admin.members');
});

Route::get('/member-designations', function () {
    return view('admin.member_designations');
});

Route::get('/agents', function () {
    return view('admin.agent');
});

Route::get('/landowners', function () {
    return view('admin.landowners');
});

Route::get('/vendors', function () {
    return view('admin.vendors');
});

Route::get('/interested-customers', function () {
    return view('admin.interested_customers');
});

Route::get('/give-access', function () {
    return view('admin.give_access');
});

Route::get('/ledgers', function () {
    return view('admin.ledgers');
});

Route::get('/letterheads', function () {
    return view('admin.letterheads');
});


Route::get('/letterheads/print/{id}', [\App\Http\Controllers\Api\V1\Admin\LetterheadController::class, 'printPreview']);

Route::get('/id-cards', function () { return view('admin.id_cards'); });
Route::get('/id-cards/print/{type}/{id}', [\App\Http\Controllers\Api\V1\Admin\IdCardController::class, 'printPreview'])
    ->where('id', '.*');


// List Page
    Route::get('/debit_vouchers', [DebitVoucherWebController::class, 'index'])->name('admin.debit_vouchers.index');
    // Create Form Page
    Route::get('/debit_vouchers/create', [DebitVoucherWebController::class, 'create'])->name('admin.debit_vouchers.create');

    Route::get('/debit_vouchers/print/{id}', [App\Http\Controllers\Admin\DebitVoucherWebController::class, 'print'])->name('admin.debit_vouchers.print');

});

Route::get('/admin/companies', [CompanyWebController::class, 'index'])->name('admin.companies.index');

// Route::middleware(['auth'])->prefix('user')->name('user.')->group(function () {
    Route::get('user/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Baki routes: profile, my-properties, etc.


// });
