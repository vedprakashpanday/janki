<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccessControlController extends Controller
{
    public function index(Request $request)
    {
       // Future Proofing: Query joined with adm_regist to fetch branch/company info
        $query = EmployeeLogin::query()
            ->join('adm_regist', 'employee_logins.user_id', '=', 'adm_regist.member_id')
            ->select('employee_logins.*', 'adm_regist.full_name', 'adm_regist.branch_id', 'adm_regist.company_id');

        // ==========================================
        // 🛡️ 1. DATA FILTER LOGIC (Strict Scoping)
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            if ($user->hasRole(['CEO', 'Director'])) {
                // CEO/Director ko poori company ka access dikhega
                $query->where('adm_regist.company_id', $user->company_id);
            } else {
                // Branch Managers ko sirf apni branch ka access dikhega
                $query->where('adm_regist.branch_id', $user->branch_id);
            }
        } else {
            // Master Admins ke liye Frontend Branch Filter
            if ($request->has('branch_id') && $request->branch_id != '') {
                $query->where('adm_regist.branch_id', $request->branch_id);
            }
        }
        // ==========================================
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('employee_logins.user_id', 'LIKE', "%{$search}%")
                  ->orWhere('employee_logins.panel_id', 'LIKE', "%{$search}%")
                  ->orWhere('adm_regist.full_name', 'LIKE', "%{$search}%");
            });
        }

        $totalData = EmployeeLogin::count();
        $totalFiltered = $query->count();

        // 10-10 Chunk Pagination (Works for both desktop table & mobile cards)
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        if ($length != -1) {
            $query->offset($start)->limit($length);
        }

        $accesses = $query->orderBy('employee_logins.id', 'desc')->get();

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $accesses
        ]);
    }

    // REJECT DEVICE REQUEST
    public function rejectDeviceRequest(Request $request)
    {
        $request->validate([
            'panel_id' => 'required|string',
            'device_token' => 'required|string'
        ]);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->firstOrFail();

        // ==========================================
        // 🛡️ 2. OWNERSHIP CHECK FOR DEVICES
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            $employee = \Illuminate\Support\Facades\DB::table('adm_regist')->where('member_id', $login->user_id)->first();
            
            if ($employee) {
                if ($user->hasRole(['CEO', 'Director']) && $employee->company_id != $user->company_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! Employee belongs to another company.'], 403);
                }
                if (!$user->hasRole(['CEO', 'Director']) && $employee->branch_id != $user->branch_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! Employee belongs to another branch.'], 403);
                }
            }
        }
        // ==========================================
        
        // Remove token from other_devices array
        $otherDevices = $login->other_devices ?? [];
        $filteredDevices = array_filter($otherDevices, function($item) use ($request) {
            return $item['device_token'] !== $request->device_token;
        });

        $login->update([
            'other_devices' => array_values($filteredDevices)
        ]);

        return response()->json(['status' => 'success', 'message' => 'Device request rejected successfully.']);
    }

    // BLOCK DEVICE
    public function blockDevice(Request $request)
    {
        $request->validate([
            'panel_id' => 'required|string',
            'device_token' => 'required|string'
        ]);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->firstOrFail();

        // ==========================================
        // 🛡️ 2. OWNERSHIP CHECK FOR DEVICES
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            $employee = \Illuminate\Support\Facades\DB::table('adm_regist')->where('member_id', $login->user_id)->first();
            
            if ($employee) {
                if ($user->hasRole(['CEO', 'Director']) && $employee->company_id != $user->company_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! Employee belongs to another company.'], 403);
                }
                if (!$user->hasRole(['CEO', 'Director']) && $employee->branch_id != $user->branch_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! Employee belongs to another branch.'], 403);
                }
            }
        }
        // ==========================================
        
        $otherDevices = $login->other_devices ?? [];
        $blockedDevices = $login->blocked_devices ?? [];

        // Move from other_devices to blocked_devices
        $filteredDevices = array_filter($otherDevices, function($item) use ($request) {
            return $item['device_token'] !== $request->device_token;
        });

        if (!in_array($request->device_token, $blockedDevices)) {
            $blockedDevices[] = $request->device_token;
        }

        // Agar wo primary ya secondary active chal raha tha toh wahan se bhi hatao safety ke liye
        $updateData = [
            'other_devices' => array_values($filteredDevices),
            'blocked_devices' => $blockedDevices
        ];
        if($login->primary_device === $request->device_token) $updateData['primary_device'] = null;
        if($login->secondary_device === $request->device_token) $updateData['secondary_device'] = null;

        $login->update($updateData);

        return response()->json(['status' => 'success', 'message' => 'Device has been permanently blocked.']);
    }

    // UNBLOCK DEVICE
    public function unblockDevice(Request $request)
    {
        $request->validate([
            'panel_id' => 'required|string',
            'device_token' => 'required|string'
        ]);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->firstOrFail();

        // ==========================================
        // 🛡️ 2. OWNERSHIP CHECK FOR DEVICES
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            $employee = \Illuminate\Support\Facades\DB::table('adm_regist')->where('member_id', $login->user_id)->first();
            
            if ($employee) {
                if ($user->hasRole(['CEO', 'Director']) && $employee->company_id != $user->company_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! Employee belongs to another company.'], 403);
                }
                if (!$user->hasRole(['CEO', 'Director']) && $employee->branch_id != $user->branch_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! Employee belongs to another branch.'], 403);
                }
            }
        }
        // ==========================================
        
        $blockedDevices = $login->blocked_devices ?? [];
        $otherDevices = $login->other_devices ?? [];

        // Remove from blocked list
        $blockedDevices = array_filter($blockedDevices, function($token) use ($request) {
            return $token !== $request->device_token;
        });

        // Put back in other_devices logs for tracking
        $otherDevices[] = [
            'device_token' => $request->device_token,
            'latitude' => 'Unblocked',
            'longitude' => 'Unblocked',
            'time' => now()->format('Y-m-d h:i A')
        ];

        $login->update([
            'blocked_devices' => array_values($blockedDevices),
            'other_devices' => $otherDevices
        ]);

        return response()->json(['status' => 'success', 'message' => 'Device unblocked and moved to requests log.']);
    }
    
   
   // API: Fetch Employees Contextually
  public function getEmployeesList(Request $request)
    {
        $query = DB::table('adm_regist')->select('full_name', 'member_id', 'branch_id');
        
        // 🛡️ 3. DROPDOWN FILTER LOGIC
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            if ($user->hasRole(['CEO', 'Director'])) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->where('branch_id', $user->branch_id);
            }
        } else {
            // Master Admins ke liye
            if ($request->has('branch_id') && $request->branch_id != '') {
                $query->where('branch_id', $request->branch_id);
            }
        }

        $employees = $query->get();
        return response()->json(['status' => 'success', 'data' => $employees]);
    }


    // ==========================================
    // GENERATE NEW EMPLOYEE ACCESS
    // ==========================================
   public function generateEmployeeAccess(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string',
            'panel_assign' => 'required|string'
        ]);

        // ==========================================
        // 🛡️ 4. CREATION OWNERSHIP CHECK
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!in_array($user->email, $developerEmails)) {
            $employee = \Illuminate\Support\Facades\DB::table('adm_regist')->where('member_id', $request->user_id)->first();
            
            if (!$employee) {
                return response()->json(['status' => 'error', 'message' => 'Employee not found.'], 404);
            }
            if ($user->hasRole(['CEO', 'Director']) && $employee->company_id != $user->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized! Cannot generate access for another company.'], 403);
            }
            if (!$user->hasRole(['CEO', 'Director']) && $employee->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized! Cannot generate access for another branch.'], 403);
            }
        }
        // ==========================================

        // 1. Check if employee already has an access panel
        $existing = EmployeeLogin::where('user_id', $request->user_id)->first();
        
        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'This employee already has an active panel assigned!'
            ], 400); // 400 status se AJAX error block trigger hoga
        }

        // 2. Generate Random Panel ID & Password
        $panelId = 'EMP-' . rand(10000000, 99999999); 
        $panelPassword = \Illuminate\Support\Str::random(10); 

        // 3. Save to Database
        $access = EmployeeLogin::create([
            'user_id' => $request->user_id,
            'panel_assign' => $request->panel_assign,
            'panel_id' => $panelId,
            'panel_password' => $panelPassword,
            'p_time_from' => '09:00:00', // Default working hours
            'p_time_to' => '18:00:00',
            'p_status' => 'allow'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Credentials generated successfully!',
            'data' => [
                'user_id' => $access->user_id,
                'panel_id' => $access->panel_id,
                'panel_password' => $access->panel_password,
                'time_slot' => substr($access->p_time_from, 0, 5) . ' to ' . substr($access->p_time_to, 0, 5)
            ]
        ]);
    }
}