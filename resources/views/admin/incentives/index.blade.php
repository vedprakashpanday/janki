@extends('layout.app')
@section('title', 'Incentives')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <!-- ========================================== -->
    <!-- FLOATING ACTION BAR FOR BULK DELETE -->
    <!-- ========================================== -->
    <style>
        .floating-action-bar {
            position: fixed;
            bottom: -100px;
            left: 50%;
            transform: translateX(-50%);
            background: #343a40;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: bottom 0.3s ease-in-out;
            z-index: 1050;
        }

        .floating-action-bar.show {
            bottom: 30px;
        }

        /* Fix for Select2 focus inside Bootstrap 5 Modal */
        .select2-container--open {
            z-index: 9999999 !important;
        }
    </style>

    <div class="floating-action-bar" id="floatingActionBar">
        <span id="selectedCount" class="fw-bold">0 Selected</span>
        <div class="vr bg-white mx-1"></div>
        <button class="btn btn-sm btn-light rounded-pill" id="floatSelectAllBtn">Select All</button>
        <button class="btn btn-sm btn-danger rounded-pill" id="floatDeleteBtn">
            <i class="fa fa-trash"></i> Delete Selected
        </button>
    </div>

    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Employee Incentives</h4>
                <div id="topActionButtons"></div>
            </div>
        </div>

        <!-- 💻 DESKTOP VIEW -->
        <div class="card d-none d-md-block">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="incentiveTable" style="width:100%">
                        <thead>
                            <tr>
                                <th class="checkbox-col d-none"><input type="checkbox" id="selectAllCheckbox"></th>
                                <th>Emp Name</th>
                                <th>ID</th>
                                <th>Company</th>
                                <th>Type</th>
                                <th>Net Amt</th>
                                <th>Value</th>
                                <th>Calculated Amt</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 📱 MOBILE VIEW -->
        <div class="d-block d-md-none">
            <div id="mobileCardsContainer" class="row"></div>
            <div class="text-center mt-3 mb-4">
                <button id="loadMoreBtn" class="btn btn-primary rounded-pill px-4 d-none">
                    Load More <i class="fa-solid fa-chevron-down"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MAIN INCENTIVE MODAL -->
    <!-- ========================================== -->
    <div class="modal fade" id="addIncentiveModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add Incentive</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="incentiveForm">
                        <input type="hidden" id="incentive_status_payload" name="status" value="pending">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Select Company <span class="text-danger">*</span></label>
                                <select class="form-select select2-ajax" id="company_id" name="company_ids[]"
                                    multiple="multiple" required></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Select Branch <span class="text-danger">*</span></label>
                                <select class="form-select select2-ajax" id="branch_id" name="branch_ids[]"
                                    multiple="multiple" required></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Select Department</label>
                                <select class="form-select select2-ajax" id="department_id" name="department_ids[]"
                                    multiple="multiple"></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Select Designation</label>
                                <select class="form-select select2-ajax" id="designation_id" name="designation_ids[]"
                                    multiple="multiple"></select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Select Employees <span class="text-danger">*</span></label>
                                <select class="form-select select2-ajax" id="emp_ids" name="emp_ids[]" multiple="multiple"
                                    required></select>
                            </div>

                            <hr class="my-3">

                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-label mb-0">Incentive Type <span class="text-danger">*</span></label>
                                    <span class="badge bg-secondary cursor-pointer" onclick="openNestedModal()">+ add
                                        incentive type</span>
                                </div>
                                <select class="form-select mt-2" id="incentive_type_id" name="incentive_type_id" required>
                                    <option value="">Select Type</option>
                                </select>
                            </div>

                            <div class="col-md-6 d-none" id="passbookContainer">
                                <label class="form-label mt-2">Passbook Number</label>
                                <input type="text" class="form-control" id="passbook_no" name="passbook_no"
                                    placeholder="Enter Passbook No">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Net Amount (₹)</label>
                                <input type="number" class="form-control calc-trigger" id="net_amount"
                                    name="net_amount" min="0" step="0.01">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Incentive Setup</label>
                                <div class="input-group">
                                    <input type="number" class="form-control calc-trigger" id="value"
                                        name="value" placeholder="10" required>
                                    <select class="form-select calc-trigger" id="calc_type" name="calc_type">
                                        <option value="percentage">%</option>
                                        <option value="amount">₹ (Amt)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Distribution</label>
                                <select class="form-select calc-trigger" id="dist_type" name="dist_type">
                                    <option value="each">Each Employee</option>
                                    <option value="all">Divide in All</option>
                                </select>
                            </div>

                            <div class="col-12 mt-3">
                                <div class="alert alert-info text-center mb-0">
                                    <strong>Computed Final Incentive: </strong>
                                    <span id="computed_preview" class="fs-5 fw-bold text-success">₹ 0.00</span>
                                    <small>per selected employee</small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveIncentiveBtn"
                        onclick="submitIncentive()">Save Incentive</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- NESTED MODAL: ADD INCENTIVE TYPE -->
    <!-- ========================================== -->
    <div class="modal fade" id="addIncentiveTypeModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white p-2">
                    <h6 class="modal-title m-0">Add Type</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <label class="form-label">Incentive Name</label>
                    <input type="text" class="form-control form-control-sm" id="new_type_name"
                        placeholder="e.g. Booking">
                </div>
                <div class="modal-footer p-2">
                    <button type="button" class="btn btn-sm btn-primary w-100"
                        onclick="saveIncentiveType()">Save</button>
                </div>
            </div>
        </div>
    </div>
<!-- ========================================== -->
<!-- VIEW MODAL (HISTORY & RECEIPT STYLE) -->
<!-- ========================================== -->
<div class="modal fade" id="viewIncentiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Incentive Details & History</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <h5 class="text-primary mb-0" id="view_emp_name">-</h5>
                                <small class="text-muted" id="view_emp_id">-</small>
                            </div>
                            <div class="col-6 text-end">
                                <h4 class="text-success mb-0" id="view_calc_amt">₹ 0.00</h4>
                                <span class="badge" id="view_status">-</span>
                            </div>
                        </div>
                        <hr>
                        <div class="row small">
                            <div class="col-4"><strong>Type:</strong> <span id="view_type">-</span></div>
                            <div class="col-4"><strong>Date:</strong> <span id="view_date">-</span></div>
                            <div class="col-4"><strong>DV No:</strong> <span id="view_dv_no">-</span></div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-2">History (Up to this date)</h6>
                <div class="table-responsive bg-white rounded shadow-sm border">
                    <table class="table table-sm table-striped mb-0 text-center" style="font-size: 12px;">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>DV No</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Total Left</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <!-- ONLY Datatables scripts here. Select2 and SweetAlert are already in app.blade.php -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script>
        let timeScope = window.location.pathname.includes('directory') ? 'all_time' : 'current_date';
        let dataTable;
        let mobileStart = 0;
        let mobileLength = 20;

        $(document).ready(function() {
            initDataTable();

            if (window.innerWidth < 768) {
                loadMobileCards();
            }

            $('#loadMoreBtn').on('click', function() {
                mobileStart += mobileLength;
                loadMobileCards(true);
            });

            // Initialize Select2 right away
            initCascadingSelect2();
        });

        // ==========================================
        // BULK SELECTION & FLOATING BUTTONS LOGIC
        // ==========================================
        let selectedIds = [];

        $(document).on('change', '#selectAllCheckbox', function() {
            let isChecked = $(this).prop('checked');
            $('.row-checkbox').prop('checked', isChecked);
            updateFloatingBar();
        });

        $(document).on('change', '.row-checkbox', function() {
            if (!$(this).prop('checked')) {
                $('#selectAllCheckbox').prop('checked', false);
            }
            if ($('.row-checkbox:checked').length === $('.row-checkbox').length) {
                $('#selectAllCheckbox').prop('checked', true);
            }
            updateFloatingBar();
        });

        $('#floatSelectAllBtn').on('click', function() {
            $('.row-checkbox').prop('checked', true);
            $('#selectAllCheckbox').prop('checked', true);
            updateFloatingBar();
        });

        function updateFloatingBar() {
            selectedIds = [];
            $('.row-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length > 0) {
                $('#selectedCount').text(`${selectedIds.length} Selected`);
                $('#floatingActionBar').addClass('show');
            } else {
                $('#floatingActionBar').removeClass('show');
            }
        }

        $('#floatDeleteBtn').on('click', function() {
            if (selectedIds.length === 0) return;

            Swal.fire({
                title: 'Permanent Delete!',
                text: 'This action cannot be undone. Are you sure you want to permanently delete the selected records?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, permanently delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/api/v1/incentives/bulk-delete',
                        type: 'POST',
                        data: {
                            ids: selectedIds,
                            time_scope: timeScope
                        },
                        success: function(res) {
                            Swal.fire('Deleted!', res.message, 'success');
                            selectedIds = [];
                            $('#selectAllCheckbox').prop('checked', false);
                            $('#floatingActionBar').removeClass('show');
                            if (window.innerWidth < 768) {
                                mobileStart = 0;
                                loadMobileCards();
                            } else {
                                dataTable.ajax.reload(null, false);
                            }
                        },
                        error: function(err) {
                            Swal.fire('Error!', err.responseJSON?.message ||
                                'Failed to delete records.', 'error');
                        }
                    });
                }
            });
        });

       // ==========================================
    // 3. DYNAMIC TOP BUTTONS (Permissions Based)
    // ==========================================
    function renderTopButtons(perms) {
        let btnHtml = '';
        if(perms.can_add_direct) {
            btnHtml += `<button class="btn btn-success me-2" onclick="openAddModal('active')"><i class="fa fa-plus"></i> Add Incentive</button>`;
        } else if (perms.can_add_request) {
            btnHtml += `<button class="btn btn-warning me-2" onclick="openAddModal('pending')"><i class="fa fa-paper-plane"></i> Request Incentive</button>`;
        }
        
        if(perms.can_export) {
            btnHtml += `<button class="btn btn-info me-2 text-white" onclick="exportExcel()"><i class="fa fa-file-excel"></i> Export</button>`;
        }
        
        if(perms.can_print) {
            let portal = "{{ request()->segment(1) }}"; 
            let currentToken = localStorage.getItem('token') || localStorage.getItem('admin_token') || localStorage.getItem('emp_token') || '';
            // 🔥 FIX: Print URL me token bhej rahe hain taaki backend auth kar sake
            let printUrl = `/${portal}/incentives/print?time_scope=${timeScope}&token=${currentToken}`;
            btnHtml += `<button class="btn btn-secondary" onclick="window.open('${printUrl}', '_blank')"><i class="fa fa-print"></i> Print</button>`;      
        }
        
        $('#topActionButtons').html(btnHtml);
    }

    // ==========================================
    // 20-20 CHUNKING EXCEL EXPORT LOGIC
    // ==========================================
    async function exportExcel() {
        let exportBtn = $('button:contains("Export")');
        let originalText = exportBtn.html();
        
        exportBtn.html('<i class="fa fa-spinner fa-spin"></i> Fetching...').prop('disabled', true);
        
        let allExportData = [];
        let start = 0;
        let length = 20; // Chunk size
        let totalRecords = 1; 
        let serialNo = 1; // 🔥 FIX: S.No counter add kiya gaya hai

        try {
            while (start < totalRecords) {
                let response = await $.ajax({
                    url: "{{ url('api/v1/incentives') }}",
                    type: "GET",
                    data: {
                        time_scope: timeScope,
                        start: start,
                        length: length,
                        'search[value]': dataTable ? dataTable.search() : '' 
                    }
                });

                totalRecords = response.recordsFiltered;
                
                response.data.forEach(row => {
                    allExportData.push({
                        'S.No': serialNo++, // 🔥 FIX: Serial Number push ho raha hai
                        'Date': new Date(row.created_at).toLocaleDateString('en-IN'),
                        'Employee Name': row.employee?.full_name || '-',
                        'Member ID': row.employee?.member_id || '-',
                        'Company': row.company?.company_name || '-',
                        'Type': row.type?.name || 'Other',
                        'Base Net Amount': row.net_amount,
                        'Calculated Incentive': row.calculated_amount,
                        'Status': row.incentive_status.toUpperCase()
                    });
                });
                start += length;
            }
            generateCSV(allExportData);
        } catch (error) {
            console.error("Export Error: ", error);
            alert("Failed to export data.");
        } finally {
            exportBtn.html(originalText).prop('disabled', false);
        }
    }
        function generateCSV(dataArray) {
            if (dataArray.length === 0) return alert("No data to export!");

            const headers = Object.keys(dataArray[0]);
            let csvContent = headers.join(",") + "\n";

            dataArray.forEach(row => {
                let rowData = headers.map(header => {
                    let cellData = row[header] === null || row[header] === undefined ? "" : row[header];
                    return `"${String(cellData).replace(/"/g, '""')}"`;
                });
                csvContent += rowData.join(",") + "\n";
            });

            const blob = new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            let fileName = (timeScope === 'all_time' ? 'All_Time_' : 'Daily_') + 'Incentives.csv';
            link.setAttribute("href", url);
            link.setAttribute("download", fileName);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        $(document).on('click', '.temp-delete-btn', function() {
            let id = $(this).data('id');
            if (confirm("Move this record to trash?")) {
                $.ajax({
                    url: `/api/v1/incentives/${id}`,
                    type: 'DELETE',
                    success: function(res) {
                        if (window.innerWidth < 768) {
                            mobileStart = 0;
                            loadMobileCards();
                        } else {
                            dataTable.ajax.reload(null, false);
                        }
                    }
                });
            }
        });

        // ==========================================
        // DATATABLE INIT
        // ==========================================
        function initDataTable() {
            dataTable = $('#incentiveTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ url('api/v1/incentives') }}",
                    type: "GET",
                    data: function(d) {
                        d.time_scope = timeScope;
                    },
                    dataSrc: function(json) {
                        renderTopButtons(json.permissions);
                        if (json.permissions.can_delete) {
                            $('.checkbox-col').removeClass('d-none');
                        } else {
                            $('.checkbox-col').addClass('d-none');
                        }
                        return json.data;
                    }
                },
                columns: [{
                        data: 'id',
                        orderable: false,
                        className: 'checkbox-col',
                        render: function(data, type, row, meta) {
                            return `<input type="checkbox" class="row-checkbox" value="${data}">`;
                        }
                    },
                    {
                        data: 'employee.full_name',
                        name: 'employee.full_name'
                    },
                    {
                        data: 'employee.member_id',
                        name: 'employee.member_id'
                    },
                    {
                        data: 'company.company_code',
                        name: 'company.company_code',
                        defaultContent: '-'
                    },
                    {
                        data: 'type.name',
                        name: 'type.name',
                        defaultContent: 'Other'
                    },
                    {
                        data: 'net_amount',
                        name: 'net_amount'
                    },
                    {
                        data: 'value',
                        render: function(data, type, row) {
                            return row.calc_type === 'percentage' ? data + '%' : '₹' + data;
                        }
                    },
                    {
                        data: 'calculated_amount',
                        name: 'calculated_amount',
                        render: data => '₹' + data
                    },
                    {
                        data: 'incentive_status',
                        render: function(data) {
                            let badge = data === 'active' ? 'bg-success' : (data === 'pending' ?
                                'bg-warning' : 'bg-danger');
                            return `<span class="badge ${badge}">${data.toUpperCase()}</span>`;
                        }
                    },
                    {
                        data: 'id',
                        orderable: false,
                        render: function(data, type, row) {
                            return `<button class="btn btn-sm btn-outline-info view-btn me-1" data-id="${data || row.id}" title="View History"><i class="fa fa-eye"></i></button>
    <button class="btn btn-sm btn-outline-secondary receipt-btn me-1" data-id="${data || row.id}" title="Print Receipt"><i class="fa fa-print"></i></button>
    <button class="btn btn-sm btn-outline-danger temp-delete-btn" data-id="${data || row.id}" title="Temporary Delete"><i class="fa fa-trash"></i></button>`;
                        }
                    }
                ],
                createdRow: function(row, data, dataIndex) {
                    if ($('.checkbox-col').hasClass('d-none')) {
                        $('td:eq(0)', row).addClass('d-none');
                    }
                }
            });
        }

        // ==========================================
        // MOBILE CARDS
        // ==========================================
        function loadMobileCards(append = false) {
            let btn = $('#loadMoreBtn');
            btn.html('<i class="fa fa-spinner fa-spin"></i> Loading...');

            $.ajax({
                url: "{{ url('api/v1/incentives') }}",
                type: "GET",
                data: {
                    time_scope: timeScope,
                    start: mobileStart,
                    length: mobileLength
                },
                success: function(response) {
                    renderTopButtons(response.permissions);

                    let cardsHtml = '';
                    response.data.forEach(row => {
                        let valStr = row.calc_type === 'percentage' ? row.value + '%' : '₹' + row.value;
                        let badge = row.incentive_status === 'active' ? 'bg-success' : (row
                            .incentive_status === 'pending' ? 'bg-warning' : 'bg-danger');
                        let checkboxHtml = response.permissions.can_delete ?
                            `<input type="checkbox" class="row-checkbox form-check-input float-end" value="${row.id}">` :
                            '';

                        cardsHtml += `
                        <div class="col-12 mb-3">
                            <div class="card shadow-sm border-0">
                                <div class="card-body">
                                    ${checkboxHtml}
                                    <h6 class="mb-1 text-primary">${row.employee?.full_name ?? 'Unknown'} (${row.employee?.member_id ?? '-'})</h6>
                                    <p class="mb-1 small text-muted"><i class="fa fa-building"></i> ${row.company?.company_name ?? 'HO'}</p>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-2 p-2 bg-light rounded">
                                        <div class="small"><strong>Type:</strong> ${row.type?.name ?? '-'}</div>
                                        <div class="small"><strong>Val:</strong> ${valStr}</div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div class="text-success fw-bold">₹${row.calculated_amount}</div>
                                        <span class="badge ${badge}">${row.incentive_status.toUpperCase()}</span>
                                    </div>
                                    
                                    <div class="mt-2 text-end border-top pt-2">
                                        // <button class="btn btn-sm btn-outline-danger temp-delete-btn" data-id="${row.id}"><i class="fa fa-trash"></i></button>
                                        <button class="btn btn-sm btn-outline-info view-btn me-1" data-id="${data || row.id}" title="View History"><i class="fa fa-eye"></i></button>
    <button class="btn btn-sm btn-outline-secondary receipt-btn me-1" data-id="${data || row.id}" title="Print Receipt"><i class="fa fa-print"></i></button>
    <button class="btn btn-sm btn-outline-danger temp-delete-btn" data-id="${data || row.id}" title="Temporary Delete"><i class="fa fa-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    });

                    if (append) {
                        $('#mobileCardsContainer').append(cardsHtml);
                    } else {
                        $('#mobileCardsContainer').html(cardsHtml);
                    }

                    if (response.recordsFiltered > (mobileStart + mobileLength)) {
                        btn.removeClass('d-none').html('Load More <i class="fa-solid fa-chevron-down"></i>');
                    } else {
                        btn.addClass('d-none');
                    }
                },
                error: function(err) {
                    console.error("Error fetching mobile cards", err);
                    btn.html('Load More <i class="fa-solid fa-chevron-down"></i>');
                }
            });
        }

      // ==========================================
    // 3. DYNAMIC TOP BUTTONS (Permissions Based)
    // ==========================================
    function renderTopButtons(perms) {
        let btnHtml = '';
        if(perms.can_add_direct) {
            btnHtml += `<button class="btn btn-success me-2" onclick="openAddModal('active')"><i class="fa fa-plus"></i> Add Incentive</button>`;
        } else if (perms.can_add_request) {
            btnHtml += `<button class="btn btn-warning me-2" onclick="openAddModal('pending')"><i class="fa fa-paper-plane"></i> Request Incentive</button>`;
        }
        
        if(perms.can_export) {
            btnHtml += `<button class="btn btn-info me-2 text-white" onclick="exportExcel()"><i class="fa fa-file-excel"></i> Export</button>`;
        }
        
        if(perms.can_print) {
            let portal = "{{ request()->segment(1) }}"; 
            let currentToken = localStorage.getItem('token') || localStorage.getItem('admin_token') || localStorage.getItem('emp_token') || '';
            // 🔥 FIX: Print URL me token bhej rahe hain taaki backend auth kar sake
            let printUrl = `/${portal}/incentives/print?time_scope=${timeScope}&token=${currentToken}`;
            btnHtml += `<button class="btn btn-secondary" onclick="window.open('${printUrl}', '_blank')"><i class="fa fa-print"></i> Print</button>`;      
        }
        
        $('#topActionButtons').html(btnHtml);
    }

        function openAddModal(statusContext) {
            $('#incentiveForm')[0].reset();
            $('.select2-ajax').val(null).trigger('change');
            $('#incentive_status_payload').val(statusContext);
            $('#modalTitle').text(statusContext === 'active' ? 'Add Active Incentive' : 'Request Incentive');

            loadIncentiveTypes();
            calculateIncentive();
            $('#addIncentiveModal').modal('show');
        }

        function openNestedModal() {
            $('#new_type_name').val('');
            $('#addIncentiveTypeModal').modal('show');
        }

        $('#incentive_type_id').on('change', function() {
            let text = $(this).find('option:selected').text().toLowerCase();
            if (text.includes('booking')) {
                $('#passbookContainer').removeClass('d-none');
            } else {
                $('#passbookContainer').addClass('d-none');
                $('#passbook_no').val('');
            }
        });


        // 3. ACTION CLICK EVENTS (Script ke aakhir me daalein)
$(document).on('click', '.view-btn', function() {
    let id = $(this).data('id');
    
    // Fetch data and history
    $.get(`/api/v1/incentives/${id}`, function(res) {
        let d = res.data;
        $('#view_emp_name').text(d.employee?.full_name || 'Unknown');
        $('#view_emp_id').text(d.employee?.member_id || d.emp_id);
        $('#view_calc_amt').text('₹ ' + d.calculated_amount);
        $('#view_type').text(d.type?.name || 'Other');
        $('#view_date').text(new Date(d.created_at).toLocaleDateString());
        $('#view_dv_no').text(d.dv_no || 'N/A');
        
        let badge = d.incentive_status === 'active' ? 'bg-success' : (d.incentive_status === 'pending' ? 'bg-warning' : 'bg-danger');
        $('#view_status').attr('class', 'badge ' + badge).text(d.incentive_status.toUpperCase());

        let rows = '';
        res.history.forEach(h => {
            let hBadge = h.incentive_status === 'active' ? 'text-success' : (h.incentive_status === 'pending' ? 'text-warning' : 'text-danger');
            rows += `<tr>
                <td>${new Date(h.created_at).toLocaleDateString()}</td>
                <td>${h.type?.name || '-'}</td>
                <td>${h.dv_no || '-'}</td>
                <td>₹${h.calculated_amount}</td>
                <td class="text-success">₹${h.paid}</td>
                <td class="text-danger">₹${h.total_left}</td>
                <td class="fw-bold ${hBadge}">${h.incentive_status.toUpperCase()}</td>
            </tr>`;
        });
        $('#historyTableBody').html(rows || '<tr><td colspan="7">No history found</td></tr>');
        
        $('#viewIncentiveModal').modal('show');
    });
});

$(document).on('click', '.receipt-btn', function() {
    let id = $(this).data('id');
    let portal = "{{ request()->segment(1) }}"; 
    let token = localStorage.getItem('token') || localStorage.getItem('admin_token') || localStorage.getItem('emp_token') || '';
    window.open(`/${portal}/incentives/receipt/${id}?token=${token}`, '_blank');
});

      // ==========================================
    // 3-LETTER CASCADING AJAX SEARCH (Select2)
    // ==========================================
    function initCascadingSelect2() {

        $('#company_id').select2({
            dropdownParent: $('#addIncentiveModal'),
            width: '100%', // 🔥 FIX: Forces Select2 to take full width of container
            minimumInputLength: 3,
            ajax: {
                url: '/api/v1/incentives/search-companies',
                data: params => ({ q: params.term }),
                processResults: data => ({ results: data.data.map(item => ({ id: item.id, text: item.company_name })) })
            }
        }).on('change', function() { $('#branch_id, #department_id, #designation_id, #emp_ids').val(null).trigger('change'); });

        $('#branch_id').select2({
            dropdownParent: $('#addIncentiveModal'),
            width: '100%', // 🔥 FIX
            minimumInputLength: 3,
            ajax: {
                url: '/api/v1/incentives/search-branches',
                data: params => ({ q: params.term, company_ids: $('#company_id').val() }),
                processResults: data => ({ results: data.data.map(item => ({ id: item.id, text: item.branch_name })) })
            }
        }).on('change', function() { $('#department_id, #designation_id, #emp_ids').val(null).trigger('change'); });

        $('#department_id').select2({
            dropdownParent: $('#addIncentiveModal'),
            width: '100%', // 🔥 FIX
            minimumInputLength: 3,
            ajax: {
                url: '/api/v1/incentives/search-departments',
                data: params => ({ q: params.term, company_ids: $('#company_id').val(), branch_ids: $('#branch_id').val() }),
                processResults: data => ({ results: data.data.map(item => ({ id: item.id, text: item.department_name })) })
            }
        }).on('change', function() { $('#designation_id, #emp_ids').val(null).trigger('change'); });

        $('#designation_id').select2({
            dropdownParent: $('#addIncentiveModal'),
            width: '100%', // 🔥 FIX
            minimumInputLength: 3,
            ajax: {
                url: '/api/v1/incentives/search-designations',
                data: params => ({ q: params.term, department_ids: $('#department_id').val() }),
                processResults: data => ({ results: data.data.map(item => ({ id: item.id, text: item.designation_name })) })
            }
        }).on('change', function() { $('#emp_ids').val(null).trigger('change'); });

        $('#emp_ids').select2({
    dropdownParent: $('#addIncentiveModal'),
    width: '100%',
    minimumInputLength: 3,
    ajax: {
        url: '/api/v1/incentives/search-employees',
        data: params => ({ q: params.term, designation_ids: $('#designation_id').val(), branch_ids: $('#branch_id').val() }),
        // 🔥 FIX: id property me ab item.member_id jayega
        processResults: data => ({ results: data.data.map(item => ({ id: item.member_id, text: `${item.full_name} (${item.member_id})` })) })
    }
}).on('change', calculateIncentive);
    }
        $('.calc-trigger').on('input change', calculateIncentive);

        function calculateIncentive() {
            let netAmt = parseFloat($('#net_amount').val()) || 0;
            let val = parseFloat($('#value').val()) || 0;
            let calcType = $('#calc_type').val();
            let distType = $('#dist_type').val();
            let selectedEmps = $('#emp_ids').val();
            let empCount = selectedEmps ? selectedEmps.length : 0;

            let baseComputed = (calcType === 'percentage') ? (netAmt * val / 100) : val;
            let finalAmt = (distType === 'all' && empCount > 0) ? (baseComputed / empCount) : baseComputed;

            $('#computed_preview').text('₹ ' + finalAmt.toFixed(2));
        }

        function loadIncentiveTypes() {
            $.get('/api/v1/incentive-types/active', function(res) {
                let options = '<option value="">Select Type</option>';
                res.data.forEach(t => options += `<option value="${t.id}">${t.name}</option>`);
                $('#incentive_type_id').html(options);
            });
        }

        function saveIncentiveType() {
            let name = $('#new_type_name').val().trim();
            if (!name) return alert('Name required!');

            $.ajax({
                url: '/api/v1/incentive-types/store',
                type: 'POST',
                data: {
                    name: name
                },
                success: function(res) {
                    $('#addIncentiveTypeModal').modal('hide');
                    loadIncentiveTypes();
                    setTimeout(() => $('#incentive_type_id').val(res.data.id).trigger('change'), 500);
                }
            });
        }

        function submitIncentive() {
            if (!$('#emp_ids').val()) return alert("Please select at least one employee.");
            if (!$('#incentive_type_id').val()) return alert("Please select Incentive Type.");

            let btn = $('#saveIncentiveBtn');
            btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: '/api/v1/incentives',
                type: 'POST',
                data: $('#incentiveForm').serialize() + '&time_scope=' + timeScope,
                success: function(res) {
                    $('#addIncentiveModal').modal('hide');
                    btn.prop('disabled', false).text('Save Incentive');

                    if (window.innerWidth < 768) {
                        mobileStart = 0;
                        loadMobileCards();
                    } else {
                        dataTable.ajax.reload(null, false);
                    }

                    Swal.fire('Success', res.message, 'success');
                },
                error: function(err) {
                    btn.prop('disabled', false).text('Save Incentive');
                    Swal.fire('Error!', err.responseJSON?.message || 'Something went wrong.', 'error');
                }
            });
        }
    </script>
@endpush
