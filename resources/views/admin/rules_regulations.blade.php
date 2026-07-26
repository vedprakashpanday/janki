@extends('layout.app')

@section('content')
    <style>
        /* 🟢 Document Reading Mode Styling - DESKTOP EXPANDED */
        .document-paper {
            width: 98%;
            max-width: 1200px;
            height: auto;
            margin: 10px auto;
            background: #fff;
            padding: 30px 40px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            color: #000;
            box-sizing: border-box !important;
            word-wrap: break-word;
            overflow-wrap: break-word;
            display: flow-root;
        }

        .user-view-container {
            width: 100%;
            overflow-x: hidden;
            box-sizing: border-box;
        }

        /* 🔴 HIGH SECURITY MODE CLASSES (Applied to Employees Only) */
        .secure-mode {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .secure-blur {
            filter: blur(10px);
            transition: filter 0.2s ease-in-out;
        }

        @media print {
            body.secure-print-block {
                display: none !important;
                visibility: hidden !important;
            }

            .admin-print-only {
                display: none !important;
            }
        }

        /* 📱 MOBILE VIEW UI & STRICT OVERFLOW FIXES 📱 */
        @media (max-width: 767.98px) {
            .app-main {
                padding: 3px !important;
            }

            .admin-view-container .card-body {
                padding: 0.5rem !important;
                background: transparent !important;
            }

            .admin-view-container {
                background: transparent !important;
                box-shadow: none !important;
            }

            .user-view-container {
                padding: 0 !important;
                margin: 0 !important;
            }

            .document-paper {
                width: 100% !important;
                max-width: 100% !important;
                padding: 10px 5px !important;
                margin: 3px auto !important;
                border-radius: 4px;
                border: 1px solid #eee;
                box-shadow: none !important;
                box-sizing: border-box !important;
                overflow-x: hidden !important;
            }

            /* 🔥 TINYMCE STRICT OVERRIDE 🔥 */
            #userReadingContent,
            #userReadingContent * {
                max-width: 100% !important;
                width: auto !important;
                box-sizing: border-box !important;
                word-wrap: break-word !important;
                overflow-wrap: break-word !important;
                white-space: normal !important;
            }

            #userReadingContent img {
                max-width: 100% !important;
                height: auto !important;
                display: block;
            }

            #userReadingContent table {
                display: block !important;
                width: 100% !important;
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
                border-collapse: collapse !important;
            }
        }
    </style>

    <div class="container-fluid py-2" id="rulesMainContainer">
        <div class="d-flex justify-content-between align-items-center mb-4 admin-top-bar" style="display: none;">
            <h4 class="fw-bold text-dark mb-0 fs-5 fs-md-4"><i class="fas fa-gavel text-primary me-2"></i> Rules & Regulations</h4>
            <button class="btn btn-primary btn-sm secured-item" data-permission="rules_add" onclick="openAddModal()">
                <i class="fas fa-plus-circle me-1"></i> <span class="d-none d-md-inline">Add New Rule</span>
            </button>
        </div>

        <div class="card border-0 shadow-sm admin-view-container" style="display: none;">
            <div class="card-body p-0 p-md-3">
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover align-middle" id="rulesTable">
                        <thead class="bg-light">
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Target Audience</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="rulesList">
                        </tbody>
                    </table>
                </div>

                <div class="d-block d-md-none" id="rulesCardsList">
                </div>
            </div>
        </div>

        <div class="user-view-container" style="display: none;">
            <div class="d-flex justify-content-end mb-3 admin-print-btn-wrapper no-print"
                style="width: 98%; max-width: 1200px; margin: 0 auto; display: none;">
                <button class="btn btn-dark btn-sm fw-medium shadow-sm" onclick="window.print()">
                    <i class="fas fa-print me-2"></i> Print Document
                </button>
            </div>

            <div class="document-paper" id="secureDocumentArea">
                <div id="userReadingContent">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>Loading Official Document...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ruleModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalTitle">Add Rules & Regulations</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3 p-md-4">
                    <form id="ruleForm">
                        <input type="hidden" id="ruleId">
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="fw-medium mb-1">Title</label>
                                <input type="text" id="title" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-medium mb-1">Target Audience</label>
                                <select id="target_audience" class="form-select" required>
                                    <option value="employee">Employees</option>
                                    <option value="member">Members</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-medium mb-1">Content</label>
                            <textarea id="tinymce-editor" class="form-control" name="content"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="fw-medium mb-1">Status</label>
                            <select id="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary px-4 fw-medium" onclick="saveRule()">Save Rules</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.tiny.cloud/1/{{ env('TINYMCE_API_KEY', 'no-api-key') }}/tinymce/7/tinymce.min.js"
        referrerpolicy="origin"></script>
 
    <script>
        let isEditing = false;
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
        });

        $(document).ready(function() {
            setTimeout(loadRules, 300);
        });

        function loadRules() {
            // Updated API URL
            $.ajax({
                url: '/api/v1/rules-regulations',
                type: 'GET',
                success: function(res) {
                    // Changed Permission Check to 'rules_view'
                    let isAdmin = window.userGodMode || (window.userPerms && window.userPerms.includes('rules_view'));

                    if (isAdmin) {
                        $('.admin-top-bar').show();
                        $('.admin-view-container').fadeIn();
                        $('.user-view-container').hide();
                        $('.admin-print-btn-wrapper').show();

                        let tableHtml = '';
                        let cardsHtml = '';

                        res.data.forEach(rule => {
                            let badgeClass = rule.status === 'active' ? 'bg-success' : 'bg-danger';
                            let audienceBadge = rule.target_audience === 'employee' ? 'bg-info' : 'bg-warning text-dark';

                            tableHtml += `<tr>
                                <td>${rule.id}</td>
                                <td class="fw-bold">${rule.title}</td>
                                <td><span class="badge ${audienceBadge}">${rule.target_audience.toUpperCase()}</span></td>
                                <td><span class="badge ${badgeClass}">${rule.status.toUpperCase()}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-light border text-primary secured-item me-1" data-permission="rules_edit" onclick="editRule(${rule.id})"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-light border text-danger secured-item" data-permission="rules_delete" onclick="deleteRule(${rule.id})"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>`;

                            cardsHtml += `
                            <div class="card shadow-sm border-0 rounded-3 mb-3" style="background:#fff;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-secondary">ID: ${rule.id}</span>
                                        <div>
                                            <span class="badge ${audienceBadge} me-1">${rule.target_audience.toUpperCase()}</span>
                                            <span class="badge ${badgeClass}">${rule.status.toUpperCase()}</span>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold text-dark mt-2 mb-3 text-truncate">${rule.title}</h6>
                                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                                        <button class="btn btn-sm btn-outline-primary secured-item px-3 flex-grow-1" data-permission="rules_edit" onclick="editRule(${rule.id})"><i class="fas fa-edit me-1"></i> Edit</button>
                                        <button class="btn btn-sm btn-outline-danger secured-item px-3 flex-grow-1" data-permission="rules_delete" onclick="deleteRule(${rule.id})"><i class="fas fa-trash me-1"></i> Delete</button>
                                    </div>
                                </div>
                            </div>`;
                        });

                        $('#rulesList').html(tableHtml);
                        $('#rulesCardsList').html(cardsHtml);

                        if (typeof window.applyPermissions === 'function') window.applyPermissions();

                    } else {
                        applySecurityMeasures();

                        if ($(window).width() < 768) {
                            $('#rulesMainContainer').removeClass('py-2').addClass('p-0');
                        }

                        $('.admin-top-bar').hide();
                        $('.admin-view-container').hide();
                        $('.user-view-container').fadeIn();
                        $('.admin-print-btn-wrapper').remove(); 

                        if (res.data && res.data.length > 0) {
                            let rule = res.data[0];
                            let headerHtml = res.header_html || '';

                            let documentHtml = `
                            ${headerHtml}
                            <hr style="border-top: 2px solid #000; opacity: 1; margin-top: 2px; margin-bottom: 25px;">
                            <h4 class="text-center fw-bold mb-4" style="text-decoration: underline; text-transform: uppercase;">
                                ${rule.title}
                            </h4>
                            <div style="font-size: 15px; line-height: 1.8; text-align: justify;">
                                ${rule.content}
                            </div>
                        `;
                            $('#userReadingContent').html(documentHtml);
                        } else {
                            $('#userReadingContent').html(
                                '<div class="alert alert-warning text-center mt-5"><i class="fas fa-info-circle me-2"></i> No active Rules & Regulations have been published for your profile yet.</div>'
                            );
                        }
                    }
                },
                error: function() {
                    $('.user-view-container').fadeIn();
                    $('#userReadingContent').html(
                        '<div class="alert alert-danger text-center mt-5"><i class="fas fa-exclamation-triangle me-2"></i> Could not load rules. Access Denied or Network Error.</div>'
                    );
                }
            });
        }

        // 🔥 SECURITY PROTOCOL FUNCTION 🔥
        function applySecurityMeasures() {
            $('body').addClass('secure-print-block');
            $('#secureDocumentArea').addClass('secure-mode');

            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'PrintScreen') {
                    navigator.clipboard.writeText(''); 
                    e.preventDefault();
                }
                if (e.ctrlKey && (e.key === 'p' || e.key === 'P' || e.key === 's' || e.key === 'S' || e.key === 'c' || e.key === 'C')) {
                    e.preventDefault();
                }
                if (e.key === 'F12') {
                    e.preventDefault();
                }
            });

            window.addEventListener('blur', function() {
                $('#secureDocumentArea').addClass('secure-blur');
            });

            window.addEventListener('focus', function() {
                $('#secureDocumentArea').removeClass('secure-blur');
            });
        }

        // --- Admin CRUD Functions ---
        function openAddModal() {
            isEditing = false;
            $('#ruleId').val('');
            $('#title').val('');
            $('#target_audience').val('employee');
            $('#status').val('active');
            tinymce.get('tinymce-editor').setContent('');

            $('#modalTitle').text('Add Rules & Regulations');
            $('#ruleModal').modal('show');
        }

        function editRule(id) {
            $.ajax({
                url: `/api/v1/rules-regulations/${id}`,
                type: 'GET',
                success: function(res) {
                    let r = res.data;
                    isEditing = true;
                    $('#ruleId').val(r.id);
                    $('#title').val(r.title);
                    $('#target_audience').val(r.target_audience);
                    $('#status').val(r.status);

                    tinymce.get('tinymce-editor').setContent(r.content);

                    $('#modalTitle').text('Edit Rules & Regulations');
                    $('#ruleModal').modal('show');
                }
            });
        }

        function saveRule() {
            let payload = {
                title: $('#title').val(),
                target_audience: $('#target_audience').val(),
                content: tinymce.get('tinymce-editor').getContent(),
                status: $('#status').val()
            };

            let method = isEditing ? 'PUT' : 'POST';
            let url = isEditing ? `/api/v1/rules-regulations/${$('#ruleId').val()}` : '/api/v1/rules-regulations';

            $.ajax({
                url: url,
                type: method,
                data: payload,
                success: function(res) {
                    Swal.fire('Success', res.message, 'success');
                    $('#ruleModal').modal('hide');
                    loadRules();
                },
                error: function(err) {
                    Swal.fire('Error', 'Please fill all required fields properly!', 'error');
                }
            });
        }

        function deleteRule(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/api/v1/rules-regulations/${id}`,
                        type: 'DELETE',
                        success: function(res) {
                            Swal.fire('Deleted!', res.message, 'success');
                            loadRules();
                        }
                    });
                }
            });
        }
    </script>
@endpush