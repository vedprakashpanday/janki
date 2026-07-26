<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteVehicleTrip;
use App\Models\Company;
use App\Models\Employee;
use App\Models\SiteAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\MediaConverterService;

class VehicleTripController extends Controller
{
    private function generateSlipNumber($companyId, $slipType, $date)
    {
        $company = Company::find($companyId);
        $compCode = $company ? strtoupper($company->company_code) : 'COMP';
        $typeCode = strtoupper(substr(str_replace(' ', '', $slipType), 0, 4));

        $count = SiteVehicleTrip::where('company_id', $companyId)
            ->where('slip_type', $slipType)
            ->whereDate('trip_date', $date)
            ->count();

        $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        return "{$compCode}/{$typeCode}/{$sequence}";
    }

    public function store(Request $request)
    {
        $context = $this->getGlobalContext();

        $request->validate([
            'slip_type' => 'required|string',
            'entry_date' => 'required|date',
            'trips' => 'required|array'
        ]);

        DB::beginTransaction();
        try {
            $companyId = $context->company_id;
            $mediaConverter = new MediaConverterService();
            $savedTrips = [];

            $user = auth()->user();
            $date = $request->entry_date;

            // 🔥 PERFECT ALLOCATION LOGIC (No designation_name needed)
            $allocation = SiteAllocation::where('employee_id', $user->id)
                ->where('status', 'active')
                ->where(function ($q) use ($date) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', $date);
                })
                ->where(function ($q) use ($date) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
                })
                ->first();

            $supervisor_id = null;
            $pm_id = null;

            // Check roles and assign Integer IDs to DB
            if ($allocation && is_array($allocation->incharge_types)) {
                if (in_array('Site Supervisor', $allocation->incharge_types)) {
                    $supervisor_id = $user->id;
                }
                if (in_array('Site Project Manager', $allocation->incharge_types) || in_array('Site Incharge', $allocation->incharge_types)) {
                    $pm_id = $user->id;
                }
            }

            foreach ($request->trips as $index => $trip) {
                $slipNo = $this->generateSlipNumber($companyId, $request->slip_type, $request->entry_date);

                $arrImg = null;
                $depImg = null;

                if ($request->hasFile("trips.{$index}.arrival_image")) {
                    $media = $mediaConverter->uploadAndConvert($request->file("trips.{$index}.arrival_image"));
                    $arrImg = $media ? $media->file_path : null;
                }
                if ($request->hasFile("trips.{$index}.departure_image")) {
                    $media = $mediaConverter->uploadAndConvert($request->file("trips.{$index}.departure_image"));
                    $depImg = $media ? $media->file_path : null;
                }

                $savedTrips[] = SiteVehicleTrip::create([
                    'company_id' => $companyId,
                    'phase_name' => $request->phase_name ?? null,
                    'slip_type' => strtoupper($request->slip_type),
                    'slip_number' => $slipNo,
                    'trip_date' => $request->entry_date,
                    'vehicle_number' => strtoupper($trip['vehicle_number']),
                    'arrival_time' => $trip['arrival_time'] ?? null,
                    'departure_time' => $trip['departure_time'] ?? null,
                    'arrival_image' => $arrImg,
                    'departure_image' => $depImg,
                    'project_manager_id' => $pm_id,
                    'site_supervisor_id' => $supervisor_id
                ]);
            }

            DB::commit();
            $ids = collect($savedTrips)->pluck('id')->join(',');
            return response()->json(['status' => 'success', 'message' => 'Trips Saved Successfully!', 'print_ids' => $ids]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function printPreview(Request $request)
    {
        $ids = explode(',', $request->query('ids'));
        $trips = SiteVehicleTrip::with(['company'])->whereIn('id', $ids)->get();

        if ($trips->isEmpty()) abort(404, 'Trips not found');

        return view('admin.vehicle_trips.print', compact('trips'));
    }

    public function generateBlankSlips(Request $request)
    {
        $companyId = $request->query('company_id');
        $phase = $request->query('phase_name');
        $slipType = $request->query('slip_type');
        $date = $request->query('trip_date');
        $numTrips = (int) $request->query('num_trips', 1);

        // Form se aayi hui ID le rahe hain taaki 'null' crash na ho
        $userId = $request->query('user_id');
        $user = auth()->user();
        $actualUserId = $user ? $user->id : $userId;

        $company = Company::find($companyId);

        $supervisorIdStr = '';
        $pmIdStr = '';
        
        // 🔥 PERFECT ALLOCATION LOGIC FOR BLANK SLIPS
        if ($actualUserId) {
            $employeeRecord = Employee::find($actualUserId);
            $memberIdToPrint = $employeeRecord ? ($employeeRecord->member_id ?? $employeeRecord->id) : '';

            $allocation = SiteAllocation::where('employee_id', $actualUserId)
                ->where('status', 'active')
                ->where(function ($q) use ($date) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', $date);
                })
                ->where(function ($q) use ($date) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
                })
                ->first();

            if ($allocation && is_array($allocation->incharge_types)) {
                if (in_array('Site Supervisor', $allocation->incharge_types)) {
                    $supervisorIdStr = $memberIdToPrint; // ID ki jagah Member ID Print hogi
                }
                if (in_array('Site Project Manager', $allocation->incharge_types) || in_array('Site Incharge', $allocation->incharge_types)) {
                    $pmIdStr = $memberIdToPrint; // ID ki jagah Member ID Print hogi
                }
            }
        }

        $existingCount = SiteVehicleTrip::where('company_id', $companyId)
            ->where('slip_type', $slipType)
            ->whereDate('trip_date', $date)
            ->count();

        $compCode = $company ? strtoupper($company->company_code) : 'COMP';
        $typeCode = strtoupper(substr(str_replace(' ', '', $slipType), 0, 4));

        $slips = [];
        for ($i = 1; $i <= $numTrips; $i++) {
            $seq = str_pad($existingCount + $i, 3, '0', STR_PAD_LEFT);
            $slips[] = [
                'slip_number' => "{$compCode}/{$typeCode}/{$seq}",
                'company' => $company,
                'phase' => $phase,
                'slip_type' => $slipType,
                'date' => $date,
                'supervisor_id' => $supervisorIdStr,
                'pm_id' => $pmIdStr
            ];
        }

        // 🔥 YAHAN SE NAYA CODE ADD KAREIN 🔥
        // Debugging ke liye saari values capture kar rahe hain
        $debug_data = [
            '1_Form_User_ID' => $userId ?? 'NULL',
            '2_Auth_User_ID' => $user ? $user->id : 'NULL (Session Drop)',
            '3_Actual_User_ID_Used' => $actualUserId ?? 'NULL',
            '4_Employee_Member_ID' => isset($memberIdToPrint) ? $memberIdToPrint : 'NOT FOUND',
            '5_Allocation_Found' => isset($allocation) && $allocation ? 'YES' : 'NO',
            '6_Allocation_Roles' => isset($allocation) ? $allocation->incharge_types : 'N/A',
            '7_Is_Roles_Array' => isset($allocation) ? (is_array($allocation->incharge_types) ? 'YES' : 'NO (String hai)') : 'N/A',
            '8_Final_Supervisor_ID' => $supervisorIdStr,
            '9_Final_PM_ID' => $pmIdStr,
        ];

        // Debug data ko view me pass kar diya
        return view('admin.vehicle_trips.print_blank', compact('slips', 'debug_data'));
    }
    
}
