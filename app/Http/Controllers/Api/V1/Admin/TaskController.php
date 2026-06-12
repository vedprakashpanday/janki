<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TaskProgressLog;

class TaskController extends Controller
{
    /**
     * 🔥 FETCH TASKS (With Auto-Sync Magic, Eager Loading & Crash Protection)
     */
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();

        if ($context->is_employee || $context->is_member) {
            $this->autoSyncDynamicTasks($user);
        }

        $query = Task::with([
            'assigner',
            'assignee',
            'trackingModule',
            'progressLogs' => function ($q) {
                $q->with('actor')->orderBy('created_at', 'desc');
            }
        ]);

        if ($context->is_god) {
            if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
            if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        } elseif ($context->is_director) {
            $query->where('company_id', $context->company_id);
            if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        } else {
            $query->where(function ($q) use ($user) {
                $q->where(function ($subQ) use ($user) {
                    $subQ->where('assignee_type', get_class($user))->where('assignee_id', $user->id);
                })->orWhere(function ($subQ) use ($user) {
                    $subQ->where('assigner_type', get_class($user))->where('assigner_id', $user->id);
                });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tasks = $query->orderBy('created_at', 'desc')->get();

        // 🔥 FIX: Catching \Throwable intercepts all PHP 8 fatal errors preventing 500 crashes
        $tasks->map(function ($task) {
            try {
                $task->syncLiveProgress();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Task Progress Sync Failed for Task {$task->id}: " . $e->getMessage());
            }
            return $task;
        });

        return response()->json(['status' => 'success', 'data' => $tasks]);
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

            $taskDate = \Carbon\Carbon::parse($task->created_at)->toDateString();

            try {
                $count = DB::table($module->target_table)
                    ->where($module->user_id_column, $matchValue)
                    ->whereDate($module->date_column, $taskDate)
                    ->count();

                $task->achieved_count = $count;

                if ($task->target_count > 0 && $count >= $task->target_count) {
                    $task->status = 'Completed';
                } elseif ($count > 0 && $task->status === 'Pending') {
                    $task->status = 'In-Progress';
                }

                $task->save();
            } catch (\Throwable $e) { // 🔥 FIX: Catching \Throwable
                \Illuminate\Support\Facades\Log::error("Dynamic Task Sync Error for Task ID {$task->id}: " . $e->getMessage());
            }
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'assignee_type' => 'required|string',
            'assignee_ids' => 'required|array|min:1',
            'tasks' => 'required|array|min:1',
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.tracking_module_id' => 'nullable|exists:task_tracking_modules,id',
            'tasks.*.target_count' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'priority' => 'required|in:Low,Medium,High,Urgent',
            'due_datetime' => 'nullable|date',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        $context = $this->getGlobalContext();
        if (!$context) return response()->json(['status' => 'error', 'message' => 'User context not found.'], 401);

        $user = auth()->user();

        DB::beginTransaction();
        try {
            $uploadedFiles = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
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

            $totalTasksCreated = 0;

            foreach ($request->assignee_ids as $assigneeId) {
                $assigneeClass = $request->assignee_type;
                $assigneeRecord = $assigneeClass::find($assigneeId);

                if (!$assigneeRecord) continue;

                $empCompanyId = $assigneeRecord->company_id ?? $context->company_id;
                $empBranchId = $assigneeRecord->branch_id ?? $context->branch_id;
                $empDeptId = $assigneeRecord->department_id ?? $context->department_id;

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
                        'target_count' => $taskItem['target_count'] ?? 0,
                        'description' => $request->description,
                        'priority' => $request->priority,
                        'due_datetime' => $request->due_datetime,
                        'status' => 'Pending',
                    ]);

                    $totalTasksCreated++;

                    foreach ($uploadedFiles as $fileData) {
                        TaskAttachment::create(array_merge($fileData, [
                            'task_id' => $task->id,
                            'task_progress_log_id' => null,
                            'uploader_type' => get_class($user),
                            'uploader_id' => $user->id,
                        ]));
                    }
                }
            }

            DB::commit();
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
                'actor_name' => $user->name ?? $user->full_name ?? $user->member_name ?? 'System',
                'date' => $log->created_at->format('d M Y, h:i A'),
                'message' => $log->message_or_remark,
                'log_type' => $log->log_type,
                'attachments' => $uploadedFilesForEvent
            ];

            broadcast(new \App\Events\TaskProgressUpdated($task->id, $logData))->toOthers();

            $isAssignee = ($user->id === $task->assignee_id && get_class($user) === $task->assignee_type);
            $receiverType = $isAssignee ? $task->assigner_type : $task->assignee_type;
            $receiverId = $isAssignee ? $task->assigner_id : $task->assignee_id;

            $portal = 'admin';
            if (str_contains($receiverType, 'Employee')) {
                $portal = 'employee';
            } elseif (str_contains($receiverType, 'Member')) {
                $portal = 'customer';
            }

            $globalChannelName = "global.user.{$portal}.{$receiverId}";

            broadcast(new \App\Events\GlobalUserNotification($globalChannelName, $task->id, $task->title, $logData))->toOthers();

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

            $task->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Task has been deleted permanently.'
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
                'target_count' => $request->target_count ?? 0,
                'due_datetime' => $request->due_datetime,
            ]);

            \App\Models\TaskProgressLog::create([
                'task_id' => $task->id,
                'actor_type' => get_class(auth()->user()),
                'actor_id' => auth()->user()->id,
                'log_type' => 'progress_update',
                'message_or_remark' => 'System Note: Task details (Priority/Target/Date) were modified by Admin.',
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
            $query = Task::query();

            $hasDateFilter = $request->filled('start_date') && $request->filled('end_date');
            $startDate = $hasDateFilter ? $request->start_date . ' 00:00:00' : null;
            $endDate = $hasDateFilter ? $request->end_date . ' 23:59:59' : null;

            $query->with(['assignee', 'trackingModule', 'progressLogs' => function ($q) use ($hasDateFilter, $startDate, $endDate) {
                $q->with('actor')->orderBy('created_at', 'desc');
                if ($hasDateFilter) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                }
            }]);

            if (!$context->is_god) {
                $query->where('company_id', $context->company_id);
                if ($context->is_employee) {
                    $query->where('assignee_id', $context->user_id)
                        ->where('assignee_type', get_class(auth()->user()));
                }
            }

            if ($hasDateFilter) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate])
                        ->orWhereBetween('due_datetime', [$startDate, $endDate])
                        ->orWhereHas('progressLogs', function ($logQuery) use ($startDate, $endDate) {
                            $logQuery->whereBetween('created_at', [$startDate, $endDate]);
                        });
                });
            }

            if ($request->filled('company_ids')) {
                $query->whereIn('company_id', explode(',', $request->company_ids));
            }

            if ($request->filled('branch_ids')) {
                $b_ids = explode(',', $request->branch_ids);
                $query->where(function ($q) use ($b_ids) {
                    $normal_branches = [];
                    foreach ($b_ids as $bid) {
                        if (str_starts_with($bid, 'HO_')) {
                            $compId = str_replace('HO_', '', $bid);
                            $q->orWhere(function ($sq) use ($compId) {
                                $sq->where('company_id', $compId)->whereNull('branch_id');
                            });
                        } else {
                            $normal_branches[] = $bid;
                        }
                    }
                    if (count($normal_branches) > 0) {
                        $q->orWhereIn('branch_id', $normal_branches);
                    }
                });
            }

            if ($request->filled('department_ids')) {
                $query->whereIn('department_id', explode(',', $request->department_ids));
            }

            if ($request->filled('assignee_ids')) {
                $query->whereIn('assignee_id', explode(',', $request->assignee_ids));
            }

            $tasks = $query->orderBy('created_at', 'desc')->get();

            $report = [];
            foreach ($tasks as $task) {
                $task->syncLiveProgress();
                $empId = ($task->assignee_type ?? 'Unassigned') . '_' . ($task->assignee_id ?? '0');
                $empName = $task->assignee ? ($task->assignee->full_name ?? $task->assignee->member_name) : 'Unknown User';

                if (!isset($report[$empId])) {
                    $report[$empId] = [
                        'employee_name' => $empName,
                        'total_tasks' => 0,
                        'tasks' => []
                    ];
                }

                $report[$empId]['total_tasks'] += 1;

                $logs = [];
                foreach ($task->progressLogs as $log) {
                    $logs[] = [
                        'date' => $log->created_at->format('d M Y, h:i A'),
                        'actor' => $log->actor ? ($log->actor->full_name ?? $log->actor->member_name ?? $log->actor->name) : 'System',
                        'message' => $log->message_or_remark,
                        'type' => $log->log_type
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
                'message' => 'Line: ' . $e->getLine() . ' | Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
