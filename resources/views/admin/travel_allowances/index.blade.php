@extends('layout.app')

@section('content')
    <style>
        #bulkActionBar {
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: #1A365D;
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            display: none;
            z-index: 1050;
            align-items: center;
            gap: 15px;
        }

        .mobile-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
    </style>

    <div class="container-fluid py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <h4 class="mb-0 fw-bold">Travel Allowances</h4>
            <div class="d-flex flex-grow-1 mx-md-3" style="max-width: 400px;">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control border-start-0 ps-0"
                        placeholder="Search employee, destination...">
                </div>
            </div>
            <button class="btn btn-primary text-nowrap" onclick="openTAModal()">
                <i class="fas fa-plus me-1"></i> Request TA
            </button>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block mb-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="taTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3"><input type="checkbox" id="selectAllDesktop" class="form-check-input">
                                </th>
                                <th>Date</th>
                                <th>Employee</th>
                                <th>Purpose & Destination</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="taTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-block d-md-none" id="taMobileContainer"></div>

        <div class="d-flex justify-content-between align-items-center mt-3 bg-white p-3 rounded shadow-sm">
            <span class="small text-muted" id="paginationInfo">Showing 0 of 0</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" id="btnPrevPage" onclick="changePage(-1)">Previous</button>
                <button class="btn btn-sm btn-outline-secondary" id="btnNextPage" onclick="changePage(1)">Next</button>
            </div>
        </div>
    </div>

    <div id="bulkActionBar" class="secured-item" data-permission="ta_delete">
        <span id="selectedCount">0 Selected</span>
        <button class="btn btn-light btn-sm fw-bold text-dark" onclick="selectAllMobile()">Select All</button>
        <button class="btn btn-danger btn-sm fw-bold" onclick="executeBulkDelete()"><i class="fas fa-trash"></i>
            Delete</button>
    </div>

    <div class="modal fade" id="taModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-fullscreen-md-down">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="taModalTitle">Request Travel Allowance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="taForm">
                    <div class="modal-body">
                        <input type="hidden" id="ta_id" name="id">
                        <div id="adminSelectionArea" class="row mb-3 bg-light p-3 rounded border">
                            <h6 class="fw-bold mb-3 text-primary">Office Hierarchy (Admin Only)</h6>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">Company</label>
                                <select class="form-select form-select-sm admin-select" id="company_id"
                                    name="company_id"></select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">Branch</label>
                                <select class="form-select form-select-sm admin-select" id="branch_id"
                                    name="branch_id"></select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label small">Department</label>
                                <select class="form-select form-select-sm admin-select" id="department_id"
                                    name="department_id"></select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label small">Designation</label>
                                <select class="form-select form-select-sm admin-select" id="designation_id"
                                    name="designation_id"></select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label small">Employee</label>
                                <select class="form-select form-select-sm admin-select" id="employee_id"
                                    name="employee_id"></select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3"><label class="form-label">Date</label><input type="date"
                                    class="form-control" name="ta_date" id="ta_date" required></div>
                            <div class="col-md-4 mb-3"><label class="form-label">Vehicle No.</label><input type="text"
                                    class="form-control" name="vehicle_no" id="vehicle_no"></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Purpose of Work</label>
                                <textarea class="form-control" name="purpose" id="purpose" rows="2" required></textarea>
                            </div>
                            <div class="col-md-6 mb-3"><label class="form-label">Destination</label>
                                <textarea class="form-control" name="destination" id="destination" rows="2" required></textarea>
                            </div>
                            <div class="col-md-4 mb-3"><label class="form-label">Distance (KMs)</label><input
                                    type="text" class="form-control" name="distance_km" id="distance_km"></div>
                            <div class="col-md-4 mb-3"><label class="form-label">Fuel (Litre)</label><input
                                    type="text" class="form-control" name="fuel_litre" id="fuel_litre"></div>
                            <div class="col-md-4 mb-3"><label class="form-label">In Time</label><input type="time"
                                    class="form-control" name="in_time" id="in_time"></div>
                            <div class="col-md-4 mb-3"><label class="form-label">Out Time</label><input type="time"
                                    class="form-control" name="out_time" id="out_time"></div>
                            <div class="col-md-4 mb-3"><label class="form-label fw-bold">Amount (₹)</label><input
                                    type="number" step="0.01" class="form-control border-primary" name="amount"
                                    id="amount" required></div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveTaBtn">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0" id="viewModalBody">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="remarksModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Remarks</h6><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"><input type="hidden" id="remark_ta_id">
                    <textarea id="ta_remark_text" class="form-control" rows="4"></textarea>
                </div>
                <div class="modal-footer p-1 secured-item" data-permission="ta_remark"><button
                        class="btn btn-primary btn-sm w-100" onclick="saveRemark()">Save</button></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const portal = window.location.pathname.split('/')[1];
        const apiUrl = `/api/v1/travel-allowances`;
        window.taDataList = [];

        // Pagination state
        let currentPage = 1;
        let totalPages = 1;
        let searchTimer;

        $(document).ready(function() {
            loadData(1);
            setupDropdowns();

            $('#taForm').on('submit', function(e) {
                e.preventDefault();
                saveTA();
            });
            $('#selectAllDesktop').on('change', function() {
                $('#taTableBody .row-checkbox').prop('checked', this.checked);
                toggleBulkActionBar();
            });
            $(document).on('change', '.row-checkbox', toggleBulkActionBar);

            // Live Search Debounce
            $('#searchInput').on('keyup', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    currentPage = 1;
                    loadData(1);
                }, 500);
            });
        });

        // ===============================
        // 1. DATA LOADING (PAGINATION)
        // ===============================
        function loadData(page) {
            currentPage = page;
            let search = $('#searchInput').val();

            $.get(`${apiUrl}?page=${page}&search=${search}`, function(res) {
                let records = res.data; // Laravel pagination object holds items in .data
                window.taDataList = records;

                totalPages = res.last_page || 1;
                $('#paginationInfo').text(`Showing Page ${currentPage} of ${totalPages}`);
                $('#btnPrevPage').prop('disabled', currentPage <= 1);
                $('#btnNextPage').prop('disabled', currentPage >= totalPages);

                if (records.length === 0) {
                    $('#taTableBody').html(
                        '<tr><td colspan="7" class="text-center text-muted py-4 fw-bold">No Records Found</td></tr>'
                        );
                    $('#taMobileContainer').html(
                        '<div class="text-center text-muted py-4 bg-white border rounded shadow-sm fw-bold">No Records Found</div>'
                        );
                    return;
                }

                let desktopHtml = '';
                let mobileHtml = '';

                records.forEach(item => {
                    // 🔥 Rename active to Approved, inactive/rejected to Rejected
                    let statusLabel = item.status === 'active' ? 'APPROVED' : (item.status === 'rejected' ?
                        'REJECTED' : 'PENDING');
                    let badgeClass = item.status === 'active' ? 'bg-success' : (item.status === 'rejected' ?
                        'bg-danger' : 'bg-warning text-dark');
                    let employeeName = item.employee ?
                        `${item.employee.full_name} (${item.employee.member_id})` : 'N/A';
                    let isOwnTA = (item.employee_id == window.userId);

                    // View, Print, Remarks
                    let actions = `
                    <button class="btn btn-sm btn-light border text-dark" onclick="viewTA(${item.id})" title="View"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-info text-white secured-item" data-permission="ta_print" onclick="printTA(${item.id})" title="Print"><i class="fas fa-print"></i></button>
                    <button class="btn btn-sm btn-secondary" onclick="openRemarks(${item.id}, '${item.remarks || ''}', ${isOwnTA})" title="Remarks"><i class="fas fa-comment"></i></button>
                `;

                    if(item.status === 'pending') {
                    // 🔥 FIX: Added (window.userPerms || []) fallback
                    let hasEditPerm = (window.userPerms || []).includes('ta_edit'); 
                    if(isOwnTA || hasEditPerm || window.userGodMode) {
                        actions += `<button class="btn btn-sm btn-primary" onclick="editTA(${item.id})" title="Edit"><i class="fas fa-pencil-alt"></i></button>`;
                    }
                        if (!isOwnTA || window.userGodMode) {
                            actions += `
                            <button class="btn btn-sm btn-success secured-item" data-permission="ta_appr" onclick="updateStatus(${item.id}, 'approve')" title="Approve"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-danger secured-item" data-permission="ta_rej" onclick="updateStatus(${item.id}, 'reject')" title="Reject"><i class="fas fa-times"></i></button>
                        `;
                        }
                    }

                    desktopHtml += `
                    <tr>
                        <td class="ps-3"><input type="checkbox" class="form-check-input row-checkbox" value="${item.id}"></td>
                        <td>${item.ta_date}</td>
                        <td class="fw-medium">${employeeName}</td>
                        <td><div class="text-truncate" style="max-width:200px;">${item.purpose}</div><small class="text-muted d-block text-truncate" style="max-width:200px;">${item.destination}</small></td>
                        <td class="fw-bold text-nowrap">₹${item.amount}</td>
                        <td><span class="badge ${badgeClass}">${statusLabel}</span></td>
                        <td class="pe-3"><div class="d-flex flex-wrap gap-1" style="min-width: 140px;">${actions}</div></td>
                    </tr>
                `;

                    mobileHtml += `
                    <div class="mobile-card shadow-sm">
                        <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" class="form-check-input row-checkbox" value="${item.id}">
                                <strong>${item.ta_date}</strong>
                            </div>
                            <span class="badge ${badgeClass}">${statusLabel}</span>
                        </div>
                        <div class="text-muted small mb-1"><i class="fas fa-user"></i> ${employeeName}</div>
                        <div class="small mb-2"><i class="fas fa-map-marker-alt"></i> ${item.purpose} - ${item.destination}</div>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                            <h6 class="mb-0 fw-bold text-primary">₹${item.amount}</h6>
                            <div class="d-flex flex-wrap gap-1">${actions}</div>
                        </div>
                    </div>
                `;
                });

                $('#taTableBody').html(desktopHtml);
                $('#taMobileContainer').html(mobileHtml);

                if (typeof window.applyPermissions === 'function') window.applyPermissions();
            });
        }

        function changePage(direction) {
            let newPage = currentPage + direction;
            if (newPage >= 1 && newPage <= totalPages) loadData(newPage);
        }

        // ===============================
        // 2. VIEW, ADD & EDIT (ASYNC PRE-FILL)
        // ===============================
        function viewTA(id) {
            $('#viewModalBody').html(
                '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2 text-muted">Loading Preview...</p></div>'
                );
            $('#viewModal').modal('show');
            $.get(`${apiUrl}/${id}`, function(res) {
                $('#viewModalBody').html(res.html);
            });
        }

        function openTAModal() {
            $('#taForm')[0].reset();
            $('#ta_id').val('');
            $('#taModalTitle').text('Request Travel Allowance');
            prepareFormView();
            $('#taModal').modal('show');
        }

        // 🔥 SMART ASYNC EDIT (Fixes Dropdown Pre-fill)
        async function editTA(id) {
            let item = window.taDataList.find(t => t.id === id);
            if (!item) return;

            $('#taForm')[0].reset();
            $('#ta_id').val(item.id);
            $('#taModalTitle').text('Edit Travel Allowance');

            $('#ta_date').val(item.ta_date);
            $('#vehicle_no').val(item.vehicle_no);
            $('#purpose').val(item.purpose);
            $('#destination').val(item.destination);
            $('#distance_km').val(item.distance_km);
            $('#fuel_litre').val(item.fuel_litre);
            $('#in_time').val(item.in_time);
            $('#out_time').val(item.out_time);
            $('#amount').val(item.amount);

            prepareFormView(item);

            // Wait for dropdowns to sequentially load if Admin
            if (portal !== 'employee' && (window.userGodMode || window.userPerms.includes('ta_edit'))) {
                $('#taModalBody').css('opacity', '0.5'); // Optional loading state

                await loadCompaniesAsync();
                $('#company_id').val(item.company_id);

                await loadBranchesAsync(item.company_id);
                $('#branch_id').val(item.branch_id || 'HO');

                await loadDepartmentsAsync(item.company_id, item.branch_id || 'HO');
                $('#department_id').val(item.department_id);

                await loadDesignationsAsync(item.department_id);
                $('#designation_id').val(item.designation_id);

                await loadEmployeesAsync(item.designation_id, item.branch_id || 'HO', item.company_id, item
                    .department_id);
                $('#employee_id').val(item.employee_id);

                $('#taModalBody').css('opacity', '1');
            }

            $('#taModal').modal('show');
        }

        function prepareFormView(item = null) {
            if (portal === 'employee' || (!window.userGodMode && !window.userPerms.includes('ta_appr'))) {
                $('#adminSelectionArea').hide();
                $('.admin-select').removeAttr('required');
                $.get(`/api/v1/${portal}/auth/me`, function(res) {
                    let user = res.data;
                    if ($('#hidden_emp_inputs').length === 0) {
                        $('#taForm').append(`<div id="hidden_emp_inputs">
                        <input type="hidden" name="company_id" id="h_company_id">
                        <input type="hidden" name="branch_id" id="h_branch_id">
                        <input type="hidden" name="department_id" id="h_department_id">
                        <input type="hidden" name="designation_id" id="h_designation_id">
                        <input type="hidden" name="employee_id" id="h_employee_id">
                    </div>`);
                    }
                    $('#h_company_id').val(user.company_id);
                    $('#h_branch_id').val(user.branch_id);
                    $('#h_department_id').val(user.department_id);
                    $('#h_designation_id').val(user.designation_id);
                    $('#h_employee_id').val(user.id);
                });
            } else {
                $('#adminSelectionArea').show();
                $('.admin-select').attr('required', true);
                if (!item) loadCompanies();
            }
        }

        function saveTA() {
            let id = $('#ta_id').val();
            $.ajax({
                url: id ? `${apiUrl}/${id}` : apiUrl,
                type: id ? 'PUT' : 'POST',
                data: $('#taForm').serialize(),
                success: function(res) {
                    $('#taModal').modal('hide');
                    loadData(currentPage);
                    Swal.fire('Success', res.message, 'success');
                },
                error: function(err) {
                    Swal.fire('Error', 'Please fill required fields properly.', 'error');
                }
            });
        }

        // ===============================
        // 3. WORKFLOW ACTIONS
        // ===============================
        function updateStatus(id, action) {
            Swal.fire({
                title: `Confirm ${action}?`,
                icon: 'warning',
                showCancelButton: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(`${apiUrl}/${id}/${action}`, function(res) {
                        loadData(currentPage);
                        Swal.fire('Done!', res.message, 'success');
                    });
                }
            });
        }

        function openRemarks(id, currentRemark, isOwnTA) {
            $('#remark_ta_id').val(id);
            $('#ta_remark_text').val(currentRemark !== 'null' && currentRemark ? currentRemark : '');
            let canModifyRemarks = window.userPerms.includes('ta_appr') || window.userPerms.includes('ta_rej') || window
                .userPerms.includes('ta_remark');

            if (isOwnTA || (!canModifyRemarks && !window.userGodMode)) {
                $('#ta_remark_text').prop('readonly', true);
                $('#remarksModal .modal-footer').hide();
            } else {
                $('#ta_remark_text').prop('readonly', false);
                $('#remarksModal .modal-footer').show();
            }
            $('#remarksModal').modal('show');
        }

        function saveRemark() {
            let id = $('#remark_ta_id').val();
            $.post(`${apiUrl}/${id}/remarks`, {
                remarks: $('#ta_remark_text').val()
            }, function(res) {
                $('#remarksModal').modal('hide');
                loadData(currentPage);
                Swal.fire('Saved', 'Remarks updated.', 'success');
            });
        }

        // ===============================
        // 4. BULK DELETE & CHECKBOXES
        // ===============================
        function toggleBulkActionBar() {
            let count = $('.row-checkbox:checked:visible').length;
            if (count > 0) {
                $('#selectedCount').text(`${count} Selected`);
                $('#bulkActionBar').css('display', 'flex');
            } else {
                $('#bulkActionBar').hide();
            }
        }

        function selectAllMobile() {
            let mobileCbs = $('#taMobileContainer .row-checkbox');
            mobileCbs.prop('checked', mobileCbs.length !== $('#taMobileContainer .row-checkbox:checked').length);
            toggleBulkActionBar();
        }

        function executeBulkDelete() {
            let ids = [];
            $('.row-checkbox:checked').each(function() {
                ids.push($(this).val());
            });
            Swal.fire({
                title: 'Delete Selected?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(`${apiUrl}/bulk-delete`, {
                        ids: ids
                    }, function() {
                        loadData(1);
                        $('#bulkActionBar').hide();
                        Swal.fire('Deleted!', '', 'success');
                    });
                }
            });
        }

        // ===============================
        // 5. CASCADING DROPDOWNS (PROMISIFIED FOR EDIT)
        // ===============================
        function getArray(res) {
            if (typeof res === 'string') {
                try {
                    res = JSON.parse(res);
                } catch (e) {
                    return [];
                }
            }
            if (Array.isArray(res)) return res;
            if (res && Array.isArray(res.data)) return res.data;
            if (res && res.data && Array.isArray(res.data.data)) return res.data.data;
            if (res && Array.isArray(res.designations)) return res.designations;
            if (res && Array.isArray(res.employees)) return res.employees;
            return [];
        }

        function loadCompaniesAsync() {
            return $.get('/api/v1/get-active-companies').then(res => {
                let opts = '<option value="">Select Company</option>';
                res.data.forEach(c => opts += `<option value="${c.id}">${c.company_name}</option>`);
                $('#company_id').html(opts);
            });
        }

        function loadBranchesAsync(cid) {
            if (!cid) return Promise.resolve($('#branch_id').html('<option value="">Select Branch</option>'));
            let companyName = $("#company_id option:selected").text();
            return $.get(`/api/v1/branches?company_id=${cid}`).then(res => {
                let arr = getArray(res);
                let opts =
                    `<option value="">Select Branch</option><option value="HO">${companyName} (Head Office)</option>`;
                arr.forEach(b => opts += `<option value="${b.id}">${b.branch_name}</option>`);
                $('#branch_id').html(opts);
            });
        }

        function loadDepartmentsAsync(cid, bid) {
            return $.get(`/api/v1/get-active-departments?company_id=${cid}&branch_id=${bid}`).then(res => {
                let arr = getArray(res);
                let opts = '<option value="">Select Dept</option>';
                arr.forEach(d => opts += `<option value="${d.id}">${d.department_name}</option>`);
                $('#department_id').html(opts);
            });
        }

        function loadDesignationsAsync(did) {
            return $.get(`/api/v1/get-designations-by-dept?department_id=${did}`).then(res => {
                let arr = getArray(res);
                let opts = '<option value="">Select Designation</option>';
                arr.forEach(d => opts += `<option value="${d.id}">${d.designation_name}</option>`);
                $('#designation_id').html(opts);
            });
        }

        function loadEmployeesAsync(desigId, branchId, companyId, deptId) {
            if (!desigId) return Promise.resolve($('#employee_id').html('<option value="">Select Employee</option>'));
            return $.get(
                `/api/v1/employees?designation_id=${desigId}&branch_id=${branchId}&company_id=${companyId}&department_id=${deptId}`
                ).then(res => {
                let arr = getArray(res);
                let opts = '<option value="">Select Employee</option>';
                arr.forEach(e => opts += `<option value="${e.id}">${e.full_name} (${e.member_id})</option>`);
                $('#employee_id').html(opts);
            });
        }

        function setupDropdowns() {
            $('#company_id').change(function() {
                loadBranchesAsync($(this).val());
            });
            $('#branch_id').change(function() {
                loadDepartmentsAsync($('#company_id').val(), $(this).val());
            });
            $('#department_id').change(function() {
                loadDesignationsAsync($(this).val());
            });
            $('#designation_id').change(function() {
                loadEmployeesAsync($(this).val(), $('#branch_id').val(), $('#company_id').val(), $('#department_id')
                    .val());
            });
        }

        function loadCompanies() {
            loadCompaniesAsync();
        }

        function printTA(id) {
            window.open(`/${portal}/travel-allowances/print/${id}`, '_blank');
        }
    </script>
@endpush
