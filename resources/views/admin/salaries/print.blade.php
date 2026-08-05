<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Salary Register - {{ $request->month }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000 !important;
            padding: 4px;
            vertical-align: middle;
        }

        @media print {
            @page {
                size: landscape;
                margin: 5mm;
            }

            .btn-print {
                display: none !important;
            }
        }

        .footer-sigs {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            text-align: center;
        }

        .sig-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
        }

        .digital-sign {
            height: 40px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            font-size: 16px;
            font-weight: bold;
            font-style: italic;
            color: #333;
            padding-bottom: 5px;
            font-family: 'Brush Script MT', cursive;
        }

        .sig-line {
            border-top: 1px solid #000;
            width: 220px;
            padding-top: 5px;
            font-weight: bold;
            font-size: 13px;
        }

        /* 🔥 Naya CSS Abbreviations ke liye 🔥 */
        .legend-box {
            margin-top: 35px;
            padding-top: 8px;
            border-top: 1px dashed #888;
            font-size: 9.5px;
            text-align: center;
            color: #444;
            line-height: 1.6;
        }
    </style>
</head>

<body onload="window.print()" class="landscape-mode">

    <div class="container-fluid py-3">
        <!-- Header Component -->
        <x-print-header :company="$company" :branch="$branch" />

        <h5 class="text-center fw-bold mt-3 text-uppercase">
            SALARY REGISTER FOR THE MONTH OF {{ date('F Y', strtotime($request->month . '-01')) }}
        </h5>

        <table class="table table-bordered text-center" style="font-size: 10px;">
            <thead class="bg-light align-middle">
                <tr>
                    <th>S.N.</th>
                    <th>EMP Code</th>
                    <th>Name</th>
                    <th>Desig.</th>
                    <th title="Present">P</th>
                    <th title="Absent">A</th>
                    <th title="Half Day">HD</th>
                    <th title="Leave">L</th>
                    <th title="Casual Leave" class="text-info">CL</th>
                    <th title="Short Leave">SL</th>
                    <th title="Week Off">WO</th>
                    <th title="Holiday">H</th>
                    <th title="Extra Days" class="text-dark">ED</th>
                    <th>Base(₹)</th>
                    <th>Actual(₹)</th>
                    <th>Inc.(₹)</th>
                    <th>Fine(₹)</th>
                    <th>L.Cut(₹)</th>
                    <th class="text-danger">L.Bal(₹)</th>
                    <th>T.A.(₹)</th>
                    <th>Net Pay(₹)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salaries as $key => $sal)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $sal->employee->member_id ?? '-' }}</td>
                        <td class="text-start fw-bold">{{ $sal->employee->full_name ?? '-' }}</td>
                        <td>{{ $sal->designation->designation_name ?? '-' }}</td>

                        <td class="text-success fw-bold">{{ $sal->present_days }}</td>
                        <td class="text-danger fw-bold">{{ $sal->absent_days }}</td>
                        <td class="text-warning">{{ $sal->half_days }}</td>
                        <td class="text-secondary">{{ $sal->paid_leaves }}</td>
                        <td class="text-info fw-bold">{{ $sal->cl }}</td>
                        <td class="text-primary">{{ $sal->short_leaves }}</td>
                        <td class="text-muted">{{ $sal->week_offs }}</td>
                        <td class="text-info">{{ $sal->holidays }}</td>
                        <td class="text-dark fw-bold">{{ $sal->extra_days }}</td>

                        <td>{{ number_format($sal->base_salary, 2) }}</td>
                        <td>{{ number_format($sal->actual_salary, 2) }}</td>
                        <td class="text-info">{{ number_format($sal->incentive_added, 2) }}</td>
                        <td class="text-danger">{{ number_format($sal->fine_deduction, 2) }}</td>

                        <!-- L.Cut and L.Bal Logic -->
                        @php
                            $loanBal = 0;
                            if ($sal->loan_deduction > 0) {
                                $rep = \App\Models\EmployeeLoanRepayment::where('salary_id', $sal->id)->first();
                                if ($rep) {
                                    $l = \App\Models\EmployeeLoan::find($rep->employee_loan_id);
                                    if ($l) {
                                        $loanBal = $l->remaining_amount;
                                    }
                                }
                            } else {
                                $l = \App\Models\EmployeeLoan::where('employee_id', $sal->employee->member_id)
                                    ->where('status', 'active')
                                    ->first();
                                if ($l) {
                                    $loanBal = $l->remaining_amount;
                                }
                            }
                        @endphp

                        <td class="text-danger">{{ number_format($sal->loan_deduction, 2) }}</td>
                        <td class="text-danger fw-bold">{{ number_format($loanBal, 2) }}</td>

                        <td class="text-success">{{ number_format($sal->travel_allowance_added, 2) }}</td>
                        <td class="fw-bold text-primary">{{ number_format($sal->net_payable_salary, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="21" class="text-center">No salary records found for this criteria.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-light fw-bold">
                <tr>
                    <td colspan="13" class="text-end pe-3">GRAND TOTAL</td>
                    <td>{{ number_format($salaries->sum('base_salary'), 2) }}</td>
                    <td>{{ number_format($salaries->sum('actual_salary'), 2) }}</td>
                    <td class="text-info">{{ number_format($salaries->sum('incentive_added'), 2) }}</td>
                    <td class="text-danger">{{ number_format($salaries->sum('fine_deduction'), 2) }}</td>
                    <td class="text-danger">{{ number_format($salaries->sum('loan_deduction'), 2) }}</td>
                    <td class="text-danger">-</td>
                    <td class="text-success">{{ number_format($salaries->sum('travel_allowance_added'), 2) }}</td>
                    <td class="text-primary fs-6">{{ number_format($salaries->sum('net_payable_salary'), 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- DYNAMIC DIGITAL SIGNATURE FOOTER -->
        <div class="footer-sigs">
            <div class="sig-block">
                <div class="digital-sign">{{ $preparedBy }}</div>
                <div class="sig-line">Prepared / Approved By</div>
            </div>
            <div class="sig-block">
                <div class="digital-sign">{{ $checkedBy }}</div>
                <div class="sig-line">Checked By</div>
            </div>
            <div class="sig-block">
                <div class="digital-sign">{{ $authorizedSignatory }}</div>
                <div class="sig-line">Authorized Signatory</div>
            </div>
        </div>

        <!-- 🔥 NAYA: ABBREVIATIONS SECTION 🔥 -->
        <div class="legend-box">
            <strong>*Abbreviations:</strong>
            <strong>P</strong> - Present |
            <strong>A</strong> - Absent |
            <strong>HD</strong> - Half Day |
            <strong>L</strong> - Leave (Unpaid) |
            <strong>CL</strong> - Casual Leave |
            <strong>SL</strong> - Short Leave |
            <strong>WO</strong> - Week Off |
            <strong>H</strong> - Holiday |
            <strong>ED</strong> - Extra Day(s) |
            <strong>Base</strong> - Base Salary |
            <strong>Actual</strong> - Actual Salary |
            <strong>Inc.</strong> - Incentive |
            <strong>L.Cut</strong> - Loan Deduction |
            <strong>L.Bal</strong> - Loan Balance |
            <strong>T.A.</strong> - Travel Allowance |
            <strong>Net Pay</strong> - Net Payable Salary
        </div>

    </div>

</body>

</html>
