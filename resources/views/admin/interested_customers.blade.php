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

        /* Custom Tabs for Mobile (Side-by-Side) */
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
            <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Interested Customers</h4>
            <button type="button" class="btn text-white px-3 py-2 shadow-sm" style="background-color: var(--brand-primary);"
                onclick="openModal('add')">
                <i class="fas fa-plus me-1"></i> Add Customer
            </button>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold"><i class="fas fa-headset me-2 text-primary"></i> Assign Telecaller</h6>
            </div>
            <div class="card-body">
                <form id="assignForm" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Telecaller ID (From Staff List)</label>
                        <input type="text" id="a_telecaller" class="form-control" list="staffDataList"
                            placeholder="Search ID or Name" required autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status Category</label>
                        <select id="a_status" class="form-select" required>
                            <option value="General">General Data</option>
                            <option value="Interested">Interested Data</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Data From (Row)</label>
                        <input type="number" id="a_from" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-2">
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

        <div class="accordion mb-4 shadow-sm border-0" id="filterAccordion">
            <div class="accordion-item border-0">
                <h2 class="accordion-header"><button class="accordion-button collapsed fw-bold bg-light" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseFilters"><i
                            class="fas fa-filter me-2 text-primary"></i> Advanced Download & Print Reports</button></h2>
                <div id="collapseFilters" class="accordion-collapse collapse" data-bs-parent="#filterAccordion">
                    <div class="accordion-body">
                        <form id="filterForm" class="row g-3">
                            <div class="col-md-2"><label class="form-label">From Date</label><input type="date"
                                    id="r_from" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">To Date</label><input type="date"
                                    id="r_to" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Follow-up Month</label>
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
                                    id="r_refer" class="form-control" list="staffDataList" placeholder="Search Refer By">
                            </div>
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

        <div class="d-block d-md-none mb-3">
            <input type="text" id="mobileGlobalSearch" class="form-control shadow-sm mb-3"
                placeholder="Search Customers by Name, Mobile...">

            <ul class="nav nav-pills nav-pills-custom d-flex w-100 mb-2" id="mobileTabs">
                <li class="nav-item flex-fill text-center">
                    <button class="nav-link active w-100" data-bs-toggle="tab"
                        data-bs-target="#interestedList">Interested</button>
                </li>
                <li class="nav-item flex-fill text-center">
                    <button class="nav-link w-100" data-bs-toggle="tab" data-bs-target="#generalList">General</button>
                </li>
            </ul>

            <div class="d-flex gap-2 mb-2">
                <button type="button" class="btn btn-sm text-white shadow-sm flex-fill"
                    style="background-color: #10b981;" onclick="downloadMobileExcel('interested')"><i
                        class="fas fa-file-excel me-1"></i> Interested Excel</button>
                <button type="button" class="btn btn-sm text-white shadow-sm flex-fill"
                    style="background-color: #10b981;" onclick="downloadMobileExcel('general')"><i
                        class="fas fa-file-excel me-1"></i> General Excel</button>
            </div>
        </div>

        <ul class="nav nav-tabs mb-3 d-none d-md-flex" id="desktopTabs">
            <li class="nav-item"><button class="nav-link active fw-bold text-primary" data-bs-toggle="tab"
                    data-bs-target="#interestedList">Interested Customers Data</button></li>
            <li class="nav-item"><button class="nav-link fw-bold text-secondary" data-bs-toggle="tab"
                    data-bs-target="#generalList">General Customers Data</button></li>
        </ul>

        <div class="tab-content">

            <div class="tab-pane fade show active" id="interestedList">

                <div class="card border-0 shadow-sm d-none d-md-block mb-4">
                    <div class="card-body p-3 table-responsive">
                        <table id="intTable" class="table table-hover table-custom w-100">
                            <thead>
                                <tr>
                                    <th>Branch</th>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>Interested For</th>
                                    <th>Budget</th>
                                    <th>Telecaller</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div id="mIntContainer" class="d-block d-md-none"></div>
                <button id="btnLoadInt" class="btn btn-outline-primary w-100 fw-bold d-none d-md-none mb-4 py-2"
                    style="border-radius: 8px;">Load More (Interested)...</button>
            </div>

            <div class="tab-pane fade" id="generalList">

                <div class="card border-0 shadow-sm d-none d-md-block mb-4">
                    <div class="card-body p-3 table-responsive">
                        <table id="genTable" class="table table-hover table-custom w-100">
                            <thead>
                                <tr>
                                    <th>Branch</th>
                                    <th>Name</th>
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

                <div id="mGenContainer" class="d-block d-md-none"></div>
                <button id="btnLoadGen" class="btn btn-outline-secondary w-100 fw-bold d-none d-md-none mb-4 py-2"
                    style="border-radius: 8px;">Load More (General)...</button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i
                            class="fas fa-eye me-2 text-info"></i> Customer Details Overview</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light h-100">
                                <h6 class="fw-bold text-primary mb-3">System & Staff Info</h6>
                                <p class="mb-1"><strong>Branch:</strong> <span id="v_branch" class="text-dark"></span>
                                </p>
                                <p class="mb-1"><strong>Assigned Telecaller:</strong> <span id="v_tele"
                                        class="text-dark"></span></p>
                                <p class="mb-0"><strong>Refer By:</strong> <span id="v_referby"
                                        class="text-dark"></span> (<span id="v_ref_type" class="small"></span>)</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light h-100">
                                <h6 class="fw-bold text-primary mb-3">Status & Tracking</h6>
                                <p class="mb-1"><strong>Current Status:</strong> <span id="v_status"
                                        class="badge"></span></p>
                                <p class="mb-1"><strong>Date of Calling:</strong> <span id="v_doc"></span></p>
                                <p class="mb-0"><strong>Next Follow-up:</strong> <span id="v_next_fup"
                                        class="text-danger fw-bold"></span></p>
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <h6 class="fw-bold text-secondary border-bottom pb-2">Customer Personal Info</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Name</p>
                            <h6 class="fw-bold" id="v_name"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Mobile</p>
                            <h6 class="fw-bold" id="v_mobile"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Alternate Mobile</p>
                            <h6 class="fw-bold" id="v_alt"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Email</p>
                            <h6 class="fw-bold" id="v_email"></h6>
                        </div>
                        <div class="col-md-8">
                            <p class="small text-muted mb-0">Address</p>
                            <h6 class="fw-bold" id="v_addr"></h6>
                        </div>

                        <div class="col-12 mt-3">
                            <h6 class="fw-bold text-secondary border-bottom pb-2">Requirements & Budget</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Interested For</p>
                            <h6 class="fw-bold text-primary" id="v_int_for"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Required For (Phase)</p>
                            <h6 class="fw-bold text-info" id="v_req_for"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Budget</p>
                            <h6 class="fw-bold text-success" id="v_budget"></h6>
                        </div>

                        <div class="col-12 mt-3">
                            <h6 class="fw-bold text-secondary border-bottom pb-2">Additional Remarks</h6>
                        </div>
                        <div class="col-12">
                            <div class="p-2 border rounded bg-light" style="font-size:13px;" id="v_remark"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="custModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalTitle" style="color: var(--sidebar-bg);">Manage Customer
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="custForm" class="row g-3">
                        <input type="hidden" id="edit_id">

                        <div class="col-md-3">
                            <label class="form-label">Branch *</label>
                            <select name="branch_id" id="f_branch" class="form-select" required></select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Customer Name *</label><input type="text"
                                name="cust_name" id="f_name" class="form-control" required></div>

                        <div class="col-md-3"><label class="form-label">Assigned Tele-Caller</label>
                            <input type="text" name="assigned_telecaller" id="f_tele" class="form-control"
                                list="staffDataList" autocomplete="off" placeholder="Search ID or Name">
                        </div>

                        <div class="col-md-3"><label class="form-label">Mobile Number *</label><input type="text"
                                name="mobile" id="f_mob" class="form-control" maxlength="10" required></div>
                        <div class="col-md-3"><label class="form-label">Alternate Number</label><input type="text"
                                name="alternate_no" id="f_alt" class="form-control" maxlength="10"></div>
                        <div class="col-md-3"><label class="form-label">Email ID</label><input type="email"
                                name="email" id="f_email" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Date of Calling</label><input type="date"
                                name="date" id="f_date" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Reference Type</label>
                            <select name="reference" id="f_ref" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="ADMIN">Admin</option>
                                <option value="MARKETING">Marketing</option>
                                <option value="OTHER">Others</option>
                            </select>
                        </div>

                        <div class="col-md-3"><label class="form-label">Refer By</label>
                            <input type="text" name="refer_by" id="f_refby" class="form-control"
                                list="staffDataList" autocomplete="off" placeholder="Search ID or Name">
                        </div>

                        <div class="col-md-6"><label class="form-label">Address</label><input type="text"
                                name="address" id="f_addr" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Interested For</label>
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

                        <div class="col-md-3"><label class="form-label">Status *</label>
                            <select name="status" id="f_status" class="form-select" required>
                                <option value="General">General</option>
                                <option value="Connected">Connected</option>
                                <option value="Not Reachable">Not Reachable</option>
                                <option value="Follow Up">Follow Up</option>
                                <option value="Site Visit Schedule">Site Visit Schedule</option>
                                <option value="Site Visit Done">Site Visit Done</option>
                                <option value="Booking Done">Booking Done</option>
                                <option value="Lost">Lost</option>
                                <option value="Interested">Interested</option>
                            </select>
                        </div>

                        <div class="col-md-3"><label class="form-label">Follow-up Date</label><input type="date"
                                name="followup_date" id="f_fdate" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Follow-up Month</label>
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
                            <button type="button" class="btn btn-secondary px-4 me-2"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success px-5" id="saveBtn">Save Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="printArea" style="display:none;"></div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {
            const apiToken = localStorage.getItem('admin_token');
            let mode = 'add';

            let intData = [],
                genData = [];
            let intIndex = 0,
                genIndex = 0;
            const CHUNK = 10;

            let intTable = $('#intTable').DataTable({
                dom: '<"row"<"col-md-6"B><"col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    className: 'btn btn-success btn-sm shadow-sm'
                }]
            });
            let genTable = $('#genTable').DataTable({
                dom: '<"row"<"col-md-6"B><"col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    className: 'btn btn-success btn-sm shadow-sm'
                }]
            });

            // Load Branches First
            function loadBranches() {
                $.ajax({
                    url: '/api/v1/admin/branches',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        let options = '<option value="">-- Choose Branch --</option>';
                        res.data.forEach(b => options +=
                            `<option value="${b.id}">${b.branch_name}</option>`);
                        $('#f_branch').html(options);
                    }
                });
            }
            loadBranches();

            function loadAllData() {
                $.ajax({
                    url: '/api/v1/admin/interested-customers',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        intData = res.interested;
                        genData = res.general;

                      // Bind Global Datalist
                let dlHtml = '';
                res.staff_list.forEach(s => dlHtml += `<option value="${s.staff_id}">${s.name} (${s.role})</option>`);
                $('#staffDataList').html(dlHtml);

                        intTable.clear();
                        intData.forEach(d => {
                            let bName = d.branch ? d.branch.branch_name : '-';
                            let actions =
                                `<button class="btn btn-sm btn-light text-info me-1 view-btn" data-id="${d.id}"><i class="fas fa-eye"></i></button><button class="btn btn-sm btn-light text-primary me-1 edit-btn" data-id="${d.id}"><i class="fas fa-edit"></i></button><button class="btn btn-sm btn-light text-danger delete-btn" data-id="${d.id}"><i class="fas fa-trash"></i></button>`;
                            intTable.row.add([bName, d.cust_name, d.mobile, d.interested_for ||
                                '-', d.budget || '-', d.assigned_telecaller || '-',
                                `<span class="badge bg-info">${d.status}</span>`,
                                actions
                            ]);
                        });
                        intTable.draw();

                        genTable.clear();
                        genData.forEach(d => {
                            let bName = d.branch ? d.branch.branch_name : '-';
                            let actions =
                                `<button class="btn btn-sm btn-light text-info me-1 view-btn" data-id="${d.id}"><i class="fas fa-eye"></i></button><button class="btn btn-sm btn-light text-primary me-1 edit-btn" data-id="${d.id}"><i class="fas fa-edit"></i></button><button class="btn btn-sm btn-light text-danger delete-btn" data-id="${d.id}"><i class="fas fa-trash"></i></button>`;
                            genTable.row.add([bName, d.cust_name, d.mobile, d.required_for ||
                                '-', d.refer_by || '-', d.assigned_telecaller || '-',
                                `<span class="badge bg-secondary">${d.status}</span>`,
                                actions
                            ]);
                        });
                        genTable.draw();

                        // Reset Mobile Cards
                        intIndex = 0;
                        genIndex = 0;
                        $('#mIntContainer').empty();
                        $('#mGenContainer').empty();
                        renderMobileInt();
                        renderMobileGen();
                    }
                });
            }
            loadAllData();

            // ================= MOBILE RENDER LOGIC =================
            function renderMobileInt() {
                let chunk = intData.slice(intIndex, intIndex + CHUNK);
                let html = '';
                chunk.forEach(d => {
                    html += `<div class="mobile-item m-card-item">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div><h6 class="fw-bold mb-0">${d.cust_name}</h6><span class="small text-danger"><i class="fas fa-map-marker-alt"></i> ${d.branch ? d.branch.branch_name : '-'}</span></div>
                    <span class="badge bg-info">${d.status}</span>
                </div>
                <div class="small text-muted mb-2"><i class="fas fa-phone me-1"></i> ${d.mobile} | Tele: ${d.assigned_telecaller||'N/A'}</div>
                <div class="small text-muted mb-2"><b>Interested:</b> ${d.interested_for||'-'} | <b>Next:</b> ${d.followup_date||'-'}</div>
                <div class="d-flex gap-2 border-top pt-2 mt-2">
                    <button class="btn btn-sm btn-light text-info flex-fill fw-bold view-btn" data-id="${d.id}"><i class="fas fa-eye"></i> View</button>
                    <button class="btn btn-sm btn-light text-primary flex-fill fw-bold edit-btn" data-id="${d.id}"><i class="fas fa-edit"></i> Edit</button>
                    <button class="btn btn-sm btn-light text-danger flex-fill fw-bold delete-btn" data-id="${d.id}"><i class="fas fa-trash"></i> Delete</button>
                </div>
            </div>`;
                });
                $('#mIntContainer').append(html);
                intIndex += CHUNK;
                if (intIndex >= intData.length) $('#btnLoadInt').removeClass('d-block').addClass('d-none');
                else $('#btnLoadInt').removeClass('d-none').addClass('d-block');
            }

            function renderMobileGen() {
                let chunk = genData.slice(genIndex, genIndex + CHUNK);
                let html = '';
                chunk.forEach(d => {
                    html += `<div class="mobile-item m-card-item">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div><h6 class="fw-bold mb-0">${d.cust_name}</h6><span class="small text-danger"><i class="fas fa-map-marker-alt"></i> ${d.branch ? d.branch.branch_name : '-'}</span></div>
                    <span class="badge bg-secondary">${d.status}</span>
                </div>
                <div class="small text-muted mb-2"><i class="fas fa-phone me-1"></i> ${d.mobile} | Tele: ${d.assigned_telecaller||'N/A'}</div>
                <div class="d-flex gap-2 border-top pt-2 mt-2">
                    <button class="btn btn-sm btn-light text-info flex-fill fw-bold view-btn" data-id="${d.id}"><i class="fas fa-eye"></i> View</button>
                    <button class="btn btn-sm btn-light text-primary flex-fill fw-bold edit-btn" data-id="${d.id}"><i class="fas fa-edit"></i> Edit</button>
                    <button class="btn btn-sm btn-light text-danger flex-fill fw-bold delete-btn" data-id="${d.id}"><i class="fas fa-trash"></i> Delete</button>
                </div>
            </div>`;
                });
                $('#mGenContainer').append(html);
                genIndex += CHUNK;
                if (genIndex >= genData.length) $('#btnLoadGen').removeClass('d-block').addClass('d-none');
                else $('#btnLoadGen').removeClass('d-none').addClass('d-block');
            }

            $('#btnLoadInt').click(() => renderMobileInt());
            $('#btnLoadGen').click(() => renderMobileGen());

            // GLOBAL MOBILE SEARCH (Works for both active tabs)
            $('#mobileGlobalSearch').on('keyup', function() {
                let v = $(this).val().toLowerCase();
                $('.m-card-item').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1)
                });
            });

            // ================= ADD / EDIT MODALS =================
            window.openModal = function(type, id = null) {
                mode = type;
                $('#custForm')[0].reset();
                $('#modalTitle').text(type === 'add' ? 'Add Interested Customer' : 'Edit Customer');

                if (type === 'edit') {
                    $.get({
                        url: `/api/v1/admin/interested-customers/${id}`,
                        headers: {
                            'Authorization': 'Bearer ' + apiToken
                        },
                        success: function(res) {
                            let d = res.data;
                            $('#edit_id').val(d.id);
                            $('#f_branch').val(d.branch_id);
                            $('#f_name').val(d.cust_name);
                            $('#f_tele').val(d.assigned_telecaller);
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
                            $('#f_status').val(d.status);
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

            // ================= VIEW MODAL LOGIC =================
            $(document).on('click', '.view-btn', function() {
                let id = $(this).data('id');
                $.get({
                    url: `/api/v1/admin/interested-customers/${id}`,
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        let d = res.data;
                        $('#v_branch').text(d.branch ? d.branch.branch_name : 'N/A');
                        $('#v_tele').text(d.assigned_telecaller || 'Not Assigned');
                        $('#v_referby').text(d.refer_by || 'N/A');
                        $('#v_ref_type').text(d.reference || '-');

                        let bClass = d.status === 'General' ? 'bg-secondary' :
                            'bg-info text-dark';
                        $('#v_status').text(d.status).attr('class', 'badge ' + bClass);

                        $('#v_doc').text(d.date || 'N/A');
                        $('#v_next_fup').text(d.followup_date ?
                            `${d.followup_date} (${d.followup_month||''})` : 'N/A');

                        $('#v_name').text(d.cust_name || 'N/A');
                        $('#v_mobile').text(d.mobile || 'N/A');
                        $('#v_alt').text(d.alternate_no || 'N/A');
                        $('#v_email').text(d.email || 'N/A');
                        $('#v_addr').text(d.address || 'N/A');

                        $('#v_int_for').text(d.interested_for || 'N/A');
                        $('#v_req_for').text(d.required_for || 'N/A');
                        $('#v_budget').text(d.budget ? `₹ ${d.budget}` : 'N/A');

                        $('#v_remark').text(d.remark || 'No Remarks Added.');

                        $('#viewModal').modal('show');
                    }
                });
            });

            // ================= DELETE =================
            $(document).on('click', '.delete-btn', function() {
                if (confirm("Delete this customer permanently?")) {
                    $.ajax({
                        url: `/api/v1/admin/interested-customers/${$(this).data('id')}`,
                        type: 'DELETE',
                        headers: {
                            'Authorization': 'Bearer ' + apiToken
                        },
                        success: function(res) {
                            loadAllData();
                        }
                    });
                }
            });

            // ================= SAVE DATA =================
            $('#custForm').submit(function(e) {
                e.preventDefault();
                let id = $('#edit_id').val();
                let url = mode === 'add' ? '/api/v1/admin/interested-customers' :
                    `/api/v1/admin/interested-customers/${id}`;
                let type = mode === 'add' ? 'POST' : 'PUT';

                $.ajax({
                    url: url,
                    type: type,
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    data: $(this).serialize(),
                    success: function(res) {
                        alert(res.message);
                        $('#custModal').modal('hide');
                        loadAllData();
                    }
                });
            });

            // ================= BULK ASSIGN TELECALLER =================
            $('#assignForm').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: '/api/v1/admin/interested-customers/assign-telecaller',
                    type: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    data: {
                        telecaller: $('#a_telecaller').val(),
                        status: $('#a_status').val(),
                        data_from: $('#a_from').val(),
                        data_to: $('#a_to').val()
                    },
                    success: function(res) {
                        alert(res.message);
                        if (res.status) loadAllData();
                    }
                });
            });

            // ================= REPORTS =================
            window.fetchAndExport = function(actionType) {
                let filters = {
                    from_date: $('#r_from').val(),
                    to_date: $('#r_to').val(),
                    followup_month: $('#r_month').val(),
                    refer_by: $('#r_refer').val(),
                    budget_from: $('#r_bfrom').val(),
                    budget_to: $('#r_bto').val()
                };

                $.post({
                    url: '/api/v1/admin/interested-customers/filter-reports',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    data: filters,
                    success: function(res) {
                        if (res.data.length === 0) {
                            alert("No data found for the selected filters.");
                            return;
                        }

                        let tableHtml =
                            `<table border="1" width="100%" style="border-collapse:collapse; font-size:12px; font-family:Arial;" id="exportTableTemp">
                    <thead style="background:#f2f2f2;"><tr><th>SL</th><th>Branch</th><th>Name</th><th>Email</th><th>Mobile</th><th>Budget</th><th>Telecaller</th><th>Status</th><th>Refer By</th><th>Remarks</th></tr></thead><tbody>`;

                        res.data.forEach((d, i) => {
                            tableHtml +=
                                `<tr><td>${i+1}</td><td>${d.branch ? d.branch.branch_name : '-'}</td><td>${d.cust_name}</td><td>${d.email||'-'}</td><td>${d.mobile}</td><td>${d.budget||'-'}</td><td>${d.assigned_telecaller||'-'}</td><td>${d.status}</td><td>${d.refer_by||'-'}</td><td>${d.remark||'-'}</td></tr>`;
                        });
                        tableHtml += `</tbody></table>`;

                        if (actionType === 'excel') {
                            $('#printArea').html(tableHtml);
                            let wb = XLSX.utils.table_to_book(document.getElementById(
                                "exportTableTemp"), {
                                sheet: "Report"
                            });
                            XLSX.writeFile(wb,
                                `Customer_Report_${new Date().toISOString().split('T')[0]}.xlsx`
                                );
                            $('#printArea').empty();
                        } else if (actionType === 'print') {
                            let win = window.open('', '', 'width=1000,height=700');
                            win.document.write(`<html><head><title>Print Report</title></head><body>
                        <h2 style="text-align:center; font-family:Arial;">Amitabh Builders - Customer Report</h2>
                        <hr>${tableHtml}</body></html>`);
                            win.document.close();
                            win.focus();
                            win.print();
                        }
                    }
                });
            };

            window.downloadMobileExcel = function(type) {
                if (type === 'interested') $('.buttons-excel').eq(0).click();
                else $('.buttons-excel').eq(1).click();
            };
        });
    </script>
@endpush
