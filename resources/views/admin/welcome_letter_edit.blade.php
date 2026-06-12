@extends('layout.app')

@section('content')
    <style>
        .editor-wrapper {
            margin-top: -10px;
            margin-bottom: 20px;
        }

        .tags-employee,
        .tags-member,
        .tags-customer {
            display: none;
        }
    </style>

    <div class="container-fluid editor-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="mb-0 fw-bold" style="color: var(--sidebar-bg);">
                <i class="fas fa-edit text-warning me-2"></i> Edit Welcome Letter Template
            </h5>

            <div class="d-flex gap-2 align-items-center flex-wrap">
                <select id="templateType" class="form-select fw-bold shadow-sm"
                    style="width: auto; border-color: var(--sidebar-bg);">
                    <option value="employee" selected>Common Employee</option>
                    <option value="member">Common Associate</option>
                    <option value="customer">Common Customer</option>
                    <option value="other" class="text-danger fw-bold">Specific (Particular Person)</option>
                </select>

                <div id="entityTypeContainer" style="display: none;">
                    <select id="entityType" class="form-select shadow-sm" style="width: auto; border-color: #D69E2E;">
                        <option value="" selected disabled>Select Category...</option>
                        <option value="employee">Employee</option>
                        <option value="member">Associate Member</option>
                        <option value="customer">Customer</option>
                    </select>
                </div>

                <div id="entityIdContainer" style="display: none;">
                    <input type="text" id="entityInput" list="entityList" class="form-control shadow-sm"
                        placeholder="Type ID or Name..." style="width: 220px; border-color: #D69E2E;">
                    <datalist id="entityList"></datalist>
                </div>

                <button id="saveTemplateBtn" class="btn btn-success shadow-sm text-nowrap">
                    <i class="fas fa-save me-2"></i> Save Changes
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-light border-bottom py-3">
                <h6 class="mb-0 text-dark" style="font-size: 13.5px;">
                    <i class="fas fa-info-circle text-primary me-2"></i>
                    <strong>Dynamic Variables:</strong> Use these exact tags in the text to fetch live data: <br>

                    <div class="tags-employee mt-2">
                        <span class="badge bg-secondary">[EMPLOYEE_NAME]</span>
                        <span class="badge bg-secondary">[EMP_ID]</span>
                        <span class="badge bg-secondary">[COMPANY_NAME]</span>
                        <span class="badge bg-secondary">[BRANCH_NAME]</span>
                        <span class="badge bg-secondary">[DEPARTMENT]</span>
                        <span class="badge bg-secondary">[DESIGNATION]</span>
                        <span class="badge bg-secondary">[DATE]</span>
                    </div>

                    <div class="tags-member mt-2">
                        <span class="badge bg-info text-dark">[MEMBER_NAME]</span>
                        <span class="badge bg-info text-dark">[MEMBER_ID]</span>
                        <span class="badge bg-info text-dark">[COMPANY_NAME]</span>
                        <span class="badge bg-info text-dark">[SPONSOR_ID]</span>
                        <span class="badge bg-info text-dark">[DESIGNATION]</span>
                        <span class="badge bg-info text-dark">[DATE]</span>
                    </div>

                    <div class="tags-customer mt-2">
                        <span class="badge bg-success">[CUSTOMER_NAME]</span>
                        <span class="badge bg-success">[FATHER_NAME]</span>
                        <span class="badge bg-success">[ADDRESS]</span>
                        <span class="badge bg-success">[CUSTOMER_ID]</span>
                        <span class="badge bg-success">[DATE]</span>
                    </div>
                </h6>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div id="loading-indicator" class="text-center p-5">
                    <i class="fas fa-spinner fa-spin fa-2x text-warning"></i>
                    <p class="mt-2 text-muted fw-bold">Loading Template Data...</p>
                </div>

                <div id="editor-container" style="display: none;">
                    <textarea id="tinymce-editor"></textarea>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.tiny.cloud/1/{{ env('TINYMCE_API_KEY', 'no-api-key') }}/tinymce/7/tinymce.min.js"
        referrerpolicy="origin"></script>

    <script>
        $(document).ready(function() {
            let editorReady = false;

            // Initialize TinyMCE
            tinymce.init({
                selector: '#tinymce-editor',
                height: 600,
                menubar: true,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount'
                ],
                toolbar: 'undo redo | blocks | ' +
                    'bold italic textcolor | alignleft aligncenter ' +
                    'alignright alignjustify | bullist numlist outdent indent | ' +
                    'removeformat | help',
                content_style: 'body { font-family: "Georgia", serif; font-size:15px; line-height:1.6; }',
                setup: function(editor) {
                    editor.on('init', function() {
                        editorReady = true;
                        // Initial load
                        loadTemplate($('#templateType').val());
                    });
                }
            });

            // 🟢 FUNCTION: Update Tags Visibility
            function updateTagsVisibility(type) {
                $('.tags-employee, .tags-member, .tags-customer').hide();
                if (type === 'member') {
                    $('.tags-member').fadeIn();
                } else if (type === 'customer') {
                    $('.tags-customer').fadeIn();
                } else {
                    $('.tags-employee').fadeIn(); // Default for employee
                }
            }

            // 🟢 FUNCTION: Load Template Data via API
            function loadTemplate(letterType, entityType = '', entityId = '') {
                if (!editorReady) return;

                $('#editor-container').hide();
                $('#loading-indicator').show();

                // Decide which tags to show
                let effectiveType = letterType;
                if (letterType === 'other' && entityType !== '') {
                    effectiveType = entityType;
                }
                updateTagsVisibility(effectiveType);

                // Fetch Template
                $.ajax({
                    url: '/api/v1/admin/welcome-letter-template',
                    type: 'GET',
                    data: {
                        type: letterType,
                        entity_type: entityType,
                        entity_id: entityId
                    },
                    success: function(res) {
                        $('#loading-indicator').hide();
                        $('#editor-container').fadeIn();

                        if (res.success && res.data) {
                            tinymce.get('tinymce-editor').setContent(res.data);
                        } else {
                            tinymce.get('tinymce-editor').setContent('');
                        }
                    },
                    error: function(err) {
                        console.error("Fetch Error:", err);
                        $('#loading-indicator').html(
                            '<div class="alert alert-danger m-3">Failed to load template.</div>');
                    }
                });
            }

            // 🟢 EVENT: Primary Dropdown Change
            $('#templateType').change(function() {
                let val = $(this).val();

                if (val === 'other') {
                    $('#entityTypeContainer').fadeIn();
                    $('#entityType').val(''); // Reset
                    $('#entityIdContainer').hide();
                    $('#entityInput').val('');
                    tinymce.get('tinymce-editor').setContent(
                        '<p class="text-center text-muted">Please select a category and user to load the template.</p>'
                        );
                } else {
                    $('#entityTypeContainer').hide();
                    $('#entityIdContainer').hide();
                    $('#entityType').val('');
                    $('#entityInput').val('');
                    loadTemplate(val);
                }
            });

            // 🟢 EVENT: Entity Category Change (Employee/Member/Customer)
            $('#entityType').change(function() {
                let category = $(this).val();
                if (!category) return;

                $('#entityInput').val(''); // clear input
                $('#entityIdContainer').fadeIn();

                // Fetch Data for Datalist
                $('#entityList').html('<option value="Loading...">');

                $.ajax({
                    url: '/api/v1/admin/welcome-letter-entities',
                    type: 'GET',
                    data: {
                        type: category
                    },
                    success: function(res) {
                        let html = '';
                        if (res.success && res.data.length > 0) {
                           res.data.forEach(item => {
    // Label Format: Name (ID)
    html += `<option value="${item.id}">${item.name} (${item.id})</option>`;
});
                        } else {
                            html = '<option value="" disabled>No records found</option>';
                        }
                        $('#entityList').html(html);

                        // Load common template of this category as fallback while user hasn't selected an ID
                        loadTemplate('other', category, '');
                    }
                });
            });

            // 🟢 EVENT: Datalist Input Change (When Specific User is selected/typed)
            $('#entityInput').on('change', function() {
                let selectedId = $(this).val();
                let category = $('#entityType').val();

                if (selectedId && category) {
                    loadTemplate('other', category, selectedId);
                }
            });

            // 🟢 EVENT: Save Button Click
            $('#saveTemplateBtn').click(function() {
                let btn = $(this);
                let content = tinymce.get('tinymce-editor').getContent();
                let type = $('#templateType').val();
                let entityType = $('#entityType').val();
                let entityId = $('#entityInput').val();

                // Validation
                if (content.trim() === '') {
                    Swal.fire('Warning', 'Content cannot be empty!', 'warning');
                    return;
                }
                if (type === 'other' && (!entityType || !entityId)) {
                    Swal.fire('Warning', 'Please select a specific person from the list before saving.',
                        'warning');
                    return;
                }

                btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

                $.ajax({
                    url: '/api/v1/admin/welcome-letter-template',
                    type: 'POST',
                    data: {
                        content: content,
                        type: type,
                        entity_type: type === 'other' ? entityType : null,
                        entity_id: type === 'other' ? entityId : null
                    },
                    success: function(res) {
                        Swal.fire('Saved!', res.message, 'success');
                        btn.html('<i class="fas fa-save me-2"></i> Save Changes').prop(
                            'disabled', false);
                    },
                    error: function(err) {
                        Swal.fire('Error', 'Could not save template.', 'error');
                        btn.html('<i class="fas fa-save me-2"></i> Save Changes').prop(
                            'disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
