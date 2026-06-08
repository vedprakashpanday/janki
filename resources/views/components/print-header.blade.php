<style>
    /* 🔥 DEFAULT DESKTOP & TABLET VIEW 🔥 */
    .header-wrap {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 2px solid #000;
        padding-bottom: 5px;
        margin-bottom: 8px;
    }

    .header-logo {
        width: 15%;
        text-align: center;
    }

    .logo-image {
        width: 130px;
        max-width: 130px;
        height: 70px;
        display: block;
        margin: 0 auto;
    }

    .iso {
        font-size: 7px;
        font-weight: bold;
        margin-top: 4px;
    }

    .header-text {
        width: 85%;
        text-align: center;
        /* margin-right: 70px; */
    }

    .company-title {
        font-size: 24px;
        font-weight: 900;
        color: #000;
        margin-bottom: 2px;
        line-height: 1.1;
    }

    .cin-text {
        font-size: 10px;
        font-weight: bold;
        margin-bottom: 3px;
    }

    .small-text {
        font-size: 10px;
        line-height: 1.4;
        color: #000;
    }

    /* 🔥 MOBILE VIEW FIXES (Responsive Magic) 🔥 */
    @media (max-width: 767.98px) {
        .header-wrap {
            flex-direction: column;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }

        /* Mobile me Logo hide karein */
        .logo-image {
            display: none !important;
        }

        .header-logo {
            width: 100%;
            margin-bottom: 0px;
        }

        .iso {
            font-size: 6px;
            /* Thoda readable banaya logo hatne ke baad */
            margin-top: 0;
            margin-bottom: 5px;
             display: none !important;
        }

        .header-text {
            width: 100%;
        }

        .company-title {
            font-size: 12px;
            /* Heading chhoti ki */
            margin-bottom: 4px;
        }

        .cin-text {
            font-size: 9px;
            padding: 2px 6px !important;
            margin-bottom: 5px;
        }

        .small-text {
            font-size: 6.5px;
            /* Font kafi shrink kiya taaki ek line me aaye */
            line-height: 1.3;
            letter-spacing: -0.1px;
            /* Words ko slightly close kiya */
        }
    }

    /* 🔥 THE MAGIC: FORCE ROW & COLORS IN PRINT 🔥 */
    @media print {
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .bg-danger {
            background-color: #dc3545 !important;
            color: #fff !important;
        }

        .text-danger,
        .iso {
            color: #dc3545 !important;
        }

        .header-wrap {
            flex-direction: row !important;
            padding-bottom: 2px !important;
            margin-bottom: 2px !important;
            gap: 0 !important;
        }

        .header-logo {
            width: 20% !important;
            display: block !important;
        }

        .logo-image {
            display: block !important;
            /* Print me logo wapas laana hai */
        }

        .header-text {
            width: 75% !important;
        }

        .company-title {
            font-size: 18px !important;
            margin-bottom: 0 !important;
        }

        .cin-text {
            font-size: 10px !important;
            margin-bottom: 0 !important;
        }

        .small-text {
            font-size: 10px !important;
            line-height: 1.3 !important;
        }

        .iso {
            font-size: 7px !important;
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

        <div class="iso text-danger">{{ '(An ISO ' . $company->iso_no . ' Certified)' ?? '(An ISO 9001:2015 Certified)' }}
        </div>
    </div>

    <div class="header-text">
        <div class="company-title">
            {{ $company ? strtoupper($company->company_name) : 'AMITABH BUILDERS & DEVELOPERS PVT. LTD.' }}
        </div>

        <div class="cin-text bg-danger text-white d-inline-block px-2 py-1 rounded">
            CIN NO. : {{ $company->cin_no ?? 'U43299BR2024PTC072712' }}
        </div>

        <div class="small-text">
            <strong>H.O. :</strong>
            {{ $company->address ?? '1st Floor, Pappu Yadav Building, South of NH-27, Kakarghati Chowk, Darbhanga-846007' }}

            @if ($branch && $branch->branch_location)
                <br><strong>B.O. :</strong> {{ $branch->branch_location }}
            @endif

            <br>
            <strong><i class="fa-solid fa-phone"></i> - </strong>
            {{ $branch->phone ?? ($company->phone ?? '9031079721') }}
            | <strong><i class="fa-brands fa-whatsapp"></i> - </strong>
            {{ $branch->whatsapp ?? ($company->whatsapp ?? '9472467007') }}
            | <strong>Website(<i class="fa-solid fa-globe"></i>) - </strong>
            {{ $company->website ?? 'www.jankivilla.com' }}
        </div>
    </div>
</div>
