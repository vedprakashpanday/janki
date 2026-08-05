<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutoTaskSetting;
use Illuminate\Http\Request;

class AutoTaskSettingController extends Controller
{
    // 1. Data load karna (Table & Cards ke liye)
    public function index()
    {
        $context = $this->getGlobalContext();

        $query = AutoTaskSetting::with(['assignee', 'phase']);

        // Scope check
        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
        }

        $settings = $query->latest()->get();
        return response()->json(['success' => true, 'data' => $settings]);
    }

    // 2. Naye rules banana (Multi-select employees ke liye bulk create)
    public function store(Request $request)
    {
        $context = $this->getGlobalContext();

        $request->validate([
            'assignee_type' => 'required|string',
            'assignee_ids' => 'required|array|min:1', // Ab ye array aayega multi-select se
            'title_template' => 'required|string|max:255',
            'task_type' => 'required|in:manual,target',
            'run_time' => 'required',
            'priority' => 'required|in:Low,Medium,High,Urgent',
        ]);

        $createdCount = 0;

        // Har selected employee ke liye alag rule banega
        foreach ($request->assignee_ids as $empId) {
            $data = [
                'company_id' => $context->company_id,
                'branch_id' => $context->branch_id,
                'assignee_type' => $request->assignee_type,
                'assignee_id' => $empId,
                'title_template' => $request->title_template,
                'description_template' => $request->description_template,
                'priority' => $request->priority,
                'run_time' => $request->run_time,
                'carry_forward_pending' => $request->has('carry_forward_pending') ? true : false,
                'is_active' => true,
                'created_by' => $context->profile_id,
            ];

          if ($request->task_type === 'target') {
            $data['phase_id'] = $request->phase_id;
            $data['tracking_module_id'] = $request->tracking_module_id;
            $data['daily_target_count'] = $request->daily_target_count;
            $data['provider_id'] = $request->provider_id ?? null;
            $data['provider_percent'] = $request->provider_percent ?? 50; // 🔥 YAHAN ADD KAREIN
        } else {
            $data['phase_id'] = null;
            $data['tracking_module_id'] = null;
            $data['daily_target_count'] = 0;
            $data['carry_forward_pending'] = false;
            $data['provider_id'] = null; 
            $data['provider_percent'] = 50; // 🔥 YAHAN BHI ADD KAREIN
        }

            AutoTaskSetting::create($data);
            $createdCount++;
        }

        return response()->json(['success' => true, 'message' => "$createdCount Auto-Task rules created successfully!"]);
    }

    // 3. Edit Modal ke liye data bhejna
    public function show($id)
    {
        $setting = AutoTaskSetting::with('assignee')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $setting]);
    }

    // 4. Edit form se data update karna
    public function update(Request $request, $id)
    {
        $setting = AutoTaskSetting::findOrFail($id);

        $request->validate([
            'title_template' => 'required|string|max:255',
            'task_type' => 'required|in:manual,target',
            'run_time' => 'required',
            'priority' => 'required|in:Low,Medium,High,Urgent',
            
        ]);

        $data = [
            'title_template' => $request->title_template,
            'description_template' => $request->description_template,
            'priority' => $request->priority,
            'run_time' => $request->run_time,
            'carry_forward_pending' => $request->has('carry_forward_pending') ? true : false,
        ];

       if ($request->task_type === 'target') {
            $data['phase_id'] = $request->phase_id;
            $data['tracking_module_id'] = $request->tracking_module_id;
            $data['daily_target_count'] = $request->daily_target_count;
            $data['provider_id'] = $request->provider_id ?? null;
            $data['provider_percent'] = $request->provider_percent ?? 50; // 🔥 YAHAN ADD KAREIN
        } else {
            $data['phase_id'] = null;
            $data['tracking_module_id'] = null;
            $data['daily_target_count'] = 0;
            $data['carry_forward_pending'] = false;
            $data['provider_id'] = null; 
            $data['provider_percent'] = 50; // 🔥 YAHAN BHI ADD KAREIN
        }

        $setting->update($data);

        return response()->json(['success' => true, 'message' => 'Rule updated successfully!']);
    }

    // 5. Status ON/OFF karna
    public function updateStatus(Request $request, $id)
    {
        $setting = AutoTaskSetting::findOrFail($id);
        $setting->update(['is_active' => $request->is_active]);
        return response()->json(['success' => true, 'message' => 'Status updated!']);
    }

    // 6. Delete karna (Single)
    public function destroy($id)
    {
        AutoTaskSetting::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Rule deleted!']);
    }
}
