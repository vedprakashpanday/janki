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

        .desc-collapse {
            max-height: 90px;
            overflow: hidden;
            position: relative;
            transition: max-height 0.3s ease;
        }

      .desc-collapse.expanded {
    max-height: 5000px !important;
    overflow: visible !important;
    -webkit-line-clamp: unset !important;
}
        .desc-collapse::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 35px;
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0), rgba(255, 255, 255, 1));
            transition: opacity 0.3s;
            pointer-events: none;
        }
       .desc-collapse.expanded::after {
    display: none !important;
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

        <!-- 🔍 SEARCH-BASED TASK FILTER ROW -->
<div class="card border-0 shadow-sm p-3 mb-3 bg-white">
    <div class="row g-2 align-items-center">
        <div class="col-md-3">
            <label class="small fw-bold text-muted mb-1">Company Filter</label>
            <select id="filterCompany" class="form-select form-select-sm border-secondary shadow-sm">
                <option value="">All Companies</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="small fw-bold text-muted mb-1">Branch Filter</label>
            <select id="filterBranch" class="form-select form-select-sm border-secondary shadow-sm">
                <option value="">All Branches</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="small fw-bold text-muted mb-1">Department Filter</label>
            <select id="filterDepartment" class="form-select form-select-sm border-secondary shadow-sm">
                <option value="">All Departments</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="small fw-bold text-muted mb-1">Designation Filter</label>
            <select id="filterDesignation" class="form-select form-select-sm border-secondary shadow-sm">
                <option value="">All Designations</option>
            </select>
        </div>
    </div>
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
                                <div class="task-row bg-white p-3 rounded border border-secondary border-opacity-25 mb-2 shadow-sm">
                                    <div class="row g-2 align-items-end">
                                        <!-- Title -->
                                        <div class="col-md-2">
                                            <label class="small fw-bold text-muted mb-1">Task Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-sm task-title-input" placeholder="Task Name" required>
                                        </div>
                                        <!-- Specific Assignees -->
                                        <div class="col-md-2 position-relative">
                                            <label class="small fw-bold text-muted mb-1">Specific Assignees</label>
                                            <select class="select2-multiple task-specific-users" multiple data-placeholder="All targets" style="width: 100%;"></select>
                                        </div>
                                        <!-- Module -->
                                        <div class="col-md-2">
                                            <label class="small fw-bold text-muted mb-1">Target Base Work</label>
                                            <select class="form-select form-select-sm tracking-module-dropdown border-primary">
                                                <option value="">Manual Task</option>
                                            </select>
                                        </div>
                                        <!-- Phase -->
                                        <div class="col-md-2">
                                            <label class="small fw-bold text-muted mb-1">Linked Phase</label>
                                            <select class="form-select form-select-sm task-phase-dropdown border-info">
                                                <option value="">-- No Phase --</option>
                                            </select>
                                        </div>
                                        <!-- 🔥 NAYA: PROVIDER DROPDOWN + PERCENTAGE 🔥 -->
                                        <div class="col-md-2">
                                            <label class="small fw-bold text-muted mb-1">Provider & %</label>
                                            <div class="input-group input-group-sm">
                                                <select class="form-select task-provider-dropdown border-warning">
                                                    <option value="">-- Mixed Data --</option>
                                                </select>
                                                <input type="number" class="form-control border-warning provider-percent-input" value="50" min="1" max="100" style="max-width: 60px;" title="Provider Assignment Percentage">
                                            </div>
                                        </div>

                                        <!-- Count -->
                                        <div class="col-md-1">
                                            <label class="small fw-bold text-muted mb-1">Count</label>
                                            <input type="number" class="form-control form-control-sm target-count-input" value="0" min="0">
                                        </div>
                                        <!-- Remove Btn -->
                                        <div class="col-md-1 text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-task-row" disabled title="First row cannot be removed"><i class="fas fa-times"></i></button>
                                        </div>

                                        <!-- 🔥 NAYA: MEMBER OVERRIDE TOGGLE 🔥 -->
                                        <div class="col-md-12 mt-2">
                                            <div class="form-check form-switch bg-light p-2 rounded border">
                                                <input class="form-check-input override-member-toggle ms-0 me-2" type="checkbox" style="cursor: pointer;">
                                                <label class="form-check-label small fw-bold text-danger" style="cursor: pointer;">
                                                    <i class="fas fa-unlock-alt"></i> Assign Member's Data (Override)
                                                </label>
                                            </div>
                                        </div>

                                        <!-- OVERRIDE SECTION (Hidden by default) -->
                                        <div class="col-md-12 mt-2 override-section d-none bg-danger bg-opacity-10 p-3 rounded border border-danger">
                                            <div class="row align-items-end g-2">
                                               <div class="col-md-4 position-relative">
    <label class="small fw-bold text-danger mb-1">Select Member</label>
                                                    <select class="form-select form-select-sm task-override-member border-danger" style="width: 100%;">
                                                        <option value="">Search Member...</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <button type="button" class="btn btn-sm btn-danger w-100 view-summary-btn fw-bold shadow-sm"><i class="fas fa-chart-pie me-1"></i> View Summary</button>
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="small fw-bold text-danger mb-1">Fetch Status Type</label>
                                                    <select class="form-select form-select-sm task-override-status border-danger">
                                                        <option value="Pending">Pending (Fresh)</option>
                                                        <option value="Busy">Busy</option>
                                                        <option value="Switch Off">Switch Off</option>
                                                        <option value="Not Reachable">Not Reachable</option>
                                                        <option value="Call Back Requested">Call Back Requested</option>
                                                        <option value="all">All Available</option>
                                                    </select>
                                                </div>
                                            </div>
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

  <div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-hidden="true">
        <!-- 🔥 FIX: modal-dialog-scrollable hata diya gaya hai taaki custom flexbox kaam kare -->
        <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-md-down">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark text-truncate" id="detailTaskTitle" style="max-width: 85%;">Loading...</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- 🔥 FIX: height: 80vh; add kiya gaya hai -->
                <div class="modal-body p-0 d-flex flex-column" style="background: #eef2f5; height: 80vh;">
                    
                    <!-- 🔝 SCROLLABLE AREA -->
                    <div class="flex-grow-1 overflow-auto" id="taskModalScrollArea">
                        <div class="p-3 bg-white border-bottom shadow-sm">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="badge bg-secondary" id="detailTaskPriority">Priority: -</span>
                                <span class="badge bg-secondary" id="detailTaskStatus">Status: -</span>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <p class="text-muted small mb-1"><i class="fas fa-calendar-alt text-primary me-1"></i> Due Date</p>
                                    <p class="fw-bold text-dark small mb-0" id="detailTaskDue">-</p>
                                </div>
                                <div class="col-6 text-end">
                                    <p class="text-muted small mb-1"><i class="fas fa-user-circle text-primary me-1"></i> Assigned To</p>
                                    <p class="fw-bold text-dark small mb-0" id="detailTaskAssignee">-</p>
                                </div>
                            </div>
                        </div>

                        <!-- Expandable Instructions -->
                        <div class="p-3 pb-0">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-dark mb-0 small text-uppercase">Instructions</h6>
                                <a href="#" id="toggleDescBtn" class="small fw-bold text-primary text-decoration-none" style="display: none;">Read More <i class="fas fa-chevron-down"></i></a>
                            </div>
                            <div class="bg-white p-3 rounded border shadow-sm small text-dark desc-collapse" id="detailTaskDesc" style="white-space: pre-wrap;">
                                Loading description...
                            </div>
                        </div>

                        <div class="p-3 pb-0" id="detailProgressBox" style="display: none;">
                            <h6 class="fw-bold text-dark mb-2 small text-uppercase"><i class="fas fa-crosshairs text-danger me-1"></i> Target Progress</h6>
                            <div class="bg-white p-3 rounded border shadow-sm border-primary border-opacity-25">
                                <div class="d-flex justify-content-between small fw-bold mb-1">
                                    <span class="text-muted">Completed: <span id="detailProgressText">0/0</span></span>
                                    <span class="text-primary" id="detailProgressPercent">0%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" id="detailProgressBar" role="progressbar" style="width: 0%;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 pb-0" id="detailAttachmentsBox" style="display: none;">
                            <h6 class="fw-bold text-dark mb-2 small text-uppercase"><i class="fas fa-paperclip text-info me-1"></i> Initial Attachments</h6>
                            <div class="bg-white p-2 rounded border shadow-sm d-flex flex-wrap gap-2" id="detailAttachmentsContainer"></div>
                        </div>

                        <div class="p-3 mt-2" style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-size: cover; border-top: 1px solid #e2e8f0;">
                            <h6 class="fw-bold text-dark mb-3 small text-uppercase bg-white d-inline-block px-3 py-1 rounded shadow-sm">
                                <i class="fas fa-comments text-success me-1"></i> Live Chat & Updates
                            </h6>
                            <!-- Timeline height removed so it adjusts naturally -->
                            <div id="detailTimeline" class="mb-2 p-2 rounded" style="display: flex; flex-direction: column;">
                                <div class="text-center text-muted small py-3"><i class="fas fa-spinner fa-spin"></i> Loading timeline...</div>
                            </div>
                        </div>
                    </div>

                    <!-- ⬇️ STICKY FOOTER FORM -->
                    <div class="p-3 border-top bg-white z-1 shadow-lg mt-auto w-100">
                        <form id="taskReplyForm">
                            <input type="hidden" name="task_id" id="replyTaskId">
                            <div class="row align-items-center g-2 mb-2">
                                <div class="col-md-5">
                                    <select name="status" id="replyTaskStatus" class="form-select form-select-sm border-secondary shadow-sm">
                                        <option value="Pending">Pending</option>
                                        <option value="In-Progress">In-Progress</option>
                                        <option value="Under Review">Under Review</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                </div>
                                <div class="col-md-7">
                                    <input type="file" name="attachments[]" id="chatFileInput" class="form-control form-control-sm shadow-sm" multiple title="Attach File/Proof" accept="image/*,video/*,.pdf">
                                </div>
                            </div>
                            
                            <!-- File Preview Container -->
                            <div id="filePreviewContainer" class="d-flex gap-2 mb-2 flex-wrap empty-hide d-none"></div>
                            
                            <div class="input-group shadow-sm position-relative">
                                <textarea name="message_or_remark" id="chatMessageInput" class="form-control" placeholder="Type your message here... @ to tag" rows="1" required style="resize: none; overflow-y: hidden; max-height: 120px;"></textarea>
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
    <div class="col-md-3">
        <label class="form-label small fw-bold text-dark"><i class="fas fa-crosshairs text-danger me-1"></i> Target Base Work</label>
        <select name="tracking_module_id" id="editTrackingModuleSelect" class="form-select form-select-sm tracking-module-dropdown border-primary">
            <option value="">Manual Task</option>
        </select>
    </div>
    <div class="col-md-3 mt-2 mt-md-0">
        <label class="form-label small fw-bold text-dark"><i class="fas fa-building text-warning me-1"></i> Linked Phase</label>
        <select name="phase_id" id="editPhaseSelect" class="form-select form-select-sm task-phase-dropdown border-info">
            <option value="">-- No Phase --</option>
        </select>
    </div>
    <!-- 🔥 NAYA: EDIT PROVIDER DROPDOWN + PERCENTAGE 🔥 -->
    <div class="col-md-3 mt-2 mt-md-0">
        <label class="form-label small fw-bold text-dark"><i class="fas fa-filter text-success me-1"></i> Provider & %</label>
        <div class="input-group input-group-sm">
            <select name="provider_id" id="editProviderSelect" class="form-select border-warning task-provider-dropdown">
                <option value="">Loading providers...</option>
            </select>
            <input type="number" name="provider_percent" id="editProviderPercentInput" class="form-control border-warning" value="50" min="1" max="100" style="max-width: 70px;">
        </div>
    </div>
    <div class="col-md-3 mt-2 mt-md-0">
        <label class="form-label small fw-bold text-dark">Target Count</label>
        <input type="number" name="target_count" id="editTargetCountInput" class="form-control form-control-sm border-primary" min="0">
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
        let loggedInUserName = $('.user-name-display').first().text().trim();
        let globalContext = null;

        // 1. Inject UI Button for Staff Tasks
        let btnHtml = `
            <button class="btn btn-primary fw-bold shadow-sm open-assign-modal secured-item" data-permission="task_add_direct">
                <i class="fas fa-user-tie me-1"></i> Give Task to Staff
            </button>`;
        $('#dynamicTaskButtons').html(btnHtml);

        // Permissions Check Interval
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
                        empBadge.attr('data-count', empCount).text(empCount + ' New Msg').removeClass('d-none');
                    }
                }
            }
        };

        function initSelect2(element) {
            $(element).select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $(element).parent(),
                closeOnSelect: false
            });
        }

        function getSelected(selectId) {
            let vals = $('#' + selectId).val();
            return (vals && vals.length > 0) ? vals.join(',') : '';
        }

        function loadAvailableProviders() {
            $.ajax({
                url: apiPrefix + '/available-providers',
                type: 'GET',
                success: function(res) {
                    let options = '<option value="">-- Mixed Data (No specific) --</option>';
                    if (res.success && res.data && res.data.length > 0) {
                        res.data.forEach(p => { options += `<option value="${p.id}">${p.name}</option>`; });
                    } else {
                        options = '<option value="">-- No Data Available --</option>';
                    }
                    $('.task-provider-dropdown').html(options);
                }
            });
        }
        loadAvailableProviders();

        // ========================================================
        // 🛡️ 2. ZERO-TRUST GLOBAL CONTEXT FETCH & AUTO-LOCK
        // ========================================================
        $.get(apiPrefix + '/context', function(res) {
            globalContext = res;
            console.log("Global Context Loaded:", globalContext);
        });

        $(document).on('click', '.open-assign-modal', function() {
            $('#hiddenAssigneeType').val('App\\Models\\Employee');
            
            $('#modalTitle').html(`<i class="fas fa-user-tie text-primary me-2"></i> Bulk Assign to Staff`);
            $('#targetUserLabel').html(`Target Staff / Employees <span class="text-danger">*</span>`);
            $('#targetDeptLabel').text('Staff Departments');
            $('#targetDesigLabel').text('Staff Designations');

            $('#assignTaskForm')[0].reset();
            $('.select2-multiple').empty().val(null).trigger('change.select2');
            $('.task-row:not(:first)').remove();
            $('.task-phase-dropdown').val(''); 

            // Apply Zero-Trust Lock
            if (globalContext && !globalContext.is_god && globalContext.role_level !== 'ceo') {
                if (globalContext.company_id) {
                    let compOption = new Option("My Company", globalContext.company_id, true, true);
                    $('#companySelect').append(compOption).trigger('change');
                    $('#companySelect').prop('disabled', true); // 🔒 Locked
                }
                if (globalContext.branch_id) {
                    let branchOption = new Option("My Branch", globalContext.branch_id, true, true);
                    $('#branchSelect').append(branchOption).trigger('change');
                    $('#branchSelect').prop('disabled', true); // 🔒 Locked
                }
            } else {
                $('#companySelect, #branchSelect').prop('disabled', false);
            }

            new bootstrap.Modal(document.getElementById('assignTaskModal')).show();
        });

        // ========================================================
        // 🔒 STRICT HIERARCHY LOCK LOGIC (Prevents skipping steps)
        // ========================================================
        function updateDropdownStates() {
            let compVals = $('#companySelect').val();
            let branchVals = $('#branchSelect').val();
            let deptVals = $('#deptSelect').val();
            let desigVals = $('#desigSelect').val();

            // Next box tabhi Enable hoga jab pichla box bhara ho
            $('#branchSelect').prop('disabled', !(compVals && compVals.length > 0));
            $('#deptSelect').prop('disabled', !(branchVals && branchVals.length > 0));
            $('#desigSelect').prop('disabled', !(deptVals && deptVals.length > 0));
            $('#userSelect').prop('disabled', !(desigVals && desigVals.length > 0));
        }

        // Jab modal khule toh check karo
        $('#assignTaskModal').on('shown.bs.modal', function () {
            updateDropdownStates();
        });

        // Jab bhi kisi me kuch Select ya Unselect ho, tab verify karo
        $('#companySelect, #branchSelect, #deptSelect, #desigSelect').on('change', function() {
            updateDropdownStates();
        });


        // ========================================================
        // 🧹 CASCADING CLEAR LOGIC (When a parent is changed/removed)
        // ========================================================
        $('#companySelect').on('select2:unselect', function() {
            $('#branchSelect').empty().val(null).trigger('change');
        });

        $('#branchSelect').on('select2:unselect', function() {
            $('#deptSelect').empty().val(null).trigger('change');
        });

        $('#deptSelect').on('select2:unselect', function() {
            $('#desigSelect').empty().val(null).trigger('change');
        });

        $('#desigSelect').on('select2:unselect', function() {
            $('#userSelect').empty().val(null).trigger('change');
        });

        $(document).on('click', '.btn-clear', function() {
            let target = $(this).data('target');
            $('#' + target).empty().val(null).trigger('change');

            if (target === 'companySelect') {
                $('#branchSelect, #deptSelect, #desigSelect, #userSelect').empty().val(null).trigger('change');
            } else if (target === 'branchSelect') {
                $('#deptSelect, #desigSelect, #userSelect').empty().val(null).trigger('change');
            } else if (target === 'deptSelect') {
                $('#desigSelect, #userSelect').empty().val(null).trigger('change');
            } else if (target === 'desigSelect') {
                $('#userSelect').empty().val(null).trigger('change');
            }
        });
        // ========================================================
        // ⚡ 4. THE 3-LETTER AJAX CASCADING DROPDOWNS
        // ========================================================
        $('#companySelect').select2({
            theme: 'bootstrap-5', width: '100%', minimumInputLength: 3, dropdownParent: $('#companySelect').parent(),
            ajax: {
                url: apiPrefix + '/task-dependencies/companies', 
                dataType: 'json', delay: 250,
                data: function (params) { return { search: params.term }; },
                processResults: function (data) {
                    return { results: $.map(data.data, function(item) { return { id: item.id, text: item.company_name }; })};
                }
            }
        });

        $('#branchSelect').select2({
            theme: 'bootstrap-5', width: '100%', minimumInputLength: 3, dropdownParent: $('#branchSelect').parent(),
            ajax: {
                url: apiPrefix + '/task-dependencies/branches',
                dataType: 'json', delay: 250,
                data: function (params) { return { search: params.term, company_ids: getSelected('companySelect') }; },
                processResults: function (data, params) {
                    let results = [];
                    let selectedCompanies = $('#companySelect').select2('data');

                    // Head Office Suggestion
                    if(params.term && (params.term.toLowerCase().includes('hea') || params.term.toLowerCase().includes('off'))) {
                        selectedCompanies.forEach(comp => { 
                            if(comp.id) results.push({ id: 'HO_' + comp.id, text: 'Head Office (' + comp.text + ')' }); 
                        });
                    }

                    let mapped = $.map(data.data, function(item) { return { id: item.id, text: item.branch_name }; });
                    return { results: results.concat(mapped) };
                }
            }
        });

        $('#deptSelect').select2({
            theme: 'bootstrap-5', width: '100%', minimumInputLength: 3, dropdownParent: $('#deptSelect').parent(),
            ajax: {
                url: apiPrefix + '/task-dependencies/departments',
                dataType: 'json', delay: 250,
                data: function (params) { return { search: params.term, company_ids: getSelected('companySelect'), branch_ids: getSelected('branchSelect') }; },
                processResults: function (data) {
                    return { results: $.map(data.data, function(item) { return { id: item.id, text: item.department_name }; })};
                }
            }
        });

        $('#desigSelect').select2({
            theme: 'bootstrap-5', width: '100%', minimumInputLength: 3, dropdownParent: $('#desigSelect').parent(),
            ajax: {
                url: apiPrefix + '/task-dependencies/designations',
                dataType: 'json', delay: 250,
                data: function (params) { return { search: params.term, company_ids: getSelected('companySelect'), branch_ids: getSelected('branchSelect'), department_ids: getSelected('deptSelect') }; },
                processResults: function (data) {
                    return { results: $.map(data.data, function(item) { return { id: item.id, text: item.designation_name }; })};
                }
            }
        });

        $('#userSelect').select2({
            theme: 'bootstrap-5', width: '100%', minimumInputLength: 3, dropdownParent: $('#userSelect').parent(),
            ajax: {
                // Agar member wala page hoga, to /task-dependencies/members kar dijiyega
                url: apiPrefix + '/task-dependencies/employees',
                dataType: 'json', delay: 250,
                data: function (params) { 
                    return { 
                        search: params.term, 
                        company_ids: getSelected('companySelect'), branch_ids: getSelected('branchSelect'),
                        department_ids: getSelected('deptSelect'), designation_ids: getSelected('desigSelect')
                    }; 
                },
                processResults: function (data) {
                    return { results: $.map(data.data, function(item) {
                        let name = item.full_name || 'Unknown';
                        let idCode = item.member_id ? item.member_id : 'N/A';
                        return { id: item.id, text: `${name} (${idCode})` };
                    })};
                }
            }
        });

        $('#userSelect').on('change', function() {
            let selectedOptions = $(this).find('option:selected');
            let optionsHtml = '';
            selectedOptions.each(function() {
                optionsHtml += `<option value="${$(this).val()}" selected>${$(this).text()}</option>`;
            });
            $('.task-specific-users').each(function() {
                $(this).html(optionsHtml).trigger('change.select2');
            });
        });

        // ========================================================
        // 🚀 5. TRACKING, PHASES & REPEATER
        // ========================================================
        $.get(apiPrefix + '/tracking-modules', function(res) {
            let options = '<option value="">Manual Task (No auto-track)</option>';
            res.data.forEach(mod => { options += `<option value="${mod.id}">Yes, track '${mod.task_category_name}'</option>`; });
            $('.tracking-module-dropdown').html(options);
        });

        function loadTaskPhases() {
            $.ajax({
                url: apiPrefix + '/phases', type: 'GET',
                success: function(res) {
                    let options = '<option value="">-- No Phase / General Task --</option>';
                    if (res.success && res.data) {
                        res.data.forEach(p => {
                            let compName = p.company ? p.company.company_name : '';
                            let label = compName ? `${p.phase_name} (${compName})` : p.phase_name;
                            options += `<option value="${p.id}">${label}</option>`;
                        });
                    }
                    $('.task-phase-dropdown').html(options);
                }
            });
        }
        loadTaskPhases();

        initSelect2($('.task-specific-users').first());
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
            newRow.find('.task-phase-dropdown').val('');
            newRow.find('.target-count-input').val('0');
            newRow.find('.task-provider-dropdown').val(''); // 🔥 YE LINE ADD KAREIN
            newRow.find('.provider-percent-input').val('50');

            let newSpecificSelect = newRow.find('.task-specific-users');
            newSpecificSelect.empty().val(null);
            newSpecificSelect.removeClass('select2-hidden-accessible').removeAttr('data-select2-id tabindex aria-hidden');
            newRow.find('.select2-container').remove();

            newRow.find('.remove-task-row').prop('disabled', false).removeClass('btn-outline-danger').addClass('btn-danger shadow-sm').attr('title', 'Remove this row');
            newRow.hide().appendTo('#tasksRepeaterBody').slideDown(200);

            let selectedOptions = $('#userSelect').find('option:selected');
            let optionsHtml = '';
            selectedOptions.each(function() {
                optionsHtml += `<option value="${$(this).val()}" selected>${$(this).text()}</option>`;
            });
            newSpecificSelect.html(optionsHtml);
            initSelect2(newSpecificSelect);
        });

        $(document).on('click', '.remove-task-row', function() {
            $(this).closest('.task-row').slideUp(200, function() { $(this).remove(); });
        });

      // ========================================================
        // 📤 6. SUBMIT ASSIGN TASK
        // ========================================================
        $('#assignTaskForm').on('submit', function(e) {
            e.preventDefault();
            let globalAssignees = $('#userSelect').val();
            if (!globalAssignees || globalAssignees.length === 0) {
                Swal.fire('Wait!', 'Please select at least one person from the Target list.', 'warning');
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
                let phaseId = row.find('.task-phase-dropdown').val();
                let provId = row.find('.task-provider-dropdown').val(); 
                let provPercent = row.find('.provider-percent-input').val(); 
                let count = row.find('.target-count-input').val();
                let specificUsers = row.find('.task-specific-users').val();
                
                // 🔥 MEMBER OVERRIDE VARIABLES 🔥
                let isOverride = row.find('.override-member-toggle').is(':checked') ? 1 : 0;
                let overMemberId = row.find('.task-override-member').val();
                let overStatus = row.find('.task-override-status').val();

                let assigneesToUse = (specificUsers && specificUsers.length > 0) ? specificUsers : globalAssignees;
                let groupKey = [...assigneesToUse].sort().join(',');

                if (!requestGroups[groupKey]) { requestGroups[groupKey] = { assignee_ids: assigneesToUse, tasks: [] }; }
                
                requestGroups[groupKey].tasks.push({ 
                    title: title, tracking_module_id: trackId, phase_id: phaseId, 
                    provider_id: provId, provider_percent: provPercent, target_count: count,
                    is_member_override: isOverride, override_member_id: overMemberId, override_status: overStatus
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
                    if (t.tracking_module_id) fd.append(`tasks[${i}][tracking_module_id]`, t.tracking_module_id);
                    if (t.phase_id) fd.append(`tasks[${i}][phase_id]`, t.phase_id);
                    if (t.provider_id) fd.append(`tasks[${i}][provider_id]`, t.provider_id); 
                    if (t.provider_percent) fd.append(`tasks[${i}][provider_percent]`, t.provider_percent); 
                    fd.append(`tasks[${i}][target_count]`, t.target_count);
                    
                    // 🔥 MEMBER OVERRIDE APPEND 🔥
                    if (t.is_member_override) {
                        fd.append(`tasks[${i}][is_member_override]`, t.is_member_override);
                        fd.append(`tasks[${i}][override_member_id]`, t.override_member_id);
                        fd.append(`tasks[${i}][override_status]`, t.override_status);
                    }
                });

                if (fileInput.files.length > 0) {
                    for (let i = 0; i < fileInput.files.length; i++) { fd.append('attachments[]', fileInput.files[i]); }
                }

                promises.push($.ajax({ url: apiPrefix + '/tasks', type: 'POST', data: fd, contentType: false, processData: false }));
            });

            Promise.all(promises).then(responses => {
                Swal.fire({ icon: 'success', title: 'Tasks Dispatched!', text: 'Tasks assigned successfully.', timer: 2000, showConfirmButton: false });
                bootstrap.Modal.getInstance(document.getElementById('assignTaskModal')).hide();
                renderTasks();
            }).catch(err => {
                Swal.fire('Error', 'Some tasks failed to assign. Please check your data.', 'error');
            }).finally(() => { btn.html(originalText).prop('disabled', false); });
        });
       // ========================================================
        // 🚀 7. RENDER TASKS (With Fixed Checkbox & Filter Data)
        // ========================================================
        function renderTasks() {
            $('#taskBoard').html('<div class="col-12 text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-muted mb-3"></i><p class="text-muted fw-bold" id="loadingText">Syncing latest tasks...</p></div>');

            let apiUrl = apiPrefix + '/tasks?type=App\\Models\\Employee';

            $.ajax({
                url: apiUrl,
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    try {
                        if (!res || !res.data || !Array.isArray(res.data) || res.data.length === 0) {
                            $('#taskBoard').html('<div class="col-12 text-center text-muted py-5"><i class="fas fa-check-circle fa-3x mb-3 text-success"></i><h5>No active tasks found today!</h5></div>');
                            return;
                        }

                        let html = `
                        <div class="col-12 mb-3 text-end">
                            <button class="btn btn-sm btn-outline-primary fw-bold shadow-sm me-2" id="expandAllBtn"><i class="fas fa-expand-arrows-alt me-1"></i> Expand All</button>
                            <button class="btn btn-sm btn-outline-secondary fw-bold shadow-sm" id="collapseAllBtn"><i class="fas fa-compress-arrows-alt me-1"></i> Collapse All</button>
                        </div>`;

                        let groupedTasks = {};
                        res.data.forEach(task => {
                            let assigneeId = task.assignee_id || 'unassigned';
                            let assigneeName = 'Unassigned';
                            let cId = '', bId = '', dId = '', dsId = '';
                            
                            if (task.assignee) {
                                assigneeName = task.assignee.full_name || task.assignee.name || 'Unknown User';
                                cId = task.assignee.company_id || '';
                                bId = task.assignee.branch_id || '';
                                dId = task.assignee.department_id || '';
                                dsId = task.assignee.designation_id || '';
                            }
                            if (!groupedTasks[assigneeId]) {
                                groupedTasks[assigneeId] = { name: assigneeName, tasks: [], unreadCount: 0, cId: cId, bId: bId, dId: dId, dsId: dsId };
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

                            // 🔥 CHECK PERMISSION ONCE FOR THIS EMPLOYEE'S TASKS 🔥
                            let isDeletePermitted = window.userGodMode || (window.userPerms && (window.userPerms.includes('task_delete') || window.userPerms.includes('task_mem_delete')));
                            let empSelectAllHtml = isDeletePermitted ? `<input type="checkbox" class="form-check-input emp-select-all" data-emp="${empId}" style="cursor:pointer; width:18px; height:18px;" onclick="event.stopPropagation();">` : '';

                            emp.tasks.forEach(task => {
                                let isUnread = false;
                                let unreadMsgCount = 0;

                                if (task.progress_logs && task.progress_logs.length > 0) {
                                    let lastLog = task.progress_logs[0];
                                    let actorName = lastLog.actor ? (lastLog.actor.full_name || lastLog.actor.name) : 'System';
                                    if (actorName !== 'System' && actorName !== 'System/Admin' && actorName !== loggedInUserName) {
                                        let lastReadLogId = localStorage.getItem('task_read_' + task.id);
                                        if (lastReadLogId != lastLog.id) {
                                            isUnread = true; unreadMsgCount = 1; emp.unreadCount += 1;
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
                                if (task.phase) { targetText += ` &nbsp;|&nbsp; <i class="fas fa-building text-info"></i> ${task.phase.phase_name}`; }

                                let assignedDateText = task.created_at ? new Date(task.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : 'Unknown';
                                let dueDateText = task.due_datetime ? new Date(task.due_datetime).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : 'No Due Date';

                                let checkboxHtml = isDeletePermitted ? `<input type="checkbox" class="form-check-input mt-1 task-checkbox emp-${empId}-task" value="${task.id}" data-emp="${empId}" style="cursor:pointer;">` : '';

                                taskRowsHtml += `
                                <div class="row align-items-center border-bottom p-3 m-0 task-row-hover task-row-item ${unreadRowClass}">
                                    <div class="col-12 col-md-4 col-lg-4 mb-2 mb-md-0 d-flex align-items-start gap-2">
                                        ${checkboxHtml}
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
                                    <div class="col-12 col-md-3 col-lg-3 mb-3 mb-md-0" ${targetCount === 0 ? 'style="visibility:hidden;"' : ''}>
                                        <div class="d-flex justify-content-between small text-muted fw-bold mb-1">
                                            <span>Progress</span><span class="text-${statusColor}">${progress}%</span>
                                        </div>
                                        <div class="progress shadow-sm" style="height: 6px;">
                                            <div class="progress-bar bg-${statusColor}" role="progressbar" style="width: ${progress}%;"></div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-3 col-lg-3 text-md-end d-flex gap-1 justify-content-md-end">
                                        <button class="btn btn-sm btn-light border text-primary shadow-sm view-task-btn" data-id="${task.id}" title="View Details"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-light border text-success shadow-sm edit-task-btn secured-item" data-permission="task_edit" data-id="${task.id}" title="Edit Task"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-light border text-danger shadow-sm delete-task-btn secured-item" data-permission="task_delete" data-id="${task.id}" title="Delete Task"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                </div>`;
                            });

                            let empBadgeClass = emp.unreadCount > 0 ? 'blink-anim' : 'd-none';
                            let empBadgeText = emp.unreadCount > 0 ? `${emp.unreadCount} New Msg` : '0 Msg';

                            // Data attributes add kiye taaki filters kaam kar sakein
                            html += `
                            <div class="col-12 mb-4 emp-card-wrapper" data-company="${emp.cId}" data-branch="${emp.bId}" data-dept="${emp.dId}" data-desig="${emp.dsId}">
                                <div class="card border-0 shadow-sm overflow-hidden" style="border-top: 4px solid #1A365D;">
                                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center collapse-trigger collapsed" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="false" style="cursor:pointer;">
                                        <div class="d-flex align-items-center gap-2">
                                            ${empSelectAllHtml}
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
                                    <div id="${collapseId}" class="collapse task-collapse"><div class="card-body p-0">${taskRowsHtml}</div></div>
                                </div>
                            </div>`;
                        });

                        $('#taskBoard').html(html);
                        if (typeof window.applyPermissions === 'function') window.applyPermissions();

                    } catch (jsError) {
                        $('#taskBoard').html(`<div class="col-12 text-center py-5 text-danger"><h5>Frontend Rendering Crashed!</h5><p>${jsError.message}</p></div>`);
                    }
                },
                error: function(xhr, status, error) {
                    $('#taskBoard').html(`<div class="col-12 text-center py-5 text-danger"><i class="fas fa-wifi fa-3x mb-3"></i><h5>Failed to load tasks!</h5><button class="btn btn-sm btn-outline-secondary mt-2" onclick="location.reload()">Refresh Page</button></div>`);
                }
            });
        }
        $(document).on('click', '#expandAllBtn', function() { $('.task-collapse').collapse('show'); });
        $(document).on('click', '#collapseAllBtn', function() { $('.task-collapse').collapse('hide'); });

        $('#liveSearchTasks').on('keyup', function() {
            let keyword = $(this).val().toLowerCase();
            $('.emp-card-wrapper').each(function() {
                let employeeName = $(this).find('.emp-name-text').text().toLowerCase();
                let matchFoundInCard = false;
                $(this).find('.task-row-item').each(function() {
                    let taskTitle = $(this).find('.task-title-text').text().toLowerCase();
                    if (employeeName.includes(keyword) || taskTitle.includes(keyword)) {
                        $(this).show(); matchFoundInCard = true;
                    } else { $(this).hide(); }
                });
                if (matchFoundInCard) { $(this).show(); } else { $(this).hide(); }
            });
        });

      // ========================================================
        // 🔍 TOP ROW SELECT FILTERS LOGIC (Fixed Head Office)
        // ========================================================
        function initTopFilters() {
            let ajaxConfig = (urlExt, dependFunc) => {
                return {
                    url: apiPrefix + urlExt, dataType: 'json', delay: 250,
                    data: function(p) { let d = {search: p.term}; if(dependFunc) Object.assign(d, dependFunc()); return d; },
                    processResults: function(data) { return { results: $.map(data.data, function(i) { return { id: i.id, text: i.name || i.company_name || i.branch_name || i.department_name || i.designation_name }; }) }; }
                };
            };

            $('#filterCompany').select2({ theme: 'bootstrap-5', allowClear: true, placeholder: 'All Companies', ajax: ajaxConfig('/task-dependencies/companies') });
            
            // 🔥 FIX: Branch Filter Custom Head Office Logic 🔥
            $('#filterBranch').select2({ 
                theme: 'bootstrap-5', allowClear: true, placeholder: 'All Branches', 
                ajax: {
                    url: apiPrefix + '/task-dependencies/branches', dataType: 'json', delay: 250,
                    data: function(p) { return { search: p.term, company_ids: $('#filterCompany').val() }; },
                    processResults: function(data, params) {
                        let results = [];
                        let selectedCompany = $('#filterCompany').select2('data');
                        
                        // Inject "Head Office" manually if searched
                        if (params.term && (params.term.toLowerCase().includes('hea') || params.term.toLowerCase().includes('off'))) {
                            if (selectedCompany && selectedCompany.length > 0) {
                                selectedCompany.forEach(comp => {
                                    if(comp.id) results.push({ id: 'HO_' + comp.id, text: 'Head Office (' + comp.text + ')' });
                                });
                            } else {
                                results.push({ id: 'HO_ALL', text: 'Head Office (All)' }); // Default fallback
                            }
                        }
                        
                        let mapped = $.map(data.data, function(i) { return { id: i.id, text: i.branch_name }; });
                        return { results: results.concat(mapped) };
                    }
                }
            });

            $('#filterDepartment').select2({ theme: 'bootstrap-5', allowClear: true, placeholder: 'All Departments', ajax: ajaxConfig('/task-dependencies/departments', () => ({company_ids: $('#filterCompany').val(), branch_ids: $('#filterBranch').val()})) });
            $('#filterDesignation').select2({ theme: 'bootstrap-5', allowClear: true, placeholder: 'All Designations', ajax: ajaxConfig('/task-dependencies/designations', () => ({company_ids: $('#filterCompany').val(), branch_ids: $('#filterBranch').val(), department_ids: $('#filterDepartment').val()})) });

            $('#filterCompany, #filterBranch, #filterDepartment, #filterDesignation').on('change', function() {
                let cId = $('#filterCompany').val();
                let bId = $('#filterBranch').val();
                let dId = $('#filterDepartment').val();
                let dsId = $('#filterDesignation').val();

                $('.emp-card-wrapper').each(function() {
                    let match = true;
                    let cardC = String($(this).data('company') || '');
                    let cardB = String($(this).data('branch') || '');
                    let cardD = String($(this).data('dept') || '');
                    let cardDs = String($(this).data('desig') || '');

                    if (cId && cardC !== String(cId)) match = false;
                    
                    // 🔥 FIX: Check if selected branch is Head Office (HO) 🔥
                    if (bId) {
                        if (String(bId).startsWith('HO_')) {
                            // Employee ka branch null ya empty hona chahiye head office ke liye
                            if (cardB !== '' && cardB !== 'null' && cardB !== '0') match = false;
                        } else {
                            if (cardB !== String(bId)) match = false;
                        }
                    }
                    
                    if (dId && cardD !== String(dId)) match = false;
                    if (dsId && cardDs !== String(dsId)) match = false;

                    if (match) $(this).show(); else $(this).hide();
                });
            });
        }
        
        initTopFilters();
        
       

        // ========================================================
        // 💬 8. CHAT / VIEW MODAL LOGIC (With Edit, Delete, Reply & Preview)
        // ========================================================
        
        // Auto-resize Textarea
        $(document).on('input', '#chatMessageInput', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // File Thumbnail Preview Logic
        $(document).on('change', '#chatFileInput', function(e) {
            let container = $('#filePreviewContainer');
            container.empty();
            let files = e.target.files;
            
            if(files.length > 0) {
                container.removeClass('d-none');
                Array.from(files).forEach((file, index) => {
                    let url = URL.createObjectURL(file);
                    let html = `<div class="position-relative border rounded shadow-sm d-flex align-items-center justify-content-center bg-light" style="width: 50px; height: 50px; overflow: hidden;">`;
                    
                    if(file.type.startsWith('image/')) {
                        html += `<img src="${url}" class="img-fluid" style="object-fit: cover; width:100%; height:100%;">`;
                    } else if(file.type.startsWith('video/')) {
                        html += `<i class="fas fa-file-video fa-2x text-primary"></i>`;
                    } else if(file.type === 'application/pdf') {
                        html += `<i class="fas fa-file-pdf fa-2x text-danger"></i>`;
                    } else {
                        html += `<i class="fas fa-file fa-2x text-secondary"></i>`;
                    }
                    html += `</div>`;
                    container.append(html);
                });
            } else {
                container.addClass('d-none');
            }
        });

        function forceScrollToBottom() {
            let scrollArea = document.getElementById('taskModalScrollArea');
            if (scrollArea) { scrollArea.scrollTop = scrollArea.scrollHeight; }
        }

       $('#taskDetailsModal').on('shown.bs.modal', function() { 
    forceScrollToBottom(); 
    
    let descEl = document.getElementById('detailTaskDesc');
    if (descEl) {
        // Mobile screen par line rendering height match karne ke liye 80px check
        if (descEl.scrollHeight > 80) {
            $('#toggleDescBtn').removeClass('d-none').show().html('Read More <i class="fas fa-chevron-down"></i>');
        } else {
            $('#toggleDescBtn').hide();
            $('#detailTaskDesc').removeClass('desc-collapse');
        }
    }
});


// 1. Initialize Member Search for Override
        function initOverrideSelect2(row) {
            let selectEl = row.find('.task-override-member');
            selectEl.select2({
                theme: 'bootstrap-5', 
                width: '100%', 
                minimumInputLength: 3, 
                dropdownParent: selectEl.parent(), // 🔥 YAHAN FIX HAI: Ab ye ud kar upar nahi jayega
                ajax: {
                    url: apiPrefix + '/task-dependencies/members', dataType: 'json', delay: 250,
                    data: function (params) { return { search: params.term }; },
                    processResults: function (data) {
                        return { results: $.map(data.data, function(item) { return { id: item.member_id, text: item.full_name + ' (' + item.member_id + ')' }; })};
                    }
                }
            });
        }
        initOverrideSelect2($('.task-row').first()); // Initialize first row

        // 2. Toggle Override Section
        $(document).on('change', '.override-member-toggle', function() {
            let row = $(this).closest('.task-row');
            if($(this).is(':checked')) {
                row.find('.override-section').removeClass('d-none').hide().slideDown(200);
            } else {
                row.find('.override-section').slideUp(200, function() { $(this).addClass('d-none'); });
                row.find('.task-override-member').val(null).trigger('change');
            }
        });

        // 3. View Summary Button Click
        $(document).on('click', '.view-summary-btn', function() {
            let row = $(this).closest('.task-row');
            let memberId = row.find('.task-override-member').val();
            if(!memberId) { Swal.fire('Wait!', 'Please select a Member first!', 'warning'); return; }

            let btn = $(this);
            let ogText = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            $.get(apiPrefix + '/interested-customers/member-summary/' + memberId, function(res) {
                let html = '<ul class="list-group text-start mt-3">';
                let total = 0;
                if(res.data && res.data.length > 0) {
                    res.data.forEach(item => {
                        html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                                    ${item.status} <span class="badge bg-primary rounded-pill">${item.total}</span>
                                </li>`;
                        total += item.total;
                    });
                } else { html += '<li class="list-group-item text-danger">No leads found for this member.</li>'; }
                html += `</ul><h5 class="mt-3 text-dark fw-bold">Total Available: ${total}</h5>`;

                Swal.fire({ title: 'Lead Summary', html: html, icon: 'info' });
            }).always(function() { btn.html(ogText).prop('disabled', false); });
        });

       



       function renderTimelineToModal(task) {
            let timelineHtml = '<div class="chat-container">';
            
            // 🔥 SUPERUSER CHECK 🔥
            let myModelClass = 'App\\Models\\Employee';
            let isSuperUser = window.userGodMode || false;

            if(window.location.pathname.includes('/admin') || window.location.pathname.includes('/ceo') || window.location.pathname.includes('/director')) {
                myModelClass = 'App\\Models\\User';
                isSuperUser = true; // Full access for Admin, CEO, Director, Developer
            } else if(window.location.pathname.includes('/member')) {
                myModelClass = 'App\\Models\\Member';
            }
            
            let myUserIdentifier = myModelClass + '_' + window.userId;
            let currentTime = new Date().getTime(); // Current browser time for 5 min math

            if (task.progress_logs && task.progress_logs.length > 0) {
                let sortedLogs = [...task.progress_logs].reverse();
                
                sortedLogs.forEach(log => {
                    let deletedFor = log.deleted_for ? (typeof log.deleted_for === 'string' ? JSON.parse(log.deleted_for) : log.deleted_for) : [];
                    if (deletedFor.includes(myUserIdentifier)) return; // Skip rendering this message

                    let isSystem = !log.actor_id || log.actor_id === 0 || log.actor_type === null;
                    let actorName = log.actor ? (log.actor.full_name || log.actor.member_name || log.actor.name) : 'System/Admin';
                    
                    let logIdentifier = log.actor_type + '_' + log.actor_id;
                    let isMe = (logIdentifier === myUserIdentifier) && !isSystem;

                    let logDateObj = new Date(log.created_at);
                    let logDate = logDateObj.toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });

                    // Time difference in minutes
                    let diffMinutes = (currentTime - logDateObj.getTime()) / 60000;

                    let bubbleClass = isMe ? 'right' : 'left';
                    let messageContent = log.message_or_remark;
                    let attachmentsHtml = '';
                    let actionMenu = '';

                    // Construct files first
                    if (task.attachments) {
                        let logFiles = task.attachments.filter(f => f.task_progress_log_id == log.id);
                        if (logFiles.length > 0) {
                            attachmentsHtml += `<div class="mt-2 mb-1 d-flex flex-wrap gap-2">`;
                            logFiles.forEach(f => {
                                attachmentsHtml += `<a href="/${f.file_path}" target="_blank" class="badge bg-white text-primary border border-info border-opacity-25 text-decoration-none p-1 px-2 shadow-sm"><i class="fas fa-paperclip me-1"></i> View File</a>`;
                            });
                            attachmentsHtml += `</div>`;
                        }
                    }

                    // 🔥 SOFT DELETE DISPLAY LOGIC 🔥
                    if (log.is_deleted) {
                        if (isSuperUser) {
                            // Admin view: Can see who deleted it and the original content
                           messageContent = `<span class="badge bg-danger mb-2 opacity-75"><i class="fas fa-trash-alt me-1"></i>Deleted</span><br><span class="text-muted" style="white-space:pre-wrap;">${messageContent}</span>`;
                            if (log.is_edited) messageContent += ` <small class="text-muted fst-italic" style="font-size:10px;">(edited)</small>`;
                        } else {
                            // Employee/Member view: Strict hidden view
                            messageContent = `<span class="text-muted fst-italic"><i class="fas fa-ban me-1"></i> This message was deleted</span>`;
                            attachmentsHtml = ''; // Hide attachments for regular users
                        }
                    } else {
                        // Normal visible message
                        if (log.is_edited) messageContent += ` <small class="text-muted fst-italic" style="font-size:10px;">(edited)</small>`;

                        let deleteEveryoneOption = (isMe || isSuperUser) ? `<li><a class="dropdown-item delete-chat-btn" href="#" data-id="${log.id}" data-type="for_everyone"><i class="fas fa-trash-alt me-2 text-danger"></i> Delete for Everyone</a></li>` : '';
                        
                        // 🔥 5-MINUTE EDIT TIME LOGIC 🔥
                        let editOption = '';
                        if (isMe) {
                            if (isSuperUser || diffMinutes <= 5) {
                                editOption = `<li><a class="dropdown-item edit-chat-btn" href="#" data-id="${log.id}" data-msg="${log.message_or_remark.replace(/"/g, '&quot;')}"><i class="fas fa-pen me-2 text-primary"></i> Edit</a></li>`;
                            }
                        }

                        if(!isSystem) {
                            actionMenu = `
                            <div class="dropdown position-absolute" style="top: 8px; right: 8px;">
                                <a href="#" class="text-muted text-decoration-none" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v px-2"></i></a>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 12px; min-width: 140px;">
                                    <li><a class="dropdown-item reply-chat-btn" href="#" data-actor="${actorName}" data-msg="${log.message_or_remark.replace(/"/g, '&quot;')}"><i class="fas fa-reply me-2 text-info"></i> Reply</a></li>
                                    ${editOption}
                                    ${(isMe || isSuperUser) ? `<li><hr class="dropdown-divider m-1"></li>` : ''}
                                    ${isMe ? `<li><a class="dropdown-item delete-chat-btn" href="#" data-id="${log.id}" data-type="for_me"><i class="fas fa-trash me-2 text-warning"></i> Delete for Me</a></li>` : ''}
                                    ${deleteEveryoneOption}
                                </ul>
                            </div>`;
                        }
                    }

                    timelineHtml += `
                    <div class="chat-bubble ${bubbleClass} pr-4">
                        ${actionMenu}
                        <div class="d-flex justify-content-between mb-1">
                            <strong class="small" style="color: #1A365D;"><i class="fas fa-user-circle me-1"></i> ${actorName}</strong>
                        </div>
                        <p class="small text-dark fw-medium mb-1">${messageContent}</p>
                        ${attachmentsHtml}
                        <div class="text-end mt-1"><small class="text-muted" style="font-size: 10px;">${logDate} &nbsp; <span class="badge bg-light text-dark border p-1" style="font-size: 8px;">${log.log_type}</span></small></div>
                    </div>`;
                });
            } else {
                timelineHtml += '<div class="text-center text-muted small py-3 w-100">No updates yet. Start the conversation!</div>';
            }
            timelineHtml += '</div>';
            $('#detailTimeline').html(timelineHtml);
            forceScrollToBottom();
        }

        $(document).on('click', '.view-task-btn', function() {
            let taskId = $(this).data('id');
            let btn = $(this);
            let originalText = btn.html();

            let taskBadge = $('#unread-badge-' + taskId);
            if (taskBadge.length && !taskBadge.hasClass('d-none')) {
                let taskCount = parseInt(taskBadge.attr('data-count')) || 0;
                let empId = taskBadge.data('emp');
                
                // Remove blink and hide badge completely
                taskBadge.attr('data-count', 0).removeClass('blink-anim').addClass('d-none');
                btn.closest('.task-row-item').removeClass('unread-task-row');
                
                if (empId) {
                    let empBadge = $('#unread-emp-' + empId);
                    if (empBadge.length) {
                        let empCount = parseInt(empBadge.attr('data-count')) || 0;
                        empCount = Math.max(0, empCount - taskCount);
                        empBadge.attr('data-count', empCount);
                        if (empCount === 0) {
                            empBadge.removeClass('blink-anim').addClass('d-none');
                        } else {
                            empBadge.text(empCount + ' New Msg');
                        }
                    }
                }
            }

            btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            $.get(apiPrefix + '/tasks/' + taskId, function(res) {
                let task = res.data;
                // Update Local Storage Immediately
                if (task.progress_logs && task.progress_logs.length > 0) {
                    localStorage.setItem('task_read_' + task.id, task.progress_logs[0].id);
                }

                $('#detailTaskTitle').text(task.title);
                $('#detailTaskPriority').text('Priority: ' + task.priority).removeClass().addClass('badge bg-' + (task.priority === 'Urgent' ? 'danger' : (task.priority === 'High' ? 'warning' : 'info')));
                $('#detailTaskStatus').text('Status: ' + task.status).removeClass().addClass('badge bg-' + (task.status === 'Completed' ? 'success' : (task.status === 'In-Progress' ? 'primary' : 'warning')));
                
                let dDue = task.due_datetime ? new Date(task.due_datetime) : null;
                $('#detailTaskDue').text(dDue ? dDue.toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true }) : 'No Deadline');
                $('#detailTaskAssignee').text(task.assignee ? task.assignee.full_name : 'Unknown');
                
                // Instructions set karein, par height check baad me hoga
                $('#detailTaskDesc').text(task.description || 'No description provided.').addClass('desc-collapse').removeClass('expanded');
                $('#toggleDescBtn').hide(); // By default hide rakhein
                setTimeout(() => {
                    let descEl = document.getElementById('detailTaskDesc');
                    if (descEl && descEl.scrollHeight > 100) {
                        $('#toggleDescBtn').show().html('Read More <i class="fas fa-chevron-down"></i>');
                    } else {
                        $('#toggleDescBtn').hide();
                        $('#detailTaskDesc').removeClass('desc-collapse');
                    }
                }, 50);

                if (task.target_count > 0) {
                    $('#detailProgressBox').show();
                    let percent = Math.min((task.achieved_count / task.target_count) * 100, 100).toFixed(0);
                    $('#detailProgressText').text(`${task.achieved_count} / ${task.target_count}`);
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
                            attachmentsHtml += `<a href="/${file.file_path}" target="_blank" class="badge bg-light text-primary border text-decoration-none py-2 px-3 shadow-sm"><i class="fas fa-file-download me-1"></i> ${file.file_name}</a>`;
                        }
                    });
                }
                if (hasInitialFiles) { $('#detailAttachmentsBox').show(); $('#detailAttachmentsContainer').html(attachmentsHtml); } 
                else { $('#detailAttachmentsBox').hide(); }

                renderTimelineToModal(task);
                $('#replyTaskId').val(task.id);
                $('#replyTaskStatus').val(task.status);

                // Live Chat Sync with LocalStorage
                if (typeof window.Echo !== 'undefined') {
                    if (window.currentChatChannel) window.Echo.leave(window.currentChatChannel);
                    window.currentChatChannel = `task.${task.id}`;
                    window.Echo.private(window.currentChatChannel).listen('.message.sent', (e) => {
                        $.get(apiPrefix + '/tasks/' + task.id, function(freshRes) {
                            renderTimelineToModal(freshRes.data);
                            
                            // Agar modal open hai usi task ka, toh automatically "Read" mark kar do (LocalStorage update)
                            if ($('#taskDetailsModal').hasClass('show') && $('#replyTaskId').val() == task.id) {
                                if (freshRes.data.progress_logs && freshRes.data.progress_logs.length > 0) {
                                    localStorage.setItem('task_read_' + task.id, freshRes.data.progress_logs[0].id);
                                }
                            } else {
                                // Agar modal band hai, tabhi blink alert dikhao
                                window.markTaskAsUnread(task.id);
                            }
                        });
                    });
                }
                new bootstrap.Modal(document.getElementById('taskDetailsModal')).show();

            }).always(function() { btn.html(originalText).prop('disabled', false); });
        });

        // Expand/Collapse Toggle Button
        $(document).on('click', '#toggleDescBtn', function(e) {
            e.preventDefault();
            let descBox = $('#detailTaskDesc');
            descBox.toggleClass('expanded');
            if (descBox.hasClass('expanded')) {
                $(this).html('Show Less <i class="fas fa-chevron-up"></i>');
            } else {
                $(this).html('Read More <i class="fas fa-chevron-down"></i>');
            }
        });

        // Reply Click Handler
        $(document).on('click', '.reply-chat-btn', function(e) {
            e.preventDefault();
            let actor = $(this).data('actor');
            let msg = $(this).data('msg') || '';
            let shortMsg = msg.length > 50 ? msg.substring(0, 50) + "..." : msg;
            let quoteText = `[Replying to @${actor}: "${shortMsg}"]\n\n`;
            
            let inputField = $('#chatMessageInput');
            inputField.val(quoteText + inputField.val()).focus().trigger('input');
        });

        // 🔥 FIX: PROMISE-BASED EDIT LOGIC 🔥
        $(document).on('click', '.edit-chat-btn', function(e) {
            e.preventDefault();
            let logId = $(this).data('id');
            let oldMsg = $(this).data('msg');
            
            $('#taskDetailsModal').removeAttr('tabindex'); // Focus trap fix
            
            Swal.fire({
                title: 'Edit Message',
                input: 'textarea',
                inputValue: oldMsg,
                inputAttributes: { rows: 3 },
                showCancelButton: true,
                confirmButtonText: 'Save',
                showLoaderOnConfirm: true,
                didClose: () => { $('#taskDetailsModal').attr('tabindex', '-1'); },
                 // 🔥 YEH LINE ADD KI GAYI HAI BOOTSTRAP FOCUS TRAP TODNE KE LIYE 🔥
                target: document.getElementById('taskDetailsModal'),
                preConfirm: (newMsg) => {
                    if (!newMsg || newMsg.trim() === '') { Swal.showValidationMessage('Message cannot be empty'); return false;}
                    let socketId = (typeof window.Echo !== 'undefined' && window.Echo.socketId()) ? window.Echo.socketId() : '';
                    
                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url: apiPrefix + `/tasks/logs/${logId}/edit`,
                            type: 'POST',
                            data: { message_or_remark: newMsg },
                            headers: { 'X-Socket-ID': socketId }, // Prevent echo back to self
                            success: function(res) { resolve(res); },
                            error: function(xhr) { reject(xhr.responseJSON?.message || 'Failed to update message'); }
                        });
                    }).catch(error => { Swal.showValidationMessage(error); });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    let taskId = $('#replyTaskId').val();
                    $.get(apiPrefix + '/tasks/' + taskId, function(freshRes) {
                        renderTimelineToModal(freshRes.data);
                    });
                }
            });
        });

        // 🔥 FIX: INSTANT DELETE LOGIC 🔥
        $(document).on('click', '.delete-chat-btn', function(e) {
            e.preventDefault();
            let logId = $(this).data('id');
            let delType = $(this).data('type');
            let confirmText = delType === 'for_everyone' ? 'Delete for everyone?' : 'Delete for me?';

            Swal.fire({
                title: 'Are you sure?',
                text: confirmText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, Delete!'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
                    
                    let socketId = (typeof window.Echo !== 'undefined' && window.Echo.socketId()) ? window.Echo.socketId() : '';

                    $.ajax({
                        url: apiPrefix + `/tasks/logs/${logId}/delete`,
                        type: 'POST',
                        data: { delete_type: delType },
                        headers: { 'X-Socket-ID': socketId }, // Prevent echo back to self
                        success: function(res) {
                            if(res.success) {
                                Swal.close();
                                let taskId = $('#replyTaskId').val();
                                $.get(apiPrefix + '/tasks/' + taskId, function(freshRes) {
                                    renderTimelineToModal(freshRes.data);
                                });
                            } else {
                                Swal.fire('Error', res.message || 'Unable to delete', 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.message || 'Action failed.', 'error');
                        }
                    });
                }
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

            let socketId = (typeof window.Echo !== 'undefined' && window.Echo.socketId()) ? window.Echo.socketId() : '';

            $.ajax({
                url: apiPrefix + '/tasks/' + taskId + '/reply', type: 'POST', data: formData,
                contentType: false, processData: false, headers: { 'X-Socket-ID': socketId },
                success: function(res) {
                    form[0].reset(); $('#replyTaskId').val(taskId);
                    $('#filePreviewContainer').empty().addClass('d-none');
                    $('#chatMessageInput').css('height', 'auto');

                    $.get(apiPrefix + '/tasks/' + taskId, function(freshRes) {
                        let freshTask = freshRes.data;
                        if (freshTask.progress_logs && freshTask.progress_logs.length > 0) { localStorage.setItem('task_read_' + freshTask.id, freshTask.progress_logs[0].id); }
                        $('#detailTaskStatus').text('Status: ' + freshTask.status).removeClass().addClass('badge bg-' + (freshTask.status === 'Completed' ? 'success' : (freshTask.status === 'In-Progress' ? 'primary' : 'warning')));
                        renderTimelineToModal(freshTask);
                    });
                    renderTasks();
                },
                error: function(err) { Swal.fire('Error', 'Failed to send message.', 'error'); },
                complete: function() { btn.html(originalText).prop('disabled', false); }
            });
        });

        // --------------------------------------------------------
        // Task Card Delete / Edit Handlers
        // --------------------------------------------------------
        $(document).on('click', '.delete-task-btn', function() {
            let taskId = $(this).data('id');
            Swal.fire({
                title: 'Are you sure?', text: "You won't be able to revert this! The task and all its history will be deleted.",
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: apiPrefix + '/tasks/' + taskId, type: 'DELETE',
                        success: function(res) { Swal.fire('Deleted!', res.message, 'success'); renderTasks(); }
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
                $('#editPhaseSelect').val(task.phase_id || '');
                $('#editProviderSelect').val(task.provider_id || ''); 
                $('#editProviderPercentInput').val(task.provider_percent || 50); // 🔥 ADD THIS
                $('#editTargetCountInput').val(task.target_count);
                $('#editPrioritySelect').val(task.priority);
                if (task.due_datetime) {
                    let d = new Date(task.due_datetime);
                    let formatted = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0') + 'T' + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
                    $('#editDueDatetimeInput').val(formatted);
                } else {
                    $('#editDueDatetimeInput').val('');
                }
                new bootstrap.Modal(document.getElementById('editTaskModal')).show();
            }).always(function() { btn.html(originalHtml).prop('disabled', false); });
        });

        $('#editTaskForm').on('submit', function(e) {
            e.preventDefault();
            let taskId = $('#editTaskId').val();
            let formData = new FormData(this);
            let btn = $('#submitEditTaskBtn');
            let originalHtml = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...').prop('disabled', true);
            $.ajax({
                url: apiPrefix + '/tasks/' + taskId, type: 'POST', data: formData, contentType: false, processData: false,
                success: function(res) {
                    Swal.fire({ icon: 'success', title: 'Updated!', text: res.message, timer: 2000, showConfirmButton: false });
                    bootstrap.Modal.getInstance(document.getElementById('editTaskModal')).hide();
                    renderTasks();
                },
                complete: function() { btn.html(originalHtml).prop('disabled', false); }
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
            $('.task-checkbox:checked').each(function() { selectedIds.push($(this).val()); });
            if (selectedIds.length === 0) return;
            Swal.fire({
                title: 'Delete ' + selectedIds.length + ' Tasks?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, delete all!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: apiPrefix + '/bulk-delete', type: 'POST', data: { table_name: 'tasks', ids: selectedIds },
                        success: function(res) { $('#bulkDeleteZone').attr('style', 'display: none !important;'); renderTasks(); }
                    });
                }
            });
        });


        





        renderTasks(); // Initialize
    });
</script>
@endpush