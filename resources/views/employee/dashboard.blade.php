@extends('layout.app')

@section('content')
    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color:var(--sidebar-bg);"><i class="fas fa-layer-group text-primary me-2"></i>
                    Employee Workspace</h4>
                <p class="text-secondary small mb-0">Welcome to your secure access panel.</p>
            </div>

            <button class="btn btn-danger px-4 py-2 shadow-sm fw-bold" id="btnLogout" style="border-radius: 8px;">
                <i class="fas fa-sign-out-alt me-1"></i> Secure Logout
            </button>
        </div>

        <div class="row mt-5">
            <div class="col-12 text-center">
                <div class="card border-0 shadow-sm d-inline-block text-start p-4"
                    style="min-width: 380px; border-radius: 12px; background: #f8fafc;">
                    <div id="attendanceBox">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary me-3"></i>
                            <h5 class="fw-bold text-dark mb-0">Syncing Work Profile...</h5>
                        </div>
                        <p class="mb-0 small text-secondary mt-2">Please wait while we capture your secure access
                            coordinates and initialize your dynamic modules.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // 1. Verify Security Token
            let empToken = localStorage.getItem('emp_token');
            let currentPanelId = localStorage.getItem('emp_panel_id'); // Login page se set hoga

            if (!empToken) {
                window.location.href = '/employee/login';
                return; // Code aage run na ho
            }

            // 2. AUTO ATTENDANCE LOGIC (With Geolocation)
            function markAttendance(lat = null, long = null) {
                $.ajax({
                    url: '/api/v1/employee/mark-attendance',
                    type: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + empToken
                    }, // Sanctum token bhejna zaroori hai
                    data: {
                        panel_id: currentPanelId,
                        latitude: lat,
                        longitude: long
                    },
                    success: function(res) {
                        $('#attendanceBox').html(`
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-check-circle fa-2x text-success me-3"></i>
                                <h5 class="fw-bold text-success mb-0">Access Verified!</h5>
                            </div>
                            <p class="mb-0 small text-dark mt-2 fw-medium">${res.message || 'Attendance synced successfully.'}</p>
                        `);
                    },
                    error: function(err) {
                        $('#attendanceBox').html(`
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-exclamation-triangle fa-2x text-warning me-3"></i>
                                <h5 class="fw-bold text-warning mb-0">Sync Warning</h5>
                            </div>
                            <p class="mb-0 small text-dark mt-2">Login successful, but background attendance sync failed.</p>
                        `);
                    }
                });
            }

            // Fetch Location & Call Attendance
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        markAttendance(position.coords.latitude, position.coords.longitude);
                    },
                    function(error) {
                        console.log("Location denied:", error);
                        markAttendance(null, null);
                    }
                );
            } else {
                markAttendance(null, null); // Browser purana hai
            }

            // 3. LOGOUT LOGIC
            $('#btnLogout').click(function() {
                // Optional: Yahan backend logout API bhi call kar sakte hain
                localStorage.removeItem('emp_token');
                localStorage.removeItem('emp_panel_id');
                window.location.href = '/employee/login';
            });


            // ==========================================
            // 🛡️ ZERO-TRUST SECURITY: HYBRID AUTO-LOGOUT
            // ==========================================

            // A. INACTIVITY TRACKER (15 Minutes Idle)
            let idleTime = 0;
            const maxIdleMinutes = 15;

            let idleInterval = setInterval(function() {
                idleTime++;
                if (idleTime >= maxIdleMinutes) {
                    clearInterval(idleInterval);
                    Swal.fire({
                        title: 'Session Expired!',
                        text: '15 minutes of inactivity detected. Logging out for security.',
                        icon: 'warning',
                        confirmButtonText: 'Okay',
                        allowOutsideClick: false
                    }).then(() => {
                        $('#btnLogout').click();
                    });
                }
            }, 60000); // Check every 1 minute

            // Reset idle timer on any physical interaction
            $(document).on('mousemove keydown scroll click', function() {
                idleTime = 0;
            });

            // B. HARD SHIFT LIMIT TRACKER (18:15 Auto-Logout)
            setInterval(function() {
                let now = new Date();
                let hours = now.getHours();
                let minutes = now.getMinutes();

                // Agar time 18:15 (6:15 PM) ya uske baad ka hai
                if (hours > 18 || (hours === 18 && minutes >= 15)) {
                    Swal.fire({
                        title: 'Shift Over!',
                        text: 'Your shift limit (18:15) has been reached. System is auto-logging out.',
                        icon: 'info',
                        confirmButtonText: 'Understood',
                        allowOutsideClick: false
                    }).then(() => {
                        $('#btnLogout').click();
                    });
                }
            }, 60000);

        });
    </script>
@endpush
