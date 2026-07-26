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

    // =======================================================
    // 🔥 ROLE MANAGER CASCADING DROPDOWN APIS (ZERO-TRUST) 🔥
    // =======================================================

  // 1. Get Companies (Context Aware + ACTIVE ONLY)
    public function getCompanies(Request $request)
    {
        $user = auth()->user();
        // Agar Company me status column 'status' hai, toh usko active karenge
        $query = \App\Models\Company::where('status', 'active'); 

        if (!$this->isMasterAdmin($user)) {
            $query->where('id', $user->company_id);
        }
        if ($request->has('q')) {
            $query->where('company_name', 'LIKE', '%' . $request->q . '%');
        }
        $companies = $query->select('id', 'company_name as text')->limit(20)->get();
        return response()->json(['results' => $companies]);
    }

    // 2. Get Branches (Context Aware + Filtered + HO Injector + ACTIVE ONLY)
    public function getBranches(Request $request)
    {
        $user = auth()->user();
        // Strict Active Branch Check
        $query = \App\Models\Branch::where('branch_status', 'active');

        if (!$this->isMasterAdmin($user)) {
            if ($user->hasRole(['CEO', 'Company Director'])) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->where('id', $user->branch_id);
            }
        }

        if ($request->filled('company_ids')) {
            $query->whereIn('company_id', $request->company_ids);
        }
        if ($request->has('q')) {
            $query->where('branch_name', 'LIKE', '%' . $request->q . '%');
        }

        $branches = $query->select('id', 'branch_name as text')->limit(50)->get()->toArray();

        // HO Injector
        if ($this->isMasterAdmin($user) || $user->hasRole(['CEO', 'Company Director']) || empty($user->branch_id)) {
            if (!$request->filled('q') || stripos('head office', $request->q) !== false) {
                array_unshift($branches, ['id' => 'HO', 'text' => '🏢 Head Office (No Branch)']);
            }
        }
        return response()->json(['results' => $branches]);
    }

    // 3. Get Departments (JSON Logic + Associate Filter + HO Handling + ACTIVE ONLY)
    public function getDepartments(Request $request)
    {
        $user = auth()->user();
        // Strict Active Department
        $query = \App\Models\Department::where('status', 'active');

        if (!$this->isMasterAdmin($user)) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('company_ids')
                  ->orWhereJsonContains('company_ids', 'all')
                  ->orWhereJsonContains('company_ids', (string)$user->company_id)
                  ->orWhereJsonContains('company_ids', (int)$user->company_id);
            });
        }

        if ($request->filled('company_ids')) {
            $query->where(function ($q) use ($request) {
                $q->whereJsonContains('company_ids', 'all');
                foreach ($request->company_ids as $cId) {
                    $q->orWhereJsonContains('company_ids', (string)$cId)->orWhereJsonContains('company_ids', (int)$cId);
                }
            });
        }

        if ($request->filled('branch_ids')) {
            $branchIds = $request->branch_ids;
            $hasHO = in_array('HO', $branchIds);
            $query->where(function ($q) use ($branchIds, $hasHO) {
                $q->whereJsonContains('branch_ids', 'all');
                if ($hasHO) {
                    $q->orWhereNull('branch_ids')->orWhereJsonContains('branch_ids', 'HO');
                }
                foreach ($branchIds as $bId) {
                    if ($bId !== 'HO') {
                        $q->orWhereJsonContains('branch_ids', (string)$bId)->orWhereJsonContains('branch_ids', (int)$bId);
                    }
                }
            });
        }

        if ($request->type === 'employee') {
            $query->where('department_name', 'NOT LIKE', '%associate%');
        } elseif ($request->type === 'member') {
            $query->where('department_name', 'LIKE', '%associate%');
        }
        if ($request->has('q')) {
            $query->where('department_name', 'LIKE', '%' . $request->q . '%');
        }
        $departments = $query->select('id', 'department_name as text')->limit(50)->get();
        return response()->json(['results' => $departments]);
    }

    // 4. Get Designations (Filtered by Departments + ACTIVE ONLY)
    public function getDesignations(Request $request)
    {
        // Strict Active Designation
        $query = \App\Models\Designation::where('status', 'active');
        
        if ($request->filled('department_ids')) {
            $query->whereIn('department_id', $request->department_ids);
        }
        if ($request->has('q')) {
            $query->where('designation_name', 'LIKE', '%' . $request->q . '%');
        }
        $designations = $query->select('id', 'designation_name as text')->limit(50)->get();
        return response()->json(['results' => $designations]);
    }

    // 5. Get Target Users (Employees or Members - Fully Scoped + ACTIVE ONLY)
    public function getTargetUsers(Request $request)
    {
        $user = auth()->user();
        if ($request->type === 'employee') {
            // Strict Active Employee
            $query = \App\Models\Employee::where('emp_status', 'active');
            $nameField = 'full_name';
        } else {
            // Strict Active Member
            $query = \App\Models\Member::where('status', 'active');
            $nameField = 'member_name';
        }

        if (!$this->isMasterAdmin($user)) {
            if ($user->hasRole(['CEO', 'Company Director'])) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->where('branch_id', $user->branch_id);
            }
        }

        if ($request->filled('company_ids')) { $query->whereIn('company_id', $request->company_ids); }
        if ($request->filled('department_ids')) { $query->whereIn('department_id', $request->department_ids); }
        if ($request->filled('designation_ids')) { $query->whereIn('designation_id', $request->designation_ids); }

        if ($request->filled('branch_ids')) {
            $branchIds = $request->branch_ids;
            $hasHO = in_array('HO', $branchIds);
            $query->where(function($q) use ($branchIds, $hasHO) {
                if ($hasHO) { $q->whereNull('branch_id')->orWhere('branch_id', 0); }
                $normalBranches = array_filter($branchIds, function($val) { return $val !== 'HO'; });
                if (!empty($normalBranches)) { $q->orWhereIn('branch_id', $normalBranches); }
            });
        }

        if ($request->has('q')) {
            $search = $request->q;
            $query->where(function($q) use ($nameField, $search) {
                $q->where($nameField, 'LIKE', "%{$search}%")->orWhere('member_id', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->limit(50)->get()->map(function($u) use ($nameField) {
            return ['id' => $u->id, 'text' => $u->$nameField . ' (' . $u->member_id . ')'];
        });
        return response()->json(['results' => $users]);
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


    // =======================================================
    // 🔥 EXCEPTION MATRIX LOGIC (STEP 6) 🔥
    // =======================================================
public function loadExceptionMatrix(Request $request)
    {
        try {
            // Frontend se employee_ids ya target_ids aayega
            $targetIds = $request->target_ids ?? $request->employee_ids;
            $type = $request->type ?? 'employee'; // 'employee' ya 'member'
            
            if (empty($targetIds)) {
                return response()->json(['status' => 'error', 'message' => 'No target selected']);
            }
            if (!is_array($targetIds)) {
                $targetIds = [$targetIds];
            }

            // ... Module aur Actions fetch karne wala purana logic as it is rahega ...
            $actions = \Illuminate\Support\Facades\DB::table('system_actions')->where('status', 'active')->orderBy('id', 'asc')->get();
            $allPermissions = \Spatie\Permission\Models\Permission::all();
            $modules = [];
            
            foreach ($allPermissions as $perm) {
                $parts = explode('_', $perm->name);
                $actionSlug = array_pop($parts);
                $matchedAction = $actions->firstWhere('action_slug', $actionSlug);
                if (!$matchedAction) {
                    foreach($actions as $act) {
                        if (str_ends_with($perm->name, '_' . $act->action_slug)) { $matchedAction = $act; break; }
                    }
                }
                if ($matchedAction) {
                    $moduleSlug = str_replace('_' . $matchedAction->action_slug, '', $perm->name);
                    $modules[$moduleSlug][] = ['id' => $perm->id, 'name' => $perm->name, 'action_slug' => $matchedAction->action_slug, 'action_name' => $matchedAction->action_name];
                }
            }

            $existingPerms = [];
            if (count($targetIds) == 1) {
                // 🔥 DYNAMIC MODEL SELECTION (Employee ya Member)
                $modelClass = $type === 'member' ? \App\Models\Member::class : \App\Models\Employee::class;
                $user = $modelClass::find($targetIds[0]);
                
                if ($user) {
                    $existingPerms = $user->permissions()->pluck('name')->toArray();
                }
            }

            // HTML Generate
            $html = '<div class="table-responsive"><table class="table table-bordered table-custom table-hover">';
            $html .= '<thead class="bg-primary text-white"><tr><th>Module Name</th>';
            foreach ($actions as $action) { $html .= '<th class="text-center">' . $action->action_name . '</th>'; }
            $html .= '</tr></thead><tbody>';

            foreach ($modules as $moduleSlug => $perms) {
                $moduleDisplayName = strtoupper(str_replace('_', ' ', $moduleSlug));
                $html .= '<tr><td class="fw-bold text-secondary">' . $moduleDisplayName . '</td>';
                foreach ($actions as $action) {
                    $html .= '<td class="text-center">';
                    $permDetail = collect($perms)->firstWhere('action_slug', $action->action_slug);
                    if ($permDetail) {
                        $isChecked = in_array($permDetail['name'], $existingPerms) ? 'checked' : '';
                        $html .= '<input type="checkbox" name="permissions[]" value="'.$permDetail['name'].'" class="form-check-input perm-cb" style="transform: scale(1.3);" '.$isChecked.'>';
                    } else {
                        $html .= '<span class="text-muted">-</span>';
                    }
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
            $html .= '<div class="mt-3 text-end"><button type="button" class="btn btn-success" id="save_exceptions_btn"><i class="fas fa-save"></i> Save Exceptions</button></div>';

            return response()->json(['status' => 'success', 'html' => $html]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Backend Error: ' . $e->getMessage()]);
        }
    }

    public function saveExceptions(Request $request)
    {
        try {
            $targetIds = $request->target_ids ?? $request->employee_ids;
            $permissions = $request->permissions ?? [];
            $type = $request->type ?? 'employee';

            if (empty($targetIds)) return response()->json(['status' => 'error', 'message' => 'No target selected']);
            if (!is_array($targetIds)) $targetIds = [$targetIds];

            // 🔥 DYNAMIC MODEL SELECTION (Employee ya Member)
            $modelClass = $type === 'member' ? \App\Models\Member::class : \App\Models\Employee::class;

            foreach ($targetIds as $id) {
                $user = $modelClass::find($id);
                if ($user) {
                    $user->syncPermissions($permissions);
                }
            }

            return response()->json(['status' => 'success', 'message' => ucfirst($type) . ' exceptions applied successfully!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Backend Error: ' . $e->getMessage()]);
        }
    }
    // =======================================================
    // 🔥 MASTER GRADE SETUP APIS (For index.blade.php) 🔥
    // =======================================================

    public function loadGradeMatrix(Request $request)
    {
        $roleName = $request->role_name; // e.g., 'Grade A'
        if (empty($roleName)) {
            return response()->json(['status' => 'error', 'message' => 'No Grade selected']);
        }

        $actions = \Illuminate\Support\Facades\DB::table('system_actions')->where('status', 'active')->orderBy('id', 'asc')->get();
        $allPermissions = \Spatie\Permission\Models\Permission::all();
        $modules = [];
        
        foreach ($allPermissions as $perm) {
            $parts = explode('_', $perm->name);
            $actionSlug = array_pop($parts);
            
            $matchedAction = $actions->firstWhere('action_slug', $actionSlug);
            if (!$matchedAction) {
                foreach($actions as $act) {
                    if (str_ends_with($perm->name, '_' . $act->action_slug)) { $matchedAction = $act; break; }
                }
            }
            if ($matchedAction) {
                $moduleSlug = str_replace('_' . $matchedAction->action_slug, '', $perm->name);
                $modules[$moduleSlug][] = ['id' => $perm->id, 'name' => $perm->name, 'action_slug' => $matchedAction->action_slug, 'action_name' => $matchedAction->action_name];
            }
        }

        // 🔥 NAYA: Get permissions assigned directly to the ROLE (Grade)
        $existingPerms = [];
        $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
        if ($role) {
            $existingPerms = $role->permissions->pluck('name')->toArray();
        }

        $html = '<div class="table-responsive"><table class="table table-bordered table-custom table-hover">';
        $html .= '<thead class="bg-primary text-white"><tr><th>Module Name</th>';
        foreach ($actions as $action) { $html .= '<th class="text-center">' . $action->action_name . '</th>'; }
        $html .= '</tr></thead><tbody>';

        foreach ($modules as $moduleSlug => $perms) {
            $moduleDisplayName = strtoupper(str_replace('_', ' ', $moduleSlug));
            $html .= '<tr><td class="fw-bold text-secondary">' . $moduleDisplayName . '</td>';
            foreach ($actions as $action) {
                $html .= '<td class="text-center">';
                $permDetail = collect($perms)->firstWhere('action_slug', $action->action_slug);
                if ($permDetail) {
                    $isChecked = in_array($permDetail['name'], $existingPerms) ? 'checked' : '';
                    $html .= '<input type="checkbox" name="permissions[]" value="'.$permDetail['name'].'" class="form-check-input perm-cb" style="transform: scale(1.3);" '.$isChecked.'>';
                } else {
                    $html .= '<span class="text-muted">-</span>';
                }
                $html .= '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= '<div class="mt-3 text-end"><button type="button" class="btn btn-success" id="save_grade_btn"><i class="fas fa-save"></i> Save Grade Defaults</button></div>';

        return response()->json(['status' => 'success', 'html' => $html]);
    }

    public function saveGradeMatrix(Request $request)
    {
        $roleName = $request->role_name;
        $permissions = $request->permissions ?? [];

        if (empty($roleName)) {
            return response()->json(['status' => 'error', 'message' => 'Grade name is required']);
        }

        // 🔥 NAYA: Save strictly to the Role, NOT the user.
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $role->syncPermissions($permissions); // Ye bas us role (Grade) ki nayi permissions set karega

        return response()->json(['status' => 'success', 'message' => $roleName . ' default permissions updated successfully!']);
    }


}
