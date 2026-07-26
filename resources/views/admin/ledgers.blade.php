@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        .table-custom th {
            background-color: var(--sidebar-bg);
            color: #fff;
            font-size: 13px;
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

        #bulkActions {
            display: none;
        }

        /* Default hidden */
    </style>

    <div class="container-fluid p-0">
     <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Ledger Management</h4>
            
            <div>
                <span id="bulkActions" class="me-2">
                    <button class="btn btn-dark px-3 py-2 shadow-sm" onclick="selectAllCheckboxes()">Select All</button>
                    <button class="btn btn-danger px-3 py-2 shadow-sm secured-item" data-permission="ledger_delete" onclick="deleteSelected()">Delete Selected</button>
                </span>

                <button type="button" class="btn text-white px-3 py-2 shadow-sm secured-item" data-permission="ledger_add_direct" style="background-color: var(--brand-primary);" onclick="openModal('add')">
                    <i class="fas fa-plus-circle me-1"></i> Add Ledger
                </button>
                
                <button type="button" class="btn text-white px-3 py-2 shadow-sm secured-item" data-permission="ledger_add_request" style="background-color: #f59e0b;" onclick="openModal('add')">
                    <i class="fas fa-hand-paper me-1"></i> Request Ledger
                </button>
            </div>
        </div>

        @php
            $printCompany = \App\Models\Company::find(1);
            $watermarkLogo = $printCompany && !empty($printCompany->company_logo) 
                ? asset($printCompany->company_logo) 
                : "https://ui-avatars.com/api/?name=".urlencode($printCompany->company_name ?? 'AB')."&color=7F9CF5&background=EBF4FF";
        @endphp
        <div id="printHeaderContainer" class="d-none">
            <x-print-header :company="$printCompany" :branch="null" />
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search Ledgers...">
            <button type="button" class="btn text-white shadow-sm secured-item" data-permission="ledger_export"
                style="background-color: #10b981;" onclick="$('.buttons-excel').click()">
                <i class="fas fa-file-excel"></i>
            </button>
        </div>

        <div class="card border-0 shadow-sm mb-4 d-none d-md-block secured-item" data-permission="ledger_view">
            <div class="card-body p-4 table-responsive">
                <table id="ledgerTable" class="table table-hover table-custom w-100">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>Ledger Code</th>
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

        <div id="mobileCardsContainer" class="d-block d-md-none secured-item" data-permission="ledger_view"></div>
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

                        <!-- Phase Toggle -->
                        <div class="col-12 mt-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="addPhaseToggle" name="add_phase_toggle" value="1">
                                <label class="form-check-label fw-bold text-primary" for="addPhaseToggle">Enable Specific Phase/Company</label>
                            </div>
                        </div>

                        <!-- Phase Search & Company Inputs -->
                        <div class="col-md-6 phase-section" style="display: none; position: relative;">
                            <label class="form-label">Search Phase (Min 3 chars)</label>
                            <input type="text" id="phase_search_input" class="form-control" placeholder="Type to search phase..." autocomplete="off">
                            <ul id="phase_suggestions" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1050; display: none; max-height: 200px; overflow-y: auto;"></ul>
                            <input type="hidden" name="phase_id" id="hidden_phase_id">
                        </div>

                        <div class="col-md-6 phase-section" style="display: none;">
                            <label class="form-label">Company Name</label>
                            <input type="text" id="company_display_input" class="form-control bg-light" readonly placeholder="Auto-filled">
                            <input type="hidden" name="company_id" id="hidden_company_id">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Ledger Code <span class="text-danger">*</span></label>
                            <input type="text" name="ledger_code" id="f_code_input" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Ledger Name <span class="text-danger">*</span></label>
                            <input type="text" name="ledger_name" id="f_name_input" class="form-control"
                                placeholder="e.g. Sales Account" required>
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

    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i class="fas fa-eye me-2 text-info"></i> Ledger Details</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold text-primary mb-2">Ledger Info</h6>
                                <p class="mb-1"><strong>Code:</strong> <span id="v_code" class="text-dark fw-bold"></span></p>
                                <p class="mb-0"><strong>Name:</strong> <span id="v_name" class="text-dark"></span></p>
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

@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

    <script>
        $(document).ready(function() {
            let mode = 'add';

            // Checkbox event listeners for triggering Bulk Action buttons
            $(document).on('change', '.row-checkbox', function() {
                let checkedCount = $('.row-checkbox:checked').length;
                if (checkedCount > 0) {
                    $('#bulkActions').show();
                } else {
                    $('#bulkActions').hide();
                }
            });


            // Toggle Logic
            $('#addPhaseToggle').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.phase-section').fadeIn();
                } else {
                    $('.phase-section').fadeOut();
                    // Clear inputs if toggled off
                    $('#phase_search_input, #company_display_input, #hidden_phase_id, #hidden_company_id').val('');
                }
            });

            // Phase Search AJAX
            let searchTimeout;
            $('#phase_search_input').on('keyup', function() {
                let query = $(this).val();
                let suggestionsBox = $('#phase_suggestions');

                clearTimeout(searchTimeout);

                if (query.length >= 3) {
                    searchTimeout = setTimeout(function() {
                        $.get('/api/v1/phases/search-dynamic-list?q=' + query, function(res) {
                            suggestionsBox.empty();
                            if (res.length > 0) {
                                res.forEach(item => {
                                    suggestionsBox.append(`
                                        <li class="list-group-item list-group-item-action cursor-pointer phase-option" 
                                            data-id="${item.id}" 
                                            data-name="${item.name}" 
                                            data-company-id="${item.company_id}" 
                                            data-company-name="${item.company_name}">
                                            <strong>${item.name}</strong> <br>
                                            <small class="text-muted">(${item.company_name})</small>
                                        </li>
                                    `);
                                });
                                suggestionsBox.show();
                            } else {
                                suggestionsBox.html('<li class="list-group-item text-danger">No phases found</li>').show();
                            }
                        });
                    }, 400); // Debounce of 400ms
                } else {
                    suggestionsBox.hide();
                }
            });

            // Select Phase from suggestions
            $(document).on('click', '.phase-option', function() {
                let phaseId = $(this).data('id');
                let phaseName = $(this).data('name');
                let companyId = $(this).data('company-id');
                let companyName = $(this).data('company-name');

                // Fill Inputs
                $('#phase_search_input').val(phaseName);
                $('#hidden_phase_id').val(phaseId);
                
                $('#company_display_input').val(companyName);
                $('#hidden_company_id').val(companyId);

                // Hide suggestions box
                $('#phase_suggestions').hide();
            });

            // Hide suggestions when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.phase-section').length) {
                    $('#phase_suggestions').hide();
                }
            });


            // 1. DataTables
            let table = $('#ledgerTable').DataTable({
                ajax: {
                    url: '/api/v1/ledgers',
                    dataSrc: function(json) {
                        renderMobileCards(json.data);
                        return json.data;
                    }
                },
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
               buttons: [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="fas fa-file-excel me-1"></i> Export',
                        className: 'btn btn-success btn-sm shadow-sm secured-item',
                        attr: { 'data-permission': 'ledger_export' },
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5], // Index 6 (Actions) ko exclude kiya hai
                            format: {
                                header: function (data, column) {
                                    // First column ka header 'SL No.' kar diya
                                    return column === 0 ? 'SL No.' : data.replace(/<[^>]*>?/gm, '').trim();
                                },
                                body: function (data, row, column, node) {
                                    // First column me row index + 1 (SL No.) bhejna
                                    if (column === 0) return row + 1; 
                                    return $(node).text().trim(); // Baki columns ka HTML hata ke text nikalna
                                }
                            }
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print me-1"></i> Print',
                        className: 'btn btn-info btn-sm shadow-sm text-white secured-item ms-2',
                        attr: { 'data-permission': 'ledger_print' },
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5], // Index 6 (Actions) exclude kiya
                            format: {
                                header: function (data, column) {
                                    return column === 0 ? 'SL No.' : data.replace(/<[^>]*>?/gm, '').trim();
                                },
                                body: function (data, row, column, node) {
                                    if (column === 0) return row + 1; // SL No.
                                    return $(node).text().trim();
                                }
                            }
                        },
                        customize: function (win) {
                            // 1. DataTables ka default h1 title hatao
                            $(win.document.body).find('h1').first().remove();

                            // 2. Custom Blade Header Add Karo
                            let printHeader = $('#printHeaderContainer').html();
                            $(win.document.body).prepend(printHeader);

                            // 3. Watermark Add Karo
                            let watermark = '<div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.1; z-index: -1;">' +
                                            '<img src="{{ $watermarkLogo }}" style="width: 350px; max-width: 80vw;">' +
                                            '</div>';
                            $(win.document.body).append(watermark);

                            // 4. Table ki CSS style ko Print Format me set karo
                            $(win.document.body).find('table')
                                .removeClass('table-hover')
                                .addClass('table-bordered')
                                .css({
                                    'font-size': '12px',
                                    'margin-top': '15px',
                                    'width': '100%',
                                    'border-collapse': 'collapse'
                                });
                            
                            $(win.document.body).find('table th, table td').css({
                                'border': '1px solid #000',
                                'padding': '8px'
                            });
                        }
                    }
                ],
                columns: [{
                        data: 'id',
                        orderable: false,
                        render: d =>
                            `<input type="checkbox" class="form-check-input row-checkbox" value="${d}">`
                    },
                    {
                        data: 'ledger_code',
                        render: d => `<span class="fw-bold text-primary">${d}</span>`
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
                        render: d => {
                            if (d === 'Active')
                            return `<span class="badge bg-success">Active</span>`;
                            if (d === 'Pending')
                            return `<span class="badge bg-warning text-dark">Pending</span>`;
                            return `<span class="badge bg-danger">Inactive</span>`;
                        }
                    },
                   {
                        data: 'id',
                        orderable: false,
                        className: 'text-end text-nowrap',
                        render: (d, t, row) => `
                            <div class="d-flex justify-content-end flex-nowrap gap-1">
                                <button class="btn btn-sm btn-light text-info shadow-sm secured-item" data-permission="ledger_view" title="View" onclick="openViewModal(${d})"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-light text-success shadow-sm secured-item" data-permission="ledger_appr" title="Approve" onclick="updateStatus(${d}, 'approve')"><i class="fas fa-check-circle"></i></button>
                                <button class="btn btn-sm btn-light text-danger shadow-sm secured-item" data-permission="ledger_rej" title="Reject" onclick="updateStatus(${d}, 'reject')"><i class="fas fa-times-circle"></i></button>
                                <button class="btn btn-sm btn-light text-primary shadow-sm secured-item" data-permission="ledger_edit" title="Edit" onclick="openModal('edit', ${d})"><i class="fas fa-edit"></i></button>
                            </div>`
                    }
                ],
                drawCallback: function() {
                    if (typeof window.applyPermissions === 'function') window.applyPermissions();
                }
            });

            // 2. Mobile Cards Rendering (Checkboxes Added)
          function renderMobileCards(data) {
                let html = '';
                data.forEach(item => {
                    let stBadge = '';
                    if(item.status === 'Active') stBadge = `<span class="badge bg-success">Active</span>`;
                    else if(item.status === 'Pending') stBadge = `<span class="badge bg-warning text-dark">Pending</span>`;
                    else stBadge = `<span class="badge bg-danger">Inactive</span>`;

                    html += `
                    <div class="mobile-item ledger-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" class="form-check-input row-checkbox" value="${item.id}">
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">${item.ledger_name}</h6>
                                    <small class="text-primary fw-bold">${item.ledger_code}</small>
                                </div>
                            </div>
                            ${stBadge}
                        </div>
                        <div class="small text-muted mb-3">
                            <i class="far fa-calendar-alt me-1"></i> ${item.from_date || '-'} to ${item.to_date || '-'}
                        </div>
                        <div class="pt-2 border-top d-flex gap-1 flex-wrap">
                            <button class="btn btn-sm btn-light text-info fw-bold flex-fill secured-item" data-permission="ledger_view" onclick="openViewModal(${item.id})"><i class="fas fa-eye"></i> View</button>
                            <button class="btn btn-sm btn-light text-success fw-bold flex-fill secured-item" data-permission="ledger_appr" onclick="updateStatus(${item.id}, 'approve')"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-light text-danger fw-bold flex-fill secured-item" data-permission="ledger_rej" onclick="updateStatus(${item.id}, 'reject')"><i class="fas fa-times"></i></button>
                            <button class="btn btn-sm btn-light text-primary fw-bold flex-fill secured-item" data-permission="ledger_edit" onclick="openModal('edit', ${item.id})"><i class="fas fa-edit"></i> Edit</button>
                        </div>
                    </div>`;
                });
                $('#mobileCardsContainer').html(html || '<p class="text-center text-muted">No Ledgers found.</p>');
                if (typeof window.applyPermissions === 'function') window.applyPermissions();
            }

            // Mobile Search
            $('#mobileSearch').on('keyup', function() {
                let value = $(this).val().toLowerCase();
                $(".ledger-card").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

          window.openModal = function(type, id = null) {
                mode = type;
                $('#ledgerForm')[0].reset();
                $('#modalTitle').text(type === 'add' ? 'Add Ledger' : 'Edit Ledger');
                $('#edit_id').val('');
                
                // Naya reset
                $('#addPhaseToggle').prop('checked', false).trigger('change');
                $('#hidden_phase_id, #hidden_company_id, #company_display_input').val('');

                if (type === 'add') {
                    $.get('/api/v1/ledgers/generate-code', function(res) {
                        $('#f_code_input').val(res.code);
                    });
                } else if (type === 'edit') {
                    $.get(`/api/v1/ledgers/${id}`, function(res) {
                        let d = res.data;
                        $('#edit_id').val(d.id);
                        $('#f_name_input').val(d.ledger_name);
                        $('#f_code_input').val(d.ledger_code);
                        $('#f_from_input').val(d.from_date);
                        $('#f_to_input').val(d.to_date);
                        $('#f_status_input').val(d.status);

                        // Naya Edit Mode Logic Phase/Company ke liye
                        if(d.phase_id) {
                            $('#addPhaseToggle').prop('checked', true).trigger('change');
                            $('#hidden_phase_id').val(d.phase_id);
                            $('#hidden_company_id').val(d.company_id);
                            
                            // Names display karna (Kyunki humne relations with() mein load kiye hain)
                            if(d.phase) $('#phase_search_input').val(d.phase.phase_name);
                            if(d.company) $('#company_display_input').val(d.company.company_name);
                        }
                    });
                }
                $('#ledgerModal').modal('show');
            };

            // NAYA: Open View Modal with Details
            window.openViewModal = function(id) {
                $.get(`/api/v1/ledgers/${id}`, function(res) {
                    let d = res.data;
                    $('#v_code').text(d.ledger_code);
                    $('#v_name').text(d.ledger_name);
                    $('#v_from').text(d.from_date || 'N/A');
                    $('#v_to').text(d.to_date || 'N/A');

                    let badge = '';
                    if(d.status === 'Active') {
                        badge = '<span class="badge bg-success">Active</span>';
                    } else if(d.status === 'Pending') {
                        badge = '<span class="badge bg-warning text-dark">Pending</span>';
                    } else {
                        badge = '<span class="badge bg-danger">Inactive</span>';
                    }
                    $('#v_status_badge').html(badge);
                    
                    $('#viewModal').modal('show');
                }).fail(function() {
                    alert("Failed to fetch ledger details.");
                });
            };

            // 4. Save Data
            $('#ledgerForm').submit(function(e) {
                e.preventDefault();
                let id = $('#edit_id').val();
                let url = mode === 'add' ? '/api/v1/ledgers' : `/api/v1/ledgers/${id}`;
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

            // 5. Update Status (Approve/Reject)
            window.updateStatus = function(id, action) {
                if (confirm(`Are you sure you want to ${action} this ledger?`)) {
                    $.post(`/api/v1/ledgers/${id}/status`, {
                        action: action,
                        _method: 'POST'
                    }, function(res) {
                        alert(res.message);
                        table.ajax.reload(null, false);
                    }).fail(function(err) {
                        alert(err.responseJSON.message || "Unauthorized!");
                    });
                }
            };

            // 6. Select All Checkboxes
            window.selectAllCheckboxes = function() {
                let allChecked = $('.row-checkbox').length === $('.row-checkbox:checked').length;
                $('.row-checkbox').prop('checked', !allChecked); // Toggle all
                $('#bulkActions').show();
            };

            // 7. Delete Selected
            window.deleteSelected = function() {
                let ids = [];
                $('.row-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });

                if (ids.length === 0) return alert("Please select at least one item.");

                if (confirm(`Are you sure you want to delete ${ids.length} selected ledgers?`)) {
                    $.post('/api/v1/ledgers/bulk-delete', {
                        ids: ids,
                        _method: 'POST'
                    }, function(res) {
                        alert(res.message);
                        $('#bulkActions').hide();
                        table.ajax.reload(null, false);
                    });
                }
            };
        });
    </script>
@endpush
