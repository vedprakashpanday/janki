<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (User Interface)
|--------------------------------------------------------------------------
*/

// Agar koi direct base URL khele toh Admin Login par redirect ho
Route::get('/', function () {
    return redirect('/admin/login'); // Fixed: Removed 'api/' from redirect
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

});