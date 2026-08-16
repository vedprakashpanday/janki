<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visitor;
use App\Models\InterestedCustomer;
use App\Models\SuperAdmin;
use App\Models\Director;
use App\Models\Member;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Services\MediaConverterService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VisitorController extends Controller
{
    protected $mediaConverter;

    public function __construct(MediaConverterService $mediaConverter)
    {
        $this->mediaConverter = $mediaConverter;
    }

    // 🟢 1. TODAY'S DATA 
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Visitor::with(['company', 'branch'])->whereDate('visiting_date', Carbon::today());

        if (!$context->is_god && !$context->is_director) {
            $query->where('company_id', $context->company_id);
            if ($context->branch_id) {
                $query->where('branch_id', $context->branch_id);
            }
        }

        $totalData = $query->count();
        $totalFiltered = $totalData;

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('visitor_name', 'LIKE', "%{$search}%")
                  ->orWhere('visitor_mobile', 'LIKE', "%{$search}%")
                  ->orWhere('purpose', 'LIKE', "%{$search}%")
                  ->orWhere('whom_to_meet', 'LIKE', "%{$search}%");
            });
            $totalFiltered = $query->count();
        }

        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        return response()->json([
            "draw" => intval($request->input('draw', 0)),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $query->orderBy('time_in', 'desc')->get()
        ]);
    }

    // 🟢 2. ALL TIME DATA 
    public function directory(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Visitor::with(['company', 'branch']);

        if (!$context->is_god && !$context->is_director) {
            $query->where('company_id', $context->company_id);
            if ($context->branch_id) {
                $query->where('branch_id', $context->branch_id);
            }
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('branch_id')) {
            if ($request->branch_id === 'null' || $request->branch_id === null) $query->whereNull('branch_id');
            else $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('date')) $query->whereDate('visiting_date', $request->date);
        if ($request->filled('month')) {
            $query->whereMonth('visiting_date', date('m', strtotime($request->month)))
                  ->whereYear('visiting_date', date('Y', strtotime($request->month)));
        }

        $totalData = $query->count();
        $totalFiltered = $totalData;

        if ($request->has('search') && $request->input('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('visitor_name', 'LIKE', "%{$search}%")
                  ->orWhere('visitor_mobile', 'LIKE', "%{$search}%")
                  ->orWhere('purpose', 'LIKE', "%{$search}%")
                  ->orWhere('whom_to_meet', 'LIKE', "%{$search}%");
            });
            $totalFiltered = $query->count();
        }

        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        return response()->json([
            'draw' => intval($request->input('draw', 0)),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => $query->orderBy('visiting_date', 'desc')->orderBy('time_in', 'desc')->get()
        ]);
    }

    // 🟢 3. STORE NEW VISITOR & AUTO LEAD SYNC
    public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        
        $request->validate([
            'visitor_name' => 'required|string|max:255',
            'visitor_mobile' => 'required|string|max:20',
        ]);

        DB::beginTransaction();
        try {
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $mediaRecord = $this->mediaConverter->uploadAndConvert($request->file('photo'));
                if ($mediaRecord) $photoPath = $mediaRecord->file_path;
            }

            $visitingDate = $request->visiting_date ?? now()->toDateString();
            $timeIn = $request->time_in ? ($visitingDate . ' ' . $request->time_in . ':00') : now()->toDateTimeString();
            $timeOut = $request->filled('time_out') ? ($visitingDate . ' ' . $request->time_out . ':00') : null;

            $branchId = ($request->branch_id === 'null') ? null : $request->branch_id;
            $finalCompanyId = (!$context->is_god && !$context->is_developer) ? $context->company_id : $request->company_id;
            $finalBranchId = (!$context->is_god && !$context->is_developer) ? $context->branch_id : $branchId;

            Visitor::create([
                'company_id' => $finalCompanyId,
                'branch_id' => $finalBranchId,
                'visitor_name' => $request->visitor_name,
                'no_of_visitors' => $request->no_of_visitors ?? 1,
                'visitor_address' => $request->visitor_address,
                'visitor_mobile' => $request->visitor_mobile,
                'purpose' => $request->purpose,
                'person_department' => $request->person_department,
                'whom_to_meet' => $request->whom_to_meet,
                'photo' => $photoPath,
                'visiting_date' => $visitingDate, 
                'time_in' => $timeIn,             
                'time_out' => $timeOut,           
                'created_by' => auth()->id()
            ]);

            // 🔥 AUTO-SYNC WITH GENERAL LEADS
            $leadExists = InterestedCustomer::where('mobile', $request->visitor_mobile)->exists();
            
            if (!$leadExists) {
                // Generate next Provider ID
                $latest = InterestedCustomer::where('provider_id', 'like', 'Pro_%')
                    ->pluck('provider_id')
                    ->map(function ($id) { return (int) str_replace('Pro_', '', $id); })->max();
                $nextProviderId = 'Pro_' . str_pad(($latest ? $latest + 1 : 1), 2, '0', STR_PAD_LEFT);

                InterestedCustomer::create([
                    'company_id' => $finalCompanyId,
                    'branch_id' => $finalBranchId,
                    'cust_name' => $request->visitor_name,
                    'mobile' => $request->visitor_mobile,
                    'address' => $request->visitor_address,
                    'provider_name' => "Visitor's Entry",
                    'provider_id' => $nextProviderId,                    
                    'status' => 'General', 
                    'entry_status' => 'active'
                    // 'created_by' => auth()->id()
                ]);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Visitor entry added successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 🟢 4. SEARCH HOST FOR WHOM TO MEET
    public function searchHost(Request $request)
    {
        $dept = $request->department;
        $q = $request->q;
        $companyId = $request->company_id;
        $branchId = ($request->branch_id === 'null' || empty($request->branch_id)) ? null : $request->branch_id;

        $results = [];

        if (strlen($q) < 2) return response()->json([]);

        if ($dept === 'CEO') {
            $data = SuperAdmin::where('full_name', 'like', "%{$q}%")               
                ->get();
            foreach($data as $d) {
                $name = $d->full_name ?? $d->name;
                $results[] = ['id' => $d->id, 'full_name' => $name, 'unique_id' => ($d->ceo_id ?? 'CEO_01')];
            }
        } 
        elseif ($dept === 'Director') {
            $data = Director::where(function($sq) use($q) {
                    $sq->where('name', 'like', "%{$q}%")->orWhere('full_name', 'like', "%{$q}%");
                })->where('company_id', $companyId)->get();
            foreach($data as $d) {
                $name = $d->full_name ?? $d->name;
                $results[] = ['id' => $d->id, 'full_name' => $name, 'unique_id' => ($d->director_id ?? 'DIR_01')];
            }
        }
        elseif ($dept === 'Member') {
            $query = Member::where('member_name', 'like', "%{$q}%")->where('company_id', $companyId);
            if ($branchId) $query->where('branch_id', $branchId);
            $data = $query->get();
            foreach($data as $d) {
                $results[] = ['id' => $d->id, 'full_name' => $d->member_name, 'unique_id' => $d->member_id];
            }
        }
        elseif ($dept === 'Administrative Employee') {
            $query = Employee::where('full_name', 'like', "%{$q}%")->where('company_id', $companyId);
            if ($branchId) $query->where('branch_id', $branchId);
            $data = $query->get();
            foreach($data as $d) {
                $results[] = ['id' => $d->id, 'full_name' => $d->full_name, 'unique_id' => $d->member_id];
            }
        }

        return response()->json($results);
    }

    // 🟢 5. MANUAL PUSH TO LEADS 
    public function addGeneralLead(Request $request)
    {
        $request->validate(['visitor_ids' => 'required|array']);

        DB::beginTransaction();
        try {
            $visitors = Visitor::whereIn('id', $request->visitor_ids)->get();
            $addedCount = 0;

            foreach ($visitors as $visitor) {
                $exists = InterestedCustomer::where('mobile', $visitor->visitor_mobile)->exists();
                
                if (!$exists) {
                    $latest = InterestedCustomer::where('provider_id', 'like', 'Pro_%')
                        ->pluck('provider_id')
                        ->map(function ($id) { return (int) str_replace('Pro_', '', $id); })->max();
                    $nextProviderId = 'Pro_' . str_pad(($latest ? $latest + 1 : 1), 2, '0', STR_PAD_LEFT);

                    InterestedCustomer::create([
                        'company_id' => $visitor->company_id,
                        'branch_id' => $visitor->branch_id,
                        'cust_name' => $visitor->visitor_name,
                        'mobile' => $visitor->visitor_mobile,
                        'address' => $visitor->visitor_address,
                        'provider_name' => "Visitor's Entry",
                        'provider_id' => $nextProviderId,
                        'source' => 'Visitor Register',
                        'status' => 'General', 
                        'entry_status' => 'active',
                        'created_by' => auth()->id()
                    ]);
                    $addedCount++;
                }
            }
            DB::commit();
            return response()->json(['status' => 'success', 'message' => "$addedCount visitors successfully added to General Leads!"]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 🟢 6. DELETE VISITOR
    public function destroy(Request $request)
    {
        $ids = $request->ids; 
        if (empty($ids)) return response()->json(['status' => 'error', 'message' => 'No visitors selected!'], 400);

        Visitor::whereIn('id', $ids)->delete();
        return response()->json(['status' => 'success', 'message' => 'Selected visitors deleted!']);
    }

    // 🟢 7. PRINT PREVIEW & SUMMARY
    public function printPreview(Request $request)
    {
        if (!auth()->check() && $request->has('token')) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($accessToken) auth()->login($accessToken->tokenable);
        }

        $context = $this->getGlobalContext();
        if (!$context) return response("Unauthorized Access! Please login again.", 401);

        $query = Visitor::with(['company', 'branch']);

        if (!$context->is_god && !$context->is_director) {
            $query->where('company_id', $context->company_id);
            if ($context->branch_id) $query->where('branch_id', $context->branch_id);
        }

        if ($request->time_scope === 'today') {
            $query->whereDate('visiting_date', Carbon::today());
        } else {
            if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
            if ($request->filled('branch_id')) {
                if ($request->branch_id === 'null') $query->whereNull('branch_id');
                else $query->where('branch_id', $request->branch_id);
            }
            if ($request->filled('date')) $query->whereDate('visiting_date', $request->date);
            if ($request->filled('month')) {
                $query->whereMonth('visiting_date', date('m', strtotime($request->month)))
                      ->whereYear('visiting_date', date('Y', strtotime($request->month)));
            }
        }

        $visitors = $query->orderBy('visiting_date', 'asc')->orderBy('time_in', 'asc')->get();

        $companyId = $request->filled('company_id') ? $request->company_id : ($context->company_id ?? 1);
        $branchId = $request->filled('branch_id') && $request->branch_id !== 'null' ? $request->branch_id : ($context->branch_id ?? null);
        
        $company = \App\Models\Company::find($companyId);
        $branch = $branchId ? \App\Models\Branch::find($branchId) : null;

        $summary = [];
        $grandTotalPax = 0;
        
        foreach ($visitors as $v) {
            $loc = !empty($v->visitor_address) ? trim(strtoupper($v->visitor_address)) : 'UNKNOWN LOCATION';
            $date = \Carbon\Carbon::parse($v->visiting_date)->format('d-m-Y');
            $pax = intval($v->no_of_visitors) ?: 1;

            $grandTotalPax += $pax;

            if (!isset($summary[$loc])) $summary[$loc] = [];
            if (!isset($summary[$loc][$date])) $summary[$loc][$date] = 0;
            $summary[$loc][$date] += $pax;
        }

       return view('shared.visitors.print', compact('visitors', 'company', 'branch', 'summary', 'grandTotalPax'));
    }

    // 🟢 8. SHOW SINGLE VISITOR
    public function show($id)
    {
        $context = $this->getGlobalContext();
        $visitor = Visitor::with(['company', 'branch'])->find($id);

        if (!$visitor) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);

        if (!$context->is_god && !$context->is_director) {
            if ($visitor->company_id != $context->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        return response()->json(['status' => 'success', 'data' => $visitor]);
    }

    // 🟢 9. UPDATE VISITOR
    public function update(Request $request, $id)
    {
        $context = $this->getGlobalContext();
        $visitor = Visitor::find($id);

        if (!$visitor) return response()->json(['status' => 'error', 'message' => 'Not found'], 404);

        if (!$context->is_god && !$context->is_director) {
            if ($visitor->company_id != $context->company_id) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized Scope!'], 403);
            }
        }

        $request->validate([
            'visitor_name' => 'required|string|max:255',
            'visitor_mobile' => 'required|string|max:20',
        ]);

        DB::beginTransaction();
        try {
            $photoPath = $visitor->photo;

            if ($request->hasFile('photo')) {
                $mediaRecord = $this->mediaConverter->uploadAndConvert($request->file('photo'));
                if ($mediaRecord) $photoPath = $mediaRecord->file_path;
            } elseif ($request->remove_photo == '1') {
                $photoPath = null;
            }

            $visitingDate = $request->visiting_date ?? now()->toDateString();
            $timeIn = $request->time_in ? ($visitingDate . ' ' . $request->time_in . ':00') : now()->toDateTimeString();
            $timeOut = $request->filled('time_out') ? ($visitingDate . ' ' . $request->time_out . ':00') : null;

            $branchId = ($request->branch_id === 'null') ? null : $request->branch_id;
            $finalCompanyId = (!$context->is_god && !$context->is_developer) ? $context->company_id : $request->company_id;
            $finalBranchId = (!$context->is_god && !$context->is_developer) ? $context->branch_id : $branchId;

            // Update record
            $visitor->update([
                'company_id' => $finalCompanyId,
                'branch_id' => $finalBranchId,
                'visitor_name' => $request->visitor_name,
                'no_of_visitors' => $request->no_of_visitors ?? 1,
                'visitor_address' => $request->visitor_address,
                'visitor_mobile' => $request->visitor_mobile,
                'purpose' => $request->purpose,
                'person_department' => $request->person_department,
                'whom_to_meet' => $request->whom_to_meet,
                'photo' => $photoPath,
                'visiting_date' => $visitingDate,
                'time_in' => $timeIn,
                'time_out' => $timeOut
            ]);

            // 🔥 SYNC UPDATES WITH LEAD
            $existingLead = InterestedCustomer::where('mobile', $request->visitor_mobile)->first();
            if ($existingLead) {
                $existingLead->update([
                    'cust_name' => $request->visitor_name,
                    'address' => $request->visitor_address,
                    'company_id' => $finalCompanyId,
                    'branch_id' => $finalBranchId,
                ]);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Visitor details updated successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 🟢 10. EXPORT EXCEL/CSV
    public function export(Request $request)
    {
        if (!auth()->check() && $request->has('token')) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($accessToken) auth()->login($accessToken->tokenable);
        }

        $context = $this->getGlobalContext();
        if (!$context) return response("Unauthorized Access!", 401);

        $query = Visitor::with(['company', 'branch']);

        if (!$context->is_god && !$context->is_director) {
            $query->where('company_id', $context->company_id);
            if ($context->branch_id) $query->where('branch_id', $context->branch_id);
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('branch_id')) {
            if ($request->branch_id === 'null') $query->whereNull('branch_id');
            else $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('date')) $query->whereDate('visiting_date', $request->date);
        if ($request->filled('month')) {
            $query->whereMonth('visiting_date', date('m', strtotime($request->month)))
                  ->whereYear('visiting_date', date('Y', strtotime($request->month)));
        }

        $visitors = $query->orderBy('visiting_date', 'desc')->orderBy('time_in', 'desc')->get();

        $fileName = 'Visitor_Directory_Export_' . date('Y-m-d_H-i') . '.csv';
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['S.No', 'Date', 'Time In', 'Time Out', 'Visitor Name', 'Pax (No. of Visitors)', 'Mobile', 'Company', 'Branch', 'Purpose', 'Department', 'Whom to Meet', 'Address'];

        $callback = function() use($visitors, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            $count = 1;

            foreach ($visitors as $v) {
                $cName = $v->company ? $v->company->company_code : 'Unknown';
                $bName = $v->branch ? $v->branch->branch_name : 'Head Office';
                $date = \Carbon\Carbon::parse($v->visiting_date)->format('d-m-Y');
                $timeIn = \Carbon\Carbon::parse($v->time_in)->format('h:i A');
                $timeOut = $v->time_out ? \Carbon\Carbon::parse($v->time_out)->format('h:i A') : '-';

                fputcsv($file, [
                    $count++,
                    $date,
                    $timeIn,
                    $timeOut,
                    $v->visitor_name,
                    $v->no_of_visitors,
                    $v->visitor_mobile,
                    $cName,
                    $bName,
                    $v->purpose,
                    $v->person_department, 
                    $v->whom_to_meet,
                    $v->visitor_address
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // 🟢 11. HISTORY LOGIC
    public function history(Request $request)
    {
        $request->validate(['mobile' => 'required']);
        $context = $this->getGlobalContext();
        
        $query = Visitor::with(['company', 'branch'])->where('visitor_mobile', $request->mobile);

        if (!$context->is_god && !$context->is_director) {
            $query->where('company_id', $context->company_id);
            if ($context->branch_id) $query->where('branch_id', $context->branch_id);
        }

        $history = $query->orderBy('visiting_date', 'desc')->orderBy('time_in', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }
}