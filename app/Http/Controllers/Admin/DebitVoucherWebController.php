<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DebitVoucherWebController extends Controller
{
    // List/Datatable dikhane ke liye
    public function index()
    {
        return view('admin.debit_vouchers.index');
    }

    // Form dikhane ke liye
    public function create()
    {
        return view('admin.debit_vouchers.index');
    }

    public function print($id)
{
    $voucher = \App\Models\DebitVoucher::findOrFail($id);
    
    // View ya Print mode check karne ke liye
    $mode = request()->query('mode', 'print'); 
    
    return view('admin.debit_vouchers.print', compact('voucher', 'mode'));
}
}