@extends('layout.app')
@php
    $baseController = app(\App\Http\Controllers\Controller::class);
    $user = auth()->user() ?? auth('sanctum')->user();

    $userCompany = null;
    $userBranch = null;
    $userContext = [];

    // 🔥 SAFE CHECK: Agar user null nahi hai tabhi DB query chalegi, warna crash nahi hoga!
    if ($user) {
        $context = $baseController->getGlobalContext();
        $uType = isset($context->is_member) && $context->is_member ? 'member' : 'employee';

        $userCompany = \App\Models\Company::find($context->company_id ?? 1);
        $userBranch = \App\Models\Branch::find($context->branch_id ?? 0);

        $realUser = \Illuminate\Support\Facades\DB::table($user->getTable())->where('id', $user->id)->first();

        $userContext = [
            'is_god' => $context->is_god ?? false,
            'role_level' => $context->role_level ?? 'unknown',
            'is_director' => $context->is_director ?? false,
            'user_type' => $uType,
            'company_id' => $context->company_id ?? null,
            'branch_id' => $context->branch_id ?? '',
            'department_id' => $realUser->department_id ?? '',
            'designation_id' => $realUser->designation_id ?? '',
            'user_id' => $context->profile_id ?? ($user->id ?? null),
            'user_name' => $user->full_name ?? ($user->name ?? 'Applicant'),
        ];
    }
@endphp

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
                            placeholder="Search reason, name..." style="max-width: 300px;">
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
                            <th>Date Range</th>
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
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Apply To (Signatory) <span
                                        class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="applied_to" name="applied_to" required>
                                    <option value="">Select Approver...</option>
                                </select>
                            </div>
                            <div class="col-md-4 date-time-fields">
                                <label class="form-label small fw-bold" id="start_label">Date From <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="start_datetime"
                                    name="start_datetime" onchange="calculateDuration()">
                            </div>
                            <div class="col-md-4 date-time-fields">
                                <label class="form-label small fw-bold" id="end_label">Date To <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="end_datetime"
                                    name="end_datetime" onchange="calculateDuration()">
                            </div>
                            <div class="col-md-4 date-time-fields">
                                <label class="form-label small fw-bold">Duration</label>
                                <input type="text" class="form-control form-control-sm bg-light" id="duration_display"
                                    readonly placeholder="Auto">
                            </div>

                            <div class="col-md-4">
                               <label class="form-label small fw-bold">Resume Duty Date & Time <span class="text-danger" id="resume_label_star">*</span></label>
                                <input type="datetime-local" class="form-control form-control-sm" id="resume_datetime"
                                    name="resume_datetime" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Emergency Contact <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="emergency_contact"
                                    name="emergency_contact" required placeholder="Mobile No...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Emergency Email <span
                                        class="text-danger">*</span></label>
                                <input type="email" class="form-control form-control-sm" id="emergency_email"
                                    name="emergency_email" required placeholder="Email ID...">
                            </div>

                            <div class="col-md-2 d-flex align-items-end mb-3" id="paidLeaveToggleBox">
                                <div class="form-check form-switch fs-5">
                                    <input class="form-check-input" type="checkbox" id="is_paid_leave"
                                        name="is_paid_leave" value="1" style="cursor: pointer;">
                                    <label class="form-check-label fs-6 fw-bold mt-1 ms-1 text-success"
                                        for="is_paid_leave">Paid Leave</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Reason (Min 300 Letters) <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm" id="reason" name="reason" rows="4" minlength="300"
                                    required placeholder="Describe the detailed reason for your application..."></textarea>
                                <div class="form-text text-end" id="charCount">0 / 300 Letters</div>
                            </div>

              <div class="col-12 mb-3">
    <label class="form-label small fw-bold">Proof Attachments (Optional, Images/PDF, Max 2MB)</label>
    <input type="file" class="form-control form-control-sm" id="proof_attachments" name="proof_attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf">
    
    <div id="file_size_error" class="text-danger small fw-bold mt-1 d-none"></div>
    
    <div id="attachment_previews" class="d-flex flex-wrap gap-3 mt-2"></div>
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

    <div class="modal fade" id="viewApplicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white p-2">
                    <h5 class="modal-title fs-6"><i class="fas fa-file-alt me-2"></i> Application Document View</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" style="background: #fff; color: #000;" id="viewModalContent">
                    <div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Loading
                        Document...</div>
                </div>
                <div class="modal-footer bg-light p-2 border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close Window</button>
                    {{-- <button type="button" class="btn btn-sm btn-primary" id="btnPrintFromView"><i
                            class="fas fa-print"></i> Print Application</button> --}}
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="approveModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content border-success">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="approveModalTitle"><i class="fas fa-check-circle me-2"></i>Approve
                        Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="approve_leave_id">
                    <input type="hidden" id="approve_leave_type">

                    <div class="row g-2 mb-3" id="approve_date_area">
                        <div class="col-6">
                            <label class="form-label fw-bold small">Approved From <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" id="approve_start_datetime"
                                onchange="checkDurationMismatch()">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small">Approved To <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" id="approve_end_datetime"
                                onchange="checkDurationMismatch()">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Approved Resume Duty Date & Time <span
                                class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control form-control-sm border-success"
                            id="approve_resume_datetime" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Approved Duration (Days/Hrs) <span
                                class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control form-control-sm border-primary"
                            id="approve_duration" required onkeyup="checkDurationMismatch()">
                        <small id="approve_duration_warning" class="text-danger fw-bold d-none mt-1 d-block"><i
                                class="fas fa-exclamation-triangle"></i> Warning: Date range mismatches with
                            duration!</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Remarks / Approver Note (Optional)</label>
                        <textarea class="form-control form-control-sm" id="approve_remarks" rows="2"
                            placeholder="Great work, approved!"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light p-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-success" id="btnConfirmApprove"
                        onclick="submitApprove()"><i class="fas fa-check"></i> Confirm Approval</button>
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
        const currentUserCtx = @json($userContext);

        let currentPage = 1;
        let selectedIds = [];
        let globalProfile = null;
        let authApiUrl = '/api/v1/admin/auth/me';
        if (window.location.pathname.startsWith('/employee')) authApiUrl = '/api/v1/employee/auth/me';
        else if (window.location.pathname.startsWith('/customer')) authApiUrl = '/api/v1/customer/auth/me';

        function getArray(res) {
            return res.data?.data || res.data || res || [];
        }

        function ensureOptionExists(selector, value, text) {
            if (value !== undefined && value !== null && value !== '') {
                if ($(selector).find(`option[value="${value}"]`).length === 0) {
                    $(selector).append(`<option value="${value}">${text}</option>`);
                }
            }
        }

        // 🔥 NAYA HELPER: Timezone Bug Fix (UTC to IST Local Time Converter)
        function getLocalInputFormat(dateStr, isDateTime = false) {
            if (!dateStr) return '';
            // JS Date automatically adjusts UTC (Z) to local browser time (IST)
            let safeStr = dateStr.includes('T') ? dateStr : dateStr.replace(' ', 'T');
            let d = new Date(safeStr);
            if (isNaN(d)) return '';

            let yyyy = d.getFullYear();
            let mm = String(d.getMonth() + 1).padStart(2, '0');
            let dd = String(d.getDate()).padStart(2, '0');

            if (!isDateTime) {
                return `${yyyy}-${mm}-${dd}`;
            } else {
                let hh = String(d.getHours()).padStart(2, '0');
                let min = String(d.getMinutes()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}T${hh}:${min}`;
            }
        }

        function loadCompaniesAsync() {
            return $.get('/api/v1/get-active-companies').then(res => {
                let arr = getArray(res);
                let opts = '<option value="">Select Company</option>';
                arr.forEach(c => opts += `<option value="${c.id}">${c.company_name}</option>`);
                $('#company_id').html(opts);
            });
        }

        function loadBranchesAsync(companyId) {
            if (!companyId) return Promise.resolve($('#branch_id').html('<option value="">Head Office (None)</option>'));
            return $.post('/api/v1/get-branches-by-companies', {
                company_ids: [companyId]
            }).then(res => {
                let arr = getArray(res);
                let opts = '<option value="">Head Office (None)</option>';
                arr.forEach(b => opts += `<option value="${b.id}">${b.branch_name}</option>`);
                $('#branch_id').html(opts);
            });
        }

        function loadDepartmentsAsync(companyId, branchId) {
            let bId = branchId !== null && branchId !== undefined ? branchId : '';
            if (!companyId) return Promise.resolve($('#department_id').html('<option value="">Select Department</option>'));
            return $.get(`/api/v1/get-departments-by-company?company_id=${companyId}&branch_id=${bId}`).then(res => {
                let arr = getArray(res);
                let opts = '<option value="">Select Department</option>';
                arr.forEach(d => opts += `<option value="${d.id}">${d.department_name}</option>`);
                $('#department_id').html(opts);
            });
        }

        function loadDesignationsAsync(deptId) {
            if (!deptId) return Promise.resolve($('#designation_id').html('<option value="">Select Designation</option>'));
            return $.get(`/api/v1/get-designations-by-dept?department_id=${deptId}`).then(res => {
                let arr = getArray(res);
                let opts = '<option value="">Select Designation</option>';
                arr.forEach(d => opts += `<option value="${d.id}">${d.designation_name}</option>`);
                $('#designation_id').html(opts);
            });
        }

        function loadUsersAsync(desigId, branchId, companyId, type) {
            let bId = branchId !== null && branchId !== undefined ? branchId : '';
            if (!desigId) return Promise.resolve($('#user_id').html('<option value="">Select Applicant</option>'));
            return $.get(
                `/api/v1/leave-applications/dropdown/users?designation_id=${desigId}&branch_id=${bId}&company_id=${companyId}&user_type=${type}`
            ).then(res => {
                let arr = getArray(res);
                let opts = '<option value="">Select Applicant</option>';
                arr.forEach(u => {
                    let name = type === 'member' ? `${u.full_name || u.name} (${u.member_id})` : (u
                        .full_name || u.name);
                    opts += `<option value="${u.id}">${name}</option>`;
                });
                $('#user_id').html(opts);
            });
        }

        function loadApplyToAsync(companyId, branchId) {
            let bId = branchId !== null && branchId !== undefined ? branchId : '';
            if (!companyId) return Promise.resolve($('#applied_to').html('<option value="">Select Approver...</option>'));
            return $.get(`/api/v1/leave-applications/dropdown/apply-to?company_id=${companyId}&branch_id=${bId}&application_type=${$('#application_type').val()}`).then(
                res => {
                    let arr = getArray(res);
                    let opts = '<option value="">Select Approver...</option>';
                    arr.forEach(a => opts += `<option value="${a.id}">${a.name}</option>`);
                    $('#applied_to').html(opts);
                });
        }

        function setupDropdowns(enable = true) {
            $('#company_id, #branch_id, #department_id, #designation_id, #user_type').off('change');
            if (!enable) return;

            $('#company_id').change(function() {
                loadBranchesAsync($(this).val()).then(() => $('#branch_id').trigger('change'));
            });
            $('#branch_id').change(function() {
                loadDepartmentsAsync($('#company_id').val(), $(this).val()).then(() => {
                    $('#department_id').trigger('change');
                    loadApplyToAsync($('#company_id').val(), $(this).val());
                });
            });
            $('#department_id').change(function() {
                loadDesignationsAsync($(this).val()).then(() => $('#designation_id').trigger('change'));
            });
            $('#designation_id, #user_type').change(function() {
                loadUsersAsync($('#designation_id').val(), $('#branch_id').val(), $('#company_id').val(), $(
                    '#user_type').val());
            });
        }

        async function fetchProfile() {
            if (globalProfile) return globalProfile;
            try {
                let res = await $.get(authApiUrl);
                let u = res.data || res;

                let roleStr = (u.role || '').toLowerCase();
                let emailStr = (u.email || '').toLowerCase();

                if (!u.department_id && u.id && roleStr !== 'admin' && emailStr !== 'admin@jankivilla.com') {
                    try {
                        let empRes = await $.get(`/api/v1/employees/${u.id}`);
                        let empData = empRes.data || empRes;
                        if (empData && empData.department_id) {
                            u.department_id = empData.department_id;
                            u.designation_id = empData.designation_id;
                        }
                    } catch (err) {
                        console.warn("Could not fetch extra profile details.", err);
                    }
                }

                let role = (u.role || '').toLowerCase();
                let desig = (u.designation_name || '').toLowerCase();
                let email = (u.email || '').toLowerCase();
                let isGod = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'].includes(email);

                globalProfile = {
                    is_god: isGod || role === 'ceo' || desig.includes('ceo'),
                    is_director: role === 'director' || desig.includes('director'),
                    user_type: u.member_id ? 'member' : 'employee',
                    company_id: currentUserCtx.company_id || u.company_id || '',
                    branch_id: currentUserCtx.branch_id || u.branch_id || '',
                    department_id: currentUserCtx.department_id || u.department_id || '',
                    designation_id: currentUserCtx.designation_id || u.designation_id || '',
                    user_id: currentUserCtx.user_id || u.id,
                    user_name: currentUserCtx.user_name || u.full_name || u.name || 'Applicant'
                };
                return globalProfile;
            } catch (e) {
                console.error("Auth fetch failed via token", e);
                return null;
            }
        }

        function applyLocks(u) {
            $('.form-select').css('pointer-events', 'auto').removeClass('bg-light');
            if (!u || u.is_god) return;

            $('#company_id').css('pointer-events', 'none').addClass('bg-light');
            if (!u.is_director) {
                $('#branch_id, #department_id, #designation_id, #user_type, #user_id').css('pointer-events', 'none')
                    .addClass('bg-light');
            }
        }

        async function openLeaveModal() {
            $('#leaveApplicationForm')[0].reset();
            $('#attachment_previews').empty(); // Modal open hote hi purana preview clear ho jaye
            leaveAttachmentsDt = new DataTransfer();
            $('#leave_id').val('');
            $('#charCount').text('0 / 300 Letters').addClass('text-danger');
            handleAppTypeChange();

            $('#btnSaveLeave').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading Data...');
            $('#leaveApplicationModal').modal('show');

            setupDropdowns(false);

            let u = await fetchProfile();
            await loadCompaniesAsync();

            if (u && !u.is_god) {
                ensureOptionExists('#company_id', u.company_id, 'My Company');
                $('#company_id').val(u.company_id);

                await loadBranchesAsync(u.company_id);

                if (u.is_director) {
                    $('#branch_id').val('');
                    await loadDepartmentsAsync(u.company_id, '');
                    await loadDesignationsAsync('');
                    await loadUsersAsync('', '', u.company_id, 'employee');
                    await loadApplyToAsync(u.company_id, '');
                } else {
                    ensureOptionExists('#branch_id', u.branch_id, 'My Branch');
                    $('#branch_id').val(u.branch_id);

                    await loadDepartmentsAsync(u.company_id, u.branch_id);
                    ensureOptionExists('#department_id', u.department_id, u.department_name);
                    $('#department_id').val(u.department_id);

                    await loadDesignationsAsync(u.department_id);
                    ensureOptionExists('#designation_id', u.designation_id, u.designation_name);
                    $('#designation_id').val(u.designation_id);

                    $('#user_type').val(u.user_type);

                    await loadUsersAsync(u.designation_id, u.branch_id, u.company_id, u.user_type);
                    ensureOptionExists('#user_id', u.user_id, u.user_name);
                    $('#user_id').val(u.user_id);

                    await loadApplyToAsync(u.company_id, u.branch_id);
                }
            } else {
                await loadBranchesAsync('');
                await loadDepartmentsAsync('', '');
                await loadDesignationsAsync('');
                await loadUsersAsync('', '', '', 'employee');
                await loadApplyToAsync('', '');
            }

            applyLocks(u);
            setupDropdowns(true);
            $('#btnSaveLeave').prop('disabled', false).text('Submit Request');
        }

        async function editApplication(id) {
            let res = await $.get(`/api/v1/leave-applications/${id}`);
            let data = res.data;

            $('#leaveApplicationForm')[0].reset();
            $('#attachment_previews').empty(); // Modal open hote hi purana preview clear ho jaye
            leaveAttachmentsDt = new DataTransfer();
            $('#leave_id').val(data.id);
            $('#leaveModalTitle').text('Edit Leave / Request');
            $('#application_type').val(data.application_type);
            handleAppTypeChange();

            // 🔥 FIX: Applied getLocalInputFormat to handle UTC to IST correctly
            let isShort = data.application_type === 'Short Leave';
            if (data.start_datetime) $('#start_datetime').val(getLocalInputFormat(data.start_datetime, isShort));
            if (data.end_datetime) $('#end_datetime').val(getLocalInputFormat(data.end_datetime, isShort));

            $('#reason').val(data.reason).trigger('input');
            calculateDuration();

            $('#resume_datetime').val(getLocalInputFormat(data.resume_datetime, true));
            $('#emergency_contact').val(data.emergency_contact);
            $('#emergency_email').val(data.emergency_email);
            $('#is_paid_leave').prop('checked', data.is_paid_leave == 1);

            $('#leaveApplicationModal').modal('show');

            setupDropdowns(false);

            await loadCompaniesAsync();
            ensureOptionExists('#company_id', data.company_id, data.company?.company_name || 'Company');
            $('#company_id').val(data.company_id);

            await loadBranchesAsync(data.company_id);
            ensureOptionExists('#branch_id', data.branch_id, data.branch?.branch_name || 'Branch');
            $('#branch_id').val(data.branch_id || '');

            await loadDepartmentsAsync(data.company_id, data.branch_id);
            ensureOptionExists('#department_id', data.department_id, data.department?.department_name || 'Department');
            $('#department_id').val(data.department_id || '');

            await loadDesignationsAsync(data.department_id);
            ensureOptionExists('#designation_id', data.designation_id, data.designation?.designation_name ||
                'Designation');
            $('#designation_id').val(data.designation_id || '');

            $('#user_type').val(data.user_type);

            await loadUsersAsync(data.designation_id, data.branch_id, data.company_id, data.user_type);
            let uName = data.user_type === 'employee' ? data.employee?.full_name : data.member?.full_name;
            ensureOptionExists('#user_id', data.user_id, uName || 'Applicant');
            $('#user_id').val(data.user_id);

            await loadApplyToAsync(data.company_id, data.branch_id);
            if (data.applied_to) {
                ensureOptionExists('#applied_to', data.applied_to, data.applied_to);
            }
            $('#applied_to').val(data.applied_to);

            let u = await fetchProfile();
            applyLocks(u);
            setupDropdowns(true);
        }

        function handleAppTypeChange() {
            let type = $('#application_type').val();
            if (type === 'Other') {
                $('.date-time-fields').hide();
                $('#start_datetime, #end_datetime').val('').removeAttr('required');
                $('#duration_display').val('N/A');
                
                // NEW: Resume date optional for Other
                $('#resume_datetime').removeAttr('required');
                $('#resume_label_star').hide();
            } else {
                $('.date-time-fields').show();
                $('#start_datetime, #end_datetime').attr('required', true);
                
                // NEW: Resume date required
                $('#resume_datetime').attr('required', true);
                $('#resume_label_star').show();

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
            loadApplyToAsync($('#company_id').val(), $('#branch_id').val());
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

            if (startDate.getFullYear() < 1000 || endDate.getFullYear() < 1000) {
                return;
            }

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
            $('#charCount').text(len + ' / 300 Letters');
            if (len < 300) $('#charCount').addClass('text-danger').removeClass('text-success');
            else $('#charCount').removeClass('text-danger').addClass('text-success fw-bold');
        });

        function viewApplication(id) {
            $('#viewModalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#viewApplicationModal').modal('show');

            $.get(`/api/v1/leave-applications/${id}/view`, function(htmlContent) {
                $('#viewModalContent').html(htmlContent);
                $('#btnPrintFromView').attr('onclick', `printApplication(${id})`);
            }).fail(function() {
                $('#viewModalContent').html(
                    '<div class="text-danger text-center py-4">Failed to load document data.</div>');
            });
        }

        function printApplication(id) {
            let portalPrefix = window.location.pathname.split('/')[1];
            window.open(`/${portalPrefix}/leave-applications/print/${id}`, '_blank');
        }

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
            if (status === 'approved' || status === 'active') return '<span class="badge bg-success">Approved</span>';
            if (status === 'rejected') return '<span class="badge bg-danger">Rejected</span>';
            return '<span class="badge bg-warning text-dark">Pending</span>';
        }

        function getActionButtons(row) {
            let isOwner = globalProfile && (row.user_id == globalProfile.user_id && row.user_type === globalProfile
                .user_type);
            let html = `<div class="btn-group">`;

            let uPerms = window.userPerms || [];
            let isGod = globalProfile ? globalProfile.is_god : (window.userGodMode || false);

            // View Button
            html +=
                `<button class="btn btn-sm btn-outline-info" onclick="viewApplication(${row.id})" title="View"><i class="fas fa-eye"></i></button>`;

            // Edit Request Button
            if (row.status === 'pending' && (isOwner || uPerms.includes('leave_edit') || isGod)) {
                html +=
                    `<button class="btn btn-sm btn-outline-primary" onclick="editApplication(${row.id})" title="Edit Request"><i class="fas fa-edit"></i></button>`;
            }

            // Approve & Reject Buttons
            if (row.status === 'pending' && !isOwner) {
                if (uPerms.includes('leave_appr') || isGod) {
                    let resumeDt = row.resume_datetime || '';
                    html +=
                        `<button class="btn btn-sm btn-outline-success" onclick="openApproveModal(${row.id}, '${row.duration}', '${row.application_type}', '${row.start_datetime}', '${row.end_datetime}', '${resumeDt}')" title="Approve"><i class="fas fa-check"></i></button>`;
                }
                if (uPerms.includes('leave_rej') || isGod) {
                    html +=
                        `<button class="btn btn-sm btn-outline-danger" onclick="openRejectModal(${row.id})" title="Reject"><i class="fas fa-times"></i></button>`;
                }
            }
            // EDIT APPROVAL FEATURE
            else if (row.status === 'approved' && !isOwner) {
                if (uPerms.includes('leave_appr') || isGod) {
                    let resumeDt = row.approved_resume_datetime || row.resume_datetime || '';
                    let startDt = row.approved_start_datetime || row.start_datetime || '';
                    let endDt = row.approved_end_datetime || row.end_datetime || '';
                    let duration = row.approved_duration || row.duration || '';
                    let remarks = row.remarks || '';

                    html +=
                        `<button class="btn btn-sm btn-outline-warning" onclick="openApproveModal(${row.id}, '${duration}', '${row.application_type}', '${startDt}', '${endDt}', '${resumeDt}', '${remarks}', true)" title="Edit Approval"><i class="fas fa-user-edit"></i></button>`;
                }
            }
            // 🔥 NAYA: EDIT REJECTION FEATURE (Ye add karna hai)
            else if (row.status === 'rejected' && !isOwner) {
                if (uPerms.includes('leave_rej') || isGod) {
                    let remarks = row.remarks || '';
                    // Remarks me agar single quote hua to error na aaye isliye escape kiya hai
                    html += `<button class="btn btn-sm btn-outline-danger" onclick="openRejectModal(${row.id}, '${remarks.replace(/'/g, "\\'")}', true)" title="Edit Rejection Remark"><i class="fas fa-edit"></i></button>`;
                }
            }

            // Print Button
            if (row.status !== 'pending' && (uPerms.includes('leave_print') || isGod)) {
                html +=
                    `<button class="btn btn-sm btn-outline-secondary" onclick="printApplication(${row.id})" title="Print"><i class="fas fa-print"></i></button>`;
            }

            // Delete Button
            if ((isOwner && row.status === 'pending') || uPerms.includes('leave_delete') || isGod) {
                html +=
                    `<button class="btn btn-sm btn-outline-danger" onclick="deleteSingleApplication(${row.id})" title="Delete"><i class="fas fa-trash"></i></button>`;
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
                    ?.full_name || 'N/A');
                let typeSuffix = item.application_type === 'Short Leave' ? 'Hrs' : 'Days';
                let durationStr = item.duration ? `${item.duration} ${typeSuffix}` : 'N/A';

                if (item.status === 'approved' && item.approved_duration) {
                    if (parseFloat(item.duration) !== parseFloat(item.approved_duration)) {
                        durationStr = `<div class="d-flex flex-column lh-1">
                            <small class="text-decoration-line-through text-danger" title="Requested">Req: ${item.duration} ${typeSuffix}</small>
                            <span class="fw-bold text-success mt-1" title="Approved">App: ${item.approved_duration} ${typeSuffix}</span>
                        </div>`;
                    } else {
                        durationStr =
                            `<span class="fw-bold text-success">${item.approved_duration} ${typeSuffix}</span>`;
                    }
                }

                let paidBadge = item.is_paid_leave ?
                    '<span class="badge bg-success ms-1" style="font-size: 10px;"><i class="fas fa-rupee-sign"></i> Paid</span>' :
                    '';

                let tr = `<tr>
                    <td><input type="checkbox" class="form-check-input row-checkbox" value="${item.id}"></td>
                    <td class="fw-bold">${name} <br><small class="text-muted">${item.company?.company_name}</small></td>
                    <td><span class="badge bg-primary">${item.application_type}</span> ${paidBadge}</td>
                    <td>${durationStr}</td>
                    <td>${formatAppDateRange(item)}</td>
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
                let typeSuffix = item.application_type === 'Short Leave' ? 'Hrs' : 'Days';
                let durationStr = item.duration ? `${item.duration} ${typeSuffix}` : 'N/A';

                if (item.status === 'approved' && item.approved_duration) {
                    if (parseFloat(item.duration) !== parseFloat(item.approved_duration)) {
                        durationStr =
                            `<s class="text-danger small">${item.duration}</s> <strong class="text-success">${item.approved_duration} ${typeSuffix}</strong>`;
                    } else {
                        durationStr =
                            `<strong class="text-success">${item.approved_duration} ${typeSuffix}</strong>`;
                    }
                }

                let paidBadge = item.is_paid_leave ?
                    '<span class="badge bg-success ms-1"><i class="fas fa-rupee-sign"></i> Paid</span>' : '';

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
                            <span class="badge bg-light text-dark border me-1">${item.application_type}</span> ${paidBadge}
                            <span class="badge bg-light text-dark border"><i class="far fa-clock"></i> ${durationStr}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                            <div class="small text-muted text-truncate" style="max-width: 60%;">${formatAppDateRange(item)}</div>
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
                        table_name: 'leave_applications',
                        ids: selectedIds
                    }, function(res) {
                        Swal.fire('Deleted!', 'Records have been deleted.', 'success');
                        loadLeaveData(currentPage);
                    }).fail(function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Error performing bulk delete.',
                            'error');
                    });
                }
            });
        }

        function deleteSingleApplication(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to delete this application?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/api/v1/leave-applications/${id}`,
                        type: 'DELETE',
                        success: function(res) {
                            if (res.success) {
                                Swal.fire('Deleted!', res.message, 'success');
                                loadLeaveData(currentPage);
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.message ||
                                'Error deleting application', 'error');
                        }
                    });
                }
            });
        }

      function submitLeaveRequest() {
            if (!$('#leaveApplicationForm')[0].checkValidity()) {
                $('#leaveApplicationForm')[0].reportValidity();
                return;
            }
            
            // 🔥 NAYA: FormData for Files
            let form = $('#leaveApplicationForm')[0];
            let formData = new FormData(form);
            let id = $('#leave_id').val();

            $('#btnSaveLeave').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
            let url = id ? `/api/v1/leave-applications/${id}` : '/api/v1/leave-applications';
            
            // Laravel me form data ke sath PUT method direct nahi jata, method append karna padta hai
            if (id) formData.append('_method', 'PUT');

            $.ajax({
                url: url,
                type: 'POST', // AJAX call is POST
                data: formData,
                processData: false, // Required for File Upload
                contentType: false, // Required for File Upload
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
                        for (let key in errors) { errorMsg += errors[key][0] + '<br>'; }
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

        // 🔥 FIX: Applied getLocalInputFormat to handle UTC to IST correctly
        function openApproveModal(id, requestedDuration, type, startDt, endDt, resumeDt, remarks = '', isEdit = false) {
            $('#approve_leave_id').val(id);
            $('#approve_leave_type').val(type);
            $('#approve_duration').val(requestedDuration);
            $('#approve_remarks').val(remarks);

            $('#approveModalTitle').html(isEdit ? '<i class="fas fa-edit me-2"></i>Edit Approval' :
                '<i class="fas fa-check-circle me-2"></i>Approve Request');
            $('#btnConfirmApprove').html(isEdit ? '<i class="fas fa-save"></i> Save Changes' :
                '<i class="fas fa-check"></i> Confirm Approval');

            let isShort = type === 'Short Leave';

            // Convert to Local Time Format using the new helper
            let formatStart = getLocalInputFormat(startDt, isShort);
            let formatEnd = getLocalInputFormat(endDt, isShort);
            let formatResume = getLocalInputFormat(resumeDt, true);

            $('#approve_start_datetime').attr('type', isShort ? 'datetime-local' : 'date').val(formatStart);
            $('#approve_end_datetime').attr('type', isShort ? 'datetime-local' : 'date').val(formatEnd);
            $('#approve_resume_datetime').val(formatResume);

            $('#approve_duration_warning').addClass('d-none');
            $('#approveModal').modal('show');
        }

        function checkDurationMismatch() {
            let type = $('#approve_leave_type').val();
            let start = $('#approve_start_datetime').val();
            let end = $('#approve_end_datetime').val();
            let manualDuration = parseFloat($('#approve_duration').val());

            if (!start || !end || isNaN(manualDuration)) return;

            let startDate = new Date(start);
            let endDate = new Date(end);

            if (startDate.getFullYear() < 1000 || endDate.getFullYear() < 1000) {
                return;
            }

            if (endDate < startDate) {
                $('#approve_duration_warning').text('End date/time cannot be before start date/time!').removeClass(
                'd-none');
                return;
            }

            let calcDuration = 0;
            if (type === 'Leave') {
                calcDuration = Math.ceil(Math.abs(endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
            } else if (type === 'Short Leave') {
                calcDuration = (Math.abs(endDate - startDate) / (1000 * 60 * 60)).toFixed(2);
            }

            if (parseFloat(calcDuration) !== manualDuration) {
                $('#approve_duration_warning').html(
                    `<i class="fas fa-exclamation-triangle"></i> Warning: Selected dates equal to ${calcDuration} ${type==='Leave'?'days':'hours'}, but you entered ${manualDuration}!`
                    ).removeClass('d-none');
            } else {
                $('#approve_duration_warning').addClass('d-none');
            }
        }

        function submitApprove() {
            let id = $('#approve_leave_id').val();
            let duration = $('#approve_duration').val();
            let remarks = $('#approve_remarks').val();
            let start = $('#approve_start_datetime').val();
            let end = $('#approve_end_datetime').val();
            let resume = $('#approve_resume_datetime').val();

            if (!duration || duration <= 0 || !start || !end || !resume) {
                Swal.fire('Error', 'Please fill all valid dates, resume time and duration.', 'error');
                return;
            }

            $.ajax({
                url: `/api/v1/leave-applications/${id}/approve`,
                type: 'POST',
                data: {
                    approved_duration: duration,
                    approved_start_datetime: start,
                    approved_end_datetime: end,
                    approved_resume_datetime: resume,
                    remarks: remarks
                },
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Saved!', res.message, 'success');
                        $('#approveModal').modal('hide');
                        loadLeaveData(currentPage);
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error', xhr.responseJSON.message || 'Error processing application', 'error');
                }
            });
        }

      // 🔥 NAYA: Updated openRejectModal function
        function openRejectModal(id, remarks = '', isEdit = false) {
            $('#reject_leave_id').val(id);
            $('#reject_remarks').val(remarks);
            
            // Modal Title aur Button Text dynamically change karo
            let modalTitle = isEdit ? '<i class="fas fa-edit me-2"></i>Edit Rejection Remark' : '<i class="fas fa-times-circle me-2"></i>Reject Request';
            let btnText = isEdit ? '<i class="fas fa-save"></i> Save Changes' : '<i class="fas fa-times"></i> Confirm Rejection';
            
            $('#rejectModal .modal-title').html(modalTitle);
            $('#rejectModal .btn-danger').attr('onclick', 'submitReject()').html(btnText);

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

        // NAYA HELPER: Date Range Formatter
        function formatAppDateRange(item) {
            if(item.application_type === 'Other') return '<span class="text-muted">N/A</span>';
            
            let isShort = item.application_type === 'Short Leave';
            let start = new Date(item.start_datetime);
            let end = new Date(item.end_datetime);
            
            if (isNaN(start) || isNaN(end)) return 'N/A';

            let optDate = { day: '2-digit', month: 'short', year: 'numeric' };
            let optTime = { hour: '2-digit', minute: '2-digit', hour12: true };
            
            let sDate = start.toLocaleDateString('en-IN', optDate);
            let eDate = end.toLocaleDateString('en-IN', optDate);
            
            if(isShort) {
                let sTime = start.toLocaleTimeString('en-IN', optTime);
                let eTime = end.toLocaleTimeString('en-IN', optTime);
                return `<div class="small lh-sm"><b>From:</b> ${sDate} ${sTime}<br><b>To:</b> <span class="ms-3">${eDate} ${eTime}</span></div>`;
            } else {
                return `<div class="small lh-sm"><b>From:</b> ${sDate}<br><b>To:</b> <span class="ms-3">${eDate}</span></div>`;
            }
        }

let leaveAttachmentsDt = new DataTransfer();

        $('#proof_attachments').on('change', function(e) {
            let errorDiv = $('#file_size_error');
            errorDiv.addClass('d-none').text(''); // Reset error
            let hasError = false;

            if (e.target.files.length > 0 && e.target.files !== leaveAttachmentsDt.files) {
                let newDt = new DataTransfer();
                
                Array.from(e.target.files).forEach(file => {
                    // Check file size (2MB = 2 * 1024 * 1024 bytes)
                    if (file.size > 2 * 1024 * 1024) {
                        hasError = true;
                    } else {
                        newDt.items.add(file);
                    }
                });
                leaveAttachmentsDt = newDt; // Sirf valid files array me jayengi
            }

            // Agar koi file 2MB se badi thi, toh Error dikhao
            if (hasError) {
                errorDiv.removeClass('d-none').html('<i class="fas fa-exclamation-circle"></i> Error: One or more files exceed the 2MB limit and have been removed from selection.');
            }

            let container = $('#attachment_previews');
            container.empty();

            Array.from(leaveAttachmentsDt.files).forEach((file, index) => {
                let fileURL = URL.createObjectURL(file);
                let isImage = file.type.startsWith('image/');
                let isPdf = file.type === 'application/pdf';

                let removeBtn = `<button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle remove-file" data-index="${index}" style="z-index: 20; width: 22px; height: 22px; padding: 0; line-height: 1;" title="Remove this file"><i class="fas fa-times"></i></button>`;

                let previewHtml = '';
                if (isImage) {
                    previewHtml = `
                    <div class="position-relative border rounded p-1 bg-white shadow-sm" style="width: 120px; height: 120px;">
                        ${removeBtn}
                        <img src="${fileURL}" class="w-100 h-100 rounded" style="object-fit: cover;">
                    </div>`;
                } else if (isPdf) {
                    previewHtml = `
                    <div class="position-relative border rounded p-1 bg-white shadow-sm" style="width: 120px; height: 120px; overflow: hidden;">
                        ${removeBtn}
                        <embed src="${fileURL}#toolbar=0&navpanes=0&scrollbar=0" type="application/pdf" width="100%" height="100%" class="rounded"></embed>
                        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: transparent; z-index: 10;"></div>
                    </div>`;
                }

                if(previewHtml) container.append(previewHtml);
            });

            // Input field ko hamare maintained valid files se sync karein
            this.files = leaveAttachmentsDt.files;
        });

        // 🔥 Remove Button Action
        $(document).on('click', '.remove-file', function() {
            let indexToRemove = $(this).data('index');
            let newDt = new DataTransfer();

            Array.from(leaveAttachmentsDt.files).forEach((file, index) => {
                if (index !== indexToRemove) {
                    newDt.items.add(file); // Jis index ko hatana hai usko chhod kar baaki sab copy
                }
            });

            leaveAttachmentsDt = newDt; // Array update
            $('#proof_attachments')[0].files = leaveAttachmentsDt.files; // Input update
            $('#proof_attachments').trigger('change'); // Preview re-render
        });


        $(document).ready(async function() {
            await fetchProfile();
            loadLeaveData(1);
        });
    </script>
@endpush
