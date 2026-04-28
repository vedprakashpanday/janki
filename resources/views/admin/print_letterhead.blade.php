<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Receipt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #e9ecef;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #000;
        }

        /* =======================================================
           A4 PERFECT SIZE (SCREEN VIEW) 
           ======================================================= */
        .voucher-box {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            padding: 5mm;
            border: 1px solid #ccc;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            box-sizing: border-box;
            position: relative;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        /* ===== HEADER ===== */
        .header-wrap {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 12px;
            margin-bottom: 15px;
            width: 100%;
            position: relative;
            z-index: 1;
            background: #fff;
        }

        .header-logo {
            width: 15%;
            text-align: left;
        }

        .logo-image {
            width: 120px;
            height: auto;
        }

        .iso {
            font-size: 6px;
            color: #c00000;
            font-weight: bold;
            margin-top: 5px;
            text-align: start;
            width: 140px;
        }

        .header-text {
            width: 85%;
            text-align: center;
        }

        .company-title {
            font-size: 25px;
            font-weight: 900;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
            color: #000;
        }

        .cin-text {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .small-text {
            font-size: 12px;
            line-height: 1.2;
        }

        /* REF & DATE */
        .ref-date-row {
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            font-size: 13px;
            margin: 10px 0 20px;
            position: relative;
            z-index: 1;
        }

        /* TITLE */
        .voucher-heading {
            text-align: center;
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 20px;
            letter-spacing: 1px;
            text-decoration: underline;
            position: relative;
            z-index: 1;
        }

        /* =======================================================
           🔥 PROFESSIONAL "TO" TABLE FIX 
           ======================================================= */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
            position: relative;
            z-index: 1;
        }

        .info-table th,
        .info-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .info-table th {
            background-color: #f0f0f0 !important;
            font-weight: 700;
            color: #000;
            width: 15%;
        }

        .info-table td {
            font-weight: 600;
            color: #333;
        }

        /* =======================================================
           🔥 SUMMERNOTE TEXT FIX (Line Height & Alignment)
           ======================================================= */
        .summernote-content {
            font-size: 14px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            line-height: 1.4 !important;
            text-align: justify;
            position: relative;
            z-index: 1;
        }

        .summernote-content p,
        .summernote-content div {
            margin-bottom: 8px !important;
        }

        .summernote-content table {
            width: 100% !important;
            border-collapse: collapse;
            margin: 10px 0;
            table-layout: fixed;
        }

        .summernote-content th,
        .summernote-content td {
            border: none;
            padding: 4px;
        }

        /* WATERMARK */
        .watermark {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.08;
            pointer-events: none;
            z-index: 0;
        }

        .watermark img {
            width: 70%;
            max-width: 600px;
        }

        /* =======================================================
           🔥 MASTER PRINT TABLE FIX 🔥
           ======================================================= */
        .main-print-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .main-print-table > thead > tr > td,
        .main-print-table > tbody > tr > td,
        .main-print-table > tfoot > tr > td {
            border: none;
            padding: 0;
            background: transparent;
        }

        /* 🔥 FOOTER SPACER: Yeh har page ke niche 100px ki khali jagah banayega 🔥 */
        .footer-spacer {
            height: 100px; 
        }

        /* FOOTER NOTE (Screen View) */
        .print-footer {
            font-size: 11px;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 20px;
            text-align: justify;
            color: #333;
            position: relative;
            z-index: 1;
            background: #fff;
        }

        /* =======================================================
           🔥 PRINT MEDIA FIXES (Perfect A4 Alignment)
           ======================================================= */
        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm 10mm 15mm 10mm !important; /* Top, Right, Bottom, Left */
            }

            body {
                background: #fff !important;
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }
            
            .logo-image {
                width: 140px;
                height: auto;
            }

            .iso {
                font-size: 6px;
                color: #c00000;
                font-weight: bold;
                margin-top: 5px;
                text-align: start;
                width: 140px;
            }

            .company-title {
                font-size: 24px;
                font-weight: 900;
                letter-spacing: 0.5px;
                margin-bottom: 3px;
                color: #000;
            }

            .small-text {
                font-size: 11px;
                line-height: 1.4;
            }

            .voucher-box {
                width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }

            .watermark {
                position: fixed !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                z-index: 0 !important;
            }

            .info-table th {
                background-color: #e9ecef !important;
                -webkit-print-color-adjust: exact;
            }

            /* 🔥 FIX 1: Prevent lines/text from slicing horizontally 🔥 */
            .summernote-content p, 
            .summernote-content li, 
            .summernote-content tr,
            .summernote-content h1,
            .summernote-content h2,
            .summernote-content h3,
            .summernote-content h4,
            .summernote-content h5,
            .summernote-content h6,
            .info-table tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .summernote-content td {
                display: table-cell !important;
                width: auto !important;
            }

            /* 🔥 FIX 2: Fixed footer strictly at the bottom of every page 🔥 */
            .print-footer {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                height: 60px !important; /* Fixing height to fit inside the 100px spacer */
                background: #fff !important; /* White background to overlap any stray element */
                padding-top: 5px !important;
                border-top: 1.5px solid #000 !important;
                z-index: 9999 !important;
            }
        }
    </style>
</head>

<body>


    <div class="voucher-box">
        <div class="watermark"><img id="Img" src="{{ asset('uploads/harihomes1-logo.png') }}" class="mr-2" />
        </div>

        <div class="header-wrap">
            <div class="header-logo">
                <img src="{{ asset('uploads/harihomes1-logo.png') }}" class="logo-image">
                <div class="iso">(An ISO 9001:2015 Certified Company)</div>
            </div>

            <div class="header-text">
                <div class="company-title">AMITABH BUILDERS AND DEVELOPERS PVT LTD</div>
                <div class="cin-text">CIN NO. : U24299BR2024PTC072712</div>
                <div class="small-text">
                    <b>Office Address :</b>
                    1st Floor, Pappu Yadav Building, South of NH-27,
                    Kakarghati Chowk, Bhuskaul, Darbhanga-846007
                </div>
                <div class="small-text">
                    Phone : <b>9060218 - 222 / 333 / 666</b> |
                    WhatsApp : <b>9472467007</b> |
                    Website : <b>www.jankivilla.com</b>
                </div>
            </div>
        </div>

        <div class="ref-date-row">
            <div>Ref No : {{ $records['ref_no'] }}</div>
            <div>Date : {{ date('d/m/Y', strtotime($records['letter_date'])) }}</div>
        </div>

        <div class="voucher-heading text-uppercase">
            {{ $records['letter_title'] ?? 'LETTERHEAD' }}
        </div>

        @if (strtolower($records['emp_code'] ?? '') === 'all' || empty($records['paid_to_id']))
            <div class="mb-3">
                <b>To,</b><br>
                <b class="fs-6">{{ $records['emp_code'] ?? 'All' }}</b>
            </div>
        @else
            <b>To,</b>
            <table class="table table-bordered border-dark mt-2 mb-3">
                <tr>
                    <th width="10%">Name</th>
                    <td width="23%">{{ $records['paid_to_name'] }}</td>

                    <th width="10%">Code</th>
                    <td width="22%">{{ $records['paid_to_id'] }}</td>

                    <th width="15%">Designation</th>
                    <td width="20%">{{ $records['paid_to_designation'] }}</td>
                </tr>
                <tr>
                    <th>Doj</th>
                    <td>{{ $records['paid_to_doj'] !== '-' ? date('d/m/Y', strtotime($records['paid_to_doj'])) : '-' }}</td>

                    <th>Mobile</th>
                    <td>{{ $records['paid_to_mobile'] }}</td>

                    <th>S/O,D/O,W/O</th>
                    <td>{{ $records['paid_to_relation'] }}</td>
                </tr>
                <tr>
                    <th>Address</th>
                    <td colspan="5">{{ $records['paid_to_address'] }}</td>
                </tr>
            </table>
        @endif
        <table class="layout-table" style="width: 100%; border: none; border-collapse: collapse;">
            <thead style="border: none;">
                <tr>
                    <td style="border: none; padding: 0;">
                        <div style="height: 15px;"></div>
                    </td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border: none; padding: 0;">
                        <div class="summernote-content">
                            {!! $records['message'] !!}
                        </div>
                    </td>
                </tr>
            </tbody>
            <tfoot style="border: none;">
                <tr>
                    <td style="border: none; padding: 0;">
                        <div style="height: 50px;"></div>
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="print-footer">
            <b>Note <sup class="text-danger">*</sup></b> This document is issued strictly for the use of authorized
            company employees/members only. It shall not be deemed legally valid or enforceable without the physical
            signature of an authorized signatory and the official company seal
        </div>
    </div>
</body>

</html>