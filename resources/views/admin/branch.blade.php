@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        /* Premium Table Styling */
        .table-custom th {
            background-color: var(--sidebar-bg);
            color: #fff;
            font-weight: 500;
            font-size: 14px;
            border: none;
        }

        .table-custom td {
            vertical-align: middle;
            font-size: 14px;
            border-bottom: 1px solid var(--border-color);
        }

        .dt-buttons .btn-success {
            background-color: #10b981;
            border: none;
        }

        /* Mobile Card Styling */
        .branch-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px var(--shadow-color);
            transition: transform 0.2s;
        }

        .branch-card:active {
            transform: scale(0.98);
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        #mapPreview,
        #edit_mapPreview {
            background: var(--bg-light);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed #cbd5e1;
            height: 120px;
        }

        #mapPreview iframe,
        #edit_mapPreview iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-none d-md-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Branch Management</h4>
                <p class="text-secondary small mb-0">Manage all your corporate and local branches</p>
            </div>
            <button class="btn text-white px-4 py-2 shadow-sm d-none" id="mainAddBranchBtn"
                style="background-color: var(--brand-primary);" data-bs-toggle="modal" data-bs-target="#addBranchModal">
                <i class="fas fa-plus-circle me-2"></i> <span id="addBtnText">Add New Branch</span>
            </button>
        </div>

        <div class="d-block d-md-none mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold m-0" style="color: var(--sidebar-bg);">Branches</h5>
                <button class="btn btn-sm text-white px-3 shadow-sm d-none" id="mobileAddBranchBtn"
                    style="background-color: var(--brand-primary);" data-bs-toggle="modal" data-bs-target="#addBranchModal">
                    <i class="fas fa-plus"></i> <span id="mobileAddBtnText">Add</span>
                </button>
            </div>
            <div class="d-flex gap-2">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0" id="mobileSearch"
                        placeholder="Search branches...">
                </div>
                <button class="btn btn-success shadow-sm px-3 d-none" id="mobileExcelBtn" title="Download Excel">
                    <i class="fas fa-file-excel"></i>
                </button>
            </div>
        </div>

        <div id="bulkActionContainer"
            class="d-none bg-light p-2 rounded border border-danger border-opacity-25 mb-3 d-flex align-items-center justify-content-between w-100">
            <div>
                <span class="me-3 fw-bold text-danger"><span id="selectedCount">0</span> Branches Selected</span>
                <button class="btn btn-sm btn-outline-secondary me-2 shadow-sm" id="selectAllBtn">Select All</button>
            </div>
            <button class="btn btn-sm btn-danger shadow-sm secured-item" data-permission="branch_delete" id="bulkDeleteBtn">
                <i class="fas fa-trash-alt me-1"></i> Delete Selected
            </button>
        </div>

        <div class="card border-0 shadow-sm rounded-3 d-none d-md-block">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="branchesTable" class="table table-hover table-custom w-100">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Branch ID</th>
                                <th>Branch Name</th>
                                <th>State & District</th>
                                <th>Opening Date</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-block d-md-none" id="mobileCardsContainer">
            <div class="text-center text-muted my-4" id="cardsLoader">
                <i class="fas fa-spinner fa-spin fs-2 mb-2"></i><br>Loading Branches...
            </div>
        </div>
    </div>

    <div class="modal fade" id="addBranchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-white border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i
                            class="fas fa-building me-2 text-primary"></i> Register Branch</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addBranchForm">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6 mb-3">
                                <label class="small fw-bold text-secondary">Assign Company <span
                                        class="text-danger">*</span></label>
                                <input class="form-control border-primary" list="company_datalist" id="company_input"
                                    placeholder="Type or select company..." required>
                                <datalist id="company_datalist"></datalist>
                                <input type="hidden" name="company_id" id="company_id_hidden" required>
                            </div>
                            <!-- Inside #addBranchForm -> <div class="row g-3 mb-3"> -->
<div class="col-md-6">
    <label class="form-label text-secondary small fw-bold">Branch Name <span class="text-danger">*</span></label>
    <input type="text" class="form-control" name="branch_name" required placeholder="e.g. South Delhi Office">
</div>
<!-- 🔥 NEW: Branch Code Field -->
<div class="col-md-6">
    <label class="form-label text-secondary small fw-bold">Branch Code</label>
    <input type="text" class="form-control" name="branch_code" placeholder="e.g. SD-01 (Optional)">
</div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">State <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="branch_state" required
                                    placeholder="e.g. Bihar">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">District <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="branch_district" required
                                    placeholder="e.g. Darbhanga">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Opening Date <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="opening_date" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Location Details</label>
                            <textarea class="form-control" name="branch_location" rows="2" placeholder="Full address of the branch"></textarea>
                        </div>
                       <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Google Map Location (Link or Iframe)</label>
                            <textarea class="form-control border-primary-subtle" id="add_map_url" name="map_url" rows="2" placeholder='Paste Google Map share link or <iframe> embed code here...'></textarea>
                            <small class="text-muted" style="font-size: 11px;">System will automatically extract Latitude & Longitude from this link.</small>
                            <div id="add_mapPreview" class="mt-2 text-muted small d-none">
                                <div class="text-center"><i class="fas fa-map-marked-alt fs-3 mb-1"></i><br>Map Preview</div>
                            </div>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn text-white py-2 fw-medium"
                                style="background-color: var(--sidebar-bg);" id="saveBtn">
                                <i class="fas fa-save me-2"></i> Save Branch
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editBranchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-white border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i
                            class="fas fa-edit me-2 text-primary"></i> Edit Branch</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editBranchForm">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row g-3 mb-3">
                            <div class="col-md-12 mb-3">
                                <label class="small fw-bold text-secondary">Assign Company <span
                                        class="text-danger">*</span></label>
                                <input class="form-control border-primary" list="edit_company_datalist"
                                    id="edit_company_input" placeholder="Type or select company..." required>
                                <datalist id="edit_company_datalist"></datalist>
                                <input type="hidden" name="company_id" id="edit_company_id_hidden" required>
                            </div>
                            <!-- Inside #editBranchForm -> <div class="row g-3 mb-3"> -->
<div class="col-md-6">
    <label class="small fw-bold text-secondary">Branch Name <span class="text-danger">*</span></label>
    <input type="text" class="form-control" name="branch_name" id="edit_branch_name" required>
</div>
<!-- 🔥 NEW: Edit Branch Code Field -->
<div class="col-md-6">
    <label class="small fw-bold text-secondary">Branch Code</label>
    <input type="text" class="form-control" name="branch_code" id="edit_branch_code">
</div>
                            <div class="col-md-4">
                                <label class="small fw-bold text-secondary">State <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="branch_state" id="edit_branch_state"
                                    required>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold text-secondary">District <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="branch_district"
                                    id="edit_branch_district" required>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold text-secondary">Opening Date <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="opening_date" id="edit_opening_date"
                                    required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-secondary">Location</label>
                            <textarea class="form-control" name="branch_location" id="edit_branch_location" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-secondary">Google Map Location (Link or Iframe)</label>
                            <textarea class="form-control border-primary-subtle" id="edit_map_url" name="map_url" rows="2"></textarea>
                            <div id="edit_mapPreview" class="mt-2 text-muted small d-none"></div>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-secondary">Status</label>
                            <select class="form-select" name="branch_status" id="edit_branch_status"></select>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn text-white py-2 fw-medium"
                                style="background-color: var(--sidebar-bg);" id="updateBtn">Update Branch Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewBranchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white border-bottom-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-building me-2"></i>Branch Details</h5>
                    <button type="button" class="btn-close btn-close-white shadow-none"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <table class="table table-bordered table-striped" style="table-layout: fixed; width: 100%;">
                        <tbody>
                            <tr>
                                <th width="35%">Branch ID (Code)</th>
                                <td id="v_branch_id" class="fw-bold text-primary fs-5 text-break"></td>
                            </tr>
                            <tr>
    <th>Branch Name</th>
    <td id="v_branch_name" class="fw-bold text-dark"></td>
</tr>
<!-- 🔥 NEW: View Branch Code -->
<tr>
    <th>Branch Code</th>
    <td id="v_branch_code" class="fw-bold text-dark"></td>
</tr>
                            <tr>
                                <th>Assigned Company</th>
                                <td id="v_company_name"></td>
                            </tr>
                            <tr>
                                <th>State & District</th>
                                <td id="v_location"></td>
                            </tr>
                            <tr>
                                <th>Opening Date</th>
                                <td id="v_opening_date"></td>
                            </tr>
                            <tr>
                                <th>Full Address</th>
                                <td id="v_address"></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td id="v_status"></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-3">
                        <h6 class="fw-bold text-secondary"><i class="fas fa-map-marked-alt me-1"></i> Map Location</h6>
                        <div id="v_map" class="rounded overflow-hidden"
                            style="height: 250px; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {

            const isEmployeePortal = window.location.pathname.includes('/employee');
            const apiToken = isEmployeePortal ? localStorage.getItem('emp_token') : localStorage.getItem(
                'admin_token');

            let userPermissions = [];
            let isGodOrDirector = false;
            let table;
            let companyMap = {};

            // 1. FETCH CONTEXT & PERMISSIONS FIRST
            $.ajax({
                url: '/api/v1/branches/ui-context',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + apiToken
                },
                success: function(res) {
                    userPermissions = res.permissions || [];
                    isGodOrDirector = res.is_god || res.is_director;

                    // Handle Add/Request Trigger Buttons
                    let hasDirect = userPermissions.includes('branch_add_direct') || isGodOrDirector;
                    let hasRequest = userPermissions.includes('branch_add_request');

                    if (hasDirect || hasRequest) {
                        $('#mainAddBranchBtn, #mobileAddBranchBtn').removeClass('d-none');
                        if (hasDirect) {
                            $('#addBtnText').text('Add New Branch');
                            $('#mobileAddBtnText').text('Add');
                            $('#saveBtn').html('<i class="fas fa-save me-2"></i> Save Branch');
                        } else {
                            $('#addBtnText').text('Request New Branch');
                            $('#mobileAddBtnText').text('Request');
                            $('#saveBtn').html(
                            '<i class="fas fa-paper-plane me-2"></i> Request Branch');
                        }
                    }

                    // Auto-lock Company fields for Single Company profiles
                    if (!res.is_god && res.company) {
                        let companyDisplayName =
                            `${res.company.company_name} - ${res.company.company_code}`;
                        $('#company_input, #edit_company_input').val(companyDisplayName).css(
                            'pointer-events', 'none').addClass('bg-light');
                        $('#company_id_hidden, #edit_company_id_hidden').val(res.company.id);
                    }

                    loadCompaniesForDropdown();
                    initDataTable();
                    loadMobileCards();
                }
            });

            // 2. DATATABLES ENGINE WITH DYNAMIC PERMISSIONS
            function initDataTable() {
           let buttonsArr = [];
        // Dynamic Excel Export View Permission
        if (isGodOrDirector || userPermissions.includes('branch_export')) {
            buttonsArr.push({
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel me-1"></i> Export Excel',
                className: 'btn btn-success btn-sm shadow-sm rounded-3',
                // 🔥 SMART EXPORT CONTROL LOGIC 🔥
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5], // 🔴 Column 6 (Actions) ko exclude kar diya
                    format: {
                        header: function (data, columnIdx) {
                            // 🟢 Pehle blank (Checkbox) header ko 'Sl. No.' bana diya
                            if (columnIdx === 0) {
                                return 'Sl. No.';
                            }
                            // Baki headers se agar koi HTML tag ho to use saaf karein
                            return data.replace(/<[^>]*>/g, "");
                        },
                        body: function (data, rowIdx, columnIdx, node) {
                            // 🟢 Checkbox data ki jagah serial wise No. (1, 2, 3...) print karein
                            if (columnIdx === 0) {
                                return rowIdx + 1;
                            }
                            // Baki cells me jo HTML tags hain (jaise span, text-muted), unhe strip karke clean text bhein
                            if (typeof data === 'string') {
                                return data.replace(/<[^>]*>/g, "").trim();
                            }
                            return data;
                        }
                    }
                }
            });
            $('#mobileExcelBtn').removeClass('d-none');
        }
                table = $('#branchesTable').DataTable({
                    order: [],
                    pageLength: 10,
                    ajax: {
                        url: '/api/v1/branches',
                        type: 'GET',
                        headers: {
                            'Authorization': 'Bearer ' + apiToken
                        },
                        dataSrc: 'data'
                    },
                    dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                    buttons: buttonsArr,
                    columns: [{
                            data: 'id',
                            orderable: false,
                            className: 'text-center align-middle',
                            render: function(data) {
                                return `<input type="checkbox" class="form-check-input branch-checkbox fs-5" value="${data}">`;
                            }
                        },
                        {
                            data: 'branch_id',
                            render: function(data) {
                                return `<span class="fw-bold" style="color:var(--brand-primary)">${data}</span>`;
                            }
                        },
                        {
                            data: 'branch_name',
                            render: function(data) {
                                return `<span class="fw-medium">${data}</span>`;
                            }
                        },
                        {
                            data: null,
                            render: function(data) {
                                return `<span class="small">${data.branch_district || '-'}, ${data.branch_state || '-'}</span>`;
                            }
                        },
                        {
                            data: 'opening_date',
                            render: function(data) {
                                return data ? new Date(data).toLocaleDateString('en-GB') :
                                    '<span class="text-muted small">N/A</span>';
                            }
                        },
                        {
                            data: 'branch_status',
                            render: function(data) {
                                if (data === 'pending')
                                return '<span class="status-badge bg-warning text-dark">Pending</span>';
                                return data === 'active' ?
                                    '<span class="status-badge status-active">Active</span>' :
                                    '<span class="status-badge bg-light text-secondary">Inactive</span>';
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            render: function(row) {
                                let actionHtml =
                                    `<div class="text-end">
                            <button class="btn btn-sm btn-light text-info me-1 shadow-sm view-btn" data-id="${row.id}" title="View"><i class="fas fa-eye"></i></button>`;

                                // Approve & Reject Buttons in Action column for Pending state
                                if (row.branch_status === 'pending') {
                                    if (isGodOrDirector || userPermissions.includes(
                                        'branch_appr')) {
                                        actionHtml +=
                                            `<button class="btn btn-sm btn-light text-success me-1 shadow-sm approve-btn" data-id="${row.id}" title="Approve"><i class="fas fa-check"></i></button>`;
                                    }
                                    if (isGodOrDirector || userPermissions.includes('branch_rej')) {
                                        actionHtml +=
                                            `<button class="btn btn-sm btn-light text-danger me-1 shadow-sm reject-btn" data-id="${row.id}" title="Reject"><i class="fas fa-times"></i></button>`;
                                    }
                                }

                                if (isGodOrDirector || userPermissions.includes('branch_edit')) {
                                    actionHtml +=
                                        `<button class="btn btn-sm btn-light text-primary me-1 shadow-sm edit-btn" data-id="${row.id}" title="Edit"><i class="fas fa-edit"></i></button>`;
                                }
                                actionHtml += `</div>`;
                                return actionHtml;
                            }
                        }
                    ],
                });
            }

            // 3. MOBILE ENGINE WITH ACTION OVERRIDES
            function loadMobileCards() {
                $.ajax({
                    url: '/api/v1/branches',
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(response) {
                        let html = '';
                        response.data.forEach(branch => {
                            let statusHtml = branch.branch_status === 'pending' ?
                                '<span class="status-badge bg-warning text-dark">Pending</span>' :
                                (branch.branch_status === 'active' ?
                                    '<span class="status-badge status-active">Active</span>' :
                                    '<span class="status-badge bg-light text-secondary">Inactive</span>'
                                    );

                            let editBtnHtml = '';
                            if (isGodOrDirector || userPermissions.includes('branch_edit')) {
                                editBtnHtml =
                                    `<button class="btn btn-sm btn-light text-primary flex-fill fw-medium edit-btn" data-id="${branch.id}"><i class="fas fa-edit me-1"></i> Edit</button>`;
                            }

                            let approvalRowHtml = '';
                            if (branch.branch_status === 'pending') {
                                if (isGodOrDirector || userPermissions.includes(
                                    'branch_appr')) {
                                    approvalRowHtml +=
                                        `<button class="btn btn-sm btn-success flex-fill fw-medium approve-btn" data-id="${branch.id}"><i class="fas fa-check me-1"></i> Approve</button>`;
                                }
                                if (isGodOrDirector || userPermissions.includes('branch_rej')) {
                                    approvalRowHtml +=
                                        `<button class="btn btn-sm btn-danger flex-fill fw-medium reject-btn" data-id="${branch.id}"><i class="fas fa-times me-1"></i> Reject</button>`;
                                }
                            }

                            html += `
                    <div class="branch-card mobile-card-item d-flex gap-2">
                        <div class="pt-1"><input class="form-check-input branch-checkbox fs-4" type="checkbox" value="${branch.id}"></div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div><h6 class="fw-bold mb-1" style="color: var(--sidebar-bg);">${branch.branch_name}</h6><div class="small fw-bold" style="color: var(--brand-primary);">${branch.branch_id}</div></div>
                                ${statusHtml}
                            </div>
                            <div class="small text-dark mb-1"><i class="fas fa-map-marker-alt me-1 text-muted"></i> ${branch.branch_district || '-'}, ${branch.branch_state || '-'}</div>
                            
                            ${approvalRowHtml ? `<div class="d-flex gap-2 mt-2 pt-2 border-top">${approvalRowHtml}</div>` : ''}

                            <div class="d-flex gap-2 border-top pt-2 mt-2">
                                <button class="btn btn-sm btn-light text-info flex-fill fw-medium view-btn" data-id="${branch.id}"><i class="fas fa-eye me-1"></i> View</button>
                                ${editBtnHtml}
                            </div>
                        </div>
                    </div>`;
                        });
                        $('#cardsLoader').hide();
                        $('#mobileCardsContainer').html(html);
                    }
                });
            }

            function loadCompaniesForDropdown() {
                $.ajax({
                    url: '/api/v1/get-active-companies',
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        let options = '';
                        res.data.forEach(c => {
                            let displayName = `${c.company_name} - ${c.company_code}`;
                            options += `<option value="${displayName}">`;
                            companyMap[displayName] = c.id;
                        });
                        $('#company_datalist').html(options);
                        $('#edit_company_datalist').html(options);
                    }
                });
            }

            $(document).on('input', '#company_input, #edit_company_input', function() {
                let val = $(this).val();
                let targetHidden = $(this).attr('id') === 'company_input' ? '#company_id_hidden' :
                    '#edit_company_id_hidden';
                $(targetHidden).val(companyMap[val] || '');
            });

            // APPROVE CLICK HANDLE
            $(document).on('click', '.approve-btn', function() {
                let id = $(this).data('id');
                if (!confirm('Are you sure you want to APPROVE this branch request?')) return;
                $.ajax({
                    url: `/api/v1/branches/${id}/approve`,
                    type: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        alert(res.message);
                        table.ajax.reload(null, false);
                        loadMobileCards();
                    }
                });
            });

            // REJECT CLICK HANDLE
            $(document).on('click', '.reject-btn', function() {
                let id = $(this).data('id');
                if (!confirm('Are you sure you want to REJECT this branch request?')) return;
                $.ajax({
                    url: `/api/v1/branches/${id}/reject`,
                    type: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        alert(res.message);
                        table.ajax.reload(null, false);
                        loadMobileCards();
                    }
                });
            });

            // UI Event binds, filters and CRUD maps
            $('#mobileSearch').on('keyup', function() {
                let value = $(this).val().toLowerCase();
                $('.mobile-card-item').each(function() {
                    let cardText = $(this).text().toLowerCase();
                    if (cardText.indexOf(value) > -1) $(this).removeClass('d-none');
                    else $(this).addClass('d-none');
                });
            });

            $('#mobileExcelBtn').on('click', function() {
                $('.buttons-excel').click();
            });


$('#add_map_url, #edit_map_url').on('input', function() {
                let inputVal = $(this).val();
                let previewBox = $(this).attr('id') === 'add_map_url' ? $('#add_mapPreview') : $('#edit_mapPreview');
                
                if (inputVal.includes('<iframe')) {
                    previewBox.html(inputVal).removeClass('text-muted d-none');
                } else if (inputVal !== '') {
                     previewBox.html('<div class="text-success small fw-bold"><i class="fas fa-check-circle"></i> URL Detected</div>').removeClass('d-none');
                } else {
                     previewBox.html('<div class="text-center"><i class="fas fa-map-marked-alt fs-3 mb-1"></i><br>Map Preview</div>').addClass('text-muted d-none');
                }
            });

           $('#addBranchForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#saveBtn');
        let originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Processing...').prop('disabled', true);

        // 🔥 NAYA LOGIC: Form reset hone se pehle company data save kar lo
        let isLocked = $('#company_input').css('pointer-events') === 'none';
        let savedCompanyText = $('#company_input').val();
        let savedCompanyId = $('#company_id_hidden').val();

        $.ajax({
            url: '/api/v1/branches',
            type: 'POST',
            headers: { 'Authorization': 'Bearer ' + apiToken },
            data: $(this).serialize(),
            success: function(response) {
                $('#addBranchModal').modal('hide');
                
                // Form reset karo (saare normal fields clear ho jayenge)
                $('#addBranchForm')[0].reset();
                
                // 🔥 NAYA LOGIC: Agar company locked thi, toh wapas usko bhar do
                if (isLocked) {
                    $('#company_input').val(savedCompanyText);
                    $('#company_id_hidden').val(savedCompanyId);
                }

                table.ajax.reload(null, false);
                loadMobileCards();
            },
            error: function() { alert('Something went wrong. Please check your inputs.'); },
            complete: function() { btn.html(originalText).prop('disabled', false); }
        });
    });

            $(document).on('click', '.view-btn', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: `/api/v1/branches/${id}`,
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        let data = res.data;
                        $('#v_branch_id').text(data.branch_id);
                        $('#v_branch_name').text(data.branch_name);
                        $('#v_branch_code').text(data.branch_code || '-');
                        $('#v_company_name').text(data.company ? data.company.company_name :
                            'Master Branch');
                        $('#v_location').text((data.branch_district || '-') + ', ' + (data
                            .branch_state || '-'));
                        $('#v_opening_date').text(new Date(data.opening_date)
                            .toLocaleDateString('en-GB'));
                        $('#v_address').text(data.branch_location || '-');
                        $('#v_status').html(data.branch_status === 'active' ?
                            '<span class="badge bg-success">Active</span>' : (data
                                .branch_status === 'pending' ?
                                '<span class="badge bg-warning text-dark">Pending</span>' :
                                '<span class="badge bg-danger">Inactive</span>'));

                      // 🔥 NAYA: View me map aur external link dono support
                        let mapContainer = $('#v_map');
                        if (data.map_url) {
                            if (data.map_url.includes('<iframe')) {
                                mapContainer.html(data.map_url.replace(/width="[^"]+"/, 'width="100%"').replace(/height="[^"]+"/, 'height="100%"'));
                            } else {
                                mapContainer.html(`<a href="${data.map_url}" target="_blank" class="btn btn-outline-primary shadow-sm"><i class="fas fa-external-link-alt me-2"></i> Open Location in Google Maps</a>`);
                            }
                        } else {
                            mapContainer.html('<div class="text-muted text-center"><i class="fas fa-map-marker-slash fs-3 mb-2"></i><br>No Map Found</div>');
                        }
                        $('#viewBranchModal').modal('show');
                    }
                });
            });

            $(document).on('click', '.edit-btn', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: `/api/v1/branches/${id}`,
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(response) {
                        let branch = response.data;
                        $('#edit_id').val(branch.id);
                        if (branch.company) {
                            let companyDisplayName =
                                `${branch.company.company_name} - ${branch.company.company_code}`;
                            $('#edit_company_input').val(companyDisplayName);
                            $('#edit_company_id_hidden').val(branch.company_id);
                        }
                        $('#edit_branch_name').val(branch.branch_name);
                        $('#edit_branch_code').val(branch.branch_code);
                        $('#edit_branch_state').val(branch.branch_state);
                        $('#edit_branch_district').val(branch.branch_district);
                        $('#edit_opening_date').val(branch.opening_date);
                        $('#edit_branch_location').val(branch.branch_location);

                        let hasDirect = userPermissions.includes('branch_add_direct') ||
                            isGodOrDirector;
                        if (!hasDirect) {
                            $('#edit_branch_status').html(
                                '<option value="pending">Pending</option>');
                            $('#edit_branch_status').val('pending').css('pointer-events',
                                'none').addClass('bg-light');
                        } else {
                            $('#edit_branch_status').html(
                                '<option value="active">Active</option><option value="inactive">Inactive</option><option value="pending">Pending</option>'
                                );
                            $('#edit_branch_status').val(branch.branch_status).css(
                                'pointer-events', 'auto').removeClass('bg-light');
                        }

                      // 🔥 NAYA: Naye DB structure ke hisab se data set karo
                        $('#edit_map_url').val(branch.map_url);
                        if (branch.map_url && branch.map_url.includes('<iframe')) {
                             $('#edit_mapPreview').html(branch.map_url).removeClass('d-none');
                        } else {
                             $('#edit_mapPreview').addClass('d-none');
                        }

                        $('#editBranchModal').modal('show');
                    }
                });
            });

            $('#editBranchForm').on('submit', function(e) {
                e.preventDefault();
                let id = $('#edit_id').val();
                let btn = $('#updateBtn');
                btn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);

                $.ajax({
                    url: `/api/v1/branches/${id}`,
                    type: 'PUT',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    data: $(this).serialize(),
                    success: function() {
                        $('#editBranchModal').modal('hide');
                        alert('Branch updated successfully!');
                        table.ajax.reload(null, false);
                        loadMobileCards();
                    },
                    error: function() {
                        alert('Failed to update branch.');
                    },
                    complete: function() {
                        btn.html('Update Branch Details').prop('disabled', false);
                    }
                });
            });

            // Bulk selection algorithms
            let selectedIds = [];

            function updateBulkActionUI() {
                selectedIds = [];
                $('.branch-checkbox:checked').each(function() {
                    let id = $(this).val();
                    if (!selectedIds.includes(id)) selectedIds.push(id);
                });
                if (selectedIds.length > 0) {
                    $('#bulkActionContainer').removeClass('d-none');
                    $('#selectedCount').text(selectedIds.length);
                } else {
                    $('#bulkActionContainer').addClass('d-none');
                }
            }

            $(document).on('change', '.branch-checkbox', function() {
                let val = $(this).val();
                let isChecked = $(this).prop('checked');
                $(`.branch-checkbox[value="${val}"]`).prop('checked', isChecked);
                updateBulkActionUI();
            });

            $('#selectAllBtn').on('click', function() {
                let isSelectingAll = $(this).text().trim() === 'Select All';
                $('.branch-checkbox').prop('checked', isSelectingAll);
                updateBulkActionUI();
                $(this).text(isSelectingAll ? 'Deselect All' : 'Select All');
            });

            $('#bulkDeleteBtn').on('click', function() {
                if (!confirm(`Are you sure you want to delete ${selectedIds.length} branch(es)?`)) return;
                let btn = $(this);
                btn.prop('disabled', true);
                $.ajax({
                    url: '/api/v1/branches/bulk-delete',
                    type: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    data: {
                        ids: selectedIds
                    },
                    success: function(response) {
                        alert(response.message);
                        table.ajax.reload(null, false);
                        loadMobileCards();
                        $('#bulkActionContainer').addClass('d-none');
                        $('#selectAllBtn').text('Select All');
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
