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
     * 🔥 FETCH TASKS (With Auto-Sync Magic)
     */
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();

        // 1. Agar employee/member apna panel khol raha hai, toh pehle uske dynamic tasks update karo
        if ($context->is_employee || $context->is_member) {
            $this->autoSyncDynamicTasks($user);
        }

        // 2. Apne scope ke hisaab se tasks fetch karo
        $query = Task::with(['assigner', 'assignee', 'trackingModule', 'progressLogs.actor']);

        if ($context->is_god) {
            // CEO sab kuch dekh sakta hai, filter apply kar sakta hai
            if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
            if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        } elseif ($context->is_director) {
            // Director sirf apni company ka dekhega
            $query->where('company_id', $context->company_id);
            if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        } else {
            // Employee/Member sirf wo tasks dekhega jo ya toh usne DIYE hain, ya use MILE hain
            $query->where(function($q) use ($user) {
                $q->where(function($subQ) use ($user) {
                    $subQ->where('assignee_type', get_class($user))->where('assignee_id', $user->id);
                })->orWhere(function($subQ) use ($user) {
                    $subQ->where('assigner_type', get_class($user))->where('assigner_id', $user->id);
                });
            });
        }

        // Status filter (agar front-end se 'Pending', 'Completed' wagerah bheja gaya)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tasks = $query->latest()->get();

        return response()->json(['status' => 'success', 'data' => $tasks]);
    }


    /**
     * 🔥 DYNAMIC PROGRESS ENGINE (The Magic)
     */
    private function autoSyncDynamicTasks($user)
    {
        // Sirf un tasks ko pakdo jo is user ko mile hain, pending/in-progress hain, aur dynamic hain
        $tasks = Task::where('assignee_type', get_class($user))
            ->where('assignee_id', $user->id)
            ->whereNotNull('tracking_module_id')
            ->whereIn('status', ['Pending', 'In-Progress'])
            ->with('trackingModule')
            ->get();

        foreach ($tasks as $task) {
            $module = $task->trackingModule;
            if (!$module || !$module->is_dynamic) continue;

            // Decide karo ki database se 'member_id' match karna hai ya 'id'
            $matchValue = ($module->join_column === 'member_id') ? ($user->member_id ?? null) : $user->id;

            if (!$matchValue) continue;

            // Date set karo (Jis din task assign hua tha, usi din ki entries track karni hain)
            $taskDate = \Carbon\Carbon::parse($task->created_at)->toDateString();

            try {
                // 🔥 THE DYNAMIC QUERY 🔥
                // E.g: DB::table('debit_vouchers')->where('approved_by', 'ABDPL-A/0007')->whereDate('created_at', '2026-06-07')->count();
                $count = DB::table($module->target_table)
                    ->where($module->user_id_column, $matchValue)
                    ->whereDate($module->date_column, $taskDate)
                    ->count();

                $task->achieved_count = $count;
                
                // Agar target pura ho gaya toh status 'Completed' kar do
                if ($task->target_count > 0 && $count >= $task->target_count) {
                    $task->status = 'Completed';
                } elseif ($count > 0 && $task->status === 'Pending') {
                    $task->status = 'In-Progress'; // Entry start karte hi In-Progress
                }
                
                $task->save();
            } catch (\Exception $e) {
                // Agar column name wrong configure ho gaya toh system crash na ho
                \Illuminate\Support\Facades\Log::error("Dynamic Task Sync Error for Task ID {$task->id}: " . $e->getMessage());
            }
        }
    }



   
  /**
     * 🔥 TASK ASSIGNMENT ENGINE (Multi-Assign Support)
     */
 public function store(Request $request)
    {
        // 🔥 NAYA: Ab 'title', 'tracking_module_id' aur 'target_count' ek Array ('tasks') ke andar aayenge
        $request->validate([
            'assignee_type' => 'required|string', 
            'assignee_ids' => 'required|array|min:1', 
            'tasks' => 'required|array|min:1', // Repeater array
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.tracking_module_id' => 'nullable|exists:task_tracking_modules,id',
            'tasks.*.target_count' => 'nullable|integer|min:0',
            
            // Ye sab common rahenge saare tasks ke liye
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
            $companyId = $context->is_god ? $request->company_id : $context->company_id;
            $branchId = $context->is_god || $context->is_director ? $request->branch_id : $context->branch_id;
            $departmentId = $context->is_employee ? $context->department_id : $request->department_id;

            // 1. Handle File Uploads (File ek baar save hogi, link multiple times hogi)
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

            // 2. Loop through selected Assignees
            foreach ($request->assignee_ids as $assigneeId) {
                
                // 3. Loop through Multiple Tasks for EACH Assignee (Repeater Magic)
                foreach ($request->tasks as $taskItem) {
                    
                    $task = Task::create([
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
                        'department_id' => $departmentId,
                        'assigner_type' => get_class($user),
                        'assigner_id' => $user->id,
                        'assignee_type' => $request->assignee_type,
                        'assignee_id' => $assigneeId, 
                        'title' => $taskItem['title'], // From Array
                        'tracking_module_id' => $taskItem['tracking_module_id'] ?? null, // From Array
                        'target_count' => $taskItem['target_count'] ?? 0, // From Array
                        'description' => $request->description, // Common
                        'priority' => $request->priority,       // Common
                        'due_datetime' => $request->due_datetime, // Common
                        'status' => 'Pending',
                    ]);

                    $totalTasksCreated++;

                    // 4. Attach common files to each specific task card
                    foreach ($uploadedFiles as $fileData) {
                        TaskAttachment::create(array_merge($fileData, [
                            'task_id' => $task->id,
                            'task_progress_log_id' => null, // Initial attachment
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
    /**
     * 🔥 SHOW TASK DETAILS (With Security Scope Check)
     */
    public function show($id)
    {
        try {
            $context = $this->getGlobalContext();
            $user = auth()->user();

            $task = Task::with([
                'assigner', 
                'assignee', 
                'trackingModule',
                'attachments' => function($q) {
                    $q->orderBy('created_at', 'desc');
                },
                'progressLogs' => function($q) {
                    $q->with('actor')->orderBy('created_at', 'desc');
                }
            ])->findOrFail($id);

            // 🛡️ SMART SECURITY SCOPE CHECK
            if (!$context->is_god) {
                // Check 1: Agar current user khud Assignee (jisko task mila hai) ya Assigner (jisne diya hai) hai, toh full access do!
                $isDirectlyInvolved = 
                    ($task->assignee_type === get_class($user) && $task->assignee_id === $user->id) ||
                    ($task->assigner_type === get_class($user) && $task->assigner_id === $user->id);

                // Check 2: Agar directly involved nahi hai, toh dekho kya uski company same hai (For Directors)
                if (!$isDirectlyInvolved && $task->company_id != $context->company_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Access! Yeh task aapke scope se bahar hai.'], 403);
                }
            }

            return response()->json(['status' => 'success', 'data' => $task]);

        } catch (\Exception $e) {
            // Agar code crash hota hai, toh exact line aur error frontend ko bhejo
            return response()->json([
                'status' => 'error', 
                'message' => 'System Error: ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ' on line ' . $e->getLine()
            ], 500);
        }
    }

    /**
     * 🔥 2. ADD REPLY, REMARK & UPDATE STATUS 🔥
     */
    public function addReply(Request $request, $id)
    {
        $request->validate([
            'message_or_remark' => 'required|string',
            'status' => 'nullable|in:Pending,In-Progress,Under Review,Completed',
            'attachments.*' => 'nullable|file|max:10240', // Max 10MB per file
        ]);

        $context = $this->getGlobalContext();
        $user = auth()->user();

        DB::beginTransaction();
        try {
            $task = Task::findOrFail($id);

            // 1. Create Progress Log (Timeline entry)
            $log = TaskProgressLog::create([
                'task_id' => $task->id,
                'actor_type' => get_class($user),
                'actor_id' => $user->id,
                'log_type' => 'reply',
                'message_or_remark' => $request->message_or_remark,
                'entries_completed' => 0 // Manual me usually 0 rakhenge, auto me count jayega
            ]);

            // 2. Upload Attachments (Agar employee ne file/proof diya hai)
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $originalName = $file->getClientOriginalName();
                    $ext = $file->getClientOriginalExtension();
                    $filename = time() . '_' . uniqid() . '.' . $ext;
                    $file->move(public_path('uploads/task_attachments'), $filename);
                    
                    TaskAttachment::create([
                        'task_id' => $task->id,
                        'task_progress_log_id' => $log->id, // Is reply ke sath link kar diya
                        'uploader_type' => get_class($user),
                        'uploader_id' => $user->id,
                        'file_name' => $originalName,
                        'file_path' => 'uploads/task_attachments/' . $filename,
                        'file_type' => $ext,
                    ]);
                }
            }

            // 3. Update Task Status (Agar front-end se status change bheja gaya hai)
            if ($request->filled('status') && $request->status !== $task->status) {
                $task->update(['status' => $request->status]);
                
                // Status change ka ek system log bhi daal do timeline mein
                TaskProgressLog::create([
                    'task_id' => $task->id,
                    'actor_type' => get_class($user),
                    'actor_id' => $user->id,
                    'log_type' => 'progress_update',
                    'message_or_remark' => "Changed task status to: " . $request->status,
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 'success', 
                'message' => 'Update posted successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 🔥 DELETE TASK (Destroy) 🔥
     */
    public function destroy($id)
    {
        try {
            $context = $this->getGlobalContext();
            $task = Task::findOrFail($id);

            // 🛡️ SECURITY SCOPE CHECK
            if (!$context->is_god) {
                if ($task->company_id != $context->company_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Access to delete this Task!'], 403);
                }
            }

            // TaskDelete karte hi database aage ka kaam automatically kar dega agar foreign keys set hain (Cascade)
            // Warna manually attachments aur logs hatane padenge. Hum safe side par manually uda dete hain pehle:
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

    /**
     * 🔥 UPDATE TASK (Edit Feature) 🔥
     */
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

            // 🛡️ SECURITY SCOPE CHECK
            if (!$context->is_god) {
                if ($task->company_id != $context->company_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Access! You cannot edit this task.'], 403);
                }
            }

            // Update Task record
            $task->update([
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority,
                'tracking_module_id' => $request->tracking_module_id,
                'target_count' => $request->target_count ?? 0,
                'due_datetime' => $request->due_datetime,
            ]);

            // Ek system log timeline mein daal do taaki employee ko pata chale ki task modify hua hai
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


}