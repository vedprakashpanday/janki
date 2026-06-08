<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Branch::with('company')->latest();

        // 🛡️ ZERO-TRUST SCOPING
        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            // Agar normal employee hai, toh usko sirf apni branch dikhe (agar business logic allow kare to)
            // if ($context->is_employee) $query->where('id', $context->branch_id); 
        }

        $totalData = Branch::count();

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('branch_name', 'LIKE', "%{$search}%")
                    ->orWhere('branch_code', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('company_ids')) {
    $query->whereIn('company_id', explode(',', $request->company_ids));
} elseif ($request->filled('company_id')) {
    $query->where('company_id', $request->company_id);
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

    public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        $request->validate(['branch_name' => 'required|string|max:255']);

        // Check Add Power
        $hasDirect = $context->is_god || $context->is_director;
        if (!$hasDirect && method_exists(auth()->user(), 'getAllPermissions')) {
            if (in_array('branch_master_add_direct', auth()->user()->getAllPermissions()->pluck('name')->toArray())) $hasDirect = true;
        }

        DB::beginTransaction();
        try {
            $finalCompanyId = $request->company_id;
            if (!$context->is_god) $finalCompanyId = $context->company_id; // Lock company

            Branch::create([
                'branch_name'   => $request->branch_name,
                'branch_code'   => $request->branch_code,
                'company_id'    => $finalCompanyId,
                'branch_status' => $hasDirect ? ($request->branch_status ?? 'active') : 'pending',
                'branch_address' => $request->branch_address,
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => !$hasDirect ? 'Branch Requested!' : 'Branch Saved Successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $context = $this->getGlobalContext();
        $branch = Branch::with('company')->findOrFail($id);

        if (!$context->is_god && $branch->company_id != $context->company_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
        }

        return response()->json(['status' => 'success', 'data' => $branch]);
    }

    public function update(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $request->validate(['branch_name' => 'required|string|max:255']);

        DB::beginTransaction();
        try {
            $branch = Branch::findOrFail($id);

            if (!$context->is_god && $branch->company_id != $context->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }

            $hasDirect = $context->is_god || $context->is_director;
            if (!$hasDirect && method_exists(auth()->user(), 'getAllPermissions')) {
                if (in_array('branch_master_edit_direct', auth()->user()->getAllPermissions()->pluck('name')->toArray())) $hasDirect = true;
            }

            $finalCompanyId = $request->company_id;
            if (!$context->is_god) $finalCompanyId = $context->company_id;

            $branch->update([
                'branch_name'   => $request->branch_name,
                'branch_code'   => $request->branch_code,
                'company_id'    => $finalCompanyId,
                'branch_status' => $hasDirect ? ($request->branch_status ?? 'active') : 'pending',
                'branch_address' => $request->branch_address,
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Branch Updated Successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $context = $this->getGlobalContext();
        $branch = Branch::findOrFail($id);

        if (!$context->is_god && $branch->company_id != $context->company_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
        }

        $branch->delete();
        return response()->json(['status' => 'success', 'message' => 'Branch Deleted!']);
    }
}
