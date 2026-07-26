@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        /* 🔥 Professional Mobile Swipeable Tabs 🔥 */
        .category-slider {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            gap: 10px;
            padding-bottom: 10px;
            scrollbar-width: none;
        }

        .category-slider::-webkit-scrollbar {
            display: none;
        }

        .category-btn {
            white-space: nowrap;
            transition: 0.3s;
            font-weight: 600;
            border-radius: 50px;
        }

        .category-btn.active {
            background-color: var(--brand-primary);
            color: white;
            border-color: var(--brand-primary);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .entry-row {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }

        .btn-remove-row {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }

        /* Uploaded File Previews Container */
        .file-preview-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .file-preview-box {
            position: relative;
            width: 60px;
            height: 60px;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }

        .file-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .file-preview-box .remove-file {
            position: absolute;
            top: -5px;
            right: -5px;
            background: red;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
        }

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
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            z-index: 1050;
            transition: bottom 0.3s ease-in-out;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        #bulkActionBar.show {
            bottom: 80px;
        }
    </style>

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark"><i class="fas fa-clipboard-list text-primary me-2"></i>Daily Site Entries</h5>
            <div>
                <button class="btn btn-success btn-sm secured-item me-2" data-permission="sites_export" id="btnExportExcel">
                    <i class="fas fa-file-excel"></i> <span class="d-none d-md-inline">Export</span>
                </button>
            </div>
        </div>

        <div class="category-slider mb-3" id="categoryFilterContainer">
            <button class="btn btn-outline-secondary category-btn active" data-category="All">All Categories</button>
        </div>

        <div class="mb-3 text-end d-none" id="addEntryBtnContainer">
            <button class="btn btn-warning btn-sm rounded-pill shadow-sm me-2 d-none" id="btnPrintTripSlips">
                <i class="fas fa-print"></i> Print Blank Slips
            </button>
            <button class="btn btn-primary btn-sm rounded-pill shadow-sm secured-item" data-permission="sites_add_direct"
                id="btnOpenEntryForm">
                <i class="fas fa-plus"></i> Add <span id="addBtnLabel">Entry</span>
            </button>
        </div>

        <div id="bulkActionBar">
            <span id="selectedCount" class="fw-bold">0 Selected</span>
            <button class="btn btn-light btn-sm rounded-pill" id="btnSelectAllRows">Select All</button>
            <button class="btn btn-danger btn-sm rounded-pill secured-item" data-permission="sites_delete"
                id="btnBulkDelete"><i class="fas fa-trash"></i> Delete</button>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body">
                <table class="table table-hover w-100" id="entryTable">
                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" class="form-check-input" id="checkAllMaster"></th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Entered By</th>
                            <th>Details</th>
                            <th>Financials</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <div class="d-block d-md-none" id="mobileCardsContainer"></div>
    </div>

    <div class="d-none" id="entryFormTemplate">
        <form class="daily-entry-form" enctype="multipart/form-data">
            <input type="hidden" name="allocation_id" class="allocation-id-input">
            <input type="hidden" name="category" class="category-input">
            <input type="hidden" name="edit_id" class="edit-id-input">

            <div class="mb-3">
                <label class="form-label small text-muted">Entry Date <span class="text-danger">*</span></label>
                <input type="date" name="entry_date" class="form-control form-control-sm date-input" required
                    value="{{ date('Y-m-d') }}">
            </div>

            <div class="dynamic-rows-container"></div>

            <div class="text-center mb-3">
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-4 btn-add-row"><i
                        class="fas fa-plus me-1"></i> Add Another Row</button>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-save-entry">Save Entry</button>
        </form>
    </div>

    <div class="modal fade" id="desktopModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h6 class="modal-title fw-bold modal-main-title">Daily Entry</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="desktopModalBody"></div>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-bottom shadow-lg" tabindex="-1" id="mobileOffcanvas" style="height: 85vh; border-top-left-radius: 20px; border-top-right-radius: 20px;">
        <div class="offcanvas-header bg-light border-bottom" style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
            <h6 class="offcanvas-title fw-bold offcanvas-main-title">Daily Entry</h6>
            <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body" id="mobileOffcanvasBody" style="overflow-y: auto; overflow-x: hidden;">
            </div>
    </div>

    <div class="modal fade" id="historyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h6 class="modal-title fw-bold"><i class="fas fa-history"></i> Edit History Tracker</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="historyTimeline"></div>
            </div>
        </div>
    </div>

<div class="modal fade" id="printTripConfigModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h6 class="modal-title fw-bold"><i class="fas fa-print"></i> Configure Bulk Trip Slips</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                   <form id="printBulkSlipForm" target="_blank" action="{{ url(Request::segment(1) . '/vehicle-trips/generate-blank') }}" method="GET">
                        <input type="hidden" name="company_id" id="printSlipCompanyId">
                        
                        <input type="hidden" name="user_id" id="printSlipUserId">
                        
                        <div class="mb-3">
                            <label class="small text-muted fw-bold">Trip Date</label>
                            <input type="date" name="trip_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted fw-bold">Project / Phase</label>
                            <select name="phase_name" class="form-select" required>
                                <option value="MAIN PROJECT">Main Project</option>
                                <option value="JANKI VILLA PHASE-II">Janki Villa Phase-II</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted fw-bold">Slip Type</label>
                            <input list="printSlipTypes" name="slip_type" class="form-control text-uppercase" placeholder="e.g. SOIL, SARIYA" required>
                            <datalist id="printSlipTypes"><option value="SOIL"></option><option value="RED SAND"></option><option value="WHITE SAND"></option><option value="SARIYA"></option><option value="CEMENT"></option></datalist>
                        </div>
                        <div class="mb-4">
                            <label class="small text-muted fw-bold">How many trips to print?</label>
                            <input type="number" name="num_trips" class="form-control" min="1" max="100" value="10" required>
                            <small class="text-muted" style="font-size: 10px;">Prints 10 slips per A4 Page.</small>
                        </div>
                        <button type="submit" class="btn btn-dark w-100">Generate & Print</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewEntryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white">
                    <h6 class="modal-title fw-bold"><i class="fas fa-eye"></i> View Entry Details</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewEntryModalBody">
                    </div>
                <div class="modal-footer bg-light d-flex justify-content-between">
                    <a href="#" target="_blank" class="btn btn-success rounded-pill" id="btnViewPrint"><i class="fas fa-print"></i> Print This Entry</a>
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            let currentCategory = 'All';
            let globalAllocationId = null;
            let rowIndex = 0;
            let $activeFormContainer;
            let selectedIds = new Set();
            let isHighLevelUser = false; // from API
            let globalShops = []; // Stores shop auto-fill data

            // 🔥 NAYA VARIABLE
            let globalUserId = null;

            // Custom File Tracker for Previews & Remove
            let fileTracker = new DataTransfer();

            // Fetch Shops for Auto-fill
            $.get('/api/v1/site-entries/shops', function(res) {
                if (res.data) globalShops = res.data;
            });

            let table = $('#entryTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/site-entries',
                    data: function(d) {
                        d.category = currentCategory === 'All' ? '' : currentCategory;
                    }
                },
                columns: [{
                        data: 'id',
                        orderable: false,
                        render: function(data) {
                            return `<input type="checkbox" class="form-check-input row-checkbox" value="${data}">`;
                        }
                    },
                    {
                        data: 'entry_date',
                        render: function(data) {
                            return `<strong>${data}</strong>`;
                        }
                    },
                    {
                        data: 'category',
                        render: function(data) {
                            return `<span class="badge bg-secondary">${data}</span>`;
                        }
                    },
                    {
                        data: 'entered_by.full_name',
                        defaultContent: 'N/A'
                    },
                    {
                        data: 'entry_details',
                        render: function(data) {
                            let str = '';
                            for (let key in data) {
                                str +=
                                    `<span class="small me-2 text-muted"><strong>${key.replace(/_/g, ' ').toUpperCase()}:</strong> ${data[key]}</span><br>`;
                            }
                            return str;
                        }
                    },
                    {
                        data: 'total_amount',
                        render: function(data, type, row) {
                            if (row.category === 'Vehicle Trip Slip') {
                                return `<strong class="text-dark">- Trip Record -</strong>`;
                            }
                            return `<strong class="text-dark">₹${data}</strong><br>
                            <small class="text-success">Paid: ₹${row.paid_amount}</small> | 
                            <small class="text-danger">Left: ₹${row.balance_amount}</small>`;
                        }
                    },
                    {
                        data: 'id',
                        orderable: false,
                        render: function(data, type, row) {
                            let btn = '';
                            // 🔥 NAYA: View Button (Eye Icon)
btn += `<button class="btn btn-sm btn-light text-info me-1 btn-view-entry" data-id="${data}" data-category="${row.category}" title="View Details"><i class="fas fa-eye"></i></button>`;
                            btn +=
                                `<button class="btn btn-sm btn-light text-primary me-1 secured-item btn-edit-entry" data-id="${data}" data-permission="sites_edit" title="Edit"><i class="fas fa-edit"></i></button>`;

                            // 🔥 Print URL uses web route now to bypass API auth block
                           // 🔥 Dynamic portal prefix fetch karega (e.g., /admin ya /employee)
let portalPrefix = '/' + window.location.pathname.split('/')[1];
let printUrl = row.category === 'Vehicle Trip Slip' ?
    `${portalPrefix}/vehicle-trips/print?ids=${data}` : `${portalPrefix}/site-entries/print/${data}`;

                            btn +=
                                `<a href="${printUrl}" target="_blank" class="btn btn-sm btn-light text-success me-1 secured-item" data-permission="sites_print" title="Print"><i class="fas fa-print"></i></a>`;

                            // 🔥 History Button Logic
                            if (table.ajax.json().is_high_level) {
                                btn +=
                                    `<button class="btn btn-sm btn-light text-warning btn-view-history" data-id="${data}" title="View Edit History"><i class="fas fa-history"></i></button>`;
                            }
                            return btn;
                        }
                    }
                ],
                drawCallback: function(settings) {
                    isHighLevelUser = settings.json.is_high_level;

                    // 🔥 NAYA: Table load hote hi user_id save kar lo
                    globalUserId = settings.json.user_id;

                    window.applyPermissions();
                    loadMobileCards();
                }
            });

            // 1. Fetch Categories (Project Hata Diya, Other Update kar diya)
            $.get('/api/v1/site-entries/allowed-categories', function(res) {
                if (res.data && res.data.length > 0) {
                    globalAllocationId = res.allocation_id;
                    let btns = '';
                    res.data.forEach(cat => {
                        if (cat === 'Project Name') return; // Hide project
                        let displayCat = cat === 'Other Expenses' ? 'Other/Misc Expenses' : cat;
                        btns +=
                            `<button class="btn btn-outline-secondary category-btn" data-category="${cat}">${displayCat}</button>`;
                    });
                    $('#categoryFilterContainer').append(btns);
                }
            });

            $(document).on('click', '.category-btn', function() {
                $('.category-btn').removeClass('active');
                $(this).addClass('active');
                currentCategory = $(this).data('category');

                if (currentCategory !== 'All') {
                    $('#addBtnLabel').text(currentCategory);
                    $('#addEntryBtnContainer').removeClass('d-none');
                } else {
                    $('#addEntryBtnContainer').addClass('d-none');
                }

                if (currentCategory === 'Vehicle Trip Slip') {
                    $('#btnPrintTripSlips').removeClass('d-none');
                } else {
                    $('#btnPrintTripSlips').addClass('d-none');
                }

                table.ajax.reload();
            });

            // 3. Open Form Logic
            $('#btnOpenEntryForm').on('click', function() {
                if (!globalAllocationId) {
                    Swal.fire('Error', 'No active allocation.', 'error');
                    return;
                }

                let formHtml = $('#entryFormTemplate').html();
                if ($(window).width() >= 768) {
                    $activeFormContainer = $('#desktopModalBody');
                    $activeFormContainer.html(formHtml);
                    $('#desktopModal').modal('show');
                } else {
                    $activeFormContainer = $('#mobileOffcanvasBody');
                    $activeFormContainer.html(formHtml);
                    $('#mobileOffcanvas').offcanvas('show');
                }

                $activeFormContainer.find('.allocation-id-input').val(globalAllocationId);
                $activeFormContainer.find('.category-input').val(currentCategory);
                $('.modal-main-title, .offcanvas-main-title').text('Daily Entry: ' + currentCategory);
                $activeFormContainer.find('.dynamic-rows-container').empty();

                // 🔥 NAYA: TRIPS SLIP KE LIYE CUSTOM HEADER 🔥
                $activeFormContainer.find('.trip-global-fields').remove();
                if (currentCategory === 'Vehicle Trip Slip') {
                    $activeFormContainer.find('.dynamic-rows-container').before(`
                        <div class="row g-2 mb-3 trip-global-fields border-bottom pb-3">
                            <div class="col-6">
                                <label class="small text-muted fw-bold">Project / Phase</label>
                                <select name="phase_name" class="form-select form-select-sm" required>
                                    <option value="MAIN PROJECT">Main Project</option>
                                    <option value="JANKI VILLA PHASE-II">Janki Villa Phase-II</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted fw-bold">Slip Type</label>
                                <input list="slipTypes" name="slip_type" class="form-control form-control-sm text-uppercase" placeholder="e.g. SOIL, SARIYA" required>
                                <datalist id="slipTypes">
                                    <option value="SOIL">
                                    <option value="RED SAND">
                                    <option value="WHITE SAND">
                                    <option value="SARIYA">
                                    <option value="CEMENT">
                                </datalist>
                            </div>
                        </div>
                    `);
                }

                fileTracker = new DataTransfer(); // Reset files
                rowIndex = 0;
                appendNewRow(currentCategory);
            });

            $(document).on('click', '.btn-add-row', function() {
                appendNewRow(currentCategory);
            });
            $(document).on('click', '.btn-remove-row', function() {
                $(this).closest('.entry-row').remove();
            });

           // 2. 🔥 DYNAMIC ROWS (Fix: Goods Carrier, Material Shop Details & Labour Images)
            function appendNewRow(category) {
                let fieldsHtml = '';
                let rowIdClass = `row-index-${rowIndex}`;

                if (category === 'Labour') {
                    fieldsHtml = `
                    <div class="row g-2">
                        <div class="col-6"><input type="text" name="entries[${rowIndex}][details][name]" class="form-control form-control-sm" placeholder="Labour Name" required></div>
                        <div class="col-6"><input type="text" name="entries[${rowIndex}][details][phone]" class="form-control form-control-sm" placeholder="Phone"></div>
                        <div class="col-4"><input type="number" name="entries[${rowIndex}][details][rate]" class="form-control form-control-sm calc-trigger" placeholder="Rate" required></div>
                        <div class="col-4"><select name="entries[${rowIndex}][details][rate_type]" class="form-select form-select-sm calc-trigger"><option value="per_day">Per Day</option><option value="per_hour">Per Hour</option><option value="per_minute">Per Minute</option></select></div>
                        <div class="col-4"><input type="time" name="entries[${rowIndex}][details][start_time]" class="form-control form-control-sm calc-trigger"></div>
                        <div class="col-4"><input type="time" name="entries[${rowIndex}][details][end_time]" class="form-control form-control-sm calc-trigger"></div>
                        
                        <!-- 🔥 FIX: Labour Arrival & Departure Images -->
                       <div class="col-6 mt-2">
                    <label class="small text-muted fw-bold">Arrival Image <span class="text-danger">*</span></label>
                    <input type="file" name="entries[${rowIndex}][arrival_image]" class="form-control form-control-sm single-img-input" accept="image/*" required>
                    <div class="single-preview"></div>
                </div>
                <div class="col-6 mt-2">
                    <label class="small text-muted fw-bold">Departure Image <span class="text-danger">*</span></label>
                    <input type="file" name="entries[${rowIndex}][departure_image]" class="form-control form-control-sm single-img-input" accept="image/*" required>
                    <div class="single-preview"></div>
                </div>
                    </div>`;
                } else if (category === 'Material') {
                    let shopOptions = globalShops.map(s => `<option value="${s.shop_name}">`).join('');
                    fieldsHtml = `
                    <div class="row g-2">
                        <!-- 🔥 FIX: Shop Details Added Back -->
                        <div class="col-12"><input list="shopList" name="entries[${rowIndex}][details][shop_name]" class="form-control form-control-sm shop-select" placeholder="Shop Name" required><datalist id="shopList">${shopOptions}</datalist></div>
                        <div class="col-6"><input type="text" name="entries[${rowIndex}][details][gst_number]" class="form-control form-control-sm" placeholder="GST Number"></div>
                        <div class="col-6"><input type="text" name="entries[${rowIndex}][details][mobile_number]" class="form-control form-control-sm" placeholder="Mobile Number" required></div>
                        <div class="col-6"><input type="text" name="entries[${rowIndex}][details][alt_number]" class="form-control form-control-sm" placeholder="Alternate Number"></div>
                        <div class="col-6"><input type="text" name="entries[${rowIndex}][details][location]" class="form-control form-control-sm" placeholder="Location"></div>
                        <div class="col-12"><textarea name="entries[${rowIndex}][details][address]" class="form-control form-control-sm" placeholder="Full Address" rows="1"></textarea></div>
                        
                        <div class="col-6"><input type="text" name="entries[${rowIndex}][details][brand_company]" class="form-control form-control-sm" placeholder="Brand/Company (e.g. ACC, Tata)" required></div>
                        <div class="col-6"><input type="text" name="entries[${rowIndex}][details][material_name]" class="form-control form-control-sm" placeholder="Material Name" required></div>
                        <div class="col-4"><input type="number" name="entries[${rowIndex}][details][quantity]" class="form-control form-control-sm calc-trigger" placeholder="Qty" required></div>
                        <div class="col-4"><input type="number" name="entries[${rowIndex}][details][rate]" class="form-control form-control-sm calc-trigger" placeholder="Rate" required></div>
                        <div class="col-4">
                            <select name="entries[${rowIndex}][details][rate_type]" class="form-select form-select-sm">
                                <option value="per_sack">Per Sack</option><option value="per_kg">Per Kg</option>
                                <option value="per_ton">Per Ton</option><option value="per_quintal">Per Quintal</option>
                                <option value="per_piece">Per Piece</option>
                            </select>
                        </div>
                    </div>`;
                } else if (category === 'Goods Carrier') {
                    // 🔥 FIX: Goods Carrier block added back with Trip & Images
                    fieldsHtml = `
                    <div class="row g-2">
                        <div class="col-6"><input type="text" name="entries[${rowIndex}][details][vehicle_number]" class="form-control form-control-sm" placeholder="Vehicle Number" required></div>
                        <div class="col-6"><input type="text" name="entries[${rowIndex}][details][owner_name]" class="form-control form-control-sm" placeholder="Owner/Transporter Name"></div>
                        <div class="col-6"><input type="text" name="entries[${rowIndex}][details][material_type]" class="form-control form-control-sm" placeholder="Material Type (e.g. Sand)"></div>
                        <div class="col-6"><input type="number" name="entries[${rowIndex}][details][trips]" class="form-control form-control-sm calc-trigger" placeholder="Total Trips" required></div>
                        <div class="col-6"><input type="number" name="entries[${rowIndex}][details][rate]" class="form-control form-control-sm calc-trigger" placeholder="Rate Per Trip" required></div>
                        
                        <div class="col-6 mt-2">
                            <label class="small text-muted fw-bold">Arrival Image</label>
                            <input type="file" name="entries[${rowIndex}][arrival_image]" class="form-control form-control-sm" accept="image/*">
                        </div>
                        <div class="col-6 mt-2">
                            <label class="small text-muted fw-bold">Departure Image</label>
                            <input type="file" name="entries[${rowIndex}][departure_image]" class="form-control form-control-sm" accept="image/*">
                        </div>
                    </div>`;
                } else if (category === 'Construction Equipment Vehicle') {
                    fieldsHtml = `
                    <div class="row g-2">
                        <div class="col-6"><input type="text" name="entries[${rowIndex}][details][owner_name]" class="form-control form-control-sm" placeholder="Owner Name" required></div>
                        <div class="col-6"><input type="text" name="entries[${rowIndex}][details][vehicle_number]" class="form-control form-control-sm" placeholder="Vehicle Number"></div>
                        <div class="col-4"><input type="number" name="entries[${rowIndex}][details][rate]" class="form-control form-control-sm calc-trigger" placeholder="Rate" required></div>
                        <div class="col-4"><select name="entries[${rowIndex}][details][rate_type]" class="form-select form-select-sm calc-trigger"><option value="per_day">Per Day</option><option value="per_hour">Per Hour</option></select></div>
                        <div class="col-6"><input type="time" name="entries[${rowIndex}][details][start_time]" class="form-control form-control-sm calc-trigger"></div>
                        <div class="col-6"><input type="time" name="entries[${rowIndex}][details][end_time]" class="form-control form-control-sm calc-trigger"></div>
                    </div>`;
                } else if (category === 'Vehicle Trip Slip') {
                    fieldsHtml = `
                    <div class="row g-2">
                        <div class="col-12"><input type="text" name="trips[${rowIndex}][vehicle_number]" class="form-control form-control-sm text-uppercase" placeholder="Vehicle No. (e.g. BR01GF1234)" required></div>
                        <div class="col-6"><label class="small text-muted">Arrival Time</label><input type="time" name="trips[${rowIndex}][arrival_time]" class="form-control form-control-sm" required></div>
                        <div class="col-6"><label class="small text-muted">Departure Time</label><input type="time" name="trips[${rowIndex}][departure_time]" class="form-control form-control-sm"></div>
                        <div class="col-6">
                            <label class="small text-muted">Arrival Image</label>
                            <input type="file" name="trips[${rowIndex}][arrival_image]" class="form-control form-control-sm" accept="image/*">
                        </div>
                        <div class="col-6">
                            <label class="small text-muted">Departure Image</label>
                            <input type="file" name="trips[${rowIndex}][departure_image]" class="form-control form-control-sm" accept="image/*">
                        </div>
                    </div>`;
                } else if (category.includes('Other')) {
                    fieldsHtml = `
                    <div class="row g-2">
                        <div class="col-12"><input type="text" name="entries[${rowIndex}][details][expense_name]" class="form-control form-control-sm" placeholder="Expense Name" required></div>
                        <div class="col-6"><input type="number" name="entries[${rowIndex}][details][price_per_piece]" class="form-control form-control-sm calc-trigger" placeholder="Price Per Piece" required></div>
                        <div class="col-6"><input type="number" name="entries[${rowIndex}][details][quantity]" class="form-control form-control-sm calc-trigger" placeholder="Qty" value="1" required></div>
                    </div>`;
                }

                let commonHtml = '';
                if (category !== 'Vehicle Trip Slip') {
                    commonHtml = `
                    <div class="row g-2 mt-2 pt-2 border-top">
                        <div class="col-4"><label class="small text-muted">Total</label><input type="number" name="entries[${rowIndex}][total_amount]" class="form-control form-control-sm bg-light total-field" readonly></div>
                        <div class="col-4"><label class="small text-muted">Paid</label><input type="number" name="entries[${rowIndex}][paid_amount]" class="form-control form-control-sm calc-trigger paid-field" value="0"></div>
                        <div class="col-4"><label class="small text-muted">Left</label><input type="number" class="form-control form-control-sm bg-light balance-field" readonly></div>
                        
                        <div class="col-12 mt-2">
                            <label class="small text-muted fw-bold text-danger">Attach Documents *</label>
                            <input type="file" name="entries[${rowIndex}][documents][]" class="form-control form-control-sm file-input" accept="image/*,.pdf" multiple required>
                            <div class="file-preview-container" id="preview-${rowIndex}"></div>
                        </div>
                    </div>`;
                } else {
                    commonHtml = `<hr class="mt-3">`;
                }

                $activeFormContainer.find('.dynamic-rows-container').append(`
                    <div class="entry-row ${rowIdClass}" data-row-id="${rowIndex}" data-category="${category}">
                        ${rowIndex > 0 ? `<button type="button" class="btn btn-sm btn-danger rounded-circle shadow-sm btn-remove-row"><i class="fas fa-times"></i></button>` : ''}
                        <div class="fw-bold text-primary mb-2 small">#${rowIndex + 1}</div>
                        ${fieldsHtml} ${commonHtml}
                    </div>
                `);
                rowIndex++;
            }

            // 🔥 SHOP AUTO FILL LOGIC
            $(document).on('change', '.shop-select', function() {
                let val = $(this).val();
                let shop = globalShops.find(s => s.shop_name === val);
                if (shop) {
                    let row = $(this).closest('.entry-row');
                    row.find('[name$="[gst_number]"]').val(shop.gst_number || '');
                    row.find('[name$="[mobile_number]"]').val(shop.mobile_number || '');
                    row.find('[name$="[alt_number]"]').val(shop.alt_number || '');
                    row.find('[name$="[location]"]').val(shop.location || '');
                    row.find('[name$="[address]"]').val(shop.address || '');
                }
            });

            // ... (Calculate Row Totals Logic) ...
            function calculateRowTotals(row) {
                let category = row.data('category');
                let total = 0;
                let rate = parseFloat(row.find('[name$="[rate]"]').val()) || 0;

                if (category === 'Labour' || category === 'Construction Equipment Vehicle') {
                    let rateType = row.find('[name$="[rate_type]"]').val();
                    if (rateType === 'per_hour' || rateType === 'per_minute') {
                        let start = row.find('[name$="[start_time]"]').val();
                        let end = row.find('[name$="[end_time]"]').val();
                        if (start && end) {
                            let s = new Date(`1970-01-01T${start}:00`);
                            let e = new Date(`1970-01-01T${end}:00`);
                            let diffMs = e - s;
                            if (diffMs < 0) diffMs += (24 * 60 * 60 * 1000);
                            let minutes = diffMs / 1000 / 60;
                            total = (rateType === 'per_hour') ? (minutes / 60) * rate : minutes * rate;
                        }
                    } else {
                        total = rate;
                    }
                } else if (category === 'Material') {
                    total = (parseFloat(row.find('[name$="[quantity]"]').val()) || 0) * rate;
                } else if (category === 'Goods Carrier') {
                    total = (parseFloat(row.find('[name$="[trips]"]').val()) || 0) * rate;
                } else if (category.includes('Other')) {
                    total = (parseFloat(row.find('[name$="[quantity]"]').val()) || 0) * (parseFloat(row.find(
                        '[name$="[price_per_piece]"]').val()) || 0);
                } else {
                    total = rate;
                }

                row.find('.total-field').val(total.toFixed(2));
                row.find('.balance-field').val((total - (parseFloat(row.find('.paid-field').val()) || 0)).toFixed(
                    2));
            }
            $(document).on('input change', '.calc-trigger', function() {
                calculateRowTotals($(this).closest('.entry-row'));
            });

            // 🔥 MULTIPLE FILE PREVIEW & REMOVE LOGIC
            $(document).on('change', '.file-input', function(e) {
                let rowId = $(this).closest('.entry-row').data('row-id');
                let previewBox = $(`#preview-${rowId}`);

                Array.from(this.files).forEach((file) => {
                    // if (file.size > 1048576) {
                    //     Swal.fire('Too Large', 'Files must be < 1MB', 'warning');
                    //     return;
                    // }
                    fileTracker.items.add(file); // Store in global DataTransfer object

                    let url = file.type.startsWith('image/') ? URL.createObjectURL(file) :
                        'https://cdn-icons-png.flaticon.com/512/337/337946.png'; // generic PDF icon
                    previewBox.append(`
                        <div class="file-preview-box" data-name="${file.name}">
                            <img src="${url}">
                            <a class="remove-file" title="Remove"><i class="fas fa-times"></i></a>
                        </div>
                    `);
                });

                // Re-assign updated files to input
                this.files = fileTracker.files;
            });

            $(document).on('click', '.remove-file', function() {
                let box = $(this).closest('.file-preview-box');
                let nameToRemove = box.data('name');
                let input = $(this).closest('.entry-row').find('.file-input')[0];

                // Remove from DataTransfer
                let newDt = new DataTransfer();
                Array.from(fileTracker.files).forEach(f => {
                    if (f.name !== nameToRemove) newDt.items.add(f);
                });
                fileTracker = newDt;
                input.files = fileTracker.files; // Update input
                box.remove();
            });

            // 4. 🔥 ENHANCED EDIT HISTORY TRACKER (Paid Amount & JSON Details)
            $(document).on('click', '.btn-view-history', function() {
                let id = $(this).data('id');
                $.get(`/api/v1/site-entries/history/${id}`, function(res) {
                    let html = '';
                    if (res.data.length === 0) html =
                        '<p class="text-muted text-center">No edits made yet.</p>';

                    res.data.forEach(h => {
                        // Find all differences in details JSON
                        let diffHtml = '';
                        if (h.old_data.entry_details && h.new_data.entry_details) {
                            for (let key in h.new_data.entry_details) {
                                let oldV = h.old_data.entry_details[key] || '-';
                                let newV = h.new_data.entry_details[key] || '-';
                                if (oldV != newV) {
                                    diffHtml +=
                                        `<div><small class="text-muted text-uppercase">${key}:</small> <del class="text-danger">${oldV}</del> <i class="fas fa-arrow-right mx-1"></i> <span class="text-success fw-bold">${newV}</span></div>`;
                                }
                            }
                        }

                        html += `
                        <div class="border-start border-warning border-3 ps-3 mb-3 bg-light p-2 rounded">
                            <div class="d-flex justify-content-between">
                                <small class="text-muted fw-bold"><i class="fas fa-clock"></i> ${new Date(h.created_at).toLocaleString()}</small>
                                <small class="text-primary fw-bold">${h.editor ? h.editor.full_name : 'Admin'}</small>
                            </div>
                            <hr class="my-1">
                            <div class="row text-center small fw-bold mb-2">
                                <div class="col-4 text-dark border-end">Total<br><del class="text-danger">₹${h.old_data.total_amount}</del> <i class="fas fa-arrow-right"></i> <span class="text-success">₹${h.new_data.total_amount}</span></div>
                                <div class="col-4 text-success border-end">Paid<br><del class="text-danger">₹${h.old_data.paid_amount}</del> <i class="fas fa-arrow-right"></i> <span>₹${h.new_data.paid_amount}</span></div>
                                <div class="col-4 text-danger">Left<br><del class="text-secondary">₹${h.old_data.balance_amount}</del> <i class="fas fa-arrow-right"></i> <span>₹${h.new_data.balance_amount}</span></div>
                            </div>
                            ${diffHtml ? `<div class="p-2 border rounded bg-white">${diffHtml}</div>` : ''}
                        </div>`;
                    });
                    $('#historyTimeline').html(html);
                    $('#historyModal').modal('show');
                });
            });

            // Mobile Cards Rendering
            function loadMobileCards() {
                if ($(window).width() >= 768) return;
                $.get(`/api/v1/site-entries?category=${currentCategory === 'All'?'':currentCategory}`, function(
                res) {
                    let html = '';
                    res.data.forEach(item => {
                       let portalPrefix = '/' + window.location.pathname.split('/')[1];
let printUrl = item.category === 'Vehicle Trip Slip' ?
    `${portalPrefix}/vehicle-trips/print?ids=${item.id}` :
    `${portalPrefix}/site-entries/print/${item.id}`;
                        html += `
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <input type="checkbox" class="form-check-input row-checkbox" value="${item.id}">
                                    <span class="badge bg-secondary">${item.category}</span>
                                    <small class="text-muted ms-auto">${item.entry_date}</small>
                                </div>
                                <div class="fw-bold mb-2">${item.category === 'Vehicle Trip Slip' ? '- Trip Record -' : '₹'+item.total_amount+' (Paid: ₹'+item.paid_amount+')'}</div>
                                <div class="d-flex justify-content-end gap-2 mt-2 border-top pt-2">
                                <button class="btn btn-sm btn-light text-info btn-view-entry" data-id="${item.id}" data-category="${item.category}"><i class="fas fa-eye"></i> View</button>
                                    <button class="btn btn-sm btn-light text-primary btn-edit-entry" data-id="${item.id}"><i class="fas fa-edit"></i> Edit</button>
                                    <a href="${printUrl}" target="_blank" class="btn btn-sm btn-light text-success secured-item" data-permission="sites_print"><i class="fas fa-print"></i></a>
                                    ${isHighLevelUser ? `<button class="btn btn-sm btn-light text-warning btn-view-history" data-id="${item.id}"><i class="fas fa-history"></i></button>` : ''}
                                </div>
                            </div>
                        </div>`;
                    });
                    $('#mobileCardsContainer').html(html);
                });
            }

            // FLOATING BULK BAR CHECKBOXES
            $(document).on('change', '.row-checkbox', function() {
                let val = $(this).val();
                if ($(this).is(':checked')) selectedIds.add(val);
                else selectedIds.delete(val);
                $('#selectedCount').text(`${selectedIds.size} Selected`);
                if (selectedIds.size > 0) $('#bulkActionBar').addClass('show');
                else $('#bulkActionBar').removeClass('show');
            });

            $('#btnBulkDelete').on('click', function() {
                Swal.fire({
                    title: 'Delete Selected?',
                    icon: 'warning',
                    showCancelButton: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('/api/v1/site-entries/bulk-delete', {
                            ids: Array.from(selectedIds)
                        }, function(res) {
                            Swal.fire('Deleted!', res.message, 'success');
                            selectedIds.clear();
                            $('#bulkActionBar').removeClass('show');
                            table.ajax.reload();
                        });
                    }
                });
            });

            
          // Open Print Config Modal
            $('#btnPrintTripSlips').on('click', function() {
                $.get('/api/v1/context', function(res) {
                    $('#printSlipCompanyId').val(res.company_id || 1);
                    
                    // 🔥 NAYA: Form khulte hi hidden field me ID daal do
                    $('#printSlipUserId').val(globalUserId); 
                    
                    $('#printTripConfigModal').modal('show');
                });
            });
            
            // Close modal after form submit
            $('#printBulkSlipForm').on('submit', function() {
                setTimeout(() => { $('#printTripConfigModal').modal('hide'); }, 500);
            });

            // ========================================================
            // 🔥 EDIT BUTTON CLICK LOGIC (Console Error Fixed) 🔥
            // ========================================================
            $(document).on('click', '.btn-edit-entry', function() {
                let id = $(this).data('id');
                Swal.fire({ title: 'Loading...', didOpen: () => Swal.showLoading() });

                $.get('/api/v1/site-entries/' + id, function(res) {
                    Swal.close();
                    let entry = res.data;
                    
                    // 🔥 FIX: Ye line $.get ke andar honi chahiye thi
                    $('.modal-main-title, .offcanvas-main-title').text('Edit Entry: ' + entry.category);

                    let formHtml = $('#entryFormTemplate').html();
                    $activeFormContainer = $(window).width() >= 768 ? $('#desktopModalBody') : $('#mobileOffcanvasBody');
                    $activeFormContainer.html(formHtml);
                    $(window).width() >= 768 ? $('#desktopModal').modal('show') : $('#mobileOffcanvas').offcanvas('show');

                    // Set IDs & Date Fix
                    $activeFormContainer.find('.edit-id-input').val(entry.id);
                    $activeFormContainer.find('.allocation-id-input').val(entry.site_allocation_id);
                    $activeFormContainer.find('.category-input').val(entry.category);

                    if (entry.entry_date) {
                        $activeFormContainer.find('.date-input').val(entry.entry_date.split('T')[0]);
                    }
                    $activeFormContainer.find('.btn-add-row').hide();
                    $activeFormContainer.find('.dynamic-rows-container').empty();
                    rowIndex = 0;
                    appendNewRow(entry.category);

                    let rowContainer = $activeFormContainer.find('.entry-row').first();
                    rowContainer.find('.btn-remove-row').hide();

                    // Load Old Documents
                    if (entry.documents && entry.documents.length > 0) {
                        $activeFormContainer.find('.file-input').removeAttr('required');
                        let previewBox = $activeFormContainer.find('.file-preview-container');
                        entry.documents.forEach(doc => {
                            let url = '/' + doc.file_path;
                            previewBox.append(`
                                <div class="file-preview-box">
                                    <a href="${url}" target="_blank" title="Click to Zoom"><img src="${url}"></a>
                                    <a href="#" class="remove-file bg-danger text-white px-1 rounded-circle position-absolute top-0 end-0" style="text-decoration:none;" title="Cannot remove old file here (admin only)"><i class="fas fa-times" style="font-size:10px;"></i></a>
                                </div>
                            `);
                        });
                    }

                    if (entry.entry_details) {
                        for (let key in entry.entry_details) {
                            rowContainer.find(`[name="entries[0][details][${key}]"]`).val(entry.entry_details[key]);
                        }
                    }
                    rowContainer.find('.total-field').val(entry.total_amount);
                    rowContainer.find('.paid-field').val(entry.paid_amount);
                    rowContainer.find('.balance-field').val(entry.balance_amount);
                });
            });


            // 🔥 NEW: Single Image Preview & Remove Logic (For Arrival/Departure)
            $(document).on('change', '.single-img-input', function(e) {
                let file = this.files[0];
                let previewContainer = $(this).siblings('.single-preview');
                previewContainer.empty();
                if (file) {
                    let url = URL.createObjectURL(file);
                    previewContainer.append(`<div class="position-relative d-inline-block mt-2"><img src="${url}" style="width: 70px; height: 70px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;"><a href="#" class="remove-single position-absolute top-0 end-0 bg-danger text-white rounded-circle text-center" style="width: 18px; height: 18px; font-size: 11px; text-decoration: none; transform: translate(50%, -50%);"><i class="fas fa-times"></i></a></div>`);
                }
            });

            $(document).on('click', '.remove-single', function(e) {
                e.preventDefault();
                let container = $(this).closest('.single-preview');
                container.siblings('.single-img-input').val('');
                container.empty();
            });

            // 🔥 VIEW MODAL FIX: Show Images inside JSON details
            $(document).on('click', '.btn-view-entry', function() {
                let id = $(this).data('id');
                let category = $(this).data('category');
                let typeParam = category === 'Vehicle Trip Slip' ? 'trip' : 'normal';

                Swal.fire({ title: 'Loading...', didOpen: () => Swal.showLoading() });

                $.get(`/api/v1/site-entries/${id}?type=${typeParam}`, function(res) {
                    Swal.close();
                    let entry = res.data;
                    let html = '<table class="table table-bordered table-striped small mb-0"><tbody>';
                    
                    let dateDisplay = entry.entry_date ? entry.entry_date.split('T')[0] : '-';
                    let enteredBy = entry.entered_by ? entry.entered_by.full_name : 'Admin';

                    html += `<tr><th class="bg-light" style="width: 35%;">Date</th><td><strong>${dateDisplay}</strong></td></tr>`;
                    html += `<tr><th class="bg-light">Category</th><td><span class="badge bg-secondary">${entry.category}</span></td></tr>`;
                    html += `<tr><th class="bg-light">Entered By</th><td>${enteredBy}</td></tr>`;
                    
                    if (entry.entry_details) {
                        for (let key in entry.entry_details) {
                            let val = entry.entry_details[key] || '-';
                            let formattedKey = key.replace(/_/g, ' ').toUpperCase();
                            
                            // Agar Arrival ya Departure Image hai to Photo dikhao
                            if (key === 'arrival_image' || key === 'departure_image') {
                                let url = '/' + val;
                                html += `<tr><th class="bg-light">${formattedKey}</th><td><a href="${url}" target="_blank"><img src="${url}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ccc;"></a></td></tr>`;
                            } else {
                                html += `<tr><th class="bg-light">${formattedKey}</th><td>${val}</td></tr>`;
                            }
                        }
                    }
                    
                    if (category !== 'Vehicle Trip Slip') {
                        html += `<tr><th class="bg-light">Total Amount</th><td class="text-dark fw-bold">₹${entry.total_amount}</td></tr>`;
                        html += `<tr><th class="bg-light">Paid Amount</th><td class="text-success fw-bold">₹${entry.paid_amount}</td></tr>`;
                        html += `<tr><th class="bg-light text-danger">Left Balance</th><td class="text-danger fw-bold">₹${entry.balance_amount}</td></tr>`;
                    }

                    if (entry.documents && entry.documents.length > 0) {
                        html += `<tr><th class="bg-light">Attachments</th><td><div class="d-flex gap-2 flex-wrap">`;
                        entry.documents.forEach(doc => {
                            let url = '/' + doc.file_path;
                            html += `<a href="${url}" target="_blank" class="border rounded p-1 d-inline-block shadow-sm"><img src="${url}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;"></a>`;
                        });
                        html += `</div></td></tr>`;
                    }

                    html += '</tbody></table>';
                    $('#viewEntryModalBody').html(html);
                    let portalPrefix = '/' + window.location.pathname.split('/')[1];
                    $('#btnViewPrint').attr('href', category === 'Vehicle Trip Slip' ? `${portalPrefix}/vehicle-trips/print?ids=${entry.id}` : `${portalPrefix}/site-entries/print/${entry.id}`);
                    $('#viewEntryModal').modal('show');
                });
            });



            // ========================================================
            // 🔥 UPDATED SAVE/EDIT FORM SUBMIT LOGIC 🔥
            // ========================================================
            $(document).on('submit', '.daily-entry-form', function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                let editId = $activeFormContainer.find('.edit-id-input').val(); // Check if edit mode
                let category = $activeFormContainer.find('.category-input').val(); // Category check

                // Agar edit mode hai toh URL /update/{id} hogi, nahi toh simple store url hogi
                let url = editId ? '/api/v1/site-entries/update/' + editId : '/api/v1/site-entries';

                // 🔥 Agar Trip Slip hai, toh route change karo
                if (category === 'Vehicle Trip Slip') {
                    url = '/api/v1/vehicle-trips/store';
                }

                Swal.fire({
                    title: 'Processing...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        Swal.fire('Success', res.message, 'success');
                        $('.modal').modal('hide');
                        $('.offcanvas').offcanvas('hide');
                        table.ajax.reload();

                       // 🔥 NAYA: Success hote hi automatically Print Tab open hoga
if (res.print_ids) {
    let portalPrefix = '/' + window.location.pathname.split('/')[1];
    window.open(`${portalPrefix}/vehicle-trips/print?ids=` + res.print_ids, '_blank');
}
                    },
                    error: function(err) {
                        Swal.fire('Error', err.responseJSON?.message || 'Failed to process',
                            'error');
                    }
                });
            });
        });

        
        // ========================================================
            // 🔥 NAYA: VIEW BUTTON KA LOGIC 🔥
            // ========================================================
            $(document).on('click', '.btn-view-entry', function() {
                let id = $(this).data('id');
                let category = $(this).data('category');
                let typeParam = category === 'Vehicle Trip Slip' ? 'trip' : 'normal';

                Swal.fire({ title: 'Loading...', didOpen: () => Swal.showLoading() });

                $.get(`/api/v1/site-entries/${id}?type=${typeParam}`, function(res) {
                    Swal.close();
                    let entry = res.data;
                    
                    let html = '<table class="table table-bordered table-striped small mb-0">';
                    html += `<tbody>`;
                    
                    // Basic Details
                    let dateDisplay = entry.entry_date ? entry.entry_date.split('T')[0] : '-';
                    let enteredBy = entry.entered_by ? entry.entered_by.full_name : 'Admin';

                    html += `<tr><th class="bg-light" style="width: 35%;">Date</th><td><strong>${dateDisplay}</strong></td></tr>`;
                    html += `<tr><th class="bg-light">Category</th><td><span class="badge bg-secondary">${entry.category}</span></td></tr>`;
                    html += `<tr><th class="bg-light">Entered By</th><td>${enteredBy}</td></tr>`;
                    
                    // JSON Fields (Shop Name, Rate, Qty, Vehicle No. sab aayega isme)
                    if (entry.entry_details) {
                        for (let key in entry.entry_details) {
                            let formattedKey = key.replace(/_/g, ' ').toUpperCase();
                            let val = entry.entry_details[key] || '-';
                            html += `<tr><th class="bg-light">${formattedKey}</th><td>${val}</td></tr>`;
                        }
                    }
                    
                    // Financials (Skip for Vehicle Trips)
                    if (category !== 'Vehicle Trip Slip') {
                        html += `<tr><th class="bg-light">Total Amount</th><td class="text-dark fw-bold">₹${entry.total_amount}</td></tr>`;
                        html += `<tr><th class="bg-light">Paid Amount</th><td class="text-success fw-bold">₹${entry.paid_amount}</td></tr>`;
                        html += `<tr><th class="bg-light text-danger">Left Balance</th><td class="text-danger fw-bold">₹${entry.balance_amount}</td></tr>`;
                    }

                    // Attachments (Images)
                    if (entry.documents && entry.documents.length > 0) {
                        html += `<tr><th class="bg-light">Attachments</th><td><div class="d-flex gap-2 flex-wrap">`;
                        entry.documents.forEach(doc => {
                            let url = '/' + doc.file_path;
                            html += `<a href="${url}" target="_blank" class="border rounded p-1 d-inline-block shadow-sm"><img src="${url}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;"></a>`;
                        });
                        html += `</div></td></tr>`;
                    }

                    html += '</tbody></table>';

                    $('#viewEntryModalBody').html(html);
                    
                    // Set Print Button URL properly inside modal
                    let portalPrefix = '/' + window.location.pathname.split('/')[1];
                    let printUrl = category === 'Vehicle Trip Slip' ? 
                        `${portalPrefix}/vehicle-trips/print?ids=${entry.id}` : 
                        `${portalPrefix}/site-entries/print/${entry.id}`;
                    
                    $('#btnViewPrint').attr('href', printUrl);
                    $('#viewEntryModal').modal('show');
                });
            });
    </script>
@endpush
