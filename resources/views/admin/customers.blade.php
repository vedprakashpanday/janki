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

        /* Tabs Styling - Make them horizontal and scrollable on mobile */
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

        /* Hide scrollbar */
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

        /* File Preview Box Styling */
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
                <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Customer Details</h4>
            </div>
            <button type="button" class="btn text-white px-3 py-2 shadow-sm secured-item" data-permission="customer_add" style="background-color: var(--brand-primary);" onclick="openModal('add')">
    <i class="fas fa-plus me-1"></i> Register Customer
</button>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search Customer...">
            <button type="button" class="btn text-white shadow-sm" style="background-color: #10b981;"
                id="mobileExcelBtn"><i class="fas fa-file-excel"></i></button>
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

                            <div class="tab-pane fade show active" id="tab-personal">
                                <div class="row g-3">
                                  <div class="col-md-4">
                                    <label class="form-label text-secondary small">Select Branch <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="f_branch" list="branchList" placeholder="Search Branch..." required autocomplete="off">
                                    <input type="hidden" name="branch_id" id="branch_id_hidden" required>
                                    <datalist id="branchList"></datalist>
                                </div>


                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Name in Full <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="customer_name" class="form-control" id="f_name"
                                            required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Associate Member (Optional)</label>
                                        <input type="text" id="member_search_input" class="form-control"
                                            list="memberListOptions" placeholder="Search Member..." autocomplete="off">

                                        <input type="hidden" name="member_id" id="f_member_id">

                                        <datalist id="memberListOptions"></datalist>
                                    </div>

                                    <div class="col-md-4 password-div" style="display: none;">
                                        <label class="form-label text-secondary small">Login Password <span
                                                class="text-info">(Editable)</span></label>
                                        <input type="text" name="password" class="form-control"
                                            placeholder="Enter new password to change">
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
                                            <input class="form-check-input" type="radio" name="marital_status"
                                                value="Married" id="ms_married">
                                            <label class="form-check-label" for="ms_married">Married</label>
                                        </div>
                                        <div class="form-check form-check-inline mt-2">
                                            <input class="form-check-input" type="radio" name="marital_status"
                                                value="Unmarried" id="ms_unmarried">
                                            <label class="form-check-label" for="ms_unmarried">Unmarried</label>
                                        </div>
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


    <div class="modal fade" id="responseModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="responseModalTitle">Message</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <i id="responseModalIcon" class="fas fa-3x mb-3"></i>
                    <p id="responseModalMessage" class="mb-0 fw-medium" style="word-wrap: break-word; font-size: 14px;"></p>
                </div>
                <div class="modal-footer border-top-0 justify-content-center pb-4">
                    <button type="button" class="btn px-5 text-white fw-bold shadow-sm" id="responseModalBtn" data-bs-dismiss="modal">OK</button>
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
            let branchMap = {}; // Branch ki hidden ID ke liye
            let memberMap = {}; // Member ki hidden ID ke liye

            // ==========================================
            // 1. SMART RESPONSE MODAL
            // ==========================================
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

            // ==========================================
            // 2. DATATABLES & EXCEL TRICK (10-10 Server Side)
            // ==========================================
            let table = $('#customerTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/customers',
                    type: 'GET',
                    headers: { 'Authorization': 'Bearer ' + apiToken }
                },
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export All to Excel',
                    className: 'btn btn-success btn-sm shadow-sm rounded-3',
                    action: function(e, dt, button, config) {
                        let oldLength = dt.page.len();
                        dt.page.len(-1).draw(); // Saara data laao
                        dt.one('draw', function() {
                            $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, button, config);
                            dt.page.len(oldLength).draw(); // Wapas 10 pe set karo
                        });
                    }
                }],
                columns: [
                    { data: 'customer_id', render: d => `<span class="fw-bold text-primary">${d}</span>` },
                    { data: 'branch_id', render: (d, t, row) => {
                            if (!row.branch) return 'N/A';
                            let compName = row.branch.company ? row.branch.company.company_name : 'Master Company';
                            return `<div class="small fw-bold text-primary"><i class="fas fa-building me-1"></i> ${compName}</div>
                                    <div class="small text-muted"><i class="fas fa-map-marker-alt text-danger me-1"></i> ${row.branch.branch_name}</div>`;
                    }},
                    { data: 'customer_name' },
                    { data: 'customer_mobile' },
                 // 1. DataTables action column render ke andar
{ data: 'id', orderable: false, className: 'text-end text-nowrap', render: d => `
    <div class="d-flex justify-content-end flex-nowrap gap-1">
        <button type="button" class="btn btn-sm btn-light text-info view-btn" data-id="${d}"><i class="fas fa-eye"></i></button>
        <button type="button" class="btn btn-sm btn-light text-primary edit-btn secured-item" data-permission="customer_edit" data-id="${d}"><i class="fas fa-edit"></i></button>
        <button type="button" class="btn btn-sm btn-light text-danger delete-btn secured-item" data-permission="customer_delete" data-id="${d}"><i class="fas fa-trash-alt"></i></button>
    </div>`
}
                ],
                drawCallback: function(settings) {
                    renderMobileCards(settings.json.data);
                }
            });

            // ==========================================
            // 3. MOBILE CARDS RENDER
            // ==========================================
            function renderMobileCards(data) {
                let html = '';
                if (!data || data.length === 0) {
                    html = '<div class="text-center p-3 text-muted border rounded bg-light">No customers found.</div>';
                } else {
                    data.forEach(d => {
                        let compName = d.branch && d.branch.company ? d.branch.company.company_name : 'Master Company';
                        let branchName = d.branch ? d.branch.branch_name : 'N/A';
                        html += `<div class="mobile-item">
                            <h6 class="fw-bold text-dark">${d.customer_name} <span class="float-end text-primary small">${d.customer_id}</span></h6>
                            <div class="small text-muted mb-1"><i class="fas fa-building text-info me-1"></i> ${compName} - ${branchName}</div>
                            <div class="small text-muted"><i class="fas fa-phone me-1"></i> ${d.customer_mobile}</div>
                           <div class="mt-2 pt-2 border-top d-flex gap-2">
    <button type="button" class="btn btn-sm btn-light text-info flex-fill view-btn" data-id="${d.id}">View</button>
    <button type="button" class="btn btn-sm btn-light text-primary flex-fill edit-btn secured-item" data-permission="customer_edit" data-id="${d.id}">Edit</button>
    <button type="button" class="btn btn-sm btn-light text-danger flex-fill delete-btn secured-item" data-permission="customer_delete" data-id="${d.id}">Delete</button>
</div>
                        </div>`;
                    });
                }
                $('#mobileCardsContainer').html(html);
            }

            $('#mobileSearch').on('keyup', function() {
                let v = $(this).val().toLowerCase();
                $('.mobile-item').filter(function() { $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1) });
            });
            $('#mobileExcelBtn').click(() => $('.buttons-excel').click());

            // ==========================================
            // 4. DATALIST MAPPING (Branch & Member)
            // ==========================================
            
            // Branch Input Mapping
            $('#f_branch').on('input change', function() {
                let val = $(this).val();
                if (branchMap[val]) {
                    $('#branch_id_hidden').val(branchMap[val]); 
                    this.setCustomValidity('');
                } else {
                    $('#branch_id_hidden').val('');
                    this.setCustomValidity('Please select a valid branch from the list');
                }
            });

            // Member Load & Mapping
            function loadMembersForDatalist() {
                $.ajax({
                    url: '/api/v1/members', type: 'GET', headers: { 'Authorization': 'Bearer ' + apiToken },
                    success: function(res) {
                        let options = '';
                        memberMap = {};
                        res.data.forEach(m => {
                            let disp = `${m.member_name} (${m.member_id})`;
                            options += `<option value="${disp}">`;
                            memberMap[disp] = m.member_id; // Hidden ID store kar li
                        });
                        $('#memberListOptions').html(options);
                    }
                });
            }
            loadMembersForDatalist();

            // Member Input Mapping
            $('#member_search_input').on('input change', function() {
                let val = $(this).val();
                if (memberMap[val]) {
                    $('#f_member_id').val(memberMap[val]);
                } else {
                    $('#f_member_id').val('');
                }
            });

            // ==========================================
            // 5. FILE PREVIEWS LOGIC
            // ==========================================
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
                            content.html(`<img src="${event.target.result}" style="max-height:90px; border-radius:6px; object-fit:contain;">`);
                            wrapper.slideDown();
                        }
                        reader.readAsDataURL(file);
                    } else {
                        let icon = file.type === 'application/pdf' ? 'fa-file-pdf text-danger' : 'fa-file-alt text-primary';
                        content.html(`<div class="d-flex align-items-center gap-2 fw-bold text-dark px-2"><i class="fas ${icon} fs-3"></i><span style="font-size:12px; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${file.name}</span></div>`);
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

            // ==========================================
            // 6. OPEN MODAL & FORM SUBMIT
            // ==========================================
            window.openModal = function(type, id = null) {
                mode = type;
                $('#customerForm')[0].reset();
                $('#branch_id_hidden').val('');
                $('#f_member_id').val('');
                $('#modalTitle').text(type === 'add' ? 'Register Customer' : 'Edit Customer');
                $('.nav-tabs button:first').tab('show'); 
                $('.file-preview-wrapper').hide().find('.preview-content').empty();

                if(type === 'add') { $('.password-div').hide(); } 
                else { $('.password-div').show(); }

                $.ajax({
                    url: '/api/v1/branches', headers: { 'Authorization': 'Bearer ' + apiToken },
                    success: function(res) {
                        let options = '';
                        branchMap = {}; 
                        res.data.forEach(b => {
                            let compName = b.company ? b.company.company_name : 'Master Company';
                            let disp = `${compName} - ${b.branch_name} (${b.branch_id})`;
                            options += `<option value="${disp}">`;
                            branchMap[disp] = b.id;
                        });
                        $('#branchList').html(options);

                        if(type === 'edit') {
                            $.get({
                                url: `/api/v1/customers/${id}`, headers: { 'Authorization': 'Bearer ' + apiToken },
                                success: function(res) {
                                    let cust = res.data;
                                    $('#edit_id').val(cust.id);
                                    
                                    // Branch Auto-fill
                                    if(cust.branch) {
                                        let compName = cust.branch.company ? cust.branch.company.company_name : 'Master Company';
                                        let disp = `${compName} - ${cust.branch.branch_name} (${cust.branch.branch_id})`;
                                        $('#f_branch').val(disp);
                                        $('#branch_id_hidden').val(cust.branch_id);
                                    }

                                    // Member Auto-fill
                                    if (cust.member_id) {
                                        // Reverse search in map
                                        let memberDisp = Object.keys(memberMap).find(key => memberMap[key] === cust.member_id);
                                        if(memberDisp) {
                                            $('#member_search_input').val(memberDisp);
                                            $('#f_member_id').val(cust.member_id);
                                        }
                                    }

                                    // Text fields Auto-fill
                                    Object.keys(cust).forEach(key => {
                                        let input = $(`#customerForm [name="${key}"]`);
                                        if(input.length && input.attr('type') !== 'file' && input.attr('type') !== 'radio') {
                                            if (typeof cust[key] === 'object' && cust[key] !== null) return; 
                                            if (key !== 'branch_id' && key !== 'member_id') input.val(cust[key]);
                                        }
                                    });

                                    $('#f_bank_branch').val(cust.bank_branch_text); // Bank branch conflict bypass

                                    // Radios
                                    if (cust.gender) $(`input[name="gender"][value="${cust.gender}"]`).prop('checked', true);
                                    if (cust.marital_status) $(`input[name="marital_status"][value="${cust.marital_status}"]`).prop('checked', true);

                                    // Files Auto-fill
                                    let fileFields = [
                                        'aadharcard', 'pancard', 'bank_passbook_pdf', 'drivinglicense', 'passport', 'passport_photo', 
                                        'tenthmarksheet', 'twelvethmarksheet', 'graduationcertificate', 'pgcertificate', 'otherdoc',
                                        'nom_aadharcard', 'nom_pancard', 'nom_bankpassbook', 'nom_drivinglicense', 'nom_passport',
                                        'nom_passport_photo', 'nom_tenthmarksheet', 'nom_twelvethmarksheet', 'nom_graduationcertificate',
                                        'nom_pgcertificate', 'nom_otherdoc'
                                    ];

                                    fileFields.forEach(function(field) {
                                        let filePath = cust[field];
                                        let input = $(`#customerForm input[name="${field}"]`);
                                        if (input.length > 0 && filePath) {
                                            let wrapper = input.next('.file-preview-wrapper');
                                            let content = wrapper.find('.preview-content');
                                            let fullUrl = filePath.startsWith('/') ? filePath : '/' + filePath;
                                            let ext = filePath.split('.').pop().toLowerCase();
                                            let imageExts = ['jpg', 'jpeg', 'png', 'webp', 'bmp'];

                                            if (imageExts.includes(ext)) {
                                                content.html(`<img src="${fullUrl}" style="max-height:90px; border-radius:6px; object-fit:contain;">`);
                                            } else {
                                                let icon = ext === 'pdf' ? 'fa-file-pdf text-danger' : 'fa-file-alt text-primary';
                                                content.html(`<div class="d-flex align-items-center gap-2 fw-bold text-dark px-2"><i class="fas ${icon} fs-3"></i><a href="${fullUrl}" target="_blank" class="text-decoration-none" style="font-size:12px;">View Document</a></div>`);
                                            }
                                            wrapper.show();
                                        }
                                    });
                                }
                            });
                        }
                    }
                });
                $('#customerModal').modal('show');
            };

            $(document).on('click', '.edit-btn', function() {
                openModal('edit', $(this).data('id'));
            });

            // BULLETPROOF FORM SUBMIT
            $('#customerForm').submit(function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                let id = $('#edit_id').val();
                let url = mode === 'add' ? '/api/v1/customers' : `/api/v1/customers/${id}`;
                if (mode === 'edit') formData.append('_method', 'PUT');

                let btn = $('#saveBtn');
                btn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: url,
                    type: 'POST',
                    headers: { 'Authorization': 'Bearer ' + apiToken },
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
                        if (err.responseJSON && err.responseJSON.message) errMsg = err.responseJSON.message; 
                        showMessage(errMsg, 'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Save Customer Details');
                    }
                });
            });

            // 7. VIEW & DELETE
            $(document).on('click', '.view-btn', function() {
                let id = $(this).data('id');
                $.get({
                    url: `/api/v1/customers/${id}`,
                    headers: { 'Authorization': 'Bearer ' + apiToken },
                    success: function(res) {
                        let d = res.data;
                        $('#v_cust_id').text(d.customer_id || 'N/A');
                        $('#v_password').text(d.password || 'N/A'); 
                        
                        let branchText = 'N/A';
                        if(d.branch) {
                            let compName = d.branch.company ? d.branch.company.company_name : 'Master Company';
                            branchText = compName + ' - ' + d.branch.branch_name;
                        }
                        $('#v_branch').text(branchText);

                        $('#v_name').text(d.customer_name || 'N/A');
                        $('#v_mobile').text(d.customer_mobile || 'N/A');
                        $('#v_email').text(d.customer_email || 'N/A');
                        $('#v_aadhar').text(d.aadhar_number || 'N/A');
                        $('#v_pan').text(d.pan_number || 'N/A');
                        $('#v_booking').text(d.booking_date || 'N/A');

                        $('#v_nom_name').text(d.nominee_name || 'N/A');
                        $('#v_nom_relation').text(d.nominee_relation || 'N/A');
                        $('#v_nom_mobile').text(d.nominee_mobile || 'N/A');

                        $('#viewModal').modal('show');
                    }
                });
            });

            $(document).on('click', '.delete-btn', function() {
                if (confirm("Delete Customer?")) {
                    $.ajax({
                        url: `/api/v1/customers/${$(this).data('id')}`,
                        type: 'DELETE',
                        headers: { 'Authorization': 'Bearer ' + apiToken },
                        success: function() {
                            table.ajax.reload(null, false);
                            showMessage('Deleted successfully!', 'success');
                        }
                    });
                }
            });
        });
    </script>
@endpush
