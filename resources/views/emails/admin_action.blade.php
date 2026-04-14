<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JankiVilla Security Alert</title>
    <style>
        /* Email clients ke defaults ko reset karna */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        
        /* Mobile View CSS */
        @media screen and (max-width: 480px) {
            /* Mobile me dono buttons 100% width lekar upar-niche aayenge, chipkenge nahi */
            .btn-container { width: 100% !important; display: block !important; margin-bottom: 12px !important; }
            .btn-link { display: block !important; text-align: center !important; padding: 14px 20px !important; }
            .box { padding: 20px 15px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; padding: 40px 15px;">
        <tr>
            <td align="center">
                
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden;">
                    
                    <tr>
                        <td align="center" style="background-color: #0f172a; padding: 25px 20px;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 600; letter-spacing: 0.5px;">JankiVilla Workspace</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 35px 30px;">
                            <h2 style="color: #334155; font-size: 18px; margin-top: 0; margin-bottom: 15px;">Action Required</h2>
                            <p style="color: #64748b; font-size: 15px; line-height: 1.6; margin: 0 0 25px 0;">A new request requires your administrative approval to proceed.</p>

                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; border-radius: 8px; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 15px 20px;">
                                        <p style="margin: 0 0 8px 0; font-size: 14px;"><strong style="color: #1e293b;">Action Type:</strong> <span style="color: #475569;">{{ $actionName }}</span></p>
                                        <p style="margin: 0; font-size: 14px;"><strong style="color: #1e293b;">Requested By:</strong> <span style="color: #2563eb;">{{ $userEmail }}</span></p>
                                    </td>
                                </tr>
                            </table>

                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center">
                                        
                                        <table border="0" cellpadding="0" cellspacing="0" class="btn-container" style="display: inline-block; margin: 0 5px;">
                                            <tr>
                                                <td align="center" style="background-color: #22c55e; border-radius: 6px;">
                                                    <a href="{{ $approveUrl }}" target="_blank" class="btn-link" style="font-size: 15px; color: #ffffff !important; text-decoration: none !important; padding: 12px 24px; display: inline-block; font-weight: bold;">Approve Request</a>
                                                </td>
                                            </tr>
                                        </table>

                                        <table border="0" cellpadding="0" cellspacing="0" class="btn-container" style="display: inline-block; margin: 0 5px;">
                                            <tr>
                                                <td align="center" style="background-color: #ef4444; border-radius: 6px;">
                                                    <a href="{{ $rejectUrl }}" target="_blank" class="btn-link" style="font-size: 15px; color: #ffffff !important; text-decoration: none !important; padding: 12px 24px; display: inline-block; font-weight: bold;">Reject Request</a>
                                                </td>
                                            </tr>
                                        </table>

                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="background-color: #f8fafc; padding: 20px; border-top: 1px solid #e2e8f0;">
                            <p style="color: #94a3b8; font-size: 12px; margin: 0;">If you ignore this email, the action will remain pending.</p>
                            <p style="color: #94a3b8; font-size: 12px; margin: 5px 0 0 0;">&copy; {{ date('Y') }} JankiVilla Workspace. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
                
            </td>
        </tr>
    </table>
</body>
</html>