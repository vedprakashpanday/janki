@extends('layout.app') @section('content')
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

        /* Map Preview Container */
        #mapPreview {
            background: var(--bg-light);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed #cbd5e1;
            height: 150px;
        }

        #mapPreview iframe {
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
            <button class="btn text-white px-4 py-2 shadow-sm secured-item" data-permission="branch_add" style="background-color: var(--brand-primary);" data-bs-toggle="modal" data-bs-target="#addBranchModal">
    <i class="fas fa-plus-circle me-2"></i> Add New Branch
</button>
        </div>

        <div class="d-block d-md-none mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold m-0" style="color: var(--sidebar-bg);">Branches</h5>
                <button class="btn btn-sm text-white px-3 shadow-sm secured-item" data-permission="branch_add" style="background-color: var(--brand-primary);" data-bs-toggle="modal" data-bs-target="#addBranchModal">
    <i class="fas fa-plus"></i> Add
</button>
            </div>
            <div class="d-flex gap-2">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0" id="mobileSearch"
                        placeholder="Search branches...">
                </div>
                <button class="btn btn-success shadow-sm px-3" id="mobileExcelBtn" title="Download Excel">
                    <i class="fas fa-file-excel"></i>
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 d-none d-md-block">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="branchesTable" class="table table-hover table-custom w-100">
                        <thead>
                            <tr>
                                <th>Branch ID</th>
                                <th>Branch Name</th>
                                <th>State & District</th>
                                <th>Opening Date</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
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
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);">
                        <i class="fas fa-building me-2 text-primary"></i> Register Branch
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addBranchForm">
                        <div class="row g-3 mb-3">
                                      <div class="col-md-6 mb-3">
    <label class="small fw-bold text-secondary">Assign Company <span class="text-danger">*</span></label>
    <select class="form-select border-primary" id="company_dropdown" name="company_id" required>
        <option value="">-- Loading Companies... --</option>
    </select>
    <small class="text-muted">Branch code will be generated based on this company.</small>
</div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold">Branch Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="branch_name" required
                                    placeholder="e.g. South Delhi Office">
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
                            <label class="form-label text-secondary small fw-bold">Google Map Iframe (Optional)</label>
                            <input type="text" class="form-control" id="mapInput" name="branch_map"
                                placeholder="Paste <iframe...> here">
                            <div id="mapPreview" class="mt-2 text-muted small"
                                style="height: 120px; border: 1px dashed #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <div class="text-center"><i class="fas fa-map-marked-alt fs-3 mb-1"></i><br>Map Preview
                                </div>
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
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);">
                        <i class="fas fa-edit me-2 text-primary"></i> Edit Branch
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editBranchForm">
                        <input type="hidden" name="id" id="edit_id">

                        <div class="row g-3 mb-3">
                            <div class="col-md-12 mb-3">
    <label class="small fw-bold text-secondary">Assign Company <span class="text-danger">*</span></label>
    <select class="form-select border-primary" id="edit_company_dropdown" name="company_id" required>
        <option value="">-- Loading Companies... --</option>
    </select>
    <small class="text-muted">Branch code will be generated based on this company.</small>
</div>
                            <div class="col-md-12">
                                <label class="small fw-bold text-secondary">Branch Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="branch_name" id="edit_branch_name"
                                    required>
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
                            <label class="small fw-bold text-secondary">Google Map Iframe</label>
                            <input type="text" class="form-control" id="edit_mapInput" name="branch_map">
                            <div id="edit_mapPreview" class="mt-2"
                                style="height: 120px; border: 1px dashed #ddd; border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-secondary">Status</label>
                            <select class="form-select" name="branch_status" id="edit_branch_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn text-white py-2 fw-medium"
                                style="background-color: var(--sidebar-bg);" id="updateBtn">
                                Update Branch Details
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>



    <!-- View Branch Modal -->
<div class="modal fade" id="viewBranchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-bottom-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-building me-2"></i>Branch Details</h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr><th width="35%">Branch ID (Code)</th><td id="v_branch_id" class="fw-bold text-primary fs-5"></td></tr>
                        <tr><th>Branch Name</th><td id="v_branch_name" class="fw-bold text-dark"></td></tr>
                        <tr><th>Assigned Company</th><td id="v_company_name"></td></tr>
                        <tr><th>State & District</th><td id="v_location"></td></tr>
                        <tr><th>Opening Date</th><td id="v_opening_date"></td></tr>
                        <tr><th>Full Address</th><td id="v_address"></td></tr>
                        <tr><th>Status</th><td id="v_status"></td></tr>
                    </tbody>
                </table>
                <div class="mt-3">
                    <h6 class="fw-bold text-secondary"><i class="fas fa-map-marked-alt me-1"></i> Map Location</h6>
                    <div id="v_map" class="rounded overflow-hidden" style="height: 250px; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; background: #f8f9fa;"></div>
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





            const apiToken = localStorage.getItem('admin_token');

            // 1. Initialize DataTable (Desktop)
            let table = $('#branchesTable').DataTable({
                order: [], 
                pageLength: 10,
                ajax: {
                    url: '/api/v1/branches',
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    dataSrc: 'data' // Controller me response->json(['data' => $branches]) hai
                },
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export Excel',
                    className: 'btn btn-success btn-sm shadow-sm rounded-3'
                }],
                columns: [{
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

                    // NAYA FIELD: State & District (Ek hi column me dikha sakte hain space bachane ke liye)
                    {
                        data: null,
                        render: function(data) {
                            return `<span class="small">${data.branch_district}, ${data.branch_state}</span>`;
                        }
                    },

                    // NAYA FIELD: Opening Date
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
                            return data === 'active' ?
                                '<span class="status-badge status-active">Active</span>' :
                                '<span class="status-badge bg-light text-secondary">Inactive</span>';
                        }
                    },
                  { 
                    data: 'id', orderable: false, render: function(data) {
        return `
            <div class="text-end">
               <button class="btn btn-sm btn-light text-info me-1 shadow-sm view-btn" data-id="${data}" title="View"><i class="fas fa-eye"></i></button>
               <button class="btn btn-sm btn-light text-primary me-1 shadow-sm edit-btn secured-item" data-permission="branch_edit" data-id="${data}"><i class="fas fa-edit"></i></button>
<button class="btn btn-sm btn-light text-danger shadow-sm delete-btn secured-item" data-permission="branch_delete" data-id="${data}"><i class="fas fa-trash-alt"></i></button>
            </div>`;
    }}
                ]
            });

            // 2. Fetch and Render Mobile Cards
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
                            html += `
                <div class="branch-card mobile-card-item">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold mb-1" style="color: var(--sidebar-bg);">${branch.branch_name}</h6>
                            <div class="small fw-bold" style="color: var(--brand-primary);">${branch.branch_id}</div>
                        </div>
                        ${branch.branch_status === 'active' ? '<span class="status-badge status-active">Active</span>' : '<span class="status-badge bg-light text-secondary">Inactive</span>'}
                    </div>
                    
                    <div class="small text-dark mb-1">
                        <i class="fas fa-map-marker-alt me-1 text-muted"></i> ${branch.branch_district}, ${branch.branch_state}
                    </div>
                    <div class="small text-muted mb-2">
                        <i class="fas fa-calendar-alt me-1"></i> Opened: ${new Date(branch.opening_date).toLocaleDateString('en-GB')}
                    </div>

                   <div class="d-flex gap-2 border-top pt-2 mt-2">
      <button class="btn btn-sm btn-light text-info flex-fill fw-medium view-btn" data-id="${branch.id}"><i class="fas fa-eye me-1"></i> View</button>
        <button class="btn btn-sm btn-light text-primary flex-fill fw-medium edit-btn secured-item" data-permission="branch_edit" data-id="${branch.id}"><i class="fas fa-edit me-1"></i> Edit</button>
<button class="btn btn-sm btn-light text-danger flex-fill fw-medium delete-btn secured-item" data-permission="branch_delete" data-id="${branch.id}"><i class="fas fa-trash-alt me-1"></i> Delete</button>
    </div>
                </div>`;
                        });
                        $('#cardsLoader').hide();
                        $('#mobileCardsContainer').html(html);
                    }
                });
            }
            loadMobileCards(); // Initial load

            // 3. Mobile Search Logic
            $('#mobileSearch').on('keyup', function() {
                let value = $(this).val().toLowerCase();
                $('.mobile-card-item').filter(function() {
                    // Search based on branch name or ID text inside the card
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // 4. Mobile Excel Button triggers Desktop Excel Button
            $('#mobileExcelBtn').on('click', function() {
                $('.buttons-excel').click(); // DT ke hidden button ko click karwa do
            });

            // 5. Google Map Live Preview Logic
            $('#mapInput').on('input', function() {
                let inputVal = $(this).val();
                if (inputVal.includes('<iframe')) {
                    // Extract iframe and clean it
                    $('#mapPreview').html(inputVal).removeClass('text-muted');
                } else if (inputVal === '') {
                    $('#mapPreview').html('<i class="fas fa-map-marked-alt fs-3 mb-1"></i><br>Map Preview')
                        .addClass('text-muted');
                } else {
                    $('#mapPreview').html(
                        '<span class="text-danger small">Please paste a valid Google Maps iframe</span>'
                        ).addClass('text-muted');
                }
            });

            // 6. Submit Add Branch Form via AJAX
            $('#addBranchForm').on('submit', function(e) {
                e.preventDefault();
                let btn = $('#saveBtn');
                let originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Saving...').prop('disabled', true);

                $.ajax({
                    url: '/api/v1/branches',
                    type: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    data: $(this).serialize(),
                    success: function(response) {
                        // Modal band karo aur form reset karo
                        $('#addBranchModal').modal('hide');
                        $('#addBranchForm')[0].reset();
                        $('#mapPreview').html(
                            '<i class="fas fa-map-marked-alt fs-3 mb-1"></i><br>Map Preview'
                            ); // Reset preview

                        // Table aur Cards dono ko reload karo
                        table.ajax.reload(null, false);
                        loadMobileCards();
                    },
                    error: function(xhr) {
                        alert('Something went wrong. Please check your inputs.');
                    },
                    complete: function() {
                        btn.html(originalText).prop('disabled', false);
                    }
                });
            });

            // --- 2. EDIT: Data Fetch Karna (Modal kholne par) ---
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

                        $('#edit_company_dropdown').val(branch.company_id);
                        $('#edit_branch_name').val(branch.branch_name);

                        // Nayi lines yahan add karein 👇
                        $('#edit_branch_state').val(branch.branch_state);
                        $('#edit_branch_district').val(branch.branch_district);
                        $('#edit_opening_date').val(branch.opening_date);

                        $('#edit_branch_location').val(branch.branch_location);
                        $('#edit_branch_status').val(branch.branch_status);
                        $('#edit_mapInput').val(branch.branch_map);

                        if (branch.branch_map) {
                            $('#edit_mapPreview').html(branch.branch_map);
                        } else {
                            $('#edit_mapPreview').html(
                                '<div class="h-100 d-flex align-items-center justify-content-center text-muted small">No map available</div>'
                                );
                        }

                        $('#editBranchModal').modal('show');
                    }
                });
            });

            // --- 3. UPDATE: Data Save Karna (PUT Request) ---
            $('#editBranchForm').on('submit', function(e) {
                e.preventDefault();
                let id = $('#edit_id').val();
                let btn = $('#updateBtn');
                btn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);

                $.ajax({
                    url: `/api/v1/branches/${id}`,
                    type: 'PUT', // Resource controller ka update method PUT/PATCH use karta hai
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#editBranchModal').modal('hide');
                        alert('Branch updated successfully!');
                        table.ajax.reload(null, false); // DataTable reload
                        loadMobileCards(); // Mobile cards reload
                    },
                    error: function() {
                        alert('Failed to update branch.');
                    },
                    complete: function() {
                        btn.html('Update Branch Details').prop('disabled', false);
                    }
                });
            });

            // --- 4. DELETE: Data Remove Karna (DELETE Request) ---
            $(document).on('click', '.delete-btn', function() {
                let id = $(this).data('id');

                if (confirm('Do You Want to Delete This Branch?')) {
                    $.ajax({
                        url: `/api/v1/branches/${id}`,
                        type: 'DELETE',
                        headers: {
                            'Authorization': 'Bearer ' + apiToken
                        },
                        success: function(response) {
                            alert('Branch deleted successfully!');
                            table.ajax.reload(null, false);
                            loadMobileCards();
                        },
                        error: function() {
                            alert('Error in deleting branch.');
                        }
                    });
                }
            });

            // Map Input Preview for Edit Modal
            $('#edit_mapInput').on('input', function() {
                let val = $(this).val();
                if (val.includes('<iframe')) {
                    $('#edit_mapPreview').html(val);
                }
            });

function loadCompaniesForDropdown() {
        $.ajax({
            url: '/api/v1/get-active-companies', // Humne pichle step me banaya tha
            type: 'GET',
            headers: { 'Authorization': 'Bearer ' + apiToken },
            success: function(res) {
                let options = '<option value="">-- Select Company --</option>';
                res.data.forEach(c => {
                    options += `<option value="${c.id}">${c.company_name} (${c.company_code})</option>`;
                });
                $('#company_dropdown').html(options);
                $('#edit_company_dropdown').html(options); // Edit modal ke liye
            }
        });
    }

    loadCompaniesForDropdown(); 


// --- 1. VIEW: Data Fetch aur Modal Show Karna ---
    $(document).on('click', '.view-btn', function() {
        let id = $(this).data('id');
        
        $.ajax({
            url: `/api/v1/branches/${id}`,
            type: 'GET',
            headers: { 'Authorization': 'Bearer ' + apiToken },
            success: function(res) {
                let data = res.data;
                
                // Fill Table Data
                $('#v_branch_id').text(data.branch_id);
                $('#v_branch_name').text(data.branch_name);
                $('#v_company_name').text(data.company ? data.company.company_name : 'Master Branch');
                $('#v_location').text((data.branch_district || '-') + ', ' + (data.branch_state || '-'));
                
                // Format Date
                let dateObj = new Date(data.opening_date);
                $('#v_opening_date').text(dateObj.toLocaleDateString('en-GB'));
                
                $('#v_address').text(data.branch_location || '-');
                $('#v_status').html(data.branch_status === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>');
                
                // Render Map properly
                if(data.branch_map && data.branch_map.includes('<iframe')) {
                    let iframeHTML = data.branch_map.replace(/width="[^"]+"/, 'width="100%"').replace(/height="[^"]+"/, 'height="100%"');
                    $('#v_map').html(iframeHTML);
                } else {
                    $('#v_map').html('<div class="text-muted"><i class="fas fa-map-marker-slash fs-3 mb-2"></i><br>No Map Found</div>');
                }
                
                $('#viewBranchModal').modal('show');
            }
        });
    });


        });
    </script>
@endpush
