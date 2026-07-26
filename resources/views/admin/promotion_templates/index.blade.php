@extends('layout.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Main Editor Section -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-file-signature text-primary me-2"></i> Promotion Letter Templates</h5>
                    
                    <!-- Tabs for Employee & Member -->
                    <ul class="nav nav-pills" id="templateTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active btn-sm" id="employee-tab" data-bs-toggle="pill" data-type="employee" type="button" role="tab">Employee</button>
                        </li>
                        <li class="nav-item ms-2" role="presentation">
                            <button class="nav-link btn-sm" id="member-tab" data-bs-toggle="pill" data-type="member" type="button" role="tab">Member</button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body p-4">
                    <form id="templateForm">
                        <input type="hidden" id="template_type" value="employee">
                        
                        <div class="mb-3">
                            <label class="form-label fw-medium">Email / Letter Subject</label>
                            <input type="text" class="form-control" id="subject" placeholder="e.g., Congratulations on your Promotion!">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-medium">Letter Body</label>
                            <!-- Yahan tumhara pasandida Rich Text Editor (Summernote/CKEditor) init hoga -->
                            <textarea class="form-control" id="template_body" rows="15" placeholder="Write your template here..."></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i> Save Template
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Placeholders Guide Section -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 bg-light">
                <div class="card-header bg-light border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-code text-warning me-2"></i> Available Placeholders</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Click on any tag to copy it, then paste it in your template.</p>
                    <ul class="list-group list-group-flush border rounded">
                        <li class="list-group-item bg-white d-flex justify-content-between align-items-center">
                            User's Name <code class="cursor-pointer copy-tag">[NAME]</code>
                        </li>
                        <li class="list-group-item bg-white d-flex justify-content-between align-items-center">
                            Company Name <code class="cursor-pointer copy-tag">[COMPANY_NAME]</code>
                        </li>
                        <li class="list-group-item bg-white d-flex justify-content-between align-items-center">
                            Old Designation <code class="cursor-pointer copy-tag">[OLD_DESIGNATION]</code>
                        </li>
                        <li class="list-group-item bg-white d-flex justify-content-between align-items-center">
                            New Designation <code class="cursor-pointer copy-tag">[NEW_DESIGNATION]</code>
                        </li>
                        <li class="list-group-item bg-white d-flex justify-content-between align-items-center">
                            Old Salary <code class="cursor-pointer copy-tag">[OLD_SALARY]</code>
                        </li>
                        <li class="list-group-item bg-white d-flex justify-content-between align-items-center">
                            New Salary <code class="cursor-pointer copy-tag">[NEW_SALARY]</code>
                        </li>
                        <li class="list-group-item bg-white d-flex justify-content-between align-items-center">
                            Effective Date <code class="cursor-pointer copy-tag">[EFFECTIVE_DATE]</code>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    
    // Copy Placeholder to Clipboard
    $('.copy-tag').on('click', function() {
        let text = $(this).text();
        navigator.clipboard.writeText(text);
        Swal.fire({
            toast: true, position: 'top-end', icon: 'success', title: text + ' copied!', showConfirmButton: false, timer: 1500
        });
    });

    // Load Template Function
    function loadTemplate(type) {
        $('#template_type').val(type);
        $('#subject').val('');
        $('#template_body').val(''); // Agar Summernote use karte ho, toh yahan destroy karke set karna padega
        
        $.ajax({
           url: "/api/v1/promotion-templates/get",
            type: "GET",
            data: { type: type },
            success: function(res) {
                if(res.data) {
                    $('#subject').val(res.data.subject);
                    $('#template_body').val(res.data.template_body);
                    // Agar Summernote hai: $('#template_body').summernote('code', res.data.template_body);
                }
            }
        });
    }

    // Initial Load
    loadTemplate('employee');

    // Tab Change Event
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        let type = $(e.target).data('type');
        loadTemplate(type);
    });

    // Save Template Event
    $('#templateForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = {
            type: $('#template_type').val(),
            subject: $('#subject').val(),
            template_body: $('#template_body').val(), // Agar summernote hai: $('#template_body').summernote('code')
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        Swal.fire({ title: 'Saving...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });

        $.ajax({
            url: "/api/v1/promotion-templates/save",
            type: "POST",
            data: formData,
            success: function(res) {
                Swal.fire({ icon: 'success', title: 'Success', text: res.message });
            },
            error: function(err) {
                Swal.fire({ icon: 'error', title: 'Oops...', text: 'Something went wrong!' });
            }
        });
    });
});
</script>
@endpush