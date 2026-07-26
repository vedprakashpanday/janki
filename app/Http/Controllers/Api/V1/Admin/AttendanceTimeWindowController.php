<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceTimeWindow;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceTimeWindowController extends Controller
{
    /**
     * Fetch hierarchical dropdown data based on logged-in user's role
     */
    public function getDropdownData(Request $request)
    {
        $context = $this->getGlobalContext();
        $companies = collect();

        if ($context->is_god) {
            $companies = Company::where('status', 'active')->with(['branches' => function ($q) {
                $q->where('branch_status', 'active');
            }])->get();
        }
        elseif ($context->is_director) {
            $directorMapping = DB::table('company_director')->where('director_id', $context->profile_id)->first();
            $lockedCompanyId = $directorMapping ? $directorMapping->company_id : $context->company_id;

            $companies = Company::where('id', $lockedCompanyId)->where('status', 'active')->with(['branches' => function ($q) {
                $q->where('branch_status', 'active');
            }])->get();
        }
        elseif ($context->is_employee) {
            $companies = Company::where('id', $context->company_id)->where('status', 'active')->with(['branches' => function ($q) use ($context) {
                if ($context->branch_id) {
                    $q->where('id', $context->branch_id)->where('branch_status', 'active');
                } else {
                    $q->whereRaw('1 = 0');
                }
            }])->get();
        }

        $dropdownList = [];
        foreach ($companies as $comp) {
            if ($context->is_god || $context->is_director || empty($context->branch_id)) {
                $dropdownList[] = [
                    'id' => 'HO_' . $comp->id,
                    'text' => $comp->company_name . ' (Head Office)'
                ];
            }

            foreach ($comp->branches as $branch) {
                $dropdownList[] = [
                    'id' => 'BR_' . $branch->id,
                    'text' => $comp->company_name . ' - ' . $branch->branch_name
                ];
            }
        }

        return response()->json(['status' => 'success', 'data' => $dropdownList]);
    }

    /**
     * Store Time Window Rules
     */
    public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();

        $hasDirect = false;
        $hasRequest = false;

        if ($context->is_god || $context->is_director) {
            $hasDirect = true;
        } else {
            $permissions = self::getLiveActivePermissions($user);
            $hasDirect = in_array('atten_wind_add_direct', $permissions);
            $hasRequest = in_array('atten_wind_add_request', $permissions);

            if (!$hasDirect && !$hasRequest) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized! You do not have permission to manage time windows.'], 403);
            }
        }

        $request->validate([
            'selections' => 'required|array',
            'login_start' => 'required',
            'login_end' => 'required',
            'late_time' => 'required', // 🔥 NAYA: Late Threshold validation
            'logout_start' => 'required',
            'logout_end' => 'required',
            'min_working_hours' => 'required|numeric'
        ]);

        $status = $hasDirect ? 'active' : 'pending';

        DB::beginTransaction();
        try {
            foreach ($request->selections as $selection) {
                $companyId = null;
                $branchId = null;

                if (str_starts_with($selection, 'HO_')) {
                    $companyId = str_replace('HO_', '', $selection);
                } elseif (str_starts_with($selection, 'BR_')) {
                    $branchId = str_replace('BR_', '', $selection);
                    $branch = Branch::find($branchId);
                    if ($branch) $companyId = $branch->company_id;
                }

                if ($companyId) {
                    AttendanceTimeWindow::updateOrCreate(
                        [
                            'company_id' => $companyId,
                            'branch_id' => $branchId
                        ],
                        [
                            'login_start' => $request->login_start,
                            'login_end' => $request->login_end,
                            'late_time' => $request->late_time, // 🔥 NAYA: Insert Late Time
                            'logout_start' => $request->logout_start,
                            'logout_end' => $request->logout_end,
                            'min_working_hours' => $request->min_working_hours,
                            'status' => $status,
                            'action_by' => $user->id,
                        ]
                    );
                }
            }
            DB::commit();
            $msg = $status === 'active' ? 'Time Windows Updated Successfully!' : 'Time Windows Requested For Approval.';
            return response()->json(['status' => 'success', 'message' => $msg]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get list of all configured time windows
     */
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = AttendanceTimeWindow::with(['company', 'branch']);

        if ($context->is_god) {
            // God sees all rules
        } elseif ($context->is_director) {
            $directorMapping = DB::table('company_director')->where('director_id', $context->profile_id)->first();
            $lockedCompanyId = $directorMapping ? $directorMapping->company_id : $context->company_id;
            $query->where('company_id', $lockedCompanyId);
        } elseif ($context->is_employee) {
            $query->where('company_id', $context->company_id);
            if ($context->branch_id) {
                $query->where('branch_id', $context->branch_id);
            } else {
                $query->whereNull('branch_id');
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderBy('id', 'desc')->get()
        ]);
    }

    /**
     * Update an existing rule
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'login_start' => 'required',
            'login_end' => 'required',
            'late_time' => 'required', // 🔥 NAYA
            'logout_start' => 'required',
            'logout_end' => 'required',
            'min_working_hours' => 'required|numeric'
        ]);

        $window = AttendanceTimeWindow::findOrFail($id);
        
        $window->update([
            'login_start' => $request->login_start,
            'login_end' => $request->login_end,
            'late_time' => $request->late_time, // 🔥 NAYA
            'logout_start' => $request->logout_start,
            'logout_end' => $request->logout_end,
            'min_working_hours' => $request->min_working_hours,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Time Window Updated Successfully!']);
    }

    public function destroy($id)
    {
        $window = AttendanceTimeWindow::findOrFail($id);
        $window->delete();
        return response()->json(['status' => 'success', 'message' => 'Time Window Rule Deleted!']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:attendance_time_windows,id'
        ]);

        AttendanceTimeWindow::whereIn('id', $request->ids)->delete();

        return response()->json([
            'status' => 'success', 
            'message' => count($request->ids) . ' Time Windows deleted successfully!'
        ]);
    }
}