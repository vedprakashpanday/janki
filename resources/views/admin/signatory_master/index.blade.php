@extends('layout.app')

@section('content')
    <!-- Select2 & DataTables CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.4.1/css/rowGroup.bootstrap5.min.css">
    <style>
        /* Select2 Customization */
        .select2-container .select2-selection--multiple {
            border-color: #dee2e6;
            min-height: 38px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #0d6efd;
            border: none;
            color: white;
            border-radius: 4px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            margin-right: 5px;
        }

        /* DataTable Expand/Collapse Cursor */
        tr.dtrg-group {
            cursor: pointer;
            background-color: #f8f9fa !important;
        }

        tr.dtrg-group:hover {
            background-color: #e9ecef !important;
        }

        /* 🔥 FIX 1: Hide default DataTable Excel Button (We have custom one) */
        .dt-buttons {
            display: none !important;
        }

        /* Mobile Card Style */
        .mobile-card {
            border-left: 4px solid #0d6efd;
        }
    </style>

    <div class="container-fluid px-2 px-md-3 py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-primary"><i class="fas fa-sitemap me-2"></i>Signatory Hierarchy Master</h5>
        </div>

        <div class="row g-3">
            <!-- 📝 SETUP FORM (Left Side) -->
            <div class="col-lg-4 col-md-5">
                <div class="card border-primary shadow-sm mb-3">
                    <div class="card-header bg-primary text-white fw-bold">
                        <i class="fas fa-link me-1"></i> Map Hierarchy
                    </div>
                    <div class="card-body bg-light p-3">
                        <form id="hierarchyForm">
                            @csrf

                            <!-- 1. CONTEXT SECTION -->
                            <h6 class="fw-bold text-secondary border-bottom pb-2 mb-3">1. Select Context</h6>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="small fw-bold text-muted">Module *</label>
                                    <select class="form-select form-select-sm border-primary" id="module" name="module"
                                        required>
                                        <option value="debit_voucher">Debit Voucher</option>
                                        <option value="receipt_voucher">Receipt Voucher</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="small fw-bold text-muted">Company *</label>
                                    <select class="form-select form-select-sm" id="company_id" name="company_id" required>
                                        <option value="">Select...</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->company_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="small fw-bold text-muted">Branch *</label>
                                    <select class="form-select form-select-sm" id="branch_id" name="branch_id" required
                                        disabled>
                                        <option value="">Select...</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="small fw-bold text-muted">Department</label>
                                    <select class="form-select form-select-sm" id="department_id" name="department_id"
                                        disabled>
                                        <option value="">All Depts</option>
                                    </select>
                                </div>
                            </div>

                            <!-- 2. BASE PERSON -->
                            <h6 class="fw-bold text-secondary border-bottom pb-2 mb-3">2. Base Person</h6>
                            <div class="mb-2">
                                <label class="small fw-bold text-muted">Base Role</label>
                                <input type="text" class="form-control form-control-sm bg-white fw-bold text-info"
                                    id="base_role_display" value="Prepared By" readonly>
                                <input type="hidden" id="base_role" value="prepared_by">
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold text-muted">Select Employee *</label>
                                <input list="basePersonsList" class="form-control form-control-sm border-info"
                                    id="base_person_search" placeholder="Search..." required disabled autocomplete="off">
                                <datalist id="basePersonsList"></datalist>
                                <input type="hidden" id="hidden_base_person_id">
                                <input type="hidden" id="hidden_base_grade">
                                <small id="base_grade_display" class="text-success fw-bold d-none"
                                    style="font-size:10px;"></small>
                            </div>

                            <!-- 3. APPROVED BY -->
                            <div id="section_approved_by">
                                <h6 class="fw-bold text-secondary border-bottom pb-2 mb-2 mt-4">3. Assign Approved By</h6>
                                <div class="mb-2">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input appr-type-cb" type="checkbox" value="employee"
                                            id="cb_appr_emp" checked>
                                        <label class="form-check-label fw-bold small" for="cb_appr_emp">EMP</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input appr-type-cb" type="checkbox" value="director"
                                            id="cb_appr_dir" checked>
                                        <label class="form-check-label fw-bold small text-success"
                                            for="cb_appr_dir">DIR</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input appr-type-cb" type="checkbox" value="ceo"
                                            id="cb_appr_ceo" checked>
                                        <label class="form-check-label fw-bold small text-danger"
                                            for="cb_appr_ceo">CEO</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <select class="form-select select2-multiple" id="appr_persons" multiple="multiple"
                                        style="width: 100%;" disabled></select>
                                </div>
                            </div>

                            <!-- 4. AUTHORIZED SIGNATORY -->
                            <div id="section_authorized_by">
                                <h6 class="fw-bold text-secondary border-bottom pb-2 mb-2 mt-4"><span
                                        id="lbl_sec_4">4</span>. Assign Auth. Signatory</h6>
                                <div class="mb-2">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input auth-type-cb" type="checkbox" value="employee"
                                            id="cb_auth_emp">
                                        <label class="form-check-label fw-bold small" for="cb_auth_emp">EMP</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input auth-type-cb" type="checkbox" value="director"
                                            id="cb_auth_dir" checked>
                                        <label class="form-check-label fw-bold small text-success"
                                            for="cb_auth_dir">DIR</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input auth-type-cb" type="checkbox" value="ceo"
                                            id="cb_auth_ceo" checked>
                                        <label class="form-check-label fw-bold small text-danger"
                                            for="cb_auth_ceo">CEO</label>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <select class="form-select select2-multiple" id="auth_persons" multiple="multiple"
                                        style="width: 100%;" disabled></select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold" id="btnSave">
                                <i class="fas fa-link me-1"></i> Map Hierarchy
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 📋 MAPPINGS LIST SECTION (Right Side) -->
            <div class="col-lg-8 col-md-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="row align-items-center g-2">
                            <div class="col-sm-3 col-6">
                                <h6 class="fw-bold mb-0 text-dark">Active Mappings</h6>
                            </div>
                            <div class="col-sm-3 col-6">
                                <select class="form-select form-select-sm" id="filter_module">
                                    <option value="debit_voucher">Debit Voucher</option>
                                    <option value="receipt_voucher">Receipt Voucher</option>
                                </select>
                            </div>
                            <!-- Search & Export -->
                            <div class="col-sm-4 col-8">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                    <input type="text" id="customSearch" class="form-control border-start-0"
                                        placeholder="Search person...">
                                </div>
                            </div>
                            <div class="col-sm-2 col-4 text-end">
                                <button class="btn btn-success btn-sm w-100 fw-bold" id="btnExportExcel"><i
                                        class="fas fa-file-excel me-1"></i> Excel</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0 bg-light">

                        <!-- 💻 DESKTOP DATATABLE -->
                        <div class="table-responsive d-none d-md-block" style="max-height: 75vh;">
                            <table class="table table-hover bg-white align-middle mb-0 w-100" id="hierarchyTable"
                                style="font-size: 13px;">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">
                                            <input type="checkbox" class="form-check-input" id="selectAllDesktop">
                                        </th>
                                        <th>Base Person</th> <!-- Hidden, used for grouping -->
                                        <th>Target Role</th>
                                        <th>Mapped Approver / Signatory</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <!-- 📱 MOBILE CARDS (Grouped) -->
                        <div class="d-md-none p-2" id="mobileCardsContainer">
                            <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAllMobile">
                                    <label class="form-check-label small fw-bold" for="selectAllMobile">Select All
                                        Visible</label>
                                </div>
                            </div>
                            <div id="mobileCards"></div>
                            <div class="text-center mt-3 mb-2" id="loadMoreContainer" style="display:none;">
                                <button class="btn btn-outline-primary btn-sm rounded-pill fw-bold px-4" id="btnLoadMore">
                                    <i class="fas fa-sync-alt me-1"></i> Load More Groups
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 🛑 FLOATING BULK DELETE BUTTON -->
    <div id="floatingDeleteBtn" class="position-fixed bottom-0 end-0 m-3 m-md-4" style="display: none; z-index: 1050;">
        <button class="btn btn-danger shadow-lg fw-bold rounded-pill px-4 py-2" onclick="bulkDeleteSelected()">
            <i class="fas fa-trash-alt me-2"></i>Delete Selected (<span id="selCount">0</span>)
        </button>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- DataTables Core & Extensions -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/rowgroup/1.4.1/js/dataTables.rowGroup.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {
            let token = localStorage.getItem('token') || sessionStorage.getItem('token');
            let allMappingsData = [];
            let dataTableInstance = null;

            // Mobile Pagination Config (By Groups)
            let mobilePage = 0;
            const mobileGroupSize = 10; // Load 10 employees (groups) at a time

            // Select2 Initialization
            $('.select2-multiple').select2({
                placeholder: "Click to select...",
                allowClear: true
            });

            loadMappings();

            // ==========================================
            // 🛠️ DYNAMIC MODULE RULES
            // ==========================================
            $('#module').on('change', function() {
                let mod = $(this).val();
                if (mod === 'receipt_voucher') {
                    $('#base_role').val('approved_by');
                    $('#base_role_display').val('Approved By (Base)');
                    $('#section_approved_by').hide();
                    $('#lbl_sec_4').text('3');
                } else {
                    $('#base_role').val('prepared_by');
                    $('#base_role_display').val('Prepared By (Creator)');
                    $('#section_approved_by').show();
                    $('#lbl_sec_4').text('4');
                }
                $('#base_person_search').val('');
                $('#hidden_base_person_id, #hidden_base_grade').val('');
                $('#base_grade_display').addClass('d-none');
                $('#appr_persons, #auth_persons').empty().prop('disabled', true);
            });
            $('#module').trigger('change');

            // ==========================================
            // 🔄 CASCADING: Context Loading
            // ==========================================
            $('#company_id').on('change', function() {
                let compId = $(this).val();
                $('#branch_id').html('<option value="">Loading...</option>').prop('disabled', true);
                $('#department_id').html('<option value="">All Departments</option>').prop('disabled',
                true);
                if (compId) {
                    $.ajax({
                        url: `/api/v1/signatory-master/get-branches?company_id=${compId}`,
                        type: 'GET',
                        headers: token ? {
                            'Authorization': 'Bearer ' + token
                        } : {},
                        success: function(res) {
                            let options =
                                '<option value="">Select Branch...</option><option value="HO" class="fw-bold">Head Office</option>';
                            res.data.forEach(b => {
                                options +=
                                    `<option value="${b.id}">${b.branch_name}</option>`;
                            });
                            $('#branch_id').html(options).prop('disabled', false);
                        }
                    });
                    fetchDepartments();
                    $('#base_person_search').prop('disabled', false).val('');
                } else {
                    $('#base_person_search').prop('disabled', true).val('');
                    $('#appr_persons, #auth_persons').empty().prop('disabled', true);
                }
            });

            $('#branch_id').on('change', fetchDepartments);

            function fetchDepartments() {
                let compId = $('#company_id').val();
                let branchId = $('#branch_id').val();
                if (!compId) return;
                $.ajax({
                    url: `/api/v1/signatory-master/get-departments?company_id=${compId}&branch_id=${branchId}`,
                    type: 'GET',
                    headers: token ? {
                        'Authorization': 'Bearer ' + token
                    } : {},
                    success: function(res) {
                        let options = '<option value="">All Departments</option>';
                        res.data.forEach(d => {
                            options += `<option value="${d.id}">${d.department_name}</option>`;
                        });
                        $('#department_id').html(options).prop('disabled', false);
                    }
                });
            }

            // ==========================================
            // 🧑‍💼 BASE PERSON LOGIC
            // ==========================================
            let typingTimerBase;
            $('#base_person_search').on('input', function() {
                clearTimeout(typingTimerBase);
                let val = $(this).val();
                let selectedOption = $(`#basePersonsList option[value="${val}"]`);
                if (selectedOption.length > 0) {
                    $('#hidden_base_person_id').val(selectedOption.data('id'));
                    let grade = selectedOption.data('grade');
                    $('#hidden_base_grade').val(grade);
                    if (grade && grade !== 'null' && grade !== 'undefined') {
                        $('#base_grade_display').text(`(Grade: ${grade})`).removeClass('d-none');
                    } else {
                        $('#base_grade_display').addClass('d-none');
                    }
                    refreshTargets();
                    return;
                }
                $('#hidden_base_person_id, #hidden_base_grade').val('');
                $('#base_grade_display').addClass('d-none');
                $('#appr_persons, #auth_persons').empty().prop('disabled', true);
                typingTimerBase = setTimeout(() => {
                    fetchBasePersons(val);
                }, 500);
            });

            function fetchBasePersons(searchQ) {
                let compId = $('#company_id').val();
                if (!compId) return;
                $.ajax({
                    url: '/api/v1/signatory-master/get-persons',
                    type: 'GET',
                    data: {
                        company_id: compId,
                        branch_id: $('#branch_id').val(),
                        department_id: $('#department_id').val(),
                        types: ['employee'],
                        q: searchQ
                    },
                    headers: token ? {
                        'Authorization': 'Bearer ' + token
                    } : {},
                    success: function(res) {
                        let options = '';
                        res.data.forEach(p => {
                            options +=
                                `<option value="${p.name} [EMP - ${p.id}]" data-id="${p.id}" data-grade="${p.grade}">`;
                        });
                        $('#basePersonsList').html(options);
                    }
                });
            }

            // ==========================================
            // 🎯 TARGET: SELECT2 REFRESH LOGIC
            // ==========================================
            $('.appr-type-cb, .auth-type-cb').on('change', refreshTargets);

            function refreshTargets() {
                let baseGrade = $('#hidden_base_grade').val();
                let basePersonId = $('#hidden_base_person_id').val();
                if (!basePersonId) return;

                let apprTypes = [];
                $('.appr-type-cb:checked').each(function() {
                    apprTypes.push(this.value);
                });
                loadSelect2Options('appr_persons', apprTypes, baseGrade);

                let authTypes = [];
                $('.auth-type-cb:checked').each(function() {
                    authTypes.push(this.value);
                });
                loadSelect2Options('auth_persons', authTypes, baseGrade);
            }

            function loadSelect2Options(selectId, types, baseGrade) {
                let compId = $('#company_id').val();
                if (!compId || types.length === 0) {
                    $(`#${selectId}`).empty().prop('disabled', true);
                    return;
                }
                $.ajax({
                    url: '/api/v1/signatory-master/get-persons',
                    type: 'GET',
                    data: {
                        company_id: compId,
                        branch_id: $('#branch_id').val(),
                        types: types,
                        base_grade: baseGrade
                    },
                    headers: token ? {
                        'Authorization': 'Bearer ' + token
                    } : {},
                    success: function(res) {
                        let options = '';
                        res.data.forEach(p => {
                            let badge = p.person_type === 'employee' ? 'EMP' : (p
                                .person_type === 'director' ? 'DIR' : 'CEO');
                            options +=
                                `<option value="${p.person_type}|${p.id}">${p.name} [${badge} - ${p.id}]</option>`;
                        });
                        $(`#${selectId}`).html(options).prop('disabled', false);
                    }
                });
            }

            // ==========================================
            // 💾 SAVE HIERARCHY
            // ==========================================
            $('#hierarchyForm').on('submit', function(e) {
                e.preventDefault();
                let module = $('#module').val();
                let targetPayload = [];

                if (module === 'debit_voucher') {
                    let apprSelections = $('#appr_persons').val();
                    if (!apprSelections || apprSelections.length === 0) {
                        Swal.fire('Error', 'Please select at least one Approved By person.', 'error');
                        return;
                    }
                    apprSelections.forEach(val => {
                        let parts = val.split('|');
                        targetPayload.push({
                            target_role: 'approved_by',
                            target_person_type: parts[0],
                            target_person_id: parts[1]
                        });
                    });
                }

                let authSelections = $('#auth_persons').val();
                if (!authSelections || authSelections.length === 0) {
                    Swal.fire('Error', 'Please select at least one Authorized Signatory.', 'error');
                    return;
                }
                authSelections.forEach(val => {
                    let parts = val.split('|');
                    targetPayload.push({
                        target_role: 'authorized_signatory',
                        target_person_type: parts[0],
                        target_person_id: parts[1]
                    });
                });

                let formData = {
                    module: module,
                    company_id: $('#company_id').val(),
                    branch_id: $('#branch_id').val(),
                    department_id: $('#department_id').val(),
                    base_role: $('#base_role').val(),
                    base_person_id: $('#hidden_base_person_id').val(),
                    targets: targetPayload
                };

                let btn = $('#btnSave');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: '/api/v1/signatory-master/hierarchies',
                    type: 'POST',
                    data: formData,
                    headers: token ? {
                        'Authorization': 'Bearer ' + token
                    } : {},
                    success: function(res) {
                        Swal.fire('Success', res.message, 'success');
                        $('#appr_persons, #auth_persons').val(null).trigger('change');
                        loadMappings();
                    },
                    error: function(err) {
                        Swal.fire('Error', err.responseJSON?.message || 'Error saving.',
                            'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fas fa-link me-1"></i> Map Hierarchy');
                    }
                });
            });

            // ==========================================
            // 📊 DATATABLES & MOBILE CARDS LOGIC
            // ==========================================
            $('#filter_module').on('change', loadMappings);

            function loadMappings() {
                let filterMod = $('#filter_module').val();

                $.ajax({
                    url: `/api/v1/signatory-master/hierarchies?module=${filterMod}`,
                    type: 'GET',
                    headers: token ? {
                        'Authorization': 'Bearer ' + token
                    } : {},
                    success: function(res) {
                        allMappingsData = res.data;
                        renderDesktopTable();

                        // Mobile Reset
                        mobilePage = 0;
                        $('#mobileCards').empty();
                        renderMobileCards();

                        $('#floatingDeleteBtn').hide();
                        $('#selectAllDesktop, #selectAllMobile').prop('checked', false);
                    }
                });
            }

            function renderDesktopTable() {
                if (dataTableInstance !== null) {
                    dataTableInstance.clear().rows.add(allMappingsData).draw();
                    return;
                }

                dataTableInstance = $('#hierarchyTable').DataTable({
                    data: allMappingsData,
                    pageLength: 20,
                    lengthChange: false,
                    dom: 'Brtip', // B calls default buttons (which we hid via CSS)
                    order: [
                        [1, 'asc']
                    ], // Order by base person name for grouping
                    rowGroup: {
                        dataSrc: 'base_person_name',
                        startRender: function(rows, group) {
                            let baseId = rows.data()[0].base_person_id;
                            return $('<tr/>')
                                .append(
                                    `<td colspan="5" class="bg-light fw-bold text-primary"><i class="fas fa-chevron-down me-2 text-muted" style="font-size:10px;"></i> ${group} <span class="text-muted small fw-normal ms-2">(${baseId})</span></td>`
                                    )
                                .attr('data-name', group);
                        }
                    },
                    columns: [{
                            data: null,
                            orderable: false,
                            className: 'text-center',
                            render: function(data, type, row) {
                                return `<input type="checkbox" class="form-check-input row-checkbox" value="${row.id}">`;
                            }
                        },
                        {
                            data: 'base_person_name',
                            visible: false
                        },
                        {
                            data: 'target_role',
                            render: function(data) {
                                let roleText = data.replace('_', ' ').toUpperCase();
                                let badgeClass = data === 'approved_by' ? 'bg-success' :
                                    'bg-primary';
                                return `<span class="badge ${badgeClass}">${roleText}</span>`;
                            }
                        },
                        {
                            data: null,
                            render: function(data, type, row) {
                                return `<div class="fw-bold text-dark">${row.target_person_name}</div><small class="text-muted">${row.target_person_id} (${row.target_person_type})</small>`;
                            }
                        },
                        {
                            data: 'id',
                            orderable: false,
                            className: 'text-center',
                            render: function(data) {
                                return `<button class="btn btn-sm btn-outline-danger shadow-none" onclick="deleteHierarchy(${data})"><i class="fas fa-trash"></i></button>`;
                            }
                        }
                    ],
                    buttons: [{
                        extend: 'excelHtml5',
                        title: 'Signatory_Hierarchies',
                        exportOptions: {
                            columns: [1, 2, 3]
                        }
                    }],
                    drawCallback: function() {
                        updateFloatingBtnState();
                    }
                });

                // Expand/Collapse Group Logic
                $('#hierarchyTable tbody').on('click', 'tr.dtrg-group', function() {
                    $(this).nextUntil('.dtrg-group').toggle();
                    let icon = $(this).find('i.fas');
                    if (icon.hasClass('fa-chevron-down')) {
                        icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                    } else {
                        icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                    }
                });
            }

            // Custom Excel Button click triggers DataTables hidden button
            $('#btnExportExcel').on('click', function() {
                $('.dt-button.buttons-excel').click();
            });

            // ==========================================
            // 📱 🔥 MOBILE VIEW: GROUPED EXPAND/COLLAPSE 🔥
            // ==========================================
            $('#btnLoadMore').on('click', renderMobileCards);

            function renderMobileCards() {
                // Pehle allMappingsData ko Base Person ke hisaab se Group (Bundle) karenge
                let groupedData = {};
                allMappingsData.forEach(m => {
                    if (!groupedData[m.base_person_id]) {
                        groupedData[m.base_person_id] = {
                            base_person_name: m.base_person_name,
                            base_person_id: m.base_person_id,
                            targets: []
                        };
                    }
                    groupedData[m.base_person_id].targets.push(m);
                });

                let groupsArray = Object.values(groupedData);

                // Pagination on Groups (Load 10 Base Persons at a time)
                let start = mobilePage * mobileGroupSize;
                let end = start + mobileGroupSize;
                let slice = groupsArray.slice(start, end);

                if (slice.length === 0 && mobilePage === 0) {
                    $('#mobileCards').html('<div class="text-center p-4 text-muted">No mappings found.</div>');
                    $('#loadMoreContainer').hide();
                    return;
                }

                let html = '';
                slice.forEach((group, index) => {
                    let safeGroupId = 'group_' + group.base_person_id.replace(/[^a-zA-Z0-9]/g, '_');

                    // Group Header Card (Clickable Collapse)
                    html += `
                    <div class="card shadow-sm mb-2 border-0 mobile-group-card">
                        <div class="card-header bg-white border d-flex justify-content-between align-items-center p-3" 
                             data-bs-toggle="collapse" data-bs-target="#${safeGroupId}" aria-expanded="false" style="cursor: pointer;">
                            <div>
                                <div class="fw-bold text-primary"><i class="fas fa-chevron-down me-2 transition-icon"></i> ${group.base_person_name}</div>
                                <small class="text-muted ms-4">${group.base_person_id}</small>
                            </div>
                            <span class="badge bg-secondary rounded-pill">${group.targets.length} Target(s)</span>
                        </div>
                        <div id="${safeGroupId}" class="collapse">
                            <div class="card-body p-2 bg-light border border-top-0">
                    `;

                    // Mapped Target Cards inside the group
                    group.targets.forEach(m => {
                        let roleText = m.target_role.replace('_', ' ').toUpperCase();
                        let badgeClass = m.target_role === 'approved_by' ? 'bg-success' :
                            'bg-primary';

                        html += `
                        <div class="card shadow-sm border mb-2 mobile-card bg-white">
                            <div class="card-body p-2">
                                <div class="d-flex justify-content-between align-items-start mb-2 border-bottom pb-2">
                                    <div class="form-check">
                                        <input class="form-check-input row-checkbox" type="checkbox" value="${m.id}" id="mob_chk_${m.id}">
                                        <label class="form-check-label small fw-bold text-muted" for="mob_chk_${m.id}">Select to Delete</label>
                                    </div>
                                    <span class="badge ${badgeClass}">${roleText}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <div class="fw-bold">${m.target_person_name}</div>
                                        <div class="small text-muted">${m.target_person_id} (${m.target_person_type})</div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger shadow-none" onclick="deleteHierarchy(${m.id})"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        </div>`;
                    });

                    html += `
                            </div>
                        </div>
                    </div>`;
                });

                $('#mobileCards').append(html);
                mobilePage++;

                if (groupsArray.length > (mobilePage * mobileGroupSize)) {
                    $('#loadMoreContainer').show();
                } else {
                    $('#loadMoreContainer').hide();
                }

                // Icon transition logic for Bootstrap collapse
                $('.collapse').off('show.bs.collapse').on('show.bs.collapse', function() {
                    $(this).parent().find('.transition-icon').removeClass('fa-chevron-down').addClass(
                        'fa-chevron-up');
                });
                $('.collapse').off('hide.bs.collapse').on('hide.bs.collapse', function() {
                    $(this).parent().find('.transition-icon').removeClass('fa-chevron-up').addClass(
                        'fa-chevron-down');
                });
            }

            // ==========================================
            // 🔍 CUSTOM SEARCH FOR DESKTOP & MOBILE
            // ==========================================
            $('#customSearch').on('keyup', function() {
                let val = this.value.toLowerCase();
                if (dataTableInstance) dataTableInstance.search(val).draw();

                $('#mobileCards .mobile-group-card').each(function() {
                    let text = $(this).text().toLowerCase();
                    $(this).toggle(text.includes(val));
                });
            });

            // ==========================================
            // ☑️ CHECKBOX & BULK DELETE LOGIC
            // ==========================================
            $('#selectAllDesktop, #selectAllMobile').on('change', function() {
                let isChecked = $(this).prop('checked');
                $('#selectAllDesktop, #selectAllMobile').prop('checked', isChecked);
                $('.row-checkbox:visible').prop('checked', isChecked);
                updateFloatingBtnState();
            });

            $(document).on('change', '.row-checkbox', function() {
                updateFloatingBtnState();
            });

            function updateFloatingBtnState() {
                let checkedCount = $('.row-checkbox:checked').length;
                $('#selCount').text(checkedCount);
                if (checkedCount > 0) {
                    $('#floatingDeleteBtn').fadeIn();
                } else {
                    $('#floatingDeleteBtn').fadeOut();
                    $('#selectAllDesktop, #selectAllMobile').prop('checked', false);
                }
            }

            // Single Delete
            window.deleteHierarchy = function(id) {
                Swal.fire({
                    title: 'Delete Mapping?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/api/v1/signatory-master/hierarchies/${id}`,
                            type: 'DELETE',
                            headers: token ? {
                                'Authorization': 'Bearer ' + token
                            } : {},
                            success: function(res) {
                                Swal.fire('Deleted!', res.message, 'success');
                                loadMappings();
                            }
                        });
                    }
                });
            }

            // Bulk Delete
            window.bulkDeleteSelected = function() {
                let selectedIds = [];
                $('.row-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length === 0) return;

                Swal.fire({
                    title: `Delete ${selectedIds.length} Mappings?`,
                    text: "This cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete all!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/api/v1/signatory-master/hierarchies/bulk-delete',
                            type: 'POST',
                            data: {
                                ids: selectedIds
                            },
                            headers: token ? {
                                'Authorization': 'Bearer ' + token
                            } : {},
                            success: function(res) {
                                Swal.fire('Deleted!', res.message, 'success');
                                loadMappings();
                            }
                        });
                    }
                });
            }
        });
    </script>
@endpush
