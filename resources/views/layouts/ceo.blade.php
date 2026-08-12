<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Portal') }} — CEO Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="antialiased bg-[#f8fafc]" x-data="{ sidebarOpen: window.innerWidth >= 1024 }" @resize.window="sidebarOpen = window.innerWidth >= 1024">

    <div class="flex h-screen overflow-hidden">

        <!-- ── CEO Sidebar ─────────────────────────────────── -->
        <aside
               x-show="sidebarOpen"
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-150"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full"
               class="w-64 flex-shrink-0 bg-[#0f172a] flex flex-col shadow-2xl z-40">

            <!-- Brand header -->
            <div class="flex items-center gap-3 px-6 py-5 border-b border-white/10">
                <div class="w-9 h-9 rounded-lg bg-[#c3122e] flex items-center justify-center flex-shrink-0 shadow-lg shadow-[#c3122e]/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-white font-semibold text-sm leading-tight">{{ config('app.name') }}</p>
                    <p class="text-[#c3122e] text-xs font-medium">CEO Portal</p>
                </div>
            </div>

            <!-- CEO Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <p class="px-4 text-[10px] text-slate-500 uppercase tracking-widest font-semibold mb-2">
                    Executive Access
                </p>

                <!-- CEO Dashboard link -->
                <a href="{{ route('ceo.dashboard') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('ceo.dashboard')
                              ? 'bg-[#c3122e] text-white shadow-lg shadow-[#c3122e]/30'
                              : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="truncate">Overview Dashboard</span>
                </a>

                <!-- Companies section -->
                @php
                    $ceoCompanies = auth()->user()->ceoCompanies ?? collect();
                @endphp

                @if($ceoCompanies->isNotEmpty())
                    <div class="mt-4 pt-4 border-t border-white/10">
                        <p class="px-4 text-[10px] text-slate-500 uppercase tracking-widest font-semibold mb-2">
                            My Companies
                        </p>
                        @foreach($ceoCompanies as $ceoCompany)
                            <a href="{{ url('/'.$ceoCompany->slug.'/summary-dashboard') }}"
                               class="flex items-center gap-3 px-4 py-2 rounded-lg text-slate-400 text-xs font-medium transition-all duration-200 hover:bg-white/10 hover:text-white">
                                <div class="w-5 h-5 rounded-md bg-[#c3122e]/20 flex items-center justify-center flex-shrink-0">
                                    <span class="text-[#c3122e] text-[9px] font-bold">{{ strtoupper(substr($ceoCompany->name, 0, 2)) }}</span>
                                </div>
                                <span class="truncate">{{ $ceoCompany->name }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </nav>

            <!-- User profile + logout -->
            <div class="px-3 py-4 border-t border-white/10">
                <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/5">
                    <div class="w-8 h-8 rounded-full bg-[#c3122e] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        {{ strtoupper(substr(auth()->user()->name ?? 'C', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-white text-xs font-medium truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[#c3122e] text-[10px] truncate font-medium">CEO</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-2 px-4 py-2 text-slate-400 hover:text-red-400 text-xs font-medium transition-colors rounded-lg hover:bg-white/5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </aside>

        <!-- ── Main content ──────────────────────────── -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Top bar -->
            <header class="bg-white border-b border-slate-100 flex items-center justify-between h-16 px-6 flex-shrink-0 shadow-sm">
                <div class="flex items-center gap-4">
                    <button class="text-slate-600 hover:text-[#c3122e] p-2 rounded-xl hover:bg-slate-100 transition-colors flex items-center gap-2 border border-slate-200"
                            @click="sidebarOpen = !sidebarOpen" title="Toggle Sidebar Navigation">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <span class="text-xs font-semibold hidden sm:inline">Menu</span>
                    </button>
                    @hasSection('header')
                        <div class="text-[#0f172a] font-semibold text-base">@yield('header')</div>
                    @elseif(isset($header))
                        <div class="text-[#0f172a] font-semibold text-base">{{ $header }}</div>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#fdf2f4] border border-[#f8d7da] text-[#c3122e] text-xs font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#c3122e]"></span>
                        CEO Access
                    </span>
                    <div class="w-8 h-8 rounded-full bg-[#c3122e] flex items-center justify-center text-white text-xs font-bold">
                        {{ strtoupper(substr(auth()->user()->name ?? 'C', 0, 1)) }}
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-6 bg-[#f8fafc]">
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot }}
                @endif
            </main>
        </div>
    </div>

</body>
</html>
