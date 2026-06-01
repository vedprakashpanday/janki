<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AdvancedRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Purani cached permissions clear karna (zaroori hai taaki koi error na aaye)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Modules aur Unke Actions define karein
        $modules = ['company', 'department', 'designation', 'employee'];
        $actions = ['view', 'add_request', 'add_direct', 'edit', 'delete', 'restore', 'print'];

        // 3. Loop chala kar saari Permissions Generate karein
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                // e.g., 'employee_add_direct', 'company_print' ban jayega
                Permission::firstOrCreate([
                    'name' => $module . '_' . $action, 
                    'guard_name' => 'web' // Agar API use kar rahe hain toh 'api' bhi rakh sakte hain
                ]);
            }
        }

        // 4. Kuch Special Permissions (Jo loop mein cover nahi hoti)
        $specialPermissions = [
            'delegate_permissions',       // Kisi aur ko permission dene ki power
            'approve_employee_request',   // Employee ki add_request ko approve karne ki power
            'access_master_dashboard',    // Main Dashboard dekhne ki power
        ];

        foreach ($specialPermissions as $sp) {
            Permission::firstOrCreate(['name' => $sp, 'guard_name' => 'web']);
        }

        // ====================================================
        // 5. ROLES BANAYEIN AUR POWERS ASSIGN KAREIN
        // ====================================================

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $companyDirector = Role::firstOrCreate(['name' => 'Company Director', 'guard_name' => 'web']);
        $basicEmployee = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);

        // 👉 SUPER ADMIN: Isko sab kuch allowed hai
        $superAdmin->givePermissionTo(Permission::all());

        // 👉 COMPANY DIRECTOR: Ise sirf apni company manage karne ki limited powers hain
        $companyDirector->givePermissionTo([
            // Employees par poora control (apni company ke)
            'employee_view', 'employee_add_direct', 'employee_edit', 'employee_delete', 'employee_restore', 'employee_print', 'approve_employee_request',
            
            // Department & Designation (Sirf dekh sakte hain, ya edit bhi de sakte ho)
            'department_view', 'designation_view', 
            
            // Dusron ko role assign karne ki power
            'delegate_permissions' 
        ]);

        // 👉 BASIC EMPLOYEE: Ise sirf dekhne aur "Request" dalne ki permission hai
        $basicEmployee->givePermissionTo([
            'employee_view',
            'employee_add_request' // Direct save nahi hoga, approval mein jayega
        ]);
        
        $this->command->info('Granular Permissions aur Roles successfully set ho gaye hain!');
    }
}