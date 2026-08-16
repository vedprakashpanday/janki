<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: #fff; 
            color: #000; 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
            position: relative; /* Watermark ke positioning ke liye zaroori */
        }
        .table th { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; }
        
        /* 🔥 WATERMARK CSS 🔥 */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.1; /* Logo ko halka (transparent) banane ke liye */
            z-index: -1;  /* Text ke peeche bhejne ke liye */
            pointer-events: none;
        }
        .watermark img {
            max-width: 500px; /* Logo ka maximum size */
            max-height: 500px;
            filter: grayscale(100%); /* Agar watermark black & white chahiye to, warna ye line hata sakte hain */
        }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 15px; }
            .watermark {
                position: fixed !important;
                display: block !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <!-- 🔥 WATERMARK HTML 🔥 -->
    @if($company && $company->company_logo)
    <div class="watermark">
        <img src="{{ asset($company->company_logo) }}" alt="Company Watermark">
    </div>
    @endif

    <!-- Aapka existing print header component -->
    <x-print-header :company="$company" :branch="$branch" />

    <div class="text-center mb-3 mt-4">
        <h4 class="mb-0 text-decoration-underline fw-bold">STOCK INVENTORY REPORT</h4>
        <p class="mb-0 text-muted">Generated On: {{ date('d-M-Y h:i A') }}</p>
    </div>

    <table class="table table-bordered table-sm mt-3">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Category</th>
                <th>Item Name</th>
                <th>Entry Date</th>
                <th>Serial No.</th>
                <th class="text-end">Unit Price (₹)</th> <!-- 🔥 Price Column -->
                <th class="text-center">Total Qty</th>
                <th class="text-center">Lost Qty</th>
                <th class="text-center">Available Qty</th>
                <th class="text-end">Total Value (₹)</th> <!-- 🔥 Total Amount Column -->
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @forelse($stocks as $index => $stock)
                @php 
                    $availableQty = $stock->total_quantity - $stock->lost_quantity;
                    $totalValue = $availableQty * $stock->price; // Available amount ki value nikalne ke liye
                    $grandTotal += $totalValue;
                @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $stock->category ?? '-' }}</td>
                <td><strong>{{ $stock->item_name }}</strong></td>
                <td>{{ date('d-M-Y', strtotime($stock->entry_date)) }}</td>
                <td>{{ $stock->serial_number ?? '-' }}</td>
                <td class="text-end">{{ number_format($stock->price, 2) }}</td>
                <td class="text-center">{{ $stock->total_quantity }}</td>
                <td class="text-center text-danger">{{ $stock->lost_quantity }}</td>
                <td class="text-center fw-bold text-success">{{ $availableQty }}</td>
                <td class="text-end fw-bold">{{ number_format($totalValue, 2) }}</td>
                <td>{{ $stock->remarks ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="text-center py-3">No Stock Found for the selected filters.</td>
            </tr>
            @endforelse
        </tbody>
        
        <!-- 🔥 Grand Total Footer 🔥 -->
        @if($stocks->isNotEmpty())
        <tfoot>
            <tr class="table-light">
                <th colspan="9" class="text-end fs-6">GRAND TOTAL ESTIMATE:</th>
                <th class="text-end fs-6 text-success fw-bold">₹{{ number_format($grandTotal, 2) }}</th>
                <th></th>
            </tr>
        </tfoot>
        @endif
    </table>

</body>
</html>