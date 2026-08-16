@extends('layout.app')

@section('content')
    <!-- 🛡️ View Permission Check -->
    <div class="secured-item" data-permission="{{ $prefix }}view">
        <div class="container-fluid px-1 px-md-3 py-2">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="fas fa-list-ul me-2"></i>
                    {{ $source === 'index' ? 'Debit Vouchers (Today)' : 'Debit Vouchers Directory' }}
                </h5>


                <!-- 🛡️ Add / Request Button Logic -->
                <div class="d-flex gap-2">
                    <!-- 1. Direct Add Button (Status: Approved) -->
                    <button class="btn btn-primary btn-sm shadow-sm secured-item"
                        data-permission="{{ $prefix }}add_direct" onclick="openAddModal()">
                        <i class="fas fa-plus-circle me-1"></i> New Voucher
                    </button>

                    <!-- 2. Request Add Button (Status: Pending) -->
                    <button class="btn btn-warning btn-sm shadow-sm secured-item"
                        data-permission="{{ $prefix }}add_request" onclick="openAddModal()">
                        <i class="fas fa-paper-plane me-1"></i> Request Voucher
                    </button>
                </div>
            </div>

            <div class="row align-items-center mb-3 bg-white p-2 rounded shadow-sm">
                <!-- Date Filters -->
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Start Date</label>
                    <input type="date" id="filter_start_date" class="form-control form-control-sm border-primary">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">End Date</label>
                    <input type="date" id="filter_end_date" class="form-control form-control-sm border-primary">
                </div>
                <div class="col-md-2 mt-4">
                    <button class="btn btn-sm btn-primary w-100 fw-bold" onclick="table.ajax.reload()"><i
                            class="fas fa-filter"></i> Apply</button>
                </div>

                <!-- 🛡️ Export Button Logic -->
                <div class="col-md-4 mt-4 text-end">
                    <button class="btn btn-success btn-sm shadow-sm fw-bold secured-item"
                        data-permission="{{ $prefix }}export" id="btnExportExcel">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </div>
            </div>



            <div class="card border-0 shadow-sm d-none d-md-block">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="dvDataTable" style="width: 100%;">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">DV No</th>
                                    <th>Date</th>
                                    <th>Head of Account</th>
                                    <th>Amount</th>
                                    <th>Mode</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3 d-md-none">
                <div class="card-body p-2 p-md-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-8 col-md-9">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i
                                        class="fas fa-search"></i></span>
                                <input type="text" id="customSearch" class="form-control border-start-0 ps-0"
                                    placeholder="Search DV No or Account...">
                            </div>
                        </div>
                        <div class="col-4 col-md-3 text-end">
                            <button class="btn btn-success btn-sm w-100 shadow-sm fw-bold" id="btnExportExcel">
                                <i class="fas fa-file-excel me-1"></i> <span class="d-none d-md-inline">Export
                                    Excel</span><span class="d-md-none">Excel</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-md-none" id="mobileCardsContainer">
                <div class="text-center py-5" id="mobileLoader">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted small">Loading vouchers...</p>
                </div>
            </div>
        </div>

        <div class="modal fade" id="voucherModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light border-bottom-0">
                        <h5 class="modal-title fw-bold text-primary" id="modalTitle"><i
                                class="fas fa-plus-circle me-2"></i>Add
                            Debit Voucher</h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <form id="voucherForm">
                        @csrf
                        <div class="modal-body p-4">
                            <input type="hidden" id="v_id" name="id">
                            <div class="row g-3">

                                <!-- 1. Select Company (NEW) -->
                                <div class="col-md-6">
                                    <label class="small fw-bold">Select Company *</label>
                                    <input list="companyList" class="form-control" id="m_company_id" name="company_id"
                                        placeholder="Type 3 letters to search company..." autocomplete="off" required>
                                    <datalist id="companyList"></datalist>
                                    <!-- Hidden input real ID store karne ke liye JS me use hoga -->
                                    <input type="hidden" id="hidden_company_id">
                                </div>

                                <!-- 2. Select Branch (Updated) -->
                                <div class="col-md-6">
                                    <label class="small fw-bold">Select Branch *</label>
                                    <input list="branchList" class="form-control" id="m_branch_id" name="branch_id"
                                        placeholder="Type to search branch..." autocomplete="off" disabled required>
                                    <datalist id="branchList"></datalist>
                                    <input type="hidden" id="hidden_branch_id">
                                </div>

                                <!-- 3. DV No (Updated Sequence) -->
                                <div class="col-md-4">
                                    <label class="small fw-bold">DV No *</label>
                                    <input type="text" class="form-control fw-bold text-primary" id="m_dv_no"
                                        name="dv_no" disabled>
                                    <small id="dv_no_error" class="text-danger fw-bold mt-1" style="display:none;"><i
                                            class="fas fa-times-circle"></i> Already Taken</small>
                                    <small id="dv_no_success" class="text-success fw-bold mt-1" style="display:none;"><i
                                            class="fas fa-check-circle"></i> Available</small>
                                </div>

                                <!-- 4. Date -->
                                <div class="col-md-4">
                                    <label class="small fw-bold">Date *</label>
                                    <input type="date" class="form-control" id="m_voucher_date" name="voucher_date"
                                        required disabled>
                                </div>

                                <!-- 5. Head of Account (Updated for Datalist) -->
                                <div class="col-md-4">
                                    <label class="small fw-bold">Head of Account *</label>
                                    <input list="ledgerList" class="form-control" id="m_head_of_account"
                                        name="head_of_account" placeholder="Search Account..." autocomplete="off"
                                        disabled required>
                                    <datalist id="ledgerList"></datalist>
                                </div>

                                <!-- 6. Paid To (Updated with Advance Button) -->
                                <div class="col-md-6">
                                    <label class="small fw-bold">Paid To *</label>
                                    <div class="input-group">
                                        <input list="paidToList" class="form-control" id="m_paid_to" name="paid_to"
                                            placeholder="Search Name..." autocomplete="off" disabled required>
                                        <button type="button" class="btn btn-info text-white fw-bold px-3 shadow-none"
                                            id="btnViewAdvance" style="display: none;" onclick="openAdvanceModal()"
                                            title="View Advance History">
                                            <i class="fas fa-wallet me-1"></i> Advance
                                        </button>
                                    </div>
                                    <datalist id="paidToList"></datalist>
                                </div>

                                <!-- 🔥 Salary Payment Details (Hidden By Default) -->
                                <div id="salaryPaymentSection"
                                    class="col-md-12 mb-3 bg-light p-3 rounded border border-warning"
                                    style="display: none;">
                                    <h6 class="fw-bold text-warning mb-2"><i
                                            class="fas fa-money-check-alt me-1"></i>Salary Payment Details</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="small fw-bold">Select Month *</label>
                                            <input type="month" class="form-control" id="m_salary_month"
                                                name="salary_month">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small fw-bold">Left Amount (₹)</label>
                                            <input type="text" class="form-control text-danger fw-bold"
                                                id="m_salary_left_amount" readonly placeholder="0.00">
                                            <input type="hidden" id="hidden_salary_id" name="salary_id">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small fw-bold">Payment Type *</label>
                                            <select class="form-select" id="m_salary_payment_type"
                                                name="salary_payment_type">
                                                <option value="none">None</option>
                                                <option value="part">Part Payment</option>
                                                <option value="full" selected>Full Payment</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="small fw-bold">Amount</label>
                                    <input type="number" class="form-control" id="m_amount" name="amount"
                                        oninput="convertToWords(this.value)">
                                </div>
                                <div class="col-md-8">
                                    <label class="small fw-bold">Amount in Words</label>
                                    <input type="text" class="form-control bg-light" id="m_amount_words"
                                        name="amount_words" readonly>
                                </div>

                                <div class="col-md-12">
                                    <label class="small fw-bold">Payment Mode</label>
                                    <select class="form-select border-primary fw-bold" id="m_payment_mode"
                                        name="payment_mode" onchange="togglePaymentFields()">
                                        <option value="Cash">Cash</option>
                                        <option value="Bank Transfer">Bank Transfer (NEFT/IMPS)</option>
                                        <option value="Cheque">Cheque</option>
                                        <option value="UPI">UPI</option>
                                    </select>
                                </div>

                                <div id="bankTransferSection" style="display: none;"
                                    class="row g-2 mt-2 bg-info-subtle p-3 rounded border border-info">
                                    <h6 class="fw-bold text-primary mb-1"><i class="fas fa-university"></i> Receiver's
                                        Bank Details</h6>
                                    <div class="col-md-6">
                                        <label class="small fw-bold">Bank Name</label>
                                        <input type="text" class="form-control bg-white" id="bt_bank_name"
                                            name="bank_name" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-bold">Account No.</label>
                                        <input type="text" class="form-control bg-white" id="bt_account_no"
                                            name="account_no" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small fw-bold">IFSC Code</label>
                                        <input type="text" class="form-control bg-white" id="bt_ifsc_code"
                                            name="ifsc_code" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small fw-bold">Branch</label>
                                        <!-- 🔥 FIX: name="bank_branch" add kiya taaki DB me save ho -->
                                        <input type="text" class="form-control bg-white" id="bt_branch"
                                            name="bank_branch" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small fw-bold">A/c Type</label>
                                        <input type="text" class="form-control bg-white" id="bt_account_type"
                                            readonly>
                                    </div>

                                    <hr class="my-2 border-info">

                                    <h6 class="fw-bold text-primary mb-1 mt-0"><i class="fas fa-exchange-alt"></i>
                                        Transaction Details</h6>
                                    <!-- 🔥 FIX: Sender's Bank input -->
                                    <div class="col-md-4">
                                        <label class="small fw-bold">Sender's Bank *</label>
                                        <input list="senderBankList" class="form-control" id="bt_sender_bank"
                                            name="sender_bank" placeholder="Type to search..." autocomplete="off">
                                        <datalist id="senderBankList"></datalist>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small fw-bold">Tr. Type</label>
                                        <select class="form-select" id="bt_type" name="type">
                                            <option value="NEFT">NEFT</option>
                                            <option value="IMPS">IMPS</option>
                                            <option value="RTGS">RTGS</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small fw-bold">Tr. ID / UTR</label>
                                        <input type="text" class="form-control" id="bt_transaction_id"
                                            name="transaction_id">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small fw-bold">Tr. Date</label>
                                        <input type="date" class="form-control" id="bt_bank_date" name="bank_date">
                                    </div>
                                </div>

                                <!-- 📱 UPI SECTION (NEW) -->
                                <div id="upiSection" style="display: none;"
                                    class="row g-2 mt-1 bg-success-subtle p-2 rounded border border-success">
                                    <h6 class="fw-bold text-success mb-1 mt-0"><i class="fas fa-mobile-alt"></i> UPI
                                        Details</h6>
                                    <div class="col-md-6">
                                        <label class="small fw-bold">UPI / UTR Number</label>
                                        <input type="text" class="form-control" id="upi_transaction_id"
                                            name="pay_upi">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-bold">Transaction Date</label>
                                        <input type="date" class="form-control" id="upi_bank_date" name="bank_date">
                                    </div>
                                </div>

                                <div id="chequeSection" style="display: none;"
                                    class="row g-2 mt-1 bg-light p-2 rounded border">
                                    <div class="col-md-6">
                                        <label class="small fw-bold">Bank Name</label>
                                        <input type="text" class="form-control" id="cq_bank_name" name="bank_name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-bold">Cheque Date</label>
                                        <input type="date" class="form-control" id="cq_bank_date" name="bank_date">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-bold">Cheque No</label>
                                        <input type="text" class="form-control" id="cq_transaction_id"
                                            name="transaction_id">
                                    </div>
                                </div>

                                <div class="col-12 mt-3">
                                    <label class="form-label small fw-bold">Narration / Detailed Description <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control" id="m_narration" name="narration" rows="3"
                                        placeholder="Write detailed description here (minimum 300 characters)..." minlength="300" required></textarea>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small class="text-muted" style="font-size: 11px;"><i
                                                class="fas fa-info-circle"></i>
                                            Minimum 300 characters are required for audit.</small>
                                        <small class="fw-bold" style="font-size: 11px;">
                                            <span id="char_count" class="text-danger">0</span> <span class="text-muted">/
                                                300
                                                min</span>
                                        </small>
                                    </div>
                                </div>

                                <!-- 🔥 UPDATE: Added Dynamic Approver & Signatory fields -->
                                <div class="col-md-6 secured-item" data-permission="{{ $prefix }}appr_by">
                                    <label class="small fw-bold">Approved By *</label>
                                    <select class="form-select" id="m_approved_by" name="approved_by" required>
                                        <option value="">Select Approver...</option>
                                    </select>
                                </div>
                                <div class="col-md-6 secured-item" data-permission="{{ $prefix }}auth_sign">
                                    <label class="small fw-bold">Authorized Signatory *</label>
                                    <select class="form-select" id="m_authorized_signatory" name="authorized_signatory"
                                        required>
                                        <option value="">Select Signatory...</option>
                                    </select>
                                </div>

                            </div>
                        </div>
                        <div class="modal-footer bg-light border-top-0">
                            <button type="button" class="btn btn-secondary fw-bold"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary fw-bold" id="saveBtn">Save Voucher</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 💰 Advance History Modal -->
        <div class="modal fade" id="advanceHistoryModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-info text-white border-bottom-0">
                        <h5 class="modal-title fw-bold"><i class="fas fa-wallet me-2"></i>Salary Advance History</h5>
                        <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 bg-light">
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <h6 class="text-muted small mb-1">Total Taken</h6>
                                <h5 class="fw-bold text-dark" id="advTotal">₹0</h5>
                            </div>
                            <div class="col-4 border-start border-end">
                                <h6 class="text-muted small mb-1">Repaid</h6>
                                <h5 class="fw-bold text-success" id="advRepaid">₹0</h5>
                            </div>
                            <div class="col-4">
                                <h6 class="text-muted small mb-1">Remaining</h6>
                                <h5 class="fw-bold text-danger" id="advRemaining" data-val="0">₹0</h5>
                            </div>
                        </div>

                        <div class="card border-info mb-3">
                            <div class="card-body p-2">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-1">
                                    <span class="small fw-bold text-muted">Advance Taking Today:</span>
                                    <span class="fw-bold text-primary" id="advToday">₹0</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small fw-bold text-muted">Projected Total Remaining:</span>
                                    <span class="fw-bold text-danger" id="advProjected">₹0</span>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-bold text-secondary mb-2"><i class="fas fa-history me-1"></i>Repayment History
                            (Deductions)</h6>
                        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm table-bordered table-striped align-middle mb-0"
                                style="font-size: 12px;">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>Month</th>
                                        <th>Deducted On</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="advHistoryTable">
                                    <!-- Dynamic rows aayengi -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
    @push('scripts')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">

        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            // Blade Variables for Context & Scoping
            const prefix = "{{ $prefix ?? 'dv_' }}";
            const pageSource = "{{ $source ?? 'index' }}";
            let currentPortal = window.location.pathname.split('/')[1] || 'admin';

            // 🔥 USER CONTEXT VARIABLES
            let u_company_id = "";
            let u_company_name = "";
            let u_branch_id = "";
            let u_branch_name = "";
            let u_is_executive = false;
            let u_is_director = false;

            // 🛡️ RBAC Helper
            function hasPerm(action) {
                let permClass = prefix + action;
                return true;
            }

            // 🕒 DEBOUNCE FUNCTION
            function debounce(func, delay = 600) {
                let timer;
                return function(...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => {
                        func.apply(this, args);
                    }, delay);
                };
            }

            let table;

            // Anti Piracy 
            document.addEventListener('contextmenu', event => event.preventDefault());
            document.addEventListener('keyup', (e) => {
                if (e.key == 'PrintScreen') {
                    navigator.clipboard.writeText('');
                    Swal.fire('Security Alert', 'Screenshots are disabled on this portal for security reasons.',
                        'error');
                }
            });

            $(document).ready(function() {
                let token = localStorage.getItem('token') || sessionStorage.getItem('token');
                let u_member_id = "";

                // 🔥 Fetch User Info & Signatory Hierarchies
                $.ajax({
                    url: `/api/v1/${currentPortal}/auth/me`,
                    type: 'GET',
                    headers: token ? {
                        'Authorization': 'Bearer ' + token
                    } : {},
                    success: function(res) {
                        if (res && res.data) {
                            let u = res.data;
                            u_company_id = u.company_id ? u.company_id.toString() : "1";
                            u_company_name = u.company_name || "AMITABH BUILDERS & DEVELOPERS PVT.LTD.";
                            u_branch_id = u.branch_id ? u.branch_id.toString() : "";
                            u_branch_name = u.branch_name || "Head Office";
                            u_member_id = u.member_id || u.id.toString();

                            let email = u.email || "";
                            let designation = (u.designation_name || "").toLowerCase();

                            // Strict Executive Check
                            u_is_executive = ['admin@jankivilla.com', 'superadmin@example.com',
                                    'vedprakash@infoera.in'
                                ].includes(email) ||
                                designation.includes('ceo') ||
                                designation.includes('super admin');
                            u_is_director = designation.includes('director');

                            // 🔥 NAYA: Dynamic Hierarchy Fetch based on logged-in user
                            $.ajax({
                                url: '/api/v1/signatory-master/hierarchies?module=debit_voucher',
                                type: 'GET',
                                headers: token ? {
                                    'Authorization': 'Bearer ' + token
                                } : {},
                                success: function(hierarchyRes) {
                                    let apprOptions =
                                        '<option value="">Select Approver...</option>';
                                    let authOptions =
                                        '<option value="">Select Signatory...</option>';

                                    hierarchyRes.data.forEach(m => {
                                        // Base person must be the logged-in user to show options
                                        if (m.base_person_id === u_member_id) {
                                            if (m.target_role === 'approved_by') {
                                                apprOptions +=
                                                    `<option value="${m.target_person_id}">${m.target_person_name} (${m.target_person_id})</option>`;
                                            } else if (m.target_role ===
                                                'authorized_signatory') {
                                                authOptions +=
                                                    `<option value="${m.target_person_id}">${m.target_person_name} (${m.target_person_id})</option>`;
                                            }
                                        }
                                    });

                                    $('#m_approved_by').html(apprOptions);
                                    $('#m_authorized_signatory').html(authOptions);
                                }
                            });
                        }
                    }
                });

                // DataTable Initialization with Scoping & RBAC Buttons
                table = $('#dvDataTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '/api/v1/debit_vouchers',
                        type: 'GET',
                        data: function(d) {
                            d.source = pageSource;
                            d.start_date = $('#filter_start_date').val();
                            d.end_date = $('#filter_end_date').val();
                        }
                    },
                    columns: [{
                            data: 'dv_no',
                            className: 'ps-3 fw-bold text-primary'
                        },
                        {
                            data: 'voucher_date'
                        },
                        {
                            data: 'head_of_account'
                        },
                        {
                            data: 'amount',
                            render: $.fn.dataTable.render.number(',', '.', 2, '₹')
                        },
                        {
                            data: 'payment_mode'
                        },
                        {
                            data: 'status',
                            render: function(d) {
                                if (!d) return '';
                                let statusStr = d.toLowerCase();
                                if (statusStr === 'approved')
                                return `<span class="badge bg-success-subtle text-success border border-success"><i class="fas fa-check-circle"></i> Approved</span>`;
                                if (statusStr === 'rejected')
                                return `<span class="badge bg-danger-subtle text-danger border border-danger"><i class="fas fa-times-circle"></i> Rejected</span>`;
                                if (statusStr === 'cancelled')
                                return `<span class="badge bg-secondary-subtle text-secondary border border-secondary"><i class="fas fa-ban"></i> Cancelled</span>`;
                                return `<span class="badge bg-warning-subtle text-warning border border-warning"><i class="fas fa-clock"></i> Pending</span>`;
                            }
                        },
                        {
                            data: 'id',
                            orderable: false,
                            searchable: false,
                            className: 'text-center text-nowrap',
                            render: function(d, type, row) {
                                let buttons = `<div class="d-flex justify-content-center gap-1">`;
                                let statusStr = row.status ? row.status.toLowerCase() : '';
                                let isDeleted = row.deleted_at !== null;

                                // View & Print
                                buttons +=
                                    `<a href="/${currentPortal}/debit_vouchers/print/${d}?mode=view" target="_blank" class="btn btn-sm btn-light border text-info" title="View"><i class="fas fa-eye"></i></a>`;
                                if (hasPerm('print')) buttons +=
                                    `<a href="/${currentPortal}/debit_vouchers/print/${d}?mode=print" target="_blank" class="btn btn-sm btn-light border text-dark" title="Print"><i class="fas fa-print"></i></a>`;

                                if (isDeleted) {
                                    // Restore
                                    if (hasPerm('restore')) buttons +=
                                        `<button onclick="actionVoucher(${d}, 'restore')" class="btn btn-sm btn-light border text-success" title="Restore"><i class="fas fa-undo"></i></button>`;
                                } else {
                                    // Edit
                                    if (hasPerm('edit')) buttons +=
                                        `<button onclick="editVoucher(${d})" class="btn btn-sm btn-light border text-primary" title="Edit"><i class="fas fa-edit"></i></button>`;

                                    // Cancel
                                    if (statusStr !== 'rejected' && statusStr !== 'cancelled' &&
                                        hasPerm('cancel')) {
                                        buttons +=
                                            `<button onclick="actionVoucher(${d}, 'cancel')" class="btn btn-sm btn-light border text-warning" title="Cancel"><i class="fas fa-ban"></i></button>`;
                                    }

                                    // Delete
                                    if (hasPerm('delete')) buttons +=
                                        `<button onclick="actionVoucher(${d}, 'delete')" class="btn btn-sm btn-light border text-danger" title="Delete"><i class="fas fa-trash"></i></button>`;
                                }
                                buttons += `</div>`;
                                return buttons;
                            }
                        }
                    ],
                    dom: "<'row align-items-center mb-2'<'col-md-6'l><'col-md-6'f>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row align-items-center mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    drawCallback: function(settings) {
                        renderMobileCards(settings.json.data);
                        if (typeof window.applyPermissions === 'function') window.applyPermissions();
                    }
                });

                // -------------------------------------------------------------
                // 🔥 DYNAMIC 3-LETTER SEARCH & CASCADING LOGIC
                // -------------------------------------------------------------

                // 1. Company Search
                $('#m_company_id').on('keyup', debounce(function() {
                    let q = $(this).val();
                    if (q.length >= 3) {
                        $.get(`/api/v1/debit_vouchers/search-companies?q=${q}`, function(res) {
                            let options = '';
                            res.data.forEach(c => {
                                options +=
                                    `<option value="${c.company_name}" data-id="${c.id}">`;
                            });
                            $('#companyList').html(options);
                        });
                    }
                }));

                $('#m_company_id').on('change', function() {
                    let val = $(this).val();
                    let selected = $(`#companyList option[value="${val}"]`);
                    if (selected.length > 0) {
                        $('#hidden_company_id').val(selected.data('id'));
                        $('#m_branch_id').prop('disabled', false).val('');
                    } else {
                        $('#m_branch_id, #m_dv_no, #m_voucher_date, #m_head_of_account, #m_paid_to').prop(
                            'disabled', true);
                    }
                });

                // 2. Branch Search
                $('#m_branch_id').on('keyup', debounce(function() {
                    let q = $(this).val();
                    let compId = $('#hidden_company_id').val();
                    if (q.length >= 3 && compId) {
                        $.get(`/api/v1/debit_vouchers/search-branches?q=${q}&company_id=${compId}`,
                            function(res) {
                                let options = '<option value="Head Office" data-id="HO">';
                                res.data.forEach(b => {
                                    options +=
                                        `<option value="${b.branch_name}" data-id="${b.id}">`;
                                });
                                $('#branchList').html(options);
                            });
                    }
                }));

                $('#m_branch_id').on('change', function() {
                    let val = $(this).val();
                    let selected = $(`#branchList option[value="${val}"]`);
                    if (selected.length > 0) {
                        $('#hidden_branch_id').val(selected.data('id'));
                        $('#m_dv_no, #m_voucher_date, #m_head_of_account, #m_paid_to').prop('disabled', false);
                        fetchNextDvNo();
                    }
                });

                // 3. Ledger Search
                $('#m_head_of_account').on('keyup', debounce(function() {
                    let q = $(this).val();
                    let branchId = $('#hidden_branch_id').val();
                    let companyId = $('#hidden_company_id').val(); // 🔥 NAYA: Company ID bhi pass hoga
                    if (q.length >= 3) {
                        // API Call me company_id add kar diya gaya hai
                        $.get(`/api/v1/debit_vouchers/search-ledgers?q=${q}&branch_id=${branchId}&company_id=${companyId}`,
                            function(res) {
                                let options = '';
                                res.data.forEach(l => {
                                    options +=
                                        `<option value="${l.ledger_name} (${l.ledger_code})">`;
                                });
                                $('#ledgerList').html(options);
                            });
                    }
                }));

                // 4. Paid To Search
                $('#m_paid_to').on('keyup', debounce(function() {
                    let q = $(this).val();
                    if (q.length >= 3) {
                        $.get(`/api/v1/debit_vouchers/search-paid-to?q=${q}`, function(res) {
                            let options = '';
                            res.data.forEach(p => {
                                options +=
                                    `<option value="${p.name} - ${p.id} [${p.type}]">`;
                            });
                            $('#paidToList').html(options);
                        });
                    }
                }));

                // 5. Sender Bank Search (1 letter type karte hi)
                $('#bt_drawn_on').on('keyup', debounce(function() {
                    let q = $(this).val();
                    if (q.length >= 1) { // 1 letter par trigger hoga
                        $.ajax({
                            url: `/api/v1/get-sender-bank?q=${q}`,
                            type: 'GET',
                            success: function(res) {
                                if (res.status === 'success') {
                                    let options = '';
                                    res.data.forEach(item => {
                                        options +=
                                            `<option value="${item.display_name}" data-acc="${item.full_account_no}">`;
                                    });
                                    $('#senderBankList').html(options);
                                } else {
                                    $('#senderBankList').html('');
                                }
                            }
                        });
                    }
                }, 300));

                // Narration Character Count
                $('#m_narration').on('input', function() {
                    let currentLength = $(this).val().length;
                    $('#char_count').text(currentLength);
                    if (currentLength < 300) {
                        $('#char_count').removeClass('text-success').addClass('text-danger');
                    } else {
                        $('#char_count').removeClass('text-danger').addClass('text-success');
                    }
                });

                // ==========================================
                // 🔥 DYNAMIC SALARY ADVANCE LOGIC
                // ==========================================

                // ==========================================
                // 🔥 DYNAMIC SALARY PAYMENT LOGIC
                // ==========================================

                // Ledger ya Employee change hone par condition check karo
                $('#m_head_of_account, #m_paid_to').on('input change', function() {
                    let ledger = $('#m_head_of_account').val() || '';
                    let paidTo = $('#m_paid_to').val() || '';

                    // Condition: Ledger "ABDPL-LED/063" (SALARY ACCOUNT) hona chahiye aur Paid To "[employee]" hona chahiye
                    if (ledger.includes('ABDPL-LED/063') && paidTo.includes('[employee]')) {
                        $('#salaryPaymentSection').fadeIn();
                    } else {
                        $('#salaryPaymentSection').fadeOut();
                        $('#m_salary_month, #m_salary_left_amount, #hidden_salary_id').val('');
                        $('#m_salary_payment_type').val('full');
                    }
                });

                // "Month" select hone par API call marna
                $('#m_salary_month').on('change', function() {
                    let month = $(this).val();
                    let paidTo = $('#m_paid_to').val() || '';
                    let token = localStorage.getItem('token') || sessionStorage.getItem('token');

                    if (month && paidTo.includes('[employee]')) {
                        $('#m_salary_left_amount').val('Loading...');

                        $.ajax({
                            url: '/api/v1/debit_vouchers/get-salary-details',
                            type: 'GET',
                            data: {
                                month: month,
                                employee: paidTo
                            },
                            headers: token ? {
                                'Authorization': 'Bearer ' + token
                            } : {},
                            success: function(res) {
                                if (res.status === 'success') {
                                    $('#m_salary_left_amount').val(res.data.left_amount);
                                    $('#hidden_salary_id').val(res.data.salary_id);
                                } else {
                                    $('#m_salary_left_amount').val('0.00');
                                    $('#hidden_salary_id').val('');
                                    alert(res.message);
                                }
                            },
                            error: function() {
                                $('#m_salary_left_amount').val('0.00');
                                alert('Error fetching salary details.');
                            }
                        });
                    }
                });

                // "Amount" change hone par Modal ki Projected Value automatically update ho
                $('#m_amount').on('input', function() {
                    let amount = parseFloat($(this).val()) || 0;
                    let remaining = parseFloat($('#advRemaining').attr('data-val')) || 0;

                    $('#advToday').text('₹' + amount.toFixed(2));
                    $('#advProjected').text('₹' + (remaining + amount).toFixed(2));
                });

                // Form Submit Logic
                $('#voucherForm').off('submit').on('submit', function(e) {
                    e.preventDefault();
                    let narrationText = $('#m_narration').val().trim();
                    if (narrationText.length < 300) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Narration Too Short',
                            text: `Minimum 300 characters are required.`
                        });
                        return;
                    }

                    // if ($('#m_payment_mode').val() === 'Bank Transfer') {
                    //     let displayVal = $('#bt_drawn_on').val();
                    //     let selectedOption = $(`#senderBankList option[value="${displayVal}"]`);
                    //     if (selectedOption.length > 0) $('#bt_drawn_on').val(selectedOption.attr('data-acc'));
                    // }

                    let id = $('#v_id').val();
                    let url = id ? `/api/v1/debit_vouchers/${id}` : '/api/v1/debit_vouchers';
                    let method = id ? 'PUT' : 'POST';

                    // Add hidden fields dynamically to formData
                    let formData = $(this).serializeArray();
                    formData.push({
                        name: 'company_id',
                        value: $('#hidden_company_id').val()
                    });
                    formData.push({
                        name: 'branch_id',
                        value: $('#hidden_branch_id').val()
                    });

                    let btn = $('#saveBtn');
                    btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm"></span> Saving...');

                    $.ajax({
                        url: url,
                        type: method,
                        data: $.param(formData),
                        success: function(res) {
                            $('#voucherModal').modal('hide');
                            Swal.fire('Success', res.message, 'success');
                            table.ajax.reload(null, false);
                        },
                        error: function(err) {
                            Swal.fire('Error', err.responseJSON?.message || 'Something went wrong',
                                'error');
                        },
                        complete: function() {
                            btn.prop('disabled', false).html('Save Voucher');
                        }
                    });
                });

                // Manual typing par DV No check karne ke liye
                $('#m_dv_no').on('keyup change', debounce(function() {
                    checkDvNo();
                }, 300));


            });


            // API Call for Advance History Modal
            function openAdvanceModal() {
                let paidTo = $('#m_paid_to').val();
                let todayAmount = parseFloat($('#m_amount').val()) || 0;

                // Loading state setup
                $('#advHistoryTable').html(
                    '<tr><td colspan="3" class="text-center"><span class="spinner-border spinner-border-sm text-info"></span> Loading history...</td></tr>'
                    );
                $('#advanceHistoryModal').modal('show');

                $.ajax({
                    url: '/api/v1/debit_vouchers/get-advance-history?q=' + encodeURIComponent(paidTo),
                    type: 'GET',
                    success: function(res) {
                        if (res.status === 'success') {
                            let d = res.data;
                            let remaining = parseFloat(d.remaining_amount) || 0;

                            $('#advTotal').text('₹' + (d.total_amount || 0));
                            $('#advRepaid').text('₹' + (d.paid_amount || 0));
                            $('#advRemaining').text('₹' + remaining).attr('data-val', remaining);

                            $('#advToday').text('₹' + todayAmount);
                            $('#advProjected').text('₹' + (remaining + todayAmount));

                            if (d.repayments && d.repayments.length > 0) {
                                let rows = '';
                                d.repayments.forEach(r => {
                                    rows += `<tr>
                                    <td><span class="badge bg-secondary">${r.month}</span></td>
                                    <td>${r.date}</td>
                                    <td class="text-end text-success fw-bold">₹${parseFloat(r.amount).toFixed(2)}</td>
                                </tr>`;
                                });
                                $('#advHistoryTable').html(rows);
                            } else {
                                $('#advHistoryTable').html(
                                    '<tr><td colspan="3" class="text-center text-muted"><i class="fas fa-info-circle"></i> No repayment history found.</td></tr>'
                                    );
                            }
                        }
                    }
                });
            }


            // 🔥 SMART OPEN MODAL LOGIC (With Strict Hierarchical Locking Rules)
            function openAddModal() {
                $('#voucherForm')[0].reset();
                $('#v_id').val('');
                $('#hidden_company_id, #hidden_branch_id').val('');

                $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add Debit Voucher');
                $('#m_voucher_date').val(new Date().toISOString().split('T')[0]);
                $('#char_count').text('0').removeClass('text-success').addClass('text-danger');
                $('#bankTransferSection, #chequeSection, #upiSection').hide();
                $('#dv_no_error, #dv_no_success').hide();

                // 1. Sabse pehle saare fields disable kardo
                $('#m_branch_id, #m_dv_no, #m_voucher_date, #m_head_of_account, #m_paid_to').prop('disabled', true);
                $('#m_company_id').prop('disabled', false).val('');

                // 2. Variables for Logic
                // String 'null', '0', ya empty ko false mark karega
                let hasBranch = (u_branch_id !== '' && u_branch_id !== 'null' && u_branch_id !== null && u_branch_id !== '0');
                let isMasterCompany = (u_company_id === '1');

                // 🛠️ STRICT RULES IMPLEMENTATION

                if (hasBranch) {
                    // ✅ RULE 2 & 4: Employee kisi bhi company (Master ya Child) ke SPECIFIC BRANCH (e.g. Branch ID 8) se hai.
                    // Company aur Branch dono auto-fill hokar autolock ho jayenge!
                    $('#m_company_id').val(u_company_name).prop('disabled', true);
                    $('#hidden_company_id').val(u_company_id);

                    $('#m_branch_id').val(u_branch_name).prop('disabled', true);
                    $('#hidden_branch_id').val(u_branch_id);

                    // Fields open karo aur DV No generate karo
                    $('#m_dv_no, #m_voucher_date, #m_head_of_account, #m_paid_to').prop('disabled', false);
                    fetchNextDvNo();
                } else if (isMasterCompany || u_is_executive) {
                    // ✅ RULE 1: Admin, CEO, Super Admin YA (Parent Company + Head Office/null branch)
                    // Company select karne ke liye OPEN rahega. Branch company select hone par khulega.
                    // Koi Auto-lock nahi hoga.
                } else {
                    // ✅ RULE 3: Child Company + Head Office/null branch (Same for Directors of child company)
                    // Company auto-fill hokar autolock rahegi, Branch search & select ke liye OPEN rahega.
                    $('#m_company_id').val(u_company_name).prop('disabled', true);
                    $('#hidden_company_id').val(u_company_id);

                    $('#m_branch_id').prop('disabled', false).val('');
                }

                $('#voucherModal').modal('show');
            }

            // Fetch Next DV No Logic
            // 🟢 FIX 3: Fetch Next DV No Logic (Now specific to Company + Branch)
            function fetchNextDvNo() {
                let compId = $('#hidden_company_id').val();
                let branchId = $('#hidden_branch_id').val() || 'HO';

                // Agar company select nahi hai to request mat bhejo
                if (!compId) return;

                $.ajax({
                    // URL me company aur branch parameters bhejein
                    url: `/api/v1/get-next-dv-no?company_id=${compId}&branch_id=${branchId}`,
                    type: 'GET',
                    success: function(res) {
                        $('#m_dv_no').val(res.next_dv);
                        checkDvNo(); // Generate hone ke baad check bhi karega
                    }
                });
            }

            // Validate DV No for specific company and branch
            // 🟢 FIX: Exclude ID pass karna
            function checkDvNo() {
                let dvNo = $('#m_dv_no').val();
                let compId = $('#hidden_company_id').val();
                let branchId = $('#hidden_branch_id').val() || 'HO';
                let v_id = $('#v_id').val(); // Edit mode ke liye current id

                $('#dv_no_error, #dv_no_success').hide();

                if (dvNo && dvNo.length > 0 && compId) {
                    $.ajax({
                        // URL me exclude_id add kiya gaya hai
                        url: `/api/v1/check-dv-no?dv_no=${dvNo}&company_id=${compId}&branch_id=${branchId}&exclude_id=${v_id}`,
                        type: 'GET',
                        success: function(res) {
                            if (res.exists) {
                                $('#dv_no_error').show();
                                $('#saveBtn').prop('disabled', true);
                            } else {
                                $('#dv_no_success').show();
                                $('#saveBtn').prop('disabled', false);
                            }
                        }
                    });
                }
            }

            // 🟢 FIX 1: EDIT AUTO-FILL LOGIC
            function editVoucher(id) {
                $.ajax({
                    url: `/api/v1/debit_vouchers/${id}`,
                    type: 'GET',
                    success: function(res) {
                        let data = res.data;
                        $('#v_id').val(data.id);
                        $('#m_dv_no').val(data.dv_no);
                        $('#m_voucher_date').val(data.voucher_date);

                        $('#hidden_company_id').val(data.company_id);
                        $('#hidden_branch_id').val(data.branch_id || 'HO');

                        // Company aur Branch automatically bharega kyunki backend relation bhej raha hai
                        $('#m_company_id').val(data.company ? data.company.company_name : '');
                        $('#m_branch_id').prop('disabled', false).val(data.branch ? data.branch.branch_name :
                            'Head Office');
                        $('#m_dv_no, #m_voucher_date, #m_head_of_account, #m_paid_to').prop('disabled', false);

                        $('#m_head_of_account').val(data.head_of_account);
                        $('#m_paid_to').val(data.paid_to);
                        $('#m_amount').val(data.amount);
                        $('#m_project_name').val(data.project_name || 'Janki Villa');
                        $('#m_payment_mode').val(data.payment_mode);
                        $('#m_approved_by').val(data.approved_by); // Fill mapped Approved By
                        $('#m_authorized_signatory').val(data
                        .authorized_signatory); // Fill mapped Authorized Signatory

                        // Bank & Transaction details auto-fill
                        $('#bt_bank_name').val(data.bank_name);
                        $('#bt_account_no').val(data.account_no);
                        $('#bt_ifsc_code').val(data.ifsc_code);
                        $('#bt_branch').val(data.bank_branch); // Receiver Branch auto-fill

                        if (data.payment_mode === 'Bank Transfer') {
                            $('#bt_sender_bank').val(data.sender_bank);
                            $('#bt_type').val(data.type);
                            $('#bt_transaction_id').val(data.transaction_id);
                            $('#bt_bank_date').val(data.bank_date);
                        } else if (data.payment_mode === 'UPI') {
                            $('#upi_transaction_id').val(data.pay_upi);
                            $('#upi_bank_date').val(data.bank_date);
                        } else if (data.payment_mode === 'Cheque') {
                            $('#cq_bank_name').val(data.bank_name);
                            $('#cq_bank_date').val(data.bank_date);
                            $('#cq_transaction_id').val(data.transaction_id);
                        }

                        $('#m_narration').val(data.narration).trigger('input');
                        togglePaymentFields();
                        convertToWords(data.amount);

                        $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Debit Voucher');
                        $('#voucherModal').modal('show');
                    }
                });
            }

            // 🟢 FIX 2: SENDER'S BANK SEARCH EVENT
            $('#bt_sender_bank').on('keyup', debounce(function() {
                let q = $(this).val();
                if (q.length >= 1) {
                    $.ajax({
                        url: `/api/v1/get-sender-bank?q=${q}`,
                        type: 'GET',
                        success: function(res) {
                            if (res.status === 'success') {
                                let options = '';
                                res.data.forEach(item => {
                                    // Value sirf "Bank Name (ID)" show/save hogi
                                    options += `<option value="${item.display_name}">`;
                                });
                                $('#senderBankList').html(options);
                            } else {
                                $('#senderBankList').html('');
                            }
                        }
                    });
                }
            }, 300));

            // 🔥 MASTER ACTION FUNCTION (Cancel, Restore, Delete)
            function actionVoucher(id, actionType) {
                let textMap = {
                    'cancel': 'cancel this voucher?',
                    'restore': 'restore this deleted voucher?',
                    'delete': 'delete this voucher?'
                };

                let method = actionType === 'delete' ? 'DELETE' : 'POST';
                let url = actionType === 'delete' ? `/api/v1/debit_vouchers/${id}` :
                    `/api/v1/debit_vouchers/${id}/${actionType}`;

                Swal.fire({
                    title: 'Are you sure?',
                    text: `Do you want to ${textMap[actionType]}`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: `Yes, ${actionType} it!`
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            type: method,
                            success: function(res) {
                                Swal.fire('Success!', res.message, 'success');
                                table.ajax.reload(null, false);
                            },
                            error: function(err) {
                                Swal.fire('Error', err.responseJSON?.message || 'Action failed', 'error');
                            }
                        });
                    }
                });
            }

            // Mobile Cards Mapping
            function renderMobileCards(data) {
                $('#mobileLoader').hide();
                let html = '';
                if (!data || data.length === 0) {
                    html = '<div class="text-center p-4 bg-white rounded shadow-sm">No vouchers found.</div>';
                } else {
                    data.forEach(row => {
                        let statusStr = row.status ? row.status.toLowerCase() : '';
                        let isDeleted = row.deleted_at !== null;
                        let statusBadge = '';

                        if (statusStr === 'approved') statusBadge =
                            `<span class="badge bg-success-subtle text-success border border-success"><i class="fas fa-check-circle"></i> Approved</span>`;
                        else if (statusStr === 'rejected') statusBadge =
                            `<span class="badge bg-danger-subtle text-danger border border-danger"><i class="fas fa-times-circle"></i> Rejected</span>`;
                        else if (statusStr === 'cancelled') statusBadge =
                            `<span class="badge bg-secondary-subtle text-secondary border border-secondary"><i class="fas fa-ban"></i> Cancelled</span>`;
                        else statusBadge =
                            `<span class="badge bg-warning-subtle text-warning border border-warning"><i class="fas fa-clock"></i> Pending</span>`;

                        let buttons = `<div class="d-flex gap-1">`;
                        buttons +=
                            `<a href="/${currentPortal}/debit_vouchers/print/${row.id}?mode=view" target="_blank" class="btn btn-sm btn-light border text-info"><i class="fas fa-eye"></i></a>`;
                        if (hasPerm('print')) buttons +=
                            `<a href="/${currentPortal}/debit_vouchers/print/${row.id}?mode=print" target="_blank" class="btn btn-sm btn-light border text-dark"><i class="fas fa-print"></i></a>`;

                        if (isDeleted) {
                            if (hasPerm('restore')) buttons +=
                                `<button onclick="actionVoucher(${row.id}, 'restore')" class="btn btn-sm btn-light border text-success"><i class="fas fa-undo"></i></button>`;
                        } else {
                            if (hasPerm('edit')) buttons +=
                                `<button onclick="editVoucher(${row.id})" class="btn btn-sm btn-light border text-primary"><i class="fas fa-edit"></i></button>`;

                            if (statusStr !== 'rejected' && statusStr !== 'cancelled' && hasPerm('cancel')) {
                                buttons +=
                                    `<button onclick="actionVoucher(${row.id}, 'cancel')" class="btn btn-sm btn-light border text-warning"><i class="fas fa-ban"></i></button>`;
                            }
                            if (hasPerm('delete')) buttons +=
                                `<button onclick="actionVoucher(${row.id}, 'delete')" class="btn btn-sm btn-light border text-danger"><i class="fas fa-trash"></i></button>`;
                        }
                        buttons += `</div>`;

                        html += `
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-1">DV NO. - ${row.dv_no}</span>
                                    <h6 class="fw-bold mb-0">${row.head_of_account}</h6>
                                </div>
                                <div class="text-end">
                                    <h6 class="fw-bold text-dark mb-0">₹${row.amount || 0}</h6>
                                    <small class="badge bg-light text-secondary border">${row.payment_mode}</small>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <small class="text-muted"><i class="far fa-calendar-alt me-1"></i> ${row.voucher_date} <br> ${statusBadge}</small>
                                ${buttons}
                            </div>
                        </div>
                    </div>`;
                    });
                }
                $('#mobileCardsContainer').html(html);
            }

            // Baki Search aur Utility Functions Same hai
            $('#customSearch').on('keyup', function() {
                table.search(this.value).draw();
            });
            $('#btnExportExcel').on('click', function() {
                table.button('.buttons-excel').trigger();
            });

            $('#m_paid_to').on('input change', function() {
                let selectedText = $(this).val();
                let idMatch = selectedText.match(/-\s*(.*?)\s*\[/);
                if (idMatch && idMatch[1]) {
                    fetchReceiverBankDetails(idMatch[1].trim());
                } else {
                    clearBankDetails();
                }
            });

            function fetchReceiverBankDetails(memberId) {
                $.ajax({
                    url: `/api/v1/get-member-bank?member_id=${memberId}`,
                    type: 'GET',
                    success: function(res) {
                        if (res.status === 'success' && res.data) {
                            $('#bt_bank_name').val(res.data.bank_name);
                            $('#bt_account_no').val(res.data.account_no);
                            $('#bt_ifsc_code').val(res.data.ifsc_code);
                            $('#bt_branch').val(res.data.branch);
                            $('#bt_account_type').val(res.data.account_type);
                        } else {
                            clearBankDetails();
                        }
                    },
                    error: function() {
                        clearBankDetails();
                    }
                });
            }

            function clearBankDetails() {
                $('#bt_bank_name, #bt_account_no, #bt_ifsc_code, #bt_branch, #bt_account_type').val('');
            }

            function fetchSenderBank() {
                $.ajax({
                    url: '/api/v1/get-sender-bank',
                    type: 'GET',
                    success: function(res) {
                        if (res.status === 'success') {
                            let options = '';
                            res.data.forEach(item => {
                                options +=
                                    `<option value="${item.display_name}" data-acc="${item.full_account_no}">`;
                            });
                            $('#senderBankList').html(options);
                        }
                    }
                });
            }

            function togglePaymentFields() {
                let mode = $('#m_payment_mode').val();
                // Sabko hide aur disable karo pehle
                $('#bankTransferSection, #chequeSection, #upiSection').hide();
                $('#bankTransferSection input, #bankTransferSection select, #chequeSection input, #upiSection input').prop(
                    'disabled', true);

                if (mode === 'Bank Transfer') {
                    $('#bankTransferSection').show();
                    $('#bankTransferSection input, #bankTransferSection select').prop('disabled', false);
                    fetchSenderBank();
                } else if (mode === 'Cheque') {
                    $('#chequeSection').show();
                    $('#chequeSection input').prop('disabled', false);
                } else if (mode === 'UPI') {
                    $('#upiSection').show();
                    $('#upiSection input').prop('disabled', false);
                }
            }

            function convertToWords(amount) {
                if (!amount || amount == 0) {
                    $('#m_amount_words').val("");
                    return;
                }
                const a = ['', 'One ', 'Two ', 'Three ', 'Four ', 'Five ', 'Six ', 'Seven ', 'Eight ', 'Nine ', 'Ten ',
                    'Eleven ', 'Twelve ', 'Thirteen ', 'Fourteen ', 'Fifteen ', 'Sixteen ', 'Seventeen ', 'Eighteen ',
                    'Nineteen '
                ];
                const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
                const inWords = (num) => {
                    if ((num = num.toString()).length > 9) return 'overflow';
                    let n = ('000000000' + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
                    if (!n) return '';
                    let str = '';
                    str += (n[1] != 0) ? (a[Number(n[1])] || b[n[1][0]] + ' ' + a[n[1][1]]) + 'Crore ' : '';
                    str += (n[2] != 0) ? (a[Number(n[2])] || b[n[2][0]] + ' ' + a[n[2][1]]) + 'Lakh ' : '';
                    str += (n[3] != 0) ? (a[Number(n[3])] || b[n[3][0]] + ' ' + a[n[3][1]]) + 'Thousand ' : '';
                    str += (n[4] != 0) ? (a[Number(n[4])] || b[n[4][0]] + ' ' + a[n[4][1]]) + 'Hundred ' : '';
                    str += (n[5] != 0) ? ((str != '') ? 'and ' : '') + (a[Number(n[5])] || b[n[5][0]] + ' ' + a[n[5][1]]) :
                        '';
                    return str + 'Rupees Only';
                };
                $('#m_amount_words').val(inWords(Math.floor(amount)));
            }
        </script>
        <style>
            body {
                -webkit-user-select: none;
                -moz-user-select: none;
                -ms-user-select: none;
                user-select: none;
            }

            .dataTables_paginate .pagination .page-item.active .page-link {
                background-color: var(--brand-primary);
                border-color: var(--brand-primary);
            }

            .table thead th {
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #718096;
                padding: 12px 10px;
            }

            .table tbody td {
                font-size: 13.5px;
                color: #2D3748;
                padding: 12px 10px;
                border-bottom: 1px solid #F1F5F9;
            }

            div.dataTables_length select {
                width: 70px;
                display: inline-block;
            }
        </style>
    @endpush
