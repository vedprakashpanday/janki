<?php
namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserApiController extends Controller
{
    public function getDashboardData()
    {
        $user = Auth::guard('api')->user(); // API auth guard
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                // Aur bhi data bhejein jaise recent viewed properties etc.
            ]
        ], 200);
    }
}