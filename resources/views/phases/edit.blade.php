@extends('layout.app') 

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10 col-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0 fw-bold text-warning"><i class="fas fa-edit me-2"></i>Edit Phase</h4>
                            <p class="text-muted small">Phase ki details update karein.</p>
                        </div>
                        <a href="#" id="backBtn" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
                    </div>

                    <div class="card-body p-4">
                        <form id="phaseEditForm" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="_method" value="PUT"> 
                            <input type="hidden" id="phase_id" value="{{ $id }}">

                            <div class="text-center mb-3" id="loadingSpinner">
                                <div class="spinner-border text-warning" role="status"></div>
                                <p class="text-muted small mt-2">Loading data...</p>
                            </div>

                            <div class="row g-3 d-none" id="formContent">
                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold">Company</label>
                                    <select name="company_id" id="company_id" class="form-select shadow-none" disabled>
                                        <option value="">Loading...</option>
                                    </select>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold">Branch</label>
                                    <select name="branch_id" id="branch_id" class="form-select shadow-none" disabled>
                                        <option value="">Select Branch</option>
                                    </select>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold">Phase Name</label>
                                    <input type="text" id="phase_name" name="phase_name" class="form-control shadow-none" required>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold">Phase Location</label>
                                    <input type="text" id="phase_location" name="phase_location" class="form-control shadow-none" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">Phase Details</label>
                                    <textarea id="phase_details" name="phase_details" class="form-control shadow-none" rows="3" required></textarea>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold">Phase Image (Leave blank to keep old)</label>
                                    <input type="file" id="phase_image" name="phase_image" class="form-control shadow-none" accept="image/*">
                                    
                                    <div id="imagePreviewContainer" class="mt-3 position-relative d-none" style="width: 150px;">
                                        <img id="imagePreview" src="" alt="Preview" class="img-thumbnail w-100 shadow-sm rounded">
                                        <button type="button" id="clearImageBtn" class="btn btn-danger btn-sm position-absolute top-0 start-100 translate-middle rounded-circle shadow" style="width: 25px; height: 25px; padding: 0;">
                                            <i class="fas fa-times" style="font-size: 12px;"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold">Google Map URL (Optional)</label>
                                    <input type="url" id="phase_google_map_url" name="phase_google_map_url" class="form-control shadow-none">
                                </div>
                                
                                <div class="col-12 mt-4 text-end">
                                    <button type="submit" class="btn btn-warning px-4 text-white" id="submitBtn">Update Phase</button>
                                </div>
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
        let currentPortal = "{{ request()->segment(1) }}"; 
        $('#backBtn').attr('href', '/' + currentPortal + '/phases');
        
        let phaseId = $('#phase_id').val();
        let phaseData = null;

        // 1. Pehle Dropdown Options layenge
        $.ajax({
            url: '/api/v1/phases/form-data',
            type: 'GET',
            success: function(res) {
                if(res.success) {
                    let d = res.data;
                    let compSelect = $('#company_id');
                    
                    compSelect.empty();

                    if(d.is_god) {
                        compSelect.prop('disabled', false);
                        compSelect.append('<option value="">Select Company</option>');
                        d.companies.forEach(c => {
                            compSelect.append(`<option value="${c.id}">${c.company_name}</option>`);
                        });
                    } else if(d.is_director || true) { // Employee ko bhi locked dikhana hai
                        compSelect.append(`<option value="${d.locked_company_id}" selected>Apni Company</option>`);
                        compSelect.prop('disabled', true);
                    }

                    // Ab specific phase ka data load karo
                    loadPhaseData();
                }
            }
        });

        // 2. Specific Phase ka data load karna
        function loadPhaseData() {
            $.ajax({
                url: '/api/v1/phases/' + phaseId,
                type: 'GET',
                success: function(res) {
                    if(res.success) {
                        phaseData = res.data;
                        
                        // Populate Text Fields
                        $('#phase_name').val(phaseData.phase_name);
                        $('#phase_location').val(phaseData.phase_location);
                        $('#phase_details').val(phaseData.phase_details);
                        $('#phase_google_map_url').val(phaseData.phase_google_map_url);

                        // Set Company and trigger branch load
                        if(phaseData.company_id && !$('#company_id').is(':disabled')) {
                            $('#company_id').val(phaseData.company_id);
                        }
                        
                        loadBranches(phaseData.company_id, phaseData.branch_id);

                        // Image Preview
                        if(phaseData.phase_image) {
                            $('#imagePreview').attr('src', '/' + phaseData.phase_image);
                            $('#imagePreviewContainer').removeClass('d-none');
                        }

                        // Show Form
                        $('#loadingSpinner').addClass('d-none');
                        $('#formContent').removeClass('d-none');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to load phase data', 'error');
                }
            });
        }

        // 3. Dependent Branch Dropdown Logic
        function loadBranches(companyId, selectedBranchId = null) {
            let branchSelect = $('#branch_id');
            branchSelect.empty().append('<option value="">Select Branch</option>');

            if (companyId) {
                branchSelect.append('<option value="">Head Office (Auto)</option>');
                branchSelect.prop('disabled', true);

                $.ajax({
                    url: '/api/v1/phases/get-branches/' + companyId,
                    type: 'GET',
                    success: function(res) {
                        if (res.success && res.data.length > 0) {
                            res.data.forEach(b => {
                                let selected = (selectedBranchId == b.id) ? 'selected' : '';
                                branchSelect.append(`<option value="${b.id}" ${selected}>${b.branch_name}</option>`);
                            });
                        }
                        branchSelect.prop('disabled', false);
                    }
                });
            }
        }

        // Company Change Event
        $('#company_id').on('change', function() {
            loadBranches($(this).val());
        });

        // Image Preview Logic
        $('#phase_image').on('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#imagePreview').attr('src', e.target.result);
                    $('#imagePreviewContainer').removeClass('d-none');
                };
                reader.readAsDataURL(file);
            }
        });

        $('#clearImageBtn').on('click', function() {
            $('#phase_image').val('');
            $('#imagePreviewContainer').addClass('d-none');
        });

        // AJAX Form Submit
        $('#phaseEditForm').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            
            if($('#company_id').is(':disabled')) formData.append('company_id', $('#company_id').val() || phaseData.company_id);
            if($('#branch_id').is(':disabled')) formData.append('branch_id', $('#branch_id').val() || phaseData.branch_id);

            $('#submitBtn').prop('disabled', true).text('Updating...');

            $.ajax({
                url: '/api/v1/phases/' + phaseId, // PUT route
                type: 'POST', // Laravel uses POST with _method=PUT inside formData
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Swal.fire('Success', 'Phase updated successfully!', 'success').then(() => {
                        window.location.href = '/' + currentPortal + '/phases';
                    });
                },
                error: function(xhr) {
                    $('#submitBtn').prop('disabled', false).text('Update Phase');
                    Swal.fire('Error', xhr.responseJSON.message || 'An error occurred', 'error');
                }
            });
        });
    });
</script>
@endpush