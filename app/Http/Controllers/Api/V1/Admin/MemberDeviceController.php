<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MemberDevice;
use App\Models\MemberLoginSession;
use Illuminate\Support\Facades\DB;

class MemberDeviceController extends Controller
{
    // 🔥 Security Check: Sirf CEO aur admin@jankivilla.com allow honge
    private function checkStrictAccess()
    {
        $user = auth()->user();
        if (!$user) return false;

        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if (in_array(strtolower($user->email ?? ''), $developerEmails) || (method_exists($user, 'hasRole') && $user->hasRole(['CEO', 'Super Admin']))) {
            return true;
        }
        return false;
    }

    // 1. Get Devices (Grouped by Member) & Filters
    public function index(Request $request)
    {
        if (!$this->checkStrictAccess()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access!'], 403);
        }

        $query = MemberDevice::with(['member.company', 'member.branch', 'member.department']);

        // Apply Multiple Select Filters
        if ($request->filled('company_ids')) {
            $companyIds = is_array($request->company_ids) ? $request->company_ids : explode(',', $request->company_ids);
            $query->whereHas('member', function($q) use ($companyIds) { $q->whereIn('company_id', $companyIds); });
        }
        if ($request->filled('branch_ids')) {
            $branchIds = is_array($request->branch_ids) ? $request->branch_ids : explode(',', $request->branch_ids);
            $query->whereHas('member', function($q) use ($branchIds) { $q->whereIn('branch_id', $branchIds); });
        }
        if ($request->filled('department_ids')) {
            $deptIds = is_array($request->department_ids) ? $request->department_ids : explode(',', $request->department_ids);
            $query->whereHas('member', function($q) use ($deptIds) { $q->whereIn('department_id', $deptIds); });
        }

        $devices = $query->orderBy('id', 'desc')->get();

       $grouped = $devices->groupBy('member_id')->map(function ($deviceGroup) {
            $member = $deviceGroup->first()->member;
            
            // 🔥 FIX: Column aur Relation ke naam ka conflict bypass kiya
            $companyObj = $member ? $member->company()->first() : null;
            $branchObj = $member ? $member->branch()->first() : null;
            $deptObj = $member ? $member->department()->first() : null;

            return [
                'member_id' => $member ? $member->member_id : 'Unknown',
                'member_name' => $member ? $member->member_name : 'Unknown',
                'mobile' => $member ? $member->mobile : '',
                'company' => $companyObj ? $companyObj->company_name : 'N/A',
                'branch' => $branchObj ? $branchObj->branch_name : 'HO',
                'department' => $deptObj ? $deptObj->department_name : 'N/A',
                'devices' => $deviceGroup
            ];
        })->values();

        return response()->json(['status' => 'success', 'data' => $grouped]);
    }

    // 3. Swap Device Type (Dono Taraf)
    public function swapType(Request $request, $id)
    {
        if (!$this->checkStrictAccess()) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        $device = MemberDevice::findOrFail($id);
        $newType = $request->device_type; // 'Primary' ya 'Secondary'

        if ($newType === 'Primary') {
            // Demote existing Primary to Secondary
            MemberDevice::where('member_id', $device->member_id)
                        ->where('device_type', 'Primary')
                        ->update(['device_type' => 'Secondary', 'device_code' => DB::raw("REPLACE(device_code, '_P', '_S')")]);
            $device->device_code = str_replace(['_S', '_O'], '_P', $device->device_code);
        } else if ($newType === 'Secondary') {
            $device->device_code = str_replace(['_P', '_O'], '_S', $device->device_code);
        }

        $device->device_type = $newType;
        if ($device->status === 'blocked') $device->status = 'active'; // Promote hote hi unblock
        $device->save();

        return response()->json(['status' => 'success', 'message' => "Device successfully set as " . $newType]);
    }
    // 2. Change Status (Block / Active)
    public function updateStatus(Request $request, $id)
    {
        if (!$this->checkStrictAccess()) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        $device = MemberDevice::findOrFail($id);
        $device->status = $request->status; // 'active' ya 'blocked'
        $device->save();

        return response()->json(['status' => 'success', 'message' => "Device status updated to " . ucfirst($request->status)]);
    }

   

    // 4. View Tracking Logs (Location & Time)
    public function getLogs($id)
    {
        if (!$this->checkStrictAccess()) return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 403);

        $logs = MemberLoginSession::where('member_device_id', $id)->orderBy('id', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $logs]);
    }
}