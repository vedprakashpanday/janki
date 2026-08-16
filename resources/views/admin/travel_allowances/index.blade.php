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

        /* 🔥 MOBILE OFFCANVAS & DESKTOP MODAL CSS 🔥 */
        @media (max-width: 767.98px) {
            .mobile-bottom-sheet {
                align-items: flex-end;
                margin: 0;
                min-height: 100%;
            }

            .mobile-bottom-sheet .modal-content {
                border-radius: 20px 20px 0 0 !important;
                border: none;
                max-height: 90vh;
                /* 🔥 FIX: Modal ko thoda chhota rakha taaki scroll kaam kare */
                overflow: hidden;
                /* 🔥 FIX: Design bahar na nikle */
            }

            .modal.fade .mobile-bottom-sheet {
                transform: translateY(100%);
            }

            .modal.show .mobile-bottom-sheet {
                transform: translateY(0);
            }
        }
    </style>

    <div class="container-fluid py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <h4 class="mb-0 fw-bold">Travelling & Conveyance Expenses</h4>
            <div class="d-flex flex-grow-1 mx-md-3" style="max-width: 400px;">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control border-start-0 ps-0"
                        placeholder="Search employee, destination...">
                </div>
            </div>
            <button class="btn btn-primary text-nowrap" onclick="openTAModal()">
                Travelling & Conveyance Expenses
            </button>
        </div>

  <!-- 🔥 TOP FILTERS SECTION (Zero Trust Compatible - Hidden by Default) 🔥 -->
        <div class="card border-0 shadow-sm mb-4 bg-white" id="adminTopFilters" style="display: none;">
            <div class="card-body p-3">
                <h6 class="fw-bold text-primary mb-3"><i class="fas fa-filter"></i> Advanced Filters (Type min 3 letters)</h6>
                <div class="row g-2 align-items-end">
                    
                    <div class="col-md-2 position-relative">
                        <label class="small text-muted fw-bold">Company</label>
                        <input type="text" class="form-control form-control-sm search-input" id="search_company" placeholder="Type Company...">
                        <input type="hidden" id="filter_company_id">
                        <ul class="dropdown-menu w-100 autocomplete-dropdown shadow-sm" id="suggest_company"></ul>
                    </div>

                    <div class="col-md-2 position-relative">
                        <label class="small text-muted fw-bold">Branch</label>
                        <input type="text" class="form-control form-control-sm search-input" id="search_branch" placeholder="Type Branch...">
                        <input type="hidden" id="filter_branch_id">
                        <ul class="dropdown-menu w-100 autocomplete-dropdown shadow-sm" id="suggest_branch"></ul>
                    </div>

                    <div class="col-md-2 position-relative">
                        <label class="small text-muted fw-bold">Department</label>
                        <input type="text" class="form-control form-control-sm search-input" id="search_department" placeholder="Type Dept...">
                        <input type="hidden" id="filter_department_id">
                        <ul class="dropdown-menu w-100 autocomplete-dropdown shadow-sm" id="suggest_department"></ul>
                    </div>

                    <div class="col-md-2 position-relative">
                        <label class="small text-muted fw-bold">Designation</label>
                        <input type="text" class="form-control form-control-sm search-input" id="search_designation" placeholder="Type Desig...">
                        <input type="hidden" id="filter_designation_id">
                        <ul class="dropdown-menu w-100 autocomplete-dropdown shadow-sm" id="suggest_designation"></ul>
                    </div>

                    <div class="col-md-2 position-relative">
                        <label class="small text-muted fw-bold">Employee</label>
                        <input type="text" class="form-control form-control-sm search-input" id="search_employee" placeholder="Type Emp Name/ID...">
                        <input type="hidden" id="filter_employee_id">
                        <ul class="dropdown-menu w-100 autocomplete-dropdown shadow-sm" id="suggest_employee"></ul>
                    </div>

                    <div class="col-md-1">
                        <label class="small text-muted fw-bold">Month</label>
                        <input type="month" class="form-control form-control-sm" id="filter_month">
                    </div>

                    <div class="col-md-1">
                        <button class="btn btn-primary btn-sm w-100 fw-bold" id="applyFilterBtn">
                            <i class="fas fa-check"></i> Apply
                        </button>
                    </div>

                </div>
            </div>
        </div>
        
        <!-- 🔥 DYNAMIC SUMMARY SECTION (Hidden by default) 🔥 -->
        <div class="row mb-4" id="summarySection" style="display: none;">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm bg-light-primary" style="border-left: 4px solid #1A365D !important;">
                    <div class="card-body py-2 d-flex justify-content-between align-items-center">
                        <div class="fw-bold text-muted small">Total Requested Amount (This Month)</div>
                        <h5 class="mb-0 fw-bold text-primary" id="sumApplied">₹0.00</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mt-2 mt-md-0">
                <div class="card border-0 shadow-sm bg-light-success" style="border-left: 4px solid #28a745 !important;">
                    <div class="card-body py-2 d-flex justify-content-between align-items-center">
                        <div class="fw-bold text-muted small">Total Approved Amount (This Month)</div>
                        <h5 class="mb-0 fw-bold text-success" id="sumApproved">₹0.00</h5>
                    </div>
                </div>
            </div>
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
                                <th>Person Info</th>
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
    <div class="modal fade" id="taModal" tabindex="-1" aria-labelledby="taModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable mobile-bottom-sheet">

            <form id="taForm" class="modal-content mb-0">

                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold" id="taModalTitle">Travelling & Conveyance Expenses</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-3 p-md-4"> <input type="hidden" id="ta_id" name="id">

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

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Person Name</label>
                            <input type="text" class="form-control" name="person_name" id="person_name"
                                placeholder="Self or Guest Name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Person Contact</label>
                            <input type="text" class="form-control" name="person_number" id="person_number"
                                placeholder="Mobile Number">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">No. of Persons <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="number_of_persons" id="number_of_persons"
                                value="1" min="1" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Purpose of Work <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="purpose" id="purpose" rows="3" minlength="200"
                                placeholder="Detail your purpose (Minimum 200 characters required)..." required></textarea>
                            <small id="purposeCount" class="text-danger fw-bold">0 / 200 minimum characters</small>
                        </div>
                        <div class="col-md-6 mb-3"><label class="form-label">Destination</label>
                            <textarea class="form-control" name="destination" id="destination" rows="2" required></textarea>
                        </div>
                        <div class="col-md-4 mb-3"><label class="form-label">Distance (KMs)</label><input type="text"
                                class="form-control" name="distance_km" id="distance_km"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Fuel (Litre)</label><input type="text"
                                class="form-control" name="fuel_litre" id="fuel_litre"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">In Time</label><input type="time"
                                class="form-control" name="in_time" id="in_time"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Out Time</label><input type="time"
                                class="form-control" name="out_time" id="out_time"></div>
                        <div class="col-md-4 mb-3"><label class="form-label fw-bold">Amount (₹)</label><input
                                type="number" step="0.01" class="form-control border-primary" name="amount"
                                id="amount" required></div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Upload Proof(s) <span class="text-danger">*</span> <small
                                    class="text-muted">(Select multiple JPG/PDF)</small></label>
                            <input type="file" class="form-control" id="proof_file_input"
                                accept=".jpg,.jpeg,.png,.webp,.pdf" multiple>
                            <div id="proof_preview_container" class="d-flex flex-wrap gap-2 mt-2"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveTaBtn">Submit Request</button>
                </div>
            </form>

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
           // 🔥 NAYA: Zero Trust Client-Side Admin Check 🔥
            let isSuperAdmin = window.userGodMode === true;
            let hasApprovePerm = Array.isArray(window.userPerms) && window.userPerms.includes('ta_appr');
            let isPortalAdmin = portal === 'admin'; // Portal URL based check

            // Agar user ke paas rights hain toh hi filter section dikhayein aur uski JS load karein
            if (isSuperAdmin || hasApprovePerm || isPortalAdmin) {
                $('#adminTopFilters').show();
                setupTopFilters(); 
            }

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

            // Live character counter for Purpose
            $('#purpose').on('input', function() {
                let len = $(this).val().trim().length;
                $('#purposeCount').text(`${len} / 200 minimum characters`);
                if (len < 200) {
                    $('#purposeCount').removeClass('text-success').addClass('text-danger');
                } else {
                    $('#purposeCount').removeClass('text-danger').addClass('text-success');
                }
            });
        });

        // ===============================
        // 🔥 SMART AUTOCOMPLETE FILTERS 🔥
        // ===============================
        function setupTopFilters() {
            // 1. Reusable Autocomplete Binder Function
            function bindAutocomplete(type, inputId, hiddenId, suggestId, getDependencies, resetNext) {
                let timer;
                
                $(inputId).on('keyup', function() {
                    clearTimeout(timer);
                    let q = $(this).val();
                    let suggestBox = $(suggestId);
                    
                    if(q.trim() === '') {
                        $(hiddenId).val('');
                        suggestBox.removeClass('show');
                        if(resetNext) resetNext();
                        return;
                    }

                    if (q.length < 3) {
                        suggestBox.removeClass('show');
                        return;
                    }

                    let deps = getDependencies();
                    timer = setTimeout(() => {
                        $.get(`/api/v1/travel-allowances/search-filters`, { type: type, q: q, ...deps }, function(res) {
                            let html = '';
                            if (res.length > 0) {
                                res.forEach(item => {
                                    html += `<li><a class="dropdown-item small select-item py-1" href="#" data-id="${item.id}" data-text="${item.text}">${item.text}</a></li>`;
                                });
                            } else {
                                html = `<li><span class="dropdown-item small text-muted">No matching record</span></li>`;
                            }
                            suggestBox.html(html).addClass('show');
                        });
                    }, 400); 
                });

                $(suggestId).on('click', '.select-item', function(e) {
                    e.preventDefault();
                    $(inputId).val($(this).data('text'));
                    $(hiddenId).val($(this).data('id'));
                    $(suggestId).removeClass('show');
                    if(resetNext) resetNext(); 
                });
            }

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.position-relative').length) {
                    $('.autocomplete-dropdown').removeClass('show');
                }
            });

            bindAutocomplete('company', '#search_company', '#filter_company_id', '#suggest_company', 
                () => ({}), 
                () => { $('#search_branch, #search_department, #search_designation, #search_employee').val(''); $('#filter_branch_id, #filter_department_id, #filter_designation_id, #filter_employee_id').val(''); }
            );

            bindAutocomplete('branch', '#search_branch', '#filter_branch_id', '#suggest_branch', 
                () => ({ company_id: $('#filter_company_id').val() }), 
                () => { $('#search_department, #search_designation, #search_employee').val(''); $('#filter_department_id, #filter_designation_id, #filter_employee_id').val(''); }
            );

            bindAutocomplete('department', '#search_department', '#filter_department_id', '#suggest_department', 
                () => ({ company_id: $('#filter_company_id').val(), branch_id: $('#filter_branch_id').val() }), 
                () => { $('#search_designation, #search_employee').val(''); $('#filter_designation_id, #filter_employee_id').val(''); }
            );

            bindAutocomplete('designation', '#search_designation', '#filter_designation_id', '#suggest_designation', 
                () => ({ department_id: $('#filter_department_id').val() }), 
                () => { $('#search_employee').val(''); $('#filter_employee_id').val(''); }
            );

            bindAutocomplete('employee', '#search_employee', '#filter_employee_id', '#suggest_employee', 
                () => ({ 
                    company_id: $('#filter_company_id').val(), 
                    branch_id: $('#filter_branch_id').val(),
                    department_id: $('#filter_department_id').val(),
                    designation_id: $('#filter_designation_id').val()
                })
            );

            $('#applyFilterBtn').on('click', function(e) {
                e.preventDefault();
                let btn = $(this);
                let originalHtml = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
                
                loadData(1);

                setTimeout(() => {
                    btn.html(originalHtml).prop('disabled', false);
                }, 800);
            });
        }

        // ===============================
        // 1. DATA LOADING (PAGINATION)
        // ===============================
        function loadData(page) {
            currentPage = page;
            let search = $('#searchInput').val();
            let f_company = $('#filter_company_id').val() || '';
            let f_branch = $('#filter_branch_id').val() || '';
            let f_dept = $('#filter_department_id').val() || '';
            let f_desig = $('#filter_designation_id').val() || '';
            let f_emp = $('#filter_employee_id').val() || '';
            let f_month = $('#filter_month').val() || '';

            let queryParams = `?page=${page}&search=${search}&company_id=${f_company}&branch_id=${f_branch}&department_id=${f_dept}&designation_id=${f_desig}&employee_id=${f_emp}&month=${f_month}`;

            $.get(`${apiUrl}${queryParams}`, function(res) {
                let records = res.data; 
                window.taDataList = records;

                if (f_month !== '') {
                    $('#sumApplied').text(`₹${parseFloat(res.summary.total_applied || 0).toFixed(2)}`);
                    $('#sumApproved').text(`₹${parseFloat(res.summary.total_approved || 0).toFixed(2)}`);
                    $('#summarySection').slideDown();
                } else {
                    $('#summarySection').slideUp();
                }

                totalPages = res.last_page || 1;
                $('#paginationInfo').text(`Showing Page ${currentPage} of ${totalPages}`);
                $('#btnPrevPage').prop('disabled', currentPage <= 1);
                $('#btnNextPage').prop('disabled', currentPage >= totalPages);

                if (records.length === 0) {
                    $('#taTableBody').html('<tr><td colspan="7" class="text-center text-muted py-4 fw-bold">No Records Found</td></tr>');
                    $('#taMobileContainer').html('<div class="text-center text-muted py-4 bg-white border rounded shadow-sm fw-bold">No Records Found</div>');
                    return;
                }

                let desktopHtml = '';
                let mobileHtml = '';

                records.forEach(item => {
                    let statusLabel = item.status === 'active' ? 'APPROVED' : (item.status === 'rejected' ? 'REJECTED' : 'PENDING');
                    let badgeClass = item.status === 'active' ? 'bg-success' : (item.status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark');
                    let employeeName = item.employee ? `${item.employee.full_name} (${item.employee.member_id})` : 'N/A';
                    let isOwnTA = (item.employee_id == window.userId);

                    let amountDisplay = `<span class="fw-bold">₹${item.amount}</span>`;
                    if (item.status === 'active' && item.approved_amount) {
                        if (parseFloat(item.amount) !== parseFloat(item.approved_amount)) {
                            amountDisplay = `<del class="text-muted small">₹${item.amount}</del> <br> <span class="fw-bold text-success">₹${item.approved_amount}</span>`;
                        } else {
                            amountDisplay = `<span class="fw-bold text-success">₹${item.approved_amount}</span>`;
                        }
                    }

                    let actions = `
                        <button class="btn btn-sm btn-light border text-dark" onclick="viewTA(${item.id})" title="View"><i class="fas fa-eye"></i></button>
                        <button class="btn btn-sm btn-info text-white secured-item" data-permission="ta_print" onclick="printTA(${item.id})" title="Print"><i class="fas fa-print"></i></button>
                    `;

                    if (item.status === 'pending') {
                        let hasEditPerm = (window.userPerms || []).includes('ta_edit');
                        if (isOwnTA || hasEditPerm || window.userGodMode) {
                            actions += `<button class="btn btn-sm btn-primary" onclick="editTA(${item.id})" title="Edit Details"><i class="fas fa-pencil-alt"></i></button>`;
                        }
                    }

                    if (!isOwnTA || window.userGodMode) {
                        // 🔥 FINAL FIX: No amount/remark passing in HTML to avoid syntax errors
                        actions += `<button class="btn btn-sm btn-success secured-item" data-permission="ta_appr" onclick="updateStatus(${item.id}, 'approve')" title="Approve / Re-Approve"><i class="fas fa-check-double"></i></button>`;
                        actions += `<button class="btn btn-sm btn-danger secured-item" data-permission="ta_rej" onclick="updateStatus(${item.id}, 'reject')" title="Reject / Re-Reject"><i class="fas fa-ban"></i></button>`;
                    }

                    let personInfoDisplay = `
                        <div class="text-truncate" style="max-width:180px;">
                            <i class="fas fa-user-friends text-primary"></i> <b>${item.person_name || 'Self'}</b> <span class="badge bg-secondary ms-1">x${item.number_of_persons || 1}</span>
                        </div>
                        ${item.person_number ? `<small class="text-muted"><i class="fas fa-phone-alt"></i> ${item.person_number}</small>` : ''}
                    `;

                    desktopHtml += `
                        <tr>
                            <td class="ps-3"><input type="checkbox" class="form-check-input row-checkbox" value="${item.id}"></td>
                            <td>${item.ta_date}</td>
                            <td class="fw-medium">${employeeName}</td>
                            <td>${personInfoDisplay}</td> <td><div class="text-truncate" style="max-width:200px;">${item.purpose}</div><small class="text-muted d-block text-truncate" style="max-width:200px;">${item.destination}</small></td>
                            <td class="text-nowrap">${amountDisplay}</td>
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
                                <h6 class="mb-0 text-primary">${amountDisplay}</h6>
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
        // 2. VIEW, ADD & EDIT
        // ===============================
        function viewTA(id) {
            $('#viewModalBody').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2 text-muted">Loading Preview...</p></div>');
            $('#viewModal').modal('show');
            $.get(`${apiUrl}/${id}`, function(res) {
                $('#viewModalBody').html(res.html);
            });
        }

        let selectedFiles = [];
        let existingFiles = [];

        $('#proof_file_input').on('change', function(e) {
            let files = e.target.files;
            for (let i = 0; i < files.length; i++) {
                selectedFiles.push(files[i]);
            }
            $(this).val(''); 
            renderPreviews();
        });

        function renderPreviews() {
            $('#proof_preview_container').empty();

            existingFiles.forEach((path, index) => {
                let isPdf = path.toLowerCase().endsWith('.pdf');
                let content = isPdf ? '<i class="fas fa-file-pdf fa-2x text-danger mt-3"></i>' : `<img src="/${path}" style="width:100%; height:100%; object-fit:cover;">`;

                $('#proof_preview_container').append(`
                    <div class="position-relative border rounded shadow-sm" style="width: 80px; height: 80px; overflow: hidden; background: #f8f9fa; text-align:center;">
                        ${content}
                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle" style="padding: 1px 5px; font-size: 10px; margin: 2px;" onclick="removeExisting(${index})"><i class="fas fa-times"></i></button>
                    </div>
                `);
            });

            selectedFiles.forEach((file, index) => {
                let isPdf = file.type === 'application/pdf';
                let url = URL.createObjectURL(file);
                let content = isPdf ? '<i class="fas fa-file-pdf fa-2x text-danger mt-3"></i>' : `<img src="${url}" style="width:100%; height:100%; object-fit:cover;">`;

                $('#proof_preview_container').append(`
                    <div class="position-relative border border-primary border-2 rounded shadow-sm" style="width: 80px; height: 80px; overflow: hidden; background: #e3f2fd; text-align:center;">
                        ${content}
                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle" style="padding: 1px 5px; font-size: 10px; margin: 2px;" onclick="removeNew(${index})"><i class="fas fa-times"></i></button>
                    </div>
                `);
            });
        }

        window.removeNew = function(index) {
            selectedFiles.splice(index, 1);
            renderPreviews();
        };
        window.removeExisting = function(index) {
            existingFiles.splice(index, 1);
            renderPreviews();
        };

        function openTAModal() {
            $('#purpose').trigger('input');
            $('#taForm')[0].reset();
            $('#ta_id').val('');
            $('#taModalTitle').text('Request Travelling & Conveyance Expenses');
            selectedFiles = [];
            existingFiles = [];
            renderPreviews();
            prepareFormView();
            $('#taModal').modal('show');
        }

        async function editTA(id) {
            let item = window.taDataList.find(t => t.id === id);
            if (!item) return;
            $('#purpose').trigger('input');

            $('#taForm')[0].reset();
            $('#ta_id').val(item.id);
            $('#taModalTitle').text('Edit Travelling & Conveyance Expenses');

            $('#ta_date').val(item.ta_date);
            $('#vehicle_no').val(item.vehicle_no);
            $('#purpose').val(item.purpose);
            $('#destination').val(item.destination);
            $('#distance_km').val(item.distance_km);
            $('#fuel_litre').val(item.fuel_litre);
            $('#in_time').val(item.in_time);
            $('#out_time').val(item.out_time);
            $('#amount').val(item.amount);
            $('#person_name').val(item.person_name);
            $('#person_number').val(item.person_number);
            $('#number_of_persons').val(item.number_of_persons || 1);

            selectedFiles = [];
            existingFiles = [];
            if (item.proof_file) {
                try {
                    let parsed = JSON.parse(item.proof_file);
                    existingFiles = Array.isArray(parsed) ? parsed : [item.proof_file];
                } catch (e) {
                    existingFiles = [item.proof_file];
                }
            }
            renderPreviews();
            prepareFormView(item);
            $('#taModal').modal('show');
        }

        function prepareFormView(item = null) {
            if (portal === 'employee' || (!window.userGodMode && !window.userPerms.includes('ta_appr'))) {
                $('#adminSelectionArea').hide();
                $('.admin-select').removeAttr('required').prop('disabled', true);
                
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
                $('.admin-select').attr('required', true).prop('disabled', false);
                $('#hidden_emp_inputs').remove();
                if (!item) loadCompanies();
            }
        }

        function saveTA() {
            if (selectedFiles.length === 0 && existingFiles.length === 0) {
                Swal.fire('Required', 'Please upload at least one proof file.', 'error');
                return;
            }

            let id = $('#ta_id').val();
            let formData = new FormData($('#taForm')[0]);

            selectedFiles.forEach(file => {
                formData.append('proof_files[]', file);
            });
            formData.append('existing_proofs', JSON.stringify(existingFiles));

            if (id) {
                formData.append('_method', 'PUT');
            }

            $.ajax({
                url: id ? `${apiUrl}/${id}` : apiUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    $('#taModal').modal('hide');
                    loadData(currentPage);
                    Swal.fire('Success', res.message, 'success');
                },
                error: function(err) {
                    Swal.fire('Error', 'Failed to save data.', 'error');
                }
            });
        }

        // ===============================
        // 3. WORKFLOW ACTIONS (APPROVE/REJECT)
        // ===============================
        function updateStatus(id, action) {
            let item = window.taDataList.find(t => t.id === id);
            if (!item) return;

            let requestedAmount = item.amount;
            let currentApprovedAmount = item.approved_amount;
            
            // 🔥 FIX: Guarding against HTML injection inside textarea via replace
            let safeRemark = item.remarks ? item.remarks.replace(/</g, "&lt;").replace(/>/g, "&gt;") : ''; 
            let safeApproved = currentApprovedAmount !== null && currentApprovedAmount !== undefined ? currentApprovedAmount : requestedAmount;
            
            // 🔥 FIX: Guarding against HTML breaking in Purpose box
            let purposeText = item.purpose ? item.purpose.replace(/</g, "&lt;").replace(/>/g, "&gt;") : 'No purpose provided.';

            if (action === 'approve') {
                Swal.fire({
                    title: 'Approve TA Request',
                    html: `
                        <div class="text-start">
                            <label class="form-label small fw-bold text-muted">Requested Amount: ₹${requestedAmount}</label>
                            <input id="swal-amount" class="form-control border-success mb-3" type="number" value="${safeApproved}">
                            
                            <label class="form-label small fw-bold text-muted">Purpose of Work</label>
                            <div class="p-2 mb-3 bg-light border rounded small text-dark" style="max-height: 120px; overflow-y: auto; white-space: pre-wrap; font-size: 13px;">${purposeText}</div>

                            <label class="form-label small fw-bold text-muted">Approver's Remark (Optional)</label>
                            <textarea id="swal-remark" class="form-control" rows="4" placeholder="Add approval remarks...">${safeRemark}</textarea>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Confirm Approve',
                    confirmButtonColor: '#28a745',
                    preConfirm: () => {
                        let amt = document.getElementById('swal-amount').value;
                        if (!amt || amt <= 0) {
                            Swal.showValidationMessage('Please enter a valid amount!');
                            return false;
                        }
                        return {
                            approved_amount: amt,
                            remarks: document.getElementById('swal-remark').value
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post(`${apiUrl}/${id}/approve`, result.value, function(res) {
                            loadData(currentPage);
                            Swal.fire('Approved!', res.message, 'success');
                        });
                    }
                });
            } else {
                Swal.fire({
                    title: `Reject TA Request`,
                    html: `
                        <div class="text-start">
                            <label class="form-label small fw-bold text-muted">Purpose of Work</label>
                            <div class="p-2 mb-3 bg-light border rounded small text-dark" style="max-height: 120px; overflow-y: auto; white-space: pre-wrap; font-size: 13px;">${purposeText}</div>

                            <label class="form-label small fw-bold text-muted">Reason for Rejection (Optional)</label>
                            <textarea id="swal-remark" class="form-control border-danger" rows="4" placeholder="Why are you rejecting this?">${safeRemark}</textarea>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Confirm Reject',
                    preConfirm: () => {
                        return { remarks: document.getElementById('swal-remark').value }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post(`${apiUrl}/${id}/reject`, result.value, function(res) {
                            loadData(currentPage);
                            Swal.fire('Rejected!', res.message, 'success');
                        });
                    }
                });
            }
        }

        function openRemarks(id, currentRemark, isOwnTA) {
            $('#remark_ta_id').val(id);
            $('#ta_remark_text').val(currentRemark !== 'null' && currentRemark ? currentRemark : '');
            let canModifyRemarks = window.userPerms.includes('ta_appr') || window.userPerms.includes('ta_rej') || window.userPerms.includes('ta_remark');

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
                let opts = `<option value="">Select Branch</option><option value="HO">${companyName} (Head Office)</option>`;
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
                loadEmployeesAsync($(this).val(), $('#branch_id').val(), $('#company_id').val(), $('#department_id').val());
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
