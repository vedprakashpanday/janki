<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Salary;
use App\Models\FinePenalty;
use App\Models\TravelAllowance;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRepayment;

class SalaryApiController extends Controller
{
    // ==============================================================
    // 1. CALCULATE DATA FOR WIZARD (ADVANCED 30-DAY FIXED & PRORATED)
    // ==============================================================
    public function calculateData(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:adm_regist,id',
            'month'       => 'required|date_format:Y-m'
        ]);

        $employee = Employee::find($request->employee_id);
        $monthStr = $request->month;

        $startDate = Carbon::parse($monthStr . '-01')->startOfMonth();
        $endDate = Carbon::parse($monthStr . '-01')->endOfMonth();
        $todayStr = Carbon::today()->format('Y-m-d');
        $daysInMonth = $startDate->daysInMonth;

        $latestSalary = $employee->current_salary ?? 0;

        $timeWindows = \App\Models\AttendanceTimeWindow::where('status', 'active')->get();
        $empWindow = $timeWindows->where('company_id', $employee->company_id)->where('branch_id', $employee->branch_id)->first() ?? $timeWindows->where('company_id', $employee->company_id)->whereNull('branch_id')->first();

        $attendances = \App\Models\Attendance::where('user_id', $employee->member_id)->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->get()->keyBy('date');
        $corrections = \App\Models\AttendanceCorrection::where('user_id', $employee->id)->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->get()->keyBy('date');
        $serviceRecords = \App\Models\ServiceRecord::where('user_id', $employee->id)->orderBy('joining_date', 'asc')->get();

        $holidaysList = \App\Models\Holiday::where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('start_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->orWhere(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $endDate->format('Y-m-d'))->where(function ($subQ) use ($startDate) {
                        $subQ->whereNull('end_date')->orWhere('end_date', '>=', $startDate->format('Y-m-d'));
                    });
                });
        })->get();

        $holidays = [];
        foreach ($holidaysList as $holiday) {
            $period = \Carbon\CarbonPeriod::create($holiday->start_date, $holiday->end_date ?? $holiday->start_date);
            foreach ($period as $dt) {
                $holidays[] = $dt->format('Y-m-d');
            }
        }

        $empLeaves = \App\Models\LeaveApplication::where('user_id', $employee->id)
            ->where('status', 'approved')->whereNotNull('approved_start_datetime')
            ->where('approved_start_datetime', '<=', $endDate->format('Y-m-d 23:59:59'))
            ->where('approved_end_datetime', '>=', $startDate->format('Y-m-d 00:00:00'))->get();

        $dates = [];
        for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
            $dates[] = $d->copy();
        }

        $rawRecords = [];
        $dailySalaries = [];
        $lateCount = 0;
        $totalMonthlyBase = 0;
        $joinDate = $employee->created_at ? Carbon::parse($employee->created_at)->startOfDay() : Carbon::parse('2000-01-01');

        // ==========================================
        // LOOP 1: Determine Daily Status & Active Salary
        // ==========================================
        foreach ($dates as $dateObj) {
            $dStr = $dateObj->format('Y-m-d');
            $isTuesday = $dateObj->isTuesday();
            $isHoliday = in_array($dStr, $holidays);
            $isFuture = $dStr > $todayStr;

            // 1. Fetch exact active salary for THIS specific date from Service Record
            $dailySalary = $latestSalary;
            if ($serviceRecords->count() > 0) {
                $validRecord = $serviceRecords->filter(function ($record) use ($dStr) {
                    $join = Carbon::parse($record->joining_date)->format('Y-m-d');
                    $leave = $record->date_of_leaving ? Carbon::parse($record->date_of_leaving)->format('Y-m-d') : '9999-12-31';
                    return $dStr >= $join && $dStr <= $leave;
                })->last();
                if ($validRecord) {
                    $dailySalary = $validRecord->current_salary ?? $latestSalary;
                }
            }
            $dailySalaries[$dStr] = $dailySalary;
            $totalMonthlyBase += $dailySalary; // Accumulating exact base value for the month

            $status = 'A';
            $onLeave = false;
            $leaveType = 'L';
            $isPaidLeave = false;

            foreach ($empLeaves as $leave) {
                $lStart = Carbon::parse($leave->approved_start_datetime)->format('Y-m-d');
                $lEnd = Carbon::parse($leave->approved_end_datetime)->format('Y-m-d');
                if ($dStr >= $lStart && $dStr <= $lEnd) {
                    $onLeave = true;
                    $isPaidLeave = $leave->is_paid_leave == 1;
                    if ($leave->application_type === 'Short Leave') $leaveType = 'SL';
                    break;
                }
            }

            if ($dateObj->lt($joinDate)) {
                $status = 'N/A';
            } elseif ($corrections->has($dStr)) {
                $corr = $corrections->get($dStr);
                $status = $corr->corrected_status;
            } elseif ($attendances->has($dStr)) {
                $att = $attendances->get($dStr);
                if ($isTuesday || $isHoliday) {
                    $status = 'ED';
                } elseif (!empty($att->login_time) && empty($att->logout_time)) {
                    if ($dStr === $todayStr) {
                        $isLateToday = false;
                        if ($empWindow) {
                            $lateLimit = !empty($empWindow->late_time) ? Carbon::parse($dStr . ' ' . $empWindow->late_time) : Carbon::parse($dStr . ' ' . $empWindow->login_start)->addMinutes(60);
                            if (Carbon::parse($dStr . ' ' . $att->login_time)->gt($lateLimit)) {
                                $isLateToday = true;
                            }
                        } elseif ($att->is_late_punch) {
                            $isLateToday = true;
                        }
                        if ($onLeave && $leaveType === 'SL') {
                            $status = 'SL';
                        } elseif ($isLateToday) {
                            $status = 'LT';
                        } else {
                            $status = 'P';
                        }
                    } else {
                        $status = 'HD';
                    }
                } elseif (empty($att->login_time) && !empty($att->logout_time)) {
                    $status = 'HD';
                } elseif (empty($att->login_time) && empty($att->logout_time)) {
                    $status = 'A';
                } else {
                    $isLateToday = false;
                    if ($empWindow) {
                        $lateLimit = !empty($empWindow->late_time) ? Carbon::parse($dStr . ' ' . $empWindow->late_time) : Carbon::parse($dStr . ' ' . $empWindow->login_start)->addMinutes(60);
                        if (Carbon::parse($dStr . ' ' . $att->login_time)->gt($lateLimit)) {
                            $isLateToday = true;
                        }
                    } elseif ($att->is_late_punch) {
                        $isLateToday = true;
                    }
                    $minHoursRaw = $empWindow ? $empWindow->min_working_hours : 8.25;
                    $minHours = (strpos((string)$minHoursRaw, ':') !== false) ? (int)explode(':', $minHoursRaw)[0] + ((int)explode(':', $minHoursRaw)[1] / 60) : (float)$minHoursRaw;
                    $in = Carbon::parse($dStr . ' ' . $att->login_time);
                    $out = Carbon::parse($dStr . ' ' . $att->logout_time);
                    $diffSeconds = $out->timestamp - $in->timestamp;
                    if ($diffSeconds < 0) {
                        $out->addDay();
                        $diffSeconds = $out->timestamp - $in->timestamp;
                    }
                    $workedHours = $diffSeconds / 3600;
                    $isShortHours = ($workedHours < $minHours);
                    if ($onLeave && $leaveType === 'SL') $isShortHours = false;
                    if ($isShortHours) {
                        $status = 'HD';
                    } elseif ($onLeave && $leaveType === 'SL') {
                        $status = 'SL';
                    } elseif ($isLateToday) {
                        $status = 'LT';
                    } else {
                        $status = 'P';
                    }
                }
            } elseif ($onLeave) {
                if ($leaveType === 'SL' && !$isFuture && $dStr < $todayStr) {
                    $status = 'A';
                } else {
                    if ($leaveType === 'SL') {
                        $status = 'SL';
                    } else {
                        $status = $isPaidLeave ? 'PL' : 'L';
                    }
                }
            }

            // 🔥 FIX: Future dates me koi free ka WO ya HO nahi milega
            elseif ($isFuture) {
                $status = 'N/A';
            } else {
                if ($isTuesday) $status = 'WO';
                elseif ($isHoliday) $status = 'HO';
                else $status = 'A';
            }

            // Late Penalty Rule (3 Lates = 1 Absent)
            if ($status === 'LT') {
                $lateCount++;
                if ($lateCount % 3 == 0) {
                    $status = 'A';
                }
            }

            $rawRecords[$dStr] = $status;
        }

        // ==========================================
        // LOOP 2: Apply Rules & Calculate Exact Deductions & Additions
        // ==========================================
        $stats = ['P' => 0, 'A' => 0, 'HD' => 0, 'L' => 0, 'PL' => 0, 'CL' => 0, 'SL' => 0, 'ED' => 0, 'LT' => 0, 'WO' => 0, 'HO' => 0, 'N/A' => 0];

        $cl_available = 1; // Mahine ki 1 paid CL

        $calculatedRegularSalary = 0;
        $totalDeductionAmount = 0;
        $edSalary = 0;

        for ($i = 0; $i < count($dates); $i++) {
            $dStr = $dates[$i]->format('Y-m-d');
            $currStatus = $rawRecords[$dStr];
            $perDayRate = $dailySalaries[$dStr] / 30; // Formula uses strictly 30 days

            // Sandwich Check (Agar Auth Controller se miss ho gaya ho)
            if ($currStatus === 'WO' || $currStatus === 'HO') {
                $prevStatus = ($i > 0) ? $rawRecords[$dates[$i - 1]->format('Y-m-d')] : 'P';
                $nextStatus = ($i < count($dates) - 1) ? $rawRecords[$dates[$i + 1]->format('Y-m-d')] : 'P';
                if (in_array($prevStatus, ['A', 'L', 'SL']) && in_array($nextStatus, ['A', 'L', 'SL'])) {
                    $currStatus = 'A'; // Sandwich Lag Gaya
                }
            }

            // 🔥 SHIVAM FIX: CL sirf tab milegi jab 'L' (Approved Leave) ho. Absent (A) par nahi.
            if ($currStatus === 'L' && $cl_available > 0) {
                $currStatus = 'CL';
                $cl_available--;
            }

            // Stats update
            if (isset($stats[$currStatus])) {
                $stats[$currStatus]++;
            }

           // 🔥 Monetary Trackers
            if (in_array($currStatus, ['P', 'LT', 'CL', 'PL', 'WO', 'HO'])) { // Yahan 'LT' add kiya
                $calculatedRegularSalary += $perDayRate;
            } elseif (in_array($currStatus, ['HD', 'SL'])) {
                $calculatedRegularSalary += ($perDayRate * 0.5);
                $totalDeductionAmount += ($perDayRate * 0.5);
            } elseif (in_array($currStatus, ['A', 'L', 'N/A'])) {
                $totalDeductionAmount += $perDayRate;
            } elseif ($currStatus === 'ED') {
                $edSalary += $perDayRate; // ED Track
            }
        }

        $averageMonthlySalary = $totalMonthlyBase / $daysInMonth; // Blended base

        // ----------------------------------------------------
        // 🔥 VED FIX: HYBRID 30-DAY PAYROLL MATH
        // ----------------------------------------------------
        //
      // 1. Days Calculation
        $calculatedRegularDays = $stats['P'] + $stats['LT'] + $stats['CL'] + $stats['PL'] + $stats['WO'] + $stats['HO'] + ($stats['HD'] * 0.5) + ($stats['SL'] * 0.5); // Yahan $stats['LT'] add kiya
        $maxRegularDays = 30 - ($stats['A'] + $stats['L'] + $stats['N/A'] + ($stats['HD'] * 0.5) + ($stats['SL'] * 0.5));

        // System dono compare karega, aur sahi/minimum din uthayega (31-day bug fixed)
        $finalRegularDays = min($calculatedRegularDays, max(0, $maxRegularDays));
        $payableDays = $finalRegularDays + $stats['ED']; // ED finally add hogi

        // 2. Exact Monetary Calculation (Salary Amount)
        $maxRegularSalary = $averageMonthlySalary - $totalDeductionAmount;
        $finalRegularSalary = min($calculatedRegularSalary, max(0, $maxRegularSalary));

        $actualSalary = $finalRegularSalary + $edSalary; // Final amount with ED
        if ($actualSalary < 0) $actualSalary = 0;

        // Fines...
        $fines = FinePenalty::where('employee_id', $employee->id)->where('status', 'Approved')->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->get();
        $totalFineAmount = 0;
        foreach ($fines as $fine) {
            $totalFineAmount += ($fine->fine_rupees ?? 0) + ($fine->penalty_rupees ?? 0) + (($fine->fine_days ?? 0) * ($averageMonthlySalary / 30)) + (($fine->penalty_days ?? 0) * ($averageMonthlySalary / 30));
        }

        // TA & Loans...
        $totalTA = TravelAllowance::where('employee_id', $employee->id)->where('status', 'active')->whereBetween('ta_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->sum('approved_amount');
        $activeLoan = EmployeeLoan::where('employee_id', $employee->id)->where('status', 'active')->orderBy('id', 'asc')->first();
        $remainingLoan = $activeLoan ? $activeLoan->remaining_amount : 0;

        $defaultLoanDeduction = 0;
        if ($remainingLoan > 0) {
            $baseForLoan = $actualSalary - $totalFineAmount;
            if ($baseForLoan > 0) {
                $defaultLoanDeduction = min($baseForLoan * 0.30, $remainingLoan);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'base_salary'      => round($averageMonthlySalary, 2),
                'per_day_salary'   => round($averageMonthlySalary / 30, 2),
                'latest_per_day'   => round($latestSalary / 30, 2),
                'attendance'       => [
                    'present'     => $stats['P'],
                    'absent' => $stats['A'],
                    'half_day' => $stats['HD'],
                    'late_punch' => $stats['LT'],
                    'leaves'      => $stats['L'],
                    'cl' => $stats['CL'],
                    'paid_leaves' => $stats['PL'],
                    'short_leaves' => $stats['SL'],
                    'week_offs'   => $stats['WO'],
                    'holidays' => $stats['HO'],
                    'extra_days' => $stats['ED'],
                    'payable_days' => $payableDays
                ],
                'actual_salary'    => round($actualSalary, 2),
                'total_fine' => round($totalFineAmount, 2),
                'total_ta' => round($totalTA, 2),
                'active_loan'      => $activeLoan ? ['id' => $activeLoan->id, 'remaining_amount' => round($remainingLoan, 2), 'default_cut' => round($defaultLoanDeduction, 2)] : null
            ]
        ]);
    }

    // ==============================================================
    // 2. STORE FINALIZED SALARY
    // ==============================================================
    public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();

        // 1. DYNAMIC PERMISSION & GOD MODE CHECK
        $isGodMode = false;
        if ($context && isset($context->is_god) && $context->is_god) {
            $isGodMode = true;
        }

        // Explicit Developer Email Check Fallback
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if ($user && in_array(strtolower($user->email ?? ''), $developerEmails)) {
            $isGodMode = true;
        }

        $userPerms = [];
        if ($user && method_exists($user, 'getAllPermissions')) {
            try {
                $userPerms = $user->getAllPermissions()->pluck('name')->toArray();
            } catch (\Exception $e) {
            }
        } elseif ($context && isset($context->permissions) && is_array($context->permissions)) {
            $userPerms = $context->permissions;
        }

        // Checking exact slugs
        $hasDirect = $isGodMode || in_array('emp_salary_add_direct', $userPerms) || in_array('emp_salary_add', $userPerms);
        $hasRequest = in_array('emp_salary_add_request', $userPerms);

        if (!$hasDirect && !$hasRequest) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized! You cannot generate salary. Required permissions missing.',
                'debug_info' => $user ? 'Logged In as: ' . $user->email : 'Not Logged In (Token Missing)'
            ], 403);
        }

        $request->validate([
            'employee_id'            => 'required|exists:adm_regist,id',
            'salary_month'           => 'required|date_format:Y-m',
            'base_salary'            => 'required|numeric',
            'actual_salary'          => 'required|numeric',
            'fine_deduction'         => 'required|numeric',
            'loan_deduction'         => 'required|numeric',
            'travel_allowance_added' => 'required|numeric',
            'net_payable_salary'     => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::find($request->employee_id);

            // God mode ya direct permission hone par 'active', warna 'pending'
            $salaryStatus = ($isGodMode || $hasDirect) ? 'active' : 'pending';

            $exists = Salary::where('employee_id', $employee->id)
                ->where('salary_month', $request->salary_month)
                ->first();

            if ($exists) {
                return response()->json(['status' => 'error', 'message' => 'Salary for this month already exists!'], 400);
            }

            $salary = Salary::create([
                'company_id'             => $employee->company_id,
                'branch_id'              => $employee->branch_id,
                'department_id'          => $employee->department_id,
                'designation_id'         => $employee->designation_id,
                'employee_id'            => $employee->id,
                'salary_month'           => $request->salary_month,
                'base_salary'            => $request->base_salary,
                'per_day_salary'         => $request->per_day_salary ?? ($request->base_salary / 30),

                // Safely accepting attendance days
                'present_days'           => $request->present_days ?? 0,
                'absent_days'            => $request->absent_days ?? 0,
                'half_days'              => $request->half_days ?? 0,
                'paid_leaves'            => $request->paid_leaves ?? 0,
                'short_leaves'           => $request->short_leaves ?? 0,
                'week_offs'              => $request->week_offs ?? 0,
                'holidays'               => $request->holidays ?? 0,
                'extra_days'             => $request->extra_days ?? 0,

                'total_payable_days'     => $request->total_payable_days ?? 0,
                'actual_salary'          => $request->actual_salary,
                'travel_allowance_added' => $request->travel_allowance_added,
                'fine_deduction'         => $request->fine_deduction,
                'loan_deduction'         => $request->loan_deduction,
                'net_payable_salary'     => $request->net_payable_salary,
                'status'                 => $salaryStatus,
                'created_by'             => $user ? $user->id : null,
                'reward_days'            => $request->reward_days ?? 0,
                'remarks'                => $request->remarks,
                'approved_by'            => ($isGodMode || $hasDirect) && $user ? $user->id : null,
            ]);

            // LOAN DEDUCTION & REPAYMENT LEDGER LOGIC
            if ($request->loan_deduction > 0 && $request->active_loan_id) {
                $loan = EmployeeLoan::find($request->active_loan_id);
                if ($loan && $loan->status === 'active') {
                    EmployeeLoanRepayment::create([
                        'employee_loan_id' => $loan->id,
                        'salary_id'        => $salary->id,
                        'salary_month'     => $salary->salary_month,
                        'deduction_date'   => now()->format('Y-m-d'),
                        'amount_deducted'  => $request->loan_deduction,
                    ]);

                    $loan->paid_amount += $request->loan_deduction;
                    $loan->remaining_amount -= $request->loan_deduction;

                    if ($loan->remaining_amount <= 0) {
                        $loan->status = 'settled';
                    }
                    $loan->save();
                }
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Salary Finalized and Saved Successfully!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ==============================================================
    // 3. FETCH DATA FOR DATATABLE
    // ==============================================================
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        $query = Salary::with(['employee', 'department', 'designation']);

        // Scoping: Kaun kya dekh sakta hai
        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if ($context->is_employee && $context->branch_id) {
                $query->where('branch_id', $context->branch_id);
            }
        }

        // Filters
        if ($request->filled('month')) {
            $query->where('salary_month', $request->month);
        }
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        if ($request->filled('department_id')) $query->where('department_id', $request->department_id);
        if ($request->filled('employee_id')) $query->where('employee_id', $request->employee_id);

        $totalFiltered = $query->count();
        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        $salaries = $query->orderBy('id', 'desc')->get();

        $data = $salaries->map(function ($s) {
            return [
                'id'          => $s->id,
                'emp_code'    => $s->employee->member_id ?? '-',
                'name'        => $s->employee->full_name ?? '-',
                'department'  => $s->department->department_name ?? '-',
                'designation' => $s->designation->designation_name ?? '-',
                'month'       => Carbon::parse($s->salary_month)->format('M Y'),
                'present'     => $s->present_days,
                'absent'      => $s->absent_days,
                'leaves'      => $s->paid_leaves + $s->short_leaves + $s->week_offs,
                'actual'      => $s->actual_salary,
                'fine'        => $s->fine_deduction,
                'loan'        => $s->loan_deduction,
                'ta'          => $s->travel_allowance_added,
                'payable'     => $s->net_payable_salary,
                'status'      => $s->status,
            ];
        });

        return response()->json([
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => Salary::count(),
            "recordsFiltered" => $totalFiltered,
            "data"            => $data
        ]);
    }

    // ==============================================================
    // 4. PRINT REGISTER / EXPORT LOGIC
    // ==============================================================
    public function printRegister(Request $request)
    {
        // Token Verification for new tab (window.open)
        if (!auth()->check() && $request->has('token')) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($accessToken) {
                auth()->login($accessToken->tokenable);
            }
        }

        $context = $this->getGlobalContext();
        $user = auth()->user();

        if (!$user) {
            abort(401, 'Unauthorized Access! Token missing or invalid.');
        }

        // Permission Check
        $userPerms = [];
        if (method_exists($user, 'getAllPermissions')) {
            try {
                $userPerms = $user->getAllPermissions()->pluck('name')->toArray();
            } catch (\Exception $e) {
            }
        } elseif ($context && isset($context->permissions)) {
            $userPerms = $context->permissions;
        }

        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isGodMode = ($context && $context->is_god) || in_array(strtolower($user->email ?? ''), $developerEmails);

        if (!$isGodMode && !in_array('emp_salary_print', $userPerms) && !in_array('emp_salary_export', $userPerms)) {
            abort(403, 'Unauthorized action.');
        }

        // Fetching Data
        $query = Salary::with(['employee', 'department', 'designation']);

        if (!$isGodMode) {
            $query->where('company_id', $context->company_id);
            if ($context->is_employee && $context->branch_id) {
                $query->where('branch_id', $context->branch_id);
            }
        }

        if ($request->filled('month')) $query->where('salary_month', $request->month);
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);

        $salaries = $query->orderBy('id', 'desc')->get();

        // 🔥 EXCEL EXPORT LOGIC 🔥
        if ($request->export === 'excel') {
            $monthName = $request->filled('month') ? date('F_Y', strtotime($request->month . '-01')) : 'All_Months';
            $fileName = "Salary_Register_" . $monthName . ".csv";

            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=$fileName",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $columns = ['S.N.', 'Employee Code', 'Employee Name', 'Designation', 'Present', 'Absent', 'Leaves (Paid+WO)', 'Base Salary', 'Actual Salary', 'Fine Deduction', 'Loan Deduction', 'T.A. Added', 'Net Payable'];

            $callback = function () use ($salaries, $columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);

                foreach ($salaries as $key => $sal) {
                    fputcsv($file, [
                        $key + 1,
                        $sal->employee->member_id ?? '-',
                        $sal->employee->full_name ?? '-',
                        $sal->designation->designation_name ?? '-',
                        $sal->present_days,
                        $sal->absent_days,
                        $sal->paid_leaves + $sal->short_leaves + $sal->week_offs,
                        $sal->base_salary,
                        $sal->actual_salary,
                        $sal->fine_deduction,
                        $sal->loan_deduction,
                        $sal->travel_allowance_added,
                        $sal->net_payable_salary
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        // NORMAL PRINT LOGIC
        $companyId = $request->filled('company_id') ? $request->company_id : ($context->company_id ?? 1);
        $branchId = $request->filled('branch_id') ? $request->branch_id : ($context->branch_id ?? null);
        $company = \App\Models\Company::find($companyId);
        $branch = $branchId ? \App\Models\Branch::find($branchId) : null;

        $preparedBy = ($user->full_name ?? $user->name) . " (" . ($user->member_id ?? $user->id) . ")";
        $checkedBy = ($isGodMode || strtolower($user->email ?? '') === 'admin@jankivilla.com') ? "HR Management" : "Accountant";

        $ceo = DB::table('super_admins')->where('status', 'active')->first();
        $authorizedSignatory = $ceo ? $ceo->full_name . " (" . $ceo->ceo_id . ")" : "Authorized Signatory";

        return view('admin.salaries.print', compact('salaries', 'company', 'branch', 'preparedBy', 'checkedBy', 'authorizedSignatory', 'request'));
    }

    // ==========================================
    // 5. DELETE & BULK DELETE ACTIONS (NEW)
    // ==========================================
    public function destroy($id)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();

        $isGodMode = ($context && $context->is_god) || ($user && in_array(strtolower($user->email ?? ''), ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in']));
        $userPerms = [];
        if ($user && method_exists($user, 'getAllPermissions')) {
            try {
                $userPerms = $user->getAllPermissions()->pluck('name')->toArray();
            } catch (\Exception $e) {
            }
        }

        if (!$isGodMode && !in_array('emp_salary_delete', $userPerms)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized to delete salary!'], 403);
        }

        $salary = Salary::findOrFail($id);

        // LOAN REVERSION LOGIC: Agar salary delete hui, toh kata hua loan wapas kar do
        if ($salary->loan_deduction > 0) {
            $repayment = EmployeeLoanRepayment::where('salary_id', $salary->id)->first();
            if ($repayment) {
                $loan = EmployeeLoan::find($repayment->employee_loan_id);
                if ($loan) {
                    $loan->paid_amount -= $repayment->amount_deducted;
                    $loan->remaining_amount += $repayment->amount_deducted;
                    $loan->status = 'active'; // Remaining badh gaya to active kardo
                    $loan->save();
                }
            }
        }

        $salary->delete();
        return response()->json(['status' => 'success', 'message' => 'Salary deleted and loan deductions reverted!']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        foreach ($request->ids as $id) {
            $this->destroy($id); // Re-use destroy logic for loan reversion
        }
        return response()->json(['status' => 'success', 'message' => 'Selected salaries deleted successfully!']);
    }
}
