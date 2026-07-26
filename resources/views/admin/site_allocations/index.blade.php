@extends('layout.app') 
@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    /* Floating Bulk Action Bar */
    #bulkActionBar {
        position: fixed;
        bottom: -100px;
        left: 50%;
        transform: translateX(-50%);
        background: #1A365D;
        color: white;
        padding: 12px 24px;
        border-radius: 50px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        z-index: 1050;
        transition: bottom 0.3s ease-in-out;
        display: flex;
        gap: 15px;
        align-items: center;
    }
    #bulkActionBar.show {
        bottom: 80px; /* Mobile bottom nav se upar */
    }
    .select2-container { width: 100% !important; }
    .action-tools { margin-bottom: 5px; font-size: 12px; text-align: right; }
    .action-tools a { cursor: pointer; color: var(--brand-primary); text-decoration: none; font-weight: 600; margin-left: 10px; }
    .action-tools a:hover { text-decoration: underline; }
    /* 🔥 Select2 Dropdown Z-Index Fix for Modal/Offcanvas 🔥 */
.select2-container--open {
    z-index: 999999 !important;
}
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark"><i class="fas fa-hard-hat text-warning me-2"></i>Site Development Incharge</h5>
        <div>
            <button class="btn btn-success btn-sm secured-item me-2" data-permission="site_export" id="btnExportExcel">
                <i class="fas fa-file-excel"></i> <span class="d-none d-md-inline">Export</span>
            </button>
            
            <button class="btn btn-primary btn-sm secured-item" data-permission="site_add_direct" id="btnOpenSetup">
                <i class="fas fa-plus"></i> <span class="d-none d-md-inline">Set Incharge</span>
            </button>
        </div>
    </div>

    <div id="bulkActionBar">
        <span id="selectedCount" class="fw-bold">0 Selected</span>
        <button class="btn btn-light btn-sm rounded-pill" id="btnSelectAllRows">Select All</button>
        <button class="btn btn-danger btn-sm rounded-pill secured-item" data-permission="site_delete" id="btnBulkDelete">
            <i class="fas fa-trash"></i> Delete
        </button>
    </div>

    <div class="card border-0 shadow-sm d-none d-md-block">
        <div class="card-body">
            <table class="table table-hover w-100" id="allocationTable">
                <thead class="table-light">
                    <tr>
                        <th><input type="checkbox" class="form-check-input" id="checkAllMaster"></th>
                        <th>Employee</th>
                        <th>Company & Branch</th>
                        <th>Incharge Role</th>
                        <th>Allowed For</th>
                        <th>Validity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="d-block d-md-none" id="mobileCardsContainer">
        </div>
</div>

<div class="d-none" id="formTemplate">
    <form class="setupForm">
        <input type="hidden" name="edit_id" class="edit-id-input">

        <div class="row mb-3">
            <div class="col-6">
                <label class="form-label small text-muted">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm">
            </div>
            <div class="col-6">
                <label class="form-label small text-muted">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm">
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-end">
                <label class="form-label small text-muted mb-0">Select Company <span class="text-danger">*</span></label>
                <div class="action-tools">
                    <a class="compSelectAll">Select All</a> | <a class="compClearAll text-danger">Clear</a>
                </div>
            </div>
            <select name="company_ids[]" class="form-select select2-multiple company-select" multiple required>
            </select>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-end">
                <label class="form-label small text-muted mb-0">Select Branch <span class="text-danger">*</span></label>
                <div class="action-tools">
                    <a class="branchSelectAll">Select All</a> | <a class="branchClearAll text-danger">Clear</a>
                </div>
            </div>
            <select name="branch_ids[]" class="form-select select2-multiple branch-select" multiple required>
            </select>
            <small class="text-muted" style="font-size:10px;">Head Office is represented as 'HO' or empty branch.</small>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-end">
                <label class="form-label small text-muted mb-0">Select Employees <span class="text-danger">*</span></label>
                <div class="action-tools">
                    <a class="empSelectAll">Select All</a> | <a class="empClearAll text-danger">Clear</a>
                </div>
            </div>
            <select name="employee_ids[]" class="form-select select2-multiple employee-select" multiple required>
            </select>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-end">
                <label class="form-label small text-muted mb-0">Incharge Type <span class="text-danger">*</span></label>
                <div class="action-tools">
                    <a class="roleSelectAll">Select All</a> | <a class="roleClearAll text-danger">Clear</a>
                </div>
            </div>
            <select name="incharge_types[]" class="form-select select2-tags role-select" multiple required>
    <option value="Site Supervisor">Site Supervisor</option>
    <option value="Site Incharge">Site Incharge</option>
    <option value="Site Guard">Site Guard</option>
    <option value="Site Project Manager">Site Project Manager</option>
</select>
        </div>

        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-end">
                <label class="form-label small text-muted mb-0">Allowed For <span class="text-danger">*</span></label>
                <div class="action-tools">
                    <a class="catSelectAll">Select All</a> | <a class="catClearAll text-danger">Clear</a>
                </div>
            </div>
            <select name="allowed_categories[]" class="form-select select2-tags category-select" multiple required>
                <option value="Labour">Labour</option>
                <option value="Construction Equipment Vehicle">Construction Equipment Vehicle</option>
                <option value="Goods Carrier">Goods Carrier</option>
                <option value="Material">Material</option>
                <option value="Other Expenses">Other Expenses</option>
                <option value="Vehicle Trip Slip">Vehicle Trip Slip</option> 
            </select>
        </div>

        <button type="submit" class="btn btn-primary w-100 btnSaveSetup">Save & Assign</button>
    </form>
</div>


<div class="modal fade" id="desktopModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold">Set Site Incharge</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="desktopModalBody"></div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-bottom" tabindex="-1" id="mobileOffcanvas" style="height: 85vh; border-radius: 20px 20px 0 0;">
    <div class="offcanvas-header bg-light border-bottom">
        <h6 class="offcanvas-title fw-bold">Set Site Incharge</h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" id="mobileOffcanvasBody"></div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    let table;
    let selectedIds = new Set();
    let $activeFormContainer;

    $('#btnOpenSetup').on('click', function() {
        let formHtml = $('#formTemplate').html();
        if ($(window).width() >= 768) {
            $activeFormContainer = $('#desktopModalBody');
            $activeFormContainer.html(formHtml);
            $('#desktopModal').modal('show');
        } else {
            $activeFormContainer = $('#mobileOffcanvasBody');
            $activeFormContainer.html(formHtml);
            $('#mobileOffcanvas').offcanvas('show');
        }
        $activeFormContainer.find('.edit-id-input').val(''); // Naya entry hai toh empty
        initFormLogic();
    });

    // 🔥 FIX: Edit data pas karne ka logic
    function initFormLogic(editData = null) {
        $activeFormContainer.find('.select2-multiple').select2({ placeholder: "Select options", allowClear: true });
        $activeFormContainer.find('.select2-tags').select2({ tags: true, tokenSeparators: [','] });

        let $companySelect = $activeFormContainer.find('.company-select');
        let $branchSelect = $activeFormContainer.find('.branch-select');
        let $employeeSelect = $activeFormContainer.find('.employee-select');

        // Load Companies
        $.get('/api/v1/get-active-companies', function(res) {
            let options = '';
            res.data.forEach(c => { options += `<option value="${c.id}">${c.company_name}</option>`; });
            $companySelect.html(options);
            
            if (editData) {
                $companySelect.val([editData.company_id]).trigger('change');
            } else if(res.data.length === 1) {
                $companySelect.val([res.data[0].id]).trigger('change');
                if(!window.userGodMode) $companySelect.attr('readonly', true).css('pointer-events', 'none');
            }
        });

        // Load Branches 
        $companySelect.on('change', function() {
            let compIds = $(this).val(); 
            if(!compIds || compIds.length === 0) {
                $branchSelect.html('').trigger('change'); 
                $employeeSelect.html('').trigger('change'); return;
            }
            $.get(`/api/v1/branches?company_ids=${compIds.join(',')}`, function(res) {
                let options = '<option value="HO">Head Office (No Branch)</option>'; 
                res.data.forEach(b => { options += `<option value="${b.id}">${b.branch_name}</option>`; });
                $branchSelect.html(options);
                
                if (editData) {
                    $branchSelect.val([editData.branch_id === null ? 'HO' : editData.branch_id]).trigger('change');
                } else {
                    $branchSelect.trigger('change');
                }
            });
        });

        // Load Employees 
        $branchSelect.on('change', function() {
            let compIds = $companySelect.val();
            let branchIds = $(this).val() || [];
            if(!compIds || compIds.length === 0) return;

            let url = `/api/v1/employees?company_ids=${compIds.join(',')}`;
            let validBranchIds = branchIds.filter(id => id !== 'HO');
            if(validBranchIds.length > 0) url += `&branch_ids=${validBranchIds.join(',')}`;

            $.get(url, function(res) {
                let options = '';
                res.data.forEach(e => { options += `<option value="${e.id}">${e.full_name} (${e.member_id})</option>`; });
                $employeeSelect.html(options);
                
                if (editData) {
                    $employeeSelect.val([editData.employee_id]).trigger('change');
                    editData = null; // Auto-fill complete, stop loop
                }
            });
        });

        // Action Buttons Select All
        $activeFormContainer.find('.compSelectAll').click(() => { $companySelect.find('option').prop("selected", true); $companySelect.trigger("change"); });
        $activeFormContainer.find('.compClearAll').click(() => { $companySelect.val(null).trigger("change"); });
        $activeFormContainer.find('.branchSelectAll').click(() => { $branchSelect.find('option').prop("selected", true); $branchSelect.trigger("change"); });
        $activeFormContainer.find('.branchClearAll').click(() => { $branchSelect.val(null).trigger("change"); });
        $activeFormContainer.find('.empSelectAll').click(() => { $employeeSelect.find('option').prop("selected", true); $employeeSelect.trigger("change"); });
        $activeFormContainer.find('.empClearAll').click(() => { $employeeSelect.val(null).trigger("change"); });
        $activeFormContainer.find('.roleSelectAll').click(() => { let s = $activeFormContainer.find('.role-select'); s.find('option').prop("selected", true); s.trigger("change"); });
        $activeFormContainer.find('.roleClearAll').click(() => { $activeFormContainer.find('.role-select').val(null).trigger("change"); });
        $activeFormContainer.find('.catSelectAll').click(() => { let s = $activeFormContainer.find('.category-select'); s.find('option').prop("selected", true); s.trigger("change"); });
        $activeFormContainer.find('.catClearAll').click(() => { $activeFormContainer.find('.category-select').val(null).trigger("change"); });

        // Save / Update Logic
        $activeFormContainer.find('.setupForm').on('submit', function(e) {
            e.preventDefault();
            let formData = $(this).serializeArray();
            let editId = $activeFormContainer.find('.edit-id-input').val();
            
            let url = editId ? '/api/v1/site-allocations/' + editId : '/api/v1/site-allocations';
            if (editId) formData.push({name: '_method', value: 'PUT'}); // 🔥 PUT method for Laravel
            
            Swal.fire({ title: 'Saving...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            $.ajax({
                url: url, type: 'POST', data: formData,
                success: function(res) {
                    Swal.fire('Success', res.message, 'success');
                    $('#desktopModal').modal('hide'); $('#mobileOffcanvas').offcanvas('hide');
                    table.ajax.reload(); loadMobileCards();
                },
                error: function(err) { Swal.fire('Error', err.responseJSON?.message || 'Something went wrong', 'error'); }
            });
        });
    }

    // 🔥 DataTable Initialization (Fixed to `site_*`)
    table = $('#allocationTable').DataTable({
        processing: true, serverSide: true,
        ajax: { url: '/api/v1/site-allocations' },
        columns: [
            { data: 'id', orderable: false, render: function(data) { return `<input type="checkbox" class="form-check-input row-checkbox" value="${data}">`; } },
            { data: 'employee', render: function(data) { return `<strong>${data?.full_name || 'N/A'}</strong><br><small class="text-muted">${data?.member_id || ''}</small>`; } },
            { data: 'company', render: function(data, type, row) { 
                let b = row.branch ? row.branch.branch_name : '<span class="badge bg-secondary">Head Office</span>';
                return `<strong>${data?.company_name || 'N/A'}</strong><br><small>${b}</small>`; 
            }},
            { data: 'incharge_types', render: function(data) { return data ? data.map(d => `<span class="badge bg-primary me-1">${d}</span>`).join('') : '-'; } },
            { data: 'allowed_categories', render: function(data) { return data ? data.map(d => `<span class="badge bg-info text-dark me-1">${d}</span>`).join('') : '-'; } },
            { data: 'start_date', render: function(data, type, row) { return `<small>${data || 'Any'} to ${row.end_date || 'Any'}</small>`; } },
            { 
                data: 'id', orderable: false,
                render: function(data, type, row) {
                    let buttons = '';
                    if(row.status === 'pending') {
                        buttons += `<button class="btn btn-sm btn-success me-1 secured-item" data-permission="site_appr" title="Approve"><i class="fas fa-check"></i></button>`;
                        buttons += `<button class="btn btn-sm btn-danger me-1 secured-item" data-permission="site_rej" title="Reject"><i class="fas fa-times"></i></button>`;
                    }
                    buttons += `<button class="btn btn-sm btn-light text-primary me-1 secured-item btn-edit-allocation" data-id="${data}" data-permission="site_edit" title="Edit"><i class="fas fa-edit"></i></button>`;
                   // 🔥 Naya Code: (Isse replace karein)
buttons += `<a href="/site-allocations/print/${data}" target="_blank" class="btn btn-sm btn-light text-success secured-item" data-permission="sites_print" title="Print Allocation Letter"><i class="fas fa-print"></i></a>`;
                    return buttons;
                }
            }
        ],
        drawCallback: function() { window.applyPermissions(); }
    });

    // 🔥 Mobile Cards Logic (Fixed `site_*` and `data-id`)
    function loadMobileCards() {
        if ($(window).width() >= 768) return;
        $.get('/api/v1/site-allocations?length=20', function(res) {
            let html = '';
            res.data.forEach(item => {
                let branchStr = item.branch ? item.branch.branch_name : '<span class="badge bg-secondary">Head Office</span>';
                let roles = item.incharge_types ? item.incharge_types.map(r => `<span class="badge bg-primary me-1">${r}</span>`).join('') : '';
                let cats = item.allowed_categories ? item.allowed_categories.map(c => `<span class="badge bg-info text-dark me-1">${c}</span>`).join('') : '';
                
                html += `
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" class="form-check-input row-checkbox" value="${item.id}">
                                <div><h6 class="mb-0 fw-bold">${item.employee?.full_name || 'N/A'}</h6><small class="text-muted">${item.employee?.member_id || ''}</small></div>
                            </div>
                        </div>
                        <div class="mb-2 small"><strong>Company:</strong> ${item.company?.company_name || 'N/A'}<br><strong>Branch:</strong> ${branchStr}</div>
                        <div class="mb-2">${roles}</div><div class="mb-3">${cats}</div>
                        <div class="d-flex justify-content-end gap-2">
                            <button class="btn btn-sm btn-light text-primary secured-item btn-edit-allocation" data-id="${item.id}" data-permission="site_edit"><i class="fas fa-edit"></i> Edit</button>
                            <a href="/site-allocations/print/${item.id}" target="_blank" class="btn btn-sm btn-light text-success secured-item" data-permission="sites_print"><i class="fas fa-print"></i></a>
                        </div>
                    </div>
                </div>`;
            });
            $('#mobileCardsContainer').html(html); window.applyPermissions();
        });
    }
    loadMobileCards();

    // 🔥 Edit Button Action
    $(document).on('click', '.btn-edit-allocation', function() {
        let id = $(this).data('id');
        if(!id) return;
        
        Swal.fire({ title: 'Loading...', didOpen: () => Swal.showLoading() });
        $.get('/api/v1/site-allocations/' + id, function(res) {
            Swal.close(); let data = res.data;
            
            let formHtml = $('#formTemplate').html();
            if ($(window).width() >= 768) {
                $activeFormContainer = $('#desktopModalBody'); $activeFormContainer.html(formHtml); $('#desktopModal').modal('show');
            } else {
                $activeFormContainer = $('#mobileOffcanvasBody'); $activeFormContainer.html(formHtml); $('#mobileOffcanvas').offcanvas('show');
            }
            
            // Set IDs & Dates
            $activeFormContainer.find('.edit-id-input').val(data.id);
            $activeFormContainer.find('[name="start_date"]').val(data.start_date ? data.start_date.split('T')[0] : '');
            $activeFormContainer.find('[name="end_date"]').val(data.end_date ? data.end_date.split('T')[0] : '');
            
            // Select2 Tags Auto-fill
            $activeFormContainer.find('.role-select').val(data.incharge_types).trigger('change');
            $activeFormContainer.find('.category-select').val(data.allowed_categories).trigger('change');

            // Boot up Cascading Dropdowns
            initFormLogic(data);
        });
    });

    // Checkbox bulk bar
    function updateBulkBar() {
        $('#selectedCount').text(`${selectedIds.size} Selected`);
        if (selectedIds.size > 0) $('#bulkActionBar').addClass('show'); else $('#bulkActionBar').removeClass('show');
    }
    $(document).on('change', '.row-checkbox', function() {
        if ($(this).is(':checked')) selectedIds.add($(this).val()); else selectedIds.delete($(this).val());
        updateBulkBar();
    });
    $('#checkAllMaster, #btnSelectAllRows').on('click', function() {
        let isChecked = $(this).is(':checked') || $(this).attr('id') === 'btnSelectAllRows';
        $('.row-checkbox').prop('checked', isChecked).each(function() {
            if (isChecked) selectedIds.add($(this).val()); else selectedIds.delete($(this).val());
        }); updateBulkBar();
    });
    $('#btnBulkDelete').on('click', function() {
        Swal.fire({ title: 'Are you sure?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, delete!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('/api/v1/site-allocations/bulk-delete', { ids: Array.from(selectedIds) }, function(res) {
                    Swal.fire('Deleted!', res.message, 'success');
                    selectedIds.clear(); updateBulkBar(); table.ajax.reload(); loadMobileCards();
                });
            }
        });
    });
});
</script>
@endpush