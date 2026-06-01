@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        .table-custom th {
            background-color: var(--sidebar-bg);
            color: #fff;
            font-size: 13px;
            border: none;
        }

        .table-custom td {
            font-size: 13px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .emp-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px var(--shadow-color);
        }

        .emp-id-badge {
            color: var(--brand-primary);
            font-weight: 700;
            font-size: 12px;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
        }

        .modal-xl {
            max-width: 95%;
        }

        @media (max-width: 768px) {
            .modal-xl {
                max-width: 98%;
                margin: 10px auto;
            }
        }

        .nav-pills .nav-link {
            color: #64748b;
            font-weight: 600;
        }

        .nav-pills .nav-link.active {
            background-color: var(--sidebar-bg);
            color: #fff;
        }

        .form-label {
            color: #475569;
            margin-bottom: 0.2rem;
        }

        input[type="file"]::file-selector-button {
            background-color: var(--bg-light);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            border-radius: 4px;
            padding: 4px 10px;
            margin-right: 10px;
        }

        .file-preview-wrapper {
            display: none;
            position: relative;
            margin-top: 10px;
            padding: 12px;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
            width: fit-content;
            transition: all 0.3s ease;
        }

        .remove-preview-btn {
            position: absolute;
            top: -12px;
            right: -12px;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.3);
        }
    </style>

    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);"><i
                        class="fas fa-user-shield text-primary me-2"></i>Company Directors</h4>
                <p class="text-secondary small d-none d-md-block mb-0">Manage corporate board of directors and legal
                    documents</p>
            </div>
            <button class="btn text-white px-4 py-2 shadow-sm secured-item" data-permission="director_add" style="background-color: var(--brand-primary);" onclick="openAddModal()">
    <i class="fas fa-user-plus me-1"></i> <span class="d-none d-md-inline">Add Director</span>
</button>
        </div>

        <div class="d-flex d-md-none gap-2 mb-3">
            <input type="text" id="mobileSearch" class="form-control shadow-sm" placeholder="Search Board...">
            <button class="btn text-white shadow-sm" style="background-color: #10b981;" id="mobileExcelBtn"><i
                    class="fas fa-file-excel"></i></button>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="directorTable" class="table table-hover table-custom w-100">
                        <thead>
                            <tr>
                                <th>Director ID</th>
                               
                                <th>Name</th>
                                <th>Designation</th>
                                <th>Mobile</th>
                                <th>Email</th>
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
                <i class="fas fa-spinner fa-spin fs-2 mb-2"></i><br>Loading Board Directory...
            </div>
        </div>
    </div>

    <div class="modal fade" id="directorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom pb-3">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);" id="modalTitle">
                        <i class="fas fa-user-tie me-2 text-primary"></i> Register Board Node
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-3 p-md-4">
                    <form id="directorForm">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="_method" id="form_method" value="POST">
                        <ul class="nav nav-pills mb-4 bg-light p-1 rounded-3 shadow-sm flex-nowrap overflow-auto"
                            style="white-space: nowrap;">
                            <li class="nav-item"><a class="nav-link active small py-2 px-3" data-bs-toggle="tab"
                                    href="#personal"><i class="fas fa-user me-1"></i> Personal</a></li>
                            <li class="nav-item"><a class="nav-link small py-2 px-3" data-bs-toggle="tab" href="#bank"><i
                                        class="fas fa-university me-1"></i> Bank</a></li>
                            <li class="nav-item"><a class="nav-link small py-2 px-3" data-bs-toggle="tab" href="#nominee"><i
                                        class="fas fa-users me-1"></i> Nominee</a></li>
                            <li class="nav-item"><a class="nav-link small py-2 px-3" data-bs-toggle="tab" href="#ceoDocs"><i
                                        class="fas fa-file-alt me-1"></i> Director Docs</a></li>
                            <li class="nav-item"><a class="nav-link small py-2 px-3" data-bs-toggle="tab"
                                    href="#nomDocsStatus"><i class="fas fa-clipboard-check me-1"></i> Nom Docs & Status</a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="personal">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Personal
                                    Details</h6>
                                <div class="row g-3">
                        
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Full Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="full_name" id="full_name" class="form-control"
                                            placeholder="Enter Full Name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">S/O, D/O, Spouse's Name</label>
                                        <input type="text" name="father_spouse_name" id="father_spouse_name"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Mother's Name</label>
                                        <input type="text" name="mother_name" id="mother_name" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Designation <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="designation" id="designation"
                                            class="form-control fw-bold bg-light" value="Director" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Gender</label>
                                        <div class="mt-2">
                                            <div class="form-check form-check-inline"><input class="form-check-input"
                                                    type="radio" name="gender" value="Male" id="g_male"> <label
                                                    class="form-check-label" for="g_male">Male</label></div>
                                            <div class="form-check form-check-inline"><input class="form-check-input"
                                                    type="radio" name="gender" value="Female" id="g_female"> <label
                                                    class="form-check-label" for="g_female">Female</label></div>
                                            <div class="form-check form-check-inline"><input class="form-check-input"
                                                    type="radio" name="gender" value="Others" id="g_others"> <label
                                                    class="form-check-label" for="g_others">Others</label></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Marital Status</label>
                                        <select name="marital_status" id="marital_status" class="form-select">
                                            <option value="Unmarried">Unmarried</option>
                                            <option value="Married">Married</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4" id="doa_container" style="display:none;">
                                        <label class="form-label small fw-bold">Date of Anniversary</label>
                                        <input type="date" name="anniversary_date" id="anniversary_date"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Date of Birth</label>
                                        <input type="date" name="dob" id="dob" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Blood Group</label>
                                        <select name="blood_group" id="blood_group" class="form-select">
                                            <option value="">-- Select --</option>
                                            <option value="A+">A+</option>
                                            <option value="A-">A-</option>
                                            <option value="B+">B+</option>
                                            <option value="B-">B-</option>
                                            <option value="AB+">AB+</option>
                                            <option value="AB-">AB-</option>
                                            <option value="O+">O+</option>
                                            <option value="O-">O-</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Contact No <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="contact_no" id="contact_no" class="form-control"
                                            maxlength="15" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Alternate No</label>
                                        <input type="text" name="alternate_no" id="alternate_no" class="form-control"
                                            maxlength="15">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Email ID</label>
                                        <input type="email" name="email" id="email" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">PAN No</label>
                                        <input type="text" name="pan_no" id="pan_no" class="form-control"
                                            style="text-transform:uppercase;" maxlength="10">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Aadhar Card No <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="aadhar_no" id="aadhar_no" class="form-control"
                                            maxlength="20" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Native Place</label>
                                        <input type="text" name="native_place" id="native_place"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold">Communication Address</label>
                                        <textarea name="communication_address" id="communication_address" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">City/Town/Village</label>
                                        <input type="text" name="city" id="city" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Pin Code</label>
                                        <input type="text" name="pincode" id="pincode" class="form-control"
                                            maxlength="6">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-primary">Auto Generated
                                            Password</label>
                                        <input type="text" name="password" id="mem_pass"
                                            class="form-control bg-light" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="bank">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Bank
                                    Details</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Account Holder Name</label>
                                        <input type="text" name="account_holder_name" id="account_holder_name"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Bank A/c No</label>
                                        <input type="text" name="account_no" id="account_no" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Account Type</label>
                                        <select class="form-select" name="account_type" id="account_type">
                                            <option value="">-- Select Type --</option>
                                            <option value="Saving">Saving Account</option>
                                            <option value="Current">Current Account</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Bank Name</label>
                                        <input type="text" name="bank_name" id="bank_name" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">IFSC Code</label>
                                        <input type="text" name="ifsc_code" id="ifsc_code" class="form-control"
                                            style="text-transform:uppercase;">
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="nominee">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Nominee
                                    Details</h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Nominee Name</label>
                                        <input type="text" name="nominee_name" id="nominee_name"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Relation</label>
                                        <input type="text" name="nominee_relation" id="nominee_relation"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">S/o, D/o, W/o</label>
                                        <input type="text" name="nominee_so_do_wo" id="nominee_so_do_wo"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Date of Birth</label>
                                        <input type="date" name="nominee_dob" id="nominee_dob" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Mobile No</label>
                                        <input type="text" name="nominee_mobile" id="nominee_mobile"
                                            class="form-control" maxlength="15">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nominee Aadhar</label>
                                        <input type="text" name="nominee_aadhar" id="nominee_aadhar"
                                            class="form-control" maxlength="20">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nominee PAN</label>
                                        <input type="text" name="nominee_pan" id="nominee_pan" class="form-control"
                                            style="text-transform:uppercase;">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold">Address</label>
                                        <textarea name="nominee_address" id="nominee_address" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="ceoDocs">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Upload
                                    Director Documents</h6>
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label small">Passport Photo
                                            (Img)</label><input type="file" name="passport_photo" class="form-control"
                                            accept="image/*"></div>
                                    <div class="col-md-4"><label class="form-label small">Signature Photo
                                            (Img)</label><input type="file" name="signature_photo"
                                            class="form-control" accept="image/*"></div>
                                    <div class="col-md-4"><label class="form-label small">Aadhar Card (PDF)</label><input
                                            type="file" name="aadhar_pdf" class="form-control" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small">PAN Card (PDF)</label><input
                                            type="file" name="pan_pdf" class="form-control" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small">Bank Passbook
                                            (PDF)</label><input type="file" name="bank_passbook_pdf"
                                            class="form-control" accept=".pdf"></div>
                                    <div class="col-md-4"><label class="form-label small">Residential Proof
                                            (PDF)</label><input type="file" name="residential_proof_pdf"
                                            class="form-control" accept=".pdf"></div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="nomDocsStatus">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--brand-primary);">Nominee
                                    Documents & Status</h6>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4"><label class="form-label small">Nominee Photo
                                            (Img)</label><input type="file" name="nom_passport_photo"
                                            class="form-control" accept="image/*"></div>
                                    <div class="col-md-4"><label class="form-label small">Nominee Aadhar
                                            (PDF)</label><input type="file" name="nom_aadhar_pdf" class="form-control"
                                            accept=".pdf"></div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Status</label>
                                        <select class="form-select" name="status" id="status">
                                            <option value="active">Active</option>
                                            <option value="inactive">In-Active</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 leave-fields" style="display:none;">
                                        <label class="form-label small">Date of Leaving/Death</label>
                                        <input type="date" name="date_of_leaving_death" id="date_of_leaving_death"
                                            class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid mt-4 pt-3 border-top">
                            <button type="submit" class="btn text-white py-2 fw-bold"
                                style="background-color: var(--sidebar-bg);" id="saveBtn">Save Director Record</button>
                        </div>
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
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {
            const apiToken = localStorage.getItem('admin_token');
            // if (!apiToken) window.location.href = '/login';

            let table = $('#directorTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/api/v1/directors',
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    dataSrc: 'data'
                },
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-1"></i> Export Excel',
                    className: 'btn btn-success btn-sm'
                }],
                columns: [
                   { data: 'director_id', render: d => `<span class="emp-id-badge">${d}</span>` },
 // 🔥 NAYA COLUMN
{ data: 'full_name', render: d => `<span class="fw-medium">${d}</span>` },
                    {
                        data: 'designation'
                    },
                    {
                        data: 'contact_no'
                    },
                    {
                        data: 'email',
                        render: d => d || '-'
                    },
                    {
                        data: 'status',
                        render: s => s === 'active' ? `<span class="status-active">Active</span>` :
                            `<span class="status-inactive">Inactive</span>`
                    },
                    {
    data: 'id',
    orderable: false,
    className: 'text-end text-nowrap',
    render: d => `
        <div class="d-flex justify-content-end flex-nowrap gap-1">
            <button class="btn btn-sm btn-light text-primary shadow-sm edit-btn secured-item" data-permission="director_edit" data-id="${d}"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-light text-danger shadow-sm delete-btn secured-item" data-permission="director_delete" data-id="${d}"><i class="fas fa-trash"></i></button>
        </div>`
}
                ],
                drawCallback: function(settings) {
                    renderMobileCards(settings.json.data);
                }
            });

            function renderMobileCards(data) {
                $('#cardsLoader').hide();
                let html = '';
                if (!data || data.length === 0) {
                    html =
                        '<div class="text-center text-muted p-3 border rounded bg-light">No records registered.</div>';
                } else {
                    data.forEach(d => {
                        let statusHtml = d.status === 'active' ?
                            `<span class="status-active">Active</span>` :
                            `<span class="status-inactive">Inactive</span>`;
                        html += `<div class="emp-card mobile-emp-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div><h6 class="fw-bold mb-0">${d.full_name}</h6><span class="emp-id-badge">${d.director_id}</span></div>
                               
                                ${statusHtml}
                            </div>
                            <div class="small text-secondary mb-3">
                                <div><i class="fas fa-briefcase me-1 text-muted"></i> ${d.designation}</div>
                                <div class="mt-1"><i class="fas fa-phone me-1 text-muted"></i> ${d.contact_no}</div>
                            </div>
                            <div class="d-flex gap-2 border-top pt-2">
    <button class="btn btn-sm btn-light text-primary flex-fill fw-medium edit-btn secured-item" data-permission="director_edit" data-id="${d.id}"><i class="fas fa-edit me-1"></i> Edit</button>
    <button class="btn btn-sm btn-light text-danger flex-fill fw-medium delete-btn secured-item" data-permission="director_delete" data-id="${d.id}"><i class="fas fa-trash-alt me-1"></i> Del</button>
</div>
                        </div>`;
                    });
                }
                $('#mobileCardsContainer').html(html);
            }

            // Toggles
            $('#marital_status').on('change', function() {
                if ($(this).val() === 'Married') {
                    $('#doa_container').slideDown();
                } else {
                    $('#doa_container').slideUp();
                    $('#anniversary_date').val('');
                }
            });

            $('#status').on('change', function() {
                if ($(this).val() === 'inactive') {
                    $('.leave-fields').show();
                } else {
                    $('.leave-fields').hide();
                    $('#date_of_leaving_death').val('');
                }
            });

            function generatePassword() {
                let name = $('#full_name').val().trim();
                let aadhar = $('#aadhar_no').val().replace(/\D/g, '');
                if (name.length < 1 || aadhar.length < 4) {
                    $('#mem_pass').val('');
                    return;
                }
                $('#mem_pass').val(name.split(' ')[0].charAt(0).toUpperCase() + name.split(' ')[0].substring(1)
                    .toLowerCase() + '@' + aadhar.slice(-4));
            }
            $('#full_name, #aadhar_no').on('keyup change', generatePassword);

            window.openAddModal = function() {
                $('#directorForm')[0].reset();
                $('#edit_id').val('');
                $('#form_method').val('POST');
                $('#designation').val('Director');
                $('#doa_container, .leave-fields').hide();
                $('.nav-pills a:first').tab('show');
                $('#directorModal').modal('show');
            };

            $('#directorForm').on('submit', function(e) {
                e.preventDefault();
                let id = $('#edit_id').val();
                let url = id ? `/api/v1/directors/${id}` : `/api/v1/directors`;
                let formData = new FormData(this);
                let btn = $('#saveBtn');
                btn.prop('disabled', true).text('Processing...');

                $.ajax({
                    url: url,
                    type: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(res) {
                        $('#directorModal').modal('hide');
                        alert(res.message);
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        alert('Operation Failed');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Save Director Record');
                    }
                });
            });

            $(document).on('click', '.edit-btn', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: `/api/v1/directors/${id}`,
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    },
                    success: function(res) {
                        let d = res.data;
                        $('#edit_id').val(d.id);
                        $('#form_method').val('PUT');
                        Object.keys(d).forEach(key => {
                            let input = $(`#directorForm [name="${key}"]`);
                            if (input.attr('type') !== 'file' && input.attr('type') !==
                                'radio') {
                                input.val(d[key]);
                            }
                        });
                        if (d.gender) $(`input[name="gender"][value="${d.gender}"]`).prop(
                            'checked', true);
                           
                        if (d.marital_status) $('#marital_status').val(d.marital_status)
                            .trigger('change');
                        if (d.status) $('#status').val(d.status).trigger('change');

                        $('.nav-pills a:first').tab('show');
                        $('#directorModal').modal('show');
                    }
                });
            });

            $(document).on('click', '.delete-btn', function() {
                if (confirm('Permanently delete director node?')) {
                    $.ajax({
                        url: `/api/v1/directors/${$(this).data('id')}`,
                        type: 'DELETE',
                        headers: {
                            'Authorization': 'Bearer ' + apiToken
                        },
                        success: function() {
                            table.ajax.reload(null, false);
                        }
                    });
                }
            });

            // File Previews Layout Setup
            $('input[type="file"]').each(function() {
                $(this).after(
                    `<div class="file-preview-wrapper"><button type="button" class="btn btn-danger remove-preview-btn"><i class="fas fa-times"></i></button><div class="preview-content text-center"></div></div>`
                    );
            });
            $(document).on('change', 'input[type="file"]', function() {
                let file = this.files[0];
                let wrapper = $(this).next('.file-preview-wrapper');
                if (file) {
                    wrapper.find('.preview-content').text(file.name);
                    wrapper.slideDown();
                } else {
                    wrapper.slideUp();
                }
            });
            $(document).on('click', '.remove-preview-btn', function() {
                $(this).closest('.file-preview-wrapper').prev('input[type="file"]').val('');
                $(this).closest('.file-preview-wrapper').slideUp();
            });


  



        });
    </script>
@endpush
