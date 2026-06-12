<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Models\LeaveApplication;
use App\Models\Employee;
use App\Models\Member;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;


class LeaveApplicationApiController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = LeaveApplication::with(['company', 'branch', 'department', 'designation', 'employee', 'member', 'approver', 'rejecter']);

        // 🛡️ RBAC: Visibility Logic using Global Context
        if (!$context->is_god && $context->role_level !== 'ceo') {

            if ($context->is_director) {
                // Director sees only their company's applications
                $query->where('company_id', $context->company_id);
            } else {
                $userPerms = method_exists(auth()->user(), 'getAllPermissions') ? auth()->user()->getAllPermissions()->pluck('name')->toArray() : [];

                if (in_array('leave_appr', $userPerms) || in_array('leave_rej', $userPerms)) {
                    // Approver/Manager sees their company and branch/dept
                    $query->where('company_id', $context->company_id);
                    if (!empty($context->branch_id)) {
                        $query->where('branch_id', $context->branch_id);
                    } else {
                        $query->whereNull('branch_id'); // Head Office
                    }
                } else {
                    // Normal Employee/Member sees ONLY their own applications
                    $query->where('user_id', auth()->id())
                        ->where('user_type', $context->is_member ? 'member' : 'employee');
                }
            }
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($subQ) use ($search) {
                        $subQ->where('full_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('member', function ($subQ) use ($search) {
                        $subQ->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        $applications = $query->latest()->paginate(10);
        return response()->json(['success' => true, 'data' => $applications]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'application_type' => 'required|in:Leave,Short Leave,Other',
            'reason' => 'required|string|min:300',
            'start_datetime' => 'required_unless:application_type,Other|date',
            'end_datetime' => 'required_unless:application_type,Other|date|after_or_equal:start_datetime',
            'user_type' => 'required|in:employee,member'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $context = $this->getGlobalContext();
        $data = $request->all();
        $data['status'] = 'pending';

        // 🔒 Auto-Locking Data securely from Global Context
        if (!$context->is_god && $context->role_level !== 'ceo' && !$context->is_director) {
            $data['company_id'] = $context->company_id;
            $data['branch_id'] = $context->branch_id ?? null;
            $data['department_id'] = $context->department_id;
            $data['designation_id'] = auth()->user()->designation_id ?? null;
            $data['user_type'] = $context->is_member ? 'member' : 'employee';
            $data['user_id'] = auth()->id();
        }

        if ($data['application_type'] !== 'Other') {
            $start = Carbon::parse($data['start_datetime']);
            $end = Carbon::parse($data['end_datetime']);
            if ($data['application_type'] === 'Leave') {
                $data['duration'] = $start->diffInDays($end) + 1;
            } else {
                $data['duration'] = $start->diffInHours($end);
            }
        } else {
            $data['duration'] = null;
        }

        $leave = LeaveApplication::create($data);
        return response()->json(['success' => true, 'message' => 'Application submitted successfully', 'data' => $leave]);
    }

    public function approve(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'approved_duration' => 'required|numeric|min:0.5',
            'remarks' => 'nullable|string'
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $leave = LeaveApplication::findOrFail($id);
        if ($leave->user_id == auth()->id() && $leave->user_type == 'employee') {
            return response()->json(['success' => false, 'message' => 'You cannot approve your own application.'], 403);
        }

        $leave->update(['status' => 'approved', 'approved_duration' => $request->approved_duration, 'remarks' => $request->remarks, 'approved_by' => auth()->id()]);
        return response()->json(['success' => true, 'message' => 'Application approved successfully']);
    }

    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), ['remarks' => 'required|string|min:10']);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $leave = LeaveApplication::findOrFail($id);
        if ($leave->user_id == auth()->id() && $leave->user_type == 'employee') {
            return response()->json(['success' => false, 'message' => 'You cannot reject your own application.'], 403);
        }

        $leave->update(['status' => 'rejected', 'remarks' => $request->remarks, 'rejected_by' => auth()->id()]);
        return response()->json(['success' => true, 'message' => 'Application rejected successfully']);
    }

    public function getUsersByDesignation(Request $request)
    {
        $context = $this->getGlobalContext();
        $type = $request->user_type;
        $designationId = $request->designation_id;
        $companyId = $context->is_god ? $request->company_id : $context->company_id;
        $branchId = $request->branch_id; // Allows "" for Head Office

        if ($type === 'employee') {
            $users = Employee::where('emp_status', 'active')
                ->when($designationId, function ($q) use ($designationId) {
                    return $q->where('designation_id', $designationId);
                })
                ->when($companyId, function ($q) use ($companyId) {
                    return $q->where('company_id', $companyId);
                })
                ->where(function ($q) use ($branchId) {
                    if (!empty($branchId)) $q->where('branch_id', $branchId);
                    else $q->whereNull('branch_id'); // Head Office filter
                })
                ->get(['id', 'full_name']);
        } else {
            $users = Member::where('status', 'active')
                ->when($designationId, function ($q) use ($designationId) {
                    return $q->where('designation_id', $designationId);
                })
                ->when($companyId, function ($q) use ($companyId) {
                    return $q->where('company_id', $companyId);
                })
                ->where(function ($q) use ($branchId) {
                    if (!empty($branchId)) $q->where('branch_id', $branchId);
                    else $q->whereNull('branch_id');
                })
                ->get(['id', 'full_name', 'member_id']);
        }

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function getDepartmentsByCompany(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Department::where('status', 'active');
        
        $companyId = $context->is_god ? $request->company_id : $context->company_id;

        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->whereNull('company_ids')
                  ->orWhereJsonContains('company_ids', 'all')
                  ->orWhereJsonContains('company_ids', (string)$companyId)
                  ->orWhereJsonContains('company_ids', (int)$companyId);
            });
        }

        // 🔥 FIX: Head Office logic (Agar branch_id empty hai)
        $query->where(function ($q) use ($request) {
            $branchId = $request->branch_id;
            if ($branchId === '' || $branchId === null) {
                // Head Office: wo departments jahan branch_ids empty ho
                $q->where(function($sub) {
                    $sub->whereNull('branch_ids')
                        ->orWhere('branch_ids', '[]')
                        ->orWhere('branch_ids', '')
                        ->orWhereRaw("JSON_LENGTH(branch_ids) = 0");
                });
            } else {
                // Specific Branch: Branch specific OR Global departments
                $q->where(function($sub) use ($branchId) {
                    $sub->whereNull('branch_ids')
                        ->orWhere('branch_ids', '[]')
                        ->orWhereRaw("JSON_LENGTH(branch_ids) = 0")
                        ->orWhereJsonContains('branch_ids', (string)$branchId)
                        ->orWhereJsonContains('branch_ids', (int)$branchId);
                });
            }
        });

        return response()->json(['status' => 'success', 'data' => $query->get(['id', 'department_name'])]);
    }

    public function printPreview($id)
    {
        $context = $this->getGlobalContext();
        $application = LeaveApplication::with(['company', 'branch', 'department', 'designation', 'employee', 'member', 'approver', 'rejecter'])->findOrFail($id);

        if (!$context->is_god) {
            if ($context->is_director && $application->company_id != $context->company_id) abort(403, 'Unauthorized Access');
        }

        return view('admin.leave_applications.print', compact('application'));
    }
}
