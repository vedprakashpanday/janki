@extends('layout.app')

@section('content')
    <!-- 🟢 Custom CSS for Calendar -->
    <style>
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            text-align: center;
            margin-top: 10px;
        }

        .calendar-header {
            font-weight: 600;
            background: #f1f5f9;
            padding: 8px 0;
            font-size: 13px;
            color: #475569;
            border-radius: 4px;
        }

        .calendar-cell {
            border: 1px solid #e2e8f0;
            min-height: 80px;
            position: relative;
            border-radius: 6px;
            background: #ffffff;
            padding: 25px 5px 10px 5px;
            transition: 0.2s;
        }

        .calendar-cell.empty {
            background: transparent;
            border: none;
        }

        .calendar-cell.disabled-date {
            background: #f8fafc;
            color: #94a3b8;
        }

        .calendar-cell .date-number {
            position: absolute;
            top: 5px;
            left: 8px;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
        }

        .calendar-cell.disabled-date .date-number {
            color: #cbd5e1;
        }

        .status-btn {
            font-size: 10px;
            padding: 3px 6px;
            width: 100%;
            cursor: pointer;
            font-weight: 600;
            border: none;
            border-radius: 4px;
        }
    </style>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="fw-bold" style="color: var(--sidebar-bg);">Member Dashboard</h4>
                <p class="text-muted">Swagat hai aapka JankiVila member portal mein.</p>
            </div>
        </div>

        <div class="row">
            <!-- Profile Card -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0 h-100" style="border-radius: 12px;">
                    <div class="card-body text-center">
                        <img src="https://ui-avatars.com/api/?name=Member&background=1A365D&color=fff"
                            class="rounded-circle mb-3 user-avatar-img" width="80" height="80">
                        <h5 class="fw-bold user-name-display">Loading...</h5>
                        <p class="text-muted small user-role-display mb-1">Authenticating...</p>
                        <span class="badge bg-success">Active Member</span>
                    </div>
                </div>
            </div>

            <div class="col-md-8 mb-4">
                <div class="row">
                    <!-- Income Card -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm border-0 bg-primary text-white" style="border-radius: 12px;">
                            <div class="card-body">
                                <h6 class="text-white-50">Total Income</h6>
                                <h3 class="fw-bold">₹ 0.00</h3>
                                <small>Updated just now</small>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Card -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm border-0 bg-success text-white" style="border-radius: 12px;">
                            <div class="card-body">
                                <h6 class="text-white-50">Attendance</h6>
                                <h3 class="fw-bold" id="attendanceStatusText">Not Marked</h3>

                                <div class="d-flex align-items-center mt-2">
                                    <!-- Punch In Button -->
                                    <button id="markAttendanceBtn" class="btn btn-light btn-sm fw-bold text-success">
                                        <i class="fas fa-fingerprint"></i> Punch In
                                    </button>

                                    <!-- 🔥 YAHAN MISSING THA VIEW CALENDAR BUTTON 🔥 -->
                                    <button type="button" class="btn btn-outline-light btn-sm ms-2 fw-bold"
                                        data-bs-toggle="modal" data-bs-target="#attendanceCalendarModal">
                                        <i class="fas fa-calendar-alt"></i> View Calendar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 🟢 Calendar Modal HTML (Ise container ke bahar rakha hai taaki design na toote) -->
    <div class="modal fade" id="attendanceCalendarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-calendar-check me-2"></i> My Attendance Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">

                    <!-- Month/Year Filter -->
                    <div class="row mb-3">
                        <div class="col-6 col-md-4">
                            <select id="filterMonth" class="form-select form-select-sm shadow-sm">
                                <option value="01">January</option>
                                <option value="02">February</option>
                                <option value="03">March</option>
                                <option value="04">April</option>
                                <option value="05">May</option>
                                <option value="06">June</option>
                                <option value="07">July</option>
                                <option value="08">August</option>
                                <option value="09">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4">
                            <select id="filterYear" class="form-select form-select-sm shadow-sm">
                                <option value="2026">2026</option>
                                <option value="2027">2027</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4 mt-2 mt-md-0">
                            <button id="btnFetchCalendar" class="btn btn-primary btn-sm w-100 shadow-sm"><i
                                    class="fas fa-search"></i> Show</button>
                        </div>
                    </div>

                    <!-- 🟢 Calendar Grid Container -->
                    <div class="bg-white rounded shadow-sm p-3">
                        <div class="calendar-grid" id="calendarHeader">
                            <div class="calendar-header text-danger">Sun</div>
                            <div class="calendar-header">Mon</div>
                            <div class="calendar-header">Tue</div>
                            <div class="calendar-header">Wed</div>
                            <div class="calendar-header">Thu</div>
                            <div class="calendar-header">Fri</div>
                            <div class="calendar-header">Sat</div>
                        </div>
                        <div class="calendar-grid" id="calendarDaysBody">
                            <!-- JS se calendar render hoga -->
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let locationPingInterval = null;

    // 🟢 1. Check Today's Status from Server (Page Load Hote Hi)
    function checkStatusOnLoad() {
        // Shuru me loading text dikhayein
        $('#attendanceStatusText').text('Checking...');
        $('#markAttendanceBtn').hide();

        $.ajax({
            url: '/api/v1/member/attendance/today-status',
            type: 'GET',
            success: function(res) {
                if (res.status === 'success') {
                    // Update UI securely from Database
                    updateAttendanceUI(res.action);
                    
                    // Naye din ka track rakhne ke liye date aur status store karein
                    localStorage.setItem('member_attendance_status', res.action);
                    localStorage.setItem('member_attendance_date', new Date().toDateString());

                    if (res.action === 'punched_in') {
                        startLocationPinging();
                    } else {
                        stopLocationPinging();
                    }
                }
            },
            error: function(err) {
                console.error('Failed to fetch status', err);
                $('#attendanceStatusText').text('Network Error');
            }
        });
    }

    // Call check on page load
    checkStatusOnLoad();


    // 🟢 2. Punch In / Punch Out Button Click Event
    $('#markAttendanceBtn').on('click', function() {
        if (navigator.geolocation) {
            Swal.fire({
                title: 'Fetching Location...',
                text: 'Please wait while we get your GPS coordinates.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            navigator.geolocation.getCurrentPosition(function(position) {
                let lat = position.coords.latitude;
                let lng = position.coords.longitude;

                // API Call for Punch In / Out
                $.ajax({
                    url: '/api/v1/member/attendance/mark',
                    type: 'POST',
                    data: { latitude: lat, longitude: lng },
                    success: function(res) {
                        Swal.fire('Success', res.message, 'success');
                        
                        localStorage.setItem('member_attendance_status', res.action);
                        localStorage.setItem('member_attendance_date', new Date().toDateString());
                        
                        updateAttendanceUI(res.action);

                        if (res.action === 'punched_in') {
                            startLocationPinging();
                        } else if (res.action === 'completed' || res.action === 'punched_out') {
                            stopLocationPinging();
                        }
                    },
                    error: function(err) {
                        Swal.fire('Error', 'Failed to mark attendance. Try again.', 'error');
                    }
                });
            }, function(error) {
                Swal.fire('Error', 'Please enable Location/GPS to mark attendance.', 'error');
            }, {
                enableHighAccuracy: true
            });
        } else {
            Swal.fire('Error', 'Geolocation is not supported by your browser.', 'error');
        }
    });

    // 🟢 3. Helper Function to Update UI
    function updateAttendanceUI(action) {
        if (action === 'punched_in') {
            $('#attendanceStatusText').text('Punched In');
            $('#markAttendanceBtn').html('<i class="fas fa-sign-out-alt"></i> Punch Out')
                                   .removeClass('text-success')
                                   .addClass('text-danger').show();
        } else if (action === 'completed') {
            $('#attendanceStatusText').text('Completed Today');
            $('#markAttendanceBtn').hide(); 
        } else { // pending
            $('#attendanceStatusText').text('Not Marked');
            $('#markAttendanceBtn').html('<i class="fas fa-fingerprint"></i> Punch In')
                                   .removeClass('text-danger')
                                   .addClass('text-success').show();
        }
    }


    // 🟢 4. Background Tracking (Har 2 Minute Me)
    function startLocationPinging() {
        if(!locationPingInterval) {
            locationPingInterval = setInterval(pingServer, 120000); 
            console.log("Background location tracking started.");
        }
    }

    function stopLocationPinging() {
        if (locationPingInterval) {
            clearInterval(locationPingInterval);
            locationPingInterval = null;
            console.log("Background location tracking stopped.");
        }
    }

    function pingServer() {
        // 🔥 MIDNIGHT AUTO-KILL LOGIC 🔥
        let savedDate = localStorage.getItem('member_attendance_date');
        let todayDate = new Date().toDateString();
        
        // Agar date badal gayi hai (matlab 12 baj gaye hain), to ping stop kar do!
        if (savedDate && savedDate !== todayDate) {
            console.warn("Day changed! Auto-stopping background ping.");
            stopLocationPinging();
            updateAttendanceUI('pending');
            return;
        }

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                $.ajax({
                    url: '/api/v1/member/attendance/ping-location',
                    type: 'POST',
                    data: {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude
                    },
                    success: function() {
                        console.log("Location pinged successfully.");
                    },
                    error: function(xhr) {
                        // 🔥 SESSION EXPIRED AUTO-KILL 🔥
                        if (xhr.status === 401 || xhr.status === 403) {
                            console.error("Session expired or blocked. Stopping pings.");
                            stopLocationPinging();
                        }
                    }
                });
            }, function(error) {
                console.warn("Background tracking failed this time: ", error.message);
            }, { enableHighAccuracy: true });
        }
    }


    // 🟢 5. View Calendar Logic
    const d = new Date();
    let currentMonth = String(d.getMonth() + 1).padStart(2, '0');
    let currentYear = d.getFullYear();
    $('#filterMonth').val(currentMonth);
    $('#filterYear').val(currentYear);

    $('#attendanceCalendarModal').on('show.bs.modal', function () {
        fetchMonthlyAttendance();
    });

    $('#btnFetchCalendar').on('click', function() {
        fetchMonthlyAttendance();
    });

    function fetchMonthlyAttendance() {
        let month = $('#filterMonth').val();
        let year = $('#filterYear').val();
        
        $('#calendarDaysBody').html('<div style="grid-column: span 7; text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin text-primary fs-3"></i></div>');

        $.ajax({
            url: '/api/v1/member/attendance/monthly',
            type: 'GET',
            data: { month: month, year: year },
            success: function(res) {
                renderCalendarGrid(year, month, res.data, res.joining_date);
            },
            error: function() {
                $('#calendarDaysBody').html('<div style="grid-column: span 7; text-align: center; color: red;">Failed to load data.</div>');
            }
        });
    }

    function renderCalendarGrid(year, month, attendanceData, joiningDateStr) {
        let firstDay = new Date(year, month - 1, 1).getDay(); // Month ka pehla din konsa hai (0-6)
        let daysInMonth = new Date(year, month, 0).getDate(); // Total days
        let joiningDate = joiningDateStr ? new Date(joiningDateStr) : new Date('2000-01-01');
        joiningDate.setHours(0,0,0,0); // Sirf date compare karne ke liye

        let html = '';

        // Khali cells
        for (let i = 0; i < firstDay; i++) {
            html += '<div class="calendar-cell empty"></div>';
        }

        // Days render karein
        for (let day = 1; day <= daysInMonth; day++) {
            let currentDateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            let currentDateObj = new Date(year, month - 1, day);
            currentDateObj.setHours(0,0,0,0);
            
            let dayOfWeek = currentDateObj.getDay(); 
            let isTuesday = (dayOfWeek === 2); // 2 means Tuesday

            if (currentDateObj < joiningDate) {
                html += `<div class="calendar-cell disabled-date"><span class="date-number">${day}</span><div class="text-center mt-3 text-muted" style="font-size: 20px;">-</div></div>`;
            } else {
                let record = attendanceData[currentDateStr];
                
                if (record) {
                    let badgeClass = 'bg-secondary';
                    let fullText = record.status.toUpperCase();
                    let shortText = fullText.charAt(0);

                    if(record.status === 'present') { badgeClass = 'bg-success'; fullText = 'PRESENT'; shortText = 'P'; }
                    else if(record.status === 'absent') { badgeClass = 'bg-danger'; fullText = 'ABSENT'; shortText = 'A'; }
                    else if(record.status === 'leave') { badgeClass = 'bg-warning text-dark'; fullText = 'LEAVE'; shortText = 'L'; }
                    else if(record.status === 'sl') { badgeClass = 'bg-info text-dark'; fullText = 'SHORT LEAVE'; shortText = 'SL'; }

                    let recordJson = encodeURIComponent(JSON.stringify(record));

                    html += `
                        <div class="calendar-cell">
                            <span class="date-number">${day}</span>
                            <button class="status-btn text-white shadow-sm mt-3 ${badgeClass}" onclick="showAttendanceDetails('${recordJson}')">
                                <span class="d-none d-md-inline">${fullText}</span>
                                <span class="d-inline d-md-none">${shortText}</span>
                            </button>
                        </div>`;
                } else {
                    if (isTuesday) {
                        html += `
                        <div class="calendar-cell" style="background: #f8fafc;">
                            <span class="date-number">${day}</span>
                            <div class="status-btn bg-secondary text-white shadow-sm mt-3" style="cursor: default;">
                                <span class="d-none d-md-inline">WEEK OFF</span>
                                <span class="d-inline d-md-none">WO</span>
                            </div>
                        </div>`;
                    } else {
                        html += `<div class="calendar-cell"><span class="date-number">${day}</span><div class="text-center mt-3 text-muted" style="font-size: 20px;">-</div></div>`;
                    }
                }
            }
        }
        $('#calendarDaysBody').html(html);
    }

    // 🟢 View Details in SweetAlert (Sleek UI)
    window.showAttendanceDetails = function(recordJsonEncoded) {
        let record = JSON.parse(decodeURIComponent(recordJsonEncoded));

        let dateObj = new Date(record.date);
        let dateStr = dateObj.toLocaleDateString('en-IN', { day: '2-digit', month: 'long', year: 'numeric' });
        let dayName = dateObj.toLocaleDateString('en-IN', { weekday: 'long' });

        let timeIn = record.punch_in_time ? new Date(record.punch_in_time).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true }) : 'Not Punched In';
        let timeOut = record.punch_out_time ? new Date(record.punch_out_time).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true }) : 'Not Punched Out';

        Swal.fire({
            title: `<span style="color: #1A365D;">${dateStr}</span>`,
            html: `
                <div style="text-align: left; background: #f8fafc; padding: 15px; border-radius: 8px; font-size: 14px; margin-top: 10px;">
                    <div class="mb-2"><strong><i class="fas fa-calendar-day text-secondary me-2"></i> Day:</strong> ${dayName}</div>
                    <div class="mb-2"><strong><i class="fas fa-check-circle text-primary me-2"></i> Status:</strong> <span class="text-uppercase fw-bold">${record.status}</span></div>
                    <div class="mb-2"><strong><i class="fas fa-sign-in-alt text-success me-2"></i> Punch In:</strong> ${timeIn}</div>
                    <div class="mb-2"><strong><i class="fas fa-sign-out-alt text-danger me-2"></i> Punch Out:</strong> ${timeOut}</div>
                </div>
            `,
            showCloseButton: true,
            showConfirmButton: false,
            customClass: { popup: 'rounded-3 shadow-lg' }
        });
    }
});
</script>
@endpush
