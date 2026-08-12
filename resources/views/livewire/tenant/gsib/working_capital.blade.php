{{--
    resources/views/livewire/tenant/health/working_capital.blade.php
    Controller: App\Http\Controllers\Tenant\WorkingCapitalController
--}}
@extends('layouts.portal')
@section('header', 'Working Capital Loan')

@section('content')
<div x-data="{ addingRow: false, revisingId: null }">

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-bold text-[#0f172a]">Working Capital Loan Portfolio</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $company->name }} · Working Capital Facilities, IML &amp; Settlements</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Outstanding WC</p>
            <p class="text-xl font-bold text-amber-600">LKR {{ number_format($totalOutstanding, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Facilities Approved</p>
            <p class="text-xl font-bold text-[#0f172a]">LKR {{ number_format($totalFacility, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Overdue Loans</p>
            <p class="text-xl font-bold text-red-600">{{ $loans->filter(fn($l) => $l->settlement_days_overdue > 0)->count() }} Facilities</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Avg Interest Rate</p>
            <p class="text-xl font-bold text-[#c3122e]">{{ $loans->count() ? number_format($loans->avg('interest_rate'), 2).'%' : '—' }}</p>
        </div>
    </div>

    {{-- Hidden New Entry Form --}}
    <form id="wc-new-row" method="POST" action="{{ route('tenant.working-capital.store', ['company_slug' => $company->slug]) }}">@csrf</form>

    <datalist id="wc-types-options">@foreach($existingFacilityTypes as $t)<option value="{{ $t }}">@endforeach</datalist>
    <datalist id="wc-tenors-options">@foreach($existingTenors as $t)<option value="{{ $t }}">@endforeach</datalist>

    {{-- Main Table Card --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-[#0f172a] text-sm">Working Capital Loan Portfolio</h2>
                <p class="text-xs text-slate-400 mt-0.5">{{ $loans->count() }} active facilities · Click 📜 History to view past revisions &amp; settlements</p>
            </div>
            <button type="button" @click="addingRow = true" x-show="!addingRow"
                class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-bold transition-colors shadow-sm cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Working Capital Loan
            </button>
            <div x-show="addingRow" class="flex gap-2">
                <button type="submit" form="wc-new-row" class="px-3.5 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white text-xs font-bold">✓ Save</button>
                <button type="button" @click="addingRow = false" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold">✕ Cancel</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-[#f8fafc] text-slate-500 text-[11px]">
                        <th class="px-3 py-2.5 text-left font-semibold uppercase tracking-wider min-w-[100px]">Bank</th>
                        <th class="px-3 py-2.5 text-left font-semibold uppercase tracking-wider min-w-[120px]">Facility Type</th>
                        <th class="px-3 py-2.5 text-left font-semibold uppercase tracking-wider min-w-[80px]">Tenor</th>
                        <th class="px-3 py-2.5 text-right font-semibold uppercase tracking-wider min-w-[100px]">Facility Amt</th>
                        <th class="px-3 py-2.5 text-left font-semibold uppercase tracking-wider min-w-[90px]">Settlement</th>
                        <th class="px-3 py-2.5 text-center font-semibold uppercase tracking-wider min-w-[65px]">Status</th>
                        <th class="px-3 py-2.5 text-right font-semibold uppercase tracking-wider min-w-[65px]">Rate %</th>
                        <th class="px-3 py-2.5 text-right font-semibold uppercase tracking-wider min-w-[110px]">Outstanding</th>
                        <th class="px-3 py-2.5 text-center font-semibold uppercase tracking-wider min-w-[50px]">CCY</th>
                        <th class="px-3 py-2.5 text-center font-semibold uppercase tracking-wider min-w-[100px]">History</th>
                        <th class="px-3 py-2.5 text-center font-semibold uppercase tracking-wider min-w-[90px]">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50" x-data="{ openHistoryId: null }">
                    @forelse($loans as $loan)
                        @php
                            $isOverdue = $loan->settlement_days_overdue > 0;
                            $settleDate = $loan->bank_confirmed_date ?? $loan->revised_settlement_date ?? $loan->settlement_date;
                            $daysLeft = $settleDate ? now()->diffInDays($settleDate, false) : null;
                            $isDueSoon = $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 30;
                            $isSettled = ($loan->outstanding_amount == 0) || ($loan->action_type === 'settle_loan' && $loan->settlement_type === 'all');
                        @endphp
                        <tr class="transition-colors {{ $isSettled ? 'bg-emerald-50/80 border-l-4 border-l-emerald-500 hover:bg-emerald-100/60' : ($isOverdue ? 'bg-red-50/60 border-l-4 border-l-red-500 hover:bg-red-100/60' : ($isDueSoon ? 'bg-amber-50/60 border-l-4 border-l-amber-500 hover:bg-amber-100/60' : 'hover:bg-[#fdf2f4]/30')) }}">
                            <td class="px-3 py-3 font-medium text-[#0f172a]">
                                <span class="inline-block px-1.5 py-0.5 rounded bg-[#fdf2f4] text-[#c3122e] font-bold font-mono text-[10px] border border-[#f8d7da]">{{ $loan->bank->bank_code ?? '—' }}</span>
                                <span class="ml-1 font-semibold">{{ $loan->bank->name ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-3 font-bold text-slate-800">
                                {{ $loan->facility_type }}
                                @if($isSettled)
                                    <span class="ml-1.5 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[9px] uppercase tracking-wider">Settled</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 font-mono font-bold text-slate-700">{{ $loan->formatted_tenor }}</td>
                            <td class="px-3 py-3 text-right font-mono text-slate-700">{{ number_format($loan->facility_amount, 0) }}</td>
                            <td class="px-3 py-3 text-slate-600 font-medium">
                                {{ $settleDate?->format('d M Y') ?? '—' }}
                                @if($loan->is_bank_confirmed && $loan->bank_confirmed_date)
                                    <span class="block text-[10px] text-emerald-700 font-bold font-mono">✓ Bank Confirmed</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($isSettled)
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">Settled</span>
                                @elseif($isOverdue)
                                    <span class="px-1.5 py-0.5 rounded bg-red-100 text-red-700 font-bold text-[10px]">{{ $loan->settlement_days_overdue }}d Overdue</span>
                                @elseif($isDueSoon)
                                    <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-bold text-[10px]">Due in {{ $daysLeft }}d</span>
                                @else
                                    <span class="px-1.5 py-0.5 rounded bg-green-100 text-green-700 font-bold text-[10px]">Active</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right font-bold text-[#c3122e] text-sm">{{ number_format($loan->interest_rate, 3) }}%</td>
                            <td class="px-3 py-3 text-right font-mono font-bold text-[#0f172a] text-sm">
                                @if($isSettled)
                                    <span class="text-emerald-700">LKR 0.00</span>
                                @else
                                    {{ number_format($loan->outstanding_amount, 0) }}
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $loan->currency === 'USD' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600' }}">{{ $loan->currency }}</span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($loan->history_records->count() > 0)
                                    <button type="button" @click="openHistoryId = (openHistoryId === {{ $loan->id }} ? null : {{ $loan->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-bold text-[10px] transition-colors shadow-sm cursor-pointer">
                                        📜 History ({{ $loan->history_records->count() }})
                                    </button>
                                @else
                                    <span class="text-slate-300 text-[10px]">No revisions</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center flex items-center justify-center gap-1.5">
                                {{-- Icon Only Edit / Revise Button --}}
                                <button type="button" @click="revisingId = {{ $loan->id }}"
                                    class="p-1.5 rounded-lg bg-[#fdf2f4] hover:bg-[#c3122e] text-[#c3122e] hover:text-white transition-colors border border-[#f8d7da] cursor-pointer"
                                    title="Revise IML &amp; Settle Loan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <form method="POST" action="{{ route('tenant.working-capital.destroy', ['company_slug' => $company->slug, 'workingCapitalLoan' => $loan->id]) }}" onsubmit="return confirm('Delete this loan entry?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Delete Loan"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                </form>
                            </td>
                        </tr>

                        {{-- History Drawer Sub-Row --}}
                        @if($loan->history_records->count() > 0)
                        <tr x-show="openHistoryId === {{ $loan->id }}" x-cloak class="bg-slate-100/90 border-y border-slate-300">
                            <td colspan="11" class="p-4">
                                <div class="text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center justify-between border-b border-slate-300 pb-2">
                                    <span>📜 Complete Version &amp; Settlement Revision History for {{ $loan->facility_type }} (Current: Version {{ $loan->version }})</span>
                                    <span class="text-[10px] text-slate-500 font-normal">{{ $loan->history_records->count() }} archived revisions</span>
                                </div>
                                <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                                    @foreach($loan->history_records as $hist)
                                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3.5 text-xs text-slate-600 space-y-2.5">
                                            {{-- Version & Timestamp Bar --}}
                                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-0.5 rounded bg-slate-800 text-white font-mono font-bold text-[10px]">Version {{ $hist->version }}</span>
                                                    <span class="text-slate-700 font-semibold">🕒 {{ $hist->created_at->format('d M Y, h:i:s A') }}</span>
                                                    <span class="text-slate-400">· Modified by <strong class="text-slate-700">{{ $hist->user->name ?? 'User' }}</strong></span>
                                                </div>
                                                @if($hist->action_type === 'settle_loan')
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                                                            Settle Loan ({{ strtoupper($hist->settlement_type ?? 'ALL') }}: LKR {{ number_format($hist->settled_amount ?? $hist->outstanding_amount, 2) }})
                                                        </span>
                                                        @if($hist->settledViaLoan)
                                                            <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-bold text-[10px]">
                                                                🔄 Settled via: {{ $hist->settledViaLoan->bank->name ?? 'Bank' }} - {{ $hist->settledViaLoan->facility_type }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                @elseif($hist->is_bank_confirmed && $hist->bank_confirmed_date)
                                                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                                                        ✓ Bank Confirmed Date: {{ $hist->bank_confirmed_date->format('d M Y') }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($hist->revision_notes)
                                                <div class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 font-semibold text-[11px] flex items-center gap-1">
                                                    📝 Revision / Settlement Notes: "{{ $hist->revision_notes }}"
                                                </div>
                                            @endif

                                            {{-- Snapshot Attributes Grid --}}
                                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 font-mono text-[11px]">
                                                <div class="bg-slate-50 p-2 rounded-lg border border-slate-100">
                                                    <span class="text-[10px] font-sans text-slate-400 block uppercase font-semibold">Interest Rate</span>
                                                    <span class="font-bold text-[#c3122e] text-xs">{{ number_format($hist->interest_rate, 3) }}%</span>
                                                </div>
                                                <div class="bg-slate-50 p-2 rounded-lg border border-slate-100">
                                                    <span class="text-[10px] font-sans text-slate-400 block uppercase font-semibold">Outstanding Amount</span>
                                                    <span class="font-bold text-slate-800 text-xs">LKR {{ number_format($hist->outstanding_amount, 0) }}</span>
                                                </div>
                                                <div class="bg-slate-50 p-2 rounded-lg border border-slate-100">
                                                    <span class="text-[10px] font-sans text-slate-400 block uppercase font-semibold">Facility Limit</span>
                                                    <span class="font-semibold text-slate-700">LKR {{ number_format($hist->facility_amount, 0) }}</span>
                                                </div>
                                                <div class="bg-slate-50 p-2 rounded-lg border border-slate-100">
                                                    <span class="text-[10px] font-sans text-slate-400 block uppercase font-semibold">Tenor</span>
                                                    <span class="font-semibold text-slate-700">{{ $hist->formatted_tenor }}</span>
                                                </div>
                                                <div class="bg-slate-50 p-2 rounded-lg border border-slate-100">
                                                    <span class="text-[10px] font-sans text-slate-400 block uppercase font-semibold">Settlement Date</span>
                                                    <span class="font-semibold text-slate-700">{{ $hist->bank_confirmed_date?->format('d M Y') ?? $hist->revised_settlement_date?->format('d M Y') ?? $hist->settlement_date?->format('d M Y') ?? '—' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @endif

                    @empty
                        <tr x-show="!addingRow">
                            <td colspan="11" class="px-6 py-10 text-center text-slate-300 text-xs">No Working Capital Loans recorded yet. Click <strong class="text-[#c3122e]">+ Add Working Capital Loan</strong> to create one.</td>
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
                        <td class="px-2 py-2"><input type="number" min="1" name="tenor" form="wc-new-row" placeholder="Months (e.g. 3)" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs font-mono text-center"></td>
                        <td class="px-2 py-2"><input type="number" name="facility_amount" form="wc-new-row" required step="1000" min="0" placeholder="0" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs font-mono text-right"></td>
                        <td class="px-2 py-2"><input type="date" name="settlement_date" form="wc-new-row" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs"></td>
                        <td class="px-2 py-2 text-center text-slate-400">—</td>
                        <td class="px-2 py-2"><input type="number" name="interest_rate" form="wc-new-row" required step="0.001" min="0" max="100" placeholder="0.000" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs font-mono text-right"></td>
                        <td class="px-2 py-2"><input type="number" name="outstanding_amount" form="wc-new-row" required step="1000" min="0" placeholder="0" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs font-mono text-right"></td>
                        <td class="px-2 py-2">
                            <select name="currency" form="wc-new-row" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs bg-white"><option value="LKR">LKR</option><option value="USD">USD</option></select>
                        </td>
                        <td colspan="2" class="px-2 py-2">
                            <input type="date" name="entry_date" form="wc-new-row" required value="{{ date('Y-m-d') }}" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs">
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
             @keydown.escape.window="revisingId = null"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto"
             x-data="{ mode: 'revise_iml', settlementType: 'all', settleUsingNewLoan: false, isBankConfirmed: {{ $loan->is_bank_confirmed ? 'true' : 'false' }} }">
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg p-6 relative overflow-hidden my-6">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-4 border-b pb-3 border-slate-100">
                    <div>
                        <h3 class="font-bold text-[#0f172a] text-sm">Revise Rate &amp; Settlement: {{ $loan->facility_type }}</h3>
                        <p class="text-[11px] text-slate-400 font-mono mt-0.5">Bank: {{ $loan->bank->name ?? '—' }} | Outstanding: LKR {{ number_format($loan->outstanding_amount, 2) }}</p>
                    </div>
                    <button type="button" @click.prevent="revisingId = null" class="text-slate-400 hover:text-slate-600 p-1 text-base leading-none font-bold">✕</button>
                </div>

                <!-- 2 Top Mode Switcher Buttons: Revise IML and Settle -->
                <div class="grid grid-cols-2 gap-2 p-1 rounded-xl bg-slate-100 mb-5">
                    <button type="button"
                        @click="mode = 'revise_iml'"
                        :class="mode === 'revise_iml' ? 'bg-white text-[#c3122e] shadow-sm font-bold border border-slate-200' : 'text-slate-600 font-medium hover:text-slate-900'"
                        class="py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        <span>Revise IML</span>
                    </button>
                    <button type="button"
                        @click="mode = 'settle_loan'"
                        :class="mode === 'settle_loan' ? 'bg-[#c3122e] text-white shadow-sm font-bold' : 'text-slate-600 font-medium hover:text-slate-900'"
                        class="py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Settle</span>
                    </button>
                </div>

                <form method="POST" action="{{ route('tenant.working-capital.update-rate', ['company_slug' => $company->slug, 'workingCapitalLoan' => $loan->id]) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="action_type" :value="mode">

                    <!-- Mode 1: Revise IML Fields -->
                    <div x-show="mode === 'revise_iml'" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1.5">New Interest Rate (%) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.001" name="interest_rate" value="{{ $loan->interest_rate }}" :required="mode === 'revise_iml'" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1.5">New Outstanding Amount <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" name="outstanding_amount" value="{{ $loan->outstanding_amount }}" :required="mode === 'revise_iml'" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1.5">Revised Settlement Date</label>
                            <input type="date" name="revised_settlement_date" value="{{ $loan->revised_settlement_date?->format('Y-m-d') ?? $loan->settlement_date?->format('Y-m-d') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
                        </div>

                        <!-- Is Bank Confirmed Date Checkbox -->
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="is_bank_confirmed" value="1" x-model="isBankConfirmed" class="rounded text-[#c3122e] focus:ring-[#c3122e] w-4 h-4">
                                <span class="text-xs font-bold text-slate-800">🏦 Is Bank Confirmed Date</span>
                            </label>

                            <!-- Bank Confirmed Date Input (Mandatory when checked) -->
                            <div x-show="isBankConfirmed" class="pt-2 border-t border-slate-200">
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1.5">Bank Confirmed Date <span class="text-red-500">*</span></label>
                                <input type="date" name="bank_confirmed_date" value="{{ $loan->bank_confirmed_date?->format('Y-m-d') }}" :required="mode === 'revise_iml' && isBankConfirmed" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-medium focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            </div>
                        </div>
                    </div>

                    <!-- Mode 2: Settle Loan Fields -->
                    <div x-show="mode === 'settle_loan'" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1.5">Settlement Option <span class="text-red-500">*</span></label>
                            <select name="settlement_type" x-model="settlementType" :required="mode === 'settle_loan'" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white font-medium focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                                <option value="all">All (Full Settlement - LKR {{ number_format($loan->outstanding_amount, 2) }})</option>
                                <option value="partial">Partially (Partial Settlement)</option>
                            </select>
                        </div>

                        <!-- Amount for Partial Settlement -->
                        <div x-show="settlementType === 'partial'">
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1.5">Settlement Amount (LKR) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="{{ $loan->outstanding_amount }}" name="settled_amount" placeholder="Enter partial amount..." :required="mode === 'settle_loan' && settlementType === 'partial'" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono text-emerald-600 font-bold focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        </div>

                        <!-- Settle Using New Loan Toggle / Checkbox -->
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="settle_using_new_loan" value="1" x-model="settleUsingNewLoan" class="rounded text-[#c3122e] focus:ring-[#c3122e] w-4 h-4">
                                <span class="text-xs font-bold text-slate-800">🔄 Settled Using New Loan / Facility</span>
                            </label>

                            <!-- Select Other Available Loan Dropdown -->
                            <div x-show="settleUsingNewLoan" class="pt-2 border-t border-slate-200">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Select Replacement / New Loan Facility</label>
                                <select name="settled_via_loan_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white font-medium">
                                    <option value="">— Select Replacement Loan —</option>
                                    @foreach($loans->where('id', '!=', $loan->id) as $otherLoan)
                                        <option value="{{ $otherLoan->id }}">{{ $otherLoan->bank->name ?? 'Bank' }} - {{ $otherLoan->facility_type }} (LKR {{ number_format($otherLoan->facility_amount) }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Mandatory Revision / Settlement Notes (Both Sides) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                            Revision / Settlement Notes <span class="text-red-500">*</span>
                        </label>
                        <textarea name="revision_notes" rows="2" required placeholder="Mandatory notes describing rate change, bank confirmation, or settlement details..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]"></textarea>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button type="button" @click.prevent="revisingId = null" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition-colors">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-bold shadow-sm transition-colors flex items-center gap-1.5">
                            <span x-text="mode === 'revise_iml' ? 'Save Revision' : 'Confirm Settlement'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

</div>
@endsection
