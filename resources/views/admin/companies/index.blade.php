@extends('layout.app')

@section('content')
    <div class="container-fluid px-1 px-md-3 py-2">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-building me-2"></i>Company Management</h5>
            <button class="btn btn-primary btn-sm shadow-sm fw-bold" onclick="openAddModal()">
                <i class="fas fa-plus-circle me-1"></i> Add Company
            </button>
        </div>

        <!-- 🔥 MOBILE SEARCH & EXCEL BUTTON 🔥 -->
        <div class="d-block d-md-none mb-3">
            <div class="d-flex gap-2">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0" id="mobileSearch"
                        placeholder="Search companies...">
                </div>
                <button class="btn btn-success shadow-sm px-3" id="mobileExcelBtn" title="Download Excel">
                    <i class="fas fa-file-excel"></i>
                </button>
            </div>
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

                                    <!-- 🔥 LIVE PREVIEW BOX (Hide by default) 🔥 -->
    <div id="logoPreviewBox" class="mt-2 d-none position-relative d-inline-block">
        <img id="logoPreviewImg" src="" class="border rounded shadow-sm" style="width: 70px; height: 70px; object-fit: cover;">
        <!-- Cut (X) Button -->
        <button type="button" id="clearLogoBtn" class="btn btn-danger position-absolute top-0 start-100 translate-middle rounded-circle shadow" style="width: 22px; height: 22px; padding: 0; font-size: 12px; line-height: 1;" title="Remove Logo">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <!-- Flag to tell backend if existing logo was deleted -->
    <input type="hidden" id="remove_logo_flag" name="remove_logo_flag" value="0">
                            </div>

                            <!-- Registration Details -->
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

                            <!-- Prefix Code Input -->
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
    <div id="directorRows">
        </div>
    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addDirectorRow()">
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

                            <div class="col-md-4">
                                <label class="small fw-bold">GST No (Optional)</label>
                                <input type="text" class="form-control text-uppercase" id="c_gst_no" name="gst_no"
                                    placeholder="GSTIN">
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

    <!-- PREMIUM VIEW COMPANY MODAL -->
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
                            <!-- Logo/Initials injected via JS -->
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
                        <div class="col-md-6">
                            <div class="small fw-bold text-dark mb-1">Mobile:</div>
                            <div class="text-muted" id="v_phone_display"></div>
                        </div>
                        <div class="col-md-6">
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

                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- 🔥 NEW EXCEL/BUTTONS CDNs ADDED 🔥 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>

 // इसे $(document).ready() के बाहर रखें या window पर असाइन करें
window.addDirectorRow = function(directorId = '', role = 'Director') {
    let row = `
        <div class="row g-2 mb-2 director-row">
            <div class="col-6">
                <select class="form-control director-select" name="directors[]" required>
                    <option value="">Select Director</option>
                </select>
            </div>
            <div class="col-4">
                <select class="form-select role-select" name="roles[]">
                    <option value="Director" ${role=='Director'?'selected':''}>Director</option>
                    <option value="CEO" ${role=='CEO'?'selected':''}>CEO</option>
                    <option value="MD" ${role=='MD'?'selected':''}>MD</option>
                </select>
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('.director-row').remove()"><i class="fas fa-times"></i></button>
            </div>
        </div>
    `;
    $('#directorRows').append(row);
    
    // Select2 initialize karein (DropdownParent zaroori hai modal ke liye)
    let lastRow = $('.director-select').last();
    lastRow.select2({ 
        dropdownParent: $('#companyModal'),
        width: '100%' 
    });
    
    // Directors API call
    $.get('/api/v1/admin/directors/active', function(res) {
        let opts = '<option value="">Select Director</option>';
        res.data.forEach(d => {
            opts += `<option value="${d.id}" ${d.id == directorId ? 'selected' : ''}>${d.full_name} (${d.director_id})</option>`;
        });
        lastRow.html(opts).trigger('change');
    });
}





        let table;
        const apiToken = localStorage.getItem('admin_token');

        $(document).ready(function() {

            table = $('#companyDataTable').DataTable({
                // 🔥 DOM SETTING: Page entries hata kar Buttons (B) laga diya 🔥
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export Excel',
                    className: 'btn btn-success btn-sm shadow-sm rounded-3'
                }],
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/admin/companies',
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    }
                },
                columns: [{
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
                            return data === 'active' ?
                                '<span class="badge bg-success">Active</span>' :
                                '<span class="badge bg-danger">Inactive</span>';
                        }
                    },
                    // 🔥 ACTION COLUMN RE-RENDERED FRONTEND PAR (Print button included) 🔥
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data) {
                            return `
                        <button onclick="printCompany(${data})" class="btn btn-sm btn-light border text-secondary" title="Print"><i class="fas fa-print"></i></button>
                        <button onclick="viewCompany(${data})" class="btn btn-sm btn-light border text-info" title="View"><i class="fas fa-eye"></i></button>
                        <button onclick="editCompany(${data})" class="btn btn-sm btn-light border text-success" title="Edit"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteCompany(${data})" class="btn btn-sm btn-light border text-danger" title="Delete"><i class="fas fa-trash"></i></button>
                    `;
                        }
                    }
                ],
                drawCallback: function(settings) {
                    renderMobileCards(settings.json.data);
                }
            });

            loadParentCompanies();

            // 🔥 MOBILE SEARCH BINDING 🔥
            $('#mobileSearch').on('keyup', function() {
                table.search(this.value).draw();
            });

            // 🔥 MOBILE EXCEL BINDING 🔥
            $('#mobileExcelBtn').on('click', function() {
                $('.buttons-excel').click();
            });


           






            $('#companyForm').on('submit', function(e) {
                e.preventDefault();

                let directorData = [];
    $('.director-row').each(function() {
        directorData.push({
            director_id: $(this).find('.director-select').val(),
            role: $(this).find('.role-select').val()
        });
    });

                let id = $('#c_id').val();
                let url = id ? `/api/v1/admin/companies/${id}` : '/api/v1/admin/companies';
                let method = id ? 'PUT' : 'POST';

                let formData = new FormData(this);
formData.append('director_assignments', JSON.stringify(directorData));
                if (id) {
                    formData.append('_method', 'PUT');
                    url = `/api/v1/admin/companies/${id}`;
                }

                $('#saveBtn').html('<span class="spinner-border spinner-border-sm"></span> Saving...');

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
                        $('#saveBtn').html('Save Company');
                    }
                });
            });

// 🔥 1. LIVE IMAGE PREVIEW LOGIC 🔥
    $('#c_company_logo').on('change', function(e) {
        let file = e.target.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function(event) {
                $('#logoPreviewImg').attr('src', event.target.result);
                $('#logoPreviewBox').removeClass('d-none');
                $('#remove_logo_flag').val('0'); // Reset flag
            }
            reader.readAsDataURL(file);
        }
    });

    // 🔥 2. CUT (REMOVE) LOGO LOGIC 🔥
    $('#clearLogoBtn').on('click', function() {
        $('#c_company_logo').val(''); // Input clear karo
        $('#logoPreviewBox').addClass('d-none'); // Preview chhupao
        $('#logoPreviewImg').attr('src', '');
        $('#remove_logo_flag').val('1'); // Backend ko batane ke liye ki logo uda do
    });


    


        });

        function loadParentCompanies() {
            $.ajax({
                url: '/api/v1/admin/get-active-companies',
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

        function openAddModal() {
            $('#companyForm')[0].reset();
            $('#c_id').val('');
            // Nayi line add karein 👇
    $('#logoPreviewBox').addClass('d-none'); $('#remove_logo_flag').val('0');
            $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add Company');
            $('#companyModal').modal('show');
        }

        function editCompany(id) {
            $.ajax({
                url: `/api/v1/admin/companies/${id}`,
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
                    $('#c_email').val(data.email);
                    $('#c_state').val(data.state);
                    $('#c_district').val(data.district);
                    $('#c_address').val(data.address);
                    $('#c_gst_no').val(data.gst_no);
                    $('#c_status').val(data.status);
                    $('#c_cin_no').val(data.cin_no);
                    $('#c_iso_no').val(data.iso_no);
                    $('#c_trademark').val(data.trademark);
                    $('#c_logo_reg_no').val(data.logo_reg_no);

                    // Edit success function mein:
$('#directorRows').empty(); // Purane rows saaf karo
if (data.directors && data.directors.length > 0) {
    data.directors.forEach(dir => {
        addDirectorRow(dir.id, dir.pivot.role);
    });
}
                    // 🔥 Edit form mein purana logo dikhane ka logic 👇
            if(data.company_logo) {
                $('#logoPreviewImg').attr('src', '/' + data.company_logo);
                $('#logoPreviewBox').removeClass('d-none');
            } else {
                $('#logoPreviewBox').addClass('d-none');
            }
            $('#remove_logo_flag').val('0');
            $('#c_company_logo').val(''); // Ensure file input is empty on load

                    $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Company');
                    $('#companyModal').modal('show');
                }
            });
        }

        function viewCompany(id) {
            $.ajax({
                url: `/api/v1/admin/companies/${id}`,
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + apiToken
                },
                success: function(res) {
                    let data = res.data;

                    if (data.company_logo) {
                        $('#v_logo_container').html(
                                `<img src="/${data.company_logo}" style="width:100%; height:100%; object-fit:cover;">`
                                )
                            .css('background-color', 'transparent');
                    } else {
                        let initials = data.company_code ? data.company_code.substring(0, 3) : 'COM';
                        $('#v_logo_container').html(initials)
                            .css('background-color', '#1a2a40');
                    }

                    // JS Logic:
let directorsHtml = res.data.directors.map(d => 
    `<li class="list-group-item d-flex justify-content-between">
        ${d.full_name} 
        <span class="badge bg-primary">${d.pivot.role}</span>
    </li>`
).join('');

$('#v_directors_list').html('<ul class="list-group list-group-flush">' + directorsHtml + '</ul>');
                    $('#v_name_display').text(data.company_name);
                    $('#v_code_display').text(data.company_code);

                    let badgeHtml = data.status === 'active' ?
                        '<span class="badge bg-success shadow-sm" style="font-size: 11px; vertical-align: middle;">Active</span>' :
                        '<span class="badge bg-danger shadow-sm" style="font-size: 11px; vertical-align: middle;">Inactive</span>';
                    $('#v_status_display').html(badgeHtml);

                    $('#v_parent_display').text(data.parent ? data.parent.company_name : 'Master Company');
                    let shortLocation = [data.district, data.state].filter(Boolean).join(', ');
                    $('#v_district_state_top').text(shortLocation || 'Location N/A');

                    $('#v_phone_display').text(data.phone || 'N/A');
                    $('#v_email_display').text(data.email || 'N/A');

                    let fullAddress = [data.address, data.district, data.state].filter(Boolean).join(', ');
                    $('#v_address_display').text(fullAddress || 'N/A');

                    $('#v_cin_display').text(data.cin_no || 'N/A');
                    $('#v_gst_display').text(data.gst_no || 'N/A');
                    $('#v_iso_display').text(data.iso_no || 'N/A');
                    $('#v_trademark_display').text(data.trademark || 'N/A');
                    $('#v_logo_reg_display').text(data.logo_reg_no || 'N/A');

                    $('#viewCompanyModal').modal('show');
                }
            });
        }

        // 🔥 PRINT COMPANY FUNCTION (DUMMY FOR TODAY) 🔥
        function printCompany(id) {
            Swal.fire({
                title: 'Print Configuration',
                text: 'Company print format will be integrated tomorrow!',
                icon: 'info',
                confirmButtonColor: '#3085d6'
            });
            // Kal ye line aayegi: window.open(`/admin/companies/print/${id}`, '_blank');
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
                        headers: {
                            'Authorization': 'Bearer ' + apiToken
                        },
                        success: function(res) {
                            Swal.fire('Deleted!', 'Company has been deleted.', 'success');
                            table.ajax.reload();
                            loadParentCompanies();
                        }
                    });
                }
            });
        }

        function renderMobileCards(data) {
            $('#mobileLoader').hide();
            let html = '';
            if (!data || data.length === 0) {
                html = '<div class="text-center p-4 bg-white rounded shadow-sm">No companies found.</div>';
            } else {
                data.forEach(c => {
                    let statusBadge = c.status === 'active' ?
                        '<span class="badge bg-success-subtle text-success">Active</span>' :
                        '<span class="badge bg-danger-subtle text-danger">Inactive</span>';
                    let rawPrefix = $(c.company_code).text() || c.company_code;
                    let directorsHtml = c.directors_html || 'No Director';
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
                        <div class="mt-2 small text-muted"><strong>Board:</strong><br>${directorsHtml}</div>
                    </div>
                    <div class="d-flex justify-content-end align-items-center pt-2 border-top gap-1">
                        <!-- 🔥 MOBILE CARD ME BHI PRINT BUTTON AAGAYA 🔥 -->
                        <button onclick="printCompany(${c.id})" class="btn btn-sm btn-light border text-secondary" title="Print"><i class="fas fa-print"></i></button>
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
        .table thead th {
            font-size: 13px;
            text-transform: uppercase;
            color: #718096;
        }

        .table tbody td {
            font-size: 14px;
            color: #2D3748;
        }
    </style>
@endpush
