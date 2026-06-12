<?php
namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\TermCondition;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

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
        $context = $this->getGlobalContext();
        $hasAdminPerm = $this->checkPermission('view');

        // 🔥 ZONE 1: ADMIN ACCESS (Shows Table Data)
        if ($hasAdminPerm) {
            $query = TermCondition::query();
            if ($request->has('target_audience')) {
                $query->where('target_audience', $request->target_audience);
            }
            return response()->json([
                'status' => 'success',
                'data' => $query->orderBy('id', 'desc')->get()
            ]);
        }

        // 🔥 ZONE 2: EMPLOYEE / MEMBER / CUSTOMER ACCESS (Read Only Official Document View)
        $myAudience = 'general';
        if (isset($context->is_employee) && $context->is_employee) $myAudience = 'employee';
        if (isset($context->is_member) && $context->is_member) $myAudience = 'member';
        if (isset($context->is_customer) && $context->is_customer) $myAudience = 'customer';

        $query = TermCondition::where('status', 'active')
                              ->where('target_audience', $myAudience);

        $term = $query->orderBy('id', 'desc')->first();

        // 👉 DYNAMIC HEADER LOGIC
        $user = auth()->user();
        $company = null;
        $branch = null;

        if ($user) {
            // Get Company (Fallback to ID 1 if null)
            $companyId = $user->company_id ?? 1;
            $company = Company::find($companyId);
            
            // Get Branch if available
            if (!empty($user->branch_id)) {
                $branch = Branch::find($user->branch_id);
            }
        }

        // Render your header component to HTML string
        $headerHtml = '';
        if ($company) {
            $headerHtml = View::make('components.print-header', compact('company', 'branch'))->render();
        }

        return response()->json([
            'status' => 'success',
            'data' => $term ? [$term] : [],
            'header_html' => $headerHtml
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->checkPermission('add')) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        $request->validate(['title' => 'required', 'target_audience' => 'required', 'content' => 'required']);
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
        if (!$this->checkPermission('edit')) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        $term = TermCondition::findOrFail($id);
        $term->update($request->all());
        return response()->json(['status' => 'success', 'message' => 'Updated Successfully']);
    }

    public function destroy($id)
    {
        if (!$this->checkPermission('delete')) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        TermCondition::destroy($id);
        return response()->json(['status' => 'success', 'message' => 'Deleted Successfully']);
    }
}