<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>ID Card - {{ $data['name'] }}</title>
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card-container {
            display: flex;
            gap: 1cm;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 11cm;
        }

        .id-card {
            width: 5.5cm;
            height: 8.6cm;
            position: relative;
            overflow: hidden;
            border-radius: 0.25cm;
            /* //box-shadow: 0 0.25cm 0.5cm rgba(0, 0, 0, 0.3); */
            transition: transform 0.3s ease;
            border:#000 solid 0.02cm;
        }

        .front,
        .back {
            background-color: #2610b5;
            border: 0.04cm solid black;
            position: relative;
            width: 100%;
            height: 100%;
            overflow: visible;
            border-radius: 0.23cm;
            padding: 0.33cm;
            color: white;
        }

        .content-layer {
            position: relative;
            z-index: 10;
            height: 100%;
        }

        .header {
            border-radius: 0.16cm;
            margin-top: 0.2cm;
            text-align: center;
            margin-bottom: 0.1cm;
            padding: 0;
        }

        .header img {
            border: 0.02cm solid black;
            border-radius: 0.16cm;
            height: 1.25cm;
            max-height: none;
            object-fit: cover;
            text-align: center;
            width: 4cm;
            background: white;
            margin-bottom: -0.3cm;
        }

       .photo {
    width: 3.3cm;
    height: 3.3cm;
    border-radius: 50%;
    border: 0.07cm solid white;
    box-shadow: 0 0.13cm 0.33cm rgba(0, 0, 0, 0.4);
    display: block;
    margin: 0 auto 0.4cm;
    object-fit: cover;
}

        .info-list {
            list-style: none;
            padding: 0;
            margin: 3px 0px;
            text-align: center;
        }

        img, svg {
    vertical-align: top;
}

        .info-list li {
            margin-bottom: 0.05cm;
            font-size: 0.21cm;
        }

        .info-list li:nth-child(1) h1 {
            font-size: 0.33cm;
            font-weight: 800;
            margin-bottom: 0.09cm;
            text-transform: uppercase;
        }

        .info-list li:nth-child(2) {
            font-size: 0.25cm;
            font-weight: 700;
        }

        .shape {
            position: absolute;
            background: white;
            border-radius: 50%;
            z-index: 1;
        }

        .shape.main {
            width: 9.2cm;
            height: 6.8cm;
            border: 0.09cm solid black;
            left: -0.5cm;
            top: 4cm;
        }

        .shape.secondary {
            width: 6.9cm;
            height: 6.6cm;
            left: -2.4cm;
            top: -2.6cm;
        }

        .back .shape.main {
            right: -0.7cm;
            left: auto;
        }

        .back .shape.secondary {
            right: -2.4cm;
            left: auto;
        }

        .signature-box {
            position: relative;
            margin-left: 2.4cm;
            width: fit-content;
            text-align: center;
            z-index: 20;
            border-radius: 50%;
        }

        /* 🔥 MIX BLEND MODE: MULTIPLY ensures white background goes transparent 🔥 */
        .signature-box img {
    width: 1.8cm;
    height: 0.8cm;
    margin-top: 0.1cm;
    object-fit: contain;
    mix-blend-mode: multiply;
    margin-bottom: -0.2cm;
    filter: brightness(1.11);
}

        .signature-text {
            font-size: 0.16cm;
            color: #000;
            margin-top: 0.05cm;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 0.03cm;
        }

        .details-box {
    background-color: white;
    color: black;
    font-weight: 500;
    border: 0.015cm solid black;
    border-radius: 0.08cm;
    padding: 0.20cm 0.15cm;
    margin: 0.5cm auto 0.2cm;
    font-size: 0.17cm;
    letter-spacing: 0.0001cm;
    line-height: 2.2;
    list-style: none;
    width: 4.8cm;
    text-align: left;
    box-shadow: 0 0.02cm 0.05cm rgba(0, 0, 0, 0.2);
    display: flex
;
    flex-direction: column;
    justify-content: space-between;
}

        .details-box li {
            display: flex;
            margin-bottom: 0.015cm;
            align-items: flex-start;
            flex: 1;
        }

        .details-box .label {
            width: 1.5cm;
            display: inline-block;
            text-align: left;
            flex-shrink: 0;
            font-size: 7px;
        }

        .details-box .value {
            font-weight: 700;
            flex: 1;
            margin-left: 0.05cm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 7px;
        }

        @media print {
            body {
                padding: 0;
                background: transparent !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .front,
            .back {
                background-color: #2610b5 !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                position: relative !important;
                z-index: 1 !important;
            }

            .shape {
                z-index: 10 !important;
                background-color: white !important;
                -webkit-print-color-adjust: exact !important;
            }

            .content-layer {
                z-index: 20 !important;
                position: relative !important;
            }

            .card-container {
                gap: 1cm !important;
            }

            button {
                display: none !important;
            }
        }
    </style>
    <script>
        if (window.self !== window.top) { // Agar modal (iframe) me khula hai
            // 1. Button aur CSS Print ko completely block karo
            document.write(`
                <style> 
                    .print-btn-container, button { display: none !important; } 
                    @media print { body { display: none !important; } } 
                </style>
            `);
            
            // 2. Right Click & Shortcuts Block
            document.addEventListener('contextmenu', e => e.preventDefault());
            document.addEventListener('keydown', e => {
                if(e.key === 'PrintScreen' || (e.ctrlKey && (e.key === 'p' || e.key === 'P' || e.key === 's'))) {
                    e.preventDefault();
                }
            });
        }
    </script>
</head>

<body>
    <button onclick="window.print()"
        style="background: #2610b5; color: white; border: none; padding: 12px 24px; font-size: 16px; font-weight: 600; border-radius: 8px; cursor: pointer; margin-top: 20px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(38,16,181,0.3);">
        🖨️ Print ID Card
    </button>
    <div class="card-container">
        <!-- FRONT SIDE -->
        <div class="id-card front-side">
            <div class="front">
                <div class="content-layer">
                    <div class="header">
                        <img src="{{ $data['company_logo'] }}" alt="Company Logo"> <br>
                          <span style="color: red; font-size: 5.5px; font-weight: bold;" >(An ISO {{ $data['iso_no'] }} Certified Company)</span>
                    </div>
                  
                    <img src="{{ $data['photo_url'] }}" alt="{{ $data['name'] }}" class="photo">
                    <ul class="info-list">
                        <li>
                            <h1 style="color: red;">{{ $data['name'] }}</h1>
                        </li>

                        <!-- 🔥 Dynamic Employee/Member Text 🔥 -->
                        <li style="color: #2610b5;">{{ $data['designation'] }}</li>

                        <!-- 🔥 Dynamic Code Label 🔥 -->
                        <li
                            style="color: #2610b5; border-top: 0.013cm solid #2610b5; border-bottom: 0.013cm solid #2610b5;">
                            {{ $data['code_label'] }} - {{ $data['id'] }}
                        </li>

                        <!-- 🔥 Company Name (Auto fit single line) 🔥 -->
                        <li
                            style="color: red; margin-bottom: 0; font-weight:800; font-size: 8px; width: 100%; white-space: nowrap; display: block;">
                            {{ strtoupper($data['company_name']) }}
                        </li>

                        <li style="color: black; font-weight: 600; font-size: 7px;">CIN NO.: {{ $data['cin_no'] }}</li>

                        <!-- 🔥 Contact: Employee/Member + Company Phone 🔥 -->
                        <li style="color: blue; font-weight: 600;">
                            Contact No. - {{ $data['mobile'] }} @if ($data['company_phone'])
                                {{-- / {{ $data['company_phone'] }} --}} / 9031079721
                            @endif
                        </li>
                    </ul>
                </div>
                <div class="shape main"></div>
                <div class="shape secondary"></div>
            </div>
        </div>

        <!-- BACK SIDE -->
        <div class="id-card back-side">
            <div class="back">
                <div class="content-layer">
                    <div class="header">
                        <img src="{{ $data['company_logo'] }}" alt="Company Logo">
                        <br>
                        <span style="color: red; font-size: 5.5px; font-weight: bold;" >(An ISO {{ $data['iso_no'] }} Certified Company)</span>
                    </div>
                    <ul class="details-box">
                        <li><span class="label">Father's Name</span><span class="value">:
                                {{ $data['father_name'] }}</span></li>
                        <li><span class="label">Date of Birth:</span><span class="value">: {{ $data['dob'] }}</span>
                        </li>
                        <li><span class="label">Aadhar No.:</span><span class="value">: {{ $data['aadhar'] }}</span>
                        </li>
                        <li><span class="label">Blood Group:</span><span class="value">:
                                {{ $data['blood_group'] }}</span></li>
                    </ul>

                    <div class="signature-box">
                        @if ($data['signature'])
                            <img src="{{ $data['signature'] }}" alt="CEO Signature">
                        @else
                            <img src="{{ asset('member_document/sign-removebg-preview.png') }}"
                                alt="Authorised Signature">
                        @endif
                        <div class="signature-text">Authorised Signatory</div>
                    </div>

                    <ul class="info-list">
                        <li
                            style="text-decoration: underline; color: red; font-size: 0.2cm; font-weight: 700; margin-bottom: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ strtoupper($data['company_name']) }}
                        </li>
                        <li style="color: black; font-size: 0.16cm; font-weight: 700; margin-top: 0;">
                            CIN No.: {{ $data['cin_no'] }}
                        </li>
                    </ul>

                    <div style="display: flex;">
                        <div>
                            <svg width="0.18cm" height="0.22cm" viewBox="0 0 24 24" fill="#2610b5"
                                style="margin-right: 0.01cm; margin-top: 0.01cm; color: red;">
                                <path
                                    d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                            </svg>
                        </div>
                        <div>
                            <!-- 🔥 Address Gap Fixed: Removed extra margins/padding 🔥 -->
                            <ol
                                style="z-index: 10; position: relative; color: #2610b5; list-style: none; padding: 0; margin: 0 0 0 0; font-size: 0.16cm; font-family: 'Segoe UI', Tahoma, Arial, sans-serif; text-align:center;">
                                <li
                                    style="white-space: no-wrap; font-weight: 550; font-size: 0.2cm; line-height:1.2; margin-bottom: 0.03cm;">
                                    {{ $data['company_address'] }}
                                </li>
                                <li
                                    style="white-space: nowrap; text-overflow: ellipsis; height: 0.25cm; font-weight: 600; font-size: 0.21cm; margin-bottom: 0.02cm;">
                                    Email: {{ $data['company_email'] }}
                                </li>
                                <!-- 🔥 Contact: Employee/Member + Company Phone 🔥 -->
                        <li style="color: blue; font-weight: 700;    font-size: 8px;">
                            Contact No. - {{ $data['mobile'] }} @if ($data['company_phone'])
                                {{-- / {{ $data['company_phone'] }} --}} / 9031079721
                            @endif
                        </li>
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="shape main"></div>
                <div class="shape secondary"></div>
            </div>
        </div>
    </div>
</body>

</html>
