@extends('layout.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-dark"><i class="fas fa-cogs text-primary me-2"></i> Dynamic Task Tracking Engine</h4>
            <span class="badge bg-danger"><i class="fas fa-lock me-1"></i> Developer Zone Only</span>
        </div>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold mb-0 text-dark">Create New Tracking Rule</h6>
                    </div>
                    <div class="card-body">
                        <form id="trackingConfigForm">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Task Category Name</label>
                                <input type="text" class="form-control" name="task_category_name"
                                    placeholder="e.g., Debit Voucher Entry" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Target Table</label>
                                <select class="form-select" id="targetTableSelect" name="target_table" required>
                                    <option value="">Loading tables...</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">User ID Column (Kisne entry ki)</label>
                                <select class="form-select column-select" name="user_id_column" required disabled>
                                    <option value="">Select table first</option>
                                </select>
                                <small class="text-secondary" style="font-size: 11px;">E.g., approved_by,
                                    assigned_telecaller</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Join Match Value</label>
                                <select class="form-select" name="join_column" required>
                                    <option value="member_id">Match with User's 'member_id' (e.g. ABDPL-A/0007)</option>
                                    <option value="id">Match with User's 'id' (Auto-increment ID)</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-muted small fw-bold">Date Column (Tracking filter ke
                                    liye)</label>
                                <select class="form-select column-select" name="date_column" required disabled>
                                    <option value="">Select table first</option>
                                </select>
                                <small class="text-secondary" style="font-size: 11px;">Usually 'created_at' or
                                    'updated_at'</small>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm" id="saveConfigBtn">
                                <i class="fas fa-save me-2"></i> Save Configuration
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold mb-0 text-dark">Active Tracking Modules</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="configuredModulesTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-secondary small fw-bold">Category Name</th>
                                        <th class="text-secondary small fw-bold">Target Table</th>
                                        <th class="text-secondary small fw-bold">User Column</th>
                                        <th class="text-secondary small fw-bold">Date Column</th>
                                        <th class="text-secondary small fw-bold text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4"><i
                                                class="fas fa-spinner fa-spin me-2"></i> Loading data...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // 1. Fetch Database Tables on Load
    function loadTables() {
        $.ajax({
            url: '/api/v1/developer/tables', // 🔥 CORRECTED URL (Removed /admin)
            type: 'GET',
            success: function(res) {
                let options = '<option value="">-- Select Target Table --</option>';
                res.data.forEach(function(table) {
                    options += `<option value="${table}">${table}</option>`;
                });
                $('#targetTableSelect').html(options);
            },
            error: function(err) {
                Swal.fire('Error', 'Failed to fetch database tables. Ensure you have Developer access.', 'error');
            }
        });
    }

    // 2. Fetch Configured Modules
    function loadConfiguredModules() {
        $.ajax({
            url: '/api/v1/tracking-modules', // 🔥 CORRECTED URL (Removed /admin)
            type: 'GET',
            success: function(res) {
                let rows = '';
                if(res.data.length === 0) {
                    rows = '<tr><td colspan="5" class="text-center text-muted py-4">No tracking rules configured yet.</td></tr>';
                } else {
                    res.data.forEach(function(mod) {
                        rows += `
                            <tr>
                                <td class="fw-bold text-dark">${mod.task_category_name}</td>
                                <td><span class="badge bg-dark">${mod.target_table}</span></td>
                                <td><code class="text-primary">${mod.user_id_column}</code></td>
                                <td><code class="text-secondary">${mod.date_column}</code></td>
                                <td class="text-center"><span class="badge bg-success"><i class="fas fa-check-circle"></i> Active</span></td>
                            </tr>
                        `;
                    });
                }
                $('#configuredModulesTable tbody').html(rows);
            }
        });
    }

    // 3. Handle Table Selection (Fetch Columns dynamically)
    $('#targetTableSelect').on('change', function() {
        let tableName = $(this).val();
        let columnSelects = $('.column-select');
        
        if (!tableName) {
            columnSelects.html('<option value="">Select table first</option>').prop('disabled', true);
            return;
        }

        columnSelects.html('<option value="">Fetching columns...</option>').prop('disabled', true);

        $.ajax({
            url: '/api/v1/developer/columns', // 🔥 CORRECTED URL (Removed /admin)
            type: 'POST',
            data: { table_name: tableName },
            success: function(res) {
                let options = '<option value="">-- Select Column --</option>';
                res.data.forEach(function(col) {
                    options += `<option value="${col}">${col}</option>`;
                });
                columnSelects.html(options).prop('disabled', false);
            }
        });
    });

    // 4. Submit Form
    $('#trackingConfigForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#saveConfigBtn');
        let originalText = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

        $.ajax({
            url: '/api/v1/developer/tracking-modules', // 🔥 CORRECTED URL (Removed /admin)
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                $('#trackingConfigForm')[0].reset();
                $('.column-select').html('<option value="">Select table first</option>').prop('disabled', true);
                loadConfiguredModules();
            },
            error: function(err) {
                let msg = err.responseJSON ? err.responseJSON.message : 'An error occurred';
                Swal.fire('Error', msg, 'error');
            },
            complete: function() {
                btn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Initialize
    loadTables();
    loadConfiguredModules();
});
</script>
@endpush