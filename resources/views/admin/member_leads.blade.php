@extends('layout.app')

@section('content')
    <!-- SheetJS for Excel Import -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        .lead-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
            background-color: #fff;
        }

        .lead-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .status-badge {
            font-size: 11px;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
        }

        .modal-custom-bg {
            background-color: #f8f9fa;
        }

        .form-control,
        .form-select {
            border-color: #ced4da;
            box-shadow: none !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #0d6efd;
        }
    </style>

    <div class="container-fluid p-0">

        <!-- 1. TOP ACTION BAR -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold mb-0 text-dark"><i class="fas fa-headset text-primary me-2"></i> My Calling Portal</h4>
                <small class="text-muted">Manage your daily follow-ups and fresh leads.</small>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <!-- 🔥 Bas aage ka / hata diya hai 🔥 -->
                <a href="interested-customers/import-template" class="btn btn-info text-white shadow-sm fw-bold">
                    <i class="fas fa-download me-1"></i> Template
                </a>
                <button type="button" class="btn btn-success shadow-sm fw-bold" id="openImportModalBtn">
                    <i class="fas fa-file-import me-1"></i> Upload Excel
                </button>
                <button type="button" class="btn btn-primary shadow-sm fw-bold" onclick="openLeadModal('add')">
                    <i class="fas fa-plus me-1"></i> Add Lead
                </button>
            </div>
        </div>

        <!-- 2. FILTER BAR (UPDATED) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form id="filterForm" class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted mb-1">Mobile</label>
                        <input type="text" id="filterMobile" class="form-control form-control-sm"
                            placeholder="Number...">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted mb-1">Name</label>
                        <input type="text" id="filterName" class="form-control form-control-sm" placeholder="Search Name...">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted mb-1">Date</label>
                        <input type="date" id="filterDate" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1">Status</label>
                        <select id="filterStatus" class="form-select form-select-sm">
    <option value="">-- All Status --</option>
    <option value="Pending">22. Pending status</option>
    <option value="Connected">1. Connected Call</option>
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
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1">Address/City</label>
                        <input type="text" id="filterAddress" class="form-control form-control-sm"
                            placeholder="Search Address...">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-dark btn-sm w-100 fw-bold"><i
                                class="fas fa-search"></i></button>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-light border btn-sm w-100 text-danger fw-bold"
                            id="resetFilterBtn"><i class="fas fa-times"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. LEADS DATA SECTION -->
        <div class="row" id="leadsContainer">
            <div class="col-12 text-center py-5">
                <i class="fas fa-spinner fa-spin fa-3x text-muted mb-3"></i>
                <p class="text-muted fw-bold">Loading your leads...</p>
            </div>
        </div>

        <!-- Load More Button -->
        <div class="text-center mb-5 d-none" id="loadMoreContainer">
            <button class="btn btn-outline-primary px-5 py-2 fw-bold shadow-sm" id="loadMoreBtn">
                Load More Leads <i class="fas fa-arrow-down ms-1"></i>
            </button>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- MODAL 1: ADD / EDIT LEAD (UPDATED FIELDS)  -->
    <!-- ========================================== -->
    <div class="modal fade" id="leadModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="leadModalTitle">Add New Lead</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 modal-custom-bg">
                    <form id="leadForm" class="row g-3">
                        <input type="hidden" id="edit_id">

                        <div class="col-md-4">
                            <label class="small fw-bold text-dark mb-1">Customer Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" id="l_name" name="cust_name" class="form-control" required
                                placeholder="Enter name">
                        </div>

                        <!-- Entry Date Box -->
                        <div class="col-md-4">
                            <label class="small fw-bold text-dark mb-1">Entry Date</label>
                            <input type="date" id="l_entry_date" name="entry_date" class="form-control">
                        </div>

                        <!-- Remark History Box -->
                        <div class="col-12 mt-2 d-none" id="remarkHistoryContainer">
                            <label class="small fw-bold text-info mb-1"><i class="fas fa-history"></i> Remark History</label>
                            <textarea id="l_rem_hist" class="form-control bg-light text-secondary" rows="4" readonly></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold text-dark mb-1">Mobile Number <span
                                    class="text-danger">*</span></label>
                            <input type="text" id="l_mob" name="mobile" class="form-control" maxlength="10"
                                required placeholder="10-digit number">
                            <small id="mobileError" class="text-danger fw-bold d-none">This number already exists!</small>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold text-dark mb-1"><i class="fab fa-whatsapp text-success me-1"></i>
                                WhatsApp No.</label>
                            <input type="text" id="l_alt" name="alternate_no" class="form-control"
                                maxlength="10" placeholder="10-digit number">
                        </div>

                        <div class="col-md-4">
                            <label class="small fw-bold text-dark mb-1">Email ID</label>
                            <input type="email" id="l_email" name="email" class="form-control"
                                placeholder="example@mail.com">
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold text-dark mb-1">Date of Birth</label>
                            <input type="date" id="l_dob" name="dob" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold text-dark mb-1">Anniversary Date</label>
                            <input type="date" id="l_anni" name="anniversary_date" class="form-control">
                        </div>

                        <div class="col-md-12">
                            <label class="small fw-bold text-dark mb-1">Full Address</label>
                            <input type="text" id="l_addr" name="address" class="form-control"
                                placeholder="House no, Street, City...">
                        </div>

                        <div class="col-md-4">
                            <label class="small fw-bold text-dark mb-1">Current Status <span
                                    class="text-danger">*</span></label>
                            <select id="l_status" name="status" class="form-select border-primary" required>
                                <option value="Pending">22. Pending status</option>
                                <option value="Connected">1. Connected Call</option>
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
                            <label class="small fw-bold text-dark mb-1">Next Follow-up Date</label>
                            <input type="date" id="l_fdate" name="followup_date" class="form-control border-info">
                        </div>

                        <!-- 🔥 NAYA: PROVIDER SUGGESTION FIELD 🔥 -->
                        <div class="col-md-4">
                            <label class="small fw-bold text-dark mb-1">Provider / Source</label>
                            <input type="text" id="l_provider_name" name="provider_name" class="form-control"
                                list="providerList" placeholder="Select or type new..." autocomplete="off">
                            <datalist id="providerList">
                                <!-- Options will be loaded via AJAX -->
                            </datalist>
                            <input type="hidden" id="l_provider_id" name="provider_id">
                        </div>

                        <div class="col-md-4">
                            <label class="small fw-bold text-muted mb-1">Interested For</label>
                            <select id="l_int" name="interested_for" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="villa">Villa</option>
                                <option value="plot">Plot</option>
                                <option value="villa&plot">Villa & Plot</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold text-muted mb-1">Budget</label>
                            <input type="text" id="l_budget" name="budget" class="form-control"
                                placeholder="e.g. 20 Lakhs">
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold text-muted mb-1">Required For (Phase)</label>
                            <input type="text" id="l_req" name="required_for" class="form-control"
                                placeholder="Phase name">
                        </div>

                        <div class="col-12">
                            <label class="small fw-bold text-dark mb-1">Discussion Remarks</label>
                            <textarea id="l_rem" name="remark" class="form-control" rows="2"
                                placeholder="Write discussion summary..."></textarea>
                        </div>
                        <div class="col-12 text-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-secondary px-4 me-2"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success px-5 fw-bold" id="saveLeadBtn">Save
                                Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 2: IMPORT EXCEL (MEMBER SPECIFIC)    -->
    <!-- ========================================== -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark"><i class="fas fa-file-excel text-success me-2"></i> Import
                        Leads</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <form id="importForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-1">Provider/Source Name (e.g. JustDial) <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="provider_name" class="form-control shadow-none"
                                placeholder="Enter source name" required>
                        </div>
                        <div class="mb-4">
                            <label class="small fw-bold text-muted mb-1">Upload Excel File <span
                                    class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control shadow-none"
                                accept=".xlsx, .xls, .csv" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary shadow-none" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="importForm" class="btn btn-success shadow-sm px-4"
                        id="submitImportBtn">
                        <i class="fas fa-upload me-2"></i> Import Data
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let apiPrefix = '/api/v1';
            let currentPage = 1;
            let lastPage = 1;
            let memberContext = {
                company_id: '',
                branch_id: '',
                member_id: ''
            };

            $.ajax({
                url: apiPrefix + '/context',
                type: 'GET',
                success: function(res) {
                    memberContext.company_id = res.company_id || '';
                    memberContext.branch_id = res.branch_id || '';
                    memberContext.member_id = res.profile_id || '';
                    loadLeads(1);
                    loadMemberProviders(); // Fetch providers
                }
            });

            // 🔥 Fetch Providers list mapped to this member_id
            function loadMemberProviders() {
                $.ajax({
                    url: apiPrefix + '/available-providers',
                    type: 'GET',
                    data: {
                        member_id: memberContext.member_id
                    },
                    success: function(res) {
                        if (res.data) {
                            let options = '';
                            res.data.forEach(p => {
                                options +=
                                    `<option data-id="${p.provider_id}" value="${p.provider_name}"></option>`;
                            });
                            $('#providerList').html(options);
                        }
                    }
                });
            }

            // Auto-fill hidden provider_id field when a known provider is selected from datalist
            $('#l_provider_name').on('input', function() {
                let val = $(this).val();
                let option = $('#providerList option').filter(function() {
                    return this.value === val;
                });

                if (option.length > 0) {
                    $('#l_provider_id').val(option.data('id'));
                } else {
                    $('#l_provider_id').val(''); // Backend generate karega agar blank hai
                }
            });

            // 🔥 DYNAMIC STATUS COLOR FUNCTION 🔥
            function getStatusBadge(status) {
                if (!status) return 'bg-secondary text-white';
                let s = status.toLowerCase();

                if (s.includes('done') || s.includes('confirm') || s.includes('registry'))
                return 'bg-success text-white';
                if (s.includes('follow') || s.includes('call back')) return 'bg-warning text-dark';
                if (s.includes('not interested') || s.includes('lost') || s.includes('reject') || s.includes('dnd'))
                    return 'bg-danger text-white';
                if (s.includes('interested')) return 'bg-primary text-white';
                if (s.includes('site visit') || s.includes('connected')) return 'bg-info text-dark';
                if (s.includes('busy') || s.includes('off') || s.includes('reachable') || s.includes('answering'))
                    return 'bg-dark text-white';

                return 'bg-secondary text-white'; // Default fallback (e.g. for Pending)
            }

            // 🔥 FILTER VALUES ADDED TO API CALL 🔥
            function loadLeads(page, append = false) {
               let filterName = $('#filterName').val(); // 🔥 Naya
                let filterDate = $('#filterDate').val();
                let filterMobile = $('#filterMobile').val();
                let filterStatus = $('#filterStatus').val();
                let filterAddress = $('#filterAddress').val();
                if (!append) {
                    $('#leadsContainer').html(
                        '<div class="col-12 text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i><br>Fetching leads...</div>'
                    );
                    $('#loadMoreContainer').addClass('d-none');
                }

                let params = new URLSearchParams({
                    page: page,
                    name: filterName, // 🔥 Naya
                    date: filterDate,
                    mobile: filterMobile,
                    status: filterStatus,
                    address: filterAddress,
                    member_id: memberContext.member_id
                }).toString();

                $.ajax({
                    url: `${apiPrefix}/interested-customers/member-portal/leads?${params}`,
                    type: 'GET',
                    success: function(res) {
                        if (res.success) {
                            currentPage = res.current_page;
                            lastPage = res.last_page;
                            renderLeads(res.data, append);

                            if (currentPage < lastPage) $('#loadMoreContainer').removeClass('d-none');
                            else $('#loadMoreContainer').addClass('d-none');
                        }
                    }
                });
            }

            function renderLeads(leads, append) {
                let html = '';
                if (leads.length === 0 && !append) {
                    html =
                        `<div class="col-12 text-center text-muted py-5"><i class="fas fa-box-open fa-3x mb-3"></i><h5>No leads found!</h5></div>`;
                } else {
                    leads.forEach(lead => {
                        // Naye Badge function ka use karke color assign kiya
                        let badgeClass = getStatusBadge(lead.status);

                        let dateHtml = lead.followup_date ?
                            `<small class="text-danger fw-bold"><i class="fas fa-calendar-alt me-1"></i> Follow-up: ${lead.followup_date}</small>` :
                            `<small class="text-muted">Fresh / No Follow-up</small>`;

                        html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="lead-card p-3 h-100 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2 border-bottom pb-2">
                                <div>
                                    <h6 class="fw-bold text-primary mb-1 text-truncate" style="max-width: 180px;">${lead.cust_name}</h6>
                                    <span class="status-badge ${badgeClass}">${lead.status}</span>
                                </div>
                                <div class="text-end">
                                    <a href="tel:${lead.mobile}" class="btn btn-sm btn-success rounded-circle shadow-sm" title="Call Now">
                                        <i class="fas fa-phone-alt"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="small fw-bold text-dark"><i class="fas fa-mobile-alt text-muted me-2"></i> ${lead.mobile}</div>
                                <div class="small fw-bold text-dark"><i class="fas fa-map-marker-alt text-muted me-2"></i> ${lead.address || 'N/A'}</div>
                            </div>
                            <div class="mt-auto border-top pt-2 d-flex justify-content-between align-items-center">
                                ${dateHtml}
                                <button class="btn btn-sm btn-outline-primary edit-lead-btn" data-id="${lead.id}">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </div>
                        </div>
                    </div>`;
                    });
                }
                if (append) $('#leadsContainer').append(html);
                else $('#leadsContainer').html(html);
            }

            $('#loadMoreBtn').click(function() {
                let originalText = $(this).html();
                $(this).html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
                loadLeads(currentPage + 1, true);
                setTimeout(() => {
                    $(this).html(originalText).prop('disabled', false);
                }, 1000);
            });

            $('#filterForm').submit(function(e) {
                e.preventDefault();
                loadLeads(1);
            });
            $('#resetFilterBtn').click(function() {
                $('#filterForm')[0].reset();
                loadLeads(1);
            });

           // 🔥 POPULATE ALL NEW FIELDS ON EDIT 🔥
            window.openLeadModal = function(mode, id = null) {
                $('#leadForm')[0].reset();
                $('#edit_id').val('');
                $('#mobileError').addClass('d-none');
                $('#saveLeadBtn').prop('disabled', false);
                $('#l_provider_id').val('');

                if (mode === 'add') {
                    $('#leadModalTitle').text('Add New Lead');
                    
                    // 🔥 NAYA: Aaj ki date pre-fill karna & History hide karna
                    $('#l_entry_date').val(new Date().toLocaleDateString('en-CA'));
                    $('#remarkHistoryContainer').addClass('d-none');
                    
                    $('#leadModal').modal('show');
                } else {
                    $('#leadModalTitle').text('Update Lead Details');
                    $.get(apiPrefix + `/interested-customers/${id}`, function(res) {
                        let d = res.data;
                        $('#edit_id').val(d.id);
                        $('#l_name').val(d.cust_name);
                        $('#l_mob').val(d.mobile);
                        $('#l_alt').val(d.alternate_no);
                        $('#l_email').val(d.email);
                        $('#l_dob').val(d.dob);
                        $('#l_anni').val(d.anniversary_date);
                        $('#l_addr').val(d.address);
                        $('#l_status').val(d.status);
                        $('#l_fdate').val(d.followup_date);
                        $('#l_int').val(d.interested_for);
                        $('#l_budget').val(d.budget);
                        $('#l_req').val(d.required_for);
                        $('#l_rem').val(d.remark);
                       $('#l_provider_name').val(d.provider_name || '');
                        $('#l_provider_id').val(d.provider_id || '');
                        
                        // 🔥 NAYA: Entry Date set karein aur History dikhayein
                        $('#l_entry_date').val(d.entry_date || '');
                        
                        if(d.remark_history && d.remark_history.trim() !== '') {
                            $('#l_rem_hist').val(d.remark_history);
                            $('#remarkHistoryContainer').removeClass('d-none');
                        } else {
                            $('#remarkHistoryContainer').addClass('d-none');
                        }

                        $('#leadModal').modal('show');
                    });
                }
            };

            $(document).on('click', '.edit-lead-btn', function() {
                openLeadModal('edit', $(this).data('id'));
            });

            $('#l_mob').on('keyup', function() {
                let mobile = $(this).val();
                let excludeId = $('#edit_id').val();
                if (mobile.length >= 10) {
                    $.post(apiPrefix + '/interested-customers/check-mobile', {
                        mobile: mobile,
                        exclude_id: excludeId
                    }, function(res) {
                        if (res.exists) {
                            $('#mobileError').removeClass('d-none');
                            $('#saveLeadBtn').prop('disabled', true);
                        } else {
                            $('#mobileError').addClass('d-none');
                            $('#saveLeadBtn').prop('disabled', false);
                        }
                    });
                }
            });

            $('#leadForm').submit(function(e) {
                e.preventDefault();
                let id = $('#edit_id').val();
                let isAdd = (id === '');
                let url = isAdd ? apiPrefix + '/interested-customers' : apiPrefix +
                    `/interested-customers/${id}`;
                let type = isAdd ? 'POST' : 'PUT';

                let formData = $(this).serializeArray();
                if (isAdd) {
                    formData.push({
                        name: 'company_id',
                        value: memberContext.company_id
                    });
                    formData.push({
                        name: 'branch_id',
                        value: memberContext.branch_id
                    });
                    formData.push({
                        name: 'assigned_telecaller',
                        value: memberContext.member_id
                    });
                    formData.push({
                        name: 'is_member',
                        value: 1
                    });
                    formData.push({
                        name: 'member_id',
                        value: memberContext.member_id
                    });
                    formData.push({
                        name: 'entry_type',
                        value: 'direct'
                    });
                }

                let btn = $('#saveLeadBtn');
                let og = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

                $.ajax({
                    url: url,
                    type: type,
                    data: $.param(formData),
                    success: function(res) {
                        if (res.is_duplicate) {
                            Swal.fire('Duplicate', res.message, 'warning');
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: 'Saved!',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            $('#leadModal').modal('hide');
                            loadLeads(currentPage);
                            loadMemberProviders(); // Reload providers if a new one was added
                        }
                    },
                    complete: function() {
                        btn.html(og).prop('disabled', false);
                    }
                });
            });

            // 🔥 OPEN IMPORT MODAL 🔥
            $('#openImportModalBtn').click(function() {
                $('#importForm')[0].reset();
                $('#importModal').modal('show');
            });

            // 🔥 EXCEL IMPORT & CHUNKING LOGIC 🔥
            const expectedHeaders = ['cust_name', 'mobile', 'email', 'address', 'remark', 'status',
                'assigned_telecaller', 'reference', 'refer_by'
            ];

            $('#importForm').on('submit', function(e) {
                e.preventDefault();

                let fileInput = $(this).find('input[type="file"]')[0];
                let file = fileInput.files[0];
                if (!file) return;

                let btn = $('#submitImportBtn');
                let originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Reading Excel...').prop('disabled',
                    true);

                let pName = $('input[name="provider_name"]').val().trim();

                let reader = new FileReader();
                reader.onload = function(e) {
                    let data = new Uint8Array(e.target.result);
                    let workbook = XLSX.read(data, {
                        type: 'array'
                    });
                    let firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    let excelData = XLSX.utils.sheet_to_json(firstSheet, {
                        defval: ""
                    });

                    if (excelData.length === 0) {
                        Swal.fire('Empty File!', 'No data found in the Excel file.', 'error');
                        btn.html(originalText).prop('disabled', false);
                        return;
                    }

                    let actualHeaders = Object.keys(excelData[0]);
                    let isValid = expectedHeaders.every(h => actualHeaders.includes(h));

                    if (!isValid) {
                        Swal.fire('Format Mismatch!',
                            'Please download the Template first. Columns must match exactly!',
                            'error');
                        btn.html(originalText).prop('disabled', false);
                        return;
                    }

                    btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Generating ID...');

                    $.ajax({
                        url: apiPrefix + '/interested-customers/next-provider-id',
                        type: 'GET',
                        success: function(res) {
                            if (res.status === 'success') {
                                processChunkedImport(excelData, pName, res.provider_id, btn,
                                    originalText);
                            } else {
                                Swal.fire('Error', 'Could not generate Provider ID',
                                    'error');
                                btn.html(originalText).prop('disabled', false);
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'API Route missing for Provider ID!',
                                'error');
                            btn.html(originalText).prop('disabled', false);
                        }
                    });
                };
                reader.readAsArrayBuffer(file);
            });

            function processChunkedImport(allData, pName, pId, btn, originalText) {
                let chunkSize = 100;
                let totalRows = allData.length;
                let currentIndex = 0;
                let totalInserted = 0;
                let totalDuplicates = 0;

                function uploadNextChunk() {
                    let chunk = allData.slice(currentIndex, currentIndex + chunkSize);

                    if (chunk.length === 0) {
                        $('#importModal').modal('hide');
                        btn.html(originalText).prop('disabled', false);
                        Swal.fire({
                            title: 'Import Complete!',
                            html: `<b>Inserted:</b> ${totalInserted} records <br> <b>Skipped (Duplicates):</b> ${totalDuplicates} records`,
                            icon: 'success'
                        }).then(() => {
                            $('#importForm')[0].reset();
                            loadLeads(1);
                            loadMemberProviders(); // Reload providers
                        });
                        return;
                    }

                    $.ajax({
                        url: apiPrefix + '/interested-customers/import',
                        type: 'POST',
                        data: {
                            leads: chunk,
                            provider_name: pName,
                            provider_id: pId,
                            company_id: memberContext.company_id,
                            branch_id: memberContext.branch_id,
                            is_member: 1,
                            member_id: memberContext.member_id
                        },
                        success: function(res) {
                            if (res.status === 'success') {
                                totalInserted += res.inserted;
                                totalDuplicates += res.db_duplicates;
                                currentIndex += chunkSize;

                                let percent = Math.min(Math.round((currentIndex / totalRows) * 100),
                                    100);
                                btn.html(
                                    `<i class="fas fa-spinner fa-spin me-2"></i> Importing... ${percent}%`
                                    );

                                uploadNextChunk();
                            } else {
                                Swal.fire('Error', 'Server returned an error.', 'error');
                                btn.html(originalText).prop('disabled', false);
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Import failed midway!', 'error');
                            btn.html(originalText).prop('disabled', false);
                        }
                    });
                }
                uploadNextChunk();
            }

        });
    </script>
@endpush
