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

        .mobile-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s, box-shadow 0.2s;
            margin-bottom: 15px;
            padding: 15px;
            position: relative;
        }

        /* Floating Bulk Action Bar */
        #bulkActionContainer {
            display: none;
            background: var(--sidebar-bg);
            color: white;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 15px;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color:var(--sidebar-bg);"><i
                        class="fas fa-sitemap text-primary me-2"></i>Department Master</h4>
                <p class="text-secondary small d-none d-md-block mb-0">Manage Departments and Hierarchical Designations</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-success px-3 py-2 shadow-sm" id="customExcelBtn" style="display:none;"
                    onclick="table.button('.buttons-excel').trigger()">
                    <i class="fas fa-file-excel me-1"></i> <span class="d-none d-md-inline">Excel</span>
                </button>

                <button class="btn text-white px-3 py-2 shadow-sm" id="addDepartmentBtn"
                    style="background-color:var(--brand-primary); display:none;" onclick="openAddModal()">
                    <i class="fas fa-plus me-1"></i> <span class="d-none d-md-inline" id="addBtnText">Add Department</span>
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

        <div id="bulkActionContainer" class="animate__animated animate__fadeIn" style="display: none;">
            <div class="d-flex align-items-center gap-2">
                <input type="checkbox" class="form-check-input mt-0" id="selectAllMaster"
                    style="width: 1.2rem; height: 1.2rem;">
                <span class="fw-bold" id="selectedCount">0 Selected</span>
            </div>
            <div>
                <button class="btn btn-sm btn-danger fw-bold shadow-sm secured-item" data-permission="department_delete"
                    onclick="executeBulkDelete()"><i class="fas fa-trash-alt me-1"></i> Delete Selected</button>
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
                                <th style="width: 40px;" class="no-export text-center">
                                    <input type="checkbox" class="form-check-input" id="selectAllDt">
                                </th>
                                <th style="width: 50px;">Sl.</th>
                                <th>Department Name</th>
                                <th>Assigned To (Company)</th>
                                <th>Total Designations</th>
                                <th>Status</th>
                                <th class="text-center no-export">Actions</th>
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

    <div class="modal fade" id="deptModal" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
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
                                <label class="form-label small fw-bold text-secondary">Assign to Company(s)</label>
                                <select class="form-control" name="company_ids[]" id="m_company_ids" multiple
                                    style="width:100%;"></select>
                            </div>
                            <div class="col-md-6" id="modalBranchContainer">
                                <label class="form-label small fw-bold text-secondary">Assign to Branch(es)</label>
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
                                Designations</h6>
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

    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-primary"><i class="fas fa-eye me-2"></i> View Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered table-sm">
                        <tr>
                            <th class="bg-light" style="width:40%;">Department Name</th>
                            <td id="v_dept_name" class="fw-bold"></td>
                        </tr>
                        <tr>
                            <th class="bg-light">Companies</th>
                            <td id="v_companies"></td>
                        </tr>
                        <tr>
                            <th class="bg-light">Status</th>
                            <td id="v_status"></td>
                        </tr>
                    </table>
                    <h6 class="fw-bold text-secondary mt-3 mb-2 border-bottom pb-1">Designations List</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead class="bg-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="v_designations_list"></tbody>
                        </table>
                    </div>
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
        let currentPortal = window.location.pathname.split('/')[1] || 'admin';
        let currentUserData = null;
        let globalCompanyOptions = '<option value="all">-- Apply to All (Global) --</option>';

        function hasPerm(slug) {
            if (window.userGodMode) return true;
            return (window.userPerms || []).includes(slug);
        }

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

        $(document).on('input', '#m_dept_name', function() {
            let val = $(this).val().toLowerCase();
            if (val.includes('associate')) {
                $('.associate-extra-fields').removeClass('d-none');
            } else {
                // Agar galti se associate delete kar diya, toh fields wapas hide ho jayenge aur value clear ho jayegi
                $('.associate-extra-fields').addClass('d-none');
                $('.d-position, .d-plot-comm, .d-const-comm').val('');
            }
        });

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

                    // Head office inject logic based on selected companies
                    if (!payload.includes('all')) {
                        payload.forEach(cid => {
                            options +=
                                `<option value="HO_${cid}" class="text-primary fw-bold">📍 Head Office (Co. ID: ${cid})</option>`;
                        });
                    }

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

        function toggleBulkActions() {
            let ids = [];
            $('.row-checkbox:checked').each(function() {
                ids.push($(this).val());
            });
            // 🟢 Set ka use karke duplicate counts hata diye (Desktop + Mobile mix nahi hoga)
            let uniqueCount = new Set(ids).size;

            if (uniqueCount > 0) {
                $('#selectedCount').text(`${uniqueCount} Selected`);
                $('#bulkActionContainer').css('display', 'flex');
            } else {
                $('#bulkActionContainer').hide();
                $('#selectAllDt, #selectAllMaster').prop('checked', false);
            }
        }

        window.executeBulkDelete = function() {
            let ids = [];
            $('.row-checkbox:checked').each(function() {
                ids.push($(this).val());
            });
            // 🟢 Array se unique IDs filter kar liye backend ke liye
            let uniqueIds = [...new Set(ids)];

            Swal.fire({
                title: 'Delete Selected?',
                text: `You are about to delete ${uniqueIds.length} departments.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, Delete!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/api/v1/departments/bulk-delete',
                        type: 'POST',
                        data: {
                            ids: uniqueIds
                        },
                        success: function(res) {
                            Swal.fire('Deleted!', res.message, 'success');
                            table.ajax.reload(null, false);
                            toggleBulkActions();
                        }
                    });
                }
            });
        }

        window.actionReq = function(id, action) {
            Swal.fire({
                title: `${action.charAt(0).toUpperCase() + action.slice(1)} Department?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                confirmButtonText: `Yes, ${action}!`
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/api/v1/departments/${id}/${action}`,
                        type: 'POST',
                        success: function(res) {
                            Swal.fire('Success!', res.message, 'success');
                            table.ajax.reload(null, false);
                        }
                    });
                }
            });
        }

        window.openAddModal = function() {
            $('#deptForm')[0].reset();
            $('#edit_id').val('');
            $('#form_method').val('POST');
            $('#designationRowsContainer').empty();
            addDesignationRow();

            let isDirector = currentUserData?.designation_name?.toLowerCase().includes('director');

            if (window.userGodMode) {
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

            // Lock pending status if only request permission
            if (!hasPerm('department_add_direct') && hasPerm('department_add_request')) {
                $('#m_status').html('<option value="pending" selected>Pending</option>').prop('disabled', true);
                $('#modalTitle').html('<i class="fas fa-paper-plane me-2 text-warning"></i> Request Department');
            } else {
                $('#m_status').html('<option value="active">Active</option><option value="inactive">Inactive</option>')
                    .prop('disabled', false);
                $('#modalTitle').html('<i class="fas fa-sitemap me-2 text-primary"></i> Add Department');
            }

            $('#deptModal').modal('show');
        };

        window.addDesignationRow = function(id = '', name = '', code = '', status = 'active', position = '', plotComm = '',
            constComm = '') {
            let statusSelect = `
        <select class="form-select form-select-sm d-status">
            <option value="active" ${status === 'active' ? 'selected' : ''}>Active</option>
            <option value="inactive" ${status === 'inactive' ? 'selected' : ''}>Inactive</option>
            <option value="pending" ${status === 'pending' ? 'selected' : ''}>Pending</option>
        </select>
    `;

            if (!hasPerm('department_add_direct') && hasPerm('department_add_request') && $('#form_method').val() ===
                'POST') {
                statusSelect =
                    `<select class="form-select form-select-sm d-status" disabled><option value="pending" selected>Pending</option></select>`;
            }

            // Check karein ki department name mein 'associate' hai ya nahi
            let deptName = ($('#m_dept_name').val() || '').toLowerCase();
            let showExtra = deptName.includes('associate') ? '' : 'd-none';

            let rowHtml = `
        <div class="p-2 border rounded mb-2 desig-row bg-white shadow-sm">
            <div class="row g-2 align-items-center">
                <input type="hidden" class="d-id" value="${id}">
                <div class="col-12 col-md-4 mb-2 mb-md-0"><input type="text" class="form-control form-control-sm d-name" placeholder="e.g. Manager" value="${name}" required></div>
                <div class="col-7 col-md-3"><input type="text" class="form-control form-control-sm text-uppercase d-code" placeholder="CODE" value="${code}" required></div>
                <div class="col-3 col-md-3">${statusSelect}</div>
                <div class="col-2 col-md-2 text-end"><button type="button" class="btn btn-danger btn-sm w-100" onclick="$(this).closest('.desig-row').remove()"><i class="fas fa-trash"></i></button></div>
            </div>
            
            <div class="row g-2 mt-2 associate-extra-fields ${showExtra}">
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm d-position border-info" placeholder="Position" value="${position}">
                </div>
                <div class="col-md-4">
                    <input type="number" step="0.01" class="form-control form-control-sm d-plot-comm border-info" placeholder="Plot Commission (%)" value="${plotComm}">
                </div>
                <div class="col-md-4">
                    <input type="number" step="0.01" class="form-control form-control-sm d-const-comm border-info" placeholder="Const. Commission (%)" value="${constComm}">
                </div>
            </div>
        </div>`;
            $('#designationRowsContainer').append(rowHtml);

            // 🔥 Naya row add hone par UI ko smoothly niche scroll karne ke liye
            let modalBody = $('#deptModal .modal-body');
            if (modalBody.length) {
                // Pehle se chal rahi animation ko turant rok do, warna queue clash se
                // scrollbar ulta-pulta jump karta hai (upar-niche).
                modalBody.stop(true, false);

                // Ek frame wait karo taaki naya row DOM me fully reflow ho jaye,
                // tabhi scrollHeight ki sahi value milegi.
                requestAnimationFrame(function() {
                    modalBody.animate({
                        scrollTop: modalBody.prop('scrollHeight')
                    }, 300);
                });
            }
        };

        function renderMobileCards(dataset) {
            $('#cardsLoader').hide();
            let gridHtml = '';

            if (!dataset || dataset.length === 0) {
                $('#mobileCardsContainer').html(
                    '<div class="text-center p-4 bg-white rounded shadow-sm text-muted small">No departments found.</div>'
                );
                return;
            }

            dataset.forEach(item => {
                let stBadge = item.status === 'pending' ? '<span class="status-pending">Pending</span>' : (item
                    .status === 'active' ? '<span class="status-active">Active</span>' :
                    '<span class="status-inactive">Inactive</span>');
                let cName = item.company_name || 'Master HO';

                let actionBtns = '';
                if (hasPerm('department_view')) actionBtns +=
                    `<button class="btn btn-sm btn-light border text-info flex-fill view-btn" data-id="${item.id}"><i class="fas fa-eye"></i></button>`;
                if (hasPerm('department_edit')) actionBtns +=
                    `<button class="btn btn-sm btn-light border text-primary flex-fill edit-btn" data-id="${item.id}"><i class="fas fa-edit"></i></button>`;
                if (item.status === 'pending' && hasPerm('department_appr')) actionBtns +=
                    `<button class="btn btn-sm btn-light border text-success flex-fill" onclick="actionReq(${item.id}, 'approve')"><i class="fas fa-check-circle"></i></button>`;
                if (item.status === 'pending' && hasPerm('department_rej')) actionBtns +=
                    `<button class="btn btn-sm btn-light border text-warning flex-fill" onclick="actionReq(${item.id}, 'reject')"><i class="fas fa-times-circle"></i></button>`;

                gridHtml += `
                <div class="mobile-card">
                    <div class="position-absolute" style="top:10px; right:10px;">
                        <input type="checkbox" class="form-check-input row-checkbox" value="${item.id}" style="width:1.2rem; height:1.2rem;">
                    </div>
                    <div class="d-flex justify-content-between align-items-start mb-2 pe-4">
                        <div>
                            <h6 class="fw-bold text-primary mb-1">${item.department_name}</h6>
                            <div class="small text-secondary"><i class="fas fa-building me-1"></i>${cName}</div>
                        </div>
                    </div>
                    <div class="mb-2 d-flex justify-content-between align-items-center">
                        <span class="badge bg-secondary"><i class="fas fa-user-tag me-1"></i>${item.designation_count || 0} Designations</span>
                        ${stBadge}
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top gap-2 mt-3">
                        ${actionBtns}
                    </div>
                </div>`;
            });
            $('#mobileCardsContainer').html(gridHtml);
        }

        $(document).ready(function() {
            loadCompanies();

            // 🟢 Auth Me - Yahan Permissions Check Hongi
            $.ajax({
                url: `/api/v1/${currentPortal}/auth/me`,
                type: 'GET',
                success: function(res) {
                    currentUserData = res.data;

                    // Add/Request Button Logic
                    if (hasPerm('department_add_direct') || hasPerm('department_add_request')) {
                        $('#addDepartmentBtn').show();
                        if (!hasPerm('department_add_direct') && hasPerm('department_add_request')) $(
                            '#addBtnText').text('Request Department');
                    }

                    // 🟢 Excel Export Button Logic (Strict Check)
                    if (hasPerm('department_export')) {
                        $('#customExcelBtn').show();
                    }
                }
            });

            $('#m_company_ids').select2({
                dropdownParent: $('#deptModal .modal-body'),
                placeholder: '-- Select Companies --',
                allowClear: true
            });
            $('#m_branch_ids').select2({
                dropdownParent: $('#deptModal .modal-body'),
                placeholder: '-- Select Branches --',
                allowClear: true
            });
            $('#m_company_ids').on('change', function() {
                fetchAndLoadBranches($(this).val());
            });

            // 🟢 Datatables Setup
            table = $('#deptTable').DataTable({
                // dom se 'B' hata diya taaki default container na bane
                dom: '<"row mb-3"<"col-md-12"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    className: 'd-none buttons-excel', // Isko hide kar diya, trigger custom button se hoga
                    exportOptions: {
                        columns: ':not(.no-export)',
                        format: {
                            body: function(inner, rowidx, colidx, node) {
                                if (colidx === 1) return rowidx + 1; // Auto Sl. No
                                return inner.replace(/<[^>]*>?/gm, '').trim();
                            }
                        }
                    }
                }],
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/departments',
                    type: 'GET',
                    data: function(d) {
                        d.company_ids = $('#filter_company').val();
                    }
                },
                columns: [{
                        data: 'id',
                        className: 'text-center no-export',
                        orderable: false,
                        render: d =>
                            `<input type="checkbox" class="form-check-input row-checkbox" value="${d}">`
                    },
                    {
                        data: null,
                        searchable: false,
                        orderable: false,
                        render: (d, t, r, m) => m.row + m.settings._iDisplayStart + 1
                    }, // Auto SL.No
                    {
                        data: 'department_name',
                        render: d => `<span class="fw-bold text-primary">${d}</span>`
                    },
                    {
                        data: 'company_name',
                        render: d => `<span class="small fw-bold text-secondary">${d}</span>`
                    },
                    {
                        data: 'designation_count',
                        render: d => `<span class="badge bg-secondary">${d}</span>`
                    },
                    {
                        data: 'status',
                        render: s => s === 'pending' ? `<span class="status-pending">Pending</span>` : (
                            s === 'active' ? `<span class="status-active">Active</span>` :
                            `<span class="status-inactive">Inactive</span>`)
                    },
                    {
                        data: null,
                        className: 'text-center text-nowrap no-export',
                        orderable: false,
                        render: function(d) {
                            let btns = `<div class="d-flex justify-content-center gap-1">`;
                            if (hasPerm('department_view')) btns +=
                                `<button class="btn btn-sm btn-light border text-info view-btn" data-id="${d.id}" title="View"><i class="fas fa-eye"></i></button>`;
                            if (hasPerm('department_edit')) btns +=
                                `<button class="btn btn-sm btn-light border text-primary edit-btn" data-id="${d.id}" title="Edit"><i class="fas fa-edit"></i></button>`;
                            if (d.status === 'pending' && hasPerm('department_appr')) btns +=
                                `<button class="btn btn-sm btn-light border text-success" onclick="actionReq(${d.id}, 'approve')" title="Approve"><i class="fas fa-check-circle"></i></button>`;
                            if (d.status === 'pending' && hasPerm('department_rej')) btns +=
                                `<button class="btn btn-sm btn-light border text-warning" onclick="actionReq(${d.id}, 'reject')" title="Reject"><i class="fas fa-times-circle"></i></button>`;
                            btns += `</div>`;
                            return btns;
                        }
                    }
                ],
                drawCallback: function(settings) {
                    $('#selectAllDt, #selectAllMaster').prop('checked', false);
                    toggleBulkActions();
                    if (settings.json && settings.json.data) renderMobileCards(settings.json.data);
                    if (typeof window.applyPermissions === 'function') window.applyPermissions();
                }
            });

            $('#mobileSearch').on('keyup', function() {
                table.search(this.value).draw();
            });
            $('#filter_company').change(function() {
                table.ajax.reload();
            });

            // Checkbox Logics
            $(document).on('change', '#selectAllDt, #selectAllMaster', function() {
                let isChecked = $(this).prop('checked');
                $('.row-checkbox, #selectAllDt, #selectAllMaster').prop('checked', isChecked);
                toggleBulkActions();
            });

            $(document).on('change', '.row-checkbox', function() {
                let isChecked = $(this).prop('checked');
                let val = $(this).val();

                // 🟢 Jo checkbox tick hua hai, uski value wale saare checkboxes (Mobile aur Desktop dono) sync rahenge
                $(`.row-checkbox[value="${val}"]`).prop('checked', isChecked);

                if (!isChecked) $('#selectAllDt, #selectAllMaster').prop('checked', false);
                toggleBulkActions();
            });

            // VIEW Action
            $(document).on('click', '.view-btn', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: `/api/v1/departments/${id}`,
                    type: 'GET',
                    success: function(res) {
                        let d = res.data;
                        $('#v_dept_name').text(d.department_name);
                        $('#v_status').html(d.status === 'active' ?
                            '<span class="badge bg-success">Active</span>' : (d.status ===
                                'pending' ?
                                '<span class="badge bg-warning text-dark">Pending</span>' :
                                '<span class="badge bg-danger">Inactive</span>'));

                        // Map Companies roughly for view
                        let cNames = 'Global / All';
                        if (d.company_ids && d.company_ids.length > 0 && !d.company_ids
                            .includes('all')) {
                            cNames = `Selected IDs: ${d.company_ids.join(', ')}`;
                        }
                        $('#v_companies').text(cNames);

                        let tbody = '';
                        if (d.designations && d.designations.length > 0) {
                            d.designations.forEach(ds => {
                                let st = ds.status === 'active' ? 'text-success' : (ds
                                    .status === 'pending' ? 'text-warning' :
                                    'text-danger');
                                tbody +=
                                    `<tr><td>${ds.designation_name}</td><td>${ds.designation_code}</td><td class="fw-bold ${st}">${ds.status.toUpperCase()}</td></tr>`;
                            });
                        } else {
                            tbody =
                                '<tr><td colspan="3" class="text-center text-muted">No Designations</td></tr>';
                        }
                        $('#v_designations_list').html(tbody);
                        $('#viewModal').modal('show');
                    }
                });
            });

            $('#deptForm').submit(function(e) {
                e.preventDefault();
                let designationsList = [];
                $('.desig-row').each(function() {
                    let dName = $(this).find('.d-name').val().trim(),
                        dCode = $(this).find('.d-code').val().trim(),
                        dStatus = $(this).find('.d-status').val(),
                        dId = $(this).find('.d-id').val(),
                        dPos = $(this).find('.d-position').val().trim(),
                        dPComm = $(this).find('.d-plot-comm').val().trim(),
                        dCComm = $(this).find('.d-const-comm').val().trim();

                    if (dName && dCode) {
                        designationsList.push({
                            id: dId,
                            name: dName,
                            code: dCode,
                            status: dStatus,
                            position: dPos !== "" ? dPos : null,
                            plot_commission: dPComm !== "" ? dPComm : null,
                            construction_commission: dCComm !== "" ? dCComm : null
                        });
                    }
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

                        let isDirector = currentUserData?.designation_name?.toLowerCase()
                            .includes('director');
                        $('#m_status').html(
                            '<option value="active">Active</option><option value="inactive">Inactive</option><option value="pending">Pending</option>'
                        ).val(d.status).prop('disabled', false);

                        let cIds = (d.company_ids && d.company_ids.length > 0) ? d.company_ids
                            .map(String) : [];
                        let bIds = (d.branch_ids && d.branch_ids.length > 0) ? d.branch_ids.map(
                            String) : [];

                        if (window.userGodMode) {
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
                                // Yahan extra parameters paas kiye gaye hain
                                addDesignationRow(
                                    desig.id,
                                    desig.designation_name,
                                    desig.designation_code,
                                    desig.status,
                                    desig.position || '',
                                    desig.plot_commission || '',
                                    desig.construction_commission || ''
                                );
                            });
                        } else {
                            addDesignationRow();
                        }

                        // 🔥 Edit modal open hone ke baad associate wale fields check karne ke liye trigger lagayein
                        setTimeout(() => {
                            $('#m_dept_name').trigger('input');
                        }, 100);

                        $('#modalTitle').html(
                            '<i class="fas fa-edit me-2 text-primary"></i> Edit Department');
                        $('#deptModal').modal('show');
                    }
                });
            });
        });
    </script>
@endpush
