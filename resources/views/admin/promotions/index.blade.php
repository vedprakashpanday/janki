@extends('layout.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-level-up-alt text-success me-2"></i> Promote Staff & Update Roles</h5>
                    
                    <ul class="nav nav-pills" id="promotionTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active btn-sm px-4 rounded-pill" id="employee-tab" data-bs-toggle="pill" data-type="employee" type="button" role="tab">Promote Employee</button>
                        </li>
                        <li class="nav-item ms-2" role="presentation">
                            <button class="nav-link btn-sm px-4 rounded-pill" id="member-tab" data-bs-toggle="pill" data-type="member" type="button" role="tab">Promote Member</button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <form id="promotionForm">
                        <input type="hidden" id="staff_type" value="employee">
                        
                        <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">1. Select Target (New) Role</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-muted small">New Company <span class="text-danger">*</span></label>
                                <select class="form-select select2-init" id="company_id" required>
                                    <option value="">Select Company...</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium text-muted small">New Branch <span class="text-danger">*</span></label>
                                <select class="form-select select2-init" id="branch_id" required disabled>
                                    <option value="">Select Company First...</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium text-muted small">New Department <span class="text-danger">*</span></label>
                                <select class="form-select select2-init" id="department_id" required disabled>
                                    <option value="">Select Branch First...</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium text-muted small">New Designation <span class="text-danger">*</span></label>
                                <select class="form-select select2-init" id="designation_id" required disabled>
                                    <option value="">Select Department First...</option>
                                </select>
                            </div>
                        </div>

                       <h6 class="text-primary fw-bold mb-3 border-bottom pb-2 mt-4">2. Select Candidates & Set Details</h6>
                        <div class="row g-3">
                            <!-- Smart Search Staff -->
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-muted small">Search Candidates (Type Name or ID) <span class="text-danger">*</span></label>
                                <select class="form-select" id="staff_ids" multiple="multiple" required disabled>
                                </select>
                                <small class="text-muted mt-1 d-block"><i class="fas fa-info-circle"></i> Multiple selection allowed.</small>
                            </div>

                            <!-- New Salary -->
                            <div class="col-md-3">
                                <label class="form-label fw-medium text-muted small">New Salary (₹) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">₹</span>
                                    <input type="number" step="0.01" class="form-control" id="new_salary" placeholder="e.g. 25000" required>
                                </div>
                            </div>

                            <!-- Effective Date -->
                            <div class="col-md-3">
                                <label class="form-label fw-medium text-muted small">Effective Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="effective_date" required>
                            </div>
                        </div>

                        <div class="mt-5 text-end">
                            <button type="submit" class="btn btn-success px-5 rounded-pill shadow-sm" id="promoteBtn">
                                <i class="fas fa-rocket me-2"></i> Promote Selected Staff
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    
    // Initialize standard select2
    $('.select2-init').select2({ width: '100%' });

    // Initialize Smart Search Select2 for Candidates
    function initStaffSearch(type) {
        $('#staff_ids').empty().select2({
            width: '100%',
            placeholder: 'Type name to search...',
            minimumInputLength: 2,
            ajax: {
                url: '/api/v1/promotions/search',
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term,
                        staff_type: type
                    };
                },
                processResults: function (response) {
                    return {
                        results: $.map(response.data, function (item) {
                            return {
                                id: item.id,
                                text: item.name + ' (' + item.member_id + ')' + (item.salary ? ' - Current ₹' + item.salary : '')
                            }
                        })
                    };
                },
                cache: true
            }
        });
    }

    // Initial Setup
    initStaffSearch('employee');

    // Tab Switch Logic
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        let type = $(e.target).data('type');
        $('#staff_type').val(type);
        
        // Re-init search bar for the new type (Employee/Member)
        initStaffSearch(type);
        
        // Reset form completely but keep the first active companies
        $('#branch_id, #department_id, #designation_id').val('').trigger('change').prop('disabled', true);
        $('#company_id').val('').trigger('change');
        $('#new_salary').val('');
        $('#staff_ids').prop('disabled', true);
    });

    // ==========================================
    // CASCADING DROPDOWNS LOGIC
    // ==========================================

    // 1. Load Companies on Page Load
    $.get('/api/v1/get-active-companies', function(res) {
        if(res.status === 'success') {
            let options = '<option value="">Select Company...</option>';
            res.data.forEach(c => {
                options += `<option value="${c.id}">${c.company_name} (${c.company_code})</option>`;
            });
            $('#company_id').html(options);
        }
    });

    // 2. Company -> Load Branches
    $('#company_id').on('change', function() {
        let compId = $(this).val();
        $('#branch_id, #department_id, #designation_id').val('').trigger('change').prop('disabled', true);
        $('#staff_ids').prop('disabled', true);

        if(compId) {
            $.post('/api/v1/get-branches-by-companies', { company_ids: [compId] }, function(res) {
                if(res.status === 'success') {
                    // Head office ka default option null pass karne ke liye
                    let options = '<option value="">Select Branch...</option>';
                    options += `<option value="HO" class="fw-bold text-primary">Head Office (Default)</option>`;
                    
                    res.data.forEach(b => {
                        options += `<option value="${b.id}">${b.branch_name} (${b.branch_id})</option>`;
                    });
                    
                    $('#branch_id').html(options).prop('disabled', false);
                }
            });
        }
    });

    // 3. Branch -> Load Departments
    $('#branch_id').on('change', function() {
        let branchId = $(this).val();
        let compId = $('#company_id').val();
        $('#department_id, #designation_id').val('').trigger('change').prop('disabled', true);
        $('#staff_ids').prop('disabled', true);

        if(branchId) {
            $.ajax({
                url: '/api/v1/get-departments-by-company',
                type: 'GET',
                data: { company_id: compId, branch_id: branchId },
                success: function(res) {
                    if(res.status === 'success') {
                        let options = '<option value="">Select Department...</option>';
                        res.data.forEach(d => {
                            options += `<option value="${d.id}">${d.department_name}</option>`;
                        });
                        $('#department_id').html(options).prop('disabled', false);
                    }
                }
            });
        }
    });

    // 4. Department -> Load Designations
    $('#department_id').on('change', function() {
        let deptId = $(this).val();
        $('#designation_id').val('').trigger('change').prop('disabled', true);
        $('#staff_ids').prop('disabled', true);

        if(deptId) {
            $.ajax({
                url: '/api/v1/get-designations-by-dept',
                type: 'GET',
                data: { department_id: deptId },
                success: function(res) {
                    if(res.status === 'success') {
                        let options = '<option value="">Select Designation...</option>';
                        res.data.forEach(d => {
                            options += `<option value="${d.id}">${d.designation_name}</option>`;
                        });
                        $('#designation_id').html(options).prop('disabled', false);
                    }
                }
            });
        }
    });

    // 5. Enable Staff Search when Target Designation is selected
    $('#designation_id').on('change', function() {
        if($(this).val()) {
            $('#staff_ids').prop('disabled', false);
        } else {
            $('#staff_ids').prop('disabled', true);
        }
    });

    // ==========================================
    // FORM SUBMISSION (We will build API in Phase 4)
    // ==========================================
    $('#promotionForm').on('submit', function(e) {
        e.preventDefault();
        
     let formData = {
            staff_type: $('#staff_type').val(),
            company_id: $('#company_id').val(),
            branch_id: $('#branch_id').val() === 'HO' ? null : $('#branch_id').val(),
            department_id: $('#department_id').val(),
            designation_id: $('#designation_id').val(),
            staff_ids: $('#staff_ids').val(),
            new_salary: $('#new_salary').val(),
            effective_date: $('#effective_date').val(), // 🔥 NAYA FIELD
        };

        if(!formData.staff_ids || formData.staff_ids.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Action Required', text: 'Please select at least one candidate.' });
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to promote ${formData.staff_ids.length} staff member(s).`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Promote Now!'
        }).then((result) => {
            if (result.isConfirmed) {
                
                Swal.fire({ title: 'Processing Promotion...', html: 'Please wait...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                // Yahan hum Phase 4 ka Submit API hit karenge
                $.ajax({
                    url: '/api/v1/promotions/submit', // Ye API Phase 4 mein banayenge
                    type: 'POST',
                    data: formData,
                    success: function(res) {
                        Swal.fire({ icon: 'success', title: 'Congratulations!', text: res.message }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function(err) {
                        Swal.fire({ icon: 'error', title: 'Oops!', text: err.responseJSON?.message || 'Something went wrong.' });
                    }
                });
            }
        });
    });

});
</script>
@endpush