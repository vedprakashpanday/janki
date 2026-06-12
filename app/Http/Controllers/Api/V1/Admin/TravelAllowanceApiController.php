<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TravelAllowance;
use App\Models\Employee; // Added for notifications
use Illuminate\Support\Facades\Auth;
use App\Events\GlobalUserNotification; // Realtime Event

class TravelAllowanceApiController extends Controller
{
    /**
     * 1. Fetch Records (Server-side Pagination, Search & Company Isolation)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = TravelAllowance::with(['company', 'branch', 'employee', 'approver'])
            ->orderBy('id', 'desc');

        // 🔥 SERVER-SIDE SEARCH LOGIC
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('purpose', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('destination', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('vehicle_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('employee', function ($q2) use ($searchTerm) {
                        $q2->where('full_name', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('member_id', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }

        // 🔥 ROLE & COMPANY ISOLATION LOGIC
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $emailStr = strtolower($user->email ?? '');
        $isGodMode = in_array($emailStr, $developerEmails);

        $isExecutive = false;
        if (!empty($user->designation_name)) {
            $desig = strtolower($user->designation_name);
            $isExecutive = (str_contains($desig, 'ceo') || str_contains($desig, 'director'));
        }

        $canApproveReject = $user->hasPermissionTo('ta_appr') || $user->hasPermissionTo('ta_rej');

        if (!$isGodMode) {
            if ($isExecutive || $canApproveReject) {
                // Manager/Executive: Sirf APNI COMPANY ke logo ka TA dekh sakte hain
                $query->where('company_id', $user->company_id);
            } else {
                // Normal Employee: Sirf apna TA dekh sakta hai
                $query->where('employee_id', $user->id);
            }
        }

        // 🔥 SERVER-SIDE PAGINATION (10 per page)
        $perPage = $request->input('per_page', 10);
        return response()->json($query->paginate($perPage));
    }

    /**
     * Helper Function for Real-time Notifications
     */
    private function sendTANotification($userIds, $taId, $title, $actorName)
    {
        $userIds = array_unique(array_filter($userIds)); // Remove duplicates & nulls
        foreach ($userIds as $uid) {
            $logData = [
                'type' => 'ta_request',
                'actor_name' => $actorName,
                'message' => $title
            ];
            // Send to both possible portals to ensure delivery
            event(new GlobalUserNotification('global.user.admin.' . $uid, $taId, $title, $logData));
            event(new GlobalUserNotification('global.user.employee.' . $uid, $taId, $title, $logData));
        }
    }

    /**
     * 2. Store TA Request & Notify Approvers
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'ta_date' => 'required|date',
            'employee_id' => 'required|exists:adm_regist,id',
            'company_id' => 'required|exists:companies,id',
        ]);

        $branchId = ($request->branch_id === 'HO') ? null : $request->branch_id;

        $ta = TravelAllowance::create([
            'company_id' => $request->company_id,
            'branch_id' => $branchId,
            'department_id' => $request->department_id,
            'designation_id' => $request->designation_id,
            'employee_id' => $request->employee_id,
            'ta_date' => $request->ta_date,
            'vehicle_no' => $request->vehicle_no,
            'purpose' => $request->purpose,
            'destination' => $request->destination,
            'distance_km' => $request->distance_km,
            'in_time' => $request->in_time,
            'out_time' => $request->out_time,
            'fuel_litre' => $request->fuel_litre,
            'amount' => $request->amount,
            'status' => 'pending'
        ]);

       // 🔥 NOTIFY DIRECTORS & APPROVERS OF THE SAME COMPANY
        $approvers = Employee::permission(['ta_appr', 'ta_rej'])
                            ->where('company_id', $request->company_id)
                            ->where('emp_status', 'active')
                            ->pluck('id')->toArray();
                            
        $directors = Employee::where('company_id', $request->company_id)
                            ->where('role', 'director')
                            ->pluck('id')->toArray();

        // 🔥 NAYA FIX: DEVELOPER / GOD MODE KO BHI NOTIFY KARO 🔥
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $godModeIds = Employee::whereIn('email', $developerEmails)->pluck('id')->toArray();

        // Sabko merge karo aur duplicate hatao, khud ka id hatao
        $notifyIds = array_diff(array_unique(array_merge($approvers, $directors, $godModeIds)), [$user->id]); 
        
        $this->sendTANotification($notifyIds, $ta->id, "New TA Request from " . ($user->full_name ?? 'Employee'), $user->full_name ?? 'System');

        return response()->json(['message' => 'TA request submitted successfully.', 'data' => $ta], 201);
    }

    /**
     * 3. Update Existing TA Request
     */
    public function update(Request $request, $id)
    {
        $ta = TravelAllowance::findOrFail($id);

        if ($ta->status !== 'pending') {
            return response()->json(['message' => 'Cannot edit a processed request.'], 403);
        }

        $branchId = ($request->branch_id === 'HO') ? null : $request->branch_id;
        $data = $request->all();
        $data['branch_id'] = $branchId; // Apply HO fix

        $ta->update($data);

        return response()->json(['message' => 'TA request updated successfully.']);
    }

    /**
     * 4. Bulk Delete
     */
    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:travel_allowances,id']);
        TravelAllowance::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Selected records deleted successfully.']);
    }

    public function destroy($id)
    {
        TravelAllowance::findOrFail($id)->delete();
        return response()->json(['message' => 'Record deleted successfully.']);
    }

    // ==========================================
    // 5. WORKFLOW ACTIONS (Approve / Reject / Remarks)
    // ==========================================

    public function approve($id)
    {
        $user = Auth::user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isGodMode = in_array(strtolower($user->email ?? ''), $developerEmails);
        $isExecutive = !empty($user->designation_name) && (str_contains(strtolower($user->designation_name), 'ceo') || str_contains(strtolower($user->designation_name), 'director'));

        if (!$isGodMode && !$isExecutive && !$user->hasPermissionTo('ta_appr')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $ta = TravelAllowance::findOrFail($id);
        $ta->update([
            'status' => 'active',
            'approver_id' => $isGodMode ? null : $user->id
        ]);

        // 🔥 NOTIFY EMPLOYEE
        $this->sendTANotification([$ta->employee_id], $ta->id, "Your TA Request was Approved", $isGodMode ? 'Super Admin' : $user->full_name);

        return response()->json(['message' => 'TA request approved successfully.']);
    }

    public function reject($id)
    {
        $user = Auth::user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isGodMode = in_array(strtolower($user->email ?? ''), $developerEmails);
        $isExecutive = !empty($user->designation_name) && (str_contains(strtolower($user->designation_name), 'ceo') || str_contains(strtolower($user->designation_name), 'director'));

        if (!$isGodMode && !$isExecutive && !$user->hasPermissionTo('ta_rej')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $ta = TravelAllowance::findOrFail($id);
        $ta->update([
            'status' => 'rejected',
            'approver_id' => $isGodMode ? null : $user->id
        ]);

        // 🔥 NOTIFY EMPLOYEE
        $this->sendTANotification([$ta->employee_id], $ta->id, "Your TA Request was Rejected", $isGodMode ? 'Super Admin' : $user->full_name);

        return response()->json(['message' => 'TA request rejected.']);
    }

    public function updateRemarks(Request $request, $id)
    {
        $user = Auth::user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isGodMode = in_array(strtolower($user->email ?? ''), $developerEmails);
        $isExecutive = !empty($user->designation_name) && (str_contains(strtolower($user->designation_name), 'ceo') || str_contains(strtolower($user->designation_name), 'director'));

        if (!$isGodMode && !$isExecutive && !$user->hasPermissionTo('ta_remark')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $ta = TravelAllowance::findOrFail($id);
        $ta->update(['remarks' => $request->remarks]);

        return response()->json(['message' => 'Remarks saved successfully.', 'remarks' => $ta->remarks]);
    }

    // ==========================================
    // 6. PRINT PREVIEW BLADE
    // ==========================================
    public function printPreview($id)
    {
        $ta = TravelAllowance::with(['company', 'branch', 'employee', 'approver'])->findOrFail($id);
        return view('admin.travel_allowances.print', ['ta' => $ta, 'company' => $ta->company, 'branch' => $ta->branch]);
    }


    /**
     * 7. Fetch Single TA for View Modal (Returns HTML)
     */
    public function show($id)
    {
        $ta = TravelAllowance::with(['company', 'branch', 'employee', 'approver'])->findOrFail($id);
        
        $html = view('admin.travel_allowances.view_partial', [
            'ta' => $ta,
            'company' => $ta->company,
            'branch' => $ta->branch
        ])->render();

        return response()->json(['html' => $html]);
    }



}
