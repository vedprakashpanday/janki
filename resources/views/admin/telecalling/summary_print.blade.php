<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Telecaller Summary Print</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            background: #fff;
        }

        .table th {
            background-color: #f8f9fa !important;
            -webkit-print-color-adjust: exact;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            @page {
                size: A4 portrait;
                margin: 2mm 3mm;
            }

        }
    </style>
</head>

<body onload="window.print()">

    <x-print-header :company="$company" :branch="$branch" />

    <div class="container-fluid mt-4">
        <h4 class="text-center fw-bold mb-1">Telecaller Performance Summary</h4>
        <p class="text-center text-muted mb-4">
            <strong>Employee:</strong> {{ $employee->full_name ?? ($employee->member_name ?? 'Unknown') }} |
            <strong>Date:</strong> {{ $request->date ?: 'All Time' }} |
            <strong>Month:</strong> {{ $request->month ?: 'All Time' }}
        </p>

        <table class="table table-bordered table-striped text-center mb-5">
            <thead>
                <tr>
                    <th>Call Status</th>
                    <th>Assigned Total</th>
                    <th>Called</th>
                    <th>Left Call</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $tAssign = 0;
                    $tCall = 0;
                    $tLeft = 0;
                @endphp

                @foreach ($summary as $status => $counts)
                    @if ($counts['assigned'] > 0 || $counts['called'] > 0 || $counts['left'] > 0)
                        <tr>
                            <td class="text-start fw-bold">{{ $status }}</td>
                            <td>{{ $counts['assigned'] }}</td>
                            <td>{{ $counts['called'] }}</td>
                            <td>{{ $counts['left'] }}</td>
                        </tr>
                        @php
                            $tAssign += $counts['assigned'];
                            $tCall += $counts['called'];
                            $tLeft += $counts['left'];
                        @endphp
                    @endif
                @endforeach

                <tr class="table-dark fw-bold">
                    <td class="text-end">TOTAL:</td>
                    <td>{{ $tAssign }}</td>
                    <td>{{ $tCall }}</td>
                    <td>{{ $tLeft }}</td>
                </tr>
            </tbody>
        </table>

        <!-- 🔥 NAYA: Interested Leads ki List -->
        @if(isset($interestedCustomers) && count($interestedCustomers) > 0)
            <h5 class="fw-bold mt-4 text-success" style="border-bottom: 2px solid #198754; padding-bottom: 5px;">Interested / Hot Leads</h5>
            <table class="table table-bordered table-striped text-center mt-3">
                <thead class="table-success">
                    <tr>
                        <th>#</th>
                        <th>Customer Name</th>
                        <th>Mobile Number</th>
                        <th>Refer By</th>
                        <th>Current Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($interestedCustomers as $index => $cust)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="text-start fw-bold">{{ $cust['name'] }}</td>
                        <td>{{ $cust['mobile'] }}</td>
                        <td>{{ $cust['refer_by'] }}</td>
                        <td><span class="badge bg-success" style="color: black !important;">{{ $cust['status'] }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-center text-muted mt-5">No interested leads found in this filter.</p>
        @endif
    </div>

</body>

</html>