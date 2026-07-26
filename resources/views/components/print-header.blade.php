<style>
    /* 🌟 DEFAULT DESKTOP & TABLET VIEW 🌟 */
    .header-wrap {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 2px solid #000;
        padding-bottom: 5px;
        margin-bottom: 8px;
    }

    .header-logo {
        width: 20%;
        text-align: center;
    }

    .logo-image {
        width: 195px;
        max-width: 200px;
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
        width: 80%;
        text-align: center;
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

    /* 🌟 MOBILE VIEW FIXES (Responsive Magic) 🌟 */
    @media (max-width: 767.98px) {
        .header-wrap {
            flex-direction: column;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }

        .logo-image {
            display: none !important;
        }

        .header-logo {
            width: 100%;
            margin-bottom: 0px;
        }

        .iso {
            font-size: 6px;
            margin-top: 0;
            margin-bottom: 5px;
            display: none !important;
            text-align: center;
            /* Mobile me hide kiya hai */
        }

        .header-text {
            width: 100%;
        }

        .company-title {
            font-size: 12px;
            margin-bottom: 4px;
        }

        .cin-text {
            font-size: 9px;
            padding: 2px 6px !important;
            margin-bottom: 5px;
        }

        .small-text {
            font-size: 6.5px;
            line-height: 1.3;
            letter-spacing: -0.1px;
        }
    }

    /* 🌟 THE MAGIC: FORCE ROW & COLORS IN PRINT 🌟 */
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
            display: flex !important;
            /* Force flex in print */
        }

        .header-logo {
            width: 20% !important;
            display: block !important;
            /* border: 1px solid #000 !important; */
        }

        .logo-image {
            display: block !important;
            margin: 0 0 0 -10px !important;
            /* z-index: -1 !important; */
        }

        /* 🔥 FIX: Force ISO to display and reset its styling in Print 🔥 */
        .iso {
            font-size: 8px !important;
            display: block !important;
            margin-top: 5px !important;
            text-align: center !important;
            /* border: 1px solid #dc3545 !important; */
            margin-left: 25px !important;
        }

        .header-text {
            width: 80% !important;
            text-align: center !important;
        }

        .company-title {
            font-size: 20px !important;
            margin-bottom: 2px !important;
        }

        /* 🔥 FIX: Force CIN to be inline-block so it doesn't stretch 🔥 */
        .cin-text {
            font-size: 10px !important;
            display: inline-block !important;
            margin-bottom: 2px !important;
            padding: 2px 8px !important;
            border-radius: 4px !important;
        }

        .small-text {
            font-size: 10px !important;
            line-height: 1.3 !important;
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
            <strong><i class="fa-solid fa-phone"></i> - </strong>
            {{ $branch->phone ?? ($company->phone ?? '9031079721') }}
            | <strong><i class="fa-brands fa-whatsapp"></i> - </strong>
            {{ $branch->whatsapp ?? ($company->whatsapp ?? '9472467007') }}
            | <strong>Website(<i class="fa-solid fa-globe"></i>) - </strong>
            {{ $company->website ?? 'www.jankivilla.com' }}
        </div>
    </div>
</div>
