<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Incentives Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; position: relative; z-index: 1; }
        
        /* 🌟 WATERMARK LOGIC 🌟 */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.1; /* Light watermark */
            z-index: -1;
            width: 600px;
            pointer-events: none;
        }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; }
        
        @media print {
             @page {
                size: A4 portrait;
                margin: 6mm 2mm;
            }
            .no-print { display: none !important; }
            .watermark { display: block !important; }

        }
    </style>
</head>
<body onload="window.print()">

    <!-- Print Action Button (Hidden during actual print) -->
    <div class="text-end m-3 no-print">
        <button onclick="window.print()" class="btn btn-primary">Print Document</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <div class="container-fluid">
        <!-- 🌟 YOUR CUSTOM COMPONENT 🌟 -->
        <x-print-header :company="$company" :branch="$branch" />

        <!-- Watermark Image -->
        @if($company && $company->company_logo)
            <img src="{{ asset($company->company_logo) }}" class="watermark" alt="Watermark">
        @endif

        <h4 class="text-center mt-3 mb-3 text-decoration-underline">EMPLOYEE INCENTIVES REPORT</h4>

        <table>
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Date</th>
                    <th>Employee Name</th>
                    <th>Member ID</th>
                    <th>Type</th>
                    <th>Calculated Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($incentives as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d-M-Y') }}</td>
                    <td>{{ $row->employee->full_name ?? '-' }}</td>
                    <td>{{ $row->employee->member_id ?? '-' }}</td>
                    <td>{{ $row->type->name ?? 'Other' }}</td>
                    <td>₹ {{ number_format($row->calculated_amount, 2) }}</td>
                    <td>{{ strtoupper($row->incentive_status) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No incentives found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</body>
</html>