<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <!-- 🔥 TITLE WITH NAME, ID AND DATE 🔥 -->
    <title>
        {{ str_replace(' ', '_', $ta->employee->full_name ?? 'Employee') }}_{{ $ta->employee->member_id ?? 'ID' }}_TA_{{ \Carbon\Carbon::parse($ta->ta_date)->format('d-m-Y') }}
    </title>

    <link rel="shortcut icon" href="{{ asset('uploads/harihomes1-fevicon.png') }}" type="image/x-icon" id="dynamicFavicon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* 🌟 HIGH-FIDELITY PRINT ARCHITECTURE 🌟 */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            @page {
                size: A4;
                margin: 5mm;
            }

            body {
                background-color: #fff !important;
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .print-container {
                box-shadow: none !important;
                padding: 15px !important;
                background: #fff !important;
                border: 1px solid #000 !important;
                min-height: auto !important;
            }
        }

        /* 🌟 NORMAL SCREEN CSS 🌟 */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            color: #000;
        }

        /* 🔥 TOGGLE SWITCH CSS 🔥 */
        .switch {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 20px;
            vertical-align: middle;
            margin-left: 10px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 20px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 14px;
            width: 14px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #28a745;
        }

        input:checked+.slider:before {
            transform: translateX(20px);
        }

        .toggle-wrap {
            display: inline-flex;
            align-items: center;
            background: #fff;
            border: 1px solid #ccc;
            padding: 6px 15px;
            border-radius: 5px;
            margin-right: 15px;
        }

        .print-btn-bar {
            max-width: 800px;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .btn-print {
            background: #1A365D;
            color: #fff;
            border: none;
            padding: 8px 20px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .btn-print:hover {
            background: #2A4365;
        }

        .print-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            position: relative;
            border: 1px solid #ccc;
        }

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

        .bill-no {
            color: #fff;
            background-color: rgb(182, 42, 42);
            padding: 5px;
            font-weight: 600;
            border-radius: 5px;
        }

        .ta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .ta-table td {
            padding: 5px 8px;
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

        .value-cell {
            width: 25%;
        }

        .full-row-value {
            font-weight: 500;
        }

        .signature-section {
            margin-top: 15px;
            margin-bottom: 5px;
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
    </style>
</head>

<body>

    <div class="print-btn-bar no-print">
        <!-- 🔥 TOGGLE BUTTON 🔥 -->
        @if ($ta->proof_file)
            <div class="toggle-wrap shadow-sm">
                <span style="font-size: 13px; font-weight: bold; color: #1A365D;">Include Proofs in Print</span>
                <label class="switch">
                    <input type="checkbox" id="toggleProof" onchange="toggleProofVisibility()">
                    <span class="slider"></span>
                </label>
            </div>
        @endif

        <button class="btn-print" onclick="window.print();"><i class="fas fa-print"></i> Print Document</button>
    </div>

    <div class="print-container">

        @if ($ta->status === 'rejected')
            <div class="print-watermark-img"
                style="color: rgba(255, 0, 0, 0.2) !important; font-size: 110px; font-weight: 900; text-align: center; font-family: 'Arial Black', sans-serif; letter-spacing: 15px; border: 10px solid rgba(255, 0, 0, 0.2); padding: 20px; border-radius: 20px; transform: translate(-50%, -50%) rotate(-45deg);">
                REJECTED
            </div>
        @elseif ($company && !empty($company->company_logo))
            <img src="{{ asset($company->company_logo) }}" class="print-watermark-img" alt="Watermark">
        @endif

        <x-print-header :company="$company" :branch="$branch" />

        <div class="bill-title">TRAVELLING EXPENSE BILL</div>
        <div class="bill-serial"><span class="bill-no">BILL SR. NO.:
                TA-{{ str_pad($ta->id, 5, '0', STR_PAD_LEFT) }}</span></div>

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
                <td class="label-cell">Person Info</td>
                <td class="value-cell">
                    <b>{{ $ta->person_name ?? 'Self' }}</b>
                    @if ($ta->person_number)
                        <br><span style="font-size: 11px;">Ph: {{ $ta->person_number }}</span>
                    @endif
                </td>
                <td class="label-cell">No. of Persons</td>
                <td class="value-cell">{{ $ta->number_of_persons ?? 1 }} Person(s)</td>
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
                <td class="label-cell" style="color: #000; font-size: 13px;">Amount Details</td>
                <td class="value-cell full-row-value" colspan="3" style="font-size: 14px;">
                    Requested Amount: <span
                        style="font-weight: bold; color: #1A365D;">₹{{ number_format($ta->amount, 2) }}</span>
                    @if ($ta->status === 'active' && $ta->approved_amount)
                        &nbsp;&nbsp; | &nbsp;&nbsp;
                        Approved Amount: <span
                            style="font-weight: bold; color: #28a745;">₹{{ number_format($ta->approved_amount, 2) }}</span>
                    @endif
                </td>
            </tr>
            @if ($ta->remarks)
                <tr>
                    <td class="label-cell" style="color: red !important;">
                        {{ $ta->status === 'rejected' ? "Rejecter's Remark" : "Approver's Remark" }}</td>
                    <td class="value-cell" colspan="3"
                        style="font-style: italic; color: green !important; font-weight: bold;">{{ $ta->remarks }}
                    </td>
                </tr>
            @endif

            <!-- 🔥 TEXT ROW FOR ATTACHMENT (Visible when toggle is OFF) 🔥 -->
            @if ($ta->proof_file)
                <tr id="attachmentTextRow" style="background-color: #f8f9fa !important;">
                    <td class="label-cell" style="color: #1A365D !important;">Attachments</td>
                    <td class="value-cell" colspan="3" style="font-weight: bold; color: #1A365D;">
                        <i class="fas fa-paperclip"></i> Proof Attachment(s) Available in System
                    </td>
                </tr>
            @endif
        </table>

        <!-- 🔥 MULTIPLE PROOFS VIEWER (Visible when toggle is ON) 🔥 -->
        @php
            $proofs = [];
            if ($ta->proof_file) {
                $decoded = json_decode($ta->proof_file, true);
                $proofs = is_array($decoded) ? $decoded : [$ta->proof_file];
            }
        @endphp

        @if (count($proofs) > 0)
            <div id="proofImagesContainer"
                style="display: none; margin: 10px 0; padding: 5px; border: 1px solid #000; text-align: center; page-break-inside: avoid; background: #fff;">
                <div style="font-size: 13px; font-weight: bold; margin-bottom: 5px; text-decoration: underline;">
                    ATTACHED PROOF DOCUMENT(S)</div>
                <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 10px;">
                    @foreach ($proofs as $proofPath)
                        @if (\Illuminate\Support\Str::endsWith(strtolower($proofPath), 'pdf'))
                            <iframe src="{{ asset($proofPath) }}"
                                style="width: 48%; height: 220px; border: 1px solid #ccc;"></iframe>
                        @else
                            <img src="{{ asset($proofPath) }}"
                                style="max-width: 48%; height: auto; max-height: 220px; object-fit: contain; border: 1px solid #ccc;">
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        <div class="signature-section">
            <div class="sig-box" style="float: left;">
                <div class="sig-name">{{ $ta->employee->full_name ?? '' }} ({{ $ta->employee->member_id ?? '' }})
                </div>
                <div class="sig-line"></div>
                <div class="sig-title">SIGNATURE OF EMPLOYEE</div>
            </div>

            <div class="sig-box" style="float: right;">
                <div class="sig-name">
                    @if ($ta->status === 'active' || $ta->status === 'rejected')
                        @if ($ta->approver)
                            {{ $ta->approver->full_name }} ({{ $ta->approver->member_id }})<br>
                            <span
                                style="font-size: 11px; font-weight: normal;">{{ $ta->approver_role ?? 'HR Management' }}</span>
                        @else
                            <span
                                style="font-size: 12px; font-weight: bold;">{{ $ta->approver_role ?? 'HR Management' }}</span>
                        @endif
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

    <!-- 🔥 TOGGLE LOGIC SCRIPT 🔥 -->
    <script>
        function toggleProofVisibility() {
            var toggle = document.getElementById('toggleProof');
            var textRow = document.getElementById('attachmentTextRow');
            var imagesContainer = document.getElementById('proofImagesContainer');

            if (toggle && toggle.checked) {
                // Agar ON hai, toh Text hide karo aur Images show karo
                if (textRow) textRow.style.display = 'none';
                if (imagesContainer) imagesContainer.style.display = 'block';
            } else {
                // Agar OFF hai, toh Text show karo aur Images hide karo
                if (textRow) textRow.style.display = 'table-row';
                if (imagesContainer) imagesContainer.style.display = 'none';
            }
        }
    </script>
</body>

</html>
