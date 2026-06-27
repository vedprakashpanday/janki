<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceAdminController extends Controller
{
    /**
     * MASTER ATTENDANCE MATRIX ENDPOINT
     */
    public function getFilteredAttendance(Request $request)
    {
        $user = auth()->user();

        // --- 1. PORTAL CONTEXT AUTHORIZATION SCOPES ---
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isGodAdmin = in_array(strtolower($user->email), $developerEmails) || (method_exists($user, 'hasRole') && $user->hasRole('CEO'));
        $isDirector = method_exists($user, 'hasRole') && $user->hasRole('Director');

        $empQuery = Employee::query()->where('emp_status', 'active');

        // Multi-Tenant Hierarchy Filtering Locks
        if ($isGodAdmin) {
            if ($request->filled('company_id')) $empQuery->where('company_id', $request->company_id);
            if ($request->filled('branch_id')) {
                if ($request->branch_id === 'HO') {
                    $empQuery->whereNull('branch_id');
                } else {
                    $empQuery->where('branch_id', $request->branch_id);
                }
            }
        } elseif ($isDirector) {
            $directorMapping = DB::table('company_director')->where('director_id', $user->id)->first();
            $lockedCompanyId = $directorMapping ? $directorMapping->company_id : $user->company_id;

            $empQuery->where('company_id', $lockedCompanyId);
            if ($request->filled('branch_id')) {
                if ($request->branch_id === 'HO') {
                    $empQuery->whereNull('branch_id');
                } else {
                    $empQuery->where('branch_id', $request->branch_id);
                }
            }
        } else {
            // Employee / Branch Manager
            $empQuery->where('company_id', $user->company_id);
            if ($user->branch_id) {
                $empQuery->where('branch_id', $user->branch_id);
            } else {
                $empQuery->whereNull('branch_id');
            }
        }

        if ($request->filled('department_id')) {
            $empQuery->where('department_id', $request->department_id);
        }

        $employees = $empQuery->get();

        if ($employees->isEmpty()) {
            return response()->json(['success' => true, 'matrix' => []]);
        }

        // --- 2. DATE RANGE CALCULATION (Fix: Allow Full Month) ---
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
        } else {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth(); // User asked to show full month
        }

        $dates = [];
        for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
            $dates[] = $d->copy();
        }

        // --- 3. BULK DATA FETCHING ---
        $empIds = $employees->pluck('member_id')->toArray();
        $empDbIds = $employees->pluck('id')->toArray();

        $attendances = Attendance::whereIn('user_id', $empIds)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()->groupBy('user_id');

        $corrections = AttendanceCorrection::whereIn('user_id', $empDbIds)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()->groupBy('user_id');

        // Holiday Query fixed to support start_date and end_date
        $holidays = Holiday::where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('start_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->orWhere(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $endDate->format('Y-m-d'))
                        ->where(function ($subQ) use ($startDate) {
                            $subQ->whereNull('end_date')
                                ->orWhere('end_date', '>=', $startDate->format('Y-m-d'));
                        });
                });
        })->get()->flatMap(function ($holiday) {
            $period = \Carbon\CarbonPeriod::create($holiday->start_date, $holiday->end_date ?? $holiday->start_date);
            $dts = [];
            foreach ($period as $dt) {
                $dts[] = $dt->format('Y-m-d');
            }
            return $dts;
        })->toArray();

        $leaves = LeaveApplication::whereIn('user_id', $empDbIds)
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_datetime', [$startDate->format('Y-m-d 00:00:00'), $endDate->format('Y-m-d 23:59:59')])
                    ->orWhereBetween('end_datetime', [$startDate->format('Y-m-d 00:00:00'), $endDate->format('Y-m-d 23:59:59')]);
            })->get()->groupBy('user_id');

        // --- 4. MATRIX GENERATION & SANDWICH LOGIC ---
        $matrix = [];
        $todayStr = Carbon::today()->format('Y-m-d');

        foreach ($employees as $emp) {
            $empAtt = $attendances->has($emp->member_id) ? $attendances->get($emp->member_id)->keyBy('date') : collect();
            $empCorr = $corrections->has($emp->id) ? $corrections->get($emp->id)->keyBy('date') : collect();
            $empLeaves = $leaves->has($emp->id) ? $leaves->get($emp->id) : collect();

            $stats = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'leave' => 0, 'extra_day' => 0];
            $rawRecords = [];

            // Joining Date Rule
            $joinDate = $emp->created_at ? Carbon::parse($emp->created_at)->startOfDay() : Carbon::parse('2000-01-01');

            foreach ($dates as $dateObj) {
                $dStr = $dateObj->format('Y-m-d');
                $isSunday = $dateObj->isSunday();
                $isHoliday = in_array($dStr, $holidays);
                $isFuture = $dStr > $todayStr;

                $status = 'A';
                $remark = '';
                $inTime = null;
                $outTime = null;

                // Priority 0: Future Dates OR Before Joining Date
                if ($isFuture || $dateObj->lt($joinDate)) {
                    $status = 'N/A'; // N/A will be rendered as '-' in UI
                    if ($isFuture && $isSunday) {
                        $status = 'WO';
                    } elseif ($isFuture && $isHoliday) {
                        $status = 'HO';
                    }
                }
                // Priority 1: Manual Correction by Admin
                elseif ($empCorr->has($dStr)) {
                    $corr = $empCorr->get($dStr);
                    $status = $corr->corrected_status;
                    $remark = "Corrected: " . $corr->reason;
                    if ($status == 'P') $stats['present']++;
                    elseif ($status == 'HD') $stats['half_day']++;
                    elseif ($status == 'L') $stats['leave']++;
                    elseif ($status == 'A') $stats['absent']++;
                    elseif ($status == 'WO' || $status == 'HO') $stats['extra_day']++;
                }
                // Priority 2: Actual Machine/Web Punch
                elseif ($empAtt->has($dStr)) {
                    $att = $empAtt->get($dStr);
                    $inTime = $att->login_time ? date('h:i A', strtotime($att->login_time)) : null;
                    $outTime = $att->logout_time ? date('h:i A', strtotime($att->logout_time)) : null;

                    if ($isSunday || $isHoliday) {
                        $status = 'ED';
                        $remark = 'Worked on ' . ($isSunday ? 'Sunday' : 'Holiday');
                        $stats['extra_day']++;
                    }
                    // 🔥 STRICT RULE: Login null + Logout done = Half Day
                    elseif (empty($att->login_time) && !empty($att->logout_time)) {
                        $status = 'HD';
                        $remark = 'Missed Punch-In';
                        $stats['half_day']++;
                    }
                    // 🔥 STRICT RULE: No login and no logout = Absent
                    elseif (empty($att->login_time) && empty($att->logout_time)) {
                        $status = 'A';
                        $remark = 'Did not punch In/Out';
                        $stats['absent']++;
                    } elseif ($att->half_day == 1) {
                        $status = 'HD';
                        $remark = 'Half Day Punch';
                        $stats['half_day']++;
                    } elseif ($att->present == 1) {
                        $status = 'P';
                        $remark = 'On Time';
                        $stats['present']++;
                    } else {
                        $status = 'A';
                        $stats['absent']++;
                    }
                }
                // Priority 3: Leave Approved
                elseif ($empLeaves->isNotEmpty()) {
                    $onLeave = false;
                    foreach ($empLeaves as $leave) {
                        $lStart = Carbon::parse($leave->start_datetime)->format('Y-m-d');
                        $lEnd = Carbon::parse($leave->end_datetime)->format('Y-m-d');
                        if ($dStr >= $lStart && $dStr <= $lEnd) {
                            $onLeave = true;
                            $status = 'L';
                            $remark = 'Approved Leave';
                            $stats['leave']++;
                            break;
                        }
                    }

                    if (!$onLeave) {
                        if ($isSunday) {
                            $status = 'WO';
                            $remark = 'Weekly Off';
                        } elseif ($isHoliday) {
                            $status = 'HO';
                            $remark = 'Holiday';
                        } else {
                            $status = 'A';
                            $stats['absent']++;
                        }
                    }
                }
                // Priority 4: Default Check (Sunday, Holiday or Absent)
                else {
                    if ($isSunday) {
                        $status = 'WO';
                        $remark = 'Weekly Off';
                    } elseif ($isHoliday) {
                        $status = 'HO';
                        $remark = 'Holiday';
                    } else {
                        $status = 'A';
                        $stats['absent']++;
                    }
                }

                if ($empAtt->has($dStr)) {
                    $att = $empAtt->get($dStr);
                    $rawRecords[$dStr] = [
                        'id' => $att->id,
                        'status' => $status,
                        'remark' => $remark,
                        'in' => $inTime,
                        'out' => $outTime,
                        'lat' => $att->latitude,
                        'lng' => $att->longitude,
                        'reason' => $att->punch_reason,
                        'proof_images' => $att->punch_proof_images,
                        'verification_status' => $att->hr_verification_status
                    ];
                } else {
                    $rawRecords[$dStr] = [
                        'id' => null,
                        'status' => $status,
                        'remark' => $remark,
                        'in' => null,
                        'out' => null,
                        'lat' => null,
                        'lng' => null,
                        'reason' => null,
                        'proof_images' => null,
                        'verification_status' => 'none'
                    ];
                }
            }

            // --- 5. THE SANDWICH RULE ENGINE (Post-Calculation) ---
            $finalDatesRecord = [];
            for ($i = 0; $i < count($dates); $i++) {
                $dStr = $dates[$i]->format('Y-m-d');
                $currRecord = $rawRecords[$dStr];

                if ($currRecord['status'] === 'WO' || $currRecord['status'] === 'HO') {
                    $prevStatus = ($i > 0) ? $rawRecords[$dates[$i - 1]->format('Y-m-d')]['status'] : 'P';
                    $nextStatus = ($i < count($dates) - 1) ? $rawRecords[$dates[$i + 1]->format('Y-m-d')]['status'] : 'P';

                    $prevAbsent = in_array($prevStatus, ['A', 'L']);
                    $nextAbsent = in_array($nextStatus, ['A', 'L']);

                    if ($prevAbsent && $nextAbsent) {
                        $currRecord['status'] = 'A';
                        $currRecord['remark'] = 'Sandwich Rule Applied';
                        // Stats me se extra_day hata ke absent badhana padega agar count ho gaya tha
                        if ($rawRecords[$dStr]['status'] == 'WO' || $rawRecords[$dStr]['status'] == 'HO') {
                            if (isset($stats['extra_day']) && $stats['extra_day'] > 0) {
                                $stats['extra_day']--;
                            }
                        }
                        $stats['absent']++;
                    }
                }
                $finalDatesRecord[$dStr] = $currRecord;
            }

            // 🔥 USER KI DEMAND: Name and Dept/Desig correctly map karein
            $exactName = !empty($emp->full_name) ? $emp->full_name : ($emp->name ?? 'Unknown');

            $exactDept = 'N/A';
            if (!empty($emp->department_id)) {
                $deptRecord = DB::table('departments')->where('id', $emp->department_id)->first();
                if ($deptRecord) $exactDept = $deptRecord->department_name;
            }

            $exactDesig = 'N/A';
            if (!empty($emp->designation_id)) {
                $desigRecord = DB::table('designations')->where('id', $emp->designation_id)->first();
                if ($desigRecord) $exactDesig = $desigRecord->designation_name;
            }

            $matrix[] = [
                'employee' => [
                    'db_id' => $emp->id,
                    'member_id' => $emp->member_id,
                    'name' => $exactName,
                    'department' => $exactDept,
                    'designation' => $exactDesig,
                ],
                'stats' => $stats,
                'dates' => $finalDatesRecord
            ];
        }

        return response()->json([
            'success' => true,
            'matrix' => $matrix,
            'dates_list' => array_keys($matrix[0]['dates'] ?? [])
        ]);
    }

    /**
     * MANUAL OVERRIDE ENDPOINT
     */
    public function saveCorrection(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:adm_regist,id',
            'date' => 'required|date',
            'corrected_status' => 'required|in:P,A,HD,L,WO',
            'reason' => 'required|string'
        ]);

        AttendanceCorrection::updateOrCreate(
            ['user_id' => $request->employee_id, 'date' => $request->date],
            [
                'corrected_status' => $request->corrected_status,
                'reason' => $request->reason,
                'action_by' => auth()->user()->id
            ]
        );

        return response()->json(['success' => true, 'message' => 'Attendance manually corrected and locked!']);
    }

    /**
     * HR VERIFICATION ENDPOINT FOR PENDING PUNCHES
     */
    public function verifyPendingPunch(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'action_status' => 'required|in:approved,rejected', // Action on the proof
            'final_attendance_status' => 'required|in:P,HD,A,L', // Final status HR wants to give
            'hr_remark' => 'required|string|min:5' // Mandatory reason from HR
        ]);

        DB::beginTransaction();
        try {
            $attendance = Attendance::find($request->attendance_id);

            // Update actual attendance record
            $attendance->hr_verification_status = $request->action_status;
            $attendance->hr_remark = $request->hr_remark;

            // Reset all flags first
            $attendance->present = 0;
            $attendance->absent = 0;
            $attendance->half_day = 0;
            $attendance->leave = 0;

            // Apply new HR decided status
            if ($request->final_attendance_status == 'P') $attendance->present = 1;
            elseif ($request->final_attendance_status == 'HD') $attendance->half_day = 1;
            elseif ($request->final_attendance_status == 'A') $attendance->absent = 1;
            elseif ($request->final_attendance_status == 'L') $attendance->leave = 1;

            $attendance->save();

            // Log into Corrections table for Auditing / History
            $emp = \App\Models\Employee::where('member_id', $attendance->user_id)->first();
            if ($emp) {
                \App\Models\AttendanceCorrection::updateOrCreate(
                    ['user_id' => $emp->id, 'date' => $attendance->date],
                    [
                        'corrected_status' => $request->final_attendance_status,
                        'reason' => 'HR Verification: ' . $request->hr_remark,
                        'action_by' => auth()->user()->id
                    ]
                );
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Attendance Verified & Updated Successfully!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
