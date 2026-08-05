@extends('layout.app')

@section('content')
    <div class="container-fluid px-1 px-md-3 py-2">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-building me-2"></i>Company Management</h5>

            <button id="addCompanyBtn" class="btn btn-primary btn-sm shadow-sm fw-bold d-none" onclick="openAddModal()">
                <i class="fas fa-plus-circle me-1"></i> Add Company
            </button>
        </div>
    </div>

    <div class="d-block d-md-none mb-3 px-1 px-md-3">
        <div class="d-flex gap-2">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0" id="mobileSearch"
                    placeholder="Search companies...">
            </div>
            <button class="btn btn-success shadow-sm px-3 d-none" id="mobileExcelBtn" title="Download Excel">
    <i class="fas fa-file-excel"></i>
</button>
        </div>
    </div>

    <div class="container-fluid px-1 px-md-3">
        <div class="d-none mb-3" id="bulkActions">
            <button class="btn btn-warning btn-sm shadow-sm fw-bold me-2" id="selectAllBtn">
                <i class="fas fa-check-square me-1"></i> Select All
            </button>
            <button class="btn btn-danger btn-sm shadow-sm fw-bold" id="deleteSelectedBtn">
                <i class="fas fa-trash me-1"></i> Delete Selected
            </button>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="companyDataTable" style="width: 100%;">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-center" style="width: 40px;">
                                    <input type="checkbox" class="form-check-input" id="checkAllDesktop">
                                </th>
                                <th>Sl No</th>
                                <th>ID</th>
                                <th>Prefix</th>
                                <th>Company Name</th>
                                <th>Parent Company</th>
                                <th>Directors</th>
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

        <div class="d-md-none" id="mobileCardsContainer">
            <div class="text-center py-5" id="mobileLoader">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted small">Loading companies...</p>
            </div>
        </div>
    </div>

    <div class="modal fade" id="companyModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold text-primary" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add
                        Company</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="companyForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body p-4">
                        <input type="hidden" id="c_id" name="id">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold">Company Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-primary" id="c_company_name"
                                    name="company_name" placeholder="e.g. Amitabh Developers" required>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold">Company Logo</label>
                                <input type="file" class="form-control" id="c_company_logo" name="company_logo"
                                    accept="image/*">

                                <div id="logoPreviewBox" class="mt-2 d-none position-relative d-inline-block">
                                    <img id="logoPreviewImg" src="" class="border rounded shadow-sm"
                                        style="width: 70px; height: 70px; object-fit: cover;">
                                    <button type="button" id="clearLogoBtn"
                                        class="btn btn-danger position-absolute top-0 start-100 translate-middle rounded-circle shadow"
                                        style="width: 22px; height: 22px; padding: 0; font-size: 12px; line-height: 1;"
                                        title="Remove Logo">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>

                                <input type="hidden" id="remove_logo_flag" name="remove_logo_flag" value="0">
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold">CIN Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-primary text-uppercase" id="c_cin_no"
                                    name="cin_no" placeholder="CIN Number" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold">ISO Number</label>
                                <input type="text" class="form-control text-uppercase" id="c_iso_no" name="iso_no"
                                    placeholder="Optional">
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold">Trademark</label>
                                <input type="text" class="form-control" id="c_trademark" name="trademark"
                                    placeholder="Optional">
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold">Logo Reg. No.</label>
                                <input type="text" class="form-control text-uppercase" id="c_logo_reg_no"
                                    name="logo_reg_no" placeholder="Optional">
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold">Prefix Code (Short Name) <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control border-primary text-uppercase"
                                    id="c_company_code" name="company_code" placeholder="e.g. ABD" maxlength="10"
                                    required>
                                <small class="text-muted">Will be used to generate branch/employee IDs.</small>
                            </div>

                            <div class="col-md-6 mt-4 border-top pt-3">
                                <h6 class="fw-bold mb-3">Assign Directors/CEOs</h6>
                                <div id="directorRows"></div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                                    onclick="addDirectorRow()">
                                    <i class="fas fa-plus"></i> Add Director Row
                                </button>
                            </div>

                            <div class="col-md-12">
                                <label class="small fw-bold">Parent Company</label>
                                <select class="form-select" id="c_parent_id" name="parent_id">
                                    <option value="">-- None (Master Company) --</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold">Phone</label>
                                <input type="text" class="form-control" id="c_phone" name="phone"
                                    placeholder="Contact Number">
                            </div>

                            <!-- 🔥 NAYA: WhatsApp Field -->
<div class="col-md-6">
    <label class="small fw-bold">WhatsApp No</label>
    <input type="text" class="form-control" id="c_whatsapp_no" name="whatsapp_no" placeholder="WhatsApp Number">
</div>
                            <div class="col-md-6">
                                <label class="small fw-bold">Email</label>
                                <input type="email" class="form-control" id="c_email" name="email"
                                    placeholder="Email Address">
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold">State</label>
                                <input type="text" class="form-control" id="c_state" name="state"
                                    placeholder="e.g. Bihar">
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold">District</label>
                                <input type="text" class="form-control" id="c_district" name="district"
                                    placeholder="e.g. Darbhanga">
                            </div>

                            <div class="col-md-8">
                                <label class="small fw-bold">Address</label>
                                <input type="text" class="form-control" id="c_address" name="address"
                                    placeholder="Full Address">
                            </div>

                            <div class="col-md-12 mt-2">
                                <label class="small fw-bold text-primary"><i class="fas fa-map-marker-alt me-1"></i> Google Map Location (Link or Iframe)</label>
                                <textarea class="form-control border-primary-subtle" id="c_map_url" name="map_url" rows="2" placeholder='Paste Google Map share link or <iframe> embed code here...'></textarea>
                                <small class="text-muted" style="font-size: 11px;">System will automatically extract Latitude & Longitude from this link.</small>
                            </div>

                            <div class="col-md-4">
                                <label class="small fw-bold">GST No (Optional)</label>
                                <input type="text" class="form-control text-uppercase" id="c_gst_no" name="gst_no"
                                    placeholder="GSTIN">
                            </div>

                            <div class="col-md-12" id="statusFieldContainer">
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

    <div class="modal fade" id="viewCompanyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: #1a2a40;">
                    <h6 class="modal-title fw-bold"><i class="fas fa-building me-2"></i> Company Full Profile</h6>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-0">
                    <div class="text-center p-4 border-bottom position-relative">
                        <div id="v_logo_container"
                            class="mx-auto mb-3 d-flex align-items-center justify-content-center text-white rounded-circle shadow-sm"
                            style="width: 85px; height: 85px; font-size: 28px; font-weight: bold; overflow: hidden; background-color: #1a2a40;">
                        </div>

                        <h5 class="fw-bold mb-1 text-uppercase text-dark">
                            <span id="v_name_display"></span>
                            <span id="v_status_display" class="ms-1"></span>
                        </h5>

                        <div class="text-primary fw-bold mb-2" id="v_code_display" style="font-size: 15px;"></div>

                        <div class="text-muted small fw-medium text-uppercase">
                            <i class="fas fa-sitemap text-secondary me-1"></i> <span id="v_parent_display"></span>
                            &nbsp;&nbsp;|&nbsp;&nbsp;
                            <i class="fas fa-map-marker-alt text-secondary me-1"></i> <span
                                id="v_district_state_top"></span>
                        </div>
                    </div>

                    <div class="px-4 py-2 fw-bold border-bottom"
                        style="background-color: #f8f9fa; color: #5a6268; font-size: 13px; text-transform: uppercase;">
                        Contact & Address
                    </div>
                    <div class="p-4 pt-3 row g-3">
                       <div class="col-md-4">
    <div class="small fw-bold text-dark mb-1">Mobile:</div>
    <div class="text-muted" id="v_phone_display"></div>
</div>
<!-- 🔥 NAYA: WhatsApp View -->
<div class="col-md-4">
    <div class="small fw-bold text-dark mb-1">WhatsApp:</div>
    <div class="text-muted" id="v_whatsapp_display"></div>
</div>
<div class="col-md-4">
    <div class="small fw-bold text-dark mb-1">Email:</div>
    <div class="text-muted" id="v_email_display"></div>
</div>
                        <div class="col-md-12">
                            <div class="small fw-bold text-dark mb-1">Address:</div>
                            <div class="text-muted" id="v_address_display"></div>
                        </div>
                    </div>

                    <div class="px-4 py-2 fw-bold border-bottom border-top"
                        style="background-color: #f8f9fa; color: #5a6268; font-size: 13px; text-transform: uppercase;">
                        Registration Details
                    </div>
                    <div class="p-4 pt-3 row g-3">
                        <div class="col-md-6">
                            <div class="small fw-bold text-dark mb-1">CIN Number:</div>
                            <div class="text-muted text-uppercase fw-medium" id="v_cin_display"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small fw-bold text-dark mb-1">GST No:</div>
                            <div class="text-muted text-uppercase fw-medium" id="v_gst_display"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small fw-bold text-dark mb-1">ISO Number:</div>
                            <div class="text-muted text-uppercase" id="v_iso_display"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small fw-bold text-dark mb-1">Trademark:</div>
                            <div class="text-muted" id="v_trademark_display"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small fw-bold text-dark mb-1">Logo Reg. No:</div>
                            <div class="text-muted text-uppercase" id="v_logo_reg_display"></div>
                        </div>
                    </div>
                    <div class="px-4 py-2 fw-bold border-bottom border-top mt-3" style="background-color: #e9ecef; color: #495057; font-size: 13px; text-transform: uppercase;">
                        <i class="fas fa-map text-secondary me-1"></i> Location Map
                    </div>
                    <div class="p-4 pt-3 text-center">
                        <div id="v_map_display" class="w-100 rounded border overflow-hidden shadow-sm" style="min-height: 250px; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            <span class="text-muted"><i class="fas fa-map-marked-alt fa-2x mb-2 text-light"></i><br>Loading Map...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Global object for permissions
        let userPermissions = {
            canEdit: false,
            canDelete: false,
            canPrint: false
        };

        let table;
       const apiToken = localStorage.getItem('admin_token') || localStorage.getItem('emp_token') || localStorage.getItem('token');
        let selectedCompanies = [];

        // =======================================================
        // 🔄 1. DYNAMIC BOARD MEMBER DROPDOWNS
        // =======================================================
        window.addDirectorRow = function(personId = '', role = 'Director') {
            let uniqueId = Math.random().toString(36).substr(2, 9);
            let row = `
                <div class="row g-2 mb-2 director-row border p-2 rounded bg-light">
                    <div class="col-4">
                        <label class="small fw-bold">Select Role</label>
                        <select class="form-select role-select" name="roles[]" onchange="handleRoleChange(this, '${uniqueId}')">
                            <option value="Director" ${role == 'Director' ? 'selected' : ''}>Director</option>
                            <option value="CEO" ${role == 'CEO' ? 'selected' : ''}>CEO</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold">Select Person</label>
                        <select class="form-control person-select" id="person_${uniqueId}" name="persons[]" required>
                            <option value="">Select ${role}</option>
                        </select>
                    </div>
                    <div class="col-2 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="$(this).closest('.director-row').remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            $('#directorRows').append(row);

            let personDropdown = $(`#person_${uniqueId}`);
            personDropdown.select2({
                dropdownParent: $('#companyModal'),
                width: '100%',
                placeholder: `Select ${role}`
            });

            loadPersonsDropdown(personDropdown, role, personId);
        }

        window.handleRoleChange = function(element, uniqueId) {
            let selectedRole = $(element).val();
            let personDropdown = $(`#person_${uniqueId}`);
            personDropdown.empty().trigger('change');
            personDropdown.select2({
                dropdownParent: $('#companyModal'),
                width: '100%',
                placeholder: `Select ${selectedRole}`
            });
            loadPersonsDropdown(personDropdown, selectedRole, '');
        }

        window.loadPersonsDropdown = function(dropdown, role, selectedId) {
            let apiUrl = role === 'CEO' ? '/api/v1/super-admins?length=-1' : '/api/v1/directors/active';
            dropdown.html(`<option value="">Loading...</option>`).trigger('change');

            $.get(apiUrl, function(res) {
                let opts = `<option value="">Select ${role}</option>`;
                if (res.data) {
                    res.data.forEach(d => {
                        let displayId = role === 'CEO' ? d.ceo_id : d.director_id;
                        opts += `<option value="${d.id}" ${d.id == selectedId ? 'selected' : ''}>
                                    ${d.full_name} (${displayId})
                                 </option>`;
                    });
                }
                dropdown.html(opts).trigger('change');
            }).fail(function() {
                dropdown.html(`<option value="">Error Loading Data</option>`).trigger('change');
            });
        }

        // =======================================================
        // 🚀 2. DOCUMENT READY & DATATABLES INITIALIZATION
        // =======================================================
        $(document).ready(function() {
            table = $('#companyDataTable').DataTable({
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
               buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export Excel',
                    // 🔥 NAYA: 'd-none' class initially add kar di hai 
                    className: 'btn btn-success btn-sm shadow-sm rounded-3 d-none buttons-excel',
                    title: 'Group Of Companies', 
                    exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8] }
                }],
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/companies',
                    type: 'GET',
                    headers: { 'Authorization': 'Bearer ' + apiToken },
                    dataSrc: function (json) {
                        if (json.permissions) {
                            userPermissions.canEdit = json.permissions.can_edit;
                            userPermissions.canDelete = json.permissions.can_delete;
                            userPermissions.canPrint = json.permissions.can_print;

                            let addBtn = $('#addCompanyBtn');
                            
                            // 🔥 Button Hierarchy Override Logic Fix 🔥
                            // Agar direct power hai, toh request wali classes completely remove karni hongi
                            if (json.permissions.can_add_direct) {
                                addBtn.html('<i class="fas fa-plus-circle me-1"></i> Add Company');
                                addBtn.removeClass('d-none btn-warning text-dark').addClass('btn-primary text-white');
                                addBtn.attr('onclick', 'openAddModal("direct")');
                            } else if (json.permissions.can_add_request) {
                                addBtn.html('<i class="fas fa-paper-plane me-1"></i> Request Company');
                                addBtn.removeClass('d-none btn-primary text-white').addClass('btn-warning text-dark');
                                addBtn.attr('onclick', 'openAddModal("request")');
                            } else {
                                addBtn.addClass('d-none');
                            }

                            // 🔥 Excel Export Visibility Logic
                            if (json.permissions.can_export) {
                                $('.buttons-excel').removeClass('d-none');
                                $('#mobileExcelBtn').removeClass('d-none');
                            } else {
                                $('.buttons-excel').addClass('d-none');
                                $('#mobileExcelBtn').addClass('d-none');
                            }
                        }
                        return json.data;
                    }
                },
                order: [
                    [2, 'desc']
                ],
                columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data, type, row) {
                            return `<input type="checkbox" class="form-check-input row-checkbox" value="${row.id}">`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'id',
                        className: 'ps-3 fw-bold text-primary'
                    },
                    {
                        data: 'company_code'
                    },
                    {
                        data: 'company_name',
                        className: 'fw-bold'
                    },
                    {
                        data: 'parent_name'
                    },
                    {
                        data: 'directors_html',
                        render: d => `<div style="font-size:12px;">${d}</div>`
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return row.district + ', ' + row.state;
                        }
                    },
                    {
                        data: 'status',
                        render: function(data) {
                            // 🔥 Status rendering updated for Pending
                            if (data === 'active')
                            return '<span class="badge bg-success">Active</span>';
                            if (data === 'inactive')
                            return '<span class="badge bg-danger">Inactive</span>';
                            if (data === 'pending')
                            return '<span class="badge bg-warning text-dark shadow-sm">Pending Request</span>';
                            return data;
                        }
                    },
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data) {
                            let buttons = '';

                            // if (userPermissions.canPrint) {
                            //     buttons +=
                            //         `<button onclick="printCompany(${data})" class="btn btn-sm btn-light border text-secondary" title="Print"><i class="fas fa-print"></i></button> `;
                            // }

                            buttons +=
                                `<button onclick="viewCompany(${data})" class="btn btn-sm btn-light border text-info" title="View"><i class="fas fa-eye"></i> View </button> `;

                            if (userPermissions.canEdit) {
                                buttons +=
                                    `<button onclick="editCompany(${data})" class="btn btn-sm btn-light border text-success" title="Edit"><i class="fas fa-edit"></i> Edit </button>`;
                            }
                            return buttons;
                        }
                    }
                ],
                drawCallback: function(settings) {
                    renderMobileCards(settings.json.data, settings);
                    bindCheckboxEvents();
                }
            });

            loadParentCompanies();

            $('#mobileSearch').on('keyup', function() {
                table.search(this.value).draw();
            });
            $('#mobileExcelBtn').on('click', function() {
                $('.buttons-excel').click();
            });

            // =======================================================
            // 🛡️ 3. FORM SUBMISSION
            // =======================================================
            $('#companyForm').on('submit', function(e) {
                e.preventDefault();

                let boardData = [];
                $('.director-row').each(function() {
                    let role = $(this).find('.role-select').val();
                    let selectedPersonId = $(this).find('.person-select').val();

                    if (selectedPersonId) {
                        boardData.push({
                            role: role,
                            director_id: role === 'Director' ? selectedPersonId : null,
                            ceo_id: role === 'CEO' ? selectedPersonId : null
                        });
                    }
                });

                let id = $('#c_id').val();
                let url = id ? `/api/v1/companies/${id}` : '/api/v1/companies';

                let formData = new FormData(this);
                formData.append('board_assignments', JSON.stringify(boardData));

                if (id) {
                    formData.append('_method', 'PUT');
                }

                $('#saveBtn').html('<span class="spinner-border spinner-border-sm"></span> Saving...').prop(
                    'disabled', true);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        $('#companyModal').modal('hide');
                        Swal.fire('Success', res.message, 'success');
                        table.ajax.reload();
                        loadParentCompanies();
                    },
                    error: function(err) {
                        Swal.fire('Error', err.responseJSON?.message || 'Something went wrong',
                            'error');
                    },
                    complete: function() {
                        // Reset button text appropriately
                        let btnText = $('#statusFieldContainer').hasClass('d-none') ?
                            'Save Request' : 'Save Company';
                        $('#saveBtn').html(btnText).prop('disabled', false);
                    }
                });
            });

            $('#c_company_logo').on('change', function(e) {
                let file = e.target.files[0];
                if (file) {
                    let reader = new FileReader();
                    reader.onload = function(event) {
                        $('#logoPreviewImg').attr('src', event.target.result);
                        $('#logoPreviewBox').removeClass('d-none');
                        $('#remove_logo_flag').val('0');
                    }
                    reader.readAsDataURL(file);
                }
            });

            $('#clearLogoBtn').on('click', function() {
                $('#c_company_logo').val('');
                $('#logoPreviewBox').addClass('d-none');
                $('#logoPreviewImg').attr('src', '');
                $('#remove_logo_flag').val('1');
            });

            // =======================================================
            // 🗑️ 4. BULK DELETE LOGIC
            // =======================================================
            $('#selectAllBtn').on('click', function() {
                $('.row-checkbox').prop('checked', true).trigger('change');
            });

            $('#deleteSelectedBtn').on('click', function() {
                if (selectedCompanies.length === 0) return;

                Swal.fire({
                    title: 'Are you sure?',
                    text: `You are about to delete ${selectedCompanies.length} selected companies!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete them!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/api/v1/bulk-delete',
                            type: 'POST',
                            headers: {
                                'Authorization': 'Bearer ' + apiToken
                            },
                            data: {
                                ids: selectedCompanies,
                                model: 'Company'
                            },
                            success: function(res) {
                                Swal.fire('Deleted!', res.message, 'success');
                                selectedCompanies = [];
                                $('#bulkActions').addClass('d-none');
                                $('#checkAllDesktop').prop('checked', false);
                                table.ajax.reload();
                            }
                        });
                    }
                });
            });
        });

        // =======================================================
        // 🔄 5. UI HELPER FUNCTIONS
        // =======================================================
        function bindCheckboxEvents() {
            $('.row-checkbox').off('change').on('change', function() {
                let val = $(this).val();
                if ($(this).is(':checked')) {
                    if (!selectedCompanies.includes(val)) selectedCompanies.push(val);
                } else {
                    selectedCompanies = selectedCompanies.filter(id => id !== val);
                }
                toggleBulkActions();
            });

            $('#checkAllDesktop').off('change').on('change', function() {
                let isChecked = $(this).is(':checked');
                $('.row-checkbox').prop('checked', isChecked).trigger('change');
            });
        }

        function toggleBulkActions() {
            // 🔥 Only show bulk delete if user has permission
            if (selectedCompanies.length > 0 && userPermissions.canDelete) {
                $('#bulkActions').removeClass('d-none');
            } else {
                $('#bulkActions').addClass('d-none');
                $('#checkAllDesktop').prop('checked', false);
            }
        }

        function loadParentCompanies() {
            $.ajax({
                url: '/api/v1/get-active-companies',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + apiToken
                },
                success: function(res) {
                    let options = '<option value="">-- None (Master Company) --</option>';
                    if (res.status === 'success') {
                        res.data.forEach(c => {
                            options +=
                                `<option value="${c.id}">${c.company_name} (${c.company_code})</option>`;
                        });
                    }
                    $('#c_parent_id').html(options);
                }
            });
        }

        // 🔥 Dynamic Modal Opening based on Mode (Request vs Direct)
        window.openAddModal = function(mode = 'direct') {
            $('#companyForm')[0].reset();
            $('#c_id').val('');
            $('#c_map_url').val('');
            $('#directorRows').empty();
            $('#logoPreviewBox').addClass('d-none');
            $('#remove_logo_flag').val('0');

            if (mode === 'request') {
                $('#modalTitle').html('<i class="fas fa-paper-plane me-2"></i>Request Company');
                $('#saveBtn').html('Save Request');
                $('#statusFieldContainer').addClass('d-none'); // Hide status from requester
            } else {
                $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add Company');
                $('#saveBtn').html('Save Company');
                $('#statusFieldContainer').removeClass('d-none'); // Show status for admin
            }

            $('#companyModal').modal('show');
        }

        function editCompany(id) {
            $.ajax({
                url: `/api/v1/companies/${id}`,
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + apiToken
                },
                success: function(res) {
                    let data = res.data;
                    $('#c_id').val(data.id);
                    $('#c_company_name').val(data.company_name);
                    $('#c_company_code').val(data.company_code);
                    $('#c_parent_id').val(data.parent_id);
                    $('#c_phone').val(data.phone);
                    $('#c_whatsapp_no').val(data.whatsapp_no);
                    $('#c_email').val(data.email);
                    $('#c_state').val(data.state);
                    $('#c_district').val(data.district);
                    $('#c_address').val(data.address);
                    $('#c_map_url').val('');
                    $('#c_gst_no').val(data.gst_no);
                    $('#c_status').val(data.status);
                    $('#c_cin_no').val(data.cin_no);
                    $('#c_iso_no').val(data.iso_no);
                    $('#c_trademark').val(data.trademark);
                    $('#c_logo_reg_no').val(data.logo_reg_no);

                    $('#directorRows').empty();
                    if (data.directors && data.directors.length > 0) {
                        data.directors.forEach(dir => {
                            addDirectorRow(dir.id, dir.pivot.role);
                        });
                    }
                    if (data.ceos && data.ceos.length > 0) {
                        data.ceos.forEach(ceo => {
                            addDirectorRow(ceo.id, ceo.pivot.role);
                        });
                    }

                    if (data.company_logo) {
                        $('#logoPreviewImg').attr('src', '/' + data.company_logo);
                        $('#logoPreviewBox').removeClass('d-none');
                    } else {
                        $('#logoPreviewBox').addClass('d-none');
                    }
                    $('#remove_logo_flag').val('0');
                    $('#c_company_logo').val('');

                    $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Company');
                    $('#saveBtn').html('Update Details');
                    $('#statusFieldContainer').removeClass('d-none'); // Ensure status is visible during edit
                    $('#companyModal').modal('show');
                }
            });
        }

        function viewCompany(id) {
            $.ajax({
                url: `/api/v1/companies/${id}`,
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + apiToken
                },
                success: function(res) {
                    let data = res.data;
                    if (data.company_logo) {
                        $('#v_logo_container').html(
                            `<img src="/${data.company_logo}" style="width:100%; height:100%; object-fit:cover;">`
                            ).css('background-color', 'transparent');
                    } else {
                        let initials = data.company_code ? data.company_code.substring(0, 3) : 'COM';
                        $('#v_logo_container').html(initials).css('background-color', '#1a2a40');
                    }

                    let directorsHtml = res.data.directors.map(d =>
                        `<li class="list-group-item d-flex justify-content-between">${d.full_name} <span class="badge bg-primary">${d.pivot.role}</span></li>`
                        ).join('');
                    $('#v_directors_list').html('<ul class="list-group list-group-flush">' + directorsHtml +
                        '</ul>');

                    $('#v_name_display').text(data.company_name);
                    $('#v_code_display').text(data.company_code);

                    let badgeHTML = '';
                    if (data.status === 'active') badgeHTML =
                        '<span class="badge bg-success shadow-sm" style="font-size: 11px; vertical-align: middle;">Active</span>';
                    else if (data.status === 'inactive') badgeHTML =
                        '<span class="badge bg-danger shadow-sm" style="font-size: 11px; vertical-align: middle;">Inactive</span>';
                    else if (data.status === 'pending') badgeHTML =
                        '<span class="badge bg-warning text-dark shadow-sm" style="font-size: 11px; vertical-align: middle;">Pending Request</span>';
                    $('#v_status_display').html(badgeHTML);

                    $('#v_parent_display').text(data.parent ? data.parent.company_name : 'Master Company');
                    $('#v_district_state_top').text([data.district, data.state].filter(Boolean).join(', ') ||
                        'Location N/A');
                    $('#v_phone_display').text(data.phone || 'N/A');
                    $('#v_whatsapp_display').text(data.whatsapp_no || 'N/A'); // 🔥 NEW: Display value
                    $('#v_email_display').text(data.email || 'N/A');
                    $('#v_address_display').text([data.address, data.district, data.state].filter(Boolean).join(
                        ', ') || 'N/A');
                    $('#v_cin_display').text(data.cin_no || 'N/A');
                    $('#v_gst_display').text(data.gst_no || 'N/A');
                    $('#v_iso_display').text(data.iso_no || 'N/A');
                    $('#v_trademark_display').text(data.trademark || 'N/A');
                    $('#v_logo_reg_display').text(data.logo_reg_no || 'N/A');

                    // 🔥 NAYA: Map Rendering Logic
                    let mapContainer = $('#v_map_display');
                    if (data.map_url) {
                        if (data.map_url.includes('<iframe')) {
                            // Agar Iframe hai to direct render karo aur uski width 100% kardo
                            mapContainer.html(data.map_url).find('iframe').css({width: '100%', height: '250px', border: 'none'});
                        } else {
                            // Agar normal link hai toh button dikhao
                            mapContainer.html(`<a href="${data.map_url}" target="_blank" class="btn btn-outline-primary shadow-sm"><i class="fas fa-external-link-alt me-2"></i> Open Location in Google Maps</a>`);
                        }
                    } else {
                        mapContainer.html('<span class="text-muted"><i class="fas fa-map-marker-slash mb-2 fs-3 text-secondary"></i><br>Location Map not provided</span>');
                    }

                    $('#viewCompanyModal').modal('show');
                }
            });
        }

        function printCompany(id) {
            Swal.fire({
                title: 'Print Configuration',
                text: 'Company print format will be integrated tomorrow!',
                icon: 'info',
                confirmButtonColor: '#3085d6'
            });
        }

        // =======================================================
        // 📱 6. MOBILE RENDER FIXES 
        // =======================================================
        function renderMobileCards(data, settings) {
            $('#mobileLoader').hide();
            let html = '';
            if (!data || data.length === 0) {
                html = '<div class="text-center p-4 bg-white rounded shadow-sm">No companies found.</div>';
            } else {
                data.forEach((c, index) => {
                    let slNo = index + settings._iDisplayStart + 1;
                    let isChecked = selectedCompanies.includes(c.id.toString()) ? 'checked' : '';

                    let statusBadge = '';
                    if (c.status === 'active') statusBadge =
                        '<span class="badge bg-success-subtle text-success">Active</span>';
                    else if (c.status === 'inactive') statusBadge =
                        '<span class="badge bg-danger-subtle text-danger">Inactive</span>';
                    else if (c.status === 'pending') statusBadge =
                        '<span class="badge bg-warning-subtle text-dark">Pending</span>';

                    // 🔥 Mobile action buttons respecting permissions
                    let actionBtns = '';
                    // if (userPermissions.canPrint) {
                    //     actionBtns +=
                    //         `<button onclick="printCompany(${c.id})" class="btn  btn-light border text-secondary" title="Print"><i class="fas fa-print"></i> Print </button> `;
                    // }
                    actionBtns +=
                        `<button onclick="viewCompany(${c.id})" class="btn  btn-light border text-info col-6" title="View"><i class="fas fa-eye"></i> View </button> `;
                    if (userPermissions.canEdit) {
                        actionBtns +=
                            `<button onclick="editCompany(${c.id})" class="btn  btn-light border text-success col-6" title="Edit"><i class="fas fa-edit"></i> Edit </button>`;
                    }

                    html += `
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" class="form-check-input row-checkbox mobile-checkbox" value="${c.id}" ${isChecked}>
                                    <span class="badge bg-secondary">#${slNo}</span>
                                    <span class="badge bg-dark">${c.company_code}</span>
                                </div>
                                <div class="text-end">${statusBadge}</div>
                            </div>
                            <div class="mb-2">
                                <h6 class="fw-bold mb-1 text-primary">${c.company_name}</h6>
                                <small class="text-muted d-block"><i class="fas fa-sitemap me-1"></i> ${c.parent_name || 'Master'}</small>
                            </div>
                            <div class="mt-2 small text-muted"><strong>Board:</strong><br>${c.directors_html || 'No Director'}</div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top gap-1">
                               ${actionBtns}
                            </div>
                        </div>
                    </div>`;
                });
            }
            $('#mobileCardsContainer').html(html);
            bindCheckboxEvents();
        }
    </script>
    <style>
        .table thead th {
            font-size: 13px;
            text-transform: uppercase;
            color: #718096;
        }

        .table tbody td {
            font-size: 14px;
            color: #2D3748;
        }

        .select2-container {
            z-index: 9999 !important;
        }
    </style>
@endpush
