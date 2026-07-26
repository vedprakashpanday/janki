@extends('layout.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        .badge-primary {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-secondary {
            background: #fef9c3;
            color: #854d0e;
        }

        .badge-other {
            background: #fee2e2;
            color: #991b1b;
        }

        /* 🔥 MOBILE CARD VIEW CSS 🔥 */
        @media (max-width: 767.98px) {
            .device-table thead {
                display: none;
            }

            .device-table tbody tr {
                display: block;
                margin-bottom: 15px;
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 10px;
            }

            .device-table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid #f1f5f9;
                padding: 8px 5px;
                text-align: right;
            }

            .device-table tbody td::before {
                content: attr(data-label);
                font-weight: bold;
                color: #475569;
                margin-right: 15px;
                text-align: left;
            }

            .device-table tbody td:last-child {
                border-bottom: none;
                justify-content: flex-end;
            }
        }

        .member-header {
            cursor: pointer;
            transition: 0.3s;
        }

        .member-header:hover {
            background-color: #f8fafc;
        }

        .accordion-button:not(.collapsed) {
            background-color: #e2e8f0;
            color: #000;
            box-shadow: none;
        }

        .select2-container {
            width: 100% !important;
        }
    </style>

    <div class="container-fluid mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0 text-primary"><i class="fas fa-shield-alt me-2"></i> Access & Device Control</h4>

            <div>
                <button class="btn btn-success btn-sm me-2" onclick="$('.buttons-excel').click()"><i
                        class="fas fa-file-excel"></i> Export Excel</button>
                <button class="btn btn-primary btn-sm" onclick="$('.buttons-print').click()"><i class="fas fa-print"></i>
                    Print</button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="small text-muted fw-bold">Company</label>
                    <select id="filterCompany" class="form-control select2" multiple="multiple"
                        data-placeholder="Select Companies"></select>
                </div>
                <div class="col-md-3">
                    <label class="small text-muted fw-bold">Branch</label>
                    <select id="filterBranch" class="form-control select2" multiple="multiple"
                        data-placeholder="Select Branches"></select>
                </div>
                <div class="col-md-4">
                    <label class="small text-muted fw-bold">Department (Only Associates)</label>
                    <select id="filterDepartment" class="form-control select2" multiple="multiple"
                        data-placeholder="Select Departments"></select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100 fw-bold" onclick="loadData()"><i class="fas fa-search me-1"></i>
                        Track</button>
                </div>
            </div>
        </div>

        <div class="accordion" id="deviceAccordion">
        </div>

        <div class="d-none">
            <table id="hiddenExportTable" class="table">
                <thead>
                    <tr>
                        <th>Member ID</th>
                        <th>Member Name</th>
                        <th>Company & Branch</th>
                        <th>Device Code</th>
                        <th>Type</th>
                        <th>System Info</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="hiddenExportBody"></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="logsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Device GPS & IP Logs</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body table-responsive">
                    <table class="table table-bordered table-sm small">
                        <thead class="bg-light">
                            <tr>
                                <th>Date/Time</th>
                                <th>IP Address</th>
                                <th>Location (Lat/Lng)</th>
                                <th>Attempt Status</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

    <script>
        let exportTable;

        $(document).ready(function() {
            $('.select2').select2({
                theme: "classic"
            });
            loadFilterData();
            loadData();
        });

        function loadFilterData() {
            $.get('/api/v1/companies/active', function(res) {
                if (res.status === 'success') {
                    res.data.forEach(c => $('#filterCompany').append(new Option(c.company_name, c.id)));
                }
            });
        }

        $('#filterCompany').on('change', function() {
            let compIds = $(this).val();
            $('#filterBranch').empty().append('<option value="HO">Head Office (HO)</option>');
            $('#filterDepartment').empty();

            if (compIds && compIds.length > 0) {
                compIds.forEach(cId => {
                    $.get(`/api/v1/dropdown/branches?company_id=${cId}`, function(res) {
                        res.data.forEach(b => $('#filterBranch').append(new Option(b.branch_name, b
                            .id)));
                    });
                    $.get(`/api/v1/dropdown/departments?company_id=${cId}`, function(res) {
                        res.data.forEach(d => {
                            if (d.department_name.toLowerCase().includes('associate')) {
                                if ($('#filterDepartment').find(`option[value="${d.id}"]`)
                                    .length === 0) {
                                    $('#filterDepartment').append(new Option(d
                                        .department_name, d.id));
                                }
                            }
                        });
                    });
                });
            }
        });

        window.loadData = function() {
            let data = {
                company_ids: $('#filterCompany').val(),
                branch_ids: $('#filterBranch').val(),
                department_ids: $('#filterDepartment').val()
            };

            $('#deviceAccordion').html(
                '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i> Loading Data...</div>');

            $.get('/api/v1/member-devices', data, function(res) {
                let accordionHtml = '';
                let exportBodyHtml = '';

                if (res.data.length === 0) {
                    $('#deviceAccordion').html(
                        '<div class="alert alert-warning text-center">No devices found.</div>');
                    if ($.fn.DataTable.isDataTable('#hiddenExportTable')) $('#hiddenExportTable').DataTable()
                        .clear().draw();
                    return;
                }

                res.data.forEach((group, index) => {
                    let deviceRows = '';

                    group.devices.forEach(d => {
                        let badgeClass = d.device_type === 'Primary' ? 'badge-primary' : (d
                            .device_type === 'Secondary' ? 'badge-secondary' : 'badge-other'
                            );
                        let statusBadge = d.status === 'active' ?
                            '<span class="badge bg-success">Active</span>' :
                            '<span class="badge bg-danger">Blocked</span>';

                        let actionBtns =
                            `<button class="btn btn-sm btn-info text-white me-1 mb-1" onclick="viewLogs(${d.id})"><i class="fas fa-map-marker-alt"></i> Logs</button>`;

                        if (d.status === 'blocked') {
                            actionBtns +=
                                `<button class="btn btn-sm btn-success me-1 mb-1" onclick="changeStatus(${d.id}, 'active')">Unblock</button>`;
                        } else {
                            actionBtns +=
                                `<button class="btn btn-sm btn-danger me-1 mb-1" onclick="changeStatus(${d.id}, 'blocked')">Block</button>`;
                        }

                        if (d.device_type === 'Primary') {
                            actionBtns +=
                                `<button class="btn btn-sm btn-warning mb-1" onclick="swapType(${d.id}, 'Secondary')">Make Secondary</button>`;
                        } else {
                            actionBtns +=
                                `<button class="btn btn-sm btn-primary mb-1" onclick="swapType(${d.id}, 'Primary')">Make Primary</button>`;
                        }

                        // For UI Accordion
                        deviceRows += `<tr>
                        <td data-label="Device Code" class="fw-bold text-primary">${d.device_code}</td>
                        <td data-label="Type"><span class="badge ${badgeClass}">${d.device_type}</span></td>
                        <td data-label="System Info"><small>${d.device_name}</small></td>
                        <td data-label="Status">${statusBadge}</td>
                        <td data-label="Action">${actionBtns}</td>
                    </tr>`;

                        // For Hidden Export Table
                        exportBodyHtml += `<tr>
                        <td>${group.member_id}</td>
                        <td>${group.member_name}</td>
                        <td>${group.company} - ${group.branch}</td>
                        <td>${d.device_code}</td>
                        <td>${d.device_type}</td>
                        <td>${d.device_name}</td>
                        <td>${d.status}</td>
                    </tr>`;
                    });

                    accordionHtml += `
                <div class="accordion-item mb-2 border rounded">
                    <h2 class="accordion-header" id="heading${index}">
                        <button class="accordion-button collapsed member-header" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${index}">
                            <div class="d-flex justify-content-between w-100 me-3">
                                <div><b class="text-dark">${group.member_name}</b> <span class="badge bg-secondary ms-2">${group.member_id}</span></div>
                                <div class="small text-muted d-none d-md-block">${group.company} | ${group.department}</div>
                                <div><span class="badge bg-info rounded-pill">${group.devices.length} Devices</span></div>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse${index}" class="accordion-collapse collapse" data-bs-parent="#deviceAccordion">
                        <div class="accordion-body bg-light p-2 p-md-3">
                            <table class="table device-table mb-0 w-100">
                                <thead class="table-dark"><tr><th>Code</th><th>Type</th><th>System Info</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>${deviceRows}</tbody>
                            </table>
                        </div>
                    </div>
                </div>`;
                });

                $('#deviceAccordion').html(accordionHtml);
                $('#hiddenExportBody').html(exportBodyHtml);

                // 🔥 MASTER TRICK: DataTables ko safely destroy karke wapas initialize karna hidden table par
                if ($.fn.DataTable.isDataTable('#hiddenExportTable')) {
                    $('#hiddenExportTable').DataTable().destroy();
                }

                exportTable = $('#hiddenExportTable').DataTable({
                    dom: 'B', // Only Buttons, No Search/Pagination Needed here
                    buttons: [{
                            extend: 'excelHtml5',
                            title: 'Member Device Tracking Report'
                        },
                        {
                            extend: 'print',
                            title: 'Member Device Tracking Report'
                        }
                    ]
                });
            });
        }

        // Action APIs
        window.changeStatus = function(id, status) {
            Swal.fire({
                title: 'Confirm',
                text: `Set device to ${status}?`,
                icon: 'warning',
                showCancelButton: true
            }).then(result => {
                if (result.isConfirmed) {
                    $.post(`/api/v1/member-devices/${id}/status`, {
                        status: status,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    }, function(res) {
                        Swal.fire('Updated', res.message, 'success');
                        loadData();
                    });
                }
            });
        }

        window.swapType = function(id, type) {
            Swal.fire({
                title: 'Change Authority',
                text: `Set this device as ${type}?`,
                icon: 'question',
                showCancelButton: true
            }).then(result => {
                if (result.isConfirmed) {
                    $.post(`/api/v1/member-devices/${id}/swap`, {
                        device_type: type,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    }, function(res) {
                        Swal.fire('Updated', res.message, 'success');
                        loadData();
                    });
                }
            });
        }

        window.viewLogs = function(id) {
            $.get(`/api/v1/member-devices/${id}/logs`, function(res) {
                let html = '';
                res.data.forEach(log => {
                    let mapLink = log.login_lat && log.login_lng ?
                        `<a href="http://googleusercontent.com/maps.google.com/?q=${log.login_lat},${log.login_lng}" target="_blank" class="text-primary"><i class="fas fa-map"></i> View Map</a>` :
                        'N/A';
                    let statColor = log.status === 'Success' ? 'text-success' : 'text-danger';
                    html +=
                        `<tr><td>${new Date(log.login_time).toLocaleString()}</td><td>${log.ip_address || 'N/A'}</td><td>${mapLink}</td><td class="fw-bold ${statColor}">${log.status}</td></tr>`;
                });
                if (!html) html = '<tr><td colspan="4" class="text-center">No logs found.</td></tr>';
                $('#logsTableBody').html(html);
                $('#logsModal').modal('show');
            });
        }
    </script>
@endpush
