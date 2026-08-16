<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyType;
use Illuminate\Http\Request;

class PropertyTypeApiController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext(); // Context fetching[cite: 11]
        $query = PropertyType::with(['company', 'branch', 'phase']);

        // 🛡️ ZERO-TRUST SCOPING[cite: 11]
        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if (!$context->is_director && $context->branch_id) {
                $query->where('branch_id', $context->branch_id);
            }
        }

        // DataTables Pagination & Search Logic
        $totalData = $query->count();
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('type_name', 'LIKE', "%{$search}%");
        }
        $totalFiltered = $query->count();

        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        $data = $query->latest()->get();

        // Pass RBAC array to frontend for action buttons rendering[cite: 11]
        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $data,
            "permissions" => $context->permissions 
        ]);
    }

   public function store(Request $request)
    {
        $context = $this->getGlobalContext(); 
        
        // Validation me array allow kar diya hai
        $request->validate([
            'phase_id' => 'required|array',
            'phase_id.*' => 'integer',
            'type_name' => 'required|string|max:255'
        ]);

        // RBAC Check
        $hasDirect = $context->is_god || in_array('p_type_add_direct', $context->permissions);
        $status = $hasDirect ? 'active' : 'pending';

        // Har selected phase ke liye record banayenge
        $phases = \App\Models\Phase::whereIn('id', $request->phase_id)->get();
        $insertedCount = 0;

        foreach ($phases as $phase) {
            // Security Override: Check if user has scope to add in this company
            if (!$context->is_god) {
                if ($phase->company_id != $context->company_id) continue;
                if (!$context->is_director && $context->branch_id && $phase->branch_id != $context->branch_id) continue;
            }

            PropertyType::create([
                'company_id' => $phase->company_id, // Phase se hi directly exact company nikal li
                'branch_id' => $phase->branch_id,   // Phase se hi exact branch (ya HO) nikal liya
                'phase_id' => $phase->id,
                'type_name' => $request->type_name,
                'status' => $status,
                'created_by' => $context->profile_id
            ]);
            
            $insertedCount++;
        }

        if ($insertedCount === 0) {
            return response()->json(['success' => false, 'message' => 'Unauthorized Scope or Invalid Phases!'], 403);
        }

        return response()->json([
            'success' => true, 
            'message' => $hasDirect ? "Property Type saved for {$insertedCount} phase(s)!" : 'Request submitted for approval.'
        ]);
    }

    public function update(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $propertyType = PropertyType::findOrFail($id);
        
        // Scope Verification
        if (!$context->is_god && $propertyType->company_id != $context->company_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized Scope!'], 403);
        }

        $request->validate(['type_name' => 'required|string|max:255']);
        $propertyType->update(['type_name' => $request->type_name]);

        return response()->json(['success' => true, 'message' => 'Updated successfully.']);
    }

    public function destroy($id)
    {
        PropertyType::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        PropertyType::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => true, 'message' => 'Selected records deleted!']);
    }

    public function approve($id)
    {
        PropertyType::findOrFail($id)->update(['status' => 'active']);
        return response()->json(['success' => true, 'message' => 'Approved successfully!']);
    }

    public function reject($id)
    {
        PropertyType::findOrFail($id)->update(['status' => 'inactive']);
        return response()->json(['success' => true, 'message' => 'Rejected successfully!']);
    }

    // 🟢 PRINT PREVIEW METHOD
    public function printPreview(Request $request)
    {
        // 🔥 FIX: Manual token authentication for Web Routes
        if ($request->has('token')) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($token) { auth()->setUser($token->tokenable); }
        }

        $context = $this->getGlobalContext();
        if (!$context) { return abort(401, 'Unauthorized Access - Token missing or expired.'); }

        $query = PropertyType::with(['company', 'branch', 'phase']);

        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if (!$context->is_director && $context->branch_id) {
                $query->where('branch_id', $context->branch_id);
            }
        }

        $propertyTypes = $query->latest()->get();
        
        $company = $context->company_id ? \App\Models\Company::find($context->company_id) : null;
        $branch = $context->branch_id ? \App\Models\Branch::find($context->branch_id) : null;

        return view('admin.property_types.print', compact('propertyTypes', 'company', 'branch'));
    }

    // 🟢 EXPORT CSV METHOD
    public function exportExcel(Request $request)
    {
        // 🔥 FIX: Manual token authentication for Web Routes
        if ($request->has('token')) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($token) { auth()->setUser($token->tokenable); }
        }

        $context = $this->getGlobalContext();
        if (!$context) { return abort(401, 'Unauthorized Access - Token missing or expired.'); }

        $query = PropertyType::with(['company', 'branch', 'phase']);

        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if (!$context->is_director && $context->branch_id) {
                $query->where('branch_id', $context->branch_id);
            }
        }

        $propertyTypes = $query->latest()->get();
        $fileName = 'property_types_report_' . date('Y-m-d_H-i') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($propertyTypes) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Type Name', 'Phase Name', 'Company', 'Branch', 'Status', 'Created At']);

            foreach ($propertyTypes as $row) {
                $phaseName = $row->phase ? $row->phase->phase_name : 'N/A';
                $companyName = $row->company ? $row->company->company_name : 'N/A';
                $branchName = $row->branch ? $row->branch->branch_name : 'Head Office';
                
                fputcsv($file, [
                    $row->id,
                    $row->type_name,
                    $phaseName,
                    $companyName,
                    $branchName,
                    strtoupper($row->status),
                    $row->created_at->format('d-M-Y h:i A')
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

  
}