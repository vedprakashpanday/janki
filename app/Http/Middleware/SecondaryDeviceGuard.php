<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecondaryDeviceGuard
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $tokenName = $user->currentAccessToken()->name;
            
            // Check if Token has '_S_' (Secondary Device)
            if (str_contains($tokenName, '_S_')) {
                $request->attributes->add(['is_secondary_device' => true]);

                // 1. BLOCKED ACTIONS (Export, Print, Delete)
                if ($request->is('api/*/export*') || $request->is('api/*/print*') || $request->is('api/*/delete*') || $request->is('api/*/bulk-delete*')) {
                    return response()->json(['status' => 'error', 'message' => 'Action blocked on Secondary Device.'], 403);
                }

                // 2. DATA BLOCKER (Datatables & Cards ko Empty Array bhejna)
                if ($request->isMethod('get')) {
                    
                    // Ye APIs allowed rahengi (Tasks, Notices, Dropdowns)
                    $safeUrls = ['auth/me', 'dashboard', 'dropdown', 'get-active', 'get-branches', 'get-departments', 'get-employees', 'tasks', 'notice', 'welcome-letter', 'terms-conditions'];
                    
                    $isSafe = false;
                    foreach ($safeUrls as $url) {
                        if (str_contains($request->url(), $url)) {
                            $isSafe = true;
                            break;
                        }
                    }

                    // Agar URL safe nahi hai (jaise General Leads, Panel Access), toh seedha Blank bhejo
                    if (!$isSafe) {
                        return response()->json([
                            "status" => "success",
                            "draw" => intval($request->input('draw', 1)),
                            "recordsTotal" => 0,
                            "recordsFiltered" => 0,
                            "data" => [] // Data yahi se block! UI par kuch jayega hi nahi.
                        ]);
                    }
                }
            }
        }

        return $next($request);
    }
}