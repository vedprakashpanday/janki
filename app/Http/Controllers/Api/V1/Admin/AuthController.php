<?php
namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail; // Email ke liye baad me use karenge
use App\Mail\AdminActionMail;

class AuthController extends Controller
{
    public function requestLogin(Request $request)
    {
        // 1. Validate data from AJAX
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'session_id' => 'required' // Ye jQuery se aayega Reverb ke liye
        ]);

        $user = User::where('email', $request->email)->first();

        // 2. Check Password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        // 3. Admin Role Check
        if ($user->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only admins can access this panel'
            ], 403);
        }

        // 4. Save to Pending Actions
        $actionId = DB::table('pending_actions')->insertGetId([
            'action_type' => 'admin_login',
            'user_id' => $user->id,
            'payload' => json_encode(['session_id' => $request->session_id]),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Generate Magic Links (Signed URLs)
        // Ye routes hum aage banayenge
        $approveUrl = URL::signedRoute('admin.action.approve', ['id' => $actionId]);
        $rejectUrl = URL::signedRoute('admin.action.reject', ['id' => $actionId]);

        // 6. Send Email (Abhi ke liye comment kar rakha hai jab tak mail view na ban jaye)
        Mail::to('ved526543@gmail.com')->send(new AdminActionMail('Admin Login Request', $user->email, $approveUrl, $rejectUrl));

        return response()->json([
            'status' => 'success',
            'message' => 'Approval email sent to master admin.'
        ], 200);
    }
}