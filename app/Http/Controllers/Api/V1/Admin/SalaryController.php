<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Salary;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    public function index()
    {
       $query = Employee::with('salary')->latest();

        // ==========================================
        // 🛡️ 1. DATA FILTER LOGIC
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            if ($user->hasRole(['CEO', 'Director'])) {
                // CEO/Director ko apni poori company ki salary list dikhegi
                $query->where('company_id', $user->company_id);
            } else {
                // Branch HR/Manager ko sirf apni branch ki dikhegi
                $query->where('branch_id', $user->branch_id);
            }
        }
        // ==========================================

        $employees = $query->get();
        
        $data = $employees->map(function ($emp) {
            return [
                'id' => $emp->id,
                'member_id' => $emp->member_id,
                'full_name' => $emp->full_name,
                'designation' => $emp->designation,
                'salary' => $emp->salary ? $emp->salary->amount : null,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:adm_regist,member_id',
            'amount' => 'required|numeric|min:0'
        ]);

       $employee = Employee::where('member_id', $request->member_id)->firstOrFail();

        // ==========================================
        // 🛡️ 2. STORE OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            if ($user->hasRole(['CEO', 'Director']) && $employee->company_id != $user->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized! Employee belongs to another company.'], 403);
            }
            if (!$user->hasRole(['CEO', 'Director']) && $employee->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized! Employee belongs to another branch.'], 403);
            }
        }
        // ==========================================

        Salary::updateOrCreate(
            ['employee_id' => $employee->id],
            $request->only(['amount', 'basic_pay', 'hra', 'da', 'medical_allowance', 'travel_allowance', 'other_allowance'])
        );

        return response()->json(['status' => 'success', 'message' => 'Salary updated successfully']);
    }

    public function show($id)
    {
       $employee = Employee::with('salary')->findOrFail($id);
        
        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            if ($user->hasRole(['CEO', 'Director']) && $employee->company_id != $user->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
            if (!$user->hasRole(['CEO', 'Director']) && $employee->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }
        
        return response()->json([
            'status' => 'success', 
            'data' => [
                'id' => $employee->id,
                'member_id' => $employee->member_id,
                'full_name' => $employee->full_name,
                'amount' => $employee->salary ? $employee->salary->amount : '',
                'basic_pay' => $employee->salary ? $employee->salary->basic_pay : '',
                'hra' => $employee->salary ? $employee->salary->hra : '',
                'da' => $employee->salary ? $employee->salary->da : '',
                'medical_allowance' => $employee->salary ? $employee->salary->medical_allowance : '',
                'travel_allowance' => $employee->salary ? $employee->salary->travel_allowance : '',
                'other_allowance' => $employee->salary ? $employee->salary->other_allowance : '',
            ]
        ]);
    }

   public function update(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0'
        ]);

        $employee = Employee::findOrFail($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            if ($user->hasRole(['CEO', 'Director']) && $employee->company_id != $user->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
            if (!$user->hasRole(['CEO', 'Director']) && $employee->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        Salary::updateOrCreate(
            ['employee_id' => $id],
            $request->only(['amount', 'basic_pay', 'hra', 'da', 'medical_allowance', 'travel_allowance', 'other_allowance'])
        );

        return response()->json(['status' => 'success', 'message' => 'Salary updated successfully']);
    }
}