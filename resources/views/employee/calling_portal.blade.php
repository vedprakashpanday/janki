@extends('layout.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 32px !important;
            font-size: 12px !important;
            border: 1px solid #ced4da !important;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            background-color: #1A365D !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 50rem !important;
            padding: 1px 8px !important;
            font-size: 10px !important;
        }

        .filter-actions a {
            cursor: pointer;
            text-decoration: none;
            font-size: 10px;
            font-weight: 700;
            transition: 0.2s;
            user-select: none;
        }

        .filter-actions a:hover {
            opacity: 0.7;
        }

        .filter-bar {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }

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

        .status-badge {
            font-size: 11px;
            padding: 5px 8px;
        }

        #phaseModalImg {
            cursor: zoom-in;
            transition: transform 0.2s;
        }

        #phaseModalImg:hover {
            transform: scale(1.02);
            opacity: 0.9;
        }
    </style>

    <div class="container-fluid mt-3">
        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-3">
            <div>
                <h4 class="mb-0 fw-bold text-primary"><i class="fas fa-headset me-2"></i>My Calling Portal</h4>
                <p class="text-muted small mb-0">Call customers, update feedback, and track goals.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-info shadow-sm text-white secured-item" data-permission="tele_export"
                    id="printReportBtn">
                    <i class="fas fa-print"></i> <span class="d-none d-md-inline ms-1">Print Report</span>
                </button>
                <button class="btn btn-sm btn-success shadow-sm secured-item" data-permission="tele_export"
                    id="exportExcelBtn">
                    <i class="fas fa-file-excel"></i> <span id="exportBtnText">Export Advanced Excel</span>
                </button>
            </div>
        </div>

        <div class="filter-bar shadow-sm">
            <div class="row g-2 mb-2 pb-2 border-bottom">
                <div class="col-md-2">
                    <div class="d-flex justify-content-between"><label class="small fw-bold text-dark mb-0">Company</label>
                        <div class="filter-actions"><a class="text-primary btn-all" data-target="companySelect">All</a>|<a
                                class="text-danger btn-clear" data-target="companySelect">Clr</a></div>
                    </div>
                    <select class="select2-multiple" id="companySelect" multiple
                        data-placeholder="Select Company..."></select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex justify-content-between"><label
                            class="small fw-bold text-dark mb-0">Branch/HO</label>
                        <div class="filter-actions"><a class="text-primary btn-all" data-target="branchSelect">All</a>|<a
                                class="text-danger btn-clear" data-target="branchSelect">Clr</a></div>
                    </div>
                    <select class="select2-multiple" id="branchSelect" multiple
                        data-placeholder="Select Branch..."></select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex justify-content-between"><label
                            class="small fw-bold text-dark mb-0">Department</label>
                        <div class="filter-actions"><a class="text-primary btn-all" data-target="deptSelect">All</a>|<a
                                class="text-danger btn-clear" data-target="deptSelect">Clr</a></div>
                    </div>
                    <select class="select2-multiple" id="deptSelect" multiple data-placeholder="Select Dept..."></select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex justify-content-between"><label
                            class="small fw-bold text-dark mb-0">Designation</label>
                        <div class="filter-actions"><a class="text-primary btn-all" data-target="desigSelect">All</a>|<a
                                class="text-danger btn-clear" data-target="desigSelect">Clr</a></div>
                    </div>
                    <select class="select2-multiple" id="desigSelect" multiple data-placeholder="Select Desig..."></select>
                </div>
                <div class="col-md-4">
                    <div class="d-flex justify-content-between"><label class="small fw-bold text-danger mb-0">Telecaller /
                            Assignee</label>
                        <div class="filter-actions"><a class="text-primary btn-all" data-target="assigneeSelect">All</a>|<a
                                class="text-danger btn-clear" data-target="assigneeSelect">Clr</a></div>
                    </div>
                    <select class="select2-multiple" id="assigneeSelect" multiple
                        data-placeholder="Filter Telecaller..."></select>
                </div>
            </div>

            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1">Search Customer Name/Mobile</label>
                    <input type="text" id="liveSearch" class="form-control form-control-sm shadow-none"
                        placeholder="Type here...">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1">Month</label>
                    <input type="month" id="monthFilter" class="form-control form-control-sm shadow-none">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1">Date</label>
                    <input type="date" id="dateFilter" class="form-control form-control-sm shadow-none">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1">Call Status</label>
                    <select id="statusFilter" class="form-select form-select-sm shadow-none">
                        <option value="">21. All Status (History)</option>
                        <option value="pending" selected>22. Pending status</option>
                        <option value="Connected">1. Connected Call</option>
                        <option value="Interested">2. Interested Call</option>
                        <option value="Not Interested">3. Not Interested Call</option>
                        <option value="Not Answering Call">4. Not Answering Call</option>
                        <option value="Not Reachable">5. Not Reachable call</option>
                        <option value="Number Doesn't Exists call">6. Number Doesn't Exists call</option>
                        <option value="Site visit Scheduled">7. Site visit Scheduled Call</option>
                        <option value="Site Visit Done">8. Site Visit Done Call</option>
                        <option value="Booking Done">9. Booking Done</option>
                        <option value="Lost Lead">10. Lost Lead</option>
                        <option value="Booking Confirm">11. Booking Confirm</option>
                        <option value="Follow Up">12. FollowUp Required</option>
                        <option value="Registry Completed">13. Registry Completed</option>
                        <option value="On Hold">14. On Hold</option>
                        <option value="Highly Interested">15. Highly Interested</option>
                        <option value="Call Back Requested">16. Call Back Requested</option>
                        <option value="Busy">17. Busy</option>
                        <option value="Switched Off">18. Switched Off</option>
                        <option value="DND/Call Rejected">19. DND/Call Rejected</option>
                        <option value="Price Discussion">20. Price Discussion</option>
                        <option value="Incoming Call Not Available">23. Incoming Call Not Available</option>
                    </select>
                </div>
                <div class="col-md-3 text-end d-flex justify-content-end gap-3">
                    <button class="btn btn-sm btn-warning shadow-sm fw-bold text-dark" id="todayScheduledBtn">
                        <i class="fas fa-calendar-day"></i> Today's Scheduled
                    </button>
                    <button class="btn btn-sm btn-primary shadow-sm fw-bold" id="applyFilterBtn">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </div>
        </div>

        <div id="summaryCard" class="card shadow-sm border-0 mb-3 d-none bg-light"
            style="border-left: 4px solid var(--bs-primary) !important;">
            <div class="card-body p-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div>
                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-chart-pie me-2"></i>Filter Summary</h6>
                        <small class="text-muted" id="summaryFilterText">Showing results for applied filters.</small>
                    </div>
                    <div>
                        <span class="badge bg-primary fs-6 py-1 px-3 shadow-sm rounded-pill">
                            Total Leads: <span id="totalLeadsCount">0</span>
                        </span>
                    </div>
                </div>
                <div id="employeeSplitContainer" class="d-flex flex-wrap gap-2 mt-3 border-top pt-3">
                </div>
            </div>
        </div>

        <div id="bulkActionBar" class="bulk-action-bar mobile-floating d-none">
            <div class="fw-bold text-primary"><span id="selectedCount">0</span> Selected</div>
            <div class="ms-auto">
                <button class="btn btn-sm btn-outline-secondary me-2" id="selectAllBtn">Select All</button>
                <button class="btn btn-sm btn-danger shadow-sm secured-item" data-permission="tele_delete"
                    id="deleteSelectedBtn">
                    <i class="fas fa-trash"></i> <span class="d-none d-sm-inline">Delete</span>
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-3 d-none d-md-block mb-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="callTable">
                        <thead class="bg-light text-secondary" style="font-size: 13px; text-transform: uppercase;">
                            <tr>
                                <th class="ps-4" style="width: 50px;"><input type="checkbox"
                                        class="form-check-input shadow-none" id="masterCheckbox"></th>
                                <th>Customer Name</th>
                                <th>Mobile / Alt</th>
                                <th>Phase & Task</th>
                                <th>Status & Follow-up</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody id="callTableBody" style="font-size: 14px;"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="mobileCardsContainer" class="d-block d-md-none mb-3"></div>

        <div class="text-center mb-5 d-none" id="loadMoreContainer">
            <button class="btn btn-outline-primary fw-bold shadow-sm px-4 py-2" id="loadMoreBtn">
                <i class="fas fa-arrow-down me-2"></i> Load More Records
            </button>
        </div>
    </div>

    <div class="modal fade" id="phaseInfoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-info text-white border-0">
                    <h5 class="modal-title fw-bold" id="phaseModalTitle"><i class="fas fa-building me-2"></i> Phase
                        Details</h5>
                    <button type="button" class="btn-close btn-close-white shadow-none"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="bg-light p-2 mb-3 rounded shadow-sm d-inline-block position-relative" id="phaseImgWrapper"
                        style="display: none !important;">
                        <span class="badge bg-dark position-absolute top-0 end-0 m-2 rounded-pill"><i
                                class="fas fa-search-plus"></i> Zoom</span>
                        <img id="phaseModalImg" src="" class="img-fluid rounded"
                            style="max-height: 250px; object-fit: contain;" title="Click to zoom">
                    </div>
                    <div class="bg-light p-3 rounded border text-start">
                        <h6 class="fw-bold text-dark mb-2">Description / Details:</h6>
                        <p id="phaseModalDesc" class="small text-muted mb-0" style="white-space: pre-wrap;"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="imageZoomModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content bg-transparent border-0 shadow-none">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close btn-close-white ms-auto shadow-none bg-dark p-2"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="zoomedImage" src="" class="img-fluid rounded shadow-lg"
                        style="max-height: 90vh; border: 3px solid #fff;">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="feedbackModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-phone-volume me-2"></i> Update Feedback</h5>
                    <button type="button" class="btn-close btn-close-white shadow-none"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border shadow-sm mb-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold text-dark mb-1" id="modalCustomerName">Customer Name</h6>
                            <span class="text-primary fw-bold" id="modalMobile"><i
                                    class="fas fa-phone-alt me-1"></i></span>
                        </div>
                    </div>

                    <form id="feedbackForm">
                        <input type="hidden" id="allocationId">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark">Call Status *</label>
                                <select name="call_status" id="callStatus" class="form-select shadow-none border-primary"
                                    required>
                                    <option value="Pending">22. Pending status</option>
                                    <option value="Connected ">1. Connected Call</option>
                                    <option value="Interested">2. Interested Call</option>
                                    <option value="Not Interested">3. Not Interested Call</option>
                                    <option value="Not Answering Call">4. Not Answering Call</option>
                                    <option value="Not Reachable">5. Not Reachable call</option>
                                    <option value="Number Doesn't Exists call">6. Number Doesn't Exists call</option>
                                    <option value="Site visit Scheduled">7. Site visit Scheduled Call</option>
                                    <option value="Site Visit Done Call">8. Site Visit Done Call</option>
                                    <option value="Booking Done">9. Booking Done</option>
                                    <option value="Lost Lead">10. Lost Lead</option>
                                    <option value="Booking Confirm">11. Booking Confirm</option>
                                    <option value="Follow Up">12. FollowUp Required</option>
                                    <option value="Registry Completed">13. Registry Completed</option>
                                    <option value="On Hold">14. On Hold</option>
                                    <option value="Highly Interested">15. Highly Interested</option>
                                    <option value="Call Back Requested">16. Call Back Requested</option>
                                    <option value="Busy">17. Busy</option>
                                    <option value="Switched Off">18. Switched Off</option>
                                    <option value="DND/Call Rejected">19. DND/Call Rejected</option>
                                    <option value="Price Discussion">20. Price Discussion</option>
                                    <option value="Incoming Call Not Available">23. Incoming Call Not Available</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">Interested For</label>
                                <select name="interested_for" id="interestedFor" class="form-select shadow-none">
                                    <option value="">-- Select --</option>
                                    <option value="Plot">Plot</option>
                                    <option value="Villa">Villa</option>
                                    <option value="Plot & Villa Both">Plot & Villa Both</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">Estimated Budget</label>
                                <input type="text" name="budget" id="budget" class="form-control shadow-none"
                                    placeholder="e.g. 25 Lakhs">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Alternate Mobile No.</label>
                                <input type="text" name="alternate_no" id="alternateNo"
                                    class="form-control shadow-none" placeholder="Optional">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Email ID</label>
                                <input type="email" name="email" id="emailId" class="form-control shadow-none"
                                    placeholder="customer@domain.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">Customer Address</label>
                                <input type="text" name="address" id="address" class="form-control shadow-none"
                                    placeholder="Full address...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">Date of Birth</label>
                                <input type="date" name="dob" id="dob" class="form-control shadow-none">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">Anniversary Date</label>
                                <input type="date" name="anniversary_date" id="anniversaryDate"
                                    class="form-control shadow-none">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">Follow-up Date</label>
                                <input type="date" name="followup_date" id="followupDate"
                                    class="form-control shadow-none">
                            </div>

                            <div class="col-12 border-top pt-3 mt-3">
                                <label class="form-label small fw-bold text-muted">Call Remarks / Note</label>
                                <textarea name="remark" id="remark" class="form-control shadow-none" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary shadow-none" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="feedbackForm" class="btn btn-primary shadow-sm px-4 secured-item"
                        data-permission="tele_edit" id="saveFeedbackBtn">
                        <i class="fas fa-save me-1"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="summaryDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-chart-pie me-2"></i> Performance & Hot Leads: <span
                            id="summaryEmpName" class="text-warning"></span></h5>
                    <button class="btn btn-sm btn-light ms-auto me-3 fw-bold no-print" id="printSummaryBtn">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button type="button" class="btn-close btn-close-white shadow-none"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-list-ul me-1"></i> Call Status Breakdown</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm text-center align-middle mb-0" id="modalSummaryTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-start">Call Status (Assigned As)</th>
                                    <th class="text-primary">Assigned Total</th>
                                    <th class="text-success">Called</th>
                                    <th class="text-danger">Left (Pending)</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <h6 class="fw-bold mt-4 text-success mb-3"><i class="fas fa-star me-1"></i> Interested / Hot Leads
                        List</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm align-middle mb-0" id="modalInterestedTable">
                            <thead class="table-success">
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Mobile Number</th>
                                    <th>Refer By</th>
                                    <th>Current Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
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
            let currentOffset = 0;
            let limit = 20;
            let userContext = {};

            // 🔥 NAYA CODE: Check if URL has ?filter=today (from Notification)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('filter') === 'today') {
                setTimeout(function() {
                    $('#todayScheduledBtn').trigger('click'); // Button ko apne aap daba dega
                }, 500);
            }

            // --- SELECT2 INITIALIZATION & ACTIONS ---
            function initSelect2(element) {
                $(element).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
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

            function getSelected(id) {
                let v = $('#' + id).val();
                return (v && v.length) ? v.join(',') : '';
            }

            // --- RBAC & DEPENDENT DROPDOWNS ---
            $.get(apiPrefix + '/context', function(res) {
                userContext = res;
                loadCompanies();

                if (userContext.is_employee && !userContext.is_god && !userContext.is_director) {
                    $('#companySelect, #branchSelect, #deptSelect, #desigSelect, #assigneeSelect').prop(
                        'disabled', true);
                }
            });

            function fetchAssigneeInitial() {
                $.get(apiPrefix + '/employees?length=-1', function(res) {
                    let select = $('#assigneeFilter');
                    if (res.data && res.data.length > 0) {
                        if (res.data.length === 1) {
                            let emp = res.data[0];
                            select.html(
                                `<option value="${emp.id}">${emp.full_name} (${emp.member_id})</option>`
                                );
                            select.prop('disabled', true);
                        } else {
                            let html = '<option value="">All Telecallers</option>';
                            res.data.forEach(emp => {
                                html +=
                                    `<option value="${emp.id}">${emp.full_name} (${emp.member_id})</option>`;
                            });
                            select.html(html);
                        }
                    } else {
                        select.html('<option value="">No Users Found</option>');
                    }
                    loadAllocations(false);
                });
            }

            fetchAssigneeInitial();

            function loadCompanies() {
                $.get(apiPrefix + '/companies', function(res) {
                    let html = '';
                    res.data.forEach(c => {
                        html +=
                            `<option value="${c.id}" data-name="${c.company_name}">${c.company_name}</option>`;
                    });
                    $('#companySelect').html(html);

                    if (userContext.is_director) {
                        $('#companySelect').val([userContext.company_id]).trigger('change').prop('disabled',
                            true);
                    } else if (userContext.is_employee && !userContext.is_god) {
                        $('#companySelect').val([userContext.company_id]).trigger('change');
                    }
                });
            }

            $('#companySelect').on('change', function() {
                let compIds = getSelected('companySelect');
                if (!compIds) {
                    $('#branchSelect, #deptSelect, #desigSelect, #assigneeSelect').html('').trigger(
                        'change');
                    return;
                }

                let html = '';
                $('#companySelect option:selected').each(function() {
                    html +=
                        `<option value="HO_${$(this).val()}">Head Office (${$(this).data('name')})</option>`;
                });

                $.get(apiPrefix + `/branches?company_ids=${compIds}`, function(res) {
                    res.data.forEach(b => {
                        html += `<option value="${b.id}">${b.branch_name}</option>`;
                    });
                    $('#branchSelect').html(html);

                    if (userContext.is_employee && !userContext.is_god && !userContext
                        .is_director) {
                        let bVal = userContext.branch_id ? userContext.branch_id : ('HO_' +
                            userContext.company_id);
                        $('#branchSelect').val([bVal]).trigger('change');
                    }
                    loadAssignees();
                });
            });

            $('#branchSelect').on('change', function() {
                let branchIds = getSelected('branchSelect');
                if (!branchIds) {
                    $('#deptSelect').html('').trigger('change');
                    loadAssignees();
                    return;
                }

                $.get(apiPrefix + `/departments?branch_ids=${branchIds}`, function(res) {
                    let html = '';
                    res.data.forEach(d => {
                        html += `<option value="${d.id}">${d.department_name}</option>`;
                    });
                    $('#deptSelect').html(html);

                    if (userContext.is_employee && !userContext.is_god && !userContext
                        .is_director) {
                        if (userContext.department_id) {
                            $('#deptSelect').val([userContext.department_id]).trigger('change');
                        } else {
                            $('#deptSelect').trigger('change');
                        }
                    }
                    loadAssignees();
                });
            });

            $('#deptSelect').on('change', function() {
                let deptIds = getSelected('deptSelect');
                if (!deptIds) {
                    $('#desigSelect').html('').trigger('change');
                    loadAssignees();
                    return;
                }

                $.get(apiPrefix + `/designations?department_ids=${deptIds}`, function(res) {
                    let html = '';
                    res.data.forEach(d => {
                        html += `<option value="${d.id}">${d.designation_name}</option>`;
                    });
                    $('#desigSelect').html(html);

                    if (userContext.is_employee && !userContext.is_god && !userContext
                        .is_director) {
                        if (userContext.designation_id) {
                            $('#desigSelect').val([userContext.designation_id]).trigger('change');
                        } else {
                            $('#desigSelect').trigger('change');
                        }
                    }
                    loadAssignees();
                });
            });

            $('#desigSelect').on('change', function() {
                loadAssignees();
            });

            function loadAssignees() {
                let params = {
                    length: -1,
                    company_ids: getSelected('companySelect'),
                    branch_ids: getSelected('branchSelect'),
                    department_ids: getSelected('deptSelect'),
                    designation_ids: getSelected('desigSelect'),
                    status: 'active'
                };
                $.get(apiPrefix + '/employees', params, function(res) {
                    let select = $('#assigneeSelect');
                    let html = '';
                    res.data.forEach(emp => {
                        html +=
                            `<option value="${emp.id}">${emp.full_name} (${emp.member_id})</option>`;
                    });
                    select.html(html);

                    if (userContext.is_employee && !userContext.is_god && !userContext.is_director) {
                        if (res.data.length === 1) select.val([res.data[0].id]).trigger('change.select2');
                    }

                    if (currentOffset === 0 && $('#callTableBody').is(':empty')) loadAllocations(false);
                });
            }

            // --- MAIN DATA LOADING ---
            // --- MAIN DATA LOADING ---
            function loadAllocations(append = false) {
                if (!append) {
                    currentOffset = 0;
                    $('#callTableBody').html(
                        '<tr><td colspan="6" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div> Fetching data...</td></tr>'
                    );
                    $('#mobileCardsContainer').html(
                        '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
                }

                let payload = {
                    offset: currentOffset,
                    company_ids: getSelected('companySelect'),
                    branch_ids: getSelected('branchSelect'),
                    department_ids: getSelected('deptSelect'),
                    designation_ids: getSelected('desigSelect'),
                    assignee_ids: $('#assigneeSelect').val() ? $('#assigneeSelect').val().join(',') : $(
                        '#assigneeFilter').val(),
                    call_status: $('#statusFilter').val(),
                    month: $('#monthFilter').val(),
                    date: $('#dateFilter').val(),
                    search: $('#liveSearch').val(),
                    // 🔥 YAHAN NAYA PARAMETER ADD KIYA HAI 🔥
                    scheduled_today: $('#todayScheduledBtn').hasClass('active-scheduled') ? 1 : 0
                };

                $.ajax({
                    url: apiPrefix + '/telecalling/allocations',
                    type: 'GET',
                    data: payload,
                    success: function(res) {
                        if (!append) {
                            $('#callTableBody, #mobileCardsContainer').empty();
                        }

                        if (res.success && res.data.length > 0) {
                            renderData(res.data);
                            currentOffset += res.data.length;
                            $('#loadMoreContainer').toggleClass('d-none', !res.has_more);

                            // ==========================================
                            // 🔥 Summary UI Update Logic
                            // ==========================================
                            if (!append) {
                                $('#totalLeadsCount').text(res.total_count || res.data.length);

                                if (res.employee_summary && res.employee_summary.length > 0) {
                                    let empHtml = '';
                                    res.employee_summary.forEach(emp => {
                                        empHtml += `
                                        <div class="bg-white border rounded shadow-sm px-3 py-2 d-flex align-items-center emp-summary-badge" data-empid="${emp.id}" data-emptype="${emp.type}" data-empname="${emp.name}" style="cursor: pointer; transition: 0.2s;">
                                            <i class="fas fa-user-circle text-info fs-5 me-2"></i>
                                            <div class="lh-1">
                                                <small class="text-muted d-block fw-bold" style="font-size: 10px;">TELECALLER</small>
                                                <span class="fw-bold text-dark fs-6">${emp.name}</span>
                                            </div>
                                            <div class="ms-3 ps-3 border-start">
                                                <span class="badge bg-danger fs-6">${emp.count}</span>
                                            </div>
                                        </div>`;
                                    });
                                    $('#employeeSplitContainer').html(empHtml);
                                } else {
                                    $('#employeeSplitContainer').html(
                                        '<span class="text-muted small">No specific telecaller split available.</span>'
                                        );
                                }
                                $('#summaryCard').removeClass('d-none');
                            }

                        } else if (!append) {
                            $('#callTableBody').html(
                                '<tr><td colspan="6" class="text-center py-4 text-muted">No calls found in this filter.</td></tr>'
                            );
                            $('#mobileCardsContainer').html(
                                '<div class="text-center py-4 text-muted bg-white rounded shadow-sm">No calls found.</div>'
                            );
                            $('#loadMoreContainer').addClass('d-none');
                            $('#summaryCard').addClass('d-none');
                        }
                        updateBulkActionBar();
                        if (typeof window.applyPermissions === 'function') window.applyPermissions();
                    }
                });
            }

            function renderData(dataList) {
                dataList.forEach(item => {
                    let cust = item.customer || {};
                    let cName = cust.cust_name || 'Unknown';
                    let cPhone = cust.mobile || 'N/A';
                    let cAltPhone = cust.alternate_no ?
                        `<br><small class="text-muted"><i class="fas fa-phone-alt" style="font-size:10px;"></i> ${cust.alternate_no}</small>` :
                        '';

                    let phaseName = item.phase ? item.phase.phase_name : 'General Task';
                    let phaseDesc = item.phase ? (item.phase.phase_details || 'No description available') :
                        'N/A';
                    let phaseImg = item.phase ? (item.phase.phase_image || '') : '';

                    let phaseBtn = item.phase ?
                        `<br><button class="btn btn-sm btn-outline-info mt-1 py-0 px-2 fw-bold show-phase-btn" data-name="${phaseName}" data-desc="${phaseDesc}" data-img="${phaseImg}"><i class="fas fa-info-circle"></i> View Details</button>` :
                        '';

                    let systemRemark = item.remark ?
                        `<br><small class="text-danger fw-bold" style="font-size: 11px; background: #fff5f5; padding: 2px 4px; border-radius: 4px; border: 1px solid #ffcccc;"><i class="fas fa-info-circle"></i> ${item.remark}</small>` :
                        '';

                    let bgClass = item.call_status === 'Pending' ? 'warning' : (item.call_status ===
                        'Connected' ? 'success' : (item.call_status === 'Lost' ? 'danger' : 'info'));
                    let statusBadge =
                        `<span class="badge bg-${bgClass} status-badge shadow-sm">${item.call_status}</span>`;
                    let followUp = item.followup_date ?
                        `<br><small class="text-danger fw-bold"><i class="far fa-clock"></i> Follow: ${item.followup_date}</small>` :
                        '';

                    let safeName = cName.replace(/"/g, '&quot;');
                    let safeAlt = cust.alternate_no || '';
                    let safeEmail = cust.email || '';
                    let safeAddress = cust.address ? cust.address.replace(/"/g, '&quot;') : '';
                    let safeDob = cust.dob || item.dob || '';
                    let safeAnniv = cust.anniversary_date || item.anniversary_date || '';

                    // Desktop Row
                    $('#callTableBody').append(`
                    <tr class="data-row">
                        <td class="ps-4"><input type="checkbox" class="form-check-input row-checkbox shadow-none" value="${item.id}"></td>
                        <td class="fw-bold text-dark">${cName} ${systemRemark}</td>
                        <td>
                            <a href="tel:${cPhone}" class="text-decoration-none fw-bold text-primary"><i class="fas fa-phone-square-alt me-1"></i>${cPhone}</a>
                            ${cAltPhone}
                        </td>
                        <td class="small text-muted"><strong>${phaseName}</strong> ${phaseBtn}<br>${item.task ? item.task.title : ''}</td>
                        <td>${statusBadge} ${followUp}</td>
                        <td class="text-end pe-4 action-btn-group">
                            <button class="btn btn-outline-primary shadow-sm update-btn secured-item" data-permission="tele_edit" 
                                data-id="${item.id}" data-name="${safeName}" data-phone="${cPhone}" 
                                data-status="${item.call_status}" data-int="${item.interested_for || ''}" 
                                data-bud="${item.budget || ''}" data-fdate="${item.followup_date || ''}" data-rem="${item.remark || ''}"
                                data-alt="${safeAlt}" data-email="${safeEmail}" data-addr="${safeAddress}" data-dob="${safeDob}" data-anniv="${safeAnniv}">
                                <i class="fas fa-pencil-alt"></i> Update
                            </button>
                        </td>
                    </tr>
                `);

                    // Mobile Card
                    $('#mobileCardsContainer').append(`
                    <div class="card shadow-sm mb-3 data-row border-0" style="border-left: 4px solid var(--bs-${bgClass});">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0 fw-bold text-dark"><input type="checkbox" class="form-check-input row-checkbox shadow-none me-2" value="${item.id}">${cName}</h6>
                                ${statusBadge}
                            </div>
                            ${item.remark ? `<div class="mb-2">${systemRemark}</div>` : ''}
                            <div class="mb-2">
                                <a href="tel:${cPhone}" class="text-decoration-none fw-bold text-primary"><i class="fas fa-phone-alt me-1"></i>${cPhone}</a>
                                ${cAltPhone}
                            </div>
                            <div class="small text-muted mb-2"><i class="fas fa-building me-1"></i> ${phaseName} ${phaseBtn}</div>
                            ${followUp ? `<div class="small mb-2">${followUp}</div>` : ''}
                            <div class="text-end border-top pt-2">
                                <button class="btn btn-outline-primary shadow-sm update-btn secured-item" data-permission="tele_edit" 
                                    data-id="${item.id}" data-name="${safeName}" data-phone="${cPhone}" 
                                    data-status="${item.call_status}" data-int="${item.interested_for || ''}" 
                                    data-bud="${item.budget || ''}" data-fdate="${item.followup_date || ''}" data-rem="${item.remark || ''}"
                                    data-alt="${safeAlt}" data-email="${safeEmail}" data-addr="${safeAddress}" data-dob="${safeDob}" data-anniv="${safeAnniv}">
                                    <i class="fas fa-pencil-alt"></i> Feedback
                                </button>
                            </div>
                        </div>
                    </div>
                `);
                });
            }

            $('#applyFilterBtn').on('click', function() {
                loadAllocations(false);
            });

        // 🔥 NAYA CODE: Today's Scheduled Quick Filter (Smart Toggle) 🔥
            $('#todayScheduledBtn').on('click', function() {
                $(this).toggleClass('active-scheduled');
                
                if ($(this).hasClass('active-scheduled')) {
                    // Button ko red karke active dikhao
                    $(this).removeClass('btn-warning').addClass('btn-danger').html('<i class="fas fa-calendar-check"></i> Showing Scheduled');
                    
                    // Baaki normal filters clear kar do taaki dates clash na karein
                    $('#dateFilter').val('');
                    $('#statusFilter').val('');
                    $('#monthFilter').val('');
                    $('#liveSearch').val('');
                } else {
                    // Wapas normal mode
                    $(this).removeClass('btn-danger').addClass('btn-warning').html('<i class="fas fa-calendar-day"></i> Today\'s Scheduled');
                }
                
                loadAllocations(false);
            });

            $('#applyFilterBtn').on('click', function() {
                // Agar normal filter apply kiya toh Scheduled wala toggle band kar do
                $('#todayScheduledBtn').removeClass('active-scheduled btn-danger').addClass('btn-warning').html('<i class="fas fa-calendar-day"></i> Today\'s Scheduled');
                loadAllocations(false);
            });

            $('#loadMoreBtn').on('click', function() {
                loadAllocations(true);
            });

            $('#loadMoreBtn').on('click', function() {
                loadAllocations(true);
            });

            // Phase Modals & Zoom
            $(document).on('click', '.show-phase-btn', function() {
                $('#phaseModalTitle').text($(this).data('name'));
                $('#phaseModalDesc').text($(this).data('desc'));
                let img = $(this).data('img');
                if (img && img !== 'null' && img !== '') {
                    $('#phaseModalImg').attr('src', '/' + img);
                    $('#phaseImgWrapper').attr('style', 'display: inline-block !important;');
                } else {
                    $('#phaseImgWrapper').attr('style', 'display: none !important;');
                }
                $('#phaseInfoModal').modal('show');
            });

            $('#phaseModalImg').on('click', function() {
                $('#zoomedImage').attr('src', $(this).attr('src'));
                $('#phaseInfoModal').modal('hide');
                $('#imageZoomModal').modal('show');
            });

            // Feedback Modal Open
            $(document).on('click', '.update-btn', function() {
                let b = $(this);
                $('#allocationId').val(b.data('id'));
                $('#modalCustomerName').text(b.data('name'));
                $('#modalMobile').html(`<i class="fas fa-phone-alt me-1"></i> ${b.data('phone')}`);

                // Existing Fields
                $('#callStatus').val(b.data('status'));
                $('#interestedFor').val(b.data('int'));
                $('#budget').val(b.data('bud'));
                $('#followupDate').val(b.data('fdate'));
                $('#remark').val(b.data('rem'));

                // New Customer Data Fields
                $('#alternateNo').val(b.data('alt'));
                $('#emailId').val(b.data('email'));
                $('#address').val(b.data('addr'));
                $('#dob').val(b.data('dob'));
                $('#anniversaryDate').val(b.data('anniv'));

                $('#feedbackModal').modal('show');
            });

            // Save Feedback
            $('#feedbackForm').on('submit', function(e) {
                e.preventDefault();
                let btn = $('#saveFeedbackBtn');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
                $.ajax({
                    url: apiPrefix + '/telecalling/allocations/' + $('#allocationId').val() +
                        '/feedback',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $('#feedbackModal').modal('hide');
                        loadAllocations(false);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fas fa-save me-1"></i> Save');
                    }
                });
            });

            // DETAILED EXCEL EXPORT
            $('#exportExcelBtn').on('click', function() {
                let btn = $('#exportBtnText');
                btn.text('Exporting...');
                $('#exportExcelBtn').prop('disabled', true);

               let payload = {
                    offset: currentOffset,
                    company_ids: getSelected('companySelect'),
                    branch_ids: getSelected('branchSelect'),
                    department_ids: getSelected('deptSelect'),
                    designation_ids: getSelected('desigSelect'),
                    assignee_ids: $('#assigneeSelect').val() ? $('#assigneeSelect').val().join(',') : $('#assigneeFilter').val(),
                    call_status: $('#statusFilter').val(),
                    month: $('#monthFilter').val(),
                    date: $('#dateFilter').val(),
                    search: $('#liveSearch').val(),
                    // 🔥 NAYA PARAMETER
                    scheduled_today: $('#todayScheduledBtn').hasClass('active-scheduled') ? 1 : 0
                };
                
                $.ajax({
                    url: apiPrefix + '/telecalling/allocations',
                    type: 'GET',
                    data: payload,
                    success: function(res) {
                        if (res.data && res.data.length > 0) {
                            let csv =
                                "Company,Branch,Department,Designation,Telecaller Name,Telecaller ID,Customer Name,Mobile,Alternate Mobile,Email,Address,DOB,Anniversary,Phase,Task,Status,Interested For,Budget,Follow-up,Remarks,Called At\n";

                            res.data.forEach(item => {
                                let emp = item.assignee || {};
                                let cName = (emp.company ? emp.company.company_name :
                                    'N/A').replace(/,/g, " ");
                                let bName = (emp.branch ? emp.branch.branch_name :
                                    'HO/Global').replace(/,/g, " ");
                                let dName = (emp.department ? emp.department
                                    .department_name : 'N/A').replace(/,/g, " ");
                                let dgName = (emp.designation ? emp.designation
                                    .designation_name : 'N/A').replace(/,/g, " ");
                                let tName = (emp.full_name || emp.member_name ||
                                    'Unknown').replace(/,/g, " ");
                                let tId = (emp.member_id || '').replace(/,/g, " ");

                                let cust = item.customer || {};
                                let custName = (cust.cust_name || '').replace(/,/g,
                                " ");
                                let custMob = (cust.mobile || '');
                                let altMob = (cust.alternate_no || '');
                                let emailId = (cust.email || '').replace(/,/g, " ");
                                let address = (cust.address || '').replace(/\n/g, " ")
                                    .replace(/,/g, " ");
                                let dob = (cust.dob || item.dob || '');
                                let anniv = (cust.anniversary_date || item
                                    .anniversary_date || '');

                                let phase = (item.phase ? item.phase.phase_name :
                                    'General Task').replace(/,/g, " ");
                                let task = (item.task ? item.task.title : '').replace(
                                    /,/g, " ");
                                let intFor = (item.interested_for || '').replace(/,/g,
                                    " ");
                                let bud = (item.budget || '').replace(/,/g, " ");
                                let rem = (item.remark || '').replace(/\n/g, " ")
                                    .replace(/,/g, " ");
                                let calledAt = (item.called_at || '');

                                csv +=
                                    `${cName},${bName},${dName},${dgName},${tName},${tId},${custName},${custMob},${altMob},${emailId},${address},${dob},${anniv},${phase},${task},${item.call_status},${intFor},${bud},${item.followup_date || ''},${rem},${calledAt}\n`;
                            });

                            let link = document.createElement("a");
                            link.setAttribute("href", encodeURI("data:text/csv;charset=utf-8," +
                                csv));
                            link.setAttribute("download", "Detailed_Calling_Report.csv");
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        } else {
                            Swal.fire('No Data',
                                'No records found for the applied filters to export.',
                                'info');
                        }
                    },
                    complete: function() {
                        btn.text('Export Advanced Excel');
                        $('#exportExcelBtn').prop('disabled', false);
                    }
                });
            });

            // Bulk Delete Logic
            $(document).on('change', '#masterCheckbox', function() {
                $('.data-row:visible .row-checkbox').prop('checked', $(this).prop('checked'));
                updateBulkActionBar();
            });
            $(document).on('change', '.row-checkbox', function() {
                updateBulkActionBar();
            });
            $('#selectAllBtn').on('click', function() {
                let allChecked = $('.data-row:visible .row-checkbox:checked').length === $(
                    '.data-row:visible .row-checkbox').length;
                $('.data-row:visible .row-checkbox').prop('checked', !allChecked);
                updateBulkActionBar();
            });

            function updateBulkActionBar() {
                let c = $('.data-row:visible .row-checkbox:checked').length / 2;
                $('#selectedCount').text(Math.ceil(c));
                c > 0 ? $('#bulkActionBar').removeClass('d-none') : $('#bulkActionBar').addClass('d-none');
            }
            $('#deleteSelectedBtn').on('click', function() {
                let ids = [];
                $('.data-row:visible .row-checkbox:checked').each(function() {
                    if (!ids.includes($(this).val())) ids.push($(this).val());
                });
                if (ids.length > 0) {
                    Swal.fire({
                        title: 'Delete?',
                        icon: 'warning',
                        showCancelButton: true
                    }).then((r) => {
                        if (r.isConfirmed) $.post(apiPrefix + '/bulk-delete', {
                            table_name: 'telecaller_allocations',
                            ids: ids
                        }, function() {
                            loadAllocations(false);
                        });
                    });
                }
            });

            // DETAILED PRINT REPORT
            $('#printReportBtn').on('click', function() {
                let btn = $(this);
                let originalHtml = btn.html();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');

                let payload = {
                    company_ids: getSelected('companySelect'),
                    branch_ids: getSelected('branchSelect'),
                    department_ids: getSelected('deptSelect'),
                    designation_ids: getSelected('desigSelect'),
                    assignee_ids: $('#assigneeSelect').val() ? $('#assigneeSelect').val().join(',') : $(
                        '#assigneeFilter').val(),
                    call_status: $('#statusFilter').val(),
                    month: $('#monthFilter').val(),
                    date: $('#dateFilter').val(),
                    search: $('#liveSearch').val()
                };

                $.ajax({
                    url: apiPrefix + '/telecalling/allocations/print',
                    type: 'GET',
                    data: payload,
                    success: function(htmlResponse) {
                        let printWindow = window.open('', '_blank');
                        printWindow.document.open();
                        printWindow.document.write(htmlResponse);
                        printWindow.document.close();

                        setTimeout(function() {
                            printWindow.print();
                        }, 1000);
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Failed to generate print report.', 'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });

          // 🔥 Print Detailed Summary Logic (FIXED: AJAX ke zariye taaki Token jaye aur redirect na ho) 🔥
          // 🔥 Print Detailed Summary Logic (FIXED: Current Employee Ka Data Bhejega) 🔥
            $('#printSummaryBtn').on('click', function() {
                let btn = $(this);
                let originalHtml = btn.html();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Print...');

                // Modal ke title se pehle hum data- attributes set karenge
                let empId = btn.data('empid'); 
                let empType = btn.data('emptype');
                
                let payload = {
                    emp_id: empId,
                    emp_type: empType,
                    call_status: $('#statusFilter').val(),
                    month: $('#monthFilter').val(),
                    date: $('#dateFilter').val()
                };

                $.ajax({
                    url: apiPrefix + '/telecalling/allocations/summary/print',
                    type: 'GET',
                    data: payload,
                    success: function(htmlResponse) {
                        let printWindow = window.open('', '_blank');
                        printWindow.document.open();
                        printWindow.document.write(htmlResponse);
                        printWindow.document.close();
                        
                        setTimeout(function() {
                            printWindow.print();
                        }, 1000);
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Print report fetch karne me error aayi.', 'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });

          
           // 🔥 Unified Detailed Summary Button Click Logic 🔥
            $(document).on('click', '.emp-summary-badge', function() {
                let btn = $(this);
                
                let empId = btn.data('empid'); 
                let empType = btn.data('emptype');
                let empName = btn.data('empname');
                
                // NAYA: Print button me current employee ka ID store kar rahe hain taaki print usika nikle
                $('#printSummaryBtn').data('empid', empId).data('emptype', empType);

                // Header Name Update
                $('#summaryEmpName').text(empName);

                let originalText = btn.html();
                btn.css('pointer-events', 'none').animate({opacity: 0.5}, 200);

                $.ajax({
                    url: apiPrefix + '/telecalling/allocations/detailed-summary',
                    type: 'GET',
                    data: {
                        emp_id: empId,
                        emp_type: empType,
                        date: $('#dateFilter').val(),
                        month: $('#monthFilter').val()
                    },
                    success: function(res) {
                        if (res.status === 'success') {
                            // 1. Table me Summary dalo
                            let summaryHtml = '';
                            let tAssigned = 0,
                                tCalled = 0,
                                tLeft = 0;
                            $.each(res.summary, function(status, counts) {
                                summaryHtml += `<tr>
                                    <td class="text-start fw-bold ps-3">${status}</td>
                                    <td class="text-primary fw-bold">${counts.assigned}</td>
                                    <td class="text-success fw-bold">${counts.called}</td>
                                    <td class="text-danger fw-bold">${counts.left}</td>
                                </tr>`;
                                tAssigned += counts.assigned;
                                tCalled += counts.called;
                                tLeft += counts.left;
                            });

                            if (tAssigned === 0) {
                                summaryHtml =
                                    '<tr><td colspan="4" class="text-muted py-3">No summary data found for applied filters.</td></tr>';
                            } else {
                                summaryHtml += `<tr class="table-dark">
                                    <td class="text-end fw-bold">TOTAL:</td>
                                    <td class="fw-bold fs-6 text-primary">${tAssigned}</td>
                                    <td class="fw-bold fs-6 text-success">${tCalled}</td>
                                    <td class="fw-bold fs-6 text-danger">${tLeft}</td>
                                </tr>`;
                            }
                            $('#modalSummaryTable tbody').html(summaryHtml);

                            // 2. Table me Interested Leads dalo
                            let intHtml = '';
                            if (res.interested_customers.length > 0) {
                                $.each(res.interested_customers, function(i, cust) {
                                    intHtml += `<tr>
                                        <td class="fw-bold text-dark">${cust.name}</td>
                                        <td>${cust.mobile}</td>
                                        <td>${cust.refer_by}</td>
                                        <td><span class="badge bg-success shadow-sm">${cust.status}</span></td>
                                    </tr>`;
                                });
                            } else {
                                intHtml =
                                    `<tr><td colspan="4" class="text-center text-muted py-3">No interested leads found in this filter.</td></tr>`;
                            }
                            $('#modalInterestedTable tbody').html(intHtml);

                            // Modal Open karo
                            $('#summaryDetailsModal').modal('show');
                        }
                    },
                    complete: function() {
                        btn.css('pointer-events', 'auto').animate({
                            opacity: 1
                        }, 200);
                    }
                });
            });

        });
    </script>
@endpush
