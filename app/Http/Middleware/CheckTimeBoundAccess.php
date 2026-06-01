<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTimeBoundAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Agar login nahi hai, toh aage jaane do (auth middleware pakad lega)
        if (!$user) return $next($request);

        // 2. God Mode Bypass: Master Admin ko koi nahi rok sakta
        if ($user->email === 'admin@jankivilla.com' || $user->hasRole('Super Admin')) {
            return $next($request);
        }

        $today = now()->format('Y-m-d');
        $currentTime = now()->format('H:i:s');

        // 3. Date Range Lock Check
        if ($user->access_start_date && $today < $user->access_start_date) {
            return $this->blockAccess("Your access hasn't started yet. Active from: " . date('d M Y', strtotime($user->access_start_date)), $request);
        }
        if ($user->access_end_date && $today > $user->access_end_date) {
            return $this->blockAccess("Your system access has expired.", $request);
        }

        // 4. Daily Shift Time Lock Check
        if ($user->daily_start_time && $currentTime < $user->daily_start_time) {
            return $this->blockAccess("Shift hasn't started. Login allowed after " . date('h:i A', strtotime($user->daily_start_time)), $request);
        }
        if ($user->daily_end_time && $currentTime > $user->daily_end_time) {
            return $this->blockAccess("Shift ended. System access is locked after " . date('h:i A', strtotime($user->daily_end_time)), $request);
        }

        return $next($request);
    }

    private function blockAccess($message, $request)
    {
        // Agar API hit hai toh JSON error denge, web hit hai toh view/redirect
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Time Bound Restriction: ' . $message
            ], 403);
        }
        
        abort(403, 'Time Bound Restriction: ' . $message);
    }
}