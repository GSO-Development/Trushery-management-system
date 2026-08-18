<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#f8fafc]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'GS Enterprise Portal') }} - Group Treasury</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <!-- Chart.js & Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-[#f8fafc]">

    @php
        $u = auth()->user();
        if ($u->is_admin) {
            $ceoCompanies = \App\Models\Company::where('slug', '!=', 'admin')->orderBy('name')->get();
        } elseif ($u->group && $u->group->isGroup() && ! empty($u->group->company_ids)) {
            $ceoCompanies = \App\Models\Company::whereIn('id', $u->group->company_ids)->orderBy('name')->get();
        } else {
            $ceoCompanies = $u->ceoCompanies ?? collect();
            if ($ceoCompanies->isEmpty()) {
                $ceoCompanies = \App\Models\Company::where('slug', '!=', 'admin')->orderBy('name')->get();
            }
        }
        $ceoAlerts = \App\Services\NotificationService::getAlerts(null, 30);
        $currentCompSlug = request()->route('company_slug');
    @endphp

    <div x-data="{ sidebarOpen: true }" class="flex h-screen overflow-hidden bg-[#f8fafc]">

        <!-- Sidebar -->
        <aside :style="sidebarOpen ? 'width: 260px; min-width: 260px; opacity: 1;' : 'width: 0px; min-width: 0px; opacity: 0; pointer-events: none; overflow: hidden;'"
               class="bg-[#0f172a] flex flex-col flex-shrink-0 transition-all duration-300 ease-in-out shadow-2xl relative z-40 h-screen">

            <!-- Brand header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-white/10 flex-shrink-0 h-16 w-64">
                <a href="{{ route('group.dashboard') }}" class="flex items-center gap-3 overflow-hidden">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#c3122e] to-[#e63956] flex items-center justify-center flex-shrink-0 shadow-lg shadow-[#c3122e]/40">
                        <span class="text-white font-extrabold text-sm">GS</span>
                    </div>
                    <div class="truncate">
                        <p class="text-white font-bold text-sm leading-tight tracking-wide">GEORGE STEUART</p>
                        <p class="text-[#c3122e] text-[10px] uppercase tracking-wider font-semibold">Group Treasury</p>
                    </div>
                </a>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-4 space-y-5 overflow-y-auto w-64">
                
                <!-- 1. Executive Group Portals -->
                <div>
                    <p class="px-3 text-[10px] text-slate-500 uppercase tracking-widest font-extrabold mb-2">
                        Executive Overview
                    </p>
                    <div class="space-y-1">
                        <!-- Group Dashboard Link -->
                        <a href="{{ route('group.dashboard') }}"
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200
                                  {{ request()->routeIs('group.dashboard') || request()->routeIs('ceo.dashboard')
                                      ? 'bg-[#c3122e] text-white shadow-lg shadow-[#c3122e]/30'
                                      : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span class="truncate">Group Overview Dashboard</span>
                        </a>

                        <!-- Comparison Tool Link -->
                        <a href="{{ route('group.comparison') }}"
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200
                                  {{ request()->routeIs('group.comparison') || request()->routeIs('ceo.comparison')
                                      ? 'bg-[#c3122e] text-white shadow-lg shadow-[#c3122e]/30'
                                      : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="truncate">Multi-Company Comparison</span>
                        </a>

                        <!-- Group Notifications Link (3rd Item) -->
                        @php
                            $ceoAlertCount = count(\App\Services\NotificationService::getAlerts(null, 30));
                        @endphp
                        <a href="{{ route('group.notifications') }}"
                           class="flex items-center justify-between gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200
                                  {{ request()->routeIs('group.notifications') || request()->routeIs('ceo.notifications')
                                      ? 'bg-[#c3122e] text-white shadow-lg shadow-[#c3122e]/30'
                                      : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                            <div class="flex items-center gap-3 min-w-0">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                <span class="truncate">Group Notifications</span>
                            </div>
                            @if($ceoAlertCount > 0)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black flex-shrink-0
                                             {{ request()->routeIs('group.notifications') ? 'bg-white text-[#c3122e]' : 'bg-[#c3122e] text-white' }}">
                                    {{ $ceoAlertCount }}
                                </span>
                            @endif
                        </a>
                    </div>
                </div>

                <!-- 2. Sub-Company Individual Dashboards (Group Scoped) -->
                @if($ceoCompanies->isNotEmpty())
                    <div class="pt-3 border-t border-white/10">
                        <div class="flex items-center justify-between px-3 mb-2">
                            <p class="text-[10px] text-slate-500 uppercase tracking-widest font-extrabold">
                                Sub-Company Dashboards
                            </p>
                            <span class="px-1.5 py-0.2 rounded bg-white/10 text-slate-400 text-[10px] font-bold">
                                {{ $ceoCompanies->count() }}
                            </span>
                        </div>
                        <div class="space-y-1">
                            @foreach($ceoCompanies as $comp)
                                @php
                                    $isCurrentActive = request()->routeIs('group.company.dashboard') && ($currentCompSlug === $comp->slug);
                                @endphp
                                <a href="{{ route('group.company.dashboard', $comp->slug) }}"
                                   title="Open {{ $comp->name }} Executive Dashboard"
                                   class="group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-all duration-200
                                          {{ $isCurrentActive
                                              ? 'bg-white/20 text-white ring-1 ring-[#c3122e] font-bold'
                                              : 'text-slate-400 hover:bg-white/10 hover:text-white' }}">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-6 h-6 rounded-lg {{ $isCurrentActive ? 'bg-[#c3122e] text-white' : 'bg-white/10 text-slate-300 group-hover:bg-[#c3122e] group-hover:text-white' }} flex items-center justify-center flex-shrink-0 transition-colors">
                                            <span class="text-[9px] font-bold">{{ strtoupper(substr($comp->name, 0, 2)) }}</span>
                                        </div>
                                        <span class="truncate">{{ $comp->name }}</span>
                                    </div>
                                    <svg class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity text-slate-400 group-hover:text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </nav>

            <!-- User profile + logout -->
            <div class="px-3 py-4 border-t border-white/10 flex-shrink-0 bg-[#0a101f] w-64">
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-white/5 border border-white/5">
                    <div class="w-8 h-8 rounded-full bg-[#c3122e] flex items-center justify-center text-white text-xs font-bold flex-shrink-0 shadow-md">
                        {{ strtoupper(substr(auth()->user()->name ?? 'G', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-white text-xs font-bold truncate">{{ auth()->user()->name }}</p>
                        <p class="text-slate-400 text-[10px] truncate font-medium">Group Executive</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 text-slate-400 hover:text-red-400 text-xs font-medium transition-colors rounded-lg hover:bg-white/5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Top Header Bar -->
            <header class="bg-white border-b border-slate-200 flex items-center justify-between h-16 px-4 sm:px-6 flex-shrink-0 shadow-sm z-10">
                <div class="flex items-center gap-3 sm:gap-4">
                    <!-- Hamburger Menu Button -->
                    <button type="button"
                            @click="sidebarOpen = !sidebarOpen"
                            class="text-slate-600 hover:text-[#c3122e] p-2 rounded-xl hover:bg-slate-100 transition-colors flex items-center gap-2 border border-slate-200 cursor-pointer"
                            title="Toggle Sidebar Navigation">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <span class="text-xs font-bold text-slate-700">Menu</span>
                    </button>

                    @hasSection('header')
                        <div class="text-[#0f172a] font-bold text-sm sm:text-base truncate">@yield('header')</div>
                    @elseif(isset($header))
                        <div class="text-[#0f172a] font-bold text-sm sm:text-base truncate">{{ $header }}</div>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <!-- Group Notification Bell (All Companies Expiry Tracker) -->
                    <x-notification-bell :alerts="$ceoAlerts" />

                    <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#fdf2f4] border border-[#f8d7da] text-[#c3122e] text-xs font-bold">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#c3122e]"></span>
                        Group Access
                    </span>
                    <div class="w-8 h-8 rounded-full bg-[#c3122e] flex items-center justify-center text-white text-xs font-bold shadow-sm shadow-[#c3122e]/30">
                        {{ strtoupper(substr(auth()->user()->name ?? 'G', 0, 1)) }}
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-[#f8fafc]">
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