<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Units Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style> body { font-family: Arial, sans-serif; background-color: #fff; font-size: 11px; } .table th { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; } @media print { .no-print { display: none !important; } .landscape-mode { font-size: 12px; } body { padding: 0; margin: 0; } } </style>
</head>
<body class="p-4 landscape-mode" onload="window.print()">
    <div class="text-end mb-3 no-print"><button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print</button> <button class="btn btn-secondary" onclick="window.close()">Close</button></div>
    <x-print-header :company="$company" :branch="$branch" />
    <div class="text-center mb-4 mt-2"><h4 class="text-decoration-underline fw-bold">PROPERTY UNITS & PLOTS</h4><p class="mb-0 text-muted">Generated on: {{ now()->format('d-M-Y h:i A') }}</p></div>
    <table class="table table-bordered table-sm">
        <thead><tr><th>S.No.</th><th>Unit/Plot No.</th><th>Category</th><th>Area</th><th>East</th><th>West</th><th>North</th><th>South</th><th>Status</th></tr></thead>
        <tbody>
            @forelse($propertyUnits as $key => $unit)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td class="fw-bold text-primary">{{ $unit->unit_number }}</td>
                <td>{{ $unit->category ? $unit->category->category_name : 'N/A' }}</td>
                <td>{{ $unit->area ? $unit->area->area_name . ' ' . $unit->area->measurement_unit : 'N/A' }}</td>
                <td>{{ $unit->boundaries['east'] ?? '-' }}</td>
                <td>{{ $unit->boundaries['west'] ?? '-' }}</td>
                <td>{{ $unit->boundaries['north'] ?? '-' }}</td>
                <td>{{ $unit->boundaries['south'] ?? '-' }}</td>
                <td>{{ strtoupper($unit->status) }}</td>
            </tr>
            @empty <tr><td colspan="9" class="text-center">No Records Found</td></tr> @endforelse
        </tbody>
    </table>
</body>
</html>