<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notice;
use App\Models\NoticeReply;
use Carbon\Carbon;

class NoticeAdminController extends Controller
{
    // 1. Fetch All Notices for Datatable (With Filters & Matrix Locks)
  // 1. Fetch All Notices for Datatable
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Notice::with(['targetCompany', 'targetBranch', 'targetDepartment']);

        // A. Date Range Filter
        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate   = $request->end_date ?? Carbon::now()->endOfMonth()->toDateString();
        $query->whereBetween('notice_date', [$startDate, $endDate]);

        // B. Matrix Auto-Lock & Visibility Logic
        if ($context && !$context->is_god) {
            
            if ($context->is_director) {
                // RULE: Director ko apni company ke targeted notices aur apne employees ke bheje notices dikhenge
                $query->where(function ($q) use ($context) {
                    $q->where('target_company_id', $context->company_id)
                      ->orWhere('company_id', $context->company_id);
                });
            } 
            else if ($context->is_employee) {
                // RULE: Assigned Employee ko uske bheje notices aur uski branch/dept ke notices dikhenge
                $query->where(function ($q) use ($context) {
                    // 1. Jo notice employee ne khud create kiya hai
                    $q->where('created_by', $context->profile_id)
                      // 2. Ya jo uski branch/dept/audience ke liye aaya hai
                      ->orWhere(function ($targetQ) use ($context) {
                          $targetQ->where('target_company_id', $context->company_id);
                          
                          // HIDE DIRECTOR-ONLY NOTICES FROM EMPLOYEES IN ADMIN PANEL
                          // Agar sirf Company selected hai (Branch/Dept null) aur Audience 'All' hai, toh yeh Director only hai.
                          $targetQ->where(function($hideQ) {
                              $hideQ->where('target_audience', '!=', 'all')
                                    ->orWhereNotNull('target_branch_id')
                                    ->orWhereNotNull('target_department_id');
                          });

                          if (!empty($context->branch_id)) {
                              $targetQ->where(function ($bq) use ($context) {
                                  $bq->whereNull('target_branch_id')
                                     ->orWhere('target_branch_id', $context->branch_id);
                              });
                          }
                      });
                });
            }
        }

        // Frontend Filters
        if ($request->company_id) $query->where('target_company_id', $request->company_id);
        if ($request->branch_id) $query->where('target_branch_id', $request->branch_id);
        if ($request->department_id) $query->where('target_department_id', $request->department_id);

        $notices = $query->orderBy('id', 'desc')->get();
        return response()->json(['success' => true, 'data' => $notices]);
    }

    
    // 2. Save New Notice (With RBAC Direct/Request Check & Matrix Lock)

    public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();
        
        // Fetch Live Permissions to decide status
        $perms = static::getLiveActivePermissions($user);
        
        $status = 'pending';

        // 🔥 FIX: God Admin (Admin/CEO) aur Directors ko direct permission mil jayegi 🔥
        if ($context->is_god || $context->is_director || in_array('notices_add_direct', $perms)) {
            $status = 'active'; // Direct publish
        } elseif (!in_array('notices_add_request', $perms)) {
            // Agar employee hai aur uske paas dono me se koi permission nahi hai
            return response()->json(['success' => false, 'message' => 'You do not have permission to add notices.'], 403);
        }

        $request->validate([
            'title'           => 'required|string|max:255',
            'content'         => 'required',
            'notice_date'     => 'required|date',
            'target_audience' => 'required|in:all,employee,member,customer,other',
        ]);

        // Auto-Lock Logic for Saving
        $target_company_id = $request->target_company_id;
        $target_branch_id = $request->target_branch_id;

        if (!$context->is_god) {
            $target_company_id = $context->company_id; // Auto-lock company
            
            if ($context->is_employee && !empty($context->branch_id)) {
                $target_branch_id = $context->branch_id; // Auto-lock branch if employee belongs to one
            }
        }

       $notice = Notice::create([
            'title'                => $request->title,
            'content'              => $request->content,
            'notice_date'          => $request->notice_date,
            'target_audience'      => $request->target_audience,
            'entity_type'          => $request->target_audience === 'other' ? $request->entity_type : null,
            'entity_id'            => $request->target_audience === 'other' ? $request->entity_id : null,
            'requires_reply'       => $request->requires_reply === 'true' || $request->requires_reply == 1 ? 1 : 0,
            'company_id'           => $context->company_id ?? null,
            'created_by'           => $context->profile_id ?? null,
            'status'               => $status,
            'target_company_id'    => $target_company_id,
            'target_branch_id'     => $target_branch_id,
            'target_department_id' => $request->target_department_id ?? null,
        ]);

        // 🔥 HOLIDAY SAVE LOGIC 🔥
        if ($request->is_holiday == 1) {
            \App\Models\Holiday::create([
                'notice_id'  => $notice->id,
                'total_days' => $request->holiday_total_days,
                'start_date' => $request->holiday_start_date,
                'end_date'   => $request->holiday_total_days > 1 ? $request->holiday_end_date : null,
            ]);
        }

        if ($status === 'active') {
        $this->fireNoticeEvent($notice);
    }

        return response()->json([
            'success' => true,
            'message' => $status === 'active' ? 'Notice successfully published!' : 'Notice request submitted for approval.'
        ]);
    }

    // 3. Fetch Single Notice for Edit
    public function show($id)
{
    // Holiday relation ko bhi load karenge
    $notice = Notice::with('holiday')->findOrFail($id);
    return response()->json([
        'success' => true,
        'data' => $notice
    ]);
}

    // 4. Update Notice
    public function update(Request $request, $id)
    {
        $notice = Notice::findOrFail($id);
        $context = $this->getGlobalContext();

        $request->validate([
            'title'           => 'required|string|max:255',
            'content'         => 'required',
            'notice_date'     => 'required|date',
           'target_audience' => 'required|in:all,employee,member,customer,other',
        ]);

        $target_company_id = $request->target_company_id;
        $target_branch_id = $request->target_branch_id;

        if (!$context->is_god) {
            $target_company_id = $context->company_id;
            if ($context->is_employee && !empty($context->branch_id)) {
                $target_branch_id = $context->branch_id;
            }
        }

        $notice->update([
            'title'                => $request->title,
            'content'              => $request->content,
            'notice_date'          => $request->notice_date,
            'target_audience'      => $request->target_audience,
            'entity_type'          => $request->target_audience === 'other' ? $request->entity_type : null,
            'entity_id'            => $request->target_audience === 'other' ? $request->entity_id : null,
            'requires_reply'       => $request->requires_reply === 'true' || $request->requires_reply == 1 ? 1 : 0,
            'target_company_id'    => $target_company_id,
            'target_branch_id'     => $target_branch_id,
            'target_department_id' => $request->target_department_id ?? null,
            // Status can be updated here if sent, otherwise remains same
            'status'               => $request->status ?? $notice->status,
        ]);

       // 🔥 HOLIDAY UPDATE LOGIC 🔥
        if ($request->is_holiday == 1) {
            \App\Models\Holiday::updateOrCreate(
                ['notice_id' => $notice->id],
                [
                    'total_days' => $request->holiday_total_days,
                    'start_date' => $request->holiday_start_date,
                    'end_date'   => $request->holiday_total_days > 1 ? $request->holiday_end_date : null,
                ]
            );
        } else {
            // Agar pehle holiday tha par ab untick kar diya to delete kardo
            \App\Models\Holiday::where('notice_id', $notice->id)->delete();
        }

        return response()->json(['success' => true, 'message' => 'Notice successfully updated!']);
    }

    // 5. Approve Notice
    public function approve($id)
    {
        $notice = Notice::findOrFail($id);
        $context = $this->getGlobalContext();

        // Check if user is God Admin or Director of the SAME company
        if (!$context->is_god && $context->company_id != $notice->company_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to approve this notice.'], 403);
        }

        $notice->update([
            'status' => 'active',
            'action_taken_by' => $context->profile_id
        ]);

    $this->fireNoticeEvent($notice); // Yahan call karein
        return response()->json(['success' => true, 'message' => 'Notice approved and published!']);
    }

    // 6. Reject Notice
    public function reject($id)
    {
        $notice = Notice::findOrFail($id);
        $context = $this->getGlobalContext();

        if (!$context->is_god && $context->company_id != $notice->company_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to reject this notice.'], 403);
        }

        $notice->update([
            'status' => 'inactive',
            'action_taken_by' => $context->profile_id
        ]);

        return response()->json(['success' => true, 'message' => 'Notice request rejected.']);
    }

    // 7. Delete Notice
    public function destroy($id)
    {
        $notice = Notice::findOrFail($id);
        $notice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notice deleted successfully!'
        ]);
    }

    // 8. Get Replies for a Notice
    public function getReplies($id)
    {
        $replies = NoticeReply::where('notice_id', $id)->orderBy('created_at', 'asc')->get();
        return response()->json([
            'success' => true,
            'data' => $replies
        ]);
    }

    // 🔥 REAL-TIME NOTIFICATION DISPATCHER 🔥
    protected function fireNoticeEvent($notice)
    {
        // 1. Employees ko Notification
        if (in_array($notice->target_audience, ['all', 'employee'])) {
            $query = \App\Models\Employee::where('emp_status', 'active');
            if ($notice->target_company_id) $query->where('company_id', $notice->target_company_id);
            if ($notice->target_branch_id) $query->where('branch_id', $notice->target_branch_id);
            if ($notice->target_department_id) $query->where('department_id', $notice->target_department_id);
            
            foreach($query->pluck('id') as $id) {
                event(new \App\Events\GlobalUserNotification("global.user.employee.{$id}", $notice->id, $notice->title, ['actor_name' => 'Admin/HR', 'type' => 'notice']));
            }
        }

        // 2. Members ko Notification
        if (in_array($notice->target_audience, ['all', 'member'])) {
            $query = \App\Models\Member::where('status', 'active');
            if ($notice->target_company_id) $query->where('company_id', $notice->target_company_id);
            if ($notice->target_branch_id) $query->where('branch_id', $notice->target_branch_id);
            
            foreach($query->pluck('id') as $id) {
                event(new \App\Events\GlobalUserNotification("global.user.member.{$id}", $notice->id, $notice->title, ['actor_name' => 'Admin/HR', 'type' => 'notice']));
            }
        }
        
        // 3. Agar kisi Specific Insaan ko bheja hai
        if ($notice->target_audience === 'other' && $notice->entity_id) {
            event(new \App\Events\GlobalUserNotification("global.user.{$notice->entity_type}.{$notice->entity_id}", $notice->id, $notice->title, ['actor_name' => 'Admin/HR', 'type' => 'notice']));
        }
    }

}
