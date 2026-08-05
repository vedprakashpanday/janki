<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Salary Slip - {{ $salary->employee->full_name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            margin: 0;
            padding: 20px;
            color: #1e293b;
            background-color: #f1f5f9;
        }

        .slip-wrapper {
            max-width: 850px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            border-top: 6px solid #2563eb;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .fw-bold {
            font-weight: 700;
        }

        .fw-semibold {
            font-weight: 600;
        }

        .text-muted {
            color: #64748b;
        }

        .page-title {
            font-size: 22px;
            letter-spacing: 2px;
            color: #0f172a;
            margin-top: 25px;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .pay-month {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 30px;
        }

        /* Modern Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        th,
        td {
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }

        .table-header th {
            background-color: #f8fafc;
            text-transform: uppercase;
            font-size: 11px;
            color: #475569;
            letter-spacing: 0.5px;
        }

        /* Employee Info Box */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 5px;
        }

        .info-item:nth-last-child(-n+2) {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-label {
            color: #64748b;
            font-weight: 500;
        }

        .info-val {
            color: #0f172a;
            font-weight: 600;
            text-align: right;
        }

        .amount-col {
            text-align: right !important;
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            font-weight: 600;
        }

        .text-success {
            color: #16a34a;
        }

        .text-danger {
            color: #dc2626;
        }

        .gross-row th {
            background-color: #f1f5f9;
            font-size: 13px;
            color: #0f172a;
        }

        .net-pay-row th {
            background-color: #0f172a;
            color: #fff;
            font-size: 16px;
            padding: 18px 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .net-pay-val {
            background-color: #10b981 !important;
            color: #fff !important;
            font-size: 20px;
            text-align: right;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 10px;
            text-transform: uppercase;
            border-left: 3px solid #2563eb;
            padding-left: 8px;
        }

        @media print {
            body {
                background-color: #fff;
                padding: 0;
            }

            .slip-wrapper {
                box-shadow: none;
                border-radius: 0;
                padding: 0;
                border-top: none;
                max-width: 100%;
            }

            .btn-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="slip-wrapper">

        <!-- HEADER COMPONENT -->
        <x-print-header :company="$salary->company" :branch="$salary->branch" />

        <div class="text-center">
            <div class="page-title">PAYSLIP</div>
            <div class="pay-month">For the month of <span
                    style="color:#0f172a; font-weight: 600;">{{ \Carbon\Carbon::parse($salary->salary_month)->format('F Y') }}</span>
            </div>
        </div>

        <!-- Employee Details -->
        <div class="info-grid">
            <div class="info-item"><span class="info-label">Employee Name</span> <span
                    class="info-val">{{ $salary->employee->full_name }}</span></div>
            <div class="info-item"><span class="info-label">EMP Code</span> <span
                    class="info-val">{{ $salary->employee->member_id }}</span></div>
            <div class="info-item"><span class="info-label">Designation</span> <span
                    class="info-val">{{ $salary->designation->designation_name ?? '-' }}</span></div>
            <div class="info-item"><span class="info-label">Department</span> <span
                    class="info-val">{{ $salary->department->department_name ?? '-' }}</span></div>
            <div class="info-item"><span class="info-label">Total Days</span> <span
                    class="info-val">{{ \Carbon\Carbon::parse($salary->salary_month)->daysInMonth }} Days</span></div>
            <div class="info-item"><span class="info-label">Payable Days</span> <span
                    class="info-val text-success">{{ $salary->total_payable_days }} Days</span></div>
        </div>

        <!-- Salary Breakdown -->
        <div class="section-title">Salary Breakdown</div>
        <table>
            <tr class="table-header">
                <th colspan="2" style="width: 50%;">Earnings</th>
                <th colspan="2" style="width: 50%;">Deductions</th>
            </tr>
            <tr>
                <td>Basic Pay</td>
                <td class="amount-col">{{ number_format($breakup['basic_pay'], 2) }}</td>
                <td>Provident Fund (PF)</td>
                <td class="amount-col">{{ number_format($breakup['pf'], 2) }}</td>
            </tr>
            <tr>
                <td>House Rent Allowance (HRA)</td>
                <td class="amount-col">{{ number_format($breakup['hra'], 2) }}</td>
                <td>ESI Contribution</td>
                <td class="amount-col">{{ number_format($breakup['esi'], 2) }}</td>
            </tr>
            <tr>
                <td>Dearness Allowance (DA)</td>
                <td class="amount-col">{{ number_format($breakup['da'], 2) }}</td>
                <td>Fines & Penalties</td>
                <td class="amount-col">{{ number_format($breakup['other_deduc'], 2) }}</td>
            </tr>
            <tr>
                <td>Medical Allowance</td>
                <td class="amount-col">{{ number_format($breakup['medical'], 2) }}</td>
                <td>Advance / Loan Repayment</td>
                <td class="amount-col">{{ number_format($breakup['advance'], 2) }}</td>
            </tr>
            <tr>
                <td>Travel Allowance</td>
                <td class="amount-col">{{ number_format($breakup['ta'], 2) }}</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Other Allowances</td>
                <td class="amount-col">{{ number_format($breakup['other_allow'], 2) }}</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Performance Incentive</td>
                <td class="amount-col text-success">{{ number_format($salary->incentive_added ?? 0, 2) }}</td>
                <td></td>
                <td></td>
            </tr>
            <tr class="gross-row">
                <th class="fw-bold">Gross Earnings</th>
                <th class="amount-col text-success">₹
                    {{ number_format($breakup['gross_earn'] + ($salary->incentive_added ?? 0), 2) }}</th>
                <th class="fw-bold">Gross Deductions</th>
                <th class="amount-col text-danger">₹ {{ number_format($breakup['gross_deduc'], 2) }}</th>
            </tr>
            <tr class="net-pay-row">
                <th colspan="2">Net Payable Amount</th>
                <th colspan="2" class="net-pay-val">₹ {{ number_format($salary->net_payable_salary, 2) }}</th>
            </tr>
        </table>

        <!-- Loan History -->
        <div class="section-title" style="margin-top: 35px;">Advance / Loan History</div>
        <table>
            <tr class="table-header">
                <th>Loan Reference</th>
                <th>Disbursed Date</th>
                <th class="text-end">Total Loan (₹)</th>
                <th>Repayment Month</th>
                <th class="text-end">Deducted (₹)</th>
                <th class="text-end">Remaining Bal. (₹)</th>
            </tr>
            @forelse($loans as $loan)
                @foreach ($loan->repayments as $repayment)
                    <tr>
                        <td class="fw-semibold">{{ $loan->loan_code }}</td>
                        <td>{{ \Carbon\Carbon::parse($loan->created_at)->format('d M, Y') }}</td>
                        <td class="amount-col">{{ number_format($loan->total_amount, 2) }}</td>
                        <td>{{ date('F Y', strtotime($repayment->salary_month . '-01')) }}</td>
                        <td class="amount-col text-success">{{ number_format($repayment->amount_deducted, 2) }}</td>
                        <td class="amount-col text-danger">{{ number_format($loan->remaining_amount, 2) }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted" style="padding: 20px;">No active advances or
                        historical deductions found.</td>
                </tr>
            @endforelse
        </table>

        <div class="text-center" style="margin-top: 40px;">
            <button onclick="window.print()" class="btn-print"
                style="padding: 12px 30px; font-size: 15px; font-weight: 600; cursor: pointer; background: #2563eb; color: #fff; border: none; border-radius: 6px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);">
                <svg style="width: 18px; height: 18px; margin-right: 8px; vertical-align: sub;" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                    </path>
                </svg>
                Print Standard Slip
            </button>
        </div>
    </div>

</body>

</html>
