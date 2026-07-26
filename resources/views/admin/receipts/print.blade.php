<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Print | {{ $receipt->receipt_no }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        /* 🌟 GLOBAL RESET 🌟 */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #525659;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 0;
        }

        /* 🌟 STRICT A4 PAGE CONTAINER 🌟 */
        .a4-page {
            width: 210mm;
            height: 297mm;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 0 5mm;
        }

        /* 🌟 RECEIPT CONTAINER (138mm + Overflow Hidden = No Overlap) 🌟 */
        .receipt-wrapper {
            width: 100%;
            height: 138mm !important;
            /* Safe height for ALL printers */
            border: 1.5px solid #7c8e87;
            border-radius: 6px;
            padding: 3mm 4mm;
            display: flex;
            flex-direction: column;
            position: relative;
            background: #fff;
            overflow: hidden !important;
            /* 🔥 CRITICAL: Prevents any content from escaping the box 🔥 */
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            z-index: 0;
            width: 45%;
            pointer-events: none;
        }

        /* Flex architecture ensures even spreading of boxes */
        .receipt-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            gap: 3px;
        }

        /* Colors */
        .bg-green {
            background-color: #0e5d36 !important;
            color: #fff !important;
        }

        .text-green {
            color: #0e5d36 !important;
        }

        .bg-blue {
            background-color: #082142 !important;
            color: #fff !important;
        }

        .border-green {
            border: 1px solid #0e5d36 !important;
        }

        /* Typography */
        .fs-xxs {
            font-size: 10px;
            line-height: 1.1;
        }

        .fs-xs {
            font-size: 7.5px;
            line-height: 1.1;
        }

        .fs-sm {
            font-size: 8px;
            line-height: 1.1;
        }

        .fs-md {
            font-size: 10px;
            line-height: 1.1;
        }

        .fw-bold {
            font-weight: bold;
        }

        /* Custom Box Structure */
        .c-box {
            border: 1px solid #aebac6;
            border-radius: 3px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .c-box-head {
            padding: 2px 6px;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .c-box-body {
            padding: 3px 6px;
            display: flex;
            flex-direction: column;
            justify-content: space-evenly;
            flex-grow: 1;
            gap: 2px;
        }

        /* Label-Value Rows */
        .r-row {
            display: flex;
            align-items: flex-end;
            font-size: 8px;
            margin: 0;
        }

        .r-label {
            font-weight: bold;
            flex-shrink: 0;
        }

        .r-colon {
            margin: 0 4px;
            font-weight: bold;
        }

        .r-val {
            flex-grow: 1;
            border-bottom: 0.5px solid #ccc;
            padding-left: 2px;
            padding-bottom: 1px;
            font-weight: bold;
            word-wrap: break-word;
            line-height: 1.1;
        }

        /* Checkboxes */
        .cb-wrap {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            margin-right: 6px;
            font-weight: bold;
            font-size: 8px;
        }

        .cb {
            display: inline-block;
            width: 8px;
            height: 8px;
            border: 1px solid #333;
            text-align: center;
            line-height: 7px;
            font-size: 7px;
        }

        /* 🔥 Layout Sections Constraints 🔥 */
        .header-section {
            flex: 0 0 auto;
        }

        .title-section {
            flex: 0 0 auto;
            text-align: center;
            margin: 2px 0;
        }

        .main-columns-section {
            display: flex;
            flex-direction: row;
            gap: 6px;
            flex: 1 1 auto;
            /* Fills remaining space */
            min-height: 0;
            /* CRITICAL: Prevents internal boxes from forcing expansion */
        }

        .col-left,
        .col-right {
            width: 50%;
            display: flex;
            flex-direction: column;
            gap: 4px;
            height: 100%;
            min-height: 0;
            /* CRITICAL */
        }

        .bottom-section {
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        /* Scissors Divider */
        .cut-line {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 5mm;
            width: 100%;
            position: relative;
        }

        .cut-line::before {
            content: "";
            position: absolute;
            width: 100%;
            border-top: 1px dashed #777;
            top: 50%;
            z-index: 1;
        }

        .cut-line i {
            background: #fff;
            padding: 0 6px;
            color: #555;
            z-index: 2;
            font-size: 11px;
        }

        .btn-print {
            background: #007bff;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-bottom: 10px;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 7.5mm;
            }

            body {
                background: #fff;
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .a4-page {
                box-shadow: none;
                width: 100%;
                height: 100%;
                justify-content: center;
                padding: 0;
                margin: 0;
            }

            .btn-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    @php
        $particulars = is_array($receipt->amount_details)
            ? collect($receipt->amount_details)->pluck('particular')->toArray()
            : [];
        $isAdmission = in_array('Admission Fee', $particulars);

        $title = $isAdmission ? 'ADMISSION FEE RECEIPT' : 'RECEIPT VOUCHER';
        $showNonRefundable = $isAdmission;

        $tcText = $isAdmission ? 'Admission amount is' : 'Enrollment amount is';

        function convertNumberToWords($num)
        {
            $num = (int) $num;
            if ($num == 0) {
                return '';
            }
            $words = [
                0 => '',
                1 => 'One',
                2 => 'Two',
                3 => 'Three',
                4 => 'Four',
                5 => 'Five',
                6 => 'Six',
                7 => 'Seven',
                8 => 'Eight',
                9 => 'Nine',
                10 => 'Ten',
                11 => 'Eleven',
                12 => 'Twelve',
                13 => 'Thirteen',
                14 => 'Fourteen',
                15 => 'Fifteen',
                16 => 'Sixteen',
                17 => 'Seventeen',
                18 => 'Eighteen',
                19 => 'Nineteen',
                20 => 'Twenty',
                30 => 'Thirty',
                40 => 'Forty',
                50 => 'Fifty',
                60 => 'Sixty',
                70 => 'Seventy',
                80 => 'Eighty',
                90 => 'Ninety',
            ];
            $result = '';
            if ($num >= 10000000) {
                $result .= convertNumberToWords((int) ($num / 10000000)) . ' Crore ';
                $num %= 10000000;
            }
            if ($num >= 100000) {
                $result .= convertNumberToWords((int) ($num / 100000)) . ' Lakh ';
                $num %= 100000;
            }
            if ($num >= 1000) {
                $result .= convertNumberToWords((int) ($num / 1000)) . ' Thousand ';
                $num %= 1000;
            }
            if ($num >= 100) {
                $result .= convertNumberToWords((int) ($num / 100)) . ' Hundred ';
                $num %= 100;
            }
            if ($num > 0) {
                if ($num < 20) {
                    $result .= $words[$num] . ' ';
                } else {
                    $result .= $words[(int) ($num / 10) * 10] . ' ';
                    if ($num % 10 > 0) {
                        $result .= $words[$num % 10] . ' ';
                    }
                }
            }
            return trim($result);
        }

        $amt = $receipt->amount_received;
        $amountInWords = $amt > 0 ? convertNumberToWords($amt) . ' Rupees Only' : 'Zero Rupees';
        $rcptDate = \Carbon\Carbon::parse($receipt->receipt_date);
    @endphp

    <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> Print Professional A4</button>

    <div class="a4-page">
        @for ($i = 0; $i < 2; $i++)
            <div class="receipt-wrapper">

                @if ($receipt->company && !empty($receipt->company->company_logo))
                    <img src="{{ asset($receipt->company->company_logo) }}" class="watermark" alt="Watermark">
                @endif

                <div class="receipt-content">

                    <!-- 1. HEADER -->
                    <div class="header-section">
                        <x-print-header :company="$receipt->company" :branch="$receipt->branch" />
                    </div>

                    <!-- 2. TITLE SECTION -->
                    <div class="title-section">
                        <div class="bg-blue"
                            style="display:inline-block; padding:3px 30px; font-size:11.5px; font-weight:900; border-radius: 4px; border-left: 10px solid #0e5d36; border-right: 10px solid #0e5d36; letter-spacing: 1.5px;">
                            {{ $title }}
                        </div>
                        @if ($showNonRefundable)
                            <div class="text-green fw-bold fs-xxs" style="margin-top: 1px;">(NON-REFUNDABLE)</div>
                        @endif
                    </div>

                    <!-- 3. MAIN COLUMNS (STRICTLY CONSTRAINED) -->
                    <div class="main-columns-section">

                        <!-- LEFT COLUMN -->
                        <div class="col-left">

                            <!-- Box 1: Basic Info -->
                            <div class="c-box" style="flex: 1 1 auto;">
                                <div class="c-box-body">
                                    <div class="r-row">
                                        <div class="r-label" style="width:75px;"><i class="far fa-calendar-alt"></i>
                                            Receipt Date</div>
                                        <div class="r-colon">:</div>
                                        <div style="display: flex; gap: 4px;">
                                            <div
                                                style="border: 1px solid #777; padding: 1px 4px; text-align: center; border-radius: 2px;">
                                                {{ $rcptDate->format('d') }}</div> /
                                            <div
                                                style="border: 1px solid #777; padding: 1px 4px; text-align: center; border-radius: 2px;">
                                                {{ $rcptDate->format('m') }}</div> /
                                            <div
                                                style="border: 1px solid #777; padding: 1px 6px; text-align: center; border-radius: 2px;">
                                                {{ $rcptDate->format('Y') }}</div>
                                        </div>
                                    </div>
                                    <div class="r-row">
                                        <div class="r-label" style="width:75px;"><i class="fas fa-file-invoice"></i>
                                            Receipt No.</div>
                                        <div class="r-colon">:</div>
                                        <div class="r-val" style="border:none; color:#d93025; font-size: 9.5px;">
                                            {{ $receipt->receipt_no }}</div>
                                    </div>
                                    <div class="r-row">
                                        <div class="r-label" style="width:75px;"><i class="fas fa-id-badge"></i>
                                            Customer ID</div>
                                        <div class="r-colon">:</div>
                                        <div>
                                            <div
                                                style="border: 1px solid #777; padding: 1px 6px; border-radius: 2px; font-weight: bold;">
                                                {{ $receipt->customer_identification_no ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="r-row">
                                        <div class="r-label" style="width:75px;"><i class="fas fa-book"></i> Passbook
                                            No.</div>
                                        <div class="r-colon">:</div>
                                        <div class="r-val" style="border:none; font-size: 9px;">
                                            {{ $receipt->passbook_no ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Box 2: Project Details -->
                            <div class="c-box border-green" style="flex: 1 1 auto;">
                                <div class="c-box-head bg-green"><i class="fas fa-building"></i> PROJECT DETAILS</div>
                                <div class="c-box-body">
                                    <div class="r-row">
                                        <div class="r-label" style="width:85px;">Project Name</div>
                                        <div class="r-colon">:</div>
                                        <div class="r-val fs-sm">{{ $receipt->project_name ?? '-' }}</div>
                                    </div>
                                    <div class="r-row">
                                        <div class="r-label" style="width:85px;">Phase</div>
                                        <div class="r-colon">:</div>
                                        <div class="r-val fs-sm">{{ $receipt->phase_id ?? '-' }}</div>
                                    </div>
                                    <div class="r-row">
                                        <div class="r-label" style="width:85px;">Unit Type</div>
                                        <div class="r-colon">:</div>
                                        <div class="r-val" style="border:none;">
                                            @foreach (['Plot', 'Villa', 'Flat'] as $type)
                                                <span class="cb-wrap"><span
                                                        class="cb">{!! $receipt->property_type === $type ? '&#10004;' : '' !!}</span>{{ $type }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="r-row">
                                        <div class="r-label" style="width:85px;">Unit No.</div>
                                        <div class="r-colon">:</div>
                                        <div class="r-val fs-sm">{{ $receipt->unit_no ?? '-' }}</div>
                                    </div>
                                    <div class="r-row">
                                        <div class="r-label" style="width:85px;">Plot Size / Type</div>
                                        <div class="r-colon">:</div>
                                        <div class="r-val fs-sm">{{ $receipt->area_sqft ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- RIGHT COLUMN -->
                        <div class="col-right">

                            <!-- Box 3: Payment Information -->
                            <div class="c-box border-green" style="flex: 0 0 auto;">
                                <div class="c-box-head bg-green"><i class="fas fa-money-check-alt"></i> PAYMENT
                                    INFORMATION</div>
                                <div class="c-box-body">
                                    @if (in_array($receipt->payment_mode, ['UPI', 'NEFT', 'RTGS', 'IMPS', 'Other']))
                                        <div class="r-row">
                                            <div class="r-label" style="width:120px;">Transaction / UTR No.</div>
                                            <div class="r-colon">:</div>
                                            <div class="r-val fs-sm">
                                                {{ $receipt->utr_no ?? ($receipt->transaction_no ?? '-') }}</div>
                                        </div>
                                        <div class="r-row">
                                            <div class="r-label" style="width:120px;">Bank Name</div>
                                            <div class="r-colon">:</div>
                                            <div class="r-val fs-sm">{{ $receipt->received_bank_name ?? '-' }}</div>
                                        </div>
                                        <div class="r-row">
                                            <div class="r-label" style="width:120px;">Branch</div>
                                            <div class="r-colon">:</div>
                                            <div class="r-val fs-sm">-</div>
                                        </div>
                                        <div class="r-row">
                                            <div class="r-label" style="width:120px;">Transaction Date</div>
                                            <div class="r-colon">:</div>
                                            <div class="r-val fs-sm">
                                                {{ $receipt->transaction_date ? \Carbon\Carbon::parse($receipt->transaction_date)->format('d / m / Y') : '- / - / ----' }}
                                            </div>
                                        </div>
                                    @elseif($receipt->payment_mode == 'Cheque')
                                        <div class="r-row">
                                            <div class="r-label" style="width:120px;">Cheque No.</div>
                                            <div class="r-colon">:</div>
                                            <div class="r-val fs-sm">{{ $receipt->cheque_no ?? '-' }}</div>
                                        </div>
                                        <div class="r-row">
                                            <div class="r-label" style="width:120px;">Bank Name</div>
                                            <div class="r-colon">:</div>
                                            <div class="r-val fs-sm">{{ $receipt->bank_name ?? '-' }}</div>
                                        </div>
                                        <div class="r-row">
                                            <div class="r-label" style="width:120px;">Branch</div>
                                            <div class="r-colon">:</div>
                                            <div class="r-val fs-sm">-</div>
                                        </div>
                                        <div class="r-row">
                                            <div class="r-label" style="width:120px;">Date of Cheque</div>
                                            <div class="r-colon">:</div>
                                            <div class="r-val fs-sm">
                                                {{ $receipt->date_of_cheque ? \Carbon\Carbon::parse($receipt->date_of_cheque)->format('d / m / Y') : '- / - / ----' }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="r-row">
                                            <div class="r-label" style="width:120px;">Transaction / UTR No.</div>
                                            <div class="r-colon">:</div>
                                            <div class="r-val fs-sm">-</div>
                                        </div>
                                        <div class="r-row">
                                            <div class="r-label" style="width:120px;">Bank Name</div>
                                            <div class="r-colon">:</div>
                                            <div class="r-val fs-sm">-</div>
                                        </div>
                                        <div class="r-row">
                                            <div class="r-label" style="width:120px;">Branch</div>
                                            <div class="r-colon">:</div>
                                            <div class="r-val fs-sm">-</div>
                                        </div>
                                        <div class="r-row">
                                            <div class="r-label" style="width:120px;">Instrument Date</div>
                                            <div class="r-colon">:</div>
                                            <div class="r-val fs-sm">- / - / ----</div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Box 4: Payment Summary -->
                            <div class="c-box border-green" style="flex: 1 1 auto;">
                                <div class="c-box-head bg-green"><i class="fas fa-rupee-sign"></i> PAYMENT SUMMARY
                                </div>
                                <div class="c-box-body" style="justify-content: center; gap: 4px;">
                                    <div>
                                        <div class="fs-xxs fw-bold mb-1">Received with thanks from Shri / Smt. / M/s.
                                        </div>
                                        <div class="fw-bold fs-sm"
                                            style="border-bottom: 1.5px solid #888; padding-bottom: 2px; color: #111;">
                                            {{ strtoupper($receipt->customer_name) }}</div>
                                    </div>

                                    <div style="display: flex; gap: 6px; align-items: stretch; margin-top: 4px;">
                                        <div style="width: 140px; display: flex; flex-direction: column;">
                                            <div class="bg-blue"
                                                style="color:white; font-size:7px; font-weight:bold; padding:2px; border-radius:3px; margin-bottom: 3px; text-align: center;">
                                                {{ count($particulars) > 0 ? implode(', ', $particulars) : 'Advance / Other' }}
                                            </div>
                                            <div
                                                style="display: flex; border: 2px solid #0e5d36; border-radius: 4px; align-items: stretch; flex-grow: 1;">
                                                <div class="bg-green"
                                                    style="padding: 2px 6px; font-size: 13px; font-weight: bold; display: flex; align-items: center;">
                                                    ₹</div>
                                                <div
                                                    style="text-align: center; flex-grow: 1; font-weight: bold; font-size: 13px; display: flex; align-items: center; justify-content: center; color: #000;">
                                                    {{ number_format($receipt->amount_received, 2) }}</div>
                                            </div>
                                        </div>

                                        <div
                                            style="flex-grow: 1; border: 1.5px solid #aebac6; border-radius: 4px; position: relative; padding: 4px; display: flex; align-items: center; justify-content: center; background: #fafafa;">
                                            <span class="text-green fw-bold fs-xxs"
                                                style="position: absolute; top: -5px; left: 6px; background: #fafafa; padding: 0 4px;">Amount
                                                in Words</span>
                                            <div
                                                style="font-size: 7.5px; font-weight: bold; text-align: center; color: #222; line-height: 1.3;">
                                                {{ $amountInWords }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Box 5: Payment Mode -->
                            <div class="c-box border-green" style="flex: 0 0 auto;">
                                <div class="c-box-head bg-green"><i class="fas fa-check-circle"></i> PAYMENT MODE (✓
                                    One)</div>
                                <div class="c-box-body"
                                    style="flex-direction: row; flex-wrap: wrap; padding: 4px 6px; gap: 0;">
                                    @foreach (['Cash', 'UPI', 'Cheque', 'NEFT', 'RTGS', 'IMPS', 'Other'] as $mode)
                                        <div class="cb-wrap" style="width: 30%; margin-bottom: 2px;">
                                            <span class="cb">{!! $receipt->payment_mode === $mode ? '&#10004;' : '' !!}</span>{{ $mode }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. BOTTOM SECTION (Company Use, Icons, Footer) -->
                    <div class="bottom-section">

                        <!-- Company Use & Signature Block -->
                        <div class="c-box border-green" style="display: flex; flex-direction: row;">
                            <div
                                style="width: 70%; padding: 4px 8px; display: flex; flex-direction: column; justify-content: space-evenly;">
                                <div class="bg-green"
                                    style="display:inline-block; padding: 2px 8px; font-size: 7px; font-weight: bold; border-radius: 2px; margin-bottom: 4px; width: max-content;">
                                    COMPANY USE ONLY</div>
                                <div style="display: flex; gap: 15px; margin-bottom: 3px;">
                                    <div class="r-row" style="width: 50%;">
                                        <div class="r-label" style="width:75px;">Received By</div>
                                        <div class="r-colon">:</div>
                                        <div class="r-val fs-sm">{{ $receipt->receivedByEmployee->full_name ?? '-' }}
                                        </div>
                                    </div>
                                    <div class="r-row" style="width: 50%;">
                                        <div class="r-label" style="width:85px;">Employee Code</div>
                                        <div class="r-colon">:</div>
                                        <div class="r-val fs-sm">{{ $receipt->received_by_emp_code ?? '-' }}</div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 15px;">
                                    <div class="r-row" style="width: 50%;">
                                        <div class="r-label" style="width:75px;">Department</div>
                                        <div class="r-colon">:</div>
                                        <div class="r-val fs-sm">{{ $receipt->received_by_department ?? '-' }}</div>
                                    </div>
                                    <div class="r-row" style="width: 50%;">
                                        <div class="r-label" style="width:85px;">Remarks</div>
                                        <div class="r-colon">:</div>
                                        <div class="r-val fs-sm">{{ $receipt->remarks ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Seal and Signatory -->
                            <div
                                style="width: 30%; display: flex; flex-direction: column; align-items: center; justify-content: center; border-left: 1px solid #aebac6; padding: 3px; background: #fafafa;">
                                <div class="fw-bold text-center mb-1" style="font-size: 7px;">FOR
                                    {{ $receipt->company ? strtoupper($receipt->company->company_name) : 'COMPANY NAME' }}
                                </div>
                                <div
                                    style="width: 28px; height: 28px; border: 1px solid #444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 4px; font-weight: bold; color: #555; text-align: center; margin: 2px 0;">
                                    Company<br>Seal</div>
                                <div class="fw-bold text-center mt-1" style="font-size: 6px;">
                                    {{ $receipt->authorizedCeo->full_name ?? 'Signatory Name' }}
                                    ({{ $receipt->authorizedCeo->ceo_id ?? 'ID' }})</div>
                                <div class="fw-bold"
                                    style="border-top: 1px solid #333; width: 85%; padding-top: 2px; margin-top: 2px; font-size: 6px; text-align: center;">
                                    Authorised Signatory</div>
                            </div>
                        </div>

                        <!-- Icons -->
                        <div
                            style="border: 1px solid #aebac6; border-radius: 3px; padding: 3px 10px; display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
                            <div class="text-green fw-bold" style="text-align: center; font-size: 6px;"><i
                                    class="fas fa-shield-alt" style="font-size: 11px; margin-bottom: 1px;"></i><br>
                                100% Clear Title</div>
                            <div class="text-green fw-bold" style="text-align: center; font-size: 6px;"><i
                                    class="fas fa-gavel" style="font-size: 11px; margin-bottom: 1px;"></i><br> Legal &
                                Secure</div>
                            <div class="text-green fw-bold" style="text-align: center; font-size: 6px;"><i
                                    class="fas fa-compass" style="font-size: 11px; margin-bottom: 1px;"></i><br> Vastu
                                Compliant</div>
                            <div class="text-green fw-bold" style="text-align: center; font-size: 6px;"><i
                                    class="fas fa-road" style="font-size: 11px; margin-bottom: 1px;"></i><br> Wide
                                Roads & Parks</div>
                            <div class="text-green fw-bold" style="text-align: center; font-size: 6px;"><i
                                    class="fas fa-user-shield" style="font-size: 11px; margin-bottom: 1px;"></i><br>
                                24x7 Security</div>
                            <div class="text-green fw-bold" style="text-align: center; font-size: 6px;"><i
                                    class="fas fa-tree" style="font-size: 11px; margin-bottom: 1px;"></i><br> Greens &
                                Open Spaces</div>
                        </div>

                        <!-- 🌟 THE MASTERPIECE FOOTER 🌟 -->
                        <div class="bg-blue"
                            style="display: flex; justify-content:center; align-items: center; padding: 3px 8px; border-radius: 3px; font-size: 6.5px; font-weight: bold;">
                            <div style="display: flex; gap: 15px;">
                                <span><i class="fas fa-laptop me-1"></i> Computer Generated</span>
                                <div style="color: #ffd700; letter-spacing: 0.3px;">
                                * Note: {{ $tcText }} strictly Non-Refundable under any circumstances.
                            </div>
                                <span><i class="fas fa-fingerprint me-1"></i> UID :
                                    {{ str_replace('/', '', $receipt->receipt_no) }}</span>
                            </div>
                            
                        </div>

                    </div>

                </div>
            </div>

            <!-- SCISSORS DIVIDER -->
            @if ($i == 0)
                <div class="cut-line"><i class="fas fa-cut"></i></div>
            @endif
        @endfor
    </div>
</body>

</html>
