<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicon-96x96.png') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon-192x192.png') }}">
        <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('favicon-512x512.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

        @php
            $currentRoute = request()->route()?->getName();
            $pageTitles = [
                'login' => 'Sign In | Project L.E.A.F.',
                'register' => 'Sign Up | Project L.E.A.F.',
                'password.request' => 'Forgot Password | Project L.E.A.F.',
                'password.reset' => 'Reset Password | Project L.E.A.F.',
                'verification.notice' => 'Email Verification | Project L.E.A.F.',
                'password.confirm' => 'Confirm Password | Project L.E.A.F.',
            ];
        @endphp
        <title>{{ $pageTitles[$currentRoute] ?? 'Project L.E.A.F. | IoT-Based Hydroponic Cultivation System' }}</title>

        <!-- Fonts: Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body {
                font-family: 'Inter', sans-serif;
            }
        </style>
    </head>
    <body class="font-sans antialiased bg-[#F8FAF8] text-[#1B4332] min-h-screen selection:bg-[#95D5B2] selection:text-[#1B4332]">
        {{ $slot }}
    </body>
</html>
