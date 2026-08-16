<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyRate;
use Illuminate\Http\Request;

class PropertyRateApiController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = PropertyRate::with(['company', 'branch', 'area.category.propertyType.phase']);

        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if (!$context->is_director && $context->branch_id) {
                $query->where('branch_id', $context->branch_id);
            }
        }

        $totalData = $query->count();
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('rate_amount', 'LIKE', "%{$search}%");
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
            'property_area_id' => 'required|array',
            'property_area_id.*' => 'integer',
            'rate_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $hasDirect = $context->is_god || in_array('p_rate_add_direct', $context->permissions);
        $status = $hasDirect ? 'active' : 'pending';

        $areas = \App\Models\PropertyArea::whereIn('id', $request->property_area_id)->get();
        $insertedCount = 0;

        foreach ($areas as $area) {
            if (!$context->is_god) {
                if ($area->company_id != $context->company_id) continue;
                if (!$context->is_director && $context->branch_id && $area->branch_id != $context->branch_id) continue;
            }

            PropertyRate::create([
                'company_id' => $area->company_id,
                'branch_id' => $area->branch_id,
                'property_area_id' => $area->id,
                'rate_amount' => $request->rate_amount,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => $status,
                'created_by' => $context->profile_id
            ]);
            $insertedCount++;
        }

        if ($insertedCount === 0) return response()->json(['success' => false, 'message' => 'Unauthorized Scope or Invalid Areas!'], 403);

        return response()->json(['success' => true, 'message' => $hasDirect ? "Rate saved for {$insertedCount} area(s)!" : 'Request submitted.']);
    }

    public function update(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $rate = PropertyRate::findOrFail($id);
        
        if (!$context->is_god && $rate->company_id != $context->company_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized Scope!'], 403);
        }

        $request->validate([
            'rate_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);
        
        $rate->update([
            'rate_amount' => $request->rate_amount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date
        ]);

        return response()->json(['success' => true, 'message' => 'Updated successfully.']);
    }

    public function destroy($id) { PropertyRate::findOrFail($id)->delete(); return response()->json(['success' => true, 'message' => 'Deleted successfully.']); }
    public function bulkDelete(Request $request) { PropertyRate::whereIn('id', $request->ids)->delete(); return response()->json(['success' => true, 'message' => 'Deleted successfully!']); }
    public function approve($id) { PropertyRate::findOrFail($id)->update(['status' => 'active']); return response()->json(['success' => true, 'message' => 'Approved!']); }
    public function reject($id) { PropertyRate::findOrFail($id)->update(['status' => 'inactive']); return response()->json(['success' => true, 'message' => 'Rejected!']); }

    // 🟢 PRINT PREVIEW
    public function printPreview(Request $request)
    {
        if ($request->has('token')) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($token) { auth()->setUser($token->tokenable); }
        }
        $context = $this->getGlobalContext();
        if (!$context) return abort(401, 'Unauthorized Access.');

        $query = PropertyRate::with(['company', 'branch', 'area.category.propertyType.phase']);
        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if (!$context->is_director && $context->branch_id) $query->where('branch_id', $context->branch_id);
        }

        $propertyRates = $query->latest()->get();
        $company = $context->company_id ? \App\Models\Company::find($context->company_id) : null;
        $branch = $context->branch_id ? \App\Models\Branch::find($context->branch_id) : null;

        return view('admin.property_rates.print', compact('propertyRates', 'company', 'branch'));
    }

    // 🟢 EXPORT CSV
    public function exportExcel(Request $request)
    {
        if ($request->has('token')) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($token) { auth()->setUser($token->tokenable); }
        }
        $context = $this->getGlobalContext();
        if (!$context) return abort(401, 'Unauthorized Access.');

        $query = PropertyRate::with(['company', 'branch', 'area.category.propertyType.phase']);
        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if (!$context->is_director && $context->branch_id) $query->where('branch_id', $context->branch_id);
        }

        $propertyRates = $query->latest()->get();
        $fileName = 'property_rates_' . date('Y-m-d_H-i') . '.csv';

        $headers = [ "Content-type" => "text/csv", "Content-Disposition" => "attachment; filename=$fileName", "Pragma" => "no-cache", "Cache-Control" => "must-revalidate", "Expires" => "0" ];

        $callback = function() use($propertyRates) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Rate Amount', 'Start Date', 'End Date', 'Area Name', 'Category', 'Type', 'Phase', 'Company', 'Branch', 'Status']);

            foreach ($propertyRates as $row) {
                fputcsv($file, [
                    $row->id,
                    $row->rate_amount,
                    $row->start_date,
                    $row->end_date ?? 'Ongoing',
                    $row->area ? $row->area->area_name . ' ' . $row->area->measurement_unit : 'N/A',
                    ($row->area && $row->area->category) ? $row->area->category->category_name : 'N/A',
                    ($row->area && $row->area->category && $row->area->category->propertyType) ? $row->area->category->propertyType->type_name : 'N/A',
                    ($row->area && $row->area->category && $row->area->category->propertyType && $row->area->category->propertyType->phase) ? $row->area->category->propertyType->phase->phase_name : 'N/A',
                    $row->company ? $row->company->company_name : 'N/A',
                    $row->branch ? $row->branch->branch_name : 'Head Office',
                    strtoupper($row->status)
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}