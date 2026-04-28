<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JankiVilla | Coming Soon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;800&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --royal-gold: #B8860B;
            --deep-text: #1a1a1a;
        }

        /* Sabse pehle screen ka jhamela khatam karte hain */
        * {
            box-sizing: border-box;
        }

        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', sans-serif;
            background-color: #f4f4f4;
            /* Scroll allow karenge agar content mobile par bada ho jaye */
            overflow-x: hidden; 
        }

        /* Hero Section: Har device par full screen dikhega */
        .hero-section {
            position: relative;
            min-height: 100vh;
            width: 100%;
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.5)), 
                        url('https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px; /* Mobile par screen se chipkega nahi */
        }

        /* Premium Card: Isko responsive banaya hai */
        .premium-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 40px 5px 40px 5px;
            padding: 3rem 2rem;
            width: 100%;
            max-width: 700px; /* Desktop par size limit */
            text-align: center;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            margin: auto;
        }

        .brand-logo {
            width: 100%;
            max-width: 200px; /* Mobile par logo size */
            height: auto;
            margin-bottom: 1.5rem;
        }

        .status-badge {
            font-size: 0.7rem;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: var(--royal-gold);
            font-weight: 700;
            margin-bottom: 1rem;
            display: block;
        }

        h1 {
            font-family: 'Cinzel', serif;
            /* Fluid Typography: Screen ke hisab se scale hoga */
            font-size: calc(1rem + 1.5vw); 
            color: var(--deep-text);
            font-weight: 800;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .gold-divider {
            width: 60px;
            height: 3px;
            background: var(--royal-gold);
            margin: 1.2rem auto;
        }

        .lead-text {
            color: #333;
            font-weight: 500;
            line-height: 1.6;
            font-size: calc(0.95rem + 0.2vw);
            margin-bottom: 0;
        }

        .footer-info {
            margin-top: 2.5rem;
            font-size: 0.75rem;
            color: #666;
            letter-spacing: 2px;
            font-weight: 600;
        }

        /* Mobile Specific Tweaks */
        @media (max-width: 576px) {
            .premium-card {
                padding: 2.5rem 1.2rem;
                border-radius: 30px 5px 30px 5px;
            }
            .brand-logo {
                max-width: 160px;
            }
            .hero-section {
                padding: 10px;
            }
        }
    </style>
</head>
<body>

    <section class="hero-section">
        <div class="container d-flex justify-content-center">
            
            <div class="premium-card">
                <span class="status-badge">Grand Unveiling</span>
                
                <div class="logo-container">
                    <img src="{{ asset('uploads/harihomes1-logo.png') }}" alt="JankiVilla Logo" class="brand-logo">
                </div>

                <h1>Excellence In Every Detail</h1>
                
                <div class="gold-divider"></div>

                <p class="lead-text">
                    A warm and heartfelt welcome to the <strong>JankiVilla Family</strong>. <br class="d-none d-md-block">
                    Something extraordinary is being crafted for you. Our digital home will be ready to welcome you soon.
                </p>

                <div class="footer-info">
                    &copy; 2026 JANKIVILLA &bull; PREMIUM LIVING
                </div>
            </div>

        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>