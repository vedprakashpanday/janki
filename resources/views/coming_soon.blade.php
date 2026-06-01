<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Residences | Coming Soon</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts for Premium Look (Playfair Display & Montserrat) -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body, html { 
            height: 100%; 
            margin: 0; 
            font-family: 'Montserrat', sans-serif;
            background-color: #111;
        }
        
        .premium-bg {
            /* Dark gradient overlay with a luxury real estate background image */
            background: linear-gradient(to right, rgba(0, 0, 0, 0.85), rgba(0, 0, 0, 0.4)), 
                        url('https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #fff;
        }

        h1, .brand-name {
            font-family: 'Playfair Display', serif;
        }

        .text-gold {
            color: #D4AF37; /* Luxury Gold Color */
        }

        .border-gold {
            border-color: #D4AF37 !important;
        }

        .letter-spacing {
            letter-spacing: 3px;
            text-transform: uppercase;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .contact-info {
            font-weight: 300;
            font-size: 0.95rem;
        }

        .social-icons a {
            color: #fff;
            font-size: 1.2rem;
            transition: 0.3s ease;
        }

        .social-icons a:hover {
            color: #D4AF37;
            transform: translateY(-3px);
        }

        /* Subtle Fade-in Animation */
        .fade-in {
            animation: fadeIn ease 2s;
        }

        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>

<div class="premium-bg fade-in">
    
    <!-- Header / Brand Area -->
    <header class="p-4 p-md-5 d-flex justify-content-between align-items-center">
        <!-- Aap yahan Apna Logo laga sakte hain -->
        <div class="brand-name fs-2 fw-bold text-gold">JankiVilla</div>
        <div class="letter-spacing d-none d-md-block text-white-50">Exclusive Residences</div>
    </header>

    <!-- Main Content Area -->
    <main class="container text-center text-md-start px-4 px-md-5 mb-auto mt-auto">
        <div class="row">
            <div class="col-lg-8 col-xl-7">
                <p class="letter-spacing text-gold mb-3"><i class="fas fa-key me-2"></i> Arriving Soon</p>
                <h1 class="display-3 fw-bold mb-4">Redefining Luxury Living.</h1>
                <p class="lead fw-light mb-5 text-white-50" style="max-width: 600px; line-height: 1.8;">
                    An exclusive collection of premium spaces designed for those who appreciate the finer things in life. Architectural brilliance meets unmatched comfort.
                </p>
                
                <!-- Information / Contact (No Forms) -->
                {{-- <div class="contact-info mt-5 border-start border-gold border-3 ps-4 py-2">
                    <p class="mb-2"><i class="fas fa-phone-alt text-gold me-3"></i> +91 98765 43210</p>
                    <p class="mb-2"><i class="fas fa-envelope text-gold me-3"></i> info@jankivilla.com</p>
                    <p class="mb-0"><i class="fas fa-map-marker-alt text-gold me-3"></i> Prime Location, City Name</p>
                </div> --}}
            </div>
        </div>
    </main>

    <!-- Footer Area -->
    {{-- <footer class="p-4 p-md-5 mt-auto d-flex flex-column flex-md-row justify-content-between align-items-center" style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);">
        <p class="mb-3 mb-md-0 contact-info text-white-50">
            &copy; {{ date('Y') }} JankiVilla. All Rights Reserved.
        </p>
        <div class="social-icons">
            <a href="#" class="me-4"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="me-4"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
        </div>
    </footer> --}}

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>