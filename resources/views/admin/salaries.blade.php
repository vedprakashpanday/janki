@extends('layout.app') 
@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<style>
    .table-custom th { background-color: var(--sidebar-bg); color: #fff; font-size: 13px; border: none; padding: 12px 15px; }
    .table-custom td { font-size: 13px; vertical-align: middle; border-bottom: 1px solid var(--border-color); padding: 12px 15px; }
    
    /* Mobile Card Styling */
    .mobile-item { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 15px; margin-bottom: 15px; box-shadow: 0 4px 6px var(--shadow-color); }
    .amount-badge { background: #dcfce7; color: #166534; padding: 5px 12px; border-radius: 6px; font-weight: bold; font-size: 13px; }
    .not-assigned { background: #f3f4f6; color: #6b7280; padding: 5px 12px; border-radius: 6px; font-weight: bold; font-size: 13px; }
    
    /* Auto-filled inputs look different */
    .auto-fill-input { background-color: #f8fafc; border-style: dashed; }
</style>

<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Employee Salaries</h4>
        <button type="button" class="btn text-white px-3 py-2 shadow-sm" style="background-color: var(--brand-primary);" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus-circle me-1"></i> Add / Assign Salary
        </button>
    </div>

    <div class="d-block d-md-none mb-3">
        <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search Employee...">
    </div>

    <div class="card border-0 shadow-sm mb-4 d-none d-md-block">
        <div class="card-body p-4 table-responsive">
            <table id="salaryTable" class="table table-hover table-custom w-100">
                <thead>
                    <tr>
                        <th>Emp ID</th>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>Gross Salary</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div id="mobileCardsContainer" class="d-block d-md-none"></div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i class="fas fa-file-invoice-dollar me-2 text-success"></i> Salary Breakdown</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4 pb-3 border-bottom">
                    <h5 class="fw-bold text-dark mb-1" id="v_name"></h5>
                    <span class="badge bg-primary" id="v_empid"></span>
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between p-3 rounded" style="background-color: #dcfce7; border: 1px solid #bbf7d0;">
                            <span class="fw-bold text-success">Gross Salary (Total Amount)</span>
                            <span class="fw-bold text-success fs-5">₹ <span id="v_amount">0.00</span></span>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-secondary mb-3 text-uppercase" style="font-size: 12px;">Component Details</h6>
                <ul class="list-group list-group-flush border rounded">
                    <li class="list-group-item d-flex justify-content-between"><span>Basic Pay (40%)</span> <strong id="v_basic">₹ 0.00</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>HRA (8%)</span> <strong id="v_hra">₹ 0.00</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>DA (8%)</span> <strong id="v_da">₹ 0.00</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Medical Allowance (8%)</span> <strong id="v_medical">₹ 0.00</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Travel Allowance (0%)</span> <strong id="v_travel">₹ 0.00</strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Other Allowance (36%)</span> <strong id="v_other">₹ 0.00</strong></li>
                </ul>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);">Assign Salary</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addForm" class="row g-3">
                    <div class="col-12">
                        <label class="form-label text-secondary small fw-bold">Select Employee</label>
                        <input type="text" name="member_id" class="form-control" list="employeeList" placeholder="Search by ID or Name..." required autocomplete="off">
                        <datalist id="employeeList"></datalist>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label text-secondary small fw-bold">Gross Salary Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="add_amount" class="form-control fw-bold text-success fs-5" required min="0" step="0.01">
                    </div>

                    <div class="col-6"><label class="form-label small">Basic Pay (40%)</label><input type="text" name="basic_pay" id="add_basic" class="form-control auto-fill-input" readonly></div>
                    <div class="col-6"><label class="form-label small">HRA (8%)</label><input type="text" name="hra" id="add_hra" class="form-control auto-fill-input" readonly></div>
                    <div class="col-6"><label class="form-label small">DA (8%)</label><input type="text" name="da" id="add_da" class="form-control auto-fill-input" readonly></div>
                    <div class="col-6"><label class="form-label small">Medical (8%)</label><input type="text" name="medical_allowance" id="add_medical" class="form-control auto-fill-input" readonly></div>
                    <div class="col-6"><label class="form-label small">Travel (0%)</label><input type="text" name="travel_allowance" id="add_travel" class="form-control auto-fill-input" readonly></div>
                    <div class="col-6"><label class="form-label small">Other (36%)</label><input type="text" name="other_allowance" id="add_other" class="form-control auto-fill-input" readonly></div>

                    <div class="col-12 text-end mt-4 pt-3 border-top">
                        <button type="submit" class="btn text-white px-4 shadow-sm w-100 fw-bold" style="background-color: var(--brand-primary);">Save & Assign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);">Update Salary</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="editForm" class="row g-3">
                    <input type="hidden" id="edit_id">
                    <div class="col-12">
                        <label class="form-label text-secondary small fw-bold">Employee</label>
                        <input type="text" id="edit_emp_detail" class="form-control bg-light" readonly>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label text-secondary small fw-bold">Gross Salary Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="edit_amount" class="form-control fw-bold text-success fs-5" required min="0" step="0.01">
                    </div>

                    <div class="col-6"><label class="form-label small">Basic Pay (40%)</label><input type="text" name="basic_pay" id="edit_basic" class="form-control auto-fill-input" readonly></div>
                    <div class="col-6"><label class="form-label small">HRA (8%)</label><input type="text" name="hra" id="edit_hra" class="form-control auto-fill-input" readonly></div>
                    <div class="col-6"><label class="form-label small">DA (8%)</label><input type="text" name="da" id="edit_da" class="form-control auto-fill-input" readonly></div>
                    <div class="col-6"><label class="form-label small">Medical (8%)</label><input type="text" name="medical_allowance" id="edit_medical" class="form-control auto-fill-input" readonly></div>
                    <div class="col-6"><label class="form-label small">Travel (0%)</label><input type="text" name="travel_allowance" id="edit_travel" class="form-control auto-fill-input" readonly></div>
                    <div class="col-6"><label class="form-label small">Other (36%)</label><input type="text" name="other_allowance" id="edit_other" class="form-control auto-fill-input" readonly></div>

                    <div class="col-12 text-end mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary px-4 shadow-sm w-100 fw-bold">Update Salary</button>
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

<script>
$(document).ready(function() {
    const apiToken = localStorage.getItem('admin_token');

    // === AUTO CALCULATION LOGIC ===
    function calculateComponents(amount, prefix) {
        let amt = parseFloat(amount) || 0;
        
        $(`#${prefix}_basic`).val((amt * 0.40).toFixed(2));
        $(`#${prefix}_hra`).val((amt * 0.08).toFixed(2));
        $(`#${prefix}_da`).val((amt * 0.08).toFixed(2));
        $(`#${prefix}_medical`).val((amt * 0.08).toFixed(2));
        $(`#${prefix}_travel`).val((0).toFixed(2)); // 0%
        $(`#${prefix}_other`).val((amt * 0.36).toFixed(2));
    }

    $('#add_amount').on('keyup change', function() { calculateComponents($(this).val(), 'add'); });
    $('#edit_amount').on('keyup change', function() { calculateComponents($(this).val(), 'edit'); });

    // === DATATABLES ===
    let table = $('#salaryTable').DataTable({
        ajax: { 
            url: '/api/v1/admin/salaries', 
            headers: { 'Authorization': 'Bearer ' + apiToken },
            dataSrc: function(json) {
                renderMobileCards(json.data);
                populateDatalist(json.data);
                return json.data;
            }
        },
        columns: [
            { data: 'member_id', render: d => `<span class="fw-bold text-primary">${d}</span>` },
            { data: 'full_name', render: d => `<span class="fw-bold text-dark">${d}</span>` },
            { data: 'designation', render: d => d ? d : '-' },
            { data: 'salary', render: function(d) {
                // FIX 2: Added strict null check so it doesn't fail on 0
                return d !== null ? `<span class="amount-badge">₹ ${d}</span>` : `<span class="not-assigned">Not Assigned</span>`;
            }},
            { data: 'id', render: function(d, type, row) {
                let btns = `<div class="text-end">`;
                // FIX 2: Check for !== null rather than just row.salary
                if(row.salary !== null) {
                    btns += `<button class="btn btn-sm btn-light text-info me-1 fw-bold view-btn" data-id="${d}"><i class="fas fa-eye"></i></button>`;
                }
                btns += `<button class="btn btn-sm btn-light text-primary fw-bold edit-btn" data-id="${d}"><i class="fas fa-edit"></i></button></div>`;
                return btns;
            }}
        ]
    });

    function populateDatalist(data) {
        let options = '';
        data.forEach(emp => { options += `<option value="${emp.member_id}">${emp.full_name} (${emp.designation || 'No Role'})</option>`; });
        $('#employeeList').html(options);
    }

    // === MOBILE CARDS ===
    function renderMobileCards(data) {
        let html = '';
        data.forEach(emp => {
            // FIX 2: Strict null check for mobile cards as well
            let amountHtml = emp.salary !== null
                ? `<span class="amount-badge"><i class="fas fa-rupee-sign me-1"></i>${emp.salary}</span>` 
                : `<span class="not-assigned">Not Assigned</span>`;
                
            let viewBtn = emp.salary !== null ? `<button class="btn btn-sm btn-light text-info fw-bold flex-fill view-btn" data-id="${emp.id}"><i class="fas fa-eye"></i> View</button>` : '';

            html += `
                <div class="mobile-item emp-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0">${emp.full_name}</h6>
                            <small class="text-primary fw-bold">${emp.member_id}</small>
                        </div>
                        ${amountHtml}
                    </div>
                    <div class="small text-muted mb-3"><i class="fas fa-user-tag me-1"></i> ${emp.designation || '-'}</div>
                    <div class="pt-2 border-top d-flex gap-2">
                        ${viewBtn}
                        <button class="btn btn-sm btn-light text-primary fw-bold flex-fill edit-btn" data-id="${emp.id}"><i class="fas fa-edit"></i> Edit</button>
                    </div>
                </div>
            `;
        });
        $('#mobileCardsContainer').html(html);
    }

    $('#mobileSearch').on('keyup', function() {
        let val = $(this).val().toLowerCase();
        $('.emp-card').filter(function() { $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1); });
    });

    // === ACTION: VIEW MODAL ===
    $(document).on('click', '.view-btn', function() {
        let id = $(this).data('id');
        $.get({
            url: `/api/v1/admin/salaries/${id}`,
            headers: { 'Authorization': 'Bearer ' + apiToken },
            success: function(res) {
                let d = res.data;
                $('#v_empid').text(d.member_id);
                $('#v_name').text(d.full_name);
                $('#v_amount').text(d.amount);
                
                $('#v_basic').text(`₹ ${d.basic_pay}`);
                $('#v_hra').text(`₹ ${d.hra}`);
                $('#v_da').text(`₹ ${d.da}`);
                $('#v_medical').text(`₹ ${d.medical_allowance}`);
                $('#v_travel').text(`₹ ${d.travel_allowance}`);
                $('#v_other').text(`₹ ${d.other_allowance}`);

                $('#viewModal').modal('show');
            }
        });
    });

    // === ACTION: ADD & EDIT ===
    $('#addForm').submit(function(e) {
        e.preventDefault();
        $.post({
            url: '/api/v1/admin/salaries',
            headers: { 'Authorization': 'Bearer ' + apiToken },
            data: $(this).serialize(),
            success: function(res) {
                alert(res.message);
                $('#addModal').modal('hide');
                table.ajax.reload(null, false);
            }
        });
    });

    $(document).on('click', '.edit-btn', function() {
        let id = $(this).data('id');
        $.get({
            url: `/api/v1/admin/salaries/${id}`,
            headers: { 'Authorization': 'Bearer ' + apiToken },
            success: function(res) {
                let d = res.data;
                $('#edit_id').val(d.id);
                $('#edit_emp_detail').val(`${d.member_id} - ${d.full_name}`);
                $('#edit_amount').val(d.amount);
                
                // Pre-fill calculation logic
                calculateComponents(d.amount, 'edit');

                $('#editModal').modal('show');
            }
        });
    });

    $('#editForm').submit(function(e) {
        e.preventDefault();
        let id = $('#edit_id').val();
        $.ajax({
            url: `/api/v1/admin/salaries/${id}`,
            type: 'PUT',
            headers: { 'Authorization': 'Bearer ' + apiToken },
            data: $(this).serialize(),
            success: function(res) {
                alert(res.message);
                $('#editModal').modal('hide');
                table.ajax.reload(null, false);
            }
        });
    });
});
</script>
@endpush