<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated AND has the 'admin' role
        if (auth()->check() && auth()->user()->role === 'admin') {
            return $next($request); // Access Granted
        }

        // Access Denied
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized Access. Admin privileges required.'
        ], 403);
    }
}