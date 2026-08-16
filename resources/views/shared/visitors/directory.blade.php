@extends('layout.app')

@section('content')
    <style>
        .visitor-photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .mobile-card-photo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .card-checkbox {
            transform: scale(1.3);
            cursor: pointer;
        }

        /* Responsive View Logic */
        @media (min-width: 768px) {
            #mobileCardsContainer {
                display: none !important;
            }

            #desktopTableContainer {
                display: block !important;
            }

            #btnLoadMoreCards {
                display: none !important;
            }
        }

        @media (max-width: 767.98px) {
            #desktopTableContainer {
                display: none !important;
            }

            #mobileCardsContainer {
                display: flex !important;
            }

            /* Hide Desktop Pagination on Mobile */
            .dataTables_paginate,
            .dataTables_info,
            .dataTables_length {
                display: none !important;
            }
        }
    </style>

    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h4 class="mb-0 fw-bold"><i class="fas fa-folder-open text-warning me-2"></i> Visitor Directory</h4>

            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-info text-white secured-item" data-permission="visitors_dir_print"
                    id="btnGenerateReport">
                    <i class="fas fa-print me-1"></i> Print / Report
                </button>
                <button class="btn btn-success secured-item" data-permission="visitors_dir_export" id="btnExportExcel">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </button>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Company</label>
                    <select id="filter_company" class="form-select select2" style="width:100%;"></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Branch</label>
                    <select id="filter_branch" class="form-select select2" style="width:100%;">
                        <option value="">All Branches</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Date</label>
                    <input type="date" id="filter_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Month</label>
                    <input type="month" id="filter_month" class="form-control">
                </div>
                <div class="col-12 text-end">
                    <button class="btn btn-sm btn-secondary" id="btnClearFilters"><i
                            class="fas fa-sync-alt me-1"></i>Reset</button>
                    <button class="btn btn-sm btn-primary" id="btnApplyFilters"><i
                            class="fas fa-filter me-1"></i>Filter</button>
                </div>
            </div>
        </div>

        <!-- Bulk Actions Panel -->
        <div id="bulkActionsPanel" class="mb-3 p-2 bg-white rounded shadow-sm border" style="display: none;">
            <div class="d-flex align-items-center flex-wrap gap-3">
                <span class="fw-bold text-primary px-2" id="selectedCount">0 Selected</span>
                <button class="btn btn-sm btn-outline-secondary" id="btnSelectAll"><i
                        class="fas fa-check-square me-1"></i>Select All</button>
                <button class="btn btn-sm btn-outline-secondary" id="bDeselectAll"><i
                        class="fas fa-minus-square me-1"></i>Deselect</button>

                <div class="ms-auto d-flex gap-2">
                    <button class="btn btn-sm btn-success secured-item" data-permission="visitors_dir_add_lead"
                        id="btnBulkAddLead">
                        <i class="fas fa-user-plus me-1"></i> Add to General Leads
                    </button>
                    <button class="btn btn-sm btn-danger secured-item" data-permission="visitors_dir_delete"
                        id="btnBulkDelete">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- Desktop DataTables -->
        <div class="card shadow-sm border-0" id="desktopTableContainer">
            <div class="card-body">
                <table class="table table-hover w-100" id="directoryTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" class="form-check-input master-checkbox"
                                    style="transform: scale(1.2);"></th>
                            <th>Date & Time</th>
                            <th>Photo</th>
                            <th>Visitor Details</th>
                            <th>Purpose & Meet</th>
                            <th>Company & Branch</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Cards -->
        <div id="mobileCardsContainer" class="row g-3">
            <!-- Cards injected via JS -->
        </div>

        <!-- Custom Load More Button -->
        <div class="text-center mt-3 d-block d-md-none">
            <button id="btnLoadMoreCards" class="btn btn-outline-primary fw-bold" style="display: none;">
                <i class="fas fa-chevron-down me-1"></i> Load More
            </button>
        </div>
    </div>

    <!-- ===================== MODALS ===================== -->

    <!-- Image Zoom Modal -->
    <div class="modal fade" id="imageZoomModal" tabindex="-1" style="background: rgba(0,0,0,0.8); z-index: 1070;">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-header border-0 p-2 text-end d-block">
                    <button type="button" class="btn-close btn-close-white fs-4 bg-dark" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="zoomedImage" src="" class="img-fluid rounded shadow" style="max-height: 80vh;">
                </div>
            </div>
        </div>
    </div>

    <!-- VIEW VISITOR PROFILE MODAL -->
    <div class="modal fade" id="viewVisitorModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white border-0 position-relative" style="height: 100px;">
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center mt-n5 position-relative pt-0">
                    <img id="view_photo" src=""
                        class="rounded-circle border border-4 border-white shadow-sm zoomable-photo bg-white"
                        style="width: 100px; height: 100px; object-fit: cover; margin-top: -50px; cursor: pointer;">

                    <h5 class="fw-bold mt-2 mb-0" id="view_name">Visitor Name</h5>
                    <span class="badge bg-info text-dark mb-2" id="view_pax">1 Pax</span>

                    <div class="d-flex justify-content-center flex-wrap gap-3 mb-3 text-muted small">
                        <span id="view_visiting_date" class="badge bg-light text-success border"><i
                                class="far fa-calendar-alt"></i> Date</span>
                        <span id="view_time_in" class="badge bg-light text-primary border"><i
                                class="fas fa-sign-in-alt"></i> In</span>
                        <span id="view_time_out" class="badge bg-light text-danger border"><i
                                class="fas fa-sign-out-alt"></i> Out</span>
                        <span><i class="fas fa-phone-alt text-dark"></i> <span id="view_mobile"></span></span>
                    </div>

                    <hr class="mt-0">
                    <div class="row text-start g-3 px-3">
                        <div class="col-6"><small class="text-muted d-block fw-bold">Company / Branch</small><span
                                id="view_company_branch" class="fw-medium text-dark"></span></div>
                        <div class="col-6"><small class="text-muted d-block fw-bold">Location / Address</small><span
                                id="view_address" class="fw-medium text-dark"></span></div>
                        <div class="col-6"><small class="text-muted d-block fw-bold">Purpose</small><span
                                id="view_purpose" class="fw-medium text-dark"></span></div>
                        <div class="col-6"><small class="text-muted d-block fw-bold">Whom to Meet</small><span
                                id="view_meet" class="fw-medium text-dark"></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT VISITOR MODAL -->
    <div class="modal fade" id="editVisitorModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Edit Visitor Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="editVisitorForm">
                        <input type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="visitor_id" id="edit_visitor_id">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Company *</label>
                                <select name="company_id" id="edit_company_id" class="form-select select2" required
                                    style="width: 100%;"></select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Branch *</label>
                                <select name="branch_id" id="edit_branch_id" class="form-select select2" required
                                    style="width: 100%;"></select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold">Visiting Date *</label>
                                <input type="date" name="visiting_date" id="edit_visiting_date" class="form-control"
                                    required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold">Time In *</label>
                                <input type="time" name="time_in" id="edit_time_in" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold">Time Out</label>
                                <input type="time" name="time_out" id="edit_time_out" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold">Visitor Name *</label>
                                <input type="text" name="visitor_name" id="edit_visitor_name" class="form-control"
                                    required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold">No. of Visitors *</label>
                                <input type="number" name="no_of_visitors" id="edit_no_of_visitors"
                                    class="form-control" min="1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold">Mobile *</label>
                                <input type="tel" name="visitor_mobile" id="edit_visitor_mobile"
                                    class="form-control" maxlength="15" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Address</label>
                            <input type="text" name="visitor_address" id="edit_visitor_address" class="form-control">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Purpose</label>
                                <input type="text" name="purpose" id="edit_purpose" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Whom To Meet</label>
                                <input type="text" name="whom_to_meet" id="edit_whom_to_meet" class="form-control">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Update Photo (Optional)</label>
                            <input type="file" name="photo" id="editPhotoInput" class="form-control"
                                accept="image/*">
                            <input type="hidden" name="remove_photo" id="edit_remove_photo" value="0">

                            <div id="editPhotoPreviewContainer" class="mt-3 text-center" style="display: none;">
                                <img id="editPhotoPreview" src="" class="img-thumbnail shadow-sm"
                                    style="max-height: 100px; object-fit: cover;">
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-danger" id="btnEditRemovePhoto">
                                        <i class="fas fa-trash me-1"></i> Remove Image
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold" id="btnUpdateVisitor"
                            style="padding: 12px;">
                            <i class="fas fa-save me-1"></i> Update Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Directory Report & Analytics Modal -->
    <div class="modal fade" id="directoryReportModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="fas fa-chart-pie me-2"></i> Filtered Analytics Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="row mb-4">
                        <div class="col-6">
                            <div class="card border-0 shadow-sm text-center p-3">
                                <h3 class="text-primary fw-bold mb-0" id="statTotalEntries">0</h3>
                                <small class="text-muted fw-bold text-uppercase">Total Entries</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card border-0 shadow-sm text-center p-3">
                                <h3 class="text-info fw-bold mb-0" id="statTotalPax">0</h3>
                                <small class="text-muted fw-bold text-uppercase">Total Footfall (Pax)</small>
                            </div>
                        </div>
                    </div>
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Location-wise Breakdown</h6>
                    <div class="accordion shadow-sm" id="locationAccordion"></div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary fw-bold" id="btnPrintFromModal">
                        <i class="fas fa-print me-1"></i> Print Register
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Visitor History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white">
                    <h6 class="modal-title fw-bold"><i class="fas fa-history me-2"></i> Visit History: <span
                            id="historyVisitorName"></span></h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <ul class="list-group list-group-flush" id="historyList">
                        <li class="list-group-item text-center p-4 text-muted">Loading history...</li>
                    </ul>
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
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2();

            let userCompanyId = null;
            let userBranchId = null;
            let isMaster = window.userGodMode || false;
            let dtTable;
            let isMobileAppending = false; // Flag for Load More logic

            // Fetch Context & Populate Companies
            $.get('/api/v1/context', function(res) {
                userCompanyId = res.company_id;
                userBranchId = res.branch_id;
                if (res.is_god || res.is_developer || res.is_director) {
                    isMaster = true;
                }

                $.get('/api/v1/get-active-companies', function(compRes) {
                    let filterComp = $('#filter_company');
                    let editComp = $('#edit_company_id');

                    filterComp.empty().append('<option value="">All Companies</option>');
                    editComp.empty().append('<option value="">Select Company</option>');

                    compRes.data.forEach(c => {
                        filterComp.append(new Option(c.company_name, c.id));
                        editComp.append(new Option(c.company_name, c.id));
                    });

                    if (!isMaster && userCompanyId) {
                        filterComp.val(userCompanyId).trigger('change');
                        filterComp.prop('disabled', true);
                    } else {
                        filterComp.trigger('change');
                    }
                });
            });

            // Cascading Branches for Filter
            $('#filter_company').on('change', function() {
                let cid = $(this).val();
                let branchSelect = $('#filter_branch');
                let compName = $(this).find("option:selected").text();

                branchSelect.empty().append('<option value="">All Branches</option>');

                if (cid) {
                    branchSelect.append(`<option value="null">Head Office (${compName})</option>`);
                    $.get(`/api/v1/branches?company_id=${cid}&length=-1`, function(branchRes) {
                        if (branchRes && branchRes.data) {
                            branchRes.data.forEach(b => {
                                branchSelect.append(new Option(b.branch_name, b.id));
                            });
                        }
                        if (!isMaster && userBranchId) {
                            branchSelect.val(userBranchId).trigger('change');
                            branchSelect.prop('disabled', true);
                        }
                    });
                } else {
                    $.get(`/api/v1/branches?length=-1`, function(branchRes) {
                        if (branchRes && branchRes.data) {
                            branchRes.data.forEach(b => {
                                branchSelect.append(new Option(
                                    `${b.branch_name} (${b.company ? b.company.company_code : ''})`,
                                    b.id));
                            });
                        }
                    });
                }
            });

            // Cascading Branch for Edit Modal
            $('#edit_company_id').on('change', function() {
                let cid = $(this).val();
                let branchSelect = $('#edit_branch_id');
                let compName = $(this).find("option:selected").text();

                branchSelect.empty().append(`<option value="null">Head Office (${compName})</option>`);
                if (cid) {
                    $.get(`/api/v1/branches?company_id=${cid}&length=-1`, function(branchRes) {
                        if (branchRes && branchRes.data) {
                            branchRes.data.forEach(b => {
                                branchSelect.append(new Option(b.branch_name, b.id));
                            });
                        }
                    });
                }
            });

            // Server-Side DataTables Initialization
            dtTable = $('#directoryTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/visitors/directory',
                    type: 'GET',
                    data: function(d) {
                        d.company_id = $('#filter_company').val();
                        d.branch_id = $('#filter_branch').val();
                        d.date = $('#filter_date').val();
                        d.month = $('#filter_month').val();
                    }
                },
                pageLength: 10,
                order: [],
                columns: [{
                        data: 'id',
                        orderable: false,
                        render: function(data) {
                            return `<input type="checkbox" class="form-check-input row-checkbox" value="${data}" style="transform: scale(1.2);">`;
                        }
                    },
                    {
                        data: 'visiting_date',
                        render: function(data, type, row) {
                            let dateStr = new Date(row.visiting_date).toLocaleDateString('en-GB');
                            let timeIn = new Date(row.time_in).toLocaleTimeString([], {
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            let timeOutStr = row.time_out ? new Date(row.time_out)
                                .toLocaleTimeString([], {
                                    hour: '2-digit',
                                    minute: '2-digit'
                                }) : 'N/A';

                            return `<span class="badge bg-light text-dark border d-block mb-1"><i class="far fa-calendar-alt text-success"></i> ${dateStr}</span>
                                    <span class="badge bg-light text-dark border d-block mb-1" title="Time In"><i class="fas fa-sign-in-alt text-primary"></i> In: ${timeIn}</span>
                                    <span class="badge bg-light text-dark border" title="Time Out"><i class="fas fa-sign-out-alt text-danger"></i> Out: ${timeOutStr}</span>`;
                        }
                    },
                    {
                        data: 'photo',
                        orderable: false,
                        render: function(data) {
                            let img = data ? `/${data}` :
                                'https://ui-avatars.com/api/?name=Visitor&background=e9ecef&color=6c757d';
                            return `<img src="${img}" class="visitor-photo zoomable-photo" style="cursor: pointer;" onerror="this.src='https://ui-avatars.com/api/?name=Visitor&background=e9ecef&color=6c757d'">`;
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            let pax = data.no_of_visitors > 1 ?
                                `<span class="badge bg-info text-dark ms-1">${data.no_of_visitors} Pax</span>` :
                                '';
                            return `<strong>${data.visitor_name}</strong> ${pax}<br>
                            <small class="text-muted"><i class="fas fa-phone-alt" style="font-size:10px;"></i> ${data.visitor_mobile}</small><br>
                            <small class="text-muted">${data.visitor_address || '-'}</small>`;
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            return `<small class="d-block"><strong>Purpose:</strong> ${data.purpose || 'N/A'}</small>
                            <small class="d-block"><strong>Meet:</strong> ${data.whom_to_meet || 'N/A'}</small>`;
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            let cName = data.company ? data.company.company_code : 'Unknown';
                            let bName = data.branch ? data.branch.branch_name : 'H.O.';
                            return `<span class="badge bg-dark">${cName}</span> <span class="badge bg-secondary">${bName}</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data) {
                            return `
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-info btn-view secured-item" data-permission="visitors_dir_view" data-id="${data.id}" title="View"><i class="fas fa-eye"></i></button>
                        <button class="btn btn-sm btn-outline-primary btn-edit secured-item" data-permission="visitors_dir_edit" data-id="${data.id}" title="Edit"><i class="fas fa-edit"></i></button>
                    </div>`;
                        }
                    }
                ],
                drawCallback: function(settings) {
                    let api = this.api();
                    let data = api.rows({
                        page: 'current'
                    }).data().toArray();
                    let pageInfo = api.page.info();

                    renderMobileCards(data);

                    if (pageInfo.page < pageInfo.pages - 1) {
                        $('#btnLoadMoreCards').show();
                    } else {
                        $('#btnLoadMoreCards').hide();
                    }

                    updateBulkActionPanel();
                    if (typeof window.applyPermissions === 'function') window.applyPermissions();
                    isMobileAppending = false;
                }
            });

            function renderMobileCards(data) {
                let container = $('#mobileCardsContainer');
                if (!isMobileAppending) container.empty();

                if (data.length === 0 && !isMobileAppending) {
                    container.append(
                        '<div class="col-12 text-center text-muted p-4">No visitors found for the selected filters.</div>'
                    );
                    return;
                }

                $.each(data, function(i, v) {
                    let img = v.photo ? `/${v.photo}` :
                        'https://ui-avatars.com/api/?name=Visitor&background=e9ecef&color=6c757d';
                    let dateStr = new Date(v.visiting_date).toLocaleDateString('en-GB');
                    let timeIn = new Date(v.time_in).toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    let timeOut = v.time_out ? new Date(v.time_out).toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    }) : 'N/A';
                    let cName = v.company ? v.company.company_code : 'Unknown';
                    let bName = v.branch ? v.branch.branch_name : 'H.O.';
                    let pax = v.no_of_visitors > 1 ?
                        `<span class="badge bg-info text-dark ms-1">${v.no_of_visitors} Pax</span>` : '';

                    let card = `
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-3">
                            <input type="checkbox" class="form-check-input card-checkbox row-checkbox" value="${v.id}">
                            <img src="${img}" class="mobile-card-photo zoomable-photo" style="cursor: pointer;">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h6 class="mb-0 fw-bold">${v.visitor_name} ${pax}</h6>
                                    <span class="badge bg-light text-dark border" style="font-size:10px;">${dateStr}</span>
                                </div>
                                <div class="small text-muted mb-1"><i class="fas fa-phone-alt" style="font-size:10px;"></i> ${v.visitor_mobile}</div>
                                <div class="small">
                                    <span class="badge bg-dark" style="font-size:9px;">${cName}</span> 
                                    <span class="badge bg-secondary" style="font-size:9px;">${bName}</span>
                                </div>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-end small text-muted">
                            <div>
                                <span class="d-block text-primary"><i class="fas fa-sign-in-alt"></i> In: ${timeIn}</span>
                                <span class="d-block text-danger"><i class="fas fa-sign-out-alt"></i> Out: ${timeOut}</span>
                            </div>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-info btn-view secured-item" data-permission="visitors_dir_view" data-id="${v.id}"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-outline-primary btn-edit secured-item" data-permission="visitors_dir_edit" data-id="${v.id}"><i class="fas fa-edit"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
                    container.append(card);
                });
            }

            // Load More Button
            $('#btnLoadMoreCards').on('click', function() {
                let btn = $(this);
                btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Loading...');
                isMobileAppending = true;
                dtTable.page('next').draw('page');
                setTimeout(() => {
                    btn.html('<i class="fas fa-chevron-down me-1"></i> Load More');
                }, 500);
            });

            // Photo Preview & Zoom Logic
            $('#editPhotoInput').on('change', function(e) {
                let file = e.target.files[0];
                if (file) {
                    let reader = new FileReader();
                    reader.onload = function(e) {
                        $('#editPhotoPreview').attr('src', e.target.result);
                        $('#editPhotoPreviewContainer').slideDown();
                        $('#edit_remove_photo').val('0');
                    }
                    reader.readAsDataURL(file);
                }
            });

            $('#btnEditRemovePhoto').on('click', function() {
                $('#editPhotoInput').val('');
                $('#editPhotoPreview').attr('src', '');
                $('#editPhotoPreviewContainer').slideUp();
                $('#edit_remove_photo').val('1');
            });

            $(document).on('click', '.zoomable-photo', function() {
                let src = $(this).attr('src');
                if (!src.includes('ui-avatars')) {
                    $('#zoomedImage').attr('src', src);
                    $('#imageZoomModal').modal('show');
                }
            });

            // Helper to get row data
            function getVisitorData(id) {
                let allData = dtTable.ajax.json().data;
                return allData.find(v => v.id == id);
            }

            // View Profile
            $(document).on('click', '.btn-view', function() {
                let id = $(this).data('id');
                let v = getVisitorData(id);
                if (v) {
                    let img = v.photo ? `/${v.photo}` :
                        'https://ui-avatars.com/api/?name=Visitor&background=e9ecef&color=6c757d';
                    let dateStr = new Date(v.visiting_date).toLocaleDateString('en-GB');
                    let timeIn = new Date(v.time_in).toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    let timeOut = v.time_out ? new Date(v.time_out).toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    }) : 'N/A';
                    let cName = v.company ? v.company.company_code : 'Unknown';
                    let bName = v.branch ? v.branch.branch_name : 'H.O.';

                    $('#view_photo').attr('src', img);
                    $('#view_name').text(v.visitor_name);
                    $('#view_pax').text(v.no_of_visitors + ' Pax');
                    $('#view_mobile').text(v.visitor_mobile);

                    $('#view_visiting_date').html(`<i class="far fa-calendar-alt"></i> ${dateStr}`);
                    $('#view_time_in').html(`<i class="fas fa-sign-in-alt"></i> ${timeIn}`);
                    $('#view_time_out').html(`<i class="fas fa-sign-out-alt"></i> ${timeOut}`);

                    $('#view_company_branch').html(
                        `<span class="badge bg-dark">${cName}</span> <span class="badge bg-secondary">${bName}</span>`
                    );
                    $('#view_address').text(v.visitor_address || 'Not Provided');
                    $('#view_purpose').text(v.purpose || 'Not Provided');
                    $('#view_meet').text(v.whom_to_meet || 'Not Provided');

                    $('#viewVisitorModal').modal('show');
                }
            });

            // Edit Profile
            $(document).on('click', '.btn-edit', function() {
                let id = $(this).data('id');
                let v = getVisitorData(id);
                if (v) {
                    $('#edit_visitor_id').val(v.id);
                    $('#edit_company_id').val(v.company_id).trigger('change');

                    setTimeout(() => {
                        $('#edit_branch_id').val(v.branch_id === null ? 'null' : v.branch_id)
                            .trigger('change');
                    }, 500);

                    $('#edit_visiting_date').val(v.visiting_date);
                    let tIn = new Date(v.time_in);
                    $('#edit_time_in').val(String(tIn.getHours()).padStart(2, '0') + ':' + String(tIn
                        .getMinutes()).padStart(2, '0'));

                    if (v.time_out) {
                        let tOut = new Date(v.time_out);
                        $('#edit_time_out').val(String(tOut.getHours()).padStart(2, '0') + ':' + String(tOut
                            .getMinutes()).padStart(2, '0'));
                    } else {
                        $('#edit_time_out').val('');
                    }

                    $('#edit_visitor_name').val(v.visitor_name);
                    $('#edit_no_of_visitors').val(v.no_of_visitors);
                    $('#edit_visitor_mobile').val(v.visitor_mobile);
                    $('#edit_visitor_address').val(v.visitor_address);
                    $('#edit_purpose').val(v.purpose);
                    $('#edit_whom_to_meet').val(v.whom_to_meet);

                    $('#editPhotoInput').val('');
                    $('#edit_remove_photo').val('0');
                    if (v.photo) {
                        $('#editPhotoPreview').attr('src', `/${v.photo}`);
                        $('#editPhotoPreviewContainer').show();
                    } else {
                        $('#editPhotoPreviewContainer').hide();
                    }

                    if (!isMaster) {
                        $('#edit_company_id, #edit_branch_id').prop('disabled', true);
                    }

                    $('#editVisitorModal').modal('show');
                }
            });

            // Submit Edit
            $('#editVisitorForm').on('submit', function(e) {
                e.preventDefault();
                let id = $('#edit_visitor_id').val();
                let btn = $('#btnUpdateVisitor');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Updating...');

                $('#edit_company_id, #edit_branch_id').prop('disabled', false);
                let formData = new FormData(this);

                if (!isMaster) {
                    $('#edit_company_id, #edit_branch_id').prop('disabled', true);
                }

                $.ajax({
                    url: `/api/v1/visitors/${id}`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        Swal.fire('Success', res.message, 'success');
                        $('#editVisitorModal').modal('hide');
                        dtTable.ajax.reload(null, false);
                    },
                    error: function(err) {
                        let msg = err.responseJSON ? err.responseJSON.message : 'Error';
                        Swal.fire('Error', msg, 'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fas fa-save me-1"></i> Update Changes');
                    }
                });
            });

            // Filters
            $('#btnApplyFilters').on('click', function() {
                dtTable.ajax.reload();
            });
            $('#btnClearFilters').on('click', function() {
                if (isMaster) {
                    $('#filter_company').val('').trigger('change');
                }
                $('#filter_date').val('');
                $('#filter_month').val('');
                dtTable.ajax.reload();
            });

            // Bulk Actions Setup
            $(document).on('change', '.row-checkbox', function() {
                let isChecked = $(this).prop('checked');
                $(`.row-checkbox[value="${$(this).val()}"]`).prop('checked', isChecked);
                updateBulkActionPanel();
            });

            $('.master-checkbox').on('change', function() {
                $('.row-checkbox').prop('checked', $(this).prop('checked'));
                updateBulkActionPanel();
            });

            $('#btnSelectAll').on('click', function() {
                $('.row-checkbox, .master-checkbox').prop('checked', true);
                updateBulkActionPanel();
            });

            $('#bDeselectAll').on('click', function() {
                $('.row-checkbox, .master-checkbox').prop('checked', false);
                updateBulkActionPanel();
            });

            function updateBulkActionPanel() {
                let selected = getSelectedIds();
                if (selected.length > 0) {
                    $('#selectedCount').text(`${selected.length} Selected`);
                    $('#bulkActionsPanel').slideDown(200);
                } else {
                    $('#bulkActionsPanel').slideUp(200);
                    $('.master-checkbox').prop('checked', false);
                }
            }

            function getSelectedIds() {
                let ids = [];
                $('.row-checkbox:checked').each(function() {
                    if ($.inArray($(this).val(), ids) === -1) ids.push($(this).val());
                });
                return ids;
            }

            // Add To General Leads
            $('#btnBulkAddLead').on('click', function() {
                let ids = getSelectedIds();
                let btn = $(this);
                Swal.fire({
                    title: 'Add to General Leads?',
                    text: `You are moving ${ids.length} visitor(s) to interested customers table.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Proceed'
                }).then((result) => {
                    if (result.isConfirmed) {
                        btn.prop('disabled', true).html(
                            '<i class="fas fa-spinner fa-spin me-1"></i> Processing...');
                        $.ajax({
                            url: '/api/v1/visitors/add-general-lead',
                            type: 'POST',
                            data: {
                                visitor_ids: ids
                            },
                            success: function(res) {
                                Swal.fire('Success!', res.message, 'success');
                                $('#bDeselectAll').click();
                            },
                            complete: function() {
                                btn.prop('disabled', false).html(
                                    '<i class="fas fa-user-plus me-1"></i> Add to General Leads'
                                );
                            }
                        });
                    }
                });
            });

            // Bulk Delete Action
            $('#btnBulkDelete').on('click', function() {
                let ids = getSelectedIds();
                Swal.fire({
                    title: 'Are you sure?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/api/v1/visitors/bulk-delete',
                            type: 'POST',
                            data: {
                                ids: ids
                            },
                            success: function(res) {
                                Swal.fire('Deleted!', res.message, 'success');
                                dtTable.ajax.reload(null, false);
                                $('#bulkActionsPanel').slideUp();
                            }
                        });
                    }
                });
            });

            // Generate Report & Analytics
            $('#btnGenerateReport').on('click', function() {
                let directoryData = dtTable.ajax.json().data;
                let totalEntries = directoryData.length;
                let totalPax = 0;
                let locationGroups = {};

                directoryData.forEach(v => {
                    let pax = parseInt(v.no_of_visitors) || 1;
                    totalPax += pax;
                    let loc = v.visitor_address ? v.visitor_address.trim().toUpperCase() :
                        'UNKNOWN LOCATION';
                    if (!locationGroups[loc]) locationGroups[loc] = [];
                    locationGroups[loc].push(v);
                });

                $('#statTotalEntries').text(totalEntries);
                $('#statTotalPax').text(totalPax);

                let accordionHtml = '';
                let index = 0;

                for (let [location, visitors] of Object.entries(locationGroups)) {
                    let locPaxCount = visitors.reduce((sum, v) => sum + (parseInt(v.no_of_visitors) || 1),
                        0);
                    let headingId = `heading${index}`;
                    let collapseId = `collapse${index}`;

                    let visitorListHtml = visitors.map(v => {
                        let dateStr = new Date(v.visiting_date).toLocaleDateString('en-GB');
                        let timeIn = new Date(v.time_in).toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        let timeOut = v.time_out ? new Date(v.time_out).toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        }) : 'N/A';

                        return `
                <div class="d-flex justify-content-between align-items-center p-2 border-bottom hover-bg-light">
                    <div>
                        <strong>${v.visitor_name}</strong> 
                        ${v.no_of_visitors > 1 ? `<span class="badge bg-secondary ms-1">${v.no_of_visitors} Pax</span>` : ''}
                        <br><small class="text-muted">
                        <i class="far fa-calendar-alt text-success"></i> ${dateStr} | 
                        <i class="fas fa-sign-in-alt text-primary"></i> ${timeIn} - <i class="fas fa-sign-out-alt text-danger"></i> ${timeOut} | 
                        <i class="fas fa-phone-alt"></i> ${v.visitor_mobile} | Meet: ${v.whom_to_meet || '-'}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary rounded-pill btn-view-history" data-mobile="${v.visitor_mobile}" data-name="${v.visitor_name}">
                        History <i class="fas fa-arrow-right ms-1"></i>
                    </button>
                </div>
            `
                    }).join('');

                    accordionHtml += `
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="${headingId}">
                    <button class="accordion-button collapsed bg-white fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}">
                        <i class="fas fa-map-marker-alt text-danger me-2"></i> ${location} 
                        <span class="badge bg-primary ms-auto me-2">${visitors.length} Entries (${locPaxCount} Pax)</span>
                    </button>
                </h2>
                <div id="${collapseId}" class="accordion-collapse collapse" data-bs-parent="#locationAccordion">
                    <div class="accordion-body p-0 bg-white">${visitorListHtml}</div>
                </div>
            </div>`;
                    index++;
                }

                if (Object.keys(locationGroups).length === 0) {
                    accordionHtml = '<div class="text-center p-4 text-muted">No visitors found.</div>';
                }

                $('#locationAccordion').html(accordionHtml);
                $('#directoryReportModal').modal('show');
            });

            // History Modal
            $(document).on('click', '.btn-view-history', function() {
                let mobile = $(this).data('mobile');
                let name = $(this).data('name');

                $('#historyVisitorName').text(name);
                $('#historyList').html(
                    '<li class="list-group-item text-center p-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i> Fetching records...</li>'
                );
                $('#historyModal').modal('show');

                $.get(`/api/v1/visitors/history?mobile=${mobile}`, function(res) {
                    let html = '';
                    if (res.data.length > 0) {
                        res.data.forEach(h => {
                            let dateStr = new Date(h.visiting_date).toLocaleDateString(
                                'en-GB');
                            let timeIn = new Date(h.time_in).toLocaleTimeString([], {
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            let timeOut = h.time_out ? new Date(h.time_out)
                                .toLocaleTimeString([], {
                                    hour: '2-digit',
                                    minute: '2-digit'
                                }) : 'N/A';
                            let cName = h.company ? h.company.company_code : 'Unknown';
                            let bName = h.branch ? h.branch.branch_name : 'H.O.';

                            html += `
                    <li class="list-group-item p-3">
                        <div class="d-flex justify-content-between mb-1">
                            <strong><i class="far fa-calendar-alt text-success me-1"></i> ${dateStr} <i class="fas fa-sign-in-alt text-primary ms-2"></i> ${timeIn} - <i class="fas fa-sign-out-alt text-danger"></i> ${timeOut}</strong>
                            <span class="badge bg-dark">${cName} - ${bName}</span>
                        </div>
                        <div class="small text-muted">
                            <strong>Purpose:</strong> ${h.purpose || '-'} | <strong>Met:</strong> ${h.whom_to_meet || '-'}
                        </div>
                    </li>`;
                        });
                    } else {
                        html =
                            '<li class="list-group-item text-center p-4 text-muted">No history found.</li>';
                    }
                    $('#historyList').html(html);
                });
            });

            // Print & Export
            $('#btnPrintFromModal').on('click', function() {
                let token = localStorage.getItem('admin_token') || localStorage.getItem('emp_token') ||
                    localStorage.getItem('member_token') || '';
                let params = $.param({
                    time_scope: 'all_time',
                    company_id: $('#filter_company').val(),
                    branch_id: $('#filter_branch').val(),
                    date: $('#filter_date').val(),
                    month: $('#filter_month').val(),
                    token: token
                });
                window.open('/visitors/print?' + params, '_blank');
            });

            $('#btnExportExcel').on('click', function() {
                let token = localStorage.getItem('admin_token') || localStorage.getItem('emp_token') ||
                    localStorage.getItem('member_token') || '';
                let params = $.param({
                    company_id: $('#filter_company').val(),
                    branch_id: $('#filter_branch').val(),
                    date: $('#filter_date').val(),
                    month: $('#filter_month').val(),
                    token: token
                });
                window.location.href = '/api/v1/visitors/export?' + params;
            });
        });
    </script>
@endpush
