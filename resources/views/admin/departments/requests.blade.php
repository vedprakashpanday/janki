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
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color:var(--sidebar-bg);"><i
                        class="fas fa-sitemap text-warning me-2"></i>Department Requests</h4>
                <p class="text-secondary small d-none d-md-block mb-0">Review and approve pending department records</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3" id="smartFilterCard" style="display: none;">
            <div class="card-body p-3 d-flex align-items-center gap-3 flex-wrap">
                <span class="fw-bold text-secondary"><i class="fas fa-filter text-primary me-1"></i> Filter by
                    Company:</span>
                <div class="input-group" style="max-width:300px;" id="filter_col_company">
                    <select class="form-select fw-medium text-secondary shadow-sm" id="f_company">
                        <option value="">-- All Companies --</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search departments...">
            <button type="button" class="btn text-white shadow-sm px-3" style="background-color: #10b981; display: none;"
                id="mobileExcelBtn">
                <i class="fas fa-file-excel"></i>
            </button>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="deptRequestTable" class="table table-hover table-custom w-100">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center">S.No</th>
                                <th>Department Name</th>
                                <th>Assigned To (Company)</th>
                                <th>Total Designations</th>
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
                <p class="mt-2 text-muted small">Loading requests...</p>
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
        let currentUserData = null;
        let moduleBaseCode = 'dep-request';

        $(document).ready(function() {
            let currentPortal = window.location.pathname.split('/')[1] || 'admin';
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
        // 🛡️ SMART CASCADING FILTERS (Company ONLY)
        // ==========================================
        function setupSmartFilters() {
            let isGod = window.userGodMode || false;

            if (isGod) {
                $('#smartFilterCard').show();
                loadFilterCompanies();
            } else {
                $('#smartFilterCard').hide();
                if (currentUserData) {
                    $('#f_company').html(
                        `<option value="${currentUserData.company_id}">${currentUserData.company_name}</option>`);
                }
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

        $('#f_company').change(function() {
            table.ajax.reload();
        });

        // ==========================================
        // 📊 DATATABLE & EXPORT RBAC
        // ==========================================
        function initDatatable() {
            let perms = window.userPerms || [];
            let isGod = window.userGodMode || false;
            let hasExport = isGod || perms.includes(`${moduleBaseCode}_export`);

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

            table = $('#deptRequestTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                dom: '<"row mb-3 d-none d-md-flex"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: dtButtons,
                ajax: {
                    url: '/api/v1/departments-pending',
                    type: 'GET',
                    data: function(d) {
                        d.company_id = $('#f_company').val();
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
                        data: 'department_name',
                        render: d => `<span class="fw-bold text-primary">${d}</span>`
                    },
                    {
                        data: 'company_name',
                        render: d =>
                            `<span class="small fw-bold text-secondary"><i class="fas fa-building me-1"></i>${d}</span>`
                    },
                    {
                        data: 'designation_count',
                        render: d => `<span class="badge bg-secondary">${d} Designations</span>`
                    },
                    {
                        data: 'status',
                        render: () =>
                            `<span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-clock"></i> Pending</span>`
                    },
                    {
                        data: 'id',
                        className: 'text-center text-nowrap',
                        orderable: false,
                        render: function(d) {
                            let perms = window.userPerms || [];
                            let isGod = window.userGodMode || false;

                            let hasApprove = isGod || perms.includes(`${moduleBaseCode}_appr`) || perms
                                .includes(`${moduleBaseCode}_approve`);
                            let hasReject = isGod || perms.includes(`${moduleBaseCode}_rej`) || perms
                                .includes(`${moduleBaseCode}_reject`);

                            let btns = '';
                            if (hasApprove) btns +=
                                `<button class="btn btn-sm btn-success shadow-sm fw-bold px-3" onclick="updateStatus(${d}, 'active')" title="Approve"><i class="fas fa-check me-1"></i> Approve</button> `;
                            if (hasReject) btns +=
                                `<button class="btn btn-sm btn-danger shadow-sm fw-bold px-3" onclick="updateStatus(${d}, 'inactive')" title="Reject"><i class="fas fa-times me-1"></i> Reject</button>`;

                            if (!btns)
                            return `<span class="badge bg-light border text-muted"><i class="fas fa-lock me-1"></i> Locked</span>`;
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
        // 📱 PREMIUM MOBILE CARD RENDERING (Cleaned)
        // ==========================================
        function renderMobileCards(data) {
            $('#mobileLoader').hide();
            let html = '';

            if (!data || data.length === 0) {
                html =
                '<div class="text-center p-4 bg-white rounded shadow-sm text-muted">No pending requests found.</div>';
            } else {
                let perms = window.userPerms || [];
                let isGod = window.userGodMode || false;
                let hasApprove = isGod || perms.includes(`${moduleBaseCode}_appr`) || perms.includes(
                    `${moduleBaseCode}_approve`);
                let hasReject = isGod || perms.includes(`${moduleBaseCode}_rej`) || perms.includes(
                    `${moduleBaseCode}_reject`);

                data.forEach(d => {
                    let actionBtns = '';
                    if (hasApprove) actionBtns +=
                        `<button class="btn btn-sm btn-success shadow-sm fw-bold flex-fill" onclick="updateStatus(${d.id}, 'active')"><i class="fas fa-check"></i> Approve</button>`;
                    if (hasReject) actionBtns +=
                        `<button class="btn btn-sm btn-danger shadow-sm fw-bold flex-fill" onclick="updateStatus(${d.id}, 'inactive')"><i class="fas fa-times"></i> Reject</button>`;
                    if (!actionBtns) actionBtns =
                        `<span class="text-muted small fw-bold text-center w-100"><i class="fas fa-lock"></i> Action Locked</span>`;

                    html += `
                    <div class="card border border-warning shadow-sm mb-3 rounded-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">${d.department_name}</h6>
                                    <small class="text-secondary fw-bold"><i class="fas fa-building text-primary me-1"></i>${d.company_name}</small>
                                </div>
                                <span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-clock"></i> Pending</span>
                            </div>
                            <div class="mb-3 mt-2">
                                <span class="badge bg-secondary"><i class="fas fa-user-tag"></i> ${d.designation_count} Designations</span>
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

        // ==========================================
        // 🔄 STATUS UPDATE LOGIC (Approve/Reject)
        // ==========================================
        window.updateStatus = function(id, statusStr) {
            let actionName = statusStr === 'active' ? 'Approve' : 'Reject';
            let confirmColor = statusStr === 'active' ? '#198754' : '#dc3545';

            Swal.fire({
                title: `${actionName} Department?`,
                text: `Are you sure you want to ${actionName.toLowerCase()} this request?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                confirmButtonText: `Yes, ${actionName}`
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/api/v1/departments/${id}/status`,
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
