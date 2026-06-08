<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DesignationController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Designation::with('department')->latest();

        // 🛡️ ZERO-TRUST SCOPING VIA DEPARTMENT
        if (!$context->is_god) {
            $companyId = $context->company_id;
            // Wahi designations lao jinke department tumhari company ke andar aate hain ya global hain
            $query->whereHas('department', function ($q) use ($companyId) {
                $q->whereNull('company_ids')
                    ->orWhereJsonContains('company_ids', 'all')
                    ->orWhereJsonContains('company_ids', (string)$companyId)
                    ->orWhereJsonContains('company_ids', (int)$companyId);
            });
        }

        $totalData = Designation::count();

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('designation_name', 'LIKE', "%{$search}%")
                ->orWhere('designation_code', 'LIKE', "%{$search}%");
        }

        if ($request->filled('department_ids')) {
    $query->whereIn('department_id', explode(',', $request->department_ids));
} elseif ($request->filled('department_id')) {
    $query->where('department_id', $request->department_id);
}

        $totalFiltered = $query->count();
        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $query->get()
        ]);
    }

    public function show($id)
    {
        $context = $this->getGlobalContext();
        $designation = Designation::with('department')->findOrFail($id);

        if (!$context->is_god) {
            $dept = $designation->department;
            if ($dept) {
                $cIds = $dept->company_ids ?? [];
                $isGlobal = empty($cIds) || in_array('all', $cIds);
                $belongsToCompany = in_array((string)$context->company_id, $cIds) || in_array((int)$context->company_id, $cIds);

                if (!$isGlobal && !$belongsToCompany) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
                }
            }
        }

        return response()->json(['status' => 'success', 'data' => $designation]);
    }

    // Dropdown API For Forms
    public function getDesignationsByDepartment(Request $request)
    {
        $deptId = $request->department_id;
        if (!$deptId) return response()->json(['status' => 'error', 'message' => 'Department ID is required']);

        $designations = Designation::where('department_id', $deptId)
            ->where('status', 'active')
            ->get(['id', 'designation_name', 'designation_code']);

        return response()->json(['status' => 'success', 'data' => $designations]);
    }

    // Dropdown for Active Designations (Context Aware)
    public function getActiveDesignations(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Designation::with('department')->where('status', 'active');

        if (!$context->is_god) {
            $companyId = $context->company_id;
            $query->whereHas('department', function ($q) use ($companyId) {
                $q->whereNull('company_ids')
                    ->orWhereJsonContains('company_ids', 'all')
                    ->orWhereJsonContains('company_ids', (string)$companyId)
                    ->orWhereJsonContains('company_ids', (int)$companyId);
            });
        }

        return response()->json(['status' => 'success', 'data' => $query->get()]);
    }
}
