<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiteAllocationController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = SiteAllocation::with(['company', 'branch', 'employee'])->latest();

        // 🛡️ ZERO-TRUST SCOPING
        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if ($context->is_employee) {
                $query->where('employee_id', $context->profile_id); // Employee sirf apna allocation dekhega
            }
        }

        // Search Logic
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('member_id', 'LIKE', "%{$search}%");
            });
        }

        $totalData = SiteAllocation::count();
        $totalFiltered = $query->count();

        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        return response()->json([
            "draw"            => intval($request->input('draw', 0)),
            "recordsTotal"    => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data"            => $query->get()
        ]);
    }

   public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        
        $request->validate([
            'employee_ids' => 'required|array',
            'incharge_types' => 'required|array',
            'allowed_categories' => 'required|array',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
            // company_ids aur branch_ids aayenge depending on God mode
        ]);

        $userPerms = \App\Http\Controllers\Controller::getLiveActivePermissions(auth()->user());
        $hasDirect = $context->is_god || in_array('site_add_direct', $userPerms);
        
        $finalStatus = $hasDirect ? 'active' : 'pending';

        DB::beginTransaction();
        try {
            // Arrays generate karein based on God Mode
            $companyIds = $context->is_god && $request->has('company_ids') 
                            ? $request->company_ids 
                            : [$context->company_id];

            $branchIds = $request->branch_ids ?? ['HO'];

            // Loop inside loop inside loop for Multi-Multi-Multi allocation
            foreach ($companyIds as $compId) {
                foreach ($branchIds as $bId) {
                    $finalBranchId = ($bId === 'HO' || empty($bId)) ? null : $bId;

                    foreach ($request->employee_ids as $empId) {
                        SiteAllocation::updateOrCreate(
                            [
                                'company_id' => $compId,
                                'branch_id' => $finalBranchId,
                                'employee_id' => $empId,
                            ],
                            [
                                'start_date' => $request->start_date,
                                'end_date' => $request->end_date,
                                'incharge_types' => $request->incharge_types, 
                                'allowed_categories' => $request->allowed_categories,
                                'status' => $finalStatus
                            ]
                        );
                    }
                }
            }

            DB::commit();
            $msg = $hasDirect ? 'Site Incharge Assigned Successfully!' : 'Incharge Request Submitted for Approval!';
            return response()->json(['status' => 'success', 'message' => $msg]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    public function bulkDelete(Request $request)
    {
        $context = $this->getGlobalContext();
        $ids = $request->ids;

        if (empty($ids)) return response()->json(['status' => 'error', 'message' => 'No records selected!'], 400);

        $query = SiteAllocation::whereIn('id', $ids);
        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
        }

        $query->delete();
        return response()->json(['status' => 'success', 'message' => 'Allocations deleted successfully!']);
    }

    // 🔥 NAYA METHOD: Edit Modal me data bharne ke liye
    public function show($id)
    {
        $context = $this->getGlobalContext();
        $allocation = SiteAllocation::with(['company', 'branch', 'employee'])->findOrFail($id);
        
        // Security Check
        if (!$context->is_god && $allocation->company_id != $context->company_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }
        
        return response()->json(['status' => 'success', 'data' => $allocation]);
    }

    // 🔥 NAYA METHOD: Edit form ko save karne ke liye
    public function update(Request $request, $id)
    {
        $allocation = SiteAllocation::findOrFail($id);
        
        $request->validate([
            'incharge_types' => 'required|array',
            'allowed_categories' => 'required|array',
            'company_ids' => 'required|array',
            'branch_ids' => 'required|array',
            'employee_ids' => 'required|array',
        ]);

        $allocation->update([
            'company_id' => $request->company_ids[0], // Edit me single hi aayega
            'branch_id' => $request->branch_ids[0] === 'HO' ? null : $request->branch_ids[0],
            'employee_id' => $request->employee_ids[0],
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'incharge_types' => $request->incharge_types,
            'allowed_categories' => $request->allowed_categories,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Site Allocation Updated Successfully!']);
    }
    public function printPreview($id)
    {
        $context = $this->getGlobalContext();
        $allocation = SiteAllocation::with(['company', 'branch', 'employee'])->findOrFail($id);
        
        // Security: Apni company ke bahar ka letter print nahi kar sakte (God mode allowed)
        if (!$context->is_god && $allocation->company_id != $context->company_id) {
            abort(403, 'Unauthorized Access!');
        }

        return view('admin.site_allocations.print_allocation', compact('allocation'));
    }
}