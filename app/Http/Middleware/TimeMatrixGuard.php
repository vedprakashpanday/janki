<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TimeMatrixGuard
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // User kisi bhi table se ho (adm_regist, directors, super_admins), Laravel khud pakad lega
        $user = auth()->user() ?? auth('sanctum')->user() ?? auth('api')->user();

        // Agar user logged in nahi hai, toh aage badhne do (Auth middleware khud rok lega)
        if (!$user) {
            return $next($request);
        }

        // Master Admins / Developers ko bypass karna ho toh (Optional)
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (in_array($user->email, $developerEmails)) {
            return $next($request);
        }

        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');

        // ==============================================================
        // 🔥 THE 16-CONDITION MASTER LOGIC (RESOLVED IN 4 LINES) 🔥
        // ==============================================================

        // 1. START DATE Check
        if (!empty($user->access_start_date) && $today < $user->access_start_date) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Access Denied: Your access starts from ' . date('d-M-Y', strtotime($user->access_start_date))
            ], 403);
        }

        // 2. END DATE Check
        if (!empty($user->access_end_date) && $today > $user->access_end_date) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Access Expired: Your account access ended on ' . date('d-M-Y', strtotime($user->access_end_date))
            ], 403);
        }

        // 3. SHIFT START TIME Check
        if (!empty($user->daily_start_time) && $currentTime < $user->daily_start_time) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Shift Not Started: You can only access the system after ' . date('h:i A', strtotime($user->daily_start_time))
            ], 403);
        }

        // 4. SHIFT END TIME Check
        if (!empty($user->daily_end_time) && $currentTime > $user->daily_end_time) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Shift Ended: Your system access closed at ' . date('h:i A', strtotime($user->daily_end_time))
            ], 403);
        }

        // Agar chaaro Check pass ho gaye (Ya set hi nahi the), toh Access Allow kardo!
        return $next($request);
    }
}