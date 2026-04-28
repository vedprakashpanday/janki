@extends('layout.app')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<style>
    .table-custom th { background-color: var(--sidebar-bg); color: #fff; font-size: 13px; border: none; padding: 12px 15px;}
    .table-custom td { font-size: 13px; vertical-align: middle; padding: 12px 15px;}
    .mobile-item { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 15px; margin-bottom: 15px; box-shadow: 0 4px 6px var(--shadow-color); }
    .status-active { background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold;}
    .status-inactive { background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold;}
    .comm-badge { background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 6px; font-weight: bold; font-size: 12px;}
</style>

<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Member Designations & Commission</h4>
        </div>
        <button type="button" class="btn text-white px-3 py-2 shadow-sm" style="background-color: var(--brand-primary);" onclick="openModal('add')">
            <i class="fas fa-plus me-1"></i> Add Designation
        </button>
    </div>

    <div class="d-flex d-md-none gap-2 mb-3">
        <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search Designation...">
        <button type="button" class="btn text-white shadow-sm" style="background-color: #10b981;" id="mobileExcelBtn"><i class="fas fa-file-excel"></i></button>
    </div>

    <div class="card border-0 shadow-sm d-none d-md-block">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="desigTable" class="table table-hover table-custom w-100">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Designation Name</th>
                            <th>Commission (%)</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="mobileCardsContainer" class="d-block d-md-none"></div>
</div>

<div class="modal fade" id="desigModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="modalTitle" style="color: var(--sidebar-bg);"><i class="fas fa-briefcase me-2 text-primary"></i> Manage Designation</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="desigForm">
                    <input type="hidden" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Designation Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="designation_name" id="f_name" required placeholder="e.g. Sales Executive (S.E.)">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Commission Percentage (%)</label>
                        <input type="number" step="0.01" class="form-control" name="commission_percentage" id="f_comm" placeholder="e.g. 5.50">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold">Status</label>
                        <select class="form-select" name="status" id="f_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="btn text-white w-100 py-2 fw-medium shadow-sm" style="background-color: var(--brand-primary);" id="saveBtn">Save Designation</button>
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
$(document).ready(function() {
    const apiToken = localStorage.getItem('admin_token');
    let mode = 'add'; 

    // 1. DataTables
    let table = $('#desigTable').DataTable({
        ajax: { url: '/api/v1/admin/member-designations', headers: { 'Authorization': 'Bearer ' + apiToken } },
        dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
        buttons: [{ extend: 'excelHtml5', text: '<i class="fas fa-file-excel me-1"></i> Export Excel', className: 'btn btn-success btn-sm shadow-sm rounded-3' }],
        columns: [
            { data: 'designation_code', render: d => `<span class="fw-bold text-primary">${d}</span>` },
            { data: 'designation_name', render: d => `<span class="fw-medium">${d}</span>` },
            { data: 'commission_percentage', render: d => `<span class="comm-badge">${d}%</span>` },
            { data: 'status', render: d => d === 'active' ? `<span class="status-active">Active</span>` : `<span class="status-inactive">Inactive</span>` },
            { data: 'id', orderable: false, render: d => `
                <div class="text-end">
                    <button type="button" class="btn btn-sm btn-light text-primary me-1 edit-btn" data-id="${d}"><i class="fas fa-edit"></i> Edit</button>
                    <button type="button" class="btn btn-sm btn-light text-danger delete-btn" data-id="${d}"><i class="fas fa-trash-alt"></i></button>
                </div>`
            }
        ]
    });

    // 2. Mobile Cards
    function loadMobile() {
        $.ajax({
            url: '/api/v1/admin/member-designations',
            headers: { 'Authorization': 'Bearer ' + apiToken },
            success: function(res) {
                let html = '';
                res.data.forEach(d => {
                    let statusHtml = d.status === 'active' ? `<span class="status-active">Active</span>` : `<span class="status-inactive">Inactive</span>`;
                    html += `<div class="mobile-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-1">${d.designation_name}</h6>
                                <span class="fw-bold text-primary small">${d.designation_code}</span>
                            </div>
                            ${statusHtml}
                        </div>
                        <div class="small text-muted mb-3"><span class="comm-badge">Commission: ${d.commission_percentage}%</span></div>
                        <div class="border-top pt-2 d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-light text-primary flex-fill edit-btn" data-id="${d.id}"><i class="fas fa-edit"></i> Edit</button>
                            <button type="button" class="btn btn-sm btn-light text-danger flex-fill delete-btn" data-id="${d.id}"><i class="fas fa-trash-alt"></i> Delete</button>
                        </div>
                    </div>`;
                });
                $('#mobileCardsContainer').html(html);
            }
        });
    }
    loadMobile();

    // Mobile Search & Excel functionality
    $('#mobileSearch').on('keyup', function() {
        let v = $(this).val().toLowerCase();
        $('.mobile-item').filter(function() { $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1) });
    });
    $('#mobileExcelBtn').click(() => $('.buttons-excel').click());

    // 3. Open Modal
    window.openModal = function(type, id = null) {
        mode = type;
        $('#desigForm')[0].reset();
        $('#modalTitle').html(type === 'add' ? '<i class="fas fa-plus-circle me-2 text-primary"></i> Add Designation' : '<i class="fas fa-edit me-2 text-primary"></i> Edit Designation');
        
        if(type === 'edit') {
            $.get({
                url: `/api/v1/admin/member-designations/${id}`,
                headers: { 'Authorization': 'Bearer ' + apiToken },
                success: function(res) {
                    let d = res.data;
                    $('#edit_id').val(d.id);
                    $('#f_name').val(d.designation_name);
                    $('#f_comm').val(d.commission_percentage);
                    $('#f_status').val(d.status);
                }
            });
        }
        $('#desigModal').modal('show');
    };

    $(document).on('click', '.edit-btn', function() { openModal('edit', $(this).data('id')); });

    // 4. Form Submit
    $('#desigForm').submit(function(e) {
        e.preventDefault();
        let id = $('#edit_id').val();
        let url = mode === 'add' ? '/api/v1/admin/member-designations' : `/api/v1/admin/member-designations/${id}`;
        let type = mode === 'add' ? 'POST' : 'PUT';

        let btn = $('#saveBtn');
        btn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: url, type: type, headers: { 'Authorization': 'Bearer ' + apiToken },
            data: $(this).serialize(),
            success: function(res) {
                $('#desigModal').modal('hide');
                table.ajax.reload(null, false);
                loadMobile();
            },
            error: function(err) { alert(err.responseJSON.message); },
            complete: function() { btn.prop('disabled', false).text('Save Designation'); }
        });
    });

    // 5. Delete Logic
    $(document).on('click', '.delete-btn', function() {
        if(confirm("Are you sure you want to delete this Designation?")) {
            $.ajax({
                url: `/api/v1/admin/member-designations/${$(this).data('id')}`,
                type: 'DELETE', headers: { 'Authorization': 'Bearer ' + apiToken },
                success: function() { table.ajax.reload(null, false); loadMobile(); }
            });
        }
    });
});
</script>
@endpush