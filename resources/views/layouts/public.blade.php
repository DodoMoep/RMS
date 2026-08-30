<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
<div class="container py-5" style="max-width: 720px;">
    <div class="mb-4 text-muted small">
        <img src="{{ asset('assets/images/rms.png') }}" height="28" alt="{{ config('app.name') }}" class="me-2">
    </div>
    {{ $slot }}
</div>
</body>
</html>
