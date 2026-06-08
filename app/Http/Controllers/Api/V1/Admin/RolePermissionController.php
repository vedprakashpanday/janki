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

        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (in_array($user->email, $developerEmails)) return true;

        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) return true;

        return false;
    }

    public function getUsers(Request $request)
    {
        try {
            $user = auth()->user();
            $today = date('Y-m-d');
            $time = date('H:i:s'); // 🔥 NAYA: Current Time bhi nikal liya

            $query = Employee::with([
                'roles' => function($q) use ($today, $time) {
                    // DATE Check
                    $q->where(function($query) use ($today) {
                        $query->whereNull('model_has_roles.access_end_date')
                              ->orWhere('model_has_roles.access_end_date', '>=', $today);
                    })
                    // 🔥 TIME Check (Agar daily time diya hai, toh usko bhi check karega)
                    ->where(function($query) use ($time) {
                        $query->whereNull('model_has_roles.daily_end_time')
                              ->orWhere('model_has_roles.daily_end_time', '>=', $time);
                    });
                },
                'permissions' => function($q) use ($today, $time) {
                    // DATE Check
                    $q->where(function($query) use ($today) {
                        $query->whereNull('model_has_permissions.access_end_date')
                              ->orWhere('model_has_permissions.access_end_date', '>=', $today);
                    })
                    // 🔥 TIME Check (1:50 PM ke baad Admin screen se hide kar dega)
                    ->where(function($query) use ($time) {
                        $query->whereNull('model_has_permissions.daily_end_time')
                              ->orWhere('model_has_permissions.daily_end_time', '>=', $time);
                    });
                }
            ]);

            // Scope Filtering
            if (!$this->isMasterAdmin($user) && !$user->hasRole(['CEO', 'Director'])) {
                $query->where('company_id', $user->company_id)
                      ->where('branch_id', $user->branch_id)
                      ->where('department_id', $user->department_id); 
            } elseif (!$this->isMasterAdmin($user)) {
                $query->where('company_id', $user->company_id);
            }

            return response()->json(['status' => 'success', 'data' => $query->get()]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function buildModulePermissionTree($parentId, $allModules, $allPermissions)
    {
        $tree = [];
        $children = $allModules->where('parent_id', $parentId)->sortBy('sequence');

        foreach ($children as $child) {
            $modulePerms = [];
            if (!empty($child->permission_base)) {
                $modulePerms = $allPermissions->filter(function ($p) use ($child) {
                    return str_starts_with($p->name, $child->permission_base . '_');
                })->values();
            }

            $tree[] = [
                'id' => $child->id,
                'module_name' => $child->module_name,
                'permission_base' => $child->permission_base,
                'permissions' => $modulePerms,
                'children' => $this->buildModulePermissionTree($child->id, $allModules, $allPermissions)
            ];
        }

        return $tree;
    }

    public function getRolesAndPermissions()
    {
        $roles = Role::where('name', '!=', 'Super Admin')->get();
        $allModules = \App\Models\Module::where('status', 'active')->get();
        $allPermissions = Permission::all();

        $moduleTree = $this->buildModulePermissionTree(null, $allModules, $allPermissions);

        return response()->json([
            'status' => 'success',
            'data' => [
                'roles' => $roles,
                'module_tree' => $moduleTree
            ]
        ]);
    }


    // ==========================================
    // 🔥 ADVANCED PIVOT ASSIGNMENT LOGIC 🔥
    // ==========================================
    public function assignPowers(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'roles' => 'nullable|array',
            'permissions' => 'nullable|array',
            'model_type' => 'nullable|string'
        ]);

        $currentUser = auth()->user();
        $modelClass = $request->model_type ?? \App\Models\Employee::class;

        DB::beginTransaction();
        try {
            foreach ($request->user_ids as $userId) {
                $targetUser = $modelClass::findOrFail($userId);
                $mode = $request->mode ?? 'append';

                if ($modelClass === \App\Models\Employee::class) {
                    if (!$this->isMasterAdmin($currentUser) && !$currentUser->hasRole(['CEO', 'Director'])) {
                        if (
                            $currentUser->company_id != $targetUser->company_id ||
                            $currentUser->branch_id != $targetUser->branch_id ||
                            $currentUser->department_id != $targetUser->department_id
                        ) {
                            throw new \Exception('Scope Restriction: You cannot assign powers outside your branch/department.');
                        }
                    }
                }

                // Agar 'Sync' mode hai, toh pehle wali sab clear karo
                if ($mode === 'sync') {
                    DB::table('model_has_roles')->where('model_id', $targetUser->id)->where('model_type', $modelClass)->delete();
                    DB::table('model_has_permissions')->where('model_id', $targetUser->id)->where('model_type', $modelClass)->delete();
                }

                // ROLES ASSIGN
                if ($request->has('roles') && !empty($request->roles)) {
                    $roles = Role::whereIn('name', $request->roles)->get();
                    foreach ($roles as $role) {
                        DB::table('model_has_roles')->updateOrInsert(
                            ['role_id' => $role->id, 'model_id' => $targetUser->id, 'model_type' => $modelClass],
                            [
                                'access_start_date' => $request->access_start_date,
                                'access_end_date'   => $request->access_end_date,
                                'daily_start_time'  => $request->daily_start_time,
                                'daily_end_time'    => $request->daily_end_time,
                            ]
                        );
                    }
                }

                // PERMISSIONS ASSIGN
                if ($request->has('permissions') && !empty($request->permissions)) {
                    $permissions = Permission::whereIn('name', $request->permissions)->get();
                    foreach ($permissions as $perm) {
                        DB::table('model_has_permissions')->updateOrInsert(
                            ['permission_id' => $perm->id, 'model_id' => $targetUser->id, 'model_type' => $modelClass],
                            [
                                'access_start_date' => $request->access_start_date,
                                'access_end_date'   => $request->access_end_date,
                                'daily_start_time'  => $request->daily_start_time,
                                'daily_end_time'    => $request->daily_end_time,
                            ]
                        );
                    }
                }
            }

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Powers with Time Matrices Assigned Successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }


    // ==========================================
    // 🔥 CLEAR USER POWERS (UPDATED) 🔥
    // ==========================================
    public function clearUserPowers(Request $request)
    {
        $request->validate(['user_id' => 'required']);
        $currentUser = auth()->user();
        $targetUser = Employee::findOrFail($request->user_id);
        $modelClass = \App\Models\Employee::class;

        if (!$this->isMasterAdmin($currentUser) && $currentUser->company_id != $targetUser->company_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
        }

        // Sirf Spatie Pivot tables ko khali karna hai
        DB::table('model_has_roles')->where('model_id', $targetUser->id)->where('model_type', $modelClass)->delete();
        DB::table('model_has_permissions')->where('model_id', $targetUser->id)->where('model_type', $modelClass)->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json(['status' => 'success', 'message' => 'User powers cleared completely.']);
    }

    public function revokeSinglePermission(Request $request)
    {
        $request->validate(['user_id' => 'required', 'permission' => 'required']);
        $currentUser = auth()->user();
        $targetUser = Employee::findOrFail($request->user_id);

        if (!$this->isMasterAdmin($currentUser) && $currentUser->company_id != $targetUser->company_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
        }

        $targetUser->revokePermissionTo($request->permission);
        return response()->json(['status' => 'success', 'message' => 'Permission revoked.']);
    }

    public function revokeModulePermissions(Request $request)
    {
        $request->validate(['user_id' => 'required', 'module_prefix' => 'required']);
        $currentUser = auth()->user();
        $targetUser = Employee::findOrFail($request->user_id);

        if (!$this->isMasterAdmin($currentUser) && !$currentUser->hasRole(['CEO', 'Director'])) {
            if ($currentUser->branch_id != $targetUser->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        $prefix = $request->module_prefix;
        $userPerms = $targetUser->permissions()->pluck('name');

        $permsToRemove = $userPerms->filter(function ($name) use ($prefix) {
            return str_starts_with($name, $prefix . '_');
        });

        if ($permsToRemove->count() > 0) {
            $targetUser->revokePermissionTo($permsToRemove->toArray());
        }

        return response()->json(['status' => 'success', 'message' => 'Module permissions cleared.']);
    }
}
