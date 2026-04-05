<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $proposalSubject }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 20px;
            color: #333333;
            font-size: 14px;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #8b5cf6;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #6366f1;
            font-size: 24px;
            margin: 0;
        }
        .content {
            margin-bottom: 40px;
        }
        .content p {
            white-space: pre-wrap;
            margin-bottom: 15px;
            text-align: justify;
        }
        .footer {
            margin-top: 40px;
            border-top: 1px solid #eeeeee;
            padding-top: 20px;
            font-size: 12px;
            color: #777777;
        }
        .sender {
            margin-top: 30px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $proposalSubject }}</h1>
    </div>

    <div class="content">
        <p>{!! nl2br(e($proposalBody)) !!}</p>
    </div>

    @if($senderName || $senderEmail)
    <div class="sender">
        <p>С уважением,</p>
        @if($senderName)
        <p>{{ $senderName }}</p>
        @endif
        @if($senderEmail)
        <p>{{ $senderEmail }}</p>
        @endif
    </div>
    @endif

    <div class="footer">
        <p>Данное коммерческое предложение подготовлено компанией FABRIKA ZOV.</p>
        <p>{{ date('d.m.Y') }}</p>
    </div>
</body>
</html>
