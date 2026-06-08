@extends('layout.app')

@section('content')
    <style>
        .stat-card {
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: #fff;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .calendar-wrapper {
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        }

        .cal-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-weight: 700;
            font-size: 13px;
            color: #64748b;
            margin-bottom: 10px;
        }

        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }

        .cal-day {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px;
            min-height: 95px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #f8fafc;
            transition: 0.2s;
        }

        .cal-day.empty {
            background: transparent;
            border: none;
        }

        .cal-date {
            font-weight: 700;
            font-size: 14px;
            color: #334155;
        }

        .status-box {
            font-size: 11px;
            padding: 4px;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        /* Specific Status Colors */
        .day-present {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .day-present .status-box {
            background: #dcfce7;
            color: #166534;
        }

        .day-absent {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .day-absent .status-box {
            background: #fee2e2;
            color: #991b1b;
        }

        .day-halfday {
            border-color: #fca5a5;
            background: #fff1f2;
        }

        .day-halfday .status-box {
            background: #fecaca;
            color: #b91c1c;
        }

        /* Dark Red for Half Day */

        .day-cl {
            border-color: #bfdbfe;
            background: #f0f9ff;
        }

        .day-cl .status-box {
            background: #dbeafe;
            color: #0369a1;
        }

        /* Blue for CL */

        .day-off {
            border-color: #e2e8f0;
            background: #f1f5f9;
            opacity: 0.7;
        }

        .day-off .status-box {
            background: #e2e8f0;
            color: #475569;
        }

        .day-future {
            opacity: 0.5;
        }

        .time-box {
            font-size: 10.5px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding-top: 5px;
        }
    </style>

    <div class="container-fluid p-0">
        <!-- TOP HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4"
                    style="width: 50px; height: 50px;" id="profileInitial">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0" style="color:var(--sidebar-bg);" id="empNameDisplay">Employee Workspace</h5>
                    <p class="text-secondary small mb-0"><span id="empRoleDisplay">Loading Profile...</span> | <span
                            class="fw-bold text-dark">Calc. Basis: 30 Days</span></p>
                </div>
            </div>

            <button class="btn btn-danger px-4 py-2 shadow-sm fw-bold" id="btnLogout" style="border-radius: 8px;">
                <i class="fas fa-sign-out-alt me-1"></i> Secure Logout
            </button>
        </div>

        <!-- STATS ROW -->
        <div class="row g-3 mb-4">
            <div class="col-4 col-md-2">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">Present</div>
                        <h4 class="fw-bold text-success mb-0" id="statPresent">0</h4>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-4 col-md-2">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">Absent</div>
                        <h4 class="fw-bold text-danger mb-0" id="statAbsent">0</h4>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-times-circle"></i></div>
                </div>
            </div>
            <div class="col-4 col-md-2">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">Half Days</div>
                        <h4 class="fw-bold text-danger mb-0" id="statHalfDay">0</h4>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-adjust"></i></div>
                </div>
            </div>
            <div class="col-4 col-md-2">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">Available CL</div>
                        <h4 class="fw-bold text-info mb-0" id="statCL">1</h4>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-calendar-day"></i></div>
                </div>
            </div>
            <div class="col-4 col-md-2">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">Total Leave</div>
                        <h4 class="fw-bold text-secondary mb-0" id="statLeaves">0</h4>
                    </div>
                    <div class="stat-icon bg-secondary bg-opacity-10 text-secondary"><i class="fas fa-plane-departure"></i>
                    </div>
                </div>
            </div>
            <div class="col-4 col-md-2">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">Overtime (Hrs)</div>
                        <h4 class="fw-bold text-warning mb-0" id="statOT">0</h4>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-12 col-md-4 mt-3">
                <div class="stat-card border-danger">
                    <div>
                        <div class="small text-danger fw-bold">Total Fine Calculation (This Month)</div>
                        <h3 class="fw-bold text-danger mb-0">₹ <span id="statFine">0.00</span></h3>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger fs-3"><i class="fas fa-rupee-sign"></i></div>
                </div>
            </div>
        </div>

        <!-- CALENDAR SECTION -->
        <div class="row">
            <div class="col-12">
                <div class="calendar-wrapper">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                        <h5 class="fw-bold text-dark mb-3 mb-md-0"><i class="fas fa-calendar-alt text-primary me-2"></i>
                            Attendance Record</h5>

                        <div class="d-flex align-items-center gap-3">
                            <div class="small text-muted fw-bold"><i class="fas fa-info-circle me-1"></i> Tuesdays are
                                Weekly Offs</div>
                            <!-- MONTH SELECTOR -->
                            <input type="month" id="monthSelector"
                                class="form-control form-control-sm border-primary fw-bold" style="width: auto;">
                        </div>
                    </div>

                    <div class="cal-header">
                        <div class="text-danger">SUN</div>
                        <div>MON</div>
                        <div class="text-warning">TUE</div>
                        <div>WED</div>
                        <div>THU</div>
                        <div>FRI</div>
                        <div>SAT</div>
                    </div>
                    <div class="cal-grid" id="calendarGrid">
                        <div class="text-center py-5 text-muted col-span-7" style="grid-column: span 7;"><i
                                class="fas fa-spinner fa-spin me-2"></i> Loading Backend Data...</div>
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
            let empToken = localStorage.getItem('emp_token');
            let currentPanelId = localStorage.getItem('emp_panel_id');

            if (!empToken) {
                window.location.href = '/employee/login';
                return;
            }

            // Set default month selector to current month
            let today = new Date();
            let currentStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
            $('#monthSelector').val(currentStr);

            // Fetch Basic Profile Once
            $.ajax({
                url: '/api/v1/employee/auth/me',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + empToken
                },
                success: function(res) {
                    $('#empNameDisplay').text(res.data.name);
                    $('#empRoleDisplay').text(res.data.designation_name + ' | ' + res.data.email);
                    $('#profileInitial').html(res.data.name.charAt(0).toUpperCase());
                }
            });

            // Fetch Real Data from New API
            function fetchDashboardData(month, year) {
                $('#calendarGrid').html(
                    '<div class="text-center py-5 text-muted col-span-7" style="grid-column: span 7;"><i class="fas fa-spinner fa-spin me-2"></i> Fetching Live Records...</div>'
                    );

                $.ajax({
                    url: `/api/v1/employee/dashboard-data?month=${month}&year=${year}`,
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + empToken
                    },
                    success: function(res) {
                        updateStats(res.stats);
                        renderCalendar(res.month, res.year, res.daily_data);
                    },
                    error: function(err) {
                        console.error('Data Fetch Error');
                    }
                });
            }

            function updateStats(stats) {
                $('#statPresent').text(stats.present);
                $('#statAbsent').text(stats.absent);
                $('#statHalfDay').text(stats.half_day);
                $('#statCL').text(stats.cl_available);
                $('#statLeaves').text(stats.total_leave);
                $('#statOT').text(stats.ot_hours);
                $('#statFine').text(stats.fine_amount.toFixed(2));
            }

            // CALENDAR RENDERER
            function renderCalendar(month, year, dailyData) {
                // Parse month correctly (JavaScript months are 0-indexed)
                let mIndex = parseInt(month) - 1;
                let yIndex = parseInt(year);

                let firstDay = new Date(yIndex, mIndex, 1).getDay();
                let daysInMonth = new Date(yIndex, mIndex + 1, 0).getDate();

                let calHtml = '';

                for (let i = 0; i < firstDay; i++) {
                    calHtml += `<div class="cal-day empty"></div>`;
                }

                for (let day = 1; day <= daysInMonth; day++) {
                    let dateStr =
                    `${yIndex}-${String(mIndex + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    let record = dailyData[dateStr] || {
                        status: 'future'
                    };

                    let boxClass = 'day-future';
                    let statusHtml = '';

                    if (record.status === 'off') {
                        boxClass = 'day-off';
                        statusHtml = `<div class="status-box"><i class="fas fa-bed"></i> Weekly Off</div>`;
                    } else if (record.status === 'present') {
                        boxClass = 'day-present';
                        let otText = record.ot > 0 ?
                            ` <span class="badge bg-warning text-dark ms-1" style="font-size:9px;">${record.ot}h OT</span>` :
                            '';
                        statusHtml = `
                            <div class="status-box"><i class="fas fa-check-square"></i> Present${otText}</div>
                            <div class="time-box">
                                <div class="d-flex justify-content-between mb-1"><span class="text-muted">In:</span> <span class="fw-bold text-success">${record.login_time}</span></div>
                                <div class="d-flex justify-content-between"><span class="text-muted">Out:</span> <span class="fw-bold text-danger">${record.logout_time}</span></div>
                            </div>
                        `;
                    } else if (record.status === 'half_day') {
                        boxClass = 'day-halfday';
                        statusHtml = `
                            <div class="status-box"><i class="fas fa-adjust"></i> Half Day</div>
                            <div class="time-box">
                                <div class="d-flex justify-content-between mb-1"><span class="text-muted">In:</span> <span class="fw-bold text-success">${record.login_time}</span></div>
                                <div class="d-flex justify-content-between"><span class="text-muted">Out:</span> <span class="fw-bold text-danger">${record.logout_time}</span></div>
                            </div>
                        `;
                    } else if (record.status === 'cl') {
                        boxClass = 'day-cl';
                        statusHtml = `<div class="status-box"><i class="fas fa-umbrella-beach"></i> CL Taken</div>`;
                    } else if (record.status === 'absent') {
                        boxClass = 'day-absent';
                        statusHtml = `<div class="status-box"><i class="fas fa-times-square"></i> Absent</div>`;
                    }

                    // Highlight Today
                    let isToday = (dateStr ===
                            `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`
                            ) ? 'border: 2px solid var(--brand-primary); box-shadow: 0 0 10px rgba(0,0,0,0.15);' :
                        '';

                    calHtml += `
                        <div class="cal-day ${boxClass}" style="${isToday}">
                            <div class="cal-date">${day}</div>
                            ${statusHtml}
                        </div>
                    `;
                }

                $('#calendarGrid').html(calHtml);
            }

            // On Month Selector Change
            $('#monthSelector').on('change', function() {
                let val = $(this).val();
                if (val) {
                    let parts = val.split('-');
                    fetchDashboardData(parts[1], parts[0]);
                }
            });

            // Initial Data Load
            fetchDashboardData(String(today.getMonth() + 1).padStart(2, '0'), today.getFullYear());


            // ==========================================
            // AUTO ATTENDANCE & SECURITY LOGIC 
            // ==========================================
            function markAttendance(lat = null, long = null) {
                $.ajax({
                    url: '/api/v1/employee/mark-attendance',
                    type: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + empToken
                    },
                    data: {
                        panel_id: currentPanelId,
                        latitude: lat,
                        longitude: long
                    },
                    success: function(res) {
                        let curMonth = $('#monthSelector').val().split('-');
                        fetchDashboardData(curMonth[1], curMonth[0]);
                    }
                });
            }

           // Sirf tabhi call karo jab user sach mein Naya Login karke aaya ho!
if (!sessionStorage.getItem('attendance_marked_today')) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) { 
                markAttendance(position.coords.latitude, position.coords.longitude); 
                sessionStorage.setItem('attendance_marked_today', 'yes');
            },
            function(error) { 
                markAttendance(null, null); 
                sessionStorage.setItem('attendance_marked_today', 'yes');
            }
        );
    } else {
        markAttendance(null, null);
        sessionStorage.setItem('attendance_marked_today', 'yes');
    }
}

            $('#btnLogout').click(function() {
                $.post({
                    url: '/api/v1/employee/logout',
                    headers: {
                        'Authorization': 'Bearer ' + empToken
                    },
                    data: {
                        panel_id: currentPanelId
                    }
                }).always(function() {
                    localStorage.removeItem('emp_token');
                    localStorage.removeItem('emp_panel_id');
                    window.location.href = '/employee/login';
                });
            });

            // let idleTime = 0;
            // let idleInterval = setInterval(function() {
            //     idleTime++;
            //     if (idleTime >= 15) {
            //         clearInterval(idleInterval);
            //         Swal.fire({
            //             title: 'Session Expired!',
            //             text: '15 minutes of inactivity detected.',
            //             icon: 'warning',
            //             allowOutsideClick: false
            //         }).then(() => {
            //             $('#btnLogout').click();
            //         });
            //     }
            // }, 60000);
            // $(document).on('mousemove keydown scroll click', function() {
            //     idleTime = 0;
            // });

            // setInterval(function() {
            //     let now = new Date();
            //     if (now.getHours() > 18 || (now.getHours() === 18 && now.getMinutes() >= 15)) {
            //         Swal.fire({
            //             title: 'Shift Over!',
            //             text: 'Your shift limit (18:15) has been reached.',
            //             icon: 'info',
            //             allowOutsideClick: false
            //         }).then(() => {
            //             $('#btnLogout').click();
            //         });
            //     }
            // }, 60000);

        });
    </script>
@endpush
