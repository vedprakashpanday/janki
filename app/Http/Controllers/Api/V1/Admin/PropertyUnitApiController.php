<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyUnit;
use App\Models\PropertyCharge;
use Illuminate\Http\Request;

class PropertyUnitApiController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = PropertyUnit::with(['company', 'branch', 'phase', 'category', 'area']);

        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if (!$context->is_director && $context->branch_id) $query->where('branch_id', $context->branch_id);
        }

        $totalData = $query->count();
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where('unit_number', 'LIKE', "%{$search}%");
        }
        $totalFiltered = $query->count();

        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        $data = $query->latest()->get()->map(function($unit) {
            // Charges JSON ko actual charge names me convert karna
            $chargeNames = [];
            if (!empty($unit->charge_ids)) {
                $chargeNames = PropertyCharge::whereIn('id', $unit->charge_ids)->pluck('charge_name')->toArray();
            }
            $unit->charge_names = $chargeNames;
            return $unit;
        });

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

        // 1. Updated Validation (Now includes map_coordinates and entity_type)
        $request->validate([
            'phase_id' => 'required|integer',
            'entity_type' => 'required|string',
            'unit_number' => 'required|string|max:255',
            'map_coordinates' => 'required|string', // JSON string from canvas
            'property_area_id' => 'nullable|integer',
            'boundaries' => 'nullable|array',
            'charge_ids' => 'nullable|array'
        ]);

        $phase = \App\Models\Phase::findOrFail($request->phase_id);

        // Security Check
        if (!$context->is_god) {
            if ($phase->company_id != $context->company_id) return response()->json(['success' => false, 'message' => 'Unauthorized Scope!'], 403);
            if (!$context->is_director && $context->branch_id && $phase->branch_id != $context->branch_id) return response()->json(['success' => false, 'message' => 'Unauthorized Scope!'], 403);
        }

        $hasDirect = $context->is_god || in_array('p_unit_add_direct', $context->permissions);
        $status = $hasDirect ? 'active' : 'pending';

        // 2. Decode the JSON string from JS into a PHP Array
        $mapCoords = json_decode($request->map_coordinates, true);

        // 3. Area, Category aur Type ID nikalna (Sirf Plots/Commercial ke liye)
        $propTypeId = null;
        $propCatId = null;
        if ($request->property_area_id) {
            $area = \App\Models\PropertyArea::with('category')->find($request->property_area_id);
            if ($area) {
                $propCatId = $area->property_category_id;
                $propTypeId = $area->category ? $area->category->property_type_id : null;
            }
        }

     // 4. Save to Database
        $unit = PropertyUnit::create([
            'company_id' => $phase->company_id,
            'branch_id' => $phase->branch_id,
            'phase_id' => $phase->id,
            'entity_type' => $request->entity_type,
            'property_type_id' => $propTypeId,
            'property_category_id' => $propCatId,
            'property_area_id' => $request->property_area_id,
            'unit_number' => $request->unit_number,
            'boundaries' => $request->boundaries,
            'charge_ids' => $request->charge_ids ?? [],
            'map_coordinates' => $mapCoords, 
            'status' => $status,
            'created_by' => $context->profile_id
        ]);

        // 🔥 FIX: Nayi ID wapas bhej rahe hain 🔥
        return response()->json([
            'success' => true, 
            'message' => 'Entity saved successfully on the map!',
            'unit_id' => $unit->id 
        ]);
    }

    // NAYA: Map Entity Update karne ke liye
    public function update(Request $request, $id)
    {
        $unit = PropertyUnit::findOrFail($id);
        
        $mapCoords = null;
        if ($request->has('map_coordinates')) {
            $mapCoords = json_decode($request->map_coordinates, true);
        }

        $propTypeId = null;
        $propCatId = null;
        if ($request->property_area_id) {
            $area = \App\Models\PropertyArea::with('category')->find($request->property_area_id);
            if ($area) {
                $propCatId = $area->property_category_id;
                $propTypeId = $area->category ? $area->category->property_type_id : null;
            }
        }

        $unit->update([
            'entity_type' => $request->entity_type,
            'property_type_id' => $propTypeId,
            'property_category_id' => $propCatId,
            'property_area_id' => $request->property_area_id,
            'unit_number' => $request->unit_number,
            'boundaries' => $request->boundaries,
            'charge_ids' => $request->charge_ids ?? [],
            'map_coordinates' => $mapCoords ?? $unit->map_coordinates,
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Entity updated successfully!',
            'unit_id' => $unit->id
        ]);
    }

   // NAYA: Map Entity Hamesha ke liye Delete karne ke liye
    public function destroy($id)
    {
        $unit = PropertyUnit::findOrFail($id);
        $unit->delete();
        return response()->json(['success' => true, 'message' => 'Entity permanently deleted!']);
    }
    public function bulkDelete(Request $request) { PropertyUnit::whereIn('id', $request->ids)->delete(); return response()->json(['success' => true, 'message' => 'Deleted successfully!']); }
    public function approve($id) { PropertyUnit::findOrFail($id)->update(['status' => 'active']); return response()->json(['success' => true, 'message' => 'Approved!']); }
    public function reject($id) { PropertyUnit::findOrFail($id)->update(['status' => 'inactive']); return response()->json(['success' => true, 'message' => 'Rejected!']); }

    // API to fetch charges based on phase (for dropdown)
   public function getPhaseCharges($phase_id)
{
    $charges = PropertyCharge::where('phase_id', $phase_id)->where('status', 'active')
        ->select('id', 'charge_name', 'charge_percentage')->get();
    return response()->json(['success' => true, 'data' => $charges]);
}
    // Print & Export Logic
    public function printPreview(Request $request)
    {
        if ($request->has('token')) { $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token); if ($token) auth()->setUser($token->tokenable); }
        $context = $this->getGlobalContext(); if (!$context) return abort(401, 'Unauthorized Access.');

        $query = PropertyUnit::with(['company', 'branch', 'phase', 'category', 'area']);
        if (!$context->is_god) { $query->where('company_id', $context->company_id); if (!$context->is_director && $context->branch_id) $query->where('branch_id', $context->branch_id); }

        $propertyUnits = $query->latest()->get();
        $company = $context->company_id ? \App\Models\Company::find($context->company_id) : null;
        $branch = $context->branch_id ? \App\Models\Branch::find($context->branch_id) : null;
        return view('admin.property_units.print', compact('propertyUnits', 'company', 'branch'));
    }

    public function exportExcel(Request $request)
    {
        if ($request->has('token')) { $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token); if ($token) auth()->setUser($token->tokenable); }
        $context = $this->getGlobalContext(); if (!$context) return abort(401, 'Unauthorized Access.');

        $query = PropertyUnit::with(['company', 'branch', 'phase', 'category', 'area']);
        if (!$context->is_god) { $query->where('company_id', $context->company_id); if (!$context->is_director && $context->branch_id) $query->where('branch_id', $context->branch_id); }

        $propertyUnits = $query->latest()->get();
        $fileName = 'property_units_' . date('Y-m-d_H-i') . '.csv';
        $headers = [ "Content-type" => "text/csv", "Content-Disposition" => "attachment; filename=$fileName", "Pragma" => "no-cache", "Cache-Control" => "must-revalidate", "Expires" => "0" ];

        $callback = function() use($propertyUnits) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Unit Number', 'Category', 'Area', 'East', 'West', 'North', 'South', 'Availability', 'Status']);
            foreach ($propertyUnits as $row) {
                fputcsv($file, [
                    $row->id, $row->unit_number, 
                    $row->category ? $row->category->category_name : 'N/A',
                    $row->area ? $row->area->area_name . ' ' . $row->area->measurement_unit : 'N/A',
                    $row->boundaries['east'] ?? '-', $row->boundaries['west'] ?? '-',
                    $row->boundaries['north'] ?? '-', $row->boundaries['south'] ?? '-',
                    strtoupper($row->availability_status), strtoupper($row->status)
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

   public function getPhaseMapData($phase_id)
    {
        $phase = \App\Models\Phase::find($phase_id);
        if (!$phase) return response()->json(['success' => false, 'message' => 'Phase not found']);

        if (!$phase->khatiyan_map) {
            return response()->json(['success' => false, 'message' => 'No Base Map uploaded for this Phase.']);
        }

        // FIX: Remove specific column restrictions and load relationships
        $units = PropertyUnit::with(['area.category'])->where('phase_id', $phase_id)->get();

        return response()->json([
            'success' => true,
            'map_url' => asset($phase->khatiyan_map) . '?v=' . time(), // Cache busting taaki hamesha fresh map load ho
            'units' => $units
        ]);
    }


    // API to fetch areas based on phase (for visual map builder)
    public function getPhaseAreas($phase_id)
    {
        $areas = \App\Models\PropertyArea::with('category')
            ->whereHas('category.propertyType', function($q) use ($phase_id) {
                $q->where('phase_id', $phase_id);
            })->get();

        $data = $areas->map(function($area) {
            $catName = $area->category ? $area->category->category_name : 'Unknown Category';
            return [
                'id' => $area->id,
                'name' => $catName . ' - ' . $area->area_name . ' ' . $area->measurement_unit
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }


}