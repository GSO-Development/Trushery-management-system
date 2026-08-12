<?php

use App\Models\Bank;
use App\Models\BankEntry;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public ?int $selectedBankId = null;
    public string $selectedBankName = '';
    public string $interestRate = '';
    public string $availableAmount = '';
    public string $notes = '';
    public bool $showEditModal = false;

    public function with(): array
    {
        $user    = auth()->user();
        $company = $user->company;

        // Assigned banks for this company
        $assignedBanks = $company ? $company->banks()->where('is_active', true)->get() : collect();

        // Existing entries for this company keyed by bank_id
        $entries = BankEntry::where('company_id', $company?->id)
            ->get()
            ->keyBy('bank_id');

        return [
            'company'       => $company,
            'assignedBanks' => $assignedBanks,
            'entries'       => $entries,
        ];
    }

    public function openEdit(int $bankId): void
    {
        $user    = auth()->user();
        $company = $user->company;
        $bank    = Bank::findOrFail($bankId);

        $entry = BankEntry::where('company_id', $company->id)
            ->where('bank_id', $bankId)
            ->first();

        $this->selectedBankId   = $bank->id;
        $this->selectedBankName = $bank->name . ' (' . ($bank->short_name ?: $bank->bank_code) . ')';
        $this->interestRate     = $entry ? (string) $entry->interest_rate : '';
        $this->availableAmount  = $entry ? (string) $entry->available_amount : '';
        $this->notes            = $entry ? (string) $entry->notes : '';
        $this->showEditModal    = true;
    }

    public function saveEntry(): void
    {
        $this->validate([
            'selectedBankId'  => 'required|exists:banks,id',
            'interestRate'    => 'required|numeric|min:0|max:100',
            'availableAmount' => 'required|numeric|min:0',
            'notes'           => 'nullable|string|max:1000',
        ]);

        $user    = auth()->user();
        $company = $user->company;

        BankEntry::updateOrCreate(
            [
                'company_id' => $company->id,
                'bank_id'    => $this->selectedBankId,
            ],
            [
                'user_id'          => $user->id,
                'interest_rate'    => $this->interestRate,
                'available_amount' => $this->availableAmount,
                'notes'            => $this->notes,
                'entry_date'       => now(),
            ]
        );

        $this->showEditModal = false;
        session()->flash('success', 'Bank rate & available amount updated successfully.');
    }
}; ?>

<div>
    @slot('header') Bank Rates & Available Amounts @endslot

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-[#0f172a]">Finance Data Entry</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $company->name ?? '' }} · Assigned Bank Facilities</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-5 p-3.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <!-- Cards Grid of Assigned Banks -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
        @forelse($assignedBanks as $bank)
            @php
                $entry = $entries->get($bank->id);
            @endphp
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex flex-col justify-between hover:border-[#c3122e]/30 transition-all">
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <span class="px-2.5 py-1 rounded-full bg-[#fdf2f4] text-[#c3122e] text-xs font-bold font-mono border border-[#f8d7da]">
                                Code: {{ $bank->bank_code }}
                            </span>
                            <h3 class="font-bold text-[#0f172a] text-lg mt-2">{{ $bank->name }}</h3>
                            <p class="text-xs text-slate-400 font-medium">{{ $bank->short_name }} {{ $bank->swift_code ? '· SWIFT: '.$bank->swift_code : '' }}</p>
                        </div>
                    </div>

                    <div class="space-y-3 my-4 py-3 border-y border-slate-100">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500 font-medium">Interest Rate</span>
                            <span class="font-bold text-lg {{ $entry ? 'text-[#c3122e]' : 'text-slate-300' }}">
                                {{ $entry ? $entry->interest_rate.'%' : 'Not set' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500 font-medium">Available Amount</span>
                            <span class="font-bold text-base {{ $entry ? 'text-[#0f172a]' : 'text-slate-300' }}">
                                {{ $entry ? 'LKR '.number_format($entry->available_amount, 2) : 'Not set' }}
                            </span>
                        </div>
                        @if($entry && $entry->notes)
                            <p class="text-xs text-slate-500 bg-slate-50 p-2 rounded-lg italic">"{{ $entry->notes }}"</p>
                        @endif
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400">
                        {{ $entry ? 'Updated '.$entry->updated_at->diffForHumans() : 'No entry' }}
                    </span>
                    <button wire:click="openEdit({{ $bank->id }})"
                        class="px-4 py-2 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-sm shadow-[#c3122e]/20">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        {{ $entry ? 'Update Rates' : 'Enter Rates' }}
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl p-12 text-center text-slate-400">
                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <p class="font-medium text-slate-600">No banks assigned to {{ $company->name ?? 'your company' }} yet.</p>
                <p class="text-xs mt-1">Please ask your system administrator to assign banks under Company Management.</p>
            </div>
        @endforelse
    </div>

    <!-- Edit Modal -->
    @if($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-[#0f172a] text-base">{{ $selectedBankName }}</h3>
                    <button wire:click="$set('showEditModal', false)" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit="saveEntry" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Annual Interest Rate (%) <span class="text-red-500">*</span></label>
                        <input wire:model="interestRate" type="number" step="0.001" min="0" max="100" placeholder="e.g. 11.5" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] font-mono">
                        @error('interestRate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Available Credit Amount (LKR) <span class="text-red-500">*</span></label>
                        <input wire:model="availableAmount" type="number" step="0.01" min="0" placeholder="e.g. 50000000.00" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] font-mono">
                        @error('availableAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Notes / Remarks (Optional)</label>
                        <textarea wire:model="notes" rows="3" placeholder="Additional conditions, rate expiry date..."
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="$set('showEditModal', false)" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition-colors">Cancel</button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-sm font-semibold transition-colors">Save Entry</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
