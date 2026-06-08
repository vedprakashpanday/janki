@extends('layout.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        /* Premium Compact Select2 UI Fixes */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px !important;
            font-size: 13px !important;
            border: 1px solid #ced4da !important;
            padding-bottom: 2px !important;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            background-color: #1A365D !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 50rem !important;
            padding: 2px 10px !important;
            font-size: 11.5px !important;
            margin-top: 5px !important;
            margin-right: 5px !important;
            display: inline-flex !important;
            align-items: center !important;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
            color: rgba(255, 255, 255, 0.8) !important;
            background: transparent !important;
            border: none !important;
            margin-right: 6px !important;
            font-size: 14px !important;
            font-weight: bold !important;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #ff4d4d !important;
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

        /* Hover effect for task rows on desktop */
        .task-row-hover:hover {
            background-color: #f8f9fa !important;
            transition: all 0.2s ease-in-out;
        }

        /* Expand / Collapse Icon Animation */
        .toggle-icon {
            transition: transform 0.3s ease;
        }

        .card-header[aria-expanded="false"] .toggle-icon {
            transform: rotate(180deg);
        }

        .card-header[aria-expanded="true"] .toggle-icon {
            transform: rotate(0deg);
        }
    </style>

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-0"><i class="fas fa-tasks text-primary me-2"></i> Task Command Center</h4>
                <small class="text-muted">Manage, track, and complete your daily goals.</small>
            </div>

            @php
                $canAssignTasks = false;
                if (request()->is('admin') || request()->is('admin/*')) {
                    $canAssignTasks = true;
                } elseif (auth()->check()) {
                    $currentUser = auth()->user();
                    $email = strtolower(trim($currentUser->email ?? ''));
                    $isDev = in_array($email, [
                        'admin@jankivilla.com',
                        'superadmin@example.com',
                        'vedprakash@infoera.in',
                    ]);
                    $hasAdminRole = method_exists($currentUser, 'hasRole')
                        ? $currentUser->hasRole(['CEO', 'Director'])
                        : false;
                    $canAssignTasks = $isDev || $hasAdminRole;
                }
            @endphp

            @if ($canAssignTasks)
                <div class="d-flex gap-2">
                    <button class="btn btn-primary fw-bold shadow-sm open-assign-modal" data-type="App\Models\Employee">
                        <i class="fas fa-user-tie me-1"></i> Give Task to Staff
                    </button>
                    <button class="btn btn-warning fw-bold shadow-sm text-dark open-assign-modal"
                        data-type="App\Models\Member">
                        <i class="fas fa-users me-1"></i> Give Task to Associates
                    </button>
                </div>
            @endif
        </div>

        <div class="row mb-3">
            <div class="col-md-5 col-lg-4">
                <div class="input-group shadow-sm border border-secondary border-opacity-25 rounded-3 overflow-hidden">
                    <span class="input-group-text bg-white border-0 text-primary"><i class="fas fa-search"></i></span>
                    <input type="text" id="liveSearchTasks" class="form-control border-0 shadow-none ps-1"
                        placeholder="Search by Employee or Task Title...">
                </div>
            </div>
        </div>

        <div class="row" id="taskBoard">
            <div class="col-12 text-center py-5">
                <i class="fas fa-spinner fa-spin fa-3x text-muted mb-3"></i>
                <p class="text-muted fw-bold">Syncing latest tasks...</p>
            </div>
        </div>
    </div>

    <div class="modal fade" id="assignTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-lg-down">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="modalTitle"><i class="fas fa-bolt text-warning me-2"></i>
                        Bulk Assign</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <form id="assignTaskForm">
                        <input type="hidden" name="assignee_type" id="hiddenAssigneeType">

                        <div class="bg-light p-3 rounded border mb-4 shadow-sm">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="d-flex justify-content-between align-items-end mb-1">
                                        <label class="small fw-bold text-dark mb-0">Companies</label>
                                        <div class="filter-actions"><a class="text-primary btn-all"
                                                data-target="companySelect">All</a> | <a class="text-danger btn-clear"
                                                data-target="companySelect">Clear</a></div>
                                    </div>
                                    <select class="select2-multiple" id="companySelect" multiple
                                        data-placeholder="Select Companies..." style="width: 100%;"></select>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex justify-content-between align-items-end mb-1">
                                        <label class="small fw-bold text-dark mb-0">Branches / HO</label>
                                        <div class="filter-actions"><a class="text-primary btn-all"
                                                data-target="branchSelect">All</a> | <a class="text-danger btn-clear"
                                                data-target="branchSelect">Clear</a></div>
                                    </div>
                                    <select class="select2-multiple" id="branchSelect" multiple
                                        data-placeholder="Select Branches..." style="width: 100%;"></select>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex justify-content-between align-items-end mb-1">
                                        <label class="small fw-bold text-dark mb-0">Departments</label>
                                        <div class="filter-actions"><a class="text-primary btn-all"
                                                data-target="deptSelect">All</a> | <a class="text-danger btn-clear"
                                                data-target="deptSelect">Clear</a></div>
                                    </div>
                                    <select class="select2-multiple" id="deptSelect" multiple
                                        data-placeholder="Select Depts..." style="width: 100%;"></select>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex justify-content-between align-items-end mb-1">
                                        <label class="small fw-bold text-dark mb-0">Designations</label>
                                        <div class="filter-actions"><a class="text-primary btn-all"
                                                data-target="desigSelect">All</a> | <a class="text-danger btn-clear"
                                                data-target="desigSelect">Clear</a></div>
                                    </div>
                                    <select class="select2-multiple" id="desigSelect" multiple
                                        data-placeholder="Select Roles..." style="width: 100%;"></select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <label class="small fw-bold text-danger mb-0">Target Employees *</label>
                                <div class="filter-actions"><a class="text-primary btn-all"
                                        data-target="userSelect">SELECT ALL</a> | <a class="text-danger btn-clear"
                                        data-target="userSelect">CLEAR ALL</a></div>
                            </div>
                            <select class="select2-multiple" name="assignee_ids[]" id="userSelect" multiple
                                data-placeholder="Search or filter above to load employees..." style="width: 100%;"
                                required></select>
                        </div>

                        <hr class="text-muted">

                        <div id="tasksRepeaterContainer" class="border border-primary border-opacity-25 rounded p-3 mb-3"
                            style="background-color: #f8f9fa;">
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h6 class="fw-bold text-dark mb-0"><i class="fas fa-list-check text-primary me-2"></i> Add
                                    Task Items</h6>
                                <button type="button" class="btn btn-sm btn-success fw-bold shadow-sm"
                                    id="addTaskRowBtn">
                                    <i class="fas fa-plus me-1"></i> Add Another Target
                                </button>
                            </div>

                            <div id="tasksRepeaterBody">
                                <div
                                    class="task-row bg-white p-3 rounded border border-secondary border-opacity-25 mb-2 shadow-sm">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-5">
                                            <label class="small fw-bold text-muted mb-1">Task Title <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="tasks[0][title]"
                                                class="form-control form-control-sm"
                                                placeholder="E.g., Complete today's follow-ups" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small fw-bold text-muted mb-1">Target Base Work</label>
                                            <select name="tasks[0][tracking_module_id]"
                                                class="form-select form-select-sm tracking-module-dropdown">
                                                <option value="">Manual Task (No auto-track)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="small fw-bold text-muted mb-1">Target Count</label>
                                            <input type="number" name="tasks[0][target_count]"
                                                class="form-control form-control-sm" value="0" min="0">
                                        </div>
                                        <div class="col-md-1 text-center">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger w-100 remove-task-row" disabled
                                                title="First row cannot be removed">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Common Instructions (Applies to all
                                        tasks)</label>
                                    <textarea name="description" class="form-control" rows="5"
                                        placeholder="Type all details, lines, and instructions here..."></textarea>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-muted">Priority</label>
                                        <select name="priority" class="form-select form-select-sm">
                                            <option value="Low">Low</option>
                                            <option value="Medium" selected>Medium</option>
                                            <option value="High">High</option>
                                            <option value="Urgent">Urgent</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-muted">Due Date</label>
                                        <input type="datetime-local" name="due_datetime"
                                            class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Attach Files (Optional)</label>
                                    <input type="file" name="attachments[]" class="form-control form-control-sm"
                                        multiple>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="assignTaskForm" class="btn btn-primary fw-bold shadow-sm px-4"
                        id="submitTaskBtn">
                        <i class="fas fa-paper-plane me-2"></i> Send Tasks
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark text-truncate" id="detailTaskTitle" style="max-width: 85%;">
                        Loading...</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-0" style="background: #f8f9fa;">
                    <div class="p-3 bg-white border-bottom">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge bg-secondary" id="detailTaskPriority">Priority: -</span>
                            <span class="badge bg-secondary" id="detailTaskStatus">Status: -</span>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <p class="text-muted small mb-1"><i class="fas fa-calendar-alt text-primary me-1"></i> Due
                                    Date</p>
                                <p class="fw-bold text-dark small mb-0" id="detailTaskDue">-</p>
                            </div>
                            <div class="col-6 text-end">
                                <p class="text-muted small mb-1"><i class="fas fa-user-circle text-primary me-1"></i>
                                    Assigned To</p>
                                <p class="fw-bold text-dark small mb-0" id="detailTaskAssignee">-</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-3">
                        <h6 class="fw-bold text-dark mb-2 small text-uppercase">Instructions</h6>
                        <div class="bg-white p-3 rounded border shadow-sm small text-dark" id="detailTaskDesc"
                            style="white-space: pre-wrap;">
                            Loading description...
                        </div>
                    </div>

                    <div class="p-3 pt-0" id="detailProgressBox" style="display: none;">
                        <h6 class="fw-bold text-dark mb-2 small text-uppercase"><i
                                class="fas fa-crosshairs text-danger me-1"></i> Target Progress</h6>
                        <div class="bg-white p-3 rounded border shadow-sm border-primary border-opacity-25">
                            <div class="d-flex justify-content-between small fw-bold mb-1">
                                <span class="text-muted">Completed: <span id="detailProgressText">0/0</span></span>
                                <span class="text-primary" id="detailProgressPercent">0%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                    id="detailProgressBar" role="progressbar" style="width: 0%;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 pt-0" id="detailAttachmentsBox" style="display: none;">
                        <h6 class="fw-bold text-dark mb-2 small text-uppercase"><i
                                class="fas fa-paperclip text-info me-1"></i> Attachments</h6>
                        <div class="bg-white p-2 rounded border shadow-sm d-flex flex-wrap gap-2"
                            id="detailAttachmentsContainer">
                        </div>
                    </div>

                    <div class="p-3 border-top bg-white">
                        <h6 class="fw-bold text-dark mb-3 small text-uppercase"><i
                                class="fas fa-comments text-success me-1"></i> Updates & Remarks</h6>

                        <div id="detailTimeline" class="mb-4 bg-light p-3 rounded border shadow-sm"
                            style="max-height: 250px; overflow-y: auto;">
                            <div class="text-center text-muted small py-3"><i class="fas fa-spinner fa-spin"></i> Loading
                                timeline...</div>
                        </div>

                        <form id="taskReplyForm"
                            class="bg-white p-3 rounded border shadow-sm border-primary border-opacity-25">
                            <input type="hidden" name="task_id" id="replyTaskId">

                            <div class="mb-2">
                                <label class="small fw-bold text-muted mb-1">Change Status (Optional)</label>
                                <select name="status" id="replyTaskStatus"
                                    class="form-select form-select-sm border-secondary">
                                    <option value="Pending">Pending</option>
                                    <option value="In-Progress">In-Progress</option>
                                    <option value="Under Review">Under Review</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>

                            <div class="mb-2">
                                <textarea name="message_or_remark" class="form-control form-control-sm" rows="2"
                                    placeholder="Type your remark or update here..." required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small fw-bold text-muted mb-1">Attach File/Proof (Optional)</label>
                                <input type="file" name="attachments[]" class="form-control form-control-sm" multiple>
                            </div>

                            <button type="submit" class="btn btn-success btn-sm w-100 fw-bold shadow-sm"
                                id="replySubmitBtn">
                                <i class="fas fa-reply me-1"></i> Post Update
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark"><i class="fas fa-edit text-success me-2"></i> Edit Task
                        Details</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <form id="editTaskForm">
                        <input type="hidden" name="task_id" id="editTaskId">
                        <input type="hidden" name="_method" value="PUT">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Task Title <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="title" id="editTaskTitleInput" class="form-control fw-bold"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Detailed Instructions</label>
                            <textarea name="description" id="editTaskDescInput" class="form-control" rows="4"></textarea>
                        </div>

                        <div class="row bg-light p-3 rounded border border-primary border-opacity-25 mx-0 mb-3 shadow-sm">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark"><i
                                        class="fas fa-crosshairs text-danger me-1"></i> Target Base Work</label>
                                <select name="tracking_module_id" id="editTrackingModuleSelect"
                                    class="form-select form-select-sm tracking-module-dropdown border-primary">
                                    <option value="">Manual Task (No auto-track)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mt-2 mt-md-0">
                                <label class="form-label small fw-bold text-dark">Target Count</label>
                                <input type="number" name="target_count" id="editTargetCountInput"
                                    class="form-control form-control-sm border-primary" min="0">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Priority</label>
                                <select name="priority" id="editPrioritySelect" class="form-select form-select-sm">
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-6 mt-2 mt-md-0">
                                <label class="form-label small fw-bold text-muted">Due Date</label>
                                <input type="datetime-local" name="due_datetime" id="editDueDatetimeInput"
                                    class="form-control form-control-sm">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="editTaskForm" class="btn btn-success fw-bold shadow-sm"
                        id="submitEditTaskBtn">
                        <i class="fas fa-save me-2"></i> Save Changes
                    </button>
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
            let isFetchingUsers = false;

            // ==========================================
            // 🔥 INITIALIZE SELECT2 & CASCADING DROPDOWNS 🔥
            // ==========================================
            $('.select2-multiple').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $('#assignTaskModal'),
                closeOnSelect: false
            });

            $(document).on('click', '.btn-all', function() {
                let target = $(this).data('target');
                $('#' + target + ' > option').prop('selected', true);
                $('#' + target).trigger('change');
            });

            $(document).on('click', '.btn-clear', function() {
                let target = $(this).data('target');
                $('#' + target).val(null).trigger('change');
            });

            function getSelected(selectId) {
                let vals = $('#' + selectId).val();
                return (vals && vals.length > 0) ? vals.join(',') : '';
            }

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

            $('#companySelect').on('change', function() {
                let compIds = getSelected('companySelect');
                if (!compIds) {
                    $('#branchSelect').html('').val(null).trigger('change');
                    loadUsers();
                    return;
                }

                let html = '';
                $('#companySelect option:selected').each(function() {
                    html +=
                        `<option value="HO_${$(this).val()}">Head Office (${$(this).data('name')})</option>`;
                });

                $.get(apiPrefix + '/branches?company_ids=' + compIds, function(res) {
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

                $.get(apiPrefix + '/departments?branch_ids=' + branchIds, function(res) {
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

                $.get(apiPrefix + '/designations?department_ids=' + deptIds, function(res) {
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

            function loadUsers() {
                if (isFetchingUsers) return;

                let type = $('#hiddenAssigneeType').val();
                let apiUrl = type === 'App\\Models\\Employee' ? apiPrefix + '/employees' : apiPrefix + '/members';

                let params = {
                    length: -1,
                    company_ids: getSelected('companySelect'),
                    branch_ids: getSelected('branchSelect'),
                    department_ids: getSelected('deptSelect'),
                    designation_ids: getSelected('desigSelect')
                };

                if (!params.company_ids) {
                    $('#userSelect').html('').val(null).trigger('change.select2');
                    return;
                }

                isFetchingUsers = true;
                $.get(apiUrl, params, function(res) {
                    let html = '';
                    res.data.forEach(user => {
                        let name = user.full_name || user.member_name;
                        html += `<option value="${user.id}">${name} (${user.member_id})</option>`;
                    });
                    $('#userSelect').html(html).val(null).trigger('change.select2');
                    isFetchingUsers = false;
                });
            }

            $.get(apiPrefix + '/tracking-modules', function(res) {
                let options = '<option value="">Manual Task (No auto-track)</option>';
                res.data.forEach(mod => {
                    options +=
                        `<option value="${mod.id}">Yes, track '${mod.task_category_name}'</option>`;
                });
                $('.tracking-module-dropdown').html(options);
            });

            // ==========================================
            // 🔥 REPEATER ENGINE 🔥
            // ==========================================
            let taskRowIndex = 1;

            $('#addTaskRowBtn').on('click', function() {
                let firstRow = $('.task-row').first();
                let newRow = firstRow.clone();

                newRow.find('input[name="tasks[0][title]"]').attr('name', `tasks[${taskRowIndex}][title]`)
                    .val('');
                newRow.find('select[name="tasks[0][tracking_module_id]"]').attr('name',
                    `tasks[${taskRowIndex}][tracking_module_id]`).val('');
                newRow.find('input[name="tasks[0][target_count]"]').attr('name',
                    `tasks[${taskRowIndex}][target_count]`).val('0');

                newRow.find('.remove-task-row').prop('disabled', false)
                    .removeClass('btn-outline-danger').addClass('btn-danger shadow-sm')
                    .attr('title', 'Remove this row');

                newRow.hide().appendTo('#tasksRepeaterBody').slideDown(200);
                taskRowIndex++;
            });

            $(document).on('click', '.remove-task-row', function() {
                let row = $(this).closest('.task-row');
                row.slideUp(200, function() {
                    $(this).remove();
                });
            });

            $('#assignTaskModal').on('hidden.bs.modal', function() {
                $('#assignTaskForm')[0].reset();
                $('.task-row:not(:first)').remove();
                taskRowIndex = 1;
                $('.select2-multiple').html('').val(null).trigger('change.select2');
            });

            // ==========================================
            // 🔥 TASK DASHBOARD RENDER LOGIC 🔥
            // ==========================================
            function renderTasks() {
                $.get(apiPrefix + '/tasks', function(res) {
                    let html = '';
                    if (res.data.length === 0) {
                        html =
                            '<div class="col-12 text-center text-muted py-5"><i class="fas fa-check-circle fa-3x mb-3 text-success"></i><h5>No active tasks found!</h5></div>';
                        $('#taskBoard').html(html);
                        return;
                    }

                    let expandCollapseControls = `
                    <div class="col-12 mb-3 text-end">
                        <button class="btn btn-sm btn-outline-primary fw-bold shadow-sm me-2" id="expandAllBtn">
                            <i class="fas fa-expand-arrows-alt me-1"></i> Expand All
                        </button>
                        <button class="btn btn-sm btn-outline-secondary fw-bold shadow-sm" id="collapseAllBtn">
                            <i class="fas fa-compress-arrows-alt me-1"></i> Collapse All
                        </button>
                    </div>`;
                    html += expandCollapseControls;

                    let groupedTasks = {};
                    res.data.forEach(task => {
                        let assigneeId = task.assignee_id || 'unassigned';
                        let assigneeName = task.assignee ? (task.assignee.full_name || task.assignee
                            .member_name) : 'Unassigned';

                        if (!groupedTasks[assigneeId]) {
                            groupedTasks[assigneeId] = {
                                name: assigneeName,
                                tasks: []
                            };
                        }
                        groupedTasks[assigneeId].tasks.push(task);
                    });

                    for (let empId in groupedTasks) {
                        let emp = groupedTasks[empId];
                        let taskRowsHtml = '';
                        let collapseId = `collapse-emp-${empId}`;

                        emp.tasks.forEach(task => {
                            let progress = task.target_count > 0 ? Math.min((task.achieved_count /
                                task.target_count) * 100, 100).toFixed(0) : 0;
                            let statusColor = task.status === 'Completed' ? 'success' : (task
                                .status === 'In-Progress' ? 'primary' : 'warning');
                            let priorityColor = task.priority === 'Urgent' ? 'danger' : (task
                                .priority === 'High' ? 'warning' : 'info');

                            let targetText = task.target_count > 0 ?
                                `Target: ${task.achieved_count}/${task.target_count}` :
                                'Manual Task';
                            let dueDateText = task.due_datetime ? new Date(task.due_datetime)
                                .toLocaleDateString() : 'No Due Date';

                            taskRowsHtml += `
                            <div class="row align-items-center border-bottom p-3 m-0 task-row-hover task-row-item">
                                <div class="col-12 col-md-4 col-lg-4 mb-2 mb-md-0">
                                    <h6 class="fw-bold text-dark mb-1 text-truncate task-title-text" title="${task.title}">${task.title}</h6>
                                    <small class="text-muted"><i class="fas fa-bullseye me-1"></i> ${targetText} &nbsp;|&nbsp; <i class="fas fa-clock text-warning"></i> ${dueDateText}</small>
                                </div>
                                
                                <div class="col-12 col-md-2 col-lg-2 mb-2 mb-md-0 d-flex gap-2">
                                    <span class="badge bg-${priorityColor} bg-opacity-10 text-${priorityColor} border border-${priorityColor} border-opacity-25">${task.priority}</span>
                                    <span class="badge bg-${statusColor} text-white">${task.status}</span>
                                </div>
                                
                                <div class="col-12 col-md-3 col-lg-3 mb-3 mb-md-0">
                                    <div class="d-flex justify-content-between small text-muted fw-bold mb-1">
                                        <span>Progress</span>
                                        <span class="text-${statusColor}">${progress}%</span>
                                    </div>
                                    <div class="progress shadow-sm" style="height: 6px;">
                                        <div class="progress-bar bg-${statusColor}" role="progressbar" style="width: ${progress}%;"></div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-3 col-lg-3 text-md-end d-flex gap-1 justify-content-md-end">
                                    <button class="btn btn-sm btn-light border text-primary shadow-sm view-task-btn" data-id="${task.id}" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light border text-success shadow-sm edit-task-btn" data-id="${task.id}" title="Edit Task">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light border text-danger shadow-sm delete-task-btn" data-id="${task.id}" title="Delete Task">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>`;
                        });

                        html += `
                        <div class="col-12 mb-4 emp-card-wrapper">
                            <div class="card border-0 shadow-sm overflow-hidden" style="border-top: 4px solid #1A365D;">
                                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center" 
                                     data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="true" style="cursor: pointer; user-select: none;">
                                    <h6 class="fw-bold text-dark mb-0 emp-name-text">
                                        <i class="fas fa-user-circle text-primary fs-5 me-2 align-middle"></i> <span class="align-middle">${emp.name}</span>
                                    </h6>
                                    <div>
                                        <span class="badge bg-secondary rounded-pill shadow-sm me-3">${emp.tasks.length} Active Tasks</span>
                                        <i class="fas fa-chevron-up text-muted toggle-icon fs-5 align-middle"></i>
                                    </div>
                                </div>
                                <div id="${collapseId}" class="collapse show task-collapse">
                                    <div class="card-body p-0">
                                        ${taskRowsHtml}
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    }

                    $('#taskBoard').html(html);
                });
            }

            $(document).on('click', '#expandAllBtn', function() {
                $('.task-collapse').collapse('show');
            });
            $(document).on('click', '#collapseAllBtn', function() {
                $('.task-collapse').collapse('hide');
            });

            // ==========================================
            // 🔥 LIVE SEARCH ENGINE 🔥
            // ==========================================
            $('#liveSearchTasks').on('keyup', function() {
                let keyword = $(this).val().toLowerCase();

                $('.emp-card-wrapper').each(function() {
                    let employeeName = $(this).find('.emp-name-text').text().toLowerCase();
                    let matchFoundInCard = false;

                    $(this).find('.task-row-item').each(function() {
                        let taskTitle = $(this).find('.task-title-text').text()
                        .toLowerCase();

                        if (employeeName.includes(keyword) || taskTitle.includes(keyword)) {
                            $(this).show();
                            matchFoundInCard = true;
                        } else {
                            $(this).hide();
                        }
                    });

                    if (matchFoundInCard) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // ==========================================
            // 🔥 ASSIGN NEW TASK FORM SUBMIT 🔥
            // ==========================================
            $('.open-assign-modal').on('click', function() {
                let type = $(this).data('type');
                let titleText = type === 'App\\Models\\Employee' ? 'Bulk Assign to Staff' :
                    'Bulk Assign to Associates';
                $('#modalTitle').html(`<i class="fas fa-bolt text-warning me-2"></i> ${titleText}`);
                $('#hiddenAssigneeType').val(type);

                loadCompanies();
                new bootstrap.Modal(document.getElementById('assignTaskModal')).show();
            });

            $('#assignTaskForm').on('submit', function(e) {
                e.preventDefault();

                if (!$('#userSelect').val() || $('#userSelect').val().length === 0) {
                    Swal.fire('Wait!', 'Please select at least one person from the Target Employees list.',
                        'warning');
                    return;
                }

                let btn = $('#submitTaskBtn');
                let originalText = btn.html();
                let formData = new FormData(this);

                btn.html('<i class="fas fa-spinner fa-spin"></i> Dispatching...').prop('disabled', true);

                $.ajax({
                    url: apiPrefix + '/tasks',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Tasks Dispatched!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        bootstrap.Modal.getInstance(document.getElementById('assignTaskModal'))
                            .hide();
                        renderTasks();
                    },
                    error: function(err) {
                        Swal.fire('Error', err.responseJSON ? err.responseJSON.message :
                            'Error assigning task.', 'error');
                    },
                    complete: function() {
                        btn.html(originalText).prop('disabled', false);
                    }
                });
            });

            renderTasks();

            // ==========================================
            // 🔥 HELPER: RENDER CHAT TIMELINE (SMOOTH UX) 🔥
            // ==========================================
            function renderTimelineToModal(task) {
                let timelineHtml = '';
                if (task.progress_logs && task.progress_logs.length > 0) {
                    task.progress_logs.forEach(log => {
                        // FIX: Added fallback to name or 'System/Admin' if undefined
                        let actorName = log.actor ? (log.actor.full_name || log.actor.member_name || log
                            .actor.name) : 'System/Admin';
                        let logDate = new Date(log.created_at).toLocaleString();

                        let logFilesHtml = '';
                        if (task.attachments) {
                            let logFiles = task.attachments.filter(f => f.task_progress_log_id == log.id);
                            if (logFiles.length > 0) {
                                logFilesHtml += `<div class="mt-2 mb-1 d-flex flex-wrap gap-2">`;
                                logFiles.forEach(f => {
                                    let url = '/' + f.file_path;
                                    logFilesHtml +=
                                        `<a href="${url}" target="_blank" class="badge bg-info bg-opacity-10 text-primary border border-info border-opacity-25 text-decoration-none p-2 shadow-sm"><i class="fas fa-paperclip me-1"></i> ${f.file_name}</a>`;
                                });
                                logFilesHtml += `</div>`;
                            }
                        }

                        timelineHtml += `
                        <div class="mb-3 border-bottom pb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <strong class="text-dark small"><i class="fas fa-user-circle text-primary me-1"></i> ${actorName}</strong>
                                <small class="text-muted" style="font-size: 10px;">${logDate}</small>
                            </div>
                            <p class="small text-secondary mb-1">${log.message_or_remark}</p>
                            ${logFilesHtml}
                            <span class="badge bg-white text-dark border small shadow-sm mt-1">${log.log_type}</span>
                        </div>`;
                    });
                } else {
                    timelineHtml =
                        '<div class="text-center text-muted small py-3">No updates yet. Start the conversation!</div>';
                }

                let timelineDiv = $('#detailTimeline');
                timelineDiv.html(timelineHtml);

                // Auto scroll to bottom
                timelineDiv.animate({
                    scrollTop: timelineDiv.prop("scrollHeight")
                }, 500);
            }

            // ==========================================
            // 🔥 VIEW TASK DETAILS & OPEN MODAL 🔥
            // ==========================================
            $(document).on('click', '.view-task-btn', function() {
                let taskId = $(this).data('id');
                let btn = $(this);
                let originalText = btn.html();

                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

                $.get(apiPrefix + '/tasks/' + taskId, function(res) {
                    let task = res.data;

                    $('#detailTaskTitle').text(task.title);
                    $('#detailTaskPriority').text('Priority: ' + task.priority);
                    $('#detailTaskStatus').text('Status: ' + task.status);

                    $('#detailTaskStatus').removeClass().addClass('badge bg-' + (task.status ===
                        'Completed' ? 'success' : (task.status === 'In-Progress' ?
                            'primary' : 'warning')));
                    $('#detailTaskPriority').removeClass().addClass('badge bg-' + (task.priority ===
                        'Urgent' ? 'danger' : (task.priority === 'High' ? 'warning' :
                            'info')));

                    $('#detailTaskDue').text(task.due_datetime ? new Date(task.due_datetime)
                        .toLocaleString() : 'No Deadline');
                    $('#detailTaskAssignee').text(task.assignee ? (task.assignee.full_name || task
                        .assignee.member_name) : 'Unknown');
                    $('#detailTaskDesc').text(task.description || 'No description provided.');

                    if (task.target_count > 0) {
                        $('#detailProgressBox').show();
                        let percent = Math.min((task.achieved_count / task.target_count) * 100, 100)
                            .toFixed(0);
                        $('#detailProgressText').text(
                            `${task.achieved_count} / ${task.target_count}`);
                        $('#detailProgressPercent').text(`${percent}%`);
                        $('#detailProgressBar').css('width', `${percent}%`);
                    } else {
                        $('#detailProgressBox').hide();
                    }

                    let attachmentsHtml = '';
                    let hasInitialFiles = false;

                    if (task.attachments && task.attachments.length > 0) {
                        task.attachments.forEach(file => {
                            if (file.task_progress_log_id == null) {
                                hasInitialFiles = true;
                                let url = '/' + file.file_path;
                                attachmentsHtml +=
                                    `<a href="${url}" target="_blank" class="badge bg-light text-primary border text-decoration-none py-2 px-3 shadow-sm"><i class="fas fa-file-download me-1"></i> ${file.file_name}</a>`;
                            }
                        });
                    }

                    if (hasInitialFiles) {
                        $('#detailAttachmentsBox').show();
                        $('#detailAttachmentsContainer').html(attachmentsHtml);
                    } else {
                        $('#detailAttachmentsBox').hide();
                    }

                    // Render smooth chat timeline
                    renderTimelineToModal(task);

                    $('#replyTaskId').val(task.id);
                    $('#replyTaskStatus').val(task.status);

                    new bootstrap.Modal(document.getElementById('taskDetailsModal')).show();

                }).fail(function(err) {
                    let errorMsg = err.responseJSON && err.responseJSON.message ? err.responseJSON
                        .message : 'Server is not responding properly.';
                    Swal.fire('Error', errorMsg, 'error');
                }).always(function() {
                    btn.html(originalText).prop('disabled', false);
                });
            });

            // ==========================================
            // 🔥 SUBMIT TASK REPLY (SMOOTH CHAT UX) 🔥
            // ==========================================
            $('#taskReplyForm').on('submit', function(e) {
                e.preventDefault();

                let form = $(this);
                let btn = $('#replySubmitBtn');
                let originalText = btn.html();
                let taskId = $('#replyTaskId').val();

                if (!taskId) {
                    Swal.fire('Error', 'Task ID missing!', 'error');
                    return;
                }

                let formData = new FormData(this);
                btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Sending...').prop('disabled', true);

                $.ajax({
                    url: apiPrefix + '/tasks/' + taskId + '/reply',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(res) {
                        // 1. Toast dikhao (Modal nahi hatega)
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true,
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Message Sent!'
                        });

                        // 2. Form reset
                        form[0].reset();
                        $('#replyTaskId').val(taskId);

                        // 3. Naya data manga kar chat timeline update karo
                        $.get(apiPrefix + '/tasks/' + taskId, function(freshRes) {
                            let freshTask = freshRes.data;
                            $('#detailTaskStatus').text('Status: ' + freshTask.status);
                            $('#detailTaskStatus').removeClass().addClass('badge bg-' +
                                (freshTask.status === 'Completed' ? 'success' : (
                                    freshTask.status === 'In-Progress' ?
                                    'primary' : 'warning')));
                            renderTimelineToModal(freshTask);
                        });

                        // 4. Background dashboard update
                        renderTasks();
                    },
                    error: function(err) {
                        let errorMsg = err.responseJSON && err.responseJSON.message ? err
                            .responseJSON.message : 'Failed to send message.';
                        Swal.fire('Error', errorMsg, 'error');
                    },
                    complete: function() {
                        btn.html(originalText).prop('disabled', false);
                    }
                });
            });

            // ==========================================
            // 🔥 DELETE TASK AJAX 🔥
            // ==========================================
            $(document).on('click', '.delete-task-btn', function() {
                let taskId = $(this).data('id');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this! The task and all its history will be deleted.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: apiPrefix + '/tasks/' + taskId,
                            type: 'DELETE',
                            success: function(res) {
                                Swal.fire('Deleted!', res.message, 'success');
                                renderTasks();
                            },
                            error: function(err) {
                                let errorMsg = err.responseJSON && err.responseJSON
                                    .message ? err.responseJSON.message :
                                    'Could not delete task.';
                                Swal.fire('Error!', errorMsg, 'error');
                            }
                        });
                    }
                });
            });

            // ==========================================
            // 🔥 EDIT TASK (Open Modal & Populate) 🔥
            // ==========================================
            $(document).on('click', '.edit-task-btn', function() {
                let taskId = $(this).data('id');
                let btn = $(this);
                let originalHtml = btn.html();

                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

                $.get(apiPrefix + '/tasks/' + taskId, function(res) {
                    let task = res.data;
                    $('#editTaskId').val(task.id);
                    $('#editTaskTitleInput').val(task.title);
                    $('#editTaskDescInput').val(task.description);
                    $('#editTrackingModuleSelect').val(task.tracking_module_id || '');
                    $('#editTargetCountInput').val(task.target_count);
                    $('#editPrioritySelect').val(task.priority);

                    if (task.due_datetime) {
                        let d = new Date(task.due_datetime);
                        let formatted = d.getFullYear() + '-' +
                            String(d.getMonth() + 1).padStart(2, '0') + '-' +
                            String(d.getDate()).padStart(2, '0') + 'T' +
                            String(d.getHours()).padStart(2, '0') + ':' +
                            String(d.getMinutes()).padStart(2, '0');
                        $('#editDueDatetimeInput').val(formatted);
                    } else {
                        $('#editDueDatetimeInput').val('');
                    }

                    new bootstrap.Modal(document.getElementById('editTaskModal')).show();
                }).fail(function() {
                    Swal.fire('Error', 'Could not fetch task details for editing.', 'error');
                }).always(function() {
                    btn.html(originalHtml).prop('disabled', false);
                });
            });

            // ==========================================
            // 🔥 SUBMIT UPDATED TASK 🔥
            // ==========================================
            $('#editTaskForm').on('submit', function(e) {
                e.preventDefault();

                let taskId = $('#editTaskId').val();
                let formData = new FormData(this);
                let btn = $('#submitEditTaskBtn');
                let originalHtml = btn.html();

                btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...').prop('disabled', true);

                $.ajax({
                    url: apiPrefix + '/tasks/' + taskId,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        bootstrap.Modal.getInstance(document.getElementById('editTaskModal'))
                            .hide();
                        renderTasks();
                    },
                    error: function(err) {
                        let errorMsg = err.responseJSON && err.responseJSON.message ? err
                            .responseJSON.message : 'Failed to update task.';
                        Swal.fire('Error', errorMsg, 'error');
                    },
                    complete: function() {
                        btn.html(originalHtml).prop('disabled', false);
                    }
                });
            });

        });
    </script>
@endpush
