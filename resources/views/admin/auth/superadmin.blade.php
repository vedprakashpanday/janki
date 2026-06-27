<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login | JankiVilla</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #F7FAFC;
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(26, 54, 93, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 40px 30px;
        }
        .brand-color { color: #1A365D; }
        .btn-brand {
            background-color: #1A365D;
            color: white;
            font-weight: 500;
        }
        .btn-brand:hover { background-color: #2A4365; color: white; }
        .form-control:focus {
            border-color: #D69E2E;
            box-shadow: 0 0 0 0.25rem rgba(214, 158, 46, 0.25);
        }
        /* OTP Form initially hidden */
        #otpSection { display: none; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <img src="{{ asset('uploads/harihomes1-logo.png') }}" alt="JankiVilla" height="50" class="mb-3">
            <h4 class="fw-bold brand-color">Super Admin Portal</h4>
            <p class="text-muted small">Secure Master Login</p>
        </div>

        <div id="alertBox" class="alert d-none" style="font-size: 13px;"></div>

        <form id="requestOtpForm">
            <div class="mb-4">
                <label class="form-label small fw-bold text-secondary">Email ID or Mobile Number</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-user-shield text-muted"></i></span>
                    <input type="text" id="login_id" class="form-control" placeholder="Enter Email or Mobile" required>
                </div>
            </div>
            <button type="submit" class="btn btn-brand w-100 py-2" id="sendOtpBtn">
                Send OTP <i class="fas fa-paper-plane ms-1"></i>
            </button>
        </form>

        <form id="verifyOtpForm">
            <div id="otpSection">
                <input type="hidden" id="admin_id">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Enter 6-Digit OTP</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-key text-muted"></i></span>
                        <input type="number" id="otp" class="form-control" placeholder="X X X X X X" required maxlength="6">
                    </div>
                    <small class="text-success mt-1 d-block"><i class="fas fa-check-circle me-1"></i> OTP sent to your email</small>
                </div>
                <button type="submit" class="btn btn-success w-100 py-2" id="verifyBtn">
                    Verify & Login <i class="fas fa-sign-in-alt ms-1"></i>
                </button>
                <div class="text-center mt-3">
                    <a href="#" class="small text-decoration-none" onclick="location.reload();">Back to Email</a>
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {

            function showAlert(message, type) {
                $('#alertBox').removeClass('d-none alert-danger alert-success alert-info')
                              .addClass('alert-' + type).text(message);
            }

            // Step 1: Request OTP API Call
            $('#requestOtpForm').on('submit', function(e) {
                e.preventDefault();
                let btn = $('#sendOtpBtn');
                btn.html('<i class="fas fa-spinner fa-spin"></i> Sending...').prop('disabled', true);
                
                $.ajax({
                    url: '/api/v1/admin/auth/super-admin/request-otp',
                    type: 'POST',
                    data: {
                        login_id: $('#login_id').val(),
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        showAlert(res.message, 'success');
                        $('#admin_id').val(res.admin_id); // Backend se aayi ID save kar li
                        $('#requestOtpForm').slideUp();   // Email form hide karo
                        $('#otpSection').slideDown();     // OTP form dikhao
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong!';
                        showAlert(msg, 'danger');
                        btn.html('Send OTP <i class="fas fa-paper-plane ms-1"></i>').prop('disabled', false);
                    }
                });
            });

            // Step 2: Verify OTP API Call
            $('#verifyOtpForm').on('submit', function(e) {
                e.preventDefault();
                let btn = $('#verifyBtn');
                btn.html('<i class="fas fa-spinner fa-spin"></i> Verifying...').prop('disabled', true);

                $.ajax({
                    url: '/api/v1/admin/auth/super-admin/verify-otp',
                    type: 'POST',
                    data: {
                        admin_id: $('#admin_id').val(),
                        otp: $('#otp').val(),
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        showAlert('Login successful! Redirecting...', 'success');
                        
                        // JankiVilla ke layout.app ko jo token chahiye wo set kar rahe hain
                        localStorage.setItem('admin_token', res.token);
                        
                        // Ab redirect kar denge dashboard par
                        setTimeout(() => {
                            window.location.href = '/admin/dashboard';
                        }, 1000);
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Invalid OTP!';
                        showAlert(msg, 'danger');
                        btn.html('Verify & Login <i class="fas fa-sign-in-alt ms-1"></i>').prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>
</html>