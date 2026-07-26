@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    
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

        .action-btns button {
            margin-right: 3px;
            margin-bottom: 3px;
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">General Leads Management</h4>
            <div class="d-flex gap-2">
                <div class="d-flex gap-2 align-items-center">
    <div class="import-section d-flex flex-column gap-2">
        <div class="d-flex align-items-center gap-3">
            <button id="import-btn" class="btn btn-primary shadow-sm" onclick="$('#importExcel').click()">
                <i class="fas fa-file-import me-1"></i> Select Excel Files
            </button>
            <input type="file" id="importExcel" class="d-none" accept=".xlsx, .xls, .csv" multiple>
            
            <button id="start-upload-btn" class="btn btn-success shadow-sm" style="display: none;">
                <i class="fas fa-cloud-upload-alt me-1"></i> Start Import
            </button>
        </div>

        <div id="progress-container" class="bg-light border rounded p-2 text-dark" style="display: none; font-size: 13px;">
            <div id="import-analysis" class="mb-1 fw-bold text-primary"></div>
            <div class="progress shadow-sm" style="height: 15px; width: 300px; border-radius: 10px;">
                <div id="import-progress" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;"></div>
            </div>
            <span id="import-percentage" class="fw-bold mt-1 d-block text-muted">0% Complete</span>
        </div>
    </div>
</div>
                <button type="button" class="btn text-white px-3 py-2 shadow-sm secured-item"
                    data-permission="general_leads_add_direct" style="background-color: var(--brand-primary);"
                    onclick="openModal('add_direct')"><i class="fas fa-plus me-1"></i> Add Lead</button>

                <button type="button" class="btn text-dark px-3 py-2 shadow-sm secured-item"
                    data-permission="general_leads_add_request" style="background-color: #facc15;"
                    onclick="openModal('add_request')"><i class="fas fa-paper-plane me-1"></i> Request Lead</button>
            </div>
        </div>

        <div class="card mb-3 admin-report-section" style="display: none; border-left: 4px solid #0d6efd;">
            <div class="card-header bg-light fw-bold">
                <i class="fas fa-chart-pie me-2"></i> Employee Performance Report
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="d-flex justify-content-between mb-1">
                            <label class="form-label mb-0">Company</label>
                            <div>
                                <span class="badge bg-success" style="cursor:pointer;"
                                    onclick="multiSelectAll('company')">Select All</span>
                                <span class="badge bg-danger" style="cursor:pointer;"
                                    onclick="multiClearAll('company')">Clear</span>
                            </div>
                        </div>
                        <input list="dl_company" class="form-control form-control-sm multi-inp" data-type="company"
                            placeholder="Type to select...">
                        <datalist id="dl_company"></datalist>
                        <div id="chips_company" class="d-flex flex-wrap gap-1 mt-2"></div>
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex justify-content-between mb-1">
                            <label class="form-label mb-0">Branch</label>
                            <div>
                                <span class="badge bg-success" style="cursor:pointer;"
                                    onclick="multiSelectAll('branch')">Select All</span>
                                <span class="badge bg-danger" style="cursor:pointer;"
                                    onclick="multiClearAll('branch')">Clear</span>
                            </div>
                        </div>
                        <input list="dl_branch" class="form-control form-control-sm multi-inp" data-type="branch"
                            placeholder="Type to select...">
                        <datalist id="dl_branch"></datalist>
                        <div id="chips_branch" class="d-flex flex-wrap gap-1 mt-2"></div>
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex justify-content-between mb-1">
                            <label class="form-label mb-0">Department</label>
                            <div>
                                <span class="badge bg-success" style="cursor:pointer;"
                                    onclick="multiSelectAll('dept')">Select All</span>
                                <span class="badge bg-danger" style="cursor:pointer;"
                                    onclick="multiClearAll('dept')">Clear</span>
                            </div>
                        </div>
                        <input list="dl_dept" class="form-control form-control-sm multi-inp" data-type="dept"
                            placeholder="Type to select...">
                        <datalist id="dl_dept"></datalist>
                        <div id="chips_dept" class="d-flex flex-wrap gap-1 mt-2"></div>
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex justify-content-between mb-1">
                            <label class="form-label mb-0">Employee</label>
                            <div>
                                <span class="badge bg-success" style="cursor:pointer;"
                                    onclick="multiSelectAll('emp')">Select All</span>
                                <span class="badge bg-danger" style="cursor:pointer;"
                                    onclick="multiClearAll('emp')">Clear</span>
                            </div>
                        </div>
                        <input list="dl_emp" class="form-control form-control-sm multi-inp" data-type="emp"
                            placeholder="Type to select...">
                        <datalist id="dl_emp"></datalist>
                        <div id="chips_emp" class="d-flex flex-wrap gap-1 mt-2"></div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" id="rep_from_date" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" id="rep_to_date" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary btn-sm w-100" onclick="generateReport()"><i
                                class="fas fa-search"></i> View Report</button>
                    </div>
                </div>

                <div id="reportResultArea" class="mt-4 d-none">
                    <h6 class="fw-bold border-bottom pb-2">Report Results</h6>
                    <div class="row" id="reportCardsContainer">
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4 admin-only-section" style="display:none;">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold"><i class="fas fa-headset me-2 text-primary"></i> Bulk Assign Telecaller (General)
                </h6>
            </div>
            <div class="card-body">
                <form id="assignForm" class="row g-3 align-items-end">
                    <div class="col-md-4"><label class="form-label">Telecaller ID / Name</label><input type="text"
                            id="a_telecaller" class="form-control" list="staffDataList" required autocomplete="off">
                    </div>
                    <div class="col-md-3"><label class="form-label">Data From (Row)</label><input type="number"
                            id="a_from" class="form-control" min="1" required></div>
                    <div class="col-md-3"><label class="form-label">Data To (Row)</label><input type="number"
                            id="a_to" class="form-control" min="1" required></div>
                    <div class="col-md-2"><button type="button" class="btn btn-primary" onclick="saveAssignTelecaller(false)">Assign Now</button></div>
                </form>
            </div>
        </div>

        <datalist id="staffDataList"></datalist>
        <datalist id="referByDataList"></datalist>

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
                            <div class="col-md-3"><label class="form-label">Refer By</label><input type="text"
                                    name="refer_by" id="r_refer" class="form-control" list="referByDataList"
                                    autocomplete="off" placeholder="Search ID or Name"></div>
                            <div class="col-md-2"><label class="form-label">Budget From</label><input type="number"
                                    id="r_bfrom" class="form-control" placeholder="Min"></div>
                            <div class="col-md-2"><label class="form-label">Budget To</label><input type="number"
                                    id="r_bto" class="form-control" placeholder="Max"></div>

                            <div class="col-12 mt-3 d-flex gap-2">
                                <button type="button" class="btn btn-success px-4 secured-item"
                                    data-permission="general_leads_export" onclick="downloadFilteredExcel()"><i
                                        class="fas fa-file-excel me-1"></i> Download Filtered Excel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-block d-lg-none mb-3">
            <ul class="nav nav-pills nav-pills-custom d-flex w-100 mb-3" id="mobileTabs">
                <li class="nav-item flex-fill text-center"><button class="nav-link active w-100" data-bs-toggle="tab"
                        data-bs-target="#activeLeadsList">Active Leads</button></li>
                <li class="nav-item flex-fill text-center shadow-tab"><button class="nav-link w-100 text-warning"
                        data-bs-toggle="tab" data-bs-target="#pendingApprovalList">Pending Requests</button></li>
            </ul>
        </div>

        <ul class="nav nav-tabs mb-3 d-none d-lg-flex" id="desktopTabs">
            <li class="nav-item"><button class="nav-link active fw-bold text-primary" data-bs-toggle="tab"
                    data-bs-target="#activeLeadsList">Active General Leads</button></li>
            <li class="nav-item"><button class="nav-link fw-bold text-warning" data-bs-toggle="tab"
                    data-bs-target="#pendingApprovalList">Pending Approval Requests</button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="activeLeadsList">
                <div class="d-block d-lg-none">
                    <input type="text" id="mobileSearchActive" class="form-control shadow-sm mb-3"
                        placeholder="Search Active Leads...">
                    <div id="mContainer"></div>
                    <button id="btnLoadMore" class="btn btn-outline-secondary w-100 fw-bold d-none mb-4 py-2"
                        style="border-radius: 8px;">Load More...</button>
                </div>

                <div class="card border-0 shadow-sm mb-4 d-none d-lg-block">
                    <div class="card-body p-3 table-responsive">
                        <table id="dataTableMain" class="table table-hover table-custom w-100">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"><input type="checkbox" class="form-check-input border-dark"
                                            id="checkAllDesktop"></th>
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
                <div class="d-block d-lg-none">
                    <input type="text" id="mobileSearchPending" class="form-control shadow-sm mb-3"
                        placeholder="Search Pending Requests...">
                    <div id="mPendingContainer"></div>
                </div>
                <div class="card border-0 shadow-sm mb-4 d-none d-lg-block">
                    <div class="card-body p-3 table-responsive">
                        <table id="pendingTable" class="table table-hover table-custom w-100">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"><input type="checkbox" class="form-check-input border-dark"
                                            id="checkAllDesktop"></th>
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

                        <div class="col-md-3"><label class="form-label">Company *</label><select name="company_id"
                                id="f_company" class="form-select" required></select></div>
                        <div class="col-md-3"><label class="form-label">Branch (Optional for HO)</label><select
                                name="branch_id" id="f_branch" class="form-select">
                                <option value="">-- Head Office --</option>
                            </select></div>
                        <div class="col-md-3"><label class="form-label">Customer Name *</label><input type="text"
                                name="cust_name" id="f_name" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Assigned Tele-Caller</label><input type="text"
                                name="assigned_telecaller" id="f_tele" class="form-control" list="staffDataList"
                                autocomplete="off" placeholder="Search ID or Name"></div>

                        <div class="col-md-3"><label class="form-label">Mobile Number *</label><input type="text"
                                name="mobile" id="f_mob" class="form-control" maxlength="10" required>
                            <small id="mobileErrorMsg" class="text-danger fw-bold d-none"><i
                                    class="fas fa-exclamation-triangle"></i> Record already exists!</small>

                        </div>
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
                                name="refer_by" id="f_refby" class="form-control" list="referByDataList"
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

                        <div class="col-md-3"><label class="form-label">Status</label>
                            <select name="status" id="f_status" class="form-select" required>
                                <option value="">-- Select --</option>
                                <option value="General" selected>General</option>
                                <option value="Connected">Connected</option>
                            </select>

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

    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i
                            class="fas fa-eye me-2 text-primary"></i>View Lead Details</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <p class="text-muted mb-1 small fw-bold">Company</p>
                            <h6 id="v_company" class="fw-bold">-</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1 small fw-bold">Branch</p>
                            <h6 id="v_branch" class="fw-bold">-</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1 small fw-bold">Customer Name</p>
                            <h6 id="v_name" class="fw-bold text-primary">-</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1 small fw-bold">Mobile Number</p>
                            <h6 id="v_mob" class="fw-bold">-</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1 small fw-bold">Alternate Number</p>
                            <h6 id="v_alt" class="fw-bold">-</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1 small fw-bold">Email ID</p>
                            <h6 id="v_email" class="fw-bold">-</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1 small fw-bold">Assigned Tele-Caller</p>
                            <h6 id="v_tele" class="fw-bold">-</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1 small fw-bold">Reference Type</p>
                            <h6 id="v_ref" class="fw-bold">-</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1 small fw-bold">Refer By</p>
                            <h6 id="v_refby" class="fw-bold">-</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1 small fw-bold">Interested For</p>
                            <h6 id="v_int" class="fw-bold">-</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1 small fw-bold">Required For (Phase)</p>
                            <h6 id="v_req" class="fw-bold">-</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1 small fw-bold">Budget</p>
                            <h6 id="v_budget" class="fw-bold">-</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1 small fw-bold">Date of Calling</p>
                            <h6 id="v_date" class="fw-bold">-</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1 small fw-bold">Follow-up Date</p>
                            <h6 id="v_fdate" class="fw-bold">-</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1 small fw-bold">Status</p>
                            <h6 id="v_status" class="fw-bold"><span class="badge bg-secondary">General</span></h6>
                        </div>
                        <div class="col-md-12 border-top pt-3 mt-3">
                            <p class="text-muted mb-1 small fw-bold">Address</p>
                            <h6 id="v_addr" class="fw-bold">-</h6>
                        </div>
                        <div class="col-md-12">
                            <p class="text-muted mb-1 small fw-bold">Remarks</p>
                            <h6 id="v_rem" class="fw-bold">-</h6>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="floatingBulkBar"
        class="d-none position-fixed bottom-0 start-50 translate-middle-x mb-0 mb-md-4 p-2 p-md-3 bg-dark shadow-lg text-white d-flex align-items-center justify-content-between w-100"
        style="z-index: 1050; max-width: 600px; border-top-left-radius: 12px; border-top-right-radius: 12px;">
        <span id="selectedCountText" class="fw-bold fs-6 ms-2">0 Selected</span>
        <div class="me-2">
            <button class="btn btn-sm btn-outline-light me-2" onclick="selectAllMobile()"><i
                    class="fas fa-check-double"></i> Select All</button>
            <button class="btn btn-sm btn-danger secured-item" data-permission="general_leads_delete"
                onclick="deleteBulkRecords()">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
    </div>

    <!-- 🔥 NAYA: Floating Circle Counter (Bottom-Left) -->
    <div id="floatingCounter"
        class="position-fixed d-flex justify-content-center align-items-center rounded-circle shadow-lg bg-primary text-white"
        style="bottom: 20px; left: 20px; width: 65px; height: 65px; z-index: 1050; flex-direction: column; cursor: help;"
        title="Today's Entries">
        <i class="fas fa-chart-line fs-5 mb-1"></i>
        <span id="countValue" class="fw-bold lh-1" style="font-size: 14px;">0</span>
    </div>
@endsection
@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

   <script>
        $(document).ready(function() {
            // 🔥 1. sysContext ERROR FIX (Synchronous Load) 🔥
            let sysContext = null;
            $.ajax({
                url: '/api/v1/context',
                type: 'GET',
                async: false, 
                success: function(res) {
                    sysContext = res;
                }
            });

            let authRole = sysContext ? (sysContext.role_level || 'employee').toLowerCase() : 'employee',
                authCompanyId = sysContext ? sysContext.company_id : '',
                authBranchId = sysContext ? sysContext.branch_id : '',
                authProfileId = sysContext ? sysContext.profile_id : '';
                
            let isAdmin = sysContext ? (sysContext.is_god || ['developer', 'ceo', 'admin'].includes(authRole)) : false,
                isDirector = sysContext ? sysContext.is_director : false;

            let listData = [],
                pendingData = [];
            let mIndex = 0;
            const CHUNK = 25;

            let dataTableMain;
            let pendingTable = $('#pendingTable').DataTable({
                pageLength: 25, 
                dom: '<"row"f>rt<"row"ip>'
            });

            // Export Excel Function
            window.downloadFilteredExcel = function() {
                let btn = $('#filterForm button.btn-success');
                let originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Downloading...');

                let params = new URLSearchParams({
                    type: 'general', // 🔥 Backend ko bataye ki General data chahiye
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
                        if (res.data && res.data.length > 0) {
                            let ws = XLSX.utils.json_to_sheet(res.data);
                            let wb = XLSX.utils.book_new();
                            XLSX.utils.book_append_sheet(wb, ws, "General Leads");
                            XLSX.writeFile(wb, "General_Leads_Filtered.xlsx");
                        } else {
                            alert("No matching data found.");
                        }
                    },
                    error: function() {
                        btn.html(originalText);
                        alert("Export failed.");
                    }
                });
            };

            function loadAllData() {
                // 🔥 2. VIEW PERMISSION CHECK 🔥
                let p = (sysContext && sysContext.permissions) ? sysContext.permissions : (window.userPerms || []);
                let hasView = isAdmin || p.includes('general_leads_view');

                if (!hasView) {
                    // Agar permission nahi hai to UI hide karo
                    $('.card.d-none.d-lg-block').hide(); 
                    $('#mobileTabs, #desktopTabs').hide();
                    $('#mobileSearchActive, #mobileSearchPending').hide();
                    $('#mContainer, #mPendingContainer').hide();
                    $('#btnLoadMore').hide();
                    $('#floatingBulkBar').remove(); 
                    
                    if ($('#noViewWarning').length === 0) {
                        $('<div id="noViewWarning" class="alert alert-warning text-center mt-3 fw-bold shadow-sm" style="background-color: #fff3cd; color: #856404; border-color: #ffeeba;"><i class="fas fa-lock me-2"></i> You only have permission to Add/Request. Data table view is restricted.</div>').insertAfter('.d-flex.justify-content-between.align-items-center.mb-4');
                    }
                } 
                else {
                    // 🔥 VIEW PERMISSION HAI TOH DATATABLE LOAD KAREN 🔥
                    if (!$.fn.DataTable.isDataTable('#dataTableMain')) {
                        dataTableMain = $('#dataTableMain').DataTable({
                            pageLength: 25,
                            lengthMenu: [ [10, 25, 50, 100, 500], [10, 25, 50, 100, "All"] ],
                            processing: true,
                            serverSide: true,
                            ajax: {
                                url: '/api/v1/interested-customers',
                                type: 'GET',
                                data: function(d) {
                                    d.type = 'general'; // 🔥 NAYA: API ko specifically batayega ki General Data chahiye
                                },
                                headers: { "Accept": "application/json" }
                            },
                            order: [ [0, 'desc'] ],
                            dom: isAdmin ? '<"row"<"col-md-6"B><"col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>' : '<"row"f>rt<"row"ip>',
                            buttons: isAdmin ? [{
                                text: '<i class="fas fa-file-excel me-1"></i> Download All (Searched) Excel',
                                className: 'btn btn-success btn-sm shadow-sm secured-item admin-only-section',
                                action: function(e, dt, node, config) {
                                    let searchVal = dt.search() || '';
                                    let btn = $(node);
                                    let originalText = btn.html();
                                    btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Exporting...');

                                    $.ajax({
                                        url: '/api/v1/general-leads/export?type=general&search=' + encodeURIComponent(searchVal),
                                        type: 'GET',
                                        success: function(res) {
                                            btn.html(originalText);
                                            if (res.data && res.data.length > 0) {
                                                let ws = XLSX.utils.json_to_sheet(res.data);
                                                let wb = XLSX.utils.book_new();
                                                XLSX.utils.book_append_sheet(wb, ws, "General Leads");
                                                XLSX.writeFile(wb, "General_Leads_Export.xlsx");
                                            } else {
                                                alert("No data found to export.");
                                            }
                                        },
                                        error: function() {
                                            btn.html(originalText);
                                            alert("Export failed.");
                                        }
                                    });
                                }
                            }] : []
                        });
                    } else {
                        dataTableMain.ajax.reload(null, false);
                    }
                } // End of hasView condition

                // Background Data Load (Counters, Datalists, Mobile Cards)
                $.ajax({
                    url: '/api/v1/interested-customers?type=general',
                    type: 'GET',
                    headers: { "Accept": "application/json" },
                    success: function(res) {
                        authRole = (res.auth_role || 'employee').toLowerCase();
                        authCompanyId = res.auth_company || '';
                        authBranchId = res.auth_branch || '';
                        authProfileId = res.auth_profile_id || '';

                        if (res.today_count !== undefined) {
                            $('#countValue').text(res.today_count);
                        }

                        isAdmin = ['developer', 'ceo', 'admin'].includes(authRole);
                        isDirector = (authRole === 'director');

                        if (isAdmin) $('.admin-only-section').show();
                        if (isAdmin || isDirector) {
                            $('.admin-report-section').show();
                            if (typeof loadReportInitial === 'function') loadReportInitial();
                        }

                        // LIVE Auto-Refresh Logic
                        if ((isAdmin || isDirector) && !window.liveUpdateStarted) {
                            window.liveUpdateStarted = true;
                            setInterval(function() {
                                $.ajax({
                                    url: '/api/v1/interested-customers?type=general',
                                    type: 'GET',
                                    headers: { "Accept": "application/json" },
                                    success: function(liveRes) {
                                        if (liveRes.today_count !== undefined) $('#countValue').text(liveRes.today_count);
                                    }
                                });
                                if ($.fn.DataTable.isDataTable('#dataTableMain')) {
                                    $('#dataTableMain').DataTable().ajax.reload(null, false);
                                }
                            }, 10000); 
                        }

                        listData = res.general || [];
                        pendingData = (res.pending_requests || []).filter(d => d.status.toLowerCase() === 'general');

                        let activeStaff = (res.staff_list || []).filter(s => {
                            let status = s.emp_status ? s.emp_status.toString().toLowerCase() : '';
                            return status === 'active';
                        });

                        let dlHtml = '';
                        activeStaff.forEach(s => {
                            dlHtml += `<option value="${s.staff_id}">${s.name} (${s.role})</option>`;
                        });

                        // 🔥 3. CUSTOM REFER BY (Unique List from Controller) 🔥
                        if (res.custom_refers && res.custom_refers.length > 0) {
                            res.custom_refers.forEach(ref => {
                                dlHtml += `<option value="${ref}">${ref} (Custom)</option>`;
                            });
                        }

                        $('#staffDataList').html(dlHtml);
                        let referByHtml = '<option value="Amitabh Sir">Amitabh Sir</option>' + dlHtml;
                        $('#referByDataList').html(referByHtml);

                        if (hasView) {
                            renderMobileAndPending();
                        }

                        if ($('#f_company option').length <= 1) loadCompanies();
                        else applyCompanyLocks();

                        if (typeof window.applyPermissions === 'function') window.applyPermissions();
                    }
                });
            }
            loadAllData();

            function renderMobileAndPending() {
                pendingTable.clear();
                pendingData.forEach(d => {
                    let compName = d.company ? d.company.company_name : '-';
                    let bName = d.branch ? d.branch.branch_name : 'HO';
                    let approvalActions =
                        `<div class="action-btns"><button class="btn btn-sm btn-success approve-btn secured-item" data-permission="general_leads_appr" data-id="${d.id}"><i class="fas fa-check"></i></button><button class="btn btn-sm btn-danger reject-btn secured-item" data-permission="general_leads_rej" data-id="${d.id}"><i class="fas fa-times"></i></button><span class="badge bg-warning text-dark pending-wait-badge" style="display:none;"><i class="fas fa-clock"></i> Wait..</span></div>`;
                    pendingTable.row.add([
                        `<b>${compName}</b><br><small class="text-muted">${bName}</small>`, d
                        .cust_name, d.mobile,
                        `<span class="badge bg-warning text-dark">Pending Review</span>`, d
                        .assigned_telecaller || 'Staff', approvalActions
                    ]);
                });
                pendingTable.draw();

                mIndex = 0;
                $('#mContainer, #mPendingContainer').empty();
                renderMobile();
                renderMobilePending();
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
                        if (!isAdmin && !isDirector && autoSelectBranchId) $('#f_branch').val(
                            autoSelectBranchId).attr('style',
                            'pointer-events: none; background-color: #f1f1f1;');
                        else $('#f_branch').removeAttr('style');
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

                if (window.formMode === 'add') {
                    if (!isAdmin && !isDirector) $('#f_tele').val(authProfileId).attr('readonly', true).css(
                        'background-color', '#f1f1f1');
                    else $('#f_tele').val('').removeAttr('readonly').css('background-color', '');
                }

                // 🔥 NAYA CODE: Modal khulte hi check karega
                if (window.formMode === 'edit') {
                    // Edit Mode me sirf Connected dikhega
                    $('#f_status').html(
                        '<option value="General">General</option><option value="Connected">Connected</option><option value="Not Reachable">Not Reachable</option>'
                        );
                } else {
                    // Add ya Request Mode me default 'General' rahega
                    $('#f_status').html('<option value="General" selected>General</option>');
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
                                    } else if (isDirector) $('#f_company').attr('style',
                                        'pointer-events: none; background-color: #f1f1f1;'
                                    );
                                }
                            });
                            $('#f_name').val(d.cust_name);
                            $('#f_tele').val(d.assigned_telecaller);
                            if (!isAdmin && !isDirector) $('#f_tele').attr('readonly', true).css(
                                'background-color', '#f1f1f1');
                            else $('#f_tele').removeAttr('readonly').css('background-color', '');
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
                let type = window.formMode === 'add' ? 'POST' : 'PUT';

                $.ajax({
                    url: url,
                    type: type,
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if (res.is_duplicate) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Duplicate Entry!',
                                text: res.message,
                                confirmButtonColor: '#3085d6'
                            });
                            return;
                        }

                        // 🔥 NAYA CODE: Bina refresh kiye counter ko instantly 1 badha dena (Agar naya add hua hai)
                        if (window.formMode === 'add' || window.formMode === 'add_direct' ||
                            window.formMode === 'add_request') {
                            let currentCount = parseInt($('#countValue').text()) || 0;
                            $('#countValue').text(currentCount + 1);
                        }
                        alert(res.message);

                        // Agar Edit mode hai, toh modal close kardo
                        if (window.formMode === 'edit') {
                            $('#custModal').modal('hide');
                        }
                        // Agar Add ya Request mode hai, toh form reset karo aur modal open rakho
                        else {
                            $('#custForm')[0].reset();
                            $('#edit_id').val('');

                            // Hidden values set karein
                            $('#entry_type').val(window.formMode === 'add_request' ? 'request' :
                                'direct');

                            applyCompanyLocks();

                            if (!isAdmin && !isDirector) {
                                $('#f_tele').val(authProfileId).attr('readonly', true).css(
                                    'background-color', '#f1f1f1');
                            } else {
                                $('#f_tele').val('').removeAttr('readonly').css(
                                    'background-color', '');
                            }

                            // General status ensure karein reset ke baad
                            if ($('#f_status').is('[readonly]')) {
                                $('#f_status').val('General');
                            }
                        }

                        loadAllData(); // Background me table update hogi
                    },
                    error: function(xhr) {
                        let errorMsg = xhr.responseJSON ? xhr.responseJSON.message : "Failed";
                        alert("Error: " + errorMsg);
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
                if (confirm("Delete?")) $.ajax({
                    url: `/api/v1/interested-customers/${$(this).data('id')}`,
                    type: 'DELETE',
                    success: function() {
                        loadAllData();
                    }
                });
            });

            // Mobile Cards Logic
            function renderMobile() {
                let chunk = listData.slice(mIndex, mIndex + CHUNK);
                chunk.forEach(d => {
                    // 🔥 YAHAN d-flex ke andar checkbox daala hai
                    $('#mContainer').append(
                        `<div class="mobile-item m-card-item">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div>
                        <input type="checkbox" class="form-check-input row-checkbox me-2 border-dark" value="${d.id}" style="transform: scale(1.2);">
                        <h6 class="fw-bold d-inline align-middle">${d.cust_name}</h6>
                    </div>
                    <span class="badge bg-secondary">${d.status}</span>
                </div>
                <div class="small text-muted mb-2">${d.mobile} | Tele: ${d.assigned_telecaller||'N/A'}</div>
                <div class="d-flex gap-2 border-top pt-2 mt-2">
                    <button class="btn btn-sm btn-light text-primary flex-fill edit-btn secured-item" data-permission="general_leads_edit" data-id="${d.id}"><i class="fas fa-edit"></i> Edit</button>
                    <button class="btn btn-sm btn-light text-danger flex-fill delete-btn secured-item" data-permission="general_leads_delete" data-id="${d.id}"><i class="fas fa-trash"></i> Delete</button>
                </div>
            </div>`
                    );
                });
                mIndex += CHUNK;
                if (mIndex >= listData.length) $('#btnLoadMore').removeClass('d-block').addClass('d-none');
                else $('#btnLoadMore').removeClass('d-none').addClass('d-block');
            }

            function renderMobilePending() {
                pendingData.forEach(d => {
                    let actions =
                        `<span class="badge bg-warning text-dark pending-wait-badge"><i class="fas fa-clock"></i> Wait..</span>`;
                    if (isAdmin || isDirector) actions =
                        `<button class="btn btn-sm btn-success flex-fill approve-btn secured-item" data-permission="general_leads_appr" data-id="${d.id}"><i class="fas fa-check"></i> Approve</button><button class="btn btn-sm btn-danger flex-fill reject-btn secured-item" data-permission="general_leads_rej" data-id="${d.id}"><i class="fas fa-times"></i> Reject</button>`;
                    $('#mPendingContainer').append(
                        `<div class="mobile-item m-card-item-pending"><div class="d-flex justify-content-between"><div><h6 class="fw-bold">${d.cust_name}</h6></div><span class="badge bg-warning text-dark">Pending</span></div><div class="small text-muted mb-2">${d.mobile} | Tele: ${d.assigned_telecaller||'N/A'}</div><div class="d-flex gap-2 border-top pt-2 mt-2">${actions}</div></div>`
                    );
                });
            }

            $('#btnLoadMore').click(renderMobile);
            $('#mobileSearchActive').on('keyup', function() {
                let v = $(this).val().toLowerCase();
                $('#mContainer .m-card-item').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1)
                });
            });
            $('#mobileSearchPending').on('keyup', function() {
                let v = $(this).val().toLowerCase();
                $('#mPendingContainer .m-card-item-pending').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1)
                });
            });

 // Smart Import: PapaParse + Universal Data Scanner
let validUniqueLeads = []; 
let totalExcelRows = 0;
let internalDuplicates = 0;

$('#importExcel').change(async function(e) {
    let files = e.target.files;
    if (!files || files.length === 0) return;

    $('#import-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Scanning File...');
    $('#progress-container').show();
    $('#import-analysis').text('Data scan ho raha hai, kripya pratiksha karein...');
    $('#import-progress').css('width', '0%');
    $('#start-upload-btn').hide();

    let allLeadsCombined = [];
    totalExcelRows = 0;
    internalDuplicates = 0;

    // 🔥 UNIVERSAL SCANNER: Headers par depend nahi karta, direct data dhundhta hai
    const processRawData = (rawDataObjects) => {
        let fileLeads = [];
        if (!rawDataObjects || rawDataObjects.length === 0) return fileLeads;

        for(let i = 0; i < rawDataObjects.length; i++) {
            let row = rawDataObjects[i];
            // Dono format (Array ya Object) ko ek list (values) me badalna
            let isArray = Array.isArray(row);
            let values = isArray ? row : Object.values(row);
            let keys = isArray ? [] : Object.keys(row);
            
            let rawMob = null;
            let cName = null;
            let eml = null;
            let addr = null;

            // METHOD 1: Pura object scan karo
            if (!isArray) {
                for (let j = 0; j < keys.length; j++) {
                    let origKey = keys[j];
                    // Key me se saare special characters hata do
                    let cleanKey = String(origKey).toLowerCase().replace(/[^a-z0-9]/g, '');
                    let val = row[origKey];
                    
                    if (!val) continue;

                    if (cleanKey.includes('mob') || cleanKey.includes('phone') || cleanKey.includes('contact')) {
                        if (!rawMob) rawMob = val;
                    }
                    else if (cleanKey === 'name' || cleanKey.includes('firstname') || cleanKey.includes('fullname')) {
                        if (!cName) cName = val;
                    }
                    else if (cleanKey === 'lastname' && cName) {
                        cName = cName + ' ' + val;
                    }
                    else if (cleanKey.includes('email')) {
                        if (!eml) eml = val;
                    }
                }
            }

            // METHOD 2 (MASTER FALLBACK): Agar mobile nahi mila, to puri row check karo kisi 10+ digit number ke liye
            if (!rawMob) {
                for(let val of values) {
                    if(!val) continue;
                    let strVal = String(val).trim();
                    let numOnly = strVal.replace(/\D/g, ''); // Sirf digits nikalna
                    
                    // Agar 10 se 15 digit ka number hai aur email nahi hai, to wo pakka mobile number hai
                    if (numOnly.length >= 10 && numOnly.length <= 15 && !strVal.includes('@')) {
                         rawMob = strVal;
                         break; 
                    }
                }
            }

            // Name Fallback: Pehla word jo number ya email na ho
            if (!cName) {
                for(let val of values) {
                     if(!val) continue;
                     let strVal = String(val).trim();
                     if (isNaN(strVal) && strVal.length > 2 && !strVal.includes('@') && !strVal.includes('http')) {
                         cName = strVal;
                         break;
                     }
                }
            }

            // Agar puri row chan marne ke baad bhi mobile nahi mila, to skip row
            if (!rawMob) continue; 
            
            // Number ko clean karna (+91 ya p: hatana)
            let mob = String(rawMob).replace(/p:/gi, '').replace(/\+/g, '').trim(); 
            let cleanMobOnly = mob.replace(/\D/g, ''); 
            if(cleanMobOnly.length < 10) continue; 

            cName = (cName ? String(cName).trim() : 'Unknown');

            fileLeads.push({ 
                "cust_name": cName, 
                "mobile": mob, 
                "email": eml ? String(eml) : null, 
                "address": addr ? String(addr) : null, 
                "status": "General", 
                "assigned_telecaller": "ABDPL-A/0001", 
                "reference": "Admin", 
                "refer_by": "Amitabh Sir" 
            });
        }
        return fileLeads;
    };

    const readFileAsLeads = (file) => {
        return new Promise((resolve, reject) => {
            let ext = file.name.split('.').pop().toLowerCase();

            if (ext === 'csv') {
                Papa.parse(file, {
                    header: true,
                    skipEmptyLines: true,
                    complete: function(results) {
                        resolve(processRawData(results.data));
                    },
                    error: function(err) {
                        reject(new Error("CSV Error: " + err.message));
                    }
                });
            } else {
                let reader = new FileReader();
                reader.onload = function(event) {
                    try {
                        let data = new Uint8Array(event.target.result);
                        let workbook = XLSX.read(data, { type: 'array' });
                        let firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                        let rawDataObjects = XLSX.utils.sheet_to_json(firstSheet, { defval: "" });
                        resolve(processRawData(rawDataObjects));
                    } catch (err) {
                        reject(new Error("Excel Error: " + err.message));
                    }
                };
                reader.readAsArrayBuffer(file);
            }
        });
    };

    setTimeout(async () => {
        try {
            for (let i = 0; i < files.length; i++) {
                let extractedLeads = await readFileAsLeads(files[i]);
                allLeadsCombined = allLeadsCombined.concat(extractedLeads);
            }

            totalExcelRows = allLeadsCombined.length;

            let seenMobiles = new Set();
            validUniqueLeads = [];

            // Duplicates filtering
            for (let i = 0; i < allLeadsCombined.length; i++) {
                let lead = allLeadsCombined[i];
                if (seenMobiles.has(lead.mobile)) {
                    internalDuplicates++; 
                } else {
                    seenMobiles.add(lead.mobile);
                    validUniqueLeads.push(lead);
                }
            }

            $('#import-btn').prop('disabled', false).html('<i class="fas fa-file-import me-1"></i> Select Again');
            
            if (validUniqueLeads.length === 0) {
                $('#import-analysis').html('<span class="text-danger fw-bold">Warning: File me koi valid 10-digit number nahi mila!</span>');
                $('#importExcel').val('');
                return;
            }

            $('#import-analysis').html(`<b>Total Scanned:</b> ${totalExcelRows} | <b>Internal Duplicates Removed:</b> ${internalDuplicates} | <b class="text-success fs-5">Ready to Import: ${validUniqueLeads.length}</b>`);
            $('#start-upload-btn').show();

        } catch (error) {
            Swal.fire('Error', error.message, 'error');
            $('#import-btn').prop('disabled', false).html('<i class="fas fa-file-import me-1"></i> Import Excel');
            $('#importExcel').val('');
        }
    }, 100);
});

// START UPLOAD BUTTON WALA PURANA CODE WAHI RAHEGA
$('#start-upload-btn').click(async function() {
    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Uploading...');
    $('#import-btn').prop('disabled', true);
    
    const chunkSize = 500; 
    const totalLeads = validUniqueLeads.length;
    let processedCount = 0;
    
    let totalInserted = 0;
    let totalDbDuplicates = 0;

    for (let i = 0; i < totalLeads; i += chunkSize) {
        let chunk = validUniqueLeads.slice(i, i + chunkSize);
        
        try {
            let response = await $.ajax({
                url: '/api/v1/interested-customers/import',
                type: 'POST',
                data: JSON.stringify({ leads: chunk }),
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });

            totalInserted += response.inserted;
            totalDbDuplicates += response.db_duplicates;

            processedCount += chunk.length;
            let percentage = Math.round((processedCount / totalLeads) * 100);

            $('#import-progress').css('width', percentage + '%');
            $('#import-percentage').text(`Uploading... ${percentage}%`);
        
        } catch (err) {
            console.error("Upload failed", err);
            Swal.fire('Error', `Upload ruk gaya hai. ${processedCount} rows tak import hua.`, 'error');
            break;
        }
    }

    $('#start-upload-btn').hide();
    $('#import-progress').removeClass('progress-bar-animated bg-success').addClass('bg-primary');
    $('#import-btn').prop('disabled', false);
    $('#importExcel').val('');

    Swal.fire({
        title: 'Import Process Completed!',
        html: `
            <div style="text-align: left; font-size: 14px;">
                <b>Total Rows in Excel:</b> ${totalExcelRows}<br><br>
                <b style="color:#d33;">Excel Internal Duplicates:</b> ${internalDuplicates} <i>(Skipped)</i><br>
                <b style="color:#f39c12;">Database Duplicates:</b> ${totalDbDuplicates} <i>(Skipped)</i><br><br>
                <b style="color:#28a745; font-size: 16px;">Successfully Inserted: ${totalInserted}</b>
            </div>
        `,
        icon: 'success'
    });

    if(typeof dataTableMain !== 'undefined') { dataTableMain.ajax.reload(null, false); } else { loadAllData(); }
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
                        $('#v_addr').text(d.address || 'N/A');
                        $('#v_rem').text(d.remark || 'N/A');
                        $('#viewModal').modal('show');
                    },
                    error: function(xhr) {
                        alert("Failed to fetch lead details.");
                    }
                });
            });

            // Tab switch hone par table width thik karne ke liye
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                $.fn.dataTable.tables({
                    visible: true,
                    api: true
                }).columns.adjust();
            });

            // ====== BULK DELETE LOGIC ======
            let selectedIds = [];

            // Checkbox tick/untick hone par array update aur floating bar toggle
            $(document).on('change', '.row-checkbox', function() {
                let val = String($(this).val());
                if ($(this).is(':checked')) {
                    if (!selectedIds.includes(val)) selectedIds.push(val);
                } else {
                    selectedIds = selectedIds.filter(id => id !== val);
                }
                toggleFloatingBar();
            });

            // Floating bar ko dikhana ya chhupana
            // Floating bar ko dikhana ya chhupana aur Counter ko upar shift karna
            function toggleFloatingBar() {
                if (selectedIds.length > 0) {
                    $('#selectedCountText').text(selectedIds.length + ' Selected');
                    $('#floatingBulkBar').removeClass('d-none');

                    // 🔥 NAYA: Jab delete bar aaye, toh counter ko smooth upar shift kar do
                    $('#floatingCounter').css({
                        'transition': 'bottom 0.3s ease',
                        'bottom': '85px'
                    });
                } else {
                    $('#floatingBulkBar').addClass('d-none');

                    // 🔥 NAYA: Jab delete bar hide ho, toh counter wapas niche aa jaye
                    $('#floatingCounter').css({
                        'transition': 'bottom 0.3s ease',
                        'bottom': '20px'
                    });
                }
            }



            // 🔥 UPDATED: Toggle Select All Button (Mobile aur Floating Bar ke liye)
            window.selectAllMobile = function() {
                // Agar pehle se saare records selected hain, toh UNSELECT All kar do
                if (selectedIds.length === listData.length && listData.length > 0) {
                    selectedIds = [];
                    $('.row-checkbox').prop('checked', false);
                    $('#checkAllDesktop').prop('checked', false);
                } else {
                    // Nahi toh database ke SAARE records (All Pages) select kar lo
                    selectedIds = listData.map(item => String(item.id));
                    $('.row-checkbox').prop('checked', true);
                    $('#checkAllDesktop').prop('checked', true);
                }
                toggleFloatingBar();
            };
            // Final API call for deletion
            window.deleteBulkRecords = function() {
                if (selectedIds.length === 0) return;

                Swal.fire({
                    title: 'Are you sure?',
                    text: `You are about to delete ${selectedIds.length} leads.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete them!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/api/v1/interested-customers/bulk-delete',
                            type: 'POST',
                            data: {
                                ids: selectedIds
                            },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Deleted!', res.message, 'success');
                                    selectedIds = [];
                                    toggleFloatingBar();

                                    // Tables ko refresh karo
                                    if ($.fn.DataTable.isDataTable('#dataTableMain')) {
                                        $('#dataTableMain').DataTable().ajax.reload(null,
                                            false);
                                    }
                                    loadAllData
                                (); // Ye listData update karke mobile cards bhi naye banayega
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('Error', 'Something went wrong!', 'error');
                            }
                        });
                    }
                });
            };
            // ==================================

            // 🔥 UPDATED: Desktop Checkbox Select All (Saare pages ke liye)
            $('#checkAllDesktop').change(function() {
                let isChecked = $(this).is(':checked');
                $('.row-checkbox').prop('checked', isChecked); // Current page ke boxes tick karo

                if (isChecked) {
                    // listData me database ke saare records hain, wahan se saari IDs nikal lo
                    selectedIds = listData.map(item => String(item.id));
                } else {
                    // Uncheck karne par array khali kar do
                    selectedIds = [];
                }
                toggleFloatingBar();
            });
            // Jab DataTable ka page change ho, to selection maintain rahe aur Buttons wapas aayein

            $('#dataTableMain').on('draw.dt', function() {
                // Agar saare selected hain, toh top wale checkbox ko bhi tick rakho
                let allSelected = (selectedIds.length === listData.length && listData.length > 0);
                $('#checkAllDesktop').prop('checked', allSelected);

                // Naye page ke visible checkboxes ko state ke hisaab se tick karo
                $('.row-checkbox').each(function() {
                    if (selectedIds.includes(String($(this).val()))) {
                        $(this).prop('checked', true);
                    }
                });

                // Action buttons wapas dikhane ka logic
                if (typeof window.applyPermissions === 'function') {
                    window.applyPermissions();
                }
                if (typeof isAdmin !== 'undefined' && isAdmin) {
                    $('.admin-only-section').show();
                }
            });

            // ====== MOBILE NUMBER DUPLICATE CHECK ======
            $('#f_mob').on('keyup', function() {
                let mobile = $(this).val();
                let excludeId = $('#edit_id')
            .val(); // Agar edit mode me hai toh same customer ko ignore karega

                // Jab number 10 digit ka ho jaye tabhi check karein (agar 10 digit mandatory hai toh)
                if (mobile.length >= 10) {
                    $.ajax({
                        url: '/api/v1/interested-customers/check-mobile',
                        type: 'POST',
                        data: {
                            mobile: mobile,
                            exclude_id: excludeId
                        },
                        success: function(res) {
                            if (res.exists) {
                                $('#mobileErrorMsg').removeClass(
                                'd-none'); // Red message dikhao
                                $('#saveBtn').prop('disabled', true); // Save button band kar do
                            } else {
                                $('#mobileErrorMsg').addClass('d-none'); // Message chhupao
                                $('#saveBtn').prop('disabled',
                                false); // Save button wapas chalu
                            }
                        }
                    });
                } else {
                    // Agar 10 digit se kam type kiya hai toh error chupao aur button on rakho
                    $('#mobileErrorMsg').addClass('d-none');
                    $('#saveBtn').prop('disabled', false);
                }
            });

            // ====== ADVANCED REPORTING MULTI-SELECT LOGIC ======

            // Global array selected data store karne ke liye
            window.reportData = {
                company: [],
                branch: [],
                dept: [],
                emp: []
            };

            // 1. Datalist se select karne par Input clear aur Chip ban jaye

            $('.multi-inp').on('change', function() {
                let val = $(this).val();
                let type = $(this).data('type');

                if (!val) return;

                let datalistId = $(this).attr('list');
                // 🔥 FIX: filter() ka use taaki exact option match ho
                let optionFound = $(`#${datalistId} option`).filter(function() {
                    return $(this).val() === val;
                });

                if (optionFound.length > 0) {
                    // 🔥 FIX: .attr() ka use kiya hai taaki empty ID bhi sahi se aaye
                    let id = optionFound.attr('data-id') || "";
                    let text = optionFound.attr('data-text');

                    if (!window.reportData[type].find(x => x.id === id)) {
                        window.reportData[type].push({
                            id: id,
                            text: text
                        });
                        renderChips(type);
                        triggerDependency(type);
                    }
                }
                $(this).val('');
            });

            // 2. Chips (Tags) ko screen par dikhana
            window.renderChips = function(type) {
                let container = $(`#chips_${type}`);
                container.empty();

                window.reportData[type].forEach(item => {
                    container.append(`
            <span class="badge bg-secondary d-flex align-items-center" style="font-size:12px;">
                ${item.text} 
                <i class="fas fa-times ms-2 text-danger" style="cursor:pointer;" onclick="removeChip('${type}', '${item.id}')"></i>
            </span>
        `);
                });
            };

            // 3. Chip delete (X par click)
            window.removeChip = function(type, id) {
                window.reportData[type] = window.reportData[type].filter(x => x.id !== id);
                renderChips(type);
                triggerDependency(type);
            };

            // 4. Select All Logic (FIXED)
            window.multiSelectAll = function(type) {
                let datalistId = $('.multi-inp[data-type="' + type + '"]').attr('list');
                window.reportData[type] = [];

                $(`#${datalistId} option`).each(function() {
                    window.reportData[type].push({
                        // 🔥 FIX: Yahan bhi .attr() lagaya
                        id: $(this).attr('data-id') || "",
                        text: $(this).attr('data-text')
                    });
                });
                renderChips(type);
                triggerDependency(type);
            };

            // 5. Clear All Logic
            window.multiClearAll = function(type) {
                window.reportData[type] = [];
                renderChips(type);
                triggerDependency(type);
            };

            // ====== DYNAMIC DEPENDENCY & REPORT LOADING ======

            // 1. Initial Company Load (Ye sirf Admin/CEO/Director ke liye chalega)
            window.loadReportInitial = function() {
                $.get('/api/v1/get-active-companies', function(res) {
                    let opts = '';
                    res.data.forEach(c => {
                        opts +=
                            `<option data-id="${c.id}" data-text="${c.company_name}" value="${c.company_name} | ID:${c.id}"></option>`;
                    });
                    $('#dl_company').html(opts);
                });
            };
            // 2. Dependency Trigger me Employee Loader (FIXED)
            window.triggerDependency = function(changedType) {
                if (changedType === 'company') {
                    let compIds = window.reportData.company.map(x => x.id);
                    if (compIds.length === 0) return;

                    $.post('/api/v1/get-branches-by-companies', {
                        company_ids: compIds
                    }, function(res) {
                        let opts =
                            '<option data-id="" data-text="Head Office" value="Head Office | ID:HO"></option>';
                        res.data.forEach(b => {
                            opts +=
                                `<option data-id="${b.id}" data-text="${b.branch_name}" value="${b.branch_name} | ID:${b.id}"></option>`;
                        });
                        $('#dl_branch').html(opts);
                        window.multiClearAll('branch');
                    });
                } else if (changedType === 'branch') {
                    let opts = `
            <option data-id="15" data-text="Customer Services" value="Customer Services | ID:15"></option>
            <option data-id="10" data-text="Data Operator" value="Data Operator | ID:10"></option>
            <option data-id="12" data-text="OFFICE OPERATOR" value="OFFICE OPERATOR | ID:12"></option>
        `;
                    $('#dl_dept').html(opts);
                    window.multiClearAll('dept');
                } else if (changedType === 'dept') {
                    let branchIds = window.reportData.branch.map(x => x.id);
                    let deptIds = window.reportData.dept.map(x => x.id);
                    if (deptIds.length === 0) return;

                    $.post('/api/v1/interested-customers/report-employees', {
                        branches: branchIds,
                        depts: deptIds
                    }, function(res) {
                        let opts = '';
                        // 🔥 FIX: adm_regist ka full_name aur member_id use ho raha hai
                        res.data.forEach(e => {
                            let name = e.full_name || 'Unknown';
                            opts +=
                                `<option data-id="${e.member_id}" data-text="${name} (${e.member_id})" value="${name} | ${e.member_id}"></option>`;
                        });
                        $('#dl_emp').html(opts);
                        window.multiClearAll('emp');
                    });
                }
            };

            // 3. View Report Generate Karna
            window.generateReport = function() {
                let emps = window.reportData.emp.map(x => x.id);
                let from = $('#rep_from_date').val();
                let to = $('#rep_to_date').val();

                if (emps.length === 0) {
                    Swal.fire('Warning', 'Please select at least one employee!', 'warning');
                    return;
                }

                let btn = $(event.currentTarget);
                let originalHtml = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i> Loading...');

                $.post('/api/v1/interested-customers/generate-report', {
                    employees: emps,
                    from_date: from,
                    to_date: to
                }, function(res) {
                    btn.html(originalHtml);
                    $('#reportResultArea').removeClass('d-none');
                    let container = $('#reportCardsContainer');
                    container.empty();

                    if (res.data.length === 0) {
                        container.html(
                            '<div class="col-12 text-center text-muted fw-bold py-3"><i class="fas fa-box-open fs-2 mb-2"></i><br>No entries found for the selected criteria.</div>'
                            );
                        return;
                    }

                    res.data.forEach(item => {
                        // Find employee name from our selected chips
                        let empName = item.assigned_telecaller;
                        let foundEmp = window.reportData.emp.find(x => x.id === item
                            .assigned_telecaller);
                        if (foundEmp) empName = foundEmp.text;

                        container.append(`
                <div class="col-md-3 mb-3">
                    <div class="card bg-white border border-primary shadow-sm h-100" style="border-radius:10px;">
                        <div class="card-body text-center p-3">
                            <h6 class="fw-bold text-dark mb-2 text-truncate" title="${empName}">${empName}</h6>
                            <span class="badge bg-primary fs-5 px-3 py-2 rounded-pill shadow-sm">${item.total} Entries</span>
                        </div>
                    </div>
                </div>
            `);
                    });
                }).fail(function() {
                    btn.html(originalHtml);
                    Swal.fire('Error', 'Failed to fetch report!', 'error');
                });
            };

         window.saveAssignTelecaller = function(forceAssign = false) {
                if (event) { event.preventDefault(); }

                // HTML IDs ke hisaab se values fetch karna
                let idFrom = $('#a_from').val();
                let idTo = $('#a_to').val();
                let telecallerId = $('#a_telecaller').val();

                if (!idFrom || !idTo || !telecallerId) {
                    Swal.fire('Error', 'Kripya sabhi fields dhyan se select karein.', 'error');
                    return;
                }

                // Agar form mein Data To, Data From se chhota dal diya jaye toh
                if (parseInt(idFrom) > parseInt(idTo)) {
                    Swal.fire('Error', '"Data From" ki value "Data To" se choti ya barabar honi chahiye.', 'error');
                    return;
                }

                // Button text loading karna (ID ya class check kar lein, aapke HTML me btn-primary hai)
                let btn = $('#assignForm .btn-primary');
                let originalText = btn.html();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Assigning...');

                $.ajax({
                    url: '/api/v1/interested-customers/assign-telecaller',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        id_from: idFrom,
                        id_to: idTo,
                        telecaller_id: telecallerId,
                        force_assign: forceAssign 
                    },
                    success: function(response) {
                        btn.prop('disabled', false).html(originalText);

                        if (response.status === 'conflict') {
                            let idsToShow = response.assigned_ids.slice(0, 5).join(', ');
                            let moreText = response.assigned_ids.length > 5 ? ' aur aage...' : '';

                            Swal.fire({
                                title: 'Data Pehle Se Assign Hai!',
                                text: `In numbers ke beech mein ${response.assigned_count} rows pehle se kisi aur ko assign hain (IDs: ${idsToShow}${moreText}). Kya aap inhe overwrite karke naye telecaller ko dena chahte hain?`,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Haan, Assign Karein!',
                                cancelButtonText: 'Nahi, Cancel'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    saveAssignTelecaller(true);
                                }
                            });

                        } else if (response.status === 'success') {
                            Swal.fire('Success!', response.message, 'success');
                            
                            // Input fields ko empty karna (Naye IDs ke hisaab se)
                            $('#a_from, #a_to, #a_telecaller').val('');
                            
                            if(typeof dataTableMain !== 'undefined') {
                                dataTableMain.ajax.reload(null, false);
                            } else {
                                loadAllData();
                            }
                        }
                    },
                    error: function(err) {
                        btn.prop('disabled', false).html(originalText);
                        Swal.fire('Error', 'Assign karte waqt koi network error aa gaya.', 'error');
                    }
                });
            };

        });
    </script>
@endpush
