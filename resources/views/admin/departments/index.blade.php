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

        .select2-container .select2-selection--multiple {
            border: 1px solid #0d6efd;
            min-height: 38px;
            border-radius: 0.375rem;
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
            <button class="btn text-white px-3 py-2 shadow-sm" style="background-color:var(--brand-primary);"
                onclick="openAddModal()">
                <i class="fas fa-plus me-1"></i> <span class="d-none d-md-inline">Add Department</span>
            </button>
        </div>

        <div class="d-block d-md-none mb-3">
            <div class="d-flex gap-2">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0" id="mobileSearch"
                        placeholder="Search departments...">
                </div>
                <button class="btn btn-success shadow-sm px-3" id="mobileExcelBtn" title="Download Excel">
                    <i class="fas fa-file-excel"></i>
                </button>
            </div>
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

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="deptTable" class="table table-hover table-custom w-100">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Department Name</th>
                                <th>Assigned To</th>
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

        <div class="d-md-none" id="mobileCardsContainer">
            <div class="text-center py-5" id="mobileLoader">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted small">Loading departments...</p>
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
                            <div class="col-md-12" id="modalCompanyContainer">
                                <label class="form-label small fw-bold text-secondary">Assign to Company(s) <span
                                        class="text-muted fw-normal">(Khali = All Companies)</span></label>
                                <select class="form-control" name="company_ids[]" id="m_company_ids" multiple
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
                                </select>
                            </div>
                        </div>

                        <div class="mb-2 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-primary mb-0"><i class="fas fa-user-tag me-1"></i> Include
                                Designations</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addDesignationRow()">
                                <i class="fas fa-plus"></i> Add Row
                            </button>
                        </div>

                        <div class="bg-light p-3 rounded border">
                            <div class="row g-2 mb-2 d-none d-md-flex">
                                <div class="col-6"><label class="small fw-bold text-muted">Designation Name</label></div>
                                <div class="col-5"><label class="small fw-bold text-muted">Short Code</label></div>
                                <div class="col-1"></div>
                            </div>
                            <div id="designationRowsContainer">
                            </div>
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
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        let table;
        const apiToken = localStorage.getItem('admin_token');

        // 🔥 MOCK ROLES 🔥
        const loggedInRole = 'super_admin';
        const loggedInCompanyId = null;

        // ==========================================
        // GLOBAL FUNCTIONS (Window Context)
        // ==========================================
        window.openAddModal = function() {
            $('#deptForm')[0].reset();
            $('#edit_id').val('');
            $('#form_method').val('POST');
            $('#m_company_ids').val(null).trigger('change');
            $('#designationRowsContainer').empty();

            addDesignationRow();

            if (loggedInRole === 'director') {
                $('#m_company_ids').val([loggedInCompanyId]).trigger('change');
                $('#modalCompanyContainer').hide();
            }

            $('#modalTitle').html('<i class="fas fa-sitemap me-2 text-primary"></i> Add Department');
            $('#deptModal').modal('show');
        };

        window.addDesignationRow = function(id = '', name = '', code = '') {
            let rowHtml = `
                <div class="row g-2 mb-2 desig-row align-items-center">
                    <input type="hidden" class="d-id" value="${id}">
                    <div class="col-12 col-md-6 mb-2 mb-md-0">
                        <input type="text" class="form-control d-name" placeholder="e.g. Project Manager" value="${name}" required>
                    </div>
                    <div class="col-10 col-md-5">
                        <input type="text" class="form-control text-uppercase d-code" placeholder="e.g. PM" maxlength="10" value="${code}" required>
                    </div>
                    <div class="col-2 col-md-1 text-end">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="$(this).closest('.desig-row').remove()"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `;
            $('#designationRowsContainer').append(rowHtml);
        };

        $(document).ready(function() {
            if (!apiToken) window.location.href = '/admin/login';

            $('#m_company_ids').select2({
                dropdownParent: $('#deptModal'),
                placeholder: '-- Select Companies --',
                allowClear: true
            });

            function loadCompanies() {
                $.ajax({
                    url: '/api/v1/admin/get-active-companies',
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        let filterOpts = '<option value="">-- All Companies --</option>';
                        let multiOpts = '<option value="all">-- Apply to All (Global) --</option>';
                        if (res.data) {
                            res.data.forEach(c => {
                                let text = `${c.company_name} (${c.company_code})`;
                                filterOpts += `<option value="${c.id}">${text}</option>`;
                                multiOpts += `<option value="${c.id}">${text}</option>`;
                            });
                        }
                        $('#filter_company').html(filterOpts);
                        $('#m_company_ids').html(multiOpts);

                        if (loggedInRole === 'director') {
                            $('#filter_company').val(loggedInCompanyId);
                            $('#globalFilterCard').hide();
                        }
                    }
                });
            }
            loadCompanies();

            // ==========================================
            // DATATABLE INIT & DOM SETTINGS
            // ==========================================
            table = $('#deptTable').DataTable({
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export Excel',
                    className: 'btn btn-success btn-sm shadow-sm rounded-3'
                }],
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/admin/departments',
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    dataSrc: 'data'
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
                        render: s => s === 'active' ? `<span class="status-active">Active</span>` :
                            `<span class="status-inactive">Inactive</span>`
                    },
                    {
                        data: 'id',
                        className: 'text-center text-nowrap',
                        orderable: false,
                        render: d => `
                        <button class="btn btn-sm btn-light border text-primary shadow-sm edit-btn me-1" data-id="${d}"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-light border text-danger shadow-sm delete-btn" data-id="${d}"><i class="fas fa-trash"></i></button>
                    `
                    }
                ],
                drawCallback: function(settings) {
                    // Mobile Cards Render
                    renderMobileCards(settings.json?.data || this.api().data().toArray());
                }
            });

            // 🔥 MOBILE SEARCH & EXCEL BINDING 🔥
            $('#mobileSearch').on('keyup', function() {
                table.search(this.value).draw();
            });

            $('#mobileExcelBtn').on('click', function() {
                $('.buttons-excel').click();
            });

            // 🔥 RENDER MOBILE CARDS LOGIC 🔥
            function renderMobileCards(data) {
                $('#mobileLoader').hide();
                let html = '';
                if (!data || data.length === 0) {
                    html =
                        '<div class="text-center p-4 bg-white rounded shadow-sm text-muted">No departments found.</div>';
                } else {
                    data.forEach(d => {
                        let statusBadge = d.status === 'active' ?
                            '<span class="badge bg-success-subtle text-success">Active</span>' :
                            '<span class="badge bg-danger-subtle text-danger">Inactive</span>';

                        html += `
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="fw-bold mb-1 text-primary">${d.department_name}</h6>
                                        <small class="text-muted d-block"><i class="fas fa-building me-1"></i> ${d.company_name}</small>
                                        <span class="badge bg-secondary mt-2">${d.designation_count} Designations</span>
                                    </div>
                                    <div class="text-end">
                                        ${statusBadge}
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end align-items-center pt-2 border-top gap-1 mt-3">
                                    <button class="btn btn-sm btn-light border text-primary edit-btn flex-fill" data-id="${d.id}"><i class="fas fa-edit me-1"></i> Edit</button>
                                    <button class="btn btn-sm btn-light border text-danger delete-btn flex-fill" data-id="${d.id}"><i class="fas fa-trash-alt me-1"></i> Delete</button>
                                </div>
                            </div>
                        </div>`;
                    });
                }
                $('#mobileCardsContainer').html(html);
            }

            // FILTER
            $('#filter_company').change(function() {
                // Future Implementation: Add parameter to API call
            });

            // FORM SUBMIT
            $('#deptForm').submit(function(e) {
                e.preventDefault();

                let designationsList = [];
                $('.desig-row').each(function() {
                    let dName = $(this).find('.d-name').val().trim();
                    let dCode = $(this).find('.d-code').val().trim();
                    let dId = $(this).find('.d-id').val();
                    if (dName !== '' && dCode !== '') {
                        designationsList.push({
                            id: dId,
                            name: dName,
                            code: dCode
                        });
                    }
                });

                if (designationsList.length === 0) {
                    Swal.fire('Warning', 'Please add at least one designation!', 'warning');
                    return;
                }

                let formData = $(this).serializeArray();
                formData.push({
                    name: 'designations',
                    value: JSON.stringify(designationsList)
                });

                if (loggedInRole === 'director') {
                    formData = formData.filter(f => f.name !== 'company_ids[]');
                    formData.push({
                        name: 'company_ids[]',
                        value: loggedInCompanyId
                    });
                }

                let id = $('#edit_id').val();
                let method = $('#form_method').val();
                let url = method === 'PUT' ? `/api/v1/admin/departments/${id}` :
                '/api/v1/admin/departments';

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
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
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

            // EDIT DATA LOAD
            $(document).on('click', '.edit-btn', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: `/api/v1/admin/departments/${id}`,
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        let d = res.data;
                        $('#edit_id').val(d.id);
                        $('#form_method').val('PUT');
                        $('#m_dept_name').val(d.department_name);
                        $('#m_status').val(d.status);

                        let cIds = (d.company_ids && d.company_ids.length > 0) ? d.company_ids
                            .map(String) : [];
                        $('#m_company_ids').val(cIds).trigger('change');

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

            // DELETE
            $(document).on('click', '.delete-btn', function() {
                let id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Department?',
                    text: 'This will also delete ALL associated Designations!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/api/v1/admin/departments/${id}`,
                            type: 'DELETE',
                            headers: {
                                'Authorization': 'Bearer ' + apiToken
                            },
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
