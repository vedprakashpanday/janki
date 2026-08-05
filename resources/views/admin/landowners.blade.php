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
            <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Landowner Details</h4>
            <button type="button" class="btn text-white px-3 py-2 shadow-sm secured-item" data-permission="landowner_add"
                style="background-color: var(--brand-primary);" onclick="openModal('add')">
                <i class="fas fa-plus me-1"></i> Add Landowner
            </button>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search Landowner...">
            <button type="button" class="btn text-white shadow-sm" style="background-color: #10b981;"
                id="mobileExcelBtn"><i class="fas fa-file-excel"></i></button>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="landownerTable" class="table table-hover table-custom w-100">
                        <thead>
                            <tr>
                                <th>Owner ID</th>
                                <th>Land ID</th>
                                <th>Name</th>
                                <th>Agent ID</th>
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
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i
                            class="fas fa-eye me-2 text-info"></i> Landowner Overview</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold text-primary mb-3">System IDs</h6>
                                <p class="mb-1"><strong>Owner ID:</strong> <span id="v_lo_id" class="text-dark"></span>
                                </p>
                                <p class="mb-0"><strong>Land ID:</strong> <span id="v_land_id"
                                        class="text-danger fw-bold"></span></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light h-100">
                                <h6 class="fw-bold text-primary mb-3">Agent & Branch Info</h6>
                                <p class="mb-1"><strong>Branch:</strong> <span id="v_branch" class="text-dark"></span>
                                </p>
                                <p class="mb-0"><strong>Agent:</strong> <span id="v_agent" class="text-dark"></span></p>
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <h6 class="fw-bold text-secondary border-bottom pb-2">Personal Information</h6>
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
                            <p class="small text-muted mb-0">Aadhar/PAN</p>
                            <h6 class="fw-bold" id="v_docs"></h6>
                        </div>

                        <div class="col-12 mt-3">
                            <h6 class="fw-bold text-secondary border-bottom pb-2">Land Specifications</h6>
                        </div>
                        <div class="col-md-6">
                            <p class="small text-muted mb-0">Khesra (Old/New)</p>
                            <h6 class="fw-bold" id="v_khesra"></h6>
                        </div>
                        <div class="col-md-6">
                            <p class="small text-muted mb-0">Khata (Old/New)</p>
                            <h6 class="fw-bold" id="v_khata"></h6>
                        </div>
                        <div class="col-md-12">
                            <p class="small text-muted mb-0">Rakwa Measurement</p>
                            <h6 class="fw-bold text-primary" id="v_rakwa"></h6>
                        </div>
                        <div class="col-md-12">
                            <p class="small text-muted mb-0">Chauhaddi</p>
                            <div class="p-2 border rounded bg-light" style="font-size:12px;" id="v_chauhaddi"></div>
                        </div>

                        <div class="col-12 mt-3">
                            <h6 class="fw-bold text-secondary border-bottom pb-2">Financials</h6>
                        </div>
                        <div class="col-md-6">
                            <p class="small text-muted mb-0">Total Land Value</p>
                            <h6 class="fw-bold text-success" id="v_land_value"></h6>
                        </div>
                        <div class="col-md-6">
                            <p class="small text-muted mb-0">Agent Commission</p>
                            <h6 class="fw-bold text-danger" id="v_agent_comm"></h6>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="landownerModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold" id="modalTitle" style="color: var(--sidebar-bg);"><i
                            class="fas fa-map-marked-alt me-2 text-primary"></i> Register Landowner</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <form id="loForm" enctype="multipart/form-data">
                        <input type="hidden" id="edit_id">

                        <ul class="nav nav-tabs px-4 pt-3 bg-light" role="tablist">
                            <li class="nav-item"><button class="nav-link active fw-bold" data-bs-toggle="tab"
                                    data-bs-target="#tab-personal" type="button">1. Personal & Bank</button></li>
                            <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab"
                                    data-bs-target="#tab-land" type="button">2. Land & Finance</button></li>
                            <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab"
                                    data-bs-target="#tab-nominee" type="button">3. Nominee Info</button></li>
                            <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab"
                                    data-bs-target="#tab-docs" type="button">4. Documents</button></li>
                        </ul>

                        <div class="tab-content p-4">

                            <div class="tab-pane fade show active" id="tab-personal">

                                <!-- ALREADY REGISTERED TOGGLE -->
<div class="form-check form-switch mb-3 p-3 bg-light border rounded">
    <input class="form-check-input" type="checkbox" id="alreadyRegisteredToggle" style="margin-left: 0; width: 2.5em; height: 1.25em; cursor: pointer;">
    <label class="form-check-label ms-3 fw-bold text-primary" for="alreadyRegisteredToggle" style="cursor: pointer; margin-top: 2px;">
        Already Registered? (Auto-fill Data)
    </label>
</div>

<!-- AUTO-FILL SEARCH SECTION (Hidden by default) -->
<div id="alreadyRegisteredSection" class="row g-3 mb-4 p-3 bg-white border border-primary rounded shadow-sm" style="display: none;">
    <h6 class="text-primary fw-bold mb-1"><i class="fas fa-search me-1"></i> Search Existing Record</h6>
    
    <div class="col-md-3 position-relative">
        <label class="form-label text-secondary small">Search Company</label>
        <input type="text" class="form-control" id="search_reg_company" placeholder="Type 3 letters...">
        <input type="hidden" id="search_reg_company_id">
        <ul class="list-group position-absolute w-100 shadow-sm" id="s_reg_company_list" style="z-index: 1000; display: none;"></ul>
    </div>
    
    <div class="col-md-3 position-relative">
        <label class="form-label text-secondary small">Search Branch</label>
        <input type="text" class="form-control" id="search_reg_branch" placeholder="Type 3 letters..." disabled>
        <input type="hidden" id="search_reg_branch_id">
        <ul class="list-group position-absolute w-100 shadow-sm" id="s_reg_branch_list" style="z-index: 1000; display: none;"></ul>
    </div>
    
    <div class="col-md-3 position-relative">
        <label class="form-label text-secondary small">Search Phase</label>
        <input type="text" class="form-control" id="search_reg_phase" placeholder="Type 2 letters..." disabled>
        <input type="hidden" id="search_reg_phase_id">
        <ul class="list-group position-absolute w-100 shadow-sm" id="s_reg_phase_list" style="z-index: 1000; display: none;"></ul>
    </div>

    <div class="col-md-3 position-relative">
        <label class="form-label text-secondary small">Search Landowner</label>
        <input type="text" class="form-control" id="search_reg_landowner" placeholder="Type name..." disabled>
        <input type="hidden" id="search_reg_landowner_id">
        <ul class="list-group position-absolute w-100 shadow-sm" id="s_reg_landowner_list" style="z-index: 1000; display: none;"></ul>
    </div>
</div>

<!-- STANDARD FORM FIELDS (Company -> Branch -> Phase -> Agent) -->
<div class="row g-3 mb-4 pb-3 border-bottom" id="standardFormFields">
    
    <div class="col-md-3 position-relative">
        <label class="form-label text-secondary small">Select Company <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="f_company" placeholder="Type 3 letters..." required autocomplete="off">
        <input type="hidden" name="company_id" id="company_id_hidden" required>
        <ul class="list-group position-absolute w-100 shadow-sm" id="f_company_list" style="z-index: 1000; display: none;"></ul>
    </div>

    <div class="col-md-3 position-relative">
        <label class="form-label text-secondary small">Select Branch <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="f_branch" placeholder="Type 3 letters..." required autocomplete="off" disabled>
        <input type="hidden" name="branch_id" id="branch_id_hidden" required>
        <ul class="list-group position-absolute w-100 shadow-sm" id="f_branch_list" style="z-index: 1000; display: none;"></ul>
    </div>

    <div class="col-md-3 position-relative">
        <label class="form-label text-secondary small">Select Phase <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="f_phase" placeholder="Type 2 letters..." required autocomplete="off" disabled>
        <input type="hidden" name="phase_id" id="phase_id_hidden" required>
        <ul class="list-group position-absolute w-100 shadow-sm" id="f_phase_list" style="z-index: 1000; display: none;"></ul>
    </div>

    <div class="col-md-3 position-relative">
        <label class="form-label text-secondary small">Select Agent (Optional)</label>
        <!-- Purana datalist wala agent as it is rakha hai jaisa aapne bola -->
        <input type="text" name="agent_id" class="form-control" id="f_agent" list="agentList" placeholder="Search Agent ID..." autocomplete="off">
        <datalist id="agentList"></datalist>
    </div>
</div>

                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label text-secondary small">Land Owner Name
                                            <span class="text-danger">*</span></label><input type="text"
                                            name="land_owner_name" class="form-control" id="f_name" required></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">S/o, D/o,
                                            W/o</label><input type="text" name="relation_name" class="form-control"
                                            id="f_rel"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Date of
                                            Birth</label><input type="date" name="lo_dob" class="form-control"
                                            id="f_dob"></div>

                                    <div class="col-md-4"><label class="form-label text-secondary small">Aadhar
                                            Number</label><input type="text" name="lo_aadhar" class="form-control"
                                            id="f_aadhar" maxlength="12"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">PAN
                                            Number</label><input type="text" name="lo_pan"
                                            class="form-control text-uppercase" id="f_pan" maxlength="10"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Mobile No. 1
                                            <span class="text-danger">*</span></label><input type="text"
                                            name="mobile1" class="form-control" id="f_mob1" required maxlength="10">
                                    </div>

                                    <div class="col-md-4"><label class="form-label text-secondary small">Mobile No.
                                            2</label><input type="text" name="mobile2" class="form-control"
                                            id="f_mob2" maxlength="10"></div>
                                    <div class="col-md-8"><label
                                            class="form-label text-secondary small">Address</label><input type="text"
                                            name="address" class="form-control" id="f_addr"></div>

                                    <div class="col-md-3"><label
                                            class="form-label text-secondary small">State</label><input type="text"
                                            name="lo_state" class="form-control" id="f_state"></div>
                                    <div class="col-md-3"><label
                                            class="form-label text-secondary small">District</label><input type="text"
                                            name="lo_district" class="form-control" id="f_dist"></div>
                                    <div class="col-md-3"><label
                                            class="form-label text-secondary small">Block</label><input type="text"
                                            name="lo_block" class="form-control" id="f_block"></div>
                                    <div class="col-md-3"><label
                                            class="form-label text-secondary small">Village</label><input type="text"
                                            name="lo_village" class="form-control" id="f_vill"></div>
                                </div>

                                <h6 class="text-primary fw-bold border-bottom pb-2 mt-4 mb-3"><i
                                        class="fas fa-university me-1"></i> Bank Details</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Account Name</label>
                                        <input type="text" name="account_name" class="form-control" id="f_b_name">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Account No.</label>
                                        <input type="text" name="account_no" class="form-control" id="f_b_acc">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Account Type</label>
                                        <select class="form-select" name="account_type" id="f_b_type">
                                            <option value="">-- Select Type --</option>
                                            <option value="saving">Saving Account</option>
                                            <option value="current">Current Account</option>
                                            <option value="cc">Cash Credit Account</option>
                                            <option value="od">Over Draft Account</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Bank Name</label>
                                        <input type="text" name="bank_name" class="form-control" id="f_b_bank">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">Branch Name</label>
                                        <input type="text" name="branch" class="form-control" id="f_b_branch">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small">IFSC Code</label>
                                        <input type="text" name="ifsc_code" class="form-control text-uppercase"
                                            id="f_b_ifsc">
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-land">
                                <div class="row g-3 mb-4 pb-3 border-bottom">
                                    <div class="col-md-3"><label class="form-label text-secondary small">Mauza
                                            Name</label><input type="text" name="mauze_name" class="form-control"
                                            id="f_mauza"></div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Thana
                                            No.</label><input type="text" name="thana_no" class="form-control"
                                            id="f_thana"></div>
                                    <div class="col-md-3"><label
                                            class="form-label text-secondary small">Jamabandi</label><input type="text"
                                            name="jamabandi" class="form-control" id="f_jama"></div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Agreement
                                            Date</label><input type="date" name="agree_date" class="form-control"
                                            id="f_agr_date"></div>
                                </div>

                                <h6 class="text-primary fw-bold mb-3">Land Measurement (JSON)</h6>
                                <div class="row g-3 p-3 bg-light rounded border mb-4">
                                    <div class="col-md-3"><label class="form-label text-secondary small">Old
                                            Khesra</label><input type="text" name="khesra_no[old_khesra_no]"
                                            class="form-control" id="f_kh_old"></div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">New
                                            Khesra</label><input type="text" name="khesra_no[new_khesra_no]"
                                            class="form-control" id="f_kh_new"></div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Old
                                            Khata</label><input type="text" name="khata[old_khata]"
                                            class="form-control" id="f_kha_old"></div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">New
                                            Khata</label><input type="text" name="khata[new_khata]"
                                            class="form-control" id="f_kha_new"></div>

                                    <div class="col-12 mt-3"><label class="form-label text-secondary small fw-bold">Rakwa
                                            Details</label></div>
                                    <div class="col-md-2"><label
                                            class="form-label text-secondary small">Bigha</label><input type="number"
                                            name="rakuwa[bigha]" class="form-control" id="f_r_big"></div>
                                    <div class="col-md-2"><label
                                            class="form-label text-secondary small">Kattha</label><input type="number"
                                            name="rakuwa[kattha]" class="form-control" id="f_r_kat"></div>
                                    <div class="col-md-2"><label
                                            class="form-label text-secondary small">Dhoor</label><input type="number"
                                            name="rakuwa[dhoor]" class="form-control" id="f_r_dho"></div>
                                    <div class="col-md-2"><label
                                            class="form-label text-secondary small">Kanma</label><input type="number"
                                            name="rakuwa[kanma]" class="form-control" id="f_r_kan"></div>
                                    <div class="col-md-2"><label
                                            class="form-label text-secondary small">Dismil</label><input type="number"
                                            name="rakuwa[dismil]" class="form-control" id="f_r_dis"></div>
                                    <div class="col-md-2"><label class="form-label text-secondary small">Sq.
                                            Feet</label><input type="number" name="rakuwa[squarefeet]"
                                            class="form-control" id="f_r_sqf"></div>

                                    <div class="col-12 mt-3"><label
                                            class="form-label text-secondary small fw-bold">Chauhaddi</label></div>
                                    <div class="col-md-3"><label
                                            class="form-label text-secondary small">North</label><input type="text"
                                            name="chauhaddi[north]" class="form-control" id="f_c_n"></div>
                                    <div class="col-md-3"><label
                                            class="form-label text-secondary small">South</label><input type="text"
                                            name="chauhaddi[south]" class="form-control" id="f_c_s"></div>
                                    <div class="col-md-3"><label
                                            class="form-label text-secondary small">East</label><input type="text"
                                            name="chauhaddi[east]" class="form-control" id="f_c_e"></div>
                                    <div class="col-md-3"><label
                                            class="form-label text-secondary small">West</label><input type="text"
                                            name="chauhaddi[west]" class="form-control" id="f_c_w"></div>
                                </div>

                                <h6 class="text-primary fw-bold mb-3">Financials</h6>
                                <div class="row g-3">
                                    <div class="col-md-3"><label class="form-label text-secondary small">Rate Per
                                            Katha</label><input type="number" step="0.01" name="rate_per_katha"
                                            class="form-control" id="f_rate"></div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Total Land
                                            Value</label><input type="number" step="0.01" name="total_land_value"
                                            class="form-control" id="f_tot_val"></div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Agent
                                            Rate/Katha</label><input type="number" step="0.01"
                                            name="agent_rate_per_katha" class="form-control" id="f_ag_rate"></div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Agent Total
                                            Comm.</label><input type="number" step="0.01"
                                            name="agent_total_land_value" class="form-control" id="f_ag_tot"></div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-nominee">
                                <div class="row g-3">
                                    <div class="col-md-3"><label class="form-label text-secondary small">Nominee
                                            Name</label><input type="text" name="nominee_name" class="form-control">
                                    </div>
                                    <div class="col-md-3"><label
                                            class="form-label text-secondary small">Relation</label><input type="text"
                                            name="nominee_relation" class="form-control"></div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">S/o, D/o,
                                            W/o</label><input type="text" name="nominee_so_do_wo"
                                            class="form-control"></div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Date of
                                            Birth</label><input type="date" name="nominee_dob" class="form-control">
                                    </div>

                                    <div class="col-md-4"><label class="form-label text-secondary small">Mobile
                                            No</label><input type="text" name="nominee_mobile" class="form-control"
                                            maxlength="10"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Alt.
                                            Mobile</label><input type="text" name="nominee_alternate_mobile"
                                            class="form-control" maxlength="10"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">Email
                                            Id</label><input type="email" name="nominee_email" class="form-control">
                                    </div>

                                    <div class="col-md-4"><label class="form-label text-secondary small">Aadhar
                                            No.</label><input type="text" name="nominee_aadhar" class="form-control"
                                            maxlength="12"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">PAN
                                            No.</label><input type="text" name="nominee_pan"
                                            class="form-control text-uppercase" maxlength="10"></div>
                                    <div class="col-md-4"><label class="form-label text-secondary small">PIN
                                            Code</label><input type="text" name="nominee_pincode" class="form-control"
                                            maxlength="6"></div>

                                    <div class="col-md-4"><label
                                            class="form-label text-secondary small">State</label><input type="text"
                                            name="nominee_state" class="form-control"></div>
                                    <div class="col-md-4"><label
                                            class="form-label text-secondary small">District</label><input type="text"
                                            name="nominee_district" class="form-control"></div>
                                    <div class="col-md-4"><label
                                            class="form-label text-secondary small">Address</label><input type="text"
                                            name="nominee_address" class="form-control"></div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-docs">
                                <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">Landowner Documents</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3"><label class="form-label text-secondary small">Aadhar
                                            (.pdf)</label><input type="file" name="aadhar_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_aadhar_pdf"></div>
                                    </div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">PAN
                                            (.pdf)</label><input type="file" name="pan_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_pan_pdf"></div>
                                    </div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Bank Passbook
                                            (.pdf)</label><input type="file" name="bank_passbook_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_bank_passbook_pdf"></div>
                                    </div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Passport Photo
                                            (Img)</label><input type="file" name="passport_photo"
                                            class="form-control form-control-sm" accept="image/*">
                                        <div id="link_passport_photo"></div>
                                    </div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Signature
                                            (Img)</label><input type="file" name="sign"
                                            class="form-control form-control-sm" accept="image/*">
                                        <div id="link_sign"></div>
                                    </div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Khatiyaan
                                            (.pdf)</label><input type="file" name="khatiyaan_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_khatiyaan_pdf"></div>
                                    </div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Jamabandi
                                            (.pdf)</label><input type="file" name="jamabandi_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_jamabandi_pdf"></div>
                                    </div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">LO Agreement
                                            (.pdf)</label><input type="file" name="lo_agreement_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_lo_agreement_pdf"></div>
                                    </div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Registry Deed
                                            (.pdf)</label><input type="file" name="registry_deed_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_registry_deed_pdf"></div>
                                    </div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Link Deed
                                            (.pdf)</label><input type="file" name="link_deed_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_link_deed_pdf"></div>
                                    </div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Final Deed
                                            (.pdf)</label><input type="file" name="final_deed_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_final_deed_pdf"></div>
                                    </div>
                                    <div class="col-md-3"><label class="form-label text-secondary small">Other Doc
                                            (.pdf)</label><input type="file" name="other_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_other_pdf"></div>
                                    </div>
                                </div>

                                <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">Nominee Documents</h6>
                                <div class="row g-3 mb-4">
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
                                    <div class="col-md-4"><label class="form-label text-secondary small">Nominee Other
                                            Doc</label><input type="file" name="nom_other_pdf"
                                            class="form-control form-control-sm" accept=".pdf">
                                        <div id="link_nom_other_pdf"></div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="modal-footer bg-light border-top-0">
                            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn text-white px-5 shadow-sm fw-medium"
                                style="background-color: var(--brand-primary);" id="saveBtn">Save Landowner</button>
                        </div>
                    </form>
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
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            const apiToken = localStorage.getItem('admin_token');
            let mode = 'add';
            let branchMap = {}; // Datalist map ke liye

            // ========================================================
// 🔥 NAYA: AUTO-FILL & DEBOUNCING LOGIC 🔥
// ========================================================

// 1. Debounce Function
function debounce(func, delay) {
    let timer;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => func.apply(this, args), delay);
    };
}

// 2. Toggle "Already Registered" Section
$('#alreadyRegisteredToggle').change(function() {
    if ($(this).is(':checked')) {
        $('#alreadyRegisteredSection').slideDown();
        $('#standardFormFields').slideUp();
    } else {
        $('#alreadyRegisteredSection').slideUp();
        $('#standardFormFields').slideDown();
        // Reset Search Fields
        $('#search_reg_company, #search_reg_branch, #search_reg_phase, #search_reg_landowner').val('');
        $('#search_reg_company_id, #search_reg_branch_id, #search_reg_phase_id, #search_reg_landowner_id').val('');
        $('#search_reg_branch, #search_reg_phase, #search_reg_landowner').prop('disabled', true);
    }
});

// 3. Search Company (Already Registered)
$('#search_reg_company').on('input', debounce(function() {
    let q = $(this).val();
    let list = $('#s_reg_company_list');
    if (q.length >= 3) {
        $.get(`/api/v1/search-company?q=${q}`, function(data) {
            list.empty().show();
            if(data.length === 0) list.append('<li class="list-group-item text-muted">No company found</li>');
            data.forEach(item => {
                list.append(`<li class="list-group-item list-group-item-action c-pointer" data-id="${item.id}">${item.company_name}</li>`);
            });
        });
    } else {
        list.hide();
    }
}, 400));

// Select Company
$(document).on('click', '#s_reg_company_list li[data-id]', function() {
    $('#search_reg_company').val($(this).text());
    $('#search_reg_company_id').val($(this).data('id'));
    $('#s_reg_company_list').hide();
    // Enable Next
    $('#search_reg_branch').prop('disabled', false).focus();
    $('#search_reg_branch, #search_reg_phase, #search_reg_landowner').val('');
});

// 4. Search Branch (Already Registered)
$('#search_reg_branch').on('input', debounce(function() {
    let q = $(this).val();
    let c_id = $('#search_reg_company_id').val();
    let list = $('#s_reg_branch_list');
    if (q.length >= 3) {
        $.get(`/api/v1/search-branch?q=${q}&company_id=${c_id}`, function(data) {
            list.empty().show();
            if(data.length === 0) list.append('<li class="list-group-item text-muted">No branch found</li>');
            data.forEach(item => {
                list.append(`<li class="list-group-item list-group-item-action c-pointer" data-id="${item.id}">${item.branch_name}</li>`);
            });
        });
    } else {
        list.hide();
    }
}, 400));

// Select Branch
$(document).on('click', '#s_reg_branch_list li[data-id]', function() {
    $('#search_reg_branch').val($(this).text());
    $('#search_reg_branch_id').val($(this).data('id'));
    $('#s_reg_branch_list').hide();
    // Enable Next
    $('#search_reg_phase').prop('disabled', false).focus();
    $('#search_reg_phase, #search_reg_landowner').val('');
});

// 5. Search Phase (Already Registered)
$('#search_reg_phase').on('input', debounce(function() {
    let q = $(this).val();
    let c_id = $('#search_reg_company_id').val();
    let b_id = $('#search_reg_branch_id').val();
    let list = $('#s_reg_phase_list');
    
    if (q.length >= 2) {
        $.get(`/api/v1/search-phase?q=${q}&company_id=${c_id}&branch_id=${b_id}`, function(data) {
            list.empty().show();
            if(data.length === 0) list.append('<li class="list-group-item text-muted">No phase found</li>');
            data.forEach(item => {
                let c_name = item.company ? item.company.company_name : 'N/A';
                list.append(`<li class="list-group-item list-group-item-action c-pointer" data-id="${item.id}">${item.phase_name} <small class="text-info fw-bold">(${c_name})</small></li>`);
            });
        });
    } else {
        list.hide();
    }
}, 400));

// Select Phase
$(document).on('click', '#s_reg_phase_list li[data-id]', function() {
    $('#search_reg_phase').val($(this).text());
    $('#search_reg_phase_id').val($(this).data('id'));
    $('#s_reg_phase_list').hide();
    // Enable Next
    $('#search_reg_landowner').prop('disabled', false).focus();
    $('#search_reg_landowner').val('');
});

// 6. Search Landowner & AUTO-FILL
$('#search_reg_landowner').on('input', debounce(function() {
    let q = $(this).val();
    let c_id = $('#search_reg_company_id').val();
    let b_id = $('#search_reg_branch_id').val();
    let p_id = $('#search_reg_phase_id').val();
    let list = $('#s_reg_landowner_list');
    
    if (q.length >= 3) {
        $.get(`/api/v1/search-landowners-list?q=${q}&company_id=${c_id}&branch_id=${b_id}&phase_id=${p_id}`, function(data) {
            list.empty().show();
            if(data.length === 0) list.append('<li class="list-group-item text-muted">No landowner found</li>');
            data.forEach(item => {
                list.append(`<li class="list-group-item list-group-item-action c-pointer" data-id="${item.id}">${item.land_owner_name} <small class="text-danger">(${item.mobile1})</small></li>`);
            });
        });
    } else {
        list.hide();
    }
}, 400));

// Select Landowner & Trigger Form Fill
$(document).on('click', '#s_reg_landowner_list li[data-id]', function() {
    let id = $(this).data('id');
    $('#search_reg_landowner').val($(this).text());
    $('#s_reg_landowner_list').hide();
    
    // API se pura data fetch karna
    $.get(`/api/v1/landowners/${id}`, function(res) {
        let d = res.data;
        
        // 1. Inputs Auto-fill (except files)
        Object.keys(d).forEach(key => {
            let input = $(`#loForm [name="${key}"]`);
            if (input.length && input.attr('type') !== 'file' && input.attr('type') !== 'radio') {
                if (typeof d[key] !== 'object') {
                    input.val(d[key]);
                }
            }
        });

        // Bank Branch Name mapping
        $('#f_b_branch').val(d.bank_branch_text);

        // Standard dropdowns hidden values set karna taaki form submit hone pe sahi data jaye
        $('#company_id_hidden').val(d.company_id);
        $('#branch_id_hidden').val(d.branch_id);
        $('#phase_id_hidden').val(d.phase_id);

        // 2. Existing File Previews Render
        const autoFillDocs = [
            'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'passport_photo', 'sign',
            'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_passport_pdf', 'nom_passport_photo'
        ];
        
        autoFillDocs.forEach(field => {
            let filePath = d[field];
            let input = $(`#loForm [name="${field}"]`);
            if (input.length && filePath) {
                let wrapper = input.next('.file-preview-wrapper');
                let content = wrapper.find('.preview-content');
                let fullUrl = filePath.startsWith('/') ? filePath : '/' + filePath;
                let ext = filePath.split('.').pop().toLowerCase();
                let imageExts = ['jpg', 'jpeg', 'png', 'webp', 'bmp'];

                if (imageExts.includes(ext)) {
                    content.html(`<img src="${fullUrl}" style="max-height:80px; border-radius:6px;">`);
                } else {
                    content.html(`<div class="p-2 small"><i class="fas fa-file-pdf text-danger me-2"></i><a href="${fullUrl}" target="_blank">View File</a></div>`);
                }
                wrapper.show();
            }
        });

        showMessage("Landowner Data Auto-Filled Successfully!", "success");
    });
});

// Hide dropdowns when clicked outside
$(document).click(function(e) {
    if (!$(e.target).closest('.position-relative').length) {
        $('.list-group').hide();
    }
});


            // ========================================================
            // 🔥 NAYA: Custom Modal Show Karne ka Function 🔥
            // ========================================================
            window.showMessage = function(message, type = 'success') {
                let modal = $('#responseModal');
                let header = modal.find('.modal-header');
                let title = $('#responseModalTitle');
                let icon = $('#responseModalIcon');
                let btn = $('#responseModalBtn');
                let closeBtn = modal.find('.btn-close');

                // Classes reset kar rahe hain
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

            // Document Fields Array for auto-links
            const loDocFields = [
                'aadhar_pdf', 'pan_pdf', 'bank_passbook_pdf', 'passport_photo', 'sign',
                'khatiyaan_pdf', 'jamabandi_pdf', 'lo_agreement_pdf', 'registry_deed_pdf',
                'link_deed_pdf', 'final_deed_pdf', 'other_pdf',
                'nom_aadhar_pdf', 'nom_pan_pdf', 'nom_bank_passbook_pdf', 'nom_passport_pdf',
                'nom_passport_photo', 'nom_other_pdf'
            ];

            // Helper: JSON to Text functions
            const formatRakwa = (json) => {
                if (!json) return 'N/A';
                let parts = [];
                if (json.bigha) parts.push(`${json.bigha} Bigha`);
                if (json.kattha) parts.push(`${json.kattha} Kattha`);
                if (json.dhoor) parts.push(`${json.dhoor} Dhoor`);
                if (json.squarefeet) parts.push(`${json.squarefeet} SqFt`);
                return parts.join(', ') || 'N/A';
            };

            const formatChauhaddi = (json) => {
                if (!json) return 'N/A';
                return `<b>N:</b> ${json.north||'-'}, <b>S:</b> ${json.south||'-'}, <b>E:</b> ${json.east||'-'}, <b>W:</b> ${json.west||'-'}`;
            };

            // 1. DataTables (Server-side + Excel All Data Trick) (API Updated)
            let table = $('#landownerTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/landowners', // Changed from /admin/
                },
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export All to Excel',
                    className: 'btn btn-success btn-sm shadow-sm rounded-3',
                    // 🔥 NAYA LOGIC: EXCEL MEIN SAARA DATA DOWNLOAD KARNE KI TRICK 🔥
                    action: function(e, dt, button, config) {
                        let oldLength = dt.page.len(); // Purani limit save ki (10)
                        dt.page.len(-1).draw(); // Limit -1 karke saara data draw kiya

                        dt.one('draw', function() {
                            // Jab saara data aa jaye, tab excel ka asli function call karo
                            $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e,
                                dt, button, config);
                            dt.page.len(oldLength).draw(); // Wapas 10 limit set kardo
                        });
                    }
                }],
                columns: [{
                        data: 'land_owner_id',
                        render: d => `<span class="fw-bold text-primary">${d}</span>`
                    },
                    {
                        data: 'land_id',
                        render: d => `<span class="fw-bold text-danger">${d}</span>`
                    },
                    {
                        data: 'land_owner_name'
                    },
                    {
                        data: 'agent_id',
                        render: d => d ? d : 'N/A'
                    },
                    {
                        data: 'mobile1'
                    },
                    {
                        data: 'id',
                        orderable: false,
                        render: d => `
                        <div class="text-end text-nowrap">
                            <button type="button" class="btn btn-sm btn-light text-info view-btn" data-id="${d}"><i class="fas fa-eye"></i></button>
                            <button type="button" class="btn btn-sm btn-light text-primary edit-btn secured-item" data-permission="landowner_edit" data-id="${d}"><i class="fas fa-edit"></i></button>
                            <button type="button" class="btn btn-sm btn-light text-danger delete-btn secured-item" data-permission="landowner_delete" data-id="${d}"><i class="fas fa-trash-alt"></i></button>
                        </div>`
                    }
                ],
                drawCallback: function(settings) {
                    renderMobileCards(settings.json.data);
                }
            });

            // 2. Mobile Cards (Table draw hone par render hoga)
            function renderMobileCards(data) {
                let html = '';
                if (!data || data.length === 0) {
                    html = '<div class="text-center p-3 text-muted bg-light rounded">No landowners found.</div>';
                } else {
                    data.forEach(d => {
                        html += `<div class="mobile-item">
                        <div class="d-flex flex-column mb-2">
                            <h6 class="fw-bold text-dark mb-1">${d.land_owner_name}</h6>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <span class="badge bg-light border text-primary"><i class="fas fa-user-tie me-1"></i> ${d.land_owner_id}</span>
                                <span class="badge bg-light border text-danger"><i class="fas fa-map-marker-alt me-1"></i> ${d.land_id}</span>
                            </div>
                        </div>
                        <div class="small text-muted mt-2"><i class="fas fa-phone me-1"></i> ${d.mobile1}</div>
                        ${d.agent_id ? `<div class="small text-muted mt-1"><i class="fas fa-briefcase me-1"></i> Agent: ${d.agent_id}</div>` : ''}
                        
                        <div class="mt-3 pt-3 border-top d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-light text-info flex-fill fw-bold view-btn" data-id="${d.id}"><i class="fas fa-eye"></i></button>
                            <button type="button" class="btn btn-sm btn-light text-primary flex-fill fw-bold edit-btn secured-item" data-permission="landowner_edit" data-id="${d.id}"><i class="fas fa-edit"></i></button>
                            <button type="button" class="btn btn-sm btn-light text-danger flex-fill fw-bold delete-btn secured-item" data-permission="landowner_delete" data-id="${d.id}"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </div>`;
                    });
                }
                $('#mobileCardsContainer').html(html);

                // 🛡️ RE-APPLY PERMISSIONS for mobile
                if (typeof window.applyPermissions === 'function') window.applyPermissions();
            }

            // Branch Input Mapping Event
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

            // 3. Open Modal
            window.openModal = function(type, id = null) {
                mode = type;
                $('#loForm')[0].reset();
                $('#branch_id_hidden').val('');
                $('#modalTitle').text(type === 'add' ? 'Register Landowner' : 'Edit Landowner');
                $('.nav-tabs button:first').tab('show');
                $('.file-preview-wrapper').hide().find('.preview-content').empty();

                // Branch fetch karein (API Updated)
                $.ajax({
                    url: '/api/v1/branches', // Changed from /admin/
                    success: function(res) {
                        let bOpt = '';
                        branchMap = {};
                        res.data.forEach(b => {
                            let compName = b.company ? b.company.company_name :
                                'Master Company';
                            let disp = `${compName} - ${b.branch_name} (${b.branch_id})`;
                            bOpt += `<option value="${disp}">`;
                            branchMap[disp] = b.id;
                        });
                        $('#branchList').html(bOpt);

                        $.get({
                            url: '/api/v1/agents', // Changed from /admin/
                            success: function(aRes) {
                                let aOpt = '';
                                aRes.data.forEach(a => aOpt +=
                                    `<option value="${a.agent_id}">${a.full_name}</option>`
                                    );
                                $('#agentList').html(aOpt);

                                if (type === 'edit') {
                                    $.get({
                                        url: `/api/v1/landowners/${id}`, // Changed from /admin/
                                        success: function(r) {
                                            let d = r.data;
                                            $('#edit_id').val(d.id);

                                            if (d.branch) {
                                                let compName = d.branch
                                                    .company ? d.branch
                                                    .company.company_name :
                                                    'Master Company';
                                                let disp =
                                                    `${compName} - ${d.branch.branch_name} (${d.branch.branch_id})`;
                                                $('#f_branch').val(disp);
                                                $('#branch_id_hidden').val(d
                                                    .branch_id);
                                            }

                                            Object.keys(d).forEach(key => {
                                                let input = $(
                                                    `#loForm [name="${key}"]`
                                                    );
                                                if (input.length &&
                                                    input.attr(
                                                        'type') !==
                                                    'file' && input
                                                    .attr(
                                                    'type') !==
                                                    'radio') {
                                                    if (typeof d[
                                                            key] ===
                                                        'object' &&
                                                        d[key] !==
                                                        null)
                                                return;
                                                    if (key !==
                                                        'branch_id')
                                                        input.val(d[
                                                                key
                                                                ]);
                                                }
                                            });

                                            $('#f_b_branch').val(d
                                                .bank_branch_text);

                                            // JSON Fields Manual Binding (Rakwa, Khata, etc.)
                                            if (d.khesra_no) {
                                                $('#f_kh_old').val(d
                                                    .khesra_no
                                                    .old_khesra_no);
                                                $('#f_kh_new').val(d
                                                    .khesra_no
                                                    .new_khesra_no);
                                            }
                                            if (d.khata) {
                                                $('#f_kha_old').val(d.khata
                                                    .old_khata);
                                                $('#f_kha_new').val(d.khata
                                                    .new_khata);
                                            }
                                            if (d.rakuwa) {
                                                $('#f_r_big').val(d.rakuwa
                                                    .bigha);
                                                $('#f_r_kat').val(d.rakuwa
                                                    .kattha);
                                                $('#f_r_dho').val(d.rakuwa
                                                    .dhoor);
                                                $('#f_r_kan').val(d.rakuwa
                                                    .kanma);
                                                $('#f_r_dis').val(d.rakuwa
                                                    .dismil);
                                                $('#f_r_sqf').val(d.rakuwa
                                                    .squarefeet);
                                            }
                                            if (d.chauhaddi) {
                                                $('#f_c_n').val(d.chauhaddi
                                                    .north);
                                                $('#f_c_s').val(d.chauhaddi
                                                    .south);
                                                $('#f_c_e').val(d.chauhaddi
                                                    .east);
                                                $('#f_c_w').val(d.chauhaddi
                                                    .west);
                                            }

                                            // EXISTING FILES PREVIEW LOGIC
                                            loDocFields.forEach(field => {
                                                let filePath = d[
                                                    field];
                                                let input = $(
                                                    `#loForm [name="${field}"]`
                                                    );
                                                if (input.length &&
                                                    filePath) {
                                                    let wrapper =
                                                        input.next(
                                                            '.file-preview-wrapper'
                                                            );
                                                    let content =
                                                        wrapper
                                                        .find(
                                                            '.preview-content'
                                                            );
                                                    let fullUrl =
                                                        filePath
                                                        .startsWith(
                                                            '/') ?
                                                        filePath :
                                                        '/' +
                                                        filePath;
                                                    let ext =
                                                        filePath
                                                        .split('.')
                                                        .pop()
                                                        .toLowerCase();
                                                    let imageExts = [
                                                        'jpg',
                                                        'jpeg',
                                                        'png',
                                                        'webp',
                                                        'bmp'
                                                    ];

                                                    if (imageExts
                                                        .includes(
                                                            ext)) {
                                                        content
                                                            .html(
                                                                `<img src="${fullUrl}" style="max-height:80px; border-radius:6px;">`
                                                                );
                                                    } else {
                                                        content
                                                            .html(
                                                                `<div class="p-2 small"><i class="fas fa-file-pdf text-danger me-2"></i><a href="${fullUrl}" target="_blank">View File</a></div>`
                                                                );
                                                    }
                                                    wrapper.show();
                                                }
                                            });

                                        }
                                    });
                                }
                            }
                        });
                    }
                });
                $('#landownerModal').modal('show');
            };


            $(document).on('click', '.edit-btn', function() {
                openModal('edit', $(this).data('id'));
            });


            // 4. Form Submit (Bulletproof Logic) (API Updated)
            $('#loForm').submit(function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                let id = $('#edit_id').val();
                let url = mode === 'add' ? '/api/v1/landowners' :
                `/api/v1/landowners/${id}`; // Changed from /admin/
                if (mode === 'edit') formData.append('_method', 'PUT');

                let btn = $('#saveBtn');

                // 1. Button ko lock kiya aur Saving likha
                btn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#landownerModal').modal('hide');
                        if (typeof table !== 'undefined') table.ajax.reload(null, false);
                        if (typeof loadMobile === 'function') loadMobile();

                        // Check if showMessage function exists, warna normal alert dikhao taaki code crash na ho
                        if (typeof showMessage === 'function') {
                            showMessage(res.message, 'success');
                        } else {
                            alert(res.message);
                        }
                    },
                    error: function(err) {
                        let errMsg = 'Something went wrong!';
                        if (err.responseJSON && err.responseJSON.message) {
                            errMsg = err.responseJSON.message;
                        }

                        if (typeof showMessage === 'function') {
                            showMessage(errMsg, 'error');
                        } else {
                            alert(errMsg);
                        }
                    },
                    complete: function() {
                        // 🔥 2. YE BLOCK HAMESHA CHALEGA AUR BUTTON KO WAPAS THEEK KAREGA 🔥
                        btn.prop('disabled', false).text('Save Landowner');
                    }
                });
            });


            // 5. View Logic (API Updated)
            $(document).on('click', '.view-btn', function() {
                $.get({
                    url: `/api/v1/landowners/${$(this).data('id')}`, // Changed from /admin/
                    success: function(res) {
                        let d = res.data;
                        $('#v_lo_id').text(d.land_owner_id || 'N/A');
                        $('#v_land_id').text(d.land_id || 'N/A');
                        $('#v_branch').text(d.branch ? d.branch.branch_name : 'N/A');
                        $('#v_agent').text(d.agent_id || 'N/A');

                        $('#v_name').text(d.land_owner_name);
                        $('#v_mobile').text(d.mobile1);
                        $('#v_docs').text(
                            `Aadhar: ${d.lo_aadhar||'N/A'} / PAN: ${d.lo_pan||'N/A'}`);

                        // JSON Formats
                        $('#v_khesra').text(
                            `Old: ${d.khesra_no?.old_khesra_no||'-'} / New: ${d.khesra_no?.new_khesra_no||'-'}`
                        );
                        $('#v_khata').text(
                            `Old: ${d.khata?.old_khata||'-'} / New: ${d.khata?.new_khata||'-'}`
                        );
                        $('#v_rakwa').text(formatRakwa(d.rakuwa));
                        $('#v_chauhaddi').html(formatChauhaddi(d.chauhaddi));

                        $('#v_land_value').text(`₹ ${d.total_land_value}`);
                        $('#v_agent_comm').text(`₹ ${d.agent_total_land_value}`);

                        $('#viewModal').modal('show');
                    }
                });
            });

            // 6. Delete Logic (API Updated)
            $(document).on('click', '.delete-btn', function() {
                if (confirm("Delete Landowner?")) {
                    $.ajax({
                        url: `/api/v1/landowners/${$(this).data('id')}`, // Changed from /admin/
                        type: 'DELETE',
                        success: function() {
                            table.ajax.reload(null, false);
                            loadMobile();
                        }
                    });
                }
            });

            // 1. File input ke aage automatically Preview Container lagayein
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

            // 2. File select hone par Preview dikhayein
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

            // 3. X (Cut) Button se file hatayein
            $(document).on('click', '.remove-preview-btn', function() {
                let wrapper = $(this).closest('.file-preview-wrapper');
                wrapper.prev('input[type="file"]').val('');
                wrapper.slideUp();
            });

        });
    </script>
@endpush
