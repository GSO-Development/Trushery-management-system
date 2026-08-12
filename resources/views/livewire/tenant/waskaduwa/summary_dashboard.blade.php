{{--
    resources/views/livewire/tenant/health/summary_dashboard.blade.php
    Controller: App\Http\Controllers\Tenant\TenantController
    Executive Overview for Head of Finance with Interactive Charts & Real-time Metrics
--}}
@extends('layouts.portal')
@section('header', 'Executive Summary Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Chart.js Script --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#0f172a] tracking-tight">Executive Summary Dashboard</h1>
            <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
                <span>🏢 {{ $company->name }}</span>
                <span>•</span>
                <span class="font-medium text-slate-600">Treasury &amp; Financial Capital Analytics</span>
            </p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <span class="px-3.5 py-1.5 rounded-full bg-[#fdf2f4] border border-[#f8d7da] text-[#c3122e] text-xs font-bold uppercase tracking-wider shadow-sm flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-[#c3122e] animate-pulse"></span>
                Live Finance Portal
            </span>
        </div>
    </div>

    {{-- Top Executive 4 Core KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        {{-- Card 1: Long Term Loans --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 relative overflow-hidden group hover:border-[#c3122e]/30 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Long Term Loans</span>
                <span class="p-2 rounded-xl bg-red-50 text-[#c3122e]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
            </div>
            <p class="text-2xl font-black text-[#c3122e]">LKR {{ number_format($ltlOutstanding, 0) }}</p>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Facility: LKR {{ number_format($ltlFacilities, 0) }}</span>
                <span class="font-bold text-slate-700">{{ $ltlCount }} Loans ({{ number_format($avgLtlRate, 2) }}%)</span>
            </div>
        </div>

        {{-- Card 2: Working Capital Loans --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 relative overflow-hidden group hover:border-amber-500/30 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Working Capital Loans</span>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <p class="text-2xl font-black text-amber-600">LKR {{ number_format($wcOutstanding, 0) }}</p>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Facility: LKR {{ number_format($wcFacilities, 0) }}</span>
                <span class="font-bold text-slate-700">{{ $wcCount }} Facilities ({{ number_format($avgWcRate, 2) }}%)</span>
            </div>
        </div>

        {{-- Card 3: Fixed Deposits --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 relative overflow-hidden group hover:border-emerald-500/30 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Fixed Deposits</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </span>
            </div>
            <p class="text-2xl font-black text-emerald-600">LKR {{ number_format($fixedDepositsTotal, 0) }}</p>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Monthly Profit: <strong class="text-emerald-700">LKR {{ number_format($fdMonthlyProfit, 0) }}</strong></span>
                <span class="font-bold text-slate-700">{{ $fdCount }} FDs ({{ number_format($avgFdRate, 2) }}%)</span>
            </div>
        </div>

        {{-- Card 4: Net Available Cash Position --}}
        <div class="bg-gradient-to-br from-blue-900 to-indigo-900 rounded-2xl text-white shadow-lg shadow-blue-900/20 p-5 relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-blue-200 uppercase tracking-wider">Net Available Cash</span>
                <span class="p-2 rounded-xl bg-white/10 text-blue-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <p class="text-2xl font-black text-white">LKR {{ number_format($availableCash, 0) }}</p>
            <div class="mt-3 pt-3 border-t border-white/10 flex items-center justify-between text-xs text-blue-200">
                <span>Total Liquid Assets: <strong>LKR {{ number_format($totalLiquidAssets, 0) }}</strong></span>
            </div>
        </div>
    </div>

    {{-- Overdue Working Capital Banner Only (FD Banners Removed as requested) --}}
    @if($overdueWcCount > 0)
        <div class="p-4 rounded-2xl bg-amber-500 text-white shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs uppercase tracking-wider font-bold opacity-80">⚠️ Overdue Working Capital</div>
                <div class="text-lg font-extrabold mt-0.5">{{ $overdueWcCount }} Overdue Loans (LKR {{ number_format($overdueWcAmount, 0) }})</div>
            </div>
            <a href="{{ route('tenant.working-capital', ['company_slug' => $company->slug]) }}" class="px-3.5 py-1.5 rounded-xl bg-white text-amber-800 font-bold text-xs shadow-sm hover:bg-slate-100 transition-colors">Action</a>
        </div>
    @endif

    {{-- Interactive Visual Analytics & Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- Chart 1: Debt vs Asset Capital Allocation (Donut Chart) --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-bold text-[#0f172a] text-sm">Treasury Portfolio Allocation</h3>
                    <span class="text-[10px] font-mono text-slate-400">Distribution %</span>
                </div>
                <p class="text-xs text-slate-400 mb-4">Proportion of debt obligations vs liquid assets</p>
                <div class="relative w-full h-56 flex items-center justify-center">
                    <canvas id="portfolioDonutChart"></canvas>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-4 pt-4 border-t border-slate-100 text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#c3122e]"></span>
                    <span class="text-slate-600 font-medium">LTL Debt</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <span class="text-slate-600 font-medium">WC Debt</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span class="text-slate-600 font-medium">Fixed Deposits</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                    <span class="text-slate-600 font-medium">Available Cash</span>
                </div>
            </div>
        </div>

        {{-- Chart 2: Facility Limit vs Current Debt (Grouped Bar Chart) --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 lg:col-span-2 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-bold text-[#0f172a] text-sm">Approved Facilities vs Outstanding Debt</h3>
                    <span class="text-xs text-slate-500 font-bold bg-slate-100 px-2.5 py-1 rounded-full">LKR Millions</span>
                </div>
                <p class="text-xs text-slate-400 mb-4">Compares sanctioned credit limits against current utilized balances</p>
                <div class="relative w-full h-64">
                    <canvas id="facilityVsDebtBarChart"></canvas>
                </div>
            </div>
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-100 text-xs">
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-300"></span> Approved Limit</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#c3122e]"></span> Utilized Debt</span>
                </div>
                <span class="text-slate-500 font-mono text-[11px]">Net Exposure: LKR {{ number_format($totalDebtOutstanding, 0) }}</span>
            </div>
        </div>
    </div>

    {{-- Quick Action Shortcuts --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <a href="{{ route('tenant.long-term-loans', ['company_slug' => $company->slug]) }}"
           class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm hover:shadow-md hover:border-[#c3122e] transition-all group flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 group-hover:text-[#c3122e]">Manage</p>
                <p class="text-sm font-extrabold text-[#0f172a]">Long Term Loans →</p>
            </div>
            <span class="w-8 h-8 rounded-xl bg-red-50 text-[#c3122e] flex items-center justify-center font-bold text-xs">LTL</span>
        </a>
        <a href="{{ route('tenant.working-capital', ['company_slug' => $company->slug]) }}"
           class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm hover:shadow-md hover:border-amber-500 transition-all group flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 group-hover:text-amber-600">Manage</p>
                <p class="text-sm font-extrabold text-[#0f172a]">Working Capital →</p>
            </div>
            <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs">WC</span>
        </a>
        <a href="{{ route('tenant.fixed-deposits', ['company_slug' => $company->slug]) }}"
           class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm hover:shadow-md hover:border-emerald-500 transition-all group flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 group-hover:text-emerald-600">Manage</p>
                <p class="text-sm font-extrabold text-[#0f172a]">Fixed Deposits →</p>
            </div>
            <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">FD</span>
        </a>
        <a href="{{ route('tenant.cash-position', ['company_slug' => $company->slug]) }}"
           class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-600 transition-all group flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 group-hover:text-blue-600">Manage</p>
                <p class="text-sm font-extrabold text-[#0f172a]">Daily Cash Position →</p>
            </div>
            <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">CP</span>
        </a>
    </div>

    {{-- Initialize Chart.js Code --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart 1: Donut Chart - Treasury Portfolio Allocation
            const ctxDonut = document.getElementById('portfolioDonutChart').getContext('2d');
            new Chart(ctxDonut, {
                type: 'doughnut',
                data: {
                    labels: ['Long Term Loans Debt', 'Working Capital Debt', 'Fixed Deposits', 'Available Cash'],
                    datasets: [{
                        data: [
                            {{ round($ltlOutstanding / 1000000, 2) }},
                            {{ round($wcOutstanding / 1000000, 2) }},
                            {{ round($fixedDepositsTotal / 1000000, 2) }},
                            {{ round($availableCash / 1000000, 2) }}
                        ],
                        backgroundColor: ['#c3122e', '#f59e0b', '#10b981', '#2563eb'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.label + ': LKR ' + context.raw + ' M';
                                }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });

            // Chart 2: Grouped Bar Chart - Facility vs Debt
            const ctxBar = document.getElementById('facilityVsDebtBarChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: ['Long Term Loans', 'Working Capital Loans', 'Fixed Deposits'],
                    datasets: [
                        {
                            label: 'Approved Facility Limit (M)',
                            data: [
                                {{ round($ltlFacilities / 1000000, 2) }},
                                {{ round($wcFacilities / 1000000, 2) }},
                                {{ round($fixedDepositsTotal / 1000000, 2) }}
                            ],
                            backgroundColor: '#cbd5e1',
                            borderRadius: 6
                        },
                        {
                            label: 'Current Balance (M)',
                            data: [
                                {{ round($ltlOutstanding / 1000000, 2) }},
                                {{ round($wcOutstanding / 1000000, 2) }},
                                {{ round($fixedDepositsTotal / 1000000, 2) }}
                            ],
                            backgroundColor: '#c3122e',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
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
