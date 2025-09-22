<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Подтверждение Email - BONUS5</title>
    <style type="text/css">
        /* Mobile Responsive Styles */
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
            .title-text {
                font-size: 36px !important;
                letter-spacing: 4px !important;
            }
            .welcome-text {
                font-size: 24px !important;
                letter-spacing: 1px !important;
            }
            .button-padding {
                padding: 14px 24px !important;
                font-size: 14px !important;
            }
            .feature-icon {
                width: 20px !important;
                height: 20px !important;
            }
            .feature-text {
                font-size: 14px !important;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .email-body {
                background-color: #0f172a !important;
            }
        }

        /* Print styles */
        @media print {
            .email-container {
                background: white !important;
                color: black !important;
            }
        }
    </style>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #111827; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #f3f4f6;">
    <!-- Email Container -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="min-height: 100vh; background-color: #111827;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <!-- Main Content Card -->
                <table width="600" cellpadding="0" cellspacing="0" border="0" class="content-card" style="max-width: 600px; width: 100%; background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(128, 202, 255, 0.1) 100%); border-radius: 16px; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td class="header-padding" style="background: linear-gradient(135deg, #4f46e5 0%, #80caff 100%); padding: 40px 30px; text-align: center;">
                                        <!-- Logo/Brand -->
                                        <h1 class="title-text" style="margin: 0; font-size: 48px; font-weight: 600; letter-spacing: 8px; color: #ffffff; text-transform: uppercase; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);">
                                            BONUS 5
                                        </h1>
                                        <div style="margin-top: 8px; height: 3px; width: 80px; background: rgba(255, 255, 255, 0.3); margin-left: auto; margin-right: auto; border-radius: 2px;"></div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td class="content-padding" style="padding: 50px 40px;">
                            <!-- Welcome Message -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="text-align: center; padding-bottom: 30px;">
                                        <h2 class="welcome-text" style="margin: 0; font-size: 28px; font-weight: 600; color: #ffffff; letter-spacing: 2px;">
                                            Добро пожаловать!
                                        </h2>
                                        <p style="margin: 16px 0 0 0; font-size: 16px; color: #d1d5db; line-height: 1.7;">
                                            Спасибо за регистрацию в BONUS5
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Message Content -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="background: rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 30px; border: 1px solid rgba(255, 255, 255, 0.1);">
                                        <p style="margin: 0 0 24px 0; font-size: 16px; color: #f3f4f6; line-height: 1.7; text-align: center;">
                                            Для завершения регистрации и активации вашего аккаунта необходимо подтвердить адрес электронной почты.
                                        </p>

                                        <p style="margin: 0 0 32px 0; font-size: 16px; color: #f3f4f6; line-height: 1.7; text-align: center;">
                                            Нажмите на кнопку ниже, чтобы подтвердить ваш email:
                                        </p>

                                        <!-- Verification Button -->
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 32px 0;">
                                            <tr>
                                                <td align="center">
                                                    <table cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%); border-radius: 8px; box-shadow: 0 4px 14px 0 rgba(79, 70, 229, 0.4); transition: all 0.3s ease;">
                                                                <a href="{{ $verificationUrl }}"
                                                                   class="button-padding"
                                                                   style="display: inline-block; padding: 16px 32px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; letter-spacing: 1px; text-transform: uppercase; border-radius: 8px;"
                                                                   target="_blank">
                                                                    Подтвердить Email
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Alternative Link -->
                                        <p style="margin: 32px 0 0 0; font-size: 14px; color: #9ca3af; line-height: 1.6; text-align: center;">
                                            Если кнопка не работает, скопируйте и вставьте эту ссылку в адресную строку браузера:
                                        </p>
                                        <p style="margin: 12px 0 0 0; word-break: break-all; text-align: center;">
                                            <a href="{{ $verificationUrl }}"
                                               style="color: #60a5fa; text-decoration: underline; font-size: 14px;"
                                               target="_blank">{{ $verificationUrl }}</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Features Section -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 40px;">
                                <tr>
                                    <td>
                                        <h3 style="margin: 0 0 24px 0; font-size: 20px; font-weight: 600; color: #ffffff; text-align: center; letter-spacing: 1px;">
                                            Что вас ждет в BONUS5:
                                        </h3>

                                        <!-- Feature Items -->
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding: 16px 0;">
                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td width="40" valign="top" style="padding-right: 16px;">
                                                                <table cellpadding="0" cellspacing="0" border="0" style="width: 24px; height: 24px; background: #10b981; border-radius: 50%;">
                                                                    <tr>
                                                                        <td align="center" valign="middle" style="width: 24px; height: 24px; text-align: center; vertical-align: middle;">
                                                                            <span style="color: #ffffff; font-size: 14px; font-weight: bold; line-height: 1;">✓</span>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                            <td>
                                                                <p style="margin: 0; font-size: 16px; color: #d1d5db; line-height: 1.6;">
                                                                    <strong style="color: #ffffff;">Кэшбэк</strong> при покупке для себя
                                                                </p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 16px 0;">
                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td width="40" valign="top" style="padding-right: 16px;">
                                                                <table cellpadding="0" cellspacing="0" border="0" style="width: 24px; height: 24px; background: #10b981; border-radius: 50%;">
                                                                    <tr>
                                                                        <td align="center" valign="middle" style="width: 24px; height: 24px; text-align: center; vertical-align: middle;">
                                                                            <span style="color: #ffffff; font-size: 14px; font-weight: bold; line-height: 1;">✓</span>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                            <td>
                                                                <p style="margin: 0; font-size: 16px; color: #d1d5db; line-height: 1.6;">
                                                                    <strong style="color: #ffffff;">Бонус</strong> за приведение клиентов
                                                                </p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 16px 0;">
                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td width="40" valign="top" style="padding-right: 16px;">
                                                                <table cellpadding="0" cellspacing="0" border="0" style="width: 24px; height: 24px; background: #10b981; border-radius: 50%;">
                                                                    <tr>
                                                                        <td align="center" valign="middle" style="width: 24px; height: 24px; text-align: center; vertical-align: middle;">
                                                                            <span style="color: #ffffff; font-size: 14px; font-weight: bold; line-height: 1;">✓</span>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                            <td>
                                                                <p style="margin: 0; font-size: 16px; color: #d1d5db; line-height: 1.6;">
                                                                    <strong style="color: #ffffff;">Надежность</strong> и конфиденциальность
                                                                </p>
                                                            </td>
                                                        </tr>
                                                    </table>
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
                        <td style="background: rgba(255, 255, 255, 0.05); padding: 30px 40px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="text-align: center;">
                                        <p style="margin: 0 0 16px 0; font-size: 14px; color: #9ca3af; line-height: 1.6;">
                                            Если вы не регистрировались на нашем сайте, то просто проигнорируйте это письмо.
                                        </p>
                                        <p style="margin: 0; font-size: 14px; color: #6b7280; line-height: 1.6;">
                                            С уважением,<br>
                                            <strong style="color: #ffffff; letter-spacing: 2px;">Команда BONUS5</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- Bottom Spacing -->
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 20px;">
                    <tr>
                        <td style="text-align: center; padding: 20px;">
                            <p style="margin: 0; font-size: 12px; color: #6b7280;">
                                © {{ date('Y') }} BONUS5. Все права защищены.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
