{{--
    resources/views/livewire/tenant/health/fixed_deposits.blade.php
    Controller: App\Http\Controllers\Tenant\FixedDepositController
--}}
@extends('layouts.portal')
@section('header', 'Fixed Deposits')

@section('content')
<div x-data="{ addingRow: false, revisingId: null }">

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-bold text-[#0f172a]">Fixed Deposit Portfolio</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $company->name }} · Fixed Deposits &amp; Interest Yield Portfolio</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- KPI Cards with Color Warning Counts --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-5">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Fixed Deposits</p>
            <p class="text-xl font-bold text-[#0f172a]">LKR {{ number_format($totalAmount, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-100 bg-emerald-50/50 shadow-sm p-5">
            <p class="text-xs font-semibold text-emerald-800 uppercase tracking-wider mb-1">Est. Monthly Profit</p>
            <p class="text-xl font-bold text-emerald-700">LKR {{ number_format($totalMonthlyProfit, 0) }} / mo</p>
        </div>
        <div class="bg-white rounded-2xl border {{ $urgentFinalWeek > 0 ? 'border-red-300 bg-red-50/80 ring-2 ring-red-400/30' : 'border-slate-100 bg-white' }} shadow-sm p-5">
            <p class="text-xs font-semibold {{ $urgentFinalWeek > 0 ? 'text-red-700 font-bold' : 'text-slate-400' }} uppercase tracking-wider mb-1">🚨 Final Week (≤ 7 Days)</p>
            <p class="text-xl font-bold {{ $urgentFinalWeek > 0 ? 'text-red-700' : 'text-slate-700' }}">{{ $urgentFinalWeek }} FDs</p>
        </div>
        <div class="bg-white rounded-2xl border {{ $maturingSoon > 0 ? 'border-amber-200 bg-amber-50/80' : 'border-slate-100 bg-white' }} shadow-sm p-5">
            <p class="text-xs font-semibold {{ $maturingSoon > 0 ? 'text-amber-700 font-bold' : 'text-slate-400' }} uppercase tracking-wider mb-1">🗓️ Maturing ≤ 30 Days</p>
            <p class="text-xl font-bold {{ $maturingSoon > 0 ? 'text-amber-800' : 'text-slate-700' }}">{{ $maturingSoon }} FDs</p>
        </div>
        <div class="bg-white rounded-2xl border {{ $alreadyMatured > 0 ? 'border-red-200 bg-red-100/50' : 'border-slate-100 bg-white' }} shadow-sm p-5">
            <p class="text-xs font-semibold {{ $alreadyMatured > 0 ? 'text-red-700' : 'text-slate-400' }} uppercase tracking-wider mb-1">Already Matured</p>
            <p class="text-xl font-bold {{ $alreadyMatured > 0 ? 'text-red-700' : 'text-slate-700' }}">{{ $alreadyMatured }} FDs</p>
        </div>
    </div>

    {{-- Hidden New Entry Form --}}
    <form id="fd-new-row" method="POST" action="{{ route('tenant.fixed-deposits.store', ['company_slug' => $company->slug]) }}">@csrf</form>

    <datalist id="fd-renewal-options">@foreach($existingRenewalInstructions as $instr)<option value="{{ $instr }}">@endforeach</datalist>

    {{-- Main Table Card --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-[#0f172a] text-sm">Fixed Deposit Portfolio</h2>
                <p class="text-xs text-slate-400 mt-0.5">{{ $deposits->count() }} active deposits · Yellow = ≤ 30d maturity · Red = Final Week (≤ 7d)</p>
            </div>
            <button type="button" @click="addingRow = true" x-show="!addingRow"
                class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-bold transition-colors shadow-sm cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Fixed Deposit
            </button>
            <div x-show="addingRow" class="flex gap-2">
                <button type="submit" form="fd-new-row" class="px-3.5 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white text-xs font-bold">✓ Save</button>
                <button type="button" @click="addingRow = false" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold">✕ Cancel</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-[#f8fafc] text-slate-500 text-[11px]">
                        <th class="px-3 py-2.5 text-left font-semibold uppercase tracking-wider min-w-[100px]">Bank / Institute</th>
                        <th class="px-3 py-2.5 text-right font-semibold uppercase tracking-wider min-w-[100px]">FD Amount</th>
                        <th class="px-3 py-2.5 text-center font-semibold uppercase tracking-wider min-w-[50px]">CCY</th>
                        <th class="px-3 py-2.5 text-left font-semibold uppercase tracking-wider min-w-[90px]">Commenced</th>
                        <th class="px-3 py-2.5 text-left font-semibold uppercase tracking-wider min-w-[95px]">Maturity</th>
                        <th class="px-3 py-2.5 text-left font-semibold uppercase tracking-wider min-w-[80px]">Tenor</th>
                        <th class="px-3 py-2.5 text-right font-semibold uppercase tracking-wider min-w-[65px]">Rate %</th>
                        <th class="px-3 py-2.5 text-right font-semibold uppercase tracking-wider min-w-[110px] text-emerald-700">Est. Monthly Profit</th>
                        <th class="px-3 py-2.5 text-center font-semibold uppercase tracking-wider min-w-[90px]">History</th>
                        <th class="px-3 py-2.5 text-center font-semibold uppercase tracking-wider min-w-[80px]">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50" x-data="{ openHistoryId: null }">
                    @forelse($deposits as $fd)
                        @php
                            $days = $fd->maturity_date ? now()->diffInDays($fd->maturity_date, false) : null;
                            $isMatured = $days !== null && $days < 0;
                            $isUrgent  = $days !== null && $days >= 0 && $days <= 7;
                            $isSoon    = $days !== null && $days > 7 && $days <= 30;
                            $isWithdrawn = ($fd->amount == 0) || ($fd->action_type === 'withdrawal' && $fd->withdrawal_type === 'all');
                        @endphp
                        <tr class="transition-colors
                            {{ $isWithdrawn ? 'bg-emerald-50/80 border-l-4 border-l-emerald-500 hover:bg-emerald-100/60' : ($isMatured ? 'bg-red-100/60 border-l-4 border-l-red-600' : ($isUrgent ? 'bg-red-500/10 border-l-4 border-l-red-500' : ($isSoon ? 'bg-amber-100/60 border-l-4 border-l-amber-500' : 'hover:bg-[#fdf2f4]/30'))) }}">

                            <td class="px-3 py-3 font-medium text-[#0f172a]">
                                <span class="inline-block px-1.5 py-0.5 rounded bg-[#fdf2f4] text-[#c3122e] font-bold font-mono text-[10px] border border-[#f8d7da]">{{ $fd->bank->bank_code ?? '—' }}</span>
                                <span class="ml-1 font-semibold">{{ $fd->bank->name ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-3 text-right font-mono font-bold text-[#0f172a] text-sm">
                                @if($isWithdrawn)
                                    <span class="text-emerald-700 font-bold">LKR 0.00</span>
                                @else
                                    {{ number_format($fd->amount, 0) }}
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $fd->currency === 'USD' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600' }}">{{ $fd->currency }}</span>
                            </td>
                            <td class="px-3 py-3 text-slate-600">{{ $fd->commencement_date?->format('d M Y') ?? '—' }}</td>
                            <td class="px-3 py-3 font-semibold {{ $isWithdrawn ? 'text-emerald-800' : ($isMatured ? 'text-red-700' : ($isUrgent ? 'text-red-600 font-extrabold' : ($isSoon ? 'text-amber-800' : 'text-slate-700'))) }}">
                                {{ $fd->maturity_date?->format('d M Y') ?? '—' }}
                            </td>
                            <td class="px-3 py-3 font-mono text-slate-600">{{ $fd->auto_tenor }}</td>
                            <td class="px-3 py-3 text-right font-bold text-[#c3122e] text-sm">{{ number_format($fd->interest_rate, 3) }}%</td>
                            
                            {{-- Monthly Profit Calculation Column --}}
                            <td class="px-3 py-3 text-right font-mono font-bold text-emerald-700 bg-emerald-50/30">
                                @if($isWithdrawn)
                                    <span class="text-slate-400 font-normal">LKR 0.00</span>
                                @else
                                    + {{ number_format($fd->monthly_profit, 2) }}
                                @endif
                            </td>

                            <td class="px-3 py-3 text-center">
                                @if($fd->history_records->count() > 0)
                                    <button type="button" @click="openHistoryId = (openHistoryId === {{ $fd->id }} ? null : {{ $fd->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-bold text-[10px] transition-colors shadow-sm cursor-pointer">
                                        📜 History ({{ $fd->history_records->count() }})
                                    </button>
                                @else
                                    <span class="text-slate-300 text-[10px]">No revisions</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center flex items-center justify-center gap-1.5">
                                {{-- Icon Only Edit / Withdraw Button --}}
                                <button type="button" @click="revisingId = {{ $fd->id }}"
                                    class="p-1.5 rounded-lg bg-[#fdf2f4] hover:bg-[#c3122e] text-[#c3122e] hover:text-white transition-colors border border-[#f8d7da] cursor-pointer"
                                    title="Withdrawal &amp; Renewal">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <form method="POST" action="{{ route('tenant.fixed-deposits.destroy', ['company_slug' => $company->slug, 'fixedDeposit' => $fd->id]) }}" onsubmit="return confirm('Delete this deposit entry?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Delete Fixed Deposit"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                </form>
                            </td>
                        </tr>

                        {{-- History Drawer Sub-Row --}}
                        @if($fd->history_records->count() > 0)
                        <tr x-show="openHistoryId === {{ $fd->id }}" x-cloak class="bg-slate-100/90 border-y border-slate-300">
                            <td colspan="10" class="p-4">
                                <div class="text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center justify-between border-b border-slate-300 pb-2">
                                    <span>📜 Complete Version, Withdrawal &amp; Renewal History for Fixed Deposit at {{ $fd->bank->name }} (Current: Version {{ $fd->version }})</span>
                                    <span class="text-[10px] text-slate-500 font-normal">{{ $fd->history_records->count() }} archived revisions</span>
                                </div>
                                <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                                    @foreach($fd->history_records as $hist)
                                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3.5 text-xs text-slate-600 space-y-2.5">
                                            {{-- Version & Timestamp Bar --}}
                                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-0.5 rounded bg-slate-800 text-white font-mono font-bold text-[10px]">Version {{ $hist->version }}</span>
                                                    <span class="text-slate-700 font-semibold">🕒 {{ $hist->created_at->format('d M Y, h:i:s A') }}</span>
                                                    <span class="text-slate-400">· Modified by <strong class="text-slate-700">{{ $hist->user->name ?? 'User' }}</strong></span>
                                                </div>
                                                @if($hist->action_type === 'withdrawal')
                                                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                                                        FD Withdrawal ({{ strtoupper($hist->withdrawal_type ?? 'ALL') }}: LKR {{ number_format($hist->withdrawn_amount ?? $hist->amount, 2) }})
                                                    </span>
                                                @endif
                                            </div>
                                            @if($hist->revision_notes)
                                                <div class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 font-semibold text-[11px] flex items-center gap-1">
                                                    📝 Revision / Withdrawal Notes: "{{ $hist->revision_notes }}"
                                                </div>
                                            @endif

                                            {{-- Snapshot Attributes Grid --}}
                                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 font-mono text-[11px]">
                                                <div class="bg-slate-50 p-2 rounded-lg border border-slate-100">
                                                    <span class="text-[10px] font-sans text-slate-400 block uppercase font-semibold">Interest Rate</span>
                                                    <span class="font-bold text-[#c3122e] text-xs">{{ number_format($hist->interest_rate, 3) }}%</span>
                                                </div>
                                                <div class="bg-slate-50 p-2 rounded-lg border border-slate-100">
                                                    <span class="text-[10px] font-sans text-slate-400 block uppercase font-semibold">FD Principal</span>
                                                    <span class="font-bold text-slate-800 text-xs">LKR {{ number_format($hist->amount, 0) }}</span>
                                                </div>
                                                <div class="bg-slate-50 p-2 rounded-lg border border-slate-100">
                                                    <span class="text-[10px] font-sans text-slate-400 block uppercase font-semibold">Monthly Profit</span>
                                                    <span class="font-bold text-emerald-700 text-xs">LKR {{ number_format($hist->monthly_profit, 2) }}</span>
                                                </div>
                                                <div class="bg-slate-50 p-2 rounded-lg border border-slate-100">
                                                    <span class="text-[10px] font-sans text-slate-400 block uppercase font-semibold">Maturity Date</span>
                                                    <span class="font-semibold text-slate-700">{{ $hist->maturity_date?->format('d M Y') ?? '—' }}</span>
                                                </div>
                                                <div class="bg-slate-50 p-2 rounded-lg border border-slate-100">
                                                    <span class="text-[10px] font-sans text-slate-400 block uppercase font-semibold">Renewal Instruction</span>
                                                    <span class="font-semibold text-slate-700 font-sans truncate block" title="{{ $hist->renewal_instructions }}">{{ $hist->renewal_instructions ?? '—' }}</span>
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
                            <td colspan="10" class="px-6 py-10 text-center text-slate-300 text-xs">No Fixed Deposits recorded yet. Click <strong class="text-[#c3122e]">+ Add Fixed Deposit</strong> to create one.</td>
                        </tr>
                    @endforelse

                    {{-- Add New Inline Row --}}
                    <tr x-show="addingRow" class="bg-[#fffbf5] border-t-2 border-[#c3122e]/20">
                        <td class="px-2 py-2">
                            <select name="bank_id" form="fd-new-row" required class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs bg-white">
                                <option value="">— Bank —</option>
                                @foreach($banks as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                            </select>
                        </td>
                        <td class="px-2 py-2"><input type="number" name="amount" form="fd-new-row" required step="1000" min="0" placeholder="0" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs font-mono text-right"></td>
                        <td class="px-2 py-2">
                            <select name="currency" form="fd-new-row" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs bg-white"><option value="LKR">LKR</option><option value="USD">USD</option></select>
                        </td>
                        <td class="px-2 py-2"><input type="date" name="commencement_date" form="fd-new-row" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs"></td>
                        <td class="px-2 py-2"><input type="date" name="maturity_date" form="fd-new-row" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs"></td>
                        <td class="px-2 py-2 text-center text-slate-400 text-[10px] italic">auto</td>
                        <td class="px-2 py-2"><input type="number" name="interest_rate" form="fd-new-row" required step="0.001" min="0" max="100" placeholder="0.000" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs font-mono text-right"></td>
                        <td class="px-2 py-2 font-mono font-bold text-emerald-700 text-right">—</td>
                        <td colspan="2" class="px-2 py-2">
                            <input type="date" name="entry_date" form="fd-new-row" required value="{{ date('Y-m-d') }}" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Withdrawal & Renewal Popup Modal --}}
    @foreach($deposits as $fd)
        <div x-show="revisingId === {{ $fd->id }}"
             x-cloak
             @click.self="revisingId = null"
             @keydown.escape.window="revisingId = null"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto"
             x-data="{ mode: 'withdrawal', withdrawalType: 'all' }">
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg p-6 relative overflow-hidden my-6">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-4 border-b pb-3 border-slate-100">
                    <div>
                        <h3 class="font-bold text-[#0f172a] text-sm">FD Withdrawal &amp; Renewal: {{ $fd->bank->name ?? 'Bank' }}</h3>
                        <p class="text-[11px] text-slate-400 font-mono mt-0.5">FD Amount: LKR {{ number_format($fd->amount, 2) }} | Rate: {{ number_format($fd->interest_rate, 3) }}%</p>
                    </div>
                    <button type="button" @click.prevent="revisingId = null" class="text-slate-400 hover:text-slate-600 p-1 text-base leading-none font-bold">✕</button>
                </div>

                <!-- 2 Top Mode Switcher Buttons: Withdrawal and Renew / Revise -->
                <div class="grid grid-cols-2 gap-2 p-1 rounded-xl bg-slate-100 mb-5">
                    <button type="button"
                        @click="mode = 'withdrawal'"
                        :class="mode === 'withdrawal' ? 'bg-[#c3122e] text-white shadow-sm font-bold' : 'text-slate-600 font-medium hover:text-slate-900'"
                        class="py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Withdrawal</span>
                    </button>
                    <button type="button"
                        @click="mode = 'renew_revise'"
                        :class="mode === 'renew_revise' ? 'bg-white text-[#c3122e] shadow-sm font-bold border border-slate-200' : 'text-slate-600 font-medium hover:text-slate-900'"
                        class="py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Renew / Revise</span>
                    </button>
                </div>

                <form method="POST" action="{{ route('tenant.fixed-deposits.update-rate', ['company_slug' => $company->slug, 'fixedDeposit' => $fd->id]) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="action_type" :value="mode">

                    <!-- Mode 1: Withdrawal Fields -->
                    <div x-show="mode === 'withdrawal'" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1.5">Withdrawal Option <span class="text-red-500">*</span></label>
                            <select name="withdrawal_type" x-model="withdrawalType" :required="mode === 'withdrawal'" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white font-medium focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                                <option value="all">Withdraw All (Full Withdrawal - LKR {{ number_format($fd->amount, 2) }})</option>
                                <option value="partial">Partially (Partial Withdrawal)</option>
                            </select>
                        </div>

                        <!-- Amount for Partial Withdrawal -->
                        <div x-show="withdrawalType === 'partial'">
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1.5">Withdrawal Amount (LKR) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="{{ $fd->amount }}" name="withdrawn_amount" placeholder="Enter partial withdrawal amount..." :required="mode === 'withdrawal' && withdrawalType === 'partial'" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono text-emerald-600 font-bold focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        </div>
                    </div>

                    <!-- Mode 2: Renew / Revise Fields -->
                    <div x-show="mode === 'renew_revise'" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1.5">Bank / Financial Institution <span class="text-red-500">*</span></label>
                            <select name="bank_id" :required="mode === 'renew_revise'" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm bg-white font-medium">
                                @foreach($banks as $b)
                                    <option value="{{ $b->id }}" {{ $b->id === $fd->bank_id ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1.5">FD Amount (Principal) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" name="amount" value="{{ $fd->amount }}" :required="mode === 'renew_revise'" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1.5">New Interest Rate (%) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.001" name="interest_rate" value="{{ $fd->interest_rate }}" :required="mode === 'renew_revise'" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono text-[#c3122e] font-bold">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1.5">Renewed Maturity Date</label>
                            <input type="date" name="maturity_date" value="{{ $fd->maturity_date?->format('Y-m-d') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1.5">Renewal Instructions</label>
                            <input type="text" name="renewal_instructions" value="{{ $fd->renewal_instructions }}" list="fd-renewal-options" placeholder="e.g. Renew Principal + Interest..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
                        </div>
                    </div>

                    <!-- Mandatory Revision / Withdrawal Notes (Both Sides) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                            Revision / Withdrawal Notes <span class="text-red-500">*</span>
                        </label>
                        <textarea name="revision_notes" rows="2" required placeholder="Mandatory notes describing withdrawal or renewal details..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]"></textarea>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button type="button" @click.prevent="revisingId = null" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition-colors">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-bold shadow-sm transition-colors flex items-center gap-1.5">
                            <span x-text="mode === 'withdrawal' ? 'Confirm Withdrawal' : 'Save Renewal'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

</div>
@endsection
