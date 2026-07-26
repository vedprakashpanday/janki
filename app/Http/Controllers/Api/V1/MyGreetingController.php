<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MyGreetingController extends Controller
{
    public function getMyGreetings(Request $request)
    {
        $user = auth()->user();
        
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        
        // 🔥 Yahan whereIn lagaya hai taaki Birthday aur Promotion dono load hon
        $greetings = $user->notifications()
            ->whereIn('type', [
                'App\Notifications\GreetingNotification',
                'App\Notifications\PromotionNotification'
            ])
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($notif) {
                $data = $notif->data;
                
                // 🔥 Naya Logic: Agar payload me effective_date hai, toh usko dikhao, warna DB ki created_at date lo
                $data['display_date'] = $data['effective_date'] ?? $notif->created_at->format('d M, Y'); 
                
                return $data; 
            });

        return response()->json([
            'success' => true,
            'data' => $greetings
        ]);
    }
}