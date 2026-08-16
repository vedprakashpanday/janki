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

        /* 🔥 Mobile Floating Action Bar */
        .mobile-floating-bar {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            padding: 10px 20px;
            border-radius: 50px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 1050;
            display: none; /* Managed by JS */
            width: 90%;
            max-width: 400px;
            justify-content: space-between;
            align-items: center;
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

        .nav-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            border-bottom: 1px solid #dee2e6;
        }

        .nav-tabs::-webkit-scrollbar {
            display: none;
        }

        .nav-tabs .nav-link {
            font-weight: 600;
            color: #6b7280;
            border: none;
            border-bottom: 3px solid transparent;
            white-space: nowrap;
        }

        .nav-tabs .nav-link.active {
            color: var(--brand-primary);
            border-bottom: 3px solid var(--brand-primary);
            background: transparent;
        }

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: #4b5563;
        }

        /* 🔥 Bulk Action Floating Bar */
        #bulkActionDiv {
            display: none;
            background: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border: 1px solid #e2e8f0;
            animation: slideDown 0.3s ease-out forwards;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Member Details</h4>
            </div>

            <div class="d-flex gap-2">
               <!-- NAYA BULK ACTION DIV JISME 2 BUTTONS HAIN -->
                <div id="bulkActionDiv" class="d-none align-items-center gap-2 me-3">
                    <span class="fw-bold text-secondary small border-end pe-2"><span id="selectedCount">0</span> Selected</span>
                    <button type="button" class="btn btn-outline-primary btn-sm fw-bold shadow-sm" id="selectAllDbBtn">
                        <i class="fas fa-check-double me-1"></i> Select All in DB
                    </button>
                    <button type="button" class="btn btn-danger btn-sm fw-bold shadow-sm" id="bulkDeleteBtn">
                        <i class="fas fa-trash-alt me-1"></i> Delete Selected
                    </button>
                </div>

                {{-- <button type="button" id="addMemberBtn" class="btn text-white px-3 py-2 shadow-sm secured-item"
                    data-permission="member_add" style="background-color: var(--brand-primary);" onclick="openModal('add')">
                    <i class="fas fa-plus me-1"></i> Add New Member
                </button> --}}

                <!-- 🔥 NAYA EXPORT/PRINT DIV -->
    <div id="exportPrintDiv" class="align-items-center gap-2 d-none">
        <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm" id="btnExportExcel">
            <i class="fas fa-file-excel me-1"></i> Excel
        </button>
        <button type="button" class="btn btn-secondary btn-sm fw-bold shadow-sm" id="btnPrintMembers">
            <i class="fas fa-print me-1"></i> Print
        </button>
    </div>
            </div>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search Member...">
            <button type="button" class="btn text-white shadow-sm" style="background-color: #10b981; display: none;"
                id="mobileExcelBtn"><i class="fas fa-file-excel"></i></button>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="allTimeMemberTable" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="text-center">
                                    <input type="checkbox" class="form-check-input" id="selectAllCheckbox">
                                </th>
                                <th>MEMBER ID</th>
                                <th>Branch</th>
                                <th>Name</th>
                                <th>Sponsor ID</th>
                                {{-- <th>Mobile</th> --}}
                                <th>Date Of Joining</th>
                                <th>Status</th> <!-- 🔥 NAYA COLUMN -->
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 🔥 NAYA: YEH MOBILE VIEW KE LIYE MISSING THA 🔥 -->
      
        <div id="mobileCardsContainer" class="d-block d-md-none"></div>

        <!-- 🔥 LOAD MORE BUTTON FOR MOBILE 🔥 -->
        <div class="text-center d-md-none mt-3 mb-5" id="mobileLoadMoreDiv" style="display:none;">
            <button class="btn text-white btn-sm px-4 rounded-pill shadow-sm" id="mobileLoadMoreBtn" style="background-color: var(--brand-primary);">
                <i class="fas fa-sync-alt me-1"></i> Load More
            </button>
        </div>

        <!-- 🔥 FLOATING ACTION BAR FOR MOBILE 🔥 -->
        <div id="mobileBulkActionDiv" class="mobile-floating-bar d-md-none">
            <span class="fw-bold text-secondary small"><span id="mobileSelectedCount">0</span> Selected</span>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm" id="mobileSelectAllDbBtn" title="Select All in DB" style="width: 35px; height: 35px;">
                    <i class="fas fa-check-double"></i>
                </button>
                <button type="button" class="btn btn-danger btn-sm rounded-circle shadow-sm" id="mobileBulkDeleteBtn" title="Delete" style="width: 35px; height: 35px;">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>

    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i
                            class="fas fa-eye me-2 text-info"></i> Member Details</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold text-primary mb-3">Login Credentials</h6>
                                <p class="mb-1"><strong>Member ID:</strong> <span id="v_mem_id" class="text-dark"></span>
                                </p>
                                <p class="mb-0"><strong>Password:</strong> <span id="v_password"
                                        class="text-danger fw-bold"></span></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light h-100">
                                <h6 class="fw-bold text-primary mb-3">Office Info</h6>
                                <p class="mb-1"><strong>Branch:</strong> <span id="v_branch" class="text-dark"></span>
                                </p>
                                <p class="mb-0"><strong>Sponsor:</strong> <span id="v_sponsor" class="text-dark"></span>
                                </p>
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <h6 class="fw-bold text-secondary border-bottom pb-2">Personal Information</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Full Name</p>
                            <h6 class="fw-bold" id="v_name"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Designation</p>
                            <h6 class="fw-bold" id="v_designation"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Grade</p>
                            <h6 class="fw-bold" id="v_grade"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Mobile Number</p>
                            <h6 class="fw-bold" id="v_mobile"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">DOJ</p>
                            <h6 class="fw-bold" id="v_doj"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Aadhar No.</p>
                            <h6 class="fw-bold" id="v_aadhar"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">PAN No.</p>
                            <h6 class="fw-bold text-uppercase" id="v_pan"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Employment Status</p>
                            <h6 class="fw-bold" id="v_mem_status"></h6>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="memberModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold" id="modalTitle" style="color: var(--sidebar-bg);"><i
                            class="fas fa-user-plus me-2 text-primary"></i> Register Member</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <form id="memberForm" enctype="multipart/form-data">
                        <input type="hidden" id="edit_id">

                        <ul class="nav nav-tabs px-4 pt-3 bg-light" role="tablist">
                            <li class="nav-item" role="presentation"><button class="nav-link active fw-bold"
                                    data-bs-toggle="tab" data-bs-target="#tab-personal" type="button"><i
                                        class="fas fa-user me-1"></i> Personal Info</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link fw-bold"
                                    data-bs-toggle="tab" data-bs-target="#tab-bank" type="button"><i
                                        class="fas fa-university me-1"></i> Bank Details</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link fw-bold"
                                    data-bs-toggle="tab" data-bs-target="#tab-nominee" type="button"><i
                                        class="fas fa-user-shield me-1"></i> Nominee Info</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link fw-bold"
                                    data-bs-toggle="tab" data-bs-target="#tab-docs" type="button"><i
                                        class="fas fa-file-upload me-1"></i> Documents</button></li>
                        </ul>

                        <div class="tab-content p-4">
                            <div class="tab-pane fade show active" id="tab-personal">

                                <div class="col-12 mb-4 border-bottom pb-3 bg-white p-3 rounded shadow-sm"
                                    id="transferred_section">
                                    <div class="form-check form-switch fs-6">
                                        <input class="form-check-input" type="checkbox" id="is_transferred">
                                        <label class="form-check-label fw-bold text-primary" for="is_transferred">Is it a
                                            Transferred Associate?</label>
                                    </div>
                                    <div id="transferred_div" style="display:none;" class="row mt-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-secondary small">Select Company (For
                                                Transfer)</label>
                                            <input type="text" id="t_company" class="form-control"
                                                list="tCompanyList" placeholder="Search Company">
                                            <datalist id="tCompanyList"></datalist>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-secondary small">Select Transferred
                                                Member</label>
                                            <input type="text" id="t_member" class="form-control" list="tMemberList"
                                                placeholder="Select Member">
                                            <datalist id="tMemberList"></datalist>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4 pb-3 border-bottom">
                                    <!-- Company -->
<div class="col-md-4" id="wrap_company">
    <label class="form-label text-secondary small">Company <span class="text-danger">*</span></label>
    <select name="company_id" id="f_company" class="form-select select2-ajax" style="width: 100%;" required>
        <option value="">Search Company...</option>
    </select>
</div>

                                   <!-- Branch -->
<div class="col-md-4" id="wrap_branch">
    <label class="form-label text-secondary small">Branch <span class="text-danger">*</span></label>
    <select name="branch_id" id="f_branch" class="form-select select2-ajax" style="width: 100%;" required>
        <option value="">Search Branch...</option>
    </select>
</div>

                                    <!-- Department -->
<div class="col-md-4" id="wrap_dept">
    <label class="form-label text-secondary small">Department <span class="text-danger">*</span></label>
    <select name="department_id" id="f_department" class="form-select select2-ajax" style="width: 100%;" required>
        <option value="">Search Department...</option>
    </select>
</div>

                                <!-- Sponsor ID -->
<div class="col-md-4">
    <label class="form-label text-secondary small">Sponsor ID</label>
    <select name="sponsor_id" id="f_sponsor_id" class="form-select select2-ajax fw-bold" style="width: 100%;">
        <option value="">Search Sponsor...</option>
    </select>
</div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Sponsor Name</label>
                                        <input type="text" name="sponsor_name" class="form-control bg-light fw-bold"
                                            id="f_sponsor_name" readonly placeholder="Enter Sponsor Name">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Member ID <span
                                                class="text-info">(Editable)</span></label>
                                        <input type="text" name="member_id" id="f_member_id"
                                            class="form-control fw-bold" placeholder="Auto-generated if left blank">
                                    </div>
                                    <div class="col-md-4" id="manual_series_div" style="display:none;">
                                        <label class="form-label text-secondary small">Manual Series <span
                                                class="text-warning">(001-007 Only)</span></label>
                                        <input type="number" name="manual_series" id="f_manual_series"
                                            class="form-control" min="1" max="7"
                                            placeholder="Leave blank for 008+ auto">
                                    </div>

                                    <div class="col-md-4 password-edit-div" style="display:none;">
                                        <label class="form-label text-secondary small">Login Password <span
                                                class="text-info">(Editable)</span></label>
                                        <input type="text" name="password" id="f_password" class="form-control"
                                            placeholder="Update password">
                                    </div>
                                    <div class="col-md-4 password-gen-div">
                                        <label class="form-label text-secondary small">Login Password <span
                                                class="text-info">(Editable)</span></label>
                                        <input type="text" name="password" id="mem_pass_gen" class="form-control"
                                            placeholder="Auto generated or Type">
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Name in Full <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="member_name" class="form-control" id="f_name"
                                            required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Select Blood Group</label>
                                        <select class="form-select" name="blood_group" id="f_blood">
                                            <option value="">-- Select --</option>
                                            <option value="A+">A+</option>
                                            <option value="A-">A-</option>
                                            <option value="B+">B+</option>
                                            <option value="B-">B-</option>
                                            <option value="O+">O+</option>
                                            <option value="O-">O-</option>
                                            <option value="AB+">AB+</option>
                                            <option value="AB-">AB-</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">S/o, D/o, Spouse's Name</label>
                                        <input type="text" name="so_do_name" class="form-control" id="f_sodowo">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Mother's Name</label>
                                        <input type="text" name="parents_name" class="form-control" id="f_mother">
                                    </div>
                                   <div class="col-md-4">
    <label class="form-label small fw-bold">Designation <span class="text-danger">*</span></label>
    <select name="designation" id="designation" class="form-select" required>
        <option value="">-- Select Designation --</option>
    </select>
</div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Grade</label>
                                        <select name="grade" class="form-select">
    <option value="">-- Select Grade --</option>
    <option value="Member Grade A">Member Grade A</option>
    <option value="Member Grade B">Member Grade B</option>
    <option value="Member Grade C">Member Grade C</option>
    <option value="Member Grade D">Member Grade D</option>
</select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small d-block">Gender</label>
                                        <div class="form-check form-check-inline mt-2"><input class="form-check-input"
                                                type="radio" name="gender" value="Male" id="g_m"><label
                                                class="form-check-label" for="g_m">Male</label></div>
                                        <div class="form-check form-check-inline mt-2"><input class="form-check-input"
                                                type="radio" name="gender" value="Female" id="g_f"><label
                                                class="form-check-label" for="g_f">Female</label></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small d-block">Marital Status</label>
                                        <div class="form-check form-check-inline mt-2"><input class="form-check-input"
                                                type="radio" name="marital_status" value="Married"
                                                id="ms_m"><label class="form-check-label"
                                                for="ms_m">Married</label></div>
                                        <div class="form-check form-check-inline mt-2"><input class="form-check-input"
                                                type="radio" name="marital_status" value="Unmarried"
                                                id="ms_u"><label class="form-check-label"
                                                for="ms_u">Unmarried</label></div>
                                    </div>
                                    <div class="col-md-4"><label
                                            class="form-label text-secondary small">Nationality</label><input
                                            type="text" name="nationality" class="form-control" value="Indian"
                                            readonly></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Date of
                                            Birth</label><input type="date" name="dob" class="form-control"
                                            id="f_dob"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Date of Joining
                                            <span class="text-danger">*</span></label><input type="date"
                                            name="doj" class="form-control" id="f_doj" required></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Date of
                                            Anniversary</label><input type="date" name="date_of_anniversary"
                                            class="form-control" id="f_doa"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Contact No. <span
                                                class="text-danger">*</span></label><input type="text" name="mobile"
                                            class="form-control" id="f_mobile" maxlength="10" required></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Alt.
                                            No.</label><input type="text" name="alternate_mobile" class="form-control"
                                            id="f_alt" maxlength="10"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Email
                                            ID</label><input type="email" name="email" class="form-control"
                                            id="f_email"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">PAN
                                            No.</label><input type="text" name="pan_number"
                                            class="form-control text-uppercase" id="f_pan" maxlength="10"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Aadhar Card
                                            No.<span class="text-danger">*</span></label><input type="text"
                                            name="aadhar_number" class="form-control" id="f_aadhar" maxlength="12"
                                            required></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Native
                                            Place</label><input type="text" name="native_place" class="form-control"
                                            id="f_native"></div>
                                    <div class="col-md-4"><label
                                            class="form-label text-secondary small">City/Town/Village</label><input
                                            type="text" name="city" class="form-control" id="f_city"></div>
                                    <div class="col-md-8"><label class="form-label text-secondary small">Communication
                                            Address</label><input type="text" name="address" class="form-control"
                                            id="f_address"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Pin
                                            Code</label><input type="text" name="pincode" class="form-control"
                                            id="f_pincode" maxlength="6"></div>
                                </div>
                            </div>

                         <div class="tab-pane fade" id="tab-bank">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="text-primary fw-bold mb-0"><i class="fas fa-university me-1"></i> Multiple Bank Details</h6>
                                    <button type="button" class="btn btn-sm btn-primary shadow-sm fw-bold" id="addBankBtn">
                                        <i class="fas fa-plus me-1"></i> Add Another Bank
                                    </button>
                                </div>
                                <div id="bank-container"></div>
                            </div>
                            <div class="tab-pane fade" id="tab-nominee">
                                <div class="row g-3">
                                    <div class="col-md-3"><label class="form-label text-secondary small">Nominee
                                            Name</label><input type="text" name="nominee_name" class="form-control"
                                            id="f_nom_name"></div>
                                    <div class="col-md-3"><label
                                            class="form-label text-secondary small">Relation</label><input type="text"
                                            name="nominee_relation" class="form-control" id="f_nom_rel"></div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">S/o, D/o,
                                            W/o</label><input type="text" name="nominee_so_do_wo" class="form-control"
                                            id="f_nom_so"></div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Date of
                                            Birth</label><input type="date" name="nominee_dob" class="form-control"
                                            id="f_nom_dob"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Mobile
                                            No</label><input type="text" name="nominee_mobile" class="form-control"
                                            id="f_nom_mob" maxlength="10"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Alt. Mobile
                                            No</label><input type="text" name="nominee_alternate_mobile"
                                            class="form-control" id="f_nom_alt" maxlength="10"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Email
                                            Id</label><input type="email" name="nominee_email" class="form-control"
                                            id="f_nom_email"></div>
                                    <div class="col-md-4"><label
                                            class="form-label text-secondary small">Aadhar</label><input type="text"
                                            name="nominee_aadhar" class="form-control" id="f_nom_aadhar" maxlength="12">
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">PAN</label><input
                                            type="text" name="nominee_pan" class="form-control text-uppercase"
                                            id="f_nom_pan" maxlength="10"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">PIN
                                            Code</label><input type="text" name="nominee_pincode" class="form-control"
                                            id="f_nom_pin" maxlength="6"></div>
                                    <div class="col-md-4"><label
                                            class="form-label text-secondary small">State</label><input type="text"
                                            name="nominee_state" class="form-control" id="f_nom_state"></div>
                                    <div class="col-md-4"><label
                                            class="form-label text-secondary small">District</label><input type="text"
                                            name="nominee_district" class="form-control" id="f_nom_dist"></div>
                                    <div class="col-md-4"><label
                                            class="form-label text-secondary small">Address</label><input type="text"
                                            name="nominee_address" class="form-control" id="f_nom_address"></div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-docs">
                                <h6 class="text-primary fw-bold border-bottom pb-2 mb-3"><i
                                        class="fas fa-user-circle me-1"></i> Member Documents</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4"><label class="form-label text-secondary small">Aadhar Card
                                            (.pdf)</label><input type="file" name="aadharcard"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">PAN Card
                                            (.pdf)</label><input type="file" name="pancard"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Bank Passbook
                                            (.pdf)</label><input type="file" name="bankpassbook"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Driving License
                                            (.pdf)</label><input type="file" name="drivinglicense"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Passport
                                            (.pdf)</label><input type="file" name="passport"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Passport Photo
                                            (Img)</label><input type="file" name="passport_photo"
                                            class="form-control form-control-sm" accept="image/*"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Signature (Img)
                                            <span class="text-danger">*</span></label><input type="file"
                                            name="sign" class="form-control form-control-sm" accept="image/*"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">10th Marksheet
                                            (.pdf)</label><input type="file" name="tenthmarksheet"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">12th Marksheet
                                            (.pdf)</label><input type="file" name="twelvethmarksheet"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Graduation Cert
                                            (.pdf)</label><input type="file" name="graduationcertificate"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">PG Certificate
                                            (.pdf)</label><input type="file" name="pgcertificate"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Other Docs
                                            (.pdf)</label><input type="file" name="otherdoc"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                </div>

                                <h6 class="text-primary fw-bold border-bottom pb-2 mb-3"><i
                                        class="fas fa-user-shield me-1"></i> Nominee Documents</h6>
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Aadhar
                                            (.pdf)</label><input type="file" name="nom_aadharcard"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee PAN
                                            (.pdf)</label><input type="file" name="nom_pancard"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Bank
                                            Passbook</label><input type="file" name="nom_bankpassbook"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Driving
                                            License</label><input type="file" name="nom_drivinglicense"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Passport
                                            (.pdf)</label><input type="file" name="nom_passport"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Photo
                                            (Img)</label><input type="file" name="nom_passport_photo"
                                            class="form-control form-control-sm" accept="image/*"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee 10th
                                            (.pdf)</label><input type="file" name="nom_tenthmarksheet"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee 12th
                                            (.pdf)</label><input type="file" name="nom_twelvethmarksheet"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Grad Cert
                                            (.pdf)</label><input type="file" name="nom_graduationcertificate"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee PG Cert
                                            (.pdf)</label><input type="file" name="nom_pgcertificate"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Other
                                            Docs</label><input type="file" name="nom_otherdoc"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                </div>

                                <div class="col-12 mt-4 pt-3 border-top">
                                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-info-circle me-1"></i>
                                        Employment Status</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-secondary small">Associate Status</label>
                                            <select name="status" id="f_status" class="form-select fw-bold">
                                                <option value="active" class="text-success">Active</option>
                                                <option value="inactive" class="text-danger">Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-secondary small">Associate Employment
                                                Status</label>
                                            <select name="mem_status" id="f_mem_status"
                                                class="form-select fw-bold text-primary">
                                                <option value="On Board">On Board</option>
                                                <option value="Probation">Probation</option>
                                                <option value="Confirmed">Confirmed</option>
                                                <option value="Notice Period">Notice Period</option>
                                                <option value="Resigned">Resigned</option>
                                                <option value="Relieved">Relieved</option>
                                                <option value="Terminated">Terminated</option>
                                                <option value="Dismissed">Dismissed</option>
                                                <option value="Suspended">Suspended</option>
                                                <option value="Transferred">Transferred</option>
                                                <option value="Retired">Retired</option>
                                                <option value="Deceased">Deceased</option>
                                                <option value="Rehired">Rehired</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="modal-footer bg-light border-top-0">
                            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn text-white px-5 shadow-sm fw-medium"
                                style="background-color: var(--brand-primary);" id="saveBtn">Save Member
                                Details</button>
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
        // Agar URL "jankivilla.com/admin/members" hai, toh ye "admin" dega.
        // Agar "jankivilla.com/employee/members" hai, toh "employee" dega.
        const currentPortal = "{{ Request::segment(1) }}"; 
    </script>

   <script>
$(document).ready(function() {
    $.fn.dataTable.ext.errMode = 'none';

    let mode = 'add';
    let sysContext = null;
    let isMasterAdmin = false;
    let isUserAdmin = false;


    // ==========================================
    // 🔥 TRANSFERRED MEMBER SEARCH & AUTO-FILL 🔥
    // ==========================================
    let transferredUsersList = [];

    $('#t_company').on('input change', function() {
        let val = $(this).val().trim();
        // Backend API search text directly
        if (val.length >= 3) {
            $.ajax({
                url: `/api/v1/members/search-companies?q=${val}`,
                type: 'GET',
                success: function(res) {
                    let topts = '';
                    res.data.forEach(c => { topts += `<option value="${c.id}">${c.company_name}</option>`; });
                    $('#tCompanyList').html(topts);
                }
            });
        }
    });

    // Company select hone par us company ke transferred members laana
    $('#t_company').on('change', function() {
        let cid = $(this).val();
        if (cid) {
            $.ajax({
                url: `/api/v1/members/transferred?company_id=${cid}`,
                type: 'GET',
                success: function(res) {
                    transferredUsersList = res.data; 
                    let opts = '';
                    res.data.forEach(m => {
                        let displayId = m.member_id || m.emp_id || 'N/A';
                        let displayName = m.member_name || m.name || 'Unknown';
                        let tag = m.source_type === 'employee' ? '(Employee)' : '(Member)';
                        opts += `<option value="${displayName} ${tag} - ${displayId}" data-id="${m.id}" data-type="${m.source_type}">`;
                    });
                    $('#tMemberList').html(opts);
                }
            });
        }
    });

    // Transferred Member select hone par form Auto-fill karna
    $('#t_member').on('input change', function() {
        let val = $(this).val();
        let selectedOption = $('#tMemberList option').filter(function() { return this.value === val; });

        if (selectedOption.length > 0 && transferredUsersList.length > 0) {
            let memId = selectedOption.attr('data-id');
            let sType = selectedOption.attr('data-type');
            let d = transferredUsersList.find(m => m.id == memId && m.source_type == sType);
            
            if (d) {
                // Personal Details Fill
                if (sType === 'employee') {
                    if (d.name || d.full_name) $('#f_name').val(d.name || d.full_name);
                    if (d.mobile_no || d.phone) $('#f_mobile').val(d.mobile_no || d.phone);
                    if (d.pan_no || d.pan) $('#f_pan').val(d.pan_no || d.pan);
                    if (d.aadhar_no || d.aadhar) $('#f_aadhar').val(d.aadhar_no || d.aadhar);
                    if (d.joining_date) $('#f_doj').val(d.joining_date);
                } else {
                    if (d.member_name) $('#f_name').val(d.member_name);
                    if (d.mobile) $('#f_mobile').val(d.mobile);
                    if (d.pan_number) $('#f_pan').val(d.pan_number);
                    if (d.aadhar_number) $('#f_aadhar').val(d.aadhar_number);
                    if (d.doj) $('#f_doj').val(d.doj);
                }
                
                // Other fields
                if (d.email) $('#f_email').val(d.email);
                if (d.gender) $(`input[name="gender"][value="${d.gender}"]`).prop('checked', true);
                if (d.marital_status) $(`input[name="marital_status"][value="${d.marital_status}"]`).prop('checked', true);
            }
        }
    });



    // Fetch Global Context
    $.ajax({
        url: '/api/v1/context',
        type: 'GET',
        async: false,
        success: function(res) {
            sysContext = res;
            isMasterAdmin = sysContext.is_god || sysContext.role_level === 'ceo';
            isUserAdmin = isMasterAdmin || ['admin', 'director', 'branch_manager'].includes(sysContext.role_level);
        }
    });

    // ==========================================
    // 🔥 SELECT2 AJAX INITIALIZATIONS 🔥
    // ==========================================
    $('#f_company').select2({
        ajax: {
            url: '/api/v1/members/search-companies',
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) {
                return { results: $.map(data.data, function(item) { return { text: item.company_name, id: item.id }; }) };
            }
        },
        minimumInputLength: 3,
        placeholder: 'Type 3 letters to search...',
        dropdownParent: $('#memberModal')
    }).on('select2:select', function(e) {
        let compId = e.params.data.id;
        $('#f_branch').empty().append(new Option('Head Office', 'HO_' + compId, true, true)).trigger('change');
        $('#f_department').empty().trigger('change');
        $('#designation').empty();

        if (mode === 'add' && !window.isEditLoading) {
            $.ajax({
                url: '/api/v1/members/next-id?company_id=' + compId,
                type: 'GET',
                success: function(response) { $('#f_member_id').val(response.next_id); }
            });
        }
    });

    $('#f_branch').select2({
        ajax: {
            url: '/api/v1/members/search-branches',
            dataType: 'json',
            delay: 250,
            data: function(params) { 
                return { 
                    q: params.term, 
                    company_id: $('#f_company').val() // Dynamically fetch current company
                }; 
            },
            processResults: function(data) {
                let results = $.map(data.data, function(item) { return { text: item.branch_name, id: item.id }; });
                let compId = $('#f_company').val();
                if(compId) results.unshift({ id: 'HO_' + compId, text: 'Head Office' });
                return { results: results };
            }
        },
        minimumInputLength: 3,
        placeholder: 'Type 3 letters to search...',
        dropdownParent: $('#memberModal')
    }).on('select2:select', function(e) {
        $('#f_department').empty().trigger('change');
    });

    $('#f_department').select2({
        ajax: {
            url: '/api/v1/members/search-departments',
            dataType: 'json',
            delay: 250,
            data: function(params) { 
                return { 
                    q: params.term, 
                    company_id: $('#f_company').val(), 
                    branch_id: $('#f_branch').val() 
                }; 
            },
            processResults: function(data) {
                return { results: $.map(data.data, function(item) { return { text: item.department_name, id: item.id }; }) };
            }
        },
        minimumInputLength: 3,
        placeholder: 'Search Associate Depts...',
        dropdownParent: $('#memberModal')
    }).on('select2:select', function(e) {
        let sponsorVal = $('#f_sponsor_id').val();
        if(!sponsorVal && isUserAdmin) {
            $('#f_sponsor_id').empty().append(new Option('SYSTEM ROOT', 'SYSTEM ROOT', true, true)).trigger('change');
            $('#f_sponsor_name').val('SYSTEM ROOT');
            loadDesignationsBySponsor('SYSTEM ROOT');
        } else {
            loadDesignationsBySponsor(sponsorVal);
        }
    });

    $('#f_sponsor_id').select2({
        ajax: {
            url: '/api/v1/members/search-sponsor',
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) {
                // Ensure id is the member_id
                return { results: $.map(data.data, function(item) { 
                    return { text: item.member_id + ' - ' + item.member_name, id: item.member_id, name: item.member_name }; 
                }) };
            }
        },
        minimumInputLength: 3,
        placeholder: 'Search by Name or ID...',
        dropdownParent: $('#memberModal'),
        tags: true
    }).on('select2:select', function(e) {
        let sponsorName = e.params.data.name || 'Unknown';
        let sponsorId = e.params.data.id;
        if (sponsorId.includes(' - ')) sponsorId = sponsorId.split(' - ')[0];
        $('#f_sponsor_name').val(sponsorName);
        loadDesignationsBySponsor(sponsorId);
    }).on('select2:clear', function(e) {
        $('#f_sponsor_name').val('');
        loadDesignationsBySponsor(null);
    });

    function loadDesignationsBySponsor(sponsorId = null, overrideDeptId = null) {
        let deptId = overrideDeptId || $('#f_department').val(); 
        if (!deptId) return;
        
        $.ajax({
            url: '/api/v1/members/available-designations',
            type: 'GET',
            data: { sponsor_id: sponsorId, department_id: deptId },
            success: function(res) {
                let options = '<option value="">-- Select Designation --</option>';
                res.data.forEach(function(item) { 
                    options += `<option value="${item.designation_name}">${item.designation_name}</option>`; 
                });
                $('#designation').html(options); 
            }
        });
    }

   // Helper function for fetching ID
    function fetchNextMemberId(compId) {
        if (mode === 'add' && !window.isEditLoading && compId) {
            $.ajax({
                url: '/api/v1/members/next-id?company_id=' + compId,
                type: 'GET',
                success: function(response) { $('#f_member_id').val(response.next_id); }
            });
        }
    }

    function setLockedOption(selector, url, id, fallbackText) {
        if(!id) return;
        if(id.toString().startsWith('HO_')) {
            $(selector).empty().append(new Option('Head Office', id, true, true)).trigger('change').prop('disabled', true);
            return;
        }
        $.get(url + '/' + id, function(res) {
            let text = res.data.company_name || res.data.branch_name || res.data.department_name || fallbackText;
            $(selector).empty().append(new Option(text, id, true, true)).trigger('change').prop('disabled', true);
        }).fail(function() {
            $(selector).empty().append(new Option(fallbackText, id, true, true)).trigger('change').prop('disabled', true);
        });
    }

    function applyRoleUI() {
        if (!sysContext) return;
        $('#f_company, #f_branch, #f_department, #f_sponsor_id').prop('disabled', false);
        $('#manual_series_div').hide();
        if(isMasterAdmin) $('#manual_series_div').show();

        if (sysContext.is_member || sysContext.role_level === 'member') {
            setLockedOption('#f_company', '/api/v1/companies', sysContext.company_id, 'Your Company');
            let branchVal = sysContext.branch_id || 'HO_' + sysContext.company_id;
            setLockedOption('#f_branch', '/api/v1/branches', branchVal, 'Your Branch');
            setLockedOption('#f_department', '/api/v1/departments', sysContext.department_id, 'Your Dept');
            
            // 🔥 NAYA: ID Auto-generate call yahan karein
            fetchNextMemberId(sysContext.company_id);
            
            if(sysContext.profile_id) {
                $.get('/api/v1/members/search-sponsor', { q: sysContext.profile_id }, function(res) {
                    if(res.data && res.data.length > 0) {
                        let me = res.data[0];
                        let optionText = me.member_id + ' - ' + me.member_name;
                        $('#f_sponsor_id').empty().append(new Option(optionText, me.member_id, true, true)).trigger('change').prop('disabled', true);
                        $('#f_sponsor_name').val(me.member_name).prop('readonly', true);
                        loadDesignationsBySponsor(me.member_id, sysContext.department_id);
                    }
                });
            }
        }
        else if (sysContext.is_employee && sysContext.branch_id) {
            setLockedOption('#f_company', '/api/v1/companies', sysContext.company_id, 'Company');
            setLockedOption('#f_branch', '/api/v1/branches', sysContext.branch_id, 'Branch');
            // 🔥 NAYA: ID Auto-generate call yahan karein
            fetchNextMemberId(sysContext.company_id);
        } 
        else if (sysContext.is_director) {
            setLockedOption('#f_company', '/api/v1/companies', sysContext.company_id, 'Company');
            fetchNextMemberId(sysContext.company_id);
        }
    }

    // Modal Logic
    window.openModal = function(type, id = null) {
        mode = type;
        
        // CRASH FIX FOR DIRECTORY PAGE
        if ($('#memberForm').length === 0) {
            alert('Please go to the main Member Details page to register or edit members.');
            return;
        }

        $('#memberForm')[0].reset();
        $('#edit_id').val('');
        $('#form_method').val('POST');
        
        $('#f_company, #f_branch, #f_department, #f_sponsor_id').empty().trigger('change');
        $('#f_sponsor_name').prop('readonly', true).addClass('bg-light').val('');
        $('#modalTitle').text(type === 'add' ? 'Register Member' : 'Edit Member');
        $('.file-preview-wrapper').hide().find('.preview-content').empty();
        $('.nav-tabs button:first').tab('show');

        $('#is_transferred').prop('checked', false).trigger('change');
        window.isEditLoading = false;

        if (type === 'add') {
            $('.password-edit-div').hide();
            $('.password-gen-div').show();
            $('#bank-container').empty();
            bankIndex = 0;
            appendBankRow();
            applyRoleUI();
            loadDesignationsBySponsor(null); 
        } else {
            $('.password-edit-div').show();
            $('.password-gen-div').hide();
            $('#manual_series_div').hide();

            $.get({
                url: `/api/v1/members/${id}`,
                success: function(res) {
                    window.isEditLoading = true;
                    let d = res.data;
                    $('#edit_id').val(d.id);
                    $('#form_method').val('PUT');
                    
                    if (d.company_id && d.company) $('#f_company').empty().append(new Option(d.company.company_name, d.company_id, true, true)).trigger('change');
                    if (d.branch_id === null) $('#f_branch').empty().append(new Option('Head Office', 'HO_' + d.company_id, true, true)).trigger('change');
                    else if (d.branch) $('#f_branch').empty().append(new Option(d.branch.branch_name, d.branch_id, true, true)).trigger('change');
                    
                    if (d.department) $('#f_department').empty().append(new Option(d.department.department_name, d.department_id, true, true)).trigger('change');
                    if (d.sponsor_id) {
                        $('#f_sponsor_id').empty().append(new Option(d.sponsor_id, d.sponsor_id, true, true)).trigger('change');
                        $('#f_sponsor_name').val(d.sponsor_name);
                        loadDesignationsBySponsor(d.sponsor_id);
                    }

                    // Populate Select designation safely
                    setTimeout(() => { if (d.designation) $('#designation').val(d.designation); }, 500);

                    $('#bank-container').empty();
                    bankIndex = 0;
                    if (d.banks && d.banks.length > 0) d.banks.forEach((bank, i) => { appendBankRow(bank, i); bankIndex = i + 1; });
                    else appendBankRow();

                   Object.keys(d).forEach(key => {
                        let input = $(`#memberForm [name="${key}"]`);
                        if (input.length) {
                            if (input.attr('type') === 'file') {
                                // 🔥 NAYA: Document Preview Logic 🔥
                                if (d[key]) {
                                    let wrapper = input.next('.file-preview-wrapper');
                                    let content = wrapper.find('.preview-content');
                                    let fileUrl = d[key].startsWith('http') ? d[key] : '/' + d[key];
                                    let ext = d[key].split('.').pop().toLowerCase();
                                    
                                    // Agar image hai toh photo dikhao, varna PDF link
                                    if (['jpg', 'jpeg', 'png', 'webp', 'bmp', 'gif'].includes(ext)) {
                                        content.html(`<a href="${fileUrl}" target="_blank"><img src="${fileUrl}" style="max-height:80px; border-radius:6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></a>`);
                                    } else {
                                        content.html(`<a href="${fileUrl}" target="_blank" class="p-2 small fw-bold text-primary text-decoration-none"><i class="fas fa-file-pdf me-2 text-danger"></i>View Uploaded File</a>`);
                                    }
                                    wrapper.slideDown();
                                }
                            } else if (input.attr('type') !== 'radio') {
                                if (typeof d[key] === 'object' && d[key] !== null) return;
                                input.val(d[key]);
                            }
                        }
                    });

                    if (d.gender) $(`input[name="gender"][value="${d.gender}"]`).prop('checked', true);
                    if (d.marital_status) $(`input[name="marital_status"][value="${d.marital_status}"]`).prop('checked', true);

                    applyRoleUI();
                    setTimeout(() => { window.isEditLoading = false; }, 800);
                }
            });
        }
        $('#memberModal').modal('show');
    };

    $(document).on('click', '.edit-btn', function() { openModal('edit', $(this).data('id')); });

  // Rest of UI logic (DataTable init, Submit logic)
    let p = (sysContext && sysContext.permissions) ? sysContext.permissions : (window.userPerms || []);
    // 🔥 ROOT CAUSE FIX: Kewal mem_dir_view check karega
    let hasView = isMasterAdmin || p.includes('mem_dir_view');

    // 🔥 Naya Permission Check for Buttons
let hasExport = isMasterAdmin || p.includes('member_export') || p.includes('mem_dir_export');
let hasPrint = isMasterAdmin || p.includes('member_print') || p.includes('mem_dir_print');

if (hasExport || hasPrint) {
    $('#exportPrintDiv').removeClass('d-none').addClass('d-flex');
    
    // Sirf wahi dikhega jiski permission hai, warna hide
    if (!hasExport) {
        $('#btnExportExcel').hide();
        $('#mobileExcelBtn').hide(); // Mobile wala bhi hide
    } else {
        $('#mobileExcelBtn').show();
    }
    
    if (!hasPrint) {
        $('#btnPrintMembers').hide();
    }
}

    let table = null;

    if (!hasView) {
        $('.card.d-none.d-md-block').hide(); 
        $('#mobileCardsContainer').hide();   
        $('#mobileSearch').hide();           
        $('#mobileExcelBtn').hide();         
    } else {
        // 🔥 ROOT CAUSE FIX: Table ki ID '#allTimeMemberTable' hogi
        table = $('#allTimeMemberTable').DataTable({
            processing: true,
            serverSide: true,
            // 🔥 ROOT CAUSE FIX: API directory wali hit hogi
            ajax: { url: '/api/v1/members/all-time-records' },
            dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
            buttons: [],
            order: [[1, 'desc']],
            columns: [
                {
                    data: 'id', orderable: false, searchable: false, className: 'text-center',
                    render: function(data) {
                        let hasDelete = isMasterAdmin || p.includes('mem_dir_delete'); 
                        if(!hasDelete) return `<i class="fas fa-lock text-muted small"></i>`; 
                        return `<input type="checkbox" class="form-check-input member-checkbox" value="${data}">`;
                    }
                },
                { data: 'member_id', render: d => `<span class="fw-bold text-primary">${d}</span>` },
                {
                    data: 'branch_id',
                    render: (d, t, row) => {
                        let compName = row.company ? row.company.company_name : 'Master Company';
                        let bName = (row.branch_id === null) ? 'Head Office' : (row.branch ? row.branch.branch_name : 'N/A');
                        return `<div class="small fw-bold text-primary"><i class="fas fa-building me-1"></i> ${compName}</div><div class="small text-muted"><i class="fas fa-map-marker-alt text-danger me-1"></i> ${bName}</div>`;
                    }
                },
                { data: 'member_name' },
                { data: 'sponsor_id', render: d => d ? d : 'N/A' },
                { data: 'doj' },
                {
                    data: 'status',
                    render: function(d) {
                        if (d === 'pending') return `<span class="badge bg-warning text-dark shadow-sm">Pending</span>`;
                        if (d === 'active') return `<span class="badge bg-success shadow-sm">Active</span>`;
                        return `<span class="badge bg-danger shadow-sm">Inactive</span>`;
                    }
                },
                {
                    data: 'id', orderable: false, className: 'text-end text-nowrap',
                    render: function(d, type, row) {
                        // 🔥 FIX: Saare slugs mem_dir_* ho gaye
                        let hasEdit = isMasterAdmin || p.includes('mem_dir_edit');
                        let hasAppr = isMasterAdmin || p.includes('mem_dir_appr');
                        let hasRej  = isMasterAdmin || p.includes('mem_dir_rej');

                        let btns = `<button type="button" class="btn btn-sm btn-light text-info me-1 view-btn" data-id="${d}"><i class="fas fa-eye"></i></button>`;
                        if (hasEdit) btns += `<button type="button" class="btn btn-sm btn-light text-primary me-1 edit-btn" data-id="${d}"><i class="fas fa-edit"></i></button>`;
                        if (row.status === 'pending') {
                            if (hasAppr) btns += `<button type="button" class="btn btn-sm btn-success me-1 appr-btn" data-id="${d}" title="Approve"><i class="fas fa-check"></i></button>`;
                            if (hasRej) btns += `<button type="button" class="btn btn-sm btn-warning me-1 rej-btn" data-id="${d}" title="Reject"><i class="fas fa-times"></i></button>`;
                        }
                        return `<div class="d-flex justify-content-end flex-nowrap">${btns}</div>`;
                    }
                }
            ],
           // Isko apne DataTable init ke sabse end me replace karein:
            drawCallback: function(settings) {
                let api = this.api();
                let rows = api.rows({page:'current'}).data();
                let mobileHtml = '';
                
                if(rows.length === 0) {
                    $('#mobileCardsContainer').html('<div class="alert alert-light text-center border mt-3 shadow-sm rounded">No records found</div>');
                    return;
                }

                rows.each(function(row) {
                    let compName = (row.branch_id === null) ? (row.company ? row.company.company_name : 'Master Company') : (row.branch ? row.branch.company.company_name : 'N/A');
                    let bName = (row.branch_id === null) ? 'Head Office' : (row.branch ? row.branch.branch_name : 'N/A');
                    
                    let statusBadge = '';
                    if (row.status === 'pending') statusBadge = `<span class="badge bg-warning text-dark px-2 py-1">Pending</span>`;
                    else if (row.status === 'active') statusBadge = `<span class="badge bg-success px-2 py-1">Active</span>`;
                    else statusBadge = `<span class="badge bg-danger px-2 py-1">Inactive</span>`;

                    // Universal RBAC for both pages
                    let p = (sysContext && sysContext.permissions) ? sysContext.permissions : (window.userPerms || []);
                    let hasEdit = isMasterAdmin || p.includes('member_edit') || p.includes('mem_dir_edit');
                    let hasDelete = isMasterAdmin || p.includes('member_delete') || p.includes('mem_dir_delete');

                    let actionBtns = `<button class="btn btn-sm btn-light text-info view-btn border me-1" data-id="${row.id}"><i class="fas fa-eye"></i> View</button>`;
                    if(hasEdit) actionBtns += `<button class="btn btn-sm btn-light text-primary edit-btn border" data-id="${row.id}"><i class="fas fa-edit"></i> Edit</button>`;

                    // 🔥 YAHAN CHECKBOX ADD KIYA GAYA HAI 🔥
                    let checkHtml = hasDelete ? `<input type="checkbox" class="form-check-input member-checkbox me-2" value="${row.id}" style="transform: scale(1.2);">` : `<i class="fas fa-lock text-muted small me-2"></i>`;

                    mobileHtml += `
                        <div class="mobile-item">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <div class="fw-bold text-primary fs-6 d-flex align-items-center">
                                    ${checkHtml} 
                                    ${row.member_id}
                                </div>
                                <div>${statusBadge}</div>
                            </div>
                            <div class="fw-bold mb-2 text-dark">${row.member_name}</div>
                            <div class="small text-muted mb-1"><i class="fas fa-building me-2 text-warning"></i> ${compName} - ${bName}</div>
                            <div class="small text-muted mb-1"><i class="fas fa-user-tie me-2 text-primary"></i> ${row.sponsor_id || 'N/A'}</div>
                            <div class="small text-muted mb-2"><i class="fas fa-phone me-2 text-success"></i> ${row.mobile || 'N/A'}</div>
                            <div class="d-flex justify-content-end mt-2 pt-2">
                                ${actionBtns}
                            </div>
                        </div>
                    `;
                });
                $('#mobileCardsContainer').html(mobileHtml);
            }
        });
    }

    $('#memberForm').submit(function(e) {
        e.preventDefault();
        $('#f_company, #f_branch, #f_department, #f_sponsor_id').prop('disabled', false);
        let formData = new FormData(this);
        let id = $('#edit_id').val();
        let url = mode === 'add' ? '/api/v1/members' : `/api/v1/members/${id}`;
        if (mode === 'edit') formData.append('_method', 'PUT');

        $.ajax({
            url: url, type: 'POST', data: formData, processData: false, contentType: false,
            success: function(res) {
                alert(res.message);
                $('#memberModal').modal('hide');
                if(table) table.ajax.reload(null, false);
                applyRoleUI();
            },
            error: function() { applyRoleUI(); }
        });
    });

    $('#f_member_id, #f_manual_series').on('input change keyup', function() {
        if (window.isEditLoading) return;
        let memIdVal = $('#f_member_id').val().trim().toUpperCase();
        let manualVal = $('#f_manual_series').val();
        let isRoot = false; let rootSeries = '001';

        if (manualVal === '1' || memIdVal.endsWith('/001')) { isRoot = true; rootSeries = '001'; }
        else if (manualVal === '2' || memIdVal.endsWith('/002')) { isRoot = true; rootSeries = '002'; }
        else if (manualVal === '3' || memIdVal.endsWith('/003')) { isRoot = true; rootSeries = '003'; }

        if (isRoot) {
            let prefix = 'CMP';
            if (memIdVal.includes('-M/')) prefix = memIdVal.split('-M/')[0];
            let defaultSponsor = `${prefix}-M/${rootSeries}`;
            $('#f_sponsor_id').empty().append(new Option(defaultSponsor, defaultSponsor, true, true)).trigger('change');
            $('#f_sponsor_name').val('SYSTEM ROOT');
            loadDesignationsBySponsor(defaultSponsor);
        }
    });

    function generatePassword() {
        let fullName = $('#f_name').val().trim();
        let aadhar = $('#f_aadhar').val().replace(/\D/g, '');
        if (fullName.length < 1 || aadhar.length < 4) { $('#mem_pass_gen').val(''); return; }
        let firstNamePart = fullName.split(' ')[0].substring(0, 3).toLowerCase();
        let formattedName = firstNamePart.charAt(0).toUpperCase() + firstNamePart.slice(1);
        let aadharLast4 = aadhar.slice(-4);
        $('#mem_pass_gen').val(formattedName + '@' + aadharLast4);
    }
    $('#f_name, #f_aadhar').on('keyup change', generatePassword);

    $(document).on('click', '.view-btn', function() {
        $.get({
            url: `/api/v1/members/${$(this).data('id')}`,
            success: function(res) {
                let d = res.data;
                $('#v_mem_id').text(d.member_id || 'N/A');
                $('#v_password').text(d.password || 'N/A');

                let branchText = 'N/A';
                if (d.branch_id === null) {
                    let compName = d.company ? d.company.company_name : 'Master Company';
                    branchText = compName + ' - Head Office';
                } else if (d.branch) {
                    let compName = d.branch.company ? d.branch.company.company_name : 'Master Company';
                    branchText = compName + ' - ' + d.branch.branch_name;
                }
                $('#v_branch').text(branchText);

                $('#v_sponsor').text(d.sponsor_name ? `${d.sponsor_name} (${d.sponsor_id})` : (d.sponsor_id || 'N/A'));
                $('#v_name').text(d.member_name || 'N/A');
                $('#v_designation').text(d.designation || 'N/A');
                $('#v_grade').text(d.grade || 'N/A');
                $('#v_mem_status').text(d.mem_status || 'On Board');
                $('#v_mobile').text(d.mobile || 'N/A');
                $('#v_doj').text(d.doj || 'N/A');
                $('#v_aadhar').text(d.aadhar_number || 'N/A');
                $('#v_pan').text(d.pan_number || 'N/A');
                $('#v_nom_name').text(d.nominee_name || 'N/A');
                $('#v_nom_relation').text(d.nominee_relation || 'N/A');
                $('#v_nom_mobile').text(d.nominee_mobile || 'N/A');

                $('#viewModal').modal('show');
            }
        });
    });

    $('input[type="file"]').each(function() {
        $(this).after(`<div class="file-preview-wrapper"><button type="button" class="btn btn-danger remove-preview-btn"><i class="fas fa-times"></i></button><div class="preview-content text-center"></div></div>`);
    });

    $(document).on('change', 'input[type="file"]', function() {
        let file = this.files[0];
        let wrapper = $(this).next('.file-preview-wrapper');
        let content = wrapper.find('.preview-content');
        if (file) {
            if (file.type.startsWith('image/')) {
                let reader = new FileReader();
                reader.onload = e => {
                    content.html(`<img src="${e.target.result}" style="max-height:80px; border-radius:6px;">`);
                    wrapper.slideDown();
                }
                reader.readAsDataURL(file);
            } else {
                content.html(`<div class="p-2 small fw-bold text-dark"><i class="fas fa-file-alt text-primary me-2"></i>${file.name}</div>`);
                wrapper.slideDown();
            }
        }
    });

    $(document).on('click', '.remove-preview-btn', function() {
        $(this).closest('.file-preview-wrapper').prev('input[type="file"]').val('');
        $(this).closest('.file-preview-wrapper').slideUp();
    });

    let bankIndex = 0;
    function appendBankRow(bank = {}, index = null) {
        let idx = index !== null ? index : bankIndex++;
        let showBtn = idx > 0 ? '' : 'style="display:none;"'; 
        let row = `
            <div class="bank-row border p-3 mb-3 rounded bg-white shadow-sm position-relative">
                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 remove-bank-btn" ${showBtn} title="Remove Bank"><i class="fas fa-times"></i></button>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label text-secondary small">Account Holder Name</label><input type="text" name="banks[${idx}][account_name]" class="form-control" value="${bank.account_name || ''}"></div>
                    <div class="col-md-4"><label class="form-label text-secondary small">Bank A/c No.</label><input type="text" name="banks[${idx}][account_no]" class="form-control" value="${bank.account_no || ''}"></div>
                    <div class="col-md-4"><label class="form-label text-secondary small">Select Account Type</label>
                        <select class="form-select" name="banks[${idx}][account_type]">
                            <option value="">-- Select Type --</option>
                            <option value="saving" ${bank.account_type === 'saving' ? 'selected' : ''}>Saving</option>
                            <option value="current" ${bank.account_type === 'current' ? 'selected' : ''}>Current</option>
                            <option value="cc" ${bank.account_type === 'cc' ? 'selected' : ''}>CC</option>
                            <option value="od" ${bank.account_type === 'od' ? 'selected' : ''}>OD</option>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label text-secondary small">Bank Name</label><input type="text" name="banks[${idx}][bank_name]" class="form-control" value="${bank.bank_name || ''}"></div>
                    <div class="col-md-4"><label class="form-label text-secondary small">Branch Name</label><input type="text" name="banks[${idx}][branch]" class="form-control" value="${bank.branch || ''}"></div>
                    <div class="col-md-4"><label class="form-label text-secondary small">IFSC Code</label><input type="text" name="banks[${idx}][ifsc_code]" class="form-control text-uppercase" value="${bank.ifsc_code || ''}"></div>
                </div>
            </div>
        `;
        $('#bank-container').append(row);
    }

    $('#addBankBtn').on('click', function() { appendBankRow(); });
    $(document).on('click', '.remove-bank-btn', function() { $(this).closest('.bank-row').remove(); });

$('#btnExportExcel').on('click', function() {
        let companyId = $('#hidden_company_id').val() || '';
        let branchId = $('#hidden_branch_id').val() || '';
        let selectedIds = [];
        $('.member-checkbox:checked').each(function() { selectedIds.push($(this).val()); });
        
        // 🔥 FIX: Ab ye har portal ke hisaab se dynamically route banayega
        let url = `/${currentPortal}/members/export-excel?company_id=${companyId}&branch_id=${branchId}`;
        if(selectedIds.length > 0) url += `&ids=${selectedIds.join(',')}`;
        
        window.location.href = url;
    });

    $('#btnPrintMembers').on('click', function() {
        let companyId = $('#hidden_company_id').val() || '';
        let branchId = $('#hidden_branch_id').val() || '';
        let selectedIds = [];
        $('.member-checkbox:checked').each(function() { selectedIds.push($(this).val()); });
        
        // 🔥 FIX: Dynamic Print Route
        let url = `/${currentPortal}/members/print?company_id=${companyId}&branch_id=${branchId}`;
        if(selectedIds.length > 0) url += `&ids=${selectedIds.join(',')}`;
        
        window.open(url, '_blank', 'width=900,height=800');
    });
// ==========================================
    // 🔥 MOBILE SEARCH BOX LOGIC 🔥
    // ==========================================
    $('#mobileSearch').on('keyup', function() {
        if (table) {
            table.search(this.value).draw(); // Isse DataTable aur Cards dono filter ho jayenge
        }
    });

    // ==========================================
    // 🔥 CHECKBOX, COUNT & BULK DELETE LOGIC 🔥
    // ==========================================
    let isAllDbSelected = false;
    let currentMobilePageLen = 10;

   function updateBulkActionUI() {
        let checkedCount = $('.member-checkbox:checked').length;
        let totalCheckboxes = $('.member-checkbox').length;
        
        let p = (sysContext && sysContext.permissions) ? sysContext.permissions : (window.userPerms || []);
        let hasDelete = isMasterAdmin || p.includes('member_delete') || p.includes('mem_dir_delete');

        if (checkedCount > 0 && hasDelete) {
            // 🔥 Desktop UI: d-none (mobile par hide), d-md-flex (desktop par show)
            $('#bulkActionDiv').attr('style', '').removeClass('d-none').addClass('d-none d-md-flex');
            $('#selectedCount').text(isAllDbSelected ? 'All DB' : checkedCount);

            // 🔥 Mobile Floating UI: d-flex (mobile par show), d-md-none (desktop par hide)
            $('#mobileBulkActionDiv').attr('style', '').removeClass('d-none').addClass('d-flex d-md-none');
            $('#mobileSelectedCount').text(isAllDbSelected ? 'All' : checkedCount);

            // Thead Master Checkbox handle
            if (totalCheckboxes > 0 && checkedCount === totalCheckboxes) {
                $('#selectAllCheckbox').prop('checked', true);
            } else {
                $('#selectAllCheckbox').prop('checked', false);
            }
        } else {
            // Hide Both UIs safely
            $('#bulkActionDiv').attr('style', '').removeClass('d-md-flex').addClass('d-none');
            $('#mobileBulkActionDiv').attr('style', '').removeClass('d-flex d-md-none').addClass('d-none');
            $('#selectAllCheckbox').prop('checked', false);
            isAllDbSelected = false;
        }
    }

    $(document).on('change', '#selectAllCheckbox', function() {
        let isChecked = $(this).prop('checked');
        $('.member-checkbox').prop('checked', isChecked);
        isAllDbSelected = false;
        updateBulkActionUI();
    });

    $(document).on('change', '.member-checkbox', function() {
        isAllDbSelected = false;
        updateBulkActionUI();
    });

    $(document).on('click', '#selectAllDbBtn, #mobileSelectAllDbBtn', function() {
        isAllDbSelected = true;
        $('.member-checkbox').prop('checked', true); 
        $('#selectAllCheckbox').prop('checked', true);
        updateBulkActionUI();
        let totalDbRecords = table ? table.page.info().recordsDisplay : 0; 
        alert('All ' + totalDbRecords + ' records have been selected from the database.');
    });

    // ==========================================
    // 🔥 MOBILE LOAD MORE LOGIC 🔥
    // ==========================================
    $('#mobileLoadMoreBtn').on('click', function() {
        currentMobilePageLen += 20;
        if(table) table.page.len(currentMobilePageLen).draw(false);
    });

    if(table) {
        table.on('draw', function () {
            isAllDbSelected = false;
            $('#selectAllCheckbox').prop('checked', false);
            updateBulkActionUI();
            
            // Show/Hide Load More Button
            let info = table.page.info();
            if(info.recordsDisplay > info.length && $(window).width() < 768) {
                $('#mobileLoadMoreDiv').show();
            } else {
                $('#mobileLoadMoreDiv').hide();
            }
        });
    }
    
});
</script>
@endpush
