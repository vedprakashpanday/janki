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
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                style="width: 70px; height: 70px;">
                                <i class="fas fa-id-badge fs-2 text-primary"></i>
                            </div>
                            <h5 class="fw-bold text-dark">Select Staff Member</h5>
                            <p class="text-muted small">Choose an employee or member from the list below.</p>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary">Search Name or ID <span
                                    class="text-danger">*</span></label>
                            <input type="text" id="staff_input" class="form-control form-control-lg bg-light"
                                list="staffList" placeholder="Type to search..." autocomplete="off">
                            <datalist id="staffList"></datalist>
                        </div>

                        <div id="actionButtons" style="display: none;">
                            <hr class="my-4">
                            <h6 class="fw-bold text-secondary mb-3">Available Print Actions</h6>
                            <div class="d-grid gap-3">
                                <button class="btn btn-primary btn-lg fw-bold print-btn shadow-sm secured-item"
                                    data-permission="id_card_print" data-type="id_card">
                                    <i class="fas fa-id-card me-2"></i> Print ID Card
                                </button>
                                <button class="btn btn-outline-primary btn-lg fw-bold print-btn bg-white secured-item"
                                    data-permission="id_card_print" data-type="visiting_normal">
                                    <i class="fas fa-address-card me-2"></i> Print Visiting Card (Normal)
                                </button>
                                <button class="btn btn-dark btn-lg fw-bold print-btn shadow-sm secured-item"
                                    data-permission="id_card_print" data-type="visiting_premium">
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

            // Load Datalist via API (API CLEANED: Removed /admin/ and Token Headers)
            $.ajax({
                url: '/api/v1/id-cards/staff-list',
                type: 'GET',
                success: function(res) {
                    let options = '';
                    if (res.data) {
                        res.data.forEach(s => {
                            options += `<option value="${s.id}">${s.name} (${s.type})</option>`;
                        });
                    }
                    $('#staffList').html(options);
                },
                error: function(err) {
                    console.error("Failed to load staff list", err);
                }
            });

            // Show buttons only if input has value
            $('#staff_input').on('input', function() {
                let val = $(this).val().trim();
                if (val.length > 0) {
                    $('#actionButtons').slideDown(300, function() {
                        // 🛡️ RE-APPLY PERMISSIONS jab buttons screen par show ho jayein
                        if (typeof window.applyPermissions === 'function') {
                            window.applyPermissions();
                        }
                    });
                } else {
                    $('#actionButtons').slideUp(300);
                }
            });

        // Print logic (Opens in new tab)
    $('.print-btn').on('click', function() {
        let type = $(this).data('type');
        let id = $('#staff_input').val().trim();
        
        console.log(type);
        console.log(id);
        
        
        if(!id) {
            alert('Please select a valid staff member first.');
            return;
        }
        
        // 🔥 NAYA: /admin/ lagaya gaya hai, aur ID ko ?member_id= karke bheja gaya hai 🔥
        //window.open(`/admin/id-cards/print/${type}?member_id=${encodeURIComponent(id)}`, '_blank');
    });
        });
    </script>
@endpush
