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
            position: relative;
        }

        /* TinyMCE branding removal */
        .tox-promotion, .tox-statusbar__branding { display: none !important; }

        /* Floating Bulk Action Bar */
        #bulkActionBar {
            transition: transform 0.3s ease-in-out;
            transform: translateY(100%);
            z-index: 1040;
            left: 0; 
            right: 0;
        }
        #bulkActionBar.show {
            transform: translateY(0);
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Letterhead (Today's Data)</h4>
            <div>
                <!-- 🛡️ Add Permission (Direct or Request) -->
                <button type="button" class="btn text-white px-3 py-2 shadow-sm secured-item" data-permission="letterhead_add_direct,letterhead_add_request"
                    style="background-color: var(--brand-primary);" onclick="openModal('add')">
                    <i class="fas fa-plus-circle me-1"></i> Create Letterhead
                </button>
            </div>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search Letterhead...">
            <button type="button" class="btn text-white shadow-sm secured-item" data-permission="letterhead_export" style="background-color: #10b981;"
                onclick="$('.buttons-excel').click()">
                <i class="fas fa-file-excel"></i>
            </button>
        </div>

        <div class="card border-0 shadow-sm mb-4 d-none d-md-block">
            <div class="card-body p-4 table-responsive">
                <table id="lhTable" class="table table-hover table-custom w-100">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="text-center"><input type="checkbox" id="selectAllMaster" class="form-check-input"></th>
                            <th>Ref No</th>
                            <th>Date</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div id="mobileCardsContainer" class="d-block d-md-none"></div>
    </div>

    <!-- 🔥 FLOATING BULK ACTION BAR 🔥 -->
    <div id="bulkActionBar" class="position-fixed bottom-0 w-100 bg-white border-top shadow-lg p-3 d-flex justify-content-between align-items-center">
        <div>
            <span class="fw-bold text-primary fs-5 me-2"><span id="selectedCount">0</span> Selected</span>
        </div>
        <div>
            <button class="btn btn-outline-secondary me-2" id="btnSelectAllFloating">Select All</button>
            
            <!-- 🔥 NAYA: Bulk Send Button -->
            <!-- Note: letterheads.blade.php me data-permission="letterhead_dir_send" rakhein -->
            <!-- Note: letterhead_daily.blade.php me data-permission="letterhead_send" rakhein -->
            <button class="btn text-white shadow-sm secured-item me-2" style="background-color: #0ea5e9;" data-permission="letterhead_send" id="btnBulkSend">
                <i class="fas fa-paper-plane me-1"></i> Send Selected
            </button>

            <button class="btn btn-danger shadow-sm secured-item" data-permission="letterhead_delete" id="btnBulkDelete">
                <i class="fas fa-trash-alt me-1"></i> Delete Selected
            </button>
        </div>
    </div>

    <!-- MAIN MODAL -->
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
                                <input type="text" name="ref_no" id="ref_no" class="form-control fw-bold text-primary" required>
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
                            <label class="form-label small fw-bold">Reference Year <span class="text-danger">*</span></label>
                            <input type="number" name="ref_year" id="f_year" class="form-control" value="{{ date('Y') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Letter Date <span class="text-danger">*</span></label>
                            <input type="date" name="letter_date" id="f_date" class="form-control" required>
                        </div>
                        
                        <!-- Form field inside modal -->
<div class="col-md-6">
    <label class="form-label small fw-bold">Assign To <small class="text-muted">(Type 3 letters to search)</small></label>
    <input type="text" name="emp_code" id="f_emp" class="form-control"
        placeholder="Select Group, Manual or Search Name/ID..." list="staffList" autocomplete="off" required>
    <datalist id="staffList">
        <option value="All Employees">All Employees</option>
        <option value="All Members">All Members</option>
        <option value="All Directors">All Directors</option>
        <option value="All CEOs">All CEOs</option>
        <option value="Manual">Manual</option>
    </datalist>
</div>

                        <div class="col-md-12 mt-3">
                            <label class="form-label small fw-bold">Subject</label>
                            <input type="text" name="subject" id="f_sub" class="form-control">
                        </div>

                        <div class="col-12 mt-3">
                            <label class="form-label fw-bold text-primary"><i class="fas fa-edit me-1"></i> Letter Content <span class="text-danger">*</span></label>
                            <textarea id="message_editor" name="message"></textarea>
                        </div>

                        <div class="col-12 text-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-secondary px-4 me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn text-white px-5 shadow-sm fw-medium" style="background-color: var(--brand-primary);" id="saveBtn">Save Letterhead</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- PRINT MODAL -->
    <div class="modal fade" id="printModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i class="fas fa-file-pdf me-2 text-danger"></i> Print Preview</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" style="height: 70vh;">
                    <iframe id="printFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success px-4 shadow-sm" onclick="document.getElementById('printFrame').contentWindow.print()">
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
    <script src="https://cdn.tiny.cloud/1/x34jz09l49eq4m2bh1dl4olj6a26dxjoly00pun0lmtla5pb/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>

    <script>
        $(document).ready(function() {
            // === 1. SMART LOCAL STORAGE TOKEN SETUP ===
            let currentPath = window.location.pathname;
            let userToken = localStorage.getItem(currentPath.includes('/admin') ? 'admin_token' : (currentPath.includes('/employee') ? 'emp_token' : 'token')) || localStorage.getItem('token');

            if (!userToken) console.error("Auth Token missing!");

            $.ajaxSetup({ headers: { 'Authorization': 'Bearer ' + userToken, 'Accept': 'application/json' }});

            let globalUserContext = null;
            let mode = 'add';
            let table;

            // === 2. INITIALIZE CONTEXT & DROPDOWNS (THE BRAIN) ===
            function initContext() {
                $.ajax({
                    url: '/api/v1/context', type: 'GET',
                    success: function(context) {
                        globalUserContext = context;
                        loadCompaniesAndSetAccess(context);
                    }
                });
            }

            function loadCompaniesAndSetAccess(context) {
                $.ajax({
                    url: '/api/v1/get-active-companies', type: 'GET',
                    success: function(res) {
                        let companies = res.data || [];
                        companies.forEach(comp => {
                            $('#company_id').append(`<option value="${comp.id}">${comp.company_name}</option>`);
                        });

                        let isAdmin = context.is_god || context.is_director;
                        
                        // 🧠 SMART LOCKING LOGIC
                        if (!isAdmin) {
                            if (context.company_id && !context.branch_id) {
                                // CASE: Sub-HO (Only Company Locked)
                                $('#company_id').val(context.company_id).css('pointer-events', 'none').prop('readonly', true);
                                loadBranches(context.company_id);
                            } else if (context.company_id && context.branch_id) {
                                // CASE: Specific Branch (Both Locked)
                                $('#company_id').val(context.company_id).css('pointer-events', 'none').prop('readonly', true);
                                loadBranches(context.company_id, function() {
                                    $('#branch_id').val(context.branch_id).css('pointer-events', 'none').prop('readonly', true);
                                });
                            }
                        }
                    }
                });
            }

            $('#company_id').on('change', function(e, selectedBranchId = null) {
                let compId = $(this).val();
                let branchDropdown = $('#branch_id');
                branchDropdown.empty(); 

                if (compId === 'global') {
                    branchDropdown.append('<option value="all">All Branches</option>').css('pointer-events', 'none').prop('readonly', true);
                } else if (compId) {
                    branchDropdown.css('pointer-events', 'auto').prop('readonly', false).append('<option value="">Head Office</option>');
                    loadBranches(compId, null, selectedBranchId);
                } else {
                    branchDropdown.css('pointer-events', 'auto').prop('readonly', false).append('<option value="">Head Office</option>');
                }
            });

            function loadBranches(companyId, callback = null, selectedBranchId = null) {
                if (!companyId || companyId === 'global') return;
                $.ajax({
                    url: `/api/v1/branches?company_id=${companyId}`, type: 'GET',
                    success: function(res) {
                        (res.data || []).forEach(branch => {
                            $('#branch_id').append(`<option value="${branch.id}">${branch.branch_name}</option>`);
                        });
                        if(selectedBranchId) $('#branch_id').val(selectedBranchId);
                        if (callback) callback();
                    }
                });
            }

            initContext();

            // === 3. TINYMCE SETUP ===
            tinymce.init({
                selector: '#message_editor', height: 400,
                plugins: ['advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview', 'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'media', 'table', 'help', 'wordcount'],
                toolbar: 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image table code help',
                images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
                    let formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());
                    $.ajax({
                        url: '/api/v1/letterheads/upload-image', type: 'POST', data: formData, contentType: false, processData: false,
                        success: res => resolve(res.location), error: () => reject('Image upload failed.')
                    });
                })
            });

            // === 4. DATATABLE (DAILY VIEW) ===
            table = $('#lhTable').DataTable({
                ajax: { 
                    url: '/api/v1/letterheads?filter=daily', // 🔥 ONLY DAILY DATA
                    dataSrc: function(json) { renderMobileCards(json.data); return json.data; }
                },
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export',
                    className: 'btn btn-success btn-sm shadow-sm secured-item',
                    attr: { 'data-permission': 'letterhead_export' } // 🔥 RBAC
                }],
                order: [[1, 'desc']], // Sort by ID/Date descending
                columns: [
                    { 
                        data: 'id', orderable: false, className: 'text-center',
                        render: d => `<input type="checkbox" class="row-checkbox form-check-input" value="${d}">`
                    },
                    { data: 'ref_no', render: d => `<span class="fw-bold text-primary">${d}</span>` },
                    { data: 'letter_date' },
                    { data: 'emp_code', render: d => `<span class="badge bg-secondary">${d||'N/A'}</span>` },
                    { 
                        data: 'status', 
                        render: d => {
                            if(d === 'active') return `<span class="badge bg-success">Active</span>`;
                            if(d === 'pending') return `<span class="badge bg-warning text-dark">Pending</span>`;
                            return `<span class="badge bg-danger">Inactive</span>`;
                        }
                    },
                    { 
                        data: null, className: 'text-end',
                        render: function(row) {
                            let btns = `<button class="btn btn-sm btn-light text-success me-1 print-btn secured-item" data-permission="letterhead_print" data-id="${row.id}"><i class="fas fa-print"></i></button>`;
                            
                            btns += `<button class="btn btn-sm btn-light text-primary me-1 edit-btn secured-item" data-permission="letterhead_edit" data-id="${row.id}"><i class="fas fa-edit"></i></button>`;
                            
                            btns += `<button class="btn btn-sm btn-light text-danger me-1 delete-btn secured-item" data-permission="letterhead_delete" data-id="${row.id}"><i class="fas fa-trash-alt"></i></button>`;
                            // NAYA: Send Button
                            btns += `<button class="btn btn-sm btn-light text-info me-1 send-btn secured-item" data-permission="letterhead_send" data-id="${row.id}" title="Send Notice"><i class="fas fa-paper-plane"></i></button>`;
                            
                            if(row.status === 'pending') {
                                btns += `<button class="btn btn-sm btn-success me-1 approve-btn secured-item" data-permission="letterhead_appr" data-id="${row.id}" title="Approve"><i class="fas fa-check-circle"></i></button>`;
                                btns += `<button class="btn btn-sm btn-danger me-1 reject-btn secured-item" data-permission="letterhead_rej" data-id="${row.id}" title="Reject"><i class="fas fa-times-circle"></i></button>`;
                            }
                            return btns;
                        }
                    }
                ],
                drawCallback: function() {
                    handleCheckboxLogic();
                    if (typeof window.applyPermissions === 'function') window.applyPermissions();
                }
            });

            // === 5. MOBILE CARDS ===
            function renderMobileCards(data) {
                let html = '';
                data.forEach(item => {
                    let stBadge = item.status === 'active' ? 'bg-success' : (item.status === 'pending' ? 'bg-warning text-dark' : 'bg-danger');
                    html += `
                        <div class="mobile-item lh-card">
                            <div class="position-absolute top-0 end-0 p-2">
                                <input type="checkbox" class="row-checkbox form-check-input" value="${item.id}">
                            </div>
                            <div class="d-flex justify-content-between align-items-start mb-2 pe-4">
                                <div>
                                    <h6 class="fw-bold text-primary mb-0">${item.ref_no}</h6>
                                    <small class="text-muted"><i class="far fa-calendar-alt"></i> ${item.letter_date}</small>
                                </div>
                                <span class="badge ${stBadge}">${item.status.toUpperCase()}</span>
                            </div>
                            <div class="small text-dark mb-3"><b>Assigned:</b> ${item.emp_code || 'N/A'}</div>
                            <div class="pt-2 border-top d-flex flex-wrap gap-2">
                                <button class="btn btn-sm btn-light text-success fw-bold flex-fill print-btn secured-item" data-permission="letterhead_print" data-id="${item.id}"><i class="fas fa-print"></i></button>
                               // NAYA: Send Button
                                <button class="btn btn-sm btn-light text-info fw-bold flex-fill send-btn secured-item" data-permission="letterhead_send" data-id="${item.id}"><i class="fas fa-paper-plane"></i></button>
                                <button class="btn btn-sm btn-light text-primary fw-bold flex-fill edit-btn secured-item" data-permission="letterhead_edit" data-id="${item.id}"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-light text-danger fw-bold flex-fill delete-btn secured-item" data-permission="letterhead_delete" data-id="${item.id}"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>`;
                });
                $('#mobileCardsContainer').html(html || '<p class="text-center text-muted">No Letterheads found.</p>');
                handleCheckboxLogic();
                if (typeof window.applyPermissions === 'function') window.applyPermissions();
            }

            // === 6. FLOATING BAR & CHECKBOX LOGIC ===
            function handleCheckboxLogic() {
                let checkedCount = $('.row-checkbox:checked').length;
                $('#selectedCount').text(checkedCount);
                if (checkedCount > 0) {
                    $('#bulkActionBar').addClass('show');
                } else {
                    $('#bulkActionBar').removeClass('show');
                    $('#selectAllMaster').prop('checked', false);
                }
            }

            $(document).on('change', '.row-checkbox', handleCheckboxLogic);
            
            $('#selectAllMaster').on('change', function() {
                $('.row-checkbox').prop('checked', this.checked);
                handleCheckboxLogic();
            });

            $('#btnSelectAllFloating').click(function() {
                let allChecked = $('.row-checkbox:checked').length === $('.row-checkbox').length;
                $('.row-checkbox').prop('checked', !allChecked);
                $('#selectAllMaster').prop('checked', !allChecked);
                handleCheckboxLogic();
            });

            $('#btnBulkDelete').click(function() {
                let ids = $('.row-checkbox:checked').map(function() { return $(this).val(); }).get();
                if (ids.length === 0) return;
                
                if (confirm(`Are you sure you want to delete ${ids.length} letterheads?`)) {
                    $.post('/api/v1/letterheads/bulk-delete', { ids: ids }, function(res) {
                        alert(res.message);
                        table.ajax.reload(null, false);
                        $('#bulkActionBar').removeClass('show');
                    }).fail(err => alert("Error deleting records."));
                }
            });

            // === 7. MODAL LOGIC & SEARCH ===
            window.openModal = function(type, id = null) {
                mode = type;
                $('#lhForm')[0].reset();
                $('#ref_no').prop('readonly', false); 
                if (tinymce.get('message_editor')) tinymce.get('message_editor').setContent('');
                $('#modalTitle').text(type === 'add' ? 'Create Letterhead' : 'Edit Letterhead');

                if (type === 'add') {
                    $.get('/api/v1/letterheads/next-ref', res => { if(res.status === 'success') $('#ref_no').val(res.next_ref_no); });
                }

                if (type === 'edit') {
                    $.get(`/api/v1/letterheads/${id}`, res => {
                        let d = res.data;
                        $('#edit_id').val(d.id);
                        $('#ref_no').val(d.ref_no).prop('readonly', true); 
                        let cId = d.company_id || 'global';
                        $('#company_id').val(cId);
                        $('#company_id').trigger('change', [d.branch_id || (cId === 'global' ? 'all' : '')]);
                        $('#f_year').val(d.ref_year); $('#f_date').val(d.letter_date);
                        $('#f_emp').val(d.emp_code); $('#f_sub').val(d.subject);
                        if (tinymce.get('message_editor')) tinymce.get('message_editor').setContent(d.message || '');
                    });
                }
                $('#lhModal').modal('show');
            };

           // === DYNAMIC SEARCH LOGIC ===
            $('#f_emp').on('keyup', function() {
                let q = $(this).val();
                
                let defaultOptions = `
                    <option value="All Employees">All Employees</option>
                    <option value="All Members">All Members</option>
                    <option value="All Directors">All Directors</option>
                    <option value="All CEOs">All CEOs</option>
                    <option value="Manual">Manual</option>
                `;

                // Agar 3 se kam characters hain, toh default dikhao
                if (q.length < 3) { 
                    $('#staffList').html(defaultOptions); 
                    return; 
                }

                // Agar inme se koi group likha ja raha hai toh API call mat karo
                let skipWords = ['all employees', 'all members', 'all directors', 'all ceos', 'manual'];
                if (skipWords.includes(q.toLowerCase())) return;

                // API Call for dynamic search
                $.ajax({
                    url: '/api/v1/letterheads/search-entities?q=' + q,
                    type: 'GET',
                    success: function(res) {
                        let opts = defaultOptions; // Naye results ke sath defaults bhi rakhte hain
                        res.forEach(i => opts += `<option value="${i.id}">${i.text}</option>`);
                        $('#staffList').html(opts);
                    }
                });
            });

            // === 8. CRUD ACTIONS ===
            $(document).on('click', '.edit-btn', function() { openModal('edit', $(this).data('id')); });

            $('#lhForm').submit(function(e) {
                e.preventDefault();
                if (tinymce.get('message_editor')) tinymce.triggerSave();
                
                let id = $('#edit_id').val();
                let url = mode === 'add' ? '/api/v1/letterheads' : `/api/v1/letterheads/${id}`;
                let type = mode === 'add' ? 'POST' : 'PUT';
                let btn = $('#saveBtn'); btn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: url, type: type, data: $(this).serialize(),
                    success: res => { alert(res.message); $('#lhModal').modal('hide'); table.ajax.reload(null, false); },
                    error: err => alert(err.responseJSON?.message || "Error occurred."),
                    complete: () => btn.prop('disabled', false).text('Save Letterhead')
                });
            });

            $(document).on('click', '.delete-btn', function() {
                if (confirm("Are you sure?")) {
                    $.ajax({ url: `/api/v1/letterheads/${$(this).data('id')}`, type: 'DELETE', success: () => table.ajax.reload(null, false) });
                }
            });

            $(document).on('click', '.print-btn', function() {
                $('#printFrame').attr('src', `/admin/letterheads/print/${$(this).data('id')}`);
                $('#printModal').modal('show');
            });

            $(document).on('click', '.approve-btn', function() {
                $.post(`/api/v1/letterheads/${$(this).data('id')}/approve`, res => { alert(res.message); table.ajax.reload(null, false); });
            });

            $(document).on('click', '.reject-btn', function() {
                $.post(`/api/v1/letterheads/${$(this).data('id')}/reject`, res => { alert(res.message); table.ajax.reload(null, false); });
            });


     // ==========================================
            // 🔥 SEND TO NOTICE BOARD LOGIC (WITH REPLY OPTION)
            // ==========================================
            
            // 1. INDIVIDUAL SEND BUTTON
            $(document).off('click', '.send-btn').on('click', '.send-btn', function(e) {
                e.preventDefault();
                let id = $(this).data('id');
                let btn = $(this);

                Swal.fire({
                    title: '<i class="fas fa-paper-plane text-info"></i> Send Notice',
                    html: '<b>Do you want a reply/acknowledgment for this letterhead?</b>',
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check-circle"></i> Yes, Require Reply',
                    denyButtonText: '<i class="fas fa-paper-plane"></i> No, Just Send',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#10b981', // Success Green
                    denyButtonColor: '#0ea5e9',    // Info Blue
                }).then((result) => {
                    if (result.isConfirmed || result.isDenied) {
                        let requiresReply = result.isConfirmed ? 1 : 0; // 1 for Yes, 0 for No
                        
                        let originalHtml = btn.html();
                        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

                        $.ajax({
                            url: '/api/v1/letterheads/send',
                            type: 'POST',
                            data: { ids: [id], requires_reply: requiresReply },
                            success: function(res) {
                                Swal.fire('Sent!', res.message, 'success');
                                if(typeof table !== 'undefined') table.ajax.reload(null, false);
                            },
                            error: function(err) {
                                Swal.fire('Error', err.responseJSON?.message || "Error sending notice.", 'error');
                            },
                            complete: function() {
                                btn.html(originalHtml).prop('disabled', false);
                            }
                        });
                    }
                });
            });

            // 2. BULK SEND BUTTON
            $(document).off('click', '#btnBulkSend').on('click', '#btnBulkSend', function(e) {
                e.preventDefault();
                let ids = $('.row-checkbox:checked').map(function() { return $(this).val(); }).get();
                
                if (ids.length === 0) {
                    Swal.fire('Warning', 'Please select at least one letterhead.', 'warning');
                    return;
                }
                
                Swal.fire({
                    title: `<i class="fas fa-paper-plane text-info"></i> Send ${ids.length} Notices`,
                    html: '<b>Do you want a reply/acknowledgment for these letterheads?</b>',
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check-circle"></i> Yes, Require Reply',
                    denyButtonText: '<i class="fas fa-paper-plane"></i> No, Just Send',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#10b981',
                    denyButtonColor: '#0ea5e9',
                }).then((result) => {
                    if (result.isConfirmed || result.isDenied) {
                        let requiresReply = result.isConfirmed ? 1 : 0;
                        
                        let btn = $('#btnBulkSend');
                        let originalHtml = btn.html();
                        btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Sending...').prop('disabled', true);

                        $.ajax({
                            url: '/api/v1/letterheads/send',
                            type: 'POST',
                            data: { ids: ids, requires_reply: requiresReply },
                            success: function(res) {
                                Swal.fire('Sent!', res.message, 'success');
                                if(typeof table !== 'undefined') table.ajax.reload(null, false);
                                $('#bulkActionBar').removeClass('show');
                                $('#selectAllMaster').prop('checked', false);
                            },
                            error: function(err) {
                                Swal.fire('Error', err.responseJSON?.message || "Error sending notices.", 'error');
                            },
                            complete: function() {
                                btn.html(originalHtml).prop('disabled', false);
                            }
                        });
                    }
                });
            });

        });
    </script>
@endpush