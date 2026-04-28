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
        // 1. Jinhe access mil chuka hai unki list nikal lo
        $accessList = TelecallerAccess::pluck('staff_id')->toArray();

        // 2. Saare Employees fetch karein
        $employees = Employee::whereNotNull('member_id')->get()->map(function($e) use ($accessList) {
            return [
                'staff_id' => (string) $e->member_id,
                'name' => $e->full_name,
                'role' => 'Employee',
                'has_access' => in_array((string)$e->member_id, $accessList)
            ];
        });

        // 3. Saare Members fetch karein
        $members = Member::whereNotNull('member_id')->get()->map(function($m) use ($accessList) {
            return [
                'staff_id' => (string) $m->member_id,
                'name' => $m->member_name,
                'role' => 'Member',
                'has_access' => in_array((string)$m->member_id, $accessList)
            ];
        });

        // Combine karke unique list banayein
        $combinedStaff = $employees->concat($members)->unique('staff_id')->values();

        return response()->json(['status' => 'success', 'data' => $combinedStaff]);
    }

    public function toggleAccess(Request $request)
    {
        $request->validate(['staff_id' => 'required']);
        $staffId = $request->staff_id;

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