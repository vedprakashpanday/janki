<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debit Voucher - {{ $voucher->dv_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Arial', sans-serif; background: #fff; color: #000; }
        .invoice-container { max-width: 800px; margin: 10px auto; padding: 20px; border: 1px solid #000; }
        
        /* Exact Header Style from print_dv.php */
        .company-header { text-align: center; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .company-name { font-size: 26px; font-weight: bold; margin: 0; text-transform: uppercase; }
        .company-address { font-size: 14px; margin: 0; font-weight: bold; }
        
        .header-title { 
            font-size: 22px; 
            font-weight: bold; 
            text-transform: uppercase; 
            text-align: center; 
            margin: 15px 0; 
            text-decoration: underline; 
        }

        .info-table { width: 100%; margin-bottom: 15px; }
        .info-table td { padding: 5px; font-size: 15px; }
        
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .details-table th, .details-table td { border: 1px solid #000; padding: 10px; font-size: 14px; text-align: left; }
        .details-table th { background-color: #f2f2f2; width: 30%; font-weight: bold; }
        
        /* Amount Box Section */
        .amount-section { border: 2px solid #000; padding: 15px; margin-top: 20px; margin-bottom: 30px; }
        .amount-title { font-weight: bold; font-size: 15px; margin-bottom: 8px; text-transform: uppercase; }
        .amount-row { display: flex; align-items: center; justify-content: space-between; }
        .amount-number { font-size: 22px; font-weight: bold; border: 1px solid #000; padding: 8px 20px; background: #eee; }
        .amount-words { font-size: 14px; font-weight: bold; text-transform: uppercase; flex: 1; margin-left: 20px; }

        /* Signature Section */
        .signature-row { margin-top: 50px; }
        .signature-line { border-top: 1px solid #000; width: 85%; margin: 0 auto; padding-top: 5px; font-weight: bold; font-size: 13px; text-transform: uppercase; }

        @media print {
            .no-print { display: none; }
            .invoice-container { border: 1px solid #000; margin: 0 auto; width: 100%; }
        }
    </style>
</head>
<body>

    @if($mode == 'view')
    <div class="text-center my-3 no-print">
        <button onclick="window.print()" class="btn btn-dark fw-bold px-4"><i class="fas fa-print me-2"></i> PRINT VOUCHER</button>
        <button onclick="window.close()" class="btn btn-outline-danger fw-bold ms-2">CLOSE</button>
    </div>
    @endif

    <div class="invoice-container">
        <div class="company-header">
            <div class="company-name">JANKI VILLA</div>
            <div class="company-address">A Unit of Hari Homes & Developers Pvt. Ltd.</div>
            <div class="company-address" style="font-weight: normal;">Darbhanga, Bihar - 846004</div>
        </div>

        <div class="header-title">Debit Voucher</div>

        <table class="info-table">
            <tr>
                <td><strong>DATE:</strong> {{ date('d-m-Y', strtotime($voucher->voucher_date)) }}</td>
                <td style="text-align: right;"><strong>VOUCHER NO:</strong> {{ $voucher->dv_no }}</td>
            </tr>
        </table>

        <table class="details-table">
            <tr>
                <th>PAID TO</th>
                <td style="font-weight: bold; font-size: 16px;">{{ strtoupper($voucher->paid_to) }}</td>
            </tr>
            <tr>
                <th>HEAD OF ACCOUNT</th>
                <td>{{ strtoupper($voucher->head_of_account) }}</td>
            </tr>
            <tr>
                <th>PAYMENT MODE</th>
                <td>{{ strtoupper($voucher->payment_mode) }}</td>
            </tr>

            @if(in_array(strtolower($voucher->payment_mode), ['cheque', 'bank transfer', 'upi']))
            <tr>
                <th>TR. ID / CHQ NO.</th>
                <td>{{ $voucher->transaction_id ?? '-' }}</td>
            </tr>
            <tr>
                <th>BANK NAME (DRAWN ON)</th>
                <td>{{ strtoupper($voucher->drawn_on ?? '-') }}</td>
            </tr>
            @endif

            <tr>
                <th>NARRATION / BEING</th>
                <td style="height: 60px; vertical-align: top;">{{ $voucher->narration ?? '-' }}</td>
            </tr>
        </table>

        <div class="amount-section">
            <div class="amount-title">Amount Paid</div>
            <div class="amount-row">
                <div class="amount-number">₹ {{ number_format($voucher->amount, 2) }} /-</div>
                <div class="amount-words">
                    ({{ $voucher->amount_words }})
                </div>
            </div>
        </div>

        <div class="row text-center signature-row">
            <div class="col-4">
                <div class="signature-line">Approved By</div>
            </div>
            <div class="col-4">
                <div class="signature-line">Authorize Signatory</div>
            </div>
            <div class="col-4">
                <div class="signature-line">Receiver's Signature</div>
            </div>
        </div>
    </div>

    @if($mode == 'print')
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
    @endif

</body>
</html>