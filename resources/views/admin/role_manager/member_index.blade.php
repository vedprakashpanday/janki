@extends('layout.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-success"><i class="fas fa-crown me-2"></i> Member Master Grade Setup</h4>
        <a href="{{ url(request()->segment(1) . '/role-manager/member') }}" class="btn btn-warning btn-sm"><i class="fas fa-user-shield"></i> Go to Member Exceptions</a>
    </div>

    <div class="alert alert-info small">
        <i class="fas fa-info-circle"></i> <b>Note:</b> Yahan set ki gayi permissions directly us Member Grade ke sabhi members par automatically apply ho jayengi.
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold text-secondary">Select Member Grade</label>
                    <select id="master_grade_select" class="form-select border-success">
                        <option value="">-- Select Grade --</option>
                        <option value="Member Grade A">Member Grade A</option>
                        <option value="Member Grade B">Member Grade B</option>
                        <option value="Member Grade C">Member Grade C</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-success px-4" id="load_grade_matrix_btn" disabled>
                        <i class="fas fa-cogs"></i> Load Matrix
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="grade_matrix_area" style="display: none;"></div>
</div>

@push('scripts')
<script>
    // JS bilkul employee wale Master setup jaisa hoga
    $(document).ready(function() {
        $('#master_grade_select').on('change', function() {
            $('#load_grade_matrix_btn').prop('disabled', !$(this).val());
        });

        $('#load_grade_matrix_btn').on('click', function() {
            let roleName = $('#master_grade_select').val();
            let btn = $(this); let originalHtml = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);

            $.ajax({
                url: '/api/v1/role-manager/grade-matrix/load',
                type: 'POST',
                data: { role_name: roleName, _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(res) {
                    if(res.status === 'success') { $('#grade_matrix_area').html(res.html).slideDown(); } 
                    else { alert(res.message); }
                },
                complete: function() { btn.html(originalHtml).prop('disabled', false); }
            });
        });

        $(document).on('click', '#save_grade_btn', function() {
            let roleName = $('#master_grade_select').val();
            let selectedPerms = [];
            $('.perm-cb:checked').each(function() { selectedPerms.push($(this).val()); });

            let btn = $(this); let originalHtml = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

            $.ajax({
                url: '/api/v1/role-manager/grade-matrix/save',
                type: 'POST',
                data: { role_name: roleName, permissions: selectedPerms, _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(res) {
                    if(res.status === 'success') { alert("✅ " + res.message); } 
                    else { alert("❌ " + res.message); }
                },
                complete: function() { btn.html(originalHtml).prop('disabled', false); }
            });
        });
    });
</script>
@endpush
@endsection