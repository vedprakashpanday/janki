@extends('layout.app')
@section('content')
    <style>
        /* 1. Modal/Offcanvas Width Fix */
        .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-dropdown {
            z-index: 1060 !important;
            /* Bootstrap Modal z-index se upar */
        }

        /* 2. Select2 Box Height & Appearance */
        .select2-container .select2-selection--multiple {
            min-height: 40px !important;
            max-height: 80px !important;
            overflow-y: auto !important;
            border: 1px solid #ced4da !important;
            border-radius: 0.375rem !important;
            padding: 0 4px !important;
            background-color: #fff !important;
        }

        /* Locked (Disabled) State Styling */
        .select2-container--default.select2-container--disabled .select2-selection--multiple {
            background-color: #e9ecef !important;
            cursor: not-allowed !important;
        }

        /* 3. Input Text Alignment */
        .select2-container .select2-search--inline .select2-search__field {
            margin-top: 8px !important;
            line-height: 20px !important;
            font-family: inherit !important;
        }

        /* 4. Tags (Pills) styling */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #f8f9fa !important;
            border: 1px solid #ced4da !important;
            color: #212529 !important;
            border-radius: 4px !important;
            margin-top: 5px !important;
            padding: 2px 6px !important;
            display: inline-flex !important;
            align-items: center;
        }

        /* 5. Truncate long names */
        .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            max-width: 180px !important;
            padding-left: 5px !important;
            display: inline-block !important;
            font-size: 13px !important;
        }

        /* 6. Remove 'x' button styling */
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            border-right: 1px solid #dee2e6 !important;
            padding-right: 5px !important;
            color: #dc3545 !important;
        }
    </style>

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Fine / Penalty Management</h4>
            <div>
                <button id="addBtnDesktop" class="btn btn-primary d-none" data-bs-toggle="modal" data-bs-target="#fineModal">
                    <i class="fas fa-plus"></i> Add Fine/Penalty
                </button>
                <button id="addBtnMobile" class="btn btn-primary d-none" data-bs-toggle="offcanvas"
                    data-bs-target="#fineOffcanvas">
                    <i class="fas fa-plus"></i> Add
                </button>
            </div>
        </div>

        <div class="bulk-actions d-none mb-2" id="bulkActionContainer">
            <button class="btn btn-sm btn-info" id="selectAllBtn">Select All</button>
            <button class="btn btn-sm btn-danger" id="deleteSelectedBtn">Delete Selected</button>
        </div>

        <div class="card mb-3 shadow-sm border-0">
            <div class="card-body bg-light p-3 rounded">
                <div class="row g-2">
                    <div class="col-md-2"><input type="text" id="f_search" class="form-control"
                            placeholder="Live Search Name/ID..."></div>
                    <div class="col-md-2"><input type="date" id="f_start_date" class="form-control" title="Start Date">
                    </div>
                    <div class="col-md-2"><input type="date" id="f_end_date" class="form-control" title="End Date"></div>
                    <div class="col-md-2">
                        <select id="f_company_id" class="form-select">
                            <option value="">All Companies</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="f_department_id" class="form-select">
                            <option value="">All Departments</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" id="btnFilter"><i class="fas fa-filter"></i> Apply
                            Filter</button>
                    </div>
                </div>

                <!-- DYNAMIC AGGREGATE SUMMARY UI -->
                <div class="alert alert-info d-none mb-3 mt-3" id="summaryContainer">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <h6 class="mb-0"><i class="fas fa-calculator me-2"></i> Filtered Total Summary</h6>
                        <button class="btn btn-sm btn-outline-dark bg-white" id="toggleSummaryBtn">
                            <i class="fas fa-chevron-down"></i> Expand Details
                        </button>
                    </div>

                    <div id="summaryDetailsWrapper" class="d-none">
                        <div id="summaryDetails" class="mb-2" style="max-height: 400px; overflow-y: auto;"></div>
                        <div class="text-center mt-2">
                            <button class="btn btn-sm btn-info text-white d-none" id="loadMoreSummaryBtn">
                                <i class="fas fa-sync-alt"></i> Load More (Next 20)
                            </button>
                        </div>
                    </div>

                    <div class="border-top pt-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Grand Total</h6>
                        <h5 class="mb-0 fw-bold text-danger" id="grandTotalUI">₹0.00</h5>
                    </div>
                </div>

                <div class="row g-2 mt-1">
                    <div class="col-md-6 d-flex gap-2">
                        <button class="btn btn-dark w-100 d-none" id="btnPrintAll"><i class="fas fa-print"></i> Print
                            Filtered Data</button>
                        <button class="btn btn-success w-100 d-none" id="btnExportExcel"><i class="fas fa-file-excel"></i>
                            Export to Excel</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive d-none d-md-block shadow-sm bg-white rounded">
            <table class="table table-bordered table-hover mb-0" id="fineTable">
                <thead class="table-light">
                    <tr>
                        <th><input type="checkbox" id="masterCheckbox" style="display: none;"></th>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Fine (₹/Days)</th>
                        <th>Penalty (₹/Days)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Loading data...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-block d-md-none" id="mobileCardsContainer">
            <div class="text-center p-3 text-muted">Loading data...</div>
        </div>
    </div>

    @php
        // 🔥 DUPLICATE IDs AND BOOTSTRAP-SELECT CLASSES REMOVED
        $formHtml = '
<form class="finePenaltyForm">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="fw-bold">User Type <span class="text-danger">*</span></label>
            <select name="user_type" class="form-select">
                <option value="Employee">Employee</option>
                <option value="Member">Member</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="fw-bold">Company <span class="text-danger">*</span></label>
            <select name="company_id[]" class="form-select select2-dynamic" multiple required></select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="fw-bold">Branch (Optional for HO)</label>
            <select name="branch_id[]" class="form-select select2-dynamic" multiple></select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="fw-bold">Department</label>
            <select name="department_id[]" class="form-select select2-dynamic" multiple></select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="fw-bold">Designation</label>
            <select name="designation_id[]" class="form-select select2-dynamic" multiple></select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="fw-bold">Applicant Name <span class="text-danger">*</span></label>
            <select name="employee_ids[]" class="form-select select2-dynamic" multiple required></select>
        </div>
        
        <hr class="mt-2 mb-3">

        <div class="col-md-12 mb-3 p-2 bg-light border rounded">
            <label class="fw-bold text-danger">Treat As (Action Type) <span class="text-danger">*</span></label>
            <select name="treat_as" class="form-select" required>
                <option value="apply" selected>Apply Penalty/Fine</option>
                <option value="warning">Warning Letter</option>
                <option value="final">Final Warning</option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>Fine in Rupees</label>
            <input type="number" name="fine_rupees" class="form-control" placeholder="₹">
        </div>
        <div class="col-md-6 mb-3">
            <label>Fine Days (Number)</label>
            <input type="number" step="0.5" min="0" name="fine_days" class="form-control" placeholder="e.g. 2.5">
        </div>
        <div class="col-md-6 mb-3">
            <label>Penalty in Rupees</label>
            <input type="number" name="penalty_rupees" class="form-control" placeholder="₹">
        </div>
        <div class="col-md-6 mb-3">
            <label>Penalty Days (Number)</label>
            <input type="number" step="0.5" min="0" name="penalty_days" class="form-control" placeholder="e.g. 1.5">
        </div>

        <div class="col-md-6 mb-3">
            <label class="fw-bold">Date <span class="text-danger">*</span></label>
            <input type="date" name="date" class="form-control" required>
        </div>
        
        <div class="col-md-6 mb-3">
            <label class="fw-bold">Attach Proof (Images Only)</label>
            <input type="file" class="form-control proof_file" accept="image/*" multiple>
            <input type="hidden" name="proof_media_ids" class="proof_media_ids">
            <small class="form-text mt-1 fw-bold proof_status"></small>
            <div class="d-flex flex-wrap mt-2 gap-2 image_previews"></div>
        </div>

        <div class="col-md-12 mb-2">
            <label class="fw-bold">Description / Remark</label>
            <textarea name="description" class="form-control tinymce"></textarea>
        </div>
    </div>
    <button type="submit" class="btn btn-success mt-3 w-100 submitBtn"><i class="fas fa-save"></i> Save Fine/Penalty</button>
</form>';
    @endphp

    <!-- Modals & Offcanvas -->
    <div class="modal fade" id="fineModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add/Edit Fine/Penalty</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">{!! $formHtml !!}</div>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="fineOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Add/Edit Fine/Penalty</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">{!! $formHtml !!}</div>
    </div>

    <div class="modal fade" id="viewFineModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-uppercase"><i class="fas fa-receipt text-secondary"></i>
                        Fine/Penalty Details</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewFineBody">
                    <div class="text-center p-3 text-muted">Loading details...</div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="imageZoomModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-header border-0 pb-0 justify-content-end">
                    <button type="button" class="btn-close btn-close-white fs-4 bg-dark"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="zoomedImage" src="" class="img-fluid rounded shadow-lg" style="max-height: 85vh;">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- 🔥 FIX: REMOVED bootstrap-select JS CDN -->
    <script src="https://cdn.tiny.cloud/1/{{ env('TINYMCE_API_KEY') }}/tinymce/6/tinymce.min.js" referrerpolicy="origin">
    </script>

    <script>
        // 🔥 FOCUS FIX: Allows Select2 to gain focus inside Modals properly without crashing
        $(document).on('select2:open', function(e) {
            window.setTimeout(function() {
                document.querySelector('.select2-container--open .select2-search__field').focus();
            }, 0);
        });

        $(document).ready(function() {
            let date = new Date();
            let y = date.getFullYear();
            let m = String(date.getMonth() + 1).padStart(2, '0');
            let firstDay = `${y}-${m}-01`;
            let lastDayObj = new Date(y, date.getMonth() + 1, 0);
            let lastDay = `${y}-${m}-${String(lastDayObj.getDate()).padStart(2, '0')}`;

            $('#f_start_date').val(firstDay);
            $('#f_end_date').val(lastDay);

            tinymce.init({
                selector: '.tinymce',
                height: 200
            });

            const token = localStorage.getItem('admin_token') || localStorage.getItem('emp_token');
            const contextRole = (localStorage.getItem('role_level') || 'employee').toLowerCase();

            let permissions = {};
            window.currentUserDetails = null;
            window.isGodUI = false;
            window.isCEOorDirector = false;

            let meUrl = localStorage.getItem('admin_token') ? '/api/v1/admin/auth/me' : '/api/v1/employee/auth/me';

            $.ajax({
                url: meUrl,
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(res) {
                    let user = res.data ? res.data : res;
                    window.currentUserDetails = user;
                    let email = user.email || '';
                    let userPerms = user.permissions || [];

                    window.isGodUI = ['admin@jankivilla.com', 'superadmin@example.com',
                            'vedprakash@infoera.in'
                        ].includes(email) || ['developer', 'admin', 'superadmin', 'super_admin']
                        .includes(contextRole);
                    window.isCEOorDirector = user.designation_name && (user.designation_name
                        .toLowerCase().includes('ceo') || user.designation_name.toLowerCase()
                        .includes('director'));

                    permissions = {
                        view: window.isGodUI || userPerms.includes('fine_view'),
                        edit: window.isGodUI || userPerms.includes('fine_edit'),
                        delete: window.isGodUI || userPerms.includes('fine_delete'),
                        print: window.isGodUI || userPerms.includes('fine_print'),
                        export: window.isGodUI || userPerms.includes('fine_export') || userPerms
                            .includes('company_export'),
                        approve: window.isGodUI || userPerms.includes('fine_approve'),
                        reject: window.isGodUI || userPerms.includes('fine_rej'),
                        remark: window.isGodUI || userPerms.includes('fine_remark'),
                        add_direct: window.isGodUI || userPerms.includes('fine_add_direct'),
                        add_request: window.isGodUI || userPerms.includes('fine_add_request')
                    };

                    // 🔥 RBAC Check for Master Checkbox visibility
                    if (permissions.delete) {
                        $('#masterCheckbox').show();
                    } else {
                        $('#masterCheckbox').hide();
                    }

                    if (permissions.add_direct || permissions.add_request) {
                        $('#addBtnDesktop').removeClass('d-none').addClass('d-none d-md-block');
                        $('#addBtnMobile').removeClass('d-none').addClass('d-md-none');
                        if (!permissions.add_direct && permissions.add_request) {
                            $('#addBtnDesktop').html(
                                '<i class="fas fa-paper-plane"></i> Request Fine/Penalty');
                            $('.submitBtn').html('<i class="fas fa-paper-plane"></i> Submit Request');
                        } else {
                            $('#addBtnDesktop').html('<i class="fas fa-plus"></i> Add Fine/Penalty');
                            $('.submitBtn').html('<i class="fas fa-save"></i> Save Fine/Penalty');
                        }
                    }

                    if (permissions.print) $('#btnPrintAll').removeClass('d-none');
                    if (permissions.export) $('#btnExportExcel').removeClass('d-none');

                    // Filter Dropdowns
                    $.get('/api/v1/get-active-companies', function(cRes) {
                        let data = cRes.data ? cRes.data : cRes;
                        let options = '<option value="">All Companies</option>';
                        if (Array.isArray(data)) {
                            data.forEach(c => options +=
                                `<option value="${c.id}">${c.company_name}</option>`);
                        }
                        $('#f_company_id').html(options);
                        if (!window.isGodUI && window.currentUserDetails.company_id) {
                            $('#f_company_id').val(window.currentUserDetails.company_id).prop(
                                'disabled', true);
                        }
                    });

                    $.get('/api/v1/departments', function(dRes) {
                        let data = dRes.data ? dRes.data : dRes;
                        let options = '<option value="">All Departments</option>';
                        let seen = new Set();
                        if (Array.isArray(data)) {
                            data.forEach(d => {
                                let cleanName = d.department_name
                                    .trim(); // Fix duplicates
                                if (!seen.has(cleanName)) {
                                    seen.add(cleanName);
                                    options +=
                                        `<option value="${d.id}">${cleanName}</option>`;
                                }
                            });
                        }
                        $('#f_department_id').html(options);
                    });

                    loadData();
                },
                error: function(err) {
                    $('#fineTable tbody').html(
                        '<tr><td colspan="7" class="text-center text-danger">Authentication Failed. Please reload.</td></tr>'
                    );
                }
            });

            // Form Reset Event
            $('select[name="user_type"]').on('change', function() {
                let form = $(this).closest('form');
                form.find(
                    'select[name="company_id[]"], select[name="branch_id[]"], select[name="department_id[]"], select[name="designation_id[]"], select[name="employee_ids[]"]'
                ).val(null).trigger('change');
            });

            // 1. DYNAMIC COMPANY
            $('select[name="company_id[]"]').each(function() {
                let parentUI = $(this).closest('.modal-content, .offcanvas-body'); // Dynamic Scope
                $(this).select2({
                    width: '100%',
                    placeholder: "Type 2 letters...",
                    minimumInputLength: 2,
                    multiple: true,
                    dropdownParent: parentUI.length ? parentUI : $(document.body),
                    ajax: {
                        url: '/api/v1/companies/search-dynamic',
                        headers: {
                            'Authorization': 'Bearer ' + token
                        },
                        delay: 400,
                        data: function(params) {
                            return {
                                q: params.term
                            };
                        },
                        processResults: function(res) {
                            return {
                                results: $.map(res.data, function(item) {
                                    return {
                                        text: item.company_name + ' (' + item
                                            .company_code + ')',
                                        id: item.id
                                    }
                                })
                            };
                        }
                    }
                }).on('change', function() {
                    let form = $(this).closest('form');
                    form.find(
                        'select[name="branch_id[]"], select[name="department_id[]"], select[name="designation_id[]"], select[name="employee_ids[]"]'
                    ).val(null).trigger('change');

                    let selectedCompanies = $(this).select2('data');
                    let branchDropdown = form.find('select[name="branch_id[]"]');
                    branchDropdown.empty();
                    selectedCompanies.forEach(function(comp) {
                        if (comp.id) {
                            branchDropdown.append(new Option("Head Office (" + comp.text +
                                ")", "HO_" + comp.id, false, false));
                        }
                    });
                });
            });

            // 2. DYNAMIC BRANCH
            $('select[name="branch_id[]"]').each(function() {
                let parentUI = $(this).closest('.modal-content, .offcanvas-body');
                $(this).select2({
                    width: '100%',
                    placeholder: "Type 3 letters...",
                    minimumInputLength: 3,
                    multiple: true,
                    dropdownParent: parentUI.length ? parentUI : $(document.body),
                    ajax: {
                        url: '/api/v1/branches/search-dynamic',
                        headers: {
                            'Authorization': 'Bearer ' + token
                        },
                        delay: 400,
                        data: function(params) {
                            let cIds = $(this).closest('form').find(
                                'select[name="company_id[]"]').val();
                            return {
                                q: params.term,
                                company_id: cIds ? cIds.join(',') : ''
                            };
                        }.bind(this),
                        processResults: function(res) {
                            return {
                                results: $.map(res.data, function(item) {
                                    return {
                                        text: item.branch_name,
                                        id: item.id
                                    }
                                })
                            };
                        }
                    }
                }).on('change', function() {
                    $(this).closest('form').find(
                        'select[name="department_id[]"], select[name="designation_id[]"], select[name="employee_ids[]"]'
                    ).val(null).trigger('change');
                });
            });

            // 3. DYNAMIC DEPARTMENT
            $('select[name="department_id[]"]').each(function() {
                let parentUI = $(this).closest('.modal-content, .offcanvas-body');
                $(this).select2({
                    width: '100%',
                    placeholder: "Type 3 letters...",
                    minimumInputLength: 3,
                    multiple: true,
                    dropdownParent: parentUI.length ? parentUI : $(document.body),
                    ajax: {
                        url: '/api/v1/departments/search-dynamic',
                        headers: {
                            'Authorization': 'Bearer ' + token
                        },
                        delay: 400,
                        data: function(params) {
                            let form = $(this).closest('form');
                            let cIds = form.find('select[name="company_id[]"]').val();
                            let bIds = form.find('select[name="branch_id[]"]').val();
                            return {
                                q: params.term,
                                company_id: cIds ? cIds.join(',') : '',
                                branch_id: bIds ? bIds.join(',') : '',
                                type: form.find('select[name="user_type"]').val().toLowerCase()
                            };
                        }.bind(this),
                        processResults: function(res) {
                            return {
                                results: $.map(res.data, function(item) {
                                    return {
                                        text: item.department_name,
                                        id: item.id
                                    }
                                })
                            };
                        }
                    }
                }).on('change', function() {
                    $(this).closest('form').find(
                        'select[name="designation_id[]"], select[name="employee_ids[]"]').val(
                        null).trigger('change');
                });
            });

            // 4. DYNAMIC DESIGNATION
            $('select[name="designation_id[]"]').each(function() {
                let parentUI = $(this).closest('.modal-content, .offcanvas-body');
                $(this).select2({
                    width: '100%',
                    placeholder: "Type 3 letters...",
                    minimumInputLength: 3,
                    multiple: true,
                    dropdownParent: parentUI.length ? parentUI : $(document.body),
                    ajax: {
                        url: '/api/v1/designations/search-dynamic',
                        headers: {
                            'Authorization': 'Bearer ' + token
                        },
                        delay: 400,
                        data: function(params) {
                            let form = $(this).closest('form');
                            let deptIds = form.find('select[name="department_id[]"]').val();
                            let cIds = form.find('select[name="company_id[]"]').val();
                            let bIds = form.find('select[name="branch_id[]"]').val();
                            return {
                                q: params.term,
                                department_id: deptIds ? deptIds.join(',') : '',
                                company_id: cIds ? cIds.join(',') : '',
                                branch_id: bIds ? bIds.join(',') : ''
                            };
                        }.bind(this),
                        processResults: function(res) {
                            return {
                                results: $.map(res.data, function(item) {
                                    return {
                                        text: item.designation_name,
                                        id: item.id
                                    }
                                })
                            };
                        }
                    }
                }).on('change', function() {
                    $(this).closest('form').find('select[name="employee_ids[]"]').val(null).trigger(
                        'change');
                });
            });

          
                   // 5. DYNAMIC EMPLOYEE/MEMBER (Applicant)
            $('select[name="employee_ids[]"]').each(function() {
                let parentUI = $(this).closest('.modal-content, .offcanvas-body');
                let form = $(this).closest('form');

                $(this).select2({
                    width: '100%',
                    placeholder: "Type 3 letters...",
                    minimumInputLength: 3,
                    multiple: true,
                    dropdownParent: parentUI.length ? parentUI : $(document.body),
                    ajax: {
                        // 🔥 NAYA: Transport use karke GET/POST ka issue handle kar diya
                        transport: function (params, success, failure) {
                            let isMember = form.find('select[name="user_type"]').val() === 'Member';
                            
                            // Member ke liye purana GET, Employee ke liye apne Controller ka POST
                            let url = isMember ? '/api/v1/members/search-dynamic' : '/api/v1/get-filtered-employees';
                            let method = isMember ? 'GET' : 'POST';

                            let cIds = form.find('select[name="company_id[]"]').val();
                            let bIds = form.find('select[name="branch_id[]"]').val();
                            let deptIds = form.find('select[name="department_id[]"]').val();
                            let desigIds = form.find('select[name="designation_id[]"]').val();

                            let requestData = {
                                q: params.data.q || '',
                                company_ids: cIds ? cIds.join(',') : '',
                                branch_ids: bIds ? bIds.join(',') : '',
                                department_ids: deptIds ? deptIds.join(',') : '',
                                designation_ids: desigIds ? desigIds.join(',') : ''
                            };

                            let $request = $.ajax({
                                url: url,
                                type: method,
                                data: requestData,
                                headers: { 'Authorization': 'Bearer ' + token }
                            });

                            $request.then(success);
                            $request.fail(failure);
                            return $request;
                        },
                        data: function(params) {
                            return { q: params.term };
                        },
                        processResults: function(res) {
                            let items = res.data ? res.data : res;
                            return {
                                results: $.map(items, function(item) {
                                    let name = item.full_name || item.member_name || item.name;
                                    let mId = item.emp_id || item.member_id;
                                    return {
                                        text: name + ' (' + mId + ')',
                                        id: item.id
                                    }
                                })
                            };
                        }
                    }
                });
            });

            // 🟢 AUTO LOCK FORM LOGIC 
            function resetForm() {
                $('.finePenaltyForm').each(function() {
                    $(this)[0].reset();
                });
                if (tinymce.get("descriptionEditor")) tinymce.get("descriptionEditor").setContent('');

                $('select[name="user_type"], select[name="company_id[]"], select[name="branch_id[]"], select[name="department_id[]"], select[name="designation_id[]"], select[name="employee_ids[]"]')
                    .prop('disabled', false);
                $('.select2-dynamic').empty().trigger('change');

                let currentPortalStr = window.location.pathname.startsWith('/customer') ? 'Member' : 'Employee';

                if (!window.isGodUI && !window.isCEOorDirector) {
                    $('select[name="user_type"]').val(currentPortalStr).prop('disabled', true);

                    if (window.currentUserDetails && window.currentUserDetails.company_id) {
                        let cName = window.currentUserDetails.company ? window.currentUserDetails.company
                            .company_name : "Company";
                        $('select[name="company_id[]"]').empty().append(new Option(cName, window.currentUserDetails
                            .company_id, true, true)).trigger('change').prop('disabled', true);

                        if (!window.currentUserDetails.branch_id) {
                            $('select[name="branch_id[]"]').empty().append(new Option("Head Office (" + cName + ")",
                                    "HO_" + window.currentUserDetails.company_id, true, true)).trigger('change')
                                .prop('disabled', true);
                        } else {
                            let bName = window.currentUserDetails.branch ? window.currentUserDetails.branch
                                .branch_name : "Branch";
                            $('select[name="branch_id[]"]').empty().append(new Option(bName, window
                                .currentUserDetails.branch_id, true, true)).trigger('change').prop('disabled',
                                true);
                        }
                    }
                }

                uploadedMediaIds = [];
                $('.proof_media_ids').val('');
                $('.image_previews').empty();
                $('.proof_status').text('');
                editId = null;
            }

            $('#addBtnDesktop, #addBtnMobile').click(function() {
                resetForm();
            });

            // IMAGE UPLOAD LOGIC
            let uploadedMediaIds = [];
            $('.proof_file').on('change', async function() {
                let files = this.files;
                if (files.length === 0) return;
                let form = $(this).closest('form');

                form.find('.submitBtn').prop('disabled', true);
                form.find('.proof_status').text('Uploading files...').removeClass(
                        'text-danger text-success')
                    .addClass('text-info');

                for (let i = 0; i < files.length; i++) {
                    let file = files[i];
                    let formData = new FormData();
                    formData.append('file', file);

                    try {
                        let res = await $.ajax({
                            url: '/api/v1/media/upload',
                            method: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            headers: {
                                'Authorization': 'Bearer ' + token
                            }
                        });

                        if (res.status === 'success') {
                            let mediaId = res.data.id;
                            let fileUrl = '/' + res.data.file_path;
                            uploadedMediaIds.push(mediaId);

                            form.find('.image_previews').append(`
                            <div class="position-relative preview-box border rounded p-1" data-id="${mediaId}" style="width: 80px; height: 80px;">
                                <img src="${fileUrl}" class="img-fluid rounded" style="width: 100%; height: 100%; object-fit: cover;">
                                <button type="button" class="btn btn-danger btn-sm position-absolute remove-media-btn" style="top: -5px; right: -5px; padding: 0 5px; font-size: 12px; border-radius: 50%;">&times;</button>
                            </div>
                        `);
                        }
                    } catch (err) {}
                }

                form.find('.proof_media_ids').val(uploadedMediaIds.join(','));
                form.find('.proof_status').text('All Uploads Complete').removeClass(
                        'text-info text-danger')
                    .addClass('text-success');
                form.find('.submitBtn').prop('disabled', false);
                $(this).val('');
            });

            $(document).on('click', '.remove-media-btn', function() {
                let box = $(this).closest('.preview-box');
                let idToRemove = box.data('id');
                let form = $(this).closest('form');
                uploadedMediaIds = uploadedMediaIds.filter(id => id != idToRemove);
                form.find('.proof_media_ids').val(uploadedMediaIds.join(','));
                box.fadeOut(300, function() {
                    $(this).remove();
                });
            });

            // 🟢 DATA LOAD & AGGREGATE SUMMARY LOGIC
            let globalDataArray = [];
            window.summaryDataList = [];
            window.summaryCurrentIndex = 0;

            function loadData() {
                let filterPayload = {
                    search: $('#f_search').val(),
                    start_date: $('#f_start_date').val(),
                    end_date: $('#f_end_date').val(),
                    company_id: $('#f_company_id').val(),
                    department_id: $('#f_department_id').val(),
                };

                $.ajax({
                    url: '/api/v1/fine-penalties',
                    method: 'GET',
                    data: filterPayload,
                    headers: {
                        'Authorization': 'Bearer ' + token
                    },
                    success: function(res) {
                        let dataArray = res.data ? res.data : res;
                        if (!Array.isArray(dataArray)) dataArray = [];
                        globalDataArray = dataArray;

                        if (dataArray.length === 0) {
                            $('#fineTable tbody').html(
                                '<tr><td colspan="7" class="text-center text-muted">No records found.</td></tr>'
                            );
                            $('#mobileCardsContainer').html(
                                '<div class="text-center p-4 text-muted bg-white rounded shadow-sm">No records found.</div>'
                            );
                            $('#summaryContainer').addClass('d-none');
                            $('#summaryDetails').empty();
                            return;
                        }

                        let tbody = '';
                        let cards = '';
                        let grandTotalRupees = 0;
                        let summaryMap = {};

                        dataArray.forEach((item) => {
                            let empNameDisplay = item.employee ? (item.employee.full_name ||
                                item.employee.member_name) : 'N/A';
                            let recordNo = 'FP-' + String(item.id).padStart(5, '0');
                            let empName = item.employee ?
                                `${empNameDisplay} <br><small class="text-muted">(${item.employee.member_id})</small>` :
                                'N/A';
                            let fineText = item.fine_rupees ? `₹${item.fine_rupees}` : (item
                                .fine_days ? item.fine_days + ' Days' : '-');
                            let penaltyText = item.penalty_rupees ? `₹${item.penalty_rupees}` :
                                (item.penalty_days ? item.penalty_days + ' Days' : '-');

                            let actionLabel = item.treat_as ?
                                `<br><span class="badge bg-secondary" style="font-size:10px;">${item.treat_as.toUpperCase()}</span>` :
                                '';
                            let statusBadge = item.status === 'Approved' ?
                                '<span class="badge bg-success">Approved</span>' : (item
                                    .status === 'Rejected' ?
                                    '<span class="badge bg-danger">Rejected</span>' :
                                    '<span class="badge bg-warning text-dark">Pending</span>');

                            let portalPrefix = window.location.pathname.startsWith(
                                '/employee') ? 'employee' : (window.location.pathname
                                .startsWith('/customer') ? 'customer' : 'admin');
                            let actions = '<div class="btn-group btn-group-sm">';
                            if (permissions.view) actions +=
                                `<button class="btn btn-info btn-view" data-id="${item.id}" title="View"><i class="fas fa-eye text-white"></i></button>`;
                            if (permissions.print) actions +=
                                `<button class="btn btn-secondary btn-print-doc" data-id="${item.id}" data-portal="${portalPrefix}" title="Print"><i class="fas fa-print"></i></button>`;
                            if (permissions.edit) actions +=
                                `<button class="btn btn-primary btn-edit" data-id="${item.id}" title="Edit"><i class="fas fa-edit"></i></button>`;
                            if (item.status === 'Pending') {
                                if (permissions.approve) actions +=
                                    `<button class="btn btn-success btn-approve" data-id="${item.id}" title="Approve"><i class="fas fa-check"></i></button>`;
                                if (permissions.reject) actions +=
                                    `<button class="btn btn-danger btn-reject" data-id="${item.id}" title="Reject"><i class="fas fa-times"></i></button>`;
                            }
                            if (permissions.delete) actions +=
                                `<button class="btn btn-dark btn-delete" data-id="${item.id}" title="Delete"><i class="fas fa-trash"></i></button>`;
                            actions += '</div>';

                            let baseFine = parseFloat(item.fine_rupees) || 0;
                            let basePenalty = parseFloat(item.penalty_rupees) || 0;
                            let fineDaysAmt = 0;
                            let penaltyDaysAmt = 0;

                            if ((item.user_type === 'Employee' || !item.user_type) && item
                                .employee && item.employee.payable_salary) {
                                let perDay = parseFloat(item.employee.payable_salary) / 30;
                                fineDaysAmt = (parseFloat(item.fine_days) || 0) * perDay;
                                penaltyDaysAmt = (parseFloat(item.penalty_days) || 0) * perDay;
                            }

                            let rowTotal = baseFine + basePenalty + fineDaysAmt +
                                penaltyDaysAmt;
                            grandTotalRupees += rowTotal;

                            if (rowTotal > 0) {
                                let displayId = item.employee ? item.employee.member_id : 'N/A';
                                let key =
                                    `${empNameDisplay} (${displayId}) - ${item.user_type || 'Employee'}`;
                                if (!summaryMap[key]) summaryMap[key] = 0;
                                summaryMap[key] += rowTotal;
                            }

                          // 🔥 FIX: Permission ke hisaab se Checkbox dikhana aur Mobile me add karna
                            let checkboxHtml = permissions.delete ? `<input type="checkbox" class="row-checkbox form-check-input" value="${item.id}">` : '-';
                            let mobileCheckboxHtml = permissions.delete ? `<input type="checkbox" class="row-checkbox form-check-input me-2" value="${item.id}">` : '';

                            // Desktop
                            tbody += `<tr>
                            <td class="text-center">${checkboxHtml}</td>
                            <td>${item.date} ${actionLabel}</td>
                            <td>${empName}</td>
                            <td>${fineText}</td>
                            <td>${penaltyText}</td>
                            <td>${statusBadge}</td>
                            <td>${actions}</td>
                        </tr>`;

                            // Mobile
                            cards += `
                        <div class="card mb-3 shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                    <div class="d-flex align-items-center">
                                        ${mobileCheckboxHtml}
                                        <strong><i class="fas fa-calendar-alt text-muted"></i> ${item.date}</strong>
                                    </div>
                                    ${statusBadge}
                                </div>
                                <div class="d-flex justify-content-between small mb-2">
                                    <span class="text-muted">Record No:</span> <span class="fw-bold">${recordNo}</span>
                                </div>
                                <div class="d-flex justify-content-between small mb-2">
                                    <span class="text-muted">Employee:</span> <span class="fw-bold text-end">${empNameDisplay} <br><small>(${item.employee ? item.employee.member_id : ''})</small></span>
                                </div>
                                <div class="d-flex justify-content-between small mb-2">
                                    <span class="text-muted">Fine:</span> <span class="text-danger fw-bold">${fineText}</span>
                                </div>
                                <div class="d-flex justify-content-between small mb-3">
                                    <span class="text-muted">Penalty:</span> <span class="text-danger fw-bold">${penaltyText}</span>
                                </div>
                                <div class="d-flex justify-content-end">${actions}</div>
                            </div>
                        </div>`;
                        });

                        $('#fineTable tbody').html(tbody);
                        $('#mobileCardsContainer').html(cards);

                        window.summaryDataList = [];
                        for (let key in summaryMap) {
                            window.summaryDataList.push({
                                name: key,
                                amount: summaryMap[key]
                            });
                        }

                        if (window.summaryDataList.length > 0 && grandTotalRupees > 0) {
                            $('#summaryContainer').removeClass('d-none');
                            $('#grandTotalUI').text('₹' + grandTotalRupees.toFixed(2));

                            $('#summaryDetails').empty();
                            $('#summaryDetailsWrapper').addClass('d-none');
                            $('#toggleSummaryBtn').html(
                                '<i class="fas fa-chevron-down"></i> Expand Details');
                            window.summaryCurrentIndex = 0;

                            renderSummaryChunk();
                        } else {
                            $('#summaryContainer').addClass('d-none');
                        }
                    }
                });
            }

            function renderSummaryChunk() {
                let chunkSize = 20;
                let html = '';
                let end = Math.min(window.summaryCurrentIndex + chunkSize, window.summaryDataList.length);

                for (let i = window.summaryCurrentIndex; i < end; i++) {
                    let item = window.summaryDataList[i];
                    html += `<div class="d-flex justify-content-between small text-dark mb-1 border-bottom pb-1">
                    <span><i class="fas fa-user-circle text-muted me-1"></i> ${item.name}</span> 
                    <span class="fw-medium">₹${item.amount.toFixed(2)}</span>
                </div>`;
                }

                $('#summaryDetails').append(html);
                window.summaryCurrentIndex = end;

                if (window.summaryCurrentIndex < window.summaryDataList.length) {
                    $('#loadMoreSummaryBtn').removeClass('d-none');
                } else {
                    $('#loadMoreSummaryBtn').addClass('d-none');
                }
            }

            $('#toggleSummaryBtn').click(function() {
                let wrapper = $('#summaryDetailsWrapper');
                if (wrapper.hasClass('d-none')) {
                    wrapper.removeClass('d-none');
                    $(this).html('<i class="fas fa-chevron-up"></i> Collapse Details');
                } else {
                    wrapper.addClass('d-none');
                    $(this).html('<i class="fas fa-chevron-down"></i> Expand Details');
                }
            });

            $('#loadMoreSummaryBtn').click(function() {
                renderSummaryChunk();
            });

            $('#btnFilter').click(function() {
                loadData();
            });
            $('#f_search').on('keyup', function() {
                loadData();
            });

            $('#btnPrintAll').click(function() {
                let portalPrefix = window.location.pathname.startsWith('/employee') ? 'employee' : 'admin';
                let qs = $.param({
                    search: $('#f_search').val(),
                    start_date: $('#f_start_date').val(),
                    end_date: $('#f_end_date').val(),
                    company_id: $('#f_company_id').val(),
                    department_id: $('#f_department_id').val()
                });
                window.open(`/${portalPrefix}/fine-penalties/print-all?` + qs, '_blank');
            });

            $('#btnExportExcel').click(function() {
                let csvContent =
                    "data:text/csv;charset=utf-8,Date,Employee Name,Employee ID,Designation,Action Type,Fine Amount/Days,Penalty Amount/Days,Status\n";
                globalDataArray.forEach(function(item) {
                    let empName = item.employee ? (item.employee.full_name || item.employee
                        .member_name) : 'N/A';
                    let empId = item.employee ? item.employee.member_id : 'N/A';
                    let desig = item.employee && item.employee.designation ? item.employee
                        .designation.designation_name : 'N/A';
                    let actionType = item.treat_as || 'Apply';
                    let fineText = item.fine_rupees ? item.fine_rupees : (item.fine_days ? item
                        .fine_days + ' Days' : '-');
                    let penText = item.penalty_rupees ? item.penalty_rupees : (item.penalty_days ?
                        item.penalty_days + ' Days' : '-');
                    csvContent +=
                        `"${item.date}","${empName}","${empId}","${desig}","${actionType}","${fineText}","${penText}","${item.status}"\n`;
                });
                let encodedUri = encodeURI(csvContent);
                let link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "Fine_Penalty_Report.csv");
                document.body.appendChild(link);
                link.click();
            });

            $(document).on('click', '.btn-approve', function() {
                if (!confirm('Approve this record?')) return;
                $.post(`/api/v1/fine-penalties/${$(this).data('id')}/approve`, {}, function(res) {
                    alert(res.message);
                    loadData();
                });
            });

            $(document).on('click', '.btn-reject', function() {
                if (!confirm('Reject this record?')) return;
                $.post(`/api/v1/fine-penalties/${$(this).data('id')}/reject`, {}, function(res) {
                    alert(res.message);
                    loadData();
                });
            });

            $(document).on('click', '.btn-delete', function() {
                if (!confirm('Delete this record forever?')) return;
                $.post('/api/v1/fine-penalties/bulk-delete', {
                    ids: [$(this).data('id')]
                }, function(res) {
                    alert('Deleted Successfully');
                    loadData();
                });
            });

            $(document).on('click', '.btn-remark', function() {
                let remark = prompt("Enter remark:");
                if (remark) {
                    $.post(`/api/v1/fine-penalties/${$(this).data('id')}/remark`, {
                        description: remark
                    }, function(res) {
                        alert('Remark saved!');
                        loadData();
                    });
                }
            });

            // EDIT 
            let editId = null;
            $(document).on('click', '.btn-edit', function() {
                editId = $(this).data('id');
                let parentForm = $(window).width() < 768 ? $('#fineOffcanvas form') : $('#fineModal form');

                $.get(`/api/v1/fine-penalties/${editId}`, function(res) {

                    parentForm.find('select[name="user_type"]').val(res.user_type || 'Employee')
                        .prop('disabled', true);

                    let compOpt = new Option(res.company.company_name, res.company_id, true, true);
                    parentForm.find('select[name="company_id[]"]').empty().append(compOpt).trigger(
                        'change').prop('disabled', true);

                    if (res.branch_id && res.employee && res.employee.branch) {
                        let brOpt = new Option(res.employee.branch.branch_name, res.branch_id, true,
                            true);
                        parentForm.find('select[name="branch_id[]"]').empty().append(brOpt).trigger(
                            'change').prop('disabled', true);
                    } else {
                        let hoOpt = new Option("Head Office (" + res.company.company_name + ")",
                            "HO_" + res.company_id, true, true);
                        parentForm.find('select[name="branch_id[]"]').empty().append(hoOpt).trigger(
                            'change').prop('disabled', true);
                    }

                    if (res.department_id && res.employee && res.employee.department) {
                        let deptOpt = new Option(res.employee.department.department_name, res
                            .department_id, true, true);
                        parentForm.find('select[name="department_id[]"]').empty().append(deptOpt)
                            .trigger('change').prop('disabled', true);
                    }

                    if (res.designation_id && res.employee && res.employee.designation) {
                        let desigOpt = new Option(res.employee.designation.designation_name, res
                            .designation_id, true, true);
                        parentForm.find('select[name="designation_id[]"]').empty().append(desigOpt)
                            .trigger('change').prop('disabled', true);
                    }

                    let empName = (res.employee.full_name || res.employee.member_name || 'N/A') +
                        ' (' + res.employee.member_id + ')';
                    let empOpt = new Option(empName, res.employee_id, true, true);
                    parentForm.find('select[name="employee_ids[]"]').empty().append(empOpt).trigger(
                        'change').prop('disabled', true);

                    parentForm.find('input[name="fine_rupees"]').val(res.fine_rupees);
                    parentForm.find('input[name="fine_days"]').val(res.fine_days);
                    parentForm.find('input[name="penalty_rupees"]').val(res.penalty_rupees);
                    parentForm.find('input[name="penalty_days"]').val(res.penalty_days);
                    parentForm.find('input[name="date"]').val(res.date);
                    parentForm.find('select[name="treat_as"]').val(res.treat_as || 'apply');

                    if (res.description) tinymce.get("descriptionEditor").setContent(res
                        .description);

                    parentForm.find('.image_previews').empty();
                    uploadedMediaIds = [];
                    if (res.proof_media_list && res.proof_media_list.length > 0) {
                        res.proof_media_list.forEach(media => {
                            uploadedMediaIds.push(media.id);
                            parentForm.find('.image_previews').append(`
                            <div class="position-relative preview-box border rounded p-1" data-id="${media.id}" style="width: 80px; height: 80px;">
                                <img src="/${media.file_path}" class="img-fluid rounded" style="width: 100%; height: 100%; object-fit: cover;">
                                <button type="button" class="btn btn-danger btn-sm position-absolute remove-media-btn" style="top: -5px; right: -5px; padding: 0 5px; font-size: 12px; border-radius: 50%;">&times;</button>
                            </div>
                        `);
                        });
                        parentForm.find('.proof_media_ids').val(uploadedMediaIds.join(','));
                    }

                    if ($(window).width() < 768) $('#fineOffcanvas').offcanvas('show');
                    else $('#fineModal').modal('show');
                });
            });

            $('.finePenaltyForm').off('submit').on('submit', function(e) {
                e.preventDefault();
                tinymce.triggerSave();
                let form = $(this);
                let formData = form.serialize();

                if (form.find('select[name="company_id[]"]').prop('disabled')) formData += '&company_id=' +
                    form.find('select[name="company_id[]"]').val();
                if (form.find('select[name="user_type"]').prop('disabled')) formData += '&user_type=' + form
                    .find('select[name="user_type"]').val();

                let url = editId ? `/api/v1/fine-penalties/${editId}` : '/api/v1/fine-penalties';
                let method = editId ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    method: method,
                    data: formData,
                    headers: {
                        'Authorization': 'Bearer ' + token
                    },
                    success: function(res) {
                        $('#fineModal').modal('hide');
                        $('#fineOffcanvas').offcanvas('hide');
                        resetForm();
                        loadData();
                        alert(res.message || 'Saved successfully!');
                    }
                });
            });

            $('#fineModal, #fineOffcanvas').on('hidden.bs.modal hidden.bs.offcanvas', function() {
                resetForm();
            });

            $('#masterCheckbox').click(function() {
                $('.row-checkbox').prop('checked', $(this).prop('checked')).trigger('change');
            });
            $(document).on('change', '.row-checkbox', function() {
                if ($('.row-checkbox:checked').length > 0) $('#bulkActionContainer').removeClass('d-none');
                else $('#bulkActionContainer').addClass('d-none');
            });
            $('#selectAllBtn').click(function() {
                $('.row-checkbox').prop('checked', true).trigger('change');
            });
            $('#deleteSelectedBtn').click(function() {
                let ids = [];
                $('.row-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });
                if (confirm('Are you sure you want to delete selected records Permanently?')) {
                    $.post('/api/v1/fine-penalties/bulk-delete', {
                        ids: ids
                    }, function() {
                        loadData();
                        $('#bulkActionContainer').addClass('d-none');
                        $('#masterCheckbox').prop('checked', false);
                    });
                }
            });


            $(document).on('click', '.btn-view', function() {
                openViewModal($(this).data('id'));
            });

            function openViewModal(id) {
                $('#viewFineBody').html(
                    '<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
                $('#viewFineModal').modal('show');

                $.get(`/api/v1/fine-penalties/${id}`, function(res) {
                    let statusColor = res.status === 'Approved' ? 'green' : (res.status === 'Rejected' ?
                        'red' : 'orange');

                    let proofHtml = '';
                    if (res.proof_media_list && res.proof_media_list.length > 0) {
                        let imgTags = res.proof_media_list.map(media =>
                            `<img src="/${media.file_path}" class="img-thumbnail cursor-pointer view-zoom-img m-1" style="max-height: 120px; cursor: pointer;" title="Click to Zoom">`
                        ).join('');

                        proofHtml = `
                        <div class="mt-4 border p-2 rounded text-center bg-light">
                            <p class="fw-bold mb-2">Attached Proof(s)</p>
                            <div class="d-flex flex-wrap justify-content-center">${imgTags}</div>
                            <p class="small text-muted mt-1"><i class="fas fa-search-plus"></i> Click image to zoom</p>
                        </div>
                    `;
                    }

                    let baseFine = parseFloat(res.fine_rupees) || 0;
                    let basePenalty = parseFloat(res.penalty_rupees) || 0;
                    let fineDaysAmt = 0;
                    let penaltyDaysAmt = 0;

                    if ((res.user_type === 'Employee' || !res.user_type) && res.employee && res.employee
                        .payable_salary) {
                        let perDay = parseFloat(res.employee.payable_salary) / 30;
                        fineDaysAmt = (parseFloat(res.fine_days) || 0) * perDay;
                        penaltyDaysAmt = (parseFloat(res.penalty_days) || 0) * perDay;
                    }

                    let totalAmount = baseFine + basePenalty + fineDaysAmt + penaltyDaysAmt;
                    let totalDays = (parseFloat(res.fine_days) || 0) + (parseFloat(res.penalty_days) || 0);

                    let hasFine = (baseFine > 0 || (parseFloat(res.fine_days) || 0) > 0);
                    let hasPenalty = (basePenalty > 0 || (parseFloat(res.penalty_days) || 0) > 0);
                    let noticeTitle = (hasFine && hasPenalty) ? "FINE & PENALTY NOTICE" : (hasPenalty ?
                        "PENALTY NOTICE" : "FINE NOTICE");

                    let designationName = (res.employee && res.employee.designation) ? res.employee
                        .designation.designation_name : 'N/A';

                 // --- NAYA: Treat As Badge Logic ---
                    let treatAsText = 'Applied';
                    let treatAsColor = 'success';
                    if (res.treat_as === 'warning') {
                        treatAsText = 'Warning';
                        treatAsColor = 'warning text-dark';
                    } else if (res.treat_as === 'final') {
                        treatAsText = 'Final Warning';
                        treatAsColor = 'danger';
                    }
                    let treatAsBadge = `<span class="badge bg-${treatAsColor}">${treatAsText}</span>`;

                    let viewHtml = `
                        <div class="border p-3 rounded bg-white">
                            <div class="mb-4 pb-2 border-bottom text-center">
                                ${res.header_html ? res.header_html : ''}
                            </div>
                            
                            <div class="text-center mt-3 mb-4">
                                <h5 class="text-uppercase text-decoration-underline mb-1">${noticeTitle}</h5>
                            </div>
                            
                            <div class="row mb-3 bg-light p-2 rounded mx-0">
                                <div class="col-sm-6 mb-2 mb-sm-0">
                                    <strong>Employee Name:</strong> ${res.employee ? (res.employee.full_name || res.employee.member_name) : 'N/A'}<br>
                                    <strong>Employee ID:</strong> ${res.employee ? res.employee.member_id : 'N/A'}<br>
                                    <strong>Department:</strong> ${res.employee && res.employee.department ? res.employee.department.department_name : 'N/A'}<br>
                                    <strong>Designation:</strong> ${designationName}
                                </div>
                                <div class="col-sm-6 text-sm-end">
                                    <strong>Record No:</strong> FP-${String(res.id).padStart(5, '0')}<br>
                                    <strong>Date of Issue:</strong> ${res.date}<br>
                                    <strong>Action:</strong> ${treatAsBadge}<br>
                                    <strong>Status:</strong> <span style="color: ${statusColor}; font-weight: bold;">${res.status.toUpperCase()}</span>
                                </div>
                            </div>

                        <div class="fw-bold bg-light p-2 border border-bottom-0 rounded-top">Details of Charges</div>
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr><th>Charge Type</th><th>Amount (₹)</th><th>Deduction Days</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Fine</strong></td>
                                    <td class="text-danger">${res.fine_rupees ? '₹' + res.fine_rupees : '-'}</td>
                                    <td class="text-danger">
                                        ${res.fine_days ? res.fine_days + ' Days' : '-'}
                                        ${fineDaysAmt > 0 ? `<br><small class="text-muted">(₹${fineDaysAmt.toFixed(2)})</small>` : ''}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Penalty</strong></td>
                                    <td class="text-danger">${res.penalty_rupees ? '₹' + res.penalty_rupees : '-'}</td>
                                    <td class="text-danger">
                                        ${res.penalty_days ? res.penalty_days + ' Days' : '-'}
                                        ${penaltyDaysAmt > 0 ? `<br><small class="text-muted">(₹${penaltyDaysAmt.toFixed(2)})</small>` : ''}
                                    </td>
                                </tr>
                                <tr class="table-secondary">
                                    <td class="text-end align-middle"><strong>Grand Total:</strong></td>
                                    <td colspan="2" class="text-center fw-bold fs-5 text-danger">
                                        ₹${totalAmount.toFixed(2)}
                                        ${totalDays > 0 ? `<div class="fs-6 text-dark fw-normal mt-1">Total Deduction: ${totalDays} Days</div>` : ''}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        ${res.description ? `<div class="mt-4"><div class="fw-bold bg-light p-2 border border-bottom-0 rounded-top">Remarks / Description</div><div class="p-3 border rounded-bottom">${res.description}</div></div>` : ''}
                        ${proofHtml}
                    </div>
                `;
                    $('#viewFineBody').html(viewHtml);
                }).fail(function() {
                    $('#viewFineBody').html(
                        '<div class="text-center text-danger p-3">Failed to load data.</div>');
                });
            }

            $(document).on('click', '.view-zoom-img', function() {
                $('#zoomedImage').attr('src', $(this).attr('src'));
                $('#imageZoomModal').modal('show');
            });

            let urlParams = new URLSearchParams(window.location.search);
            let viewId = urlParams.get('view_id');
            if (viewId) {
                setTimeout(function() {
                    openViewModal(viewId);
                    window.history.replaceState({}, document.title, window.location.pathname);
                }, 600);
            }
        });

        $(document).on('click', '.btn-print-doc', function() {
            let id = $(this).data('id');
            let portal = $(this).data('portal') || 'employee';
            Swal.fire({
                title: 'Print Options',
                text: "Do you want to print the attached proof images?",
                icon: 'question',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: 'Yes, with Images',
                denyButtonText: 'No, Hide Images',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.open(`/${portal}/fine-penalties/print/${id}?with_proof=1`, '_blank');
                } else if (result.isDenied) {
                    window.open(`/${portal}/fine-penalties/print/${id}?with_proof=0`, '_blank');
                }
            });
        });
    </script>
@endpush
