@extends('layout.user-app')

@section('content')
<style>
    /* Hide scrollbar for horizontal scrolling but keep functionality */
    .horizontal-scroll {
        display: flex;
        overflow-x: auto;
        gap: 15px;
        padding-bottom: 10px;
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
    .horizontal-scroll::-webkit-scrollbar {
        display: none; /* Chrome, Safari and Opera */
    }
    
    .property-card {
        min-width: 280px; /* Width for mobile swipe */
        border-radius: 18px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        background: #fff;
        overflow: hidden;
    }
    
    .property-img {
        height: 180px;
        object-fit: cover;
        width: 100%;
        position: relative;
    }

    .badge-price {
        position: absolute;
        bottom: 10px;
        left: 10px;
        background: rgba(255, 255, 255, 0.9);
        padding: 5px 12px;
        border-radius: 10px;
        font-weight: bold;
        color: #0d6efd;
    }

    .category-box {
        background: #fff;
        border-radius: 15px;
        padding: 12px;
        min-width: 85px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        text-decoration: none;
        color: #495057;
        transition: 0.3s ease;
    }
    
    .category-box:hover, .category-box:active {
        background: #0d6efd;
        color: #fff;
    }
    
    .category-icon {
        font-size: 24px;
        margin-bottom: 5px;
    }
</style>

<div class="container-fluid px-3 pt-3">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted mb-0 small">Location</p>
            <h6 class="fw-bold mb-0"><i class="fas fa-map-marker-alt text-primary me-1"></i> Patna, Bihar <i class="fas fa-chevron-down ms-1 small"></i></h6>
        </div>
        <div>
            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center shadow-sm" style="width: 45px; height: 45px; font-weight: bold; font-size: 18px;">
                V
            </div>
        </div>
    </div>

    <h4 class="fw-bold mb-1">Hi, Ved 👋</h4>
    <p class="text-muted mb-4">Find your dream property at JankiVilla</p>

    <div class="input-group mb-4 shadow-sm" style="border-radius: 15px; overflow: hidden;">
        <span class="input-group-text bg-white border-0 ps-3 pe-2 text-muted">
            <i class="fas fa-search"></i>
        </span>
        <input type="text" class="form-control border-0 py-3" placeholder="Search villas, plots, apartments..." style="box-shadow: none;">
        <button class="btn btn-primary px-4 border-0" type="button">
            <i class="fas fa-sliders-h"></i>
        </button>
    </div>

    <div class="d-flex justify-content-between mb-4">
        <h6 class="fw-bold">Categories</h6>
        <a href="#" class="text-primary text-decoration-none small">See all</a>
    </div>
    <div class="horizontal-scroll mb-4">
        <a href="#" class="category-box">
            <div class="category-icon"><i class="fas fa-home text-primary"></i></div>
            <span class="small fw-semibold">Villas</span>
        </a>
        <a href="#" class="category-box">
            <div class="category-icon"><i class="fas fa-building text-warning"></i></div>
            <span class="small fw-semibold">Flats</span>
        </a>
        <a href="#" class="category-box">
            <div class="category-icon"><i class="fas fa-layer-group text-success"></i></div>
            <span class="small fw-semibold">Plots</span>
        </a>
        <a href="#" class="category-box">
            <div class="category-icon"><i class="fas fa-store text-danger"></i></div>
            <span class="small fw-semibold">Shops</span>
        </a>
    </div>

    <div class="d-flex justify-content-between mb-3 mt-2">
        <h6 class="fw-bold">Featured Properties</h6>
    </div>
    
    <div class="horizontal-scroll mb-5">
        
        <div class="property-card">
            <div class="position-relative">
                <img src="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Villa" class="property-img">
                <div class="badge-price">₹ 45.5 Lacs</div>
                <button class="btn btn-light btn-sm rounded-circle position-absolute" style="top: 10px; right: 10px; width: 35px; height: 35px;">
                    <i class="fas fa-heart text-danger"></i>
                </button>
            </div>
            <div class="p-3">
                <h6 class="fw-bold mb-1">Luxury Duplex Villa</h6>
                <p class="text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i> Bailey Road, Patna</p>
                <div class="d-flex justify-content-between border-top pt-2 mt-2">
                    <span class="small text-muted"><i class="fas fa-bed me-1"></i> 3 Beds</span>
                    <span class="small text-muted"><i class="fas fa-bath me-1"></i> 2 Baths</span>
                    <span class="small text-muted"><i class="fas fa-vector-square me-1"></i> 1500 sqft</span>
                </div>
            </div>
        </div>

        <div class="property-card">
            <div class="position-relative">
                <img src="https://images.unsplash.com/photo-1512917774080-9991f1c4c750?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Apartment" class="property-img">
                <div class="badge-price">₹ 32.0 Lacs</div>
                <button class="btn btn-light btn-sm rounded-circle position-absolute" style="top: 10px; right: 10px; width: 35px; height: 35px;">
                    <i class="far fa-heart text-muted"></i>
                </button>
            </div>
            <div class="p-3">
                <h6 class="fw-bold mb-1">Premium 2BHK Flat</h6>
                <p class="text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i> Saguna More, Patna</p>
                <div class="d-flex justify-content-between border-top pt-2 mt-2">
                    <span class="small text-muted"><i class="fas fa-bed me-1"></i> 2 Beds</span>
                    <span class="small text-muted"><i class="fas fa-bath me-1"></i> 2 Baths</span>
                    <span class="small text-muted"><i class="fas fa-vector-square me-1"></i> 1100 sqft</span>
                </div>
            </div>
        </div>

    </div>
    
    <div style="height: 40px;"></div>
</div>
@endsection