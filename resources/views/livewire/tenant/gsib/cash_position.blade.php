{{--
    resources/views/livewire/tenant/[slug]/cash_position.blade.php
    Controller: App\Http\Controllers\Tenant\CashPositionController
--}}
@extends('layouts.portal')
@section('header', 'Daily Group Cash Position Report')

@section('content')
<div x-data="{
    activeTab: 'daily_position',
    showAddAccountModal: false,
    showEntryModal: false,
    showMovementModal: false,
    selectedAccountId: '',
    selectedAccountDisplay: '',
    openingBal: '0.00',
    cashIn: '0.00',
    cashOut: '0.00',
    restrictedCash: '0.00',
    remarks: '',
    entryDate: '{{ $selectedDate }}',
}">

    {{-- Top Alert Messages --}}
    @if(session('success'))
        <div class="mb-5 p-3.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-medium">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Header Banner & Action Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-[#0f172a] tracking-tight">Daily Group Cash Position</h1>
            <p class="text-xs text-slate-500 mt-1 flex items-center gap-2">
                <span>{{ $company->name }}</span>
                <span>•</span>
                <span>Immediate visibility of available liquidity &amp; banking operations</span>
            </p>
        </div>

        {{-- Date Picker & Quick Actions --}}
        <div class="flex flex-wrap items-center gap-2.5">
            <form method="GET" action="{{ route('tenant.cash-position', ['company_slug' => $company->slug]) }}" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ $selectedDate }}"
                    onchange="this.form.submit()"
                    class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]">
            </form>

            <button type="button" @click="showMovementModal = true"
                class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-black text-white text-xs font-bold transition-all shadow-sm flex items-center gap-2 cursor-pointer border border-slate-800">
                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Record Transfer</span>
            </button>

            <button type="button" @click="showAddAccountModal = true"
                class="px-4 py-2 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-bold transition-all shadow-md shadow-[#c3122e]/25 flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>+ Add Bank Account</span>
            </button>
        </div>
    </div>

    {{-- Top 4 KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Opening Balance</p>
            <p class="text-xl font-extrabold text-slate-700">LKR {{ number_format($totalOpening, 2) }}</p>
            <p class="text-[10px] text-slate-400 mt-1">As of {{ date('d M Y', strtotime($selectedDate)) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
            <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider mb-1">Today's Inflows</p>
            <p class="text-xl font-extrabold text-emerald-600">+ LKR {{ number_format($totalInflows, 2) }}</p>
            <p class="text-[10px] text-emerald-500 mt-1">Receipts &amp; transfers</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
            <p class="text-[10px] font-bold text-red-500 uppercase tracking-wider mb-1">Today's Outflows</p>
            <p class="text-xl font-extrabold text-red-600">- LKR {{ number_format($totalOutflows, 2) }}</p>
            <p class="text-[10px] text-red-400 mt-1">Payments &amp; transfers</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 bg-gradient-to-br from-blue-50/50 to-indigo-50/30">
            <p class="text-[10px] font-bold text-blue-700 uppercase tracking-wider mb-1">Net Available Cash</p>
            <p class="text-xl font-extrabold text-blue-800">LKR {{ number_format($totalAvailable, 2) }}</p>
            <p class="text-[10px] text-blue-600 font-mono mt-1">USD ≈ ${{ number_format($totalUsdClosing, 2) }}</p>
        </div>
    </div>

    {{-- Main Cash Position Table Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-slate-100 bg-[#f8fafc] flex items-center justify-between">
            <div>
                <h2 class="font-bold text-[#0f172a] text-base">Bank Account Balances &amp; Daily Liquidity</h2>
                <p class="text-xs text-slate-400">Position as of {{ date('l, d F Y', strtotime($selectedDate)) }}</p>
            </div>
            <button type="button" @click="showAddAccountModal = true" class="text-xs text-[#c3122e] font-bold hover:underline">
                + Add Account
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-[#f1f5f9] text-slate-900 font-extrabold uppercase tracking-wider text-[11px]">
                        <th class="px-4 py-3.5 text-left text-slate-900">Bank</th>
                        <th class="px-4 py-3.5 text-left text-slate-900">Account Type</th>
                        <th class="px-4 py-3.5 text-left font-mono text-slate-900">Account #</th>
                        <th class="px-4 py-3.5 text-center text-slate-900">Currency</th>
                        <th class="px-4 py-3.5 text-right text-slate-900">Opening Balance</th>
                        <th class="px-4 py-3.5 text-right text-slate-900">Inflows</th>
                        <th class="px-4 py-3.5 text-right text-slate-900">Outflows</th>
                        <th class="px-4 py-3.5 text-right font-black text-slate-900 bg-slate-200/70 border-x border-slate-300">Closing Balance</th>
                        <th class="px-4 py-3.5 text-right text-slate-900">Restricted Cash</th>
                        <th class="px-4 py-3.5 text-right font-black text-slate-900 bg-slate-200/70 border-x border-slate-300">Available Cash</th>
                        <th class="px-4 py-3.5 text-right font-black text-slate-900 bg-slate-200/70 border-x border-slate-300 font-mono">USD Closing Balance</th>
                        <th class="px-4 py-3.5 text-center text-slate-900">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($bankAccounts as $acct)
                        @php
                            $entry = $latestEntries->get($acct->id);
                            $open  = $entry ? (float)$entry->opening_balance : 0.00;
                            $in    = $entry ? (float)$entry->cash_in : 0.00;
                            $out   = $entry ? (float)$entry->cash_out : 0.00;
                            $close = $entry ? (float)$entry->closing_balance : ($open + $in - $out);
                            $restr = $entry ? (float)$entry->restricted_cash : 0.00;
                            $avail = $close - $restr;

                            if (strtoupper($acct->currency) === 'USD') {
                                $usdClosing = $close;
                            } else {
                                $usdClosing = $usdExchangeRate > 0 ? ($close / $usdExchangeRate) : 0.00;
                            }
                        @endphp
                        @php
    $isNegativeOrZero = ($avail <= 0 && $open > 0);
    $hasRestricted = ($restr > 0);
@endphp
<tr class="transition-colors {{ $isNegativeOrZero ? 'bg-red-50/70 border-l-4 border-l-red-600 hover:bg-red-100/70' : ($hasRestricted ? 'bg-amber-50/40 border-l-4 border-l-amber-400 hover:bg-amber-50/70' : 'hover:bg-slate-50') }}">
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-900 text-white font-black text-xs font-mono tracking-wider shadow-sm">
                                    {{ $acct->bank->short_name ?: ($acct->bank->bank_code ?: $acct->bank->name) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-semibold text-[10px]">
                                    {{ $acct->account_type }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-mono font-bold text-slate-800 tracking-wider">
                                {{ $acct->account_number }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold
                                    {{ $acct->currency === 'USD' ? 'bg-blue-100 text-blue-700' : ($acct->currency === 'EUR' ? 'bg-purple-100 text-purple-700' : ($acct->currency === 'GBP' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-700')) }}">
                                    {{ $acct->currency }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono font-semibold text-slate-900">
                                {{ number_format($open, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono font-bold text-emerald-700">
                                {{ $in > 0 ? '+ '.number_format($in, 2) : '0.00' }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono font-black text-red-700">
                                {{ $out > 0 ? '- '.number_format($out, 2) : '0.00' }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono font-black text-slate-950 bg-slate-100/90 border-x border-slate-200">
                                {{ number_format($close, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono font-semibold text-slate-900">
                                {{ number_format($restr, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono font-black text-slate-950 bg-slate-100/90 border-x border-slate-200">
                                {{ number_format($avail, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono font-black text-slate-950 bg-slate-100/90 border-x border-slate-200">
                                ${{ number_format($usdClosing, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button"
                                        @click="selectedAccountId = {{ $acct->id }}; selectedAccountDisplay = '{{ addslashes($acct->bank->name ?? 'Bank') }} - {{ $acct->account_number }} ({{ $acct->currency }})'; openingBal = '{{ $open }}'; cashIn = '{{ $in }}'; cashOut = '{{ $out }}'; restrictedCash = '{{ $restr }}'; remarks = '{{ addslashes($entry->remarks ?? '') }}'; showEntryModal = true"
                                        class="px-2.5 py-1 rounded-lg bg-[#c3122e] hover:bg-[#9e0e24] text-white font-semibold text-[11px] transition-colors shadow-sm cursor-pointer">
                                        {{ $entry ? 'Update' : 'Enter' }}
                                    </button>

                                    @if($entry)
                                        <form method="POST" action="{{ route('tenant.cash-position.entry.destroy', ['company_slug' => $company->slug, 'entry' => $entry->id]) }}" onsubmit="return confirm('Reset / Delete today\'s recorded cash entry for account {{ $acct->account_number }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-1 rounded-lg text-amber-500 hover:text-amber-700 hover:bg-amber-50 transition-colors cursor-pointer" title="Delete / Reset Recorded Entry">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('tenant.cash-position.bank-account.destroy', ['company_slug' => $company->slug, 'bankAccount' => $acct->id]) }}" onsubmit="return confirm('Delete bank account {{ $acct->account_number }} and all its historical records?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer" title="Delete Bank Account">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-6 py-12 text-center text-slate-400 text-xs">
                                No bank accounts registered for {{ $company->name }} yet.<br>
                                Click <button type="button" @click="showAddAccountModal = true" class="text-[#c3122e] font-bold hover:underline">+ Add Bank Account</button> above to register your first bank account.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($bankAccounts->count() > 0)
                    <tfoot>
                        <tr class="bg-slate-900 text-white font-bold text-xs">
                            <td colspan="4" class="px-4 py-3 uppercase tracking-wider">Total Summary</td>
                            <td class="px-4 py-3 text-right font-mono">{{ number_format($totalOpening, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-emerald-400">+ {{ number_format($totalInflows, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-red-400">- {{ number_format($totalOutflows, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-amber-300">{{ number_format($totalClosing, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-amber-400">{{ number_format($totalRestricted, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-blue-300">{{ number_format($totalAvailable, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-purple-300">${{ number_format($totalUsdClosing, 2) }}</td>
                            <td class="px-4 py-3 text-center"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Modal 1: Enter / Update Daily Cash Entry --}}
    <div x-show="showEntryModal" x-cloak
         @click.self="showEntryModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-md p-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div>
                    <h3 class="font-bold text-[#0f172a] text-sm">Daily Cash Entry</h3>
                    <p class="text-[11px] text-slate-400" x-text="selectedAccountDisplay"></p>
                </div>
                <button type="button" @click="showEntryModal = false" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
            </div>

            <form method="POST" action="{{ route('tenant.cash-position.entry', ['company_slug' => $company->slug]) }}" class="space-y-3.5">
                @csrf
                <input type="hidden" name="company_bank_account_id" :value="selectedAccountId">
                <input type="hidden" name="entry_date" :value="entryDate">

                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Opening Balance</label>
                    <input type="number" step="0.01" name="opening_balance" x-model="openingBal" required
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono font-semibold focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-emerald-700 uppercase mb-1">Inflows (+)</label>
                        <input type="number" step="0.01" min="0" name="cash_in" x-model="cashIn" required
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-red-600 uppercase mb-1">Outflows (-)</label>
                        <input type="number" step="0.01" min="0" name="cash_out" x-model="cashOut" required
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono font-bold text-red-600 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-amber-700 uppercase mb-1">Restricted Cash</label>
                    <input type="number" step="0.01" min="0" name="restricted_cash" x-model="restrictedCash"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono font-semibold text-amber-700 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Remarks (Optional)</label>
                    <input type="text" name="remarks" x-model="remarks" placeholder="Notes..."
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showEntryModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-[#c3122e] text-white text-xs font-bold hover:bg-[#9e0e24] shadow-sm">Save Entry</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal 2: Add New Bank Account --}}
    <div x-show="showAddAccountModal" x-cloak
         @click.self="showAddAccountModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-md p-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="font-bold text-[#0f172a] text-sm">+ Add New Bank Account</h3>
                <button type="button" @click="showAddAccountModal = false" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
            </div>

            <form method="POST" action="{{ route('tenant.cash-position.bank-account', ['company_slug' => $company->slug]) }}" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Bank</label>
                    <select name="bank_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                        <option value="">— Select Bank —</option>
                        @foreach($banks as $b)
                            <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->bank_code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Account Type</label>
                    <select name="account_type" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                        <option value="Current">Current Account (C/A)</option>
                        <option value="Savings">Savings Account</option>
                        <option value="Money Market">Money Market A/C</option>
                        <option value="Call Deposit">Call Deposit</option>
                        <option value="Margin">Margin Account</option>
                        <option value="Intercompany">Intercompany</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Account Number</label>
                    <input type="text" name="account_number" required placeholder="e.g. 1001 1100 2815"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Currency</label>
                        <select name="currency" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                            <option value="LKR">LKR</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Initial Balance</label>
                        <input type="number" step="0.01" name="opening_balance" value="0.00" required
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showAddAccountModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-[#c3122e] text-white text-xs font-bold hover:bg-[#9e0e24] shadow-sm">Save Account</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal 3: Record Interbank Transfer / Cash Movement --}}
    <div x-show="showMovementModal" x-cloak
         @click.self="showMovementModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-md p-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="font-bold text-[#0f172a] text-sm">Interbank Fund Transfer</h3>
                <button type="button" @click="showMovementModal = false" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
            </div>

            <form method="POST" action="{{ route('tenant.cash-position.movement', ['company_slug' => $company->slug]) }}" class="space-y-3.5">
                @csrf
                <input type="hidden" name="movement_date" :value="entryDate">

                <div>
                    <label class="block text-[11px] font-bold text-red-600 uppercase mb-1">From Bank Account (Outflow)</label>
                    <select name="from_account_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                        <option value="">— Select Source Account —</option>
                        @foreach($bankAccounts as $ba)
                            <option value="{{ $ba->id }}">{{ $ba->bank->name ?? 'Bank' }} - {{ $ba->account_number }} ({{ $ba->currency }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-emerald-700 uppercase mb-1">To Bank Account (Inflow)</label>
                    <select name="to_account_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                        <option value="">— Select Destination Account —</option>
                        @foreach($bankAccounts as $ba)
                            <option value="{{ $ba->id }}">{{ $ba->bank->name ?? 'Bank' }} - {{ $ba->account_number }} ({{ $ba->currency }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Transfer Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono font-bold focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Description / Reference</label>
                    <input type="text" name="description" placeholder="e.g. Settlement / Liquidity rebalance"
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showMovementModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-[#c3122e] text-white text-xs font-bold hover:bg-[#9e0e24] shadow-sm">Execute Transfer</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection