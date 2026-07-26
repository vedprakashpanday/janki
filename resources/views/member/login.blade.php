<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Member Login | JankiVila</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #F7FAFC; font-family: 'Inter', sans-serif; }
        .login-card { max-width: 400px; margin: 80px auto; border-radius: 10px; box-shadow: 0 4px 12px rgba(26, 54, 93, 0.08); border: none; }
    </style>
</head>
<body>

<div class="container">
    <div class="card login-card p-4 bg-white">
        <div class="text-center mb-4">
            <img src="{{ asset('uploads/harihomes1-logo.png') }}" alt="Logo" height="50">
            <h5 class="mt-3 text-secondary">Member Portal Login</h5>
        </div>

        <form id="step1Form">
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Mobile Number / Email</label>
                <input type="text" id="login_id" class="form-control" placeholder="Enter Mobile or Email" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Password</label>
                <input type="password" id="password" class="form-control" placeholder="Enter Password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold" id="btnStep1">
                Send OTP <i class="fas fa-paper-plane ms-1"></i>
            </button>
        </form>

        <form id="step2Form" class="d-none">
            <div class="alert alert-success small text-center">
                <i class="fas fa-check-circle"></i> OTP sent successfully! (Hint: 123456)
            </div>
            <input type="hidden" id="hidden_member_id">
            
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Enter OTP</label>
                <input type="text" id="otp_code" class="form-control text-center fw-bold" placeholder="• • • • • •" required maxlength="6">
            </div>
            <button type="submit" class="btn btn-success w-100 fw-bold" id="btnStep2">
                Verify & Login <i class="fas fa-sign-in-alt ms-1"></i>
            </button>
            <button type="button" class="btn btn-link w-100 mt-2 text-decoration-none small" id="btnBack">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {

    // 1. UNIQUE DEVICE TOKEN GENERATOR (Ye hamesha browser me save rahega)
    function getDeviceToken() {
        let token = localStorage.getItem('unique_device_id');
        if (!token) {
            token = 'DEV-' + Math.random().toString(36).substr(2, 9) + '-' + Date.now();
            localStorage.setItem('unique_device_id', token);
        }
        return token;
    }

    // 2. BROWSER & OS DETECTOR
    function getSystemInfo() {
        let ua = navigator.userAgent;
        let browser = "Unknown", os = "Unknown";
        
        if (ua.indexOf("Firefox") > -1) browser = "Firefox";
        else if (ua.indexOf("Chrome") > -1) browser = "Chrome";
        else if (ua.indexOf("Safari") > -1) browser = "Safari";
        
        if (ua.indexOf("Win") > -1) os = "Windows";
        else if (ua.indexOf("Mac") > -1) os = "MacOS";
        else if (ua.indexOf("Android") > -1) os = "Android";
        else if (ua.indexOf("like Mac") > -1) os = "iOS";
        
        return { browser: browser, os: os };
    }

    // Global variables for location
    let userLat = null;
    let userLng = null;

    // 3. GET GPS LOCATION JAISE HI PAGE LOAD HO
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            userLat = position.coords.latitude;
            userLng = position.coords.longitude;
        }, function(error) {
            console.log("Location Denied or Unavailable: ", error);
            // Strict mode: Agar location allow karna zaroori karna hai toh yahan alert de sakte ho
        });
    }

    // Agar pehle se logged in hai toh direct redirect kar do
    if(localStorage.getItem('member_token')) {
        window.location.href = '/member/dashboard';
    }

    // Step 1: Login Request (Email/Mobile + Password)
    $('#step1Form').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnStep1');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Checking...');

        $.ajax({
            url: '/api/v1/member/auth/login-request',
            type: 'POST',
            data: {
                login_id: $('#login_id').val(),
                password: $('#password').val(),
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if(response.status === 'success') {
                    $('#hidden_member_id').val(response.member_id); // Save ID for Step 2
                    $('#step1Form').addClass('d-none');
                    $('#step2Form').removeClass('d-none');
                }
            },
            error: function(xhr) {
                let msg = xhr.responseJSON ? xhr.responseJSON.message : 'An error occurred';
                Swal.fire('Error', msg, 'error');
                btn.prop('disabled', false).html('Send OTP <i class="fas fa-paper-plane ms-1"></i>');
            }
        });
    });

   // 4. STEP 2 (OTP FORM SUBMIT) KO UPDATE KAREIN
    $('#step2Form').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnStep2');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Verifying Device & OTP...');

        let sysInfo = getSystemInfo();

        $.ajax({
            url: '/api/v1/member/auth/verify-otp',
            type: 'POST',
            data: {
                member_id: $('#hidden_member_id').val(),
                otp: $('#otp_code').val(),
                device_token: getDeviceToken(), // 🔥 Backend ko yahi se pehchan milegi
                latitude: userLat,
                longitude: userLng,
                browser: sysInfo.browser,
                os: sysInfo.os,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if(response.status === 'success') {
                    // Token aur Session ID localStorage me save kiya
                    localStorage.setItem('member_token', response.token);
                    localStorage.setItem('member_session_id', response.session_id); // 🔥 Logout ke time kaam aayega
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Secure Login Successful',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        window.location.href = '/member/dashboard';
                    });
                }
            },
            error: function(xhr) {
                let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Invalid OTP or Device Blocked';
                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: msg
                });
                btn.prop('disabled', false).html('Verify & Login <i class="fas fa-sign-in-alt ms-1"></i>');
            }
        });
    });



    // Back button logic
    $('#btnBack').on('click', function() {
        $('#step2Form').addClass('d-none');
        $('#step1Form').removeClass('d-none');
        $('#btnStep1').prop('disabled', false).html('Send OTP <i class="fas fa-paper-plane ms-1"></i>');
    });
});
</script>

</body>
</html>