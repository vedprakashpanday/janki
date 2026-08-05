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

        // 🔥 FIX: Soft Deleted records sirf Admin, CEO, aur Director ko hi fetch honge
        if ($context->is_god || $context->is_director) {
            $query->withTrashed();
        }

        // 🔥 Filter based on requested User Type (Employee or Member)
        if ($request->has('req_user_type') && in_array($request->req_user_type, ['employee', 'member'])) {
            $query->where('user_type', $request->req_user_type);
        }

        if (!$context->is_god && $context->role_level !== 'ceo') {
            if ($context->is_director) {
                $query->where('company_id', $context->company_id);
            } else {
                $userPerms = method_exists(auth()->user(), 'getAllPermissions') ? auth()->user()->getAllPermissions()->pluck('name')->toArray() : [];

                if (in_array('leave_appr', $userPerms) || in_array('leave_rej', $userPerms) || in_array('mem_app_appr', $userPerms) || in_array('mem_app_rej', $userPerms)) {
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
            
            // 🔥 NAYA: Custom dates ke validation rules
            'is_custom_date' => 'nullable',
            'custom_dates' => 'required_if:is_custom_date,1|array',
            
            // 🔥 FIX: Agar Custom Date (1) hai YA type Other hai, toh in fields ko validation se bahar (exclude) nikal do
            'start_datetime' => 'exclude_if:is_custom_date,1|exclude_if:application_type,Other|required|date',
            'end_datetime' => 'exclude_if:is_custom_date,1|exclude_if:application_type,Other|required|date|after_or_equal:start_datetime',
            
            'user_type' => 'required|in:employee,member',
            
            // 🔥 FIX: Resume date ko bhi Other me exclude kar diya
            'resume_datetime' => 'exclude_if:application_type,Other|required|date',
            
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

       $data['is_custom_date'] = $request->has('is_custom_date') && $request->is_custom_date ? 1 : 0;

        if ($data['application_type'] !== 'Other') {
            if ($data['is_custom_date']) {
                // 🔥 NAYA: Custom Dates Logic
                $data['custom_dates'] = $request->custom_dates;
                $data['start_datetime'] = null;
                $data['end_datetime'] = null;
                $data['duration'] = count($request->custom_dates); // Har select ki hui date 1 din mani jayegi
            } else {
                // Purana Normal Range Logic
                $data['custom_dates'] = null;
                $start = Carbon::parse($data['start_datetime']);
                $end = Carbon::parse($data['end_datetime']);
                if ($data['application_type'] === 'Leave') {
                    $data['duration'] = $start->diffInDays($end) + 1;
                } else {
                    $data['duration'] = $start->diffInHours($end);
                }
            }
        } else {
            $data['duration'] = null;
            $data['is_custom_date'] = 0;
            $data['custom_dates'] = null;
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

       // 🔥 1. NEW LEAVE NOTIFICATION LOGIC (Dynamic URL & Name Fix) 🔥
        $applicantName = auth()->user()->member_name ?? auth()->user()->full_name ?? auth()->user()->name ?? 'An Applicant';

        // Target list uthao jinke pass Leave Approve ka power hai
        $targets = NotificationHelper::getTargets($leave->company_id, $leave->branch_id, 'leave_appr');

        // Khud ko list se nikal do
        $targets = $targets->reject(function ($target) {
            return $target->id === auth()->id();
        });

        if ($targets->count() > 0) {
            // 🔥 NAYA: User type ke hisaab se URL decide hoga
            $redirectUrl = $leave->user_type === 'member' ? '/admin/member-leave-applications' : '/admin/leave-applications';
            
            Notification::send($targets, new SystemAlertNotification(
                'New Leave Request',
                "{$applicantName} has applied for a {$leave->application_type}.",
                $redirectUrl,
                'fa-calendar-plus',
                'text-warning'
            ));
        }

        return response()->json(['success' => true, 'message' => 'Application submitted successfully']);
    }

    public function show($id)
    {
        $context = $this->getGlobalContext();
        $query = LeaveApplication::with(['company', 'branch', 'department', 'designation', 'employee', 'member', 'approver', 'rejecter']);
        
        // 🔥 FIX: Conditional withTrashed
        if ($context->is_god || $context->is_director) {
            $query->withTrashed();
        }
        
        $application = $query->findOrFail($id);

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
            
            // 🔥 NAYA: Custom dates ke validation rules
            'is_custom_date' => 'nullable',
            'custom_dates' => 'required_if:is_custom_date,1|array',
            
            // 🔥 FIX: Agar Custom Date (1) hai YA type Other hai, toh in fields ko validation se bahar (exclude) nikal do
            'start_datetime' => 'exclude_if:is_custom_date,1|exclude_if:application_type,Other|required|date',
            'end_datetime' => 'exclude_if:is_custom_date,1|exclude_if:application_type,Other|required|date|after_or_equal:start_datetime',
            
            'user_type' => 'required|in:employee,member',
            
            // 🔥 FIX: Resume date ko bhi Other me exclude kar diya
            'resume_datetime' => 'exclude_if:application_type,Other|required|date',
            
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

        $data['is_custom_date'] = $request->has('is_custom_date') && $request->is_custom_date ? 1 : 0;

        if ($data['application_type'] !== 'Other') {
            if ($data['is_custom_date']) {
                // 🔥 NAYA: Custom Dates Logic
                $data['custom_dates'] = $request->custom_dates;
                $data['start_datetime'] = null;
                $data['end_datetime'] = null;
                $data['duration'] = count($request->custom_dates); // Har select ki hui date 1 din mani jayegi
            } else {
                // Purana Normal Range Logic
                $data['custom_dates'] = null;
                $start = Carbon::parse($data['start_datetime']);
                $end = Carbon::parse($data['end_datetime']);
                if ($data['application_type'] === 'Leave') {
                    $data['duration'] = $start->diffInDays($end) + 1;
                } else {
                    $data['duration'] = $start->diffInHours($end);
                }
            }
        } else {
            $data['duration'] = null;
            $data['is_custom_date'] = 0;
            $data['custom_dates'] = null;
        }

        $leave->update($data);

        return response()->json(['success' => true, 'message' => 'Application updated successfully', 'data' => $leave]);
    }

 public function destroy($id)
    {
        $context = $this->getGlobalContext();
        // 🔥 FIX: withTrashed() joda gaya taaki soft deleted record bhi mil sake
        $leave = LeaveApplication::withTrashed()->findOrFail($id);

        $userPerms = method_exists(auth()->user(), 'getAllPermissions') ? auth()->user()->getAllPermissions()->pluck('name')->toArray() : [];
        $hasDeletePerm = in_array('leave_delete', $userPerms) || in_array('mem_app_delete', $userPerms) || $context->is_god;

        $userType = isset($context->is_member) && $context->is_member ? 'member' : 'employee';
        $isExactOwner = ($leave->user_id == auth()->id() && $leave->user_type === $userType);

        if ($isExactOwner && !$hasDeletePerm) {
            // Owner can only delete if it's pending and not already trashed
            if ($leave->status !== 'pending' && !$leave->trashed()) {
                return response()->json(['success' => false, 'message' => 'You cannot delete this application because it has already been processed.'], 403);
            }
        } elseif (!$hasDeletePerm) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to delete!'], 403);
        }

        // 🔥 NAYA LOGIC: Agar pehle se delete (soft delete) hai, toh ab Hard Delete (forceDelete) karo
        if ($leave->trashed()) {
            $leave->forceDelete();
            return response()->json(['success' => true, 'message' => 'Application permanently deleted from database.']);
        } else {
            $leave->delete();
            return response()->json(['success' => true, 'message' => 'Application moved to trash (Soft Deleted).']);
        }
    }

 public function approve(Request $request, $id)
    {
        // 🔥 FIX: start_datetime aur end_datetime ko 'nullable' kiya gaya, aur custom_dates array ko allowed me daala gaya
        $validator = Validator::make($request->all(), [
            'approved_duration' => 'required|numeric|min:0.5',
            'approved_start_datetime' => 'nullable|date',
            'approved_end_datetime' => 'nullable|date|after_or_equal:approved_start_datetime',
            'approved_resume_datetime' => 'required|date',
            'remarks' => 'nullable|string',
            'approved_custom_dates' => 'nullable|array'
        ]);
        
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $leave = LeaveApplication::findOrFail($id);

        if ($leave->user_id == auth()->id() && $leave->user_type == 'employee') {
            $context = $this->getGlobalContext();
            if (!$context->is_god) {
                return response()->json(['success' => false, 'message' => 'You cannot approve your own application.'], 403);
            }
        }

        // 🔥 PICHLE STEP ME JO HUMNE UPDATE KIYA THA WAHI SAME LOGIC YAHAN AAYEGA
        $updateData = [
            'status' => 'approved',
            'remarks' => $request->remarks,
            'approved_resume_datetime' => $request->approved_resume_datetime,
            'approved_by' => auth()->id()
        ];

        // Check if it's a Custom Dates application
        if ($leave->is_custom_date) {
            $updateData['approved_custom_dates'] = $request->approved_custom_dates ?? [];
            $updateData['approved_duration'] = count($request->approved_custom_dates ?? []);
            $updateData['approved_start_datetime'] = null;
            $updateData['approved_end_datetime'] = null;
        } else {
            $updateData['approved_duration'] = $request->approved_duration;
            $updateData['approved_start_datetime'] = $request->approved_start_datetime;
            $updateData['approved_end_datetime'] = $request->approved_end_datetime;
            $updateData['approved_custom_dates'] = null;
        }

        $leave->update($updateData);

        // 🔥 Iske aage aapka Notification bhejney ka purana code waisa hi rahega...
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

        // 🔥 NAYA: Agar approved chhutti reject ho rahi hai, toh uski purani approved details NULL kar do
        $leave->update([
            'status' => 'rejected',
            'remarks' => $request->remarks,
            'rejected_by' => auth()->id(),
            'approved_by' => null,
            'approved_duration' => null,
            'approved_start_datetime' => null,
            'approved_end_datetime' => null,
            'approved_resume_datetime' => null
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
                // 🔥 FIX: 'full_name' ki jagah 'member_name as full_name' kar diya gaya hai
                ->select('id', 'member_name as full_name', 'member_id')
                ->get();
        }

        return response()->json(['success' => true, 'data' => $users]);
    }

  public function printPreview($id)
    {
        $context = $this->getGlobalContext();
        $query = LeaveApplication::with(['company', 'branch', 'department', 'designation', 'employee', 'member', 'approver', 'rejecter']);
        
        // 🔥 FIX: Conditional withTrashed
        if ($context && ($context->is_god || $context->is_director)) {
            $query->withTrashed();
        }
        
        $application = $query->findOrFail($id);

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
        $context = $this->getGlobalContext();
        $query = LeaveApplication::with(['company', 'branch', 'department', 'designation', 'employee', 'member', 'approver']);
        
        // 🔥 FIX: Conditional withTrashed
        if ($context && ($context->is_god || $context->is_director)) {
            $query->withTrashed();
        }
        
        $app = $query->findOrFail($id);

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

    // 🔥 NAYA FUNCTION: Other applications me sirf remark dene ke liye
    public function addRemark(Request $request, $id)
    {
        $validator = Validator::make($request->all(), ['remarks' => 'required|string']);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $leave = LeaveApplication::findOrFail($id);

        // Status ko approved karenge taaki pending queue se bahar nikal jaye (Frontend pe 'Reviewed' dikhayenge)
        $leave->update([
            'status' => 'approved', 
            'remarks' => $request->remarks,
            'approved_by' => auth()->id()
        ]);

        // Notification Bhejna
        $modelClass = $leave->user_type === 'member' ? \App\Models\Member::class : \App\Models\Employee::class;
        $applicant = $modelClass::find($leave->user_id);

        if ($applicant) {
            $portalRoute = $leave->user_type === 'member' ? 'member' : 'employee';
            $urlPath = $leave->user_type === 'member' ? '/member-leave-applications' : '/leave-applications';
            $applicant->notify(new SystemAlertNotification(
                'Application Reviewed',
                "Management has replied to your {$leave->application_type} application.",
                "/{$portalRoute}{$urlPath}",
                'fa-comment-dots',
                'text-info'
            ));
        }

        return response()->json(['success' => true, 'message' => 'Remark added successfully.']);
    }

}
