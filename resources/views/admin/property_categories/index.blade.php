@extends('layout.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3 align-items-center">
        <div class="col-md-5">
            <h4 class="mb-0"><i class="fas fa-tags text-brand-primary"></i> Property Categories</h4>
        </div>
        <div class="col-md-7 text-md-end mt-2 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
            <button class="btn btn-success secured-item" data-permission="p_cat_export" id="exportBtn"><i class="fas fa-file-excel"></i> Export</button>
            <button class="btn btn-info secured-item text-white" data-permission="p_cat_print" id="printBtn"><i class="fas fa-print"></i> Print</button>
            <button class="btn btn-primary secured-item" data-permission="p_cat_add_direct" onclick="openModal('direct')"><i class="fas fa-plus"></i> Add</button>
            <button class="btn btn-warning secured-item" data-permission="p_cat_add_request" onclick="openModal('request')"><i class="fas fa-paper-plane"></i> Request</button>
            <button class="btn btn-danger secured-item" data-permission="p_cat_delete" id="bulkDeleteBtn" style="display:none;"><i class="fas fa-trash"></i> Delete Selected</button>
        </div>
    </div>

    <!-- Desktop Datatable -->
    <div class="card shadow-sm border-0 d-none d-md-block">
        <div class="card-body">
            <table class="table table-hover table-bordered w-100" id="propertyCategoriesTable">
                <thead class="table-light">
                    <tr>
                        <th width="5%"><input type="checkbox" id="selectAll"></th>
                        <th>Category Name</th>
                        <th>Property Type</th>
                        <th>Phase (Branch)</th>
                        <th>Status</th>
                        <th width="15%">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Cards -->
    <div class="d-md-none mt-3" id="mobileCardsContainer"></div>

    <!-- Mobile FAB -->
    <div class="position-fixed bottom-0 start-50 translate-middle-x mb-5 pb-4 z-3" id="mobileFloatingAction" style="display: none; width: max-content;">
        <button class="btn btn-danger rounded-pill shadow-lg secured-item px-4" data-permission="p_cat_delete" id="mobileBulkDeleteBtn">
            <i class="fas fa-trash me-2"></i> Delete Selected (<span id="mobileSelectedCount">0</span>)
        </button>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="propertyCategoryModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="propertyCategoryForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Property Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div id="scopeContainer">
                        <div class="mb-3 secured-item" data-permission="public" id="companyWrapper" style="display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">Select Company</label>
                                <div><button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="selectAll('#company_id')">Select All</button> <button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="clearAll('#company_id')">Clear All</button></div>
                            </div>
                            <select class="form-control select2-modal" id="company_id" name="company_id[]" multiple="multiple" style="width:100%;"></select>
                        </div>
                        <div class="mb-3 secured-item" data-permission="public" id="branchWrapper" style="display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">Select Branch</label>
                                <div><button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="selectAll('#branch_id')">Select All</button> <button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="clearAll('#branch_id')">Clear All</button></div>
                            </div>
                            <select class="form-control select2-modal" id="branch_id" name="branch_id[]" multiple="multiple" style="width:100%;"></select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Select Phase</label>
                            <div><button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="selectAll('#phase_id')">Select All</button> <button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="clearAll('#phase_id')">Clear All</button></div>
                        </div>
                        <select class="form-control select2-modal" id="phase_id" name="phase_id[]" multiple="multiple" style="width:100%;"></select>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Select Property Type <span class="text-danger">*</span></label>
                            <div><button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="selectAll('#property_type_id')">Select All</button> <button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="clearAll('#property_type_id')">Clear All</button></div>
                        </div>
                        <select class="form-control select2-modal" id="property_type_id" name="property_type_id[]" multiple="multiple" required style="width:100%;"></select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="category_name" placeholder="e.g., Plot, Flat, Villa" required>
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

        $('.select2-modal').select2({ dropdownParent: $('#propertyCategoryModal'), placeholder: "Search and Select...", allowClear: true, width: '100%' });

        table = $('#propertyCategoriesTable').DataTable({
            processing: true, serverSide: true,
            ajax: { url: '/api/v1/property-categories', type: 'GET' },
            columns: [
                { data: 'id', orderable: false, searchable: false, render: data => `<input type="checkbox" class="row-checkbox" value="${data}">` },
                { data: 'category_name' },
                { data: 'property_type.type_name', defaultContent: 'N/A' },
                { 
                    data: 'property_type',
                    render: function(data, type, row) {
                        let phaseName = (data && data.phase) ? data.phase.phase_name : 'N/A';
                        let branchName = row.branch ? row.branch.branch_name : 'HO';
                        return `${phaseName} <small class="text-muted">(${branchName})</small>`;
                    }
                },
                { data: 'status', render: data => `<span class="badge ${data === 'active' ? 'bg-success' : (data === 'pending' ? 'bg-warning' : 'bg-danger')}">${data.toUpperCase()}</span>` },
                {
                    data: null, orderable: false,
                    render: function(data, type, row) {
                        let buttons = '', isGod = window.userGodMode || false, perms = window.userPerms || [];
                        let rowDataStr = encodeURIComponent(JSON.stringify(row));

                        if (row.status === 'pending') {
                            if (isGod || perms.includes('p_cat_approve')) buttons += `<button type="button" class="btn btn-sm btn-success me-1" onclick="actionApprove(${row.id})"><i class="fas fa-check"></i></button>`;
                            if (isGod || perms.includes('p_cat_reject')) buttons += `<button type="button" class="btn btn-sm btn-danger me-1" onclick="actionReject(${row.id})"><i class="fas fa-times"></i></button>`;
                        }
                        if (isGod || perms.includes('p_cat_edit')) buttons += `<button type="button" class="btn btn-sm btn-info me-1 text-white" onclick="editRow(this)" data-row="${rowDataStr}"><i class="fas fa-edit"></i></button>`;
                        return buttons;
                    }
                }
            ],
            drawCallback: function(settings) {
                if(typeof window.applyPermissions === 'function') window.applyPermissions();
                
                // Mobile View Rendering
                let records = this.api().rows({page: 'current'}).data();
                let mobileHtml = records.length === 0 ? `<div class="alert alert-secondary text-center">No Records found.</div>` : '';
                records.each(function(row) {
                    let phaseName = (row.property_type && row.property_type.phase) ? row.property_type.phase.phase_name : 'N/A';
                    let branchName = row.branch ? row.branch.branch_name : 'HO';
                    let typeName = row.property_type ? row.property_type.type_name : 'N/A';
                    let rowDataStr = encodeURIComponent(JSON.stringify(row));
                    let isGod = window.userGodMode || false, perms = window.userPerms || [];
                    let buttons = '';

                    if (row.status === 'pending') {
                        if (isGod || perms.includes('p_cat_approve')) buttons += `<button type="button" class="btn btn-sm btn-success me-1" onclick="actionApprove(${row.id})"><i class="fas fa-check"></i></button>`;
                        if (isGod || perms.includes('p_cat_reject')) buttons += `<button type="button" class="btn btn-sm btn-danger me-1" onclick="actionReject(${row.id})"><i class="fas fa-times"></i></button>`;
                    }
                    if (isGod || perms.includes('p_cat_edit')) buttons += `<button type="button" class="btn btn-sm btn-info me-1 text-white" onclick="editRow(this)" data-row="${rowDataStr}"><i class="fas fa-edit"></i></button>`;
                    
                    mobileHtml += `<div class="card shadow-sm border-0 mb-3"><div class="card-body"><div class="d-flex justify-content-between align-items-start mb-2"><div class="d-flex align-items-center gap-2"><input type="checkbox" class="mobile-row-checkbox form-check-input mt-0" value="${row.id}" style="width: 1.2rem; height: 1.2rem;"><h6 class="mb-0 fw-bold">${row.category_name}</h6></div><span class="badge ${row.status === 'active' ? 'bg-success' : (row.status === 'pending' ? 'bg-warning' : 'bg-danger')}">${row.status.toUpperCase()}</span></div><div class="text-muted small mb-3"><i class="fas fa-tags me-1"></i> Type: ${typeName} <br><i class="fas fa-building me-1"></i> Phase: ${phaseName} (${branchName})</div><div class="d-flex justify-content-end border-top pt-2">${buttons}</div></div></div>`;
                });
                $('#mobileCardsContainer').html(mobileHtml);
                if(typeof window.applyPermissions === 'function') window.applyPermissions();
            }
        });

        // 🟢 CASCADING DROPDOWNS
        $('#company_id').on('change', function() { let ids = $(this).val() || []; $('#branch_id, #phase_id, #property_type_id').empty().trigger('change'); if(ids.length) loadBranches(ids); });
        $('#branch_id').on('change', function() { let cIds = $('#company_id').val() || (globalContext.company_id ? [globalContext.company_id.toString()] : []); let bIds = $(this).val() || (globalContext.branch_id ? [globalContext.branch_id.toString()] : []); $('#property_type_id').empty().trigger('change'); filterAndLoadPhases(cIds, bIds); });
        $('#phase_id').on('change', function() { let pIds = $(this).val() || []; $('#property_type_id').empty().trigger('change'); if(pIds.length) loadTypes(pIds); });

        // Checkboxes & Bulk Delete
        $('#selectAll').on('change', function() { $('.row-checkbox').prop('checked', $(this).prop('checked')); toggleBulkDeleteBtn(); });
        $('#propertyCategoriesTable').on('change', '.row-checkbox', toggleBulkDeleteBtn);
        $(document).on('change', '.mobile-row-checkbox', function() { let c = $('.mobile-row-checkbox:checked').length; if(c > 0) { $('#mobileSelectedCount').text(c); $('#mobileFloatingAction').fadeIn(); } else $('#mobileFloatingAction').fadeOut(); });

        $('#bulkDeleteBtn, #mobileBulkDeleteBtn').on('click', function() {
            let ids = [];
            if ($(window).width() >= 768) $('.row-checkbox:checked').each(function() { ids.push($(this).val()); });
            else $('.mobile-row-checkbox:checked').each(function() { ids.push($(this).val()); });
            if (ids.length === 0) return;

            Swal.fire({ title: 'Are you sure?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#E53E3E', confirmButtonText: 'Yes, delete!' }).then((result) => {
                if (result.isConfirmed) $.post('/api/v1/property-categories/bulk-delete', { ids: ids }, function(res) { Swal.fire('Deleted!', res.message, 'success'); table.ajax.reload(null, false); toggleBulkDeleteBtn(); $('#mobileFloatingAction').fadeOut(); });
            });
        });

        // Form Submit
        $('#propertyCategoryForm').on('submit', function(e) {
            e.preventDefault();
            let id = $('#edit_id').val(), url = id ? `/api/v1/property-categories/${id}` : '/api/v1/property-categories', method = id ? 'PUT' : 'POST';
            $.ajax({ url: url, type: method, data: $(this).serialize(), success: function(res) { $('#propertyCategoryModal').modal('hide'); Swal.fire('Success', res.message, 'success'); table.ajax.reload(null, false); }, error: function() { Swal.fire('Error', 'Something went wrong!', 'error'); } });
        });

        // Token Export/Print
        $('#exportBtn').on('click', function() { let p = window.location.pathname.split('/')[1]; let t = localStorage.getItem('token') || localStorage.getItem('admin_token') || localStorage.getItem('emp_token') || ''; window.location.href = `/${p}/property-categories/export?token=${t}`; });
        $('#printBtn').on('click', function() { let p = window.location.pathname.split('/')[1]; let t = localStorage.getItem('token') || localStorage.getItem('admin_token') || localStorage.getItem('emp_token') || ''; window.open(`/${p}/property-categories/print?token=${t}`, '_blank'); });
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

    function loadTypes(pIds) {
        let o = '';
        Promise.all(pIds.map(id => new Promise(res => $.get(`/api/v1/property-dependencies/types/${id}`, r => res(r.data || [])).fail(() => res([]))))).then(results => {
            results.forEach(arr => arr.forEach(t => o += `<option value="${t.id}">${t.type_name}</option>`));
            $('#property_type_id').html(o).trigger('change');
        });
    }

    window.openModal = function(type) {
        $('#propertyCategoryForm')[0].reset(); $('#edit_id').val(''); $('#company_id, #branch_id, #phase_id, #property_type_id').val(null).trigger('change');
        $('#modalTitle').text(type === 'direct' ? 'Add Property Category' : 'Request Property Category');
        $('#saveBtn').text(type === 'direct' ? 'Save' : 'Submit Request');
        $('#propertyCategoryModal').modal('show');
    };

    window.editRow = function(btn) {
        let row = JSON.parse(decodeURIComponent($(btn).data('row')));
        $('#propertyCategoryForm')[0].reset(); $('#edit_id').val(row.id); $('#category_name').val(row.category_name);
        
        let cId = row.company_id, cName = row.company ? row.company.company_name : 'Selected Company';
        let bId = row.branch_id, bName = row.branch ? row.branch.branch_name : `Head Office (${cName})`, bVal = bId ? bId : `HO_${cId}`;
        let pId = row.property_type ? row.property_type.phase_id : '', pName = (row.property_type && row.property_type.phase) ? row.property_type.phase.phase_name : 'Selected Phase';
        let tId = row.property_type_id, tName = row.property_type ? row.property_type.type_name : 'Selected Type';

        if(!$('#company_id option[value="'+cId+'"]').length) $('#company_id').append(new Option(cName, cId, true, true)); $('#company_id').val([cId]).trigger('change');
        setTimeout(() => {
            if(!$('#branch_id option[value="'+bVal+'"]').length) $('#branch_id').append(new Option(bName, bVal, true, true)); $('#branch_id').val([bVal]).trigger('change');
            setTimeout(() => {
                if(!$('#phase_id option[value="'+pId+'"]').length) $('#phase_id').append(new Option(pName, pId, true, true)); $('#phase_id').val([pId]).trigger('change');
                setTimeout(() => {
                    if(!$('#property_type_id option[value="'+tId+'"]').length) $('#property_type_id').append(new Option(tName, tId, true, true)); $('#property_type_id').val([tId]).trigger('change');
                }, 300);
            }, 300);
        }, 300);
        $('#modalTitle').text('Edit Property Category'); $('#saveBtn').text('Update'); $('#propertyCategoryModal').modal('show');
    };

    window.actionApprove = function(id) { $.post(`/api/v1/property-categories/${id}/approve`, res => { table.ajax.reload(null, false); Swal.fire('Approved!', res.message, 'success'); }); };
    window.actionReject = function(id) { $.post(`/api/v1/property-categories/${id}/reject`, res => { table.ajax.reload(null, false); Swal.fire('Rejected!', res.message, 'success'); }); };
</script>
@endpush