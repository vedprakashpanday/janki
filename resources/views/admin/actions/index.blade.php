@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        /* Custom Variables & Typography */
        .table-custom th {
            background-color: var(--sidebar-bg);
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            border: none;
            padding: 12px 15px;
        }

        .table-custom td {
            font-size: 13px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
            padding: 12px 15px;
        }

        /* Status Badges */
        .status-active {
            background: #dcfce7;
            color: #166534;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

        /* Mobile Card View Layout Styles */
        .mobile-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s, box-shadow 0.2s;
            margin-bottom: 15px;
        }

        .mobile-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 54, 93, 0.08);
        }

        .mobile-card-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--sidebar-bg);
        }

        .mobile-card-sub {
            font-size: 12px;
            color: #718096;
        }

        /* Absolute DataTables Wrapper Isolation for Mobile */
        @media (max-width: 767.98px) {
            .dataTables_wrapper {
                display: none !important;
            }
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color:var(--sidebar-bg);"><i class="fas fa-bolt me-2 text-warning"></i>Action
                    Master</h4>
                <p class="text-secondary small mb-0 d-none d-md-block">Manage Dynamic System Permissions (e.g. Approve,
                    Verify)</p>
            </div>

            <!-- 🛡️ SECURED: Add Action Button -->
            <button class="btn text-white px-4 py-2 shadow-sm secured-item" data-permission="action_master_add"
                onclick="openActionModal()"
                style="background-color:var(--brand-primary); font-weight: 500; border-radius: 8px;">
                <i class="fas fa-plus me-1"></i> Add Action
            </button>
        </div>

        <div class="d-block d-md-none mb-4">
            <div class="row g-2">
                <div class="col">
                    <div class="input-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control border-start-0" id="mobileSearch"
                            placeholder="Search actions...">
                    </div>
                </div>
                <div class="col-auto">
                    <button class="btn btn-success h-100 shadow-sm px-3" id="mobileExcelBtn" title="Export to Excel"
                        style="border-radius: 8px;">
                        <i class="fas fa-file-excel fs-5"></i>
                    </button>
                </div>
            </div>

            <div id="mobileCardsContainer" class="mt-3">
                <div class="text-center py-5" id="mobileLoader">
                    <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
                    <p class="mt-2 text-muted small mb-0">Loading system actions...</p>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-4">
                <table id="actionTable" class="table table-hover table-custom w-100">
                    <thead>
                        <tr>
                            <th width="8%">ID</th>
                            <th>Action Name</th>
                            <th>Action Slug <small class="fw-normal">(Code)</small></th>
                            <th width="15%">Status</th>
                            <th class="text-center" width="15%">Options</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="actionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 12px;">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="modalTitle">Add Action</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="actionForm">
                        <input type="hidden" id="edit_id">
                        <input type="hidden" id="form_method" value="POST">

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Action Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="action_name" id="a_name"
                                placeholder="e.g. Approve Voucher" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Action Slug <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="action_slug" id="a_slug"
                                placeholder="e.g. approve" required>
                            <small class="text-muted" style="font-size: 11px;">Only lowercase letters and underscores (no
                                spaces).</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Status</label>
                            <select class="form-select shadow-sm" name="status" id="a_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <button type="submit" class="btn text-white w-100 py-2 fw-semibold shadow-sm"
                            style="background-color:var(--sidebar-bg); border-radius: 8px;" id="saveBtn">Save
                            Action</button>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let table;

        window.openActionModal = function() {
            $('#actionForm')[0].reset();
            $('#edit_id').val('');
            $('#form_method').val('POST');
            $('#modalTitle').html('<i class="fas fa-plus-circle text-primary me-2"></i>Add System Action');
            $('#actionModal').modal('show');
        };

        $(document).ready(function() {

            // 2. INITIALIZE DATATABLE
            table = $('#actionTable').DataTable({
                dom: '<"row d-none d-md-flex mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row d-none d-md-flex mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Download Actions',
                    className: 'btn btn-success btn-sm font-weight-bold px-3'
                }],
                ajax: {
                    url: '/api/v1/system-actions', // CLEANED API ROUTE
                    type: 'GET',
                    dataSrc: 'data'
                },
                columns: [{
                        data: 'id',
                        className: 'fw-bold text-dark text-center'
                    },
                    {
                        data: 'action_name',
                        className: 'fw-bold text-primary'
                    },
                    {
                        data: 'action_slug',
                        render: d =>
                            `<span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-code me-1 text-muted"></i>${d}</span>`
                    },
                    {
                        data: 'status',
                        render: s => s === 'active' ?
                            `<span class="status-active">Active</span>` :
                            `<span class="status-inactive">Inactive</span>`
                    },
                    {
                        data: 'id',
                        className: 'text-center text-nowrap',
                        render: function(id) {
                            let btns = '';
                            // 🛡️ SECURED: Edit and Delete buttons
                            btns +=
                                `<button class="btn btn-sm btn-light border text-primary edit-btn me-1 shadow-sm secured-item" data-permission="action_master_edit" data-id="${id}" title="Edit"><i class="fas fa-edit"></i></button>`;
                            btns +=
                                `<button class="btn btn-sm btn-light border text-danger delete-btn shadow-sm secured-item" data-permission="action_master_delete" data-id="${id}" title="Delete"><i class="fas fa-trash-alt"></i></button>`;
                            return btns;
                        }
                    }
                ],
                order: [
                    [0, 'desc']
                ],
                pageLength: 25,
                drawCallback: function() {
                    renderMobileCardGrid(this.api().rows({
                        search: 'applied'
                    }).data().toArray());
                    // 🛡️ Ensure permissions are applied after draw
                    if (typeof window.applyPermissions === 'function') window.applyPermissions();
                }
            });

            // 3. MOBILE BINDINGS
            $('#mobileSearch').on('keyup', function() {
                table.search(this.value).draw();
            });

            $('#mobileExcelBtn').on('click', function() {
                table.button('.buttons-excel').trigger();
            });


            // 🔥 MOBILE CARD GRID RENDERER 🔥
            function renderMobileCardGrid(dataset) {
                $('#mobileLoader').hide();
                let gridHtml = '';

                if (!dataset || dataset.length === 0) {
                    gridHtml =
                        '<div class="text-center p-4 bg-white rounded shadow-sm text-muted small"><i class="fas fa-search-minus d-block fs-3 mb-2"></i>No matching actions found.</div>';
                    $('#mobileCardsContainer').html(gridHtml);
                    return;
                }

                dataset.forEach(item => {
                    let stBadge = item.status === 'active' ? '<span class="status-active">Active</span>' :
                        '<span class="status-inactive">Inactive</span>';

                    let editMarkup =
                        `<button class="btn btn-sm btn-light border text-primary edit-btn flex-fill me-2 secured-item" data-permission="action_master_edit" data-id="${item.id}"><i class="fas fa-edit me-1"></i> Edit</button>`;
                    let delMarkup =
                        `<button class="btn btn-sm btn-light border text-danger delete-btn flex-fill secured-item" data-permission="action_master_delete" data-id="${item.id}"><i class="fas fa-trash-alt me-1"></i> Delete</button>`;

                    gridHtml += `
                    <div class="mobile-card p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="mobile-card-title"><i class="fas fa-bolt text-warning me-2"></i>${item.action_name}</div>
                                <div class="mobile-card-sub mt-2"><i class="fas fa-code me-1"></i> Slug: <span class="badge bg-light text-dark border">${item.action_slug}</span></div>
                            </div>
                            <div class="text-end text-nowrap">
                                <div class="small text-muted fw-bold mb-1">ID: ${item.id}</div>
                                ${stBadge}
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top gap-1 mt-3">
                            ${editMarkup}
                            ${delMarkup}
                        </div>
                    </div>`;
                });

                $('#mobileCardsContainer').html(gridHtml);
                // 🛡️ RE-APPLY PERMISSIONS for mobile
                if (typeof window.applyPermissions === 'function') window.applyPermissions();
            }

            // FORM SUBMIT
            $('#actionForm').submit(function(e) {
                e.preventDefault();
                let formData = $(this).serialize();
                let id = $('#edit_id').val();
                let method = $('#form_method').val();
                let url = method === 'PUT' ? `/api/v1/system-actions/${id}` :
                '/api/v1/system-actions'; // CLEANED API ROUTE
                if (method === 'PUT') formData += '&_method=PUT';

                let btn = $('#saveBtn');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    success: function(res) {
                        $('#actionModal').modal('hide');
                        Swal.fire('Success', res.message, 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function(err) {
                        Swal.fire('Error', err.responseJSON?.message || 'Error occurred',
                            'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Save Action');
                    }
                });
            });

            // EDIT POPULATE
            $(document).on('click', '.edit-btn', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: `/api/v1/system-actions/${id}`, // CLEANED API ROUTE
                    type: 'GET',
                    success: function(res) {
                        let d = res.data;
                        $('#edit_id').val(d.id);
                        $('#form_method').val('PUT');
                        $('#a_name').val(d.action_name);
                        $('#a_slug').val(d.action_slug);
                        $('#a_status').val(d.status);

                        $('#modalTitle').html(
                            '<i class="fas fa-edit text-warning me-2"></i>Edit System Action'
                        );
                        $('#actionModal').modal('show');
                    }
                });
            });

            // DELETE ACTION
            $(document).on('click', '.delete-btn', function() {
                let id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Action?',
                    text: "It will not delete old permissions, but will stop creating this action for new modules.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/api/v1/system-actions/${id}`, // CLEANED API ROUTE
                            type: 'DELETE',
                            success: function(res) {
                                Swal.fire('Deleted!', res.message, 'success');
                                table.ajax.reload(null, false);
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
