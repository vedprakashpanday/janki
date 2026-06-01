<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function index(Request $request)
    {
        $query = Designation::query();

        // ========================================================
        // 🛡️ ZERO-TRUST RBAC SECURITY (Live Data)
        // ========================================================
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        // Agar God Mode (Developer) ya CEO/Director NAHI hai, tabhi strict filter lagega
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            
            $userCompanyId = (string)$user->company_id;
            $userBranchId = (string)$user->branch_id;

            // Employee sirf 'Global', apni company ki 'All Branches', ya 'Specific Branch' dekh sakta hai
            $query->where(function ($q) use ($userCompanyId, $userBranchId) {
                // 1. Global (Sabke liye)
                $q->whereJsonContains('company_ids', 'all')
                  // 2. Apni Company ke andar check
                  ->orWhere(function ($q2) use ($userCompanyId, $userBranchId) {
                      $q2->where(function ($q3) use ($userCompanyId) {
                          $q3->whereJsonContains('company_ids', $userCompanyId)
                             ->orWhereJsonContains('company_ids', (int)$userCompanyId);
                      })
                      // Branch 'all' ho ya specific uski branch ho
                      ->where(function ($q4) use ($userBranchId) {
                          $q4->whereJsonContains('branch_ids', 'all')
                             ->orWhereJsonContains('branch_ids', $userBranchId)
                             ->orWhereJsonContains('branch_ids', (int)$userBranchId);
                      });
                  });
            });
        }
        // ========================================================
        // 🔥 FRONTEND FILTERS 🔥
        // ========================================================
        if ($request->has('company_id') && $request->company_id != '') {
            $query->where(function ($q) use ($request) {
                $q->whereJsonContains('company_ids', 'all')
                    ->orWhereJsonContains('company_ids', (string)$request->company_id)
                    ->orWhereJsonContains('company_ids', (int)$request->company_id);
            });
        }
        if ($request->has('branch_id') && $request->branch_id != '') {
            $query->where(function ($q) use ($request) {
                $q->whereJsonContains('branch_ids', 'all')
                    ->orWhereJsonContains('branch_ids', (string)$request->branch_id)
                    ->orWhereJsonContains('branch_ids', (int)$request->branch_id);
            });
        }

        $designations = $query->latest()->get();

        $companiesList = Company::pluck('company_name', 'id')->toArray();
        $branchesList = Branch::pluck('branch_name', 'id')->toArray();

        // 7-LAYER HIERARCHY MAPPING
        $data = $designations->map(function ($d) use ($companiesList, $branchesList) {
            $cIds = $d->company_ids ?? [];
            $bIds = $d->branch_ids ?? [];

            $isCompanyEmpty = empty($cIds);
            $isCompanyAll   = in_array('all', $cIds);

            $isBranchEmpty  = empty($bIds);
            $isBranchAll    = in_array('all', $bIds);

            // Level Decision Logic (Aapke bataye hue rules)
            if ($isCompanyAll) {
                $d->level = 'Global (All Companies & Branches)';
                $d->company_name = 'All Companies';
                $d->branch_name = 'All Branches';
            } else {
                // Map Company Names
                if ($isCompanyEmpty) {
                    $d->company_name = 'Master Company (HQ)';
                } else {
                    $names = [];
                    foreach ($cIds as $id) {
                        if (isset($companiesList[$id])) $names[] = $companiesList[$id];
                    }
                    $d->company_name = !empty($names) ? implode(', ', $names) : '-';
                }

                // Map Branch Names & Levels
                if ($isCompanyEmpty && $isBranchEmpty) {
                    $d->level = 'Master Head Office';
                    $d->branch_name = 'Head Office Only';
                } elseif ($isCompanyEmpty && $isBranchAll) {
                    $d->level = 'Master All Branches';
                    $d->branch_name = 'All Master Branches';
                } elseif ($isCompanyEmpty && !$isBranchEmpty) {
                    $d->level = 'Master Specific Branch';
                    $names = [];
                    foreach ($bIds as $id) {
                        if (isset($branchesList[$id])) $names[] = $branchesList[$id];
                    }
                    $d->branch_name = implode(', ', $names);
                } elseif (!$isCompanyEmpty && $isBranchEmpty) {
                    $d->level = 'Company Head Office';
                    $d->branch_name = 'Head Office Only';
                } elseif (!$isCompanyEmpty && $isBranchAll) {
                    $d->level = 'Company All Branches';
                    $d->branch_name = 'All Branches of Company';
                } else {
                    $d->level = 'Company Specific Branch';
                    $names = [];
                    foreach ($bIds as $id) {
                        if (isset($branchesList[$id])) $names[] = $branchesList[$id];
                    }
                    $d->branch_name = implode(', ', $names);
                }
            }

            return $d;
        });

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'designation_code' => 'required|string|max:10|unique:designations,designation_code',
            'designation_name' => 'required|string|unique:designations,designation_name',
            'company_ids'      => 'nullable|array',
            'branch_ids'       => 'nullable|array',
        ]);

        $cIds = $request->company_ids ?: [];
        $bIds = $request->branch_ids ?: [];

        // 🔥 LOGIC: Agar Company mein 'Apply to All' hai, toh branch automatically 'All' ho jayega
        if (in_array('all', $cIds)) {
            $cIds = ['all'];
            $bIds = ['all'];
        }

        $designation = Designation::create([
            'designation_code' => strtoupper($request->designation_code),
            'designation_name' => $request->designation_name,
            'company_ids'      => empty($cIds) ? null : $cIds,
            'branch_ids'       => empty($bIds) ? null : $bIds,
            'status'           => $request->status ?? 'active',
        ]);

        return response()->json(['status' => 'success', 'data' => $designation, 'message' => 'Designation Created Successfully']);
    }

    public function show($id)
    {
        $designation = Designation::findOrFail($id);

        // 🛡️ OWNERSHIP & GLOBAL MODIFICATION CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            $cIds = $designation->company_ids ?? [];
            $isGlobal = empty($cIds) || in_array('all', $cIds);
            
            // NOTE: Agar 'show' method mein daal rahe hain, toh is IF block ko hata dein
            if ($isGlobal) {
                return response()->json(['status' => 'error', 'message' => 'Global Designations can only be modified by Master Admins.'], 403);
            }

            $belongsToCompany = in_array((string)$user->company_id, $cIds) || in_array((int)$user->company_id, $cIds);
            
            if (!$isGlobal && !$belongsToCompany) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! This designation belongs to another company.'], 403);
            }
        }



        return response()->json(['status' => 'success', 'data' => $designation]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'designation_code' => 'required|string|max:10|unique:designations,designation_code,' . $id,
            'designation_name' => 'required|string|unique:designations,designation_name,' . $id,
            'company_ids'      => 'nullable|array',
            'branch_ids'       => 'nullable|array',
        ]);

        $designation = Designation::findOrFail($id);

        // 🛡️ OWNERSHIP & GLOBAL MODIFICATION CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            $cIds = $designation->company_ids ?? [];
            $isGlobal = empty($cIds) || in_array('all', $cIds);
            
            // NOTE: Agar 'show' method mein daal rahe hain, toh is IF block ko hata dein
            if ($isGlobal) {
                return response()->json(['status' => 'error', 'message' => 'Global Designations can only be modified by Master Admins.'], 403);
            }

            $belongsToCompany = in_array((string)$user->company_id, $cIds) || in_array((int)$user->company_id, $cIds);
            
            if (!$isGlobal && !$belongsToCompany) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! This designation belongs to another company.'], 403);
            }
        }

        $cIds = $request->company_ids ?: [];
        $bIds = $request->branch_ids ?: [];

        // 🔥 LOGIC: Agar Company mein 'Apply to All' hai
        if (in_array('all', $cIds)) {
            $cIds = ['all'];
            $bIds = ['all'];
        }

        $designation->update([
            'designation_code' => strtoupper($request->designation_code),
            'designation_name' => $request->designation_name,
            'company_ids'      => empty($cIds) ? null : $cIds,
            'branch_ids'       => empty($bIds) ? null : $bIds,
            'status'           => $request->status ?? 'active',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Designation Updated Successfully']);
    }

    public function destroy($id)
    {
        Designation::findOrFail($id)->delete();

        // 🛡️ OWNERSHIP & GLOBAL MODIFICATION CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            $cIds = $designation->company_ids ?? [];
            $isGlobal = empty($cIds) || in_array('all', $cIds);
            
            // NOTE: Agar 'show' method mein daal rahe hain, toh is IF block ko hata dein
            if ($isGlobal) {
                return response()->json(['status' => 'error', 'message' => 'Global Designations can only be modified by Master Admins.'], 403);
            }

            $belongsToCompany = in_array((string)$user->company_id, $cIds) || in_array((int)$user->company_id, $cIds);
            
            if (!$isGlobal && !$belongsToCompany) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope! This designation belongs to another company.'], 403);
            }
        }


        return response()->json(['status' => 'success', 'message' => 'Designation Deleted']);
    }


    public function getDesignationsByDepartment(Request $request)
    {
        $departmentId = $request->department_id;
        $query = Designation::where('status', 'active')->where('department_id', $departmentId);

        // 🛡️ DROPDOWN FILTER LOGIC
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            $userCompanyId = (string)$user->company_id;
            
            $query->where(function ($q) use ($userCompanyId) {
                $q->whereJsonContains('company_ids', 'all')
                  ->orWhereJsonContains('company_ids', $userCompanyId)
                  ->orWhereJsonContains('company_ids', (int)$userCompanyId);
            });
        }

        $designations = $query->get(['id', 'designation_name', 'designation_code']);
        return response()->json(['status' => 'success', 'data' => $designations]);
    }


}
