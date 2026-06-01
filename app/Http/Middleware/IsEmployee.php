<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsEmployee
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if authenticated
        if (!auth()->check()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $user = auth()->user();

        // 2. Check if user belongs to 'adm_regist' (Employee table)
        // Agar aap customer aur employee ko alag table/guard me rakhte hain toh ye secure karega
        if ($user instanceof \App\Models\Employee) {
            
            // Optional: Agar aap employee ka status (active/inactive) check karna chahte hain
            // if($user->status !== 'active') {
            //     return response()->json(['status' => 'error', 'message' => 'Your account is disabled.'], 403);
            // }

            return $next($request); // Access Granted
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized. Only valid employees can access this endpoint.'
        ], 403);
    }
}