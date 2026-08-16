<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TaskProgressLog;
use App\Services\MediaConverterService;
use App\Notifications\SystemAlertNotification;
use Illuminate\Support\Facades\Notification;

class TaskController extends Controller
{

    protected $mediaConverter;

    public function __construct(MediaConverterService $mediaConverter)
    {
        $this->mediaConverter = $mediaConverter;
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();
        
        $query = \App\Models\Task::with(['assignee', 'phase', 'progressLogs.actor', 'attachments']);
        
        // 1. Separation: Staff ya Associate (Default Staff)
        $assigneeType = $request->get('type', 'App\Models\Employee'); 
        $query->where('assignee_type', $assigneeType);
        
        // 2. Sirf Aaj Ke Tasks (Current Date)
        $today = now()->toDateString();
        $query->whereDate('created_at', $today);
        
        // 3. Sirf "Active" Staff ya Members ke Tasks
        $query->whereHasMorph('assignee', [$assigneeType], function($q) use ($assigneeType) {
             if ($assigneeType === 'App\Models\Employee') {
                 $q->where('emp_status', 'active');
             } else {
                 $q->where('status', 'active');
             }
        });

        // 4. 🔥 THE BYPASS & SECURITY LOGIC 🔥
        if ($context->is_god || $context->role_level === 'ceo') {
            // God / Developer / CEO: Sab kuch dikhega (Full Bypass)
        } 
        elseif ($context->is_director) {
            // Director: Sirf apni company ka dikhega
            $query->where('company_id', $context->company_id);
        } 
        else {
            // Normal Employee / Assigned Member
            if (in_array('task_view_all', $context->permissions ?? [])) {
                 $query->where('branch_id', $context->branch_id);
            } else {
                 $query->where(function($q) use ($user) {
                     $q->where('assignee_id', $user->id)
                       ->orWhere('assigner_id', $user->id); 
                 });
            }
        }
        
        // Sort karke return karein
        $tasks = $query->latest()->get();
        
        return response()->json([
            'status' => 'success', 
            'data' => $tasks
        ]);
    }

    private function autoSyncDynamicTasks($user)
    {
        $tasks = Task::where('assignee_type', get_class($user))
            ->where('assignee_id', $user->id)
            ->whereNotNull('tracking_module_id')
            ->whereIn('status', ['Pending', 'In-Progress'])
            ->with('trackingModule')
            ->get();

        foreach ($tasks as $task) {
            $module = $task->trackingModule;
            if (!$module || !$module->is_dynamic) continue;

            $matchValue = ($module->join_column === 'member_id') ? ($user->member_id ?? null) : $user->id;
            if (!$matchValue) continue;

            $taskCreatedAt = $task->created_at;

            try {
                $count = DB::table($module->target_table)
                    ->where($module->user_id_column, $matchValue)
                    ->where($module->date_column, '>=', $taskCreatedAt)
                    ->when($task->due_datetime, function ($query) use ($task, $module) {
                        return $query->where($module->date_column, '<=', $task->due_datetime);
                    })
                    ->count();

                if ($count != $task->achieved_count) {
                    $difference = $count - $task->achieved_count;
                    $diffText = $difference > 0 ? "+{$difference}" : "{$difference}";

                    \App\Models\TaskProgressLog::create([
                        'task_id' => $task->id,
                        'actor_type' => $task->assignee_type,
                        'actor_id' => $task->assignee_id,
                        'log_type' => 'progress_update',
                        'message_or_remark' => "System Note: Detected {$diffText} new entries. (Total Achieved: {$count})",
                        'entries_completed' => $difference
                    ]);

                    $task->achieved_count = $count;

                    if ($task->target_count > 0 && $count >= $task->target_count && $task->status !== 'Completed') {
                        $task->status = 'Completed';

                        \App\Models\TaskProgressLog::create([
                            'task_id' => $task->id,
                            'actor_type' => $task->assignee_type,
                            'actor_id' => $task->assignee_id,
                            'log_type' => 'progress_update',
                            'message_or_remark' => "System Note: Target of {$task->target_count} achieved! Task Auto-Completed.",
                            'entries_completed' => 0
                        ]);
                    } elseif ($count > 0 && $task->status === 'Pending') {
                        $task->status = 'In-Progress';
                    }

                    $task->save();
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Dynamic Task Sync Error for Task ID {$task->id}: " . $e->getMessage());
            }
        }
    }

    public function store(Request $request, \App\Services\TelecallerAllocationService $allocationService)
    {
        $request->validate([
            'assignee_type' => 'required|string',
            'assignee_ids' => 'required|array|min:1',
            'tasks' => 'required|array|min:1',
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.tracking_module_id' => 'nullable|exists:task_tracking_modules,id',
            'tasks.*.phase_id' => 'nullable|exists:phases,id',
            'tasks.*.provider_id' => 'nullable|string',
            'tasks.*.provider_percent' => 'nullable|integer|min:1|max:100',
            'tasks.*.target_count' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'priority' => 'required|in:Low,Medium,High,Urgent',
            'due_datetime' => 'nullable|date',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        $context = $this->getGlobalContext();
        if (!$context) return response()->json(['status' => 'error', 'message' => 'User context not found.'], 401);

        $user = auth()->user();
        $assignerName = $user->name ?? $user->full_name ?? $user->member_name ?? 'System';

        DB::beginTransaction();
        try {
            $uploadedFiles = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $mediaRecord = $this->mediaConverter->uploadAndConvert($file);
                    if ($mediaRecord) {
                        $uploadedFiles[] = [
                            'file_name' => $mediaRecord->original_name,
                            'file_path' => $mediaRecord->file_path,
                            'file_type' => $mediaRecord->extension,
                        ];
                    } else {
                        $originalName = $file->getClientOriginalName();
                        $ext = $file->getClientOriginalExtension();
                        $filename = time() . '_' . uniqid() . '.' . $ext;
                        $file->move(public_path('uploads/task_attachments'), $filename);

                        $uploadedFiles[] = [
                            'file_name' => $originalName,
                            'file_path' => 'uploads/task_attachments/' . $filename,
                            'file_type' => $ext,
                        ];
                    }
                }
            }

            $totalTasksCreated = 0;
            $usersToNotify = [];

            foreach ($request->assignee_ids as $assigneeId) {
                $assigneeClass = $request->assignee_type;
                $assigneeRecord = $assigneeClass::find($assigneeId);

                if (!$assigneeRecord) continue;

                $empCompanyId = $assigneeRecord->company_id ?? $context->company_id;
                $empBranchId = $assigneeRecord->branch_id ?? $context->branch_id;
                $empDeptId = $assigneeRecord->department_id ?? $context->department_id;

                $tasksCountForUser = 0;

                foreach ($request->tasks as $taskItem) {
                    $task = Task::create([
                        'company_id' => $empCompanyId,
                        'branch_id' => $empBranchId,
                        'department_id' => $empDeptId,
                        'assigner_type' => get_class($user),
                        'assigner_id' => $user->id,
                        'assignee_type' => $request->assignee_type,
                        'assignee_id' => $assigneeId,
                        'title' => $taskItem['title'],
                        'tracking_module_id' => $taskItem['tracking_module_id'] ?? null,
                        'phase_id' => $taskItem['phase_id'] ?? null,
                        'provider_id' => $taskItem['provider_id'] ?? null, 
                        'provider_percent' => $taskItem['provider_percent'] ?? null, 
                        'target_count' => $taskItem['target_count'] ?? 0,
                        'description' => $request->description,
                        'priority' => $request->priority,
                        'due_datetime' => $request->due_datetime,
                        'status' => 'Pending',
                    ]);

                    // ==========================================
                    // 🔥 NAYA ALLOCATION LOGIC WITH FALLBACK 🔥
                    // ==========================================
                    if ($task->phase_id && $task->target_count > 0) {
                        $remainingTarget = $task->target_count;
                        
                        // Step 1: Check Override Member Leads
                        if (isset($taskItem['is_member_override']) && $taskItem['is_member_override'] == '1') {
                            $overrideCount = $allocationService->allocateOverrideMemberLeads(
                                $task, 
                                $taskItem['override_member_id'], 
                                $taskItem['override_status'], 
                                $remainingTarget
                            );
                            
                            $remainingTarget -= $overrideCount; // Target me se override leads minus kar do
                        }

                        // Step 2: Fallback (Bacha hua target normal Employee Quota (Point 1) se uthega)
                        if ($remainingTarget > 0) {
                            $allocationService->allocateFreshCustomers($task, $remainingTarget);
                        }
                    }
                    // ==========================================

                    $totalTasksCreated++;
                    $tasksCountForUser++;

                    foreach ($uploadedFiles as $fileData) {
                        TaskAttachment::create(array_merge($fileData, [
                            'task_id' => $task->id,
                            'task_progress_log_id' => null,
                            'uploader_type' => get_class($user),
                            'uploader_id' => $user->id,
                        ]));
                    }
                }

                if ($tasksCountForUser > 0) {
                    $usersToNotify[] = [
                        'record' => $assigneeRecord,
                        'count' => $tasksCountForUser
                    ];
                }
            }

            DB::commit();

            foreach ($usersToNotify as $target) {
                $assigneeRecord = $target['record'];
                $count = $target['count'];
        
                $portal = str_contains($request->assignee_type, 'Employee') ? 'employee' : 'member';

                if ($task->target_count > 0) {
                    $notifTitle = "🎯 New Target Task Assigned";
                    $notifIcon = "fa-bullseye";
                    $notifColor = "text-primary";
                } else {
                    $notifTitle = "📌 New Manual Task Assigned";
                    $notifIcon = "fa-thumbtack";
                    $notifColor = "text-warning";
                }

                $notifMsg = "Task: " . $task->title . " \nPriority: " . $task->priority;
                $taskRoute = str_contains($task->assignee_type, 'Employee') ? 'tasks/staff' : 'tasks/associates';
                $targetUrl = "/{$portal}/{$taskRoute}";

                $assigneeRecord->notify(new \App\Notifications\SystemAlertNotification(
                    $notifTitle,
                    $notifMsg,
                    $targetUrl, 
                    $notifIcon,
                    $notifColor
                ));
            }

            return response()->json([
                'status' => 'success',
                'message' => $totalTasksCreated . ' Tasks assigned successfully across ' . count($request->assignee_ids) . ' employees!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $context = $this->getGlobalContext();
            $user = auth()->user();

            $task = Task::with([
                'assigner',
                'assignee',
                'trackingModule',
                'phase', 
                'attachments' => function ($q) {
                    $q->orderBy('created_at', 'desc');
                },
                'progressLogs' => function ($q) {
                    $q->with('actor')->orderBy('created_at', 'desc');
                }
            ])->findOrFail($id);

            if (!$context->is_god) {
                $isDirectlyInvolved =
                    ($task->assignee_type === get_class($user) && $task->assignee_id === $user->id) ||
                    ($task->assigner_type === get_class($user) && $task->assigner_id === $user->id);

                if (!$isDirectlyInvolved && $task->company_id != $context->company_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Access! Yeh task aapke scope se bahar hai.'], 403);
                }
            }
            $task->syncLiveProgress();

            return response()->json(['status' => 'success', 'data' => $task]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'System Error: ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ' on line ' . $e->getLine()
            ], 500);
        }
    }

    public function addReply(Request $request, $id)
    {
        $request->validate([
            'message_or_remark' => 'required|string',
            'status' => 'nullable|in:Pending,In-Progress,Under Review,Completed',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        $user = auth()->user();
        $senderName = $user->name ?? $user->full_name ?? $user->member_name ?? 'System';

        DB::beginTransaction();
        try {
            $task = Task::findOrFail($id);

            $log = TaskProgressLog::create([
                'task_id' => $task->id,
                'actor_type' => get_class($user),
                'actor_id' => $user->id,
                'log_type' => 'reply',
                'message_or_remark' => $request->message_or_remark,
                'entries_completed' => 0
            ]);

            $uploadedFilesForEvent = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $mediaRecord = $this->mediaConverter->uploadAndConvert($file);
                    if ($mediaRecord) {
                        $attachment = TaskAttachment::create([
                            'task_id' => $task->id,
                            'task_progress_log_id' => $log->id,
                            'uploader_type' => get_class($user),
                            'uploader_id' => $user->id,
                            'file_name' => $mediaRecord->original_name,
                            'file_path' => $mediaRecord->file_path,
                            'file_type' => $mediaRecord->extension,
                        ]);
                    } else {
                        $originalName = $file->getClientOriginalName();
                        $ext = $file->getClientOriginalExtension();
                        $filename = time() . '_' . uniqid() . '.' . $ext;
                        $file->move(public_path('uploads/task_attachments'), $filename);

                        $attachment = TaskAttachment::create([
                            'task_id' => $task->id,
                            'task_progress_log_id' => $log->id,
                            'uploader_type' => get_class($user),
                            'uploader_id' => $user->id,
                            'file_name' => $originalName,
                            'file_path' => 'uploads/task_attachments/' . $filename,
                            'file_type' => $ext,
                        ]);
                    }

                    $uploadedFilesForEvent[] = [
                        'file_name' => $attachment->file_name,
                        'file_path' => $attachment->file_path
                    ];
                }
            }

            if ($request->filled('status') && $request->status !== $task->status) {
                $task->update(['status' => $request->status]);
                TaskProgressLog::create([
                    'task_id' => $task->id,
                    'actor_type' => get_class($user),
                    'actor_id' => $user->id,
                    'log_type' => 'progress_update',
                    'message_or_remark' => "Changed task status to: " . $request->status,
                ]);
            }

            DB::commit();

            $logData = [
                'id' => $log->id,
                'actor_name' => $senderName,
                'date' => $log->created_at->format('d M Y, h:i A'),
                'message' => $log->message_or_remark,
                'log_type' => $log->log_type,
                'attachments' => $uploadedFilesForEvent
            ];

            broadcast(new \App\Events\TaskProgressUpdated($task->id, $logData))->toOthers();

            $isAssignee = ($user->id === $task->assignee_id && get_class($user) === $task->assignee_type);
            $receiverType = $isAssignee ? $task->assigner_type : $task->assignee_type;
            $receiverId = $isAssignee ? $task->assigner_id : $task->assignee_id;

            $receiverRecord = $receiverType::find($receiverId);

            if ($receiverRecord) {
                $portal = 'admin';
                if (str_contains($receiverType, 'Employee') || str_contains($receiverType, 'adm_regist')) {
                    $portal = 'employee';
                } elseif (str_contains($receiverType, 'Member')) {
                    $portal = 'customer';
                }

                $truncatedTitle = \Illuminate\Support\Str::limit($task->title, 25);
                $truncatedMsg = \Illuminate\Support\Str::limit($request->message_or_remark, 40);

                $taskRoute = str_contains($task->assignee_type, 'Employee') ? 'tasks/staff' : 'tasks/associates';
                $targetUrl = "/{$portal}/{$taskRoute}";

                $receiverRecord->notify(new SystemAlertNotification(
                    "Task Update: {$truncatedTitle}",
                    "New msg from {$senderName}: {$truncatedMsg}",
                    $targetUrl,
                    "fa-comments",
                    "text-info"
                ));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Update posted successfully!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $context = $this->getGlobalContext();
            $task = Task::findOrFail($id);

            if (!$context->is_god) {
                if ($task->company_id != $context->company_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Access to delete this Task!'], 403);
                }
            }

            \App\Models\TaskAttachment::where('task_id', $task->id)->delete();
            \App\Models\TaskProgressLog::where('task_id', $task->id)->delete();

         // 1. Is task se judi hui sirf 'Fresh Leads' (is_rollover = 0) ke customer_ids nikalna
        $freshCustomerIds = \App\Models\TelecallerAllocation::where('task_id', $id)
            ->where('is_rollover', 0)
            ->pluck('customer_id')
            ->toArray();

        // 2. Agar is task me fresh leads thi, toh unhe wapas Master Pool (Godown) me bhej do
        if (!empty($freshCustomerIds)) {
            \App\Models\InterestedCustomer::whereIn('id', $freshCustomerIds)->update([
                'is_assigned'         => 0,    // Wapas se 0 kar diya taaki doosra assign ho sake
                'assigned_telecaller' => null, // Telecaller ka naam hata diya
                'updated_at'          => now()
            ]);
        }

        // 3. Purani allocations delete karna (Optional - agar aap clean rakhna chahte hain)
        \App\Models\TelecallerAllocation::where('task_id', $id)->delete();

        // 4. Finally Task ko delete karna
        $task = \App\Models\Task::findOrFail($id);
        $task->delete();

        return response()->json([
            'status' => 'success', 
            'message' => 'Task deleted aur saari fresh leads wapas pool me bhej di gayi hain!'
        ]);
    } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:Low,Medium,High,Urgent',
            'tracking_module_id' => 'nullable|exists:task_tracking_modules,id',
            'phase_id' => 'nullable|exists:phases,id',
            'provider_id' => 'nullable|string',
            'provider_percent' => 'nullable|integer|min:1|max:100',
            'target_count' => 'nullable|integer|min:0',
            'due_datetime' => 'nullable|date',
        ]);

        try {
            $context = $this->getGlobalContext();
            $task = Task::findOrFail($id);

            if (!$context->is_god) {
                if ($task->company_id != $context->company_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Access! You cannot edit this task.'], 403);
                }
            }

            $task->update([
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority,
                'tracking_module_id' => $request->tracking_module_id,
                'phase_id' => $request->phase_id,
                'provider_id' => $request->provider_id ?? null,
                'provider_percent' => $request->provider_percent ?? null,
                'target_count' => $request->target_count ?? 0,
                'due_datetime' => $request->due_datetime,
            ]);

            \App\Models\TaskProgressLog::create([
                'task_id' => $task->id,
                'actor_type' => get_class(auth()->user()),
                'actor_id' => auth()->user()->id,
                'log_type' => 'progress_update',
                'message_or_remark' => 'System Note: Task details (Priority/Target/Phase/Date) were modified by Admin.',
                'entries_completed' => 0
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Task has been updated successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function progressReport(Request $request)
    {
        try {
            $context = $this->getGlobalContext();

           $query = Task::with([
                'assignee',
                'progressLogs' => function ($q) {
                    $q->with(['actor', 'attachments'])->orderBy('created_at', 'asc');
                }
            ]);

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            }

            if ($request->filled('assignee_ids')) {
                $assigneeIds = explode(',', $request->assignee_ids);
                $query->whereIn('assignee_id', $assigneeIds);
            } elseif ($request->filled('employee_id')) {
                $query->where('assignee_id', $request->employee_id)->where('assignee_type', 'App\Models\Employee');
            } elseif ($request->filled('member_id')) {
                $query->where('assignee_id', $request->member_id)->where('assignee_type', 'App\Models\Member');
            }

            if ($request->filled('company_ids')) {
                $query->whereIn('company_id', explode(',', $request->company_ids));
            }
            if ($request->filled('branch_ids')) {
                $branchIds = [];
                foreach(explode(',', $request->branch_ids) as $b) {
                    if(!str_starts_with($b, 'HO_')) $branchIds[] = $b;
                }
                if(count($branchIds) > 0) $query->whereIn('branch_id', $branchIds);
            }
            if ($request->filled('department_ids')) {
                $query->whereIn('department_id', explode(',', $request->department_ids));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if (!$context->is_god) {
                if ($context->is_director) {
                    $query->where('company_id', $context->company_id);
                } else {
                    $query->where('assignee_id', auth()->user()->id)
                        ->where('assignee_type', get_class(auth()->user()));
                }
            }

            $tasks = $query->get();

            $report = [];

            foreach ($tasks as $task) {
                $empId = $task->assignee_type . '_' . $task->assignee_id;

                if (!isset($report[$empId])) {
                    $assigneeName = $task->assignee ? ($task->assignee->full_name ?? $task->assignee->member_name ?? $task->assignee->name) : 'Unknown';
                    $report[$empId] = [
                        'employee_name' => $assigneeName,
                        'total_tasks' => 0,
                        'completed_tasks' => 0,
                        'pending_tasks' => 0,
                        'tasks' => []
                    ];
                }

                $report[$empId]['total_tasks']++;
                if ($task->status === 'Completed') {
                    $report[$empId]['completed_tasks']++;
                } else {
                    $report[$empId]['pending_tasks']++;
                }

               $logs = [];
                foreach ($task->progressLogs as $log) {
                    $logs[] = [
                        'date' => $log->created_at->format('d M, h:i A'),
                        'actor' => $log->actor ? ($log->actor->full_name ?? $log->actor->member_name ?? $log->actor->name) : 'System',
                        'message' => $log->message_or_remark,
                        'type' => $log->log_type,
                        'attachments' => $log->attachments ? $log->attachments->map(function ($file) {
                            return [
                                'file_name' => $file->file_name,
                                'file_path' => $file->file_path
                            ];
                        })->toArray() : []
                    ];
                }

                $report[$empId]['tasks'][] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'is_target_based' => $task->target_count > 0,
                    'target' => $task->target_count,
                    'achieved' => $task->achieved_count,
                    'progress_percent' => $task->target_count > 0 ? min(round(($task->achieved_count / $task->target_count) * 100), 100) : 0,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'assigned_date' => $task->created_at ? $task->created_at->format('d M Y') : 'Unknown',
                    'due_date' => $task->due_datetime ? \Carbon\Carbon::parse($task->due_datetime)->format('d M Y') : 'No Due Date',
                    'logs' => $logs
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => array_values($report)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Line: ' . $e->getLine() . ' | ' . $e->getMessage()
            ], 500);
        }
    }

    public function editReply(\Illuminate\Http\Request $request, $log_id)
    {
        $request->validate(['message_or_remark' => 'required|string']);
        $log = \App\Models\TaskProgressLog::findOrFail($log_id);
        
        $user = auth()->user();
        $isOwner = ($log->actor_type === get_class($user) && $log->actor_id === $user->id);
        $context = $this->getGlobalContext();
        
        $isSuperUser = $context->is_god || in_array(strtolower($context->role_level ?? ''), ['ceo', 'director', 'admin']);

        if (!$isOwner && !$isSuperUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to edit this message'], 403);
        }

        if ($isOwner && !$isSuperUser) {
            $minutesPassed = $log->created_at->diffInMinutes(now());
            if ($minutesPassed > 5) {
                return response()->json(['success' => false, 'message' => 'Time limit exceeded! You can only edit messages within 5 minutes.'], 403);
            }
        }

        if ($log->is_deleted) {
            return response()->json(['success' => false, 'message' => 'Cannot edit a deleted message'], 400);
        }

        $log->message_or_remark = $request->message_or_remark;
        $log->is_edited = 1;
        $log->save();

        broadcast(new \App\Events\TaskProgressUpdated($log->task_id, $log))->toOthers();

        return response()->json(['success' => true, 'message' => 'Message updated successfully']);
    }

    public function deleteReply(\Illuminate\Http\Request $request, $log_id)
    {
        $request->validate(['delete_type' => 'required|in:for_me,for_everyone']);
        $log = \App\Models\TaskProgressLog::findOrFail($log_id);
        
        $user = auth()->user();
        $isOwner = ($log->actor_type === get_class($user) && $log->actor_id === $user->id);
        $context = $this->getGlobalContext();
        $isSuperUser = $context->is_god || in_array(strtolower($context->role_level ?? ''), ['ceo', 'director', 'admin']);

        if ($request->delete_type === 'for_everyone') {
            if (!$isOwner && !$isSuperUser) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $log->is_deleted = 1;
            $log->save();

            broadcast(new \App\Events\TaskProgressUpdated($log->task_id, $log))->toOthers();

        } else {
            $deletedFor = $log->deleted_for ? (is_array($log->deleted_for) ? $log->deleted_for : json_decode($log->deleted_for, true)) : [];
            $userIdentifier = get_class($user) . '_' . $user->id;
            
            if (!in_array($userIdentifier, $deletedFor)) {
                $deletedFor[] = $userIdentifier;
                $log->deleted_for = json_encode($deletedFor);
                $log->save();
            }
        }

        return response()->json(['success' => true, 'message' => 'Message deleted successfully']);
    }
}