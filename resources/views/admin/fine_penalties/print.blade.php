<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Fine/Penalty Receipt - {{ $fine->employee->full_name ?? $fine->employee->name ?? $fine->employee->employee_name ?? 'Employee' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            background-color: #f8f9fa;
        }

        .receipt-container {
            max-width: 240mm;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            background-color: #f8f9fa;
            padding: 8px;
            font-weight: bold;
            border-bottom: 2px solid #000;
            margin-top: 25px;
            margin-bottom: 15px;
        }

        .proof-img {
            max-height: 550px;
            max-width: 50%;
            border: 1px solid #ccc;
            padding: 5px;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        /* 🖨️ A4 PRINT CSS */
        @media print {
            @page {
                size: A4;
                margin: 5mm;
              border: 1px solid black;  
            }

            body {
                background-color: #fff;
            }

            .d-print-none {
                display: none !important;
            }

            .receipt-container {
                border: none;
                box-shadow: none;
                padding: 0;
                width: 100%;
                max-width: 100%;
                /* box-sizing: border-box; */
            }

            .section-title {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background-color: #f8f9fa !important;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid ">

        <!-- Controls Section (Hidden in Print) -->
        <div
            class="d-print-none d-flex justify-content-between align-items-center mb-3 p-3 bg-white rounded shadow-sm border">
            @if ($hasProof)
                <div class="form-check form-switch fs-5">
                    <input class="form-check-input cursor-pointer" type="checkbox" id="toggleProof" checked
                        style="cursor: pointer;">
                    <label class="form-check-label fw-bold" for="toggleProof" style="cursor: pointer;">Show Proof Images
                        in Print</label>
                </div>
            @else
                <div class="text-muted"><i class="fas fa-info-circle"></i> No proof attached</div>
            @endif

            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print
                Document</button>
        </div>

        <div class="receipt-container my-3">
            <x-print-header :company="$company" :branch="$fine->employee->branch ?? null" />
           <!-- 1. DYNAMIC TITLE & RECORD NO LOGIC -->
           @php
                $hasFine = ($fine->fine_rupees > 0 || $fine->fine_days > 0);
                $hasPenalty = ($fine->penalty_rupees > 0 || $fine->penalty_days > 0);
                
                if ($hasFine && $hasPenalty) {
                    $noticeTitle = "FINE & PENALTY NOTICE";
                } elseif ($hasPenalty) {
                    $noticeTitle = "PENALTY NOTICE";
                } else {
                    $noticeTitle = "FINE NOTICE";
                }

                // --- NAYA: Treat As Badge Logic ---
                $treatAsText = 'APPLIED';
                $treatColor = '#008000'; 
                if ($fine->treat_as === 'warning') { 
                    $treatAsText = 'WARNING'; 
                    $treatColor = '#ffc107'; 
                } elseif ($fine->treat_as === 'final') { 
                    $treatAsText = 'FINAL WARNING'; 
                    $treatColor = '#dc3545'; 
                }
            @endphp

            <div class="text-center mt-4 mb-4 ">
                <h4 class="text-uppercase text-decoration-underline ">{{ $noticeTitle }}</h4>
            </div>

            <!-- 2. EMPLOYEE DETAILS -->
            <div class="row mb-3">
                <div class="col-6">
                    <strong>Employee Name:</strong> {{ $fine->employee->full_name ?? $fine->employee->name ?? 'N/A' }}<br>
                    <strong>Employee ID:</strong> {{ $fine->employee->member_id ?? 'N/A' }}<br>                   
                    <strong>Department:</strong> {{ $fine->employee->department->department_name ?? 'N/A' }}<br>
                    <strong>Designation:</strong> {{ $designationName }}
                </div>
                <div class="col-6 text-end">
                    <strong>Record No:</strong> FP-{{ str_pad($fine->id, 5, '0', STR_PAD_LEFT) }}<br>
                    <strong>Date of Issue:</strong> {{ \Carbon\Carbon::parse($fine->date)->format('d M, Y') }}<br>
                    <strong>Action:</strong> <span style="color: {{ $treatColor }}; font-weight: bold;">{{ $treatAsText }}</span><br>
                    <strong>Status:</strong> 
                    <span style="color: {{ $fine->status === 'Approved' ? 'green' : 'orange' }}; font-weight: bold;">
                        {{ strtoupper($fine->status) }}
                    </span>
                </div>
            </div>

            <div class="section-title">Details of Charges</div>
            <table class="table table-bordered border-dark">
                <thead>
                    <tr>
                        <th>Charge Type</th>
                        <th>Amount (₹)</th>
                        <th>Deduction Days</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Fine</strong></td>
                        <td>{{ $fine->fine_rupees ? '₹' . number_format($fine->fine_rupees, 2) : '-' }}</td>
                        <td>
                            {{ $fine->fine_days ?? '-' }}
                            @if($fineDaysAmount > 0)
                                <br><small class="text-muted">(₹{{ number_format($fineDaysAmount, 2) }})</small>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Penalty</strong></td>
                        <td>{{ $fine->penalty_rupees ? '₹' . number_format($fine->penalty_rupees, 2) : '-' }}</td>
                        <td>
                            {{ $fine->penalty_days ?? '-' }}
                            @if($penaltyDaysAmount > 0)
                                <br><small class="text-muted">(₹{{ number_format($penaltyDaysAmount, 2) }})</small>
                            @endif
                        </td>
                    </tr>
                    <!-- Grand Total Row -->
                    <tr class="table-secondary">
                        <td class="text-end" style="vertical-align: middle;"><strong>Grand Total Amount:</strong></td>
                        <td colspan="2" class="text-center fw-bold fs-5">
                            ₹{{ number_format($totalAmount, 2) }}
                            @if($totalDays > 0)
                                <div class="fs-6 text-dark fw-normal mt-1">Total Deduction Days: {{ $totalDays }}</div>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- 📝 REMARKS / DESCRIPTION SECTION -->
            @if ($fine->description)
                <div class="section-title mt-3">Remarks / Description</div>
                <div class="p-2 border border-dark bg-light">
                    {!! $fine->description !!}
                </div>
            @endif

            <!-- 📸 PROOF SECTION -->

           
          
           <!-- Signature Section -->
            <div class="row mt-5 pt-5 align-items-end">
                <div class="col-6 text-center">
                    <!-- Placeholder div for equal height balance -->
                    <div style="min-height: 24px;"><strong> {{ $fine->employee->full_name ?? $fine->employee->name ?? 'N/A' }} ({{ $fine->employee->member_id ?? 'N/A' }}) </strong></div>
                    _______________________<br>
                    <strong>Employee Signature</strong>
                </div>
                <div class="col-6 text-center">
                    <div class="fw-bold" style="font-size: 16px; min-height: 24px;">{{ $signatoryName }}</div>
                    _______________________<br>
                    <strong>Authorized Signatory</strong>
                </div>
            </div>

             <!-- 📸 PROOF SECTION -->
            @if($hasProof)
                <div class="section-title mt-5 proof-section-title">Attached Proof</div>
                
                <!-- ID for Images Container -->
                <div id="proofImagesContainer" class="d-flex flex-wrap gap-2 justify-content-center">
                    @foreach($proofMediaList as $media)
                        <img src="{{ asset($media->file_path) }}" class="proof-img" alt="Proof Image">
                    @endforeach
                </div>

                <!-- ID for Text Container (Pehle se hidden rahega) -->
                <div id="proofTextContainer" class="p-3 border border-dark text-center fw-bold bg-light d-none">
                    <i class="fas fa-paperclip"></i> Proof Attached: Yes <br>
                    <small class="text-muted">(Images are hidden in this print copy)</small>
                </div>
            @endif

        </div>
    </div>

    <script>
        // Toggle Logic with Bootstrap Classes
        let toggleBtn = document.getElementById('toggleProof');
        if(toggleBtn) {
            toggleBtn.addEventListener('change', function() {
                const imgContainer = document.getElementById('proofImagesContainer');
                const textContainer = document.getElementById('proofTextContainer');
                
                if(this.checked) {
                    // Show Images, Hide Text
                    imgContainer.classList.remove('d-none');
                    imgContainer.classList.add('d-flex');
                    textContainer.classList.add('d-none');
                } else {
                    // Hide Images, Show Text
                    imgContainer.classList.remove('d-flex');
                    imgContainer.classList.add('d-none');
                    textContainer.classList.remove('d-none');
                }
            });
        }
    </script>
</body>

</html>
