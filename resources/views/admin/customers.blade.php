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

        $canExport = $isGod || in_array('customer_print', $perms) || in_array('customer_export', $perms);
    @endphp

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Customer Details</h4>
            </div>
            <div>
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
                                <th>CUST ID</th>
                                <th>Branch</th>
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

    <!-- MODAL STARTS HERE -->
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

                            <!-- TAB 1: PERSONAL INFO -->
                            <div class="tab-pane fade show active" id="tab-personal">
                                <div class="row g-3">

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
                                        <!-- Error Message Box -->
                                        <small id="branch_error" class="text-danger fw-bold mt-1"
                                            style="display: none;"></small>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Name in Full <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="customer_name" class="form-control" id="f_name"
                                            required autocomplete="off">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Associate Member (Optional)</label>
                                        <input type="text" id="member_search_input" class="form-control"
                                            list="memberListOptions" placeholder="Search Member..." autocomplete="nope">
                                        <input type="hidden" name="member_id" id="f_member_id">
                                        <datalist id="memberListOptions"></datalist>
                                    </div>

                                    <div class="col-md-4 password-div" style="display: none;">
                                        <label class="form-label text-secondary small">Login Password <span
                                                class="text-info">(Editable)</span></label>
                                        <input type="text" name="password" class="form-control"
                                            placeholder="Enter new password to change" autocomplete="new-password">
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
                                        <label class="form-label text-secondary small">Booking Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="booking_date" class="form-control" id="f_booking"
                                            required>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">S/o, D/o, W/o</label>
                                        <input type="text" name="so_do_wo" class="form-control" id="f_sodowo">
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

                            <!-- TAB 2: BANK DETAILS -->
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

                            <!-- TAB 3: NOMINEE DETAILS -->
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

                            <!-- TAB 4: DOCUMENTS -->
                            <div class="tab-pane fade" id="tab-docs">

                                <h6 class="text-primary fw-bold border-bottom pb-2 mb-3"><i
                                        class="fas fa-user-circle me-1"></i> Customer Documents</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Upload Aadhar Card (.pdf)</label>
                                        <input type="file" name="aadharcard" class="form-control form-control-sm"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Upload PAN Card (.pdf)</label>
                                        <input type="file" name="pancard" class="form-control form-control-sm"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Upload Bank Passbook (.pdf)</label>
                                        <input type="file" name="bank_passbook_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Upload Driving License
                                            (.pdf)</label>
                                        <input type="file" name="drivinglicense" class="form-control form-control-sm"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Upload Passport (.pdf)</label>
                                        <input type="file" name="passport" class="form-control form-control-sm"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Passport Size Photo (Image)</label>
                                        <input type="file" name="passport_photo" class="form-control form-control-sm"
                                            accept="image/jpeg,image/png,image/webp">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">10th Marksheet (.pdf)</label>
                                        <input type="file" name="tenthmarksheet" class="form-control form-control-sm"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">12th Marksheet (.pdf)</label>
                                        <input type="file" name="twelvethmarksheet"
                                            class="form-control form-control-sm" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Graduation Certificate
                                            (.pdf)</label>
                                        <input type="file" name="graduationcertificate"
                                            class="form-control form-control-sm" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">PG Certificate (.pdf)</label>
                                        <input type="file" name="pgcertificate" class="form-control form-control-sm"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Other Documents (.pdf)</label>
                                        <input type="file" name="otherdoc" class="form-control form-control-sm"
                                            accept=".pdf">
                                    </div>
                                </div>

                                <h6 class="text-primary fw-bold border-bottom pb-2 mb-3"><i
                                        class="fas fa-user-shield me-1"></i> Nominee Documents</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee Aadhar Card (.pdf)</label>
                                        <input type="file" name="nom_aadharcard" class="form-control form-control-sm"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee PAN Card (.pdf)</label>
                                        <input type="file" name="nom_pancard" class="form-control form-control-sm"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee Bank Passbook (.pdf)</label>
                                        <input type="file" name="nom_bankpassbook"
                                            class="form-control form-control-sm" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee Driving License
                                            (.pdf)</label>
                                        <input type="file" name="nom_drivinglicense"
                                            class="form-control form-control-sm" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee Passport (.pdf)</label>
                                        <input type="file" name="nom_passport" class="form-control form-control-sm"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee Passport Photo (Img)</label>
                                        <input type="file" name="nom_passport_photo"
                                            class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee 10th Marksheet
                                            (.pdf)</label>
                                        <input type="file" name="nom_tenthmarksheet"
                                            class="form-control form-control-sm" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee 12th Marksheet
                                            (.pdf)</label>
                                        <input type="file" name="nom_twelvethmarksheet"
                                            class="form-control form-control-sm" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee Graduation Cert
                                            (.pdf)</label>
                                        <input type="file" name="nom_graduationcertificate"
                                            class="form-control form-control-sm" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee PG Certificate
                                            (.pdf)</label>
                                        <input type="file" name="nom_pgcertificate"
                                            class="form-control form-control-sm" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Nominee Other Docs (.pdf)</label>
                                        <input type="file" name="nom_otherdoc" class="form-control form-control-sm"
                                            accept=".pdf">
                                    </div>
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
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold text-primary mb-3">Login Credentials</h6>
                                <p class="mb-1"><strong>Customer ID:</strong> <span id="v_cust_id"
                                        class="text-dark"></span></p>
                                <p class="mb-0"><strong>Password:</strong> <span id="v_password"
                                        class="text-danger fw-bold"></span></p>
                            </div>
                        </div>
                        <div class="col-md-6">
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
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Mobile Number</p>
                            <h6 class="fw-bold" id="v_mobile"></h6>
                        </div>
                        <div class="col-md-4">
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
                            <p class="small text-muted mb-0">Booking Date</p>
                            <h6 class="fw-bold" id="v_booking"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Marital Status</p>
                            <h6 class="fw-bold" id="v_marital"></h6>
                        </div>
                        <div class="col-md-4" id="view_anniversary_wrap" style="display:none;">
                            <p class="small text-muted mb-0">Date of Anniversary</p>
                            <h6 class="fw-bold" id="v_anniversary"></h6>
                        </div>

                        <div class="col-12 mt-3">
                            <h6 class="fw-bold text-secondary border-bottom pb-2">Nominee Basic Info</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Nominee Name</p>
                            <h6 class="fw-bold" id="v_nom_name"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Relation</p>
                            <h6 class="fw-bold" id="v_nom_relation"></h6>
                        </div>
                        <div class="col-md-4">
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
        // Server variables initialization
        window.userPerms = {!! json_encode($perms) !!};
        window.userGodMode = {{ $isGod ? 'true' : 'false' }};
        window.userContext = {!! json_encode($globalContext) !!};
        window.moduleBase = 'customer';
        window.canExport = {{ $canExport ? 'true' : 'false' }};
    </script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script>
       $(document).ready(function() {
            const apiToken = localStorage.getItem('admin_token') || localStorage.getItem('emp_token');
            let mode = 'add';
            let branchMap = {};
            let companyMap = {};
            let memberMap = {};

            // 👇 NAYA CODE: Check Request vs Direct Access 👇
            let permsArray = window.userPerms || [];
            let isGod = window.userGodMode || false;
            let hasDirectAdd = isGod || permsArray.includes('customer_add_direct') || permsArray.includes('customer_add'); 
            let hasRequestAdd = permsArray.includes('customer_add_request');

            // Top Add Button ka Text Change
            if (!hasDirectAdd && hasRequestAdd) {
                $('#addCustomerBtn').html('<i class="fas fa-paper-plane me-1"></i> Request Customer Register');
            }

            if (window.userGodMode) {
                $('.secured-item').css('visibility', 'visible');
            }

           $(document).ajaxSuccess(function(event, xhr, settings) {
                if (settings.url.indexOf('/auth/me') !== -1) {
                    if (typeof window.applyPermissions === 'function') {
                        window.applyPermissions();
                    }
                    if (typeof table !== 'undefined') table.draw(false);

                    // 🔥 Dynamic Add Button Text Logic (After API load) 🔥
                    let p = window.userPerms || [];
                    let isG = window.userGodMode || false;
                    let hasDirect = isG || p.includes('customer_add_direct') || p.includes('customer_add');
                    let hasReq = p.includes('customer_add_request');

                    if (!hasDirect && hasReq) {
                        $('#addCustomerBtn').html('<i class="fas fa-paper-plane me-1"></i> Request Customer Register');
                    }
                }
            });

            $(document).on('change', '.marital-radio', function() {
                if ($(this).val() === 'Married') {
                    $('#wrap_anniversary').slideDown();
                } else {
                    $('#wrap_anniversary').slideUp();
                    $('#f_anniversary').val('');
                }
            });

            function applyScopeUI() {
                let isGod = window.userGodMode || false;
                let isDir = false;

                if (window.userContext) {
                    isGod = isGod || window.userContext.is_god || window.userContext.role_level === 'developer';
                    isDir = window.userContext.is_director || window.userContext.role_level === 'director';
                } else {
                    let roleText = $('.user-role-display').text().toLowerCase();
                    if (roleText.includes('director')) isDir = true;
                }

                if (isGod) {
                    $('#wrap_company, #wrap_branch').show();
                    $('#f_company').prop('required', true);
                } else if (isDir) {
                    $('#wrap_company').hide();
                    $('#wrap_branch').show();
                } else {
                    $('#wrap_company, #wrap_branch').hide();
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
                    }
                });
            }
            loadCompanies();

            function loadMembersForDatalist(cId = '', bId = '') {
                let url = '/api/v1/members?';
                if (cId) url += 'company_id=' + cId + '&';
                if (bId) url += 'branch_id=' + bId;

                $.ajax({
                    url: url,
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        let options = '';
                        memberMap = {};
                        res.data.forEach(m => {
                            let disp = `${m.member_name} (${m.member_id})`;
                            options += `<option value="${disp}">`;
                            memberMap[disp] = m.member_id;
                        });
                        $('#memberListOptions').html(options);
                    }
                });
            }
            loadMembersForDatalist();

            // 🔥 SMART BRANCH VALIDATION (BLUR / CHANGE ONLY) 🔥
            function validateBranchField() {
                let val = $('#f_branch').val().trim();
                let errorSpan = $('#branch_error');
                let hiddenId = $('#branch_id_hidden');
                let companyId = $('#hidden_company_id').val();

                if (val === '') {
                    // Blank = Head Office
                    hiddenId.val('');
                    errorSpan.hide().text('');
                    document.getElementById('f_branch').setCustomValidity('');
                    loadMembersForDatalist(companyId, '');
                } else if (branchMap[val]) {
                    // Exact Match (Perfect)
                    hiddenId.val(branchMap[val]);
                    errorSpan.hide().text('');
                    document.getElementById('f_branch').setCustomValidity('');
                    loadMembersForDatalist(companyId, branchMap[val]);
                } else {
                    // Check if user is currently typing a valid substring
                    let isTyping = false;
                    for (let key in branchMap) {
                        if (key.toLowerCase().includes(val.toLowerCase())) {
                            isTyping = true;
                            break;
                        }
                    }

                    if (isTyping) {
                        // Matching partially (Don't show error yet)
                        hiddenId.val('');
                        errorSpan.hide().text('');
                        document.getElementById('f_branch').setCustomValidity('Incomplete branch name');
                    } else {
                        // Completely wrong or Wrong Autofill Selection
                        hiddenId.val('');
                        errorSpan.text('❌ This branch does not belong to the selected company!').show();
                        document.getElementById('f_branch').setCustomValidity('Invalid branch');
                        loadMembersForDatalist(companyId, '');
                    }
                }
            }

            $('#f_company').on('change blur', function() {
                let val = $(this).val();
                if (companyMap[val]) {
                    $('#hidden_company_id').val(companyMap[val]);
                    loadMembersForDatalist(companyMap[val], $('#branch_id_hidden').val());

                    $.ajax({
                        url: '/api/v1/branches?company_id=' + companyMap[val],
                        type: 'GET',
                        success: function(res) {
                            let bOpts = '';
                            branchMap = {};
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

            // Trigger on input, change, and blur to catch Autofill instantly
            $('#f_branch').on('input change blur', function() {
                validateBranchField();
            });

            // 🔥 NATIVE DATATABLES EXCEL BUTTON 🔥
           // 🔥 PHP ka dependency khatam kiya. Button humesha push hoga, par 'secured-item' usko hide/show karega 🔥
            let dtButtons = [{
                extend: 'excelHtml5',
                className: 'btn btn-success btn-sm shadow-sm rounded-3 mt-1 secured-item', // secured-item class zaroori hai
                attr: {
                    'data-permission': 'customer_export' // Action Slug yahan aayega
                },
                text: '<i class="fas fa-file-excel me-1"></i> Export Excel',
                action: function(e, dt, button, config) {
                    let oldLength = dt.page.len();
                    dt.page.len(-1).draw();
                    dt.one('draw', function() {
                        $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, button, config);
                        dt.page.len(oldLength).draw();
                    });
                }
            }];
            if (window.canExport) {
                dtButtons.push({
                    extend: 'excelHtml5',
                    className: 'btn btn-success btn-sm shadow-sm rounded-3 mt-1',
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

            let table = $('#customerTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/customers',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    }
                },
                // 🔥 DOM layout fixed: Excel on left (col-sm-6), Search on right (col-sm-6) 🔥
                dom: '<"row mb-3 align-items-center"<"col-sm-6"B><"col-sm-6 text-md-end"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: dtButtons,
                columns: [{
                        data: 'customer_id',
                        render: d => `<span class="fw-bold text-primary">${d}</span>`
                    },
                    {
                        data: 'branch_id',
                        render: (d, t, row) => {
                            let compName = 'N/A';
                            let branchName = 'Head Office';

                            if (row.branch && row.branch.company) {
                                compName = row.branch.company.company_name;
                                branchName = row.branch.branch_name;
                            } else if (row.company_id) {
                                compName = 'HQ - Primary Company';
                            }
                            return `<div class="small fw-bold text-primary"><i class="fas fa-building me-1"></i> ${compName}</div>
                                    <div class="small text-muted"><i class="fas fa-map-marker-alt text-danger me-1"></i> ${branchName}</div>`;
                        }
                    },
                    {
                        data: 'customer_name'
                    },
                    {
                        data: 'customer_mobile'
                    },
                    {
                        data: 'status',
                        render: function(d) {
                            let badgeClass = 'bg-secondary';
                            if (d === 'active') badgeClass = 'bg-success';
                            else if (d === 'pending') badgeClass = 'bg-warning text-dark';
                            else if (d === 'inactive') badgeClass = 'bg-danger';
                            return `<span class="badge ${badgeClass}">${d ? d.toUpperCase() : 'N/A'}</span>`;
                        }
                    },
                    {
                        data: 'created_by',
                        render: function(d) {
                            if (!d) return '<span class="text-muted">N/A</span>';
                            
                            // Check if format is "Name (ID)"
                            let match = d.match(/(.*)\s*\((.*?)\)/);
                            if (match) {
                                return `
                                <div class="text-start">
                                    <span class="d-block fw-bold text-dark" style="font-size:12px;"><i class="fas fa-user-tie text-secondary me-1"></i> ${match[1]}</span>
                                    <span class="badge bg-primary-subtle text-primary border mt-1" style="font-size:10.5px;">ID: ${match[2]}</span>
                                </div>`;
                            }
                            // Fallback
                            return `<span class="created-badge"><i class="fas fa-user-edit me-1"></i>${d}</span>`;
                        }
                    },
                    {
                        data: 'id',
                        orderable: false,
                        className: 'text-end text-nowrap',
                        render: function(d) {
                            let isGod = window.userGodMode || false;
                            let p = window.userPerms || [];

                            let hasView = isGod || p.includes(window.moduleBase + '_view');
                            let hasEdit = isGod || p.includes(window.moduleBase + '_edit');
                            let hasDelete = isGod || p.includes(window.moduleBase + '_delete');
                            let hasRestore = isGod || p.includes(window.moduleBase + '_restore');

                            let btns = '';
                            if (hasView) btns +=
                                `<button type="button" class="btn btn-sm btn-light text-info view-btn" data-id="${d}"><i class="fas fa-eye"></i></button>`;
                            if (hasEdit) btns +=
                                `<button type="button" class="btn btn-sm btn-light text-primary edit-btn" data-id="${d}"><i class="fas fa-edit"></i></button>`;
                            if (hasDelete) btns +=
                                `<button type="button" class="btn btn-sm btn-light text-danger delete-btn" data-id="${d}"><i class="fas fa-trash-alt"></i></button>`;
                            if (hasRestore) btns +=
                                `<button type="button" class="btn btn-sm btn-light text-success restore-btn" data-id="${d}" title="Restore"><i class="fas fa-trash-restore"></i></button>`;

                            if (!btns)
                            return `<span class="text-muted small"><i class="fas fa-lock"></i> Locked</span>`;
                            return `<div class="d-flex justify-content-end gap-1">${btns}</div>`;
                        }
                    }
                ],
                drawCallback: function(settings) {
                    renderMobileCards(settings.json.data);
                }
            });

            function renderMobileCards(data) {
                let html = '';
                if (!data || data.length === 0) {
                    html =
                        '<div class="text-center p-3 text-muted border rounded bg-light">No customers found.</div>';
                } else {
                    let isGod = window.userGodMode || false;
                    let p = window.userPerms || [];

                    let hasView = isGod || p.includes(window.moduleBase + '_view');
                    let hasEdit = isGod || p.includes(window.moduleBase + '_edit');
                    let hasDelete = isGod || p.includes(window.moduleBase + '_delete');
                    let hasRestore = isGod || p.includes(window.moduleBase + '_restore');

                    data.forEach(d => {
                        let compName = d.branch && d.branch.company ? d.branch.company.company_name :
                            'HQ - Primary Company';
                        let branchName = d.branch ? d.branch.branch_name : 'Head Office';
                        let creator = d.created_by ?
                            `<span class="created-badge float-end"><i class="fas fa-user-edit me-1"></i>${d.created_by}</span>` :
                            '';

                        let statusBadge = '';
                        if (d.status === 'active') statusBadge =
                            `<span class="badge bg-success float-end mt-1">Active</span>`;
                        else if (d.status === 'pending') statusBadge =
                            `<span class="badge bg-warning text-dark float-end mt-1">Pending</span>`;
                        else if (d.status === 'inactive') statusBadge =
                            `<span class="badge bg-danger float-end mt-1">Inactive</span>`;

                        let actionBtns = '';
                        if (hasView) actionBtns +=
                            `<button type="button" class="btn btn-sm btn-light text-info flex-fill view-btn" data-id="${d.id}">View</button>`;
                        if (hasEdit) actionBtns +=
                            `<button type="button" class="btn btn-sm btn-light text-primary flex-fill edit-btn" data-id="${d.id}">Edit</button>`;
                        if (hasDelete) actionBtns +=
                            `<button type="button" class="btn btn-sm btn-light text-danger flex-fill delete-btn" data-id="${d.id}">Delete</button>`;
                        if (hasRestore) actionBtns +=
                            `<button type="button" class="btn btn-sm btn-light text-success flex-fill restore-btn" data-id="${d.id}">Restore</button>`;

                        if (!actionBtns) actionBtns =
                            `<span class="small fw-bold text-muted w-100 text-center"><i class="fas fa-lock"></i> Locked</span>`;

                        html += `<div class="mobile-item">
                            <h6 class="fw-bold text-dark">${d.customer_name} ${creator}</h6>
                            <div class="small fw-bold text-primary mt-2">${d.customer_id} ${statusBadge}</div>
                            <div class="small text-muted mb-1"><i class="fas fa-building text-info me-1"></i> ${compName} - ${branchName}</div>
                            <div class="small text-muted"><i class="fas fa-phone me-1"></i> ${d.customer_mobile}</div>
                           <div class="mt-2 pt-2 border-top d-flex gap-2">${actionBtns}</div>
                        </div>`;
                    });
                }
                $('#mobileCardsContainer').html(html);
            }

            $('#mobileSearch').on('keyup', function() {
                let v = $(this).val().toLowerCase();
                $('.mobile-item').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1)
                });
            });

            $('#member_search_input').on('input change', function() {
                let val = $(this).val();
                if (memberMap[val]) {
                    $('#f_member_id').val(memberMap[val]);
                } else {
                    $('#f_member_id').val('');
                }
            });

            $('input[type="file"]').each(function() {
                $(this).after(`
                    <div class="file-preview-wrapper">
                        <button type="button" class="btn btn-danger remove-preview-btn" title="Remove File"><i class="fas fa-times"></i></button>
                        <div class="preview-content text-center"></div>
                    </div>
                `);
            });

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

           window.openModal = function(type, id = null) {
                mode = type;
                $('#customerForm')[0].reset();
                $('#branch_id_hidden, #hidden_company_id, #f_member_id').val('');
                $('#wrap_anniversary').hide();
                $('#branch_error').hide();
                document.getElementById('f_branch').setCustomValidity('');

                // 🔥 NAYA FOOLPROOF TEXT LOGIC 🔥
                // Seedha bahar wale button ka text padho, agar usme Request likha hai to modal bhi Request mode me jayega
                let isReq = $('#addCustomerBtn').text().toLowerCase().includes('request');

                if (type === 'add') {
                    if (isReq) {
                        $('#modalTitle').html('<i class="fas fa-paper-plane me-2 text-warning"></i> Request Customer Registration');
                        $('#saveBtn').text('Submit Customer Request');
                    } else {
                        $('#modalTitle').html('<i class="fas fa-user-plus me-2 text-primary"></i> Register Customer');
                        $('#saveBtn').text('Save Customer Details');
                    }
                    $('.password-div').hide();
                    $('.status-div').hide();
                } else {
                    $('#modalTitle').html('<i class="fas fa-edit me-2 text-primary"></i> Edit Customer Details');
                    $('#saveBtn').text('Update Customer Details');
                    $('.password-div').show();
                    $('.status-div').show();
                }

                applyScopeUI();


                $('#modalTitle').text(type === 'add' ? 'Register Customer' : 'Edit Customer');
                $('.nav-tabs button:first').tab('show');
                $('.file-preview-wrapper').hide().find('.preview-content').empty();

                applyScopeUI();

                if (type === 'add') {
                    $('.password-div').hide();
                    $('.status-div').hide();
                } else {
                    $('.password-div').show();
                    $('.status-div').show();
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

                            if (cust.branch) {
                                let compName = cust.branch.company ? cust.branch.company
                                    .company_name : 'Master Company';
                                let disp =
                                    `${compName} - ${cust.branch.branch_name} (${cust.branch.branch_id})`;
                                $('#f_branch').val(disp);
                                $('#branch_id_hidden').val(cust.branch_id);
                            }

                            if (cust.member_id) {
                                let memberDisp = Object.keys(memberMap).find(key => memberMap[
                                    key] === cust.member_id);
                                if (memberDisp) {
                                    $('#member_search_input').val(memberDisp);
                                    $('#f_member_id').val(cust.member_id);
                                }
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

                if (!this.checkValidity()) {
                    return;
                }

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
                        $('#v_booking').text(d.booking_date || 'N/A');
                        $('#v_marital').text(d.marital_status || 'N/A');

                        if (d.marital_status === 'Married' && d.date_of_anniversary) {
                            $('#view_anniversary_wrap').show();
                            $('#v_anniversary').text(d.date_of_anniversary);
                        } else {
                            $('#view_anniversary_wrap').hide();
                        }

                        $('#v_nom_name').text(d.nominee_name || 'N/A');
                        $('#v_nom_relation').text(d.nominee_relation || 'N/A');
                        $('#v_nom_mobile').text(d.nominee_mobile || 'N/A');

                        $('#viewModal').modal('show');
                    }
                });
            });

            // 🔥 YAHAN EDIT BUTTON KA LISTENER ADD KIYA GAYA HAI 🔥
            $(document).on('click', '.edit-btn', function() {
                let id = $(this).data('id');
                window.openModal('edit', id);
            });


            $(document).on('click', '.delete-btn', function() {
                if (confirm("Delete Customer?")) {
                    $.ajax({
                        url: `/api/v1/customers/${$(this).data('id')}`,
                        type: 'DELETE',
                        headers: {
                            'Authorization': 'Bearer ' + apiToken
                        },
                        success: function() {
                            table.ajax.reload(null, false);
                            showMessage('Deleted successfully!', 'success');
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
                        success: function() {
                            table.ajax.reload(null, false);
                            showMessage('Restored successfully!', 'success');
                        },
                        error: function(err) {
                            showMessage(err.responseJSON?.message || 'Unauthorized Action',
                                'error');
                        }
                    });
                }
            });

        });
    </script>
@endpush
