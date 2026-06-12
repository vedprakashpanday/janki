<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Application - {{ $application->id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fff; color: #000; font-family: 'Inter', sans-serif; }
        .print-container { position: relative; max-width: 800px; margin: 0 auto; padding: 20px; }
        
        /* 🔥 DYNAMIC WATERMARK LOGIC 🔥 */
        .watermark {
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            opacity: 0.1;
            z-index: 0;
            pointer-events: none;
        }
        
        @media print {
            .watermark-rejected { color: red !important; opacity: 0.3 !important; font-size: 80px !important; font-weight: bold; border: 5px solid red; padding: 10px; }
            .watermark-logo { width: 300px; opacity: 0.08 !important; }
            .no-print { display: none !important; }
        }

        .watermark-rejected { color: red; opacity: 0.3; font-size: 80px; font-weight: bold; border: 5px solid red; padding: 10px; }
        .watermark-logo { width: 300px; opacity: 0.08; }

        .content-box { position: relative; z-index: 1; margin-top: 20px; }
        .table-custom th { width: 30%; background-color: #f8f9fa !important; }
    </style>
</head>
<body onload="window.print()">

<div class="no-print text-center mt-3 mb-3">
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print Now</button>
</div>

<div class="print-container">

    <div class="watermark text-center">
        @if($application->status === 'rejected')
            <div class="watermark-rejected">REJECTED</div>
        @else
            <img src="{{ $application->company->company_logo ?? asset('uploads/harihomes1-logo.png') }}" class="watermark-logo">
        @endif
    </div>

    <div class="content-box">
        <x-print-header :company="$application->company" :branch="$application->branch" />

        <h4 class="text-center text-uppercase mt-4 mb-3" style="font-weight: 800; text-decoration: underline;">
            {{ $application->application_type }} APPLICATION
        </h4>

        <table class="table table-bordered table-custom border-dark align-middle">
            <tbody>
                <tr>
                    <th>Application ID</th>
                    <td>#APP-{{ str_pad($application->id, 5, '0', STR_PAD_LEFT) }}</td>
                </tr>
                <tr>
                    <th>Applicant Name</th>
                    <td>
                        {{ $application->user_type === 'employee' ? ($application->employee->full_name ?? 'N/A') : ($application->member->full_name ?? 'N/A') }}
                        <small class="text-muted">({{ ucfirst($application->user_type) }})</small>
                    </td>
                </tr>
                <tr>
                    <th>Department & Designation</th>
                    <td>{{ $application->department->department_name ?? 'N/A' }} / {{ $application->designation->designation_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Date / Time Range</th>
                    <td>
                        <strong>From:</strong> {{ \Carbon\Carbon::parse($application->start_datetime)->format('d M Y, h:i A') }} <br>
                        <strong>To:</strong> {{ \Carbon\Carbon::parse($application->end_datetime)->format('d M Y, h:i A') }}
                    </td>
                </tr>
                <tr>
                    <th>Requested Duration</th>
                    <td>{{ $application->duration }} {{ $application->application_type === 'Short Leave' ? 'Hours' : 'Days' }}</td>
                </tr>
                <tr>
                    <th>Reason</th>
                    <td style="white-space: pre-wrap;">{{ $application->reason }}</td>
                </tr>
            </tbody>
        </table>

        <h5 class="mt-4 mb-2 fw-bold bg-light p-2 border border-dark">OFFICE USE ONLY</h5>
        <table class="table table-bordered table-custom border-dark">
            <tbody>
                <tr>
                    <th>Status</th>
                    <td class="text-uppercase fw-bold {{ $application->status === 'approved' ? 'text-success' : ($application->status === 'rejected' ? 'text-danger' : 'text-warning') }}">
                        {{ $application->status }}
                    </td>
                </tr>
                <tr>
                    <th>Approved Duration</th>
                    <td>{{ $application->approved_duration ?? 'N/A' }} {{ $application->application_type === 'Short Leave' ? 'Hours' : 'Days' }}</td>
                </tr>
                <tr>
                    <th>Approver / Reviewer Remarks</th>
                    <td>{{ $application->remarks ?? 'No remarks provided.' }}</td>
                </tr>
                <tr>
                    <th>Processed By</th>
                    <td>{{ $application->approver->name ?? ($application->rejecter->name ?? 'System/Pending') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="row mt-5 pt-4">
            <div class="col-6 text-center">
                <hr style="width: 70%; border: 1px solid #000; margin: 0 auto 5px;">
                <strong>Applicant Signature</strong>
            </div>
            <div class="col-6 text-center">
                <hr style="width: 70%; border: 1px solid #000; margin: 0 auto 5px;">
                <strong>Authorized Signatory</strong>
            </div>
        </div>

    </div>
</div>

</body>
</html>