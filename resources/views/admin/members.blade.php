
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
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Member Details</h4>
            </div>
            <button type="button" class="btn text-white px-3 py-2 shadow-sm secured-item" data-permission="member_add"
                style="background-color: var(--brand-primary);" onclick="openModal('add')">
                <i class="fas fa-plus me-1"></i> Add New Member
            </button>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search Member...">
            <button type="button" class="btn text-white shadow-sm" style="background-color: #10b981;"
                id="mobileExcelBtn"><i class="fas fa-file-excel"></i></button>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="memberTable" class="table table-hover table-custom w-100">
                        <thead>
                            <tr>
                                <th>MEMBER ID</th>
                                <th>Branch</th>
                                <th>Name</th>
                                <th>Sponsor ID</th>
                                <th>Mobile</th>
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

                        <div class="col-12 mt-3">
                            <h6 class="fw-bold text-secondary border-bottom pb-2">Nominee Info</h6>
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

                                <div class="row g-3 mb-4 pb-3 border-bottom">

                                    <div class="col-md-4" id="wrap_company">
                                        <label class="form-label text-secondary small">Company <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="f_company" class="form-control" list="companyList"
                                            placeholder="Search Company" autocomplete="off" required>
                                        <input type="hidden" name="company_id" id="hidden_company_id">
                                        <datalist id="companyList"></datalist>
                                    </div>

                                    <div class="col-md-4" id="wrap_branch">
                                        <label class="form-label text-secondary small">Branch <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="f_branch" class="form-control" list="branchList"
                                            placeholder="Search Branch" autocomplete="off" required>
                                        <input type="hidden" name="branch_id" id="hidden_branch_id">
                                        <datalist id="branchList"></datalist>
                                    </div>

                                    <div class="col-md-4" id="wrap_dept">
                                        <label class="form-label text-secondary small">Department <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="f_department" class="form-control" list="deptList"
                                            placeholder="Search Department" autocomplete="off" required>
                                        <input type="hidden" name="department_id" id="hidden_department_id">
                                        <datalist id="deptList"></datalist>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Sponsor ID</label>
                                        <input type="text" name="sponsor_id" class="form-control fw-bold"
                                            id="f_sponsor_id" list="sponsorList" placeholder="Type or select Sponsor ID"
                                            autocomplete="off">
                                        <datalist id="sponsorList"></datalist>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Sponsor Name</label>
                                        <input type="text" name="sponsor_name" class="form-control bg-light fw-bold"
                                            id="f_sponsor_name" readonly placeholder="Enter Sponsor Name">
                                    </div>

                                    <div class="col-md-4 password-edit-div" style="display:none;">
                                        <label class="form-label text-secondary small">Login Password <span
                                                class="text-info">(Editable)</span></label>
                                        <input type="text" name="password" id="f_password" class="form-control"
                                            placeholder="Update password">
                                    </div>
                                    <div class="col-md-4 password-gen-div">
                                        <label class="form-label text-secondary small">Auto Password</label>
                                        <input type="text" id="mem_pass_gen" class="form-control bg-light" readonly>
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
                                        <label class="form-label small fw-bold">Designation <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="designation" id="designation" class="form-control"
                                            list="designationList" placeholder="Type or Select Designation" required
                                            autocomplete="off">
                                        <datalist id="designationList"></datalist>
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
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label text-secondary small">Account Holder
                                            Name</label><input type="text" name="account_name" class="form-control"
                                            id="f_acc_name"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Bank A/c
                                            No.</label><input type="text" name="account_no" class="form-control"
                                            id="f_acc_no"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Select Account
                                            Type</label>
                                        <select class="form-select" name="account_type" id="f_acc_type">
                                            <option value="">-- Select Type --</option>
                                            <option value="saving">Saving</option>
                                            <option value="current">Current</option>
                                            <option value="cc">CC</option>
                                            <option value="od">OD</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Bank
                                            Name</label><input type="text" name="bank_name" class="form-control"
                                            id="f_bank_name"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Branch
                                            Name</label>
                                        <input type="text" name="branch" class="form-control" id="f_bank_branch">
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">IFSC
                                            Code</label><input type="text" name="ifsc_code"
                                            class="form-control text-uppercase" id="f_ifsc"></div>
                                </div>
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
        $(document).ready(function() {
            const apiToken = localStorage.getItem('admin_token');
            let mode = 'add';
            let allMembers = [];
            let allDesignations = [];

            // Maps for converting DataList Text -> ID
            let companyMap = {};
            let branchMap = {};
            let deptMap = {};

            // ==========================================
            // 🔥 ROLE-BASED SPONSOR & UI LOGIC (LOCALSTORAGE FALLBACK FIX) 🔥
            // ==========================================
            let userEmail = (localStorage.getItem('user_email') || localStorage.getItem('admin_email') || '').toLowerCase();
            let roleLevel = localStorage.getItem('user_role') || ''; 
            let loggedInMemberId = localStorage.getItem('member_id') || '';
            let loggedInMemberName = localStorage.getItem('user_name') || '';
            let loggedInDesignation = localStorage.getItem('designation') || '';

            // 🔥 NAYA FALLBACK LOGIC 🔥
            // Agar localStorage me role save nahi hai, lekin 'admin_token' mojood hai, toh usko CEO maan lo
            if (!roleLevel && localStorage.getItem('admin_token')) {
                roleLevel = 'ceo';
                userEmail = userEmail || 'admin@jankivilla.com'; // Default admin email
            } else if (!roleLevel) {
                roleLevel = 'member'; // Warna normal member
            }

            // Developer Override Check (Hardcoded Emails)
            const devEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
            if (devEmails.includes(userEmail) || roleLevel === 'ceo' || roleLevel === 'super_admin' || typeof window.userGodMode !== 'undefined') {
                roleLevel = 'ceo';
            }

            // Boolean flag for all admin types
            const isUserAdmin = (roleLevel === 'ceo' || roleLevel === 'director' || roleLevel === 'branch_manager');

            function applyRoleUI() {
                if (roleLevel === 'ceo') {
                    $('#wrap_company, #wrap_branch, #wrap_dept').show();
                    $('#f_sponsor_id').prop('readonly', false);
                } else if (roleLevel === 'director') {
                    $('#wrap_company').hide();
                    $('#wrap_branch, #wrap_dept').show();
                    $('#f_sponsor_id').prop('readonly', false);
                } else if (roleLevel === 'branch_manager') {
                    $('#wrap_company, #wrap_branch').hide();
                    $('#wrap_dept').show();
                    $('#f_sponsor_id').prop('readonly', false);
                } else {
                    // Normal Associate Member
                    $('#wrap_company, #wrap_branch, #wrap_dept').hide();
                    
                    // Lock Sponsor to Self
                    $('#f_sponsor_id').val(loggedInMemberId).prop('readonly', true);
                    $('#f_sponsor_name').val(loggedInMemberName).prop('readonly', true);
                    
                    // Trigger manual change to fetch appropriate child designations
                    $('#f_sponsor_id').trigger('change');
                }
            }

            // ==========================================
            // CASCADING DATALISTS LOGIC
            // ==========================================
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

            // 1. Company Change -> Fetch Branches
            $('#f_company').on('input change', function() {
                let val = $(this).val();
                if (companyMap[val]) {
                    $('#hidden_company_id').val(companyMap[val]);
                    this.setCustomValidity('');

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
                            $('#f_branch').val('');
                            $('#hidden_branch_id').val('');
                            $('#f_department').val('');
                            $('#hidden_department_id').val('');
                        }
                    });
                } else {
                    $('#hidden_company_id').val('');
                    if (val !== '') this.setCustomValidity('Please select a valid company');
                }
            });

            // 2. Branch Change -> Fetch Departments
            $('#f_branch').on('input change', function() {
                let val = $(this).val();
                if (branchMap[val]) {
                    $('#hidden_branch_id').val(branchMap[val]);
                    this.setCustomValidity('');

                    $.ajax({
                        url: '/api/v1/get-departments-by-company?company_id=' + $(
                            '#hidden_company_id').val(),
                        type: 'GET',
                        success: function(res) {
                            let dOpts = '';
                            deptMap = {};
                            res.data.forEach(d => {
                                dOpts += `<option value="${d.department_name}">`;
                                deptMap[d.department_name] = d.id;
                            });
                            $('#deptList').html(dOpts);
                            $('#f_department').val('');
                            $('#hidden_department_id').val('');
                        }
                    });
                } else {
                    $('#hidden_branch_id').val('');
                    if (val !== '') this.setCustomValidity('Please select a valid branch');
                }
            });

            // 3. Department Change -> Fetch Designations & Auto-Sponsor Logic
            $('#f_department').on('input change', function() {
                let val = $(this).val();
                let deptId = deptMap[val];

                if (deptId) {
                    $('#hidden_department_id').val(deptId);
                    this.setCustomValidity('');

                    // Fetch Designations for this department
                    $.ajax({
                        url: '/api/v1/get-designations-by-dept?department_id=' + deptId,
                        type: 'GET',
                        success: function(res) {
                            allDesignations = res.data;

                            // Agar normal member hai, to turant re-trigger sponsor logic
                            if (!isUserAdmin) {
                                $('#f_sponsor_id').trigger('change');
                            } else {
                                renderDesignations(); // CEO wagaira ke liye all show karo
                            }
                        }
                    });

                    // 🔥 AUTO SPONSOR LOGIC (Only for Admin Roles)
                    if (isUserAdmin) {
                        let branchId = $('#hidden_branch_id').val();
                        let membersInBranch = allMembers.filter(m => m.branch_id == branchId);

                        if (membersInBranch.length === 0) {
                            let compStr = $('#f_company').val() ? $('#f_company').val().substring(0, 3)
                                .toUpperCase() : 'CMP';
                            let brStr = $('#f_branch').val() ? $('#f_branch').val().substring(0, 3)
                                .toUpperCase() : 'BRN';
                            let autoSponsorId = `${compStr}-${brStr}-0001`;

                            $('#f_sponsor_id').val(autoSponsorId).prop('readonly', true);
                            $('#f_sponsor_name').val('SYSTEM ROOT').prop('readonly', true);
                        } else {
                            $('#f_sponsor_id').val('').prop('readonly', false);
                            $('#f_sponsor_name').val('').prop('readonly', true);
                        }
                    }

                } else {
                    $('#hidden_department_id').val('');
                    if (val !== '') this.setCustomValidity('Please select a valid department');
                }
            });


            // ==========================================
            // 🔥 SPONSOR SELECTION & DESIGNATION FILTER 🔥
            // ==========================================
            function renderDesignations(maxComm = null) {
                let options = '';
                allDesignations.forEach(function(item) {
                    // Agar koi max commission set nahi hai, YA FIR current item ka commission sponsor se chota hai
                    if (maxComm === null || parseFloat(item.commission_percentage || 0) < parseFloat(
                            maxComm)) {
                        options += `<option value="${item.designation_name}">`;
                    }
                });
                $('#designationList').html(options);
            }

            $('#f_sponsor_id').on('input change', function() {
                let val = $(this).val();

                // User Administrator Nahi Hai (Associate Member logic)
                if (!isUserAdmin) {
                    $('#f_sponsor_name').val(loggedInMemberName);
                    // Ab backend se fetch ki gayi allDesignations mein iski post dhundte hain
                    if (allDesignations.length > 0) {
                        let myDesigObj = allDesignations.find(d => d.designation_name ===
                            loggedInDesignation);
                        if (myDesigObj) {
                            renderDesignations(myDesigObj.commission_percentage); // Filter laga diya
                        } else {
                            renderDesignations();
                        }
                    }
                    return; // Normal logic bypass
                }

                // Administrator Logic
                if (val === '') {
                    $('#f_sponsor_name').val('');
                    renderDesignations(); // Show all designations
                } else {
                    let found = allMembers.find(m => m.member_id === val);
                    if (found) {
                        $('#f_sponsor_name').val(found.member_name);
                        let sponsorDesigObj = allDesignations.find(d => d.designation_name === found
                            .designation);

                        if (sponsorDesigObj) {
                            renderDesignations(sponsorDesigObj.commission_percentage); // Filter laga diya
                        } else {
                            renderDesignations();
                        }
                    } else {
                        // Custom sponsor id typing scenario
                        if (!$(this).prop('readonly')) {
                            $('#f_sponsor_name').val('');
                            renderDesignations();
                        }
                    }
                }
            });


            // ==========================================
            // DataTables
            // ==========================================
            let table = $('#memberTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/members', // /admin/ removed
                },
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export Excel',
                    className: 'btn btn-success btn-sm shadow-sm rounded-3'
                }],
                columns: [{
                        data: 'member_id',
                        render: d => `<span class="fw-bold text-primary">${d}</span>`
                    },
                    {
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
                        data: 'member_name'
                    },
                    {
                        data: 'sponsor_id',
                        render: d => d ? d : 'N/A'
                    },
                    {
                        data: 'mobile'
                    },
                    {
                        data: 'id',
                        render: d => `
                <div class="text-end">
                    <button type="button" class="btn btn-sm btn-light text-info me-1 view-btn" data-id="${d}"><i class="fas fa-eye"></i></button>
                    <button type="button" class="btn btn-sm btn-light text-primary me-1 edit-btn secured-item" data-permission="member_edit" data-id="${d}"><i class="fas fa-edit"></i></button>
                    <button type="button" class="btn btn-sm btn-light text-danger delete-btn secured-item" data-permission="member_delete" data-id="${d}"><i class="fas fa-trash-alt"></i></button>
                </div>`
                    }
                ],
                drawCallback: function(settings) {
                    renderMobileCards(settings.json.data);
                    if (typeof window.applyPermissions === 'function') window.applyPermissions();
                }

            });

            function renderMobileCards(data) {
                let html = '';
                if (!data || data.length === 0) {
                    html =
                    '<div class="text-center p-3 text-muted border rounded bg-light">No members found.</div>';
                } else {
                    data.forEach(d => {
                        let branchName = d.branch ? d.branch.branch_name : 'N/A';
                        html += `<div class="mobile-item">
                            <h6 class="fw-bold text-dark">${d.member_name} <span class="float-end text-primary small">${d.member_id}</span></h6>
                            <div class="small text-muted mb-1"><i class="fas fa-map-marker-alt text-danger me-1"></i> ${branchName}</div>
                            <div class="small text-muted"><i class="fas fa-phone me-1"></i> ${d.mobile}</div>
                            <div class="mt-2 pt-2 border-top d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-light text-info flex-fill view-btn" data-id="${d.id}">View</button>
                                <button type="button" class="btn btn-sm btn-light text-primary flex-fill edit-btn secured-item" data-permission="member_edit" data-id="${d.id}">Edit</button>
                                <button type="button" class="btn btn-sm btn-light text-danger flex-fill delete-btn secured-item" data-permission="member_delete" data-id="${d.id}">Delete</button>
                            </div>
                        </div>`;
                    });
                }
                $('#mobileCardsContainer').html(html);
                if (typeof window.applyPermissions === 'function') window.applyPermissions();
            }

            $('#mobileSearch').on('keyup', function() {
                let v = $(this).val().toLowerCase();
                $('.mobile-item').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1)
                });
            });

            // Load All Members for Sponsor List
            function loadSponsorsList() {
                $.ajax({
                    url: '/api/v1/members',
                    type: 'GET',
                    success: function(res) {
                        allMembers = res.data;
                        let options = '';
                        res.data.forEach(m => {
                            options +=
                                `<option value="${m.member_id}">${m.member_name} (${m.member_id})</option>`;
                        });
                        $('#sponsorList').html(options);
                    }
                });
            }
            loadSponsorsList();


            // ==========================================
            // Modals
            // ==========================================
            window.openModal = function(type, id = null) {
                mode = type;
                $('#memberForm')[0].reset();
                $('#edit_id').val('');
                $('#form_method').val('POST');

                // Clear hidden IDs
                $('#hidden_company_id, #hidden_branch_id, #hidden_department_id').val('');

                $('#f_sponsor_name').prop('readonly', true).addClass('bg-light');
                $('#modalTitle').text(type === 'add' ? 'Register Member' : 'Edit Member');
                $('.file-preview-wrapper').hide().find('.preview-content').empty();
                $('.nav-tabs button:first').tab('show');

                if (type === 'add') {
                    $('.password-edit-div').hide();
                    $('.password-gen-div').show();
                    applyRoleUI(); // Yahan call hoga applyRoleUI
                } else {
                    $('.password-edit-div').show();
                    $('.password-gen-div').hide();

                    // Fetch Data for Edit
                    $.get({
                        url: `/api/v1/members/${id}`,
                        success: function(res) {
                            let d = res.data;
                            $('#edit_id').val(d.id);
                            $('#form_method').val('PUT');

                            // Set Hidden IDs
                            $('#hidden_company_id').val(d.company_id);
                            $('#hidden_branch_id').val(d.branch_id);
                            $('#hidden_department_id').val(d.department_id);

                            // Setup visual inputs
                            if (d.company) $('#f_company').val(d.company.company_name);
                            if (d.branch) $('#f_branch').val(d.branch.branch_name);
                            if (d.department) $('#f_department').val(d.department.department_name);

                            // Smart Populating Logic
                            Object.keys(d).forEach(key => {
                                let input = $(`#memberForm [name="${key}"]`);
                                if (input.length && input.attr('type') !== 'file' && input
                                    .attr('type') !== 'radio') {
                                    if (typeof d[key] === 'object' && d[key] !== null)
                                        return;
                                    input.val(d[key]);
                                }
                            });

                            $('#f_bank_branch').val(d.bank_branch_text);

                            // Trigger sponsor event to load hierarchy if sponsor exists
                            if (d.sponsor_id) {
                                $('#f_sponsor_id').trigger('change');
                            }

                            if (d.gender) $(`input[name="gender"][value="${d.gender}"]`).prop(
                                'checked', true);
                            if (d.marital_status) $(
                                    `input[name="marital_status"][value="${d.marital_status}"]`)
                                .prop('checked', true);

                            // Edit mode me UI theek karne ke liye applyRoleUI chalega
                            applyRoleUI();
                        }
                    });
                }
                $('#memberModal').modal('show');
            };

            $(document).on('click', '.edit-btn', function() {
                openModal('edit', $(this).data('id'));
            });

            // Form Submit
            $('#memberForm').submit(function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                let id = $('#edit_id').val();
                let url = mode === 'add' ? '/api/v1/members' : `/api/v1/members/${id}`;
                if (mode === 'edit') formData.append('_method', 'PUT');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        alert(res.message);
                        $('#memberModal').modal('hide');
                        table.ajax.reload(null, false);
                        loadSponsorsList(); // Refresh sponsors
                    }
                });
            });

            // Delete Logic
            $(document).on('click', '.delete-btn', function() {
                if (confirm("Delete Member?")) {
                    $.ajax({
                        url: `/api/v1/members/${$(this).data('id')}`,
                        type: 'DELETE',
                        success: function() {
                            table.ajax.reload(null, false);
                            loadSponsorsList();
                        }
                    });
                }
            });

            // Password Generator Logic
            function generatePassword() {
                let fullName = $('#f_name').val().trim();
                let aadhar = $('#f_aadhar').val().replace(/\D/g, '');
                if (fullName.length < 1 || aadhar.length < 4) {
                    $('#mem_pass_gen').val('');
                    return;
                }
                let firstNamePart = fullName.split(' ')[0].substring(0, 3).toLowerCase();
                let formattedName = firstNamePart.charAt(0).toUpperCase() + firstNamePart.slice(1);
                let aadharLast4 = aadhar.slice(-4);
                $('#mem_pass_gen').val(formattedName + '@' + aadharLast4);
            }
            $('#f_name, #f_aadhar').on('keyup change', generatePassword);

            // View Logic
            $(document).on('click', '.view-btn', function() {
                $.get({
                    url: `/api/v1/members/${$(this).data('id')}`,
                    success: function(res) {
                        let d = res.data;
                        $('#v_mem_id').text(d.member_id || 'N/A');
                        $('#v_password').text(d.password || 'N/A');

                        let branchText = 'N/A';
                        if (d.branch) {
                            let compName = d.branch.company ? d.branch.company.company_name :
                                'Master Company';
                            branchText = compName + ' - ' + d.branch.branch_name;
                        }
                        $('#v_branch').text(branchText);

                        $('#v_sponsor').text(d.sponsor_name ?
                            `${d.sponsor_name} (${d.sponsor_id})` : (d.sponsor_id || 'N/A'));
                        $('#v_name').text(d.member_name || 'N/A');
                        $('#v_designation').text(d.designation || 'N/A');
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

            // Previews
            $('input[type="file"]').each(function() {
                $(this).after(
                    `<div class="file-preview-wrapper"><button type="button" class="btn btn-danger remove-preview-btn"><i class="fas fa-times"></i></button><div class="preview-content text-center"></div></div>`
                    );
            });

            $(document).on('change', 'input[type="file"]', function() {
                let file = this.files[0];
                let wrapper = $(this).next('.file-preview-wrapper');
                let content = wrapper.find('.preview-content');
                if (file) {
                    if (file.type.startsWith('image/')) {
                        let reader = new FileReader();
                        reader.onload = e => {
                            content.html(
                                `<img src="${e.target.result}" style="max-height:80px; border-radius:6px;">`
                                );
                            wrapper.slideDown();
                        }
                        reader.readAsDataURL(file);
                    } else {
                        content.html(
                            `<div class="p-2 small fw-bold text-dark"><i class="fas fa-file-alt text-primary me-2"></i>${file.name}</div>`
                            );
                        wrapper.slideDown();
                    }
                }
            });

            $(document).on('click', '.remove-preview-btn', function() {
                $(this).closest('.file-preview-wrapper').prev('input[type="file"]').val('');
                $(this).closest('.file-preview-wrapper').slideUp();
            });

        });
    </script>
@endpush
