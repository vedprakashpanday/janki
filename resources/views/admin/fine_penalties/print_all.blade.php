<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Filtered Fine & Penalty Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .receipt-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        @media print {
            .btn-print {
                display: none;
            }

            .receipt-container {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="text-end mb-3">
            <button onclick="window.print()" class="btn btn-primary btn-print"><i class="fas fa-print"></i> Print
                Report</button>
        </div>

        <div class="receipt-container">
            <x-print-header :company="$company" :branch="null" />

            <h4 class="text-center mt-4 mb-4 text-uppercase text-decoration-underline">Fine & Penalty Report</h4>

            <table class="table table-bordered table-sm text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Employee Name</th>
                        <th>Action Type</th>
                        <th>Fine</th>
                        <th>Penalty</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fines as $fine)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($fine->date)->format('d M, Y') }}</td>
                            <td>
                                <strong>{{ $fine->employee->full_name ?? 'N/A' }}</strong><br>
                                <span class="text-muted"
                                    style="font-size: 10px;">{{ $fine->employee->member_id ?? '' }}</span>
                            </td>
                            <td class="text-uppercase">{{ $fine->treat_as ?? 'Apply' }}</td>
                            <td>{{ $fine->fine_rupees ? '₹' . $fine->fine_rupees : ($fine->fine_days ? $fine->fine_days . ' Days' : '-') }}
                            </td>
                            <td>{{ $fine->penalty_rupees ? '₹' . $fine->penalty_rupees : ($fine->penalty_days ? $fine->penalty_days . ' Days' : '-') }}
                            </td>
                            <td class="fw-bold">{{ strtoupper($fine->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No records found for the applied filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
