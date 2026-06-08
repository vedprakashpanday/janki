<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberDesignation;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberDesignationController extends Controller
{
    // ==========================================
    // 🔥 HELPER: CHECK PERMISSION INTERNALLY
    // ==========================================
    private function checkPermission($action)
    {
        $user = auth()->user();
        if (!$user) return false;

        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (in_array($user->email, $developerEmails) || (method_exists($user, 'hasRole') && $user->hasRole('Super Admin'))) {
            return true;
        }

        // Fetching ACTIVE (Live) permissions from our Base Controller
        $livePerms = self::getLiveActivePermissions($user);

        // Handling direct vs request addition based on your setup
        if ($action === 'add') {
            return in_array("member_designation_add", $livePerms) ||
                in_array("member_designation_add_direct", $livePerms) ||
                in_array("member_designation_add_request", $livePerms);
        }

        return in_array("member_designation_{$action}", $livePerms);
    }


    // ==========================================
    // 1. GET (INDEX)
    // ==========================================
    public function index(Request $request)
    {
        if (!$this->checkPermission('view') && !$this->checkPermission('add')) {
            // View ya Add ki permission nahi toh data nahi denge (add mode wale form me dropdowns lagte hain isliye)
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access!'], 403);
        }

        $query = MemberDesignation::with('branch.company');

        // 🛡️ DATA FILTER LOGIC
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($request->has('branch_id') && $request->branch_id != '') {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('designation_code', 'LIKE', "%{$search}%")
                    ->orWhere('designation_name', 'LIKE', "%{$search}%");
            });
        }

        $totalData = MemberDesignation::count();
        $totalFiltered = $query->count();

        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        if ($length != -1) $query->offset($start)->limit($length);

        $designations = $query->orderBy('id', 'desc')->get();

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $designations
        ]);
    }

    // ==========================================
    // 2. STORE
    // ==========================================
    public function store(Request $request)
    {
        if (!$this->checkPermission('add')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized! You do not have permission to add designations.'], 403);
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'designation_code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('member_designations')->where('branch_id', $request->branch_id)
            ],
            'designation_name' => [
                'required',
                Rule::unique('member_designations')->where('branch_id', $request->branch_id)
            ],
            'commission_percentage' => 'nullable|numeric|min:0'
        ]);

        $branch = Branch::findOrFail($request->branch_id);

        // 🛡️ STORE OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];

        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($branch->id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized! You can only add designations to your own branch.'], 403);
            }
        }

        MemberDesignation::create([
            'company_id' => $branch->company_id,
            'branch_id' => $request->branch_id,
            'designation_code' => strtoupper($request->designation_code),
            'designation_name' => $request->designation_name,
            'commission_percentage' => $request->commission_percentage ?? 0,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Member Designation Added!']);
    }

    // ==========================================
    // 3. SHOW
    // ==========================================
    public function show($id)
    {
        if (!$this->checkPermission('edit') && !$this->checkPermission('view')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access!'], 403);
        }

        $designation = MemberDesignation::with('branch.company')->findOrFail($id);

        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($designation->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        return response()->json(['status' => 'success', 'data' => $designation]);
    }

    // ==========================================
    // 4. UPDATE
    // ==========================================
    public function update(Request $request, $id)
    {
        if (!$this->checkPermission('edit')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized! Your edit permission may have expired.'], 403);
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'designation_code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('member_designations')->where('branch_id', $request->branch_id)->ignore($id)
            ],
            'designation_name' => [
                'required',
                Rule::unique('member_designations')->where('branch_id', $request->branch_id)->ignore($id)
            ],
            'commission_percentage' => 'nullable|numeric|min:0'
        ]);

        $branch = Branch::findOrFail($request->branch_id);
        $designation = MemberDesignation::findOrFail($id);

        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($designation->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You cannot modify data of another branch.'], 403);
            }
        }

        $designation->update([
            'company_id' => $branch->company_id,
            'branch_id' => $request->branch_id,
            'designation_code' => strtoupper($request->designation_code),
            'designation_name' => $request->designation_name,
            'commission_percentage' => $request->commission_percentage ?? 0,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Designation Updated!']);
    }

    // ==========================================
    // 5. DESTROY
    // ==========================================
    public function destroy($id)
    {
        if (!$this->checkPermission('delete')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized! Your delete permission may have expired.'], 403);
        }

        $designation = MemberDesignation::findOrFail($id);

        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($designation->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! You cannot modify data of another branch.'], 403);
            }
        }

        $designation->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted Successfully!']);
    }
}
