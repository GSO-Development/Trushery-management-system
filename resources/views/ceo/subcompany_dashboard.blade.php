{{--
    resources/views/ceo/subcompany_dashboard.blade.php
    Controller: App\Http\Controllers\Ceo\CeoDashboardController@subcompanyDashboard
    Executive Read-Only Sub-Company Treasury Dashboard for Group / CEO Portal
--}}
@extends('layouts.ceo')

@section('header', $company->name . ' - Treasury Overview')

@section('content')
<div class="space-y-6">

    {{-- Sub-Company Header Card --}}
    <div class="bg-gradient-to-r from-[#0f172a] to-[#1e293b] rounded-2xl p-6 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-5 relative overflow-hidden">
        <div class="absolute right-0 top-0 translate-x-8 -translate-y-8 w-64 h-64 rounded-full bg-[#c3122e]/10 blur-3xl pointer-events-none"></div>

        <div class="flex items-center gap-4 min-w-0 z-10">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-[#c3122e] to-[#e63956] flex items-center justify-center text-white font-black text-xl shadow-lg shadow-[#c3122e]/40 flex-shrink-0">
                {{ strtoupper(substr($company->name, 0, 2)) }}
            </div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="px-2.5 py-0.5 rounded-full bg-white/10 text-slate-300 text-[10px] font-bold uppercase tracking-wider">
                        Entity Portal
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full bg-[#c3122e]/30 text-[#fca5a5] border border-[#c3122e]/40 text-[10px] font-bold">
                        Executive View-Only
                    </span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-white tracking-tight truncate">{{ $company->name }}</h1>
                <p class="text-xs text-slate-400 mt-0.5 font-mono">/{{ $company->slug }} • {{ $bankAccounts->count() }} Registered Accounts</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 z-10">
            <a href="{{ route('group.dashboard') }}"
               class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all flex items-center gap-2 border border-white/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Group Overview</span>
            </a>
            <a href="{{ route('group.comparison') }}"
               class="px-4 py-2 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-bold shadow-md shadow-[#c3122e]/30 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Comparison Tool</span>
            </a>
        </div>
    </div>

    {{-- Company Quick-Switch Strip (visible when user has multiple companies) --}}
    @if($accessibleCompanies->count() > 1)
    <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar">
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex-shrink-0 pr-1">Switch:</span>
        @foreach($accessibleCompanies as $acc)
            @php $isActive = $acc->slug === $company->slug; @endphp
            <a href="{{ route('group.company.dashboard', $acc->slug) }}"
               class="flex-shrink-0 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold transition-all duration-150
                      {{ $isActive
                          ? 'bg-[#c3122e] text-white shadow-md shadow-[#c3122e]/20 ring-2 ring-[#c3122e]/20'
                          : 'bg-white border border-slate-200 text-slate-700 hover:border-[#c3122e]/40 hover:text-[#c3122e] hover:bg-red-50/60' }}">
                <span class="w-5 h-5 rounded-md {{ $isActive ? 'bg-white/20' : 'bg-slate-100' }} flex items-center justify-center font-black text-[10px]">
                    {{ strtoupper(substr($acc->name, 0, 2)) }}
                </span>
                <span class="truncate max-w-[120px]">{{ $acc->name }}</span>
                @if($isActive)
                    <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                @endif
            </a>
        @endforeach
    </div>
    @endif

    {{-- Top 4 Core Treasury KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- 1. Daily Cash Position --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Available Net Cash</span>
                <span class="p-2 rounded-xl bg-blue-50 text-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <p class="text-2xl font-black text-[#0f172a]">LKR {{ number_format($availableCash, 0) }}</p>
            <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 text-[10px] text-slate-500 font-mono">
                <span>Closing: {{ number_format($totalClosingCash, 0) }}</span>
                <span>Restricted: {{ number_format($totalRestrictedCash, 0) }}</span>
            </div>
        </div>

        {{-- 2. Long Term Loans --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Long Term Loans</span>
                <span class="p-2 rounded-xl bg-red-50 text-[#c3122e]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
            </div>
            <p class="text-2xl font-black text-[#c3122e]">LKR {{ number_format($ltlOutstanding, 0) }}</p>
            <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 text-[10px] text-slate-500 font-mono">
                <span>{{ $ltlCount }} Active Loans</span>
                <span>Avg {{ number_format($avgLtlRate, 2) }}%</span>
            </div>
        </div>

        {{-- 3. Working Capital Loans --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Working Capital</span>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </span>
            </div>
            <p class="text-2xl font-black text-amber-600">LKR {{ number_format($wcOutstanding, 0) }}</p>
            <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 text-[10px] text-slate-500 font-mono">
                <span>{{ $wcCount }} Facilities</span>
                <span>Avg {{ number_format($avgWcRate, 2) }}%</span>
            </div>
        </div>

        {{-- 4. Fixed Deposits --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Fixed Deposits</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <p class="text-2xl font-black text-emerald-600">LKR {{ number_format($fixedDepositsTotal, 0) }}</p>
            <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 text-[10px] text-slate-500 font-mono">
                <span>{{ $fdCount }} Deposits</span>
                <span>Yield: LKR {{ number_format($fdMonthlyProfit, 0) }}/mo</span>
            </div>
        </div>
    </div>

    {{-- Executive Financial Capital Breakdown Strip --}}
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Capital Structure &amp; Debt Summary</h2>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 text-center divide-x divide-slate-100">
            <div class="px-2">
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Total Outstanding Debt</span>
                <span class="text-base font-black text-[#0f172a] block mt-1">LKR {{ number_format($totalDebtOutstanding, 0) }}</span>
            </div>
            <div class="px-2">
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Approved Credit Limit</span>
                <span class="text-base font-black text-slate-700 block mt-1">LKR {{ number_format($totalCreditFacilities, 0) }}</span>
            </div>
            <div class="px-2">
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Total Liquid Assets</span>
                <span class="text-base font-black text-emerald-700 block mt-1">LKR {{ number_format($totalLiquidAssets, 0) }}</span>
            </div>
            <div class="px-2">
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Net Debt Exposure</span>
                <span class="text-base font-black text-red-600 block mt-1">LKR {{ number_format($netDebtPosition, 0) }}</span>
            </div>
            <div class="px-2">
                <span class="text-[10px] text-slate-400 uppercase font-semibold block">Avg Borrowing Rate</span>
                <span class="text-base font-black text-[#c3122e] block mt-1">{{ number_format($avgLoanRate, 2) }}%</span>
            </div>
        </div>
    </div>

    {{-- Tabbed Section for Detailed Instruments --}}
    <div x-data="{ activeTab: 'ltl' }" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        
        {{-- Tabs Navigation --}}
        <div class="flex items-center justify-between border-b border-slate-200 px-6 pt-4 bg-[#f8fafc]">
            <div class="flex items-center gap-2 overflow-x-auto">
                <button type="button" @click="activeTab = 'ltl'"
                    :class="activeTab === 'ltl' ? 'border-[#c3122e] text-[#c3122e] bg-white shadow-sm' : 'border-transparent text-slate-600 hover:text-slate-900'"
                    class="py-3 px-4 rounded-t-xl border-b-2 font-bold text-xs transition-all flex items-center gap-2 cursor-pointer">
                    <span>🏢 Long Term Loans</span>
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10px] font-bold">{{ $ltlLoans->count() }}</span>
                </button>

                <button type="button" @click="activeTab = 'wc'"
                    :class="activeTab === 'wc' ? 'border-amber-500 text-amber-600 bg-white shadow-sm' : 'border-transparent text-slate-600 hover:text-slate-900'"
                    class="py-3 px-4 rounded-t-xl border-b-2 font-bold text-xs transition-all flex items-center gap-2 cursor-pointer">
                    <span>💳 Working Capital Loans</span>
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10px] font-bold">{{ $wcLoans->count() }}</span>
                </button>

                <button type="button" @click="activeTab = 'fd'"
                    :class="activeTab === 'fd' ? 'border-emerald-600 text-emerald-600 bg-white shadow-sm' : 'border-transparent text-slate-600 hover:text-slate-900'"
                    class="py-3 px-4 rounded-t-xl border-b-2 font-bold text-xs transition-all flex items-center gap-2 cursor-pointer">
                    <span>💰 Fixed Deposits</span>
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10px] font-bold">{{ $fdList->count() }}</span>
                </button>

                <button type="button" @click="activeTab = 'accounts'"
                    :class="activeTab === 'accounts' ? 'border-blue-600 text-blue-600 bg-white shadow-sm' : 'border-transparent text-slate-600 hover:text-slate-900'"
                    class="py-3 px-4 rounded-t-xl border-b-2 font-bold text-xs transition-all flex items-center gap-2 cursor-pointer">
                    <span>🏦 Bank Accounts &amp; Balances</span>
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10px] font-bold">{{ $bankAccounts->count() }}</span>
                </button>
            </div>
        </div>

        {{-- Tab 1: Long Term Loans Table --}}
        <div x-show="activeTab === 'ltl'" class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                            <th class="px-4 py-3">Bank / Institution</th>
                            <th class="px-4 py-3">Facility Purpose / Ref</th>
                            <th class="px-4 py-3 text-right">Facility Limit</th>
                            <th class="px-4 py-3 text-right">Outstanding Debt</th>
                            <th class="px-4 py-3 text-center">Interest Rate</th>
                            <th class="px-4 py-3 text-center">Entry Date</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono">
                        @forelse($ltlLoans as $loan)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 font-sans font-bold text-[#0f172a]">
                                    <div class="flex items-center gap-2">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-bold font-mono">{{ $loan->bank->bank_code ?? 'BNK' }}</span>
                                        <span>{{ $loan->bank->name ?? 'Bank' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-sans text-slate-700">
                                    <p class="font-bold">{{ $loan->purpose ?? 'General Facility' }}</p>
                                    <p class="text-[10px] text-slate-400 font-mono">{{ $loan->account_number ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-slate-600">
                                    LKR {{ number_format($loan->facility_amount, 0) }}
                                </td>
                                <td class="px-4 py-3 text-right font-black text-[#c3122e]">
                                    LKR {{ number_format($loan->outstanding_amount, 0) }}
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-slate-800">
                                    {{ number_format($loan->interest_rate, 2) }}%
                                </td>
                                <td class="px-4 py-3 text-center text-slate-700">
                                    {{ $loan->entry_date ? \Carbon\Carbon::parse($loan->entry_date)->format('Y-m-d') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">
                                        Active
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-400 font-sans text-xs">
                                    No active Long Term Loans recorded for this sub-company.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tab 2: Working Capital Table --}}
        <div x-show="activeTab === 'wc'" class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                            <th class="px-4 py-3">Bank / Lender</th>
                            <th class="px-4 py-3">Facility Type / Ref</th>
                            <th class="px-4 py-3 text-right">Sanctioned Limit</th>
                            <th class="px-4 py-3 text-right">Utilized / Outstanding</th>
                            <th class="px-4 py-3 text-center">Interest Rate</th>
                            <th class="px-4 py-3 text-center">Settlement Date</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono">
                        @forelse($wcLoans as $wc)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 font-sans font-bold text-[#0f172a]">
                                    <div class="flex items-center gap-2">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-bold font-mono">{{ $wc->bank->bank_code ?? 'BNK' }}</span>
                                        <span>{{ $wc->bank->name ?? 'Bank' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-sans text-slate-700">
                                    <p class="font-bold">{{ $wc->loan_type ?? 'Overdraft / Working Capital' }}</p>
                                    <p class="text-[10px] text-slate-400 font-mono">{{ $wc->loan_reference ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-slate-600">
                                    LKR {{ number_format($wc->facility_amount, 0) }}
                                </td>
                                <td class="px-4 py-3 text-right font-black text-amber-600">
                                    LKR {{ number_format($wc->outstanding_amount, 0) }}
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-slate-800">
                                    {{ number_format($wc->interest_rate, 2) }}%
                                </td>
                                <td class="px-4 py-3 text-center text-slate-700">
                                    {{ $wc->settlement_date ? \Carbon\Carbon::parse($wc->settlement_date)->format('Y-m-d') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold">
                                        Active
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-400 font-sans text-xs">
                                    No active Working Capital Loans recorded for this sub-company.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tab 3: Fixed Deposits Table --}}
        <div x-show="activeTab === 'fd'" class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                            <th class="px-4 py-3">Bank / Institution</th>
                            <th class="px-4 py-3">Commencement Date</th>
                            <th class="px-4 py-3 text-right">Principal Amount</th>
                            <th class="px-4 py-3 text-center">Interest Rate</th>
                            <th class="px-4 py-3 text-right">Monthly Profit</th>
                            <th class="px-4 py-3 text-center">Maturity Date</th>
                            <th class="px-4 py-3 text-center">Security Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono">
                        @forelse($fdList as $fd)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 font-sans font-bold text-[#0f172a]">
                                    <div class="flex items-center gap-2">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-bold font-mono">{{ $fd->bank->bank_code ?? 'BNK' }}</span>
                                        <span>{{ $fd->bank->name ?? 'Bank' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $fd->commencement_date ? \Carbon\Carbon::parse($fd->commencement_date)->format('Y-m-d') : ($fd->entry_date ? \Carbon\Carbon::parse($fd->entry_date)->format('Y-m-d') : '-') }}
                                </td>
                                <td class="px-4 py-3 text-right font-black text-emerald-700">
                                    LKR {{ number_format($fd->amount, 0) }}
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-slate-800">
                                    {{ number_format($fd->interest_rate, 2) }}%
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-slate-700">
                                    LKR {{ number_format(($fd->amount * ($fd->interest_rate / 100)) / 12, 0) }}
                                </td>
                                <td class="px-4 py-3 text-center text-slate-700">
                                    {{ $fd->maturity_date ? \Carbon\Carbon::parse($fd->maturity_date)->format('Y-m-d') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-center font-sans">
                                    @if(!empty($fd->pledged_details))
                                        <span class="px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200 text-[10px] font-bold" title="{{ $fd->pledged_details }}">
                                            🔒 Pledged
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold">
                                            Unencumbered
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-400 font-sans text-xs">
                                    No active Fixed Deposits recorded for this sub-company.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tab 4: Bank Accounts Table --}}
        <div x-show="activeTab === 'accounts'" class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($bankAccounts as $acc)
                    @php
                        $entry = $latestCashEntries->get($acc->id);
                        $closing = (float)($entry->closing_balance ?? 0);
                        $restricted = (float)($entry->restricted_cash ?? 0);
                        $avail = max(0, $closing - $restricted);
                    @endphp
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white hover:border-[#c3122e]/40 transition-all space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded bg-white text-slate-800 border border-slate-200 font-mono text-[10px] font-bold">
                                {{ $acc->bank->bank_code ?? 'BNK' }}
                            </span>
                            <span class="text-[10px] font-bold text-slate-400 uppercase font-mono">{{ $acc->currency ?? 'LKR' }}</span>
                        </div>
                        <p class="font-bold text-xs text-[#0f172a]">{{ $acc->bank->name ?? 'Bank Account' }}</p>
                        <p class="font-mono text-[11px] text-slate-500">{{ $acc->account_number }} ({{ $acc->account_name ?? 'Current' }})</p>
                        
                        <div class="pt-2 border-t border-slate-200/80 flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-medium">Available:</span>
                            <span class="font-black text-blue-700 font-mono">LKR {{ number_format($avail, 0) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-8 text-slate-400 text-xs">
                        No bank accounts registered for this sub-company.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection