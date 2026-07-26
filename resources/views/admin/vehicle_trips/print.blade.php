<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Vehicle Trip Slips</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 0; background: #f0f0f0; }
        .page-container { width: 210mm; margin: 20px auto; background: white; padding: 10mm; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px; }
        
        .slip-wrapper { width: 48%; border: 1px dashed #666; position: relative; box-sizing: border-box; padding: 2px; margin-bottom: 15px; page-break-inside: avoid; }
        .slip-inner { border: 2px solid #000; padding: 10px; display: flex; gap: 10px; position: relative; z-index: 2; height: 100%; box-sizing: border-box; }
        
        /* Watermark */
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.1; width: 60%; z-index: 1; pointer-events: none; }
        
        /* Left Box (Trip No) */
        .left-box { width: 25%; border-right: 1px dashed #666; display: flex; flex-direction: column; align-items: center; justify-content: center; padding-right: 10px; }
        .trip-title { font-weight: bold; text-decoration: underline; font-size: 12px; margin-bottom: 10px; }
        .trip-number-box { border: 2px solid #000; border-radius: 6px; padding: 8px 5px; text-align: center; width: 100%; }
        .trip-prefix { font-size: 10px; color: #555; border-bottom: 1px solid #ccc; padding-bottom: 4px; margin-bottom: 4px; }
        .trip-main-num { font-size: 18px; font-weight: 900; }

        /* Right Box (Details) */
        .right-box { width: 75%; padding-left: 5px; }
        .header-text { text-align: center; margin-bottom: 10px; }
        .company-name { font-size: 14px; font-weight: 900; margin: 0; text-transform: uppercase; }
        .phase-name { font-size: 12px; font-weight: bold; margin: 2px 0; }
        .slip-type { font-size: 12px; font-weight: bold; text-decoration: underline; text-transform: uppercase; }
        
        .slip-full-no { border: 2px solid #000; border-radius: 20px; text-align: center; padding: 4px; font-size: 13px; font-weight: bold; margin-bottom: 15px; }
        
        .details-row { display: flex; align-items: center; margin-bottom: 8px; font-size: 12px; font-weight: 600; }
        .details-row span { white-space: nowrap; margin-right: 5px; }
        .line-input { flex-grow: 1; border-bottom: 1px solid #000; padding-left: 5px; font-weight: normal; }

        /* Signatures */
        .signatures { display: flex; justify-content: space-between; margin-top: 25px; }
        .sign-box { width: 45%; text-align: center; font-size: 9px; font-weight: bold; }
        .sign-line { border-bottom: 1px solid #000; margin-bottom: 4px; height: 15px; }

        @media print {
            body { background: white; }
            .page-container { margin: 0; padding: 5mm; width: 100%; box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="text-center no-print" style="padding: 15px; background: #fff; margin-bottom: 10px; border-bottom: 1px solid #ccc;">
    <button onclick="window.print()" style="padding: 8px 20px; background: #1A365D; color: #fff; border: none; cursor: pointer; border-radius: 4px; font-weight: bold;">Print Slips</button>
</div>

<div class="page-container">
    @foreach($trips as $trip)
    @php 
        $slipParts = explode('/', $trip->slip_number);
        $prefix = $slipParts[0] . '/' . $slipParts[1] . '/';
        $number = $slipParts[2] ?? '000';
    @endphp
    
    <div class="slip-wrapper">
        <!-- Watermark -->
        @if($trip->company && $trip->company->company_logo)
            <img src="{{ asset($trip->company->company_logo) }}" class="watermark">
        @endif
        
        <div class="slip-inner">
            <!-- Left Side -->
            <div class="left-box">
                <div class="trip-title">TRIP NO.</div>
                <div class="trip-number-box">
                    <div class="trip-prefix">{{ $prefix }}</div>
                    <div class="trip-main-num">{{ $number }}</div>
                </div>
            </div>
            
            <!-- Right Side -->
            <div class="right-box">
                <div class="header-text">
                    <h4 class="company-name">{{ $trip->company->company_name ?? 'COMPANY NAME' }}</h4>
                    <p class="phase-name">{{ $trip->phase_id ? 'JANKI VILLA PHASE-II' : 'MAIN PROJECT' }}</p>
                    <div class="slip-type">{{ $trip->slip_type }} RECEIPT SLIP</div>
                </div>
                
                <div class="slip-full-no">
                    Slip / Trip No.: {{ $trip->slip_number }}
                </div>
                
                <div class="details-row">
                    <span>Vehicle No.:</span>
                    <div class="line-input">{{ $trip->vehicle_number }}</div>
                </div>
                <div class="details-row">
                    <span>Date:</span>
                    <div class="line-input">{{ \Carbon\Carbon::parse($trip->trip_date)->format('d-m-Y') }}</div>
                </div>
                <div class="details-row">
                    <span>Time:</span>
                    <div class="line-input">{{ $trip->arrival_time ? \Carbon\Carbon::parse($trip->arrival_time)->format('h:i A') : '______' }}</div>
                </div>
                
               <div class="signatures">
                    <div class="sign-box">
                        <div class="sign-line">
                            <span style="font-weight: normal; font-size: 8px;">{{ \App\Models\Employee::find($trip->project_manager_id)->member_id ?? '' }}</span>
                        </div>
                        Site Project Manager Sign & Date
                    </div>
                    <div class="sign-box">
                        <div class="sign-line">
                            <span style="font-weight: normal; font-size: 8px;">{{ \App\Models\Employee::find($trip->site_supervisor_id)->member_id ?? '' }}</span>
                        </div>
                        Site Supervisor Sign & Date
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

</body>
</html>