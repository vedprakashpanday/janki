<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Allocation Letter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="container mt-5" style="max-width: 800px;">
        <div class="text-end mb-3 no-print">
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print</button>
        </div>
        <div class="text-center mb-4 border-bottom pb-3">
            @if($allocation->company && $allocation->company->company_logo)
                <img src="{{ asset($allocation->company->company_logo) }}" height="60" class="mb-2">
            @endif
            <h4 class="fw-bold mb-0">{{ $allocation->company->company_name ?? 'COMPANY NAME' }}</h4>
            <p class="text-muted">{{ $allocation->branch->branch_name ?? 'Head Office' }}</p>
        </div>
        
        <h5 class="text-center fw-bold text-decoration-underline mb-4">SITE INCHARGE ALLOCATION LETTER</h5>
        
        <table class="table table-bordered">
            <tr>
                <th class="bg-light" width="30%">Employee Name</th>
                <td>{{ $allocation->employee->full_name ?? 'N/A' }} <small>({{ $allocation->employee->member_id ?? '' }})</small></td>
            </tr>
            <tr>
                <th class="bg-light">Roles Assigned</th>
                <td>{{ implode(', ', $allocation->incharge_types ?? []) }}</td>
            </tr>
            <tr>
                <th class="bg-light">Modules Allowed</th>
                <td>{{ implode(', ', $allocation->allowed_categories ?? []) }}</td>
            </tr>
            <tr>
                <th class="bg-light">Validity Period</th>
                <td>{{ $allocation->start_date ? \Carbon\Carbon::parse($allocation->start_date)->format('d M Y') : 'Any' }} <strong>to</strong> {{ $allocation->end_date ? \Carbon\Carbon::parse($allocation->end_date)->format('d M Y') : 'Any' }}</td>
            </tr>
        </table>

        <div class="mt-5 pt-5 d-flex justify-content-between">
            <div class="text-center">
                <hr style="width: 200px; border: 1px solid #000;">
                <strong>Employee Signature</strong>
            </div>
            <div class="text-center">
                <hr style="width: 200px; border: 1px solid #000;">
                <strong>Authorized Signatory</strong>
            </div>
        </div>
    </div>
</body>
</html>