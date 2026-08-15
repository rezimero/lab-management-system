{{ $title }}

{{ $body }}

@if ($actionLabel && $actionUrl)
{{ $actionLabel }}: {{ $actionUrl }}

@endif
{{ $footer }}
