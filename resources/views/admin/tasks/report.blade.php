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

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
            color: rgba(255, 255, 255, 0.8) !important;
            margin-right: 6px !important;
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

        .toggle-icon {
            transition: transform 0.3s ease;
        }

        .card-header[aria-expanded="false"] .toggle-icon {
            transform: rotate(180deg);
        }

        .card-header[aria-expanded="true"] .toggle-icon {
            transform: rotate(0deg);
        }

        .chat-box {
            background: #f1f3f5;
            border-radius: 8px;
            padding: 10px;
            margin-top: 8px;
            border-left: 3px solid #0dcaf0;
        }

        .chat-msg {
            font-size: 12px;
            margin-bottom: 5px;
            border-bottom: 1px dashed #dee2e6;
            padding-bottom: 5px;
        }

        .chat-msg:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
    </style>

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-0"><i class="fas fa-chart-pie text-success me-2"></i> Employee Performance
                    Report</h4>
                <small class="text-muted">Analyze tasks, targets, and employee chat logs.</small>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success fw-bold shadow-sm" id="exportExcelBtn" style="display: none;">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </button>
                <a href="{{ route('admin.tasks') }}" class="btn btn-light border shadow-sm fw-bold">
                    <i class="fas fa-arrow-left me-1"></i> Tasks Center
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4" style="border-top: 4px solid #198754;">
            <div class="card-body bg-light">
                <form id="filterReportForm">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="small fw-bold text-muted mb-1">Start Date</label>
                            <input type="date" name="start_date" id="filterStartDate"
                                class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold text-muted mb-1">End Date</label>
                            <input type="date" name="end_date" id="filterEndDate" class="form-control form-control-sm"
                                required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <label class="small fw-bold text-dark mb-0">Companies</label>
                                <div class="filter-actions"><a class="text-primary btn-all"
                                        data-target="companySelect">All</a> | <a class="text-danger btn-clear"
                                        data-target="companySelect">Clear</a></div>
                            </div>
                            <select class="select2-multiple" name="company_ids" id="companySelect" multiple
                                data-placeholder="Select Companies..."></select>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <label class="small fw-bold text-dark mb-0">Branches / HO</label>
                                <div class="filter-actions"><a class="text-primary btn-all"
                                        data-target="branchSelect">All</a> | <a class="text-danger btn-clear"
                                        data-target="branchSelect">Clear</a></div>
                            </div>
                            <select class="select2-multiple" name="branch_ids" id="branchSelect" multiple
                                data-placeholder="Select Branches..."></select>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <label class="small fw-bold text-dark mb-0">Departments</label>
                                <div class="filter-actions"><a class="text-primary btn-all" data-target="deptSelect">All</a>
                                    | <a class="text-danger btn-clear" data-target="deptSelect">Clear</a></div>
                            </div>
                            <select class="select2-multiple" name="department_ids" id="deptSelect" multiple
                                data-placeholder="Select Depts..."></select>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <label class="small fw-bold text-dark mb-0">Designations</label>
                                <div class="filter-actions"><a class="text-primary btn-all"
                                        data-target="desigSelect">All</a> | <a class="text-danger btn-clear"
                                        data-target="desigSelect">Clear</a></div>
                            </div>
                            <select class="select2-multiple" name="designation_ids" id="desigSelect" multiple
                                data-placeholder="Select Roles..."></select>
                        </div>
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-md-10">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <label class="small fw-bold text-danger mb-0">Target Employees</label>
                                <div class="filter-actions"><a class="text-primary btn-all" data-target="userSelect">SELECT
                                        ALL</a> | <a class="text-danger btn-clear" data-target="userSelect">CLEAR ALL</a>
                                </div>
                            </div>
                            <select class="select2-multiple" name="assignee_ids" id="userSelect" multiple
                                data-placeholder="Filter above to load employees, or leave blank to fetch ALL..."></select>
                        </div>
                        <div class="col-md-2 text-end">
                            <button type="submit" class="btn btn-primary fw-bold w-100 shadow-sm"
                                id="generateReportBtn">
                                <i class="fas fa-sync-alt me-1"></i> Generate
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row" id="reportBoard">
            <div class="col-12 text-center py-5">
                <i class="fas fa-filter fa-3x text-muted mb-3 opacity-50"></i>
                <h5 class="text-muted fw-bold">Select dates and filters, then click Generate.</h5>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            let apiPrefix = '/api/v1';
            let globalReportData = [];

            // ==========================================
            // 🔥 INITIALIZE SELECT2 & CASCADING DROPDOWNS 🔥
            // ==========================================
            $('.select2-multiple').select2({
                theme: 'bootstrap-5',
                width: '100%'
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

            $.get(apiPrefix + '/companies', function(res) {
                let html = '';
                res.data.forEach(c => {
                    html +=
                        `<option value="${c.id}" data-name="${c.company_name}">${c.company_name}</option>`;
                });
                $('#companySelect').html(html).val(null).trigger('change.select2');
            });

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

                $.get(apiPrefix + '/employees', params, function(res) {
                    let html = '';
                    res.data.forEach(user => {
                        html +=
                            `<option value="${user.id}">${user.full_name || user.member_name} (${user.member_id})</option>`;
                    });
                    $('#userSelect').html(html).val(null).trigger('change.select2');
                });
            }

            let date = new Date();
            let firstDay = new Date(date.getFullYear(), date.getMonth(), 1).toISOString().split('T')[0];
            let lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).toISOString().split('T')[0];
            $('#filterStartDate').val(firstDay);
            $('#filterEndDate').val(lastDay);


            // ==========================================
            // 🔥 FETCH REPORT & RENDER ACCORDION 🔥
            // ==========================================
            $('#filterReportForm').on('submit', function(e) {
                e.preventDefault();
                let btn = $('#generateReportBtn');
                let originalHtml = btn.html();

                let queryData = {
                    start_date: $('#filterStartDate').val(),
                    end_date: $('#filterEndDate').val(),
                    company_ids: getSelected('companySelect'),
                    branch_ids: getSelected('branchSelect'),
                    department_ids: getSelected('deptSelect'),
                    designation_ids: getSelected('desigSelect'),
                    assignee_ids: getSelected('userSelect')
                };

                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
                $('#reportBoard').html(
                    '<div class="col-12 text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i></div>'
                );
                $('#exportExcelBtn').hide();

                $.get(apiPrefix + '/task-reports-data', queryData, function(res) {
                    globalReportData = res.data;
                    let html = '';

                    if (res.data.length === 0) {
                        $('#reportBoard').html(
                            '<div class="col-12 text-center py-5 text-danger fw-bold"><i class="fas fa-exclamation-circle fa-2x mb-2"></i><br>No records found for this criteria.</div>'
                        );
                        return;
                    }

                    html += `
                    <div class="col-12 mb-3 text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold shadow-sm me-2" id="expandAllBtn"><i class="fas fa-expand-arrows-alt me-1"></i> Expand All Panels</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary fw-bold shadow-sm" id="collapseAllBtn"><i class="fas fa-compress-arrows-alt me-1"></i> Collapse All Panels</button>
                    </div>`;

                    html += '<div class="accordion" id="reportAccordion">';

                    res.data.forEach((emp, index) => {
                        let collapseId = `report-emp-${index}`;
                        let taskRowsHtml = '';

                        emp.tasks.forEach(task => {
                            let statusColor = task.status === 'Completed' ?
                                'success' : (task.status === 'In-Progress' ?
                                    'primary' : 'warning');
                            let priorityColor = task.priority === 'Urgent' ?
                                'danger' : (task.priority === 'High' ? 'warning' :
                                    'info');

                            let progressHtml = '';
                            if (task.is_target_based) {
                                progressHtml =
                                    `
                                <div class="d-flex justify-content-between small text-muted fw-bold mb-1">
                                    <span>Target: ${task.achieved}/${task.target}</span> <span class="text-${statusColor}">${task.progress_percent}%</span>
                                </div>
                                <div class="progress shadow-sm" style="height: 6px;"><div class="progress-bar bg-${statusColor}" style="width: ${task.progress_percent}%;"></div></div>`;
                            } else {
                                progressHtml =
                                    `<div class="mt-2"><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25"><i class="fas fa-clipboard-check me-1"></i> Normal/Manual Task</span></div>`;
                            }

                            let chatHtml = '';
                            let chatToggleBtn = '';

                            if (task.logs.length > 0) {
                                // 🔥 Chat is hidden by default, button says "Expand" with down arrow
                                chatToggleBtn =
                                    `<button type="button" class="btn btn-xs btn-light border py-0 px-2 toggle-chat-box fw-bold text-secondary shadow-sm" style="font-size: 10px;"><i class="fas fa-angle-down me-1"></i> Expand</button>`;

                                chatHtml +=
                                    '<div class="chat-box mt-2" style="display: none;">';
                                task.logs.forEach(log => {
                                    chatHtml += `<div class="chat-msg">
                                        <div class="d-flex justify-content-between">
                                            <strong class="text-primary"><i class="fas fa-comment-dots"></i> ${log.actor}</strong>
                                            <small class="text-muted">${log.date}</small>
                                        </div>
                                        <span class="text-dark d-block mt-1">${log.message}</span>
                                    </div>`;
                                });
                                chatHtml += '</div>';
                            } else {
                                chatHtml =
                                    '<div class="text-muted small mt-2 fst-italic"><i class="fas fa-info-circle"></i> Is date range mein koi naya response/chat nahi hai.</div>';
                            }

                            taskRowsHtml += `
                            <div class="row align-items-start border-bottom p-3 m-0 task-row-hover bg-white">
                                <div class="col-12 col-md-5 mb-2 mb-md-0 border-end">
                                    <h6 class="fw-bold text-dark mb-1">${task.title}</h6>
                                    <div class="d-flex flex-wrap gap-2 mb-2">
                                        <span class="badge bg-${priorityColor} bg-opacity-10 text-${priorityColor} border border-${priorityColor} border-opacity-25">${task.priority}</span>
                                        <span class="badge bg-${statusColor} text-white">${task.status}</span>
                                        <span class="small text-muted"><i class="fas fa-calendar-plus text-success"></i> Assigned: ${task.assigned_date}</span>
                                        <span class="small text-muted"><i class="fas fa-clock text-warning"></i> Due: ${task.due_date}</span>
                                    </div>
                                    ${progressHtml}
                                </div>
                                <div class="col-12 col-md-7">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong class="small text-muted text-uppercase">Employee Response & Chats</strong>
                                        ${chatToggleBtn}
                                    </div>
                                    ${chatHtml}
                                </div>
                            </div>`;
                        });

                        html += `
                        <div class="accordion-item mb-3 border-0 shadow-sm rounded">
                            <h2 class="accordion-header" id="heading-${index}">
                                <div class="accordion-button collapsed bg-light fw-bold text-dark rounded border border-bottom-0 d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" style="cursor: pointer;" aria-expanded="false">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-circle text-primary fs-5 me-2"></i> ${emp.employee_name} 
                                    </div>
                                    <div class="d-flex align-items-center gap-2 ms-auto me-3" onclick="event.stopPropagation();">
                                        <button type="button" class="btn btn-xs btn-outline-success fw-bold py-0 px-2 toggle-emp-chats" data-emp-index="${index}" style="font-size: 11px;">
                                            <i class="fas fa-arrows-alt-v me-1"></i> Toggle All Chats
                                        </button>
                                        <span class="badge bg-secondary rounded-pill shadow-sm">${emp.total_tasks} Tasks Associated</span>
                                    </div>
                                </div>
                            </h2>
                            <div id="${collapseId}" class="accordion-collapse collapse task-collapse">
                                <div class="accordion-body p-0 border border-top-0 rounded-bottom">
                                    ${taskRowsHtml}
                                </div>
                            </div>
                        </div>`;
                    });

                    html += '</div>';

                    $('#reportBoard').html(html);
                    $('#exportExcelBtn').fadeIn();

                }).fail(function(xhr) {
                    let errorMsg = xhr.responseJSON ? xhr.responseJSON.message :
                        'Server Error Occurred!';
                    $('#reportBoard').html(
                        `<div class="col-12 text-center py-5 text-danger"><i class="fas fa-bug fa-2x mb-2"></i><br>${errorMsg}</div>`
                    );
                }).always(function() {
                    btn.html(originalHtml).prop('disabled', false);
                });
            });

            // ==========================================
            // 🔥 EXPAND / COLLAPSE CHAT INTERACTION LOGIC 🔥
            // ==========================================

            // 1. Single Task Chat Row Toggle
            $(document).on('click', '.toggle-chat-box', function(e) {
                e.stopPropagation();
                let btn = $(this);
                let chatBox = btn.closest('.col-md-7').find('.chat-box');

                if (chatBox.is(':visible')) {
                    chatBox.slideUp(200);
                    btn.html('<i class="fas fa-angle-down me-1"></i> Expand');
                } else {
                    chatBox.slideDown(200);
                    btn.html('<i class="fas fa-angle-up me-1"></i> Collapse');
                }
            });

            // 2. Employee Level Master Chat Toggle
            $(document).on('click', '.toggle-emp-chats', function(e) {
                e.stopPropagation();
                let empIndex = $(this).data('emp-index');
                let targetContainer = $(`#report-emp-${empIndex}`);

                let chatBoxes = targetContainer.find('.chat-box');
                let rowButtons = targetContainer.find('.toggle-chat-box');

                let visibleBoxes = chatBoxes.filter(':visible');
                if (visibleBoxes.length > 0) {
                    chatBoxes.slideUp(200);
                    rowButtons.html('<i class="fas fa-angle-down me-1"></i> Expand');
                } else {
                    chatBoxes.slideDown(200);
                    rowButtons.html('<i class="fas fa-angle-up me-1"></i> Collapse');
                }
            });

            $(document).on('click', '#expandAllBtn', function() {
                $('.task-collapse').collapse('show');
            });
            $(document).on('click', '#collapseAllBtn', function() {
                $('.task-collapse').collapse('hide');
            });

            // ==========================================
            // 🔥 EXPORT TO EXCEL (CSV) LOGIC 🔥
            // ==========================================
            $('#exportExcelBtn').on('click', function() {
                if (globalReportData.length === 0) {
                    Swal.fire('Empty', 'No data available to export.', 'warning');
                    return;
                }

                let csvContent =
                    "Employee Name,Task Title,Type,Priority,Status,Assigned Date,Due Date,Target,Achieved,Progress %,Chat Date,Actor,Message\n";

                globalReportData.forEach(emp => {
                    let safeEmpName = `"${emp.employee_name.replace(/"/g, '""')}"`;

                    emp.tasks.forEach(task => {
                        let type = task.is_target_based ? 'Target Based' : 'Manual';
                        let safeTitle = `"${task.title.replace(/"/g, '""')}"`;

                        if (task.logs.length > 0) {
                            task.logs.forEach(log => {
                                let safeMsg =
                                `"${log.message.replace(/"/g, '""')}"`;
                                let safeActor =
                                `"${log.actor.replace(/"/g, '""')}"`;

                                csvContent +=
                                    `${safeEmpName},${safeTitle},${type},${task.priority},${task.status},${task.assigned_date},${task.due_date},${task.target},${task.achieved},${task.progress_percent},"${log.date}",${safeActor},${safeMsg}\n`;
                            });
                        } else {
                            csvContent +=
                                `${safeEmpName},${safeTitle},${type},${task.priority},${task.status},${task.assigned_date},${task.due_date},${task.target},${task.achieved},${task.progress_percent},"","",""\n`;
                        }
                    });
                });

                const blob = new Blob([csvContent], {
                    type: 'text/csv;charset=utf-8;'
                });
                const link = document.createElement("a");
                const url = URL.createObjectURL(blob);

                link.setAttribute("href", url);
                link.setAttribute("download", "Employee_Task_Report_" + new Date().getTime() + ".csv");
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });

            function applyRBACtoTaskForm() {
                $.get('/api/v1/admin/auth/me', function(res) {
                    let u = res.data;
                    let isGodMode = window.userGodMode;
                    let isDirector = u.designation_name && (u.designation_name.toLowerCase().includes(
                        'director') || u.designation_name.toLowerCase().includes('ceo'));

                    if (isGodMode) {
                        $('#companySelect').prop('disabled', false);
                        $('#branchSelect').prop('disabled', false);
                        $('#departmentSelect').prop('disabled', false);
                    } else if (isDirector) {
                        $('#companySelect').html(
                            `<option value="${u.company_id}" selected>Your Company</option>`);
                        $('#companySelect').prop('disabled', true).trigger('change');
                        $('#taskAssignForm').append(
                            `<input type="hidden" name="company_id" value="${u.company_id}">`);
                    } else {
                        $('#companySelect').html(
                            `<option value="${u.company_id}" selected>Your Company</option>`);
                        $('#companySelect').prop('disabled', true);
                        $('#taskAssignForm').append(
                            `<input type="hidden" name="company_id" value="${u.company_id}">`);

                        $('#branchSelect').html(
                            `<option value="${u.branch_id}" selected>Your Branch</option>`);
                        $('#branchSelect').prop('disabled', true);
                        $('#taskAssignForm').append(
                            `<input type="hidden" name="branch_id" value="${u.branch_id}">`);

                        $('#departmentSelect').html(
                            `<option value="${u.department_id}" selected>Your Department</option>`);
                        $('#departmentSelect').prop('disabled', true);
                        $('#taskAssignForm').append(
                            `<input type="hidden" name="department_id" value="${u.department_id}">`);
                    }
                });
            }
        });
    </script>
@endpush
