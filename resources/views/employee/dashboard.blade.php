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
            min-height: 110px;
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

        /* 🔥 NAYA: Late Theme for Dashboard 🔥 */
        .day-late {
            border-color: #fbd38d;
            background: #fffaf0;
        }

        .day-late .status-box {
            background: #feebc8;
            color: #dd6b20;
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

        .bg-NA {
            background: #f8f9fa !important;
            color: #adb5bd !important;
            border: 1px dashed #adb5bd !important;
        }

        .time-box {
            font-size: 10.5px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding-top: 5px;
        }

        @media (max-width: 768px) {
            .cal-day {
                padding: 4px;
                min-height: 85px;
            }

            .cal-date {
                font-size: 11px;
                text-align: center;
            }

            .status-box {
                font-size: 13px !important;
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

        .day-sl { border-color: #c7d2fe; background: #eef2ff; } 
.day-sl .status-box { background: #e0e7ff; color: #312e81; }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <!-- 🔥 FIX 1: w-100 lagaya jisse mobile me ye poora width lega -->
            <div class="d-flex align-items-center gap-3 w-100">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm flex-shrink-0"
                    style="width: 50px; height: 50px; cursor: pointer; overflow: hidden; border: 2px solid var(--brand-primary);" 
                    id="profileWrapper" onclick="openProfileModal()" title="View Profile">
                    <div id="profileInitial"><i class="fas fa-user"></i></div>
                </div>
                <div class="flex-grow-1 text-truncate">
                    <h5 class="fw-bold mb-0 text-truncate" style="color:var(--sidebar-bg);" id="empNameDisplay">Employee Workspace</h5>
                    <p class="text-secondary small mb-0 text-truncate"><span id="empRoleDisplay">Loading Profile...</span></p>
                    <p class="text-muted small mb-0" style="font-size: 11px;"><strong>EMP ID:</strong> <span id="empIdDisplay">Loading...</span></p>
                </div>
            </div>
            <!-- Secure Logout yahan se hata diya gaya hai -->
        </div>

        <div class="card shadow-sm border-0 mb-4" id="attendancePanel"
            style="border-radius: 12px; display: none; border-left: 4px solid #3b82f6 !important;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-primary mb-0"><i class="fas fa-fingerprint me-2"></i> Attendance Console</h6>
                    <small class="badge bg-light text-dark border" id="activeWindowDisplay">Loading Window...</small>
                </div>
                <div class="row align-items-end g-3">
                    <div class="col-md-4"><input type="time" id="claimedTime" class="form-control fw-bold border-primary" required></div>
                    <div class="col-md-4"><button class="btn btn-primary fw-bold w-100 py-2 shadow-sm" id="btnInitiatePunch"><i class="fas fa-sign-in-alt me-1"></i> Punch In</button></div>
                    <!-- 🔥 FIX 2: Naya Punch Out button -->
                    <div class="col-md-4"><button class="btn btn-danger fw-bold w-100 py-2 shadow-sm" id="btnPunchOut" style="display: none;"><i class="fas fa-sign-out-alt me-1"></i> Punch Out</button></div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0"
                    style="border-radius: 12px; cursor: pointer; border-left: 4px solid var(--brand-primary) !important;"
                    data-bs-toggle="modal" data-bs-target="#calendarModal">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div id="todayStatusIcon"
                                class="rounded-circle bg-secondary text-white d-flex justify-content-center align-items-center shadow-sm"
                                style="width: 45px; height: 45px; font-weight: bold; font-size: 20px;">-</div>
                            <div>
                                <h6 class="fw-bold mb-1">Today's Attendance <span
                                        class="badge bg-light text-primary border ms-2" style="font-size: 10px;">Click to
                                        view full calendar</span></h6>
                                <p class="text-muted small mb-0">{{ now()->format('d M, Y') }} <span id="todayStatusText"
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

        <!-- 🔥 NAYA: Added "Late Marks" Card 🔥 -->
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
                        <div class="small text-muted fw-bold">Extra Work Hrs</div>
                        <h5 class="fw-bold text-primary mb-0" id="statExtraHrs">0h 0m</h5>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-stopwatch"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">Late Marks</div>
                        <h4 class="fw-bold mb-0" style="color:#dd6b20;" id="statLate">0</h4>
                    </div>
                    <div class="stat-icon" style="background:#fffaf0; color:#dd6b20;"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">Total Leave</div>
                        <h4 class="fw-bold text-info mb-0" id="statLeaves">0</h4>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-plane-departure"></i></div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">Fine (Month)</div>
                        <h4 class="fw-bold text-danger mb-0">₹ <span id="statFine">0.00</span></h4>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-rupee-sign"></i></div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="calendarModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                    <div class="modal-header bg-light" style="border-radius: 15px 15px 0 0;">
                        <h5 class="modal-title fw-bold"><i class="fas fa-calendar-alt text-primary me-2"></i> Monthly
                            Attendance Record</h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3 bg-light">
                        <div class="calendar-wrapper border-0 shadow-none m-0 p-0" style="background: transparent;">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                                <div class="small text-muted fw-bold"><i class="fas fa-info-circle me-1"></i> Check your
                                    exact P/A/H/L/LT status</div>
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

        <div class="modal fade" id="proofModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                    <div class="modal-header bg-danger text-white" style="border-radius: 15px 15px 0 0;">
                        <h6 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Discrepancy
                            Detected</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 bg-light">
                        <form id="proofForm">
                            <div class="mb-3">
                                <textarea id="punchReason" class="form-control form-control-sm border-secondary" rows="2"
                                    placeholder="Reason..." required></textarea>
                            </div>
                            <div class="mb-3"><input type="file" id="proofImages"
                                    class="form-control form-control-sm border-secondary" multiple accept="image/*"
                                    required></div>
                            <div id="imagePreviewContainer" class="d-flex flex-wrap gap-2 mb-3"></div>
                            <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold py-2 shadow-sm"
                                id="btnSubmitProof"><i class="fas fa-upload me-1"></i> Submit with Proof</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="profilePhotoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                    <div class="modal-header bg-light">
                        <h6 class="modal-title fw-bold text-primary"><i class="fas fa-user-circle me-2"></i> User Profile</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center p-4 bg-white">
                        <img id="zoomedProfilePhoto" src="" alt="Profile" class="img-fluid rounded-circle shadow mb-3 d-none" style="width: 130px; height: 130px; object-fit: cover; border: 4px solid var(--brand-primary);">
                        <div id="zoomedProfileInitial" class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center mx-auto mb-3 fw-bold shadow d-none" style="width: 130px; height: 130px; font-size: 50px; border: 4px solid var(--brand-primary);">
                        </div>
                        <h5 class="fw-bold text-dark mb-1" id="modalEmpName"></h5>
                        <p class="text-muted small mb-3" id="modalEmpRole"></p>
                        
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-secondary btn-sm fw-bold px-3" data-bs-dismiss="modal">Close</button>
                            <a href="/employee/my-profile" class="btn btn-primary btn-sm fw-bold px-3"><i class="fas fa-user-edit me-1"></i> My Profile</a>
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

            let today = new Date();
            $('#monthSelector').val(`${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`);

       $.ajax({
                url: '/api/v1/employee/auth/me',
                type: 'GET',
                headers: { 'Authorization': 'Bearer ' + empToken },
                success: function(res) {
                    $('#empNameDisplay').text(res.data.name);
                    let roleString = (res.data.designation_name || 'Employee') + ' | ' + (res.data.department_name || 'General Dept');
                    $('#empRoleDisplay').text(roleString);
                    $('#empIdDisplay').text(res.data.member_id || res.data.id || 'N/A');

                    // Modal me text update karein
                    $('#modalEmpName').text(res.data.name);
                    $('#modalEmpRole').text(roleString);

                    // 🔥 FIX: Photo check aur UI update 🔥
                    let photoUrl = res.data.passport_photo || res.data.profile_photo;
                    if (photoUrl) {
                        // Agar photo hai toh choti aur badi dono jagah image lagao
                        $('#profileInitial').html(`<img src="${photoUrl}" style="width: 100%; height: 100%; object-fit: cover;">`);
                        $('#zoomedProfilePhoto').attr('src', photoUrl).removeClass('d-none');
                        $('#zoomedProfileInitial').addClass('d-none');
                    } else if(res.data.name) {
                        // Agar photo nahi hai toh naam ka pehla akshar dikhao
                        let initial = res.data.name.charAt(0).toUpperCase();
                        $('#profileInitial').html('<b>' + initial + '</b>');
                        $('#zoomedProfileInitial').html(initial).removeClass('d-none');
                        $('#zoomedProfilePhoto').addClass('d-none');
                    }
                }
            });

            // 🔥 NAYA: Image par click karte hi modal kholne ka function 🔥
            window.openProfileModal = function() {
                $('#profilePhotoModal').modal('show');
            };
            let globalTimeWindow = null;

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
                        if (res && res.stats) {
                            updateStats(res.stats);
                            renderCalendar(res.month, res.year, res.daily_data);
                            if (res.time_window) {
                                globalTimeWindow = res.time_window;
                                checkTimeWindowAndToggleUI();
                            } else {
                                $('#attendancePanel').hide();
                            }
                        }
                    }
                });
            }

           function updateStats(stats) {
                if (!stats) return;
                $('#statPresent').text(stats.present || 0);
                $('#statAbsent').text(stats.absent || 0);
                $('#statHalfDay').text(stats.half_day || 0);
                $('#statLate').text(stats.late || 0);
                $('#statLeaves').text(stats.total_leave || 0);
                $('#statExtraHrs').text(stats.extra_hours_str || '0h 0m'); // 🔥 NAYA
                $('#statFine').text(stats.fine_amount ? parseFloat(stats.fine_amount).toFixed(2) : "0.00");
            }

      window.showDayDetails = function(dateStr, inTime, outTime, status, remark, workedTime, extraTime) {
                let displayDate = new Date(dateStr).toLocaleString('en-IN', {
                    day: 'numeric', month: 'short', year: 'numeric'
                });

                let statusBadgeColor = 'bg-secondary';
                if (status === 'present') statusBadgeColor = 'bg-success';
                else if (status === 'absent') statusBadgeColor = 'bg-danger';
                else if (status === 'half_day') statusBadgeColor = 'bg-warning text-dark';
                else if (status === 'lt') statusBadgeColor = 'bg-warning text-dark'; // Custom for SweetAlert
                else if (status === 'leave' || status === 'cl') statusBadgeColor = 'bg-info text-dark';

             let cleanStatus = status === 'lt' ? 'LATE IN (LT)' : status.replace('_', ' ').toUpperCase();

                Swal.fire({
                    title: `<strong class="text-primary">${displayDate}</strong>`,
                    html: `
                        <div class="text-start mt-3" style="font-size: 14px;">
                            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                <span class="fw-bold text-muted">System Status:</span>
                                <span class="badge ${statusBadgeColor} border">${cleanStatus}</span>
                            </div>
                            <!-- 🔥 NAYA: 4 Box wala naya Grid -->
                            <div class="row g-2 mb-3">
                                <div class="col-6"><div class="p-2 border rounded bg-success bg-opacity-10 text-success text-center"><i class="fas fa-sign-in-alt"></i><br>In: <strong>${inTime}</strong></div></div>
                                <div class="col-6"><div class="p-2 border rounded bg-danger bg-opacity-10 text-danger text-center"><i class="fas fa-sign-out-alt"></i><br>Out: <strong>${outTime}</strong></div></div>
                                <div class="col-6"><div class="p-2 border rounded bg-primary bg-opacity-10 text-primary text-center"><i class="fas fa-briefcase"></i><br>Total: <strong>${workedTime}</strong></div></div>
                                <div class="col-6"><div class="p-2 border rounded bg-info bg-opacity-10 text-info text-center"><i class="fas fa-stopwatch"></i><br>Extra: <strong>${extraTime}</strong></div></div>
                            </div>
                            <div class="p-3 bg-light border rounded">
                                <p class="mb-1 text-muted small fw-bold"><i class="fas fa-info-circle text-primary"></i> Reason / System Note:</p>
                                <div class="fw-bold text-dark">${remark}</div>
                            </div>
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonText: 'Close Details',
                    confirmButtonColor: '#3085d6',
                    width: '400px'
                });
            };

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
                    let safeRemark = (record.remark || 'No specific note.').replace(/'/g, "\\'").replace(/"/g,
                        "&quot;");

                  // 🔥 NAYA: record.extra_time function me pass kiya gaya
                    let infoBtn = '';
                    if (record.remark && record.status !== 'future' && record.status !== 'n_a' && record.status !== 'off' && record.status !== 'holiday') {
                        infoBtn = `<div class="mt-1 text-center"><button class="btn btn-sm btn-outline-info py-0 px-1 w-100" style="font-size:10px; border-radius:4px;" onclick="showDayDetails('${dateStr}', '${record.login_time || '--:--'}', '${record.logout_time || '--:--'}', '${record.status}', '${safeRemark}', '${record.worked_time || '--:--'}', '${record.extra_time || '0h 0m'}')"><i class="fas fa-eye"></i><span class="d-none d-md-inline"> Details</span></button></div>`;
                    } else if (record.status === 'off' || record.status === 'holiday') {
                        infoBtn = `<div class="mt-1 text-center text-muted" style="font-size:9px; font-weight:bold;">${record.remark}</div>`;
                    }
                    if (record.status === 'n_a') {
                        boxClass = 'day-off';
                        statusHtml = `<div class="status-box bg-NA border"><span class="d-none d-md-inline">- (Before Join)</span><span class="d-inline d-md-none fw-bold">-</span></div>`;
                    } else if (record.status === 'future') {
                        boxClass = 'day-future day-off';
                        statusHtml = `<div class="status-box bg-NA border"><span class="d-none d-md-inline">${record.remark || '-'}</span><span class="d-inline d-md-none fw-bold">-</span></div>`;
                    } else if (record.status === 'off') {
                        boxClass = 'day-off';
                        statusHtml = `<div class="status-box"><span class="d-none d-md-inline"><i class="fas fa-bed"></i> Weekly Off</span><span class="d-inline d-md-none fw-bold">WO</span></div>`;
                    } else if (record.status === 'present') {
                        boxClass = 'day-present';
                        // 🔥 FIX: Removed .time-box HTML from here entirely
                        statusHtml = `<div class="status-box"><span class="d-none d-md-inline"><i class="fas fa-check-square"></i> Present</span><span class="d-inline d-md-none fw-bold">P</span></div>`;
                    } else if (record.status === 'sl') {
                        boxClass = 'day-sl'; 
                        statusHtml = `<div class="status-box"><span class="d-none d-md-inline"><i class="fas fa-user-clock"></i> Short Leave</span><span class="d-inline d-md-none fw-bold">SL</span></div>`;
                    } else if (record.status === 'lt') {
                        boxClass = 'day-late';
                        statusHtml = `<div class="status-box"><span class="d-none d-md-inline"><i class="fas fa-clock"></i> Late In</span><span class="d-inline d-md-none fw-bold">LT</span></div>`;
                    } else if (record.status === 'half_day') {
                        boxClass = 'day-halfday';
                        statusHtml = `<div class="status-box"><span class="d-none d-md-inline"><i class="fas fa-adjust"></i> Half Day</span><span class="d-inline d-md-none fw-bold">HD</span></div>`;
                    } else if (record.status === 'cl' || record.status === 'leave') {
                        boxClass = 'day-cl';
                        statusHtml = `<div class="status-box"><span class="d-none d-md-inline"><i class="fas fa-umbrella-beach"></i> ${record.status === 'cl' ? 'CL Taken' : 'On Leave'}</span><span class="d-inline d-md-none fw-bold">${record.status === 'cl' ? 'CL' : 'L'}</span></div>`;
                    } else if (record.status === 'holiday') {
                        boxClass = 'day-holiday';
                        statusHtml = `<div class="status-box"><span class="d-none d-md-inline"><i class="fas fa-star"></i> Holiday</span><span class="d-inline d-md-none fw-bold">HO</span></div>`;
                    } else if (record.status === 'absent') {
                        boxClass = 'day-absent';
                        statusHtml = `<div class="status-box"><span class="d-none d-md-inline"><i class="fas fa-times-square"></i> Absent</span><span class="d-inline d-md-none fw-bold">A</span></div>`;
                    }

                    let isToday = (dateStr ===
                            `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`
                            ) ? 'border: 2px solid var(--brand-primary); box-shadow: 0 0 10px rgba(0,0,0,0.15);' :
                        '';
                    calHtml +=
                        `<div class="cal-day ${boxClass}" style="${isToday}"><div><div class="cal-date">${day}</div>${statusHtml}</div>${infoBtn}</div>`;
                }

                $('#calendarGrid').html(calHtml);

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
                    } else if (todayRec.status === 'lt') { // 🔥 NAYA
                        $('#todayStatusIcon').attr('class',
                            'rounded-circle text-white d-flex justify-content-center align-items-center shadow-sm'
                            ).css('background', '#dd6b20').text('LT');
                        $('#todayStatusText').attr('class', 'ms-1 fw-bold').css('color', '#dd6b20').text(
                            '(Late In)');
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
                    } else {
                        $('#todayStatusIcon').attr('class',
                            'rounded-circle bg-secondary text-white d-flex justify-content-center align-items-center shadow-sm'
                            ).text('-');
                        $('#todayStatusText').text('');
                        $('#todayTimeBox').hide();
                    }
                }
            }

          function checkTimeWindowAndToggleUI() {
                if (!globalTimeWindow) return;
                let now = new Date();
                let currentTimeStr = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
                
                let loginStart = globalTimeWindow.login_start.substring(0, 5);
                let loginEnd = globalTimeWindow.login_end.substring(0, 5);
                let logoutStart = globalTimeWindow.logout_start.substring(0, 5);
                
                $('#activeWindowDisplay').text(`IN: ${loginStart}-${loginEnd} | OUT after: ${logoutStart}`);
                $('#attendancePanel').show();

                if (currentTimeStr >= loginStart && currentTimeStr <= loginEnd) {
                    $('#claimedTime').prop('disabled', false);
                    $('#btnInitiatePunch').show();
                    if (!$('#claimedTime').val()) $('#claimedTime').val(currentTimeStr);
                } else {
                    $('#claimedTime').prop('disabled', true);
                    $('#btnInitiatePunch').hide();
                }

                // 🔥 NAYA: Punch Out sirf logout_start time ke baad dikhega 🔥
                if (currentTimeStr >= logoutStart) {
                    $('#btnPunchOut').show();
                } else {
                    $('#btnPunchOut').hide();
                }
            }

            // Purane $('#btnLogout').click(...) ko isse replace karein
            $('#btnPunchOut').click(function(e) {
                e.preventDefault();
                Swal.fire({ title: 'Punching Out...', text: 'Fetching out location...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

                let geoOptions = { enableHighAccuracy: true, timeout: 7000, maximumAge: 0 };
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(pos) { executePunchOut(pos.coords.latitude, pos.coords.longitude); },
                        function(err) { executePunchOut(null, null); }, geoOptions
                    );
                } else { executePunchOut(null, null); }
            });

            function executePunchOut(lat, lng) {
                let payload = { panel_id: currentPanelId };
                if (lat && lng) { payload.latitude = lat; payload.longitude = lng; }

                $.ajax({
                    url: '/api/v1/employee/logout', // Humne backend me ise update kar diya hai
                    type: 'POST',
                    headers: { 'Authorization': 'Bearer ' + empToken },
                    data: payload,
                    success: function(res) {
                        Swal.fire('Success', 'Punched Out Recorded!', 'success');
                        let curMonth = $('#monthSelector').val().split('-');
                        fetchDashboardData(curMonth[1], curMonth[0]);
                    },
                    error: function() {
                        Swal.fire('Error', 'Punch Out Failed', 'error');
                    }
                });
            }

            $('#btnInitiatePunch').click(function() {
                let claimedVal = $('#claimedTime').val();
                if (!claimedVal) return Swal.fire('Warning', 'Select time.', 'warning');
                let now = new Date();
                let claimedDate = new Date();
                let parts = claimedVal.split(':');
                claimedDate.setHours(parts[0], parts[1], 0);
                if (((now - claimedDate) / 1000 / 60) > 5) {
                    $('#proofModal').modal('show');
                } else {
                    executePunchAPI(claimedVal);
                }
            });

            $('#proofForm').submit(function(e) {
                e.preventDefault();
                let reason = $('#punchReason').val().trim();
                executePunchAPI($('#claimedTime').val(), reason, Array.from(document.getElementById(
                    'proofImages').files));
                $('#proofModal').modal('hide');
            });

           // Punch In Location Config
            function executePunchAPI(claimedTimeStr, reason = null, files = []) {
                Swal.fire({ title: 'Recording...', didOpen: () => { Swal.showLoading(); } });
                let formData = new FormData(); formData.append('panel_id', currentPanelId); formData.append('claimed_time', claimedTimeStr);
                if (reason) formData.append('reason', reason); files.forEach((file) => formData.append('proof_images[]', file));
                
                // 🔥 NAYA: High Accuracy & Timeout
                let geoOptions = { enableHighAccuracy: true, timeout: 7000, maximumAge: 0 };
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(pos) { formData.append('latitude', pos.coords.latitude); formData.append('longitude', pos.coords.longitude); sendData(formData); },
                        function(err) { sendData(formData); },
                        geoOptions
                    );
                } else { sendData(formData); }
            }

            // 👇 YE WALA FUNCTION MISSING THA, ISE ADD KAREIN 👇
            function sendData(formData) {
                $.ajax({
                    url: '/api/v1/employee/mark-attendance',
                    type: 'POST',
                    headers: { 'Authorization': 'Bearer ' + empToken },
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

            $('#monthSelector').on('change', function() {
                let val = $(this).val();
                if (val) {
                    let parts = val.split('-');
                    fetchDashboardData(parts[1], parts[0]);
                }
            });
          // Punch Out Location Config
            // $('#btnLogout').click(function(e) {
            //     e.preventDefault();
            //     Swal.fire({ title: 'Securely Logging Out...', text: 'Fetching your punch-out location...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            //     let geoOptions = { enableHighAccuracy: true, timeout: 7000, maximumAge: 0 };
            //     if (navigator.geolocation) {
            //         navigator.geolocation.getCurrentPosition(
            //             function(pos) { executeLogout(pos.coords.latitude, pos.coords.longitude); },
            //             function(err) { executeLogout(null, null); },
            //             geoOptions
            //         );
            //     } else { executeLogout(null, null); }
            // });

            function executeLogout(lat, lng) {
                let payload = { panel_id: currentPanelId };
                if (lat && lng) {
                    payload.latitude = lat;
                    payload.longitude = lng;
                }

                $.ajax({
                    url: '/api/v1/employee/logout',
                    type: 'POST',
                    headers: { 'Authorization': 'Bearer ' + empToken },
                    data: payload,
                    success: function(res) {
                        if (typeof window.performNormalLogout === 'function') {
                            window.performNormalLogout();
                        } else {
                            localStorage.clear();
                            window.location.href = '/employee/login';
                        }
                    },
                    error: function() {
                        if (typeof window.performNormalLogout === 'function') window.performNormalLogout();
                        else { localStorage.clear(); window.location.href = '/employee/login'; }
                    }
                });
            }

            fetchDashboardData(String(today.getMonth() + 1).padStart(2, '0'), today.getFullYear());
            setInterval(checkTimeWindowAndToggleUI, 60000);
        });
    </script>
@endpush
