@extends('layout.app')

@section('content')
<style>
    /* Desktop DataTables visibility */
    #stockTableContainer { display: block; }
    #mobileCardsContainer { display: none; }

    /* Mobile Cards View */
    @media (max-width: 768px) {
        #stockTableContainer { display: none !important; }
        #mobileCardsContainer { display: block; }
        
        .stock-card {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            position: relative;
        }
        .stock-card .card-checkbox {
            position: absolute;
            top: 15px;
            right: 15px;
            transform: scale(1.3);
        }
        .stock-card-title { font-weight: 600; font-size: 16px; color: #2d3748; margin-bottom: 5px; padding-right: 30px;}
        .stock-card-detail { font-size: 13px; color: #718096; margin-bottom: 3px; }
        .stock-card-actions { margin-top: 10px; border-top: 1px solid #edf2f7; padding-top: 10px; display: flex; justify-content: flex-end; gap: 10px; }
    }

    /* Select2 Bootstrap 5 Fix */
    .select2-container { width: 100% !important; }
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important; /* Text vertically center */
        padding-left: 12px !important;
        color: #212529 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        top: 6px !important;
        right: 8px !important;
    }
    /* Floating Action Bar */
    #floatingActionBar {
        position: fixed;
        bottom: 80px; 
        left: 50%;
        transform: translateX(-50%);
        background: #1A365D;
        color: white;
        padding: 10px 25px;
        border-radius: 30px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        display: none;
        z-index: 1050;
        align-items: center;
        gap: 15px;
        transition: all 0.3s ease;
    }
    
    @media (min-width: 768px) {
        #floatingActionBar { bottom: 30px; }
    }
</style>

<div class="container-fluid">
    <!-- Header & Action Buttons -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <h4 class="mb-0 fw-bold text-dark"><i class="fas fa-boxes text-warning me-2"></i> Stock Management</h4>
        <div class="d-flex gap-2">
            <button class="btn btn-success d-none" id="btnExportExcel"><i class="fas fa-file-excel me-1"></i> Export</button>
            <button class="btn btn-secondary d-none" id="btnPrint"><i class="fas fa-print me-1"></i> Print</button>
            <button class="btn btn-primary d-none" id="btnAddStock" data-bs-toggle="modal" data-bs-target="#stockModal"><i class="fas fa-plus me-1"></i> Add Stock</button>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Company</label>
                   <select id="filterCompany" class="form-select select2-dynamic">
    <option value="">Search Company...</option>
</select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Branch</label>
                    <select id="filterBranch" class="form-select select2-branch"><option value="">Select Company First</option></select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Category</label>
                    <select id="filterCategory" class="form-select"><option value="">All Categories</option></select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Product</label>
                    <select id="filterProduct" class="form-select"><option value="">All Products</option></select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button class="btn btn-dark w-50" id="btnFilter" title="Apply Filter"><i class="fas fa-filter"></i></button>
                    <button class="btn btn-warning w-50" id="btnGenerateReport" title="Generate Report"><i class="fas fa-file-invoice"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Display Area -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0 p-md-3">
            
            <!-- DESKTOP DATATABLE -->
            <div id="stockTableContainer" class="table-responsive">
                <table class="table table-hover align-middle w-100" id="stockTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" class="form-check-input" id="selectAllDesktop"></th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Entry Date</th>
                            <th>Price / Qty</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <!-- MOBILE CARDS CONTAINER -->
            <div id="mobileCardsContainer" class="p-3">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAllMobile">
                        <label class="form-check-label fw-bold" for="selectAllMobile">Select All</label>
                    </div>
                </div>
                <div id="cardsWrapper"></div>
            </div>

        </div>
    </div>
</div>

<!-- Floating Action Bar for Bulk Delete -->
<div id="floatingActionBar">
    <span class="fw-bold"><span id="selectedCount">0</span> Selected</span>
    <button class="btn btn-sm btn-danger rounded-pill px-3" id="btnBulkDelete"><i class="fas fa-trash me-1"></i> Delete</button>
    <button class="btn btn-sm btn-light rounded-circle" id="btnClearSelection" style="width: 30px; height: 30px; padding: 0;"><i class="fas fa-times text-dark"></i></button>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="stockModalLabel">Add Stock Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="stockForm">
                    <input type="hidden" id="stock_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Company <span class="text-danger">*</span></label>
                            <select id="formCompany" name="company_id" class="form-select select2-dynamic" required>
    <option value="">Search Company...</option>
</select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <select id="formBranch" name="branch_id" class="form-select select2-branch"><option value="">Select Company First</option></select>
                        </div>
                        <!-- Modifed to Dropdowns for Category & Item -->
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category" id="category" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <select class="form-select" name="item_name" id="item_name" required></select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Entry Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="entry_date" id="entry_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Serial Number</label>
                            <input type="text" class="form-control" name="serial_number" id="serial_number">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Price (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="price" id="price">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Qty <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="total_quantity" id="total_quantity" value="1" required>
                        </div>
                        <div class="col-md-4" id="lostQtyDiv" style="display: none;">
                            <label class="form-label text-danger">Lost Qty</label>
                            <input type="number" class="form-control" name="lost_quantity" id="lost_quantity" value="0">
                        </div>
                        <div class="col-md-12 mt-3">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" name="remarks" id="remarks" rows="2" placeholder="Any specific details..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveStock">Save Entry</button>
            </div>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Stock Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewStockDetails">
                <!-- Data will be populated here dynamically -->
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    let stockTable;
    let selectedStockIds = new Set();
    let currentPermissions = {};

    $(document).ready(function() {
        
        // 1. Initialize Cascading Dropdowns (with Modal support for form)
        initCascadingDropdowns('#filterCompany', '#filterBranch'); 
        initCascadingDropdowns('#formCompany', '#formBranch', '#stockModal');

        // Initialize Category and Item Name with Tags Support in Modal
        $('#category, #item_name').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#stockModal'),
            tags: true, 
            width: '100%'
        });

        // Fetch Modal Categories and Items
        function loadModalDropdowns(categoryId = null) {
            $.get('/api/v1/stocks/filters', { category: categoryId }, function(res) {
                if(!categoryId) {
                    let catHtml = '<option value="">Select or Type Category...</option>';
                    res.categories.forEach(c => catHtml += `<option value="${c}">${c}</option>`);
                    $('#category').html(catHtml);
                }
                let itemHtml = '<option value="">Select or Type Item...</option>';
                res.products.forEach(p => itemHtml += `<option value="${p}">${p}</option>`);
                $('#item_name').html(itemHtml);
            });
        }

        // Trigger Item reload when Category changes in Modal
        $('#category').on('change', function() {
            let cat = $(this).val();
            if(cat) loadModalDropdowns(cat);
        });

        // Load Filters dynamically on Branch change for Filter Section
        $('#filterBranch').change(function() {
            $.get('/api/v1/stocks/filters', { 
                company_id: $('#filterCompany').val(), 
                branch_id: $(this).val() 
            }, function(res) {
                let catOptions = '<option value="">All Categories</option>';
                res.categories.forEach(c => catOptions += `<option value="${c}">${c}</option>`);
                $('#filterCategory').html(catOptions);

                let prodOptions = '<option value="">All Products</option>';
                res.products.forEach(p => prodOptions += `<option value="${p}">${p}</option>`);
                $('#filterProduct').html(prodOptions);
            });
        });

        // 2. Initialize DataTable
        stockTable = $('#stockTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "/api/v1/stocks",
                type: "GET",
                data: function(d) {
                    d.company_id = $('#filterCompany').val();
                    d.branch_id = $('#filterBranch').val();
                    d.category = $('#filterCategory').val();
                    d.product = $('#filterProduct').val();
                },
                dataSrc: function(json) {
                    currentPermissions = json.permissions || {};
                    applyUIPermissions();
                    return json.data;
                }
            },
            columns: [
                {
                    data: 'id',
                    orderable: false,
                    render: function(data) {
                        let checked = selectedStockIds.has(data) ? 'checked' : '';
                        return `<input type="checkbox" class="form-check-input row-checkbox" value="${data}" ${checked}>`;
                    }
                },
                { 
                    data: 'item_name',
                    render: function(data, type, row) {
                        let sn = row.serial_number ? `<br><small class="text-muted">SN: ${row.serial_number}</small>` : '';
                        return `<strong>${data}</strong>${sn}`;
                    }
                },
                { data: 'category' },
                { data: 'entry_date' },
                { 
                    data: 'price',
                    render: function(data, type, row) {
                        let lost = row.lost_quantity > 0 ? `<span class="badge bg-danger ms-1">Lost: ${row.lost_quantity}</span>` : '';
                        return `₹${data} / Qty: ${row.total_quantity} ${lost}`;
                    }
                },
                { 
                    data: 'status',
                    render: function(data) {
                        let cls = data === 'active' ? 'success' : (data === 'pending' ? 'warning' : 'danger');
                        return `<span class="badge bg-${cls}">${data.toUpperCase()}</span>`;
                    }
                },
                {
                    data: 'id',
                    orderable: false,
                    className: 'text-end',
                    render: function(data) {
                        let btns = '';
                        btns += `<button class="btn btn-sm btn-light text-info btn-view" data-id="${data}" title="View"><i class="fas fa-eye"></i></button> `;
                        
                        if(currentPermissions.can_edit) {
                            btns += `<button class="btn btn-sm btn-light text-primary btn-edit" data-id="${data}" title="Edit"><i class="fas fa-edit"></i></button> `;
                        }
                        if(currentPermissions.can_delete) {
                            btns += `<button class="btn btn-sm btn-light text-danger btn-delete" data-id="${data}" title="Delete"><i class="fas fa-trash"></i></button>`;
                        }
                        return btns;
                    }
                }
            ],
            drawCallback: function(settings) {
                renderMobileCards(this.api().rows({page: 'current'}).data().toArray());
                updateSelectAllState();
            }
        });

        $('#btnFilter').click(() => stockTable.draw());

        // 3. Mobile Cards Render Logic
        function renderMobileCards(data) {
            let html = '';
            if(data.length === 0) {
                html = '<div class="text-center text-muted py-4">No data available</div>';
            } else {
                data.forEach(row => {
                    let checked = selectedStockIds.has(row.id) ? 'checked' : '';
                    let lost = row.lost_quantity > 0 ? `<span class="badge bg-danger">Lost: ${row.lost_quantity}</span>` : '';
                    let sn = row.serial_number ? ` | SN: ${row.serial_number}` : '';
                    
                    let actionBtns = `<button class="btn btn-sm btn-outline-info btn-view" data-id="${row.id}">View</button>`;
                    if(currentPermissions.can_edit) actionBtns += `<button class="btn btn-sm btn-outline-primary btn-edit" data-id="${row.id}">Edit</button>`;
                    if(currentPermissions.can_delete) actionBtns += `<button class="btn btn-sm btn-outline-danger btn-delete" data-id="${row.id}">Delete</button>`;

                    html += `
                        <div class="stock-card">
                            <input type="checkbox" class="form-check-input row-checkbox card-checkbox" value="${row.id}" ${checked}>
                            <div class="stock-card-title">${row.item_name}</div>
                            <div class="stock-card-detail"><i class="fas fa-tag fa-fw"></i> ${row.category || 'N/A'}${sn}</div>
                            <div class="stock-card-detail"><i class="fas fa-calendar fa-fw"></i> ${row.entry_date}</div>
                            <div class="stock-card-detail"><i class="fas fa-rupee-sign fa-fw"></i> ${row.price} (Qty: ${row.total_quantity}) ${lost}</div>
                            <div class="stock-card-detail"><i class="fas fa-info-circle fa-fw"></i> Status: <strong>${row.status.toUpperCase()}</strong></div>
                            ${actionBtns ? `<div class="stock-card-actions">${actionBtns}</div>` : ''}
                        </div>
                    `;
                });
            }
            $('#cardsWrapper').html(html);
        }

        // 4. RBAC UI Helper
        function applyUIPermissions() {
            if(currentPermissions.can_add_direct || currentPermissions.can_add_request) $('#btnAddStock').removeClass('d-none');
            if(currentPermissions.can_export) $('#btnExportExcel').removeClass('d-none');
            if(currentPermissions.can_print) $('#btnPrint').removeClass('d-none');
        }

        // 5. Floating Action Bar & Checkbox Logic
        $(document).on('change', '.row-checkbox', function() {
            let id = parseInt($(this).val());
            if($(this).is(':checked')) {
                selectedStockIds.add(id);
            } else {
                selectedStockIds.delete(id);
            }
            updateFloatingBar();
            syncCheckboxes();
        });

        $('#selectAllDesktop, #selectAllMobile').change(function() {
            let isChecked = $(this).is(':checked');
            $('.row-checkbox').each(function() {
                $(this).prop('checked', isChecked);
                let id = parseInt($(this).val());
                isChecked ? selectedStockIds.add(id) : selectedStockIds.delete(id);
            });
            updateFloatingBar();
            syncCheckboxes();
        });

        function updateFloatingBar() {
            let count = selectedStockIds.size;
            $('#selectedCount').text(count);
            if(count > 0 && currentPermissions.can_delete) {
                $('#floatingActionBar').css('display', 'flex');
            } else {
                $('#floatingActionBar').css('display', 'none');
            }
        }

        function syncCheckboxes() {
            $('.row-checkbox').each(function() {
                $(this).prop('checked', selectedStockIds.has(parseInt($(this).val())));
            });
            updateSelectAllState();
        }

        function updateSelectAllState() {
            let totalRows = $('.row-checkbox').length;
            let checkedRows = $('.row-checkbox:checked').length;
            let state = totalRows > 0 && totalRows === checkedRows;
            $('#selectAllDesktop, #selectAllMobile').prop('checked', state);
        }

        $('#btnClearSelection').click(function() {
            selectedStockIds.clear();
            syncCheckboxes();
            updateFloatingBar();
        });

        // Bulk Delete API Call
        $('#btnBulkDelete').click(function() {
            Swal.fire({
                title: 'Are you sure?', text: "You won't be able to revert this!", icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/api/v1/stocks/bulk-delete',
                        type: 'POST',
                        data: { ids: Array.from(selectedStockIds) },
                        success: function(res) {
                            Swal.fire('Deleted!', res.message, 'success');
                            selectedStockIds.clear();
                            updateFloatingBar();
                            stockTable.draw(false);
                        },
                        error: function(xhr) { Swal.fire('Error!', xhr.responseJSON.message || 'Something went wrong.', 'error'); }
                    });
                }
            });
        });

        // 6. Add/Edit Form Logic
        $('#btnAddStock').click(function() {
            $('#stockForm')[0].reset();
            $('#stock_id').val('');
            $('#lostQtyDiv').hide(); 
            $('#stockModalLabel').text('Add Stock Entry');
            
            $('#formCompany').val(null).trigger('change');
            $('#category').val(null).trigger('change');
            $('#item_name').val(null).trigger('change');
            
            loadModalDropdowns(); 
        });

        $('#btnSaveStock').click(function() {
            let id = $('#stock_id').val();
            let url = id ? `/api/v1/stocks/${id}` : '/api/v1/stocks';
            let method = id ? 'PUT' : 'POST';
            let data = $('#stockForm').serialize();

            $.ajax({
                url: url,
                type: method,
                data: data,
                success: function(res) {
                    $('#stockModal').modal('hide');
                    Swal.fire('Success', res.message, 'success');
                    stockTable.draw(false);
                },
                error: function(xhr) {
                    Swal.fire('Error', xhr.responseJSON.message || 'Check required fields', 'error');
                }
            });
        });

        // Edit Row Data Loading
        $(document).on('click', '.btn-edit', function() {
            let id = $(this).data('id');
            let rowData = stockTable.rows().data().toArray().find(x => x.id == id);
            
            if(rowData) {
                $('#stock_id').val(rowData.id);
                $('#entry_date').val(rowData.entry_date);
                $('#serial_number').val(rowData.serial_number);
                $('#price').val(rowData.price);
                $('#total_quantity').val(rowData.total_quantity);
                $('#remarks').val(rowData.remarks);
                
                $('#lostQtyDiv').show(); 
                $('#lost_quantity').val(rowData.lost_quantity);
                
                $('#stockModalLabel').text('Edit Stock Entry');
                
                if ($('#category').find("option[value='" + rowData.category + "']").length) {
                    $('#category').val(rowData.category).trigger('change');
                } else {
                    let newCat = new Option(rowData.category, rowData.category, true, true);
                    $('#category').append(newCat).trigger('change');
                }

                if ($('#item_name').find("option[value='" + rowData.item_name + "']").length) {
                    $('#item_name').val(rowData.item_name).trigger('change');
                } else {
                    let newItem = new Option(rowData.item_name, rowData.item_name, true, true);
                    $('#item_name').append(newItem).trigger('change');
                }
                
                if(rowData.company_id) {
                    let newComp = new Option(rowData.company.company_name, rowData.company_id, true, true);
                    $('#formCompany').append(newComp).trigger('change');
                }
                
                setTimeout(() => {
                    if(rowData.branch_id) $('#formBranch').val(rowData.branch_id).trigger('change');
                }, 500);

                $('#stockModal').modal('show');
            }
        });

        // View Button Action
        $(document).on('click', '.btn-view', function() {
            let id = $(this).data('id');
            let row = stockTable.rows().data().toArray().find(x => x.id == id);
            
            if(row) {
                let bName = row.branch ? row.branch.branch_name : 'Head Office';
                let html = `
                    <table class="table table-sm table-borderless">
                        <tr><th>Company:</th><td>${row.company?.company_name || '-'}</td></tr>
                        <tr><th>Branch:</th><td>${bName}</td></tr>
                        <tr><th>Item Name:</th><td>${row.item_name}</td></tr>
                        <tr><th>Category:</th><td>${row.category || '-'}</td></tr>
                        <tr><th>Entry Date:</th><td>${row.entry_date}</td></tr>
                        <tr><th>Total Qty:</th><td>${row.total_quantity}</td></tr>
                        <tr><th>Lost Qty:</th><td class="text-danger">${row.lost_quantity}</td></tr>
                        <tr><th>Available Qty:</th><td class="text-success fw-bold">${row.total_quantity - row.lost_quantity}</td></tr>
                        <tr><th>Remarks:</th><td>${row.remarks || '-'}</td></tr>
                    </table>
                `;
                $('#viewStockDetails').html(html);
                $('#viewStockModal').modal('show');
            }
        });

        // Generate Report / Print Logic
        $('#btnGenerateReport, #btnPrint').click(function() {
            let comp = $('#filterCompany').val() || '';
            let branch = $('#filterBranch').val() || '';
            let cat = $('#filterCategory').val() || '';
            let prod = $('#filterProduct').val() || '';
            
            // 🔥 FIX: Current portal nikalna aur localStorage se token uthana
            let portal = window.location.pathname.split('/')[1]; 
            let t = localStorage.getItem('token') || localStorage.getItem('admin_token') || localStorage.getItem('emp_token') || '';
            
            // URL me &token= bhej rahe hain taaki controller me user pehchana ja sake
            let url = `/${portal}/stocks/print?company_id=${comp}&branch_id=${branch}&category=${cat}&product=${prod}&token=${t}`;
            window.open(url, '_blank');
        });
        
        // Export Excel
        $('#btnExportExcel').click(function() {
            let comp = $('#filterCompany').val() || '';
            let branch = $('#filterBranch').val() || '';
            let cat = $('#filterCategory').val() || '';
            let prod = $('#filterProduct').val() || '';
            let search = stockTable.search() || '';
            
            Swal.fire({title: 'Generating Excel...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
            
            $.ajax({
                url: `/api/v1/stocks?export=true&company_id=${comp}&branch_id=${branch}&category=${cat}&product=${prod}&search[value]=${search}`,
                type: 'GET',
                success: function(res) {
                    Swal.close();
                    let csv = 'ID,Company,Branch,Item Name,Category,Entry Date,Serial Number,Price,Total Qty,Lost Qty,Remarks\n';
                    res.data.forEach(r => {
                        let cName = r.company ? r.company.company_name.replace(/,/g, '') : '';
                        let bName = r.branch ? r.branch.branch_name.replace(/,/g, '') : 'Head Office';
                        let iName = r.item_name ? r.item_name.replace(/,/g, '') : '';
                        let rem = r.remarks ? r.remarks.replace(/,/g, ' ') : '';
                        
                        csv += `${r.id},${cName},${bName},${iName},${r.category},${r.entry_date},${r.serial_number},${r.price},${r.total_quantity},${r.lost_quantity},${rem}\n`;
                    });
                    
                    let blob = new Blob([csv], { type: 'text/csv' });
                    let link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = `Stock_Report_${new Date().getTime()}.csv`;
                    link.click();
                },
                error: function() { Swal.fire('Error', 'Failed to generate export', 'error'); }
            });
        });

        // 7. Cascading Helper Function
        function initCascadingDropdowns(compSelector, branchSelector, modalSelector = null) {
            let select2Options = {
                theme: 'bootstrap-5',
                width: '100%', 
                placeholder: 'Search Company...',
                ajax: {
                    url: '/api/v1/stocks/search-companies',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term }; },
                    processResults: function(data) {
                        return { results: $.map(data.data, function(item) { return { text: item.company_name, id: item.id }; }) };
                    }
                }
            };

            if(modalSelector) {
                select2Options.dropdownParent = $(modalSelector);
            }

            $(compSelector).select2(select2Options);

            $(compSelector).on('select2:select', function (e) {
                let compId = e.params.data.id;
                let branchSelect = $(branchSelector);
                branchSelect.html('<option value="">Loading...</option>');
                
                $.ajax({
                    url: '/api/v1/stocks/search-branches',
                    data: { company_id: compId },
                    success: function(res) {
                        branchSelect.empty();
                        res.data.forEach(function(b) {
                            branchSelect.append(new Option(b.branch_name, b.id)); 
                        });
                        branchSelect.trigger('change');
                    }
                });
            });
        }
    });
</script>
@endpush