<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SignatoryMasterApiController extends Controller
{
    // ==============================================================
    // 1. GET DEPARTMENTS (Fixed JSON Array Types & HO Null Logic)
    // ==============================================================
    public function getDepartments(Request $request)
    {
        $companyId = $request->company_id;
        $branchId = $request->branch_id; 

        $query = DB::table('departments')
            ->where('department_name', 'NOT LIKE', '%Associate%')
            ->where('status', 'active');

        // Company JSON Array Filter (Checking both Int and String)
        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->whereJsonContains('company_ids', (int)$companyId)
                  ->orWhereJsonContains('company_ids', (string)$companyId)
                  ->orWhereJsonContains('company_ids', 'all');
            });
        }

        // Branch Logic: Head Office (HO) is strictly NULL in DB
        if (empty($branchId) || $branchId === 'null' || $branchId === 'HO') {
            $query->where(function ($q) {
                $q->whereNull('branch_ids')
                  ->orWhereJsonContains('branch_ids', 'all')
                  ->orWhereJsonLength('branch_ids', 0); 
            });
        } else {
            // Specific Branch ID Filter (Checking both Int and String)
            $query->where(function ($q) use ($branchId) {
                $q->whereJsonContains('branch_ids', (int)$branchId)
                  ->orWhereJsonContains('branch_ids', (string)$branchId)
                  ->orWhereJsonContains('branch_ids', 'all');
            });
        }

        return response()->json(['status' => 'success', 'data' => $query->get(['id', 'department_name'])]);
    }
// ==============================================================
    // 2. GET PERSONS (Fixed emp_status for Employees)
    // ==============================================================
    public function getPersons(Request $request)
    {
        $companyId = $request->company_id;
        $branchId = $request->branch_id;
        $departmentId = $request->department_id;
        $types = $request->types; 
        $search = $request->q; 
        $baseGrade = $request->base_grade;

        if (empty($types) || !is_array($types)) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $queries = [];

        // A. EMPLOYEES
        if (in_array('employee', $types)) {
            $empQuery = DB::table('adm_regist')
                ->select('member_id as id', 'full_name as name', DB::raw("'employee' as person_type"), 'grade')
                ->where('emp_status', 'Active'); // 🔥 FIX: Changed 'status' to 'emp_status'
            
            if ($companyId) $empQuery->where('company_id', $companyId);
            
            if (empty($branchId) || $branchId === 'null' || $branchId === 'HO') {
                $empQuery->where(function($q) {
                    $q->whereNull('branch_id')->orWhere('branch_id', 0)->orWhere('branch_id', '');
                });
            } else {
                $empQuery->where('branch_id', $branchId);
            }
            
            if (!empty($departmentId)) $empQuery->where('department_id', $departmentId);
            
            // GRADE LOGIC (Grade A <= Grade C = True)
            if ($baseGrade) {
                $empQuery->whereNotNull('grade')->where('grade', '<=', $baseGrade); 
            }

            if ($search) $empQuery->where('full_name', 'LIKE', "%{$search}%");
            
            $queries[] = $empQuery;
        }

        // B. DIRECTORS
        if (in_array('director', $types)) {
            $dirQuery = DB::table('directors')
                ->select('director_id as id', 'full_name as name', DB::raw("'director' as person_type"), DB::raw("NULL as grade"))
                ->where('status', 'active');
            if ($search) $dirQuery->where('full_name', 'LIKE', "%{$search}%");
            $queries[] = $dirQuery;
        }

        // C. CEOS
        if (in_array('ceo', $types)) {
            $ceoQuery = DB::table('super_admins')
                ->select('ceo_id as id', 'full_name as name', DB::raw("'ceo' as person_type"), DB::raw("NULL as grade"))
                ->where('status', 'active');
            if ($search) $ceoQuery->where('full_name', 'LIKE', "%{$search}%");
            $queries[] = $ceoQuery;
        }

        $finalQuery = array_shift($queries); 
        foreach ($queries as $q) {
            $finalQuery->unionAll($q); 
        }

        $results = $finalQuery ? $finalQuery->limit(50)->get() : [];
        return response()->json(['status' => 'success', 'data' => $results]);
    }

 // ==============================================================
    // 3. SAVE HIERARCHY (Fixed 'HO' to NULL issue)
    // ==============================================================
    public function saveHierarchy(Request $request)
    {
        $request->validate([
            'module' => 'required|string',
            'company_id' => 'required',
            'base_role' => 'required|string',
            'base_person_id' => 'required|string',
            'targets' => 'required|array|min:1' 
        ]);

        // 🔥 FIX: 'HO' ko properly catch karke null banaya gaya hai
        $branchId = in_array($request->branch_id, ['HO', 'null', '', null], true) ? null : $request->branch_id;
        
        DB::beginTransaction();
        try {
            foreach ($request->targets as $target) {
                // Check if already mapped to prevent duplicate rows
                $exists = DB::table('voucher_signatory_hierarchies')
                    ->where('module', $request->module)
                    ->where('company_id', $request->company_id)
                    ->where('branch_id', $branchId)
                    ->where('base_role', $request->base_role)
                    ->where('base_person_id', $request->base_person_id)
                    ->where('target_role', $target['target_role'])
                    ->where('target_person_id', $target['target_person_id'])
                    ->exists();

                if (!$exists) {
                    DB::table('voucher_signatory_hierarchies')->insert([
                        'module' => $request->module,
                        'company_id' => $request->company_id,
                        'branch_id' => $branchId, // Ab yahan null jayega agar HO select hua to
                        'department_id' => $request->department_id ?? null,
                        'base_role' => $request->base_role,
                        'base_person_id' => $request->base_person_id,
                        'target_role' => $target['target_role'],
                        'target_person_type' => $target['target_person_type'],
                        'target_person_id' => $target['target_person_id'],
                        'created_by' => auth()->user()->id ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Hierarchy setup successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ==============================================================
    // 4. FETCH HIERARCHIES (For DataTable/List)
    // ==============================================================
    public function getHierarchies(Request $request)
    {
        $query = DB::table('voucher_signatory_hierarchies as vsh')
            ->leftJoin('companies as c', 'vsh.company_id', '=', 'c.id')
            ->leftJoin('branches as b', 'vsh.branch_id', '=', 'b.id')
            ->select(
                'vsh.*',
                'c.company_name',
                DB::raw("COALESCE(b.branch_name, 'Head Office') as branch_name")
            );

        if ($request->filled('module')) {
            $query->where('vsh.module', $request->module);
        }

        $data = $query->orderBy('vsh.id', 'desc')->get();

        // 🟢 Fetching Names for Base and Target dynamically
        $data->transform(function ($item) {
            $item->base_person_name = $this->getPersonName($item->base_person_id);
            $item->target_person_name = $this->getPersonName($item->target_person_id);
            return $item;
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    // ==============================================================
    // 5. DELETE HIERARCHY
    // ==============================================================
    public function deleteHierarchy($id)
    {
        DB::table('voucher_signatory_hierarchies')->where('id', $id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Mapping removed successfully!']);
    }

    // ==============================================================
    // 6. BULK DELETE HIERARCHIES
    // ==============================================================
    public function bulkDeleteHierarchies(Request $request)
    {
        $ids = $request->ids;
        if (is_array($ids) && count($ids) > 0) {
            DB::table('voucher_signatory_hierarchies')->whereIn('id', $ids)->delete();
            return response()->json(['status' => 'success', 'message' => count($ids) . ' mappings deleted successfully!']);
        }
        return response()->json(['status' => 'error', 'message' => 'No mappings selected.'], 400);
    }

    // Helper Function to get Person Name across all tables
    private function getPersonName($id)
    {
        $name = DB::table('adm_regist')->where('member_id', $id)->value('full_name');
        if ($name) return $name;

        $name = DB::table('directors')->where('director_id', $id)->value('full_name');
        if ($name) return $name;

        $name = DB::table('super_admins')->where('ceo_id', $id)->value('full_name');
        return $name ?: $id; // Return ID if name not found
    }
    // ==============================================================
    // 0. GET ALL BRANCHES FOR COMPANY (Bina 3-letter search ke)
    // ==============================================================
    public function getBranches(Request $request)
    {
        $companyId = $request->company_id;
        if (!$companyId) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $branches = DB::table('branches')
            ->where('company_id', $companyId)
            ->where('branch_status', 'active')
            ->get(['id', 'branch_name']);

        return response()->json(['status' => 'success', 'data' => $branches]);
    }
}