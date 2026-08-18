{{--
    resources/views/livewire/tenant/[slug]/fixed_deposits.blade.php
    Controller: App\Http\Controllers\Tenant\FixedDepositController
--}}
@extends('layouts.portal')
@section('header', 'Fixed Deposits Portfolio')

@section('content')
<div x-data="{ addingRow: false, revisingId: null }">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-[#0f172a] tracking-tight">Fixed Deposits Portfolio</h1>
            <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
                <span>{{ $company->name }}</span>
                <span>•</span>
                <span>Term deposit placements, maturity alerts &amp; renewal instructions</span>
            </p>
        </div>
        <button type="button" @click="addingRow = !addingRow"
            class="px-4 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-semibold transition-colors shadow-sm shadow-[#c3122e]/20 flex items-center gap-2 self-start sm:self-auto cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span x-text="addingRow ? 'Cancel Entry' : '+ Add Fixed Deposit'"></span>
        </button>
    </div>

    @if(session('success'))
        <div class="mb-5 p-3.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Datalists for Autocomplete --}}
    <datalist id="fd-renewal-options">
        @foreach($existingRenewalInstructions as $ri)<option value="{{ $ri }}"></option>@endforeach
    </datalist>

    {{-- KPI Header Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Placed FDs</p>
            <p class="text-2xl font-bold text-[#0f172a]">{{ $deposits->count() }} Placements</p>
            <p class="text-xs text-slate-400 mt-1">Across {{ $deposits->pluck('bank_id')->unique()->count() }} banking institutes</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Principal Invested</p>
            <p class="text-2xl font-bold text-[#0f172a]">LKR {{ number_format($totalAmount, 0) }}</p>
            <p class="text-xs text-slate-400 mt-1">Active fixed deposit portfolio</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider mb-1">Est. Monthly Profit</p>
            <p class="text-2xl font-bold text-emerald-600">+ LKR {{ number_format($totalMonthlyProfit, 0) }}</p>
            <p class="text-xs text-slate-400 mt-1">Estimated yield return</p>
        </div>
    </div>

    {{-- Invisible Store Form --}}
    <form id="fd-new-row" method="POST" action="{{ route('tenant.fixed-deposits.store', ['company_slug' => $company->slug]) }}">
        @csrf
    </form>

    {{-- Main Fixed Deposits Table Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-slate-100 bg-[#f8fafc] flex items-center justify-between">
            <div>
                <h2 class="font-bold text-[#0f172a] text-base">Fixed Deposit Placements</h2>
                <p class="text-xs text-slate-400">Term deposits, rates, maturity schedules &amp; renewal instructions</p>
            </div>
            <span class="text-xs font-semibold text-[#c3122e] bg-[#fdf2f4] px-3 py-1 rounded-full border border-[#f8d7da]">
                {{ $deposits->count() }} Active FDs
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-[#f1f5f9] text-slate-600 font-bold uppercase tracking-wider">
                        <th class="px-3 py-3 text-left">Bank / Institute</th>
                        <th class="px-3 py-3 text-right">Amount Rs./FCY</th>
                        <th class="px-3 py-3 text-center">CCY</th>
                        <th class="px-3 py-3 text-center">Commencement</th>
                        <th class="px-3 py-3 text-center">Maturity Date</th>
                        <th class="px-3 py-3 text-center">Tenor</th>
                        <th class="px-3 py-3 text-right">Rate %</th>
                        <th class="px-3 py-3 text-left">Renewal Instructions</th>
                        <th class="px-3 py-3 text-left">Pledged Details</th>
                        <th class="px-3 py-3 text-right text-emerald-700">Est. Monthly Profit</th>
                        <th class="px-3 py-3 text-center">History</th>
                        <th class="px-3 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($deposits as $fd)
                        @php
                            $daysLeft = $fd->maturity_date ? (int) now()->diffInDays($fd->maturity_date, false) : null;
                            $isMatured = $daysLeft !== null && $daysLeft < 0;
                            $isDueSoon = $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 7;
                        @endphp
                        <tr class="transition-colors {{ $isMatured ? 'bg-red-50/70 border-l-4 border-l-red-500' : ($isDueSoon ? 'bg-amber-50/70 border-l-4 border-l-amber-500' : 'hover:bg-[#fdf2f4]/30') }}">
                            <td class="px-3 py-3 font-medium text-[#0f172a]">
                                <span class="inline-block px-1.5 py-0.5 rounded bg-[#fdf2f4] text-[#c3122e] font-bold font-mono text-[10px] border border-[#f8d7da]">{{ $fd->bank->bank_code ?? '—' }}</span>
                                <span class="ml-1 font-semibold">{{ $fd->bank->name ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-3 text-right font-mono font-bold text-slate-800 text-sm">
                                {{ number_format($fd->amount, 2) }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $fd->currency === 'USD' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600' }}">{{ $fd->currency }}</span>
                            </td>
                            <td class="px-3 py-3 text-center font-mono text-slate-600">{{ $fd->commencement_date ? $fd->commencement_date->format('d/m/Y') : '—' }}</td>
                            <td class="px-3 py-3 text-center font-mono text-slate-600">
                                {{ $fd->maturity_date ? $fd->maturity_date->format('d/m/Y') : '—' }}
                                @if($isMatured)
                                    <span class="block text-[9px] font-bold text-red-600 uppercase">Matured ({{ abs($daysLeft) }}d ago)</span>
                                @elseif($isDueSoon)
                                    <span class="block text-[9px] font-bold text-amber-600 uppercase">Due in {{ $daysLeft }}d</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center font-mono font-bold text-slate-700">{{ $fd->tenor_display ?? ($fd->tenor ? $fd->tenor.' M' : '—') }}</td>
                            <td class="px-3 py-3 text-right font-bold text-[#c3122e] text-sm">{{ number_format($fd->interest_rate, 3) }}%</td>
                            <td class="px-3 py-3 text-slate-700">
                                @if($fd->renewal_instructions)
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-medium text-[10px]">{{ $fd->renewal_instructions }}</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-slate-600 text-[11px]">
                                {{ $fd->pledged_details ?: '—' }}
                            </td>
                            <td class="px-3 py-3 text-right font-mono font-bold text-emerald-700 text-xs">
                                + {{ number_format($fd->monthly_profit, 2) }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($fd->history_records->count() > 0)
                                    <span class="px-2 py-0.5 rounded bg-slate-800 text-white font-bold text-[10px]">
                                        📜 {{ $fd->history_records->count() }}
                                    </span>
                                @else
                                    <span class="text-slate-300 text-[10px]">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center flex items-center justify-center gap-1.5">
                                <button type="button" @click="revisingId = {{ $fd->id }}"
                                    class="p-1.5 rounded-lg bg-[#fdf2f4] hover:bg-[#c3122e] text-[#c3122e] hover:text-white transition-colors border border-[#f8d7da] cursor-pointer"
                                    title="Withdraw or Renew FD">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <form method="POST" action="{{ route('tenant.fixed-deposits.destroy', ['company_slug' => $company->slug, 'fixedDeposit' => $fd->id]) }}" onsubmit="return confirm('Delete this deposit permanently?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer" title="Delete FD">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr x-show="!addingRow">
                            <td colspan="12" class="px-6 py-10 text-center text-slate-300 text-xs">No Fixed Deposits recorded yet. Click <strong class="text-[#c3122e]">+ Add Fixed Deposit</strong> to create one.</td>
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
                        <td class="px-2 py-2"><input type="date" name="commencement_date" form="fd-new-row" value="{{ date('Y-m-d') }}" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs"></td>
                        <td class="px-2 py-2"><input type="date" name="maturity_date" form="fd-new-row" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs"></td>
                        <td class="px-2 py-2 text-center text-slate-400 text-[10px] italic">auto</td>
                        <td class="px-2 py-2"><input type="number" name="interest_rate" form="fd-new-row" required step="0.001" min="0" max="100" placeholder="0.000" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs font-mono text-right"></td>
                        <td class="px-2 py-2"><input type="text" name="renewal_instructions" list="fd-renewal-options" form="fd-new-row" placeholder="Instructions..." class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs"></td>
                        <td class="px-2 py-2"><input type="text" name="pledged_details" form="fd-new-row" placeholder="Pledged to..." class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs"></td>
                        <td class="px-2 py-2 font-mono font-bold text-emerald-700 text-right text-[10px]">—</td>
                        <td class="px-2 py-2 text-center text-slate-400">
                            <input type="hidden" name="entry_date" form="fd-new-row" value="{{ date('Y-m-d') }}">
                            <span class="text-[10px]">New</span>
                        </td>
                        <td class="px-2 py-2 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button type="submit" form="fd-new-row" class="px-2.5 py-1 rounded-lg bg-[#c3122e] hover:bg-[#9e0e24] text-white text-[11px] font-bold shadow-sm cursor-pointer">
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

    {{-- Withdrawal & Renewal Popup Modal --}}
    @foreach($deposits as $fd)
        <div x-show="revisingId === {{ $fd->id }}"
             x-cloak
             @click.self="revisingId = null"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto"
             x-data="{ mode: 'renew', withdrawalType: 'all' }">
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg p-6 relative my-6">
                
                <div class="flex items-center justify-between mb-4 border-b pb-3 border-slate-100">
                    <div>
                        <h3 class="font-bold text-[#0f172a] text-sm">Manage Fixed Deposit</h3>
                        <p class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $fd->bank->name ?? 'Bank' }} | Amount: LKR {{ number_format($fd->amount, 2) }}</p>
                    </div>
                    <button type="button" @click="revisingId = null" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
                </div>

                <div class="grid grid-cols-2 gap-2 p-1 rounded-xl bg-slate-100 mb-5">
                    <button type="button" @click="mode = 'renew'"
                        :class="mode === 'renew' ? 'bg-white text-[#c3122e] shadow-sm font-bold border border-slate-200' : 'text-slate-600 font-medium'"
                        class="py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span>Renew / Update Terms</span>
                    </button>
                    <button type="button" @click="mode = 'withdraw'"
                        :class="mode === 'withdraw' ? 'bg-[#c3122e] text-white shadow-sm font-bold' : 'text-slate-600 font-medium'"
                        class="py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span>Withdraw / Liquidate</span>
                    </button>
                </div>

                <form method="POST" action="{{ route('tenant.fixed-deposits.update-rate', ['company_slug' => $company->slug, 'fixedDeposit' => $fd->id]) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="action_type" :value="mode">

                    <div x-show="mode === 'renew'" class="space-y-3.5">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">New Rate (%) *</label>
                                <input type="number" step="0.001" min="0" max="100" name="new_interest_rate" value="{{ $fd->interest_rate }}"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-mono font-bold focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">New Maturity Date</label>
                                <input type="date" name="maturity_date" value="{{ $fd->maturity_date ? $fd->maturity_date->format('Y-m-d') : '' }}"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Renewal Instructions</label>
                            <input type="text" name="renewal_instructions" list="fd-renewal-options" value="{{ $fd->renewal_instructions }}"
                                placeholder="Instructions..."
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Pledged Details (if any)</label>
                            <input type="text" name="pledged_details" value="{{ $fd->pledged_details }}"
                                placeholder="Details..."
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                        </div>
                    </div>

                    <div x-show="mode === 'withdraw'" class="space-y-3.5">
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer"
                                   :class="withdrawalType === 'all' ? 'border-[#c3122e] bg-[#fdf2f4] text-[#c3122e] font-bold' : 'border-slate-200 text-slate-600'">
                                <input type="radio" name="withdrawal_type" value="all" x-model="withdrawalType" class="hidden">
                                <span>Full Liquidation (100%)</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer"
                                   :class="withdrawalType === 'partial' ? 'border-[#c3122e] bg-[#fdf2f4] text-[#c3122e] font-bold' : 'border-slate-200 text-slate-600'">
                                <input type="radio" name="withdrawal_type" value="partial" x-model="withdrawalType" class="hidden">
                                <span>Partial Withdrawal</span>
                            </label>
                        </div>
                        <div x-show="withdrawalType === 'partial'">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Withdrawn Amount (LKR) *</label>
                            <input type="number" step="1000" min="1" max="{{ $fd->amount }}" name="withdrawn_amount"
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
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Reason / Notes</label>
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