@extends('layout.app')

@section('content')
    <style>
        .welcome-letter-wrapper {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 5px 0;
            box-sizing: border-box;
        }

        .letter-container {
            background: #ffffff;
            padding: 45px 55px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid #E2E8F0;
            border-top: 6px solid var(--brand-primary);
            border-radius: 8px;
            font-family: 'Georgia', serif;
            color: #2D3748;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .letter-container::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 450px;
            height: 450px;
            background-image: var(--dynamic-watermark, none);
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0.15;
            z-index: 0;
            pointer-events: none;
        }

        .letter-content {
            position: relative;
            z-index: 1;
        }

        .letter-title {
            color: var(--sidebar-bg);
            font-weight: 700;
            letter-spacing: 2px;
            border-bottom: 2px solid var(--brand-primary);
            display: inline-block;
            padding-bottom: 6px;
            text-align: center;
            
           
        }

        .details-box {
            background-color: #F8FAFC;
            border-left: 4px solid var(--sidebar-bg);
            border-radius: 0 6px 6px 0;
        }

        .letter-body-text p {
            line-height: 1.8;
            font-size: 15px;
            margin-bottom: 18px;
            text-align: justify;
        }

        .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        @media print {

            html,
            body,
            #letter-content-box,
            .letter-container,
            .welcome-letter-wrapper {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
            }
        }

        /* 🔥 MOBILE SPECIFIC OVERRIDES 🔥 */
        @media (max-width: 767.98px) {
            .letter-container {
                padding: 20px 15px;
                /* Mobile par padding aur kam ki */
            }

            .letter-body-text p {
                font-size: 13.5px;
                /* Body text lightly smaller */
                line-height: 1.6;
            }

            /* Staff Welcome Letter font chhota kiya */
            .letter-title {
                font-size: 17px !important;
                /* Force to fit in one line */
                letter-spacing: 1px;
                margin-top: 10px;
            }

            .details-box {
                padding: 15px !important;
            }
        }
    </style>

    <div class="welcome-letter-wrapper no-select">
        <div class="mb-3 d-none d-md-block">
            <h5 class="fw-bold" style="color: var(--sidebar-bg);">
                <i class="fas fa-file-signature text-warning me-2"></i> Official Engagement Document
            </h5>
        </div>

        <div class="letter-container">
            <div class="letter-content" id="letter-content-box">
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#letter-content-box').html(
                '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-warning"></i><p class="mt-2 text-muted fw-bold">Loading secure document...</p></div>'
                );

            $.ajax({
                url: '/api/v1/employee/welcome-letter',
                type: 'GET',
                success: function(res) {
                    if (res.success) {
                        $('#letter-content-box').html(res.data);
                        if (res.logo) {
                            $('.letter-container').get(0).style.setProperty('--dynamic-watermark',
                                'url(' + res.logo + ')');
                        }
                    }
                },
                error: function(err) {
                    $('#letter-content-box').html(
                        '<div class="alert alert-danger text-center fw-bold m-4">Unauthorized or Document Session Expired.</div>'
                        );
                }
            });

            // 🔒 SECURITY JAVASCRIPT
            document.addEventListener('contextmenu', e => e.preventDefault());
            document.addEventListener('keydown', e => {
                if ((e.ctrlKey && e.key === 'p') || (e.ctrlKey && e.key === 's') || ((e.ctrlKey && e
                        .shiftKey && e.key === 'I') || e.key === 'F12')) {
                    e.preventDefault();
                    return false;
                }
            });

            window.addEventListener('blur', () => $('.letter-container').css('filter', 'blur(10px)'));
            window.addEventListener('focus', () => $('.letter-container').css('filter', 'none'));
        });
    </script>
@endpush
