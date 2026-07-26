<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Print Employees</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fff;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
            font-size: 13px;
        }

        th {
            background-color: #f8f9fa !important;
            font-weight: bold;
        }

        .role-text {
            font-size: 11px;
            color: #555;
            display: block;
            margin-top: 2px;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <!-- Print Header Component -->
    <x-print-header :company="$company" :branch="$branch" />

    <div class="text-center mt-3 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-sm px-4">Print Now</button>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">Sl.</th>
                <th style="width: 15%;">Code (Emp ID)</th>
                <th style="width: 20%;">Name</th>
                <th style="width: 15%;">Department</th>
                <th style="width: 20%;">Role / Designation</th>
                <!-- NAYA COLUMN: Date of Joining -->
                <th style="width: 15%;">Date of Joining</th>
                <th style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $emp)
                @php
                    $empArr = $emp->toArray();

                    // Extract Department safely
                    $dept = $empArr['department'] ?? null;
                    $deptName = is_array($dept)
                        ? $dept['department_name'] ?? 'N/A'
                        : (is_string($dept) && !empty($dept)
                            ? $dept
                            : 'N/A');

                    // Extract Designation safely
                    $desig = $empArr['designation'] ?? null;
                    $desigName = is_array($desig)
                        ? $desig['designation_name'] ?? 'N/A'
                        : (is_string($desig) && !empty($desig)
                            ? $desig
                            : 'N/A');
                @endphp

                <tr>
                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                    <td><strong>{{ $emp->member_id ?? 'N/A' }}</strong></td>
                    <td>{{ $emp->full_name }}</td>
                    <td>{{ $deptName }}</td>
                    <td>
                        <strong>{{ $desigName }}</strong>
                        @if ($emp->role)
                            <span class="role-text">({{ $emp->role }})</span>
                        @endif
                    </td>

                    <!-- Date of Joining ko format karke print kar rahe hain -->
                    <td>{{ $emp->doj ? date('d-M-Y', strtotime($emp->doj)) : 'N/A' }}</td>

                    <td>{{ ucfirst($emp->emp_status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No data available</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>
