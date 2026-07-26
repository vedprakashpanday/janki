<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberDevice;
use App\Models\MemberLoginSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;


class AuthController extends Controller
{
    
  public function requestLogin(Request $request)
    {
        $request->validate([
            'login_id' => 'required|string', 
            'password' => 'required|string',
        ]);

        $member = Member::where('email', $request->login_id)
                        ->orWhere('mobile', $request->login_id) 
                        ->first();

        // 🔥 PURE PLAIN TEXT CHECK
        if (!$member || $request->password !== $member->password) {
            return response()->json([
                'status' => 'error',
                'message' => 'Galti! Invalid email/mobile or password.'
            ], 401);
        }

        if (strtolower($member->status) !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Aapka account ' . ucfirst($member->status) . ' hai. Kripya admin se sampark karein.'
            ], 403); 
        }

        // 🔥 FUTURE OTP SETUP (Abhi hardcoded bypass kiya hai)
        $otp = 123456; 

        return response()->json([
            'status' => 'success',
            'message' => 'OTP aapke registered details par bhej diya gaya hai.',
            'member_id' => $member->id 
        ]);
    }
 /**
     * 2. Verify OTP, Strict Device Binding & Live Location Tracking
     */
   /**
     * 2. Verify OTP, Strict Device Binding & Live Location Tracking
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'member_id' => 'required|integer', 
            'otp' => 'required|string',
            'device_token' => 'required|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'browser' => 'nullable|string',
            'os' => 'nullable|string',
        ]);

        $member = Member::find($request->member_id);

        if (!$member) {
            return response()->json(['status' => 'error', 'message' => 'Member nahi mila.'], 404);
        }

        // OTP validation bypass
        if ($request->otp !== '123456') {
            return response()->json(['status' => 'error', 'message' => 'Galat OTP!'], 401);
        }

        // ==========================================
        // 🔥 STRICT DEVICE IDENTIFICATION & BINDING
        // ==========================================
        $currentDeviceToken = $request->device_token;
        
        // 1. Check if device exists globally (Prevent 1062 Duplicate Entry & Check Cross-Login)
        $deviceRecord = MemberDevice::where('device_token', $currentDeviceToken)->first();

        if ($deviceRecord) {
            // RULE 1: Agar device pehle se kisi aur member (mobile no) ke sath registered hai
            if ($deviceRecord->member_id != $member->id) {
                // Location track karke seedha block aur unauthorized access
                MemberLoginSession::create([
                    'member_id' => $member->id,
                    'member_device_id' => $deviceRecord->id,
                    'device_code' => $deviceRecord->device_code,
                    'login_time' => Carbon::now(),
                    'ip_address' => $request->ip(),
                    'login_lat' => $request->latitude,
                    'login_lng' => $request->longitude,
                    'status' => 'Blocked - Unauthorized Access'
                ]);

                return response()->json([
                    'status' => 'error', 
                    'message' => 'Unauthorized access! Ye device kisi aur user ke sath registered hai.'
                ], 403);
            }
        } else {
            // RULE 2: Naya device try kar raha hai, uski limits check karo
            $hasPrimary = MemberDevice::where('member_id', $member->id)->where('device_type', 'Primary')->exists();

            if (!$hasPrimary) {
                $deviceType = 'Primary';
                $deviceCode = $member->member_id . '_P';
                $status = 'active';
            } else {
                // Agar primary hai, toh usko 'Other' assign kar ke status 'blocked' kar do (Admin action ke liye)
                $deviceType = 'Other';
                $deviceCode = $member->member_id . '_O_' . rand(100, 999); 
                $status = 'blocked'; 
            }

            // Database me Naya Device record create kar lo
            $deviceRecord = MemberDevice::create([
                'member_id' => $member->id, 
                'device_token' => $currentDeviceToken,
                'device_code' => $deviceCode,
                'device_type' => $deviceType,
                'device_name' => $request->os . ' - ' . $request->browser,
                'browser' => $request->browser,
                'os' => $request->os,
                'status' => $status
            ]);
        }

        // ==========================================
        // 🔥 LIVE LOCATION & ATTEMPT TRACKING
        // ==========================================
        $sessionStatus = ($deviceRecord->status === 'active' && in_array($deviceRecord->device_type, ['Primary', 'Secondary'])) ? 'Success' : 'Blocked';

        $session = MemberLoginSession::create([
            'member_id' => $member->id, 
            'member_device_id' => $deviceRecord->id,
            'device_code' => $deviceRecord->device_code,
            'login_time' => Carbon::now(),
            'ip_address' => $request->ip(),
            'login_lat' => $request->latitude,
            'login_lng' => $request->longitude,
            'status' => $sessionStatus
        ]);

        // 🔥 BLOCK HANDLING (Agar login success nahi hua)
        if ($sessionStatus === 'Blocked') {
            if ($deviceRecord->device_type === 'Other') {
                
                $hasPrimary = MemberDevice::where('member_id', $member->id)->where('device_type', 'Primary')->exists();
                $hasSecondary = MemberDevice::where('member_id', $member->id)->where('device_type', 'Secondary')->exists();
                
                // RULE 2 Message: Sirf Primary hai, Naya (Secondary) device try kiya
                if ($hasPrimary && !$hasSecondary) {
                    return response()->json([
                        'status' => 'error', 
                        'message' => 'This device is not registered. You can Ask admin to register as Secondary device.'
                    ], 403);
                } else {
                    // RULE 3 Message: Dono limit full, 3rd device try kiya
                    return response()->json([
                        'status' => 'error', 
                        'message' => 'Unauthorized access!'
                    ], 403);
                }
            } else {
                // Primary ya Secondary toh hai, lekin Admin ne jaan-bujh kar status Inactive/Blocked rakha hai
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Your device (' . $deviceRecord->device_code . ') has been blocked by the Admin.'
                ], 403);
            }
        }

        $tokenName = ($deviceRecord->device_type === 'Secondary') ? 'member_token_S_' : 'member_token_P_';
        $token = $member->createToken($tokenName)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login safal raha! (' . $deviceRecord->device_code . ')',
            'token' => $token,
            'user' => $member,
            'session_id' => $session->id 
        ]);
    }

   

    /**
     * 3. Current Member Profile Context (Navbar aur Layout permissions ke liye)
     */
    public function me(Request $request)
    {
        $member = $request->user();

        // Tumhare Controller.php se live permissions fetch karne ke liye static call
        $member->permissions = Controller::getLiveActivePermissions($member);

        return response()->json([
            'status' => 'success',
            'data' => $member,
            // 👇 SIRF YE NAYI KEYS ADD KAREIN COMMON PROFILE KE LIYE 👇
                'profile_mobile' => $member->mobile ?? '', 
                'profile_address' => $member->address ?? '',
                'profile_photo' => $member->passport_photo ? asset($member->passport_photo) : null,
                'profile_id_string' => $member->member_id, // For ID card URL
                'profile_designation' => 'Member', // Ya agar inka bhi designation hai toh wo daal dein
        ]);
    }

/**
     * 4. Secure Logout & Record Logout GPS
     */
    /**
     * 4. Secure Logout & Record Logout GPS
     */
    public function logout(Request $request)
    {
        $request->validate([
            'session_id' => 'required|integer',
            'logout_lat' => 'nullable|string',
            'logout_lng' => 'nullable|string',
        ]);

        $user = $request->user();

        // 🔥 FIX: Logout me bhi real ID use hogi
        MemberLoginSession::where('id', $request->session_id)
            ->where('member_id', $user->id) 
            ->update([
                'logout_time' => Carbon::now(),
                'logout_lat' => $request->logout_lat,
                'logout_lng' => $request->logout_lng,
            ]);

        $user->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out securely.'
        ]);
    }
}
