@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        /* 🔥 MOBILE CARDS VIEW CSS 🔥 */
        @media (max-width: 767.98px) {
            #noticesTable thead {
                display: none;
            }

            #noticesTable tbody tr {
                display: block;
                margin-bottom: 15px;
                background: #fff;
                border: 1px solid var(--border-color);
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                padding: 10px;
            }

            #noticesTable tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid #f1f5f9;
                padding: 8px 5px;
                text-align: right;
            }

            #noticesTable tbody td::before {
                content: attr(data-label);
                font-weight: bold;
                color: #475569;
                text-align: left;
                margin-right: 15px;
            }

            #noticesTable tbody td:last-child {
                border-bottom: none;
                justify-content: center;
                gap: 10px;
            }

            /* Fix Checkbox alignment in Mobile */
            #noticesTable tbody td:first-child {
                justify-content: flex-start !important;
                gap: 15px;
            }

            #noticesTable tbody td:first-child::before {
                margin-right: auto;
            }

            /* Hide Desktop Pagination in Mobile */
            .dataTables_paginate,
            .dataTables_info,
            .dataTables_length {
                display: none !important;
            }

            /* Modal Bottom Sheet */
            .modal-responsive-custom .modal-dialog {
                position: fixed;
                bottom: 0;
                margin: 0;
                width: 100%;
                max-width: 100%;
                transform: translateY(100%);
                transition: transform 0.3s ease-out;
            }

            .modal-responsive-custom.show .modal-dialog {
                transform: translateY(0);
            }

            .modal-responsive-custom .modal-content {
                border-radius: 20px 20px 0 0;
                height: 90vh;
                overflow-y: auto;
            }
        }

        .tox-tinymce {
            border-radius: 8px !important;
            border: 1px solid var(--border-color) !important;
        }

        .dt-buttons .btn {
            margin-right: 5px;
            font-weight: bold;
            border-radius: 5px;
        }
    </style>

    <div class="container-fluid mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold mb-0" style="color: var(--sidebar-bg);">
                <i class="fas fa-bullhorn text-warning me-2"></i> Notice Board Management
            </h5>

            <div class="d-flex gap-2">
                <button class="btn btn-danger shadow-sm fw-bold secured-item" id="bulkDeleteBtn"
                    data-permission="notices_delete" style="display: none;">
                    <i class="fas fa-trash-alt me-2"></i> Delete Selected
                </button>

                <button class="btn btn-info text-white shadow-sm fw-bold secured-item" data-permission="notices_add_request"
                    onclick="openNoticeModal('request')">
                    <i class="fas fa-hand-paper me-2"></i> Request Notice
                </button>

                <button class="btn btn-primary shadow-sm fw-bold secured-item" data-permission="notices_add_direct"
                    onclick="openNoticeModal('direct')">
                    <i class="fas fa-paper-plane me-2"></i> Publish Notice
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3 bg-light">
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="small fw-bold">Start Date</label>
                        <input type="date" id="filter_start_date" class="form-control form-control-sm shadow-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">End Date</label>
                        <input type="date" id="filter_end_date" class="form-control form-control-sm shadow-sm">
                    </div>
                    <div class="col-md-2 filter-company-div">
                        <label class="small fw-bold">Company</label>
                        <select id="filter_company" class="form-select form-select-sm shadow-sm">
                            <option value="">All Companies</option>
                        </select>
                    </div>
                    <div class="col-md-2 filter-branch-div">
                        <label class="small fw-bold">Branch</label>
                        <select id="filter_branch" class="form-select form-select-sm shadow-sm">
                            <option value="">All Branches</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Department</label>
                        <select id="filter_department" class="form-select form-select-sm shadow-sm">
                            <option value="">All Depts</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-sm btn-dark w-100 shadow-sm fw-bold" onclick="loadTableData()"><i
                                class="fas fa-filter"></i> Filter</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="mobileBulkActionBar"
            class="d-flex d-md-none align-items-center justify-content-between mb-3 p-2 bg-white border rounded shadow-sm"
            style="display: none !important;">
            <div class="form-check mb-0">
                <input type="checkbox" id="mobileSelectAllCheckbox" class="form-check-input"
                    style="transform: scale(1.3); cursor: pointer;">
                <label class="form-check-label fw-bold ms-2" for="mobileSelectAllCheckbox">Select All</label>
            </div>
            <button class="btn btn-sm btn-danger shadow-sm fw-bold secured-item" id="mobileBulkDeleteBtn"
                data-permission="notices_delete" style="display: none;">
                <i class="fas fa-trash-alt me-1"></i> Delete (<span id="mobileSelectedCount">0</span>)
            </button>
        </div>

        <div class="card shadow-sm border-0 secured-item" data-permission="notices_view">
            <div class="card-body p-0 p-md-3">
                <table id="noticesTable" class="table table-hover w-100" style="font-size: 13.5px;">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 40px;" class="text-center"><input type="checkbox" id="selectAllCheckbox"
                                    class="form-check-input"></th>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Target</th>
                            <th>Requires Reply</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="text-center mt-3 d-md-none" id="mobileLoadMoreContainer" style="display: none;">
            <button id="mobileLoadMoreBtn" class="btn btn-primary btn-sm fw-bold px-4 shadow-sm">
                <i class="fas fa-sync-alt me-2"></i> Load More
            </button>
        </div>

    </div>

    <div class="modal fade modal-responsive-custom" id="noticeModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold" id="modalTitle"><i class="fas fa-bullhorn text-primary me-2"></i> Notice
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="noticeForm">
                        <input type="hidden" id="notice_id">

                        <div class="row g-3 mb-3 pb-3 border-bottom bg-light p-2 rounded">
                            <div class="col-12">
                                <h6 class="fw-bold text-secondary mb-0"><i class="fas fa-sitemap"></i> Organization
                                    Targeting</h6>
                            </div>
                            <div class="col-md-4 form-company-div">
                                <label class="form-label small fw-bold">Target Company <span
                                        class="text-danger">*</span></label>
                                <select id="form_company_id" class="form-select shadow-sm" required></select>
                            </div>
                            <div class="col-md-4 form-branch-div">
                                <label class="form-label small fw-bold">Target Branch</label>
                                <select id="form_branch_id" class="form-select shadow-sm">
                                    <option value="">All Branches</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-department-div">
                                <label class="form-label small fw-bold">Target Department</label>
                                <select id="form_department_id" class="form-select shadow-sm">
                                    <option value="">All Departments</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Notice Title / Subject <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="title" class="form-control shadow-sm" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Notice Date <span class="text-danger">*</span></label>
                                <input type="date" id="notice_date" class="form-control shadow-sm" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3 pb-3 border-bottom">
                        <div class="col-md-4">
    <label class="form-label fw-bold">Audience Group <span class="text-danger">*</span></label>
    <select id="target_audience" class="form-select shadow-sm" required>
        <option value="all" selected>All / Everyone</option>
        <option value="all_except_customers">All / Everyone except Customers</option>
        <option value="all_except_management">All / Everyone except Customers, CEOs & Directors</option>
        <option value="director">Only Directors</option>
        <option value="employee">Only Employees</option>
        <option value="member">Only Associate Members</option>
        <option value="customer">Only Customers</option>
        <option value="other" class="text-danger fw-bold">Specific (Individual Person)</option>
    </select>
</div>

<div class="col-md-4 entity-div" style="display: none;">
    <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
    <select id="entity_type" class="form-select shadow-sm">
        <option value="" disabled selected>Select Category...</option>
        <option value="ceo" class="fw-bold">CEO / Super Admin</option>
        <option value="director" class="fw-bold">Director</option>
        <option value="employee">Employee</option>
        <option value="member">Associate Member</option>
        <option value="customer">Customer</option>
    </select>
</div>
                            <div class="col-md-4 entity-div" style="display: none;">
                                <label class="form-label fw-bold">Select Person <span class="text-danger">*</span></label>
                                <input type="text" id="entity_id" list="entityList" class="form-control shadow-sm"
                                    placeholder="Type Name or ID...">
                                <datalist id="entityList"></datalist>
                            </div>
                        </div>

                      <div class="mb-3 d-flex align-items-center bg-light p-3 rounded border">
    <div class="form-check form-switch fs-5">
        <input class="form-check-input cursor-pointer" type="checkbox" id="requires_reply" style="cursor: pointer;">
        <label class="form-check-label fw-bold ms-2 text-dark" for="requires_reply" style="font-size: 14px; cursor: pointer;">
            Require Acknowledgment / Reply from Receiver(s)
        </label>
    </div>
</div>

<div class="mb-3 bg-light p-3 rounded border border-warning shadow-sm">
    <div class="form-check form-switch fs-5 text-danger mb-2">
        <input class="form-check-input cursor-pointer" type="checkbox" id="is_holiday" style="cursor: pointer;">
        <label class="form-check-label fw-bold ms-2" for="is_holiday" style="font-size: 14px; cursor: pointer;">
            Is this a Holiday Notice?
        </label>
    </div>
    
    <div id="holiday_fields" class="row g-3 mt-1" style="display: none;">
        <div class="col-md-4">
            <label class="form-label fw-bold text-secondary small">Total Days</label>
            <input type="number" id="holiday_total_days" class="form-control form-control-sm shadow-sm" value="1" min="1">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold text-secondary small" id="lbl_start_date">Holiday Date</label>
            <input type="date" id="holiday_start_date" class="form-control form-control-sm shadow-sm">
        </div>
        <div class="col-md-4" id="div_end_date" style="display: none;">
            <label class="form-label fw-bold text-secondary small">To Date</label>
            <input type="date" id="holiday_end_date" class="form-control form-control-sm shadow-sm">
        </div>
    </div>
</div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Notice Content <span class="text-danger">*</span></label>
                            <textarea id="noticeContent"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0 bg-light">
                    <button type="button" class="btn btn-secondary shadow-sm px-4 fw-bold"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary shadow-sm px-4 fw-bold" id="saveNoticeBtn">Save
                        Notice</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewNoticeModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-dark text-white border-bottom-0">
                    <h5 class="modal-title fw-bold" id="viewTitle">Notice</h5>
                    <button type="button" class="btn-close btn-close-white shadow-none"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-white text-dark p-4" id="viewContent"
                    style="font-size: 15px; line-height: 1.6;"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script src="https://cdn.tiny.cloud/1/{{ env('TINYMCE_API_KEY', 'no-api-key') }}/tinymce/7/tinymce.min.js"
        referrerpolicy="origin"></script>

    <script>
    let table;
    let editorReady = false;
    let userContext = null;
    let currentMobilePageLen = 10;

    $(document).ready(function() {
        // 1. Set Default Date Range (Current Month)
        let date = new Date();
        let firstDay = new Date(date.getFullYear(), date.getMonth(), 1).toISOString().split('T')[0];
        let lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).toISOString().split('T')[0];
        $('#filter_start_date').val(firstDay);
        $('#filter_end_date').val(lastDay);

        // 2. Fetch User Context & Initialize Layout
        $.get('/api/v1/admin/auth/me', function(res) {
            let u = res.data;
            userContext = {
                is_god: ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in']
                    .includes((u.email || '').toLowerCase()),
                company_id: u.company_id,
                branch_id: u.branch_id
            };
            loadHierarchicalDropdowns();
            initTinyMCE();
            initDataTable();
        });


        // 🔥 HOLIDAY LOGIC JS 🔥
    $('#is_holiday').change(function() {
        if ($(this).is(':checked')) {
            $('#holiday_fields').slideDown();
        } else {
            $('#holiday_fields').slideUp();
        }
    });

    $('#holiday_total_days').on('input', function() {
        let days = parseInt($(this).val()) || 1;
        if (days > 1) {
            $('#lbl_start_date').text('From Date');
            $('#div_end_date').slideDown();
        } else {
            $('#lbl_start_date').text('Holiday Date');
            $('#div_end_date').slideUp();
        }
    });

    // Reset Notice Modal (Update your existing openNoticeModal function)
    let oldOpenNoticeModal = window.openNoticeModal;
    window.openNoticeModal = function(mode) {
        oldOpenNoticeModal(mode); // Purana function call karo
        $('#is_holiday').prop('checked', false).trigger('change');
        $('#holiday_total_days').val(1).trigger('input');
        $('#holiday_start_date, #holiday_end_date').val('');
    };


    });

    // 🔥 TINYMCE INITIALIZATION
    function initTinyMCE() {
        tinymce.init({
            selector: '#noticeContent',
            height: 400,
            menubar: false,
            plugins: ['advlist', 'autolink', 'lists', 'link', 'table', 'wordcount'],
            toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | table',
            setup: function(editor) {
                editor.on('init', function() {
                    editorReady = true;
                });
            }
        });
    }

    // 🔥 HIERARCHICAL CASCADING DROPDOWNS MANAGEMENT
    function loadHierarchicalDropdowns() {
        $.get('/api/v1/companies', function(res) {
            let html = '<option value="">All Companies</option>';
            res.data.forEach(c => {
                html += `<option value="${c.id}">${c.company_name}</option>`;
            });
            $('#filter_company, #form_company_id').html(html);

            if (!userContext.is_god && userContext.company_id) {
                $('#filter_company, #form_company_id').val(userContext.company_id).prop('disabled', true);
                loadBranches(userContext.company_id);
            }
        });

        $('#filter_company, #form_company_id').change(function() {
            loadBranches($(this).val(), $(this).attr('id') === 'form_company_id' ? 'form' : 'filter');
        });

        $('#filter_branch, #form_branch_id').change(function() {
            loadDepartments($(this).val(), $(this).attr('id') === 'form_branch_id' ? 'form' : 'filter');
        });
    }

   function loadBranches(companyId, target = 'both') {
        if (!companyId) {
            if (target === 'filter' || target === 'both') $('#filter_branch').html('<option value="">All Branches</option>');
            if (target === 'form' || target === 'both') $('#form_branch_id').html('<option value="">All Branches</option>');
            loadDepartments('', target); // Call to reset departments
            return;
        }
        $.post('/api/v1/get-branches-by-companies', {
            company_ids: [companyId]
        }, function(res) {
            let html = '<option value="">All Branches</option>';
            html += '<option value="HO" class="fw-bold text-primary">Head Office (Main Branch)</option>';
            res.data.forEach(b => {
                html += `<option value="${b.id}">${b.branch_name}</option>`;
            });

            if (target === 'filter' || target === 'both') $('#filter_branch').html(html);
            if (target === 'form' || target === 'both') {
                $('#form_branch_id').html(html);
                if (!userContext.is_god && userContext.branch_id) {
                    $('#filter_branch, #form_branch_id').val(userContext.branch_id).prop('disabled', true);
                    loadDepartments(userContext.branch_id);
                } else if (!userContext.is_god && !userContext.branch_id) {
                    $('#filter_branch, #form_branch_id').val('HO').prop('disabled', true); // User is from Head Office
                    loadDepartments('HO');
                } else {
                    loadDepartments(''); // Load all depts by default
                }
            }
        });
    }

  // 🔥 DYNAMIC DEPARTMENT LOADING
    function loadDepartments(branchId, target = 'both') {
        $.get('/api/v1/departments', function(res) {
            let html = '<option value="">All Departments</option>';
            res.data.forEach(d => {
                if (branchId === 'HO') {
                    if (!d.branch_id) html += `<option value="${d.id}">${d.department_name}</option>`;
                } else if (branchId && branchId !== '') {
                    if (d.branch_id == branchId) html += `<option value="${d.id}">${d.department_name}</option>`;
                } else {
                    html += `<option value="${d.id}">${d.department_name} ${d.branch ? '('+d.branch.branch_name+')' : '(HO)'}</option>`;
                }
            });
            if (target === 'filter' || target === 'both') $('#filter_department').html(html);
            if (target === 'form' || target === 'both') $('#form_department_id').html(html);
        });
    }

    // 🔥 DATATABLE INITIALIZATION AND CONFIGURATION
    function initDataTable() {
        let isMobile = $(window).width() < 768;
        table = $('#noticesTable').DataTable({
            processing: true,
            serverSide: false,
            responsive: false,
            pageLength: 10,
            dom: "<'row mb-3'<'col-sm-12 col-md-6 d-flex align-items-center'B><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            buttons: [{
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel"></i> Export Excel',
                className: 'btn btn-success btn-sm secured-item',
                attr: { 'data-permission': 'notices_export' },
                exportOptions: { columns: [1, 2, 3, 4, 5] }
            }],
            ajax: {
                url: '/api/v1/notices',
                data: function(d) {
                    d.start_date = $('#filter_start_date').val();
                    d.end_date = $('#filter_end_date').val();
                    d.company_id = $('#filter_company').val();
                    d.branch_id = $('#filter_branch').val();
                    d.department_id = $('#filter_department').val();
                },
                dataSrc: 'data'
            },
            columns: [
                {
                    data: 'id',
                    orderable: false,
                    className: 'text-center',
                    render: data => `<input type="checkbox" class="row-checkbox form-check-input" value="${data}">`
                },
                { data: 'notice_date', render: data => `<span class="fw-bold text-dark">${data}</span>` },
                { data: 'title', width: '25%' },
                {
                    data: 'target_audience',
                    render: function(data, type, row) {
                        let audienceBadge = data === 'other' ?
                            `<span class="badge bg-secondary mb-2">Specific: ${row.entity_id}</span>` :
                            `<span class="badge bg-info text-dark text-uppercase mb-2">ALL ${data}S</span>`;

                        let compName = row.target_company ? row.target_company.company_name : '<span class="text-muted">All Companies</span>';
                        let branchName = row.target_branch ? row.target_branch.branch_name : '<span class="text-muted">All Branches</span>';
                        let deptName = row.target_department ? row.target_department.department_name : '<span class="text-muted">All Depts</span>';

                        let hierarchyInfo = `
                            <div style="font-size: 11px; color: #475569; text-align: left; background: #f8fafc; padding: 5px; border-radius: 4px; border: 1px solid #e2e8f0; display: inline-block;">
                                <div class="mb-1"><i class="fas fa-building text-primary w-15px"></i> <b>C:</b> ${compName}</div>
                                <div class="mb-1"><i class="fas fa-code-branch text-success w-15px"></i> <b>B:</b> ${branchName}</div>
                                <div><i class="fas fa-users text-warning w-15px"></i> <b>D:</b> ${deptName}</div>
                            </div>
                        `;
                        return audienceBadge + '<br>' + hierarchyInfo;
                    }
                },
                {
                    data: 'requires_reply',
                    render: data => data == 1 ?
                        '<span class="text-success fw-bold"><i class="fas fa-check"></i> Yes</span>' :
                        '<span class="text-danger"><i class="fas fa-times"></i> No</span>'
                },
                {
                    data: 'status',
                    render: function(data) {
                        if (data === 'active') return '<span class="badge bg-success">Active</span>';
                        if (data === 'pending') return '<span class="badge bg-warning text-dark">Pending</span>';
                        return '<span class="badge bg-danger">Inactive</span>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: function(data, type, row) {
                        let btns = `<div class="d-flex justify-content-center gap-1">`;
                        btns += `<button class="btn btn-sm btn-dark text-white secured-item" data-permission="notices_view" onclick="viewNotice(${row.id})" title="View Notice"><i class="fas fa-eye"></i></button>`;

                        // 🔥 DYNAMIC PRINT PARAMETERS TRANSFERS
                        let compId = row.target_company_id ? row.target_company_id : 'all';
                        let branchId = row.target_branch_id ? row.target_branch_id : 'all';
                        btns += `<button class="btn btn-sm btn-secondary text-white secured-item" data-permission="notices_print" onclick="printNotice(${row.id}, '${compId}', '${branchId}')" title="Print"><i class="fas fa-print"></i></button>`;

                        if (row.status === 'pending') {
                            btns += `<button class="btn btn-sm btn-success text-white secured-item" data-permission="notices_appr" onclick="approveNotice(${row.id})" title="Approve"><i class="fas fa-check-circle"></i></button>`;
                            btns += `<button class="btn btn-sm btn-danger text-white secured-item" data-permission="notices_rej" onclick="rejectNotice(${row.id})" title="Reject"><i class="fas fa-times-circle"></i></button>`;
                        }

                        btns += `<button class="btn btn-sm btn-warning secured-item" data-permission="notices_edit" onclick="editNotice(${row.id})" title="Edit"><i class="fas fa-edit"></i></button>`;
                        btns += `</div>`;
                        return btns;
                    }
                }
            ],
          drawCallback: function(settings) {
                // 🔥 FIX: Use this.api() instead of the global 'table' variable here
                let api = this.api(); 

                // Responsive Matrix Setup
                $('#noticesTable tbody tr').each(function() {
                    $(this).find('td').eq(0).attr('data-label', 'Select');
                    $(this).find('td').eq(1).attr('data-label', 'Date');
                    $(this).find('td').eq(2).attr('data-label', 'Title');
                    $(this).find('td').eq(3).attr('data-label', 'Target');
                    $(this).find('td').eq(4).attr('data-label', 'Reply');
                    $(this).find('td').eq(5).attr('data-label', 'Status');
                    $(this).find('td').eq(6).attr('data-label', 'Actions');
                });

                // Mobile Bulk Layout Interceptor
                if ($(window).width() < 768 && api.rows().count() > 0) { // 🔥 FIX APPLIED HERE
                    $('#mobileBulkActionBar').attr('style', 'display: flex !important;');
                    let info = api.page.info(); // 🔥 FIX APPLIED HERE
                    if (info.length < info.recordsDisplay) {
                        $('#mobileLoadMoreContainer').show();
                    } else {
                        $('#mobileLoadMoreContainer').hide();
                    }
                } else {
                    $('#mobileBulkActionBar').attr('style', 'display: none !important;');
                    $('#mobileLoadMoreContainer').hide();
                }

                $('#selectAllCheckbox, #mobileSelectAllCheckbox').prop('checked', false);
                toggleBulkDeleteBtn();

                if (typeof window.applyPermissions === 'function') window.applyPermissions();
            }
        });
    }

    // 🔥 MOBILE PAGINATION: LOAD MORE EVENT
    $('#mobileLoadMoreBtn').click(function() {
        currentMobilePageLen += 10;
        table.page.len(currentMobilePageLen).draw(false);
    });

    function loadTableData() {
        currentMobilePageLen = 10; // Reset mobile length sequence
        if ($(window).width() < 768) table.page.len(currentMobilePageLen);
        table.ajax.reload(null, false);
    }

    // 🔥 SELECTION & SYNC LOGIC (DESKTOP + MOBILE)
    $('#selectAllCheckbox').change(function() {
        let isChecked = $(this).prop('checked');
        $('.row-checkbox').prop('checked', isChecked);
        $('#mobileSelectAllCheckbox').prop('checked', isChecked);
        toggleBulkDeleteBtn();
    });

    $('#mobileSelectAllCheckbox').change(function() {
        let isChecked = $(this).prop('checked');
        $('.row-checkbox').prop('checked', isChecked);
        $('#selectAllCheckbox').prop('checked', isChecked);
        toggleBulkDeleteBtn();
    });

    $(document).on('change', '.row-checkbox', function() {
        let totalCheckboxes = $('.row-checkbox').length;
        let checkedCount = $('.row-checkbox:checked').length;
        let allChecked = (totalCheckboxes > 0 && totalCheckboxes === checkedCount);

        $('#selectAllCheckbox').prop('checked', allChecked);
        $('#mobileSelectAllCheckbox').prop('checked', allChecked);
        toggleBulkDeleteBtn();
    });

    function toggleBulkDeleteBtn() {
        let checkedCount = $('.row-checkbox:checked').length;
        $('#mobileSelectedCount').text(checkedCount);

        if (checkedCount > 0) {
            $('#bulkDeleteBtn, #mobileBulkDeleteBtn').fadeIn();
        } else {
            $('#bulkDeleteBtn, #mobileBulkDeleteBtn').fadeOut();
        }
    }

    // 🔥 BULK PROCESSING DELETE REQUEST
    $('#bulkDeleteBtn, #mobileBulkDeleteBtn').click(function() {
        let ids = [];
        $('.row-checkbox:checked').each(function() {
            ids.push($(this).val());
        });
        if (ids.length === 0) return;

        Swal.fire({
            title: 'Delete Selected?',
            text: `You are about to delete ${ids.length} notice(s). This cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6e7881',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                let deletePromises = ids.map(id => $.ajax({
                    url: `/api/v1/notices/${id}`,
                    type: 'DELETE'
                }));
                Promise.all(deletePromises).then(() => {
                    Swal.fire('Deleted!', 'Selected notices deleted successfully.', 'success');
                    loadTableData();
                    $('#bulkDeleteBtn, #mobileBulkDeleteBtn').hide();
                });
            }
        });
    });

    // 🔥 INTERACTIVE MODAL ACTIONS
    window.openNoticeModal = function(mode) {
        $('#noticeForm')[0].reset();
        $('#notice_id').val('');
        $('.entity-div').hide();
        if (editorReady) tinymce.get('noticeContent').setContent('');
        $('#notice_date').val(new Date().toISOString().split('T')[0]);
        $('#modalTitle').html('<i class="fas fa-bullhorn text-primary me-2"></i> ' + (mode === 'direct' ? 'Publish Notice' : 'Request Notice'));
        $('#saveNoticeBtn').text(mode === 'direct' ? 'Publish Now' : 'Submit Request');
        $('#noticeModal').modal('show');
    }

    window.editNotice = function(id) {
        $.get(`/api/v1/notices/${id}`, function(res) {
            let data = res.data;
            $('#notice_id').val(data.id);
            $('#title').val(data.title);
            $('#notice_date').val(data.notice_date);

            // window.editNotice function ke andar jahan data map ho raha hai wahan add karein:
if (data.holiday) {
    $('#is_holiday').prop('checked', true).trigger('change');
    $('#holiday_total_days').val(data.holiday.total_days).trigger('input');
    $('#holiday_start_date').val(data.holiday.start_date);
    if(data.holiday.total_days > 1) $('#holiday_end_date').val(data.holiday.end_date);
} else {
    $('#is_holiday').prop('checked', false).trigger('change');
}
            
            $('#form_company_id').val(data.target_company_id).trigger('change');
            setTimeout(() => { $('#form_branch_id').val(data.target_branch_id).trigger('change'); }, 500);
            setTimeout(() => { $('#form_department_id').val(data.target_department_id); }, 1000);

            $('#target_audience').val(data.target_audience).trigger('change');
            if (data.target_audience === 'other') {
                $('#entity_type').val(data.entity_type).trigger('change');
                setTimeout(() => {
                    loadAudienceEntities();
                    $('#entity_id').val(data.entity_id);
                }, 500);
            }

            $('#requires_reply').prop('checked', data.requires_reply == 1);
            if (editorReady) tinymce.get('noticeContent').setContent(data.content);

            $('#modalTitle').html('<i class="fas fa-edit text-warning me-2"></i> Edit Notice');
            $('#saveNoticeBtn').text('Update Notice');
            $('#noticeModal').modal('show');
        });
    }

    $('#saveNoticeBtn').click(function() {
        let btn = $(this);
        let id = $('#notice_id').val();
        
        // 🔥 SMART FIX: Seedha Exact ID uthaya
        let exactEntityId = $('#entity_id').val(); 

        let payload = {
            title: $('#title').val(),
            notice_date: $('#notice_date').val(),
            target_audience: $('#target_audience').val(),
            target_company_id: $('#form_company_id').val(),
            target_branch_id: $('#form_branch_id').val(),
            target_department_id: $('#form_department_id').val(),
            entity_type: $('#entity_type').val(),
            entity_id: exactEntityId, // Directly bhej diya!
            requires_reply: $('#requires_reply').is(':checked') ? 1 : 0,
            content: tinymce.get('noticeContent').getContent(),
            is_holiday: $('#is_holiday').is(':checked') ? 1 : 0,
            holiday_total_days: $('#holiday_total_days').val(),
            holiday_start_date: $('#holiday_start_date').val(),
            holiday_end_date: $('#holiday_end_date').val(),
        };

        btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        $.ajax({
            url: id ? `/api/v1/notices/${id}` : `/api/v1/notices`,
            type: id ? 'PUT' : 'POST',
            data: payload,
            success: function(res) {
                $('#noticeModal').modal('hide');
                Swal.fire('Success', res.message, 'success');
                loadTableData();
                btn.html('Save Notice').prop('disabled', false);
            },
            error: function(err) {
                Swal.fire('Error', err.responseJSON?.message || 'Error occurred', 'error');
                btn.html('Save Notice').prop('disabled', false);
            }
        });
    });
   // 🔥 TARGET AUDIENCE CHANGEOVER EVENT HANDLERS
    $('#target_audience').change(function() {
        if ($(this).val() === 'other') {
            $('.entity-div').fadeIn();
            $('#entity_type, #entity_id').prop('required', true);
            loadAudienceEntities();
        } else {
            $('.entity-div').fadeOut();
            $('#entity_type, #entity_id').val('').prop('required', false);
            $('#entityList').html('');
        }
    });

    // 🔥 SPECIFIC CATEGORY ONCHANGE (WITH DIRECTOR VALIDATION) 🔥
    $('#entity_type').change(function() {
        if ($(this).val() === 'director') {
            let selectedCompany = $('#form_company_id').val();
            if (!selectedCompany) {
                Swal.fire({
                    title: 'Warning!',
                    text: 'Please select a Target Company first before fetching Directors.',
                    icon: 'warning'
                });
                $(this).val(''); // Reset Category Dropdown
                $('#entityList').html('');
                return;
            }
        }
        loadAudienceEntities();
    });

    $('#form_company_id, #form_branch_id, #form_department_id').change(function() {
        if ($('#target_audience').val() === 'other') loadAudienceEntities();
    });

    // 🔥 DYNAMIC PERSONNEL CONTEXT FETCHING
    function loadAudienceEntities() {
        let type = $('#entity_type').val();
        if (!type) return;

        let companyId = $('#form_company_id').val();
        let rawBranchId = $('#form_branch_id').val();
        let departmentId = $('#form_department_id').val();

        // 🔥 SMART FIX: API me query block na ho isliye HO ke case me branch khali bhejenge
        let apiBranchId = (rawBranchId === 'HO') ? '' : rawBranchId;

        let url = '';
        if (type === 'ceo') url = '/api/v1/super-admins';
        if (type === 'director') url = '/api/v1/directors/active';
        if (type === 'employee') url = '/api/v1/employees';
        if (type === 'member') url = '/api/v1/members';
        if (type === 'customer') url = '/api/v1/customers';

        $('#entityList').html('<option value="">Loading items...</option>');

        $.get(url, {
            company_id: companyId,
            branch_id: apiBranchId, // HO hone par khali jayega
            department_id: departmentId,
            status: 'active',
            emp_status: 'active' // Safey ke liye dono fields bhej diye hain
        }, function(res) {
            let html = '';
            let items = res.data || res;
            let count = 0;

            if (Array.isArray(items) && items.length > 0) {
                items.forEach(item => {
                    if (rawBranchId === 'HO') {
                        if (item.branch_id != null && item.branch_id != 0 && item.branch_id !== '') return;
                    }

                    let name = item.name || item.full_name || item.employee_name || item.member_name || item.customer_name || 'No Name';
                    let displayId = item.employee_smart_id || item.member_id || item.customer_id || item.id;
                    
                    if (name && displayId) {
                        // 🔥 SMART FIX: Ab hum exact string ID bhej rahe hain (e.g., EMP-001)
                        html += `<option value="${displayId}">${name} (${displayId})</option>`;
                        count++;
                    }
                });
            }
            
            if (count === 0) {
                html = '<option value="" disabled>No records found for this location</option>';
            }
            
            $('#entityList').html(html);
        }).fail(function() {
            $('#entityList').html('<option value="" disabled>Error loading records</option>');
        });
    }
    // 🔥 SINGLE ITEM VIEW AND OVERLAYS
    window.viewNotice = function(id) {
        $.get(`/api/v1/notices/${id}`, function(res) {
            $('#viewTitle').text(res.data.title);
            $('#viewContent').html(res.data.content);
            $('#viewNoticeModal').modal('show');
        });
    }

    // 🔥 ROUTING PARAMETERS LINKING TO PRINT ENGINE
    window.printNotice = function(id, companyId, branchId) {
        let url = `/admin/notices/print/${id}?company_id=${companyId}&branch_id=${branchId}`;
        window.open(url, '_blank', 'width=900,height=800');
    }

    // 🔥 WORKFLOW STATE CONTROL INTERFACES (APPROVE / REJECT)
    window.approveNotice = function(id) {
        Swal.fire({
            title: 'Approve Notice?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`/api/v1/notices/${id}/approve`, function(res) {
                    Swal.fire('Approved', res.message, 'success');
                    loadTableData();
                });
            }
        });
    }

    window.rejectNotice = function(id) {
        Swal.fire({
            title: 'Reject Notice?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, Reject'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`/api/v1/notices/${id}/reject`, function(res) {
                    Swal.fire('Rejected', res.message, 'success');
                    loadTableData();
                });
            }
        });
    }
</script>
@endpush