@extends('layout.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            
            <!-- Filter Section (Naya Code) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-3">
                    <form id="filterForm" class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <select class="form-select border-0 bg-light" id="filterMonth">
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select border-0 bg-light" id="filterYear">
                                <!-- Dynamic years load karne ke liye -->
                                @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3 text-end">
                            <button type="submit" class="btn btn-primary w-100 rounded-pill shadow-sm">
                                <i class="fas fa-filter me-1"></i> Apply
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Agar koi greeting nahi hai tab ye dikhega -->
            <div id="noGreetingState" class="text-center py-5 d-none">
                <i class="fas fa-box-open fa-3x text-muted mb-3 opacity-50"></i>
                <h5 class="text-secondary fw-medium">No special events found for this month.</h5>
                <p class="text-muted small">Try selecting a different month or year.</p>
            </div>

            <!-- Greeting Cards Container -->
            <div id="greetingsContainer">
                <!-- Data yahan append hoga -->
            </div>
            
            <!-- Loader -->
            <div class="text-center py-5 d-none" id="loadingState">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Confetti Animation Library -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<script>
$(document).ready(function() {
    
    // Default current system month/year set karna
    const currentMonth = new Date().getMonth() + 1;
    $('#filterMonth').val(currentMonth);

    // Celebration Animation Function
    function fireConfetti() {
        var duration = 3 * 1000;
        var animationEnd = Date.now() + duration;
        var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 };

        function randomInRange(min, max) {
            return Math.random() * (max - min) + min;
        }

        var interval = setInterval(function() {
            var timeLeft = animationEnd - Date.now();
            if (timeLeft <= 0) return clearInterval(interval);

            var particleCount = 50 * (timeLeft / duration);
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
        }, 250);
    }

    // Load Greetings Data Function
    function loadGreetings(month, year) {
        $('#greetingsContainer').empty();
        $('#noGreetingState').addClass('d-none');
        $('#loadingState').removeClass('d-none');

        $.ajax({
            url: '/api/v1/my-greetings',
            type: 'GET',
            data: { month: month, year: year },
            success: function(res) {
                $('#loadingState').addClass('d-none');

                if(res.success && res.data.length > 0) {
                    // Agar nayi details hain toh celebrate karo
                    fireConfetti();
                    
                    let html = '';
                    res.data.forEach(function(greeting) {
                        
                        let formattedMessage = greeting.message.replace(/\n/g, '<br>');
                        formattedMessage = formattedMessage.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

                        html += `
                            <div class="card border-0 shadow-lg rounded-4 mb-4 overflow-hidden" style="animation: fadeInUp 0.5s ease-out;">
                                <div class="bg-light px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                                            <i class="fas ${greeting.icon} ${greeting.colorClass} fs-4"></i>
                                        </div>
                                        <h5 class="mb-0 fw-bold text-dark">${greeting.title}</h5>
                                    </div>
                                    <span class="badge bg-secondary rounded-pill px-3 py-2"><i class="far fa-calendar-alt me-1"></i> ${greeting.display_date}</span>
                                </div>
                                <div class="card-body p-4 p-md-5 bg-white text-center">
                                    <p class="card-text fs-5 text-secondary" style="line-height: 1.8;">
                                        ${formattedMessage}
                                    </p>
                                </div>
                                <div class="card-footer bg-white border-top-0 text-center pb-4">
                                    <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="confetti()">
                                        <i class="fas fa-gift me-2"></i> Celebrate More!
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    $('#greetingsContainer').html(html);
                } else {
                    $('#noGreetingState').removeClass('d-none');
                }
            },
            error: function() {
                $('#loadingState').addClass('d-none');
                $('#noGreetingState').removeClass('d-none').find('h5').text('Failed to load greetings.');
            }
        });
    }

    // 1. Initial Load (Default values par)
    loadGreetings($('#filterMonth').val(), $('#filterYear').val());

    // 2. Filter Form Submit Event
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        let selectedMonth = $('#filterMonth').val();
        let selectedYear = $('#filterYear').val();
        loadGreetings(selectedMonth, selectedYear);
    });

});
</script>

<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endpush