<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $proposalSubject }}</title>
    <style>
        @page {
            margin: 0;
            size: A4;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
            color: #1f2937;
            font-size: 13px;
            line-height: 1.7;
        }

        /* Header — solid color for DomPDF (no gradient support) */
        .header {
            background-color: #5b21b6;
            padding: 25px 40px 20px;
            color: #ffffff;
            text-align: center;
        }

        .logo-container {
            margin-bottom: 10px;
            text-align: center;
        }

        .logo-container img {
            width: 90px;
            height: auto;
        }

        .header-divider {
            width: 60px;
            height: 2px;
            background-color: #7c3aed;
            margin: 10px auto;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 0;
            color: #ffffff;
        }

        .header-subtitle {
            font-size: 11px;
            color: #c4b5fd;
            letter-spacing: 2px;
            margin-top: 6px;
        }

        /* Date badge */
        .date-badge {
            text-align: right;
            padding: 8px 40px 0;
            font-size: 11px;
            color: #9ca3af;
        }

        /* Content area */
        .content {
            padding: 10px 40px 10px;
        }



        /* Proposal body */
        .proposal-body {
            background-color: #f8f7ff;
            border-left: 4px solid #7c3aed;
            padding: 16px 20px;
            margin: 0;
        }

        .proposal-body p {
            white-space: pre-wrap;
            margin: 0;
            text-align: justify;
            font-size: 12px;
            line-height: 1.4;
            color: #374151;
        }

        /* Divider */
        .section-divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 16px 40px;
        }

        /* Sender block */
        .sender-block {
            padding: 0 40px 20px;
        }

        .sender-regards {
            font-size: 12px;
            color: #6b7280;
            margin: 0 0 10px 0;
        }

        .sender-table {
            width: 100%;
        }

        .sender-logo-cell {
            width: 58px;
            vertical-align: top;
            padding-right: 14px;
        }

        .sender-logo-box {
            width: 48px;
            height: 48px;
            background-color: #5b21b6;
            border-radius: 10px;
            text-align: center;
            padding-top: 8px;
        }

        .sender-logo-box img {
            width: 32px;
            height: auto;
        }

        .sender-name {
            font-size: 15px;
            font-weight: bold;
            color: #1f2937;
            margin: 0;
            line-height: 1.3;
        }

        .sender-position {
            font-size: 12px;
            color: #7c3aed;
            font-weight: bold;
            margin: 3px 0 0;
        }

        .sender-contact-line {
            font-size: 12px;
            color: #6b7280;
            margin: 8px 0 0;
        }

        .sender-contact-line a {
            color: #4f46e5;
            text-decoration: none;
        }

        /* Footer */
        .footer {
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 14px 40px;
            text-align: center;
        }

        .footer p {
            font-size: 10px;
            color: #9ca3af;
            margin: 2px 0;
        }

        .footer-brand {
            font-weight: bold;
            color: #7c3aed;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <!-- Header with solid purple background -->
    <div class="header">
        <div class="logo-container">
            <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTI4IiBoZWlnaHQ9IjExNyIgdmlld0JveD0iMCAwIDEyOCAxMTciIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+DQo8cGF0aCBkPSJNMzguOTM1NyA0NC44NTUxQzM1LjA5MzQgNDYuNTE1MSAzMi4yNDEyIDQ4LjE4MzggMzIuMjQxMiA0OC4xODM4TDYzLjc2NyAwTDg3Ljk0NjggMzUuODM3NEM4Ny45NDY4IDM1LjgzNzQgODYuNjI2NSAzNS4wNzY2IDgyLjMxODcgMzQuNjg3NUM3Ni4yNzU5IDM0LjE0MjggNjcuODgwMiAzNS41MjYxIDYxLjMzODEgMzcuMDQ3OEM1NC44MjEzIDM4LjU2MDkgNDUuMzE3MSA0Mi4wODg0IDM4LjkzNTcgNDQuODU1MVoiIGZpbGw9IiNmZmZmZmYiLz4NCjxwYXRoIGQ9Ik05MC44NDA2IDQwLjQ5MDJMMTAxLjE0OSA1Ni40Njc5QzEwMS4xNDkgNTYuNDY3OSA5Mi45NzMzIDU1LjAxNTQgODUuODY0MiA1NS41NjAxQzc4Ljc1NSA1Ni4xMDQ4IDU5LjAxODYgNTguMjgzNiA1OS4wMTg2IDU4LjI4MzZDNTkuMDE4NiA1OC4yODM2IDc1LjU1NTkgNDkuNzUgODIuMTMxOSA0Ni4yOTE3Qzg4LjcwNzkgNDIuODUwNiA5MC44NDA2IDQwLjQ5MDIgOTAuODQwNiA0MC40OTAyWiIgZmlsbD0iI2ZmZmZmZiIvPg0KPHBhdGggZD0iTTEyOCA5Ny43MTA4TDEwOC4yNjQgNjcuMzgwOUMxMDguMjY0IDY3LjM4MDkgMTA4LjA4NiA3MS4zNzUzIDk5LjE5OTUgNzYuNDU5MUM5MC4zMTMgODEuNTQyOSA4NC4yNzAzIDg0LjA4NDggNzIuNzA5NCA4OC40NTFDNjEuMTU3IDkyLjgwODUgNDkuNDUyMSA5Ny44OTI0IDQ5LjQ1MjEgOTcuODkyNEwxMjggOTcuNzEwOFoiIGZpbGw9IiNmZmZmZmYiLz4NCjxwYXRoIGQ9Ik0wIDk4LjA3MThIMzUuMDg4OUMzNS4wODg5IDk4LjA3MTggNjAuNDUzNSA4OC4xMTE3IDc1LjE0NTggODAuNjQxNkM4OC4wMSA3NC4xMDUzIDk2Ljc1MjYgNjcuODE5NyA5NC41ODYgNjMuMzkzQzkyLjk4NjQgNjAuMTI0OCA4NS4wNDc5IDYwLjEyNDggNzUuNTYwNSA2MC4xMjQ4QzY3Ljk3NzMgNjAuMTI0OCA1OS4zOTU2IDYwLjc1NiA1NC45MzU0IDYwLjY2OTVDNDguODMzMyA2MC41NDg1IDUwLjg0NzYgNTkuODIyMiA1Mi45ODAzIDU4LjMwOTJDNTQuNTU0NSA1Ny4xOTM5IDcwLjAzMzkgNDguMzA1OCA3My45NjA5IDQ1Ljk2MjhDNzkuNTI5OCA0Mi42MzQxIDc3LjMzNzcgNDEgNzQuNzkwMyA0MS43ODY4QzcwLjgwNDEgNDMuMDE0NSA1Ny45NTY3IDQ4Ljg2NzggNDQuNjI3IDU3LjA0NjlDMzEuMjg4OCA2NS4yMTczIDIxLjUxMzcgNzMuNzUwOCAxNC4wNDkxIDgwLjg0MDVDNi41NzU5NyA4Ny45MDQyIDAgOTguMDcxOCAwIDk4LjA3MThaIiBmaWxsPSIjZmZmZmZmIi8+DQo8cGF0aCBkPSJNMS45NTUxNCAxMDUuOTI2VjEwMi44NEMxLjk1NTE0IDEwMi44NCAyOS41NzA5IDEwMi42NTggMzIuNzcgMTAyLjY1OEMzNS45NjkxIDEwMi42NTggMzguMDQyNiAxMDQuMzcgMzguMDQyNiAxMDYuNzEzQzM4LjA0MjYgMTA5LjA3MyAzNS4zNzY4IDEwOS45MiAzNS4zNzY4IDEwOS45MkMzNS4zNzY4IDEwOS45MiAzOC4wNDI2IDExMC43NzYgMzguMDQyNiAxMTMuNTUxQzM4LjA0MjYgMTE1LjQyNyAzNi42OCAxMTcuMDAxIDMyLjc3IDExNy4wMDFIMS43NzczNFYxMTMuNzMzSDI2LjQ5MDNWMTExLjczNkgxMS4zNzQ3VjEwNy45MjNIMjYuNDkwM1YxMDUuOTY5TDEuOTU1MTQgMTA1LjkyNloiIGZpbGw9IiNmZmZmZmYiLz4NCjxwYXRoIGQ9Ik03MC41MzAxIDExMy4zMjJINTIuODQxOFYxMDUuODc4SDcwLjUzMDFWMTEzLjMyMlpNNzYuNzA4NCAxMDIuNTIzSDQ2LjY2MzZDNDMuNTU3NiAxMDIuNTIzIDQxLjAxODYgMTA1LjExNyA0MS4wMTg2IDEwOC4yOVYxMTAuOTE4QzQxLjAxODYgMTE0LjA5MSA0My41NTc2IDExNi42ODUgNDYuNjYzNiAxMTYuNjg1SDc2LjcwODRDNzkuODE0NCAxMTYuNjg1IDgyLjM1MzQgMTE0LjA5MSA4Mi4zNTM0IDExMC45MThWMTA4LjI5QzgyLjM1MzQgMTA1LjExNyA3OS44MjI5IDEwMi41MjMgNzYuNzA4NCAxMDIuNTIzWiIgZmlsbD0iI2ZmZmZmZiIvPg0KPHBhdGggZD0iTTExNS41MiAxMDcuNTc2SDEwMC40MDVWMTExLjM4OEgxMTUuNTJWMTEzLjM4NUg5Ny4xOFYxMDUuNTg3TDExNS41MiAxMDUuNjIyVjEwNy41NzZaTTEyNC40MDcgMTA5LjU3M0MxMjQuNDA3IDEwOS41NzMgMTI3LjA4MSAxMDguNzI1IDEyNy4wODEgMTA2LjM2NUMxMjcuMDgxIDEwNC4wMjIgMTI1LjAwNyAxMDIuMzExIDEyMS44IDEwMi4zMTFIODUuNTY4NFYxMTYuNjYySDEyMS44QzEyNS43MSAxMTYuNjYyIDEyNy4wODEgMTE1LjA4OCAxMjcuMDgxIDExMy4yMTJDMTI3LjA4MSAxMTAuNDM3IDEyNC40MDcgMTA5LjU3MyAxMjQuNDA3IDEwOS41NzNaIiBmaWxsPSIjZmZmZmZmIi8+DQo8L3N2Zz4NCg==" alt="ZOV" />
        </div>
        <div class="header-divider"></div>
        <h1>{{ $proposalSubject }}</h1>
    </div>

    <!-- Date -->
    <div class="date-badge">
        {{ date('d.m.Y') }}
    </div>

    <!-- Content -->
    <div class="content">
        <div class="proposal-body">
            <p>{!! nl2br(e($proposalBody)) !!}</p>
        </div>
    </div>

    <!-- Divider -->
    <hr class="section-divider" />

    <!-- Sender Block -->
    <div class="sender-block">
        <p class="sender-regards">С уважением,</p>

        <table class="sender-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="sender-logo-cell">
                    <div class="sender-logo-box">
                        <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTI4IiBoZWlnaHQ9IjExNyIgdmlld0JveD0iMCAwIDEyOCAxMTciIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+DQo8cGF0aCBkPSJNMzguOTM1NyA0NC44NTUxQzM1LjA5MzQgNDYuNTE1MSAzMi4yNDEyIDQ4LjE4MzggMzIuMjQxMiA0OC4xODM4TDYzLjc2NyAwTDg3Ljk0NjggMzUuODM3NEM4Ny45NDY4IDM1LjgzNzQgODYuNjI2NSAzNS4wNzY2IDgyLjMxODcgMzQuNjg3NUM3Ni4yNzU5IDM0LjE0MjggNjcuODgwMiAzNS41MjYxIDYxLjMzODEgMzcuMDQ3OEM1NC44MjEzIDM4LjU2MDkgNDUuMzE3MSA0Mi4wODg0IDM4LjkzNTcgNDQuODU1MVoiIGZpbGw9IiNmZmZmZmYiLz4NCjxwYXRoIGQ9Ik05MC44NDA2IDQwLjQ5MDJMMTAxLjE0OSA1Ni40Njc5QzEwMS4xNDkgNTYuNDY3OSA5Mi45NzMzIDU1LjAxNTQgODUuODY0MiA1NS41NjAxQzc4Ljc1NSA1Ni4xMDQ4IDU5LjAxODYgNTguMjgzNiA1OS4wMTg2IDU4LjI4MzZDNTkuMDE4NiA1OC4yODM2IDc1LjU1NTkgNDkuNzUgODIuMTMxOSA0Ni4yOTE3Qzg4LjcwNzkgNDIuODUwNiA5MC44NDA2IDQwLjQ5MDIgOTAuODQwNiA0MC40OTAyWiIgZmlsbD0iI2ZmZmZmZiIvPg0KPHBhdGggZD0iTTEyOCA5Ny43MTA4TDEwOC4yNjQgNjcuMzgwOUMxMDguMjY0IDY3LjM4MDkgMTA4LjA4NiA3MS4zNzUzIDk5LjE5OTUgNzYuNDU5MUM5MC4zMTMgODEuNTQyOSA4NC4yNzAzIDg0LjA4NDggNzIuNzA5NCA4OC40NTFDNjEuMTU3IDkyLjgwODUgNDkuNDUyMSA5Ny44OTI0IDQ5LjQ1MjEgOTcuODkyNEwxMjggOTcuNzEwOFoiIGZpbGw9IiNmZmZmZmYiLz4NCjxwYXRoIGQ9Ik0wIDk4LjA3MThIMzUuMDg4OUMzNS4wODg5IDk4LjA3MTggNjAuNDUzNSA4OC4xMTE3IDc1LjE0NTggODAuNjQxNkM4OC4wMSA3NC4xMDUzIDk2Ljc1MjYgNjcuODE5NyA5NC41ODYgNjMuMzkzQzkyLjk4NjQgNjAuMTI0OCA4NS4wNDc5IDYwLjEyNDggNzUuNTYwNSA2MC4xMjQ4QzY3Ljk3NzMgNjAuMTI0OCA1OS4zOTU2IDYwLjc1NiA1NC45MzU0IDYwLjY2OTVDNDguODMzMyA2MC41NDg1IDUwLjg0NzYgNTkuODIyMiA1Mi45ODAzIDU4LjMwOTJDNTQuNTU0NSA1Ny4xOTM5IDcwLjAzMzkgNDguMzA1OCA3My45NjA5IDQ1Ljk2MjhDNzkuNTI5OCA0Mi42MzQxIDc3LjMzNzcgNDEgNzQuNzkwMyA0MS43ODY4QzcwLjgwNDEgNDMuMDE0NSA1Ny45NTY3IDQ4Ljg2NzggNDQuNjI3IDU3LjA0NjlDMzEuMjg4OCA2NS4yMTczIDIxLjUxMzcgNzMuNzUwOCAxNC4wNDkxIDgwLjg0MDVDNi41NzU5NyA4Ny45MDQyIDAgOTguMDcxOCAwIDk4LjA3MThaIiBmaWxsPSIjZmZmZmZmIi8+DQo8cGF0aCBkPSJNMS45NTUxNCAxMDUuOTI2VjEwMi44NEMxLjk1NTE0IDEwMi44NCAyOS41NzA5IDEwMi42NTggMzIuNzcgMTAyLjY1OEMzNS45NjkxIDEwMi42NTggMzguMDQyNiAxMDQuMzcgMzguMDQyNiAxMDYuNzEzQzM4LjA0MjYgMTA5LjA3MyAzNS4zNzY4IDEwOS45MiAzNS4zNzY4IDEwOS45MkMzNS4zNzY4IDEwOS45MiAzOC4wNDI2IDExMC43NzYgMzguMDQyNiAxMTMuNTUxQzM4LjA0MjYgMTE1LjQyNyAzNi42OCAxMTcuMDAxIDMyLjc3IDExNy4wMDFIMS43NzczNFYxMTMuNzMzSDI2LjQ5MDNWMTExLjczNkgxMS4zNzQ3VjEwNy45MjNIMjYuNDkwM1YxMDUuOTY5TDEuOTU1MTQgMTA1LjkyNloiIGZpbGw9IiNmZmZmZmYiLz4NCjxwYXRoIGQ9Ik03MC41MzAxIDExMy4zMjJINTIuODQxOFYxMDUuODc4SDcwLjUzMDFWMTEzLjMyMlpNNzYuNzA4NCAxMDIuNTIzSDQ2LjY2MzZDNDMuNTU3NiAxMDIuNTIzIDQxLjAxODYgMTA1LjExNyA0MS4wMTg2IDEwOC4yOVYxMTAuOTE4QzQxLjAxODYgMTE0LjA5MSA0My41NTc2IDExNi42ODUgNDYuNjYzNiAxMTYuNjg1SDc2LjcwODRDNzkuODE0NCAxMTYuNjg1IDgyLjM1MzQgMTE0LjA5MSA4Mi4zNTM0IDExMC45MThWMTA4LjI5QzgyLjM1MzQgMTA1LjExNyA3OS44MjI5IDEwMi41MjMgNzYuNzA4NCAxMDIuNTIzWiIgZmlsbD0iI2ZmZmZmZiIvPg0KPHBhdGggZD0iTTExNS41MiAxMDcuNTc2SDEwMC40MDVWMTExLjM4OEgxMTUuNTJWMTEzLjM4NUg5Ny4xOFYxMDUuNTg3TDExNS41MiAxMDUuNjIyVjEwNy41NzZaTTEyNC40MDcgMTA5LjU3M0MxMjQuNDA3IDEwOS41NzMgMTI3LjA4MSAxMDguNzI1IDEyNy4wODEgMTA2LjM2NUMxMjcuMDgxIDEwNC4wMjIgMTI1LjAwNyAxMDIuMzExIDEyMS44IDEwMi4zMTFIODUuNTY4NFYxMTYuNjYySDEyMS44QzEyNS43MSAxMTYuNjYyIDEyNy4wODEgMTE1LjA4OCAxMjcuMDgxIDExMy4yMTJDMTI3LjA4MSAxMTAuNDM3IDEyNC40MDcgMTA5LjU3MyAxMjQuNDA3IDEwOS41NzNaIiBmaWxsPSIjZmZmZmZmIi8+DQo8L3N2Zz4NCg==" alt="ZOV" />
                    </div>
                </td>
                <td style="vertical-align: top;">
                    <p class="sender-name">Челноков Евгений</p>
                    <p class="sender-position">Руководитель проекта ZOV-RUBONUS</p>
                    <p class="sender-contact-line">8 (915) 400-00-20 &middot; https://rubonus.pro &middot; info@rubonus.pro</p>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
