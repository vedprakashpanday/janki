@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        .table-custom th {
            background-color: var(--sidebar-bg);
            color: #fff;
            font-size: 13px;
            border: none;
            padding: 12px 15px;
        }

        .table-custom td {
            font-size: 13px;
            vertical-align: middle;
            padding: 12px 15px;
        }

        .mobile-item {
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px var(--shadow-color);
        }

        /* TinyMCE branding removal for clean look */
        .tox-promotion,
        .tox-statusbar__branding {
            display: none !important;
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Letterhead Management</h4>
            <button type="button" class="btn text-white px-3 py-2 shadow-sm secured-item" data-permission="ledger_add"
                style="background-color: var(--brand-primary);" onclick="openModal('add')">
                <i class="fas fa-plus-circle me-1"></i> Create Letterhead
            </button>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search Letterhead...">
            <button type="button" class="btn text-white shadow-sm" style="background-color: #10b981;"
                onclick="$('.buttons-excel').click()">
                <i class="fas fa-file-excel"></i>
            </button>
        </div>

        <div class="card border-0 shadow-sm mb-4 d-none d-md-block">
            <div class="card-body p-4 table-responsive">
                <table id="lhTable" class="table table-hover table-custom w-100">
                    <thead>
                        <tr>
                            <th>Ref No</th>
                            <th>Date</th>
                            <th>Assigned To</th>
                            <th>Subject</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div id="mobileCardsContainer" class="d-block d-md-none"></div>
    </div>

    <div class="modal fade" id="lhModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold" id="modalTitle" style="color: var(--sidebar-bg);">Create Letterhead</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="lhForm" class="row g-3">
                        <input type="hidden" id="edit_id">

                        <div class="row m-0 p-0">
                            <div class="col-md-4 mb-3">
                                <label>Letterhead ID / Ref No <span class="text-danger">*</span></label>
                                <input type="text" name="ref_no" id="ref_no"
                                    class="form-control fw-bold text-primary" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Select Company</label>
                                <select name="company_id" id="company_id" class="form-control">
                                    <option value="">-- Select Company --</option>
                                    <option value="global">Global (All Companies)</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Select Branch</label>
                                <select name="branch_id" id="branch_id" class="form-control">
                                    <option value="">Head Office</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Reference Year <span
                                    class="text-danger">*</span></label>
                            <input type="number" name="ref_year" id="f_year" class="form-control"
                                value="{{ date('Y') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Letter Date <span class="text-danger">*</span></label>
                            <input type="date" name="letter_date" id="f_date" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Employee ID (or 'All')</label>
                            <input type="text" name="emp_code" id="f_emp" class="form-control"
                                placeholder="Search ID or type All" list="staffList" autocomplete="off">
                            <datalist id="staffList"></datalist>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Subject</label>
                            <input type="text" name="subject" id="f_sub" class="form-control">
                        </div>

                        <div class="col-md-6 mt-3">
                            <label class="form-label small fw-bold">Paid To (Manual Name)</label>
                            <input type="text" name="paid_to" id="f_paid_to" class="form-control"
                                placeholder="Leave empty if ID is selected">
                        </div>
                        <div class="col-md-6 mt-3">
                            <label class="form-label small fw-bold">Paid To Address</label>
                            <input type="text" name="paid_to_address" id="f_paid_address" class="form-control">
                        </div>

                        <div class="col-12 mt-3">
                            <label class="form-label fw-bold text-primary"><i class="fas fa-edit me-1"></i> Letter Content
                                <span class="text-danger">*</span></label>
                            <textarea id="message_editor" name="message"></textarea>
                        </div>

                        <div class="col-12 text-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-secondary px-4 me-2"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn text-white px-5 shadow-sm fw-medium"
                                style="background-color: var(--brand-primary);" id="saveBtn">Save Letterhead</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="printModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i
                            class="fas fa-file-pdf me-2 text-danger"></i> Print Preview</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" style="height: 70vh;">
                    <iframe id="printFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success px-4 shadow-sm"
                        onclick="document.getElementById('printFrame').contentWindow.print()">
                        <i class="fas fa-print me-2"></i> Print Document
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/x34jz09l49eq4m2bh1dl4olj6a26dxjoly00pun0lmtla5pb/tinymce/8/tinymce.min.js"
        referrerpolicy="origin" crossorigin="anonymous"></script>

   <script>
        $(document).ready(function() {
            // === 1. SMART LOCAL STORAGE TOKEN SETUP ===
            // (Current URL ke hisaab se sahi token lega)
            let currentPath = window.location.pathname;
            let userToken = '';

            if (currentPath.includes('/admin')) {
                userToken = localStorage.getItem('admin_token') || localStorage.getItem('token');
            } else if (currentPath.includes('/employee')) {
                userToken = localStorage.getItem('emp_token') || localStorage.getItem('token');
            } else {
                userToken = localStorage.getItem('admin_token') || localStorage.getItem('emp_token') || localStorage.getItem('token');
            }

            if (!userToken) {
                console.error("Auth Token nahi mila! Kripya dobara login karein.");
            }

            $.ajaxSetup({
                headers: {
                    'Authorization': 'Bearer ' + userToken,
                    'Accept': 'application/json'
                }
            });

            let globalUserContext = null;
            let mode = 'add';
            let table;

            // === 2. INITIALIZE CONTEXT & DROPDOWNS ===
            function initContext() {
                // Get Next Ref No
                $.ajax({
                    url: '/api/v1/letterheads/next-ref', 
                    type: 'GET',
                    success: function(response) {
                        if(response.status === 'success' && !$('#ref_no').val()) {
                            $('#ref_no').val(response.next_ref_no);
                        }
                    }
                });

                // Get User Context
                $.ajax({
                    url: '/api/v1/context', 
                    type: 'GET',
                    success: function(context) {
                        console.log("Global Context Loaded:", context);
                        globalUserContext = context;
                        loadCompaniesAndSetAccess(context);
                    },
                    error: function(err) {
                        console.error("Context API Error:", err.responseJSON || err);
                    }
                });

                // Get Employees for DataList
                $.get({
                    url: '/api/v1/employees', 
                    success: function(res) {
                        if(res.data) {
                            let dlHtml = '<option value="All">All Staff</option>';
                            res.data.forEach(e => dlHtml += `<option value="${e.member_id}">${e.full_name}</option>`);
                            $('#staffList').html(dlHtml);
                        }
                    }
                });
            }

            function loadCompaniesAndSetAccess(context) {
                // Fetch Companies
                $.ajax({
                    url: '/api/v1/get-active-companies', 
                    type: 'GET',
                    success: function(res) {
                        console.log("Companies Loaded:", res.data);
                        let companies = res.data || [];
                        companies.forEach(function(comp) {
                            $('#company_id').append(`<option value="${comp.id}">${comp.company_name}</option>`);
                        });

                        // Role based restrictions (Employee/Member ke liye block karega)
                        // Kyunki admin_email is_god = true dega, toh admin ke liye block nahi hoga
                        if (context && !context.is_god && !context.is_director) {
                            $('#company_id').val(context.company_id).css('pointer-events', 'none').prop('readonly', true);
                            
                            loadBranches(context.company_id, function() {
                                let bId = context.branch_id ? context.branch_id : "";
                                $('#branch_id').val(bId).css('pointer-events', 'none').prop('readonly', true);
                            });
                        }
                    },
                    error: function(err) {
                        console.error("Companies API Error:", err.responseJSON || err);
                    }
                });
            }

            // Company Dropdown Change Logic
            $('#company_id').on('change', function(e, selectedBranchId = null) {
                let compId = $(this).val();
                let branchDropdown = $('#branch_id');
                
                branchDropdown.empty(); 

                if (compId === 'global') {
                    branchDropdown.append('<option value="all">All Branches</option>');
                    branchDropdown.css('pointer-events', 'none').prop('readonly', true);
                } else if (compId) {
                    branchDropdown.css('pointer-events', 'auto').prop('readonly', false);
                    branchDropdown.append('<option value="">Head Office</option>');
                    loadBranches(compId, null, selectedBranchId);
                } else {
                    branchDropdown.css('pointer-events', 'auto').prop('readonly', false);
                    branchDropdown.append('<option value="">Head Office</option>');
                }
            });

            function loadBranches(companyId, callback = null, selectedBranchId = null) {
                if (!companyId || companyId === 'global') return;
                
                $.ajax({
                    url: `/api/v1/branches?company_id=${companyId}`, 
                    type: 'GET',
                    success: function(res) {
                        let branches = res.data || [];
                        branches.forEach(function(branch) {
                            $('#branch_id').append(`<option value="${branch.id}">${branch.branch_name}</option>`);
                        });
                        
                        if(selectedBranchId) {
                            $('#branch_id').val(selectedBranchId);
                        }

                        if (callback) callback();
                    },
                    error: function(err) {
                        console.error("Branches API Error:", err);
                    }
                });
            }

            // Fire Init
            initContext();

            // === 3. TINYMCE SETUP ===
            tinymce.init({
                selector: '#message_editor',
                height: 400,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount'
                ],
                toolbar: 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image table code help',
                content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
                    let formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());

                    $.ajax({
                        url: '/api/v1/letterheads/upload-image', 
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function(response) {
                            resolve(response.location);
                        },
                        error: function() {
                            reject('Image upload failed.');
                        }
                    });
                })
            });

            // === 4. DATATABLE ===
            table = $('#lhTable').DataTable({
                ajax: {
                    url: '/api/v1/letterheads', 
                    dataSrc: function(json) {
                        renderMobileCards(json.data);
                        return json.data;
                    }
                },
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export',
                    className: 'btn btn-success btn-sm shadow-sm'
                }],
                columns: [
                    { data: 'ref_no', render: d => `<span class="fw-bold text-primary">${d}</span>` },
                    { data: 'letter_date' },
                    { data: 'emp_code', render: d => `<span class="badge bg-secondary">${d||'N/A'}</span>` },
                    { data: 'subject', render: d => d || '-' },
                    { data: 'id', render: d => `
                        <div class="text-end">
                            <button class="btn btn-sm btn-light text-success me-1 print-btn" data-id="${d}"><i class="fas fa-print"></i></button>
                            <button class="btn btn-sm btn-light text-primary shadow-sm edit-btn secured-item" data-permission="ledger_edit" title="Edit" data-id="${d}"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-light text-danger shadow-sm delete-btn secured-item" data-permission="ledger_delete" title="Delete" data-id="${d}"><i class="fas fa-trash-alt"></i></button>
                        </div>`
                    }
                ],
                drawCallback: function() {
                    if (typeof window.applyPermissions === 'function') window.applyPermissions();
                }
            });

            // === 5. MOBILE CARDS ===
            function renderMobileCards(data) {
                let html = '';
                data.forEach(item => {
                    html += `
                        <div class="mobile-item lh-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-bold text-primary mb-0">${item.ref_no}</h6>
                                    <small class="text-muted"><i class="far fa-calendar-alt"></i> ${item.letter_date}</small>
                                </div>
                                <span class="badge bg-secondary">${item.emp_code || 'N/A'}</span>
                            </div>
                            <div class="small text-dark mb-3"><b>Sub:</b> ${item.subject || '-'}</div>
                            <div class="pt-2 border-top d-flex gap-2">
                                <button class="btn btn-sm btn-light text-success fw-bold flex-fill print-btn" data-id="${item.id}"><i class="fas fa-print"></i></button>
                                <button class="btn btn-sm btn-light text-primary fw-bold flex-fill edit-btn secured-item" data-permission="ledger_edit" data-id="${item.id}"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-light text-danger fw-bold flex-fill delete-btn secured-item" data-permission="ledger_delete" data-id="${item.id}"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>`;
                });
                $('#mobileCardsContainer').html(html || '<p class="text-center text-muted">No Letterheads found.</p>');
                if (typeof window.applyPermissions === 'function') window.applyPermissions();
            }

            $('#mobileSearch').on('keyup', function() {
                let val = $(this).val().toLowerCase();
                $(".lh-card").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1)
                });
            });

            // === 6. MODAL OPEN/EDIT LOGIC ===
            window.openModal = function(type, id = null) {
                mode = type;
                $('#lhForm')[0].reset();
                $('#ref_no').prop('readonly', false); 

                if (tinymce.get('message_editor')) {
                    tinymce.get('message_editor').setContent('');
                }

                $('#modalTitle').text(type === 'add' ? 'Create Letterhead' : 'Edit Letterhead');

                if (type === 'add') {
                    $.ajax({
                        url: '/api/v1/letterheads/next-ref', 
                        success: function(response) {
                            if(response.status === 'success') $('#ref_no').val(response.next_ref_no);
                        }
                    });
                }

                if (type === 'edit') {
                    $.get({
                        url: `/api/v1/letterheads/${id}`,
                        success: function(res) {
                            let d = res.data;
                            $('#edit_id').val(d.id);
                            
                            $('#ref_no').val(d.ref_no).prop('readonly', true); 

                            let cId = d.company_id || 'global';
                            $('#company_id').val(cId);
                            
                            let bId = d.branch_id || (cId === 'global' ? 'all' : '');
                            $('#company_id').trigger('change', [bId]);

                            $('#f_year').val(d.ref_year);
                            $('#f_date').val(d.letter_date);
                            $('#f_emp').val(d.emp_code);
                            $('#f_sub').val(d.subject);
                            $('#f_paid_to').val(d.paid_to);
                            $('#f_paid_address').val(d.paid_to_address);

                            if (tinymce.get('message_editor')) {
                                tinymce.get('message_editor').setContent(d.message || '');
                            }
                        }
                    });
                }
                $('#lhModal').modal('show');
            };

            $(document).on('click', '.edit-btn', function() {
                openModal('edit', $(this).data('id'));
            });

            // === 7. FORM SUBMIT ===
            $('#lhForm').submit(function(e) {
                e.preventDefault();

                if (tinymce.get('message_editor')) {
                    tinymce.triggerSave();
                }

                let id = $('#edit_id').val();
                let url = mode === 'add' ? '/api/v1/letterheads' : `/api/v1/letterheads/${id}`;
                let type = mode === 'add' ? 'POST' : 'PUT';
                let btn = $('#saveBtn');
                
                btn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: url,
                    type: type,
                    data: $(this).serialize(),
                    success: function(res) {
                        alert(res.message);
                        $('#lhModal').modal('hide');
                        table.ajax.reload(null, false);
                    },
                    error: function(err) {
                        alert(err.responseJSON?.message || "Error occurred.");
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Save Letterhead');
                    }
                });
            });

            // === 8. DELETE ===
            $(document).on('click', '.delete-btn', function() {
                if (confirm("Are you sure?")) {
                    $.ajax({
                        url: `/api/v1/letterheads/${$(this).data('id')}`,
                        type: 'DELETE',
                        success: function() {
                            table.ajax.reload(null, false);
                        }
                    });
                }
            });

            // === 9. PRINT ===
            $(document).on('click', '.print-btn', function() {
                let id = $(this).data('id');
                $('#printFrame').attr('src', `/admin/letterheads/print/${id}`);
                $('#printModal').modal('show');
            });
        });
    </script>
@endpush
