<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Workspace | Portal</title>

    <link rel="shortcut icon" href="{{ asset('uploads/harihomes1-fevicon.png') }}" type="image/x-icon" id="dynamicFavicon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --sidebar-bg: #1A365D;
            --sidebar-hover: #2A4365;
            --brand-primary: #D69E2E;
            --bg-light: #F7FAFC;
            --border-color: #E2E8F0;
            --text-main: #2D3748;
            --shadow-color: rgba(26, 54, 93, 0.08);
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
        }

        /* HIDDEN BY DEFAULT TO PREVENT UI FLICKER */
        .secured-item {
            display: none;
        }

        @media (min-width: 768px) {
            .app-sidebar {
                display: none !important;
            }

            .app-header-wrapper {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1020;
                box-shadow: 0 4px 12px var(--shadow-color);
            }

            .header-top {
                height: 70px;
                background: #ffffff;
                display: flex;
                align-items: center;
                padding: 0 30px;
                justify-content: space-between;
                border-bottom: 1px solid var(--border-color);
            }

            .header-bottom {
                background-color: var(--sidebar-bg);
                min-height: 50px;
                display: flex;
                align-items: center;
                padding: 0 30px;
            }

            .app-main {
                margin-left: 0;
                padding: 30px;
                padding-top: 150px;
                min-height: 100vh;
            }

            .mobile-bottom-nav,
            .mobile-header-brand {
                display: none !important;
            }

            .desktop-nav {
                list-style: none;
                margin: 0;
                padding: 0;
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }

            .desktop-nav>li {
                position: relative;
            }

            .desktop-nav>li>a {
                color: #E2E8F0;
                text-decoration: none;
                font-size: 13.5px;
                font-weight: 500;
                padding: 14px 16px;
                display: block;
                border-radius: 4px;
                transition: 0.2s;
            }

            .desktop-nav>li>a:hover,
            .desktop-nav>li>a.active {
                color: #ffffff;
                background-color: rgba(255, 255, 255, 0.1);
            }

            .desktop-nav>li>a i {
                margin-right: 6px;
                color: var(--brand-primary);
            }

            .desktop-dropdown {
                position: absolute;
                top: 100%;
                left: 0;
                background: #ffffff;
                min-width: 240px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                border-radius: 6px;
                border: 1px solid var(--border-color);
                opacity: 0;
                visibility: hidden;
                transform: translateY(10px);
                transition: all 0.2s ease;
                display: flex;
                flex-direction: column;
                padding: 8px 0;
                z-index: 1050;
            }

            .desktop-nav>li:hover .desktop-dropdown {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }

            .desktop-dropdown a {
                color: var(--text-main);
                text-decoration: none;
                padding: 10px 20px;
                font-size: 13px;
                font-weight: 500;
                transition: 0.2s;
                display: flex;
                align-items: center;
            }

            .desktop-dropdown a i {
                width: 20px;
                color: #718096;
                margin-right: 8px;
            }

            .desktop-dropdown a:hover {
                background-color: var(--bg-light);
                color: var(--brand-primary);
            }
        }

        @media (max-width: 767.98px) {
            .app-header-wrapper {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1010;
            }

            .header-top {
                height: 60px;
                background: #ffffff;
                border-bottom: 1px solid var(--border-color);
                display: flex;
                align-items: center;
                padding: 0 15px;
                justify-content: space-between;
                box-shadow: 0 2px 10px var(--shadow-color);
            }

            .header-bottom {
                display: none !important;
            }

            .app-main {
                padding: 15px;
                padding-top: 80px;
                padding-bottom: 90px;
                min-height: 100vh;
            }

            .mobile-bottom-nav {
                display: flex;
                justify-content: space-between;
                align-items: center;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: 65px;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(10px);
                border-top: 1px solid var(--border-color);
                z-index: 1030;
                padding: 0 10px;
                padding-bottom: env(safe-area-inset-bottom);
                box-shadow: 0 -2px 10px var(--shadow-color);
            }

            .mobile-bottom-nav a {
                text-align: center;
                color: #718096;
                text-decoration: none;
                font-size: 11px;
                flex: 1;
                padding: 10px 0;
                font-weight: 500;
                transition: color 0.2s;
            }

            .mobile-bottom-nav a i {
                display: block;
                font-size: 20px;
                margin-bottom: 3px;
            }

            .mobile-bottom-nav a.active {
                color: var(--brand-primary);
            }
        }

        .nav-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #A0AEC0;
            letter-spacing: 1.5px;
            margin: 25px 24px 10px;
            font-weight: 600;
        }

        .nav-item-custom {
            color: #E2E8F0;
            padding: 12px 24px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 14px;
            font-weight: 500;
            transition: 0.2s;
            cursor: pointer;
        }

        .nav-item-custom .menu-icon {
            width: 24px;
            font-size: 16px;
            color: #A0AEC0;
            transition: 0.2s;
        }

        .nav-item-custom:hover,
        .nav-item-custom.active,
        .nav-item-custom[aria-expanded="true"] {
            background: var(--sidebar-hover);
            color: white;
            border-right: 3px solid var(--brand-primary);
        }

        .nav-item-custom .dropdown-caret {
            font-size: 10px;
            transition: transform 0.3s;
        }

        .nav-item-custom[aria-expanded="true"] .dropdown-caret {
            transform: rotate(180deg);
        }

        .nav-sub-menu {
            background-color: rgba(255, 255, 255, 0.03);
        }

        .nav-item-sub {
            padding: 10px 24px 10px 48px;
            color: #CBD5E1;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 13px;
            transition: 0.2s;
        }

        .nav-item-sub:hover,
        .nav-item-sub.active {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
        }

        .sidebar-user-card {
            margin-top: auto;
            padding: 20px 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.02);
        }

        .offcanvas-dark-custom {
            background-color: var(--sidebar-bg) !important;
            border-right: none;
        }
    </style>
</head>

<body>
    @php
        // Fetch active modules database structure (Will automatically hide/show based on JS Permissions)
        $navModules = \App\Models\Module::whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('sequence', 'asc')
            ->with([
                'children' => function ($q) {
                    $q->where('status', 'active')->orderBy('sequence', 'asc');
                },
            ])
            ->get();
    @endphp

    <header class="app-header-wrapper">
        <div class="header-top">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ asset('uploads/harihomes1-logo.png') }}" alt="Workspace Logo" height="35"
                    class="brand-logo-img">
            </div>

            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light rounded-circle border-0 text-secondary shadow-sm"><i
                        class="fas fa-bell"></i></button>
                <div class="dropdown">
                    <a href="#" class="text-decoration-none d-flex align-items-center gap-2"
                        data-bs-toggle="dropdown" style="color: var(--text-main);">
                        <img src="https://ui-avatars.com/api/?name=User&background=1A365D&color=fff" alt="User"
                            class="rounded-circle user-avatar-img" width="35" height="35">
                        <span class="d-none d-md-block fw-medium fs-6 user-name-display">Loading...</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3">
                        <li><a class="dropdown-item py-2 fw-medium handle-logout" href="#"
                                style="color: #E53E3E;"><i class="fas fa-sign-out-alt me-2"></i> Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="header-bottom">
            <ul class="desktop-nav">
                @foreach ($navModules as $parent)
                    @if ($parent->children->isEmpty())
                        <li class="secured-item"
                            data-permission="{{ $parent->permission_base ? $parent->permission_base . '_view' : 'public' }}">
                            <a href="{{ url($parent->route ?? '#') }}"
                                class="{{ request()->is(trim($parent->route, '/') . '*') ? 'active' : '' }}">
                                <i class="{{ $parent->icon }}"></i> {{ $parent->module_name }}
                            </a>
                        </li>
                    @else
                        <li class="secured-item parent-item" data-parent-id="{{ $parent->id }}">
                            <a href="#"><i class="{{ $parent->icon }}"></i> {{ $parent->module_name }} <i
                                    class="fas fa-chevron-down ms-1" style="font-size: 10px;"></i></a>
                            <div class="desktop-dropdown">
                                @foreach ($parent->children as $child)
                                    <a href="{{ url($child->route ?? '#') }}"
                                        class="secured-item child-item {{ request()->is(trim($child->route, '/') . '*') ? 'active' : '' }}"
                                        data-parent-id="{{ $parent->id }}"
                                        data-permission="{{ $child->permission_base ? $child->permission_base . '_view' : 'public' }}">
                                        <i class="{{ $child->icon }}"></i> {{ $child->module_name }}
                                    </a>
                                @endforeach
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </header>

    <div class="offcanvas offcanvas-end offcanvas-dark-custom text-white" tabindex="-1" id="mobileSidebar">
        <div class="offcanvas-header border-bottom bg-white">
            <img src="{{ asset('uploads/harihomes1-logo.png') }}" alt="Workspace Logo" height="35"
                class="brand-logo-img">
            <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"
                aria-label="Close"></button>
        </div>

        <div class="offcanvas-body p-0 d-flex flex-column">
            <div class="flex-grow-1 overflow-auto py-2">
                <div class="nav-label">Main Menu</div>

                @foreach ($navModules as $parent)
                    @if ($parent->children->isEmpty())
                        <a href="{{ url($parent->route ?? '#') }}"
                            class="nav-item-custom secured-item {{ request()->is(trim($parent->route, '/') . '*') ? 'active' : '' }}"
                            data-permission="{{ $parent->permission_base ? $parent->permission_base . '_view' : 'public' }}">
                            <div><i class="{{ $parent->icon }} menu-icon"></i> {{ $parent->module_name }}</div>
                        </a>
                    @else
                        <a href="#mobileMenu_{{ $parent->id }}" data-bs-toggle="collapse"
                            class="nav-item-custom secured-item parent-item" aria-expanded="false"
                            data-parent-id="{{ $parent->id }}">
                            <div><i class="{{ $parent->icon }} menu-icon"></i> {{ $parent->module_name }}</div>
                            <i class="fas fa-chevron-down dropdown-caret"></i>
                        </a>
                        <div class="collapse nav-sub-menu secured-item parent-item" id="mobileMenu_{{ $parent->id }}"
                            data-parent-id="{{ $parent->id }}">
                            @foreach ($parent->children as $child)
                                <a href="{{ url($child->route ?? '#') }}"
                                    class="nav-item-sub secured-item child-item {{ request()->is(trim($child->route, '/') . '*') ? 'active' : '' }}"
                                    data-parent-id="{{ $parent->id }}"
                                    data-permission="{{ $child->permission_base ? $child->permission_base . '_view' : 'public' }}">
                                    <i class="{{ $child->icon }} me-2"></i> {{ $child->module_name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="sidebar-user-card mt-auto">
                <div class="d-flex align-items-center mb-3">
                    <img src="https://ui-avatars.com/api/?name=User&background=D69E2E&color=fff"
                        class="rounded-circle me-3 user-avatar-img" width="40" height="40">
                    <div>
                        <div class="fw-bold fs-6 text-white user-name-display">Loading...</div>
                        <div class="small user-role-display" style="color: #A0AEC0;">Authenticating...</div>
                    </div>
                </div>
                <button class="btn w-100 handle-logout fw-medium"
                    style="background-color: #E53E3E; color: white; border: none;">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </button>
            </div>
        </div>
    </div>

    <main class="app-main">
        @yield('content')
    </main>

    <nav class="mobile-bottom-nav shadow-sm">
        <a href="#" onclick="window.location.href = '/' + currentPortal + '/dashboard'" class="active"><i
                class="fas fa-layer-group"></i>Home</a>
        <a href="#" class="secured-item" data-permission="voucher_view"><i
                class="fas fa-file-invoice-dollar"></i>Vouchers</a>
        <a href="#" class="secured-item" data-permission="employee_view"><i
                class="fas fa-user-tie"></i>Staff</a>
        <a href="#" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar"><i
                class="fas fa-bars"></i>Menu</a>
    </nav>

    <div class="modal fade" id="deviceManagerModal" tabindex="-1" data-bs-backdrop="static"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

 <script>
        (function() {
            // MULTI-PORTAL DYNAMIC CONTEXT RESOLVER
            let currentPath = window.location.pathname;
            let currentPortal = 'admin'; // Default
            let tokenKey = 'admin_token';
            let loginUrl = '/admin/login';

            let authApiUrl = '/api/v1/admin/auth/me';
            let logoutApiUrl = '/api/v1/admin/auth/logout-current';

            // Detect Context based on URL and assign proper endpoints
            if (currentPath.startsWith('/employee')) {
                currentPortal = 'employee';
                tokenKey = 'emp_token';
                loginUrl = '/employee/login';
                authApiUrl = '/api/v1/employee/auth/me';
                logoutApiUrl = '/api/v1/employee/auth/logout-current';
            } else if (currentPath.startsWith('/customer')) {
                currentPortal = 'customer';
                tokenKey = 'customer_token';
                loginUrl = '/customer/login';
                authApiUrl = '/api/v1/customer/auth/me';
                logoutApiUrl = '/api/v1/customer/auth/logout-current';
            }

            const layoutToken = localStorage.getItem(tokenKey);

            // ========================================================
            // 🛡️ 1. GLOBAL AJAX & ZERO-TRUST SECURITY SETUP
            // ========================================================
            if (layoutToken) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Authorization': 'Bearer ' + layoutToken
                    }
                });
            }

            // Global Error Handler: Agar koi bhi API 401 (Unauthorized) ya 403 (Forbidden) return karti hai
            $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
                if (jqxhr.status === 401 || jqxhr.status === 403) {
                    // Ignore auth/login endpoints to prevent infinite loops
                    if(settings.url.indexOf('/auth/login') === -1 && settings.url.indexOf('/verify-id') === -1) {
                        console.error("Security Breach or Token Expired! Logging out...");
                        localStorage.removeItem(tokenKey);
                        window.location.href = loginUrl;
                    }
                }
            });

            $(document).ready(function() {
                // Kick out if no token matches the portal
                if (!layoutToken) {
                    window.location.href = loginUrl;
                    return;
                }

                // ========================================================
                // 🛡️ 2. FETCH USER AUTHORITY & ROLE
                // ========================================================
                $.ajax({
                    url: authApiUrl,
                    type: 'GET',
                    success: function(res) {
                        let u = res.data;
                        
                        // 🔥 NAYA: Master Developers (God Mode) Array Update Kiya Hai
                        let developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
                        let isGodMode = developerEmails.includes(u.email);

                        // Safely handle permissions array
                        let perms = Array.isArray(u.permissions) ? u.permissions : [];
                        if (u.permissions && !Array.isArray(u.permissions)) {
                            perms = Object.values(u.permissions).map(p => p.name || p);
                        }

                        // Dynamic Identity Map
                        let displayName = u.name || u.full_name || u.employee_name || 'User';
                        $('.user-name-display').text(displayName);
                        $('.user-avatar-img').attr('src', `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=1A365D&color=fff`);

                        let roleDisplay = isGodMode ? 'Master Access' : (u.designation_name || currentPortal.toUpperCase());
                        $('.user-role-display').text(roleDisplay);

                        // DYNAMIC COMPANY LOGO FIX
                        if (u.company_logo) {
                            $('.brand-logo-img').attr('src', u.company_logo);
                            $('#dynamicFavicon').attr('href', u.company_logo);
                        }

                        
                        // ========================================================
// 🛡️ 3. ZERO-TRUST DYNAMIC MENU & BUTTON RENDERER
// ========================================================
// Global variables set kar rahe hain
window.userGodMode = isGodMode;
window.userPerms = perms;

window.applyPermissions = function() {
    $('.secured-item').each(function() {
        let reqPerm = $(this).data('permission');

        if (reqPerm === 'public' || window.userGodMode || window.userPerms.includes(reqPerm)) {
            $(this).show(); // Button/Menu dikhao
            if ($(this).hasClass('child-item')) {
                let parentId = $(this).data('parent-id');
                $('.parent-item[data-parent-id="' + parentId + '"]').show();
            }
        } else {
            $(this).remove(); // Jiske paas power nahi, uska button uda do
        }
    });
};

// Pehli baar menu ke liye run karo
window.applyPermissions(); 

// 🔥 MASTER STROKE: Jab bhi koi Datatable naya data load karega, ye automatically buttons ko show/hide karega!
$(document).on('draw.dt', function() {
    if(typeof window.applyPermissions === 'function') {
        window.applyPermissions();
    }
});

                        // ECHO BROADCAST (Auto Logout)
                        if (typeof window.Echo !== 'undefined') {
                            const currentTokenId = layoutToken.split('|')[0];
                            window.Echo.channel(`${currentPortal}.logout.${u.id}`)
                                .listen('.user.logged.out', (e) => {
                                    if (e.tokenId === null || e.tokenId == currentTokenId) {
                                        localStorage.removeItem(tokenKey);
                                        window.location.href = loginUrl;
                                    }
                                });
                        }
                    },
                    // Global error handler will catch failures automatically now!
                });

                // Universal Logout Flow
                $('.handle-logout').on('click', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: logoutApiUrl,
                        type: 'POST',
                        success: function() {
                            localStorage.removeItem(tokenKey);
                            window.location.href = loginUrl;
                        },
                        error: function() {
                            localStorage.removeItem(tokenKey);
                            window.location.href = loginUrl;
                        }
                    });
                });
            });
        })();
    </script>
    @stack('scripts')
</body>

</html>
