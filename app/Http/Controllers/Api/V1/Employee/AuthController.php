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
use App\Models\LeaveApplication;

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

        $blockedDevices = $login->blocked_devices ?? [];
        foreach ($blockedDevices as $bToken) {
            if (str_ends_with($bToken, '_' . $baseToken)) {
                return response()->json(['status' => 'error', 'message' => 'This device is BLOCKED!'], 403);
            }
        }

        if (empty($login->primary_device)) {
            return response()->json(['status' => 'require_password', 'message' => 'First time login. Enter Password to bind device.']);
        }

        if ($login->s_status === 'allow' && empty($login->secondary_device)) {
            if (!str_ends_with($login->primary_device, '_' . $baseToken)) {
                return response()->json(['status' => 'require_password', 'message' => 'Secondary Slot Active. Enter Password to permanently bind this device.']);
            }
        }

        if (str_ends_with($login->primary_device, '_' . $baseToken)) {
            return $this->sendOtp($login, 'Welcome back! OTP sent to your registered email.');
        }

        if (!empty($login->secondary_device) && str_ends_with($login->secondary_device, '_' . $baseToken)) {
            return $this->sendOtp($login, 'Secondary Access Verified! OTP sent to your email.');
        }

        $isRecognizedDevice = EmployeeLogin::where('primary_device', 'LIKE', '%' . $baseToken)
            ->orWhere('secondary_device', 'LIKE', '%' . $baseToken)
            ->exists();
        if ($isRecognizedDevice) {
            return $this->sendOtp($login, 'Shared Device Detected! Bypass successful, OTP sent.');
        }

        $otherDevices = $login->other_devices ?? [];
        $fullOtherToken = $empId . '_O_' . $baseToken;
        $alreadyLogged = false;
        foreach ($otherDevices as $od) {
            if (str_ends_with($od['device_token'], '_' . $baseToken)) {
                $alreadyLogged = true;
                break;
            }
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

        if (empty($login->primary_device)) {
            $fullTokenToSave = $login->user_id . '_P_' . $baseToken;
            $login->primary_device = $fullTokenToSave;
        } else if ($login->s_status === 'allow' && empty($login->secondary_device)) {
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

            $otherDevices = $login->other_devices ?? [];
            $filteredOther = array_filter($otherDevices, function ($d) use ($baseToken) {
                return !str_ends_with($d['device_token'], '_' . $baseToken);
            });
            $login->other_devices = array_values($filteredOther);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Device slots are full or access is unauthorized.'], 403);
        }

        $login->save();

        $employee = Employee::where('member_id', $login->user_id)->first();
        if (!$employee) return response()->json(['status' => 'error', 'message' => 'Master profile error.'], 404);

        $token = $employee->createToken($fullTokenToSave)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Device bound successfully! Access Granted.',
            'emp_token' => $token,
            'emp_panel_id' => $login->panel_id
        ]);
    }

    private function sendOtp($login, $message)
    {
        $otp = str_pad(rand(0, 999999), 6, "0", STR_PAD_LEFT);
        $login->panel_otp = $otp;
        $login->otp_time_till = now()->addMinutes(3); // OTP 3 minute ke liye valid rahega
        $login->save();

        // 1. Employee ka Data aur Email nikalna
        $employee = \App\Models\Employee::where('member_id', $login->user_id)->first();

        if ($employee && !empty($employee->email)) {
            $email = $employee->email;
            $empName = $employee->full_name ?? $employee->employee_name ?? 'Employee';

            // 2. Real Email Bhejna (SMTP ke through)
            try {
                \Illuminate\Support\Facades\Mail::send([], [], function ($messageBuilder) use ($email, $empName, $otp) {
                    $messageBuilder->to($email)
                        ->subject('Your Login Verification OTP - Security Alert')
                        ->html("
                            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd; border-radius: 8px; max-width: 500px; margin: auto;'>
                                <h3 style='color: #1A365D;'>Hello {$empName},</h3>
                                <p>We received a request to log in to your employee attendance panel.</p>
                                <p>Your One-Time Password (OTP) for device verification is:</p>
                                <h2 style='background: #f1f5f9; padding: 10px; text-align: center; letter-spacing: 5px; color: #3b82f6; border-radius: 5px;'>{$otp}</h2>
                                <p style='color: #ef4444; font-size: 12px;'><strong>Note:</strong> This OTP is valid for the next 3 minutes only. Please do not share it with anyone.</p>
                                <hr style='border: none; border-top: 1px solid #eee; margin-top: 20px;' />
                                <p style='font-size: 11px; color: #888;'>If you did not request this login, please contact your HR or IT Administrator immediately.</p>
                            </div>
                        ");
                });
                
                $message .= " Please check your email inbox (and spam folder).";
                
            } catch (\Exception $e) {
                // Agar SMTP fail ho jaye toh error dikhaye
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to send OTP Email. Please check SMTP settings. Error: ' . $e->getMessage()
                ], 500);
            }
        } else {
            // Agar employee ki email missing hai
            return response()->json([
                'status' => 'error',
                'message' => 'No registered email found for your profile. Please contact HR.'
            ], 404);
        }

        // 3. Success Response (Yahan se 'mock_otp' hata diya gaya hai security ke liye)
        return response()->json([
            'status' => 'require_otp',
            'message' => $message
        ]);
    }

    // 🔥 NAYA: Resend OTP Function 🔥
    public function resendOtp(Request $request)
    {
        $request->validate([
            'panel_id' => 'required|string'
        ]);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->first();
        
        if (!$login) {
            return response()->json(['status' => 'error', 'message' => 'Invalid Panel ID!'], 404);
        }

        // Ye wahi naya wala sendOtp function call karega jo humne email ke liye banaya tha
        return $this->sendOtp($login, 'A fresh OTP has been sent to your email.');
    }

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

            // 🔥 FIX 1: OTP Expiry Check - 3 min ke baad reject karega
            if (\Carbon\Carbon::now()->greaterThan($loginRecord->otp_time_till)) {
                return response()->json(['status' => 'error', 'message' => 'OTP has expired! Please request a new login.'], 401);
            }

            $employee = Employee::where('member_id', $loginRecord->user_id)->first();

            if (!$employee) {
                return response()->json(['status' => 'error', 'message' => 'Employee Record Not Found in Database!'], 404);
            }

            $isSecondary = !empty($loginRecord->secondary_device) && str_ends_with($loginRecord->secondary_device, '_' . $request->device_token);
            $prefix = $isSecondary ? '_S_' : '_P_';
            $fullTokenName = $loginRecord->user_id . $prefix . $request->device_token;

            $token = $employee->createToken($fullTokenName)->plainTextToken;

            // 🔥 FIX 2: Security ke liye use hone ke baad OTP mita dein
            DB::table('employee_logins')->where('panel_id', $request->panel_id)->update([
                'panel_otp' => null,
                'otp_time_till' => null
            ]);

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

        $timeWindow = AttendanceTimeWindow::where('company_id', $user->company_id)
            ->where(function ($q) use ($user) {
                if ($user->branch_id) $q->where('branch_id', $user->branch_id)->orWhereNull('branch_id');
                else $q->whereNull('branch_id');
            })
            ->where('status', 'active')
            ->orderBy('branch_id', 'desc')
            ->first();

        $claimedTimeStr = $request->claimed_time ?? $systemTime->format('H:i:s');
        $claimedTime = Carbon::parse($today . ' ' . $claimedTimeStr);

        $diffInMinutes = $systemTime->diffInMinutes($claimedTime);
        $needsProof = $diffInMinutes > 5;

        if ($needsProof && (!$request->has('reason') || !$request->hasFile('proof_images'))) {
            return response()->json(['status' => 'error', 'message' => 'Time discrepancy detected! Proof Images and Reason are strictly required.'], 422);
        }

        $isLate = false;
        if ($timeWindow) {
            $lateLimit = !empty($timeWindow->late_time) ? Carbon::parse($today . ' ' . $timeWindow->late_time) : Carbon::parse($today . ' ' . $timeWindow->login_start)->addMinutes(60);
            $isLate = $claimedTime->greaterThan($lateLimit);
        }

        $proofPaths = [];
        if ($request->hasFile('proof_images')) {
            foreach ($request->file('proof_images') as $file) {
                $media = $mediaConverter->uploadAndConvert($file);
                if ($media) $proofPaths[] = $media->file_path;
            }
        }

        // 🔥 FIX 1: Strict TRAP Logic using Approved Dates & Ignoring Short Leave 🔥
        $employeeId = $login->employee->id ?? 0;
        $activeLeave = null;
        if ($employeeId) {
            $activeLeave = LeaveApplication::where('user_id', $employeeId)
                ->where('status', 'approved')
                ->whereNotNull('approved_start_datetime')
                ->where('approved_start_datetime', '<=', now()->format('Y-m-d 23:59:59'))
                ->where('approved_end_datetime', '>=', now()->format('Y-m-d 00:00:00'))
                ->where('application_type', '!=', 'Short Leave') 
                ->first();
        }

        $verificationStatus = (count($proofPaths) > 0 || $request->reason) ? 'pending' : 'none';
        $finalReason = $request->reason;

        if ($activeLeave) {
            $verificationStatus = 'pending'; 
            $alertMsg = "⚠️ SYSTEM ALERT: Punch attempted during an active Approved Leave.";
            $finalReason = $finalReason ? $alertMsg . " | Employee Note: " . $finalReason : $alertMsg;
        }

        $attendance = Attendance::where('user_id', $login->user_id)->where('date', $today)->first();

        $newSession = ['in' => $claimedTimeStr, 'out' => null, 'lat' => $request->latitude ?? null, 'lng' => $request->longitude ?? null];

        if (!$attendance) {
            Attendance::create([
                'user_id' => $login->user_id, 'date' => $today, 'present' => 1, 'login_time' => $claimedTimeStr,
                'latitude' => $request->latitude, 'longitude' => $request->longitude, 'remarks' => 'Manual Punch via Dashboard',
                'session_logs' => json_encode([$newSession]), 'is_late_punch' => $isLate, 'punch_reason' => $finalReason,
                'punch_proof_images' => count($proofPaths) > 0 ? $proofPaths : null, 'hr_verification_status' => $verificationStatus
            ]);
        } else {
            $logs = $attendance->session_logs ? json_decode($attendance->session_logs, true) : [];
            $logs[] = $newSession;
            $attendance->update(['session_logs' => json_encode($logs)]);
        }

        return response()->json(['status' => 'success', 'message' => $verificationStatus === 'pending' ? 'Punch Recorded. Sent to HR for Validation!' : 'Attendance Marked Successfully!']);
    }

   public function logout(Request $request)
    {
        $request->validate([
            'panel_id' => 'required|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string'
        ]);
        
        $login = EmployeeLogin::where('panel_id', $request->panel_id)->first();
        $isAutoLogout = $request->has('is_auto') && $request->is_auto == 1;

        if ($login) {
            $today = now()->format('Y-m-d');
            $currentTime = now()->format('H:i:s');
            $attendance = Attendance::where('user_id', $login->user_id)->where('date', $today)->first();

            if ($attendance) {
                $logs = $attendance->session_logs ? json_decode($attendance->session_logs, true) : [];
                $lastIndex = count($logs) - 1;

                if ($lastIndex >= 0 && $logs[$lastIndex]['out'] === null) {
                    $logs[$lastIndex]['out'] = $isAutoLogout ? 'Auto-Closed' : $currentTime;
                    if (!$isAutoLogout) {
                        $logs[$lastIndex]['out_lat'] = $request->latitude ?? null;
                        $logs[$lastIndex]['out_lng'] = $request->longitude ?? null;
                    }
                }

                $updateData = ['session_logs' => json_encode($logs)];
                if (!$isAutoLogout) {
                    $updateData['logout_time'] = $currentTime;
                }
                $attendance->update($updateData);
            }
        }
        
        // 🔥 FIX: Token delete wala code hata diya gaya hai taaki user logged in rahe.
        return response()->json(['status' => 'success', 'message' => 'Punched Out Successfully!']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $permissions = \App\Http\Controllers\Controller::getLiveActivePermissions($user);
        $isSecondaryDevice = false;

        if ($user && $user->currentAccessToken() && str_contains($user->currentAccessToken()->name, '_S_')) {
            $isSecondaryDevice = true;
        }

        // 1. Fetch Company Details
        $logoUrl = null;
        $companyName = 'N/A';
        $branchName = 'N/A';

        if (!empty($user->company_id)) {
            $company = \Illuminate\Support\Facades\DB::table('companies')->where('id', $user->company_id)->first();
            if ($company) {
                $companyName = $company->company_name;
                $logoUrl = !empty($company->company_logo) ? asset($company->company_logo) : null;
            }
        }
        if (!empty($user->branch_id)) {
            $branch = \Illuminate\Support\Facades\DB::table('branches')->where('id', $user->branch_id)->value('branch_name');
            if ($branch) $branchName = $branch;
        }

       // 2. 🔥 RAW DB QUERY TO GET EXACT DESIGNATION & PHOTO 🔥
        $empRecord = \Illuminate\Support\Facades\DB::table('adm_regist')->where('id', $user->id)->first();
        
        $designationName = 'Employee Access';
        $departmentName = 'General Dept'; // 🔥 NAYA
        $passportPhoto = null;

        if ($empRecord) {
            $passportPhoto = $empRecord->passport_photo ?? $empRecord->photo ?? null;
            
            if (!empty($empRecord->designation_id)) {
                $desigName = \Illuminate\Support\Facades\DB::table('designations')->where('id', $empRecord->designation_id)->value('designation_name');
                if ($desigName) {
                    $designationName = $desigName;
                }
            } elseif (!empty($empRecord->designation)) {
                $designationName = $empRecord->designation;
            }

            // 🔥 NAYA: Fetch Department Name
            if (!empty($empRecord->department_id)) {
                $deptName = \Illuminate\Support\Facades\DB::table('departments')->where('id', $empRecord->department_id)->value('department_name');
                if ($deptName) {
                    $departmentName = $deptName;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'member_id' => $user->member_id, 
                'contact_no' => $user->contact_no ?? $user->mobile ?? '',
                'address' => $user->address ?? $user->permanent_address ?? '',
                'name' => $user->full_name ?? $user->employee_name ?? 'User',
                'email' => $user->email,
                'designation_name' => $designationName,
                'department_name' => $departmentName, // 🔥 NAYA
                'passport_photo' => $passportPhoto ? asset($passportPhoto) : null,
                'company_logo' => $logoUrl,
                'company_id' => $user->company_id ?? '',
                'company_name' => $companyName,
                'branch_id' => $user->branch_id ?? null,
                'branch_name' => $branchName,
                'is_secondary_device' => $isSecondaryDevice,
                'permissions' => $permissions,
            ]
        ]);
    }
   // ==========================================
    // DASHBOARD DATA (100% HR LOGIC SYNC)
    // ==========================================
    public function getDashboardData(Request $request)
    {
        $user = $request->user();
        $month = $request->month ?? date('m');
        $year = $request->year ?? date('Y');
        $memberId = $user->member_id ?? $user->id;

        $monthStart = sprintf('%04d-%02d-01', $year, $month);
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        $checkStart = date('Y-m-d', strtotime($monthStart . ' -1 day'));
        $checkEnd = date('Y-m-d', strtotime($monthEnd . ' +1 day'));

        $attendances = Attendance::where('user_id', $memberId)->whereBetween('date', [$checkStart, $checkEnd])->get()->keyBy('date');
        $corrections = \App\Models\AttendanceCorrection::where('user_id', $user->id)->whereBetween('date', [$checkStart, $checkEnd])->get()->keyBy('date');

        $holidays = \App\Models\Holiday::where(function ($query) use ($checkStart, $checkEnd) {
            $query->whereBetween('start_date', [$checkStart, $checkEnd])->orWhere(function ($q) use ($checkStart, $checkEnd) {
                $q->where('start_date', '<=', $checkEnd)->where(function ($subQ) use ($checkStart) { $subQ->whereNull('end_date')->orWhere('end_date', '>=', $checkStart); });
            });
        })->get()->flatMap(function ($holiday) {
            $period = \Carbon\CarbonPeriod::create($holiday->start_date, $holiday->end_date ?? $holiday->start_date);
            $dts = []; foreach ($period as $dt) { $dts[] = $dt->format('Y-m-d'); } return $dts;
        })->toArray();
        $holidayDates = array_flip($holidays);

        // 🔥 FIX 2: Strict Leave Dates Fetch
        $leaves = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereNotNull('approved_start_datetime')
            ->where('approved_start_datetime', '<=', $checkEnd . ' 23:59:59')
            ->where('approved_end_datetime', '>=', $checkStart . ' 00:00:00')
            ->get();

        $timeWindow = AttendanceTimeWindow::where('company_id', $user->company_id)
            ->where(function ($q) use ($user) { if ($user->branch_id) $q->where('branch_id', $user->branch_id)->orWhereNull('branch_id'); else $q->whereNull('branch_id'); })
            ->where('status', 'active')->orderBy('branch_id', 'desc')->first();

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $todayDate = date('Y-m-d');
        $joinDate = $user->created_at ? Carbon::parse($user->created_at)->startOfDay() : Carbon::parse('2000-01-01');

        $dailyData = []; $rawStatuses = []; $lateCount = 0;

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $i);
            $dateObj = Carbon::parse($dateStr);
            $isTuesday = $dateObj->isTuesday();
            $isHoliday = isset($holidayDates[$dateStr]);
            $isFuture = $dateStr > $todayDate;

            $status = 'absent'; $remark = ''; $inTime = '--:--'; $outTime = '--:--';

            if ($attendances->has($dateStr)) {
                $tempAtt = $attendances->get($dateStr);
                $inTime = $tempAtt->login_time ? date('h:i A', strtotime($tempAtt->login_time)) : '--:--';
                $outTime = $tempAtt->logout_time ? date('h:i A', strtotime($tempAtt->logout_time)) : '--:--';
            }

            $onLeave = false; $leaveType = 'L';
            foreach ($leaves as $leave) {
                $lStart = Carbon::parse($leave->approved_start_datetime)->format('Y-m-d');
                $lEnd = Carbon::parse($leave->approved_end_datetime)->format('Y-m-d');
                if ($dateStr >= $lStart && $dateStr <= $lEnd) {
                    $onLeave = true;
                    if ($leave->application_type === 'Short Leave') $leaveType = 'SL';
                    break;
                }
            }

            if ($dateObj->lt($joinDate)) {
                $status = 'n_a'; $remark = 'Before Joining';
            } elseif ($isFuture) {
                $status = 'future'; $remark = $isTuesday ? 'Upcoming Weekly Off' : ($isHoliday ? 'Upcoming Holiday' : '-');
            } elseif ($corrections->has($dateStr)) {
                $corr = $corrections->get($dateStr);
                $statusMap = ['P' => 'present', 'A' => 'absent', 'HD' => 'half_day', 'L' => 'leave', 'SL' => 'sl', 'WO' => 'off', 'HO' => 'holiday', 'LT' => 'lt'];
                $status = $statusMap[$corr->corrected_status] ?? 'present';
                $remark = "Corrected: " . $corr->reason;
            } elseif ($attendances->has($dateStr)) {
                $att = $attendances->get($dateStr);

                if ($isTuesday || $isHoliday) {
                    $status = 'extra_day'; $remark = 'Worked on ' . ($isTuesday ? 'Weekly Off' : 'Holiday');
                }
                elseif (!empty($att->login_time) && empty($att->logout_time)) {
                    // 🔥 FIX 3: Current Day 'In-Office' & Late Logic (Shivam Fix)
                    if ($dateStr === $todayDate) {
                        $isLateToday = false;
                        if ($timeWindow) {
                            $actualLogin = Carbon::parse($dateStr . ' ' . $att->login_time);
                            $lateLimit = !empty($timeWindow->late_time) ? Carbon::parse($dateStr . ' ' . $timeWindow->late_time) : Carbon::parse($dateStr . ' ' . $timeWindow->login_start)->addMinutes(60);
                            if ($actualLogin->gt($lateLimit)) { $isLateToday = true; }
                        } elseif ($att->is_late_punch) { $isLateToday = true; }

                        if ($isLateToday) $lateCount++;

                        if ($onLeave && $leaveType === 'SL') { $status = 'sl'; $remark = 'Short Leave (In-Office)'; }
                        elseif ($isLateToday) { $status = 'lt'; $remark = 'Late Punch In (Active)'; }
                        else { $status = 'present'; $remark = 'Present In-Office (Active)'; }
                    } else {
                        // Agar pichla din hai aur logout nahi kiya tab jaake HD do
                        $status = 'half_day'; $remark = 'Missed Punch-Out';
                    }
                }
                elseif (empty($att->login_time) && !empty($att->logout_time)) { $status = 'half_day'; $remark = 'Missed Punch-In'; }
                elseif (empty($att->login_time) && empty($att->logout_time)) { $status = 'absent'; $remark = 'Did not punch In/Out'; }
                else {
                    // Dono Punches Maujud Hain
                    $isLateToday = false;
                    if ($timeWindow) {
                        $actualLogin = Carbon::parse($dateStr . ' ' . $att->login_time);
                        $lateLimit = !empty($timeWindow->late_time) ? Carbon::parse($dateStr . ' ' . $timeWindow->late_time) : Carbon::parse($dateStr . ' ' . $timeWindow->login_start)->addMinutes(60);
                        if ($actualLogin->gt($lateLimit)) { $isLateToday = true; }
                    } elseif ($att->is_late_punch) { $isLateToday = true; }

                    if ($isLateToday) $lateCount++;

                    $minHoursRaw = $timeWindow ? $timeWindow->min_working_hours : 8.25;
                    $minHours = (strpos((string)$minHoursRaw, ':') !== false) ? (int)explode(':', $minHoursRaw)[0] + ((int)explode(':', $minHoursRaw)[1] / 60) : (float)$minHoursRaw;

                    $in = Carbon::parse($dateStr . ' ' . $att->login_time); $out = Carbon::parse($dateStr . ' ' . $att->logout_time);
                    $diffSeconds = $out->timestamp - $in->timestamp;
                    if ($diffSeconds < 0) { $out->addDay(); $diffSeconds = $out->timestamp - $in->timestamp; }

                   // 🔥 NAYA: Exact Working Hours aur Extra Minutes calculation 🔥
                    $workedMins = round($diffSeconds / 60);
                    $h = floor($workedMins / 60); $m = $workedMins % 60;
                    $workedHoursStr = "{$h}h {$m}m";
                    
                    $isShortHours = ($workedMins < ($minHours * 60));
                    
                    if ($workedMins > ($minHours * 60) && (!$onLeave || $leaveType !== 'SL')) {
                        $stats['extra_minutes'] = ($stats['extra_minutes'] ?? 0) + ($workedMins - ($minHours * 60));
                    }
                    if ($onLeave && $leaveType === 'SL') $isShortHours = false; // Bypass short hours rule for SL

                    if ($isLateToday && $lateCount > 0 && ($lateCount % 3 == 0)) { $status = 'absent'; $remark = 'Absent (Penalty for 3 Late Punches)'; }
                    elseif ($isShortHours) { $status = 'half_day'; $wHours = floor($workedHours); $wMins = round(($workedHours - $wHours) * 60); $remark = 'Short Working Hours (' . $wHours . 'h ' . $wMins . 'm)'; }
                    elseif ($onLeave && $leaveType === 'SL') { $status = 'sl'; $remark = ($isLateToday ? 'Late In & ' : '') . 'Short Leave'; }
                    elseif ($isLateToday) { $status = 'lt'; $remark = 'Late Punch In'; }
                    else { $status = 'present'; $remark = $isLateToday ? 'Present (Late)' : 'On Time'; }
                }
            } elseif ($onLeave) {
                if ($leaveType === 'SL' && !$isFuture && $dateStr < $todayDate) {
                    $status = 'absent'; $remark = 'Absent (No punch on Short Leave day)';
                } else {
                    $status = $leaveType === 'SL' ? 'sl' : 'leave'; $remark = $leaveType === 'SL' ? 'Approved Short Leave' : 'Approved Leave';
                }
            } else {
                if ($isTuesday) { $status = 'off'; $remark = 'Weekly Off'; } elseif ($isHoliday) { $status = 'holiday'; $remark = 'Holiday'; } else { $status = 'absent'; }
            }

          $rawStatuses[$dateStr] = ['status' => $status, 'remark' => $remark, 'login_time' => $inTime, 'logout_time' => $outTime, 'worked_time' => $workedHoursStr ?? '--'];
        }

        $stats = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'extra_days' => 0, 'fine_amount' => 0, 'total_leave' => 0, 'late' => 0]; // cl_available hata diya
        
        $dateKeys = array_keys($rawStatuses);
        for ($i = 0; $i < count($dateKeys); $i++) {
            $dStr = $dateKeys[$i]; $data = $rawStatuses[$dStr];

            // 🔥 FIX: Smarter Sandwich Rule (Ab lagatar 2-3 chutiyon ko bhi cross check karega)
            if ($data['status'] === 'off' || $data['status'] === 'holiday') {
                $prevStatus = 'present';
                for ($p = $i - 1; $p >= 0; $p--) {
                    if (!in_array($rawStatuses[$dateKeys[$p]]['status'], ['off', 'holiday'])) {
                        $prevStatus = $rawStatuses[$dateKeys[$p]]['status'];
                        break;
                    }
                }
                
                $nextStatus = 'present';
                for ($n = $i + 1; $n < count($dateKeys); $n++) {
                    if (!in_array($rawStatuses[$dateKeys[$n]]['status'], ['off', 'holiday'])) {
                        $nextStatus = $rawStatuses[$dateKeys[$n]]['status'];
                        break;
                    }
                }

                if (in_array($prevStatus, ['absent', 'leave', 'cl', 'sl']) && in_array($nextStatus, ['absent', 'leave', 'cl', 'sl'])) {
                    $data['status'] = 'absent'; $data['remark'] = 'Sandwich Rule Applied';
                }
            }

            // 🔥 FIX: Yahan se "Auto CL Deduction" wala logic permanently HATA DIYA gaya hai 🔥

            if ($data['status'] === 'present') $stats['present']++;
            elseif ($data['status'] === 'half_day') $stats['half_day']++;
            elseif ($data['status'] === 'absent') $stats['absent']++;
            elseif (in_array($data['status'], ['leave', 'cl', 'sl'])) $stats['total_leave']++;
            elseif ($data['status'] === 'lt') { $stats['late']++; $stats['present']++; }
            elseif ($data['status'] === 'extra_day') $stats['extra_days']++;

            $dailyData[$dStr] = $data;
        }

      $salaryRecord = \App\Models\Salary::where('employee_id', $user->id)->first();
        $perDaySalary = ($salaryRecord && $salaryRecord->amount > 0) ? ($salaryRecord->amount / 30) : 0;
        $stats['fine_amount'] = round(($stats['absent'] + ($stats['half_day'] * 0.5)) * $perDaySalary, 2);

        // 🔥 NAYA: Extra minutes ko Hours & Minutes me convert karein
        $totalExtMins = $stats['extra_minutes'] ?? 0;
        $extH = floor($totalExtMins / 60); $extM = $totalExtMins % 60;
        $stats['extra_hours_str'] = "{$extH}h {$extM}m";

        return response()->json(['status' => 'success', 'month' => $month, 'year' => $year, 'stats' => $stats, 'daily_data' => $dailyData, 'time_window' => $timeWindow]);
    }
}
