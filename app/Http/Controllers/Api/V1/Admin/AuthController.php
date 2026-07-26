<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminActionMail;
use App\Events\UserLogoutEvent;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    // Device ka naam nikalne ke liye chhota sa helper function
    private function getDeviceName($userAgent)
    {
        $os = "Unknown OS";
        $browser = "Unknown Browser";

        if (preg_match('/windows nt/i', $userAgent)) $os = 'Windows';
        elseif (preg_match('/macintosh|mac os x/i', $userAgent)) $os = 'Mac OS';
        elseif (preg_match('/android/i', $userAgent)) $os = 'Android';
        elseif (preg_match('/iphone|ipad/i', $userAgent)) $os = 'iOS';

        if (preg_match('/edg/i', $userAgent)) $browser = 'Edge';
        elseif (preg_match('/chrome/i', $userAgent)) $browser = 'Chrome';
        elseif (preg_match('/firefox/i', $userAgent)) $browser = 'Firefox';
        elseif (preg_match('/safari/i', $userAgent)) $browser = 'Safari';

        return "$os - $browser";
    }

    public function requestLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'session_id' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid credentials'], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Only admins can access this panel'], 403);
        }

        // NAYA: Device ka naam nikal kar payload me save karein
        $deviceName = $this->getDeviceName($request->header('User-Agent')) . ' (' . now()->format('d M H:i') . ')';

        $actionId = DB::table('pending_actions')->insertGetId([
            'action_type' => 'admin_login',
            'user_id' => $user->id,
            'payload' => json_encode([
                'session_id' => $request->session_id,
                'device_info' => $deviceName // Store Device Name
            ]),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $approveUrl = URL::signedRoute('admin.action.approve', ['id' => $actionId]);
        $rejectUrl = URL::signedRoute('admin.action.reject', ['id' => $actionId]);

        //abdeveloperspl

        Mail::to('abdeveloperspl@gmail.com')
    ->bcc('ved526543@gmail.com')
    ->send(new AdminActionMail('Admin Login Request', $user->email, $approveUrl, $rejectUrl));

        return response()->json(['status' => 'success', 'message' => 'Approval email sent. Please wait.']);
    }

    // ================== NAYE SESSION MANAGEMENT APIs ==================

    // 1. Saare active devices ki list bheje
    public function getActiveSessions(Request $request)
    {
        $tokens = $request->user()->tokens;
        $currentId = $request->user()->currentAccessToken()->id;

        $sessions = $tokens->map(function ($token) use ($currentId) {
            return [
                'id' => $token->id,
                'name' => $token->name, // Device name
                'last_used' => $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Just now',
                'is_current' => $token->id === $currentId
            ];
        });

        return response()->json(['status' => 'success', 'data' => $sessions]);
    }

    public function logoutCurrent(Request $request)
    {
        $user = $request->user();
        $tokenId = $user->currentAccessToken()->id;

        $user->currentAccessToken()->delete();

        // Broadcast for current device
        broadcast(new UserLogoutEvent($user->id, $tokenId));

        return response()->json(['status' => 'success', 'message' => 'Logged out']);
    }

    public function logoutDevice(Request $request, $tokenId)
    {
        $user = $request->user();
        $user->tokens()->where('id', $tokenId)->delete();

        // Broadcast specific device logout
        broadcast(new UserLogoutEvent($user->id, $tokenId));

        return response()->json(['status' => 'success', 'message' => 'Device logged out']);
    }

    public function logoutAll(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();

        // Broadcast all devices logout (tokenId = null)
        broadcast(new UserLogoutEvent($user->id, null));

        return response()->json(['status' => 'success', 'message' => 'Logged out from all']);
    }

public function me(Request $request)
    {
        $user = $request->user();
        
        // 1. Fetch company logo safely
        $logoUrl = null;
        if (method_exists($user, 'company') && $user->company) {
            $logoName = $user->company->company_logo ?? $user->company->logo ?? null;
            if ($logoName) {
                $logoUrl = asset('uploads/' . $logoName);
            }
        }

        // 2. Fetch permissions safely via Spatie
        $permissions = [];
        if (method_exists($user, 'getAllPermissions')) {
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
        }

        // 🔥 NAYA: Global Context se is_god ka pata lagana
        $context = $this->getGlobalContext();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->full_name ?? $user->employee_name ?? 'Admin', // full_name CEO ke liye
                'email' => $user->email,
                'company_logo' => $logoUrl,
                'permissions' => $permissions,
                'is_god' => $context->is_god ?? false, // Frontend ko bata rahe hain ki ye God hai ya nahi
                'role_level' => $context->role_level ?? 'unknown'
            ]
        ]);
    }
// =========================================================
    // 🔥 SUPER ADMIN (CEO) OTP LOGIN LOGIC
    // =========================================================

    public function superAdminRequestOtp(Request $request)
    {
        $request->validate([
            'login_id' => 'required|string', // Email ya Mobile No.
        ]);

        $loginId = $request->login_id;

        // Check karo ki entry email hai ya contact_no
        $admin = SuperAdmin::where('email', $loginId)
                           ->orWhere('contact_no', $loginId)
                           ->first();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Invalid Email or Mobile Number!'], 404);
        }
        if ($admin->status === 'inactive') {
            return response()->json(['status' => 'error', 'message' => 'Your account is inactive. Please contact support.'], 403);
        }

        // 6-digit Random OTP Generate karna
        $otp = mt_rand(100000, 999999);

        // Cache me OTP 10 minute ke liye store karein
        Cache::put('super_admin_otp_' . $admin->id, $otp, now()->addMinutes(10));

        // OTP Email par bhejna
        if ($admin->email) {
            try {
                Mail::raw("Hello {$admin->full_name},\n\nYour Login OTP for JankiVilla Panel is: {$otp}\nThis OTP is valid for 10 minutes.\n\nDo not share this with anyone.", function ($message) use ($admin) {
                    $message->to($admin->email)
                            ->subject('Super Admin Login OTP - JankiVilla');
                });
            } catch (\Exception $e) {
                // Agar Mail fail ho jaye (jaise local me), to response me OTP bhej do testing ke liye
                return response()->json([
                    'status' => 'success', 
                    'message' => 'OTP sent! (Mail failed, Testing OTP: '.$otp.')',
                    'admin_id' => $admin->id
                ]);
            }
        }

        return response()->json([
            'status' => 'success', 
            'message' => 'OTP has been sent to your registered email.',
            'admin_id' => $admin->id
        ]);
    }

    public function superAdminVerifyOtp(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|integer',
            'otp' => 'required|numeric'
        ]);

        $admin = SuperAdmin::find($request->admin_id);
        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Admin not found!'], 404);
        }

        $cachedOtp = Cache::get('super_admin_otp_' . $admin->id);

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return response()->json(['status' => 'error', 'message' => 'Invalid or Expired OTP!'], 400);
        }

        // OTP Verify hone ke baad cache se uda do
        Cache::forget('super_admin_otp_' . $admin->id);

        // Sanctum Token generate karo
        $token = $admin->createToken('admin_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login Successful!',
            'token' => $token,
            'user' => [
                'id' => $admin->id,
                'name' => $admin->full_name,
                'email' => $admin->email,
                'role' => 'ceo' // Ye aapke getGlobalContext ko bata dega ki CEO aaya hai
            ]
        ]);
    }
    


}
