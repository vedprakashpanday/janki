<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsDeveloper
{
    public function handle(Request $request, Closure $next): Response
    {
        // Yahan apne (Developers) aur SuperAdmin ke emails daal dein
        $developerEmails = [
            'admin@jankivilla.com', 
            'superadmin@example.com',
            'vedprakash@infoera.in' // Aapka email (Example)
        ];

        // Check if user is logged in AND their email is in the God Mode list
        if (auth()->check() && in_array(auth()->user()->email, $developerEmails)) {
            return $next($request); // Access Granted (Sab kuch allow)
        }

        // Access Denied for everyone else (Even for CEO if they try to hit developer API)
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized Access. Strict Developer Privileges Required.'
        ], 403);
    }
}