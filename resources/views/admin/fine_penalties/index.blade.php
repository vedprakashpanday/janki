@extends('layout.app')
@section('content')
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">


    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Fine / Penalty Management</h4>

            <div>
                <button id="addBtnDesktop" class="btn btn-primary d-none" data-bs-toggle="modal" data-bs-target="#fineModal">
                    <i class="fas fa-plus"></i> Add Fine/Penalty
                </button>
                <button id="addBtnMobile" class="btn btn-primary d-none" data-bs-toggle="offcanvas"
                    data-bs-target="#fineOffcanvas">
                    <i class="fas fa-plus"></i> Add
                </button>
            </div>
        </div>

        <div class="bulk-actions d-none mb-2" id="bulkActionContainer">
            <button class="btn btn-sm btn-info" id="selectAllBtn">Select All</button>
            <button class="btn btn-sm btn-danger" id="deleteSelectedBtn">Delete Selected</button>
        </div>

        <div class="table-responsive d-none d-md-block">
            <table class="table table-bordered" id="fineTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="masterCheckbox"></th>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Fine (₹/Days)</th>
                        <th>Penalty (₹/Days)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center">Loading data...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-block d-md-none" id="mobileCardsContainer">
            <div class="text-center p-3 text-muted">Loading data...</div>
        </div>
    </div>

   @php
    $formHtml = '
    <form id="finePenaltyForm">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="fw-bold">User Type <span class="text-danger">*</span></label>
                <select class="form-select form-control" disabled>
                    <option>Employee</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="fw-bold">Company <span class="text-danger">*</span></label>
                <select name="company_id" id="company_id" class="form-control selectpicker" data-live-search="true" title="Select Company" required></select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="fw-bold">Branch (Optional for HO)</label>
                <select name="branch_id[]" id="branch_id" class="form-control selectpicker" multiple data-live-search="true" data-actions-box="true" title="Select Branch"></select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="fw-bold">Department</label>
                <select name="department_id[]" id="department_id" class="form-control selectpicker" multiple data-live-search="true" data-actions-box="true" title="Select Department"></select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="fw-bold">Designation</label>
                <select name="designation_id[]" id="designation_id" class="form-control selectpicker" multiple data-live-search="true" data-actions-box="true" title="Select Designation"></select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="fw-bold">Applicant Name <span class="text-danger">*</span></label>
                <select name="employee_ids[]" id="employee_id" class="form-control selectpicker" multiple data-live-search="true" data-actions-box="true" title="Select Employee" required></select>
            </div>
            
            <hr class="mt-2 mb-3">

            <div class="col-md-3 mb-3">
                <label>Fine in Rupees</label>
                <input type="number" name="fine_rupees" class="form-control" placeholder="₹">
            </div>
            <div class="col-md-3 mb-3">
                <label>Fine Days</label>
                <select name="fine_days" class="form-control">
                    <option value="">None</option>
                    <option value="Quarter Day">Quarter Day</option>
                    <option value="Half Day">Half Day</option>
                    <option value="Full Day">Full Day</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label>Penalty in Rupees</label>
                <input type="number" name="penalty_rupees" class="form-control" placeholder="₹">
            </div>
            <div class="col-md-3 mb-3">
                <label>Penalty Days</label>
                <select name="penalty_days" class="form-control">
                    <option value="">None</option>
                    <option value="Quarter Day">Quarter Day</option>
                    <option value="Half Day">Half Day</option>
                    <option value="Full Day">Full Day</option>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label class="fw-bold">Date <span class="text-danger">*</span></label>
                <input type="date" name="date" class="form-control" required>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="fw-bold">Attach Proof (Images Only)</label>
                <input type="file" id="proof_file" class="form-control" accept="image/*" multiple>
                <input type="hidden" name="proof_media_ids" id="proof_media_ids">
                <small id="proof_status" class="form-text mt-1 fw-bold"></small>
                
                <div id="image_previews" class="d-flex flex-wrap mt-2 gap-2"></div>
            </div>

            <div class="col-md-12 mb-2">
                <label class="fw-bold">Description / Remark</label>
                <textarea name="description" id="descriptionEditor" class="form-control tinymce"></textarea>
            </div>
        </div>
        <button type="submit" id="submitBtn" class="btn btn-success mt-3 w-100"><i class="fas fa-save"></i> Save Fine/Penalty</button>
    </form>
    ';
@endphp

    <div class="modal fade" id="fineModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add/Edit Fine/Penalty</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">{!! $formHtml !!}</div>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="fineOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Add/Edit Fine/Penalty</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">{!! $formHtml !!}</div>
    </div>
@endsection


  


@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/{{ env('TINYMCE_API_KEY') }}/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    </script>

    <script>
        $(document).ready(function() {
            // 1. Initialize Plugins
            
            tinymce.init({
                selector: '.tinymce',
                height: 200
            });

            const token = localStorage.getItem('admin_token') || localStorage.getItem('emp_token');
            const contextRole = (localStorage.getItem('role_level') || 'employee').toLowerCase();

            // Global permissions object
            let permissions = {};

            // 2. Fetch User Profile First (BULLETPROOF GOD MODE)
            let meUrl = localStorage.getItem('admin_token') ? '/api/v1/admin/auth/me' : '/api/v1/employee/auth/me';

            $.ajax({
                url: meUrl,
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(res) {
                    let user = res.data ? res.data : res;
                    let email = user.email || '';
                    let userPerms = user.permissions || [];

                    // Check Explicit Email or LocalStorage Role
                    let isGodUI = ['admin@jankivilla.com', 'superadmin@example.com',
                            'vedprakash@infoera.in'
                        ].includes(email) || ['developer', 'admin', 'superadmin', 'super_admin', 'ceo']
                        .includes(contextRole);

                    // Assign proper RBAC
                    permissions = {
                        view: isGodUI || userPerms.includes('fine_view'),
                        edit: isGodUI || userPerms.includes('fine_edit'),
                        delete: isGodUI || userPerms.includes('fine_delete'),
                        print: isGodUI || userPerms.includes('fine_print'),
                        approve: isGodUI || userPerms.includes('fine_approve'),
                        reject: isGodUI || userPerms.includes('fine_rej'),
                        remark: isGodUI || userPerms.includes('fine_remark'),
                        add: isGodUI || userPerms.includes('fine_add_direct')
                    };

                    // Toggle Add Buttons
                    if (permissions.add) {
                        $('#addBtnDesktop').removeClass('d-none').addClass('d-none d-md-block');
                        $('#addBtnMobile').removeClass('d-none').addClass('d-md-none');
                    }

                    // Init Data Loading *after* permissions are set
                    loadCompanies();
                    loadData();
                },
                error: function(err) {
                    console.error("Auth Me Error: ", err);
                    $('#fineTable tbody').html(
                        '<tr><td colspan="7" class="text-center text-danger">Authentication Failed. Please reload.</td></tr>'
                        );
                }
            });

           $('.selectpicker').selectpicker();

            // 2. Load Companies
    function loadCompanies() {
        $.ajax({
            url: '/api/v1/get-active-companies',
            headers: { 'Authorization': 'Bearer ' + token },
            success: function(res) {
                let items = res.data ? res.data : res;
                if(typeof items === 'string') items = JSON.parse(items);

                let options = ''; // 'title' attribute already added in HTML, so no need for blank option
                if(Array.isArray(items)) {
                    items.forEach(c => options += `<option value="${c.id}">${c.company_name}</option>`);
                }
                
                $('#company_id').html(options).selectpicker('refresh');
                
                if (contextRole === 'director') {
                    let dirCompanyId = localStorage.getItem('company_id'); 
                    $('#company_id').val(dirCompanyId).prop('disabled', true).selectpicker('refresh').trigger('change');
                }
            }
        });
    }

    // 3. Cascading Dropdowns
    $('#company_id').on('change', function() {
        let companyId = $(this).val();
        let companyName = $(this).find("option:selected").text(); 
        
        if(!companyId) {
            $('#branch_id, #department_id, #designation_id, #employee_id').html('').selectpicker('refresh');
            return;
        }

        $.post('/api/v1/get-branches-by-companies', { company_ids: [companyId] }, function(res) {
            let items = res.data ? res.data : res;
            let options = `<option value="">${companyName} (Head Office)</option>`;
            if(Array.isArray(items)) {
                items.forEach(b => options += `<option value="${b.id}">${b.branch_name}</option>`);
            }
            $('#branch_id').html(options).selectpicker('refresh').trigger('change'); 
        });
    });

    $('#branch_id').on('change', function() {
        let branchIds = $(this).val() || []; 
        let companyId = $('#company_id').val();
        if(!companyId) return;

        $.post('/api/v1/get-filtered-departments', { branch_ids: branchIds, company_id: companyId }, function(res) {
            let items = res.data ? res.data : res;
            let options = '';
            if(Array.isArray(items)) items.forEach(d => options += `<option value="${d.id}">${d.department_name}</option>`);
            $('#department_id').html(options).selectpicker('refresh').trigger('change');
        });
    });

    $('#department_id').on('change', function() {
        let deptIds = $(this).val() || [];
        if(deptIds.length === 0) {
            $('#designation_id, #employee_id').html('').selectpicker('refresh');
            return;
        }

        $.post('/api/v1/get-filtered-designations', { department_ids: deptIds }, function(res) {
            let items = res.data ? res.data : res;
            let options = '';
            if(Array.isArray(items)) {
                items.forEach(d => options += `<option value="${d.id}">${d.designation_name}</option>`);
            }
            $('#designation_id').html(options).selectpicker('refresh').trigger('change');
        });
    });

    $('#designation_id').on('change', function() {
        let desigIds = $(this).val() || [];
        if(desigIds.length === 0) {
            $('#employee_id').html('').selectpicker('refresh');
            return;
        }

        $.post('/api/v1/get-filtered-employees', { designation_ids: desigIds }, function(res) {
            let items = res.data ? res.data : res;
            let options = '';
            if(Array.isArray(items)) {
                items.forEach(e => options += `<option value="${e.id}">${e.full_name} (${e.member_id})</option>`);
            }
            $('#employee_id').html(options).selectpicker('refresh');
        });
    });

    // 4. Update Reset Form (Important for clearing out the UI)
    function resetForm() {
        $('#finePenaltyForm')[0].reset();
        tinymce.get("descriptionEditor").setContent('');
        $('#company_id, #employee_id').prop('disabled', false);
        
        // Refresh all pickers
        $('.selectpicker').selectpicker('refresh'); 

        uploadedMediaIds = [];
        $('#proof_media_ids').val('');
        $('#image_previews').empty();
        $('#proof_status').text('');
        editId = null;
    }

            // 3. 🔥 MULTIPLE IMAGE PREVIEW & UPLOAD LOGIC 🔥
    let uploadedMediaIds = [];

    $('#proof_file').on('change', async function() {
        let files = this.files;
        if(files.length === 0) return;

        $('#submitBtn').prop('disabled', true);
        $('#proof_status').text('Uploading files...').removeClass('text-danger text-success').addClass('text-info');

        // Loop through all selected files
        for(let i = 0; i < files.length; i++) {
            let file = files[i];
            let formData = new FormData();
            formData.append('file', file);

            try {
                let res = await $.ajax({
                    url: '/api/v1/media/upload',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: { 'Authorization': 'Bearer ' + token }
                });

                if(res.status === 'success') {
                    let mediaId = res.data.id;
                    let fileUrl = '/' + res.data.file_path; // Server se return hua URL
                    uploadedMediaIds.push(mediaId);

                    // Add Preview Box HTML with Cut 'X' button
                    $('#image_previews').append(`
                        <div class="position-relative preview-box border rounded p-1" data-id="${mediaId}" style="width: 80px; height: 80px;">
                            <img src="${fileUrl}" class="img-fluid rounded" style="width: 100%; height: 100%; object-fit: cover;">
                            <button type="button" class="btn btn-danger btn-sm position-absolute remove-media-btn" style="top: -5px; right: -5px; padding: 0 5px; font-size: 12px; border-radius: 50%;">&times;</button>
                        </div>
                    `);
                }
            } catch (err) {
                console.error("Upload failed for file: ", file.name);
            }
        }

        // Update hidden input with comma separated IDs
        $('#proof_media_ids').val(uploadedMediaIds.join(','));
        $('#proof_status').text('All Uploads Complete').removeClass('text-info text-danger').addClass('text-success');
        $('#submitBtn').prop('disabled', false);
        
        // Reset input field taaki user aur nayi files chune sake
        $(this).val(''); 
    });

    // 4. Remove Image (Cut button logic)
    $(document).on('click', '.remove-media-btn', function() {
        let box = $(this).closest('.preview-box');
        let idToRemove = box.data('id');
        
        // Array me se remove karna
        uploadedMediaIds = uploadedMediaIds.filter(id => id != idToRemove);
        $('#proof_media_ids').val(uploadedMediaIds.join(','));
        
        // UI se hide karna
        box.fadeOut(300, function() { $(this).remove(); });
    });

    // 5. Update Reset Form function (Modal close hone pe images clear karne ke liye)
    function resetForm() {
        $('#finePenaltyForm')[0].reset();
        tinymce.get("descriptionEditor").setContent('');
        $('#company_id, #employee_id').prop('disabled', false);
        
        // Clear Media
        uploadedMediaIds = [];
        $('#proof_media_ids').val('');
        $('#image_previews').empty();
        $('#proof_status').text('');
        
        editId = null;
    }

            

            // 4. Load Data for DataTable
            function loadData() {
                $.ajax({
                    url: '/api/v1/fine-penalties',
                    method: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + token
                    },
                    success: function(res) {
                        if (typeof res === 'string') res = JSON.parse(res);
                        let dataArray = res.data ? res.data : res;
                        if (!Array.isArray(dataArray)) dataArray = [];

                        if (dataArray.length === 0) {
                            $('#fineTable tbody').html(
                                '<tr><td colspan="7" class="text-center text-muted">No records found.</td></tr>'
                                );
                            $('#mobileCardsContainer').html(
                                '<div class="text-center p-3 text-muted">No records found.</div>');
                            return;
                        }

                        let tbody = '';
                        let cards = '';

                        dataArray.forEach((item) => {
                            let empName = item.employee ?
                                `${item.employee.name} (${item.employee.member_id})` : 'N/A';
                            let fineText = item.fine_rupees ? `₹${item.fine_rupees}` : (item
                                .fine_days || '-');
                            let penaltyText = item.penalty_rupees ? `₹${item.penalty_rupees}` :
                                (item.penalty_days || '-');

                            let statusBadge = item.status === 'Approved' ?
                                '<span class="badge bg-success">Approved</span>' :
                                item.status === 'Rejected' ?
                                '<span class="badge bg-danger">Rejected</span>' :
                                '<span class="badge bg-warning text-dark">Pending</span>';

                            // Actions Builder
                            let actions = '<div class="btn-group btn-group-sm">';
                            if (permissions.view) actions +=
                                `<button class="btn btn-info btn-view" data-id="${item.id}" title="View"><i class="fas fa-eye"></i></button>`;
                            if (permissions.print) actions +=
                                `<a href="/fine-penalties/print/${item.id}" target="_blank" class="btn btn-secondary" title="Print"><i class="fas fa-print"></i></a>`;

                            if (item.status === 'Pending') {
                                if (permissions.approve) actions +=
                                    `<button class="btn btn-success btn-approve" data-id="${item.id}" title="Approve"><i class="fas fa-check"></i></button>`;
                                if (permissions.reject) actions +=
                                    `<button class="btn btn-danger btn-reject" data-id="${item.id}" title="Reject"><i class="fas fa-times"></i></button>`;
                                if (permissions.edit) actions +=
                                    `<button class="btn btn-primary btn-edit" data-id="${item.id}" title="Edit"><i class="fas fa-edit"></i></button>`;
                            }
                            if (permissions.remark) actions +=
                                `<button class="btn btn-warning btn-remark" data-id="${item.id}" title="Add Remark"><i class="fas fa-comment"></i></button>`;
                            if (permissions.delete) actions +=
                                `<button class="btn btn-dark btn-delete" data-id="${item.id}" title="Delete"><i class="fas fa-trash"></i></button>`;
                            actions += '</div>';

                            let checkboxHtml = permissions.delete ?
                                `<input type="checkbox" class="row-checkbox form-check-input" value="${item.id}">` :
                                '';

                            // Desktop Table Row
                            tbody += `<tr>
                        <td>${checkboxHtml}</td>
                        <td>${item.date}</td>
                        <td>${empName}</td>
                        <td>${fineText}</td>
                        <td>${penaltyText}</td>
                        <td>${statusBadge}</td>
                        <td>${actions}</td>
                    </tr>`;

                            // Mobile Card
                            cards += `
                    <div class="card mb-3 shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>${checkboxHtml} ${item.date}</strong>
                                ${statusBadge}
                            </div>
                            <h6 class="card-title text-primary">${empName}</h6>
                            <p class="mb-1 text-muted small"><strong>Fine:</strong> ${fineText}</p>
                            <p class="mb-2 text-muted small"><strong>Penalty:</strong> ${penaltyText}</p>
                            <div class="d-flex justify-content-end">${actions}</div>
                        </div>
                    </div>`;
                        });

                        $('#fineTable tbody').html(tbody);
                        $('#mobileCardsContainer').html(cards);
                    },
                    error: function(err) {
                        console.error("API Fetch Error:", err);
                    }
                });
            }

            // 5. Action Listeners
            $(document).on('click', '.btn-approve', function() {
                if (!confirm('Approve this record?')) return;
                $.post(`/api/v1/fine-penalties/${$(this).data('id')}/approve`, {}, function(res) {
                    alert(res.message);
                    loadData();
                });
            });

            $(document).on('click', '.btn-reject', function() {
                if (!confirm('Reject this record?')) return;
                $.post(`/api/v1/fine-penalties/${$(this).data('id')}/reject`, {}, function(res) {
                    alert(res.message);
                    loadData();
                });
            });

            $(document).on('click', '.btn-delete', function() {
                if (!confirm('Delete this record forever?')) return;
                $.post('/api/v1/fine-penalties/bulk-delete', {
                    ids: [$(this).data('id')]
                }, function(res) {
                    alert('Deleted Successfully');
                    loadData();
                });
            });

            $(document).on('click', '.btn-remark', function() {
                let remark = prompt("Enter remark:");
                if (remark) {
                    $.post(`/api/v1/fine-penalties/${$(this).data('id')}/remark`, {
                        description: remark
                    }, function(res) {
                        alert('Remark saved!');
                        loadData();
                    });
                }
            });

            // 6. Edit & Form Submit
            let editId = null;

            $(document).on('click', '.btn-edit', function() {
                editId = $(this).data('id');
                $.get(`/api/v1/fine-penalties/${editId}`, function(res) {
                    $('#company_id').html(
                        `<option value="${res.company_id}">${res.company.company_name}</option>`
                        ).prop('disabled', true);
                    $('#employee_id').html(
                            `<option value="${res.employee_id}">${res.employee.name}</option>`)
                        .prop('disabled', true);

                    $('input[name="fine_rupees"]').val(res.fine_rupees);
                    $('select[name="fine_days"]').val(res.fine_days);
                    $('input[name="penalty_rupees"]').val(res.penalty_rupees);
                    $('select[name="penalty_days"]').val(res.penalty_days);
                    $('input[name="date"]').val(res.date);

                    if (res.description) tinymce.get("descriptionEditor").setContent(res
                        .description);

                    if ($(window).width() < 768) $('#fineOffcanvas').offcanvas('show');
                    else $('#fineModal').modal('show');
                });
            });

            $('#finePenaltyForm').off('submit').on('submit', function(e) {
                e.preventDefault();
                tinymce.triggerSave();

                let formData = $(this).serialize();
                if ($('#company_id').prop('disabled')) formData += '&company_id=' + $('#company_id').val();

                let url = editId ? `/api/v1/fine-penalties/${editId}` : '/api/v1/fine-penalties';
                let method = editId ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    method: method,
                    data: formData,
                    headers: {
                        'Authorization': 'Bearer ' + token
                    },
                    success: function(res) {
                        $('#fineModal').modal('hide');
                        $('#fineOffcanvas').offcanvas('hide');
                        resetForm();
                        loadData();
                        alert(res.message || 'Saved successfully!');
                    }
                });
            });

            function resetForm() {
                $('#finePenaltyForm')[0].reset();
                tinymce.get("descriptionEditor").setContent('');
                $('#company_id, #employee_id').prop('disabled', false);
                editId = null;
            }

            $('#fineModal, #fineOffcanvas').on('hidden.bs.modal hidden.bs.offcanvas', function() {
                resetForm();
            });

            // 7. Checkbox Actions
            $('#masterCheckbox').click(function() {
                $('.row-checkbox').prop('checked', $(this).prop('checked')).trigger('change');
            });

            $(document).on('change', '.row-checkbox', function() {
                if ($('.row-checkbox:checked').length > 0) $('#bulkActionContainer').removeClass('d-none');
                else $('#bulkActionContainer').addClass('d-none');
            });

            $('#selectAllBtn').click(function() {
                $('.row-checkbox').prop('checked', true).trigger('change');
            });

            $('#deleteSelectedBtn').click(function() {
                let ids = [];
                $('.row-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });
                if (confirm('Are you sure you want to delete selected records?')) {
                    $.post('/api/v1/fine-penalties/bulk-delete', {
                        ids: ids
                    }, function() {
                        loadData();
                        $('#bulkActionContainer').addClass('d-none');
                        $('#masterCheckbox').prop('checked', false);
                    });
                }
            });
        });
    </script>
@endpush
