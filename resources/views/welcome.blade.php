@extends('layout.frontend-app')

@section('content')
<style>
    /* ==========================================
       PREMIUM UI ELEMENTS & HERO
       ========================================== */
    .hero-container {
        height: 85vh;
        min-height: 600px;
        background: url('https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?q=80&w=2000&auto=format&fit=crop') center/cover no-repeat;
        position: relative;
        display: flex;
        align-items: center;
        border-radius: 0 0 40px 40px;
        margin-bottom: 40px;
    }
    .hero-container::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.3));
        border-radius: 0 0 40px 40px;
    }
    .hero-content { position: relative; z-index: 1; color: white; }
    
    /* Floating Search Bar */
    .glass-search {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 8px;
        border-radius: 100px;
        display: flex;
        align-items: center;
        max-width: 500px;
        margin-top: 30px;
    }
    .glass-search input { background: transparent; border: none; color: white; padding: 10px 20px; width: 100%; outline: none; }
    .glass-search input::placeholder { color: rgba(255,255,255,0.7); }
    .glass-search button { background: #fff; color: #0f172a; border: none; width: 45px; height: 45px; border-radius: 50%; transition: 0.3s; }
    .glass-search button:hover { background: #0d6efd; color: white; }

    /* ==========================================
       EXPLORE CARDS 
       ========================================== */
    .explore-card {
        border-radius: 24px;
        padding: 25px 15px;
        background: #fff;
        border: none;
        box-shadow: 0 10px 25px rgba(0,0,0,0.04);
        text-align: center;
        cursor: pointer;
        transition: 0.3s;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .explore-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); }
    .explore-icon { width: 65px; height: 65px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; font-size: 24px; }
    .explore-title { font-weight: 700; margin: 0 0 5px 0; font-size: 18px; }
    .explore-sub { font-size: 13px; opacity: 0.7; margin: 0; }

    /* ==========================================
       FEATURES, TRUST & PROCESS CSS
       ========================================== */
    .feature-box {
        padding: 30px;
        background: #fff;
        border-radius: 24px;
        border: 1px solid rgba(0,0,0,0.03);
        box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        transition: 0.4s;
        height: 100%;
    }
    .feature-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.06);
        border-color: rgba(13, 110, 253, 0.1);
    }
    .icon-wrapper {
        width: 60px; height: 60px;
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; margin-bottom: 20px;
    }
    
    /* Project Phases CSS */
    .phase-card {
        border-radius: 24px;
        padding: 40px 30px;
        border: 1px solid rgba(0,0,0,0.05);
        position: relative;
        overflow: hidden;
        height: 100%;
        transition: 0.3s;
    }
    .phase-active { background: #0f172a; color: white; box-shadow: 0 20px 40px rgba(15,23,42,0.15); border: none; }
    .phase-selling { background: #ffffff; border: 2px solid #0d6efd; box-shadow: 0 15px 35px rgba(13, 110, 253, 0.1); }
    .phase-upcoming { background: #f8fafc; color: #64748b; }
    .phase-badge { position: absolute; top: 20px; right: 20px; padding: 6px 16px; border-radius: 30px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }

    /* Process Section */
    .process-section {
        background: #0f172a;
        color: white;
        border-radius: 40px;
        padding: 80px 20px;
        margin: 0 10px 60px 10px;
    }
    
    .step-number {
        font-size: 55px;
        font-weight: 900;
        color: transparent;
        -webkit-text-stroke: 1.5px rgba(255, 255, 255, 0.2); 
        line-height: 1;
        margin-bottom: 15px;
        display: block;
    }

    /* Testimonial & CTA CSS */
    .testimonial-card {
        background: #fff;
        border-radius: 24px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        height: 100%;
        border: 1px solid rgba(0,0,0,0.02);
    }
    .cta-section {
        background: linear-gradient(135deg, #0d6efd, #0043a8);
        border-radius: 30px;
        color: white;
        padding: 60px 40px;
        position: relative;
        overflow: hidden;
    }

    /* Mobile Adjustments */
    @media (max-width: 768px) {
        .hero-container { border-radius: 0 0 30px 30px; height: 75vh; }
        .hero-container::before { border-radius: 0 0 30px 30px; }
        .process-section { border-radius: 30px; padding: 60px 15px; margin: 0 0 40px 0; }
        .phase-card { padding: 30px 20px; }
        
        .explore-card { padding: 15px 5px !important; border-radius: 16px; }
        .explore-icon { width: 45px !important; height: 45px !important; font-size: 18px !important; margin-bottom: 10px !important; }
        .explore-title { font-size: 13px !important; }
        .explore-sub { font-size: 10px !important; }

        .cta-section { padding: 40px 20px; text-align: center; border-radius: 20px; }
    }
</style>

<div class="hero-container">
    <div class="container hero-content">
        <span class="badge bg-primary text-white px-3 py-2 rounded-pill mb-3 fw-normal">Premium Real Estate in Darbhanga</span>
        <h1 class="display-4 fw-bold mb-3" style="letter-spacing: -1px; line-height: 1.1;">Find the perfect<br>place to build your life.</h1>
        <p class="fs-5 text-light opacity-75 max-w-50">Exclusive plots, luxury ready-to-move villas, and custom combo builds tailored for you.</p>
        
        <div class="glass-search shadow-lg">
            <input type="text" placeholder="Search locations, plots, or villas...">
            <button><i class="fas fa-search"></i></button>
        </div>
    </div>
</div>

<div class="container mb-5 pt-3" id="explore">
    <div class="d-flex justify-content-between align-items-end mb-4 px-2">
        <div>
            <h2 class="fw-bold m-0" style="letter-spacing: -0.5px;">Explore JankiVilla</h2>
            <p class="text-muted m-0 mt-1">Select your property type</p>
        </div>
    </div>
    
    <div class="row g-2 g-md-3 px-2">
        <div class="col-4">
            <div class="explore-card">
                <div class="explore-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-layer-group"></i>
                </div>
                <h5 class="explore-title text-dark">Plots</h5>
                <p class="explore-sub text-muted">120+ Available</p>
            </div>
        </div>
        <div class="col-4">
            <div class="explore-card">
                <div class="explore-icon bg-dark bg-opacity-10 text-dark">
                    <i class="fas fa-home"></i>
                </div>
                <h5 class="explore-title text-dark">Villas</h5>
                <p class="explore-sub text-muted">45+ Premium</p>
            </div>
        </div>
        <div class="col-4">
            <div class="explore-card" style="background: #f0fdf4;">
                <div class="explore-icon bg-success text-white">
                    <i class="fas fa-tools"></i>
                </div>
                <h5 class="explore-title text-success">Combo</h5>
                <p class="explore-sub text-success">Land + Build</p>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5 pt-5" id="phases">
    <div class="text-center mb-5">
        <span class="text-primary fw-bold text-uppercase small" style="letter-spacing: 2px;">Development Masterplan</span>
        <h2 class="fw-bold mt-2 mb-3 display-6" style="letter-spacing: -1px;">A Growing Township</h2>
        <p class="text-muted mx-auto" style="max-width: 600px;">JankiVilla is being developed in strategic phases. Secure your spot early as we expand into a complete, self-sustaining luxury ecosystem.</p>
    </div>

    <div class="row g-4 px-2">
        <div class="col-lg-4">
            <div class="phase-card phase-active">
                <span class="phase-badge bg-primary text-white">Selling Fast</span>
                <h6 class="text-primary fw-bold mb-1"><i class="fas fa-check-circle me-1"></i> Phase 1</h6>
                <h3 class="fw-bold text-white mb-3">Foundation</h3>
                <p class="text-light opacity-75 mb-4">The core residential zone is currently open for booking. Perfect for immediate construction or high-return investment.</p>
                <ul class="list-unstyled mb-0 text-light opacity-75">
                    <li class="mb-2"><i class="fas fa-arrow-right text-primary me-2"></i> Premium Residential Plots</li>
                    <li class="mb-2"><i class="fas fa-arrow-right text-primary me-2"></i> Ready-to-move Villas</li>
                    <li class="mb-2"><i class="fas fa-arrow-right text-primary me-2"></i> Land + Construction Combo</li>
                    <li><i class="fas fa-arrow-right text-primary me-2"></i> Basic Infrastructure (Roads)</li>
                </ul>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="phase-card phase-selling">
                <span class="phase-badge bg-primary text-white">Now Open</span>
                <h6 class="text-primary fw-bold mb-1"><i class="fas fa-fire me-1"></i> Phase 2</h6>
                <h3 class="fw-bold text-dark mb-3">Community</h3>
                <p class="text-muted mb-4">Booking open! Development focused on lifestyle amenities and commercial convenience for the residents.</p>
                <ul class="list-unstyled mb-0 text-dark">
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Luxury Clubhouse & Pool</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Commercial Mini-Mall</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Landscaped Central Parks</li>
                    <li><i class="fas fa-check-circle text-success me-2"></i> Dedicated Kids Play Area</li>
                </ul>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="phase-card phase-upcoming border">
                <span class="phase-badge bg-white border text-muted">Coming Soon</span>
                <h6 class="text-muted fw-bold mb-1"><i class="fas fa-rocket me-1"></i> Phase 3</h6>
                <h3 class="fw-bold text-dark mb-3">Grandeur</h3>
                <p class="text-muted mb-4">The final phase will complete the township vision with large-scale facilities and advanced living options.</p>
                <ul class="list-unstyled mb-0 text-muted">
                    <li class="mb-2"><i class="fas fa-lock me-2 opacity-50"></i> High-rise Apartments</li>
                    <li class="mb-2"><i class="fas fa-lock me-2 opacity-50"></i> Tie-ups for School & Hospital</li>
                    <li class="mb-2"><i class="fas fa-lock me-2 opacity-50"></i> Extended Villa Layouts</li>
                    <li><i class="fas fa-lock me-2 opacity-50"></i> EV Charging Stations</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5 px-2">
    <div class="bg-primary rounded-4 p-4 p-md-5 d-flex flex-column flex-lg-row align-items-center justify-content-between shadow-lg position-relative overflow-hidden">
        <div class="position-absolute" style="right: -5%; top: -50%; width: 300px; height: 300px; background: rgba(255,255,255,0.1); border-radius: 50%; blur: 20px;"></div>
        
        <div class="text-white position-relative z-1 mb-4 mb-lg-0 pe-lg-5">
            <span class="badge bg-white text-primary rounded-pill px-3 py-2 fw-bold mb-3">Investment Opportunity</span>
            <h3 class="fw-bold mb-2">The Early Bird Advantage</h3>
            <p class="opacity-75 mb-0" style="max-width: 700px;">Properties purchased in Phase 1 & 2 traditionally see a **25% to 40% appreciation** in value by the time Phase 3 launches. Invest today to lock in the lowest foundational prices.</p>
        </div>
        <div class="position-relative z-1 shrink-0 text-lg-end w-100" style="max-width: 200px;">
            <a href="#" class="btn btn-light btn-lg rounded-pill fw-bold w-100 shadow">Book Now</a>
        </div>
    </div>
</div>

<div class="container mb-5 pt-4" id="why-us">
    <div class="text-center mb-5">
        <h2 class="fw-bold mt-2 mb-3 display-6" style="letter-spacing: -1px;">Buy with Zero Anxiety</h2>
        <p class="text-muted mx-auto" style="max-width: 600px;">We eliminate the typical real estate hurdles by offering fully verified, ready-to-register plots and transparent construction processes.</p>
    </div>

    <div class="row g-4 px-2">
        <div class="col-md-6 col-lg-3">
            <div class="feature-box">
                <div class="icon-wrapper bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-file-signature"></i>
                </div>
                <h5 class="fw-bold mb-2">100% Legal Clear</h5>
                <p class="text-muted small m-0">Every inch of land is legally verified. No disputes, clear titles, and immediate registry available.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="feature-box">
                <div class="icon-wrapper bg-success bg-opacity-10 text-success">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h5 class="fw-bold mb-2">Prime Locations</h5>
                <p class="text-muted small m-0">Strategically selected plots in fast-growing corridors. High appreciation value guaranteed.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="feature-box">
                <div class="icon-wrapper bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-compass"></i>
                </div>
                <h5 class="fw-bold mb-2">Vastu Compliant</h5>
                <p class="text-muted small m-0">Carefully planned layouts ensuring proper direction and flow of energy for your prosperity.</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="feature-box">
                <div class="icon-wrapper bg-danger bg-opacity-10 text-danger">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <h5 class="fw-bold mb-2">Zero Brokerage</h5>
                <p class="text-muted small m-0">Deal directly with the company. No hidden commissions, transparent pricing, and flexible plans.</p>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5 py-5 border-top border-bottom">
    <div class="row align-items-center px-2">
        <div class="col-lg-5 mb-4 mb-lg-0">
            <h2 class="fw-bold display-6 mb-3" style="letter-spacing: -1px;">Not just land,<br>it's a lifestyle.</h2>
            <p class="text-muted fs-6 mb-4">When you buy a plot at JankiVilla, you aren't just buying dirt. You are investing in a fully developed ecosystem ready for your dream home.</p>
            <a href="#" class="btn btn-outline-dark rounded-pill px-4 py-2 fw-medium">Contact for Details <i class="fas fa-arrow-right ms-2"></i></a>
        </div>
        <div class="col-lg-6 offset-lg-1">
            <div class="row g-4">
                <div class="col-6">
                    <div class="d-flex align-items-start gap-3">
                        <i class="fas fa-road text-primary fs-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Wide Roads</h6>
                            <p class="text-muted small m-0">20ft to 30ft wide internal pitch roads.</p>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-start gap-3">
                        <i class="fas fa-tint text-primary fs-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Drainage</h6>
                            <p class="text-muted small m-0">Underground drainage system completed.</p>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-start gap-3">
                        <i class="fas fa-bolt text-primary fs-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Electricity</h6>
                            <p class="text-muted small m-0">Poles installed with dedicated transformers.</p>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-start gap-3">
                        <i class="fas fa-shield-alt text-primary fs-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Security</h6>
                            <p class="text-muted small m-0">Gated boundary with 24/7 CCTV surveillance.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="process-section shadow-lg">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold display-6 text-white mb-3" style="letter-spacing: -1px;">Your journey to ownership</h2>
            <p class="text-light opacity-75 mx-auto" style="max-width: 500px;">A seamless, transparent 4-step process from viewing the land to getting the keys to your new life.</p>
        </div>

        <div class="row g-4 px-2">
            <div class="col-md-3">
                <div class="step-number">01</div>
                <h5 class="fw-bold text-white mb-2">Site Visit</h5>
                <p class="text-light opacity-75 small">Schedule a free site visit. Our executives will give you a complete tour of the property and infrastructure.</p>
            </div>
            <div class="col-md-3">
                <div class="step-number">02</div>
                <h5 class="fw-bold text-white mb-2">Select & Block</h5>
                <p class="text-light opacity-75 small">Choose your preferred plot size and location. Pay a nominal token amount to block it instantly.</p>
            </div>
            <div class="col-md-3">
                <div class="step-number">03</div>
                <h5 class="fw-bold text-white mb-2">Legal & Finance</h5>
                <p class="text-light opacity-75 small">Review all property papers. We assist you with easy EMI options and home loans from our banking partners.</p>
            </div>
            <div class="col-md-3">
                <div class="step-number">04</div>
                <h5 class="fw-bold text-white mb-2">Registry & Build</h5>
                <p class="text-light opacity-75 small">Complete the payment and get immediate registry. Choose our combo offer to let us start building your villa.</p>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5 pt-4">
    <div class="text-center mb-5">
        <h2 class="fw-bold display-6 mb-3" style="letter-spacing: -1px;">Voices of Trust</h2>
        <p class="text-muted mx-auto" style="max-width: 600px;">Don't just take our word for it. Hear from the families who have successfully secured their future with JankiVilla.</p>
    </div>

    <div class="row g-4 px-2">
        <div class="col-md-4">
            <div class="testimonial-card">
                <div class="d-flex text-warning mb-3"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p class="text-muted mb-4">"The best part about JankiVilla is their transparency. The legal papers were crystal clear, and the registry was done the very same day. Highly recommended!"</p>
                <div class="d-flex align-items-center">
                    <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center fw-bold me-3" style="width: 45px; height: 45px;">R</div>
                    <div><h6 class="fw-bold mb-0">Rakesh Singh</h6><small class="text-muted">Plot Owner, Phase 1</small></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="testimonial-card">
                <div class="d-flex text-warning mb-3"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
                <p class="text-muted mb-4">"We opted for the Land + Villa combo. The construction quality is top-notch, and the team kept us updated at every stage. It truly feels like a premium society."</p>
                <div class="d-flex align-items-center">
                    <div class="bg-success text-white rounded-circle d-flex justify-content-center align-items-center fw-bold me-3" style="width: 45px; height: 45px;">M</div>
                    <div><h6 class="fw-bold mb-0">Manoj & Aarti Sharma</h6><small class="text-muted">Villa Owners</small></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="testimonial-card">
                <div class="d-flex text-warning mb-3"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p class="text-muted mb-4">"I bought a plot purely for investment. Seeing the pace of development in Phase 1 & 2, I am confident my ROI will be excellent. Very professional team."</p>
                <div class="d-flex align-items-center">
                    <div class="bg-dark text-white rounded-circle d-flex justify-content-center align-items-center fw-bold me-3" style="width: 45px; height: 45px;">A</div>
                    <div><h6 class="fw-bold mb-0">Amitabh Verma</h6><small class="text-muted">Investor</small></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- <div class="container mb-5 pb-4 px-2">
    <div class="cta-section shadow-lg">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2 class="fw-bold display-5 mb-3" style="letter-spacing: -1px;">Ready to build your dream?</h2>
                <p class="text-white opacity-75 fs-5 mb-0">Drop your number below. Our real estate expert will call you back As soon as possible to discuss availability and pricing.</p>
            </div>
            <div class="col-lg-5 offset-lg-1">
                <div class="bg-white p-2 rounded-pill d-flex align-items-center shadow">
                    <input type="tel" class="form-control border-0 shadow-none bg-transparent ps-4" placeholder="Enter your mobile number">
                    <button class="btn btn-dark rounded-pill px-4 py-2 fw-bold">Get a Call</button>
                </div>
                <div class="mt-3 text-center text-lg-end text-white opacity-75 small">
                    <i class="fas fa-lock me-1"></i> Your information is 100% secure.
                </div>
            </div>
        </div>
    </div>
</div> --}}
@endsection