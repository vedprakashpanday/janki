<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Alert - Amitabh Builders</title>
    <style>
        /* Email clients CSS Reset */
        body,
        table,
        td,
        a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        /* General Styles */
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
        }
    </style>
</head>

<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: 'Segoe UI', Helvetica, Arial, sans-serif;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%"
        style="background-color: #f1f5f9; padding: 30px 10px;">
        <tr>
            <td align="center">

                <table border="0" cellpadding="0" cellspacing="0" width="100%"
                    style="max-width: 550px; background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden;">

                    <tr>
                        <td align="center" style="background-color: #1e3a8a; padding: 30px 20px;">
                            <h1
                                style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">
                                AMITABH BUILDERS & DEVELOPERS PVT. LTD.</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 35px 25px;">
                            <h2 style="color: #0f172a; font-size: 20px; margin-top: 0; margin-bottom: 10px;">Action
                                Required</h2>
                            <p style="color: #475569; font-size: 15px; line-height: 1.6; margin: 0 0 25px 0;">A new
                                system request requires your administrative approval to proceed.</p>

                            <table border="0" cellpadding="0" cellspacing="0" width="100%"
                                style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 20px;">
                                <tr>
                                    <td style="padding: 15px 20px;">
                                        <p style="margin: 0 0 8px 0; font-size: 14px;"><strong
                                                style="color: #1e293b;">Action Type:</strong> <span
                                                style="color: #0284c7;">{{ $actionName }}</span></p>
                                        <p style="margin: 0; font-size: 14px;"><strong style="color: #1e293b;">Requested
                                                By:</strong> <span style="color: #0284c7;">{{ $userEmail }}</span>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- 🔥 NAYA: Attractive Passkey Box 🔥 -->
                            @if(isset($otp))
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center" style="background-color: #eff6ff; border: 2px dashed #3b82f6; border-radius: 8px; padding: 20px;">
                                        <p style="margin: 0 0 8px 0; font-size: 12px; color: #2563eb; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">Today's Secure Passkey</p>
                                        <p style="margin: 0; font-size: 34px; font-weight: bold; color: #1e3a8a; letter-spacing: 6px;">{{ $otp }}</p>
                                        <p style="margin: 10px 0 0 0; font-size: 12px; color: #64748b;">Use this 6-digit code via <b>"Try Another Way"</b> on the login screen if buttons don't work.</p>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            <!-- 🔥 END Passkey Box 🔥 -->

                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="right" width="48%" style="padding-right: 5px;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td align="center"
                                                    style="background-color: #10b981; border-radius: 6px; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);">
                                                    <a href="{{ $approveUrl }}" target="_blank"
                                                        style="font-size: 15px; color: #ffffff !important; text-decoration: none !important; padding: 12px 10px; display: block; font-weight: bold; white-space: nowrap;">Approve</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                    <td width="4%"></td>

                                    <td align="left" width="48%" style="padding-left: 5px;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td align="center"
                                                    style="background-color: #ef4444; border-radius: 6px; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);">
                                                    <a href="{{ $rejectUrl }}" target="_blank"
                                                        style="font-size: 15px; color: #ffffff !important; text-decoration: none !important; padding: 12px 10px; display: block; font-weight: bold; white-space: nowrap;">Reject</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <tr>
                        <td align="center"
                            style="background-color: #f8fafc; padding: 20px; border-top: 1px solid #e2e8f0;">
                            <p style="color: #64748b; font-size: 12px; margin: 0;">If you ignore this email, the action
                                will automatically remain pending.</p>
                            <p style="color: #94a3b8; font-size: 12px; margin: 8px 0 0 0;">&copy; {{ date('Y') }}
                                Amitabh Builders & Developers Pvt. Ltd.<br>All rights reserved.</p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>

</html>