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
        // LOOP 1: Determine Daily Status
        // ==========================================
        foreach ($dates as $dateObj) {
            $dStr = $dateObj->format('Y-m-d');
            $isTuesday = $dateObj->isTuesday();
            $isHoliday = in_array($dStr, $holidays);
            $isFuture = $dStr > $todayStr;

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
            $totalMonthlyBase += $dailySalary; 

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
            } elseif ($isFuture) {
                $status = 'N/A';
            } else {
                if ($isTuesday) $status = 'WO';
                elseif ($isHoliday) $status = 'HO';
                else $status = 'A';
            }

            if ($status === 'LT') {
                $lateCount++;
                if ($lateCount % 3 == 0) {
                    $status = 'A';
                }
            }

            $rawRecords[$dStr] = $status;
        }

        // ==========================================
        // LOOP 2: Apply Rules (CL Fix logic included)
        // ==========================================
        $stats = ['P' => 0, 'A' => 0, 'HD' => 0, 'L' => 0, 'PL' => 0, 'CL' => 0, 'SL' => 0, 'ED' => 0, 'LT' => 0, 'WO' => 0, 'HO' => 0, 'N/A' => 0];

        $cl_available = 1; 
        $calculatedRegularSalary = 0;
        $totalDeductionAmount = 0;
        $edSalary = 0;

        for ($i = 0; $i < count($dates); $i++) {
            $dStr = $dates[$i]->format('Y-m-d');
            $currStatus = $rawRecords[$dStr];
            $perDayRate = $dailySalaries[$dStr] / 30; 

            if ($currStatus === 'WO' || $currStatus === 'HO') {
                $prevStatus = ($i > 0) ? $rawRecords[$dates[$i - 1]->format('Y-m-d')] : 'P';
                $nextStatus = ($i < count($dates) - 1) ? $rawRecords[$dates[$i + 1]->format('Y-m-d')] : 'P';
                if (in_array($prevStatus, ['A', 'L', 'SL']) && in_array($nextStatus, ['A', 'L', 'SL'])) {
                    $currStatus = 'A'; 
                }
            }

            // 🔥 FIX: Check both Leave ('L') and Absent ('A') for CL conversion
            if (in_array($currStatus, ['L', 'A']) && $cl_available > 0) {
                $currStatus = 'CL';
                $cl_available--;
            }

            if (isset($stats[$currStatus])) {
                $stats[$currStatus]++;
            }

            if (in_array($currStatus, ['P', 'LT', 'CL', 'PL', 'WO', 'HO'])) {
                $calculatedRegularSalary += $perDayRate;
            } elseif (in_array($currStatus, ['HD', 'SL'])) {
                $calculatedRegularSalary += ($perDayRate * 0.5);
                $totalDeductionAmount += ($perDayRate * 0.5);
            } elseif (in_array($currStatus, ['A', 'L', 'N/A'])) {
                $totalDeductionAmount += $perDayRate;
            } elseif ($currStatus === 'ED') {
                $edSalary += $perDayRate; 
            }
        }

        $averageMonthlySalary = $totalMonthlyBase / $daysInMonth; 

        // ----------------------------------------------------
        // 🔥 STRICT 30-DAY PAYROLL MATH
        // ----------------------------------------------------
        $totalDeductionDays = $stats['A'] + $stats['L'] + $stats['N/A'] + ($stats['HD'] * 0.5) + ($stats['SL'] * 0.5);
        $finalRegularDays = 30 - $totalDeductionDays;
        if ($finalRegularDays < 0) $finalRegularDays = 0; 
        $payableDays = $finalRegularDays + $stats['ED']; 

        $finalRegularSalary = $averageMonthlySalary - $totalDeductionAmount;
        if ($finalRegularSalary < 0) $finalRegularSalary = 0;

        $actualSalary = $finalRegularSalary + $edSalary; 
        if ($actualSalary < 0) $actualSalary = 0;

        // Fines
        $fines = FinePenalty::where('employee_id', $employee->id)->where('status', 'Approved')->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->get();
        $totalFineAmount = 0;
        foreach ($fines as $fine) {
            $totalFineAmount += ($fine->fine_rupees ?? 0) + ($fine->penalty_rupees ?? 0) + (($fine->fine_days ?? 0) * ($averageMonthlySalary / 30)) + (($fine->penalty_days ?? 0) * ($averageMonthlySalary / 30));
        }

        // TA & Loans
        $totalTA = TravelAllowance::where('employee_id', $employee->id)->where('status', 'active')->whereBetween('ta_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->sum('approved_amount');
        
        $activeLoan = EmployeeLoan::where('employee_id', $employee->member_id)->where('status', 'active')->orderBy('id', 'asc')->first();
        $remainingLoan = $activeLoan ? $activeLoan->remaining_amount : 0;

        $defaultLoanDeduction = 0;
        if ($remainingLoan > 0) {
            $baseForLoan = $actualSalary - $totalFineAmount;
            if ($baseForLoan > 0) {
                $defaultLoanDeduction = min($baseForLoan * 0.30, $remainingLoan);
            }
        }

        // Incentives
        $pendingIncentives = DB::table('incentives')
            ->where('emp_id', $employee->member_id)
            ->whereIn('incentive_status', ['pending', 'active'])
            ->whereBetween('created_at', [$startDate->format('Y-m-d 00:00:00'), $endDate->format('Y-m-d 23:59:59')])
            ->sum('left');

        return response()->json([
            'status' => 'success',
            'data' => [
                'base_salary'      => round($averageMonthlySalary),
                'per_day_salary'   => round($averageMonthlySalary / 30),
                'latest_per_day'   => round($latestSalary / 30),
                'attendance'       => [
                    'present'     => $stats['P'],
                    'absent'      => $stats['A'],
                    'half_day'    => $stats['HD'],
                    'late_punch'  => $stats['LT'],
                    'leaves'      => $stats['L'],
                    'cl'          => $stats['CL'],
                    'paid_leaves' => $stats['PL'],
                    'short_leaves'=> $stats['SL'],
                    'week_offs'   => $stats['WO'],
                    'holidays'    => $stats['HO'],
                    'extra_days'  => $stats['ED'],
                    'payable_days'=> $payableDays
                ],
                'actual_salary'    => round($actualSalary),
                'total_fine'       => round($totalFineAmount),
                'total_ta'         => round($totalTA),
                'active_loan'      => $activeLoan ? ['id' => $activeLoan->id, 'remaining_amount' => round($remainingLoan), 'default_cut' => round($defaultLoanDeduction)] : null,
                'total_pending_incentive' => round($pendingIncentives) 
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

        $isGodMode = false;
        if ($context && isset($context->is_god) && $context->is_god) { $isGodMode = true; }
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        if ($user && in_array(strtolower($user->email ?? ''), $developerEmails)) { $isGodMode = true; }

        $userPerms = [];
        if ($user && method_exists($user, 'getAllPermissions')) {
            try { $userPerms = $user->getAllPermissions()->pluck('name')->toArray(); } catch (\Exception $e) { }
        } elseif ($context && isset($context->permissions) && is_array($context->permissions)) {
            $userPerms = $context->permissions;
        }

        $hasDirect = $isGodMode || in_array('emp_salary_add_direct', $userPerms) || in_array('emp_salary_add', $userPerms);
        $hasRequest = in_array('emp_salary_add_request', $userPerms);

        if (!$hasDirect && !$hasRequest) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized! Required permissions missing.'], 403);
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
            'incentive_added'        => 'nullable|numeric', 
        ]);

      DB::beginTransaction();
        try {
            $employee = Employee::find($request->employee_id);
            $salaryStatus = ($isGodMode || $hasDirect) ? 'active' : 'pending';

            $exists = Salary::where('employee_id', $employee->id)->where('salary_month', $request->salary_month)->first();

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
                
                'base_salary'            => round($request->base_salary),
                'per_day_salary'         => round($request->per_day_salary ?? ($request->base_salary / 30)),
                
                'present_days'           => $request->present_days ?? 0,
                'absent_days'            => $request->absent_days ?? 0,
                'half_days'              => $request->half_days ?? 0,
                'paid_leaves'            => $request->paid_leaves ?? 0,
                'cl'                     => $request->cl ?? 0,
                'short_leaves'           => $request->short_leaves ?? 0,
                'week_offs'              => $request->week_offs ?? 0,
                'holidays'               => $request->holidays ?? 0,
                'extra_days'             => $request->extra_days ?? 0,
                'total_payable_days'     => $request->total_payable_days ?? 0,
                
                'actual_salary'          => round($request->actual_salary),
                'travel_allowance_added' => round($request->travel_allowance_added ?? 0),
                'incentive_added'        => round($request->incentive_added ?? 0), 
                'fine_deduction'         => round($request->fine_deduction ?? 0),
                'loan_deduction'         => round($request->loan_deduction ?? 0),
                'net_payable_salary'     => round($request->net_payable_salary),
                
                // Trackers for Voucher system
                'paid_amount'            => 0,
                'left_amount'            => round($request->net_payable_salary),
                'salary_payment_type'    => 'none',
                'dv_no'                  => null,
                
                'status'                 => $salaryStatus,
                'created_by'             => $user ? $user->id : null,
                'reward_days'            => $request->reward_days ?? 0,
                'remarks'                => $request->remarks,
                'approved_by'            => ($isGodMode || $hasDirect) && $user ? $user->id : null,
            ]);

            // LOAN DEDUCTION LOGIC
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
                    if ($loan->remaining_amount <= 0) { $loan->status = 'settled'; }
                    $loan->save();
                }
            }

            // FIFO INCENTIVE DISTRIBUTION LOGIC
            $amountToDistribute = $request->incentive_added ?? 0;
            
            if ($amountToDistribute > 0) {
                $startDate = Carbon::parse($request->salary_month . '-01')->startOfMonth()->format('Y-m-d 00:00:00');
                $endDate = Carbon::parse($request->salary_month . '-01')->endOfMonth()->format('Y-m-d 23:59:59');

                $incentives = DB::table('incentives')
                    ->where('emp_id', $employee->member_id)
                    ->whereIn('incentive_status', ['pending', 'active'])
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($incentives as $inc) {
                    if ($amountToDistribute <= 0) break;
                    
                    $payableForThisRow = min($inc->left, $amountToDistribute);
                    
                    $newPaid = $inc->paid + $payableForThisRow;
                    $newTotalPaid = $inc->total_paid + $payableForThisRow;
                    $newLeft = $inc->left - $payableForThisRow;
                    $newTotalLeft = $inc->total_left - $payableForThisRow;
                    
                    $newStatus = $newLeft <= 0 ? 'calculated' : 'active';

                    DB::table('incentives')->where('id', $inc->id)->update([
                        'paid' => $newPaid,
                        'total_paid' => $newTotalPaid,
                        'left' => $newLeft,
                        'total_left' => $newTotalLeft,
                        'incentive_status' => $newStatus,
                        'updated_at' => now()
                    ]);
                    $amountToDistribute -= $payableForThisRow;
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Salary Finalized and Saved Successfully!']);
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

        if (!$context->is_god) {
            $query->where('company_id', $context->company_id);
            if ($context->is_employee && $context->branch_id) { $query->where('branch_id', $context->branch_id); }
        }

        if ($request->filled('month')) $query->where('salary_month', $request->month);
        if ($request->filled('employee_id')) $query->where('employee_id', $request->employee_id);

        $totalFiltered = $query->count();
        if ($request->has('length') && $request->input('length') != -1) {
            $query->offset($request->input('start', 0))->limit($request->input('length', 10));
        }

        $salaries = $query->orderBy('id', 'desc')->get();

        $data = $salaries->map(function ($s) {
            // 🔥 NAYA: Calculate Current Loan Balance for Datatable
            $loanBal = 0;
            if ($s->loan_deduction > 0) {
                $rep = EmployeeLoanRepayment::where('salary_id', $s->id)->first();
                if ($rep) {
                    $l = EmployeeLoan::find($rep->employee_loan_id);
                    if ($l) { $loanBal = $l->remaining_amount; }
                }
            } else {
                $l = EmployeeLoan::where('employee_id', $s->employee->member_id)->where('status', 'active')->first();
                if ($l) { $loanBal = $l->remaining_amount; }
            }

            return [
                'id'          => $s->id,
                'emp_code'    => $s->employee->member_id ?? '-',
                'name'        => $s->employee->full_name ?? '-',
                'month'       => Carbon::parse($s->salary_month)->format('M Y'),
                'present'     => $s->present_days,
                'absent'      => $s->absent_days,
                'hd'          => $s->half_days,
                'l'           => $s->paid_leaves, 
                'cl'          => $s->cl,
                'sl'          => $s->short_leaves,
                'wo'          => $s->week_offs,
                'h'           => $s->holidays,
                'ed'          => $s->extra_days,
                'actual'      => $s->actual_salary,
                'fine'        => $s->fine_deduction,
                'loan'        => $s->loan_deduction,
                'loan_bal'    => $loanBal, // Output balance
                'ta'          => $s->travel_allowance_added,
                'payable'     => $s->net_payable_salary,
                'status'      => $s->status,
            ];
        });

        return response()->json([ "draw" => intval($request->input('draw')), "recordsTotal" => Salary::count(), "recordsFiltered" => $totalFiltered, "data" => $data ]);
    }

    // ==============================================================
    // 4. PRINT REGISTER / EXPORT LOGIC
    // ==============================================================
    public function printRegister(Request $request)
    {
        if (!auth()->check() && $request->has('token')) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
            if ($accessToken) { auth()->login($accessToken->tokenable); }
        }

        $context = $this->getGlobalContext();
        $user = auth()->user();

        if (!$user) { abort(401, 'Unauthorized Access! Token missing or invalid.'); }

        $userPerms = [];
        if (method_exists($user, 'getAllPermissions')) {
            try { $userPerms = $user->getAllPermissions()->pluck('name')->toArray(); } catch (\Exception $e) {}
        } elseif ($context && isset($context->permissions)) {
            $userPerms = $context->permissions;
        }

        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        $isGodMode = ($context && $context->is_god) || in_array(strtolower($user->email ?? ''), $developerEmails);

        if (!$isGodMode && !in_array('emp_salary_print', $userPerms) && !in_array('emp_salary_export', $userPerms)) {
            abort(403, 'Unauthorized action.');
        }

       $query = Salary::with(['employee', 'department', 'designation']);

        $reqCompany = $request->filled('company_id') ? $request->company_id : ($context->company_id ?? null);
        $reqBranch = $request->filled('branch_id') ? $request->branch_id : ($context->branch_id ?? null);

        if (!$isGodMode) {
            if ($reqCompany) { $query->where('company_id', $reqCompany); }
            if ($context->is_employee && $reqBranch) { $query->where('branch_id', $reqBranch); }
        }

        if ($request->filled('month')) $query->where('salary_month', $request->month);
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
        if ($request->filled('department_id')) $query->where('department_id', $request->department_id);
        if ($request->filled('employee_id')) $query->where('employee_id', $request->employee_id);

        $salaries = $query->orderBy('id', 'desc')->get();

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

            // Added L.Bal Column
            $columns = ['S.N.', 'EMP Code', 'Name', 'P', 'A', 'HD', 'L', 'CL', 'SL', 'WO', 'H', 'ED', 'Base Salary', 'Actual Salary', 'Incentive', 'Fine', 'L.Cut', 'L.Bal', 'TA', 'Net Payable'];

            $callback = function () use ($salaries, $columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);
                foreach ($salaries as $key => $sal) {
                    
                    // Fetch Loan Balance for Excel
                    $loanBal = 0;
                    if ($sal->loan_deduction > 0) {
                        $rep = EmployeeLoanRepayment::where('salary_id', $sal->id)->first();
                        if ($rep) { $l = EmployeeLoan::find($rep->employee_loan_id); if ($l) { $loanBal = $l->remaining_amount; } }
                    } else {
                        $l = EmployeeLoan::where('employee_id', $sal->employee->member_id)->where('status', 'active')->first();
                        if ($l) { $loanBal = $l->remaining_amount; }
                    }

                    fputcsv($file, [
                        $key + 1, $sal->employee->member_id ?? '-', $sal->employee->full_name ?? '-',
                        $sal->present_days, $sal->absent_days, $sal->half_days, $sal->paid_leaves, $sal->cl, $sal->short_leaves, $sal->week_offs, $sal->holidays, $sal->extra_days,
                        $sal->base_salary, $sal->actual_salary, $sal->incentive_added, $sal->fine_deduction, $sal->loan_deduction, $loanBal, $sal->travel_allowance_added, $sal->net_payable_salary
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

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

    // ==============================================================
    // 5. DELETE & BULK DELETE ACTIONS
    // ==============================================================
    public function destroy($id)
    {
        $context = $this->getGlobalContext();
        $user = auth()->user();

        $isGodMode = ($context && $context->is_god) || ($user && in_array(strtolower($user->email ?? ''), ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in']));
        $userPerms = [];
        if ($user && method_exists($user, 'getAllPermissions')) {
            try { $userPerms = $user->getAllPermissions()->pluck('name')->toArray(); } catch (\Exception $e) {}
        }

        if (!$isGodMode && !in_array('emp_salary_delete', $userPerms)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized to delete salary!'], 403);
        }

        $salary = Salary::findOrFail($id);

        if ($salary->loan_deduction > 0) {
            $repayment = EmployeeLoanRepayment::where('salary_id', $salary->id)->first();
            if ($repayment) {
                $loan = EmployeeLoan::find($repayment->employee_loan_id);
                if ($loan) {
                    $loan->paid_amount -= $repayment->amount_deducted;
                    $loan->remaining_amount += $repayment->amount_deducted;
                    $loan->status = 'active'; 
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
            $this->destroy($id);
        }
        return response()->json(['status' => 'success', 'message' => 'Selected salaries deleted successfully!']);
    }

    // ==============================================================
    // 6. INDIVIDUAL SALARY SLIP PRINT
    // ==============================================================
    public function printSlip($id)
    {
        $salary = Salary::with(['employee', 'department', 'designation', 'company'])->findOrFail($id);
        
        $gross = $salary->actual_salary;
        
        $breakup = [
            'basic_pay' => $gross * 0.40,
            'hra'       => $gross * 0.08,
            'da'        => $gross * 0.08,
            'medical'   => $gross * 0.08,
            'other_allow' => $gross * 0.36,
            'ta'        => $salary->travel_allowance_added,
            'gross_earn'=> $gross + $salary->travel_allowance_added,
            
            'pf'  => 0, 'esi' => 0, 'other_deduc' => $salary->fine_deduction,
            'advance' => $salary->loan_deduction,
            'gross_deduc' => $salary->fine_deduction + $salary->loan_deduction,
        ];

        $loans = EmployeeLoan::where('employee_id', $salary->employee->member_id)->with('repayments')->get();

        return view('admin.salaries.salary_slip', compact('salary', 'breakup', 'loans'));
    }
}