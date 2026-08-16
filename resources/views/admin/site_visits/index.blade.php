@extends('layout.app')

@section('content')
    <style>
        .mobile-card {
            border-left: 4px solid var(--brand-primary);
        }

        .floating-action-bar {
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            padding: 10px 20px;
            border-radius: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            z-index: 1050;
            display: none;
        }

        .select2-container,
        .select2-container--default .select2-selection--single {
            width: 100% !important;
            height: 38px;
        }

        .select2-selection__rendered {
            line-height: 38px !important;
        }

        .select2-selection__arrow {
            height: 36px !important;
        }

        .preview-item {
            width: 60px;
            height: 60px;
        }

        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .remove-img {
            width: 18px;
            height: 18px;
            font-size: 10px;
            line-height: 1;
            top: -5px;
            right: -5px;
            padding: 0;
            z-index: 10;
        }

        .remove-existing-img {
            width: 18px;
            height: 18px;
            font-size: 10px;
            line-height: 1;
            top: -5px;
            right: -5px;
            padding: 0;
            z-index: 10;
        }

        #printHeaderContainer {
            display: none;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            .print-area,
            .print-area * {
                visibility: visible;
            }

            .print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .btn,
            .floating-action-bar,
            .modal,
            .stats-panel,
            .row-checkbox,
            th:first-child,
            td:first-child,
            th:last-child,
            td:last-child {
                display: none !important;
            }
        }
    </style>

    <div class="container-fluid">
        <div id="printHeaderContainer">
            <x-print-header />
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 text-dark fw-bold"><i class="fas fa-map-marked-alt text-primary me-2"></i> Site Visits Details
            </h4>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-success secured-item" data-permission="sv_export" onclick="exportExcel()"><i
                        class="fas fa-file-excel"></i> Export</button>
                <button class="btn btn-outline-info secured-item" data-permission="sv_print" onclick="printReport()"><i
                        class="fas fa-print"></i> Print</button>

                <!-- 🟢 FIX 1: TWO SEPARATE BUTTONS FOR DIRECT ADD vs REQUEST -->
                <button class="btn btn-primary" id="addSiteVisitBtnDirect" style="display: none;"
                    onclick="openAddModal()"><i class="fas fa-plus"></i> Add Site Visit</button>
                <button class="btn btn-warning text-dark" id="addSiteVisitBtnRequest" style="display: none;"
                    onclick="openAddModal()"><i class="fas fa-paper-plane"></i> Request Site Visit</button>
            </div>
        </div>

        <!-- 🟢 FIX 2: WRAPPED ENTIRE SECTION FOR ADMIN ONLY -->
        <div class="card shadow-sm border-0 mb-4 bg-light stats-panel" id="admin_only_filter_section"
            style="display: none;">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-3 mb-2"><label class="small fw-bold">Company</label><select
                            class="form-control filter-select" id="filter_company_id"></select></div>
                    <div class="col-md-3 mb-2"><label class="small fw-bold">Branch</label><select
                            class="form-control filter-select" id="filter_branch_id"></select></div>
                    <div class="col-md-3 mb-2"><label class="small fw-bold">Department</label><select
                            class="form-control filter-select" id="filter_department_id"></select></div>
                    <div class="col-md-3 mb-2"><label class="small fw-bold">Designation</label><select
                            class="form-control filter-select" id="filter_designation_id"></select></div>
                </div>
                <div class="row align-items-end">
                    <div class="col-md-3 mb-2"><label class="small fw-bold">Select Employee</label><select
                            class="form-control" id="filter_employee_id"></select></div>
                    <div class="col-md-3 mb-2"><label class="small fw-bold">Start Date</label><input type="date"
                            class="form-control" id="filter_start_date"></div>
                    <div class="col-md-3 mb-2"><label class="small fw-bold">End Date</label><input type="date"
                            class="form-control" id="filter_end_date"></div>
                    <div class="col-md-3 mb-2"><button class="btn btn-dark w-100 fw-bold" id="apply_filter_btn"><i
                                class="fas fa-filter"></i> Apply Filter</button></div>
                </div>

                <div class="row mt-3 text-center" id="stats_container" style="display: none;">
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded bg-white"><small class="text-muted d-block">Total Visits</small>
                            <h5 class="mb-0 text-dark fw-bold" id="stat_total_visits">0</h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 border rounded bg-white"><small class="text-muted d-block">Approved Visits</small>
                            <h5 class="mb-0 text-success fw-bold" id="stat_approved_visits">0</h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mt-2 mt-md-0">
                        <div class="p-2 border rounded bg-white"><small class="text-muted d-block">Total Amount</small>
                            <h5 class="mb-0 text-dark fw-bold" id="stat_total_amount">₹0</h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mt-2 mt-md-0">
                        <div class="p-2 border rounded bg-white"><small class="text-muted d-block">Approved Amount</small>
                            <h5 class="mb-0 text-success fw-bold" id="stat_approved_amount">₹0</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 print-area">
            <div class="card-body p-0">
                <div class="d-none d-print-block mb-3">
                    <x-print-header />
                    <h4 class="text-center mt-2 border-bottom pb-2 fw-bold text-uppercase">Site Visits Report</h4>
                </div>
                <div class="d-none d-md-block table-responsive p-3">
                    <table class="table table-hover align-middle w-100" id="desktopTable">
                        <thead class="bg-light text-secondary" style="font-size: 12px; text-transform: uppercase;">
                            <tr>
                                <th><input type="checkbox" id="selectAllDesktop"></th>
                                <th>Date & Time</th>
    <th>Employee</th>
    <th>Phase</th>
    <th>Customer Name</th> <!-- 🟢 NAYA -->
    <th>Contact</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="d-block d-md-none p-2 bg-light">
                    <div class="mb-2 ms-2"><input type="checkbox" id="selectAllMobile" class="form-check-input"> <label
                            class="small fw-bold ms-1">Select All</label></div>
                    <div id="mobileCardsContainer"></div>
                    <div class="text-center mt-3 mb-3"><button class="btn btn-outline-primary btn-sm rounded-pill px-4"
                            id="loadMoreBtn" style="display:none;">Load More</button></div>
                </div>
            </div>
        </div>
    </div>

    <div class="floating-action-bar" id="bulkActionBar">
        <span class="fw-bold me-3 text-dark"><span id="selectedCount">0</span> Selected</span>
        <button class="btn btn-danger btn-sm rounded-pill secured-item" data-permission="sv_delete"
            onclick="bulkDelete()"><i class="fas fa-trash"></i> Delete Selected</button>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="siteVisitModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold" id="modalTitle">Add Site Visit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="siteVisitForm" enctype="multipart/form-data">
                    <input type="hidden" id="edit_visit_id" name="edit_visit_id">
                    <input type="hidden" id="form_method" name="_method" value="POST" disabled>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="small fw-bold">Company <span
                                        class="text-danger">*</span></label><select name="company_id" id="company_id"
                                    class="form-control" required></select></div>
                            <div class="col-md-6 mb-3"><label class="small fw-bold">Branch</label><select
                                    name="branch_id" id="branch_id" class="form-control"></select></div>
                            <div class="col-md-6 mb-3"><label class="small fw-bold">Department <span
                                        class="text-danger">*</span></label><select name="department_id"
                                    id="department_id" class="form-control" required></select></div>
                            <div class="col-md-6 mb-3"><label class="small fw-bold">Designation <span
                                        class="text-danger">*</span></label><select name="designation_id"
                                    id="designation_id" class="form-control" required></select></div>
                            <div class="col-md-12 mb-3"><label class="small fw-bold">Employee <span
                                        class="text-danger">*</span></label><select name="employee_id" id="employee_id"
                                    class="form-control" required></select></div>

                            <div class="col-md-4 mb-3"><label class="small fw-bold">Phase <span
                                        class="text-danger">*</span></label><select name="phase_id" id="phase_id"
                                    class="form-control" required></select></div>
                           <div class="col-md-4 mb-3"><label class="small fw-bold">Visit Date <span class="text-danger">*</span></label><input type="date" name="visit_date" id="visit_date" class="form-control" required></div>
    <div class="col-md-4 mb-3"><label class="small fw-bold">Visit Time <span class="text-danger">*</span></label><input type="time" name="visit_time" id="visit_time" class="form-control" required></div>
    
    <!-- 🟢 NAYA: Customer Name with Datalist -->
    <div class="col-md-6 mb-3">
        <label class="small fw-bold">Customer Name <span class="text-danger">*</span></label>
        <input type="text" name="customer_name" id="customer_name" class="form-control" list="scheduled_customers_list" autocomplete="off" required>
        <datalist id="scheduled_customers_list"></datalist>
    </div>
    
    <div class="col-md-6 mb-3"><label class="small fw-bold">Customer Contact <span class="text-danger">*</span></label><input type="text" name="customer_contact_number" id="customer_contact_number" class="form-control" required></div>

                            <div class="col-md-6 mb-3">
                                <label class="small fw-bold">Images (Any Format)</label>
                                <input type="file" id="image_upload" name="images[]" class="form-control" multiple
                                    accept="image/*,.pdf">
                                <div id="existing_image_previews" class="mt-2 d-flex flex-wrap gap-2"></div>
                                <div id="image_previews" class="mt-2 d-flex flex-wrap gap-2"></div>
                                <small class="text-muted edit-image-note" style="display:none;">Uploading new images will
                                    append to existing ones.</small>
                            </div>

                            <div class="col-md-12 mb-3"><label class="small fw-bold">Description</label>
                                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" class="btn btn-primary w-100 fw-bold" id="saveSubmitBtn">Save
                            Visit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- VIEW Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold">Site Visit Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6"><small class="text-muted d-block">Employee</small><strong
                                id="v_emp"></strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Phase / Site</small><strong
                                id="v_phase"></strong></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><small class="text-muted d-block">Date & Time</small><strong
                                id="v_datetime"></strong></div>
                        <div class="col-md-4"><small class="text-muted d-block">Contact</small><strong
                                id="v_contact"></strong></div>
                        <div class="col-md-4"><small class="text-muted d-block">Status</small><strong
                                id="v_status"></strong></div>
                    </div>
                    <div class="mb-3"><small class="text-muted d-block">Description</small><span id="v_desc"></span>
                    </div>
                    <div class="mb-3 bg-light p-2 rounded border"><small class="text-muted d-block">Admin
                            Remarks</small><strong id="v_remarks" class="text-danger"></strong></div>

                    <h6 class="fw-bold border-bottom pb-2 mt-4">Attached Images/Files</h6>
                    <div class="d-flex flex-wrap gap-2" id="v_images"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve/Reject Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold" id="actionModalTitle">Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="bg-light p-3 rounded mb-3">
                        <label class="small text-muted fw-bold">Employee Description:</label>
                        <p id="emp_desc_display" class="mb-0 small"></p>
                    </div>
                    <input type="hidden" id="action_visit_id">
                    <input type="hidden" id="action_type">
                    <label class="small fw-bold">Remarks <span class="text-danger">*</span></label>
                    <textarea id="action_remarks" class="form-control" rows="3" required
                        placeholder="Enter reason for approval/rejection..."></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-dark w-100 fw-bold" onclick="submitAction()">Update
                        Status</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    const currentPath = window.location.pathname;
    const currentPortal = currentPath.startsWith('/employee') ? 'employee' : (currentPath.startsWith('/member') ? 'member' : 'admin');

    $(document).ready(function() {
        const isEmployee = (currentPortal === 'employee');
        
        // 🟢 STRICT POLLING FOR PERMISSIONS
        function applyUIPermissions() {
            if (typeof window.userPerms !== 'undefined' && typeof window.userGodMode !== 'undefined') {
                let perms = window.userPerms;
                let isGod = window.userGodMode;

                let hasDirect = isGod || perms.includes('sv_add_direct') || perms.includes('sv_add') || perms.includes('sv__add_direct');
                let hasRequest = isGod || perms.includes('sv_add_request') || perms.includes('sv__add_request');

                if (hasDirect) {
                    $('#addSiteVisitBtnDirect').show();
                    $('#addSiteVisitBtnRequest').hide();
                } else if (hasRequest) {
                    $('#addSiteVisitBtnRequest').show();
                    $('#addSiteVisitBtnDirect').hide();
                } else {
                    $('#addSiteVisitBtnDirect').hide();
                    $('#addSiteVisitBtnRequest').hide();
                }

                if (!isEmployee) {
                    $('#admin_only_filter_section').show();
                    initFilterDropdowns();
                } else {
                    $('#admin_only_filter_section').hide();
                }
            } else {
                setTimeout(applyUIPermissions, 150);
            }
        }
        applyUIPermissions();

        let dataTable;
        let mobileData = [];
        let mobilePage = 0;
        const mobileLimit = 10;

       // 🟢 NAYA: Scheduled Customers Data Array
        let scheduledCustomersData = [];

        function fetchScheduledCustomersForDate() {
            let vDate = $('#visit_date').val();
            let empId = $('#employee_id').val() || window.userId; // Select kiya hua Employee ID ya khud ka ID
            
            $('#scheduled_customers_list').empty();
            scheduledCustomersData = [];

            if(vDate && empId) {
                $.post('/api/v1/site-visits/fetch-scheduled-customers', { 
                    visit_date: vDate, 
                    employee_id: empId 
                }, function(res) {
                    if(res.status === 'success' && res.data.length > 0) {
                        scheduledCustomersData = res.data;
                        let listHtml = '';
                        res.data.forEach(c => {
                            listHtml += `<option value="${c.cust_name}">`;
                        });
                        $('#scheduled_customers_list').html(listHtml);
                    }
                });
            }
        }

        // Jab Visit Date change ho, tab API call karo
        $('#visit_date').on('change', fetchScheduledCustomersForDate);

        // Jab Employee change ho, tab bhi list refresh karo (Admin use case)
        $('#employee_id').on('change', function() {
            if ($('#visit_date').val()) {
                fetchScheduledCustomersForDate();
            }
        });

        // 🟢 NAYA: Autofill Contact Number on Customer Selection
        $('#customer_name').on('input change', function() {
            let selectedName = $(this).val();
            let matchedCustomer = scheduledCustomersData.find(c => c.cust_name === selectedName);
            
            if(matchedCustomer && matchedCustomer.mobile) {
                $('#customer_contact_number').val(matchedCustomer.mobile);
            }
        });

        // IMAGE UPLOAD PREVIEW
        let selectedFiles = new DataTransfer();
        $('#image_upload').on('change', function(e) {
            let files = e.target.files;
            for (let i = 0; i < files.length; i++) {
                selectedFiles.items.add(files[i]);
                let reader = new FileReader();
                reader.onload = function(event) {
                    let isPdf = files[i].type.includes('pdf');
                    let src = isPdf ? 'https://upload.wikimedia.org/wikipedia/commons/8/87/PDF_file_icon.svg' : event.target.result;
                    $('#image_previews').append(`
                        <div class="position-relative d-inline-block me-2 mb-2 preview-item" data-name="${files[i].name}">
                            <img src="${src}" class="rounded border bg-white p-1 shadow-sm">
                            <button type="button" class="btn btn-danger position-absolute rounded-circle remove-img" data-name="${files[i].name}"><i class="fas fa-times"></i></button>
                        </div>
                    `);
                };
                reader.readAsDataURL(files[i]);
            }
            this.files = selectedFiles.files;
        });

        $(document).on('click', '.remove-img', function() {
            let name = $(this).data('name');
            let newDt = new DataTransfer();
            for (let i = 0; i < selectedFiles.files.length; i++) {
                if (selectedFiles.files[i].name !== name) newDt.items.add(selectedFiles.files[i]);
            }
            selectedFiles = newDt;
            $('#image_upload')[0].files = selectedFiles.files;
            $(this).closest('.preview-item').remove();
        });

        $(document).on('click', '.remove-existing-img', function() {
            let imageId = $(this).data('id');
            let elementToRemove = $(this).closest('.preview-item');
            
            Swal.fire({
                title: 'Delete Image?',
                text: "Are you sure you want to delete this existing image? It will be removed permanently.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#E53E3E',
                confirmButtonText: 'Yes, delete!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/api/v1/site-visits/image/${imageId}`,
                        type: 'DELETE',
                        success: function(res) { elementToRemove.remove(); },
                        error: function(err) { Swal.fire('Error', 'Failed to delete image', 'error'); }
                    });
                }
            });
        });

        function initFilterDropdowns() {
            $('#filter_company_id').select2({ ajax: { url: '/api/v1/companies/search-dynamic', data: function(p){ return { q: p.term }; }, processResults: function(d){ return { results: d.data.map(c => ({ id: c.id, text: c.company_name })) }; } } });
            $('#filter_branch_id').select2({ minimumInputLength: 0, ajax: { url: '/api/v1/site-visits/search-branch', data: function(p){ return { q: p.term, company_id: $('#filter_company_id').val() }; }, processResults: function(d){ return { results: d.data.map(b => ({ id: b.id, text: b.branch_name })) }; } } });
            $('#filter_department_id').select2({ ajax: { url: '/api/v1/departments/search-dynamic', data: function(p){ return { q: p.term, company_id: $('#filter_company_id').val(), branch_id: $('#filter_branch_id').val() }; }, processResults: function(d){ return { results: d.data.map(d => ({ id: d.id, text: d.department_name })) }; } } });
            $('#filter_designation_id').select2({ ajax: { url: '/api/v1/designations/search-dynamic', data: function(p){ return { q: p.term, department_id: $('#filter_department_id').val() }; }, processResults: function(d){ return { results: d.data.map(d => ({ id: d.id, text: d.designation_name })) }; } } });
            
            $('#filter_employee_id').select2({
                minimumInputLength: 3,
                ajax: {
                    url: '/api/v1/site-visits/search-employee',
                    data: function(params) {
                        return { q: params.term, company_id: $('#filter_company_id').val(), branch_id: $('#filter_branch_id').val(), department_id: $('#filter_department_id').val(), designation_id: $('#filter_designation_id').val() };
                    },
                    processResults: function(data) { return { results: data.data.map(e => ({ id: e.id, text: e.full_name })) }; }
                }
            });
        }

        function initModalDropdowns() {
            $('#company_id').select2({ dropdownParent: $('#siteVisitModal'), minimumInputLength: 3, ajax: { url: '/api/v1/companies/search-dynamic', data: function (p) { return { q: p.term }; }, processResults: function (d) { return { results: d.data.map(c => ({ id: c.id, text: c.company_name })) }; } }});
            $('#company_id').on('change', function() { $('#branch_id, #department_id, #designation_id, #employee_id, #phase_id').val(null).trigger('change'); });

            $('#branch_id').select2({ dropdownParent: $('#siteVisitModal'), minimumInputLength: 0, ajax: { url: '/api/v1/site-visits/search-branch', data: function (p) { return { q: p.term, company_id: $('#company_id').val() }; }, processResults: function (d) { return { results: d.data.map(b => ({ id: b.id, text: b.branch_name })) }; } }});
            $('#branch_id').on('change', function() { $('#department_id, #designation_id, #employee_id, #phase_id').val(null).trigger('change'); });

            $('#department_id').select2({ dropdownParent: $('#siteVisitModal'), minimumInputLength: 3, ajax: { url: '/api/v1/departments/search-dynamic', data: function (p) { if(!$('#company_id').val()) return { q: '' }; return { q: p.term, company_id: $('#company_id').val(), branch_id: $('#branch_id').val() }; }, processResults: function (d) { return { results: d.data.map(d => ({ id: d.id, text: d.department_name })) }; } }});
            $('#department_id').on('change', function() { $('#designation_id, #employee_id').val(null).trigger('change'); });

            $('#designation_id').select2({ dropdownParent: $('#siteVisitModal'), minimumInputLength: 3, ajax: { url: '/api/v1/designations/search-dynamic', data: function (p) { return { q: p.term, department_id: $('#department_id').val() }; }, processResults: function (d) { return { results: d.data.map(d => ({ id: d.id, text: d.designation_name })) }; } }});
            $('#designation_id').on('change', function() { $('#employee_id').val(null).trigger('change'); });

            $('#employee_id').select2({ dropdownParent: $('#siteVisitModal'), minimumInputLength: 3, ajax: { url: '/api/v1/site-visits/search-employee', data: function (p) { return { q: p.term, company_id: $('#company_id').val(), branch_id: $('#branch_id').val(), department_id: $('#department_id').val(), designation_id: $('#designation_id').val() }; }, processResults: function (d) { return { results: d.data.map(e => ({ id: e.id, text: e.full_name })) }; } }});
            $('#phase_id').select2({ dropdownParent: $('#siteVisitModal'), minimumInputLength: 0, ajax: { url: '/api/v1/site-visits/search-phase', data: function (p) { return { company_id: $('#company_id').val(), branch_id: $('#branch_id').val() }; }, processResults: function (d) { return { results: d.data.map(p => ({ id: p.id, text: p.phase_name })) }; } }});
        }
        initModalDropdowns();

        function loadData() {
            if (window.innerWidth >= 768) {
                if (dataTable) dataTable.destroy();
                dataTable = $('#desktopTable').DataTable({
                    processing: true, serverSide: true,
                    ajax: {
                        url: '/api/v1/site-visits',
                        data: function(d) {
                            if(!isEmployee){
                                d.company_id = $('#filter_company_id').val();
                                d.branch_id = $('#filter_branch_id').val();
                                d.department_id = $('#filter_department_id').val();
                                d.designation_id = $('#filter_designation_id').val();
                            }
                            d.employee_id = $('#filter_employee_id').val();
                            d.month_start = $('#filter_start_date').val();
                            d.month_end = $('#filter_end_date').val();
                        }
                    },
                    columns: [
                        { data: null, render: function(d) { return `<input type="checkbox" class="row-checkbox form-check-input" value="${d.id}">`; }, orderable: false },
                        { data: null, render: function(d) { return `${d.visit_date} <br><small class="text-muted">${d.visit_time || ''}</small>`; } },
                        { data: null, render: function(d) { return d.employee ? d.employee.full_name : '<span class="text-danger">N/A</span>'; } },
                        { data: null, render: function(d) { return d.phase ? d.phase.phase_name : '<span class="text-danger">N/A</span>'; } },
                        { data: 'customer_name', render: function(d) { return d ? d : 'N/A'; } },
                        { data: 'customer_contact_number' },
                        { data: 'status', render: function(d) {
                            let c = d === 'active' ? 'success' : (d === 'pending' ? 'warning' : 'danger');
                            return `<span class="badge bg-${c}">${d.toUpperCase()}</span>`;
                        }},
                        { data: null, className: 'text-end', render: function(d) { return getActionButtons(d); }, orderable: false }
                    ],
                    drawCallback: function() { if (typeof window.applyPermissions === 'function') window.applyPermissions(); }
                });
            } else {
                $.ajax({ url: '/api/v1/site-visits?length=-1', type: 'GET', success: function(res) { mobileData = res.data; mobilePage = 0; $('#mobileCardsContainer').empty(); renderMobileCards(); } });
            }
        }

        window.printReport = function() {
            let btn = $('button[onclick="printReport()"]');
            let originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

            let payload = { length: -1 };
            if (!isEmployee) {
                payload.company_id = $('#filter_company_id').val();
                payload.branch_id = $('#filter_branch_id').val();
                payload.department_id = $('#filter_department_id').val();
                payload.designation_id = $('#filter_designation_id').val();
            }
            payload.employee_id = $('#filter_employee_id').val();
            payload.month_start = $('#filter_start_date').val();
            payload.month_end = $('#filter_end_date').val();

            $.get('/api/v1/site-visits', payload, function(res) {
                let rows = '';
                res.data.forEach(d => {
                    let phase = d.phase ? d.phase.phase_name : 'N/A';
                    let emp = d.employee ? d.employee.full_name : 'N/A';
                    let custName = d.customer_name ? d.customer_name : 'N/A';
                    
                    rows += `
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 8px;">${d.visit_date} <br><small>${d.visit_time || ''}</small></td>
                            <td style="border: 1px solid #ddd; padding: 8px;">${emp}</td>
                            <td style="border: 1px solid #ddd; padding: 8px;">${phase}</td>
                            <td style="border: 1px solid #ddd; padding: 8px;">${custName}</td>
                            <td style="border: 1px solid #ddd; padding: 8px;">${d.customer_contact_number}</td>
                            <td style="border: 1px solid #ddd; padding: 8px;">${d.status.toUpperCase()}</td>
                        </tr>
                    `;
                });

                let headerContent = $('#printHeaderContainer').html();
                let periodText = (payload.month_start && payload.month_end) ? `Period: ${payload.month_start} to ${payload.month_end}` : 'All Time Record';

                let finalHtml = `
                    <html><head><title>Site Visits Report</title><style>
                            body { font-family: Arial, sans-serif; padding: 20px; }
                            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
                            th { background-color: #f4f4f4; border: 1px solid #ddd; padding: 8px; text-align: left; text-transform: uppercase; }
                            .header-wrap { display: flex; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 12px; }
                            .header-logo { width: 20%; display: flex; flex-direction: column; align-items: center; }
                            .logo-image { width: 100%; max-height: 70px; object-fit: contain; }
                            .iso { font-size: 8px; font-weight: bold; margin-top: 4px; color: #dc3545; text-align: center; }
                            .header-text { width: 80%; display: flex; flex-direction: column; align-items: center; justify-content: center; }
                            .company-title { font-size: 24px; font-weight: 900; color: #000; margin-bottom: 4px; text-align: center; }
                            .cin-text { font-size: 10px; font-weight: bold; margin-bottom: 5px; }
                            .small-text { font-size: 11px; line-height: 1.4; color: #000; text-align: center; }
                        </style></head><body>
                        ${headerContent}
                        <div style="text-align: center; margin-bottom: 20px;"><h3 style="margin: 0; text-transform: uppercase;">Site Visits Report</h3><p style="margin: 5px 0 0 0; font-size: 12px;">${periodText}</p></div>
                        <table><thead><tr><th>Date & Time</th><th>Employee</th><th>Phase</th><th>Customer</th><th>Contact</th><th>Status</th></tr></thead><tbody>${rows}</tbody></table>
                    </body></html>
                `;

                let printWindow = window.open('', '_blank');
                printWindow.document.open(); printWindow.document.write(finalHtml); printWindow.document.close();
                setTimeout(() => { printWindow.print(); }, 500);
                btn.prop('disabled', false).html(originalHtml);
            });
        };

        function renderMobileCards() {
            let start = mobilePage * mobileLimit;
            let end = start + mobileLimit;
            let items = mobileData.slice(start, end);

            items.forEach(d => {
                let c = d.status === 'active' ? 'success' : (d.status === 'pending' ? 'warning' : 'danger');
                let custName = d.customer_name ? d.customer_name : '';
                
                let html = `
                    <div class="card border-0 shadow-sm mb-3 mobile-card p-3">
                        <div class="d-flex justify-content-between mb-2">
                            <div><input type="checkbox" class="row-checkbox form-check-input me-2" value="${d.id}"><span class="fw-bold">${d.employee ? d.employee.full_name : 'Unknown'}</span></div>
                            <span class="badge bg-${c}">${d.status.toUpperCase()}</span>
                        </div>
                        <div class="small text-muted mb-2"><i class="fas fa-map-marker-alt text-danger"></i> ${d.phase ? d.phase.phase_name : 'N/A'} | <i class="fas fa-user"></i> ${custName} | <i class="fas fa-phone"></i> ${d.customer_contact_number}</div>
                        <div class="d-flex justify-content-between align-items-center mt-2 border-top pt-2">
                            <small><i class="fas fa-calendar"></i> ${d.visit_date} ${d.visit_time || ''}</small>
                            <div>${getActionButtons(d)}</div>
                        </div>
                    </div>
                `;
                $('#mobileCardsContainer').append(html);
            });

            if (end < mobileData.length) $('#loadMoreBtn').show(); else $('#loadMoreBtn').hide();
            mobilePage++;
            if (typeof window.applyPermissions === 'function') window.applyPermissions();
        }
        $('#loadMoreBtn').click(renderMobileCards);
        setTimeout(() => { loadData(); }, 500);

        function getActionButtons(data) {
            let desc = data.description ? data.description.replace(/'/g, "\\'").replace(/"/g, "&quot;") : '';
            let remarks = data.remarks ? data.remarks.replace(/'/g, "\\'").replace(/"/g, "&quot;") : '';

            let btnAppr = `<button class="btn btn-sm ${data.status === 'active' ? 'btn-success text-white' : 'btn-outline-success'} me-1 secured-item" data-permission="sv_appr" title="Approve / Update Remarks" onclick="openActionModal(${data.id}, 'approve', '${desc}', '${remarks}')"><i class="fas fa-check-double"></i></button>`;
            let btnRej = `<button class="btn btn-sm ${data.status === 'inactive' ? 'btn-danger text-white' : 'btn-outline-danger'} me-1 secured-item" data-permission="sv_rej" title="Reject / Update Remarks" onclick="openActionModal(${data.id}, 'reject', '${desc}', '${remarks}')"><i class="fas fa-times-circle"></i></button>`;

            let html = `<div class="d-flex justify-content-end gap-1">
                <button class="btn btn-sm btn-info text-white" onclick="viewVisit(${data.id})" title="View"><i class="fas fa-eye"></i></button>
                <button class="btn btn-sm btn-secondary secured-item" data-permission="sv_edit" onclick="editVisit(${data.id})" title="Edit"><i class="fas fa-pen"></i></button>
                ${btnAppr} ${btnRej}
            </div>`;
            return html;
        }

        window.viewVisit = function(id) {
            $.get(`/api/v1/site-visits/${id}`, function(res) {
                let d = res.data;
                $('#v_emp').text(d.employee ? d.employee.full_name : 'N/A');
                $('#v_phase').text(d.phase ? d.phase.phase_name : 'N/A');
                $('#v_datetime').text(`${d.visit_date} ${d.visit_time || ''}`);
                
                // 🟢 VIEW FIX: Properly scoped 'd' variable usage
                $('#v_contact').html((d.customer_name ? d.customer_name : 'N/A') + '<br><small>' + d.customer_contact_number + '</small>');
                
                $('#v_status').html(`<span class="badge bg-${d.status === 'active' ? 'success' : (d.status==='pending'?'warning':'danger')}">${d.status.toUpperCase()}</span>`);
                $('#v_desc').text(d.description || 'N/A');
                $('#v_remarks').text(d.remarks || 'No remarks provided.');

                let imgHtml = '';
                if (d.images && d.images.length > 0) {
                    d.images.forEach(i => {
                        let path = '/' + i.media_path;
                        let isPdf = path.includes('.pdf');
                        let src = isPdf ? 'https://upload.wikimedia.org/wikipedia/commons/8/87/PDF_file_icon.svg' : path;
                        imgHtml += `<a href="${path}" target="_blank"><img src="${src}" class="border rounded p-1 shadow-sm" style="width: 80px; height: 80px; object-fit: cover;"></a>`;
                    });
                } else { imgHtml = '<small class="text-muted">No images attached.</small>'; }
                $('#v_images').html(imgHtml);
                $('#viewModal').modal('show');
            });
        };

      window.openAddModal = function() {
            $('#siteVisitForm')[0].reset();
            $('#edit_visit_id').val('');
            
            // 🟢 FIX 2: Wapas POST set karke disable kiya taaki naya add theek se ho
            $('#form_method').prop('disabled', true).val('POST');
            
            let isRequestOnly = !window.userGodMode && typeof window.userPerms !== 'undefined' && !window.userPerms.includes('sv_add_direct') && window.userPerms.includes('sv_add_request');  
            $('#modalTitle').text(isRequestOnly ? 'Request Site Visit' : 'Add Site Visit');
            $('#saveSubmitBtn').text(isRequestOnly ? 'Submit Request' : 'Save Visit');

            $('.edit-image-note').hide();
            $('#image_previews, #existing_image_previews').empty();
            $('#scheduled_customers_list').empty(); // Clear Datalist
            selectedFiles = new DataTransfer();

            $('#company_id, #branch_id, #department_id, #designation_id, #employee_id, #phase_id').val(null).trigger('change');
            
            if (isEmployee) { 
                $('#company_id, #branch_id, #department_id, #designation_id, #employee_id').prop('disabled', true); 
                $.get(`/api/v1/employees/${window.userId}`, function(res) {
                    let emp = res.data;
                    if (emp.company) { let o = new Option(emp.company.company_name, emp.company_id, true, true); $('#company_id').append(o).trigger('change'); }
                    if (emp.branch_id) { let o = new Option(emp.branch.branch_name, emp.branch_id, true, true); $('#branch_id').append(o).trigger('change'); } 
                    else { let o = new Option('Head Office', 'null', true, true); $('#branch_id').append(o).trigger('change'); }
                    if (emp.department) { let o = new Option(emp.department.department_name, emp.department_id, true, true); $('#department_id').append(o).trigger('change'); }
                    if (emp.designation) { let o = new Option(emp.designation.designation_name, emp.designation_id, true, true); $('#designation_id').append(o).trigger('change'); }
                    let empOption = new Option(emp.full_name, emp.id, true, true); $('#employee_id').append(empOption).trigger('change');
                });
            }
            $('#siteVisitModal').modal('show');
        };

       window.editVisit = function(id) {
            $.get(`/api/v1/site-visits/${id}`, function(res) {
                let d = res.data;
                $('#siteVisitForm')[0].reset();
                $('#edit_visit_id').val(d.id);
                
                // 🟢 FIX 1: Value ko PUT set kiya taaki Laravel ise Update samjhe
                $('#form_method').prop('disabled', false).val('PUT'); 
                
                $('#modalTitle').text('Edit Site Visit');
                $('#saveSubmitBtn').text('Update Visit');
                $('.edit-image-note').show();
                $('#image_previews, #existing_image_previews').empty();
                $('#scheduled_customers_list').empty();
                selectedFiles = new DataTransfer();

                $('#visit_date').val(d.visit_date);
                $('#visit_time').val(d.visit_time);
                $('#customer_name').val(d.customer_name); 
                $('#customer_contact_number').val(d.customer_contact_number);
                $('#description').val(d.description);

                if (d.company) { let o = new Option(d.company.company_name, d.company_id, true, true); $('#company_id').append(o).trigger('change'); }
                if (d.branch_id) { let o = new Option(d.branch.branch_name, d.branch_id, true, true); $('#branch_id').append(o).trigger('change'); } 
                else { let o = new Option('Head Office', 'null', true, true); $('#branch_id').append(o).trigger('change'); }

                if (d.department) { let o = new Option(d.department.department_name, d.department_id, true, true); $('#department_id').append(o).trigger('change'); }
                if (d.designation) { let o = new Option(d.designation.designation_name, d.designation_id, true, true); $('#designation_id').append(o).trigger('change'); }
                if (d.employee) { let o = new Option(d.employee.full_name, d.employee_id, true, true); $('#employee_id').append(o).trigger('change'); }
                if (d.phase) { let o = new Option(d.phase.phase_name, d.phase_id, true, true); $('#phase_id').append(o).trigger('change'); }

                if (d.images && d.images.length > 0) {
                    d.images.forEach(img => {
                        let path = '/' + img.media_path;
                        let isPdf = path.includes('.pdf');
                        let src = isPdf ? 'https://upload.wikimedia.org/wikipedia/commons/8/87/PDF_file_icon.svg' : path;
                        $('#existing_image_previews').append(`
                            <div class="position-relative d-inline-block me-2 mb-2 preview-item" id="existing_img_${img.id}">
                                <img src="${src}" class="rounded border bg-white p-1 shadow-sm">
                                <button type="button" class="btn btn-danger position-absolute rounded-circle remove-existing-img" data-id="${img.id}"><i class="fas fa-times"></i></button>
                            </div>
                        `);
                    });
                }
                $('#siteVisitModal').modal('show');
            });
        };

       $('#siteVisitForm').submit(function(e) {
            e.preventDefault();
            
            // 🟢 FIX: Form submit karte waqt disabled fields ko temporarily enable karo
            // Taaki FormData unki values ko capture kar sake
            let disabledFields = $(this).find(':disabled');
            disabledFields.prop('disabled', false);

            let formData = new FormData(this);
            
            // 🟢 Data capture hone ke baad turant wapas lock (disable) kar do
            disabledFields.prop('disabled', true);

            // Employee ke case me employee_id safety ke liye manual append kar dete hain
            if (isEmployee) formData.append('employee_id', window.userId);

            let editId = $('#edit_visit_id').val();
            let url = editId ? `/api/v1/site-visits/${editId}` : '/api/v1/site-visits';

            let submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

            $.ajax({
                url: url, type: 'POST', data: formData, contentType: false, processData: false,
                success: function(res) {
                    $('#siteVisitModal').modal('hide');
                    Swal.fire({ icon: 'success', title: 'Success', text: res.message, timer: 1500, showConfirmButton: false });
                    loadData();
                },
                error: function(err) { Swal.fire('Error', err.responseJSON?.message || 'Error occurred', 'error'); },
                complete: function() { submitBtn.prop('disabled', false).html(editId ? 'Update Visit' : 'Save Visit'); }
            });
        });

        window.openActionModal = function(id, type, desc, existingRemarks) {
            $('#action_visit_id').val(id);
            $('#action_type').val(type);
            $('#emp_desc_display').text(desc && desc !== 'undefined' ? desc : 'No description provided.');
            $('#action_remarks').val(existingRemarks && existingRemarks !== 'undefined' ? existingRemarks : '');
            $('#actionModalTitle').text(type === 'approve' ? 'Approve / Update Remarks' : 'Reject / Update Remarks');
            $('#actionModal').modal('show');
        };

        window.submitAction = function() {
            let id = $('#action_visit_id').val();
            let type = $('#action_type').val();
            let remarks = $('#action_remarks').val();
            if (!remarks) { Swal.fire('Warning', 'Remarks are mandatory!', 'warning'); return; }

            let btn = $('#actionModal .btn-dark');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

            $.ajax({
                url: `/api/v1/site-visits/${id}/${type}`, type: 'POST', data: { remarks: remarks },
                success: function(res) {
                    $('#actionModal').modal('hide');
                    Swal.fire({ icon: 'success', title: 'Success', text: res.message, timer: 1500, showConfirmButton: false });
                    loadData(); $('#action_remarks').val('');
                },
                complete: function() { btn.prop('disabled', false).text('Update Status'); }
            });
        };

        $('#apply_filter_btn').click(function() {
            let empId = $('#filter_employee_id').val() || (isEmployee ? window.userId : null);
            let start = $('#filter_start_date').val();
            let end = $('#filter_end_date').val();

            loadData();

            if (empId && start && end) {
                $.post('/api/v1/site-visits/calculate-stats', { employee_id: empId, month_start: start, month_end: end }, function(res) {
                    $('#stats_container').fadeIn();
                    $('#stat_total_visits').text(res.data.total_visits);
                    $('#stat_approved_visits').text(res.data.approved_visits);
                    $('#stat_total_amount').text('₹' + parseFloat(res.data.total_amount).toFixed(2));
                    $('#stat_approved_amount').text('₹' + parseFloat(res.data.approved_amount).toFixed(2));
                });
            }
        });

        function toggleActionBar() {
            let count = $('.row-checkbox:checked').length;
            if (count > 0) { $('#selectedCount').text(count); $('#bulkActionBar').fadeIn(); } else { $('#bulkActionBar').fadeOut(); }
        }
        $(document).on('change', '.row-checkbox', toggleActionBar);
        $('#selectAllDesktop, #selectAllMobile').change(function() { $('.row-checkbox').prop('checked', $(this).prop('checked')); toggleActionBar(); });

        window.bulkDelete = function() {
            let ids = []; $('.row-checkbox:checked').each(function() { ids.push($(this).val()); });
            Swal.fire({ title: 'Are you sure?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#E53E3E', confirmButtonText: 'Yes, delete!' }).then((result) => {
                if (result.isConfirmed) {
                    $.post('/api/v1/site-visits/bulk-delete', { ids: ids }, function(res) {
                        $('#bulkActionBar').fadeOut(); Swal.fire({ icon: 'success', title: 'Deleted', text: res.message, timer: 1500, showConfirmButton: false }); loadData();
                    });
                }
            });
        };

        window.exportExcel = function() { Swal.fire('Info', 'Export feature coming soon!', 'info'); };
    });
</script>
@endpush