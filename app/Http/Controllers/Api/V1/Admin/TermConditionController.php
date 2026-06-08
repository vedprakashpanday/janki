<?php
namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\TermCondition;
use Illuminate\Http\Request;

class TermConditionController extends Controller
{
    // RBAC Check helper
    private function checkPermission($action)
    {
        $user = auth()->user();
        if (!$user) return false;

        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (in_array(strtolower($user->email), $developerEmails) || (method_exists($user, 'hasRole') && $user->hasRole('Super Admin'))) {
            return true;
        }

        $livePerms = self::getLiveActivePermissions($user);
        return in_array("terms_{$action}", $livePerms);
    }

    public function index(Request $request)
    {
        if (!$this->checkPermission('view')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access!'], 403);
        }

        $query = TermCondition::query();
        
        // Target Audience Filter for Panel View
        if ($request->has('target_audience')) {
            $query->where('target_audience', $request->target_audience);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderBy('id', 'desc')->get()
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->checkPermission('add')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        }

        $request->validate([
            'title' => 'required',
            'target_audience' => 'required',
            'content' => 'required'
        ]);

        $term = TermCondition::create($request->all());
        return response()->json(['status' => 'success', 'message' => 'Terms created successfully', 'data' => $term]);
    }

    public function show($id)
    {
        $term = TermCondition::find($id);
        if(!$term) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        return response()->json(['status' => 'success', 'data' => $term]);
    }

    public function update(Request $request, $id)
    {
        if (!$this->checkPermission('edit')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        }

        $term = TermCondition::findOrFail($id);
        $term->update($request->all());
        return response()->json(['status' => 'success', 'message' => 'Updated Successfully']);
    }

    public function destroy($id)
    {
        if (!$this->checkPermission('delete')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);
        }

        TermCondition::destroy($id);
        return response()->json(['status' => 'success', 'message' => 'Deleted Successfully']);
    }
}