<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccessControlController extends Controller
{
    // ==========================================
    // GET ALL PANEL ACCESS (SMART CONTEXT)
    // ==========================================
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();

        $query = EmployeeLogin::query()
            ->join('adm_regist', 'employee_logins.user_id', '=', 'adm_regist.member_id')
            ->select('employee_logins.*', 'adm_regist.full_name', 'adm_regist.branch_id', 'adm_regist.company_id');

        // 🛡️ ZERO-TRUST SCOPING BASED ON CONTEXT
        if (!$context->is_god) {
            if ($context->is_director) {
                $query->where('adm_regist.company_id', $context->company_id);
            } else {
                $query->where('adm_regist.branch_id', $context->branch_id);
            }
        } else {
            // Master Admins ke liye Frontend Branch Filter & Head Office Logic
            if ($request->has('branch_id') && $request->branch_id != '') {
                if (str_starts_with($request->branch_id, 'HO_')) {
                    $compId = str_replace('HO_', '', $request->branch_id);
                    $query->where('adm_regist.company_id', $compId)
                          ->where(function ($q) {
                              $q->whereNull('adm_regist.branch_id')
                                ->orWhere('adm_regist.branch_id', '')
                                ->orWhere('adm_regist.branch_id', '0')
                                ->orWhereRaw('LOWER(adm_regist.branch_id) = ?', ['ho']);
                          });
                } else {
                    $query->where('adm_regist.branch_id', $request->branch_id);
                }
            }
        }
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('employee_logins.user_id', 'LIKE', "%{$search}%")
                    ->orWhere('employee_logins.panel_id', 'LIKE', "%{$search}%")
                    ->orWhere('adm_regist.full_name', 'LIKE', "%{$search}%");
            });
        }

        $totalData = EmployeeLogin::count();
        $totalFiltered = $query->count();

        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        if ($length != -1) $query->offset($start)->limit($length);

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $query->orderBy('employee_logins.id', 'desc')->get()
        ]);
    }

    // ==========================================
    // 🛡️ REUSABLE SECURITY CHECKER FOR DEVICES
    // ==========================================
    private function checkEmployeeScope($context, $memberId)
    {
        if ($context->is_god) return true;

        $employee = DB::table('adm_regist')->where('member_id', $memberId)->first();
        if (!$employee) return false;

        if ($context->is_director && $employee->company_id != $context->company_id) return false;
        if ($context->is_employee && $employee->branch_id != $context->branch_id) return false;

        return true;
    }

    // ==========================================
    // GET EMPLOYEES LIST FOR DROPDOWN
    // ==========================================

    // API: Fetch Employees Contextually
    public function getEmployeesList(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = DB::table('adm_regist')->select('full_name', 'member_id', 'branch_id');

        // 🛡️ ZERO-TRUST DROPDOWN FILTER
        if (!$context->is_god) {
            if ($context->is_director) {
                $query->where('company_id', $context->company_id);
                if ($request->has('branch_id') && $request->branch_id != '') {
                    $query->where('branch_id', $request->branch_id);
                } else {
                    $query->where(function ($q) {
                        $q->whereNull('branch_id')->orWhere('branch_id', 0)->orWhere('branch_id', '');
                    });
                }
            } else {
                $query->where('branch_id', $context->branch_id);
            }
        } else {
            // Master Admins ke liye
            if ($request->has('company_id') && $request->company_id != '') {
                $query->where('company_id', $request->company_id);
            }
            if ($request->has('branch_id') && $request->branch_id != '') {
                $query->where('branch_id', $request->branch_id);
            } else {
                $query->where(function ($q) {
                    $q->whereNull('branch_id')->orWhere('branch_id', 0)->orWhere('branch_id', '');
                });
            }
        }

        // 🔥 NAYA FIX: Department Name se ID nikal kar filter karega 🔥
        if ($request->has('department_name') && $request->department_name != '') {
            // Database se us department naam ke saare IDs nikal lega (Global + Company specific)
            $deptIds = DB::table('departments')->where('department_name', $request->department_name)->pluck('id');
            $query->whereIn('department_id', $deptIds);
        } elseif ($request->has('department_id') && $request->department_id != '') {
            // Fallback purane code ke liye
            $query->where('department_id', $request->department_id);
        }

        // MySQL NULL Trap fix (Agar emp_status null ho toh bhi fetch kare)
        $query->where(function ($q) {
            $q->whereNotIn('emp_status', ['terminated', 'resigned', 'Inactive'])
                ->orWhereNull('emp_status');
        });

        $employees = $query->get();
        return response()->json(['status' => 'success', 'data' => $employees]);
    }

    // ==========================================
    // GENERATE NEW EMPLOYEE ACCESS
    // ==========================================
   public function generateEmployeeAccess(Request $request)
    {
        $context = $this->getGlobalContext();
        $request->validate([
            'user_id' => 'required|string',
            'panel_assign' => 'required|string',
            'p_time_from' => 'required',
            'p_time_to' => 'required'
        ]);

        if (!$this->checkEmployeeScope($context, $request->user_id)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
        }

        $existing = EmployeeLogin::where('user_id', $request->user_id)->first();
        if ($existing) return response()->json(['status' => 'error', 'message' => 'Active panel already assigned!'], 400);

        $panelId = 'EMP-' . rand(10000000, 99999999);
        $panelPassword = \Illuminate\Support\Str::random(10);

        $access = EmployeeLogin::create([
            'user_id' => $request->user_id,
            'panel_assign' => $request->panel_assign,
            'panel_id' => $panelId,
            'panel_password' => $panelPassword,
            'p_time_from' => $request->p_time_from,
            'p_time_to' => $request->p_time_to,
            'p_status' => 'allow'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Credentials generated successfully!',
            'data' => [
                'panel_id' => $access->panel_id,
                'panel_password' => $access->panel_password
            ]
        ]);
    }

    // ==========================================
    // DEVICE MANAGEMENT & EMERGENCY OVERRIDES
    // ==========================================

    public function rejectDeviceRequest(Request $request)
    {
        $context = $this->getGlobalContext();
        $request->validate(['panel_id' => 'required|string', 'device_token' => 'required|string']);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->firstOrFail();
        if (!$this->checkEmployeeScope($context, $login->user_id)) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        $otherDevices = $login->other_devices ?? [];
        foreach ($otherDevices as &$device) {
            if ($device['device_token'] === $request->device_token) $device['status'] = 'rejected';
        }
        $login->update(['other_devices' => $otherDevices]);

        return response()->json(['status' => 'success', 'message' => 'Device marked as rejected.']);
    }

    public function revokeDeviceAccess(Request $request)
    {
        $context = $this->getGlobalContext();
        $request->validate(['panel_id' => 'required|string', 'device_token' => 'required|string']);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->firstOrFail();
        if (!$this->checkEmployeeScope($context, $login->user_id)) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        $updateData = [];
        if ($login->primary_device === $request->device_token) $updateData['primary_device'] = null;
        if ($login->secondary_device === $request->device_token) {
            $updateData['s_status'] = 'deny';
            $updateData['s_time_from'] = null;
            $updateData['s_time_to'] = null;
            $updateData['secondary_device'] = null;
        }

        if (count($updateData) > 0) $login->update($updateData);
        return response()->json(['status' => 'success', 'message' => 'Access Revoked (Stopped) successfully!']);
    }

    public function blockDevice(Request $request)
    {
        $context = $this->getGlobalContext();
        $request->validate(['panel_id' => 'required|string', 'device_token' => 'required|string']);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->firstOrFail();
        if (!$this->checkEmployeeScope($context, $login->user_id)) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        $otherDevices = $login->other_devices ?? [];
        $blockedDevices = $login->blocked_devices ?? [];

        $filteredDevices = array_filter($otherDevices, function ($item) use ($request) {
            return $item['device_token'] !== $request->device_token;
        });

        if (!in_array($request->device_token, $blockedDevices)) $blockedDevices[] = $request->device_token;

        $updateData = ['other_devices' => array_values($filteredDevices), 'blocked_devices' => $blockedDevices];
        if ($login->primary_device === $request->device_token) $updateData['primary_device'] = null;
        if ($login->secondary_device === $request->device_token) $updateData['secondary_device'] = null;

        $login->update($updateData);
        return response()->json(['status' => 'success', 'message' => 'Device has been permanently blocked.']);
    }

    public function unblockDevice(Request $request)
    {
        $context = $this->getGlobalContext();
        $request->validate(['panel_id' => 'required|string', 'device_token' => 'required|string']);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->firstOrFail();
        if (!$this->checkEmployeeScope($context, $login->user_id)) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        $blockedDevices = $login->blocked_devices ?? [];
        $otherDevices = $login->other_devices ?? [];

        $blockedDevices = array_filter($blockedDevices, function ($token) use ($request) {
            return $token !== $request->device_token;
        });

        $otherDevices[] = [
            'device_token' => $request->device_token,
            'latitude' => 'Unblocked',
            'longitude' => 'Unblocked',
            'time' => now()->format('Y-m-d h:i A')
        ];

        $login->update(['blocked_devices' => array_values($blockedDevices), 'other_devices' => $otherDevices]);
        return response()->json(['status' => 'success', 'message' => 'Device unblocked and moved to requests log.']);
    }

    // Multi-Day Overrides Handling
    public function grantEmergencyAccess(Request $request)
    {
        $context = $this->getGlobalContext();
        $request->validate([
            'panel_id' => 'required|string',
            's_date_from' => 'required|date',
            's_date_to' => 'required|date',
            's_time_from' => 'required',
            's_time_to' => 'required',
        ]);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->firstOrFail();
        if (!$this->checkEmployeeScope($context, $login->user_id)) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        $updateData = [
            's_status' => 'allow',
            's_date_from' => $request->s_date_from,
            's_date_to' => $request->s_date_to,
            's_time_from' => $request->s_time_from,
            's_time_to' => $request->s_time_to
        ];

        if ($request->filled('device_token')) {
            $token = $request->device_token;
            $newToken = str_replace('_O_', '_S_', $token);
            $updateData['secondary_device'] = $newToken;

            $otherDevices = $login->other_devices ?? [];
            $filteredOther = array_filter($otherDevices, function ($d) use ($token) {
                return $d['device_token'] !== $token;
            });
            $updateData['other_devices'] = array_values($filteredOther);
        }

        $login->update($updateData);
        return response()->json(['status' => 'success', 'message' => 'Multi-Day Emergency Access Authorized!']);
    }

    // Smart Unbind Workflow API
    public function processSmartUnbind(Request $request)
    {
        $context = $this->getGlobalContext();
        $request->validate([
            'panel_id' => 'required|string',
            'action_type' => 'required|in:replace_existing,clear_fresh',
            'target_device_token' => 'nullable|string'
        ]);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->firstOrFail();
        if (!$this->checkEmployeeScope($context, $login->user_id)) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        $newPassword = \Illuminate\Support\Str::random(10);
        $updateData = ['panel_password' => $newPassword];

        if ($request->action_type === 'replace_existing') {
            if (!$request->filled('target_device_token')) {
                return response()->json(['status' => 'error', 'message' => 'Please select a replacement device.'], 400);
            }

            $oldToken = $request->target_device_token;
            $newToken = str_replace('_O_', '_P_', $oldToken);

            // Clean other_devices logs
            $otherDevices = $login->other_devices ?? [];
            $filteredOther = array_filter($otherDevices, function ($d) use ($oldToken) {
                return $d['device_token'] !== $oldToken;
            });

            $updateData['primary_device'] = $newToken;
            $updateData['other_devices'] = array_values($filteredOther);
        } else {
            // Fresh clear
            $updateData['primary_device'] = null;
        }

        // Har haal me secondary clear karo session hygiene ke liye
        $updateData['secondary_device'] = null;
        $updateData['s_status'] = 'deny';

        $login->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Hardware configuration re-routed successfully!',
            'new_password' => $newPassword
        ]);
    }











    public function getSessionLogs(Request $request)
    {
        $request->validate(['user_id' => 'required', 'date' => 'required|date']);

        $attendance = \App\Models\Attendance::where('user_id', $request->user_id)->where('date', $request->date)->first();
        $logs = [];
        if ($attendance && $attendance->session_logs) {
            $logs = is_string($attendance->session_logs) ? json_decode($attendance->session_logs, true) : $attendance->session_logs;
        }

        return response()->json(['status' => 'success', 'data' => $logs]);
    }


    // ==========================================
    // PHASE 2: HARD RESET & PASSWORD REGENERATE
    // ==========================================
    public function hardResetDevice(Request $request)
    {
        $context = $this->getGlobalContext();

        $request->validate([
            'panel_id' => 'required|string'
        ]);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->firstOrFail();

        // Scope Validation - Check if Admin has rights for this employee
        if (!$this->checkEmployeeScope($context, $login->user_id)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
        }

        // Generate a fresh 10-digit random password
        $newPassword = \Illuminate\Support\Str::random(10);

        // Reset all active bindings and assign new password
        $login->update([
            'primary_device' => null,
            'secondary_device' => null,
            's_status' => 'deny',         // Emergency access bhi revoke kar do
            's_time_from' => null,
            's_time_to' => null,
            'panel_password' => $newPassword
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Device bindings cleared and new password generated!',
            'new_password' => $newPassword
        ]);
    }


    // ==========================================
    // PHASE 3: SET DEVICE ROLE (PRIMARY / UNBIND)
    // ==========================================
    public function setDeviceRole(Request $request)
    {
        $context = $this->getGlobalContext();
        $request->validate([
            'panel_id' => 'required|string',
            'device_token' => 'required|string',
            'role' => 'required|in:primary,unbind'
        ]);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->firstOrFail();

        if (!$this->checkEmployeeScope($context, $login->user_id)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
        }

        $token = $request->device_token;
        $otherDevices = $login->other_devices ?? [];

        // 🟢 MAKE PRIMARY LOGIC
        if ($request->role === 'primary') {
            // Token me se _O_ hata kar _P_ lagana
            $newToken = str_replace('_O_', '_P_', $token);

            // Us device ko pending list (other_devices) se hata do
            $filteredOther = array_filter($otherDevices, function ($d) use ($token) {
                return $d['device_token'] !== $token;
            });

            $login->update([
                'primary_device' => $newToken,
                'other_devices' => array_values($filteredOther)
            ]);

            return response()->json(['status' => 'success', 'message' => 'Device successfully promoted to PRIMARY!']);
        }

        // 🔴 UNBIND (REMOVE) LOGIC WITHOUT RESETTING PASSWORD
        if ($request->role === 'unbind') {
            if ($login->primary_device === $token) {
                $login->update(['primary_device' => null]);
                return response()->json(['status' => 'success', 'message' => 'Primary Device Unbound successfully.']);
            }
            if ($login->secondary_device === $token) {
                $login->update([
                    'secondary_device' => null,
                    's_status' => 'deny',
                    's_time_from' => null,
                    's_time_to' => null
                ]);
                return response()->json(['status' => 'success', 'message' => 'Secondary Device Unbound successfully.']);
            }
        }
    }



    // ==========================================
    // UPDATE SHIFT TIMINGS
    // ==========================================
    public function updateShiftTimings(Request $request)
    {
        $context = $this->getGlobalContext();
        $request->validate([
            'panel_id' => 'required|string',
            'p_time_from' => 'required',
            'p_time_to' => 'required'
        ]);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->firstOrFail();
        if (!$this->checkEmployeeScope($context, $login->user_id)) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        $login->update([
            'p_time_from' => $request->p_time_from,
            'p_time_to' => $request->p_time_to
        ]);

        return response()->json(['status' => 'success', 'message' => 'Shift timings updated successfully.']);
    }

// ==========================================
    // BULK COMPANY SHIFT UPDATE
    // ==========================================
    public function updateCompanyShiftTimings(Request $request)
    {
        $context = $this->getGlobalContext();
        
        $request->validate([
            'company_id' => 'required',
            'p_time_from' => 'required',
            'p_time_to' => 'required'
        ]);

        // Zero-Trust Scope Validation for Directors
        if (!$context->is_god && $context->is_director && $context->company_id != $request->company_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You can only update your own company.'], 403);
        }

        // 1. Company ke saare employees ki member_id nikal lo
        $memberIds = DB::table('adm_regist')
                        ->where('company_id', $request->company_id)
                        ->pluck('member_id');

        if ($memberIds->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No employees found in this company.'], 404);
        }

        // 2. Ek single query me un sabhi ka panel access time update kar do
        EmployeeLogin::whereIn('user_id', $memberIds)->update([
            'p_time_from' => $request->p_time_from,
            'p_time_to' => $request->p_time_to
        ]);

        return response()->json([
            'status' => 'success', 
            'message' => 'Shift timings successfully updated for all employees of the selected company!'
        ]);
    }

}
