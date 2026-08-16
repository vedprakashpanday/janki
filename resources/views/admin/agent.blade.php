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

        /* File Preview Wrapper */
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
            <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Agent Details</h4>
            <button type="button" class="btn text-white px-3 py-2 shadow-sm secured-item" data-permission="agent_add" style="background-color: var(--brand-primary);" onclick="openModal('add')">
    <i class="fas fa-plus me-1"></i> Register Agent
</button>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search Agent...">
            <button type="button" class="btn text-white shadow-sm" style="background-color: #10b981;"
                id="mobileExcelBtn"><i class="fas fa-file-excel"></i></button>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="agentTable" class="table table-hover table-custom w-100">
                        <thead>
                            <tr>
                                <th>AGENT ID</th>
                                <th>Branch</th>
                                <th>Name</th>
                                <th>Mobile</th>
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

    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i
                            class="fas fa-eye me-2 text-info"></i> Agent Overview</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold text-primary mb-3">Login Credentials</h6>
                                <p class="mb-1"><strong>Agent ID:</strong> <span id="v_agent_id" class="text-dark"></span>
                                </p>
                                <p class="mb-0"><strong>Password:</strong> <span id="v_password"
                                        class="text-danger fw-bold"></span></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light h-100">
                                <h6 class="fw-bold text-primary mb-3">Professional Info</h6>
                                <p class="mb-1"><strong>Branch:</strong> <span id="v_branch" class="text-dark"></span>
                                </p>
                                <p class="mb-0"><strong>Joining Date:</strong> <span id="v_joining"
                                        class="text-dark"></span></p>
                                <p class="mb-0 mt-2"><strong>Status:</strong> <span id="v_status"
                                        class="badge bg-success"></span></p>
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
                            <p class="small text-muted mb-0">Email</p>
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

                        <div class="col-12 mt-3">
                            <h6 class="fw-bold text-secondary border-bottom pb-2">Nominee Info</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Nominee Name</p>
                            <h6 class="fw-bold" id="v_nom_name"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Relation</p>
                            <h6 class="fw-bold" id="v_nom_rel"></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="small text-muted mb-0">Nominee Mobile</p>
                            <h6 class="fw-bold" id="v_nom_mob"></h6>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="agentModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold" id="modalTitle" style="color: var(--sidebar-bg);"><i
                            class="fas fa-user-plus me-2 text-primary"></i> Register Agent</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <form id="agentForm" enctype="multipart/form-data">
                        <input type="hidden" id="edit_id">

                        <ul class="nav nav-tabs px-4 pt-3 bg-light" role="tablist">
                            <li class="nav-item"><button class="nav-link active fw-bold" data-bs-toggle="tab"
                                    data-bs-target="#tab-personal" type="button"><i class="fas fa-user me-1"></i>
                                    Personal Info</button></li>
                            <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab"
                                    data-bs-target="#tab-bank" type="button"><i class="fas fa-university me-1"></i> Bank
                                    Details</button></li>
                            <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab"
                                    data-bs-target="#tab-nominee" type="button"><i class="fas fa-user-shield me-1"></i>
                                    Nominee Info</button></li>
                            <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab"
                                    data-bs-target="#tab-docs" type="button"><i class="fas fa-file-upload me-1"></i>
                                    Documents</button></li>
                        </ul>

                        <div class="tab-content p-4">

                            <div class="tab-pane fade show active" id="tab-personal">

                                <div class="row g-3 mb-4 pb-3 border-bottom">
                                  <div class="col-md-4">
                                        <label class="form-label text-secondary small">Select Branch <span class="text-danger">*</span></label>
                                        
                                        <input type="text" class="form-control" id="f_branch" list="branchList" placeholder="Search Branch..." required autocomplete="off">
                                        
                                        <input type="hidden" name="branch_id" id="branch_id_hidden" required>
                                        
                                        <datalist id="branchList"></datalist>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Joining Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="joining_date" class="form-control" id="f_joining"
                                            required>
                                    </div>
                                    <div class="col-md-4 password-div" style="display:none;">
                                        <label class="form-label text-secondary small">Password</label>
                                        <input type="text" name="password" class="form-control" id="f_password">
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label text-secondary small">Name in Full
                                            <span class="text-danger">*</span></label><input type="text"
                                            name="full_name" class="form-control" id="f_name" required></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">S/o, D/o,
                                            Spouse's Name</label><input type="text" name="father_spouse_name"
                                            class="form-control" id="f_sodowo"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Mother's
                                            Name</label><input type="text" name="mother_name" class="form-control"
                                            id="f_mother"></div>

                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Blood Group</label>
                                        <select name="blood_group" class="form-select" id="f_blood">
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
                                    <div class="col-md-4"><label class="form-label text-secondary small">Gender</label>
                                        <div class="d-flex mt-1">
                                            <div class="form-check me-3"><input class="form-check-input" type="radio"
                                                    name="gender" value="Male" id="g_m"><label
                                                    class="form-check-label" for="g_m">Male</label></div>
                                            <div class="form-check me-3"><input class="form-check-input" type="radio"
                                                    name="gender" value="Female" id="g_f"><label
                                                    class="form-check-label" for="g_f">Female</label></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Marital
                                            Status</label>
                                        <div class="d-flex mt-1">
                                            <div class="form-check me-3"><input class="form-check-input" type="radio"
                                                    name="marital_status" value="Married" id="ms_m"><label
                                                    class="form-check-label" for="ms_m">Married</label></div>
                                            <div class="form-check me-3"><input class="form-check-input" type="radio"
                                                    name="marital_status" value="Unmarried" id="ms_u"><label
                                                    class="form-check-label" for="ms_u">Unmarried</label></div>
                                        </div>
                                    </div>

                                    <div class="col-md-4"><label
                                            class="form-label text-secondary small">Nationality</label><input
                                            type="text" name="nationality" class="form-control" value="Indian"
                                            readonly></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Date of
                                            Birth</label><input type="date" name="dob" class="form-control"
                                            id="f_dob"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Anniversary
                                            Date</label><input type="date" name="anniversary_date"
                                            class="form-control" id="f_doa"></div>

                                    <div class="col-md-4"><label class="form-label text-secondary small">Contact No. <span
                                                class="text-danger">*</span></label><input type="text"
                                            name="contact_no" class="form-control" id="f_mob" maxlength="10"
                                            required></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Alternate
                                            No.</label><input type="text" name="alternate_no" class="form-control"
                                            id="f_alt" maxlength="10"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Email
                                            ID</label><input type="email" name="email" class="form-control"
                                            id="f_email"></div>

                                    <div class="col-md-4"><label class="form-label text-secondary small">Aadhar Card
                                            No.<span class="text-danger">*</span></label><input type="text"
                                            name="aadhar_no" class="form-control" id="f_aadhar" maxlength="12"
                                            required></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">PAN
                                            No.</label><input type="text" name="pan_no"
                                            class="form-control text-uppercase" id="f_pan" maxlength="10"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Native
                                            Place</label><input type="text" name="native_place" class="form-control"
                                            id="f_native"></div>

                                    <div class="col-md-8"><label class="form-label text-secondary small">Communication
                                            Address</label><input type="text" name="communication_address"
                                            class="form-control" id="f_address"></div>
                                    <div class="col-md-4"><label
                                            class="form-label text-secondary small">City/Town/Village</label><input
                                            type="text" name="city" class="form-control" id="f_city"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Pin
                                            Code</label><input type="text" name="pin_code" class="form-control"
                                            id="f_pin" maxlength="6"></div>
                                </div>

                                <div class="row g-3 mt-4 pt-3 border-top bg-light rounded p-2">
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small fw-bold">Agent Status</label>
                                        <select name="agent_status" id="f_status" class="form-select">
                                            <option value="active">Active</option>
                                            <option value="inactive">In-Active</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 leave-fields d-none">
                                        <label class="form-label text-secondary small fw-bold">Date of Leaving</label>
                                        <input type="date" name="d_o_l" class="form-control" id="f_dol">
                                    </div>
                                    <div class="col-md-4 leave-fields d-none">
                                        <label class="form-label text-secondary small fw-bold">Leaving Remarks</label>
                                        <input type="text" name="leaving_remarks" class="form-control"
                                            id="f_remarks">
                                    </div>
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
                                    <div class="col-md-4"><label class="form-label text-secondary small">Account
                                            Type</label>
                                        <select class="form-select" name="account_type" id="f_acc_type">
                                            <option value="">-- Select --</option>
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
                                            Name</label><input type="text" name="bank_branch" class="form-control"
                                            id="f_bank_branch"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">IFSC
                                            Code</label><input type="text" name="ifsc_code"
                                            class="form-control text-uppercase" id="f_ifsc"></div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-nominee">
                                <div class="row g-3">
                                    <div class="col-md-3"><label class="form-label text-secondary small">Nominee
                                            Name</label><input type="text" name="nominee_name" class="form-control"
                                            id="f_n_name"></div>
                                    <div class="col-md-3"><label
                                            class="form-label text-secondary small">Relation</label><input type="text"
                                            name="nominee_relation" class="form-control" id="f_n_rel"></div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">S/o, D/o,
                                            W/o</label><input type="text" name="nominee_so_do_wo" class="form-control"
                                            id="f_n_so"></div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Date of
                                            Birth</label><input type="date" name="nominee_dob" class="form-control"
                                            id="f_n_dob"></div>

                                    <div class="col-md-4"><label class="form-label text-secondary small">Mobile
                                            No</label><input type="text" name="nominee_mobile" class="form-control"
                                            id="f_n_mob" maxlength="10"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Alt. Mobile
                                            No</label><input type="text" name="nominee_alternate_mobile"
                                            class="form-control" id="f_n_alt" maxlength="10"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Email
                                            Id</label><input type="email" name="nominee_email" class="form-control"
                                            id="f_n_email"></div>

                                    <div class="col-md-4"><label
                                            class="form-label text-secondary small">Aadhar</label><input type="text"
                                            name="nominee_aadhar" class="form-control" id="f_n_aadhar" maxlength="12">
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">PAN</label><input
                                            type="text" name="nominee_pan" class="form-control text-uppercase"
                                            id="f_n_pan" maxlength="10"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">PIN
                                            Code</label><input type="text" name="nominee_pincode" class="form-control"
                                            id="f_n_pin" maxlength="6"></div>

                                    <div class="col-md-4"><label
                                            class="form-label text-secondary small">State</label><input type="text"
                                            name="nominee_state" class="form-control" id="f_n_state"></div>
                                    <div class="col-md-4"><label
                                            class="form-label text-secondary small">District</label><input type="text"
                                            name="nominee_district" class="form-control" id="f_n_dist"></div>
                                    <div class="col-md-4"><label
                                            class="form-label text-secondary small">Address</label><input type="text"
                                            name="nominee_address" class="form-control" id="f_n_addr"></div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-docs">
                                <h6 class="text-primary fw-bold border-bottom pb-2 mb-3"><i
                                        class="fas fa-user-circle me-1"></i> Agent Documents</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4"><label class="form-label text-secondary small">Aadhar Card
                                            (.pdf)</label><input type="file" name="aadhar_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_aadhar_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">PAN Card
                                            (.pdf)</label><input type="file" name="pan_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_pan_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Bank Passbook
                                            (.pdf)</label><input type="file" name="bank_passbook_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_bank_passbook_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Driving License
                                            (.pdf)</label><input type="file" name="driving_license_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_driving_license_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Passport
                                            (.pdf)</label><input type="file" name="passport_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_passport_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Passport Photo
                                            (Img)</label><input type="file" name="passport_photo"
                                            class="form-control form-control-sm" accept="image/*">
                                        <div id="link_passport_photo"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">10th Marksheet
                                            (.pdf)</label><input type="file" name="tenth_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_tenth_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">12th Marksheet
                                            (.pdf)</label><input type="file" name="twelfth_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_twelfth_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Graduation Cert
                                            (.pdf)</label><input type="file" name="graduation_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_graduation_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">PG Certificate
                                            (.pdf)</label><input type="file" name="pg_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_pg_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Other Docs
                                            (.pdf)</label><input type="file" name="other_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_other_pdf"></div>
                                    </div>
                                </div>

                                <h6 class="text-primary fw-bold border-bottom pb-2 mb-3"><i
                                        class="fas fa-user-shield me-1"></i> Nominee Documents</h6>
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Aadhar
                                            (.pdf)</label><input type="file" name="nom_aadhar_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_nom_aadhar_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee PAN
                                            (.pdf)</label><input type="file" name="nom_pan_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_nom_pan_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Bank
                                            Passbook</label><input type="file" name="nom_bank_passbook_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_nom_bank_passbook_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Driving
                                            License</label><input type="file" name="nom_driving_license_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_nom_driving_license_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Passport
                                            (.pdf)</label><input type="file" name="nom_passport_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_nom_passport_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Photo
                                            (Img)</label><input type="file" name="nom_passport_photo"
                                            class="form-control form-control-sm" accept="image/*">
                                        <div id="link_nom_passport_photo"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee 10th
                                            (.pdf)</label><input type="file" name="nom_tenth_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_nom_tenth_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee 12th
                                            (.pdf)</label><input type="file" name="nom_twelfth_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_nom_twelfth_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Grad Cert
                                            (.pdf)</label><input type="file" name="nom_graduation_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_nom_graduation_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee PG Cert
                                            (.pdf)</label><input type="file" name="nom_pg_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_nom_pg_pdf"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Other
                                            Docs</label><input type="file" name="nom_other_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_nom_other_pdf"></div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="modal-footer bg-light border-top-0">
                            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn text-white px-5 shadow-sm fw-medium"
                                style="background-color: var(--brand-primary);" id="saveBtn">Save Agent Details</button>
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
            const apiToken = localStorage.getItem('emp_token') || localStorage.getItem('admin_token');
            let mode = 'add';
            
            // 🔥 NAYA: Mapping ke liye variable aur Event Listener
            let branchMap = {}; 

            $('#f_branch').on('input change', function() {
                let val = $(this).val();
                if (branchMap[val]) {
                    $('#branch_id_hidden').val(branchMap[val]); // ID mil gayi, hidden me set kardo
                    this.setCustomValidity(''); // Error clear
                } else {
                    $('#branch_id_hidden').val(''); 
                    this.setCustomValidity('Please select a valid branch from the list');
                }
            });

            // 1. DataTables
            let table = $('#agentTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/agents',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    }
                },
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export Excel',
                    className: 'btn btn-success btn-sm shadow-sm rounded-3'
                }],
                columns: [{
                        data: 'agent_id',
                        render: d => `<span class="fw-bold text-primary">${d}</span>`
                    },
                    {
                        data: 'branch_id',
                        render: (d, t, row) =>
                            `<span class="badge bg-light text-dark border"><i class="fas fa-map-marker-alt text-danger me-1"></i> ${row.branch ? row.branch.branch_name : 'N/A'}</span>`
                    },
                    {
                        data: 'full_name'
                    },
                    {
                        data: 'contact_no'
                    },
                    {
                        data: 'agent_status',
                        render: d => d === 'active' ? `<span class="badge bg-success">Active</span>` :
                            `<span class="badge bg-danger">In-Active</span>`
                    },
                    {
                        data: 'id',
                        render: d => `
                <div class="text-end">
                    <button type="button" class="btn btn-sm btn-light text-info me-1 view-btn" data-id="${d}"><i class="fas fa-eye"></i></button>
                    <button type="button" class="btn btn-sm btn-light text-primary me-1 edit-btn" data-id="${d}"><i class="fas fa-edit"></i></button>
                    <button type="button" class="btn btn-sm btn-light text-danger delete-btn" data-id="${d}"><i class="fas fa-trash-alt"></i></button>
                </div>`
                    }
                ],
                drawCallback: function(settings) {
                    renderMobileCards(settings.json.data);
                }
            });

           // 2. Mobile Cards
            function renderMobileCards(data) {
                let html = '';
                if(!data || data.length === 0) {
                    html = '<div class="text-center p-3 text-muted border rounded bg-light">No agents found.</div>';
                } else {
                    data.forEach(d => {
                        let st = d.agent_status === 'active' ?
                            `<span class="badge bg-success">Active</span>` :
                            `<span class="badge bg-danger">In-Active</span>`;
                        let compName = d.branch && d.branch.company ? d.branch.company.company_name : 'Master Company';
                        let branchName = d.branch ? d.branch.branch_name : 'N/A';

                        html += `<div class="mobile-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div><h6 class="fw-bold text-dark mb-0">${d.full_name}</h6><span class="text-primary small fw-bold">${d.agent_id}</span></div>
                                ${st}
                            </div>
                            <div class="small text-muted mb-1"><i class="fas fa-building text-info me-1"></i> ${compName} - ${branchName}</div>
                            <div class="small text-muted"><i class="fas fa-phone me-1"></i> ${d.contact_no}</div>
                            <div class="mt-2 pt-2 border-top d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-light text-info flex-fill view-btn" data-id="${d.id}">View</button>
                                <button type="button" class="btn btn-sm btn-light text-primary flex-fill edit-btn" data-id="${d.id}">Edit</button>
                                <button type="button" class="btn btn-sm btn-light text-danger flex-fill delete-btn" data-id="${d.id}">Delete</button>
                            </div>
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
            $('#mobileExcelBtn').click(() => $('.buttons-excel').click());

            // 3. Status Toggle Logic
            $('#f_status').on('change', function() {
                if ($(this).val() === 'inactive') {
                    $('.leave-fields').removeClass('d-none');
                } else {
                    $('.leave-fields').addClass('d-none');
                    $('#f_dol, #f_remarks').val('');
                }
            });

            // 4. Modals
            // === Array of all document fields ===
            const docFields = [
                'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'driving_license_pdf', 'passport_pdf',
                'passport_photo',
                'tenth_pdf', 'twelfth_pdf', 'graduation_pdf', 'pg_pdf', 'other_pdf',
                'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_driving_license_pdf',
                'nom_passport_pdf',
                'nom_passport_photo', 'nom_tenth_pdf', 'nom_twelfth_pdf', 'nom_graduation_pdf', 'nom_pg_pdf',
                'nom_other_pdf'
            ];

       window.openModal = function(type, id = null) {
                mode = type;
                $('#agentForm')[0].reset();
                $('#branch_id_hidden').val(''); // Hidden input clear kiya
                $('#modalTitle').text(type === 'add' ? 'Register Agent' : 'Edit Agent');
                $('.nav-tabs button:first').tab('show'); 
                $('#f_status').trigger('change');
                
                $('.file-preview-wrapper').hide().find('.preview-content').empty();
                $('.password-div').toggle(type === 'edit');

                // Document Fields Array (Aapka purana wala)
                const docFields = [
                    'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'driving_license_pdf', 'passport_pdf', 'passport_photo', 
                    'tenth_pdf', 'twelfth_pdf', 'graduation_pdf', 'pg_pdf', 'other_pdf',
                    'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_driving_license_pdf', 'nom_passport_pdf', 
                    'nom_passport_photo', 'nom_tenth_pdf', 'nom_twelfth_pdf', 'nom_graduation_pdf', 'nom_pg_pdf', 'nom_other_pdf'
                ];

                $.ajax({
                    url: '/api/v1/branches', headers: { 'Authorization': 'Bearer ' + apiToken },
                    success: function(res) {
                        let options = '';
                        branchMap = {}; // Map reset karein
                        
                        res.data.forEach(b => {
                            let compName = b.company ? b.company.company_name : 'Master Company';
                            let displayText = `${compName} - ${b.branch_name} (${b.branch_id})`;
                            
                            options += `<option value="${displayText}">`; // Ab sirf text value me jayega
                            branchMap[displayText] = b.id; // Text ko ID se map kiya
                        });
                        $('#branchList').html(options);

                        if(type === 'edit') {
                            $.get({
                                url: `/api/v1/agents/${id}`, headers: { 'Authorization': 'Bearer ' + apiToken },
                                success: function(res) {
                                    let d = res.data;
                                    $('#edit_id').val(d.id);
                                    
                                    // 🔥 Edit mode me branch set karne ka fix
                                    if(d.branch) {
                                        let compName = d.branch.company ? d.branch.company.company_name : 'Master Company';
                                        let displayText = `${compName} - ${d.branch.branch_name} (${d.branch.branch_id})`;
                                        $('#f_branch').val(displayText);
                                        $('#branch_id_hidden').val(d.branch_id);
                                    }
                                    
                                    // Baaki fields populate
                                    Object.keys(d).forEach(key => {
                                        let input = $(`#agentForm [name="${key}"]`);
                                        if(input.length && input.attr('type') !== 'file' && input.attr('type') !== 'radio') {
                                            if (typeof d[key] === 'object' && d[key] !== null) return;
                                            
                                            // branch_id skip karein kyunki humne upar set kar diya
                                            if(key !== 'branch_id') input.val(d[key]); 
                                        }
                                    });

                                    if(d.gender) $(`input[name="gender"][value="${d.gender}"]`).prop('checked', true);
                                    if(d.marital_status) $(`input[name="marital_status"][value="${d.marital_status}"]`).prop('checked', true);

                                    $('#f_status').trigger('change');

                                    // Preview Logic... (Aapka purana wala same rahega)
                                    docFields.forEach(field => {
                                        let filePath = d[field];
                                        let input = $(`#agentForm [name="${field}"]`);
                                        if(input.length && filePath) {
                                            let wrapper = input.next('.file-preview-wrapper');
                                            let content = wrapper.find('.preview-content');
                                            let fullUrl = filePath.startsWith('/') ? filePath : '/' + filePath;
                                            let ext = filePath.split('.').pop().toLowerCase();
                                            let imageExts = ['jpg', 'jpeg', 'png', 'webp', 'bmp'];

                                            if(imageExts.includes(ext)) {
                                                content.html(`<img src="${fullUrl}" style="max-height:80px; border-radius:6px;">`);
                                            } else {
                                                content.html(`<div class="p-2 small"><i class="fas fa-file-pdf text-danger me-2"></i><a href="${fullUrl}" target="_blank">View File</a></div>`);
                                            }
                                            wrapper.show();
                                        }
                                    });
                                }
                            });
                        }
                    }
                });
                $('#agentModal').modal('show');
            };

            
            $(document).on('click', '.edit-btn', function() {
                openModal('edit', $(this).data('id'));
            });

            // 5. Form Submit
            $('#agentForm').submit(function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                let id = $('#edit_id').val();
                let url = mode === 'add' ? '/api/v1/agents' : `/api/v1/agents/${id}`;
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
                        alert(res.message);
                        $('#agentModal').modal('hide');
                        table.ajax.reload(null, false);
                        loadMobile();
                    },
                    error: function(err) {
                        alert('Error saving data!');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Save Agent Details');
                    }
                });
            });

            // View Logic (Show Company Name)
            $(document).on('click', '.view-btn', function() {
                $.get({
                    url: `/api/v1/agents/${$(this).data('id')}`,
                    headers: { 'Authorization': 'Bearer ' + apiToken },
                    success: function(res) {
                        let d = res.data;
                        $('#v_agent_id').text(d.agent_id || 'N/A');
                        $('#v_password').text(d.password || 'N/A');
                        
                        let branchText = 'N/A';
                        if(d.branch) {
                            let compName = d.branch.company ? d.branch.company.company_name : 'Master Company';
                            branchText = compName + ' - ' + d.branch.branch_name;
                        }
                        $('#v_branch').text(branchText);
                        $('#v_joining').text(d.joining_date || 'N/A');

                        if (d.agent_status === 'active') {
                            $('#v_status').text('Active').attr('class', 'badge bg-success');
                        } else {
                            $('#v_status').text(`In-Active (Left: ${d.d_o_l})`).attr('class',
                                'badge bg-danger');
                        }

                        $('#v_name').text(d.full_name || 'N/A');
                        $('#v_mobile').text(d.contact_no || 'N/A');
                        $('#v_email').text(d.email || 'N/A');
                        $('#v_aadhar').text(d.aadhar_no || 'N/A');
                        $('#v_pan').text(d.pan_no || 'N/A');

                        $('#v_nom_name').text(d.nominee_name || 'N/A');
                        $('#v_nom_rel').text(d.nominee_relation || 'N/A');
                        $('#v_nom_mob').text(d.nominee_mobile || 'N/A');

                        $('#viewModal').modal('show');
                    }
                });
            });

            $(document).on('click', '.delete-btn', function() {
                if (confirm("Delete Agent?")) {
                    $.ajax({
                        url: `/api/v1/agents/${$(this).data('id')}`,
                        type: 'DELETE',
                        headers: {
                            'Authorization': 'Bearer ' + apiToken
                        },
                        success: function() {
                            table.ajax.reload(null, false);
                            loadMobile();
                        }
                    });
                }
            });


// 1. Har file input ke neeche Preview Container add karein
    $('input[type="file"]').each(function() {
        if ($(this).next('.file-preview-wrapper').length === 0) {
            $(this).after(`
                <div class="file-preview-wrapper">
                    <button type="button" class="btn btn-danger remove-preview-btn" title="Remove File"><i class="fas fa-times"></i></button>
                    <div class="preview-content text-center"></div>
                </div>
            `);
        }
    });

    // 2. Select karne par Preview dikhao
    $(document).on('change', 'input[type="file"]', function() {
        let file = this.files[0];
        let wrapper = $(this).next('.file-preview-wrapper');
        let content = wrapper.find('.preview-content');

        if (file) {
            if (file.type.startsWith('image/')) {
                let reader = new FileReader();
                reader.onload = e => { content.html(`<img src="${e.target.result}" style="max-height:80px; border-radius:6px;">`); wrapper.slideDown(); }
                reader.readAsDataURL(file);
            } else {
                content.html(`<div class="p-2 small fw-bold text-dark"><i class="fas fa-file-alt text-primary me-2"></i>${file.name}</div>`);
                wrapper.slideDown();
            }
        }
    });

    // 3. Remove Button (X) logic
    $(document).on('click', '.remove-preview-btn', function() {
        let wrapper = $(this).closest('.file-preview-wrapper');
        wrapper.prev('input[type="file"]').val('');
        wrapper.slideUp();
    });


        });
    </script>
@endpush
