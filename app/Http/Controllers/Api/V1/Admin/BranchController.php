<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    // 🔥 PRIVATE HELPER: Smart Code Generator Flow
    private function generateSmartBranchId($companyId, $state, $district, $openingDate)
    {
        $company = Company::find($companyId);
        $compCode = $company ? strtoupper($company->company_code) : 'COMP';

        // 1. Intelligent State Code Map
        $stateLower = strtolower(trim($state));
        $stateMap = [
            'bihar' => 'BIH',
            'uttar pradesh' => 'UP',
            'delhi' => 'DL',
            'jharkhand' => 'JHA',
            'west bengal' => 'WB'
        ];
        $stateCode = $stateMap[$stateLower] ?? strtoupper(substr($stateLower, 0, 3));

        // 2. Intelligent District Code Map
        $distLower = strtolower(trim($district));
        $distMap = [
            'madhubani' => 'MAD',
            'darbhanga' => 'DBJ',
            'gopalganj' => 'GOPJ',
            'saharsa' => 'SAH',
            'patna' => 'PAT'
        ];
        $distCode = $distMap[$distLower] ?? strtoupper(substr($distLower, 0, 3));

        // 3. Year Extraction
        $year = date('Y', strtotime($openingDate));

        // 4. Series Calculation (Count branches within same company + state + district)
        $count = Branch::where('company_id', $companyId)
            ->where('branch_state', $state)
            ->where('branch_district', $district)
            ->count();

        $series = sprintf('%02d', $count + 1);

        return "{$compCode}/{$stateCode}/{$distCode}/{$series}/{$year}";
    }

   public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        
        // DataTables ko company ka naam chahiye, isliye 'with' lagana zaroori hai
        $query = Branch::with('company')->latest();

        // 🛡️ ZERO-TRUST SCOPING
        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
        }

        $totalData = Branch::count();

        // 1. DYNAMIC SEARCH (Branch Name ya ID se search)
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('branch_name', 'LIKE', "%{$search}%")
                  ->orWhere('branch_id', 'LIKE', "%{$search}%");
            });
        }

        // 2. COMPANY FILTER (Agar future me branch ko company se filter karna ho)
        if ($request->filled('company_ids')) {
            $query->whereIn('company_id', explode(',', $request->company_ids));
        } elseif ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $totalFiltered = $query->count();

        // 3. PAGINATION
        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        return response()->json([
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data"            => $query->get()
        ]);
    }
    public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        $request->validate([
            'branch_name' => 'required|string|max:255',
            'branch_state' => 'required|string',
            'branch_code' => 'nullable|string|max:50',
            'branch_district' => 'required|string',
            'opening_date' => 'required|date'
        ]);

        $hasDirect = $context->is_god || $context->is_director;
        if (!$hasDirect && method_exists(auth()->user(), 'getAllPermissions')) {
            $userPerms = auth()->user()->getAllPermissions()->pluck('name')->toArray();
            if (in_array('branch_add_direct', $userPerms)) {
                $hasDirect = true;
            }
        }

        DB::beginTransaction();
        try {
            $finalCompanyId = $request->company_id ?: $context->company_id;

            // Generate Dynamic Smart Branch ID
            $smartBranchId = $this->generateSmartBranchId(
                $finalCompanyId,
                $request->branch_state,
                $request->branch_district,
                $request->opening_date
            );

            $mapData = $this->extractLatLng($request->map_url);

            Branch::create([
                'branch_id'       => $smartBranchId,
                'branch_name'     => $request->branch_name,
                'branch_code'     => $request->branch_code,
                'company_id'      => $finalCompanyId,
                'branch_status'   => $hasDirect ? 'active' : 'pending',
                'branch_state'    => $request->branch_state,
                'branch_district' => $request->branch_district,
                'opening_date'    => $request->opening_date,
                'branch_location' => $request->branch_location,
                'branch_map'      => $request->branch_map,
                // 🔥 NAYA: Database me fields save karein
            'map_url'       => $request->map_url,
            'latitude'      => $mapData['latitude'],
            'longitude'     => $mapData['longitude']
            ]);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => !$hasDirect ? 'Branch Requested Successfully! Sent for approval.' : 'Branch Saved Successfully!'
            ]);
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
        $request->validate([
            'branch_name' => 'required|string|max:255',
            'branch_code' => 'nullable|string|max:50',
            
            ]);

        DB::beginTransaction();
        try {
            $branch = Branch::findOrFail($id);

            if (!$context->is_god && $branch->company_id != $context->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }

            $hasDirect = $context->is_god || $context->is_director;
            if (!$hasDirect && method_exists(auth()->user(), 'getAllPermissions')) {
                $userPerms = auth()->user()->getAllPermissions()->pluck('name')->toArray();
                if (in_array('branch_add_direct', $userPerms)) $hasDirect = true;
            }

            $finalCompanyId = $request->company_id ?: $context->company_id;
            // 🔥 NAYA: Extract Location for Update
        $mapData = $this->extractLatLng($request->map_url);

            $branch->update([
                'branch_name'     => $request->branch_name,
                'branch_state'    => $request->branch_state,
                'branch_district' => $request->branch_district,
                'branch_code'     => $request->branch_code,
                'opening_date'    => $request->opening_date,
                'company_id'      => $finalCompanyId,
                'branch_status'   => $hasDirect ? ($request->branch_status ?? $branch->branch_status) : 'pending',
                'branch_location' => $request->branch_location,
                'branch_map'      => $request->branch_map,
                // 🔥 NAYA: Database me fields save karein
            'map_url'       => $request->map_url,
            'latitude'      => $mapData['latitude'],
            'longitude'     => $mapData['longitude']
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Branch Updated Successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 🟢 APPROVE METHOD
    public function approve($id)
    {
        $branch = Branch::findOrFail($id);
        $branch->update(['branch_status' => 'active']);
        return response()->json(['status' => 'success', 'message' => 'Branch Request Approved Successfully!']);
    }

    // 🔴 REJECT METHOD
    public function reject($id)
    {
        $branch = Branch::findOrFail($id);
        $branch->update(['branch_status' => 'inactive']);
        return response()->json(['status' => 'success', 'message' => 'Branch Request Rejected!']);
    }

    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);
        $branch->delete();
        return response()->json(['status' => 'success', 'message' => 'Branch Deleted!']);
    }

    public function bulkDelete(Request $request)
    {
        $context = $this->getGlobalContext();
        $ids = $request->ids;

        if (empty($ids)) return response()->json(['status' => 'error', 'message' => 'No branches selected!'], 400);

        DB::beginTransaction();
        try {
            $query = Branch::whereIn('id', $ids);
            if (!$context->is_god) $query->where('company_id', $context->company_id);

            $query->delete();
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Selected branches deleted successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to delete branches.'], 500);
        }
    }

    public function uiContext()
    {
        $context = $this->getGlobalContext();
        $permissions = \App\Http\Controllers\Controller::getLiveActivePermissions(auth()->user());
        $companyData = $context->company_id ? \App\Models\Company::find($context->company_id) : null;

        return response()->json([
            'is_god' => $context->is_god,
            'is_director' => $context->is_director,
            'company' => $companyData,
            'permissions' => $permissions
        ]);
    }


    // 🔥 NAYA: Google Map Link/Iframe se Lat-Lng extract karne ka function
    private function extractLatLng($mapString)
    {
        if (empty($mapString)) {
            return ['latitude' => null, 'longitude' => null];
        }

        if (strpos($mapString, '<iframe') !== false || strpos($mapString, 'pb=') !== false) {
            preg_match('/!3d([-0-9.]+)/', $mapString, $latMatch);
            preg_match('/![24]d([-0-9.]+)/', $mapString, $lngMatch); 

            if (isset($latMatch[1]) && isset($lngMatch[1])) {
                return ['latitude' => $latMatch[1], 'longitude' => $lngMatch[1]];
            }
        }

        if (preg_match('/@([-0-9.]+),([-0-9.]+)/', $mapString, $matches)) {
            return ['latitude' => $matches[1], 'longitude' => $matches[2]];
        }

        if (preg_match('/[?&]q=([-0-9.]+),([-0-9.]+)/', $mapString, $matches)) {
            return ['latitude' => $matches[1], 'longitude' => $matches[2]];
        }

        return ['latitude' => null, 'longitude' => null];
    }

  public function searchDynamic(Request $request)
    {
        $q = $request->q;
        $companyId = $request->company_id;
        if (strlen($q) < 3 || empty($companyId)) return response()->json(['status' => 'success', 'data' => []]);

        $context = $this->getGlobalContext();
        $query = \App\Models\Branch::where('branch_status', 'active')
            ->where('company_id', $companyId)
            ->where('branch_name', 'LIKE', "%{$q}%");

        // 🔥 Master HO Bypass Logic
        $isMasterHO = false;
        if ($context->is_employee && empty($context->branch_id) && !empty($context->company_id)) {
            $comp = \App\Models\Company::find($context->company_id);
            if ($comp && empty($comp->parent_id)) $isMasterHO = true;
        }

        if (!$context->is_god && !$context->is_director && !$isMasterHO && $context->company_id) {
            $query->where('company_id', $context->company_id);
        }

        $branches = $query->limit(20)->get(['id', 'branch_name', 'branch_id']);
        return response()->json(['status' => 'success', 'data' => $branches]);
    }

}
