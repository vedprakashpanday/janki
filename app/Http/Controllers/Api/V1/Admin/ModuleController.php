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

    // ==========================================
    // 🛡️ NAYA: RECURSIVE FUNCTION FOR N-LEVEL TREE
    // ==========================================
    private function getNestedModules($parentId = null, $depth = 0, $allModules)
    {
        $result = [];
        $prefix = str_repeat('— ', $depth); // Depth ke hisaab se dash (-) lagayega

        // Current parent ke bachho ko sequence ke hisaab se nikalo
        $children = $allModules->where('parent_id', $parentId)->sortBy('sequence');

        foreach ($children as $child) {
            $child->display_name = $prefix . $child->module_name; // e.g. "— — Sub Child"
            $child->depth = $depth;
            $result[] = $child;

            // Child ke andar sub-child dhoondhne ke liye khud ko call karo (Recursion)
            $result = array_merge($result, $this->getNestedModules($child->id, $depth + 1, $allModules));
        }

        return $result;
    }

    public function index(Request $request)
    {
        if (!$this->checkAccess($request->user(), 'module_master_view')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        // Saare modules ek baar mein fetch karke Memory me N-Level Tree banayenge (Fast Performance)
        $allModules = Module::with('parent')->get();
        $orderedModules = $this->getNestedModules(null, 0, $allModules);

        return response()->json(['status' => 'success', 'data' => $orderedModules]);
    }

    public function getParents(Request $request)
    {
        $allModules = Module::get();
        $orderedModules = $this->getNestedModules(null, 0, $allModules);

        // Dropdown ke liye sirf ID aur Display Name bhejein
        $parents = collect($orderedModules)->map(function($m) {
            return ['id' => $m->id, 'module_name' => $m->display_name];
        });

        return response()->json(['status' => 'success', 'data' => $parents]);
    }

   public function store(Request $request)
    {
        if (!$this->checkAccess($request->user(), 'module_master_add')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        // 🔥 NAYA: 'selected_actions' ko validate kiya gaya hai
        $request->validate([
            'module_name' => 'required|string|max:255',
            'sequence' => 'numeric',
            'selected_actions' => 'nullable|array' 
        ]);

        DB::beginTransaction();
        try {
            // Module create karte waqt 'selected_actions' ko filter kar diya taaki DB error na aaye
            $moduleData = $request->except('selected_actions');
            $module = Module::create($moduleData);

            // 🔥 NAYA LOGIC: Sirf tick kiye gaye (selected) actions ka hi loop chalega
            if (!empty($request->permission_base) && !empty($request->selected_actions)) {
                
                foreach ($request->selected_actions as $actionSlug) {
                    \Spatie\Permission\Models\Permission::firstOrCreate([
                        'name' => $request->permission_base . '_' . $actionSlug, 
                        'guard_name' => 'web'
                    ]);
                }
            }
            
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Module & Selected Permissions Generated Successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $module = Module::findOrFail($id);
        $attachedActions = [];

        // 🔥 FIX: Agar module me permission base hai, toh uske actions nikal kar frontend bhejo
        if (!empty($module->permission_base)) {
            $permissions = \Spatie\Permission\Models\Permission::where('name', 'like', $module->permission_base . '_%')->pluck('name');
            foreach ($permissions as $perm) {
                // 'employee_add' me se 'employee_' hata denge toh sirf 'add' (action slug) bachega
                $attachedActions[] = str_replace($module->permission_base . '_', '', $perm);
            }
        }

        return response()->json([
            'status' => 'success', 
            'data' => $module, 
            'attached_actions' => $attachedActions
        ]);
    }

   public function update(Request $request, $id)
    {
        if (!$this->checkAccess($request->user(), 'module_master_edit')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $module = Module::findOrFail($id);
        $oldBase = $module->permission_base; // Purana base save kar liya

        // Core module data update karo
        $moduleData = $request->except('selected_actions');
        $module->update($moduleData);

        $newBase = $module->permission_base;

        // 🔥 FIX: Naye actions create karo aur purane (unchecked) actions delete karo 🔥
        if (!empty($oldBase) || !empty($newBase)) {
            $searchBase = !empty($oldBase) ? $oldBase : $newBase;
            $existingPerms = \Spatie\Permission\Models\Permission::where('name', 'like', $searchBase . '_%')->get();
            
            $newPermNames = [];

            // 1. Naye tick kiye hue permissions Create karo
            if (!empty($newBase) && !empty($request->selected_actions)) {
                foreach ($request->selected_actions as $actionSlug) {
                    $permName = $newBase . '_' . $actionSlug;
                    $newPermNames[] = $permName;
                    
                    \Spatie\Permission\Models\Permission::firstOrCreate([
                        'name' => $permName, 
                        'guard_name' => 'web'
                    ]);
                }
            }

            // 2. Jo permissions DB me the par ab array me nahi hain (uncheck ho gaye), unko UDA DO (Delete)
            foreach ($existingPerms as $ep) {
                if (!in_array($ep->name, $newPermNames)) {
                    $ep->delete(); // Spatie table se hamesha ke liye delete
                }
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Module & Actions Updated Successfully!']);
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
