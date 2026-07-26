@extends('layout.app')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
    .status-badge {
        width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: bold; border-radius: 6px; cursor: pointer; transition: 0.2s; margin: 0 auto;
    }
    .status-badge:hover { transform: scale(1.1); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .bg-P { background-color: #d1e7dd; color: #0f5132; }
    .bg-A { background-color: #f8d7da; color: #842029; }
    .bg-L { background-color: #cff4fc; color: #055160; }
    .bg-SL { background-color: #e0e7ff; color: #3730a3; border: 1px solid #3730a3; }
    .bg-WO { background-color: #e2e3e5; color: #41464b; }
    .bg-NA { background-color: transparent; color: #adb5bd; cursor: not-allowed; border: 1px dashed #adb5bd; }
    .holiday-col { background-color: #fff0f0 !important; }
    .holiday-header { background-color: #ffe6e6 !important; color: #d63384 !important; }
</style>

<div class="container-fluid p-0 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0 text-primary"><i class="fas fa-calendar-check me-2"></i> Member Attendance Matrix</h4>
        <button id="btnExportExcel" class="btn btn-success btn-sm fw-bold shadow-sm">
            <i class="fas fa-file-excel me-1"></i> Export Excel
        </button>
    </div>

    <!-- 🟢 Filter Form -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form id="filterForm" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1">Company</label>
                    <select class="form-select form-select-sm" id="filter_company">
                        <option value="">All Companies</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1">Branch</label>
                    <select class="form-select form-select-sm" id="filter_branch">
                        <option value="">All Branches</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1">Department</label>
                    <select class="form-select form-select-sm" id="filter_department">
                        <option value="">All Departments</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1">Select Month</label>
                    <input type="month" class="form-control form-control-sm border-warning" id="filter_month" value="{{ date('Y-m') }}">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1">Or Date Range</label>
                    <div class="input-group input-group-sm">
                        <input type="date" class="form-control" id="start_date">
                        <span class="input-group-text bg-light">to</span>
                        <input type="date" class="form-control" id="end_date">
                    </div>
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-primary btn-sm w-100 fw-bold" id="btnLoadMatrix"><i class="fas fa-search"></i></button>
                </div>
            </form>

            <div class="row mt-3 border-top pt-3">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-primary"><i class="fas fa-search text-primary"></i></span>
                        <input type="text" id="liveSearch" class="form-control border-primary fw-bold" placeholder="Live Search Member Name or ID...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 🟢 Loading Indicator -->
    <div id="loadingIndicator" class="text-center py-5 d-none">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 fw-bold text-muted">Calculating Timeline Metrics...</p>
    </div>

    <!-- 🟢 Data View -->
    <div id="dataViewWrapper" class="d-none">
        <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 12.5px; min-width: 1200px;">
                    <thead class="table-light" id="matrixThead"></thead>
                    <tbody id="matrixTbody"></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- 🟢 Correction Modal -->
<div class="modal fade" id="correctionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold text-primary"><i class="fas fa-edit me-2"></i> Adjust Attendance Status</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="correctionForm">
                    <input type="hidden" id="corr_emp_id">
                    <input type="hidden" id="corr_date">

                    <div class="alert alert-info py-2 small fw-bold mb-3">
                        Target Date: <span id="corr_date_display" class="text-danger"></span> <br>
                        Current System Status: <span id="corr_old_status" class="badge bg-secondary ms-1"></span>
                        <div class="row mt-2 border-top pt-2">
        <div class="col-6 text-success"><i class="fas fa-sign-in-alt"></i> In: <span id="corr_in_time_disp"></span></div>
        <div class="col-6 text-danger"><i class="fas fa-sign-out-alt"></i> Out: <span id="corr_out_time_disp"></span></div>
        <div class="col-12 mt-1 text-muted"><i class="fas fa-info-circle"></i> System Note: <span id="corr_sys_remark"></span></div>
    </div>
    
    <!-- 👇 YAHAN SE ADD KAREIN (Location Buttons) 👇 -->
    <div class="row g-2 mt-2 border-top pt-2">
        <div class="col-6">
            <a id="corr_map_link_in" href="#" target="_blank" class="btn btn-sm btn-outline-success w-100 d-none" title="View Punch-In Location">
                <i class="fas fa-map-marker-alt"></i> In-Loc
            </a>
        </div>
        <div class="col-6">
            <a id="corr_map_link_out" href="#" target="_blank" class="btn btn-sm btn-outline-danger w-100 d-none" title="View Punch-Out Location">
                <i class="fas fa-map-marker-alt"></i> Out-Loc
            </a>
        </div>
    </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">New Corrected Status</label>
                        <select class="form-select form-select-sm fw-bold" id="corr_new_status" required>
                            <option value="P">Present (P)</option>
                            <option value="A">Absent (A)</option>
                            <option value="L">Approved Leave (L)</option>
                            <option value="SL">Short Leave (SL)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Reason for Override <span class="text-danger">*</span></label>
                        <textarea class="form-control form-control-sm" id="corr_reason" rows="2" placeholder="Provide a valid HR reason..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">Save & Lock Correction</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 🟢 Route Map Modal -->
<div class="modal fade" id="routeMapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold"><i class="fas fa-route me-2 text-warning"></i> Route Map: <span id="mapMemberName" class="text-warning"></span></h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="d-flex justify-content-between bg-light p-2 px-3 border-bottom small fw-bold">
                    <span class="text-primary"><i class="fas fa-calendar-day"></i> Date: <span id="mapDateDisplay"></span></span>
                    <span class="text-success"><i class="fas fa-map-marker-alt"></i> Total Pings: <span id="mapPingCount">0</span></span>
                </div>
                <!-- Yahan map render hoga -->
                <div id="leafletRouteMap" style="height: 450px; width: 100%; background: #e9ecef;"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
<script>
$(document).ready(function() {
    let adminToken = localStorage.getItem('admin_token') || localStorage.getItem('emp_token');
    const baseUrl = '/api/v1/member-attendance-matrix-api';

    function apiCall(url, data = {}, type = 'POST', onSuccess) {
        $.ajax({
            url: url, type: type, headers: { 'Authorization': 'Bearer ' + adminToken }, data: data, success: onSuccess,
            error: function(xhr) { if(xhr.status === 401) console.error("API Auth Error"); }
        });
    }

    // 🟢 1. Cascading Dropdowns
    function loadCompanies() {
        apiCall(baseUrl + '/companies', {}, 'GET', function(res) {
            let opts = '<option value="">All Companies</option>';
            res.data.forEach(c => opts += `<option value="${c.id}">${c.company_name}</option>`);
            $('#filter_company').html(opts);
            if(res.data.length === 1) $('#filter_company').val(res.data[0].id).trigger('change');
            else loadBranches();
        });
    }

    function loadBranches() {
        let compId = $('#filter_company').val();
        apiCall(baseUrl + '/branches', { company_ids: compId ? [compId] : [] }, 'POST', function(res) {
            let opts = '<option value="">All Branches</option>';
            if(compId) opts += `<option value="HO" class="fw-bold text-primary">Head Office</option>`;
            res.data.forEach(b => opts += `<option value="${b.id}">${b.branch_name}</option>`);
            $('#filter_branch').html(opts);
            if(res.data.length === 1 && compId) $('#filter_branch').val(res.data[0].id).trigger('change');
            else loadDepartments();
        });
    }

    function loadDepartments() {
        let compId = $('#filter_company').val();
        let branchId = $('#filter_branch').val();
        apiCall(baseUrl + '/departments', { company_ids: compId ? [compId] : [], branch_ids: branchId ? [branchId] : [] }, 'POST', function(res) {
            let opts = '<option value="">All Departments</option>';
            res.data.forEach(d => opts += `<option value="${d.id}">${d.department_name}</option>`);
            $('#filter_department').html(opts);
        });
    }

    $('#filter_company').change(loadBranches);
    $('#filter_branch').change(loadDepartments);
    loadCompanies();

    // 🟢 2. Load Matrix Logic
    $('#btnLoadMatrix').click(function() {
        $('#dataViewWrapper').addClass('d-none');
        $('#loadingIndicator').removeClass('d-none');
        
        let reqData = {
            company_id: $('#filter_company').val(),
            branch_id: $('#filter_branch').val(),
            department_id: $('#filter_department').val(),
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            month_year: $('#filter_month').val()
        };

        apiCall(baseUrl + '/load-matrix', reqData, 'POST', function(res) {
            $('#loadingIndicator').addClass('d-none');
            if (res.success && res.matrix && res.matrix.length > 0) {
                window.currentMatrixData = res.matrix;
                window.currentDatesList = res.dates_list;
                renderDesktopTable(res.matrix, res.dates_list);
                $('#dataViewWrapper').removeClass('d-none');
                $('#liveSearch').trigger('keyup');
            } else {
                $('#matrixThead').empty();
                $('#matrixTbody').html('<tr><td class="text-center py-4 fw-bold text-danger">No attendance data found for these filters.</td></tr>');
                $('#dataViewWrapper').removeClass('d-none');
            }
        });
    });

    // 🟢 3. Render Table
    function renderDesktopTable(matrix, dates) {
        let headHtml = `<tr><th class="py-3 align-middle" style="min-width:250px; background-color:#1A365D; color:#fff; position: sticky; left: 0; z-index: 2;">Member Identity</th>`;
        
        let holidayMap = {};
        dates.forEach(d => {
            let dateObj = new Date(d);
            let dayName = dateObj.toLocaleDateString('en-US', { weekday: 'short' }).toUpperCase();
            let isWeekOff = (dayName === 'TUE');
            holidayMap[d] = isWeekOff;

            let headerClass = isWeekOff ? 'holiday-header' : '';
            let textColor = isWeekOff ? '' : 'color:#6c757d;';
            headHtml += `<th class="text-center align-middle ${headerClass}" style="min-width:45px; border-bottom: 2px solid #D69E2E;"><div style="font-size:11px; ${textColor}">${d.split('-')[2]}</div><div style="font-size:10px; font-weight:bold;">${dayName}</div></th>`;
        });

        headHtml += `<th class="text-center bg-success text-white align-middle">P</th>
                     <th class="text-center bg-danger text-white align-middle">A</th>
                     <th class="text-center bg-info text-white align-middle">L</th>
                     <th class="text-center text-white align-middle" style="background-color:#3730a3;">SL</th></tr>`;
        $('#matrixThead').html(headHtml);

        let bodyHtml = '';
        matrix.forEach(row => {
            let emp = row.employee;
            let stats = row.stats;
            let joinDate = row.joining_date ? new Date(row.joining_date) : new Date('2000-01-01');
            joinDate.setHours(0,0,0,0);

            bodyHtml += `<tr class="emp-search-row"><td class="bg-white" style="position: sticky; left: 0; z-index: 1;"><div class="fw-bold text-primary">${emp.name}</div><div class="text-muted" style="font-size:10.5px;">ID: <span class="fw-bold">${emp.member_id}</span> | ${emp.department}</div></td>`;

            dates.forEach(d => {
                let currentObj = new Date(d); currentObj.setHours(0,0,0,0);
                let colClass = holidayMap[d] ? 'holiday-col' : '';

                if(currentObj < joinDate) {
                    bodyHtml += `<td class="text-center align-middle bg-light text-muted">-</td>`;
                } else {
                    let dayData = row.dates[d];
                    let displayStatus = dayData.status === 'N/A' ? '-' : dayData.status;
                   // Puraane clickAction ko is line se replace karein
let clickAction = `onclick="openCorrectionModal('${emp.db_id}', '${d}', '${dayData.status}', '${dayData.in || ''}', '${dayData.out || ''}', '${dayData.remark || ''}', '${dayData.lat || ''}', '${dayData.lng || ''}', '${dayData.out_lat || ''}', '${dayData.out_lng || ''}')"`;
                    
                 let mapButton = '';
if(dayData.in) { // Agar present/punch-in hai tabhi route icon dikhayenge
    mapButton = `<div class="mt-1 text-primary" style="font-size: 10px; cursor: pointer;" onclick="openRouteMap('${emp.db_id}', '${d}', '${emp.name}')"><i class="fas fa-route"></i> Map</div>`;
}

bodyHtml += `<td class="text-center align-middle ${colClass}" title="In: ${dayData.in || '--'} | Out: ${dayData.out || '--'}">
    <div class="status-badge bg-${dayData.status === 'N/A' ? 'NA' : dayData.status}" ${clickAction}>${displayStatus}</div>
    ${mapButton}
</td>`;
                }
            });

            bodyHtml += `<td class="text-center fw-bold bg-success bg-opacity-10 text-success align-middle">${stats.present}</td>
                         <td class="text-center fw-bold bg-danger bg-opacity-10 text-danger align-middle">${stats.absent}</td>
                         <td class="text-center fw-bold bg-info bg-opacity-10 text-info align-middle">${stats.leave}</td>
                         <td class="text-center fw-bold bg-opacity-10 align-middle" style="color:#3730a3; background-color:#e0e7ff;">${stats.sl}</td></tr>`;
        });
        $('#matrixTbody').html(bodyHtml);
    }

    // 🟢 4. Correction Modal
  // Function parameters aur logic update karein
window.openCorrectionModal = function(empId, date, status, inTime, outTime, remark, inLat, inLng, outLat, outLng) {
    $('#corr_emp_id').val(empId);
    $('#corr_date').val(date);
    $('#corr_date_display').text(new Date(date).toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' }));
    $('#corr_old_status').text(status);
    $('#corr_in_time_disp').text(inTime || '--:--');
    $('#corr_out_time_disp').text(outTime || '--:--');
    $('#corr_sys_remark').text(remark || 'None');
    $('#corr_new_status').val((status === 'N/A' || status === 'WO') ? 'P' : status);
    $('#corr_reason').val('');

    // 👇 Location Buttons Logic 👇
    if (inLat && inLng && inLat !== 'null') {
        $('#corr_map_link_in').attr('href', `https://www.google.com/maps?q=${inLat},${inLng}`).removeClass('d-none disabled btn-outline-secondary').addClass('btn-outline-success').html('<i class="fas fa-map-marker-alt"></i> In-Loc');
    } else {
        $('#corr_map_link_in').attr('href', '#').removeClass('btn-outline-success d-none').addClass('disabled btn-outline-secondary').html('<i class="fas fa-map-marker-alt"></i> In Missing');
    }

    if (outLat && outLng && outLat !== 'null') {
        $('#corr_map_link_out').attr('href', `https://www.google.com/maps?q=${outLat},${outLng}`).removeClass('d-none disabled btn-outline-secondary').addClass('btn-outline-danger').html('<i class="fas fa-map-marker-alt"></i> Out-Loc');
    } else {
        $('#corr_map_link_out').attr('href', '#').removeClass('btn-outline-danger d-none').addClass('disabled btn-outline-secondary').html('<i class="fas fa-map-marker-alt"></i> Out Missing');
    }

    $('#correctionModal').modal('show');
};
    $('#correctionForm').submit(function(e) {
        e.preventDefault();
        alert("Ready for API Submission");
    });

    // 🟢 5. Live Search & Excel Export
    $('#liveSearch').on('keyup', function() {
        let v = $(this).val().toLowerCase();
        $('.emp-search-row').each(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1);
        });
    });

    $('#btnExportExcel').click(function() {
        if (!window.currentMatrixData || window.currentMatrixData.length === 0) {
            Swal.fire('Notice', 'Please load the matrix first.', 'info');
            return;
        }
        let csvContent = "data:text/csv;charset=utf-8,";
        let headers = ["Member ID", "Name", "Designation"];
        window.currentDatesList.forEach(d => { 
            let parts = d.split('-'); 
            let dayName = new Date(d).toLocaleDateString('en-US', { weekday: 'short' }).toUpperCase();
            headers.push(`${parts[2]}-${parts[1]} (${dayName})`); 
        });
        headers.push("Present", "Absent", "Leave", "Short Leave"); 
        csvContent += headers.map(h => `"${h}"`).join(",") + "\r\n";

        window.currentMatrixData.forEach(row => {
            let rowData = [ `"${row.employee.member_id}"`, `"${row.employee.name}"`, `"${row.employee.department}"` ];
            window.currentDatesList.forEach(d => { let st = row.dates[d].status; rowData.push(`"${st === 'N/A' ? '-' : st}"`); });
            rowData.push(row.stats.present, row.stats.absent, row.stats.leave, row.stats.sl); 
            csvContent += rowData.join(",") + "\r\n";
        });

        let link = document.createElement("a"); 
        link.setAttribute("href", encodeURI(csvContent)); 
        link.setAttribute("download", `Member_Attendance_${new Date().getTime()}.csv`); 
        document.body.appendChild(link); link.click(); document.body.removeChild(link);
    });

    // ----------------------------------------------------
    // 🟢 ROUTE MAP (LEAFLET.JS) LOGIC
    // ----------------------------------------------------
    let routeMap = null;
    let routePolyline = null;
    let markersLayer = null;

    window.openRouteMap = function(memberId, dateStr, memberName) {
        let displayDate = new Date(dateStr).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
        $('#mapMemberName').text(memberName);
        $('#mapDateDisplay').text(displayDate);
        $('#mapPingCount').text('Loading...');
        
        $('#routeMapModal').modal('show');

        // Fetch Route Data
        apiCall(baseUrl + '/get-route', { member_id: memberId, date: dateStr }, 'POST', function(res) {
            if(res.status === 'success') {
                drawMap(res);
            }
        });
    };

    function drawMap(data) {
        let routeLogs = data.route;
        $('#mapPingCount').text(routeLogs.length);

        // Agar map pehle se bana hai, toh layers clear karein
        if (routeMap !== null) {
            routeMap.remove();
        }

        // Initialize Map
        routeMap = L.map('leafletRouteMap');
        
        // OpenStreetMap Free Tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(routeMap);

        let latlngs = [];
        let bounds = new L.LatLngBounds();

        markersLayer = L.layerGroup().addTo(routeMap);

        // Draw Punch In Marker (Green)
        if (data.punch_in.lat && data.punch_in.lng) {
            let inPt = [data.punch_in.lat, data.punch_in.lng];
            latlngs.push(inPt);
            bounds.extend(inPt);
            L.marker(inPt).addTo(markersLayer).bindPopup(`<b>Start (Punch In)</b><br>${data.punch_in.time}`);
        }

        // Draw Route Pings
        routeLogs.forEach(log => {
            let pt = [log.latitude, log.longitude];
            latlngs.push(pt);
            bounds.extend(pt);
        });

        // Draw Punch Out Marker (Red)
        if (data.punch_out.lat && data.punch_out.lng) {
            let outPt = [data.punch_out.lat, data.punch_out.lng];
            latlngs.push(outPt);
            bounds.extend(outPt);
            L.marker(outPt).addTo(markersLayer).bindPopup(`<b>End (Punch Out)</b><br>${data.punch_out.time}`);
        }

        // Draw Polyline (Red Route)
        if (latlngs.length > 1) {
            routePolyline = L.polyline(latlngs, {color: 'red', weight: 4, opacity: 0.7}).addTo(routeMap);
        } else if (latlngs.length === 0) {
            // Default center if no data
            routeMap.setView([20.5937, 78.9629], 5); // Center of India
            return;
        }

        // Fit map bounds to show full route
        routeMap.fitBounds(bounds, { padding: [30, 30] });
    }

    // Leaflet requires size invalidation when opened inside a Bootstrap Modal
    $('#routeMapModal').on('shown.bs.modal', function() {
        if (routeMap) {
            routeMap.invalidateSize();
        }
    });

});
</script>
@endpush