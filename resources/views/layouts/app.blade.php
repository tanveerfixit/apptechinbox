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
    <title>{{ $title ?? 'Businesses Apps By Techinbox' }}</title>

    <!-- Google Fonts for Modern Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Compiled Tailwind CSS & JS assets via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    <!-- QR Code Generator Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
</head>
<body class="flex flex-col min-h-screen bg-[#f3f3f3] text-[#242424] font-sans antialiased">

    <!-- Reusable Navbar Component -->
    <x-navbar />

    <!-- Main Content Area -->
    <main class="flex-1 py-4">
        {{ $slot }}
    </main>

    <!-- Reusable Footer Component -->
    <x-footer />

    @livewireScripts
</body>
</html>
