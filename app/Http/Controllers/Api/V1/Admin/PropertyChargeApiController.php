<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyCharge;
use Illuminate\Http\Request;

class PropertyChargeApiController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = PropertyCharge::with(['company', 'branch', 'phase']);

        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if (!$context->is_director && $context->branch_id) $query->where('branch_id', $context->branch_id);
        }

        $totalData = $query->count();
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('charge_name', 'LIKE', "%{$search}%");
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
            'charge_name' => 'required|string|max:255',
            'charge_percentage' => 'required|numeric|min:0|max:100'
        ]);

        $hasDirect = $context->is_god || in_array('p_charge_add_direct', $context->permissions);
        $status = $hasDirect ? 'active' : 'pending';

        $phases = \App\Models\Phase::whereIn('id', $request->phase_id)->get();
        $insertedCount = 0;

        foreach ($phases as $phase) {
            if (!$context->is_god) {
                if ($phase->company_id != $context->company_id) continue;
                if (!$context->is_director && $context->branch_id && $phase->branch_id != $context->branch_id) continue;
            }
            PropertyCharge::create([
                'company_id' => $phase->company_id,
                'branch_id' => $phase->branch_id,
                'phase_id' => $phase->id,
                'charge_name' => $request->charge_name,
                'charge_percentage' => $request->charge_percentage,
                'status' => $status,
                'created_by' => $context->profile_id
            ]);
            $insertedCount++;
        }

        if ($insertedCount === 0) return response()->json(['success' => false, 'message' => 'Unauthorized Scope or Invalid Phases!'], 403);
        return response()->json(['success' => true, 'message' => $hasDirect ? "Charge saved for {$insertedCount} phase(s)!" : 'Request submitted.']);
    }

    public function update(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $charge = PropertyCharge::findOrFail($id);
        
        if (!$context->is_god && $charge->company_id != $context->company_id) return response()->json(['success' => false, 'message' => 'Unauthorized Scope!'], 403);

        $request->validate([
            'charge_name' => 'required|string|max:255',
            'charge_percentage' => 'required|numeric|min:0|max:100'
        ]);
        
        $charge->update($request->only(['charge_name', 'charge_percentage']));
        return response()->json(['success' => true, 'message' => 'Updated successfully.']);
    }

    public function destroy($id) { PropertyCharge::findOrFail($id)->delete(); return response()->json(['success' => true, 'message' => 'Deleted successfully.']); }
    public function bulkDelete(Request $request) { PropertyCharge::whereIn('id', $request->ids)->delete(); return response()->json(['success' => true, 'message' => 'Deleted successfully!']); }
    public function approve($id) { PropertyCharge::findOrFail($id)->update(['status' => 'active']); return response()->json(['success' => true, 'message' => 'Approved!']); }
    public function reject($id) { PropertyCharge::findOrFail($id)->update(['status' => 'inactive']); return response()->json(['success' => true, 'message' => 'Rejected!']); }

    public function printPreview(Request $request)
    {
        if ($request->has('token')) { $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token); if ($token) auth()->setUser($token->tokenable); }
        $context = $this->getGlobalContext(); if (!$context) return abort(401, 'Unauthorized Access.');

        $query = PropertyCharge::with(['company', 'branch', 'phase']);
        if (!$context->is_god) { $query->where('company_id', $context->company_id); if (!$context->is_director && $context->branch_id) $query->where('branch_id', $context->branch_id); }

        $propertyCharges = $query->latest()->get();
        $company = $context->company_id ? \App\Models\Company::find($context->company_id) : null;
        $branch = $context->branch_id ? \App\Models\Branch::find($context->branch_id) : null;
        return view('admin.property_charges.print', compact('propertyCharges', 'company', 'branch'));
    }

    public function exportExcel(Request $request)
    {
        if ($request->has('token')) { $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token); if ($token) auth()->setUser($token->tokenable); }
        $context = $this->getGlobalContext(); if (!$context) return abort(401, 'Unauthorized Access.');

        $query = PropertyCharge::with(['company', 'branch', 'phase']);
        if (!$context->is_god) { $query->where('company_id', $context->company_id); if (!$context->is_director && $context->branch_id) $query->where('branch_id', $context->branch_id); }

        $propertyCharges = $query->latest()->get();
        $fileName = 'property_charges_' . date('Y-m-d_H-i') . '.csv';
        $headers = [ "Content-type" => "text/csv", "Content-Disposition" => "attachment; filename=$fileName", "Pragma" => "no-cache", "Cache-Control" => "must-revalidate", "Expires" => "0" ];

        $callback = function() use($propertyCharges) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Charge Name', 'Rate/SqFt', 'Phase', 'Company', 'Branch', 'Status']);
            foreach ($propertyCharges as $row) {
                fputcsv($file, [
                    $row->id, $row->charge_name, $row->charge_percentage,
                    $row->phase ? $row->phase->phase_name : 'N/A', $row->company ? $row->company->company_name : 'N/A',
                    $row->branch ? $row->branch->branch_name : 'HO', strtoupper($row->status)
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}