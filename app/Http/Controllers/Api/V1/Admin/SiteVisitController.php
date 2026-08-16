<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteVisit;
use App\Models\SiteVisitImage;
use App\Models\SiteVisitSetting;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\MediaConverterService;

class SiteVisitController extends Controller
{
    protected $mediaConverter;

    public function __construct(MediaConverterService $mediaConverter)
    {
        $this->mediaConverter = $mediaConverter;
    }

    // ==========================================
    // 1. MAIN DATATABLE / CARDS INDEX
    // ==========================================
   public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $userPerms = self::getLiveActivePermissions(auth()->user());

        $query = SiteVisit::with(['company', 'branch', 'department', 'designation', 'employee', 'phase', 'images'])->latest();

        if (!$context->is_god) {
            if (!in_array('sv_view', $userPerms)) {
                return response()->json(['draw' => intval($request->input('draw')), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
            }

            if ($context->is_director) {
                $query->where('company_id', $context->company_id);
            } elseif ($context->is_employee) {
                $query->where('employee_id', auth()->id());
            }
        }

        // 🟢 FIX: Admin ke liye extra filters backend me handle kar liye hain
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('branch_id') && $request->branch_id !== 'null') $query->where('branch_id', $request->branch_id);
        if ($request->filled('department_id')) $query->where('department_id', $request->department_id);
        if ($request->filled('designation_id')) $query->where('designation_id', $request->designation_id);

        if ($request->filled('employee_id')) $query->where('employee_id', $request->employee_id);
        if ($request->filled('month_start') && $request->filled('month_end')) {
            $query->whereBetween('visit_date', [$request->month_start, $request->month_end]);
        }

        $totalData = SiteVisit::count();
        $totalFiltered = $query->count();

        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $query->get()
        ]);
    }

    // ==========================================
    // 2. STORE ACTION (With visit_time)
    // ==========================================
    public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        $userPerms = self::getLiveActivePermissions(auth()->user());

        $hasDirect = $context->is_god || in_array('sv_add_direct', $userPerms);
        $hasRequest = in_array('sv_add_request', $userPerms);

        if (!$hasDirect && !$hasRequest) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized to add Site Visit!'], 403);
        }

        $request->validate([
            'company_id' => 'required',
            'department_id' => 'required',
            'designation_id' => 'required',
            'employee_id' => 'required',
            'phase_id' => 'required',
            'customer_name' => 'required|string|max:255', // 🔥 Ye line add karein
            'customer_contact_number' => 'required|string|max:20',
            'visit_date' => 'required|date',
            'visit_time' => 'required', // 🟢 Added Time validation
            'images.*' => 'nullable|file'
        ]);

        DB::beginTransaction();
        try {
            $status = $hasDirect ? 'active' : 'pending';
            $branchId = ($request->branch_id === 'null' || empty($request->branch_id)) ? null : $request->branch_id;

            $visit = SiteVisit::create([
                'company_id' => $request->company_id,
                'branch_id' => $branchId,
                'department_id' => $request->department_id,
                'designation_id' => $request->designation_id,
                'employee_id' => $request->employee_id,
                'phase_id' => $request->phase_id,
                'customer_name' => $request->customer_name,
                'customer_contact_number' => $request->customer_contact_number,
                'visit_date' => $request->visit_date,
                'visit_time' => $request->visit_time, // 🟢 Save Time
                'description' => $request->description,
                'status' => $status,
                'created_by' => auth()->id()
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $mediaRecord = $this->mediaConverter->uploadAndConvert($file);
                    if ($mediaRecord) {
                        SiteVisitImage::create([
                            'site_visit_id' => $visit->id,
                            'media_path' => $mediaRecord->file_path
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => $hasDirect ? 'Site Visit Added Successfully!' : 'Site Visit Requested (Pending Approval).']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function approve(Request $request, $id)
    { /* Same as before */
        $visit = SiteVisit::findOrFail($id);
        $visit->update(['status' => 'active', 'remarks' => $request->remarks, 'approved_by' => auth()->id()]);
        return response()->json(['status' => 'success', 'message' => 'Site Visit Approved!']);
    }

    public function reject(Request $request, $id)
    { /* Same as before */
        $visit = SiteVisit::findOrFail($id);
        $visit->update(['status' => 'inactive', 'remarks' => $request->remarks]);
        return response()->json(['status' => 'success', 'message' => 'Site Visit Rejected!']);
    }

    public function bulkDelete(Request $request)
    { /* Same as before */
        SiteVisit::whereIn('id', $request->ids)->delete();
        return response()->json(['status' => 'success', 'message' => 'Selected Site Visits Deleted!']);
    }

     public function calculateStats(Request $request)
    {
        $empId = $request->employee_id;
        $start = $request->month_start;
        $end = $request->month_end;

        if (!$empId || !$start || !$end) {
            return response()->json(['status' => 'error', 'message' => 'Missing parameters']);
        }

        $totalVisits = SiteVisit::where('employee_id', $empId)
                        ->whereBetween('visit_date', [$start, $end])
                        ->count();

        $approvedVisits = SiteVisit::where('employee_id', $empId)
                        ->where('status', 'active')
                        ->whereBetween('visit_date', [$start, $end])
                        ->count();

        // Match with settings table
        $settings = SiteVisitSetting::where('status', 'active')
                        ->where(function($q) use ($start, $end) {
                            $q->where('start_date', '<=', $end)->where('end_date', '>=', $start);
                        })->get();

        $totalAmount = 0;
        $approvedAmount = 0;

        foreach ($settings as $setting) {
            if ($totalVisits >= $setting->min_visits && ($setting->max_visits === null || $totalVisits <= $setting->max_visits)) {
                $totalAmount = $totalVisits * $setting->amount;
            }
            if ($approvedVisits >= $setting->min_visits && ($setting->max_visits === null || $approvedVisits <= $setting->max_visits)) {
                $approvedAmount = $approvedVisits * $setting->amount;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_visits' => $totalVisits,
                'approved_visits' => $approvedVisits,
                'total_amount' => $totalAmount,
                'approved_amount' => $approvedAmount
            ]
        ]);
    }


    public function deleteImage($id)
    {
        $image = SiteVisitImage::findOrFail($id);
        // Tum file system se delete karne ka logic yahan laga sakte ho (e.g. \File::delete(public_path($image->media_path));)
        $image->delete();
        return response()->json(['status' => 'success', 'message' => 'Image removed!']);
    }


    // ==========================================
    // 6. DYNAMIC CASCADING DROPDOWNS (FIXED)
    // ==========================================

    public function searchBranchForSV(Request $request)
    {
        // 🟢 FIX: No minimum length required. Load Head Office immediately.
        $query = \App\Models\Branch::where('company_id', $request->company_id)
            ->where('branch_status', 'active');

        if (!empty($request->q)) {
            $query->where('branch_name', 'LIKE', "%{$request->q}%");
        }

        $branches = $query->limit(20)->get(['id', 'branch_name']);

        // 🟢 ALWAYS Prepend Head Office
        $branches->prepend(['id' => 'null', 'branch_name' => 'Head Office (By Default)']);

        return response()->json(['data' => $branches]);
    }

    public function searchEmployeeForSV(Request $request)
    {
        $q = $request->q;
        if (strlen($q) < 3) return response()->json(['data' => []]);

        // 🟢 FIX: Safe Null Checks to prevent 500 Errors
        $branchId = ($request->branch_id === 'null' || $request->branch_id === '' || $request->branch_id === null) ? null : $request->branch_id;

        $query = Employee::where('emp_status', 'active')
            ->where('company_id', $request->company_id);

        // Sirf tabhi query me daalo jab id valid ho (warna fail ho jayega)
        if (!empty($request->department_id)) $query->where('department_id', $request->department_id);
        if (!empty($request->designation_id)) $query->where('designation_id', $request->designation_id);

        $query->where(function ($sq) use ($branchId) {
            if ($branchId === null) {
                $sq->whereNull('branch_id');
            } else {
                $sq->where('branch_id', $branchId);
            }
        });

        $employees = $query->where('full_name', 'LIKE', "%{$q}%")
            ->limit(20)
            ->get(['id', 'full_name', 'member_id']);

        return response()->json(['data' => $employees]);
    }

    public function searchPhaseForSV(Request $request)
    {
        $branchId = ($request->branch_id === 'null' || empty($request->branch_id)) ? null : $request->branch_id;
        $phases = \App\Models\Phase::where('company_id', $request->company_id)
            ->where(function ($sq) use ($branchId) {
                if ($branchId === null) {
                    $sq->whereNull('branch_id');
                } else {
                    $sq->where('branch_id', $branchId);
                }
            })->get(['id', 'phase_name']);
        return response()->json(['data' => $phases]);
    }

    // SiteVisitController.php me index() aur store() ke baad ye add karein:

    public function show($id)
    {
        $visit = SiteVisit::with(['company', 'branch', 'department', 'designation', 'employee', 'phase', 'images'])->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $visit]);
    }

    public function update(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $userPerms = self::getLiveActivePermissions(auth()->user());

        if (!$context->is_god && !in_array('sv_edit', $userPerms)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized to edit!'], 403);
        }

        $visit = SiteVisit::findOrFail($id);
        $visit->update([
            'company_id' => $request->company_id,
            'branch_id' => ($request->branch_id === 'null' || empty($request->branch_id)) ? null : $request->branch_id,
            'department_id' => $request->department_id,
            'designation_id' => $request->designation_id,
            'employee_id' => $request->employee_id,
            'phase_id' => $request->phase_id,
            'customer_name' => $request->customer_name,
            'customer_contact_number' => $request->customer_contact_number,
            'visit_date' => $request->visit_date,
            'visit_time' => $request->visit_time,
            'description' => $request->description,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $mediaRecord = $this->mediaConverter->uploadAndConvert($file);
                if ($mediaRecord) {
                    \App\Models\SiteVisitImage::create(['site_visit_id' => $visit->id, 'media_path' => $mediaRecord->file_path]);
                }
            }
        }
        return response()->json(['status' => 'success', 'message' => 'Site Visit Updated Successfully!']);
    }

   public function fetchScheduledCustomers(Request $request)
    {
        $date = $request->visit_date;
        
        // Agar admin kisi aur employee ke liye form bhar raha hai, toh select kiya hua employee_id use hoga.
        // Agar employee khud bhar raha hai (fields locked hain), toh uski apni login ID use hogi.
        $assigneeId = $request->employee_id ?? auth()->id();

        if (!$date || !$assigneeId) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $customers = DB::table('telecaller_allocations')
            ->join('interested_customers', 'telecaller_allocations.customer_id', '=', 'interested_customers.id')
            ->whereDate('telecaller_allocations.followup_date', $date) // 🟢 called_at ki jagah followup_date
            ->where('telecaller_allocations.assignee_id', $assigneeId) // 🟢 Logged in ya selected employee id
            ->where('telecaller_allocations.call_status', 'Site Visit Scheduled')
            ->select('interested_customers.id', 'interested_customers.cust_name', 'interested_customers.mobile')
            ->distinct() // Duplicate suggestions rokne ke liye
            ->get();

        return response()->json(['status' => 'success', 'data' => $customers]);
    }

  

}
