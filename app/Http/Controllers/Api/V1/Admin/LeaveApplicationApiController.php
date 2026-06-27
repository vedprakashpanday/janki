<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveApplication;
use App\Models\Employee;
use App\Models\Member;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Notifications\SystemAlertNotification;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Notification;

class LeaveApplicationApiController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = LeaveApplication::with(['company', 'branch', 'department', 'designation', 'employee', 'member', 'approver', 'rejecter']);

        if (!$context->is_god && $context->role_level !== 'ceo') {
            if ($context->is_director) {
                $query->where('company_id', $context->company_id);
            } else {
                $userPerms = method_exists(auth()->user(), 'getAllPermissions') ? auth()->user()->getAllPermissions()->pluck('name')->toArray() : [];

                if (in_array('leave_appr', $userPerms) || in_array('leave_rej', $userPerms)) {
                    $query->where('company_id', $context->company_id);
                    if (!empty($context->branch_id)) {
                        $query->where('branch_id', $context->branch_id);
                    } else {
                        $query->whereNull('branch_id');
                    }
                } else {
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
            'user_type' => 'required|in:employee,member',
            'resume_datetime' => 'required_unless:application_type,Other|nullable|date',
            'emergency_contact' => 'required|string|max:50',
            'applied_to' => 'required|string',
            'proof_attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $context = $this->getGlobalContext();
        $data = $request->all();
        $data['status'] = 'pending';
        $data['is_paid_leave'] = $request->has('is_paid_leave') ? 1 : 0;

        if (!$context->is_god && $context->role_level !== 'ceo' && !$context->is_director) {
            $data['company_id'] = $context->company_id;
            $data['branch_id'] = $context->branch_id ?? null;
            $data['department_id'] = $context->department_id;
            $data['designation_id'] = auth()->user()->designation_id ?? null;
            $data['user_type'] = isset($context->is_member) && $context->is_member ? 'member' : 'employee';
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

        // NAYA FILE UPLOAD LOGIC
        if ($request->hasFile('proof_attachments')) {
            $attachments = [];
            foreach ($request->file('proof_attachments') as $file) {
                $filename = time() . '_' . rand(1000, 9999) . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/leave_proofs'), $filename);
                $attachments[] = 'uploads/leave_proofs/' . $filename;
            }
            $data['proof_attachments'] = $attachments;
        }

        $leave = LeaveApplication::create($data);

        // 🔥 1. NEW LEAVE NOTIFICATION LOGIC 🔥
        $applicantName = auth()->user()->full_name ?? auth()->user()->name ?? auth()->user()->member_name ?? 'An Employee';

        // Target list uthao jinke pass Leave Approve ka power hai
        $targets = NotificationHelper::getTargets($leave->company_id, $leave->branch_id, 'leave_appr');

        // Khud ko list se nikal do
        $targets = $targets->reject(function ($target) {
            return $target->id === auth()->id();
        });

        if ($targets->count() > 0) {
            Notification::send($targets, new SystemAlertNotification(
                'New Leave Request',
                "{$applicantName} has applied for a {$leave->application_type}.",
                '/admin/leave-applications',
                'fa-calendar-plus',
                'text-warning'
            ));
        }

        return response()->json(['success' => true, 'message' => 'Application submitted successfully']);
    }

    public function show($id)
    {
        $context = $this->getGlobalContext();
        $application = LeaveApplication::with(['company', 'branch', 'department', 'designation', 'employee', 'member', 'approver', 'rejecter'])->findOrFail($id);

        if (!$context->is_god && $context->role_level !== 'ceo') {
            if ($context->is_director) {
                if ($application->company_id != $context->company_id) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized scope.'], 403);
                }
            } else {
                $userPerms = method_exists(auth()->user(), 'getAllPermissions') ? auth()->user()->getAllPermissions()->pluck('name')->toArray() : [];

                if (in_array('leave_appr', $userPerms) || in_array('leave_rej', $userPerms) || in_array('leave_edit', $userPerms)) {
                    if ($application->company_id != $context->company_id) {
                        return response()->json(['success' => false, 'message' => 'Unauthorized scope.'], 403);
                    }
                    if (!empty($context->branch_id) && $application->branch_id != $context->branch_id) {
                        return response()->json(['success' => false, 'message' => 'Unauthorized scope.'], 403);
                    }
                } else {
                    if ($application->user_id != auth()->id()) {
                        return response()->json(['success' => false, 'message' => 'You can only view your own applications.'], 403);
                    }
                }
            }
        }

        return response()->json(['success' => true, 'data' => $application]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'application_type' => 'required|in:Leave,Short Leave,Other',
            'reason' => 'required|string|min:300',
            'start_datetime' => 'required_unless:application_type,Other|date',
            'end_datetime' => 'required_unless:application_type,Other|date|after_or_equal:start_datetime',
            'user_type' => 'required|in:employee,member',
            'resume_datetime' => 'required_unless:application_type,Other|nullable|date',
            'emergency_contact' => 'required|string|max:50',
            'applied_to' => 'required|string',
            'proof_attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $context = $this->getGlobalContext();
        $leave = LeaveApplication::findOrFail($id);

        $userPerms = method_exists(auth()->user(), 'getAllPermissions') ? auth()->user()->getAllPermissions()->pluck('name')->toArray() : [];
        $isOwner = ($leave->user_id == auth()->id());
        $canEdit = $context->is_god || $context->role_level === 'ceo' || in_array('leave_edit', $userPerms) || $isOwner;

        if (!$canEdit) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to edit this application.'], 403);
        }

        if ($leave->status !== 'pending' && !$context->is_god) {
            return response()->json(['success' => false, 'message' => 'Cannot edit an application that has already been processed.'], 400);
        }

        $data = $request->all();
        $data['is_paid_leave'] = $request->has('is_paid_leave') ? 1 : 0;

        // NAYA FILE UPLOAD LOGIC
        if ($request->hasFile('proof_attachments')) {
            $attachments = [];
            foreach ($request->file('proof_attachments') as $file) {
                $filename = time() . '_' . rand(1000, 9999) . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/leave_proofs'), $filename);
                $attachments[] = 'uploads/leave_proofs/' . $filename;
            }
            $data['proof_attachments'] = $attachments;
        }

        if (!$context->is_god && $context->role_level !== 'ceo' && !$context->is_director && !in_array('leave_edit', $userPerms)) {
            $data['company_id'] = $context->company_id;
            $data['branch_id'] = $context->branch_id ?? null;
            $data['department_id'] = $context->department_id;
            $data['designation_id'] = auth()->user()->designation_id ?? null;
            $data['user_type'] = isset($context->is_member) && $context->is_member ? 'member' : 'employee';
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

        $leave->update($data);

        return response()->json(['success' => true, 'message' => 'Application updated successfully', 'data' => $leave]);
    }

    public function destroy($id)
    {
        $context = $this->getGlobalContext();
        $leave = LeaveApplication::findOrFail($id);

        $userPerms = method_exists(auth()->user(), 'getAllPermissions') ? auth()->user()->getAllPermissions()->pluck('name')->toArray() : [];
        $hasDeletePerm = in_array('leave_delete', $userPerms) || $context->is_god;

        $userType = isset($context->is_member) && $context->is_member ? 'member' : 'employee';
        $isExactOwner = ($leave->user_id == auth()->id() && $leave->user_type === $userType);

        if ($isExactOwner && !$hasDeletePerm) {
            if ($leave->status !== 'pending') {
                return response()->json(['success' => false, 'message' => 'You cannot delete this application because it has already been processed.'], 403);
            }
        } elseif (!$hasDeletePerm) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to delete!'], 403);
        }

        $leave->delete();
        return response()->json(['success' => true, 'message' => 'Application deleted successfully']);
    }

    public function approve(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'approved_duration' => 'required|numeric|min:0.5',
            'approved_start_datetime' => 'required|date',
            'approved_end_datetime' => 'required|date|after_or_equal:approved_start_datetime',
            'approved_resume_datetime' => 'required|date',
            'remarks' => 'nullable|string'
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $leave = LeaveApplication::findOrFail($id);

        if ($leave->user_id == auth()->id() && $leave->user_type == 'employee') {
            $context = $this->getGlobalContext();
            if (!$context->is_god) {
                return response()->json(['success' => false, 'message' => 'You cannot approve your own application.'], 403);
            }
        }

        $leave->update([
            'status' => 'approved',
            'approved_duration' => $request->approved_duration,
            'approved_start_datetime' => $request->approved_start_datetime,
            'approved_end_datetime' => $request->approved_end_datetime,
            'approved_resume_datetime' => $request->approved_resume_datetime,
            'remarks' => $request->remarks,
            'approved_by' => auth()->id()
        ]);

        // 🔥 2. LEAVE APPROVE NOTIFICATION (Dynamic Model Find) 🔥
        $modelClass = $leave->user_type === 'member' ? \App\Models\Member::class : \App\Models\Employee::class;
        $applicant = $modelClass::find($leave->user_id);

        if ($applicant) {
            $portalRoute = $leave->user_type === 'member' ? 'customer' : 'employee';

            $applicant->notify(new SystemAlertNotification(
                'Leave Approved! 🎉',
                "Your {$leave->application_type} application has been approved.",
                "/{$portalRoute}/leave-applications",
                'fa-check-circle',
                'text-success'
            ));
        }

        return response()->json(['success' => true, 'message' => 'Application approved successfully']);
    }

    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), ['remarks' => 'required|string|min:10']);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $leave = LeaveApplication::findOrFail($id);

        if ($leave->user_id == auth()->id() && $leave->user_type == 'employee') {
            $context = $this->getGlobalContext();
            if (!$context->is_god) {
                return response()->json(['success' => false, 'message' => 'You cannot reject your own application.'], 403);
            }
        }

        $leave->update([
            'status' => 'rejected',
            'remarks' => $request->remarks,
            'rejected_by' => auth()->id()
        ]);

        // 🔥 3. LEAVE REJECT NOTIFICATION (Dynamic Model Find) 🔥
        $modelClass = $leave->user_type === 'member' ? \App\Models\Member::class : \App\Models\Employee::class;
        $applicant = $modelClass::find($leave->user_id);

        if ($applicant) {
            $portalRoute = $leave->user_type === 'member' ? 'customer' : 'employee';

            $applicant->notify(new SystemAlertNotification(
                'Leave Rejected',
                "Your {$leave->application_type} application has been rejected.",
                "/{$portalRoute}/leave-applications",
                'fa-times-circle',
                'text-danger'
            ));
        }

        return response()->json(['success' => true, 'message' => 'Application rejected successfully']);
    }

    public function getUsersByDesignation(Request $request)
    {
        $context = $this->getGlobalContext();
        $type = $request->user_type;
        $designationId = $request->designation_id;
        $companyId = $context->is_god ? $request->company_id : $context->company_id;
        $branchId = $request->branch_id;

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
                    else $q->whereNull('branch_id');
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

    public function printPreview($id)
    {
        $application = LeaveApplication::with(['company', 'branch', 'department', 'designation', 'employee', 'member', 'approver', 'rejecter'])->findOrFail($id);

        $context = $this->getGlobalContext();
        if ($context) {
            if (!$context->is_god && isset($context->is_director) && $context->is_director) {
                if ($application->company_id != $context->company_id) {
                    abort(403, 'Unauthorized Access');
                }
            }
        }

        return view('admin.leave_applications.print', compact('application'));
    }

    public function viewHtml($id)
    {
        $app = LeaveApplication::with(['company', 'branch', 'department', 'designation', 'employee', 'member', 'approver'])->findOrFail($id);

        return view('admin.leave_applications.view_partial', [
            'app' => $app,
            'company' => $app->company,
            'branch' => $app->branch
        ]);
    }

    public function getApplyToOptions(Request $request)
    {
        $companyId = $request->company_id;
        $branchId = $request->branch_id;
        $appType = $request->application_type;

        $options[] = [
            'id' => 'Management (admin@jankivilla.com)',
            'name' => 'Management (admin@jankivilla.com) - Master Admin'
        ];
        
        if ($appType === 'Other') {
        $ceos = \Illuminate\Support\Facades\DB::table('super_admins')->where('status', 'active')->get();
        foreach ($ceos as $ceo) {
            $options[] = ['id' => $ceo->full_name . ' (' . $ceo->ceo_id . ')', 'name' => $ceo->full_name . ' (' . $ceo->ceo_id . ') - CEO'];
        }

        if ($companyId) {
            $directors = \Illuminate\Support\Facades\DB::table('directors')
                ->join('company_director', 'directors.id', '=', 'company_director.director_id')
                ->where('company_director.company_id', $companyId)
                ->where('directors.status', 'active')
                ->select('directors.*')
                ->get();

            foreach ($directors as $dir) {
                $options[] = [
                    'id' => $dir->full_name . ' (' . $dir->director_id . ')',
                    'name' => $dir->full_name . ' (' . $dir->director_id . ') - Director'
                ];
            }
        }
    }

        if ($companyId && $branchId) {
            $employees = Employee::permission(['leave_appr', 'leave_rej'])
                ->where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->where('emp_status', 'active')
                ->get();
            foreach ($employees as $emp) {
                $options[] = ['id' => $emp->full_name . ' (' . $emp->member_id . ')', 'name' => $emp->full_name . ' (' . $emp->member_id . ') - Auth Signatory'];
            }
        }

        return response()->json(['success' => true, 'data' => $options]);
    }
}
