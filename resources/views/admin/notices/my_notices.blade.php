@extends('layout.app')

@section('content')
<style>
    /* 🔥 ANTI-PRINT & SECURITY CSS 🔥 */
    @media print {
        .secure-notice-modal { display: none !important; visibility: hidden !important; }
    }
    .secure-notice-modal {
        user-select: none !important;
        -webkit-user-select: none !important;
        -moz-user-select: none !important;
        -ms-user-select: none !important;
    }

    /* Notice Card Styling */
    .notice-card {
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
        border-left: 4px solid var(--brand-primary);
        background: #fff;
        border-radius: 8px;
    }
    .notice-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
    }
    
    .reply-section {
        background-color: #F8FAFC;
        border-top: 2px dashed #CBD5E1;
    }
</style>

<div class="container-fluid mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0" style="color: var(--sidebar-bg);">
            <i class="fas fa-bell text-danger me-2"></i> My Notice Board
        </h5>
    </div>

    <div class="row g-3" id="noticesList">
        <div class="col-12 text-center p-5" id="loadingNotices">
            <i class="fas fa-spinner fa-spin fa-3x text-warning mb-3"></i>
            <p class="mt-2 text-muted fw-bold fs-5">Loading your notices...</p>
        </div>
    </div>
</div>

<div class="modal fade" id="readNoticeModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow border-0 secure-notice-modal">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-envelope-open-text text-primary me-2"></i> Official Notice</h5>
                <span class="badge bg-danger ms-3 px-3 py-2"><i class="fas fa-shield-alt"></i> Secured Document</span>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-0 position-relative">
                <div id="noticeLoading" class="text-center p-5" style="display: none;">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                </div>
                
                <div id="noticeHtmlContent" class="p-4" style="min-height: 400px; background-color: #fff;"></div>

                <div id="replySection" class="reply-section p-4" style="display: none;">
                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-reply text-warning me-2"></i> Acknowledgment / Reply Required</h6>
                    
                    <div id="replyFormArea">
                        <input type="hidden" id="current_notice_id">
                        <textarea id="replyText" class="form-control shadow-sm mb-3" rows="3" placeholder="Type your reply or acknowledgment here..."></textarea>
                        <button id="submitReplyBtn" class="btn btn-success fw-bold px-4 shadow-sm">
                            <i class="fas fa-paper-plane me-2"></i> Submit Reply
                        </button>
                    </div>
                    
                    <div id="alreadyRepliedArea" style="display: none;">
                        <div class="alert alert-success mb-0 fw-bold shadow-sm border-0" style="background-color: #C6F6D5; color: #22543D;">
                            <i class="fas fa-check-circle me-2"></i> You have already submitted your reply to this notice.
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
    // 🔥 ANTI-SCREENSHOT & HOTKEYS SCRIPT 🔥
    document.addEventListener('contextmenu', event => {
        if(document.getElementById('readNoticeModal').classList.contains('show')) {
            event.preventDefault();
        }
    }); 
    
    document.addEventListener('keydown', function(e) {
        if(document.getElementById('readNoticeModal').classList.contains('show')) {
            if (e.key === 'PrintScreen') {
                navigator.clipboard.writeText('Screenshots are disabled for this document.');
                Swal.fire('Action Denied', 'Taking screenshots is not allowed.', 'warning');
            }
            if (e.ctrlKey && (e.key === 'p' || e.key === 'P' || e.key === 's' || e.key === 'S' || e.key === 'c' || e.key === 'C' || e.key === 'u' || e.key === 'U')) {
                e.preventDefault();
                Swal.fire('Action Denied', 'This action is disabled for security reasons.', 'warning');
            }
        }
    });

    $(document).ready(function() {
        // 1. Fetch User Notices
        fetchNotices();

        function fetchNotices() {
            $.ajax({
                url: '/api/v1/my-notices',
                type: 'GET',
                success: function(res) {
                    $('#loadingNotices').hide();
                    let html = '';
                    
                    if(res.data.length === 0) {
                        html = `<div class="col-12 text-center p-5">
                                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="100" class="mb-3 opacity-50">
                                    <h5 class="text-muted fw-bold">No new notices for you.</h5>
                                </div>`;
                    } else {
                        res.data.forEach(notice => {
                            let replyBadge = notice.requires_reply == 1 
                                ? `<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-circle"></i> Reply Required</span>` 
                                : ``;
                                
                            html += `
                            <div class="col-md-6 col-lg-4">
                                <div class="card notice-card shadow-sm h-100" onclick="openNotice(${notice.id})">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="badge bg-light text-dark border"><i class="far fa-calendar-alt"></i> ${notice.notice_date}</span>
                                            ${replyBadge}
                                        </div>
                                        <h6 class="fw-bold text-dark mt-2 mb-0" style="line-height: 1.4;">${notice.title}</h6>
                                        <div class="text-muted small mt-2"><i class="fas fa-eye text-primary"></i> Click to read full notice</div>
                                    </div>
                                </div>
                            </div>`;
                        });
                    }
                    $('#noticesList').html(html);
                },
                error: function() {
                    $('#noticesList').html('<div class="col-12 text-center text-danger p-5"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>Failed to load notices.</div>');
                }
            });
        }

        // 2. Open Notice Modal and Fetch Secure Content
        window.openNotice = function(id) {
            $('#noticeHtmlContent').html('');
            $('#replySection').hide();
            $('#noticeLoading').show();
            $('#readNoticeModal').modal('show');

            $.ajax({
                url: `/api/v1/my-notices/${id}`,
                type: 'GET',
                success: function(res) {
                    $('#noticeLoading').hide();
                    $('#noticeHtmlContent').html(res.html);

                    // Handle Reply Section Logic
                    if(res.notice.requires_reply == 1) {
                        $('#replySection').show();
                        $('#current_notice_id').val(id);
                        $('#replyText').val(''); // clear input

                        if(res.has_replied) {
                            $('#replyFormArea').hide();
                            $('#alreadyRepliedArea').show();
                        } else {
                            $('#alreadyRepliedArea').hide();
                            $('#replyFormArea').show();
                        }
                    }
                },
                error: function() {
                    $('#noticeLoading').hide();
                    $('#noticeHtmlContent').html('<div class="alert alert-danger text-center">Failed to load notice content securely.</div>');
                }
            });
        }

        // 3. Submit Reply
        $('#submitReplyBtn').click(function() {
            let btn = $(this);
            let id = $('#current_notice_id').val();
            let text = $('#replyText').val().trim();

            if(!text) {
                Swal.fire('Warning', 'Please type your reply before submitting.', 'warning');
                return;
            }

            btn.html('<i class="fas fa-spinner fa-spin"></i> Submitting...').prop('disabled', true);

            $.ajax({
                url: `/api/v1/my-notices/${id}/reply`,
                type: 'POST',
                data: { reply_text: text },
                success: function(res) {
                    Swal.fire('Success', res.message, 'success');
                    // Hide form and show success box
                    $('#replyFormArea').slideUp();
                    $('#alreadyRepliedArea').slideDown();
                    btn.html('<i class="fas fa-paper-plane me-2"></i> Submit Reply').prop('disabled', false);
                },
                error: function(err) {
                    Swal.fire('Error', 'Failed to submit reply. Try again.', 'error');
                    btn.html('<i class="fas fa-paper-plane me-2"></i> Submit Reply').prop('disabled', false);
                }
            });
        });
    });
</script>
@endpush