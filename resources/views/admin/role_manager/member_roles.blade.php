@extends('layout.app')
@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--multiple { border: 1px solid #cbd5e1; border-radius: 6px; min-height: 40px; }
    .filter-label { font-size: 12px; font-weight: bold; color: var(--sidebar-bg); }
    .action-links a { text-decoration: none; font-size: 11px; margin-left: 5px; }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-success"><i class="fas fa-users-cog me-2"></i> Member Role Exception Manager</h4>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light">
            <h6 class="mb-3 text-success border-bottom pb-2"><i class="fas fa-filter"></i> Target Member Selection</h6>
            <div class="row g-3">
                <div class="col-md-2"><label class="filter-label">1. Company</label>
                    <select id="filter_company" class="form-control select2" multiple="multiple"></select></div>
                <div class="col-md-2"><label class="filter-label">2. Branch</label>
                    <select id="filter_branch" class="form-control select2" multiple="multiple" disabled></select></div>
                <div class="col-md-3"><label class="filter-label">3. Department</label>
                    <select id="filter_department" class="form-control select2" multiple="multiple" disabled></select></div>
                <div class="col-md-2"><label class="filter-label">4. Designation</label>
                    <select id="filter_designation" class="form-control select2" multiple="multiple" disabled></select></div>
                
                <div class="col-md-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="filter-label">5. Select Member(s)</label>
                        <div class="action-links">
                            <a href="#" class="text-success fw-bold select-all-btn" data-target="#filter_member" data-endpoint="/targets">All</a> | 
                            <a href="#" class="text-danger clear-all-btn" data-target="#filter_member">Clear</a>
                        </div>
                    </div>
                    <select id="filter_member" class="form-control select2" multiple="multiple" disabled></select>
                </div>
            </div>
            <div class="mt-3 text-end">
                <button type="button" class="btn btn-warning btn-sm" id="reset_filters"><i class="fas fa-sync"></i> Reset All</button>
                <button type="button" class="btn btn-success btn-sm px-4" id="load_permissions_btn" disabled><i class="fas fa-cogs"></i> Load Exceptions</button>
            </div>
        </div>
    </div>
    <div id="permission_matrix_area" style="display: none;"></div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        const PAGE_TYPE = 'member'; // 🔥 IMPORTANT
        const API_BASE = '/api/v1/role-manager/dropdown'; 

        function getParams() {
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
                    url: API_BASE + endpoint, dataType: 'json', delay: 250,
                    data: function(params) { let fd = getParams(); fd.q = params.term; return fd; },
                    processResults: function(data) { return { results: data.results }; }
                }
            });
        }

        initSelect2('#filter_company', '/companies');

        $('#filter_company').on('change', function() {
            $('#filter_branch').empty().prop('disabled', false);
            $('#filter_department, #filter_designation, #filter_member').empty().prop('disabled', true);
            initSelect2('#filter_branch', '/branches');
        });

        $('#filter_branch').on('change', function() {
            $('#filter_department').empty().prop('disabled', false);
            $('#filter_designation, #filter_member').empty().prop('disabled', true);
            initSelect2('#filter_department', '/departments');
        });

        $('#filter_department').on('change', function() {
            $('#filter_designation').empty().prop('disabled', false);
            $('#filter_member').empty().prop('disabled', true);
            initSelect2('#filter_designation', '/designations');
        });

        $('#filter_designation').on('change', function() {
            $('#filter_member').empty().prop('disabled', false);
            initSelect2('#filter_member', '/targets');
        });

        $('#filter_member').on('change', function() {
            $('#load_permissions_btn').prop('disabled', !($(this).val() && $(this).val().length > 0));
        });

        // Load Exceptions
        $('#load_permissions_btn').on('click', function() {
            let targetIds = $('#filter_member').val();
            let btn = $(this); let originalHtml = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);

            $.ajax({
                url: '/api/v1/role-manager/matrix/load', type: 'POST',
                data: { target_ids: targetIds, type: PAGE_TYPE, _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(res) {
                    if(res.status === 'success') { $('#permission_matrix_area').html(res.html).slideDown(); } 
                    else { alert("❌ " + res.message); }
                },
                complete: function() { btn.html(originalHtml).prop('disabled', false); }
            });
        });

        // Save Exceptions
        $(document).on('click', '#save_exceptions_btn', function() {
            let targetIds = $('#filter_member').val();
            let selectedPerms = [];
            $('.perm-cb:checked').each(function() { selectedPerms.push($(this).val()); });

            let btn = $(this); let originalHtml = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

            $.ajax({
                url: '/api/v1/role-manager/matrix/save', type: 'POST',
                data: { target_ids: targetIds, permissions: selectedPerms, type: PAGE_TYPE, _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(res) {
                    if(res.status === 'success') { alert("✅ " + res.message); } 
                    else { alert("❌ " + res.message); }
                },
                complete: function() { btn.html(originalHtml).prop('disabled', false); }
            });
        });
        
        // Select All / Clear All Logic yahan paste kar sakte hain same as employee
        $('.select-all-btn').on('click', function(e) {
            e.preventDefault(); let targetId = $(this).data('target'); let endpoint = $(this).data('endpoint');
            let selectEl = $(targetId); if(selectEl.prop('disabled')) return;
            let originalText = $(this).text(); $(this).text('...');
            $.ajax({
                url: API_BASE + endpoint, type: 'GET', dataType: 'json', data: getParams(),
                success: function(res) {
                    if(res && res.results) {
                        selectEl.empty(); res.results.forEach(function(item) { selectEl.append(new Option(item.text, item.id, true, true)); });
                        selectEl.trigger('change');
                    }
                },
                complete: () => { $(this).text(originalText); }
            });
        });
        $('.clear-all-btn').on('click', function(e) { e.preventDefault(); $($(this).data('target')).val(null).trigger('change'); });
        $('#reset_filters').on('click', function() { $('#filter_company').val(null).trigger('change'); });
    });
</script>
@endpush
@endsection