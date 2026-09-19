<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'PRESTO') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-navbar />

    <div class="min-h-custom">
        @if (session('message') || session('errorMessage'))
            <div class="container pt-3">
                @if (session('message'))
                    <div class="alert alert-success mb-0">{{ session('message') }}</div>
                @endif
                @if (session('errorMessage'))
                    <div class="alert alert-danger mb-0">{{ session('errorMessage') }}</div>
                @endif
            </div>
        @endif

        {{ $slot }}
    </div>

    <x-footer />
</body>
</html>
