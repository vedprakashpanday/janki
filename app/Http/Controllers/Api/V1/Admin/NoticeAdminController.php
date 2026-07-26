<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notice;
use App\Models\NoticeReply;
use Carbon\Carbon;
use App\Notifications\SystemAlertNotification;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Notification;

class NoticeAdminController extends Controller
{
    // 1. Fetch All Notices for Datatable
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();

        $query = Notice::with(['targetCompany', 'targetBranch', 'targetDepartment']);

        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate   = $request->end_date ?? Carbon::now()->endOfMonth()->toDateString();
        $query->whereBetween('notice_date', [$startDate, $endDate]);

        $isGodAdmin = ($context && $context->is_god) ||
            (in_array(strtolower($user->email ?? ''), ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in']));

        if (!$isGodAdmin && $context) {

            if ($context->is_director) {
                $query->where(function ($q) use ($context, $user) {
                    $q->where('created_by', $context->profile_id)
                        ->orWhere('created_by', $user->id)
                        ->orWhere('target_company_id', $context->company_id)
                        ->orWhere('company_id', $context->company_id);
                });
            } else if ($context->is_employee) {
                $query->where(function ($q) use ($context, $user) {
                    $q->where('created_by', $context->profile_id)
                        ->orWhere('created_by', $user->id)
                        ->orWhere(function ($targetQ) use ($context) {
                            $targetQ->where('target_company_id', $context->company_id);

                            if (!empty($context->branch_id)) {
                                $targetQ->where(function ($bq) use ($context) {
                                    $bq->whereNull('target_branch_id')
                                        ->orWhere('target_branch_id', $context->branch_id);
                                });
                            }

                            if (!empty($context->department_id)) {
                                $targetQ->where(function ($dq) use ($context) {
                                    $dq->whereNull('target_department_id')
                                        ->orWhere('target_department_id', $context->department_id);
                                });
                            }

                            $targetQ->where(function ($aq) use ($context) {
                                $aq->whereNotIn('target_audience', ['other', 'director'])
                                    ->orWhere(function ($sq) use ($context) {
                                        $sq->where('target_audience', 'other')
                                            ->where('entity_type', 'employee')
                                            ->where('entity_id', $context->profile_id);
                                    });
                            });
                        });
                });
            }
        }

        if ($request->company_id && $request->company_id !== 'all') $query->where('target_company_id', $request->company_id);
        if ($request->branch_id && $request->branch_id !== 'all' && $request->branch_id !== 'HO') $query->where('target_branch_id', $request->branch_id);
        if ($request->department_id && $request->department_id !== 'all') $query->where('target_department_id', $request->department_id);

        $notices = $query->orderBy('id', 'desc')->get();
        return response()->json(['success' => true, 'data' => $notices]);
    }

    // 2. Save New Notice
    public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();

        $perms = static::getLiveActivePermissions($user);
        $status = 'pending';

        if ($context->is_god || $context->is_director || in_array('notices_add_direct', $perms)) {
            $status = 'active';
        } elseif (!in_array('notices_add_request', $perms)) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to add notices.'], 403);
        }

        $request->validate([
            'title'           => 'required|string|max:255',
            'content'         => 'required',
            'notice_date'     => 'required|date',
            'target_audience' => 'required|in:all,all_except_customers,all_except_management,director,employee,member,customer,other',
        ]);

        // 🔥 FIX: SANITIZE STRING VALUES TO NULL FOR INTEGER COLUMNS 🔥
        $target_company_id = in_array($request->target_company_id, ['all', '', null]) ? null : $request->target_company_id;
        $target_branch_id = in_array($request->target_branch_id, ['HO', 'all', '', null]) ? null : $request->target_branch_id;
        $target_department_id = in_array($request->target_department_id, ['all', '', null]) ? null : $request->target_department_id;

        if (!$context->is_god) {
            $target_company_id = $context->company_id;
            if ($context->is_employee && !empty($context->branch_id)) {
                $target_branch_id = $context->branch_id;
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
            'target_department_id' => $target_department_id,
        ]);

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
            'target_audience' => 'required|in:all,all_except_customers,all_except_management,director,employee,member,customer,other',
        ]);

        // 🔥 FIX: SANITIZE STRING VALUES TO NULL FOR INTEGER COLUMNS 🔥
        $target_company_id = in_array($request->target_company_id, ['all', '', null]) ? null : $request->target_company_id;
        $target_branch_id = in_array($request->target_branch_id, ['HO', 'all', '', null]) ? null : $request->target_branch_id;
        $target_department_id = in_array($request->target_department_id, ['all', '', null]) ? null : $request->target_department_id;

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
            'target_department_id' => $target_department_id,
            'status'               => $request->status ?? $notice->status,
        ]);

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
            \App\Models\Holiday::where('notice_id', $notice->id)->delete();
        }

        return response()->json(['success' => true, 'message' => 'Notice successfully updated!']);
    }

    // 5. Approve Notice
    public function approve($id)
    {
        $notice = Notice::findOrFail($id);
        $context = $this->getGlobalContext();

        if (!$context->is_god && $context->company_id != $notice->company_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to approve this notice.'], 403);
        }

        $notice->update([
            'status' => 'active',
            'action_taken_by' => $context->profile_id
        ]);

        $this->fireNoticeEvent($notice);
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

    // FIRE NOTIFICATION DISPATCHER
    protected function fireNoticeEvent($notice)
    {
        $title = 'Notice: ' . $notice->title;
        $message = 'A new official notice requires your attention.';
        $icon = 'fa-bullhorn';
        $color = 'text-warning';

        if (in_array($notice->target_audience, ['all', 'all_except_customers', 'all_except_management', 'employee'])) {
            $query = \App\Models\Employee::where('emp_status', 'active');
            if ($notice->target_company_id) $query->where('company_id', $notice->target_company_id);
            if ($notice->target_branch_id) $query->where('branch_id', $notice->target_branch_id);
            if ($notice->target_department_id) $query->where('department_id', $notice->target_department_id);

            $employees = $query->get();
            if ($employees->isNotEmpty()) {
                Notification::send($employees, new SystemAlertNotification($title, $message, '/employee/my-notices', $icon, $color));
            }
        }

        if (in_array($notice->target_audience, ['all', 'all_except_customers', 'all_except_management', 'member'])) {
            $query = \App\Models\Member::where('status', 'active');
            if ($notice->target_company_id) $query->where('company_id', $notice->target_company_id);
            if ($notice->target_branch_id) $query->where('branch_id', $notice->target_branch_id);

            $members = $query->get();
            if ($members->isNotEmpty()) {
                Notification::send($members, new SystemAlertNotification($title, $message, '/customer/my-notices', $icon, $color));
            }
        }

        if (in_array($notice->target_audience, ['all', 'customer'])) {
            $query = \App\Models\Customer::where('status', 'active');
            if ($notice->target_company_id) $query->where('company_id', $notice->target_company_id);
            if ($notice->target_branch_id) $query->where('branch_id', $notice->target_branch_id);

            $customers = $query->get();
            if ($customers->isNotEmpty()) {
                Notification::send($customers, new SystemAlertNotification($title, $message, '/customer/my-notices', $icon, $color));
            }
        }

        if ($notice->target_audience === 'other' && $notice->entity_id) {
            $modelClass = '\\App\\Models\\' . ucfirst(strtolower($notice->entity_type));
            if ($notice->entity_type === 'ceo') {
                $modelClass = '\\App\\Models\\SuperAdmin';
            }

            if (class_exists($modelClass)) {
                $entity = $modelClass::find($notice->entity_id);
                if ($entity) {
                    $url = '/employee/my-notices';
                    if (in_array($notice->entity_type, ['customer', 'member'])) $url = '/customer/my-notices';
                    if (in_array($notice->entity_type, ['ceo', 'director'])) $url = '/admin/notices';

                    $entity->notify(new SystemAlertNotification($title, $message, $url, $icon, $color));
                }
            }
        }

        if ($notice->target_audience !== 'all_except_management') {
            $managementTargets = NotificationHelper::getTargets($notice->target_company_id, $notice->target_branch_id, 'notices_view');
            if ($managementTargets && $managementTargets->isNotEmpty()) {
                Notification::send($managementTargets, new SystemAlertNotification(
                    'Notice System Alert',
                    'Notice "' . $notice->title . '" has been published.',
                    '/admin/notices',
                    'fa-info-circle',
                    'text-info'
                ));
            }
        }
    }
}
