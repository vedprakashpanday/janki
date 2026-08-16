<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Members List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            position: relative;
        }
        
        /* 🔥 WATERMARK CSS 🔥 */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.1; /* Halka sa visible */
            z-index: -1;
            pointer-events: none;
            width: 500px; /* Size adjust kar sakte hain */
        }
        
        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 13px;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th {
            background-color: #f2f2f2;
            padding: 8px;
            text-align: left;
            -webkit-print-color-adjust: exact;
        }
        td {
            padding: 6px 8px;
        }
        .text-center { text-align: center; }
        
        /* Print settings */
        @media print {
            @page { margin: 15mm; }
            .no-print { display: none; }
            .watermark { 
                -webkit-print-color-adjust: exact; 
                opacity: 0.1 !important; 
            }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: right; margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 8px 15px; background: #1A365D; color: white; border: none; cursor: pointer;">
            <i class="fa fa-print"></i> Print Document
        </button>
    </div>

    <x-print-header :company="$company" :branch="$branch" />

    @if($company && $company->company_logo)
        <img src="{{ asset($company->company_logo) }}" class="watermark" alt="Watermark">
    @else
        <img src="{{ asset('uploads/harihomes1-logo.png') }}" class="watermark" alt="Watermark">
    @endif

    <h3 style="text-align: center; text-decoration: underline; margin-top: 20px;">MEMBERS LIST</h3>

    <table>
        <thead>
            <tr>
                <th>S.No</th>
                <th>Member ID</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>Designation</th>
                <th>Company</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($members as $index => $member)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $member->member_id }}</td>
                    <td>{{ strtoupper($member->member_name ?? $member->full_name) }}</td>
                    <td>{{ $member->mobile }}</td>
                    <td class="text-center">{{ is_object($member->designation) ? strtoupper($member->designation->designation_name) : strtoupper($member->designation ?? 'N/A') }}</td>
                    <td>{{ $member->company ? strtoupper($member->company->company_name) : 'N/A' }}</td>
                    <td class="text-center">{{ ucfirst($member->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No Members Found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>