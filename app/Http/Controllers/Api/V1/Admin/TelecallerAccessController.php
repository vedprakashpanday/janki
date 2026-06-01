<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\TelecallerAccess;
use App\Models\Employee;
use App\Models\Member;
use Illuminate\Http\Request;

class TelecallerAccessController extends Controller
{
    public function index()
    {
        $accessList = TelecallerAccess::pluck('staff_id')->toArray();

        // ==========================================
        // 🛡️ 1. DATA FILTER LOGIC
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        $empQuery = Employee::whereNotNull('member_id');
        $memQuery = Member::whereNotNull('member_id');

        if (!in_array($user->email, $developerEmails)) {
            if ($user->hasRole(['CEO', 'Director'])) {
                $empQuery->where('company_id', $user->company_id);
                $memQuery->where('company_id', $user->company_id);
            } else {
                $empQuery->where('branch_id', $user->branch_id);
                $memQuery->where('branch_id', $user->branch_id);
            }
        }
        // ==========================================

        $employees = $empQuery->get()->map(function($e) use ($accessList) {
            return [
                'staff_id' => (string) $e->member_id,
                'name' => $e->full_name,
                'role' => 'Employee',
                'has_access' => in_array((string)$e->member_id, $accessList)
            ];
        });

        $members = $memQuery->get()->map(function($m) use ($accessList) {
            return [
                'staff_id' => (string) $m->member_id,
                'name' => $m->member_name,
                'role' => 'Member',
                'has_access' => in_array((string)$m->member_id, $accessList)
            ];
        });

        $combinedStaff = $employees->concat($members)->unique('staff_id')->values();

        return response()->json(['status' => 'success', 'data' => $combinedStaff]);
    }
    public function toggleAccess(Request $request)
    {
       $request->validate(['staff_id' => 'required']);
        $staffId = $request->staff_id;

        // ==========================================
        // 🛡️ 2. SECURITY & OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            // Find out if this staff_id is an Employee or Member to check their branch
            $staff = Employee::where('member_id', $staffId)->first() ?? Member::where('member_id', $staffId)->first();
            
            if (!$staff) {
                return response()->json(['status' => 'error', 'message' => 'Staff not found.'], 404);
            }

            if ($user->hasRole(['CEO', 'Director']) && $staff->company_id != $user->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized! Staff belongs to another company.'], 403);
            }
            if (!$user->hasRole(['CEO', 'Director']) && $staff->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized! Staff belongs to another branch.'], 403);
            }
        }
        // ==========================================

        $access = TelecallerAccess::where('staff_id', $staffId)->first();

        if ($access) {
            $access->delete(); // Agar pehle se access hai toh Remove kar do
            return response()->json(['status' => 'success', 'message' => 'Access Removed Successfully', 'has_access' => false]);
        } else {
            TelecallerAccess::create(['staff_id' => $staffId]); // Agar nahi hai toh Add kar do
            return response()->json(['status' => 'success', 'message' => 'Access Granted Successfully', 'has_access' => true]);
        }
    }
}