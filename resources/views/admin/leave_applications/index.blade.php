@extends('layout.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 text-dark fw-bold"><i class="fas fa-calendar-alt text-primary me-2"></i>Leave & Applications</h4>
            <button class="btn btn-primary secured-item" data-permission="public" onclick="openLeaveModal()">
                <i class="fas fa-plus me-1"></i> New Request
            </button>
        </div>

        <div id="table-container">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <div id="bulkActions" class="d-none">
                            <button class="btn btn-sm btn-outline-secondary" onclick="selectAllVisible()"><i
                                    class="fas fa-check-double"></i> Select All</button>
                            <button class="btn btn-sm btn-danger secured-item" data-permission="leave_delete"
                                onclick="deleteSelected()"><i class="fas fa-trash"></i> Delete Selected</button>
                        </div>
                    </div>

                    <div class="d-flex gap-2 w-sm-100">
                        <input type="text" id="searchBox" class="form-control form-control-sm"
                            placeholder="Search reason, name..." style="max-width: 200px;">
                        <button class="btn btn-sm btn-success secured-item" data-permission="leave_export"
                            onclick="exportExcel()">
                            <i class="fas fa-file-excel"></i> Export
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive d-none d-md-block shadow-sm bg-white rounded">
                <table class="table table-hover align-middle mb-0" id="leaveTable">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" class="form-check-input" id="checkAllDesktop">
                            </th>
                            <th>Applicant</th>
                            <th>Type</th>
                            <th>Duration</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="desktopTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-block d-md-none" id="mobileCardContainer">
                <div class="text-center py-4 text-muted">Loading...</div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3" id="paginationControls"></div>
        </div>
    </div>

    <div class="modal fade" id="leaveApplicationModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="leaveModalTitle">Apply for Leave / Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="leaveApplicationForm">
                        <input type="hidden" id="leave_id" name="id">

                        <div class="row g-3 mb-3 pb-3 border-bottom">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">User Type <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="user_type" name="user_type" required>
                                    <option value="employee">Employee</option>
                                    <option value="member">Member</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Company <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="company_id" name="company_id" required>
                                    <option value="">Select Company...</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Branch (Optional for HO)</label>
                                <select class="form-select form-select-sm" id="branch_id" name="branch_id">
                                    <option value="">Select Branch...</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Department</label>
                                <select class="form-select form-select-sm" id="department_id" name="department_id">
                                    <option value="">Select Department...</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Designation</label>
                                <select class="form-select form-select-sm" id="designation_id" name="designation_id">
                                    <option value="">Select Designation...</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Applicant Name <span
                                        class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="user_id" name="user_id" required>
                                    <option value="">Select Applicant...</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Application Type <span
                                        class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="application_type" name="application_type"
                                    required onchange="handleAppTypeChange()">
                                    <option value="Leave">Leave</option>
                                    <option value="Short Leave">Short Leave</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-3 date-time-fields">
                                <label class="form-label small fw-bold" id="start_label">Date From <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="start_datetime"
                                    name="start_datetime" onchange="calculateDuration()">
                            </div>
                            <div class="col-md-3 date-time-fields">
                                <label class="form-label small fw-bold" id="end_label">Date To <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="end_datetime"
                                    name="end_datetime" onchange="calculateDuration()">
                            </div>
                            <div class="col-md-2 date-time-fields">
                                <label class="form-label small fw-bold">Duration</label>
                                <input type="text" class="form-control form-control-sm bg-light" id="duration_display"
                                    readonly placeholder="Auto">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Reason (Min 300 Characters) <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm" id="reason" name="reason" rows="4" minlength="300"
                                    required placeholder="Describe the detailed reason for your application..."></textarea>
                                <div class="form-text text-end" id="charCount">0 / 300 min</div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light p-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-primary" id="btnSaveLeave"
                        onclick="submitLeaveRequest()">Submit Request</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="approveModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content border-success">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Approve Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="approve_leave_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Approved Duration (Days/Hrs) <span
                                class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="approve_duration" required>
                        <small class="text-muted">You can modify the requested duration before approving.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Remarks / Approver Note (Optional)</label>
                        <textarea class="form-control" id="approve_remarks" rows="2" placeholder="Great work, approved!"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light p-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-success" onclick="submitApprove()"><i
                            class="fas fa-check"></i> Confirm Approval</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-times-circle me-2"></i>Reject Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reject_leave_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Reason for Rejection <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_remarks" rows="3" required minlength="10"
                            placeholder="Please specify why this is being rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light p-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="submitReject()"><i
                            class="fas fa-times"></i> Confirm Rejection</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let currentPage = 1;
        let selectedIds = [];

        let globalUserProfile = null;
        let globalDropdownCache = {};

        // ==========================================
        // 1. DYNAMIC FIELDS LOGIC
        // ==========================================
        function handleAppTypeChange() {
            let type = $('#application_type').val();
            if (type === 'Other') {
                $('.date-time-fields').hide();
                $('#start_datetime, #end_datetime').val('').removeAttr('required');
                $('#duration_display').val('N/A');
            } else {
                $('.date-time-fields').show();
                $('#start_datetime, #end_datetime').attr('required', true);

                if (type === 'Short Leave') {
                    $('#start_datetime').attr('type', 'datetime-local');
                    $('#end_datetime').attr('type', 'datetime-local');
                    $('#start_label').html('Time From <span class="text-danger">*</span>');
                    $('#end_label').html('Time To <span class="text-danger">*</span>');
                } else {
                    $('#start_datetime').attr('type', 'date');
                    $('#end_datetime').attr('type', 'date');
                    $('#start_label').html('Date From <span class="text-danger">*</span>');
                    $('#end_label').html('Date To <span class="text-danger">*</span>');
                }
                calculateDuration();
            }
        }

        function calculateDuration() {
            let type = $('#application_type').val();
            let start = $('#start_datetime').val();
            let end = $('#end_datetime').val();

            if (!start || !end || type === 'Other') {
                $('#duration_display').val('');
                return;
            }
            let startDate = new Date(start);
            let endDate = new Date(end);

            if (endDate < startDate) {
                Swal.fire('Invalid Date', 'End Date/Time cannot be before Start Date/Time', 'warning');
                $('#end_datetime').val('');
                $('#duration_display').val('');
                return;
            }

            if (type === 'Leave') {
                let diffTime = Math.abs(endDate - startDate);
                $('#duration_display').val(Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1 + ' Day(s)');
            } else if (type === 'Short Leave') {
                let diffTime = Math.abs(endDate - startDate);
                $('#duration_display').val((diffTime / (1000 * 60 * 60)).toFixed(2) + ' Hour(s)');
            }
        }

        $('#reason').on('input', function() {
            let len = $(this).val().length;
            $('#charCount').text(len + ' / 300 min');
            if (len < 300) $('#charCount').addClass('text-danger').removeClass('text-success');
            else $('#charCount').removeClass('text-danger').addClass('text-success fw-bold');
        });

        // ==========================================
        // 2. AUTO-LOCK & CONTEXT FETCH (LOCALSTORAGE FIX)
        // ==========================================
        async function loadCompaniesAndCache() {
            // 🔥 NAYA: API token flow URL handle karne ke liye
            let currentPath = window.location.pathname;
            let profileApiUrl = '/api/v1/admin/auth/me';
            if (currentPath.startsWith('/employee')) profileApiUrl = '/api/v1/employee/auth/me';
            else if (currentPath.startsWith('/customer')) profileApiUrl = '/api/v1/customer/auth/me';

            try {
                // Fetch profile directly through API (using localStorage token attached in headers)
                let authRes = await $.get(profileApiUrl);
                let rawUser = authRes.data || authRes;

                let emailStr = (rawUser.email || '').toLowerCase();
                let isGod = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'].includes(
                    emailStr);
                let desig = (rawUser.designation_name || '').toLowerCase();

                globalUserProfile = {
                    ...rawUser,
                    is_god: isGod,
                    is_director: desig.includes('director'),
                    is_ceo: desig.includes('ceo'),
                    is_member: !!rawUser.member_id // Agar member_id field present hai toh
                };
            } catch (e) {
                console.error("Profile load failed. Token might be invalid or missing.", e);
            }

            let cRes = await $.get('/api/v1/get-active-companies');
            let cData = cRes.data || cRes;
            let cOpts = '<option value="">Select Company...</option>';
            if (Array.isArray(cData)) {
                cData.forEach(c => cOpts += `<option value="${c.id}">${c.company_name}</option>`);
            }
            globalDropdownCache.company = cOpts;
        }

        async function applyLocksBasedOnProfile() {
            let u = globalUserProfile;
            if (!u) return;

            if (u.is_god || u.is_ceo) {
                $('#company_id').html(globalDropdownCache.company).css('pointer-events', 'auto').removeClass(
                'bg-light');
                return; // Sab open rahega inke liye
            }

            // Lock Company
            $('#company_id').html(globalDropdownCache.company).val(u.company_id).css('pointer-events', 'none').addClass(
                'bg-light');

            if (!u.is_director) {
                // Fetch & Lock Branch
                let bRes = await $.post('/api/v1/get-branches-by-companies', {
                    company_ids: [u.company_id]
                });
                let bData = bRes.data || bRes;
                let bOpts = '<option value="">Head Office (None)</option>';
                if (Array.isArray(bData)) {
                    bData.forEach(b => bOpts += `<option value="${b.id}">${b.branch_name}</option>`);
                }
                $('#branch_id').html(bOpts).val(u.branch_id || '').css('pointer-events', 'none').addClass('bg-light');

                // Fetch & Lock Dept
                let dRes = await $.get(`/api/v1/get-departments-by-company`, {
                    company_id: u.company_id,
                    branch_id: u.branch_id || ''
                });
                let dData = dRes.data || dRes;
                let dOpts = '<option value="">Select Department...</option>';
                if (Array.isArray(dData)) {
                    dData.forEach(d => dOpts += `<option value="${d.id}">${d.department_name}</option>`);
                }
                $('#department_id').html(dOpts).val(u.department_id).css('pointer-events', 'none').addClass('bg-light');

                // Fetch & Lock Designation
                if (u.department_id) {
                    let dsRes = await $.get(`/api/v1/get-designations-by-dept`, {
                        department_id: u.department_id
                    });
                    let dsData = dsRes.data || dsRes;
                    let dsOpts = '<option value="">Select Designation...</option>';
                    if (Array.isArray(dsData)) {
                        dsData.forEach(d => dsOpts += `<option value="${d.id}">${d.designation_name}</option>`);
                    }
                    $('#designation_id').html(dsOpts).val(u.designation_id).css('pointer-events', 'none').addClass(
                        'bg-light');
                }

                // Fetch & Lock User Name
                if (u.designation_id) {
                    let uType = u.is_member ? 'member' : 'employee';
                    let uRes = await $.get(`/api/v1/leave-applications/dropdown/users`, {
                        designation_id: u.designation_id,
                        company_id: u.company_id,
                        branch_id: u.branch_id || '',
                        user_type: uType
                    });
                    let uData = uRes.data || uRes;
                    let uOpts = '<option value="">Select Applicant...</option>';
                    if (Array.isArray(uData)) {
                        uData.forEach(user => {
                            let name = user.full_name || user.name;
                            if (uType === 'member') name += ` (${user.member_id})`;
                            uOpts += `<option value="${user.id}">${name}</option>`;
                        });
                    }

                    $('#user_type').val(uType).css('pointer-events', 'none').addClass('bg-light');
                    $('#user_id').html(uOpts).val(u.id).css('pointer-events', 'none').addClass('bg-light');
                }
            }
        }

        function openLeaveModal() {
            $('#leaveApplicationForm')[0].reset();
            $('#leave_id').val('');
            $('#charCount').text('0 / 300 min').addClass('text-danger');
            handleAppTypeChange();

            // Clean visual resets
            $('#branch_id').html('<option value="">Head Office (None)</option>');
            $('#department_id').html('<option value="">Select Department...</option>');
            $('#designation_id').html('<option value="">Select Designation...</option>');
            $('#user_id').html('<option value="">Select Applicant...</option>');

            $('#btnSaveLeave').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading Data...');
            $('#leaveApplicationModal').modal('show');

            applyLocksBasedOnProfile().then(() => {
                $('#btnSaveLeave').prop('disabled', false).text('Submit Request');
            });
        }

        // ==========================================
        // 3. MANUAL CASCADING (For Admin/GodMode)
        // ==========================================
        $('#company_id').change(function() {
            let cid = $(this).val();
            $('#branch_id').html('<option value="">Head Office (None)</option>');
            $('#department_id').html('<option value="">Select Department...</option>');
            $('#designation_id').html('<option value="">Select Designation...</option>');
            $('#user_id').html('<option value="">Select Applicant...</option>');
            if (!cid) return;
            $.post('/api/v1/get-branches-by-companies', {
                company_ids: [cid]
            }, function(res) {
                let data = res.data || res;
                let options = '<option value="">Head Office (None)</option>';
                if (Array.isArray(data)) data.forEach(b => options +=
                    `<option value="${b.id}">${b.branch_name}</option>`);
                $('#branch_id').html(options);
            });
        });

        $('#branch_id').change(function() {
            let cid = $('#company_id').val();
            let bid = $(this).val();
            $('#department_id').html('<option value="">Select Department...</option>');
            $('#designation_id').html('<option value="">Select Designation...</option>');
            $('#user_id').html('<option value="">Select Applicant...</option>');
            if (!cid) return;
            $.get(`/api/v1/get-departments-by-company`, {
                company_id: cid,
                branch_id: bid
            }, function(res) {
                let data = res.data || res;
                let options = '<option value="">Select Department...</option>';
                if (Array.isArray(data)) data.forEach(d => options +=
                    `<option value="${d.id}">${d.department_name}</option>`);
                $('#department_id').html(options);
            });
        });

        $('#department_id').change(function() {
            let did = $(this).val();
            $('#designation_id').html('<option value="">Select Designation...</option>');
            $('#user_id').html('<option value="">Select Applicant...</option>');
            if (!did) return;
            $.get(`/api/v1/get-designations-by-dept`, {
                department_id: did
            }, function(res) {
                let data = res.data || res;
                let options = '<option value="">Select Designation...</option>';
                if (Array.isArray(data)) data.forEach(d => options +=
                    `<option value="${d.id}">${d.designation_name}</option>`);
                $('#designation_id').html(options);
            });
        });

        $('#designation_id, #user_type').change(function() {
            let desig = $('#designation_id').val();
            let comp = $('#company_id').val();
            let branch = $('#branch_id').val();
            let type = $('#user_type').val();
            if (!desig) {
                $('#user_id').html('<option value="">Select Applicant...</option>');
                return;
            }
            $.get(`/api/v1/leave-applications/dropdown/users`, {
                designation_id: desig,
                company_id: comp,
                branch_id: branch,
                user_type: type
            }, function(res) {
                let data = res.data || res;
                let options = '<option value="">Select Applicant...</option>';
                if (Array.isArray(data)) data.forEach(u => {
                    let name = type === 'member' ? `${u.full_name} (${u.member_id})` : u.full_name;
                    options += `<option value="${u.id}">${name}</option>`;
                });
                $('#user_id').html(options);
            });
        });

        // ==========================================
        // 4. FETCH & RENDER UI
        // ==========================================
        function loadLeaveData(page = 1) {
            let search = $('#searchBox').val();
            $.get(`/api/v1/leave-applications?page=${page}&search=${search}`, function(res) {
                currentPage = page;
                renderDesktopTable(res.data.data);
                renderMobileCards(res.data.data);
                renderPagination(res.data);
                selectedIds = [];
                toggleBulkActions();
                $('#checkAllDesktop').prop('checked', false);
            });
        }

        function getStatusBadge(status) {
            if (status === 'approved') return '<span class="badge bg-success">Approved</span>';
            if (status === 'rejected') return '<span class="badge bg-danger">Rejected</span>';
            return '<span class="badge bg-warning text-dark">Pending</span>';
        }

        function getActionButtons(row) {
            let isOwner = row.user_id == window.userId; // API assigns this globally
            let html = `<div class="btn-group">`;

            html +=
                `<button class="btn btn-sm btn-outline-info" onclick="viewApplication(${row.id})" title="View"><i class="fas fa-eye"></i></button>`;

            if (row.status === 'pending' && (isOwner || window.userPerms.includes('leave_edit') || window.userGodMode)) {
                html +=
                    `<button class="btn btn-sm btn-outline-primary" onclick="editApplication(${row.id})" title="Edit"><i class="fas fa-edit"></i></button>`;
            }
            if (row.status === 'pending' && !isOwner) {
                if (window.userPerms.includes('leave_appr') || window.userGodMode) {
                    html +=
                        `<button class="btn btn-sm btn-outline-success" onclick="openApproveModal(${row.id}, '${row.duration}', '${row.application_type}')" title="Approve"><i class="fas fa-check"></i></button>`;
                }
                if (window.userPerms.includes('leave_rej') || window.userGodMode) {
                    html +=
                        `<button class="btn btn-sm btn-outline-danger" onclick="openRejectModal(${row.id})" title="Reject"><i class="fas fa-times"></i></button>`;
                }
            }
            if (row.status !== 'pending' && (window.userPerms.includes('leave_print') || window.userGodMode)) {
                html +=
                    `<button class="btn btn-sm btn-outline-secondary" onclick="printApplication(${row.id})" title="Print"><i class="fas fa-print"></i></button>`;
            }
            html += `</div>`;
            return html;
        }

        function renderDesktopTable(data) {
            let tbody = $('#desktopTableBody');
            tbody.empty();
            if (data.length === 0) {
                tbody.html('<tr><td colspan="7" class="text-center py-4">No records found.</td></tr>');
                return;
            }
            data.forEach(item => {
                let name = item.user_type === 'employee' ? (item.employee?.full_name || 'N/A') : (item.member
                    ?.full_name || 'N/A') + ' (' + item.member?.member_id + ')';
                let durationStr = item.duration ?
                    `${item.duration} ${item.application_type === 'Short Leave' ? 'Hrs' : 'Days'}` : 'N/A';
                let tr = `<tr>
                    <td><input type="checkbox" class="form-check-input row-checkbox" value="${item.id}"></td>
                    <td class="fw-bold">${name} <br><small class="text-muted">${item.company?.company_name}</small></td>
                    <td><span class="badge bg-primary">${item.application_type}</span></td>
                    <td>${durationStr}</td>
                    <td><span class="d-inline-block text-truncate" style="max-width: 150px;" title="${item.reason}">${item.reason}</span></td>
                    <td>${getStatusBadge(item.status)}</td>
                    <td class="text-end">${getActionButtons(item)}</td>
                </tr>`;
                tbody.append(tr);
            });
        }

        function renderMobileCards(data) {
            let container = $('#mobileCardContainer');
            container.empty();
            if (data.length === 0) {
                container.html('<div class="text-center py-4 text-muted">No records found.</div>');
                return;
            }
            data.forEach(item => {
                let name = item.user_type === 'employee' ? (item.employee?.full_name || 'N/A') : (item.member
                    ?.full_name || 'N/A');
                let durationStr = item.duration ?
                    `${item.duration} ${item.application_type === 'Short Leave' ? 'Hrs' : 'Days'}` : 'N/A';
                let card = `<div class="card mb-2 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" class="form-check-input row-checkbox" value="${item.id}">
                                <strong class="text-dark">${name}</strong>
                            </div>
                            ${getStatusBadge(item.status)}
                        </div>
                        <div class="small text-muted mb-2">
                            <i class="fas fa-building me-1"></i> ${item.company?.company_name} <br>
                            <span class="badge bg-light text-dark border me-1">${item.application_type}</span>
                            <span class="badge bg-light text-dark border"><i class="far fa-clock"></i> ${durationStr}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                            <div class="small text-muted text-truncate" style="max-width: 60%;">${item.reason}</div>
                            ${getActionButtons(item)}
                        </div>
                    </div>
                </div>`;
                container.append(card);
            });
        }

        function renderPagination(meta) {
            let html = `
                <span class="text-muted small">Showing ${meta.from || 0} to ${meta.to || 0} of ${meta.total}</span>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary" ${!meta.prev_page_url ? 'disabled' : ''} onclick="loadLeaveData(${currentPage - 1})">Prev</button>
                    <button class="btn btn-sm btn-outline-primary" ${!meta.next_page_url ? 'disabled' : ''} onclick="loadLeaveData(${currentPage + 1})">Next</button>
                </div>`;
            $('#paginationControls').html(html);
        }

        // ==========================================
        // 5. BULK ACTIONS & OTHERS
        // ==========================================
        $(document).on('change', '.row-checkbox', function() {
            let val = $(this).val();
            if ($(this).is(':checked')) {
                if (!selectedIds.includes(val)) selectedIds.push(val);
            } else {
                selectedIds = selectedIds.filter(id => id !== val);
                $('#checkAllDesktop').prop('checked', false);
            }
            toggleBulkActions();
        });

        $('#checkAllDesktop').change(function() {
            let isChecked = $(this).is(':checked');
            $('.row-checkbox').prop('checked', isChecked);
            selectedIds = [];
            if (isChecked) {
                $('.row-checkbox').each(function() {
                    selectedIds.push($(this).val());
                });
            }
            toggleBulkActions();
        });

        function selectAllVisible() {
            $('.row-checkbox').prop('checked', true);
            selectedIds = [];
            $('.row-checkbox').each(function() {
                selectedIds.push($(this).val());
            });
            $('#checkAllDesktop').prop('checked', true);
            toggleBulkActions();
        }

        function toggleBulkActions() {
            if (selectedIds.length > 0) $('#bulkActions').removeClass('d-none');
            else $('#bulkActions').addClass('d-none');
        }

        $('#searchBox').on('keyup', function() {
            loadLeaveData(1);
        });

        function deleteSelected() {
            if (selectedIds.length === 0) return;
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete ${selectedIds.length} records.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('/api/v1/bulk-delete', {
                        table: 'leave_applications',
                        ids: selectedIds
                    }, function(res) {
                        Swal.fire('Deleted!', 'Records have been deleted.', 'success');
                        loadLeaveData(currentPage);
                    });
                }
            });
        }

        // ==========================================
        // 6. CRUD ACTIONS
        // ==========================================
        function submitLeaveRequest() {
            if (!$('#leaveApplicationForm')[0].checkValidity()) {
                $('#leaveApplicationForm')[0].reportValidity();
                return;
            }
            let formData = $('#leaveApplicationForm').serialize();
            $('#btnSaveLeave').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
            let id = $('#leave_id').val();
            let url = id ? `/api/v1/leave-applications/${id}` : '/api/v1/leave-applications';
            let method = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                type: method,
                data: formData,
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Success', res.message, 'success');
                        $('#leaveApplicationModal').modal('hide');
                        loadLeaveData(currentPage);
                    }
                },
                error: function(xhr) {
                    let errors = xhr.responseJSON?.errors;
                    let errorMsg = '';
                    if (errors) {
                        for (let key in errors) {
                            errorMsg += errors[key][0] + '<br>';
                        }
                    } else {
                        errorMsg = xhr.responseJSON?.message || 'Something went wrong';
                    }
                    Swal.fire('Error', errorMsg, 'error');
                },
                complete: function() {
                    $('#btnSaveLeave').prop('disabled', false).text('Submit Request');
                }
            });
        }

        function viewApplication(id) {
            $.get(`/api/v1/leave-applications/${id}`, function(res) {
                let data = res.data;
                let name = data.user_type === 'employee' ? (data.employee?.full_name || 'N/A') : (data.member
                    ?.full_name || 'N/A');
                let durationStr = data.duration ?
                    `${data.duration} ${data.application_type === 'Short Leave' ? 'Hrs' : 'Days'}` : 'N/A';
                let badge = data.status === 'approved' ? '<span class="badge bg-success">Approved</span>' : (data
                    .status === 'rejected' ? '<span class="badge bg-danger">Rejected</span>' :
                    '<span class="badge bg-warning text-dark">Pending</span>');

                let html = `
                <div class="text-start" style="font-size: 14px;">
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2"><strong>Status:</strong> ${badge}</div>
                    <p><strong>Applicant:</strong> ${name} <small class="text-muted">(${data.user_type})</small></p>
                    <p><strong>Hierarchy:</strong> ${data.company?.company_name || 'N/A'} > ${data.department?.department_name || 'N/A'} > ${data.designation?.designation_name || 'N/A'}</p>
                    <p><strong>Type:</strong> <span class="badge bg-primary">${data.application_type}</span></p>
                    <p><strong>Duration:</strong> ${durationStr}</p>
                    <p><strong>Dates:</strong> ${data.start_datetime ? data.start_datetime.substring(0,16).replace('T', ' ') : 'N/A'} <b>TO</b> ${data.end_datetime ? data.end_datetime.substring(0,16).replace('T', ' ') : 'N/A'}</p>
                    <p class="mb-1"><strong>Reason:</strong></p>
                    <div class="p-2 bg-light border rounded text-muted mb-2" style="max-height: 150px; overflow-y: auto;">${data.reason}</div>
                    ${data.remarks ? `<p class="mb-1"><strong>Approver Remarks:</strong></p><div class="p-2 bg-light border border-danger rounded text-danger">${data.remarks}</div>` : ''}
                </div>`;
                Swal.fire({
                    title: 'Application Details',
                    html: html,
                    icon: 'info',
                    width: '600px',
                    showCloseButton: true,
                    showConfirmButton: false
                });
            });
        }

        function editApplication(id) {
            $.get(`/api/v1/leave-applications/${id}`, function(res) {
                let data = res.data;
                $('#leaveApplicationForm')[0].reset();
                $('#leave_id').val(data.id);
                $('#leaveModalTitle').text('Edit Leave / Request');
                $('#application_type').val(data.application_type);
                handleAppTypeChange();

                if (data.start_datetime) {
                    let start = data.start_datetime.substring(0, 16);
                    if (data.application_type !== 'Short Leave') start = data.start_datetime.substring(0, 10);
                    $('#start_datetime').val(start);
                }
                if (data.end_datetime) {
                    let end = data.end_datetime.substring(0, 16);
                    if (data.application_type !== 'Short Leave') end = data.end_datetime.substring(0, 10);
                    $('#end_datetime').val(end);
                }

                $('#reason').val(data.reason).trigger('input');
                calculateDuration();

                $('#user_type').val(data.user_type);
                $('#company_id').html(`<option value="${data.company_id}">${data.company?.company_name}</option>`)
                    .val(data.company_id);

                let branchName = data.branch ? data.branch.branch_name : 'Head Office (None)';
                $('#branch_id').html(`<option value="${data.branch_id || ''}">${branchName}</option>`).val(data
                    .branch_id || '');

                $('#department_id').html(
                    `<option value="${data.department_id || ''}">${data.department?.department_name || 'Select Department'}</option>`
                    ).val(data.department_id || '');
                $('#designation_id').html(
                    `<option value="${data.designation_id || ''}">${data.designation?.designation_name || 'Select Designation'}</option>`
                    ).val(data.designation_id || '');

                let userName = data.user_type === 'employee' ? data.employee?.full_name : data.member?.full_name;
                $('#user_id').html(`<option value="${data.user_id}">${userName}</option>`).val(data.user_id);

                $('#leaveApplicationModal').modal('show');
            });
        }

        function printApplication(id) {
            window.open(`/leave-applications/print/${id}`, '_blank', 'width=900,height=650').focus();
        }

        function openApproveModal(id, requestedDuration, type) {
            $('#approve_leave_id').val(id);
            $('#approve_duration').val(requestedDuration);
            $('#approve_remarks').val('');
            $('#approveModal').modal('show');
        }

        function submitApprove() {
            let id = $('#approve_leave_id').val();
            let duration = $('#approve_duration').val();
            let remarks = $('#approve_remarks').val();
            if (!duration || duration <= 0) {
                Swal.fire('Error', 'Please enter a valid duration.', 'error');
                return;
            }
            $.ajax({
                url: `/api/v1/leave-applications/${id}/approve`,
                type: 'POST',
                data: {
                    approved_duration: duration,
                    remarks: remarks
                },
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Approved!', res.message, 'success');
                        $('#approveModal').modal('hide');
                        loadLeaveData(currentPage);
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error', xhr.responseJSON.message || 'Error approving application', 'error');
                }
            });
        }

        function openRejectModal(id) {
            $('#reject_leave_id').val(id);
            $('#reject_remarks').val('');
            $('#rejectModal').modal('show');
        }

        function submitReject() {
            let id = $('#reject_leave_id').val();
            let remarks = $('#reject_remarks').val();
            if (!remarks || remarks.length < 10) {
                Swal.fire('Error', 'Rejection reason must be at least 10 characters.', 'error');
                return;
            }
            $.ajax({
                url: `/api/v1/leave-applications/${id}/reject`,
                type: 'POST',
                data: {
                    remarks: remarks
                },
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Rejected!', res.message, 'success');
                        $('#rejectModal').modal('hide');
                        loadLeaveData(currentPage);
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error', xhr.responseJSON.message || 'Error rejecting application', 'error');
                }
            });
        }

        $(document).ready(function() {
            loadLeaveData(1);
            loadCompaniesAndCache();
        });
    </script>
@endpush
