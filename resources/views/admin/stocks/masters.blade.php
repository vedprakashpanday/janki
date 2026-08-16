@extends('layout.app')

@section('content')
    <style>
        /* Tabs & Cards Styling */
        .nav-tabs .nav-link {
            font-weight: 600;
            color: #4a5568;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 12px 20px;
        }

        .nav-tabs .nav-link.active {
            color: #2b6cb0;
            border-bottom: 3px solid #2b6cb0;
            background: transparent;
        }

        /* Responsive Table/Cards Logic */
        .desktop-view {
            display: block;
        }

        .mobile-view {
            display: none;
        }

        @media (max-width: 768px) {
            .desktop-view {
                display: none !important;
            }

            .mobile-view {
                display: block;
            }

            .master-card {
                background: #fff;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 12px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
                border: 1px solid #e2e8f0;
                position: relative;
            }

            .master-card .card-checkbox {
                position: absolute;
                top: 15px;
                right: 15px;
                transform: scale(1.3);
            }

            .master-title {
                font-weight: 600;
                font-size: 16px;
                margin-bottom: 5px;
                padding-right: 30px;
            }

            .master-actions {
                margin-top: 10px;
                border-top: 1px solid #edf2f7;
                padding-top: 10px;
                display: flex;
                justify-content: flex-end;
                gap: 8px;
            }
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
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            display: none;
            z-index: 1050;
            align-items: center;
            gap: 15px;
        }

        @media (min-width: 768px) {
            #floatingActionBar {
                bottom: 30px;
            }
        }
    </style>

    <div class="container-fluid">
        <div class="d-flex align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-dark"><i class="fas fa-cogs text-primary me-2"></i> Stock Master Settings</h4>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white pt-3 pb-0 border-0">
                <ul class="nav nav-tabs" id="masterTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-cat" data-tab="cat"><i
                                class="fas fa-layer-group me-1"></i> Categories</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-type" data-tab="type"><i
                                class="fas fa-sitemap me-1"></i> Item Types</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-brand" data-tab="brand"><i
                                class="fas fa-copyright me-1"></i> Brands</button>
                    </li>
                    <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-attr" data-tab="attr"><i class="fas fa-list-alt me-1"></i> Specifications</button>
</li>
                </ul>
            </div>
            <div class="card-body p-4">
                <div class="tab-content" id="masterTabsContent">

                    <!-- 🟢 CATEGORY TAB 🟢 -->
                    <div class="tab-pane fade show active" id="tab-cat">
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-3" id="title-cat">Add Category</h6>
                                        <form id="form-cat">
                                            <input type="hidden" id="id-cat">
                                            <div class="mb-3"><label class="form-label">Category Name <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="name-cat" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100"><i
                                                    class="fas fa-save me-1"></i> Save Category</button>
                                            <button type="button" class="btn btn-light w-100 mt-2 d-none btn-cancel-edit"
                                                data-type="cat">Cancel Edit</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="desktop-view table-responsive">
                                    <table class="table table-hover align-middle w-100" id="table-cat">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 40px;"><input type="checkbox"
                                                        class="form-check-input select-all" data-type="cat"></th>
                                                <th>Category Name</th>
                                                <th>Status</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                                <div class="mobile-view">
                                    <div class="d-flex mb-2"><input class="form-check-input select-all me-2" type="checkbox"
                                            data-type="cat"><label class="fw-bold">Select All</label></div>
                                    <div id="cards-cat"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 🟢 TYPES TAB 🟢 -->
                    <div class="tab-pane fade" id="tab-type">
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-3" id="title-type">Add Item Type</h6>
                                        <form id="form-type">
                                            <input type="hidden" id="id-type">
                                            <div class="mb-3"><label class="form-label">Category <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select dynamic-cat-dropdown" id="category_id-type"
                                                    required></select>
                                            </div>
                                            <div class="mb-3"><label class="form-label">Type Name <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="name-type" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100"><i
                                                    class="fas fa-save me-1"></i> Save Type</button>
                                            <button type="button" class="btn btn-light w-100 mt-2 d-none btn-cancel-edit"
                                                data-type="type">Cancel Edit</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3 d-flex align-items-center gap-2">
                                    <label class="fw-bold">Filter:</label>
                                    <select class="form-select w-50 dynamic-cat-dropdown" id="filterTypeCategory">
                                        <option value="">All Categories</option>
                                    </select>
                                </div>
                                <div class="desktop-view table-responsive">
                                    <table class="table table-hover align-middle w-100" id="table-type">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 40px;"><input type="checkbox"
                                                        class="form-check-input select-all" data-type="type"></th>
                                                <th>Category</th>
                                                <th>Type Name</th>
                                                <th>Status</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                                <div class="mobile-view">
                                    <div class="d-flex mb-2"><input class="form-check-input select-all me-2"
                                            type="checkbox" data-type="type"><label class="fw-bold">Select All</label>
                                    </div>
                                    <div id="cards-type"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 🟢 BRANDS TAB 🟢 -->
                    <div class="tab-pane fade" id="tab-brand">
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-3" id="title-brand">Add Brand</h6>
                                        <form id="form-brand">
                                            <input type="hidden" id="id-brand">
                                            <div class="mb-3"><label class="form-label">Brand Name <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="name-brand" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100"><i
                                                    class="fas fa-save me-1"></i> Save Brand</button>
                                            <button type="button" class="btn btn-light w-100 mt-2 d-none btn-cancel-edit"
                                                data-type="brand">Cancel Edit</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="desktop-view table-responsive">
                                    <table class="table table-hover align-middle w-100" id="table-brand">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 40px;"><input type="checkbox"
                                                        class="form-check-input select-all" data-type="brand"></th>
                                                <th>Brand Name</th>
                                                <th>Status</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                                <div class="mobile-view">
                                    <div class="d-flex mb-2"><input class="form-check-input select-all me-2"
                                            type="checkbox" data-type="brand"><label class="fw-bold">Select All</label>
                                    </div>
                                    <div id="cards-brand"></div>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- 🟢 SPECIFICATIONS (ATTRIBUTES) TAB 🟢 -->
                <div class="tab-pane fade" id="tab-attr">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="card bg-light border-0"><div class="card-body">
                                <h6 class="fw-bold mb-3" id="title-attr">Add Specification</h6>
                                <form id="form-attr">
                                    <input type="hidden" id="id-attr">
                                    <div class="mb-3">
                                        <label class="form-label">Category <span class="text-danger">*</span></label>
                                        <select class="form-select dynamic-cat-dropdown" id="category_id-attr" required></select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Specification Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name-attr" placeholder="e.g. RAM, Material" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Options <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="options-attr" rows="2" placeholder="e.g. 4GB, 8GB, 16GB (Comma separated)" required></textarea>
                                        <small class="text-muted">Separate options with commas.</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i> Save Specification</button>
                                    <button type="button" class="btn btn-light w-100 mt-2 d-none btn-cancel-edit" data-type="attr">Cancel Edit</button>
                                </form>
                            </div></div>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-3 d-flex align-items-center gap-2">
                                <label class="fw-bold">Filter:</label>
                                <select class="form-select w-50 dynamic-cat-dropdown" id="filterAttrCategory"><option value="">All Categories</option></select>
                            </div>
                            <div class="desktop-view table-responsive">
                                <table class="table table-hover align-middle w-100" id="table-attr">
                                    <thead class="table-light"><tr>
                                        <th style="width: 40px;"><input type="checkbox" class="form-check-input select-all" data-type="attr"></th>
                                        <th>Category</th><th>Specification</th><th>Options</th><th>Status</th><th class="text-end">Action</th>
                                    </tr></thead>
                                </table>
                            </div>
                            <div class="mobile-view">
                                <div class="d-flex mb-2"><input class="form-check-input select-all me-2" type="checkbox" data-type="attr"><label class="fw-bold">Select All</label></div>
                                <div id="cards-attr"></div>
                            </div>
                        </div>
                    </div>
                </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Bar -->
    <div id="floatingActionBar">
        <span class="fw-bold"><span id="selectedCount">0</span> Selected</span>
        <button class="btn btn-sm btn-danger rounded-pill px-3" id="btnBulkDelete"><i class="fas fa-trash me-1"></i>
            Delete</button>
        <button class="btn btn-sm btn-light rounded-circle" id="btnClearSelection"
            style="width:30px;height:30px;padding:0;"><i class="fas fa-times text-dark"></i></button>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Master Details</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewDetails"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
       let dTables = { cat: null, type: null, brand: null, attr: null };
let selectedIds = { cat: new Set(), type: new Set(), brand: new Set(), attr: new Set() };
        let activeTab = 'cat';
        let permissions = {};

        $(document).ready(function() {
            loadDropdownCategories();

            // Handle Tab Change
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                activeTab = $(e.target).data('tab');
                if (dTables[activeTab]) dTables[activeTab].columns.adjust().draw();
                syncCheckboxes();
            });

            // 🟢 INITIALIZE DATATABLES
            dTables.cat = initDataTable('cat', '/api/v1/stocks/masters/categories', [{
                    data: 'id',
                    orderable: false,
                    render: d => renderCheckbox(d, 'cat')
                },
                {
                    data: 'name',
                    render: d => `<strong>${d}</strong>`
                },
                {
                    data: 'status',
                    render: d => renderStatusBadge(d)
                },
                {
                    data: 'id',
                    orderable: false,
                    className: 'text-end',
                    render: (d, t, r) => renderActions(r, 'cat')
                }
            ]);

            dTables.type = initDataTable('type', '/api/v1/stocks/masters/types', [{
                    data: 'id',
                    orderable: false,
                    render: d => renderCheckbox(d, 'type')
                },
                {
                    data: 'category.name',
                    render: d => `<span class="text-muted">${d||'-'}</span>`
                },
                {
                    data: 'name',
                    render: d => `<strong>${d}</strong>`
                },
                {
                    data: 'status',
                    render: d => renderStatusBadge(d)
                },
                {
                    data: 'id',
                    orderable: false,
                    className: 'text-end',
                    render: (d, t, r) => renderActions(r, 'type')
                }
            ], function(d) {
                d.category_id = $('#filterTypeCategory').val();
            });

            dTables.brand = initDataTable('brand', '/api/v1/stocks/masters/brands', [{
                    data: 'id',
                    orderable: false,
                    render: d => renderCheckbox(d, 'brand')
                },
                {
                    data: 'name',
                    render: d => `<strong>${d}</strong>`
                },
                {
                    data: 'status',
                    render: d => renderStatusBadge(d)
                },
                {
                    data: 'id',
                    orderable: false,
                    className: 'text-end',
                    render: (d, t, r) => renderActions(r, 'brand')
                }
            ]);

            dTables.attr = initDataTable('attr', '/api/v1/stocks/masters/attributes', [
         { data: 'id', orderable: false, render: d => renderCheckbox(d, 'attr') },
         { data: 'categories', render: d => `<span class="text-muted">${(d && d.length > 0) ? d[0].name : '-'}</span>` },
         { data: 'name', render: d => `<strong>${d}</strong>` },
         { data: 'options', render: d => d ? d.map(o => `<span class="badge bg-light text-dark border me-1">${o.value}</span>`).join('') : '-' },
         { data: 'status', render: d => renderStatusBadge(d) },
         { data: 'id', orderable: false, className: 'text-end', render: (d, t, r) => renderActions(r, 'attr') }
     ], function(d) { d.category_id = $('#filterAttrCategory').val(); });

     $('#filterAttrCategory').change(() => dTables.attr.draw());
     setupForm('attr', '/api/v1/stocks/masters/attributes');



            $('#filterTypeCategory').change(() => dTables.type.draw());

            // 🟢 FORM SUBMISSIONS (Create / Edit)
            setupForm('cat', '/api/v1/stocks/masters/categories');
            setupForm('type', '/api/v1/stocks/masters/types');
            setupForm('brand', '/api/v1/stocks/masters/brands');

            // 🟢 ACTION CLICKS (Edit Data Load)
            $(document).on('click', '.btn-edit', function() {
                let type = $(this).data('type');
                let row = dTables[type].rows().data().toArray().find(x => x.id == $(this).data('id'));

                if(type === 'type' || type === 'attr') $(`#category_id-${type}`).val(row.category_id || (row.categories && row.categories.length > 0 ? row.categories[0].id : ''));
             if(type === 'attr') {
                 // Extract comma separated options
                 let ops = row.options ? row.options.map(o => o.value).join(', ') : '';
                 $(`#options-${type}`).val(ops);
             }


                if (row) {
                    $(`#id-${type}`).val(row.id);
                    $(`#name-${type}`).val(row.name);
                    if (type === 'type') $(`#category_id-${type}`).val(row.category_id);

                    $(`#title-${type}`).text('Edit Record');
                    $(`.btn-cancel-edit[data-type="${type}"]`).removeClass('d-none');
                }
            });

            // 🟢 CANCEL EDIT FIX
            $(document).on('click', '.btn-cancel-edit', function() {
                let type = $(this).data('type');

                // Hardcoded direct ID reset - 100% immune to error
                document.getElementById(`form-${type}`).reset();

                $(`#id-${type}`).val('');
                let defaultTitle = type === 'cat' ? 'Add Category' : (type === 'type' ? 'Add Item Type' :
                    'Add Brand');
                $(`#title-${type}`).text(defaultTitle);

                $(this).addClass('d-none');
            });

            $(document).on('click', '.btn-appr', function() {
                handleStatus($(this).data('type'), $(this).data('id'), 'approve');
            });
            $(document).on('click', '.btn-rej', function() {
                handleStatus($(this).data('type'), $(this).data('id'), 'reject');
            });

            $(document).on('click', '.btn-view', function() {
                let type = $(this).data('type');
                let row = dTables[type].rows().data().toArray().find(x => x.id == $(this).data('id'));
                let html =
                    `<table class="table table-sm"><tr><th>Name:</th><td>${row.name}</td></tr><tr><th>Status:</th><td>${renderStatusBadge(row.status)}</td></tr>`;
                if (type === 'type') html +=
                    `<tr><th>Category:</th><td>${row.category?.name || '-'}</td></tr>`;
                html += `</table>`;
                $('#viewDetails').html(html);
                $('#viewModal').modal('show');
            });

            // 🟢 CHECKBOX & BULK DELETE LOGIC
            $(document).on('change', '.row-checkbox', function() {
                let id = parseInt($(this).val());
                $(this).is(':checked') ? selectedIds[activeTab].add(id) : selectedIds[activeTab].delete(id);
                syncCheckboxes();
            });

            $('.select-all').change(function() {
                let isChecked = $(this).is(':checked');
                $(`.row-checkbox[data-type="${activeTab}"]`).each(function() {
                    let id = parseInt($(this).val());
                    isChecked ? selectedIds[activeTab].add(id) : selectedIds[activeTab].delete(id);
                });
                syncCheckboxes();
            });

            $('#btnClearSelection').click(function() {
                selectedIds[activeTab].clear();
                syncCheckboxes();
            });

            $('#btnBulkDelete').click(function() {
                let ids = Array.from(selectedIds[activeTab]);
                let apiUrls = {
                    cat: 'categories',
                    type: 'types',
                    brand: 'brands'
                };
                Swal.fire({
                    title: 'Delete Selected?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete!'
                }).then((res) => {
                    if (res.isConfirmed) {
                        $.post(`/api/v1/stocks/masters/${apiUrls[activeTab]}/bulk-delete`, {
                            ids: ids
                        }, function(res) {
                            Swal.fire('Deleted!', res.message, 'success');
                            selectedIds[activeTab].clear();
                            syncCheckboxes();
                            dTables[activeTab].draw(false);
                            if (activeTab === 'cat') loadDropdownCategories();
                        }).fail(() => Swal.fire('Error', 'Failed to delete', 'error'));
                    }
                });
            });

            // 🟢 HELPER FUNCTIONS
            function initDataTable(type, url, columns, dataCallback = null) {
                return $(`#table-${type}`).DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: url,
                        type: "GET",
                        data: function(d) {
                            if (dataCallback) dataCallback(d);
                        },
                        dataSrc: function(json) {
                            permissions = json.permissions;
                            return json.data;
                        }
                    },
                    columns: columns,
                    drawCallback: function() {
                        renderMobileCards(this.api().rows({
                            page: 'current'
                        }).data().toArray(), type);
                        syncCheckboxes();
                    }
                });
            }

            function renderMobileCards(data, type) {
                let html = data.length === 0 ? '<div class="text-center text-muted">No data found</div>' : '';
                data.forEach(row => {
                    let catInfo = type === 'type' ?
                        `<div class="master-card-detail"><i class="fas fa-tag"></i> ${row.category?.name || '-'}</div>` :
                        '';
                    html += `
                    <div class="master-card">
                        ${renderCheckbox(row.id, type, 'card-checkbox')}
                        <div class="master-title">${row.name}</div>
                        ${catInfo}
                        <div class="mt-1">${renderStatusBadge(row.status)}</div>
                        <div class="master-actions">${renderActions(row, type)}</div>
                    </div>`;
                });
                $(`#cards-${type}`).html(html);
            }

            function setupForm(type, baseUrl) {
                $(`#form-${type}`).submit(function(e) {
                    e.preventDefault();
                    let id = $(`#id-${type}`).val();
                    let method = id ? 'PUT' : 'POST';
                    let url = id ? `${baseUrl}/${id}` : baseUrl;

                   let data = { name: $(`#name-${type}`).val() };
             if(type === 'type' || type === 'attr') data.category_id = $(`#category_id-${type}`).val();
             if(type === 'attr') data.options = $(`#options-${type}`).val(); // Extra line for specifications

                    $.ajax({
                        url: url,
                        type: method,
                        data: data,
                        success: function(res) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: res.message,
                                showConfirmButton: false,
                                timer: 1500
                            });
                            $(`.btn-cancel-edit[data-type="${type}"]`)
                        .click(); // Cleanly reset using the button
                            dTables[type].draw(false);
                            if (type === 'cat') loadDropdownCategories();
                        },
                        error: () => Swal.fire('Error', 'Validation failed', 'error')
                    });
                });
            }

            function handleStatus(type, id, action) {
                let apiUrls = {
                    cat: 'categories',
                    type: 'types',
                    brand: 'brands'
                };
                $.post(`/api/v1/stocks/masters/${apiUrls[type]}/${id}/status`, {
                    action: action
                }, function(res) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: res.message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    dTables[type].draw(false);
                }).fail(() => Swal.fire('Error', 'Unauthorized', 'error'));
            }

            function loadDropdownCategories() {
                $.get('/api/v1/stocks/masters/dropdown-categories', function(res) {
                    let options = '<option value="">Select Category...</option>';
                    res.forEach(c => options += `<option value="${c.id}">${c.name}</option>`);
                    $('.dynamic-cat-dropdown').html(options);
                });
            }

            function syncCheckboxes() {
                $(`.row-checkbox[data-type="${activeTab}"]`).each(function() {
                    $(this).prop('checked', selectedIds[activeTab].has(parseInt($(this).val())));
                });
                let count = selectedIds[activeTab].size;
                $('#selectedCount').text(count);
                if (count > 0 && permissions.can_delete) $('#floatingActionBar').css('display', 'flex');
                else $('#floatingActionBar').css('display', 'none');

                let total = $(`.row-checkbox[data-type="${activeTab}"]`).length;
                $(`.select-all[data-type="${activeTab}"]`).prop('checked', total > 0 && total === count);
            }

            function renderCheckbox(id, type, cls = '') {
                let checked = selectedIds[type].has(id) ? 'checked' : '';
                return `<input type="checkbox" class="form-check-input row-checkbox ${cls}" data-type="${type}" value="${id}" ${checked}>`;
            }

            function renderStatusBadge(s) {
                let c = s === 'active' ? 'success' : (s === 'pending' ? 'warning text-dark' : 'danger');
                return `<span class="badge bg-${c}">${s.toUpperCase()}</span>`;
            }

            function renderActions(row, type) {
                let btns =
                    `<button class="btn btn-sm btn-light text-info btn-view" data-id="${row.id}" data-type="${type}"><i class="fas fa-eye"></i></button> `;
                if (row.status === 'pending' && permissions.can_appr) btns +=
                    `<button class="btn btn-sm btn-success btn-appr" data-id="${row.id}" data-type="${type}"><i class="fas fa-check"></i></button> `;
                if (row.status === 'active' && permissions.can_rej) btns +=
                    `<button class="btn btn-sm btn-warning btn-rej" data-id="${row.id}" data-type="${type}"><i class="fas fa-times"></i></button> `;
                if (permissions.can_edit) btns +=
                    `<button class="btn btn-sm btn-light text-primary btn-edit" data-id="${row.id}" data-type="${type}"><i class="fas fa-edit"></i></button> `;
                return btns;
            }
        });
    </script>
@endpush
