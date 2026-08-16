<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyEmiPlan;
use Illuminate\Http\Request;

class PropertyEmiPlanApiController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = PropertyEmiPlan::with(['company', 'branch', 'phase']);

        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if (!$context->is_director && $context->branch_id) $query->where('branch_id', $context->branch_id);
        }

        $totalData = $query->count();
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('plan_name', 'LIKE', "%{$search}%");
        }
        $totalFiltered = $query->count();

        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $query->latest()->get(),
            "permissions" => $context->permissions 
        ]);
    }

    public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        $request->validate([
            'phase_id' => 'required|array',
            'phase_id.*' => 'integer',
            'plan_name' => 'required|string|max:255',
            'emi_tenure' => 'required|integer|min:0',
            'rate_discount_per_sqft' => 'required|numeric|min:0',
            'downpayment_percentage' => 'required|numeric|min:0|max:100',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $hasDirect = $context->is_god || in_array('p_emi_add_direct', $context->permissions);
        $status = $hasDirect ? 'active' : 'pending';

        $phases = \App\Models\Phase::whereIn('id', $request->phase_id)->get();
        $insertedCount = 0;

        foreach ($phases as $phase) {
            if (!$context->is_god) {
                if ($phase->company_id != $context->company_id) continue;
                if (!$context->is_director && $context->branch_id && $phase->branch_id != $context->branch_id) continue;
            }
            PropertyEmiPlan::create([
                'company_id' => $phase->company_id,
                'branch_id' => $phase->branch_id,
                'phase_id' => $phase->id,
                'plan_name' => $request->plan_name,
                'emi_tenure' => $request->emi_tenure,
                'rate_discount_per_sqft' => $request->rate_discount_per_sqft,
                'downpayment_percentage' => $request->downpayment_percentage,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => $status,
                'created_by' => $context->profile_id
            ]);
            $insertedCount++;
        }

        if ($insertedCount === 0) return response()->json(['success' => false, 'message' => 'Unauthorized Scope or Invalid Phases!'], 403);
        return response()->json(['success' => true, 'message' => $hasDirect ? "EMI Plan saved for {$insertedCount} phase(s)!" : 'Request submitted.']);
    }

    public function update(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $plan = PropertyEmiPlan::findOrFail($id);
        
        if (!$context->is_god && $plan->company_id != $context->company_id) return response()->json(['success' => false, 'message' => 'Unauthorized Scope!'], 403);

        $request->validate([
            'plan_name' => 'required|string|max:255',
            'emi_tenure' => 'required|integer|min:0',
            'rate_discount_per_sqft' => 'required|numeric|min:0',
            'downpayment_percentage' => 'required|numeric|min:0|max:100',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);
        
        $plan->update($request->only(['plan_name', 'emi_tenure', 'rate_discount_per_sqft', 'downpayment_percentage', 'start_date', 'end_date']));
        return response()->json(['success' => true, 'message' => 'Updated successfully.']);
    }

    public function destroy($id) { PropertyEmiPlan::findOrFail($id)->delete(); return response()->json(['success' => true, 'message' => 'Deleted successfully.']); }
    public function bulkDelete(Request $request) { PropertyEmiPlan::whereIn('id', $request->ids)->delete(); return response()->json(['success' => true, 'message' => 'Deleted successfully!']); }
    public function approve($id) { PropertyEmiPlan::findOrFail($id)->update(['status' => 'active']); return response()->json(['success' => true, 'message' => 'Approved!']); }
    public function reject($id) { PropertyEmiPlan::findOrFail($id)->update(['status' => 'inactive']); return response()->json(['success' => true, 'message' => 'Rejected!']); }

    public function printPreview(Request $request)
    {
        if ($request->has('token')) { $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token); if ($token) auth()->setUser($token->tokenable); }
        $context = $this->getGlobalContext(); if (!$context) return abort(401, 'Unauthorized Access.');

        $query = PropertyEmiPlan::with(['company', 'branch', 'phase']);
        if (!$context->is_god) { $query->where('company_id', $context->company_id); if (!$context->is_director && $context->branch_id) $query->where('branch_id', $context->branch_id); }

        $emiPlans = $query->latest()->get();
        $company = $context->company_id ? \App\Models\Company::find($context->company_id) : null;
        $branch = $context->branch_id ? \App\Models\Branch::find($context->branch_id) : null;
        return view('admin.property_emi_plans.print', compact('emiPlans', 'company', 'branch'));
    }

    public function exportExcel(Request $request)
    {
        if ($request->has('token')) { $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token); if ($token) auth()->setUser($token->tokenable); }
        $context = $this->getGlobalContext(); if (!$context) return abort(401, 'Unauthorized Access.');

        $query = PropertyEmiPlan::with(['company', 'branch', 'phase']);
        if (!$context->is_god) { $query->where('company_id', $context->company_id); if (!$context->is_director && $context->branch_id) $query->where('branch_id', $context->branch_id); }

        $emiPlans = $query->latest()->get();
        $fileName = 'property_emi_plans_' . date('Y-m-d_H-i') . '.csv';
        $headers = [ "Content-type" => "text/csv", "Content-Disposition" => "attachment; filename=$fileName", "Pragma" => "no-cache", "Cache-Control" => "must-revalidate", "Expires" => "0" ];

        $callback = function() use($emiPlans) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Plan Name', 'Tenure (Months)', 'Discount/SqFt', 'Downpayment %', 'Start Date', 'End Date', 'Phase', 'Company', 'Branch', 'Status']);
            foreach ($emiPlans as $row) {
                fputcsv($file, [
                    $row->id, $row->plan_name, $row->emi_tenure, $row->rate_discount_per_sqft, $row->downpayment_percentage,
                    $row->start_date, $row->end_date ?? 'Ongoing',
                    $row->phase ? $row->phase->phase_name : 'N/A', $row->company ? $row->company->company_name : 'N/A',
                    $row->branch ? $row->branch->branch_name : 'HO', strtoupper($row->status)
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}