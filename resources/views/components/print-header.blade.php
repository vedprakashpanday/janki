<style>
    /* 🌟 DEFAULT DESKTOP & TABLET VIEW 🌟 */
    .header-wrap {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
        margin-bottom: 12px;
    }

    .header-logo {
        width: 20%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .logo-image {
        width: 100%;
        max-height: 70px;
        object-fit: contain;
    }

    .iso {
        font-size: 8px;
        font-weight: bold;
        margin-top: 4px;
        color: #dc3545;
        text-align: center;
    }

    .header-text {
        width: 80%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .company-title {
        font-size: 24px;
        font-weight: 900;
        color: #000;
        margin-bottom: 4px;
        line-height: 1.1;
        text-align: center;
    }

    .cin-text {
        font-size: 10px;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .small-text {
        font-size: 11px;
        line-height: 1.4;
        color: #000;
        text-align: center;
    }

    /* 🌟 MOBILE VIEW FIXES 🌟 */
    @media (max-width: 767.98px) {
        .header-wrap { flex-direction: column; padding-bottom: 8px; }
        .header-logo { width: 100%; }
        .logo-image, .iso { display: none !important; }
        .header-text { width: 100%; }
        .company-title { font-size: 16px; }
        .small-text { font-size: 9px; }
    }

    /* 🌟 BASE PRINT FIXES & PORTRAIT (DEFAULT) 🌟 */
    @media print {
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .bg-danger { background-color: #dc3545 !important; color: #fff !important; }
        .text-danger { color: #dc3545 !important; }

        .header-wrap {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: flex-start !important;
            padding-bottom: 6px !important;
            margin-bottom: 10px !important;
            border-bottom: 2px solid #000 !important;
            width: 100% !important;
        }

        /* Portrait Ratio (20-80) */
        .header-logo {
            width: 20% !important;
            flex: 0 0 20% !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
        }

        .header-text {
            width: 80% !important;
            flex: 0 0 80% !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .logo-image {
            display: block !important;
            width: 100% !important;
            max-height: 65px !important; 
            object-fit: contain !important;
            margin: 0 !important;
        }

        .iso {
            font-size: 8px !important;
            display: block !important;
            margin-top: 4px !important;
            text-align: center !important;
        }

        .company-title {
            font-size: 21px !important; 
            margin-bottom: 3px !important;
            text-align: center !important;
        }

        .cin-text {
            font-size: 10px !important;
            margin-bottom: 3px !important;
        }

        .small-text {
            font-size: 10px !important; 
            line-height: 1.3 !important;
            text-align: center !important;
        }
    }

    /* 🔥 PRINT: LANDSCAPE MODE (Forced via Class) 🔥 */
    @media print {
        .landscape-mode .header-logo {
            width: 30% !important;
            flex: 0 0 30% !important;
        }
        .landscape-mode .header-text {
            width: 70% !important;
            flex: 0 0 70% !important;
        }

        /* Landscape me font, image size, aur proper text border */
        .landscape-mode .logo-image {
            max-height: 85px !important; 
        }
        .landscape-mode .company-title {
            font-size: 28px !important; 
           
            
            padding: 4px 12px !important;
        }
        .landscape-mode .small-text {
            font-size: 12px !important; 
        }
        .landscape-mode .cin-text {
            font-size: 11px !important;
        }
        .landscape-mode .iso {
            font-size: 9px !important;
        }
    }
</style>
<div class="header-wrap">
    <div class="header-logo">
        @if ($company && !empty($company->company_logo))
            <img src="{{ asset($company->company_logo) }}" class="logo-image" alt="Company Logo">
        @else
            <img src="https://ui-avatars.com/api/?name={{ urlencode($company->company_name ?? 'AB') }}&color=7F9CF5&background=EBF4FF"
                class="logo-image" alt="Default Logo">
        @endif

        <div class="iso text-danger">
            {{ '(An ISO ' . ($company->iso_no ?? '9001:2015') . ' Certified)' }}
        </div>
    </div>

    <div class="header-text">
        <div class="company-title">
            {{ $company ? strtoupper($company->company_name) : 'AMITABH BUILDERS & DEVELOPERS PVT. LTD.' }}
        </div>

        <div class="cin-text">
            <span class="bg-danger text-white p-1 rounded">CIN NO. : {{ $company->cin_no ?? 'U43299BR2024PTC072712' }}</span>
        </div>

        <div class="small-text">
            <strong>H.O. :</strong>
            {{ $company->address ?? '1st Floor, Pappu Yadav Building, South of NH-27, Kakarghati Chowk, Darbhanga-846007' }}

            @if ($branch && $branch->branch_location)
                <br><strong>B.O. :</strong> {{ $branch->branch_location }}
            @endif

            <br>
            <strong>Contact No. (<i class="fa-solid fa-phone"></i>) - </strong>
            {{ $branch->phone ?? ($company->phone ?? '9031079721') }}
            | <strong>Whatsapp(<i class="fa-brands fa-whatsapp"></i>) - </strong>
            {{ $branch->whatsapp ?? ($company->whatsapp_no ?? '94724670') }}
            | <strong>Website(<i class="fa-solid fa-globe"></i>) - </strong>
            {{ $company->website ?? 'www.jankivilla.com' }}
        </div>
    </div>
</div>
