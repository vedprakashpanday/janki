<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($entityData)->full_name ?? optional($entityData)->member_name ?? optional($entityData)->customer_name ?? 'Notice' }} - {{ $notice->title }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #fff;
            color: #000;
            padding: 20px;
            margin: 0;
            position: relative;
        }

        .print-container {
            max-width: 800px;
            margin: 0 auto;
            background: transparent;
            z-index: 10;
            position: relative;
        }

        /* 🔥 WATERMARK CSS 🔥 */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            z-index: -1;
            pointer-events: none;
            text-align: center;
        }

        .watermark img {
            width: 100%;
            max-width: 480px;
            
        }

        /* Notice Elements */
        .notice-header {
            text-align: right;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .notice-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 25px;
            text-decoration: underline;
            color: #1A365D;
        }

        /* 🔥 INDIVIDUAL TABLE CSS 🔥 */
        .entity-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 13px;
        }

        .entity-table th,
        .entity-table td {
            border: 1px solid #718096;
            padding: 6px 10px;
            text-align: left;
        }

        .entity-table th {
            background-color: #f1f5f9;
            width: 18%;
            font-weight: bold;
        }

        .entity-table td {
            width: 32%;
        }

        .notice-content {
            font-size: 15px;
            line-height: 1.6;
            text-align: justify;
        }

        @media print {
            body {
                padding: 0;
            }

            .print-container {
                width: 100%;
                max-width: none;
            }

            @page {
                margin: 1cm;
            }
        }
    </style>
</head>

<body @if(!request()->has('view_only')) onload="window.print()" @endif>

    @php
        $logoUrl =
            $company && !empty($company->company_logo)
                ? asset($company->company_logo)
                : 'https://ui-avatars.com/api/?name=' .
                    urlencode($company->company_name ?? 'AB') .
                    '&color=7F9CF5&background=EBF4FF';
    @endphp
    <div class="watermark">
        <img src="{{ $logoUrl }}" alt="Watermark">
    </div>

    <div class="print-container">
       

        <x-print-header :company="$company" :branch="$branch" />

        <div class="notice-header">
            Date: {{ \Carbon\Carbon::parse($notice->notice_date)->format('d-M-Y') }}
        </div>

        <div class="notice-title">
            {{ strtoupper($notice->title) }}
        </div>

   @if ($notice->target_audience === 'other' && isset($entityData))
            @php
                $name = $entityData->full_name ?? ($entityData->customer_name ?? 'N/A');
                $regId = $entityData->member_id ?? ($entityData->customer_id ?? 'N/A');
                $relName = $entityData->father_spouse_name ?? ($entityData->so_do_name ?? 'N/A');
                $address = $entityData->communication_address ?? ($entityData->address ?? 'N/A');

                // 🔥 YAHAN FIX KIYA HAI: Pehle custom attribute check karega, nahi mila tab relation check karega
                $desig = $entityData->custom_designation_name ?? ($entityData->designation->designation_name ?? 'N/A');
                $dept = $entityData->department->department_name ?? 'N/A';
                $branchName = $entityData->custom_branch_name ?? ($entityData->branch->branch_name ?? 'N/A');
                $compName = $entityData->company->company_name ?? 'N/A';

                $idLabel = 'ID Number';
                if ($notice->entity_type == 'employee') {
                    $idLabel = 'Employee ID';
                } elseif ($notice->entity_type == 'member') {
                    $idLabel = 'Associate ID';
                } elseif ($notice->entity_type == 'customer') {
                    $idLabel = 'Customer ID';
                }
            @endphp
            <table class="entity-table">
                <tr>
                    <th>Name</th>
                    <td><b>{{ $name }}</b></td>
                    <th>{{ $idLabel }}</th>
                    <td><b>{{ $regId }}</b></td>
                </tr>

                @if ($notice->entity_type != 'customer')
                    <tr>
                        <th>Designation</th>
                        <td>{{ $desig }}</td>
                        <th>Department</th>
                        <td>{{ $dept }}</td>
                    </tr>
                @endif

                <tr>
                    <th>Branch</th>
                    <td>{{ $branchName }}</td>
                    <th>Company</th>
                    <td>{{ $compName }}</td>
                </tr>
                <tr>
                    <th>S/O, W/O, D/O</th>
                    <td>{{ $relName }}</td>
                    <th>Address</th>
                    <td>{{ $address }}</td>
                </tr>
            </table>
        @endif

        <div class="notice-content">
            {!! $notice->content !!}
        </div>

        <!-- 🔥 NEW: Print Replies Section 🔥 -->
        @if ($notice->requires_reply == 1 && $notice->replies->count() > 0)
            <div style="margin-top: 40px; border-top: 2px dashed #718096; padding-top: 20px;">
                <h4 style="color: #1A365D; margin-bottom: 15px; text-decoration: underline; font-size: 16px;">Acknowledgments / Replies Received</h4>
                <table class="entity-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Date & Time</th>
                            <th style="width: 25%;">Sender Name</th>
                            <th style="width: 15%;">Role</th>
                            <th style="width: 40%;">Reply Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notice->replies as $reply)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($reply->created_at)->format('d-M-Y h:i A') }}</td>
                            <td><b>{{ $reply->sender_name }}</b></td>
                            <td style="text-transform: capitalize;">{{ $reply->sender_type }}</td>
                            <td>{{ $reply->reply_text }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>
</body>

</html>
