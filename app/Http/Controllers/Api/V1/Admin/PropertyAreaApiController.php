<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyArea;
use Illuminate\Http\Request;

class PropertyAreaApiController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = PropertyArea::with(['company', 'branch', 'category.propertyType.phase']);

        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if (!$context->is_director && $context->branch_id) {
                $query->where('branch_id', $context->branch_id);
            }
        }

        $totalData = $query->count();
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('area_name', 'LIKE', "%{$search}%");
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
            'property_category_id' => 'required|array',
            'property_category_id.*' => 'integer',
            'area_name' => 'required|string|max:255',
            'measurement_unit' => 'nullable|string|max:50'
        ]);

        $hasDirect = $context->is_god || in_array('p_area_add_direct', $context->permissions);
        $status = $hasDirect ? 'active' : 'pending';

        $categories = \App\Models\PropertyCategory::whereIn('id', $request->property_category_id)->get();
        $insertedCount = 0;

        foreach ($categories as $cat) {
            if (!$context->is_god) {
                if ($cat->company_id != $context->company_id) continue;
                if (!$context->is_director && $context->branch_id && $cat->branch_id != $context->branch_id) continue;
            }

            PropertyArea::create([
                'company_id' => $cat->company_id,
                'branch_id' => $cat->branch_id,
                'property_category_id' => $cat->id,
                'area_name' => $request->area_name,
                'measurement_unit' => $request->measurement_unit ?? 'Sq Ft',
                'status' => $status,
                'created_by' => $context->profile_id
            ]);
            $insertedCount++;
        }

        if ($insertedCount === 0) return response()->json(['success' => false, 'message' => 'Unauthorized Scope or Invalid Categories!'], 403);

        return response()->json(['success' => true, 'message' => $hasDirect ? "Property Area saved for {$insertedCount} category(s)!" : 'Request submitted.']);
    }

    public function update(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $area = PropertyArea::findOrFail($id);
        
        if (!$context->is_god && $area->company_id != $context->company_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized Scope!'], 403);
        }

        $request->validate(['area_name' => 'required|string|max:255', 'measurement_unit' => 'nullable|string|max:50']);
        $area->update(['area_name' => $request->area_name, 'measurement_unit' => $request->measurement_unit ?? 'Sq Ft']);

        return response()->json(['success' => true, 'message' => 'Updated successfully.']);
    }

    public function destroy($id) { PropertyArea::findOrFail($id)->delete(); return response()->json(['success' => true, 'message' => 'Deleted successfully.']); }
    public function bulkDelete(Request $request) { PropertyArea::whereIn('id', $request->ids)->delete(); return response()->json(['success' => true, 'message' => 'Deleted successfully!']); }
    public function approve($id) { PropertyArea::findOrFail($id)->update(['status' => 'active']); return response()->json(['success' => true, 'message' => 'Approved!']); }
    public function reject($id) { PropertyArea::findOrFail($id)->update(['status' => 'inactive']); return response()->json(['success' => true, 'message' => 'Rejected!']); }

    // 🟢 PRINT PREVIEW METHOD
    public function printPreview(Request $request)
    {
        if ($request->has('token')) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($token) { auth()->setUser($token->tokenable); }
        }
        $context = $this->getGlobalContext();
        if (!$context) return abort(401, 'Unauthorized Access.');

        $query = PropertyArea::with(['company', 'branch', 'category.propertyType.phase']);
        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if (!$context->is_director && $context->branch_id) $query->where('branch_id', $context->branch_id);
        }

        $propertyAreas = $query->latest()->get();
        $company = $context->company_id ? \App\Models\Company::find($context->company_id) : null;
        $branch = $context->branch_id ? \App\Models\Branch::find($context->branch_id) : null;

        return view('admin.property_areas.print', compact('propertyAreas', 'company', 'branch'));
    }

    // 🟢 EXPORT CSV METHOD
    public function exportExcel(Request $request)
    {
        if ($request->has('token')) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($token) { auth()->setUser($token->tokenable); }
        }
        $context = $this->getGlobalContext();
        if (!$context) return abort(401, 'Unauthorized Access.');

        $query = PropertyArea::with(['company', 'branch', 'category.propertyType.phase']);
        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if (!$context->is_director && $context->branch_id) $query->where('branch_id', $context->branch_id);
        }

        $propertyAreas = $query->latest()->get();
        $fileName = 'property_areas_' . date('Y-m-d_H-i') . '.csv';

        $headers = [ "Content-type" => "text/csv", "Content-Disposition" => "attachment; filename=$fileName", "Pragma" => "no-cache", "Cache-Control" => "must-revalidate", "Expires" => "0" ];

        $callback = function() use($propertyAreas) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Area Name', 'Unit', 'Category Name', 'Type Name', 'Phase Name', 'Company', 'Branch', 'Status']);

            foreach ($propertyAreas as $row) {
                fputcsv($file, [
                    $row->id,
                    $row->area_name,
                    $row->measurement_unit,
                    $row->category ? $row->category->category_name : 'N/A',
                    ($row->category && $row->category->propertyType) ? $row->category->propertyType->type_name : 'N/A',
                    ($row->category && $row->category->propertyType && $row->category->propertyType->phase) ? $row->category->propertyType->phase->phase_name : 'N/A',
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