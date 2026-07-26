<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary Register - {{ $request->month }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 12px; }
        .table-bordered th, .table-bordered td { border: 1px solid #000 !important; padding: 4px; }
        .footer-sigs { margin-top: 50px; display: flex; justify-content: space-between; text-align: center; }
        .sig-line { border-top: 1px solid #000; padding-top: 5px; width: 250px; font-weight: bold; }
        @media print {
            @page { size: landscape; margin: 10mm; }
            .btn-print { display: none !important; }
        }
    </style>
    <style>
        .footer-sigs { margin-top: 80px; display: flex; justify-content: space-between; text-align: center; }
        .sig-block { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; }
        .sig-space { height: 60px; } /* Ye physical signature ke liye khali jagah hai */
        .sig-line { border-top: 1px solid #000; width: 220px; padding-top: 5px; font-weight: bold; font-size: 13px; }
        .sig-name { font-size: 11px; color: #444; margin-top: 2px; }
    </style>
</head>
<body onload="window.print()">

<div class="container-fluid py-3">
    
    <!-- Render Print Header Component -->
    <x-print-header :company="$company" :branch="$branch" />
    
    <h5 class="text-center fw-bold mt-3 text-uppercase">
        SALARY REGISTER FOR THE MONTH OF {{ date('F Y', strtotime($request->month . '-01')) }}
    </h5>

    <table class="table table-bordered table-sm mt-4 text-center align-middle">
        <thead class="table-light fw-bold">
            <tr>
                <th>S.N.</th>
                <th>Employee Code</th>
                <th>Employee Name</th>
                <th>Designation</th>
                <th>P</th>
                <th>A</th>
                <th>Leaves</th>
                <th>Base Salary (₹)</th>
                <th>Actual Salary (₹)</th>
                <th>Fine Deduct (₹)</th>
                <th>Loan EMI (₹)</th>
                <th>T.A. Added (₹)</th>
                <th>Net Payable (₹)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salaries as $key => $sal)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td class="fw-bold">{{ $sal->employee->member_id ?? '-' }}</td>
                <td>{{ $sal->employee->full_name ?? '-' }}</td>
                <td>{{ $sal->designation->designation_name ?? '-' }}</td>
                <td>{{ $sal->present_days }}</td>
                <td>{{ $sal->absent_days }}</td>
                <td>{{ $sal->paid_leaves + $sal->short_leaves + $sal->week_offs }}</td>
                <td>{{ number_format($sal->base_salary, 2) }}</td>
                <td>{{ number_format($sal->actual_salary, 2) }}</td>
                <td class="text-danger">{{ number_format($sal->fine_deduction, 2) }}</td>
                <td class="text-danger">{{ number_format($sal->loan_deduction, 2) }}</td>
                <td class="text-success">{{ number_format($sal->travel_allowance_added, 2) }}</td>
                <td class="fw-bold fs-6">{{ number_format($sal->net_payable_salary, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="13">No salary records found for this month.</td></tr>
            @endforelse
        </tbody>
    </table>

  <!-- DYNAMIC SIGNATURE FOOTER -->
    <div class="footer-sigs">
        <div class="sig-block">
            <div class="sig-space"></div>
            <div class="sig-line">Prepared / Approved By</div>
            <div class="sig-name">{{ $preparedBy }}</div>
        </div>
        <div class="sig-block">
            <div class="sig-space"></div>
            <div class="sig-line">Checked By</div>
            <div class="sig-name">{{ $checkedBy }}</div>
        </div>
        <div class="sig-block">
            <div class="sig-space"></div>
            <div class="sig-line">Authorized Signatory</div>
            <div class="sig-name">{{ $authorizedSignatory }}</div>
        </div>
    </div>
</div>

</body>
</html>