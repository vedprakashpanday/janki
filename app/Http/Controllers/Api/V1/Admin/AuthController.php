<?php
namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail; 
use App\Mail\AdminActionMail;
use App\Events\UserLogoutEvent;

class AuthController extends Controller
{
    // Device ka naam nikalne ke liye chhota sa helper function
    private function getDeviceName($userAgent) {
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
        
        Mail::to('ved526pandit@gmail.com')->send(new AdminActionMail('Admin Login Request', $user->email, $approveUrl, $rejectUrl));

        return response()->json(['status' => 'success', 'message' => 'Approval email sent. Please wait.']);
    }

    // ================== NAYE SESSION MANAGEMENT APIs ==================

    // 1. Saare active devices ki list bheje
    public function getActiveSessions(Request $request) {
        $tokens = $request->user()->tokens;
        $currentId = $request->user()->currentAccessToken()->id;
        
        $sessions = $tokens->map(function($token) use ($currentId) {
            return [
                'id' => $token->id,
                'name' => $token->name, // Device name
                'last_used' => $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Just now',
                'is_current' => $token->id === $currentId
            ];
        });

        return response()->json(['status' => 'success', 'data' => $sessions]);
    }

  public function logoutCurrent(Request $request) {
        $user = $request->user();
        $tokenId = $user->currentAccessToken()->id;
        
        $user->currentAccessToken()->delete();
        
        // Broadcast for current device
        broadcast(new UserLogoutEvent($user->id, $tokenId));

        return response()->json(['status' => 'success', 'message' => 'Logged out']);
    }

    public function logoutDevice(Request $request, $tokenId) {
        $user = $request->user();
        $user->tokens()->where('id', $tokenId)->delete();
        
        // Broadcast specific device logout
        broadcast(new UserLogoutEvent($user->id, $tokenId));

        return response()->json(['status' => 'success', 'message' => 'Device logged out']);
    }

    public function logoutAll(Request $request) {
        $user = $request->user();
        $user->tokens()->delete();
        
        // Broadcast all devices logout (tokenId = null)
        broadcast(new UserLogoutEvent($user->id, null));

        return response()->json(['status' => 'success', 'message' => 'Logged out from all']);
    }
}