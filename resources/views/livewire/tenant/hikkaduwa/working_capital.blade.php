{{--
    resources/views/livewire/tenant/[slug]/working_capital.blade.php
    Controller: App\Http\Controllers\Tenant\WorkingCapitalController
--}}
@extends('layouts.portal')
@section('header', 'Working Capital Loan Portfolio')

@section('content')
<div x-data="{ addingRow: false, revisingId: null }">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-[#0f172a] tracking-tight">Working Capital Loan Portfolio</h1>
            <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
                <span>{{ $company->name }}</span>
                <span>•</span>
                <span>Short-term credit lines, settlement tracking &amp; extensions</span>
            </p>
        </div>
        <button type="button" @click="addingRow = !addingRow"
            class="px-4 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-semibold transition-colors shadow-sm shadow-[#c3122e]/20 flex items-center gap-2 self-start sm:self-auto cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span x-text="addingRow ? 'Cancel Entry' : '+ Add WC Facility'"></span>
        </button>
    </div>

    @if(session('success'))
        <div class="mb-5 p-3.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Datalists for Autocomplete --}}
    <datalist id="wc-types-options">
        @foreach($existingFacilityTypes as $ft)<option value="{{ $ft }}"></option>@endforeach
    </datalist>
    <datalist id="wc-tenor-options">
        @foreach($existingTenors as $wct)<option value="{{ $wct }}"></option>@endforeach
    </datalist>

    {{-- KPI Header Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total WC Facilities</p>
            <p class="text-2xl font-bold text-[#0f172a]">{{ $loans->count() }} Facilities</p>
            <p class="text-xs text-slate-400 mt-1">Across {{ $loans->pluck('bank_id')->unique()->count() }} financial institutions</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Facility Approved</p>
            <p class="text-2xl font-bold text-[#0f172a]">LKR {{ number_format($totalFacility, 0) }}</p>
            <p class="text-xs text-slate-400 mt-1">Short-term credit limits</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-[#c3122e] uppercase tracking-wider mb-1">Total Outstanding</p>
            <p class="text-2xl font-bold text-[#c3122e]">LKR {{ number_format($totalOutstanding, 0) }}</p>
            <p class="text-xs text-slate-400 mt-1">Current utilized sum</p>
        </div>
    </div>

    {{-- Invisible Store Form --}}
    <form id="wc-new-row" method="POST" action="{{ route('tenant.working-capital.store', ['company_slug' => $company->slug]) }}">
        @csrf
    </form>

    {{-- Main Working Capital Table Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-slate-100 bg-[#f8fafc] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-[#0f172a] text-base">Working Capital Facilities</h2>
                <p class="text-xs text-slate-400">Short-term loans, settlement tracking, overdue days &amp; extensions</p>
            </div>

            {{-- Month & Year View Filter --}}
            <div class="flex items-center gap-3">
                <form method="GET" action="{{ route('tenant.working-capital', ['company_slug' => $company->slug]) }}" class="flex items-center gap-2">
                    <label class="text-xs font-bold text-slate-500 uppercase">Period:</label>
                    <select name="month" class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs bg-white font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-[#c3122e]">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ ($filterMonth ?? 4) == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                        @endforeach
                    </select>
                    <select name="year" class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs bg-white font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-[#c3122e]">
                        @foreach(range(2024, 2030) as $y)
                            <option value="{{ $y }}" {{ ($filterYear ?? 2026) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition-colors">Apply</button>
                </form>
                <span class="text-xs font-semibold text-[#c3122e] bg-[#fdf2f4] px-3 py-1 rounded-full border border-[#f8d7da]">
                    {{ $loans->count() }} Facilities
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-[#f1f5f9] text-slate-600 font-bold uppercase tracking-wider">
                        <th class="px-3 py-3 text-left">Bank</th>
                        <th class="px-3 py-3 text-left">Facility Type</th>
                        <th class="px-3 py-3 text-left font-mono">Tenor</th>
                        <th class="px-3 py-3 text-right">Facility Amt Rs./FCY</th>
                        <th class="px-3 py-3 text-center">Obtained Date</th>
                        <th class="px-3 py-3 text-center">Settlement Date</th>
                        <th class="px-3 py-3 text-center">Status / Overdue</th>
                        <th class="px-3 py-3 text-center">Days Extended</th>
                        <th class="px-3 py-3 text-center">Revised Settlement</th>
                        <th class="px-3 py-3 text-right">Rate %</th>
                        <th class="px-3 py-3 text-right font-extrabold text-[#0f172a]">
                            Outstanding as at {{ $filterDateFormatted ?? (($filterMonthName ?? 'April') . ' ' . ($filterYear ?? '2026')) }} Rs./FCY
                        </th>
                        <th class="px-3 py-3 text-center">CCY</th>
                        <th class="px-3 py-3 text-center">History</th>
                        <th class="px-3 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($loans as $loan)
                        @php
                            $effectiveSettlement = $loan->revised_settlement_date ?? $loan->settlement_date;
                            $daysOverdue = 0;
                            if ($effectiveSettlement) {
                                $daysOverdue = (int) now()->diffInDays($effectiveSettlement, false) * -1;
                            }
                            $isOverdue = $daysOverdue > 0;
                            $isSettled = ($loan->outstanding_amount == 0) || ($loan->action_type === 'settle_loan' && $loan->settlement_type === 'all');
                        @endphp
                        <tr class="transition-colors {{ $isSettled ? 'bg-emerald-50/80 border-l-4 border-l-emerald-500' : ($isOverdue ? 'bg-red-50/70 border-l-4 border-l-red-500' : 'hover:bg-[#fdf2f4]/30') }}">
                            <td class="px-3 py-3 font-medium text-[#0f172a]">
                                <span class="inline-block px-1.5 py-0.5 rounded bg-[#fdf2f4] text-[#c3122e] font-bold font-mono text-[10px] border border-[#f8d7da]">{{ $loan->bank->bank_code ?? '—' }}</span>
                                <span class="ml-1 font-semibold">{{ $loan->bank->name ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-3 font-bold text-slate-800">{{ $loan->facility_type }}</td>
                            <td class="px-3 py-3 font-mono font-bold text-slate-700">{{ $loan->tenor ?? '—' }}</td>
                            <td class="px-3 py-3 text-right font-mono text-slate-700">{{ number_format($loan->facility_amount, 0) }}</td>
                            <td class="px-3 py-3 text-center font-mono text-slate-600">{{ $loan->obtained_date ? $loan->obtained_date->format('d/m/Y') : '—' }}</td>
                            <td class="px-3 py-3 text-center font-mono text-slate-600">{{ $loan->settlement_date ? $loan->settlement_date->format('d/m/Y') : '—' }}</td>
                            <td class="px-3 py-3 text-center">
                                @if($isSettled)
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">Settled</span>
                                @elseif($isOverdue)
                                    <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-bold text-[10px]">⚠️ {{ $daysOverdue }}d Overdue</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-bold text-[10px]">Active</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center font-mono text-slate-600">
                                {{ $loan->days_extended ? $loan->days_extended.' d' : '—' }}
                            </td>
                            <td class="px-3 py-3 text-center font-mono text-slate-600">
                                {{ $loan->revised_settlement_date ? $loan->revised_settlement_date->format('d/m/Y') : '—' }}
                            </td>
                            <td class="px-3 py-3 text-right font-bold text-[#c3122e] text-sm">{{ number_format($loan->interest_rate, 3) }}%</td>
                            <td class="px-3 py-3 text-right font-mono font-bold text-[#0f172a] text-sm">
                                {{ $isSettled ? '0.00' : number_format($loan->outstanding_amount, 0) }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $loan->currency === 'USD' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600' }}">{{ $loan->currency }}</span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($loan->history_records->count() > 0)
                                    <span class="px-2 py-0.5 rounded bg-slate-800 text-white font-bold text-[10px]">
                                        📜 {{ $loan->history_records->count() }}
                                    </span>
                                @else
                                    <span class="text-slate-300 text-[10px]">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center flex items-center justify-center gap-1.5">
                                <button type="button" @click="revisingId = {{ $loan->id }}"
                                    class="p-1.5 rounded-lg bg-[#fdf2f4] hover:bg-[#c3122e] text-[#c3122e] hover:text-white transition-colors border border-[#f8d7da] cursor-pointer"
                                    title="Revise Rate / Settlement">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <form method="POST" action="{{ route('tenant.working-capital.destroy', ['company_slug' => $company->slug, 'workingCapitalLoan' => $loan->id]) }}" onsubmit="return confirm('Delete this facility permanently?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer" title="Delete Facility">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr x-show="!addingRow">
                            <td colspan="14" class="px-6 py-10 text-center text-slate-300 text-xs">No Working Capital facilities recorded yet. Click <strong class="text-[#c3122e]">+ Add WC Facility</strong> to create one.</td>
                        </tr>
                    @endforelse

                    {{-- Add New Inline Row --}}
                    <tr x-show="addingRow" class="bg-[#fffbf5] border-t-2 border-[#c3122e]/20">
                        <td class="px-2 py-2">
                            <select name="bank_id" form="wc-new-row" required class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs bg-white">
                                <option value="">— Bank —</option>
                                @foreach($banks as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                            </select>
                        </td>
                        <td class="px-2 py-2"><input type="text" name="facility_type" form="wc-new-row" required list="wc-types-options" placeholder="Type…" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs"></td>
                        <td class="px-2 py-2"><input type="text" name="tenor" form="wc-new-row" placeholder="30D/60D" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs font-mono text-center"></td>
                        <td class="px-2 py-2"><input type="number" name="facility_amount" form="wc-new-row" required step="1000" min="0" placeholder="0" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs font-mono text-right"></td>
                        <td class="px-2 py-2"><input type="date" name="obtained_date" form="wc-new-row" value="{{ date('Y-m-d') }}" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs"></td>
                        <td class="px-2 py-2"><input type="date" name="settlement_date" form="wc-new-row" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs"></td>
                        <td class="px-2 py-2 text-center text-slate-400 text-[10px]">—</td>
                        <td class="px-2 py-2"><input type="number" name="days_extended" form="wc-new-row" min="0" placeholder="0" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs text-center font-mono"></td>
                        <td class="px-2 py-2"><input type="date" name="revised_settlement_date" form="wc-new-row" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs"></td>
                        <td class="px-2 py-2"><input type="number" name="interest_rate" form="wc-new-row" required step="0.001" min="0" max="100" placeholder="0.000" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs font-mono text-right"></td>
                        <td class="px-2 py-2"><input type="number" name="outstanding_amount" form="wc-new-row" required step="1000" min="0" placeholder="0" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs font-mono text-right"></td>
                        <td class="px-2 py-2">
                            <select name="currency" form="wc-new-row" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs bg-white"><option value="LKR">LKR</option><option value="USD">USD</option></select>
                        </td>
                        <td class="px-2 py-2 text-center text-slate-400">
                            <input type="hidden" name="entry_date" form="wc-new-row" value="{{ date('Y-m-d') }}">
                            <span class="text-[10px]">New</span>
                        </td>
                        <td class="px-2 py-2 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button type="submit" form="wc-new-row" class="px-2.5 py-1 rounded-lg bg-[#c3122e] hover:bg-[#9e0e24] text-white text-[11px] font-bold shadow-sm cursor-pointer">
                                    Save
                                </button>
                                <button type="button" @click="addingRow = false" class="p-1 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer" title="Cancel / Remove Row">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Revise Rate & Settlement Popup Modal --}}
    @foreach($loans as $loan)
        <div x-show="revisingId === {{ $loan->id }}"
             x-cloak
             @click.self="revisingId = null"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto"
             x-data="{ mode: 'rate_change', settlementType: 'all' }">
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg p-6 relative my-6">
                
                <div class="flex items-center justify-between mb-4 border-b pb-3 border-slate-100">
                    <div>
                        <h3 class="font-bold text-[#0f172a] text-sm">Manage Facility: {{ $loan->facility_type }}</h3>
                        <p class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $loan->bank->name ?? 'Bank' }} | Outstanding: LKR {{ number_format($loan->outstanding_amount, 2) }}</p>
                    </div>
                    <button type="button" @click="revisingId = null" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
                </div>

                <div class="grid grid-cols-2 gap-2 p-1 rounded-xl bg-slate-100 mb-5">
                    <button type="button" @click="mode = 'rate_change'"
                        :class="mode === 'rate_change' ? 'bg-white text-[#c3122e] shadow-sm font-bold border border-slate-200' : 'text-slate-600 font-medium'"
                        class="py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span>Change Rate / Extend</span>
                    </button>
                    <button type="button" @click="mode = 'settle_loan'"
                        :class="mode === 'settle_loan' ? 'bg-[#c3122e] text-white shadow-sm font-bold' : 'text-slate-600 font-medium'"
                        class="py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span>Settle Facility</span>
                    </button>
                </div>

                <form method="POST" action="{{ route('tenant.working-capital.update-rate', ['company_slug' => $company->slug, 'workingCapitalLoan' => $loan->id]) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="action_type" :value="mode">

                    <div x-show="mode === 'rate_change'" class="space-y-3.5">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Current Rate</label>
                                <div class="px-3 py-2 bg-slate-50 rounded-xl border border-slate-200 text-xs font-mono font-bold text-slate-400">
                                    {{ number_format($loan->interest_rate, 3) }}%
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-[#c3122e] uppercase mb-1">New Rate (%) *</label>
                                <input type="number" step="0.001" min="0" max="100" name="new_interest_rate" value="{{ $loan->interest_rate }}"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-mono font-bold focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Days Extended</label>
                                <input type="number" min="0" name="days_extended" value="{{ $loan->days_extended ?? 0 }}"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-mono focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Revised Settlement Date</label>
                                <input type="date" name="revised_settlement_date" value="{{ $loan->revised_settlement_date ? $loan->revised_settlement_date->format('Y-m-d') : '' }}"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                            </div>
                        </div>
                    </div>

                    <div x-show="mode === 'settle_loan'" class="space-y-3.5">
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer"
                                   :class="settlementType === 'all' ? 'border-[#c3122e] bg-[#fdf2f4] text-[#c3122e] font-bold' : 'border-slate-200 text-slate-600'">
                                <input type="radio" name="settlement_type" value="all" x-model="settlementType" class="hidden">
                                <span>Full Settlement (100%)</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer"
                                   :class="settlementType === 'partial' ? 'border-[#c3122e] bg-[#fdf2f4] text-[#c3122e] font-bold' : 'border-slate-200 text-slate-600'">
                                <input type="radio" name="settlement_type" value="partial" x-model="settlementType" class="hidden">
                                <span>Partial Settlement</span>
                            </label>
                        </div>
                        <div x-show="settlementType === 'partial'">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Settled Sum (LKR) *</label>
                            <input type="number" step="1000" min="1" max="{{ $loan->outstanding_amount }}" name="settled_amount"
                                placeholder="Amount..."
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-mono font-bold focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Effective Date *</label>
                            <input type="date" name="effective_date" required value="{{ date('Y-m-d') }}"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-mono focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Revision Reason</label>
                            <input type="text" name="revision_notes" placeholder="Notes..."
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="revisingId = null" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-[#c3122e] text-white text-xs font-bold hover:bg-[#9e0e24] shadow-sm">Confirm Update</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

</div>
@endsection