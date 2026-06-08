<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    // ========================================================
    // 🔥 MASTER GLOBAL CONTEXT FUNCTION 🔥
    // ========================================================
    public function getGlobalContext()
    {
        $user = auth()->user();
        if (!$user) return null;

        $table = $user->getTable();

        // Default Context Object
        $context = (object)[
            'is_god'        => false,
            'is_director'   => false,
            'is_employee'   => false,
            'is_member'     => false,
            'is_vendor'     => false,
            'is_customer'   => false,
            'company_id'    => $user->company_id ?? null,
            'branch_id'     => $user->branch_id  ?? null,
            'role_level'    => 'unknown',
            'profile_id'    => $user->id,
        ];

        // ----------------------------------------------------------------
        // 1. DEVELOPER / GOD MODE — email-based bypass
        // ----------------------------------------------------------------
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (in_array($user->email ?? '', $developerEmails)) {
            $context->is_god    = true;
            $context->role_level = 'developer';
            // ✅ company_id / branch_id / profile_id already set above from $user — no early return
        }

        // ----------------------------------------------------------------
        // 2. users TABLE — single-table auth (admin, employee, customer etc.)
        //    Role column: enum('admin','employee','customer', ...)
        // ----------------------------------------------------------------
        elseif ($table === 'users') {
            $role = strtolower($user->role ?? 'customer');

            if (in_array($role, ['admin', 'superadmin', 'ceo', 'super_admin'])) {
                $context->is_god    = true;
                $context->role_level = 'admin';
                $context->profile_id = $user->id;
            } elseif ($role === 'director') {
                $context->is_director = true;
                $context->role_level  = 'director';
                $context->profile_id  = $user->id;
            } elseif (in_array($role, ['employee', 'staff', 'telecaller'])) {
                $context->is_employee = true;
                $context->role_level  = 'employee';
                $context->profile_id  = $user->id;
            } else {
                // customer ya koi aur role
                $context->is_customer = true;
                $context->role_level  = $role;
                $context->profile_id  = $user->id;
            }
        }

        // ----------------------------------------------------------------
        // 3. super_admins TABLE
        // ----------------------------------------------------------------
        elseif ($table === 'super_admins' || (method_exists($user, 'hasRole') && $user->hasRole(['CEO', 'Super Admin']))) {
            $context->is_god    = true;
            $context->role_level = 'ceo';
            $context->profile_id = $user->ceo_id ?? $user->id;
        }

        // ----------------------------------------------------------------
        // 4. directors TABLE
        // ----------------------------------------------------------------
        elseif ($table === 'directors' || (method_exists($user, 'hasRole') && $user->hasRole('Director'))) {
            $context->is_director = true;
            $context->role_level  = 'director';
            $context->company_id  = $user->company_id ?? null;
            $context->profile_id  = $user->director_id ?? $user->id;
        }

        // ----------------------------------------------------------------
        // 5. adm_regist / employees TABLE
        // ----------------------------------------------------------------
        elseif ($table === 'adm_regist' || $table === 'employees') {
            $context->is_employee = true;
            $context->role_level  = 'employee';
            $context->company_id  = $user->company_id ?? null;
            $context->branch_id   = $user->branch_id  ?? null;
            $context->profile_id  = $user->member_id  ?? $user->id;
        }

        // ----------------------------------------------------------------
        // 6. members TABLE
        // ----------------------------------------------------------------
        elseif ($table === 'members') {
            $context->is_member  = true;
            $context->role_level = 'member';
            $context->profile_id = $user->member_id ?? $user->id;
        }

        // ----------------------------------------------------------------
        // 7. vendors TABLE
        // ----------------------------------------------------------------
        elseif ($table === 'vendors') {
            $context->is_vendor  = true;
            $context->role_level = 'vendor';
            $context->profile_id = $user->vendor_id ?? $user->id;
        }

        // ----------------------------------------------------------------
        // 8. customers TABLE
        // ----------------------------------------------------------------
        elseif ($table === 'customers') {
            $context->is_customer = true;
            $context->role_level  = 'customer';
            $context->profile_id  = $user->customer_id ?? $user->id;
        }

        return $context;
    }



    public static function getLiveActivePermissions($user)
    {
        if (!$user) return [];
        $today = date('Y-m-d');
        $time = date('H:i:s');
        $modelClass = get_class($user);

        // 1. Direct Permissions
        $directPerms = \Illuminate\Support\Facades\DB::table('model_has_permissions')
            ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->where('model_id', $user->id)
            ->where('model_type', $modelClass)
            ->where(function($q) use ($today) { $q->whereNull('access_start_date')->orWhere('access_start_date', '<=', $today); })
            ->where(function($q) use ($today) { $q->whereNull('access_end_date')->orWhere('access_end_date', '>=', $today); })
            ->where(function($q) use ($time) { $q->whereNull('daily_start_time')->orWhere('daily_start_time', '<=', $time); })
            ->where(function($q) use ($time) { $q->whereNull('daily_end_time')->orWhere('daily_end_time', '>=', $time); })
            ->pluck('permissions.name')->toArray();

        // 2. Roles
        $validRolesIds = \Illuminate\Support\Facades\DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->where('model_type', $modelClass)
            ->where(function($q) use ($today) { $q->whereNull('access_start_date')->orWhere('access_start_date', '<=', $today); })
            ->where(function($q) use ($today) { $q->whereNull('access_end_date')->orWhere('access_end_date', '>=', $today); })
            ->where(function($q) use ($time) { $q->whereNull('daily_start_time')->orWhere('daily_start_time', '<=', $time); })
            ->where(function($q) use ($time) { $q->whereNull('daily_end_time')->orWhere('daily_end_time', '>=', $time); })
            ->pluck('role_id')->toArray();

        // 3. Role Permissions
        $rolePerms = [];
        if (!empty($validRolesIds)) {
            $rolePerms = \Illuminate\Support\Facades\DB::table('role_has_permissions')
                ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
                ->whereIn('role_has_permissions.role_id', $validRolesIds)
                ->pluck('permissions.name')->toArray();
        }

        return array_values(array_unique(array_merge($directPerms, $rolePerms)));
    }
}