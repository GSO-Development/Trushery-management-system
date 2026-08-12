{{--
    resources/views/ceo/dashboard.blade.php
    Controller: App\Http\Controllers\Ceo\CeoDashboardController
    Executive Overview Dashboard for Group Treasury & CEO
--}}
@extends('layouts.ceo')

@section('header', 'Group CEO Treasury Dashboard')

@section('content')
<div class="space-y-8">

    {{-- Header Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#0f172a] tracking-tight">Group Treasury &amp; Executive Dashboard</h1>
            <p class="text-sm text-slate-500 mt-1">Consolidated capital overview across all {{ $ceoCompanies->count() }} group companies</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('ceo.comparison') }}"
               class="px-4 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-bold shadow-md shadow-[#c3122e]/20 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                📊 Multi-Company Comparison Tool
            </a>
        </div>
    </div>

    {{-- Top 4 Group Consolidated Core KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        {{-- 1. Daily Cash Position --}}
        <div class="bg-gradient-to-br from-blue-900 to-indigo-900 rounded-2xl text-white shadow-lg shadow-blue-900/20 p-6 relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-blue-200 uppercase tracking-wider">Group Daily Cash Position</span>
                <span class="p-2 rounded-xl bg-white/10 text-blue-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <p class="text-3xl font-black text-white">LKR {{ number_format($groupAvailableCash, 0) }}</p>
            <p class="text-xs text-blue-200 mt-2">Available net liquid cash across all bank accounts</p>
        </div>

        {{-- 2. Long Term Loans --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 relative overflow-hidden group hover:border-[#c3122e]/40 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Long Term Loans</span>
                <span class="p-2 rounded-xl bg-red-50 text-[#c3122e]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
            </div>
            <p class="text-3xl font-black text-[#c3122e]">LKR {{ number_format($groupLtlOutstanding, 0) }}</p>
            <p class="text-xs text-slate-500 mt-2">Approved Limit: <strong class="text-slate-800">LKR {{ number_format($groupLtlFacility, 0) }}</strong> ({{ $groupLtlCount }} Loans)</p>
        </div>

        {{-- 3. Working Capital Loans --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 relative overflow-hidden group hover:border-amber-500/40 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Working Capital Loans</span>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <p class="text-3xl font-black text-amber-600">LKR {{ number_format($groupWcOutstanding, 0) }}</p>
            <p class="text-xs text-slate-500 mt-2">Approved Limit: <strong class="text-slate-800">LKR {{ number_format($groupWcFacility, 0) }}</strong> ({{ $groupWcCount }} Facilities)</p>
        </div>

        {{-- 4. Fixed Deposits --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 relative overflow-hidden group hover:border-emerald-500/40 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Fixed Deposits</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </span>
            </div>
            <p class="text-3xl font-black text-emerald-600">LKR {{ number_format($groupFdTotal, 0) }}</p>
            <p class="text-xs text-slate-500 mt-2">Monthly Interest Yield: <strong class="text-emerald-700">LKR {{ number_format($groupFdMonthlyYield, 0) }} / mo</strong></p>
        </div>
    </div>

    {{-- Modern Interactive Multi-Company Graph --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="font-bold text-[#0f172a] text-lg">Group Companies Treasury Comparison</h2>
                <p class="text-xs text-slate-400 mt-0.5">Hover over any bar to view exact amounts &amp; rates per company</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-medium">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#c3122e]"></span> LTL Debt</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-500"></span> WC Debt</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-500"></span> Fixed Deposits</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-blue-600"></span> Available Cash</span>
            </div>
        </div>

        <div class="relative w-full h-80">
            <canvas id="companyGroupChart"></canvas>
        </div>
    </div>

    {{-- "My Companies" Grid Section --}}
    <div>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-[#0f172a]">My Companies</h2>
                <p class="text-xs text-slate-400">Click any company card below to drill down into its active portfolio</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($companySummaries as $summary)
                <a href="{{ route('ceo.dashboard', ['company_id' => $summary['id']]) }}"
                   class="block bg-white rounded-2xl border p-5 shadow-sm transition-all duration-200 text-left
                          {{ $selectedSummary && $selectedSummary['id'] === $summary['id']
                              ? 'border-[#c3122e] ring-2 ring-[#c3122e]/20 shadow-lg shadow-[#c3122e]/10'
                              : 'border-slate-200 hover:border-[#c3122e]/40 hover:shadow-md' }}">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-9 h-9 rounded-xl bg-[#fdf2f4] flex items-center justify-center">
                            <span class="text-[#c3122e] font-bold text-sm">{{ strtoupper(substr($summary['name'], 0, 2)) }}</span>
                        </div>
                        @if($selectedSummary && $selectedSummary['id'] === $summary['id'])
                            <span class="px-2 py-0.5 rounded-full bg-[#c3122e] text-white text-[10px] font-bold">Selected</span>
                        @endif
                    </div>
                    <p class="font-bold text-[#0f172a] text-sm truncate">{{ $summary['name'] }}</p>
                    
                    <div class="mt-3 pt-3 border-t border-slate-100 space-y-1 text-xs">
                        <div class="flex justify-between text-slate-500">
                            <span>LTL Debt:</span>
                            <span class="font-bold text-[#c3122e]">LKR {{ number_format($summary['ltlDebt'] / 1000000, 1) }}M</span>
                        </div>
                        <div class="flex justify-between text-slate-500">
                            <span>WC Debt:</span>
                            <span class="font-bold text-amber-600">LKR {{ number_format($summary['wcDebt'] / 1000000, 1) }}M</span>
                        </div>
                        <div class="flex justify-between text-slate-500">
                            <span>Fixed Deposits:</span>
                            <span class="font-bold text-emerald-600">LKR {{ number_format($summary['fdAmount'] / 1000000, 1) }}M</span>
                        </div>
                        <div class="flex justify-between text-slate-500">
                            <span>Available Cash:</span>
                            <span class="font-bold text-blue-600">LKR {{ number_format($summary['availableCash'] / 1000000, 1) }}M</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Focused Selected Company Portfolio Detail --}}
    @if($selectedSummary)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b pb-4 border-slate-100">
                <div>
                    <h2 class="font-bold text-[#0f172a] text-lg">{{ $selectedSummary['name'] }} — Detailed Treasury Breakdown</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Active portfolio entries &amp; bank rate details</p>
                </div>
                <a href="{{ url('/'.$selectedSummary['slug'].'/summary-dashboard') }}"
                   class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition-colors shadow-sm self-start sm:self-auto">
                    Open {{ $selectedSummary['name'] }} Portal →
                </a>
            </div>

            {{-- 4 Sub-Cards for Selected Company --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-red-50/50 border border-red-100">
                    <span class="text-[10px] uppercase font-bold text-red-700">Long Term Loans</span>
                    <p class="text-lg font-bold text-[#c3122e] mt-1">LKR {{ number_format($selectedSummary['ltlDebt'], 0) }}</p>
                    <span class="text-[11px] text-slate-500 block mt-0.5">{{ $selectedSummary['ltlCount'] }} Loans</span>
                </div>

                <div class="p-4 rounded-xl bg-amber-50/50 border border-amber-100">
                    <span class="text-[10px] uppercase font-bold text-amber-700">Working Capital</span>
                    <p class="text-lg font-bold text-amber-600 mt-1">LKR {{ number_format($selectedSummary['wcDebt'], 0) }}</p>
                    <span class="text-[11px] text-slate-500 block mt-0.5">{{ $selectedSummary['wcCount'] }} Facilities</span>
                </div>

                <div class="p-4 rounded-xl bg-emerald-50/50 border border-emerald-100">
                    <span class="text-[10px] uppercase font-bold text-emerald-700">Fixed Deposits</span>
                    <p class="text-lg font-bold text-emerald-600 mt-1">LKR {{ number_format($selectedSummary['fdAmount'], 0) }}</p>
                    <span class="text-[11px] text-slate-500 block mt-0.5">Yield: LKR {{ number_format($selectedSummary['fdMonthlyYield'], 0) }}/mo</span>
                </div>

                <div class="p-4 rounded-xl bg-blue-50/50 border border-blue-100">
                    <span class="text-[10px] uppercase font-bold text-blue-700">Available Cash</span>
                    <p class="text-lg font-bold text-blue-600 mt-1">LKR {{ number_format($selectedSummary['availableCash'], 0) }}</p>
                    <span class="text-[11px] text-slate-500 block mt-0.5">Daily Cash Position</span>
                </div>
            </div>
        </div>
    @endif

    {{-- Interactive Chart.js Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('companyGroupChart').getContext('2d');
            
            const companyNames = [
                @foreach($companySummaries as $s)
                    "{{ addslashes($s['name']) }}",
                @endforeach
            ];

            const ltlData = [
                @foreach($companySummaries as $s)
                    {{ round($s['ltlDebt'] / 1000000, 2) }},
                @endforeach
            ];

            const wcData = [
                @foreach($companySummaries as $s)
                    {{ round($s['wcDebt'] / 1000000, 2) }},
                @endforeach
            ];

            const fdData = [
                @foreach($companySummaries as $s)
                    {{ round($s['fdAmount'] / 1000000, 2) }},
                @endforeach
            ];

            const cashData = [
                @foreach($companySummaries as $s)
                    {{ round($s['availableCash'] / 1000000, 2) }},
                @endforeach
            ];

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: companyNames,
                    datasets: [
                        {
                            label: 'LTL Debt (M)',
                            data: ltlData,
                            backgroundColor: '#c3122e',
                            borderRadius: 6
                        },
                        {
                            label: 'WC Debt (M)',
                            data: wcData,
                            backgroundColor: '#f59e0b',
                            borderRadius: 6
                        },
                        {
                            label: 'Fixed Deposits (M)',
                            data: fdData,
                            backgroundColor: '#10b981',
                            borderRadius: 6
                        },
                        {
                            label: 'Available Cash (M)',
                            data: cashData,
                            backgroundColor: '#2563eb',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { size: 13, weight: 'bold' },
                            bodyFont: { size: 12 },
                            padding: 12,
                            cornerRadius: 12,
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.dataset.label + ': LKR ' + context.raw + ' M';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                callback: function(value) { return 'LKR ' + value + 'M'; }
                            }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        });
    </script>

</div>
@endsection
