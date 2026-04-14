<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Action Processed - JankiVilla Workspace</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f8fafc; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            margin: 0;
            color: #334155;
        }
        
        .response-card { 
            background: #ffffff; 
            padding: 40px 30px; 
            border-radius: 16px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.05); 
            border: 1px solid #e2e8f0; 
            max-width: 420px; 
            width: 100%; 
            text-align: center; 
        }

        .status-icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin: 0 auto 24px;
        }

        .icon-success {
            background-color: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .icon-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .btn-primary-custom {
            background-color: #2563eb;
            border: none;
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            background-color: #1d4ed8;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        /* Pulse Animation for the close button when timer ends */
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(37, 99, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
        }
        
        .btn-pulse {
            animation: pulse-ring 2s infinite;
        }
    </style>
</head>
<body>

    <div class="response-card mx-3">
        
        @if($status == 'approved')
            <div class="status-icon-wrapper icon-success">
                <i class="fas fa-check"></i>
            </div>
            <h3 class="fw-bold text-dark mb-2">Request Approved</h3>
        @else
            <div class="status-icon-wrapper icon-danger">
                <i class="fas fa-times"></i>
            </div>
            <h3 class="fw-bold text-dark mb-2">Request Denied</h3>
        @endif
        
        <p class="text-secondary mb-4">{{ $message }}</p>
        
        <button id="closeBtn" class="btn btn-primary btn-primary-custom w-100" onclick="window.close()">
            Close Window (<span id="timer">5</span>s)
        </button>

        <p class="small text-muted mt-4 mb-0" style="font-size: 12px;">
            <i class="fas fa-shield-alt me-1"></i> Due to browser security policies, auto-close may be prevented. Please click the button above to return to your inbox.
        </p>
    </div>

    <script>
        let seconds = 5;
        const timerElement = document.getElementById('timer');
        const closeButton = document.getElementById('closeBtn');

        const countdown = setInterval(() => {
            seconds--;
            timerElement.innerText = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdown);
                timerElement.parentElement.innerHTML = "Close Window Now";
                
                // Attempt to auto-close the tab
                window.close();
                
                // Highlight the button if auto-close was blocked by the browser
                setTimeout(() => {
                    closeButton.classList.add('btn-pulse');
                }, 500);
            }
        }, 1000);
    </script>
</body>
</html>