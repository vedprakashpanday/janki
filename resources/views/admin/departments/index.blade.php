@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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

        .status-active {
            background: #dcfce7;
            color: #166534;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
        }

        .status-pending {
            background: #fef3c7;
            color: #9a3412;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
        }

        .select2-container .select2-selection--multiple {
            border: 1px solid #0d6efd;
            min-height: 38px;
            border-radius: 0.375rem;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: var(--sidebar-bg);
            color: white;
            border: none;
            padding: 2px 8px;
            font-size: 12px;
        }

        /* 🔥 NAYA: Mobile Card Styles 🔥 */
        .mobile-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s, box-shadow 0.2s;
            margin-bottom: 15px;
            padding: 15px;
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color:var(--sidebar-bg);"><i
                        class="fas fa-sitemap text-primary me-2"></i>Department Master</h4>
                <p class="text-secondary small d-none d-md-block mb-0">Manage Departments and their hierarchical
                    Designations</p>
            </div>
            <button class="btn text-white px-3 py-2 shadow-sm" id="addDepartmentBtn"
                style="background-color:var(--brand-primary); display:none;" onclick="openAddModal()">
                <i class="fas fa-plus me-1"></i> <span class="d-none d-md-inline">Add Department</span>
            </button>
        </div>

        <div class="card border-0 shadow-sm mb-3" id="globalFilterCard">
            <div class="card-body p-3 d-flex align-items-center gap-3 flex-wrap">
                <span class="fw-bold text-secondary"><i class="fas fa-filter text-primary me-1"></i> Filter:</span>
                <div class="input-group" style="max-width:300px;">
                    <span class="input-group-text bg-white"><i class="fas fa-industry text-primary"></i></span>
                    <select class="form-select fw-medium text-secondary" id="filter_company">
                        <option value="">-- All Companies --</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="d-block d-md-none mb-3">
            <div class="input-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control border-start-0" id="mobileSearch"
                    placeholder="Search departments...">
            </div>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="deptTable" class="table table-hover table-custom w-100">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Department Name</th>
                                <th>Assigned To (Company)</th>
                                <th>Total Designations</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="mobileCardsContainer" class="d-block d-md-none mb-4">
            <div class="text-center text-muted my-4" id="cardsLoader">
                <i class="fas fa-spinner fa-spin fs-2 mb-2"></i><br>Loading Departments...
            </div>
        </div>

    </div>

    <div class="modal fade" id="deptModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" style="color:var(--sidebar-bg);" id="modalTitle">
                        <i class="fas fa-sitemap me-2 text-primary"></i> Add Department
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="deptForm">
                        <input type="hidden" id="edit_id">
                        <input type="hidden" id="form_method" value="POST">

                        <div class="row g-3 mb-4 border-bottom pb-4">
                            <div class="col-md-6" id="modalCompanyContainer">
                                <label class="form-label small fw-bold text-secondary">Assign to Company(s) <span
                                        class="text-muted fw-normal">(Khali = All)</span></label>
                                <select class="form-control" name="company_ids[]" id="m_company_ids" multiple
                                    style="width:100%;"></select>
                            </div>
                            <div class="col-md-6" id="modalBranchContainer">
                                <label class="form-label small fw-bold text-secondary">Assign to Branch(es) <span
                                        class="text-muted fw-normal">(Khali = All)</span></label>
                                <select class="form-control" name="branch_ids[]" id="m_branch_ids" multiple
                                    style="width:100%;"></select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-bold text-secondary">Department Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control border-primary" id="m_dept_name"
                                    name="department_name" placeholder="e.g. Information Technology" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-secondary">Status</label>
                                <select class="form-select" id="m_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-primary mb-0"><i class="fas fa-user-tag me-1"></i> Include
                                Designations
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addDesignationRow()">
                                <i class="fas fa-plus"></i> Add Row
                            </button>
                        </div>
                        <div class="bg-light p-3 rounded border">
                            <div id="designationRowsContainer"></div>
                        </div>

                        <button type="submit" class="btn text-white w-100 py-2 fw-medium mt-4 shadow-sm"
                            style="background-color:var(--sidebar-bg);" id="saveBtn">Save Master Record</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        let table;
        let currentPortal = window.location.pathname.split('/')[1] || 'admin';
        let currentUserData = null; // Hold User Context
        let globalCompanyOptions = '<option value="all">-- Apply to All (Global) --</option>';

        function loadCompanies() {
            $.ajax({
                url: '/api/v1/get-active-companies',
                type: 'GET',
                success: function(res) {
                    let filterOpts = '<option value="">-- All Companies --</option>';
                    globalCompanyOptions = '<option value="all">-- Apply to All (Global) --</option>';
                    if (res.data) {
                        res.data.forEach(c => {
                            let text = `${c.company_name} (${c.company_code || c.id})`;
                            filterOpts += `<option value="${c.id}">${text}</option>`;
                            globalCompanyOptions += `<option value="${c.id}">${text}</option>`;
                        });
                    }
                    $('#filter_company').html(filterOpts);
                    $('#m_company_ids').html(globalCompanyOptions);
                }
            });
        }

        function fetchAndLoadBranches(selectedCompanies, preSelectedBranches = []) {
            let payload = selectedCompanies;
            if (!payload || payload.length === 0 || payload.includes('all')) payload = ['all'];

            $.ajax({
                url: '/api/v1/get-branches-by-companies',
                type: 'POST',
                data: {
                    company_ids: payload
                },
                success: function(res) {
                    let options = '<option value="all">-- Apply to All (Global) --</option>';
                    if (res.data) {
                        res.data.forEach(b => {
                            options +=
                                `<option value="${b.id}">${b.branch_name} (${b.branch_id || b.id})</option>`;
                        });
                    }
                    $('#m_branch_ids').html(options);
                    if (preSelectedBranches.length > 0) $('#m_branch_ids').val(preSelectedBranches).trigger(
                        'change.select2');
                }
            });
        }

        function applySmartRBACUI() {
            let perms = window.userPerms || [];
            let isGod = window.userGodMode || false;
            let isDirector = currentUserData?.designation_name?.toLowerCase().includes('director');

            let hasDirect = isGod || isDirector || perms.includes('department_add_direct');
            let hasRequest = perms.includes('department_add_request');

            let btn = $('#addDepartmentBtn');
            if (hasDirect) {
                btn.html('<i class="fas fa-plus me-1"></i> <span class="d-none d-md-inline">Add Department</span>').show();
            } else if (hasRequest) {
                btn.html(
                    '<i class="fas fa-paper-plane me-1"></i> <span class="d-none d-md-inline">Request Department</span>'
                    ).show();
            } else {
                btn.hide();
            }
        }

        window.openAddModal = function() {
            $('#deptForm')[0].reset();
            $('#edit_id').val('');
            $('#form_method').val('POST');
            $('#designationRowsContainer').empty();
            addDesignationRow();

            let perms = window.userPerms || [];
            let isGod = window.userGodMode || false;
            let isDirector = currentUserData?.designation_name?.toLowerCase().includes('director');
            let hasDirect = isGod || isDirector || perms.includes('department_add_direct');

            if (isGod) {
                $('#m_company_ids').html(globalCompanyOptions).prop('disabled', false).val(null).trigger(
                    'change.select2');
                $('#m_branch_ids').prop('disabled', false).html(
                    '<option value="all">-- Apply to All (Global) --</option>').val(null).trigger('change.select2');
            } else if (currentUserData) {
                let compHtml =
                    `<option value="${currentUserData.company_id}" selected>${currentUserData.company_name || 'My Company'}</option>`;
                $('#m_company_ids').html(compHtml).prop('disabled', true);

                if (isDirector) {
                    $('#m_branch_ids').prop('disabled', false);
                    fetchAndLoadBranches([String(currentUserData.company_id)]);
                } else {
                    let branchName = currentUserData.branch_name || `Branch #${currentUserData.branch_id}`;
                    $('#m_branch_ids').html(
                        `<option value="${currentUserData.branch_id}" selected>${branchName}</option>`).prop(
                        'disabled', true);
                }
            }

            if (!hasDirect) {
                $('#m_status').html('<option value="pending" selected>Pending</option>').prop('disabled', true);
                $('#modalTitle').html('<i class="fas fa-paper-plane me-2 text-warning"></i> Request Department');
            } else {
                $('#m_status').html('<option value="active">Active</option><option value="inactive">Inactive</option>')
                    .prop('disabled', false);
                $('#modalTitle').html('<i class="fas fa-sitemap me-2 text-primary"></i> Add Department');
            }

            $('#deptModal').modal('show');
        };

        window.addDesignationRow = function(id = '', name = '', code = '') {
            let rowHtml = `
                <div class="row g-2 mb-2 desig-row align-items-center">
                    <input type="hidden" class="d-id" value="${id}">
                    <div class="col-12 col-md-6 mb-2 mb-md-0"><input type="text" class="form-control d-name" placeholder="e.g. Manager" value="${name}" required></div>
                    <div class="col-10 col-md-5"><input type="text" class="form-control text-uppercase d-code" placeholder="e.g. MGR" value="${code}" required></div>
                    <div class="col-2 col-md-1 text-end"><button type="button" class="btn btn-danger btn-sm w-100" onclick="$(this).closest('.desig-row').remove()"><i class="fas fa-trash"></i></button></div>
                </div>`;
            $('#designationRowsContainer').append(rowHtml);
        };

        $(document).ready(function() {
            loadCompanies();

            $.ajax({
                url: `/api/v1/${currentPortal}/auth/me`,
                type: 'GET',
                success: function(res) {
                    currentUserData = res.data;
                    applySmartRBACUI();
                }
            });

            $('#m_company_ids').select2({
                dropdownParent: $('#deptModal'),
                placeholder: '-- Select Companies --',
                allowClear: true
            });
            $('#m_branch_ids').select2({
                dropdownParent: $('#deptModal'),
                placeholder: '-- Select Branches --',
                allowClear: true
            });

            $('#m_company_ids').on('change', function() {
                let selectedComps = $(this).val();
                fetchAndLoadBranches(selectedComps);
            });

            // 🔥 NAYA: Mobile Card Logic 🔥
            function renderMobileCards(dataset) {
                $('#cardsLoader').hide();
                let gridHtml = '';

                if (!dataset || dataset.length === 0) {
                    gridHtml =
                        '<div class="text-center p-4 bg-white rounded shadow-sm text-muted small">No departments found.</div>';
                    $('#mobileCardsContainer').html(gridHtml);
                    return;
                }

                dataset.forEach(item => {
                    let stBadge = '';
                    if (item.status === 'pending') stBadge = '<span class="status-pending">Pending</span>';
                    else if (item.status === 'active') stBadge =
                    '<span class="status-active">Active</span>';
                    else stBadge = '<span class="status-inactive">Inactive</span>';

                    let cName = item.company_name || 'Master HO';

                    gridHtml += `
                    <div class="mobile-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold text-primary mb-1"><i class="fas fa-sitemap me-1 text-muted"></i> ${item.department_name}</h6>
                                <div class="small text-secondary"><i class="fas fa-building me-1"></i>${cName}</div>
                            </div>
                            <div class="text-end text-nowrap">
                                <div class="small text-muted fw-bold mb-1">ID: ${item.id}</div>
                                ${stBadge}
                            </div>
                        </div>
                        <div class="mt-2 mb-3">
                            <span class="badge bg-secondary"><i class="fas fa-user-tag me-1"></i>${item.designation_count || 0} Designations</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top gap-2 mt-3">
                            <button class="btn btn-sm btn-light border text-primary flex-fill fw-medium edit-btn secured-item" data-permission="department_edit" data-id="${item.id}"><i class="fas fa-edit me-1"></i> Edit</button>
                            <button class="btn btn-sm btn-light border text-danger flex-fill fw-medium delete-btn secured-item" data-permission="department_delete" data-id="${item.id}"><i class="fas fa-trash-alt me-1"></i> Delete</button>
                        </div>
                    </div>`;
                });

                $('#mobileCardsContainer').html(gridHtml);
            }

            table = $('#deptTable').DataTable({
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/departments',
                    type: 'GET',
                    data: function(d) {
                        d.company_id = $('#filter_company').val();
                    }
                },
                columns: [{
                        data: 'id',
                        className: 'text-center fw-bold'
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
                        render: s => {
                            if (s === 'pending')
                            return `<span class="status-pending">Pending</span>`;
                            return s === 'active' ? `<span class="status-active">Active</span>` :
                                `<span class="status-inactive">Inactive</span>`;
                        }
                    },
                    {
                        data: 'id',
                        className: 'text-center text-nowrap',
                        orderable: false,
                        render: d => `
                        <div class="d-flex justify-content-center gap-1">
                            <button class="btn btn-sm btn-light border text-primary shadow-sm edit-btn secured-item" data-permission="department_edit" data-id="${d}"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-light border text-danger shadow-sm delete-btn secured-item" data-permission="department_delete" data-id="${d}"><i class="fas fa-trash"></i></button>
                        </div>`
                    }
                ],
                // 🔥 FIX: drawCallback me ab Mobile Cards render honge 🔥
                drawCallback: function(settings) {
                    if (settings.json && settings.json.data) {
                        renderMobileCards(settings.json.data);
                    } else {
                        renderMobileCards(this.api().data().toArray());
                    }

                    if (typeof window.applyPermissions === 'function') window.applyPermissions();
                }
            });

            // Mobile Search Binding
            $('#mobileSearch').on('keyup', function() {
                table.search(this.value).draw();
            });

            $('#filter_company').change(function() {
                table.ajax.reload();
            });

            $('#deptForm').submit(function(e) {
                e.preventDefault();
                let designationsList = [];
                $('.desig-row').each(function() {
                    let dName = $(this).find('.d-name').val().trim(),
                        dCode = $(this).find('.d-code').val().trim(),
                        dId = $(this).find('.d-id').val();
                    if (dName && dCode) designationsList.push({
                        id: dId,
                        name: dName,
                        code: dCode
                    });
                });

                let formData = $(this).serializeArray();
                formData.push({
                    name: 'designations',
                    value: JSON.stringify(designationsList)
                });

                let id = $('#edit_id').val(),
                    method = $('#form_method').val();
                let url = method === 'PUT' ? `/api/v1/departments/${id}` : '/api/v1/departments';
                if (method === 'PUT') formData.push({
                    name: '_method',
                    value: 'PUT'
                });

                let btn = $('#saveBtn');
                btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...').prop('disabled', true);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    success: function(res) {
                        $('#deptModal').modal('hide');
                        Swal.fire('Success', res.message, 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function(err) {
                        Swal.fire('Error', err.responseJSON?.message || 'Error occurred',
                            'error');
                    },
                    complete: function() {
                        btn.html('Save Master Record').prop('disabled', false);
                    }
                });
            });

            $(document).on('click', '.edit-btn', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: `/api/v1/departments/${id}`,
                    type: 'GET',
                    success: function(res) {
                        let d = res.data;
                        $('#edit_id').val(d.id);
                        $('#form_method').val('PUT');
                        $('#m_dept_name').val(d.department_name);

                        let perms = window.userPerms || [];
                        let isGod = window.userGodMode || false;
                        let isDirector = currentUserData?.designation_name?.toLowerCase()
                            .includes('director');
                        let hasDirect = isGod || isDirector || perms.includes(
                            'department_add_direct');

                        if (!hasDirect) {
                            $('#m_status').html(
                                '<option value="pending" selected>Pending</option>').prop(
                                'disabled', true);
                        } else {
                            $('#m_status').html(
                                '<option value="active">Active</option><option value="inactive">Inactive</option><option value="pending">Pending</option>'
                                ).val(d.status).prop('disabled', false);
                        }

                        let cIds = (d.company_ids && d.company_ids.length > 0) ? d.company_ids
                            .map(String) : [];
                        let bIds = (d.branch_ids && d.branch_ids.length > 0) ? d.branch_ids.map(
                            String) : [];

                        if (isGod) {
                            $('#m_company_ids').html(globalCompanyOptions).prop('disabled',
                                false).val(cIds).trigger('change.select2');
                            $('#m_branch_ids').prop('disabled', false);
                            fetchAndLoadBranches(cIds, bIds);
                        } else {
                            let compHtml =
                                `<option value="${currentUserData.company_id}" selected>${currentUserData.company_name || 'My Company'}</option>`;
                            $('#m_company_ids').html(compHtml).prop('disabled', true);

                            if (isDirector) {
                                $('#m_branch_ids').prop('disabled', false);
                                fetchAndLoadBranches([String(currentUserData.company_id)],
                                bIds);
                            } else {
                                let branchName = currentUserData.branch_name ||
                                    `Branch #${currentUserData.branch_id}`;
                                $('#m_branch_ids').html(
                                    `<option value="${currentUserData.branch_id}" selected>${branchName}</option>`
                                    ).prop('disabled', true);
                            }
                        }

                        $('#designationRowsContainer').empty();
                        if (d.designations && d.designations.length > 0) {
                            d.designations.forEach(desig => {
                                addDesignationRow(desig.id, desig.designation_name,
                                    desig.designation_code);
                            });
                        } else {
                            addDesignationRow();
                        }

                        $('#modalTitle').html(
                            '<i class="fas fa-edit me-2 text-primary"></i> Edit Department');
                        $('#deptModal').modal('show');
                    }
                });
            });

            $(document).on('click', '.delete-btn', function() {
                let id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Department?',
                    text: 'This will delete ALL associated Designations!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/api/v1/departments/${id}`,
                            type: 'DELETE',
                            success: function(res) {
                                Swal.fire('Deleted!', res.message, 'success');
                                table.ajax.reload(null, false);
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
