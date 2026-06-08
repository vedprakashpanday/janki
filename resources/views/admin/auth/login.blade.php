<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - JankiVilla</title>
    @vite(['resources/js/app.js'])

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            /* Linear gradient for dark overlay + Business/Real Estate Background Image */
            background: linear-gradient(rgba(15, 23, 42, 0.7), rgba(15, 23, 42, 0.8)),
                url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=2070&auto=format&fit=crop') no-repeat center center;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        /* Glassmorphism Effect for Card */
        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 40px 30px;
        }

        .brand-icon {
            width: 300px;
            height: 80px;
            color: white;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 15px;
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.3);
        }

        /* Styling Inputs with Icons */
        .input-group-text {
            background-color: transparent;
            border-right: none;
            color: #6c757d;
        }

        .form-control {
            border-left: none;
            padding-left: 0;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #dee2e6;
        }

        .input-group:focus-within {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
            border-radius: 0.375rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            border: none;
            border-radius: 10px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.4);
        }
    </style>
</head>

<body>

    <div class="login-card mx-3">
        <div class="brand-icon">
            <img src="{{ asset('uploads/harihomes1-logo.png') }}" alt="" style="width: 250px; height: 70px;">
        </div>

        <form id="loginForm">
            <div class="mb-3">
                <label class="form-label small fw-medium text-secondary">Email Address</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control fs-6" id="email" placeholder="admin@jankivilla.com"
                        required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-medium text-secondary">Secure Password</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control fs-6" id="password" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 mt-2" id="loginBtn">
                Secure Login <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </form>

        <div id="messageBox" class="mt-4 text-center d-none" style="font-size: 14px;"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="module">
        // 1. Unique Session ID generate karo
        const currentLoginSessionId = 'jv_' + Math.random().toString(36).substr(2, 9);

        // --- FORM SUBMISSION LOGIC ---
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();

            const data = {
                email: $('#email').val(),
                password: $('#password').val(),
                session_id: currentLoginSessionId
            };

            // UI ko waiting mode me daalo aur purane messages hatao
            $('#loginBtn').html('<i class="fas fa-spinner fa-spin me-2"></i> Requesting...').prop('disabled', true);
            $('#messageBox').addClass('d-none');

            // AJAX call with Success & Error handling
            $.ajax({
                url: '/api/v1/admin/auth/login-request', // 🔥 FIXED: Aapke routing structure ke hisaab se
                type: 'POST',
                data: data,
                success: function(response) {
                    // Agar credentials sahi hain aur mail chala gaya
                    $('#messageBox').removeClass('d-none').html(
                        '<div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-warning-emphasis"><i class="fas fa-envelope-open-text me-2"></i>Waiting for Admin approval...</div>'
                        );
                },
                error: function(xhr) {
                    // Agar email/password galat hai, toh button wapas normal karo
                    $('#loginBtn').html('Secure Login <i class="fas fa-arrow-right ms-2"></i>').prop(
                        'disabled', false);

                    // Backend se error message nikalo (e.g., "Invalid credentials")
                    let errorMessage = "Something went wrong. Please try again.";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    // Error message UI par dikhao
                    $('#messageBox').removeClass('d-none').html(
                        '<div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger"><i class="fas fa-exclamation-circle me-2"></i>' +
                        errorMessage + '</div>');
                }
            });
        });

        // --- REVERB LISTENER LOGIC ---
        window.Echo.channel('login-status.' + currentLoginSessionId)
            .listen('.LoginApproved', (data) => {

                if (data.status === 'approved') {
                    $('#messageBox').html(
                        '<div class="alert alert-success border-0 bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle me-2"></i>Access Granted! Redirecting...</div>'
                        );

                    // 🔥 NAYA LOGIC: Token ke saath user ki details bhi save karo 🔥
                    localStorage.setItem('admin_token', data.token);

                    // Frontend ko role-based banana ke liye user detail save karna zaroori hai
                    if (data.user) {
                        localStorage.setItem('user_email', data.user.email || '');
                        localStorage.setItem('user_role', data.user.role || ''); // Backend se role aana chahiye
                        localStorage.setItem('user_name', data.user.name || '');
                        localStorage.setItem('member_id', data.user.member_id || '');
                        localStorage.setItem('designation', data.user.designation || '');
                    }

                    setTimeout(() => {
                        window.location.href = '/admin/dashboard';
                    }, 1000);
                } else if (data.status === 'rejected') {
                    $('#messageBox').html(
                        '<div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger"><i class="fas fa-times-circle me-2"></i>Access Denied by Administrator.</div>'
                        );
                    $('#loginBtn').html('Secure Login <i class="fas fa-arrow-right ms-2"></i>').prop('disabled', false);
                }

            });
    </script>
</body>

</html>
