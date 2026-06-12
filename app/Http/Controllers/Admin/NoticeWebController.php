<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;

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

        // 🔥 SPECIFIC INDIVIDUAL FETCH LOGIC 🔥
        $entityData = null;
        if ($notice->target_audience === 'other' && $notice->entity_type && $notice->entity_id) {
            $eType = $notice->entity_type;
            $eId = $notice->entity_id;
            
            if ($eType === 'employee') {
                $entityData = \App\Models\Employee::with(['designation', 'department', 'branch', 'company'])
                    ->where('member_id', $eId)->orWhere('id', $eId)->first();
            } elseif ($eType === 'member') {
                $entityData = \App\Models\Member::with(['designation', 'department', 'branch', 'company'])
                    ->where('member_id', $eId)->orWhere('id', $eId)->first();
            } elseif ($eType === 'customer') {
                $entityData = \App\Models\Customer::with(['branch', 'company'])
                    ->where('customer_id', $eId)->orWhere('id', $eId)->first();
            }
        }

        return view('admin.notices.print', compact('notice', 'company', 'branch', 'entityData'));
    }
}