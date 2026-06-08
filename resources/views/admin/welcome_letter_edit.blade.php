@extends('layout.app') 

@section('content')
<!-- Include Summernote CSS for Rich Text Editor -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">

<style>
    /* Admin Edit page custom spacing fix */
    .editor-wrapper {
        margin-top: -10px; /* Removes top white space under navbar */
        margin-bottom: 20px;
    }
    .note-editor .note-editing-area .note-editable {
        font-family: 'Georgia', serif;
        font-size: 15px;
        line-height: 1.6;
        background-color: #ffffff;
    }
</style>

<div class="container-fluid editor-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 fw-bold" style="color: var(--sidebar-bg);">
            <i class="fas fa-edit text-warning me-2"></i> Edit Welcome Letter Template
        </h5>
        <button id="saveTemplateBtn" class="btn btn-success shadow-sm">
            <i class="fas fa-save me-2"></i> Save Changes
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-light border-bottom py-3">
            <h6 class="mb-0 text-dark" style="font-size: 13.5px;">
                <i class="fas fa-info-circle text-primary me-2"></i> 
                <strong>Dynamic Variables:</strong> Use these exact tags to fetch live data: <br>
                <span class="badge bg-secondary mt-2">[EMPLOYEE_NAME]</span>
                <span class="badge bg-secondary mt-2">[EMP_ID]</span>
                <span class="badge bg-secondary mt-2">[COMPANY_NAME]</span>
                <span class="badge bg-secondary mt-2">[BRANCH_NAME]</span>
                <span class="badge bg-secondary mt-2">[DEPARTMENT]</span>
                <span class="badge bg-secondary mt-2">[DESIGNATION]</span>
                <span class="badge bg-secondary mt-2">[DATE]</span>
            </h6>
        </div>
        <div class="card-body p-0">
            <div id="loading-indicator" class="text-center p-5">
                <i class="fas fa-spinner fa-spin fa-2x text-warning"></i>
                <p class="mt-2 text-muted fw-bold">Loading Template Data...</p>
            </div>
            
            <div id="editor-container" style="display: none;">
                <textarea id="summernote"></textarea>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
    $(document).ready(function() {
        // Fetch Template Content from API
        $.ajax({
            url: '/api/v1/admin/welcome-letter-template',
            type: 'GET',
            success: function(res) {
                $('#loading-indicator').hide();
                $('#editor-container').fadeIn();

                // Initialize Summernote
                $('#summernote').summernote({
                    height: 500,
                    placeholder: 'Type or edit the welcome letter content here...',
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'italic', 'underline', 'clear']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['table', ['table']],
                        ['insert', ['link', 'picture', 'hr']],
                        ['view', ['fullscreen', 'codeview', 'help']]
                    ]
                });

                // Set Default or Fetched Content
                if(res.success && res.data) {
                    $('#summernote').summernote('code', res.data);
                }
            },
            error: function(err) {
                console.error("Template Fetch Error:", err);
                $('#loading-indicator').html('<div class="alert alert-danger m-3">Failed to load template. Please check connection.</div>');
            }
        });

        // Save Template Flow
        $('#saveTemplateBtn').click(function() {
            let btn = $(this);
            let content = $('#summernote').summernote('code');

            if (content.trim() === '' || content.trim() === '<p><br></p>') {
                Swal.fire('Warning', 'Content cannot be empty!', 'warning');
                return;
            }

            btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

            $.ajax({
                url: '/api/v1/admin/welcome-letter-template',
                type: 'POST',
                data: { content: content },
                success: function(res) {
                    Swal.fire('Saved!', res.message, 'success');
                    btn.html('<i class="fas fa-save me-2"></i> Save Changes').prop('disabled', false);
                },
                error: function(err) {
                    Swal.fire('Error', 'Could not save template.', 'error');
                    btn.html('<i class="fas fa-save me-2"></i> Save Changes').prop('disabled', false);
                }
            });
        });
    });
</script>
@endpush