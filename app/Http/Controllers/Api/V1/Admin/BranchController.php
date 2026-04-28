<?php
namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    // 1. GET: Saari branches dekhein
    public function index()
    {
        $branches = Branch::latest()->get();
        return response()->json(['status' => 'success', 'data' => $branches]);
    }

 // ==========================================
    // POST: Nayi branch add karein
    // ==========================================
   // ==========================================
    // POST: Nayi branch add karein
    // ==========================================
    public function store(Request $request)
    {
        $request->validate([
            'branch_name' => 'required',
            'branch_state' => 'required',
            'branch_district' => 'required',
            'opening_date' => 'required|date'
        ]);

        $cleanDistrict = trim($request->branch_district);

        // --- ID GENERATION LOGIC ---
        // 1. District Code (Darbhanga -> DAR) Uppercase me
        $districtCode = strtoupper(substr($cleanDistrict, 0, 3)); 
        
        // 2. Full Year Code (2024, 2025, etc.)
        $yearCode = date('Y', strtotime($request->opening_date)); 
        
        // 3. Sequence count: Sirf district check karenge, saal (year) ka filter hata diya
        $count = Branch::whereRaw('LOWER(branch_district) = ?', [strtolower($cleanDistrict)])
                       ->count() + 1; 
        
        // 4. Final ID: JV/BR/DAR1/2024
        $branchCustomId = "JV/BR/{$districtCode}{$count}/{$yearCode}";

        // --- SAVE TO DB ---
        $branch = Branch::create([
            'branch_id' => $branchCustomId,
            'branch_name' => $request->branch_name,
            'branch_state' => $request->branch_state,
            'branch_district' => $cleanDistrict,
            'opening_date' => $request->opening_date,
            'branch_location' => $request->branch_location,
            'branch_map' => $request->branch_map,
            'branch_status' => $request->branch_status ?? 'active',
        ]);

        return response()->json(['status' => 'success', 'data' => $branch]);
    }

    // 3. GET: Single branch dekhein
    public function show($id)
    {
        $branch = Branch::find($id);
        if (!$branch) return response()->json(['status' => 'error', 'message' => 'Branch not found'], 404);
        
        return response()->json(['status' => 'success', 'data' => $branch]);
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'branch_name' => 'required',
            'branch_state' => 'required',
            'branch_district' => 'required',
            'opening_date' => 'required|date'
        ]);

        $branch = Branch::findOrFail($id);
        
        // Update data. Hum branch_id update nahi karenge kyunki ID fixed rehni chahiye.
        $branch->update([
            'branch_name' => $request->branch_name,
            'branch_state' => $request->branch_state,
            'branch_district' => trim($request->branch_district),
            'opening_date' => $request->opening_date,
            'branch_location' => $request->branch_location,
            'branch_map' => $request->branch_map,
            'branch_status' => $request->branch_status,
        ]);

        return response()->json(['status' => 'success', 'data' => $branch]);
    }

    // 5. DELETE: Branch delete karein
    public function destroy($id)
    {
        $branch = Branch::find($id);
        if (!$branch) return response()->json(['status' => 'error', 'message' => 'Branch not found'], 404);

        $branch->delete();
        return response()->json(['status' => 'success', 'message' => 'Branch deleted successfully']);
    }
}