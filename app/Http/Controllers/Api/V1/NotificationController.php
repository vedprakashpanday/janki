<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // 📥 1. Sirf Unread (Naye) Alerts Bhejo
    public function getUnread(Request $request)
    {
        $user = auth()->user() ?? auth('sanctum')->user();
        if (!$user) return response()->json(['success' => false], 401);

        // Sirf top 20 unread notifications layega (Lightweight & Fast!)
        $notifications = $user->unreadNotifications()->take(20)->get();
        
        return response()->json(['success' => true, 'data' => $notifications]);
    }

    // 👁️ 2. Jab user Bell Icon khole, sabko 'Seen/Read' mark kar do
    public function markAsRead(Request $request)
    {
        $user = auth()->user() ?? auth('sanctum')->user();
        if ($user) {
            $user->unreadNotifications->markAsRead(); // Ye database me read_at column bhar dega
        }
        return response()->json(['success' => true]);
    }
}