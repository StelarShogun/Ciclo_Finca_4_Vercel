@php
    $siteUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $mailContact = (string) config('mail.from.address', 'ciclo.finca4@gmail.com');
    $logoUrl = url(asset('assets/images/brand/logo-ciclo-finca-icon-64.png'));
    $pageTitle = trim($__env->yieldContent('title')) ?: 'Ciclo Finca 4';
@endphp
<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>{{ $pageTitle }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; line-height: 100%; outline: none; text-decoration: none; display: block; }
        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background-color: #DAF1DE;
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            color: #333333;
        }
        a { color: #235347; text-decoration: none; }
        .email-preheader {
            display: none !important;
            visibility: hidden;
            opacity: 0;
            color: transparent;
            height: 0;
            width: 0;
            max-height: 0;
            max-width: 0;
            overflow: hidden;
            mso-hide: all;
            font-size: 1px;
            line-height: 1px;
        }
        .email-body-copy {
            font-size: 16px;
            line-height: 1.6;
            color: #333333;
        }
        .email-body-copy p {
            margin: 0 0 16px 0;
        }
        .email-body-copy p:last-child {
            margin-bottom: 0;
        }
        .email-card {
            background-color: #ffffff;
            border: 1px solid #c8e6c9;
            border-radius: 8px;
            overflow: hidden;
        }
        .email-footer {
            background-color: #f4faf5;
            border-top: 1px solid #c8e6c9;
            color: #666666;
            font-size: 12px;
            line-height: 1.6;
        }
        .email-footer a {
            color: #235347;
            text-decoration: underline;
        }
        .email-brand-row {
            width: 100%;
        }
        .email-logo-cell {
            width: 80px;
            vertical-align: top;
        }
        .email-logo-spacer {
            width: 80px;
            font-size: 0;
            line-height: 0;
        }
        .email-wordmark-cell {
            vertical-align: middle;
            text-align: center;
        }
        .email-logo {
            width: 80px !important;
            height: 80px !important;
            max-width: 80px;
        }
        .email-wordmark {
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -0.01em;
            text-align: center;
            white-space: nowrap;
        }
        .email-wordmark-dark { color: #DAF1DE; }
        .email-wordmark-green { color: #8EB69B; }
        .email-header-title {
            margin: 0;
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            font-size: 17px;
            font-weight: 600;
            line-height: 1.4;
            color: #DAF1DE;
            text-align: center;
        }
        @media only screen and (max-width: 620px) {
            .email-shell {
                width: 100% !important;
            }
            .email-content-cell,
            .email-header-cell,
            .email-footer-cell {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }
            .email-brand-title {
                font-size: 20px !important;
            }
            .email-logo-cell,
            .email-logo-spacer {
                width: 64px !important;
            }
            .email-logo {
                width: 64px !important;
                height: 64px !important;
                max-width: 64px !important;
            }
            .email-wordmark {
                font-size: 20px !important;
                white-space: normal !important;
            }
            .email-header-title {
                font-size: 15px !important;
            }
        }
        @yield('styles')
    </style>
</head>
<body style="margin:0;padding:0;background-color:#DAF1DE;">
    @hasSection('preheader')
        <div class="email-preheader">@yield('preheader')</div>
    @endif

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#DAF1DE;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" class="email-shell" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;">
                    <tr>
                        <td class="email-card" style="background-color:#ffffff;border:1px solid #c8e6c9;border-radius:8px;overflow:hidden;">

                            {{-- Header: logo esquina izquierda, wordmark centrado, título como subtítulo --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td class="email-header-cell" bgcolor="#235347" style="background-color:#235347;padding:16px 20px 20px 16px;">
                                        <table role="presentation" class="email-brand-row" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td class="email-logo-cell" width="80" align="left" valign="top" style="width:80px;vertical-align:top;text-align:left;">
                                                    <a href="{{ $siteUrl }}" style="text-decoration:none;">
                                                        <img src="{{ $logoUrl }}"
                                                             width="80"
                                                             height="80"
                                                             alt="Ciclo Finca 4"
                                                             class="email-logo"
                                                             style="display:block;border:0;outline:none;text-decoration:none;width:80px;height:80px;max-width:80px;">
                                                    </a>
                                                </td>
                                                <td class="email-wordmark-cell" align="center" valign="middle" style="vertical-align:middle;text-align:center;">
                                                    <a href="{{ $siteUrl }}" style="text-decoration:none;display:inline-block;">
                                                        <span class="email-wordmark" style="font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:28px;font-weight:700;line-height:1.15;letter-spacing:-0.01em;text-align:center;white-space:nowrap;">
                                                            <span class="email-wordmark-dark" style="color:#DAF1DE;">CICLO </span>
                                                            <span class="email-wordmark-green" style="color:#8EB69B;">FINCA </span>
                                                            <span class="email-wordmark-dark" style="color:#DAF1DE;">4</span>
                                                        </span>
                                                    </a>
                                                    @hasSection('header-title')
                                                        <p class="email-header-title" style="margin:10px 0 0;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:17px;font-weight:600;line-height:1.4;color:#DAF1DE;text-align:center;">
                                                            @yield('header-title')
                                                        </p>
                                                    @endif
                                                    @hasSection('header-subtitle')
                                                        <p style="margin:6px 0 0;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:13px;line-height:1.5;color:#DAF1DE;text-align:center;opacity:0.95;">
                                                            @yield('header-subtitle')
                                                        </p>
                                                    @endif
                                                </td>
                                                <td class="email-logo-spacer" width="80" align="right" valign="top" style="width:80px;font-size:0;line-height:0;">&nbsp;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Content --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td class="email-content-cell email-body-copy" style="padding:32px 32px 28px;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.6;color:#333333;">
                                        @yield('content')
                                    </td>
                                </tr>
                            </table>

                            {{-- Footer --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td class="email-footer-cell email-footer" style="background-color:#f4faf5;border-top:1px solid #c8e6c9;padding:20px 32px;text-align:center;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#666666;">
                                        @hasSection('footer-note')
                                            <p style="margin:0 0 12px 0;">@yield('footer-note')</p>
                                        @endif

                                        <p style="margin:0 0 8px 0;">
                                            <strong style="color:#235347;">Contacto Ciclo Finca 4</strong>
                                        </p>
                                        <p style="margin:0 0 6px 0;">
                                            Correo:
                                            <a href="mailto:{{ $mailContact }}" style="color:#235347;text-decoration:underline;">{{ $mailContact }}</a>
                                        </p>
                                        <p style="margin:0 0 12px 0;">
                                            Sitio web:
                                            <a href="{{ $siteUrl }}" style="color:#235347;text-decoration:underline;">{{ $siteUrl }}</a>
                                        </p>

                                        @hasSection('unsubscribe')
                                            <p style="margin:0 0 12px 0;">@yield('unsubscribe')</p>
                                        @endif

                                        <p style="margin:0 0 6px 0;">
                                            &copy; {{ date('Y') }} Ciclo Finca 4. Todos los derechos reservados.
                                        </p>
                                        <p style="margin:0;">
                                            Este es un correo automático del sistema. Por favor no responda directamente a esta dirección.
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
</body>
</html>
