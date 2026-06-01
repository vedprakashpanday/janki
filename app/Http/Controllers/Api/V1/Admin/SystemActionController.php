<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemAction;
use Illuminate\Http\Request;

class SystemActionController extends Controller
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
        if (!$this->checkAccess($request->user(), 'action_master_view')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }
        $actions = SystemAction::orderBy('id', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $actions]);
    }

    public function store(Request $request)
    {
        if (!$this->checkAccess($request->user(), 'action_master_add')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'action_name' => 'required|string|max:255',
            'action_slug' => 'required|string|unique:system_actions,action_slug'
        ]);

        SystemAction::create($request->all());
        return response()->json(['status' => 'success', 'message' => 'New Action Created!']);
    }

    public function show($id)
    {
        return response()->json(['status' => 'success', 'data' => SystemAction::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        if (!$this->checkAccess($request->user(), 'action_master_edit')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        $action = SystemAction::findOrFail($id);
        $action->update($request->all());
        return response()->json(['status' => 'success', 'message' => 'Action Updated!']);
    }

    public function destroy(Request $request, $id)
    {
        if (!$this->checkAccess($request->user(), 'action_master_delete')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        SystemAction::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Action Deleted!']);
    }
}