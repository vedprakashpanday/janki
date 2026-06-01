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
        }

        .table-custom td {
            font-size: 13px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

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
            white-space: nowrap;
        }

        .nav-pills .nav-link.active {
            background-color: var(--sidebar-bg);
            color: #fff;
        }

        .form-label {
            color: #475569;
            margin-bottom: 0.2rem;
        }

        input[type="file"]::file-selector-button {
            background-color: var(--bg-light);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            border-radius: 4px;
            padding: 4px 10px;
            margin-right: 10px;
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
                <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);"><i
                        class="fas fa-id-card text-primary me-2"></i>Employee Management</h4>
                <p class="text-secondary small d-none d-md-block mb-0">Manage all administrative employees and documents</p>
            </div>
            <button class="btn text-white px-4 py-2 shadow-sm secured-item" data-permission="employee_add"
                style="background-color: var(--brand-primary);" onclick="openAddModal()">
                <i class="fas fa-user-plus me-1"></i> <span class="d-none d-md-inline">Add Employee</span>
            </button>
        </div>

        <div class="card border-0 shadow-sm mb-3" id="globalFilterCard">
            <div class="card-body p-3 d-flex align-items-center gap-3 flex-wrap">
                <span class="fw-bold text-secondary"><i class="fas fa-filter text-primary me-1"></i> Data Filter:</span>

                <div class="input-group" style="max-width: 250px;" id="filterCompanyContainer">
                    <span class="input-group-text bg-white"><i class="fas fa-industry text-primary"></i></span>
                    <select class="form-select fw-medium text-secondary" id="filter_company">
                        <option value="">-- All Companies --</option>
                    </select>
                </div>

                <div class="input-group" style="max-width: 250px;" id="filterBranchContainer">
                    <span class="input-group-text bg-white"><i class="fas fa-building text-primary"></i></span>
                    <select class="form-select fw-medium text-secondary" id="filter_branch">
                        <option value="">-- All Branches --</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search Employee...">
            <button class="btn text-white shadow-sm" style="background-color: #10b981;" id="mobileExcelBtn"><i
                    class="fas fa-file-excel"></i></button>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex gap-2">
                            <button class="btn btn-danger px-3 py-2 shadow-sm d-none secured-item"
                                data-permission="employee_delete" id="bulkDeleteBtn">
                                <i class="fas fa-trash-alt me-1"></i> Delete Selected
                            </button>
                        </div>
                    </div>

                    <table id="empTable" class="table table-hover table-custom w-100">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAll"
                                        class="form-check-input border-secondary"></th>
                                <th>Emp ID</th>
                                <th>Name</th>
                                <th>Designation</th>
                                <th>Company & Branch</th>
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

    <!-- Registration Modal -->
    <div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom pb-3">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);" id="modalTitle">
                        <i class="fas fa-user-plus me-2 text-primary"></i> Register New Employee
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-3 p-md-4">
                    <form id="empForm" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="_method" id="form_method" value="POST">

                        <ul class="nav nav-pills mb-4 bg-light p-1 rounded-3 shadow-sm flex-nowrap overflow-auto">
                            <li class="nav-item"><a class="nav-link active small py-2 px-3" data-bs-toggle="tab"
                                    href="#personal"><i class="fas fa-user me-1"></i> Personal</a></li>
                            <li class="nav-item"><a class="nav-link small py-2 px-3" data-bs-toggle="tab"
                                    href="#bank"><i class="fas fa-university me-1"></i> Bank</a></li>
                            <li class="nav-item"><a class="nav-link small py-2 px-3" data-bs-toggle="tab"
                                    href="#nominee"><i class="fas fa-users me-1"></i> Nominee</a></li>
                            <li class="nav-item"><a class="nav-link small py-2 px-3" data-bs-toggle="tab"
                                    href="#empDocs"><i class="fas fa-file-alt me-1"></i> Emp Docs</a></li>
                            <li class="nav-item"><a class="nav-link small py-2 px-3" data-bs-toggle="tab"
                                    href="#nomDocsStatus"><i class="fas fa-clipboard-check me-1"></i> Nom Docs &
                                    Status</a></li>
                        </ul>

                        <div class="tab-content">
                            <!-- PERSONAL TAB -->
                            <div class="tab-pane fade show active" id="personal">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Personal
                                    Details</h6>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-3" id="modalCompanyContainer">
                                        <label class="form-label small fw-bold">Select Company <small
                                                class="text-primary">(Blank=Master)</small></label>
                                        <select class="form-control select2-search" id="m_company_id" name="company_id"
                                            style="width: 100%;">
                                            <option value="">-- Master Head Office --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3" id="modalBranchContainer">
                                        <label class="form-label small fw-bold">Select Branch <small
                                                class="text-primary">(Blank=HO)</small></label>
                                        <select class="form-control select2-search" id="m_branch_id" name="branch_id"
                                            style="width: 100%;">
                                            <option value="">-- Head Office --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3" id="modalDepartmentContainer">
                                        <label class="form-label small fw-bold">Department <span
                                                class="text-danger">*</span></label>
                                        <select name="department_id" id="m_department_id"
                                            class="form-control select2-search" style="width: 100%;" required>
                                            <option value="">-- Select Company First --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Designation <span
                                                class="text-danger">*</span></label>
                                        <select name="designation_id" id="designation_input"
                                            class="form-control select2-search" style="width: 100%;" required>
                                            <option value="">-- Select Dept First --</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Full Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="full_name" id="full_name" class="form-control"
                                            placeholder="Enter Full Name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">S/O, D/O, Spouse's Name</label>
                                        <input type="text" name="father_spouse_name" id="father_spouse_name"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Mother's Name</label>
                                        <input type="text" name="mother_name" id="mother_name" class="form-control">
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
                                    <div class="col-md-4" id="doa_container" style="display:none;">
                                        <label class="form-label small fw-bold">Date of Anniversary</label>
                                        <input type="date" name="anniversary_date" id="anniversary_date"
                                            class="form-control">
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

                            <!-- BANK TAB -->
                            <div class="tab-pane fade" id="bank">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Bank
                                    Details</h6>
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label small fw-bold">Account Holder
                                            Name</label><input type="text" name="account_name" id="account_name"
                                            class="form-control"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Bank A/c
                                            No</label><input type="text" name="account_no" id="account_no"
                                            class="form-control"></div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Account Type</label>
                                        <select class="form-select" name="account_type" id="account_type">
                                            <option value="">-- Select Type --</option>
                                            <option value="saving">Saving Account</option>
                                            <option value="current">Current Account</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Bank Name</label><input
                                            type="text" name="bank_name" id="bank_name" class="form-control"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Branch
                                            Name</label><input type="text" name="bank_branch" id="bank_branch"
                                            class="form-control"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">IFSC Code</label><input
                                            type="text" name="ifsc_code" id="ifsc_code" class="form-control"
                                            style="text-transform:uppercase;"></div>
                                </div>
                            </div>

                            <!-- NOMINEE TAB -->
                            <div class="tab-pane fade" id="nominee">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Nominee
                                    Details</h6>
                                <div class="row g-3">
                                    <div class="col-md-3"><label class="form-label small fw-bold">Nominee
                                            Name</label><input type="text" name="nominee_name" id="nominee_name"
                                            class="form-control"></div>
                                    <div class="col-md-3"><label class="form-label small fw-bold">Relation</label><input
                                            type="text" name="nominee_relation" id="nominee_relation"
                                            class="form-control"></div>
                                    <div class="col-md-3"><label class="form-label small fw-bold">S/o, D/o,
                                            W/o</label><input type="text" name="nominee_so_do_wo"
                                            id="nominee_so_do_wo" class="form-control"></div>
                                    <div class="col-md-3"><label class="form-label small fw-bold">Date of
                                            Birth</label><input type="date" name="nominee_dob" id="nominee_dob"
                                            class="form-control"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Mobile No</label><input
                                            type="text" name="nominee_mobile" id="nominee_mobile"
                                            class="form-control" maxlength="10"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Aadhar
                                            Card</label><input type="text" name="nominee_aadhar" id="nominee_aadhar"
                                            class="form-control" maxlength="12"></div>
                                </div>
                            </div>

                            <!-- EMPLOYEE DOCS TAB -->
                            <div class="tab-pane fade" id="empDocs">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Upload
                                    Documents</h6>
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label small fw-bold">Passport Photo
                                            (Img)</label><input type="file" name="passport_photo" class="form-control"
                                            accept="image/*"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Signature
                                            (Img)</label><input type="file" name="signature_photo"
                                            class="form-control" accept="image/*"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Aadhar Card
                                            (PDF)</label><input type="file" name="aadhar_pdf" class="form-control"
                                            accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">PAN Card
                                            (PDF)</label><input type="file" name="pan_pdf" class="form-control"
                                            accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Bank Passbook
                                            (PDF)</label><input type="file" name="bank_passbook_pdf"
                                            class="form-control" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Driving License
                                            (PDF)</label><input type="file" name="driving_license_pdf"
                                            class="form-control" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">10th Marksheet
                                            (PDF)</label><input type="file" name="tenth_pdf" class="form-control"
                                            accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">12th Marksheet
                                            (PDF)</label><input type="file" name="twelfth_pdf" class="form-control"
                                            accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Graduation Cert
                                            (PDF)</label><input type="file" name="graduation_pdf" class="form-control"
                                            accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">PG Cert
                                            (PDF)</label><input type="file" name="pg_pdf" class="form-control"
                                            accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Other Docs
                                            (PDF)</label><input type="file" name="other_pdf" class="form-control"
                                            accept=".pdf"></div>
                                </div>
                            </div>

                            <!-- NOMINEE DOCS & STATUS TAB -->
                            <div class="tab-pane fade" id="nomDocsStatus">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Nominee
                                    Docs</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4"><label class="form-label small fw-bold">Nominee Photo
                                            (Img)</label><input type="file" name="nom_passport_photo"
                                            class="form-control" accept="image/*"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Nominee Aadhar
                                            (PDF)</label><input type="file" name="nom_aadhar_pdf" class="form-control"
                                            accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Nominee PAN
                                            (PDF)</label><input type="file" name="nom_pan_pdf" class="form-control"
                                            accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Nominee Passbook
                                            (PDF)</label><input type="file" name="nom_bank_passbook_pdf"
                                            class="form-control" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Nominee 10th
                                            (PDF)</label><input type="file" name="nom_tenth_pdf" class="form-control"
                                            accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Nominee 12th
                                            (PDF)</label><input type="file" name="nom_twelfth_pdf"
                                            class="form-control" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Nominee Grad
                                            (PDF)</label><input type="file" name="nom_graduation_pdf"
                                            class="form-control" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Nominee PG
                                            (PDF)</label><input type="file" name="nom_pg_pdf" class="form-control"
                                            accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Nominee Others
                                            (PDF)</label><input type="file" name="nom_other_pdf" class="form-control"
                                            accept=".pdf"></div>
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
                                    <div class="col-md-4 leave-fields" style="display:none;"><label
                                            class="form-label small fw-bold">Date of Leaving</label><input type="date"
                                            name="d_o_l" id="d_o_l" class="form-control"></div>
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

    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background-color: var(--sidebar-bg);">
                    <h6 class="modal-title fw-bold"><i class="fas fa-id-badge me-2"></i> Employee Full Profile</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light p-0" id="viewDetailsBody"></div>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        let table;
        // GLOBAL TOKEN RETAINED SO NOTHING CRASHES
        const apiToken = localStorage.getItem('admin_token');
        let fullBranchesData = [];

        // 🔥 MOCK ROLES FOR TESTING 🔥
        const loggedInRole = 'super_admin';
        const loggedInCompanyId = 1;
        const loggedInBranchId = 1;

        window.openAddModal = function() {
            $('#empForm')[0].reset();
            $('#edit_id').val('');
            $('#form_method').val('POST');

            $('#m_company_id').val('').trigger('change');
            $('#m_branch_id').val('').trigger('change');
            $('#designation_input').val('').trigger('change');

            if (loggedInRole === 'super_admin') {
                $('#m_branch_id').html('<option value="">-- Master Head Office (No Branch) --</option>');
                loadDesignations('', '');
            } else if (loggedInRole === 'director') {
                $('#m_company_id').val(loggedInCompanyId).trigger('change');
            } else if (loggedInRole === 'branch_manager') {
                $('#m_company_id').html(`<option value="${loggedInCompanyId}">Assigned Company</option>`);
                $('#m_branch_id').html(`<option value="${loggedInBranchId}">Assigned Branch</option>`);
                loadDesignations(loggedInCompanyId, loggedInBranchId);
            }

            $('#modalTitle').html('<i class="fas fa-user-plus me-2 text-primary"></i> Register New Employee');
            $('#doa_container, .leave-fields').hide();
            $('.nav-pills a:first').tab('show');
            $('#m_unmarried').prop('checked', true).trigger('change');

            // Clear File Previews
            $('.file-preview-wrapper').hide().find('.preview-content').empty();

            $('#employeeModal').modal('show');
        };

        $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';

            $('.select2-search').select2({
                dropdownParent: $('#employeeModal'),
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });

            function applyRoleRestrictions() {
                if (loggedInRole === 'director' || loggedInRole === 'company_head') {
                    $('#filterCompanyContainer, #modalCompanyContainer').hide();
                } else if (loggedInRole === 'branch_manager' || loggedInRole === 'branch_employee') {
                    $('#globalFilterCard, #modalCompanyContainer, #modalBranchContainer').hide();
                }
            }
            applyRoleRestrictions();

            // 🔥 DYNAMIC DESIGNATION LOADER 🔥
            window.loadDesignations = function(compId = '', branchId = '') {
                $('#designation_input').html('<option value="">-- Loading... --</option>').trigger('change');
                $.ajax({
                    url: '/api/v1/designations',
                    type: 'GET',
                    data: {
                        strict_filter: true,
                        company_id: compId,
                        branch_id: branchId
                    },
                    success: function(res) {
                        let opts = '<option value="">-- Select Designation --</option>';
                        res.data.forEach(item => {
                            if (item.status === 'active') {
                                opts +=
                                    `<option value="${item.id}">${item.designation_name} (${item.level})</option>`;
                            }
                        });
                        $('#designation_input').html(opts).trigger('change');
                    }
                });
            }

            function fetchCompanies() {
                $.ajax({
                    url: '/api/v1/get-active-companies',
                    type: 'GET',
                    success: function(res) {
                        let opts = '<option value="">-- Master Head Office --</option>';
                        res.data.forEach(c => {
                            opts +=
                                `<option value="${c.id}">${c.company_name} (${c.company_code})</option>`;
                        });
                        $('#filter_company').html(opts);
                        $('#m_company_id').html(opts);
                        if (loggedInRole === 'director') {
                            $('#filter_company, #m_company_id').val(loggedInCompanyId).trigger(
                            'change');
                        }
                    }
                });
            }

            function fetchBranches() {
                $.ajax({
                    url: '/api/v1/branches',
                    type: 'GET',
                    success: function(res) {
                        fullBranchesData = res.data;
                        let opts = '<option value="">-- All Branches --</option>';
                        res.data.forEach(b => {
                            if (b.branch_status === 'active') opts +=
                                `<option value="${b.id}">${b.branch_name} (${b.branch_id})</option>`;
                        });
                        $('#filter_branch').html(opts);
                    }
                });
            }

            fetchCompanies();
            fetchBranches();

            // Cascading Form Triggers
            $('#m_company_id').on('change', function() {
                let compId = $(this).val();
                let bOpts = '<option value="">-- Head Office (No Branch) --</option>';
                if (compId) {
                    fullBranchesData.filter(b => b.company_id == compId && b.branch_status === 'active')
                        .forEach(b => {
                            bOpts += `<option value="${b.id}">${b.branch_name}</option>`;
                        });
                }
                $('#m_branch_id').html(bOpts).trigger('change');

                $('#m_department_id').html('<option value="">-- Loading... --</option>').trigger('change');
                $.ajax({
                    url: '/api/v1/get-departments-by-company',
                    type: 'GET',
                    data: {
                        company_id: compId
                    },
                    success: function(res) {
                        let opts = '<option value="">-- Select Department --</option>';
                        res.data.forEach(d => {
                            opts +=
                                `<option value="${d.id}">${d.department_name}</option>`;
                        });
                        $('#m_department_id').html(opts).trigger('change');
                    }
                });
            });

            $('#m_department_id').on('change', function() {
                let deptId = $(this).val();
                $('#designation_input').html('<option value="">-- Select Dept First --</option>').trigger(
                    'change');
                if (deptId) {
                    $('#designation_input').html('<option value="">-- Loading... --</option>');
                    $.ajax({
                        url: '/api/v1/get-designations-by-dept',
                        type: 'GET',
                        data: {
                            department_id: deptId
                        },
                        success: function(res) {
                            let opts = '<option value="">-- Select Designation --</option>';
                            res.data.forEach(item => {
                                opts +=
                                    `<option value="${item.id}">${item.designation_name} (${item.designation_code})</option>`;
                            });
                            $('#designation_input').html(opts).trigger('change');
                        }
                    });
                }
            });

            $('#m_branch_id').on('change', function() {
                let compId = $('#m_company_id').val();
                let branchId = $(this).val();
                if (compId !== null || branchId !== null) loadDesignations(compId, branchId);
            });

            // Table Filters
            $('#filter_company').change(function() {
                let compId = $(this).val();
                let bOpts = '<option value="">-- All Branches --</option>';
                if (compId) {
                    fullBranchesData.filter(b => b.company_id == compId && b.branch_status === 'active')
                        .forEach(b => {
                            bOpts += `<option value="${b.id}">${b.branch_name}</option>`;
                        });
                }
                $('#filter_branch').html(bOpts);
                table.ajax.reload();
            });
            $('#filter_branch').change(function() {
                table.ajax.reload();
            });

            // Table Init
            table = $('#empTable').DataTable({
                serverSide: false,
                autoWidth: false,
                ajax: {
                    url: '/api/v1/employees',
                    type: 'GET',
                    data: function(d) {
                        d.company_id = $('#filter_company').val() || (loggedInRole === 'director' ?
                            loggedInCompanyId : '');
                        d.branch_id = $('#filter_branch').val() || (loggedInRole === 'branch_manager' ?
                            loggedInBranchId : '');
                    },
                    dataSrc: 'data'
                },
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export Excel',
                    className: 'btn btn-success btn-sm'
                }],
                columns: [{
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data) {
                            return `<input type="checkbox" class="form-check-input border-secondary row-checkbox" value="${data}">`;
                        }
                    },
                    {
                        data: 'member_id',
                        render: d => `<span class="emp-id-badge">${d}</span>`
                    },
                    {
                        data: 'full_name',
                        render: d => `<span class="fw-medium">${d}</span>`
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (typeof row.designation === 'object' && row.designation !== null) {
                                return row.designation.designation_name || '-';
                            }
                            return row.designation || '-';
                        }
                    },
                    {
                        data: 'branch',
                        render: b => b ?
                            `<span class="small fw-bold text-secondary"><i class="fas fa-building me-1"></i>${b.company ? b.company.company_name : 'No Company'} <br><i class="fas fa-code-branch me-1"></i>${b.branch_name}</span>` :
                            'Head Office'
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
                        render: d => `<div class="text-end flex-nowrap">
                            <button class="btn btn-sm btn-light text-success view-btn me-1" data-id="${d}"><i class="fas fa-eye"></i></button>
                            <button class="btn btn-sm btn-light text-primary edit-btn secured-item" data-permission="employee_edit" data-id="${d}"><i class="fas fa-edit"></i></button>
                        </div>`
                    }
                ],
                drawCallback: function(settings) {
                    if (settings.json && settings.json.data && settings.json.data.length > 0) {
                        loadMobileCards(settings.json.data);
                    } else if (this.api().data().length > 0) {
                        loadMobileCards(this.api().data().toArray());
                    } else {
                        loadMobileCards([]); // Clean if empty
                    }

                    $('#selectAll').prop('checked', false);
                    toggleBulkDeleteBtn();
                }
            });

            // Mobile Real-Time Search
            $('#mobileSearch').on('keyup', function() {
                let value = $(this).val().toLowerCase();
                $('.mobile-emp-item').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });

            // Mobile Cards Logic
            function loadMobileCards(data) {
                $('#cardsLoader').hide();
                let html = '';
                if (!data || data.length === 0) {
                    html =
                        '<div class="text-center text-muted p-3 border rounded bg-light">No employees found.</div>';
                } else {
                    data.forEach(emp => {
                        let statusHtml = emp.emp_status === 'active' ?
                            `<span class="status-active">Active</span>` :
                            `<span class="status-inactive">Inactive</span>`;
                        let branchName = emp.branch ? emp.branch.branch_name : 'Master HO';

                        let desigName = '-';
                        if (typeof emp.designation === 'object' && emp.designation !== null) {
                            desigName = emp.designation.designation_name;
                        } else if (emp.designation) {
                            desigName = emp.designation;
                        }

                        html += `
                        <div class="emp-card mobile-emp-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div><h6 class="fw-bold mb-0">${emp.full_name}</h6><span class="emp-id-badge">${emp.member_id}</span></div>
                                ${statusHtml}
                            </div>
                            <div class="small text-secondary mb-3">
                                <div><i class="fas fa-briefcase me-1 text-muted"></i> ${desigName}</div>
                                <div class="mt-1"><i class="fas fa-building me-1 text-muted"></i> ${branchName}</div>
                            </div>
                            <div class="d-flex gap-2 border-top pt-2">
                                <button class="btn btn-sm btn-light text-success flex-fill fw-medium view-btn" data-id="${emp.id}"><i class="fas fa-eye me-1"></i> View</button>
                                <button class="btn btn-sm btn-light text-primary flex-fill fw-medium edit-btn secured-item" data-permission="employee_edit" data-id="${emp.id}"><i class="fas fa-edit me-1"></i> Edit</button>
                                <button class="btn btn-sm btn-light text-danger flex-fill fw-medium delete-btn secured-item" data-permission="employee_delete" data-id="${emp.id}"><i class="fas fa-trash-alt me-1"></i> Del</button>
                            </div>
                        </div>`;
                    });
                }
                $('#mobileCardsContainer').html(html);
            }

            // Save Form
            $('#empForm').on('submit', function(e) {
                e.preventDefault();
                let id = $('#edit_id').val();
                let formData = new FormData(this);
                if (loggedInRole === 'branch_manager') {
                    formData.append('branch_id', loggedInBranchId);
                    formData.append('company_id', loggedInCompanyId);
                }

                let btn = $('#saveBtn');
                btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
                $.ajax({
                    url: id ? `/api/v1/employees/${id}` : `/api/v1/employees`,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function() {
                        $('#employeeModal').modal('hide');
                        Swal.fire('Success', 'Saved Successfully', 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON ? xhr.responseJSON.message :
                            'Failed', 'error');
                    },
                    complete: function() {
                        btn.html('Save Employee Record').prop('disabled', false);
                    }
                });
            });

            // Edit Form
            $(document).on('click', '.edit-btn', function() {
                let id = $(this).data('id');
                $.get({
                    url: `/api/v1/employees/${id}`,
                    success: function(res) {
                        let emp = res.data;
                        $('#edit_id').val(emp.id);
                        $('#form_method').val('PUT');

                        Object.keys(emp).forEach(key => {
                            let input = $(`#empForm [name="${key}"]`);
                            if (input.attr('type') !== 'file' && input.attr('type') !==
                                'radio') {
                                input.val(emp[key]);
                            }
                        });

                        if (emp.gender) $(`input[name="gender"][value="${emp.gender}"]`).prop(
                            'checked', true);
                        if (emp.marital_status) $(
                                `input[name="marital_status"][value="${emp.marital_status}"]`)
                            .prop('checked', true).trigger('change');
                        if (emp.emp_status) $('#emp_status').val(emp.emp_status).trigger(
                            'change');

                        if (loggedInRole === 'super_admin' || loggedInRole === 'director') {
                            $('#m_company_id').val(emp.company_id || '').trigger('change');
                            setTimeout(() => {
                                $('#m_branch_id').val(emp.branch_id || '').trigger(
                                    'change');
                                $('#m_department_id').val(emp.department_id || '')
                                    .trigger('change');
                                setTimeout(() => {
                                    $('#designation_input').val(emp
                                        .designation_id || '').trigger(
                                        'change');
                                }, 400);
                            }, 400);
                        }

                        // Preview Files handling
                        let fileFields = [
                            'passport_photo', 'signature_photo', 'aadhar_pdf', 'pan_pdf',
                            'bank_passbook_pdf', 'driving_license_pdf',
                            'tenth_pdf', 'twelfth_pdf', 'graduation_pdf', 'pg_pdf',
                            'other_pdf',
                            'nom_passport_photo', 'nom_aadhar_pdf', 'nom_pan_pdf',
                            'nom_bank_passbook_pdf',
                            'nom_tenth_pdf', 'nom_twelfth_pdf', 'nom_graduation_pdf',
                            'nom_pg_pdf', 'nom_other_pdf'
                        ];

                        fileFields.forEach(function(field) {
                            let filePath = emp[field];
                            let input = $(`#empForm input[name="${field}"]`);
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
                                    content.html(
                                        `<div class="d-flex align-items-center gap-2 fw-bold text-dark px-2"><i class="fas fa-file-pdf text-danger fs-3"></i><a href="${fullUrl}" target="_blank" class="text-decoration-none" style="font-size:12px;">View Doc</a></div>`
                                        );
                                }
                                wrapper.show();
                            }
                        });

                        $('.nav-pills a:first').tab('show');
                        $('#employeeModal').modal('show');
                    }
                });
            });

            // VIEW MODAL LOGIC
            $(document).on('click', '.view-btn', function() {
                let id = $(this).data('id');

                $.ajax({
                    url: `/api/v1/employees/${id}`,
                    type: 'GET',
                    success: function(res) {
                        let d = res.data;
                        let branchText = d.branch ?
                            `${d.branch.company ? d.branch.company.company_name : 'No Company'} - ${d.branch.branch_name}` :
                            'Head Office';
                        let desigName = (typeof d.designation === 'object' && d.designation !==
                            null) ? d.designation.designation_name : (d.designation || '-');

                        let html = `
                            <div class="p-3">
                                <table class="table table-bordered table-sm mb-0">
                                    <tr><th width="35%">Emp ID</th><td class="text-primary fw-bold">${d.member_id || '-'}</td></tr>
                                    <tr><th>Full Name</th><td>${d.full_name || '-'}</td></tr>
                                    <tr><th>Designation</th><td>${desigName}</td></tr>
                                    <tr><th>Company & Branch</th><td>${branchText}</td></tr>
                                    <tr><th>Mobile No.</th><td>${d.contact_no || '-'}</td></tr>
                                    <tr><th>Email ID</th><td>${d.email || '-'}</td></tr>
                                    <tr><th>Aadhar No.</th><td>${d.aadhar_no || '-'}</td></tr>
                                    <tr><th>PAN No.</th><td class="text-uppercase">${d.pan_no || '-'}</td></tr>
                                    <tr><th>Joining Date</th><td>${d.doj || '-'}</td></tr>
                                    <tr><th>Status</th><td>${d.emp_status === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'}</td></tr>
                                </table>
                            </div>
                        `;
                        $('#viewDetailsBody').html(html);
                        $('#viewModal').modal('show');
                    }
                });
            });

            // Delete Individual
            $(document).on('click', '.delete-btn', function() {
                Swal.fire({
                    title: 'Are you sure?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/api/v1/employees/${$(this).data('id')}`,
                            type: 'DELETE',
                            success: function() {
                                Swal.fire('Deleted!', '', 'success');
                                table.ajax.reload(null, false);
                            }
                        });
                    }
                });
            });

            // File Previews Layout
            $('input[type="file"]').each(function() {
                $(this).after(
                    `<div class="file-preview-wrapper"><button type="button" class="btn btn-danger remove-preview-btn"><i class="fas fa-times"></i></button><div class="preview-content text-center"></div></div>`
                    );
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
                        };
                        reader.readAsDataURL(file);
                    } else {
                        content.html(
                            `<div class="d-flex align-items-center gap-2 fw-bold text-dark px-2"><i class="fas fa-file-pdf text-danger fs-3"></i><span style="font-size:12px;">${file.name}</span></div>`
                            );
                        wrapper.slideDown();
                    }
                } else {
                    wrapper.slideUp();
                }
            });
            $(document).on('click', '.remove-preview-btn', function() {
                $(this).closest('.file-preview-wrapper').prev('input[type="file"]').val('');
                $(this).closest('.file-preview-wrapper').slideUp();
            });

            // Bulk Delete Logic
            $('#selectAll').on('change', function() {
                $('.row-checkbox').prop('checked', this.checked);
                toggleBulkDeleteBtn();
            });
            $('#empTable tbody').on('change', '.row-checkbox', function() {
                if (!this.checked) $('#selectAll').prop('checked', false);
                if ($('.row-checkbox:checked').length === $('.row-checkbox').length) $('#selectAll').prop(
                    'checked', true);
                toggleBulkDeleteBtn();
            });

            function toggleBulkDeleteBtn() {
                if ($('.row-checkbox:checked').length > 0) $('#bulkDeleteBtn').removeClass('d-none');
                else $('#bulkDeleteBtn').addClass('d-none');
            }
            $('#bulkDeleteBtn').on('click', function() {
                let selectedIds = [];
                $('.row-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length > 0) {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: `You are about to delete ${selectedIds.length} employee(s). This cannot be undone!`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete them!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            let btn = $(this);
                            let originalText = btn.html();
                            btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Deleting...')
                                .prop('disabled', true);

                            $.ajax({
                                url: '/api/v1/bulk-delete',
                                type: 'POST',
                                data: {
                                    table_name: 'adm_regist',
                                    ids: selectedIds
                                },
                                success: function(res) {
                                    Swal.fire('Deleted!', res.message, 'success');
                                    table.ajax.reload(null, false);
                                },
                                error: function(err) {
                                    Swal.fire('Error', err.responseJSON.message ||
                                        'Failed to delete', 'error');
                                },
                                complete: function() {
                                    btn.html(originalText).prop('disabled', false);
                                    $('#selectAll').prop('checked', false);
                                    toggleBulkDeleteBtn();
                                }
                            });
                        }
                    });
                }
            });

            // Password Generator
            function generatePassword() {
                let fullName = $('#full_name').val().trim();
                let aadhar = $('#aadhar_no').val().replace(/\D/g, '');
                if (fullName.length < 1 || aadhar.length < 4) {
                    $('#mem_pass').val('');
                    return;
                }
                let firstNamePart = fullName.split(' ')[0].substring(0, 3).toLowerCase();
                let formattedName = firstNamePart.charAt(0).toUpperCase() + firstNamePart.slice(1);
                let aadharLast4 = aadhar.slice(-4);
                $('#mem_pass').val(formattedName + '@' + aadharLast4);
            }
            $('#full_name, #aadhar_no').on('keyup change', generatePassword);

            // Marital Status & Leave Status Toggles
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
                    $('#d_o_l').val('');
                }
            });
        });
    </script>
@endpush
