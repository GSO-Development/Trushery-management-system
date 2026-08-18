<?php

use App\Models\Bank;
use App\Models\CompanyBankAccount;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    public string $name      = '';
    public string $shortName = '';
    public string $bankCode  = '';
    public bool   $isActive = true;
    public string $search   = '';

    public bool  $showModal    = false;
    public ?int  $editingId    = null;
    public bool  $showAccounts = false;
    public ?int  $viewingBankId = null;

    // Add Bank Account Modal State
    public bool   $showAccountModal = false;
    public ?int   $accountBankId    = null;
    public string $accountNumber    = '';
    public string $accountType      = 'Current Account';
    public string $accountCurrency  = 'LKR';
    public string $accountNotes     = '';

    public function with(): array
    {
        return [
            'banks' => Bank::when($this->search, function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('bank_code', 'like', "%{$this->search}%");
            })->withCount('companyBankAccounts')
              ->with(['companyBankAccounts.company'])
              ->latest()->get(),

            'allActiveBanks' => Bank::where('is_active', true)->orderBy('name')->get(),

            'viewingAccounts' => $this->viewingBankId
                ? CompanyBankAccount::with('company')
                    ->where('bank_id', $this->viewingBankId)
                    ->orderBy('currency')
                    ->get()
                : collect(),

            'viewingBank' => $this->viewingBankId ? Bank::find($this->viewingBankId) : null,
        ];
    }

    public function openCreate(): void
    {
        $this->reset('name', 'shortName', 'bankCode', 'editingId');
        $this->isActive  = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $bank            = Bank::findOrFail($id);
        $this->editingId = $bank->id;
        $this->name      = $bank->name;
        $this->shortName = $bank->short_name ?? '';
        $this->bankCode  = $bank->bank_code;
        $this->isActive  = $bank->is_active;
        $this->showModal = true;
    }

    public function viewAccounts(int $bankId): void
    {
        $this->viewingBankId = $bankId;
        $this->showAccounts  = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'      => 'required|string|max:255',
            'shortName' => 'required|string|max:30',
            'bankCode'  => 'required|string|max:10|unique:banks,bank_code,' . ($this->editingId ?? 'NULL'),
            'isActive'  => 'boolean',
        ]);

        $data = [
            'name'       => $this->name,
            'short_name' => strtoupper(trim($this->shortName)),
            'bank_code'  => strtoupper($this->bankCode),
            'is_active'  => $this->isActive,
        ];

        if ($this->editingId) {
            Bank::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Bank updated successfully.');
        } else {
            Bank::create($data);
            session()->flash('success', 'Bank created successfully.');
        }

        $this->reset('name', 'shortName', 'bankCode', 'editingId');
        $this->showModal = false;
    }

    public function openCreateAccount(?int $bankId = null): void
    {
        $this->reset('accountNumber', 'accountNotes');
        $this->accountBankId    = $bankId ?: (Bank::where('is_active', true)->first()?->id);
        $this->accountType      = 'Current Account';
        $this->accountCurrency  = 'LKR';
        $this->showAccountModal = true;
    }

    public function saveAccount(): void
    {
        $this->validate([
            'accountBankId'   => 'required|exists:banks,id',
            'accountNumber'   => 'required|string|max:100',
            'accountType'     => 'required|string|max:100',
            'accountCurrency' => 'required|string|max:10',
        ]);

        CompanyBankAccount::create([
            'bank_id'        => $this->accountBankId,
            'company_id'     => null,
            'account_number' => $this->accountNumber,
            'account_type'   => $this->accountType,
            'currency'       => $this->accountCurrency,
            'notes'          => $this->accountNotes,
        ]);

        $this->showAccountModal = false;
        session()->flash('success', 'Bank Account added successfully!');
    }

    public function delete(int $id): void
    {
        Bank::findOrFail($id)->delete();
        session()->flash('success', 'Bank deleted successfully.');
    }
}; ?>

<div>
    @slot('header') Banks Management @endslot

    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between mb-6">
        <div class="relative w-full sm:w-72">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text"
                placeholder="Search banks or code…"
                class="pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] w-full transition-all">
        </div>
        
        <div class="flex items-center gap-2.5 w-full sm:w-auto">
            <!-- Add Account Button -->
            <button wire:click="openCreateAccount"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition-colors shadow-sm shadow-blue-600/20 w-full sm:w-auto justify-center cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                + Add Bank Account
            </button>

            <!-- Add Bank Button -->
            <button wire:click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-sm font-semibold transition-colors shadow-sm shadow-[#c3122e]/20 w-full sm:w-auto justify-center cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add New Bank
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <!-- Banks Table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm mb-6 relative">
        <div class="px-6 py-4 border-b border-slate-50 flex items-center justify-between">
            <h2 class="font-bold text-[#0f172a] text-sm">All Banks</h2>
            <span class="text-xs text-slate-400">Click the 👁️ icon to view account numbers</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-[#f8fafc]">
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-700 uppercase tracking-wider">Bank Name</th>
                        <th class="px-6 py-3.5 text-center text-xs font-bold text-slate-700 uppercase tracking-wider">Short Code</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-700 uppercase tracking-wider">Bank Code</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Registered Accounts</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($banks as $bank)
                        <tr class="hover:bg-[#fdf2f4]/30 transition-colors">
                            <td class="px-6 py-4 font-bold text-[#0f172a]">{{ $bank->name }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-900 text-white font-extrabold text-xs font-mono tracking-wider shadow-sm">
                                    {{ $bank->short_name ?: $bank->bank_code }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs font-bold text-slate-700">{{ $bank->bank_code }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($bank->company_bank_accounts_count > 0)
                                    <div class="relative inline-block text-left group"
                                         x-data="{ open: false }"
                                         @mouseenter="open = true"
                                         @mouseleave="open = false">
                                        <button wire:click="viewAccounts({{ $bank->id }})"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-[#fdf2f4] text-[#c3122e] hover:bg-[#c3122e] hover:text-white text-xs font-bold transition-all border border-[#f8d7da] shadow-sm cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            <span>{{ $bank->company_bank_accounts_count }} Accounts</span>
                                        </button>

                                        <!-- Downward Right-Aligned Hover Popover Tooltip (Aligned with Button) -->
                                        <div x-show="open"
                                             x-cloak
                                             x-transition:enter="transition ease-out duration-150"
                                             x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                             x-transition:leave="transition ease-in duration-100"
                                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                             x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                                             class="absolute z-50 top-full right-0 mt-2 w-80 bg-[#0f172a] text-white rounded-xl shadow-2xl border border-slate-700 p-3 text-left pointer-events-auto">
                                            <div class="text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-2 pb-1.5 border-b border-slate-700/60 flex justify-between items-center">
                                                <span>{{ $bank->name }} Accounts</span>
                                                <span class="text-[#c3122e] bg-[#fdf2f4] px-1.5 py-0.5 rounded text-[10px] font-bold">{{ $bank->company_bank_accounts_count }} total</span>
                                            </div>
                                            <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1 text-xs">
                                                @foreach($bank->companyBankAccounts as $acct)
                                                    <div class="bg-slate-800/90 rounded-lg p-2 border border-slate-700/60 hover:border-slate-500 transition-colors">
                                                        @if($acct->company)
                                                            <div class="flex items-center justify-between text-[11px]">
                                                                <span class="font-bold text-slate-200 truncate max-w-[190px]">{{ $acct->company->name }}</span>
                                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold {{ $acct->currency === 'USD' ? 'bg-blue-900 text-blue-200' : 'bg-slate-700 text-slate-300' }}">{{ $acct->currency }}</span>
                                                            </div>
                                                        @endif
                                                        <div class="flex items-center justify-between mt-1 text-[11px]">
                                                            <span class="font-mono text-amber-400 font-bold tracking-wider">{{ $acct->account_number }}</span>
                                                            <span class="text-[10px] text-slate-400 bg-slate-900 px-1.5 py-0.5 rounded border border-slate-700/50">{{ $acct->account_type }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <!-- Up Arrow Pointing at Accounts Button -->
                                            <div class="absolute bottom-full right-6 -mb-px border-8 border-transparent border-b-[#0f172a]"></div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($bank->is_active)
                                    <span class="px-2.5 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold border border-green-100">Active</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-xs font-medium">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- 👁️ View Accounts Icon Button -->
                                    <button wire:click="viewAccounts({{ $bank->id }})" title="View Bank Accounts Overview"
                                        class="p-2 rounded-xl border border-slate-200 text-blue-600 hover:bg-blue-50 hover:border-blue-300 transition-all shadow-sm group cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>

                                    <!-- ➕ Add Account Icon Button -->
                                    <button wire:click="openCreateAccount({{ $bank->id }})" title="Add Account to this Bank"
                                        class="p-2 rounded-xl border border-slate-200 text-emerald-600 hover:bg-emerald-50 hover:border-emerald-300 transition-all shadow-sm group cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>

                                    <!-- ✏️ Edit Bank Icon Button -->
                                    <button wire:click="edit({{ $bank->id }})" title="Edit Bank Information"
                                        class="p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-amber-50 hover:border-amber-300 hover:text-amber-700 transition-all shadow-sm group cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>

                                    <!-- 🗑️ Delete Bank Icon Button -->
                                    <button wire:click="delete({{ $bank->id }})" wire:confirm="Are you sure you want to delete {{ $bank->name }}?" title="Delete Bank"
                                        class="p-2 rounded-xl border border-slate-200 text-slate-400 hover:bg-red-50 hover:border-red-300 hover:text-red-600 transition-all shadow-sm group cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm">No banks found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Bank Account Numbers Overview Modal Popup ─────────────────── -->
    @if($showAccounts && $viewingBank)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-2xl my-8 overflow-hidden transform transition-all max-h-[90vh] flex flex-col">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-[#f8fafc] flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">
                            🏦
                        </div>
                        <div>
                            <h3 class="font-bold text-[#0f172a] text-base flex items-center gap-2">
                                {{ $viewingBank->name }}
                                <span class="text-xs font-mono px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-semibold">{{ $viewingBank->bank_code }}</span>
                            </h3>
                            <p class="text-xs text-slate-400">
                                {{ $viewingAccounts->count() }} registered account numbers
                            </p>
                        </div>
                    </div>
                    <button wire:click="$set('showAccounts', false)" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto space-y-4 flex-1">
                    @if($viewingAccounts->count() > 0)
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider text-[10px]">
                                        <th class="px-4 py-3 text-left">Sub-Company</th>
                                        <th class="px-4 py-3 text-left">Account Type</th>
                                        <th class="px-4 py-3 text-left font-mono">Account Number</th>
                                        <th class="px-4 py-3 text-center">Currency</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($viewingAccounts as $acct)
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-4 py-2.5 font-semibold text-slate-700">
                                                {{ $acct->company->name ?? '— (Unassigned)' }}
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10px] font-semibold">
                                                    {{ $acct->account_type }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5 font-mono font-bold text-[#0f172a] text-xs tracking-wider">
                                                {{ $acct->account_number }}
                                            </td>
                                            <td class="px-4 py-2.5 text-center">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold
                                                    {{ $acct->currency === 'USD' ? 'bg-blue-100 text-blue-700' : ($acct->currency === 'EUR' ? 'bg-purple-100 text-purple-700' : ($acct->currency === 'GBP' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-700')) }}">
                                                    {{ $acct->currency }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="py-12 text-center text-slate-400 text-xs italic bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            No bank account numbers recorded for {{ $viewingBank->name }} yet.
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end px-6 py-3.5 border-t border-slate-100 bg-[#f8fafc] flex-shrink-0">
                    <button type="button" wire:click="$set('showAccounts', false)"
                        class="px-5 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold transition-colors">
                        Close Overview
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ── Add New Bank Account Modal Popup ──────────────────────────── -->
    @if($showAccountModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-md my-8 overflow-hidden transform transition-all max-h-[90vh] flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-[#f8fafc] flex-shrink-0">
                    <h3 class="font-bold text-[#0f172a] text-base flex items-center gap-2">
                        <span>🏦 Add New Bank Account</span>
                    </h3>
                    <button wire:click="$set('showAccountModal', false)" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto space-y-4 flex-1">
                    <form wire:submit="saveAccount" id="accountForm" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Select Bank <span class="text-red-500">*</span></label>
                            <select wire:model="accountBankId" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white font-medium">
                                @foreach($allActiveBanks as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->bank_code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Account Number <span class="text-red-500">*</span></label>
                            <input wire:model="accountNumber" type="text" placeholder="e.g. 1000284719, 800294711" required
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] font-mono font-bold tracking-wider">
                            @error('accountNumber') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Account Type <span class="text-red-500">*</span></label>
                                <select wire:model="accountType" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white font-medium">
                                    <option value="Current Account">Current Account</option>
                                    <option value="Savings Account">Savings Account</option>
                                    <option value="Overdraft Facility">Overdraft Facility</option>
                                    <option value="Term Loan Account">Term Loan Account</option>
                                    <option value="Fixed Deposit Account">Fixed Deposit Account</option>
                                    <option value="Money Market Account">Money Market Account</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Currency <span class="text-red-500">*</span></label>
                                <select wire:model="accountCurrency" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white font-medium">
                                    <option value="LKR">LKR (Sri Lankan Rupee)</option>
                                    <option value="USD">USD (US Dollar)</option>
                                    <option value="EUR">EUR (Euro)</option>
                                    <option value="GBP">GBP (British Pound)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Notes / Description (Optional)</label>
                            <input wire:model="accountNotes" type="text" placeholder="e.g. Operational Account"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                        </div>
                    </form>
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-[#f8fafc] flex-shrink-0">
                    <button type="button" wire:click="$set('showAccountModal', false)"
                        class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-sm font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" form="accountForm"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition-colors shadow-sm shadow-blue-600/20">
                        Save Bank Account
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ── Bank Add/Edit Modal ────────────────────────────────────────── -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-md my-8 overflow-hidden transform transition-all max-h-[90vh] flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-[#f8fafc] flex-shrink-0">
                    <h3 class="font-bold text-[#0f172a] text-base">
                        {{ $editingId ? 'Edit Bank Information' : 'Add New Bank' }}
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto space-y-4 flex-1">
                    <form wire:submit="save" id="bankForm" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Bank Full Name <span class="text-red-500">*</span></label>
                            <input wire:model="name" type="text" placeholder="e.g. Commercial Bank of Ceylon" required
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Bank Short Code <span class="text-red-500">*</span></label>
                                <input wire:model="shortName" type="text" placeholder="e.g. COMBANK, HNB, BOC" required
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] font-mono font-bold uppercase">
                                @error('shortName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Bank Code (Clearing No) <span class="text-red-500">*</span></label>
                                <input wire:model="bankCode" type="text" placeholder="e.g. 7056" required
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] font-mono">
                                @error('bankCode') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="flex items-center gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                            <input wire:model="isActive" type="checkbox" id="is-active" class="w-4 h-4 rounded text-[#c3122e] focus:ring-[#c3122e]">
                            <label for="is-active" class="text-xs font-semibold text-slate-700">Active Bank (available for company assignment)</label>
                        </div>
                    </form>
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-[#f8fafc] flex-shrink-0">
                    <button type="button" wire:click="$set('showModal', false)"
                        class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-sm font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" form="bankForm"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-sm font-semibold transition-colors shadow-sm shadow-[#c3122e]/20">
                        <svg wire:loading wire:target="save" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ $editingId ? 'Save Changes' : 'Create Bank' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
