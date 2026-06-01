<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ModuleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Cache clear karna zaroori hai
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Safe cleanup taaki duplicate na ho re-run karne par
        Module::truncate();

        // 1. Dashboard (Standalone Module - No Parent)
        $this->createModuleAndPermissions('Dashboard', 'admin/dashboard', 'fas fa-tachometer-alt', 'dashboard', 1);

        // 2. Vouchers (Parent)
        $vouchers = $this->createModuleAndPermissions('Vouchers', null, 'fas fa-file-invoice', null, 2);
        $this->createModuleAndPermissions('Debit Vouchers', 'admin/debit_vouchers', 'fas fa-arrow-circle-up', 'debit_voucher', 1, $vouchers->id);
        $this->createModuleAndPermissions('Receipt Vouchers', 'admin/give-access', 'fas fa-arrow-circle-down', 'receipt_voucher', 2, $vouchers->id);

        // 3. CRM & Leads (Parent)
        $crm = $this->createModuleAndPermissions('CRM & Leads', null, 'fas fa-bullhorn', null, 3);
        $this->createModuleAndPermissions('Add Interested', 'admin/interested-customers', 'fas fa-user-plus', 'interested_customer', 1, $crm->id);
        $this->createModuleAndPermissions('Customer Details', 'admin/customers', 'fas fa-users', 'customer', 2, $crm->id);
        $this->createModuleAndPermissions('Give Access', 'admin/give-access', 'fas fa-user-shield', 'customer_access', 3, $crm->id);

        // 4. Network (Parent)
        $network = $this->createModuleAndPermissions('Network', null, 'fas fa-network-wired', null, 4);
        $this->createModuleAndPermissions('Member Details', 'admin/members', 'fas fa-user-friends', 'member', 1, $network->id);
        $this->createModuleAndPermissions('Member Designations', 'admin/member-designations', 'fas fa-user-tag', 'member_designation', 2, $network->id);
        $this->createModuleAndPermissions('Agent Details', 'admin/agents', 'fas fa-briefcase', 'agent', 3, $network->id);
        $this->createModuleAndPermissions('Landowner Details', 'admin/landowners', 'fas fa-map-marked-alt', 'landowner', 4, $network->id);
        $this->createModuleAndPermissions('Vendor Details', 'admin/vendors', 'fas fa-store', 'vendor', 5, $network->id);

        // 5. HR & Admin (Parent)
        $hrAdmin = $this->createModuleAndPermissions('HR & Admin', null, 'fas fa-user-tie', null, 5);
        
        // Master Control Tier
        $this->createModuleAndPermissions('Super Admin (CEO)', 'admin/super-admins', 'fas fa-crown text-warning', 'super_admin', 1, $hrAdmin->id);
        $this->createModuleAndPermissions('Company Directors', 'admin/directors', 'fas fa-user-shield text-primary', 'director', 2, $hrAdmin->id);
        $this->createModuleAndPermissions('Roles & Permissions', 'admin/role-manager', 'fas fa-user-lock text-danger', 'role_management', 3, $hrAdmin->id);
        $this->createModuleAndPermissions('Device Access', 'admin/panel-access', 'fas fa-laptop-house text-success', 'device_access', 4, $hrAdmin->id);

        // Operations Center
        $this->createModuleAndPermissions('Group Of Companies', 'admin/companies', 'fas fa-industry', 'company', 5, $hrAdmin->id);
        $this->createModuleAndPermissions('Branches', 'admin/branches', 'fas fa-building', 'branch', 6, $hrAdmin->id);
        $this->createModuleAndPermissions('Departments', 'admin/departments', 'fas fa-sitemap text-info', 'department', 7, $hrAdmin->id);
        $this->createModuleAndPermissions('Ledgers', 'admin/ledgers', 'fas fa-book', 'ledger', 8, $hrAdmin->id);
        $this->createModuleAndPermissions('LetterHeads', 'admin/letterheads', 'fas fa-file-signature', 'letterhead', 9, $hrAdmin->id);
        $this->createModuleAndPermissions('ID Cards', 'admin/id-cards', 'fas fa-id-badge', 'id_card', 10, $hrAdmin->id);

        // Staff Allocation
        $this->createModuleAndPermissions('Employee Details', 'admin/employees', 'fas fa-id-card', 'employee', 11, $hrAdmin->id);
        $this->createModuleAndPermissions('Employee Designations', 'admin/designations', 'fas fa-user-tag', 'designation', 12, $hrAdmin->id);
        $this->createModuleAndPermissions('Employee Salaries', 'admin/salaries', 'fas fa-money-bill-wave', 'salary', 13, $hrAdmin->id);
    }

    // Helper function jo module bhi save karega aur permissions bhi loop kar dega
    private function createModuleAndPermissions($name, $route, $icon, $permissionBase, $sequence, $parentId = null)
    {
        $module = Module::create([
            'parent_id' => $parentId,
            'module_name' => $name,
            'route' => $route,
            'icon' => $icon,
            'permission_base' => $permissionBase,
            'sequence' => $sequence,
            'status' => 'active'
        ]);

        // Agar child menu hai aur uska permission base set hai, toh uske actions auto-generate karo
        if ($permissionBase) {
            $actions = ['view', 'add_request', 'add_direct', 'edit', 'delete', 'restore', 'print'];
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => $permissionBase . '_' . $action,
                    'guard_name' => 'web'
                ]);
            }
        }

        return $module;
    }
}