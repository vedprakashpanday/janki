@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        /* Desktop Table */
        .table-custom th {
            background-color: var(--sidebar-bg);
            color: #fff;
            font-size: 13px;
            border: none;
        }

        .table-custom td {
            font-size: 13px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        /* Mobile Cards */
        .emp-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px var(--shadow-color);
        }

        .emp-id-badge {
            color: var(--brand-primary);
            font-weight: 700;
            font-size: 12px;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
        }

        /* Modal & Tabs Fix */
        .modal-xl {
            max-width: 95%;
        }

        @media (max-width: 768px) {
            .modal-xl {
                max-width: 98%;
                margin: 10px auto;
            }
        }

        .nav-pills .nav-link {
            color: #64748b;
            font-weight: 600;
        }

        .nav-pills .nav-link.active {
            background-color: var(--sidebar-bg);
            color: #fff;
        }

        .form-label {
            color: #475569;
            margin-bottom: 0.2rem;
        }

        /* File input styling */
        input[type="file"]::file-selector-button {
            background-color: var(--bg-light);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            border-radius: 4px;
            padding: 4px 10px;
            margin-right: 10px;
        }

        /* Preview Box Styling */
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
                <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Employee Management</h4>
                <p class="text-secondary small d-none d-md-block mb-0">Manage all administrative employees and documents</p>
            </div>
            <button class="btn text-white px-4 py-2 shadow-sm" style="background-color: var(--brand-primary);"
                onclick="openAddModal()">
                <i class="fas fa-user-plus me-1"></i> <span class="d-none d-md-inline">Add Employee</span>
            </button>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search Employee...">
            <button class="btn text-white shadow-sm" style="background-color: #10b981;" id="mobileExcelBtn"><i
                    class="fas fa-file-excel"></i></button>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="empTable" class="table table-hover table-custom w-100">
                        <thead>
                            <tr>
                                <th>Emp ID</th>
                                <th>Name</th>
                                <th>Designation</th>
                                <th>Branch</th>
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

        <div id="mobileCardsContainer" class="d-block d-md-none">
            <div class="text-center text-muted my-4" id="cardsLoader">
                <i class="fas fa-spinner fa-spin fs-2 mb-2"></i><br>Loading Employees...
            </div>
        </div>
    </div>

    <div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom pb-3">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);" id="modalTitle">
                        <i class="fas fa-user-plus me-2 text-primary"></i> Register New Employee
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-3 p-md-4">
                    <form id="empForm">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="_method" id="form_method" value="POST">
                        <ul class="nav nav-pills mb-4 bg-light p-1 rounded-3 shadow-sm flex-nowrap overflow-auto"
                            style="white-space: nowrap;">
                            <li class="nav-item"><a class="nav-link active small py-2 px-3" data-bs-toggle="tab"
                                    href="#personal"><i class="fas fa-user me-1"></i> Personal</a></li>
                            <li class="nav-item"><a class="nav-link small py-2 px-3" data-bs-toggle="tab" href="#bank"><i
                                        class="fas fa-university me-1"></i> Bank</a></li>
                            <li class="nav-item"><a class="nav-link small py-2 px-3" data-bs-toggle="tab" href="#nominee"><i
                                        class="fas fa-users me-1"></i> Nominee</a></li>
                            <li class="nav-item"><a class="nav-link small py-2 px-3" data-bs-toggle="tab" href="#empDocs"><i
                                        class="fas fa-file-alt me-1"></i> Emp Docs</a></li>
                            <li class="nav-item"><a class="nav-link small py-2 px-3" data-bs-toggle="tab"
                                    href="#nomDocsStatus"><i class="fas fa-clipboard-check me-1"></i> Nom Docs & Status</a>
                            </li>
                        </ul>

                        <div class="tab-content">

                            <div class="tab-pane fade show active" id="personal">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Personal
                                    Details</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Select Branch <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select" name="branch_id" id="branchSelect" required>
                                            <option value="">-- Select Branch --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Full Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="full_name" id="full_name" class="form-control"
                                            placeholder="Enter Full Name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">S/O, D/O, Spouse's Name</label>
                                        <input type="text" name="father_spouse_name" id="father_spouse_name"
                                            class="form-control" placeholder="Father/Spouse Name">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Mother's Name</label>
                                        <input type="text" name="mother_name" id="mother_name" class="form-control"
                                            placeholder="Mother's Name">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Designation <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="designation" id="designation_input"
                                            class="form-control" list="designationList"
                                            placeholder="Type or Select Designation" required>

                                        <datalist id="designationList">
                                        </datalist>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Gender</label>
                                        <div class="mt-2">
                                            <div class="form-check form-check-inline"><input class="form-check-input"
                                                    type="radio" name="gender" value="Male" id="g_male"> <label
                                                    class="form-check-label" for="g_male">Male</label></div>
                                            <div class="form-check form-check-inline"><input class="form-check-input"
                                                    type="radio" name="gender" value="Female" id="g_female"> <label
                                                    class="form-check-label" for="g_female">Female</label></div>
                                            <div class="form-check form-check-inline"><input class="form-check-input"
                                                    type="radio" name="gender" value="Others" id="g_others"> <label
                                                    class="form-check-label" for="g_others">Others</label></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Marital Status</label>
                                        <div class="mt-2">
                                            <div class="form-check form-check-inline"><input
                                                    class="form-check-input marital-radio" type="radio"
                                                    name="marital_status" value="Married" id="m_married"> <label
                                                    class="form-check-label" for="m_married">Married</label></div>
                                            <div class="form-check form-check-inline"><input
                                                    class="form-check-input marital-radio" type="radio"
                                                    name="marital_status" value="Unmarried" id="m_unmarried" checked>
                                                <label class="form-check-label" for="m_unmarried">Unmarried</label></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nationality</label>
                                        <input type="text" name="nationality" id="nationality" class="form-control"
                                            value="Indian">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Date of Birth</label>
                                        <input type="date" name="dob" id="dob" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Blood Group</label>
                                        <select name="blood_group" id="blood_group" class="form-select">
                                            <option value="">-- Select --</option>
                                            <option value="A+">A+</option>
                                            <option value="A-">A-</option>
                                            <option value="B+">B+</option>
                                            <option value="B-">B-</option>
                                            <option value="AB+">AB+</option>
                                            <option value="AB-">AB-</option>
                                            <option value="O+">O+</option>
                                            <option value="O-">O-</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Date of Joining</label>
                                        <input type="date" name="doj" id="doj" class="form-control">
                                    </div>
                                    <div class="col-md-4" id="doa_container" style="display:none;">
                                        <label class="form-label small fw-bold">Date of Anniversary</label>
                                        <input type="date" name="anniversary_date" id="anniversary_date"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Contact No <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="contact_no" id="contact_no" class="form-control"
                                            maxlength="10" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Alternate No</label>
                                        <input type="text" name="alternate_no" id="alternate_no" class="form-control"
                                            maxlength="10">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Email ID</label>
                                        <input type="email" name="email" id="email" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">PAN No</label>
                                        <input type="text" name="pan_no" id="pan_no" class="form-control"
                                            style="text-transform:uppercase;" maxlength="10">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Aadhar Card No <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="aadhar_no" id="aadhar_no" class="form-control"
                                            maxlength="12" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Native Place</label>
                                        <input type="text" name="native_place" id="native_place"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold">Communication Address</label>
                                        <textarea name="communication_address" id="communication_address" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">City/Town/Village</label>
                                        <input type="text" name="city" id="city" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Pin Code</label>
                                        <input type="text" name="pin_code" id="pin_code" class="form-control"
                                            maxlength="6">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-primary">Auto Generated
                                            Password</label>
                                        <input type="text" name="password" id="mem_pass"
                                            class="form-control bg-light" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="bank">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Bank
                                    Details</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Account Holder Name</label>
                                        <input type="text" name="account_name" id="account_name"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Bank A/c No</label>
                                        <input type="text" name="account_no" id="account_no" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Account Type</label>
                                        <select class="form-select" name="account_type" id="account_type">
                                            <option value="">-- Select Type --</option>
                                            <option value="saving">Saving Account</option>
                                            <option value="current">Current Account</option>
                                            <option value="cc">Cash Credit Account</option>
                                            <option value="od">Over Draft Account</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Bank Name</label>
                                        <input type="text" name="bank_name" id="bank_name" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Branch Name</label>
                                        <input type="text" name="bank_branch" id="bank_branch" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">IFSC Code</label>
                                        <input type="text" name="ifsc_code" id="ifsc_code" class="form-control"
                                            style="text-transform:uppercase;">
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="nominee">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Nominee
                                    Details</h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Nominee Name</label>
                                        <input type="text" name="nominee_name" id="nominee_name"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Relation</label>
                                        <input type="text" name="nominee_relation" id="nominee_relation"
                                            class="form-control" placeholder="e.g. Wife, Son">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">S/o, D/o, W/o</label>
                                        <input type="text" name="nominee_so_do_wo" id="nominee_so_do_wo"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Date of Birth</label>
                                        <input type="date" name="nominee_dob" id="nominee_dob" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Mobile No</label>
                                        <input type="text" name="nominee_mobile" id="nominee_mobile"
                                            class="form-control" maxlength="10">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Alt Mobile No</label>
                                        <input type="text" name="nominee_alternate_mobile"
                                            id="nominee_alternate_mobile" class="form-control" maxlength="10">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Email Id</label>
                                        <input type="email" name="nominee_email" id="nominee_email"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nominee Aadhar</label>
                                        <input type="text" name="nominee_aadhar" id="nominee_aadhar"
                                            class="form-control" maxlength="12">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nominee PAN</label>
                                        <input type="text" name="nominee_pan" id="nominee_pan" class="form-control"
                                            style="text-transform:uppercase;" maxlength="10">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Address</label>
                                        <input type="text" name="nominee_address" id="nominee_address"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">PIN Code</label>
                                        <input type="text" name="nominee_pincode" id="nominee_pincode"
                                            class="form-control" maxlength="6">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">State</label>
                                        <input type="text" name="nominee_state" id="nominee_state"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">District</label>
                                        <input type="text" name="nominee_district" id="nominee_district"
                                            class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="empDocs">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Upload
                                    Employee Documents</h6>
                                <div class="alert alert-info py-2 small"><i class="fas fa-info-circle me-1"></i> Images
                                    will be automatically compressed to WebP. Documents must be PDF.</div>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Passport Size Photo (Img)</label>
                                        <input type="file" name="passport_photo" class="form-control"
                                            accept="image/*">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Signature Photo (Img)</label>
                                        <input type="file" name="signature_photo" class="form-control"
                                            accept="image/*">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Aadhar Card (PDF)</label>
                                        <input type="file" name="aadhar_pdf" class="form-control" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">PAN Card (PDF)</label>
                                        <input type="file" name="pan_pdf" class="form-control" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Bank Passbook (PDF)</label>
                                        <input type="file" name="bank_passbook_pdf" class="form-control"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Driving License (PDF)</label>
                                        <input type="file" name="driving_license_pdf" class="form-control"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Passport Doc (PDF)</label>
                                        <input type="file" name="passport_pdf" class="form-control" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">10th Marksheet (PDF)</label>
                                        <input type="file" name="tenth_pdf" class="form-control" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">12th Marksheet (PDF)</label>
                                        <input type="file" name="twelfth_pdf" class="form-control" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Graduation Cert. (PDF)</label>
                                        <input type="file" name="graduation_pdf" class="form-control" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">PG Certificate (PDF)</label>
                                        <input type="file" name="pg_pdf" class="form-control" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Other Doc (PDF)</label>
                                        <input type="file" name="other_pdf" class="form-control" accept=".pdf">
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="nomDocsStatus">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Nominee
                                    Documents</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nominee Photo (Img)</label>
                                        <input type="file" name="nom_passport_photo" class="form-control"
                                            accept="image/*">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nominee Aadhar (PDF)</label>
                                        <input type="file" name="nom_aadhar_pdf" class="form-control" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nominee PAN (PDF)</label>
                                        <input type="file" name="nom_pan_pdf" class="form-control" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nominee Passbook (PDF)</label>
                                        <input type="file" name="nom_bank_passbook_pdf" class="form-control"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nominee DL (PDF)</label>
                                        <input type="file" name="nom_driving_license_pdf" class="form-control"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nominee Passport (PDF)</label>
                                        <input type="file" name="nom_passport_pdf" class="form-control"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nominee 10th (PDF)</label>
                                        <input type="file" name="nom_tenth_pdf" class="form-control" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nominee 12th (PDF)</label>
                                        <input type="file" name="nom_twelfth_pdf" class="form-control"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nominee Graduation (PDF)</label>
                                        <input type="file" name="nom_graduation_pdf" class="form-control"
                                            accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nominee PG (PDF)</label>
                                        <input type="file" name="nom_pg_pdf" class="form-control" accept=".pdf">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nominee Other (PDF)</label>
                                        <input type="file" name="nom_other_pdf" class="form-control" accept=".pdf">
                                    </div>
                                </div>

                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Service
                                    Status</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Employee Status</label>
                                        <select class="form-select" name="emp_status" id="emp_status">
                                            <option value="active">Active</option>
                                            <option value="inactive">In-Active</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 leave-fields" style="display:none;">
                                        <label class="form-label small fw-bold">Date of Leaving</label>
                                        <input type="date" name="d_o_l" id="d_o_l" class="form-control">
                                    </div>
                                    <div class="col-md-4 leave-fields" style="display:none;">
                                        <label class="form-label small fw-bold">Leaving Remarks</label>
                                        <textarea name="d_remarks" id="d_remarks" class="form-control" rows="1"></textarea>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="d-grid mt-4 pt-3 border-top">
                            <button type="submit" class="btn text-white py-2 fw-bold"
                                style="background-color: var(--sidebar-bg);" id="saveBtn">Save Employee Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background-color: var(--sidebar-bg);">
                    <h6 class="modal-title fw-bold"><i class="fas fa-id-badge me-2"></i> Employee Full Profile</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light p-0" id="viewDetailsBody">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {
            const apiToken = localStorage.getItem('admin_token');
            if (!apiToken) window.location.href = '/admin/login';
            $.fn.dataTable.ext.errMode = 'none';

            // ==========================================
            // DESIGNATIONS FETCH KARKE DATALIST ME BHARNA
            // ==========================================
            function loadDesignations() {
                $.ajax({
                    url: '/api/v1/admin/designations',
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(response) {
                        let options = '';
                        // Sirf 'active' designations dikhayenge
                        response.data.forEach(function(item) {
                            if (item.status === 'active') {
                                options += `<option value="${item.designation_name}">`;
                            }
                        });
                        $('#designationList').html(options);
                    },
                    error: function() {
                        console.log("Error fetching designations for datalist");
                    }
                });
            }

            // Page load hote hi designations load kar lo
            loadDesignations();

            // 1. Fetch Branches for Dropdown
            function loadBranches() {
                $.ajax({
                    url: '/api/v1/admin/branches',
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        let options = '<option value="">-- Select Branch --</option>';
                        res.data.forEach(b => {
                            if (b.branch_status === 'active') {
                                options +=
                                    `<option value="${b.id}">${b.branch_name} (${b.branch_id})</option>`;
                            }
                        });
                        $('#branchSelect').html(options);
                    }
                });
            }
            loadBranches();

            // 2. Initialize Desktop DataTables
            let table = $('#empTable').DataTable({
                ajax: {
                    url: '/api/v1/admin/employees',
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    dataSrc: 'data'
                },
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export Excel',
                    className: 'btn btn-success btn-sm shadow-sm'
                }],
                columns: [{
                        data: 'member_id',
                        render: d => `<span class="emp-id-badge">${d}</span>`
                    },
                    {
                        data: 'full_name',
                        render: d => `<span class="fw-medium">${d}</span>`
                    },
                    {
                        data: 'designation',
                        render: d => d || '-'
                    },
                    {
                        data: 'branch',
                        render: b => b ? b.branch_name : '<span class="text-muted">N/A</span>'
                    },
                    {
                        data: 'contact_no'
                    },
                    {
                        data: 'emp_status',
                        render: s => s === 'active' ? `<span class="status-active">Active</span>` :
                            `<span class="status-inactive">Inactive</span>`
                    },
                    { 
    data: 'id', 
    orderable: false, 
    className: 'text-end text-nowrap', // <-- Column ko tootne se rokega
    render: d => `
        <div class="d-flex justify-content-end flex-nowrap gap-1">
            <button class="btn btn-sm btn-light text-success shadow-sm view-btn" title="View" data-id="${d}"><i class="fas fa-eye"></i></button>
            <button class="btn btn-sm btn-light text-primary shadow-sm edit-btn" title="Edit" data-id="${d}"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-light text-danger shadow-sm delete-btn" title="Delete" data-id="${d}"><i class="fas fa-trash"></i></button>
        </div>`
}
                ]
            });

            // 3. Render Mobile Cards
            function loadMobileCards() {
                $.ajax({
                    url: '/api/v1/admin/employees',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        let html = '';
                        res.data.forEach(emp => {
                            let statusHtml = emp.emp_status === 'active' ?
                                `<span class="status-active">Active</span>` :
                                `<span class="status-inactive">Inactive</span>`;
                            html += `
                    <div class="emp-card mobile-emp-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold mb-0" style="color: var(--sidebar-bg);">${emp.full_name}</h6>
                                <span class="emp-id-badge">${emp.member_id}</span>
                            </div>
                            ${statusHtml}
                        </div>
                        <div class="small text-secondary mb-3">
                            <div><i class="fas fa-briefcase me-1 text-muted"></i> ${emp.designation || 'No Designation'}</div>
                            <div class="mt-1"><i class="fas fa-building me-1 text-muted"></i> ${emp.branch ? emp.branch.branch_name : 'No Branch'}</div>
                        </div>
                        <div class="d-flex gap-2 border-top pt-2">
                            <button class="btn btn-sm btn-light text-success flex-fill fw-medium view-btn" data-id="${emp.id}"><i class="fas fa-eye me-1"></i> View</button>
                            <button class="btn btn-sm btn-light text-primary flex-fill fw-medium edit-btn" data-id="${emp.id}"><i class="fas fa-edit me-1"></i> Edit</button>
                            <button class="btn btn-sm btn-light text-danger flex-fill fw-medium delete-btn" data-id="${emp.id}"><i class="fas fa-trash-alt me-1"></i> Del</button>
                        </div>
                    </div>`;
                        });
                        $('#cardsLoader').hide();
                        $('#mobileCardsContainer').html(html);
                    }
                });
            }
            loadMobileCards();

            // Mobile Search & Excel Events
            $('#mobileSearch').on('keyup', function() {
                let v = $(this).val().toLowerCase();
                $('.mobile-emp-item').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1)
                });
            });
            $('#mobileExcelBtn').on('click', () => $('.buttons-excel').click());

            // 4. JS Logics (Password, Marital, Status)
            function generatePassword() {
                let fullName = $('#full_name').val().trim();
                let aadhar = $('#aadhar_no').val().replace(/\D/g, '');
                if (fullName.length < 1 || aadhar.length < 4) {
                    $('#mem_pass').val('');
                    return;
                }
                let namePart = fullName.split(' ')[0].substring(0, 3).toLowerCase();
                namePart = namePart.charAt(0).toUpperCase() + namePart.slice(1);
                $('#mem_pass').val(namePart + '@' + aadhar.slice(-4));
            }
            $('#full_name, #aadhar_no').on('keyup change', generatePassword);

            $('.marital-radio').on('change', function() {
                if ($(this).val() === 'Married') {
                    $('#doa_container').show();
                } else {
                    $('#doa_container').hide();
                    $('#anniversary_date').val('');
                }
            });

            $('#emp_status').on('change', function() {
                if ($(this).val() === 'inactive') {
                    $('.leave-fields').show();
                } else {
                    $('.leave-fields').hide();
                    $('#d_o_l, #d_remarks').val('');
                }
            });

            // 5. Open Add Modal (Reset form)
            window.openAddModal = function() {
                $('#empForm')[0].reset();
                $('#edit_id').val('');
                loadDesignations(); // Refresh options
                $('#form_method').val('POST');
                $('#modalTitle').html(
                    '<i class="fas fa-user-plus me-2 text-primary"></i> Register New Employee');
                $('#mem_pass').val('');
                $('#doa_container, .leave-fields').hide();
                $('.nav-pills a:first').tab('show'); // Reset to first tab
                $('#employeeModal').modal('show');
            };

            // 6. Form Submission (Using FormData for Files)
            $('#empForm').on('submit', function(e) {
                e.preventDefault();
                let id = $('#edit_id').val();
                let method = $('#form_method').val(); // POST or PUT
                let url = id ? `/api/v1/admin/employees/${id}` : `/api/v1/admin/employees`;

                let formData = new FormData(this);
                // Laravel needs POST method for FormData file uploads, but we pass _method=PUT inside it for edits.

                let btn = $('#saveBtn');
                let originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Saving...').prop('disabled', true);

                $.ajax({
                    url: url,
                    type: 'POST', // Always POST, Laravel reads _method from FormData
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(res) {
                        $('#employeeModal').modal('hide');
                        alert(id ? 'Employee Updated Successfully!' :
                            'Employee Registered Successfully!');
                        table.ajax.reload(null, false);
                        loadMobileCards();
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON ? xhr.responseJSON.message :
                            'Upload Failed. Check Server Logs.';
                        alert('Error: ' + msg);
                    },
                    complete: function() {
                        btn.html(originalText).prop('disabled', false);
                    }
                });
            });

            // 7. Edit Button Event (Fetch and populate form)
            // 7. Edit Button Event (Fetch and populate form)
            $(document).on('click', '.edit-btn', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: `/api/v1/admin/employees/${id}`,
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        let emp = res.data;
                        $('#edit_id').val(emp.id);
                        $('#form_method').val('PUT'); // Tell Laravel this is an update
                        $('#modalTitle').html(
                            '<i class="fas fa-user-edit me-2 text-primary"></i> Edit Employee'
                            );

                        // Populate text fields
                        Object.keys(emp).forEach(key => {
                            let input = $(`#empForm [name="${key}"]`);
                            if (input.attr('type') !== 'file' && input.attr('type') !==
                                'radio') {
                                input.val(emp[key]);
                            }
                        });

                        // Radios
                        if (emp.gender) $(`input[name="gender"][value="${emp.gender}"]`).prop(
                            'checked', true);
                        if (emp.marital_status) {
                            $(`input[name="marital_status"][value="${emp.marital_status}"]`)
                                .prop('checked', true).trigger('change');
                        }

                        // Status trigger
                        if (emp.emp_status) {
                            $('#emp_status').val(emp.emp_status).trigger('change');
                        }

                        // Bank Details
                        if (emp.bank_details) {
                            $('#account_name').val(emp.bank_details.account_name);
                            $('#account_no').val(emp.bank_details.account_no);
                            $('#account_type').val(emp.bank_details.account_type);
                            $('#bank_name').val(emp.bank_details.bank_name);
                            $('#bank_branch').val(emp.bank_details.branch);
                            $('#ifsc_code').val(emp.bank_details.ifsc_code);
                        }

                        $('.nav-pills a:first').tab('show');

                        // =========================================================
                        // NAYA: EXISTING FILES PREVIEW LOGIC
                        // =========================================================
                        let fileFields = [
                            'passport_photo', 'signature_photo', 'aadhar_pdf', 'pan_pdf',
                            'bank_passbook_pdf',
                            'driving_license_pdf', 'passport_pdf', 'tenth_pdf',
                            'twelfth_pdf',
                            'graduation_pdf', 'pg_pdf', 'other_pdf',
                            'nom_passport_photo', 'nom_aadhar_pdf', 'nom_pan_pdf',
                            'nom_bank_passbook_pdf',
                            'nom_driving_license_pdf', 'nom_passport_pdf', 'nom_tenth_pdf',
                            'nom_twelfth_pdf', 'nom_graduation_pdf', 'nom_pg_pdf',
                            'nom_other_pdf'
                        ];

                        fileFields.forEach(function(field) {
                            // YAHAN FIX KIYA HAI: d[field] ki jagah emp[field] hoga
                            let filePath = emp[
                            field]; // Database se file ka rasta (path)
                            let input = $(
                            `#employeeModal input[name="${field}"]`); // Form ka file input

                            if (input.length > 0) {
                                let wrapper = input.next('.file-preview-wrapper');
                                let content = wrapper.find('.preview-content');

                                if (filePath) {
                                    // Absolute URL banayein
                                    let fullUrl = filePath.startsWith('/') ? filePath :
                                        '/' + filePath;
                                    let ext = filePath.split('.').pop().toLowerCase();
                                    let imageExts = ['jpg', 'jpeg', 'png', 'webp',
                                        'bmp'];

                                    // Agar Image hai toh photo dikhao
                                    if (imageExts.includes(ext)) {
                                        content.html(
                                            `<img src="${fullUrl}" style="max-height:90px; border-radius:6px; object-fit:contain;">`
                                            );
                                    }
                                    // Agar PDF/Doc hai toh View ka link dikhao
                                    else {
                                        let icon = ext === 'pdf' ?
                                            'fa-file-pdf text-danger' :
                                            'fa-file-alt text-primary';
                                        content.html(`
                                    <div class="d-flex align-items-center gap-2 fw-bold text-dark px-2">
                                        <i class="fas ${icon} fs-3"></i>
                                        <a href="${fullUrl}" target="_blank" class="text-decoration-none" style="font-size:12px;">View Uploaded Document</a>
                                    </div>
                                `);
                                    }
                                    wrapper.show(); // Box ko dikha do
                                } else {
                                    wrapper
                                .hide(); // Agar database me file nahi hai toh hide kar do
                                }
                            }
                        });
                        // =========================================================

                        $('#employeeModal').modal('show');
                    }
                });
            });

            // 8. Delete Button Event
            $(document).on('click', '.delete-btn', function() {
                if (confirm('Are you sure you want to permanently delete this employee?')) {
                    let id = $(this).data('id');
                    $.ajax({
                        url: `/api/v1/admin/employees/${id}`,
                        type: 'DELETE',
                        headers: {
                            'Authorization': 'Bearer ' + apiToken
                        },
                        success: function() {
                            table.ajax.reload(null, false);
                            loadMobileCards();
                        }
                    });
                }
            });

            // 9. View Profile Details Modal
            $(document).on('click', '.view-btn', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: `/api/v1/admin/employees/${id}`,
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        let emp = res.data;
                        let b = emp.bank_details || {};

                        let html = `
                    <div class="p-4 bg-white mb-2 text-center border-bottom">
                        <img src="${emp.passport_photo ? '/' + emp.passport_photo : 'https://ui-avatars.com/api/?name='+emp.full_name+'&background=1A365D&color=fff'}" class="rounded-circle border shadow-sm mb-3" width="100" height="100" style="object-fit:cover;">
                        <h5 class="fw-bold mb-1">${emp.full_name} <span class="badge ${emp.emp_status==='active'?'bg-success':'bg-danger'} ms-2">${emp.emp_status}</span></h5>
                        <div class="text-primary fw-bold mb-2">${emp.member_id}</div>
                        <div class="text-secondary small"><i class="fas fa-briefcase me-1"></i> ${emp.designation||'N/A'} &nbsp;|&nbsp; <i class="fas fa-building me-1"></i> ${emp.branch?emp.branch.branch_name:'N/A'}</div>
                    </div>
                    
                    <div class="p-4">
                        <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">Contact & Address</h6>
                        <div class="row g-3 mb-4 small">
                            <div class="col-6"><strong>Mobile:</strong> <br>${emp.contact_no||'-'}</div>
                            <div class="col-6"><strong>Email:</strong> <br>${emp.email||'-'}</div>
                            <div class="col-12"><strong>Address:</strong> <br>${emp.communication_address||'-'}, ${emp.city||'-'}, ${emp.pin_code||'-'}</div>
                        </div>

                        <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">Bank Details</h6>
                        <div class="row g-3 mb-4 small">
                            <div class="col-6"><strong>Bank Name:</strong> <br>${b.bank_name||'-'}</div>
                            <div class="col-6"><strong>A/c No:</strong> <br>${b.account_no||'-'}</div>
                            <div class="col-6"><strong>IFSC:</strong> <br>${b.ifsc_code||'-'}</div>
                            <div class="col-6"><strong>A/c Type:</strong> <br>${b.account_type||'-'}</div>
                        </div>
                        
                        <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">Nominee</h6>
                        <div class="row g-3 small">
                            <div class="col-6"><strong>Name:</strong> <br>${emp.nominee_name||'-'}</div>
                            <div class="col-6"><strong>Relation:</strong> <br>${emp.nominee_relation||'-'}</div>
                            <div class="col-6"><strong>Mobile:</strong> <br>${emp.nominee_mobile||'-'}</div>
                        </div>
                    </div>
                `;
                        $('#viewDetailsBody').html(html);
                        $('#viewModal').modal('show');
                    }
                });
            });

        });

        $(document).ready(function() {

            // 1. Har file input ke neeche automatically Preview Container add karein
            $('input[type="file"]').each(function() {
                $(this).after(`
            <div class="file-preview-wrapper">
                <button type="button" class="btn btn-danger remove-preview-btn" title="Remove File">
                    <i class="fas fa-times"></i>
                </button>
                <div class="preview-content text-center"></div>
            </div>
        `);
            });

            // 2. Jab koi file select kare, toh Preview Generate karein
            $(document).on('change', 'input[type="file"]', function(e) {
                let input = this;
                let file = input.files[0];
                let wrapper = $(this).next('.file-preview-wrapper');
                let content = wrapper.find('.preview-content');

                if (file) {
                    // Agar file Image hai (Preview dikhayenge)
                    if (file.type.startsWith('image/')) {
                        let reader = new FileReader();
                        reader.onload = function(event) {
                            content.html(
                                `<img src="${event.target.result}" style="max-height:90px; border-radius:6px; object-fit:contain;">`
                                );
                            wrapper.slideDown();
                        }
                        reader.readAsDataURL(file);
                    }
                    // Agar file PDF/Doc hai (Icon aur Naam dikhayenge)
                    else {
                        let icon = file.type === 'application/pdf' ? 'fa-file-pdf text-danger' :
                            'fa-file-alt text-primary';
                        content.html(`
                    <div class="d-flex align-items-center gap-2 fw-bold text-dark px-2">
                        <i class="fas ${icon} fs-3"></i>
                        <span style="font-size:12px; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${file.name}</span>
                    </div>
                `);
                        wrapper.slideDown();
                    }
                } else {
                    wrapper.slideUp();
                }
            });

            // 3. Jab 'X' par click karein, toh file hata dein aur preview band kar dein
            $(document).on('click', '.remove-preview-btn', function() {
                let wrapper = $(this).closest('.file-preview-wrapper');
                let input = wrapper.prev('input[type="file"]');

                input.val(''); // Input field empty kar diya
                wrapper.slideUp(); // Preview hide kar diya
            });

            // 4. Modal band hone par saare previews aur file inputs clear ho jayein
            $('.modal').on('hidden.bs.modal', function() {
                $(this).find('input[type="file"]').val('');
                $(this).find('.file-preview-wrapper').hide();
            });
        });
    </script>
@endpush
