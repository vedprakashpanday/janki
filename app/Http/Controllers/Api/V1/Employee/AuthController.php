<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\EmployeeLogin;
use Illuminate\Support\Facades\DB;

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

        $currentTime = now()->format('H:i:s');
        $currentDate = now()->format('Y-m-d');

        // 🔥 FIX: Night-Shift Proof Time Check (Primary) 🔥
        $isPrimaryTime = false;
        if ($login->p_time_from < $login->p_time_to) {
            $isPrimaryTime = ($currentTime >= $login->p_time_from && $currentTime <= $login->p_time_to);
        } else {
            $isPrimaryTime = ($currentTime >= $login->p_time_from || $currentTime <= $login->p_time_to);
        }

        // 🔥 FIX: Night-Shift Proof Time Check (Secondary) 🔥
        $isSecondaryTime = false;
        if ($login->s_status === 'allow' && $login->s_date_from && $login->s_date_to && $login->s_time_from && $login->s_time_to) {
            if ($currentDate >= $login->s_date_from && $currentDate <= $login->s_date_to) {
                if ($login->s_time_from < $login->s_time_to) {
                    $isSecondaryTime = ($currentTime >= $login->s_time_from && $currentTime <= $login->s_time_to);
                } else {
                    $isSecondaryTime = ($currentTime >= $login->s_time_from || $currentTime <= $login->s_time_to);
                }
            }
        }

        // 1. First Time Login Check
        if (empty($login->primary_device)) {
            return response()->json(['status' => 'require_password', 'message' => 'First time login. Enter Password to bind device.']);
        }

        // 2. 🔥 NEW FIX: Smart Secondary Self-Binding 🔥
        // Agar admin ne override diya hai par device empty hai, toh password mango!
        if ($login->s_status === 'allow' && empty($login->secondary_device) && $isSecondaryTime) {
            if (!str_ends_with($login->primary_device, '_' . $baseToken)) {
                return response()->json(['status' => 'require_password', 'message' => 'Emergency Shift Active. Enter Password to temporarily bind this device.']);
            }
        }

        // 3. Check if it's the Primary Device
        if (str_ends_with($login->primary_device, '_' . $baseToken)) {
            if (!$isPrimaryTime && !$isSecondaryTime) {
                return response()->json(['status' => 'error', 'message' => 'Outside working hours. Access Denied.'], 403);
            }
            return $this->sendOtp($login, 'Welcome back! OTP sent to your registered email.');
        }

        // 4. Check if it's the Secondary Device
        if (!empty($login->secondary_device) && str_ends_with($login->secondary_device, '_' . $baseToken)) {
            if (!$isSecondaryTime) {
                return response()->json(['status' => 'error', 'message' => 'Emergency shift parameters expired.'], 403);
            }
            return $this->sendOtp($login, 'Emergency Access Verified! OTP sent to your email.');
        }

        // Log Unauthorized Attempt
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

            // Naya code:
    $fullTokenName = $loginRecord->user_id . '_P_' . $request->device_token;
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

    // ==========================================
    // PHASE 4: SMART AUTO-ATTENDANCE (MULTI-SESSION REPAIRED)
    // ==========================================
    public function markAttendance(Request $request)
    {
        $request->validate([
            'panel_id' => 'required|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
        ]);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->first();
        if (!$login) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $today = now()->format('Y-m-d');
        $currentTime = now()->format('H:i:s');

        $attendance = \App\Models\Attendance::where('user_id', $login->user_id)
            ->where('date', $today)
            ->first();

        // Har login par naya session banega
        $newSession = [
            'in' => $currentTime,
            'out' => null
        ];

        if (!$attendance) {
            // Din ka pehla login
            \App\Models\Attendance::create([
                'user_id' => $login->user_id,
                'date' => $today,
                'present' => 1,
                'login_time' => $currentTime,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'remarks' => 'Auto-marked via Secure Login',
                'session_logs' => json_encode([$newSession]) // Pehla session push hua
            ]);
            return response()->json(['status' => 'success', 'message' => 'Attendance Marked!']);
        } else {
            // Din me dobara login (Array me push karo)
            $logs = $attendance->session_logs ? json_decode($attendance->session_logs, true) : [];
            $lastIndex = count($logs) - 1;

            if ($lastIndex >= 0 && $logs[$lastIndex]['out'] === null) {
                // Agar pichla session bina manual logout ke close hua tha (Auto-Logout)
                $logs[$lastIndex]['out'] = 'Auto-Closed';
            }

            // Naya session jod do
            $logs[] = $newSession;

            $attendance->update([
                'session_logs' => json_encode($logs)
            ]);

            return response()->json(['status' => 'success', 'message' => 'Re-Login Session Recorded!']);
        }
    }

    // ==========================================
    // SECURE LOGOUT (MULTI-SESSION REPAIRED)
    // ==========================================
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
            if ($login->s_status === 'allow') {
                $login->update([
                    's_status' => 'deny',
                    's_time_from' => null,
                    's_time_to' => null
                ]);
            }
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

        // 🔥 YAHAN FIX KIYA HAI: Purane "getAllPermissions" ko hatakar apna "Live" wala lagaya 🔥
        // Ab API frontend ko wahi permission degi jo time ke hisaab se valid hain
        $permissions = \App\Http\Controllers\Controller::getLiveActivePermissions($user);

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
                'branch_id' => $user->branch_id ?? '',
                'branch_name' => $branchName,
                // 🔥 NAYA ARRAY JAYEGA FRONTEND PAR 🔥
                'permissions' => $permissions 
            ]
        ]);
    }
    // ==========================================
    // DASHBOARD DATA (SALARY FIXED)
    // ==========================================
    public function getDashboardData(Request $request)
    {
        $user = $request->user();
        $month = $request->month ?? date('m');
        $year = $request->year ?? date('Y');

        $memberId = $user->member_id ?? $user->id;

        $attendances = \App\Models\Attendance::where('user_id', $memberId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get()->keyBy('date');

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $todayDate = date('Y-m-d');

        $dailyData = [];
        $stats = [
            'present' => 0,
            'absent' => 0,
            'half_day' => 0,
            'ot_hours' => 0,
            'fine_amount' => 0,
            'cl_available' => 1,
            'total_leave' => 0
        ];

       // 🔥 PERFECT FIX: Fetching Exact Salary from Salary Model 🔥
        // Database se direct us employee ki salary nikalenge
        $salaryRecord = \App\Models\Salary::where('employee_id', $user->id)->first();
        
        // Agar admin ne salary daali hai to amount aayega, varna 0
        $salaryAmount = $salaryRecord ? $salaryRecord->amount : 0; 
        
        // 30 Days Basis Calculation (Agar salary 0 hai to per day bhi 0 hoga)
        $perDaySalary = $salaryAmount > 0 ? ($salaryAmount / 30) : 0;

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $i);
            $dayOfWeek = date('w', strtotime($dateStr)); // 0=Sun, 1=Mon, 2=Tue

            if ($dayOfWeek == 2) {
                $dailyData[$dateStr] = ['status' => 'off'];
                continue;
            }

            if ($dateStr > $todayDate) {
                $dailyData[$dateStr] = ['status' => 'future'];
                continue;
            }

            $att = $attendances->get($dateStr);

            if ($att && $att->login_time) {
                $loginTime = $att->login_time;
                $logoutTime = $att->logout_time;

                $hoursWorked = 0;
                $isHalfDay = false;

                if (!$logoutTime && $dateStr != $todayDate) {
                    $isHalfDay = true;
                } elseif ($logoutTime) {
                    $t1 = strtotime($loginTime);
                    $t2 = strtotime($logoutTime);
                    $hoursWorked = ($t2 - $t1) / 3600;
                    if ($hoursWorked < 4.8) {
                        $isHalfDay = true;
                    }
                }

                if ($isHalfDay) {
                    $dailyData[$dateStr] = [
                        'status' => 'half_day',
                        'login_time' => date('h:i A', strtotime($loginTime)),
                        'logout_time' => $logoutTime ? date('h:i A', strtotime($logoutTime)) : '--:--',
                        'ot' => 0
                    ];
                    $stats['half_day']++;
                } else {
                    $ot = ($hoursWorked > 8) ? round($hoursWorked - 8, 1) : 0;
                    $dailyData[$dateStr] = [
                        'status' => 'present',
                        'login_time' => date('h:i A', strtotime($loginTime)),
                        'logout_time' => $logoutTime ? date('h:i A', strtotime($logoutTime)) : '--:--',
                        'ot' => $ot
                    ];
                    $stats['present']++;
                    $stats['ot_hours'] += $ot;
                }
            } else {
                $dailyData[$dateStr] = ['status' => 'absent'];
                if ($stats['cl_available'] > 0) {
                    $stats['cl_available']--;
                    $dailyData[$dateStr]['status'] = 'cl';
                } else {
                    $stats['absent']++;
                }
            }
        }

        $fineDays = $stats['absent'] + ($stats['half_day'] * 0.5);
        $stats['fine_amount'] = round($fineDays * $perDaySalary, 2);
        $stats['total_leave'] = $stats['absent'] + (1 - $stats['cl_available']);

        return response()->json([
            'status' => 'success',
            'month' => $month,
            'year' => $year,
            'stats' => $stats,
            'daily_data' => $dailyData
        ]);
    }
}
