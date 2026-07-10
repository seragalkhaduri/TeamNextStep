@php
    $lang = auth()->check() ? auth()->user()->preferred_language : 'ar';
    $dir = $lang === 'ar' ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'UIMP Core Platform' }}</title>
    @livewireStyles
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
            font-family: system-ui, -apple-system, sans-serif;
        }
    </style>
</head>
<body>
    {{ $slot }}
    @livewireScripts
</body>
</html>
