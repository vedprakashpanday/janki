<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Member;
use App\Models\MemberAttendance;
use Carbon\Carbon;

class MemberAttendanceAdminController extends Controller
{
    // 1. Get Companies
    public function getCompanies(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Company::where('status', 'active');
        
        if (!$context->is_god) {
            $query->where('id', $context->company_id);
        }
        
        return response()->json(['status' => 'success', 'data' => $query->get(['id', 'company_name'])]);
    }

    // 2. Get Branches (Depends on Company)
    public function getBranches(Request $request)
    {
        $companyIds = $request->company_ids ?? [];
        
        $query = Branch::where('branch_status', 'active');
        
        if (!empty($companyIds)) {
            $query->whereIn('company_id', $companyIds);
        }
        
        return response()->json(['status' => 'success', 'data' => $query->get(['id', 'branch_name', 'company_id'])]);
    }

   // 3. Get Departments (Filtered by 'associate')
    public function getDepartments(Request $request)
    {
        $companyIds = $request->company_ids ?? [];
        $branchIds = $request->branch_ids ?? [];
        
        // 🔥 SIRF 'associate' WALE DEPARTMENTS LAO 🔥
        $query = Department::where('status', 'active')
                           ->where('department_name', 'LIKE', '%associate%');
        
        if (!empty($companyIds)) {
            $query->where(function($q) use ($companyIds) {
                $q->whereNull('company_ids')->orWhereJsonContains('company_ids', 'all');
                foreach ($companyIds as $cId) {
                    $q->orWhereJsonContains('company_ids', (string)$cId)->orWhereJsonContains('company_ids', (int)$cId);
                }
            });
        }
        
        // HO Logic
        if (!empty($branchIds)) {
            $hasHO = in_array('HO', $branchIds);
            $query->where(function($q) use ($branchIds, $hasHO) {
                if ($hasHO) {
                    $q->whereNull('branch_ids')->orWhereJsonContains('branch_ids', null);
                }
                foreach ($branchIds as $bId) {
                    if ($bId !== 'HO') {
                        $q->orWhereJsonContains('branch_ids', (string)$bId)->orWhereJsonContains('branch_ids', (int)$bId);
                    }
                }
            });
        }
        
        return response()->json(['status' => 'success', 'data' => $query->get(['id', 'department_name'])]);
    }

    // 4. Get Designations (Depends on Department)
    public function getDesignations(Request $request)
    {
        $deptIds = $request->department_ids ?? [];
        
        $query = Designation::where('status', 'active');
        
        if (!empty($deptIds)) {
            $query->whereIn('department_id', $deptIds);
        }
        
        return response()->json(['status' => 'success', 'data' => $query->get(['id', 'designation_name'])]);
    }

  public function getMembers(Request $request)
    {
        $query = Member::where('status', 'active');
        
        if (!empty($request->company_ids)) $query->whereIn('company_id', $request->company_ids);
        
        if (!empty($request->branch_ids)) {
            $hasHO = in_array('HO', $request->branch_ids);
            $normalBranchIds = array_filter($request->branch_ids, fn($val) => $val !== 'HO');
            
            $query->where(function($q) use ($normalBranchIds, $hasHO) {
                if (!empty($normalBranchIds)) $q->whereIn('branch_id', $normalBranchIds);
                if ($hasHO) $q->orWhereNull('branch_id');
            });
        }
        
        if (!empty($request->department_ids)) $query->whereIn('department_id', $request->department_ids);
        if (!empty($request->designation_ids)) $query->whereIn('designation_id', $request->designation_ids);
        
        return response()->json(['status' => 'success', 'data' => $query->get(['id', 'member_name', 'member_id'])]);
    }

    // 6. 🟢 LOAD MATRIX DATA (Updated for Premium UI)
    public function loadMatrix(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Agar date range nahi hai, toh Month/Year use karein
        if (!$startDate || !$endDate) {
            $monthYear = $request->month_year ?? date('Y-m');
            $parsedDate = Carbon::parse($monthYear . '-01');
            $startDate = $parsedDate->copy()->startOfMonth()->toDateString();
            $endDate = $parsedDate->copy()->endOfMonth()->toDateString();
        }

        // Generate array of all dates in range
        $datesList = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        while ($current <= $end) {
            $datesList[] = $current->toDateString();
            $current->addDay();
        }

        // Filter Members
        $query = Member::with('designation')->where('status', 'active');
        
        if (!empty($request->company_id)) $query->where('company_id', $request->company_id);
        if (!empty($request->branch_id)) {
            if ($request->branch_id === 'HO') $query->whereNull('branch_id');
            else $query->where('branch_id', $request->branch_id);
        }
        if (!empty($request->department_id)) $query->where('department_id', $request->department_id);
        
        $members = $query->get();
        $memberIds = $members->pluck('id')->toArray();
        
        // Fetch Attendance
        $attendances = MemberAttendance::whereIn('member_id', $memberIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
            
        $matrixData = [];
        
        foreach ($members as $member) {
            $desigName = is_object($member->designation) ? ($member->designation->designation_name ?? 'N/A') : (is_string($member->designation) ? $member->designation : 'N/A');

            $memberRow = [
                'employee' => [
                    'db_id' => $member->id,
                    'member_id' => $member->member_id ?? 'N/A',
                    'name' => $member->member_name,
                    'department' => $desigName, // Showing designation in department line for UI fit
                ],
                'joining_date' => $member->created_at ? $member->created_at->toDateString() : null,
                'dates' => [],
                'stats' => ['present' => 0, 'absent' => 0, 'leave' => 0, 'sl' => 0]
            ];
            
            foreach ($datesList as $dateStr) {
                $record = $attendances->where('member_id', $member->id)->where('date', $dateStr)->first();
                $isTuesday = Carbon::parse($dateStr)->dayOfWeek === Carbon::TUESDAY;
                
                if ($record) {
                    $status = strtoupper($record->status);
                    if ($status === 'PRESENT') { $memberRow['stats']['present']++; $status = 'P'; }
                    elseif ($status === 'ABSENT') { $memberRow['stats']['absent']++; $status = 'A'; }
                    elseif ($status === 'LEAVE') { $memberRow['stats']['leave']++; $status = 'L'; }
                    elseif ($status === 'SL') { $memberRow['stats']['sl']++; $status = 'SL'; }
                    else { $status = substr($status, 0, 1); } // Fallback to first letter

                    $memberRow['dates'][$dateStr] = [
                        'status' => $status,
                        'in' => $record->punch_in_time ? Carbon::parse($record->punch_in_time)->format('h:i a') : null,
                        'out' => $record->punch_out_time ? Carbon::parse($record->punch_out_time)->format('h:i a') : null,
                       // 👇 YE 4 LINES ADD KARNI HAIN 👇
                    'lat' => $record->punch_in_latitude,
                    'lng' => $record->punch_in_longitude,
                    'out_lat' => $record->punch_out_latitude,
                    'out_lng' => $record->punch_out_longitude,
                        'remark' => $record->remarks
                    ];
                } else {
                    if ($isTuesday) {
                        $memberRow['dates'][$dateStr] = ['status' => 'WO', 'in' => null, 'out' => null];
                    } else {
                        $memberRow['dates'][$dateStr] = ['status' => 'N/A', 'in' => null, 'out' => null];
                    }
                }
            }
            $matrixData[] = $memberRow;
        }

        return response()->json([
            'success' => true, // Using 'success' to match employee JS logic
            'dates_list' => $datesList,
            'matrix' => $matrixData
        ]);
    }

    // 7. 🟢 GET ROUTE TRACKING DATA
    public function getMemberRoute(Request $request)
    {
        $memberId = $request->member_id;
        $date = $request->date;

        // Punch In aur Punch Out ki locations lene ke liye
        $attendance = MemberAttendance::where('member_id', $memberId)
                                      ->where('date', $date)
                                      ->first();

        
  // 🔴 Route tracking disabled. Sirf Punch In/Out point return karenge.
        return response()->json([
            'status' => 'success',
            'punch_in' => [
                'lat' => $attendance->punch_in_latitude ?? null,
                'lng' => $attendance->punch_in_longitude ?? null,
                'time' => $attendance->punch_in_time ? Carbon::parse($attendance->punch_in_time)->format('h:i a') : null
            ],
            'punch_out' => [
                'lat' => $attendance->punch_out_latitude ?? null,
                'lng' => $attendance->punch_out_longitude ?? null,
                'time' => $attendance->punch_out_time ? Carbon::parse($attendance->punch_out_time)->format('h:i a') : null
            ],
            'route' => [] // Khaali array bhej diya taaki map fail na ho
        ]);
    }

}