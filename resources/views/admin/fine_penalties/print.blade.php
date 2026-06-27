<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fine/Penalty Receipt - {{ $fine->employee->name ?? 'Employee' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; }
        .receipt-container { max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
        .section-title { background-color: #f8f9fa; padding: 8px; font-weight: bold; border-bottom: 2px solid #000; margin-top: 20px; margin-bottom: 15px; }
        @media print {
            .btn-print { display: none; }
            .receipt-container { border: none; padding: 0; }
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <div class="text-end mb-3">
        <button onclick="window.print()" class="btn btn-primary btn-print"><i class="fas fa-print"></i> Print Document</button>
    </div>

    <div class="receipt-container">
        <x-print-header :company="$company" :branch="$fine->employee->branch ?? null" />

        <h4 class="text-center mt-4 mb-4 text-uppercase text-decoration-underline">Fine & Penalty Notice</h4>

        <div class="row mb-3">
            <div class="col-6">
                <strong>Employee Name:</strong> {{ $fine->employee->name ?? 'N/A' }}<br>
                <strong>Employee ID:</strong> {{ $fine->employee->member_id ?? 'N/A' }}<br>
                <strong>Department:</strong> {{ $fine->employee->department->department_name ?? 'N/A' }}<br>
                <strong>Designation:</strong> {{ $fine->employee->designation->designation_name ?? 'N/A' }}
            </div>
            <div class="col-6 text-end">
                <strong>Record No:</strong> FP-{{ str_pad($fine->id, 5, '0', STR_PAD_LEFT) }}<br>
                <strong>Date of Issue:</strong> {{ \Carbon\Carbon::parse($fine->date)->format('d M, Y') }}<br>
                <strong>Status:</strong> 
                <span style="color: {{ $fine->status === 'Approved' ? 'green' : ($fine->status === 'Rejected' ? 'red' : 'orange') }}">
                    {{ strtoupper($fine->status) }}
                </span>
            </div>
        </div>

        <div class="section-title">Details of Charges</div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Charge Type</th>
                    <th>Amount (₹)</th>
                    <th>Deduction Days</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Fine</strong></td>
                    <td>{{ $fine->fine_rupees ? '₹' . number_format($fine->fine_rupees, 2) : '-' }}</td>
                    <td>{{ $fine->fine_days ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>Penalty</strong></td>
                    <td>{{ $fine->penalty_rupees ? '₹' . number_format($fine->penalty_rupees, 2) : '-' }}</td>
                    <td>{{ $fine->penalty_days ?? '-' }}</td>
                </tr>
            </tbody>
        </table>

        @if($fine->description)
        <div class="section-title">Remarks / Description</div>
        <div class="p-2 border">
            {!! $fine->description !!}
        </div>
        @endif

        <div class="row mt-5 pt-5">
            <div class="col-6 text-center">
                _______________________<br>
                <strong>Employee Signature</strong>
            </div>
            <div class="col-6 text-center">
                _______________________<br>
                <strong>Authorized Signatory</strong>
            </div>
        </div>
    </div>
</div>

</body>
</html>