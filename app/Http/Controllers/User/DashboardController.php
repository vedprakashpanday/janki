<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\EmployeeLogin;
use App\Models\Attendance;

class DashboardController extends Controller
{
   public function index()
    {
        // 1. Employee ko uske relation (Department, Designation) ke sath load karna
        $user = Auth::user()->load(['department', 'designation']);

        // 2. Device ID (panel_id) nikalna employee_logins table se
        // User id yahan member_id (adm_regist wali) hoti hai
        $loginDetails = EmployeeLogin::where('user_id', $user->member_id)->first();
        $deviceId = $loginDetails ? $loginDetails->panel_id : 'N/A';

        // 3. Sirf Aaj ka Attendance check karna
        $today = now()->format('Y-m-d');
        $todayAttendance = Attendance::where('user_id', $user->member_id)
                                     ->where('date', $today)->first();

        // Default status 'A' (Absent)
        $todayStatus = 'A'; 
        $statusColor = 'danger'; // Red for Absent
        
        if ($todayAttendance) {
            $todayStatus = 'P'; // Present
            $statusColor = 'success'; // Green for Present
            
            // Agar late/half-day ka logic database me save ho raha hai
            // toh yahan 'H' (Half-day) bhi set kar sakte hain future me
        }

        // View me data bhej rahe hain
        return view('user.dashboard', compact('user', 'deviceId', 'todayStatus', 'statusColor', 'todayAttendance'));
    }
}