<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $proposalSubject }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style type="text/css">
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
            }
            .content-card {
                margin: 10px !important;
                border-radius: 12px !important;
            }
            .header-padding {
                padding: 30px 20px !important;
            }
            .content-padding {
                padding: 30px 20px !important;
            }
            .logo-img {
                width: 80px !important;
                height: auto !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #111827; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #f3f4f6;">
    <!-- Email Container -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="min-height: 100vh; background-color: #111827;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <!-- Main Content Card -->
                <table width="640" cellpadding="0" cellspacing="0" border="0" class="content-card" style="max-width: 640px; width: 100%; background: linear-gradient(135deg, rgba(79, 70, 229, 0.08) 0%, rgba(139, 92, 246, 0.08) 50%, rgba(236, 72, 153, 0.05) 100%); border-radius: 16px; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.1);">

                    <!-- Header with Logo -->
                    <tr>
                        <td style="padding: 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td class="header-padding" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #a855f7 100%); padding: 40px 40px 35px; text-align: center;">
                                        <!-- Logo -->
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td align="center" style="padding-bottom: 20px;">
                                                    <img src="https://zovofficial.com/image/catalog/logo-dark.svg"
                                                         alt="ЗОВ"
                                                         class="logo-img"
                                                         width="100"
                                                         style="width: 100px; height: auto; display: block; margin: 0 auto; filter: brightness(0) invert(1);" />
                                                </td>
                                            </tr>
                                        </table>
                                        <!-- Divider -->
                                        <div style="height: 1px; width: 80px; background: rgba(255, 255, 255, 0.3); margin: 0 auto 20px;"></div>
                                        <!-- Subject -->
                                        <h1 style="margin: 0; font-size: 22px; font-weight: 600; letter-spacing: 1px; color: #ffffff; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);">
                                            {{ $proposalSubject }}
                                        </h1>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td class="content-padding" style="padding: 30px 40px;">
                            <!-- Proposal Body Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="background: rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 24px; border: 1px solid rgba(255, 255, 255, 0.08); border-left: 4px solid #8b5cf6;">
                                        <p style="margin: 0; font-size: 15px; color: #e5e7eb; line-height: 1.8; white-space: pre-wrap;">{!! nl2br(e($proposalBody)) !!}</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- PDF Notice -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 30px;">
                                <tr>
                                    <td style="background: rgba(139, 92, 246, 0.1); border-radius: 10px; padding: 20px 24px; border: 1px solid rgba(139, 92, 246, 0.2);">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="40" valign="top" style="padding-right: 16px;">
                                                    <table cellpadding="0" cellspacing="0" border="0" style="width: 36px; height: 36px; background: linear-gradient(135deg, #7c3aed, #a855f7); border-radius: 8px;">
                                                        <tr>
                                                            <td align="center" valign="middle" style="width: 36px; height: 36px; text-align: center; vertical-align: middle;">
                                                                <span style="color: #ffffff; font-size: 18px; line-height: 1;">📎</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td>
                                                    <p style="margin: 0; font-size: 14px; color: #c4b5fd; font-weight: 600;">
                                                        PDF-файл
                                                    </p>
                                                    <p style="margin: 4px 0 0; font-size: 13px; color: #9ca3af; line-height: 1.5;">
                                                        К письму прикреплён PDF-документ с КП
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Sender Info Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 30px;">
                                <tr>
                                    <td style="border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 30px;">
                                        <p style="margin: 0 0 16px 0; font-size: 14px; color: #6b7280; line-height: 1.6;">
                                            С уважением,
                                        </p>
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="50" valign="top" style="padding-right: 16px;">
                                                    <table cellpadding="0" cellspacing="0" border="0" style="width: 48px; height: 48px; background: linear-gradient(135deg, #4f46e5, #7c3aed); border-radius: 12px; overflow: hidden;">
                                                        <tr>
                                                            <td align="center" valign="middle" style="width: 48px; height: 48px; text-align: center; vertical-align: middle; padding: 10px;">
                                                                <img src="https://zovofficial.com/image/catalog/logo-dark.svg"
                                                                     alt="ЗОВ"
                                                                     width="28"
                                                                     style="width: 28px; height: auto; display: block; filter: brightness(0) invert(1);" />
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td>
                                                    <p style="margin: 0; font-size: 16px; font-weight: 600; color: #ffffff; line-height: 1.4;">
                                                        Челноков Евгений
                                                    </p>
                                                    <p style="margin: 2px 0 0; font-size: 13px; color: #a78bfa; line-height: 1.4;">
                                                        Руководитель проекта ZOV-RUBONUS
                                                    </p>
                                                    <p style="margin: 8px 0 0; font-size: 13px; color: #9ca3af; line-height: 1.6;">
                                                        <a href="https://rubonus.pro" style="color: #9ca3af; text-decoration: none;">https://rubonus.pro</a>
                                                        &nbsp;&middot;&nbsp;
                                                        <a href="tel:+79154000020" style="color: #9ca3af; text-decoration: none;">8 (915) 400-00-20</a>
                                                        &nbsp;&middot;&nbsp;
                                                        <a href="mailto:info@rubonus.pro" style="color: #60a5fa; text-decoration: none;">info@rubonus.pro</a>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background: rgba(255, 255, 255, 0.03); padding: 24px 40px; border-top: 1px solid rgba(255, 255, 255, 0.06);">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="text-align: center;">
                                        <p style="margin: 0 0 8px 0; font-size: 13px; color: #6b7280; line-height: 1.6;">
                                            Фабрика мебели «ЗОВ» &middot; Партнёрская программа RUBONUS
                                        </p>
                                        <p style="margin: 0; font-size: 12px; color: #4b5563;">
                                            &copy; {{ date('Y') }} Все права защищены
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- Bottom Spacing -->
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 16px;">
                    <tr>
                        <td style="text-align: center; padding: 16px;">
                            <p style="margin: 0; font-size: 11px; color: #4b5563;">
                                Это письмо отправлено автоматически. Если у вас возникли вопросы — свяжитесь с нами по email
                                <a href="mailto:info@rubonus.pro" style="color: #60a5fa; text-decoration: none;">info@rubonus.pro</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
