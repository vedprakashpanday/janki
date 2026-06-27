<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\EmployeeLogin;
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceTimeWindow;
use App\Services\MediaConverterService;
use Carbon\Carbon;
use App\Models\Attendance;

class AuthController extends Controller
{

public function verifyId(Request $request)
    {
        $request->validate([
            'panel_id' => 'required|string',
            'device_token' => 'required|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);

        $login = EmployeeLogin::with('employee')->where('panel_id', $request->panel_id)->first();
        
        if (!$login) return response()->json(['status' => 'error', 'message' => 'Invalid Panel ID!'], 404);
        if (!$login->employee || $login->employee->emp_status !== 'active') {
            return response()->json(['status' => 'error', 'message' => 'Access Denied! Account is inactive.'], 403);
        }
        if ($login->p_status !== 'allow') return response()->json(['status' => 'error', 'message' => 'Access blocked by Admin.'], 403);

        $baseToken = $request->device_token;
        $empId = $login->user_id;

        // Blocked Device Check
        $blockedDevices = $login->blocked_devices ?? [];
        foreach ($blockedDevices as $bToken) {
            if (str_ends_with($bToken, '_' . $baseToken)) {
                return response()->json(['status' => 'error', 'message' => 'This device is BLOCKED!'], 403);
            }
        }

        // 1. First Time Login Check
        if (empty($login->primary_device)) {
            return response()->json(['status' => 'require_password', 'message' => 'First time login. Enter Password to bind device.']);
        }

        // 2. Secondary Device Slot Active
        if ($login->s_status === 'allow' && empty($login->secondary_device)) {
            if (!str_ends_with($login->primary_device, '_' . $baseToken)) {
                return response()->json(['status' => 'require_password', 'message' => 'Secondary Slot Active. Enter Password to permanently bind this device.']);
            }
        }

        // 3. Check if Primary
        if (str_ends_with($login->primary_device, '_' . $baseToken)) {
            return $this->sendOtp($login, 'Welcome back! OTP sent to your registered email.');
        }

        // 4. Check if Secondary
        if (!empty($login->secondary_device) && str_ends_with($login->secondary_device, '_' . $baseToken)) {
            return $this->sendOtp($login, 'Secondary Access Verified! OTP sent to your email.');
        }

        // 5. Cross-Device Office Bypass (Bina GPS ke, sirf check karega ki device kisi aur ka registered toh nahi)
        $isRecognizedDevice = \App\Models\EmployeeLogin::where('primary_device', 'LIKE', '%' . $baseToken)
                                           ->orWhere('secondary_device', 'LIKE', '%' . $baseToken)
                                           ->exists();
        if ($isRecognizedDevice) {
             return $this->sendOtp($login, 'Shared Device Detected! Bypass successful, OTP sent.');
        }

        // 6. Log Unauthorized Attempt
        $otherDevices = $login->other_devices ?? [];
        $fullOtherToken = $empId . '_O_' . $baseToken;
        $alreadyLogged = false;
        foreach ($otherDevices as $od) {
            if (str_ends_with($od['device_token'], '_' . $baseToken)) { $alreadyLogged = true; break; }
        }

        if (!$alreadyLogged) {
            $otherDevices[] = [
                'device_token' => $fullOtherToken,
                'latitude' => $request->latitude ?? 'Location Denied',
                'longitude' => $request->longitude ?? 'Location Denied',
                'time' => now()->format('Y-m-d h:i A'),
                'status' => 'pending'
            ];
            $login->update(['other_devices' => $otherDevices]);
        }

        return response()->json(['status' => 'unauthorized_device', 'message' => 'Unrecognized device blocked!'], 403);
    }

    public function bindDevice(Request $request)
    {
        $request->validate([
            'panel_id' => 'required|string',
            'panel_password' => 'required|string',
            'device_token' => 'required|string'
        ]);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->first();

        if (!$login || $login->panel_password !== $request->panel_password) {
            return response()->json(['status' => 'error', 'message' => 'Invalid Panel Password!'], 401);
        }

        $baseToken = $request->device_token;
        $fullTokenToSave = '';

        // CASE 1: First time Primary Binding
        if (empty($login->primary_device)) {
            $fullTokenToSave = $login->user_id . '_P_' . $baseToken;
            $login->primary_device = $fullTokenToSave;
        } 
        // CASE 2: 🔥 NEW FIX: Emergency Secondary Binding 🔥
        else if ($login->s_status === 'allow' && empty($login->secondary_device)) {
            
            $currentTime = now()->format('H:i:s');
            $currentDate = now()->format('Y-m-d');
            $isSecondaryTime = false;
            
            if ($currentDate >= $login->s_date_from && $currentDate <= $login->s_date_to) {
                if ($login->s_time_from < $login->s_time_to) {
                    $isSecondaryTime = ($currentTime >= $login->s_time_from && $currentTime <= $login->s_time_to);
                } else {
                    $isSecondaryTime = ($currentTime >= $login->s_time_from || $currentTime <= $login->s_time_to);
                }
            }

            if (!$isSecondaryTime) {
                return response()->json(['status' => 'error', 'message' => 'Emergency time slot has expired!'], 403);
            }

            $fullTokenToSave = $login->user_id . '_S_' . $baseToken;
            $login->secondary_device = $fullTokenToSave;

            // Agar ye device control room me pending tha, toh usko wahan se hata do
            $otherDevices = $login->other_devices ?? [];
            $filteredOther = array_filter($otherDevices, function($d) use ($baseToken) {
                return !str_ends_with($d['device_token'], '_' . $baseToken);
            });
            $login->other_devices = array_values($filteredOther);

        } else {
            return response()->json(['status' => 'error', 'message' => 'Device slots are full or access is unauthorized.'], 403);
        }

        $login->save();

        $employee = \App\Models\Employee::where('member_id', $login->user_id)->first();
        if (!$employee) return response()->json(['status' => 'error', 'message' => 'Master profile error.'], 404);
        
        $token = $employee->createToken($fullTokenToSave)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Device bound successfully! Access Granted.',
            'emp_token' => $token,
            'emp_panel_id' => $login->panel_id
        ]);
    }



    // Helper method for generating OTP
    private function sendOtp($login, $message)
    {
        $otp = str_pad(rand(0, 999999), 6, "0", STR_PAD_LEFT);
        $login->panel_otp = $otp;
        $login->otp_time_till = now()->addMinutes(3);
        $login->save();

        return response()->json([
            'status' => 'require_otp',
            'message' => $message,
            'mock_otp' => $otp // Bypass ke liye
        ]);
    }

//     // ==========================================
//     // PHASE 2: BIND DEVICE (FIRST TIME LOGIN)
//     // ==========================================
//   public function bindDevice(Request $request)
//     {
//         $request->validate([
//             'panel_id' => 'required|string',
//             'panel_password' => 'required|string',
//             'device_token' => 'required|string'
//         ]);

//         $login = EmployeeLogin::where('panel_id', $request->panel_id)->first();

//         if (!$login || $login->panel_password !== $request->panel_password) {
//             return response()->json(['status' => 'error', 'message' => 'Invalid Panel Password!'], 401);
//         }
//         if (!empty($login->primary_device)) {
//             return response()->json(['status' => 'error', 'message' => 'A device is already bound.'], 403);
//         }

//         $baseToken = $request->device_token;
//         $fullPrimaryToken = $login->user_id . '_P_' . $baseToken;

//         // Bind device permanently
//         $login->primary_device = $fullPrimaryToken;
//         $login->save();

//         // Doubt 1 Fix: Yahi se direct real Sanctum Token create karke login kara do!
//         $employee = \App\Models\Employee::where('member_id', $login->user_id)->first();
//         if (!$employee) return response()->json(['status' => 'error', 'message' => 'Master profile error.'], 404);
        
//         $token = $employee->createToken($fullPrimaryToken)->plainTextToken;

//         return response()->json([
//             'status' => 'success',
//             'message' => 'Device bound successfully! Access Granted.',
//             'emp_token' => $token,
//             'emp_panel_id' => $login->panel_id
//         ]);
//     }

   public function verifyOtp(Request $request)
    {
        $request->validate([
            'panel_id' => 'required',
            'panel_otp' => 'required',
            'device_token' => 'required'
        ]);

        try {
            $loginRecord = DB::table('employee_logins')
                ->where('panel_id', $request->panel_id)
                ->where('panel_otp', $request->panel_otp)
                ->first();

            if (!$loginRecord) {
                return response()->json(['status' => 'error', 'message' => 'Invalid OTP or Panel ID!'], 401);
            }

            $employee = Employee::where('member_id', $loginRecord->user_id)->first();

            if (!$employee) {
                return response()->json(['status' => 'error', 'message' => 'Employee Record Not Found in Database!'], 404);
            }

            // 🔥 MASTER FIX: Detect if device is Secondary or Primary
            $isSecondary = !empty($loginRecord->secondary_device) && str_ends_with($loginRecord->secondary_device, '_' . $request->device_token);
            
            // Agar secondary hai toh '_S_' lagao, varna '_P_'
            $prefix = $isSecondary ? '_S_' : '_P_';
            $fullTokenName = $loginRecord->user_id . $prefix . $request->device_token;
            
            // Correct token create karo
            $token = $employee->createToken($fullTokenName)->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login Successful',
                'emp_token' => $token,
                'emp_panel_id' => $request->panel_id
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

public function markAttendance(Request $request, MediaConverterService $mediaConverter)
    {
        // 1. Existing Device Security Check
        $request->validate([
            'panel_id' => 'required|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->first();
        if (!$login) {
            return response()->json(['status' => 'error', 'message' => 'User not found or invalid device panel.'], 404);
        }

        $user = $request->user();
        $today = Carbon::now()->format('Y-m-d');
        $systemTime = Carbon::now();

        // 2. Get Applicable Time Window (Prioritize Branch level rule)
        $timeWindow = AttendanceTimeWindow::where('company_id', $user->company_id)
            ->where(function($q) use ($user) {
                if ($user->branch_id) {
                    $q->where('branch_id', $user->branch_id)->orWhereNull('branch_id');
                } else {
                    $q->whereNull('branch_id'); // Head Office
                }
            })
            ->where('status', 'active')
            ->orderBy('branch_id', 'desc')
            ->first();

        // 3. Time Validations
        $claimedTimeStr = $request->claimed_time ?? $systemTime->format('H:i:s');
        $claimedTime = Carbon::parse($today . ' ' . $claimedTimeStr);

        $diffInMinutes = $systemTime->diffInMinutes($claimedTime);
        $needsProof = $diffInMinutes > 5;

        // Strict Enforcement: Reject if > 5 mins difference and no proof
        if ($needsProof && (!$request->has('reason') || !$request->hasFile('proof_images'))) {
            return response()->json(['status' => 'error', 'message' => 'Time discrepancy detected! Proof Images and Reason are strictly required for this punch-in.'], 422);
        }

        // 3 Late = 1 Leave tracking parameter
        $isLate = false;
        if ($timeWindow) {
            $loginStart = Carbon::parse($today . ' ' . $timeWindow->login_start);
            $isLate = $claimedTime->greaterThan($loginStart->copy()->addMinutes(30));
        }

        // 4. Handle Proof Images Upload via Service
        $proofPaths = [];
        if ($request->hasFile('proof_images')) {
            foreach ($request->file('proof_images') as $file) {
                $media = $mediaConverter->uploadAndConvert($file);
                if ($media) {
                    $proofPaths[] = $media->file_path;
                }
            }
        }

        $verificationStatus = (count($proofPaths) > 0 || $request->reason) ? 'pending' : 'none';

        // 5. Existing Database Schema & Session Array Builder
        $attendance = Attendance::where('user_id', $login->user_id)->where('date', $today)->first();

        $newSession = [
            'in' => $claimedTimeStr,
            'out' => null,
            'lat' => $request->latitude ?? null,
            'lng' => $request->longitude ?? null
        ];

        if (!$attendance) {
            // First Punch of the day
            Attendance::create([
                'user_id' => $login->user_id,
                'date' => $today,
                'present' => 1,
                'login_time' => $claimedTimeStr,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'remarks' => 'Manual Punch via Dashboard',
                'session_logs' => json_encode([$newSession]),
                
                // New Tracking Columns
                'is_late_punch' => $isLate,
                'punch_reason' => $request->reason,
                'punch_proof_images' => count($proofPaths) > 0 ? $proofPaths : null,
                'hr_verification_status' => $verificationStatus
            ]);

            // Existing logic: Cancel Leave if employee logged in
            $employeeId = $login->employee->id ?? 0;
            if ($employeeId) {
                $todayLeave = \App\Models\LeaveApplication::where('user_id', $employeeId)
                    ->where('status', 'approved')
                    ->where('start_datetime', '<=', now()->format('Y-m-d 23:59:59'))
                    ->where('end_datetime', '>=', now()->format('Y-m-d 00:00:00'))
                    ->first();

                if ($todayLeave) {
                    if ($todayLeave->duration > 1) {
                        $todayLeave->decrement('duration', 1.00);
                        if ($todayLeave->approved_duration > 1) $todayLeave->decrement('approved_duration', 1.00);
                    } else {
                        $todayLeave->update([
                            'duration' => 0, 'approved_duration' => 0, 'status' => 'rejected',
                            'remarks' => ($todayLeave->remarks ? $todayLeave->remarks . ' | ' : '') . 'Cancelled due to system Punch-In.'
                        ]);
                    }
                }
            }
        } else {
            // Agar employee naya session create karta hai same day par (e.g. Break se wapas)
            $logs = $attendance->session_logs ? json_decode($attendance->session_logs, true) : [];
            $logs[] = $newSession;
            $attendance->update([
                'session_logs' => json_encode($logs)
            ]);
        }

        return response()->json([
            'status' => 'success', 
            'message' => $verificationStatus === 'pending' ? 'Punch Recorded. Sent to HR for Validation!' : 'Attendance Marked Successfully!'
        ]);
    }
    
   // ==========================================
    // SECURE LOGOUT (WITH AUTO-LOGOUT DETECTION)
    // ==========================================
    public function logout(Request $request)
    {
        $request->validate(['panel_id' => 'required|string']);
        $login = EmployeeLogin::where('panel_id', $request->panel_id)->first();
        
        // NAYA: Check karo ki kya yeh auto-logout ki request hai
        $isAutoLogout = $request->has('is_auto') && $request->is_auto == 1;

        if ($login) {
            $today = now()->format('Y-m-d');
            $currentTime = now()->format('H:i:s');
            
            $attendance = \App\Models\Attendance::where('user_id', $login->user_id)
                ->where('date', $today)->first();

            if ($attendance) {
                $logs = $attendance->session_logs ? json_decode($attendance->session_logs, true) : [];
                $lastIndex = count($logs) - 1;

                // Sirf last open session ka OUT time update karo
                if ($lastIndex >= 0 && $logs[$lastIndex]['out'] === null) {
                    // Agar Auto-Logout hai, to "Auto-Closed" likho, varna real-time daalo
                    $logs[$lastIndex]['out'] = $isAutoLogout ? 'Auto-Closed' : $currentTime;
                }

                $updateData = ['session_logs' => json_encode($logs)];

                // 🔥 MAIN LOGIC: Official attendance logout_time sirf manual click par hi bharega
                if (!$isAutoLogout) {
                    $updateData['logout_time'] = $currentTime;
                }

                $attendance->update($updateData);
            }

            // CLEAN-UP: Remove Emergency Access on Logout
            // if ($login->s_status === 'allow') {
            //     $login->update([
            //         's_status' => 'deny',
            //         's_time_from' => null,
            //         's_time_to' => null
            //     ]);
            // }
        }

        // Token destroy karo
        $user = $request->user();
        if($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json(['status' => 'success', 'message' => 'Logged out safely!']);
    }

public function me(Request $request)
    {
        $user = $request->user();

        // 🔥 Purane "getAllPermissions" ko hatakar apna "Live" wala lagaya
        $permissions = \App\Http\Controllers\Controller::getLiveActivePermissions($user);
        
        $isSecondaryDevice = false;

        // Agar Token me '_S_' hai, to sirf flag ON karenge, Permissions change NAHI karenge
        if ($user && $user->currentAccessToken() && str_contains($user->currentAccessToken()->name, '_S_')) {
            $isSecondaryDevice = true;
            // Yahan se saara permission filter karne wala code hata diya gaya hai.
            // Ab jiske paas 'add_direct' hai usko wahi milega, jiske paas 'add_request' hai usko wahi milega.
        }

        $logoUrl = null;
        $companyName = 'N/A';
        $branchName = 'N/A';

        if (!empty($user->company_id)) {
            $company = \Illuminate\Support\Facades\DB::table('companies')->where('id', $user->company_id)->first();
            if ($company) {
                $companyName = $company->company_name;
                if (!empty($company->company_logo)) {
                    $logoUrl = asset($company->company_logo); 
                }
            }
        }

        if (!empty($user->branch_id)) {
            $branch = \Illuminate\Support\Facades\DB::table('branches')->where('id', $user->branch_id)->first();
            if ($branch) {
                $branchName = $branch->branch_name;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->full_name ?? $user->employee_name ?? 'User', 
                'email' => $user->email,
                'designation_name' => $user->designation_name ?? 'Employee Access',
                'company_logo' => $logoUrl,
                'company_id' => $user->company_id ?? '',
                'company_name' => $companyName,
                'branch_id' => $user->branch_id ?? null,
                'branch_name' => $branchName,
                'is_secondary_device' => $isSecondaryDevice, // Frontend ko restriction ke liye flag
                'permissions' => $permissions // Sahi permissions bina modify kiye bhejein
            ]
        ]);
    }
   
  // ==========================================
    // DASHBOARD DATA (HOLIDAYS & SANDWICH LEAVE RULE APPLIED)
    // ==========================================
    public function getDashboardData(Request $request)
    {
        $user = $request->user();
        $month = $request->month ?? date('m');
        $year = $request->year ?? date('Y');

        $memberId = $user->member_id ?? $user->id;

        $monthStart = sprintf('%04d-%02d-01', $year, $month);
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        // Sandwich rule ke liye mahine ke ek din pehle aur ek din baad ka data bhi chahiye hoga
        $checkStart = date('Y-m-d', strtotime($monthStart . ' -1 day'));
        $checkEnd = date('Y-m-d', strtotime($monthEnd . ' +1 day'));

        $attendances = \App\Models\Attendance::where('user_id', $memberId)
            ->whereBetween('date', [$checkStart, $checkEnd])
            ->get()->keyBy('date');

        // 1. Fetch Active Holidays
        $holidays = \App\Models\Holiday::where('status', 'active')
            ->where(function($q) use ($checkStart, $checkEnd) {
                $q->whereBetween('start_date', [$checkStart, $checkEnd])
                  ->orWhereBetween('end_date', [$checkStart, $checkEnd])
                  ->orWhere(function($q2) use ($checkStart, $checkEnd) {
                      $q2->where('start_date', '<=', $checkStart)
                         ->where('end_date', '>=', $checkEnd);
                  });
            })->get();

        $holidayDates = [];
        foreach ($holidays as $h) {
            $start = strtotime($h->start_date);
            $end = $h->end_date ? strtotime($h->end_date) : $start;
            for ($time = $start; $time <= $end; $time += 86400) {
                $holidayDates[date('Y-m-d', $time)] = true;
            }
        }

        // 2. Fetch Approved Leaves
        $leaves = \App\Models\LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where(function($q) use ($checkStart, $checkEnd) {
                $q->whereBetween('start_datetime', [$checkStart . ' 00:00:00', $checkEnd . ' 23:59:59'])
                  ->orWhereBetween('end_datetime', [$checkStart . ' 00:00:00', $checkEnd . ' 23:59:59'])
                  ->orWhere(function($q2) use ($checkStart, $checkEnd) {
                      $q2->where('start_datetime', '<=', $checkStart . ' 00:00:00')
                         ->where('end_datetime', '>=', $checkEnd . ' 23:59:59');
                  });
            })->get();

        $leaveDates = [];
        foreach ($leaves as $leave) {
            $start = strtotime($leave->approved_start_datetime ?? $leave->start_datetime);
            $end = strtotime($leave->approved_end_datetime ?? $leave->end_datetime);
            for ($time = $start; $time <= $end; $time += 86400) {
                $leaveDates[date('Y-m-d', $time)] = true;
            }
        }

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $todayDate = date('Y-m-d');

        $dailyData = [];
        $rawStatuses = [];

        // ==========================================
        // ROUND 1: HAR DIN KA STATUS NIKALNA
        // ==========================================
       for ($i = 1; $i <= $daysInMonth; $i++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $i);
            $dayOfWeek = date('w', strtotime($dateStr));

            $isTuesday = ($dayOfWeek == 2);
            $isHoliday = isset($holidayDates[$dateStr]);
            $isLeave = isset($leaveDates[$dateStr]);

            $att = $attendances->get($dateStr);
            $status = 'absent';
            $remark = '';
            $inTime = '--:--';
            $outTime = '--:--';
            $ot = 0;

            if ($dateStr > $todayDate) {
                $status = 'future';
            } elseif ($att && $att->login_time) {
                $inTime = date('h:i A', strtotime($att->login_time));
                $outTime = $att->logout_time ? date('h:i A', strtotime($att->logout_time)) : '--:--';
                
                $hoursWorked = 0;
                if ($att->logout_time) {
                    $t1 = strtotime($att->login_time);
                    $t2 = strtotime($att->logout_time);
                    $hoursWorked = ($t2 - $t1) / 3600;
                }

                // 🔥 POINT 4: EXTRA DAY 4-HOURS & LOGOUT MANDATORY RULE 🔥
                if ($isTuesday || $isHoliday) {
                    if ($att->logout_time && $hoursWorked >= 4.0) {
                        $status = 'extra_day';
                        $remark = $isTuesday ? 'Worked on Weekly Off' : 'Worked on Holiday';
                    } else {
                        $status = $isHoliday ? 'holiday' : 'off';
                        $remark = (!$att->logout_time) ? 'No Logout (Not Eligible)' : 'Worked < 4 Hrs (Not Eligible)';
                    }
                } else {
                    $isHalfDay = false;
                    $timeOnly = date('H:i:s', strtotime($att->login_time));

                    if ($timeOnly > '11:00:00') {
                        $isHalfDay = true;
                        $remark = 'Late: After 11 AM (Half Day)';
                    }

                    if (!$att->logout_time && $dateStr != $todayDate) {
                        $isHalfDay = true;
                        $remark = $remark ?: 'No Logout (Half Day)';
                    } elseif ($att->logout_time) {
                        // 🔥 POINT 5: WORKING HOURS SET TO 8.25 HOURS 🔥
                        if ($hoursWorked < 8.25 && !$isHalfDay) {
                            $isHalfDay = true;
                            $remark = $remark ?: 'Short Hours (< 8.25 Hrs Half Day)';
                        }
                        
                        $logoutTimeOnly = date('H:i:s', strtotime($att->logout_time));
                        if (($logoutTimeOnly < '18:00:00' || $logoutTimeOnly > '19:00:00') && !$isHalfDay) {
                            $isHalfDay = true;
                            $remark = $remark ?: 'Logout outside 6-7 PM window';
                        }
                    } else {
                        if ($dateStr != $todayDate) {
                            $isHalfDay = true;
                        } elseif (date('H:i:s') > '19:00:00') {
                            $isHalfDay = true;
                            $remark = 'Missed 6-7 PM Logout window';
                        }
                    }

                    $status = $isHalfDay ? 'half_day' : 'present';
                    $ot = ($hoursWorked > 8.25) ? round($hoursWorked - 8.25, 1) : 0;
                }
            } else {
                if ($isHoliday) {
                    $status = 'holiday';
                } elseif ($isLeave) {
                    $status = 'leave';
                    $remark = 'Approved Leave';
                } elseif ($isTuesday) {
                    $status = 'off';
                } else {
                    $status = 'absent';
                }
            }

            $rawStatuses[$dateStr] = [
                'status' => $status,
                'remark' => $remark,
                'login_time' => $inTime,
                'logout_time' => $outTime,
                'ot' => $ot
            ];
        }

        // Sandwich ke liye bahar ki date check karne ka function
        $checkBoundaryStatus = function($dStr) use ($attendances, $leaveDates, $holidayDates, $todayDate) {
             if ($dStr > $todayDate) return 'future';
             if ($attendances->has($dStr)) return 'present';
             if (isset($leaveDates[$dStr])) return 'leave';
             if (isset($holidayDates[$dStr])) return 'holiday';
             return 'absent';
        };

        // ==========================================
        // ROUND 2: SANDWICH RULE & CL DEDUCTION 
        // ==========================================
        $stats = [
            'present' => 0, 'absent' => 0, 'half_day' => 0, 'extra_days' => 0,
            'fine_amount' => 0, 'cl_available' => 1, 'total_leave' => 0, 'late_10_11_count' => 0
        ];

        foreach ($rawStatuses as $dateStr => $data) {
            
           
           // 🔥 SANDWICH RULE APPLY (Agar Tuesday hai) 🔥
            if ($data['status'] === 'off') { 
                $mondayStr = date('Y-m-d', strtotime($dateStr . ' -1 day'));
                $wednesdayStr = date('Y-m-d', strtotime($dateStr . ' +1 day'));

                $monStatus = isset($rawStatuses[$mondayStr]) ? $rawStatuses[$mondayStr]['status'] : $checkBoundaryStatus($mondayStr);
                $wedStatus = isset($rawStatuses[$wednesdayStr]) ? $rawStatuses[$wednesdayStr]['status'] : $checkBoundaryStatus($wednesdayStr);

                $monAbsent = in_array($monStatus, ['absent', 'leave', 'cl']);
                $wedAbsent = in_array($wedStatus, ['absent', 'leave', 'cl']);

                // 🔥 Removed the $todayDate limit so it predicts correctly for future as well
                if ($monAbsent && $wedAbsent) {
                    $data['status'] = 'absent'; 
                    $data['remark'] = 'Sandwich Rule Applied (Mon & Wed Leave)';
                }
            }
            // 🔥 COMPENSATORY LEAVE (CL) AUTO-APPLY 🔥
            if ($data['status'] === 'absent') {
                if ($stats['cl_available'] > 0) {
                    $stats['cl_available']--;
                    $data['status'] = 'cl';
                    $data['remark'] = ($data['remark'] ? $data['remark'] . ' | ' : '') . 'CL Applied';
                }
            }

            // Stats Update
            if ($data['status'] === 'present') $stats['present']++;
            elseif ($data['status'] === 'half_day') $stats['half_day']++;
            elseif ($data['status'] === 'absent') $stats['absent']++;
            elseif ($data['status'] === 'leave' || $data['status'] === 'cl') $stats['total_leave']++;
            elseif ($data['status'] === 'extra_day') $stats['extra_days']++;

            $dailyData[$dateStr] = $data;
        }

        // Salary Record & Fine Calculation
        $salaryRecord = \App\Models\Salary::where('employee_id', $user->id)->first();
        $salaryAmount = $salaryRecord ? $salaryRecord->amount : 0; 
        $perDaySalary = $salaryAmount > 0 ? ($salaryAmount / 30) : 0;

        $fineDays = $stats['absent'] + ($stats['half_day'] * 0.5);
        $stats['fine_amount'] = round($fineDays * $perDaySalary, 2);

        // 🔥 NAYA: Fetch Active Time Window for this employee
        $timeWindow = AttendanceTimeWindow::where('company_id', $user->company_id)
            ->where(function($q) use ($user) {
                if ($user->branch_id) {
                    $q->where('branch_id', $user->branch_id)->orWhereNull('branch_id');
                } else {
                    $q->whereNull('branch_id'); // Head Office
                }
            })
            ->where('status', 'active')
            ->orderBy('branch_id', 'desc') // Branch specific rule overrides HO rule
            ->first();

        return response()->json([
            'status' => 'success',
            'month' => $month,
            'year' => $year,
            'stats' => $stats,
            'daily_data' => $dailyData,
            'time_window' => $timeWindow
        ]);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 999999; // Fallback agar location na ho
        $earth_radius = 6371000; // Meters mein
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * asin(sqrt($a));
        return $earth_radius * $c;
    }





}