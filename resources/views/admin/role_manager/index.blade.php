@extends('layout.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold text-secondary"><i class="fas fa-crown me-2"></i> Master Grade (Role) Setup</h4>
          <a href="{{ url(request()->segment(1) . '/role-manager/employee') }}" class="btn btn-warning btn-sm"><i class="fas fa-user-shield"></i> Go to Exceptions</a>
        </div>

        <div class="alert alert-info small">
            <i class="fas fa-info-circle"></i> <b>Note:</b> Yahan set ki gayi permissions directly us Grade (Role) ke sabhi
            users par automatically apply ho jayengi. Individual exceptions ke liye "Go to Exceptions" par click karein.
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-light">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-secondary">Select Grade (Role)</label>
                        <select id="master_grade_select" class="form-select border-primary">
                            <option value="">-- Select Grade --</option>
                            <option value="Grade A">Grade A</option>
                            <option value="Grade B">Grade B</option>
                            <option value="Grade C">Grade C</option>
                            <option value="Grade D">Grade D</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary px-4" id="load_grade_matrix_btn" disabled>
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
            $(document).ready(function() {

                // Enable load button when grade is selected
                $('#master_grade_select').on('change', function() {
                    if ($(this).val()) {
                        $('#load_grade_matrix_btn').prop('disabled', false);
                    } else {
                        $('#load_grade_matrix_btn').prop('disabled', true);
                    }
                });

                // 🟢 LOAD GRADE MATRIX
                $('#load_grade_matrix_btn').on('click', function() {
                    let roleName = $('#master_grade_select').val();
                    let btn = $(this);
                    let originalHtml = btn.html();

                    btn.html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);

                    $.ajax({
                        url: '/api/v1/role-manager/grade-matrix/load',
                        type: 'POST',
                        data: {
                            role_name: roleName,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(res) {
                            if (res.status === 'success') {
                                $('#grade_matrix_area').html(res.html).slideDown();
                            } else {
                                alert(res.message);
                            }
                        },
                        complete: function() {
                            btn.html(originalHtml).prop('disabled', false);
                        }
                    });
                });

                // 🔴 SAVE GRADE MATRIX
                $(document).on('click', '#save_grade_btn', function() {
                    let roleName = $('#master_grade_select').val();
                    let selectedPerms = [];

                    $('.perm-cb:checked').each(function() {
                        selectedPerms.push($(this).val());
                    });

                    let btn = $(this);
                    let originalHtml = btn.html();
                    btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

                    $.ajax({
                        url: '/api/v1/role-manager/grade-matrix/save',
                        type: 'POST',
                        data: {
                            role_name: roleName,
                            permissions: selectedPerms,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(res) {
                            if (res.status === 'success') {
                                alert("✅ " + res.message);
                            } else {
                                alert("❌ " + res.message);
                            }
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
