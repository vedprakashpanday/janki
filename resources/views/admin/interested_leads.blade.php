@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/scroller/2.4.1/css/scroller.bootstrap5.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        .table-custom th { background-color: var(--sidebar-bg); color: #fff; font-size: 12px; border: none; padding: 10px; }
        .table-custom td { font-size: 12px; vertical-align: middle; padding: 10px; }
        .mobile-item { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 15px; margin-bottom: 15px; box-shadow: 0 4px 6px var(--shadow-color); }
        .form-label { font-size: 12px; font-weight: 700; color: #4b5563; }
        .nav-pills-custom .nav-link { border-radius: 8px; font-weight: bold; color: #6b7280; background: #f3f4f6; margin: 0 4px; }
        .nav-pills-custom .nav-link.active { background-color: var(--brand-primary); color: #fff; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .action-btns button { margin-right: 3px; margin-bottom: 3px; }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Interested Leads Management</h4>
            <div class="d-flex gap-2">
                <button type="button" class="btn text-white px-3 py-2 shadow-sm secured-item"
                    data-permission="interested_leads_add_direct" style="background-color: #10b981;"
                    onclick="$('#importExcel').click()"><i class="fas fa-file-import me-1"></i> Import Excel</button>
                <input type="file" id="importExcel" class="d-none" accept=".xlsx, .xls, .csv">
                
                <button type="button" class="btn text-white px-3 py-2 shadow-sm secured-item"
                    data-permission="interested_leads_add_direct" style="background-color: var(--brand-primary);"
                    onclick="openModal('add_direct')"><i class="fas fa-plus me-1"></i> Add Lead</button>
                    
                <button type="button" class="btn text-dark px-3 py-2 shadow-sm secured-item"
                    data-permission="interested_leads_add_reque" style="background-color: #facc15;"
                    onclick="openModal('add_request')"><i class="fas fa-paper-plane me-1"></i> Request Lead</button>
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
                            <div class="col-md-2"><label class="form-label">From Date</label><input type="date" id="r_from" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">To Date</label><input type="date" id="r_to" class="form-control"></div>
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
                            <div class="col-md-2"><label class="form-label">Refer By</label><input type="text" id="r_refer" class="form-control" list="staffDataList" placeholder="Search Refer By"></div>
                            <div class="col-md-2"><label class="form-label">Budget From</label><input type="number" id="r_bfrom" class="form-control" placeholder="Min"></div>
                            <div class="col-md-2"><label class="form-label">Budget To</label><input type="number" id="r_bto" class="form-control" placeholder="Max"></div>

                            <div class="col-12 mt-3 d-flex gap-2">
                                <button type="button" class="btn btn-success px-4 secured-item"
                                    data-permission="interested_leads_export" onclick="downloadFilteredExcel()"><i
                                        class="fas fa-file-excel me-1"></i> Download Filtered Excel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-3 d-none d-lg-flex" id="desktopTabs">
            <li class="nav-item"><button class="nav-link active fw-bold text-primary" data-bs-toggle="tab" data-bs-target="#activeLeadsList">Active Interested Leads</button></li>
            <li class="nav-item"><button class="nav-link fw-bold text-warning" data-bs-toggle="tab" data-bs-target="#pendingApprovalList">Pending Approval Requests</button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="activeLeadsList">
                <div class="card border-0 shadow-sm mb-4 d-none d-lg-block">
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
                <div class="card border-0 shadow-sm mb-4 d-none d-lg-block">
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
                    <h5 class="modal-title fw-bold" id="modalTitle" style="color: var(--sidebar-bg);">Manage Interested Lead</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="custForm" class="row g-3">
                        <input type="hidden" id="edit_id">
                        <input type="hidden" id="entry_type" name="entry_type" value="direct">

                        <div class="col-md-3"><label class="form-label">Company *</label><select name="company_id" id="f_company" class="form-select" required></select></div>
                        <div class="col-md-3"><label class="form-label">Branch (Optional for HO)</label><select name="branch_id" id="f_branch" class="form-select"><option value="">-- Head Office --</option></select></div>
                        <div class="col-md-3"><label class="form-label">Customer Name *</label><input type="text" name="cust_name" id="f_name" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Assigned Tele-Caller</label><input type="text" name="assigned_telecaller" id="f_tele" class="form-control" list="staffDataList" autocomplete="off"></div>

                        <div class="col-md-3"><label class="form-label">Mobile Number *</label><input type="text" name="mobile" id="f_mob" class="form-control" maxlength="10" required></div>
                        <div class="col-md-3"><label class="form-label">Alternate Number</label><input type="text" name="alternate_no" id="f_alt" class="form-control" maxlength="10"></div>
                        <div class="col-md-3"><label class="form-label">Email ID</label><input type="email" name="email" id="f_email" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Date of Calling</label><input type="date" name="date" id="f_date" class="form-control"></div>

                        <div class="col-md-3">
                            <label class="form-label">Reference Type</label>
                            <select name="reference" id="f_ref" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="ADMIN">Admin</option>
                                <option value="MARKETING">Marketing</option>
                                <option value="OTHER">Others</option>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Refer By</label><input type="text" name="refer_by" id="f_refby" class="form-control" list="staffDataList" autocomplete="off"></div>
                        <div class="col-md-6"><label class="form-label">Address</label><input type="text" name="address" id="f_addr" class="form-control"></div>

                        <div class="col-md-3">
                            <label class="form-label">Interested For</label>
                            <select name="interested_for" id="f_int" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="villa">Villa</option>
                                <option value="plot">Plot</option>
                                <option value="villa&plot">Villa & Plot</option>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Required For (Phase)</label><input type="text" name="required_for" id="f_req" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Budget</label><input type="text" name="budget" id="f_budget" class="form-control"></div>
                        
                        <div class="col-md-3">
    <label class="form-label text-danger">Status (Not General) *</label>
    <select name="status" id="f_status" class="form-select" required>
        <option value="">-- Select --</option>
        <option value="Connected">Connected</option>
        <option value="Not Reachable">Not Reachable</option>
        <option value="Follow Up">Follow Up</option>
        <option value="Site Visit Schedule">Site Visit Schedule</option>
        <option value="Site Visit Done">Site Visit Done</option>
        <option value="Booking Done">Booking Done</option>
        <option value="Lost">Lost</option>
    </select>
</div>

                        <div class="col-md-3"><label class="form-label">Follow-up Date</label><input type="date" name="followup_date" id="f_fdate" class="form-control"></div>
                        <div class="col-md-3">
                            <label class="form-label">Follow-up Month</label>
                            <select name="followup_month" id="f_fmonth" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="January">January</option>
                                <option value="February">February</option>
                                </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Remarks</label><input type="text" name="remark" id="f_rem" class="form-control"></div>

                        <div class="col-12 text-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn px-5 btn-success" id="saveBtn">Save Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i class="fas fa-eye me-2 text-primary"></i>View Interested Lead Details</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-4"><p class="text-muted mb-1 small fw-bold">Company</p><h6 id="v_company" class="fw-bold">-</h6></div>
                    <div class="col-md-4"><p class="text-muted mb-1 small fw-bold">Branch</p><h6 id="v_branch" class="fw-bold">-</h6></div>
                    <div class="col-md-4"><p class="text-muted mb-1 small fw-bold">Customer Name</p><h6 id="v_name" class="fw-bold text-primary">-</h6></div>
                    <div class="col-md-4"><p class="text-muted mb-1 small fw-bold">Mobile Number</p><h6 id="v_mob" class="fw-bold">-</h6></div>
                    <div class="col-md-4"><p class="text-muted mb-1 small fw-bold">Alternate Number</p><h6 id="v_alt" class="fw-bold">-</h6></div>
                    <div class="col-md-4"><p class="text-muted mb-1 small fw-bold">Email ID</p><h6 id="v_email" class="fw-bold">-</h6></div>
                    <div class="col-md-4"><p class="text-muted mb-1 small fw-bold">Assigned Tele-Caller</p><h6 id="v_tele" class="fw-bold">-</h6></div>
                    <div class="col-md-4"><p class="text-muted mb-1 small fw-bold">Reference Type</p><h6 id="v_ref" class="fw-bold">-</h6></div>
                    <div class="col-md-4"><p class="text-muted mb-1 small fw-bold">Refer By</p><h6 id="v_refby" class="fw-bold">-</h6></div>
                    <div class="col-md-4"><p class="text-muted mb-1 small fw-bold">Interested For</p><h6 id="v_int" class="fw-bold">-</h6></div>
                    <div class="col-md-4"><p class="text-muted mb-1 small fw-bold">Required For (Phase)</p><h6 id="v_req" class="fw-bold">-</h6></div>
                    <div class="col-md-4"><p class="text-muted mb-1 small fw-bold">Budget</p><h6 id="v_budget" class="fw-bold">-</h6></div>
                    <div class="col-md-4"><p class="text-muted mb-1 small fw-bold">Date of Calling</p><h6 id="v_date" class="fw-bold">-</h6></div>
                    <div class="col-md-4"><p class="text-muted mb-1 small fw-bold">Follow-up Date</p><h6 id="v_fdate" class="fw-bold">-</h6></div>
                    <div class="col-md-4"><p class="text-muted mb-1 small fw-bold">Status</p><h6><span id="v_status" class="badge bg-primary"></span></h6></div>
                    <div class="col-md-12 border-top pt-3 mt-3"><p class="text-muted mb-1 small fw-bold">Address</p><h6 id="v_addr" class="fw-bold">-</h6></div>
                    <div class="col-md-12"><p class="text-muted mb-1 small fw-bold">Remarks</p><h6 id="v_rem" class="fw-bold">-</h6></div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>



@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/scroller/2.4.1/js/dataTables.scroller.min.js"></script>

    <script>
        $(document).ready(function() {
            let authRole = 'employee', authCompanyId = '', authBranchId = '', authProfileId = '';
            let isAdmin = false, isDirector = false;
            let listData = [], pendingData = [];

            let dataTableMain = $('#dataTableMain').DataTable({ dom: '<"row"f>rt<"row"ip>' });
            let pendingTable = $('#pendingTable').DataTable({ dom: '<"row"f>rt<"row"ip>' });

            // 🔥 Export Filtered Data (Passing type=interested)
            window.downloadFilteredExcel = function() {
                let btn = $('#filterForm button.btn-success');
                let originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Downloading...');

                let params = new URLSearchParams({
                    type: 'interested', // Backend ko batane ke liye
                    from_date: $('#r_from').val(),
                    to_date: $('#r_to').val(),
                    followup_month: $('#r_month').val(),
                    refer_by: $('#r_refer').val(),
                    budget_from: $('#r_bfrom').val(),
                    budget_to: $('#r_bto').val()
                }).toString();

                $.ajax({
                    url: '/api/v1/general-leads/export?' + params,
                    type: 'GET',
                    success: function(res) {
                        btn.html(originalText);
                        if(res.data && res.data.length > 0) {
                            let ws = XLSX.utils.json_to_sheet(res.data);
                            let wb = XLSX.utils.book_new();
                            XLSX.utils.book_append_sheet(wb, ws, "Interested Leads");
                            XLSX.writeFile(wb, "Interested_Leads_Filtered.xlsx");
                        } else {
                            alert("No matching data found.");
                        }
                    },
                    error: function() { btn.html(originalText); alert("Export failed."); }
                });
            };

            function loadAllData() {
                // 🔥 Passing type=interested in API call
                $.ajax({
                    url: '/api/v1/interested-customers?type=interested',
                    type: 'GET',
                    success: function(res) {
                        authRole = (res.auth_role || 'employee').toLowerCase();
                        authCompanyId = res.auth_company || '';
                        authBranchId = res.auth_branch || '';
                        authProfileId = res.auth_profile_id || '';
                        
                        isAdmin = ['developer', 'ceo', 'admin'].includes(authRole);
                        isDirector = (authRole === 'director');

                        if (isAdmin) $('.admin-only-section').show();

                        // 🔥 Controller will send interested leads here now
                        listData = res.general || []; 
                        pendingData = res.pending_requests || [];

                        let dlHtml = '';
                        res.staff_list.forEach(s => dlHtml += `<option value="${s.staff_id}">${s.name} (${s.role})</option>`);
                        $('#staffDataList').html(dlHtml);

                        renderTables();
                        if ($('#f_company option').length <= 1) loadCompanies();
                        else applyCompanyLocks();

                        if (typeof window.applyPermissions === 'function') window.applyPermissions();
                    }
                });
            }
            loadAllData();

            function renderTables() {
                if ($.fn.DataTable.isDataTable('#dataTableMain')) { dataTableMain.destroy(); }
                
                dataTableMain = $('#dataTableMain').DataTable({
                    data: listData,
                    deferRender: true,
                    scroller: true,
                    scrollY: 400,
                    dom: isAdmin ? '<"row"<"col-md-6"B><"col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>' : '<"row"f>rt<"row"ip>',
                    buttons: isAdmin ? [{
                        text: '<i class="fas fa-file-excel me-1"></i> Download Excel',
                        className: 'btn btn-success btn-sm secured-item admin-only-section',
                        attr: { 'data-permission': 'interested_leads_export' },
                        action: function(e, dt, node, config) {
                            let searchVal = dt.search() || '';
                            let btn = $(node); let originalText = btn.html();
                            btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Exporting...');
                            
                            $.ajax({
                                url: '/api/v1/general-leads/export?type=interested&search=' + encodeURIComponent(searchVal),
                                type: 'GET',
                                success: function(res) {
                                    btn.html(originalText);
                                    if(res.data && res.data.length > 0) {
                                        let ws = XLSX.utils.json_to_sheet(res.data);
                                        let wb = XLSX.utils.book_new();
                                        XLSX.utils.book_append_sheet(wb, ws, "Interested Leads");
                                        XLSX.writeFile(wb, "Interested_Leads_Export.xlsx");
                                    } else { alert("No data found."); }
                                },
                                error: function() { btn.html(originalText); alert("Export failed."); }
                            });
                        }
                    }] : [],
                    columns: [
                        { data: null, render: d => `<b>${d.company?.company_name || '-'}</b><br><small>${d.branch?.branch_name || 'HO'}</small>` },
                        { data: 'cust_name' },
                        { data: 'mobile' },
                        { data: 'required_for', defaultContent: '-' },
                        { data: 'refer_by', defaultContent: '-' },
                        { data: 'assigned_telecaller', defaultContent: '-' },
                        { data: 'status', render: d => `<span class="badge bg-primary">${d}</span>` },
                        { data: null, render: d => `
                            <div class="action-btns">
                                <button class="btn btn-sm btn-light text-info view-btn secured-item" data-permission="interested_leads_view" data-id="${d.id}"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-light text-primary edit-btn secured-item" data-permission="interested_leads_edit" data-id="${d.id}"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-light text-danger delete-btn" data-id="${d.id}"><i class="fas fa-trash"></i></button>
                            </div>` 
                        }
                    ]
                });

                if ($.fn.DataTable.isDataTable('#pendingTable')) { pendingTable.destroy(); }
                pendingTable = $('#pendingTable').DataTable({
                    data: pendingData,
                    deferRender: true,
                    scrollY: 400,
                    columns: [
                        { data: null, render: d => `<b>${d.company?.company_name || '-'}</b><br><small>${d.branch?.branch_name || 'HO'}</small>` },
                        { data: 'cust_name' },
                        { data: 'mobile' },
                        { data: null, render: () => `<span class="badge bg-warning text-dark">Pending</span>` },
                        { data: 'assigned_telecaller', defaultContent: 'Staff' },
                        { data: null, render: d => `
                            <button class="btn btn-sm btn-success approve-btn secured-item" data-permission="interested_leads_appr" data-id="${d.id}">Appr</button> 
                            <button class="btn btn-sm btn-danger reject-btn secured-item" data-permission="interested_leads_rej" data-id="${d.id}">Rej</button>` 
                        }
                    ]
                });
            }

            // Standard Boilerplate Functions (Load Companies, Branches, etc.)
            function loadCompanies() {
                $.ajax({
                    url: '/api/v1/get-active-companies',
                    success: function(res) {
                        let options = '<option value="">-- Choose Company --</option>';
                        res.data.forEach(c => options += `<option value="${c.id}">${c.company_name}</option>`);
                        $('#f_company').html(options);
                        applyCompanyLocks();
                    }
                });
            }

            function applyCompanyLocks() {
                if (!isAdmin && authCompanyId) {
                    $('#f_company').val(authCompanyId).attr('style', 'pointer-events: none; background-color: #f1f1f1;');
                    loadBranches(authCompanyId, authBranchId);
                } else if (isDirector && authCompanyId) {
                    $('#f_company').val(authCompanyId).attr('style', 'pointer-events: none; background-color: #f1f1f1;');
                    loadBranches(authCompanyId);
                } else { $('#f_company').removeAttr('style'); }
            }

            $('#f_company').change(function() { loadBranches($(this).val()); });

            function loadBranches(companyId, autoSelectBranchId = null) {
                if (!companyId) { $('#f_branch').html('<option value="">-- Head Office --</option>'); return; }
                $.ajax({
                    url: '/api/v1/get-branches-by-companies',
                    type: 'POST', data: { company_ids: [companyId] },
                    success: function(res) {
                        let options = '<option value="">-- Head Office --</option>';
                        res.data.forEach(b => options += `<option value="${b.id}">${b.branch_name}</option>`);
                        $('#f_branch').html(options);
                    }
                });
            }

            window.openModal = function(type, id = null) {
                window.formMode = type.includes('add') ? 'add' : 'edit';
                $('#custForm')[0].reset();
                $('#edit_id').val('');
                // Reset status to Interested instead of General
                $('#f_status').val('');

                if (type === 'add_request') {
                    $('#entry_type').val('request'); $('#modalTitle').text('Request Interested Lead');
                } else if (type === 'add_direct') {
                    $('#entry_type').val('direct'); $('#modalTitle').text('Add Interested Lead');
                } else {
                    $('#entry_type').val('edit'); $('#modalTitle').text('Edit Interested Lead');
                }

                $('#f_company').removeAttr('style'); $('#f_branch').removeAttr('style');
                applyCompanyLocks();

                if (window.formMode === 'add') {
                    if (!isAdmin && !isDirector) $('#f_tele').val(authProfileId).attr('readonly', true).css('background-color', '#f1f1f1');
                    else $('#f_tele').val('').removeAttr('readonly').css('background-color', '');
                }

                if (window.formMode === 'edit') {
                    $.get({
                        url: `/api/v1/interested-customers/${id}`,
                        success: function(res) {
                            let d = res.data;
                            $('#edit_id').val(d.id);
                            loadBranches(d.company_id, d.branch_id);
                            setTimeout(() => {
                                $('#f_company').val(d.company_id);
                                $('#f_branch').val(d.branch_id);
                            }, 500);

                            $('#f_name').val(d.cust_name); $('#f_tele').val(d.assigned_telecaller);
                            $('#f_mob').val(d.mobile); $('#f_alt').val(d.alternate_no);
                            $('#f_email').val(d.email); $('#f_date').val(d.date);
                            $('#f_ref').val(d.reference); $('#f_refby').val(d.refer_by);
                            $('#f_addr').val(d.address); $('#f_int').val(d.interested_for);
                            $('#f_req').val(d.required_for); $('#f_budget').val(d.budget);
                            $('#f_status').val(d.status); $('#f_fdate').val(d.followup_date);
                            $('#f_fmonth').val(d.followup_month); $('#f_rem').val(d.remark);
                        }
                    });
                }
                $('#custModal').modal('show');
            };

            $(document).on('click', '.edit-btn', function() { openModal('edit', $(this).data('id')); });

            $('#custForm').submit(function(e) {
                e.preventDefault();
                let id = $('#edit_id').val();
                let url = window.formMode === 'add' ? '/api/v1/interested-customers' : `/api/v1/interested-customers/${id}`;
                let type = window.formMode === 'add' ? 'POST' : 'PUT';
                $.ajax({
                    url: url, type: type, data: $(this).serialize(), dataType: 'json',
                    success: function(res) { alert(res.message); $('#custModal').modal('hide'); loadAllData(); },
                    error: function(xhr) { alert("Error: " + (xhr.responseJSON ? xhr.responseJSON.message : "Failed")); }
                });
            });

            window.processWorkflow = function(id, status) {
                $.ajax({
                    url: `/api/v1/interested-customers/${id}/status`, type: 'POST', data: { entry_status: status },
                    success: function(res) { alert(res.message); loadAllData(); }
                });
            };
            $(document).on('click', '.approve-btn', function() { if (confirm("Approve?")) processWorkflow($(this).data('id'), 'active'); });
            $(document).on('click', '.reject-btn', function() { if (confirm("Reject?")) processWorkflow($(this).data('id'), 'inactive'); });
            
            $(document).on('click', '.delete-btn', function() {
                if (confirm("Delete?")) $.ajax({
                    url: `/api/v1/interested-customers/${$(this).data('id')}`, type: 'DELETE',
                    success: function() { loadAllData(); }
                });
            });

            // Excel Import logic (Optional for Interested leads, but kept it intact)
            $('#importExcel').change(function(e) {
                let file = e.target.files[0];
                if (!file) return;
                let reader = new FileReader();
                reader.onload = function(event) {
                    let data = new Uint8Array(event.target.result);
                    let workbook = XLSX.read(data, { type: 'array' });
                    let firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    let rawData = XLSX.utils.sheet_to_json(firstSheet, { header: 1, range: 2 });
                    let allLeads = [];
                    rawData.forEach(row => {
                        if (!row[7]) return;
                        allLeads.push({
                            "cust_name": row[1] ? String(row[1]) : "Unknown",
                            "email": row[2] && row[2] !== "-" ? row[2] : null,
                            "budget": row[3] && row[3] !== "-" ? row[3] : null,
                            "assigned_telecaller": row[4] ?? null,
                            "reference": row[5] ?? null,
                            "refer_by": row[6] ?? null,
                            "mobile": String(row[7]),
                            "alternate_no": row[8] && row[8] !== "-" ? row[8] : null,
                            "address": row[9] && row[9] !== "-" ? row[9] : null,
                            "date": (row[10] && row[10] !== "-") ? row[10] : null,
                            "interested_for": row[11] ?? null,
                            "required_for": row[12] ?? null,
                            "status": row[13] ?? "Interested", // Defaulting imported leads to Interested
                            "followup_date": (row[14] && row[14] !== "-") ? row[14] : null,
                            "followup_month": row[15] ?? null,
                            "remark": row[16] ?? null
                        });
                    });
                    const chunkSize = 500;
                    let totalChunks = Math.ceil(allLeads.length / chunkSize);
                    async function uploadChunks() {
                        for (let i = 0; i < totalChunks; i++) {
                            let chunk = allLeads.slice(i * chunkSize, (i + 1) * chunkSize);
                            try {
                                await $.ajax({
                                    url: '/api/v1/interested-customers/import', type: 'POST',
                                    data: JSON.stringify({ leads: chunk }), contentType: 'application/json'
                                });
                            } catch (err) { alert("Import Error at chunk " + (i + 1)); return; }
                        }
                        alert("All leads imported successfully!"); loadAllData(); $('#importExcel').val('');
                    }
                    uploadChunks();
                };
                reader.readAsArrayBuffer(file);
            });

            // 🔥 FIX: Tab switch hone par table width thik karne ke liye
$('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
});
        });



        // VIEW BUTTON LOGIC
            $(document).on('click', '.view-btn', function() {
                let id = $(this).data('id');
                $.get({
                    url: `/api/v1/interested-customers/${id}`,
                    success: function(res) {
                        let d = res.data;
                        $('#v_company').text(d.company ? d.company.company_name : 'N/A');
                        $('#v_branch').text(d.branch ? d.branch.branch_name : 'Head Office');
                        $('#v_name').text(d.cust_name || 'N/A');
                        $('#v_mob').text(d.mobile || 'N/A');
                        $('#v_alt').text(d.alternate_no || 'N/A');
                        $('#v_email').text(d.email || 'N/A');
                        $('#v_tele').text(d.assigned_telecaller || 'N/A');
                        $('#v_ref').text(d.reference || 'N/A');
                        $('#v_refby').text(d.refer_by || 'N/A');
                        $('#v_int').text(d.interested_for || 'N/A');
                        $('#v_req').text(d.required_for || 'N/A');
                        $('#v_budget').text(d.budget || 'N/A');
                        $('#v_date').text(d.date || 'N/A');
                        $('#v_fdate').text(d.followup_date || 'N/A');
                        $('#v_status').text(d.status || 'N/A'); 
                        $('#v_addr').text(d.address || 'N/A');
                        $('#v_rem').text(d.remark || 'N/A');
                        $('#viewModal').modal('show');
                    },
                    error: function(xhr) {
                        alert("Failed to fetch lead details.");
                    }
                });
            });
    </script>
@endpush