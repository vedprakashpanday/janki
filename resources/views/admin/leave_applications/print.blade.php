<!DOCTYPE html>
<html lang="en">

<head>
   
    <meta charset="UTF-8">
    @php
        $uName = $application->user_type === 'employee' ? ($application->employee->full_name ?? 'User') : ($application->member->full_name ?? 'User');
        $uCode = $application->user_type === 'employee' ? ($application->employee->member_id ?? '') : ($application->member->member_id ?? '');
        
        // Remove spaces and make it safe for file name
        $safeName = str_replace(' ', '_', strtoupper($uName));
        $appType = str_replace(' ', '_', strtoupper($application->application_type));
        $appDate = \Carbon\Carbon::parse($application->created_at)->format('d_m_Y');
        
        $docTitle = "{$safeName}_{$uCode}_{$appType}_{$appDate}";
    @endphp
    <title>{{ $docTitle }}</title>
   
    <link rel="shortcut icon" href="{{ asset('uploads/harihomes1-fevicon.png') }}" type="image/x-icon" id="dynamicFavicon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; box-sizing: border-box; }
            @page { size: A4 portrait; margin: 0; }
            body { background-color: #fff !important; margin: 0; padding: 10mm 15mm; }
            .no-print { display: none !important; }
            .print-container { width: 100%; max-width: 100%; border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; }
        }
        body { font-family: 'Arial', sans-serif; background-color: #f4f4f4; margin: 0; padding: 10px; color: #000; }
        .print-container { max-width: 900px; margin: 0 auto; background: #fff; padding: 15px; border: 1px solid #ddd; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
    </style>
</head>

<body>
    @php
        // Strict check to see if valid files exist
        $hasAttachments = !empty($application->proof_attachments) && is_array($application->proof_attachments) && count(array_filter($application->proof_attachments)) > 0;
    @endphp

    <div class="no-print d-flex justify-content-end align-items-center gap-3 mb-4" style="max-width: 900px; margin: 0 auto;">
        
        @if($hasAttachments)
        <div class="border rounded px-3 py-2 bg-white d-flex align-items-center shadow-sm">
            <span class="fw-bold me-3" style="color: #1A365D; font-size: 14px;">Include Proofs in Print</span>
            <div class="form-check form-switch mb-0 fs-5">
                <input class="form-check-input" type="checkbox" id="printAttachmentsToggle" style="cursor: pointer;">
            </div>
        </div>
        @endif

        <button onclick="window.print()" class="btn" style="background-color: #1A365D; color: #fff; padding: 8px 20px; font-weight: 500;">
            <i class="fas fa-print me-1"></i> Print Document
        </button>
    </div>

    <div class="print-container">
        @include('admin.leave_applications.view_partial', ['app' => $application, 'company' => $application->company, 'branch' => $application->branch])
    </div>

    <script>
        let toggleBtn = document.getElementById('printAttachmentsToggle');
        if(toggleBtn) {
            toggleBtn.addEventListener('change', function() {
                let attachDiv = document.getElementById('print-attachments-section');
                let labelBox = document.getElementById('attachment-label-box'); // Chhota label

                if(attachDiv) {
                    if(this.checked) {
                        attachDiv.classList.remove('no-print');
                        // Agar actual files print ho rahi hain, to chhote label ka kya kaam? Use chhupa do
                        if(labelBox) labelBox.classList.add('no-print');
                    } else {
                        attachDiv.classList.add('no-print');
                        // Wapas un-check kare toh chhota label dikha do
                        if(labelBox) labelBox.classList.remove('no-print');
                    }
                }
            });
        }
    </script>
</body>

</html>
