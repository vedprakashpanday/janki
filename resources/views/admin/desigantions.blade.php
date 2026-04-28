@extends('layout.app')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<style>
    .table-custom th { background-color: var(--sidebar-bg); color: #fff; font-size: 13px; border: none; }
    .table-custom td { font-size: 13px; vertical-align: middle; border-bottom: 1px solid var(--border-color); }
    .desig-card { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 15px; margin-bottom: 15px; box-shadow: 0 4px 6px var(--shadow-color); }
    .status-active { background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 20px; font-size: 11px; }
    .status-inactive { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 20px; font-size: 11px; }
</style>

<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Employee Designations</h4>
            <p class="text-secondary small d-none d-md-block mb-0">Manage job roles and designations</p>
        </div>
        <div>
            <button class="btn text-white px-3 py-2 shadow-sm" style="background-color: var(--brand-primary);" onclick="openAddModal()">
                <i class="fas fa-plus me-1"></i> Add Designation
            </button>
        </div>
    </div>

    <div class="d-flex d-md-none gap-2 mb-3">
        <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search Designation...">
        <button class="btn text-white shadow-sm" style="background-color: #10b981;" id="mobileExcelBtn"><i class="fas fa-file-excel"></i></button>
    </div>

    <div class="card border-0 shadow-sm d-none d-md-block">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="desigTable" class="table table-hover table-custom w-100">
                    <thead>
                        <tr>
                            <th>Designation Code</th>
                            <th>Designation Name</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="mobileCardsContainer" class="d-block d-md-none">
        <div class="text-center text-muted my-4" id="cardsLoader">
            <i class="fas fa-spinner fa-spin fs-2 mb-2"></i><br>Loading Designations...
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i class="fas fa-briefcase me-2 text-primary"></i> Add Designation</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Designation Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="designation_name" required placeholder="e.g. Software Developer">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Status</label>
                        <select class="form-select" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn text-white w-100 py-2 fw-medium mt-2" style="background-color: var(--sidebar-bg);" id="saveBtn">Save Designation</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i class="fas fa-edit me-2 text-primary"></i> Edit Designation</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Designation Code</label>
                        <input type="text" class="form-control bg-light" id="edit_code" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Designation Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Status</label>
                        <select class="form-select" id="edit_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn text-white w-100 py-2 fw-medium mt-2" style="background-color: var(--sidebar-bg);" id="updateBtn">Update Designation</button>
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

    // 1. Initialize DataTable
    let table = $('#desigTable').DataTable({
        ajax: {
            url: '/api/v1/admin/designations',
            type: 'GET',
            headers: { 'Authorization': 'Bearer ' + apiToken },
            dataSrc: 'data'
        },
        dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
        buttons: [{ extend: 'excelHtml5', text: '<i class="fas fa-file-excel me-1"></i> Export Excel', className: 'btn btn-success btn-sm shadow-sm rounded-3' }],
        columns: [
            { data: 'designation_code', render: d => `<span class="fw-bold" style="color:var(--brand-primary)">${d}</span>` },
            { data: 'designation_name', render: d => `<span class="fw-medium">${d}</span>` },
            { data: 'status', render: s => s === 'active' ? `<span class="status-active">Active</span>` : `<span class="status-inactive">Inactive</span>` },
            { data: 'id', orderable: false, render: d => `
                <div class="text-end">
                    <button class="btn btn-sm btn-light text-primary me-1 edit-btn" data-id="${d}"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-light text-danger delete-btn" data-id="${d}"><i class="fas fa-trash-alt"></i></button>
                </div>`
            }
        ]
    });

    // 2. Load Mobile Cards
    function loadMobileCards() {
        $.ajax({
            url: '/api/v1/admin/designations',
            headers: { 'Authorization': 'Bearer ' + apiToken },
            success: function(res) {
                let html = '';
                res.data.forEach(d => {
                    let statusHtml = d.status === 'active' ? `<span class="status-active">Active</span>` : `<span class="status-inactive">Inactive</span>`;
                    html += `
                    <div class="desig-card mobile-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div><h6 class="fw-bold mb-1" style="color: var(--sidebar-bg);">${d.designation_name}</h6><div class="small fw-bold" style="color: var(--brand-primary);">${d.designation_code}</div></div>
                            ${statusHtml}
                        </div>
                        <div class="d-flex gap-2 border-top pt-2 mt-2">
                            <button class="btn btn-sm btn-light text-primary flex-fill fw-medium edit-btn" data-id="${d.id}"><i class="fas fa-edit me-1"></i> Edit</button>
                            <button class="btn btn-sm btn-light text-danger flex-fill fw-medium delete-btn" data-id="${d.id}"><i class="fas fa-trash-alt me-1"></i> Delete</button>
                        </div>
                    </div>`;
                });
                $('#cardsLoader').hide();
                $('#mobileCardsContainer').html(html);
            }
        });
    }
    loadMobileCards();

    // Mobile Search & Excel Event
    $('#mobileSearch').on('keyup', function() {
        let v = $(this).val().toLowerCase();
        $('.mobile-item').filter(function() { $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1) });
    });
    $('#mobileExcelBtn').click(() => $('.buttons-excel').click());

    // 3. Open Add Modal
    window.openAddModal = function() {
        $('#addForm')[0].reset();
        $('#addModal').modal('show');
    };

    // 4. Submit Add Form
    $('#addForm').submit(function(e) {
        e.preventDefault();
        let btn = $('#saveBtn');
        btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        $.ajax({
            url: '/api/v1/admin/designations',
            type: 'POST',
            headers: { 'Authorization': 'Bearer ' + apiToken },
            data: $(this).serialize(),
            success: function() {
                $('#addModal').modal('hide');
                table.ajax.reload(null, false);
                loadMobileCards();
            },
            error: function(err) { alert(err.responseJSON.message || 'Error occurred'); },
            complete: function() { btn.html('Save Designation').prop('disabled', false); }
        });
    });

    // 5. Open Edit Modal
    $(document).on('click', '.edit-btn', function() {
        let id = $(this).data('id');
        $.get({
            url: `/api/v1/admin/designations/${id}`,
            headers: { 'Authorization': 'Bearer ' + apiToken },
            success: function(res) {
                $('#edit_id').val(res.data.id);
                $('#edit_code').val(res.data.designation_code);
                $('#edit_name').val(res.data.designation_name);
                $('#edit_status').val(res.data.status);
                $('#editModal').modal('show');
            }
        });
    });

    // 6. Submit Edit Form
    $('#editForm').submit(function(e) {
        e.preventDefault();
        let id = $('#edit_id').val();
        $.ajax({
            url: `/api/v1/admin/designations/${id}`,
            type: 'PUT',
            headers: { 'Authorization': 'Bearer ' + apiToken },
            data: { designation_name: $('#edit_name').val(), status: $('#edit_status').val() },
            success: function() {
                $('#editModal').modal('hide');
                table.ajax.reload(null, false);
                loadMobileCards();
            },
            error: function(err) { alert(err.responseJSON.message || 'Error occurred'); }
        });
    });

    // 7. Delete Designation
    $(document).on('click', '.delete-btn', function() {
        if(confirm('Delete this Designation?')) {
            $.ajax({
                url: `/api/v1/admin/designations/${$(this).data('id')}`,
                type: 'DELETE',
                headers: { 'Authorization': 'Bearer ' + apiToken },
                success: function() {
                    table.ajax.reload(null, false);
                    loadMobileCards();
                }
            });
        }
    });
});
</script>
@endpush