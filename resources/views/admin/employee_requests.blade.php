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
        }

        .table-custom td {
            font-size: 13px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .status-pending {
            background: #fef3c7;
            color: #9a3412;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .emp-id-badge {
            color: var(--brand-primary);
            font-weight: 700;
            font-size: 12px;
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color:var(--sidebar-bg);"><i
                        class="fas fa-user-clock text-warning me-2"></i>Employee Requests</h4>
                <p class="text-secondary small d-none d-md-block mb-0">Review and approve pending employee registrations or
                    transfers</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3" id="smartFilterCard" style="display: none;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-filter text-primary me-2"></i><span class="fw-bold text-secondary small">Smart Scope
                        Filters</span>
                </div>
                <div class="row g-2">
                    <div class="col-6 col-md-3" id="filter_col_company">
                        <select class="form-select form-select-sm shadow-sm border-primary" id="f_company">
                            <option value="">-- All Companies --</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3" id="filter_col_branch">
                        <select class="form-select form-select-sm shadow-sm border-info" id="f_branch" disabled>
                            <option value="">-- All Branches --</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3" id="filter_col_dept">
                        <select class="form-select form-select-sm shadow-sm border-warning" id="f_department" disabled>
                            <option value="">-- All Departments --</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3" id="filter_col_desig">
                        <select class="form-select form-select-sm shadow-sm border-success" id="f_designation" disabled>
                            <option value="">-- All Designations --</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search employees...">
            <button type="button" class="btn text-white shadow-sm px-3" style="background-color: #10b981; display: none;"
                id="mobileExcelBtn">
                <i class="fas fa-file-excel"></i>
            </button>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="requestTable" class="table table-hover table-custom w-100">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center">S.No</th>
                                <th>Employee Profile</th>
                                <th>Designation & Dept</th>
                                <th>Assigned To (Scope)</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-md-none" id="mobileCardsContainer">
            <div class="text-center py-5" id="mobileLoader">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted small">Loading pending requests...</p>
            </div>
        </div>

        <div id="mobilePaginationWrapper"
            class="d-flex d-md-none justify-content-between align-items-center mt-2 px-1 mb-4"></div>
    </div>
@endsection

@push('scripts')
    <script>
        @php
            $currentUser = auth()->user();
            $perms = [];
            $isGod = false;

            if ($currentUser) {
                if (method_exists($currentUser, 'getAllPermissions')) {
                    $perms = $currentUser->getAllPermissions()->pluck('name')->toArray();
                }
                if (method_exists($currentUser, 'hasRole') && $currentUser->hasRole('Super Admin')) {
                    $isGod = true;
                }
            }
        @endphp

        window.userPerms = {!! json_encode($perms) !!};
        window.userGodMode = {{ $isGod ? 'true' : 'false' }};
    </script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let table;
        let currentPortal = window.location.pathname.split('/')[1] || 'admin';
        let currentUserData = null;

        // 🔥 Aapke Module ka Base Code
        let moduleBaseCode = 'employee_request';

        $(document).ready(function() {
            $.ajax({
                url: `/api/v1/${currentPortal}/auth/me`,
                type: 'GET',
                success: function(res) {
                    currentUserData = res.data;
                    setupSmartFilters();
                    initDatatable();
                }
            });
        });

        // ==========================================
        // 🛡️ SMART CASCADING FILTERS 
        // ==========================================
        function setupSmartFilters() {
            let isGod = window.userGodMode || false;
            let isDirector = currentUserData?.designation_name?.toLowerCase().includes('director');

            $('#smartFilterCard').show();

            if (isGod) {
                loadFilterCompanies();
            } else {
                // Director & Employee both have company locked
                $('#filter_col_company').hide();
                $('#f_company').html(
                    `<option value="${currentUserData.company_id}">${currentUserData.company_name}</option>`);

                if (isDirector) {
                    loadFilterBranches(currentUserData.company_id);
                } else {
                    $('#filter_col_branch').hide();
                    let branchName = currentUserData.branch_id ? currentUserData.branch_name : 'Head Office';
                    $('#f_branch').html(`<option value="${currentUserData.branch_id || ''}">${branchName}</option>`);
                }

                // Sabhi ko Department & Designation filter karne ka haq hai
                loadFilterDepartments(currentUserData.company_id);
            }
        }

        function loadFilterCompanies() {
            $.ajax({
                url: '/api/v1/get-active-companies',
                type: 'GET',
                success: function(res) {
                    let opts = '<option value="">-- All Companies --</option>';
                    if (res.data) res.data.forEach(c => opts +=
                        `<option value="${c.id}">${c.company_name}</option>`);
                    $('#f_company').html(opts).prop('disabled', false);
                }
            });
        }

        function loadFilterBranches(compId) {
            $('#f_branch').html('<option value="">Loading...</option>').prop('disabled', true);
            $.ajax({
                url: '/api/v1/get-branches-by-companies',
                type: 'POST',
                data: {
                    company_ids: [compId]
                },
                success: function(res) {
                    let opts = '<option value="">-- All Branches / HO --</option>';
                    if (res.data) res.data.forEach(b => opts +=
                        `<option value="${b.id}">${b.branch_name}</option>`);
                    $('#f_branch').html(opts).prop('disabled', false);
                }
            });
        }

        function loadFilterDepartments(compId) {
            $('#f_department').html('<option value="">Loading...</option>').prop('disabled', true);
            $.ajax({
                url: `/api/v1/get-departments-by-company?company_id=${compId}`,
                type: 'GET',
                success: function(res) {
                    let opts = '<option value="">-- All Departments --</option>';
                    if (res.data) res.data.forEach(d => opts +=
                        `<option value="${d.id}">${d.department_name}</option>`);
                    $('#f_department').html(opts).prop('disabled', false);
                }
            });
        }

        $('#f_company').change(function() {
            let val = $(this).val();
            if (val) {
                loadFilterBranches(val);
                loadFilterDepartments(val);
            } else {
                $('#f_branch, #f_department, #f_designation').html(
                    '<option value="">-- First Select Company --</option>').prop('disabled', true);
            }
            table.ajax.reload();
        });

        $('#f_branch').change(function() {
            table.ajax.reload();
        });

        $('#f_department').change(function() {
            let deptId = $(this).val();
            if (deptId) {
                $.ajax({
                    url: `/api/v1/departments/${deptId}`,
                    type: 'GET',
                    success: function(res) {
                        let opts = '<option value="">-- All Designations --</option>';
                        if (res.data && res.data.designations) res.data.designations.forEach(d =>
                            opts += `<option value="${d.id}">${d.designation_name}</option>`);
                        $('#f_designation').html(opts).prop('disabled', false);
                    }
                });
            } else {
                $('#f_designation').html('<option value="">-- All Designations --</option>').prop('disabled', true);
            }
            table.ajax.reload();
        });

        $('#f_designation').change(function() {
            table.ajax.reload();
        });

        // ==========================================
        // 📊 DATATABLE & EXPORT
        // ==========================================
        function initDatatable() {
            let perms = window.userPerms || [];
            let isGod = window.userGodMode || false;
            let hasExport = isGod || perms.includes(`${moduleBaseCode}_export`) || perms.includes('employee_export');

            let dtButtons = [];
            if (hasExport) {
                dtButtons.push({
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export Data',
                    className: 'btn btn-success btn-sm shadow-sm fw-bold rounded-2 px-3'
                });
                $('#mobileExcelBtn').show();
            } else {
                $('#mobileExcelBtn').hide();
            }

            table = $('#requestTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                dom: '<"row mb-3 d-none d-md-flex"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: dtButtons,
                ajax: {
                    url: '/api/v1/employees-pending',
                    type: 'GET',
                    data: function(d) {
                        d.company_id = $('#f_company').val();
                        d.branch_id = $('#f_branch').val();
                        d.department_id = $('#f_department').val();
                        d.designation_id = $('#f_designation').val();
                    }
                },
                columns: [{
                        data: null,
                        searchable: false,
                        orderable: false,
                        className: 'text-center fw-bold text-secondary',
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'full_name',
                        render: (d, t, r) =>
                            `<div><span class="fw-bold text-primary d-block">${d || '-'}</span><span class="emp-id-badge">${r.member_id || '-'}</span></div>`
                    },
                    {
                        data: null,
                        render: function(data, type, r) {
                            let desigName = (typeof r.designation === 'object' && r.designation !== null) ?
                                r.designation.designation_name : (r.designation || '-');
                            let deptName = (typeof r.department === 'object' && r.department !== null) ? r
                                .department.department_name : '-';
                            return `<div class="small fw-bold text-dark">${desigName}</div><div class="small text-muted">${deptName}</div>`;
                        }
                    },
                    {
                        data: 'branch',
                        render: b => b ?
                            `<span class="small fw-bold text-secondary"><i class="fas fa-building me-1"></i>${b.company ? b.company.company_name : 'No Company'} <br><i class="fas fa-code-branch me-1"></i>${b.branch_name}</span>` :
                            'Master Head Office'
                    },
                    {
                        data: 'emp_status',
                        render: () => `<span class="status-pending"><i class="fas fa-clock"></i> Pending</span>`
                    },
                    {
                        data: 'id',
                        className: 'text-center text-nowrap',
                        orderable: false,
                        render: function(d) {
                            let perms = window.userPerms || [];
                            let isGod = window.userGodMode || false;

                            let hasApprove = isGod || perms.includes('employee_approve') || perms.includes(
                                `${moduleBaseCode}_approve`);
                            let hasReject = isGod || perms.includes('employee_reject') || perms.includes(
                                `${moduleBaseCode}_reject`);

                            let btns = '';
                            if (hasApprove) btns +=
                                `<button class="btn btn-sm btn-success shadow-sm fw-bold px-3" onclick="updateStatus(${d}, 'active')" title="Approve"><i class="fas fa-check me-1"></i> Approve</button> `;
                            if (hasReject) btns +=
                                `<button class="btn btn-sm btn-danger shadow-sm fw-bold px-3" onclick="updateStatus(${d}, 'inactive')" title="Reject"><i class="fas fa-times me-1"></i> Reject</button>`;

                            if (!btns)
                            return `<span class="text-muted small fw-bold"><i class="fas fa-lock"></i> No Rights</span>`;
                            return `<div class="d-flex justify-content-center gap-2">${btns}</div>`;
                        }
                    }
                ],
                drawCallback: function(settings) {
                    renderMobileCards(settings.json.data);
                    renderMobilePagination(settings.json);
                }
            });

            $('#mobileSearch').on('keyup', function() {
                table.search(this.value).draw();
            });
            $('#mobileExcelBtn').on('click', function() {
                table.button('.buttons-excel').trigger();
            });
        }

        // ==========================================
        // 📱 MOBILE CARD RENDERING
        // ==========================================
        function renderMobileCards(data) {
            $('#mobileLoader').hide();
            let html = '';

            let perms = window.userPerms || [];
            let isGod = window.userGodMode || false;
            let hasApprove = isGod || perms.includes('employee_approve') || perms.includes(`${moduleBaseCode}_approve`);
            let hasReject = isGod || perms.includes('employee_reject') || perms.includes(`${moduleBaseCode}_reject`);

            if (!data || data.length === 0) {
                html =
                '<div class="text-center p-4 bg-white rounded shadow-sm text-muted">No pending requests found.</div>';
            } else {
                data.forEach(emp => {
                    let branchName = emp.branch ? (emp.branch.company ? emp.branch.company.company_name :
                        'No Company') + ' <br> ' + emp.branch.branch_name : 'Master Head Office';
                    let desigName = (typeof emp.designation === 'object' && emp.designation !== null) ? emp
                        .designation.designation_name : (emp.designation || '-');
                    let deptName = (typeof emp.department === 'object' && emp.department !== null) ? emp.department
                        .department_name : '-';

                    let actionBtns = '';
                    if (hasApprove) actionBtns +=
                        `<button class="btn btn-sm btn-success shadow-sm flex-fill fw-bold" onclick="updateStatus(${emp.id}, 'active')"><i class="fas fa-check"></i> Approve</button>`;
                    if (hasReject) actionBtns +=
                        `<button class="btn btn-sm btn-danger shadow-sm flex-fill fw-bold" onclick="updateStatus(${emp.id}, 'inactive')"><i class="fas fa-times"></i> Reject</button>`;
                    if (!actionBtns) actionBtns =
                        `<span class="text-muted small fw-bold text-center w-100"><i class="fas fa-lock"></i> Action Locked</span>`;

                    html += `
                    <div class="card border border-warning shadow-sm mb-3 rounded-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-bold text-primary mb-0">${emp.full_name || 'N/A'}</h6>
                                    <span class="emp-id-badge">${emp.member_id || '-'}</span>
                                </div>
                                <span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-clock"></i> Pending</span>
                            </div>
                            
                            <div class="small text-secondary mb-3 mt-3 border bg-light p-2 rounded">
                                <div class="mb-1"><i class="fas fa-briefcase text-primary me-1"></i> ${desigName} <span class="text-muted">(${deptName})</span></div>
                                <div class="mt-2"><i class="fas fa-map-marker-alt text-danger me-1"></i> ${branchName.replace('<br>', '|')}</div>
                            </div>

                            <div class="d-flex gap-2 w-100">
                                ${actionBtns}
                            </div>
                        </div>
                    </div>`;
                });
            }
            $('#mobileCardsContainer').html(html);
        }

        function renderMobilePagination(json) {
            if (!json) return;
            let info = table.page.info();
            if (info.pages <= 1) {
                $('#mobilePaginationWrapper').html('');
                return;
            }
            let html =
                `
                <button class="btn btn-sm btn-dark px-3 m-prev" ${info.page === 0 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i> Prev</button>
                <span class="small fw-bold text-secondary">Page ${info.page + 1} of ${info.pages}</span>
                <button class="btn btn-sm btn-dark px-3 m-next" ${info.page === (info.pages - 1) ? 'disabled' : ''}>Next <i class="fas fa-chevron-right"></i></button>`;
            $('#mobilePaginationWrapper').html(html);
        }

        $(document).on('click', '.m-prev', function() {
            table.page('previous').draw('page');
        });
        $(document).on('click', '.m-next', function() {
            table.page('next').draw('page');
        });

        // 🔥 Approve/Reject Logic 🔥
        window.updateStatus = function(id, statusStr) {
            let actionName = statusStr === 'active' ? 'Approve' : 'Reject';
            let confirmColor = statusStr === 'active' ? '#198754' : '#dc3545';

            Swal.fire({
                title: `${actionName} Employee?`,
                text: `Are you sure you want to ${actionName.toLowerCase()} this employee registration request?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                confirmButtonText: `Yes, ${actionName}`
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/api/v1/employees/${id}/status`,
                        type: 'POST',
                        data: {
                            status: statusStr
                        },
                        success: function(res) {
                            Swal.fire('Success!', res.message, 'success');
                            table.ajax.reload(null, false);
                        },
                        error: function(err) {
                            Swal.fire('Error', err.responseJSON?.message || 'Something went wrong',
                                'error');
                        }
                    });
                }
            });
        };
    </script>
@endpush
