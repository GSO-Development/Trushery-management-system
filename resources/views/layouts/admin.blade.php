<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin — {{ config('app.name', 'Portal') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="antialiased bg-[#f8fafc]" x-data="{ sidebarOpen: window.innerWidth >= 1024 }" @resize.window="sidebarOpen = window.innerWidth >= 1024">

    <div class="flex h-screen overflow-hidden">

        <!-- ── Sidebar ─────────────────────────────────── -->
        <aside
               x-show="sidebarOpen"
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-150"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full"
               class="w-64 flex-shrink-0 bg-[#0f172a] flex flex-col shadow-2xl z-40">

            <!-- Logo area -->
            <div class="flex items-center gap-3 px-6 py-5 border-b border-white/10">
                <div class="w-9 h-9 rounded-lg bg-[#c3122e] flex items-center justify-center flex-shrink-0 shadow-lg shadow-[#c3122e]/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-white font-semibold text-sm leading-tight">{{ config('app.name') }}</p>
                    <p class="text-[#c3122e] text-xs font-semibold tracking-wide">Admin Panel</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <p class="px-4 text-[10px] text-slate-400 uppercase tracking-widest font-bold mb-2">Overview</p>
                
                <!-- Dashboard Link -->
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-[#c3122e] text-white shadow-lg shadow-[#c3122e]/30' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <p class="px-4 text-[10px] text-slate-400 uppercase tracking-widest font-bold mt-5 mb-2">Management</p>
                
                <!-- Users Link -->
                <a href="{{ route('admin.users.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-[#c3122e] text-white shadow-lg shadow-[#c3122e]/30' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Users</span>
                </a>

                <!-- Companies Link -->
                <a href="{{ route('admin.companies.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.companies.*') ? 'bg-[#c3122e] text-white shadow-lg shadow-[#c3122e]/30' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>Companies</span>
                </a>

                <!-- Banks Link -->
                <a href="{{ route('admin.banks.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.banks.*') ? 'bg-[#c3122e] text-white shadow-lg shadow-[#c3122e]/30' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <span>Banks</span>
                </a>

                <!-- Groups Link -->
                <a href="{{ route('admin.groups.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.groups.*') ? 'bg-[#c3122e] text-white shadow-lg shadow-[#c3122e]/30' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span>Groups</span>
                </a>
            </nav>

            <!-- Admin profile + logout -->
            <div class="px-3 py-4 border-t border-white/10">
                <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/5">
                    <div class="w-8 h-8 rounded-full bg-[#c3122e] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-white text-xs font-medium truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                        <p class="text-slate-400 text-[10px] truncate">Administrator</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-2 px-4 py-2 text-slate-300 hover:text-red-400 text-xs font-medium transition-colors rounded-lg hover:bg-white/5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- ── Main content area ──────────────────────── -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Top bar -->
            <header class="bg-white border-b border-slate-100 flex items-center justify-between h-16 px-6 flex-shrink-0 shadow-sm">
                <div class="flex items-center gap-4">
                    <!-- Hamburger sidebar toggle -->
                    <button class="text-slate-600 hover:text-[#c3122e] p-2 rounded-xl hover:bg-slate-100 transition-colors flex items-center gap-2 border border-slate-200"
                            @click="sidebarOpen = !sidebarOpen" title="Toggle Sidebar Navigation">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <span class="text-xs font-semibold hidden sm:inline">Menu</span>
                    </button>
                    @if(isset($header))
                        <div class="text-[#0f172a] font-semibold text-base">{{ $header }}</div>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-400">Welcome,</span>
                    <span class="text-xs font-semibold text-[#0f172a]">{{ auth()->user()->name ?? 'Admin' }}</span>
                    <div class="w-8 h-8 rounded-full bg-[#c3122e] flex items-center justify-center text-white text-xs font-bold">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-6 bg-[#f8fafc]">
                {{ $slot }}
            </main>
        </div>
    </div>

</body>
</html>
