<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>JankiVilla - User Panel</title>
     <link rel="shortcut icon" href="{{asset('uploads/harihomes1-fevicon.png')}}" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f8f9fa; padding-bottom: 70px; /* Space for bottom nav */ }
        
        /* --- Desktop Topbar --- */
        .top-header { background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        
        /* --- Mobile Bottom Navigation (App-like feel) --- */
        .bottom-nav {
            display: none; /* Hidden on desktop */
        }

        @media (max-width: 768px) {
            body { padding-bottom: 80px; } /* Extra padding for mobile */
            .desktop-sidebar { display: none; } /* Hide sidebar on mobile */
            
            .bottom-nav {
                display: flex;
                position: fixed;
                bottom: 0;
                width: 100%;
                background: #ffffff;
                box-shadow: 0 -4px 12px rgba(0,0,0,0.08);
                justify-content: space-around;
                align-items: center;
                padding: 10px 0;
                z-index: 1000;
                border-top-left-radius: 15px;
                border-top-right-radius: 15px;
            }
            .bottom-nav a {
                text-align: center;
                color: #6c757d;
                text-decoration: none;
                font-size: 12px;
                flex: 1;
            }
            .bottom-nav a i {
                display: block;
                font-size: 20px;
                margin-bottom: 4px;
            }
            .bottom-nav a.active { color: #0d6efd; /* Primary color */ }
        }
    </style>
</head>
<body>

    <div class="top-header">
        <div class="logo fw-bold fs-4 text-primary">JankiVilla</div>
        <div class="user-actions">
            <i class="fas fa-bell me-3"></i>
            <span class="fw-bold">{{ Auth::user()->name ?? 'User' }}</span>
        </div>
    </div>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-2 desktop-sidebar">
                <div class="list-group">
                    <a href="#" class="list-group-item list-group-item-action active"><i class="fas fa-home me-2"></i> Home</a>
                    <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-heart me-2"></i> Saved</a>
                    <a href="#" class="list-group-item list-group-item-action"><i class="fas fa-user me-2"></i> Profile</a>
                </div>
            </div>

            <div class="col-md-10">
                @yield('content')
            </div>
        </div>
    </div>

    <div class="bottom-nav">
        <a href="#" class="active"><i class="fas fa-home"></i>Home</a>
        <a href="#"><i class="fas fa-search"></i>Search</a>
        <a href="#"><i class="fas fa-heart"></i>Saved</a>
        <a href="#"><i class="fas fa-user"></i>Profile</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>