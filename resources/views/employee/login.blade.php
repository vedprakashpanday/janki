<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal | Secure Login</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* 🚀 Premium UI Styling 🚀 */

        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            /* Prevent unwanted scrolling caused by decorative elements */
            overflow-x: hidden;
        }

        /* Decorative Background Elements - Changed to FIXED so they don't cause scrolling */
        body::before {
            content: '';
            position: fixed;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(214, 158, 46, 0.1) 0%, rgba(214, 158, 46, 0) 70%);
            top: -100px;
            left: -100px;
            border-radius: 50%;
            z-index: -1;
        }

        body::after {
            content: '';
            position: fixed;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(26, 54, 93, 0.08) 0%, rgba(26, 54, 93, 0) 70%);
            bottom: -150px;
            right: -100px;
            border-radius: 50%;
            z-index: -1;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08), 0 5px 15px rgba(0, 0, 0, 0.03);
            padding: 45px 40px;
            width: 100%;
            max-width: 420px;
            z-index: 1;
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        /* Mobile specific adjustments inside the Bootstrap wrapper */
        @media (max-width: 480px) {
            .login-card {
                padding: 35px 25px;
                /* Thinner padding for smaller screens */
                border-radius: 16px;
            }
        }

        .brand-logo {
            width: 130px;
            margin-bottom: 25px;
            display: block;
            margin-left: auto;
            margin-right: auto;
            transition: transform 0.3s ease;
        }

        .brand-logo:hover {
            transform: scale(1.05);
        }

        .step-section {
            display: none;
        }

        .step-section.active {
            display: block;
            animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Input Fields Styling */
        .input-group-text {
            background: transparent;
            border-right: none;
            border-radius: 10px 0 0 10px;
            color: #a0aec0;
        }

        .form-control-custom {
            border-left: none;
            border-radius: 0 10px 10px 0;
            font-weight: 500;
            color: #2d3748;
            background: #f7fafc;
            font-size: 16px;
            /* Prevents auto-zoom on iOS Safari */
        }

        .form-control-custom:focus {
            box-shadow: none;
            background: #ffffff;
            border-color: #cbd5e1;
        }

        .input-group {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            transition: all 0.2s ease;
            background: #f7fafc;
            overflow: hidden;
        }

        .input-group:focus-within {
            border-color: #D69E2E;
            box-shadow: 0 0 0 3px rgba(214, 158, 46, 0.15);
            background: #ffffff;
        }

        .input-group:focus-within .input-group-text {
            color: #D69E2E;
        }

        /* Button Styling */
        .btn-custom {
            border-radius: 10px;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            font-size: 1rem;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-primary-theme {
            background: linear-gradient(135deg, #1A365D 0%, #2a4365 100%);
            border: none;
            color: white;
        }

        .btn-warning-theme {
            background: linear-gradient(135deg, #D69E2E 0%, #ecc94b 100%);
            border: none;
            color: white;
        }

        .btn-success-theme {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            border: none;
            color: white;
        }

        .mock-otp-box {
            background: #ebf8ff;
            color: #2b6cb0;
            padding: 12px;
            border-radius: 10px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid #bee3f8;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .badge-secure {
            background: #edf2f7;
            color: #4a5568;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
    </style>
</head>

<body>

    <!-- Bootstrap Wrapper for Perfect Centering on All Devices -->
    <div class="d-flex justify-content-center align-items-center min-vh-100 p-3">

        <div class="login-card">
            <div class="text-center mb-4">
                <img src="{{ asset('uploads/harihomes1-logo.png') }}" alt="Logo" class="brand-logo">
                <span class="badge-secure mb-3 d-inline-block"><i class="fas fa-shield-alt text-success me-1"></i>
                    Secure
                    Zero-Trust Access</span>
                <h5 class="fw-bold mb-0" style="color: #1A365D;">Employee Workspace</h5>
            </div>

            <!-- STEP 1: PANEL ID -->
            <div id="step-1-id" class="step-section active">
                <form id="verifyIdForm">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary small mb-1">Enter Panel ID</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                            <input type="text" class="form-control form-control-custom" id="panel_id_input"
                                placeholder="e.g. EMP-123456" required autocomplete="on">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-warning-theme btn-custom w-100 py-3 fw-bold" id="btnNextId">
                        Continue to Workspace <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </form>
            </div>

            <!-- STEP 2: BIND DEVICE (PASSWORD) -->
            <div id="step-2-password" class="step-section">
                <div class="alert alert-warning small border-0"
                    style="background: #fffaf0; color: #c05621; border-radius: 10px;">
                    <i class="fas fa-laptop-house me-2 fs-5 float-start mt-1"></i>
                    <strong>First time login!</strong><br>This device will be securely locked to your Panel ID.
                </div>
                <form id="bindDeviceForm">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary small mb-1">Panel Password</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control form-control-custom" id="panel_password_input"
                                placeholder="Enter given password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary-theme btn-custom w-100 py-3 fw-bold"
                        id="btnBindDevice">
                        <i class="fas fa-link me-2"></i> Bind Device & Login
                    </button>
                    <button type="button"
                        class="btn btn-link w-100 mt-3 text-muted small fw-bold text-decoration-none back-btn">
                        <i class="fas fa-arrow-left me-1"></i> Back to ID
                    </button>
                </form>
            </div>

            <!-- STEP 3: OTP VERIFICATION -->
            <div id="step-3-otp" class="step-section">
                <div class="alert alert-success small border-0 d-flex align-items-center"
                    style="background: #f0fdf4; color: #166534; border-radius: 10px;">
                    <i class="fas fa-check-circle fs-4 me-2"></i>
                    <div>Device verified successfully. Please enter the OTP.</div>
                </div>

                <!-- 🛠 BYPASS OTP BOX 🛠 -->
                <div class="mock-otp-box d-none" id="mockOtpBox"></div>

                <form id="verifyOtpForm">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary small mb-1">Enter 6-Digit OTP</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="text" class="form-control form-control-custom text-center fw-bold"
                                style="letter-spacing: 5px; font-size: 1.2rem;" id="panel_otp_input" maxlength="6"
                                placeholder="------" required autocomplete="off">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success-theme btn-custom w-100 py-3 fw-bold shadow-sm"
                        id="btnVerifyOtp">
                        <i class="fas fa-fingerprint me-2"></i> Verify & Enter Dashboard
                    </button>

                    <div class="d-flex justify-content-between align-items-center mt-4 px-1">
                        <small class="text-muted fw-medium">Didn't receive it?</small>
                        <a href="#" id="resendOtpBtn" class="text-decoration-none small fw-bold text-muted"
                            style="pointer-events: none; transition: 0.2s;">
                            Resend OTP in <span id="resendTimer">60</span>s
                        </a>
                    </div>

                    <hr class="text-muted opacity-25 my-3">
                    <button type="button"
                        class="btn btn-link w-100 text-muted small fw-bold text-decoration-none back-btn">
                        Cancel & Go Back
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- JavaScript block exactly untouched -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {

            // 1. Device Fingerprint (Token) Generator
            let deviceToken = localStorage.getItem('emp_device_token');
            if (!deviceToken) {
                deviceToken = 'DEV-' + Math.random().toString(36).substr(2, 16) + Date.now().toString(36);
                localStorage.setItem('emp_device_token', deviceToken);
            }

            let currentPanelId = '';

            // ==========================================
            // STEP 1: Verify ID with Location
            // ==========================================
            $('#verifyIdForm').submit(function(e) {
                e.preventDefault();
                let panelId = $('#panel_id_input').val();
                let btn = $('#btnNextId');
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span> Checking...');

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            sendVerifyRequest(panelId, position.coords.latitude, position.coords
                                .longitude, btn);
                        },
                        function(error) {
                            sendVerifyRequest(panelId, null, null, btn);
                        }
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

                        if (res.status === 'require_password') {
                            $('#step-2-password').addClass('active');
                        } else if (res.status === 'require_otp') {
                            $('#step-3-otp').addClass('active');

                            // Show Mock OTP if available (Bypass Logic Active)
                            if (res.mock_otp) {
                                $('#mockOtpBox').html(
                                    '<i class="fas fa-unlock-alt me-2"></i> Bypass OTP: <span class="fs-5 ms-1">' +
                                    res.mock_otp + '</span>').removeClass('d-none');
                            }

                            startResendTimer();
                        }
                    },
                    error: function(err) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Access Denied',
                            text: err.responseJSON.message ||
                                "Invalid Panel ID or inactive account."
                        });
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            'Continue to Workspace <i class="fas fa-arrow-right ms-2"></i>');
                    }
                });
            }

            // Back Buttons Action
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

                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span> Binding...');

                $.ajax({
                    url: '/api/v1/employee/bind-device',
                    type: 'POST',
                    data: {
                        panel_id: currentPanelId,
                        panel_password: password,
                        device_token: deviceToken
                    },
                    success: function(res) {
                        localStorage.setItem('emp_token', res.emp_token);
                        localStorage.setItem('emp_panel_id', currentPanelId);

                        Swal.fire({
                            icon: 'success',
                            title: 'Welcome!',
                            text: res.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.href = '/employee/dashboard';
                        });
                    },
                    error: function(err) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Password',
                            text: err.responseJSON.message ||
                                "Please check your password and try again."
                        });
                        $('#panel_password_input').val('');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fas fa-link me-2"></i> Bind Device & Login');
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

                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span> Verifying...');

                $.ajax({
                    url: '/api/v1/employee/verify-otp',
                    type: 'POST',
                    data: {
                        panel_id: currentPanelId,
                        panel_otp: otp,
                        device_token: deviceToken
                    },
                    success: function(res) {
                        localStorage.setItem('emp_token', res.emp_token);
                        localStorage.setItem('emp_panel_id', currentPanelId);

                        Swal.fire({
                            icon: 'success',
                            title: 'Login Successful',
                            text: "Redirecting to your workspace...",
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.href = '/employee/dashboard';
                        });
                    },
                    error: function(err) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Verification Failed',
                            text: err.responseJSON.message ||
                                "The OTP entered is invalid or expired."
                        });
                        $('#panel_otp_input').val('');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fas fa-fingerprint me-2"></i> Verify & Enter Dashboard'
                        );
                    }
                });
            });

            // ==========================================
            // RESEND OTP TIMER & ACTION
            // ==========================================
            let timerInterval;

            function startResendTimer() {
                let timeLeft = 60;
                let btn = $('#resendOtpBtn');

                btn.addClass('text-muted').removeClass('text-primary').css('pointer-events', 'none');

                clearInterval(timerInterval);
                timerInterval = setInterval(function() {
                    timeLeft--;
                    btn.html(`Resend OTP in <span id="resendTimer" class="fw-bold">${timeLeft}</span>s`);

                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        btn.html('Resend OTP Now');
                        btn.removeClass('text-muted').addClass('text-primary').css('pointer-events',
                            'auto');
                    }
                }, 1000);
            }

            $('#resendOtpBtn').on('click', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Sending new OTP...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '/api/v1/employee/resend-otp',
                    type: 'POST',
                    data: {
                        panel_id: currentPanelId
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sent!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Update Bypass OTP Box if backend sends it
                        if (res.mock_otp) {
                            $('#mockOtpBox').html(
                                '<i class="fas fa-unlock-alt me-2"></i> Bypass OTP: <span class="fs-5 ms-1">' +
                                res.mock_otp + '</span>').removeClass('d-none');
                        }
                        startResendTimer();
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON ? xhr.responseJSON.message :
                            'Error sending OTP';
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: msg
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>
