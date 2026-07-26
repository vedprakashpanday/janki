@extends('layout.app')

@section('content')
    <style>
        .select2-container--default .select2-selection--multiple,
        .select2-container--default .select2-selection--single {
            border: 1px solid #ced4da;
            border-radius: 8px;
            min-height: 40px;
            padding: 4px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: var(--brand-primary);
            border: none;
            color: #fff;
            border-radius: 4px;
            padding: 4px 10px;
            margin-top: 5px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff;
            margin-right: 8px;
        }

        .time-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: none;
        }

        .row-checkbox {
            transform: scale(1.2);
            cursor: pointer;
        }

        .mob-time-card {
            border-left: 4px solid var(--sidebar-bg);
            border-radius: 10px;
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0 text-primary"><i class="fas fa-clock me-2"></i> Attendance Time Windows</h4>
                <p class="text-muted small mb-0 d-none d-md-block">Manage Check-in / Check-out boundaries & Late Cutoffs.</p>
            </div>
            <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus-circle me-1"></i> Add / Request
            </button>
        </div>

        <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px; background: #fff;">
            <div class="card-body p-3 d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
                <div class="d-flex flex-column flex-md-row flex-grow-1 gap-2 align-items-md-center">
                    <div style="min-width: 250px;">
                        <select class="form-select form-select-sm" id="filterLocation" style="width: 100%;">
                            <option value="">All Branches / Locations</option>
                        </select>
                    </div>
                    <div class="input-group input-group-sm flex-grow-1" style="max-width: 400px;">
                        <span class="input-group-text bg-light border-primary"><i
                                class="fas fa-search text-primary"></i></span>
                        <input type="text" id="liveSearch" class="form-control border-primary"
                            placeholder="Search Company or Branch...">
                    </div>
                </div>
                <div class="text-end mt-2 mt-md-0">
                    <button class="btn btn-success btn-sm fw-bold shadow-sm secured-item"
                        data-permission="atten_wind_export" id="btnExportExcel">
                        <i class="fas fa-file-excel me-1"></i> <span class="d-none d-sm-inline">Export Excel</span>
                    </button>
                </div>
            </div>
        </div>

        <div id="bulkActionWrapper"
            class="d-none mb-3 bg-danger bg-opacity-10 p-3 rounded d-flex justify-content-between align-items-center border border-danger border-opacity-25 shadow-sm">
            <div class="fw-bold text-danger">
                <i class="fas fa-check-square me-1"></i> <span id="selectedCount">0</span> Selected
            </div>
            <div>
                <button id="btnSelectAllBulk" class="btn btn-sm btn-outline-dark me-2 fw-bold"><i class="fas fa-list"></i>
                    Select All</button>
                <button id="btnBulkDelete" class="btn btn-sm btn-danger fw-bold"><i class="fas fa-trash-alt me-1"></i>
                    Delete Selected</button>
            </div>
        </div>

        <div class="card time-card mb-4 d-none d-md-block" id="desktopDataView">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0" id="timeWindowsTable">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th style="width: 40px;" class="text-center"><input type="checkbox"
                                        class="form-check-input row-checkbox" id="masterCheckbox"></th>
                                <th>Location (Company & Branch)</th>
                                <th>Login Window</th>
                                <th>Late Cutoff</th> <!-- 🔥 NAYA -->
                                <th>Logout Window</th>
                                <th>Min Hrs</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="timeWindowsTableBody">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted"><i
                                        class="fas fa-spinner fa-spin me-2"></i> Loading data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row g-3 d-md-none" id="mobileCardsContainer"></div>
    </div>

    <!-- ADD MODAL -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i> Setup New Time Window</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="timeWindowForm">
                    <div class="modal-body bg-light p-4">
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-end mb-2">
                                <label class="fw-bold text-dark mb-0">Select Target Locations</label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2"
                                        id="btnSelectAllAdd">All</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2"
                                        id="btnClearAllAdd">Clear</button>
                                </div>
                            </div>
                            <select class="form-control select2-multiple" id="branch_selector" multiple="multiple"
                                style="width: 100%;" required></select>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-white shadow-sm">
                                    <h6 class="fw-bold text-success mb-3"><i class="fas fa-sign-in-alt me-1"></i> Login
                                        Config</h6>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="small text-muted fw-bold">Login Start</label>
                                            <input type="time" class="form-control form-control-sm border-success"
                                                id="login_start" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="small text-muted fw-bold">Login End</label>
                                            <input type="time" class="form-control form-control-sm border-success"
                                                id="login_end" required>
                                        </div>
                                        <div class="col-12 mt-3"> <!-- 🔥 NAYA: LATE THRESHOLD -->
                                            <label class="small text-danger fw-bold"><i
                                                    class="fas fa-exclamation-triangle"></i> Mark Late After
                                                (Cutoff)</label>
                                            <input type="time"
                                                class="form-control form-control-sm border-danger bg-danger bg-opacity-10 fw-bold text-danger"
                                                id="late_time" required>
                                            <small style="font-size:10px;" class="text-muted">3 Late marks = 1 Absent
                                                penalty.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-white shadow-sm h-100">
                                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-sign-out-alt me-1"></i> Logout
                                        Window</h6>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="small text-muted fw-bold">Logout Start</label>
                                            <input type="time" class="form-control form-control-sm border-primary"
                                                id="logout_start" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="small text-muted fw-bold">Logout End</label>
                                            <input type="time" class="form-control form-control-sm border-primary"
                                                id="logout_end" required>
                                        </div>
                                        <div class="col-12 mt-3">
                                            <label class="small fw-bold text-dark">Min Working Hours</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" class="form-control fw-bold border-dark"
                                                    id="min_working_hours" value="8.25" step="0.25" min="1"
                                                    required>
                                                <span class="input-group-text bg-light text-dark fw-bold">Hrs</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white">
                        <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4" id="btnSubmitAdd"><i
                                class="fas fa-save me-1"></i> Save Rules</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning">
                    <h6 class="modal-title fw-bold text-dark"><i class="fas fa-edit me-2"></i> Edit Time Window</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editTimeWindowForm">
                    <div class="modal-body p-4 bg-light">
                        <input type="hidden" id="edit_id">
                        <div class="alert alert-white shadow-sm border py-2 small fw-bold mb-4" id="edit_location_name"
                            style="background:#fff;"></div>

                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="small text-muted fw-bold">Login Start</label>
                                <input type="time" class="form-control form-control-sm border-success"
                                    id="edit_login_start" required>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted fw-bold">Login End</label>
                                <input type="time" class="form-control form-control-sm border-success"
                                    id="edit_login_end" required>
                            </div>
                            <div class="col-12 mt-2"> <!-- 🔥 NAYA -->
                                <label class="small text-danger fw-bold">Late Cutoff Time</label>
                                <input type="time" class="form-control form-control-sm border-danger"
                                    id="edit_late_time" required>
                            </div>
                            <hr class="my-2">
                            <div class="col-6">
                                <label class="small text-muted fw-bold">Logout Start</label>
                                <input type="time" class="form-control form-control-sm border-primary"
                                    id="edit_logout_start" required>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted fw-bold">Logout End</label>
                                <input type="time" class="form-control form-control-sm border-primary"
                                    id="edit_logout_end" required>
                            </div>
                        </div>
                        <div>
                            <label class="small text-muted fw-bold">Min Working Hours</label>
                            <input type="number" class="form-control form-control-sm border-dark fw-bold"
                                id="edit_min_hrs" step="0.25" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-top-0">
                        <button type="submit" class="btn btn-warning text-dark fw-bold w-100 shadow-sm"
                            id="btnSubmitEdit"><i class="fas fa-check-circle me-1"></i> Update Rule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- VIEW MODAL -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-info text-white">
                    <h6 class="modal-title fw-bold"><i class="fas fa-eye me-2"></i> Window Details</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <h5 class="fw-bold text-dark mb-1" id="view_location_name"></h5>
                    <p class="text-muted small mb-4" id="view_status_badge"></p>

                    <div class="row g-3">
                        <div class="col-6">
                            <div
                                class="p-3 border rounded bg-success bg-opacity-10 border-success border-opacity-25 h-100">
                                <div class="small text-success fw-bold mb-1"><i class="fas fa-sign-in-alt"></i> LOGIN
                                    WINDOW</div>
                                <h6 class="fw-bold mb-2 text-dark" id="view_login_window">--:-- to --:--</h6>
                                <div class="small text-danger fw-bold mt-2"><i class="fas fa-exclamation-triangle"></i>
                                    Late After: <br><span id="view_late_time">--:--</span></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div
                                class="p-3 border rounded bg-primary bg-opacity-10 border-primary border-opacity-25 h-100">
                                <div class="small text-primary fw-bold mb-1"><i class="fas fa-sign-out-alt"></i> LOGOUT
                                    WINDOW</div>
                                <h6 class="fw-bold mb-0 text-dark" id="view_logout_window">--:-- to --:--</h6>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 border rounded bg-light border-dark border-opacity-25">
                                <div class="small text-muted fw-bold mb-1"><i class="fas fa-business-time"></i> MIN
                                    WORKING HOURS</div>
                                <h5 class="fw-bold mb-0 text-dark" id="view_min_hrs">0 Hrs</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            let token = localStorage.getItem('token') || localStorage.getItem('admin_token') || localStorage
                .getItem('emp_token');
            window.currentRulesData = [];

            $('#branch_selector').select2({
                placeholder: "Search and select locations...",
                allowClear: true,
                dropdownParent: $('#addModal')
            });
            $('#filterLocation').select2({
                placeholder: "Filter by Location..."
            });

            $.ajax({
                url: '/api/v1/attendance-time-windows/dropdown',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(res) {
                    if (res.status === 'success') {
                        let options = '<option value="">All Branches / Locations</option>';
                        res.data.forEach(function(item) {
                            options += `<option value="${item.id}">${item.text}</option>`;
                        });
                        $('#branch_selector').html(options.replace(
                            '<option value="">All Branches / Locations</option>', ''));
                        $('#filterLocation').html(options);
                    }
                }
            });

            $('#btnSelectAllAdd').click(function() {
                $('#branch_selector > option').prop("selected", "selected");
                $('#branch_selector').trigger("change");
            });
            $('#btnClearAllAdd').click(function() {
                $('#branch_selector').val(null).trigger('change');
            });

            function loadData() {
                $.ajax({
                    url: '/api/v1/attendance-time-windows',
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + token
                    },
                    success: function(res) {
                        window.currentRulesData = res.data;
                        let tbody = '';
                        let mobileCards = '';

                        if (res.data.length === 0) {
                            tbody =
                                '<tr><td colspan="8" class="text-center py-4 text-muted fw-bold">No rules configured yet.</td></tr>';
                            mobileCards =
                                '<div class="col-12 text-center py-4 text-muted fw-bold">No rules configured yet.</div>';
                        } else {
                            res.data.forEach(item => {
                                let locId = item.branch_id ? 'BR_' + item.branch_id : 'HO_' +
                                    item.company_id;
                                let locName = item.company ? item.company.company_name :
                                    'Unknown';
                                let plainLocName = locName;

                                if (item.branch) {
                                    locName +=
                                        ` <i class="fas fa-arrow-right mx-1 text-muted" style="font-size:10px;"></i> <span class="text-primary">${item.branch.branch_name}</span>`;
                                    plainLocName += ` - ${item.branch.branch_name}`;
                                } else {
                                    locName +=
                                        ` <i class="fas fa-arrow-right mx-1 text-muted" style="font-size:10px;"></i> <span class="text-danger fw-bold">Head Office</span>`;
                                    plainLocName += ` (Head Office)`;
                                }

                                let badgeClass = item.status === 'active' ? 'bg-success' : (item
                                    .status === 'pending' ? 'bg-warning text-dark' :
                                    'bg-danger');
                                let safeLocName = plainLocName.replace(/'/g, "\\'");
                                let lateTimeStr = item.late_time ? item.late_time.substring(0,
                                    5) : 'N/A';

                                tbody += `
                            <tr class="rule-item" data-rule-id="${item.id}" data-loc-id="${locId}">
                                <td class="text-center"><input type="checkbox" class="form-check-input row-checkbox single-check" value="${item.id}"></td>
                                <td class="fw-bold small loc-text">${locName}</td>
                                <td><span class="badge bg-light text-success border border-success">${item.login_start.substring(0,5)} - ${item.login_end.substring(0,5)}</span></td>
                                <td><span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="fas fa-clock"></i> ${lateTimeStr}</span></td>
                                <td><span class="badge bg-light text-primary border border-primary">${item.logout_start.substring(0,5)} - ${item.logout_end.substring(0,5)}</span></td>
                                <td class="fw-bold text-dark">${item.min_working_hours} Hrs</td>
                                <td><span class="badge ${badgeClass}">${item.status.toUpperCase()}</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light border text-info me-1" onclick="viewRule('${safeLocName}', '${item.login_start}', '${item.login_end}', '${lateTimeStr}', '${item.logout_start}', '${item.logout_end}', '${item.min_working_hours}', '${badgeClass}', '${item.status}')" title="View"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-sm btn-light border text-primary me-1" onclick="editRule(${item.id}, '${safeLocName}', '${item.login_start}', '${item.login_end}', '${item.late_time || ''}', '${item.logout_start}', '${item.logout_end}', '${item.min_working_hours}')" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-light border text-danger" onclick="deleteRule(${item.id})" title="Delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>`;

                                mobileCards += `
                            <div class="col-12 rule-item" data-rule-id="${item.id}" data-loc-id="${locId}">
                                <div class="card shadow-sm mob-time-card bg-white">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2 border-bottom pb-2">
                                            <div class="d-flex gap-2">
                                                <input type="checkbox" class="form-check-input row-checkbox single-check mt-1" value="${item.id}">
                                                <div>
                                                    <h6 class="fw-bold text-dark mb-1 loc-text" style="font-size:13px;">${locName}</h6>
                                                    <span class="badge ${badgeClass}" style="font-size:9px;">${item.status.toUpperCase()}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <div class="small text-muted fw-bold" style="font-size:10px;">Login</div>
                                                <div class="fw-bold text-success small">${item.login_start.substring(0,5)} - ${item.login_end.substring(0,5)}</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="small text-muted fw-bold" style="font-size:10px;">Logout</div>
                                                <div class="fw-bold text-primary small">${item.logout_start.substring(0,5)} - ${item.logout_end.substring(0,5)}</div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size:9px;">Late Cutoff: ${lateTimeStr}</span>
                                            <div>
                                                <button class="btn btn-sm btn-outline-primary py-0 px-2 me-1" onclick="editRule(${item.id}, '${safeLocName}', '${item.login_start}', '${item.login_end}', '${item.late_time || ''}', '${item.logout_start}', '${item.logout_end}', '${item.min_working_hours}')"><i class="fas fa-edit" style="font-size:11px;"></i></button>
                                                <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteRule(${item.id})"><i class="fas fa-trash" style="font-size:11px;"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>`;
                            });
                        }
                        $('#timeWindowsTableBody').html(tbody);
                        $('#mobileCardsContainer').html(mobileCards);
                        applyFilters();
                        $('#masterCheckbox').prop('checked', false);
                        toggleBulkBar();
                    }
                });
            }
            loadData();

            function applyFilters() {
                let searchTxt = $('#liveSearch').val().toLowerCase();
                let filterLocId = $('#filterLocation').val();
                $('.rule-item').each(function() {
                    let rowText = $(this).find('.loc-text').text().toLowerCase();
                    let rowLocId = $(this).data('loc-id');
                    let matchSearch = rowText.indexOf(searchTxt) > -1;
                    let matchLocation = filterLocId === "" || filterLocId === rowLocId;
                    if (matchSearch && matchLocation) {
                        $(this).removeClass('d-none');
                    } else {
                        $(this).addClass('d-none');
                        $(this).find('.single-check').prop('checked', false);
                    }
                });
                toggleBulkBar();
            }

            $('#liveSearch').on('keyup', applyFilters);
            $('#filterLocation').on('change', applyFilters);

            $('#btnExportExcel').click(function() {
                if (window.currentRulesData.length === 0) return Swal.fire('Notice',
                    'No rules data available.', 'info');
                let visibleIds = [];
                $('.rule-item:not(.d-none)').each(function() {
                    visibleIds.push(parseInt($(this).data('rule-id')));
                });
                if (visibleIds.length === 0) return Swal.fire('Notice', 'No rules match filter.',
                'warning');

                let csvContent =
                    "data:text/csv;charset=utf-8,Location Name,Branch / Type,Login Window,Late Cutoff,Logout Window,Min Working Hours,Status\r\n";
                window.currentRulesData.forEach(item => {
                    if (visibleIds.includes(item.id)) {
                        let compName = item.company ? item.company.company_name : 'Unknown';
                        let branchName = item.branch ? item.branch.branch_name : 'HEAD OFFICE';
                        let rowData = [`"${compName}"`, `"${branchName}"`,
                            `"${item.login_start.substring(0,5)} to ${item.login_end.substring(0,5)}"`,
                            `"${item.late_time ? item.late_time.substring(0,5) : 'N/A'}"`,
                            `"${item.logout_start.substring(0,5)} to ${item.logout_end.substring(0,5)}"`,
                            `"${item.min_working_hours} Hrs"`, `"${item.status.toUpperCase()}"`
                        ];
                        csvContent += rowData.join(",") + "\r\n";
                    }
                });
                let encodedUri = encodeURI(csvContent);
                let link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", `Attendance_Time_Windows_Rules.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });

            function toggleBulkBar() {
                let checkedCount = $('.single-check:checked').length;
                $('#selectedCount').text(checkedCount);
                if (checkedCount > 0) {
                    $('#bulkActionWrapper').removeClass('d-none');
                } else {
                    $('#bulkActionWrapper').addClass('d-none');
                    $('#masterCheckbox').prop('checked', false);
                }
            }

            $(document).on('change', '#masterCheckbox', function() {
                $('.rule-item:not(.d-none) .single-check').prop('checked', $(this).prop('checked'));
                toggleBulkBar();
            });
            $(document).on('change', '.single-check', function() {
                toggleBulkBar();
            });

            $('#btnSelectAllBulk').click(function() {
                $('.rule-item:not(.d-none) .single-check').prop('checked', true);
                $('#masterCheckbox').prop('checked', true);
                toggleBulkBar();
            });

            $('#btnBulkDelete').click(function() {
                let selectedIds = [];
                $('.single-check:checked').each(function() {
                    selectedIds.push($(this).val());
                });
                if (selectedIds.length === 0) return;

                Swal.fire({
                    title: 'Delete ' + selectedIds.length + ' Rules?',
                    text: "This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, Delete All'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let btn = $(this);
                        let orig = btn.html();
                        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
                        $.ajax({
                            url: '/api/v1/attendance-time-windows/bulk-delete',
                            type: 'POST',
                            headers: {
                                'Authorization': 'Bearer ' + token
                            },
                            data: {
                                ids: selectedIds
                            },
                            success: function(res) {
                                Swal.fire('Deleted!', res.message, 'success');
                                loadData();
                            },
                            complete: function() {
                                btn.html(orig).prop('disabled', false);
                            }
                        });
                    }
                });
            });

            $('#timeWindowForm').submit(function(e) {
                e.preventDefault();
                let selections = $('#branch_selector').val();
                if (!selections || selections.length === 0) return Swal.fire('Warning',
                    'Select at least one Location.', 'warning');

                let btn = $('#btnSubmitAdd');
                let originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

                // 🔥 NAYA PAYLOAD
                let payload = {
                    selections: selections,
                    login_start: $('#login_start').val(),
                    login_end: $('#login_end').val(),
                    late_time: $('#late_time').val(),
                    logout_start: $('#logout_start').val(),
                    logout_end: $('#logout_end').val(),
                    min_working_hours: $('#min_working_hours').val()
                };

                $.ajax({
                    url: '/api/v1/attendance-time-windows/store',
                    type: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + token
                    },
                    data: payload,
                    success: function(res) {
                        Swal.fire('Success!', res.message, 'success');
                        $('#timeWindowForm')[0].reset();
                        $('#branch_selector').val(null).trigger('change');
                        $('#addModal').modal('hide');
                        loadData();
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON ? xhr.responseJSON.message :
                            'Error', 'error');
                    },
                    complete: function() {
                        btn.html(originalText).prop('disabled', false);
                    }
                });
            });

            window.viewRule = function(locName, lStart, lEnd, lateTime, loStart, loEnd, minHrs, badgeClass,
                statusText) {
                $('#view_location_name').html(locName);
                $('#view_status_badge').html(
                    `<span class="badge ${badgeClass}">${statusText.toUpperCase()}</span>`);
                $('#view_login_window').text(`${lStart.substring(0,5)} to ${lEnd.substring(0,5)}`);
                $('#view_late_time').text(lateTime);
                $('#view_logout_window').text(`${loStart.substring(0,5)} to ${loEnd.substring(0,5)}`);
                $('#view_min_hrs').text(`${minHrs} Hrs`);
                $('#viewModal').modal('show');
            };

            window.editRule = function(id, locName, lStart, lEnd, lateTime, loStart, loEnd, minHrs) {
                $('#edit_id').val(id);
                $('#edit_location_name').html(locName);
                $('#edit_login_start').val(lStart.substring(0, 5));
                $('#edit_login_end').val(lEnd.substring(0, 5));
                $('#edit_late_time').val(lateTime ? lateTime.substring(0, 5) : '');
                $('#edit_logout_start').val(loStart.substring(0, 5));
                $('#edit_logout_end').val(loEnd.substring(0, 5));
                $('#edit_min_hrs').val(minHrs);
                $('#editModal').modal('show');
            };

            $('#editTimeWindowForm').submit(function(e) {
                e.preventDefault();
                let id = $('#edit_id').val();
                let btn = $('#btnSubmitEdit');
                let originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

                let payload = {
                    login_start: $('#edit_login_start').val(),
                    login_end: $('#edit_login_end').val(),
                    late_time: $('#edit_late_time').val(), // 🔥 NAYA
                    logout_start: $('#edit_logout_start').val(),
                    logout_end: $('#edit_logout_end').val(),
                    min_working_hours: $('#edit_min_hrs').val()
                };

                $.ajax({
                    url: '/api/v1/attendance-time-windows/' + id,
                    type: 'PUT',
                    headers: {
                        'Authorization': 'Bearer ' + token
                    },
                    data: payload,
                    success: function(res) {
                        $('#editModal').modal('hide');
                        Swal.fire('Updated!', res.message, 'success');
                        loadData();
                    },
                    complete: function() {
                        btn.html(originalText).prop('disabled', false);
                    }
                });
            });

            window.deleteRule = function(id) {
                Swal.fire({
                    title: 'Delete this rule?',
                    text: "Users at this location will lose these timing restrictions.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/api/v1/attendance-time-windows/' + id,
                            type: 'DELETE',
                            headers: {
                                'Authorization': 'Bearer ' + token
                            },
                            success: function(res) {
                                Swal.fire('Deleted!', res.message, 'success');
                                loadData();
                            }
                        });
                    }
                });
            };
        });
    </script>
@endpush
