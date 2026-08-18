{{--
    resources/views/ceo/notifications.blade.php
    Controller: App\Http\Controllers\Ceo\CeoNotificationController@index
    Group Executive Consolidated Notifications Dashboard
--}}
@extends('layouts.ceo')

@section('header', 'Group Notifications & Expiry Alerts')

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

    {{-- Header --}}
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
                    <span class="px-2.5 py-0.5 rounded-full bg-white/10 text-slate-300 text-[10px] font-bold uppercase tracking-wider">Group Treasury</span>
                    <span class="px-2.5 py-0.5 rounded-full bg-[#c3122e]/30 text-[#fca5a5] border border-[#c3122e]/40 text-[10px] font-bold">Consolidated Alerts</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-white tracking-tight">Group Notifications & Expiry Alerts</h1>
                <p class="text-xs text-slate-400 mt-0.5">Consolidated view of all facility expiries and alerts across {{ $accessibleCompanies->count() }} sub-companies.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 z-10">
            <form method="POST" action="{{ route('group.notifications.dispatch') }}" onsubmit="return confirm('Send automated notification emails to all authorized users across sub-companies?')">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-bold transition-all flex items-center gap-2 shadow-lg shadow-[#c3122e]/30 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>📧 Dispatch Group Emails</span>
                </button>
            </form>

            <a href="{{ route('group.dashboard') }}"
               class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all flex items-center gap-2 border border-white/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Group Overview
            </a>
        </div>
    </div>

    {{-- Summary KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Group Total Alerts</p>
            <p class="text-3xl font-black text-[#0f172a]">{{ $totalCount }}</p>
            <p class="text-[10px] text-slate-400 mt-1">Across {{ $accessibleCompanies->count() }} entities</p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-red-200 shadow-sm">
            <p class="text-[10px] font-bold text-red-400 uppercase tracking-wider mb-1">⚠️ Overdue Facilities</p>
            <p class="text-3xl font-black text-red-600">{{ $overdueCount }}</p>
            <p class="text-[10px] text-red-400 mt-1">LKR {{ number_format($overdueExposure, 0) }} exposure</p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-amber-200 shadow-sm">
            <p class="text-[10px] font-bold text-amber-500 uppercase tracking-wider mb-1">🔥 Critical (&lt;7 Days)</p>
            <p class="text-3xl font-black text-amber-600">{{ $criticalCount }}</p>
            <p class="text-[10px] text-amber-400 mt-1">Immediate action required</p>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-blue-200 shadow-sm">
            <p class="text-[10px] font-bold text-blue-400 uppercase tracking-wider mb-1">📅 Upcoming (&lt;90 Days)</p>
            <p class="text-3xl font-black text-blue-600">{{ $upcomingCount }}</p>
            <p class="text-[10px] text-blue-400 mt-1">Plan ahead</p>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-3">

        {{-- Company Filter --}}
        <div class="flex items-center gap-2 flex-wrap">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mr-1">Entity:</p>
            <a href="{{ route('group.notifications') . '?' . http_build_query(array_merge(request()->query(), ['company_id' => 'all'])) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border {{ $companyFilter === 'all' ? 'bg-[#0f172a] text-white border-[#0f172a]' : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-slate-400' }}">
                All Entities
                @if($totalCount > 0)<span class="ml-1 px-1.5 py-0.5 rounded-full {{ $companyFilter === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600' }} text-[9px] font-black">{{ $totalCount }}</span>@endif
            </a>
            @foreach($accessibleCompanies as $comp)
                @php $compCount = $companyAlertCounts[$comp->id] ?? 0; @endphp
                <a href="{{ route('group.notifications') . '?' . http_build_query(array_merge(request()->query(), ['company_id' => $comp->id])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border {{ (string)$companyFilter === (string)$comp->id ? 'bg-[#c3122e] text-white border-[#c3122e] shadow-sm shadow-[#c3122e]/30' : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-[#c3122e] hover:text-[#c3122e]' }}">
                    {{ strtoupper(substr($comp->name, 0, 2)) }} {{ $comp->name }}
                    @if($compCount > 0)<span class="ml-1 px-1.5 py-0.5 rounded-full {{ (string)$companyFilter === (string)$comp->id ? 'bg-white/20 text-white' : 'bg-[#c3122e] text-white' }} text-[9px] font-black">{{ $compCount }}</span>@endif
                </a>
            @endforeach
        </div>

        {{-- Category + Urgency + Search --}}
        <div class="flex flex-wrap items-center gap-2">
            @foreach(['all' => 'All Types', 'fd' => '💰 FD', 'wc' => '💳 WC', 'ltl' => '🏢 LTL'] as $key => $label)
                <a href="{{ route('group.notifications') . '?' . http_build_query(array_merge(request()->query(), ['category' => $key])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border {{ $categoryFilter === $key ? 'bg-[#c3122e] text-white border-[#c3122e]' : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-[#c3122e] hover:text-[#c3122e]' }}">
                    {{ $label }}
                </a>
            @endforeach

            <div class="w-px h-5 bg-slate-200"></div>

            @foreach(['all' => 'All', 'overdue' => '⚠️ Overdue', 'critical' => '🔥 <7d', 'upcoming' => '📅 >7d'] as $key => $label)
                <a href="{{ route('group.notifications') . '?' . http_build_query(array_merge(request()->query(), ['urgency' => $key])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all border {{ $urgencyFilter === $key ? 'bg-slate-800 text-white border-slate-800' : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-slate-400' }}">
                    {{ $label }}
                </a>
            @endforeach

            <div class="ml-auto flex items-center gap-2">
                <input type="text" name="search" value="{{ $search }}"
                       form="ceo-filter-search"
                       placeholder="Search reference, bank…"
                       class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none w-44 bg-slate-50 text-slate-800"/>
                <form id="ceo-filter-search" method="GET" action="{{ route('group.notifications') }}">
                    <input type="hidden" name="company_id" value="{{ $companyFilter }}">
                    <input type="hidden" name="category" value="{{ $categoryFilter }}">
                    <input type="hidden" name="urgency" value="{{ $urgencyFilter }}">
                </form>
                @if($categoryFilter !== 'all' || $urgencyFilter !== 'all' || $companyFilter !== 'all' || $search)
                    <a href="{{ route('group.notifications') }}"
                       class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-600 text-xs font-medium hover:bg-slate-200 transition-colors">Clear</a>
                @endif
            </div>
        </div>

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
                <h3 class="text-sm font-bold text-slate-700 mb-1">No Group Alerts Found</h3>
                <p class="text-xs text-slate-400">All facilities are within safe limits across all selected companies.</p>
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
                    $slug        = $alert['company_slug'] ?? 'health';
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 border-l-4 {{ $severityBg }} shadow-sm hover:shadow-md transition-shadow p-5 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                    <div class="flex items-start gap-4 min-w-0 flex-1">
                        <span class="text-2xl flex-shrink-0 mt-0.5">{{ $iconEmoji }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                <span class="px-2 py-0.5 rounded-full bg-slate-900 text-white text-[10px] font-bold">{{ $alert['company_name'] ?? 'Company' }}</span>
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
                                <span>Company: <span class="font-bold text-slate-700">{{ $alert['company_name'] }}</span></span>
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
                            <form method="POST" action="{{ route('group.notifications.dispatch') }}" onsubmit="return confirm('Send notification email for {{ addslashes($alert['title']) }}?')">
                                @csrf
                                <input type="hidden" name="alert_id" value="{{ $alert['id'] }}">
                                <input type="hidden" name="company_id" value="{{ $alert['company_id'] }}">
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

</div>
@endsection
