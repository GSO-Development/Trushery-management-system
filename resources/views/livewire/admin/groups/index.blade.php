<?php

use App\Models\Company;
use App\Models\Group;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    // Form properties
    public string $name = '';
    public string $groupType = 'individual'; // 'individual' or 'group'
    public ?int $companyId = null; // For individual
    public array $selectedCompanyIds = []; // For multi-company group
    public array $selectedNavKeys = [];
    public array $availableNavPages = [];
    public bool $emailNotificationsEnabled = false;
    public bool $showModal = false;
    public ?int $editingId = null;

    // Filter & Search properties
    public string $search = '';
    public string $groupTypeFilter = 'all'; // 'all', 'individual', 'group', 'email_enabled'

    public function with(): array
    {
        $query = Group::with(['company', 'users']);

        // 1. Group Type Filter
        if ($this->groupTypeFilter === 'individual') {
            $query->where(function ($q) {
                $q->where('group_type', '!=', 'group')->orWhereNull('group_type');
            });
        } elseif ($this->groupTypeFilter === 'group') {
            $query->where('group_type', 'group');
        } elseif ($this->groupTypeFilter === 'email_enabled') {
            $query->where('email_notifications_enabled', true);
        }

        // 2. Search Filter (Group name, Assigned Company name, or User name/email)
        if (trim($this->search) !== '') {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhereHas('company', fn($cq) => $cq->where('name', 'like', $term))
                  ->orWhereHas('users', fn($uq) => $uq->where('name', 'like', $term)->orWhere('email', 'like', $term));
            });
        }

        $allCompanies = Company::orderBy('name')->get();

        return [
            'groups'       => $query->latest()->get(),
            'companies'    => $allCompanies,
            'totalCount'   => Group::count(),
            'matchedCount' => $query->count(),
        ];
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'groupTypeFilter');
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

    public function toggleSelectAllCompanies(): void
    {
        $allIds = Company::pluck('id')->map(fn($id) => (string)$id)->toArray();
        if (count($this->selectedCompanyIds) === count($allIds)) {
            $this->selectedCompanyIds = [];
        } else {
            $this->selectedCompanyIds = $allIds;
        }
    }

    public function openCreate(): void
    {
        $this->reset('name', 'groupType', 'companyId', 'selectedCompanyIds', 'selectedNavKeys', 'availableNavPages', 'editingId', 'emailNotificationsEnabled');
        $this->groupType = 'individual';
        $this->emailNotificationsEnabled = false;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $group                           = Group::with('company')->findOrFail($id);
        $this->editingId                 = $group->id;
        $this->name                      = $group->name;
        $this->groupType                 = $group->group_type ?? 'individual';
        $this->companyId                 = $group->company_id;
        $this->selectedCompanyIds        = array_map('strval', $group->company_ids ?? []);
        $this->selectedNavKeys           = $group->getNavKeys();
        $this->availableNavPages         = $group->company ? $group->company->getAvailableNavPages() : [];
        $this->emailNotificationsEnabled = (bool) $group->email_notifications_enabled;
        $this->showModal                 = true;
    }

    public function save(): void
    {
        if ($this->groupType === 'group') {
            $this->validate([
                'name'               => 'required|string|max:255',
                'selectedCompanyIds' => 'required|array|min:1',
            ], [
                'selectedCompanyIds.min'      => 'Please select at least one Sub-Company for this Multi-Company Group.',
                'selectedCompanyIds.required' => 'Please select at least one Sub-Company for this Multi-Company Group.',
            ]);

            $group = $this->editingId
                ? Group::findOrFail($this->editingId)
                : new Group();

            $group->fill([
                'name'                        => $this->name,
                'group_type'                  => 'group',
                'company_id'                  => null,
                'company_ids'                 => array_values(array_map('intval', $this->selectedCompanyIds)),
                'nav_permissions'             => ['summary_dashboard', 'ceo_overview'],
                'email_notifications_enabled' => $this->emailNotificationsEnabled,
            ])->save();

        } else {
            $this->validate([
                'name'      => 'required|string|max:255',
                'companyId' => 'required|exists:companies,id',
            ], [
                'companyId.required' => 'Please select a Sub-Company for this group.',
            ]);

            $group = $this->editingId
                ? Group::findOrFail($this->editingId)
                : new Group();

            $group->fill([
                'name'                        => $this->name,
                'group_type'                  => 'individual',
                'company_id'                  => $this->companyId,
                'company_ids'                 => null,
                'nav_permissions'             => array_values($this->selectedNavKeys),
                'email_notifications_enabled' => $this->emailNotificationsEnabled,
            ])->save();
        }

        $this->reset('name', 'groupType', 'companyId', 'selectedCompanyIds', 'selectedNavKeys', 'availableNavPages', 'editingId', 'emailNotificationsEnabled');
        $this->showModal = false;
        session()->flash('success', 'Access Group & permissions saved successfully.');
    }

    public function delete(int $id): void
    {
        Group::findOrFail($id)->delete();
        session()->flash('success', 'Group deleted successfully.');
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

    <!-- Top Action Row -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-black text-[#0f172a] flex items-center gap-2.5">
                <span>Access Groups</span>
                <span class="px-3 py-0.5 rounded-full bg-slate-100 border border-slate-200 text-slate-700 text-xs font-bold font-mono">
                    {{ $groups->count() }} @if($search || $groupTypeFilter !== 'all') of {{ $totalCount }} @endif
                </span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Manage user access groups, sub-company permissions &amp; automated email notification dispatches.</p>
        </div>
        <button wire:click="openCreate"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-sm font-bold whitespace-nowrap shrink-0 transition-all shadow-md shadow-[#c3122e]/25 hover:shadow-lg cursor-pointer">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>+ Add Access Group</span>
        </button>
    </div>

    <!-- Filter & Search Toolbar — 50/50 grid: Dropdown left, Search right -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-center">

            <!-- LEFT 50%: Group Type Dropdown Filter -->
            <div class="w-full">
                <select wire:model.live="groupTypeFilter"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 bg-slate-50 hover:bg-white focus:bg-white focus:border-[#c3122e] focus:ring-2 focus:ring-[#c3122e]/20 outline-none transition-all cursor-pointer">
                    <option value="all">📁 All Access Groups</option>
                    <option value="individual">🏢 Individual Sub-Company Groups</option>
                    <option value="group">🏢+ Multi-Company (CEO / Group)</option>
                    <option value="email_enabled">✉️ Email Alerts Active Only</option>
                </select>
            </div>

            <!-- RIGHT 50%: Search Bar + optional Reset -->
            <div class="flex items-center gap-2 w-full">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-[#c3122e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.250ms="search" type="text"
                        placeholder="Search group, company, user..."
                        class="w-full pl-10 pr-9 py-2.5 rounded-xl border border-slate-300 text-xs font-medium text-slate-800 bg-slate-50 focus:bg-white focus:border-[#c3122e] focus:ring-2 focus:ring-[#c3122e]/20 outline-none transition-all placeholder:text-slate-400">
                    @if($search)
                        <button type="button" wire:click="$set('search', '')"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-red-600 transition-colors font-bold text-xs cursor-pointer"
                            title="Clear search">
                            ✕
                        </button>
                    @endif
                </div>
                @if($search || $groupTypeFilter !== 'all')
                    <button type="button" wire:click="resetFilters"
                        class="px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors whitespace-nowrap flex items-center gap-1.5 cursor-pointer border border-slate-200 shrink-0">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Reset</span>
                    </button>
                @endif
            </div>

        </div>
    </div>

    <!-- Groups table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-[#f8fafc]">
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Group Name</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Sub-Company Scope</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Navigation / Scope</th>
                        <th class="px-6 py-3.5 text-center text-xs font-bold text-slate-600 uppercase tracking-wider">Email Alerts</th>
                        <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($groups as $group)
                        @php
                            $isGroupType = $group->isGroup();
                        @endphp
                        <tr class="hover:bg-[#fdf2f4]/30 transition-colors">
                            <!-- Group Name -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-sm shadow-sm {{ $isGroupType ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                        {{ $isGroupType ? '🏢+' : '🏢' }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-[#0f172a] text-sm">{{ $group->name }}</p>
                                        <p class="text-xs text-slate-400 font-mono">{{ $group->users->count() }} user(s)</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Group Type Badge -->
                            <td class="px-6 py-4">
                                @if($isGroupType)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-xs font-bold shadow-xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Multi-Company (Group)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold shadow-xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Individual
                                    </span>
                                @endif
                            </td>

                            <!-- Sub-Company Scope -->
                            <td class="px-6 py-4">
                                @if($isGroupType)
                                    @php
                                        $assigned = $group->getAssignedCompanies();
                                    @endphp
                                    <div>
                                        <p class="font-bold text-xs text-amber-900 flex items-center gap-1">
                                            <span>🏢</span>
                                            <span>{{ $assigned->count() }} Sub-Companies</span>
                                        </p>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach($assigned->take(4) as $comp)
                                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-medium font-mono border border-slate-200">
                                                    {{ $comp->name }}
                                                </span>
                                            @endforeach
                                            @if($assigned->count() > 4)
                                                <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-bold">
                                                    +{{ $assigned->count() - 4 }} more
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="font-semibold text-xs text-[#0f172a]">
                                        {{ $group->company->name ?? '-' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Navigation / Access Scope -->
                            <td class="px-6 py-4">
                                @if($isGroupType)
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold shadow-xs">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                        CEO Multi-Company Overview &amp; Analytics
                                    </div>
                                @else
                                    <div class="flex flex-wrap gap-1.5 max-w-md">
                                        @forelse($group->getNavKeys() as $navKey)
                                            @php
                                                $label = Str::of($navKey)->replace('_', ' ')->title()->toString();
                                            @endphp
                                            <span class="px-2 py-0.5 rounded-full bg-[#fdf2f4] text-[#c3122e] text-[11px] font-bold border border-[#f8d7da]">
                                                {{ $label }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-slate-400 italic">No page permissions assigned</span>
                                        @endforelse
                                    </div>
                                @endif
                            </td>

                            <!-- Email Notifications Badge -->
                            <td class="px-6 py-4 text-center">
                                @if($group->email_notifications_enabled)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>✉️ Active</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-500 text-xs font-medium">
                                        <span>Disabled</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="edit({{ $group->id }})"
                                        class="p-2 text-slate-400 hover:text-[#c3122e] hover:bg-[#fdf2f4] rounded-lg transition-colors cursor-pointer"
                                        title="Edit Group">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button wire:click="delete({{ $group->id }})"
                                        wire:confirm="Are you sure you want to delete this group? All assigned users will lose their group permissions."
                                        class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer"
                                        title="Delete Group">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-sm">
                                @if($search || $groupTypeFilter !== 'all')
                                    <div class="max-w-md mx-auto">
                                        <p class="font-bold text-slate-600 mb-1">No matching access groups found</p>
                                        <p class="text-xs text-slate-400 mb-3">No groups match your search "{{ $search }}".</p>
                                        <button type="button" wire:click="resetFilters" class="px-4 py-2 rounded-xl bg-[#c3122e] text-white text-xs font-bold shadow-sm cursor-pointer">Reset Search</button>
                                    </div>
                                @else
                                    No access groups found. Click <strong class="text-[#c3122e]">+ Add Access Group</strong> to create one.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

        <!-- Modal Form (Create / Edit Group) — 100% Fully Responsive -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-3 sm:p-4 md:p-6 overflow-y-auto"
             x-data
             @keydown.escape.window="$wire.set('showModal', false)">
            <div class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-100 w-full max-w-2xl md:max-w-3xl my-auto overflow-hidden transform transition-all max-h-[92vh] flex flex-col animate-in fade-in zoom-in-95 duration-150">
                
                <!-- Modal Header (Sticky Top) -->
                <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-slate-100 bg-[#f8fafc] flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-red-50 border border-red-100 flex items-center justify-center text-[#c3122e] font-bold text-lg flex-shrink-0">
                            👥
                        </div>
                        <div>
                            <h3 class="font-extrabold text-[#0f172a] text-base sm:text-lg tracking-tight">
                                {{ $editingId ? 'Edit Access Group' : 'Add New Access Group' }}
                            </h3>
                            <p class="text-[11px] sm:text-xs text-slate-400">Configure access level, sub-company permissions &amp; automated email dispatches.</p>
                        </div>
                    </div>
                    <button wire:click="$set('showModal', false)" 
                        class="text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 p-2 rounded-xl text-lg font-bold leading-none transition-colors cursor-pointer flex-shrink-0">
                        ✕
                    </button>
                </div>

                <!-- Form Content (Scrollable Middle) -->
                <div class="p-5 sm:p-6 md:p-7 overflow-y-auto space-y-5 flex-1">
                    <form wire:submit="save" id="groupForm" class="space-y-5">
                        
                        <!-- Group Level Type Radio Buttons (Responsive 1 col on mobile, 2 cols on tablet/desktop) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Group Access Level <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all {{ $groupType === 'individual' ? 'border-[#c3122e] bg-[#fdf2f4]/60 ring-2 ring-[#c3122e]/20 shadow-sm' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/50' }}">
                                    <input wire:model.live="groupType" type="radio" value="individual" class="mt-1 text-[#c3122e] focus:ring-[#c3122e] w-4 h-4">
                                    <div class="min-w-0">
                                        <p class="text-xs sm:text-sm font-extrabold text-[#0f172a] flex items-center gap-1.5">
                                            <span>🏢 Individual Sub-Company</span>
                                        </p>
                                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">Assigned to a single Sub-Company with granular page permissions.</p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all {{ $groupType === 'group' ? 'border-[#c3122e] bg-[#fdf2f4]/60 ring-2 ring-[#c3122e]/20 shadow-sm' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/50' }}">
                                    <input wire:model.live="groupType" type="radio" value="group" class="mt-1 text-[#c3122e] focus:ring-[#c3122e] w-4 h-4">
                                    <div class="min-w-0">
                                        <p class="text-xs sm:text-sm font-extrabold text-[#0f172a] flex items-center gap-1.5">
                                            <span>🏢+ Multi-Company (CEO / Executive)</span>
                                        </p>
                                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">Assigned to multiple Sub-Companies with executive overview &amp; comparison access.</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Group Name -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Group Name <span class="text-red-500">*</span>
                            </label>
                            <input wire:model="name" type="text"
                                placeholder="{{ $groupType === 'group' ? 'e.g. Executive Board, Group Treasury, CEO Level' : 'e.g. Finance, Cashier, Operations, General User' }}"
                                required
                                class="w-full px-4 py-2.5 sm:py-3 rounded-xl sm:rounded-2xl border border-slate-200 text-xs sm:text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] transition-all">
                            @error('name') <p class="text-xs text-red-500 mt-1.5 font-semibold">{{ $message }}</p> @enderror
                        </div>

                        <!-- 1. INDIVIDUAL SUB-COMPANY FORM -->
                        @if($groupType === 'individual')
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Assign Sub-Company <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model.live="companyId" required
                                        class="w-full px-4 py-2.5 sm:py-3 rounded-xl sm:rounded-2xl border border-slate-200 text-xs sm:text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white cursor-pointer transition-all">
                                        <option value="">-- Select Sub-Company --</option>
                                        @foreach($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('companyId') <p class="text-xs text-red-500 mt-1.5 font-semibold">{{ $message }}</p> @enderror
                                </div>

                                <!-- Navigation Permissions (Responsive Grid of Cards) -->
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                            Navigation Permissions (Blade Views)
                                        </label>
                                        @if($companyId && count($availableNavPages) > 0)
                                            <span class="text-[11px] font-semibold text-slate-400">
                                                {{ count($selectedNavKeys) }} of {{ count($availableNavPages) }} selected
                                            </span>
                                        @endif
                                    </div>

                                    @if($companyId && count($availableNavPages) > 0)
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-60 overflow-y-auto p-3 rounded-2xl border border-slate-200 bg-slate-50/60">
                                            @foreach($availableNavPages as $navKey => $navLabel)
                                                @php
                                                    $isChecked = in_array($navKey, $selectedNavKeys);
                                                @endphp
                                                <label class="flex items-center gap-3 p-2.5 rounded-xl border transition-all cursor-pointer {{ $isChecked ? 'border-[#c3122e] bg-[#fdf2f4]/80 ring-1 ring-[#c3122e]/30 shadow-xs' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                                                    <input wire:model="selectedNavKeys" type="checkbox" value="{{ $navKey }}"
                                                        class="rounded text-[#c3122e] focus:ring-[#c3122e] w-4 h-4 flex-shrink-0">
                                                    <div class="min-w-0 flex-1 truncate">
                                                        <p class="text-xs font-bold text-[#0f172a] truncate">{{ $navLabel }}</p>
                                                        <p class="text-[10px] text-slate-400 font-mono truncate">{{ $navKey }}</p>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif($companyId)
                                        <div class="p-3.5 rounded-2xl border border-amber-200 bg-amber-50 text-amber-800 text-xs font-medium">
                                            No custom views found in <span class="font-mono">views/livewire/tenant/{{ $companies->find($companyId)->slug ?? '' }}/</span>. Default navigation keys will apply.
                                        </div>
                                    @else
                                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 text-center text-xs text-slate-400 italic">
                                            👉 Select a Sub-Company above to configure allowed blade views and pages for this group.
                                        </div>
                                    @endif
                                </div>
                            </div>

                        <!-- 2. MULTI-COMPANY (GROUP / CEO) FORM -->
                        @else
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        Select Sub-Companies for this Group <span class="text-red-500">*</span>
                                    </label>
                                    <button type="button" wire:click="toggleSelectAllCompanies"
                                        class="px-2.5 py-1 rounded-lg bg-red-50 text-xs text-[#c3122e] hover:bg-red-100 font-bold transition-colors cursor-pointer">
                                        {{ count($selectedCompanyIds) === $companies->count() ? '✕ Deselect All' : '✓ Select All (' . $companies->count() . ')' }}
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto p-3 rounded-2xl border border-slate-200 bg-slate-50/60">
                                    @foreach($companies as $company)
                                        @php
                                            $isCompChecked = in_array((string)$company->id, $selectedCompanyIds);
                                        @endphp
                                        <label class="flex items-center justify-between p-2.5 rounded-xl border transition-all cursor-pointer {{ $isCompChecked ? 'border-[#c3122e] bg-[#fdf2f4]/80 ring-1 ring-[#c3122e]/30 shadow-xs' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                                            <div class="flex items-center gap-2.5 min-w-0 flex-1 truncate">
                                                <input wire:model.live="selectedCompanyIds" type="checkbox" value="{{ (string)$company->id }}"
                                                    class="rounded text-[#c3122e] focus:ring-[#c3122e] w-4 h-4 flex-shrink-0">
                                                <div class="truncate">
                                                    <p class="text-xs font-bold text-[#0f172a] truncate">{{ $company->name }}</p>
                                                    <p class="text-[10px] text-slate-400 font-mono">/{{ $company->slug }}</p>
                                                </div>
                                            </div>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-600 flex-shrink-0 font-mono ml-2">
                                                {{ $company->banks->count() }} Banks
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('selectedCompanyIds') <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p> @enderror

                                <!-- Info Banner -->
                                <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <div>
                                        <p class="font-extrabold">Multi-Company Group Privileges:</p>
                                        <p class="text-amber-800 text-[11px] mt-0.5 leading-relaxed">Users in this group can view the Group Executive Overview Dashboard, Comparison Analytics, and individual executive views for the selected sub-companies.</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Universal Email Notifications Checkbox -->
                        <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/80 hover:bg-slate-100 transition-colors">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input wire:model="emailNotificationsEnabled" type="checkbox"
                                    class="mt-0.5 w-4 h-4 rounded border-slate-300 text-[#c3122e] focus:ring-[#c3122e]">
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold text-[#0f172a] flex flex-wrap items-center gap-1.5">
                                        <span>✉️ Enable Automated Email Notifications for this Group</span>
                                        @if($emailNotificationsEnabled)
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[9px] font-black uppercase">Active</span>
                                        @endif
                                    </p>
                                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                        When checked, active users in this group will receive automatic email reminders for facility expiries, working capital due dates &amp; loan reviews for their accessible companies.
                                    </p>
                                </div>
                            </label>
                        </div>

                    </form>
                </div>

                <!-- Modal Footer (Sticky Bottom Actions) -->
                <div class="flex items-center justify-end gap-3 px-5 sm:px-6 py-4 border-t border-slate-100 bg-[#f8fafc] flex-shrink-0">
                    <button type="button" wire:click="$set('showModal', false)"
                        class="px-4 sm:px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs sm:text-sm font-bold transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" form="groupForm"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs sm:text-sm font-extrabold transition-all shadow-md shadow-[#c3122e]/25 cursor-pointer">
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