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

        <!-- ============================================== -->
        <!-- MODAL: MANUAL CORRECTION                       -->
        <!-- ============================================== -->
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

                                <a id="corr_map_link" href="#" target="_blank"
                                    class="btn btn-sm btn-outline-info w-100 mt-2 d-none">
                                    <i class="fas fa-map-marker-alt"></i> View Punch Location on Map
                                </a>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">New Corrected Status</label>
                                <select class="form-select form-select-sm fw-bold" id="corr_new_status" required>
                                    <option value="P">Present (P)</option>
                                    <option value="A">Absent (A)</option>
                                    <option value="HD">Half Day (HD)</option>
                                    <option value="L">Approved Leave (L)</option>
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


        <!-- ============================================== -->
        <!-- MODAL: HR VERIFICATION (Proof System)          -->
        <!-- ============================================== -->
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
                                <div class="col-md-8">
                                    <p class="mb-0 text-muted small fw-bold">EMPLOYEE DETAILS</p>
                                    <h5 class="fw-bold text-primary mb-0" id="verify_emp_name">Loading...</h5>
                                    <p class="mb-0 text-dark small fw-bold"><i class="fas fa-clock text-muted"></i>
                                        Claimed In-Time: <span id="verify_date_time" class="text-danger"></span></p>
                                </div>
                                <div class="col-md-4 text-md-end mt-2 mt-md-0">
                                    <a href="#" target="_blank" id="verify_map_link"
                                        class="btn btn-sm btn-info text-white fw-bold shadow-sm d-none">
                                        <i class="fas fa-map-marker-alt me-1"></i> View Live Location
                                    </a>
                                </div>
                            </div>
                            <div class="alert alert-white border shadow-sm mb-3" style="background: #fff;">
                                <span class="badge bg-danger mb-2">Employee's Reason for Late Punch</span>
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
                                    <label class="form-label small fw-bold text-dark">Final Attendance Status <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select border-primary fw-bold" id="verify_final_status" required>
                                        <option value="P">Present (P)</option>
                                        <option value="HD">Half Day (HD)</option>
                                        <option value="A">Absent (A)</option>
                                        <option value="L">Leave (L)</option>
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
                                if (compName && compName !== "All Companies") {
                                    options +=
                                        `<option value="HO" class="fw-bold text-primary">${compName} (Head Office)</option>`;
                                }
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
                        end_date: endDate,
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
                            Swal.fire('Error', 'Failed to fetch attendance data. Please check network.',
                                'error');
                        }
                    });
                }

                function renderDesktopTable(matrix, dates) {
                    if (!matrix || matrix.length === 0) return;

                    let headHtml =
                        `<tr><th class="py-3" style="min-width:250px; background-color:#1A365D; color:#fff;">Employee Identity</th>`;

                    dates.forEach(d => {
                        let parts = d.split('-');
                        let dateObj = new Date(d);
                        let dayName = dateObj.toLocaleDateString('en-US', {
                            weekday: 'short'
                        }).toUpperCase();

                        // User Demand: Now showing "01 MON" format
                        headHtml +=
                            `<th class="text-center" style="min-width:45px; border-bottom: 2px solid #D69E2E;"><div style="font-size:11px; color:#6c757d;">${parts[2]}</div><div style="font-size:10px; font-weight:bold;">${dayName}</div></th>`;
                    });

                    headHtml += `<th class="text-center bg-success text-white">P</th>
                             <th class="text-center bg-danger text-white">A</th>
                             <th class="text-center bg-warning text-dark">HD</th>
                             <th class="text-center bg-info text-white">L</th>
                             <th class="text-center bg-dark text-white" title="Extra Days (Off/Holiday)">ED</th></tr>`;
                    $('#matrixThead').html(headHtml);

                    let bodyHtml = '';
                    matrix.forEach(row => {
                        let emp = row.employee;
                        let stats = row.stats;

                        // USER DEMAND: Name and Dept/Desig properly formatted
                        bodyHtml += `<tr class="emp-search-row">
                        <td class="bg-light">
                            <div class="fw-bold text-primary">${emp.name}</div>
                            <div class="text-muted" style="font-size:10.5px;">EMP: <span class="fw-bold">${emp.member_id}</span> | ${emp.department} - ${emp.designation}</div>
                        </td>`;

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
                                    `onclick="openVerificationModal('${dayData.id}', '${emp.name}', '${d}', '${dayData.in || ''}', '${safeReason}', decodeURIComponent('${encodedImages}'), '${dayData.lat || ''}', '${dayData.lng || ''}')"`;
                            } else if (dayData.status !== 'N/A') {
                                clickAction =
                                    `onclick="openCorrectionModal(${emp.db_id}, '${d}', '${dayData.status}', '${dayData.lat || ''}', '${dayData.lng || ''}')"`;
                            }

                            // USER DEMAND: Detailed Hover Tooltip
                            let attachText = (dayData.proof_images && dayData.proof_images.length > 0 &&
                                dayData.proof_images !== 'null') ? 'Available' : 'Not Available';
                            let noteText = dayData.reason || dayData.remark || 'None';
                            let tooltipStr =
                                `In: ${dayData.in || '--'} | Out: ${dayData.out || '--'} \nNotes/Reason: ${noteText} \nAttachment: ${attachText}`;

                            let displayStatus = dayData.status === 'N/A' ? '-' : dayData.status;

                            bodyHtml += `<td class="text-center align-middle" title="${tooltipStr}">
                                <div class="status-badge bg-${dayData.status === 'N/A' ? 'NA' : dayData.status} position-relative" ${clickAction}>
                                    ${displayStatus}
                                    ${pendingIcon}
                                </div>
                            </td>`;
                        });

                        bodyHtml += `
                        <td class="text-center fw-bold bg-success bg-opacity-10 text-success">${stats.present}</td>
                        <td class="text-center fw-bold bg-danger bg-opacity-10 text-danger">${stats.absent}</td>
                        <td class="text-center fw-bold bg-warning bg-opacity-10 text-dark">${stats.half_day}</td>
                        <td class="text-center fw-bold bg-info bg-opacity-10 text-info">${stats.leave}</td>
                        <td class="text-center fw-bold bg-dark bg-opacity-10 text-dark">${stats.extra_day || 0}</td>
                    </tr>`;
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
                                    `onclick="openVerificationModal('${dayData.id}', '${emp.name}', '${d}', '${dayData.in || ''}', '${safeReason}', decodeURIComponent('${encodedImages}'), '${dayData.lat || ''}', '${dayData.lng || ''}')"`;
                            } else if (dayData.status !== 'N/A') {
                                clickAction =
                                    `onclick="openCorrectionModal(${emp.db_id}, '${d}', '${dayData.status}', '${dayData.lat || ''}', '${dayData.lng || ''}')"`;
                            }

                            let displayStatus = dayData.status === 'N/A' ? '-' : dayData.status;

                            let dateObj = new Date(d);
                            let dayName = dateObj.toLocaleDateString('en-US', {
                                weekday: 'short'
                            }).toUpperCase();

                            daysHtml += `
                            <div class="mob-day-box shadow-sm" ${clickAction}>
                                <div class="mob-day-num">${d.split('-')[2]}<br><span style="font-size:8px; font-weight:normal;">${dayName}</span></div>
                                <div class="status-badge bg-${dayData.status === 'N/A' ? 'NA' : dayData.status} position-relative" style="width:25px; height:25px; font-size:9px;">
                                    ${displayStatus}
                                    ${pendingIcon}
                                </div>
                            </div>`;
                        });

                        mobHtml += `
                        <div class="col-12 emp-search-card">
                            <div class="card shadow-sm border-0" style="border-radius:12px; border-left: 4px solid #1A365D !important;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                        <div>
                                            <h6 class="fw-bold mb-0 text-primary">${emp.name}</h6>
                                            <small class="text-muted" style="font-size:11px;">${emp.member_id} | ${emp.department}</small>
                                        </div>
                                        <div class="text-end" style="min-width: 60px;">
                                            <span class="badge bg-success d-block mb-1" style="font-size:9px;">P: ${stats.present}</span>
                                            <span class="badge bg-danger d-block mb-1" style="font-size:9px;">A: ${stats.absent}</span>
                                            <span class="badge bg-warning text-dark d-block" style="font-size:9px;">HD: ${stats.half_day}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                                        ${daysHtml}
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    });
                    $('#mobileViewContainer').html(mobHtml);
                }

                window.openCorrectionModal = function(empId, date, currentStatus, lat, lng) {
                    $('#corr_emp_id').val(empId);
                    $('#corr_date').val(date);

                    let displayDate = new Date(date).toLocaleString('en-IN', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    });
                    $('#corr_date_display').text(displayDate);
                    $('#corr_old_status').text(currentStatus);
                    $('#corr_new_status').val(currentStatus === 'N/A' || currentStatus === 'ED' || currentStatus ===
                        'HO' ? 'P' : currentStatus);
                    $('#corr_reason').val('');

                    if (lat && lng && lat !== 'null' && lng !== 'null' && lat !== 'undefined') {
                        $('#corr_map_link').attr('href', `https://www.google.com/maps?q=${lat},${lng}`).removeClass(
                            'd-none');
                    } else {
                        $('#corr_map_link').addClass('d-none');
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
                        headers: {
                            'Authorization': 'Bearer ' + adminToken
                        },
                        data: payload,
                        success: function(res) {
                            $('#correctionModal').modal('hide');
                            Swal.fire({
                                title: 'Locked!',
                                text: res.message || 'Status successfully updated.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            loadMatrixData();
                        },
                        error: function(err) {
                            Swal.fire('Error', 'Failed to update. Check logs.', 'error');
                        }
                    });
                });

                // Not removing getMonthName just in case, but no longer used in UI directly
                function getMonthName(m) {
                    const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov",
                        "Dec"
                    ];
                    return monthNames[parseInt(m) - 1];
                }

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

                    // User Demand: Now showing "01-06 (MON)" format for Excel headers
                    dates.forEach(d => {
                        let parts = d.split('-');
                        let dateObj = new Date(d);
                        let dayName = dateObj.toLocaleDateString('en-US', {
                            weekday: 'short'
                        }).toUpperCase();
                        headers.push(`${parts[2]}-${parts[1]} (${dayName})`);
                    });

                    headers.push("Present", "Absent", "Half Day", "Leave", "Extra Days");

                    csvContent += headers.map(h => `"${h}"`).join(",") + "\r\n";

                    matrix.forEach(row => {
                        let emp = row.employee;
                        let stats = row.stats;

                        let rowData = [
                            `"${emp.member_id || ''}"`, `"${emp.name || ''}"`,
                            `"${emp.department || ''}"`, `"${emp.designation || ''}"`
                        ];

                        dates.forEach(d => {
                            let st = row.dates[d].status;
                            rowData.push(`"${st === 'N/A' ? '-' : st}"`);
                        });

                        rowData.push(stats.present || 0, stats.absent || 0, stats.half_day || 0, stats
                            .leave || 0, stats.extra_day || 0);
                        csvContent += rowData.join(",") + "\r\n";
                    });

                    let encodedUri = encodeURI(csvContent);
                    let link = document.createElement("a");
                    let dateFileName = $('#start_date').val() ? ($('#start_date').val() + "_to_" + $(
                        '#end_date').val()) : $('#filter_month').val();

                    link.setAttribute("href", encodedUri);
                    link.setAttribute("download", `Attendance_Matrix_${dateFileName}.csv`);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });

                // LIVE SEARCH
                $('#liveSearch').on('keyup', function() {
                    let value = $(this).val().toLowerCase();
                    $('.emp-search-row').each(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                    });
                    $('.emp-search-card').each(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                    });
                });

                $('#filter_month').change(function() {
                    $('#start_date, #end_date').val('');
                });
                $('#start_date, #end_date').change(function() {
                    $('#filter_month').val('');
                });

                // HR VERIFICATION MODAL
                window.openVerificationModal = function(attendanceId, empName, punchDate, loginTime, reason, imagesJson,
                    lat, lng) {
                    $('#hrVerificationForm')[0].reset();
                    $('#verify_attendance_id').val(attendanceId);
                    $('#verify_emp_name').text(empName);
                    $('#verify_date_time').text(punchDate + ' @ ' + (loginTime || 'N/A'));
                    $('#verify_emp_reason').text(reason || 'No reason provided.');

                    if (lat && lng && lat !== 'null' && lng !== 'null' && lat !== 'undefined') {
                        $('#verify_map_link').attr('href', `https://www.google.com/maps?q=${lat},${lng}`)
                            .removeClass('d-none');
                    } else {
                        $('#verify_map_link').addClass('d-none');
                    }

                    let gallery = $('#verify_image_gallery');
                    gallery.empty();
                    if (imagesJson && imagesJson !== 'null') {
                        try {
                            let images = typeof imagesJson === 'string' ? JSON.parse(imagesJson) : imagesJson;
                            if (images.length > 0) {
                                images.forEach(img => {
                                    gallery.append(`
                                    <a href="/${img}" target="_blank" class="border rounded p-1 shadow-sm d-inline-block" style="background:#f8f9fa;">
                                        <img src="/${img}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;">
                                    </a>
                                `);
                                });
                            } else {
                                gallery.append('<span class="text-muted small">No images uploaded.</span>');
                            }
                        } catch (e) {
                            gallery.append('<span class="text-danger small">Error loading images.</span>');
                        }
                    } else {
                        gallery.append('<span class="text-muted small">No images uploaded.</span>');
                    }

                    $('#hrVerificationModal').modal('show');
                };

                $('#hrVerificationForm').submit(function(e) {
                    e.preventDefault();

                    let btn = $('#btnVerifySubmit');
                    let originalText = btn.html();
                    btn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);

                    let payload = {
                        attendance_id: $('#verify_attendance_id').val(),
                        action_status: $('#verify_action').val(),
                        final_attendance_status: $('#verify_final_status').val(),
                        hr_remark: $('#verify_hr_remark').val()
                    };

                    $.ajax({
                        url: '/api/v1/admin/attendance-verify-punch',
                        type: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + adminToken
                        },
                        data: payload,
                        success: function(res) {
                            if (res.success) {
                                Swal.fire('Verified!', res.message, 'success').then(() => {
                                    $('#hrVerificationModal').modal('hide');
                                    loadMatrixData();
                                });
                            } else {
                                Swal.fire('Error', res.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            let msg = xhr.responseJSON ? xhr.responseJSON.message :
                                'Something went wrong';
                            Swal.fire('Error', msg, 'error');
                        },
                        complete: function() {
                            btn.html(originalText).prop('disabled', false);
                        }
                    });
                });

            });
        </script>
    @endpush
