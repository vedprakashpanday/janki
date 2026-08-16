@extends('layout.frontend-app')

@section('title', 'Director Message | Amitabh Builders')
@section('meta_description', 'Message from Amitabh Kumar, Chairman & Managing Director of Amitabh Builders & Developers Pvt. Ltd.')

@push('styles')
<style>
    :root {
        --bg-light: #faf9f6;
        --text-dark: #111827;
        --text-muted: #6b7280;
    }
    body { background-color: var(--bg-light); color: var(--text-dark); }
    
    .director-img-wrapper { position: relative; display: inline-block; padding: 20px; }
    .director-img-wrapper::before { content: ''; position: absolute; inset: 0; border: 2px solid var(--primary-gold); border-radius: 20px; transform: translate(15px, 15px); z-index: 0; }
    .director-img { position: relative; z-index: 1; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
    
    .feature-box { background: #fff; padding: 25px; border-radius: 16px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 5px 15px rgba(0,0,0,0.02); height: 100%; }
    .feature-icon { background: var(--text-dark); color: #fff; width: 35px; height: 35px; display: flex; justify-content: center; align-items: center; border-radius: 50%; margin-bottom: 15px; font-size: 14px; }
</style>
@endpush

@section('content')
<section class="container py-5 mt-5">
    <div class="row g-5 align-items-center">
        <!-- Director Image Section -->
        <div class="col-lg-5 text-center text-lg-start">
            <div class="director-img-wrapper mb-4">
                <!-- Replace with actual director image path -->
                <img src="{{ asset('uploads/Director-img.jpeg') }}" alt="Amitabh Kumar - Managing Director" class="director-img">
            </div>
            <div class="text-center" style="max-width: 400px; margin: 0 auto 0 0;">
                <h3 class="fw-bold mb-1 font-family-playfair">Amitabh Kumar</h3>
                <p class="text-warning fw-bold small text-uppercase tracking-wider m-0" style="letter-spacing: 1px;">Chairman & Managing Director</p>
                <p class="text-muted small mt-1">Amitabh Builders & Developers Pvt. Ltd.</p>
            </div>
        </div>

        <!-- Director Message Section -->
        <div class="col-lg-7">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width: 40px; height: 2px; background-color: var(--primary-gold);"></div>
                <span class="text-uppercase fw-bold text-muted" style="letter-spacing: 2px; font-size: 12px;">Director's Message</span>
            </div>
            <h1 class="display-5 fw-bold mb-4 font-family-playfair" style="line-height: 1.2;">Building Foundations of<br><span style="color: var(--primary-gold); font-style: italic;">Trust and Innovation</span></h1>
            
            <p class="text-muted fs-6 mb-4 pb-2 border-bottom">"I wish to express my deepest appreciation to our dedicated team. Their honesty and relentless hard work are the primary drivers of our company's rapid growth and standing in the community."</p>

            <div class="row g-4 mb-4">
                <div class="col-sm-6">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                        <h6 class="fw-bold">Modern Systems</h6>
                        <p class="text-muted small m-0">Implementing AI-driven insights and secure online bookings to make your property journey smooth and transparent.</p>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="feature-box">
                        <div class="feature-icon bg-warning text-dark"><i class="fas fa-star"></i></div>
                        <h6 class="fw-bold">Ethics & Safety</h6>
                        <p class="text-muted small m-0">Strict adherence to RERA guidelines and legal frameworks, ensuring every plot we sell is a safe harbor for your money.</p>
                    </div>
                </div>
            </div>

            <p class="text-muted small mb-3">The real estate world is moving fast. We don't just follow trends, we set them by adopting advanced data analytics and digital platforms to find the best residential opportunities for the people of Darbhanga.</p>
            <p class="text-muted small fw-medium">Every project we undertake is a testament to our promise of quality and transparency. We believe in creating spaces that are not just structures, but legacies for future generations.</p>
        </div>
    </div>
</section>
@endsection