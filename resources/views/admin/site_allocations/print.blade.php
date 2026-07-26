<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Site Entry - {{ $entry->category }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
        }

        .print-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .details-table th {
            background-color: #f8f9fa;
            width: 30%;
        }

        .financial-box {
            border: 2px solid #333;
            padding: 15px;
            text-align: center;
            border-radius: 8px;
        }

        @media print {
            .no-print {
                display: none !important;
            }
  @page {
                size: A4 portrait;
                margin: 2mm;
            }

            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="print-container">

        <div class="text-end mb-4 no-print">
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> Print</button>
            <button onclick="window.close()" class="btn btn-secondary btn-sm">Close</button>
        </div>

        <x-print-header :company="$company" :branch="$branch" />

        <div class="text-center mb-4 mt-4">
            <h5 class="text-uppercase fw-bold border border-2 border-dark d-inline-block px-4 py-2 bg-light">
                Site Daily Report: {{ $entry->category }}
            </h5>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <strong>Entry ID:</strong> #{{ str_pad($entry->id, 5, '0', STR_PAD_LEFT) }}<br>
                <strong>Date:</strong> {{ \Carbon\Carbon::parse($entry->entry_date)->format('d M, Y') }}
            </div>
            <div class="col-6 text-end">
                <strong>Submitted By:</strong> {{ $entry->enteredBy->full_name ?? 'N/A' }}<br>
                <strong>Emp ID:</strong> {{ $entry->enteredBy->member_id ?? 'N/A' }}
            </div>
        </div>

        <h6 class="fw-bold border-bottom pb-2">Entry Details</h6>
        <table class="table table-bordered details-table mb-4">
            <tbody>
                @foreach ($details as $key => $value)
                    <tr>
                        <th class="text-capitalize">{{ str_replace('_', ' ', $key) }}</th>
                        <td>{{ is_array($value) ? implode(', ', $value) : ($value ?: '-') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($entry->category !== 'Vehicle Trip Slip')
            <h6 class="fw-bold border-bottom pb-2">Financial Summary</h6>
            <div class="row mt-3">
                <div class="col-4">
                    <div class="financial-box">
                        <small class="text-muted d-block text-uppercase">Total Amount</small>
                        <h5 class="mb-0 fw-bold">₹{{ number_format($entry->total_amount, 2) }}</h5>
                    </div>
                </div>
                <div class="col-4">
                    <div class="financial-box" style="border-color: #198754; color: #198754;">
                        <small class="d-block text-uppercase">Paid Amount</small>
                        <h5 class="mb-0 fw-bold">₹{{ number_format($entry->paid_amount, 2) }}</h5>
                    </div>
                </div>
                <div class="col-4">
                    <div class="financial-box" style="border-color: #dc3545; color: #dc3545;">
                        <small class="d-block text-uppercase">Balance Left</small>
                        <h5 class="mb-0 fw-bold">₹{{ number_format($entry->balance_amount, 2) }}</h5>
                    </div>
                </div>
            </div>
        @endif

        <div class="row mt-5 pt-5">
            <div class="col-6 text-center">
                <hr class="w-50 mx-auto border-dark">
                <strong>Site Incharge Signature</strong>
            </div>
            <div class="col-6 text-center">
                <hr class="w-50 mx-auto border-dark">
                <strong>Authorized Signatory</strong>
            </div>
        </div>

    </div>

</body>

</html>
