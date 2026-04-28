<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\InterestedCustomer;
use App\Models\Employee;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class InterestedCustomerController extends Controller
{
 public function index()
    {
        // 1. Fetch Customers with Branch
        $allData = InterestedCustomer::with('branch')->orderBy('id', 'desc')->get();
        $general = $allData->where('status', 'General')->values();
        $interested = $allData->where('status', '!=', 'General')->values();

        // === 2. SIRF ACCESS WALE LOGO KO FETCH KARNA HAI ===
        // Pehle access table se valid IDs nikal lo
        $allowedStaffIds = \App\Models\TelecallerAccess::pluck('staff_id')->toArray();

        // Ab filter laga do ->whereIn('member_id', $allowedStaffIds)
        $employees = \App\Models\Employee::whereIn('member_id', $allowedStaffIds)->get()->map(function($e) {
            return [
                'staff_id' => (string) $e->member_id, 
                'name' => $e->full_name,
                'role' => 'Employee'
            ];
        });
        
        $members = \App\Models\Member::whereIn('member_id', $allowedStaffIds)->get()->map(function($m) {
            return [
                'staff_id' => (string) $m->member_id, 
                'name' => $m->member_name,
                'role' => 'Member'
            ];
        });
        
        // Combine both lists
        $combinedStaff = $employees->concat($members)->unique('staff_id')->values();

        return response()->json([
            'status' => 'success', 
            'general' => $general,
            'interested' => $interested,
            'staff_list' => $combinedStaff // Datalist me ab wahi dikhenge jinko Access mila hua hai
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'cust_name' => 'required',
            'mobile' => 'required'
        ]);

        $data = $request->except(['_token']);
        $customer = InterestedCustomer::create($data);

        // Send Email
        // if (!empty($customer->email)) {
        //     try {
        //         $htmlBody = "
        //             <h3>Dear {$customer->cust_name},</h3>
        //             <p>Thank you for your interest in <b>Amitabh Builders & Developers</b>.</p>
        //             <p><b>Interested For:</b> {$customer->interested_for}</p>
        //             <p><b>Assigned Telecaller:</b> {$customer->assigned_telecaller}</p>
        //             <p><b>Status:</b> {$customer->status}</p>
        //             <p>Our team will contact you shortly.</p><br>
        //             <p>Regards,<br>Amitabh Builders & Developers<br>📞 9472467007</p>
        //         ";
        //         Mail::html($htmlBody, function ($message) use ($customer) {
        //             $message->to($customer->email, $customer->cust_name)
        //                 ->subject('Thank you for contacting Amitabh Builders');
        //         });
        //     } catch (\Exception $e) {
        //     }
        // }

        return response()->json(['status' => 'success', 'message' => 'Customer Added Successfully']);
    }

    public function show($id)
    {
        // with('branch') add kiya gaya hai taaki view modal me branch name dikhe
        return response()->json(['status' => 'success', 'data' => InterestedCustomer::with('branch')->findOrFail($id)]);
    }
    public function update(Request $request, $id)
    {
        $request->validate(['branch_id' => 'required|exists:branches,id']);

        $customer = InterestedCustomer::findOrFail($id);
        $customer->update($request->except(['_token', '_method']));
        return response()->json(['status' => 'success', 'message' => 'Customer Updated Successfully']);
    }

    public function destroy($id)
    {
        InterestedCustomer::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Customer Deleted']);
    }

    public function assignTelecaller(Request $request)
    {
        $telecaller = $request->telecaller;
        $dataFrom   = (int)$request->data_from;
        $dataTo     = (int)$request->data_to;
        $status     = $request->status ?? 'General';

        $query = InterestedCustomer::query();
        if ($status === 'General') {
            $query->where('status', 'General');
        } else {
            $query->where('status', '!=', 'General');
        }

        $totalRows = $query->count();
        if ($dataTo > $totalRows) {
            return response()->json(['status' => false, 'message' => "Only $totalRows records available."]);
        }

        $limit  = $dataTo - $dataFrom + 1;
        $offset = $dataFrom - 1;

        $ids = $query->orderBy('id', 'asc')->skip($offset)->take($limit)->pluck('id');

        if ($ids->isNotEmpty()) {
            InterestedCustomer::whereIn('id', $ids)->update(['assigned_telecaller' => $telecaller]);
            return response()->json(['status' => true, 'message' => "Telecaller Assigned Successfully!"]);
        }

        return response()->json(['status' => false, 'message' => "No records found."]);
    }

    public function filterReports(Request $request)
    {
        // Branch support added in filters
        $query = InterestedCustomer::with('branch');

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('date', [$request->from_date, $request->to_date]);
        }
        if ($request->filled('followup_month')) {
            $query->where('followup_month', $request->followup_month);
        }
        if ($request->filled('refer_by')) {
            $query->where('refer_by', $request->refer_by);
        }
        if ($request->filled('budget_from') && $request->filled('budget_to')) {
            $query->whereBetween('budget', [$request->budget_from, $request->budget_to]);
        }

        return response()->json(['status' => 'success', 'data' => $query->get()]);
    }
}
