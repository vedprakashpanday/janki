@extends('layout.app')

@section('content')
<style>
    .dynamic-attr-box { background: #f8f9fa; border-left: 4px solid #0d6efd; padding: 15px; border-radius: 5px; }
    .desktop-view { display: block; }
    .mobile-view { display: none; }
    
    @media (max-width: 768px) {
        .desktop-view { display: none !important; }
        .mobile-view { display: block; }
        .stock-card { background: #fff; border-radius: 8px; padding: 15px; margin-bottom: 15px; border: 1px solid #e2e8f0; position: relative; }
        .stock-card .card-checkbox { position: absolute; top: 15px; right: 15px; transform: scale(1.3); }
        .stock-card-actions { margin-top: 10px; border-top: 1px solid #edf2f7; padding-top: 10px; display: flex; justify-content: flex-end; gap: 8px; }
    }

    /* Floating Action Bar */
    #floatingActionBar {
        position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%);
        background: #1A365D; color: white; padding: 10px 25px; border-radius: 30px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2); display: none; z-index: 1050; align-items: center; gap: 15px;
    }
    @media (min-width: 768px) { #floatingActionBar { bottom: 30px; } }
</style>

<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <h4 class="mb-0 fw-bold text-dark"><i class="fas fa-cart-plus text-success me-2"></i> Daily Stock Entry</h4>
    </div>

    <!-- SMART ENTRY FORM -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white pt-3 pb-2 border-bottom"><h6 class="fw-bold mb-0">New Stock Inward</h6></div>
        <div class="card-body">
            <form id="stockEntryForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Company <span class="text-danger">*</span></label>
                        <select class="form-select select2-setup" id="company_id" name="company_id" required><option value="">Select...</option></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Branch</label>
                        <select class="form-select select2-setup" id="branch_id" name="branch_id"><option value="">Select Company First...</option></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Stock Incharge <span class="text-danger">*</span></label>
                        <select class="form-select select2-setup" id="incharge_id" name="incharge_id" required><option value="">Search Employee...</option></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Purchase Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="purchase_date" id="purchase_date" required value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small text-muted">Category <span class="text-danger">*</span></label>
                        <select class="form-select select2-setup" id="category_id" name="category_id" required><option value="">Select Category...</option></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Item Type</label>
                        <select class="form-select select2-setup" id="type_id" name="type_id"><option value="">Select Category First...</option></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Brand / Make</label>
                        <select class="form-select select2-setup" id="brand_id" name="brand_id"><option value="">Select Brand...</option></select>
                    </div>

                    <div class="col-12" id="dynamicAttributesWrapper" style="display: none;">
                        <div class="dynamic-attr-box">
                            <h6 class="fw-bold text-primary mb-3"><i class="fas fa-sliders-h me-1"></i> Item Specifications</h6>
                            <div class="row g-3" id="dynamicAttributesRow"></div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small text-muted">Item Name / Model <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="item_name" placeholder="e.g. Galaxy S24 Ultra" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Unit Price (₹)</label>
                        <input type="number" step="0.01" class="form-control" name="price" placeholder="0.00">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Total Qty <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="total_quantity" required value="1" min="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Remarks</label>
                        <input type="text" class="form-control" name="remarks" placeholder="Any specific details...">
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="button" class="btn btn-light me-2" onclick="document.getElementById('stockEntryForm').reset(); $('.select2-setup').val('').trigger('change');">Clear</button>
                        <button type="submit" class="btn btn-success px-4" id="btnSaveEntry" style="display: none;">
                            <i class="fas fa-check-circle me-1"></i> <span id="btnSaveText">Save Entry</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- TODAY'S ENTRIES TABLE -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white pt-3 pb-2 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-clock me-1"></i> Today's Entries ({{ date('d-M-Y') }})</h6>
        </div>
        <div class="card-body p-0 p-md-3">
            <div class="desktop-view table-responsive">
                <table class="table table-hover align-middle w-100" id="todayTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" class="form-check-input select-all-desktop"></th>
                            <th>Item Name</th>
                            <th>Category & Type</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
            <div class="mobile-view p-3">
                <div class="d-flex mb-2"><input class="form-check-input select-all-mobile me-2" type="checkbox"><label class="fw-bold">Select All</label></div>
                <div id="mobileCards"></div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Bar -->
<div id="floatingActionBar">
    <span class="fw-bold"><span id="selectedCount">0</span> Selected</span>
    <button class="btn btn-sm btn-danger rounded-pill px-3" id="btnBulkDelete"><i class="fas fa-trash me-1"></i> Delete</button>
    <button class="btn btn-sm btn-light rounded-circle" id="btnClearSelection" style="width:30px;height:30px;padding:0;"><i class="fas fa-times text-dark"></i></button>
</div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $('.select2-setup').select2({ theme: 'bootstrap-5', width: '100%' });
        
        let permissions = {};
        let selectedIds = new Set();

        // 1. RBAC UI
        $.get('/api/v1/context', function(res) {
            let perms = res.permissions || [];
            let isAdmin = res.is_god || res.is_admin;
            let canAddDirect = isAdmin || perms.includes('stock_daily_add_direct');
            let canAddRequest = isAdmin || perms.includes('stock_daily_add_request');

            if (canAddDirect) {
                $('#btnSaveEntry').show(); $('#btnSaveText').text('Save Entry');
            } else if (canAddRequest) {
                $('#btnSaveEntry').show().removeClass('btn-success').addClass('btn-warning text-dark');
                $('#btnSaveEntry i').removeClass('fa-check-circle').addClass('fa-paper-plane');
                $('#btnSaveText').text('Submit Request');
            } else {
                $('#stockEntryForm input, #stockEntryForm select, #stockEntryForm textarea, #stockEntryForm button').prop('disabled', true);
                $('#dynamicAttributesWrapper').hide();
            }
        });
// Load dropdowns safely
        $.get('/api/v1/stocks/search-companies', res => {
            let html = '<option value="">Select Company...</option>';
            let list = res.data || res || []; // Fallback safety
            list.forEach(c => html += `<option value="${c.id}">${c.company_name}</option>`);
            $('#company_id').html(html);
        });

        $.get('/api/v1/entry/employees', res => {
            let html = '<option value="">Search Employee...</option>';
            let list = res.data || res || [];
            list.forEach(e => html += `<option value="${e.id}">${e.full_name} (${e.member_id})</option>`);
            $('#incharge_id').html(html);
        });

        $.get('/api/v1/stocks/masters/dropdown-categories', res => {
            let html = '<option value="">Select Category...</option>';
            let list = res.data || res || [];
            list.forEach(c => html += `<option value="${c.id}">${c.name}</option>`);
            $('#category_id').html(html);
        });

        $.get('/api/v1/stocks/masters/brands', res => {
            let html = '<option value="">Select Brand...</option>';
            let list = res.data || res.data || res || [];
            if(Array.isArray(list)) {
                list.forEach(b => html += `<option value="${b.id}">${b.name}</option>`);
            }
            $('#brand_id').html(html);
        });

        $('#company_id').change(function() {
            let compId = $(this).val();
            $('#branch_id').html('<option value="">Loading...</option>');
            $.get('/api/v1/stocks/search-branches', { company_id: compId }, res => {
                let html = '';
                res.data.forEach(b => html += `<option value="${b.id}">${b.branch_name}</option>`);
                $('#branch_id').html(html);
            });
        });

      $('#category_id').change(function() {
            let catId = $(this).val();
            let typeSelect = $('#type_id');
            let attrWrapper = $('#dynamicAttributesWrapper');
            let attrRow = $('#dynamicAttributesRow');

            if(!catId) { typeSelect.html('<option value="">Select Category First...</option>'); attrWrapper.hide(); attrRow.empty(); return; }

            typeSelect.html('<option value="">Loading...</option>');
            $.get(`/api/v1/entry/category-dependencies/${catId}`, res => {
                let typeHtml = '<option value="">Select Type...</option>';
                let typesList = res.types || [];
                typesList.forEach(t => typeHtml += `<option value="${t.id}">${t.name}</option>`);
                typeSelect.html(typeHtml);

                let attributesList = res.attributes || [];
                if(attributesList.length > 0) {
                    let attrHtml = '';
                    attributesList.forEach(attr => {
                        attrHtml += `<div class="col-md-3"><label class="form-label small fw-bold">${attr.name}</label>
                                     <select class="form-select select2-dynamic-attr" name="attributes[${attr.id}]">
                                     <option value="">Select ${attr.name}...</option>`;
                        
                        let optionsList = attr.options || [];
                        optionsList.forEach(opt => { attrHtml += `<option value="${opt.id}">${opt.value}</option>`; });
                        
                        attrHtml += `</select></div>`;
                    });
                    attrRow.html(attrHtml);
                    attrWrapper.slideDown();
                    $('.select2-dynamic-attr').select2({ theme: 'bootstrap-5', width: '100%' });
                } else { attrWrapper.slideUp(); attrRow.empty(); }
            });
        });

        $('#stockEntryForm').submit(function(e) {
            e.preventDefault();
            let btn = $('#btnSaveEntry'); let orig = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            
            $.ajax({
                url: '/api/v1/entry/store', type: 'POST', data: $(this).serialize(),
                success: function(res) {
                    Swal.fire({toast:true, position:'top-end', icon:'success', title: res.message, showConfirmButton: false, timer: 2000});
                    $('#stockEntryForm')[0].reset(); $('.select2-setup').val('').trigger('change');
                    $('#dynamicAttributesWrapper').slideUp();
                    todayTable.draw(); btn.prop('disabled', false).html(orig);
                },
                error: function(err) {
                    btn.prop('disabled', false).html(orig);
                    Swal.fire('Error', err.responseJSON.message || 'Check required fields', 'error');
                }
            });
        });

        // DataTable
        let todayTable = $('#todayTable').DataTable({
            processing: true, serverSide: true, searching: false, lengthChange: false,
            ajax: { 
                url: '/api/v1/entry/today', type: "GET",
                dataSrc: function(json) { permissions = json.permissions; return json.data; }
            },
            columns: [
                { data: 'id', orderable: false, render: d => `<input type="checkbox" class="form-check-input row-checkbox" value="${d}" ${selectedIds.has(d)?'checked':''}>` },
                { data: 'item_name', render: d => `<strong>${d}</strong>` },
                { data: 'category_id', render: (d,t,r) => `<span class="fw-bold text-primary">${r.category?.name || '-'}</span>${r.type?'<br><small class="text-muted">'+r.type.name+'</small>':''}` },
                { data: 'total_quantity', render: d => `<span class="badge bg-light text-dark border">${d} Qty</span>` },
                { data: 'status', render: d => `<span class="badge bg-${d==='active'?'success':(d==='pending'?'warning text-dark':'danger')}">${d.toUpperCase()}</span>` },
                { data: 'id', orderable: false, className: 'text-end', render: (d, t, r) => {
                    let btns = '';
                    if (r.status === 'pending') {
                        if (permissions.can_appr) btns += `<button class="btn btn-sm btn-success btn-action me-1" data-id="${d}" data-action="approve"><i class="fas fa-check"></i></button>`;
                        if (permissions.can_rej) btns += `<button class="btn btn-sm btn-danger btn-action me-1" data-id="${d}" data-action="reject"><i class="fas fa-times"></i></button>`;
                    }
                    if (permissions.can_edit) btns += `<button class="btn btn-sm btn-light text-primary btn-edit me-1" data-id="${d}"><i class="fas fa-edit"></i></button>`;
                    if (permissions.can_delete) btns += `<button class="btn btn-sm btn-light text-danger btn-delete" data-id="${d}"><i class="fas fa-trash"></i></button>`;
                    return btns || '-';
                }}
            ],
            drawCallback: function() {
                let data = this.api().rows({page: 'current'}).data().toArray();
                let html = data.length === 0 ? '<div class="text-center text-muted">No entries today</div>' : '';
                data.forEach(r => {
                    let checked = selectedIds.has(r.id) ? 'checked' : '';
                    let btns = '';
                    if (r.status === 'pending') {
                        if (permissions.can_appr) btns += `<button class="btn btn-sm btn-success btn-action me-1" data-id="${r.id}" data-action="approve">Approve</button>`;
                        if (permissions.can_rej) btns += `<button class="btn btn-sm btn-danger btn-action me-1" data-id="${r.id}" data-action="reject">Reject</button>`;
                    }
                    if (permissions.can_edit) btns += `<button class="btn btn-sm btn-outline-primary btn-edit me-1" data-id="${r.id}">Edit</button>`;
                    if (permissions.can_delete) btns += `<button class="btn btn-sm btn-outline-danger btn-delete" data-id="${r.id}">Delete</button>`;

                    html += `
                        <div class="stock-card">
                            <input type="checkbox" class="form-check-input row-checkbox card-checkbox" value="${r.id}" ${checked}>
                            <h6 class="fw-bold">${r.item_name}</h6>
                            <div class="small text-muted mb-2">Qty: ${r.total_quantity} | Status: <strong>${r.status.toUpperCase()}</strong></div>
                            <div class="stock-card-actions">${btns}</div>
                        </div>`;
                });
                $('#mobileCards').html(html);
                syncCheckboxes();
            }
        });

        // Checkbox & Bulk Actions
        $(document).on('change', '.row-checkbox', function() {
            let id = parseInt($(this).val());
            $(this).is(':checked') ? selectedIds.add(id) : selectedIds.delete(id);
            syncCheckboxes();
        });

        $('.select-all-desktop, .select-all-mobile').change(function() {
            let isChecked = $(this).is(':checked');
            $('.row-checkbox').each(function() {
                let id = parseInt($(this).val());
                isChecked ? selectedIds.add(id) : selectedIds.delete(id);
            });
            syncCheckboxes();
        });

        $('#btnClearSelection').click(() => { selectedIds.clear(); syncCheckboxes(); });

        $('#btnBulkDelete').click(function() {
            let ids = Array.from(selectedIds);
            Swal.fire({ title: 'Delete Selected?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, delete!' }).then((res) => {
                if (res.isConfirmed) {
                    $.post('/api/v1/entry/bulk-delete', { ids: ids }, function(res) {
                        Swal.fire('Deleted!', res.message, 'success');
                        selectedIds.clear(); syncCheckboxes(); todayTable.draw(false);
                    }).fail(() => Swal.fire('Error', 'Failed to delete', 'error'));
                }
            });
        });

        function syncCheckboxes() {
            $('.row-checkbox').each(function() {
                $(this).prop('checked', selectedIds.has(parseInt($(this).val())));
            });
            let count = selectedIds.size;
            $('#selectedCount').text(count);
            if(count > 0) $('#floatingActionBar').css('display', 'flex');
            else $('#floatingActionBar').css('display', 'none');
            
            let total = $('.row-checkbox').length;
            $('.select-all-desktop, .select-all-mobile').prop('checked', total > 0 && total === count);
        }

        // Single Delete
        $(document).on('click', '.btn-delete', function() {
            let id = $(this).data('id');
            Swal.fire({ title: 'Delete Entry?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33' }).then((res) => {
                if (res.isConfirmed) {
                    $.post('/api/v1/entry/bulk-delete', { ids: [id] }, () => {
                        Swal.fire('Deleted!', '', 'success');
                        todayTable.draw(false);
                    });
                }
            });
        });

        // Status Actions
        $(document).on('click', '.btn-action', function() {
            let id = $(this).data('id'); let action = $(this).data('action');
            $.post(`/api/v1/entry/${id}/status`, { action: action }, res => {
                Swal.fire({toast:true, position:'top-end', icon:'success', title: res.message, showConfirmButton: false, timer: 1500});
                todayTable.draw(false);
            });
        });
    });
</script>
@endpush