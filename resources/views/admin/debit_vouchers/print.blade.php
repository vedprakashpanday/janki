<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debit Voucher - {{ $formattedDvNo }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f4f7f6;
            margin: 0;
            padding: 0;
        }

        .print-container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            padding: 15px;
        }

        .voucher-wrapper {
            border: 2px solid #000;
            position: relative;
            padding: 8px 15px;
            margin-bottom: 15px;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            /* Top aur Bottom content ko unke edges par stretch karega */
            overflow: hidden;
                border-radius: 10px;
        }

        .voucher-content {
            flex-grow: 1;
            /* Bacha hua saara space content wrap le lega */
        }

       /* 💧 WATERMARK (Existing) */
        .voucher-wrapper::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 400px;
            height: 400px;
            background: url("{{ isset($company) && !empty($company->company_logo) ? asset($company->company_logo) : asset('image/harihomes1-logo.png') }}") no-repeat center;
            background-size: contain;
            opacity: 0.08 !important;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 0;
        }

        /* 🚫 CANCELLED WATERMARK (New) */
        .cancelled-voucher::after {
            content: "CANCELLED";
            position: absolute;
            top: 50%;
            left: 50%;
            font-size: 80px;
            font-weight: 900;
            color: rgba(255, 0, 0, 0.15); /* Light red, semi-transparent */
            transform: translate(-50%, -50%) rotate(-45deg);
            pointer-events: none;
            z-index: 0; 
            letter-spacing: 15px;
            white-space: nowrap;
        }

        /* 📄 TITLE SECTION */
        .title-section {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 2px;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }

        .main-title {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 1px;
            margin: 0;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .sub-title {
            font-size: 12px;
            font-weight: bold;
        }

        /* VOUCHER NO & DATE ROW */
        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }

        .meta-row .text-danger {
            color: #d32f2f !important;
        }

        /* 📋 FORM LAYOUT */
        .form-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
            position: relative;
            z-index: 1;
        }

        .form-table td {
            padding: 4px 2px;
            vertical-align: bottom;
        }

        .label-text {
            white-space: nowrap;
            font-weight: normal;
            padding-right: 5px;
        }

        .value-text {
            border-bottom: 1px solid #000;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            padding: 0 5px;
        }

        /* 🔥 NARRATION UNLIMITED EXPANSION */
        .narration-box {
            display: block;
            width: 100%;
            white-space: normal;
            word-wrap: break-word;
            line-height: 18px;
            min-height: 40px;
        }

        /* ✂️ CUT DIVIDER */
        .copy-divider {
            border-bottom: 1px dashed #000;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 8px 0;
        }

        .copy-divider span {
            background: #fff;
            padding: 0 15px;
            font-size: 12px;
            position: absolute;
            z-index: 2;
        }

        /* ✍️ SIGNATURE SECTION */
        .signature-row {
            display: flex;
            justify-content: space-between;
            padding-top: 15px;
            position: relative;
            z-index: 1;
        }

        .sig-block {
            width: 24%;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .sig-name {
            font-size: 10px;
            font-weight: bold;
            color: #000;
            min-height: 14px;
            margin-bottom: 1px;
            text-transform: uppercase;
        }

        .sig-line {
            border-top: 1px solid #000;
            padding-top: 3px;
            font-size: 10.5px;
            font-weight: bold;
        }

        /* 🔥 PRINT CSS MAGIC 🔥 */
        @media print {
            @page {
                size: A4 portrait;
                margin: 6mm 2mm;
            }

            html,
            body {
                background: #fff !important;
                margin: 0;
                padding: 0;
                width: 100%;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            /* 🔥 FIX: Left Border Cut Issue */
            .print-container {
                padding: 0 5mm !important;
                /* Left & Right margin for hardware margins */
                max-width: 100%;
                margin: 0 auto;
            }

            /* EXACT HALF PAGE HEIGHT */
            .voucher-wrapper {
                height: 140mm;
                max-height: 140mm;
                margin-bottom: 2mm;
                padding: 5mm 4mm;
            }

            .form-table {
                font-size: 11.5px;
            }

            .form-table td {
                padding: 3.5px 2px;
            }

            .narration-box {
                line-height: 16px;
            }

            .sig-line {
                font-size: 9px !important;
                white-space: nowrap !important;
                /* Forces signature title to 1 line */
            }
        }
    </style>
</head>

<body>

    @if ($mode == 'view')
        <div class="text-center my-3 no-print">
            <button onclick="window.print()" class="btn btn-dark fw-bold px-4"><i class="fas fa-print me-2"></i> PRINT
                VOUCHER</button>
            <button onclick="window.close()" class="btn btn-outline-danger fw-bold ms-2">CLOSE</button>
        </div>
    @endif

    <div class="print-container">

        <!-- 📄 VOUCHER 1 (Office Copy) -->
       <!-- 📄 VOUCHER 1 (Office Copy) -->
        <div class="voucher-wrapper {{ strtolower($voucher->status) === 'cancelled' ? 'cancelled-voucher' : '' }}">
            <div class="voucher-content">
                <x-print-header :company="$company" :branch="$branch" />

                <div class="title-section">
                    <h1 class="main-title">DEBIT VOUCHER</h1>
                    <div class="sub-title">(Payment Authorization Voucher)</div>
                </div>

                <div class="meta-row">
                    <div>Voucher No.: <span class="text-danger">{{ $formattedDvNo }}</span></div>
                    <div>Date: <span
                            style="font-weight: normal;">{{ date('d-M-Y', strtotime($voucher->voucher_date)) }}</span>
                    </div>
                </div>

                <table class="form-table">
                    <tr>
                        <td class="label-text" style="width: 15%;">Head of Account</td>
                        <td class="value-text" style="width: 55%;">{{ $ledgerName }}</td>
                        <td class="label-text" style="width: 8%; text-align: right;">Project</td>
                        <td class="value-text" style="width: 22%;">{{ $voucher->project_name ?? 'JANKI VILLA' }}</td>
                    </tr>
                    <tr>
                        <td class="label-text">Paid to Mr./Ms.</td>
                        <td class="value-text">{{ $paidToName }}</td>
                        <td class="label-text" style="text-align: right;">Rs.</td>
                        <td class="value-text">{{ number_format($voucher->amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label-text">(Rupees</td>
                        <td class="value-text" colspan="3">{{ $voucher->amount_words }} <span class="label-text"
                                style="float: right; border: none;">only)</span></td>
                    </tr>
                    <tr>
                        <td class="label-text">by Cash/Chq./UPI/NEFT No.</td>
                        <td class="value-text">{{ $displayMode }} - {{ $paymentRef ?: 'N/A' }}</td>
                        <td class="label-text" style="text-align: right;">Dated</td>
                        <td class="value-text">
                            {{ $voucher->bank_date ? date('d-M-Y', strtotime($voucher->bank_date)) : 'N/A' }}</td>
                    </tr>
                </table>

               <table class="form-table" style="margin-top: 1px;">
                    <tr>
                        <td class="label-text" style="width: 15%;">On (Comp. Bank)</td>
                        <td class="value-text" style="width: 35%;">{{ strtoupper($voucher->payment_mode) === 'CASH' ? 'N/A' : ($voucher->sender_bank ?? 'N/A') }}</td>
                        <td class="label-text" style="width: 15%; text-align: right;">Receiver's A/c No.</td>
                        <td class="value-text" style="width: 35%;">{{ strtoupper($voucher->payment_mode) === 'CASH' ? 'N/A' : ($voucher->account_no ?? 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td class="label-text">Bank Name</td>
                        <td class="value-text">{{ strtoupper($voucher->payment_mode) === 'CASH' ? 'N/A' : ($voucher->bank_name ?? 'N/A') }}</td>
                        <td class="label-text" style="text-align: right;">Branch</td>
                        <td class="value-text">{{ strtoupper($voucher->payment_mode) === 'CASH' ? 'N/A' : ($voucher->bank_branch ?? 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td class="label-text">IFSC Code</td>
                        <td class="value-text" colspan="3">{{ strtoupper($voucher->payment_mode) === 'CASH' ? 'N/A' : ($voucher->ifsc_code ?? 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td class="label-text" style="vertical-align: top; padding-top: 8px;">Purpose / Remarks</td>
                        <td class="value-text" colspan="3" style="border: none; padding-top: 8px;">
                            <div class="narration-box">{{ $voucher->narration ?? '-' }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="signature-row">
                <div class="sig-block">
                    <div class="sig-name">{{ $approverName }}</div>
                    <div class="sig-line">Prepared by (Account)</div>
                </div>
                <div class="sig-block">
                    <div class="sig-name">{{ $approverName }}</div>
                    <div class="sig-line">Verified by</div>
                </div>
                <div class="sig-block">
                    <div class="sig-name">{{ $signatoryName }}</div>
                    <div class="sig-line">Approved by (Director)</div>
                </div>
                <div class="sig-block">
                    <div class="sig-name"></div>
                    <div class="sig-line">Receiver's Signature & Mob. No.</div>
                </div>
            </div>
        </div>

        <div class="copy-divider no-print">
            <span><i class="fas fa-scissors"></i> Detach Here</span>
        </div>

        
     <!-- 📄 VOUCHER 2 (Client Copy) -->
        <div class="voucher-wrapper {{ strtolower($voucher->status) === 'cancelled' ? 'cancelled-voucher' : '' }}">
            <div class="voucher-content">
                <x-print-header :company="$company" :branch="$branch" />

                <div class="title-section">
                    <h1 class="main-title">DEBIT VOUCHER</h1>
                    <div class="sub-title">(Payment Authorization Voucher)</div>
                </div>

                <div class="meta-row">
                    <div>Voucher No.: <span class="text-danger">{{ $formattedDvNo }}</span></div>
                    <div>Date: <span
                            style="font-weight: normal;">{{ date('d-M-Y', strtotime($voucher->voucher_date)) }}</span>
                    </div>
                </div>

                <table class="form-table">
                    <tr>
                        <td class="label-text" style="width: 15%;">Head of Account</td>
                        <td class="value-text" style="width: 55%;">{{ $ledgerName }}</td>
                        <td class="label-text" style="width: 8%; text-align: right;">Project</td>
                        <td class="value-text" style="width: 22%;">{{ $voucher->project_name ?? 'JANKI VILLA' }}</td>
                    </tr>
                    <tr>
                        <td class="label-text">Paid to Mr./Ms.</td>
                        <td class="value-text">{{ $paidToName }}</td>
                        <td class="label-text" style="text-align: right;">Rs.</td>
                        <td class="value-text">{{ number_format($voucher->amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label-text">(Rupees</td>
                        <td class="value-text" colspan="3">{{ $voucher->amount_words }} <span class="label-text"
                                style="float: right; border: none;">only)</span></td>
                    </tr>
                    <tr>
                        <td class="label-text">by Cash/Chq./UPI/NEFT No.</td>
                        <td class="value-text">{{ $displayMode }} - {{ $paymentRef ?: 'N/A' }}</td>
                        <td class="label-text" style="text-align: right;">Dated</td>
                        <td class="value-text">
                            {{ $voucher->bank_date ? date('d-M-Y', strtotime($voucher->bank_date)) : 'N/A' }}</td>
                    </tr>
                </table>

               <table class="form-table" style="margin-top: 1px;">
                    <tr>
                        <td class="label-text" style="width: 15%;">On (Comp. Bank)</td>
                        <td class="value-text" style="width: 35%;">{{ strtoupper($voucher->payment_mode) === 'CASH' ? 'N/A' : ($voucher->sender_bank ?? 'N/A') }}</td>
                        <td class="label-text" style="width: 15%; text-align: right;">Receiver's A/c No.</td>
                        <td class="value-text" style="width: 35%;">{{ strtoupper($voucher->payment_mode) === 'CASH' ? 'N/A' : ($voucher->account_no ?? 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td class="label-text">Bank Name</td>
                        <td class="value-text">{{ strtoupper($voucher->payment_mode) === 'CASH' ? 'N/A' : ($voucher->bank_name ?? 'N/A') }}</td>
                        <td class="label-text" style="text-align: right;">Branch</td>
                        <td class="value-text">{{ strtoupper($voucher->payment_mode) === 'CASH' ? 'N/A' : ($voucher->bank_branch ?? 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td class="label-text">IFSC Code</td>
                        <td class="value-text" colspan="3">{{ strtoupper($voucher->payment_mode) === 'CASH' ? 'N/A' : ($voucher->ifsc_code ?? 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td class="label-text" style="vertical-align: top; padding-top: 8px;">Purpose / Remarks</td>
                        <td class="value-text" colspan="3" style="border: none; padding-top: 8px;">
                            <div class="narration-box">{{ $voucher->narration ?? '-' }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="signature-row">
                <div class="sig-block">
                    <div class="sig-name">{{ $approverName }}</div>
                    <div class="sig-line">Prepared by (Account)</div>
                </div>
                <div class="sig-block">
                    <div class="sig-name">{{ $approverName }}</div>
                    <div class="sig-line">Verified by</div>
                </div>
                <div class="sig-block">
                    <div class="sig-name">{{ $signatoryName }}</div>
                    <div class="sig-line">Approved by (Director)</div>
                </div>
                <div class="sig-block">
                    <div class="sig-name"></div>
                    <div class="sig-line">Receiver's Signature & Mob. No.</div>
                </div>
            </div>
        </div>

    </div>

    @if ($mode == 'print')
        <script>
            window.onload = function() {
                window.print();
            }
        </script>
    @endif
</body>

</html>
