@extends('layout.app')

@section('content')
    <style>
        /* 🔥 ANTI-PRINT CSS 🔥 */
        @media print {

            html,
            body {
                display: none !important;
                visibility: hidden !important;
            }
        }

        /* 🔥 ANTI-COPY & SELECTION CSS 🔥 */
        body,
        .letter-container {
            user-select: none !important;
            -webkit-user-select: none !important;
            -moz-user-select: none !important;
            -ms-user-select: none !important;
        }

        /* Letter Box Styling */
        .letter-container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            min-height: 500px;
            border: 1px solid var(--border-color);
            position: relative;
        }
    </style>

    <div class="mb-3 w-100">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0" style="color: var(--sidebar-bg);">
                <i class="fas fa-envelope-open-text text-info me-2"></i> My Welcome Letter
            </h5>
            <span class="badge bg-danger px-3 py-2"><i class="fas fa-shield-alt me-1"></i> Secured Document</span>
        </div>

        <div class="letter-container" id="secure-area">
            <div id="letter-body">
                <div class="text-center p-3">
                    <i class="fas fa-spinner fa-spin fa-3x text-warning mb-3"></i>
                    <p class="text-muted fw-bold fs-5">Generating your secure letter...</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // 🔥 ANTI-SCREENSHOT & HOTKEYS SCRIPT 🔥
        document.addEventListener('contextmenu', event => event.preventDefault()); // Block Right Click

        document.addEventListener('keydown', function(e) {
            // Block Print Screen
            if (e.key === 'PrintScreen') {
                navigator.clipboard.writeText('Screenshots are disabled for this document.');
                Swal.fire('Action Denied', 'Taking screenshots is not allowed.', 'warning');
            }
            // Block Ctrl+P (Print), Ctrl+S (Save), Ctrl+C (Copy), Ctrl+U (Source)
            if (e.ctrlKey && (e.key === 'p' || e.key === 'P' || e.key === 's' || e.key === 'S' || e.key === 'c' || e
                    .key === 'C' || e.key === 'u' || e.key === 'U')) {
                e.preventDefault();
                Swal.fire('Action Denied', 'This action is disabled for security reasons.', 'warning');
            }
        });

        $(document).ready(function() {
            // Fetch letter content using Shared API
            $.ajax({
                url: '/api/v1/welcome-letter/generate',
                type: 'GET',
                success: function(res) {
                    if (res.success) {
                        $('#letter-body').html(res.data);
                    } else {
                        $('#letter-body').html(
                            '<div class="alert alert-warning text-center mt-4"><strong>Notice:</strong> ' +
                            res.message + '</div>');
                    }
                },
                error: function(err) {
                    $('#letter-body').html(
                        '<div class="alert alert-danger text-center mt-4"><i class="fas fa-exclamation-triangle me-2"></i> Failed to generate welcome letter.</div>'
                        );
                }
            });
        });
    </script>
@endpush
