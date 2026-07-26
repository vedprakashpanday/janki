@extends('layout.app') 

@section('content')
<style>
    /* Security for ID Card Modal */
    .noselect {
        user-select: none;
        -webkit-user-select: none;
        -webkit-touch-callout: none;
    }
    
    @media print {
        #idCardModal { display: none !important; }
        body { display: none !important; }
    }

    /* Initials Avatar Styling */
    .avatar-initials {
        width: 100px;
        height: 100px;
        background-color: #1A365D;
        color: #D69E2E;
        font-size: 36px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin: 0 auto;
    }

    .profile-img {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #D69E2E;
    }
</style>

<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body text-center">
                    <!-- Profile Image / Initials Area -->
                    <div id="avatarContainer" class="position-relative d-inline-block mb-3">
                        <img src="" id="userProfileImage" class="profile-img d-none" alt="Profile">
                        <div id="userInitials" class="avatar-initials d-none"></div>
                        
                        <!-- Upload Button -->
                        <label for="profileUpload" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2" style="cursor:pointer; font-size:12px;">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="profileUpload" class="d-none" accept="image/*">
                    </div>

                    <h4 class="fw-bold mb-1" id="displayMemberName">Loading...</h4>
                    <p class="text-muted mb-3" id="displayDesignation">Loading...</p>

                    <!-- ID & Visiting Card Buttons -->
                    <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="openIdCardModal()">
                            <i class="fas fa-id-badge"></i> View ID Card
                        </button>
                        <a href="#" id="downloadVisitingCardBtn" class="btn btn-outline-danger btn-sm rounded-pill px-3" target="_blank">
                            <i class="fas fa-print"></i> Visiting Card
                        </a>
                    </div>
                </div>
            </div>

            <!-- Update Details Form -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-bold"><i class="fas fa-user-edit text-warning"></i> Update Details</div>
                <div class="card-body">
                    <form id="profileUpdateForm">
                        <div class="mb-3">
                            <label class="form-label small">Mobile Number</label>
                            <input type="text" name="mobile" id="mobileInput" class="form-control form-control-sm">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Address</label>
                            <textarea name="address" id="addressInput" class="form-control form-control-sm"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm w-100">Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Change Password Form -->
            <div class="card shadow-sm border-0 mb-5">
                <div class="card-header bg-white fw-bold"><i class="fas fa-lock text-danger"></i> Change Password</div>
                <div class="card-body">
                    <form id="passwordUpdateForm">
                        <div class="mb-3">
                            <label class="form-label small">Current Password</label>
                            <input type="password" name="current_password" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">New Password</label>
                            <input type="password" name="new_password" class="form-control form-control-sm" required>
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm w-100">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Secured ID Card Modal -->
<div class="modal fade noselect" id="idCardModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white p-2">
                <h6 class="modal-title m-0"><i class="fas fa-shield-alt text-warning"></i> Secured ID Card</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 d-flex justify-content-center bg-light" id="idCardRenderArea" style="min-height: 400px; overflow:hidden;">
                <!-- Frame me ID Card load hoga -->
             <!-- Is line se 'pointer-events: none;' hata diya gaya hai taaki scroll kaam kare -->
<iframe id="idCardFrame" src="" style="width: 100%; height: 500px; border: none;"></iframe>
            </div>
            <div class="modal-footer p-1 bg-light justify-content-center">
                <span class="text-danger small fw-bold"><i class="fas fa-ban"></i> Screenshots & Printing Disabled</span>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
   // 1. Fetch User Data
function loadUserData() {
    let currentPath = window.location.pathname;
    let currentPortal = currentPath.split('/')[1] || 'admin'; // 'employee', 'member', ya 'admin' nikalega
    
    let profileApiUrl = '/api/v1/admin/auth/me'; 
    if (currentPortal === 'employee') {
        profileApiUrl = '/api/v1/employee/auth/me';
    } else if (currentPortal === 'member') {
        profileApiUrl = '/api/v1/member/auth/me';
    }

    $.ajax({
        url: profileApiUrl,
        type: 'GET',
      success: function(res) {
            if(res && res.data) {
                let u = res.data;
                
                // 🔥 BULLETPROOF DATA EXTRACTION 🔥
                // Ye check karega ki data 'data' block ke andar hai (u) ya bahar (res), 
                // aur purane keys ka bhi dhyan rakhega.
                
                let fullName = u.name || u.member_name || u.full_name || 'User';
                let mobile = u.profile_mobile || res.profile_mobile || u.mobile || u.contact_no || '';
                let address = u.profile_address || res.profile_address || u.address || u.communication_address || '';
                let photo = u.profile_photo || res.profile_photo || u.passport_photo || '';
                let idString = u.profile_id_string || res.profile_id_string || u.member_id || '';
                let designation = u.designation_name || u.designation || u.profile_designation || 'Employee Access';
$('#displayDesignation').text(designation);

                // Data display karna
                $('#displayMemberName').text(fullName);
                $('#displayDesignation').text(designation);
                $('#mobileInput').val(mobile);
                $('#addressInput').val(address);
                
                // Dynamic URLs for Buttons
                let currentPortal = window.location.pathname.split('/')[1] || 'admin';
                $('#downloadVisitingCardBtn').attr('href', `/${currentPortal}/id-cards/print/visiting_normal?member_id=${idString}`);
                $('#idCardFrame').attr('src', `/${currentPortal}/id-cards/print/id_card?member_id=${idString}`);

                // Profile Image / Initials Logic
                if(photo) {
                    $('#userProfileImage').attr('src', photo).removeClass('d-none');
                    $('#userInitials').addClass('d-none');
                } else {
                    let initials = fullName.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                    $('#userInitials').text(initials).removeClass('d-none');
                    $('#userProfileImage').addClass('d-none');
                }
            }
        },
        error: function(err) {
            console.error("Profile Fetch Error:", err);
            $('#displayMemberName').text('Error Loading Data');
            $('#displayDesignation').text('-');
        }
    });
}
    loadUserData();

    // 2. Profile Image Upload Handler
    $('#profileUpload').change(function() {
        let formData = new FormData();
        formData.append('profile_image', this.files[0]);
        
        Swal.fire({ title: 'Uploading...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
        
        $.ajax({
            url: '/api/v1/profile/update', // Update API path accordingly
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                Swal.fire('Success', 'Profile image updated!', 'success');
                loadUserData(); // Reload avatar
            }
        });
    });

    // 3. Security: Prevent PrintScreen & Inspect Element while Modal is open
    window.openIdCardModal = function() {
        $('#idCardModal').modal('show');
    };

    $('#idCardModal').on('show.bs.modal', function () {
        document.addEventListener("contextmenu", preventContextMenu);
        document.addEventListener("keyup", preventPrintScreen);
        document.addEventListener("keydown", preventPrintScreen);
    });

    $('#idCardModal').on('hidden.bs.modal', function () {
        document.removeEventListener("contextmenu", preventContextMenu);
        document.removeEventListener("keyup", preventPrintScreen);
        document.removeEventListener("keydown", preventPrintScreen);
    });

    function preventContextMenu(e) { e.preventDefault(); }
    
    function preventPrintScreen(e) {
        if (e.key === 'PrintScreen' || (e.ctrlKey && e.key === 'p') || (e.ctrlKey && e.shiftKey && e.key === 'I')) {
            e.preventDefault();
            navigator.clipboard.writeText(''); // Clear clipboard
            $('#idCardModal').modal('hide');
            Swal.fire('Security Alert', 'Taking screenshots or printing is restricted.', 'warning');
        }
    }
});
</script>
@endpush