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

        .desig-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px var(--shadow-color);
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

        .level-badge {
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .level-master {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .level-company {
            background: #eff6ff;
            color: #1e3a8a;
            border: 1px solid #bfdbfe;
        }

        .level-branch {
            background: #fdf4ff;
            color: #1e40af;
            border: 1px solid #e0e7ff;
        }

        .level-global {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        /* Select2 Custom Styles */
        .select2-container .select2-selection--multiple {
            border: 1px solid #0d6efd;
            min-height: 38px;
            border-radius: 0.375rem;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: var(--sidebar-bg);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 2px 8px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            margin-right: 5px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #ff4d4d;
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);"><i
                        class="fas fa-user-tag text-primary me-2"></i>Employee Designations</h4>
            </div>
          <div class="d-flex gap-2">
    <button class="btn btn-danger px-3 py-2 shadow-sm d-none secured-item" data-permission="designation_delete" id="bulkDeleteBtn">
        <i class="fas fa-trash-alt me-1"></i> Delete Selected
    </button>
    <button class="btn text-white px-3 py-2 shadow-sm secured-item" data-permission="designation_add" style="background-color: var(--brand-primary);" onclick="openAddModal()">
        <i class="fas fa-plus me-1"></i> Add Designation
    </button>
</div>
        </div>

        <div class="card border-0 shadow-sm mb-3" id="globalFilterCard">
            <div class="card-body p-3 d-flex align-items-center gap-3 flex-wrap">
                <span class="fw-bold text-secondary"><i class="fas fa-filter text-primary me-1"></i> View Filter:</span>
                <div class="input-group" style="max-width: 280px;" id="filterCompanyContainer">
                    <span class="input-group-text bg-white"><i class="fas fa-industry text-primary"></i></span>
                    <select class="form-select fw-medium text-secondary" id="filter_company">
                        <option value="">-- All Companies --</option>
                    </select>
                </div>
                <div class="input-group" style="max-width: 280px;" id="filterBranchContainer">
                    <span class="input-group-text bg-white"><i class="fas fa-building text-primary"></i></span>
                    <select class="form-select fw-medium text-secondary" id="filter_branch">
                        <option value="">-- All Branches --</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="desigTable" class="table table-hover table-custom w-100">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAll"
                                        class="form-check-input border-secondary"></th>
                                <th>Code</th>
                                <th>Designation Name</th>
                                <th>Allocation Level</th>
                                <th>Assigned Entities</th>
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
    </div>

    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i
                            class="fas fa-briefcase me-2 text-primary"></i> Add Designation</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addForm">
                        <div class="mb-3" id="modalCompanyContainer">
                            <label class="form-label small fw-bold text-secondary">Assign to Company(s) <small
                                    class="text-primary">(Leave blank for Master)</small></label>
                            <select class="form-control select2-multi" name="company_ids[]" id="add_company_ids"
                                multiple="multiple" style="width: 100%;">
                                <option value="all">-- Apply to All Companies --</option>
                            </select>
                        </div>
                        <div class="mb-3" id="modalBranchContainer">
                            <label class="form-label small fw-bold text-secondary">Assign to Branch(es) <small
                                    class="text-primary">(Leave blank for Head Office)</small></label>
                            <select class="form-control select2-multi" name="branch_ids[]" id="add_branch_ids"
                                multiple="multiple" style="width: 100%;">
                                <option value="all">-- Apply to All Branches --</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Code (Short) <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase border-primary" name="designation_code"
                                required maxlength="10">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Designation Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control border-primary" name="designation_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Status</label>
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <button type="submit" class="btn text-white w-100 py-2 fw-medium mt-2"
                            style="background-color: var(--sidebar-bg);" id="saveBtn">Save Designation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i
                            class="fas fa-edit me-2 text-primary"></i> Edit Designation</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="edit_id">
                        <div class="mb-3" id="editModalCompanyContainer">
                            <label class="form-label small fw-bold text-secondary">Assign to Company(s)</label>
                            <select class="form-control select2-multi" id="edit_company_ids" name="company_ids[]"
                                multiple="multiple" style="width: 100%;">
                                <option value="all">-- Apply to All Companies --</option>
                            </select>
                        </div>
                        <div class="mb-3" id="editModalBranchContainer">
                            <label class="form-label small fw-bold text-secondary">Assign to Branch(es)</label>
                            <select class="form-control select2-multi" id="edit_branch_ids" name="branch_ids[]"
                                multiple="multiple" style="width: 100%;">
                                <option value="all">-- Apply to All Branches --</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Code (Short) <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase border-primary" id="edit_code"
                                name="designation_code" required maxlength="10">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Designation Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control border-primary" id="edit_name"
                                name="designation_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Status</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <button type="submit" class="btn text-white w-100 py-2 fw-medium mt-2"
                            style="background-color: var(--sidebar-bg);" id="updateBtn">Update Designation</button>
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
        let fullBranchesData = [];
        const apiToken = localStorage.getItem('admin_token');

        // MOCK ROLES
        const loggedInRole = 'super_admin';
        const loggedInCompanyId = null;
        const loggedInBranchId = null;

        window.openAddModal = function() {
            $('#addForm')[0].reset();

            // Un-disable Branch dropdown default pe
            $('#add_branch_ids').prop('disabled', false);

            $('#add_company_ids').val(null).trigger('change');
            $('#add_branch_ids').val(null).trigger('change');

            if (loggedInRole === 'director') {
                $('#add_company_ids').val([loggedInCompanyId]).trigger('change');
            }
            $('#addModal').modal('show');
        };

        $(document).ready(function() {
            //if (!apiToken) window.location.href = '/login';

            // Select2 Init
            $('.select2-multi').select2({
                placeholder: "Select Options",
                allowClear: true
            });
            $('#add_company_ids, #add_branch_ids').select2({
                dropdownParent: $('#addModal')
            });
            $('#edit_company_ids, #edit_branch_ids').select2({
                dropdownParent: $('#editModal')
            });

            // Apply Role Restrictions UI
            function applyRoleRestrictions() {
                if (loggedInRole === 'director' || loggedInRole === 'company_head') {
                    $('#filterCompanyContainer, #modalCompanyContainer, #editModalCompanyContainer').hide();
                } else if (loggedInRole === 'branch_manager' || loggedInRole === 'branch_employee') {
                    $('#globalFilterCard, #modalCompanyContainer, #editModalCompanyContainer, #modalBranchContainer, #editModalBranchContainer')
                        .hide();
                }
            }
            applyRoleRestrictions();

            // Load Comapnies & Branches
            function loadFiltersAndModals() {
                $.ajax({
                    url: '/api/v1/get-active-companies',
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        let filterOpts = '<option value="">-- All Companies --</option>';
                        let multiOpts = '<option value="all">-- Apply to All Companies --</option>';
                        res.data.forEach(c => {
                            filterOpts += `<option value="${c.id}">${c.company_name}</option>`;
                            multiOpts +=
                                `<option value="${c.id}">${c.company_name} (${c.company_code})</option>`;
                        });
                        $('#filter_company').html(filterOpts);
                        $('#add_company_ids, #edit_company_ids').html(multiOpts);
                    }
                });

                $.ajax({
                    url: '/api/v1/branches',
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        fullBranchesData = res.data;
                        updateMultiBranchDropdowns([], '#add_branch_ids');
                        updateMultiBranchDropdowns([], '#edit_branch_ids');
                    }
                });
            }
            loadFiltersAndModals();

            // Cascading Branch Logic
            function updateMultiBranchDropdowns(selectedCompanies, targetDropdown) {
                let filteredBranches = fullBranchesData;

                if (loggedInRole === 'director') selectedCompanies = [loggedInCompanyId.toString()];

                if (selectedCompanies && selectedCompanies.length > 0 && !selectedCompanies.includes('all')) {
                    filteredBranches = fullBranchesData.filter(b => selectedCompanies.includes(b.company_id
                        .toString()) || selectedCompanies.includes(b.company_id));
                }

                let opts = '<option value="all">-- Apply to All Branches --</option>';
                filteredBranches.forEach(b => {
                    if (b.branch_status === 'active') opts +=
                        `<option value="${b.id}">${b.branch_name} (${b.branch_id})</option>`;
                });
                $(targetDropdown).html(opts).trigger('change.select2');
            }

            // 🔥 AUTO-LOCK LOGIC WHEN 'Apply To All' SELECTED 🔥
            $('#add_company_ids').on('change', function() {
                let vals = $(this).val() || [];
                if (vals.includes('all')) {
                    $('#add_branch_ids').val(['all']).trigger('change.select2').prop('disabled', true);
                } else {
                    $('#add_branch_ids').prop('disabled', false);
                    updateMultiBranchDropdowns(vals, '#add_branch_ids');
                }
            });

            $('#edit_company_ids').on('change', function() {
                let vals = $(this).val() || [];
                if (vals.includes('all')) {
                    $('#edit_branch_ids').val(['all']).trigger('change.select2').prop('disabled', true);
                } else {
                    $('#edit_branch_ids').prop('disabled', false);
                    updateMultiBranchDropdowns(vals, '#edit_branch_ids');
                }
            });

            // Datatable
            table = $('#desigTable').DataTable({
                serverSide: false,
                autoWidth: false,
                ajax: {
                    url: '/api/v1/designations',
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    data: function(d) {
                        d.company_id = $('#filter_company').val() || (loggedInRole === 'director' ?
                            loggedInCompanyId : '');
                        d.branch_id = $('#filter_branch').val() || (loggedInRole === 'branch_manager' ?
                            loggedInBranchId : '');
                    },
                    dataSrc: 'data'
                },
                columns: [{
                        data: 'id',
                        orderable: false,
                        className: 'text-center',
                        render: d =>
                            `<input type="checkbox" class="form-check-input border-secondary row-checkbox" value="${d}">`
                    },
                    {
                        data: 'designation_code',
                        render: d =>
                            `<span class="fw-bold" style="color:var(--brand-primary)">${d}</span>`
                    },
                    {
                        data: 'designation_name',
                        render: d => `<span class="fw-medium">${d}</span>`
                    },
                    {
                        data: 'level',
                        render: function(d) {
                            if (d === 'Global (All Companies & Branches)')
                            return `<span class="level-badge level-global"><i class="fas fa-globe"></i> ${d}</span>`;
                            if (d.includes('Master'))
                            return `<span class="level-badge level-master"><i class="fas fa-crown"></i> ${d}</span>`;
                            if (d.includes('Company Head'))
                            return `<span class="level-badge level-company"><i class="fas fa-city"></i> ${d}</span>`;
                            return `<span class="level-badge level-branch"><i class="fas fa-code-branch"></i> ${d}</span>`;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `<div class="small fw-bold text-secondary"><i class="fas fa-building me-1"></i>${row.company_name} <br><i class="fas fa-map-marker-alt me-1"></i>${row.branch_name}</div>`;
                        }
                    },
                    {
                        data: 'status',
                        render: s => s === 'active' ? `<span class="status-active">Active</span>` :
                            `<span class="status-inactive">Inactive</span>`
                    },
                    {
    data: 'id',
    orderable: false,
    render: d => `<div class="text-end"><button class="btn btn-sm btn-light text-primary me-1 edit-btn secured-item" data-permission="designation_edit" data-id="${d}"><i class="fas fa-edit"></i></button></div>`
}
                ],
                drawCallback: function(settings) {
                    $('#selectAll').prop('checked', false);
                    toggleBulkDeleteBtn();
                    if (settings.json && settings.json.data) loadMobileCards(settings.json.data);
                    else if (this.api().data().length > 0) loadMobileCards(this.api().data().toArray());
                }
            });

            // Filters
            $('#filter_company').change(function() {
                let cid = $(this).val();
                let filtered = cid ? fullBranchesData.filter(b => b.company_id == cid) : fullBranchesData;
                let opts = '<option value="">-- All Branches --</option>';
                filtered.forEach(b => {
                    if (b.branch_status === 'active') opts +=
                        `<option value="${b.id}">${b.branch_name}</option>`;
                });
                $('#filter_branch').html(opts);
                table.ajax.reload();
            });
            $('#filter_branch').change(function() {
                table.ajax.reload();
            });

            function loadMobileCards(data) {
                let html = '';
                if (!data || data.length === 0) {
                    html =
                        '<div class="text-center text-muted p-3 border rounded bg-light">No designations found.</div>';
                } else {
                    data.forEach(d => {
                        let statusHtml = d.status === 'active' ?
                            `<span class="status-active">Active</span>` :
                            `<span class="status-inactive">Inactive</span>`;
                        let lvlClass = d.level.includes('Global') ? 'level-global' : (d.level.includes(
                            'Master') ? 'level-master' : (d.level.includes('Company Head') ?
                            'level-company' : 'level-branch'));
                        html += `
                        <div class="desig-card mobile-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div><h6 class="fw-bold mb-1" style="color: var(--sidebar-bg);">${d.designation_name}</h6><div class="small fw-bold" style="color: var(--brand-primary);">${d.designation_code}</div></div>
                                ${statusHtml}
                            </div>
                            <div class="mb-2"><span class="level-badge ${lvlClass}">${d.level}</span></div>
                            <div class="small text-muted mb-2"><i class="fas fa-building me-1"></i> ${d.company_name} <br><i class="fas fa-map-marker-alt me-1"></i> ${d.branch_name}</div>
                            <div class="d-flex gap-2 border-top pt-2 mt-2">
    <button class="btn btn-sm btn-light text-primary flex-fill fw-medium edit-btn secured-item" data-permission="designation_edit" data-id="${d.id}"><i class="fas fa-edit me-1"></i> Edit</button>
</div>
                        </div>`;
                    });
                }
                $('#mobileCardsContainer').html(html);
            }

            // ADD
            $('#addForm').submit(function(e) {
                e.preventDefault();
                let btn = $('#saveBtn');
                btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

                // Fix for disabled selects not submitting
                $('#add_branch_ids').prop('disabled', false);
                let formData = $(this).serializeArray();

                if (loggedInRole === 'branch_manager') {
                    formData.push({
                        name: 'branch_ids[]',
                        value: loggedInBranchId
                    });
                    formData.push({
                        name: 'company_ids[]',
                        value: loggedInCompanyId
                    });
                }

                $.ajax({
                    url: '/api/v1/designations',
                    type: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    data: formData,
                    success: function() {
                        $('#addModal').modal('hide');
                        Swal.fire('Success', 'Designation added', 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function(err) {
                        Swal.fire('Error', err.responseJSON.message, 'error');
                    },
                    complete: function() {
                        btn.html('Save Designation').prop('disabled', false);
                    }
                });
            });

            // EDIT 
            $(document).on('click', '.edit-btn', function() {
                let id = $(this).data('id');
                $.get({
                    url: `/api/v1/designations/${id}`,
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        let d = res.data;
                        $('#edit_id').val(d.id);
                        $('#edit_code').val(d.designation_code);
                        $('#edit_name').val(d.designation_name);
                        $('#edit_status').val(d.status);

                        $('#edit_branch_ids').prop('disabled', false); // Unlock to set value

                        let cIds = d.company_ids ? d.company_ids : [];
                        let bIds = d.branch_ids ? d.branch_ids : [];

                        $('#edit_company_ids').val(cIds).trigger('change');
                        setTimeout(() => {
                            $('#edit_branch_ids').val(bIds).trigger('change');
                        }, 200);

                        $('#editModal').modal('show');
                    }
                });
            });

            // UPDATE
            $('#editForm').submit(function(e) {
                e.preventDefault();
                let id = $('#edit_id').val();
                let btn = $('#updateBtn');
                btn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);

                $('#edit_branch_ids').prop('disabled', false);
                let formData = $(this).serializeArray();

                $.ajax({
                    url: `/api/v1/designations/${id}`,
                    type: 'PUT',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    data: formData,
                    success: function() {
                        $('#editModal').modal('hide');
                        Swal.fire('Updated', 'Designation updated', 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function(err) {
                        Swal.fire('Error', err.responseJSON.message, 'error');
                    },
                    complete: function() {
                        btn.html('Update Designation').prop('disabled', false);
                    }
                });
            });

            // BULK DELETE
            $('#selectAll').on('change', function() {
                $('.row-checkbox').prop('checked', this.checked);
                toggleBulkDeleteBtn();
            });
            $('#desigTable tbody').on('change', '.row-checkbox', function() {
                if (!this.checked) $('#selectAll').prop('checked', false);
                if ($('.row-checkbox:checked').length === $('.row-checkbox').length) $('#selectAll').prop(
                    'checked', true);
                toggleBulkDeleteBtn();
            });

            function toggleBulkDeleteBtn() {
                if ($('.row-checkbox:checked').length > 0) $('#bulkDeleteBtn').removeClass('d-none');
                else $('#bulkDeleteBtn').addClass('d-none');
            }

            $('#bulkDeleteBtn').on('click', function() {
                let selectedIds = [];
                $('.row-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length > 0) {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: `Delete ${selectedIds.length} designation(s)?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            let btn = $(this);
                            let originalText = btn.html();
                            btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Deleting...')
                                .prop('disabled', true);

                            $.ajax({
                                url: '/api/v1/bulk-delete',
                                type: 'POST',
                                headers: {
                                    'Authorization': 'Bearer ' + apiToken
                                },
                                data: {
                                    table_name: 'designations',
                                    ids: selectedIds
                                },
                                success: function(res) {
                                    Swal.fire('Deleted!', res.message, 'success');
                                    table.ajax.reload(null, false);
                                },
                                error: function(err) {
                                    Swal.fire('Error', err.responseJSON.message,
                                        'error');
                                },
                                complete: function() {
                                    btn.html(originalText).prop('disabled', false);
                                    $('#selectAll').prop('checked', false);
                                    toggleBulkDeleteBtn();
                                }
                            });
                        }
                    });
                }
            });
        });
    </script>
@endpush
