@extends('layout.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10 col-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white border-0 pt-4 pb-0">
                        <h4 class="mb-0 fw-bold text-primary">Add New Phase</h4>
                        <p class="text-muted small">Telecalling ke liye nayi phase details darj karein.</p>
                    </div>

                    <div class="card-body p-4">
                        <form id="phaseForm" action="/api/v1/phases" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold">Company</label>
                                    <select name="company_id" id="company_id" class="form-select shadow-none" disabled>
                                        <option value="">Loading...</option>
                                    </select>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold">Branch</label>
                                    <select name="branch_id" id="branch_id" class="form-select shadow-none" disabled>
                                        <option value="">Loading...</option>
                                    </select>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold">Phase Name</label>
                                    <input type="text" name="phase_name" class="form-control shadow-none"
                                        placeholder="e.g. Phase 1 - Royal Villas" required>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold">Phase Location</label>
                                    <input type="text" name="phase_location" class="form-control shadow-none"
                                        placeholder="Enter full location" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">Phase Details</label>
                                    <textarea name="phase_details" class="form-control shadow-none" rows="3"
                                        placeholder="Highlight key features of this phase..." required></textarea>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold">Phase Image</label>
                                    <input type="file" id="phase_image" name="phase_image"
                                        class="form-control shadow-none" accept="image/*">
                                    <small class="text-muted">Telecaller image button par click karke ise dekh
                                        sakega.</small>

                                    <div id="imagePreviewContainer" class="mt-3 position-relative d-none"
                                        style="width: 150px;">
                                        <img id="imagePreview" src="" alt="Preview"
                                            class="img-thumbnail w-100 shadow-sm rounded">
                                        <button type="button" id="clearImageBtn"
                                            class="btn btn-danger btn-sm position-absolute top-0 start-100 translate-middle rounded-circle shadow"
                                            style="width: 25px; height: 25px; padding: 0;">
                                            <i class="fas fa-times" style="font-size: 12px;"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold">Base Map / Khatiyan</label>
                                    <input type="file" class="form-control shadow-none" id="khatiyan_map" name="khatiyan_map" accept="image/*">
                                    <small class="text-muted">Ye map Unit/Plot distribution ke liye use hoga.</small>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-semibold">Google Map URL (Optional)</label>
                                    <input type="url" name="phase_google_map_url" class="form-control shadow-none"
                                        placeholder="https://maps.google.com/...">
                                </div>

                                
                                
                            </div>

                            <div class="mt-4 text-end">
                                <button type="reset" class="btn btn-light me-2">Clear Form</button>
                                <button type="submit" class="btn btn-primary px-4" id="submitBtn">Save Phase</button>
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
        // 🔥 Ye line add karni hai
        let currentPortal = "{{ request()->segment(1) }}"; 
        
       
        
        $.ajax({
            url: '/api/v1/phases/form-data',
            type: 'GET',
            success: function(res) {
                if(res.success) {
                    let d = res.data;
                    let compSelect = $('#company_id');
                    let branchSelect = $('#branch_id');
                    
                    compSelect.empty();
                    branchSelect.empty();

                    if(d.is_god) {
                        compSelect.prop('disabled', false);
                        compSelect.append('<option value="">Select Company</option>');
                        d.companies.forEach(c => {
                            compSelect.append(`<option value="${c.id}">${c.company_name}</option>`);
                        });
                        
                        branchSelect.prop('disabled', false);
                        branchSelect.append('<option value="">Select Branch (Will load on company change)</option>');
                        
                    } else if(d.is_director) {
                        compSelect.append(`<option value="${d.locked_company_id}" selected>Apni Company (Auto Locked)</option>`);
                        compSelect.prop('disabled', true);
                        
                        branchSelect.prop('disabled', false);
                        branchSelect.append('<option value="">Select Branch</option>');
                        branchSelect.append('<option value="">Head Office</option>');
                        d.branches.forEach(b => {
                            branchSelect.append(`<option value="${b.id}">${b.branch_name}</option>`);
                        });
                    } else {
                        compSelect.append(`<option value="${d.locked_company_id}" selected>My Company</option>`);
                        compSelect.prop('disabled', true);
                        branchSelect.append(`<option value="${d.locked_branch_id}" selected>My Branch</option>`);
                        branchSelect.prop('disabled', true);
                    }
                }
            },
            error: function() {
                Swal.fire('Error', 'Could not load form data. Check your connection.', 'error');
            }
        });

        // Image Preview Logic
        const imageInput = document.getElementById('phase_image');
        const previewContainer = document.getElementById('imagePreviewContainer');
        const imagePreview = document.getElementById('imagePreview');
        const clearBtn = document.getElementById('clearImageBtn');

        imageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    previewContainer.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.classList.add('d-none');
            }
        });

        clearBtn.addEventListener('click', function() {
            imageInput.value = '';
            imagePreview.src = '';
            previewContainer.classList.add('d-none');
        });


        // ==========================================
        // 🔥 DEPENDENT DROPDOWN LOGIC 🔥
        // ==========================================
        $('#company_id').on('change', function() {
            let companyId = $(this).val();
            let branchSelect = $('#branch_id');

            branchSelect.empty();
            branchSelect.append('<option value="">Select Branch</option>');

            if (companyId) {
                // 1. By default "Head Office" add karna (Jiski value blank hai = null)
                branchSelect.append('<option value="">Head Office (Auto)</option>');
                
                // Dropdown ko temporary disable karke loading dikhayein
                branchSelect.prop('disabled', true);

                // 2. AJAX se specific company ki branches fetch karna
                $.ajax({
                    url: '/api/v1/phases/get-branches/' + companyId,
                    type: 'GET',
                    success: function(res) {
                        if (res.success && res.data.length > 0) {
                            res.data.forEach(b => {
                                branchSelect.append(`<option value="${b.id}">${b.branch_name}</option>`);
                            });
                        }
                        // Data aane ke baad Dropdown enable kar dein
                        branchSelect.prop('disabled', false);
                    },
                    error: function() {
                        branchSelect.prop('disabled', false);
                    }
                });
            }
        });

        // 🟢 NOTE: Agar Director login karega, to usko preloaded data milta hai. 
        // Usme bhi Head Office dikhane ke liye aapke form-data wale AJAX logic me jahan director ka loop hai, wahan ye add kar dein:
        // branchSelect.append('<option value="">Head Office (Auto)</option>');





        // AJAX Form Submit
        $('#phaseForm').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            if($('#company_id').is(':disabled')) formData.append('company_id', $('#company_id').val());
            if($('#branch_id').is(':disabled')) formData.append('branch_id', $('#branch_id').val());

            $('#submitBtn').prop('disabled', true).text('Saving...');

            $.ajax({
                url: '/api/v1/phases', 
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Swal.fire('Success', 'Phase created successfully!', 'success').then(() => {
                        window.location.href = '/' + currentPortal + '/phases';
                    });
                },
                error: function(xhr) {
                    $('#submitBtn').prop('disabled', false).text('Save Phase');
                    let errorMsg = 'An error occurred';
                    if(xhr.responseJSON && xhr.responseJSON.message) errorMsg = xhr.responseJSON.message;
                    Swal.fire('Error', errorMsg, 'error');
                }
            });
        });
    });
</script>
@endpush