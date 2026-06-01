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
        if ($login->p_status !== 'allow') return response()->json(['status' => 'error', 'message' => 'Access blocked.'], 403);

        // 🔥 1. CRITICAL ZERO-TRUST: CHECK IF DEVICE IS BLOCKED 🔥
        $blockedDevices = $login->blocked_devices ?? [];
        if (in_array($request->device_token, $blockedDevices)) {
            return response()->json([
                'status' => 'error', 
                'message' => 'This device has been BLOCKED by Admin! Access Denied from this machine.'
            ], 403);
        }

        $currentTime = now()->format('H:i:s');
        $isPrimaryTime = ($currentTime >= $login->p_time_from && $currentTime <= $login->p_time_to);
        $isSecondaryTime = ($login->s_status === 'allow' && $login->s_time_from && $login->s_time_to && $currentTime >= $login->s_time_from && $currentTime <= $login->s_time_to);

        if (empty($login->primary_device)) {
            return response()->json(['status' => 'require_password', 'message' => 'First time login. Enter Password to bind device.']);
        }

        if ($login->primary_device === $request->device_token) {
            if (!$isPrimaryTime && !$isSecondaryTime) {
                return response()->json(['status' => 'error', 'message' => 'Outside working hours. Access Denied.'], 403);
            }
            return $this->sendOtp($login, 'Welcome back! OTP sent to your registered email.');
        }

        if ($login->secondary_device === $request->device_token) {
            if (!$isSecondaryTime) {
                return response()->json(['status' => 'error', 'message' => 'Emergency time slot expired.'], 403);
            }
            return $this->sendOtp($login, 'Emergency Access Verified! OTP sent to your email.');
        }

        // UNAUTHORIZED DEVICE LOGGING
        $otherDevices = $login->other_devices ?? [];
        $newAttempt = [
            'device_token' => $request->device_token,
            'latitude' => $request->latitude ?? 'Location Denied',
            'longitude' => $request->longitude ?? 'Location Denied',
            'time' => now()->format('Y-m-d h:i A')
        ];
        
        array_push($otherDevices, $newAttempt);
        if (count($otherDevices) > 5) { array_shift($otherDevices); }

        $login->other_devices = $otherDevices;
        $login->save();

        return response()->json([
            'status' => 'unauthorized_device',
            'message' => 'Unrecognized device blocked! Request sent to Admin along with your location.'
        ], 403);
    }

    // Helper method for generating OTP (Code clean rakhne ke liye)
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

        


    // ==========================================
    // PHASE 2: BIND DEVICE (FIRST TIME LOGIN)
    // ==========================================
    public function bindDevice(Request $request)
    {
        $request->validate([
            'panel_id' => 'required|string',
            'panel_password' => 'required|string',
            'device_token' => 'required|string'
        ]);

        $login = EmployeeLogin::where('panel_id', $request->panel_id)->first();

        // Check ID & Password
        if (!$login || $login->panel_password !== $request->panel_password) {
            return response()->json(['status' => 'error', 'message' => 'Invalid Panel Password!'], 401);
        }

        // Check if device is already bound
        if (!empty($login->primary_device)) {
            return response()->json(['status' => 'error', 'message' => 'A device is already bound to this ID.'], 403);
        }

        // Bind the device
        $login->primary_device = $request->device_token;
        $login->save();

        // Yahan aap Laravel Sanctum/Passport ka token generate kar sakte hain
        // Filhaal hum ek dummy token bhej rahe hain jisko UI save karega
        $empToken = base64_encode($login->user_id . ':' . time()); 

        return response()->json([
            'status' => 'success', 
            'message' => 'Device bound successfully! Redirecting...',
            'emp_token' => $empToken
        ]);
    }

 public function verifyOtp(Request $request)
    {
        // 1. Validate Input
        $request->validate([
            'panel_id' => 'required',
            'panel_otp' => 'required',
            'device_token' => 'required'
        ]);

        try {
            // 2. Check OTP in employee_logins table (Ya jo bhi aapki table hai)
            $loginRecord = DB::table('employee_logins') // <-- Apni OTP table ka naam confirm karein
                ->where('panel_id', $request->panel_id)
                ->where('panel_otp', $request->panel_otp)
                ->first();

            // Agar OTP match nahi hua
            if (!$loginRecord) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Invalid OTP or Panel ID!'
                ], 401);
            }

            // 3. Get the Actual Employee from adm_regist
            // (Assuming loginRecord mein employee_id ya member_id save hota hai)
          $employee = Employee::where('member_id', $loginRecord->user_id)->first();
            // NOTE: Agar aapki DB me column ka naam alag hai, toh yahan change karein (e.g. $loginRecord->adm_regist_id)

            if (!$employee) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Employee Record Not Found in Database!'
                ], 404);
            }

            // 4. 🔥 GENERATE SANCTUM TOKEN (Yahan jadu hoga) 🔥
            // Purane device tokens clear karna chahein toh ye line use karein:
            // $employee->tokens()->where('name', $request->device_token)->delete();
            
            $token = $employee->createToken($request->device_token)->plainTextToken;

            // Optional: OTP use hone ke baad usko null kar dein taaki dobara use na ho
            // DB::table('employee_logins')->where('id', $loginRecord->id)->update(['panel_otp' => null]);

            // 5. Send Success Response to Frontend
            return response()->json([
                'status' => 'success',
                'message' => 'Login Successful',
                'emp_token' => $token, // Naya, Asli Sanctum Token
                'emp_panel_id' => $request->panel_id
            ]);

        } catch (\Exception $e) {
            // Agar koi error aayega toh API chup chap fail nahi hogi, humein exact error batayegi
            return response()->json([
                'status' => 'error', 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // PHASE 4: SMART AUTO-ATTENDANCE
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

        // Check karte hain ki kya aaj ki attendance pehle hi lag chuki hai?
        $attendance = \App\Models\Attendance::where('user_id', $login->user_id)
                                            ->where('date', $today)
                                            ->first();

        // Agar nahi lagi hai, toh nayi attendance mark kar do
        if (!$attendance) {
            \App\Models\Attendance::create([
                'user_id' => $login->user_id,
                'date' => $today,
                'present' => 1,
                'login_time' => now()->format('H:i:s'),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'remarks' => 'Auto-marked via Secure Login'
            ]);
            return response()->json(['status' => 'success', 'message' => 'Attendance Marked!']);
        }

        // Agar pehle se lagi hai, toh kuch mat karo
        return response()->json(['status' => 'already_marked', 'message' => 'Welcome back!']);
    }

    public function logout(Request $request)
    {
        $request->validate(['panel_id' => 'required|string']);
        $login = EmployeeLogin::where('panel_id', $request->panel_id)->first();
        
        if ($login) {
            $today = now()->format('Y-m-d');
            $attendance = \App\Models\Attendance::where('user_id', $login->user_id)
                                                ->where('date', $today)->first();
            if ($attendance) {
                $attendance->update(['logout_time' => now()->format('H:i:s')]);
            }

            // 🔥 CLEAN-UP: Remove Emergency Access on Logout 🔥
            if ($login->s_status === 'allow') {
                $login->update([
                    's_status' => 'deny',
                    's_time_from' => null,
                    's_time_to' => null
                    // secondary_device ko hum nahi hatayenge, taaki record rahe ki kis device se login hua tha
                ]);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Logged out safely!']);
    }

// 🔥 NAYA FUNCTION 🔥
    public function me(Request $request)
    {
        $user = $request->user();

        // Spatie se employee ki current assigned permissions nikalein
        $permissions = $user->getAllPermissions()->pluck('name');

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->full_name ?? $user->employee_name, // Name set karega
                'email' => $user->email,
                'designation_name' => 'Employee Access',
                'company_logo' => null, // Agar db me link hai toh yahan pass karein
                'permissions' => $permissions // Yeh JS padhega aur menu dikhayega
            ]
        ]);
    }



}