@extends('layout.app')

@section('content')
<div class="container-fluid px-1 px-md-3 py-2">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-building me-2"></i>Company Management</h5>
        <button class="btn btn-primary btn-sm shadow-sm fw-bold" onclick="openAddModal()">
            <i class="fas fa-plus-circle me-1"></i> Add Company
        </button>
    </div>

    <!-- Desktop Table -->
    <div class="card border-0 shadow-sm d-none d-md-block">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="companyDataTable" style="width: 100%;">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Prefix</th>
                            <th>Company Name</th>
                            <th>Parent Company</th>
                            <th>State/District</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Mobile Cards Container -->
    <div class="d-md-none" id="mobileCardsContainer">
        <div class="text-center py-5" id="mobileLoader">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted small">Loading companies...</p>
        </div>
    </div>
</div>

<!-- Add / Edit Company Modal -->
<div class="modal fade" id="companyModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold text-primary" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add Company</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="companyForm">
                @csrf
                <div class="modal-body p-4">
                    <input type="hidden" id="c_id" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small fw-bold">Company Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-primary" id="c_company_name" name="company_name" placeholder="e.g. Amitabh Developers" required>
                        </div>
                        
                        <!-- Prefix Code Input Added Here -->
                        <div class="col-md-6">
                            <label class="small fw-bold">Prefix Code (Short Name) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-primary text-uppercase" id="c_company_code" name="company_code" placeholder="e.g. ABD" maxlength="10" required>
                            <small class="text-muted">Will be used to generate branch/employee IDs.</small>
                        </div>

                        <div class="col-md-12">
                            <label class="small fw-bold">Parent Company</label>
                            <select class="form-select" id="c_parent_id" name="parent_id">
                                <option value="">-- None (Master Company) --</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="small fw-bold">Phone</label>
                            <input type="text" class="form-control" id="c_phone" name="phone" placeholder="Contact Number">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Email</label>
                            <input type="email" class="form-control" id="c_email" name="email" placeholder="Email Address">
                        </div>

                        <div class="col-md-6">
                            <label class="small fw-bold">State</label>
                            <input type="text" class="form-control" id="c_state" name="state" placeholder="e.g. Bihar">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">District</label>
                            <input type="text" class="form-control" id="c_district" name="district" placeholder="e.g. Darbhanga">
                        </div>

                        <div class="col-md-8">
                            <label class="small fw-bold">Address</label>
                            <input type="text" class="form-control" id="c_address" name="address" placeholder="Full Address">
                        </div>

                        <div class="col-md-4">
                            <label class="small fw-bold">GST No (Optional)</label>
                            <input type="text" class="form-control text-uppercase" id="c_gst_no" name="gst_no" placeholder="GSTIN">
                        </div>

                        <div class="col-md-12">
                            <label class="small fw-bold">Status</label>
                            <select class="form-select" id="c_status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold" id="saveBtn">Save Company</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Company Modal -->
<div class="modal fade" id="viewCompanyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-bottom-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-building me-2"></i>Company Details</h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr><th width="35%">Prefix Code</th><td id="v_code" class="fw-bold text-dark"></td></tr>
                        <tr><th>Company Name</th><td id="v_name" class="fw-bold text-primary"></td></tr>
                        <tr><th>Parent Company</th><td id="v_parent"></td></tr>
                        <tr><th>Phone</th><td id="v_phone"></td></tr>
                        <tr><th>Email</th><td id="v_email"></td></tr>
                        <tr><th>State & District</th><td id="v_location"></td></tr>
                        <tr><th>Address</th><td id="v_address"></td></tr>
                        <tr><th>GST No</th><td id="v_gst" class="text-uppercase"></td></tr>
                        <tr><th>Status</th><td id="v_status"></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let table;
const apiToken = localStorage.getItem('admin_token');

$(document).ready(function() {
    
    table = $('#companyDataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/api/v1/admin/companies',
            type: 'GET',
            headers: { 'Authorization': 'Bearer ' + apiToken }
        },
        columns: [
            { data: 'id', className: 'ps-3 fw-bold text-primary' },
            { data: 'company_code' }, // Naya Column
            { data: 'company_name', className: 'fw-bold' },
            { data: 'parent_name' },
            { 
                data: null, 
                render: function(data, type, row) {
                    return row.district + ', ' + row.state;
                }
            },
            { 
                data: 'status', 
                render: function(data) {
                    return data === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
                }
            },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        drawCallback: function(settings) {
            renderMobileCards(settings.json.data);
        }
    });

    loadParentCompanies();

    $('#companyForm').on('submit', function(e) {
        e.preventDefault();
        let id = $('#c_id').val();
        let url = id ? `/api/v1/admin/companies/${id}` : '/api/v1/admin/companies';
        let method = id ? 'PUT' : 'POST';
        
        let formData = $(this).serialize();
        $('#saveBtn').html('<span class="spinner-border spinner-border-sm"></span> Saving...');
        
        $.ajax({
            url: url,
            type: method,
            data: formData,
            headers: { 'Authorization': 'Bearer ' + apiToken },
            success: function(res) {
                $('#companyModal').modal('hide');
                Swal.fire('Success', res.message, 'success');
                table.ajax.reload();
                loadParentCompanies(); 
            },
            error: function(err) {
                Swal.fire('Error', err.responseJSON?.message || 'Something went wrong', 'error');
            },
            complete: function() {
                $('#saveBtn').html('Save Company');
            }
        });
    });
});

function loadParentCompanies() {
    $.ajax({
        url: '/api/v1/admin/get-active-companies',
        type: 'GET',
        headers: { 'Authorization': 'Bearer ' + apiToken },
        success: function(res) {
            let options = '<option value="">-- None (Master Company) --</option>';
            if(res.status === 'success') {
                res.data.forEach(c => {
                    options += `<option value="${c.id}">${c.company_name} (${c.company_code})</option>`;
                });
            }
            $('#c_parent_id').html(options);
        }
    });
}

function openAddModal() {
    $('#companyForm')[0].reset();
    $('#c_id').val('');
    $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add Company');
    $('#companyModal').modal('show');
}

function editCompany(id) {
    $.ajax({
        url: `/api/v1/admin/companies/${id}`,
        type: 'GET',
        headers: { 'Authorization': 'Bearer ' + apiToken },
        success: function(res) {
            let data = res.data;
            $('#c_id').val(data.id);
            $('#c_company_name').val(data.company_name);
            $('#c_company_code').val(data.company_code); // Set Prefix Code
            $('#c_parent_id').val(data.parent_id);
            $('#c_phone').val(data.phone);
            $('#c_email').val(data.email);
            $('#c_state').val(data.state);
            $('#c_district').val(data.district);
            $('#c_address').val(data.address);
            $('#c_gst_no').val(data.gst_no);
            $('#c_status').val(data.status);
            
            $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Company');
            $('#companyModal').modal('show');
        }
    });
}

// VIEW COMPANY DETAILS
function viewCompany(id) {
    $.ajax({
        url: `/api/v1/admin/companies/${id}`,
        type: 'GET',
        headers: { 'Authorization': 'Bearer ' + apiToken },
        success: function(res) {
            let data = res.data;
            $('#v_code').html('<span class="badge bg-dark">' + data.company_code + '</span>');
            $('#v_name').text(data.company_name);
            $('#v_parent').html(data.parent ? data.parent.company_name : '<span class="badge bg-secondary">Master Company</span>');
            $('#v_phone').text(data.phone || '-');
            $('#v_email').text(data.email || '-');
            $('#v_location').text((data.district || '-') + ', ' + (data.state || '-'));
            $('#v_address').text(data.address || '-');
            $('#v_gst').text(data.gst_no || '-');
            $('#v_status').html(data.status === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>');
            
            $('#viewCompanyModal').modal('show');
        }
    });
}

function deleteCompany(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "Deleting a parent company might affect its branches!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/api/v1/admin/companies/${id}`,
                type: 'DELETE',
                headers: { 'Authorization': 'Bearer ' + apiToken },
                success: function(res) {
                    Swal.fire('Deleted!', 'Company has been deleted.', 'success');
                    table.ajax.reload();
                    loadParentCompanies(); 
                }
            });
        }
    });
}

// MOBILE CARDS RENDER
function renderMobileCards(data) {
    $('#mobileLoader').hide();
    let html = '';
    if (!data || data.length === 0) {
        html = '<div class="text-center p-4 bg-white rounded shadow-sm">No companies found.</div>';
    } else {
        data.forEach(c => {
            let statusBadge = c.status === 'active' ? '<span class="badge bg-success-subtle text-success">Active</span>' : '<span class="badge bg-danger-subtle text-danger">Inactive</span>';
            // Extract raw prefix without HTML badge for mobile use
            let rawPrefix = $(c.company_code).text(); 
            html += `
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-dark mb-1">${rawPrefix}</span>
                            <h6 class="fw-bold mb-1 text-primary">${c.company_name}</h6>
                            <small class="text-muted d-block"><i class="fas fa-sitemap me-1"></i> ${c.parent_name}</small>
                            <small class="text-muted d-block"><i class="fas fa-map-marker-alt me-1"></i> ${c.district}, ${c.state}</small>
                        </div>
                        <div class="text-end">
                            ${statusBadge}
                        </div>
                    </div>
                    <div class="d-flex justify-content-end align-items-center pt-2 border-top gap-1">
                        <button onclick="viewCompany(${c.id})" class="btn btn-sm btn-light border text-info" title="View"><i class="fas fa-eye"></i></button>
                        <button onclick="editCompany(${c.id})" class="btn btn-sm btn-light border text-success" title="Edit"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteCompany(${c.id})" class="btn btn-sm btn-light border text-danger" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>`;
        });
    }
    $('#mobileCardsContainer').html(html);
}
</script>
<style>
    .table thead th { font-size: 13px; text-transform: uppercase; color: #718096; }
    .table tbody td { font-size: 14px; color: #2D3748; }
</style>
@endpush