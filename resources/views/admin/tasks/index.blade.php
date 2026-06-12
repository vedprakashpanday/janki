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

        /* 🔥 FIX: CLEAR BUTTON ('x') VISIBILITY 🔥 */
        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
            color: #ffffff !important;
            opacity: 1 !important;
            background: transparent !important;
            border: none !important;
            margin-right: 5px !important;
            font-size: 16px !important;
            font-weight: 900 !important;
            line-height: 1 !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2) !important;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove span {
            display: none !important;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove::before {
            content: "×" !important;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #ff4d4d !important;
        }

        /* 🔥 FIX: SELECT2 POSITIONING PARENT 🔥 */
        .position-relative {
            position: relative !important;
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

        .task-row-hover:hover {
            background-color: #f8f9fa !important;
            transition: all 0.2s ease-in-out;
        }

        .collapse-trigger .toggle-icon {
            transition: transform 0.3s ease;
        }

        .collapse-trigger.collapsed .toggle-icon {
            transform: rotate(180deg);
        }

        .chat-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .chat-bubble {
            max-width: 85%;
            padding: 10px 14px;
            border-radius: 12px;
            position: relative;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
        }

        .chat-bubble.left {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 2px;
            align-self: flex-start;
        }

        .chat-bubble.right {
            background-color: #dcf8c6;
            border: 1px solid #c8e6b3;
            border-bottom-right-radius: 2px;
            align-self: flex-end;
        }

        .unread-task-row {
            background-color: #fff3cd !important;
            border-left: 4px solid #ffc107 !important;
            transition: 0.3s;
        }

        @keyframes blinker {
            50% {
                opacity: 0.2;
            }
        }

        .blink-anim {
            animation: blinker 1s linear infinite;
            box-shadow: 0 0 8px rgba(220, 53, 69, 0.6);
        }
    </style>

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-0"><i class="fas fa-tasks text-primary me-2"></i> Task Command Center</h4>
                <small class="text-muted">Manage, track, and complete your daily goals.</small>
            </div>
            <div class="d-flex gap-2" id="dynamicTaskButtons"></div>
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

        <div class="alert alert-danger d-flex justify-content-between align-items-center mb-3 shadow-sm py-2 px-3 secured-item"
            data-permission="task_delete" id="bulkDeleteZone" style="display: none !important;">
            <div>
                <i class="fas fa-exclamation-triangle me-1"></i> <span class="fw-bold" id="bulkDeleteCount">0</span> Tasks
                Selected
            </div>
            <button class="btn btn-sm btn-danger fw-bold shadow-sm" id="executeBulkDeleteBtn">
                <i class="fas fa-trash-alt me-1"></i> Delete Selected
            </button>
        </div>

        <div class="row" id="taskBoard">
            <div class="col-12 text-center py-5">
                <i class="fas fa-spinner fa-spin fa-3x text-muted mb-3"></i>
                <p class="text-muted fw-bold">Syncing latest tasks...</p>
            </div>
        </div>
    </div>

    <!-- Assign Modal -->
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
                                <div class="col-md-3 position-relative">
                                    <div class="d-flex justify-content-between align-items-end mb-1">
                                        <label class="small fw-bold text-dark mb-0">Companies</label>
                                        <div class="filter-actions"><a class="text-primary btn-all"
                                                data-target="companySelect">All</a> | <a class="text-danger btn-clear"
                                                data-target="companySelect">Clear</a></div>
                                    </div>
                                    <select class="select2-multiple" id="companySelect" multiple
                                        data-placeholder="Select Companies..." style="width: 100%;"></select>
                                </div>
                                <div class="col-md-3 position-relative">
                                    <div class="d-flex justify-content-between align-items-end mb-1">
                                        <label class="small fw-bold text-dark mb-0">Branches / HO</label>
                                        <div class="filter-actions"><a class="text-primary btn-all"
                                                data-target="branchSelect">All</a> | <a class="text-danger btn-clear"
                                                data-target="branchSelect">Clear</a></div>
                                    </div>
                                    <select class="select2-multiple" id="branchSelect" multiple
                                        data-placeholder="Select Branches..." style="width: 100%;"></select>
                                </div>
                                <div class="col-md-3 position-relative">
                                    <div class="d-flex justify-content-between align-items-end mb-1">
                                        <label class="small fw-bold text-dark mb-0" id="targetDeptLabel">Departments</label>
                                        <div class="filter-actions"><a class="text-primary btn-all"
                                                data-target="deptSelect">All</a> | <a class="text-danger btn-clear"
                                                data-target="deptSelect">Clear</a></div>
                                    </div>
                                    <select class="select2-multiple" id="deptSelect" multiple
                                        data-placeholder="Select Depts..." style="width: 100%;"></select>
                                </div>
                                <div class="col-md-3 position-relative">
                                    <div class="d-flex justify-content-between align-items-end mb-1">
                                        <label class="small fw-bold text-dark mb-0"
                                            id="targetDesigLabel">Designations</label>
                                        <div class="filter-actions"><a class="text-primary btn-all"
                                                data-target="desigSelect">All</a> | <a class="text-danger btn-clear"
                                                data-target="desigSelect">Clear</a></div>
                                    </div>
                                    <select class="select2-multiple" id="desigSelect" multiple
                                        data-placeholder="Select Roles..." style="width: 100%;"></select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4 position-relative">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <label class="small fw-bold text-danger mb-0" id="targetUserLabel">Target Employees
                                    *</label>
                                <div class="filter-actions"><a class="text-primary btn-all"
                                        data-target="userSelect">SELECT ALL</a> | <a class="text-danger btn-clear"
                                        data-target="userSelect">CLEAR ALL</a></div>
                            </div>
                            <select class="select2-multiple" name="assignee_ids[]" id="userSelect" multiple
                                data-placeholder="Search or filter above to load targets..." style="width: 100%;"
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
                                        <div class="col-md-3">
                                            <label class="small fw-bold text-muted mb-1">Task Title <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-sm task-title-input"
                                                placeholder="E.g., Complete today's follow-ups" required>
                                        </div>
                                        <div class="col-md-3 position-relative">
                                            <label class="small fw-bold text-muted mb-1">Specific Assignees</label>
                                            <!-- 🔥 FIX: Ensure position-relative wrapper and select2 classes are used correctly 🔥 -->
                                            <select class="select2-multiple task-specific-users" multiple
                                                data-placeholder="All targets (Leave empty for all)"
                                                style="width: 100%;"></select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="small fw-bold text-muted mb-1">Target Base Work</label>
                                            <select class="form-select form-select-sm tracking-module-dropdown">
                                                <option value="">Manual Task (No auto-track)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="small fw-bold text-muted mb-1">Target Count</label>
                                            <input type="number" class="form-control form-control-sm target-count-input"
                                                value="0" min="0">
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
                                    <input type="file" id="globalAttachmentsInput" name="attachments[]"
                                        class="form-control form-control-sm" multiple>
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

    <!-- DETAILS AND LIVE CHAT MODAL -->
    <div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark text-truncate" id="detailTaskTitle" style="max-width: 85%;">
                        Loading...</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="background: #eef2f5;">
                    <div class="p-3 bg-white border-bottom shadow-sm">
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

                    <div class="p-3 pb-0">
                        <h6 class="fw-bold text-dark mb-2 small text-uppercase">Instructions</h6>
                        <div class="bg-white p-3 rounded border shadow-sm small text-dark" id="detailTaskDesc"
                            style="white-space: pre-wrap;">
                            Loading description...
                        </div>
                    </div>

                    <div class="p-3 pb-0" id="detailProgressBox" style="display: none;">
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

                    <div class="p-3 pb-0" id="detailAttachmentsBox" style="display: none;">
                        <h6 class="fw-bold text-dark mb-2 small text-uppercase"><i
                                class="fas fa-paperclip text-info me-1"></i> Initial Attachments</h6>
                        <div class="bg-white p-2 rounded border shadow-sm d-flex flex-wrap gap-2"
                            id="detailAttachmentsContainer"></div>
                    </div>

                    <div class="p-3 mt-2"
                        style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-size: cover; border-top: 1px solid #e2e8f0;">
                        <h6
                            class="fw-bold text-dark mb-3 small text-uppercase bg-white d-inline-block px-3 py-1 rounded shadow-sm">
                            <i class="fas fa-comments text-success me-1"></i> Live Chat & Updates
                        </h6>
                        <div id="detailTimeline" class="mb-2 p-2 rounded"
                            style="height: 350px; overflow-y: auto; display: flex; flex-direction: column;">
                            <div class="text-center text-muted small py-3"><i class="fas fa-spinner fa-spin"></i> Loading
                                timeline...</div>
                        </div>
                    </div>

                    <div class="p-3 border-top bg-white z-1 position-relative shadow-lg">
                        <form id="taskReplyForm">
                            <input type="hidden" name="task_id" id="replyTaskId">
                            <div class="row align-items-center g-2 mb-2">
                                <div class="col-md-5">
                                    <select name="status" id="replyTaskStatus"
                                        class="form-select form-select-sm border-secondary shadow-sm">
                                        <option value="Pending">Pending</option>
                                        <option value="In-Progress">In-Progress</option>
                                        <option value="Under Review">Under Review</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                </div>
                                <div class="col-md-7">
                                    <input type="file" name="attachments[]"
                                        class="form-control form-control-sm shadow-sm" multiple title="Attach File/Proof">
                                </div>
                            </div>
                            <div class="input-group shadow-sm">
                                <input type="text" name="message_or_remark" class="form-control"
                                    placeholder="Type your message here..." required autocomplete="off">
                                <button type="submit" class="btn btn-success fw-bold px-4" id="replySubmitBtn">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
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
            let loggedInUserName = $('.user-name-display').first().text().trim();

            let currentPath = window.location.pathname;
            let localPortal = 'admin';

            if (currentPath.startsWith('/employee')) {
                localPortal = 'employee';
            } else if (currentPath.startsWith('/customer')) {
                localPortal = 'customer';
            }

            let btnHtml = '';
            if (localPortal === 'admin') {
                btnHtml += `
                <button class="btn btn-primary fw-bold shadow-sm open-assign-modal secured-item" data-permission="task_add_direct" data-type="App\\Models\\Employee">
                    <i class="fas fa-user-tie me-1"></i> Give Task to Staff
                </button>
                <button class="btn btn-warning fw-bold shadow-sm text-dark open-assign-modal secured-item" data-permission="task_add_direct" data-type="App\\Models\\Member">
                    <i class="fas fa-users me-1"></i> Give Task to Associates
                </button>`;
            } else if (localPortal === 'employee') {
                btnHtml += `
                <button class="btn btn-primary fw-bold shadow-sm open-assign-modal secured-item" data-permission="task_add_direct" data-type="App\\Models\\Employee">
                    <i class="fas fa-user-tie me-1"></i> Give Task to Staff
                </button>`;
            } else {
                btnHtml += `
                <button class="btn btn-warning fw-bold shadow-sm text-dark open-assign-modal secured-item" data-permission="task_add_direct" data-type="App\\Models\\Member">
                    <i class="fas fa-users me-1"></i> Give Task to Associates
                </button>`;
            }

            $('#dynamicTaskButtons').html(btnHtml);

            let checkPermsInterval = setInterval(function() {
                if (typeof window.applyPermissions === 'function' && window.userPerms !== undefined) {
                    window.applyPermissions();
                    loggedInUserName = $('.user-name-display').first().text().trim();
                    clearInterval(checkPermsInterval);
                }
            }, 100);

            window.markTaskAsUnread = function(taskId) {
                let badge = $('#unread-badge-' + taskId);
                if (badge.length) {
                    let empId = badge.data('emp');
                    let taskCount = parseInt(badge.attr('data-count')) || 0;
                    taskCount++;
                    badge.attr('data-count', taskCount).text(taskCount + ' New Msg').removeClass('d-none');
                    badge.closest('.task-row-item').addClass('unread-task-row');

                    if (empId) {
                        let empBadge = $('#unread-emp-' + empId);
                        if (empBadge.length) {
                            let empCount = parseInt(empBadge.attr('data-count')) || 0;
                            empCount++;
                            empBadge.attr('data-count', empCount).text(empCount + ' New Msg').removeClass(
                                'd-none');
                        }
                    }
                }
            };

            // 🔥 FIX: SELECT2 POSITIONING PARENT FIX 🔥
            function initSelect2(element) {
                $(element).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $(element).parent(), // Forces it strictly to its immediate container
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

            $('.open-assign-modal').on('click', function() {
                let type = $(this).data('type');
                let isStaff = type === 'App\\Models\\Employee';

                let titleText = isStaff ? 'Bulk Assign to Staff' : 'Bulk Assign to Associates';
                let iconClass = isStaff ? 'fa-user-tie text-primary' : 'fa-users text-warning';
                let targetLabel = isStaff ? 'Target Staff / Employees <span class="text-danger">*</span>' :
                    'Target Associates / Members <span class="text-danger">*</span>';
                let deptLabel = isStaff ? 'Staff Departments' : 'Associate Departments';
                let roleLabel = isStaff ? 'Staff Designations' : 'Associate Roles';

                $('#modalTitle').html(`<i class="fas ${iconClass} me-2"></i> ${titleText}`);
                $('#targetUserLabel').html(targetLabel);
                $('#targetDeptLabel').text(deptLabel);
                $('#targetDesigLabel').text(roleLabel);

                $('#hiddenAssigneeType').val(type);

                $('.select2-multiple').html('').val(null).trigger('change.select2');
                $('#assignTaskForm')[0].reset();

                $('.task-row:not(:first)').remove();
                $('.task-specific-users').html('').val(null).trigger('change.select2');

                loadCompanies();
                new bootstrap.Modal(document.getElementById('assignTaskModal')).show();
            });

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
                let type = $('#hiddenAssigneeType').val() === 'App\\Models\\Employee' ? 'employee' :
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
                let type = $('#hiddenAssigneeType').val() === 'App\\Models\\Employee' ? 'employee' :
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
                let type = $('#hiddenAssigneeType').val() === 'App\\Models\\Employee' ? 'employee' :
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

            // 🔥 FIX: STRICT ID CODE FOR MEMBER_ID 🔥
            function loadUsers() {
                if (isFetchingUsers) return;
                let type = $('#hiddenAssigneeType').val();
                let isStaff = type === 'App\\Models\\Employee';
                let apiUrl = isStaff ? apiPrefix + '/employees' : apiPrefix + '/members';

                let params = {
                    length: -1,
                    company_ids: getSelected('companySelect'),
                    branch_ids: getSelected('branchSelect'),
                    department_ids: getSelected('deptSelect'),
                    designation_ids: getSelected('desigSelect'),
                    user_type: isStaff ? 'employee' : 'member'
                };

                if (!params.company_ids) {
                    $('#userSelect').html('').val(null).trigger('change.select2');
                    return;
                }

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

            $.get(apiPrefix + '/tracking-modules', function(res) {
                let options = '<option value="">Manual Task (No auto-track)</option>';
                res.data.forEach(mod => {
                    options +=
                        `<option value="${mod.id}">Yes, track '${mod.task_category_name}'</option>`;
                });
                $('.tracking-module-dropdown').html(options);
            });

            $('#userSelect').on('change', function() {
                let selectedOptions = $(this).find('option:selected');
                let optionsHtml = '';
                selectedOptions.each(function() {
                    optionsHtml += `<option value="${$(this).val()}">${$(this).text()}</option>`;
                });
                $('.task-specific-users').each(function() {
                    let currentVals = $(this).val() || [];
                    $(this).html(optionsHtml);
                    $(this).val(currentVals).trigger('change.select2');
                });
            });

            // 🔥 FIX: SAFELY CLONING SELECT2 WITH POSITIONING 🔥
            $('#addTaskRowBtn').on('click', function() {
                let firstRow = $('.task-row').first();
                let specificSelectFirst = firstRow.find('.task-specific-users');

                if (specificSelectFirst.hasClass('select2-hidden-accessible')) {
                    specificSelectFirst.select2('destroy');
                }

                let newRow = firstRow.clone();
                initSelect2(firstRow.find('.task-specific-users'));

                newRow.find('.task-title-input').val('');
                newRow.find('.tracking-module-dropdown').val('');
                newRow.find('.target-count-input').val('0');

                let newSpecificSelect = newRow.find('.task-specific-users');
                newSpecificSelect.empty().val(null);
                newSpecificSelect.removeClass('select2-hidden-accessible').removeAttr(
                    'data-select2-id tabindex aria-hidden');
                newRow.find('.select2-container').remove();

                newRow.find('.remove-task-row').prop('disabled', false).removeClass('btn-outline-danger')
                    .addClass('btn-danger shadow-sm').attr('title', 'Remove this row');

                newRow.hide().appendTo('#tasksRepeaterBody').slideDown(200);

                let selectedOptions = $('#userSelect').find('option:selected');
                let optionsHtml = '';
                selectedOptions.each(function() {
                    optionsHtml += `<option value="${$(this).val()}">${$(this).text()}</option>`;
                });
                newSpecificSelect.html(optionsHtml);

                initSelect2(newSpecificSelect);
            });

            $(document).on('click', '.remove-task-row', function() {
                let row = $(this).closest('.task-row');
                row.slideUp(200, function() {
                    $(this).remove();
                });
            });

            $('#assignTaskForm').on('submit', function(e) {
                e.preventDefault();

                let globalAssignees = $('#userSelect').val();
                if (!globalAssignees || globalAssignees.length === 0) {
                    Swal.fire('Wait!', 'Please select at least one person from the Target Employees list.',
                        'warning');
                    return;
                }

                let btn = $('#submitTaskBtn');
                let originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i> Dispatching...').prop('disabled', true);

                let requestGroups = {};

                $('.task-row').each(function() {
                    let row = $(this);
                    let title = row.find('.task-title-input').val();
                    let trackId = row.find('.tracking-module-dropdown').val();
                    let count = row.find('.target-count-input').val();
                    let specificUsers = row.find('.task-specific-users').val();

                    let assigneesToUse = (specificUsers && specificUsers.length > 0) ?
                        specificUsers : globalAssignees;
                    let groupKey = [...assigneesToUse].sort().join(',');

                    if (!requestGroups[groupKey]) {
                        requestGroups[groupKey] = {
                            assignee_ids: assigneesToUse,
                            tasks: []
                        };
                    }
                    requestGroups[groupKey].tasks.push({
                        title: title,
                        tracking_module_id: trackId,
                        target_count: count
                    });
                });

                let fileInput = document.getElementById('globalAttachmentsInput');
                let promises = [];

                Object.values(requestGroups).forEach(group => {
                    let fd = new FormData();
                    fd.append('assignee_type', $('#hiddenAssigneeType').val());
                    fd.append('description', $('textarea[name="description"]').val());
                    fd.append('priority', $('select[name="priority"]').val());
                    fd.append('due_datetime', $('input[name="due_datetime"]').val());

                    group.assignee_ids.forEach(id => fd.append('assignee_ids[]', id));

                    group.tasks.forEach((t, i) => {
                        fd.append(`tasks[${i}][title]`, t.title);
                        if (t.tracking_module_id) {
                            fd.append(`tasks[${i}][tracking_module_id]`, t
                                .tracking_module_id);
                        }
                        fd.append(`tasks[${i}][target_count]`, t.target_count);
                    });

                    if (fileInput.files.length > 0) {
                        for (let i = 0; i < fileInput.files.length; i++) {
                            fd.append('attachments[]', fileInput.files[i]);
                        }
                    }

                    promises.push($.ajax({
                        url: apiPrefix + '/tasks',
                        type: 'POST',
                        data: fd,
                        contentType: false,
                        processData: false
                    }));
                });

                Promise.all(promises).then(responses => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Tasks Dispatched!',
                        text: 'All targeted tasks have been assigned successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    bootstrap.Modal.getInstance(document.getElementById('assignTaskModal')).hide();
                    renderTasks();
                }).catch(err => {
                    Swal.fire('Error', 'Some tasks failed to assign. Please check your data.',
                        'error');
                }).finally(() => {
                    btn.html(originalText).prop('disabled', false);
                });
            });

            // 🔥 FIX: FAIL-SAFE LOADER PREVENTION 🔥
            // ==========================================
            // 🔥 ULTIMATE DEBUGGING RENDER TASKS 🔥
            // ==========================================
            function renderTasks() {
                console.log("🚀 -> renderTasks() called...");
                $('#taskBoard').html('<div class="col-12 text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-muted mb-3"></i><p class="text-muted fw-bold" id="loadingText">Syncing latest tasks...</p></div>');
                
                let apiUrl = apiPrefix + '/tasks';
                console.log("📡 -> Requesting Tasks from API: " + apiUrl);

                $.ajax({
                    url: apiUrl,
                    type: 'GET',
                    dataType: 'json',
                    success: function(res) {
                        console.log("✅ -> API SUCCESS! Raw Data:", res);
                        
                        // JS Engine Fail-Safe Try-Catch
                        try {
                            if (res.status === 'error') {
                                throw new Error("Backend Returned Error: " + res.message);
                            }

                            if (!res || !res.data || !Array.isArray(res.data) || res.data.length === 0) {
                                console.log("⚠️ -> No tasks found or data is empty array.");
                                let html = '<div class="col-12 text-center text-muted py-5"><i class="fas fa-check-circle fa-3x mb-3 text-success"></i><h5>No active tasks found!</h5></div>';
                                $('#taskBoard').html(html);
                                return;
                            }

                            console.log("⚙️ -> Processing " + res.data.length + " tasks to generate HTML...");
                            
                            let expandCollapseControls = `
                            <div class="col-12 mb-3 text-end">
                                <button class="btn btn-sm btn-outline-primary fw-bold shadow-sm me-2" id="expandAllBtn"><i class="fas fa-expand-arrows-alt me-1"></i> Expand All</button>
                                <button class="btn btn-sm btn-outline-secondary fw-bold shadow-sm" id="collapseAllBtn"><i class="fas fa-compress-arrows-alt me-1"></i> Collapse All</button>
                            </div>`;
                            let html = expandCollapseControls;

                            let groupedTasks = {};
                            res.data.forEach(task => {
                                let assigneeId = task.assignee_id || 'unassigned';
                                // SAFE CHECK FOR NAMES
                                let assigneeName = 'Unassigned';
                                if (task.assignee) {
                                    assigneeName = task.assignee.full_name || task.assignee.member_name || task.assignee.name || 'Unknown User';
                                }

                                if (!groupedTasks[assigneeId]) {
                                    groupedTasks[assigneeId] = {
                                        name: assigneeName,
                                        tasks: [],
                                        unreadCount: 0 
                                    };
                                }
                                groupedTasks[assigneeId].tasks.push(task);
                            });

                            for (let empId in groupedTasks) {
                                let maxTime = 0;
                                groupedTasks[empId].tasks.forEach(t => {
                                    let time = t.created_at ? new Date(t.created_at).getTime() : 0; 
                                    if (t.progress_logs && t.progress_logs.length > 0) {
                                        let logTime = t.progress_logs[0].created_at ? new Date(t.progress_logs[0].created_at).getTime() : 0;
                                        if (logTime > time) time = logTime;
                                    }
                                    if (time > maxTime) maxTime = time;
                                });
                                groupedTasks[empId].latest_activity = maxTime;
                            }

                            let sortedEmpIds = Object.keys(groupedTasks).sort((a, b) => groupedTasks[b].latest_activity - groupedTasks[a].latest_activity);

                            sortedEmpIds.forEach(empId => {
                                let emp = groupedTasks[empId];
                                let taskRowsHtml = '';
                                let collapseId = `collapse-emp-${empId}`;

                                let activeCount = emp.tasks.filter(t => t.status !== 'Completed').length;

                                emp.tasks.forEach(task => {
                                    let isUnread = false;
                                    let unreadMsgCount = 0;

                                    if (task.progress_logs && task.progress_logs.length > 0) {
                                        let lastLog = task.progress_logs[0];
                                        let actorName = lastLog.actor ? (lastLog.actor.full_name || lastLog.actor.member_name || lastLog.actor.name) : 'System';
                                        
                                        if (actorName !== 'System' && actorName !== 'System/Admin' && actorName !== loggedInUserName) {
                                            let lastReadLogId = localStorage.getItem('task_read_' + task.id);
                                            if (lastReadLogId != lastLog.id) {
                                                isUnread = true;
                                                unreadMsgCount = 1;
                                                emp.unreadCount += 1;
                                            }
                                        }
                                    }

                                    let unreadRowClass = isUnread ? 'unread-task-row' : '';
                                    let badgeClass = isUnread ? 'blink-anim' : 'd-none';
                                    let msgText = isUnread ? '1 New Msg' : '0 Msg';

                                    let targetCount = parseInt(task.target_count) || 0;
                                    let achievedCount = parseInt(task.achieved_count) || 0;
                                    let progress = targetCount > 0 ? Math.min((achievedCount / targetCount) * 100, 100).toFixed(0) : 0;
                                    
                                    let statusColor = task.status === 'Completed' ? 'success' : (task.status === 'In-Progress' ? 'primary' : 'warning');
                                    let priorityColor = task.priority === 'Urgent' ? 'danger' : (task.priority === 'High' ? 'warning' : 'info');

                                    let targetText = targetCount > 0 ? `Target: ${achievedCount}/${targetCount}` : 'Manual Task';
                                    let assignedDateText = task.created_at ? new Date(task.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : 'Unknown';
                                    let dueDateText = task.due_datetime ? new Date(task.due_datetime).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : 'No Due Date';

                                    taskRowsHtml += `
                                    <div class="row align-items-center border-bottom p-3 m-0 task-row-hover task-row-item ${unreadRowClass}">
                                        <div class="col-12 col-md-4 col-lg-4 mb-2 mb-md-0 d-flex align-items-start gap-2">
                                            <input type="checkbox" class="form-check-input mt-1 task-checkbox emp-${empId}-task" value="${task.id}" data-emp="${empId}" style="cursor:pointer;">
                                            <div>
                                                <h6 class="fw-bold text-dark mb-1 text-truncate task-title-text" title="${task.title}">
                                                    ${task.title}
                                                    <span class="badge bg-danger rounded-pill ms-2 ${badgeClass} unread-msg-badge" id="unread-badge-${task.id}" data-emp="${empId}" data-count="${unreadMsgCount}">${msgText}</span>
                                                </h6>
                                                <small class="text-muted"><i class="fas fa-bullseye me-1"></i> ${targetText} &nbsp;|&nbsp; <i class="fas fa-calendar-plus text-success"></i> Assigned: ${assignedDateText} &nbsp;|&nbsp; <i class="fas fa-clock text-warning"></i> Due: ${dueDateText}</small>
                                            </div>
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
                                            <button class="btn btn-sm btn-light border text-success shadow-sm edit-task-btn secured-item" data-permission="task_edit" data-id="${task.id}" title="Edit Task">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-light border text-danger shadow-sm delete-task-btn secured-item" data-permission="task_delete" data-id="${task.id}" title="Delete Task">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>`;
                                });

                                let empBadgeClass = emp.unreadCount > 0 ? 'blink-anim' : 'd-none';
                                let empBadgeText  = emp.unreadCount > 0 ? `${emp.unreadCount} New Msg` : '0 Msg';

                                html += `
                                <div class="col-12 mb-4 emp-card-wrapper">
                                    <div class="card border-0 shadow-sm overflow-hidden" style="border-top: 4px solid #1A365D;">
                                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center collapse-trigger collapsed" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="false" style="cursor:pointer;">
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="checkbox" class="form-check-input emp-select-all" data-emp="${empId}" style="cursor:pointer; width:18px; height:18px;" onclick="event.stopPropagation();">
                                                <h6 class="fw-bold text-dark mb-0 emp-name-text">
                                                    <i class="fas fa-user-circle text-primary fs-5 mx-1 align-middle"></i> <span class="align-middle">${emp.name}</span>
                                                    <span class="badge bg-danger rounded-pill ms-2 ${empBadgeClass} unread-emp-badge" id="unread-emp-${empId}" data-count="${emp.unreadCount}">${empBadgeText}</span>
                                                </h6>
                                            </div>
                                            <div>
                                                <span class="badge bg-secondary rounded-pill shadow-sm me-3">${activeCount} Active Tasks</span>
                                                <i class="fas fa-chevron-up text-muted toggle-icon fs-5 align-middle"></i>
                                            </div>
                                        </div>
                                        <div id="${collapseId}" class="collapse task-collapse">
                                            <div class="card-body p-0">
                                                ${taskRowsHtml}
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                            });

                            console.log("🎨 -> HTML Generation Complete. Injecting to DOM...");
                            $('#taskBoard').html(html);

                            if (typeof window.applyPermissions === 'function') {
                                window.applyPermissions();
                            }
                            console.log("🏁 -> renderTasks() Execution Finished Successfully!");

                        } catch (jsError) {
                            console.error("🚨 -> JAVASCRIPT RENDERING ERROR CAUGHT:", jsError);
                            $('#taskBoard').html(`
                                <div class="col-12 text-center py-5 text-danger border border-danger bg-light rounded mt-3">
                                    <i class="fas fa-bug fa-3x mb-3"></i>
                                    <h5 class="fw-bold">Frontend Rendering Crashed!</h5>
                                    <p class="text-dark">Message: <b>${jsError.message}</b></p>
                                    <p class="small text-muted mb-0">Check DevTools Console (F12) for the exact line number.</p>
                                </div>
                            `);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("🚨 -> API AJAX ERROR:", status, error);
                        console.log("Response Text:", xhr.responseText);
                        
                        let errorMsg = "Server is not responding. Check Network tab.";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }

                        $('#taskBoard').html(`
                            <div class="col-12 text-center py-5 text-danger">
                                <i class="fas fa-wifi fa-3x mb-3 text-warning"></i>
                                <h5 class="fw-bold">Failed to load tasks from Server!</h5>
                                <p class="text-muted small">${errorMsg}</p>
                                <button class="btn btn-sm btn-outline-secondary mt-2" onclick="location.reload()">Refresh Page</button>
                            </div>
                        `);
                    }
                });
            }

            $(document).on('click', '#expandAllBtn', function() {
                $('.task-collapse').collapse('show');
            });
            $(document).on('click', '#collapseAllBtn', function() {
                $('.task-collapse').collapse('hide');
            });

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

            // 🔥 FIX: STRICT CHAT SCROLL TO BOTTOM LOGIC 🔥
            function forceScrollToBottom() {
                let timelineDiv = document.getElementById('detailTimeline');
                if (timelineDiv) {
                    timelineDiv.scrollTop = timelineDiv.scrollHeight;
                }
            }

            $('#taskDetailsModal').on('shown.bs.modal', function() {
                forceScrollToBottom();
            });

            function renderTimelineToModal(task) {
                let timelineHtml = '<div class="chat-container">';

                if (task.progress_logs && task.progress_logs.length > 0) {
                    let sortedLogs = [...task.progress_logs].reverse();
                    sortedLogs.forEach(log => {
                        let actorName = log.actor ? (log.actor.full_name || log.actor.member_name || log
                            .actor.name) : 'System/Admin';
                        let d = new Date(log.created_at);
                        let logDate = d.toLocaleDateString('en-GB', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric'
                        }) + ' ' + d.toLocaleTimeString('en-US', {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        });

                        let logFilesHtml = '';
                        if (task.attachments) {
                            let logFiles = task.attachments.filter(f => f.task_progress_log_id == log.id);
                            if (logFiles.length > 0) {
                                logFilesHtml += `<div class="mt-2 mb-1 d-flex flex-wrap gap-2">`;
                                logFiles.forEach(f => {
                                    let url = '/' + f.file_path;
                                    logFilesHtml +=
                                        `<a href="${url}" target="_blank" class="badge bg-white text-primary border border-info border-opacity-25 text-decoration-none p-1 px-2 shadow-sm"><i class="fas fa-paperclip me-1"></i> View Attached File</a>`;
                                });
                                logFilesHtml += `</div>`;
                            }
                        }

                        let isMe = (actorName === loggedInUserName || actorName === 'System' ||
                            actorName === 'System/Admin');
                        let bubbleClass = isMe ? 'right' : 'left';

                        timelineHtml += `
                        <div class="chat-bubble ${bubbleClass}">
                            <div class="d-flex justify-content-between mb-1">
                                <strong class="small" style="color: #1A365D;"><i class="fas fa-user-circle me-1"></i> ${actorName}</strong>
                            </div>
                            <p class="small text-dark fw-medium mb-1">${log.message_or_remark}</p>
                            ${logFilesHtml}
                            <div class="text-end mt-1">
                                <small class="text-muted" style="font-size: 10px;">${logDate} &nbsp; <span class="badge bg-light text-dark border p-1" style="font-size: 8px;">${log.log_type}</span></small>
                            </div>
                        </div>`;
                    });
                } else {
                    timelineHtml +=
                        '<div class="text-center text-muted small py-3 w-100">No updates yet. Start the conversation!</div>';
                }

                timelineHtml += '</div>';
                let timelineDiv = $('#detailTimeline');
                timelineDiv.html(timelineHtml);

                // Immediately scroll and also set a tiny delay to ensure paint is done
                forceScrollToBottom();
                setTimeout(forceScrollToBottom, 150);
            }

            $(document).on('click', '.view-task-btn', function() {
                let taskId = $(this).data('id');
                let btn = $(this);
                let originalText = btn.html();

                let taskBadge = $('#unread-badge-' + taskId);
                if (taskBadge.length && !taskBadge.hasClass('d-none')) {
                    let taskCount = parseInt(taskBadge.attr('data-count')) || 0;
                    let empId = taskBadge.data('emp');

                    taskBadge.attr('data-count', 0).addClass('d-none');
                    btn.closest('.task-row-item').removeClass('unread-task-row');

                    if (empId) {
                        let empBadge = $('#unread-emp-' + empId);
                        if (empBadge.length) {
                            let empCount = parseInt(empBadge.attr('data-count')) || 0;
                            empCount = Math.max(0, empCount - taskCount);
                            empBadge.attr('data-count', empCount);
                            if (empCount === 0) {
                                empBadge.addClass('d-none');
                            } else {
                                empBadge.text(empCount + ' New Msg');
                            }
                        }
                    }
                }

                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

                $.get(apiPrefix + '/tasks/' + taskId, function(res) {
                    let task = res.data;

                    if (task.progress_logs && task.progress_logs.length > 0) {
                        localStorage.setItem('task_read_' + task.id, task.progress_logs[0].id);
                    }

                    $('#detailTaskTitle').text(task.title);
                    $('#detailTaskPriority').text('Priority: ' + task.priority);
                    $('#detailTaskStatus').text('Status: ' + task.status);

                    $('#detailTaskStatus').removeClass().addClass('badge bg-' + (task.status ===
                        'Completed' ? 'success' : (task.status === 'In-Progress' ?
                            'primary' : 'warning')));
                    $('#detailTaskPriority').removeClass().addClass('badge bg-' + (task.priority ===
                        'Urgent' ? 'danger' : (task.priority === 'High' ? 'warning' :
                            'info')));

                    let dDue = task.due_datetime ? new Date(task.due_datetime) : null;
                    let dueString = dDue ? (dDue.toLocaleDateString('en-GB', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    }) + ' ' + dDue.toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    })) : 'No Deadline';
                    $('#detailTaskDue').text(dueString);

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

                    renderTimelineToModal(task);

                    $('#replyTaskId').val(task.id);
                    $('#replyTaskStatus').val(task.status);

                    if (typeof window.Echo !== 'undefined') {
                        if (window.currentChatChannel) {
                            window.Echo.leave(window.currentChatChannel);
                        }

                        let pathUrl = window.location.pathname;
                        let tKey = pathUrl.startsWith('/employee') ? 'emp_token' : (pathUrl
                            .startsWith('/customer') ? 'customer_token' : 'admin_token');
                        let uToken = localStorage.getItem(tKey) || localStorage.getItem('token') ||
                            '';

                        let currentOptions = window.Echo.connector.options;
                        currentOptions.authEndpoint = '/broadcasting/auth?token=' +
                            encodeURIComponent(uToken);
                        currentOptions.auth = {
                            headers: {
                                'Authorization': 'Bearer ' + uToken,
                                'Accept': 'application/json'
                            },
                            params: {
                                token: uToken
                            }
                        };

                        window.Echo.disconnect();
                        window.Echo = new window.Echo.constructor(currentOptions);

                        window.currentChatChannel = `task.${task.id}`;

                        window.Echo.private(window.currentChatChannel).listen('.message.sent', (
                            e) => {
                                let log = e.logData;
                                let logFilesHtml = '';
                                if (log.attachments && log.attachments.length > 0) {
                                    logFilesHtml +=
                                        `<div class="mt-2 mb-1 d-flex flex-wrap gap-2">`;
                                    log.attachments.forEach(f => {
                                        logFilesHtml +=
                                            `<a href="/${f.file_path}" target="_blank" class="badge bg-white text-primary border border-info border-opacity-25 text-decoration-none p-1 px-2 shadow-sm"><i class="fas fa-paperclip me-1"></i> View File</a>`;
                                    });
                                    logFilesHtml += `</div>`;
                                }

                                let newMsgHtml = `
                            <div class="chat-bubble left" style="display:none;" id="new-msg-${log.id}">
                                <div class="d-flex justify-content-between mb-1">
                                    <strong class="small" style="color: #1A365D;"><i class="fas fa-user-circle me-1"></i> ${log.actor_name}</strong>
                                </div>
                                <p class="small text-dark fw-medium mb-1">${log.message}</p>
                                ${logFilesHtml}
                                <div class="text-end mt-1">
                                    <small class="text-muted" style="font-size: 10px;">Just Now &nbsp; <span class="badge bg-light text-dark border p-1" style="font-size: 8px;">${log.log_type}</span></small>
                                </div>
                            </div>`;

                                let timelineDiv = $('#detailTimeline');
                                if (timelineDiv.find('.text-center').length > 0) {
                                    timelineDiv.find('.chat-container').html('');
                                }

                                let container = timelineDiv.find('.chat-container');
                                if (!container.length) {
                                    timelineDiv.html('<div class="chat-container"></div>');
                                    container = timelineDiv.find('.chat-container');
                                }

                                container.append(newMsgHtml);
                                $(`#new-msg-${log.id}`).fadeIn(300);

                                setTimeout(forceScrollToBottom, 100);

                                if (!$('#taskDetailsModal').hasClass('show') || $(
                                        '#replyTaskId').val() != task.id) {
                                    window.markTaskAsUnread(task.id);
                                }
                            }).error((error) => {
                            console.error("Echo Subscription Error:", error);
                        });
                    }

                    new bootstrap.Modal(document.getElementById('taskDetailsModal')).show();

                }).fail(function(err) {
                    let errorMsg = err.responseJSON && err.responseJSON.message ? err.responseJSON
                        .message : 'Server is not responding properly.';
                    Swal.fire('Error', errorMsg, 'error');
                }).always(function() {
                    btn.html(originalText).prop('disabled', false);
                });
            });

            $('#taskReplyForm').on('submit', function(e) {
                e.preventDefault();

                let form = $(this);
                let btn = $('#replySubmitBtn');
                let originalText = btn.html();
                let taskId = $('#replyTaskId').val();

                if (!taskId) return;

                let formData = new FormData(this);
                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

                let socketId = '';
                if (typeof window.Echo !== 'undefined' && window.Echo.socketId()) {
                    socketId = window.Echo.socketId();
                }

                $.ajax({
                    url: apiPrefix + '/tasks/' + taskId + '/reply',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    headers: {
                        'X-Socket-ID': socketId
                    },
                    success: function(res) {
                        form[0].reset();
                        $('#replyTaskId').val(taskId);

                        $.get(apiPrefix + '/tasks/' + taskId, function(freshRes) {
                            let freshTask = freshRes.data;
                            if (freshTask.progress_logs && freshTask.progress_logs
                                .length > 0) {
                                localStorage.setItem('task_read_' + freshTask.id,
                                    freshTask.progress_logs[0].id);
                            }

                            $('#detailTaskStatus').text('Status: ' + freshTask.status);
                            $('#detailTaskStatus').removeClass().addClass('badge bg-' +
                                (freshTask.status === 'Completed' ? 'success' : (
                                    freshTask.status === 'In-Progress' ?
                                    'primary' : 'warning')));
                            renderTimelineToModal(freshTask);
                        });

                        renderTasks();
                    },
                    error: function(err) {
                        Swal.fire('Error', 'Failed to send message.', 'error');
                    },
                    complete: function() {
                        btn.html(originalText).prop('disabled', false);
                    }
                });
            });

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
                            }
                        });
                    }
                });
            });

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
                        let formatted = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2,
                                '0') + '-' + String(d.getDate()).padStart(2, '0') + 'T' + String(d
                                .getHours()).padStart(2, '0') + ':' + String(d.getMinutes())
                            .padStart(2, '0');
                        $('#editDueDatetimeInput').val(formatted);
                    } else {
                        $('#editDueDatetimeInput').val('');
                    }
                    new bootstrap.Modal(document.getElementById('editTaskModal')).show();
                }).always(function() {
                    btn.html(originalHtml).prop('disabled', false);
                });
            });

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
                    complete: function() {
                        btn.html(originalHtml).prop('disabled', false);
                    }
                });
            });

            function toggleBulkDeleteZone() {
                let checkedCount = $('.task-checkbox:checked').length;
                if (checkedCount > 0) {
                    $('#bulkDeleteZone').attr('style', 'display: flex !important;');
                    $('#bulkDeleteCount').text(checkedCount);
                } else {
                    $('#bulkDeleteZone').attr('style', 'display: none !important;');
                }
            }
            $(document).on('change', '.emp-select-all', function() {
                let empId = $(this).data('emp');
                let isChecked = $(this).prop('checked');
                $(`.emp-${empId}-task`).prop('checked', isChecked);
                toggleBulkDeleteZone();
            });
            $(document).on('change', '.task-checkbox', function() {
                let empId = $(this).data('emp');
                let totalTasks = $(`.emp-${empId}-task`).length;
                let checkedTasks = $(`.emp-${empId}-task:checked`).length;
                $(`.emp-select-all[data-emp="${empId}"]`).prop('checked', totalTasks === checkedTasks);
                toggleBulkDeleteZone();
            });
            $(document).on('click', '#executeBulkDeleteBtn', function() {
                let selectedIds = [];
                $('.task-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });
                if (selectedIds.length === 0) return;
                Swal.fire({
                    title: 'Delete ' + selectedIds.length + ' Tasks?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete all!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: apiPrefix + '/bulk-delete',
                            type: 'POST',
                            data: {
                                table_name: 'tasks',
                                ids: selectedIds
                            },
                            success: function(res) {
                                $('#bulkDeleteZone').attr('style',
                                    'display: none !important;');
                                renderTasks();
                            }
                        });
                    }
                });
            });

            renderTasks();
        });
    </script>
@endpush
