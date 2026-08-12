<?php

use App\Models\Company;
use App\Models\Group;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    public string $name = '';
    public ?int $companyId = null;
    public array $selectedNavKeys = [];
    public array $availableNavPages = [];
    public bool $showModal = false;
    public ?int $editingId = null;

    public function with(): array
    {
        return [
            'groups'    => Group::with(['company'])->latest()->get(),
            'companies' => Company::orderBy('name')->get(),
        ];
    }

    public function updatedCompanyId($value): void
    {
        if ($value) {
            $company = Company::find($value);
            $this->availableNavPages = $company ? $company->getAvailableNavPages() : [];
        } else {
            $this->availableNavPages = [];
        }
    }

    public function openCreate(): void
    {
        $this->reset('name', 'companyId', 'selectedNavKeys', 'availableNavPages', 'editingId');
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $group                  = Group::with('company')->findOrFail($id);
        $this->editingId        = $group->id;
        $this->name             = $group->name;
        $this->companyId        = $group->company_id;
        $this->selectedNavKeys  = $group->getNavKeys();
        $this->availableNavPages= $group->company ? $group->company->getAvailableNavPages() : [];
        $this->showModal        = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'      => 'required|string|max:255',
            'companyId' => 'required|exists:companies,id',
        ]);

        $group = $this->editingId
            ? Group::findOrFail($this->editingId)
            : new Group();

        $group->fill([
            'name'            => $this->name,
            'company_id'      => $this->companyId,
            'nav_permissions' => array_values($this->selectedNavKeys),
        ])->save();

        $this->reset('name', 'companyId', 'selectedNavKeys', 'availableNavPages', 'editingId');
        $this->showModal = false;
        session()->flash('success', 'Access Group & nav permissions saved successfully.');
    }

    public function delete(int $id): void
    {
        Group::findOrFail($id)->delete();
        session()->flash('success', 'Group deleted.');
    }
}; ?>

<div>
    @slot('header') Groups & Permissions Management @endslot

    @if(session('success'))
        <div class="mb-5 p-3.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <!-- Groups table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-50">
            <h2 class="font-bold text-[#0f172a] text-base">All Access Groups</h2>
            <button wire:click="openCreate"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-semibold transition-colors shadow-sm shadow-[#c3122e]/20">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Access Group
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-[#f8fafc]">
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Group Name</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Sub-Company</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Allowed Navigation Pages</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($groups as $group)
                        <tr class="hover:bg-[#fdf2f4]/30 transition-colors">
                            <td class="px-6 py-4 font-medium text-[#0f172a]">{{ $group->name }}</td>
                            <td class="px-6 py-4 text-slate-600 font-medium">{{ $group->company->name ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($group->getNavKeys() as $navKey)
                                        @php
                                            $label = Str::of($navKey)->replace('_', ' ')->title()->toString();
                                        @endphp
                                        <span class="px-2.5 py-1 rounded-full bg-[#fdf2f4] text-[#c3122e] text-xs font-semibold border border-[#f8d7da]">
                                            {{ $label }}
                                        </span>
                                    @empty
                                        <span class="text-slate-400 text-xs italic">No nav permissions assigned</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="edit({{ $group->id }})"
                                        class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:border-[#c3122e] hover:text-[#c3122e] text-xs font-medium transition-colors">Edit</button>
                                    <button wire:click="delete({{ $group->id }})"
                                        wire:confirm="Delete {{ $group->name }}?"
                                        class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-400 hover:border-red-200 hover:text-red-500 text-xs font-medium transition-colors">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-10 text-center text-slate-400 text-sm">No groups yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Responsive Group Modal Popup ────────────────────────── -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-md my-8 overflow-hidden transform transition-all max-h-[90vh] flex flex-col">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-[#f8fafc] flex-shrink-0">
                    <h3 class="font-bold text-[#0f172a] text-base">
                        {{ $editingId ? 'Edit Group & Permissions' : 'Add New Access Group' }}
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto space-y-4 flex-1">
                    <form wire:submit="save" id="groupForm" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Group Name <span class="text-red-500">*</span></label>
                            <input wire:model="name" type="text" placeholder="e.g. Finance, Dashboard, Super Dashboard" required
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Sub-Company <span class="text-red-500">*</span></label>
                            <select wire:model.live="companyId" required
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white">
                                <option value="">Select Company</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                            @error('companyId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Folder-based Dynamic Navigation Checkboxes -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                                Navigation Permissions (Blade Views in Company Folder)
                            </label>
                            @if($companyId && count($availableNavPages) > 0)
                                <div class="space-y-2 max-h-56 overflow-y-auto p-3 rounded-xl border border-slate-200 bg-slate-50/50">
                                    @foreach($availableNavPages as $navKey => $navLabel)
                                        <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 cursor-pointer hover:border-[#c3122e] transition-colors {{ in_array($navKey, $selectedNavKeys) ? 'border-[#c3122e] bg-[#fdf2f4]' : 'bg-white' }}">
                                            <input wire:model="selectedNavKeys" type="checkbox" value="{{ $navKey }}"
                                                class="rounded text-[#c3122e] focus:ring-[#c3122e] w-4 h-4">
                                            <div>
                                                <p class="text-xs font-bold text-[#0f172a]">{{ $navLabel }}</p>
                                                <p class="text-[10px] text-slate-400 font-mono">views/livewire/tenant/{{ $companies->find($companyId)->slug ?? '' }}/{{ $navKey }}.blade.php</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @elseif($companyId)
                                <div class="p-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-xs font-medium">
                                    No views found in <span class="font-mono">views/livewire/tenant/{{ $companies->find($companyId)->slug ?? '' }}/</span>. Creating this company will auto-scaffold view stubs.
                                </div>
                            @else
                                <p class="text-xs text-slate-400 italic">Please select a Sub-Company first to view its available navigation permissions.</p>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-[#f8fafc] flex-shrink-0">
                    <button type="button" wire:click="$set('showModal', false)"
                        class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-sm font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" form="groupForm"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-sm font-semibold transition-colors shadow-sm shadow-[#c3122e]/20">
                        <svg wire:loading wire:target="save" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ $editingId ? 'Save Changes' : 'Create Group' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
