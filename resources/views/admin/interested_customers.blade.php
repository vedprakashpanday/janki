@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        .table-custom th {
            background-color: var(--sidebar-bg);
            color: #fff;
            font-size: 12px;
            border: none;
            padding: 10px;
        }

        .table-custom td {
            font-size: 12px;
            vertical-align: middle;
            padding: 10px;
        }

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

        .nav-pills-custom .nav-link {
            border-radius: 8px;
            font-weight: bold;
            color: #6b7280;
            background: #f3f4f6;
            margin: 0 4px;
        }

        .nav-pills-custom .nav-link.active {
            background-color: var(--brand-primary);
            color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">General Leads Management</h4>

            <div class="d-flex gap-2">
                <button type="button" class="btn text-white px-3 py-2 shadow-sm secured-item"
                    data-permission="interested_customer_add_direct" style="background-color: var(--brand-primary);"
                    onclick="openModal('add_direct')">
                    <i class="fas fa-plus me-1"></i> Add Lead
                </button>

                <button type="button" class="btn text-dark px-3 py-2 shadow-sm secured-item"
                    data-permission="interested_customer_add_request" style="background-color: #facc15;"
                    onclick="openModal('add_request')">
                    <i class="fas fa-paper-plane me-1"></i> Request Lead
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4 admin-only-section" style="display:none;">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold"><i class="fas fa-headset me-2 text-primary"></i> Bulk Assign Telecaller (General)
                </h6>
            </div>
            <div class="card-body">
                <form id="assignForm" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Telecaller ID / Name</label>
                        <input type="text" id="a_telecaller" class="form-control" list="staffDataList"
                            placeholder="Search ID or Name" required autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data From (Row)</label>
                        <input type="number" id="a_from" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data To (Row)</label>
                        <input type="number" id="a_to" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn text-white w-100 fw-medium"
                            style="background-color: var(--sidebar-bg);">Assign Data</button>
                    </div>
                </form>
            </div>
        </div>

        <datalist id="staffDataList"></datalist>

        <div class="accordion mb-4 shadow-sm border-0 admin-only-section" id="filterAccordion" style="display:none;">
            <div class="accordion-item border-0">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold bg-light" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseFilters">
                        <i class="fas fa-filter me-2 text-primary"></i> Advanced Download & Print Reports
                    </button>
                </h2>
                <div id="collapseFilters" class="accordion-collapse collapse" data-bs-parent="#filterAccordion">
                    <div class="accordion-body">
                        <form id="filterForm" class="row g-3">
                            <div class="col-md-2"><label class="form-label">From Date</label><input type="date"
                                    id="r_from" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">To Date</label><input type="date"
                                    id="r_to" class="form-control"></div>
                            <div class="col-md-2">
                                <label class="form-label">Follow-up Month</label>
                                <select id="r_month" class="form-select">
                                    <option value="">-- All --</option>
                                    <option value="January">January</option>
                                    <option value="February">February</option>
                                    <option value="March">March</option>
                                    <option value="April">April</option>
                                    <option value="May">May</option>
                                    <option value="June">June</option>
                                    <option value="July">July</option>
                                    <option value="August">August</option>
                                    <option value="September">September</option>
                                    <option value="October">October</option>
                                    <option value="November">November</option>
                                    <option value="December">December</option>
                                </select>
                            </div>
                            <div class="col-md-2"><label class="form-label">Refer By</label><input type="text"
                                    id="r_refer" class="form-control" list="staffDataList"
                                    placeholder="Search Refer By"></div>
                            <div class="col-md-2"><label class="form-label">Budget From</label><input type="number"
                                    id="r_bfrom" class="form-control" placeholder="Min"></div>
                            <div class="col-md-2"><label class="form-label">Budget To</label><input type="number"
                                    id="r_bto" class="form-control" placeholder="Max"></div>

                            <div class="col-12 mt-3 d-flex gap-2">
                                <button type="button" class="btn btn-success px-4" onclick="fetchAndExport('excel')"><i
                                        class="fas fa-file-excel me-1"></i> Download Excel</button>
                                <button type="button" class="btn btn-primary px-4" onclick="fetchAndExport('print')"><i
                                        class="fas fa-print me-1"></i> Print Report</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-3 d-none d-md-flex" id="desktopTabs">
            <li class="nav-item"><button class="nav-link active fw-bold text-primary" data-bs-toggle="tab"
                    data-bs-target="#activeLeadsList">Active General Leads</button></li>
            <li class="nav-item director-admin-section" id="pendingTabItem" style="display:none;"><button
                    class="nav-link fw-bold text-warning" data-bs-toggle="tab"
                    data-bs-target="#pendingApprovalList">Pending Approval Requests</button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="activeLeadsList">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3 table-responsive">
                        <table id="dataTableMain" class="table table-hover table-custom w-100">
                            <thead>
                                <tr>
                                    <th>Company / Branch</th>
                                    <th>Lead Name</th>
                                    <th>Mobile</th>
                                    <th>Required For</th>
                                    <th>Refer By</th>
                                    <th>Telecaller</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="pendingApprovalList">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3 table-responsive">
                        <table id="pendingTable" class="table table-hover table-custom w-100">
                            <thead>
                                <tr>
                                    <th>Company / Branch</th>
                                    <th>Lead Name</th>
                                    <th>Mobile</th>
                                    <th>Status</th>
                                    <th>Requested By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="custModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalTitle" style="color: var(--sidebar-bg);">Manage General Lead
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="custForm" class="row g-3">
                        <input type="hidden" id="edit_id">

                        <input type="hidden" id="entry_type" name="entry_type" value="direct">

                        <div class="col-md-3">
                            <label class="form-label">Company *</label>
                            <select name="company_id" id="f_company" class="form-select" required></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Branch (Optional for HO)</label>
                            <select name="branch_id" id="f_branch" class="form-select">
                                <option value="">-- Head Office --</option>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Customer Name *</label><input type="text"
                                name="cust_name" id="f_name" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Assigned Tele-Caller</label><input type="text"
                                name="assigned_telecaller" id="f_tele" class="form-control" list="staffDataList"
                                autocomplete="off" placeholder="Search ID or Name"></div>

                        <div class="col-md-3"><label class="form-label">Mobile Number *</label><input type="text"
                                name="mobile" id="f_mob" class="form-control" maxlength="10" required></div>
                        <div class="col-md-3"><label class="form-label">Alternate Number</label><input type="text"
                                name="alternate_no" id="f_alt" class="form-control" maxlength="10"></div>
                        <div class="col-md-3"><label class="form-label">Email ID</label><input type="email"
                                name="email" id="f_email" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Date of Calling</label><input type="date"
                                name="date" id="f_date" class="form-control"></div>

                        <div class="col-md-3">
                            <label class="form-label">Reference Type</label>
                            <select name="reference" id="f_ref" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="ADMIN">Admin</option>
                                <option value="MARKETING">Marketing</option>
                                <option value="OTHER">Others</option>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Refer By</label><input type="text"
                                name="refer_by" id="f_refby" class="form-control" list="staffDataList"
                                autocomplete="off" placeholder="Search ID or Name"></div>
                        <div class="col-md-6"><label class="form-label">Address</label><input type="text"
                                name="address" id="f_addr" class="form-control"></div>

                        <div class="col-md-3">
                            <label class="form-label">Interested For</label>
                            <select name="interested_for" id="f_int" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="villa">Villa</option>
                                <option value="plot">Plot</option>
                                <option value="villa&plot">Villa & Plot</option>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Required For (Phase)</label><input type="text"
                                name="required_for" id="f_req" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Budget</label><input type="text"
                                name="budget" id="f_budget" class="form-control"></div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <input type="text" name="status" id="f_status" class="form-control" value="General"
                                readonly style="background-color: #e9ecef;">
                        </div>

                        <div class="col-md-3"><label class="form-label">Follow-up Date</label><input type="date"
                                name="followup_date" id="f_fdate" class="form-control"></div>
                        <div class="col-md-3">
                            <label class="form-label">Follow-up Month</label>
                            <select name="followup_month" id="f_fmonth" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="January">January</option>
                                <option value="February">February</option>
                                <option value="March">March</option>
                                <option value="April">April</option>
                                <option value="May">May</option>
                                <option value="June">June</option>
                                <option value="July">July</option>
                                <option value="August">August</option>
                                <option value="September">September</option>
                                <option value="October">October</option>
                                <option value="November">November</option>
                                <option value="December">December</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Remarks</label><input type="text"
                                name="remark" id="f_rem" class="form-control"></div>

                        <div class="col-12 text-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn px-5" id="saveBtn">Save Details</button>
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
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {
            let authRole = 'employee',
                authCompanyId = '',
                authBranchId = '',
                authProfileId = '';
            let isAdmin = false,
                isDirector = false;

            let listData = [],
                pendingData = [];
            let dataTableMain = $('#dataTableMain').DataTable({
                dom: '<"row"f>rt<"row"ip>'
            });
            let pendingTable = $('#pendingTable').DataTable({
                dom: '<"row"f>rt<"row"ip>'
            });

            function loadAllData() {
                $.ajax({
                   url: '/api/v1/interested-customers',
                    data: { per_page: 500, page: 1 },
                    success: function(res) {
                        authRole = (res.auth_role || 'employee').toLowerCase();
                        authCompanyId = res.auth_company || '';
                        authBranchId = res.auth_branch || '';
                        authProfileId = res.auth_profile_id || ''; // 🔥 ID Received

                        isAdmin = ['developer', 'ceo', 'admin'].includes(authRole);
                        isDirector = (authRole === 'director');

                        if (isAdmin) {
                            $('.admin-only-section').show();
                            dataTableMain.destroy();
                            dataTableMain = $('#dataTableMain').DataTable({
                                dom: '<"row"<"col-md-6"B><"col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                                buttons: [{
                                    extend: 'excelHtml5',
                                    className: 'btn btn-success btn-sm shadow-sm'
                                }]
                            });
                        }
                        if (isAdmin || isDirector) {
                            $('.director-admin-section').show();
                        }

                        listData = res.general || [];
                        pendingData = (res.pending_requests || []).filter(d => d.status === 'General');

                        let dlHtml = '';
                        res.staff_list.forEach(s => dlHtml +=
                            `<option value="${s.staff_id}">${s.name} (${s.role})</option>`);
                        $('#staffDataList').html(dlHtml);

                        renderTables();

                        if ($('#f_company option').length <= 1) {
                            loadCompanies();
                        } else {
                            applyCompanyLocks();
                        }

                        if (typeof window.applyPermissions === 'function') window.applyPermissions();
                    }
                });
            }
            loadAllData();

            function renderTables() {
                dataTableMain.clear();
                listData.forEach(d => {
                    let compName = d.company ? d.company.company_name : '-';
                    let bName = d.branch ? d.branch.branch_name : 'HO';
                    let actions =
                        `<button class="btn btn-sm btn-light text-primary me-1 edit-btn secured-item" data-permission="interested_customer_edit" data-id="${d.id}"><i class="fas fa-edit"></i></button><button class="btn btn-sm btn-light text-danger delete-btn secured-item" data-permission="interested_customer_delete" data-id="${d.id}"><i class="fas fa-trash"></i></button>`;
                    dataTableMain.row.add([
                        `<b>${compName}</b><br><small class="text-muted">${bName}</small>`,
                        d.cust_name, d.mobile, d.required_for || '-', d.refer_by || '-', d
                        .assigned_telecaller || '-',
                        `<span class="badge bg-secondary">${d.status}</span>`, actions
                    ]);
                });
                dataTableMain.draw();

                pendingTable.clear();
                pendingData.forEach(d => {
                    let compName = d.company ? d.company.company_name : '-';
                    let bName = d.branch ? d.branch.branch_name : 'HO';
                    let approvalActions = `<span class="badge bg-warning text-dark">Wait..</span>`;

                    if (isAdmin || isDirector) {
                        approvalActions =
                            `<button class="btn btn-sm btn-success me-1 approve-btn" data-id="${d.id}"><i class="fas fa-check"></i></button><button class="btn btn-sm btn-danger reject-btn" data-id="${d.id}"><i class="fas fa-times"></i></button>`;
                    }

                    pendingTable.row.add([
                        `<b>${compName}</b><br><small class="text-muted">${bName}</small>`,
                        d.cust_name, d.mobile,
                        `<span class="badge bg-warning text-dark">Pending</span>`,
                        d.assigned_telecaller || 'Staff', approvalActions
                    ]);
                });
                pendingTable.draw();
                if (typeof window.applyPermissions === 'function') window.applyPermissions();
            }

            function loadCompanies() {
                $.ajax({
                    url: '/api/v1/get-active-companies',
                    success: function(res) {
                        let options = '<option value="">-- Choose Company --</option>';
                        res.data.forEach(c => options +=
                            `<option value="${c.id}">${c.company_name}</option>`);
                        $('#f_company').html(options);
                        applyCompanyLocks();
                    }
                });
            }

            function applyCompanyLocks() {
                if (!isAdmin && authCompanyId) {
                    $('#f_company').val(authCompanyId).attr('style',
                        'pointer-events: none; background-color: #f1f1f1;');
                    loadBranches(authCompanyId, authBranchId);
                } else if (isDirector && authCompanyId) {
                    $('#f_company').val(authCompanyId).attr('style',
                        'pointer-events: none; background-color: #f1f1f1;');
                    loadBranches(authCompanyId);
                } else {
                    $('#f_company').removeAttr('style');
                }
            }

            $('#f_company').change(function() {
                loadBranches($(this).val());
            });

            function loadBranches(companyId, autoSelectBranchId = null) {
                if (!companyId) {
                    $('#f_branch').html('<option value="">-- Head Office --</option>');
                    return;
                }
                $.ajax({
                    url: '/api/v1/get-branches-by-companies',
                    type: 'POST',
                    data: {
                        company_ids: [companyId]
                    },
                    success: function(res) {
                        let options = '<option value="">-- Head Office --</option>';
                        res.data.forEach(b => options +=
                            `<option value="${b.id}">${b.branch_name}</option>`);
                        $('#f_branch').html(options);

                        if (!isAdmin && !isDirector && autoSelectBranchId) {
                            $('#f_branch').val(autoSelectBranchId).attr('style',
                                'pointer-events: none; background-color: #f1f1f1;');
                        } else {
                            $('#f_branch').removeAttr('style');
                        }
                    }
                });
            }

            window.openModal = function(type, id = null) {
                window.formMode = type.includes('add') ? 'add' : 'edit';

                $('#custForm')[0].reset();
                $('#edit_id').val('');

                if (type === 'add_request') {
                    $('#entry_type').val('request');
                    $('#modalTitle').text('Request General Lead');
                    $('#saveBtn').text('Submit Request').removeClass('btn-success').addClass(
                        'btn-warning text-dark');
                } else if (type === 'add_direct') {
                    $('#entry_type').val('direct');
                    $('#modalTitle').text('Add General Lead');
                    $('#saveBtn').text('Save Details').removeClass('btn-warning text-dark').addClass(
                        'btn-success');
                } else {
                    $('#entry_type').val('edit');
                    $('#modalTitle').text('Edit General Lead');
                    $('#saveBtn').text('Update Details').removeClass('btn-warning text-dark').addClass(
                        'btn-success');
                }

                $('#f_company').removeAttr('style');
                $('#f_branch').removeAttr('style');
                applyCompanyLocks();

                // 🔥 NAYA FIX: AUTOFILL & LOCK TELECALLER FOR EMPLOYEE
                if (window.formMode === 'add') {
                    if (!isAdmin && !isDirector) {
                        $('#f_tele').val(authProfileId).attr('readonly', true).css('background-color',
                            '#f1f1f1');
                    } else {
                        $('#f_tele').val('').removeAttr('readonly').css('background-color', '');
                    }
                }

                if (window.formMode === 'edit') {
                    $.get({
                        url: `/api/v1/interested-customers/${id}`,
                        success: function(res) {
                            let d = res.data;
                            $('#edit_id').val(d.id);

                            $.ajax({
                                url: '/api/v1/get-branches-by-companies',
                                type: 'POST',
                                data: {
                                    company_ids: [d.company_id]
                                },
                                success: function(bRes) {
                                    let options =
                                        '<option value="">-- Head Office --</option>';
                                    bRes.data.forEach(b => options +=
                                        `<option value="${b.id}">${b.branch_name}</option>`
                                    );
                                    $('#f_branch').html(options);

                                    $('#f_company').val(d.company_id);
                                    $('#f_branch').val(d.branch_id);

                                    if (!isAdmin && !isDirector) {
                                        $('#f_company').attr('style',
                                            'pointer-events: none; background-color: #f1f1f1;'
                                        );
                                        $('#f_branch').attr('style',
                                            'pointer-events: none; background-color: #f1f1f1;'
                                        );
                                    } else if (isDirector) {
                                        $('#f_company').attr('style',
                                            'pointer-events: none; background-color: #f1f1f1;'
                                        );
                                    }
                                }
                            });

                            $('#f_name').val(d.cust_name);
                            $('#f_tele').val(d.assigned_telecaller);

                            // 🔥 Lock during edit as well for employee
                            if (!isAdmin && !isDirector) {
                                $('#f_tele').attr('readonly', true).css('background-color',
                                    '#f1f1f1');
                            } else {
                                $('#f_tele').removeAttr('readonly').css('background-color', '');
                            }

                            $('#f_mob').val(d.mobile);
                            $('#f_alt').val(d.alternate_no);
                            $('#f_email').val(d.email);
                            $('#f_date').val(d.date);
                            $('#f_ref').val(d.reference);
                            $('#f_refby').val(d.refer_by);
                            $('#f_addr').val(d.address);
                            $('#f_int').val(d.interested_for);
                            $('#f_req').val(d.required_for);
                            $('#f_budget').val(d.budget);
                            $('#f_fdate').val(d.followup_date);
                            $('#f_fmonth').val(d.followup_month);
                            $('#f_rem').val(d.remark);
                        }
                    });
                }
                $('#custModal').modal('show');
            };

            $(document).on('click', '.edit-btn', function() {
                openModal('edit', $(this).data('id'));
            });

            $('#custForm').submit(function(e) {
                e.preventDefault();
                let id = $('#edit_id').val();
                let url = window.formMode === 'add' ? '/api/v1/interested-customers' :
                    `/api/v1/interested-customers/${id}`;
                $.ajax({
                    url: url,
                    type: window.formMode === 'add' ? 'POST' : 'PUT',
                    data: $(this).serialize(),
                    success: function(res) {
                        alert(res.message);
                        $('#custModal').modal('hide');
                        loadAllData();
                    }
                });
            });

            window.processWorkflow = function(id, status) {
                $.ajax({
                    url: `/api/v1/interested-customers/${id}/status`,
                    type: 'POST',
                    data: {
                        entry_status: status
                    },
                    success: function(res) {
                        alert(res.message);
                        loadAllData();
                    }
                });
            };
            $(document).on('click', '.approve-btn', function() {
                if (confirm("Approve?")) processWorkflow($(this).data('id'), 'active');
            });
            $(document).on('click', '.reject-btn', function() {
                if (confirm("Reject?")) processWorkflow($(this).data('id'), 'inactive');
            });
            $(document).on('click', '.delete-btn', function() {
                if (confirm("Delete?")) {
                    $.ajax({
                        url: `/api/v1/interested-customers/${$(this).data('id')}`,
                        type: 'DELETE',
                        success: function() {
                            loadAllData();
                        }
                    });
                }
            });
        });
    </script>
@endpush
