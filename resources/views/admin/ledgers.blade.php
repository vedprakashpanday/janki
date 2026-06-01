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

        /* Mobile Card Styling */
        .mobile-item {
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px var(--shadow-color);
        }

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: #4b5563;
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Ledger Management</h4>
            <!-- 🛡️ SECURED: Add Ledger Button -->
            <button type="button" class="btn text-white px-3 py-2 shadow-sm secured-item" data-permission="ledger_add"
                style="background-color: var(--brand-primary);" onclick="openModal('add')">
                <i class="fas fa-plus-circle me-1"></i> Add Ledger
            </button>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search Ledgers...">
            <button type="button" class="btn text-white shadow-sm" style="background-color: #10b981;"
                onclick="$('.buttons-excel').click()">
                <i class="fas fa-file-excel"></i>
            </button>
        </div>

        <div class="card border-0 shadow-sm mb-4 d-none d-md-block">
            <div class="card-body p-4 table-responsive">
                <table id="ledgerTable" class="table table-hover table-custom w-100">
                    <thead>
                        <tr>
                            <th>Ledger Code</th>
                            <th>Branch</th>
                            <th>Ledger Name</th>
                            <th>From Date</th>
                            <th>To Date</th>
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

    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i
                            class="fas fa-eye me-2 text-info"></i> Ledger Details</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold text-primary mb-2">Ledger Info</h6>
                                <p class="mb-1"><strong>Code:</strong> <span id="v_code"
                                        class="text-dark fw-bold"></span></p>
                                <p class="mb-1"><strong>Name:</strong> <span id="v_name" class="text-dark"></span></p>
                                <p class="mb-0"><strong>Branch:</strong> <span id="v_branch" class="text-dark"></span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <p class="small text-muted mb-0">From Date</p>
                            <h6 class="fw-bold" id="v_from"></h6>
                        </div>
                        <div class="col-md-6">
                            <p class="small text-muted mb-0">To Date</p>
                            <h6 class="fw-bold" id="v_to"></h6>
                        </div>
                        <div class="col-12">
                            <p class="small text-muted mb-0">Status</p>
                            <div id="v_status_badge"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ledgerModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold" id="modalTitle" style="color: var(--sidebar-bg);">Manage Ledger</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="ledgerForm" class="row g-3">
                        <input type="hidden" id="edit_id">

                        <div class="col-md-6">
                            <label class="form-label">Select Branch <span class="text-danger">*</span></label>
                            <select name="branch_id" id="f_branch" class="form-select" required></select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Ledger Name <span class="text-danger">*</span></label>
                            <input type="text" name="ledger_name" id="f_name_input" class="form-control"
                                placeholder="e.g. Sales Account" required>
                        </div>

                        <div class="col-12 code-div" style="display:none;">
                            <label class="form-label text-muted">Ledger Code (Auto Generated)</label>
                            <input type="text" id="f_code_input" class="form-control bg-light" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" id="f_from_input" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" id="f_to_input" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select name="status" id="f_status_input" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12 text-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-secondary px-4 me-2"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn text-white px-5 shadow-sm fw-medium"
                                style="background-color: var(--brand-primary);" id="saveBtn">Save Ledger</button>
                        </div>
                    </form>
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

    <script>
        $(document).ready(function() {
            const apiToken = localStorage.getItem('admin_token');
            let mode = 'add';

            // 1. DataTables (API Updated)
            let table = $('#ledgerTable').DataTable({
                ajax: {
                    url: '/api/v1/ledgers', // Changed from /admin/
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
                columns: [{
                        data: 'ledger_code',
                        render: d => `<span class="fw-bold text-primary">${d}</span>`
                    },
                    {
                        data: 'branch_id',
                        render: (d, t, row) =>
                            `<span class="badge bg-light text-dark border">${row.branch ? row.branch.branch_name : 'N/A'}</span>`
                    },
                    {
                        data: 'ledger_name',
                        render: d => `<span class="fw-bold text-dark">${d}</span>`
                    },
                    {
                        data: 'from_date',
                        render: d => d ? d : '-'
                    },
                    {
                        data: 'to_date',
                        render: d => d ? d : '-'
                    },
                    {
                        data: 'status',
                        render: d => d === 'Active' ? `<span class="badge bg-success">Active</span>` :
                            `<span class="badge bg-danger">Inactive</span>`
                    },
                    {
                        data: 'id',
                        orderable: false,
                        className: 'text-end text-nowrap',
                        render: d => `
                            <div class="d-flex justify-content-end flex-nowrap gap-1">
                                <button class="btn btn-sm btn-light text-success shadow-sm view-btn" title="View" data-id="${d}"><i class="fas fa-eye"></i></button>
                                <!-- 🛡️ SECURED: Edit and Delete buttons -->
                                <button class="btn btn-sm btn-light text-primary shadow-sm edit-btn secured-item" data-permission="ledger_edit" title="Edit" data-id="${d}"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-light text-danger shadow-sm delete-btn secured-item" data-permission="ledger_delete" title="Delete" data-id="${d}"><i class="fas fa-trash"></i></button>
                            </div>`
                    }
                ],
                drawCallback: function(settings) {
                    // 🛡️ Ensure permissions are applied after draw
                    if (typeof window.applyPermissions === 'function') window.applyPermissions();
                }
            });

            // 2. Mobile Cards Rendering
            function renderMobileCards(data) {
                let html = '';
                data.forEach(item => {
                    let stBadge = item.status === 'Active' ?
                        `<span class="badge bg-success">Active</span>` :
                        `<span class="badge bg-danger">Inactive</span>`;
                    let branchName = item.branch ? item.branch.branch_name : 'N/A';

                    html += `
                <div class="mobile-item ledger-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0">${item.ledger_name}</h6>
                            <small class="text-primary fw-bold">${item.ledger_code}</small>
                        </div>
                        ${stBadge}
                    </div>
                    <div class="small text-muted mb-1"><i class="fas fa-map-marker-alt text-danger me-1"></i> ${branchName}</div>
                    <div class="small text-muted mb-3">
                        <i class="far fa-calendar-alt me-1"></i> From: ${item.from_date || '-'} <br> 
                        <i class="far fa-calendar-check me-1"></i> To: ${item.to_date || '-'}
                    </div>
                    <div class="pt-2 border-top d-flex gap-2">
                        <button class="btn btn-sm btn-light text-info fw-bold flex-fill view-btn" data-id="${item.id}"><i class="fas fa-eye"></i> View</button>
                        <!-- 🛡️ SECURED: Edit and Delete buttons for Mobile -->
                        <button class="btn btn-sm btn-light text-primary fw-bold flex-fill edit-btn secured-item" data-permission="ledger_edit" data-id="${item.id}"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn btn-sm btn-light text-danger fw-bold flex-fill delete-btn secured-item" data-permission="ledger_delete" data-id="${item.id}"><i class="fas fa-trash"></i> Delete</button>
                    </div>
                </div>
            `;
                });
                $('#mobileCardsContainer').html(html || '<p class="text-center text-muted">No Ledgers found.</p>');

                // 🛡️ RE-APPLY PERMISSIONS for mobile
                if (typeof window.applyPermissions === 'function') window.applyPermissions();
            }

            // Mobile Search
            $('#mobileSearch').on('keyup', function() {
                let value = $(this).val().toLowerCase();
                $(".ledger-card").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // 3. View Details (API Updated)
            $(document).on('click', '.view-btn', function() {
                let id = $(this).data('id');
                $.get({
                    url: `/api/v1/ledgers/${id}`, // Changed from /admin/
                    success: function(res) {
                        let d = res.data;
                        $('#v_code').text(d.ledger_code);
                        $('#v_name').text(d.ledger_name);
                        $('#v_branch').text(d.branch ? d.branch.branch_name : 'N/A');
                        $('#v_from').text(d.from_date || 'N/A');
                        $('#v_to').text(d.to_date || 'N/A');

                        let badge = d.status === 'Active' ?
                            '<span class="badge bg-success">Active</span>' :
                            '<span class="badge bg-danger">Inactive</span>';
                        $('#v_status_badge').html(badge);

                        $('#viewModal').modal('show');
                    }
                });
            });

            // 4. Open Modal (Add / Edit) (API Updated)
            window.openModal = function(type, id = null) {
                mode = type;
                $('#ledgerForm')[0].reset();
                $('#modalTitle').text(type === 'add' ? 'Add Ledger' : 'Edit Ledger');
                if (type === 'add') {
                    $('.code-div').hide();
                }

                $.ajax({
                    url: '/api/v1/branches', // Changed from /admin/
                    success: function(res) {
                        let options = '<option value="">-- Choose Branch --</option>';
                        res.data.forEach(b => {
                            options += `<option value="${b.id}">${b.branch_name}</option>`;
                        });
                        $('#f_branch').html(options);

                        if (type === 'edit') {
                            $.get({
                                url: `/api/v1/ledgers/${id}`, // Changed from /admin/
                                success: function(res) {
                                    let d = res.data;
                                    $('#edit_id').val(d.id);
                                    $('#f_branch').val(d.branch_id);
                                    $('#f_name_input').val(d.ledger_name);
                                    $('.code-div').show();
                                    $('#f_code_input').val(d.ledger_code);
                                    $('#f_from_input').val(d.from_date);
                                    $('#f_to_input').val(d.to_date);
                                    $('#f_status_input').val(d.status);
                                }
                            });
                        }
                    }
                });
                $('#ledgerModal').modal('show');
            };

            $(document).on('click', '.edit-btn', function() {
                openModal('edit', $(this).data('id'));
            });

            // 5. Save Data (API Updated)
            $('#ledgerForm').submit(function(e) {
                e.preventDefault();
                let id = $('#edit_id').val();
                let url = mode === 'add' ? '/api/v1/ledgers' :
                `/api/v1/ledgers/${id}`; // Changed from /admin/
                let type = mode === 'add' ? 'POST' : 'PUT';
                let btn = $('#saveBtn');
                btn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: url,
                    type: type,
                    data: $(this).serialize(),
                    success: function(res) {
                        alert(res.message);
                        $('#ledgerModal').modal('hide');
                        table.ajax.reload(null, false);
                    },
                    error: function(err) {
                        alert(err.responseJSON.message || "Error occurred.");
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Save Ledger');
                    }
                });
            });

            // 6. Delete (API Updated)
            $(document).on('click', '.delete-btn', function() {
                if (confirm("Are you sure?")) {
                    $.ajax({
                        url: `/api/v1/ledgers/${$(this).data('id')}`, // Changed from /admin/
                        type: 'DELETE',
                        success: function(res) {
                            table.ajax.reload(null, false);
                        }
                    });
                }
            });
        });
    </script>
@endpush
