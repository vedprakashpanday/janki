@extends('layout.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        .badge-primary {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-secondary {
            background: #fef9c3;
            color: #854d0e;
        }

        @media (max-width: 767.98px) {
            .receipt-table thead {
                display: none;
            }

            .receipt-table tbody tr {
                display: block;
                margin-bottom: 15px;
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 10px;
            }

            .receipt-table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid #f1f5f9;
                padding: 8px 5px;
                text-align: right;
            }

            .receipt-table tbody td::before {
                content: attr(data-label);
                font-weight: bold;
                color: #475569;
                margin-right: 15px;
                text-align: left;
            }

            .receipt-table tbody td:last-child {
                border-bottom: none;
                justify-content: flex-end;
                gap: 5px;
            }
        }

        .select2-container {
            width: 100% !important;
            z-index: 99999 !important;
        }

        .select2-dropdown {
            z-index: 99999 !important;
        }

        .amount-words {
            font-size: 11px;
            color: #0d6efd;
            text-transform: capitalize;
            margin-top: 4px;
            display: block;
        }
    </style>

    <div class="container-fluid mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0 text-primary"><i class="fas fa-file-invoice-dollar me-2"></i> Temporary Receipts</h4>
            <button class="btn btn-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#receiptFormModal"
                onclick="resetFormState()">
                <i class="fas fa-plus me-1"></i> Create Receipt
            </button>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
            <div class="table-responsive">
                <table id="receiptsDataTable" class="table receipt-table w-100 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Receipt No.</th>
                            <th>Date</th>
                            <th>Customer Name</th>
                            <th>Property</th>
                            <th>Net Amount</th>
                            <th>Received</th>
                            <th>Balance</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Receipt Form Modal -->
    <div class="modal fade" id="receiptFormModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">Receipt Processing Engine</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <form id="receiptEntryForm" class="needs-validation" novalidate>

                        <!-- Company & Branch (DATALIST IMPLEMENTATION) -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body row g-3">
                                <div class="col-md-6 form-group">
                                    <label class="small text-muted fw-bold">Search Company *</label>
                                    <input list="companies-list" class="form-control d-company-search"
                                        placeholder="Type to search company..." required
                                        onchange="handleCompanySelection(this)">
                                    <datalist id="companies-list"></datalist>
                                    <input type="hidden" class="d-company" required> <!-- Hidden ID -->
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="small text-muted fw-bold">Search Branch *</label>
                                    <input list="branches-list" class="form-control d-branch-search"
                                        placeholder="Type to search branch..." required
                                        onchange="handleBranchSelection(this)">
                                    <datalist id="branches-list"></datalist>
                                    <input type="hidden" class="d-branch" required> <!-- Hidden ID -->
                                </div>
                            </div>
                        </div>

                        <!-- Basic Details -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body row g-3">
                                <div class="col-md-3"><label class="small text-muted fw-bold">Receipt Date *</label><input
                                        type="date" class="form-control d-date" required value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-md-3"><label class="small text-muted fw-bold">Receipt No. *</label><input
                                        type="text" class="form-control d-receipt-no" required></div>
                                <div class="col-md-3"><label class="small text-muted fw-bold">Project Name</label><input
                                        type="text" class="form-control d-project" value="Janki Villa" readonly></div>
                                <div class="col-md-3 form-group">
                                    <label class="small text-muted fw-bold">Select Phase</label>
                                    <select class="form-select select2 d-phase">
                                        <option value="">-- Select Phase --</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- CUSTOMER DETAILS (DATALIST & AUTO-FILL) -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white fw-bold text-primary"><i class="fas fa-user"></i> Customer
                                Details</div>
                            <div class="card-body row g-3">
                                <div class="col-md-12">
                                    <label class="small text-muted fw-bold">Search Existing Customer</label>
                                    <input list="customers-list" class="form-control d-cust-search"
                                        placeholder="Type Customer Name or ID to auto-fill details..."
                                        onchange="handleCustomerSelection(this)">
                                    <datalist id="customers-list"></datalist>
                                </div>
                                <div class="col-md-4"><label class="small text-muted fw-bold">Customer Name *</label><input
                                        type="text" class="form-control d-cust-name" required></div>
                                <div class="col-md-4"><label class="small text-muted fw-bold">Customer ID No.</label><input
                                        type="text" class="form-control d-cust-id-no"></div>
                                <div class="col-md-4"><label class="small text-muted fw-bold">Passbook No.</label><input
                                        type="text" class="form-control d-passbook"></div>

                                <div class="col-md-4"><label class="small text-muted fw-bold">Father's / Husband's
                                        Name</label><input type="text" class="form-control d-father-name"></div>
                                <div class="col-md-4"><label class="small text-muted fw-bold">Mobile No.</label><input
                                        type="text" class="form-control d-mobile"></div>
                                <div class="col-md-4"><label class="small text-muted fw-bold">Spouse Name</label><input
                                        type="text" class="form-control d-spouse-name"></div>
                                <div class="col-md-12"><label class="small text-muted fw-bold">Address</label><input
                                        type="text" class="form-control d-address"></div>
                            </div>
                        </div>

                        <!-- Property Details -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body row g-3">
                                <div class="col-md-4"><label class="small text-muted fw-bold">Property Type</label><select
                                        class="form-select d-prop-type">
                                        <option value="Plot">Plot</option>
                                        <option value="Villa">Villa</option>
                                        <option value="Flat">Flat</option>
                                    </select></div>
                                <div class="col-md-4"><label class="small text-muted fw-bold">Unit Number</label><input
                                        type="text" class="form-control d-unit-no"></div>
                                <div class="col-md-4"><label class="small text-muted fw-bold">Area (Sq.ft.)</label><input
                                        type="number" step="0.01" class="form-control d-area"></div>
                            </div>
                        </div>

                        <!-- Payment Mode -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body row g-3">
                                <div class="col-md-12"><label class="small text-muted fw-bold">Payment Mode
                                        *</label><select class="form-select d-pay-mode" required
                                        onchange="handlePaymentModeFields(this.value)">
                                        <option value="Cash">Cash</option>
                                        <option value="UPI">UPI</option>
                                        <option value="Cheque">Cheque</option>
                                        <option value="NEFT">NEFT</option>
                                        <option value="RTGS">RTGS</option>
                                        <option value="Other">Other</option>
                                    </select></div>

                                <div class="col-12 bg-white border p-3 rounded d-none mt-2"
                                    id="conditional-fields-container">
                                    <div class="row g-2 id-cheque-fields d-none">
                                        <div class="col-md-4"><label class="small fw-bold">Cheque No.</label><input
                                                type="text" class="form-control form-control-sm target-cheque-no">
                                        </div>
                                        <div class="col-md-4"><label class="small fw-bold">Received Bank
                                                Name</label><input type="text"
                                                class="form-control form-control-sm target-bank-name"></div>
                                        <div class="col-md-4"><label class="small fw-bold">Date of Cheque</label><input
                                                type="date" class="form-control form-control-sm target-cheque-date">
                                        </div>
                                    </div>
                                    <div class="row g-2 id-upi-fields d-none">
                                        <div class="col-md-6"><label class="small fw-bold">Transaction / UTR
                                                Number</label><input type="text"
                                                class="form-control form-control-sm target-utr-no"></div>
                                        <div class="col-md-6"><label class="small fw-bold">Transaction Date</label><input
                                                type="date" class="form-control form-control-sm target-txn-date"></div>
                                    </div>
                                    <div class="row g-2 id-bank-transfer-fields d-none">
                                        <div class="col-md-4"><label class="small fw-bold">Transaction No</label><input
                                                type="text" class="form-control form-control-sm target-txn-no"></div>
                                        <div class="col-md-4"><label class="small fw-bold">Transaction Date</label><input
                                                type="date" class="form-control form-control-sm target-bank-txn-date">
                                        </div>
                                        <div class="col-md-4"><label class="small fw-bold">Bank Name (Where
                                                Received)</label><input type="text"
                                                class="form-control form-control-sm target-rec-bank"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- COMPANY USE ONLY (RECEIVED BY & REMARKS) -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white fw-bold text-success"><i class="fas fa-building"></i> Company
                                Use Only</div>
                            <div class="card-body row g-3">
                                <div class="col-md-4">
                                    <label class="small text-muted fw-bold">Received By (Search Employee)</label>
                                    <input list="employees-list" class="form-control d-received-by-search"
                                        placeholder="Type Employee Name..." onchange="handleEmployeeSelection(this)">
                                        <input type="hidden" id="d-received-by-name">
                                    <datalist id="employees-list"></datalist>
                                </div>
                                <div class="col-md-4"><label class="small text-muted fw-bold">Employee Code</label><input
                                        type="text" class="form-control d-received-emp-code" readonly></div>
                                <div class="col-md-4"><label class="small text-muted fw-bold">Department</label><input
                                        type="text" class="form-control d-received-dept" readonly></div>
                                <div class="col-12"><label class="small text-muted fw-bold">Remarks (If any)</label>
                                    <textarea class="form-control d-remarks" rows="1"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- AMOUNT DETAILS -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <div
                                    class="d-flex justify-content-between align-items-center bg-dark text-white p-2 rounded mb-2">
                                    <span class="small fw-bold"><i class="fas fa-list"></i> AMOUNT DETAILS</span>
                                    <button type="button" class="btn btn-success btn-sm py-0"
                                        onclick="addNewAmountRow()"><i class="fas fa-plus"></i></button>
                                </div>
                                <div class="amount-rows-wrapper" id="amount-rows-holder"></div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body row g-3">
                                <div class="col-md-4">
                                    <label class="small fw-bold text-danger">Net Total Amount *</label>
                                    <input type="number" class="form-control d-net-amt" required
                                        oninput="calculateBalanceSummary()">
                                </div>
                                <div class="col-md-4">
                                    <label class="small fw-bold text-success">Total Amount Received</label>
                                    <input type="number" class="form-control d-received-amt" value="0" readonly>
                                    <!-- 🌟 Amount in Words 🌟 -->
                                    <span id="amount-in-words" class="amount-words fw-bold"></span>
                                </div>
                                <div class="col-md-4"><label class="small fw-bold text-warning">Balance
                                        Amount</label><input type="number" class="form-control d-balance-amt"
                                        value="0" readonly></div>
                            </div>
                        </div>

                        <!-- Approvals -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body row g-3">
                                <div class="col-md-6 form-group">
                                    <label class="small text-muted fw-bold">Approved By (Account Dept) *</label>
                                    <select class="form-select select2 d-approved-by" required>
                                        <option value="">-- Choose Reviewer --</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="small text-muted fw-bold">Authorized Signatory (CEO) *</label>
                                    <select class="form-select select2 d-ceo" required>
                                        <option value="">-- Choose CEO Profile --</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary fw-bold" onclick="submitReceiptForm()">Save
                        Receipt</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Preview Modal -->
    <div class="modal fade" id="printPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-print me-2"></i> Print Simulation View</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light p-0">
                    <iframe id="previewIframe" src="" style="width: 100%; height: 75vh; border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let currentPath = window.location.pathname;
        let currentPortal = currentPath.split('/')[1] || 'admin';
        let tokenKey = currentPortal === 'employee' ? 'emp_token' : (currentPortal === 'member' ? 'member_token' :
            'admin_token');
        const apiToken = localStorage.getItem(tokenKey) || localStorage.getItem('token') || '';

        $.ajaxSetup({
            headers: {
                'Authorization': 'Bearer ' + apiToken,
                'Accept': 'application/json'
            }
        });

        const availableParticularOptions = ['Admission Fee', 'Enrollment Amount', 'Allotment Amount', 'Premium Amount',
            'Construction Amount', 'Other Charges', 'Advance'
        ];

        // Global Arrays for Datalists
        let globalCompanies = [];
        let globalBranches = [];
        let globalCustomers = [];
        let globalEmployees = [];

        $(document).ready(function() {
            $('.select2').each(function() {
                $(this).select2({
                    theme: "classic",
                    dropdownParent: $(this).parent()
                });
            });
            initDataTablesEngine();
            loadDropdownDataMatrix();
            loadExtraDatalists();
        });

        function initDataTablesEngine() {
            $('#receiptsDataTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: false,
                ajax: {
                    url: '/api/v1/receipts',
                    type: 'GET'
                },
                columns: [{
                        data: 'receipt_no',
                        createdCell: (td) => $(td).attr('data-label', 'Receipt No.')
                    },
                    {
                        data: 'receipt_date',
                        createdCell: (td) => $(td).attr('data-label', 'Date')
                    },
                    {
                        data: 'customer_name',
                        createdCell: (td) => $(td).attr('data-label', 'Customer Name')
                    },
                    {
                        data: null,
                        createdCell: (td) => $(td).attr('data-label', 'Property'),
                        render: d =>
                            `<span class="badge badge-secondary">${d.property_type || '-'} (${d.unit_no || '-'})</span>`
                    },
                    {
                        data: 'total_amount',
                        createdCell: (td) => $(td).attr('data-label', 'Total Amount'),
                        render: data => `₹${parseFloat(data).toFixed(2)}`
                    },
                    {
                        data: 'amount_received',
                        createdCell: (td) => $(td).attr('data-label', 'Received'),
                        render: data =>
                            `<span class="text-success fw-bold">₹${parseFloat(data).toFixed(2)}</span>`
                    },
                    {
                        data: 'balance_amount',
                        createdCell: (td) => $(td).attr('data-label', 'Balance'),
                        render: data =>
                            `<span class="text-danger fw-bold">₹${parseFloat(data).toFixed(2)}</span>`
                    },
                    {
                        data: null,
                        orderable: false,
                        className: 'text-end text-md-center',
                        createdCell: (td) => $(td).attr('data-label', 'Actions'),
                        render: d =>
                            `
                            <button class="btn btn-info text-white btn-sm px-2 py-1 me-1" onclick="triggerLivePreview(${d.id})"><i class="fas fa-eye"></i></button>
                            <a href="/${currentPortal}/receipts/print/${d.id}" target="_blank" class="btn btn-dark btn-sm px-2 py-1"><i class="fas fa-print"></i></a>`
                    }
                ]
            });
        }

        // LOAD BASE FORM DATA
        function loadDropdownDataMatrix() {
            $.get('/api/v1/receipts/form-data', function(res) {
                if (res.status === 'success') {
                    globalCompanies = res.data.companies;
                    let compDatalist = $('#companies-list');
                    compDatalist.empty();
                    globalCompanies.forEach(c => compDatalist.append(
                    `<option value="${c.company_name}"></option>`));

                    res.data.phases.forEach(p => $('.d-phase').append(new Option(p.phase_name, p.id)));
                    res.data.account_employees.forEach(e => $('.d-approved-by').append(new Option(
                        `${e.full_name} (${e.member_id})`, e.id)));
                    res.data.ceos.forEach(c => $('.d-ceo').append(new Option(`${c.full_name} (${c.ceo_id})`, c
                    .id)));
                }
            });
        }

        // LOAD NEW CUSTOMER & EMPLOYEE API DATA
        function loadExtraDatalists() {
            $.get('/api/v1/receipts/get-customers', function(res) {
                if (res.status === 'success') {
                    globalCustomers = res.data.map(c => ({
                        ...c,
                        search_text: `${c.customer_name} - ${c.customer_id || 'N/A'}`
                    }));
                    let list = $('#customers-list');
                    list.empty();
                    globalCustomers.forEach(c => list.append(`<option value="${c.search_text}"></option>`));
                }
            });

            $.get('/api/v1/receipts/get-employees', function(res) {
                if (res.status === 'success') {
                    globalEmployees = res.data.map(e => ({
                        ...e,
                        search_text: `${e.full_name} (${e.member_id})`
                    }));
                    let list = $('#employees-list');
                    list.empty();
                    globalEmployees.forEach(e => list.append(`<option value="${e.search_text}"></option>`));
                }
            });
        }

        // DATALIST EVENT HANDLERS
        window.handleCompanySelection = function(input) {
            let selected = globalCompanies.find(c => c.company_name === input.value);
            if (selected) {
                $('.d-company').val(selected.id);
                loadCompanyBranches(selected.id);
            } else {
                $('.d-company').val('');
            }
        }

        window.loadCompanyBranches = function(companyId) {
            $.get(`/api/v1/receipts/get-branches/${companyId}`, function(res) {
                if (res.status === 'success') {
                    globalBranches = res.data;
                    let list = $('#branches-list');
                    list.empty();
                    list.append(`<option value="Head Office"></option>`);
                    globalBranches.forEach(b => list.append(`<option value="${b.branch_name}"></option>`));
                    $('.d-branch-search').val('Head Office');
                    $('.d-branch').val('all'); // default
                }
            });
        }

        window.handleBranchSelection = function(input) {
            if (input.value === 'Head Office') {
                $('.d-branch').val('all');
                return;
            }
            let selected = globalBranches.find(b => b.branch_name === input.value);
            if (selected) $('.d-branch').val(selected.id);
            else $('.d-branch').val('');
        }

        window.handleCustomerSelection = function(input) {
            let selected = globalCustomers.find(c => c.search_text === input.value);
            if (selected) {
                $('.d-cust-name').val(selected.customer_name);
                $('.d-cust-id-no').val(selected.customer_id);
                $('.d-father-name').val(selected.father_name);
                $('.d-spouse-name').val(selected.spouse_name);
                $('.d-mobile').val(selected.customer_mobile);
                $('.d-address').val(selected.address);
            }
        }

        window.handleEmployeeSelection = function(input) {
            let selected = globalEmployees.find(e => e.search_text === input.value);
            if (selected) {
                
                $('.d-received-emp-code').val(selected.member_id);
                $('.d-received-dept').val(selected.department ? selected.department.department_name : '');
                $('#d-received-by-name').val(selected.full_name);
            }
        }

        // UI HELPERS
        window.handlePaymentModeFields = function(mode) {
            const container = $('#conditional-fields-container');
            container.removeClass('d-none');
            $('.id-cheque-fields, .id-upi-fields, .id-bank-transfer-fields').addClass('d-none');

            if (mode === 'Cash') container.addClass('d-none');
            else if (mode === 'Cheque') $('.id-cheque-fields').removeClass('d-none');
            else if (mode === 'UPI') $('.id-upi-fields').removeClass('d-none');
            else if (['NEFT', 'RTGS', 'Other'].includes(mode)) $('.id-bank-transfer-fields').removeClass('d-none');
        }

        window.resetFormState = function() {
            document.getElementById('receiptEntryForm').reset();
            $('.d-company, .d-branch').val(''); // hidden fields reset
            $('#amount-rows-holder').empty();
            $('#amount-in-words').text('');
            addNewAmountRow();
        }

        // DYNAMIC AMOUNT ROWS
        window.addNewAmountRow = function() {
            const container = $('#amount-rows-holder');
            if (container.children().length >= availableParticularOptions.length) {
                Swal.fire('Limit', 'Max added.', 'warning');
                return;
            }

            const selectedValues = $('.row-particular').map(function() {
                return this.value;
            }).get();
            const filteredOptions = availableParticularOptions.filter(opt => !selectedValues.includes(opt));
            const rowId = Date.now();

            let rowHtml = `
                <div class="row g-2 align-items-center mb-2" id="amt-row-${rowId}">
                    <div class="col-5"><select class="form-select form-select-sm row-particular" onchange="recalculateParticularsFilters()" required><option value="">-- For --</option>${filteredOptions.map(o => `<option value="${o}">${o}</option>`).join('')}</select></div>
                    <div class="col-3"><select class="form-select form-select-sm row-status" required><option value="Paid">Paid</option><option value="Partial">Partial</option></select></div>
                    <div class="col-3"><input type="number" class="form-control form-control-sm row-amount" required oninput="calculateBalanceSummary()"></div>
                    <div class="col-1 text-end"><button type="button" class="btn btn-danger btn-sm py-0" onclick="removeAmountRow('${rowId}')"><i class="fas fa-times"></i></button></div>
                </div>`;
            container.append(rowHtml);
            recalculateParticularsFilters();
        }

        window.removeAmountRow = function(rowId) {
            $(`#amt-row-${rowId}`).remove();
            calculateBalanceSummary();
            recalculateParticularsFilters();
        }

        window.recalculateParticularsFilters = function() {
            const selectedValues = $('.row-particular').map(function() {
                return this.value;
            }).get();
            $('.row-particular').each(function() {
                const currentVal = $(this).val();
                $(this).empty().append('<option value="">-- For --</option>');
                availableParticularOptions.forEach(opt => {
                    if (!selectedValues.includes(opt) || opt === currentVal) $(this).append(new Option(
                        opt, opt, false, opt === currentVal));
                });
            });
        }

        window.calculateBalanceSummary = function() {
            const netAmt = parseFloat($('.d-net-amt').val()) || 0;
            let totalReceived = 0;
            $('.row-amount').each(function() {
                totalReceived += parseFloat($(this).val()) || 0;
            });

            $('.d-received-amt').val(totalReceived.toFixed(2));
            $('.d-balance-amt').val((netAmt - totalReceived).toFixed(2));

            // Convert total received to words
            $('#amount-in-words').text(totalReceived > 0 ? numberToWords(totalReceived) + ' Only' : '');
        }

        // 🌟 NUMBER TO WORDS CONVERTER 🌟
        function numberToWords(amount) {
            const words = ["Zero", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten", "Eleven",
                "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen"
            ];
            const tens = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
            if (amount === 0) return "Zero Rupees";
            let numStr = amount.toString();
            if (numStr.includes('.')) numStr = numStr.split('.')[0]; // ignore paise for now
            let num = parseInt(numStr, 10);

            function convert(n) {
                if (n < 20) return words[n];
                if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 !== 0 ? " " + words[n % 10] : "");
                if (n < 1000) return words[Math.floor(n / 100)] + " Hundred" + (n % 100 !== 0 ? " and " + convert(n % 100) :
                    "");
                if (n < 100000) return convert(Math.floor(n / 1000)) + " Thousand" + (n % 1000 !== 0 ? " " + convert(n %
                    1000) : "");
                if (n < 10000000) return convert(Math.floor(n / 100000)) + " Lakh" + (n % 100000 !== 0 ? " " + convert(n %
                    100000) : "");
                return convert(Math.floor(n / 10000000)) + " Crore" + (n % 10000000 !== 0 ? " " + convert(n % 10000000) :
                    "");
            }
            return convert(num) + " Rupees";
        }

        // FORM SUBMISSION
        window.submitReceiptForm = function() {
            const form = document.getElementById('receiptEntryForm');
            if (!form.checkValidity() || !$('.d-company').val() || !$('.d-branch').val()) {
                form.classList.add('was-validated');
                if (!$('.d-company').val()) Swal.fire('Error', 'Please select a valid company from the list.', 'error');
                return;
            }

            const amountDetailsArr = [];
            $('.amount-rows-wrapper .row').each(function() {
                amountDetailsArr.push({
                    particular: $(this).find('.row-particular').val(),
                    status: $(this).find('.row-status').val(),
                    amount: parseFloat($(this).find('.row-amount').val()) || 0
                });
            });

            const payload = {
                company_id: $('.d-company').val(),
                branch_id: $('.d-branch').val() === 'all' ? null : $('.d-branch').val(),
                receipt_date: $('.d-date').val(),
                receipt_no: $('.d-receipt-no').val(),
                project_name: $('.d-project').val(),
                phase_id: $('.d-phase').val() || null,
                passbook_no: $('.d-passbook').val(),
                customer_name: $('.d-cust-name').val(),
                customer_identification_no: $('.d-cust-id-no').val(),
                father_name: $('.d-father-name').val(),
                spouse_name: $('.d-spouse-name').val(),
                customer_mobile: $('.d-mobile').val(),
                address: $('.d-address').val(),
                property_type: $('.d-prop-type').val(),
                unit_no: $('.d-unit-no').val(),
                area_sqft: $('.d-area').val(),
                payment_mode: $('.d-pay-mode').val(),

                received_by_emp_code: $('.d-received-emp-code').val(),
              
                received_by_department: $('.d-received-dept').val(),
                remarks: $('.d-remarks').val(),

                total_amount: $('.d-net-amt').val(),
                amount_details: amountDetailsArr,
                approved_by_emp_id: $('.d-approved-by').val(),
                auth_ceo_id: $('.d-ceo').val(),

                cheque_no: $('.target-cheque-no').val(),
                bank_name: $('.target-bank-name').val(), // Cheque bank
                date_of_cheque: $('.target-cheque-date').val(),
                utr_no: $('.target-utr-no').val(),
                transaction_date: $('.target-txn-date').val() || $('.target-bank-txn-date').val(),
                transaction_no: $('.target-txn-no').val(),
                received_bank_name: $('.target-rec-bank').val(), // NEFT/RTGS bank
            };

            $.ajax({
                url: '/api/v1/receipts',
                type: 'POST',
                data: JSON.stringify(payload),
                contentType: 'application/json',
                success: function(res) {
                    Swal.fire('Success', res.message, 'success');
                    $('#receiptFormModal').modal('hide');
                    $('#receiptsDataTable').DataTable().ajax.reload();
                },
                error: function(err) {
                    Swal.fire('Error', err.responseJSON.message || 'Failed to save receipt', 'error');
                }
            });
        }

        window.triggerLivePreview = function(id) {
            const url = `/${currentPortal}/receipts/print/${id}`;
            $('#previewIframe').attr('src', url);
            $('#printPreviewModal').modal('show');
        }
    </script>
@endpush
