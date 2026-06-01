@extends('layout.app')

@section('content')
<div class="container-fluid p-0">
    
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Company Overview</h4>
            <p class="text-secondary small mb-0">Track your real-time business metrics here.</p>
        </div>
        <div class="d-none d-md-block">
            <button class="btn btn-primary btn-sm px-3 secured-item" data-permission="dashboard_export"><i class="fas fa-download me-2"></i> Export Report</button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="card border border-light-subtle shadow-sm rounded-3 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-uppercase fw-bold text-secondary" style="font-size: 11px; letter-spacing: 1px;">Total Revenue</div>
                        <div class="text-success bg-success bg-opacity-10 p-2 rounded" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold text-dark mb-1">₹ 0.00</h2>
                    <span class="badge bg-success bg-opacity-10 text-success fw-medium"><i class="fas fa-arrow-up me-1"></i>0% from last month</span>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-4">
            <div class="card border border-light-subtle shadow-sm rounded-3 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-uppercase fw-bold text-secondary" style="font-size: 11px; letter-spacing: 1px;">Active Associates</div>
                        <div class="text-primary bg-primary bg-opacity-10 p-2 rounded" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user-tie"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold text-dark mb-1">0</h2>
                    <span class="text-secondary small">Registered in system</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border border-light-subtle shadow-sm rounded-3 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-uppercase fw-bold text-secondary" style="font-size: 11px; letter-spacing: 1px;">Pending Actions</div>
                        <div class="text-warning bg-warning bg-opacity-10 p-2 rounded" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold text-dark mb-1">0</h2>
                    <span class="text-secondary small">Requires admin approval</span>
                </div>
            </div>
        </div>
    </div>

</div>


@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const token = localStorage.getItem('admin_token');
        if (!token) {
            window.location.href = '/admin/login'; 
        }

        
    });
</script>
@endpush