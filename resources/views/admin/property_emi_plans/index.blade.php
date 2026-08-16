@extends('layout.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3 align-items-center">
        <div class="col-md-5">
            <h4 class="mb-0"><i class="fas fa-file-invoice-dollar text-brand-primary"></i> Property EMI Plans</h4>
        </div>
        <div class="col-md-7 text-md-end mt-2 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
            <button class="btn btn-success secured-item" data-permission="p_emi_export" id="exportBtn"><i class="fas fa-file-excel"></i> Export</button>
            <button class="btn btn-info secured-item text-white" data-permission="p_emi_print" id="printBtn"><i class="fas fa-print"></i> Print</button>
            <button class="btn btn-primary secured-item" data-permission="p_emi_add_direct" onclick="openModal('direct')"><i class="fas fa-plus"></i> Add</button>
            <button class="btn btn-warning secured-item" data-permission="p_emi_add_request" onclick="openModal('request')"><i class="fas fa-paper-plane"></i> Request</button>
            <button class="btn btn-danger secured-item" data-permission="p_emi_delete" id="bulkDeleteBtn" style="display:none;"><i class="fas fa-trash"></i> Delete Selected</button>
        </div>
    </div>

    <div class="card shadow-sm border-0 d-none d-md-block">
        <div class="card-body">
            <table class="table table-hover table-bordered w-100" id="propertyEmiPlansTable">
                <thead class="table-light">
                    <tr>
                        <th width="5%"><input type="checkbox" id="selectAll"></th>
                        <th>Plan Name</th>
                        <th>Validity</th>
                        <th>Tenure / DP%</th>
                        <th>Discount/SqFt</th>
                        <th>Phase (Branch)</th>
                        <th>Status</th>
                        <th width="12%">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="d-md-none mt-3" id="mobileCardsContainer"></div>
    <div class="position-fixed bottom-0 start-50 translate-middle-x mb-5 pb-4 z-3" id="mobileFloatingAction" style="display: none; width: max-content;">
        <button class="btn btn-danger rounded-pill shadow-lg secured-item px-4" data-permission="p_emi_delete" id="mobileBulkDeleteBtn"><i class="fas fa-trash me-2"></i> Delete (<span id="mobileSelectedCount">0</span>)</button>
    </div>
</div>

<div class="modal fade" id="propertyEmiPlanModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="propertyEmiPlanForm">
                <div class="modal-header"><h5 class="modal-title" id="modalTitle">Add EMI Plan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div id="scopeContainer" class="row">
                        <div class="col-md-6 mb-3 secured-item" data-permission="public" id="companyWrapper" style="display:none;">
                            <div class="d-flex justify-content-between mb-1"><label class="form-label mb-0">Select Company</label><div><button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="selectAll('#company_id')">All</button> <button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="clearAll('#company_id')">Clear</button></div></div>
                            <select class="form-control select2-modal" id="company_id" name="company_id[]" multiple="multiple" style="width:100%;"></select>
                        </div>
                        <div class="col-md-6 mb-3 secured-item" data-permission="public" id="branchWrapper" style="display:none;">
                            <div class="d-flex justify-content-between mb-1"><label class="form-label mb-0">Select Branch</label><div><button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="selectAll('#branch_id')">All</button> <button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="clearAll('#branch_id')">Clear</button></div></div>
                            <select class="form-control select2-modal" id="branch_id" name="branch_id[]" multiple="multiple" style="width:100%;"></select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1"><label class="form-label mb-0">Select Phase <span class="text-danger">*</span></label><div><button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="selectAll('#phase_id')">Select All</button> <button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="clearAll('#phase_id')">Clear All</button></div></div>
                        <select class="form-control select2-modal" id="phase_id" name="phase_id[]" multiple="multiple" required style="width:100%;"></select>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label">Plan Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="plan_name" name="plan_name" placeholder="e.g., 36 Months EMI, Downpayment Plan" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tenure (Months) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="emi_tenure" name="emi_tenure" value="0" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Discount/SqFt (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="rate_discount_per_sqft" name="rate_discount_per_sqft" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Downpayment % <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="downpayment_percentage" name="downpayment_percentage" placeholder="e.g., 25" min="0" max="100" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" id="saveBtn">Save</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    let table, globalContext = {}, allPhasesData = [];

    $(document).ready(function() {
        fetchContextAndSetup();
        $('.select2-modal').select2({ dropdownParent: $('#propertyEmiPlanModal'), placeholder: "Search and Select...", allowClear: true, width: '100%' });

        table = $('#propertyEmiPlansTable').DataTable({
            processing: true, serverSide: true,
            ajax: { url: '/api/v1/property-emi-plans', type: 'GET' },
            columns: [
                { data: 'id', orderable: false, searchable: false, render: data => `<input type="checkbox" class="row-checkbox" value="${data}">` },
                { data: 'plan_name', render: data => `<span class="fw-bold">${data}</span>` },
                { 
                    data: 'start_date', 
                    render: function(data, type, row) { 
                        return `<span class="text-primary">${data}</span> to <br><span class="text-danger">${row.end_date || 'Ongoing'}</span>`; 
                    } 
                },
                { data: 'emi_tenure', render: (data, type, row) => `${data} Months <br><small class="text-primary fw-bold">DP: ${row.downpayment_percentage}%</small>` },
                { data: 'rate_discount_per_sqft', render: data => `<span class="text-danger fw-bold">₹${data} Off</span>` },
                { 
                    data: 'phase',
                    render: function(data, type, row) {
                        let phaseName = data ? data.phase_name : 'N/A';
                        let branchName = row.branch ? row.branch.branch_name : 'HO';
                        return `${phaseName} <br><small class="text-muted">(${branchName})</small>`;
                    }
                },
                { data: 'status', render: data => `<span class="badge ${data === 'active' ? 'bg-success' : (data === 'pending' ? 'bg-warning' : 'bg-danger')}">${data.toUpperCase()}</span>` },
                {
                    data: null, orderable: false,
                    render: function(data, type, row) {
                        let buttons = '', isGod = window.userGodMode || false, perms = window.userPerms || [];
                        let rowDataStr = encodeURIComponent(JSON.stringify(row));
                        if (row.status === 'pending') {
                            if (isGod || perms.includes('p_emi_approve')) buttons += `<button type="button" class="btn btn-sm btn-success me-1" onclick="actionApprove(${row.id})"><i class="fas fa-check"></i></button>`;
                            if (isGod || perms.includes('p_emi_reject')) buttons += `<button type="button" class="btn btn-sm btn-danger me-1" onclick="actionReject(${row.id})"><i class="fas fa-times"></i></button>`;
                        }
                        if (isGod || perms.includes('p_emi_edit')) buttons += `<button type="button" class="btn btn-sm btn-info me-1 text-white" onclick="editRow(this)" data-row="${rowDataStr}"><i class="fas fa-edit"></i></button>`;
                        return buttons;
                    }
                }
            ],
            drawCallback: function(settings) {
                if(typeof window.applyPermissions === 'function') window.applyPermissions();
                let records = this.api().rows({page: 'current'}).data();
                let mobileHtml = records.length === 0 ? `<div class="alert alert-secondary text-center">No Records found.</div>` : '';
                records.each(function(row) {
                    let phaseName = row.phase ? row.phase.phase_name : 'N/A';
                    let branchName = row.branch ? row.branch.branch_name : 'HO';
                    let rowDataStr = encodeURIComponent(JSON.stringify(row));
                    let isGod = window.userGodMode || false, perms = window.userPerms || [];
                    let buttons = '';
                    if (row.status === 'pending') {
                        if (isGod || perms.includes('p_emi_approve')) buttons += `<button type="button" class="btn btn-sm btn-success me-1" onclick="actionApprove(${row.id})"><i class="fas fa-check"></i></button>`;
                        if (isGod || perms.includes('p_emi_reject')) buttons += `<button type="button" class="btn btn-sm btn-danger me-1" onclick="actionReject(${row.id})"><i class="fas fa-times"></i></button>`;
                    }
                    if (isGod || perms.includes('p_emi_edit')) buttons += `<button type="button" class="btn btn-sm btn-info me-1 text-white" onclick="editRow(this)" data-row="${rowDataStr}"><i class="fas fa-edit"></i></button>`;
                    
                    mobileHtml += `<div class="card shadow-sm border-0 mb-3"><div class="card-body"><div class="d-flex justify-content-between align-items-start mb-2"><div class="d-flex align-items-center gap-2"><input type="checkbox" class="mobile-row-checkbox form-check-input mt-0" value="${row.id}" style="width: 1.2rem; height: 1.2rem;"><h6 class="mb-0 fw-bold">${row.plan_name}</h6></div><span class="badge ${row.status === 'active' ? 'bg-success' : (row.status === 'pending' ? 'bg-warning' : 'bg-danger')}">${row.status.toUpperCase()}</span></div><div class="text-muted small mb-3"><i class="fas fa-calendar-alt me-1"></i> ${row.start_date} to ${row.end_date || 'Ongoing'}<br><i class="fas fa-clock me-1"></i> Tenure: ${row.emi_tenure} Months | DP: ${row.downpayment_percentage}%<br><i class="fas fa-tag me-1"></i> Discount: <span class="text-danger fw-bold">₹${row.rate_discount_per_sqft} Off</span><br><i class="fas fa-building me-1"></i> ${phaseName} (${branchName})</div><div class="d-flex justify-content-end border-top pt-2">${buttons}</div></div></div>`;
                });
                $('#mobileCardsContainer').html(mobileHtml);
                if(typeof window.applyPermissions === 'function') window.applyPermissions();
            }
        });

        // Cascading Logic
        $('#company_id').on('change', function() { let ids = $(this).val() || []; $('#branch_id, #phase_id').empty().trigger('change'); if(ids.length) loadBranches(ids); });
        $('#branch_id').on('change', function() { let cIds = $('#company_id').val() || (globalContext.company_id ? [globalContext.company_id.toString()] : []); let bIds = $(this).val() || (globalContext.branch_id ? [globalContext.branch_id.toString()] : []); $('#phase_id').empty().trigger('change'); filterAndLoadPhases(cIds, bIds); });

        // Bulk Delete Triggers
        $('#selectAll').on('change', function() { $('.row-checkbox').prop('checked', $(this).prop('checked')); toggleBulkDeleteBtn(); });
        $('#propertyEmiPlansTable').on('change', '.row-checkbox', toggleBulkDeleteBtn);
        $(document).on('change', '.mobile-row-checkbox', function() { let c = $('.mobile-row-checkbox:checked').length; if(c > 0) { $('#mobileSelectedCount').text(c); $('#mobileFloatingAction').fadeIn(); } else $('#mobileFloatingAction').fadeOut(); });
        $('#bulkDeleteBtn, #mobileBulkDeleteBtn').on('click', function() {
            let ids = [];
            if ($(window).width() >= 768) $('.row-checkbox:checked').each(function() { ids.push($(this).val()); }); else $('.mobile-row-checkbox:checked').each(function() { ids.push($(this).val()); });
            if (ids.length === 0) return;
            Swal.fire({ title: 'Are you sure?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#E53E3E', confirmButtonText: 'Yes, delete!' }).then((result) => {
                if (result.isConfirmed) $.post('/api/v1/property-emi-plans/bulk-delete', { ids: ids }, function(res) { Swal.fire('Deleted!', res.message, 'success'); table.ajax.reload(null, false); toggleBulkDeleteBtn(); $('#mobileFloatingAction').fadeOut(); });
            });
        });

        // Form Submission
        $('#propertyEmiPlanForm').on('submit', function(e) {
            e.preventDefault();
            let id = $('#edit_id').val(), url = id ? `/api/v1/property-emi-plans/${id}` : '/api/v1/property-emi-plans', method = id ? 'PUT' : 'POST';
            $.ajax({ url: url, type: method, data: $(this).serialize(), success: function(res) { $('#propertyEmiPlanModal').modal('hide'); Swal.fire('Success', res.message, 'success'); table.ajax.reload(null, false); }, error: function(err) { Swal.fire('Error', err.responseJSON.message || 'Error occurred', 'error'); } });
        });

        // Token Export/Print
        $('#exportBtn').on('click', function() { let p = window.location.pathname.split('/')[1]; let t = localStorage.getItem('token') || localStorage.getItem('admin_token') || localStorage.getItem('emp_token') || ''; window.location.href = `/${p}/property-emi-plans/export?token=${t}`; });
        $('#printBtn').on('click', function() { let p = window.location.pathname.split('/')[1]; let t = localStorage.getItem('token') || localStorage.getItem('admin_token') || localStorage.getItem('emp_token') || ''; window.open(`/${p}/property-emi-plans/print?token=${t}`, '_blank'); });
    });

    window.selectAll = function(selector) { $(selector + ' > option').prop("selected", "selected"); $(selector).trigger("change"); };
    window.clearAll = function(selector) { $(selector).val(null).trigger("change"); };
    function toggleBulkDeleteBtn() { $('#bulkDeleteBtn').toggle($('.row-checkbox:checked').length > 0); }

    function fetchContextAndSetup() {
        $.get('/api/v1/context', function(res) {
            globalContext = res;
            if (res.is_god) { $('#companyWrapper, #branchWrapper').show(); loadCompanies(); } 
            else if (res.is_director) { $('#branchWrapper').show(); loadBranches([res.company_id]); } 
            else cacheAllPhases(res.company_id, res.branch_id);
        });
    }

    function loadCompanies() { $.get('/api/v1/get-active-companies', function(res) { let o = ''; if(res.data) res.data.forEach(c => { o += `<option value="${c.id}">${c.company_name}</option>`; }); $('#company_id').html(o).trigger('change'); }); }
    function loadBranches(cIds) {
        let o = '';
        $('#company_id option:selected').each(function() { let v = $(this).val(); if (v) o += `<option value="HO_${v}">Head Office (${$(this).text()})</option>`; });
        if (!cIds || !cIds.length) { $('#branch_id').html(o).trigger('change'); cacheAllPhases([]); return; }
        Promise.all(cIds.map(id => new Promise(res => $.get(`/api/v1/phases/get-branches/${id}`, r => res(r.data || [])).fail(() => res([]))))).then(results => {
            results.forEach(arr => arr.forEach(b => o += `<option value="${b.id}">${b.branch_name}</option>`));
            $('#branch_id').html(o).trigger('change'); cacheAllPhases(cIds);
        });
    }

    function cacheAllPhases(cIds) { $.get('/api/v1/phases', function(res) { if(res.success && res.data) { allPhasesData = res.data; filterAndLoadPhases(cIds, $('#branch_id').val() || []); } }); }
    function filterAndLoadPhases(cIds, bIds) {
        let o = '';
        allPhasesData.forEach(p => {
            let c = p.company_id ? p.company_id.toString() : '', b = p.branch_id ? p.branch_id.toString() : '';
            if((!cIds.length || cIds.includes(c)) && (!bIds.length || (b === "" && bIds.includes("HO_"+c)) || bIds.includes(b))) o += `<option value="${p.id}">${p.phase_name} (${p.branch ? p.branch.branch_name : 'HO'})</option>`;
        });
        $('#phase_id').html(o).trigger('change');
    }

    window.openModal = function(type) {
        $('#propertyEmiPlanForm')[0].reset(); $('#edit_id').val(''); $('#company_id, #branch_id, #phase_id').val(null).trigger('change');
        $('#modalTitle').text(type === 'direct' ? 'Add EMI Plan' : 'Request EMI Plan');
        $('#saveBtn').text(type === 'direct' ? 'Save' : 'Submit Request');
        $('#propertyEmiPlanModal').modal('show');
    };

    window.editRow = function(btn) {
        let row = JSON.parse(decodeURIComponent($(btn).data('row')));
        $('#propertyEmiPlanForm')[0].reset(); $('#edit_id').val(row.id);
        $('#plan_name').val(row.plan_name); $('#emi_tenure').val(row.emi_tenure); 
        $('#rate_discount_per_sqft').val(row.rate_discount_per_sqft); $('#downpayment_percentage').val(row.downpayment_percentage);
        $('#start_date').val(row.start_date); $('#end_date').val(row.end_date);
        
        let cId = row.company_id, cName = row.company ? row.company.company_name : 'Selected Company';
        let bId = row.branch_id, bName = row.branch ? row.branch.branch_name : `Head Office (${cName})`, bVal = bId ? bId : `HO_${cId}`;
        let pId = row.phase_id, pName = row.phase ? row.phase.phase_name : 'Selected Phase';

        if(!$('#company_id option[value="'+cId+'"]').length) $('#company_id').append(new Option(cName, cId, true, true)); $('#company_id').val([cId]).trigger('change');
        setTimeout(() => {
            if(!$('#branch_id option[value="'+bVal+'"]').length) $('#branch_id').append(new Option(bName, bVal, true, true)); $('#branch_id').val([bVal]).trigger('change');
            setTimeout(() => {
                if(!$('#phase_id option[value="'+pId+'"]').length) $('#phase_id').append(new Option(pName, pId, true, true)); $('#phase_id').val([pId]).trigger('change');
            }, 300);
        }, 300);
        $('#modalTitle').text('Edit EMI Plan'); $('#saveBtn').text('Update'); $('#propertyEmiPlanModal').modal('show');
    };

    window.actionApprove = function(id) { $.post(`/api/v1/property-emi-plans/${id}/approve`, res => { table.ajax.reload(null, false); Swal.fire('Approved!', res.message, 'success'); }); };
    window.actionReject = function(id) { $.post(`/api/v1/property-emi-plans/${id}/reject`, res => { table.ajax.reload(null, false); Swal.fire('Rejected!', res.message, 'success'); }); };
</script>
@endpush