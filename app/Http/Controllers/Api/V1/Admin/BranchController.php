<?php
namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



class BranchController extends Controller
{
   public function index()
    {
        $user = auth()->user();
        
        // Base query banayein (Abhi fetch nahi kiya hai)
        $query = Branch::with('company')->latest();

        // 🛡️ DATA FILTER LOGIC
        // Agar user Developer, CEO, ya Director NAHI hai, tabhi filter lagega
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            // Employee ko sirf uski company ki branches dikhengi
            $query->where('id', $user->branch_id);
            
            // Note: Agar aap chahte hain ki employee sirf apni khud ki 'ek' branch dekhe, 
            // toh upar wali line hata kar ye likhein: $query->where('id', $user->branch_id);
        }

        // Ab filtered data fetch karein
        $branches = $query->get();
        
        return response()->json(['status' => 'success', 'data' => $branches]);
    }

    // 2. STORE NEW BRANCH (MAGIC LIES HERE ✨)
    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id', // Company exist honi chahiye
            'branch_name' => 'required',
            'branch_state' => 'required',
            'branch_district' => 'required',
            'opening_date' => 'required|date'
        ]);

        $cleanState = trim($request->branch_state);
        $cleanDistrict = trim($request->branch_district);

        // --- ID GENERATION LOGIC ---
        
        // A. Company Prefix (e.g. ABD)
        $company = Company::find($request->company_id);
        $companyPrefix = $company->company_code; // Example: ABD

        // B. State & District Codes (Starting 3 letters, Uppercase)
        $stateCode = strtoupper(substr($cleanState, 0, 3));       // BIHAR -> BIH
        $districtCode = strtoupper(substr($cleanDistrict, 0, 3)); // DARBHANGA -> DAR
        
        // C. Year Code
        $yearCode = date('Y', strtotime($request->opening_date)); // 2025

        // D. Sequence counting specific to the Company AND District
        $count = Branch::where('company_id', $company->id)
                       ->whereRaw('LOWER(branch_district) = ?', [strtolower($cleanDistrict)])
                       ->count() + 1; 
        
        // Format as 01, 02, etc.
        $series = str_pad($count, 2, '0', STR_PAD_LEFT); 

        // 4. Final ID: ABD/BIH/DAR/01/2025
        $branchCustomId = "{$companyPrefix}/{$stateCode}/{$districtCode}/{$series}/{$yearCode}";

        // --- SAVE TO DB ---
        $branch = Branch::create([
            'company_id' => $request->company_id,
            'branch_id' => $branchCustomId,
            'branch_name' => $request->branch_name,
            'branch_state' => $cleanState,
            'branch_district' => $cleanDistrict,
            'opening_date' => $request->opening_date,
            'branch_location' => $request->branch_location,
            'branch_map' => $request->branch_map,
            'branch_status' => $request->branch_status ?? 'active',
        ]);

        return response()->json(['status' => 'success', 'data' => $branch]);
    }

  // 3. SHOW SINGLE
    public function show($id)
    {
        // with('company') add kiya taaki parent company ka data bhi aa jaye
        $branch = Branch::with('company')->find($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($branch->company_id != $user->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope'], 403);
            }
        }
        
        if (!$branch) return response()->json(['status' => 'error', 'message' => 'Branch not found'], 404);
        return response()->json(['status' => 'success', 'data' => $branch]);
    }

    
    // 4. UPDATE
    public function update(Request $request, $id)
    {
        $request->validate([
            'company_id' => 'required',
            'branch_name' => 'required',
            'branch_state' => 'required',
            'branch_district' => 'required',
            'opening_date' => 'required|date'
        ]);

        $branch = Branch::findOrFail($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($branch->company_id != $user->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope'], 403);
            }
        }
        
        // ID remains fixed, rest gets updated
        $branch->update([
            'company_id' => $request->company_id,
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

    // 5. DELETE
    public function destroy($id)
    {
        $branch = Branch::find($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($branch->company_id != $user->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope'], 403);
            }
        }
        if (!$branch) return response()->json(['status' => 'error', 'message' => 'Branch not found'], 404);

        $branch->delete();
        return response()->json(['status' => 'success', 'message' => 'Branch deleted successfully']);
    }
}