<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::with('designations')->latest();

        // ==========================================
        // 🛡️ 1. DATA FILTER LOGIC (JSON Array Check)
        // ==========================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            // Employee ko wahi department dikhenge jo "Global (null/'all')" hain, ya unki company se jude hain
            $query->where(function ($q) use ($user) {
                $q->whereNull('company_ids')
                  ->orWhereJsonContains('company_ids', 'all')
                  ->orWhereJsonContains('company_ids', (string)$user->company_id)
                  ->orWhereJsonContains('company_ids', (int)$user->company_id);
            });
        }
        // ==========================================

        // 1. Total records count (Bina filter ke)
        $totalData = Department::count();

        // ==========================================
        // 🔥 NAYA: SERVER-SIDE SEARCH LOGIC 🔥
        // ==========================================
        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('department_name', 'LIKE', "%{$search}%")
                  ->orWhere('status', 'LIKE', "%{$search}%");
            });
        }

        // 2. Filtered count (Search hone ke baad kitne bache)
        $totalFiltered = $query->count(); 

        // 3. SERVER-SIDE PAGINATION (Limit & Offset)
        if ($request->has('length') && $request->input('length') != -1) {
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $query->offset($start)->limit($length);
        }

        $departments = $query->get();
        $companiesList = Company::pluck('company_name', 'id')->toArray();

        $data = $departments->map(function ($d) use ($companiesList) {
            $cIds = $d->company_ids ?? [];
            $isAllCompanies = empty($cIds) || in_array('all', $cIds);

            if ($isAllCompanies) {
                $d->company_name = 'All Companies (Global)';
            } else {
                $names = [];
                foreach ($cIds as $id) {
                    if (isset($companiesList[$id])) $names[] = $companiesList[$id];
                }
                $d->company_name = !empty($names) ? implode(', ', $names) : 'Unknown';
            }

            $d->designation_count = $d->designations->count();
            return $d;
        });

        // 4. DataTables format mein response bhejna
        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered, // Ab search kaam karega
            "data" => $data
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'department_name' => 'required|string|max:255',
            'company_ids'     => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // 1. Department Save
            $department = Department::create([
                'department_name' => $request->department_name,
                'company_ids'     => empty($request->company_ids) ? null : $request->company_ids,
                'status'          => $request->status ?? 'active',
            ]);

            // 2. Designations Save (Dynamic Rows)
            if ($request->has('designations')) {
                $designationsData = json_decode($request->designations, true);
                foreach ($designationsData as $desig) {
                    if (!empty($desig['name']) && !empty($desig['code'])) {
                        $department->designations()->create([
                            'designation_name' => $desig['name'],
                            'designation_code' => strtoupper($desig['code']),
                            'status'           => 'active'
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Department & Designations Saved Successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $department = Department::with('designations')->findOrFail($id);


        // 🛡️ OWNERSHIP & GLOBAL MODIFICATION CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            $cIds = $department->company_ids ?? [];
            $isGlobal = empty($cIds) || in_array('all', $cIds);
            
            // NOTE: Agar aap 'update' ya 'destroy' method mein ye paste kar rahe hain toh ye IF block rakhein. 
            // Agar 'show' mein paste kar rahe hain, toh is IF block ko hata dein kyunki employee Global dekh sakta hai.
            if ($isGlobal) {
                return response()->json(['status' => 'error', 'message' => 'Global Departments can only be modified by Master Admins.'], 403);
            }

            $belongsToCompany = in_array((string)$user->company_id, $cIds) || in_array((int)$user->company_id, $cIds);
            
            if (!$isGlobal && !$belongsToCompany) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! This department belongs to another company.'], 403);
            }
        }




        return response()->json(['status' => 'success', 'data' => $department]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'department_name' => 'required|string|max:255',
            'company_ids'     => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $department = Department::findOrFail($id);

            // 🛡️ OWNERSHIP & GLOBAL MODIFICATION CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            $cIds = $department->company_ids ?? [];
            $isGlobal = empty($cIds) || in_array('all', $cIds);
            
            // NOTE: Agar aap 'update' ya 'destroy' method mein ye paste kar rahe hain toh ye IF block rakhein. 
            // Agar 'show' mein paste kar rahe hain, toh is IF block ko hata dein kyunki employee Global dekh sakta hai.
            if ($isGlobal) {
                return response()->json(['status' => 'error', 'message' => 'Global Departments can only be modified by Master Admins.'], 403);
            }

            $belongsToCompany = in_array((string)$user->company_id, $cIds) || in_array((int)$user->company_id, $cIds);
            
            if (!$isGlobal && !$belongsToCompany) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! This department belongs to another company.'], 403);
            }
        }



            $department->update([
                'department_name' => $request->department_name,
                'company_ids'     => empty($request->company_ids) ? null : $request->company_ids,
                'status'          => $request->status ?? 'active',
            ]);

            // Designations Sync Logic
            if ($request->has('designations')) {
                $designationsData = json_decode($request->designations, true);
                $existingIds = [];

                foreach ($designationsData as $desig) {
                    if (!empty($desig['name']) && !empty($desig['code'])) {
                        if (isset($desig['id']) && $desig['id'] != '') {
                            // Purani Designation Update karein
                            $designation = Designation::find($desig['id']);
                            if ($designation) {
                                $designation->update([
                                    'designation_name' => $desig['name'],
                                    'designation_code' => strtoupper($desig['code'])
                                ]);
                                $existingIds[] = $designation->id;
                            }
                        } else {
                            // Nayi Designation Add karein
                            $newDesig = $department->designations()->create([
                                'designation_name' => $desig['name'],
                                'designation_code' => strtoupper($desig['code']),
                                'status'           => 'active'
                            ]);
                            $existingIds[] = $newDesig->id;
                        }
                    }
                }

                // Jo Designations form se hata di gayin hain unko database se bhi hata do
                $department->designations()->whereNotIn('id', $existingIds)->delete();
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Department Updated Successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        Department::findOrFail($id)->delete();

        // 🛡️ OWNERSHIP & GLOBAL MODIFICATION CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            $cIds = $department->company_ids ?? [];
            $isGlobal = empty($cIds) || in_array('all', $cIds);
            
            // NOTE: Agar aap 'update' ya 'destroy' method mein ye paste kar rahe hain toh ye IF block rakhein. 
            // Agar 'show' mein paste kar rahe hain, toh is IF block ko hata dein kyunki employee Global dekh sakta hai.
            if ($isGlobal) {
                return response()->json(['status' => 'error', 'message' => 'Global Departments can only be modified by Master Admins.'], 403);
            }

            $belongsToCompany = in_array((string)$user->company_id, $cIds) || in_array((int)$user->company_id, $cIds);
            
            if (!$isGlobal && !$belongsToCompany) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! This department belongs to another company.'], 403);
            }
        }


        return response()->json(['status' => 'success', 'message' => 'Department & associated Designations Deleted!']);
    }
    
    // Nayi API form load ke liye: Sirf active departments lane ke liye
   public function getActiveDepartments(Request $request)
    {
        $query = Department::with(['designations' => function($q) {
            $q->where('status', 'active');
        }])->where('status', 'active');
        
        $companyId = $request->company_id;

        // 🛡️ DROPDOWN FILTER LOGIC
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            $companyId = $user->company_id; // Overriding request to force own company
        }

        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->whereNull('company_ids')
                  ->orWhereJsonContains('company_ids', 'all')
                  ->orWhereJsonContains('company_ids', (string)$companyId)
                  ->orWhereJsonContains('company_ids', (int)$companyId);
            });
        }
        
        return response()->json(['status' => 'success', 'data' => $query->get()]);
    }


    // Specific Company ke active departments laane ke liye
    public function getDepartmentsByCompany(Request $request)
    {
        $companyId = $request->company_id;
        
        $query = Department::where('status', 'active');

        // 🛡️ DROPDOWN FILTER LOGIC
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            $companyId = $user->company_id; // Overriding request to force own company
        }

        if ($companyId) {
            $query->where(function($q) use ($companyId) {
                $q->whereNull('company_ids')
                  ->orWhereJsonContains('company_ids', 'all')
                  ->orWhereJsonContains('company_ids', (string)$companyId)
                  ->orWhereJsonContains('company_ids', (int)$companyId);
            });
        }

        return response()->json([
            'status' => 'success', 
            'data' => $query->get(['id', 'department_name'])
        ]);
    }



}