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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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

        html,
        body {
            max-width: 100vw;
            overflow-x: hidden;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
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
                display: flex;
                align-items: center;
                position: relative;
                min-width: 0;
            }

            .app-main {
                margin-left: 0;
                padding: 30px;
                min-height: calc(100vh - 70px);
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
                background-color: var(--sidebar-hover);
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
                background-color: var(--sidebar-active);
            }

            .nav-scroll-btn.left-btn {
                left: 0;
                border-top-left-radius: 8px;
                border-bottom-left-radius: 8px;
                box-shadow: 4px 0 8px rgba(0, 0, 0, 0.1);
            }

            .nav-scroll-btn.right-btn {
                right: 0;
                border-top-right-radius: 8px;
                border-bottom-right-radius: 8px;
                box-shadow: -4px 0 8px rgba(0, 0, 0, 0.1);
            }

            .nav-scroll-wrapper {
                flex: 1;
                width: 100%;
                padding: 0 35px;
                position: relative;
                overflow-x: clip;
                overflow-y: visible;
                background-color: var(--sidebar-bg);
                border-radius: 8px;
                min-height: 45px;
                display: flex;
                align-items: center;
                cursor: grab;
                user-select: none;
            }

            .nav-scroll-wrapper:active {
                cursor: grabbing;
            }

            .desktop-nav {
                list-style: none;
                margin: 0;
                padding: 0;
                display: flex;
                flex-wrap: nowrap;
                gap: 5px;
                width: 100%;
                transition: transform 0.3s ease-in-out;
            }

            .desktop-nav>li {
                position: relative;
                flex: 0 0 auto;
                margin-top: 3px;
                margin-bottom: 3px;
            }

            .desktop-nav>li>a {
                color: #E2E8F0;
                text-decoration: none;
                font-size: 13px;
                font-weight: 500;
                padding: 8px 14px;
                display: block;
                border-radius: 4px;
                transition: all 0.2s ease-in-out;
                white-space: nowrap;
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
        } elseif (request()->is('member/*')) {
            $currentPortalPrefix = 'member';
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
                        $cleanRoute = preg_replace('/^(admin|employee|customer|Member)\//', '', $rawRoute);
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
                        $cleanRoute = preg_replace('/^(admin|employee|customer|member)\//', '', $rawRoute);
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

            <div class="header-bottom flex-grow-1 mx-4" id="desktopNavContainer">
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
                            <a href="{{ url($currentPortalPrefix . '/welcome-letter') }}">
                                <i class="fas fa-envelope-open-text text-info"></i> Welcome Letter
                            </a>
                        </li>

                        @if ($currentPortalPrefix === 'admin' || $currentPortalPrefix === 'employee')
                            <li class="secured-item" data-permission="task_view">
                                <a href="{{ url($currentPortalPrefix . '/tasks/staff') }}"
                                    class="{{ request()->is('*/tasks/staff') ? 'active' : '' }}">
                                    <i class="fas fa-user-tie text-success"></i> Staff Tasks
                                </a>
                            </li>
                        @endif

                        @if ($currentPortalPrefix === 'admin' || $currentPortalPrefix === 'member')
                            <li class="secured-item" data-permission="task_mem_view">
                                <a href="{{ url($currentPortalPrefix . '/tasks/associates') }}"
                                    class="{{ request()->is('*/tasks/associates') ? 'active' : '' }}">
                                    <i class="fas fa-users text-warning"></i> Associate Tasks
                                </a>
                            </li>
                        @endif

                        <li class="secured-item" data-permission="phases_view">
                            <a href="{{ url($currentPortalPrefix . '/phases') }}"
                                class="{{ request()->is('*/phases') ? 'active' : '' }}">
                                <i class="fas fa-building text-warning"></i> Phases
                            </a>
                        </li>
                        <li class="secured-item" data-permission="public">
                            @if ($currentPortalPrefix === 'employee')
                                <a href="{{ url($currentPortalPrefix . '/leave-applications') }}">
                                    <i class="fas fa-calendar-alt text-primary"></i> Leaves & Apps
                                </a>
                            @elseif($currentPortalPrefix === 'customer' || $currentPortalPrefix === 'member')
                                <a href="{{ url($currentPortalPrefix . '/member-leave-applications') }}">
                                    <i class="fas fa-calendar-alt text-primary"></i> Leaves & Apps
                                </a>
                            @endif
                        </li>

                        {!! buildDesktopMenu($rootModules, $allModules, $currentPortalPrefix) !!}

                        <li class="secured-item" data-permission="public">
                            <a href="{{ url($currentPortalPrefix . '/my-notices') }}"
                                class="{{ request()->is('*/my-notices') ? 'active' : '' }}">
                                <i class="fas fa-bell text-danger"></i> My Notices
                            </a>
                        </li>

                        <li class="secured-item" data-permission="public">
                            <a href="{{ url($currentPortalPrefix . '/my-penalties') }}"
                                class="{{ request()->is('*/my-penalties') ? 'active' : '' }}">
                                <i class="fa-solid fa-file-invoice-dollar"></i> My Fine/Penalties
                            </a>
                        </li>
                    </ul>
                </div>

                <button class="nav-scroll-btn right-btn" id="btnScrollRight"><i
                        class="fas fa-chevron-right"></i></button>
            </div>

            <div class="d-flex align-items-center gap-3">

                <button class="btn btn-light rounded-circle border-0 text-secondary shadow-sm"
                    onclick="window.location.href='{{ url($currentPortalPrefix . '/travel-allowances') }}'"
                    title="Travel Allowances">
                    <i class="fas fa-car-side text-success"></i>
                </button>

                <button class="btn btn-light rounded-circle border-0 text-secondary shadow-sm"
                    onclick="window.location.href='{{ url($currentPortalPrefix . '/rules-regulations') }}'"
                    title="Rules & Regulations">
                    <i class="fas fa-gavel text-primary"></i>
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

                <!-- 🔥 NAYA: Header Profile Avatar and Dropdown Update 🔥 -->
                <div class="dropdown">
                    <a href="#" class="text-decoration-none d-flex align-items-center gap-2"
                        data-bs-toggle="dropdown" style="color: var(--text-main);">
                        <!-- Yahan se text (name display) hata diya gaya hai -->
                        <img src="https://ui-avatars.com/api/?name=User&background=1A365D&color=fff" alt="User"
                            class="rounded-circle user-avatar-img shadow-sm border" width="38" height="38"
                            style="object-fit: cover;">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3 p-2" style="min-width: 200px;">
                        <!-- 🔥 NAYA: User Greeting Info in Dropdown 🔥 -->
                        <li class="px-3 py-2 border-bottom mb-2 bg-light rounded text-center">
                            <span class="d-block text-muted"
                                style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Hi,</span>
                            <strong class="user-greeting-name text-dark fs-6 text-truncate d-block"
                                style="max-width: 100%;">Loading...</strong>
                        </li>

                        <li>
                            <a class="dropdown-item py-2 fw-medium rounded"
                                href="{{ url($currentPortalPrefix . '/my-profile') }}">
                                <i class="fas fa-user-circle me-2 text-success"></i> My Profile
                            </a>
                        </li>
                        <li><a class="dropdown-item py-2 fw-medium rounded"
                                href="{{ url($currentPortalPrefix . '/terms-conditions') }}"><i
                                    class="fas fa-file-contract me-2 text-primary"></i> Terms & Conditions</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item py-2 fw-medium handle-logout rounded" href="#"
                                style="color: #E53E3E;"><i class="fas fa-sign-out-alt me-2"></i> Sign Out</a></li>
                    </ul>
                </div>
            </div>
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

                <a href="{{ url($currentPortalPrefix . '/welcome-letter') }}"
                    class="nav-item-custom secured-item border-bottom mb-2" data-permission="public">
                    <div><i class="fas fa-envelope-open-text text-info menu-icon"></i> Welcome Letter</div>
                </a>

                @if ($currentPortalPrefix === 'admin' || $currentPortalPrefix === 'employee')
                    <a href="{{ url($currentPortalPrefix . '/tasks/staff') }}"
                        class="nav-item-custom secured-item border-bottom mb-2 {{ request()->is('*/tasks/staff') ? 'active' : '' }}"
                        data-permission="task_view">
                        <div><i class="fas fa-user-tie text-success menu-icon"></i> Staff Tasks</div>
                    </a>
                @endif

                @if ($currentPortalPrefix === 'admin' || $currentPortalPrefix === 'member')
                    <a href="{{ url($currentPortalPrefix . '/tasks/associates') }}"
                        class="nav-item-custom secured-item border-bottom mb-2 {{ request()->is('*/tasks/associates') ? 'active' : '' }}"
                        data-permission="task_mem_view">
                        <div><i class="fas fa-users text-warning menu-icon"></i> Associate Tasks</div>
                    </a>
                @endif

                <a href="{{ url($currentPortalPrefix . '/phases') }}"
                    class="nav-item-custom secured-item border-bottom mb-2 {{ request()->is('*/phases') ? 'active' : '' }}"
                    data-permission="phases_view">
                    <div><i class="fas fa-building text-warning menu-icon"></i> Phases</div>
                </a>

                @if ($currentPortalPrefix === 'employee')
                    <a href="{{ url($currentPortalPrefix . '/leave-applications') }}"
                        class="nav-item-custom secured-item border-bottom mb-2" data-permission="public">
                        <div><i class="fas fa-calendar-alt text-primary menu-icon"></i> Leaves & Apps</div>
                    </a>
                @elseif($currentPortalPrefix === 'customer' || $currentPortalPrefix === 'member')
                    <a href="{{ url($currentPortalPrefix . '/member-leave-applications') }}"
                        class="nav-item-custom secured-item border-bottom mb-2" data-permission="public">
                        <div><i class="fas fa-calendar-alt text-primary menu-icon"></i> Leaves & Apps</div>
                    </a>
                @endif

                <a href="{{ url($currentPortalPrefix . '/rules-regulations') }}"
                    class="nav-item-custom border-bottom mb-2 text-warning">
                    <div><i class="fas fa-gavel text-warning menu-icon"></i> Rules & Regulations</div>
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

                <a href="{{ url($currentPortalPrefix . '/my-penalties') }}"
                    class="nav-item-custom secured-item border-bottom mb-2 {{ request()->is('*/my-penalties') ? 'active' : '' }}"
                    data-permission="public">
                    <div><i class="fa-solid fa-file-invoice-dollar menu-icon"></i> My Fine/Penalties</div>
                </a>

            </div>
            <div class="sidebar-user-card mt-auto">
                <div class="d-flex align-items-center mb-3">
                    <img src="https://ui-avatars.com/api/?name=User&background=D69E2E&color=fff"
                        class="rounded-circle me-3 user-avatar-img" width="40" height="40"
                        style="object-fit: cover;">
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
        <a href="{{ url($currentPortalPrefix . '/dashboard') }}"
            class="{{ request()->is('*/dashboard') ? 'active' : '' }}"><i class="fas fa-layer-group"></i>Home</a>
        <a href="{{ url($currentPortalPrefix . '/welcome-letter') }}"
            class="secured-item {{ request()->is('*/welcome-letter') ? 'active' : '' }}" data-permission="public"><i
                class="fas fa-envelope-open-text"></i>Letter</a>

        @if ($currentPortalPrefix === 'member')
            <a href="{{ url($currentPortalPrefix . '/tasks/associates') }}"
                class="secured-item {{ request()->is('*/tasks/*') ? 'active' : '' }}"
                data-permission="task_mem_view">
                <i class="fas fa-tasks"></i>Tasks
            </a>
        @else
            <a href="{{ url($currentPortalPrefix . '/tasks/staff') }}"
                class="secured-item {{ request()->is('*/tasks/*') ? 'active' : '' }}" data-permission="task_view">
                <i class="fas fa-tasks"></i>Tasks
            </a>
        @endif

        <a href="#" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar"><i
                class="fas fa-bars"></i>Menu</a>
    </nav>

    <div class="modal fade" id="deviceManagerModal" tabindex="-1" data-bs-backdrop="static"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
            } else if (currentPath.startsWith('/member')) {
                currentPortal = 'member';
                tokenKey = 'member_token';
                loginUrl = '/member/login';
                authApiUrl = '/api/v1/member/auth/me';
                logoutApiUrl = '/api/v1/member/auth/logout';
            }

            const layoutToken = localStorage.getItem(tokenKey) || localStorage.getItem('token') || '';

            if (layoutToken) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Authorization': 'Bearer ' + layoutToken
                    }
                });

                if (typeof window.axios !== 'undefined') {
                    window.axios.defaults.headers.common['Authorization'] = 'Bearer ' + layoutToken;
                }
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

            window.performNormalLogout = function(isAuto = false) {
                let payload = {};
                if (currentPortal === 'employee') {
                    payload = {
                        panel_id: localStorage.getItem('emp_panel_id'),
                        is_auto: isAuto ? 1 : 0
                    };
                }

               function executeLogout() {
                    Swal.fire({
                        title: 'Recording Logout Location...',
                        text: 'Please wait...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    // Pehle location nikalenge, fir logout hit karenge
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(function(position) {
                            payload.logout_lat = position.coords.latitude;
                            payload.logout_lng = position.coords.longitude;
                            sendLogoutRequest(payload);
                        }, function(error) {
                            console.warn("Location off hai, direct logout kar rahe hain.");
                            sendLogoutRequest(payload); // Agar GPS off ho toh bina location logout ho jaye
                        }, { enableHighAccuracy: true });
                    } else {
                        sendLogoutRequest(payload);
                    }
                }

                // Naya Helper Function
                function sendLogoutRequest(finalPayload) {
                    $.ajax({
                        url: logoutApiUrl,
                        type: 'POST',
                        data: finalPayload,
                        success: function() { clearLocalDataAndRedirect(); },
                        error: function() { clearLocalDataAndRedirect(); }
                    });
                }

                if (isAuto) {
                    executeLogout();
                } else {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "Do you want to log out and record your Time Out?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#E53E3E',
                        cancelButtonColor: '#718096',
                        confirmButtonText: 'Yes, Logout'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            executeLogout();
                        }
                    });
                }
            };

            let lastActivityTime = Date.now();
            let idleInterval;
            const maxIdleTime = 15 * 60 * 1000;

            function resetIdleTime() {
                lastActivityTime = Date.now();
            }

            $(document).on('mousemove keydown scroll click touchstart touchmove', resetIdleTime);

            function startInactivityTracker() {
                idleInterval = setInterval(function() {
                    let currentTime = Date.now();
                    if (currentTime - lastActivityTime >= maxIdleTime) {
                        clearInterval(idleInterval);
                        Swal.fire({
                            title: 'Session Expired!',
                            text: '15 minutes of inactivity detected. Auto logging out.',
                            icon: 'info',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            timer: 3000
                        }).then(() => {
                            window.performNormalLogout(true);
                        });
                    }
                }, 10000);
            }

            if (layoutToken) {
                startInactivityTracker();
            }

            $(document).ready(function() {

                const navArea = document.getElementById('desktopNavScrollArea');
                const wrapper = document.getElementById('navScrollWrapper');
                const btnLeft = document.getElementById('btnScrollLeft');
                const btnRight = document.getElementById('btnScrollRight');

                let currentNavScroll = 0;

                if (navArea && wrapper && btnLeft && btnRight) {
                    const updateScrollButtons = () => {
                        let maxScroll = navArea.scrollWidth - wrapper.clientWidth;
                        if (maxScroll <= 0) {
                            btnLeft.style.display = 'none';
                            btnRight.style.display = 'none';
                            currentNavScroll = 0;
                            navArea.style.transform = `translateX(0px)`;
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
                        let maxScroll = navArea.scrollWidth - wrapper.clientWidth;
                        currentNavScroll += 300;
                        if (currentNavScroll > maxScroll) currentNavScroll = maxScroll;
                        navArea.style.transform = `translateX(-${currentNavScroll}px)`;
                        updateScrollButtons();
                    });

                    let isDragging = false;
                    let didDrag = false;
                    let startX;
                    let initialTransform = 0;

                    wrapper.addEventListener('mousedown', (e) => {
                        isDragging = true;
                        didDrag = false;
                        startX = e.pageX;
                        initialTransform = currentNavScroll;
                        navArea.style.transition = 'none';
                    });

                    window.addEventListener('mouseup', () => {
                        if (isDragging) {
                            isDragging = false;
                            navArea.style.transition = 'transform 0.3s ease-in-out';
                        }
                    });

                    window.addEventListener('mousemove', (e) => {
                        if (!isDragging) return;
                        e.preventDefault();
                        const x = e.pageX;

                        if (Math.abs(startX - x) > 5) {
                            didDrag = true;
                        }

                        const walk = (startX - x);
                        let maxScroll = navArea.scrollWidth - wrapper.clientWidth;
                        if (maxScroll < 0) maxScroll = 0;

                        currentNavScroll = initialTransform + walk;

                        if (currentNavScroll < 0) currentNavScroll = 0;
                        if (currentNavScroll > maxScroll) currentNavScroll = maxScroll;

                        navArea.style.transform = `translateX(-${currentNavScroll}px)`;
                        updateScrollButtons();
                    });

                    navArea.querySelectorAll('a').forEach(link => {
                        link.addEventListener('click', (e) => {
                            if (didDrag) {
                                e.preventDefault();
                                e.stopPropagation();
                            }
                        });
                    });

                    wrapper.addEventListener('wheel', function(e) {
                        if (e.deltaY !== 0) {
                            e.preventDefault();
                            let maxScroll = navArea.scrollWidth - wrapper.clientWidth;
                            currentNavScroll += e.deltaY > 0 ? 150 : -150;
                            if (currentNavScroll < 0) currentNavScroll = 0;
                            if (currentNavScroll > maxScroll) currentNavScroll = maxScroll;
                            navArea.style.transform = `translateX(-${currentNavScroll}px)`;
                            updateScrollButtons();
                        }
                    }, {
                        passive: false
                    });

                    if (window.ResizeObserver) {
                        const resizeObserver = new ResizeObserver(() => {
                            let maxScroll = navArea.scrollWidth - wrapper.clientWidth;
                            if (currentNavScroll > maxScroll && maxScroll >= 0) {
                                currentNavScroll = maxScroll;
                                navArea.style.transform = `translateX(-${currentNavScroll}px)`;
                            }
                            updateScrollButtons();
                        });
                        resizeObserver.observe(navArea);
                        resizeObserver.observe(wrapper);
                    }

                    setTimeout(updateScrollButtons, 500);
                }

                $('.desktop-nav li.has-sub').on('mouseenter', function() {
                    let $item = $(this);
                    let $dropdown = $item.children('.desktop-dropdown');

                    $dropdown.css({
                        'left': '0',
                        'right': 'auto'
                    });
                    $item.find('li.has-sub > .desktop-dropdown').css({
                        'left': '100%',
                        'right': 'auto',
                        'margin-left': '1px',
                        'margin-right': '0'
                    });

                    let wrapperEl = document.getElementById('navScrollWrapper');
                    if (wrapperEl && $dropdown.length > 0) {
                        let wrapperRect = wrapperEl.getBoundingClientRect();
                        let dropdownRect = $dropdown[0].getBoundingClientRect();

                        if (dropdownRect.right > wrapperRect.right) {
                            $dropdown.css({
                                'left': 'auto',
                                'right': '0'
                            });
                            $item.find('li.has-sub > .desktop-dropdown').css({
                                'left': 'auto',
                                'right': '100%',
                                'margin-left': '0',
                                'margin-right': '1px'
                            });
                        }
                    }
                });

                if (typeof $.fn.dataTable !== 'undefined') {
                    $.fn.dataTable.ext.errMode = 'none';
                }

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

                        let isGodMode = u.is_god || developerEmails.includes(emailStr);

                        let perms = Array.isArray(u.permissions) ? u.permissions : [];
                        if (u.permissions && !Array.isArray(u.permissions)) {
                            perms = Object.values(u.permissions).map(p => p.name || p);
                        }

                        // 🔥 NAYA: Update Names and Avatars with passport_photo logic 🔥
                        let displayUserName = u.name || u.full_name || u.employee_name || u
                            .member_name || 'User';

                        $('.user-name-display').text(displayUserName); // Mobile side menu name
                        $('.user-greeting-name').text(
                        displayUserName); // Desktop Dropdown greeting name

                        $('.user-role-display').text(isGodMode ? 'Master Access' : (u
                            .designation_name || u.designation || currentPortal
                            .toUpperCase()));

                        // Handle Profile Image fetch from passport_photo (Employees[cite: 5], Members[cite: 6], SuperAdmins[cite: 7], Customers[cite: 3])
                        let profileImageUrl =
                            `https://ui-avatars.com/api/?name=${encodeURIComponent(displayUserName)}&background=1A365D&color=fff`;

                        if (u.passport_photo) {
                            // Prepend root slash directly to load from public root if it's not an absolute URL
                            profileImageUrl = u.passport_photo.startsWith('http') ? u
                                .passport_photo : '/' + u.passport_photo;
                        }

                        $('.user-avatar-img').attr('src', profileImageUrl).on('error', function() {
                            // Fallback if image path is broken
                            $(this).attr('src',
                                `https://ui-avatars.com/api/?name=${encodeURIComponent(displayUserName)}&background=1A365D&color=fff`
                                );
                        });

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

                                    isPermitted = window.userPerms.some(p => {
                                        if (p === reqPerm) return true;
                                        if (p.startsWith(base + '_')) {
                                            if (base === 'task' && p.startsWith(
                                                    'task_mem')) {
                                                return false;
                                            }
                                            return true;
                                        }
                                        return false;
                                    });
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
                        };

                        window.applyPermissions();

                        let targetDashboard = '/' + currentPortal + '/dashboard';
                        $('.brand-logo-img').css('cursor', 'pointer').on('click', function(e) {
                            e.preventDefault();
                            window.location.href = targetDashboard;
                        });

                        if (typeof window.Echo !== 'undefined') {
                            let currentOptions = window.Echo.connector.options;
                            currentOptions.authEndpoint = '/broadcasting/auth?token=' +
                                encodeURIComponent(layoutToken);
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

                            let customChannel = `global.user.${currentPortal}.${u.id}`;
                            let modelName = 'User';
                            if (currentPortal === 'employee') modelName = 'Employee';
                            if (currentPortal === 'customer') modelName = 'Customer';
                            if (currentPortal === 'member') modelName = 'Member';
                            let defaultLaravelChannel = `App.Models.${modelName}.${u.id}`;

                            let processNotification = (e) => {
                                if (e.title || e.type) {
                                    let title = e.title || 'System Alert';
                                    let message = e.message || '';
                                    let targetUrl = e.url || '#';
                                    let iconClass = e.icon || 'fa-bell';
                                    let colorClass = e.colorClass || 'text-primary';

                                    if (targetUrl.match(/\/tasks\/?$/)) {
                                        if (currentPortal === 'member') {
                                            targetUrl = targetUrl.replace(/\/tasks\/?$/,
                                                '/tasks/associates');
                                        } else if (currentPortal === 'employee') {
                                            targetUrl = targetUrl.replace(/\/tasks\/?$/,
                                                '/tasks/staff');
                                        } else {
                                            if (message.toLowerCase().includes('associate') ||
                                                message.toLowerCase().includes('member')) {
                                                targetUrl = targetUrl.replace(/\/tasks\/?$/,
                                                    '/tasks/associates');
                                            } else {
                                                targetUrl = targetUrl.replace(/\/tasks\/?$/,
                                                    '/tasks/staff');
                                            }
                                        }
                                    }

                                    let currentCount = parseInt($('#globalUnreadCount')
                                        .text()) || 0;
                                    $('#globalUnreadCount').text(currentCount + 1).removeClass(
                                        'd-none');
                                    $('#noNotifMessage').addClass('d-none');

                                    let notifHtml = `
                                        <li class="border-bottom bg-light">
                                            <a class="dropdown-item py-3 px-3 d-flex align-items-center" href="${targetUrl}">
                                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3 border" style="width:35px; height:35px; min-width:35px;">
                                                    <i class="fas ${iconClass} ${colorClass}"></i>
                                                </div>
                                                <div class="w-100 overflow-hidden">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <strong class="text-dark small d-block">${title}</strong>
                                                        <span class="badge bg-danger blink-anim notification-new-badge" style="font-size: 0.65rem;">New</span>
                                                    </div>
                                                    <div class="small text-muted fw-medium text-truncate" style="white-space: pre-wrap;">${message}</div>
                                                    <div class="small text-muted mt-1" style="font-size: 10px;">Just now</div>
                                                </div>
                                            </a>
                                        </li>
                                    `;
                                    $('#notificationList').prepend(notifHtml);
                                }
                            };

                            [customChannel, defaultLaravelChannel].forEach(channel => {
                                window.Echo.private(channel).listen(
                                        '.notification.received', processNotification)
                                    .notification(processNotification);
                            });

                            function loadDatabaseNotifications() {
                                $.get('/api/v1/notifications/unread', function(response) {
                                    if (response.success && response.data.length > 0) {
                                        let unreadCount = response.data.length;
                                        let notifHtmlList = '';

                                        response.data.forEach(notif => {
                                            let payload = notif.data;
                                            let targetUrl = payload.url || '#';

                                            if (targetUrl.match(/\/tasks\/?$/)) {
                                                if (currentPortal === 'member') {
                                                    targetUrl = targetUrl.replace(
                                                        /\/tasks\/?$/,
                                                        '/tasks/associates');
                                                } else if (currentPortal ===
                                                    'employee') {
                                                    targetUrl = targetUrl.replace(
                                                        /\/tasks\/?$/,
                                                        '/tasks/staff');
                                                } else {
                                                    if (payload.message && (payload
                                                            .message.toLowerCase()
                                                            .includes(
                                                                'associate') ||
                                                            payload
                                                            .message.toLowerCase()
                                                            .includes('member'))) {
                                                        targetUrl = targetUrl
                                                            .replace(/\/tasks\/?$/,
                                                                '/tasks/associates'
                                                            );
                                                    } else {
                                                        targetUrl = targetUrl
                                                            .replace(/\/tasks\/?$/,
                                                                '/tasks/staff');
                                                    }
                                                }
                                            }
                                            if (currentPortal === 'member' &&
                                                targetUrl.includes('/customer/')) {
                                                targetUrl = targetUrl.replace(
                                                    '/customer/', '/member/');
                                            }

                                            let icon = payload.icon || 'fa-bell';
                                            let colorClass = payload.colorClass ||
                                                'text-primary';

                                            notifHtmlList += `
                                                <li class="border-bottom bg-light">
                                                    <a class="dropdown-item py-3 px-3 d-flex align-items-center" href="${targetUrl}">
                                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3 border" style="width:35px; height:35px; min-width:35px;">
                                                            <i class="fas ${icon} ${colorClass}"></i>
                                                        </div>
                                                        <div class="w-100 overflow-hidden">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <strong class="text-dark small d-block">${payload.title || 'System Alert'}</strong>
                                                                <span class="badge bg-danger blink-anim notification-new-badge" style="font-size: 0.65rem;">New</span>
                                                            </div>
                                                            <div class="small text-muted fw-medium text-truncate" style="white-space: pre-wrap;">${payload.message || ''}</div>
                                                            <div class="small text-muted mt-1" style="font-size: 10px;">
                                                                ${new Date(notif.created_at).toLocaleString('en-IN', { hour12: true, month: 'short', day: 'numeric', hour: '2-digit', minute:'2-digit' })}
                                                            </div>
                                                        </div>
                                                    </a>
                                                </li>
                                            `;
                                        });

                                        $('#globalUnreadCount').text(unreadCount)
                                            .removeClass('d-none');
                                        $('#noNotifMessage').addClass('d-none');
                                        $('#notificationList').html(notifHtmlList);
                                    }
                                });
                            }

                            loadDatabaseNotifications();

                            $('#globalNotificationDropdown').on('show.bs.dropdown', function() {
                                let count = parseInt($('#globalUnreadCount').text()) || 0;
                                if (count > 0) {
                                    $.post('/api/v1/notifications/mark-read', function() {
                                        setTimeout(() => {
                                            $('#globalUnreadCount').text(
                                                '0').addClass('d-none');
                                            $('.notification-new-badge')
                                                .fadeOut();
                                        }, 1500);
                                    });
                                }
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
