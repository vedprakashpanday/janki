@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <style>
        .step-card {
            border-left: 4px solid #cbd5e1;
            transition: all 0.3s ease;
            opacity: 0.5;
            pointer-events: none;
        }

        .step-card.active {
            border-left-color: var(--brand-primary);
            opacity: 1;
            pointer-events: auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .step-card.completed {
            border-left-color: #10b981;
            opacity: 0.8;
        }

        .form-switch .form-check-input {
            width: 2.5em;
            height: 1.25em;
            cursor: pointer;
        }

        .val-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
        }

        .val-box .title {
            font-size: 11px;
            color: #64748b;
            font-weight: bold;
            text-transform: uppercase;
        }

        .val-box .amount {
            font-size: 18px;
            color: #0f172a;
            font-weight: 900;
        }

        .floating-actions {
            position: fixed;
            bottom: 80px;
            right: 20px;
            z-index: 1040;
            display: none;
            flex-direction: column;
            gap: 10px;
        }

        .salary-card {
            border-radius: 12px;
            border-left: 4px solid var(--brand-primary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .salary-card .metric {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
        }

        .salary-card .val {
            font-size: 14px;
            font-weight: 900;
            color: #0f172a;
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0 text-primary"><i class="fas fa-file-invoice-dollar me-2"></i> Salary Management</h4>
            <div class="d-flex gap-2">
                <button id="btnExportExcel" class="btn btn-success btn-sm fw-bold shadow-sm secured-item"
                    data-permission="emp_salary_export"><i class="fas fa-file-excel me-1"></i> Export Excel</button>
                <button id="btnPrintSalary" class="btn btn-info btn-sm fw-bold shadow-sm text-white secured-item"
                    data-permission="emp_salary_print"><i class="fas fa-print me-1"></i> Print Register</button>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4 secured-item" data-permission="emp_salary_view"
            style="border-radius: 12px;">
            <div class="card-body p-3">
                <form id="salaryFilterForm" class="row g-2 align-items-end">
                    <div class="col-md-2"><label class="small fw-bold text-muted mb-1">Company</label><select
                            class="form-select form-select-sm select2-dynamic" id="filter_company" name="company_id">
                            <option value="">Select Company</option>
                        </select></div>
                    <div class="col-md-2"><label class="small fw-bold text-muted mb-1">Branch</label><select
                            class="form-select form-select-sm select2-dynamic" id="filter_branch" name="branch_id" disabled>
                            <option value="">Select Branch</option>
                        </select></div>
                    <div class="col-md-2"><label class="small fw-bold text-muted mb-1">Department</label><select
                            class="form-select form-select-sm select2-dynamic" id="filter_department" name="department_id"
                            disabled>
                            <option value="">Select Department</option>
                        </select></div>
                    <div class="col-md-2"><label class="small fw-bold text-muted mb-1">Designation</label><select
                            class="form-select form-select-sm select2-dynamic" id="filter_designation" name="designation_id"
                            disabled>
                            <option value="">Select Designation</option>
                        </select></div>
                    <div class="col-md-2"><label class="small fw-bold text-muted mb-1">Employee <span
                                class="text-danger">*</span></label><select
                            class="form-select form-select-sm select2-dynamic" id="filter_employee" name="employee_id"
                            disabled required>
                            <option value="">Select Employee</option>
                        </select></div>
                    <div class="col-md-1"><label class="small fw-bold text-muted mb-1">Month <span
                                class="text-danger">*</span></label><input type="month"
                            class="form-control form-control-sm border-warning fw-bold" id="filter_month"
                            value="{{ date('Y-m') }}" required></div>
                    <div class="col-md-1 text-end"><button type="button" class="btn btn-primary btn-sm w-100 fw-bold"
                            id="btnInitiateCalculation" title="Initiate Calculation"><i
                                class="fas fa-calculator"></i></button></div>
                </form>
            </div>
        </div>

        <!-- 3. Dynamic Calculation Workspace -->
        <div id="calculationWorkspace" class="d-none mb-4">
            <h5 class="fw-bold text-dark mb-3"><i class="fas fa-cogs me-2"></i> Salary Calculation Wizard</h5>
            <div class="row g-3">
                <!-- STEP 1: Attendance Verification -->
                <div class="col-md-12">
                    <div class="card step-card active p-3" id="step1_card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-primary m-0">Step 1: Attendance Verification</h6><span
                                class="badge bg-light text-dark border">Base Salary: ₹<span
                                    id="disp_base_salary">0</span></span>
                        </div>
                        <div class="row mt-2 g-2">
                            <div class="col">
                                <div class="val-box">
                                    <div class="title">P</div>
                                    <div class="amount text-success" id="disp_present">0</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="val-box">
                                    <div class="title">LT</div>
                                    <div class="amount" style="color: #dd6b20;" id="disp_lt">0</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="val-box">
                                    <div class="title">A</div>
                                    <div class="amount text-danger" id="disp_absent">0</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="val-box">
                                    <div class="title">HD</div>
                                    <div class="amount text-warning" id="disp_hd">0</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="val-box">
                                    <div class="title">SL</div>
                                    <div class="amount text-primary" id="disp_sl">0</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="val-box border-danger">
                                    <div class="title text-danger">Unpaid (L)</div>
                                    <div class="amount text-danger" id="disp_leaves">0</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="val-box">
                                    <div class="title">CL</div>
                                    <div class="amount text-info" id="disp_cl">0</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="val-box">
                                    <div class="title">Paid (PL)</div>
                                    <div class="amount text-success" id="disp_pl">0</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="val-box">
                                    <div class="title">WO</div>
                                    <div class="amount text-secondary" id="disp_wo">0</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="val-box">
                                    <div class="title">HO</div>
                                    <div class="amount text-secondary" id="disp_ho">0</div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3 g-2 align-items-center">
                            <div class="col-md-2">
                                <div class="val-box bg-dark text-white border-dark">
                                    <div class="title text-light">System ED (Added)</div>
                                    <div class="amount text-white" id="disp_sys_ed">0</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="val-box border-primary bg-white shadow-sm">
                                    <div class="title text-primary">Total Payable Days</div>
                                    <div class="amount text-primary fs-4" id="disp_payable_days">0</div>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex justify-content-end align-items-center ms-auto">
                                <div class="form-check form-switch me-3 mt-1"><input class="form-check-input"
                                        type="checkbox" id="toggle_ed"><label class="form-check-label fw-bold"
                                        for="toggle_ed">Want to reward paid days?</label></div>
                                <input type="number" class="form-control text-center border-dark fw-bold d-none"
                                    style="width: 120px;" id="input_ed_days" placeholder="Reward Days" min="0"
                                    step="0.5">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                            <h6 class="m-0 fw-bold">Actual Salary Generated: <span class="text-success fs-5">₹<span
                                        id="calc_actual_salary">0</span></span></h6><button
                                class="btn btn-primary btn-sm fw-bold px-4 step-btn" id="btnStep1Next">Confirm Attendances
                                <i class="fas fa-arrow-right ms-1"></i></button>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Fines & Penalties -->
                <div class="col-md-12">
                    <div class="card step-card p-3" id="step2_card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-danger m-0">Step 2: Fines & Penalties (Auto-Deducted)</h6>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox"
                                    id="toggle_fine"><label class="form-check-label small fw-bold"
                                    for="toggle_fine">Adjust Fine Manually?</label></div>
                        </div>
                        <div class="row align-items-center mt-2">
                            <div class="col-md-8 text-muted small"><i class="fas fa-info-circle"></i> System has
                                automatically fetched and applied the approved fines. Toggle switch to modify the amount.
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-sm"><span
                                        class="input-group-text bg-danger text-white fw-bold">Deduct ₹</span><input
                                        type="number" class="form-control fw-bold text-danger" id="input_fine_amount"
                                        value="0" min="0" readonly></div>
                            </div>
                        </div>
                        <div class="text-end mt-3 pt-2 border-top"><button
                                class="btn btn-danger btn-sm fw-bold px-4 step-btn" id="btnStep2Next">Confirm Fine
                                Deduction <i class="fas fa-arrow-right ms-1"></i></button></div>
                    </div>
                </div>

                <!-- STEP 3: Loans & Advances -->
                <div class="col-md-12">
                    <div class="card step-card p-3" id="step3_card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-warning m-0">Step 3: Loans & Advances (Auto-Deducted 30%)</h6>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox"
                                    id="toggle_loan"><label class="form-check-label small fw-bold"
                                    for="toggle_loan">Adjust Loan Manually?</label></div>
                        </div>
                        <div class="row align-items-center mt-2" id="loan_calculation_area">
                            <input type="hidden" id="hidden_active_loan_id">
                            <div class="col-md-4">
                                <div class="val-box border-warning">
                                    <div class="title text-warning">Active Loan Remaining</div>
                                    <div class="amount">₹<span id="disp_remaining_loan">0</span></div>
                                </div>
                            </div>
                            <div class="col-md-4"><label class="small fw-bold text-muted">Deduction Percentage
                                    (%)</label><input type="number"
                                    class="form-control form-control-sm text-center fw-bold" id="input_loan_percentage"
                                    value="30" min="0" max="100" readonly></div>
                            <div class="col-md-4"><label class="small fw-bold text-muted">Final Loan Deduction
                                    (₹)</label><input type="number"
                                    class="form-control form-control-sm text-center fw-bold text-danger"
                                    id="input_loan_deduction_amount" value="0" min="0" readonly></div>
                        </div>
                        <div class="text-end mt-3 pt-2 border-top"><button
                                class="btn btn-warning btn-sm fw-bold px-4 text-dark step-btn" id="btnStep3Next">Calculate
                                Payable Salary <i class="fas fa-calculator ms-1"></i></button></div>
                    </div>
                </div>

                <!-- STEP 4: Final Summary -->
                <div class="col-md-12">
                    <div class="card step-card p-3 bg-light" id="step4_card">
                        <h6 class="fw-bold text-success mb-3 text-center border-bottom pb-2">Final Salary Summary</h6>
                        <div class="row justify-content-center text-center">
                            <div class="col-md-2"><small class="fw-bold text-muted d-block">Actual Salary</small><span
                                    class="fs-6 fw-bold">₹<span id="summary_actual">0</span></span></div>
                            <div class="col-md-1"><i class="fas fa-minus text-danger mt-3"></i></div>
                            <div class="col-md-2"><small class="fw-bold text-danger d-block">Fines</small><span
                                    class="fs-6 fw-bold text-danger">₹<span id="summary_fine">0</span></span></div>
                            <div class="col-md-1"><i class="fas fa-minus text-danger mt-3"></i></div>
                            <div class="col-md-2"><small class="fw-bold text-danger d-block">Loan EMI</small><span
                                    class="fs-6 fw-bold text-danger">₹<span id="summary_loan">0</span></span></div>
                            <div class="col-md-1"><i class="fas fa-plus text-success mt-3"></i></div>
                            <div class="col-md-2"><small class="fw-bold text-success d-block">TA Added</small><span
                                    class="fs-6 fw-bold text-success">₹<span id="summary_ta">0</span></span></div>
                        </div>
                        <div class="text-center mt-4 mb-3">
                            <h4 class="fw-bold mb-1">Net Payable: <span
                                    class="text-success bg-white px-3 py-1 border rounded shadow-sm">₹<span
                                        id="summary_net_payable">0</span></span></h4>
                        </div>
                        <div class="form-group mb-3"><label class="small fw-bold text-muted">Remarks (Optional)</label>
                            <textarea class="form-control form-control-sm border-secondary" id="input_remarks" rows="2"
                                placeholder="Enter any notes or remarks regarding this salary generation..."></textarea>
                        </div>
                        <div class="text-center mt-2 pt-3 border-top"><button
                                class="btn btn-success btn-lg fw-bold px-5 shadow" id="btnFinalizeSalary"><i
                                    class="fas fa-check-circle me-1"></i> Finalize Salary</button></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Generated Salaries List -->
        <div class="card shadow-sm border-0 secured-item" data-permission="emp_salary_view" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between">
                <h6 class="fw-bold text-secondary m-0"><i class="fas fa-list me-2"></i> Salary Register (Filtered Month)
                </h6>
            </div>
            <div class="card-body">
                <!-- Desktop View -->
                <div class="table-responsive d-none d-md-block">
                    <table id="salariesTable" class="table table-sm table-bordered table-hover align-middle w-100"
                        style="font-size: 12px;">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center"><input type="checkbox" id="selectAllDesktop"></th>
                                <th>EMP Code</th>
                                <th>Name</th>
                                <th>Month</th>
                                <th class="text-center">P</th>
                                <th class="text-center">A</th>
                                <th class="text-center">L/SL</th>
                                <th class="text-end">Actual (₹)</th>
                                <th class="text-end text-danger">Fine (₹)</th>
                                <th class="text-end text-danger">Loan (₹)</th>
                                <th class="text-end text-success">TA (₹)</th>
                                <th class="text-end text-primary fw-bold">Payable (₹)</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <!-- Mobile View Cards -->
                <div id="mobileCardsContainer" class="d-md-none row g-3"></div>
            </div>
        </div>

        <div class="floating-actions" id="bulkActionBtns">
            <button class="btn btn-primary shadow rounded-circle p-3" id="btnSelectAllFloating" title="Select All"><i
                    class="fas fa-check-double"></i></button>
            <button class="btn btn-danger shadow rounded-circle p-3 secured-item" data-permission="emp_salary_delete"
                id="btnBulkDelete" title="Delete Selected"><i class="fas fa-trash-alt"></i></button>
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
            let adminToken = localStorage.getItem('admin_token') || localStorage.getItem('emp_token');
            let calculationCache = {};

            // DROPDOWNS
            $.ajax({
                url: '/api/v1/task-dependencies/companies',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + adminToken
                },
                success: function(res) {
                    let options = '<option value="">Select Company</option>';
                    let data = res.data || res;
                    if (Array.isArray(data)) {
                        data.forEach(c => {
                            options += `<option value="${c.id}">${c.company_name}</option>`;
                        });
                    }
                    $('#filter_company').html(options);
                }
            });
            $('#filter_company').change(function() {
                let companyId = $(this).val();
                $('#filter_branch, #filter_department, #filter_designation, #filter_employee').html(
                    '<option value="">Select...</option>').prop('disabled', true);
                if (companyId) {
                    $.ajax({
                        url: '/api/v1/task-dependencies/branches',
                        type: 'GET',
                        headers: {
                            'Authorization': 'Bearer ' + adminToken
                        },
                        data: {
                            company_ids: companyId
                        },
                        success: function(res) {
                            let compName = $("#filter_company option:selected").text();
                            let options = '<option value="">Select Branch</option>';
                            if (compName) options +=
                                `<option value="HO" class="fw-bold text-primary">${compName} (Head Office)</option>`;
                            let data = res.data || res;
                            if (Array.isArray(data)) {
                                data.forEach(b => {
                                    options +=
                                        `<option value="${b.id}">${b.branch_name}</option>`;
                                });
                            }
                            $('#filter_branch').html(options).prop('disabled', false);
                        }
                    });
                }
            });
            $('#filter_branch').change(function() {
                let branchId = $(this).val();
                let companyId = $('#filter_company').val();
                $('#filter_department, #filter_designation, #filter_employee').html(
                    '<option value="">Select...</option>').prop('disabled', true);
                if (branchId && companyId) {
                    $.ajax({
                        url: '/api/v1/task-dependencies/departments',
                        type: 'GET',
                        headers: {
                            'Authorization': 'Bearer ' + adminToken
                        },
                        data: {
                            company_ids: companyId,
                            branch_ids: branchId,
                            assignee_type: 'App\\Models\\Employee'
                        },
                        success: function(res) {
                            let options = '<option value="">Select Department</option>';
                            let data = res.data || res;
                            if (Array.isArray(data)) {
                                data.forEach(d => {
                                    options +=
                                        `<option value="${d.id}">${d.department_name}</option>`;
                                });
                            }
                            $('#filter_department').html(options).prop('disabled', false);
                        }
                    });
                }
            });
            $('#filter_department').change(function() {
                let deptId = $(this).val();
                $('#filter_designation, #filter_employee').html('<option value="">Select...</option>').prop(
                    'disabled', true);
                if (deptId) {
                    $.ajax({
                        url: '/api/v1/task-dependencies/designations',
                        type: 'GET',
                        headers: {
                            'Authorization': 'Bearer ' + adminToken
                        },
                        data: {
                            department_ids: deptId
                        },
                        success: function(res) {
                            let options = '<option value="">Select Designation</option>';
                            let data = res.data || res;
                            if (Array.isArray(data)) {
                                data.forEach(desig => {
                                    options +=
                                        `<option value="${desig.id}">${desig.designation_name}</option>`;
                                });
                            }
                            $('#filter_designation').html(options).prop('disabled', false);
                        }
                    });
                }
            });
            $('#filter_designation').change(function() {
                let desigId = $(this).val();
                $('#filter_employee').html('<option value="">Select Employee</option>').prop('disabled',
                    true);
                if (desigId) {
                    $.ajax({
                        url: '/api/v1/task-dependencies/employees',
                        type: 'GET',
                        headers: {
                            'Authorization': 'Bearer ' + adminToken
                        },
                        data: {
                            designation_ids: desigId
                        },
                        success: function(res) {
                            let options = '<option value="">Select Employee</option>';
                            let data = res.data || res;
                            if (Array.isArray(data)) {
                                data.forEach(emp => {
                                    options +=
                                        `<option value="${emp.id}">${emp.full_name} (${emp.member_id})</option>`;
                                });
                            }
                            $('#filter_employee').html(options).prop('disabled', false);
                        }
                    });
                }
            });

            // CALCULATION
            $('#btnInitiateCalculation').click(function() {
                let empId = $('#filter_employee').val();
                let month = $('#filter_month').val();
                if (!empId || !month) {
                    Swal.fire('Warning', 'Select Employee and Month!', 'warning');
                    return;
                }
                Swal.fire({
                    title: 'Fetching Data...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                $.ajax({
                    url: '/api/v1/salaries/calculate',
                    type: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + adminToken
                    },
                    data: {
                        employee_id: empId,
                        month: month
                    },
                    success: function(res) {
                        Swal.close();
                        if (res.status === 'success') {
                            calculationCache = res.data;
                            $('#disp_base_salary').text(calculationCache.base_salary);
                            $('#disp_present').text(calculationCache.attendance.present);
                            $('#disp_absent').text(calculationCache.attendance.absent);
                            $('#disp_leaves').text(calculationCache.attendance.leaves);
                            $('#disp_cl').text(calculationCache.attendance.cl);
                            $('#disp_pl').text(calculationCache.attendance.paid_leaves);
                            $('#disp_sl').text(calculationCache.attendance.short_leaves);
                            $('#disp_hd').text(calculationCache.attendance.half_day);
                            $('#disp_lt').text(calculationCache.attendance.late_punch);
                            $('#disp_wo').text(calculationCache.attendance.week_offs);
                            $('#disp_ho').text(calculationCache.attendance.holidays);
                            $('#disp_sys_ed').text(calculationCache.attendance.extra_days);
                            $('#disp_payable_days').text(calculationCache.attendance
                                .payable_days);
                            $('#calc_actual_salary').text(calculationCache.actual_salary);

                            $('#input_fine_amount').val(calculationCache.total_fine);
                            if (calculationCache.active_loan) {
                                $('#hidden_active_loan_id').val(calculationCache.active_loan
                                .id);
                                $('#disp_remaining_loan').text(calculationCache.active_loan
                                    .remaining_amount);
                                $('#input_loan_deduction_amount').val(calculationCache
                                    .active_loan.default_cut);
                            } else {
                                $('#hidden_active_loan_id').val('');
                                $('#disp_remaining_loan').text('0');
                                $('#input_loan_deduction_amount').val('0');
                            }

                            $('#calculationWorkspace').removeClass('d-none');
                            $('.step-card').removeClass('active completed');
                            $('#step1_card').addClass('active');
                        }
                    },
                    error: function(err) {
                        Swal.fire('Error', err.responseJSON?.message || 'Failed to calculate.',
                            'error');
                    }
                });
            });

            // WIZARD FLOW & RECALCULATION
            function recalculateNet() {
                let actual = calculationCache.current_actual_salary || calculationCache.actual_salary;
                let fine = parseFloat($('#input_fine_amount').val()) || 0;
                let ta = calculationCache.total_ta || 0;
                let loanPercent = parseFloat($('#input_loan_percentage').val()) || 0;
                let remainingLoan = calculationCache.active_loan ? calculationCache.active_loan.remaining_amount :
                0;
                let loanCut = 0;
                if (!$('#toggle_loan').is(':checked')) {
                    let baseForLoan = actual - fine;
                    if (baseForLoan > 0) {
                        loanCut = baseForLoan * (loanPercent / 100);
                        if (loanCut > remainingLoan) loanCut = remainingLoan;
                    }
                    $('#input_loan_deduction_amount').val(loanCut.toFixed(2));
                } else {
                    loanCut = parseFloat($('#input_loan_deduction_amount').val()) || 0;
                }

                let netPayable = (actual - fine - loanCut) + ta;
                if (netPayable < 0) netPayable = 0;
                $('#summary_actual').text(actual.toFixed(2));
                $('#summary_fine').text(fine.toFixed(2));
                $('#summary_loan').text(loanCut.toFixed(2));
                $('#summary_ta').text(ta.toFixed(2));
                $('#summary_net_payable').text(netPayable.toFixed(2));
            }

        // REWARD DAYS (ED) LOGIC UPDATED FOR LATEST PER DAY SALARY
    $('#toggle_ed').change(function() {
        if ($(this).is(':checked')) {
            $('#input_ed_days').removeClass('d-none');
        } else {
            $('#input_ed_days').addClass('d-none').val('');
            let basePayableDays = calculationCache.attendance.payable_days;
            $('#disp_payable_days').text(basePayableDays);
            $('#calc_actual_salary').text(calculationCache.actual_salary.toFixed(2));
            calculationCache.current_actual_salary = calculationCache.actual_salary;
            recalculateNet();
        }
    });

    $('#input_ed_days').on('input', function() {
        let rewardDays = parseFloat($(this).val()) || 0;
        let basePayableDays = calculationCache.attendance.payable_days;
        
        let newPayableDays = basePayableDays + rewardDays;
        // 🔥 NAYA: Hum latest_per_day use kar rahe hain taaki promotion ke baad wali salary se ED reward ho
        let rewardAmount = rewardDays * calculationCache.latest_per_day; 
        let newActual = calculationCache.actual_salary + rewardAmount;
        
        $('#disp_payable_days').text(newPayableDays);
        $('#calc_actual_salary').text(newActual.toFixed(2));
        calculationCache.current_actual_salary = newActual;
        recalculateNet();
    });
            $('#toggle_fine').change(function() {
                if ($(this).is(':checked')) {
                    $('#input_fine_amount').prop('readonly', false);
                } else {
                    $('#input_fine_amount').prop('readonly', true).val(calculationCache.total_fine);
                }
                recalculateNet();
            });
            $('#input_fine_amount').on('input', recalculateNet);
            $('#toggle_loan').change(function() {
                if ($(this).is(':checked')) {
                    $('#input_loan_percentage, #input_loan_deduction_amount').prop('readonly', false);
                } else {
                    $('#input_loan_percentage').prop('readonly', true).val(30);
                    $('#input_loan_deduction_amount').prop('readonly', true);
                    recalculateNet();
                }
            });
            $('#input_loan_percentage, #input_loan_deduction_amount').on('input', recalculateNet);

            $('#btnStep1Next').click(function() {
                $('#step1_card').removeClass('active').addClass('completed');
                $('#step2_card').addClass('active');
            });
            $('#btnStep2Next').click(function() {
                $('#step2_card').removeClass('active').addClass('completed');
                $('#step3_card').addClass('active');
            });
            $('#btnStep3Next').click(function() {
                recalculateNet();
                $('#step3_card').removeClass('active').addClass('completed');
                $('#step4_card').addClass('active');
            });

            // FINALIZE API
            $('#btnFinalizeSalary').click(function() {
                Swal.fire({
                    title: 'Finalize Salary?',
                    text: "Reverify data before submitting.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Finalize'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let payload = {
                            employee_id: $('#filter_employee').val(),
                            salary_month: $('#filter_month').val(),
                            base_salary: calculationCache.base_salary,
                            per_day_salary: calculationCache.per_day_salary,
                            present_days: calculationCache.attendance.present,
                            absent_days: calculationCache.attendance.absent,
                            half_days: calculationCache.attendance.half_day,
                            paid_leaves: calculationCache.attendance.paid_leaves,
                            short_leaves: calculationCache.attendance.short_leaves,
                            week_offs: calculationCache.attendance.week_offs,
                            holidays: calculationCache.attendance.holidays,
                            extra_days: calculationCache.attendance.extra_days,
                            reward_days: $('#input_ed_days').val() || 0,
                            total_payable_days: $('#disp_payable_days').text(),
                            actual_salary: $('#summary_actual').text(),
                            travel_allowance_added: $('#summary_ta').text(),
                            fine_deduction: $('#summary_fine').text(),
                            loan_deduction: $('#summary_loan').text(),
                            net_payable_salary: $('#summary_net_payable').text(),
                            remarks: $('#input_remarks').val(),
                            active_loan_id: $('#hidden_active_loan_id').val()
                        };
                        $.ajax({
                            url: '/api/v1/salaries/store',
                            type: 'POST',
                            headers: {
                                'Authorization': 'Bearer ' + adminToken
                            },
                            data: payload,
                            success: function(res) {
                                Swal.fire('Success!', res.message, 'success').then(
                            () => {
                                    $('#calculationWorkspace').addClass(
                                        'd-none');
                                    salariesTable.draw();
                                });
                            },
                            error: function(err) {
                                Swal.fire('Error', err.responseJSON?.message ||
                                    'Failed to save.', 'error');
                            }
                        });
                    }
                });
            });

         // Naya function floating buttons ko show/hide karne ke liye
            function toggleFloatingButtons() {
                let checkedCount = $('.row-checkbox:checked').length;
                if (checkedCount > 0) {
                    $('#bulkActionBtns').fadeIn();
                } else {
                    $('#bulkActionBtns').fadeOut();
                }
            }

            // DATATABLES & CHECKBOX LOGIC
            let salariesTable = $('#salariesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/salaries',
                    type: 'GET',
                    headers: { 'Authorization': 'Bearer ' + adminToken },
                    data: function(d) {
                        d.company_id = $('#filter_company').val();
                        d.branch_id = $('#filter_branch').val();
                        d.department_id = $('#filter_department').val();
                        d.employee_id = $('#filter_employee').val();
                        d.month = $('#filter_month').val();
                    }
                },
                columns: [
                    {
                        data: 'id',
                        orderable: false,
                        className: 'text-center',
                        render: function(data) {
                            return `<input type="checkbox" class="row-checkbox form-check-input border-dark" value="${data}">`;
                        }
                    },
                    { data: 'emp_code' }, { data: 'name', className: 'fw-bold text-primary' }, { data: 'month' }, 
                    { data: 'present', className: 'text-center text-success fw-bold' },
                    { data: 'absent', className: 'text-center text-danger fw-bold' }, 
                    { data: 'leaves', className: 'text-center text-info fw-bold' }, 
                    { data: 'actual', className: 'text-end' },
                    { data: 'fine', className: 'text-end text-danger fw-bold' }, 
                    { data: 'loan', className: 'text-end text-danger fw-bold' }, 
                    { data: 'ta', className: 'text-end text-success fw-bold' },
                    { data: 'payable', className: 'text-end text-primary fs-6 fw-bold' },
                    {
                        data: 'status',
                        className: 'text-center',
                        render: function(data) {
                            let badge = data === 'active' || data === 'paid' ? 'bg-success' : 'bg-warning text-dark';
                            return `<span class="badge ${badge}">${data.toUpperCase()}</span>`;
                        }
                    },
                    {
                        data: 'id',
                        orderable: false,
                        className: 'text-center',
                        render: function(data) {
                            return `<button class="btn btn-sm btn-outline-info" title="View"><i class="fas fa-eye"></i></button> <button class="btn btn-sm btn-danger btnDeleteSalary secured-item" data-permission="emp_salary_delete" data-id="${data}" title="Delete"><i class="fas fa-trash-alt"></i></button>`;
                        }
                    }
                ],
                drawCallback: function(settings) {
                    let api = this.api();
                    let records = api.rows({ page: 'current' }).data();
                    let mobileHtml = '';
                    records.each(function(r) {
                        mobileHtml += `
                        <div class="col-12">
                            <div class="card salary-card bg-white p-3">
                                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                    <div><div class="form-check d-inline-block me-2"><input class="form-check-input row-checkbox border-dark" type="checkbox" value="${r.id}"></div><span class="fw-bold text-primary">${r.name}</span> <br><small class="text-muted">${r.emp_code} | ${r.month}</small></div>
                                    <div class="text-end"><span class="badge ${r.status === 'active' || r.status === 'paid' ? 'bg-success' : 'bg-warning text-dark'}">${r.status.toUpperCase()}</span><br><div class="mt-1"><button class="btn btn-sm btn-outline-info py-0 px-2"><i class="fas fa-eye"></i></button> <button class="btn btn-sm btn-danger py-0 px-2 btnDeleteSalary secured-item" data-permission="emp_salary_delete" data-id="${r.id}"><i class="fas fa-trash-alt"></i></button></div></div>
                                </div>
                                <div class="row text-center g-2 mt-1">
                                    <div class="col-4"><div class="metric">Actual</div><div class="val">₹${r.actual}</div></div><div class="col-4 border-start border-end"><div class="metric text-danger">Fine+Loan</div><div class="val text-danger">₹${(parseFloat(r.fine) + parseFloat(r.loan)).toFixed(2)}</div></div><div class="col-4"><div class="metric text-primary">Payable</div><div class="val text-primary fs-5">₹${r.payable}</div></div>
                                </div>
                            </div>
                        </div>`;
                    });
                    $('#mobileCardsContainer').html(mobileHtml);
                    
                    // Reset Checkboxes and Buttons on page change
                    $('#selectAllDesktop').prop('checked', false);
                    toggleFloatingButtons();
                    window.applyPermissions();
                }
            });

            $('#filter_month, #filter_employee').change(function() {
                salariesTable.draw();
            });

            // Single Checkbox Tick Event
            $(document).on('change', '.row-checkbox', function() {
                toggleFloatingButtons();
            });

            // Select All Event
            $('#selectAllDesktop, #btnSelectAllFloating').click(function() {
                let isChecked = $('#selectAllDesktop').prop('checked');
                if (this.id === 'btnSelectAllFloating') {
                    isChecked = !isChecked;
                    $('#selectAllDesktop').prop('checked', isChecked);
                }
                $('.row-checkbox').prop('checked', isChecked);
                toggleFloatingButtons();
            });
            $('#filter_month, #filter_employee').change(function() {
                salariesTable.draw();
            });

            $('#selectAllDesktop, #btnSelectAllFloating').click(function() {
                let isChecked = $('#selectAllDesktop').prop('checked');
                if (this.id === 'btnSelectAllFloating') isChecked = !isChecked;
                $('#selectAllDesktop').prop('checked', isChecked);
                $('.row-checkbox').prop('checked', isChecked);
            });

            $(document).on('click', '.btnDeleteSalary', function() {
                let id = $(this).data('id');
                Swal.fire({
                    title: 'Permanent Delete?',
                    text: "This will delete salary and revert loan deductions!",
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Delete!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/api/v1/salaries/' + id,
                            type: 'DELETE',
                            headers: {
                                'Authorization': 'Bearer ' + adminToken
                            },
                            success: function(res) {
                                Swal.fire('Deleted!', res.message, 'success');
                                salariesTable.draw();
                            }
                        });
                    }
                });
            });

            $('#btnBulkDelete').click(function() {
                let selectedIds = [];
                $('.row-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });
                if (selectedIds.length === 0) {
                    Swal.fire('Notice', 'Select at least one record.', 'info');
                    return;
                }
                Swal.fire({
                    title: 'Bulk Delete?',
                    text: "Deleting these records will revert all associated loan deductions.",
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Delete All!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/api/v1/salaries/bulk-delete',
                            type: 'POST',
                            headers: {
                                'Authorization': 'Bearer ' + adminToken
                            },
                            data: {
                                ids: selectedIds
                            },
                            success: function(res) {
                                Swal.fire('Deleted!', res.message, 'success');
                                $('#selectAllDesktop').prop('checked', false);
                                salariesTable.draw();
                            }
                        });
                    }
                });
            });

            $('#btnPrintSalary').click(function() {
                window.open('/admin/salaries/print?' + $.param({
                    company_id: $('#filter_company').val() || '',
                    branch_id: $('#filter_branch').val() || '',
                    month: $('#filter_month').val() || '',
                    token: adminToken
                }), '_blank');
            });
            $('#btnExportExcel').click(function() {
                window.open('/admin/salaries/print?' + $.param({
                    company_id: $('#filter_company').val() || '',
                    branch_id: $('#filter_branch').val() || '',
                    month: $('#filter_month').val() || '',
                    export: 'excel',
                    token: adminToken
                }), '_blank');
            });
        });
    </script>
@endpush
