<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('icons/favicon-96x96.png') }}" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="{{ asset('icons/icon.svg') }}">
    <link rel="shortcut icon" href="{{ asset('icons/favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('icons/site.webmanifest') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'TechInbox - Portal' }}</title>

    <!-- Google Fonts for Modern Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Compiled assets via Laravel Vite -->
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    
    <!-- Fallback CDN Bootstrap (in case Vite is not built yet) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    @livewireStyles
    <!-- QR Code Generator Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Reusable Navbar Component -->
    <x-navbar />

    <!-- Main Content Area -->
    <main class="flex-grow-1 py-3 py-md-4">
        {{ $slot }}
    </main>

    <!-- Reusable Footer Component -->
    <x-footer />

    <!-- Bootstrap 5 JS Bundle (with Popper) CDN Fallback -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    @livewireScripts
</body>
</html>
