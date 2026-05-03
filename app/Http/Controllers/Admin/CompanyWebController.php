<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyWebController extends Controller
{
    // Sirf index.blade.php view load karne ke liye
    public function index()
    {
        return view('admin.companies.index');
    }
}