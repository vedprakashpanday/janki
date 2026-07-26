@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        /* Styling as per previous premium UI */
        .mobile-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            background: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
        }

        /* Fix Select2 Overflow Issue */
        .select2-container {
            width: 100% !important;
            max-width: 100%;
        }

        .select2-container--default .select2-selection--single {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            padding-right: 25px !important;
        }

        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
        }
    </style>

    <div class="container-fluid mt-3">

        <!-- Top Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 text-primary"><i class="fas fa-globe"></i> Bank Details (Directory)</h4>
            <div id="topActionButtons">
                <button class="btn btn-primary btn-sm d-none shadow-sm" id="btnAddDirect"
                    onclick="openAddModal('active')"><i class="fas fa-check-circle"></i> Add Direct</button>
                <button class="btn btn-warning btn-sm d-none shadow-sm" id="btnAddRequest"
                    onclick="openAddModal('pending')"><i class="fas fa-clock"></i> Add Request</button>
                <button class="btn btn-success btn-sm d-none shadow-sm" id="btnExport"><i class="fas fa-file-excel"></i>
                    Excel</button>
                <button class="btn btn-dark btn-sm d-none shadow-sm" id="btnPrintCustom"><i class="fas fa-print"></i>
                    Print</button>
            </div>
        </div>

        <!-- Filter Section (Only on Directory Page) -->
        <div class="card shadow-sm border-0 rounded mb-4 bg-light">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Company</label>
                        <select id="filter_company" class="form-control"></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Branch</label>
                        <select id="filter_branch" class="form-control"></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small">Start Date</label>
                        <input type="date" id="filter_start_date" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small">End Date</label>
                        <input type="date" id="filter_end_date" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100 fw-bold" id="btnApplyFilter"><i class="fas fa-filter"></i>
                            Apply</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Table Card -->
        <div class="card shadow-sm border-0 rounded">
            <div class="card-body">
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover table-striped w-100 align-middle" id="bankDetailsTable">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" id="selectAllDesktop"></th>
                                <th>ID</th>
                                <th>Holder Info</th>
                                <th>Account No</th>
                                <th>Bank Name</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="d-block d-md-none">
                    <div id="mobileCardsContainer"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- (Keep the exact same Modal HTML here as provided in the Daily view) -->
    <!-- Beautiful Modal (Make sure this exists in your blade file) -->
    <div class="modal fade" id="bankDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="bankDetailForm">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalTitle"><i class="fas fa-university"></i> Add Bank Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-3 p-4">
                        <input type="hidden" id="bank_detail_id" name="id">

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Company <span class="text-danger">*</span></label>
                            <select class="form-control" id="company_id" name="company_id" required></select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Branch (Default: HO)</label>
                            <select class="form-control" id="branch_id" name="branch_id"></select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold text-primary"><i class="fas fa-search"></i> Search Account
                                Holder <span class="text-danger">*</span></label>
                            <select class="form-control" id="member_id" name="member_id" required></select>
                            <small class="text-muted">Type at least 3 letters to search across all roles.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Account Name</label>
                            <input type="text" name="account_name" id="account_name" class="form-control bg-light"
                                placeholder="Auto-filled" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Account No <span class="text-danger">*</span></label>
                            <input type="text" name="account_no" id="account_no" class="form-control" required
                                placeholder="Enter A/C Number">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Account Type</label>
                            <select name="account_type" id="account_type" class="form-select">
                                <option value="saving">Saving</option>
                                <option value="current">Current</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Bank Name</label>
                            <input type="text" name="bank_name" id="bank_name" class="form-control"
                                placeholder="e.g. SBI, HDFC">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">IFSC Code</label>
                            <input type="text" name="ifsc_code" id="ifsc_code" class="form-control"
                                placeholder="e.g. SBIN0001234">
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4" id="btnSave"><i class="fas fa-save"></i>
                            Save Details</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // 🔥 ZERO-TRUST SMART TOKEN FETCHER 🔥
        function getAuthToken() {
            let path = window.location.pathname;

            // Check exact portal from URL and return STRICTLY that portal's token
            if (path.startsWith('/admin')) {
                return localStorage.getItem('admin_token');
            } else if (path.startsWith('/employee')) {
                return localStorage.getItem('emp_token');
            } else if (path.startsWith('/member')) {
                return localStorage.getItem('member_token');
            } else if (path.startsWith('/ceo')) {
                return localStorage.getItem('ceo_token');
            }

            // Fallback (for shared pages without specific prefix)
            return localStorage.getItem('admin_token') || localStorage.getItem('emp_token');
        }

        // Global AJAX Setup remains same
        $.ajaxSetup({
            headers: {
                'Authorization': 'Bearer ' + getAuthToken(),
                'Accept': 'application/json'
            }
        });

        let dataTable;
        let userContext = {}; // 🔥 FIX: Defined userContext globally
        let selectedIds = [];
        const rbacPrefix = 'bank_dir_';
        const apiUrl = '/api/v1/bank-details/directory';

        $(document).ready(function() {
            // Init RBAC & Context
            $.get('/api/v1/context', function(res) {
                userContext = res; // 🔥 FIX: Store context
                let perms = res.permissions || [];
                let isGod = res.is_god || res.role_level === 'ceo';

                if (isGod || perms.includes(rbacPrefix + 'add_direct')) $('#btnAddDirect').removeClass(
                    'd-none');
                if (!isGod && perms.includes(rbacPrefix + 'add_request')) $('#btnAddRequest').removeClass(
                    'd-none');
                if (isGod || perms.includes(rbacPrefix + 'export')) $('#btnExport').removeClass('d-none');
                if (isGod || perms.includes(rbacPrefix + 'print')) $('#btnPrintCustom').removeClass(
                    'd-none');

                // 🔥 FIX: Initialize table ONLY AFTER permissions are loaded
                initDataTable();
            });

            // Setup Select2 for Company (Add Modal)
            $('#company_id').select2({
                dropdownParent: $('#bankDetailModal'),
                placeholder: 'Search Company...',
                ajax: {
                    url: '/api/v1/companies/search-dynamic',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: $.map(data.data || data, function(item) {
                                return {
                                    text: item.company_name,
                                    id: item.id
                                }
                            })
                        };
                    }
                }
            });

            // Setup Select2 for Company
            $('#company_id').select2({
                dropdownParent: $('#bankDetailModal'),
                placeholder: 'Search Company...',
                ajax: {
                    url: '/api/v1/companies/search-dynamic',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: $.map(data.data || data, function(item) {
                                return {
                                    text: item.company_name,
                                    id: item.id
                                }
                            })
                        };
                    }
                }
            });

            // 🔥 FIX 1: Use 'select2:select' insted of 'change' taaki Edit Modal me automatically override na ho
            $('#company_id').on('select2:select', function(e) {
                let compName = e.params.data.text; // Selected company ka naam
                if (compName) {
                    let hoText = 'Head Office (' + compName + ')';
                    // Empty string ('') bhejne par Laravel khud ise database me NULL store karega
                    let defaultOption = new Option(hoText, '', true, true);
                    $('#branch_id').empty().append(defaultOption).trigger('change');
                }
            });

            // Setup Select2 for Branch
            $('#branch_id').select2({
                dropdownParent: $('#bankDetailModal'),
                // 🔥 FIX 2: Placeholder aur allowClear yahan se HATA DIYA HAI taaki empty value (NULL) par text dikhai de
                ajax: {
                    url: '/api/v1/branches/search-dynamic',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            company_id: $('#company_id').val()
                        };
                    },
                    processResults: function(data, params) {
                        let companyName = $('#company_id option:selected').text() || 'Selected Company';
                        let branchList = $.map(data.data || data || [], function(item) {
                            return {
                                text: item.branch_name,
                                id: item.id
                            }
                        });

                        // List me top par manually Head Office option daal rahe hain
                        let searchTerm = (params.term || '').toLowerCase();
                        let hoText = 'Head Office (' + companyName + ')';

                        if (searchTerm === '' || hoText.toLowerCase().includes(searchTerm) ||
                            'head office'.includes(searchTerm)) {
                            branchList.unshift({
                                id: '',
                                text: hoText
                            });
                        }

                        return {
                            results: branchList
                        };
                    }
                }
            });

            // Account Holder Search
            $('#member_id').select2({
                dropdownParent: $('#bankDetailModal'),
                placeholder: 'Search name or ID...',
                minimumInputLength: 3,
                ajax: {
                    url: '/api/v1/bank-details/search-holder',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        // 🔥 FIX: Backend ko Company aur Branch bhej rahe hain
                        return {
                            q: params.term,
                            company_id: $('#company_id').val(),
                            branch_id: $('#branch_id').val()
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: $.map(data, function(item) {
                                return {
                                    text: item.name + ' (' + item.id + ') - ' + item.type,
                                    id: item.id,
                                    name: item.name
                                }
                            })
                        };
                    }
                }
            }).on('select2:select', function(e) {
                $('#account_name').val(e.params.data.name);
            });

            // Select2 For Filters
            $('#filter_company').select2({
                placeholder: 'All Companies',
                allowClear: true,
                ajax: {
                    url: '/api/v1/companies/search-dynamic',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: $.map(data.data || data, function(item) {
                                return {
                                    text: item.company_name,
                                    id: item.id
                                }
                            })
                        };
                    }
                }
            });

            $('#filter_branch').select2({
                placeholder: 'All Branches',
                allowClear: true,
                ajax: {
                    url: '/api/v1/branches/search-dynamic',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            company_id: $('#filter_company').val()
                        };
                    },
                    processResults: function(data, params) {
                        let companyName = $('#filter_company option:selected').text() ||
                            'Selected Company';
                        let branchList = $.map(data.data || data, function(item) {
                            return {
                                text: item.branch_name,
                                id: item.id
                            }
                        });

                        let searchTerm = (params.term || '').toLowerCase();
                        let hoText = 'Head Office (' + companyName + ')';

                        if (searchTerm === '' || hoText.toLowerCase().includes(searchTerm)) {
                            branchList.unshift({
                                id: '',
                                text: hoText
                            });
                        }
                        return {
                            results: branchList
                        };
                    }
                }
            });

            // Filter Apply & Buttons
            $('#btnApplyFilter').on('click', function() {
                let params = $.param({
                    company_id: $('#filter_company').val(),
                    branch_id: $('#filter_branch').val(),
                    start_date: $('#filter_start_date').val(),
                    end_date: $('#filter_end_date').val()
                });
                dataTable.ajax.url(apiUrl + '?' + params).load();
            });

            $('#btnPrintCustom').on('click', function() {
                let params = $.param({
                    company_id: $('#filter_company').val(),
                    branch_id: $('#filter_branch').val(),
                    start_date: $('#filter_start_date').val(),
                    end_date: $('#filter_end_date').val()
                });
                // 🔥 FIX: Automatically detect /admin/ or /employee/ prefix for 404 issue
                let prefix = window.location.pathname.split('/')[1];
                window.open('/' + prefix + '/bank-details-print?' + params, '_blank');
            });

            $('#btnExport').on('click', function() {
                dataTable.button('.buttons-excel').trigger();
            });
        });

        function initDataTable() {
            if ($.fn.DataTable.isDataTable('#bankDetailsTable')) {
                $('#bankDetailsTable').DataTable().destroy();
            }

            dataTable = $('#bankDetailsTable').DataTable({
                ajax: {
                    url: apiUrl,
                    dataSrc: ''
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                buttons: [{
                    extend: 'excel',
                    className: 'd-none',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5]
                    }
                }],
                columns: [{
                        data: null,
                        render: function(data) {
                            return `<input type="checkbox" class="row-checkbox form-check-input" value="${data.id}">`;
                        },
                        orderable: false
                    },
                    {
                        data: 'id'
                    },
                    {
                        data: null,
                        render: function(data) {
                            return `<b>${data.account_name}</b><br><small class="text-muted">${data.member_id}</small>`;
                        }
                    },
                    {
                        data: 'account_no'
                    },
                    {
                        data: null,
                        render: function(data) {
                            return `${data.bank_name || 'N/A'}<br><small class="text-muted">${data.ifsc_code || ''}</small>`;
                        }
                    },
                    {
                        data: 'status',
                        render: function(data) {
                            if (data == 'active') return `<span class="badge bg-success">Active</span>`;
                            if (data == 'inactive') return `<span class="badge bg-danger">Inactive</span>`;
                            return `<span class="badge bg-warning text-dark">Pending</span>`;
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            return renderActionButtons(data);
                        },
                        orderable: false
                    }
                ],
                // 🔥 FIX: Ensure Mobile Cards load properly after table draw
                drawCallback: function() {
                    let currentData = this.api().rows({
                        page: 'current'
                    }).data().toArray();
                    renderMobileCards(currentData);
                }
            });
        }

        function renderActionButtons(row) {
            let perms = userContext.permissions || [];
            let isGod = userContext.is_god || ['ceo', 'admin', 'developer'].includes(userContext.role_level);

            // 🔥 FIX: Owner Logic -> Kya ye record current user ne banaya hai?
            let isOwner = (row.created_by == userContext.profile_id);

            let btns = `<div class="btn-group shadow-sm">`;

            // Edit button: God/Permitted ko dikhega, ya phir Owner ko (agar status pending hai)
            if (isGod || perms.includes(rbacPrefix + 'edit') || (isOwner && row.status === 'pending')) {
                btns +=
                    `<button class="btn btn-sm btn-outline-info" onclick='editRow(${JSON.stringify(row)})'><i class="fas fa-edit"></i></button>`;
            }

            // Approve/Reject strictly roles wale ko hi dikhega
            if (row.status === 'pending' && (isGod || perms.includes(rbacPrefix + 'appr'))) {
                btns +=
                    `<button class="btn btn-sm btn-outline-success" onclick="updateStatus(${row.id}, 'approve')"><i class="fas fa-check"></i></button>`;
            }
            if (row.status === 'pending' && (isGod || perms.includes(rbacPrefix + 'rej'))) {
                btns +=
                    `<button class="btn btn-sm btn-outline-warning" onclick="updateStatus(${row.id}, 'reject')"><i class="fas fa-times"></i></button>`;
            }

            // Delete button: God/Permitted ko dikhega, ya phir Owner ko (agar status pending hai)
            if (isGod || perms.includes(rbacPrefix + 'delete') || (isOwner && row.status === 'pending')) {
                btns +=
                    `<button class="btn btn-sm btn-outline-danger" onclick="deleteSoft(${row.id})"><i class="fas fa-trash"></i></button>`;
            }

            btns += `</div>`;
            return btns;
        }

        function renderMobileCards(data) {
            if (!data || data.length === 0) {
                $('#mobileCardsContainer').html('<div class="alert alert-secondary">No records found.</div>');
                return;
            }
            let html = '';
            data.forEach(row => {
                html += `
            <div class="mobile-card">
                <div class="d-flex"><span class="title">Holder:</span> <span>${row.account_name} (${row.member_id})</span></div>
                <div class="d-flex"><span class="title">A/C No:</span> <span class="fw-bold">${row.account_no}</span></div>
                <div class="d-flex"><span class="title">Status:</span> <span>${row.status.toUpperCase()}</span></div>
                <div class="mt-3 text-end">${renderActionButtons(row)}</div>
            </div>`;
            });
            $('#mobileCardsContainer').html(html);
        }

        function openAddModal() {
            $('#bankDetailForm')[0].reset();
            $('#bank_detail_id').val('');
            $('#member_id, #company_id, #branch_id').val(null).trigger('change');

            // 🔥 Context Logic (Lock for non-admins)
            let isGod = userContext.is_god || ['ceo', 'admin', 'developer'].includes(userContext.role_level);

            // Agar employee parent company ka hai (company_id=1, branch_id=null) usko bhi bypass
            if (userContext.company_id == 1 && !userContext.branch_id) isGod = true;

            if (!isGod && userContext.company_id) {
                // Lock Company
                let compOption = new Option("Your Company", userContext.company_id, true, true);
                $('#company_id').empty().append(compOption).trigger('change').prop('disabled', true);

                // Lock Branch
                if (userContext.branch_id) {
                    let branchOption = new Option("Your Branch", userContext.branch_id, true, true);
                    $('#branch_id').empty().append(branchOption).trigger('change').prop('disabled', true);
                } else {
                    let hoOption = new Option("Head Office", "", true, true);
                    $('#branch_id').empty().append(hoOption).trigger('change').prop('disabled', true);
                }
            } else {
                $('#company_id').prop('disabled', false);
                $('#branch_id').prop('disabled', false);
            }

            $('#modalTitle').html('<i class="fas fa-university"></i> Add Bank Details');
            $('#bankDetailModal').modal('show');
        }

        function editRow(row) {
            $('#bank_detail_id').val(row.id);

            let compName = row.company_name || 'Selected Company';
            $('#company_id').empty().append(new Option(compName, row.company_id, true, true)).trigger('change');

            let branchName = row.branch_name || 'Head Office (' + compName + ')';
            $('#branch_id').empty().append(new Option(branchName, row.branch_id || '', true, true)).trigger('change');

            $('#member_id').empty().append(new Option(row.account_name + " (" + row.member_id + ")", row.member_id, true,
                true)).trigger('change');

            $('#account_name').val(row.account_name);
            $('#account_no').val(row.account_no);
            $('#account_type').val(row.account_type);
            $('#bank_name').val(row.bank_name);
            $('#ifsc_code').val(row.ifsc_code);

            // 🔥 Context Logic (Lock for non-admins)
            let isGod = userContext.is_god || ['ceo', 'admin', 'developer'].includes(userContext.role_level);
            if (userContext.company_id == 1 && !userContext.branch_id) isGod = true;

            if (!isGod) {
                $('#company_id').prop('disabled', true);
                $('#branch_id').prop('disabled', true);
            } else {
                $('#company_id').prop('disabled', false);
                $('#branch_id').prop('disabled', false);
            }

            $('#modalTitle').html('<i class="fas fa-edit"></i> Edit Bank Details');
            $('#bankDetailModal').modal('show');
        }

        $('#bankDetailForm').submit(function(e) {
            e.preventDefault();

            // 🔥 FIX: Remove disabled before serialize so data passes to backend
            $('#company_id').prop('disabled', false);
            $('#branch_id').prop('disabled', false);

            let id = $('#bank_detail_id').val();
            $.ajax({
                url: id ? `/api/v1/bank-details/${id}` : `/api/v1/bank-details`,
                type: id ? 'PUT' : 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#bankDetailModal').modal('hide');
                    dataTable.ajax.reload();
                    Swal.fire('Success', res.message, 'success');
                }
            });
        });

        function updateStatus(id, action) {
            $.post(`/api/v1/bank-details/${id}/status`, {
                action: action
            }, function(res) {
                dataTable.ajax.reload();
                Swal.fire('Updated!', res.message, 'success');
            });
        }

        function deleteSoft(id) {
            if (confirm("Delete this record?")) {
                $.ajax({
                    url: `/api/v1/bank-details/${id}`,
                    type: 'DELETE',
                    success: function(res) {
                        dataTable.ajax.reload();
                    }
                });
            }
        }
    </script>
@endpush
