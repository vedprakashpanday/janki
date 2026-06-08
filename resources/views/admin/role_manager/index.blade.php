@extends('layout.app')

@section('content')
   
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        /* Select2 Premium Overrides */
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            min-height: 40px;
            padding: 2px;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 0.2rem rgba(214, 158, 46, 0.25);
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: var(--sidebar-bg) !important;
            color: #fff !important;
            border: none !important;
            border-radius: 4px !important;
            padding: 4px 8px 4px 26px !important;
            font-size: 12px;
            font-weight: 500;
            margin-top: 5px;
            position: relative;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #ffffff !important;
            position: absolute;
            left: 6px;
            top: 50%;
            transform: translateY(-50%);
            border: none !important;
            background: transparent !important;
            font-weight: bold;
        }

        /* Premium Card & Pill Styling */
        .premium-card {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }

        .premium-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 20px;
            border-radius: 10px 10px 0 0;
        }

        .perm-pill {
            display: inline-block;
            padding: 6px 14px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            transition: 0.2s;
            color: #475569;
            font-weight: 500;
        }

        .perm-check-input:checked+.perm-pill {
            background: var(--sidebar-bg);
            color: #fff;
            border-color: var(--sidebar-bg);
        }

        .perm-check-input {
            display: none;
        }

        .tree-table th {
            background-color: var(--sidebar-bg);
            color: white;
            font-size: 13px;
            font-weight: 600;
            padding: 12px 15px;
            border: none;
        }

        .tree-table td {
            font-size: 13px;
            vertical-align: middle;
            padding: 12px 15px;
        }

        .clickable-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .clickable-row:hover {
            background-color: #f1f5f9;
        }

        .mobile-audit-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 15px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        }

        .dt-buttons {
            display: none !important;
        }

        @media (max-width: 767.98px) {

            .table-responsive-desktop,
            .dataTables_wrapper .row:first-child {
                display: none;
            }
        }

        .action-link {
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .action-link:hover {
            text-decoration: underline;
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--sidebar-bg);"><i class="fas fa-shield-alt text-primary me-2"></i>
                    Role & Access Matrix</h4>
                <p class="text-secondary small mb-0">Assign batch permissions, scoped access, and time constraints.</p>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button class="btn text-white px-4 py-2 shadow-sm fw-semibold secured-item"
                    data-permission="role_manager_edit" id="saveBatchPowersBtn"
                    style="background-color:var(--sidebar-bg); border-radius: 8px;">
                    <i class="fas fa-save me-1"></i> Grant & Sync Permissions
                </button>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <div class="premium-card">
                    <div class="premium-header">
                        <h6 class="fw-bold text-dark mb-0"><i class="fas fa-crosshairs text-primary me-2"></i>1. Select
                            Targets (Employees)</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-2 col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-bold text-secondary mb-0">Companies</label>
                                    <div><a class="text-primary action-link select-all" data-target="#scopeCompany">All</a>
                                        <span class="text-muted px-1">|</span> <a class="text-danger action-link clear-all"
                                            data-target="#scopeCompany">Clear</a>
                                    </div>
                                </div>
                                <select class="form-select multi-select-field" id="scopeCompany"
                                    multiple="multiple"></select>
                            </div>
                            <div class="col-md-2 col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-bold text-secondary mb-0">Branches</label>
                                    <div><a class="text-primary action-link select-all" data-target="#scopeBranch">All</a>
                                        <span class="text-muted px-1">|</span> <a class="text-danger action-link clear-all"
                                            data-target="#scopeBranch">Clear</a>
                                    </div>
                                </div>
                                <select class="form-select multi-select-field" id="scopeBranch"
                                    multiple="multiple"></select>
                            </div>
                            <div class="col-md-2 col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-bold text-secondary mb-0">Departments</label>
                                    <div><a class="text-primary action-link select-all"
                                            data-target="#scopeDepartment">All</a> <span class="text-muted px-1">|</span> <a
                                            class="text-danger action-link clear-all"
                                            data-target="#scopeDepartment">Clear</a></div>
                                </div>
                                <select class="form-select multi-select-field" id="scopeDepartment"
                                    multiple="multiple"></select>
                            </div>
                            <div class="col-md-2 col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-bold text-secondary mb-0">Designations</label>
                                    <div><a class="text-primary action-link select-all"
                                            data-target="#scopeDesignation">All</a> <span class="text-muted px-1">|</span>
                                        <a class="text-danger action-link clear-all"
                                            data-target="#scopeDesignation">Clear</a>
                                    </div>
                                </div>
                                <select class="form-select multi-select-field" id="scopeDesignation"
                                    multiple="multiple"></select>
                            </div>
                            <div class="col-md-4 col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-bold text-secondary mb-0">Target Employees <span
                                            class="text-danger">*</span></label>
                                    <div><a class="text-primary action-link select-all" data-target="#scopeEmployee">All</a>
                                        <span class="text-muted px-1">|</span> <a class="text-danger action-link clear-all"
                                            data-target="#scopeEmployee">Clear</a>
                                    </div>
                                </div>
                                <select class="form-select multi-select-field border-primary" id="scopeEmployee"
                                    multiple="multiple"></select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="premium-card mb-4">
                    <div class="premium-header">
                        <h6 class="fw-bold text-dark mb-0"><i class="fas fa-clock text-danger me-2"></i>2. Time & Date Rules
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2 mb-3">
                            <div class="col-6"><label class="form-label small text-muted fw-bold mb-1">Start
                                    Date</label><input type="date" class="form-control form-control-sm"
                                    id="guardStartDate"></div>
                            <div class="col-6"><label class="form-label small text-muted fw-bold mb-1">End
                                    Date</label><input type="date" class="form-control form-control-sm"
                                    id="guardEndDate"></div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6"><label class="form-label small text-muted fw-bold mb-1">Shift
                                    Start</label><input type="time" class="form-control form-control-sm"
                                    id="guardStartTime"></div>
                            <div class="col-6"><label class="form-label small text-muted fw-bold mb-1">Shift
                                    End</label><input type="time" class="form-control form-control-sm"
                                    id="guardEndTime"></div>
                        </div>
                    </div>
                </div>
                <div class="premium-card">
                    <div class="premium-header">
                        <h6 class="fw-bold text-dark mb-0"><i class="fas fa-id-badge text-success me-2"></i>3. System
                            Roles</h6>
                    </div>
                    <div class="card-body p-3" id="rolesContainer">
                        <div class="text-muted small"><i class="fas fa-spinner fa-spin me-1"></i> Loading roles...</div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="premium-card h-100">
                    <div class="premium-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0"><i class="fas fa-tasks text-warning me-2"></i>4. Custom Action
                            Matrix</h6>
                        <button class="btn btn-sm btn-outline-secondary py-1" onclick="toggleGlobalCheckboxes()"><i
                                class="fas fa-check-double me-1"></i> Check All</button>
                    </div>
                    <div class="card-body p-4 overflow-auto" id="permissionsContainer" style="max-height: 440px;">
                        <div class="text-center text-muted py-5"><i
                                class="fas fa-circle-notch fa-spin fa-2x mb-2 text-primary"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-4">
                <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-list-alt text-primary me-2"></i>Assigned Permissions
                    Log</h5>
                <div class="premium-card overflow-hidden">
                    <div class="table-responsive-desktop">
                        <table id="auditTable" class="table table-hover tree-table mb-0 w-100 align-middle">
                            <thead>
                                <tr>
                                    <th width="5%"></th>
                                    <th>Employee Specs</th>
                                    <th>Assigned Scope (C/B/D)</th>
                                    <th>Date/Time Rules</th>
                                    <th>Roles</th>
                                    <th class="text-center" width="10%">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="d-md-none p-3 bg-light" id="mobileCardsContainer">
                        <div class="text-center py-4 text-muted small"><i class="fas fa-spinner fa-spin me-2"></i>Loading
                            Logs...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>  
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let rawEmployeesDataset = [];
        let auditTableInstance;

        $(document).ready(function() {
            $('.multi-select-field').select2({
                width: '100%',
                placeholder: 'Select options...'
            });

            // SELECT ALL / CLEAR ALL LOGIC
            $('.select-all').on('click', function(e) {
                e.preventDefault();
                let target = $($(this).data('target'));
                target.find('option').prop('selected', true);
                target.trigger('change');
            });

            $('.clear-all').on('click', function(e) {
                e.preventDefault();
                let target = $($(this).data('target'));
                target.val(null).trigger('change');
            });

            loadEcosystemData();

            function loadEcosystemData() {
                // (Company, Dept, Designation wale AJAX calls wahi rahenge jo pehle the)
                $.ajax({ url: '/api/v1/get-active-companies', type: 'GET', success: function(res) { let html = ''; res.data.forEach(c => html += `<option value="${c.id}">${c.company_name}</option>`); $('#scopeCompany').html(html); }});
                $.ajax({ url: '/api/v1/get-active-departments', type: 'GET', success: function(res) { let html = ''; res.data.forEach(d => html += `<option value="${d.id}">${d.department_name}</option>`); $('#scopeDepartment').html(html); }});
                $.ajax({ url: '/api/v1/designations', type: 'GET', success: function(res) { let html = ''; let data = res.data || res; data.forEach(ds => html += `<option value="${ds.id}">${ds.designation_name}</option>`); $('#scopeDesignation').html(html); }});

                // 🔥 NAYA: FETCH TREE & RENDER RECURSIVELY 🔥
                $.ajax({
                    url: '/api/v1/role-manager/roles-permissions',
                    type: 'GET',
                    success: function(res) {
                        let rolesHtml = '';
                        res.data.roles.forEach(r => {
                            rolesHtml += `<div class="form-check mb-2"><input class="form-check-input role-checkbox" type="checkbox" value="${r.name}" id="r_${r.id}"><label class="form-check-label small fw-bold" for="r_${r.id}">${r.name}</label></div>`;
                        });
                        $('#rolesContainer').html(rolesHtml);

                        // 2. Render N-Level Permissions Tree
                        let treeHtml = buildPermissionTreeHtml(res.data.module_tree, 0);
                        $('#permissionsContainer').html(treeHtml);
                    }
                });

                refreshEmployeeTable();
            }

            // ==========================================
            // 🔥 NAYA: RECURSIVE HTML BUILDER (TREE VIEW)
            // ==========================================
            function buildPermissionTreeHtml(modules, depth = 0) {
                let html = '';
                let borderClass = depth === 0 ? 'border-bottom mb-3 pb-2' : 'mt-2 border-start border-2 border-primary ps-3 ms-2';

                modules.forEach(mod => {
                    let hasChildren = mod.children && mod.children.length > 0;
                    let hasPerms = mod.permissions && mod.permissions.length > 0;

                    html += `<div class="module-node ${borderClass}">`;
                    html += `<div class="d-flex align-items-center mb-2">`;
                    if (hasPerms || hasChildren) {
                        html += `<input class="form-check-input shadow-sm border-secondary me-2 module-parent-cb child-of-${mod.parent_id || 'root'}" type="checkbox" data-id="${mod.id}" data-parent-id="${mod.parent_id || 'root'}" id="mod_${mod.id}">`;
                    } else {
                        html += `<i class="fas fa-circle text-muted" style="font-size: 6px; margin-right: 12px; margin-left: 6px;"></i>`;
                    }
                    html += `<label class="fw-bold ${depth === 0 ? 'text-uppercase text-primary fs-6' : 'text-dark'}" for="mod_${mod.id}" style="cursor:pointer;">${mod.module_name}</label></div>`;

                    if (hasPerms) {
                        html += `<div class="d-flex flex-wrap gap-2 ms-4 mb-3">`;
                        mod.permissions.forEach(p => {
                            let label = p.name.replace(mod.permission_base + '_', '').replace('_', ' ');
                            html += `<label><input type="checkbox" class="perm-check-input perm-checkbox child-of-${mod.id}" data-parent-id="${mod.id}" value="${p.name}"><span class="perm-pill">${label}</span></label>`;
                        });
                        html += `</div>`;
                    }

                    if (hasChildren) {
                        html += `<div class="sub-modules-container child-container-of-${mod.id}" data-parent-id="${mod.id}">`;
                        html += buildPermissionTreeHtml(mod.children, depth + 1);
                        html += `</div>`;
                    }
                    html += `</div>`;
                });
                return html;
            }

            // ==========================================
            // 🔥 NAYA: PARENT-CHILD CHECKBOX SYNC LOGIC
            // ==========================================
            $(document).on('change', '.module-parent-cb', function() {
                let modId = $(this).data('id');
                let isChecked = $(this).prop('checked');
                $(`.child-of-${modId}`).prop('checked', isChecked);
                $(`.child-container-of-${modId} input[type="checkbox"]`).prop('checked', isChecked);
                updateParentStatus($(this)); 
            });

            $(document).on('change', '.perm-checkbox', function() {
                updateParentStatus($(this));
            });

            function updateParentStatus(element) {
                let parentId = element.data('parent-id');
                if (!parentId || parentId === 'root') return;

                let allSiblings = $(`.child-of-${parentId}, .child-container-of-${parentId} > .module-node > div > .module-parent-cb`);
                let checkedSiblings = allSiblings.filter(':checked');
                let parentCb = $(`#mod_${parentId}`);
                
                parentCb.prop('checked', checkedSiblings.length > 0);
                if(parentCb.length) updateParentStatus(parentCb);
            }


            function refreshEmployeeTable() {
                $.ajax({
                    url: '/api/v1/role-manager/users',
                    type: 'GET',
                    success: function(res) {
                        rawEmployeesDataset = res.data || [];
                        evaluateTargetEmployeeSelectionStream();
                        initializeDataTable(rawEmployeesDataset);
                    }
                });
            }

            $('#scopeCompany').on('change', function() {
                let companies = $(this).val() || [];
                if (companies.length === 0) {
                    $('#scopeBranch').html('').trigger('change');
                    return;
                }

                $.ajax({
                    url: '/api/v1/branches',
                    type: 'GET',
                    success: function(res) {
                        let html = '';
                        res.data.forEach(b => {
                            if (companies.includes(String(b.company_id))) {
                                html += `<option value="${b.id}">${b.branch_name}</option>`;
                            }
                        });
                        $('#scopeBranch').html(html).trigger('change');
                    }
                });
            });

            $('#scopeCompany, #scopeBranch, #scopeDepartment, #scopeDesignation').on('change', function() {
                evaluateTargetEmployeeSelectionStream();
            });

            function evaluateTargetEmployeeSelectionStream() {
                let cmp = $('#scopeCompany').val() || [];
                let brs = $('#scopeBranch').val() || [];
                let dps = $('#scopeDepartment').val() || [];
                let dgs = $('#scopeDesignation').val() || [];

                let optionsHtml = '';

                rawEmployeesDataset.forEach(emp => {
                    let matchComp = cmp.length === 0 || cmp.includes(String(emp.company_id || ''));
                    let matchBranch = brs.length === 0 || brs.includes(String(emp.branch_id || ''));
                    let matchDept = dps.length === 0 || dps.includes(String(emp.department_id || ''));
                    let matchDesig = dgs.length === 0 || dgs.includes(String(emp.designation_id || ''));

                    if (matchComp && matchBranch && matchDept && matchDesig) {
                        let name = emp.name || emp.full_name || emp.employee_name || 'No Name';
                        let code = emp.email || emp.member_id || emp.employee_code || emp.id;
                        optionsHtml += `<option value="${emp.id}">${name} (${code})</option>`;
                    }
                });
                $('#scopeEmployee').html(optionsHtml).trigger('change');
            }

       function initializeDataTable(dataset) {
                if (auditTableInstance) {
                    auditTableInstance.clear().rows.add(dataset).draw();
                    return;
                }

                auditTableInstance = $('#auditTable').DataTable({
                    data: dataset,
                    pageLength: 10,
                    // 🔥 EXCEL BUTTON SETTINGS 🔥
                    dom: '<"row align-items-center mb-3"<"col-md-4"l><"col-md-4 text-center"B><"col-md-4"f>>rt<"row align-items-center mt-3"<"col-md-6"i><"col-md-6"p>>',
                    buttons: [
                        {
                            extend: 'excelHtml5',
                            className: 'btn btn-success btn-sm fw-bold',
                            text: '<i class="fas fa-file-excel"></i> Export to Excel',
                            title: 'Role & Permission Audit Matrix'
                        }
                    ],
                    columns: [{
                            data: null,
                            orderable: false,
                            className: 'text-center text-primary toggle-child-row',
                            render: () => `<i class="fas fa-chevron-right"></i>`
                        },
                        {
                            data: null,
                            render: d => `<div class="fw-bold text-dark">${d.name || d.full_name || d.employee_name || '-'}</div><small class="text-muted font-monospace">${d.email || d.member_id || d.employee_code || 'ID:' + d.id}</small>`
                        },
                        {
                            data: null,
                            render: d => `<span class="badge bg-primary-subtle text-primary border">C:${d.company_id || '*'}</span> <span class="badge bg-secondary-subtle text-secondary border">B:${d.branch_id || '*'}</span>`
                        },
                        {
                            data: null,
                            render: d => {
                                let dates = (d.access_start_date) ? `${d.access_start_date} to ${d.access_end_date||'inf'}` : 'Lifetime';
                                let time = (d.daily_start_time) ? `<br><small class="text-danger">${d.daily_start_time} - ${d.daily_end_time}</small>` : '';
                                return `<div class="small fw-semibold">${dates}${time}</div>`;
                            }
                        },
                        {
                            data: 'roles',
                            render: r => (r && r.length) ? r.map(x => `<span class="badge bg-light text-dark border me-1">${x.name}</span>`).join('') : '<small class="text-muted">None</small>'
                        },
                        {
                            data: 'id',
                            className: 'text-center text-nowrap',
                            render: (id, type, row) => `
                                <button class="btn btn-xs btn-outline-primary border me-1" onclick="editUserPermissions(${id})" title="Edit Permissions"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-xs btn-light border text-danger clear-user-btn" data-id="${id}" title="Clear All"><i class="fas fa-times"></i> Clear</button>
                            `
                        }
                    ],
                    order: [[1, 'asc']],
                    drawCallback: function() {
                        let pageData = this.api().rows({ page: 'current' }).data().toArray();
                        renderMobileCards(pageData);
                        if (typeof window.applyPermissions === 'function') window.applyPermissions();
                    }
                });

                // 🔥 NAYA: MODULE WISE GROUPING LOGIC 🔥
                $('#auditTable tbody').on('click', 'td.toggle-child-row', function() {
                    let tr = $(this).closest('tr');
                    let row = auditTableInstance.row(tr);
                    let icon = $(this).find('i');

                    if (row.child.isShown()) {
                        row.child.hide();
                        tr.removeClass('shown');
                        icon.removeClass('fa-chevron-down text-warning').addClass('fa-chevron-right text-primary');
                    } else {
                        let d = row.data();
                        let perms = d.permissions || [];
                        let permsHtml = '';

                        if (perms.length === 0) {
                            permsHtml = '<small class="text-muted">No custom permissions assigned.</small>';
                        } else {
                            // Group by prefix (Module name)
                            let grouped = {};
                            perms.forEach(p => {
                                let lastUnderscore = p.name.lastIndexOf('_');
                                let prefix = p.name.substring(0, lastUnderscore); // e.g., 'dashboard'
                                let action = p.name.substring(lastUnderscore + 1); // e.g., 'view'
                                if(!grouped[prefix]) grouped[prefix] = [];
                                grouped[prefix].push({ full: p.name, action: action });
                            });

                            permsHtml += '<div class="row g-3">';
                            for (const [prefix, actions] of Object.entries(grouped)) {
                                let moduleName = prefix.replace(/_/g, ' ').toUpperCase();
                                
                                permsHtml += `
                                <div class="col-md-6 col-lg-4">
                                    <div class="border rounded p-2 bg-white shadow-sm h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1">
                                            <strong class="text-primary small">${moduleName}</strong>
                                            <button class="btn btn-xs btn-outline-danger py-0 revoke-module-btn" data-uid="${d.id}" data-prefix="${prefix}" title="Clear this entire module"><i class="fas fa-trash-alt"></i> Clear</button>
                                        </div>
                                        <div class="d-flex flex-wrap gap-1">`;
                                
                                actions.forEach(act => {
                                    permsHtml += `<span class="badge bg-success-subtle text-success border align-items-center" style="display:inline-flex;">
                                        ${act.action.toUpperCase()} 
                                        <i class="fas fa-times ms-2 text-danger revoke-single-perm" style="cursor:pointer;" data-uid="${d.id}" data-perm="${act.full}"></i>
                                    </span>`;
                                });
                                permsHtml += `</div></div></div>`;
                            }
                            permsHtml += '</div>';
                        }
                        
                        row.child(`<div class="p-3 bg-light rounded shadow-sm border"><h6 class="small fw-bold text-uppercase text-secondary mb-2">Custom Permissions Map (Grouped by Module):</h6>${permsHtml}</div>`).show();
                        tr.addClass('shown');
                        icon.removeClass('fa-chevron-right text-primary').addClass('fa-chevron-down text-warning');
                    }
                });
            }

            function renderMobileCards(dataset) {
                let html = '';
                if (dataset.length === 0) {
                    html = '<div class="text-center p-3 text-muted">No records found.</div>';
                }

                dataset.forEach(d => {
                    let name = d.name || d.full_name || d.employee_name || 'Unknown';
                    let code = d.email || d.member_id || d.employee_code || d.id;
                    let roles = (d.roles && d.roles.length) ? d.roles.map(x => `<span class="badge bg-light text-dark border me-1">${x.name}</span>`).join('') : '<small>None</small>';
                    let dates = d.access_start_date ? `${d.access_start_date} to ${d.access_end_date||'inf'}` : 'Lifetime';
                    let permsHtml = (d.permissions && d.permissions.length) ? d.permissions.map(p => `<span class="badge bg-success-subtle text-success border me-1 mb-1">${p.name}</span>`).join('') : 'None';

                    html += `
                    <div class="mobile-audit-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div><h6 class="fw-bold mb-0 text-dark">${name}</h6><small class="text-muted font-monospace">${code}</small></div>
                            <button class="btn btn-sm btn-light border text-danger clear-user-btn py-0 px-2 secured-item" data-permission="role_manager_delete" data-id="${d.id}"><i class="fas fa-times"></i></button>
                        </div>
                        <div class="small mb-2"><i class="fas fa-building text-muted me-1"></i> Comp: ${d.company_id||'*'} | Branch: ${d.branch_id||'*'}</div>
                        <div class="small mb-2"><i class="fas fa-clock text-danger me-1"></i> ${dates}</div>
                        <div class="mb-2"><span class="small fw-bold text-secondary">Roles:</span> ${roles}</div>
                        <div class="p-2 bg-light border rounded mt-2">
                            <span class="small fw-bold text-secondary d-block mb-1">Custom Permissions:</span>
                            ${permsHtml}
                        </div>
                    </div>`;
                });
                $('#mobileCardsContainer').html(html);
                if (typeof window.applyPermissions === 'function') window.applyPermissions();
            }

            $('#saveBatchPowersBtn').on('click', function() {
                let targets = $('#scopeEmployee').val();
                if (!targets || targets.length === 0) {
                    Swal.fire('Empty Target', 'Please select at least one employee from the list.', 'warning');
                    return;
                }

                let roles = [];
                $('.role-checkbox:checked').each(function() { roles.push($(this).val()); });
                let perms = [];
                $('.perm-checkbox:checked').each(function() { perms.push($(this).val()); });

                let btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

                $.ajax({
                    url: '/api/v1/role-manager/assign',
                    type: 'POST',
                    data: {
                        user_ids: targets,
                        roles: roles,
                        permissions: perms,
                        access_start_date: $('#guardStartDate').val(),
                        access_end_date: $('#guardEndDate').val(),
                        daily_start_time: $('#guardStartTime').val(),
                        daily_end_time: $('#guardEndTime').val()
                    },
                    success: function() {
                        Swal.fire('Success', 'Permissions synced successfully.', 'success');
                        
                        // 🔥 NAYA: FORM RESET LOGIC 🔥
                        // 1. Saare Select2 Dropdowns ko khali karo
                        $('#scopeCompany, #scopeBranch, #scopeDepartment, #scopeDesignation, #scopeEmployee').val(null).trigger('change');
                        
                        // 2. Date aur Time ke dabbon ko khali karo
                        $('#guardStartDate, #guardEndDate, #guardStartTime, #guardEndTime').val('');
                        
                        // 3. Roles aur Permissions ke Checkboxes se tick hatao
                        $('.role-checkbox, .perm-check-input, .module-parent-cb').prop('checked', false);

                        // Table ko refresh karo naye data ke sath
                        refreshEmployeeTable();
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Grant & Sync Permissions');
                    }
                });
            });

            $(document).on('click', '.clear-user-btn', function() {
                let id = $(this).data('id');
                Swal.fire({
                    title: 'Clear Permissions?',
                    text: 'Remove all custom access?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, Clear'
                }).then((res) => {
                    if (res.isConfirmed) {
                        $.ajax({
                            url: '/api/v1/role-manager/assign',
                            type: 'POST',
                            data: { user_ids: [id], roles: [], permissions: [] },
                            success: function() {
                                Swal.fire('Cleared', 'User reset to default.', 'success');
                                refreshEmployeeTable();
                            }
                        });
                    }
                });
            });


            // 🔥 NAYA: Edit Mode - Matrix me user ka data load karo
            window.editUserPermissions = function(userId) {
                let user = rawEmployeesDataset.find(u => u.id === userId);
                if (!user) return;

                // Reset all checkboxes first
                $('.perm-check-input, .module-parent-cb, .role-checkbox').prop('checked', false);

                // Auto-select user in dropdown
                $('#scopeEmployee').val([userId]).trigger('change');

                // Tick assigned roles
                if (user.roles) {
                    user.roles.forEach(r => $(`input.role-checkbox[value="${r.name}"]`).prop('checked', true));
                }

                // Tick assigned permissions and bubble up to parents
                if (user.permissions) {
                    user.permissions.forEach(p => {
                        let cb = $(`input.perm-checkbox[value="${p.name}"]`);
                        if (cb.length) {
                            cb.prop('checked', true);
                            updateParentStatus(cb);
                        }
                    });
                }

                // Set Time limits
                $('#guardStartDate').val(user.access_start_date || '');
                $('#guardEndDate').val(user.access_end_date || '');
                $('#guardStartTime').val(user.access_start_date ? user.daily_start_time : '');
                $('#guardEndTime').val(user.access_end_date ? user.daily_end_time : '');

                // Change Save button to Update Mode
                $('#saveBatchPowersBtn')
                    .html('<i class="fas fa-sync-alt me-1"></i> Update Specific User')
                    .data('mode', 'sync') // Set flag to sync
                    .css('background-color', '#D69E2E'); // Warning/Gold color

                // Scroll up smoothly
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            // 🔥 UPDATED: Save Button Logic
            $('#saveBatchPowersBtn').on('click', function() {
                let targets = $('#scopeEmployee').val();
                if (!targets || targets.length === 0) {
                    Swal.fire('Empty Target', 'Please select at least one employee from the list.', 'warning');
                    return;
                }

                let roles = [];
                $('.role-checkbox:checked').each(function() { roles.push($(this).val()); });
                let perms = [];
                $('.perm-checkbox:checked').each(function() { perms.push($(this).val()); });

                let btn = $(this);
                let currentMode = btn.data('mode') || 'append'; // Get mode
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

                $.ajax({
                    url: '/api/v1/role-manager/assign',
                    type: 'POST',
                    data: {
                        user_ids: targets,
                        roles: roles,
                        permissions: perms,
                        mode: currentMode, // Send mode to backend
                        access_start_date: $('#guardStartDate').val(),
                        access_end_date: $('#guardEndDate').val(),
                        daily_start_time: $('#guardStartTime').val(),
                        daily_end_time: $('#guardEndTime').val()
                    },
                   success: function() {
                        Swal.fire('Success', 'Permissions synced successfully.', 'success');
                        
                        // 🔥 NAYA: FORM RESET LOGIC 🔥
                        $('#scopeCompany, #scopeBranch, #scopeDepartment, #scopeDesignation, #scopeEmployee').val(null).trigger('change');
                        $('#guardStartDate, #guardEndDate, #guardStartTime, #guardEndTime').val('');
                        $('.role-checkbox, .perm-check-input, .module-parent-cb').prop('checked', false);

                        refreshEmployeeTable();
                    },
                    complete: function() {
                        // Reset button back to normal Grant mode
                        btn.prop('disabled', false)
                           .html('<i class="fas fa-save me-1"></i> Grant & Sync Permissions')
                           .data('mode', 'append')
                           .css('background-color', 'var(--sidebar-bg)');
                    }
                });
            });

            // 🔥 UPDATED: Clear User
            $(document).on('click', '.clear-user-btn', function() {
                let id = $(this).data('id');
                Swal.fire({
                    title: 'Clear Permissions?',
                    text: 'Remove all custom access?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, Clear'
                }).then((res) => {
                    if (res.isConfirmed) {
                        $.ajax({
                            url: '/api/v1/role-manager/clear',
                            type: 'POST',
                            data: { user_id: id },
                            success: function() {
                                Swal.fire('Cleared', 'User reset to default.', 'success');
                                refreshEmployeeTable();
                            }
                        });
                    }
                });
            });

            // 🔥 NAYA: Single Permission Delete (The 'X' inside badge)
            $(document).on('click', '.revoke-single-perm', function() {
                let uid = $(this).data('uid');
                let perm = $(this).data('perm');
                
                Swal.fire({
                    title: 'Remove Permission?',
                    text: `Are you sure you want to remove '${perm}'?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, Remove'
                }).then((res) => {
                    if (res.isConfirmed) {
                        $.ajax({
                            url: '/api/v1/role-manager/revoke-permission',
                            type: 'POST',
                            data: { user_id: uid, permission: perm },
                            success: function() {
                                refreshEmployeeTable(); // Auto reload table
                            }
                        });
                    }
                });
            });

// 🔥 NAYA: Revoke Entire Module Permissions
            $(document).on('click', '.revoke-module-btn', function() {
                let uid = $(this).data('uid');
                let prefix = $(this).data('prefix');
                let moduleName = prefix.replace(/_/g, ' ').toUpperCase();
                
                Swal.fire({
                    title: 'Clear Module?',
                    text: `Are you sure you want to remove ALL permissions for '${moduleName}'?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, Clear Module'
                }).then((res) => {
                    if (res.isConfirmed) {
                        $.ajax({
                            url: '/api/v1/role-manager/revoke-module',
                            type: 'POST',
                            data: { user_id: uid, module_prefix: prefix },
                            success: function() {
                                refreshEmployeeTable(); 
                            }
                        });
                    }
                });
            });




        });

        function toggleGlobalCheckboxes() {
            // Updated to select both actions and parent modules
            let state = $('.perm-check-input, .module-parent-cb').length === $('.perm-check-input:checked, .module-parent-cb:checked').length;
            $('.perm-check-input, .module-parent-cb').prop('checked', !state);
        }
    </script>
@endpush
