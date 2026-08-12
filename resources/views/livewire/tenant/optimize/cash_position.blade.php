@extends('layouts.portal')
@section('header', 'Daily Group Cash Position Report')

@section('content')
@php
    $totalInflows   = $totalCashIn ?? 0;
    $totalOutflows  = $totalCashOut ?? 0;
    $totalOpening   = $totalOpening ?? 0;
    $totalClosing   = $totalOpening + $totalInflows - $totalOutflows;
    $totalRestr     = $restrictedCash ?? 0;
    $totalAvail     = $availableCash ?? ($totalClosing - $totalRestr);
    $usdExchangeRate = 300.00;
@endphp

<div class="space-y-6" x-data="{ showEntryModal: false, showAddAccountModal: false, selectedAccountId: null, selectedAccountDisplay: '', openingBal: '0.00', cashIn: '0.00', cashOut: '0.00', restrictedCash: '0.00', entryDate: '{{ date('Y-m-d') }}', remarks: '' }">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-2">
        <div>
            <h1 class="text-2xl font-extrabold text-[#0f172a] tracking-tight">Daily Group Cash Position Report</h1>
            <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
                <span>🏢 {{ $company->name ?? 'Sub-Company' }}</span>
                <span>•</span>
                <span class="font-mono text-xs text-slate-400">Date: {{ date('F j, Y') }}</span>
            </p>
        </div>
        <div class="flex items-center gap-3 self-start sm:self-auto">
            <button type="button" @click="showAddAccountModal = true"
                class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-bold transition-all shadow-md shadow-[#c3122e]/20 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                + Add Bank Account
            </button>
            <span class="px-3.5 py-2 rounded-full bg-[#fdf2f4] border border-[#f8d7da] text-[#c3122e] text-xs font-bold uppercase tracking-wider shadow-sm flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#c3122e] animate-pulse"></span>
                Daily Cash Sync
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <!-- KPI Header Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Registered Accounts</p>
            <p class="text-2xl font-bold text-[#0f172a]">{{ $bankAccounts->count() }}</p>
            <p class="text-xs text-slate-400 mt-1">Active bank accounts</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Inflows Today</p>
            <p class="text-2xl font-bold text-emerald-600">+ {{ number_format($totalInflows, 2) }}</p>
            <p class="text-xs text-slate-400 mt-1">Customer collections &amp; deposits</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Outflows Today</p>
            <p class="text-2xl font-bold text-[#c3122e]">- {{ number_format($totalOutflows, 2) }}</p>
            <p class="text-xs text-slate-400 mt-1">Payments &amp; withdrawals</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 bg-gradient-to-br from-blue-50/80 to-indigo-50/50">
            <p class="text-xs font-semibold text-blue-800 uppercase tracking-wider mb-1">Net Available Cash</p>
            <p class="text-2xl font-bold text-blue-900">{{ number_format($totalAvail, 2) }}</p>
            <p class="text-xs text-blue-600 mt-1">Closing Balance minus Restricted Cash</p>
        </div>
    </div>

    <!-- Main Table: Daily Group Cash Position Report (Exact Excel Spec) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-[#f8fafc] flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-[#0f172a] text-base">Daily Cash Position Breakdown</h2>
                <p class="text-xs text-slate-400">Registered bank accounts and cash movements for {{ $company->name }}</p>
            </div>
            <button type="button" @click="showAddAccountModal = true"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition-colors cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Account
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-[#f1f5f9] text-slate-600 font-bold uppercase tracking-wider">
                        <th class="px-4 py-3.5 text-left">Bank</th>
                        <th class="px-4 py-3.5 text-left">Account Type</th>
                        <th class="px-4 py-3.5 text-left font-mono">Account #</th>
                        <th class="px-4 py-3.5 text-center">Currency</th>
                        <th class="px-4 py-3.5 text-right">Opening Balance</th>
                        <th class="px-4 py-3.5 text-right text-emerald-700">Inflows</th>
                        <th class="px-4 py-3.5 text-right text-red-600">Outflows</th>
                        <th class="px-4 py-3.5 text-right font-extrabold text-[#0f172a]">Closing Balance</th>
                        <th class="px-4 py-3.5 text-right text-amber-700">Restricted Cash</th>
                        <th class="px-4 py-3.5 text-right font-extrabold text-blue-700">Available Cash</th>
                        <th class="px-4 py-3.5 text-right font-mono text-purple-700">USD Closing Balance</th>
                        <th class="px-4 py-3.5 text-center">Action</th>
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
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3.5 font-bold text-[#0f172a]">
                                {{ $acct->bank->name ?? '—' }}
                                <span class="block text-[10px] font-mono text-slate-400">{{ $acct->bank->bank_code ?? '' }}</span>
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
                            <td class="px-4 py-3.5 text-right font-mono font-medium text-slate-600">
                                {{ number_format($open, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono font-bold text-emerald-600">
                                {{ $in > 0 ? '+ '.number_format($in, 2) : '0.00' }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono font-bold text-red-600">
                                {{ $out > 0 ? '- '.number_format($out, 2) : '0.00' }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono font-bold text-[#0f172a] bg-slate-50/50">
                                {{ number_format($close, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-amber-700 font-medium">
                                {{ number_format($restr, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono font-bold text-blue-700 bg-blue-50/30">
                                {{ number_format($avail, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono font-bold text-purple-700 bg-purple-50/20">
                                ${{ number_format($usdClosing, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-center flex items-center justify-center gap-1.5">
                                <button type="button"
                                    @click="selectedAccountId = {{ $acct->id }}; selectedAccountDisplay = '{{ addslashes($acct->bank->name ?? 'Bank') }} - {{ $acct->account_number }} ({{ $acct->currency }})'; openingBal = '{{ $close }}'; cashIn = '0.00'; cashOut = '0.00'; restrictedCash = '{{ $restr }}'; showEntryModal = true"
                                    class="px-2.5 py-1 rounded-lg bg-[#c3122e] hover:bg-[#9e0e24] text-white font-semibold text-[11px] transition-colors shadow-sm cursor-pointer">
                                    {{ $entry ? 'Update' : 'Enter' }}
                                </button>
                                <form method="POST" action="{{ route('tenant.cash-position.bank-account.destroy', ['company_slug' => $company->slug, 'bankAccount' => $acct->id]) }}" onsubmit="return confirm('Delete bank account {{ $acct->account_number }} and its cash records?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer" title="Delete Bank Account">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
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
                            <td class="px-4 py-3 text-right font-mono text-slate-300">{{ number_format($totalRestr, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-blue-300">{{ number_format($totalAvail, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-purple-300">—</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Modal 1: Add New Bank Account -->
    <div x-show="showAddAccountModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto" x-data="{ isAddingNewBank: false }">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg my-8 overflow-hidden transform transition-all flex flex-col relative">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-[#f8fafc]">
                <div>
                    <h3 class="font-bold text-[#0f172a] text-base">Add New Bank Account</h3>
                    <p class="text-xs text-slate-400 font-mono mt-0.5">{{ $company->name }} · Register Bank Account</p>
                </div>
                <button type="button" @click="showAddAccountModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('tenant.cash-position.bank-account', ['company_slug' => $company->slug]) }}" id="addBankAccountForm" class="p-6 space-y-4">
                @csrf

                <!-- Bank Selection & Inline Add Bank Button -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-semibold text-slate-700 uppercase tracking-wider">Select Bank <span class="text-red-500">*</span></label>
                        <button type="button" @click="isAddingNewBank = !isAddingNewBank" class="text-xs font-bold text-[#c3122e] hover:underline flex items-center gap-1 cursor-pointer">
                            <span x-text="isAddingNewBank ? '← Select Existing Bank' : '+ Add New Bank'"></span>
                        </button>
                    </div>

                    <!-- Existing Bank Dropdown -->
                    <div x-show="!isAddingNewBank">
                        <select name="bank_id" :required="!isAddingNewBank" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm bg-white font-medium focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                            <option value="">— Select Available Bank —</option>
                            @foreach($allBanks as $b)
                                <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->bank_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Inline New Bank Input Fields -->
                    <div x-show="isAddingNewBank" class="p-3.5 rounded-xl bg-amber-50/80 border border-amber-200 space-y-3 mt-1">
                        <input type="hidden" name="is_adding_new_bank" :value="isAddingNewBank ? '1' : '0'">
                        <div class="text-xs font-bold text-amber-800 flex items-center gap-1.5">
                            <span>➕ Register New Bank to System</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 uppercase mb-1">Bank Name <span class="text-red-500">*</span></label>
                                <input type="text" name="new_bank_name" placeholder="e.g. Seylan Bank PLC" :required="isAddingNewBank" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 uppercase mb-1">Bank Code <span class="text-red-500">*</span></label>
                                <input type="text" name="new_bank_code" placeholder="e.g. SEYB" :required="isAddingNewBank" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs font-mono uppercase bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Number & Currency -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Account Number <span class="text-red-500">*</span></label>
                        <input type="text" name="account_number" required placeholder="e.g. 10029384756" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-mono font-bold text-slate-800 focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Currency <span class="text-red-500">*</span></label>
                        <select name="currency" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm bg-white font-medium focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                            <option value="LKR">LKR (Sri Lankan Rupee)</option>
                            <option value="USD">USD (US Dollar)</option>
                            <option value="EUR">EUR (Euro)</option>
                            <option value="GBP">GBP (British Pound)</option>
                        </select>
                    </div>
                </div>

                <!-- Account Type & Opening Balance -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Account Type <span class="text-red-500">*</span></label>
                        <select name="account_type" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm bg-white font-medium focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                            <option value="Current Account">Current Account</option>
                            <option value="Savings Account">Savings Account</option>
                            <option value="Overdraft Account">Overdraft Account</option>
                            <option value="Fixed Deposit Account">Fixed Deposit Account</option>
                            <option value="Collection Account">Collection Account</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Initial Opening Balance</label>
                        <input type="number" step="0.01" name="opening_balance" placeholder="0.00" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-mono focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Notes / Remarks (Optional)</label>
                    <input type="text" name="notes" placeholder="e.g. Main Operations account at Colombo 03 branch..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                </div>
            </form>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-[#f8fafc]">
                <button type="button" @click="showAddAccountModal = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-sm font-medium transition-colors">Cancel</button>
                <button type="submit" form="addBankAccountForm" class="px-6 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-sm font-semibold transition-colors shadow-sm shadow-[#c3122e]/20">Save Bank Account</button>
            </div>
        </div>
    </div>

    <!-- Modal 2: Daily Cash Position Entry -->
    <div x-show="showEntryModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-md my-8 overflow-hidden transform transition-all flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-[#f8fafc]">
                <div>
                    <h3 class="font-bold text-[#0f172a] text-base">Enter Daily Cash Position</h3>
                    <p class="text-xs text-slate-400 font-mono mt-0.5" x-text="selectedAccountDisplay"></p>
                </div>
                <button type="button" @click="showEntryModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <form method="POST" action="{{ route('tenant.cash-position.entry', ['company_slug' => $company->slug]) }}" id="cashEntryForm" class="space-y-4">
                    @csrf
                    <input type="hidden" name="company_bank_account_id" :value="selectedAccountId">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Entry Date <span class="text-red-500">*</span></label>
                        <input name="entry_date" x-model="entryDate" type="date" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Opening Balance <span class="text-red-500">*</span></label>
                            <input name="opening_balance" x-model="openingBal" type="number" step="0.01" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Inflows <span class="text-red-500">*</span></label>
                            <input name="cash_in" x-model="cashIn" type="number" step="0.01" min="0" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-mono text-emerald-600 font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Outflows <span class="text-red-500">*</span></label>
                            <input name="cash_out" x-model="cashOut" type="number" step="0.01" min="0" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-mono text-red-600 font-bold focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Restricted Cash</label>
                            <input name="restricted_cash" x-model="restrictedCash" type="number" step="0.01" min="0" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-mono text-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Remarks / Notes (Optional)</label>
                        <input name="remarks" x-model="remarks" type="text" placeholder="Additional comments..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                    </div>
                </form>
            </div>

            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-[#f8fafc]">
                <button type="button" @click="showEntryModal = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-sm font-medium transition-colors">Cancel</button>
                <button type="submit" form="cashEntryForm" class="px-6 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-sm font-semibold transition-colors shadow-sm shadow-[#c3122e]/20">Save Entry</button>
            </div>
        </div>
    </div>
</div>
@endsection
