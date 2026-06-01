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

        .status-active {
            background: #dcfce7;
            color: #166534;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .comm-badge {
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 12px;
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Member Designations & Commission</h4>
            </div>
            <button type="button" class="btn text-white px-3 py-2 shadow-sm secured-item"
                data-permission="member_designation_add" style="background-color: var(--brand-primary);"
                onclick="openModal('add')">
                <i class="fas fa-plus me-1"></i> Add Designation
            </button>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3 flex-column">
            <div class="d-flex gap-2">
                <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search Designation...">
                <button type="button" class="btn text-white shadow-sm px-3" style="background-color: #10b981;"
                    id="mobileExcelBtn"><i class="fas fa-file-excel"></i></button>
            </div>
            <select id="mobile_branch_filter" class="form-select shadow-sm border-primary">
                <option value="">-- Show All Branches --</option>
            </select>
        </div>

        <div class="d-none d-md-flex justify-content-end mb-3">
            <div class="input-group shadow-sm" style="max-width: 350px;">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-filter text-primary"></i></span>
                <select id="desktop_branch_filter" class="form-select border-start-0 fw-bold text-secondary">
                    <option value="">-- Show All Branches --</option>
                </select>
            </div>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="desigTable" class="table table-hover table-custom w-100">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>Code</th>
                                <th>Designation Name</th>
                                <th>Commission (%)</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="mobileCardsContainer" class="d-block d-md-none"></div>
    </div>

    <div class="modal fade" id="desigModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold" id="modalTitle" style="color: var(--sidebar-bg);"><i
                            class="fas fa-briefcase me-2 text-primary"></i> Manage Designation</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="desigForm">
                        <input type="hidden" id="edit_id">

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Select Branch <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="f_branch" list="branchList"
                                placeholder="Search Branch..." required autocomplete="off">
                            <input type="hidden" name="branch_id" id="branch_id_hidden" required>
                            <datalist id="branchList"></datalist>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Designation Code (Short) <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" name="designation_code" id="f_code"
                                required placeholder="e.g. SE, BM" maxlength="10">
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Designation Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="designation_name" id="f_name" required
                                placeholder="e.g. Sales Executive (S.E.)">
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Commission Percentage (%)</label>
                            <input type="number" step="0.01" class="form-control" name="commission_percentage"
                                id="f_comm" placeholder="e.g. 5.50">
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-secondary small fw-bold">Status</label>
                            <select class="form-select" name="status" id="f_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <button type="submit" class="btn text-white w-100 py-2 fw-medium shadow-sm"
                            style="background-color: var(--brand-primary);" id="saveBtn">Save Designation</button>
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
            // Isko rehne diya taaki aage reference error na aaye
            const apiToken = localStorage.getItem('admin_token');
            let mode = 'add';

            function loadBranchFilters() {
                $.ajax({
                    url: '/api/v1/branches', // Updated URL
                    success: function(res) {
                        let filterOpts = '<option value="">-- Show All Branches --</option>';
                        res.data.forEach(b => {
                            let compName = b.company ? b.company.company_name :
                                'Master Company';
                            filterOpts +=
                                `<option value="${b.id}">${compName} - ${b.branch_name}</option>`;
                        });
                        $('#desktop_branch_filter, #mobile_branch_filter').html(filterOpts);
                    }
                });
            }
            loadBranchFilters();

            $('#desktop_branch_filter, #mobile_branch_filter').on('change', function() {
                let selectedVal = $(this).val();
                $('#desktop_branch_filter').val(selectedVal);
                $('#mobile_branch_filter').val(selectedVal);
                table.ajax.reload();
            });

            // 1. DataTables
            let table = $('#desigTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/member-designations', // Updated URL
                    data: function(d) {
                        d.branch_id = $(window).width() < 768 ? $('#mobile_branch_filter').val() : $(
                            '#desktop_branch_filter').val();
                    }
                },
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export Excel',
                    className: 'btn btn-success btn-sm shadow-sm rounded-3'
                }],
                columns: [{
                        data: 'branch_id',
                        render: (d, t, row) => {
                            if (!row.branch) return 'N/A';
                            let compName = row.branch.company ? row.branch.company.company_name :
                                'Master Company';
                            return `<div class="small fw-bold text-primary"><i class="fas fa-building me-1"></i> ${compName}</div>
                        <div class="small text-muted"><i class="fas fa-map-marker-alt text-danger me-1"></i> ${row.branch.branch_name}</div>`;
                        }
                    },
                    {
                        data: 'designation_code',
                        render: d => `<span class="fw-bold text-primary">${d}</span>`
                    },
                    {
                        data: 'designation_name',
                        render: d => `<span class="fw-medium">${d}</span>`
                    },
                    {
                        data: 'commission_percentage',
                        render: d => `<span class="comm-badge">${d}%</span>`
                    },
                    {
                        data: 'status',
                        render: d => d === 'active' ? `<span class="status-active">Active</span>` :
                            `<span class="status-inactive">Inactive</span>`
                    },
                    {
                        data: 'id',
                        orderable: false,
                        render: d => `
                <div class="text-end">
                    <button type="button" class="btn btn-sm btn-light text-primary me-1 edit-btn secured-item" data-permission="member_designation_edit" data-id="${d}"><i class="fas fa-edit"></i> Edit</button>
                    <button type="button" class="btn btn-sm btn-light text-danger delete-btn secured-item" data-permission="member_designation_delete" data-id="${d}"><i class="fas fa-trash-alt"></i></button>
                </div>`
                    }
                ],
                drawCallback: function(settings) {
                    renderMobileCards(settings.json.data);
                    // 🛡️ Ensure permissions are applied after table redraw
                    if (typeof window.applyPermissions === 'function') window.applyPermissions();
                }
            });

            // 2. Mobile Cards
            function renderMobileCards(data) {
                let html = '';
                if (!data || data.length === 0) {
                    html =
                        '<div class="text-center p-3 text-muted border rounded bg-light">No designations found.</div>';
                } else {
                    data.forEach(d => {
                        let statusHtml = d.status === 'active' ?
                            `<span class="status-active">Active</span>` :
                            `<span class="status-inactive">Inactive</span>`;
                        html += `<div class="mobile-item">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-1">${d.designation_name}</h6>
                            <span class="fw-bold text-primary small">${d.designation_code}</span>
                        </div>
                        ${statusHtml}
                    </div>
                    <div class="small text-muted mb-3"><span class="comm-badge">Commission: ${d.commission_percentage}%</span></div>
                    <div class="border-top pt-2 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light text-primary flex-fill edit-btn secured-item" data-permission="member_designation_edit" data-id="${d.id}"><i class="fas fa-edit"></i> Edit</button>
                        <button type="button" class="btn btn-sm btn-light text-danger flex-fill delete-btn secured-item" data-permission="member_designation_delete" data-id="${d.id}"><i class="fas fa-trash-alt"></i> Delete</button>
                    </div>
                </div>`;
                    });
                }
                $('#mobileCardsContainer').html(html);
                // 🛡️ RE-APPLY PERMISSIONS for mobile
                if (typeof window.applyPermissions === 'function') window.applyPermissions();
            }

            // Mobile Search & Excel functionality
            $('#mobileSearch').on('keyup', function() {
                let v = $(this).val().toLowerCase();
                $('.mobile-item').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1)
                });
            });
            $('#mobileExcelBtn').click(() => $('.buttons-excel').click());

            let branchMap = {};

            $('#f_branch').on('input change', function() {
                let val = $(this).val();
                if (branchMap[val]) {
                    $('#branch_id_hidden').val(branchMap[val]);
                    this.setCustomValidity('');
                } else {
                    $('#branch_id_hidden').val('');
                    this.setCustomValidity('Please select a valid branch');
                }
            });

            // 3. Open Modal
            window.openModal = function(type, id = null) {
                mode = type;
                $('#desigForm')[0].reset();
                $('#branch_id_hidden').val('');
                $('#modalTitle').html(type === 'add' ?
                    '<i class="fas fa-plus-circle me-2 text-primary"></i> Add Designation' :
                    '<i class="fas fa-edit me-2 text-primary"></i> Edit Designation');

                // Branch API call (Updated URL)
                $.ajax({
                    url: '/api/v1/branches',
                    success: function(res) {
                        let options = '';
                        branchMap = {};
                        res.data.forEach(b => {
                            let compName = b.company ? b.company.company_name :
                                'Master Company';
                            let disp = `${compName} - ${b.branch_name} (${b.branch_id})`;
                            options += `<option value="${disp}">`;
                            branchMap[disp] = b.id;
                        });
                        $('#branchList').html(options);

                        if (type === 'edit') {
                            $.get({
                                url: `/api/v1/member-designations/${id}`, // Updated URL
                                success: function(res) {
                                    let d = res.data;
                                    $('#edit_id').val(d.id);

                                    // Edit me branch set karna
                                    if (d.branch) {
                                        let compName = d.branch.company ? d.branch
                                            .company.company_name : 'Master Company';
                                        let disp =
                                            `${compName} - ${d.branch.branch_name} (${d.branch.branch_id})`;
                                        $('#f_branch').val(disp);
                                        $('#branch_id_hidden').val(d.branch_id);
                                    }

                                    $('#f_name').val(d.designation_name);
                                    $('#f_comm').val(d.commission_percentage);
                                    $('#f_status').val(d.status);
                                    $('#f_code').val(d.designation_code);
                                }
                            });
                        }
                    }
                });
                $('#desigModal').modal('show');
            };

            $(document).on('click', '.edit-btn', function() {
                openModal('edit', $(this).data('id'));
            });

            // 4. Form Submit
            $('#desigForm').submit(function(e) {
                e.preventDefault();
                let id = $('#edit_id').val();
                let url = mode === 'add' ? '/api/v1/member-designations' :
                    `/api/v1/member-designations/${id}`; // Updated URL
                let type = mode === 'add' ? 'POST' : 'PUT';

                let btn = $('#saveBtn');
                btn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: url,
                    type: type,
                    data: $(this).serialize(),
                    success: function(res) {
                        $('#desigModal').modal('hide');
                        table.ajax.reload(null, false);
                        // loadMobile() hata diya gaya hai, kyunki DataTables draw hone par automatically renderMobileCards() call hota hai!
                    },
                    error: function(err) {
                        alert(err.responseJSON.message);
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Save Designation');
                    }
                });
            });

            // 5. Delete Logic
            $(document).on('click', '.delete-btn', function() {
                if (confirm("Are you sure you want to delete this Designation?")) {
                    $.ajax({
                        url: `/api/v1/member-designations/${$(this).data('id')}`, // Updated URL
                        type: 'DELETE',
                        success: function() {
                            table.ajax.reload(null, false);
                            // loadMobile() hata diya gaya hai
                        }
                    });
                }
            });
        });
    </script>
@endpush
