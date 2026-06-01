@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        /* Premium Custom Variables & Typography */
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
                <h4 class="fw-bold mb-0" style="color:var(--sidebar-bg);"><i
                        class="fas fa-sitemap me-2 text-primary"></i>Module Master</h4>
                <p class="text-secondary small mb-0 d-none d-md-block">Dynamic Menu Engine & Permission Auto-Generator Tier
                </p>
            </div>

            <button class="btn text-white px-4 py-2 shadow-sm d-none" id="addModuleBtn" onclick="openAddModal()"
                style="background-color:var(--brand-primary); font-weight: 500; border-radius: 8px;">
                <i class="fas fa-plus me-1"></i> Add Module
            </button>
        </div>

        <div class="d-block d-md-none mb-4">
            <div class="row g-2">
                <div class="col">
                    <div class="input-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control border-start-0" id="mobileSearch"
                            placeholder="Search modules dynamically...">
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
                    <p class="mt-2 text-muted small mb-0">Streaming dynamic modules from architecture...</p>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-4">
                <table id="moduleTable" class="table table-hover table-custom w-100">
                    <thead>
                        <tr>
                            <th width="5%">Seq</th>
                            <th width="5%">Icon</th>
                            <th>Module Name</th>
                            <th>Type</th>
                            <th>Route (URL)</th>
                            <th>Permission Base</th>
                            <th>Status</th>
                            <th class="text-center" width="12%">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="moduleModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 12px;">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="modalTitle">Add Module</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="moduleForm">
                        <input type="hidden" id="edit_id">
                        <input type="hidden" id="form_method" value="POST">

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Parent Module <small
                                    class="text-muted">(Leave blank if Root Level)</small></label>
                            <select class="form-select shadow-sm" name="parent_id" id="m_parent_id"></select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <label class="form-label small fw-bold text-secondary">Module Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="module_name" id="m_module_name" required
                                    autocomplete="off">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold text-secondary">Sequence</label>
                                <input type="number" class="form-control" name="sequence" id="m_sequence" value="0">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary">Route / URL</label>
                                <input type="text" class="form-control" name="route" id="m_route"
                                    placeholder="e.g., admin/vouchers">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary">Icon (FontAwesome)</label>
                                <input type="text" class="form-control" name="icon" id="m_icon"
                                    placeholder="e.g., fas fa-user">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Permission Base Code <small
                                    class="text-muted">(Triggers Auto-Actions)</small></label>
                            <input type="text" class="form-control" name="permission_base" id="m_permission_base"
                                placeholder="e.g., voucher">
                        </div>

                        <button type="submit" class="btn text-white w-100 py-2 fw-semibold shadow-sm"
                            style="background-color:var(--sidebar-bg); border-radius: 8px;" id="saveBtn">Save Module
                            Configuration</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 12px;">
                <div class="modal-header bg-light border-bottom-0" style="border-radius: 12px 12px 0 0;">
                    <h5 class="modal-title fw-bold" style="color:var(--sidebar-bg);"><i
                            class="fas fa-search-plus text-primary me-2"></i>Audit Inspection</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm mb-0">
                            <tbody>
                                <tr class="border-bottom">
                                    <td class="text-muted small fw-bold py-2" width="40%">Module Name</td>
                                    <td id="v_name" class="fw-bold text-dark py-2"></td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted small fw-bold py-2">Menu Type Layer</td>
                                    <td id="v_type" class="py-2"></td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted small fw-bold py-2">Sequence Order</td>
                                    <td id="v_seq" class="py-2"></td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted small fw-bold py-2">FontAwesome Icon</td>
                                    <td id="v_icon" class="py-2"></td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted small fw-bold py-2">Route Web Path</td>
                                    <td id="v_route" class="text-primary small py-2"></td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted small fw-bold py-2">Spatie Token Base</td>
                                    <td id="v_perm" class="py-2"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted small fw-bold py-2">Engine Status</td>
                                    <td id="v_status" class="py-2"></td>
                                </tr>
                            </tbody>
                        </table>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let table;
        const apiToken = localStorage.getItem('admin_token');
        let localModuleCache = []; // Client side memory cache for direct lookup view rendering

        window.openAddModal = function() {
            $('#moduleForm')[0].reset();
            $('#edit_id').val('');
            $('#form_method').val('POST');
            loadParents();
            $('#modalTitle').html('<i class="fas fa-plus-circle text-primary me-2"></i>Add Module Ecosystem');
            $('#moduleModal').modal('show');
        };

        function loadParents() {
            $.ajax({
                url: '/api/v1/admin/modules/parents',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + apiToken
                },
                success: function(res) {
                    let opts = '<option value="">-- Main Menu (Root Level) --</option>';
                    res.data.forEach(p => {
                        opts += `<option value="${p.id}">${p.module_name}</option>`;
                    });
                    $('#m_parent_id').html(opts);
                }
            });
        }

        $(document).ready(function() {
            if (!apiToken) {
                window.location.href = '/admin/login';
                return;
            }

            // 1. VERIFY PROFILE SECURITY CREDENTIALS FIRST
            $.ajax({
                url: '/api/v1/admin/auth/me',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + apiToken
                },
                success: function(res) {
                    let u = res.data;
                    let emailStr = u.email ? u.email.toLowerCase() : '';
                    let isMasterAdmin = (emailStr === 'admin@jankivilla.com');
                    let allowedTokens = u.permissions || [];

                    let canAdd = isMasterAdmin || allowedTokens.includes('module_master_add');
                    let canEdit = isMasterAdmin || allowedTokens.includes('module_master_edit');
                    let canDel = isMasterAdmin || allowedTokens.includes('module_master_delete');

                    if (canAdd) $('#addModuleBtn').removeClass('d-none');

                    // 2. RUN FULL ENGINE INITIALIZATION
                    table = $('#moduleTable').DataTable({
                        dom: '<"row d-none d-md-flex mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row d-none d-md-flex mt-3"<"col-md-6"i><"col-md-6"p>>',
                        buttons: [{
                            extend: 'excelHtml5',
                            text: '<i class="fas fa-file-excel me-1"></i> Download Master Sheet',
                            className: 'btn btn-success btn-sm font-weight-bold px-3'
                        }],
                        ajax: {
                            url: '/api/v1/admin/modules',
                            type: 'GET',
                            headers: {
                                'Authorization': 'Bearer ' + apiToken
                            },
                            dataSrc: function(json) {
                                localModuleCache = json.data; // Sync to client cache memory
                                return json.data;
                            }
                        },
                        columns: [{
                                data: 'sequence',
                                className: 'text-center fw-bold text-dark'
                            },
                            {
                                data: 'icon',
                                render: d => d ? `<i class="${d} text-primary fs-5"></i>` :
                                    '-'
                            },
                            {
                                data: 'module_name',
                                render: (d, t, row) => row.parent_id ?
                                    `<span class="ms-3 text-secondary">-- ${d}</span>` :
                                    `<span class="fw-bold text-dark">${d}</span>`
                            },
                            {
                                data: 'parent_id',
                                render: d => d ?
                                    '<span class="badge bg-light text-dark border">Child Sub</span>' :
                                    '<span class="badge bg-secondary">Root Parent</span>'
                            },
                            {
                                data: 'route',
                                render: d => d ?
                                    `<code class="text-dark small">${d}</code>` : '-'
                            },
                            {
                                data: 'permission_base',
                                render: d => d ?
                                    `<span class="badge bg-success-subtle text-success">${d}_*</span>` :
                                    '-'
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
                                    let uiActions =
                                        `<button class="btn btn-sm btn-light border text-info view-btn me-1" data-id="${id}"><i class="fas fa-eye"></i></button>`;
                                    if (canEdit) uiActions +=
                                        `<button class="btn btn-sm btn-light border text-primary edit-btn me-1" data-id="${id}"><i class="fas fa-edit"></i></button>`;
                                    if (canDel) uiActions +=
                                        `<button class="btn btn-sm btn-light border text-danger delete-btn" data-id="${id}"><i class="fas fa-trash-alt"></i></button>`;
                                    return uiActions;
                                }
                            }
                        ],
                        order: [
                            [0, 'asc']
                        ],
                        pageLength: 50,
                        drawCallback: function() {
                            // Sync raw structural datasets to mobile template builder natively
                            renderMobileCardGrid(this.api().rows({
                                search: 'applied'
                            }).data().toArray(), canEdit, canDel);
                        }
                    });

                    // 🔥 3. BIND SEPARATE MOBILE ACTIONS TIERS 🔥
                    $('#mobileSearch').on('keyup', function() {
                        table.search(this.value).draw();
                    });

                    $('#mobileExcelBtn').on('click', function() {
                        table.button('.buttons-excel')
                    .trigger(); // Trigger desktop instance pipeline safely
                    });
                }
            });

            // 🔥 MOBILE CARD VIEW LIVE BUILDER ENGINE 🔥
            function renderMobileCardGrid(dataset, canEdit, canDel) {
                $('#mobileLoader').hide();
                let gridHtml = '';

                if (!dataset || dataset.length === 0) {
                    gridHtml =
                        '<div class="text-center p-4 bg-white rounded shadow-sm text-muted small"><i class="fas fa-search-minus d-block fs-3 mb-2"></i>No matching records exist inside matrix.</div>';
                    $('#mobileCardsContainer').html(gridHtml);
                    return;
                }

                dataset.forEach(item => {
                    let stBadge = item.status === 'active' ? '<span class="status-active">Active</span>' :
                        '<span class="status-inactive">Inactive</span>';
                    let tpBadge = item.parent_id ?
                        '<span class="badge bg-light text-dark border ms-2">Child Menu</span>' :
                        '<span class="badge bg-dark ms-2">Main Menu</span>';

                    let editMarkup = canEdit ?
                        `<button class="btn btn-sm btn-light border text-primary edit-btn flex-fill" data-id="${item.id}"><i class="fas fa-edit me-1"></i> Edit</button>` :
                        '';
                    let delMarkup = canDel ?
                        `<button class="btn btn-sm btn-light border text-danger delete-btn" data-id="${item.id}"><i class="fas fa-trash-alt"></i></button>` :
                        '';

                    gridHtml += `
                    <div class="mobile-card p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="mobile-card-title"><i class="${item.icon || 'fas fa-link'} text-primary me-2"></i>${item.module_name} ${tpBadge}</div>
                                <div class="mobile-card-sub mt-1"><i class="far fa-folder-open me-1"></i> Path: <code>${item.route || 'Standalone Root'}</code></div>
                                <div class="mobile-card-sub mt-1"><i class="fas fa-shield-alt me-1"></i> Key: <span class="badge bg-success-subtle text-success">${item.permission_base || 'None'}_*</span></div>
                            </div>
                            <div class="text-end text-nowrap">
                                <div class="small text-muted fw-bold mb-1">Seq: ${item.sequence}</div>
                                ${stBadge}
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top gap-2 mt-3">
                            <button class="btn btn-sm btn-light border text-info view-btn flex-fill" data-id="${item.id}"><i class="fas fa-eye me-1"></i> Inspect</button>
                            ${editMarkup}
                            ${delMarkup}
                        </div>
                    </div>`;
                });

                $('#mobileCardsContainer').html(gridHtml);
            }

            // INSPECT OVERLAY VIEW HANDLER
            $(document).on('click', '.view-btn', function() {
                let currentId = $(this).data('id');
                let targetData = localModuleCache.find(obj => obj.id == currentId);

                if (targetData) {
                    $('#v_name').text(targetData.module_name);
                    $('#v_type').html(targetData.parent_id ?
                        '<span class="badge bg-light text-dark border">Sub-Menu Child Layer</span>' :
                        '<span class="badge bg-secondary">Ecosystem Parent Layer</span>');
                    $('#v_seq').html(`<span class="badge bg-dark px-2">${targetData.sequence}</span>`);
                    $('#v_icon').html(targetData.icon ?
                        `<i class="${targetData.icon} text-primary fs-5 me-2"></i> <code>${targetData.icon}</code>` :
                        '-');
                    $('#v_route').text(targetData.route || 'Main Branch Anchor');
                    $('#v_perm').html(targetData.permission_base ?
                        `<span class="badge bg-success-subtle text-success fs-7">${targetData.permission_base}_[action]</span>` :
                        '-');
                    $('#v_status').html(targetData.status === 'active' ?
                        `<span class="status-active">Active Engine</span>` :
                        `<span class="status-inactive">Inactive Block</span>`);

                    $('#viewModal').modal('show');
                }
            });

            // FORM SUBMISSION PIPELINE (Handles POST/PUT completely)
            $('#moduleForm').submit(function(e) {
                e.preventDefault();
                let serializedPayload = $(this).serialize();
                let recordId = $('#edit_id').val();
                let activeMethod = $('#form_method').val();
                let endpointUrl = activeMethod === 'PUT' ? `/api/v1/admin/modules/${recordId}` :
                    '/api/v1/admin/modules';
                if (activeMethod === 'PUT') serializedPayload += '&_method=PUT';

                let saveBtnInstance = $('#saveBtn');
                saveBtnInstance.prop('disabled', true).html(
                    '<i class="fas fa-circle-notch fa-spin me-1"></i>Syncing Architecture...');

                $.ajax({
                    url: endpointUrl,
                    type: 'POST',
                    data: serializedPayload,
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        $('#moduleModal').modal('hide');
                        Swal.fire('Ecosystem Synced', res.message, 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function(err) {
                        Swal.fire('Data Breach/Error', err.responseJSON?.message ||
                            'Transaction aborted', 'error');
                    },
                    complete: function() {
                        saveBtnInstance.prop('disabled', false).text(
                            'Save Module Configuration');
                    }
                });
            });

            // EDIT POPULATE ENGINE
            $(document).on('click', '.edit-btn', function() {
                let targetId = $(this).data('id');
                $.ajax({
                    url: `/api/v1/admin/modules/${targetId}`,
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        loadParents();
                        setTimeout(() => {
                            let core = res.data;
                            $('#edit_id').val(core.id);
                            $('#form_method').val('PUT');
                            $('#m_parent_id').val(core.parent_id || '');
                            $('#m_module_name').val(core.module_name);
                            $('#m_sequence').val(core.sequence);
                            $('#m_route').val(core.route);
                            $('#m_icon').val(core.icon);
                            $('#m_permission_base').val(core.permission_base);

                            $('#modalTitle').html(
                                '<i class="fas fa-edit text-warning me-2"></i>Modify Module Layout'
                                );
                            $('#moduleModal').modal('show');
                        }, 200);
                    }
                });
            });

            // DESTRUCTIVE CRITICAL DELETE ENGINE
            $(document).on('click', '.delete-btn', function() {
                let targetDeleteId = $(this).data('id');
                Swal.fire({
                    title: 'Purge Target?',
                    text: 'This instantly structural block will drop from layout rendering schemas!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Confirm Purge'
                }).then((executionMeta) => {
                    if (executionMeta.isConfirmed) {
                        $.ajax({
                            url: `/api/v1/admin/modules/${targetDeleteId}`,
                            type: 'DELETE',
                            headers: {
                                'Authorization': 'Bearer ' + apiToken
                            },
                            success: function(res) {
                                Swal.fire('Purged!', res.message, 'success');
                                table.ajax.reload(null, false);
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
