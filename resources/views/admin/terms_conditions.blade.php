@extends('layout.app') 

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-dark"><i class="fas fa-file-contract text-primary me-2"></i> Terms & Conditions Master</h4>
        <button class="btn btn-primary secured-item" data-permission="terms_add" onclick="openAddModal()">
            <i class="fas fa-plus-circle me-1"></i> Add New T&C
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle" id="termsTable">
                <thead class="bg-light">
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Target Audience</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="termsList">
                    </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="termModal" tabindex="-1">
    <div class="modal-dialog modal-xl"> <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Terms & Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="termForm">
                    <input type="hidden" id="termId">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Title</label>
                            <input type="text" id="title" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Target Audience</label>
                            <select id="target_audience" class="form-select" required>
                                <option value="employee">Employees</option>
                                <option value="member">Members</option>
                                <option value="customer">Customers</option>
                                <option value="vendor">Vendors</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Content</label>
                        <textarea id="contentEditor" class="form-control" name="content"></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select id="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveTerm()">Save Terms</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>

<script>
    let isEditing = false;

    $(document).ready(function() {
        // Initialize Summernote
        $('#contentEditor').summernote({
            height: 300,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });

        loadTerms();
    });

    function loadTerms() {
        $.ajax({
            url: '/api/v1/terms-conditions',
            type: 'GET',
            success: function(res) {
                let html = '';
                res.data.forEach(term => {
                    let badgeClass = term.status === 'active' ? 'bg-success' : 'bg-danger';
                    let audienceBadge = term.target_audience === 'employee' ? 'bg-info' : 'bg-warning text-dark';
                    
                    html += `<tr>
                        <td>${term.id}</td>
                        <td class="fw-medium">${term.title}</td>
                        <td><span class="badge ${audienceBadge}">${term.target_audience.toUpperCase()}</span></td>
                        <td><span class="badge ${badgeClass}">${term.status.toUpperCase()}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary secured-item" data-permission="terms_edit" onclick="editTerm(${term.id})"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger secured-item" data-permission="terms_delete" onclick="deleteTerm(${term.id})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>`;
                });
                $('#termsList').html(html);
                if (typeof window.applyPermissions === 'function') window.applyPermissions();
            }
        });
    }

    function openAddModal() {
        isEditing = false;
        $('#termId').val('');
        $('#title').val('');
        $('#target_audience').val('employee');
        $('#status').val('active');
        $('#contentEditor').summernote('code', ''); // Clear summernote
        
        $('#modalTitle').text('Add Terms & Conditions');
        $('#termModal').modal('show');
    }

    function editTerm(id) {
        $.ajax({
            url: `/api/v1/terms-conditions/${id}`,
            type: 'GET',
            success: function(res) {
                let t = res.data;
                isEditing = true;
                $('#termId').val(t.id);
                $('#title').val(t.title);
                $('#target_audience').val(t.target_audience);
                $('#status').val(t.status);
                
                // Set Summernote content
                $('#contentEditor').summernote('code', t.content);
                
                $('#modalTitle').text('Edit Terms & Conditions');
                $('#termModal').modal('show');
            }
        });
    }

    function saveTerm() {
        let payload = {
            title: $('#title').val(),
            target_audience: $('#target_audience').val(),
            content: $('#contentEditor').summernote('code'), // Get HTML content
            status: $('#status').val()
        };

        let method = isEditing ? 'PUT' : 'POST';
        let url = isEditing ? `/api/v1/terms-conditions/${$('#termId').val()}` : '/api/v1/terms-conditions';

        $.ajax({
            url: url,
            type: method,
            data: payload,
            success: function(res) {
                Swal.fire('Success', res.message, 'success');
                $('#termModal').modal('hide');
                loadTerms();
            },
            error: function(err) {
                Swal.fire('Error', 'Something went wrong!', 'error');
            }
        });
    }

    function deleteTerm(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/api/v1/terms-conditions/${id}`,
                    type: 'DELETE',
                    success: function(res) {
                        Swal.fire('Deleted!', res.message, 'success');
                        loadTerms();
                    }
                });
            }
        });
    }
</script>
@endpush