<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Blank Trip Slips</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Arial:wght@400;700;900&display=swap');

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: #fff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* 🔥 Force A4 Portrait Mode 🔥 */
        @page {
            size: A4 portrait;
            margin: 5mm;
        }

        .a4-page {
            width: 198mm;
            /* A4 Width (210mm) minus margins */
            margin: 0 auto;
        }

        /* Single Trip Row */
        .trip-row {
            width: 100%;
            height: 54mm;
            /* Adjusted height to fit 5 rows perfectly in A4 Portrait */
            border: 1.5px dashed #000;
            margin-bottom: 4mm;
            display: flex;
            box-sizing: border-box;
            position: relative;
            page-break-inside: avoid;
        }

        /* ---------------- 1. LEFT: TRIP NUMBER BOX (15%) ---------------- */
        .trip-col {
            width: 15%;
            border-right: 1.5px dashed #000;
            padding: 4px;
            box-sizing: border-box;
            background: #fff;
            z-index: 2;
        }

        .trip-inner {
            border: 1.5px solid #000;
            height: 100%;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            text-align: center;
        }

        .trip-header {
            border-bottom: 1.5px solid #000;
            font-size: 9px;
            font-weight: bold;
            padding: 4px 0;
        }

        .trip-prefix {
            font-size: 8px;
            border-bottom: 1.5px solid #000;
            padding: 4px 0;
            letter-spacing: -0.2px;
        }

        .trip-number {
            font-size: 20px;
            font-weight: 900;
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ---------------- CENTER WATERMARK ---------------- */
        .watermark {
            position: absolute;
            top: 50%;
            left: 65%;
            /* Center of the cutting line between the two slips */
            transform: translate(-50%, -50%);
            width: 45%;
            opacity: 0.12;
            z-index: 1;
            pointer-events: none;
        }

        /* ---------------- 2. MAIN RECEIPT: OFFICE COPY (50% Width) ---------------- */
        .receipt-col-main {
            width: 50%;
            padding: 4px 10px;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            z-index: 2;
        }

        .receipt-col-main .comp-name {
            font-size: 13px;
            font-weight: 900;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            letter-spacing: -0.3px;
            text-transform: uppercase;
        }

        /* 🔥 NAYA: Company Phone Number (Office Copy) */
        .receipt-col-main .comp-phone {
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            margin-top: 1px;
            color: #333;
        }

        .receipt-col-main .phase-name {
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            margin-top: 2px;
            text-transform: uppercase;
        }

        .receipt-col-main .slip-type {
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            text-decoration: underline;
            margin-top: 1px;
            text-transform: uppercase;
        }

        .receipt-col-main .rounded-slip-no {
            border: 1.5px solid #000;
            border-radius: 15px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            padding: 4px 0;
            margin: 5px 0;
            /* Margin kam kiya taaki nayi line fit ho sake */
        }

        .receipt-col-main .input-line-group {
            display: flex;
            align-items: flex-end;
            margin-bottom: 8px;
            /* Thoda kam kiya perfect fit ke liye */
            font-size: 10px;
            font-weight: bold;
        }

        .receipt-col-main .input-line-group .label {
            margin-right: 5px;
            white-space: nowrap;
        }

        .receipt-col-main .input-line-group .line {
            flex-grow: 1;
            border-bottom: 1.5px solid #000;
            height: 12px;
            display: inline-block;
            text-align: center;
            font-weight: normal;
            font-size: 16px;
            padding-bottom: 3px;
        }

        .receipt-col-main .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: auto;
            padding-bottom: 2px;
        }

        .receipt-col-main .sign-box {
            width: 45%;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
        }

        .receipt-col-main .sign-line {
            border-bottom: 1px solid #000;
            height: 14px;
            margin-bottom: 2px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        /* ---------------- SCISSOR CUT LINE ---------------- */
        .cut-line {
            width: 0;
            border-left: 1.5px dashed #000;
            position: relative;
            display: flex;
            justify-content: center;
            z-index: 2;
        }

        .cut-icon {
            position: absolute;
            top: -6px;
            background: #fff;
            font-size: 12px;
            line-height: 1;
        }

        /* ---------------- 3. SUB RECEIPT: DRIVER COPY (35% Width) ---------------- */
        .receipt-col-sub {
            width: 35%;
            padding: 4px 6px;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            z-index: 2;
        }

        .receipt-col-sub .comp-name {
            font-size: 10px;
            font-weight: 900;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }

        /* 🔥 NAYA: Company Phone Number (Driver Copy) */
        .receipt-col-sub .comp-phone {
            font-size: 7px;
            font-weight: bold;
            text-align: center;
            margin-top: 1px;
            color: #333;
        }

        .receipt-col-sub .phase-name {
            font-size: 10px;
            font-weight: 800;
            text-align: center;
            margin-top: 2px;
            text-transform: uppercase;
        }

        .receipt-col-sub .slip-type {
            font-size: 9px;
            font-weight: 900;
            text-align: center;
            text-decoration: underline;
            margin-top: 1px;
            text-transform: uppercase;
        }

        .receipt-col-sub .rounded-slip-no {
            border: 1.5px solid #000;
            border-radius: 12px;
            text-align: center;
            font-size: 9px;
            font-weight: 900;
            padding: 5px 0;
            margin: 6px 0;
            /* Margin kam kiya fit karne ke liye */
        }

        .receipt-col-sub .input-line-group {
            display: flex;
            align-items: flex-end;
            margin-bottom: 8px;
            font-size: 10px;
            font-weight: 900;
        }

        .receipt-col-sub .input-line-group .label {
            margin-right: 3px;
            white-space: nowrap;
        }

        .receipt-col-sub .input-line-group .line {
            flex-grow: 1;
            border-bottom: 1px solid #000;
            height: 10px;
            display: inline-block;
            text-align: center;
            font-weight: bold;
            font-size: 15px;
            padding-bottom: 7px;
        }

        .receipt-col-sub .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: auto;
            padding-bottom: 2px;
        }

        .receipt-col-sub .sign-box {
            width: 48%;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            white-space: nowrap;
        }

        .receipt-col-sub .sign-line {
            border-bottom: 1px solid #000;
            height: 12px;
            margin-bottom: 1px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
        }

        /* 🔥 Background colors for Slip Pills */
        .bg-red {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
            color: #fff !important;
        }

        .bg-green {
            background-color: #198754 !important;
            border-color: #198754 !important;
            color: #fff !important;
        }

        @media print {
            body {
                background: white;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="no-print" style="text-align: center; padding: 10px; background: #f0f0f0; border-bottom: 1px solid #ccc;">
        <button onclick="window.print()"
            style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #1A365D; color: white; border: none; border-radius: 5px;">Print
            Trip Slips</button>
    </div>

    <div class="a4-page">
        @foreach ($slips as $slip)
            @php
                $parts = explode('/', $slip['slip_number']);
                $prefix = $parts[0] . '/' . $parts[1] . '/';
                $number = $parts[2];
            @endphp

            <div class="trip-row">
                @if ($slip['company'] && $slip['company']->company_logo)
                    <img src="{{ asset($slip['company']->company_logo) }}" class="watermark">
                @endif

                <div class="trip-col">
                    <div class="trip-inner">
                        <div class="trip-header">TRIP NO.</div>
                        <div class="trip-prefix">{{ $prefix }}</div>
                        <div class="trip-number">{{ $number }}</div>
                    </div>
                </div>

                <div class="receipt-col-main">
                    <div class="comp-name">{{ $slip['company']->company_name ?? 'COMPANY NAME' }}</div>

                    @if (!empty($slip['company']->phone))
                        <div class="comp-phone">फ़ोन / Phone No.: {{ $slip['company']->phone }}</div>
                    @endif

                    <div class="phase-name">{{ $slip['phase'] }}</div>
                    <div class="slip-type">{{ $slip['slip_type'] }} RECEIPT SLIP</div>

                    <div class="rounded-slip-no">Slip / Trip No.: {{ $slip['slip_number'] }}</div>

                    <div class="input-line-group"><span class="label">Vehicle No.:</span><span class="line"></span>
                    </div>

                    <div class="input-line-group">
                        <span class="label">Date:</span>
                        <span class="line"
                            style="width: 35%; flex-grow: 0;">{{ \Carbon\Carbon::parse($slip['date'])->format('d-m-Y') }}</span>
                        <span class="label" style="margin-left: 10px;">Day:</span>
                        <span class="line"
                            style="flex-grow: 1;">{{ \Carbon\Carbon::parse($slip['date'])->format('l') }}</span>
                    </div>

                    <div class="input-line-group" style="align-items: center;">
                        <span class="label">Time:</span>
                        <div style="display: flex; align-items: center; margin-left: 2px;">
                            <span
                                style="border: 1.5px solid #000; width: 14px; height: 14px; display: inline-block;"></span>
                            <span
                                style="border: 1.5px solid #000; width: 14px; height: 14px; display: inline-block; margin-left: 3px;"></span>
                            <span style="margin: 0 4px; font-weight: 900;">:</span>
                            <span
                                style="border: 1.5px solid #000; width: 14px; height: 14px; display: inline-block;"></span>
                            <span
                                style="border: 1.5px solid #000; width: 14px; height: 14px; display: inline-block; margin-left: 3px;"></span>
                            <span style="margin-left: 6px; font-weight: normal; font-size: 10px;">AM / PM</span>
                        </div>
                    </div>

                    <div class="signatures">
                        <div class="sign-box">
                            <div class="sign-line">{{ $slip['pm_id'] }}</div>
                            <span>Site Project Manager Sign</span>
                        </div>
                        <div class="sign-box">
                            <div class="sign-line">{{ $slip['supervisor_id'] }}</div>
                            <span>Site Supervisor Sign</span>
                        </div>
                    </div>
                </div>

                <div class="cut-line"><span class="cut-icon">✂</span></div>

                <div class="receipt-col-sub">
                    <div class="comp-name">{{ $slip['company']->company_name ?? 'COMPANY NAME' }}</div>

                    @if (!empty($slip['company']->phone))
                        <div class="comp-phone">फ़ोन / Phone No.: {{ $slip['company']->phone }}</div>
                    @endif

                    <div class="phase-name">{{ $slip['phase'] }}</div>
                    <div class="slip-type">{{ $slip['slip_type'] }} RECEIPT SLIP</div>

                    <div class="rounded-slip-no">Slip / Trip No.: {{ $slip['slip_number'] }}</div>

                    <div class="input-line-group"><span class="label">Vehicle No.:</span><span class="line"></span>
                    </div>

                    <div class="input-line-group">
                        <span class="label">Date:</span>
                        <span class="line"
                            style="width: 35%; flex-grow: 0;">{{ \Carbon\Carbon::parse($slip['date'])->format('d-m-Y') }}</span>
                        <span class="label" style="margin-left: 6px;">Day:</span>
                        <span class="line"
                            style="flex-grow: 1;">{{ \Carbon\Carbon::parse($slip['date'])->format('l') }}</span>
                    </div>

                    <div class="input-line-group" style="align-items: center;">
                        <span class="label">Time:</span>
                        <div style="display: flex; align-items: center; margin-left: 2px;">
                            <span
                                style="border: 1px solid #000; width: 14px; height: 14px; display: inline-block;"></span>
                            <span
                                style="border: 1px solid #000; width: 14px; height: 14px; display: inline-block; margin-left: 2px;"></span>
                            <span style="margin: 0 3px; font-weight: 900;">:</span>
                            <span
                                style="border: 1px solid #000; width: 14px; height: 14px; display: inline-block;"></span>
                            <span
                                style="border: 1px solid #000; width: 14px; height: 14px; display: inline-block; margin-left: 2px;"></span>
                            <span style="margin-left: 4px; font-weight: normal; font-size: 8px;">AM / PM</span>
                        </div>
                    </div>

                    <div class="signatures">
                        <div class="sign-box">
                            <div class="sign-line">{{ $slip['pm_id'] }}</div>
                            <span>Site Project Manager Sign</span>
                        </div>
                        <div class="sign-box">
                            <div class="sign-line">{{ $slip['supervisor_id'] }}</div>
                            <span>Site Supervisor Sign</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</body>

</html>
