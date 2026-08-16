<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Register Print</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Force Landscape Mode for Print */
        @page {
            size: landscape;
            margin: 10mm;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            background-color: #fff;
            color: #000;
            position: relative;
        }

        /* Watermark Configuration */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            z-index: -1;
            width: 400px;
            max-width: 60%;
            pointer-events: none;
        }

        /* Register Table Styling */
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000 !important;
            vertical-align: middle;
            padding: 6px;
        }

        th {
            text-align: center;
            background-color: #f1f1f1 !important;
            font-size: 11px;
            font-weight: bold;
        }

        td {
            font-size: 11px;
        }

        .print-photo {
            width: 35px;
            height: 35px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .table-bordered th,
            .table-bordered td {
                border: 1px solid #000 !important;
            }

            /* Trigger landscape mode header styling from print-header.blade.php */
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>

<body class="landscape-mode" onload="window.print()">

    <!-- Print Action Button -->
    <div class="no-print mb-3 text-end p-3 bg-light border-bottom">
        <button onclick="window.print()" class="btn btn-primary fw-bold">
            <i class="fas fa-print me-1"></i> Print Register
        </button>
    </div>

    <!-- Dynamic Watermark -->
    @if ($company && $company->company_logo)
        <img src="{{ asset($company->company_logo) }}" class="watermark">
    @endif

    <!-- Injected Print Header Component -->
    <x-print-header :company="$company" :branch="$branch" />

    <!-- Table Title -->
    <div class="text-center mb-3">
        <h5 class="fw-bold d-inline-block border border-2 border-dark px-4 py-1"
            style="background-color: #f1f1f1 !important;">
            VISITOR REGISTER
            @if (request('time_scope') === 'today')
                (TODAY)
            @endif
        </h5>
    </div>

    <!-- Core Physical Register Mapping -->
    <table class="table table-bordered w-100">
        <thead>
            <tr>
                <th style="width: 4%;">S.No.</th>
                <th style="width: 8%;">Date</th>
                <th style="width: 14%;">Visitor's Name</th>
                <th style="width: 6%;">Pax</th>
                <th style="width: 16%;">Visitor's Address</th>
                <th style="width: 10%;">Visitor's Mobile No.</th>
                <th style="width: 12%;">Purpose to Visit</th>
                <th style="width: 12%;">Whom to Meet</th>
                <th style="width: 8%;">Time In</th>
                <th style="width: 8%;">Time Out</th>
                <th style="width: 8%;">Photo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($visitors as $index => $v)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($v->visiting_date)->format('d-m-Y') }}</td>
                    <td class="fw-bold">{{ $v->visitor_name }}</td>
                    <td class="text-center">{{ $v->no_of_visitors }}</td>
                    <td>{{ $v->visitor_address ?? '-' }}</td>
                    <td class="text-center fw-bold">{{ $v->visitor_mobile }}</td>
                    <td>{{ $v->purpose ?? '-' }}</td>
                    <td>{{ $v->whom_to_meet ?? '-' }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($v->time_in)->format('h:i A') }}</td>
                    <td class="text-center">
                        {{ $v->time_out ? \Carbon\Carbon::parse($v->time_out)->format('h:i A') : '-' }}</td>
                    <td class="text-center p-1">
                        @if ($v->photo)
                            <img src="{{ asset($v->photo) }}" class="print-photo">
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center py-4 fw-bold text-muted">No visitor records found for the
                        selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Location & Date Wise Summary Section -->
    <div style="page-break-inside: avoid; margin-top: 30px;">
        <h6 class="fw-bold border-bottom pb-1 mb-2" style="font-size: 13px;">VISITATION SUMMARY (LOCATION & DATE WISE)
        </h6>
        <table class="table table-bordered w-50 " style="font-size: 11px;">
            <thead>
                <tr>
                    <th>Location Name</th>
                    <th class="text-center">Date</th>
                    <th class="text-center">Total Visit (Pax)</th>
                </tr>
            </thead>
            <tbody>
                @php $hasSummary = false; @endphp
                @forelse($summary as $location => $dates)
                    @foreach ($dates as $date => $totalCount)
                        @php $hasSummary = true; @endphp
                        <tr>
                            <td class="fw-bold">{{ $location }}</td>
                            <td class="text-center">{{ $date }}</td>
                            <td class="text-center fw-bold">{{ $totalCount }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">No summary data available.</td>
                    </tr>
                @endforelse
            </tbody>
            @if (!empty($hasSummary))
                <tfoot>
                    <tr class="table-secondary fw-bold">
                        <td colspan="2" class="text-end">GRAND TOTAL:</td>
                        <td class="text-center text-danger fs-6">{{ $grandTotalPax ?? 0 }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</body>

</html>
