<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debit Voucher - {{ $voucher->dv_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* 🔥 THE BULLETPROOF A4 PRINT LAYOUT STRUCTURE ENGINE 🔥 */
        * {
            box-sizing: border-box;
        }

        body,
        html {
            background: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .voucher-container {
            width: 100%;
            max-width: 900px;
            margin: 10px auto;
        }

        .voucher-wrapper {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }

        .voucher-box {
            flex-grow: 1;
            padding: 12px 15px;
            border: 2px solid #000;
            position: relative;
            border-radius: 5px;
            z-index: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
            margin-bottom: 5px;
        }

        /* Watermark Background Logic */
        .voucher-box::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 350px;
            height: 350px;
            background: url("{{ isset($company) && !empty($company->company_logo) ? asset($company->company_logo) : asset('image/harihomes1-logo.png') }}") no-repeat center;
            background-size: contain;
            opacity: 0.12 !important;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 0;
        }

        .voucher-box>* {
            position: relative;
            z-index: 1;
        }

        .voucher-heading-wrapper {
            text-align: center;
            margin: 0px 0 8px;
            padding-bottom: 4px;
        }

        .main-heading {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-decoration: underline;
            margin-bottom: 2px;
        }

        .sub-heading {
            font-size: 11px;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
        }

        .table {
            margin-bottom: 8px;
            width: 100%;
            border-collapse: collapse;
            background: transparent !important;
            flex-grow: 1;
        }

        .table th,
        .table td {
            padding: 6px 8px !important;
            font-size: 11px !important;
            border: 1px solid #000 !important;
            vertical-align: middle;
        }

        .table th {
            background: #f2f2f2 !important;
            font-weight: bold;
        }

        .table td {
            background: transparent !important;
        }

        .amount-section {
            border: 2px solid #000;
            padding: 6px 12px;
            margin: 8px 0;
            border-radius: 4px;
            background: transparent !important;
        }

        .amount-title {
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .amount-words {
            font-size: 13px;
            font-weight: bold;
            border-bottom: 1px dashed #000;
            padding-bottom: 3px;
        }

        .amount-number {
            font-size: 20px;
            font-weight: 900;
            text-align: right;
        }

        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 15px;
        }

        .signature-block {
            width: 28%;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .signature-name {
            font-size: 10px;
            font-weight: bold;
            color: #333;
            margin-bottom: 2px;
            min-height: 15px; /* Taki agar naam blank ho to bhi line apni jagah rahe */
        }

        .signature-line {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-size: 11px;
            font-weight: 700;
            width: 100%;
        }

        .copy-divider {
            border-bottom: 1px dashed #000;
            position: relative;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }

        .copy-divider span {
            font-size: 11px;
            background: #fff;
            padding: 0 10px;
            position: absolute;
            z-index: 2;
        }

        /* 🔥 HARDWARE-LEVEL PRINT MEDIA MATRICES 🔥 */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0 !important;
            }

            html,
            body {
                background: #fff !important;
                width: 210mm !important;
                height: 297mm !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .copy-divider {
                height: 6mm !important;
                margin-bottom: 2px;
            }

            .voucher-container {
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                width: 100% !important;
                height: 290mm !important;
                max-height: 290mm !important;
                padding: 8mm 12mm !important;
                margin: 0 !important;
                overflow: hidden !important;
            }

            .voucher-wrapper {
                height: 134mm !important;
                max-height: 134mm !important;
                margin-bottom: 0 !important;
                overflow: hidden !important;
                page-break-inside: avoid;
            }

            .voucher-box {
                width: 100% !important;
                flex-grow: 1 !important;
                padding: 4mm 5mm !important;
                border: 2px solid #000 !important;
                border-radius: 4px !important;
                display: flex !important;
                flex-direction: column !important;
            }

            .table {
                margin-bottom: 4px !important;
            }

            .table th,
            .table td {
                padding: 5px 6px !important;
                font-size: 10px !important;
            }

            .amount-section {
                padding: 4px 8px !important;
                margin-top: 5px !important;
                margin-bottom: 5px !important;
            }

            .amount-number {
                font-size: 16px !important;
            }

            .signature-line {
                font-size: 9px !important;
                padding-top: 2px !important;
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

    <div class="voucher-container">

        <div class="voucher-wrapper">
            <div class="voucher-box">

                <x-print-header :company="$company" :branch="$branch" />

                <div class="voucher-heading-wrapper">
                    <div class="main-heading">Debit Voucher</div>
                    <div class="sub-heading">(Office Copy)</div>
                </div>

                <table class="info-table table">
                    <tr>
                        <td style="width: 50%;"><strong>DATE:</strong>
                            {{ date('d-m-Y', strtotime($voucher->voucher_date)) }}</td>
                        <td style="width: 50%; text-align: right;"><strong>VOUCHER NO:</strong> {{ $voucher->dv_no }}
                        </td>
                    </tr>
                </table>

                <table class="table details-table">
                    <tr>
                        <th style="width: 25%;">PAID TO</th>
                        <td style="font-weight: bold;">{{ strtoupper($voucher->paid_to) }}</td>
                    </tr>
                    <tr>
                        <th>HEAD OF ACCOUNT</th>
                        <td>{{ strtoupper($voucher->head_of_account) }}</td>
                    </tr>
                    <tr>
                        <th>PAYMENT MODE</th>
                        <td>{{ strtoupper($voucher->payment_mode) }}</td>
                    </tr>
                    @if (in_array(strtolower($voucher->payment_mode), ['cheque', 'bank transfer', 'upi']))
                        <tr>
                            <th>TR. ID / CHQ NO.</th>
                            <td>{{ $voucher->transaction_id ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>BANK (DRAWN ON)</th>
                            <td>{{ strtoupper($voucher->drawn_on ?? '-') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <th>NARRATION / BEING</th>
                        <td style="height: 35px; vertical-align: top;">{{ $voucher->narration ?? '-' }}</td>
                    </tr>
                </table>

                <div class="amount-section">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <div class="amount-title">Amount Paid (In Words)</div>
                            <div class="amount-words">({{ $voucher->amount_words }})</div>
                        </div>
                        <div class="col-4">
                            <div class="amount-number">₹ {{ number_format($voucher->amount, 2) }} /-</div>
                        </div>
                    </div>
                </div>

                <div class="signature-row">
                    <div class="signature-block">
                        <div class="signature-name">{{ $approverName }}</div>
                        <div class="signature-line">Approved By</div>
                    </div>
                    <div class="signature-block">
                        <div class="signature-name">{{ $signatoryName }}</div>
                        <div class="signature-line">Authorize Signatory</div>
                    </div>
                    <div class="signature-block">
                        <div class="signature-name"></div> <div class="signature-line">Receiver's Signature</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="copy-divider">
            <span><i class="fas fa-scissors"></i> Detach Here</span>
        </div>

        <div class="voucher-wrapper">
            <div class="voucher-box">

               <x-print-header :company="$company" :branch="$branch" />

                <div class="voucher-heading-wrapper">
                    <div class="main-heading">Debit Voucher</div>
                    <div class="sub-heading">(Client Copy)</div>
                </div>

                <table class="info-table table">
                    <tr>
                        <td style="width: 50%;"><strong>DATE:</strong>
                            {{ date('d-m-Y', strtotime($voucher->voucher_date)) }}</td>
                        <td style="width: 50%; text-align: right;"><strong>VOUCHER NO:</strong> {{ $voucher->dv_no }}
                        </td>
                    </tr>
                </table>

                <table class="table details-table">
                    <tr>
                        <th style="width: 25%;">PAID TO</th>
                        <td style="font-weight: bold;">{{ strtoupper($voucher->paid_to) }}</td>
                    </tr>
                    <tr>
                        <th>HEAD OF ACCOUNT</th>
                        <td>{{ strtoupper($voucher->head_of_account) }}</td>
                    </tr>
                    <tr>
                        <th>PAYMENT MODE</th>
                        <td>{{ strtoupper($voucher->payment_mode) }}</td>
                    </tr>
                    @if (in_array(strtolower($voucher->payment_mode), ['cheque', 'bank transfer', 'upi']))
                        <tr>
                            <th>TR. ID / CHQ NO.</th>
                            <td>{{ $voucher->transaction_id ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>BANK (DRAWN ON)</th>
                            <td>{{ strtoupper($voucher->drawn_on ?? '-') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <th>NARRATION / BEING</th>
                        <td style="height: 35px; vertical-align: top;">{{ $voucher->narration ?? '-' }}</td>
                    </tr>
                </table>

                <div class="amount-section">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <div class="amount-title">Amount Paid (In Words)</div>
                            <div class="amount-words">({{ $voucher->amount_words }})</div>
                        </div>
                        <div class="col-4">
                            <div class="amount-number">₹ {{ number_format($voucher->amount, 2) }} /-</div>
                        </div>
                    </div>
                </div>

               <div class="signature-row">
                    <div class="signature-block">
                        <div class="signature-name">{{ $approverName }}</div>
                        <div class="signature-line">Approved By</div>
                    </div>
                    <div class="signature-block">
                        <div class="signature-name">{{ $signatoryName }}</div>
                        <div class="signature-line">Authorize Signatory</div>
                    </div>
                    <div class="signature-block">
                        <div class="signature-name"></div> <div class="signature-line">Receiver's Signature</div>
                    </div>
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
