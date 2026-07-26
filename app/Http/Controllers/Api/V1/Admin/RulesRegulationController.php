<?php
namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\RulesRegulation;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class RulesRegulationController extends Controller
{
    // RBAC Check helper (Permissions ab 'rules_' se check hongi)
    private function checkPermission($action)
    {
        $user = auth()->user();
        if (!$user) return false;

        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (in_array(strtolower($user->email), $developerEmails) || (method_exists($user, 'hasRole') && $user->hasRole('Super Admin'))) {
            return true;
        }

        $livePerms = self::getLiveActivePermissions($user);
        return in_array("rules_{$action}", $livePerms);
    }

    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $hasAdminPerm = $this->checkPermission('view');

        // 🔥 ZONE 1: ADMIN ACCESS (Shows Table Data)
        if ($hasAdminPerm) {
            $query = RulesRegulation::query();
            if ($request->has('target_audience')) {
                $query->where('target_audience', $request->target_audience);
            }
            return response()->json([
                'status' => 'success',
                'data' => $query->orderBy('id', 'desc')->get()
            ]);
        }

        // 🔥 ZONE 2: EMPLOYEE / MEMBER ACCESS (Read Only Official Document View)
        $myAudience = 'employee'; // default
        if (isset($context->is_member) && $context->is_member) $myAudience = 'member';
        
        // Customer aur general isme allowed nahi hain, isliye unko access nahi milega.
        if (isset($context->is_customer) && $context->is_customer) {
             return response()->json(['status' => 'success', 'data' => [], 'header_html' => '']);
        }

        $query = RulesRegulation::where('status', 'active')
                              ->where('target_audience', $myAudience);

        $rule = $query->orderBy('id', 'desc')->first();

        // 👉 DYNAMIC HEADER LOGIC
        $user = auth()->user();
        $company = null;
        $branch = null;

        if ($user) {
            $companyId = $user->company_id ?? 1;
            $company = Company::find($companyId);
            
            if (!empty($user->branch_id)) {
                $branch = Branch::find($user->branch_id);
            }
        }

        $headerHtml = '';
        if ($company) {
            $headerHtml = View::make('components.print-header', compact('company', 'branch'))->render();
        }

        return response()->json([
            'status' => 'success',
            'data' => $rule ? [$rule] : [],
            'header_html' => $headerHtml
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->checkPermission('add')) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        $request->validate([
            'title' => 'required', 
            'target_audience' => 'required|in:employee,member', 
            'content' => 'required'
        ]);
        
        $rule = RulesRegulation::create($request->all());
        return response()->json(['status' => 'success', 'message' => 'Rules created successfully', 'data' => $rule]);
    }

    public function show($id)
    {
        $rule = RulesRegulation::find($id);
        if(!$rule) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        return response()->json(['status' => 'success', 'data' => $rule]);
    }

    public function update(Request $request, $id)
    {
        if (!$this->checkPermission('edit')) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        $rule = RulesRegulation::findOrFail($id);
        $rule->update($request->all());
        return response()->json(['status' => 'success', 'message' => 'Updated Successfully']);
    }

    public function destroy($id)
    {
        if (!$this->checkPermission('delete')) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        RulesRegulation::destroy($id);
        return response()->json(['status' => 'success', 'message' => 'Deleted Successfully']);
    }
}