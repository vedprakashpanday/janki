@extends('layout.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            min-height: 40px;
        }

        .filter-label {
            font-size: 12px;
            font-weight: bold;
            color: var(--sidebar-bg);
        }

        .action-links a {
            text-decoration: none;
            font-size: 11px;
            margin-left: 5px;
        }
    </style>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold text-secondary"><i class="fas fa-user-shield me-2"></i> Employee Role Exception Manager</h4>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-light">
                <h6 class="mb-3 text-primary border-bottom pb-2"><i class="fas fa-filter"></i> Target Employee Selection</h6>
                <div class="row g-3">

                    <div class="col-md-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="filter-label">1. Company</label>
                            <div class="action-links">
                                <a href="#" class="text-success fw-bold select-all-btn" data-target="#filter_company"
                                    data-endpoint="/companies">All</a> |
                                <a href="#" class="text-danger clear-all-btn" data-target="#filter_company">Clear</a>
                            </div>
                        </div>
                        <select id="filter_company" class="form-control select2" multiple="multiple"
                            data-placeholder="Select Companies"></select>
                    </div>

                    <div class="col-md-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="filter-label">2. Branch</label>
                            <div class="action-links">
                                <a href="#" class="text-success fw-bold select-all-btn" data-target="#filter_branch"
                                    data-endpoint="/branches">All</a> |
                                <a href="#" class="text-danger clear-all-btn" data-target="#filter_branch">Clear</a>
                            </div>
                        </div>
                        <select id="filter_branch" class="form-control select2" multiple="multiple"
                            data-placeholder="Select Branches" disabled></select>
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="filter-label">3. Department</label>
                            <div class="action-links">
                                <a href="#" class="text-success fw-bold select-all-btn"
                                    data-target="#filter_department" data-endpoint="/departments">All</a> |
                                <a href="#" class="text-danger clear-all-btn"
                                    data-target="#filter_department">Clear</a>
                            </div>
                        </div>
                        <select id="filter_department" class="form-control select2" multiple="multiple"
                            data-placeholder="Select Depts" disabled></select>
                    </div>

                    <div class="col-md-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="filter-label">4. Designation</label>
                            <div class="action-links">
                                <a href="#" class="text-success fw-bold select-all-btn"
                                    data-target="#filter_designation" data-endpoint="/designations">All</a> |
                                <a href="#" class="text-danger clear-all-btn"
                                    data-target="#filter_designation">Clear</a>
                            </div>
                        </div>
                        <select id="filter_designation" class="form-control select2" multiple="multiple"
                            data-placeholder="Select Desig" disabled></select>
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="filter-label">5. Employee</label>
                            <div class="action-links">
                                <a href="#" class="text-success fw-bold select-all-btn" data-target="#filter_employee"
                                    data-endpoint="/targets">All</a> |
                                <a href="#" class="text-danger clear-all-btn" data-target="#filter_employee">Clear</a>
                            </div>
                        </div>
                        <select id="filter_employee" class="form-control select2" multiple="multiple"
                            data-placeholder="Select Employee(s)" disabled></select>
                    </div>

                </div>
                <div class="mt-3 text-end">
                    <button type="button" class="btn btn-warning btn-sm" id="reset_filters"><i class="fas fa-sync"></i>
                        Reset All</button>
                    <button type="button" class="btn btn-primary btn-sm px-4" id="load_permissions_btn" disabled><i
                            class="fas fa-cogs"></i> Load Exceptions</button>
                </div>
            </div>
        </div>

        <div id="permission_matrix_area" style="display: none;"></div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                const PAGE_TYPE = 'employee';
                const API_BASE = '/api/v1/role-manager/dropdown';

                // Current AJAX parameters function
                function getParams(type) {
                    return {
                        type: PAGE_TYPE,
                        company_ids: $('#filter_company').val() || [],
                        branch_ids: $('#filter_branch').val() || [],
                        department_ids: $('#filter_department').val() || [],
                        designation_ids: $('#filter_designation').val() || []
                    };
                }

                function initSelect2(id, endpoint) {
                    $(id).select2({
                        ajax: {
                            url: API_BASE + endpoint,
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                let filterData = getParams();
                                filterData.q = params.term;
                                return filterData;
                            },
                            processResults: function(data) {
                                return {
                                    results: data.results
                                };
                            }
                        }
                    });
                }

                initSelect2('#filter_company', '/companies');

                // Cascade Triggers
                $('#filter_company').on('change', function() {
                    $('#filter_branch').empty().prop('disabled', false);
                    $('#filter_department, #filter_designation, #filter_employee').empty().prop('disabled',
                        true);
                    initSelect2('#filter_branch', '/branches');
                });

                $('#filter_branch').on('change', function() {
                    $('#filter_department').empty().prop('disabled', false);
                    $('#filter_designation, #filter_employee').empty().prop('disabled', true);
                    initSelect2('#filter_department', '/departments');
                });

                $('#filter_department').on('change', function() {
                    $('#filter_designation').empty().prop('disabled', false);
                    $('#filter_employee').empty().prop('disabled', true);
                    initSelect2('#filter_designation', '/designations');
                });

                $('#filter_designation').on('change', function() {
                    $('#filter_employee').empty().prop('disabled', false);
                    initSelect2('#filter_employee', '/targets');
                });

                $('#filter_employee').on('change', function() {
                    $('#load_permissions_btn').prop('disabled', !($(this).val() && $(this).val().length > 0));
                });

              // 🟢 SELECT ALL LOGIC (Fetch from API and append)
        $('.select-all-btn').on('click', function(e) {
            e.preventDefault();
            let targetId = $(this).data('target');
            let endpoint = $(this).data('endpoint');
            let selectEl = $(targetId);
            
            if(selectEl.prop('disabled')) return; // Ignore if disabled
            
            let originalText = $(this).text();
            $(this).text('...');
            
            $.ajax({
                url: API_BASE + endpoint,
                type: 'GET',
                dataType: 'json', // 🔥 YE MISSING THA
                data: getParams(), 
                success: function(res) {
                    // 🔥 SAFETY CHECK ADD KIYA HAI
                    if(res && res.results) {
                        selectEl.empty(); // Clear old
                        res.results.forEach(function(item) {
                            selectEl.append(new Option(item.text, item.id, true, true));
                        });
                        selectEl.trigger('change');
                    } else {
                        console.error("API did not return 'results' array.", res);
                        alert("Data load nahi hua. Console check karein.");
                    }
                },
                error: function(xhr) {
                    console.error("API Error: ", xhr.responseText);
                },
                complete: () => { $(this).text(originalText); }
            });
        });

                // 🔴 CLEAR ALL LOGIC
                $('.clear-all-btn').on('click', function(e) {
                    e.preventDefault();
                    let targetId = $(this).data('target');
                    if (!$(targetId).prop('disabled')) {
                        $(targetId).val(null).trigger('change');
                    }
                });

                // Reset
                $('#reset_filters').on('click', function() {
                    $('#filter_company').val(null).trigger('change');
                });



                
            // 🟢 LOAD EXCEPTIONS MATRIX LOGIC
     // 🟢 LOAD EXCEPTIONS MATRIX LOGIC
        $('#load_permissions_btn').on('click', function() {
            let employeeIds = $('#filter_employee').val();
            
            if(!employeeIds || employeeIds.length === 0) {
                alert("Please select at least one employee.");
                return;
            }

            let btn = $(this);
            let originalHtml = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);

            $.ajax({
                url: '/api/v1/role-manager/matrix/load',
                type: 'POST',
                data: {
                    employee_ids: employeeIds,
                    type: PAGE_TYPE,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    if(res.status === 'success') {
                        $('#permission_matrix_area').html(res.html).slideDown();
                    } else {
                        // Backend ne proper error message bheja hai
                        alert("❌ " + res.message);
                    }
                },
                error: function(xhr) {
                    // Agar API ka URL galat hua ya Route Crash hua toh yahan aayega
                    alert("⚠️ Network/Route Error: Inspect console for details.");
                    console.error("AJAX Error Details: ", xhr.responseText);
                },
                complete: function() {
                    btn.html(originalHtml).prop('disabled', false);
                }
            });
        });

        // 🔴 SAVE EXCEPTIONS LOGIC
        $(document).on('click', '#save_exceptions_btn', function() {
            let employeeIds = $('#filter_employee').val();
            let selectedPerms = [];
            
            $('.perm-cb:checked').each(function() {
                selectedPerms.push($(this).val());
            });

            let btn = $(this);
            let originalHtml = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

            $.ajax({
                url: '/api/v1/role-manager/matrix/save',
                type: 'POST',
                data: {
                    employee_ids: employeeIds,
                    permissions: selectedPerms,
                    type: PAGE_TYPE,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    if(res.status === 'success') {
                        alert("✅ " + res.message); 
                    } else {
                        alert("❌ " + res.message);
                    }
                },
                error: function(xhr) {
                    alert("⚠️ Network/Route Error while saving.");
                    console.error(xhr.responseText);
                },
                complete: function() {
                    btn.html(originalHtml).prop('disabled', false);
                }
            });
        });





            });



        </script>
    @endpush
@endsection
