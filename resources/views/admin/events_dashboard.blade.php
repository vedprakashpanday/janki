@extends('layout.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h4 class="mb-0 fw-bold text-dark"><i class="fas fa-calendar-alt text-primary me-2"></i> <span
                    id="dashboardTitle">Today's Events</span></h4>

            <div class="mt-3 mt-md-0">
                <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" onclick="loadEvents('today')">
                    <i class="fas fa-calendar-day me-1"></i> Today
                </button>
                <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 ms-2" type="button"
                    data-bs-toggle="collapse" data-bs-target="#filterSection">
                    <i class="fas fa-filter me-1"></i> Advanced Filters
                </button>
            </div>
        </div>

        <!-- Collapsible Filter Section -->
       <!-- Collapsible Filter Section -->
    <div class="collapse mb-4" id="filterSection">
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-body">
                <div class="row g-4">
                    
                    <!-- Date Range Filter -->
                    <div class="col-md-6 col-xl-5">
                        <label class="fw-bold text-primary mb-2"><i class="fas fa-calendar-week me-2"></i>Date Range Filter</label>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <input type="date" id="startDate" class="form-control form-control-sm" style="flex: 1 1 110px;">
                            <span class="text-muted small">to</span>
                            <input type="date" id="endDate" class="form-control form-control-sm" style="flex: 1 1 110px;">
                            <button class="btn btn-primary btn-sm px-4 flex-grow-1 flex-md-grow-0 mt-1 mt-sm-0" onclick="applyDateFilter()">Apply</button>
                        </div>
                    </div>
                    
                    <!-- Vertical Divider (Sirf Desktop par dikhega) -->
                    <div class="col-xl-1 d-none d-xl-flex justify-content-center border-end" style="min-height: 40px;"></div>
                    
                    <!-- Month Range Filter -->
                    <div class="col-md-6 col-xl-5 ps-xl-4">
                        <label class="fw-bold text-success mb-2"><i class="fas fa-calendar-alt me-2"></i>Month Range Filter</label>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <input type="month" id="startMonth" class="form-control form-control-sm" style="flex: 1 1 110px;">
                            <span class="text-muted small">to</span>
                            <input type="month" id="endMonth" class="form-control form-control-sm" style="flex: 1 1 110px;">
                            <button class="btn btn-success btn-sm px-4 flex-grow-1 flex-md-grow-0 mt-1 mt-sm-0" onclick="applyMonthFilter()">Apply</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

        <!-- Desktop View: Table -->
        <div class="card border-0 shadow-sm rounded-4 d-none d-md-block">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0" id="eventsTable">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="py-3">ID</th>
                            <th class="py-3">Name</th>
                            <th class="py-3">Type</th>
                            <th class="py-3">Occasion</th>
                            <th class="py-3">Company Name</th>
                            <th class="px-4 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="desktopTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4">Loading events...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile View: Cards -->
        <div class="d-block d-md-none" id="mobileCardsContainer">
            <div class="text-center py-4">Loading events...</div>
        </div>
    </div>

    <!-- 👁️ VIEW MODAL 👁️ -->
    <div class="modal fade" id="eventViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-circle text-primary me-2"></i> Person Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <h3 class="fw-bold text-dark mb-1" id="modalName"></h3>
                    <span id="modalType" class="badge bg-secondary mb-3 fs-6"></span>

                    <div class="p-3 bg-light rounded-3 text-start">
                        <p class="mb-2"><strong>ID:</strong> <span id="modalProfileId" class="text-dark fw-bold"></span>
                        </p>
                        <p class="mb-2"><strong>Occasion:</strong> <span id="modalOccasion"></span></p>
                        <p class="mb-2"><strong>Event Date:</strong> <span id="modalDate"
                                class="text-primary fw-bold"></span></p>
                        <p class="mb-0"><strong>Company:</strong> <span id="modalCompany" class="text-muted"></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function getOccasionBadge(occasion) {
            if (occasion === 'Date of Birth')
            return '<span class="badge bg-warning text-dark"><i class="fas fa-birthday-cake me-1"></i> Date of Birth</span>';
            if (occasion === 'Anniversary')
            return '<span class="badge bg-danger"><i class="fas fa-rings me-1"></i> Anniversary</span>';
            if (occasion === 'Work Anniversary')
            return '<span class="badge bg-success"><i class="fas fa-briefcase me-1"></i> Work Anniversary</span>';
            return occasion;
        }

        window.viewEventDetails = function(name, type, occasion, date, company, profileId) {
            $('#modalName').text(name);
            $('#modalType').text(type);
            $('#modalOccasion').html(getOccasionBadge(occasion));
            $('#modalDate').text(date);
            $('#modalCompany').text(company);
            $('#modalProfileId').text(profileId);

            var modal = new bootstrap.Modal(document.getElementById('eventViewModal'));
            modal.show();
        }

        window.loadEvents = function(filterType = 'today', params = {}) {
            $('#desktopTableBody').html(
                '<tr><td colspan="7" class="text-center py-4"><i class="fas fa-spinner fa-spin text-primary me-2"></i> Fetching details...</td></tr>'
                );
            $('#mobileCardsContainer').html(
                '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-primary me-2"></i> Fetching details...</div>'
                );

            let url = `/api/v1/events-dashboard?filter_type=${filterType}`;
            if (params.start_date) url += `&start_date=${params.start_date}&end_date=${params.end_date}`;
            if (params.start_month) url += `&start_month=${params.start_month}&end_month=${params.end_month}`;

            $.ajax({
                url: url,
                type: 'GET',
                success: function(res) {
                    if (res.success) {
                        $('#dashboardTitle').text(res.display_title);

                        let tableHtml = '';
                        let cardsHtml = '';

                        if (res.data.length === 0) {
                            tableHtml =
                                '<tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-box-open fa-2x mb-2 opacity-50 d-block"></i> No events found for the selected period</td></tr>';
                            cardsHtml =
                                '<div class="alert alert-light text-center border py-4 text-muted"><i class="fas fa-box-open fa-2x mb-2 opacity-50 d-block"></i>No events found</div>';
                        } else {
                            res.data.forEach(function(event) {
                                let badge = getOccasionBadge(event.occasion);
                                let safeName = event.name.replace(/'/g, "\\'");
                                let safeCompany = event.company_name.replace(/'/g, "\\'");

                                let viewBtn =
                                    `<button class="btn btn-sm btn-light border shadow-sm rounded-pill px-3" onclick="viewEventDetails('${safeName}', '${event.type}', '${event.occasion}', '${event.formatted_date}', '${safeCompany}', '${event.profile_id}')"><i class="fas fa-eye text-primary"></i> View</button>`;

                                // Desktop
                                tableHtml += `
                            <tr>
                                <td class="px-4 fw-bold text-primary">${event.formatted_date}</td>
                                <td><span class="fw-bold text-dark">${event.profile_id}</span></td>
                                <td class="fw-medium">${event.name}</td>
                                <td><span class="badge bg-secondary">${event.type}</span></td>
                                <td>${badge}</td>
                                <td class="text-muted small">${event.company_name}</td>
                                <td class="px-4 text-end">${viewBtn}</td>
                            </tr>
                        `;

                                // Mobile
                                cardsHtml += `
                            <div class="card border-0 shadow-sm rounded-4 mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="fw-bold fs-5 text-dark">${event.name}</div>
                                        <div class="fw-bold text-primary">${event.formatted_date}</div>
                                    </div>
                                    <div class="text-muted small mb-2 fw-bold"><i class="fas fa-id-badge me-1"></i> ID: ${event.profile_id}</div>
                                    <div class="mb-2">${badge} <span class="badge bg-secondary ms-1">${event.type}</span></div>
                                    <div class="text-muted small mb-3"><i class="fas fa-building me-1"></i> ${event.company_name}</div>
                                    <button class="btn btn-light border w-100 rounded-3" onclick="viewEventDetails('${safeName}', '${event.type}', '${event.occasion}', '${event.formatted_date}', '${safeCompany}', '${event.profile_id}')"><i class="fas fa-eye text-primary me-2"></i>View Details</button>
                                </div>
                            </div>
                        `;
                            });
                        }
                        $('#desktopTableBody').html(tableHtml);
                        $('#mobileCardsContainer').html(cardsHtml);
                    }
                }
            });
        }

        window.applyDateFilter = function() {
            let start = $('#startDate').val();
            let end = $('#endDate').val();
            if (!start || !end) return Swal.fire('Warning', 'Please select both start and end dates', 'warning');
            loadEvents('date_range', {
                start_date: start,
                end_date: end
            });
        }

        window.applyMonthFilter = function() {
            let start = $('#startMonth').val();
            let end = $('#endMonth').val();
            if (!start || !end) return Swal.fire('Warning', 'Please select both start and end months', 'warning');
            loadEvents('month_range', {
                start_month: start,
                end_month: end
            });
        }

        $(document).ready(function() {
            // Initial Load - Sirf aaj ke events
            loadEvents('today');
        });
    </script>
@endpush
