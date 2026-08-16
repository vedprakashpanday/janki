@extends('layout.frontend-app')

@section('title', 'About Us | Amitabh Builders & Developers')
@section('meta_description', 'Learn about Amitabh Builders & Developers Pvt. Ltd. Founded in 2024, redefining real estate in Darbhanga.')

@push('styles')
<style>
    /* Fresh Light Theme Colors */
    :root {
        --bg-light: #faf9f6; /* Ivory White */
        --text-dark: #111827;
        --text-muted: #6b7280;
        --card-bg: #ffffff;
    }
    
    body { background-color: var(--bg-light); color: var(--text-dark); }
    
    .about-hero { padding: 80px 0 40px; }
    .section-title { font-size: 3rem; font-weight: 700; color: var(--text-dark); line-height: 1.2; margin-bottom: 20px; }
    .gold-line { width: 60px; height: 3px; background-color: var(--primary-gold); margin-bottom: 20px; }
    
    .info-card {
        background: var(--card-bg); border: 1px solid rgba(0,0,0,0.05); border-radius: 16px;
        padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: 0.3s; height: 100%;
    }
    .info-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(212, 175, 55, 0.1); border-color: rgba(212, 175, 55, 0.3); }
    .info-icon { width: 40px; height: 40px; border-radius: 50%; background: rgba(212, 175, 55, 0.1); color: var(--primary-gold); display: flex; align-items: center; justify-content: center; margin-bottom: 15px; }
    
    .mission-box { background: linear-gradient(145deg, #f3f0e7, #ffffff); border-left: 4px solid var(--primary-gold); padding: 30px; border-radius: 0 16px 16px 0; margin-top: 30px; }
    
    .project-list { border-left: 2px solid rgba(212, 175, 55, 0.3); padding-left: 20px; margin-top: 30px; }
    .project-item { position: relative; margin-bottom: 25px; }
    .project-item::before { content: ''; position: absolute; left: -25px; top: 5px; width: 10px; height: 10px; border-radius: 50%; background: var(--primary-gold); }
</style>
@endpush

@section('content')
<section class="about-hero container py-5 mt-5">
    <div class="row g-5 align-items-center">
        <!-- Left Content -->
        <div class="col-lg-6">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="gold-line m-0"></div>
                <span class="text-uppercase tracking-wider fw-bold text-muted" style="letter-spacing: 2px; font-size: 13px;">Our Identity</span>
            </div>
            <h1 class="section-title">Amitabh Builders &<br>Developers Pvt. Ltd.</h1>
            <p class="text-muted fs-5 mb-4">Since our incorporation on December 18, 2024, in Darbhanga, Bihar, we have been committed to redefining the standards of the local real estate market.</p>
            <p class="text-muted">Our focus lies in providing high-grade residential plots and elegantly designed homes that offer both durability and modern comfort. We aim to convert raw land into thriving, sustainable communities for a better tomorrow.</p>

            <div class="row g-3 mt-4">
                <div class="col-sm-6">
                    <div class="info-card">
                        <div class="info-icon"><i class="fas fa-check"></i></div>
                        <h5 class="fw-bold">RERA Approved</h5>
                        <p class="text-muted small m-0">We follow all statutory norms and RERA guidelines to ensure your property investment is legally secure.</p>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="info-card">
                        <div class="info-icon"><i class="fas fa-chart-line"></i></div>
                        <h5 class="fw-bold">Growth Potential</h5>
                        <p class="text-muted small m-0">Our projects are located in prime zones of Darbhanga, ensuring high appreciation and long-term value.</p>
                    </div>
                </div>
            </div>

            <div class="mission-box shadow-sm">
                <h4 class="fw-bold mb-2">Our Mission</h4>
                <p class="text-muted m-0">To provide premium housing solutions at prices accessible to the common man. We believe in transparency, honesty, and a customer-first approach in every brick we lay.</p>
            </div>
        </div>

        <!-- Right Content (Image & Projects) -->
        <div class="col-lg-6">
            <img src="{{ asset('uploads/3rd-img.jpeg') }}" alt="Janki Villa Township" class="img-fluid rounded-4 shadow-lg mb-5" style="height: 300px; width: 100%; object-fit: cover;">
            
            <div class="info-card">
                <h4 class="fw-bold mb-4">Current Projects</h4>
                <div class="project-list">
                    <div class="project-item">
                        <h6 class="fw-bold mb-1">JANKI VILLA - PHASE 1</h6>
                        <p class="text-muted small m-0">A gated residential community situated near Darbhanga Airport with excellent NH-27 connectivity.</p>
                    </div>
                    <div class="project-item">
                        <h6 class="fw-bold mb-1">JANKI VILLA - PHASE 2</h6>
                        <p class="text-muted small m-0">Strategically developed in Kharthuaa, providing modern infrastructure and lush green surroundings.</p>
                    </div>
                    <div class="project-item">
                        <h6 class="fw-bold mb-1">JANKI VILLA - PHASE 3</h6>
                        <p class="text-muted small m-0">Our most ambitious project yet, coming soon to the people of Darbhanga.</p>
                    </div>
                </div>
                <button class="btn btn-dark w-100 mt-4 rounded-pill py-2" onclick="toggleNeha()">Explore All Developments</button>
            </div>
        </div>
    </div>
</section>
@endsection