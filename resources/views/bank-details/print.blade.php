<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Bank Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none; }
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    </style>
</head>
<body onload="window.print()">

    <!-- Aapka Custom Header Component -->
    <x-print-header :company="$company" :branch="$branch" />

    <div class="container-fluid mt-4">
        <h4 class="text-center mb-4">Bank Details Directory Report</h4>
        
        <table class="table table-bordered table-striped text-center">
            <thead class="table-dark">
                <tr>
                    <th>SL No</th>
                    <th>Member ID</th>
                    <th>Account Name</th>
                    <th>Account No</th>
                    <th>Bank Name</th>
                    <th>IFSC Code</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bankDetails as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->member_id }}</td>
                    <td>{{ $detail->account_name }}</td>
                    <td>{{ $detail->account_no }}</td>
                    <td>{{ $detail->bank_name ?? 'N/A' }}</td>
                    <td>{{ $detail->ifsc_code ?? 'N/A' }}</td>
                    <td>
                        @if($detail->status == 'active') Active
                        @elseif($detail->status == 'inactive') Inactive
                        @else Pending @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">No records found for the selected filters.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>