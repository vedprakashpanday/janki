<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telecalling Report Print</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            /* Thoda chhota kiya portrait me fit karne ke liye */
            color: #000;
            background-color: #fff;
            position: relative;
        }

        /* 🔥 IMAGE WATERMARK CSS 🔥 */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            pointer-events: none;
            text-align: center;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .watermark img {
            width: 400px;
            /* Portrait ke hisaab se thoda adjust kiya */
            opacity: 0.10;
        }

        .watermark .text-watermark {
            font-size: 55px;
            font-weight: 900;
            color: #000;
            transform: rotate(-30deg);
            display: inline-block;
            opacity: 0.10;
        }

        /* 🌟 TABLE CSS 🌟 */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        /* 🔥 PADDING KAM KI GAYI HAI 🔥 */
        th,
        td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
            line-height: 1.2;
        }

        th {
            background-color: #f0f0f0 !important;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 9px;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .small-text {
            font-size: 8.5px;
            color: #444;
        }

        /* 🔥 PRINT OPTIMIZATION 🔥 */
        @media print {
            @page {
                size: portrait;
                /* 🔥 LANDSCAPE SE PORTRAIT KAR DIYA 🔥 */
                margin: 5mm;
            }

            .no-print {
                display: none !important;
            }

            body {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
                background-color: transparent !important;
            }

            .watermark img,
            .watermark .text-watermark {
                opacity: 0.10 !important;
            }
        }

        .print-btn-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>

<body>

    <div class="print-btn-container no-print">
        <button onclick="window.print()" class="btn btn-primary shadow rounded-pill px-4 fw-bold">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                class="bi bi-printer me-2" viewBox="0 0 16 16">
                <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z" />
                <path
                    d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z" />
            </svg> Print Report
        </button>
    </div>

    <div class="watermark">
        @if ($company && $company->company_logo)
            <img src="{{ asset($company->company_logo) }}" alt="Watermark Logo">
        @else
            <div class="text-watermark">{{ $company ? strtoupper($company->company_name) : 'AMITABH BUILDERS' }}</div>
        @endif
    </div>

    <div class="container-fluid p-1">
        <x-print-header :company="$company" :branch="$branch" />

        <div class="text-center mt-2 mb-2">
            <h6 class="fw-bold m-0" style="text-decoration: underline;">TELECALLING ALLOCATION & FOLLOW-UP REPORT</h6>
            <div class="small mt-1" style="font-size: 9px;">Generated On: {{ now()->format('d-M-Y h:i A') }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 3%;">S.N.</th>
                    <th style="width: 12%;">Telecaller</th>
                    <th style="width: 11%;">Customer</th>
                    <th style="width: 11%;">Mobile & Alt</th>
                    <th style="width: 9%;">Phase</th>
                    <th style="width: 8%;">Status</th>
                    <th style="width: 9%;">Int. For</th>
                    <th style="width: 8%;">Budget</th>
                    <th style="width: 8%;">Follow-Up</th>
                    <th style="width: 11%;">Remarks</th>
                    <th style="width: 10%;">Authority Remark</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allocations as $index => $item)
                    @php
                        // Telecaller Details
                        $emp = $item->assignee;
                        $tName = $emp ? $emp->full_name ?? ($emp->member_name ?? 'Unknown') : 'Unknown';
                        $tId = $emp ? $emp->member_id ?? '' : '';

                        // Customer Details
                        $cust = $item->customer;
                        $cName = $cust->cust_name ?? 'N/A';
                        $cMobile = $cust->mobile ?? 'N/A';
                        $cAlt = $cust->alternate_no ?? '';

                        // Phase Details
                        $phaseName = $item->phase ? $item->phase->phase_name : 'Gen.';

                        // Formatted Dates
                        $fDate = $item->followup_date
                            ? \Carbon\Carbon::parse($item->followup_date)->format('d-m-Y')
                            : '-';
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong class="d-block">{{ $tName }}</strong>
                            <span class="small-text">{{ $tId }}</span>
                        </td>
                        <td class="fw-bold">{{ $cName }}</td>
                        <td>
                            {{ $cMobile }}
                            @if ($cAlt)
                                <br><span class="small-text">Alt: {{ $cAlt }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $phaseName }}</td>
                        <td class="text-center fw-bold">{{ $item->call_status }}</td>
                        <td class="text-center">{{ $item->interested_for ?? '-' }}</td>
                        <td class="text-center">{{ $item->budget ?? '-' }}</td>
                        <td class="text-center text-danger fw-bold">{{ $fDate }}</td>
                        <td>{{ $item->remark ?? '-' }}</td>
                        <td></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center py-3 text-muted fs-6">No calling records found for the
                            applied filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4 pt-3 d-flex justify-content-between no-print" style="page-break-inside: avoid;">
            <div class="text-center">
                <hr style="width: 120px; border-color: #000; margin: 0 auto 5px;">
                <strong>Prepared By</strong>
            </div>
            <div class="text-center">
                <hr style="width: 120px; border-color: #000; margin: 0 auto 5px;">
                <strong>Checked By</strong>
            </div>
            <div class="text-center">
                <hr style="width: 120px; border-color: #000; margin: 0 auto 5px;">
                <strong>Authorized Signatory</strong>
            </div>
        </div>
    </div>

</body>

</html>
