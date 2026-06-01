<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class ModuleController extends Controller
{
    // ==========================================
    // 🛡️ NAYA: Centralized Security Check Function 
    // ==========================================
    private function checkAccess($user, $permission)
    {
        if (!$user) return false;
        
        // 1. God Mode Bypass for Master Developers
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (in_array($user->email, $developerEmails)) return true;
        
        // 2. Spatie Roles Check
        if ($user->hasRole('Super Admin')) return true;
        if ($user->can($permission)) return true;

        return false;
    }

    public function index(Request $request)
    {
        if (!$this->checkAccess($request->user(), 'module_master_view')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $modules = Module::with('parent')->orderBy('sequence', 'asc')->get();
        return response()->json(['status' => 'success', 'data' => $modules]);
    }

    public function getParents(Request $request)
    {
        // Dropdown ke liye simple list
        $parents = Module::whereNull('parent_id')->orderBy('sequence', 'asc')->get();
        return response()->json(['status' => 'success', 'data' => $parents]);
    }

   public function store(Request $request)
{
    if (!$this->checkAccess($request->user(), 'module_master_add')) {
        return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
    }

    $request->validate([
        'module_name' => 'required|string|max:255',
        'sequence' => 'numeric'
    ]);

    DB::beginTransaction();
    try {
        $module = Module::create($request->all());

        // 🔥 HARDCODING KHATAM: Ab system_actions table se dynamic loop chalega 🔥
        if (!empty($request->permission_base)) {
            $activeActions = \App\Models\SystemAction::where('status', 'active')->get();
            
            foreach ($activeActions as $action) {
                Permission::firstOrCreate([
                    'name' => $request->permission_base . '_' . $action->action_slug, 
                    'guard_name' => 'web'
                ]);
            }
        }
        
        DB::commit();
        return response()->json(['status' => 'success', 'message' => 'Module & Dynamic Permissions Generated Successfully!']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

    public function show($id)
    {
        return response()->json(['status' => 'success', 'data' => Module::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        if (!$this->checkAccess($request->user(), 'module_master_edit')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $module = Module::findOrFail($id);
        $module->update($request->all());
        return response()->json(['status' => 'success', 'message' => 'Module Updated Successfully!']);
    }

    public function destroy(Request $request, $id)
    {
        if (!$this->checkAccess($request->user(), 'module_master_delete')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        Module::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Module Deleted!']);
    }
}
