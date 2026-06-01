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
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isMaster = $user->hasRole(['CEO', 'Director']) || in_array($user->email, $developerEmails);

        // ==========================================
        // 🛡️ 1. DATA FILTER LOGIC (Datatable Data)
        // ==========================================
        $query = InterestedCustomer::with('branch')->orderBy('id', 'desc');

        if (!$isMaster) {
            // Sirf apni branch ka data...
            $query->where('branch_id', $user->branch_id)
                  // ...Aur jisme ye khud assigned hai, YA phir jo "Unassigned (General)" hai taaki ye pick kar sake
                  ->where(function($q) use ($user) {
                      $q->where('assigned_telecaller', $user->member_id)
                        ->orWhere('assigned_telecaller', $user->full_name) // Fallback
                        ->orWhereNull('assigned_telecaller')
                        ->orWhere('assigned_telecaller', '');
                  });
        }

        $allData = $query->get();
        $general = $allData->where('status', 'General')->values();
        $interested = $allData->where('status', '!=', 'General')->values();

        // ==========================================
        // 🛡️ 2. TELECALLER DROPDOWN FILTER (Staff List)
        // ==========================================
        if (!$isMaster) {
            // Employee ko sirf APNA naam dikhega assign karne ke liye
            $allowedStaffIds = [$user->member_id];
        } else {
            // Master/CEO ko saare access wale log dikhenge
            $allowedStaffIds = \App\Models\TelecallerAccess::pluck('staff_id')->toArray();
        }

        // Fetch Staff
        $employees = \App\Models\Employee::whereIn('member_id', $allowedStaffIds)->get()->map(function($e) {
            return ['staff_id' => (string) $e->member_id, 'name' => $e->full_name, 'role' => 'Employee'];
        });
        
        $members = \App\Models\Member::whereIn('member_id', $allowedStaffIds)->get()->map(function($m) {
            return ['staff_id' => (string) $m->member_id, 'name' => $m->member_name, 'role' => 'Member'];
        });
        
        $combinedStaff = $employees->concat($members)->unique('staff_id')->values();

        return response()->json([
            'status' => 'success', 
            'general' => $general,
            'interested' => $interested,
            'staff_list' => $combinedStaff 
        ]);
    }

  public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'cust_name' => 'required',
            'mobile' => 'required'
        ]);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            if ($request->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized! You can only add leads in your own branch.'], 403);
            }
        }

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


    $customer = InterestedCustomer::with('branch')->findOrFail($id);

    // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            // Branch match honi chahiye
            if ($customer->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
            // Assigned telecaller wahi hona chahiye (ya phir general lead ho)
            if (!empty($customer->assigned_telecaller) && $customer->assigned_telecaller != $user->member_id && $customer->assigned_telecaller != $user->full_name) {
                return response()->json(['status' => 'error', 'message' => 'This lead is assigned to another telecaller.'], 403);
            }
        }


        // with('branch') add kiya gaya hai taaki view modal me branch name dikhe
        return response()->json(['status' => 'success', 'data' => InterestedCustomer::with('branch')->findOrFail($id)]);
    }
    public function update(Request $request, $id)
    {
        $request->validate(['branch_id' => 'required|exists:branches,id']);

        $customer = InterestedCustomer::findOrFail($id);

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            // Branch match honi chahiye
            if ($customer->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
            // Assigned telecaller wahi hona chahiye (ya phir general lead ho)
            if (!empty($customer->assigned_telecaller) && $customer->assigned_telecaller != $user->member_id && $customer->assigned_telecaller != $user->full_name) {
                return response()->json(['status' => 'error', 'message' => 'This lead is assigned to another telecaller.'], 403);
            }
        }



        $customer->update($request->except(['_token', '_method']));
        return response()->json(['status' => 'success', 'message' => 'Customer Updated Successfully']);
    }

    public function destroy($id)
    {
        InterestedCustomer::findOrFail($id)->delete();

        // 🛡️ OWNERSHIP CHECK
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            // Branch match honi chahiye
            if ($customer->branch_id != $user->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
            // Assigned telecaller wahi hona chahiye (ya phir general lead ho)
            if (!empty($customer->assigned_telecaller) && $customer->assigned_telecaller != $user->member_id && $customer->assigned_telecaller != $user->full_name) {
                return response()->json(['status' => 'error', 'message' => 'This lead is assigned to another telecaller.'], 403);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Customer Deleted']);
    }

    public function assignTelecaller(Request $request)
    {
        $telecaller = $request->telecaller;
        $dataFrom   = (int)$request->data_from;
        $dataTo     = (int)$request->data_to;
        $status     = $request->status ?? 'General';

        $query = InterestedCustomer::query();

        // 🛡️ DATA FILTER LOGIC FOR BULK ASSIGN
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isMaster = $user->hasRole(['CEO', 'Director']) || in_array($user->email, $developerEmails);

        if (!$isMaster) {
            $query->where('branch_id', $user->branch_id);
            // Ensure wo sirf apne aap ko hi assign kar raha ho
            if ($telecaller != $user->member_id) {
                return response()->json(['status' => false, 'message' => "You can only assign leads to yourself."]);
            }
        }

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

        // 🛡️ REPORTS FILTER LOGIC
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (!$user->hasRole(['CEO', 'Director']) && !in_array($user->email, $developerEmails)) {
            $query->where('branch_id', $user->branch_id)
                  ->where(function($q) use ($user) {
                      $q->where('assigned_telecaller', $user->member_id)
                        ->orWhere('assigned_telecaller', $user->full_name);
                  });
        }

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
