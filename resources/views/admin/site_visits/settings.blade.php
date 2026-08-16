@extends('layout.app')

@section('content')
<style>
    .floating-action-bar {
        position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%);
        background: #fff; padding: 10px 20px; border-radius: 30px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2); z-index: 1050; display: none;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 text-dark fw-bold"><i class="fas fa-cog text-primary me-2"></i>Site Visit Payout Settings</h4>
    </div>

    <div class="row">
        <!-- Add/Edit Setting Form -->
        <div class="col-md-4 secured-item" data-permission="sv_settings">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0" id="formTitle">Add New Setting</h6>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="cancelEditBtn">Cancel</button>
                </div>
                <div class="card-body">
                    <form id="settingForm">
                        <input type="hidden" id="edit_setting_id" name="edit_setting_id">
                        <input type="hidden" id="form_method" name="_method" value="POST">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Min Visits (From) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="min_visits" id="min_visits" required min="0" placeholder="e.g. 0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Max Visits (To)</label>
                            <input type="number" class="form-control" name="max_visits" id="max_visits" placeholder="Leave blank for unlimited">
                            <small class="text-muted" style="font-size: 11px;">E.g. If 1 to 10 visits give ₹100, put Min 1, Max 10.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Payout Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="amount" id="amount" required placeholder="e.g. 500">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Applicable From Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="start_date" id="start_date" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold" id="saveSettingBtn">Save Setting</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Settings List -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="settingsTable">
                            <thead class="bg-light text-secondary" style="font-size: 12px; text-transform: uppercase;">
                                <tr>
                                    <th class="ps-4"><input type="checkbox" id="selectAllSettings"></th>
                                    <th>Sl No.</th>
                                    <th>Visit Range</th>
                                    <th>Amount (₹)</th>
                                    <th>Valid From</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody id="settingsTableBody">
                                <!-- Data AJAX se yahan load hoga -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="floating-action-bar" id="bulkActionBarSettings">
    <span class="fw-bold me-3 text-dark"><span id="selectedCountSettings">0</span> Selected</span>
    <button class="btn btn-danger btn-sm rounded-pill secured-item" data-permission="sv_settings" onclick="bulkDeleteSettings()"><i class="fas fa-trash"></i> Delete Selected</button>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function formatDateToDDMMYYYY(dateString) {
        if(!dateString) return '';
        let d = new Date(dateString);
        let day = ("0" + d.getDate()).slice(-2);
        let month = ("0" + (d.getMonth() + 1)).slice(-2);
        let year = d.getFullYear();
        return `${day}-${month}-${year}`;
    }

    function loadSettings() {
        $.ajax({
            url: '/api/v1/site-visit-settings',
            type: 'GET',
            success: function(res) {
                let html = '';
                if(res.data.length === 0) {
                    html = '<tr><td colspan="6" class="text-center py-4 text-muted">No settings found.</td></tr>';
                } else {
                    res.data.forEach(function(s, index) {
                        let maxV = s.max_visits ? s.max_visits : 'Unlimited';
                        let range = `<span class="badge bg-secondary">${s.min_visits} to ${maxV}</span>`;
                        let formattedDate = formatDateToDDMMYYYY(s.start_date);
                        
                        html += `
                            <tr>
                                <td class="ps-4"><input type="checkbox" class="row-checkbox-setting form-check-input" value="${s.id}"></td>
                                <td>${index + 1}</td>
                                <td>${range}</td>
                                <td class="fw-bold text-success">₹${parseFloat(s.amount).toFixed(2)}</td>
                                <td>${formattedDate}</td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <button class="btn btn-sm btn-light text-primary edit-btn secured-item" data-permission="sv_settings" data-id="${s.id}" title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light text-danger delete-btn secured-item" data-permission="sv_settings" data-id="${s.id}" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                }
                $('#settingsTableBody').html(html);
                if (typeof window.applyPermissions === 'function') window.applyPermissions();
            }
        });
    }

    loadSettings();

    // Reset Form to "Add" Mode
    function resetForm() {
        $('#settingForm')[0].reset();
        $('#edit_setting_id').val('');
        $('#form_method').val('POST');
        $('#formTitle').text('Add New Setting');
        $('#saveSettingBtn').text('Save Setting');
        $('#cancelEditBtn').addClass('d-none');
    }

    $('#cancelEditBtn').click(resetForm);

    // Edit Button Click Logic
    $(document).on('click', '.edit-btn', function() {
        let id = $(this).data('id');
        $.ajax({
            url: `/api/v1/site-visit-settings/${id}`,
            type: 'GET',
            success: function(res) {
                let d = res.data;
                $('#edit_setting_id').val(d.id);
                $('#form_method').val('PUT'); // Change method to PUT for update
                $('#min_visits').val(d.min_visits);
                $('#max_visits').val(d.max_visits);
                $('#amount').val(d.amount);
                $('#start_date').val(d.start_date);
                
                $('#formTitle').text('Edit Setting');
                $('#saveSettingBtn').text('Update Setting');
                $('#cancelEditBtn').removeClass('d-none');
            }
        });
    });

    // Save or Update Form Submit
    $('#settingForm').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#saveSettingBtn');
        let editId = $('#edit_setting_id').val();
        let url = editId ? `/api/v1/site-visit-settings/${editId}` : '/api/v1/site-visit-settings';
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: url,
            type: 'POST', // Blade me _method se PUT pass hoga
            data: $(this).serialize(),
            success: function(res) {
                Swal.fire({ icon: 'success', title: 'Success', text: res.message, timer: 1500, showConfirmButton: false });
                resetForm();
                loadSettings();
            },
            error: function(err) { Swal.fire({ icon: 'error', title: 'Error', text: err.responseJSON?.message || 'Something went wrong!' }); },
            complete: function() { btn.prop('disabled', false).html(editId ? 'Update Setting' : 'Save Setting'); }
        });
    });

    // Delete Logic
    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        Swal.fire({ title: 'Are you sure?', text: "This payout setting will be removed!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#E53E3E', confirmButtonText: 'Yes, delete it!' }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/api/v1/site-visit-settings/' + id,
                    type: 'DELETE',
                    success: function(res) { Swal.fire({ icon: 'success', title: 'Deleted!', text: res.message, timer: 1500, showConfirmButton: false }); loadSettings(); }
                });
            }
        });
    });

    // Bulk Delete Logic
    function toggleSettingsActionBar() {
        let count = $('.row-checkbox-setting:checked').length;
        if(count > 0) { $('#selectedCountSettings').text(count); $('#bulkActionBarSettings').fadeIn(); } else { $('#bulkActionBarSettings').fadeOut(); }
    }
    
    $(document).on('change', '.row-checkbox-setting', toggleSettingsActionBar);
    $('#selectAllSettings').change(function() { $('.row-checkbox-setting').prop('checked', $(this).prop('checked')); toggleSettingsActionBar(); });

    window.bulkDeleteSettings = function() {
        let ids = []; $('.row-checkbox-setting:checked').each(function() { ids.push($(this).val()); });
        Swal.fire({ title: 'Are you sure?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#E53E3E', confirmButtonText: 'Yes, delete!' }).then((result) => {
            if (result.isConfirmed) {
                $.post('/api/v1/site-visit-settings/bulk-delete', { ids: ids }, function(res) {
                    $('#bulkActionBarSettings').fadeOut(); $('#selectAllSettings').prop('checked', false);
                    Swal.fire({ icon: 'success', title: 'Deleted', text: res.message, timer: 1500, showConfirmButton: false }); loadSettings();
                });
            }
        });
    };
});
</script>
@endpush