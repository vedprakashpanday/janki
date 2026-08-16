<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Amitabh Builders & Developers — Your Dream Our Planning')</title>
    <meta name="description" content="@yield('meta_description', 'Premium residential plots and luxury villas in Darbhanga.')">

    <link rel="shortcut icon" href="{{ asset('uploads/harihomes1-fevicon.png') }}" type="image/x-icon" id="dynamicFavicon">

    <!-- Premium Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,400&family=DM+Sans:wght@300;400;500;700&display=swap"
        rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ─── RESET & BASE ─────────────────────────────────────── */
        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        /* ─── CSS VARIABLES ────────────────────────────────────── */
        :root {
            --gold: #B8913A;
            --gold-light: #D4AC5A;
            --navy: #0D1B2A;
            --navy-mid: #1B2F42;
            --cream: #F5F2ED;
            --text: #1A1610;
            --text-mid: #4A4038;
            --text-muted: #8A7D6E;
            --serif: 'Cormorant Garamond', Georgia, serif;
            --sans: 'DM Sans', sans-serif;
        }

        body {
            font-family: var(--sans);
            background: var(--cream);
            color: var(--text);
            overflow-x: hidden;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--cream);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gold);
            border-radius: 3px;
        }

        /* ─── TOP BAR (Desktop) ────────────────────────────────── */
        .topbar {
            background: var(--navy);
            padding: 9px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            letter-spacing: 0.3px;
        }

        .topbar-left {
            color: rgba(255, 255, 255, 0.5);
        }

        .topbar-left span {
            color: var(--gold-light);
            font-style: italic;
        }

        .topbar-right {
            display: flex;
            gap: 24px;
        }

        .topbar-right a {
            color: rgba(255, 255, 255, 0.65);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }

        .topbar-right a:hover {
            color: var(--gold-light);
        }

        .topbar-right a svg {
            width: 13px;
            height: 13px;
            stroke: var(--gold);
            fill: none;
            stroke-width: 2;
        }

        /* ─── NAVBAR ───────────────────────────────────────────── */
        nav {
            background: rgba(245, 242, 237, 0.97);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 0 60px;
            height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(184, 145, 58, 0.15);
            box-shadow: 0 1px 20px rgba(0, 0, 0, 0.06);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            gap: 36px;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .nav-menu a {
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--text-mid);
            text-decoration: none;
            position: relative;
            padding-bottom: 3px;
            transition: 0.25s;
        }

        .nav-menu a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 1.5px;
            background: var(--gold);
            transition: width 0.3s ease;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: var(--text);
        }

        .nav-menu a:hover::after,
        .nav-menu a.active::after {
            width: 100%;
        }

        .nav-book {
            background: var(--navy);
            color: var(--gold-light) !important;
            padding: 11px 24px;
            border-radius: 6px;
            font-size: 11px !important;
            letter-spacing: 2px !important;
            text-transform: uppercase;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.25s !important;
        }

        .nav-book:hover {
            background: transparent !important;
            border: 1px solid var(--navy);
            color: var(--navy) !important;
        }

        .nav-book::after {
            display: none !important;
        }

        /* DROPDOWN MENU STYLES (Desktop) */
        .has-dropdown {
            position: relative;
            padding-bottom: 10px;
            margin-bottom: -10px;
        }

        .dropdown {
            position: absolute;
            top: 100%;
            left: -10px;
            background: #ffffff;
            min-width: 160px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-top: 2px solid var(--gold);
            border-radius: 0 0 4px 4px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(15px);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            padding: 10px 0;
            z-index: 1000;
            list-style: none;
        }

        .has-dropdown:hover .dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown a {
            padding: 12px 20px !important;
            font-size: 12.5px !important;
            text-transform: capitalize !important;
            letter-spacing: 0.5px !important;
            font-weight: 500 !important;
            color: var(--text-mid) !important;
            display: block;
            margin: 0;
        }

        .dropdown a::after {
            display: none !important;
        }

        .dropdown a:hover {
            color: var(--gold) !important;
            background: rgba(184, 145, 58, 0.05);
        }

        /* ─── MOBILE SPECIFIC HEADER (Call Now) ─── */
        .btn-call-mobile-header {
            display: none;
            background: var(--navy);
            color: var(--gold-light);
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-decoration: none;
            border: 1px solid var(--navy);
            transition: 0.2s;
        }

        .btn-call-mobile-header:hover {
            background: #1B2F42;
            color: #fff;
        }

        /* ─── FOOTER ───────────────────────────────────────────── */
        footer {
            background: #060D14;
            padding: 72px 60px 28px;
        }

        .footer-top {
            display: grid;
            grid-template-columns: 2.2fr 1fr 1fr 1.4fr 1.4fr;
            gap: 40px;
            padding-bottom: 48px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            margin-bottom: 28px;
        }

        .footer-about p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.3);
            line-height: 1.8;
            margin: 18px 0 26px;
            font-weight: 300;
            max-width: 400px;
        }

        .social-row {
            display: flex;
            gap: 8px;
        }

        .social-link {
            width: 34px;
            height: 34px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.4);
            font-size: 12px;
            transition: 0.2s;
        }

        .social-link:hover {
            border-color: var(--gold);
            color: var(--gold);
        }

        .footer-col h4 {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 22px;
        }

        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 0;
        }

        .footer-links a {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.3);
            text-decoration: none;
            font-weight: 300;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-links a::before {
            content: '';
            width: 12px;
            height: 1px;
            background: rgba(184, 145, 58, 0.3);
            flex-shrink: 0;
            transition: 0.2s;
        }

        .footer-links a:hover {
            color: rgba(255, 255, 255, 0.7);
        }

        .footer-links a:hover::before {
            background: var(--gold);
            width: 18px;
        }

        .footer-contact-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 0;
        }

        .footer-contact-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.3);
            line-height: 1.5;
        }

        .footer-contact-list li svg {
            width: 14px;
            height: 14px;
            stroke: var(--gold);
            fill: none;
            stroke-width: 2;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-bottom p {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.2);
            margin: 0;
        }

        .footer-bottom a {
            color: var(--gold);
            text-decoration: none;
        }

        /* ================= MOBILE BOTTOM NAV (HANDY NAVBAR) ================= */
        .mobile-bottom-bar {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            z-index: 1040;
            justify-content: space-around;
            align-items: center;
            padding: 12px 0 18px 0;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.05);
        }

        .mobile-nav-item {
            background: transparent;
            border: none;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 11px;
            font-weight: 500;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
            cursor: pointer;
        }

        .mobile-nav-item i {
            font-size: 20px;
        }

        .mobile-nav-item.active,
        .mobile-nav-item:hover {
            color: var(--gold);
        }

        /* ================= MOBILE FULL SCREEN MENU DRAWER ================= */
        .mobile-menu-drawer {
            position: fixed;
            top: 100%;
            left: 0;
            width: 100%;
            height: 100%;
            background: #ffffff;
            z-index: 1035;
            transition: top 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            padding-bottom: 80px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .mobile-menu-drawer.active {
            top: 0;
        }

        .mm-header {
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(184, 145, 58, 0.15);
            background: var(--cream);
        }

        .mm-close {
            background: rgba(184, 145, 58, 0.15);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            color: var(--navy);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .mm-body {
            padding: 20px 24px;
            overflow-y: auto;
            flex: 1;
        }

        .mm-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .mm-list li a {
            display: flex;
            align-items: center;
            padding: 18px 0;
            font-size: 15px;
            font-weight: 700;
            color: var(--navy);
            text-decoration: none;
            border-bottom: 1px solid #f5f2ed;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .mm-list li a i {
            color: var(--gold);
            width: 30px;
            font-size: 18px;
        }

        .mm-list li a:hover {
            color: var(--gold);
        }

        .mm-sub-list {
            list-style: none;
            padding: 10px 0 10px 30px;
            display: none;
        }

        .mm-list li.open .mm-sub-list {
            display: block;
        }

        .mm-sub-list a {
            padding: 12px 0 !important;
            font-size: 13px !important;
            color: var(--text-mid) !important;
            border-bottom: none !important;
            font-weight: 500 !important;
        }

        /* ================= FLOATING ACTIONS & NEHA AI ================= */
        .floating-actions {
            position: fixed;
            bottom: 30px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            z-index: 1050;
        }

        .float-btn {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            color: var(--navy);
            cursor: pointer;
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transition: 0.3s;
            text-decoration: none;
        }

        .float-btn:hover {
            transform: scale(1.1);
            color: #fff;
        }

        .btn-whatsapp {
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
        }

        .btn-neha {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: var(--navy);
            border: 2px solid var(--navy);
        }

        /* NEHA CHAT WINDOW */
        .neha-window {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 350px;
            height: 480px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            display: none;
            flex-direction: column;
            z-index: 1060;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .neha-header {
            background: var(--navy);
            color: var(--gold-light);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid var(--gold);
        }

        .neha-body {
            flex: 1;
            padding: 20px;
            background: var(--cream);
            overflow-y: auto;
            color: #333;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .msg-ai {
            background: #fff;
            border-left: 4px solid var(--gold);
            padding: 12px;
            border-radius: 0 12px 12px 12px;
            font-size: 0.9rem;
            align-self: flex-start;
            max-width: 85%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .msg-user {
            background: var(--navy);
            color: var(--gold-light);
            padding: 12px;
            border-radius: 12px 0 12px 12px;
            font-size: 0.9rem;
            align-self: flex-end;
            max-width: 85%;
        }

        .neha-footer {
            padding: 15px;
            background: #fff;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }

        .neha-input {
            flex: 1;
            border: 1px solid #ddd;
            padding: 10px 15px;
            border-radius: 30px;
            outline: none;
            font-size: 0.9rem;
            font-family: var(--sans);
        }

        .neha-send {
            background: var(--gold);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: var(--navy);
            cursor: pointer;
            transition: 0.2s;
        }

        .typing-indicator {
            font-style: italic;
            color: #888;
            font-size: 0.8rem;
            padding: 8px 12px !important;
        }

        .neha-lead-form {
            background: #fff;
            border: 1px solid #e5e7eb;
            padding: 15px;
            border-radius: 12px;
            margin-top: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        }

        .neha-lead-form input {
            font-size: 0.85rem;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            padding: 10px;
            margin-bottom: 10px;
            width: 100%;
            outline: none;
            font-family: var(--sans);
        }

        .neha-lead-form input:focus {
            border-color: var(--gold);
        }

        /* RESPONSIVE SETUP */
        @media (max-width: 1200px) {
            .footer-top {
                grid-template-columns: 2fr 1fr 1fr 2fr !important;
            }
        }

        @media (max-width: 1024px) {

            nav,
            .topbar,
            footer {
                padding-left: 30px;
                padding-right: 30px;
            }

            .footer-top {
                grid-template-columns: 1fr 1fr 1fr !important;
                gap: 40px;
            }

            /* Hide Desktop Nav Elements */
            .nav-menu,
            .nav-book {
                display: none !important;
            }

            /* Show Mobile Call Button in Header */
            .btn-call-mobile-header {
                display: inline-flex;
                align-items: center;
            }

            /* Show Handy Bottom Bar */
            .mobile-bottom-bar {
                display: flex;
            }
        }

        @media (max-width: 768px) {
            .topbar { display: none !important; }

            /* .topbar-right {
                justify-content: center;
                flex-wrap: wrap;
            } */

            .footer-top {
                grid-template-columns: 1fr 1fr !important;
            }

            body {
                padding-bottom: 85px;
            }

            /* Space for bottom nav */
            .floating-actions {
                bottom: 95px;
                right: 15px;
            }

            .neha-window {
                width: calc(100% - 30px);
                right: 15px;
                bottom: 165px;
                height: 450px;
            }
        }

        @media (max-width: 600px) {

            nav,
            .topbar,
            footer {
                padding-left: 20px;
                padding-right: 20px;
            }

            .footer-top {
                grid-template-columns: 1fr !important;
                gap: 40px;
            }

            .footer-bottom {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }

            footer {
                padding: 60px 20px 30px;
            }
        }
    </style>
    @stack('styles')
</head>

<body>

    <!-- Top Bar -->
    <div class="topbar">
        <div class="topbar-left">Welcome to <span>Amitabh Builders & Developers</span> — Your Dream Our Planning</div>
        <div class="topbar-right">
            <a href="mailto:abdeveloperspl@gmail.com">
                <svg viewBox="0 0 24 24">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                    <polyline points="22,6 12,13 2,6" />
                </svg>
                abdeveloperspl@gmail.com
            </a>
            <a href="tel:+919472467007">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.37 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 6.29 6.29l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
                </svg>
                +91 94724 67007
            </a>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav>
        <a href="/" class="logo">
            <img src="{{ asset('uploads/harihomes1-logo.png') }}" alt="Amitabh Builders Logo"
                style="max-height: 50px; width: auto;">
        </a>

        <!-- Mobile: Call Now replacing the Burger Menu in Header -->
        <a href="tel:+919472467007" class="btn-call-mobile-header d-lg-none">
            <i class="fas fa-phone-alt me-2"></i> Call Now
        </a>

        <!-- Desktop Navigation -->
        <ul class="nav-menu">
            <li><a href="/" class="active">Home</a></li>
            <li class="has-dropdown">
                <a href="#">About</a>
                <ul class="dropdown">
                    <li><a href="{{ route('about.company') ?? '#' }}">About Company</a></li>
                    <li><a href="{{ route('about.director') ?? '#' }}">About Director</a></li>
                </ul>
            </li>
            <li><a href="/#properties">Inventory</a></li>
            <li class="has-dropdown">
                <a href="#">Projects</a>
                <ul class="dropdown">
                    <li><a href="#">Phase 1</a></li>
                    <li><a href="#">Phase 2</a></li>
                </ul>
            </li>
            <li><a href="#contact">Contact</a></li>
        </ul>
        <a href="tel:+919472467007" class="nav-book">Book Now</a>
    </nav>

    <!-- Main Content Yield -->
    <main>
        @yield('content')
    </main>

    <!-- Premium Footer -->
    <footer>
        <div class="footer-top">
            <div class="footer-about">
                <a href="/" class="logo" style="display:inline-block; margin-bottom:10px;">
                    <img src="{{ asset('uploads/harihomes1-logo.png') }}" alt="AB Developers"
                        style="max-height: 45px; width: auto; filter: brightness(0) invert(1);">
                </a>
                <p>A leading developer in Bihar delivering high-quality, sustainable, and innovative building solutions
                    for modern families across Darbhanga and Madhubani.</p>
                <div class="social-row">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                </div>
            </div>

            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="/">Home</a></li>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#properties">Projects</a></li>
                    <li><a href="#">Media & Gallery</a></li>
                    <li><a href="#contact">Contact Us</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Legal</h4>
                <ul class="footer-links">
                    <li><a href="#">Terms & Conditions</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Return Policy</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Contact</h4>
                <ul class="footer-contact-list">
                    <li>
                        <svg viewBox="0 0 24 24" style="margin-top: 3px;">
                            <path
                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.37 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 6.29 6.29l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
                        </svg>
                        <div>
                            <span
                                style="color: #fff; font-size: 13px; font-weight: 500; display: block; margin-bottom: 2px;">+91
                                94724 67007</span>
                            <span
                                style="font-size: 10px; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.5px;">Mon-Sat
                                · 9 AM – 7 PM</span>
                        </div>
                    </li>
                    <li style="margin-top: 12px;">
                        <svg viewBox="0 0 24 24" style="margin-top: 3px;">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                            <polyline points="22,6 12,13 2,6" />
                        </svg>
                        <div>
                            <span
                                style="color: #fff; font-size: 13px; font-weight: 500; display: block; margin-bottom: 4px;">abdeveloperspl@gmail.com</span>
                            <span
                                style="font-size: 10px; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.5px;">We
                                reply within 24 hours</span>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Head Office</h4>
                <ul class="footer-contact-list">
                    <li>
                        <svg viewBox="0 0 24 24" style="margin-top: 3px;">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                            <circle cx="12" cy="10" r="3" />
                        </svg>
                        <div>
                            <span
                                style="font-size: 12px; color: rgba(255,255,255,0.5); line-height: 1.6; display: block;">1st
                                Floor, Pappu Yadav Building,<br>South of NH-27, Kakarghati Chowk,<br>Bhuskaul, Darbhanga
                                — 846007</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2026 Amitabh Builders & Developers Pvt. Ltd. All Rights Reserved.</p>
           
        </div>
    </footer>

    <!-- ================= MOBILE HANDY BOTTOM NAV ================= -->
    <div class="mobile-bottom-bar">
        <a href="/" class="mobile-nav-item active"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="{{ route('about.company') ?? '#' }}" class="mobile-nav-item"><i
                class="fas fa-info-circle"></i><span>About</span></a>
        <a href="/#properties" class="mobile-nav-item"><i class="fas fa-building"></i><span>Projects</span></a>
        <button class="mobile-nav-item" onclick="toggleMenuDrawer()">
            <i class="fas fa-bars"></i><span>Menu</span>
        </button>
    </div>

    <!-- ================= MOBILE FULL SCREEN MENU DRAWER ================= -->
    <div class="mobile-menu-drawer" id="menuDrawer">
        <div class="mm-header">
            <img src="{{ asset('uploads/harihomes1-logo.png') }}" alt="Logo" style="max-height: 40px;">
            <button class="mm-close" onclick="toggleMenuDrawer()"><i class="fas fa-times"></i></button>
        </div>
        <div class="mm-body">
            <ul class="mm-list">
                <li><a href="/" onclick="toggleMenuDrawer()"><i class="fas fa-home"></i> Home</a></li>
                <li onclick="toggleSubMenu(this)">
                    <a href="javascript:void(0)"><i class="fas fa-info-circle"></i> About <i
                            class="fas fa-angle-down ms-auto fs-6"></i></a>
                    <ul class="mm-sub-list">
                        <li><a href="{{ route('about.company') ?? '#' }}">- About Company</a></li>
                        <li><a href="{{ route('about.director') ?? '#' }}">- About Director</a></li>
                    </ul>
                </li>
                <li><a href="/#properties" onclick="toggleMenuDrawer()"><i class="fas fa-city"></i> Inventory</a>
                </li>
                <li onclick="toggleSubMenu(this)">
                    <a href="javascript:void(0)"><i class="fas fa-building"></i> Projects <i
                            class="fas fa-angle-down ms-auto fs-6"></i></a>
                    <ul class="mm-sub-list">
                        <li><a href="#">- Phase 1</a></li>
                        <li><a href="#">- Phase 2</a></li>
                    </ul>
                </li>
                <li><a href="#contact" onclick="toggleMenuDrawer()"><i class="fas fa-phone-alt"></i> Contact Us</a>
                </li>
            </ul>
            <div class="mt-5 text-center">
                <p class="text-muted small mb-2">Connect with us</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="#" class="btn btn-outline-dark rounded-circle"
                        style="width: 40px; height: 40px; padding: 8px;"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="btn btn-outline-dark rounded-circle"
                        style="width: 40px; height: 40px; padding: 8px;"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="btn btn-outline-dark rounded-circle"
                        style="width: 40px; height: 40px; padding: 8px;"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Actions (WhatsApp & Janki AI) -->
    <div class="floating-actions">
        <button class="float-btn btn-neha" onclick="toggleNeha()" title="Chat with Janki AI">
            <i class="fas fa-robot"></i>
        </button>
        <a href="https://wa.me/919472467007" target="_blank" class="float-btn btn-whatsapp" title="WhatsApp Us">
            <i class="fab fa-whatsapp"></i>
        </a>
    </div>

    <!-- Chatbot Window -->
    <div class="neha-window" id="nehaChat">
        <div class="neha-header">
            <div>
                <h6 class="m-0 fw-bold" style="font-family: var(--sans);"><i class="fas fa-robot me-2"></i> Janki AI
                </h6>
                <small class="opacity-75" style="font-size: 11px; color: var(--cream);">Online</small>
            </div>
            <button class="btn btn-link p-0 fs-5" style="color: var(--gold-light);" onclick="toggleNeha()"><i
                    class="fas fa-times"></i></button>
        </div>
        <div class="neha-body" id="chatBody"></div>
        <div class="neha-footer">
            <input type="text" class="neha-input" id="chatInput" placeholder="Type your message...">
            <button class="neha-send" id="sendBtn" onclick="sendMessage()"><i
                    class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Mobile Drawer Logic -->
    <script>
        function toggleMenuDrawer() {
            const drawer = document.getElementById('menuDrawer');
            drawer.classList.toggle('active');
        }

        function toggleSubMenu(element) {
            element.classList.toggle('open');
        }
    </script>

    <!-- Neha AI Chatbot Logic -->
    <script>
        let currentLeadId = null;
        let isFirstTime = true;

        function toggleNeha() {
            const chat = document.getElementById('nehaChat');
            chat.style.display = chat.style.display === 'flex' ? 'none' : 'flex';

            if (chat.style.display === 'flex') {
                if (isFirstTime && !currentLeadId) {
                    isFirstTime = false;
                    $('.neha-footer').hide();

                    setTimeout(() => {
                        showTyping();
                        setTimeout(() => {
                            hideTyping();
                            let formHtml = `
                                <div class="msg-ai mb-2">Namaste! 👋 I am Janki, your AI assistant for Amitabh Builders.</div>
                                <div class="neha-lead-form" id="leadFormBox">
                                    <p class="mb-2 fw-bold" style="font-size: 13px; color: var(--navy);">Aage badhne se pehle kripya details bharein:</p>
                                    <input type="text" id="leadName" placeholder="Aapka Naam (Your Name)" required>
                                    <input type="tel" id="leadMobile" placeholder="10-Digit Mobile No." maxlength="10" required>
                                    <button class="btn w-100 fw-bold shadow-sm" style="background: var(--navy); color: var(--gold-light); border-radius: 6px; letter-spacing: 1px;" onclick="submitLeadForm()">START CHAT <i class="fas fa-paper-plane ms-1"></i></button>
                                </div>
                            `;
                            $('#chatBody').append(formHtml);
                            scrollToBottom();
                        }, 800);
                    }, 500);
                } else if (currentLeadId) {
                    document.getElementById('chatInput').focus();
                }
            }
        }

        function submitLeadForm() {
            let name = $('#leadName').val().trim();
            let mobile = $('#leadMobile').val().trim();

            if (name === '') {
                alert("Kripya apna naam darj karein.");
                return;
            }
            if (!/^[0-9]{10}$/.test(mobile)) {
                alert("Kripya sahi 10-digit mobile number darj karein.");
                return;
            }

            $('#leadFormBox button').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Please wait...');

            $.post('/api/v1/chat/capture-lead', {
                name: name,
                mobile: mobile
            }, function(res) {
                if (res.status === 'success') {
                    currentLeadId = res.lead_id;
                    $('#leadFormBox').remove();
                    $('.neha-footer').css('display', 'flex');
                    appendAiMessage(res.reply);
                    setTimeout(() => {
                        document.getElementById('chatInput').focus();
                    }, 100);
                }
            }).fail(function() {
                $('#leadFormBox button').prop('disabled', false).html(
                    'START CHAT <i class="fas fa-paper-plane ms-1"></i>');
                alert("Kuch error aayi hai. Kripya thodi der baad try karein.");
            });
        }

        function appendUserMessage(msg) {
            $('#chatBody').append(`<div class="msg-user">${msg}</div>`);
            scrollToBottom();
        }

        function appendAiMessage(msg) {
            let formattedMsg = msg.replace(/\n/g, '<br>');
            $('#chatBody').append(`<div class="msg-ai">${formattedMsg}</div>`);
            scrollToBottom();
        }

        function showTyping() {
            $('#chatBody').append(`<div class="msg-ai typing-indicator"><i>Janki is typing...</i></div>`);
            scrollToBottom();
        }

        function hideTyping() {
            $('.typing-indicator').remove();
        }

        function scrollToBottom() {
            let body = document.getElementById('chatBody');
            body.scrollTop = body.scrollHeight;
        }

        function sendMessage() {
            const input = $('#chatInput');
            const msg = input.val().trim();

            if (msg === '' || !currentLeadId) return;

            appendUserMessage(msg);
            input.val('');
            showTyping();

            $.post('/api/v1/chat/send-message', {
                message: msg,
                lead_id: currentLeadId
            }, function(res) {
                hideTyping();
                if (res.status === 'success') {
                    appendAiMessage(res.reply);
                }
            }).fail(function() {
                hideTyping();
                appendAiMessage("Main abhi connect nahi kar paa rahi hun. Kripya apna sawal wapas bhejein.");
            });
        }

        $('#chatInput').on('keypress', function(e) {
            if (e.key === 'Enter') sendMessage();
        });
    </script>

    @stack('scripts')
</body>

</html>
