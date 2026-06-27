@extends('layout.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px !important;
            font-size: 13px !important;
            border: 1px solid #ced4da !important;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            background-color: #1A365D !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 50rem !important;
            padding: 2px 10px !important;
            font-size: 11.5px !important;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
            color: #ffffff !important;
            margin-right: 5px !important;
        }

        .filter-actions a {
            cursor: pointer;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
            transition: 0.2s;
            user-select: none;
        }

        .filter-actions a:hover {
            opacity: 0.7;
        }

        /* Bulk Action Bar */
        .bulk-action-bar {
            background: #fff;
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 767.98px) {
            .bulk-action-bar.mobile-floating {
                position: fixed;
                bottom: 80px;
                left: 5%;
                right: 5%;
                z-index: 1050;
                box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.15);
                border-radius: 12px;
                border: 2px solid var(--brand-primary);
            }
        }

        .data-row {
            transition: all 0.2s;
        }
    </style>

    <div class="container-fluid mt-4">
        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
            <div>
                <h4 class="mb-0 fw-bold text-primary"><i class="fas fa-robot me-2"></i>Auto-Task Rules</h4>
                <p class="text-muted small mb-0">Manage automatic daily task assignment rules.</p>
            </div>

            <div class="d-flex w-100 w-md-auto gap-2">
                <div class="input-group input-group-sm flex-grow-1" style="max-width: 250px;">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="liveSearch" class="form-control border-start-0 shadow-none"
                        placeholder="Search rules...">
                </div>
                <button class="btn btn-sm btn-success shadow-sm" id="exportExcelBtn" title="Download Excel">
                    <i class="fas fa-file-excel"></i> <span class="d-none d-md-inline ms-1">Excel</span>
                </button>
                <button class="btn btn-sm btn-primary shadow-sm text-nowrap" data-bs-toggle="modal"
                    data-bs-target="#addRuleModal">
                    <i class="fas fa-plus"></i> <span class="d-none d-md-inline ms-1">New Rule</span>
                </button>
            </div>
        </div>

        <div id="bulkActionBar" class="bulk-action-bar mobile-floating d-none">
            <div class="fw-bold text-primary"><span id="selectedCount">0</span> Selected</div>
            <div class="ms-auto">
                <button class="btn btn-sm btn-outline-secondary me-2" id="selectAllBtn">Select All</button>
                <button class="btn btn-sm btn-danger shadow-sm" id="deleteSelectedBtn">
                    <i class="fas fa-trash"></i> <span class="d-none d-sm-inline">Delete</span>
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-3 d-none d-md-block">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="rulesTable">
                        <thead class="bg-light text-secondary" style="font-size: 13px; text-transform: uppercase;">
                            <tr>
                                <th class="ps-4" style="width: 50px;">
                                    <input type="checkbox" class="form-check-input shadow-none" id="masterCheckbox">
                                </th>
                                <th>Rule Title (Task Name)</th>
                                <th>Assignee</th>
                                <th>Type & Target</th>
                                <th>Execution Time</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="rulesTableBody" style="font-size: 14px;">
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <div class="spinner-border spinner-border-sm me-2"></div> Loading...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="mobileCardsContainer" class="d-block d-md-none">
        </div>
    </div>

    <div class="modal fade" id="addRuleModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold text-dark"><i class="fas fa-magic text-primary me-2"></i> Create
                        Auto-Task Rules</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="autoTaskForm">
                        <div class="row g-3 bg-light p-3 rounded border mb-4 shadow-sm">
                            <div class="col-md-3">
                                <label class="small fw-bold text-dark mb-1">Target User Type *</label>
                                <select name="assignee_type" id="assigneeTypeSelect" class="form-select shadow-none"
                                    required>
                                    <option value="App\Models\Employee" selected>Employee / Staff</option>
                                    <option value="App\Models\Member">Member / Associate</option>
                                </select>
                            </div>
                            <div class="col-md-3 position-relative">
                                <div class="d-flex justify-content-between align-items-end mb-1">
                                    <label class="small fw-bold text-dark mb-0">Companies</label>
                                    <div class="filter-actions"><a class="text-primary btn-all"
                                            data-target="companySelect">All</a> | <a class="text-danger btn-clear"
                                            data-target="companySelect">Clear</a></div>
                                </div>
                                <select class="select2-multiple" id="companySelect" multiple
                                    data-placeholder="Filter by Company..." style="width: 100%;"></select>
                            </div>
                            <div class="col-md-3 position-relative">
                                <div class="d-flex justify-content-between align-items-end mb-1">
                                    <label class="small fw-bold text-dark mb-0">Branches / HO</label>
                                    <div class="filter-actions"><a class="text-primary btn-all"
                                            data-target="branchSelect">All</a> | <a class="text-danger btn-clear"
                                            data-target="branchSelect">Clear</a></div>
                                </div>
                                <select class="select2-multiple" id="branchSelect" multiple
                                    data-placeholder="Filter by Branch..." style="width: 100%;"></select>
                            </div>
                            <div class="col-md-3 position-relative">
                                <div class="d-flex justify-content-between align-items-end mb-1">
                                    <label class="small fw-bold text-dark mb-0">Departments</label>
                                    <div class="filter-actions"><a class="text-primary btn-all"
                                            data-target="deptSelect">All</a> | <a class="text-danger btn-clear"
                                            data-target="deptSelect">Clear</a></div>
                                </div>
                                <select class="select2-multiple" id="deptSelect" multiple
                                    data-placeholder="Filter by Dept..." style="width: 100%;"></select>
                            </div>
                            <div class="col-md-4 position-relative">
                                <div class="d-flex justify-content-between align-items-end mb-1">
                                    <label class="small fw-bold text-dark mb-0">Designations</label>
                                    <div class="filter-actions"><a class="text-primary btn-all"
                                            data-target="desigSelect">All</a> | <a class="text-danger btn-clear"
                                            data-target="desigSelect">Clear</a></div>
                                </div>
                                <select class="select2-multiple" id="desigSelect" multiple
                                    data-placeholder="Filter by Role..." style="width: 100%;"></select>
                            </div>
                            <div class="col-md-8 position-relative">
                                <div class="d-flex justify-content-between align-items-end mb-1">
                                    <label class="small fw-bold text-danger mb-0">Select Target Assignees *</label>
                                    <div class="filter-actions"><a class="text-primary btn-all"
                                            data-target="userSelect">SELECT ALL</a> | <a class="text-danger btn-clear"
                                            data-target="userSelect">CLEAR ALL</a></div>
                                </div>
                                <select class="select2-multiple" name="assignee_ids[]" id="userSelect" multiple
                                    data-placeholder="Filtered users will appear here..." style="width: 100%;"
                                    required></select>
                            </div>
                        </div>

                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="fas fa-cogs"></i> Task Rule
                            Configuration</h6>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold text-muted">Task Title Template *</label>
                                <input type="text" name="title_template"
                                    class="form-control shadow-none border-primary"
                                    placeholder="e.g. Complete Today's Calling" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">Daily Execution Time *</label>
                                <input type="time" name="run_time" class="form-control shadow-none border-primary"
                                    value="09:00" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted">Task Type</label>
                                <select name="task_type" id="taskTypeSelect" class="form-select shadow-none border-info">
                                    <option value="manual">Manual Task</option>
                                    <option value="target">Target Based Work</option>
                                </select>
                            </div>

                            <div class="col-md-3 target-fields d-none">
                                <label class="form-label small fw-bold text-muted">Tracking Module</label>
                                <select name="tracking_module_id" id="trackingModuleSelect"
                                    class="form-select shadow-none tracking-module-dropdown">
                                    <option value="">Loading...</option>
                                </select>
                            </div>
                            <div class="col-md-3 target-fields d-none">
                                <label class="form-label small fw-bold text-muted">Linked Phase</label>
                                <select name="phase_id" id="phaseSelect" class="form-select shadow-none">
                                    <option value="">-- No Phase / General --</option>
                                </select>
                            </div>
                            <div class="col-md-3 target-fields d-none">
                                <label class="form-label small fw-bold text-muted">Daily Target Count</label>
                                <input type="number" name="daily_target_count" class="form-control shadow-none"
                                    value="0" min="0">
                            </div>
                            <div class="col-md-12 target-fields d-none mt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="carry_forward_pending"
                                        id="carryForwardCheck" checked>
                                    <label class="form-check-label small fw-bold text-dark" for="carryForwardCheck">
                                        Rollover Pending (Carry forward yesterday's incomplete targets to today)
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-8 mt-3">
                                <label class="form-label small fw-bold text-muted">Additional Instructions</label>
                                <textarea name="description_template" class="form-control shadow-none" rows="2"
                                    placeholder="Write any fixed instructions here..."></textarea>
                            </div>
                            <div class="col-md-4 mt-3">
                                <label class="form-label small fw-bold text-muted">Priority</label>
                                <select name="priority" class="form-select shadow-none">
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary shadow-none" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="autoTaskForm" class="btn btn-primary shadow-sm px-4"
                        id="saveRuleBtn">Generate Rules</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editRuleModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold text-dark"><i class="fas fa-edit text-success me-2"></i> Edit Auto-Task
                        Rule</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="editRuleForm">
                        <input type="hidden" id="editRuleId">
                        <input type="hidden" name="_method" value="PUT">

                        <div class="alert alert-info py-2 px-3 mb-3 shadow-sm">
                            <small class="fw-bold">Assignee:</small> <span id="editAssigneeName"
                                class="text-dark"></span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold text-muted">Task Title Template *</label>
                                <input type="text" name="title_template" id="editTitle"
                                    class="form-control shadow-none" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">Execution Time *</label>
                                <input type="time" name="run_time" id="editTime" class="form-control shadow-none"
                                    required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted">Task Type</label>
                                <select name="task_type" id="editTaskTypeSelect" class="form-select shadow-none">
                                    <option value="manual">Manual Task</option>
                                    <option value="target">Target Based</option>
                                </select>
                            </div>

                            <div class="col-md-3 edit-target-fields d-none">
                                <label class="form-label small fw-bold text-muted">Tracking Module</label>
                                <select name="tracking_module_id" id="editTrackingModule"
                                    class="form-select shadow-none tracking-module-dropdown">
                                </select>
                            </div>
                            <div class="col-md-3 edit-target-fields d-none">
                                <label class="form-label small fw-bold text-muted">Linked Phase</label>
                                <select name="phase_id" id="editPhaseSelect"
                                    class="form-select shadow-none edit-phase-dropdown">
                                </select>
                            </div>
                            <div class="col-md-3 edit-target-fields d-none">
                                <label class="form-label small fw-bold text-muted">Daily Target</label>
                                <input type="number" name="daily_target_count" id="editTargetCount"
                                    class="form-control shadow-none" min="0">
                            </div>
                            <div class="col-md-12 edit-target-fields d-none">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="carry_forward_pending"
                                        id="editCarryForward">
                                    <label class="form-check-label small fw-bold text-dark"
                                        for="editCarryForward">Rollover Pending (Carry forward)</label>
                                </div>
                            </div>

                            <div class="col-md-8 mt-3">
                                <label class="form-label small fw-bold text-muted">Additional Instructions</label>
                                <textarea name="description_template" id="editDesc" class="form-control shadow-none" rows="2"></textarea>
                            </div>
                            <div class="col-md-4 mt-3">
                                <label class="form-label small fw-bold text-muted">Priority</label>
                                <select name="priority" id="editPriority" class="form-select shadow-none">
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary shadow-none" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="editRuleForm" class="btn btn-success shadow-sm px-4"
                        id="updateRuleBtn">Update Rule</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            let apiPrefix = '/api/v1';
            let allPhases = [];
            let globalRulesData = [];

            // --- SELECT2 & CASCADING LOGIC ---
            function initSelect2(element) {
                $(element).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $(element).parent(),
                    closeOnSelect: false
                });
            }
            $('.select2-multiple').each(function() {
                initSelect2(this);
            });

            $(document).on('click', '.btn-all', function() {
                let target = $(this).data('target');
                $('#' + target + ' > option').prop('selected', true);
                $('#' + target).trigger('change').trigger('change.select2');
            });

            $(document).on('click', '.btn-clear', function() {
                let target = $(this).data('target');
                $('#' + target).val(null).trigger('change').trigger('change.select2');
            });

            function getSelected(selectId) {
                let vals = $('#' + selectId).val();
                return (vals && vals.length > 0) ? vals.join(',') : '';
            }

            // Fetch Tracking Modules
            $.get(apiPrefix + '/tracking-modules', function(res) {
                let options = '<option value="">-- Manual Task --</option>';
                res.data.forEach(mod => {
                    options +=
                        `<option value="${mod.id}">Track '${mod.task_category_name}'</option>`;
                });
                $('.tracking-module-dropdown').html(options);
            });

            // Fetch All Phases for Dynamic Filtering
            $.get(apiPrefix + '/phases', function(res) {
                if (res.success && res.data) {
                    allPhases = res.data;
                    populatePhaseDropdown();
                }
            });

            function populatePhaseDropdown() {
                let selectedComps = $('#companySelect').val() || [];
                let selectedBranches = $('#branchSelect').val() || [];

                let html = '<option value="">-- No Phase / General --</option>';
                allPhases.forEach(p => {
                    // Agar dropdowns me kuch select kiya hai, toh filter karo, warna sab dikhao
                    let matchComp = selectedComps.length === 0 || selectedComps.includes(String(p
                        .company_id));
                    // Branch filter logic can be complex with HO_, so we keep it simple: filter by company first.
                    if (matchComp) {
                        let compName = p.company ? p.company.company_name : '';
                        let label = compName ? `${p.phase_name} (${compName})` : p.phase_name;
                        html += `<option value="${p.id}">${label}</option>`;
                    }
                });
                $('#phaseSelect').html(html);
                $('.edit-phase-dropdown').html(html); // populate edit dropdown too
            }

            // Task Type Toggles
            $('#taskTypeSelect').on('change', function() {
                if ($(this).val() === 'target') $('.target-fields').removeClass('d-none');
                else $('.target-fields').addClass('d-none');
            });
            $('#editTaskTypeSelect').on('change', function() {
                if ($(this).val() === 'target') $('.edit-target-fields').removeClass('d-none');
                else $('.edit-target-fields').addClass('d-none');
            });

            // Cascading Dependent Dropdowns
            function loadCompanies() {
                $.get(apiPrefix + '/companies', function(res) {
                    let html = '';
                    res.data.forEach(c => {
                        html +=
                            `<option value="${c.id}" data-name="${c.company_name}">${c.company_name}</option>`;
                    });
                    $('#companySelect').html(html).val(null).trigger('change.select2');
                });
            }

            $('#addRuleModal').on('shown.bs.modal', function() {
                if ($('#companySelect option').length === 0) loadCompanies();
                loadUsers();
            });

            $('#assigneeTypeSelect').on('change', function() {
                loadUsers();
            });

            $('#companySelect').on('change', function() {
                let compIds = getSelected('companySelect');
                populatePhaseDropdown(); // Filter phases
                if (!compIds) {
                    $('#branchSelect').html('').val(null).trigger('change');
                    loadUsers();
                    return;
                }

                let type = $('#assigneeTypeSelect').val() === 'App\\Models\\Employee' ? 'employee' :
                    'member';
                let html = '';
                $('#companySelect option:selected').each(function() {
                    html +=
                        `<option value="HO_${$(this).val()}">Head Office (${$(this).data('name')})</option>`;
                });
                $.get(apiPrefix + `/branches?company_ids=${compIds}&user_type=${type}&type=${type}`,
                    function(res) {
                        res.data.forEach(b => {
                            html += `<option value="${b.id}">${b.branch_name}</option>`;
                        });
                        $('#branchSelect').html(html).val(null).trigger('change.select2');
                        loadUsers();
                    });
            });

            $('#branchSelect').on('change', function() {
                let branchIds = getSelected('branchSelect');
                if (!branchIds) {
                    $('#deptSelect').html('').val(null).trigger('change');
                    loadUsers();
                    return;
                }
                let type = $('#assigneeTypeSelect').val() === 'App\\Models\\Employee' ? 'employee' :
                    'member';
                $.get(apiPrefix + `/departments?branch_ids=${branchIds}&user_type=${type}&type=${type}`,
                    function(res) {
                        let html = '';
                        res.data.forEach(d => {
                            html += `<option value="${d.id}">${d.department_name}</option>`;
                        });
                        $('#deptSelect').html(html).val(null).trigger('change.select2');
                        loadUsers();
                    });
            });

            $('#deptSelect').on('change', function() {
                let deptIds = getSelected('deptSelect');
                if (!deptIds) {
                    $('#desigSelect').html('').val(null).trigger('change');
                    loadUsers();
                    return;
                }
                let type = $('#assigneeTypeSelect').val() === 'App\\Models\\Employee' ? 'employee' :
                    'member';
                $.get(apiPrefix + `/designations?department_ids=${deptIds}&user_type=${type}&type=${type}`,
                    function(res) {
                        let html = '';
                        res.data.forEach(d => {
                            html += `<option value="${d.id}">${d.designation_name}</option>`;
                        });
                        $('#desigSelect').html(html).val(null).trigger('change.select2');
                        loadUsers();
                    });
            });

            $('#desigSelect').on('change', function() {
                loadUsers();
            });

            let isFetchingUsers = false;

            function loadUsers() {
                if (isFetchingUsers) return;
                let type = $('#assigneeTypeSelect').val();
                let isStaff = type === 'App\\Models\\Employee';
                let apiUrl = isStaff ? apiPrefix + '/employees' : apiPrefix + '/members';

                let params = {
                    length: -1,
                    company_ids: getSelected('companySelect'),
                    branch_ids: getSelected('branchSelect'),
                    department_ids: getSelected('deptSelect'),
                    designation_ids: getSelected('desigSelect'),
                    user_type: isStaff ? 'employee' : 'member',
                    status: 'active'
                };

                isFetchingUsers = true;
                $.get(apiUrl, params, function(res) {
                    let html = '';
                    res.data.forEach(user => {
                        let name = user.full_name || user.member_name || user.name || 'Unknown';
                        let idCode = user.member_id ? user.member_id : 'N/A';
                        html += `<option value="${user.id}">${name} (${idCode})</option>`;
                    });
                    $('#userSelect').html(html).val(null).trigger('change.select2');
                    isFetchingUsers = false;
                });
            }

            // --- DATA RENDERING (Table & Cards) ---
            function loadRules() {
                $.get(apiPrefix + '/auto-task-settings', function(res) {
                    let tbody = $('#rulesTableBody');
                    let mobileContainer = $('#mobileCardsContainer');
                    tbody.empty();
                    mobileContainer.empty();

                    if (res.data && res.data.length > 0) {
                        globalRulesData = res.data;
                        res.data.forEach(rule => {
                            let isTarget = rule.daily_target_count > 0;
                            let typeBadge = isTarget ?
                                '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary">Target Based</span>' :
                                '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">Manual Task</span>';
                            let targetText = isTarget ?
                                `Target: ${rule.daily_target_count} <br><small class="text-muted"><i class="fas fa-building"></i> ${rule.phase ? rule.phase.phase_name : 'No Phase'}</small>` :
                                '-';
                            let assigneeName = rule.assignee ? (rule.assignee.full_name || rule
                                .assignee.member_name) : 'Unknown User';

                            let toggleSwitch =
                                `<div class="form-check form-switch"><input class="form-check-input status-toggle" type="checkbox" data-id="${rule.id}" ${rule.is_active ? 'checked' : ''}></div>`;

                            // Desktop Table Row
                            let tr = `
                            <tr class="data-row">
                                <td class="ps-4"><input type="checkbox" class="form-check-input row-checkbox shadow-none" value="${rule.id}"></td>
                                <td class="fw-medium text-dark search-target">${rule.title_template}</td>
                                <td class="search-target fw-bold text-primary"><i class="fas fa-user-circle me-1"></i> ${assigneeName}</td>
                                <td>${typeBadge} <br> ${targetText}</td>
                                <td><i class="fas fa-clock text-warning me-1"></i> ${rule.run_time}</td>
                                <td>${toggleSwitch}</td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light text-success border edit-rule-btn me-1" data-id="${rule.id}"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-light text-danger border delete-rule-btn" data-id="${rule.id}"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        `;
                            tbody.append(tr);

                            // Mobile Card
                            let card = `
                            <div class="card shadow-sm mb-3 phase-card data-row border-0" style="border-left: 4px solid var(--brand-primary);">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-center">
                                            <input type="checkbox" class="form-check-input row-checkbox shadow-none me-2" value="${rule.id}" style="width: 18px; height: 18px;">
                                            <h6 class="mb-0 fw-bold text-dark search-target">${rule.title_template}</h6>
                                        </div>
                                        ${toggleSwitch}
                                    </div>
                                    <div class="small mb-1 search-target fw-bold text-primary"><i class="fas fa-user me-1"></i> ${assigneeName}</div>
                                    <div class="small mb-1">${typeBadge} | <i class="fas fa-clock text-warning mx-1"></i> ${rule.run_time}</div>
                                    <div class="small text-muted mb-2">${targetText}</div>
                                    <div class="text-end">
                                        <button class="btn btn-sm btn-light text-success border edit-rule-btn me-1 p-1 px-2" data-id="${rule.id}"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-light text-danger border delete-rule-btn p-1 px-2" data-id="${rule.id}"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        `;
                            mobileContainer.append(card);
                        });
                    } else {
                        tbody.html(
                            '<tr><td colspan="7" class="text-center py-4 text-muted">No auto-task rules found.</td></tr>'
                            );
                        mobileContainer.html(
                            '<div class="text-center py-4 text-muted bg-white rounded shadow-sm">No rules found.</div>'
                            );
                    }
                    updateBulkActionBar();
                });
            }
            loadRules();

            // --- BULK ACTION LOGIC ---
            $(document).on('change', '#masterCheckbox', function() {
                let isChecked = $(this).prop('checked');
                $('.data-row:visible .row-checkbox').prop('checked', isChecked);
                updateBulkActionBar();
            });

            $(document).on('change', '.row-checkbox', function() {
                updateBulkActionBar();
            });

            $('#selectAllBtn').on('click', function() {
                let allChecked = $('.data-row:visible .row-checkbox:checked').length === $(
                    '.data-row:visible .row-checkbox').length;
                $('.data-row:visible .row-checkbox').prop('checked', !allChecked);
                $('#masterCheckbox').prop('checked', !allChecked);
                updateBulkActionBar();
            });

            function updateBulkActionBar() {
                let checkedCount = $('.data-row:visible .row-checkbox:checked').length;
                $('#selectedCount').text(checkedCount);
                if (checkedCount > 0) $('#bulkActionBar').removeClass('d-none');
                else $('#bulkActionBar').addClass('d-none');
            }

            // --- BULK DELETE ---
            $('#deleteSelectedBtn').on('click', function() {
                let selectedIds = [];
                $('.data-row:visible .row-checkbox:checked').each(function() {
                    let val = $(this).val();
                    if (!selectedIds.includes(val)) selectedIds.push(val);
                });

                if (selectedIds.length === 0) return;

                Swal.fire({
                    title: 'Delete ' + selectedIds.length + ' Rules?',
                    text: 'System will stop auto-assigning these tasks.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: apiPrefix + '/bulk-delete',
                            type: 'POST',
                            data: {
                                table_name: 'auto_task_settings',
                                ids: selectedIds
                            },
                            success: function(res) {
                                Swal.fire('Deleted!', res.message, 'success');
                                loadRules();
                            }
                        });
                    }
                });
            });

            // --- LIVE SEARCH ---
            $('#liveSearch').on('keyup', function() {
                let value = $(this).val().toLowerCase();
                $('.data-row').each(function() {
                    let text = $(this).find('.search-target').text().toLowerCase();
                    if (text.indexOf(value) > -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                        $(this).find('.row-checkbox').prop('checked', false);
                    }
                });
                updateBulkActionBar();
            });

            // --- EXPORT EXCEL ---
            $('#exportExcelBtn').on('click', function() {
                let csv = "Rule Title,Assignee,Target,Time\n";
                $('.data-row:visible').each(function() {
                    if ($(this).is('tr')) {
                        let title = $(this).find('td:eq(1)').text().trim().replace(/,/g, " ");
                        let assignee = $(this).find('td:eq(2)').text().trim().replace(/,/g, " ");
                        let target = $(this).find('td:eq(3)').text().trim().replace(/,/g, " ")
                            .replace(/\n/g, " ");
                        let time = $(this).find('td:eq(4)').text().trim();
                        csv += `${title},${assignee},${target},${time}\n`;
                    }
                });
                let link = document.createElement("a");
                link.setAttribute("href", encodeURI("data:text/csv;charset=utf-8," + csv));
                link.setAttribute("download", "AutoTask_Rules.csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });

            // --- CRUD AJAX ---
            $('#autoTaskForm').on('submit', function(e) {
                e.preventDefault();

                if (!$('#userSelect').val() || $('#userSelect').val().length === 0) {
                    Swal.fire('Error',
                        'Please select at least one assignee from the Target Assignees list.', 'warning'
                        );
                    return;
                }

                let btn = $('#saveRuleBtn');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: apiPrefix + '/auto-task-settings',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(res) {
                        Swal.fire('Success', res.message, 'success');
                        $('#addRuleModal').modal('hide');
                        $('#autoTaskForm')[0].reset();
                        $('.select2-multiple').val(null).trigger('change.select2');
                        $('#taskTypeSelect').trigger('change');
                        loadRules();
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Generate Rules');
                    }
                });
            });

            $(document).on('change', '.status-toggle', function() {
                let id = $(this).data('id');
                let status = $(this).prop('checked') ? 1 : 0;
                $.post(apiPrefix + '/auto-task-settings/' + id + '/status', {
                    is_active: status
                });
            });

            $(document).on('click', '.delete-rule-btn', function() {
                let id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Rule?',
                    icon: 'warning',
                    showCancelButton: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: apiPrefix + '/auto-task-settings/' + id,
                            type: 'DELETE',
                            success: function() {
                                loadRules();
                            }
                        });
                    }
                });
            });

            // --- EDIT LOGIC ---
            $(document).on('click', '.edit-rule-btn', function() {
                let id = $(this).data('id');
                let btn = $(this);
                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

                $.get(apiPrefix + '/auto-task-settings/' + id, function(res) {
                    let d = res.data;
                    $('#editRuleId').val(d.id);
                    $('#editAssigneeName').text(d.assignee ? (d.assignee.full_name || d.assignee
                        .member_name) : 'Unknown');
                    $('#editTitle').val(d.title_template);
                    $('#editTime').val(d.run_time.substring(0, 5));
                    $('#editDesc').val(d.description_template);
                    $('#editPriority').val(d.priority);

                    let isTarget = d.daily_target_count > 0;
                    $('#editTaskTypeSelect').val(isTarget ? 'target' : 'manual').trigger('change');

                    if (isTarget) {
                        $('#editTrackingModule').val(d.tracking_module_id);
                        $('#editPhaseSelect').val(d.phase_id);
                        $('#editTargetCount').val(d.daily_target_count);
                        $('#editCarryForward').prop('checked', d.carry_forward_pending);
                    }
                    $('#editRuleModal').modal('show');
                }).always(function() {
                    btn.html('<i class="fas fa-edit"></i>').prop('disabled', false);
                });
            });

            $('#editRuleForm').on('submit', function(e) {
                e.preventDefault();
                let id = $('#editRuleId').val();
                let btn = $('#updateRuleBtn');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: apiPrefix + '/auto-task-settings/' + id,
                    type: 'POST', // POST + _method=PUT
                    data: $(this).serialize(),
                    success: function(res) {
                        Swal.fire('Success', res.message, 'success');
                        $('#editRuleModal').modal('hide');
                        loadRules();
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Update Rule');
                    }
                });
            });

        });
    </script>
@endpush
