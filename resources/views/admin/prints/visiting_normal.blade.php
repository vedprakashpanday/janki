<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visiting Card - {{ $data['company_name'] }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #e9ecef; display: flex; flex-direction: column; align-items: center; padding: 40px 20px; }
        .biz-card { width: 3.5in; height: 2.1in; background: #fff; border-radius: 6px; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); margin-bottom: 30px; position: relative; display: flex; flex-direction: column; border-top: 5px solid #1a4b8c; border-bottom: 5px solid #1a4b8c; overflow: hidden; }
        .card-inner { padding: 10px 12px 0 12px; flex-grow: 1; display: flex; flex-direction: column; }
        .logo-area img { max-height: 42px; width: auto; object-fit: contain; }
        .iso-text { color: #d32f2f; font-size: 6.5px; font-weight: 700; text-align: center; margin-bottom: 5px; }
        .projects-title { color: #d32f2f; font-size: 8.5px; font-weight: 700; margin-bottom: 5px; border-bottom: 1px solid #ddd; }
        .project-badge { border: 1px solid #2e7d32; border-radius: 4px; padding: 3px; text-align: center; background: #f0fff0; }
        .project-badge h5 { color: #2e7d32; font-weight: 800; margin: 0; font-size: 10px; }
        .project-badge p { color: #1b5e20; font-size: 6px; margin: 0; font-weight: 600; }
        .header-split { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 5px; }
        .person-info { text-align: right; margin-top: 5px; }
        .person-name { color: #1a4b8c; font-weight: 800; font-size: 10px; margin: 0; }
        .person-title { font-size: 7.5px; font-weight: 600; color: #555; text-transform: uppercase; }
        .contact-list { list-style: none; padding: 0; margin: 2px 0 0 42%; font-size: 8.5px; }
        .contact-list li { margin-bottom: 3px; display: flex; align-items: center; font-weight: 500; }
        .contact-list i { color: #cda434; width: 18px; font-size: 9px; }
        .company-name-red { color: #d32f2f; font-weight: 800; text-align: center; font-size: 9.5px; margin: 5px 0 2px 0; padding: 4px 0; border-top: 1.5px solid #1a4b8c; border-bottom: 1.5px solid #1a4b8c; }
        .footer-info { background-color: #f8f9fa; padding: 5px 8px; text-align: center; font-size: 7px; font-weight: 600; color: #1a4b8c; line-height: 1.3; }
        .whatsapp-icon { color: #25D366; font-size: 7px; }

        @media print {
            body { background: none; padding: 0; }
            .print-btn-container { display: none !important; }
            .biz-card { box-shadow: none; border: 0.1px solid #eee; border-top: 5px solid #1a4b8c !important; border-bottom: 5px solid #1a4b8c !important; break-inside: avoid; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>
    <div class="print-btn-container mb-4">
        <button onclick="window.print()" class="btn btn-primary btn-lg shadow">
            <i class="fas fa-print me-2"></i> Print Visiting Cards
        </button>
    </div>

    <div class="d-flex flex-wrap justify-content-center gap-4">
        <div class="biz-card">
            <div class="card-inner">
                <div class="logo-area text-center"><img src="{{ $data['company_logo'] }}" alt="Company Logo"></div>
                <div class="iso-text">(An ISO {{ $data['iso_no'] ?: '9001:2015' }} Certified Company)</div>
                <div class="projects-title">Our Projects :-</div>
                <div class="row g-2 mt-1">
                    <div class="col-6">
                        <div class="project-badge"><h5>JANKI VILLA</h5><p>PLOTS & APARTMENTS</p></div>
                    </div>
                    <div class="col-6">
                        <div class="project-badge"><h5>JANKI NIWAS</h5><p>PLOTS & VILLA</p></div>
                    </div>
                </div>
                <div class="flex-grow-1"></div> 
            </div>
            <div class="footer-info">
                <div class="fw-bold">CIN No. : {{ $data['cin_no'] }}</div>
                <div><i class="fas fa-map-marker-alt"></i> {{ $data['is_ho'] ? 'Regd. Add.: ' . $data['company_address'] : 'Branch Add.: ' . $data['company_address'] }}</div>
                <div>Office Contact No: 903107972-1/2/3/4/5/6/7/8/9  <i class="fa-brands fa-whatsapp"></i> {{ $data['company_phone'] }}</div>
            </div>
        </div>

        <div class="biz-card">
            <div class="card-inner">
                <div class="header-split">
                    <div class="logo-area" style="width: 45%;"><img src="{{ $data['company_logo'] }}" alt="Logo"></div>
                    <div class="person-info">
                        <h3 class="person-name">{{ $data['name'] }}</h3>
                        <div class="person-title">{{ $data['designation'] }}</div>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <ul class="contact-list">
                        @if(!empty($data['mobile']))
                        <li><i class="fas fa-mobile-alt"></i> {{ $data['mobile'] }}</li>
                        @endif
                        @if(!empty($data['email']))
                        <li><i class="fas fa-envelope"></i> {{ $data['email'] }}</li>
                        @endif
                        <li><i class="fas fa-globe"></i> www.jankivilla.com</li>
                    </ul>
                </div>
                <div class="company-name-red">{{ strtoupper($data['company_name']) }}</div>
            </div>
            <div class="footer-info">
                <div class="fw-bold">CIN No. : {{ $data['cin_no'] }}</div>
                <div><i class="fas fa-map-marker-alt"></i> {{ $data['is_ho'] ? 'Regd. Add.: ' . $data['company_address'] : 'Branch Add.: ' . $data['branch_address'] }}</div>
                <div>Office Contact No: 903107972-1/2/3/4/5/6/7/8/9  <i class="fa-brands fa-whatsapp"></i> {{ $data['company_phone'] }}</div>
            </div>
        </div>
    </div>
</body>
</html>