<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MemberAttendance;
use App\Models\MemberLocationLog;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // 🟢 1. Check Today's Status (Naya Function: Page load par call hoga)
    public function getTodayStatus(Request $request)
    {
        $member = $request->user();
        $today = Carbon::today()->toDateString();
        
        $attendance = MemberAttendance::where('member_id', $member->id)
                                      ->where('date', $today)
                                      ->first();

        // Agar aaj ki koi entry nahi hai
        if (!$attendance) {
            return response()->json(['status' => 'success', 'action' => 'pending']);
        }

        // Agar entry hai, par Punch Out nahi hua hai
        if (empty($attendance->punch_out_time)) {
            return response()->json(['status' => 'success', 'action' => 'punched_in']);
        }

        // Agar Punch Out bhi ho chuka hai
        return response()->json(['status' => 'success', 'action' => 'completed']);
    }

    // 🟢 2. Punch In / Punch Out API
    public function markAttendance(Request $request)
    {
        $member = $request->user();
        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now();

        $attendance = MemberAttendance::where('member_id', $member->id)
                                      ->where('date', $today)
                                      ->first();

        if (!$attendance) {
            // PUNCH IN
            MemberAttendance::create([
                'member_id'          => $member->id,
                'date'               => $today,
                'status'             => 'present',
                'punch_in_time'      => $currentTime,
                'punch_in_latitude'  => $request->latitude,
                'punch_in_longitude' => $request->longitude,
            ]);

            return response()->json(['status' => 'success', 'message' => 'Punch In Successful!', 'action' => 'punched_in']);
        } else {
            // PUNCH OUT
            if (empty($attendance->punch_out_time)) {
                $attendance->update([
                    'punch_out_time'      => $currentTime,
                    'punch_out_latitude'  => $request->latitude,
                    'punch_out_longitude' => $request->longitude,
                ]);
                return response()->json(['status' => 'success', 'message' => 'Punch Out Successful!', 'action' => 'completed']);
            }
            
            return response()->json(['status' => 'info', 'message' => 'Already Punched Out for today.', 'action' => 'completed']);
        }
    }

    // 🟢 3. Background Location Ping API (Continuous Tracking)
    public function pingLocation(Request $request)
    {
        $member = $request->user();
        
        MemberLocationLog::create([
            'member_id'  => $member->id,
            'log_date'   => Carbon::today()->toDateString(),
            'latitude'   => $request->latitude,
            'longitude'  => $request->longitude,
            'tracked_at' => Carbon::now(),
        ]);

        return response()->json(['status' => 'success']);
    }

    // 🟢 4. View Calendar / Monthly Attendance API
    public function getMonthlyAttendance(Request $request)
    {
        $member = $request->user();
        
        $month = $request->month ?? date('m');
        $year = $request->year ?? date('Y');

        $attendances = MemberAttendance::where('member_id', $member->id)
                            ->whereMonth('date', $month)
                            ->whereYear('date', $year)
                            ->get()
                            ->keyBy('date'); 

        return response()->json([
            'status' => 'success',
            'joining_date' => $member->created_at ? $member->created_at->toDateString() : null, 
            'data' => $attendances
        ]);
    }
}