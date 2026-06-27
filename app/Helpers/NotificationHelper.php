<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use App\Models\Employee;

class NotificationHelper
{
  public static function getTargets($companyId, $branchId, $requiredPermission = null)
    {
        $targets = collect();

        // 👑 1. Master Admins & Developers (USERS table se uthao)
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $godAdmins = \App\Models\User::whereIn('email', $developerEmails)->get();
        foreach ($godAdmins as $god) {
            $targets->push($god);
        }

        // 2. CEOs (Super Admins) 
        $ceos = \Illuminate\Support\Facades\DB::table('super_admins')->where('status', 'active')->get();
        foreach ($ceos as $ceo) {
            $ceoModel = \App\Models\SuperAdmin::find($ceo->id); 
            if($ceoModel) $targets->push($ceoModel);
        }

        // 3. Directors (Sirf usi company ke)
        if ($companyId) {
            $directorIds = \Illuminate\Support\Facades\DB::table('company_director')->where('company_id', $companyId)->pluck('director_id');
            $directors = \App\Models\Director::whereIn('id', $directorIds)->where('status', 'active')->get();
            foreach ($directors as $dir) {
                $targets->push($dir);
            }
        }

        // 4. Branch Assigned Employees (Jinke paas permission ho)
        if ($companyId && $branchId && $requiredPermission) {
            $employees = \App\Models\Employee::permission([$requiredPermission])
                            ->where('company_id', $companyId)
                            ->where('branch_id', $branchId)
                            ->where('emp_status', 'active')
                            ->get();
            foreach ($employees as $emp) {
                $targets->push($emp);
            }
        }

        return $targets->unique('id'); 
    }
   
}