<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyCategory;
use Illuminate\Http\Request;

class PropertyCategoryApiController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = PropertyCategory::with(['company', 'branch', 'propertyType.phase']);

        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if (!$context->is_director && $context->branch_id) {
                $query->where('branch_id', $context->branch_id);
            }
        }

        $totalData = $query->count();
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('category_name', 'LIKE', "%{$search}%");
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
            'property_type_id' => 'required|array',
            'property_type_id.*' => 'integer',
            'category_name' => 'required|string|max:255'
        ]);

        $hasDirect = $context->is_god || in_array('p_cat_add_direct', $context->permissions);
        $status = $hasDirect ? 'active' : 'pending';

        $types = \App\Models\PropertyType::whereIn('id', $request->property_type_id)->get();
        $insertedCount = 0;

        foreach ($types as $type) {
            if (!$context->is_god) {
                if ($type->company_id != $context->company_id) continue;
                if (!$context->is_director && $context->branch_id && $type->branch_id != $context->branch_id) continue;
            }

            PropertyCategory::create([
                'company_id' => $type->company_id,
                'branch_id' => $type->branch_id,
                'property_type_id' => $type->id,
                'category_name' => $request->category_name,
                'status' => $status,
                'created_by' => $context->profile_id
            ]);
            $insertedCount++;
        }

        if ($insertedCount === 0) return response()->json(['success' => false, 'message' => 'Unauthorized Scope or Invalid Types!'], 403);

        return response()->json(['success' => true, 'message' => $hasDirect ? "Property Category saved for {$insertedCount} type(s)!" : 'Request submitted.']);
    }

    public function update(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $category = PropertyCategory::findOrFail($id);
        
        if (!$context->is_god && $category->company_id != $context->company_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized Scope!'], 403);
        }

        $request->validate(['category_name' => 'required|string|max:255']);
        $category->update(['category_name' => $request->category_name]);

        return response()->json(['success' => true, 'message' => 'Updated successfully.']);
    }

    public function destroy($id) { PropertyCategory::findOrFail($id)->delete(); return response()->json(['success' => true, 'message' => 'Deleted successfully.']); }
    public function bulkDelete(Request $request) { PropertyCategory::whereIn('id', $request->ids)->delete(); return response()->json(['success' => true, 'message' => 'Deleted successfully!']); }
    public function approve($id) { PropertyCategory::findOrFail($id)->update(['status' => 'active']); return response()->json(['success' => true, 'message' => 'Approved!']); }
    public function reject($id) { PropertyCategory::findOrFail($id)->update(['status' => 'inactive']); return response()->json(['success' => true, 'message' => 'Rejected!']); }

    // 🟢 PRINT PREVIEW METHOD (Token Auth Integrated)
    public function printPreview(Request $request)
    {
        if ($request->has('token')) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($token) { auth()->setUser($token->tokenable); }
        }

        $context = $this->getGlobalContext();
        if (!$context) return abort(401, 'Unauthorized Access.');

        $query = PropertyCategory::with(['company', 'branch', 'propertyType.phase']);
        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if (!$context->is_director && $context->branch_id) $query->where('branch_id', $context->branch_id);
        }

        $propertyCategories = $query->latest()->get();
        $company = $context->company_id ? \App\Models\Company::find($context->company_id) : null;
        $branch = $context->branch_id ? \App\Models\Branch::find($context->branch_id) : null;

        return view('admin.property_categories.print', compact('propertyCategories', 'company', 'branch'));
    }

    // 🟢 EXPORT CSV METHOD (Token Auth Integrated)
    public function exportExcel(Request $request)
    {
        if ($request->has('token')) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($token) { auth()->setUser($token->tokenable); }
        }

        $context = $this->getGlobalContext();
        if (!$context) return abort(401, 'Unauthorized Access.');

        $query = PropertyCategory::with(['company', 'branch', 'propertyType.phase']);
        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if (!$context->is_director && $context->branch_id) $query->where('branch_id', $context->branch_id);
        }

        $propertyCategories = $query->latest()->get();
        $fileName = 'property_categories_' . date('Y-m-d_H-i') . '.csv';

        $headers = [ "Content-type" => "text/csv", "Content-Disposition" => "attachment; filename=$fileName", "Pragma" => "no-cache", "Cache-Control" => "must-revalidate", "Expires" => "0" ];

        $callback = function() use($propertyCategories) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Category Name', 'Type Name', 'Phase Name', 'Company', 'Branch', 'Status']);

            foreach ($propertyCategories as $row) {
                fputcsv($file, [
                    $row->id,
                    $row->category_name,
                    $row->propertyType ? $row->propertyType->type_name : 'N/A',
                    ($row->propertyType && $row->propertyType->phase) ? $row->propertyType->phase->phase_name : 'N/A',
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