@extends('layout.app')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<style>
    .table-custom th { background-color: var(--sidebar-bg); color: #fff; font-size: 13px; border: none; padding: 12px 15px;}
    .table-custom td { font-size: 13px; vertical-align: middle; padding: 12px 15px;}
    
    /* Mobile Card Styling */
    .mobile-item { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 15px; margin-bottom: 15px; box-shadow: 0 4px 6px var(--shadow-color); }
    .status-badge { font-size: 11px; padding: 4px 8px; border-radius: 20px; font-weight: bold; }
</style>

<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Manage Telecaller Access</h4>
    </div>

    <div class="d-block d-md-none mb-3">
        <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search by Name or ID...">
    </div>

    <div class="card border-0 shadow-sm mb-4 d-none d-md-block">
        <div class="card-body p-4 table-responsive">
            <table id="accessTable" class="table table-hover table-custom w-100">
                <thead>
                    <tr>
                        <th>Staff ID</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Current Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div id="mobileCardsContainer" class="d-block d-md-none">
        </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    const apiToken = localStorage.getItem('admin_token');

    // 1. Desktop DataTables
    let table = $('#accessTable').DataTable({
        ajax: { 
            url: '/api/v1/admin/telecaller-access', 
            headers: { 'Authorization': 'Bearer ' + apiToken },
            dataSrc: function(json) {
                renderMobileCards(json.data); // Parallelly render cards for mobile
                return json.data;
            }
        },
        columns: [
            { data: 'staff_id', render: d => `<span class="fw-bold text-primary">${d}</span>` },
            { data: 'name', render: d => `<span class="fw-bold text-dark">${d}</span>` },
            { data: 'role', render: d => `<span class="badge bg-light text-dark border">${d}</span>` },
            { data: 'has_access', render: d => d ? `<span class="badge bg-success"><i class="fas fa-check-circle"></i> Access Granted</span>` : `<span class="badge bg-danger"><i class="fas fa-times-circle"></i> No Access</span>` },
            { data: null, render: function(data, type, row) {
                if(row.has_access) {
                    return `<div class="text-end"><button class="btn btn-sm btn-danger toggle-access" data-id="${row.staff_id}"><i class="fas fa-user-times me-1"></i> Remove Access</button></div>`;
                } else {
                    return `<div class="text-end"><button class="btn btn-sm btn-success toggle-access" data-id="${row.staff_id}"><i class="fas fa-user-check me-1"></i> Give Access</button></div>`;
                }
            }}
        ]
    });

    // 2. Mobile Cards Rendering Function
    function renderMobileCards(data) {
        let html = '';
        data.forEach(item => {
            let statusBadge = item.has_access 
                ? `<span class="status-badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-check-circle"></i> Granted</span>` 
                : `<span class="status-badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fas fa-times-circle"></i> No Access</span>`;
            
            let actionButton = item.has_access 
                ? `<button class="btn btn-sm btn-danger w-100 fw-bold toggle-access" data-id="${item.staff_id}"><i class="fas fa-user-times me-1"></i> Remove Access</button>`
                : `<button class="btn btn-sm btn-success w-100 fw-bold toggle-access" data-id="${item.staff_id}"><i class="fas fa-user-check me-1"></i> Give Access</button>`;

            html += `
                <div class="mobile-item staff-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0">${item.name}</h6>
                            <small class="text-primary fw-bold">${item.staff_id}</small>
                        </div>
                        ${statusBadge}
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-light text-dark border" style="font-size:10px;">Role: ${item.role}</span>
                    </div>
                    <div class="pt-2 border-top">
                        ${actionButton}
                    </div>
                </div>
            `;
        });
        $('#mobileCardsContainer').html(html || '<p class="text-center text-muted">No staff records found.</p>');
    }

    // 3. Toggle Access Action (Works for both Table and Cards)
    $(document).on('click', '.toggle-access', function() {
        let staffId = $(this).data('id');
        let btn = $(this);
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/api/v1/admin/telecaller-access/toggle',
            type: 'POST',
            headers: { 'Authorization': 'Bearer ' + apiToken },
            data: { staff_id: staffId },
            success: function(res) {
                table.ajax.reload(null, false); // Reload table and mobile cards
            },
            error: function() {
                alert("Something went wrong!");
                btn.prop('disabled', false).text('Try Again');
            }
        });
    });

    // 4. Mobile Search Logic
    $('#mobileSearch').on('keyup', function() {
        let value = $(this).val().toLowerCase();
        $(".staff-card").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});
</script>
@endpush