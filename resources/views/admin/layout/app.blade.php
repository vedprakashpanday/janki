<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JankiVilla Workspace</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --sidebar-bg: #0f172a; /* Deep Slate / Navy */
            --sidebar-hover: #1e293b;
            --brand-primary: #2563eb; /* Corporate Blue */
            --bg-light: #f8fafc;
            --border-color: #e2e8f0;
        }

        body { 
            background-color: var(--bg-light); 
            font-family: 'Inter', sans-serif; 
            color: #334155; 
        }

        /* ---------------- Desktop Layout ---------------- */
        @media (min-width: 768px) {
            .app-sidebar { 
                width: 260px; height: 100vh; position: fixed; top: 0; left: 0; 
                background-color: var(--sidebar-bg) !important; 
                z-index: 1020; display: flex; flex-direction: column;
            }
            .app-header { 
                height: 70px; background: #ffffff; position: fixed; top: 0; right: 0; left: 260px; 
                z-index: 1010; border-bottom: 1px solid var(--border-color); 
                display: flex; align-items: center; padding: 0 30px; justify-content: space-between;
            }
            .app-main { margin-left: 260px; padding: 30px; padding-top: 100px; min-height: 100vh; }
            .mobile-bottom-nav { display: none !important; }
            .mobile-header-brand { display: none !important; }
        }

        /* ---------------- Mobile Layout ---------------- */
        @media (max-width: 767.98px) {
            .app-sidebar { display: none !important; } /* Hide desktop sidebar completely */
            .app-header { 
                height: 60px; background: #ffffff; position: fixed; top: 0; right: 0; left: 0; 
                z-index: 1010; border-bottom: 1px solid var(--border-color); 
                display: flex; align-items: center; padding: 0 15px; justify-content: space-between;
            }
            .app-main { padding: 15px; padding-top: 80px; padding-bottom: 90px; min-height: 100vh; }
            
            /* Professional iOS Style Bottom Nav */
            .mobile-bottom-nav {
                display: flex; justify-content: space-between; align-items: center;
                position: fixed; bottom: 0; left: 0; right: 0; height: 65px;
                background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
                border-top: 1px solid var(--border-color); z-index: 1030;
                padding: 0 10px; padding-bottom: env(safe-area-inset-bottom);
            }
            .mobile-bottom-nav a { 
                text-align: center; color: #64748b; text-decoration: none; font-size: 11px; 
                flex: 1; padding: 10px 0; font-weight: 500; transition: color 0.2s;
            }
            .mobile-bottom-nav a i { display: block; font-size: 20px; margin-bottom: 3px; }
            .mobile-bottom-nav a.active { color: var(--brand-primary); }
        }

        /* ---------------- Shared Sidebar & Menu Styles ---------------- */
        .sidebar-brand { height: 70px; display: flex; align-items: center; padding: 0 24px; color: white; font-size: 20px; font-weight: 700; border-bottom: 1px solid #1e293b; }
        .nav-label { font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: 1px; margin: 20px 24px 10px; font-weight: 600; }
        .nav-item-custom { color: #cbd5e1; padding: 12px 24px; text-decoration: none; display: flex; align-items: center; font-size: 14px; font-weight: 500; transition: 0.2s; }
        .nav-item-custom i { width: 24px; font-size: 16px; color: #94a3b8; }
        .nav-item-custom:hover, .nav-item-custom.active { background: var(--sidebar-hover); color: white; border-right: 3px solid var(--brand-primary); }
        .nav-item-custom:hover i, .nav-item-custom.active i { color: var(--brand-primary); }
        
        .sidebar-user-card { margin-top: auto; padding: 20px 24px; border-top: 1px solid #1e293b; background: rgba(0,0,0,0.1); }
        
        /* Offcanvas dark override */
        .offcanvas-dark-custom { background-color: var(--sidebar-bg) !important; border-right: none; }
    </style>
</head>
<body>

    <header class="app-header">
        <div class="mobile-header-brand text-dark fw-bold fs-5">
            <i class="fas fa-building text-primary me-2"></i>JankiVilla
        </div>
        
        <div class="d-none d-md-block">
            <h5 class="m-0 fw-bold text-dark">Workspace</h5>
        </div>

        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light rounded-circle border-0 text-secondary"><i class="fas fa-bell"></i></button>
            <div class="dropdown">
                <a href="#" class="text-dark text-decoration-none d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=2563eb&color=fff" alt="User" class="rounded-circle" width="35" height="35">
                    <span class="d-none d-md-block fw-medium fs-6">Admin</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-3">
                    <li><a class="dropdown-item py-2 text-danger fw-medium handle-logout" href="#"><i class="fas fa-sign-out-alt me-2"></i> Sign Out</a></li>
                </ul>
            </div>
        </div>
    </header>

    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-building text-primary me-2"></i> JankiVilla
        </div>
        <div class="flex-grow-1 overflow-auto py-2">
            <div class="nav-label">Main Menu</div>
            <a href="/admin/dashboard" class="nav-item-custom active"><i class="fas fa-layer-group"></i> Dashboard</a>
            <a href="#" class="nav-item-custom"><i class="fas fa-users"></i> Staff Directory</a>
            
            <div class="nav-label">Finance</div>
            <a href="#" class="nav-item-custom"><i class="fas fa-wallet"></i> Revenue</a>
            <a href="#" class="nav-item-custom"><i class="fas fa-handshake"></i> Commissions</a>
        </div>
        <div class="sidebar-user-card">
            <button class="btn btn-outline-danger w-100 handle-logout btn-sm fw-medium">
                <i class="fas fa-power-off me-2"></i> Secure Logout
            </button>
        </div>
    </aside>

    <div class="offcanvas offcanvas-start offcanvas-dark-custom text-white" tabindex="-1" id="mobileSidebar">
        <div class="offcanvas-header border-bottom border-secondary border-opacity-25">
            <h5 class="offcanvas-title fw-bold"><i class="fas fa-building text-primary me-2"></i> JankiVilla</h5>
            <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0 d-flex flex-column">
            <div class="flex-grow-1 py-2">
                <div class="nav-label">Main Menu</div>
                <a href="/admin/dashboard" class="nav-item-custom active"><i class="fas fa-layer-group"></i> Dashboard</a>
                <a href="#" class="nav-item-custom"><i class="fas fa-users"></i> Staff Directory</a>
                <div class="nav-label">Finance</div>
                <a href="#" class="nav-item-custom"><i class="fas fa-wallet"></i> Revenue</a>
                <a href="#" class="nav-item-custom"><i class="fas fa-handshake"></i> Commissions</a>
            </div>
            <div class="sidebar-user-card mt-auto">
                <div class="d-flex align-items-center mb-3">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=2563eb&color=fff" class="rounded-circle me-3" width="40" height="40">
                    <div>
                        <div class="fw-bold fs-6">Admin User</div>
                        <div class="text-secondary small">Master Access</div>
                    </div>
                </div>
                <button class="btn btn-danger w-100 handle-logout fw-medium">
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
        <a href="#"><i class="fas fa-users"></i>Staff</a>
        <a href="#"><i class="fas fa-wallet"></i>Finance</a>
        <a href="#" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar"><i class="fas fa-bars"></i>Menu</a>
    </nav>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('.handle-logout').on('click', function(e) {
                e.preventDefault();
                const token = localStorage.getItem('admin_token');
                if(token) {
                    // Disable button during request
                    $(this).html('<i class="fas fa-spinner fa-spin"></i> Logging out...');
                    $.ajax({
                        url: '/api/v1/admin/auth/logout',
                        type: 'POST',
                        headers: { 'Authorization': 'Bearer ' + token },
                        complete: function() {
                            localStorage.removeItem('admin_token');
                            window.location.href = '/admin/login';
                        }
                    });
                } else {
                    window.location.href = '/admin/login';
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>