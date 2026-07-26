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

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: #4b5563;
        }

        .file-preview-wrapper {
            display: none;
            position: relative;
            margin-top: 10px;
            padding: 12px;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
            width: fit-content;
            transition: all 0.3s ease;
        }

        .remove-preview-btn {
            position: absolute;
            top: -12px;
            right: -12px;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.3);
        }

        .created-badge {
            background-color: #e0e7ff;
            color: #2b6cb0;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #c3dafe;
        }

        /* 🔥 Navbar Hover Bug Fix: DataTables element ko lower z-index par rakhna */
        .dataTables_wrapper,
        .dataTables_filter {
            position: relative;
            z-index: 1;
        }
    </style>

    @php
        // 🔥 SERVER SIDE PERMISSION LOGIC 🔥
        $currentUser = auth()->user() ?? auth('sanctum')->user();
        $perms = [];
        $isGod = false;
        $globalContext = null;

        if ($currentUser) {
            $controller = new \App\Http\Controllers\Controller();
            $perms = $controller::getLiveActivePermissions($currentUser);
            $globalContext = $controller->getGlobalContext();
            $isGod = $globalContext->is_god ?? false;
        }

        // Using original export slug for button visibility
        $canExport = $isGod || in_array('customer_print', $perms) || in_array('customer_export', $perms);
    @endphp

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Customer Directory (All Data)</h4>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span id="bulkActions" style="display: none;">
                    <button class="btn btn-dark px-3 py-2 shadow-sm" id="btnSelectAll">Select All</button>
                    <button class="btn btn-danger px-3 py-2 shadow-sm secured-item" data-permission="customer_delete"
                        onclick="bulkDelete()">Delete Selected</button>
                </span>

                <button type="button" class="btn text-white px-3 py-2 shadow-sm secured-item" id="addCustomerBtn"
                    data-permission="customer_add" style="background-color: var(--brand-primary);"
                    onclick="openModal('add')">
                    <i class="fas fa-plus me-1"></i> Register Customer
                </button>
            </div>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm w-100" placeholder="Search Customer...">
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="customerTable" class="table table-hover table-custom w-100">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>CUST ID</th>
                                <th>Branch Info</th>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>Status</th>
                                <th>Created By</th>
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

    <!-- MODALS SECTION STARTS -->
    <div class="modal fade" id="customerModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold" id="modalTitle" style="color: var(--sidebar-bg);"><i
                            class="fas fa-user-plus me-2 text-primary"></i> Register Customer</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <form id="customerForm" enctype="multipart/form-data">
                        <input type="hidden" id="edit_id">

                        <ul class="nav nav-tabs px-4 pt-3 bg-light" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#tab-personal"
                                    type="button"><i class="fas fa-user me-1"></i> Personal Info</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#tab-bank"
                                    type="button"><i class="fas fa-university me-1"></i> Bank Details</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#tab-nominee"
                                    type="button"><i class="fas fa-user-shield me-1"></i> Nominee Info</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#tab-docs"
                                    type="button"><i class="fas fa-file-upload me-1"></i> Documents</button>
                            </li>
                        </ul>

                        <div class="tab-content p-4">
                            <!-- PERSONAL TAB -->
                            <div class="tab-pane fade show active" id="tab-personal">
                                <div class="row g-3">
                                    <div class="col-12 mb-3 pb-3 border-bottom bg-light p-3 rounded"
                                        id="old_customer_block">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="is_old_customer"
                                                name="is_old_customer" value="true">
                                            <label class="form-check-label fw-bold text-primary" for="is_old_customer">Is it
                                                an Old Customer?</label>
                                        </div>
                                        <div id="old_customer_sec" style="display:none;" class="row g-2">
                                            <div class="col-md-5">
                                                <select class="form-select fw-bold" id="old_search_company">
                                                    <option value="">-- All Companies --</option>
                                                </select>
                                            </div>
                                            <div class="col-md-7 position-relative">
                                                <input type="text" class="form-control" id="old_search_input"
                                                    list="oldCustomerList" placeholder="Search by Name/ID/Code..."
                                                    autocomplete="off">
                                                <datalist id="oldCustomerList"></datalist>
                                                <input type="hidden" name="old_customer_code" id="old_customer_code">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4" id="wrap_company">
                                        <label class="form-label text-secondary small">Select Company <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="f_company" list="companyList"
                                            placeholder="Search Company..." autocomplete="nope">
                                        <input type="hidden" name="company_id" id="hidden_company_id">
                                        <datalist id="companyList"></datalist>
                                    </div>

                                    <div class="col-md-4" id="wrap_branch">
                                        <label class="form-label text-secondary small">Select Branch <span
                                                class="text-info">(Leave blank for Head Office)</span></label>
                                        <input type="text" class="form-control" id="f_branch" list="branchList"
                                            placeholder="Search Branch..." autocomplete="nope">
                                        <input type="hidden" name="branch_id" id="branch_id_hidden">
                                        <datalist id="branchList"></datalist>
                                        <small id="branch_error" class="text-danger fw-bold mt-1"
                                            style="display: none;"></small>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Associate Member (Optional)</label>
                                        <input type="text" id="member_search_input" class="form-control"
                                            list="memberListOptions" placeholder="Search Member..." autocomplete="nope">
                                        <input type="hidden" name="member_id" id="f_member_id">
                                        <datalist id="memberListOptions"></datalist>
                                    </div>

                                    <div class="col-md-4 status-div" style="display: none;">
                                        <label class="form-label text-secondary small">Customer Status <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select fw-bold" name="status" id="f_status">
                                            <option value="active" class="text-success">Active</option>
                                            <option value="pending" class="text-warning">Pending</option>
                                            <option value="inactive" class="text-danger">Inactive</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Customer ID <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="customer_id"
                                            class="form-control fw-bold text-primary bg-light" id="f_cust_id" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Login Password <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="password"
                                            class="form-control fw-bold text-danger bg-light" id="f_password" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Joining Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="joining_date" class="form-control" id="f_joining"
                                            required>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Name in Full <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="customer_name" class="form-control" id="f_name"
                                            required autocomplete="off">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Father's Name</label>
                                        <input type="text" name="father_name" class="form-control" id="f_father">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Spouse's Name</label>
                                        <input type="text" name="spouse_name" class="form-control" id="f_spouse">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Mother's Name</label>
                                        <input type="text" name="mothers_name" class="form-control" id="f_mother">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Occupation</label>
                                        <input type="text" name="occupation" class="form-control" id="f_occupation">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Date of Birth</label>
                                        <input type="date" name="dob" class="form-control" id="f_dob">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small d-block">Gender</label>
                                        <div class="form-check form-check-inline mt-2">
                                            <input class="form-check-input" type="radio" name="gender" value="Male"
                                                id="gender_male">
                                            <label class="form-check-label" for="gender_male">Male</label>
                                        </div>
                                        <div class="form-check form-check-inline mt-2">
                                            <input class="form-check-input" type="radio" name="gender" value="Female"
                                                id="gender_female">
                                            <label class="form-check-label" for="gender_female">Female</label>
                                        </div>
                                        <div class="form-check form-check-inline mt-2">
                                            <input class="form-check-input" type="radio" name="gender" value="Others"
                                                id="gender_other">
                                            <label class="form-check-label" for="gender_other">Others</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small d-block">Marital Status</label>
                                        <div class="form-check form-check-inline mt-2">
                                            <input class="form-check-input marital-radio" type="radio"
                                                name="marital_status" value="Married" id="ms_married">
                                            <label class="form-check-label" for="ms_married">Married</label>
                                        </div>
                                        <div class="form-check form-check-inline mt-2">
                                            <input class="form-check-input marital-radio" type="radio"
                                                name="marital_status" value="Unmarried" id="ms_unmarried">
                                            <label class="form-check-label" for="ms_unmarried">Unmarried</label>
                                        </div>
                                    </div>

                                    <div class="col-md-4" id="wrap_anniversary" style="display: none;">
                                        <label class="form-label text-secondary small">Date of Anniversary</label>
                                        <input type="date" name="date_of_anniversary" class="form-control"
                                            id="f_anniversary">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nationality</label>
                                        <input type="text" name="nationality" class="form-control" value="Indian"
                                            readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Contact No. (+91) <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="customer_mobile" class="form-control" id="f_mobile"
                                            maxlength="10" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Alternate Mobile No.</label>
                                        <input type="text" name="alternate_mobile" class="form-control"
                                            id="f_alt_mobile" maxlength="10">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Email ID</label>
                                        <input type="email" name="customer_email" class="form-control" id="f_email">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Aadhar Card No.<span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="aadhar_number" class="form-control" id="f_aadhar"
                                            maxlength="12" placeholder="XXXX XXXX XXXX" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">PAN NO.</label>
                                        <input type="text" name="pan_number" class="form-control text-uppercase"
                                            id="f_pan" maxlength="10">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Native Place</label>
                                        <input type="text" name="native_place" class="form-control" id="f_native">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label text-secondary small">Communication Address</label>
                                        <input type="text" name="address" class="form-control" id="f_address">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-secondary small">City/Town/Village</label>
                                        <input type="text" name="city_town_village" class="form-control"
                                            id="f_city">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-secondary small">Pin Code</label>
                                        <input type="text" name="pincode" class="form-control" id="f_pincode"
                                            maxlength="6">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-secondary small">State</label>
                                        <input type="text" name="state" class="form-control" id="f_state">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-secondary small">District</label>
                                        <input type="text" name="district" class="form-control" id="f_district">
                                    </div>
                                </div>
                            </div>

                            <!-- BANK TAB -->
                            <div class="tab-pane fade" id="tab-bank">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Account Holder Name</label>
                                        <input type="text" name="account_name" class="form-control fw-medium"
                                            id="f_acc_name">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Bank A/c No.</label>
                                        <input type="text" name="account_no" class="form-control fw-medium"
                                            id="f_acc_no">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Select Account Type</label>
                                        <select class="form-select" name="account_type" id="f_acc_type">
                                            <option value="">-- Select Account Type --</option>
                                            <option value="saving">Saving Account</option>
                                            <option value="current">Current Account</option>
                                            <option value="cc">Cash Credit Account</option>
                                            <option value="od">Over Draft Account</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Bank Name</label>
                                        <input type="text" name="bank_name" class="form-control fw-medium"
                                            id="f_bank_name">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Branch Name</label>
                                        <input type="text" name="branch" class="form-control fw-medium"
                                            id="f_bank_branch">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">IFSC Code</label>
                                        <input type="text" name="ifsc_code"
                                            class="form-control fw-medium text-uppercase" id="f_ifsc">
                                    </div>
                                </div>
                            </div>

                            <!-- NOMINEE TAB -->
                            <div class="tab-pane fade" id="tab-nominee">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label text-secondary small">Nominee Name</label>
                                        <input type="text" name="nominee_name" class="form-control" id="f_nom_name">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-secondary small">Relation</label>
                                        <input type="text" name="nominee_relation" class="form-control"
                                            id="f_nom_relation" placeholder="e.g. Father, Wife">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-secondary small">S/o, D/o, W/o</label>
                                        <input type="text" name="nominee_so_do_wo" class="form-control"
                                            id="f_nom_sodowo">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-secondary small">Date of Birth</label>
                                        <input type="date" name="nominee_dob" class="form-control" id="f_nom_dob">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee Mobile No</label>
                                        <input type="text" name="nominee_mobile" class="form-control"
                                            id="f_nom_mobile" maxlength="10">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Alternate Mobile No</label>
                                        <input type="text" name="nominee_alternate_mobile" class="form-control"
                                            id="f_nom_alt_mobile" maxlength="10">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee Email Id</label>
                                        <input type="email" name="nominee_email" class="form-control"
                                            id="f_nom_email">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee Aadhar</label>
                                        <input type="text" name="nominee_aadhar" class="form-control"
                                            id="f_nom_aadhar" maxlength="12">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee PAN</label>
                                        <input type="text" name="nominee_pan" class="form-control text-uppercase"
                                            id="f_nom_pan" maxlength="10">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee Address</label>
                                        <input type="text" name="nominee_address" class="form-control"
                                            id="f_nom_address">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee PIN Code</label>
                                        <input type="text" name="nominee_pincode" class="form-control"
                                            id="f_nom_pincode" maxlength="6">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee State</label>
                                        <input type="text" name="nominee_state" class="form-control"
                                            id="f_nom_state">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee District</label>
                                        <input type="text" name="nominee_district" class="form-control"
                                            id="f_nom_district">
                                    </div>
                                </div>
                            </div>

                            <!-- DOCUMENTS TAB -->
                            <div class="tab-pane fade" id="tab-docs">
                                <h6 class="text-primary fw-bold border-bottom pb-2 mb-3"><i
                                        class="fas fa-user-circle me-1"></i> Customer Documents</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4"><label class="form-label text-secondary small">Upload Aadhar
                                            Card (.pdf)</label><input type="file" name="aadharcard"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Upload PAN Card
                                            (.pdf)</label><input type="file" name="pancard"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Upload Bank
                                            Passbook (.pdf)</label><input type="file" name="bank_passbook_pdf"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Upload Driving
                                            License (.pdf)</label><input type="file" name="drivinglicense"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Upload Passport
                                            (.pdf)</label><input type="file" name="passport"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Passport Size
                                            Photo (Image)</label><input type="file" name="passport_photo"
                                            class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">10th Marksheet
                                            (.pdf)</label><input type="file" name="tenthmarksheet"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">12th Marksheet
                                            (.pdf)</label><input type="file" name="twelvethmarksheet"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Graduation
                                            Certificate (.pdf)</label><input type="file" name="graduationcertificate"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">PG Certificate
                                            (.pdf)</label><input type="file" name="pgcertificate"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Other Documents
                                            (.pdf)</label><input type="file" name="otherdoc"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                </div>

                                <h6 class="text-primary fw-bold border-bottom pb-2 mb-3"><i
                                        class="fas fa-user-shield me-1"></i> Nominee Documents</h6>
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Aadhar
                                            Card (.pdf)</label><input type="file" name="nom_aadharcard"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee PAN Card
                                            (.pdf)</label><input type="file" name="nom_pancard"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Bank
                                            Passbook (.pdf)</label><input type="file" name="nom_bankpassbook"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Driving
                                            License (.pdf)</label><input type="file" name="nom_drivinglicense"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Passport
                                            (.pdf)</label><input type="file" name="nom_passport"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Passport
                                            Photo (Img)</label><input type="file" name="nom_passport_photo"
                                            class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee 10th
                                            Marksheet (.pdf)</label><input type="file" name="nom_tenthmarksheet"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee 12th
                                            Marksheet (.pdf)</label><input type="file" name="nom_twelvethmarksheet"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee
                                            Graduation Cert (.pdf)</label><input type="file"
                                            name="nom_graduationcertificate" class="form-control form-control-sm"
                                            accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee PG
                                            Certificate (.pdf)</label><input type="file" name="nom_pgcertificate"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Other
                                            Docs (.pdf)</label><input type="file" name="nom_otherdoc"
                                            class="form-control form-control-sm" accept=".pdf"></div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-light border-top-0">
                            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn text-white px-5 shadow-sm fw-medium"
                                style="background-color: var(--brand-primary);" id="saveBtn">Save Customer
                                Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- VIEW MODAL -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i
                            class="fas fa-eye me-2 text-info"></i> Customer Details</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6 secure-view-element">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold text-primary mb-3">Login Credentials</h6>
                                <p class="mb-1"><strong>Customer ID:</strong> <span id="v_cust_id"
                                        class="text-dark"></span></p>
                                <p class="mb-0"><strong>Password:</strong> <span id="v_password"
                                        class="text-danger fw-bold"></span></p>
                            </div>
                        </div>
                        <div class="col-md-6 secure-view-element">
                            <div class="p-3 border rounded bg-light h-100">
                                <h6 class="fw-bold text-primary mb-3">Branch Info</h6>
                                <p class="mb-0"><strong>Branch:</strong> <span id="v_branch" class="text-dark"></span>
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
                        <div class="col-md-4 secure-view-element">
                            <p class="small text-muted mb-0">Mobile Number</p>
                            <h6 class="fw-bold" id="v_mobile"></h6>
                        </div>
                        <div class="col-md-4 secure-view-element">
                            <p class="small text-muted mb-0">Email ID</p>
                            <h6 class="fw-bold" id="v_email"></h6>
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
                            <p class="small text-muted mb-0">Joining Date</p>
                            <h6 class="fw-bold" id="v_joining"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Father's Name</p>
                            <h6 class="fw-bold" id="v_father"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Spouse's Name</p>
                            <h6 class="fw-bold" id="v_spouse"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Marital Status</p>
                            <h6 class="fw-bold" id="v_marital"></h6>
                        </div>
                        <div class="col-md-4" id="view_anniversary_wrap" style="display:none;">
                            <p class="small text-muted mb-0">Date of Anniversary</p>
                            <h6 class="fw-bold" id="v_anniversary"></h6>
                        </div>

                        <div class="col-12 mt-3 secure-view-element">
                            <h6 class="fw-bold text-secondary border-bottom pb-2">Nominee Basic Info</h6>
                        </div>
                        <div class="col-md-4 secure-view-element">
                            <p class="small text-muted mb-0">Nominee Name</p>
                            <h6 class="fw-bold" id="v_nom_name"></h6>
                        </div>
                        <div class="col-md-4 secure-view-element">
                            <p class="small text-muted mb-0">Relation</p>
                            <h6 class="fw-bold" id="v_nom_relation"></h6>
                        </div>
                        <div class="col-md-4 secure-view-element">
                            <p class="small text-muted mb-0">Nominee Mobile</p>
                            <h6 class="fw-bold" id="v_nom_mobile"></h6>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- RESPONSE MODAL -->
    <div class="modal fade" id="responseModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="responseModalTitle">Message</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <i id="responseModalIcon" class="fas fa-3x mb-3"></i>
                    <p id="responseModalMessage" class="mb-0 fw-medium" style="word-wrap: break-word; font-size: 14px;">
                    </p>
                </div>
                <div class="modal-footer border-top-0 justify-content-center pb-4">
                    <button type="button" class="btn px-5 text-white fw-bold shadow-sm" id="responseModalBtn"
                        data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.userPerms = {!! json_encode($perms) !!};
        window.userGodMode = {{ $isGod ? 'true' : 'false' }};
        window.userContext = {!! json_encode($globalContext) !!};

        // 🔥 CRITICAL: SLUG FOR DIRECTORY PAGE 🔥
        window.moduleBase = 'cust_dir';
        window.canExport = {{ $canExport ? 'true' : 'false' }};
    </script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {
            const apiToken = localStorage.getItem('admin_token') || localStorage.getItem('emp_token') ||
                localStorage.getItem('member_token');
            let mode = 'add';
            let branchMap = {};
            let companyMap = {};
            let memberMap = {};

            let permsArray = window.userPerms || [];
            let isGod = window.userGodMode || false;
            let hasDirectAdd = isGod || permsArray.includes('customer_add_direct') || permsArray.includes(
                'customer_add');
            let hasRequestAdd = permsArray.includes('customer_add_request');

            if (!hasDirectAdd && hasRequestAdd) {
                $('#addCustomerBtn').html('<i class="fas fa-paper-plane me-1"></i> Request Customer Register');
            }
            if (window.userGodMode) {
                $('.secured-item').css('visibility', 'visible');
            }

            $(document).ajaxSuccess(function(event, xhr, settings) {
                if (settings.url.indexOf('/auth/me') !== -1) {
                    if (typeof window.applyPermissions === 'function') window.applyPermissions();
                    if (typeof table !== 'undefined') table.draw(false);

                    let user = xhr.responseJSON?.data || xhr.responseJSON?.user || xhr.responseJSON;
                    if (user) {
                        window.userContext = {
                            company_id: user.company_id,
                            branch_id: user.branch_id,
                            profile_id: user.member_id || user.id,
                            name: user.member_name || user.full_name || user.name,
                            is_member: user.member_id ? true : false,
                            role_level: user.role_level || (user.member_id ? 'member' : 'employee')
                        };
                        applyAutoLockUI();
                    }

                    let p = window.userPerms || [];
                    let isG = window.userGodMode || false;
                    let hasDirect = isG || p.includes('customer_add_direct') || p.includes('customer_add');
                    let hasReq = p.includes('customer_add_request');
                    if (!hasDirect && hasReq) $('#addCustomerBtn').html(
                        '<i class="fas fa-paper-plane me-1"></i> Request Customer Register');
                }
            });

            $(document).on('change', '.marital-radio', function() {
                if ($(this).val() === 'Married') $('#wrap_anniversary').slideDown();
                else {
                    $('#wrap_anniversary').slideUp();
                    $('#f_anniversary').val('');
                }
            });

            // 🛡️ SECURITY & SCOPING
            let isDir = window.userContext && (window.userContext.is_director || window.userContext.role_level ===
                'director');
            let showSecureData = isGod || isDir;

            function applyScopeUI() {
                $('#wrap_company, #wrap_branch').show();
                let isDir = window.userContext && (window.userContext.is_director || window.userContext
                    .role_level === 'director');
                let isGodMode = window.userGodMode === true || window.userGodMode === 'true';
                let showSecureData = isGodMode || isDir;

                if (!showSecureData) {
                    $('.secure-view-element').hide();
                }
            }

            function applyAutoLockUI() {
                let isGodMode = window.userGodMode === true || window.userGodMode === 'true';
                let ctx = window.userContext || {};
                let isMember = ctx.is_member === true || ctx.role_level === 'member';

                $('#f_company, #f_branch, #member_search_input').css('pointer-events', 'auto').removeClass(
                    'bg-light').prop('readonly', false);

                if (!isMember) {
                    $('#member_search_input').val('');
                    $('#f_member_id').val('');
                }

                if (isGodMode) return;

                let compId = ctx.company_id ? parseInt(ctx.company_id) : null;
                let branchId = ctx.branch_id ? parseInt(ctx.branch_id) : null;

                if (isMember) {
                    $('#f_company, #f_branch, #member_search_input').css('pointer-events', 'none').addClass(
                        'bg-light').prop('readonly', true);

                    let memberName = ctx.name ? `${ctx.name} (${ctx.profile_id})` : ctx.profile_id;
                    $('#member_search_input').val(memberName);
                    $('#f_member_id').val(ctx.profile_id);

                    if (compId) {
                        let cName = Object.keys(companyMap).find(key => companyMap[key] == compId) || 'My Company';
                        $('#f_company').val(cName);
                        $('#hidden_company_id').val(compId);
                    }
                    if (branchId) {
                        $('#f_branch').val('My Branch');
                        $('#branch_id_hidden').val(branchId);
                    }
                    return;
                }

                if (compId === 1 && !branchId) {} else if (compId === 1 && branchId) {
                    $('#f_company, #f_branch').css('pointer-events', 'none').addClass('bg-light').prop('readonly',
                        true);
                } else if (compId !== 1 && !branchId) {
                    $('#f_company').css('pointer-events', 'none').addClass('bg-light').prop('readonly', true);
                } else if (compId !== 1 && branchId) {
                    $('#f_company, #f_branch').css('pointer-events', 'none').addClass('bg-light').prop('readonly',
                        true);
                }

                if (compId) {
                    let cName = Object.keys(companyMap).find(key => companyMap[key] == compId) || 'My Company';
                    $('#f_company').val(cName);
                    $('#hidden_company_id').val(compId);
                }

                if (branchId) {
                    $('#f_branch').val('My Branch');
                    $('#branch_id_hidden').val(branchId);
                }
            }

            window.showMessage = function(message, type = 'success') {
                let modal = $('#responseModal');
                let header = modal.find('.modal-header');
                let title = $('#responseModalTitle');
                let icon = $('#responseModalIcon');
                let btn = $('#responseModalBtn');
                let closeBtn = modal.find('.btn-close');

                header.removeClass('bg-success bg-danger text-white');
                icon.removeClass('fa-check-circle text-success fa-times-circle text-danger');
                btn.removeClass('btn-success btn-danger');
                closeBtn.removeClass('btn-close-white');

                if (type === 'error') {
                    title.text('Error Occurred!');
                    header.addClass('bg-danger text-white');
                    icon.addClass('fas fa-times-circle text-danger');
                    btn.addClass('btn-danger');
                    closeBtn.addClass('btn-close-white');
                } else {
                    title.text('Success!');
                    header.addClass('bg-success text-white');
                    icon.addClass('fas fa-check-circle text-success');
                    btn.addClass('btn-success');
                    closeBtn.addClass('btn-close-white');
                }

                $('#responseModalMessage').text(message);
                modal.modal('show');
            };

            function loadCompanies() {
                $.ajax({
                    url: '/api/v1/get-active-companies',
                    type: 'GET',
                    success: function(res) {
                        let opts = '';
                        companyMap = {};
                        res.data.forEach(c => {
                            opts += `<option value="${c.company_name}">`;
                            companyMap[c.company_name] = c.id;
                        });
                        $('#companyList').html(opts);
                        applyAutoLockUI();
                    }
                });
            }
            loadCompanies();

            function validateBranchField() {
                let val = $('#f_branch').val().trim();
                let errorSpan = $('#branch_error');
                let hiddenId = $('#branch_id_hidden');

                if (val === '') {
                    hiddenId.val('');
                    errorSpan.hide().text('');
                    document.getElementById('f_branch').setCustomValidity('');
                } else if (branchMap[val]) {
                    hiddenId.val(branchMap[val]);
                    errorSpan.hide().text('');
                    document.getElementById('f_branch').setCustomValidity('');
                } else {
                    let isTyping = false;
                    for (let key in branchMap) {
                        if (key.toLowerCase().includes(val.toLowerCase())) {
                            isTyping = true;
                            break;
                        }
                    }
                    if (isTyping) {
                        hiddenId.val('');
                        errorSpan.hide().text('');
                        document.getElementById('f_branch').setCustomValidity('Incomplete branch name');
                    } else {
                        hiddenId.val('');
                        errorSpan.text('❌ This branch does not belong to the selected company!').show();
                        document.getElementById('f_branch').setCustomValidity('Invalid branch');
                    }
                }
            }

            $('#f_company').on('change blur', function() {
                let val = $(this).val();
                if (companyMap[val]) {
                    $('#hidden_company_id').val(companyMap[val]);
                    $('#member_search_input').val('');
                    $('#f_member_id').val('');
                    $('#memberListOptions').empty();

                    $.ajax({
                        url: '/api/v1/branches?company_id=' + companyMap[val],
                        type: 'GET',
                        success: function(res) {
                            let bOpts = '<option value="Head Office">';
                            branchMap = {
                                "Head Office": ""
                            };

                            res.data.forEach(b => {
                                bOpts += `<option value="${b.branch_name}">`;
                                branchMap[b.branch_name] = b.id;
                            });
                            $('#branchList').html(bOpts);
                            validateBranchField();
                        }
                    });
                }
            });
            $('#f_branch').on('input change blur', function() {
                validateBranchField();
            });

            // 🔥 EXCEL BUTTON FIX: Only one push if permission exists 🔥
            let dtButtons = [];

            if (window.canExport) {
                dtButtons.push({
                    extend: 'excelHtml5',
                    className: 'btn btn-success btn-sm shadow-sm rounded-3 mt-1 secured-item',
                    attr: {
                        'data-permission': 'customer_export'
                    },
                    text: '<i class="fas fa-file-excel me-1"></i> Export Excel',
                    action: function(e, dt, button, config) {
                        let oldLength = dt.page.len();
                        dt.page.len(-1).draw();
                        dt.one('draw', function() {
                            $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt,
                                button, config);
                            dt.page.len(oldLength).draw();
                        });
                    }
                });
            }

            // 🔥 DATATABLES INITIALIZATION 🔥
            let table = $('#customerTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 20, // 🔥 NAYA: 20 Items per page for Directory
                lengthMenu: [
                    [10, 20, 50, 100],
                    [10, 20, 50, 100]
                ],
                ajax: {
                    // 🔥 NAYA: Ensure URL is explicitly hitting the directory endpoint
                    url: '/api/v1/customers/directory',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    }
                },
                dom: '<"row mb-3 align-items-center"<"col-sm-6"B><"col-sm-6 text-md-end"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: dtButtons,
                columns: [{
                        data: 'id',
                        orderable: false,
                        render: d =>
                            `<input type="checkbox" class="form-check-input row-checkbox" value="${d}">`
                    },
                    {
                        data: 'customer_id',
                        render: (d, t, r) =>
                            `<span class="fw-bold text-primary">${d}</span><br><small class="text-muted">Code: ${r.customer_code||''}</small>`
                    },
                    {
                        data: 'branch_id',
                        visible: showSecureData,
                        render: function(d, t, r) {
                            let branchName = r.branch ? r.branch.branch_name : 'Head Office';
                            let compName = (r.branch && r.branch.company) ? r.branch.company
                                .company_name : 'Master Company';
                            // 🔥 NAYA: Upar Branch, Niche Company Name HTML Format
                            return `<span class="fw-bold text-dark">${branchName}</span><br><small class="text-muted">${compName}</small>`;
                        }
                    },
                    {
                        data: 'customer_name'
                    },
                    {
                        data: 'customer_mobile',
                        visible: showSecureData
                    },
                    {
                        data: 'status',
                        render: function(d, t, r) {
                            if (r.deleted_at)
                                return `<span class="badge bg-danger"><i class="fas fa-trash"></i> Deleted</span>`;
                            let badgeClass = d === 'active' ? 'bg-success' : (d === 'pending' ?
                                'bg-warning text-dark' : 'bg-danger');
                            return `<span class="badge ${badgeClass}">${d.toUpperCase()}</span>`;
                        }
                    },
                    {
                        data: 'created_by',
                        visible: showSecureData,
                        render: d => d || '-'
                    },
                    {
                        data: 'id',
                        orderable: false,
                        className: 'text-end text-nowrap',
                        render: function(d, t, r) {
                            let btns = '';
                            let p = window.userPerms || [];
                            let godMode = window.userGodMode === true || window.userGodMode ===
                                'true';

                            // Uses dynamic moduleBase (cust_dir)
                            let hasView = godMode || p.includes(window.moduleBase + '_view');
                            let hasEdit = godMode || p.includes(window.moduleBase + '_edit');
                            let hasDelete = godMode || p.includes(window.moduleBase + '_delete');
                            let hasRestore = godMode || p.includes(window.moduleBase + '_restore');
                            let hasAppr = godMode || p.includes(window.moduleBase + '_appr');
                            let hasRej = godMode || p.includes(window.moduleBase + '_rej');

                            if (r.deleted_at) {
                                if (hasRestore) {
                                    btns +=
                                        `<button type="button" class="btn btn-sm btn-light text-success restore-btn" data-id="${d}" title="Restore"><i class="fas fa-trash-restore"></i></button>`;
                                }
                            } else {
                                if (hasView) btns +=
                                    `<button type="button" class="btn btn-sm btn-light text-info view-btn" data-id="${d}" title="View"><i class="fas fa-eye"></i></button>`;
                                if (hasAppr) btns +=
                                    `<button type="button" class="btn btn-sm btn-light text-success" onclick="updateStatus(${d}, 'approve')" title="Approve"><i class="fas fa-check-circle"></i></button>`;
                                if (hasRej) btns +=
                                    `<button type="button" class="btn btn-sm btn-light text-warning" onclick="updateStatus(${d}, 'reject')" title="Reject"><i class="fas fa-times-circle"></i></button>`;
                                if (hasEdit) btns +=
                                    `<button type="button" class="btn btn-sm btn-light text-primary edit-btn" data-id="${d}" title="Edit"><i class="fas fa-edit"></i></button>`;
                                if (hasDelete) btns +=
                                    `<button type="button" class="btn btn-sm btn-light text-danger delete-btn" data-id="${d}" title="Delete"><i class="fas fa-trash-alt"></i></button>`;
                            }
                            return `<div class="d-flex justify-content-end gap-1">${btns}</div>`;
                        }
                    }
                ]
            });

            // 🔥 MOBILE CARDS 🔥
            function renderMobileCards(data) {
                let html = '';
                if (!data || data.length === 0) {
                    html =
                        '<div class="text-center p-3 text-muted border rounded bg-light">No customers found.</div>';
                } else {
                    let p = window.userPerms || [];
                    let hasView = isGod || p.includes(window.moduleBase + '_view');
                    let hasEdit = isGod || p.includes(window.moduleBase + '_edit');
                    let hasDelete = isGod || p.includes(window.moduleBase + '_delete');
                    let hasRestore = isGod || p.includes(window.moduleBase + '_restore');

                    data.forEach(d => {
                        let compName = d.branch && d.branch.company ? d.branch.company.company_name :
                            'HQ - Primary Company';
                        let branchName = d.branch ? d.branch.branch_name : 'Head Office';
                        let creator = d.created_by && showSecureData ?
                            `<span class="created-badge float-end"><i class="fas fa-user-edit me-1"></i>${d.created_by}</span>` :
                            '';

                        let statusBadge = '';
                        if (d.deleted_at) statusBadge =
                            `<span class="badge bg-danger float-end mt-1"><i class="fas fa-trash"></i> Deleted</span>`;
                        else if (d.status === 'active') statusBadge =
                            `<span class="badge bg-success float-end mt-1">Active</span>`;
                        else if (d.status === 'pending') statusBadge =
                            `<span class="badge bg-warning text-dark float-end mt-1">Pending</span>`;
                        else if (d.status === 'inactive') statusBadge =
                            `<span class="badge bg-danger float-end mt-1">Inactive</span>`;

                        let actionBtns = '';
                        if (d.deleted_at) {
                            if (hasRestore) actionBtns +=
                                `<button type="button" class="btn btn-sm btn-light text-success flex-fill restore-btn" data-id="${d.id}"><i class="fas fa-trash-restore"></i> Restore</button>`;
                        } else {
                            if (hasView) actionBtns +=
                                `<button type="button" class="btn btn-sm btn-light text-info flex-fill view-btn" data-id="${d.id}">View</button>`;
                            if (hasEdit) actionBtns +=
                                `<button type="button" class="btn btn-sm btn-light text-primary flex-fill edit-btn" data-id="${d.id}">Edit</button>`;
                            if (hasDelete) actionBtns +=
                                `<button type="button" class="btn btn-sm btn-light text-danger flex-fill delete-btn" data-id="${d.id}">Delete</button>`;
                        }
                        if (!actionBtns) actionBtns =
                            `<span class="small fw-bold text-muted w-100 text-center"><i class="fas fa-lock"></i> Locked</span>`;

                        let checkHtml =
                            `<input type="checkbox" class="form-check-input row-checkbox me-2" value="${d.id}">`;
                        let mobHtml = showSecureData ?
                            `<div class="small text-muted"><i class="fas fa-phone me-1"></i> ${d.customer_mobile}</div>` :
                            '';
                        let branchHtml = showSecureData ?
                            `<div class="small text-muted mb-1"><i class="fas fa-building text-info me-1"></i> ${branchName} <br><span style="font-size:10px;">${compName}</span></div>` :
                            '';

                        html += `<div class="mobile-item ledger-card">
                            <div class="d-flex align-items-start mb-2">
                                ${checkHtml}
                                <div class="w-100">
                                    <h6 class="fw-bold text-dark mb-0">${d.customer_name} ${creator}</h6>
                                    <div class="small fw-bold text-primary mt-1">${d.customer_id} ${statusBadge}</div>
                                    ${branchHtml}
                                    ${mobHtml}
                                </div>
                            </div>
                           <div class="mt-2 pt-2 border-top d-flex gap-2 flex-wrap">${actionBtns}</div>
                        </div>`;
                    });
                }
                $('#mobileCardsContainer').html(html);
            }

            table.on('draw', function() {
                renderMobileCards(table.ajax.json().data);
            });

            $('#mobileSearch').on('keyup', function() {
                let v = $(this).val().toLowerCase();
                $('.mobile-item').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1)
                });
            });

            $('#member_search_input').on('input change', function() {
                let val = $(this).val();
                if (memberMap[val]) $('#f_member_id').val(memberMap[val]);
                else $('#f_member_id').val('');
            });

            $('input[type="file"]').each(function() {
                $(this).after(`
                    <div class="file-preview-wrapper">
                        <button type="button" class="btn btn-danger remove-preview-btn" title="Remove File"><i class="fas fa-times"></i></button>
                        <div class="preview-content text-center"></div>
                    </div>
                `);
            });

            // 🔥 BULK DELETE LOGIC 🔥
            $(document).on('change', '.row-checkbox', toggleBulkActions);
            $('#btnSelectAll').click(function() {
                let allChecked = $('.row-checkbox').length === $('.row-checkbox:checked').length;
                $('.row-checkbox').prop('checked', !allChecked);
                toggleBulkActions();
            });

            function toggleBulkActions() {
                $('#bulkActions').toggle($('.row-checkbox:checked').length > 0);
            }

            window.bulkDelete = function() {
                let ids = [];
                $('.row-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });
                if (ids.length === 0) return;
                if (confirm(`Are you sure you want to soft-delete ${ids.length} selected customers?`)) {
                    $.ajax({
                        url: '/api/v1/customers/bulk-delete',
                        type: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + apiToken
                        },
                        data: {
                            ids: ids
                        },
                        success: function(res) {
                            table.ajax.reload(null, false);
                            $('#bulkActions').hide();
                            showMessage(res.message, 'success');
                        }
                    });
                }
            };

            $(document).on('change', 'input[type="file"]', function(e) {
                let file = this.files[0];
                let wrapper = $(this).next('.file-preview-wrapper');
                let content = wrapper.find('.preview-content');

                if (file) {
                    if (file.type.startsWith('image/')) {
                        let reader = new FileReader();
                        reader.onload = function(event) {
                            content.html(
                                `<img src="${event.target.result}" style="max-height:90px; border-radius:6px; object-fit:contain;">`
                            );
                            wrapper.slideDown();
                        }
                        reader.readAsDataURL(file);
                    } else {
                        let icon = file.type === 'application/pdf' ? 'fa-file-pdf text-danger' :
                            'fa-file-alt text-primary';
                        content.html(
                            `<div class="d-flex align-items-center gap-2 fw-bold text-dark px-2"><i class="fas ${icon} fs-3"></i><span style="font-size:12px; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${file.name}</span></div>`
                        );
                        wrapper.slideDown();
                    }
                } else {
                    wrapper.slideUp();
                }
            });

            $(document).on('click', '.remove-preview-btn', function() {
                let wrapper = $(this).closest('.file-preview-wrapper');
                let input = wrapper.prev('input[type="file"]');
                input.val('');
                wrapper.slideUp();
            });

            // 🔥 NAYA: 3-Letter Dynamic Member Search 🔥
            let memberDataMap = {};

            $('#member_search_input').on('input', function() {
                let val = $(this).val();
                let compId = $('#hidden_company_id').val();

                // अगर यूज़र ने लिस्ट में से पूरा नाम सेलेक्ट कर लिया है
                if (memberDataMap[val]) {
                    $('#f_member_id').val(memberDataMap[val]);
                    return;
                }

                // अगर यूज़र टाइप कर रहा है, तो हिडन ID क्लियर कर दें
                $('#f_member_id').val('');

                // 3 लेटर होने पर ही API हिट करें
                if (val.length >= 3) {
                    let url = `/api/v1/members/search-dynamic?q=${val}`;
                    if (compId) url += `&company_id=${compId}`;

                    $.ajax({
                        url: url,
                        type: 'GET',
                        headers: {
                            'Authorization': 'Bearer ' + apiToken
                        },
                        success: function(res) {
                            let options = '';
                            memberDataMap = {}; // Reset map

                            res.data.forEach(m => {
                                let compName = m.company ? m.company.company_name :
                                    'N/A';
                                let disp =
                                    `${m.member_name} (${m.member_id}) - ${compName}`;
                                options += `<option value="${disp}">`;
                                memberDataMap[disp] = m.member_id;
                            });

                            $('#memberListOptions').html(options);
                        }
                    });
                } else {
                    $('#memberListOptions').empty();
                }
            });

            window.openModal = function(type, id = null) {
                mode = type;
                $('#customerForm')[0].reset();
                $('#branch_id_hidden, #hidden_company_id, #f_member_id').val('');
                $('#wrap_anniversary').hide();
                $('#branch_error').hide();
                document.getElementById('f_branch').setCustomValidity('');

                let isReq = $('#addCustomerBtn').text().toLowerCase().includes('request');

                if (type === 'add') {
                    if (isReq) {
                        $('#modalTitle').html(
                            '<i class="fas fa-paper-plane me-2 text-warning"></i> Request Customer Registration'
                            );
                        $('#saveBtn').text('Submit Customer Request');
                    } else {
                        $('#modalTitle').html(
                            '<i class="fas fa-user-plus me-2 text-primary"></i> Register Customer');
                        $('#saveBtn').text('Save Customer Details');
                    }
                    $('#old_customer_block').show();
                    $('.password-div').hide();
                    $('.status-div').hide();
                    fetchNewID();
                } else {
                    $('#modalTitle').html(
                    '<i class="fas fa-edit me-2 text-primary"></i> Edit Customer Details');
                    $('#saveBtn').text('Update Customer Details');
                    $('#old_customer_block').hide();
                    $('.password-div').show();
                    $('.status-div').show();
                }

                $('.nav-tabs button:first').tab('show');
                $('.file-preview-wrapper').hide().find('.preview-content').empty();

                let isGodMode = window.userGodMode === true || window.userGodMode === 'true';
                let ctx = window.userContext || {};
                let isMember = ctx.is_member || ctx.role_level === 'member' || localStorage.getItem(
                    'member_session_id');

                applyScopeUI(); // UI को सेट करें

                if (type === 'add' && !isGodMode) {
                    if (ctx.company_id) {
                        let compName = Object.keys(companyMap).find(key => companyMap[key] == ctx.company_id);
                        if (compName) {
                            $('#f_company').val(compName);
                            $('#hidden_company_id').val(ctx.company_id);

                            $.ajax({
                                url: '/api/v1/branches?company_id=' + ctx.company_id,
                                headers: {
                                    'Authorization': 'Bearer ' + apiToken
                                },
                                success: function(res) {
                                    let options = '';
                                    branchMap = {};
                                    res.data.forEach(b => {
                                        let cName = b.company ? b.company.company_name :
                                            'Master Company';
                                        let disp =
                                            `${cName} - ${b.branch_name} (${b.branch_id})`;
                                        options += `<option value="${disp}">`;
                                        branchMap[disp] = b.id;
                                    });
                                    $('#branchList').html(options);

                                    if (ctx.branch_id) {
                                        let branchDisp = Object.keys(branchMap).find(key =>
                                            branchMap[key] == ctx.branch_id);
                                        if (branchDisp) {
                                            $('#f_branch').val(branchDisp);
                                            $('#branch_id_hidden').val(ctx.branch_id);
                                        }
                                    }
                                }
                            });
                        }
                    }

                    if (isMember && ctx.profile_id) {
                        let memberDisplayName = window.userContext.name ?
                            `${window.userContext.name} (${ctx.profile_id})` : ctx.profile_id;
                        $('#member_search_input').val(memberDisplayName);
                        $('#f_member_id').val(ctx.profile_id);
                    }
                }

                applyAutoLockUI();

                if (type === 'edit') {
                    $('#f_company, #f_branch, #member_search_input').css('pointer-events', 'auto').removeClass(
                        'bg-light').prop('readonly', false);
                }

                if (window.userGodMode) {
                    $('#branchList').empty();
                    $('#f_branch').val('');
                } else if (window.userContext && window.userContext.company_id) {
                    $.ajax({
                        url: '/api/v1/branches?company_id=' + window.userContext.company_id,
                        headers: {
                            'Authorization': 'Bearer ' + apiToken
                        },
                        success: function(res) {
                            let options = '';
                            branchMap = {};
                            res.data.forEach(b => {
                                let compName = b.company ? b.company.company_name :
                                    'Master Company';
                                let disp =
                                `${compName} - ${b.branch_name} (${b.branch_id})`;
                                options += `<option value="${disp}">`;
                                branchMap[disp] = b.id;
                            });
                            $('#branchList').html(options);
                        }
                    });
                }

                if (type === 'edit') {
                    $.get({
                        url: `/api/v1/customers/${id}`,
                        headers: {
                            'Authorization': 'Bearer ' + apiToken
                        },
                        success: function(res) {
                            let cust = res.data;
                            $('#edit_id').val(cust.id);

                            if (cust.status) $('#f_status').val(cust.status);

                            if (cust.company_id) {
                                let cName = Object.keys(companyMap).find(key => companyMap[key] ==
                                    cust.company_id);
                                if (!cName && cust.company) cName = cust.company.company_name;
                                $('#f_company').val(cName || 'Master Company');
                                $('#hidden_company_id').val(cust.company_id);
                            }

                            if (cust.branch_id && cust.branch) {
                                $('#f_branch').val(cust.branch.branch_name);
                                $('#branch_id_hidden').val(cust.branch_id);
                            } else {
                                $('#f_branch').val('Head Office');
                                $('#branch_id_hidden').val('');
                            }

                            if (cust.member_id) {
                                $('#member_search_input').val(cust.member_id);
                                $('#f_member_id').val(cust.member_id);
                            }

                            Object.keys(cust).forEach(key => {
                                let input = $(`#customerForm [name="${key}"]`);
                                if (input.length && input.attr('type') !== 'file' && input
                                    .attr('type') !== 'radio') {
                                    if (typeof cust[key] === 'object' && cust[key] !== null)
                                        return;
                                    if (key !== 'branch_id' && key !== 'member_id' &&
                                        key !== 'status') input.val(cust[key]);
                                }
                            });

                            $('#f_bank_branch').val(cust.bank_branch_text);

                            if (cust.gender) $(`input[name="gender"][value="${cust.gender}"]`).prop(
                                'checked', true);
                            if (cust.marital_status) {
                                $(`input[name="marital_status"][value="${cust.marital_status}"]`)
                                    .prop('checked', true);
                                if (cust.marital_status === 'Married') {
                                    $('#wrap_anniversary').show();
                                    $('#f_anniversary').val(cust.date_of_anniversary);
                                }
                            }

                            let fileFields = [
                                'aadharcard', 'pancard', 'bank_passbook_pdf', 'drivinglicense',
                                'passport', 'passport_photo',
                                'tenthmarksheet', 'twelvethmarksheet', 'graduationcertificate',
                                'pgcertificate', 'otherdoc',
                                'nom_aadharcard', 'nom_pancard', 'nom_bankpassbook',
                                'nom_drivinglicense', 'nom_passport',
                                'nom_passport_photo', 'nom_tenthmarksheet',
                                'nom_twelvethmarksheet', 'nom_graduationcertificate',
                                'nom_pgcertificate', 'nom_otherdoc'
                            ];

                            fileFields.forEach(function(field) {
                                let filePath = cust[field];
                                let input = $(`#customerForm input[name="${field}"]`);
                                if (input.length > 0 && filePath) {
                                    let wrapper = input.next('.file-preview-wrapper');
                                    let content = wrapper.find('.preview-content');
                                    let fullUrl = filePath.startsWith('/') ? filePath :
                                        '/' + filePath;
                                    let ext = filePath.split('.').pop().toLowerCase();
                                    let imageExts = ['jpg', 'jpeg', 'png', 'webp', 'bmp'];

                                    if (imageExts.includes(ext)) {
                                        content.html(
                                            `<img src="${fullUrl}" style="max-height:90px; border-radius:6px; object-fit:contain;">`
                                            );
                                    } else {
                                        let icon = ext === 'pdf' ?
                                            'fa-file-pdf text-danger' :
                                            'fa-file-alt text-primary';
                                        content.html(
                                            `<div class="d-flex align-items-center gap-2 fw-bold text-dark px-2"><i class="fas ${icon} fs-3"></i><a href="${fullUrl}" target="_blank" class="text-decoration-none" style="font-size:12px;">View Document</a></div>`
                                            );
                                    }
                                    wrapper.show();
                                }
                            });
                        }
                    });
                }
                $('#customerModal').modal('show');
            };

            $('#customerForm').submit(function(e) {
                e.preventDefault();
                if (!this.checkValidity()) return;
                let formData = new FormData(this);
                let id = $('#edit_id').val();
                let url = mode === 'add' ? '/api/v1/customers' : `/api/v1/customers/${id}`;
                if (mode === 'edit') formData.append('_method', 'PUT');

                let btn = $('#saveBtn');
                btn.prop('disabled', true).text('Saving...');
                $.ajax({
                    url: url,
                    type: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#customerModal').modal('hide');
                        table.ajax.reload(null, false);
                        showMessage(res.message, 'success');
                    },
                    error: function(err) {
                        let errMsg = 'Something went wrong!';
                        if (err.responseJSON && err.responseJSON.message) errMsg = err
                            .responseJSON.message;
                        showMessage(errMsg, 'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Save Customer Details');
                    }
                });
            });

            $(document).on('click', '.view-btn', function() {
                let id = $(this).data('id');
                $.get({
                    url: `/api/v1/customers/${id}`,
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        let d = res.data;
                        $('#v_cust_id').text(d.customer_id || 'N/A');
                        $('#v_password').text(d.password || 'N/A');

                        let branchText = 'N/A';
                        if (d.branch) {
                            let compName = d.branch.company ? d.branch.company.company_name :
                                'Master Company';
                            branchText = compName + ' - ' + d.branch.branch_name;
                        } else if (d.company_id) {
                            branchText = 'HQ - Primary Company (Head Office)';
                        }
                        $('#v_branch').text(branchText);

                        $('#v_name').text(d.customer_name || 'N/A');
                        $('#v_mobile').text(d.customer_mobile || 'N/A');
                        $('#v_email').text(d.customer_email || 'N/A');
                        $('#v_aadhar').text(d.aadhar_number || 'N/A');
                        $('#v_pan').text(d.pan_number || 'N/A');
                        $('#v_joining').text(d.joining_date || 'N/A');
                        $('#v_father').text(d.father_name || 'N/A');
                        $('#v_spouse').text(d.spouse_name || 'N/A');
                        $('#v_marital').text(d.marital_status || 'N/A');

                        if (d.marital_status === 'Married' && d.date_of_anniversary) {
                            $('#view_anniversary_wrap').show();
                            $('#v_anniversary').text(d.date_of_anniversary);
                        } else $('#view_anniversary_wrap').hide();

                        $('#v_nom_name').text(d.nominee_name || 'N/A');
                        $('#v_nom_relation').text(d.nominee_relation || 'N/A');
                        $('#v_nom_mobile').text(d.nominee_mobile || 'N/A');

                        $('#viewModal').modal('show');
                    }
                });
            });

            $(document).on('click', '.edit-btn', function() {
                window.openModal('edit', $(this).data('id'));
            });

            $(document).on('click', '.delete-btn', function() {
                if (confirm("Delete Customer?")) {
                    $.ajax({
                        url: `/api/v1/customers/${$(this).data('id')}`,
                        type: 'DELETE',
                        headers: {
                            'Authorization': 'Bearer ' + apiToken
                        },
                        success: function(res) {
                            table.ajax.reload(null, false);
                            showMessage(res.message || 'Deleted successfully!', 'success');
                        },
                        error: function(err) {
                            showMessage(err.responseJSON?.message || 'Unauthorized Action',
                                'error');
                        }
                    });
                }
            });

            $(document).on('click', '.restore-btn', function() {
                if (confirm("Restore Customer?")) {
                    $.ajax({
                        url: `/api/v1/customers/${$(this).data('id')}/restore`,
                        type: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + apiToken
                        },
                        success: function(res) {
                            table.ajax.reload(null, false);
                            showMessage(res.message || 'Restored successfully!', 'success');
                        },
                        error: function(err) {
                            showMessage(err.responseJSON?.message || 'Unauthorized Action',
                                'error');
                        }
                    });
                }
            });

            let oldCustomerDataMap = {};

            $(document).on('change', '#is_old_customer', function() {
                if ($(this).is(':checked')) {
                    $('#old_customer_sec').slideDown();
                    let compOpts = '<option value="">-- All Companies --</option>';
                    if (typeof companyMap !== 'undefined' && Object.keys(companyMap).length > 0) {
                        Object.keys(companyMap).forEach(compName => {
                            compOpts +=
                                `<option value="${companyMap[compName]}">${compName}</option>`;
                        });
                    }
                    $('#old_search_company').html(compOpts);
                } else {
                    $('#old_customer_sec').slideUp();
                    $('#old_search_input').val('');
                    $('#old_customer_code').val('');
                    $('#customerForm')[0].reset();
                    $(this).prop('checked', false);
                    fetchNewID();
                }
            });

            $(document).on('change', '#old_search_company', function() {
                let compId = $(this).val();
                $('#old_search_input').val('');

                if (!compId) {
                    $('#oldCustomerList').empty();
                    return;
                }

                $.get(`/api/v1/customers/search-old?company_id=${compId}`, function(res) {
                    let options = '';
                    oldCustomerDataMap = {};
                    res.data.forEach(c => {
                        options += `<option value="${c.display_text}">`;
                        oldCustomerDataMap[c.display_text] = c;
                    });
                    $('#oldCustomerList').html(options);
                });
            });

            $('#old_search_input').on('input', function() {
                let q = $(this).val();
                let compId = $('#old_search_company').val();
                if (oldCustomerDataMap[q]) {
                    autofillOldCustomer(oldCustomerDataMap[q]);
                    return;
                }
                if (q.length >= 3) {
                    $.get(`/api/v1/customers/search-old?q=${q}&company_id=${compId}`, function(res) {
                        let options = '';
                        oldCustomerDataMap = {};
                        res.data.forEach(c => {
                            options += `<option value="${c.display_text}">`;
                            oldCustomerDataMap[c.display_text] = c;
                        });
                        $('#oldCustomerList').html(options);
                    });
                }
            });

            window.updateStatus = function(id, action) {
                if (confirm(`Are you sure you want to ${action} this customer?`)) {
                    $.ajax({
                        url: `/api/v1/customers/${id}/status`,
                        type: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + apiToken
                        },
                        data: {
                            action: action
                        },
                        success: function(res) {
                            showMessage(res.message, 'success');
                            table.ajax.reload(null, false);
                        },
                        error: function(err) {
                            showMessage(err.responseJSON?.message || "Unauthorized!", 'error');
                        }
                    });
                }
            };

            function autofillOldCustomer(data) {
                $('#old_customer_code').val(data.customer_code);
                Object.keys(data).forEach(key => {
                    let input = $(`#customerForm [name="${key}"]`);
                    if (input.length && input.attr('type') !== 'file' && !['branch_id', 'company_id',
                            'member_id', 'customer_id', 'password', 'joining_date'
                        ].includes(key)) {
                        input.val(data[key]);
                    }
                });
                if (data.gender) $(`input[name="gender"][value="${data.gender}"]`).prop('checked', true);
                if (data.marital_status) $(`input[name="marital_status"][value="${data.marital_status}"]`).prop(
                    'checked', true);
                showMessage("Old customer details auto-filled successfully!", "success");
                fetchNewID();
            }

            function fetchNewID() {
                let cId = $('#hidden_company_id').val() || 1;
                $.get(`/api/v1/customers/generate-id?company_id=${cId}`, function(res) {
                    $('#f_cust_id').val(res.customer_id);
                });
            }

            function generatePassword() {
                let name = $('#f_name').val() || '';
                let aadhar = $('#f_aadhar').val() || '';
                if (name.length >= 3 && aadhar.length >= 4) {
                    let pass = name.substring(0, 3).toLowerCase() + '@' + aadhar.substring(aadhar.length - 4);
                    $('#f_password').val(pass.charAt(0).toUpperCase() + pass.slice(1));
                }
            }
            $('#f_name, #f_aadhar').on('input', generatePassword);
        });
    </script>
@endpush
