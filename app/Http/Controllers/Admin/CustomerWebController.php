<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomerWebController extends Controller
{
    public function index()
    {
        // Yahan se saara context logic hata diya taaki 500 error na aaye
        return view('admin.customers');
    }

     public function dir()
    {
        // Yahan se saara context logic hata diya taaki 500 error na aaye
        return view('admin.customer_directory');
    }
}