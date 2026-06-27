@extends('layout.app')

@section('content')



    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        .table-custom th {
            background-color: var(--sidebar-bg);
            color: #fff;
            font-size: 13px;
            border: none;
            padding: 12px 15px;
        }

        .table-custom td {
            font-size: 13px;
            vertical-align: middle;
            padding: 12px 15px;
        }

        .mobile-item {
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px var(--shadow-color);
        }

        .status-badge-allow {
            background: #dcfce7;
            color: #166534;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .status-badge-deny {
            background: #fee2e2;
            color: #991b1b;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .unbind-icon-btn {
            transition: transform 0.2s ease, color 0.2s ease;
        }

        .unbind-icon-btn:hover {
            transform: scale(1.2);
            color: #842029 !important;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #printArea,
            #printArea * {
                visibility: visible;
            }

            #printArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>

    <div class="container-fluid p-0">
     
      <!-- Header Section -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);"><i class="fas fa-key me-2 text-primary"></i> Workspace Access</h4>
    </div>
    
    <div class="d-flex gap-2 align-self-stretch align-self-md-auto">
       <button type="button" class="btn btn-danger px-3 py-2 shadow-sm d-none" id="btnBulkDelete">
            <i class="fas fa-trash-alt me-1"></i> Delete Selected
        </button>

        <button type="button" class="btn text-white px-3 py-2 shadow-sm flex-grow-1 flex-md-grow-0 text-nowrap d-none" style="background-color: #f59e0b;" id="btnOpenBulkShiftModal">
            <i class="fas fa-users-cog me-1"></i> Bulk Shift
        </button>

        <button type="button" class="btn text-white px-3 py-2 shadow-sm flex-grow-1 flex-md-grow-0 text-nowrap d-none" style="background-color: var(--brand-primary);" id="btnOpenGenerateModal">
            <i class="fas fa-plus me-1"></i> Generate ID
        </button>

        <button type="button" class="btn btn-info text-white px-3 py-2 shadow-sm flex-grow-1 flex-md-grow-0 text-nowrap d-none" id="btnOpenRequestModal">
            <i class="fas fa-paper-plane me-1"></i> Generate Request
        </button>
    </div>
</div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <span class="fw-bold text-secondary"><i class="fas fa-filter text-primary me-1"></i> Global Filter:</span>
                <div class="input-group" style="max-width: 400px;">
                    <input type="text" class="form-control fw-medium text-secondary" id="branch_filter_input"
                        list="filterBranchList" placeholder="Search Branch or Head Office..." autocomplete="off">
                    <input type="hidden" id="hidden_branch_filter_id">
                    <datalist id="filterBranchList"></datalist>
                </div>
            </div>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search matching logs...">
            <button type="button" class="btn text-white shadow-sm px-3" style="background-color: #10b981;"
                id="mobileExcelBtn"><i class="fas fa-file-excel"></i></button>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="accessTable" class="table table-hover table-custom w-100">
    <thead>
        <tr>
            <th style="width: 40px;" class="text-center"><input type="checkbox" id="selectAllCheckbox" class="form-check-input shadow-sm"></th>
            <th>User Profile</th>
            <th>Panel Scope</th>
            <th>Actions</th>
            <th>Shift Timings</th>
            <th>Hardware Binding</th>
            <th>Intrusion Alerts</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>
                </div>
            </div>
        </div>

        <div id="mobileCardsContainer" class="d-block d-md-none"></div>
        <div id="mobilePaginationWrapper" class="d-flex d-md-none justify-content-between align-items-center mt-2 px-1">
        </div>
    </div>

    <div class="modal fade" id="generateAccessModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i
                            class="fas fa-user-plus me-2 text-primary"></i> Generate New Access</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="generateAccessForm">
                        <div class="row g-2 mb-3">
                            <div class="col-12">
                                <label class="form-label text-secondary small fw-bold">Select Company <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="g_company_input" class="form-control" list="g_companyList"
                                    placeholder="Search Company" autocomplete="off" required>
                                <input type="hidden" id="g_company_id">
                                <datalist id="g_companyList"></datalist>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-secondary small fw-bold">Select Branch <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="g_branch_input" class="form-control" list="g_branchList"
                                    placeholder="First select company" autocomplete="off" disabled>
                                <input type="hidden" id="g_branch_id">
                                <datalist id="g_branchList"></datalist>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-secondary small fw-bold">Select Department <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="g_dept_input" class="form-control" list="g_deptList"
                                    placeholder="First select branch" autocomplete="off" required disabled>
                                <input type="hidden" id="g_dept_id">
                                <datalist id="g_deptList"></datalist>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-secondary small fw-bold">Select Employee <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="g_emp_search" list="g_empList"
                                    placeholder="First select department" required autocomplete="off" disabled>
                                <input type="hidden" name="user_id" id="g_user_id" required>
                                <datalist id="g_empList"></datalist>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-secondary small fw-bold">Assign Work Panel <span
                                        class="text-danger">*</span></label>
                                <select name="panel_assign" class="form-select" id="g_panel_assign" required>
                                    <option value="Employee">Employee Panel</option>
                                    <option value="Branch Manager">Branch Manager Panel</option>
                                    <option value="Associate">Associate Panel</option>
                                </select>
                            </div>
                            <div class="col-6 mt-3">
                                <label class="form-label text-secondary small fw-bold">Shift Start Time <span
                                        class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="g_p_time_from" value="09:00" required>
                            </div>
                            <div class="col-6 mt-3">
                                <label class="form-label text-secondary small fw-bold">Shift End Time <span
                                        class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="g_p_time_to" value="18:00" required>
                            </div>
                        </div>
                        <button type="submit" class="btn text-white w-100 fw-bold shadow-sm mt-3"
                            style="background-color: var(--brand-primary);" id="generateBtn">
                            <i class="fas fa-cogs me-1"></i> Generate ID & Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editShiftModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title fw-bold text-dark"><i class="fas fa-clock text-warning me-2"></i> Adjust Shift
                        Timing</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <form id="editShiftForm">
                        <input type="hidden" id="edit_shift_panel_id">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">New Start Time</label>
                                <input type="time" class="form-control" id="edit_time_from" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">New End Time</label>
                                <input type="time" class="form-control" id="edit_time_to" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 fw-bold mt-4" id="btnUpdateShift">Update
                            Shift Limits</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="credentialsModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white border-bottom-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-check-circle me-2"></i> Allocation Successful!</h5>
                    <button type="button" class="btn-close btn-close-white shadow-none"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="text-muted small">Share these randomized parameters with the employee.</p>
                    <div class="bg-light p-3 rounded border">
                        <h5 class="mb-2">Login ID: <strong class="text-primary" id="show_panel_id"></strong></h5>
                        <h5 class="mb-0">Password: <strong class="text-danger" id="show_password"></strong></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="emergencyAccessModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark border-bottom-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-unlock-alt me-2"></i> Allocate Shift Override</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <form id="emergencyAccessForm">
                        <input type="hidden" id="e_panel_id">
                        <input type="hidden" id="e_device_token">
                        <div class="row g-3">
                            <div class="col-6"><label class="form-label small fw-bold">Date From</label><input
                                    type="date" class="form-control" id="e_date_from" required></div>
                            <div class="col-6"><label class="form-label small fw-bold">Date To</label><input
                                    type="date" class="form-control" id="e_date_to" required></div>
                            <div class="col-6"><label class="form-label small">Time From</label><input type="time"
                                    class="form-control" id="e_time_from" required></div>
                            <div class="col-6"><label class="form-label small">Time To</label><input type="time"
                                    class="form-control" id="e_time_to" required></div>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 fw-bold mt-4" id="btnEmergencySave">Authorize
                            Time Parameters</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="sessionLogsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white border-bottom-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-clock me-2"></i> Employee Session History</h5>
                    <button type="button" class="btn-close btn-close-white shadow-none"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Date Filter</label>
                        <input type="date" class="form-control fw-bold border-info" id="sessionLogDate">
                        <input type="hidden" id="sessionLogUserId">
                    </div>
                    <div id="sessionLogsContainer" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deviceRequestsModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white border-bottom-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-shield-alt text-danger me-2"></i> Security Gateway
                        Logs</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light text-dark fw-bold" onclick="window.print()"><i
                                class="fas fa-print me-1"></i> Print</button>
                        <button type="button" class="btn-close btn-close-white shadow-none"
                            data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body p-3 bg-light" id="printArea">
                    <div class="d-flex align-items-center mb-3 p-2 bg-white rounded shadow-sm border no-print gap-2">
                        <i class="fas fa-filter text-primary ms-1"></i>
                        <span class="fw-bold small text-secondary">Filter by Date:</span>
                        <input type="date" id="gatewayLogDate"
                            class="form-control form-control-sm fw-bold border-primary" style="max-width: 160px;">
                        <button class="btn btn-sm btn-outline-secondary" id="clearGatewayLogDate">Clear</button>
                    </div>
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-bordered bg-white shadow-sm rounded vertical-middle mb-0">
                            <thead class="table-dark small">
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Digital Signature (Token)</th>
                                    <th>Telemetry Coordinates</th>
                                    <th class="text-end no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="requestsTableBody"></tbody>
                        </table>
                    </div>
                    <div id="requestsMobileContainer" class="d-block d-md-none no-print"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="smartUnbindModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow animate-fed">
                <div class="modal-header bg-dark text-white border-bottom-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-network-wired text-danger me-2"></i> Binding Router
                        Wizard</h5>
                    <button type="button" class="btn-close btn-close-white shadow-none"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <form id="smartUnbindForm">
                        <input type="hidden" id="su_panel_id">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Choose Routing Action Protocol <span
                                    class="text-danger">*</span></label>
                            <div class="form-check p-3 border rounded bg-white mb-2 shadow-xs cursor-pointer">
                                <input class="form-check-input ms-0 me-2" type="radio" name="action_type"
                                    id="act_clear" value="clear_fresh" checked>
                                <label class="form-check-label fw-bold text-dark" for="act_clear">Completely Clear Primary
                                    Binding (Open for Fresh Setup)</label>
                                <small class="text-muted d-block mt-1">Machine identity configuration values will be
                                    dropped. System registers same ID with a fresh password generation routine.</small>
                            </div>
                            <div class="form-check p-3 border rounded bg-white shadow-xs cursor-pointer">
                                <input class="form-check-input ms-0 me-2" type="radio" name="action_type"
                                    id="act_replace" value="replace_existing">
                                <label class="form-check-label fw-bold text-dark" for="act_replace">Promote Alternative
                                    Logged Entry As Primary</label>
                                <small class="text-muted d-block mt-1">Promote an authorized unrecognized node footprint
                                    history record directly as your active system node signature.</small>
                            </div>
                        </div>
                        <div class="mb-4 d-none" id="replacement_device_wrapper">
                            <label class="form-label small fw-bold text-secondary">Select Available Machine Node Signature
                                Logs</label>
                            <select id="su_target_device_token" class="form-select border-primary fw-medium"></select>
                        </div>
                        <button type="submit" class="btn btn-danger w-100 fw-bold shadow-sm"
                            id="btnExecuteSmartUnbind">Execute Network Authorization Rewrite</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- 7. Bulk Company Shift Update Modal -->
    <div class="modal fade" id="bulkShiftModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark border-bottom-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-users-cog me-2"></i> Company Bulk Shift Update</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <form id="bulkShiftForm">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Select Company <span class="text-danger">*</span></label>
                            <input type="text" id="b_company_input" class="form-control fw-bold border-warning" list="b_companyList" placeholder="Search Company" autocomplete="off" required>
                            <input type="hidden" id="b_company_id" required>
                            <datalist id="b_companyList"></datalist>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Global Start Time</label>
                                <input type="time" class="form-control border-warning" id="b_time_from" value="09:00" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Global End Time</label>
                                <input type="time" class="form-control border-warning" id="b_time_to" value="18:00" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 fw-bold mt-4 shadow-sm" id="btnUpdateBulkShift">
                            <i class="fas fa-check-double me-1"></i> Apply to All Employees
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script>
// 1. Token identify karein (Admin login hai ya Employee)
    let adminToken = localStorage.getItem('admin_token');
    let empToken = localStorage.getItem('emp_token');
    let activeToken = adminToken || empToken;
    let apiUrl = adminToken ? '/api/v1/admin/auth/me' : '/api/v1/employee/auth/me';

    // Mobile Excel Button Permission Check
            if (!CAN_EXPORT) {
                $('#mobileExcelBtn').addClass('d-none');
            }

    // Global variables jo poore page pe use honge
    var IS_ADMIN = false;
    var CAN_DELETE = false;
    var CAN_ADD_DIRECT = false;
    var CAN_ADD_REQUEST = false;
    var CAN_EXPORT = false;
    // 2. Token dekar Backend se current user ka data aur permissions mangwayein
    if (activeToken) {
        $.ajax({
            url: apiUrl,
            type: 'GET',
            async: false, // Taaki data aane tak Datatable wait kare
            headers: {
                'Authorization': 'Bearer ' + activeToken,
                'Accept': 'application/json'
            },
            success: function(res) {
                if(res.data) {
                    let userEmail = res.data.email ? res.data.email.toLowerCase().trim() : '';
                    let userRole = res.data.designation_name || '';
                    let perms = res.data.permissions || [];

                    // Admin Verify Check
                    IS_ADMIN = (userEmail === 'admin@jankivilla.com') || 
                               userRole === 'super_admin' || 
                               userRole === 'director' || 
                               userRole === 'Admin';

                    // 🔥 EXACT MATCH WITH DATABASE NAMES 🔥
                    CAN_DELETE = IS_ADMIN || perms.includes('device_access_delete');
                    CAN_ADD_DIRECT = IS_ADMIN || perms.includes('device_access_add_direct');
                    CAN_ADD_REQUEST = IS_ADMIN || perms.includes('device_access_add_request');
                    // AJAX ke success function ke andar jahan baaki permissions hain, wahan ye line add karein:
    CAN_EXPORT = IS_ADMIN || perms.includes('device_access_print') || perms.includes('device_access_export');
                }
            },
            error: function(err) {
                console.warn("Permissions fetch nahi ho payin, check your token status.", err);
            }
        });
    }

    // Debugging ke liye (Browser ke console tab me check kar sakte hain)
    console.log("Logged In Admin Status:", IS_ADMIN);
    console.log("Can Add Direct?", CAN_ADD_DIRECT);

        $(document).ready(function() {
            let gCompanyMap = {},
                gBranchMap = {},
                gDeptMap = {},
                gEmpMap = {};
            let branchFilterMap = {};
            let currentGatewayRowData = null;

            // --- NAYA CODE YAHAN ADD KAREIN ---
            if (CAN_ADD_DIRECT || CAN_ADD_REQUEST) {
                $('#btnOpenBulkShiftModal').removeClass('d-none');
            }
            if (CAN_ADD_DIRECT) {
                $('#btnOpenGenerateModal').removeClass('d-none');
            } else if (CAN_ADD_REQUEST) {
                $('#btnOpenRequestModal').removeClass('d-none');
            }

            // ==========================================
            // INITIAL DATA LOADING (INCLUDING HEAD OFFICE)
            // ==========================================
            $.ajax({
                url: '/api/v1/get-active-companies',
                type: 'GET',
                success: function(compRes) {
                    let options = '<option value="">-- View All Registered Branches --</option>';
                    branchFilterMap = {};

                    if (compRes.data) {
                        compRes.data.forEach(c => {
                            let str = `${c.company_name} (Head Office)`;
                            options += `<option value="${str}">`;
                            branchFilterMap[str] = 'HO_' + c
                            .id; // Head office special identifier
                        });
                    }

                    $.ajax({
                        url: '/api/v1/branches',
                        type: 'GET',
                        success: function(res) {
                            res.data.forEach(b => {
                                let cName = b.company ? b.company.company_name :
                                    'JankiVilla';
                                let str =
                                    `${cName} - ${b.branch_name} (${b.branch_id})`;
                                options += `<option value="${str}">`;
                                branchFilterMap[str] = b.id;
                            });
                            $('#filterBranchList').html(options);
                        }
                    });
                }
            });

            // ==========================================
            // GENERATE ID FORM LOGIC
            // ==========================================
            $('#btnOpenGenerateModal').click(function() {
                $('#generateAccessForm')[0].reset();
                $('#g_p_time_from').val('09:00');
                $('#g_p_time_to').val('18:00');
                $('#g_branch_input').prop('disabled', true).val('').attr('placeholder',
                    'First select company');
                $('#g_dept_input').prop('disabled', true).val('').attr('placeholder',
                'First select branch');
                $('#g_emp_search').prop('disabled', true).val('').attr('placeholder',
                    'First select department');
                $('#g_user_id, #g_dept_id, #g_branch_id, #g_company_id').val('');

                $.ajax({
                    url: '/api/v1/get-active-companies',
                    type: 'GET',
                    success: function(res) {
                        let opts = '';
                        gCompanyMap = {};
                        if (res.data) {
                            res.data.forEach(c => {
                                opts += `<option value="${c.company_name}">`;
                                gCompanyMap[c.company_name] = c.id;
                            });
                        }
                        $('#g_companyList').html(opts);
                    }
                });
                $('#generateAccessModal').modal('show');
            });

            $('#g_company_input').on('input change', function() {
                let val = $(this).val();
                if (gCompanyMap[val]) {
                    let compId = gCompanyMap[val];
                    $('#g_company_id').val(compId);
                    this.setCustomValidity('');

                    $('#g_branch_input').prop('disabled', true).val('Loading branches...');
                    $('#g_dept_input').prop('disabled', true).val('Loading departments...');
                    $('#g_emp_search').prop('disabled', true).val('');
                    $('#g_branch_id, #g_dept_id, #g_user_id').val('');

                    $.ajax({
                        url: '/api/v1/get-branches-by-companies',
                        type: 'POST',
                        data: {
                            company_ids: [compId]
                        },
                        success: function(res) {
                            let opts = '';
                            gBranchMap = {};
                            if (res.data && res.data.length > 0) {
                                res.data.forEach(b => {
                                    opts +=
                                        `<option value="${b.branch_name} (${b.branch_id})">`;
                                    gBranchMap[`${b.branch_name} (${b.branch_id})`] = b
                                        .id;
                                });
                                $('#g_branchList').html(opts);
                                $('#g_branch_input').prop('disabled', false).val('').attr(
                                    'placeholder', 'Search Branch (Leave blank for HO)');
                            } else {
                                $('#g_branch_input').val('Head Office (No Branches)').prop(
                                    'disabled', true);
                            }
                        }
                    });

                    $.ajax({
                        url: '/api/v1/get-departments-by-company?company_id=' + compId,
                        type: 'GET',
                        success: function(resDept) {
                            let optsDept = '';
                            gDeptMap = {};
                            resDept.data.forEach(d => {
                                let dName = d.department_name;
                                optsDept += `<option value="${dName}">`;
                                gDeptMap[dName] = d.id;
                            });
                            $('#g_deptList').html(optsDept);
                            $('#g_dept_input').prop('disabled', false).val('').attr(
                                'placeholder', 'Search Department');
                        }
                    });
                } else {
                    $('#g_company_id, #g_branch_id, #g_dept_id, #g_user_id').val('');
                    if (val !== '') this.setCustomValidity('Select valid company');
                }
            });

            $('#g_branch_input').on('input change', function() {
                let val = $(this).val();
                if (val === '') {
                    $('#g_branch_id').val('');
                    this.setCustomValidity('');
                    if ($('#g_dept_id').val()) $('#g_dept_input').trigger('change');
                } else if (gBranchMap[val]) {
                    $('#g_branch_id').val(gBranchMap[val]);
                    this.setCustomValidity('');
                    if ($('#g_dept_id').val()) $('#g_dept_input').trigger('change');
                } else {
                    $('#g_branch_id').val('');
                    this.setCustomValidity('Select valid branch or leave blank for HO');
                }
            });

            $('#g_dept_input').on('input change', function() {
                let val = $(this).val();
                if (gDeptMap[val]) {
                    $('#g_dept_id').val(gDeptMap[val]);
                    this.setCustomValidity('');
                    $('#g_emp_search').prop('disabled', true).val('Loading employees...');

                    let compId = $('#g_company_id').val();
                    let branchId = $('#g_branch_id').val() || '';
                    let deptName = val;

                    $.ajax({
                        url: `/api/v1/get-employees-list?company_id=${compId}&branch_id=${branchId}&department_name=${encodeURIComponent(deptName)}`,
                        type: 'GET',
                        success: function(res) {
                            let opts = '';
                            gEmpMap = {};
                            res.data.forEach(e => {
                                let disp = `${e.full_name} (${e.member_id})`;
                                opts += `<option value="${disp}">`;
                                gEmpMap[disp] = e.member_id;
                            });
                            $('#g_empList').html(opts);
                            $('#g_emp_search').prop('disabled', false).val('').attr(
                                'placeholder', 'Search Employee');
                        }
                    });
                } else {
                    $('#g_dept_id').val('');
                    if (val !== '') this.setCustomValidity('Select valid department');
                }
            });

            $('#g_emp_search').on('input change', function() {
                let val = $(this).val();
                if (gEmpMap[val]) {
                    $('#g_user_id').val(gEmpMap[val]);
                    this.setCustomValidity('');
                } else {
                    $('#g_user_id').val('');
                    if (val !== '') this.setCustomValidity('Select valid employee');
                }
            });

            $('#generateAccessForm').submit(function(e) {
                e.preventDefault();
                let btn = $('#generateBtn');
                btn.prop('disabled', true).text('Syncing...');

                $.ajax({
                    url: '/api/v1/generate-access',
                    type: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    },
                    data: {
                        user_id: $('#g_user_id').val(),
                        panel_assign: $('#g_panel_assign').val(),
                        p_time_from: $('#g_p_time_from').val(), // Dynamic timings passed
                        p_time_to: $('#g_p_time_to').val()
                    },
                    success: function(res) {
                        table.ajax.reload(null, false);
                        $('#generateAccessModal').modal('hide');
                        $('#show_panel_id').text(res.data.panel_id);
                        $('#show_password').text(res.data.panel_password);
                        $('#credentialsModal').modal('show');
                    },
                    error: function(err) {
                        alert(err.responseJSON.message || "Validation Error Occurred!");
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fas fa-cogs me-1"></i> Generate ID & Password');
                    }
                });
            });

            // ==========================================
            // DATATABLE INITIALIZATION & RENDERING
            // ==========================================
            $('#branch_filter_input').on('input change', function() {
                let val = $(this).val();
                $('#hidden_branch_filter_id').val(branchFilterMap[val] || '');
                table.ajax.reload();
            });

            let table = $('#accessTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/panel-access',
                    type: 'GET',
                    data: function(d) {
                        d.branch_id = $('#hidden_branch_filter_id').val();
                    }
                },
                dom: '<"row mb-3 d-none d-md-flex"<"col-md-6 d-flex align-items-center gap-3"lB><"col-md-6"f>>rt<"row mt-3 d-none d-md-flex"<"col-md-6"i><"col-md-6"p>>',
             buttons: [{
    extend: 'excelHtml5',
    text: '<i class="fas fa-file-excel me-1"></i> Export Data',
    className: 'btn btn-success btn-sm shadow-sm rounded-2 fw-bold',
    title: 'panel_device_excel',
    filename: 'panel_device_excel',
    exportOptions: {
        orthogonal: 'export',
        columns: [1, 2, 4, 5, 6, 7,8,9] 
    },
    // 🔥 100% WORKING SILENT EXPORT TRICK 🔥
    action: function (e, dt, button, config) {
        var self = this; // Button ka reference yahan save kiya (Scope fix)
        var oldStart = dt.settings()[0]._iDisplayStart; // Current page yaad rakha

        // Table ko intercept karke API se saara data mangwane ki command di
        dt.one('preXhr', function (e, s, data) {
            data.start = 0;
            data.length = -1; // -1 matlab ALL rows

            // Jab data API se aa jaye, par screen par dikhne se THEEK PEHLE...
            dt.one('preDraw', function (e, settings) {
                
                // Original Excel download function chalao (is baar 'self' pass kiya hai)
                $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config);

                // Wapas purani page settings (10 rows) chupke se restore karo
                dt.one('preXhr', function (e, s, data) {
                    settings._iDisplayStart = oldStart;
                    data.start = oldStart;
                });

                // Table ko original state mein refresh kar do (0 seconds delay ke sath)
                setTimeout(dt.ajax.reload, 0);

                // Saara data screen par render hone se block kar do (taaki UI na hile)
                return false; 
            });
        });

        // Backend se data nikalne ka process trigger karo
        dt.ajax.reload();
    }
}],
            initComplete: function() {
                // Agar export ki permission nahi hai, toh Datatables ka Export button hide kar do
                if (!CAN_EXPORT) {
                    $('.dt-buttons').addClass('d-none');
                }
            },
              columns: [
    {
        data: 'panel_id',
        orderable: false,
        searchable: false,
        visible: CAN_DELETE, // Agar permission nahi hai to ye column hide ho jayega
        className: 'text-center align-middle',
        render: (d) => `<input type="checkbox" class="row-checkbox form-check-input shadow-sm cursor-pointer" value="${d}">`
    },
    {
        data: 'full_name',
        render: (d, t, r) => `<div><span class="fw-bold text-dark d-block">${d}</span><small class="text-secondary">${r.user_id}</small></div>`
    },
    {
        data: 'panel_assign',
        render: (d, t, r) => `<span class="badge bg-secondary small">${d}</span><code class="d-block mt-1 bg-light px-1 py-0 border rounded text-center fw-bold">${r.panel_id}</code> <code class="d-block mt-1 bg-light px-1 py-0 border rounded text-center fw-bold">${r.panel_password}</code>`
    },
    {
        data: 'panel_id',
        orderable: false,
        className: 'text-end',
        render: (d, t, r) => {
            let sessionBtn = `<button type="button" class="btn btn-sm btn-outline-info session-history-btn shadow-sm" data-userid="${r.user_id}"><i class="fas fa-history"></i> Sessions</button>`;
            
            // Agar Admin nahi hai to sirf Session button dikhao
            if (!IS_ADMIN) return sessionBtn;

            return `
            <div class="d-flex justify-content-start gap-1">
                ${sessionBtn}
                <button type="button" class="btn btn-sm btn-light text-warning fw-bold emergency-btn shadow-sm secured-item" data-id="${d}"><i class="fas fa-unlock-alt"></i> Override</button>
                <button type="button" class="btn btn-sm btn-danger fw-bold hard-reset-btn shadow-sm secured-item" data-id="${d}"><i class="fas fa-sync-alt"></i> Reset</button>
            </div>`;
        }
    },
    {
        data: 'p_time_from',
        visible: IS_ADMIN, // Admin restriction
        render: (d, t, r) => `<div class="d-flex align-items-center gap-2">
                <span class="small fw-medium"><i class="far fa-clock text-warning me-1"></i> ${d.substring(0,5)} to ${r.p_time_to.substring(0,5)}</span>
                <button class="btn btn-xs btn-light border py-0 px-1 text-primary edit-shift-btn shadow-sm" data-id="${r.panel_id}" data-from="${d.substring(0,5)}" data-to="${r.p_time_to.substring(0,5)}" title="Edit Shift Time"><i class="fas fa-pencil-alt"></i></button>
            </div>`
    },
    {
        data: 'primary_device',
        visible: IS_ADMIN, // Admin restriction
        render: (d, t, r) => d ?
            `<div class="d-flex align-items-center gap-2">
                <span class="text-success small fw-bold"><i class="fas fa-desktop me-1"></i> Bound</span>
                <button class="btn p-0 border-0 bg-transparent text-danger dynamic-unbind-trigger unbind-icon-btn shadow-none" data-id="${r.panel_id}" data-current="${d}" title="Manage Primary Binding"><i class="fas fa-unlink fs-6"></i></button>
             </div>` : `<span class="text-warning small fw-bold"><i class="fas fa-exclamation-circle"></i> Unbound</span>`
    },
    {
        data: 'other_devices',
        visible: IS_ADMIN,
        render: (d, t, r) => {
            // 🔥 EXCEL EXPORT FORMATTING 🔥
            if (t === 'export') {
                if (!d || d.length === 0) return 'No Intrusions';
                let expText = '';
                d.forEach((a, index) => {
                    // Sahi Google Maps URL
                    let mapLink = (a.latitude && a.latitude !== 'Location Denied') 
                        ? `https://maps.google.com/?q=${a.latitude},${a.longitude}` 
                        : 'No GPS';
                    expText += `Attempt ${index + 1}: ${a.time} | Device: ${a.device_token} | Map: ${mapLink} \n`;
                });
                return expText.trim();
            }

            // 👇 Screen (UI) Render
            let attempts = d ? d.length : 0;
            let blocked = r.blocked_devices ? r.blocked_devices.length : 0;
            return `<div>
                <button type="button" class="btn btn-sm btn-outline-danger view-requests-btn py-0 px-2 small position-relative mb-1" data-id="${r.panel_id}">
                    <i class="fas fa-radar me-1"></i> Logs <span class="badge bg-danger ms-1">${attempts}</span>
                </button>
                <span class="d-block small text-muted"><i class="fas fa-ban text-secondary me-1"></i> Blocked: <b>${blocked}</b></span>
            </div>`;
        }
    },
    {
        data: 'p_status',
        render: d => d === 'allow' ?
            `<span class="status-badge-allow">Permitted</span>` :
            `<span class="status-badge-deny">Revoked</span>`
    },
    
    {
        data: 'panel_id',
        title: 'Login ID', // Excel me ye Header banega
        visible: false     // UI (Screen) par hide rahega
    },
    {
        data: 'panel_password',
        title: 'Password', // Excel me ye Header banega
        visible: false,    // UI (Screen) par hide rahega
        render: function(d) {
            // Agar backend se password nahi aa raha hoga to N/A dikhayega
            return d ? d : 'N/A'; 
        }
    }

],
                drawCallback: function(settings) {
                    renderMobileCards(settings.json.data);
                    renderMobilePagination(settings.json);
                    if (typeof window.applyPermissions === 'function') window.applyPermissions();
                }
            });

            // Mobile specific listeners
            $('#mobileSearch').on('keyup', function(e) {
                if (e.key === 'Enter') table.search($(this).val()).draw();
            });
            $('#mobileExcelBtn').click(() => {
                $('.buttons-excel').click();
            });

        function renderMobileCards(data) {
    let html = '';
    if (!data || data.length === 0) {
        html = '<div class="text-center p-4 text-muted border bg-white rounded-3">No matching profiles indexed.</div>';
    } else {
        data.forEach(r => {
            // Checkbox sirf tab dikhega jab delete ka permission ho
            let checkboxHtml = CAN_DELETE ? `<input type="checkbox" class="row-checkbox form-check-input me-3 mt-1 shadow-sm" style="transform: scale(1.2);" value="${r.panel_id}">` : '';

            let cardBody = `
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-start">
                        ${checkboxHtml}
                        <div><h6 class="fw-bold mb-0 text-dark">${r.full_name}</h6><small class="text-muted">${r.user_id} | Scope: <b>${r.panel_assign}</b></small></div>
                    </div>
                    <span class="${r.p_status === 'allow' ? 'status-badge-allow' : 'status-badge-deny'}">${r.p_status === 'allow' ? 'Active' : 'Revoked'}</span>
                </div>
            `;

            // Agar user Admin hai, to hi detail aur action buttons dikhao
            if (IS_ADMIN) {
                let hardwareStatus = r.primary_device ?
                    `<div class="d-flex align-items-center justify-content-end gap-2">
                        <span class="text-success fw-bold small"><i class="fas fa-check-circle"></i> Bound</span>
                        <button class="btn p-0 border-0 bg-transparent text-danger dynamic-unbind-trigger unbind-icon-btn shadow-none" data-id="${r.panel_id}" data-current="${r.primary_device}">
                            <i class="fas fa-unlink fs-6"></i>
                        </button>
                     </div>` : `<span class="text-warning fw-bold small"><i class="fas fa-exclamation-circle"></i> Unbound</span>`;

                let reqCount = r.other_devices ? r.other_devices.length : 0;
                let blockCount = r.blocked_devices ? r.blocked_devices.length : 0;

                cardBody += `
                    <div class="row g-2 border-top border-bottom py-2 my-2 small bg-light px-1 rounded align-items-center">
                        <div class="col-6">Login ID: <code class="fw-bold">${r.panel_id}</code></div>
                        <div class="col-6 text-end">${hardwareStatus}</div>
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <span><i class="far fa-clock text-warning me-1"></i> ${r.p_time_from.substring(0,5)} - ${r.p_time_to.substring(0,5)}</span>
                            <button class="btn btn-sm btn-light border text-primary py-0 px-2 edit-shift-btn shadow-sm" data-id="${r.panel_id}" data-from="${r.p_time_from.substring(0,5)}" data-to="${r.p_time_to.substring(0,5)}"><i class="fas fa-pencil-alt me-1"></i> Edit</button>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small text-muted"><i class="fas fa-fingerprint text-danger me-1"></i> Blocked: <b>${blockCount}</b></span>
                        <button type="button" class="btn btn-sm btn-danger view-requests-btn py-1" data-id="${r.panel_id}">
                            <i class="fas fa-shield-alt me-1"></i> Control Room (${reqCount})
                        </button>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-outline-info flex-fill fw-bold session-history-btn" data-userid="${r.user_id}"><i class="fas fa-history"></i> Sessions</button>
                        <button type="button" class="btn btn-sm btn-outline-warning flex-fill fw-bold emergency-btn" data-id="${r.panel_id}"><i class="fas fa-unlock-alt"></i> Override</button>
                        <button type="button" class="btn btn-sm btn-danger flex-fill fw-bold hard-reset-btn" data-id="${r.panel_id}"><i class="fas fa-sync-alt"></i> Reset</button>
                    </div>
                `;
            } else {
                // Agar Admin NAHI hai, to sirf Session button dikhao
                cardBody += `
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-outline-info flex-fill fw-bold session-history-btn" data-userid="${r.user_id}"><i class="fas fa-history"></i> Sessions</button>
                    </div>
                `;
            }

            html += `<div class="mobile-item">${cardBody}</div>`;
        });
    }
    $('#mobileCardsContainer').html(html);
    if (typeof window.applyPermissions === 'function') window.applyPermissions();
}
            function renderMobilePagination(json) {
                if (!json) return;
                let info = table.page.info();
                if (info.pages <= 1) {
                    $('#mobilePaginationWrapper').html('');
                    return;
                }
                let html =
                    `
                    <button class="btn btn-sm btn-dark px-3 m-prev" ${info.page === 0 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i> Prev</button>
                    <span class="small fw-bold text-secondary">Page ${info.page + 1} of ${info.pages}</span>
                    <button class="btn btn-sm btn-dark px-3 m-next" ${info.page === (info.pages - 1) ? 'disabled' : ''}>Next <i class="fas fa-chevron-right"></i></button>`;
                $('#mobilePaginationWrapper').html(html);
            }

            $(document).on('click', '.m-prev', function() {
                table.page('previous').draw('page');
            });
            $(document).on('click', '.m-next', function() {
                table.page('next').draw('page');
            });

            // ==========================================
            // EDIT SHIFT TIMINGS LOGIC
            // ==========================================
            $(document).on('click', '.edit-shift-btn', function() {
                $('#edit_shift_panel_id').val($(this).data('id'));
                $('#edit_time_from').val($(this).data('from'));
                $('#edit_time_to').val($(this).data('to'));
                $('#editShiftModal').modal('show');
            });

            $('#editShiftForm').submit(function(e) {
                e.preventDefault();
                let btn = $('#btnUpdateShift');
                btn.prop('disabled', true).text('Updating...');

                $.ajax({
                    url: '/api/v1/update-shift-timings',
                    type: 'POST',
                    data: {
                        panel_id: $('#edit_shift_panel_id').val(),
                        p_time_from: $('#edit_time_from').val(),
                        p_time_to: $('#edit_time_to').val()
                    },
                    success: function(res) {
                        $('#editShiftModal').modal('hide');
                        table.ajax.reload(null, false);
                        alert(res.message);
                    },
                    error: function(err) {
                        alert(err.responseJSON.message || 'Failed to update shift timings.');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Update Shift Limits');
                    }
                });
            });

            // ==========================================
            // EMERGENCY MULTI-DAY ACCESS LOGIC
            // ==========================================
            $(document).on('click', '.emergency-btn', function() {
                $('#emergencyAccessForm')[0].reset();
                $('#e_panel_id').val($(this).data('id'));
                $('#btnEmergencySave').text('Authorize Time Parameters');
                $('#emergencyAccessModal').modal('show');
            });

            $('#emergencyAccessForm').submit(function(e) {
                e.preventDefault();
                let btn = $('#btnEmergencySave');
                btn.prop('disabled', true).text('Processing...');
                $.ajax({
                    url: '/api/v1/grant-emergency-access',
                    type: 'POST',
                    data: {
                        panel_id: $('#e_panel_id').val(),
                        s_time_from: $('#e_time_from').val(),
                        s_time_to: $('#e_time_to').val(),
                        s_date_from: $('#e_date_from').val(),
                        s_date_to: $('#e_date_to').val(),
                        device_token: $('#e_device_token').val() || ''
                    },
                    success: function(res) {
                        $('#emergencyAccessModal').modal('hide');
                        table.ajax.reload(null, false);
                        alert(res.message);
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Authorize Time Parameters');
                    }
                });
            });

            // ==========================================
            // HARDWARE BINDING: SMART UNBIND WIZARD & RESET
            // ==========================================
            $(document).on('click', '.hard-reset-btn', function() {
                let pId = $(this).data('id');
                if (confirm(
                        "CRITICAL WARNING: This will unbind all devices and GENERATE A NEW PASSWORD for this employee. Do you want to proceed?"
                        )) {
                    let btn = $(this);
                    let originalHtml = btn.html();
                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

                    $.ajax({
                        url: '/api/v1/hard-reset-access',
                        type: 'POST',
                        data: {
                            panel_id: pId
                        },
                        success: function(res) {
                            table.ajax.reload(null, false);
                            $('#show_panel_id').text(pId);
                            $('#show_password').text(res.new_password);
                            $('#credentialsModal').modal('show');
                        },
                        error: function(err) {
                            alert(err.responseJSON.message || "Failed to execute Hard Reset.");
                        },
                        complete: function() {
                            btn.prop('disabled', false).html(originalHtml);
                        }
                    });
                }
            });

            // 🔥 FIX: MOBILE UNBIND ICON UNDEFINED ERROR RESOLVED 🔥
            $(document).on('click', '.dynamic-unbind-trigger', function() {
                let pId = $(this).data('id');
                $('#su_panel_id').val(pId);

                // Fetch row data robustly from DataTables API (Works for both Desktop TR & Mobile Divs)
                let rowData = null;
                table.rows().every(function() {
                    if (this.data().panel_id === pId) rowData = this.data();
                });

                if (!rowData) {
                    alert("System error: Unable to load hardware mapping parameters.");
                    return;
                }

                let alternatives = rowData.other_devices || [];
                let optHtml = '<option value="">-- Choose Unrecognized Node Attempt Log --</option>';
                alternatives.forEach(a => {
                    if (a.status !== 'rejected') {
                        optHtml +=
                            `<option value="${a.device_token}">At ${a.time} - Token: [ ${a.device_token.substring(0,18)}... ]</option>`;
                    }
                });

                $('#su_target_device_token').html(optHtml);
                $('#replacement_device_wrapper').addClass('d-none');
                $('#act_clear').prop('checked', true);

                $('#smartUnbindModal').modal('show');
            });

            $(document).on('change', 'input[name="action_type"]', function() {
                if ($(this).val() === 'replace_existing') {
                    $('#replacement_device_wrapper').removeClass('d-none');
                } else {
                    $('#replacement_device_wrapper').addClass('d-none');
                }
            });

            $('#smartUnbindForm').submit(function(e) {
                e.preventDefault();
                let btn = $('#btnExecuteSmartUnbind');
                btn.prop('disabled', true).text('Executing Network Intercept Re-write...');

                $.ajax({
                    url: '/api/v1/smart-unbind-device',
                    type: 'POST',
                    data: {
                        panel_id: $('#su_panel_id').val(),
                        action_type: $('input[name="action_type"]:checked').val(),
                        target_device_token: $('#su_target_device_token').val()
                    },
                    success: function(res) {
                        $('#smartUnbindModal').modal('hide');
                        table.ajax.reload(null, false);
                        $('#show_panel_id').text($('#su_panel_id').val());
                        $('#show_password').text(res.new_password);
                        $('#credentialsModal').modal('show');
                    },
                    error: function(err) {
                        alert(err.responseJSON.message ||
                            'Workflow Engine Exception execution aborted.');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text(
                            'Execute Network Authorization Rewrite');
                    }
                });
            });

            // ==========================================
            // SECURITY GATEWAY & INTRUSION ALERTS LOGIC
            // ==========================================
            $(document).on('click', '.view-requests-btn', function() {
                let pId = $(this).data('id');
                // Use robust row finding
                table.rows().every(function() {
                    if (this.data().panel_id === pId) currentGatewayRowData = this.data();
                });

                $('#gatewayLogDate').val('');
                renderGatewayLogs(currentGatewayRowData, null);
                $('#deviceRequestsModal').modal('show');
            });

            $('#gatewayLogDate').on('change', function() {
                renderGatewayLogs(currentGatewayRowData, $(this).val());
            });
            $('#clearGatewayLogDate').on('click', function() {
                $('#gatewayLogDate').val('');
                renderGatewayLogs(currentGatewayRowData, null);
            });

            $(document).on('click', '.set-role-btn', function(e) {
                e.preventDefault();
                let pId = $(this).data('panel'),
                    token = $(this).data('token'),
                    role = $(this).data('role');
                let confirmMsg = role === 'primary' ?
                    "Are you sure you want to make this the Permanent Primary Device? The current primary device will be overwritten." :
                    "Are you sure you want to unbind this device? The employee will need to bind a new device or you will have to set a new primary.";

                if (confirm(confirmMsg)) {
                    $.ajax({
                        url: '/api/v1/set-device-role',
                        type: 'POST',
                        data: {
                            panel_id: pId,
                            device_token: token,
                            role: role
                        },
                        success: function(res) {
                            $('#deviceRequestsModal').modal('hide');
                            table.ajax.reload(null, false);
                            alert(res.message);
                        },
                        error: function(err) {
                            alert(err.responseJSON.message || 'Error executing role change.');
                        }
                    });
                }
            });

            $(document).on('click', '.make-secondary-btn', function(e) {
                e.preventDefault();
                $('#deviceRequestsModal').modal('hide');
                $('#emergencyAccessForm')[0].reset();
                $('#e_panel_id').val($(this).data('panel'));

                if ($('#e_device_token').length === 0) {
                    $('#emergencyAccessForm').append('<input type="hidden" id="e_device_token">');
                }
                $('#e_device_token').val($(this).data('token'));
                $('#btnEmergencySave').text('Authorize Secondary Device');
                $('#emergencyAccessModal').modal('show');
            });

            $(document).on('click', '.reject-device-btn', function() {
                if (confirm("Reject this unique entry log attempt?")) executeNodeUpdate(
                    '/api/v1/reject-device', $(this).data('panel'), $(this).data('token'));
            });

            $(document).on('click', '.block-device-btn', function() {
                if (confirm("CRITICAL WARNING: Block this hardware node signature permanently?"))
                    executeNodeUpdate('/api/v1/block-device', $(this).data('panel'), $(this).data('token'));
            });

            $(document).on('click', '.unblock-device-btn', function() {
                if (confirm("Restore connectivity for this blocked signature?")) executeNodeUpdate(
                    '/api/v1/unblock-device', $(this).data('panel'), $(this).data('token'));
            });

            function executeNodeUpdate(targetUrl, pId, tokenSignature) {
                $.ajax({
                    url: targetUrl,
                    type: 'POST',
                    data: {
                        panel_id: pId,
                        device_token: tokenSignature
                    },
                    success: function(res) {
                        $('#deviceRequestsModal').modal('hide');
                        table.ajax.reload(null, false);
                        alert(res.message);
                    },
                    error: function(err) {
                        alert(err.responseJSON.message || 'Transmission error');
                    }
                });
            }

            function renderGatewayLogs(rowData, filterDate) {
                if (!rowData) return;

                let pId = rowData.panel_id;
                let attempts = rowData.other_devices || [];
                let blocked = rowData.blocked_devices || [];
                let primary = rowData.primary_device;
                let secondary = rowData.secondary_device;

                let now = new Date();
                let currentH = now.getHours().toString().padStart(2, '0');
                let currentM = now.getMinutes().toString().padStart(2, '0');
                let currentTimeStr = currentH + ':' + currentM;

                let isSecActive = false;
                if (rowData.s_status === 'allow' && rowData.s_time_from && rowData.s_time_to) {
                    let sFrom = rowData.s_time_from.substring(0, 5);
                    let sTo = rowData.s_time_to.substring(0, 5);
                    if (currentTimeStr >= sFrom && currentTimeStr <= sTo) isSecActive = true;
                }

                let desktopHtml = '',
                    mobileHtml = '';

                if (!filterDate) {
                    blocked.forEach(token => {
                        desktopHtml += `<tr class="table-danger">
                            <td><span class="badge bg-danger">BLOCKED SIGNATURE</span></td>
                            <td><code class="small fw-bold text-dark">${token.substring(0,12)}...</code></td>
                            <td>System Sandbox Protection</td>
                            <td class="text-end"><button class="btn btn-sm btn-success fw-bold unblock-device-btn" data-panel="${pId}" data-token="${token}"><i class="fas fa-check-circle"></i> Unblock Device</button></td>
                        </tr>`;

                        mobileHtml += `<div class="card border border-danger mb-2 p-2 bg-white shadow-sm">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-danger small">BLOCKED SIGNATURE</span>
                                <button class="btn btn-sm btn-success fw-bold unblock-device-btn" data-panel="${pId}" data-token="${token}">Unblock</button>
                            </div>
                            <div class="small mt-1 font-monospace">Token: ${token.substring(0,15)}...</div>
                        </div>`;
                    });
                }

                attempts.forEach(a => {
                    if (filterDate) {
                        let logDate = a.time.split(' ')[0];
                        if (logDate !== filterDate) return;
                    }

                    let mapBtn = (a.latitude && a.latitude !== 'Location Denied') ?
    `<a href="https://maps.google.com/?q=${a.latitude},${a.longitude}" target="_blank" class="btn btn-xs btn-outline-primary py-0 small"><i class="fas fa-map-marker-alt text-danger"></i> View GPS Map</a>` :
    '<span class="text-muted small">No GPS Coordinates</span>';

                    let actionHtmlDesktop = '',
                        actionHtmlMobile = '';
                    let isPrimary = (a.device_token === primary);
                    let isSecondary = (a.device_token === secondary && isSecActive);
                    let isBlocked = blocked.includes(a.device_token);
                    let isRejected = (a.status === 'rejected');

                    if (isPrimary || isSecondary) {
                        let lbl = isPrimary ? 'Permanently Bound' : 'Emergency Shift Active';
                        let badge =
                            `<span class="badge bg-success px-2 py-1 shadow-sm"><i class="fas fa-check-circle me-1"></i> ${lbl}</span>`;
                        let stopBtn =
                            `<button class="btn btn-sm btn-danger fw-bold ms-2 set-role-btn shadow-sm" data-panel="${pId}" data-token="${a.device_token}" data-role="unbind"><i class="fas fa-unlink"></i> Unbind</button>`;
                        actionHtmlDesktop =
                            `<div class="d-flex align-items-center justify-content-end">${badge} ${stopBtn}</div>`;
                        actionHtmlMobile =
                            `<div class="d-flex justify-content-between align-items-center mt-2">${badge} ${stopBtn}</div>`;
                    } else if (isBlocked) {
                        let badge =
                            `<span class="badge bg-danger px-2 py-1 shadow-sm"><i class="fas fa-ban me-1"></i> Already Blocked</span>`;
                        actionHtmlDesktop = badge;
                        actionHtmlMobile = badge;
                    } else {
                        let rejectBadge = isRejected ?
                            `<span class="badge bg-warning text-dark me-2 border border-warning shadow-sm"><i class="fas fa-times-circle"></i> Rejected</span>` :
                            '';
                        let rejectBtnHtml = isRejected ? '' :
                            `<button class="btn btn-sm btn-warning reject-device-btn" data-panel="${pId}" data-token="${a.device_token}"><i class="fas fa-times"></i> Reject</button>`;

                        let approveDropdown = `
                            <div class="dropdown d-inline-block shadow-sm me-1">
                                <button class="btn btn-sm btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-check"></i> Approve</button>
                                <ul class="dropdown-menu shadow border-0">
                                    <li><a class="dropdown-item text-success fw-bold set-role-btn" href="#" data-panel="${pId}" data-token="${a.device_token}" data-role="primary"><i class="fas fa-desktop me-2"></i> Set as Primary</a></li>
                                    <li><a class="dropdown-item text-warning fw-bold make-secondary-btn" href="#" data-panel="${pId}" data-token="${a.device_token}"><i class="fas fa-clock me-2"></i> Set as Secondary</a></li>
                                </ul>
                            </div>`;

                        actionHtmlDesktop = `
                            <div class="d-flex align-items-center justify-content-end">
                                ${rejectBadge}
                                <div class="btn-group shadow-sm">
                                    ${approveDropdown}
                                    ${rejectBtnHtml}
                                    <button class="btn btn-sm btn-danger block-device-btn ms-1" data-panel="${pId}" data-token="${a.device_token}"><i class="fas fa-ban"></i> Block</button>
                                </div>
                            </div>`;

                        actionHtmlMobile = `
                            <div class="d-flex align-items-center justify-content-between mt-2">
                                ${rejectBadge}
                                <div class="btn-group shadow-sm flex-fill">
                                    ${approveDropdown}
                                    ${rejectBtnHtml}
                                    <button class="btn btn-sm btn-danger block-device-btn ms-1" data-panel="${pId}" data-token="${a.device_token}">Block</button>
                                </div>
                            </div>`;
                    }

                    desktopHtml += `<tr>
                        <td class="small fw-bold text-secondary">${a.time}</td>
                        <td><code class="small">${a.device_token.substring(0,12)}...</code></td>
                        <td>${mapBtn}</td>
                        <td class="text-end">${actionHtmlDesktop}</td>
                    </tr>`;

                    mobileHtml += `<div class="card border mb-2 p-3 bg-white shadow-sm rounded-3">
                        <div class="fw-bold small text-muted mb-1"><i class="far fa-clock text-warning"></i> At: ${a.time}</div>
                        <div class="small font-monospace mb-2 text-truncate">ID: <code>${a.device_token}</code></div>
                        <div class="mb-2">${mapBtn}</div>
                        ${actionHtmlMobile}
                    </div>`;
                });

                if (desktopHtml === '') desktopHtml =
                    '<tr><td colspan="4" class="text-center p-3 text-muted">No records found for the selected date filter.</td></tr>';
                if (mobileHtml === '') mobileHtml =
                    '<div class="text-center text-muted small p-3 bg-white border rounded">No records found for this date.</div>';

                $('#requestsTableBody').html(desktopHtml);
                $('#requestsMobileContainer').html(mobileHtml);
            }


            // ==========================================
            // BULK COMPANY SHIFT LOGIC
            // ==========================================
            let bCompanyMap = {};

            $('#btnOpenBulkShiftModal').click(function() {
                $('#bulkShiftForm')[0].reset();
                $('#b_time_from').val('09:00');
                $('#b_time_to').val('18:00');
                $('#b_company_id').val('');

                // Load active companies dynamically
                $.ajax({
                    url: '/api/v1/get-active-companies',
                    type: 'GET',
                    success: function(res) {
                        let opts = '';
                        bCompanyMap = {};
                        if (res.data) {
                            res.data.forEach(c => {
                                opts += `<option value="${c.company_name}">`;
                                bCompanyMap[c.company_name] = c.id;
                            });
                        }
                        $('#b_companyList').html(opts);
                    }
                });
                $('#bulkShiftModal').modal('show');
            });

            $('#b_company_input').on('input change', function() {
                let val = $(this).val();
                if (bCompanyMap[val]) {
                    $('#b_company_id').val(bCompanyMap[val]);
                    this.setCustomValidity('');
                } else {
                    $('#b_company_id').val('');
                    if (val !== '') this.setCustomValidity('Select valid company');
                }
            });

            $('#bulkShiftForm').submit(function(e) {
                e.preventDefault();
                
                if(!confirm("Are you sure? This will overwrite the shift timings for EVERY employee in this company.")) return;

                let btn = $('#btnUpdateBulkShift');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Updating...');

                $.ajax({
                    url: '/api/v1/update-company-shift-timings',
                    type: 'POST',
                    data: {
                        company_id: $('#b_company_id').val(),
                        p_time_from: $('#b_time_from').val(),
                        p_time_to: $('#b_time_to').val()
                    },
                    success: function(res) {
                        $('#bulkShiftModal').modal('hide');
                        table.ajax.reload(null, false);
                        alert(res.message);
                    },
                    error: function(err) {
                        alert(err.responseJSON.message || 'Failed to update company shift timings.');
                    },
                    complete: function() { 
                        btn.prop('disabled', false).html('<i class="fas fa-check-double me-1"></i> Apply to All Employees'); 
                    }
                });
            });



            // ==========================================
            // FETCH & RENDER MULTI-SESSION LOGS
            // ==========================================
            $(document).on('click', '.session-history-btn', function() {
                let userId = $(this).data('userid');
                $('#sessionLogUserId').val(userId);
                let today = new Date().toISOString().split('T')[0];
                $('#sessionLogDate').val(today);
                fetchSessionLogs(userId, today);
                $('#sessionLogsModal').modal('show');
            });

            $('#sessionLogDate').on('change', function() {
                fetchSessionLogs($('#sessionLogUserId').val(), $(this).val());
            });

            function fetchSessionLogs(userId, dateStr) {
                $('#sessionLogsContainer').html(
                    '<div class="text-center p-3"><i class="fas fa-spinner fa-spin fa-2x text-info"></i><br><small class="text-muted mt-2">Loading logs...</small></div>'
                    );
                $.ajax({
                    url: `/api/v1/get-session-logs?user_id=${userId}&date=${dateStr}`,
                    type: 'GET',
                    success: function(res) {
                        let logs = res.data;
                        let html = '';
                        if (!logs || logs.length === 0) {
                            html =
                                '<div class="alert alert-secondary text-center small fw-bold">No login sessions found for this date.</div>';
                        } else {
                            html += '<ul class="list-group shadow-sm">';
                          logs.forEach((log, index) => {
    let outTime = log.out ? `<span class="text-danger fw-bold">${log.out}</span>` : `<span class="badge bg-warning text-dark"><i class="fas fa-circle-notch fa-spin"></i> Running / Missed</span>`;
    
    // View Map Button Logic
 let mapBtn = (log.lat && log.lng && log.lat !== 'Location Denied') 
    ? `<a href="https://www.google.com/maps?q=${log.lat},${log.lng}" target="_blank" class="btn btn-xs btn-outline-primary ms-2"><i class="fas fa-map-marker-alt text-danger"></i> Map</a>` 
    : '<span class="badge bg-light text-muted ms-2 border"><i class="fas fa-satellite-dish text-secondary"></i> No GPS</span>';

    html += `<li class="list-group-item d-flex justify-content-between align-items-center bg-white">
        <div><span class="badge bg-secondary me-2">Session ${index + 1}</span> <b class="text-muted small">IN:</b> <span class="text-success fw-bold ms-1">${log.in}</span> ${mapBtn}</div>
        <div class="text-end"><b class="text-muted small">OUT:</b> <span class="ms-1">${outTime}</span></div>
    </li>`;
});
                            html += '</ul>';
                        }
                        $('#sessionLogsContainer').html(html);
                    }
                });
            }

            // Select All Checkbox Logic
$('#selectAllCheckbox').on('change', function() {
    $('.row-checkbox').prop('checked', $(this).prop('checked'));
    toggleDeleteButton();
});

$(document).on('change', '.row-checkbox', function() {
    toggleDeleteButton();
});

function toggleDeleteButton() {
    if (CAN_DELETE && $('.row-checkbox:checked').length > 0) {
        $('#btnBulkDelete').removeClass('d-none');
    } else {
        $('#btnBulkDelete').addClass('d-none');
    }
}

// Bulk Delete Action
$('#btnBulkDelete').on('click', function() {
    let selectedIds = [];
    $('.row-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });

    if (confirm(`Are you sure you want to delete ${selectedIds.length} selected records?`)) {
        // Yahan aap apni Bulk Delete API call karenge
        console.log("Ids to delete:", selectedIds);
        // $.ajax({ ... });
    }
});


        });
    </script>
@endpush
