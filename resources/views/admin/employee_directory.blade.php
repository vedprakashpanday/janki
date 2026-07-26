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
            transition: all 0.2s ease-in-out;
        }

        .emp-card:active {
            transform: scale(0.98);
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

        .status-inactive,
        .status-terminated,
        .status-resigned {
            background: #fee2e2;
            color: #991b1b;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
        }

        .status-transferred {
            background: #e0e7ff;
            color: #3730a3;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
        }

        .status-pending {
            background: #fef3c7;
            color: #9a3412;
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
            .dt-buttons {
        display: none !important;
    }

            .nav-tabs {
                flex-wrap: nowrap !important;
                overflow-x: auto !important;
                overflow-y: hidden !important;
                -webkit-overflow-scrolling: touch !important;
                border-bottom: 0 !important;
            }

            .nav-tabs::-webkit-scrollbar {
                height: 3px;
            }

            .nav-tabs::-webkit-scrollbar-thumb {
                background: var(--brand-primary);
                border-radius: 10px;
            }
        }

        .nav-pills .nav-link,
        .nav-tabs .nav-link {
            color: #64748b;
            font-weight: 600;
            white-space: nowrap;
        }

        .nav-pills .nav-link.active,
        .nav-tabs .nav-link.active {
            background-color: var(--sidebar-bg);
            color: #fff;
            border: none;
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

        .action-btns .btn {
            padding: 4px 8px;
            font-size: 12px;
        }

        #mobileBulkActions {
            z-index: 1040;
            transition: transform 0.3s ease-in-out;
            transform: translateY(100%);
        }

        #mobileBulkActions.show-bar {
            transform: translateY(0%);
        }
    </style>

    <div class="container-fluid p-0 pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);"><i
                        class="fas fa-id-card text-primary me-2"></i>Employee Directory</h4>
                <p class="text-secondary small d-none d-md-block mb-0">Manage all administrative employees and documents</p>
            </div>
            <button class="btn text-white px-4 py-2 shadow-sm secured-item" data-permission="emp_dir_add"
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

<!-- 🔥 FIXED: Removed unsafe PHP auth check, using 'd-none' and JS handling 🔥 -->
<div class="input-group mb-3 d-md-none">
    <input type="text" id="mobileSearch" class="form-control" placeholder="Search Employee...">
    
    <button class="btn btn-success d-none" id="mobileCustomExcelBtn">
        <i class="fas fa-file-excel"></i>
    </button>
</div>

        <!-- यह Wrapper DataTable को केवल Tablet और Desktop पर दिखाएगा, Mobile पर Hide कर देगा -->
<div class="d-none d-md-block">
    <div class="card border-0 shadow-sm secured-item" id="desktopTableContainer" data-permission="emp_dir_view" style="display: none;">
        <div class="card-body">
            <div class="table-responsive">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex gap-2">
                        <button class="btn btn-danger px-3 py-2 shadow-sm d-none secured-item" data-permission="emp_dir_delete" id="bulkDeleteBtn">
                            <i class="fas fa-trash-alt me-1"></i> Delete Selected
                        </button>
                    </div>
                </div>

                <table id="empTable" class="table table-hover table-custom w-100">
                    <!-- Table Head & Body -->
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAll" class="form-check-input border-secondary"></th>
                            <th class="d-none">Sl No</th>
                            <th>Emp ID</th>
                            <th>Name</th>
                            <th>Company & Branch</th>
                            <th class="d-none">Company</th>
                            <th class="d-none">Branch</th>
                            <th>Dept & Role</th>
                            <th class="d-none">Department</th>
                            <th class="d-none">Role</th>
                            <th>Joining Date</th>
                            <th>Stage</th>
                            <th>Sys Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
       <!-- Mobile Cards View Wrapper -->
<div id="mobileViewWrapper" class="d-block d-md-none secured-item" data-permission="emp_dir_view">
    <div class="text-center text-muted my-4" id="cardsLoader">
        <i class="fas fa-spinner fa-spin fs-2 mb-2"></i><br>Loading Employees...
    </div>
    
    <!-- कार्ड्स यहाँ अपेंड (Append) होंगे -->
    <div id="mobileCardsContainer"></div>
    
    <!-- Load More Button -->
    <div class="text-center mt-3 mb-5" id="loadMoreContainer" style="display: none;">
        <button id="loadMoreCardsBtn" class="btn text-white px-4 py-2 rounded-pill shadow-sm fw-bold" style="background-color: var(--brand-primary);">
            <i class="fas fa-arrow-down me-2"></i> Load More
        </button>
    </div>
</div>
    </div>

    <div id="mobileBulkActions"
        class="d-md-none position-fixed bottom-0 start-0 w-100 bg-white p-3 shadow-lg border-top d-flex justify-content-between align-items-center secured-item"
        data-permission="emp_dir_delete" style="z-index: 1040;">
        <div class="form-check mb-0">
            <input type="checkbox" class="form-check-input border-secondary" id="mobileSelectAll"
                style="transform: scale(1.2);">
            <label class="form-check-label fw-bold text-dark ms-2" for="mobileSelectAll">Select All</label>
        </div>
        <button class="btn btn-danger btn-sm fw-bold shadow-sm" id="mobileBulkDeleteBtn">
            <i class="fas fa-trash-alt me-1"></i> Delete (<span id="mobileBulkDeleteCount">0</span>)
        </button>
    </div>

    <div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom pb-3 bg-light">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);" id="modalTitle">
                        <i class="fas fa-user-plus me-2 text-primary"></i> Register New Employee
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-3 p-md-4">
                    <form id="empForm" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="_method" id="form_method" value="POST">

                        <div class="card border-0 shadow-sm mb-4 bg-white" id="transferSectionCard">
                            <div class="card-body p-3 border rounded border-primary">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="is_transfer_toggle"
                                        style="cursor: pointer; transform: scale(1.3); margin-top: 5px;">
                                    <label class="form-check-label fw-bold text-primary ms-2" style="font-size: 15px;"
                                        for="is_transfer_toggle">Is this a Transferred Employee?</label>
                                </div>

                                <div id="liveSearchBox" style="display: none;" class="mt-3 position-relative">
                                    <input type="hidden" name="is_transfer" id="is_transfer" value="0">
                                    <input type="hidden" name="transfer_old_id" id="transfer_old_id">

                                    <label class="small fw-bold text-secondary">Search Old Record (ID, Email, Phone,
                                        Aadhar) <i class="fas fa-search text-primary"></i></label>
                                    <input type="text" class="form-control border-primary shadow-sm"
                                        id="transfer_search_input" placeholder="Start typing to search old employee...">

                                    <div class="list-group position-absolute w-100 shadow mt-1"
                                        id="transfer_search_results"
                                        style="z-index: 1050; max-height: 250px; overflow-y: auto; display: none;"></div>
                                </div>
                            </div>
                        </div>

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
                                <div class="row g-3 mb-3 p-3 bg-light border rounded">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-secondary">Generated ID <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control fw-bold text-primary bg-white border-primary"
                                            name="member_id" id="m_member_id" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-secondary">Role Level <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select border-primary auto-fill-field" name="role"
                                            id="m_role" required>
                                            <option value="employee">Employee</option>
                                            <option value="manager">Manager</option>
                                            <option value="director">Director</option>
                                            <option value="ceo">CEO</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-secondary">Employee Grade <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select border-primary auto-fill-field" name="grade"
                                            id="m_grade" required>
                                            <option value="">-- Select Grade --</option>
                                            <option value="Grade A">Grade A</option>
                                            <option value="Grade B">Grade B</option>
                                            <option value="Grade C">Grade C</option>
                                            <option value="Grade D">Grade D</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Full Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="full_name" id="full_name"
                                            class="form-control auto-fill-field" placeholder="Enter Full Name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">S/O, D/O, Spouse's Name</label>
                                        <input type="text" name="father_spouse_name" id="father_spouse_name"
                                            class="form-control auto-fill-field">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Mother's Name</label>
                                        <input type="text" name="mother_name" id="mother_name"
                                            class="form-control auto-fill-field">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Gender</label>
                                        <div class="mt-2">
                                            <div class="form-check form-check-inline"><input
                                                    class="form-check-input auto-fill-field" type="radio"
                                                    name="gender" value="Male" id="g_male"> <label
                                                    class="form-check-label" for="g_male">Male</label></div>
                                            <div class="form-check form-check-inline"><input
                                                    class="form-check-input auto-fill-field" type="radio"
                                                    name="gender" value="Female" id="g_female"> <label
                                                    class="form-check-label" for="g_female">Female</label></div>
                                            <div class="form-check form-check-inline"><input
                                                    class="form-check-input auto-fill-field" type="radio"
                                                    name="gender" value="Others" id="g_others"> <label
                                                    class="form-check-label" for="g_others">Others</label></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Marital Status</label>
                                        <div class="mt-2">
                                            <div class="form-check form-check-inline"><input
                                                    class="form-check-input marital-radio auto-fill-field" type="radio"
                                                    name="marital_status" value="Married" id="m_married"> <label
                                                    class="form-check-label" for="m_married">Married</label></div>
                                            <div class="form-check form-check-inline"><input
                                                    class="form-check-input marital-radio auto-fill-field" type="radio"
                                                    name="marital_status" value="Unmarried" id="m_unmarried"
                                                    checked><label class="form-check-label"
                                                    for="m_unmarried">Unmarried</label></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4" id="doa_container" style="display:none;">
                                        <label class="form-label small fw-bold text-danger">Date of Anniversary</label>
                                        <input type="date" name="anniversary_date" id="anniversary_date"
                                            class="form-control auto-fill-field border-danger">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nationality<span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="nationality" id="nationality"
                                            class="form-control auto-fill-field" value="Indian">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Date of Birth</label>
                                        <input type="date" name="dob" id="dob"
                                            class="form-control auto-fill-field">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Blood Group</label>
                                        <select name="blood_group" id="blood_group" class="form-select auto-fill-field">
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
                                        <label class="form-label small fw-bold text-primary">Date of Joining<span
                                                class="text-danger">*</span></label>
                                        <input type="date" name="doj" id="doj" class="form-control"
                                            required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Contact No <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="contact_no" id="contact_no"
                                            class="form-control auto-fill-field" maxlength="10" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Alternate No</label>
                                        <input type="text" name="alternate_no" id="alternate_no"
                                            class="form-control auto-fill-field" maxlength="10">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Email ID</label>
                                        <input type="email" name="email" id="email"
                                            class="form-control auto-fill-field">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">PAN No</label>
                                        <input type="text" name="pan_no" id="pan_no"
                                            class="form-control auto-fill-field" style="text-transform:uppercase;"
                                            maxlength="10">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Aadhar Card No <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="aadhar_no" id="aadhar_no"
                                            class="form-control auto-fill-field" maxlength="12" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Current Salary (₹)</label>
                                        <input type="number" step="0.01" name="current_salary" id="current_salary"
                                            class="form-control auto-fill-field" placeholder="Enter Salary Amount">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Native Place</label>
                                        <input type="text" name="native_place" id="native_place"
                                            class="form-control auto-fill-field">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold">Communication Address</label>
                                        <textarea name="communication_address" id="communication_address" class="form-control auto-fill-field"
                                            rows="2"></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">City/Town/Village</label>
                                        <input type="text" name="city" id="city"
                                            class="form-control auto-fill-field">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Pin Code</label>
                                        <input type="text" name="pin_code" id="pin_code"
                                            class="form-control auto-fill-field" maxlength="6">
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
                                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                    <h6 class="fw-bold mb-0" style="color: var(--brand-primary);">Bank Details</h6>
                                    <button type="button" class="btn btn-sm btn-success" id="addBankRowBtn">
                                        <i class="fas fa-plus me-1"></i> Add Account
                                    </button>
                                </div>

                                <div id="bankDetailsContainer">
                                    <div class="row g-3 bank-row mb-3 pb-3 border-bottom bg-white rounded">
                                        <div class="col-md-4"><label class="form-label small fw-bold">Account Holder
                                                Name</label><input type="text" name="account_name[]"
                                                class="form-control auto-fill-field bank-acc-name"></div>
                                        <div class="col-md-4"><label class="form-label small fw-bold">Bank A/c
                                                No</label><input type="text" name="account_no[]"
                                                class="form-control auto-fill-field bank-acc-no"></div>
                                        <div class="col-md-4"><label class="form-label small fw-bold">Account Type</label>
                                            <select class="form-select auto-fill-field bank-acc-type"
                                                name="account_type[]">
                                                <option value="">-- Select Type --</option>
                                                <option value="saving">Saving Account</option>
                                                <option value="current">Current Account</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4"><label class="form-label small fw-bold">Bank
                                                Name</label><input type="text" name="bank_name[]"
                                                class="form-control auto-fill-field bank-name"></div>
                                        <div class="col-md-4"><label class="form-label small fw-bold">Branch
                                                Name</label><input type="text" name="bank_branch[]"
                                                class="form-control auto-fill-field bank-branch"></div>
                                        <div class="col-md-3"><label class="form-label small fw-bold">IFSC
                                                Code</label><input type="text" name="ifsc_code[]"
                                                class="form-control auto-fill-field bank-ifsc"
                                                style="text-transform:uppercase;"></div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            <button type="button" class="btn btn-danger remove-bank-btn d-none"><i
                                                    class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="nominee">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Nominee
                                    Details</h6>
                                <div class="row g-3">
                                    <div class="col-md-3"><label class="form-label small fw-bold">Nominee
                                            Name</label><input type="text" name="nominee_name" id="nominee_name"
                                            class="form-control auto-fill-field"></div>
                                    <div class="col-md-3"><label class="form-label small fw-bold">Relation</label><input
                                            type="text" name="nominee_relation" id="nominee_relation"
                                            class="form-control auto-fill-field"></div>
                                    <div class="col-md-3"><label class="form-label small fw-bold">S/o, D/o,
                                            W/o</label><input type="text" name="nominee_so_do_wo"
                                            id="nominee_so_do_wo" class="form-control auto-fill-field"></div>
                                    <div class="col-md-3"><label class="form-label small fw-bold">Date of
                                            Birth</label><input type="date" name="nominee_dob" id="nominee_dob"
                                            class="form-control auto-fill-field"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Mobile No</label><input
                                            type="text" name="nominee_mobile" id="nominee_mobile"
                                            class="form-control auto-fill-field" maxlength="10"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold">Aadhar
                                            Card</label><input type="text" name="nominee_aadhar" id="nominee_aadhar"
                                            class="form-control auto-fill-field" maxlength="12"></div>
                                </div>
                            </div>

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
                                    Status & Stage</h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">System Status <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select border-primary fw-bold" name="emp_status"
                                            id="emp_status">
                                            <option value="active" class="text-success">Active</option>
                                            <option value="inactive" class="text-danger">In-Active</option>
                                            <option value="pending" class="text-warning">Pending</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-primary">Employment Stage</label>
                                        <select class="form-select border-primary" name="employment_stage"
                                            id="employment_stage">
                                            <option value="On Board">On Board</option>
                                            <option value="Probation">Probation</option>
                                            <option value="Confirmed">Confirmed</option>
                                            <option value="Notice Period">Notice Period</option>
                                            <option value="Resigned">Resigned</option>
                                            <option value="Relieved">Relieved</option>
                                            <option value="Terminated">Terminated</option>
                                            <option value="Dismissed">Dismissed</option>
                                            <option value="Retired">Retired</option>
                                            <option value="Deceased">Deceased</option>
                                            <option value="Suspended">Suspended</option>
                                            <option value="Transferred">Transferred</option>
                                            <option value="Rehired">Rehired</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3 leave-fields" style="display:none;">
                                        <label class="form-label small fw-bold text-danger">Date of Leaving (D.O.L)</label>
                                        <input type="date" name="d_o_l" id="d_o_l"
                                            class="form-control border-danger">
                                    </div>
                                    <div class="col-md-3 transferred-fields" style="display:none;">
                                        <label class="form-label small fw-bold text-primary">Transferred To
                                            (Company)</label>
                                        <select class="form-select border-primary" name="transferred_to_company"
                                            id="transferred_to_company">
                                            <option value="">-- Select Company --</option>
                                        </select>
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
        const apiToken = localStorage.getItem('admin_token') || localStorage.getItem('emp_token');
        let currentPortal = window.location.pathname.split('/')[1] || 'admin';
        let currentUserData = null;

        window.hasPerm = function(perm) {
            return window.userGodMode === true || (window.userPerms && window.userPerms.includes(perm));
        };

        $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';
            let lastLoadedPage = -1;

            let ajaxHeaders = {
                'Authorization': 'Bearer ' + apiToken
            };

            // 🔥 FIX: Nested API Call for Permissions & DataTable Race Condition 🔥
            $.ajax({
                url: `/api/v1/${currentPortal}/auth/me`,
                type: 'GET',
                headers: ajaxHeaders,
                success: function(res) {
                    currentUserData = res.data;
                    
                    // User permissions array theek se store ho rahi hai
                    window.userPerms = currentUserData.permissions || [];

                    let developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
                    if (currentUserData && (developerEmails.includes(currentUserData.email) || ['admin', 'superadmin', 'ceo', 'super_admin'].includes(currentUserData.role?.toLowerCase()))) {
                        window.userGodMode = true;
                    }
                    
                    // Table aur roles apply karne ka naya flow
                    if (currentUserData && currentUserData.company_id) {
                        $.ajax({
                            url: `/api/v1/companies/${currentUserData.company_id}`,
                            type: 'GET',
                            headers: ajaxHeaders,
                            success: function(compRes) {
                                currentUserData.company = compRes.data; 
                                applyRoleRestrictions();
                                initDataTable(); // ✅ Backend response aane ke BAAD table load hoga
                            },
                            error: function() {
                                applyRoleRestrictions();
                                initDataTable(); // ✅ Error aaye toh bhi table load ho
                            }
                        });
                    } else {
                        applyRoleRestrictions();
                        initDataTable(); // ✅ Agar user ki company set nahi hai (Master / Dev)
                    }
                }
            });

            function applyRoleRestrictions() {
                if (!currentUserData) return;

                let isDirector = currentUserData?.designation_name?.toLowerCase().includes('director');
                let isGod = window.userGodMode || false;

                let isMaster = currentUserData.company ? (currentUserData.company.parent_id == null) : false;
                let bId = currentUserData.branch_id;
                let isHO = (!bId || bId === 'null' || bId === 'N/A' || bId === 'HO');

                if (isGod || isDirector || (isMaster && isHO)) {
                    $('#globalFilterCard').show();
                    $('#filterCompanyContainer, #filterBranchContainer').show();
                } else if (isMaster && !isHO) {
                    $('#globalFilterCard').hide();
                } else if (!isMaster && isHO) {
                    $('#globalFilterCard').show();
                    $('#filterCompanyContainer').hide();
                    $('#filterBranchContainer').show();
                } else if (!isMaster && !isHO) {
                    $('#globalFilterCard').hide();
                }

              // Table visibility fallback (Check both standard and directory view permissions)
let canViewTable = window.hasPerm('emp_dir_view') || window.hasPerm('employee_view') || window.hasPerm('emp_view');

if (!canViewTable) {
    $('#desktopTableContainer').hide();
} else {
    $('#desktopTableContainer').show();
}
            }

            function fetchNextSmartId(compId) {
                if (!compId || $('#edit_id').val()) return;
                $('#m_member_id').val('Generating...');
                $.ajax({
                    url: '/api/v1/employees/next-id',
                    type: 'POST',
                    data: {
                        company_id: compId
                    },
                    success: function(res) {
                        $('#m_member_id').val(res.next_id);
                    },
                    error: function() {
                        $('#m_member_id').val('');
                    }
                });
            }

            $('#filter_company').select2({
                width: '100%',
                placeholder: 'Search company...',
                allowClear: true,
                minimumInputLength: 3,
                ajax: {
                    url: '/api/v1/companies/search-dynamic',
                    dataType: 'json',
                    delay: 400,
                    headers: ajaxHeaders,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(res) {
                        return {
                            results: $.map(res.data, function(item) {
                                return {
                                    id: item.id,
                                    text: item.company_name + ' (' + item.company_code + ')'
                                }
                            })
                        };
                    }
                }
            });

            $('#filter_branch').select2({
                width: '100%',
                placeholder: 'Search branch...',
                allowClear: true,
                minimumInputLength: 3,
                ajax: {
                    url: '/api/v1/branches/search-dynamic',
                    dataType: 'json',
                    delay: 400,
                    headers: ajaxHeaders,
                    data: function(params) {
                        return {
                            q: params.term,
                            company_id: $('#filter_company').val()
                        };
                    },
                    processResults: function(res) {
                        let branches = $.map(res.data, function(item) {
                            return {
                                id: item.id,
                                text: item.branch_name
                            }
                        });
                        branches.unshift({
                            id: 'HO',
                            text: 'Head Office'
                        });
                        return {
                            results: branches
                        };
                    }
                }
            });

            $('#m_company_id, #transferred_to_company').select2({
                dropdownParent: $('#employeeModal .modal-content'),
                width: '100%',
                placeholder: 'Search company...',
                allowClear: true,
                minimumInputLength: 3,
                ajax: {
                    url: '/api/v1/companies/search-dynamic',
                    dataType: 'json',
                    delay: 400,
                    headers: ajaxHeaders,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(res) {
                        return {
                            results: $.map(res.data, function(item) {
                                return {
                                    id: item.id,
                                    text: item.company_name
                                }
                            })
                        };
                    }
                }
            });

            $('#m_branch_id').select2({
                dropdownParent: $('#employeeModal .modal-content'),
                width: '100%',
                placeholder: 'Search branch...',
                allowClear: true,
                minimumInputLength: 3,
                ajax: {
                    url: '/api/v1/branches/search-dynamic',
                    dataType: 'json',
                    delay: 400,
                    headers: ajaxHeaders,
                    data: function(params) {
                        return {
                            q: params.term,
                            company_id: $('#m_company_id').val()
                        };
                    },
                    processResults: function(res) {
                        let branches = $.map(res.data, function(item) {
                            return {
                                id: item.id,
                                text: item.branch_name
                            }
                        });
                        branches.unshift({
                            id: 'HO',
                            text: 'Head Office (Main)'
                        });
                        return {
                            results: branches
                        };
                    }
                }
            });

            $('#m_department_id').select2({
                dropdownParent: $('#employeeModal .modal-content'),
                width: '100%',
                placeholder: 'Search department...',
                allowClear: true,
                minimumInputLength: 3,
                ajax: {
                    url: '/api/v1/departments/search-dynamic',
                    dataType: 'json',
                    delay: 400,
                    headers: ajaxHeaders,
                    data: function(params) {
                        return {
                            q: params.term,
                            company_id: $('#m_company_id').val(),
                            branch_id: $('#m_branch_id').val(),
                            type: 'employee'
                        };
                    },
                    processResults: function(res) {
                        return {
                            results: $.map(res.data, function(item) {
                                return {
                                    id: item.id,
                                    text: item.department_name
                                }
                            })
                        };
                    }
                }
            });

            $('#designation_input').select2({
                dropdownParent: $('#employeeModal .modal-content'),
                width: '100%',
                placeholder: 'Search designation...',
                allowClear: true,
                minimumInputLength: 3,
                ajax: {
                    url: '/api/v1/designations/search-dynamic',
                    dataType: 'json',
                    delay: 400,
                    headers: ajaxHeaders,
                    data: function(params) {
                        return {
                            q: params.term,
                            department_id: $('#m_department_id').val()
                        };
                    },
                    processResults: function(res) {
                        return {
                            results: $.map(res.data, function(item) {
                                return {
                                    id: item.id,
                                    text: item.designation_name
                                }
                            })
                        };
                    }
                }
            });

            $('#m_company_id').on('change', function() {
                let compId = $(this).val();
                if (!$('#m_branch_id').prop('disabled')) $('#m_branch_id').empty().trigger('change');
                $('#m_department_id').empty().trigger('change');
                fetchNextSmartId(compId);
            });
            $('#m_branch_id').on('change', function() {
                $('#m_department_id').empty().trigger('change');
            });
            $('#m_department_id').on('change', function() {
                $('#designation_input').empty().trigger('change');
            });

            $('#filter_company').change(function() {
                $('#filter_branch').empty().trigger('change');
                if (table) table.ajax.reload();
            });
            $('#filter_branch').change(function() {
                if (table) table.ajax.reload();
            });

            window.openAddModal = function() {
                $('#empForm')[0].reset();
                $('#edit_id').val('');
                $('#form_method').val('POST');
                $('#transferSectionCard').show();
                $('#is_transfer_toggle').prop('checked', false).trigger('change');

                $('#m_company_id, #m_branch_id, #m_department_id, #designation_input').empty().trigger('change');

                let isGod = window.userGodMode || false;
                let isDirector = currentUserData?.designation_name?.toLowerCase().includes('director');
                let hasDirect = isGod || isDirector || window.hasPerm('emp_dir_add_direct') || window.hasPerm('emp_dir_add');

                let isMaster = currentUserData && currentUserData.company ? (currentUserData.company.parent_id === null) : false;
                let bId = currentUserData?.branch_id;
                let isHO = (!bId || bId === 'null' || bId === 'N/A' || bId === 'HO');

                if (isGod || isDirector || (isMaster && isHO)) {
                    $('#m_company_id, #m_branch_id').prop('disabled', false);
                } else if (isMaster && !isHO) {
                    let compOption = new Option(currentUserData.company_name, currentUserData.company_id, true, true);
                    $('#m_company_id').append(compOption).prop('disabled', true).trigger('change');

                    let branchOption = new Option(currentUserData.branch_name, bId, true, true);
                    $('#m_branch_id').append(branchOption).prop('disabled', true).trigger('change');
                } else if (!isMaster && isHO) {
                    let compOption = new Option(currentUserData.company_name, currentUserData.company_id, true, true);
                    $('#m_company_id').append(compOption).prop('disabled', true).trigger('change');

                    $('#m_branch_id').prop('disabled', false);
                } else if (!isMaster && !isHO) {
                    let compOption = new Option(currentUserData.company_name, currentUserData.company_id, true, true);
                    $('#m_company_id').append(compOption).prop('disabled', true).trigger('change');

                    let branchOption = new Option(currentUserData.branch_name, bId, true, true);
                    $('#m_branch_id').append(branchOption).prop('disabled', true).trigger('change');
                }

                if (!hasDirect) {
                    $('#emp_status').html('<option value="pending" selected>Pending (Request)</option>').prop('disabled', true);
                    $('#saveBtn').text('Submit Request');
                } else {
                    $('#emp_status').html(`<option value="active">Active</option><option value="inactive">In-Active</option>`).val('active').prop('disabled', false);
                    $('#saveBtn').text('Save Employee Record');
                }

                $('.nav-pills a:first').tab('show');
                $('#employeeModal').modal('show');
            };

            // ========================================================
            // 🔥 NAYA FUNCTION: TABLE AUR BUTTONS YAHA BANEGA 🔥
            // ========================================================
            function initDataTable() {
                let loggedInEmail = "{{ auth()->user()?->email ?? '' }}";
                let loggedInRole = "{{ strtolower(auth()->user()?->role ?? '') }}";
                
                let isGodFromBlade = loggedInEmail === 'admin@jankivilla.com' || 
                                     loggedInEmail === 'vedprakash@infoera.in' || 
                                     ['admin', 'superadmin', 'ceo', 'super_admin'].includes(loggedInRole);

                let isGodMode = window.userGodMode === true || isGodFromBlade;
                let isDirectorMode = "{{ auth()->check() && (auth()->user()->getTable() === 'directors' || (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('Director'))) ? 'true' : 'false' }}" === 'true';

                // Accurate check kyunki permissions load ho chuki hain
                let canExport = isGodMode || isDirectorMode || (typeof window.hasPerm === 'function' && (window.hasPerm('employee_export') || window.hasPerm('emp_dir_export')));
                if (canExport) {
    $('#mobileCustomExcelBtn').removeClass('d-none'); // 🔥 Agar permission hai toh mobile button dikha do 🔥
}
                let canPrint = isGodMode || isDirectorMode || (typeof window.hasPerm === 'function' && (window.hasPerm('employee_print') || window.hasPerm('emp_dir_print')));

            let tableButtons = [];
            
            if (canExport) {

                $('#mobileCustomExcelBtn').removeClass('d-none');
                tableButtons.push({
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export to Excel',
                    // 🔥 FIX: Added 'd-none d-md-inline-block' to hide on mobile 🔥
                    className: 'btn btn-success btn-sm fw-bold shadow-sm d-none d-md-inline-block',
                    filename: function() {
                        return (currentUserData ? currentUserData.company_name : 'Jankivilla') + ' - Excel Report';
                    },
                    exportOptions: {
                        columns: [2, 3, 5, 6, 8, 9, 10, 11, 12] 
                    },
                    action: function (e, dt, button, config) {
                        var self = this;
                        var oldStart = dt.settings()[0]._iDisplayStart;
                        var oldLength = dt.settings()[0]._iDisplayLength;
                        dt.one('preXhr', function (e, s, data) {
                            data.start = 0;
                            data.length = -1; 
                            dt.one('preDraw', function (e, settings) {
                                $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config);
                                dt.one('preXhr', function (e, s, data) {
                                    settings._iDisplayStart = oldStart;
                                    data.length = oldLength;
                                });
                                setTimeout(dt.ajax.reload, 0);
                                return false;
                            });
                        });
                        dt.ajax.reload();
                    }
                });
            }

            if (canPrint) {
                tableButtons.push({
                    text: '<i class="fas fa-print me-1"></i> Print',
                    // 🔥 FIX: Added 'd-none d-md-inline-block' to hide on mobile 🔥
                    className: 'btn btn-primary btn-sm fw-bold shadow-sm ms-2 d-none d-md-inline-block',
                    action: function (e, dt, node, config) {
                        let comp = $('#filter_company').val() || '';
                        let br = $('#filter_branch').val() || '';
                        let token = localStorage.getItem('admin_token') || localStorage.getItem('emp_token') || '';
                        let timeScope = 'today';
                        
                        window.open(`/employee-print?company_id=${comp}&branch_id=${br}&token=${token}&time_scope=${timeScope}`, '_blank');
                    }
                });
            }
                table = $('#empTable').DataTable({
                    serverSide: true,
                    autoWidth: false,
                    ajax: {
                        url: '/api/v1/employees',
                        type: 'GET',
                        headers: ajaxHeaders,
                        data: function(d) {
                            d.company_id = $('#filter_company').val();
                            d.branch_id = $('#filter_branch').val();
                            d.time_scope = 'all_time';
                        },
                        dataSrc: 'data'
                    },
                    dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                    buttons: tableButtons,
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
                            data: null,
                            className: 'd-none',
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row, meta) {
                                return meta.row + 1;
                            }
                        },
                        {
                            data: 'member_id',
                            render: function(data, type) {
                                if (type === 'export') return data || 'N/A';
                                return `<span class="emp-id-badge">${data || 'N/A'}</span>`;
                            }
                        },
                        {
                            data: 'full_name',
                            render: function(data, type, row) {
                                if (type === 'export') return data;
                                return `<div class="fw-bold text-dark">${data || 'Unknown'}</div>${row.role ? `<small class="text-muted"><i class="fas fa-shield-alt text-warning"></i> ${row.role}</small>` : ''}`;
                            }
                        },
                        {
                            data: null,
                            render: function(data, type, row) {
                                return `<div class="fw-medium">${row.company ? row.company.company_name : 'No Company'}</div><small class="text-muted"><i class="fas fa-code-branch"></i> ${row.branch ? row.branch.branch_name : 'Head Office'}</small>`;
                            }
                        },
                        {
                            data: null,
                            className: 'd-none',
                            render: function(data, type, row) {
                                return row.company ? row.company.company_name : 'Master Head Office';
                            }
                        },
                        {
                            data: null,
                            className: 'd-none',
                            render: function(data, type, row) {
                                return row.branch ? row.branch.branch_name : 'Head Office';
                            }
                        },
                        {
                            data: null,
                            render: function(data, type, row) {
                                return `<div class="fw-medium">${row.department ? row.department.department_name : 'N/A'}</div><small class="text-primary fw-bold">${row.designation ? (typeof row.designation === 'object' ? row.designation.designation_name : row.designation) : 'N/A'}</small>`;
                            }
                        },
                        {
                            data: null,
                            className: 'd-none',
                            render: function(data, type, row) {
                                return row.department ? row.department.department_name : 'N/A';
                            }
                        },
                        {
                            data: null,
                            className: 'd-none',
                            render: function(data, type, row) {
                                return row.designation ? (typeof row.designation === 'object' ? row.designation.designation_name : row.designation) : 'N/A';
                            }
                        },
                        {
                            data: 'doj',
                            render: function(data, type) {
                                if (type === 'export') return data;
                                return data ? `<i class="fas fa-calendar-alt text-secondary me-1"></i> <span class="fw-medium">${data}</span>` : '<span class="text-muted">N/A</span>';
                            }
                        },
                        {
                            data: 'employment_stage',
                            render: function(data, type) {
                                if (type === 'export') return data;
                                return `<span class="badge border border-info text-info"><i class="fas fa-layer-group me-1"></i> ${data || 'On Board'}</span>`;
                            }
                        },
                        {
                            data: 'emp_status',
                            className: 'text-center',
                            render: function(data, type) {
                                let s = (data || 'active').toLowerCase();
                                if (type === 'export') return s.charAt(0).toUpperCase() + s.slice(1);
                                if (s === 'active') return `<span class="status-active">Active</span>`;
                                if (s === 'pending') return `<span class="status-pending">Pending</span>`;
                                if (s === 'transferred') return `<span class="status-transferred">Transferred</span>`;
                                return `<span class="status-inactive">${s.charAt(0).toUpperCase() + s.slice(1)}</span>`;
                            }
                        },
                        {
                            data: 'id',
                            orderable: false,
                            className: 'text-center action-btns',
                            render: function(data, type, row) {
                                let actions = `<button class="btn btn-sm btn-info view-employee text-white shadow-sm" data-id="${row.id}" title="View Details"><i class="fas fa-eye"></i></button>`;
                                if ((row.emp_status || '').toLowerCase() === 'pending') {
                                    if (window.hasPerm('emp_dir_approve')) actions += ` <button class="btn btn-sm btn-success approve-emp-btn shadow-sm" data-id="${row.id}"><i class="fas fa-check-circle"></i></button>`;
                                    if (window.hasPerm('emp_dir_reject')) actions += ` <button class="btn btn-sm btn-danger reject-emp-btn shadow-sm" data-id="${row.id}"><i class="fas fa-times-circle"></i></button>`;
                                }
                                if (window.hasPerm('emp_dir_edit')) actions += ` <button class="btn btn-sm btn-primary edit-employee shadow-sm" data-id="${row.id}"><i class="fas fa-edit"></i></button>`;
                                return actions;
                            }
                        }
                    ],
                    drawCallback: function(settings) {
                        let info = this.api().page.info();
                        let currentPage = info.page;
                        let shouldAppend = false;

                        if (currentPage > 0 && currentPage > lastLoadedPage) {
                            shouldAppend = true;
                        }
                        if (currentPage === 0) {
                            shouldAppend = false;
                        }
                        if (settings.json && settings.json.data && settings.json.data.length > 0) {
                            loadMobileCards(settings.json.data, shouldAppend);
                        } else {
                            loadMobileCards([], false);
                        }
                        
                        lastLoadedPage = currentPage; 
                        
                        $('#selectAll').prop('checked', false);
                        toggleBulkDeleteBtn();
                        if (typeof window.applyPermissions === 'function') window.applyPermissions();
                    }
                });
            } // END initDataTable()

            $('#mobileSearch').on('keyup', function() {
                let value = $(this).val().toLowerCase();
                $('.mobile-emp-item').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });

            function loadMobileCards(data, shouldAppend = false) {
                $('#cardsLoader').hide();
                let html = '';
                
                if (!data || data.length === 0) {
                    if (!shouldAppend) {
                        html = '<div class="text-center text-muted p-3 border rounded bg-light">No employees found.</div>';
                        $('#mobileCardsContainer').html(html);
                    }
                } else {
                    data.forEach(emp => {
                        let statusHtml = '';
                        let stageHtml = `<span class="badge border border-secondary text-secondary ms-2">${emp.employment_stage || 'On Board'}</span>`;
                        let s = (emp.emp_status || 'active').toLowerCase();
                        if (s === 'active') statusHtml = `<span class="status-active">Active</span>`;
                        else if (s === 'pending') statusHtml = `<span class="status-pending">Pending</span>`;
                        else if (s === 'transferred') statusHtml = `<span class="status-transferred">Transferred</span>`;
                        else statusHtml = `<span class="status-inactive">${s}</span>`;

                        let branchName = emp.branch ? emp.branch.branch_name : 'Master HO';
                        let compName = emp.company ? emp.company.company_name : 'No Company';
                        let deptName = emp.department ? emp.department.department_name : 'N/A';
                        let desigName = (typeof emp.designation === 'object' && emp.designation !== null) ? emp.designation.designation_name : (emp.designation || '-');

                        let actionHtml = `<div class="d-flex gap-2 border-top pt-2 mt-2">
                            <button class="btn btn-sm btn-light text-info flex-fill fw-medium view-employee" data-id="${emp.id}"><i class="fas fa-eye me-1"></i> View</button>`;

                        if (s === 'pending') {
                            if (window.hasPerm('emp_dir_approve')) actionHtml += `<button class="btn btn-sm btn-light text-success flex-fill fw-medium approve-emp-btn" data-id="${emp.id}"><i class="fas fa-check-circle me-1"></i> Approve</button>`;
                            if (window.hasPerm('emp_dir_reject')) actionHtml += `<button class="btn btn-sm btn-light text-danger flex-fill fw-medium reject-emp-btn" data-id="${emp.id}"><i class="fas fa-times-circle me-1"></i> Reject</button>`;
                        }
                        if (window.hasPerm('emp_dir_edit')) {
                            actionHtml += `<button class="btn btn-sm btn-light text-primary flex-fill fw-medium edit-employee" data-id="${emp.id}"><i class="fas fa-edit me-1"></i> Edit</button>`;
                        }
                        actionHtml += `</div>`;

                        let checkboxHtml = '';
                        if (window.hasPerm('emp_dir_delete')) {
                            checkboxHtml = `<input type="checkbox" class="form-check-input border-secondary mobile-row-checkbox mt-1 me-2" value="${emp.id}" style="transform: scale(1.3);">`;
                        }

                        html += `
                        <div class="emp-card mobile-emp-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-start">
                                    ${checkboxHtml}
                                    <div><h6 class="fw-bold mb-0">${emp.full_name}</h6><span class="emp-id-badge">${emp.member_id}</span></div>
                                </div>
                                ${statusHtml} ${stageHtml}
                            </div>
                            <div class="small text-secondary mb-3">
                                <div class="mt-1"><i class="fas fa-briefcase me-1 text-muted"></i> ${deptName} - ${desigName}</div>
                                <div class="mt-1"><i class="fas fa-phone-alt me-1 text-muted"></i> ${emp.contact_no || 'N/A'}</div>
                                <div class="mt-1"><i class="fas fa-calendar-check me-1 text-muted"></i> DOJ: <span class="text-dark fw-bold">${emp.doj || 'N/A'}</span></div>
                            </div>
                            ${actionHtml}
                        </div>`;
                    });

                    if (shouldAppend) {
                        $('#mobileCardsContainer').append(html);
                    } else {
                        $('#mobileCardsContainer').html(html);
                    }
                }

                if (table && table.page.info()) {
                    let info = table.page.info();
                    if (info.page < info.pages - 1) {
                        $('#loadMoreContainer').show();
                    } else {
                        $('#loadMoreContainer').hide();
                    }
                }

                $('#mobileSelectAll').prop('checked', false);
                toggleMobileBulkDeleteBtn();
            }

            $(document).on('click', '.edit-employee', function() {
                let id = $(this).data('id');
                let btn = $(this);
                let originalHtml = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

                $.ajax({
                    url: `/api/v1/employees/${id}`,
                    success: function(res) {
                        let emp = res.data;
                        $('#edit_id').val(emp.id);
                        $('#form_method').val('PUT');
                        $('#transferSectionCard').hide();

                        Object.keys(emp).forEach(key => {
                            let input = $(`#empForm [name="${key}"]`);
                            if (input.length > 0 && input.attr('type') !== 'file' &&
                                input.attr('type') !== 'radio') {
                                input.val(emp[key]);
                            }
                        });
                        $('#m_role').val(emp.role || 'employee');
                        if (emp.gender) $(`input[name="gender"][value="${emp.gender}"]`).prop('checked', true);
                        if (emp.marital_status) $(`input[name="marital_status"][value="${emp.marital_status}"]`).prop('checked', true).trigger('change');
                        $('#emp_status').val(emp.emp_status).prop('disabled', false).trigger('change');

                        let bdArray = emp.bank_details || emp.bankDetails;
                        populateBankDetails(bdArray);

                        $('#m_company_id, #m_branch_id, #m_department_id, #designation_input').empty();

                        if (emp.company) {
                            $('#m_company_id').append(new Option(emp.company.company_name, emp.company_id, true, true));
                        }

                        if (emp.branch) {
                            $('#m_branch_id').append(new Option(emp.branch.branch_name, emp.branch_id, true, true));
                        } else if (emp.branch_id === null || emp.branch_id === 'HO') {
                            $('#m_branch_id').append(new Option('Head Office (Main)', 'HO', true, true));
                        }

                        if (emp.department) {
                            $('#m_department_id').append(new Option(emp.department.department_name, emp.department_id, true, true));
                        }

                        if (emp.designation) {
                            let dName = typeof emp.designation === 'object' ? emp.designation.designation_name : emp.designation;
                            $('#designation_input').append(new Option(dName, emp.designation_id, true, true));
                        }

                        let isGod = window.userGodMode || false;
                        let isDirector = currentUserData?.designation_name?.toLowerCase().includes('director');
                        let isMaster = currentUserData && currentUserData.company ? (currentUserData.company.parent_id === null) : false;
                        let bId = currentUserData?.branch_id;
                        let isHO = (!bId || bId === 'null' || bId === 'N/A' || bId === 'HO');

                        if (isGod || isDirector || (isMaster && isHO)) {
                            $('#m_company_id, #m_branch_id').prop('disabled', false);
                        } else if (isMaster && !isHO) {
                            $('#m_company_id, #m_branch_id').prop('disabled', true);
                        } else if (!isMaster && isHO) {
                            $('#m_company_id').prop('disabled', true);
                            $('#m_branch_id').prop('disabled', false);
                        } else if (!isMaster && !isHO) {
                            $('#m_company_id, #m_branch_id').prop('disabled', true);
                        }

                        $('.file-preview-wrapper').hide().find('.preview-content').empty();
                        let fileFields = ['passport_photo', 'signature_photo', 'aadhar_pdf',
                            'pan_pdf', 'bank_passbook_pdf', 'driving_license_pdf',
                            'tenth_pdf', 'twelfth_pdf', 'graduation_pdf', 'pg_pdf',
                            'other_pdf', 'nom_passport_photo', 'nom_aadhar_pdf',
                            'nom_pan_pdf', 'nom_bank_passbook_pdf',
                            'nom_driving_license_pdf', 'nom_passport_pdf', 'nom_tenth_pdf',
                            'nom_twelfth_pdf', 'nom_graduation_pdf', 'nom_pg_pdf',
                            'nom_other_pdf'
                        ];
                        fileFields.forEach(function(field) {
                            let filePath = emp[field];
                            let input = $(`#empForm input[name="${field}"]`);
                            if (input.length > 0 && filePath) {
                                let wrapper = input.next('.file-preview-wrapper');
                                let content = wrapper.find('.preview-content');
                                let fullUrl = filePath.startsWith('/') ? filePath : '/' + filePath;
                                let ext = filePath.split('.').pop().toLowerCase();
                                let imageExts = ['jpg', 'jpeg', 'png', 'webp', 'bmp'];

                                if (imageExts.includes(ext)) {
                                    content.html(`<img src="${fullUrl}" style="max-height:90px; border-radius:6px; object-fit:contain;">`);
                                } else {
                                    content.html(`<div class="d-flex align-items-center gap-2 fw-bold text-dark px-2"><i class="fas fa-file-pdf text-danger fs-3"></i><a href="${fullUrl}" target="_blank" class="text-decoration-none" style="font-size:12px;">View Doc</a></div>`);
                                }
                                wrapper.show();
                            }
                        });

                        $('.nav-pills a:first').tab('show');
                        $('#employeeModal').modal('show');
                    },
                    complete: function() {
                        btn.html(originalHtml).prop('disabled', false);
                    }
                });
            });

            $(document).on('click', '.view-employee', function() {
                let id = $(this).data('id');
                let btn = $(this);
                let originalHtml = btn.html();

                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

                $.ajax({
                    url: `/api/v1/employees/${id}`,
                    type: 'GET',
                    success: function(res) {
                        let d = res.data;

                        let branchText = d.branch ?
                            `<span class="text-dark fw-bold">${d.branch.company ? d.branch.company.company_name : 'No Company'}</span><br><i class="fas fa-map-marker-alt text-danger me-1"></i> ${d.branch.branch_name}` :
                            'Master Head Office';

                        let desigName = (typeof d.designation === 'object' && d.designation !== null) ? d.designation.designation_name : (d.designation || 'N/A');
                        let deptName = (typeof d.department === 'object' && d.department !== null) ? d.department.department_name : 'N/A';
                        let statusColor = d.emp_status === 'active' ? 'success' : (d.emp_status === 'pending' ? 'warning' : (d.emp_status === 'transferred' ? 'info' : 'danger'));

                        let imgUrl = d.passport_photo ? (d.passport_photo.startsWith('/') ? d.passport_photo : '/' + d.passport_photo) :
                            `https://ui-avatars.com/api/?name=${encodeURIComponent(d.full_name || 'User')}&background=1A365D&color=fff&size=128`;

                        let bdArray = d.bank_details || d.bankDetails || [];
                        let bd = bdArray.length > 0 ? bdArray[0] : null;
                        let bankNameText = bd && bd.bank_name ? bd.bank_name : 'N/A';
                        let accNoText = bd && bd.account_no ? bd.account_no : 'N/A';
                        let ifscText = bd && bd.ifsc_code ? bd.ifsc_code : 'N/A';

                        let docsHtml = '';
                        let docList = [{ key: 'passport_photo', label: 'Passport Photo' }, { key: 'signature_photo', label: 'Signature' },
                            { key: 'aadhar_pdf', label: 'Aadhar Card' }, { key: 'pan_pdf', label: 'PAN Card' },
                            { key: 'bank_passbook_pdf', label: 'Bank Proof' }, { key: 'tenth_pdf', label: '10th Marksheet' },
                            { key: 'twelfth_pdf', label: '12th Marksheet' }, { key: 'graduation_pdf', label: 'Graduation Cert' },
                            { key: 'pg_pdf', label: 'PG Cert' }, { key: 'driving_license_pdf', label: 'Driving License' },
                            { key: 'other_pdf', label: 'Other Doc' }
                        ];
                        docList.forEach(doc => {
                            if (d[doc.key]) {
                                docsHtml += `<a href="/${d[doc.key]}" target="_blank" class="btn btn-outline-primary btn-sm mb-2 me-2"><i class="fas fa-file-alt me-1"></i> ${doc.label}</a>`;
                            }
                        });
                        if (docsHtml === '') docsHtml = '<p class="text-muted fst-italic">No documents uploaded.</p>';

                        let historyHtml = '';
                        if (d.service_history_data && d.service_history_data.length > 0) {
                            let sortedHistory = d.service_history_data;

                            sortedHistory.forEach((record, index) => {
                                let badgeColor = record.status === 'active' ? 'success' : (record.status === 'transferred' ? 'info' : (record.status === 'pending' ? 'warning' : 'danger'));

                                let fromDate = 'N/A';
                                if (record.promotion_date) {
                                    fromDate = record.promotion_date.split('T')[0].split(' ')[0];
                                } else if (record.created_at) {
                                    fromDate = record.created_at.split('T')[0].split(' ')[0];
                                }

                                let toDate = 'Present';

                                if (record.date_of_leaving) {
                                    toDate = record.date_of_leaving;
                                } else if (index > 0) {
                                    let newerRecord = sortedHistory[index - 1];
                                    if (newerRecord.promotion_date) {
                                        toDate = newerRecord.promotion_date.split('T')[0].split(' ')[0];
                                    } else if (newerRecord.created_at) {
                                        toDate = newerRecord.created_at.split('T')[0].split(' ')[0];
                                    }
                                }

                                let joinedDate = record.joining_date || 'N/A';
                                let leavingDate = record.date_of_leaving || '<span class="text-success">Present</span>';

                                let srvDesigName = 'N/A';
                                if (record.designation && typeof record.designation === 'object') {
                                    srvDesigName = record.designation.designation_name;
                                } else if (record.role) {
                                    srvDesigName = record.role.toUpperCase();
                                }

                                historyHtml += `
                                    <div class="p-3 border-bottom mb-2 bg-white rounded shadow-sm border-start border-4 border-${badgeColor}">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold text-dark">
                                                <i class="fas fa-id-badge text-primary me-1"></i> ${record.member_id} 
                                                <span class="text-primary fw-bold ms-1">(${srvDesigName})</span>
                                            </span>
                                            <span class="badge bg-${badgeColor} text-uppercase">${record.status}</span>
                                        </div>
                                        <div class="small text-muted mt-3">
                                            <div class="d-flex justify-content-between mb-1 border-bottom pb-1">
                                                <span><i class="fas fa-calendar-check me-1 text-primary"></i> Desig. FROM: <span class="text-dark fw-bold">${fromDate}</span> <b class="mx-2 text-danger">TO:</b> <span class="text-dark fw-bold">${toDate}</span></span>
                                                <span class="fw-bold text-secondary" title="Service Tracking ID"><i class="fas fa-link me-1"></i> ${record.service_id || '-'}</span>
                                            </div>
                                            <div class="d-flex justify-content-between pt-1">
                                                <span><i class="fas fa-calendar-alt me-1"></i> Joined: <span class="text-dark fw-bold">${joinedDate}</span> <b class="mx-2 text-danger">TO:</b> <span class="text-dark fw-bold">${leavingDate}</span></span>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                        } else {
                            historyHtml = '<div class="alert alert-light text-center small">No service history available.</div>';
                        }
                        let html = `
                            <div class="card border-0 shadow-none bg-transparent">
                                <div class="d-flex flex-column flex-md-row align-items-center bg-white p-4 border-bottom rounded-top">
                                    <div class="position-relative mb-3 mb-md-0 me-md-4">
                                        <img src="${imgUrl}" alt="Profile" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover; border: 4px solid #f1f5f9; box-shadow: 0 4px 10px rgba(0,0,0,0.08);">
                                    </div>
                                    <div class="text-center text-md-start flex-grow-1">
                                        <h4 class="fw-bold text-dark mb-1">${d.full_name || '-'}</h4>
                                        <h6 class="text-secondary mb-2"><i class="fas fa-briefcase me-1 text-muted"></i> ${desigName} <span class="text-muted fw-normal small">| ${deptName}</span></h6>
                                        <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start mt-2">
                                            <span class="badge bg-dark"><i class="fas fa-id-badge me-1"></i> ${d.member_id || '-'}</span>
                                            <span class="badge bg-warning text-dark"><i class="fas fa-link me-1"></i> S-ID: ${d.service_id || 'N/A'}</span>
                                            <span class="badge bg-${statusColor} text-uppercase"><i class="fas fa-circle me-1" style="font-size:8px; vertical-align:middle;"></i> ${d.emp_status}</span>
                                            <span class="badge border border-secondary text-secondary"><i class="fas fa-user-shield me-1"></i> ${d.role || 'Employee'}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 bg-light">
                                    <ul class="nav nav-tabs border-primary border-opacity-25 mb-4 flex-nowrap overflow-auto" id="viewEmpTabs" role="tablist">
                                        <li class="nav-item"><button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#v-tab-official"><i class="fas fa-briefcase me-1"></i> Official</button></li>
                                        <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#v-tab-personal"><i class="fas fa-user me-1"></i> Personal</button></li>
                                        <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#v-tab-bank"><i class="fas fa-university me-1"></i> Bank & KYC</button></li>
                                        <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#v-tab-docs"><i class="fas fa-file-alt me-1"></i> Documents</button></li>
                                        <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#v-tab-service"><i class="fas fa-history me-1"></i> Service Record</button></li>
                                    </ul>

                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="v-tab-official">
                                            <div class="row g-4">
                                                <div class="col-sm-6"><label class="text-muted small mb-1">Company</label><h6 class="fw-bold">${d.company ? d.company.company_name : 'N/A'}</h6></div>
                                                <div class="col-sm-6"><label class="text-muted small mb-1">Branch</label><h6 class="fw-bold">${d.branch ? d.branch.branch_name : 'Head Office'}</h6></div>
                                                <div class="col-sm-6"><label class="text-muted small mb-1">Department</label><h6 class="fw-bold">${d.department ? d.department.department_name : 'N/A'}</h6></div>
                                                <div class="col-sm-6"><label class="text-muted small mb-1">Date of Joining</label><h6 class="fw-bold">${d.doj || 'N/A'}</h6></div>
                                                <div class="col-sm-6"><label class="text-muted small mb-1">System Role</label><h6 class="fw-bold text-success">${d.role || 'employee'}</h6></div>
                                                <div class="col-sm-6"><label class="text-muted small mb-1">Portal Login Allowed</label><h6 class="fw-bold text-primary">${d.daily_start_time || '24'} to ${d.daily_end_time || 'Hrs'}</h6></div>
                                            </div>
                                        </div>

                                        <div class="tab-pane fade" id="v-tab-personal">
                                            <div class="row g-4">
                                                <div class="col-sm-6"><label class="text-muted small mb-1">Father/Spouse</label><h6 class="fw-bold">${d.father_spouse_name || 'N/A'}</h6></div>
                                                <div class="col-sm-6"><label class="text-muted small mb-1">Mother Name</label><h6 class="fw-bold">${d.mother_name || 'N/A'}</h6></div>
                                                <div class="col-sm-4"><label class="text-muted small mb-1">DOB</label><h6 class="fw-bold">${d.dob || 'N/A'}</h6></div>
                                                <div class="col-sm-4"><label class="text-muted small mb-1">Gender</label><h6 class="fw-bold">${d.gender || 'N/A'}</h6></div>
                                                <div class="col-sm-4"><label class="text-muted small mb-1">Blood Group</label><h6 class="fw-bold">${d.blood_group || 'N/A'}</h6></div>
                                                <div class="col-12"><label class="text-muted small mb-1">Current Address</label><h6 class="fw-bold">${d.communication_address || 'N/A'} - ${d.pin_code || ''}</h6></div>
                                                <div class="col-sm-6"><label class="text-muted small mb-1">Contact Number</label><h6 class="fw-bold">${d.contact_no || 'N/A'}</h6></div>
                                                <div class="col-sm-6"><label class="text-muted small mb-1">Email</label><h6 class="fw-bold">${d.email || 'N/A'}</h6></div>
                                            </div>
                                        </div>

                                        <div class="tab-pane fade" id="v-tab-bank">
                                            <div class="row g-4">
                                                <div class="col-sm-6"><label class="text-muted small mb-1">Aadhar No</label><h6 class="fw-bold">${d.aadhar_no || 'N/A'}</h6></div>
                                                <div class="col-sm-6"><label class="text-muted small mb-1">PAN No</label><h6 class="fw-bold text-uppercase">${d.pan_no || 'N/A'}</h6></div>
                                                <div class="col-sm-6"><label class="text-muted small mb-1">Bank Name</label><h6 class="fw-bold">${bankNameText}</h6></div>
                                                <div class="col-sm-6"><label class="text-muted small mb-1">Account No</label><h6 class="fw-bold">${accNoText}</h6></div>
                                                <div class="col-sm-6"><label class="text-muted small mb-1">IFSC Code</label><h6 class="fw-bold text-uppercase">${ifscText}</h6></div>
                                            </div>
                                        </div>

                                        <div class="tab-pane fade" id="v-tab-docs">
                                            <div class="d-flex flex-wrap gap-2">
                                                ${docsHtml}
                                            </div>
                                        </div>
                                        
                                        <div class="tab-pane fade" id="v-tab-service">
                                            <div class="timeline-container pe-2" style="max-height: 350px; overflow-y: auto;">
                                                ${historyHtml}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        $('#viewDetailsBody').html(html);
                        $('#viewModal').modal('show');
                    },
                    complete: function() {
                        btn.html(originalHtml).prop('disabled', false);
                    }
                });
            });

            $('#is_transfer_toggle').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#liveSearchBox').slideDown();
                    $('#is_transfer').val('1');
                    $('.auto-fill-field').val('').prop('readonly', true);
                    $('#m_member_id').val('');
                } else {
                    $('#liveSearchBox').slideUp();
                    $('#is_transfer').val('0');
                    $('#transfer_old_id').val('');
                    $('.auto-fill-field').val('').prop('readonly', false);
                    if ($('#m_company_id').val()) $('#m_company_id').trigger('change');
                }
            });

            let searchTimeout;
            $('#transfer_search_input').on('keyup', function() {
                clearTimeout(searchTimeout);
                let keyword = $(this).val();
                let resultsBox = $('#transfer_search_results');
                let targetCompanyId = $('#m_company_id').val();

                if (!targetCompanyId) {
                    resultsBox.html(
                        '<div class="list-group-item text-danger small"><i class="fas fa-exclamation-triangle"></i> Please type 3 letters in "Select Company" dropdown above and select the Target Company first!</div>'
                    ).show();
                    return;
                }

                if (keyword.length < 3) {
                    resultsBox.hide();
                    return;
                }

                searchTimeout = setTimeout(() => {
                    $.ajax({
                        url: '/api/v1/employees/search-transfer',
                        type: 'POST',
                        data: {
                            keyword: keyword,
                            target_company_id: targetCompanyId
                        },
                        success: function(res) {
                            resultsBox.empty().show();
                            if (res.data.length === 0) {
                                resultsBox.html(
                                    '<div class="list-group-item text-danger small">No employee transferred to this company found.</div>'
                                );
                                return;
                            }
                            res.data.forEach(emp => {
                                let compName = emp.branch && emp.branch.company ? emp.branch.company.company_name : 'Master HO';
                                let itemHtml = `
                                <a href="#" class="list-group-item list-group-item-action search-select-item" data-full='${JSON.stringify(emp)}'>
                                    <div class="fw-bold text-primary">${emp.full_name} <span class="badge bg-secondary float-end">${emp.member_id}</span></div>
                                    <small class="text-muted"><i class="fas fa-sign-out-alt text-danger"></i> From: ${compName} | <i class="fas fa-phone"></i> ${emp.contact_no}</small>
                                </a>`;
                                resultsBox.append(itemHtml);
                            });
                        }
                    });
                }, 400);
            });

            $(document).on('click', '.search-select-item', function(e) {
                e.preventDefault();
                let emp = JSON.parse($(this).attr('data-full'));

                $('#transfer_old_id').val(emp.id);
                $('#transfer_search_input').val(emp.full_name + ' (' + emp.member_id + ')');
                $('#transfer_search_results').hide();
                $('.auto-fill-field').prop('readonly', false);

                Object.keys(emp).forEach(key => {
                    let input = $(`#empForm [name="${key}"]`);
                    if (input.length && input.attr('type') !== 'file' && input.attr('type') !==
                        'radio' && !['company_id', 'branch_id', 'department_id', 'designation_id',
                            'doj', 'emp_status', 'd_o_l', 'member_id', 'role', 'service_id'
                        ].includes(key)) {
                        input.val(emp[key]);
                    }
                });

                let bdArray = emp.bank_details || emp.bankDetails || [];
                populateBankDetails(bdArray);

                $('.file-preview-wrapper').hide().find('.preview-content').empty();
                let fileFields = ['passport_photo', 'signature_photo', 'aadhar_pdf', 'pan_pdf',
                    'bank_passbook_pdf', 'driving_license_pdf', 'tenth_pdf', 'twelfth_pdf',
                    'graduation_pdf', 'pg_pdf', 'other_pdf', 'nom_passport_photo', 'nom_aadhar_pdf',
                    'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_driving_license_pdf',
                    'nom_passport_pdf', 'nom_tenth_pdf', 'nom_twelfth_pdf', 'nom_graduation_pdf',
                    'nom_pg_pdf', 'nom_other_pdf'
                ];

                fileFields.forEach(function(field) {
                    let filePath = emp[field];
                    let input = $(`#empForm input[name="${field}"]`);
                    if (input.length > 0 && filePath) {
                        let wrapper = input.next('.file-preview-wrapper');
                        let content = wrapper.find('.preview-content');
                        let fullUrl = filePath.startsWith('/') ? filePath : '/' + filePath;
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

                if (emp.gender) $(`input[name="gender"][value="${emp.gender}"]`).prop('checked', true);
                if (emp.marital_status) $(`input[name="marital_status"][value="${emp.marital_status}"]`)
                    .prop('checked', true).trigger('change');

                if ($('#m_company_id').val()) $('#m_company_id').trigger('change');
            });

            $('#empForm').on('submit', function(e) {
                e.preventDefault();
                $('#m_company_id, #m_branch_id, #emp_status').prop('disabled', false);

                if ($('#m_branch_id').val() === 'HO') $('#m_branch_id').val('');

                let id = $('#edit_id').val();
                let formData = new FormData(this);
                let btn = $('#saveBtn');
                let originalText = btn.text();
                btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);

                $.ajax({
                    url: id ? `/api/v1/employees/${id}` : `/api/v1/employees`,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(res) {
                        $('#employeeModal').modal('hide');
                        Swal.fire('Success', res.message, 'success');
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON ? xhr.responseJSON.message :
                            'Failed', 'error');
                    },
                    complete: function() {
                        btn.text(originalText).prop('disabled', false);
                    }
                });
            });

            $('#employment_stage').off('change').on('change', function() {
                let val = $(this).val();
                let leavingStages = ['Notice Period', 'Resigned', 'Relieved', 'Terminated', 'Dismissed',
                    'Absconded', 'Retired', 'Deceased', 'Transferred'
                ];
                if (leavingStages.includes(val)) {
                    $('.leave-fields').slideDown();
                    $('#d_o_l').attr('required', true);
                    if (val === 'Transferred') {
                        $('.transferred-fields').slideDown();
                        $('#transferred_to_company').attr('required', true);
                    } else {
                        $('.transferred-fields').slideUp();
                        $('#transferred_to_company').val('').removeAttr('required');
                    }
                } else {
                    $('.leave-fields, .transferred-fields').slideUp();
                    $('#d_o_l, #transferred_to_company').val('').removeAttr('required');
                }
            });

            $(document).on('click', '.approve-emp-btn', function() {
                let empId = $(this).data('id');
                Swal.fire({
                    title: 'Approve Employee?',
                    text: "This will mark the employee as 'Active'.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Approve!'
                }).then((result) => {
                    if (result.isConfirmed) updateEmployeeStatus(empId, 'active');
                });
            });

            $(document).on('click', '.reject-emp-btn', function() {
                let empId = $(this).data('id');
                Swal.fire({
                    title: 'Reject Employee?',
                    text: "This will mark the employee as 'Inactive'.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Reject!'
                }).then((result) => {
                    if (result.isConfirmed) updateEmployeeStatus(empId, 'inactive');
                });
            });

            function updateEmployeeStatus(id, newStatus) {
                $.ajax({
                    url: `/api/v1/employees/${id}/status`,
                    type: 'POST',
                    data: {
                        status: newStatus,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Updated!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        table.ajax.reload(null, false);
                    },
                    error: function(err) {
                        let errorMsg = err.responseJSON && err.responseJSON.message ? err.responseJSON
                            .message : 'System Error Occurred!';
                        Swal.fire('Error', errorMsg, 'error');
                    }
                });
            }

            function toggleBulkDeleteBtn() {
                if ($('.row-checkbox:checked').length > 0) $('#bulkDeleteBtn').removeClass('d-none');
                else $('#bulkDeleteBtn').addClass('d-none');
            }

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

            function executeBulkDelete(selectedIds, isMobile = false) {
                if (selectedIds.length > 0) {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: `Permanently Delete ${selectedIds.length} employee(s)?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            let btn = isMobile ? $('#mobileBulkDeleteBtn') : $('#bulkDeleteBtn');
                            let originalText = btn.html();
                            btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Deleting...').prop(
                                'disabled', true);

                            $.ajax({
                                url: '/api/v1/employees/bulk-delete-permanent',
                                type: 'POST',
                                data: {
                                    ids: selectedIds
                                },
                                success: function(res) {
                                    Swal.fire('Deleted!', res.message, 'success');
                                    table.ajax.reload(null, false);
                                    if (isMobile) {
                                        $('#mobileSelectAll').prop('checked', false);
                                        $('#mobileBulkActions').removeClass('show-bar');
                                    }
                                },
                                error: function(err) {
                                    Swal.fire('Error', err.responseJSON.message || 'Failed',
                                        'error');
                                },
                                complete: function() {
                                    btn.html(originalText).prop('disabled', false);
                                    if (!isMobile) {
                                        $('#selectAll').prop('checked', false);
                                        toggleBulkDeleteBtn();
                                    }
                                }
                            });
                        }
                    });
                }
            }

            $('#bulkDeleteBtn').on('click', function() {
                let selectedIds = [];
                $('.row-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });
                executeBulkDelete(selectedIds, false);
            });

            function toggleMobileBulkDeleteBtn() {
                let selectedCount = $('.mobile-row-checkbox:checked').length;
                if (selectedCount > 0) {
                    $('#mobileBulkActions').addClass('show-bar');
                    $('#mobileBulkDeleteCount').text(selectedCount);
                } else {
                    $('#mobileBulkActions').removeClass('show-bar');
                }
            }
            $(document).on('change', '.mobile-row-checkbox', function() {
                let allCount = $('.mobile-row-checkbox').length;
                let checkedCount = $('.mobile-row-checkbox:checked').length;
                $('#mobileSelectAll').prop('checked', allCount > 0 && allCount === checkedCount);
                toggleMobileBulkDeleteBtn();
            });
            $('#mobileSelectAll').on('change', function() {
                $('.mobile-row-checkbox').prop('checked', this.checked);
                toggleMobileBulkDeleteBtn();
            });
            $('#mobileBulkDeleteBtn').on('click', function() {
                let selectedIds = [];
                $('.mobile-row-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });
                executeBulkDelete(selectedIds, true);
            });

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

            $(document).on('click change', 'input[name="marital_status"]', function() {
                if ($('#m_married').is(':checked')) {
                    $('#doa_container').slideDown(200);
                } else {
                    $('#doa_container').slideUp(200);
                    $('#anniversary_date').val('');
                }
            });

            $('#addBankRowBtn').on('click', function() {
                let newRow = $('.bank-row:first').clone();
                newRow.find('input').val('');
                newRow.find('select').val('');
                newRow.find('.remove-bank-btn').removeClass('d-none');
                $('#bankDetailsContainer').append(newRow);
            });
            $(document).on('click', '.remove-bank-btn', function() {
                $(this).closest('.bank-row').remove();
            });

            function populateBankDetails(bankArray) {
                $('#bankDetailsContainer').empty();
                if (!bankArray || bankArray.length === 0) {
                    $('#addBankRowBtn').trigger('click');
                    $('.bank-row:first .remove-bank-btn').addClass('d-none');
                    return;
                }
                bankArray.forEach((bd, index) => {
                    let rowHtml = `
                    <div class="row g-3 bank-row mb-3 pb-3 border-bottom bg-white rounded">
                        <div class="col-md-4"><label class="form-label small fw-bold">Account Holder Name</label><input type="text" name="account_name[]" class="form-control auto-fill-field" value="${bd.account_name || ''}"></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Bank A/c No</label><input type="text" name="account_no[]" class="form-control auto-fill-field" value="${bd.account_no || ''}"></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Account Type</label>
                            <select class="form-select auto-fill-field" name="account_type[]">
                                <option value="">-- Select Type --</option>
                                <option value="saving" ${bd.account_type && bd.account_type.toLowerCase() === 'saving' ? 'selected' : ''}>Saving Account</option>
                                <option value="current" ${bd.account_type && bd.account_type.toLowerCase() === 'current' ? 'selected' : ''}>Current Account</option>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Bank Name</label><input type="text" name="bank_name[]" class="form-control auto-fill-field" value="${bd.bank_name || ''}"></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Branch Name</label><input type="text" name="bank_branch[]" class="form-control auto-fill-field" value="${bd.branch || ''}"></div>
                        <div class="col-md-3"><label class="form-label small fw-bold">IFSC Code</label><input type="text" name="ifsc_code[]" class="form-control auto-fill-field" value="${bd.ifsc_code || ''}" style="text-transform:uppercase;"></div>
                        <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-danger remove-bank-btn ${index === 0 && bankArray.length === 1 ? 'd-none' : ''}"><i class="fas fa-trash"></i></button></div>
                    </div>`;
                    $('#bankDetailsContainer').append(rowHtml);
                });
            }
        });

        // Load More Button Click Event
        $(document).on('click', '#loadMoreCardsBtn', function() {
            let btn = $(this);
            let originalHtml = btn.html();
            
            btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Loading...').prop('disabled', true);
            
            // बस अगला पेज कॉल करें, बाकी काम drawCallback अपने आप कर लेगा
            table.page('next').draw('page');
            
            setTimeout(() => {
                btn.html(originalHtml).prop('disabled', false);
            }, 800);
        });
    </script>
@endpush
