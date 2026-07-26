<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal | Secure Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .login-card { background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 40px; width: 100%; max-width: 400px; }
        .brand-logo { width: 150px; margin-bottom: 30px; display: block; margin-left: auto; margin-right: auto; }
        .step-section { display: none; }
        .step-section.active { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .mock-otp-box { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 6px; font-weight: bold; text-align: center; margin-bottom: 15px; border: 1px dashed #bee5eb; }
    </style>
</head>
<body>

<div class="login-card">
    <img src="{{ asset('uploads/harihomes1-logo.png') }}" alt="Logo" class="brand-logo">
    <h5 class="text-center fw-bold mb-1" style="color: #1A365D;">Employee Workspace</h5>
    <p class="text-center text-muted small mb-4">Secure Zero-Trust Access</p>

    <div id="step-1-id" class="step-section active">
        <form id="verifyIdForm">
            <div class="mb-3">
                <label class="form-label fw-bold text-secondary small">Panel ID</label>
                <input type="text" class="form-control form-control-lg bg-light" id="panel_id_input" placeholder="e.g. EMP-123456" required>
            </div>
            <button type="submit" class="btn text-white w-100 py-2 fw-bold" style="background-color: #D69E2E;" id="btnNextId">Next <i class="fas fa-arrow-right ms-1"></i></button>
        </form>
    </div>

    <div id="step-2-password" class="step-section">
        <div class="alert alert-warning small"><i class="fas fa-laptop-house me-1"></i> First time login. This device will be permanently locked to your ID.</div>
        <form id="bindDeviceForm">
            <div class="mb-3">
                <label class="form-label fw-bold text-secondary small">Panel Password</label>
                <input type="password" class="form-control form-control-lg bg-light" id="panel_password_input" placeholder="Enter given password" required>
            </div>
            <button type="submit" class="btn text-white w-100 py-2 fw-bold" style="background-color: #1A365D;" id="btnBindDevice">Bind Device & Login</button>
            <button type="button" class="btn btn-link w-100 mt-2 text-muted small back-btn">Go Back</button>
        </form>
    </div>

    <div id="step-3-otp" class="step-section">
        <div class="alert alert-success small"><i class="fas fa-check-circle me-1"></i> Device verified. Enter OTP sent to your email.</div>
        
        {{-- <div class="mock-otp-box d-none" id="mockOtpBox"></div> --}}

        <form id="verifyOtpForm">
            <div class="mb-3">
                <label class="form-label fw-bold text-secondary small">Enter 6-Digit OTP</label>
                <input type="text" class="form-control form-control-lg bg-light text-center fw-bold" id="panel_otp_input" maxlength="6" placeholder="------" required>
            </div>
            <button type="submit" class="btn text-white w-100 py-2 fw-bold" style="background-color: #10b981;" id="btnVerifyOtp">Verify & Enter Dashboard</button>
            <div class="d-flex justify-content-between align-items-center mb-3">
    <small class="text-muted">Didn't receive it?</small>
    <a href="#" id="resendOtpBtn" class="text-decoration-none small fw-bold text-muted" style="pointer-events: none;">
        Resend OTP in <span id="resendTimer">60</span>s
    </a>
</div>
            <button type="button" class="btn btn-link w-100 mt-2 text-muted small back-btn">Cancel</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    
    // 1. Device Fingerprint (Token) Generator
    // Agar browser me token nahi hai, toh ek naya secure token generate karke hamesha ke liye save kar do
    let deviceToken = localStorage.getItem('emp_device_token');
    if (!deviceToken) {
        deviceToken = 'DEV-' + Math.random().toString(36).substr(2, 16) + Date.now().toString(36);
        localStorage.setItem('emp_device_token', deviceToken);
    }

    let currentPanelId = '';

// Step 1: Verify ID with Location
    $('#verifyIdForm').submit(function(e) {
        e.preventDefault();
        let panelId = $('#panel_id_input').val();
        let btn = $('#btnNextId');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Checking...');

        // Location mangna
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) { sendVerifyRequest(panelId, position.coords.latitude, position.coords.longitude, btn); },
                function(error) { sendVerifyRequest(panelId, null, null, btn); } // Deny kiya toh null
            );
        } else {
            sendVerifyRequest(panelId, null, null, btn);
        }
    });

    function sendVerifyRequest(panelId, lat, lng, btn) {
        $.ajax({
            url: '/api/v1/employee/verify-id',
            type: 'POST',
            data: { 
                panel_id: panelId, 
                device_token: deviceToken,
                latitude: lat,
                longitude: lng
            },
            success: function(res) {
                currentPanelId = panelId;
                $('.step-section').removeClass('active');
                if (res.status === 'require_password') { $('#step-2-password').addClass('active'); } 
                else if (res.status === 'require_otp') { 
                    $('#step-3-otp').addClass('active');
                    startResendTimer()
                    // $('#mockOtpBox').html('Bypass OTP: ' + res.mock_otp).removeClass('d-none');
                }
            },
            error: function(err) {
                alert(err.responseJSON.message || "Access Denied.");
            },
            complete: function() { btn.prop('disabled', false).html('Next <i class="fas fa-arrow-right ms-1"></i>'); }
        });
    }
    // Back Buttons
    $('.back-btn').click(function() {
        $('.step-section').removeClass('active');
        $('#step-1-id').addClass('active');
        $('#panel_password_input, #panel_otp_input').val('');
        $('#mockOtpBox').addClass('d-none');
    });

   // ==========================================
    // STEP 2: SUBMIT PASSWORD (BIND DEVICE)
    // ==========================================
    $('#bindDeviceForm').submit(function(e) {
        e.preventDefault();
        let password = $('#panel_password_input').val();
        let btn = $('#btnBindDevice');
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Binding Device...');

        $.ajax({
            url: '/api/v1/employee/bind-device',
            type: 'POST',
            data: { 
                panel_id: currentPanelId, 
                panel_password: password, 
                device_token: deviceToken 
            },
            success: function(res) {
                // Token aur Panel ID save karo
                localStorage.setItem('emp_token', res.emp_token);
                
                // 🔥 NAYI LINE: Panel ID save kar rahe hain 🔥
                localStorage.setItem('emp_panel_id', currentPanelId); 
                
                alert(res.message);
                window.location.href = '/employee/dashboard';
            },
            error: function(err) {
                alert(err.responseJSON.message || "Invalid Password");
                $('#panel_password_input').val(''); // Clear input
            },
            complete: function() {
                btn.prop('disabled', false).html('Bind Device & Login');
            }
        });
    });

    // ==========================================
    // STEP 3: SUBMIT OTP (REGULAR LOGIN)
    // ==========================================
    $('#verifyOtpForm').submit(function(e) {
        e.preventDefault();
        let otp = $('#panel_otp_input').val();
        let btn = $('#btnVerifyOtp');
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Verifying...');

        $.ajax({
            url: '/api/v1/employee/verify-otp',
            type: 'POST',
            data: { 
                panel_id: currentPanelId, 
                panel_otp: otp, 
                device_token: deviceToken 
            },
           success: function(res) {
                // Token aur Panel ID save karo
                localStorage.setItem('emp_token', res.emp_token);
                
                // 🔥 NAYI LINE: Panel ID save kar rahe hain 🔥
                localStorage.setItem('emp_panel_id', currentPanelId); 
                
                alert(res.message);
                window.location.href = '/employee/dashboard';
            },
            error: function(err) {
                alert(err.responseJSON.message || "Invalid OTP");
                $('#panel_otp_input').val('');
            },
            complete: function() {
                btn.prop('disabled', false).html('Verify & Enter Dashboard');
            }
        });
    });

   let timerInterval;

        function startResendTimer() {
            let timeLeft = 60; 
            let btn = $('#resendOtpBtn');
            
            btn.addClass('text-muted').removeClass('text-success').css('pointer-events', 'none');
            
            clearInterval(timerInterval);
            timerInterval = setInterval(function() {
                timeLeft--;
                
                // 🔥 FIX: Directly text update karein taaki span gayab na ho
                btn.html(`Resend OTP in <span id="resendTimer">${timeLeft}</span>s`);

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    btn.html('Resend OTP Now');
                    btn.removeClass('text-muted').addClass('text-success').css('pointer-events', 'auto');
                }
            }, 1000);
        }

        // Jab user OTP request kare ya page par OTP block show ho, toh ye call karein:
        // startResendTimer(); <-- Isko wahan daal dein jahan API 'require_otp' return karti hai.

        // 🔥 Resend Button Click Event 🔥
        $('#resendOtpBtn').on('click', function(e) {
            e.preventDefault();
            
            // Note: Make sure aapke paas panel_id variable available ho JS me
            let panelId = $('#panel_id_input').val(); // Apne according panel_id ka variable use karein

            Swal.fire({ title: 'Sending...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            $.ajax({
                url: '/api/v1/employee/resend-otp',
                type: 'POST',
                data: { panel_id: panelId },
                success: function(res) {
                    Swal.fire('Sent!', res.message, 'success');
                    startResendTimer(); // Dobara 60 sec ka timer chalu karein
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Error sending OTP';
                    Swal.fire('Error', msg, 'error');
                }
            });
        });


});
</script>
</body>
</html>