<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberDesignation;
use Illuminate\Http\Request;

class MemberDesignationController extends Controller
{
    public function index()
    {
        $designations = MemberDesignation::latest()->get();
        return response()->json(['status' => 'success', 'data' => $designations]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'designation_name' => 'required|unique:member_designations,designation_name',
            'commission_percentage' => 'nullable|numeric|min:0'
        ]);

        // Auto Generate Code (e.g., MDESIG-001)
        $count = MemberDesignation::count() + 1;
        $code = 'MDESIG-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        $designation = MemberDesignation::create([
            'designation_code' => $code,
            'designation_name' => $request->designation_name,
            'commission_percentage' => $request->commission_percentage ?? 0,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Member Designation Added!']);
    }

    public function show($id)
    {
        return response()->json(['status' => 'success', 'data' => MemberDesignation::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'designation_name' => 'required|unique:member_designations,designation_name,' . $id,
            'commission_percentage' => 'nullable|numeric|min:0'
        ]);

        $designation = MemberDesignation::findOrFail($id);
        $designation->update([
            'designation_name' => $request->designation_name,
            'commission_percentage' => $request->commission_percentage ?? 0,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Designation Updated!']);
    }

    public function destroy($id)
    {
        MemberDesignation::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted Successfully!']);
    }
}