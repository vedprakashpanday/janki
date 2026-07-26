<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoticeWebController extends Controller
{
    public function printNotice(Request $request, $id)
    {
        $notice = Notice::findOrFail($id);

        $company = null;
        if ($request->company_id === 'all' || empty($request->company_id)) {
            $company = Company::find(1);
        } else {
            $company = Company::find($request->company_id);
        }

        $branch = null;
        if ($request->branch_id && $request->branch_id !== 'all') {
            $branch = Branch::find($request->branch_id);
        }

        $entityData = null;
        if ($notice->target_audience === 'other' && $notice->entity_type && $notice->entity_id) {
            $eType = $notice->entity_type;
            $eId = $notice->entity_id;

            if ($eType === 'employee') {
                $entityData = \App\Models\Employee::with(['department', 'branch', 'company'])
                    ->where('member_id', $eId)->orWhere('id', $eId)->first();
            } elseif ($eType === 'member') {
                $entityData = \App\Models\Member::with(['department', 'branch', 'company'])
                    ->where('member_id', $eId)->orWhere('id', $eId)->first();
            } elseif ($eType === 'customer') {
                $entityData = \App\Models\Customer::with(['branch', 'company'])
                    ->where('customer_id', $eId)->orWhere('id', $eId)->first();
            }

            // 🔥 BULLETPROOF FIX: Use Custom Attributes instead of overriding relations
            if ($entityData) {

                // 1. Branch Logic
                if (empty($entityData->branch_id) || !$entityData->branch) {
                    $entityData->custom_branch_name = 'Head Office';
                }

                // 2. Designation Logic
                if (in_array($eType, ['employee', 'member'])) {
                    if (!empty($entityData->designation_id)) {
                        $designationRecord = DB::table('designations')
                            ->where('id', $entityData->designation_id)
                            ->first();

                        if ($designationRecord) {
                            $entityData->custom_designation_name = $designationRecord->designation_name ?? 'N/A';
                        }
                    }
                }
            }
        }

        return view('admin.notices.print', compact('notice', 'company', 'branch', 'entityData'));
    }
}
