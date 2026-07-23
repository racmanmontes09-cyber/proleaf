<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Project L.E.A.F.') }} - Dashboard</title>

        <!-- Fonts: Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- ApexCharts CDN & Vite Assets -->
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --leaf-primary: #2D6A4F;
                --leaf-secondary: #40916C;
                --leaf-accent: #95D5B2;
                --leaf-bg: #F8FAF8;
                --leaf-text: #1B4332;
            }

            body {
                font-family: 'Inter', sans-serif;
                background-color: var(--leaf-bg);
                color: var(--leaf-text);
                overflow-x: hidden;
            }

            .glass-card {
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(45, 106, 79, 0.12);
            }

            .bg-grid-pattern {
                background-image: radial-gradient(rgba(45, 106, 79, 0.06) 1px, transparent 1px);
                background-size: 24px 24px;
            }
        </style>
    </head>
    <body class="h-full font-sans antialiased bg-[#F8FAF8] text-[#1B4332] bg-grid-pattern selection:bg-[#95D5B2] selection:text-[#1B4332] overflow-x-hidden">
        
        <!-- Root App State Wrapper -->
        <div x-data="{ sidebarOpen: false, activeTab: 'dashboard' }" class="min-h-screen bg-[#F8FAF8] relative overflow-x-hidden">
            
            <!-- Livewire Navigation Component (Fixed Desktop Sidebar, Mobile Drawer & Top Navbar Header) -->
            <livewire:layout.navigation />

            <!-- Main Content Area (Offset by lg:pl-64 for fixed 256px sidebar, pt-20 for fixed 64px header) -->
            <div class="lg:pl-64 pt-20 flex flex-col min-h-screen min-w-0 transition-all duration-200">
                
                <!-- Main Page Slot Container -->
                <main class="flex-1 w-full max-w-[1680px] mx-auto min-w-0 px-3 sm:px-4 lg:px-6 xl:px-8 2xl:px-10 py-4 sm:py-6 lg:py-8 overflow-x-hidden">
                    {{ $slot }}
                </main>

            </div>

        </div>

    </body>
</html>
