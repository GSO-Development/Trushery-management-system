<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#f8fafc]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'GS Treasury Portal') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-[#f8fafc]">

    @php
        $currentCompanyId = auth()->user()->company_id ?? null;
        $companySlug      = request()->route('company_slug') ?? auth()->user()->company->slug ?? '';
        $companyObj       = auth()->user()->company;
        $portalAlerts     = \App\Services\NotificationService::getAlerts($currentCompanyId, 30);
        $alertCount       = count($portalAlerts);
    @endphp

    <div x-data="{ collapsed: false }" class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <aside :class="collapsed ? 'w-16' : 'w-64'"
               class="bg-[#0f172a] flex flex-col flex-shrink-0 transition-all duration-300 ease-in-out relative z-30 shadow-2xl">

            <!-- App Brand / Logo -->
            <div class="h-16 flex items-center px-4 border-b border-white/10" :class="collapsed ? 'justify-center px-0' : 'justify-between'">
                <a href="{{ route('home') }}" class="flex items-center gap-3 overflow-hidden">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#c3122e] to-[#e63956] flex items-center justify-center text-white font-extrabold text-lg flex-shrink-0 shadow-lg shadow-[#c3122e]/40">
                        GS
                    </div>
                    <div x-show="!collapsed" x-transition:enter="transition ease-out duration-150 delay-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="truncate whitespace-nowrap">
                        <p class="text-white font-bold text-sm tracking-wide leading-tight">GEORGE STEUART</p>
                        <p class="text-slate-400 text-[10px] uppercase tracking-wider font-semibold">Treasury Portal</p>
                    </div>
                </a>
            </div>

            <!-- Current Sub-Company Badge -->
            <div class="px-3 py-3 border-b border-white/10" x-show="!collapsed">
                <div class="p-2.5 rounded-xl bg-white/5 border border-white/10 flex items-center gap-2.5">
                    <div class="w-2 h-2 rounded-full bg-[#c3122e] animate-pulse"></div>
                    <div class="overflow-hidden min-w-0">
                        <p class="text-[10px] uppercase font-extrabold tracking-wider text-[#c3122e]">Entity</p>
                        <p class="text-white font-bold text-xs truncate">{{ $companyObj->name ?? 'Sub Company' }}</p>
                    </div>
                </div>
            </div>

            <!-- Dynamic Navigation Menu -->
            <nav class="flex-1 overflow-y-auto px-2 py-4 space-y-1">
                @php
                    $u = auth()->user();
                    if ($u->is_admin || $u->isCeoOrGroupUser() || ! $u->group) {
                        $groupNavKeys = ['summary_dashboard', 'cash_position', 'long_term_loans', 'working_capital', 'fixed_deposits', 'audit_logs'];
                    } else {
                        $groupNavKeys = $u->group->getNavKeys() ?? ['summary_dashboard', 'cash_position', 'long_term_loans', 'working_capital', 'fixed_deposits', 'audit_logs'];
                    }

                    // Summary Dashboard is placed FIRST at the top of the side nav bar
                    $navMeta = [
                        'summary_dashboard'  => ['Summary Dashboard',  'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                        'cash_position'      => ['Daily Cash Position', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                        'long_term_loans'    => ['Long Term Loans',    'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        'working_capital'    => ['Working Capital',    'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        'fixed_deposits'     => ['Fixed Deposits',     'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                        'audit_logs'         => ['Audit Logs',         'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                    ];

                    $order = array_keys($navMeta);
                    usort($groupNavKeys, function($a, $b) use ($order) {
                        $posA = array_search($a, $order);
                        $posB = array_search($b, $order);
                        $posA = ($posA === false) ? 999 : $posA;
                        $posB = ($posB === false) ? 999 : $posB;
                        return $posA <=> $posB;
                    });
                @endphp

                {{-- Operational Core Module Links --}}
                @foreach($groupNavKeys as $navKey)
                    @php
                        $pageSlug = str_replace('_', '-', $navKey);
                        $href     = url("/{$companySlug}/{$pageSlug}");
                        $meta     = $navMeta[$navKey] ?? [Str::of($navKey)->replace('_',' ')->title()->toString(), 'M4 6h16M4 12h16M4 18h16'];
                        $label    = $meta[0];
                        $svgPath  = $meta[1];
                        $isActive = request()->is("{$companySlug}/{$pageSlug}*");
                    @endphp

                    <a href="{{ $href }}"
                       title="{{ $label }}"
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150
                              {{ $isActive
                                  ? 'bg-[#c3122e] text-white shadow-lg shadow-[#c3122e]/30'
                                  : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0 {{ $isActive ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $svgPath }}"/>
                        </svg>
                        <span x-show="!collapsed" class="truncate">{{ $label }}</span>
                    </a>
                @endforeach

                {{-- Notifications & Alerts Management Link (Universal for every Sub-Company) --}}
                @php
                    $isNotifActive = request()->is("{$companySlug}/notifications*");
                @endphp
                <div class="pt-3 mt-3 border-t border-white/10">
                    <p x-show="!collapsed" class="px-3 text-[10px] text-slate-500 uppercase tracking-widest font-extrabold mb-1">
                        System &amp; Alerts
                    </p>
                    <a href="{{ url("/{$companySlug}/notifications") }}"
                       title="Notifications & Facility Expiries"
                       class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150
                              {{ $isNotifActive
                                  ? 'bg-[#c3122e] text-white shadow-lg shadow-[#c3122e]/30'
                                  : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-4 h-4 flex-shrink-0 {{ $isNotifActive ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span x-show="!collapsed" class="truncate">Notifications &amp; Alerts</span>
                        </div>
                        @if($alertCount > 0)
                            <span x-show="!collapsed"
                                  class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $isNotifActive ? 'bg-white text-[#c3122e]' : 'bg-[#c3122e] text-white' }}">
                                {{ $alertCount }}
                            </span>
                        @endif
                    </a>
                </div>
            </nav>

            <!-- Bottom profile & logout -->
            <div class="p-3 border-t border-white/10 flex-shrink-0">
                <div class="flex items-center gap-3 px-2 py-2 rounded-xl bg-white/5">
                    <div class="w-8 h-8 rounded-full bg-[#c3122e] flex items-center justify-center text-white text-xs font-bold flex-shrink-0 shadow-md">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div x-show="!collapsed" class="flex-1 min-w-0">
                        <p class="text-white text-xs font-semibold truncate">{{ auth()->user()->name }}</p>
                        <p class="text-slate-400 text-[10px] truncate capitalize">{{ auth()->user()->group->name ?? 'User' }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 text-slate-400 hover:text-red-400 text-xs font-medium transition-colors rounded-lg hover:bg-white/5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span x-show="!collapsed">Sign Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Top bar -->
            <header class="bg-white border-b border-slate-100 flex items-center justify-between h-14 px-5 flex-shrink-0 shadow-sm">
                <div class="flex items-center gap-3">
                    <!-- Hamburger toggle - collapses sidebar to icon-only -->
                    <button @click="collapsed = !collapsed"
                            class="text-slate-500 hover:text-[#c3122e] p-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                            title="Toggle Sidebar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    @hasSection('header')
                        <div class="text-[#0f172a] font-semibold text-base">@yield('header')</div>
                    @elseif(isset($header))
                        <div class="text-[#0f172a] font-semibold text-base">{{ $header }}</div>
                    @endif
                </div>

                <!-- Top Bar User Profile, Notification Bell & Group Badge -->
                <div class="flex items-center gap-3" x-data="{ profileOpen: false }">
                    
                    @if(auth()->user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                            <span>Admin Panel</span>
                        </a>
                    @elseif(auth()->user()->isCeoOrGroupUser())
                        <a href="{{ route('group.dashboard') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-bold transition-colors shadow-sm shadow-[#c3122e]/20">
                            <span>Group Overview</span>
                        </a>
                    @endif

                    <!-- Notification Bell (30-day Expiry Tracker) -->
                    <x-notification-bell :alerts="$portalAlerts" :companySlug="$companySlug" />

                    <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#fdf2f4] border border-[#f8d7da] text-[#c3122e] text-xs font-semibold capitalize">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#c3122e]"></span>
                        {{ auth()->user()->group->name ?? 'User' }}
                    </span>

                    <!-- Profile Avatar Dropdown Trigger -->
                    <div class="relative">
                        <button @click="profileOpen = !profileOpen" class="flex items-center gap-1.5 p-1 rounded-full hover:bg-slate-100 transition-all focus:outline-none">
                            <div class="w-8 h-8 rounded-full bg-[#c3122e] flex items-center justify-center text-white text-xs font-bold shadow-sm shadow-[#c3122e]/30">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </div>
                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="profileOpen ? 'rotate-180 text-[#c3122e]' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Profile Dropdown Menu -->
                        <div x-show="profileOpen"
                             x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             @click.outside="profileOpen = false"
                             class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50">
                            
                            <!-- Dropdown Header Info -->
                            <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/50">
                                <p class="text-xs font-bold text-[#0f172a] truncate">{{ auth()->user()->name }}</p>
                                <p class="text-[11px] text-slate-400 truncate mb-2">{{ auth()->user()->email }}</p>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-200 text-slate-700 text-[10px] font-semibold truncate max-w-[120px]">
                                        {{ $companyObj->name ?? 'Sub Company' }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md bg-[#fdf2f4] text-[#c3122e] text-[10px] font-semibold truncate max-w-[100px]">
                                        {{ auth()->user()->group->name ?? 'User Group' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Menu Links -->
                            <a href="{{ route('tenant.profile', ['company_slug' => $companySlug]) }}"
                               @click="profileOpen = false"
                               class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-[#c3122e] transition-colors">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                My Profile & Password
                            </a>

                            <a href="{{ url("/{$companySlug}/notifications") }}"
                               @click="profileOpen = false"
                               class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-[#c3122e] transition-colors">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                Expiry &amp; Notifications
                            </a>

                            <div class="border-t border-slate-100 my-1"></div>

                            <!-- Sign Out -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
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