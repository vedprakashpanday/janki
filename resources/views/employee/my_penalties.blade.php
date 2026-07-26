@extends('layout.app')
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
            <h4><i class="fas fa-file-invoice-dollar text-danger"></i> My Fines & Penalties</h4>
        </div>

        <div class="card mb-3 shadow-sm border-0">
            <div class="card-body bg-light p-3 rounded">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5"><input type="date" id="f_start_date" class="form-control" title="Start Date"></div>
                    <div class="col-md-5"><input type="date" id="f_end_date" class="form-control" title="End Date"></div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" id="btnFilter"><i class="fas fa-filter"></i> Apply
                            Filter</button>
                    </div>
                </div>

                <div class="alert alert-info d-none mb-0 mt-3" id="summaryContainer">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <h6 class="mb-0"><i class="fas fa-calculator me-2"></i> Filtered Total Summary</h6>
                        <button class="btn btn-sm btn-outline-dark" id="toggleSummaryBtn">
                            <i class="fas fa-chevron-down"></i> Expand Details
                        </button>
                    </div>

                    <div id="summaryDetailsWrapper" class="d-none">
                        <div id="summaryDetails" class="mb-2" style="max-height: 400px; overflow-y: auto;"></div>
                        <div class="text-center mt-2">
                            <button class="btn btn-sm btn-info text-white d-none" id="loadMoreSummaryBtn">
                                <i class="fas fa-sync-alt"></i> Load More (Next 20)
                            </button>
                        </div>
                    </div>

                    <div class="border-top pt-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Grand Total</h6>
                        <h5 class="mb-0 fw-bold text-danger" id="grandTotalUI">₹0.00</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive d-none d-md-block shadow-sm bg-white rounded">
            <table class="table table-hover table-bordered mb-0" id="myFineTable">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Record No</th>
                        <th>Fine (₹ / Days)</th>
                        <th>Penalty (₹ / Days)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>
                            Loading your records...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-block d-md-none" id="mobileCardsContainer">
            <div class="text-center p-3 text-muted"><i class="fas fa-spinner fa-spin me-2"></i> Loading your records...
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewFineModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-uppercase"><i class="fas fa-receipt text-secondary"></i>
                        Fine/Penalty Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewFineBody">
                    <div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Zoom Modal -->
    <div class="modal fade" id="imageZoomModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-header border-0 pb-0 justify-content-end">
                    <button type="button" class="btn-close btn-close-white fs-4 bg-dark" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="zoomedImage" src="" class="img-fluid rounded shadow-lg" style="max-height: 85vh;">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let token = window.location.pathname.includes('/employee') ? localStorage.getItem('emp_token') :
                localStorage.getItem('admin_token');

            // Auto Set Current Month Dates
            let date = new Date();
            let y = date.getFullYear();
            let m = String(date.getMonth() + 1).padStart(2, '0');
            let firstDay = `${y}-${m}-01`;
            let lastDayObj = new Date(y, date.getMonth() + 1, 0);
            let lastDay = `${y}-${m}-${String(lastDayObj.getDate()).padStart(2, '0')}`;

            $('#f_start_date').val(firstDay);
            $('#f_end_date').val(lastDay);

            window.summaryDataList = [];
            window.summaryCurrentIndex = 0;

            function renderSummaryChunk() {
                let chunkSize = 20;
                let html = '';
                let end = Math.min(window.summaryCurrentIndex + chunkSize, window.summaryDataList.length);

                for (let i = window.summaryCurrentIndex; i < end; i++) {
                    let item = window.summaryDataList[i];
                    html += `<div class="d-flex justify-content-between small text-dark mb-1 border-bottom pb-1">
                    <span><i class="fas fa-user-circle text-muted me-1"></i> ${item.name}</span> 
                    <span class="fw-medium">₹${item.amount.toFixed(2)}</span>
                </div>`;
                }

                $('#summaryDetails').append(html);
                window.summaryCurrentIndex = end;

                if (window.summaryCurrentIndex < window.summaryDataList.length) {
                    $('#loadMoreSummaryBtn').removeClass('d-none');
                } else {
                    $('#loadMoreSummaryBtn').addClass('d-none');
                }
            }

            $('#toggleSummaryBtn').click(function() {
                let wrapper = $('#summaryDetailsWrapper');
                if (wrapper.hasClass('d-none')) {
                    wrapper.removeClass('d-none');
                    $(this).html('<i class="fas fa-chevron-up"></i> Collapse Details');
                } else {
                    wrapper.addClass('d-none');
                    $(this).html('<i class="fas fa-chevron-down"></i> Expand Details');
                }
            });

            $('#loadMoreSummaryBtn').click(function() {
                renderSummaryChunk();
            });

            function loadMyData() {
                let filters = {
                    start_date: $('#f_start_date').val(),
                    end_date: $('#f_end_date').val(),
                    personal_only: 1
                };

                $.ajax({
                    url: '/api/v1/fine-penalties',
                    method: 'GET',
                    data: filters,
                    headers: {
                        'Authorization': 'Bearer ' + token
                    },
                    success: function(res) {
                        let dataArray = Array.isArray(res.data ? res.data : res) ? (res.data ? res
                            .data : res) : [];

                        if (dataArray.length === 0) {
                            $('#myFineTable tbody').html(
                                '<tr><td colspan="6" class="text-center text-muted py-4">No fines or penalties found for this period.</td></tr>'
                                );
                            $('#mobileCardsContainer').html(
                                '<div class="text-center p-4 text-muted bg-white rounded shadow-sm">No records found.</div>'
                                );
                            $('#summaryContainer').addClass('d-none');
                            $('#summaryDetails').empty();
                            return;
                        }

                        let tbody = '';
                        let cards = '';
                        let grandTotalRupees = 0;
                        let summaryMap = {};

                        dataArray.forEach((item) => {
                            let recordNo = 'FP-' + String(item.id).padStart(5, '0');
                            let fineText = item.fine_rupees ? `₹${item.fine_rupees}` : (item
                                .fine_days ? item.fine_days + ' Days' : '-');
                            let penaltyText = item.penalty_rupees ? `₹${item.penalty_rupees}` :
                                (item.penalty_days ? item.penalty_days + ' Days' : '-');
                            let statusBadge = item.status === 'Approved' ?
                                '<span class="badge bg-success">Approved</span>' : (item
                                    .status === 'Rejected' ?
                                    '<span class="badge bg-danger">Rejected</span>' :
                                    '<span class="badge bg-warning text-dark">Pending</span>');

                            let actions =
                                `<button class="btn btn-info btn-sm btn-view text-white" data-id="${item.id}" title="View Details"><i class="fas fa-eye"></i> View Details</button>`;

                            let baseFine = parseFloat(item.fine_rupees) || 0;
                            let basePenalty = parseFloat(item.penalty_rupees) || 0;
                            let fineDaysAmt = 0;
                            let penaltyDaysAmt = 0;

                            let currentSal = item.employee ? (item.employee.payable_salary ||
                                item.employee.payable_salary) : null;

                            if ((item.user_type === 'Employee' || !item.user_type) && item
                                .employee && currentSal) {
                                let perDay = parseFloat(currentSal) / 30;
                                fineDaysAmt = (parseFloat(item.fine_days) || 0) * perDay;
                                penaltyDaysAmt = (parseFloat(item.penalty_days) || 0) * perDay;
                            }

                            let rowTotal = baseFine + basePenalty + fineDaysAmt +
                            penaltyDaysAmt;
                            grandTotalRupees += rowTotal;

                            if (rowTotal > 0) {
                                let displayName = item.employee ? (item.employee.full_name ||
                                    item.employee.member_name) : 'N/A';
                                let displayId = item.employee ? item.employee.member_id : 'N/A';
                                let key =
                                    `${displayName} (${displayId}) - ${item.user_type || 'Employee'}`;

                                if (!summaryMap[key]) summaryMap[key] = 0;
                                summaryMap[key] += rowTotal;
                            }

                            // Desktop Table Rows
                            tbody += `<tr>
                            <td><strong>${item.date}</strong></td>
                            <td>${recordNo}</td>
                            <td class="text-danger fw-medium">${fineText}</td>
                            <td class="text-danger fw-medium">${penaltyText}</td>
                            <td>${statusBadge}</td>
                            <td>${actions}</td>
                        </tr>`;

                            // Mobile View Cards
                            cards += `
                        <div class="card mb-3 shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                    <strong><i class="fas fa-calendar-alt text-muted"></i> ${item.date}</strong>
                                    ${statusBadge}
                                </div>
                                <div class="d-flex justify-content-between small mb-2">
                                    <span class="text-muted">Record No:</span> <span class="fw-bold">${recordNo}</span>
                                </div>
                                <div class="d-flex justify-content-between small mb-2">
                                    <span class="text-muted">Fine:</span> <span class="text-danger fw-bold">${fineText}</span>
                                </div>
                                <div class="d-flex justify-content-between small mb-3">
                                    <span class="text-muted">Penalty:</span> <span class="text-danger fw-bold">${penaltyText}</span>
                                </div>
                                <div class="d-flex justify-content-end">${actions}</div>
                            </div>
                        </div>`;
                        });

                        $('#myFineTable tbody').html(tbody);
                        $('#mobileCardsContainer').html(cards);

                        window.summaryDataList = [];
                        for (let key in summaryMap) {
                            window.summaryDataList.push({
                                name: key,
                                amount: summaryMap[key]
                            });
                        }

                        if (window.summaryDataList.length > 0 && grandTotalRupees > 0) {
                            $('#summaryContainer').removeClass('d-none');
                            $('#grandTotalUI').text('₹' + grandTotalRupees.toFixed(2));

                            $('#summaryDetails').empty();
                            $('#summaryDetailsWrapper').addClass('d-none');
                            $('#toggleSummaryBtn').html(
                                '<i class="fas fa-chevron-down"></i> Expand Details');
                            window.summaryCurrentIndex = 0;

                            renderSummaryChunk();
                        } else {
                            $('#summaryContainer').addClass('d-none');
                        }
                    }
                });
            }

            loadMyData();
            $('#btnFilter').click(function() {
                loadMyData();
            });

            $(document).on('click', '.btn-view', function() {
                openViewModal($(this).data('id'));
            });

            function openViewModal(id) {
                $('#viewFineBody').html(
                    '<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
                $('#viewFineModal').modal('show');

                $.get(`/api/v1/fine-penalties/${id}`, function(res) {
                    let statusColor = res.status === 'Approved' ? 'green' : (res.status === 'Rejected' ?
                        'red' : 'orange');

                    let proofHtml = '';
                    if (res.proof_media_list && res.proof_media_list.length > 0) {
                        let imgTags = res.proof_media_list.map(media =>
                            `<img src="/${media.file_path}" class="img-thumbnail cursor-pointer view-zoom-img m-1 shadow-sm" style="max-height: 120px; cursor: pointer;" title="Click to Zoom">`
                        ).join('');

                        proofHtml = `
                        <div class="mt-4 border p-3 rounded text-center bg-light shadow-sm">
                            <p class="fw-bold mb-3 border-bottom pb-2">Attached Proof(s)</p>
                            <div class="d-flex flex-wrap justify-content-center">${imgTags}</div>
                            <p class="small text-muted mt-2 mb-0"><i class="fas fa-search-plus"></i> Click image to zoom</p>
                        </div>
                    `;
                    }

                    let baseFine = parseFloat(res.fine_rupees) || 0;
                    let basePenalty = parseFloat(res.penalty_rupees) || 0;
                    let fineDaysAmt = 0;
                    let penaltyDaysAmt = 0;

                    let currentSal = res.employee ? (res.employee.payable_salary || res.employee
                        .payable_salary) : null;

                    if ((res.user_type === 'Employee' || !res.user_type) && res.employee && currentSal) {
                        let perDay = parseFloat(currentSal) / 30;
                        fineDaysAmt = (parseFloat(res.fine_days) || 0) * perDay;
                        penaltyDaysAmt = (parseFloat(res.penalty_days) || 0) * perDay;
                    }

                    let totalAmount = baseFine + basePenalty + fineDaysAmt + penaltyDaysAmt;
                    let totalDays = (parseFloat(res.fine_days) || 0) + (parseFloat(res.penalty_days) || 0);

                    let hasFine = (baseFine > 0 || (parseFloat(res.fine_days) || 0) > 0);
                    let hasPenalty = (basePenalty > 0 || (parseFloat(res.penalty_days) || 0) > 0);
                    let noticeTitle = (hasFine && hasPenalty) ? "FINE & PENALTY NOTICE" : (hasPenalty ?
                        "PENALTY NOTICE" : "FINE NOTICE");

                    let designationName = (res.employee && res.employee.designation) ? res.employee
                        .designation.designation_name : 'N/A';

                    // --- NAYA: Treat As Badge Logic ---
                    let treatAsText = 'Applied';
                    let treatAsColor = 'success';
                    if (res.treat_as === 'warning') {
                        treatAsText = 'Warning';
                        treatAsColor = 'warning text-dark';
                    } else if (res.treat_as === 'final') {
                        treatAsText = 'Final Warning';
                        treatAsColor = 'danger';
                    }
                    let treatAsBadge = `<span class="badge bg-${treatAsColor}">${treatAsText}</span>`;

                    let viewHtml = `
                        <div class="border p-3 rounded bg-white">
                            <div class="mb-4 pb-2 border-bottom text-center">
                                ${res.header_html ? res.header_html : ''}
                            </div>
                            
                            <div class="text-center mt-3 mb-4">
                                <h5 class="text-uppercase text-decoration-underline mb-1">${noticeTitle}</h5>
                            </div>
                            
                            <div class="row mb-3 bg-light p-2 rounded mx-0">
                                <div class="col-sm-6 mb-2 mb-sm-0">
                                    <strong>Employee Name:</strong> ${res.employee ? (res.employee.full_name || res.employee.member_name) : 'N/A'}<br>
                                    <strong>Employee ID:</strong> ${res.employee ? res.employee.member_id : 'N/A'}<br>
                                    <strong>Department:</strong> ${res.employee && res.employee.department ? res.employee.department.department_name : 'N/A'}<br>
                                    <strong>Designation:</strong> ${designationName}
                                </div>
                                <div class="col-sm-6 text-sm-end">
                                    <strong>Record No:</strong> FP-${String(res.id).padStart(5, '0')}<br>
                                    <strong>Date of Issue:</strong> ${res.date}<br>
                                    <strong>Action:</strong> ${treatAsBadge}<br>
                                    <strong>Status:</strong> <span style="color: ${statusColor}; font-weight: bold;">${res.status.toUpperCase()}</span>
                                </div>
                            </div>

                        <div class="fw-bold bg-light p-2 border border-bottom-0 rounded-top">Details of Charges</div>
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Charge Type</th>
                                    <th>Amount (₹)</th>
                                    <th>Deduction Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Fine</strong></td>
                                    <td class="text-danger">${res.fine_rupees ? '₹' + res.fine_rupees : '-'}</td>
                                    <td class="text-danger">
                                        ${res.fine_days ? res.fine_days + ' Days' : '-'}
                                        ${fineDaysAmt > 0 ? `<br><small class="text-muted">(₹${fineDaysAmt.toFixed(2)})</small>` : ''}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Penalty</strong></td>
                                    <td class="text-danger">${res.penalty_rupees ? '₹' + res.penalty_rupees : '-'}</td>
                                    <td class="text-danger">
                                        ${res.penalty_days ? res.penalty_days + ' Days' : '-'}
                                        ${penaltyDaysAmt > 0 ? `<br><small class="text-muted">(₹${penaltyDaysAmt.toFixed(2)})</small>` : ''}
                                    </td>
                                </tr>
                                <tr class="table-secondary">
                                    <td class="text-end align-middle"><strong>Grand Total:</strong></td>
                                    <td colspan="2" class="text-center fw-bold fs-5 text-danger">
                                        ₹${totalAmount.toFixed(2)}
                                        ${totalDays > 0 ? `<div class="fs-6 text-dark fw-normal mt-1">Total Deduction: ${totalDays} Days</div>` : ''}
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        ${res.description ? `<div class="mt-4"><div class="fw-bold bg-light p-2 border border-bottom-0 rounded-top">Remarks / Description</div><div class="p-3 border rounded-bottom">${res.description}</div></div>` : ''}
                        
                        ${proofHtml}
                    </div>
                `;

                    $('#viewFineBody').html(viewHtml);
                }).fail(function() {
                    $('#viewFineBody').html(
                        '<div class="text-center text-danger p-3">Failed to load data.</div>');
                });
            }

            $(document).on('click', '.view-zoom-img', function() {
                $('#zoomedImage').attr('src', $(this).attr('src'));
                $('#imageZoomModal').modal('show');
            });

            let urlParams = new URLSearchParams(window.location.search);
            let viewId = urlParams.get('view_id');

            if (viewId) {
                setTimeout(function() {
                    openViewModal(viewId);
                    window.history.replaceState({}, document.title, window.location.pathname);
                }, 600);
            }
        });
    </script>
@endpush
