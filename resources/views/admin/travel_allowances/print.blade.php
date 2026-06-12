<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TA_Bill_{{ $ta->id }}</title>
     <link rel="shortcut icon" href="{{ asset('uploads/harihomes1-fevicon.png') }}" type="image/x-icon" id="dynamicFavicon">
 <style>
        /* 🌟 HIGH-FIDELITY PRINT ARCHITECTURE 🌟 */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            @page {
                size: A4;
                margin: 10mm; /* Margin thoda kam kiya */
            }
            body {
                background-color: #fff !important; /* 🔥 Print me strictly white background */
                margin: 0;
                padding: 0;
            }
            .no-print { display: none !important; }
            .print-container { 
                box-shadow: none !important; 
                padding: 20px !important; 
                background: #fff !important; 
                border: 1px solid #000 !important; /* 🔥 Print me bhi solid black border */
                min-height: auto !important; /* 🔥 Extra space khatam */
            }
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            color: #000;
        }

        .print-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            position: relative; 
            border: 1px solid #000; /* 🔥 Screen par bhi solid black border */
            /* min-height: 800px; -> Ise hata diya gaya hai taaki extra space na aaye */
        }

        /* 🔥 DYNAMIC LOGO WATERMARK 🔥 */
        .print-watermark-img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 450px; 
            opacity: 0.08 !important; 
            z-index: 0;
            pointer-events: none;
        }

        .bill-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-decoration: underline;
            margin: 15px 0 5px 0;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
        }

        .bill-serial {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        /* Modern Tabular Details Form */
        .ta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px; /* Niche ka space kam kiya */
            position: relative;
            z-index: 1;
        }

        .ta-table td {
            padding: 10px 12px;
            border: 1px solid #000;
            font-size: 13px;
            vertical-align: middle;
        }

        .label-cell {
            background-color: #f8f9fa !important;
            font-weight: bold;
            width: 25%;
            text-transform: uppercase;
            font-size: 12px;
        }

        .value-cell { width: 25%; }
        .full-row-value { font-weight: 500; }

        /* 🔥 UPDATED SIGNATURE LAYOUT 🔥 */
        .signature-section {
            margin-top: 40px; /* 🔥 Signature ke upar ka extra space kam kiya */
            margin-bottom: 10px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .sig-box {
            width: 40%;
            display: inline-block;
            vertical-align: top;
            text-align: center; 
        }

        .sig-name {
            font-size: 14px;
            font-weight: bold;
            color: #000;
            margin-bottom: 5px; 
        }

        .sig-line {
            border-top: 1px dashed #000;
            margin-bottom: 5px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .sig-title {
            font-size: 13px;
            font-weight: bold;
        }

        .print-btn-bar {
            max-width: 800px;
            margin: 0 auto 15px auto;
            text-align: right;
        }
        .btn-print {
            background: #1A365D;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .bill-no
        {
            color: #fff;
            background-color: rgb(182, 42, 42);
            padding: 5px;
            font-weight: 600;
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <div class="print-btn-bar no-print">
        <button class="btn-print" onclick="window.print();"><i class="fas fa-print"></i> Print Document</button>
    </div>

    <div class="print-container">

       @if($ta->status === 'rejected')
            <div class="print-watermark-img" style="color: rgba(255, 0, 0, 0.2) !important; font-size: 110px; font-weight: 900; text-align: center; font-family: 'Arial Black', sans-serif; letter-spacing: 15px; border: 10px solid rgba(255, 0, 0, 0.2); padding: 20px; border-radius: 20px; transform: translate(-50%, -50%) rotate(-45deg);">
                REJECTED
            </div>
        @elseif ($company && !empty($company->company_logo))
            <img src="{{ asset($company->company_logo) }}" class="print-watermark-img" alt="Watermark">
        @endif

        <x-print-header :company="$company" :branch="$branch" />

        <div class="bill-title">TRAVELLING EXPENSE BILL</div>
        <div class="bill-serial"><span class="bill-no">BILL SR. NO.: TA-{{ str_pad($ta->id, 5, '0', STR_PAD_LEFT) }}</span></div>

        <table class="ta-table">
            <tr>
                <td class="label-cell">Date</td>
                <td class="value-cell">{{ \Carbon\Carbon::parse($ta->ta_date)->format('d/m/Y') }}</td>
                <td class="label-cell">Vehicle No.</td>
                <td class="value-cell">{{ $ta->vehicle_no ?? '.......' }}</td>
            </tr>
            <tr>
                <td class="label-cell">Employee Name</td>
                <td class="value-cell" style="font-weight: bold;">{{ $ta->employee->full_name ?? 'N/A' }}</td>
                <td class="label-cell">Employee Code</td>
                <td class="value-cell">{{ $ta->employee->member_id ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label-cell">Purpose of Work</td>
                <td class="value-cell full-row-value" colspan="3">{!! nl2br(e($ta->purpose ?? '.......')) !!}</td>
            </tr>
            <tr>
                <td class="label-cell">Destination</td>
                <td class="value-cell full-row-value" colspan="3">{!! nl2br(e($ta->destination ?? '.......')) !!}</td>
            </tr>
            <tr>
                <td class="label-cell">Distance (KMs)</td>
                <td class="value-cell">{{ $ta->distance_km ? $ta->distance_km . ' Km' : '.......' }}</td>
                <td class="label-cell">Fuel (Litre)</td>
                <td class="value-cell">{{ $ta->fuel_litre ? $ta->fuel_litre . ' Ltr' : '.......' }}</td>
            </tr>
            <tr>
                <td class="label-cell">In Time</td>
                <td class="value-cell">{{ $ta->in_time ?? '.......' }}</td>
                <td class="label-cell">Out Time</td>
                <td class="value-cell">{{ $ta->out_time ?? '.......' }}</td>
            </tr>
            <tr style="background-color: #f1f3f5 !important;">
                <td class="label-cell" style="color: #000; font-size: 14px;">Total Amount</td>
                <td class="value-cell full-row-value" colspan="3"
                    style="font-size: 15px; font-weight: bold; color: #1A365D;">
                    ₹{{ number_format($ta->amount, 2) }}
                </td>
            </tr>
            @if ($ta->remarks)
                <tr>
                    <td class="label-cell">Approver Remarks</td>
                    <td class="value-cell text-muted" colspan="3" style="font-style: italic;">{{ $ta->remarks }}
                    </td>
                </tr>
            @endif
        </table>

        <div class="signature-section">
            <div class="sig-box" style="float: left;">
                <div class="sig-name">{{ $ta->employee->full_name ?? '' }} ({{ $ta->employee->member_id ?? '' }})
                </div>
                <div class="sig-line"></div>
                <div class="sig-title">SIGNATURE OF EMPLOYEE</div>
            </div>

            <div class="sig-box" style="float: right;">
                <div class="sig-name">
                    @if($ta->status === 'active')
                        @if($ta->approver)
                            {{ $ta->approver->full_name }} ({{ $ta->approver->member_id }})
                        @else
                            SUPER ADMIN (Management)
                        @endif
                    @elseif($ta->status === 'rejected')
                        <span style="color: #dc3545; font-weight: bold;">REJECTED</span>
                    @else
                        <span style="color: #999; font-style: italic; font-weight: normal;">Pending Approval</span>
                    @endif
                </div>
                <div class="sig-line"></div>
                <div class="sig-title">SIGNATURE OF APPROVER</div>
            </div>

            <div style="clear: both;"></div>
        </div>

    </div>

</body>

</html>
