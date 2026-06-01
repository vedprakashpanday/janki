<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolePermissionController extends Controller
{
    // 🔥 SAFE SECURITY CHECK (God Mode Upgraded) 🔥
    private function isMasterAdmin($user)
    {
        if (!$user) return false;
        
        // 1. Seedha Email Check (Master Developers)
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (in_array($user->email, $developerEmails)) return true;
        
        // 2. Safe Role Check
        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) return true;

        return false;
    }

    public function getUsers(Request $request)
    {
        try {
            $user = auth()->user();

            // Employee model se saara data without column restrictions laayenge taaki koi missing field error na ho
            $query = Employee::with(['roles', 'permissions']);

            if (!$this->isMasterAdmin($user)) {
                $query->where('company_id', $user->company_id);
            }

            $employees = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $employees
            ]);
        } catch (\Exception $e) {
            // Agar koi error aayega toh API 500 error me message bhejegi, silent fail nahi hogi
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getRolesAndPermissions()
    {
        $roles = Role::where('name', '!=', 'Super Admin')->get();

        $permissions = Permission::all()->groupBy(function ($data) {
            return explode('_', $data->name)[0];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'roles' => $roles,
                'permissions' => $permissions
            ]
        ]);
    }

    public function assignPowers(Request $request)
    {
        // 1. Validation ko 'user_id' se badalkar 'user_ids' (Array) kar diya gaya hai
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:adm_regist,id', 
            'roles' => 'nullable|array',
            'permissions' => 'nullable|array'
        ]);

        $currentUser = auth()->user();

        // ==========================================
        // 🛡️ STRICT ROLE ASSIGNMENT CHECK
        // ==========================================
        // Agar Master Admin nahi hai, aur CEO/Director bhi nahi hai, toh block kar do
        if (!$this->isMasterAdmin($currentUser) && (!$currentUser->hasRole(['CEO', 'Director']))) {
            return response()->json(['status' => 'error', 'message' => 'Strict Restriction: You do not have the authority to assign roles or powers.'], 403);
        }
        // ==========================================

        DB::beginTransaction();
        try {
            // 2. Ab array par loop chalega, taaki ek saath 10 employees ka data update ho sake
            foreach ($request->user_ids as $userId) {
                
                $targetUser = Employee::findOrFail($userId);

                // Scope Security Check: Kya admin limits cross kar raha hai?
                if (!$this->isMasterAdmin($currentUser) && $currentUser->company_id != $targetUser->company_id) {
                    throw new \Exception('Unauthorized Scope for Employee ID: ' . $userId);
                }

                // Roles Assign (purane hatenge, naye lagenge)
                if ($request->has('roles')) {
                    $targetUser->syncRoles($request->roles);
                } else {
                    $targetUser->syncRoles([]);
                }

                // Permissions Assign
                if ($request->has('permissions')) {
                    $targetUser->syncPermissions($request->permissions);
                } else {
                    $targetUser->syncPermissions([]);
                }

                // Time Limits Save
                $targetUser->access_start_date = $request->access_start_date;
                $targetUser->access_end_date = $request->access_end_date;
                $targetUser->daily_start_time = $request->daily_start_time;
                $targetUser->daily_end_time = $request->daily_end_time;
                
                $targetUser->save();
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Powers Assigned Successfully to all selected targets!']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
