<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Incentive Receipt & History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
        }

        .receipt-box {
            max-width: 800px;
            margin: 20px auto;
            border: 1px solid #ccc;
            padding: 20px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 15px;
        }

        .info-table td {
            padding: 5px;
            border-bottom: 1px dashed #eee;
        }

        table.history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .history-table th,
        .history-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
        }

        .history-table th {
            background-color: #f8f9fa !important;
            -webkit-print-color-adjust: exact;
            font-size: 12px;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .receipt-box {
                border: none;
                margin: 0;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="text-end m-3 no-print">
        <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <div class="receipt-box">
        <x-print-header :company="$company" :branch="$branch" />

        <h5 class="text-center mt-3 mb-3 fw-bold text-decoration-underline">INCENTIVE RECEIPT & HISTORY</h5>

        <table class="info-table">
            <tr>
                <td width="20%"><strong>Employee Name:</strong></td>
                <td width="30%">{{ $incentive->employee->full_name ?? '-' }}</td>
                <td width="20%"><strong>Member ID:</strong></td>
                <td width="30%">{{ $incentive->emp_id }}</td>
            </tr>
            <tr>
                <td><strong>Department:</strong></td>
                <td>{{ $incentive->department->department_name ?? '-' }}</td>
                <td><strong>Designation:</strong></td>
                <td>{{ $incentive->designation->designation_name ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Current Receipt Date:</strong></td>
                <td>{{ \Carbon\Carbon::parse($incentive->created_at)->format('d M, Y h:i A') }}</td>
                <td><strong>Current Status:</strong></td>
                <td><b>{{ strtoupper($incentive->incentive_status) }}</b></td>
            </tr>
        </table>

        <h6 class="mt-4 fw-bold bg-dark text-white p-1" style="-webkit-print-color-adjust: exact;">PAYMENT HISTORY (UP
            TO CURRENT RECEIPT)</h6>

        <table class="history-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Incentive Type</th>
                    <th>DV No.</th>
                    <th>Calculated Amount</th>
                    <th>Amt Paid</th>
                    <th>Total Paid</th>
                    <th>Balance (Left)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($history as $row)
                    <tr
                        style="{{ $row->id == $incentive->id ? 'background-color: #e9ecef !important; font-weight:bold;' : '' }}">
                        <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d-m-Y') }}</td>
                        <td>{{ $row->type->name ?? 'Other' }}</td>
                        <td>{{ $row->dv_no ?? '-' }}</td>
                        <td>₹ {{ number_format($row->calculated_amount, 2) }}</td>
                        <td>₹ {{ number_format($row->paid, 2) }}</td>
                        <td>₹ {{ number_format($row->total_paid, 2) }}</td>
                        <td>₹ {{ number_format($row->total_left, 2) }}</td>
                        <td>{{ strtoupper($row->incentive_status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

      <!-- ========================================== -->
        <!-- SIGNATURE SECTION -->
        <!-- ========================================== -->
        <div class="d-flex justify-content-between" style="margin-top: 100px;">
            
            <!-- Left Side: Employee -->
            <div class="text-center" style="min-width: 220px;">
                <div class="fw-bold mb-2 text-dark" style="font-size: 12px; text-transform: uppercase; white-space: nowrap;">
                    {{ $incentive->employee->full_name ?? 'Employee' }} ({{ $incentive->emp_id }})
                </div>
                <div style="border-top: 1px solid #000; padding-top: 5px;">
                    <strong>Employee Signature</strong>
                </div>
            </div>

            <!-- Right Side: Authorized Signatory -->
            <div class="text-center" style="min-width: 220px;">
                <div class="fw-bold mb-2 text-dark" style="font-size: 12px; text-transform: uppercase; white-space: nowrap;">
                    {{ $signatoryName ?? 'HR MANAGEMENT' }}
                </div>
                <div style="border-top: 1px solid #000; padding-top: 5px;">
                    <strong>Authorized Signatory</strong>
                </div>
            </div>

        </div>
    </div>
</body>

</html>
