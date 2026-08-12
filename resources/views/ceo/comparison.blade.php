{{--
    resources/views/ceo/comparison.blade.php
    Controller: App\Http\Controllers\Ceo\GroupComparisonController
    Multi-Company Treasury Comparison Tool & Bank Interest Rate Matrix
--}}
@extends('layouts.ceo')

@section('header', 'Group Company Comparison')

@section('content')
<div class="space-y-6" x-data="{
    selectAllCompanies() {
        const checkboxes = document.querySelectorAll('.company-checkbox');
        checkboxes.forEach(cb => cb.checked = true);
        document.getElementById('comparison-filter-form').submit();
    },
    deselectAllCompanies() {
        const checkboxes = document.querySelectorAll('.company-checkbox');
        checkboxes.forEach(cb => cb.checked = false);
        document.getElementById('comparison-filter-form').submit();
    }
}">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#0f172a] tracking-tight">Group Treasury Comparison &amp; Bank Matrix</h1>
            <p class="text-sm text-slate-500 mt-1">Side-by-side comparison of loan facilities, interest rates, deposits &amp; cash across companies</p>
        </div>
        <a href="{{ route('ceo.dashboard') }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors self-start sm:self-auto">
            ← Back to Overview Dashboard
        </a>
    </div>

    {{-- Top Multi-Company Filter & Instrument Category Form --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
        <form id="comparison-filter-form" method="GET" action="{{ route('ceo.comparison') }}" class="space-y-5">
            
            {{-- Category Filter Tabs --}}
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2.5">1. Select Treasury Category</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <button type="submit" name="category" value="long_term_loans"
                        class="py-3 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer
                        {{ $selectedCategory === 'long_term_loans' ? 'bg-[#c3122e] text-white shadow-md shadow-[#c3122e]/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        <span>🏛️ Long Term Loans</span>
                    </button>

                    <button type="submit" name="category" value="working_capital"
                        class="py-3 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer
                        {{ $selectedCategory === 'working_capital' ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        <span>💼 Working Capital</span>
                    </button>

                    <button type="submit" name="category" value="fixed_deposits"
                        class="py-3 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer
                        {{ $selectedCategory === 'fixed_deposits' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        <span>💰 Fixed Deposits</span>
                    </button>

                    <button type="submit" name="category" value="cash_position"
                        class="py-3 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer
                        {{ $selectedCategory === 'cash_position' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        <span>🏦 Daily Cash Position</span>
                    </button>
                </div>
            </div>

            {{-- Multi-Company Selector Checkboxes --}}
            <div class="pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between mb-3">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">2. Select Companies to Compare Side-by-Side</label>
                    <div class="flex gap-2">
                        <button type="button" @click="selectAllCompanies()" class="text-[11px] font-bold text-[#c3122e] hover:underline">Select All</button>
                        <span class="text-slate-300">•</span>
                        <button type="button" @click="deselectAllCompanies()" class="text-[11px] font-bold text-slate-500 hover:underline">Clear All</button>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    @foreach($companies as $comp)
                        @php $isChecked = in_array($comp->id, $selectedCompanyIds); @endphp
                        <label class="flex items-center gap-2.5 p-2.5 rounded-xl border transition-all cursor-pointer text-xs font-medium
                            {{ $isChecked ? 'bg-[#fdf2f4]/60 border-[#c3122e] text-[#0f172a] font-bold' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' }}">
                            <input type="checkbox" name="company_ids[]" value="{{ $comp->id }}" {{ $isChecked ? 'checked' : '' }}
                                class="company-checkbox rounded text-[#c3122e] focus:ring-[#c3122e] w-4 h-4"
                                onchange="document.getElementById('comparison-filter-form').submit()">
                            <span class="truncate">{{ $comp->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </form>
    </div>

    {{-- Instrument Category Title Banner --}}
    <div class="bg-[#0f172a] rounded-2xl p-5 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-lg">
        <div>
            <span class="text-xs font-bold text-[#c3122e] uppercase tracking-wider">Comparative Analysis Category</span>
            <h2 class="text-xl font-bold mt-0.5 capitalize">
                {{ str_replace('_', ' ', $selectedCategory) }} Bank &amp; Interest Rate Matrix
            </h2>
            <p class="text-xs text-slate-400 mt-1">Comparing {{ count($filteredCompanies) }} companies across {{ count($banks) }} banking institutions</p>
        </div>
        <div class="text-right">
            <span class="text-xs text-slate-400 block">Total Category Exposure</span>
            <span class="text-2xl font-black text-white">LKR {{ number_format(collect($matrix)->sum('total_amount'), 0) }}</span>
        </div>
    </div>

    {{-- Bank x Company Interest Rate & Facility Matrix Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-[#0f172a] text-sm">Bank x Company Rate &amp; Facility Matrix</h3>
                <p class="text-xs text-slate-400 mt-0.5">Shows how many facilities each bank provided and interest rates charged per company</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-[#f8fafc] text-slate-600">
                        <th class="px-4 py-3 text-left font-bold uppercase tracking-wider min-w-[140px] sticky left-0 bg-[#f8fafc] shadow-sm">Bank / Institution</th>
                        @foreach($filteredCompanies as $comp)
                            <th class="px-4 py-3 text-center font-bold uppercase tracking-wider min-w-[140px] border-l border-slate-200">
                                {{ $comp->name }}
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-center font-bold uppercase tracking-wider min-w-[140px] bg-slate-100 border-l border-slate-300">
                            Group Total / Avg
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($matrix as $bankId => $row)
                        <tr class="hover:bg-slate-50 transition-colors">
                            {{-- Bank Name --}}
                            <td class="px-4 py-3.5 font-bold text-[#0f172a] sticky left-0 bg-white shadow-sm border-r border-slate-100">
                                <span class="px-1.5 py-0.5 rounded bg-[#fdf2f4] text-[#c3122e] font-mono text-[10px] border border-[#f8d7da] mr-1.5">{{ $row['bank']->bank_code }}</span>
                                <span>{{ $row['bank']->name }}</span>
                            </td>

                            {{-- Per-Company Matrix Cells --}}
                            @foreach($filteredCompanies as $comp)
                                @php
                                    $cell = $row['companies'][$comp->id] ?? ['count' => 0, 'avg_rate' => 0, 'amount' => 0];
                                @endphp
                                <td class="px-3 py-3 text-center border-l border-slate-100 font-mono">
                                    @if($cell['count'] > 0 || $cell['amount'] > 0)
                                        <div class="space-y-1">
                                            <span class="inline-block px-2 py-0.5 rounded bg-blue-50 text-blue-800 font-bold text-[10px]">
                                                {{ $cell['count'] }} {{ $selectedCategory === 'fixed_deposits' ? 'FDs' : 'Loans' }}
                                            </span>
                                            @if($cell['avg_rate'] > 0)
                                                <div class="font-bold text-[#c3122e] text-xs">
                                                    {{ number_format($cell['avg_rate'], 3) }}%
                                                </div>
                                            @endif
                                            <div class="text-slate-800 font-bold text-[11px]">
                                                LKR {{ number_format($cell['amount'] / 1000000, 2) }}M
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-slate-300 font-normal">—</span>
                                    @endif
                                </td>
                            @endforeach

                            {{-- Group Total / Avg Cell --}}
                            <td class="px-3 py-3 text-center bg-slate-50 border-l border-slate-200 font-mono font-bold">
                                @if($row['total_count'] > 0 || $row['total_amount'] > 0)
                                    <div class="space-y-1">
                                        <span class="inline-block px-2 py-0.5 rounded bg-slate-800 text-white font-bold text-[10px]">
                                            {{ $row['total_count'] }} Total
                                        </span>
                                        @if($row['avg_rate'] > 0)
                                            <div class="text-[#c3122e] text-xs">
                                                Avg {{ number_format($row['avg_rate'], 3) }}%
                                            </div>
                                        @endif
                                        <div class="text-[#0f172a] text-xs font-black">
                                            LKR {{ number_format($row['total_amount'] / 1000000, 2) }}M
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($filteredCompanies) + 2 }}" class="p-8 text-center text-slate-400">
                                No records found matching the selected filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Interactive Bank Rate Comparison Line Chart --}}
    @if(count($banks) > 0)
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-[#0f172a] text-base">Interest Rate Comparison by Bank Across Companies</h3>
                <p class="text-xs text-slate-400">Compares interest rates charged by different banks to each sub-company</p>
            </div>
            <span class="text-xs font-bold text-[#c3122e]">Rate %</span>
        </div>

        <div class="relative w-full h-72">
            <canvas id="bankRateComparisonChart"></canvas>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('bankRateComparisonChart').getContext('2d');
            
            const companyNames = [
                @foreach($filteredCompanies as $c)
                    "{{ addslashes($c->name) }}",
                @endforeach
            ];

            const colors = ['#c3122e', '#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'];

            const datasets = [
                @foreach($matrix as $bId => $row)
                    {
                        label: "{{ addslashes($row['bank']->name) }}",
                        data: [
                            @foreach($filteredCompanies as $c)
                                {{ round($row['companies'][$c->id]['avg_rate'] ?? 0, 3) }},
                            @endforeach
                        ],
                        borderColor: colors[{{ $loop->index % 8 }}],
                        backgroundColor: colors[{{ $loop->index % 8 }}] + '20',
                        borderWidth: 2,
                        tension: 0.2,
                        fill: false
                    },
                @endforeach
            ];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: companyNames,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.dataset.label + ': ' + context.raw + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                callback: function(value) { return value + '%'; }
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
    @endif

</div>
@endsection
