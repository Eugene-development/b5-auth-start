<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $proposalSubject }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            color: #333333;
        }
        .email-wrapper {
            max-width: 640px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            padding: 32px 40px;
            text-align: center;
        }
        .email-header h1 {
            color: #ffffff;
            font-size: 22px;
            font-weight: 600;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .email-body {
            padding: 40px;
        }
        .email-body p {
            font-size: 15px;
            line-height: 1.7;
            margin: 0 0 16px;
            color: #444444;
        }
        .proposal-content {
            background-color: #fafafa;
            border-left: 4px solid #8b5cf6;
            padding: 24px;
            margin: 24px 0;
            border-radius: 0 8px 8px 0;
        }
        .proposal-content p {
            white-space: pre-wrap;
            margin: 0;
        }
        .sender-info {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        .sender-info p {
            font-size: 14px;
            color: #6b7280;
            margin: 4px 0;
        }
        .sender-info strong {
            color: #374151;
        }
        .email-footer {
            background-color: #f9fafb;
            padding: 24px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .email-footer p {
            font-size: 13px;
            color: #9ca3af;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>{{ $proposalSubject }}</h1>
        </div>

        <div class="email-body">
            <p>Здравствуйте!</p>
            <p>Вам направлено коммерческое предложение от фабрики «ЗОВ».</p>

            <div class="proposal-content">
                <p>{!! nl2br(e($proposalBody)) !!}</p>
            </div>

            @if($senderName || $senderEmail)
            <div class="sender-info">
                <p><strong>Отправитель:</strong></p>
                @if($senderName)
                <p>{{ $senderName }}</p>
                @endif
                @if($senderEmail)
                <p>{{ $senderEmail }}</p>
                @endif
            </div>
            @endif
        </div>

        <div class="email-footer">
            <p>&copy; {{ date('Y') }} Фабрика мебели «ЗОВ». Все права защищены.</p>
        </div>
    </div>
</body>
</html>
