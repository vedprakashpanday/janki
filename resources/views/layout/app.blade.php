<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <title>JankiVilla | Workspace</title>
     <link rel="shortcut icon" href="{{asset('uploads/harihomes1-fevicon.png')}}" type="image/x-icon">
    
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

        /* ---------------- Desktop Layout (Top Navigation) ---------------- */
        @media (min-width: 768px) {
            .app-sidebar { display: none !important; } /* Sidebar hat gaya */
            
            .app-header-wrapper {
                position: fixed; top: 0; left: 0; right: 0; z-index: 1020;
                box-shadow: 0 4px 12px var(--shadow-color);
            }
            .header-top { 
                height: 70px; background: #ffffff; 
                display: flex; align-items: center; padding: 0 30px; justify-content: space-between;
                border-bottom: 1px solid var(--border-color); 
            }
            .header-bottom {
                background-color: var(--sidebar-bg); min-height: 50px;
                display: flex; align-items: center; padding: 0 30px;
            }
            
            .app-main { margin-left: 0; padding: 30px; padding-top: 150px; min-height: 100vh; }
            .mobile-bottom-nav { display: none !important; }
            .mobile-header-brand { display: none !important; }

            /* Desktop Navbar Styles */
            .desktop-nav { 
                list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: 5px; 
            }
            .desktop-nav > li { position: relative; }
            .desktop-nav > li > a {
                color: #E2E8F0; text-decoration: none; font-size: 13.5px; font-weight: 500;
                padding: 14px 16px; display: block; border-radius: 4px; transition: 0.2s;
            }
            .desktop-nav > li > a:hover, .desktop-nav > li > a.active {
                color: #ffffff; background-color: rgba(255,255,255,0.1);
            }
            .desktop-nav > li > a i { margin-right: 6px; color: var(--brand-primary); }
            
            /* Desktop Dropdown Styles */
            .desktop-dropdown {
                position: absolute; top: 100%; left: 0; background: #ffffff;
                min-width: 220px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                border-radius: 6px; border: 1px solid var(--border-color);
                opacity: 0; visibility: hidden; transform: translateY(10px);
                transition: all 0.2s ease; display: flex; flex-direction: column; padding: 8px 0;
            }
            .desktop-nav > li:hover .desktop-dropdown {
                opacity: 1; visibility: visible; transform: translateY(0);
            }
            .desktop-dropdown a {
                color: var(--text-main); text-decoration: none; padding: 10px 20px;
                font-size: 13px; font-weight: 500; transition: 0.2s; display: flex; align-items: center;
            }
            .desktop-dropdown a i { width: 20px; color: #718096; margin-right: 8px; }
            .desktop-dropdown a:hover { background-color: var(--bg-light); color: var(--brand-primary); }
            .desktop-dropdown a:hover i { color: var(--brand-primary); }
            .nav-section-title {
                font-size: 10px; text-transform: uppercase; color: #A0AEC0;
                padding: 10px 20px 5px; font-weight: 700; letter-spacing: 1px;
            }
        }

        /* ---------------- Mobile Layout (Untouched) ---------------- */
        @media (max-width: 767.98px) {
            .app-header-wrapper {
                position: fixed; top: 0; left: 0; right: 0; z-index: 1010;
            }
            .header-top { 
                height: 60px; background: #ffffff; border-bottom: 1px solid var(--border-color); 
                display: flex; align-items: center; padding: 0 15px; justify-content: space-between;
                box-shadow: 0 2px 10px var(--shadow-color);
            }
            .header-bottom { display: none !important; }
            .app-main { padding: 15px; padding-top: 80px; padding-bottom: 90px; min-height: 100vh; }
            
            .mobile-bottom-nav {
                display: flex; justify-content: space-between; align-items: center;
                position: fixed; bottom: 0; left: 0; right: 0; height: 65px;
                background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(10px);
                border-top: 1px solid var(--border-color); z-index: 1030;
                padding: 0 10px; padding-bottom: env(safe-area-inset-bottom);
                box-shadow: 0 -2px 10px var(--shadow-color);
            }
            .mobile-bottom-nav a { 
                text-align: center; color: #718096; text-decoration: none; font-size: 11px; 
                flex: 1; padding: 10px 0; font-weight: 500; transition: color 0.2s;
            }
            .mobile-bottom-nav a i { display: block; font-size: 20px; margin-bottom: 3px; }
            .mobile-bottom-nav a.active { color: var(--brand-primary); }
        }

        /* Mobile Offcanvas Sidebar Styles */
        .nav-label { 
            font-size: 11px; text-transform: uppercase; color: #A0AEC0; 
            letter-spacing: 1.5px; margin: 25px 24px 10px; font-weight: 600; 
        }
        .nav-item-custom { 
            color: #E2E8F0; padding: 12px 24px; text-decoration: none; display: flex; 
            align-items: center; justify-content: space-between; font-size: 14px; 
            font-weight: 500; transition: 0.2s; cursor: pointer;
        }
        .nav-item-custom .menu-icon { width: 24px; font-size: 16px; color: #A0AEC0; transition: 0.2s; }
        .nav-item-custom:hover, .nav-item-custom.active, .nav-item-custom[aria-expanded="true"] { 
            background: var(--sidebar-hover); color: white; border-right: 3px solid var(--brand-primary); 
        }
        .nav-item-custom:hover .menu-icon, .nav-item-custom.active .menu-icon, .nav-item-custom[aria-expanded="true"] .menu-icon { 
            color: var(--brand-primary); 
        }
        .nav-item-custom .dropdown-caret { font-size: 10px; transition: transform 0.3s; }
        .nav-item-custom[aria-expanded="true"] .dropdown-caret { transform: rotate(180deg); }
        .nav-sub-menu { background-color: rgba(255, 255, 255, 0.03); }
        .nav-item-sub {
            padding: 10px 24px 10px 48px; color: #CBD5E1; text-decoration: none; 
            display: flex; align-items: center; font-size: 13px; transition: 0.2s;
        }
        .nav-item-sub i { width: 20px; font-size: 12px; color: #94A3B8; }
        .nav-item-sub:hover, .nav-item-sub.active { color: #ffffff; background: rgba(255, 255, 255, 0.08); }
        .nav-item-sub:hover i, .nav-item-sub.active i { color: var(--brand-primary); }
        .sidebar-user-card { margin-top: auto; padding: 20px 24px; border-top: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.02); }
        .offcanvas-dark-custom { background-color: var(--sidebar-bg) !important; border-right: none; }
    </style>
</head>
<body>

    <header class="app-header-wrapper">
        <div class="header-top">
            <div class="d-flex align-items-center gap-3">
                <img src="{{asset('uploads/harihomes1-logo.png')}}" alt="JankiVilla" height="35">
            </div>

            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light rounded-circle border-0 text-secondary shadow-sm"><i class="fas fa-bell"></i></button>
                <div class="dropdown">
                    <a href="#" class="text-decoration-none d-flex align-items-center gap-2" data-bs-toggle="dropdown" style="color: var(--text-main);">
                        <img src="https://ui-avatars.com/api/?name=Admin&background=1A365D&color=fff" alt="User" class="rounded-circle" width="35" height="35">
                        <span class="d-none d-md-block fw-medium fs-6">Admin</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3">
                        <li><a class="dropdown-item py-2 fw-medium handle-logout" href="#" style="color: #E53E3E;"><i class="fas fa-sign-out-alt me-2"></i> Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="header-bottom">
            <ul class="desktop-nav">
                <li><a href="/admin/dashboard" class="{{ request()->is('admin/dashboard') ? 'active' : '' }}"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                
                <li>
                    <a href="#"><i class="fas fa-file-invoice"></i> Vouchers <i class="fas fa-chevron-down ms-1" style="font-size: 10px;"></i></a>
                    <div class="desktop-dropdown">
                        <a href="{{ url('/admin/debit_vouchers') }}"><i class="fas fa-arrow-circle-up"></i> Debit Vouchers</a>
                        <a href="{{ url('/admin/give-access') }}"><i class="fas fa-arrow-circle-down"></i> Receipt Vouchers</a>
                    </div>
                </li>

                <li>
                    <a href="#"><i class="fas fa-bullhorn"></i> CRM & Leads <i class="fas fa-chevron-down ms-1" style="font-size: 10px;"></i></a>
                    <div class="desktop-dropdown">
                        <div class="nav-section-title">Customers</div>
                        <a href="{{ url('/admin/interested-customers') }}"><i class="fas fa-user-plus"></i> Add Interested</a>
                        <a href="{{ url('/admin/customers') }}"><i class="fas fa-users"></i> Customer Details</a>
                        <a href="{{ url('/admin/give-access') }}"><i class="fas fa-user-shield"></i> Give Access</a>
                        <div class="nav-section-title mt-2">Leads</div>
                        <a href="#"><i class="fas fa-headset"></i> New Lead</a>
                        <a href="#"><i class="fas fa-database"></i> Client Database</a>
                    </div>
                </li>

                <li>
                    <a href="#"><i class="fas fa-network-wired"></i> Network <i class="fas fa-chevron-down ms-1" style="font-size: 10px;"></i></a>
                    <div class="desktop-dropdown">
                        <a href="/admin/members"><i class="fas fa-user-friends"></i> Member Details</a>
                        <a href="{{ url('/admin/member-designations') }}"><i class="fas fa-user-tag"></i> Member Designations</a>
                        <a href="/admin/agents"><i class="fas fa-briefcase"></i> Agent Details</a>
                        <a href="/admin/landowners"><i class="fas fa-map-marked-alt"></i> Landowner Details</a>
                        <a href="/admin/vendors"><i class="fas fa-store"></i> Vendor Details</a>
                    </div>
                </li>

                <li>
                    <a href="#"><i class="fas fa-user-tie"></i> HR & Admin <i class="fas fa-chevron-down ms-1" style="font-size: 10px;"></i></a>
                    <div class="desktop-dropdown">
                        <div class="nav-section-title">Staff Management</div>
                        <a href="/admin/employees"><i class="fas fa-id-card"></i> Employee Details</a>
                        <a href="{{ url('/admin/designations') }}"><i class="fas fa-user-tag"></i> Employee Designations</a>
                        <a href="{{ url('/admin/salaries') }}"><i class="fas fa-money-bill-wave"></i> Employee Salaries</a>
                        <div class="nav-section-title mt-2">Operations</div>
                        <a href="{{ url('admin/companies') }}"><i class="fas fa-industry"></i> Companies</a>
                        <a href="{{ url('admin/branches') }}"><i class="fas fa-building"></i> Branches</a>
                        <a href="/admin/ledgers"><i class="fas fa-book"></i> Ledgers</a>
                        <a href="/admin/letterheads"><i class="fas fa-file-signature"></i> LetterHeads</a>
                        <a href="/admin/id-cards"><i class="fas fa-id-badge"></i> ID Cards</a>
                    </div>
                </li>

                <li>
                    <a href="#"><i class="fas fa-wallet"></i> Financials <i class="fas fa-chevron-down ms-1" style="font-size: 10px;"></i></a>
                    <div class="desktop-dropdown">
                        <a href="#"><i class="fas fa-file-invoice-dollar"></i> Invoices</a>
                        <a href="#"><i class="fas fa-chart-line"></i> Expenses</a>
                    </div>
                </li>
            </ul>
        </div>
    </header>

<div class="offcanvas offcanvas-start offcanvas-dark-custom text-white" tabindex="-1" id="mobileSidebar">
    <div class="offcanvas-header border-bottom bg-white">
        <img src="{{asset('uploads/harihomes1-logo.png')}}" alt="JankiVilla" height="35">
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    
    <div class="offcanvas-body p-0 d-flex flex-column">
        <div class="flex-grow-1 overflow-auto py-2">
            
            <div class="nav-label">Main Menu</div>
            
            <a href="/admin/dashboard" class="nav-item-custom {{ request()->is('admin/dashboard') ? 'active' : '' }}">
                <div><i class="fas fa-tachometer-alt menu-icon"></i> Dashboard</div>
            </a>

            <a href="/admin/letterheads" class="nav-item-custom {{ request()->is('admin/letterheads*') ? 'active' : '' }}">
                <div><i class="fas fa-file-signature menu-icon"></i> LetterHead</div>
            </a>

            <a href="/admin/id-cards" class="nav-item-custom {{ request()->is('admin/id-cards*') ? 'active' : '' }}">
                <div><i class="fas fa-id-badge menu-icon"></i> ID Cards</div>
            </a>
            
            <a href="#interestedcustomersMenuMobile" data-bs-toggle="collapse" class="nav-item-custom" aria-expanded="false">
                <div><i class="fas fa-headset menu-icon"></i> Interested Customers</div>
                <i class="fas fa-chevron-down dropdown-caret"></i>
            </a>
            <div class="collapse nav-sub-menu" id="interestedcustomersMenuMobile">
                <a href="{{ url('/admin/interested-customers') }}" class="nav-item-sub"><i class="fas fa-user-plus me-2"></i> Add Interested Customer</a>
                <a href="{{ url('/admin/give-access') }}" class="nav-item-sub"><i class="fas fa-user-shield me-2"></i> Give Access</a>
            </div>

            <a href="#leadsMenuMobile" data-bs-toggle="collapse" class="nav-item-custom" aria-expanded="false">
                <div><i class="fas fa-bullhorn menu-icon"></i> Leads & Clients</div>
                <i class="fas fa-chevron-down dropdown-caret"></i>
            </a>
            <div class="collapse nav-sub-menu" id="leadsMenuMobile">
                <a href="#" class="nav-item-sub"><i class="fas fa-user-plus me-2"></i> New Lead</a>
                <a href="#" class="nav-item-sub"><i class="fas fa-users me-2"></i> Client Database</a>
            </div>
            
           <div class="nav-label">Financials & Vouchers</div>
            
            <a href="#vouchersMenuMobile" data-bs-toggle="collapse" class="nav-item-custom {{ request()->is('admin/debit_vouchers*') ? 'active' : '' }}" aria-expanded="{{ request()->is('admin/debit_vouchers*') ? 'true' : 'false' }}">
                <div><i class="fas fa-file-invoice menu-icon"></i> Vouchers</div>
                <i class="fas fa-chevron-down dropdown-caret"></i>
            </a>
            <div class="collapse nav-sub-menu {{ request()->is('admin/debit_vouchers*') ? 'show' : '' }}" id="vouchersMenuMobile">
                <a href="{{ url('/admin/debit_vouchers') }}" class="nav-item-sub {{ request()->is('admin/debit_vouchers*') ? 'active' : '' }}">
                    <i class="fas fa-arrow-circle-up me-2"></i> Debit Vouchers
                </a>
                <a href="#" class="nav-item-sub">
                    <i class="fas fa-arrow-circle-down me-2"></i> Receipt Vouchers
                </a>
            </div>

            <a href="#" class="nav-item-custom">
                <div><i class="fas fa-file-invoice-dollar menu-icon"></i> Invoices</div>
            </a>
            <a href="#" class="nav-item-custom">
                <div><i class="fas fa-chart-line menu-icon"></i> Expenses</div>
            </a>
            
            <div class="nav-label">Administration & CRM</div>
            
            <a href="{{ url('admin/companies') }}" class="nav-item-custom {{ request()->is('admin/companies*') ? 'active' : '' }}">
                <div><i class="fas fa-industry menu-icon"></i> Companies</div>
            </a>

            <a href="{{ url('admin/branches') }}" class="nav-item-custom {{ request()->is('admin/branches*') ? 'active' : '' }}">
                <div><i class="fas fa-building menu-icon"></i> Branches</div>
            </a>

            <a href="#ledgersMenuMobile" data-bs-toggle="collapse" class="nav-item-custom" aria-expanded="false">
                <div><i class="fas fa-book menu-icon"></i> Ledgers</div>
                <i class="fas fa-chevron-down dropdown-caret"></i>
            </a>
            <div class="collapse nav-sub-menu" id="ledgersMenuMobile">
                <a href="/admin/ledgers" class="nav-item-sub"><i class="fas fa-list-alt me-2"></i> Ledger Details</a>
            </div>

            <a href="#employeeMenuMobile" data-bs-toggle="collapse" class="nav-item-custom" aria-expanded="false">
                <div><i class="fas fa-user-tie menu-icon"></i> Employee</div>
                <i class="fas fa-chevron-down dropdown-caret"></i>
            </a>
            <div class="collapse nav-sub-menu" id="employeeMenuMobile">
                <a href="/admin/employees" class="nav-item-sub"><i class="fas fa-id-card me-2"></i> Employee Details</a>
                <a href="{{ url('/admin/designations') }}" class="nav-item-sub"><i class="fas fa-user-tag me-2"></i> Employee Designations</a>
                <a href="{{ url('/admin/salaries') }}" class="nav-item-sub"><i class="fas fa-money-bill-wave me-2"></i> Employee Salaries</a>
            </div>

            <a href="#customerMenuMobile" data-bs-toggle="collapse" class="nav-item-custom" aria-expanded="false">
                <div><i class="fas fa-users menu-icon"></i> Customer</div>
                <i class="fas fa-chevron-down dropdown-caret"></i>
            </a>
            <div class="collapse nav-sub-menu" id="customerMenuMobile">
                <a href="/admin/customers" class="nav-item-sub"><i class="fas fa-user me-2"></i> Customer Details</a>
            </div>

            <a href="#memberMenuMobile" data-bs-toggle="collapse" class="nav-item-custom" aria-expanded="false">
                <div><i class="fas fa-user-friends menu-icon"></i> Member</div>
                <i class="fas fa-chevron-down dropdown-caret"></i>
            </a>
            <div class="collapse nav-sub-menu" id="memberMenuMobile">
                <a href="/admin/members" class="nav-item-sub"><i class="fas fa-address-card me-2"></i> Member Details</a>
                <a href="{{ url('/admin/member-designations') }}" class="nav-item-sub"><i class="fas fa-user-tag me-2"></i> Member Designations</a>
            </div>

            <a href="#agentMenuMobile" data-bs-toggle="collapse" class="nav-item-custom" aria-expanded="false">
                <div><i class="fas fa-briefcase menu-icon"></i> Agent</div>
                <i class="fas fa-chevron-down dropdown-caret"></i>
            </a>
            <div class="collapse nav-sub-menu" id="agentMenuMobile">
                <a href="/admin/agents" class="nav-item-sub"><i class="fas fa-address-book me-2"></i> Agent Details</a>
            </div>

            <a href="#landownerMenuMobile" data-bs-toggle="collapse" class="nav-item-custom" aria-expanded="false">
                <div><i class="fas fa-map-marked-alt menu-icon"></i> Landowner</div>
                <i class="fas fa-chevron-down dropdown-caret"></i>
            </a>
            <div class="collapse nav-sub-menu" id="landownerMenuMobile">
                <a href="/admin/landowners" class="nav-item-sub"><i class="fas fa-file-signature me-2"></i> Landowner Details</a>
            </div>

            <a href="#vendorsMenuMobile" data-bs-toggle="collapse" class="nav-item-custom" aria-expanded="false">
                <div><i class="fas fa-store menu-icon"></i> Vendor</div>
                <i class="fas fa-chevron-down dropdown-caret"></i>
            </a>
            <div class="collapse nav-sub-menu" id="vendorsMenuMobile">
                <a href="/admin/vendors" class="nav-item-sub"><i class="fas fa-clipboard-list me-2"></i> Vendor Details</a>
            </div>
            
        </div>
        
        <div class="sidebar-user-card mt-auto">
            <div class="d-flex align-items-center mb-3">
                <img src="https://ui-avatars.com/api/?name=Admin&background=D69E2E&color=fff" class="rounded-circle me-3" width="40" height="40">
                <div>
                    <div class="fw-bold fs-6 text-white">Admin User</div>
                    <div class="small" style="color: #A0AEC0;">Master Access</div>
                </div>
            </div>
            <button class="btn w-100 handle-logout fw-medium" style="background-color: #E53E3E; color: white; border: none;">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </button>
        </div>
    </div>
</div>

    <main class="app-main">
        @yield('content')
    </main>

    <nav class="mobile-bottom-nav shadow-sm">
        <a href="/admin/dashboard" class="active"><i class="fas fa-layer-group"></i>Home</a>
        <a href="#"><i class="fas fa-building"></i>Assets</a>
        <a href="#"><i class="fas fa-file-invoice-dollar"></i>Finance</a>
        <a href="#" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar"><i class="fas fa-bars"></i>Menu</a>
    </nav>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <div class="modal fade" id="deviceManagerModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold" style="color: var(--sidebar-bg);"><i class="fas fa-laptop-house me-2 text-primary"></i> Active Sessions</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <span class="small fw-bold text-muted">You are logged in on these devices:</span>
                        <button class="btn btn-sm btn-danger fw-bold shadow-sm" id="btnLogoutAll"><i class="fas fa-power-off me-1"></i> Logout All Devices</button>
                    </div>
                    
                    <ul class="list-group list-group-flush" id="activeDevicesList">
                        <li class="list-group-item text-center p-4 text-muted">
                            <span class="spinner-border spinner-border-sm me-2"></span> Loading active devices...
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

   <script>
        $(document).ready(function() {
            const apiToken = localStorage.getItem('admin_token');

            if (apiToken) {
                const currentTokenId = apiToken.split('|')[0];

                $.ajax({
                    url: '/api/user',
                    type: 'GET',
                    headers: { 'Authorization': 'Bearer ' + apiToken },
                    success: function(user) {
                        const userId = user.id;
                        if (typeof window.Echo !== 'undefined') {
                            window.Echo.channel(`admin.logout.${userId}`)
                                .listen('.user.logged.out', (e) => {
                                    if (e.tokenId === null || e.tokenId == currentTokenId) {
                                        localStorage.removeItem('admin_token');
                                        window.location.href = '/admin/login';
                                    }
                                });
                        }
                    },
                    error: function() {
                        localStorage.removeItem('admin_token');
                        window.location.href = '/admin/login';
                    }
                });
            }

            $('.handle-logout').on('click', function(e) {
                e.preventDefault();
                $('#deviceManagerModal').modal('show');
                loadActiveSessions();
            });

            function loadActiveSessions() {
                $.ajax({
                    url: '/api/v1/admin/auth/sessions',
                    type: 'GET',
                    headers: { 'Authorization': 'Bearer ' + apiToken },
                    success: function(res) {
                        let html = '';
                        res.data.forEach(session => {
                            let badge = session.is_current ? `<span class="badge bg-success-subtle text-success border border-success-subtle ms-2">This Device</span>` : '';
                            let actionBtn = session.is_current 
                                ? `<button class="btn btn-sm btn-outline-danger fw-bold btn-logout-current"><i class="fas fa-sign-out-alt"></i> Logout</button>`
                                : `<button class="btn btn-sm btn-light text-danger fw-bold btn-logout-device" data-id="${session.id}"><i class="fas fa-times-circle"></i> Remove</button>`;

                            html += `
                                <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                                    <div>
                                        <h6 class="mb-1 fw-bold text-dark"><i class="fas fa-desktop me-2 text-secondary"></i>${session.name} ${badge}</h6>
                                        <small class="text-muted"><i class="far fa-clock me-1"></i> Last active: ${session.last_used}</small>
                                    </div>
                                    <div>${actionBtn}</div>
                                </li>
                            `;
                        });
                        $('#activeDevicesList').html(html);
                    }
                });
            }

            $(document).on('click', '.btn-logout-current', function() {
                let btn = $(this);
                btn.html('<span class="spinner-border spinner-border-sm"></span>');
                $.ajax({
                    url: '/api/v1/admin/auth/logout-current',
                    type: 'POST',
                    headers: { 'Authorization': 'Bearer ' + apiToken },
                    success: function() {
                        localStorage.removeItem('admin_token');
                        window.location.href = '/admin/login';
                    },
                    error: function() {
                        localStorage.removeItem('admin_token');
                        window.location.href = '/admin/login';
                    }
                });
            });

            $(document).on('click', '.btn-logout-device', function() {
                let btn = $(this);
                let id = btn.data('id');
                btn.html('<span class="spinner-border spinner-border-sm"></span>');
                $.ajax({
                    url: `/api/v1/admin/auth/logout-device/${id}`,
                    type: 'POST',
                    headers: { 'Authorization': 'Bearer ' + apiToken },
                    success: function() {
                        loadActiveSessions();
                    }
                });
            });

            $('#btnLogoutAll').on('click', function() {
                if(confirm("This will log you out from all mobile and laptop browsers. Continue?")) {
                    let btn = $(this);
                    btn.html('<span class="spinner-border spinner-border-sm"></span> Processing...');
                    $.ajax({
                        url: '/api/v1/admin/auth/logout-all',
                        type: 'POST',
                        headers: { 'Authorization': 'Bearer ' + apiToken },
                        success: function() {
                            localStorage.removeItem('admin_token');
                            window.location.href = '/admin/login';
                        },
                        error: function() {
                            localStorage.removeItem('admin_token');
                            window.location.href = '/admin/login';
                        }
                    });
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>