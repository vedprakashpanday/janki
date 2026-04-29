@extends('layout.app')

@section('content')
<div class="container-fluid px-1 px-md-3 py-2">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-list-ul me-2"></i>Debit Vouchers</h5>
        <button class="btn btn-primary btn-sm shadow-sm" onclick="openAddModal()">
            <i class="fas fa-plus-circle me-1"></i> New Voucher
        </button>
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
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm mb-3 d-md-none">
    <div class="card-body p-2 p-md-3">
        <div class="row g-2 align-items-center">
            <div class="col-8 col-md-9">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" id="customSearch" class="form-control border-start-0 ps-0" placeholder="Search DV No or Account...">
                </div>
            </div>
            <div class="col-4 col-md-3 text-end">
                <button class="btn btn-success btn-sm w-100 shadow-sm fw-bold" id="btnExportExcel">
                    <i class="fas fa-file-excel me-1"></i> <span class="d-none d-md-inline">Export Excel</span><span class="d-md-none">Excel</span>
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
                <h5 class="modal-title fw-bold text-primary" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add Debit Voucher</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="voucherForm">
                @csrf
                <div class="modal-body p-4">
                    <input type="hidden" id="v_id" name="id">
            <div class="row g-3">
    <div class="col-md-4">
        <label class="small fw-bold">DV No *</label>
        <input type="text" class="form-control fw-bold text-primary" id="m_dv_no" name="dv_no" onkeyup="checkDvNo()">
        <small id="dv_no_error" class="text-danger fw-bold mt-1" style="display:none;"><i class="fas fa-times-circle"></i> Not Available</small>
        <small id="dv_no_success" class="text-success fw-bold mt-1" style="display:none;"><i class="fas fa-check-circle"></i> Available</small>
    </div>
    
    <div class="col-md-4">
        <label class="small fw-bold">Date *</label>
        <input type="date" class="form-control" id="m_voucher_date" name="voucher_date" required>
    </div>

    <div class="col-md-4">
        <label class="small fw-bold">Branch *</label>
        <select class="form-select" id="m_branch_id" name="branch_name" onchange="loadBranchData()" required>
            <option value="">Select Branch</option>
        </select>
    </div>

    <div class="col-md-6">
        <label class="small fw-bold">Head of Account *</label>
        <input list="ledgerList" class="form-control" id="m_head_of_account" name="head_of_account" placeholder="Select Branch First" disabled>
        <datalist id="ledgerList"></datalist>
    </div>

    <div class="col-md-6">
        <label class="small fw-bold">Paid To *</label>
        <input list="paidToList" class="form-control" id="m_paid_to" name="paid_to" placeholder="Select Branch First" disabled>
        <datalist id="paidToList"></datalist>
    </div>

    <div class="col-md-4">
        <label class="small fw-bold">Amount</label>
        <input type="number" class="form-control" id="m_amount" name="amount" oninput="convertToWords(this.value)">
    </div>
    <div class="col-md-8">
        <label class="small fw-bold">Amount in Words</label>
        <input type="text" class="form-control bg-light" id="m_amount_words" name="amount_words" readonly>
    </div>

    <div class="col-md-12">
        <label class="small fw-bold">Payment Mode</label>
        <select class="form-select border-primary fw-bold" id="m_payment_mode" name="payment_mode" onchange="togglePaymentFields()">
            <option value="Cash">Cash</option>
            <option value="Bank Transfer">Bank Transfer (NEFT/IMPS)</option>
            <option value="Cheque">Cheque</option>
            <option value="UPI">UPI</option>
        </select>
    </div>

   <div id="bankTransferSection" style="display: none;" class="row g-2 mt-2 bg-info-subtle p-3 rounded border border-info">
        <h6 class="fw-bold text-primary mb-1"><i class="fas fa-university"></i> Receiver's Bank Details</h6>
        
        <div class="col-md-6">
            <label class="small fw-bold">Bank Name</label>
            <input type="text" class="form-control bg-white" id="bt_bank_name" name="bank_name" readonly>
        </div>
        <div class="col-md-6">
            <label class="small fw-bold">Account No.</label>
            <input type="text" class="form-control bg-white" id="bt_account_no" name="account_no" readonly>
        </div>
        <div class="col-md-4">
            <label class="small fw-bold">IFSC Code</label>
            <input type="text" class="form-control bg-white" id="bt_ifsc_code" name="ifsc_code" readonly>
        </div>
        <div class="col-md-4">
            <label class="small fw-bold">Branch</label>
            <input type="text" class="form-control bg-white" id="bt_branch" readonly>
        </div>
        <div class="col-md-4">
            <label class="small fw-bold">A/c Type</label>
            <input type="text" class="form-control bg-white" id="bt_account_type" readonly>
        </div>

        <hr class="my-2 border-info">

        <h6 class="fw-bold text-primary mb-1 mt-0"><i class="fas fa-exchange-alt"></i> Transaction Details</h6>
       <div class="col-md-4">
    <label class="small fw-bold">Sender Bank (Drawn On) *</label>
    <input list="senderBankList" class="form-control" id="bt_drawn_on" name="drawn_on" placeholder="Select Account" autocomplete="off">
    <datalist id="senderBankList">
        </datalist>
</div>
        <div class="col-md-4">
            <label class="small fw-bold">Tr. ID / Ref No</label>
            <input type="text" class="form-control" id="bt_transaction_id" name="transaction_id">
        </div>
        <div class="col-md-4">
            <label class="small fw-bold">Tr. Date</label>
            <input type="date" class="form-control" id="bt_bank_date" name="bank_date">
        </div>
    </div>

    <div id="chequeSection" style="display: none;" class="row g-2 mt-1 bg-light p-2 rounded border">
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
            <input type="text" class="form-control" id="cq_transaction_id" name="transaction_id">
        </div>
        {{-- <div class="col-md-6">
            <label class="small fw-bold">Clear Date</label>
            <input type="date" class="form-control" id="cq_clear_date" name="clear_date"> </div> --}}
    </div>
    
    <div class="col-12 mt-3">
        <label class="form-label small fw-bold">Narration</label>
        <textarea class="form-control" id="m_narration" name="narration" rows="2" placeholder="Write details here..."></textarea>
    </div>
</div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold" id="saveBtn">Save Voucher</button>
                </div>
            </form>
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
let table;
const apiToken = localStorage.getItem('admin_token');

$(document).ready(function() {
    
    // 1. Initialize Desktop DataTable with custom DOM
    table = $('#dvDataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/api/v1/admin/debit_vouchers', // Naya API Resource Route
            type: 'GET',
            headers: { 'Authorization': 'Bearer ' + apiToken }
        },
        columns: [
            { data: 'dv_no', className: 'ps-3 fw-bold text-primary' },
            { data: 'voucher_date' },
            { data: 'head_of_account' },
            { data: 'amount', render: $.fn.dataTable.render.number(',', '.', 2, '₹') },
            { data: 'payment_mode' },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        // MAGIC DOM: l=length(Show 10 entries), B=Buttons(Excel), f=Search(Right align), t=Table, i=Info, p=Pagination
        dom: "<'row align-items-center mb-3'<'col-sm-12 col-md-6 d-flex align-items-center gap-3'lB><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row align-items-center mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm fw-bold shadow-sm',
                exportOptions: { columns: [0, 1, 2, 3, 4] }
            }
        ],
        drawCallback: function(settings) {
            renderMobileCards(settings.json.data);
        }
    });



    
   // 3. Form Submit se pehle data handle karna
$('#voucherForm').on('submit', function(e) {
e.preventDefault();
    
    // Agar Bank Transfer mode hai toh 'drawn_on' ki value ko Account Number se replace karein
    if($('#m_payment_mode').val() === 'Bank Transfer') {
        let displayVal = $('#bt_drawn_on').val();
        let selectedOption = $(`#senderBankList option[value="${displayVal}"]`);
        
        if(selectedOption.length > 0) {
            let fullAccountNo = selectedOption.attr('data-acc');
            // Hum input ki value ko temporarily account number se replace kar denge submit ke liye
            $('#bt_drawn_on').val(fullAccountNo);
        }
    }

      
        let id = $('#v_id').val();
        let url = id ? `/api/v1/admin/debit_vouchers/${id}` : '/api/v1/admin/debit_vouchers';
        let method = id ? 'PUT' : 'POST';
        
        let formData = $(this).serialize();

        $('#saveBtn').html('<span class="spinner-border spinner-border-sm"></span> Saving...');
        
        $.ajax({
            url: url,
            type: method,
            data: formData,
            headers: { 'Authorization': 'Bearer ' + apiToken },
            success: function(res) {
                $('#voucherModal').modal('hide');
                Swal.fire('Success', res.message, 'success');
                table.ajax.reload();
            },
            error: function(err) {
                Swal.fire('Error', err.responseJSON?.message || 'Something went wrong', 'error');
            },
            complete: function() {
                $('#saveBtn').html('Save Voucher');
            }
        });
    });
});

// 3. Open Modal for ADD
function openAddModal() {
    $('#voucherForm')[0].reset();
    $('#v_id').val('');
    $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add Debit Voucher');
    $('#m_dv_no').val('Auto-Generated'); // Backend se set hoga
    $('#m_voucher_date').val(new Date().toISOString().split('T')[0]);
    $('#voucherModal').modal('show');
}

// 4. Open Modal for EDIT
function editVoucher(id) {
    $.ajax({
        url: `/api/v1/admin/debit_vouchers/${id}`,
        type: 'GET',
        headers: { 'Authorization': 'Bearer ' + apiToken },
        success: function(res) {
            let data = res.data;
            $('#v_id').val(data.id);
            $('#m_dv_no').val(data.dv_no);
            $('#m_voucher_date').val(data.voucher_date);
            $('#m_head_of_account').val(data.head_of_account);
            $('#m_amount').val(data.amount);
            $('#m_payment_mode').val(data.payment_mode);
            $('#m_narration').val(data.narration);
            
            $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Debit Voucher');
            $('#voucherModal').modal('show');
        }
    });
}

// 5. Delete Logic
function deleteVoucher(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/api/v1/admin/debit_vouchers/${id}`,
                type: 'DELETE',
                headers: { 'Authorization': 'Bearer ' + apiToken },
                success: function(res) {
                    Swal.fire('Deleted!', 'Voucher has been deleted.', 'success');
                    table.ajax.reload();
                }
            });
        }
    })
}

// 6. Mobile Card Render (Updated for View, Print & DV No format)
function renderMobileCards(data) {
    $('#mobileLoader').hide();
    let html = '';
    if (!data || data.length === 0) {
        html = '<div class="text-center p-4 bg-white rounded shadow-sm">No vouchers found.</div>';
    } else {
        data.forEach(v => {
            html += `
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-1">DV NO. - ${v.dv_no}</span>
                            <h6 class="fw-bold mb-0">${v.head_of_account}</h6>
                        </div>
                        <div class="text-end">
                            <h6 class="fw-bold text-dark mb-0">₹${v.amount || 0}</h6>
                            <small class="badge bg-light text-secondary border">${v.payment_mode}</small>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <small class="text-muted"><i class="far fa-calendar-alt me-1"></i> ${v.voucher_date}</small>
                        <div class="d-flex gap-1">
                            <a href="/admin/debit_vouchers/print/${v.id}?mode=view" target="_blank" class="btn btn-sm btn-light border text-info" title="View"><i class="fas fa-eye"></i></a>
                            <a href="/admin/debit_vouchers/print/${v.id}?mode=print" target="_blank" class="btn btn-sm btn-light border text-dark" title="Print"><i class="fas fa-print"></i></a>
                            <button onclick="editVoucher(${v.id})" class="btn btn-sm btn-light border text-success" title="Edit"><i class="fas fa-edit"></i></button>
                            <button onclick="deleteVoucher(${v.id})" class="btn btn-sm btn-light border text-danger" title="Delete"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>`;
        });
    }
    $('#mobileCardsContainer').html(html);
}

// Custom Search linking
$('#customSearch').on('keyup', function() {
    table.search(this.value).draw();
});

// Excel Export Trigger
$('#btnExportExcel').on('click', function() {
    table.button('.buttons-excel').trigger();
});

// 1. Amount to Words (Indian Format: Lakhs, Crores)
function convertToWords(amount) {
    if (!amount || amount == 0) { $('#m_amount_words').val(""); return; }

    const a = ['', 'One ', 'Two ', 'Three ', 'Four ', 'Five ', 'Six ', 'Seven ', 'Eight ', 'Nine ', 'Ten ', 'Eleven ', 'Twelve ', 'Thirteen ', 'Fourteen ', 'Fifteen ', 'Sixteen ', 'Seventeen ', 'Eighteen ', 'Nineteen '];
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
        str += (n[5] != 0) ? ((str != '') ? 'and ' : '') + (a[Number(n[5])] || b[n[5][0]] + ' ' + a[n[5][1]]) : '';
        return str + 'Rupees Only';
    };
    
    $('#m_amount_words').val(inWords(Math.floor(amount)));
}

    // 1. Fetch all accounts and populate Datalist
function fetchSenderBank() {
    $.ajax({
        url: '/api/v1/admin/get-sender-bank',
        type: 'GET',
        headers: { 'Authorization': 'Bearer ' + apiToken },
        success: function(res) {
            if(res.status === 'success') {
                let options = '';
                res.data.forEach(item => {
                    // Display ke liye "Bank Name (XXXX1234)" par data attribute me poora Account No
                    options += `<option value="${item.display_name}" data-acc="${item.full_account_no}">`;
                });
                $('#senderBankList').html(options);
            }
        }
    });
}



// Modal open hone par reset logic
function openAddModal() {
    $('#voucherForm')[0].reset();
    $('#bankDetailsSection').hide();
    $('#modalTitle').text('Add Debit Voucher');
    $('#voucherModal').modal('show');
}

$(document).ready(function() {
    // Page load hote hi saari branches load kar lo
    loadBranches();
});

// 1. Fetch Branches
function loadBranches() {
    $.ajax({
        url: '/api/v1/admin/get-branches',
        headers: { 'Authorization': 'Bearer ' + apiToken },
        success: function(res) {
            let options = '<option value="">Select Branch</option>';
            res.data.forEach(b => {
                options += `<option value="${b.id}">${b.branch_name}</option>`;
            });
            $('#m_branch_id').html(options);
        }
    });
}

// 2. Branch Select hone par Ledgers aur Paid To load karna
function loadBranchData() {
    let branchId = $('#m_branch_id').val();
    
    if(!branchId) {
        // Agar branch hataya, toh aage ke field block kar do
        $('#m_head_of_account, #m_paid_to').prop('disabled', true).val('');
        return;
    }

    // Unblock fields
    $('#m_head_of_account, #m_paid_to').prop('disabled', false).val('');
    $('#m_head_of_account').attr('placeholder', 'Search Ledger...');
    $('#m_paid_to').attr('placeholder', 'Search Name...');

    // Fetch Ledgers
    $.ajax({
        url: `/api/v1/admin/get-ledgers?branch_id=${branchId}`,
        headers: { 'Authorization': 'Bearer ' + apiToken },
        success: function(res) {
            let options = '';
            res.data.forEach(l => { options += `<option value="${l.ledger_name} (${l.ledger_code})">`; });
            $('#ledgerList').html(options);
        }
    });

    // Fetch Paid To List
    $.ajax({
        url: `/api/v1/admin/get-paid-to-list?branch_id=${branchId}`,
        headers: { 'Authorization': 'Bearer ' + apiToken },
        success: function(res) {
            let options = '';
            res.data.forEach(p => { options += `<option value="${p.name} - ${p.id} [${p.type}]">`; });
            $('#paidToList').html(options);
        }
    });
}

function checkDvNo() {
    let dvNo = $('#m_dv_no').val();
    
    // Pehle dono messages hide kardo
    $('#dv_no_error, #dv_no_success').hide();

    if(dvNo.length > 0) {
        $.ajax({
            url: `/api/v1/admin/check-dv-no?dv_no=${dvNo}`,
            headers: { 'Authorization': 'Bearer ' + apiToken },
            success: function(res) {
                if(res.exists) {
                    $('#dv_no_error').show(); // Show Red
                    $('#saveBtn').prop('disabled', true); // Block Save button
                } else {
                    $('#dv_no_success').show(); // Show Green
                    $('#saveBtn').prop('disabled', false); // Unblock Save button
                }
            }
        });
    }
}


// Modal open hone par reset logic aur Auto-Fill DV
function openAddModal() {
    $('#voucherForm')[0].reset();
    $('#bankTransferSection, #chequeSection').hide();
    $('#dv_no_error, #dv_no_success').hide();
    $('#m_head_of_account, #m_paid_to').prop('disabled', true);
    
    $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add Debit Voucher');
    $('#m_voucher_date').val(new Date().toISOString().split('T')[0]);
    
    // Auto Fetch Next DV No
    $.ajax({
        url: '/api/v1/admin/get-next-dv-no',
        headers: { 'Authorization': 'Bearer ' + apiToken },
        success: function(res) {
            $('#m_dv_no').val(res.next_dv);
            checkDvNo(); // Turant Green/Red check karega
        }
    });

    $('#voucherModal').modal('show');
}

// 1. Paid To Input Change Listener
$('#m_paid_to').on('input change', function() {
    let selectedText = $(this).val();
    
    // Datalist string se ID nikalne ka logic (e.g. "Name - ID [type]" se 'ID' nikalna)
    let idMatch = selectedText.match(/-\s*(.*?)\s*\[/);
    
    if(idMatch && idMatch[1]) {
        let memberId = idMatch[1].trim(); // ID nikal aayi
        fetchReceiverBankDetails(memberId);
    } else {
        // Agar ID nahi mili to details clear kar do
        clearBankDetails();
    }
});

// 2. Fetch API for Bank Details
function fetchReceiverBankDetails(memberId) {
    $.ajax({
        url: `/api/v1/admin/get-member-bank?member_id=${memberId}`,
        type: 'GET',
        headers: { 'Authorization': 'Bearer ' + apiToken },
        success: function(res) {
            if(res.status === 'success' && res.data) {
                // Fields ko fill karo
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

// 3. Clear Bank Details Function
function clearBankDetails() {
    $('#bt_bank_name, #bt_account_no, #bt_ifsc_code, #bt_branch, #bt_account_type').val('');
}

// 4. Payment Mode Switcher Update

function togglePaymentFields() {
    let mode = $('#m_payment_mode').val();
    
    $('#bankTransferSection, #chequeSection').hide();
    $('#bankTransferSection input, #chequeSection input').prop('disabled', true);

    if (mode === 'Bank Transfer') {
        $('#bankTransferSection').show();
        $('#bankTransferSection input').prop('disabled', false); 
        
        // YAHAN PAR FUNCTION CALL KARNA HAI 👇
        fetchSenderBank(); 
        
    } else if (mode === 'Cheque') {
        $('#chequeSection').show();
        $('#chequeSection input').prop('disabled', false); 
    }
}
</script>

<style>
    /* Styling similar to JankiVilla theme */
    .dataTables_paginate .pagination .page-item.active .page-link { background-color: var(--brand-primary); border-color: var(--brand-primary); }
    .table thead th { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #718096; padding: 12px 10px; }
    .table tbody td { font-size: 13.5px; color: #2D3748; padding: 12px 10px; border-bottom: 1px solid #F1F5F9; }
    /* Making length menu and excel button align perfectly */
    div.dataTables_length select { width: 70px; display: inline-block; }
</style>
@endpush