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
            padding: 10px;
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

        .day-cl {
            border-color: #bfdbfe;
            background: #f0f9ff;
        }

        .day-cl .status-box {
            background: #dbeafe;
            color: #0369a1;
        }

        .day-holiday {
            border-color: #fde047;
            background: #fefce8;
        }

        .day-holiday .status-box {
            background: #fef08a;
            color: #854d0e;
        }

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

        /* Mobile Specific Calendar Fixes */
        @media (max-width: 768px) {
            .cal-day {
                padding: 4px;
                min-height: 75px;
            }

            .cal-date {
                font-size: 11px;
                text-align: center;
            }

            .status-box {
                font-size: 14px !important;
                padding: 2px;
            }

            .time-box {
                font-size: 8.5px;
                text-align: center;
                padding-top: 3px;
            }

            .time-box .fw-bold {
                display: block;
            }

            .stat-card {
                padding: 10px;
            }

            .stat-icon {
                width: 35px;
                height: 35px;
                font-size: 16px;
            }
        }
    </style>

    <div class="container-fluid p-0">
        <!-- Header Profile -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4"
                    style="width: 50px; height: 50px;" id="profileInitial">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0" style="color:var(--sidebar-bg);" id="empNameDisplay">Employee Workspace</h5>
                    <p class="text-secondary small mb-0"><span id="empRoleDisplay">Loading Profile...</span></p>
                    <p class="text-muted small mb-0" style="font-size: 11px;">
                        <strong>EMP ID:</strong> <span id="empIdDisplay">Loading...</span> |
                        <strong>DEVICE:</strong> <span id="empDeviceDisplay">Loading...</span>
                    </p>
                </div>
            </div>

            <button class="btn btn-danger px-4 py-2 shadow-sm fw-bold" id="btnLogout" style="border-radius: 8px;">
                <i class="fas fa-sign-out-alt me-1"></i> Secure Logout
            </button>
        </div>

        <!-- 🔥 FIX 1: NAYA Manual Attendance Panel (Bahar nikal diya calendar modal se) 🔥 -->
        <div class="card shadow-sm border-0 mb-4" id="attendancePanel"
            style="border-radius: 12px; display: none; border-left: 4px solid #3b82f6 !important;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-primary mb-0"><i class="fas fa-fingerprint me-2"></i> Action Required: Mark
                        Attendance</h6>
                    <small class="badge bg-light text-dark border" id="activeWindowDisplay">Loading Window...</small>
                </div>
                <div class="row align-items-end g-3">
                    <div class="col-md-4">
                        <label class="small text-muted fw-bold">Actual Punch Time</label>
                        <input type="time" id="claimedTime" class="form-control fw-bold border-primary" required>
                        <small class="text-muted" style="font-size:10px;">Select exact time of reporting.</small>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary fw-bold w-100 py-2 shadow-sm" id="btnInitiatePunch">
                            <i class="fas fa-check-circle me-1"></i> Submit Punch
                        </button>
                    </div>
                </div>
                <div class="text-danger small mt-2 fw-bold d-none" id="windowClosedMsg"><i class="fas fa-lock me-1"></i>
                    Attendance window is currently closed.</div>
            </div>
        </div>

        <!-- Today's Attendance Trigger Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0"
                    style="border-radius: 12px; cursor: pointer; border-left: 4px solid var(--brand-primary) !important;"
                    data-bs-toggle="modal" data-bs-target="#calendarModal">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div id="todayStatusIcon"
                                class="rounded-circle bg-secondary text-white d-flex justify-content-center align-items-center shadow-sm"
                                style="width: 45px; height: 45px; font-weight: bold; font-size: 20px;">
                                -
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Today's Attendance <span
                                        class="badge bg-light text-primary border ms-2" style="font-size: 10px;">Click to
                                        view full calendar</span></h6>
                                <p class="text-muted small mb-0 text-uppercase" style="letter-spacing: 0.5px;">
                                    {{ now()->format('d M, Y') }} <span id="todayStatusText"
                                        class="ms-1 fw-bold text-secondary">Checking...</span></p>
                            </div>
                        </div>
                        <div class="text-end" id="todayTimeBox" style="display: none;">
                            <div class="small fw-bold text-success"><i class="fas fa-sign-in-alt me-1"></i> In: <span
                                    id="todayInTime">--:--</span></div>
                            <div class="small fw-bold text-danger mt-1"><i class="fas fa-sign-out-alt me-1"></i> Out: <span
                                    id="todayOutTime">--:--</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Statistics -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">Present</div>
                        <h4 class="fw-bold text-success mb-0" id="statPresent">0</h4>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">Absent</div>
                        <h4 class="fw-bold text-danger mb-0" id="statAbsent">0</h4>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-times-circle"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">Half Days</div>
                        <h4 class="fw-bold text-warning mb-0" id="statHalfDay">0</h4>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-adjust"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">Available CL</div>
                        <h4 class="fw-bold text-info mb-0" id="statCL">1</h4>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-calendar-day"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">Total Leave</div>
                        <h4 class="fw-bold text-secondary mb-0" id="statLeaves">0</h4>
                    </div>
                    <div class="stat-icon bg-secondary bg-opacity-10 text-secondary"><i
                            class="fas fa-plane-departure"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">Extra Days</div>
                        <h4 class="fw-bold text-primary mb-0" id="statExtraDays">0</h4>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-briefcase"></i></div>
                </div>
            </div>
            <div class="col-12 col-lg-4 mt-3">
                <div class="stat-card border-danger">
                    <div>
                        <div class="small text-danger fw-bold">Total Fine Calculation (This Month)</div>
                        <h3 class="fw-bold text-danger mb-0">₹ <span id="statFine">0.00</span></h3>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger fs-3"><i class="fas fa-rupee-sign"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Full Calendar Modal -->
        <div class="modal fade" id="calendarModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                    <div class="modal-header bg-light" style="border-radius: 15px 15px 0 0;">
                        <h5 class="modal-title fw-bold"><i class="fas fa-calendar-alt text-primary me-2"></i> Monthly
                            Attendance Record</h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3 bg-light">
                        <div class="calendar-wrapper border-0 shadow-none m-0 p-0" style="background: transparent;">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                                <div class="small text-muted fw-bold"><i class="fas fa-info-circle me-1"></i> Check your
                                    exact P/A/H/L status</div>
                                <input type="month" id="monthSelector"
                                    class="form-control form-control-sm border-primary fw-bold" style="width: auto;">
                            </div>
                            <div class="cal-header">
                                <div class="text-danger">SUN</div>
                                <div>MON</div>
                                <div>TUE</div>
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
        </div>

        <!-- Proof Collection Modal -->
        <div class="modal fade" id="proofModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                    <div class="modal-header bg-danger text-white" style="border-radius: 15px 15px 0 0;">
                        <h6 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Time Discrepancy
                            Detected</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 bg-light">
                        <div class="alert alert-warning py-2 small fw-bold mb-3 border-warning">
                            You are claiming a time that is more than 5 minutes older than the current system time. Proof is
                            strictly required!
                        </div>
                        <form id="proofForm">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Reason for late punch-in <span
                                        class="text-danger">*</span></label>
                                <textarea id="punchReason" class="form-control form-control-sm border-secondary" rows="2"
                                    placeholder="e.g., Biometric machine issue, site work, etc." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Upload Proof (Images) <span
                                        class="text-danger">*</span></label>
                                <input type="file" id="proofImages"
                                    class="form-control form-control-sm border-secondary" multiple accept="image/*"
                                    required>
                                <small class="text-muted" style="font-size:10px;">Hold Ctrl/Cmd to select multiple
                                    images.</small>
                            </div>
                            <div id="imagePreviewContainer" class="d-flex flex-wrap gap-2 mb-3"></div>
                            <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold py-2 shadow-sm"
                                id="btnSubmitProof">
                                <i class="fas fa-upload me-1"></i> Submit with Proof
                            </button>
                        </form>
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
                    $('#empRoleDisplay').text((res.data.designation_name || 'Employee') + ' | ' + (res
                        .data.department_name || 'General Dept'));
                    $('#profileInitial').html(res.data.name.charAt(0).toUpperCase());
                    $('#empIdDisplay').text(res.data.id || 'N/A');
                    $('#empDeviceDisplay').text(currentPanelId || 'N/A');
                }
            });

            // ==========================================
            // 🔥 NAYA: SMART MANUAL ATTENDANCE LOGIC 🔥
            // ==========================================
            let globalTimeWindow = null;

            // Updated fetchDashboardData (Fix 2 & 3: Consolidated and Crash-proof)
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
                        // Crash Protection Check
                        if (res && res.stats) {
                            updateStats(res.stats);
                            renderCalendar(res.month, res.year, res.daily_data);

                            if (res.time_window) {
                                globalTimeWindow = res.time_window;
                                checkTimeWindowAndToggleUI();
                            } else {
                                $('#attendancePanel').hide(); // Hide if no window assigned
                            }
                        } else {
                            $('#calendarGrid').html(
                                '<div class="text-center py-5 text-danger col-span-7 fw-bold" style="grid-column: span 7;">Data payload structure is invalid.</div>'
                                );
                        }
                    },
                    error: function(err) {
                        $('#calendarGrid').html(
                            '<div class="text-center py-5 text-danger col-span-7" style="grid-column: span 7;">Failed to load data. Permission or API error.</div>'
                            );
                    }
                });
            }

            function updateStats(stats) {
                // Safeguard against missing keys
                if (!stats) return;
                $('#statPresent').text(stats.present || 0);
                $('#statAbsent').text(stats.absent || 0);
                $('#statHalfDay').text(stats.half_day || 0);
                $('#statCL').text(stats.cl_available || 0);
                $('#statLeaves').text(stats.total_leave || 0);
                $('#statExtraDays').text(stats.extra_days || 0);
                let fineAmt = stats.fine_amount ? parseFloat(stats.fine_amount).toFixed(2) : "0.00";
                $('#statFine').text(fineAmt);
            }

            // CALENDAR RENDERER
            function renderCalendar(month, year, dailyData) {
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
                    let remarkHtml = (record.remark && record.remark !== 'On Time') ?
                        `<div class="text-danger mt-1" style="font-size:9.5px; font-weight:600;"><i class="fas fa-exclamation-circle"></i> ${record.remark}</div>` :
                        '';

                    if (record.status === 'off') {
                        boxClass = 'day-off';
                        statusHtml =
                            `<div class="status-box"><span class="d-none d-md-inline"><i class="fas fa-bed"></i> Weekly Off</span><span class="d-inline d-md-none fw-bold">WO</span></div>`;
                    } else if (record.status === 'present') {
                        boxClass = 'day-present';
                        statusHtml = `
                            <div class="status-box"><span class="d-none d-md-inline"><i class="fas fa-check-square"></i> Present</span><span class="d-inline d-md-none fw-bold">P</span></div>
                            <div class="time-box">
                                <div class="mb-1"><span class="text-muted d-none d-md-inline">In:</span> <span class="fw-bold text-success">${record.login_time || '--:--'}</span></div>
                                <div><span class="text-muted d-none d-md-inline">Out:</span> <span class="fw-bold text-danger">${record.logout_time || '--:--'}</span></div>
                                ${remarkHtml}
                            </div>`;
                    } else if (record.status === 'half_day') {
                        boxClass = 'day-halfday';
                        statusHtml = `
                            <div class="status-box"><span class="d-none d-md-inline"><i class="fas fa-adjust"></i> Half Day</span><span class="d-inline d-md-none fw-bold">HD</span></div>
                            <div class="time-box">
                                <div class="mb-1"><span class="text-muted d-none d-md-inline">In:</span> <span class="fw-bold text-success">${record.login_time || '--:--'}</span></div>
                                <div><span class="text-muted d-none d-md-inline">Out:</span> <span class="fw-bold text-danger">${record.logout_time || '--:--'}</span></div>
                                ${remarkHtml}
                            </div>`;
                    } else if (record.status === 'extra_day') {
                        boxClass = 'day-present';
                        statusHtml = `
                            <div class="status-box" style="background:#3b82f6; color:white;"><span class="d-none d-md-inline"><i class="fas fa-star"></i> Extra Day</span><span class="d-inline d-md-none fw-bold">ED</span></div>
                            <div class="time-box">
                                <div class="mb-1"><span class="text-muted d-none d-md-inline">In:</span> <span class="fw-bold text-success">${record.login_time || '--:--'}</span></div>
                                <div><span class="text-muted d-none d-md-inline">Out:</span> <span class="fw-bold text-danger">${record.logout_time || '--:--'}</span></div>
                                <div class="text-primary mt-1" style="font-size:8.5px; font-weight:600;"><i class="fas fa-info-circle d-none d-md-inline"></i> ${record.remark}</div>
                            </div>`;
                    } else if (record.status === 'cl' || record.status === 'leave') {
                        boxClass = 'day-cl';
                        let isShort = record.remark && record.remark.includes('Short Leave');
                        let deskText = record.status === 'cl' ? 'CL Taken' : (isShort ? 'Short Leave' : 'On Leave');
                        let mobText = record.status === 'cl' ? 'CL' : (isShort ? 'SL' : 'L');
                        statusHtml =
                            `<div class="status-box"><span class="d-none d-md-inline"><i class="fas fa-umbrella-beach"></i> ${deskText}</span><span class="d-inline d-md-none fw-bold">${mobText}</span></div>`;
                    } else if (record.status === 'holiday') {
                        boxClass = 'day-holiday';
                        statusHtml =
                            `<div class="status-box"><span class="d-none d-md-inline"><i class="fas fa-star"></i> Holiday</span><span class="d-inline d-md-none fw-bold">HO</span></div>`;
                    } else if (record.status === 'absent') {
                        boxClass = 'day-absent';
                        statusHtml =
                            `<div class="status-box"><span class="d-none d-md-inline"><i class="fas fa-times-square"></i> Absent</span><span class="d-inline d-md-none fw-bold">A</span></div>`;
                    }

                    // Highlight Today
                    let isToday = (dateStr ===
                            `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`
                            ) ? 'border: 2px solid var(--brand-primary); box-shadow: 0 0 10px rgba(0,0,0,0.15);' :
                        '';

                    calHtml +=
                        `<div class="cal-day ${boxClass}" style="${isToday}"><div class="cal-date">${day}</div>${statusHtml}</div>`;
                }

                $('#calendarGrid').html(calHtml);

                // --- CHHOTE CARD KA LOGIC UPDATE ---
                let todayStr =
                    `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                let todayRec = dailyData[todayStr];

                if (todayRec) {
                    if (todayRec.status === 'present') {
                        $('#todayStatusIcon').attr('class',
                            'rounded-circle bg-success text-white d-flex justify-content-center align-items-center shadow-sm'
                            ).text('P');
                        $('#todayStatusText').attr('class', 'ms-1 fw-bold text-success').text('(Present)');
                        $('#todayInTime').text(todayRec.login_time || '--:--');
                        $('#todayOutTime').text(todayRec.logout_time || '--:--');
                        $('#todayTimeBox').show();
                    } else if (todayRec.status === 'half_day') {
                        $('#todayStatusIcon').attr('class',
                            'rounded-circle bg-warning text-white d-flex justify-content-center align-items-center shadow-sm'
                            ).text('H');
                        $('#todayStatusText').attr('class', 'ms-1 fw-bold text-warning').text('(Half Day)');
                        $('#todayInTime').text(todayRec.login_time || '--:--');
                        $('#todayOutTime').text(todayRec.logout_time || '--:--');
                        $('#todayTimeBox').show();
                    } else if (todayRec.status === 'leave' || todayRec.status === 'cl') {
                        $('#todayStatusIcon').attr('class',
                            'rounded-circle bg-info text-white d-flex justify-content-center align-items-center shadow-sm'
                            ).text('L');
                        $('#todayStatusText').attr('class', 'ms-1 fw-bold text-info').text('(On Leave)');
                        $('#todayTimeBox').hide();
                    } else if (todayRec.status === 'holiday') {
                        $('#todayStatusIcon').attr('class',
                            'rounded-circle bg-primary text-white d-flex justify-content-center align-items-center shadow-sm'
                            ).html('<i class="fas fa-star" style="font-size:16px;"></i>');
                        $('#todayStatusText').attr('class', 'ms-1 fw-bold text-primary').text('(Holiday)');
                        $('#todayTimeBox').hide();
                    } else if (todayRec.status === 'off' || todayRec.status === 'future') {
                        $('#todayStatusIcon').attr('class',
                            'rounded-circle bg-secondary text-white d-flex justify-content-center align-items-center shadow-sm'
                            ).text('-');
                        $('#todayStatusText').attr('class', 'ms-1 fw-bold text-secondary').text('');
                        $('#todayTimeBox').hide();
                    } else {
                        $('#todayStatusIcon').attr('class',
                            'rounded-circle bg-danger text-white d-flex justify-content-center align-items-center shadow-sm'
                            ).text('A');
                        $('#todayStatusText').attr('class', 'ms-1 fw-bold text-danger').text('(Absent)');
                        $('#todayTimeBox').hide();
                    }
                }
            }

            function checkTimeWindowAndToggleUI() {
                if (!globalTimeWindow) return;

                let now = new Date();
                let currentTimeStr = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString()
                    .padStart(2, '0');

                let loginStart = globalTimeWindow.login_start.substring(0, 5);
                let loginEnd = globalTimeWindow.login_end.substring(0, 5);

                $('#activeWindowDisplay').text(`Window: ${loginStart} to ${loginEnd}`);
                $('#attendancePanel').show();

                if (currentTimeStr >= loginStart && currentTimeStr <= loginEnd) {
                    $('#claimedTime').prop('disabled', false);
                    $('#btnInitiatePunch').show();
                    $('#windowClosedMsg').addClass('d-none');

                    if (!$('#claimedTime').val()) $('#claimedTime').val(currentTimeStr);
                } else {
                    $('#claimedTime').prop('disabled', true);
                    $('#btnInitiatePunch').hide();
                    $('#windowClosedMsg').removeClass('d-none');
                }
            }

            // Image Preview Logic
            let selectedFiles = [];
            $('#proofImages').on('change', function(e) {
                let files = e.target.files;
                let previewContainer = $('#imagePreviewContainer');
                previewContainer.empty();
                selectedFiles = Array.from(files);

                selectedFiles.forEach((file, index) => {
                    let reader = new FileReader();
                    reader.onload = function(e) {
                        previewContainer.append(`
                            <div class="position-relative" style="width: 60px; height: 60px;">
                                <img src="${e.target.result}" class="img-thumbnail w-100 h-100" style="object-fit: cover;">
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="cursor:pointer; font-size:8px;" onclick="removeImage(${index})">X</span>
                            </div>
                        `);
                    }
                    reader.readAsDataURL(file);
                });
            });

            window.removeImage = function(index) {
                selectedFiles.splice(index, 1);
                let dt = new DataTransfer();
                selectedFiles.forEach(file => dt.items.add(file));
                document.getElementById('proofImages').files = dt.files;
                $('#proofImages').trigger('change');
            };

            // Initiate Punch (Check 5-minute rule)
            $('#btnInitiatePunch').click(function() {
                let claimedVal = $('#claimedTime').val();
                if (!claimedVal) return Swal.fire('Warning', 'Please select your punch-in time.',
                'warning');

                let now = new Date();
                let claimedDate = new Date();
                let parts = claimedVal.split(':');
                claimedDate.setHours(parts[0], parts[1], 0);

                let diffMins = (now - claimedDate) / 1000 / 60;

                if (diffMins > 5) {
                    $('#proofForm')[0].reset();
                    $('#imagePreviewContainer').empty();
                    selectedFiles = [];
                    $('#proofModal').modal('show');
                } else {
                    executePunchAPI(claimedVal);
                }
            });

            $('#proofForm').submit(function(e) {
                e.preventDefault();
                let reason = $('#punchReason').val().trim();

                if (selectedFiles.length === 0 || reason === '') {
                    return Swal.fire({
                        icon: 'error',
                        title: 'Strictly Prohibited',
                        text: 'Without proof/reason, HR may mark you as Half-Day regardless of your working hours. Please provide both.',
                        confirmButtonText: 'I Understand',
                        confirmButtonColor: '#d33'
                    });
                }

                executePunchAPI($('#claimedTime').val(), reason, selectedFiles);
                $('#proofModal').modal('hide');
            });

            function executePunchAPI(claimedTimeStr, reason = null, files = []) {
                Swal.fire({
                    title: 'Recording Punch...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                let formData = new FormData();
                formData.append('panel_id', currentPanelId);
                formData.append('claimed_time', claimedTimeStr);

                if (reason) formData.append('reason', reason);
                if (files.length > 0) {
                    files.forEach((file) => formData.append('proof_images[]', file));
                }

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            formData.append('latitude', position.coords.latitude);
                            formData.append('longitude', position.coords.longitude);
                            sendData(formData);
                        },
                        function(error) {
                            sendData(formData);
                        }
                    );
                } else {
                    sendData(formData);
                }
            }

            function sendData(formData) {
                $.ajax({
                    url: '/api/v1/employee/mark-attendance',
                    type: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + empToken
                    },
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        Swal.fire('Success!', res.message, 'success');
                        let curMonth = $('#monthSelector').val().split('-');
                        fetchDashboardData(curMonth[1], curMonth[0]);
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Punch Failed';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }

            // Month Selector Priority Rules
            $('#monthSelector').on('change', function() {
                let val = $(this).val();
                if (val) {
                    let parts = val.split('-');
                    fetchDashboardData(parts[1], parts[0]);
                }
            });

            // SECURE LOGOUT
            $('#btnLogout').click(function(e) {
                e.preventDefault();
                if (typeof window.performNormalLogout === 'function') {
                    window.performNormalLogout();
                } else {
                    console.error("Global logout function not found in app.blade.php!");
                }
            });

            // Initial Data Load
            fetchDashboardData(String(today.getMonth() + 1).padStart(2, '0'), today.getFullYear());
            // Periodic check for UI button visibility
            setInterval(checkTimeWindowAndToggleUI, 60000);

        });
    </script>
@endpush
