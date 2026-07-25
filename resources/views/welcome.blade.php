<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <title>Project L.E.A.F. | IoT Hydroponic Cultivation System</title>
    
    <!-- Meta Description -->
    <meta name="description" content="Project L.E.A.F. - An IoT-Based Hydroponic Cultivation System for Optimized Lettuce Production powered by Livewire 3, Volt, Tailwind CSS, Vite, and ESP32 hardware automation.">

    <!-- Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Vite Assets / Tailwind CSS -->
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
        }

        /* Custom Keyframe Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        @keyframes float-delayed {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(8px); }
        }

        @keyframes pulse-subtle {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.85; transform: scale(1.02); }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        .animate-float-delayed {
            animation: float-delayed 7s ease-in-out infinite 1s;
        }

        .animate-pulse-subtle {
            animation: pulse-subtle 3s ease-in-out infinite;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(45, 106, 79, 0.12);
        }

        .glass-card-dark {
            background: rgba(27, 67, 50, 0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(149, 213, 178, 0.2);
        }

        .gradient-text {
            background: linear-gradient(135deg, #1B4332 0%, #2D6A4F 60%, #40916C 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .gradient-accent-text {
            background: linear-gradient(135deg, #2D6A4F 0%, #52B788 50%, #74C69D 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Subtle grid background pattern */
        .bg-grid-pattern {
            background-image: radial-gradient(rgba(45, 106, 79, 0.08) 1px, transparent 1px);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="min-h-screen antialiased bg-grid-pattern selection:bg-[#95D5B2] selection:text-[#1B4332] flex flex-col justify-between">

    <!-- Mobile Navigation Menu State Toggle (Alpine/Vanilla fallback script included) -->
    <div id="app" class="relative overflow-x-hidden flex-grow">

        <!-- ========================================== -->
        <!-- 1. STICKY NAVIGATION BAR                   -->
        <!-- ========================================== -->
        <header class="sticky top-0 z-50 w-full bg-[#F8FAF8]/85 backdrop-blur-xl border-b border-[#2D6A4F]/10 transition-all duration-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    
                    <!-- Left: Logo & Brand -->
                    <a href="#" class="flex items-center gap-3 group focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] rounded-xl p-1">
                        <div class="w-10 h-10 rounded-xl bg-[#2D6A4F] flex items-center justify-center text-white shadow-md shadow-[#2D6A4F]/20 group-hover:scale-105 transition-transform duration-300">
                            <!-- Leaf Icon -->
                            <svg class="w-6 h-6 text-[#95D5B2]" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xl font-extrabold tracking-tight text-[#1B4332] flex items-center gap-1.5">
                                Project L.E.A.F.
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#2D6A4F]/10 text-[#2D6A4F] border border-[#2D6A4F]/20">
                                    IoT v1.0
                                </span>
                            </span>
                        </div>
                    </a>

                    <!-- Center: Desktop Navigation Links -->
                    <nav class="hidden md:flex items-center space-x-1 lg:space-x-2">
                        <a href="#home" class="px-4 py-2 rounded-lg text-sm font-medium text-[#1B4332]/80 hover:text-[#2D6A4F] hover:bg-[#2D6A4F]/5 transition-colors">Home</a>
                        <a href="#features" class="px-4 py-2 rounded-lg text-sm font-medium text-[#1B4332]/80 hover:text-[#2D6A4F] hover:bg-[#2D6A4F]/5 transition-colors">Features</a>
                        <a href="#preview" class="px-4 py-2 rounded-lg text-sm font-medium text-[#1B4332]/80 hover:text-[#2D6A4F] hover:bg-[#2D6A4F]/5 transition-colors">Dashboard Preview</a>
                        <a href="#about" class="px-4 py-2 rounded-lg text-sm font-medium text-[#1B4332]/80 hover:text-[#2D6A4F] hover:bg-[#2D6A4F]/5 transition-colors">About</a>
                    </nav>

                    <!-- Right: Auth Buttons & Dashboard Link -->
                    <div class="hidden md:flex items-center space-x-4">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-[#2D6A4F] hover:bg-[#1B4332] shadow-md shadow-[#2D6A4F]/25 hover:shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] focus:ring-offset-2">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                    </svg>
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-[#1B4332] hover:text-[#2D6A4F] hover:bg-[#2D6A4F]/5 transition-colors">
                                    Log in
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-[#2D6A4F] hover:bg-[#1B4332] shadow-md shadow-[#2D6A4F]/25 hover:shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] focus:ring-offset-2">
                                        Get Started
                                    </a>
                                @endif
                            @endauth
                        @else
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-[#2D6A4F] hover:bg-[#1B4332] shadow-md shadow-[#2D6A4F]/25 hover:shadow-lg transition-all duration-200">
                                Dashboard
                            </a>
                        @endif
                    </div>

                    <!-- Mobile Menu Button -->
                    <div class="flex items-center md:hidden">
                        <button id="mobile-menu-button" type="button" class="p-2.5 rounded-xl text-[#1B4332] hover:bg-[#2D6A4F]/10 focus:outline-none focus:ring-2 focus:ring-[#2D6A4F]" aria-controls="mobile-menu" aria-expanded="false">
                            <span class="sr-only">Open main menu</span>
                            <svg id="menu-open-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                            <svg id="menu-close-icon" class="hidden w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                </div>
            </div>

            <!-- Mobile Navigation Dropdown -->
            <div id="mobile-menu" class="hidden md:hidden border-b border-[#2D6A4F]/10 bg-[#F8FAF8] px-4 pt-2 pb-6 space-y-2">
                <a href="#home" class="block px-3 py-2 rounded-lg text-base font-medium text-[#1B4332] hover:bg-[#2D6A4F]/10">Home</a>
                <a href="#features" class="block px-3 py-2 rounded-lg text-base font-medium text-[#1B4332] hover:bg-[#2D6A4F]/10">Features</a>
                <a href="#preview" class="block px-3 py-2 rounded-lg text-base font-medium text-[#1B4332] hover:bg-[#2D6A4F]/10">Dashboard Preview</a>
                <a href="#about" class="block px-3 py-2 rounded-lg text-base font-medium text-[#1B4332] hover:bg-[#2D6A4F]/10">About</a>
                <div class="pt-4 border-t border-[#2D6A4F]/10 flex flex-col space-y-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ route('dashboard') }}" class="w-full text-center px-5 py-3 rounded-xl text-base font-semibold text-white bg-[#2D6A4F]">
                                Go to Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="w-full text-center px-5 py-2.5 rounded-xl text-base font-semibold text-[#1B4332] bg-[#2D6A4F]/10">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="w-full text-center px-5 py-3 rounded-xl text-base font-semibold text-white bg-[#2D6A4F]">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    @else
                        <a href="{{ url('/dashboard') }}" class="w-full text-center px-5 py-3 rounded-xl text-base font-semibold text-white bg-[#2D6A4F]">
                            Go to Dashboard
                        </a>
                    @endif
                </div>
            </div>
        </header>

        <!-- ========================================== -->
        <!-- 2. HERO SECTION                            -->
        <!-- ========================================== -->
        <section id="home" class="relative pt-12 pb-20 md:pt-20 md:pb-32 overflow-hidden">
            <!-- Background Glow Gradients -->
            <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-gradient-to-br from-[#95D5B2]/30 via-[#40916C]/10 to-transparent rounded-full blur-3xl pointer-events-none -z-10"></div>
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                    
                    <!-- Left Side: Copy & Calls to Action -->
                    <div class="lg:col-span-6 space-y-6 text-left">
                        
                        <!-- Status Badge -->
                        <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-white/90 border border-[#2D6A4F]/15 shadow-sm">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#40916C] opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#2D6A4F]"></span>
                            </span>
                            <span class="text-xs font-semibold text-[#1B4332] tracking-wide uppercase">
                                IoT Hydroponic Automation Platform
                            </span>
                        </div>

                        <!-- Headline & Subtitle -->
                        <div class="space-y-3">
                            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-[#1B4332] leading-[1.15]">
                                Project <span class="gradient-text">L.E.A.F.</span>
                            </h1>
                            <h2 class="text-lg sm:text-xl lg:text-2xl font-bold text-[#40916C] leading-snug">
                                An IoT-Based Hydroponic Cultivation System for Optimized Lettuce Production
                            </h2>
                        </div>

                        <!-- Short Description -->
                        <p class="text-base sm:text-lg text-[#1B4332]/80 leading-relaxed max-w-xl font-normal">
                            Monitor environmental conditions, automate hydroponic operations, and optimize lettuce growth through real-time IoT technology. Designed for precision agriculture and maximum yield efficiency.
                        </p>

                        <!-- Buttons -->
                        <div class="pt-2 flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-7 py-3.5 rounded-xl text-base font-bold text-white bg-[#2D6A4F] hover:bg-[#1B4332] shadow-lg shadow-[#2D6A4F]/30 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 group">
                                        Access Dashboard
                                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-7 py-3.5 rounded-xl text-base font-bold text-white bg-[#2D6A4F] hover:bg-[#1B4332] shadow-lg shadow-[#2D6A4F]/30 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 group">
                                        Access Dashboard
                                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>
                                    </a>
                                @endauth
                            @else
                                <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center px-7 py-3.5 rounded-xl text-base font-bold text-white bg-[#2D6A4F] hover:bg-[#1B4332] shadow-lg shadow-[#2D6A4F]/30 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 group">
                                    Access Dashboard
                                    <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </a>
                            @endif

                            <a href="#features" class="inline-flex items-center justify-center px-7 py-3.5 rounded-xl text-base font-semibold text-[#1B4332] bg-white border border-[#2D6A4F]/20 hover:bg-[#2D6A4F]/5 hover:border-[#2D6A4F]/40 shadow-sm transition-all duration-200">
                                Learn More
                            </a>
                        </div>

                        <!-- Micro Metrics Ticker -->
                        <div class="pt-6 border-t border-[#2D6A4F]/10 grid grid-cols-3 gap-4">
                            <div>
                                <span class="block text-2xl font-extrabold text-[#1B4332]">24/7</span>
                                <span class="text-xs text-[#1B4332]/70 font-medium">Real-Time Sync</span>
                            </div>
                            <div>
                                <span class="block text-2xl font-extrabold text-[#2D6A4F]">5 Telemetry</span>
                                <span class="text-xs text-[#1B4332]/70 font-medium">Core Parameters</span>
                            </div>
                            <div>
                                <span class="block text-2xl font-extrabold text-[#1B4332]">ESP32</span>
                                <span class="text-xs text-[#1B4332]/70 font-medium">Smart Hardware</span>
                            </div>
                        </div>

                    </div>

                    <!-- Right Side: Premium IoT & Dashboard Interactive Visual Preview -->
                    <div class="lg:col-span-6 relative">
                        <!-- Floating Glass Dashboard Hub Card -->
                        <div class="relative rounded-3xl p-6 sm:p-8 glass-card shadow-2xl shadow-emerald-950/10 border border-[#2D6A4F]/15 space-y-6">
                            
                            <!-- Card Header: Device Header -->
                            <div class="flex items-center justify-between pb-4 border-b border-[#2D6A4F]/10">
                                <div class="flex items-center gap-3">
                                    <div class="w-3 h-3 rounded-full bg-[#2D6A4F] animate-ping"></div>
                                    <div>
                                        <h3 class="text-sm font-bold text-[#1B4332] tracking-tight">ESP32 NFT Channel Controller</h3>
                                        <p class="text-xs text-[#40916C]">Node ID: LEAF-NODE-01 • Status: Active</p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-[#95D5B2]/30 text-[#1B4332] border border-[#2D6A4F]/20">
                                    Lactuca sativa
                                </span>
                            </div>

                            <!-- Live Hydroponic Parameter Grid Visual -->
                            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                                
                                <!-- Water Temp Widget -->
                                <div class="p-4 rounded-2xl bg-white/90 border border-[#2D6A4F]/10 shadow-sm hover:border-[#2D6A4F]/30 transition-all">
                                    <div class="flex items-center justify-between text-xs text-[#1B4332]/70 font-medium mb-1">
                                        <span>Water Temp</span>
                                        <svg class="w-4 h-4 text-[#2D6A4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                    </div>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-2xl font-bold text-[#1B4332]">22.4</span>
                                        <span class="text-xs font-semibold text-[#40916C]">°C</span>
                                    </div>
                                    <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                                        <div class="bg-[#2D6A4F] h-1.5 rounded-full" style="width: 72%"></div>
                                    </div>
                                </div>

                                <!-- pH Level Widget -->
                                <div class="p-4 rounded-2xl bg-white/90 border border-[#2D6A4F]/10 shadow-sm hover:border-[#2D6A4F]/30 transition-all">
                                    <div class="flex items-center justify-between text-xs text-[#1B4332]/70 font-medium mb-1">
                                        <span>pH Level</span>
                                        <span class="px-1.5 py-0.5 text-[10px] font-bold bg-[#95D5B2]/30 text-[#1B4332] rounded">Optimal</span>
                                    </div>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-2xl font-bold text-[#1B4332]">6.20</span>
                                        <span class="text-xs font-semibold text-[#40916C]">pH</span>
                                    </div>
                                    <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                                        <div class="bg-[#40916C] h-1.5 rounded-full" style="width: 65%"></div>
                                    </div>
                                </div>

                                <!-- Electrical Conductivity (EC) -->
                                <div class="p-4 rounded-2xl bg-white/90 border border-[#2D6A4F]/10 shadow-sm hover:border-[#2D6A4F]/30 transition-all">
                                    <div class="flex items-center justify-between text-xs text-[#1B4332]/70 font-medium mb-1">
                                        <span>Nutrient EC</span>
                                        <svg class="w-4 h-4 text-[#40916C]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                        </svg>
                                    </div>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-2xl font-bold text-[#1B4332]">1.82</span>
                                        <span class="text-xs font-semibold text-[#40916C]">mS/cm</span>
                                    </div>
                                    <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                                        <div class="bg-[#2D6A4F] h-1.5 rounded-full" style="width: 80%"></div>
                                    </div>
                                </div>

                                <!-- Ambient Humidity -->
                                <div class="p-4 rounded-2xl bg-white/90 border border-[#2D6A4F]/10 shadow-sm hover:border-[#2D6A4F]/30 transition-all">
                                    <div class="flex items-center justify-between text-xs text-[#1B4332]/70 font-medium mb-1">
                                        <span>Air Humidity</span>
                                        <svg class="w-4 h-4 text-[#2D6A4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 001.059-9.891A7.001 7.001 0 005.999 7H5a5 5 0 00-2 9.9v.1z"/>
                                        </svg>
                                    </div>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-2xl font-bold text-[#1B4332]">68.5</span>
                                        <span class="text-xs font-semibold text-[#40916C]">%/RH</span>
                                    </div>
                                    <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                                        <div class="bg-[#52B788] h-1.5 rounded-full" style="width: 68%"></div>
                                    </div>
                                </div>

                            </div>

                            <!-- Channel Graphic Bar -->
                            <div class="p-4 rounded-2xl bg-[#1B4332] text-white flex items-center justify-between shadow-inner">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-xl bg-[#2D6A4F] text-[#95D5B2]">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs text-[#95D5B2] font-semibold uppercase tracking-wider">Automated NFT Flow Rate</div>
                                        <div class="text-sm font-bold text-white">4.2 Liters / Min • Normal Flow</div>
                                    </div>
                                </div>
                                <span class="text-xs px-2.5 py-1 rounded-md bg-[#2D6A4F] text-[#95D5B2] font-mono">PUMP: ON</span>
                            </div>

                        </div>

                        <!-- Floating Micro Floating Badge 1 -->
                        <div class="hidden sm:flex absolute -top-5 -left-5 p-3.5 rounded-2xl glass-card shadow-lg items-center gap-3 border border-[#2D6A4F]/20 animate-float">
                            <div class="w-8 h-8 rounded-lg bg-[#2D6A4F]/10 flex items-center justify-center text-[#2D6A4F]">
                                🌿
                            </div>
                            <div>
                                <p class="text-xs font-bold text-[#1B4332]">Lettuce Growth Phase</p>
                                <p class="text-[11px] text-[#40916C]">Day 18 • Vegetative</p>
                            </div>
                        </div>

                        <!-- Floating Micro Floating Badge 2 -->
                        <div class="hidden sm:flex absolute -bottom-6 -right-4 p-3.5 rounded-2xl glass-card-dark text-white shadow-xl items-center gap-3 animate-float-delayed">
                            <div class="w-8 h-8 rounded-lg bg-[#40916C] flex items-center justify-center text-[#95D5B2]">
                                ⚡
                            </div>
                            <div>
                                <p class="text-xs font-bold">Auto Dosing Control</p>
                                <p class="text-[11px] text-[#95D5B2]">Nutrient A & B Standardized</p>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- 3. FEATURES SECTION                        -->
        <!-- ========================================== -->
        <section id="features" class="py-20 sm:py-28 bg-white/70 relative border-t border-b border-[#2D6A4F]/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Section Header -->
                <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold tracking-wider uppercase bg-[#2D6A4F]/10 text-[#2D6A4F]">
                        Precision Engineering
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-[#1B4332] tracking-tight">
                        Built for Modern Hydroponic Farming
                    </h2>
                    <p class="text-base sm:text-lg text-[#1B4332]/75">
                        Our IoT framework pairs specialized sensors with micro-automation to guarantee peak lettuce health and water efficiency.
                    </p>
                </div>

                <!-- 4 Feature Cards Grid -->
                <div class="grid md:grid-cols-2 gap-8">
                    
                    <!-- Card 1: Real-Time Monitoring -->
                    <div class="group p-8 rounded-3xl bg-white border border-[#2D6A4F]/12 shadow-sm hover:shadow-xl hover:border-[#2D6A4F]/30 hover:-translate-y-1.5 transition-all duration-300 relative overflow-hidden">
                        <div class="w-14 h-14 rounded-2xl bg-[#2D6A4F]/10 flex items-center justify-center text-[#2D6A4F] mb-6 group-hover:scale-110 group-hover:bg-[#2D6A4F] group-hover:text-white transition-all duration-300">
                            <!-- Heroicon: Signal / Radio -->
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 010-7.778M12 20a9.99 9.99 0 000-14M15.889 16.404a5.5 5.5 0 000-7.778M12 12h.01"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-[#1B4332] mb-3 group-hover:text-[#2D6A4F] transition-colors">
                            📡 Real-Time Monitoring
                        </h3>
                        <p class="text-sm text-[#1B4332]/75 leading-relaxed">
                            Monitor sensor readings instantly. Live data streams continuously from ESP32 controllers directly to your interactive browser interface with zero latency delay.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-2">
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-100 text-[#1B4332]">Instant Telemetry</span>
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-100 text-[#1B4332]">Livewire 3 Sync</span>
                        </div>
                    </div>

                    <!-- Card 2: Environmental Monitoring -->
                    <div class="group p-8 rounded-3xl bg-white border border-[#2D6A4F]/12 shadow-sm hover:shadow-xl hover:border-[#2D6A4F]/30 hover:-translate-y-1.5 transition-all duration-300 relative overflow-hidden">
                        <div class="w-14 h-14 rounded-2xl bg-[#2D6A4F]/10 flex items-center justify-center text-[#2D6A4F] mb-6 group-hover:scale-110 group-hover:bg-[#2D6A4F] group-hover:text-white transition-all duration-300">
                            <!-- Heroicon: Sun / Thermometer -->
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-[#1B4332] mb-3 group-hover:text-[#2D6A4F] transition-colors">
                            🌡 Environmental Monitoring
                        </h3>
                        <p class="text-sm text-[#1B4332]/75 leading-relaxed">
                            Comprehensive tracking of ambient air Temperature and Humidity. Maintain optimal VPD (Vapor Pressure Deficit) levels tailored specifically for lettuce crop yield.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-2">
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-100 text-[#1B4332]">Ambient Air Temp</span>
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-100 text-[#1B4332]">Relative Humidity</span>
                        </div>
                    </div>

                    <!-- Card 3: Water Quality Monitoring -->
                    <div class="group p-8 rounded-3xl bg-white border border-[#2D6A4F]/12 shadow-sm hover:shadow-xl hover:border-[#2D6A4F]/30 hover:-translate-y-1.5 transition-all duration-300 relative overflow-hidden">
                        <div class="w-14 h-14 rounded-2xl bg-[#2D6A4F]/10 flex items-center justify-center text-[#2D6A4F] mb-6 group-hover:scale-110 group-hover:bg-[#2D6A4F] group-hover:text-white transition-all duration-300">
                            <!-- Heroicon: Droplet / Water -->
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-[#1B4332] mb-3 group-hover:text-[#2D6A4F] transition-colors">
                            💧 Water Quality Monitoring
                        </h3>
                        <p class="text-sm text-[#1B4332]/75 leading-relaxed">
                            Detailed telemetry for Water Temperature, pH Balance, Electrical Conductivity (EC), Water Flow rate, and Reservoir Tank Water Level to eliminate nutrient stress.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-2">
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-100 text-[#1B4332]">pH & EC Sensor</span>
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-100 text-[#1B4332]">Flow & Reservoir Level</span>
                        </div>
                    </div>

                    <!-- Card 4: Smart Automation -->
                    <div class="group p-8 rounded-3xl bg-white border border-[#2D6A4F]/12 shadow-sm hover:shadow-xl hover:border-[#2D6A4F]/30 hover:-translate-y-1.5 transition-all duration-300 relative overflow-hidden">
                        <div class="w-14 h-14 rounded-2xl bg-[#2D6A4F]/10 flex items-center justify-center text-[#2D6A4F] mb-6 group-hover:scale-110 group-hover:bg-[#2D6A4F] group-hover:text-white transition-all duration-300">
                            <!-- Heroicon: Lightning Bolt / Sparkles -->
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-[#1B4332] mb-3 group-hover:text-[#2D6A4F] transition-colors">
                            ⚡ Smart Automation
                        </h3>
                        <p class="text-sm text-[#1B4332]/75 leading-relaxed">
                            Proactive automated alerts, notifications, cooling fan state triggers, automated dosing relays, and intelligent threshold monitoring to safeguard crops 24/7.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-2">
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-100 text-[#1B4332]">Auto Relay Control</span>
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-100 text-[#1B4332]">Threshold Alerts</span>
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- ========================================== -->
        <!-- 4. DASHBOARD PREVIEW SECTION               -->
        <!-- ========================================== -->
        <section id="preview" class="py-20 sm:py-28 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center max-w-3xl mx-auto mb-12 space-y-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold tracking-wider uppercase bg-[#2D6A4F]/10 text-[#2D6A4F]">
                        Live Visual Interface
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-[#1B4332] tracking-tight">
                        Designed for Precision Hydroponic Management
                    </h2>
                    <p class="text-base sm:text-lg text-[#1B4332]/75">
                        A clean, intuitive control center that gives you absolute insight into your farm's vital telemetry.
                    </p>
                </div>

                <!-- Browser Mockup Window -->
                <div class="rounded-3xl bg-white border border-[#2D6A4F]/20 shadow-2xl shadow-emerald-950/10 overflow-hidden">
                    
                    <!-- Browser Window Header Bar -->
                    <div class="px-6 py-4 bg-[#1B4332] flex items-center justify-between border-b border-[#2D6A4F]/30">
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span>
                            <span class="w-3 h-3 rounded-full bg-yellow-500 inline-block"></span>
                            <span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span>
                        </div>
                        <div class="flex-1 max-w-xl mx-4">
                            <div class="bg-black/30 text-xs font-mono text-[#95D5B2] px-4 py-1.5 rounded-lg text-center truncate border border-[#2D6A4F]/40 flex items-center justify-center gap-2">
                                <svg class="w-3.5 h-3.5 text-[#95D5B2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                https://projectleaf/dashboard
                            </div>
                        </div>
                        <div class="hidden sm:flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-[#95D5B2] animate-pulse"></span>
                            <span class="text-xs font-semibold text-[#95D5B2] font-mono">ESP32 ONLINE</span>
                        </div>
                    </div>

                    <!-- Mockup Dashboard Canvas Content -->
                    <div class="p-6 sm:p-10 bg-[#F8FAF8] space-y-8">
                        
                        <!-- Dashboard Top Widgets Row -->
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <!-- Temp -->
                            <div class="p-4 rounded-2xl bg-white border border-[#2D6A4F]/10 shadow-sm">
                                <span class="text-xs text-[#1B4332]/70 font-semibold block">Air Temp</span>
                                <span class="text-2xl font-extrabold text-[#1B4332] mt-1 block">24.2 °C</span>
                                <span class="text-[11px] text-[#2D6A4F] font-medium flex items-center gap-1 mt-1">
                                    ✓ Within Bounds
                                </span>
                            </div>
                            <!-- Humidity -->
                            <div class="p-4 rounded-2xl bg-white border border-[#2D6A4F]/10 shadow-sm">
                                <span class="text-xs text-[#1B4332]/70 font-semibold block">Air Humidity</span>
                                <span class="text-2xl font-extrabold text-[#1B4332] mt-1 block">68 %</span>
                                <span class="text-[11px] text-[#2D6A4F] font-medium flex items-center gap-1 mt-1">
                                    ✓ Optimal VPD
                                </span>
                            </div>
                            <!-- pH -->
                            <div class="p-4 rounded-2xl bg-white border border-[#2D6A4F]/10 shadow-sm">
                                <span class="text-xs text-[#1B4332]/70 font-semibold block">Solution pH</span>
                                <span class="text-2xl font-extrabold text-[#1B4332] mt-1 block">6.2 pH</span>
                                <span class="text-[11px] text-[#2D6A4F] font-medium flex items-center gap-1 mt-1">
                                    ✓ Target 5.8-6.5
                                </span>
                            </div>
                            <!-- EC -->
                            <div class="p-4 rounded-2xl bg-white border border-[#2D6A4F]/10 shadow-sm">
                                <span class="text-xs text-[#1B4332]/70 font-semibold block">Nutrient EC</span>
                                <span class="text-2xl font-extrabold text-[#1B4332] mt-1 block">1.8 mS</span>
                                <span class="text-[11px] text-[#2D6A4F] font-medium flex items-center gap-1 mt-1">
                                    ✓ Balanced Formula
                                </span>
                            </div>
                            <!-- Water Level -->
                            <div class="p-4 rounded-2xl bg-white border border-[#2D6A4F]/10 shadow-sm col-span-2 md:col-span-1">
                                <span class="text-xs text-[#1B4332]/70 font-semibold block">Water Tank</span>
                                <span class="text-2xl font-extrabold text-[#1B4332] mt-1 block">88 %</span>
                                <span class="text-[11px] text-[#2D6A4F] font-medium flex items-center gap-1 mt-1">
                                    ✓ Reservoir Full
                                </span>
                            </div>
                        </div>

                        <!-- Main Dashboard Analytics Grid (Charts & Status) -->
                        <div class="grid lg:grid-cols-12 gap-6">
                            
                            <!-- Telemetry Chart Mockup (SVG line chart) -->
                            <div class="lg:col-span-8 p-6 rounded-2xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-base font-bold text-[#1B4332]">24-Hour Telemetry Analytics</h4>
                                        <p class="text-xs text-[#1B4332]/70">Water Temperature vs. Solution pH</p>
                                    </div>
                                    <div class="flex items-center gap-4 text-xs font-semibold">
                                        <span class="flex items-center gap-1.5 text-[#2D6A4F]">
                                            <span class="w-3 h-0.5 bg-[#2D6A4F] rounded-full"></span> pH Level
                                        </span>
                                        <span class="flex items-center gap-1.5 text-[#40916C]">
                                            <span class="w-3 h-0.5 bg-[#40916C] rounded-full"></span> Temp (°C)
                                        </span>
                                    </div>
                                </div>

                                <!-- SVG Line Chart Visual -->
                                <div class="h-48 w-full pt-4">
                                    <svg class="w-full h-full" viewBox="0 0 500 150" fill="none">
                                        <!-- Grid lines -->
                                        <line x1="0" y1="30" x2="500" y2="30" stroke="#f1f5f9" stroke-width="1"/>
                                        <line x1="0" y1="75" x2="500" y2="75" stroke="#f1f5f9" stroke-width="1"/>
                                        <line x1="0" y1="120" x2="500" y2="120" stroke="#f1f5f9" stroke-width="1"/>
                                        
                                        <!-- Temperature Line -->
                                        <path d="M0,90 Q 75,60 150,80 T 300,70 T 450,50 L 500,65" fill="none" stroke="#40916C" stroke-width="2.5" stroke-linecap="round"/>
                                        
                                        <!-- pH Line -->
                                        <path d="M0,45 Q 100,55 200,40 T 350,48 L 500,42" fill="none" stroke="#2D6A4F" stroke-width="3" stroke-linecap="round"/>
                                        
                                        <!-- Gradient Fill Under pH -->
                                        <path d="M0,45 Q 100,55 200,40 T 350,48 L 500,42 V 150 H 0 Z" fill="url(#chartGradient)" opacity="0.15"/>
                                        <defs>
                                            <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stop-color="#2D6A4F"/>
                                                <stop offset="100%" stop-color="#FFFFFF"/>
                                            </linearGradient>
                                        </defs>

                                        <!-- Highlight Points -->
                                        <circle cx="200" cy="40" r="4" fill="#2D6A4F" stroke="#FFFFFF" stroke-width="2"/>
                                        <circle cx="350" cy="48" r="4" fill="#2D6A4F" stroke="#FFFFFF" stroke-width="2"/>
                                    </svg>
                                </div>
                                <div class="flex justify-between text-[11px] text-[#1B4332]/60 font-mono pt-2">
                                    <span>00:00</span>
                                    <span>04:00</span>
                                    <span>08:00</span>
                                    <span>12:00</span>
                                    <span>16:00</span>
                                    <span>20:00</span>
                                    <span>LIVE</span>
                                </div>
                            </div>

                            <!-- Hardware Actuators & Relay Status Panel -->
                            <div class="lg:col-span-4 p-6 rounded-2xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-4">
                                <h4 class="text-base font-bold text-[#1B4332]">Smart Automation Controls</h4>
                                <div class="space-y-3">
                                    
                                    <!-- Actuator Item 1 -->
                                    <div class="p-3 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-[#2D6A4F]/10 text-[#2D6A4F] flex items-center justify-center font-bold text-xs">
                                                P1
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-[#1B4332]">Nutrient Pump A</p>
                                                <p class="text-[10px] text-[#1B4332]/70">Dosing Interval: 15m</p>
                                            </div>
                                        </div>
                                        <span class="px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-[#1B4332] rounded-full">ACTIVE</span>
                                    </div>

                                    <!-- Actuator Item 2 -->
                                    <div class="p-3 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-[#2D6A4F]/10 text-[#2D6A4F] flex items-center justify-center font-bold text-xs">
                                                F1
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-[#1B4332]">Cooling Intake Fan</p>
                                                <p class="text-[10px] text-[#1B4332]/70">VPD Auto Controller</p>
                                            </div>
                                        </div>
                                        <span class="px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-[#1B4332] rounded-full">RUNNING</span>
                                    </div>

                                    <!-- Actuator Item 3 -->
                                    <div class="p-3 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-gray-200 text-gray-700 flex items-center justify-center font-bold text-xs">
                                                P2
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-[#1B4332]">pH Adjuster Pump</p>
                                                <p class="text-[10px] text-[#1B4332]/70">Standby (pH Normal)</p>
                                            </div>
                                        </div>
                                        <span class="px-2 py-0.5 text-[10px] font-bold bg-gray-200 text-gray-700 rounded-full">IDLE</span>
                                    </div>

                                </div>
                            </div>

                        </div>

                    </div>
                </div>

            </div>
        </section>

        <!-- ========================================== -->
        <!-- 5. ABOUT SECTION                           -->
        <!-- ========================================== -->
        <section id="about" class="py-20 sm:py-28 bg-white/80 border-t border-[#2D6A4F]/10 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="grid lg:grid-cols-12 gap-12 items-center">
                    
                    <div class="lg:col-span-6 space-y-6">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold tracking-wider uppercase bg-[#2D6A4F]/10 text-[#2D6A4F]">
                            Academic & Technical Innovation
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-extrabold text-[#1B4332] tracking-tight">
                            The Science of Automated Lettuce Hydroponics
                        </h2>
                        <p class="text-base text-[#1B4332]/80 leading-relaxed">
                            Project L.E.A.F. combines embedded microelectronics with web automation to streamline hydroponic NFT (Nutrient Film Technique) operations. By standardizing pH and EC telemetry, farmers reduce water consumption and optimize growth cycles for superior lettuce crop yields.
                        </p>
                        
                        <div class="space-y-4 pt-2">
                            <div class="flex items-start gap-3">
                                <div class="mt-1 w-5 h-5 rounded-full bg-[#2D6A4F] text-white flex items-center justify-center text-xs font-bold shrink-0">✓</div>
                                <div>
                                    <h4 class="text-sm font-bold text-[#1B4332]">Precision Nutrient Control</h4>
                                    <p class="text-xs text-[#1B4332]/70">Eliminates manual testing errors with continuous electrical conductivity tracking.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="mt-1 w-5 h-5 rounded-full bg-[#2D6A4F] text-white flex items-center justify-center text-xs font-bold shrink-0">✓</div>
                                <div>
                                    <h4 class="text-sm font-bold text-[#1B4332]">Hardware Resilience</h4>
                                    <p class="text-xs text-[#1B4332]/70">Dual-core ESP32 microcontrollers ensure 24/7 continuous operation even during transient network drops.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="mt-1 w-5 h-5 rounded-full bg-[#2D6A4F] text-white flex items-center justify-center text-xs font-bold shrink-0">✓</div>
                                <div>
                                    <h4 class="text-sm font-bold text-[#1B4332]">Modern Stack Architecture</h4>
                                    <p class="text-xs text-[#1B4332]/70">Constructed with Livewire 3 reactive components and Tailwind CSS.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Stack Diagram Visual -->
                    <div class="lg:col-span-6">
                        <div class="p-8 rounded-3xl glass-card border border-[#2D6A4F]/15 shadow-xl space-y-6">
                            <h3 class="text-lg font-bold text-[#1B4332] pb-2 border-b border-[#2D6A4F]/10 flex items-center justify-between">
                                <span>System Architecture</span>
                                <span class="text-xs font-mono text-[#40916C]">End-to-End IoT Pipeline</span>
                            </h3>

                            <!-- Architecture Steps -->
                            <div class="space-y-4">
                                <div class="p-4 rounded-2xl bg-white border border-[#2D6A4F]/10 flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-[#2D6A4F] text-[#95D5B2] flex items-center justify-center font-extrabold text-sm shrink-0">
                                        01
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-[#1B4332]">ESP32 Hardware & Sensors</h4>
                                        <p class="text-xs text-[#1B4332]/70">Analog pH Probe • EC Sensor • DS18B20 Water Temp • DHT22 Ambient</p>
                                    </div>
                                </div>

                                <div class="p-4 rounded-2xl bg-white border border-[#2D6A4F]/10 flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-[#40916C] text-white flex items-center justify-center font-extrabold text-sm shrink-0">
                                        02
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-[#1B4332]">Secure Telemetry Gateway</h4>
                                        <p class="text-xs text-[#1B4332]/70">REST API & Real-time WebSockets packet transmission</p>
                                    </div>
                                </div>

                                <div class="p-4 rounded-2xl bg-white border border-[#2D6A4F]/10 flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-[#1B4332] text-[#95D5B2] flex items-center justify-center font-extrabold text-sm shrink-0">
                                        03
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-[#1B4332]">Project L.E.A.F. + Livewire 3 Application</h4>
                                        <p class="text-xs text-[#1B4332]/70">Real-time reactive dashboard, persistent database logging & automated relays</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- ========================================== -->
        <!-- 6. CALL-TO-ACTION SECTION                  -->
        <!-- ========================================== -->
        <section class="py-20 sm:py-24 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="relative rounded-3xl bg-gradient-to-br from-[#1B4332] via-[#2D6A4F] to-[#1B4332] p-10 sm:p-16 text-center text-white shadow-2xl overflow-hidden">
                    
                    <!-- Decorative Radial Lights -->
                    <div class="absolute -top-24 -right-24 w-72 h-72 bg-[#95D5B2]/20 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-[#40916C]/30 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="relative z-10 max-w-3xl mx-auto space-y-6">
                        <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-white/10 text-[#95D5B2] border border-white/10 backdrop-blur-md">
                            🌱 Automated Farm Management
                        </span>
                        
                        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight leading-tight">
                            Ready to monitor your hydroponic farm?
                        </h2>
                        
                        <p class="text-base sm:text-lg text-[#95D5B2]/90 leading-relaxed font-normal">
                            Take complete control of your hydroponic lettuce environment with real-time IoT insights, automation alerts, and precision growth parameters.
                        </p>

                        <div class="pt-4">
                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-xl text-base font-bold text-[#1B4332] bg-white hover:bg-[#95D5B2] shadow-xl transition-all duration-200 group transform hover:-translate-y-0.5">
                                        Go to Dashboard
                                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-xl text-base font-bold text-[#1B4332] bg-white hover:bg-[#95D5B2] shadow-xl transition-all duration-200 group transform hover:-translate-y-0.5">
                                        Go to Dashboard
                                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>
                                    </a>
                                @endauth
                            @else
                                <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-xl text-base font-bold text-[#1B4332] bg-white hover:bg-[#95D5B2] shadow-xl transition-all duration-200 group transform hover:-translate-y-0.5">
                                    Go to Dashboard
                                    <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- ========================================== -->
        <!-- 7. FOOTER                                  -->
        <!-- ========================================== -->
        <footer class="bg-[#1B4332] text-white border-t border-[#2D6A4F]/30 pt-16 pb-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-12 gap-10 pb-12 border-b border-[#2D6A4F]/40">
                    
                    <!-- Left Brand Info -->
                    <div class="md:col-span-6 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-[#2D6A4F] flex items-center justify-center text-white">
                                🌱
                            </div>
                            <span class="text-xl font-bold text-white tracking-tight">Project L.E.A.F.</span>
                        </div>
                        <p class="text-xs sm:text-sm text-[#95D5B2]/80 leading-relaxed max-w-md">
                            Lettuce Environment Automation & Farming. An IoT-Based Hydroponic Cultivation System designed for precision monitoring, real-time sensor analytics, and crop yield optimization.
                        </p>
                    </div>

                    <!-- Tech Stack Specification Badges -->
                    <div class="md:col-span-6 space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-[#95D5B2]">System Architecture Specs</h4>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#2D6A4F]/60 text-white border border-[#2D6A4F]">Project L.E.A.F.</span>
                            <span class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#2D6A4F]/60 text-white border border-[#2D6A4F]">Volt</span>
                            <span class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#2D6A4F]/60 text-white border border-[#2D6A4F]">Livewire 3</span>
                            <span class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#2D6A4F]/60 text-white border border-[#2D6A4F]">ESP32</span>
                            <span class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#2D6A4F]/60 text-white border border-[#2D6A4F]">Tailwind CSS</span>
                        </div>
                    </div>

                </div>

                <!-- Footer Copyright & Legal -->
                <div class="pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-[#95D5B2]/70 gap-4">
                    <p>© 2026 Project L.E.A.F. All rights reserved.</p>
                    <p class="font-mono text-[11px]">IoT Hydroponic Cultivation & Automation Platform</p>
                </div>

            </div>
        </footer>

    </div>

    <!-- Minimal JavaScript for Mobile Menu Toggle Interaction -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuBtn = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            const openIcon = document.getElementById('menu-open-icon');
            const closeIcon = document.getElementById('menu-close-icon');

            if (menuBtn && mobileMenu) {
                menuBtn.addEventListener('click', function() {
                    const isExpanded = menuBtn.getAttribute('aria-expanded') === 'true';
                    menuBtn.setAttribute('aria-expanded', !isExpanded);
                    mobileMenu.classList.toggle('hidden');
                    openIcon.classList.toggle('hidden');
                    closeIcon.classList.toggle('hidden');
                });
            }
        });
    </script>
</body>
</html>
