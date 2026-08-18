@props(['alerts' => [], 'companySlug' => null])

@php
    $unreadCount = count($alerts);
    $targetSlug = $companySlug ?? (request()->route('company_slug') ?? (auth()->user()->company->slug ?? ''));
@endphp

<div class="relative" x-data="{ notifOpen: false }">
    <!-- Bell Trigger Button -->
    <button @click="notifOpen = !notifOpen"
            class="relative p-2 rounded-xl text-slate-500 hover:text-[#c3122e] hover:bg-slate-100 transition-all focus:outline-none"
            title="Notifications & Upcoming Reminders">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>

        @if($unreadCount > 0)
            <span class="absolute top-1 right-1 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-[#c3122e] text-white text-[9px] font-extrabold items-center justify-center">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
            </span>
        @endif
    </button>

    <!-- Dropdown Panel -->
    <div x-show="notifOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         @click.outside="notifOpen = false"
         class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden z-50">

        <!-- Header -->
        <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-[#c3122e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="font-bold text-[#0f172a] text-xs uppercase tracking-wider">Upcoming Expiry Reminders</h3>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $unreadCount > 0 ? 'bg-[#fdf2f4] text-[#c3122e] border border-[#f8d7da]' : 'bg-slate-100 text-slate-500' }}">
                {{ $unreadCount }} {{ Str::plural('Alert', $unreadCount) }}
            </span>
        </div>

        <!-- Alert Items List -->
        <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
            @forelse($alerts as $alert)
                <a href="{{ $alert['url'] }}" @click="notifOpen = false"
                   class="flex items-start gap-3 p-4 hover:bg-slate-50 transition-colors group">
                    <!-- Status Icon Badge -->
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5
                        {{ $alert['type'] === 'danger' ? 'bg-red-100 text-red-600' : ($alert['type'] === 'warning' ? 'bg-amber-100 text-amber-600' : 'bg-blue-100 text-blue-600') }}">
                        @if($alert['icon'] === 'fd')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @endif
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1 mb-0.5">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-[#c3122e]">
                                {{ $alert['category'] }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">
                                {{ $alert['date'] }}
                            </span>
                        </div>
                        <p class="text-xs font-bold text-[#0f172a] group-hover:text-[#c3122e] transition-colors leading-tight">
                            {{ $alert['title'] }}
                        </p>
                        <p class="text-[11px] text-slate-600 mt-1 leading-normal">
                            {{ $alert['message'] }}
                        </p>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center space-y-2">
                    <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <p class="text-xs font-bold text-[#0f172a]">All Clear!</p>
                    <p class="text-[11px] text-slate-400">No loan payments or fixed deposit maturities due within the next 30 days.</p>
                </div>
            @endforelse
        </div>

        <!-- Footer -->
        <div class="px-4 py-2.5 border-t border-slate-100 bg-slate-50/80 flex items-center justify-between text-xs">
            <span class="text-[10px] font-semibold text-slate-400">30-day expiry tracker</span>
            @if(!empty($targetSlug) && $targetSlug !== 'admin')
                <a href="{{ url("/{$targetSlug}/notifications") }}" @click="notifOpen = false"
                   class="text-[11px] font-bold text-[#c3122e] hover:underline flex items-center gap-1">
                    <span>Manage Expiries &rarr;</span>
                </a>
            @endif
        </div>
    </div>
</div>