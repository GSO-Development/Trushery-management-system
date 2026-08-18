{{--
    resources/views/tenant/notifications.blade.php
    Controller: App\Http\Controllers\Tenant\NotificationController@index
    Sub-Company Facility Expiry & Notifications Management
--}}
@extends('layouts.portal')

@section('header', 'Notifications & Expiry Alerts')

@section('content')
<div class="space-y-5">

    {{-- Top Alert Messages --}}
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-4 h-4 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="bg-gradient-to-r from-[#0f172a] to-[#1e293b] rounded-2xl p-6 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-5 relative overflow-hidden">
        <div class="absolute right-0 top-0 translate-x-8 -translate-y-8 w-64 h-64 rounded-full bg-[#c3122e]/10 blur-3xl pointer-events-none"></div>
        <div class="flex items-center gap-4 z-10">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-[#c3122e] to-[#e63956] flex items-center justify-center flex-shrink-0 shadow-lg shadow-[#c3122e]/40">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="px-2.5 py-0.5 rounded-full bg-white/10 text-slate-300 text-[10px] font-bold uppercase tracking-wider">{{ $company->name }}</span>
                    <span class="px-2.5 py-0.5 rounded-full bg-[#c3122e]/30 text-[#fca5a5] border border-[#c3122e]/40 text-[10px] font-bold">Expiry & Alert Tracker</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-white tracking-tight">Facility Expiry & Notifications</h1>
                <p class="text-xs text-slate-400 mt-0.5">Real-time alerts for Fixed Deposit maturities, Working Capital settlements &amp; Loan rate reviews.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 z-10">
            <form method="POST" action="{{ route('tenant.notifications.dispatch', ['company_slug' => $company->slug]) }}" onsubmit="return confirm('Send automated notification emails to all authorized users for active alerts?')">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-bold transition-all flex items-center gap-2 shadow-lg shadow-[#c3122e]/30 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>📧 Dispatch Alert Emails</span>
                </button>
            </form>

            <a href="{{ route('tenant.summary-dashboard', ['company_slug' => $company->slug]) }}"
               class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all flex items-center gap-2 border border-white/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Dashboard
            </a>
        </div>
    </div>

    {{-- Summary KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Alerts</p>
            <p class="text-3xl font-black text-[#0f172a]">{{ $summary['total_count'] ?? 0 }}</p>
            <p class="text-[10px] text-slate-400 mt-1">90-day look-ahead</p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-red-200 shadow-sm">
            <p class="text-[10px] font-bold text-red-400 uppercase tracking-wider mb-1">⚠️ Overdue / Action Needed</p>
            <p class="text-3xl font-black text-red-600">{{ $summary['overdue_count'] ?? 0 }}</p>
            <p class="text-[10px] text-red-400 mt-1">LKR {{ number_format($summary['overdue_amount'] ?? 0, 0) }} exposure</p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-amber-200 shadow-sm">
            <p class="text-[10px] font-bold text-amber-500 uppercase tracking-wider mb-1">🔥 Critical (&lt;7 Days)</p>
            <p class="text-3xl font-black text-amber-600">{{ $summary['urgent_count'] ?? 0 }}</p>
            <p class="text-[10px] text-amber-400 mt-1">Immediate review required</p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-blue-200 shadow-sm">
            <p class="text-[10px] font-bold text-blue-400 uppercase tracking-wider mb-1">📅 Upcoming (&lt;90 Days)</p>
            <p class="text-3xl font-black text-blue-600">{{ $summary['soon_count'] ?? 0 }}</p>
            <p class="text-[10px] text-blue-400 mt-1">Plan ahead</p>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
        <form method="GET" action="{{ url('/' . $company->slug . '/notifications') }}" class="flex flex-wrap items-center gap-3">

            {{-- Category Filter --}}
            <div class="flex items-center gap-1.5 flex-wrap">
                @foreach(['all' => 'All', 'fd' => '💰 Fixed Deposits', 'wc' => '💳 Working Capital', 'ltl' => '🏢 Long Term Loans'] as $key => $label)
                    <a href="{{ url('/' . $company->slug . '/notifications') . '?' . http_build_query(array_merge(request()->query(), ['category' => $key, 'urgency' => $urgencyFilter])) }}"
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border
                              {{ $categoryFilter === $key
                                  ? 'bg-[#c3122e] text-white border-[#c3122e] shadow-sm shadow-[#c3122e]/30'
                                  : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-[#c3122e] hover:text-[#c3122e]' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="w-px h-6 bg-slate-200 hidden sm:block"></div>

            {{-- Urgency Filter --}}
            <div class="flex items-center gap-1.5 flex-wrap">
                @foreach(['all' => 'All Urgencies', 'overdue' => '⚠️ Overdue', 'critical' => '🔥 <7 Days', 'upcoming' => '📅 >7 Days'] as $key => $label)
                    <a href="{{ url('/' . $company->slug . '/notifications') . '?' . http_build_query(array_merge(request()->query(), ['urgency' => $key, 'category' => $categoryFilter])) }}"
                       class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all border
                              {{ $urgencyFilter === $key
                                  ? 'bg-slate-800 text-white border-slate-800'
                                  : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-slate-400 hover:text-slate-800' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="ml-auto flex items-center gap-2">
                <input type="hidden" name="category" value="{{ $categoryFilter }}">
                <input type="hidden" name="urgency" value="{{ $urgencyFilter }}">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Search bank, reference…"
                       class="px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none w-48 bg-slate-50 text-slate-800"/>
                <button type="submit" class="px-4 py-2 rounded-xl bg-[#c3122e] text-white text-xs font-bold hover:bg-[#9e0e24] transition-colors">Filter</button>
                @if($categoryFilter !== 'all' || $urgencyFilter !== 'all' || $search)
                    <a href="{{ url('/' . $company->slug . '/notifications') }}"
                       class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-medium hover:bg-slate-200 transition-colors">Clear</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Alert List --}}
    <div class="space-y-3">
        @php $alertsArr = is_array($filteredAlerts) ? $filteredAlerts : $filteredAlerts->values()->all(); @endphp

        @if(count($alertsArr) === 0)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-700 mb-1">No Alerts Found</h3>
                <p class="text-xs text-slate-400">
                    @if($search || $categoryFilter !== 'all' || $urgencyFilter !== 'all')
                        No alerts match your current filters. <a href="{{ url('/' . $company->slug . '/notifications') }}" class="text-[#c3122e] font-bold">Clear filters</a>
                    @else
                        All facilities are within safe thresholds. No expiries in the next 90 days.
                    @endif
                </p>
            </div>
        @else
            @foreach($alertsArr as $alert)
                @php
                    $severityBg  = match($alert['type']) { 'danger' => 'border-l-red-500 bg-red-50/30', 'warning' => 'border-l-amber-500 bg-amber-50/30', default => 'border-l-blue-500 bg-blue-50/20' };
                    $badgeBg     = match($alert['type']) { 'danger' => 'bg-red-100 text-red-700 border-red-200', 'warning' => 'bg-amber-100 text-amber-700 border-amber-200', default => 'bg-blue-50 text-blue-700 border-blue-200' };
                    $btnStyle    = match($alert['type']) { 'danger' => 'bg-red-600 hover:bg-red-700 text-white shadow-red-200', 'warning' => 'bg-amber-500 hover:bg-amber-600 text-white shadow-amber-200', default => 'bg-blue-600 hover:bg-blue-700 text-white shadow-blue-200' };
                    $daysLeft    = (int) ($alert['days_left'] ?? 0);
                    $iconEmoji   = match($alert['icon'] ?? '') { 'fd' => '💰', 'loan' => '💳', 'rate_revision' => '🏢', default => '🔔' };
                    $daysColor   = $daysLeft < 0 ? 'text-red-600' : ($daysLeft <= 7 ? 'text-amber-600' : 'text-blue-600');
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 border-l-4 {{ $severityBg }} shadow-sm hover:shadow-md transition-shadow p-5 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                    <div class="flex items-start gap-4 min-w-0 flex-1">
                        <span class="text-2xl flex-shrink-0 mt-0.5">{{ $iconEmoji }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                <span class="px-2 py-0.5 rounded-full border text-[10px] font-bold {{ $badgeBg }}">{{ $alert['status_label'] ?? $alert['type'] }}</span>
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold border border-slate-200">{{ $alert['category'] }}</span>
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-white text-[10px] font-mono font-bold">{{ $alert['bank_code'] ?? 'BNK' }}</span>

                                {{-- Email Sent Status Badge --}}
                                @if(!empty($alert['email_sent']))
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold border border-emerald-200 shadow-sm" title="Delivered to: {{ implode(', ', $alert['email_recipients'] ?? []) }}">
                                        <span>✉️ Email Dispatched</span>
                                        <span class="text-emerald-700 font-mono">({{ $alert['email_recipients_count'] }} recipient(s) on {{ $alert['email_sent_at'] }})</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[10px] font-medium border border-slate-200">
                                        <span>✉️ Email Not Sent / Pending</span>
                                    </span>
                                @endif
                            </div>
                            <h3 class="text-sm font-black text-[#0f172a] mb-1">{{ $alert['title'] }}</h3>
                            <p class="text-xs text-slate-600 leading-relaxed">{{ $alert['message'] }}</p>
                            <div class="flex flex-wrap items-center gap-3 mt-2 text-[11px] font-mono text-slate-500">
                                <span>Ref: <span class="font-bold text-slate-700">{{ $alert['reference'] ?? '-' }}</span></span>
                                <span>Amount: <span class="font-bold text-slate-700">{{ $alert['currency'] ?? 'LKR' }} {{ number_format($alert['amount'] ?? 0, 0) }}</span></span>
                                <span>Date: <span class="font-bold text-slate-700">{{ $alert['date'] ?? '-' }}</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-row sm:flex-col items-center sm:items-end justify-between sm:justify-start gap-2.5 flex-shrink-0">
                        <div class="text-right">
                            <p class="text-xl font-black {{ $daysColor }}">{{ abs($daysLeft) }}</p>
                            <p class="text-[10px] font-bold {{ $daysColor }} uppercase tracking-wide">{{ $daysLeft < 0 ? 'Days Overdue' : 'Days Left' }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('tenant.notifications.dispatch', ['company_slug' => $company->slug]) }}" onsubmit="return confirm('Send notification email for {{ addslashes($alert['title']) }}?')">
                                @csrf
                                <input type="hidden" name="alert_id" value="{{ $alert['id'] }}">
                                <button type="submit" class="px-2.5 py-2 rounded-xl text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition-colors flex items-center gap-1 cursor-pointer" title="Send Email Alert Now">
                                    <span>✉️ Send</span>
                                </button>
                            </form>
                            <a href="{{ $alert['url'] ?? '#' }}"
                               class="px-3 py-2 rounded-xl text-[11px] font-bold transition-all shadow-sm {{ $btnStyle }}">
                                View &amp; Manage →
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Breakdown Footer --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Breakdown by Facility Type</p>
        <div class="grid grid-cols-3 gap-4 text-center">
            <div class="px-3 py-2 rounded-xl bg-emerald-50 border border-emerald-200">
                <p class="text-xl font-black text-emerald-700">{{ $summary['fd_count'] ?? 0 }}</p>
                <p class="text-[10px] text-emerald-600 font-bold">Fixed Deposits</p>
            </div>
            <div class="px-3 py-2 rounded-xl bg-amber-50 border border-amber-200">
                <p class="text-xl font-black text-amber-700">{{ $summary['wc_count'] ?? 0 }}</p>
                <p class="text-[10px] text-amber-600 font-bold">Working Capital</p>
            </div>
            <div class="px-3 py-2 rounded-xl bg-blue-50 border border-blue-200">
                <p class="text-xl font-black text-blue-700">{{ $summary['ltl_count'] ?? 0 }}</p>
                <p class="text-[10px] text-blue-600 font-bold">Long Term Loans</p>
            </div>
        </div>
    </div>

</div>
@endsection
