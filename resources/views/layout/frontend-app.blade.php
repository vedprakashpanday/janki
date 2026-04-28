<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>JankiVilla | Exclusive Real Estate</title>
    <link rel="shortcut icon" href="{{asset('uploads/harihomes1-fevicon.png')}}" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --surface-color: #ffffff;
            --text-main: #111827;
            --text-muted: #6b7280;
            --accent-color: #111827; 
            --bg-light: #f9fafb;
        }

        body { 
            font-family: 'Outfit', sans-serif; 
            background-color: var(--bg-light);
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
            padding-bottom: 0;
        }

        /* --- Desktop Header --- */
        .premium-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            position: sticky; top: 0; z-index: 1040;
        }
        .brand-logo { font-weight: 800; font-size: 22px; color: var(--text-main); text-decoration: none; letter-spacing: -0.5px; }
        .desktop-nav a { color: var(--text-muted); font-weight: 500; margin: 0 15px; text-decoration: none; transition: 0.2s; }
        .desktop-nav a:hover, .desktop-nav a.active { color: var(--text-main); }
        .btn-premium { background: var(--text-main); color: #fff; border-radius: 30px; padding: 10px 24px; font-weight: 500; font-size: 14px; transition: 0.3s; }
        .btn-premium:hover { background: #374151; color: #fff; }

        /* --- Mobile Bottom App Bar --- */
        .mobile-bottom-bar { display: none; }

        @media (max-width: 768px) {
            body { padding-bottom: 85px; } 
            .desktop-nav, .desktop-actions { display: none !important; } 
            
            .mobile-bottom-bar {
                display: flex; position: fixed; bottom: 0; left: 0; right: 0;
                background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(15px);
                border-top: 1px solid rgba(0,0,0,0.08);
                justify-content: space-around; align-items: center;
                padding: 12px 10px; padding-bottom: calc(12px + env(safe-area-inset-bottom)); 
                z-index: 1050;
            }
            .mobile-bottom-bar a { text-align: center; color: var(--text-muted); text-decoration: none; font-size: 11px; font-weight: 500; flex: 1; transition: color 0.2s; }
            .mobile-bottom-bar a i { display: block; font-size: 22px; margin-bottom: 4px; }
            .mobile-bottom-bar a.active { color: var(--accent-color); }
        }

        /* --- Floating Actions (WhatsApp & Chatbot Toggle) --- */
        .floating-actions {
            position: fixed;
            bottom: 30px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            z-index: 1060;
        }

        /* Adjust bottom position for mobile so it doesn't hide behind the bottom nav */
        @media (max-width: 768px) {
            .floating-actions {
                bottom: 90px; /* Push above bottom app bar */
                right: 15px;
            }
        }

        .float-btn {
            width: 55px; height: 55px;
            border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            font-size: 24px; color: white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            cursor: pointer; transition: 0.3s;
            border: none;
            text-decoration: none;
        }
        .float-btn:hover { transform: scale(1.1); color: white; }
        
        .btn-whatsapp { background: #25d366; }
        .btn-chat { background: var(--accent-color); }

        /* --- AI Chatbot Window UI (Reverb Ready) --- */
        .chat-window {
            position: fixed;
            bottom: 100px;
            right: 20px;
            width: 350px;
            height: 450px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
            border: 1px solid rgba(0,0,0,0.05);
            display: none; /* Hidden by default */
            flex-direction: column;
            z-index: 1070;
            overflow: hidden;
            transform-origin: bottom right;
            animation: popIn 0.3s ease-out forwards;
        }

        @keyframes popIn {
            0% { opacity: 0; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
        }

        @media (max-width: 768px) {
            .chat-window {
                width: calc(100% - 30px);
                right: 15px;
                bottom: 160px; /* Adjust for mobile */
                height: 400px;
            }
        }

        .chat-header {
            background: var(--text-main);
            color: #fff;
            padding: 15px 20px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .chat-body {
            flex: 1;
            padding: 15px;
            background: #f9fafb;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .chat-msg {
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 15px;
            font-size: 14px;
            line-height: 1.4;
        }
        .msg-ai { background: #fff; border: 1px solid #e5e7eb; align-self: flex-start; border-bottom-left-radius: 4px; }
        .msg-user { background: var(--text-main); color: #fff; align-self: flex-end; border-bottom-right-radius: 4px; }
        
        .chat-footer {
            padding: 15px;
            background: #fff;
            border-top: 1px solid #e5e7eb;
            display: flex; gap: 10px;
        }
        .chat-input {
            flex: 1; border: none; background: #f3f4f6; padding: 10px 15px; border-radius: 20px; font-size: 14px; outline: none;
        }
        .chat-send { background: transparent; border: none; color: var(--text-main); font-size: 20px; cursor: pointer; }

        .pro-footer { background: #111827; color: #9ca3af; padding: 60px 0 30px; font-size: 14px; }
        .pro-footer h5 { color: #fff; font-weight: 600; margin-bottom: 20px; font-size: 16px; }
        .pro-footer a { color: #9ca3af; text-decoration: none; display: block; margin-bottom: 10px; transition: 0.2s; }
        .pro-footer a:hover { color: #fff; }
    </style>
</head>
<body>

    <header class="premium-header py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="/" class="brand-logo"><img src="{{asset('uploads/harihomes1-logo.png')}}" alt="Company Logo" width="130"></a>
            <nav class="desktop-nav">
                <a href="/" class="active">Explore</a>
                <a href="#">Plots</a>
                <a href="#">Villas</a>
                <a href="#">Company</a>
            </nav>
            <div class="desktop-actions">
                <a href="#" class="text-dark me-4 fw-medium text-decoration-none">Log in</a>
                <a href="#" class="btn btn-premium">Book Visit</a>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="pro-footer mt-5">
        <div class="container">
            <div class="row g-4 mb-5">
                <div class="col-lg-4 col-12">
                   
                        <img src="{{asset('uploads/harihomes1-logo.png')}}" alt="Company Logo" width="150" class="mb-3" style="filter: invert(1);">
                    
                    <p class="mt-3 pe-md-4">Elevating the standard of living through meticulously planned plots and architecturally superior villas in Bihar.</p>
                </div>
                <div class="col-lg-2 col-6">
                    <h5>Properties</h5>
                    <a href="#">Premium Plots</a>
                    <a href="#">Luxury Villas</a>
                    <a href="#">Custom Combos</a>
                </div>
                <div class="col-lg-2 col-6">
                    <h5>Company</h5>
                    <a href="#">About CEO</a>
                    <a href="#">Our Partners</a>
                </div>
                <div class="col-lg-4 col-12">
                    <h5>Contact</h5>
                    <p class="mb-1">Janki Villa, Amitabh Builders & Developers Pvt. Ltd. <br> 1st Floor, Pappu Yadav Building, South of NH-27, Kakarghati Chowk,Bhuskaul, Darbhanga,Bihar- 846007</p>
                    <p class="mb-1">abdeveloperspl@gmail.com</p>
                    <p class="text-white fw-medium mt-2">+91 94724 67007</p>
                </div>
            </div>
            <div class="border-top border-secondary pt-4 d-flex flex-column flex-md-row justify-content-between align-items-center">
                <p class="mb-0 text-center w-100">© 2026 JankiVilla. All rights reserved. | Developed By POBS Freelancer</p>
            </div>
            <div style="height: 60px;" class="d-md-none"></div>
        </div>
    </footer>

    <div class="mobile-bottom-bar shadow-lg">
        <a href="/" class="active"><i class="fas fa-compass"></i>Explore</a>
        <a href="#plots"><i class="fas fa-map"></i>Plots</a>
        <a href="#villas"><i class="fas fa-home"></i>Villas</a>
        <a href="#profile"><i class="fas fa-user"></i>Profile</a>
    </div>

    <div class="floating-actions">
        <a href="https://wa.me/919031079721" target="_blank" class="float-btn btn-whatsapp" title="Chat on WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a>
        <button class="float-btn btn-chat" id="toggleChat" title="AI Assistant">
            <i class="fas fa-comment-dots"></i>
        </button>
    </div>

    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <div>
                <h6 class="mb-0 fw-bold"><i class="fas fa-robot me-2"></i>JankiVilla AI</h6>
                <small class="text-white-50"><span class="text-success">●</span> Online</small>
            </div>
            <button class="btn btn-sm text-white border-0 shadow-none" id="closeChat"><i class="fas fa-times fs-5"></i></button>
        </div>
        <div class="chat-body" id="chatBody">
            <div class="chat-msg msg-ai">
                Hi there! 👋 I'm Neha Mishra. Looking for a plot or a ready-to-move villa?
            </div>
            </div>
        <div class="chat-footer">
            <input type="text" class="chat-input" id="chatInput" placeholder="Type a message...">
            <button class="chat-send" id="btnSendMsg"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Toggle Chat Window
            $('#toggleChat').click(function() {
                $('#chatWindow').css('display', 'flex');
                $('#toggleChat').hide(); // Hide toggle button when chat is open
            });

            // Close Chat Window
            $('#closeChat').click(function() {
                $('#chatWindow').hide();
                $('#toggleChat').show();
            });

            // Basic Send Message Logic (To be hooked with Laravel Echo/Reverb later)
            function sendMessage() {
                let msg = $('#chatInput').val().trim();
                if(msg !== "") {
                    // Append User Message
                    $('#chatBody').append(`<div class="chat-msg msg-user">${msg}</div>`);
                    $('#chatInput').val('');
                    
                    // Auto-scroll to bottom
                    $('#chatBody').scrollTop($('#chatBody')[0].scrollHeight);

                    /* TODO for Reverb: 
                       Yahan par axios.post('/api/send-message') call karein, 
                       aur Echo.channel('chat').listen('MessageSent') me 
                       msg-ai append karne ka logic likhein.
                    */
                }
            }

            $('#btnSendMsg').click(sendMessage);
            $('#chatInput').keypress(function(e) {
                if(e.which == 13) sendMessage();
            });
        });
    </script>
</body>
</html>