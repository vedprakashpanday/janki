@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        .table-custom th {
            background-color: var(--sidebar-bg);
            color: #fff;
            font-size: 13px;
            border: none;
            padding: 12px 15px;
        }

        .table-custom td {
            font-size: 13px;
            vertical-align: middle;
            padding: 12px 15px;
        }

        .mobile-item {
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px var(--shadow-color);
        }

        .status-badge-allow {
            background: #dcfce7;
            color: #166534;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .status-badge-deny {
            background: #fee2e2;
            color: #991b1b;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        /* Print Table Styling for Modal */
        @media print {
            body * {
                visibility: hidden;
            }

            #printArea,
            #printArea * {
                visibility: visible;
            }

            #printArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);"><i class="fas fa-key me-2 text-primary"></i>
                    Workspace Access</h4>
            </div>
            <button type="button" class="btn text-white px-3 py-2 shadow-sm secured-item" data-permission="panel_access_add"
                style="background-color: var(--brand-primary);" id="btnOpenGenerateModal">
                <i class="fas fa-plus me-1"></i> Generate ID
            </button>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <span class="fw-bold text-secondary"><i class="fas fa-filter text-primary me-1"></i> Global Filter:</span>
                <div class="input-group" style="max-width: 400px;">
                    <input type="text" class="form-control fw-medium text-secondary" id="branch_filter_input"
                        list="filterBranchList" placeholder="Select Branch to load registers..." autocomplete="off">
                    <input type="hidden" id="hidden_branch_filter_id">
                    <datalist id="filterBranchList"></datalist>
                </div>
            </div>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search matching logs...">
            <button type="button" class="btn text-white shadow-sm px-3" style="background-color: #10b981;"
                id="mobileExcelBtn"><i class="fas fa-file-excel"></i></button>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="accessTable" class="table table-hover table-custom w-100">
                        <thead>
                            <tr>
                                <th>User Profile</th>
                                <th>Panel Scope</th>
                                <th>Login ID</th>
                                <th>Shift Timings</th>
                                <th>Hardware Binding</th>
                                <th>Intrusion Alerts</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="mobileCardsContainer" class="d-block d-md-none"></div>
        <div id="mobilePaginationWrapper" class="d-flex d-md-none justify-content-between align-items-center mt-2 px-1">
        </div>
    </div>

    <div class="modal fade" id="generateAccessModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i
                            class="fas fa-user-plus me-2 text-primary"></i> Generate New Access</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="generateAccessForm">

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Select Company <span
                                    class="text-danger">*</span></label>
                            <input type="text" id="g_company_input" class="form-control" list="g_companyList"
                                placeholder="Search Company" autocomplete="off" required>
                            <input type="hidden" id="g_company_id">
                            <datalist id="g_companyList"></datalist>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Select Branch <span
                                    class="text-danger">*</span></label>
                            <input type="text" id="g_branch_input" class="form-control" list="g_branchList"
                                placeholder="First select company" autocomplete="off" required disabled>
                            <input type="hidden" id="g_branch_id">
                            <datalist id="g_branchList"></datalist>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Select Department <span
                                    class="text-danger">*</span></label>
                            <input type="text" id="g_dept_input" class="form-control" list="g_deptList"
                                placeholder="First select branch" autocomplete="off" required disabled>
                            <input type="hidden" id="g_dept_id">
                            <datalist id="g_deptList"></datalist>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Select Employee <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="g_emp_search" list="g_empList"
                                placeholder="First select department" required autocomplete="off" disabled>
                            <input type="hidden" name="user_id" id="g_user_id" required>
                            <datalist id="g_empList"></datalist>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-secondary small fw-bold">Assign Work Panel <span
                                    class="text-danger">*</span></label>
                            <select name="panel_assign" class="form-select" id="g_panel_assign" required>
                                <option value="Employee">Employee Panel</option>
                                <option value="Branch Manager">Branch Manager Panel</option>
                                <option value="Associate">Associate Panel</option>
                            </select>
                        </div>

                        <button type="submit" class="btn text-white w-100 fw-bold shadow-sm"
                            style="background-color: var(--brand-primary);" id="generateBtn">
                            <i class="fas fa-cogs me-1"></i> Generate ID & Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="credentialsModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white border-bottom-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-check-circle me-2"></i> Allocation Successful!</h5>
                    <button type="button" class="btn-close btn-close-white shadow-none"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="text-muted small">Share these randomized parameters with the employee.</p>
                    <div class="bg-light p-3 rounded border">
                        <h5 class="mb-2">Login ID: <strong class="text-primary" id="show_panel_id"></strong></h5>
                        <h5 class="mb-0">Password: <strong class="text-danger" id="show_password"></strong></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="emergencyAccessModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark border-bottom-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-unlock-alt me-2"></i> Allocate Shift Override</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <form id="emergencyAccessForm">
                        <input type="hidden" id="e_panel_id">
                        <input type="hidden" id="e_device_token">
                        <div class="row g-3">
                            <div class="col-6"><label class="form-label small">Time From</label><input type="time"
                                    class="form-control" id="e_time_from" required></div>
                            <div class="col-6"><label class="form-label small">Time To</label><input type="time"
                                    class="form-control" id="e_time_to" required></div>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 fw-bold mt-4" id="btnEmergencySave">Authorize
                            Time Parameters</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deviceRequestsModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white border-bottom-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-shield-alt text-danger me-2"></i> Security Gateway
                        Logs</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light text-dark fw-bold" onclick="window.print()"><i
                                class="fas fa-print me-1"></i> Print</button>
                        <button type="button" class="btn-close btn-close-white shadow-none"
                            data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body p-3 bg-light" id="printArea">
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-bordered bg-white shadow-sm rounded vertical-middle mb-0">
                            <thead class="table-dark small">
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Digital Signature (Token)</th>
                                    <th>Telemetry Coordinates</th>
                                    <th class="text-end no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="requestsTableBody"></tbody>
                        </table>
                    </div>
                    <div id="requestsMobileContainer" class="d-block d-md-none no-print"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {

            // Maps for hierarchical datalists
            let gCompanyMap = {},
                gBranchMap = {},
                gDeptMap = {},
                gEmpMap = {};
            let branchFilterMap = {};

            // Initial Load for Filter Branches (Table Ke Liye)
            $.ajax({
                url: '/api/v1/branches',
                type: 'GET',
                success: function(res) {
                    let options = '<option value="">-- View All Registered Branches --</option>';
                    branchFilterMap = {};
                    res.data.forEach(b => {
                        let cName = b.company ? b.company.company_name : 'JankiVilla';
                        let str = `${cName} - ${b.branch_name} (${b.branch_id})`;
                        options += `<option value="${str}">`;
                        branchFilterMap[str] = b.id;
                    });
                    $('#filterBranchList').html(options);
                }
            });

            // ==========================================
            // 🔥 CASCADING DATALISTS FOR MODAL 🔥
            // ==========================================

            // Open Modal Action
            $('#btnOpenGenerateModal').click(function() {
                $('#generateAccessForm')[0].reset();

                // Disable children
                $('#g_branch_input').prop('disabled', true).val('').attr('placeholder',
                    'First select company');
                $('#g_dept_input').prop('disabled', true).val('').attr('placeholder',
                'First select branch');
                $('#g_emp_search').prop('disabled', true).val('').attr('placeholder',
                    'First select department');
                $('#g_user_id, #g_dept_id, #g_branch_id, #g_company_id').val('');

                // Load Companies Freshly
                $.ajax({
                    url: '/api/v1/get-active-companies',
                    type: 'GET',
                    success: function(res) {
                        let opts = '';
                        gCompanyMap = {};
                        if (res.data) {
                            res.data.forEach(c => {
                                opts += `<option value="${c.company_name}">`;
                                gCompanyMap[c.company_name] = c.id;
                            });
                        }
                        $('#g_companyList').html(opts);
                    }
                });

                $('#generateAccessModal').modal('show');
            });

            // 1. Company Selection -> Load Branches
            $('#g_company_input').on('input change', function() {
                let val = $(this).val();
                if (gCompanyMap[val]) {
                    $('#g_company_id').val(gCompanyMap[val]);
                    this.setCustomValidity('');

                    // Reset and load branches
                    $('#g_branch_input').prop('disabled', true).val('Loading branches...');
                    $('#g_dept_input').prop('disabled', true).val('');
                    $('#g_emp_search').prop('disabled', true).val('');

                    $.ajax({
                        url: '/api/v1/branches?company_id=' + gCompanyMap[val],
                        type: 'GET',
                        success: function(res) {
                            let opts = '';
                            gBranchMap = {};
                            res.data.forEach(b => {
                                opts +=
                                    `<option value="${b.branch_name} (${b.branch_id})">`;
                                gBranchMap[`${b.branch_name} (${b.branch_id})`] = b.id;
                            });
                            $('#g_branchList').html(opts);
                            $('#g_branch_input').prop('disabled', false).val('').attr(
                                'placeholder', 'Search Branch');
                        }
                    });
                } else {
                    $('#g_company_id').val('');
                    if (val !== '') this.setCustomValidity('Select valid company');
                }
            });

            // 2. Branch Selection -> Load Departments
            $('#g_branch_input').on('input change', function() {
                let val = $(this).val();
                if (gBranchMap[val]) {
                    $('#g_branch_id').val(gBranchMap[val]);
                    this.setCustomValidity('');

                    $('#g_dept_input').prop('disabled', true).val('Loading departments...');
                    $('#g_emp_search').prop('disabled', true).val('');

                    $.ajax({
                        url: '/api/v1/get-departments-by-company?company_id=' + $('#g_company_id')
                            .val(),
                        type: 'GET',
                        success: function(res) {
                            let opts = '';
                            gDeptMap = {};
                            res.data.forEach(d => {
                                opts += `<option value="${d.department_name}">`;
                                gDeptMap[d.department_name] = d.id;
                            });
                            $('#g_deptList').html(opts);
                            $('#g_dept_input').prop('disabled', false).val('').attr(
                                'placeholder', 'Search Department');
                        }
                    });
                } else {
                    $('#g_branch_id').val('');
                    if (val !== '') this.setCustomValidity('Select valid branch');
                }
            });

            // 3. Department Selection -> Load Employees
            $('#g_dept_input').on('input change', function() {
                let val = $(this).val();
                if (gDeptMap[val]) {
                    $('#g_dept_id').val(gDeptMap[val]);
                    this.setCustomValidity('');

                    $('#g_emp_search').prop('disabled', true).val('Loading employees...');

                    // Note: Update your backend controller to accept 'department_id' parameter
                    $.ajax({
                        url: `/api/v1/get-employees-list?branch_id=${$('#g_branch_id').val()}&department_id=${gDeptMap[val]}`,
                        type: 'GET',
                        success: function(res) {
                            let opts = '';
                            gEmpMap = {};
                            res.data.forEach(e => {
                                let disp = `${e.full_name} (${e.member_id})`;
                                opts += `<option value="${disp}">`;
                                gEmpMap[disp] = e
                                .member_id; // Using member_id mapped to user_id input
                            });
                            $('#g_empList').html(opts);
                            $('#g_emp_search').prop('disabled', false).val('').attr(
                                'placeholder', 'Search Employee');
                        }
                    });
                } else {
                    $('#g_dept_id').val('');
                    if (val !== '') this.setCustomValidity('Select valid department');
                }
            });

            // 4. Employee Selection
            $('#g_emp_search').on('input change', function() {
                let val = $(this).val();
                if (gEmpMap[val]) {
                    $('#g_user_id').val(gEmpMap[val]);
                    this.setCustomValidity('');
                } else {
                    $('#g_user_id').val('');
                    if (val !== '') this.setCustomValidity('Select valid employee');
                }
            });


            // ==========================================
            // DataTable & Table Filtering
            // ==========================================
            $('#branch_filter_input').on('input change', function() {
                let val = $(this).val();
                $('#hidden_branch_filter_id').val(branchFilterMap[val] || '');
                table.ajax.reload();
            });

            let table = $('#accessTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/panel-access',
                    type: 'GET',
                    data: function(d) {
                        d.branch_id = $('#hidden_branch_filter_id').val();
                    }
                },
                dom: '<"row mb-3 d-none d-md-flex"<"col-md-6 d-flex align-items-center gap-3"lB><"col-md-6"f>>rt<"row mt-3 d-none d-md-flex"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export Selected Branch Data',
                    className: 'btn btn-success btn-sm shadow-sm rounded-2 fw-bold'
                }],
                columns: [{
                        data: 'full_name',
                        render: (d, t, r) =>
                            `<div><span class="fw-bold text-dark d-block">${d}</span><small class="text-secondary">${r.user_id}</small></div>`
                    },
                    {
                        data: 'panel_assign',
                        render: d => `<span class="badge bg-secondary small">${d}</span>`
                    },
                    {
                        data: 'panel_id',
                        render: d => `<code class="fw-bold text-primary">${d}</code>`
                    },
                    {
                        data: 'p_time_from',
                        render: (d, t, r) =>
                            `<span class="small fw-medium"><i class="far fa-clock text-warning me-1"></i> ${d.substring(0,5)} to ${r.p_time_to.substring(0,5)}</span>`
                    },
                    {
                        data: 'primary_device',
                        render: d => d ?
                            `<span class="text-success small fw-bold"><i class="fas fa-desktop me-1"></i> Bound</span>` :
                            `<span class="text-warning small fw-bold"><i class="fas fa-unlink"></i> Unbound</span>`
                    },
                    {
                        data: 'other_devices',
                        render: (d, t, r) => {
                            let attempts = d ? d.length : 0;
                            let blocked = r.blocked_devices ? r.blocked_devices.length : 0;
                            return `<div>
                    <button type="button" class="btn btn-sm btn-outline-danger view-requests-btn py-0 px-2 small position-relative mb-1" data-id="${r.panel_id}">
                        <i class="fas fa-radar me-1"></i> Logs <span class="badge bg-danger ms-1">${attempts}</span>
                    </button>
                    <span class="d-block small text-muted"><i class="fas fa-ban text-secondary me-1"></i> Blocked: <b>${blocked}</b></span>
                </div>`;
                        }
                    },
                    {
                        data: 'p_status',
                        render: d => d === 'allow' ?
                            `<span class="status-badge-allow">Permitted</span>` :
                            `<span class="status-badge-deny">Revoked</span>`
                    },
                    {
                        data: 'panel_id',
                        orderable: false,
                        className: 'text-end',
                        render: d =>
                            `<button type="button" class="btn btn-sm btn-light text-warning fw-bold emergency-btn shadow-sm secured-item" data-permission="panel_access_edit" data-id="${d}"><i class="fas fa-unlock-alt"></i> Override</button>`
                    }
                ],
                drawCallback: function(settings) {
                    renderMobileCards(settings.json.data);
                    renderMobilePagination(settings.json);
                    if (typeof window.applyPermissions === 'function') window.applyPermissions();
                }
            });

            // Mobile Search & Mobile Excel Link
            $('#mobileSearch').on('keyup', function(e) {
                if (e.key === 'Enter') table.search($(this).val()).draw();
            });
            $('#mobileExcelBtn').click(() => {
                $('.buttons-excel').click();
            });

            // Submit Logic
            $('#generateAccessForm').submit(function(e) {
                e.preventDefault();
                let btn = $('#generateBtn');
                btn.prop('disabled', true).text('Syncing...');

                $.ajax({
                    url: '/api/v1/generate-access',
                    type: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    },
                    data: {
                        user_id: $('#g_user_id').val(),
                        panel_assign: $('#g_panel_assign').val()
                    },
                    success: function(res) {
                        table.ajax.reload(null, false);
                        $('#generateAccessModal').modal('hide');
                        $('#show_panel_id').text(res.data.panel_id);
                        $('#show_password').text(res.data.panel_password);
                        $('#credentialsModal').modal('show');
                    },
                    error: function(err) {
                        alert(err.responseJSON.message || "Validation Error Occurred!");
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fas fa-cogs me-1"></i> Generate ID & Password');
                    }
                });
            });

            // Mobile Cards Renderer
            function renderMobileCards(data) {
                let html = '';
                if (!data || data.length === 0) {
                    html =
                        '<div class="text-center p-4 text-muted border bg-white rounded-3">No matching profiles indexed inside this branch query.</div>';
                } else {
                    data.forEach(r => {
                        let hardwareStatus = r.primary_device ?
                            `<span class="text-success fw-bold small"><i class="fas fa-check-circle"></i> Bound</span>` :
                            `<span class="text-warning fw-bold small"><i class="fas fa-exclamation-circle"></i> Unbound</span>`;
                        let reqCount = r.other_devices ? r.other_devices.length : 0;
                        let blockCount = r.blocked_devices ? r.blocked_devices.length : 0;

                        html += `<div class="mobile-item">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">${r.full_name}</h6>
                    <small class="text-muted">${r.user_id} | Scope: <b>${r.panel_assign}</b></small>
                </div>
                <span class="${r.p_status === 'allow' ? 'status-badge-allow' : 'status-badge-deny'}">${r.p_status === 'allow' ? 'Active' : 'Revoked'}</span>
            </div>
            <div class="row g-2 border-top border-bottom py-2 my-2 small bg-light px-1 rounded">
                <div class="col-6">Login ID: <code class="fw-bold">${r.panel_id}</code></div>
                <div class="col-6 text-end">${hardwareStatus}</div>
                <div class="col-12"><i class="far fa-clock text-warning me-1"></i> Allowed: ${r.p_time_from.substring(0,5)} - ${r.p_time_to.substring(0,5)}</div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="small text-muted"><i class="fas fa-fingerprint text-danger me-1"></i> Blocked: <b>${blockCount}</b></span>
                <button type="button" class="btn btn-sm btn-danger view-requests-btn py-1" data-id="${r.panel_id}">
                    <i class="fas fa-shield-alt me-1"></i> Control Room (${reqCount})
                </button>
            </div>
            <button type="button" class="btn btn-sm btn-outline-warning w-100 fw-bold emergency-btn py-2 secured-item" data-permission="panel_access_edit" data-id="${r.panel_id}"><i class="fas fa-unlock-alt me-1"></i> Authorize Temporal Shift</button>
        </div>`;
                    });
                }
                $('#mobileCardsContainer').html(html);
                if (typeof window.applyPermissions === 'function') window.applyPermissions();
            }

            function renderMobilePagination(json) {
                if (!json) return;
                let info = table.page.info();
                if (info.pages <= 1) {
                    $('#mobilePaginationWrapper').html('');
                    return;
                }
                let html = `
    <button class="btn btn-sm btn-dark px-3 m-prev" ${info.page === 0 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i> Prev</button>
    <span class="small fw-bold text-secondary">Page ${info.page + 1} of ${info.pages}</span>
    <button class="btn btn-sm btn-dark px-3 m-next" ${info.page === (info.pages - 1) ? 'disabled' : ''}>Next <i class="fas fa-chevron-right"></i></button>
`;
                $('#mobilePaginationWrapper').html(html);
            }

            $(document).on('click', '.m-prev', function() {
                table.page('previous').draw('page');
            });
            $(document).on('click', '.m-next', function() {
                table.page('next').draw('page');
            });

            $(document).on('click', '.emergency-btn', function() {
                $('#emergencyAccessForm')[0].reset();
                $('#e_panel_id').val($(this).data('id'));
                $('#emergencyAccessModal').modal('show');
            });

            $('#emergencyAccessForm').submit(function(e) {
                e.preventDefault();
                let btn = $('#btnEmergencySave');
                btn.prop('disabled', true).text('Processing...');
                $.ajax({
                    url: '/api/v1/grant-emergency-access',
                    type: 'POST',
                    data: {
                        panel_id: $('#e_panel_id').val(),
                        s_time_from: $('#e_time_from').val(),
                        s_time_to: $('#e_time_to').val(),
                        device_token: $('#e_device_token').val() || ''
                    },
                    success: function(res) {
                        $('#emergencyAccessModal').modal('hide');
                        table.ajax.reload(null, false);
                        alert(res.message);
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Authorize Time Parameters');
                    }
                });
            });

            $(document).on('click', '.view-requests-btn', function() {
                let pId = $(this).data('id');
                let rowData = table.row($(this).closest('tr').length ? $(this).closest('tr') : table.row(
                    function(idx, data) {
                        return data.panel_id === pId;
                    }
                )).data();

                let attempts = rowData.other_devices || [];
                let blocked = rowData.blocked_devices || [];

                let desktopHtml = '',
                    mobileHtml = '';

                blocked.forEach(token => {
                    let rowMarkup = `
        <tr class="table-danger">
            <td><span class="badge bg-danger">BLOCKED SIGNATURE</span></td>
            <td><code class="small fw-bold text-dark">${token.substring(0,12)}...</code></td>
            <td>System Sandbox Protection</td>
            <td class="text-end">
                <button class="btn btn-sm btn-success fw-bold unblock-device-btn" data-panel="${pId}" data-token="${token}"><i class="fas fa-check-circle"></i> Unblock Device</button>
            </td>
        </tr>`;
                    desktopHtml += rowMarkup;

                    mobileHtml += `<div class="card border border-danger mb-2 p-2 bg-white shadow-sm">
        <div class="d-flex justify-content-between align-items-center">
            <span class="badge bg-danger small">BLOCKED SIGNATURE</span>
            <button class="btn btn-sm btn-success fw-bold unblock-device-btn" data-panel="${pId}" data-token="${token}">Unblock</button>
        </div>
        <div class="small mt-1 font-monospace">Token: ${token.substring(0,15)}...</div>
    </div>`;
                });

                attempts.forEach(a => {
                    let mapBtn = (a.latitude && a.latitude !== 'Location Denied') ?
                        `<a href="https://maps.google.com/?q=${a.latitude},${a.longitude}" target="_blank" class="btn btn-xs btn-outline-primary py-0 small"><i class="fas fa-map-marker-alt text-danger"></i> View GPS Map</a>` :
                        '<span class="text-muted small">No GPS Coordinates</span>';

                    desktopHtml += `<tr>
        <td class="small fw-bold text-secondary">${a.time}</td>
        <td><code class="small">${a.device_token.substring(0,12)}...</code></td>
        <td>${mapBtn}</td>
        <td class="text-end">
            <div class="btn-group shadow-sm">
                <button class="btn btn-sm btn-success quick-approve-btn" data-panel="${pId}" data-token="${a.device_token}"><i class="fas fa-check"></i> Allow</button>
                <button class="btn btn-sm btn-warning reject-device-btn" data-panel="${pId}" data-token="${a.device_token}"><i class="fas fa-times"></i> Reject</button>
                <button class="btn btn-sm btn-danger block-device-btn" data-panel="${pId}" data-token="${a.device_token}"><i class="fas fa-ban"></i> Block</button>
            </div>
        </td>
    </tr>`;

                    mobileHtml += `<div class="card border mb-2 p-3 bg-white shadow-sm rounded-3">
        <div class="fw-bold small text-muted mb-1"><i class="far fa-clock text-warning"></i> At: ${a.time}</div>
        <div class="small font-monospace mb-2 text-truncate">ID: <code>${a.device_token}</code></div>
        <div class="mb-3">${mapBtn}</div>
        <div class="d-flex gap-1">
            <button class="btn btn-sm btn-success flex-fill quick-approve-btn" data-panel="${pId}" data-token="${a.device_token}">Allow</button>
            <button class="btn btn-sm btn-warning flex-fill reject-device-btn" data-panel="${pId}" data-token="${a.device_token}">Reject</button>
            <button class="btn btn-sm btn-danger flex-fill block-device-btn" data-panel="${pId}" data-token="${a.device_token}">Block</button>
        </div>
    </div>`;
                });

                if (desktopHtml === '') desktopHtml =
                    '<tr><td colspan="4" class="text-center p-3 text-muted">All active nodes clear. Security protocol intact.</td></tr>';
                if (mobileHtml === '') mobileHtml =
                    '<div class="text-center text-muted small p-3 bg-white border rounded">All active nodes clear.</div>';

                $('#requestsTableBody').html(desktopHtml);
                $('#requestsMobileContainer').html(mobileHtml);
                $('#deviceRequestsModal').modal('show');
            });

            $(document).on('click', '.quick-approve-btn', function() {
                let pId = $(this).data('panel'),
                    token = $(this).data('token');
                $('#deviceRequestsModal').modal('hide');
                $('#emergencyAccessForm')[0].reset();
                $('#e_panel_id').val(pId);
                if ($('#e_device_token').length === 0) $('#emergencyAccessForm').append(
                    `<input type="hidden" id="e_device_token">`);
                $('#e_device_token').val(token);
                $('#emergencyAccessModal').modal('show');
            });

            $(document).on('click', '.reject-device-btn', function() {
                if (confirm("Reject this unique entry log attempt? Employee stays on login screen.")) {
                    executeNodeUpdate('/api/v1/reject-device', $(this).data('panel'), $(this).data(
                    'token'));
                }
            });

            $(document).on('click', '.block-device-btn', function() {
                if (confirm("CRITICAL WARNING: Block this hardware node signature permanently?")) {
                    executeNodeUpdate('/api/v1/block-device', $(this).data('panel'), $(this).data('token'));
                }
            });

            $(document).on('click', '.unblock-device-btn', function() {
                if (confirm("Restore connectivity for this blocked signature?")) {
                    executeNodeUpdate('/api/v1/unblock-device', $(this).data('panel'), $(this).data(
                        'token'));
                }
            });

            function executeNodeUpdate(targetUrl, pId, tokenSignature) {
                $.ajax({
                    url: targetUrl,
                    type: 'POST',
                    data: {
                        panel_id: pId,
                        device_token: tokenSignature
                    },
                    success: function(res) {
                        $('#deviceRequestsModal').modal('hide');
                        table.ajax.reload(null, false);
                        alert(res.message);
                    },
                    error: function(err) {
                        alert(err.responseJSON.message || 'Transmission error');
                    }
                });
            }

        });
    </script>
@endpush
