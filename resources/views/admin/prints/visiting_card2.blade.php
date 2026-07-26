<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $data['company_name'] }} - Premium Card</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  :root { --gold: #C9A84C; --gold-light: #E8C97A; --dark: #0D1117; --text: #F0EAD6; --text-muted: #9A8B6E; --card-w: 500px; --card-h: 280px; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #0a0c10; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; font-family: 'Montserrat', sans-serif; padding: 40px 20px; }
  .print-btn { margin-bottom: 36px; background: linear-gradient(135deg, var(--gold), var(--gold-light)); color: #0D1117; border: none; padding: 13px 36px; border-radius: 50px; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 13px; letter-spacing: 2px; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; gap: 10px; box-shadow: 0 8px 30px rgba(201,168,76,0.35), 0 2px 8px rgba(0,0,0,0.4); }
  .scene { display: flex; flex-direction: column; gap: 40px; align-items: center; }
  .side-label { color: var(--text-muted); font-size: 10px; letter-spacing: 3px; text-transform: uppercase; text-align: center; margin-bottom: 10px; }
  .card-wrap { width: var(--card-w); }
  .biz-card { width: var(--card-w); height: var(--card-h); border-radius: 16px; overflow: hidden; box-shadow: 0 25px 70px rgba(0,0,0,0.7), 0 0 0 1px rgba(201,168,76,0.2), inset 0 1px 0 rgba(201,168,76,0.15); position: relative; }
  .biz-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, transparent, var(--gold), var(--gold-light), var(--gold), transparent); z-index: 10; }
  .card-front { background: linear-gradient(135deg, #0f1420 0%, #1a2035 55%, #0f1420 100%); width: 100%; height: 100%; padding: 28px 36px 24px; display: flex; flex-direction: column; justify-content: space-between; position: relative; }
  .watermark { position: absolute; right: -6px; bottom: -14px; font-family: 'Playfair Display', serif; font-size: 110px; font-weight: 900; color: rgba(201,168,76,0.04); line-height: 1; pointer-events: none; user-select: none; }
  .front-top { display: flex; justify-content: space-between; align-items: flex-start; }
  .logo-area { display: flex; align-items: center; gap: 13px; }
  .logo-icon { width: 48px; height: 48px; background: linear-gradient(135deg, var(--gold), var(--gold-light)); border-radius: 11px; display: flex; align-items: center; justify-content: center; font-family: 'Playfair Display', serif; font-weight: 900; font-size: 20px; color: var(--dark); flex-shrink: 0; box-shadow: 0 4px 18px rgba(201,168,76,0.3); }
  .iso-badge { border: 1px solid rgba(201,168,76,0.4); border-radius: 7px; padding: 6px 10px; text-align: center; flex-shrink: 0; }
  .iso-badge .iso-label { font-size: 7px; letter-spacing: 2px; color: var(--text-muted); text-transform: uppercase; display: block; }
  .iso-badge .iso-num  { font-size: 11px; font-weight: 700; color: var(--gold); letter-spacing: 1px; display: block; }
  .iso-badge .iso-year { font-size: 9px; color: var(--text-muted); }
  .divider { height: 1px; background: linear-gradient(90deg, transparent, rgba(201,168,76,0.3), transparent); }
  .projects-label { font-size: 9px; letter-spacing: 3px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 10px; }
  .projects-grid { display: flex; gap: 12px; }
  .project-tag { flex: 1; border: 1px solid var(--gold); border-radius: 9px; padding: 10px 14px; text-align: center; background: rgba(201,168,76,0.05); position: relative; overflow: hidden; }
  .project-tag::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, var(--gold), var(--gold-light)); }
  .project-tag h3 { font-family: 'Playfair Display', serif; font-size: 15px; font-weight: 700; color: var(--gold-light); letter-spacing: 0.5px; }
  .project-tag p { font-size: 7.5px; letter-spacing: 2px; text-transform: uppercase; color: var(--text-muted); margin-top: 3px; }
  .card-back { background: linear-gradient(145deg, #111827 0%, #1a2235 60%, #0e1520 100%); width: 100%; height: 100%; display: flex; flex-direction: column; }
  .back-header { background: linear-gradient(90deg, rgba(201,168,76,0.13) 0%, rgba(201,168,76,0.04) 100%); padding: 16px 36px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(201,168,76,0.15); flex-shrink: 0; }
  .person-name { font-family: 'Playfair Display', serif; font-size: 21px; font-weight: 700; color: var(--text); letter-spacing: 1px; }
  .gold-bar { width: 36px; height: 2px; background: linear-gradient(90deg, var(--gold), var(--gold-light)); border-radius: 2px; margin: 5px 0 4px; }
  .person-title { font-size: 9px; letter-spacing: 3px; text-transform: uppercase; color: var(--gold); }
  .avatar { width: 46px; height: 46px; border-radius: 50%; background: linear-gradient(135deg, var(--gold), var(--gold-light)); display: flex; align-items: center; justify-content: center; color: var(--dark); font-size: 19px; font-weight: 700; font-family: 'Playfair Display', serif; box-shadow: 0 4px 14px rgba(201,168,76,0.3); flex-shrink: 0; }
  .back-body { padding: 14px 20px 16px; display: flex; flex-direction: column; flex: 1; justify-content: space-between; }
  .contact-row { display: flex; gap: 8px; flex-wrap: nowrap; align-items: center; }
  .contact-item { display: flex; align-items: center; gap: 8px;  min-width: 0; overflow: hidden; }
  .contact-icon { width: 26px; height: 26px; border-radius: 7px; background: rgba(201,168,76,0.1); border: 1px solid rgba(201,168,76,0.25); display: flex; align-items: center; justify-content: center; color: var(--gold); font-size: 10px; flex-shrink: 0; }
  .ci-label { font-size: 7.5px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--text-muted); display: block; line-height: 1; margin-bottom: 2px; white-space: nowrap; }
  .ci-value { font-size: 10.5px; font-weight: 600; color: var(--text); letter-spacing: 0.2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
  .bottom-row { display: flex; gap: 14px; align-items: stretch; }
  .address-block { flex: 1; background: rgba(201,168,76,0.05); border: 1px solid rgba(201,168,76,0.15); border-radius: 9px; padding: 10px 14px; }
  .address-label { font-size: 8px; letter-spacing: 2px; text-transform: uppercase; color: var(--gold); margin-bottom: 5px; }
  .address-text { font-size: 10px; color: var(--text-muted); line-height: 1.55; }
  .right-col { display: flex; flex-direction: column; justify-content: space-between; align-items: flex-end; min-width: 160px; }
  .cin-text { font-size: 8px; color: var(--text-muted); letter-spacing: 0.5px; text-align: right; }
  .cin-text span { color: var(--gold); font-weight: 600; }
  .office-phones { text-align: right; }
  .op-label { font-size: 8px; letter-spacing: 2px; text-transform: uppercase; color: var(--text-muted); display: block; }
  .op-nums  { font-size: 11px; font-weight: 600; color: var(--text); letter-spacing: 0.5px; }

  @media print {
      * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
      body { background: white !important; padding: 0 !important; margin: 0 !important; display: block !important; }
      .print-btn, .side-label { display: none !important; }
      .scene { display: block !important; gap: 0 !important; }
      .card-wrap { width: 500px !important; page-break-after: auto !important; page-break-inside: avoid !important; margin: 10px auto !important; animation: none !important; opacity: 1 !important; transform: none !important; }
      .biz-card { box-shadow: 0 0 0 1px rgba(201,168,76,0.5) !important; transform: none !important; border: 1px solid #ddd !important; }
  }
</style>
</head>
<body>

<button class="print-btn" onclick="window.print()">
  <i class="fas fa-print"></i> Print / Download Card
</button>

<div class="scene">
  <div class="card-wrap">
    <div class="side-label">✦ Front Side</div>
    <div class="biz-card">
      <div class="card-front">
        <div class="watermark">{{ substr(trim($data['company_name']), 0, 2) }}</div>
        <div class="front-top">
          <div class="logo-area">
            <div class="logo-icon">{{ substr(trim($data['company_name']), 0, 2) }}</div>
            <div class="logo-text">
              <img src="{{ $data['company_logo'] }}" alt="Logo" style="width: 200px; margin-bottom: 10px; filter: invert(1);">
            </div>
          </div>
          <div class="iso-badge">
            <span class="iso-label">Certified</span>
            <span class="iso-num">ISO {{ $data['iso_no'] ?: '9001' }}</span>
            <span class="iso-year">: 2015</span>
          </div>
        </div>
        <div class="divider"></div>
        <div>
          <div class="projects-label">Our Flagship Projects</div>
          <div class="projects-grid">
            <div class="project-tag"><h3>Janki Villa</h3><p>Plots &amp; Apartments</p></div>
            <div class="project-tag"><h3>Janki Niwas</h3><p>Plots &amp; Villa</p></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card-wrap">
    <div class="side-label">✦ Back Side</div>
    <div class="biz-card">
      <div class="card-back">
        <div class="back-header">
          <div>
            <div class="person-name">{{ $data['name'] }}</div>
            <div class="gold-bar"></div>
            <div class="person-title">{{ $data['designation'] }}</div>
          </div>
          <div class="avatar">{{ $data['first_letter'] }}</div>
        </div>
        <div class="back-body">
          <div class="contact-row">
            @if(!empty($data['mobile']))
            <div class="contact-item">
              <div class="contact-icon"><i class="fas fa-phone"></i></div>
              <div><span class="ci-label">Mobile</span><span class="ci-value">+91 {{ $data['mobile'] }}</span></div>
            </div>
            @endif

            @if(!empty($data['email']))
            <div class="contact-item">
              <div class="contact-icon"><i class="fas fa-envelope"></i></div>
              <div><span class="ci-label">Email</span><span class="ci-value">{{ $data['email'] }}</span></div>
            </div>
            @endif
            <div class="contact-item">
              <div class="contact-icon"><i class="fas fa-globe"></i></div>
              <div><span class="ci-label">Website</span><span class="ci-value">www.jankivilla.com</span></div>
            </div>
          </div>
          <div class="bottom-row">
            <div class="address-block">
              <div class="address-label"><i class="fas fa-map-marker-alt"></i> &nbsp;{{ $data['is_ho'] ? 'Regd. Address' : 'Branch Address' }}</div>
              <div class="address-text">{{ $data['is_ho'] ? $data['company_address'] : $data['branch_address'] }}</div>
            </div>
            <div class="right-col">
              <div class="cin-text">CIN:<br><span>{{ $data['cin_no'] }}</span></div>
              <div class="office-phones">
                <span class="op-label">Office Contacts</span>
                <span class="op-nums">{{ $data['company_phone'] }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>