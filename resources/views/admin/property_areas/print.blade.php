<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Areas Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style> body { font-family: Arial, sans-serif; background-color: #fff; font-size: 12px; } .table th { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; } @media print { .no-print { display: none !important; } body { padding: 0; margin: 0; } } </style>
</head>
<body class="p-4" onload="window.print()">
    <div class="text-end mb-3 no-print"><button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print</button> <button class="btn btn-secondary" onclick="window.close()">Close</button></div>
    <x-print-header :company="$company" :branch="$branch" />
    <div class="text-center mb-4 mt-2"><h4 class="text-decoration-underline fw-bold">PROPERTY AREAS REPORT</h4><p class="mb-0 text-muted">Generated on: {{ now()->format('d-M-Y h:i A') }}</p></div>
    <table class="table table-bordered table-sm">
        <thead><tr><th>S.No.</th><th>Area Value</th><th>Unit</th><th>Category</th><th>Property Type</th><th>Phase Name</th><th>Branch / Location</th><th>Status</th></tr></thead>
        <tbody>
            @forelse($propertyAreas as $key => $area)
            <tr>
                <td>{{ $key + 1 }}</td><td class="fw-bold">{{ $area->area_name }}</td>
                <td>{{ $area->measurement_unit }}</td>
                <td>{{ $area->category ? $area->category->category_name : 'N/A' }}</td>
                <td>{{ ($area->category && $area->category->propertyType) ? $area->category->propertyType->type_name : 'N/A' }}</td>
                <td>{{ ($area->category && $area->category->propertyType && $area->category->propertyType->phase) ? $area->category->propertyType->phase->phase_name : 'N/A' }}</td>
                <td>{{ $area->branch ? $area->branch->branch_name : 'Head Office' }}</td>
                <td>{{ strtoupper($area->status) }}</td>
            </tr>
            @empty <tr><td colspan="8" class="text-center">No Records Found</td></tr> @endforelse
        </tbody>
    </table>
</body>
</html>