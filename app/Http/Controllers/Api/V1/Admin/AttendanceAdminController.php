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
    public function getFilteredAttendance(Request $request)
    {
        $user = auth()->user();
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isGodAdmin = in_array(strtolower($user->email), $developerEmails) || (method_exists($user, 'hasRole') && $user->hasRole('CEO'));
        $isDirector = method_exists($user, 'hasRole') && $user->hasRole('Director');

        $empQuery = Employee::query()->where('emp_status', 'active');
        if ($isGodAdmin) {
            if ($request->filled('company_id')) $empQuery->where('company_id', $request->company_id);
            if ($request->filled('branch_id')) { if ($request->branch_id === 'HO') $empQuery->whereNull('branch_id'); else $empQuery->where('branch_id', $request->branch_id); }
        } elseif ($isDirector) {
            $directorMapping = DB::table('company_director')->where('director_id', $user->id)->first();
            $empQuery->where('company_id', $directorMapping ? $directorMapping->company_id : $user->company_id);
            if ($request->filled('branch_id')) { if ($request->branch_id === 'HO') $empQuery->whereNull('branch_id'); else $empQuery->where('branch_id', $request->branch_id); }
        } else {
            $empQuery->where('company_id', $user->company_id);
            if ($user->branch_id) $empQuery->where('branch_id', $user->branch_id); else $empQuery->whereNull('branch_id');
        }
        if ($request->filled('department_id')) $empQuery->where('department_id', $request->department_id);

        $employees = $empQuery->get();
        if ($employees->isEmpty()) return response()->json(['success' => true, 'matrix' => []]);

        $startDate = ($request->filled('start_date') && $request->filled('end_date')) ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = ($request->filled('start_date') && $request->filled('end_date')) ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth(); 

        $dates = []; for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) { $dates[] = $d->copy(); }

        $empIds = $employees->pluck('member_id')->toArray(); $empDbIds = $employees->pluck('id')->toArray();
        $attendances = Attendance::whereIn('user_id', $empIds)->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->get()->groupBy('user_id');
        $corrections = AttendanceCorrection::whereIn('user_id', $empDbIds)->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->get()->groupBy('user_id');
        $timeWindows = \App\Models\AttendanceTimeWindow::where('status', 'active')->get();

        $holidays = Holiday::where(function($query) use ($startDate, $endDate) {
            $query->whereBetween('start_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->orWhere(function($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate->format('Y-m-d'))->where(function($subQ) use ($startDate) { $subQ->whereNull('end_date')->orWhere('end_date', '>=', $startDate->format('Y-m-d')); });
            });
        })->get()->flatMap(function($holiday) {
            $period = \Carbon\CarbonPeriod::create($holiday->start_date, $holiday->end_date ?? $holiday->start_date);
            $dts = []; foreach ($period as $dt) { $dts[] = $dt->format('Y-m-d'); } return $dts;
        })->toArray();

        // 🔥 NAYA: Custom Dates support in Leave query
        $leaves = LeaveApplication::whereIn('user_id', $empDbIds)
            ->where('status', 'approved')
            ->where(function($q) use ($startDate, $endDate) {
                $q->where(function($subQ) use ($startDate, $endDate) {
                    $subQ->where('is_custom_date', 0)
                         ->whereNotNull('approved_start_datetime')
                         ->where('approved_start_datetime', '<=', $endDate->format('Y-m-d 23:59:59'))
                         ->where('approved_end_datetime', '>=', $startDate->format('Y-m-d 00:00:00'));
                })->orWhere(function($subQ) {
                    $subQ->where('is_custom_date', 1)
                         ->whereNotNull('approved_custom_dates');
                });
            })
            ->get()->groupBy('user_id');

        $matrix = []; $todayStr = Carbon::today()->format('Y-m-d');

        foreach ($employees as $emp) {
            $empAtt = $attendances->has($emp->member_id) ? $attendances->get($emp->member_id)->keyBy('date') : collect();
            $empCorr = $corrections->has($emp->id) ? $corrections->get($emp->id)->keyBy('date') : collect();
            $empLeaves = $leaves->has($emp->id) ? $leaves->get($emp->id) : collect();

            $empWindow = $timeWindows->where('company_id', $emp->company_id)->where('branch_id', $emp->branch_id)->first() ?? $timeWindows->where('company_id', $emp->company_id)->whereNull('branch_id')->first();
            $stats = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'leave' => 0, 'extra_day' => 0, 'late' => 0];
            $rawRecords = []; $lateCount = 0; $joinDate = $emp->created_at ? Carbon::parse($emp->created_at)->startOfDay() : Carbon::parse('2000-01-01');

            foreach ($dates as $dateObj) {
                $dStr = $dateObj->format('Y-m-d'); $isTuesday = $dateObj->isTuesday(); $isHoliday = in_array($dStr, $holidays); $isFuture = $dStr > $todayStr;
                $status = 'A'; $remark = ''; $inTime = null; $outTime = null;

                if ($empAtt->has($dStr)) {
                    $tempAtt = $empAtt->get($dStr);
                    $inTime = $tempAtt->login_time ? date('h:i A', strtotime($tempAtt->login_time)) : null;
                    $outTime = $tempAtt->logout_time ? date('h:i A', strtotime($tempAtt->logout_time)) : null;
                }

                // 🔥 NAYA: Custom Date Loop Logic
                $onLeave = false; $leaveType = 'L';
                if ($empLeaves->isNotEmpty()) {
                    foreach ($empLeaves as $leave) {
                        if ($leave->is_custom_date) {
                            $customDates = is_array($leave->approved_custom_dates) ? $leave->approved_custom_dates : [];
                            if (in_array($dStr, $customDates)) {
                                $onLeave = true;
                                if ($leave->application_type === 'Short Leave') $leaveType = 'SL';
                                break;
                            }
                        } else {
                            if (!$leave->approved_start_datetime || !$leave->approved_end_datetime) continue;
                            $lStart = Carbon::parse($leave->approved_start_datetime)->format('Y-m-d'); 
                            $lEnd = Carbon::parse($leave->approved_end_datetime)->format('Y-m-d');
                            if ($dStr >= $lStart && $dStr <= $lEnd) { 
                                $onLeave = true; 
                                if ($leave->application_type === 'Short Leave') $leaveType = 'SL'; 
                                break; 
                            }
                        }
                    }
                }

                if ($dateObj->lt($joinDate)) { $status = 'N/A'; $remark = 'Before Joining'; }
                elseif ($empCorr->has($dStr)) {
                    $corr = $empCorr->get($dStr); $status = $corr->corrected_status; $remark = "Corrected: " . $corr->reason;
                    if ($status == 'P') $stats['present']++; elseif ($status == 'HD') $stats['half_day']++; elseif ($status == 'L' || $status == 'SL') $stats['leave']++; elseif ($status == 'A') $stats['absent']++; elseif ($status == 'LT') { $stats['late']++; $stats['present']++; } elseif ($status == 'WO' || $status == 'HO') $stats['extra_day']++;
                } 
                elseif ($empAtt->has($dStr)) {
                    $att = $empAtt->get($dStr);

                    if ($isTuesday || $isHoliday) { $status = 'ED'; $remark = 'Worked on ' . ($isTuesday ? 'Weekly Off' : 'Holiday'); $stats['extra_day']++; }
                    elseif (!empty($att->login_time) && empty($att->logout_time)) {
                        
                        if ($dStr === $todayStr) {
                            $isLateToday = false;
                            if ($empWindow) {
                                $lateLimit = !empty($empWindow->late_time) ? Carbon::parse($dStr . ' ' . $empWindow->late_time) : Carbon::parse($dStr . ' ' . $empWindow->login_start)->addMinutes(60);
                                if (Carbon::parse($dStr . ' ' . $att->login_time)->gt($lateLimit)) { $isLateToday = true; }
                            } elseif ($att->is_late_punch) { $isLateToday = true; }

                            if ($isLateToday) $lateCount++;

                            if ($onLeave && $leaveType === 'SL') { $status = 'SL'; $remark = 'Short Leave (In-Office)'; }
                            elseif ($isLateToday) { $status = 'LT'; $remark = 'Late In-Office (Active)'; $stats['late']++; $stats['present']++; }
                            else { $status = 'P'; $remark = 'Present In-Office (Active)'; $stats['present']++; }
                        } else {
                            $status = 'HD'; $remark = 'Missed Punch-Out'; $stats['half_day']++;
                        }
                    }
                    elseif (empty($att->login_time) && !empty($att->logout_time)) { $status = 'HD'; $remark = 'Missed Punch-In'; $stats['half_day']++; }
                    elseif (empty($att->login_time) && empty($att->logout_time)) { $status = 'A'; $remark = 'Did not punch In/Out'; $stats['absent']++; }
                    else {
                        $isLateToday = false;
                        if ($empWindow) {
                            $lateLimit = !empty($empWindow->late_time) ? Carbon::parse($dStr . ' ' . $empWindow->late_time) : Carbon::parse($dStr . ' ' . $empWindow->login_start)->addMinutes(60);
                            if (Carbon::parse($dStr . ' ' . $att->login_time)->gt($lateLimit)) { $isLateToday = true; }
                        } elseif ($att->is_late_punch) { $isLateToday = true; }

                        if ($isLateToday) $lateCount++;

                       $minHoursRaw = $empWindow ? $empWindow->min_working_hours : 8.25;
                        $minHours = (strpos((string)$minHoursRaw, ':') !== false) ? (int)explode(':', $minHoursRaw)[0] + ((int)explode(':', $minHoursRaw)[1] / 60) : (float)$minHoursRaw;

                        $in = Carbon::parse($dStr . ' ' . $att->login_time); $out = Carbon::parse($dStr . ' ' . $att->logout_time);
                        $diffSeconds = $out->timestamp - $in->timestamp; if ($diffSeconds < 0) { $out->addDay(); $diffSeconds = $out->timestamp - $in->timestamp; }
                        
                        $workedMins = round($diffSeconds / 60);
                        $workedHours = $workedMins / 60;
                        
                        // 🔥 NAYA: Daily Extra Minutes Calculation
                        if ($workedMins > ($minHours * 60) && (!$onLeave || $leaveType !== 'SL')) {
                            $stats['extra_minutes'] = ($stats['extra_minutes'] ?? 0) + ($workedMins - ($minHours * 60));
                        }

                        $isShortHours = ($workedMins < ($minHours * 60));
                        if ($onLeave && $leaveType === 'SL') $isShortHours = false;

                        if ($isLateToday && $lateCount > 0 && ($lateCount % 3 == 0)) { $status = 'A'; $remark = 'Absent (Penalty for 3 Late Punches)'; $stats['absent']++; }
                        elseif ($isShortHours) { $status = 'HD'; $wHours = floor($workedHours); $wMins = round(($workedHours - $wHours) * 60); $remark = 'Short Working Hours (' . $wHours . 'h ' . $wMins . 'm)'; $stats['half_day']++; }
                       
                        elseif ($onLeave && $leaveType === 'SL') { $status = 'SL'; $remark = 'Worked on Short Leave'; $stats['leave']++; }
                        elseif ($isLateToday) { $status = 'LT'; $remark = 'Late Punch In'; $stats['late']++; $stats['present']++; }
                        else { $status = 'P'; $remark = 'On Time'; $stats['present']++; }
                    }
                } 
                elseif ($onLeave) {
                    if ($leaveType === 'SL' && !$isFuture && $dStr < $todayStr) { $status = 'A'; $remark = 'Absent (No punch on SL day)'; $stats['absent']++; }
                    else { $status = $leaveType === 'SL' ? 'SL' : 'L'; $remark = $leaveType === 'SL' ? 'Approved Short Leave' : 'Approved Leave'; $stats['leave']++; }
                } 
                elseif ($isFuture) { $status = 'N/A'; if ($isTuesday) { $status = 'WO'; $remark = 'Upcoming Weekly Off'; } elseif ($isHoliday) { $status = 'HO'; $remark = 'Upcoming Holiday'; } }
                else { if ($isTuesday) { $status = 'WO'; $remark = 'Weekly Off'; } elseif ($isHoliday) { $status = 'HO'; $remark = 'Holiday'; } else { $status = 'A'; $stats['absent']++; } }

                if ($empAtt->has($dStr)) {
                    $att = $empAtt->get($dStr);
                    $logs = $att->session_logs ? json_decode($att->session_logs, true) : []; $lastLog = end($logs);
                    $rawRecords[$dStr] = [ 'id' => $att->id, 'status' => $status, 'remark' => $remark, 'in' => $inTime, 'out' => $outTime, 'lat' => $att->latitude, 'lng' => $att->longitude, 'out_lat' => $lastLog['out_lat'] ?? null, 'out_lng' => $lastLog['out_lng'] ?? null, 'reason' => $att->punch_reason, 'proof_images' => $att->punch_proof_images, 'verification_status' => $att->hr_verification_status ];
                } else {
                    $rawRecords[$dStr] = [ 'id' => null, 'status' => $status, 'remark' => $remark, 'in' => null, 'out' => null, 'lat' => null, 'lng' => null, 'out_lat' => null, 'out_lng' => null, 'reason' => null, 'proof_images' => null, 'verification_status' => 'none' ];
                }
            }

         $finalDatesRecord = [];
            for ($i = 0; $i < count($dates); $i++) {
                $dStr = $dates[$i]->format('Y-m-d'); $currRecord = $rawRecords[$dStr];
                
                if ($currRecord['status'] === 'WO' || $currRecord['status'] === 'HO') {
                    $prevStatus = 'P';
                    for ($p = $i - 1; $p >= 0; $p--) {
                        $pDate = $dates[$p]->format('Y-m-d');
                        if (!in_array($rawRecords[$pDate]['status'], ['WO', 'HO', 'N/A'])) {
                            $prevStatus = $rawRecords[$pDate]['status'];
                            break;
                        }
                    }
                    
                    $nextStatus = 'P';
                    for ($n = $i + 1; $n < count($dates); $n++) {
                        $nDate = $dates[$n]->format('Y-m-d');
                        if (!in_array($rawRecords[$nDate]['status'], ['WO', 'HO', 'N/A'])) {
                            $nextStatus = $rawRecords[$nDate]['status'];
                            break;
                        }
                    }

                    if (in_array($prevStatus, ['A', 'L', 'SL', 'CL']) && in_array($nextStatus, ['A', 'L', 'SL', 'CL'])) {
                        $currRecord['status'] = 'A'; 
                        $currRecord['remark'] = 'Sandwich Rule Applied'; 
                        $stats['absent']++; 
                    }
                }
                $finalDatesRecord[$dStr] = $currRecord;
            }

            $exactName = !empty($emp->full_name) ? $emp->full_name : ($emp->name ?? 'Unknown');
            $exactDept = !empty($emp->department_id) ? (DB::table('departments')->where('id', $emp->department_id)->value('department_name') ?? 'N/A') : 'N/A';
           $exactDesig = !empty($emp->designation_id) ? (DB::table('designations')->where('id', $emp->designation_id)->value('designation_name') ?? 'N/A') : 'N/A';

            $totalExtMins = $stats['extra_minutes'] ?? 0;
            $extH = floor($totalExtMins / 60); $extM = $totalExtMins % 60;
            $stats['extra_hours_str'] = "{$extH}h {$extM}m";

            $matrix[] = [ 'employee' => [ 'db_id' => $emp->id, 'member_id' => $emp->member_id, 'name' => $exactName, 'department' => $exactDept, 'designation' => $exactDesig ], 'stats' => $stats, 'dates' => $finalDatesRecord ];
        }

        return response()->json(['success' => true, 'matrix' => $matrix, 'dates_list' => array_keys($matrix[0]['dates'] ?? [])]);
    }

   public function saveCorrection(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:adm_regist,id',
            'date' => 'required|date',
            'corrected_status' => 'required|in:P,A,HD,L,WO,LT,SL', 
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
    
    public function verifyPendingPunch(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'action_status' => 'required|in:approved,rejected',
            'final_attendance_status' => 'required|in:P,HD,A,L,LT,SL', 
            'hr_remark' => 'required|string|min:2'
        ]);

        DB::beginTransaction();
        try {
            $attendance = Attendance::find($request->attendance_id);

            $attendance->hr_verification_status = $request->action_status;
            $attendance->hr_remark = $request->hr_remark;

            $attendance->present = 0;
            $attendance->absent = 0;
            $attendance->half_day = 0;
            $attendance->leave = 0;

            if ($request->final_attendance_status == 'P' || $request->final_attendance_status == 'LT') $attendance->present = 1;
            elseif ($request->final_attendance_status == 'HD') $attendance->half_day = 1;
            elseif ($request->final_attendance_status == 'A') $attendance->absent = 1;
            elseif ($request->final_attendance_status == 'L' || $request->final_attendance_status == 'SL') $attendance->leave = 1;

            $attendance->save();

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