<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>JankiVila | Portal</title>

    <link rel="shortcut icon" href="{{ asset('uploads/harihomes1-fevicon.png') }}" type="image/x-icon" id="dynamicFavicon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/js/app.js'])
    <style>
        :root {
            --sidebar-bg: #1A365D;
            --sidebar-hover: #2A4365;
            --sidebar-active: #314E73;
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
            overflow-x: hidden;
        }

        .secured-item:not(.is-visible-node) {
            display: none !important;
        }

        @media (min-width: 768px) {
            .app-sidebar {
                display: none !important;
            }

            .app-header-wrapper {
                position: sticky;
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
                position: relative;
            }

            .app-main {
                margin-left: 0;
                padding: 30px;
                min-height: calc(100vh - 120px);
            }

            .mobile-bottom-nav,
            .mobile-header-brand {
                display: none !important;
            }

            .nav-scroll-btn {
                position: absolute;
                top: 0;
                bottom: 0;
                width: 35px;
                background-color: var(--sidebar-bg);
                border: none;
                color: rgba(255, 255, 255, 0.7);
                font-size: 14px;
                z-index: 10;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: color 0.2s, background 0.2s;
            }

            .nav-scroll-btn:hover {
                color: #ffffff;
                background-color: var(--sidebar-hover);
            }

            .nav-scroll-btn.left-btn {
                left: 0;
                box-shadow: 4px 0 8px rgba(0, 0, 0, 0.1);
            }

            .nav-scroll-btn.right-btn {
                right: 0;
                box-shadow: -4px 0 8px rgba(0, 0, 0, 0.1);
            }

            .nav-scroll-wrapper {
                width: 100%;
                padding: 0 35px;
                position: relative;
                clip-path: inset(-10px 0px -800px 0px);
            }

            .desktop-nav {
                list-style: none;
                margin: 0;
                padding: 0;
                display: flex;
                flex-wrap: nowrap;
                gap: 5px;
                width: max-content;
                transition: transform 0.3s ease-in-out;
            }

            .desktop-nav>li {
                position: relative;
                flex: 0 0 auto;
                margin-top: 5px;
                margin-bottom: 5px;
            }

            .desktop-nav>li>a {
                color: #E2E8F0;
                text-decoration: none;
                font-size: 13.5px;
                font-weight: 500;
                padding: 10px 16px;
                display: block;
                border-radius: 4px;
                transition: all 0.2s ease-in-out;
                white-space: nowrap;
                user-select: none;
                border: 1px solid transparent;
            }

            .desktop-nav>li>a i {
                margin-right: 6px;
                color: var(--brand-primary);
            }

            .desktop-nav>li>a:hover {
                color: #ffffff;
                background-color: var(--sidebar-hover);
            }

            .desktop-nav>li>a.active {
                color: #ffffff;
                background-color: var(--sidebar-active);
                border-color: rgba(255, 255, 255, 0.1);
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
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
                display: block;
                list-style: none;
                padding: 8px 0;
                margin: 0;
                z-index: 1050;
            }

            .desktop-nav li.has-sub {
                position: relative;
            }

            .desktop-dropdown li {
                position: relative;
            }

            .desktop-nav li:hover>.desktop-dropdown {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }

            .desktop-dropdown li.has-sub>.desktop-dropdown {
                top: 0;
                left: 100%;
                margin-top: -8px;
                margin-left: 1px;
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
                white-space: nowrap;
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
        $allModules = \App\Models\Module::where('status', 'active')->orderBy('sequence', 'asc')->get();
        $rootModules = $allModules->whereNull('parent_id');

        $currentPortalPrefix = 'admin';
        if (request()->is('employee/*')) {
            $currentPortalPrefix = 'employee';
        } elseif (request()->is('customer/*')) {
            $currentPortalPrefix = 'customer';
        }

        if (!function_exists('buildDesktopMenu')) {
            function buildDesktopMenu($modules, $allModules, $portalPrefix, $isRoot = true)
            {
                $html = '';
                foreach ($modules as $mod) {
                    $children = $allModules->where('parent_id', $mod->id);
                    $hasChildren = $children->isNotEmpty();

                    $perm = $mod->permission_base ? $mod->permission_base . '_view' : 'node_parent';
                    $parentIdAttr = $mod->parent_id ? 'data-parent-id="' . $mod->parent_id . '"' : '';

                    $rawRoute = trim($mod->route, '/');
                    if ($rawRoute && $rawRoute !== '#') {
                        $cleanRoute = preg_replace('/^(admin|employee|customer)\//', '', $rawRoute);
                        $url = url($portalPrefix . '/' . $cleanRoute);
                        $active = request()->is($portalPrefix . '/' . $cleanRoute . '*') ? 'active' : '';
                    } else {
                        $url = '#';
                        $active = '';
                    }

                    $icon = $mod->icon ? '<i class="' . $mod->icon . '"></i>' : '';

                    if ($isRoot) {
                        if (!$hasChildren) {
                            $html .=
                                '<li class="secured-item" data-id="' .
                                $mod->id .
                                '" ' .
                                $parentIdAttr .
                                ' data-permission="' .
                                $perm .
                                '"><a href="' .
                                $url .
                                '" class="' .
                                $active .
                                '">' .
                                $icon .
                                ' ' .
                                $mod->module_name .
                                '</a></li>';
                        } else {
                            $html .=
                                '<li class="secured-item parent-item has-sub" data-id="' .
                                $mod->id .
                                '" ' .
                                $parentIdAttr .
                                ' data-permission="' .
                                $perm .
                                '"><a href="#" class="' .
                                $active .
                                '">' .
                                $icon .
                                ' ' .
                                $mod->module_name .
                                ' <i class="fas fa-chevron-down ms-1" style="font-size: 10px;"></i></a>';
                            $html .= '<ul class="desktop-dropdown">';
                            $html .= buildDesktopMenu($children, $allModules, $portalPrefix, false);
                            $html .= '</ul></li>';
                        }
                    } else {
                        if (!$hasChildren) {
                            $html .=
                                '<li class="secured-item child-item" data-id="' .
                                $mod->id .
                                '" ' .
                                $parentIdAttr .
                                ' data-permission="' .
                                $perm .
                                '"><a href="' .
                                $url .
                                '" class="' .
                                $active .
                                '">' .
                                $icon .
                                ' ' .
                                $mod->module_name .
                                '</a></li>';
                        } else {
                            $html .=
                                '<li class="secured-item parent-item has-sub" data-id="' .
                                $mod->id .
                                '" ' .
                                $parentIdAttr .
                                ' data-permission="' .
                                $perm .
                                '"><a href="#" class="d-flex justify-content-between align-items-center"><span>' .
                                $icon .
                                ' ' .
                                $mod->module_name .
                                '</span> <i class="fas fa-chevron-right" style="font-size: 10px;"></i></a>';
                            $html .= '<ul class="desktop-dropdown">';
                            $html .= buildDesktopMenu($children, $allModules, $portalPrefix, false);
                            $html .= '</ul></li>';
                        }
                    }
                }
                return $html;
            }
        }

        if (!function_exists('buildMobileMenu')) {
            function buildMobileMenu($modules, $allModules, $portalPrefix, $isRoot = true, $depth = 1)
            {
                $html = '';
                $padding = 24 + $depth * 20;

                foreach ($modules as $mod) {
                    $children = $allModules->where('parent_id', $mod->id);
                    $hasChildren = $children->isNotEmpty();

                    $perm = $mod->permission_base ? $mod->permission_base . '_view' : 'node_parent';
                    $parentIdAttr = $mod->parent_id ? 'data-parent-id="' . $mod->parent_id . '"' : '';

                    $rawRoute = trim($mod->route, '/');
                    if ($rawRoute && $rawRoute !== '#') {
                        $cleanRoute = preg_replace('/^(admin|employee|customer)\//', '', $rawRoute);
                        $url = url($portalPrefix . '/' . $cleanRoute);
                        $active = request()->is($portalPrefix . '/' . $cleanRoute . '*') ? 'active' : '';
                    } else {
                        $url = '#';
                        $active = '';
                    }

                    $icon = $mod->icon ? '<i class="' . $mod->icon . ' menu-icon"></i>' : '';

                    if ($isRoot) {
                        if (!$hasChildren) {
                            $html .=
                                '<a href="' .
                                $url .
                                '" class="nav-item-custom secured-item ' .
                                $active .
                                '" data-id="' .
                                $mod->id .
                                '" ' .
                                $parentIdAttr .
                                ' data-permission="' .
                                $perm .
                                '"><div>' .
                                $icon .
                                ' ' .
                                $mod->module_name .
                                '</div></a>';
                        } else {
                            $html .=
                                '<div class="secured-item parent-item" data-id="' .
                                $mod->id .
                                '" ' .
                                $parentIdAttr .
                                ' data-permission="' .
                                $perm .
                                '">';
                            $html .=
                                '<a href="#mobileMenu_' .
                                $mod->id .
                                '" data-bs-toggle="collapse" class="nav-item-custom" aria-expanded="false"><div>' .
                                $icon .
                                ' ' .
                                $mod->module_name .
                                '</div><i class="fas fa-chevron-down dropdown-caret"></i></a>';
                            $html .= '<div class="collapse nav-sub-menu" id="mobileMenu_' . $mod->id . '">';
                            $html .= buildMobileMenu($children, $allModules, $portalPrefix, false, 1);
                            $html .= '</div></div>';
                        }
                    } else {
                        if (!$hasChildren) {
                            $html .=
                                '<a href="' .
                                $url .
                                '" class="nav-item-sub secured-item child-item ' .
                                $active .
                                '" data-id="' .
                                $mod->id .
                                '" ' .
                                $parentIdAttr .
                                ' data-permission="' .
                                $perm .
                                '" style="padding-left: ' .
                                $padding .
                                'px;">' .
                                $icon .
                                ' ' .
                                $mod->module_name .
                                '</a>';
                        } else {
                            $html .=
                                '<div class="secured-item parent-item" data-id="' .
                                $mod->id .
                                '" ' .
                                $parentIdAttr .
                                ' data-permission="' .
                                $perm .
                                '">';
                            $html .=
                                '<a href="#mobileMenu_' .
                                $mod->id .
                                '" data-bs-toggle="collapse" class="nav-item-sub d-flex justify-content-between align-items-center" aria-expanded="false" style="padding-left: ' .
                                $padding .
                                'px;"><div>' .
                                $icon .
                                ' ' .
                                $mod->module_name .
                                '</div><i class="fas fa-chevron-down dropdown-caret" style="font-size:10px;"></i></a>';
                            $html .= '<div class="collapse nav-sub-menu" id="mobileMenu_' . $mod->id . '">';
                            $html .= buildMobileMenu($children, $allModules, $portalPrefix, false, $depth + 1);
                            $html .= '</div></div>';
                        }
                    }
                }
                return $html;
            }
        }
    @endphp

    <header class="app-header-wrapper">
        <div class="header-top">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ asset('uploads/harihomes1-logo.png') }}" alt="Workspace Logo" height="35"
                    class="brand-logo-img">
            </div>
            <div class="d-flex align-items-center gap-3">

                <button class="btn btn-light rounded-circle border-0 text-secondary shadow-sm"
                    onclick="window.location.href='{{ url($currentPortalPrefix . '/travel-allowances') }}'"
                    title="Travel Allowances">
                    <i class="fas fa-car-side text-success"></i>
                </button>

                <button class="btn btn-light rounded-circle border-0 text-secondary shadow-sm"
                    onclick="window.location.href='{{ url($currentPortalPrefix . '/terms-conditions') }}'"
                    title="Terms & Conditions">
                    <i class="fas fa-file-signature text-primary"></i>
                </button>

                <div class="dropdown" id="globalNotificationDropdown">
                    <button class="btn btn-light rounded-circle border-0 text-secondary shadow-sm position-relative"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                            id="globalUnreadCount" style="font-size: 0.65rem; padding: 0.35em 0.5em;">0</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3 p-0" id="notificationList"
                        style="width: 320px; max-height: 400px; overflow-y: auto;">
                        <li class="p-4 text-center text-muted small" id="noNotifMessage">
                            <i class="fas fa-bell-slash fs-4 mb-2 d-block text-secondary opacity-50"></i>
                            No new notifications
                        </li>
                    </ul>
                </div>

                <div class="dropdown">
                    <a href="#" class="text-decoration-none d-flex align-items-center gap-2"
                        data-bs-toggle="dropdown" style="color: var(--text-main);">
                        <img src="https://ui-avatars.com/api/?name=User&background=1A365D&color=fff" alt="User"
                            class="rounded-circle user-avatar-img" width="35" height="35">
                        <span class="d-none d-md-block fw-medium fs-6 user-name-display">Loading...</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3">
                        <li><a class="dropdown-item py-2 fw-medium"
                                href="{{ url($currentPortalPrefix . '/terms-conditions') }}"><i
                                    class="fas fa-file-contract me-2 text-primary"></i> Terms & Conditions</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li><a class="dropdown-item py-2 fw-medium handle-logout" href="#"
                                style="color: #E53E3E;"><i class="fas fa-sign-out-alt me-2"></i> Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="header-bottom" id="desktopNavContainer">
            <button class="nav-scroll-btn left-btn" id="btnScrollLeft"><i class="fas fa-chevron-left"></i></button>

            <div class="nav-scroll-wrapper" id="navScrollWrapper">
                <ul class="desktop-nav" id="desktopNavScrollArea">
                    <li class="secured-item" data-permission="public">
                        <a href="{{ url($currentPortalPrefix . '/dashboard') }}"
                            class="dynamic-dashboard-btn text-decoration-none">
                            <i class="fas fa-home text-warning"></i> Dashboard
                        </a>
                    </li>
                    <li class="secured-item" data-permission="public">
                        <a href="{{ url('employee/welcome-letter') }}">
                            <i class="fas fa-envelope-open-text text-info"></i> Welcome Letter
                        </a>
                    </li>
                    <li class="secured-item" data-permission="public">
    <a href="{{ url($currentPortalPrefix . '/leave-applications') }}" class="{{ request()->is('*/leave-applications') ? 'active' : '' }}">
        <i class="fas fa-calendar-alt text-primary"></i> Leaves & Apps
    </a>
</li>
                    {!! buildDesktopMenu($rootModules, $allModules, $currentPortalPrefix) !!}

                    <li class="secured-item" data-permission="public">
                        <a href="{{ url($currentPortalPrefix . '/my-notices') }}"
                            class="{{ request()->is('*/my-notices') ? 'active' : '' }}">
                            <i class="fas fa-bell text-danger"></i> My Notices
                        </a>
                    </li>
                </ul>
            </div>

            <button class="nav-scroll-btn right-btn" id="btnScrollRight"><i class="fas fa-chevron-right"></i></button>
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
                <a href="{{ url($currentPortalPrefix . '/dashboard') }}"
                    class="nav-item-custom secured-item dynamic-dashboard-btn border-bottom mb-2"
                    data-permission="public">
                    <div><i class="fas fa-home text-warning menu-icon"></i> Dashboard</div>
                </a>


                <a href="{{ url('employee/welcome-letter') }}"
                    class="nav-item-custom secured-item border-bottom mb-2" data-permission="public">
                    <div><i class="fas fa-envelope-open-text text-info menu-icon"></i> Welcome Letter</div>
                </a>

                <a href="{{ url($currentPortalPrefix . '/leave-applications') }}"
    class="nav-item-custom secured-item border-bottom mb-2 {{ request()->is('*/leave-applications') ? 'active' : '' }}" 
    data-permission="public">
    <div><i class="fas fa-calendar-alt text-primary menu-icon"></i> Leaves & Apps</div>
</a>

                <a href="{{ url($currentPortalPrefix . '/terms-conditions') }}"
                    class="nav-item-custom border-bottom mb-2 text-info">
                    <div><i class="fas fa-file-contract text-info menu-icon"></i> Terms & Conditions</div>
                </a>

                {!! buildMobileMenu($rootModules, $allModules, $currentPortalPrefix) !!}

                <a href="{{ url($currentPortalPrefix . '/my-notices') }}"
                    class="nav-item-custom secured-item border-bottom mb-2 {{ request()->is('*/my-notices') ? 'active' : '' }}"
                    data-permission="public">
                    <div><i class="fas fa-bell text-danger menu-icon"></i> My Notices</div>
                </a>
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
        <a href="{{ url($currentPortalPrefix . '/dashboard') }}" class="active"><i
                class="fas fa-layer-group"></i>Home</a>
        <a href="{{ url('employee/welcome-letter') }}" class="secured-item" data-permission="public"><i
                class="fas fa-envelope-open-text"></i>Letter</a>
        <a href="#" class="secured-item" data-permission="employee_view"><i
                class="fas fa-user-tie"></i>Staff</a>
        <a href="#" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar"><i
                class="fas fa-bars"></i>Menu</a>
    </nav>

    <div class="modal fade" id="deviceManagerModal" tabindex="-1" data-bs-backdrop="static"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        (function() {
            let currentPath = window.location.pathname;
            let currentPortal = 'admin';
            let tokenKey = 'admin_token';
            let loginUrl = '/admin/login';

            let authApiUrl = '/api/v1/admin/auth/me';
            let logoutApiUrl = '/api/v1/admin/auth/logout-current';

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

            const layoutToken = localStorage.getItem(tokenKey) || localStorage.getItem('token') || '';

            if (layoutToken) {
                // Set global jQuery AJAX Token
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Authorization': 'Bearer ' + layoutToken
                    }
                });

                // Set global Axios Token (if used by Echo)
                if (typeof window.axios !== 'undefined') {
                    window.axios.defaults.headers.common['Authorization'] = 'Bearer ' + layoutToken;
                }

                // 🔥 THE ULTIMATE ECHO REBOOT (Runs only ONCE per page load) 🔥
                // if (typeof window.Echo !== 'undefined') {
                //     let echoOptions = window.Echo.connector.options;

                //     // Force the token deep into Pusher's core configuration
                //     echoOptions.authEndpoint = '/broadcasting/auth?token=' + encodeURIComponent(layoutToken);
                //     echoOptions.auth = echoOptions.auth || {};
                //     echoOptions.auth.headers = echoOptions.auth.headers || {};
                //     echoOptions.auth.headers['Authorization'] = 'Bearer ' + layoutToken;
                //     echoOptions.auth.headers['Accept'] = 'application/json';

                //     // Rebuild the Echo instance completely authorized
                //     window.Echo.disconnect();
                //     window.Echo = new window.Echo.constructor(echoOptions);
                // }
            }

            $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
                if (jqxhr.status === 401) {
                    if (settings.url.indexOf('/auth/login') === -1 && settings.url.indexOf('/verify-id') === -
                        1) {
                        clearLocalDataAndRedirect();
                    }
                }
            });

            window.clearLocalDataAndRedirect = function() {
                localStorage.removeItem(tokenKey);
                if (currentPortal === 'employee') localStorage.removeItem('emp_panel_id');
                sessionStorage.removeItem('attendance_marked_today');
                window.location.href = loginUrl;
            };

            window.performNormalLogout = function() {
                let payload = {};
                if (currentPortal === 'employee') payload = {
                    panel_id: localStorage.getItem('emp_panel_id')
                };
                Swal.fire({
                    title: 'Logging out...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                $.ajax({
                    url: logoutApiUrl,
                    type: 'POST',
                    data: payload,
                    success: function() {
                        clearLocalDataAndRedirect();
                    },
                    error: function() {
                        clearLocalDataAndRedirect();
                    }
                });
            };

            $(document).ready(function() {

                const navArea = document.getElementById('desktopNavScrollArea');
                const wrapper = document.getElementById('navScrollWrapper');
                const btnLeft = document.getElementById('btnScrollLeft');
                const btnRight = document.getElementById('btnScrollRight');

                let currentNavScroll = 0;

                if (navArea && wrapper && btnLeft && btnRight) {
                    const updateScrollButtons = () => {
                        let maxScroll = navArea.scrollWidth - wrapper.clientWidth + 70;
                        if (maxScroll <= 0) {
                            btnLeft.style.display = 'none';
                            btnRight.style.display = 'none';
                            return;
                        }
                        btnLeft.style.display = currentNavScroll > 0 ? 'flex' : 'none';
                        btnRight.style.display = currentNavScroll < maxScroll ? 'flex' : 'none';
                    };

                    btnLeft.addEventListener('click', () => {
                        currentNavScroll -= 300;
                        if (currentNavScroll < 0) currentNavScroll = 0;
                        navArea.style.transform = `translateX(-${currentNavScroll}px)`;
                        updateScrollButtons();
                    });
                    btnRight.addEventListener('click', () => {
                        let maxScroll = navArea.scrollWidth - wrapper.clientWidth + 70;
                        currentNavScroll += 300;
                        if (currentNavScroll > maxScroll) currentNavScroll = maxScroll;
                        navArea.style.transform = `translateX(-${currentNavScroll}px)`;
                        updateScrollButtons();
                    });

                    wrapper.addEventListener('wheel', function(e) {
                        if (e.deltaY !== 0) {
                            e.preventDefault();
                            let maxScroll = navArea.scrollWidth - wrapper.clientWidth + 70;
                            currentNavScroll += e.deltaY > 0 ? 150 : -150;
                            if (currentNavScroll < 0) currentNavScroll = 0;
                            if (currentNavScroll > maxScroll) currentNavScroll = maxScroll;
                            navArea.style.transform = `translateX(-${currentNavScroll}px)`;
                            updateScrollButtons();
                        }
                    }, {
                        passive: false
                    });

                    window.addEventListener('resize', () => {
                        let maxScroll = navArea.scrollWidth - wrapper.clientWidth + 70;
                        if (currentNavScroll > maxScroll) currentNavScroll = Math.max(0, maxScroll);
                        navArea.style.transform = `translateX(-${currentNavScroll}px)`;
                        updateScrollButtons();
                    });

                    setTimeout(updateScrollButtons, 300);
                }

                if (typeof $.fn.dataTable !== 'undefined') {
                    $.fn.dataTable.ext.errMode = 'none';
                }
                $(document).on('error.dt', function(e, settings, techNote, message) {
                    console.warn('DataTables Blocked: Access Expired or Unauthorized');
                });

                if (!layoutToken) {
                    window.location.href = loginUrl;
                    return;
                }

                $.ajax({
                    url: authApiUrl,
                    type: 'GET',
                    success: function(res) {
                        let u = res.data;
                        window.userId = u.id;
                        let emailStr = u.email ? u.email.toLowerCase() : '';
                        let developerEmails = ['admin@jankivilla.com', 'superadmin@example.com',
                            'vedprakash@infoera.in'
                        ];

                        let isGodMode = developerEmails.includes(emailStr);
                        let isCEOorDirector = u.designation_name && (u.designation_name
                            .toLowerCase().includes('ceo') || u.designation_name.toLowerCase()
                            .includes('director'));

                        let perms = Array.isArray(u.permissions) ? u.permissions : [];
                        if (u.permissions && !Array.isArray(u.permissions)) {
                            perms = Object.values(u.permissions).map(p => p.name || p);
                        }

                        $('.user-name-display').text(u.name || u.full_name || u.employee_name ||
                            'User');
                        $('.user-role-display').text(isGodMode ? 'Master Access' : (u
                            .designation_name || currentPortal.toUpperCase()));

                        if (u.company_logo) {
                            $('.brand-logo-img').attr('src', u.company_logo);
                            $('#dynamicFavicon').attr('href', u.company_logo);
                        }

                        window.userGodMode = isGodMode;
                        window.userPerms = perms;

                        window.applyPermissions = function() {
                            $('.secured-item').each(function() {
                                let reqPerm = $(this).data('permission');
                                let isPermitted = false;
                                if (reqPerm === 'public' || window.userGodMode) {
                                    isPermitted = true;
                                } else if (reqPerm && reqPerm !== 'node_parent') {
                                    let base = reqPerm.replace('_view', '');
                                    isPermitted = window.userPerms.some(p => p ===
                                        reqPerm || p.startsWith(base + '_'));
                                }
                                if (isPermitted) {
                                    $(this).addClass('is-visible-node');
                                } else {
                                    $(this).removeClass('is-visible-node');
                                }
                            });

                            let bubbling = true;
                            while (bubbling) {
                                bubbling = false;
                                $('.secured-item.is-visible-node').each(function() {
                                    let pId = $(this).data('parent-id');
                                    if (pId) {
                                        $('.secured-item[data-id="' + pId +
                                            '"]:not(.is-visible-node)').each(
                                            function() {
                                                $(this).addClass('is-visible-node');
                                                bubbling = true;
                                            });
                                    }
                                });
                            }
                            if (typeof updateScrollButtons !== 'undefined') {
                                setTimeout(updateScrollButtons, 100);
                            }
                        };

                        window.applyPermissions();

                        let targetDashboard = '/' + currentPortal + '/dashboard';
                        $('.brand-logo-img').css('cursor', 'pointer').on('click', function(e) {
                            e.preventDefault();
                            window.location.href = targetDashboard;
                        });

                    // =========================================================================
                        // 🔥 RESTART ECHO & SINGLE GLOBAL NOTIFICATION LISTENER 🔥
                        // =========================================================================
                       // =========================================================================
                        // 🔥 RESTART ECHO & SINGLE GLOBAL NOTIFICATION LISTENER 🔥
                        // =========================================================================
                        if (typeof window.Echo !== 'undefined') {

                            // 1. Force Echo to reconnect with the exact token BEFORE subscribing
                            let currentOptions = window.Echo.connector.options;
                            currentOptions.authEndpoint = '/broadcasting/auth?token=' + encodeURIComponent(layoutToken);
                            currentOptions.auth = {
                                headers: {
                                    'Authorization': 'Bearer ' + layoutToken,
                                    'Accept': 'application/json'
                                },
                                params: {
                                    token: layoutToken
                                }
                            };
                            window.Echo.disconnect();
                            window.Echo = new window.Echo.constructor(currentOptions);

                            // 2. Subscribe to Global Channel
                            let channelName = `global.user.${currentPortal}.${u.id}`;
                            console.log("Subscribing to Global Bell Channel: ", channelName);

                            window.Echo.private(channelName)
                                .listen('.notification.received', (e) => {
                                    let log = e.logData;
                                    let isNotice = log.type && log.type === 'notice'; 
                                    let isTA = log.type && log.type === 'ta_request'; // 🔥 Naya TA Identification Check
                                    
                                    let currentCount = parseInt($('#globalUnreadCount').text()) || 0;
                                    $('#globalUnreadCount').text(currentCount + 1).removeClass('d-none');
                                    $('#noNotifMessage').addClass('d-none');
                                    
                                    // 🔥 Dynamic Data & Link Mapping 🔥
                                    let targetUrl = isNotice ? `/${currentPortal}/my-notices` : (isTA ? `/${currentPortal}/travel-allowances` : `/${currentPortal}/tasks`);
                                    let iconClass = isNotice ? 'fa-bullhorn text-danger' : (isTA ? 'fa-car-side text-success' : 'fa-tasks text-primary');
                                    let headingLabel = isNotice ? 'Official Notice' : (isTA ? 'Travel Allowance' : 'Task Update');
                                    let titleLabel = isNotice ? 'Unread Notice:' : (isTA ? 'TA Alert:' : 'Unread msg from Task:');
                                    
                                    let notifHtml = `
                                        <li class="border-bottom bg-light">
                                            <a class="dropdown-item py-3 px-3 d-flex align-items-center" href="${targetUrl}">
                                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3 border" style="width:35px; height:35px; min-width:35px;">
                                                    <i class="fas ${iconClass}"></i>
                                                </div>
                                                <div class="w-100 overflow-hidden">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <strong class="text-dark small d-block">${headingLabel}</strong>
                                                        <span class="badge bg-danger blink-anim" style="font-size: 0.65rem;">New</span>
                                                    </div>
                                                    <div class="small text-muted fw-medium text-truncate">
                                                        ${titleLabel} <span class="text-dark fw-bold">${e.taskTitle}</span>
                                                    </div>
                                                    <div class="small text-muted mt-1" style="font-size: 10px;">
                                                        By ${log.actor_name || 'System'}
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                    `;
                                    $('#notificationList').prepend(notifHtml);
                                    
                                    if (!isNotice && !isTA && typeof window.markTaskAsUnread === 'function') {
                                        if (!$('#taskDetailsModal').hasClass('show') || $('#replyTaskId').val() != e.taskId) {
                                            window.markTaskAsUnread(e.taskId);
                                        }
                                    }
                                });


                                // =========================================================================
                        // 🔥 OFFLINE NOTIFICATION CATCH-UP (Fetch Missed Notices) 🔥
                        // =========================================================================
                        function loadMissedNotices() {
                            $.get('/api/v1/my-notices', function(response) {
                                if (response.success && response.data.length > 0) {
                                    let unreadCount = 0;
                                    let notifHtmlList = '';
                                    
                                    // Browser storage se check karein ki kaun se notices already bell me dekh liye hain
                                    let seenNotices = JSON.parse(localStorage.getItem('seen_notices_' + u.id)) || [];

                                    response.data.forEach(notice => {
                                        let noticeDate = new Date(notice.created_at);
                                        let today = new Date();
                                        // Calculate karein kitne din purana notice hai
                                        let diffDays = Math.ceil(Math.abs(today - noticeDate) / (1000 * 60 * 60 * 24));

                                        // Agar notice last 7 days me aaya hai aur user ne bell pe click karke nahi dekha hai
                                        if (diffDays <= 7 && !seenNotices.includes(notice.id)) {
                                            unreadCount++;
                                            let targetUrl = `/${currentPortal}/my-notices`;
                                            
                                            notifHtmlList += `
                                                <li class="border-bottom bg-light offline-notice-item" data-id="${notice.id}">
                                                    <a class="dropdown-item py-3 px-3 d-flex align-items-center" href="${targetUrl}">
                                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3 border" style="width:35px; height:35px; min-width:35px;">
                                                            <i class="fas fa-bullhorn text-danger"></i>
                                                        </div>
                                                        <div class="w-100 overflow-hidden">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <strong class="text-dark small d-block">Official Notice</strong>
                                                                <span class="badge bg-danger blink-anim" style="font-size: 0.65rem;">Missed</span>
                                                            </div>
                                                            <div class="small text-muted fw-medium text-truncate">
                                                                Unread Notice: <span class="text-dark fw-bold">${notice.title}</span>
                                                            </div>
                                                            <div class="small text-muted mt-1" style="font-size: 10px;">
                                                                Date: ${notice.notice_date}
                                                            </div>
                                                        </div>
                                                    </a>
                                                </li>
                                            `;
                                        }
                                    });

                                    // Agar koi unread/missed notice mila, toh Bell me list update karo
                                    if (unreadCount > 0) {
                                        let currentCount = parseInt($('#globalUnreadCount').text()) || 0;
                                        $('#globalUnreadCount').text(currentCount + unreadCount).removeClass('d-none');
                                        $('#noNotifMessage').addClass('d-none');
                                        $('#notificationList').prepend(notifHtmlList);
                                    }
                                }
                            });
                        }

                        // Load function call karein page load par
                        loadMissedNotices();

                        // 👇 YAHAN SE NAYA CODE PASTE KAREIN 👇
                        // =========================================================================
                        // 🔥 OFFLINE TA NOTIFICATIONS (Fetch Missed TA Alerts) 🔥
                        // =========================================================================
                        function loadMissedTAAlerts() {
                            // Hum recent 20 TA requests fetch karenge
                            $.get('/api/v1/travel-allowances?per_page=20', function(response) {
                                let records = response.data && response.data.data ? response.data.data : (response.data || []);
                                
                                if (records.length > 0) {
                                    let unreadCount = 0;
                                    let notifHtmlList = '';
                                    let seenNotices = JSON.parse(localStorage.getItem('seen_notices_' + u.id)) || [];

                                    records.forEach(ta => {
                                        let taDate = new Date(ta.updated_at || ta.created_at);
                                        let today = new Date();
                                        let diffDays = Math.ceil(Math.abs(today - taDate) / (1000 * 60 * 60 * 24));

                                        // Sirf last 7 din ke requests check karenge
                                        if (diffDays <= 7) {
                                            let isOwnTA = (ta.employee_id == u.id);
                                            let isActionable = false;
                                            let notifTitle = '';
                                            let badgeText = 'Missed';
                                            let badgeClass = 'bg-primary';

                                            // CONDITION 1: Admin/Manager ke liye (Agar TA pending hai aur unka khud ka nahi hai)
                                            if (ta.status === 'pending' && !isOwnTA) {
                                                isActionable = true;
                                                notifTitle = `New TA Request from ${ta.employee ? ta.employee.full_name : 'Employee'}`;
                                                badgeText = 'Action Required';
                                                badgeClass = 'bg-warning text-dark';
                                            } 
                                            // CONDITION 2: Employee ke liye (Agar unka khud ka TA approve ya reject hua hai)
                                            else if (ta.status !== 'pending' && isOwnTA) {
                                                isActionable = true;
                                                let statusText = ta.status === 'active' ? 'APPROVED' : 'REJECTED';
                                                notifTitle = `Your TA was ${statusText}`;
                                                badgeClass = ta.status === 'active' ? 'bg-success' : 'bg-danger';
                                                badgeText = statusText;
                                            }

                                            // 🔥 SMART ID LOGIC: 'ta_1_pending' ya 'ta_1_active' 
                                            // Isse status badalne par (jaise pending se approve) naya notification aayega
                                            let notifId = 'ta_' + ta.id + '_' + ta.status;

                                            // Agar yeh alert pehle seen nahi hua hai, toh list me add karo
                                            if (isActionable && !seenNotices.includes(notifId)) {
                                                unreadCount++;
                                                let targetUrl = `/${currentPortal}/travel-allowances`;
                                                
                                                notifHtmlList += `
                                                    <li class="border-bottom bg-light offline-notice-item" data-id="${notifId}">
                                                        <a class="dropdown-item py-3 px-3 d-flex align-items-center" href="${targetUrl}">
                                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3 border" style="width:35px; height:35px; min-width:35px;">
                                                                <i class="fas fa-car-side text-success"></i>
                                                            </div>
                                                            <div class="w-100 overflow-hidden">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <strong class="text-dark small d-block">Travel Allowance</strong>
                                                                    <span class="badge ${badgeClass} blink-anim" style="font-size: 0.65rem;">${badgeText}</span>
                                                                </div>
                                                                <div class="small text-muted fw-medium text-truncate">
                                                                    ${notifTitle}
                                                                </div>
                                                                <div class="small text-muted mt-1" style="font-size: 10px;">
                                                                    Date: ${ta.ta_date} | Amount: ₹${ta.amount}
                                                                </div>
                                                            </div>
                                                        </a>
                                                    </li>
                                                `;
                                            }
                                        }
                                    });

                                    // Agar koi unread TA alert mila, toh Bell Icon ka count badhao
                                    if (unreadCount > 0) {
                                        let currentCount = parseInt($('#globalUnreadCount').text()) || 0;
                                        $('#globalUnreadCount').text(currentCount + unreadCount).removeClass('d-none');
                                        $('#noNotifMessage').addClass('d-none');
                                        $('#notificationList').prepend(notifHtmlList);
                                    }
                                }
                            });
                        }

                        // Page load par TA alerts check karo
                        loadMissedTAAlerts();

                        // Jab user bell icon par click kare, toh un offline notifications ko "seen" mark kar do
                        // Taaki agli baar page refresh hone par wapas baar-baar bell icon par count na badhe
                        $('#globalNotificationDropdown').on('show.bs.dropdown', function () {
                            let seenNotices = JSON.parse(localStorage.getItem('seen_notices_' + u.id)) || [];
                            $('.offline-notice-item').each(function() {
                                let nid = $(this).data('id');
                                if(!seenNotices.includes(nid)) {
                                    seenNotices.push(nid);
                                }
                            });
                            localStorage.setItem('seen_notices_' + u.id, JSON.stringify(seenNotices));
                            
                            // 1 second baad count hata do kyuki list dekh li gayi hai
                            setTimeout(() => {
                                $('#globalUnreadCount').text('0').addClass('d-none');
                            }, 1000);
                        });



                        }
                    }
                });

                $('.handle-logout').on('click', function(e) {
                    performNormalLogout();
                });
            });
        })();
    </script>
    @stack('scripts')
</body>

</html>
