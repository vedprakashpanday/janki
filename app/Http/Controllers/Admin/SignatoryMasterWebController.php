<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;

class SignatoryMasterWebController extends Controller
{
    public function index()
    {
        // Active companies page par bhej rahe hain
        $companies = Company::where('status', 'active')->get();
        return view('admin.signatory_master.index', compact('companies'));
    }
}