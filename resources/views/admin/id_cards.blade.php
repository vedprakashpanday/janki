@extends('layout.app')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0" style="color: var(--sidebar-bg);">Generate ID & Visiting Cards</h4>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="fas fa-id-badge fs-2 text-primary"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Select Staff Member</h5>
                        <p class="text-muted small">Choose an employee or member from the list below.</p>
                    </div>

                    <div class="mb-4">
    <label class="form-label fw-bold text-secondary">Search Staff Name or ID</label>
    <div class="input-group">
        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
        <input type="text" id="staff_input" class="form-control form-control-lg bg-light border-start-0" 
               list="staffList" placeholder="e.g. ABA/BR/DAR1/001" autocomplete="off">
    </div>
    <datalist id="staffList"></datalist> </div>

                    <div id="actionButtons" style="display: none;">
                        <hr class="my-4">
                        <h6 class="fw-bold text-secondary mb-3">Available Print Actions</h6>
                        <div class="d-grid gap-3">
                            <button class="btn btn-primary btn-lg fw-bold print-btn shadow-sm" data-type="id_card">
                                <i class="fas fa-id-card me-2"></i> Print ID Card
                            </button>
                            <button class="btn btn-outline-success btn-lg fw-bold print-btn " data-type="visiting_normal">
                                <i class="fas fa-address-card me-2"></i> Print Visiting Card (Normal)
                            </button>
                            <button class="btn btn-dark btn-lg fw-bold print-btn shadow-sm" data-type="visiting_premium">
                                <i class="fas fa-gem text-warning me-2"></i> Print Visiting Card (Premium)
                            </button>
                        </div>
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
    const apiToken = localStorage.getItem('admin_token');
    
    // Load Datalist via API
    $.ajax({
        url: '/api/v1/admin/id-cards/staff-list',
        headers: { 'Authorization': 'Bearer ' + apiToken },
        success: function(res) {
            let options = '';
            res.data.forEach(s => {
                options += `<option value="${s.id}">${s.name} (${s.type})</option>`;
            });
            $('#staffList').html(options);
        }
    });

    // Show buttons only if input has value
    $('#staff_input').on('input', function() {
        let val = $(this).val().trim();
        if(val.length > 0) {
            $('#actionButtons').slideDown();
        } else {
            $('#actionButtons').slideUp();
        }
    });

    // Print logic (Opens in new tab exactly like your PHP flow)
   $('.print-btn').on('click', function() {
    let type = $(this).data('type');
    let id = $('#staff_input').val().trim();
    
    if(!id) {
        alert('Please select a valid staff member first.');
        return;
    }
    
    // NAYA FIX: encodeURIComponent use kiya taaki slashes safe rahein 
    let safeId = encodeURIComponent(id);
    window.open(`/admin/id-cards/print/${type}/${safeId}`, '_blank');
});
});
</script>
@endpush