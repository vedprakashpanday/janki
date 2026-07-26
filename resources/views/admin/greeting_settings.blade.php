@extends('layout.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold text-dark"><i class="fas fa-envelope-open-text text-primary me-2"></i> Greeting Templates Settings</h4>
        <button id="saveTemplatesBtn" class="btn btn-success fw-medium shadow-sm">
            <i class="fas fa-save me-2"></i> Save Changes
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="alert alert-info border-0 rounded-3 mb-4">
                <i class="fas fa-info-circle me-2"></i> <strong>Smart Variables:</strong> Aap messages me <code>[Name]</code>, <code>[Company]</code>, ya <code>[Years]</code> use kar sakte hain. System inko user ke actual data se automatic replace kar dega.
            </div>

            <form id="greetingSettingsForm">
                <div class="row">
                    <!-- Birthday Template -->
                    <div class="col-md-4 mb-4">
                        <label class="form-label fw-bold text-dark"><i class="fas fa-birthday-cake text-warning me-2"></i> Birthday Template</label>
                        <textarea class="form-control bg-light template-input" data-type="birthday" rows="12" placeholder="Birthday message here..."></textarea>
                    </div>

                    <!-- Anniversary Template -->
                    <div class="col-md-4 mb-4">
                        <label class="form-label fw-bold text-dark"><i class="fas fa-rings text-danger me-2"></i> Wedding Anniversary</label>
                        <textarea class="form-control bg-light template-input" data-type="anniversary" rows="12" placeholder="Wedding Anniversary message here..."></textarea>
                    </div>

                    <!-- Work Anniversary Template -->
                    <div class="col-md-4 mb-4">
                        <label class="form-label fw-bold text-dark"><i class="fas fa-briefcase text-success me-2"></i> Work Anniversary</label>
                        <textarea class="form-control bg-light template-input" data-type="work_anniversary" rows="12" placeholder="Work Anniversary message here..."></textarea>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Load Templates on Page Load
    function loadTemplates() {
        $.ajax({
            url: '/api/v1/greeting-templates',
            type: 'GET',
            success: function(response) {
                if(response.success) {
                    response.data.forEach(function(item) {
                        $('.template-input[data-type="' + item.event_type + '"]').val(item.template_text);
                    });
                }
            },
            error: function(err) {
                console.error("Failed to load templates", err);
                Swal.fire('Error', 'Could not load templates.', 'error');
            }
        });
    }

    loadTemplates();

    // Save Templates
    $('#saveTemplatesBtn').click(function(e) {
        e.preventDefault();
        let btn = $(this);
        let originalText = btn.html();
        
        let payload = { templates: [] };
        $('.template-input').each(function() {
            payload.templates.push({
                event_type: $(this).data('type'),
                template_text: $(this).val()
            });
        });

        btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Saving...').prop('disabled', true);

        $.ajax({
            url: '/api/v1/greeting-templates',
            type: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            success: function(response) {
                if(response.success) {
                    Swal.fire({ icon: 'success', title: 'Saved!', text: response.message, timer: 2000, showConfirmButton: false });
                }
            },
            error: function(err) {
                Swal.fire('Error', 'Failed to save templates.', 'error');
            },
            complete: function() {
                btn.html(originalText).prop('disabled', false);
            }
        });
    });
});
</script>
@endpush