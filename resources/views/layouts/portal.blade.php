<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Portal') }} — {{ auth()->user()->group->name ?? 'Portal' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        /* Smooth sidebar width transition */
        aside { transition: width 0.2s ease; }
        /* Tooltip for collapsed nav items */
        .nav-tooltip { display: none; }
        [data-collapsed="true"] .nav-tooltip { display: block; }
    </style>
</head>
<body class="antialiased bg-[#f8fafc]"
      x-data="{ collapsed: false }"
      x-init="collapsed = window.innerWidth < 1024">

    <div class="flex h-screen overflow-hidden">

        <!-- ── Sidebar ─────────────────────────────────── -->
        <aside
            :class="collapsed ? 'w-16' : 'w-64'"
            :data-collapsed="collapsed"
            class="flex-shrink-0 bg-[#0f172a] flex flex-col shadow-2xl z-40 overflow-hidden"
            style="transition: width 0.22s cubic-bezier(.4,0,.2,1)">

            <!-- Brand header -->
            <div class="flex items-center gap-3 px-3 py-4 border-b border-white/10 min-w-0" :class="collapsed ? 'justify-center' : ''">
                <div class="w-9 h-9 rounded-lg bg-[#c3122e] flex items-center justify-center flex-shrink-0 shadow-lg shadow-[#c3122e]/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div class="min-w-0 overflow-hidden" x-show="!collapsed" x-transition:enter="transition ease-out duration-150 delay-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <p class="text-white font-semibold text-sm leading-tight truncate whitespace-nowrap">{{ config('app.name') }}</p>
                    <p class="text-[#c3122e] text-xs font-medium capitalize truncate whitespace-nowrap">
                        {{ auth()->user()->company->name ?? 'Portal' }}
                    </p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto overflow-x-hidden">
                <p x-show="!collapsed" class="px-3 text-[10px] text-slate-500 uppercase tracking-widest font-semibold mb-2 whitespace-nowrap">
                    {{ auth()->user()->group->name ?? 'My Portal' }}
                </p>

                @php
                    use Illuminate\Support\Str;
                    $companySlug  = auth()->user()->company->slug ?? '';
                    $groupNavKeys = auth()->user()->group ? auth()->user()->group->getNavKeys() : [];

                    $navMeta = [
                        'summary_dashboard'  => ['Summary Dashboard',  'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                        'cash_position'      => ['Daily Cash Position', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        'long_term_loans'    => ['Long Term Loans',    'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        'working_capital'    => ['Working Capital Loan','M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
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

                @foreach($groupNavKeys as $navKey)
                    @php
                        $pageSlug = str_replace('_', '-', $navKey);
                        $href     = url("/{$companySlug}/{$pageSlug}");
                        $meta     = $navMeta[$navKey] ?? [Str::of($navKey)->replace('_',' ')->title()->toString(), 'M4 6h16M4 12h16M4 18h16'];
                        $label    = $meta[0];
                        $iconPath = $meta[1];
                        $isActive = request()->is("{$companySlug}/{$pageSlug}*");
                    @endphp
                    <a href="{{ $href }}"
                       title="{{ $label }}"
                       :class="collapsed ? 'justify-center px-0' : 'px-3'"
                       class="relative group flex items-center gap-3 py-2.5 rounded-lg text-slate-300 text-sm font-medium transition-all duration-200 {{ $isActive ? 'bg-[#c3122e] text-white shadow-lg shadow-[#c3122e]/30' : 'hover:bg-white/10 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
                        </svg>
                        <span x-show="!collapsed"
                              class="truncate whitespace-nowrap overflow-hidden"
                              x-transition:enter="transition ease-out duration-150 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition ease-in duration-100"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0">{{ $label }}</span>
                        {{-- Tooltip when collapsed --}}
                        <span x-show="collapsed"
                              class="absolute left-full ml-3 px-2 py-1 rounded-md bg-slate-800 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none z-50 shadow-xl border border-white/10"
                              style="transition: opacity 0.15s ease">{{ $label }}</span>
                    </a>
                @endforeach
            </nav>

            <!-- User profile + logout -->
            <div class="px-2 py-3 border-t border-white/10">
                <div class="flex items-center gap-3 px-2 py-2.5 rounded-xl bg-white/5" :class="collapsed ? 'justify-center' : ''">
                    <div class="w-8 h-8 rounded-full bg-[#c3122e] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0 overflow-hidden" x-show="!collapsed" x-transition:enter="transition ease-out duration-150 delay-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <p class="text-white text-xs font-medium truncate whitespace-nowrap">{{ auth()->user()->name }}</p>
                        <p class="text-slate-500 text-[10px] truncate capitalize whitespace-nowrap">{{ auth()->user()->group->name ?? 'User' }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-1.5">
                    @csrf
                    <button type="submit"
                        title="Sign Out"
                        :class="collapsed ? 'justify-center px-0' : 'px-3'"
                        class="w-full flex items-center gap-2 py-2 text-slate-400 hover:text-red-400 text-xs font-medium transition-colors rounded-lg hover:bg-white/5">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span x-show="!collapsed" x-transition:enter="transition ease-out duration-150 delay-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="whitespace-nowrap">Sign Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- ── Main content ──────────────────────────── -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Top bar -->
            <header class="bg-white border-b border-slate-100 flex items-center justify-between h-14 px-5 flex-shrink-0 shadow-sm">
                <div class="flex items-center gap-3">
                    <!-- Hamburger toggle — collapses sidebar to icon-only -->
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
                <div class="flex items-center gap-3">
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#fdf2f4] border border-[#f8d7da] text-[#c3122e] text-xs font-semibold capitalize">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#c3122e]"></span>
                        {{ auth()->user()->group->name ?? 'User' }}
                    </span>
                    <div class="w-8 h-8 rounded-full bg-[#c3122e] flex items-center justify-center text-white text-xs font-bold">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
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
