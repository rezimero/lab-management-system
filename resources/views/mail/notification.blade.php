<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;background:#f4f7fb;color:#172033;font-family:Arial,sans-serif;">
<div style="max-width:640px;margin:32px auto;padding:0 16px;">
    <div style="background:#ffffff;border:1px solid #dde5ef;border-radius:14px;padding:32px;">
        <h1 style="margin:0 0 16px;font-size:24px;line-height:1.25;">{{ $title }}</h1>
        <p style="margin:0 0 24px;line-height:1.65;">{{ $body }}</p>
        @if ($actionLabel && $actionUrl)
            <p style="margin:0 0 24px;">
                <a href="{{ $actionUrl }}" style="display:inline-block;background:#1d4ed8;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:700;">{{ $actionLabel }}</a>
            </p>
            <p style="margin:0 0 24px;color:#526070;font-size:13px;line-height:1.5;word-break:break-all;">If the button does not work, open this address:<br>{{ $actionUrl }}</p>
        @endif
        <p style="margin:0;color:#667085;font-size:12px;line-height:1.5;">{{ $footer }}</p>
    </div>
</div>
</body>
</html>
