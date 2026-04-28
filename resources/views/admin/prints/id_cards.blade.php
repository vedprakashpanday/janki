<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>ID Card - {{ $data['name'] }}</title>
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 40px 20px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card-container { display: flex; gap: 1cm; flex-wrap: wrap; justify-content: center; max-width: 11cm; }
        .id-card { width: 5cm; height: 8.3cm; position: relative; overflow: hidden; border-radius: 0.25cm; box-shadow: 0 0.25cm 0.5cm rgba(0, 0, 0, 0.3); transition: transform 0.3s ease; }
        .id-card:hover { transform: scale(1.02); }
        .front, .back { background-color: #2610b5; border: 0.04cm solid black; position: relative; width: 100%; height: 100%; overflow: visible; border-radius: 0.23cm; padding: 0.33cm; color: white; }
        .content-layer { position: relative; z-index: 10; height: 100%; }
        .header { border-radius: 0.16cm; margin-top: 0.2cm; text-align: center; margin-bottom: 0.6cm; padding: 0; }
        .header img { border: 0.02cm solid black; border-radius: 0.16cm; height: 1cm; max-height: none; object-fit: cover; text-align: center; width: 3cm; }
        .photo { width: 2.5cm; height: 2.5cm; border-radius: 50%; border: 0.07cm solid white; box-shadow: 0 0.13cm 0.33cm rgba(0, 0, 0, 0.4); display: block; margin: 0 auto 0.4cm; object-fit: cover; }
        .info-list { list-style: none; padding: 0; margin: 0; text-align: center; }
        .info-list li { margin-bottom: 0.14cm; font-size: 0.21cm; }
        .info-list li:nth-child(1) h1 { font-size: 0.37cm; font-weight: 800; margin-bottom: 0.09cm; }
        .info-list li:nth-child(2) { font-size: 0.26cm; font-weight: 700; }
        .shape { position: absolute; background: white; border-radius: 50%; z-index: 1; }
        .shape.main { width: 9.2cm; height: 6.6cm; border: 0.09cm solid black; left: -0.42cm; top: 4cm; }
        .shape.secondary { width: 6.6cm; height: 6.6cm; left: -2.4cm; top: -2.6cm; }
        .back .shape.main { right: -0.7cm; left: auto; }
        .back .shape.secondary { right: -2.4cm; left: auto; }
        .signature-box { position: relative; margin-left: 2.4cm; width: fit-content; text-align: center; z-index: 20; border-radius: 50%; }
        .signature-box img { width: 1.8cm; height: 0.8cm; margin-top: 0.1cm; filter: brightness(1.5) contrast(8) saturate(0); border-radius: 50%; }
        .signature-text { font-size: 0.16cm; color: #000; margin-top: 0.05cm; }
        .details-box { background-color: white; color: black; font-weight: 500; border: 0.015cm solid black; border-radius: 0.08cm; padding: 0.20cm 0.15cm; margin: 1.2cm auto 0.10cm; font-size: 0.17cm; letter-spacing: 0.0001cm; line-height: 2; list-style: none; width: 4.37cm; text-align: left; box-shadow: 0 0.02cm 0.05cm rgba(0, 0, 0, 0.2); display: flex; flex-direction: column; justify-content: space-between; }
        .details-box li { display: flex; margin-bottom: 0.015cm; align-items: flex-start; flex: 1; }
        .details-box .label { width: 1.25cm; display: inline-block; text-align: left; flex-shrink: 0; font-size: 6px; }
        .details-box .value { font-weight: 700; flex: 1; margin-left: 0.05cm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        @media print {
            #backcontact { font-size: 0.16cm !important; }
            body { padding: 0; background: transparent !important; -webkit-print-color-adjust: exact !important; color-adjust: exact !important; }
            .front, .back { background-color: #2610b5 !important; -webkit-print-color-adjust: exact !important; color-adjust: exact !important; position: relative !important; z-index: 1 !important; }
            .shape { z-index: 10 !important; background-color: white !important; -webkit-print-color-adjust: exact !important; }
            .content-layer { z-index: 20 !important; position: relative !important; }
            .card-container { gap: 1cm !important; }
            .id-card:hover { transform: none !important; }
            button { display: none !important; }
        }
    </style>
</head>
<body>
    <button onclick="window.print()" style="background: #2610b5; color: white; border: none; padding: 12px 24px; font-size: 16px; font-weight: 600; border-radius: 8px; cursor: pointer; margin-top: 20px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(38,16,181,0.3); font-family: 'Segoe UI', sans-serif;">
        🖨️ Print ID Cards
    </button>
    <div class="card-container">
        <div class="id-card front-side">
            <div class="front">
                <div class="content-layer">
                    <div class="header">
                        <img src="{{ asset('uploads/harihomes1-logo.png') }}" alt="Company">
                    </div>
                    <img src="{{ $data['photo_url'] }}" alt="{{ $data['name'] }}" class="photo">
                    <ul class="info-list">
                        <li><h1 style="color: red;">{{ $data['name'] }}</h1></li>
                        <li style="color: #2610b5;">{{ $data['designation'] }}</li>
                        <li style="color: #2610b5; border-top: 0.013cm solid #2610b5; border-bottom: 0.013cm solid #2610b5;">Employee Code - {{ $data['id'] }}</li>
                        <li style="color: red; margin-bottom: 0;font-weight:700;font-size: 7px;">AMITABH BUILDERS & DEVELOPERS PVT. LTD.</li>
                        <li style="color: black;font-weight: 550;font-size: 7px;">CIN NO.: U43299BR2024PTC072712</li>
                        <li style="color: blue;font-weight: 500;">Contact No. - {{ $data['mobile'] }} & 9031079721</li>
                    </ul>
                </div>
                <div class="shape main"></div>
                <div class="shape secondary"></div>
            </div>
        </div>

        <div class="id-card back-side">
            <div class="back">
                <div class="content-layer">
                    <div class="header">
                        <img src="{{ asset('uploads/harihomes1-logo.png') }}" alt="Company">
                    </div>
                    <ul class="details-box">
                        <li><span class="label">Father's Name</span><span class="value">: {{ $data['father_name'] }}</span></li>
                        <li><span class="label">Date of Birth:</span><span class="value">: {{ $data['dob'] }}</span></li>
                        <li><span class="label">Aadhar No.:</span><span class="value">: {{ $data['aadhar'] }}</span></li>
                        <li><span class="label">Blood Group:</span><span class="value">: {{ $data['blood_group'] }}</span></li>
                    </ul>

                    <div class="signature-box">
                        <img src="{{ asset('member_document/sign-removebg-preview.png') }}" alt="Authorised Signature">
                        <div class="signature-text">Authorised Signatory</div>
                    </div>

                    <ul class="info-list">
                        <li style="text-decoration: underline; color: red; font-size: 0.19cm; font-weight: 700;margin-bottom: 0;">AMITABH BUILDERS & DEVELOPERS PVT. LTD.</li>
                        <li style="color: black; font-size: 0.16cm; font-weight: 700;margin-top: 0;">CIN No.:U43299BR2024PTC072712</li>
                    </ul>

                    <div style="display: flex;">
                        <div><svg width="0.18cm" height="0.22cm" viewBox="0 0 24 24" fill="#2610b5" style="margin-right: 0.01cm; margin-top: 0.02cm;color: red;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" /></svg></div>
                        <div>
                            <ol style="z-index: 10; position: relative; color: #2610b5; list-style: none; padding: 0; margin: 0.1cm 0 0 0.1cm; font-size: 0.16cm; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">
                                <li style="white-space: nowrap; text-overflow: ellipsis; height: 0.25cm; margin-bottom: 0.06cm; font-weight: 500; font-size: 0.19cm;">1st. Floor, Pappu Yadav Building</li>
                                <li style="white-space: nowrap; text-overflow: ellipsis; height: 0.25cm; margin-bottom: 0.06cm; font-weight: 500; font-size: 0.19cm;">Kakarghati Chowk, Darbhanga (Bihar) - 846007</li>
                                <li style="white-space: nowrap; text-overflow: ellipsis; height: 0.25cm; font-weight: 500; font-size: 0.22cm; margin-bottom: 0.06cm;">Email Id: abdeveloperspl@gmail.com</li>
                                <li style="white-space: nowrap; text-overflow: ellipsis; height: 0.25cm; font-weight: 550; font-size: 0.18cm; margin-bottom: 0.06cm; color:#000;" id="backcontact">Contact No.-9060218-222/333/666 & 9472467007</li>
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