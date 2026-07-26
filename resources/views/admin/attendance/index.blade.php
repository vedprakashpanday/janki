@extends('layout.app')

@section('content')
    <style>
        .status-badge {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.2s;
            margin: 0 auto;
        }

        .status-badge:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .bg-P {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .bg-A {
            background-color: #f8d7da;
            color: #842029;
        }

        .bg-HD {
            background-color: #fff3cd;
            color: #664d03;
        }

        .bg-L {
            background-color: #cff4fc;
            color: #055160;
        }

        .bg-WO {
            background-color: #e2e3e5;
            color: #41464b;
        }

        .bg-HO {
            background-color: #cfe2ff;
            color: #084298;
        }

        .bg-ED {
            background-color: #d3d3d4;
            color: #141619;
            border: 1px solid #141619;
        }

        .bg-LT {
            background-color: #ffedd5;
            color: #dd6b20;
            border: 1px solid #dd6b20;
        }

        /* 🔥 NAYA: LT Color */
        .bg-NA {
            background-color: transparent;
            color: #adb5bd;
            cursor: not-allowed;
            border: 1px dashed #adb5bd;
        }

        .emp-search-card {
            transition: 0.3s;
        }

        .mob-day-box {
            width: 42px;
            text-align: center;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 5px 0;
            background: #fff;
            cursor: pointer;
        }

        .mob-day-num {
            font-size: 10px;
            color: #6c757d;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .holiday-col {
            background-color: #fff0f0 !important;
        }

        .holiday-header {
            background-color: #ffe6e6 !important;
            color: #d63384 !important;
        }

        .bg-LT { background-color: #ffedd5; color: #dd6b20; border: 1px solid #dd6b20; }
        
        /* 🔥 NAYA: SL (Short Leave) Color 🔥 */
        .bg-SL { background-color: #e0e7ff; color: #3730a3; border: 1px solid #3730a3; }

    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0 text-primary"><i class="fas fa-calendar-check me-2"></i> Attendance Master Matrix</h4>
            <button id="btnExportExcel" class="btn btn-success btn-sm fw-bold shadow-sm">
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </button>
        </div>

        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <div class="card-body p-3">
                <form id="filterForm" class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted mb-1">Company</label>
                        <select class="form-select form-select-sm" id="filter_company" name="company_id">
                            <option value="">All Companies</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted mb-1">Branch</label>
                        <select class="form-select form-select-sm" id="filter_branch" name="branch_id">
                            <option value="">All Branches</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted mb-1">Department</label>
                        <select class="form-select form-select-sm" id="filter_department" name="department_id">
                            <option value="">All Departments</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted mb-1">Select Month</label>
                        <input type="month" class="form-control form-control-sm border-warning" id="filter_month"
                            value="{{ date('Y-m') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1">Or Date Range</label>
                        <div class="input-group input-group-sm">
                            <input type="date" class="form-control" id="start_date" name="start_date">
                            <span class="input-group-text bg-light">to</span>
                            <input type="date" class="form-control" id="end_date" name="end_date">
                        </div>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-primary btn-sm w-100 fw-bold" id="btnLoadMatrix"><i
                                class="fas fa-search"></i></button>
                    </div>
                </form>

                <div class="row mt-3 border-top pt-3">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-primary"><i
                                    class="fas fa-search text-primary"></i></span>
                            <input type="text" id="liveSearch" class="form-control border-primary fw-bold"
                                placeholder="Live Search Employee Name or ID...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="loadingIndicator" class="text-center py-5 d-none">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 fw-bold text-muted">Calculating Timeline Metrics...</p>
        </div>

        <div id="dataViewWrapper" class="d-none">
            <div class="card shadow-sm border-0 d-none d-xl-block" style="border-radius: 12px; overflow: hidden;"
                id="desktopViewContainer">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 12.5px;">
                        <thead class="table-light" id="matrixThead"></thead>
                        <tbody id="matrixTbody">
                            <tr>
                                <td colspan="100%" class="text-center py-4 text-muted">Select filters and load matrix.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row g-3 d-xl-none" id="mobileViewContainer"></div>
        </div>

        <!-- 🔥 NAYA: Correction Modal me LT option added 🔥 -->
        <div class="modal fade" id="correctionModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                    <div class="modal-header bg-light">
                        <h6 class="modal-title fw-bold text-primary"><i class="fas fa-edit me-2"></i> Adjust Attendance
                            Status</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="correctionForm">
                            <input type="hidden" id="corr_emp_id">
                            <input type="hidden" id="corr_date">

                            <div class="alert alert-info py-2 small fw-bold mb-3">
                                Target Date: <span id="corr_date_display" class="text-danger"></span> <br>
                                Current System Status: <span id="corr_old_status" class="badge bg-secondary ms-1"></span>
                                <div class="row mt-2 border-top pt-2">
                                    <div class="col-6 text-success"><i class="fas fa-sign-in-alt"></i> In: <span
                                            id="corr_in_time_disp"></span></div>
                                    <div class="col-6 text-danger"><i class="fas fa-sign-out-alt"></i> Out: <span
                                            id="corr_out_time_disp"></span></div>
                                    <div class="col-12 mt-1 text-muted"><i class="fas fa-info-circle"></i> System Note:
                                        <span id="corr_sys_remark"></span></div>
                                </div>
                                <div class="row g-2 mt-2 border-top pt-2">
                                    <div class="col-6">
                                        <a id="corr_map_link_in" href="#" target="_blank"
                                            class="btn btn-sm btn-outline-success w-100 d-none"
                                            title="View Punch-In Location"><i class="fas fa-map-marker-alt"></i>
                                            In-Loc</a>
                                    </div>
                                    <div class="col-6">
                                        <a id="corr_map_link_out" href="#" target="_blank"
                                            class="btn btn-sm btn-outline-danger w-100 d-none"
                                            title="View Punch-Out Location"><i class="fas fa-map-marker-alt"></i>
                                            Out-Loc</a>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">New Corrected Status</label>
                                <select class="form-select form-select-sm fw-bold" id="corr_new_status" required>
                                    <option value="P">Present (P)</option>
                                    <option value="LT">Late In (LT)</option>
                                    <option value="A">Absent (A)</option>
                                    <option value="HD">Half Day (HD)</option>
                                    <option value="L">Approved Leave (L)</option>
                                    <option value="SL">Short Leave (SL)</option> <!-- 🔥 NAYA -->
                                    <option value="WO">Weekly Off (WO)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Reason for Override <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm" id="corr_reason" rows="2"
                                    placeholder="e.g. Employee forgot to punch out, system error, etc." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">Save & Lock
                                Correction</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="hrVerificationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title fw-bold text-dark"><i class="fas fa-clipboard-check me-2"></i> Pending HR
                            Verification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="hrVerificationForm">
                        <div class="modal-body p-4 bg-light">
                            <input type="hidden" id="verify_attendance_id">
                          <div class="row mb-3 align-items-center">
                                <div class="col-md-12">
                                    <p class="mb-0 text-muted small fw-bold">EMPLOYEE DETAILS</p>
                                    <h5 class="fw-bold text-primary mb-0" id="verify_emp_name">Loading...</h5>
                                    <p class="mb-0 text-dark small fw-bold"><i class="fas fa-clock text-muted"></i> Claimed In-Time: <span id="verify_date_time" class="text-danger"></span></p>
                                </div>
                                <!-- 🔥 NAYA: Verification Modal me Location Buttons 🔥 -->
                                <div class="col-12 mt-3 border-top pt-2">
                                    <div class="row g-2">
                                        <div class="col-6"><a id="verify_map_link_in" href="#" target="_blank" class="btn btn-sm w-100 fw-bold"><i class="fas fa-map-marker-alt"></i> In-Loc</a></div>
                                        <div class="col-6"><a id="verify_map_link_out" href="#" target="_blank" class="btn btn-sm w-100 fw-bold"><i class="fas fa-map-marker-alt"></i> Out-Loc</a></div>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-white border shadow-sm mb-3" style="background: #fff;">
                                <span class="badge bg-danger mb-2">System Alert / Employee Reason</span>
                                <p class="mb-0 small fw-bold text-dark" id="verify_emp_reason" style="font-size: 13px;">
                                </p>
                            </div>
                            <div class="mb-4">
                                <span class="badge bg-secondary mb-2">Uploaded Proofs</span>
                                <div id="verify_image_gallery"
                                    class="d-flex flex-wrap gap-2 p-2 border rounded bg-white min-vh-25"></div>
                            </div>
                            <hr>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Proof Action <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select border-warning fw-bold" id="verify_action" required>
                                        <option value="approved">Approve Proof (Valid)</option>
                                        <option value="rejected">Reject Proof (Invalid)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Final Attendance Status <span class="text-danger">*</span></label>
                                    <select class="form-select border-primary fw-bold" id="verify_final_status" required>
                                        <option value="P">Present (P)</option>
                                        <option value="LT">Late In (LT)</option>
                                        <option value="HD">Half Day (HD)</option>
                                        <option value="A">Absent (A)</option>
                                        <option value="L">Leave (L)</option>
                                        <option value="SL">Short Leave (SL)</option> <!-- 🔥 NAYA -->
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold text-dark">HR Remark / Audit Reason <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control border-secondary" id="verify_hr_remark" rows="2"
                                        placeholder="Mandatory comment..." required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-white border-top-0">
                            <button type="submit" class="btn btn-warning fw-bold px-4 shadow-sm" id="btnVerifySubmit"><i
                                    class="fas fa-save me-1"></i> Confirm & Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script>
            $(document).ready(function() {
                let adminToken = localStorage.getItem('admin_token') || localStorage.getItem('emp_token');

                function loadDropdowns() {
                    $.ajax({
                        url: '/api/v1/get-active-companies',
                        type: 'GET',
                        headers: {
                            'Authorization': 'Bearer ' + adminToken
                        },
                        success: function(res) {
                            let options = '<option value="">All Companies</option>';
                            let data = res.data || res;
                            if (Array.isArray(data)) {
                                data.forEach(c => {
                                    options += `<option value="${c.id}">${c.company_name}</option>`;
                                });
                            }
                            $('#filter_company').html(options);
                            loadMatrixData();
                        }
                    });
                }

                $('#filter_company').change(function() {
                    let companyId = $(this).val();
                    $('#filter_branch').html('<option value="">All Branches</option>');
                    $('#filter_department').html('<option value="">All Departments</option>');
                    if (companyId) {
                        $.ajax({
                            url: '/api/v1/get-branches-by-companies',
                            type: 'POST',
                            headers: {
                                'Authorization': 'Bearer ' + adminToken
                            },
                            data: {
                                company_ids: companyId
                            },
                            success: function(res) {
                                let compName = $("#filter_company option:selected").text();
                                let options = '<option value="">All Branches</option>';
                                if (compName && compName !== "All Companies") options +=
                                    `<option value="HO" class="fw-bold text-primary">${compName} (Head Office)</option>`;
                                let data = res.data || res;
                                if (Array.isArray(data)) {
                                    data.forEach(b => {
                                        options +=
                                            `<option value="${b.id}">${b.branch_name}</option>`;
                                    });
                                }
                                $('#filter_branch').html(options);
                            }
                        });
                    }
                });

                $('#filter_branch').change(function() {
                    let branchId = $(this).val();
                    let companyId = $('#filter_company').val();
                    $('#filter_department').html('<option value="">All Departments</option>');
                    if (branchId || companyId) {
                        $.ajax({
                            url: '/api/v1/get-filtered-departments',
                            type: 'POST',
                            headers: {
                                'Authorization': 'Bearer ' + adminToken
                            },
                            data: {
                                company_id: companyId,
                                branch_id: branchId
                            },
                            success: function(res) {
                                let options = '<option value="">All Departments</option>';
                                let data = res.data || res;
                                if (Array.isArray(data)) {
                                    data.forEach(d => {
                                        options +=
                                            `<option value="${d.id}">${d.department_name}</option>`;
                                    });
                                }
                                $('#filter_department').html(options);
                            }
                        });
                    }
                });

                loadDropdowns();
                $('#btnLoadMatrix').click(function() {
                    loadMatrixData();
                });

                function loadMatrixData() {
                    $('#dataViewWrapper').addClass('d-none');
                    $('#mobileViewContainer').empty();
                    $('#loadingIndicator').removeClass('d-none');
                    let startDate = $('#start_date').val();
                    let endDate = $('#end_date').val();
                    let monthVal = $('#filter_month').val();

                    if (monthVal && !startDate && !endDate) {
                        let [y, m] = monthVal.split('-');
                        startDate = `${y}-${m}-01`;
                        let lastDay = new Date(y, m, 0).getDate();
                        endDate = `${y}-${m}-${lastDay}`;
                    }

                    let payload = {
                        company_id: $('#filter_company').val(),
                        branch_id: $('#filter_branch').val(),
                        department_id: $('#filter_department').val(),
                        start_date: startDate,
                        end_date: endDate
                    };

                    $.ajax({
                        url: '/api/v1/attendance-matrix',
                        type: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + adminToken
                        },
                        data: payload,
                        success: function(res) {
                            $('#loadingIndicator').addClass('d-none');
                            if (res.success && res.matrix && res.matrix.length > 0) {
                                window.currentMatrixData = res.matrix;
                                window.currentDatesList = res.dates_list;
                                renderDesktopTable(res.matrix, res.dates_list);
                                renderMobileCards(res.matrix, res.dates_list);
                                $('#dataViewWrapper').removeClass('d-none');
                                $('#liveSearch').trigger('keyup');
                            } else {
                                $('#matrixThead').empty();
                                $('#matrixTbody').html(
                                    '<tr><td class="text-center py-4 fw-bold text-danger">No attendance data found for these filters.</td></tr>'
                                    );
                                $('#dataViewWrapper').removeClass('d-none');
                            }
                        },
                        error: function(err) {
                            $('#loadingIndicator').addClass('d-none');
                            Swal.fire('Error', 'Failed to fetch attendance data.', 'error');
                        }
                    });
                }

                function renderDesktopTable(matrix, dates) {
                    if (!matrix || matrix.length === 0) return;
                    let headHtml =
                        `<tr><th class="py-3" style="min-width:250px; background-color:#1A365D; color:#fff;">Employee Identity</th>`;

                    let holidayMap = {};
                    dates.forEach(d => {
                        let dateObj = new Date(d);
                        let dayName = dateObj.toLocaleDateString('en-US', {
                            weekday: 'short'
                        }).toUpperCase();
                        let isWeekOff = dayName === 'TUE';
                        let isHoliday = matrix.some(row => {
                            if (!row.dates[d]) return false;
                            return row.dates[d].status === 'HO' || (row.dates[d].remark || '')
                                .toLowerCase().includes('holiday');
                        });
                        holidayMap[d] = isWeekOff || isHoliday;
                    });

                    dates.forEach(d => {
                        let parts = d.split('-');
                        let dateObj = new Date(d);
                        let dayName = dateObj.toLocaleDateString('en-US', {
                            weekday: 'short'
                        }).toUpperCase();
                        let headerClass = holidayMap[d] ? 'holiday-header' : '';
                        let textColor = holidayMap[d] ? '' : 'color:#6c757d;';
                        headHtml +=
                            `<th class="text-center ${headerClass}" style="min-width:45px; border-bottom: 2px solid #D69E2E;"><div style="font-size:11px; ${textColor}">${parts[2]}</div><div style="font-size:10px; font-weight:bold;">${dayName}</div></th>`;
                    });

                    // 🔥 NAYA: Added LT column to header
                    headHtml += `<th class="text-center bg-success text-white">P</th>
                         <th class="text-center text-white" style="background-color:#dd6b20;" title="Late Marks">LT</th>
                         <th class="text-center bg-danger text-white">A</th>
                         <th class="text-center bg-warning text-dark">HD</th>
                         <th class="text-center bg-info text-white">L</th>
                         <th class="text-center bg-dark text-white" title="Extra Days">ED</th>
                        <th class="text-center bg-info text-white" title="Extra Hours">EH</th> </tr>`;
                    $('#matrixThead').html(headHtml);

                    let bodyHtml = '';
                    matrix.forEach(row => {
                        let emp = row.employee;
                        let stats = row.stats;
                        bodyHtml +=
                            `<tr class="emp-search-row"><td class="bg-light"><div class="fw-bold text-primary">${emp.name}</div><div class="text-muted" style="font-size:10.5px;">EMP: <span class="fw-bold">${emp.member_id}</span> | ${emp.department}</div></td>`;

                        dates.forEach(d => {
                            let dayData = row.dates[d];
                            let isPending = dayData.verification_status === 'pending';
                            let pendingIcon = isPending ?
                                '<i class="fas fa-exclamation-circle position-absolute top-0 start-100 translate-middle text-danger bg-white rounded-circle shadow-sm" style="font-size:12px;"></i>' :
                                '';
                            let clickAction = '';
                            if (isPending) {
                                let encodedImages = encodeURIComponent(JSON.stringify(dayData
                                    .proof_images || []));
                                let safeReason = (dayData.reason || '').replace(/'/g, "\\'");
                               
                                   clickAction = `onclick="openVerificationModal('${dayData.id}', '${emp.name}', '${d}', '${dayData.in || ''}', '${safeReason}', decodeURIComponent('${encodedImages}'), '${dayData.lat || ''}', '${dayData.lng || ''}', '${dayData.out_lat || ''}', '${dayData.out_lng || ''}')"`;
                            } else if (dayData.status !== 'N/A') {
                                let safeRem = (dayData.remark || '').replace(/'/g, "\\'");
                                clickAction =
                                    `onclick="openCorrectionModal(${emp.db_id}, '${d}', '${dayData.status}', '${dayData.lat || ''}', '${dayData.lng || ''}', '${dayData.out_lat || ''}', '${dayData.out_lng || ''}', '${dayData.in || ''}', '${dayData.out || ''}', '${safeRem}')"`;
                            }
                            let displayStatus = dayData.status === 'N/A' ? '-' : dayData.status;
                            let colClass = holidayMap[d] ? 'holiday-col' : '';

                            bodyHtml +=
                                `<td class="text-center align-middle ${colClass}" title="In: ${dayData.in || '--'} | Out: ${dayData.out || '--'} \nNote: ${dayData.reason || dayData.remark || 'None'}">
                        <div class="status-badge bg-${dayData.status === 'N/A' ? 'NA' : dayData.status} position-relative" ${clickAction}>${displayStatus}${pendingIcon}</div></td>`;
                        });

                        // 🔥 NAYA: Added LT column to body stats
                        bodyHtml += `<td class="text-center fw-bold bg-success bg-opacity-10 text-success">${stats.present}</td>
                    <td class="text-center fw-bold" style="color:#dd6b20; background-color:#fffaf0;">${stats.late || 0}</td>
                    <td class="text-center fw-bold bg-danger bg-opacity-10 text-danger">${stats.absent}</td>
                    <td class="text-center fw-bold bg-warning bg-opacity-10 text-dark">${stats.half_day}</td>
                    <td class="text-center fw-bold bg-info bg-opacity-10 text-info">${stats.leave}</td>
                    <td class="text-center fw-bold bg-dark bg-opacity-10 text-dark">${stats.extra_day || 0}</td>
                    <td>${stats.extra_hours_str}</td></tr>`;
                    });
                    $('#matrixTbody').html(bodyHtml);
                }

                function renderMobileCards(matrix, dates) {
                    let mobHtml = '';
                    matrix.forEach(row => {
                        let emp = row.employee;
                        let stats = row.stats;
                        let daysHtml = '';
                        dates.forEach(d => {
                            let dayData = row.dates[d];
                            let isPending = dayData.verification_status === 'pending';
                            let pendingIcon = isPending ?
                                '<i class="fas fa-exclamation-circle position-absolute top-0 start-100 translate-middle text-danger bg-white rounded-circle shadow-sm" style="font-size:12px;"></i>' :
                                '';
                            let clickAction = '';
                            if (isPending) {
                                let encodedImages = encodeURIComponent(JSON.stringify(dayData
                                    .proof_images || []));
                                let safeReason = (dayData.reason || '').replace(/'/g, "\\'");
                                clickAction =
                                    `onclick="openVerificationModal('${dayData.id}', '${emp.name}', '${d}', '${dayData.in || ''}', '${safeReason}', decodeURIComponent('${encodedImages}'))"`;
                            } else if (dayData.status !== 'N/A') {
                                let safeRem = (dayData.remark || '').replace(/'/g, "\\'");
                                clickAction =
                                    `onclick="openCorrectionModal(${emp.db_id}, '${d}', '${dayData.status}', '${dayData.lat || ''}', '${dayData.lng || ''}', '${dayData.out_lat || ''}', '${dayData.out_lng || ''}', '${dayData.in || ''}', '${dayData.out || ''}', '${safeRem}')"`;
                            }
                            let displayStatus = dayData.status === 'N/A' ? '-' : dayData.status;
                            let dateObj = new Date(d);
                            let dayName = dateObj.toLocaleDateString('en-US', {
                                weekday: 'short'
                            }).toUpperCase();
                            daysHtml +=
                                `<div class="mob-day-box shadow-sm" ${clickAction}><div class="mob-day-num">${d.split('-')[2]}<br><span style="font-size:8px; font-weight:normal;">${dayName}</span></div><div class="status-badge bg-${dayData.status === 'N/A' ? 'NA' : dayData.status} position-relative" style="width:25px; height:25px; font-size:9px;">${displayStatus}${pendingIcon}</div></div>`;
                        });
                        mobHtml +=
                            `<div class="col-12 emp-search-card"><div class="card shadow-sm border-0" style="border-radius:12px; border-left: 4px solid #1A365D !important;"><div class="card-body p-3"><div class="d-flex justify-content-between border-bottom pb-2 mb-2"><div><h6 class="fw-bold mb-0 text-primary">${emp.name}</h6><small class="text-muted" style="font-size:11px;">${emp.member_id} | ${emp.department}</small></div><div class="text-end"><span class="badge bg-success">P: ${stats.present}</span> <span class="badge" style="background:#dd6b20;">LT: ${stats.late||0}</span> <span class="badge bg-danger">A: ${stats.absent}</span></div></div><div class="d-flex flex-wrap gap-2 justify-content-center">${daysHtml}</div></div></div></div>`;
                    });
                    $('#mobileViewContainer').html(mobHtml);
                }

               // 🔥 NAYA: Always Show Buttons. Gray if loc missing 🔥
                window.openCorrectionModal = function(empId, date, currentStatus, lat, lng, outLat, outLng, inTime, outTime, remark) {
                    $('#corr_emp_id').val(empId); $('#corr_date').val(date);
                    let displayDate = new Date(date).toLocaleString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' });
                    $('#corr_date_display').text(displayDate); $('#corr_old_status').text(currentStatus);
                    $('#corr_in_time_disp').text(inTime || '--:--'); $('#corr_out_time_disp').text(outTime || '--:--'); $('#corr_sys_remark').text(remark || 'No specific reason found.');
                    $('#corr_new_status').val(currentStatus === 'N/A' || currentStatus === 'ED' || currentStatus === 'HO' ? 'P' : currentStatus);
                    $('#corr_reason').val('');
                    
                    if (lat && lng && lat !== 'null') {
                        $('#corr_map_link_in').attr('href', `https://www.google.com/maps?q=${lat},${lng}`).removeClass('disabled btn-outline-secondary d-none').addClass('btn-outline-success').html('<i class="fas fa-map-marker-alt"></i> In-Loc');
                    } else {
                        $('#corr_map_link_in').attr('href', '#').removeClass('btn-outline-success d-none').addClass('disabled btn-outline-secondary').html('<i class="fas fa-map-marker-alt"></i> In Missing');
                    }

                    if (outLat && outLng && outLat !== 'null') {
                        $('#corr_map_link_out').attr('href', `https://www.google.com/maps?q=${outLat},${outLng}`).removeClass('disabled btn-outline-secondary d-none').addClass('btn-outline-danger').html('<i class="fas fa-map-marker-alt"></i> Out-Loc');
                    } else {
                        $('#corr_map_link_out').attr('href', '#').removeClass('btn-outline-danger d-none').addClass('disabled btn-outline-secondary').html('<i class="fas fa-map-marker-alt"></i> Out Missing');
                    }
                    $('#correctionModal').modal('show');
                };

              $('#correctionForm').submit(function(e) {
            e.preventDefault();
            let payload = { 
                employee_id: $('#corr_emp_id').val(), 
                date: $('#corr_date').val(), 
                corrected_status: $('#corr_new_status').val(), 
                reason: $('#corr_reason').val() 
            };
            
            $.ajax({ 
                url: '/api/v1/attendance-correction', 
                type: 'POST', 
                headers: { 'Authorization': 'Bearer ' + adminToken }, 
                data: payload,
                success: function(res) { 
                    $('#correctionModal').modal('hide'); 
                    Swal.fire({ title: 'Locked!', text: res.message, icon: 'success', timer: 1500, showConfirmButton: false }); 
                    loadMatrixData(); 
                },
                // 🔥 NAYA FIX: Error aane par SweetAlert show karega
                error: function(xhr) {
                    let msg = "Validation Error";
                    if(xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire('Error', msg, 'error');
                }
            });
        });

              // EXPORT TO EXCEL
        $('#btnExportExcel').click(function() {
            if (!window.currentMatrixData || window.currentMatrixData.length === 0) {
                Swal.fire('Notice', 'Please load the matrix first before exporting.', 'info');
                return;
            }

            let matrix = window.currentMatrixData; 
            let dates = window.currentDatesList; 
            let csvContent = "data:text/csv;charset=utf-8,";
            
            let headers = ["Employee ID", "Employee Name", "Department", "Designation"];
            dates.forEach(d => { 
                let parts = d.split('-'); 
                let dateObj = new Date(d);
                let dayName = dateObj.toLocaleDateString('en-US', { weekday: 'short' }).toUpperCase();
                headers.push(`${parts[2]}-${parts[1]} (${dayName})`); 
            });
            headers.push("Present", "Late", "Absent", "Half Day", "Leave", "Extra Days"); 
            
            csvContent += headers.map(h => `"${h}"`).join(",") + "\r\n";
            
            matrix.forEach(row => {
                let emp = row.employee; let stats = row.stats;
                let rowData = [ `"${emp.member_id || ''}"`, `"${emp.name || ''}"`, `"${emp.department || ''}"`, `"${emp.designation || ''}"` ];
                dates.forEach(d => { let st = row.dates[d].status; rowData.push(`"${st === 'N/A' ? '-' : st}"`); });
                rowData.push(stats.present || 0, stats.late || 0, stats.absent || 0, stats.half_day || 0, stats.leave || 0, stats.extra_day || 0); 
                csvContent += rowData.join(",") + "\r\n";
            });

            // 🔥 NAYA: Dynamic Smart File Naming Logic 🔥
            let companyText = $('#filter_company option:selected').text();
            let companyNamePart = (companyText && companyText !== 'All Companies') ? companyText.replace(/[^a-zA-Z0-9]/g, '_') : 'All_Company';

            let startDate = $('#start_date').val();
            let endDate = $('#end_date').val();
            let monthVal = $('#filter_month').val();
            let datePart = "Attendance";

            if (startDate && endDate) {
                let sParts = startDate.split('-');
                let eParts = endDate.split('-');
                datePart = `${sParts[2]}-${sParts[1]}_to_${eParts[2]}-${eParts[1]}`; // e.g. 01-07_to_15-07
            } else if (monthVal) {
                let parts = monthVal.split('-');
                const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                datePart = monthNames[parseInt(parts[1]) - 1]; // e.g. July
            }

            let finalFileName = `${datePart}_${companyNamePart}_Employee_Attendance.csv`;

            let link = document.createElement("a"); 
            link.setAttribute("href", encodeURI(csvContent)); 
            link.setAttribute("download", finalFileName); 
            document.body.appendChild(link); 
            link.click(); 
            document.body.removeChild(link);
        });
                $('#liveSearch').on('keyup', function() {
                    let v = $(this).val().toLowerCase();
                    $('.emp-search-row, .emp-search-card').each(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1);
                    });
                });

            window.openVerificationModal = function(attendanceId, empName, punchDate, loginTime, reason, imagesJson, lat, lng, outLat, outLng) {
                    $('#hrVerificationForm')[0].reset();
                    $('#verify_attendance_id').val(attendanceId);
                    $('#verify_emp_name').text(empName);
                    $('#verify_date_time').text(punchDate + ' @ ' + (loginTime || 'N/A'));
                    $('#verify_emp_reason').text(reason || 'No reason.');
                    
                    // Same Logic for Verification Modal Map Links
                    if (lat && lng && lat !== 'null') {
                        $('#verify_map_link_in').attr('href', `https://www.google.com/maps?q=${lat},${lng}`).removeClass('disabled btn-secondary').addClass('btn-success text-white').html('<i class="fas fa-map-marker-alt"></i> In-Loc');
                    } else {
                        $('#verify_map_link_in').attr('href', '#').removeClass('btn-success text-white').addClass('disabled btn-secondary').html('<i class="fas fa-map-marker-alt"></i> In Missing');
                    }

                    if (outLat && outLng && outLat !== 'null') {
                        $('#verify_map_link_out').attr('href', `https://www.google.com/maps?q=${outLat},${outLng}`).removeClass('disabled btn-secondary').addClass('btn-danger text-white').html('<i class="fas fa-map-marker-alt"></i> Out-Loc');
                    } else {
                        $('#verify_map_link_out').attr('href', '#').removeClass('btn-danger text-white').addClass('disabled btn-secondary').html('<i class="fas fa-map-marker-alt"></i> Out Missing');
                    }

                    let gallery = $('#verify_image_gallery'); gallery.empty();
                    if (imagesJson && imagesJson !== 'null') {
                        try { JSON.parse(imagesJson).forEach(img => { gallery.append(`<a href="/${img}" target="_blank"><img src="/${img}" style="width:80px;height:80px;border-radius:4px;"></a>`); }); } catch (e) {}
                    }
                    $('#hrVerificationModal').modal('show');
                };

                $('#hrVerificationForm').submit(function(e) {
            e.preventDefault();
            let payload = { 
                attendance_id: $('#verify_attendance_id').val(), 
                action_status: $('#verify_action').val(), 
                final_attendance_status: $('#verify_final_status').val(), 
                hr_remark: $('#verify_hr_remark').val() 
            };
            
            // Note: Apna URL wahi rakhein jo aap use kar rahe hain
            $.ajax({ 
                url: '/api/v1/attendance-verify-punch', // Ya '/api/v1/admin/attendance-verify-punch' jo bhi aapka route ho
                type: 'POST', 
                headers: { 'Authorization': 'Bearer ' + adminToken }, 
                data: payload,
                success: function(res) { 
                    if (res.success) { 
                        Swal.fire('Verified!', res.message, 'success').then(() => { 
                            $('#hrVerificationModal').modal('hide'); 
                            loadMatrixData(); 
                        }); 
                    } 
                },
                // 🔥 NAYA FIX: Screen par error message dikhane ke liye
                error: function(xhr) {
                    let msg = "Validation Error";
                    if(xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire('Error', msg, 'error');
                }
            });
        });
            });
        </script>
    @endpush
