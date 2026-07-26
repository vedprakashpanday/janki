@extends('layout.app') <!-- Agar tumhara main layout ka naam kuch aur hai to usko change kar lena -->

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0 text-primary">
                <i class="fas fa-sitemap me-2"></i> My Team Downline
            </h5>
        </div>
        <div class="card-body">
            <div class="tree-container">
                <ul id="root-tree" class="downline-tree">
                    <!-- Data AJAX ke through yahan load hoga -->
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
    /* Basic Tree Structure Styling */
    .downline-tree {
        list-style-type: none;
        padding-left: 20px;
    }
    .downline-tree li {
        margin: 8px 0;
        position: relative;
    }
    .tree-node {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 8px 15px;
        border-radius: 6px;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        transition: background-color 0.2s;
        min-width: 250px;
    }
    .tree-node:hover {
        background-color: #e2e8f0;
    }
    .toggle-btn {
        cursor: pointer;
        width: 22px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 11px;
        color: #495057;
        transition: 0.2s;
    }
    .toggle-btn:hover {
        background-color: #d69e2e; /* Tumhara brand primary color */
        color: white;
        border-color: #d69e2e;
    }
    .empty-btn-space {
        width: 22px; /* Alignment sahi rakhne ke liye agar child na ho */
    }
    .member-info {
        font-weight: 600;
        font-size: 14px;
    }
    .member-id {
        font-size: 12px;
        color: #6c757d;
        margin-left: 5px;
    }
</style>

<script>
    $(document).ready(function() {
        // 1. Page load hote hi root member(s) ko fetch karo
        loadDownline('', $('#root-tree'));

        // 2. Click event delegate for + and - buttons
        $(document).on('click', '.toggle-btn', function() {
            let btn = $(this);
            let parentLi = btn.closest('li');
            let memberId = btn.data('id');
            let childContainer = parentLi.find('> .child-tree'); // Direct ul child dhoondhna

            if (btn.hasClass('fa-plus')) {
                // Expand kar rahe hain
                btn.removeClass('fa-plus').addClass('fa-minus');
                
                // Agar pehle se load nahi hua hai, to naya <ul> create karke load karo
                if (childContainer.length === 0) {
                    childContainer = $('<ul class="downline-tree child-tree mt-2"></ul>');
                    parentLi.append(childContainer);
                    loadDownline(memberId, childContainer);
                } else {
                    // Agar pehle se data hai, to sirf slideDown (show) kar do
                    childContainer.slideDown(200);
                }
            } else {
                // Collapse kar rahe hain
                btn.removeClass('fa-minus').addClass('fa-plus');
                childContainer.slideUp(200); // Hide kar do (Delete nahi kar rahe, taaki dubara API call na ho)
            }
        });

        // 3. API se data laane aur UI me append karne ka function
        function loadDownline(parentId, container) {
            // Loading text/icon dikhao
            container.append('<li class="loading-indicator text-muted small"><i class="fas fa-spinner fa-spin me-1"></i> Loading...</li>');

            $.ajax({
                url: '/api/v1/members/downline/tree',
                type: 'GET',
                data: { parent_id: parentId },
                success: function(response) {
                    // Success par loading icon hatao
                    container.find('.loading-indicator').remove();
                    
                    if (response.status === 'success' && response.data.length > 0) {
                        let html = '';
                        
                        response.data.forEach(function(member) {
                            let toggleHtml = '';
                            
                            // 💡 MAGIC: withCount('children') se jo aaya tha, uska fayda
                            // Agar is member ke bhi child hain, to '+' icon dikhao
                            if (member.children_count > 0) {
                                toggleHtml = `<i class="fas fa-plus toggle-btn shadow-sm" data-id="${member.member_id}"></i>`;
                            } else {
                                toggleHtml = `<div class="empty-btn-space"></div>`; 
                            }

                            // API se jo color code class aayi thi (e.g. text-success)
                            let colorClass = member.color_class || 'text-secondary';

                            html += `
                                <li>
                                    <div class="tree-node shadow-sm">
                                        ${toggleHtml}
                                        <div class="member-info ${colorClass}">
                                            <i class="fas fa-user-tie me-1"></i> 
                                            ${member.member_name} 
                                            <span class="member-id">(${member.member_id})</span>
                                        </div>
                                    </div>
                                </li>
                            `;
                        });
                        
                        container.append(html);
                    } else {
                        // Agar downline empty hai
                        if(parentId !== '') { 
                            container.append('<li class="text-muted small ms-4">No direct team members found.</li>');
                        }
                    }
                },
                error: function() {
                    container.find('.loading-indicator').remove();
                    container.append('<li class="text-danger small ms-4">Error loading data. Please try again.</li>');
                }
            });
        }
    });
</script>
@endpush
@endsection