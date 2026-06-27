@extends('layout.app')

@section('content')
    <style>
        /* Desktop Bulk Action Bar */
        .bulk-action-bar {
            background: #fff;
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        /* Mobile Floating Bulk Action Bar */
        @media (max-width: 767.98px) {
            .bulk-action-bar.mobile-floating {
                position: fixed;
                bottom: 80px;
                /* Aapke mobile bottom nav ke theek upar */
                left: 5%;
                right: 5%;
                z-index: 1050;
                box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.15);
                border-radius: 12px;
                margin-bottom: 0;
                border: 2px solid var(--brand-primary);
            }
        }

        .phase-card {
            border-left: 4px solid var(--brand-primary);
            transition: 0.2s;
        }

        .phase-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1) !important;
        }

        .data-row {
            transition: all 0.2s;
        }
    </style>

    <div class="container-fluid mt-4">
        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
            <div>
                <h4 class="mb-0 fw-bold text-primary"><i class="fas fa-building me-2"></i>Phases Management</h4>
                <p class="text-muted small mb-0">Manage telecalling phases, locations & details.</p>
            </div>

            <div class="d-flex w-100 w-md-auto gap-2">
                <div class="input-group input-group-sm flex-grow-1" style="max-width: 250px;">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="liveSearch" class="form-control border-start-0 shadow-none"
                        placeholder="Search phases...">
                </div>
                <button class="btn btn-sm btn-success shadow-sm" id="exportExcelBtn" title="Download Excel">
                    <i class="fas fa-file-excel"></i> <span class="d-none d-md-inline ms-1">Excel</span>
                </button>
                <a href="#" id="addPhaseBtn" class="btn btn-sm btn-primary shadow-sm text-nowrap">
                    <i class="fas fa-plus"></i> <span class="d-none d-md-inline ms-1">Add Phase</span>
                </a>
            </div>
        </div>

        <div id="bulkActionBar" class="bulk-action-bar mobile-floating d-none">
            <div class="fw-bold text-primary">
                <span id="selectedCount">0</span> Selected
            </div>
            <div>
                <button class="btn btn-sm btn-outline-secondary me-2" id="selectAllBtn">Select All</button>
                <button class="btn btn-sm btn-danger shadow-sm" id="deleteSelectedBtn">
                    <i class="fas fa-trash"></i> <span class="d-none d-sm-inline">Delete</span>
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-3 d-none d-md-block">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="phasesTable">
                        <thead class="bg-light text-secondary" style="font-size: 13px; text-transform: uppercase;">
                            <tr>
                                <th class="ps-4 py-3" style="width: 50px;">
                                    <input type="checkbox" class="form-check-input shadow-none" id="masterCheckbox">
                                </th>
                                <th>Phase Name</th>
                                <th>Company</th>
                                <th>Branch</th>
                                <th>Location</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="phasesTableBody" style="font-size: 14px;">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <div class="spinner-border spinner-border-sm me-2"></div> Loading...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="mobileCardsContainer" class="d-block d-md-none">
        </div>
    </div>

    <div class="modal fade" id="phaseDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold" id="modalPhaseName">Phase Details</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img id="modalPhaseImage" src="" class="img-fluid rounded shadow-sm d-none"
                            style="max-height: 200px;">
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><small class="text-muted d-block">Company</small><strong
                                id="modalComp"></strong></div>
                        <div class="col-6"><small class="text-muted d-block">Branch</small><strong
                                id="modalBranch"></strong></div>
                    </div>
                    <h6 class="fw-bold text-primary mb-1 mt-3">Location:</h6>
                    <p id="modalPhaseLocation" class="text-muted small"></p>
                    <h6 class="fw-bold text-primary mb-1">Description:</h6>
                    <p id="modalPhaseDesc" class="text-muted small" style="white-space: pre-wrap;"></p>

                    <a href="#" id="modalPhaseMap" target="_blank"
                        class="btn btn-outline-success btn-sm w-100 d-none mt-2">
                        <i class="fas fa-map-marker-alt me-1"></i> View on Google Maps
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let currentPortal = "{{ request()->segment(1) }}";
            $('#addPhaseBtn').attr('href', '/' + currentPortal + '/phases/create');

            let globalPhasesData = []; // Store data for Excel Export

            function loadPhases() {
                $.ajax({
                    url: '/api/v1/phases',
                    type: 'GET',
                    success: function(res) {
                        let tbody = $('#phasesTableBody');
                        let mobileContainer = $('#mobileCardsContainer');

                        tbody.empty();
                        mobileContainer.empty();

                        if (res.success && res.data.length > 0) {
                            globalPhasesData = res.data;

                            res.data.forEach((phase) => {
                                let companyName = phase.company ? phase.company.company_name :
                                    'N/A';
                                let branchName = phase.branch ? phase.branch.branch_name :
                                'N/A';
                                let imageUrl = phase.phase_image ? `/${phase.phase_image}` : '';

                                let safeDesc = (phase.phase_details || '').replace(/"/g,
                                    '&quot;');
                                let safeLoc = (phase.phase_location || '').replace(/"/g,
                                    '&quot;');
                                let safeMap = phase.phase_google_map_url || '';

                                // 1. Desktop Table Row
                                let tr = `
                                <tr class="data-row">
                                    <td class="ps-4">
                                        <input type="checkbox" class="form-check-input row-checkbox shadow-none" value="${phase.id}">
                                    </td>
                                    <td class="fw-medium text-dark search-target">${phase.phase_name}</td>
                                    <td class="search-target"><span class="badge bg-light text-dark border">${companyName}</span></td>
                                    <td class="search-target"><span class="badge bg-light text-dark border">${branchName}</span></td>
                                    <td class="search-target text-truncate" style="max-width: 150px;">${phase.phase_location}</td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-light text-primary view-details-btn" 
                                            data-name="${phase.phase_name}" data-loc="${safeLoc}" 
                                            data-desc="${safeDesc}" data-img="${imageUrl}" data-map="${safeMap}" 
                                            data-comp="${companyName}" data-branch="${branchName}">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <a href="/${currentPortal}/phases/${phase.id}/edit" class="btn btn-sm btn-light text-warning me-1" title="Edit Phase">
    <i class="fas fa-edit"></i> Edit
</a>
                                    </td>
                                </tr>
                            `;
                                tbody.append(tr);

                                // 2. Mobile Card
                                let card = `
                                <div class="card shadow-sm mb-3 phase-card data-row border-0">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input row-checkbox shadow-none me-2" value="${phase.id}" style="width: 18px; height: 18px;">
                                                <h6 class="mb-0 fw-bold text-dark search-target">${phase.phase_name}</h6>
                                            </div>
                                            <button class="btn btn-sm btn-light text-primary view-details-btn p-1 px-2" 
                                                data-name="${phase.phase_name}" data-loc="${safeLoc}" 
                                                data-desc="${safeDesc}" data-img="${imageUrl}" data-map="${safeMap}"
                                                data-comp="${companyName}" data-branch="${branchName}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="/${currentPortal}/phases/${phase.id}/edit" class="btn btn-sm btn-light text-warning p-1 px-2 me-1" title="Edit">
    <i class="fas fa-edit"></i>
</a>
                                        </div>
                                        <div class="small mb-1 search-target"><i class="fas fa-building text-muted me-1"></i> ${companyName} | ${branchName}</div>
                                        <div class="small text-muted search-target text-truncate"><i class="fas fa-map-marker-alt me-1"></i> ${phase.phase_location}</div>
                                    </div>
                                </div>
                            `;
                                mobileContainer.append(card);
                            });
                        } else {
                            tbody.html(
                                '<tr><td colspan="6" class="text-center py-4 text-muted">No phases found.</td></tr>'
                                );
                            mobileContainer.html(
                                '<div class="text-center py-4 text-muted bg-white rounded shadow-sm">No phases found.</div>'
                                );
                        }
                        updateBulkActionBar(); // Reset bar on load
                    }
                });
            }

            loadPhases();

            // --- CHECKBOX & BULK ACTION LOGIC ---

            // Master Checkbox (Desktop)
            $(document).on('change', '#masterCheckbox', function() {
                let isChecked = $(this).prop('checked');
                // Check only visible rows (helps if search is active)
                $('.data-row:visible .row-checkbox').prop('checked', isChecked);
                updateBulkActionBar();
            });

            // Individual Checkbox Click
            $(document).on('change', '.row-checkbox', function() {
                updateBulkActionBar();
            });

            // Select All Button (Mobile & Desktop)
            $('#selectAllBtn').on('click', function() {
                let allChecked = $('.data-row:visible .row-checkbox:checked').length === $(
                    '.data-row:visible .row-checkbox').length;
                $('.data-row:visible .row-checkbox').prop('checked', !allChecked); // Toggle
                $('#masterCheckbox').prop('checked', !allChecked);
                updateBulkActionBar();
            });

            function updateBulkActionBar() {
                let checkedCount = $('.data-row:visible .row-checkbox:checked').length;
                $('#selectedCount').text(checkedCount);

                if (checkedCount > 0) {
                    $('#bulkActionBar').removeClass('d-none');
                } else {
                    $('#bulkActionBar').addClass('d-none');
                    $('#masterCheckbox').prop('checked', false);
                }
            }

            // --- LIVE SEARCH LOGIC ---
            $('#liveSearch').on('keyup', function() {
                let value = $(this).val().toLowerCase();

                $('.data-row').each(function() {
                    let rowText = $(this).find('.search-target').text().toLowerCase();
                    if (rowText.indexOf(value) > -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                        $(this).find('.row-checkbox').prop('checked', false); // Uncheck hidden rows
                    }
                });
                updateBulkActionBar();
            });

            // --- EXPORT TO EXCEL (CSV) LOGIC ---
            $('#exportExcelBtn').on('click', function() {
                if (globalPhasesData.length === 0) {
                    Swal.fire('Empty', 'No data available to export', 'info');
                    return;
                }

                let csvContent = "data:text/csv;charset=utf-8,";
                csvContent += "Phase Name,Company,Branch,Location,Details\n"; // Headers

                // Only export visible rows based on search
                $('.data-row:visible').each(function() {
                    // Determine if it's desktop or mobile row to extract text properly
                    if ($(this).is('tr')) {
                        let pName = $(this).find('td:eq(1)').text().trim().replace(/,/g, " ");
                        let comp = $(this).find('td:eq(2)').text().trim().replace(/,/g, " ");
                        let branch = $(this).find('td:eq(3)').text().trim().replace(/,/g, " ");
                        let loc = $(this).find('td:eq(4)').text().trim().replace(/,/g, " ");
                        csvContent += `${pName},${comp},${branch},${loc},""\n`;
                    }
                });

                var encodedUri = encodeURI(csvContent);
                var link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "Phases_Export.csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });

            // --- VIEW MODAL LOGIC ---
            $(document).on('click', '.view-details-btn', function() {
                let btn = $(this);
                $('#modalPhaseName').text(btn.data('name'));
                $('#modalComp').text(btn.data('comp'));
                $('#modalBranch').text(btn.data('branch'));
                $('#modalPhaseLocation').text(btn.data('loc'));
                $('#modalPhaseDesc').text(btn.data('desc'));

                let img = btn.data('img');
                if (img) {
                    $('#modalPhaseImage').attr('src', img).removeClass('d-none');
                } else {
                    $('#modalPhaseImage').addClass('d-none');
                }

                let map = btn.data('map');
                if (map) {
                    $('#modalPhaseMap').attr('href', map).removeClass('d-none');
                } else {
                    $('#modalPhaseMap').addClass('d-none');
                }

                let modal = new bootstrap.Modal(document.getElementById('phaseDetailsModal'));
                modal.show();
            });

            // --- BULK DELETE LOGIC ---
            $('#deleteSelectedBtn').on('click', function() {
                let selectedIds = [];
                // Get unique selected IDs (since we have both table and card checkboxes in DOM)
                $('.data-row:visible .row-checkbox:checked').each(function() {
                    let val = $(this).val();
                    if (!selectedIds.includes(val)) selectedIds.push(val);
                });

                if (selectedIds.length === 0) return;

                Swal.fire({
                    title: 'Are you sure?',
                    text: `You are about to delete ${selectedIds.length} phases!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete them!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Start Loading
                        $('#deleteSelectedBtn').prop('disabled', true).html(
                            '<div class="spinner-border spinner-border-sm"></div>');

                        $.ajax({
                            url: '/api/v1/phases/bulk-delete', // Apko ye API route backend me banana hoga
                            type: 'POST',
                            data: {
                                ids: selectedIds
                            },
                            success: function(res) {
                                Swal.fire('Deleted!',
                                    'Selected phases have been deleted.', 'success');
                                loadPhases(); // Reload data
                                $('#deleteSelectedBtn').prop('disabled', false).html(
                                    '<i class="fas fa-trash"></i> <span class="d-none d-sm-inline">Delete</span>'
                                    );
                            },
                            error: function() {
                                Swal.fire('Error',
                                    'Failed to delete phases. Check backend API route.',
                                    'error');
                                $('#deleteSelectedBtn').prop('disabled', false).html(
                                    '<i class="fas fa-trash"></i> <span class="d-none d-sm-inline">Delete</span>'
                                    );
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
