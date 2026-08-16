@extends('layout.frontend-app')

@section('title', 'Amitabh Builders & Developers — Your Dream Our Planning')

@push('styles')
<!-- Premium Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,400&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet" />

<style>
    /* ─── CSS VARIABLES ────────────────────────────────────── */
    :root {
        --gold: #B8913A;
        --gold-light: #D4AC5A;
        --gold-pale: #F0E4C8;
        --navy: #0D1B2A;
        --navy-mid: #1B2F42;
        --cream: #F5F2ED;
        --cream-dark: #EDE8DF;
        --text: #1A1610;
        --text-mid: #4A4038;
        --text-muted: #8A7D6E;
        --serif: 'Cormorant Garamond', Georgia, serif;
        --sans: 'DM Sans', sans-serif;
    }

    /* Prevent X-Axis Overflow universally */
    html, body {
        width: 100%;
        max-width: 100vw;
        overflow-x: hidden;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    *, *::before, *::after { box-sizing: inherit; }

    body {
        font-family: var(--sans);
        background: var(--cream);
        color: var(--text);
    }

    /* ── HERO CINEMATIC (FIXED ALIGNMENT) ───────────────── */
    .hero-new {
        min-height: 100vh;
        position: relative;
        display: flex;
        align-items: center; /* Centers content vertically to prevent header overlap */
        justify-content: center;
        overflow: hidden;
        margin-top: -75px; 
        padding-top: 75px; /* Adds safe space below header */
    }
    .hero-bg-slider { position: absolute; inset: 0; z-index: 0; }
    .hero-slide {
        position: absolute; inset: 0; background-size: cover; background-position: center;
        opacity: 0; transition: opacity 1.2s ease;
    }
    .hero-slide.active { opacity: 1; }
    .hero-slide:nth-child(1) { background-image: url('https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=1600&q=80'); }
    .hero-slide:nth-child(2) { background-image: url('https://images.unsplash.com/photo-1582407947304-fd86f028f716?w=1600&q=80'); }
    .hero-slide:nth-child(3) { background-image: url('https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1600&q=80'); }
    .hero-slide:nth-child(4) { background-image: url('https://images.unsplash.com/photo-1570129477492-45c003edd2be?w=1600&q=80'); }
    
    .hero-overlay {
        position: absolute; inset: 0; z-index: 1;
        background: linear-gradient(160deg, rgba(13, 27, 42, 0.85) 0%, rgba(13, 27, 42, 0.65) 45%, rgba(13, 27, 42, 0.92) 100%);
    }
    .hero-overlay::after {
        content: ''; position: absolute; inset: 0; pointer-events: none;
        background-image: linear-gradient(rgba(184, 145, 58, 0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(184, 145, 58, 0.06) 1px, transparent 1px);
        background-size: 80px 80px;
    }

    .hero-inner {
        position: relative; z-index: 2; padding: 40px 60px;
        display: grid; grid-template-columns: 1fr 420px; gap: 60px; align-items: center; /* Keep text and box aligned */
        max-width: 1400px; margin: 0 auto; width: 100%;
    }
    
    .hero-text { padding-bottom: 10px; }
    .hero-eyebrow-new { display: flex; align-items: center; gap: 14px; margin-bottom: 24px; animation: slideRight 0.8s ease both; }
    .hero-eyebrow-new .line { width: 40px; height: 1.5px; background: var(--gold); }
    .hero-eyebrow-new span { font-size: 11px; font-weight: 600; letter-spacing: 3px; text-transform: uppercase; color: var(--gold-light); }
    .hero-h1 {
        font-family: var(--serif); font-size: 72px; font-weight: 400; color: #fff; line-height: 1.05;
        letter-spacing: -1px; margin-bottom: 24px; animation: slideRight 0.8s ease 0.1s both;
    }
    .hero-h1 em { color: var(--gold-light); font-style: italic; }
    .hero-desc-new {
        font-size: 15px; color: rgba(255, 255, 255, 0.65); line-height: 1.8; max-width: 520px;
        margin-bottom: 38px; animation: slideRight 0.8s ease 0.2s both;
    }
    .hero-actions-new { display: flex; gap: 16px; flex-wrap: wrap; animation: slideRight 0.8s ease 0.3s both; }
    
    .btn-primary-hero {
        display: inline-flex; align-items: center; gap: 10px; background: var(--gold); color: var(--navy);
        padding: 16px 34px; border-radius: 4px; font-size: 12px; font-weight: 700; letter-spacing: 2px;
        text-transform: uppercase; text-decoration: none; transition: all 0.25s; border: 2px solid var(--gold); cursor: pointer;
    }
    .btn-primary-hero:hover { background: transparent; color: var(--gold); }
    .btn-ghost-hero {
        display: inline-flex; align-items: center; gap: 10px; background: transparent; color: rgba(255, 255, 255, 0.8);
        padding: 16px 34px; border-radius: 4px; font-size: 12px; font-weight: 600; letter-spacing: 2px;
        text-transform: uppercase; text-decoration: none; border: 2px solid rgba(255, 255, 255, 0.25); transition: all 0.25s; cursor: pointer;
    }
    .btn-ghost-hero:hover { border-color: var(--gold); color: var(--gold); }

    .hero-stats-bar {
        display: flex; gap: 40px; margin-top: 52px; padding-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.1);
        animation: slideRight 0.8s ease 0.4s both;
    }
    .hstat-num { font-family: var(--serif); font-size: 38px; color: var(--gold-light); line-height: 1; font-weight: 600; }
    .hstat-label { font-size: 11px; color: rgba(255, 255, 255, 0.45); letter-spacing: 1px; text-transform: uppercase; margin-top: 4px; display: block; }

    /* Right Panel - Search */
    .hero-panel-new { animation: slideUp 0.8s ease 0.3s both; }
    .hero-search-card { background: rgba(255, 255, 255, 0.97); border-radius: 12px; padding: 32px; box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4); margin-bottom: 20px; width: 100%; }
    .hsc-title { font-family: var(--serif); font-size: 20px; color: var(--navy); margin-bottom: 24px; font-weight: 600; }
    .hsc-tabs { display: flex; background: #f5f2ed; border-radius: 6px; padding: 4px; margin-bottom: 24px; width: 100%; }
    .hsc-tab { flex: 1; padding: 9px 0; text-align: center; font-size: 12px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: var(--text-muted); border-radius: 4px; border: none; background: none; transition: 0.2s; }
    .hsc-tab.active { background: var(--navy); color: var(--gold-light); }
    .hsc-field label { display: block; font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; }
    .hsc-field select { width: 100%; padding: 13px 16px; border: 1.5px solid #e8e2d8; border-radius: 6px; font-family: var(--sans); font-size: 13px; color: var(--navy); background: #fafafa; outline: none; margin-bottom: 14px; }
    .hsc-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .btn-search-card { width: 100%; background: var(--gold); color: var(--navy); border: none; padding: 15px; border-radius: 6px; font-size: 12px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 6px; }
    
    .hero-avail-tag { background: rgba(255, 255, 255, 0.12); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 10px; padding: 16px 20px; display: flex; align-items: center; gap: 14px; width: 100%; }
    .avail-dot { width: 10px; height: 10px; border-radius: 50%; background: #27AE60; box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.25); animation: pulse 2s ease infinite; flex-shrink: 0; }
    
    .hero-slide-dots { position: absolute; bottom: 32px; left: 60px; z-index: 3; display: flex; gap: 8px; }
    .slide-dot { width: 24px; height: 3px; background: rgba(255, 255, 255, 0.3); border-radius: 2px; cursor: pointer; transition: 0.3s; }
    .slide-dot.active { background: var(--gold); width: 40px; }

    /* ── TRUST BAR ──────────────────────────────────────── */
    .trust-bar { background: var(--gold); padding: 0 60px; display: flex; justify-content: space-between; align-items: stretch; overflow: hidden; }
    .trust-item-new { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 20px 0; flex: 1; font-size: 12px; font-weight: 700; color: var(--navy); letter-spacing: 1px; text-transform: uppercase; position: relative; text-align: center; }
    .trust-item-new:not(:last-child)::after { content: ''; position: absolute; right: 0; top: 20%; height: 60%; width: 1px; background: rgba(13, 27, 42, 0.15); }

    /* ── STATS SECTION ──────────────────────────────────── */
    .stats-section { background: var(--navy); padding: 80px 60px; display: grid; grid-template-columns: repeat(4, 1fr); }
    .stat-block { text-align: center; padding: 30px 20px; position: relative; }
    .stat-block:not(:last-child)::after { content: ''; position: absolute; right: 0; top: 20%; height: 60%; width: 1px; background: rgba(255, 255, 255, 0.08); }
    .stat-big { font-family: var(--serif); font-size: 60px; color: var(--gold-light); line-height: 1; font-weight: 600; }
    .stat-unit { font-size: 28px; color: var(--gold); }
    .stat-label-big { font-size: 12px; color: rgba(255, 255, 255, 0.4); letter-spacing: 2px; text-transform: uppercase; margin-top: 10px; }

    /* ── GENERAL SECTION STYLES ─────────────────────────── */
    section { padding: 100px 60px; max-width: 100%; overflow: hidden; }
    .sec-tag { display: inline-flex; align-items: center; gap: 10px; font-size: 11px; font-weight: 600; letter-spacing: 3px; text-transform: uppercase; color: var(--gold); margin-bottom: 14px; }
    .sec-tag::before { content: ''; width: 30px; height: 1.5px; background: var(--gold); }
    .sec-h2 { font-family: var(--serif); font-size: 48px; color: var(--navy); font-weight: 400; line-height: 1.1; letter-spacing: -0.5px; }
    .sec-h2 em { color: var(--gold); font-style: italic; }
    
    /* ── FEATURED PROPERTIES ────────────────────────────── */
    .featured-section { background: var(--cream); }
    .section-head { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 56px; flex-wrap: wrap; gap: 20px;}
    .prop-grid-new { display: grid; grid-template-columns: 1.3fr 1fr 1fr; gap: 24px; align-items: start; }
    .prop-grid-row2 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 24px; }
    
    .pcard-new { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06); transition: 0.3s ease; cursor: pointer; }
    .pcard-new:hover { transform: translateY(-8px); box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12); }
    .pcard-img { position: relative; overflow: hidden; background: var(--navy-mid); }
    .pcard-new:nth-child(1) .pcard-img { height: 320px; }
    .pcard-new:not(:nth-child(1)) .pcard-img { height: 220px; }
    .pcard-img img { width: 100%; height: 100%; object-fit: cover; transition: 0.6s ease; display: block; }
    .pcard-new:hover .pcard-img img { transform: scale(1.06); }
    .pcard-img-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(13, 27, 42, 0.85) 0%, transparent 55%); }
    .pcard-badge { position: absolute; top: 16px; left: 16px; background: var(--navy); color: var(--gold-light); font-size: 9px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; padding: 5px 12px; border-radius: 3px; }
    .pcard-status { position: absolute; top: 16px; right: 16px; font-size: 9px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; padding: 5px 10px; border-radius: 3px; color: #fff; }
    .pcard-status.hot { background: #e74c3c; } .pcard-status.new { background: #2ecc71; } .pcard-status.limited { background: #e67e22; }
    .pcard-img-bottom { position: absolute; bottom: 16px; left: 16px; right: 16px; display: flex; justify-content: space-between; align-items: center; }
    .pcard-price-over { font-family: var(--serif); font-size: 22px; color: #fff; font-weight: 600; }
    .pcard-price-over small { font-family: var(--sans); font-size: 11px; color: rgba(255, 255, 255, 0.6); display: block; font-weight: 300; }
    
    .pcard-body { padding: 24px; }
    .pcard-title { font-family: var(--serif); font-size: 22px; color: var(--navy); margin-bottom: 6px; font-weight: 600; }
    .pcard-location-row { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted); margin-bottom: 18px; }
    .pcard-specs-row { display: flex; gap: 0; border: 1px solid #ede8df; border-radius: 8px; overflow: hidden; margin-bottom: 20px; }
    .pspec-item { flex: 1; text-align: center; padding: 10px 8px; position: relative; }
    .pspec-item:not(:last-child)::after { content: ''; position: absolute; right: 0; top: 20%; height: 60%; width: 1px; background: #ede8df; }
    .pspec-val { font-size: 15px; font-weight: 700; color: var(--navy); }
    .pspec-key { font-size: 10px; color: var(--text-muted); letter-spacing: 0.5px; margin-top: 2px; text-transform: uppercase;}
    .pcard-footer-row { display: flex; align-items: center; justify-content: space-between; }
    .pcard-emi-tag { font-size: 11px; background: rgba(184, 145, 58, 0.1); color: var(--gold); padding: 5px 10px; border-radius: 4px; font-weight: 600; }
    .btn-book-now { background: var(--navy); color: var(--gold-light); padding: 10px 18px; border-radius: 5px; font-size: 11px; font-weight: 700; text-transform: uppercase; text-decoration: none; transition: 0.2s; }
    .btn-book-now:hover { background: #1B2F42; color: #fff;}

    /* ── PLOT SHOWCASE ─────────────────────────────────── */
    .plot-showcase { background: var(--navy); position: relative; }
    .plot-showcase::before { content: ''; position: absolute; inset: 0; background-image: linear-gradient(rgba(184, 145, 58, 0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(184, 145, 58, 0.04) 1px, transparent 1px); background-size: 60px 60px; }
    .plot-inner { position: relative; z-index: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center; }
    .plot-h2 { font-family: var(--serif); font-size: 52px; color: #fff; line-height: 1.05; margin-bottom: 24px; }
    .plot-h2 em { color: var(--gold-light); font-style: italic; }
    .plot-desc { font-size: 15px; color: rgba(255, 255, 255, 0.55); line-height: 1.8; margin-bottom: 40px; }
    .plot-features-list { list-style: none; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 44px; padding: 0; }
    .plot-features-list li { display: flex; align-items: center; gap: 10px; font-size: 13px; color: rgba(255, 255, 255, 0.75); }
    .pfl-dot { width: 22px; height: 22px; border-radius: 50%; background: rgba(184, 145, 58, 0.15); border: 1px solid rgba(184, 145, 58, 0.4); display: flex; align-items: center; justify-content: center; }
    .plot-img-main { width: 100%; height: 420px; object-fit: cover; border-radius: 12px; display: block; }
    .plot-img-floating { position: absolute; bottom: -30px; left: -30px; width: 200px; height: 150px; border-radius: 10px; border: 4px solid var(--navy); }
    .plot-badge-floating { position: absolute; top: 20px; right: -15px; background: var(--gold); color: var(--navy); padding: 14px 20px; border-radius: 8px; text-align: center; }

    /* ── AMENITIES ─────────────────────────────────────── */
    .amenities-section { background: var(--cream-dark); }
    .amenities-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-top: 56px; }
    .am-card { background: #fff; border-radius: 12px; padding: 36px 28px; text-align: center; border: 1px solid rgba(184, 145, 58, 0.12); transition: 0.3s; position: relative; overflow: hidden; }
    .am-card::before { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: var(--gold); transform: scaleX(0); transition: 0.3s; }
    .am-card:hover { transform: translateY(-6px); box-shadow: 0 20px 50px rgba(0, 0, 0, 0.08); }
    .am-card:hover::before { transform: scaleX(1); }
    .am-icon-wrap { width: 72px; height: 72px; background: rgba(184, 145, 58, 0.08); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 22px; transition: 0.3s; }
    .am-title { font-family: var(--serif); font-size: 18px; color: var(--navy); margin-bottom: 10px; font-weight: 600; }
    .am-desc { font-size: 13px; color: var(--text-muted); line-height: 1.6; }

    /* ── CTA BAND ──────────────────────────────────────── */
    .cta-band { background: var(--gold); padding: 60px; display: flex; justify-content: space-between; align-items: center; gap: 40px; }
    .btn-cta-dark { background: var(--navy); color: var(--gold-light); padding: 16px 36px; border-radius: 5px; font-size: 12px; font-weight: 700; text-transform: uppercase; text-decoration: none; transition: 0.25s; }
    .btn-cta-outline { background: transparent; color: var(--navy); padding: 16px 36px; border-radius: 5px; font-size: 12px; font-weight: 700; text-transform: uppercase; text-decoration: none; border: 2px solid rgba(13, 27, 42, 0.3); transition: 0.25s; }
    
    /* ── CONTACT SECTION (MAP & FORM) ──────────────────── */
    .contact-new { background: #fff; }
    .contact-new-grid { display: grid; grid-template-columns: 1fr 1.3fr; gap: 60px; align-items: start; margin-top: 50px; }
    .cinfo-point { display: flex; align-items: flex-start; gap: 18px; margin-bottom: 30px; }
    .cinfo-icon-new { width: 48px; height: 48px; background: rgba(184, 145, 58, 0.08); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .cinfo-title { font-size: 13px; font-weight: 700; color: var(--navy); margin-bottom: 5px; }
    .cinfo-val { font-size: 14px; color: var(--text-muted); line-height: 1.6; }
    
    .cform-title { font-family: var(--serif); font-size: 32px; color: var(--navy); margin-bottom: 8px; line-height: 1.2; }
    .cform-title em { font-style: italic; color: var(--gold); }
    .cform-sub { font-size: 14px; color: var(--text-muted); margin-bottom: 32px; }
    .cform-group { margin-bottom: 18px; width: 100%; }
    .cform-group label { display: block; font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 7px; }
    .cform-group input, .cform-group select, .cform-group textarea { width: 100%; padding: 14px 18px; border: 1.5px solid #e8e2d8; border-radius: 7px; background: #fafafa; font-family: var(--sans); outline: none; }
    .btn-submit-new { width: 100%; background: var(--navy); color: var(--gold-light); border: none; padding: 17px; border-radius: 7px; font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; cursor: pointer; transition: 0.2s; }

    /* ── ANIMATIONS ─────────────────────────────────────── */
    @keyframes slideRight { from { opacity: 0; transform: translateX(-30px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes pulse { 0%, 100% { box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.25); } 50% { box-shadow: 0 0 0 8px rgba(39, 174, 96, 0.08); } }
    .reveal { opacity: 0; transform: translateY(28px); transition: opacity 0.7s ease, transform 0.7s ease; }
    .reveal.visible { opacity: 1; transform: translateY(0); }

    /* ================= RESPONSIVE FIXES (No Overflow) ================= */
    @media (max-width: 1024px) {
        section { padding: 80px 40px; }
        .hero-inner { grid-template-columns: 1fr; padding: 0 40px 60px; }
        .hero-panel-new { display: none; }
        .stats-section, .prop-grid-new, .amenities-grid { grid-template-columns: 1fr 1fr; }
        .plot-inner, .contact-new-grid { grid-template-columns: 1fr; gap: 50px; }
    }
    
    @media (max-width: 768px) {
        section { padding: 60px 20px; } /* Removed extreme horizontal padding */
        
        .hero-new {
            min-height: 85vh; /* Better mobile scaling */
            padding-top: 100px;
            padding-bottom: 50px;
        }
        .hero-inner { padding: 0 20px; }
        .hero-h1 { font-size: 40px; line-height: 1.15; margin-bottom: 15px; }
        .hero-desc-new { font-size: 14px; margin-bottom: 25px; }
        
        .hero-stats-bar { gap: 20px; flex-wrap: wrap; justify-content: center; text-align: center; }
        .hstat-num { font-size: 32px; }
        
        .stats-section { padding: 60px 20px; grid-template-columns: 1fr 1fr; gap: 20px; }
        .stat-block { padding: 15px 10px; }
        .stat-block:not(:last-child)::after { display: none; } /* Clear vertical lines on mobile */
        
        .prop-grid-new, .prop-grid-row2 { grid-template-columns: 1fr; gap: 20px; }
        
        .trust-bar { padding: 15px 10px; flex-wrap: wrap; gap: 10px; }
        .trust-item-new { flex: 1 1 45%; padding: 10px 0; justify-content: flex-start; text-align: left; }
        .trust-item-new:not(:last-child)::after { display: none; }
        
        .plot-h2 { font-size: 36px; }
        .plot-features-list { grid-template-columns: 1fr; }
        .amenities-grid { grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .cta-band { flex-direction: column; text-align: center; padding: 40px 20px; }
        
        /* Contact fixes for mobile */
        .cform-row { grid-template-columns: 1fr; gap: 0; }
        .hero-slide-dots { left: 50%; transform: translateX(-50%); bottom: 20px; } /* Center dots perfectly */
    }

    @media (max-width: 480px) {
        .hero-h1 { font-size: 34px; }
        .hero-stats-bar { display: none; } /* Hide complicated stats bar on extra small screens to save space */
        .btn-primary-hero, .btn-ghost-hero { width: 100%; display: flex; justify-content: center; }
        
        .stat-big { font-size: 32px; }
        .amenities-grid { grid-template-columns: 1fr; } 
        
        .cform-title { font-size: 26px; }
        .contact-new-grid { gap: 30px; }
    }
</style>
@endpush

@section('content')

<!-- ── HERO CINEMATIC ─────────────────────────────────── -->
<div class="hero-new" id="home">
    <div class="hero-bg-slider" id="heroSlider">
        <div class="hero-slide active"></div>
        <div class="hero-slide"></div>
        <div class="hero-slide"></div>
        <div class="hero-slide"></div>
    </div>
    <div class="hero-overlay"></div>

    <div class="hero-inner">
        <div class="hero-text">
            <div class="hero-eyebrow-new">
                <div class="line"></div>
                <span>RERA Approved · Clear Title · Zero Brokerage</span>
            </div>
            <h1 class="hero-h1">
                Your Dream<br>
                Plot in <em>Bihar</em>,<br>
                Now Within Reach
            </h1>
            <p class="hero-desc-new">
                Premium residential plots and luxury villas at Janki Villa, Darbhanga &amp; Madhubani.
                Immediate possession · 18-month zero-interest EMI · Bank loans available.
            </p>
            <div class="hero-actions-new">
                <!-- Trigger Neha AI Chatbot here -->
                <button onclick="toggleNeha()" class="btn-primary-hero">
                    <i class="fas fa-robot me-2"></i> Ask Janki AI
                </button>
                <a href="#properties" class="btn-ghost-hero">
                    Explore Projects
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" class="ms-2">
                        <path d="M12 5v14M5 12l7 7 7-7" />
                    </svg>
                </a>
            </div>
            <div class="hero-stats-bar">
                <div class="hstat">
                    <span class="hstat-num">150<span style="font-size:28px">+</span></span>
                    <span class="hstat-label">Happy Families</span>
                </div>
                <div class="hstat">
                    <span class="hstat-num">₹22L</span>
                    <span class="hstat-label">Plots Starting</span>
                </div>
                <div class="hstat">
                    <span class="hstat-num">18<span style="font-size:28px">mo</span></span>
                    <span class="hstat-label">Zero Interest EMI</span>
                </div>
                <div class="hstat">
                    <span class="hstat-num">2<span style="font-size:28px">km</span></span>
                    <span class="hstat-label">From Airport</span>
                </div>
            </div>
        </div>

        <div class="hero-panel-new">
            <div class="hero-search-card">
                <p class="hsc-title">Find Your Plot</p>
                <div class="hsc-tabs">
                    <button class="hsc-tab active" onclick="setTab(this,'buy')">Buy</button>
                    <button class="hsc-tab" onclick="setTab(this,'invest')">Invest</button>
                    <button class="hsc-tab" onclick="setTab(this,'visit')">Site Visit</button>
                </div>
                <div class="hsc-field">
                    <label>Property Type</label>
                    <select>
                        <option value="">All Types</option>
                        <option>Residential Plot</option>
                        <option>Corner Plot</option>
                        <option>Duplex Villa</option>
                        <option>Simplex Villa</option>
                    </select>
                </div>
                <div class="hsc-grid2">
                    <div class="hsc-field">
                        <label>Project</label>
                        <select>
                            <option value="">Any</option>
                            <option>Janki Villa Phase 1</option>
                            <option>Janki Villa Phase 2</option>
                        </select>
                    </div>
                    <div class="hsc-field">
                        <label>Budget</label>
                        <select>
                            <option value="">Any</option>
                            <option>₹20L – ₹30L</option>
                            <option>₹30L – ₹45L</option>
                            <option>₹45L+</option>
                        </select>
                    </div>
                </div>
                <button class="btn-search-card" onclick="window.location.href='#properties'">
                    <i class="fas fa-search me-2"></i> Search Properties
                </button>
            </div>
            <div class="hero-avail-tag">
                <div class="avail-dot"></div>
                <div class="avail-text">
                    <strong>Live Inventory Available</strong>
                    Phase 1 — 22 plots remaining · Phase 2 — Now Open
                </div>
            </div>
        </div>
    </div>

    <div class="hero-slide-dots" id="slideDots">
        <div class="slide-dot active" onclick="goSlide(0)"></div>
        <div class="slide-dot" onclick="goSlide(1)"></div>
        <div class="slide-dot" onclick="goSlide(2)"></div>
        <div class="slide-dot" onclick="goSlide(3)"></div>
    </div>
</div>

<!-- ── TRUST BAR ──────────────────────────────────────── -->
<div class="trust-bar">
    <div class="trust-item-new"><i class="fas fa-file-signature fs-4 text-dark me-2"></i> RERA Approved</div>
    <div class="trust-item-new"><i class="fas fa-shield-alt fs-4 text-dark me-2"></i> 100% Clear Title</div>
    <div class="trust-item-new"><i class="fas fa-handshake fs-4 text-dark me-2"></i> Zero Brokerage</div>
    <div class="trust-item-new"><i class="fas fa-university fs-4 text-dark me-2"></i> Bank Loans Available</div>
</div>

<!-- ── STATS ROW ─────────────────────────────────────── -->
<div class="stats-section">
    <div class="stat-block reveal">
        <div class="stat-big"><span class="counter" data-target="150">0</span><span class="stat-unit">+</span></div>
        <div class="stat-label-big">Happy Families</div>
    </div>
    <div class="stat-block reveal" style="transition-delay:0.1s">
        <div class="stat-big"><span class="counter" data-target="2">0</span><span class="stat-unit"> Phases</span></div>
        <div class="stat-label-big">Launched Projects</div>
    </div>
    <div class="stat-block reveal" style="transition-delay:0.2s">
        <div class="stat-big"><span class="counter" data-target="500">0</span><span class="stat-unit">+</span></div>
        <div class="stat-label-big">Plots Registered</div>
    </div>
    <div class="stat-block reveal" style="transition-delay:0.3s">
        <div class="stat-big"><span class="counter" data-target="5">0</span><span class="stat-unit"> Yrs</span></div>
        <div class="stat-label-big">Trusted Legacy</div>
    </div>
</div>

<!-- ── FEATURED PROPERTIES ──────────────────────────── -->
<section class="featured-section" id="properties">
    <div class="section-head">
        <div class="section-head-left">
            <div class="sec-tag">Our Inventory</div>
            <h2 class="sec-h2">Featured <em>Properties</em></h2>
        </div>
        <div style="display:flex;gap:12px;align-items:center; flex-wrap:wrap;">
            <button class="hsc-tab active pf-btn px-4 py-2 text-white border-0 rounded" style="background:var(--navy);" onclick="filterCards(this,'all')">All</button>
            <button class="hsc-tab pf-btn px-4 py-2 border rounded" style="background:#fff; color:var(--navy); border-color:#e0d8cd;" onclick="filterCards(this,'plot')">Plots</button>
            <button class="hsc-tab pf-btn px-4 py-2 border rounded" style="background:#fff; color:var(--navy); border-color:#e0d8cd;" onclick="filterCards(this,'villa')">Villas</button>
        </div>
    </div>

    <div class="prop-grid-new">
        <!-- Card 1 -->
        <div class="pcard-new reveal" data-cat="plot">
            <div class="pcard-img">
                <img src="https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=800&q=80" alt="Plot">
                <div class="pcard-img-overlay"></div>
                <div class="pcard-badge">⭐ Bestseller</div>
                <div class="pcard-status hot">Selling Fast</div>
                <div class="pcard-img-bottom">
                    <div class="pcard-price-over">₹22 Lakhs<small>onwards · 18-mo 0% EMI</small></div>
                </div>
            </div>
            <div class="pcard-body">
                <div class="pcard-title">Janki Villa — Phase 1</div>
                <div class="pcard-location-row"><i class="fas fa-map-marker-alt text-warning"></i> Bhuskaul, Darbhanga — 2km from Airport</div>
                <div class="pcard-specs-row">
                    <div class="pspec-item"><div class="pspec-val">950</div><div class="pspec-key">Sq.Ft</div></div>
                    <div class="pspec-item"><div class="pspec-val">20ft</div><div class="pspec-key">Road</div></div>
                    <div class="pspec-item"><div class="pspec-val">RERA</div><div class="pspec-key">Approved</div></div>
                </div>
                <div class="pcard-footer-row">
                    <span class="pcard-emi-tag">0% EMI · 18 Months</span>
                    <a href="#contact" class="btn-book-now">Book Now</a>
                </div>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="pcard-new reveal" data-cat="plot" style="transition-delay:0.1s">
            <div class="pcard-img">
                <img src="https://images.unsplash.com/photo-1574958269340-fa927503f3dd?w=600&q=80" alt="Phase 2">
                <div class="pcard-img-overlay"></div>
                <div class="pcard-badge">New Launch</div>
                <div class="pcard-status new">Open Now</div>
                <div class="pcard-img-bottom"><div class="pcard-price-over">₹30 Lakhs<small>Corner plots available</small></div></div>
            </div>
            <div class="pcard-body">
                <div class="pcard-title">Janki Villa — Phase 2</div>
                <div class="pcard-location-row"><i class="fas fa-map-marker-alt text-warning"></i> Kharthuaa, Madhubani Border</div>
                <div class="pcard-specs-row">
                    <div class="pspec-item"><div class="pspec-val">1200</div><div class="pspec-key">Sq.Ft</div></div>
                    <div class="pspec-item"><div class="pspec-val">30ft</div><div class="pspec-key">Road</div></div>
                </div>
                <div class="pcard-footer-row">
                    <span class="pcard-emi-tag">Easy Financing</span>
                    <a href="#contact" class="btn-book-now">Book Now</a>
                </div>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="pcard-new reveal" data-cat="villa" style="transition-delay:0.2s">
            <div class="pcard-img">
                <img src="https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=600&q=80" alt="Villa">
                <div class="pcard-img-overlay"></div>
                <div class="pcard-badge">Premium Villa</div>
                <div class="pcard-img-bottom"><div class="pcard-price-over">₹45 Lakhs<small>Construction Linked Plan</small></div></div>
            </div>
            <div class="pcard-body">
                <div class="pcard-title">Type-A Luxury Duplex</div>
                <div class="pcard-location-row"><i class="fas fa-map-marker-alt text-warning"></i> Janki Villa Gated Society</div>
                <div class="pcard-specs-row">
                    <div class="pspec-item"><div class="pspec-val">4 BHK</div><div class="pspec-key">Bedrooms</div></div>
                    <div class="pspec-item"><div class="pspec-val">Vastu</div><div class="pspec-key">Approved</div></div>
                </div>
                <div class="pcard-footer-row">
                    <span class="pcard-emi-tag">Linked Plan</span>
                    <a href="#contact" class="btn-book-now">Enquire</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── PLOT SHOWCASE ─────────────────────────────────── -->
<section class="plot-showcase" id="about">
    <div class="plot-inner">
        <div class="plot-left reveal">
            <div class="plot-sec-tag">Why Invest With Us</div>
            <h2 class="plot-h2">Darbhanga's Most <em>Trusted</em><br>Land Developer</h2>
            <p class="plot-desc">
                Amitabh Builders & Developers has been transforming Bihar's real estate landscape since 2020. 
                Every plot comes with government-registered documents, clear title, boundary walls, blacktop roads, 
                and full civic infrastructure — so your investment is fully protected from day one.
            </p>
            <ul class="plot-features-list">
                <li><div class="pfl-dot"><i class="fas fa-check text-warning"></i></div> Govt. Registered & Clear Title</li>
                <li><div class="pfl-dot"><i class="fas fa-check text-warning"></i></div> Blacktop 20ft &amp; 30ft Roads</li>
                <li><div class="pfl-dot"><i class="fas fa-check text-warning"></i></div> Immediate Physical Possession</li>
                <li><div class="pfl-dot"><i class="fas fa-check text-warning"></i></div> 18-Month 0% EMI Plan</li>
            </ul>
            <button onclick="toggleNeha()" class="btn-primary-hero mt-3 border-0">Chat with AI Expert</button>
        </div>
        <div class="plot-visual-wrap reveal" style="transition-delay:0.2s">
            <img class="plot-img-main" src="https://images.unsplash.com/photo-1602941525421-8f8b81d3edbb?w=900&q=80" alt="Layout">
            <div class="plot-badge-floating">
                <span class="pbf-big">2 km</span>
                <span class="pbf-small">From Airport</span>
            </div>
        </div>
    </div>
</section>

<!-- ── AMENITIES ─────────────────────────────────────── -->
<section class="amenities-section" id="amenities">
    <div style="text-align:center; max-width: 600px; margin: 0 auto;">
        <div class="sec-tag" style="justify-content:center;">World-Class Infrastructure</div>
        <h2 class="sec-h2">Every Amenity <em>Included</em></h2>
        <p class="text-muted mt-3">Thoughtfully engineered communities where every detail is built to last.</p>
    </div>
    <div class="amenities-grid">
        <div class="am-card reveal">
            <div class="am-icon-wrap"><i class="fas fa-torii-gate fs-3 text-warning"></i></div>
            <div class="am-title">Grand Entry Gate</div>
            <div class="am-desc">Imposing gated entrance with round-the-clock security.</div>
        </div>
        <div class="am-card reveal" style="transition-delay:0.1s">
            <div class="am-icon-wrap"><i class="fas fa-road fs-3 text-warning"></i></div>
            <div class="am-title">Blacktop Roads</div>
            <div class="am-desc">20ft &amp; 30ft wide internal pitch roads.</div>
        </div>
        <div class="am-card reveal" style="transition-delay:0.15s">
            <div class="am-icon-wrap"><i class="fas fa-water fs-3 text-warning"></i></div>
            <div class="am-title">Underground Drainage</div>
            <div class="am-desc">Scientifically planned sewage and drainage.</div>
        </div>
        <div class="am-card reveal" style="transition-delay:0.2s">
            <div class="am-icon-wrap"><i class="fas fa-tree fs-3 text-warning"></i></div>
            <div class="am-title">Community Park</div>
            <div class="am-desc">Green open spaces and kids' play areas.</div>
        </div>
    </div>
</section>

<!-- ── CTA BAND ──────────────────────────────────────── -->
<div class="cta-band">
    <div class="cta-band-left">
        <h2 class="cta-band-h2 text-dark">Limited Plots Remaining.<br>Secure Yours Today.</h2>
        <p class="cta-band-sub text-dark opacity-75">Phase 1 — Only 22 plots left · Phase 2 — Open for Booking</p>
    </div>
    <div class="cta-band-actions">
        <a href="#contact" class="btn-cta-dark">Book Free Site Visit</a>
        <a href="tel:+919472467007" class="btn-cta-outline"><i class="fas fa-phone-alt me-2"></i> Call Now</a>
    </div>
</div>

<!-- ── UPDATED CONTACT SECTION (INFO + MAP & FORM) ──── -->
<section class="contact-new" id="contact">
    <div style="text-align:center; max-width:600px; margin: 0 auto; margin-bottom: 50px;">
        <div class="sec-tag" style="justify-content:center;">Get In Touch</div>
        <h2 class="sec-h2">Book a Free <em>Site Visit</em></h2>
        <p class="text-muted mt-3">Fill in your details and our expert property advisors will contact you within 24 hours.</p>
    </div>

    <div class="contact-new-grid">
        <!-- Left Side: Contact Info + Google Map -->
        <div class="contact-info-new reveal">
            <div class="cinfo-point">
                <div class="cinfo-icon-new"><i class="fas fa-phone-alt fs-5 text-warning"></i></div>
                <div>
                    <div class="cinfo-title">Call / WhatsApp</div>
                    <div class="cinfo-val">9472467007<br>9060218 — 222 / 333 / 666<br><span style="font-size:11px;color:var(--text-muted);">Mon–Sat · 9 AM – 7 PM</span></div>
                </div>
            </div>
            <div class="cinfo-point">
                <div class="cinfo-icon-new"><i class="fas fa-envelope fs-5 text-warning"></i></div>
                <div>
                    <div class="cinfo-title">Email</div>
                    <div class="cinfo-val">abdeveloperspl@gmail.com<br><span style="font-size:11px;color:var(--text-muted);">We reply within 24 hours</span></div>
                </div>
            </div>
            <div class="cinfo-point">
                <div class="cinfo-icon-new"><i class="fas fa-map-marker-alt fs-5 text-warning"></i></div>
                <div>
                    <div class="cinfo-title">Head Office</div>
                    <div class="cinfo-val">1st Floor, Pappu Yadav Building,<br>South of NH-27, Kakarghati Chowk,<br>Bhuskaul, Darbhanga — 846007</div>
                </div>
            </div>
            
            <!-- Embedded Google Map -->
            <div style="border-radius:12px; overflow:hidden; margin-top:30px; border: 1px solid rgba(184,145,58,0.2); box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d14321.934206095986!2d85.95335269171781!3d26.180943948519307!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39edc90044979dfb%3A0x23a1428f9f175783!2sJanki%20villa!5e0!3m2!1sen!2sin!4v1779794348438!5m2!1sen!2sin" width="100%" height="320" style="border:0; display:block;" allowfullscreen="" loading="lazy" title="AB Developers Location"></iframe>
            </div>
        </div>

        <!-- Right Side: Contact Form -->
        <form class="contact-form-new reveal" method="POST" action="#" style="transition-delay:0.15s; background: #fff; border-radius:12px; border: 1px solid #e8e2d8; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.03);">
            @csrf
            <h3 class="cform-title" style="font-size: 28px;">Request Callback &<br><em>Get Brochure</em></h3>
            <p class="cform-sub mb-4">Submit your details to instantly receive pricing, layout maps & brochure or WhatsApp.</p>
            
            <div class="cform-row">
                <div class="cform-group">
                    <label>FULL NAME</label>
                    <input type="text" name="full_name" placeholder="Your full name" required />
                </div>
                <div class="cform-group">
                    <label>WHATSAPP NUMBER</label>
                    <input type="tel" name="mobile" placeholder="+91 XXXXX XXXXX" required />
                </div>
            </div>
            <div class="cform-group">
                <label>EMAIL ADDRESS</label>
                <input type="email" name="email" placeholder="your@email.com" />
            </div>
            <div class="cform-row">
                <div class="cform-group">
                    <label>INTERESTED IN</label>
                    <select name="interested_in" required>
                        <option value="">Select Property</option>
                        <option value="Phase 1 Plot">Phase 1 Plot</option>
                        <option value="Phase 2 Plot">Phase 2 Plot</option>
                        <option value="Villa">Simplex/Duplex Villa</option>
                    </select>
                </div>
                <div class="cform-group">
                    <label>PLANNING TO BUY IN</label>
                    <select name="timeframe" required>
                        <option value="">Select Timeframe</option>
                        <option value="Immediate">Immediate</option>
                        <option value="1-3 Months">1-3 Months</option>
                    </select>
                </div>
            </div>
            <div class="cform-group">
                <label>MESSAGE (OPTIONAL)</label>
                <textarea name="message" rows="3" placeholder="Any specific requirements or questions..."></textarea>
            </div>
            <button class="btn-submit-new mt-3" type="submit"><i class="fas fa-paper-plane me-2"></i> SEND ON WHATSAPP & GET BROCHURE</button>
        </form>
    </div>
</section>

@endsection

@push('scripts')
<script>
    // 1. Hero Image Slider JS
    let currentSlide = 0;
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.slide-dot');
    
    window.goSlide = function(n) {
        slides.forEach(s => s.classList.remove('active'));
        dots.forEach(d => d.classList.remove('active'));
        currentSlide = n;
        if(slides[currentSlide]) slides[currentSlide].classList.add('active');
        if(dots[currentSlide]) dots[currentSlide].classList.add('active');
    };

    setInterval(() => {
        if(slides.length > 0) {
            goSlide((currentSlide + 1) % slides.length);
        }
    }, 5000);

    // 2. Tabs Logic (Search Box)
    window.setTab = function(btn, type) {
        document.querySelectorAll('.hsc-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    };

    // 3. Property Filtering Logic
    window.filterCards = function(btn, cat) {
        document.querySelectorAll('.pf-btn').forEach(b => {
            b.classList.remove('active');
            b.style.background = '#fff';
            b.style.color = 'var(--navy)';
        });
        btn.classList.add('active');
        btn.style.background = 'var(--navy)';
        btn.style.color = 'var(--gold-light)';

        document.querySelectorAll('.pcard-new').forEach(card => {
            if(cat === 'all' || card.getAttribute('data-cat') === cat) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    };

    // 4. Scroll Reveal Animation Logic
    document.addEventListener("DOMContentLoaded", () => {
        const revealElements = document.querySelectorAll('.reveal');
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if(entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });
        revealElements.forEach(el => revealObserver.observe(el));

        // 5. Counter Animation Logic
        const counters = document.querySelectorAll('.counter');
        counters.forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText;
                const inc = target / 100;
                if (count < target) {
                    counter.innerText = Math.ceil(count + inc);
                    setTimeout(updateCount, 20);
                } else {
                    counter.innerText = target;
                }
            };
            
            new IntersectionObserver((entries, obs) => {
                if(entries[0].isIntersecting) {
                    updateCount();
                    obs.disconnect();
                }
            }).observe(counter);
        });
    });
</script>
@endpush