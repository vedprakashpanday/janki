@extends('layout.app')

@section('content')
    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">
                    <i class="fas fa-address-card text-success me-2"></i> Visiting Cards Printing Panel
                </h4>
                <p class="text-secondary small mb-0">Generate corporate normal and premium business cards</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-4 col-lg-2">
                        <label class="form-label small fw-bold">Company</label>
                        <select id="filter_company" class="form-select bg-light">
                            <option value="">-- Select --</option>
                        </select>
                    </div>

                    <div class="col-md-4 col-lg-2">
                        <label class="form-label small fw-bold">Branch</label>
                        <select id="filter_branch" class="form-select bg-light" disabled>
                            <option value="">-- Select --</option>
                        </select>
                    </div>

                    <div class="col-md-4 col-lg-2">
                        <label class="form-label small fw-bold">Department</label>
                        <select id="filter_department" class="form-select bg-light" disabled>
                            <option value="">-- Select --</option>
                        </select>
                    </div>

                    <div class="col-md-4 col-lg-3">
                        <label class="form-label small fw-bold">Designation</label>
                        <select id="filter_designation" class="form-select bg-light" disabled>
                            <option value="">-- Select --</option>
                        </select>
                    </div>

                    <div class="col-md-8 col-lg-3">
                        <label class="form-label small fw-bold">Select Staff <span class="text-danger">*</span></label>
                        <select id="filter_staff" class="form-select border-primary" disabled>
                            <option value="">-- Select Staff --</option>
                        </select>
                    </div>
                </div>

                <div id="actionButtons" style="display: none;" class="text-center mt-5 pt-3 border-top">
                    <h6 class="fw-bold text-secondary mb-4">Available Visiting Card Formats</h6>
                    <div class="d-flex justify-content-center gap-4">
                        <button class="btn btn-outline-primary px-4 py-2.5 fw-bold print-btn bg-white secured-item"
                            data-permission="visiting_card_print" data-type="visiting_normal">
                            <i class="fas fa-address-card me-2"></i> Print Visiting Card (Normal)
                        </button>

                        <button class="btn btn-dark px-4 py-2.5 fw-bold print-btn shadow-sm secured-item"
                            data-permission="visiting_card_print1" data-type="visiting_premium">
                            <i class="fas fa-gem text-warning me-2"></i> Print Visiting Card (Premium)
                        </button>
                    </div>
                </div>
                
                <div id="loadingIndicator" class="text-center mt-4" style="display: none;">
                    <i class="fas fa-spinner fa-spin text-success fs-3"></i>
                    <p class="small text-muted mt-2">Loading context matrix...</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // JS logic is identical to ID cards to maintain structural consistency
        $(document).ready(function() {
            let globalContext = null;

            $.ajax({
                url: '/api/v1/context',
                type: 'GET',
                success: function(res) {
                    globalContext = res;
                    loadCompanies();
                }
            });

            function loadCompanies() {
                showLoader();
                $.get('/api/v1/get-active-companies', function(res) {
                    let options = '<option value="">-- Select Company --</option>';
                    res.data.forEach(c => {
                        options += `<option value="${c.id}">${c.company_name}</option>`;
                    });
                    $('#filter_company').html(options);

                    if (!globalContext.is_god && globalContext.company_id) {
                        $('#filter_company').val(globalContext.company_id).prop('disabled', true);
                        loadBranches(globalContext.company_id);
                    }
                    hideLoader();
                });
            }

            function loadBranches(companyId) {
                showLoader();
                $('#filter_branch').html('<option value="">-- Select Branch --</option>').prop('disabled', true);
                $('#filter_department').html('<option value="">-- Select --</option>').prop('disabled', true);
                $('#filter_designation').html('<option value="">-- Select --</option>').prop('disabled', true);
                $('#filter_staff').html('<option value="">-- Select --</option>').prop('disabled', true);
                $('#actionButtons').slideUp();

                if (!companyId) { hideLoader(); return; }

                $.ajax({
                    url: '/api/v1/get-branches-by-companies',
                    type: 'POST',
                    data: { company_ids: [companyId] },
                    success: function(res) {
                        let options = '<option value="">-- Select Branch --</option>';
                        options += `<option value="HO" class="fw-bold text-success">🏢 Head Office (HO)</option>`;
                        
                        res.data.forEach(b => {
                            options += `<option value="${b.id}">${b.branch_name}</option>`;
                        });
                        
                        $('#filter_branch').html(options).prop('disabled', false);

                        if (!globalContext.is_god && !globalContext.is_director) {
                            if (globalContext.branch_id) {
                                $('#filter_branch').val(globalContext.branch_id).prop('disabled', true);
                                loadDepartments(companyId, globalContext.branch_id);
                            } else {
                                $('#filter_branch').val('HO').prop('disabled', false);
                                loadDepartments(companyId, 'HO');
                            }
                        }
                        hideLoader();
                    }
                });
            }

            function loadDepartments(companyId, branchId) {
                showLoader();
                $('#filter_department').html('<option value="">-- Select --</option>').prop('disabled', true);
                $('#filter_designation').html('<option value="">-- Select --</option>').prop('disabled', true);
                $('#filter_staff').html('<option value="">-- Select --</option>').prop('disabled', true);
                $('#actionButtons').slideUp();

                if (!branchId) { hideLoader(); return; }

                $.ajax({
                    url: '/api/v1/get-departments-by-company',
                    type: 'GET',
                    data: { company_id: companyId, branch_id: branchId },
                    success: function(res) {
                        let options = '<option value="">-- Select Department --</option>';
                        res.data.forEach(d => {
                            options += `<option value="${d.id}">${d.department_name}</option>`;
                        });
                        $('#filter_department').html(options).prop('disabled', false);
                        loadStaff();
                        hideLoader();
                    }
                });
            }

            function loadDesignations(deptId) {
                showLoader();
                $('#filter_designation').html('<option value="">-- Select --</option>').prop('disabled', true);
                $('#filter_staff').html('<option value="">-- Select --</option>').prop('disabled', true);
                $('#actionButtons').slideUp();

                if (!deptId) { loadStaff(); hideLoader(); return; }

                $.ajax({
                    url: '/api/v1/get-designations-by-dept',
                    type: 'GET',
                    data: { department_id: deptId },
                    success: function(res) {
                        let options = '<option value="">-- Select Designation --</option>';
                        res.data.forEach(d => {
                            options += `<option value="${d.id}">${d.designation_name}</option>`;
                        });
                        $('#filter_designation').html(options).prop('disabled', false);
                        loadStaff();
                        hideLoader();
                    }
                });
            }

            function loadStaff() {
                showLoader();
                $('#actionButtons').slideUp();
                
                let filters = {
                    company_id: $('#filter_company').val(),
                    branch_id: $('#filter_branch').val(),
                    department_id: $('#filter_department').val(),
                    designation_id: $('#filter_designation').val()
                };

                $.ajax({
                    url: '/api/v1/id-cards/staff-list',
                    type: 'GET',
                    data: filters,
                    success: function(res) {
                        let options = '<option value="">-- Select Staff to Print --</option>';
                        res.data.forEach(s => {
                            options += `<option value="${s.id}">${s.name} - (${s.type})</option>`;
                        });
                        $('#filter_staff').html(options).prop('disabled', false);
                        hideLoader();
                    }
                });
            }

            $('#filter_company').on('change', function() { loadBranches($(this).val()); });
            $('#filter_branch').on('change', function() { loadDepartments($('#filter_company').val(), $(this).val()); });
            $('#filter_department').on('change', function() { loadDesignations($(this).val()); });
            $('#filter_designation').on('change', function() { loadStaff(); });

            $('#filter_staff').on('change', function() {
                if ($(this).val()) {
                    $('#actionButtons').slideDown(300, function() {
                        if (typeof window.applyPermissions === 'function') window.applyPermissions();
                    });
                } else {
                    $('#actionButtons').slideUp();
                }
            });

            $('.print-btn').on('click', function() {
                let type = $(this).data('type');
                let id = $('#filter_staff').val();
                if(!id) { alert('Please select a valid staff member.'); return; }
                window.open(`/admin/id-cards/print/${type}?member_id=${encodeURIComponent(id)}`, '_blank');
            });

            function showLoader() { $('#loadingIndicator').show(); }
            function hideLoader() { $('#loadingIndicator').hide(); }
        });
    </script>
@endpush