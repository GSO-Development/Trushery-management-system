<?php

use App\Models\Bank;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    public string $name = '';
    public string $slug = '';
    public array $selectedBanks = [];
    public array $selectedAccounts = [];
    public bool $showModal = false;
    public ?int $editingId = null;

    // Azure Tenant Company Suggestions state
    public array $companySuggestions = [];
    public bool $companySearching = false;
    public ?array $selectedTenantCompany = null;

    // Add User Modal state (triggered from Company Users hover popover)
    public bool $showUserModal = false;
    public string $userName = '';
    public string $userEmail = '';
    public string $userAuthProvider = 'microsoft';
    public ?int $userCompanyId = null;
    public ?int $userGroupId = null;
    public bool $userIsAdmin = false;
    public bool $userIsCeo = false;

    // Azure Search State for Add User Modal
    public string $userAzureQuery = '';
    public array $userAzureUsers = [];
    public bool $userAzureSearching = false;
    public ?array $userSelectedAzureUser = null;

    // Modal 2-Column Split Bank Search & Selection State
    public string $modalBankSearch = '';
    public ?int $activeModalBankId = null;

    public function updatedName(): void
    {
        if (! $this->editingId) {
            $this->slug = Str::slug($this->name);
        }

        $this->companySearching = true;
        if (strlen(trim($this->name)) >= 2) {
            try {
                $service = new MicrosoftGraphService();
                $this->companySuggestions = $service->searchDepartments($this->name);
            } catch (\Throwable $e) {
                $this->companySuggestions = [];
            }
        } else {
            $this->companySuggestions = [];
        }
        $this->companySearching = false;
    }

    public function selectTenantCompany(string $name, string $slug): void
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->companySuggestions = [];
        $this->selectedTenantCompany = [
            'name' => $name,
            'slug' => $slug,
        ];
    }

    public function openAddUserModal(?int $companyId = null): void
    {
        $this->reset('userName', 'userEmail', 'userCompanyId', 'userGroupId', 'userAzureQuery', 'userAzureUsers', 'userSelectedAzureUser');
        $this->userCompanyId    = $companyId;
        $this->userAuthProvider = 'microsoft';
        $this->userIsAdmin      = false;
        $this->userIsCeo        = false;
        $this->showUserModal    = true;
    }

    public function updatedUserAzureQuery(): void
    {
        $this->userAzureSearching = true;
        if (strlen(trim($this->userAzureQuery)) >= 2) {
            $service = new MicrosoftGraphService();
            $this->userAzureUsers = $service->searchUsers($this->userAzureQuery);
        } else {
            $this->userAzureUsers = [];
        }
        $this->userAzureSearching = false;
    }

    public function selectUserAzureUser(string $email, string $name, string $jobTitle = '', string $department = ''): void
    {
        $this->userEmail = strtolower($email);
        $this->userName  = $name;
        $this->userAzureQuery = strtolower($email);
        $this->userAzureUsers = [];
        $this->userSelectedAzureUser = [
            'name'       => $name,
            'email'      => strtolower($email),
            'jobTitle'   => $jobTitle,
            'department' => $department,
        ];
    }

    public function clearSelectedUserAzureUser(): void
    {
        $this->userSelectedAzureUser = null;
        $this->userAzureQuery = '';
        $this->userAzureUsers = [];
        $this->userEmail = '';
        $this->userName = '';
    }

    public function saveNewUser(): void
    {
        $this->validate([
            'userEmail' => 'required|email|max:255|unique:users,email',
        ]);

        $name = $this->userName ?: explode('@', $this->userEmail)[0];
        User::create([
            'name'          => Str::title(str_replace('.', ' ', $name)),
            'email'         => $this->userEmail,
            'company_id'    => $this->userCompanyId,
            'group_id'      => $this->userGroupId,
            'is_admin'      => $this->userIsAdmin,
            'is_ceo'        => $this->userIsCeo,
            'auth_provider' => $this->userAuthProvider,
            'password'      => Str::random(32),
        ]);

        $this->showUserModal = false;
        session()->flash('success', 'User created and assigned to company successfully.');
    }

    public string $searchCompany = '';

    public function with(): array
    {
        return [
            'companies' => Company::with(['banks', 'users'])
                ->withCount(['users', 'groups'])
                ->when($this->searchCompany, fn($q) => $q->where('name', 'like', "%{$this->searchCompany}%")
                    ->orWhere('slug', 'like', "%{$this->searchCompany}%"))
                ->latest()
                ->get(),
            'allBanks'  => Bank::where('is_active', true)->with('companyBankAccounts')->orderBy('name')->get(),
        ];
    }

    public function openCreate(): void
    {
        $this->reset('name', 'slug', 'selectedBanks', 'selectedAccounts', 'editingId', 'companySuggestions', 'selectedTenantCompany', 'modalBankSearch', 'activeModalBankId');
        $this->activeModalBankId = Bank::where('is_active', true)->first()?->id;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $company               = Company::with(['banks', 'companyBankAccounts'])->findOrFail($id);
        $this->editingId       = $company->id;
        $this->name            = $company->name;
        $this->slug            = $company->slug;
        $this->selectedBanks   = $company->banks->pluck('id')->map(fn($id) => (int)$id)->toArray();
        $this->selectedAccounts= CompanyBankAccount::where('company_id', $company->id)->pluck('id')->map(fn($id) => (int)$id)->toArray();
        $this->companySuggestions = [];
        $this->selectedTenantCompany = null;
        $this->modalBankSearch = '';
        $this->activeModalBankId = $this->selectedBanks[0] ?? (Bank::where('is_active', true)->first()?->id);
        $this->showModal       = true;
    }

    public function save(): void
    {
        // Auto-sanitize slug: lowercase, replace spaces/special chars with hyphens, keep only a-z 0-9 - _ .
        $this->slug = strtolower(trim($this->slug));
        $this->slug = preg_replace("/\s+/", "-", $this->slug);
        $this->slug = preg_replace("/[^a-z0-9\-_.]/", "", $this->slug);

        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:companies,slug,' . ($this->editingId ?? 'NULL') . '|regex:/^[a-z0-9\-_.]+$/',
        ]);

        if ($this->editingId) {
            $company = Company::findOrFail($this->editingId);
            $company->update(['name' => $this->name, 'slug' => $this->slug]);
        } else {
            $company = Company::create(['name' => $this->name, 'slug' => $this->slug]);
        }

        // Sync assigned banks in company_bank pivot table
        $company->banks()->sync(array_map('intval', $this->selectedBanks));

        // Assign selected bank accounts to this company
        if (! empty($this->selectedAccounts)) {
            CompanyBankAccount::whereIn('id', array_map('intval', $this->selectedAccounts))->update(['company_id' => $company->id]);
        }

        // Auto-scaffold tenant view folder for this company
        $company->scaffoldViewFolder();

        $this->reset('name', 'slug', 'selectedBanks', 'selectedAccounts', 'editingId');
        $this->showModal = false;
        session()->flash('success', 'Company saved and bank assignments updated successfully.');
    }

    public function delete(int $id): void
    {
        Company::findOrFail($id)->delete();
        session()->flash('success', 'Company deleted.');
    }
}; ?>

<div>
    @slot('header') Sub-Companies Management @endslot

    <!-- Top Action Row (Add Sub-Company Button placed ABOVE table/filter) -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-lg font-bold text-[#0f172a]">Sub-Companies</h2>
            <p class="text-xs text-slate-500">Manage corporate entities, assigned bank master lists, and active accounts.</p>
        </div>
        <button wire:click="openCreate"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-sm font-semibold whitespace-nowrap shrink-0 transition-all shadow-sm shadow-[#c3122e]/20">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>+ Add Sub-Company</span>
        </button>
    </div>

    @if(session('success'))
        <div class="mb-5 p-3.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex flex-col sm:flex-row items-center justify-between px-6 py-4 border-b border-slate-50 gap-3">
            <div class="flex items-center gap-3 w-full">
                <h3 class="font-bold text-[#0f172a] text-sm whitespace-nowrap">All Registered Sub-Companies</h3>
                <div class="relative flex-1 max-w-xs">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="searchCompany" type="text"
                        placeholder="Search company name or slug..."
                        class="pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] w-full transition-all">
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-[#f8fafc]">
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Company</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Slug</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned Banks</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Users (Hover for Details)</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($companies as $company)
                        <tr class="hover:bg-[#fdf2f4]/30 transition-colors">
                            <td class="px-6 py-4 font-medium text-[#0f172a]">{{ $company->name }}</td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-500 bg-slate-50/50">/{{ $company->slug }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($company->banks as $bank)
                                        <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold border border-blue-100">
                                            {{ $bank->name }}
                                        </span>
                                    @empty
                                        <span class="text-slate-400 text-xs italic">No banks assigned</span>
                                    @endforelse
                                </div>
                            </td>

                            <!-- Users Hover Popover Cell -->
                            <td class="px-6 py-4 relative" x-data="{ openHover: false }">
                                <div class="relative inline-block" @mouseenter="openHover = true" @mouseleave="openHover = false">
                                    <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-[#fdf2f4] hover:text-[#c3122e] transition-colors cursor-pointer border border-slate-200">
                                        <span>👥 {{ $company->users_count }} {{ Str::plural('User', $company->users_count) }}</span>
                                        <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>

                                    <!-- Hover Popover List -->
                                    <div x-show="openHover" x-cloak
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                         class="absolute left-0 z-50 mt-1 w-80 bg-white rounded-2xl shadow-2xl border border-slate-200 p-3.5 space-y-3 text-left">

                                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                            <div>
                                                <p class="text-xs font-bold text-[#0f172a]">{{ $company->name }}</p>
                                                <p class="text-[10px] text-slate-400">Assigned Company Users</p>
                                            </div>
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-bold font-mono">{{ $company->users_count }} Total</span>
                                        </div>

                                        @if($company->users->count() > 0)
                                            <div class="max-h-48 overflow-y-auto space-y-2 divide-y divide-slate-50 pr-1">
                                                @foreach($company->users as $u)
                                                    <div class="pt-1.5 flex items-center justify-between text-xs">
                                                        <div class="truncate max-w-[190px]">
                                                            <p class="font-semibold text-slate-800 truncate">{{ $u->name }}</p>
                                                            <p class="text-[10px] text-slate-400 font-mono truncate">{{ $u->email }}</p>
                                                        </div>
                                                        <span class="text-[9px] px-1.5 py-0.5 rounded font-semibold {{ $u->auth_provider === 'microsoft' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600' }}">
                                                            {{ ucfirst($u->auth_provider ?? 'local') }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-xs text-slate-400 italic py-2 text-center">No users assigned to {{ $company->name }} yet.</p>
                                        @endif

                                        <div class="pt-2 border-t border-slate-100">
                                            <button type="button" wire:click="openAddUserModal({{ $company->id }})"
                                                class="w-full py-2 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white font-semibold text-xs transition-colors flex items-center justify-center gap-1.5 shadow-sm shadow-[#c3122e]/20 cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                + Add User to {{ $company->name }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="edit({{ $company->id }})"
                                        class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:border-[#c3122e] hover:text-[#c3122e] text-xs font-medium transition-colors">
                                        Edit &amp; Banks
                                    </button>
                                    <button wire:click="delete({{ $company->id }})"
                                        wire:confirm="Are you sure you want to delete {{ $company->name }}?"
                                        class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-400 hover:border-red-200 hover:text-red-500 text-xs font-medium transition-colors">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm">
                                No companies created yet. <button wire:click="openCreate" class="text-[#c3122e] underline font-semibold">Create first company</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Responsive Company Modal Popup ────────────────────────── -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div style="background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.3);width:min(800px,calc(100vw - 40px));max-height:90vh;display:flex;flex-direction:column;overflow:hidden;">
                
                <!-- Modal Header (Compact) -->
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 flex-shrink-0 bg-white">
                    <h3 class="font-bold text-[#0f172a] text-base">
                        {{ $editingId ? 'Edit Sub-Company & Bank Assignment' : 'Add New Sub-Company' }}
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 transition-colors p-1 rounded-lg hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body (Compact padding & spacing) -->
                <div class="p-5 space-y-4 flex-shrink-0">
                    <form wire:submit="save" id="companyForm" class="space-y-4">
                        
                        <!-- Top Inputs: Company Name & Company Slug -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="relative">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                                    COMPANY NAME <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input wire:model.live.debounce.300ms="name" type="text" placeholder="e.g. Health, Travels, Citrus" required
                                        class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white text-slate-800 font-medium">
                                    <div wire:loading wire:target="name" class="absolute right-3 top-1/2 -translate-y-1/2">
                                        <svg class="animate-spin h-3.5 w-3.5 text-[#c3122e]" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </div>
                                </div>
                                @error('name') <p class="text-[11px] text-red-500 font-semibold mt-0.5">{{ $message }}</p> @enderror

                                <!-- Azure Tenant Suggestions Dropdown -->
                                @if(count($companySuggestions) > 0)
                                    <div class="absolute z-50 left-0 right-0 mt-1 bg-white rounded-xl shadow-2xl border border-blue-200 max-h-36 overflow-y-auto divide-y divide-slate-100">
                                        <div class="px-3 py-1 bg-blue-50/80 text-[9px] font-bold uppercase text-blue-700">
                                            Matching Azure AD Entities ({{ count($companySuggestions) }})
                                        </div>
                                        @foreach($companySuggestions as $cs)
                                            <button type="button" wire:click="selectTenantCompany('{{ addslashes($cs['name']) }}', '{{ $cs['slug'] }}')"
                                                class="w-full px-3 py-1.5 text-left hover:bg-blue-50/80 transition-colors flex items-center justify-between group cursor-pointer">
                                                <div>
                                                    <p class="font-bold text-slate-800 text-xs group-hover:text-blue-600 transition-colors">{{ $cs['name'] }}</p>
                                                    <p class="text-[10px] text-slate-400 font-mono">Slug: {{ $cs['slug'] }}</p>
                                                </div>
                                                <span class="text-[10px] font-semibold text-blue-600 opacity-0 group-hover:opacity-100 transition-opacity">Select</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                                    COMPANY SLUG <span class="text-red-500">*</span>
                                </label>
                                <input wire:model="slug" type="text" placeholder="e.g. health" required
                                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-slate-50/50 font-mono text-slate-800">
                                @error('slug') <p class="text-[11px] text-red-500 font-semibold mt-0.5">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        @if($selectedTenantCompany)
                            <div class="p-2 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-200 text-emerald-800 font-bold text-[9px]">Verified Azure Entity</span>
                                    <span class="font-bold text-emerald-950 text-xs">{{ $selectedTenantCompany['name'] }}</span>
                                    <span class="text-emerald-700 font-mono text-[10px]">({{ $selectedTenantCompany['slug'] }})</span>
                                </div>
                            </div>
                        @endif

                        <!-- Compact 2-Column Split Box for Banks & Accounts -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50/30 shadow-sm flex flex-col">
                            
                            <!-- Header inside box -->
                            <div class="px-4 py-2 bg-slate-100/90 border-b border-slate-200 flex items-center justify-between flex-shrink-0">
                                <label class="text-[10px] font-bold text-slate-700 uppercase tracking-wider">
                                    AVAILABLE BANKS FOR THIS SUB-COMPANY & ACCOUNTS
                                </label>
                                <span class="text-[10px] text-slate-400 font-medium">
                                    Select Banks on Left <span class="text-slate-600 font-bold">➔</span> View Accounts on Right
                                </span>
                            </div>

                            <div style="display:flex;flex-direction:row;height:280px;overflow:hidden;">
                                
                                <!-- LEFT COLUMN: Scrollable Banks List (Width: 42%) -->
                                <div style="width:42%;padding:10px;background:#fff;display:flex;flex-direction:column;height:100%;border-right:1px solid #e2e8f0;min-height:0;box-sizing:border-box;">
                                    <!-- Search input -->
                                    <div class="relative flex-shrink-0 mb-1.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                        <input wire:model.live.debounce.200ms="modalBankSearch" type="text"
                                            placeholder="Search bank name or code..."
                                            class="w-full pl-8 pr-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:outline-none focus:ring-1 focus:ring-[#c3122e] bg-slate-50/50">
                                    </div>

                                    @php
                                        $searchQ = strtolower(trim($modalBankSearch));
                                        $filteredModalBanks = $allBanks->filter(function($b) use ($searchQ) {
                                            if (!$searchQ) return true;
                                            return str_contains(strtolower($b->name), $searchQ) || str_contains(strtolower($b->bank_code), $searchQ);
                                        })->sortBy(function($b) {
                                            $isSelected = in_array((int)$b->id, array_map('intval', $this->selectedBanks));
                                            return $isSelected ? 0 : 1;
                                        });
                                    @endphp

                                    <!-- Scrollable Banks Cards list -->
                                    <div style="overflow-y:auto;flex:1;padding-right:4px;min-height:0;" class="space-y-1">
                                        @forelse($filteredModalBanks as $bank)
                                            @php
                                                $isSelected = in_array((int)$bank->id, array_map('intval', $selectedBanks));
                                                $isActive   = $activeModalBankId === $bank->id;
                                            @endphp
                                            <div wire:key="modal-bank-{{ $bank->id }}"
                                                wire:click="$set('activeModalBankId', {{ $bank->id }})"
                                                class="p-2 rounded-xl border transition-all cursor-pointer flex items-center justify-between group {{ $isActive ? 'border-red-500 ring-1 ring-red-500 bg-white shadow-sm' : ($isSelected ? 'border-emerald-300 bg-emerald-50/20' : 'border-slate-200 bg-white hover:border-slate-300') }}">
                                                
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <input wire:model.live="selectedBanks" type="checkbox" value="{{ $bank->id }}"
                                                        @click.stop
                                                        class="rounded border-slate-300 text-[#c3122e] focus:ring-[#c3122e] w-3.5 h-3.5 cursor-pointer">
                                                    <div class="truncate">
                                                        <p class="text-xs font-bold text-[#0f172a] truncate leading-tight">{{ $bank->name }}</p>
                                                        <p class="text-[9px] text-slate-400 font-mono mt-0.5">Code: {{ $bank->bank_code }}</p>
                                                    </div>
                                                </div>

                                                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-600 transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7-7 7"/>
                                                </svg>
                                            </div>
                                        @empty
                                            <p class="text-xs text-slate-400 italic py-6 text-center">No matching banks found.</p>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- RIGHT COLUMN: Accounts for Selected Bank ONLY (Width: 58%) -->
                                <div style="width:58%;padding:10px;background:#f8fafc;display:flex;flex-direction:column;height:100%;min-height:0;box-sizing:border-box;">
                                    @php
                                        $selectedBankModel = \App\Models\Bank::with('companyBankAccounts')->find($activeModalBankId);
                                    @endphp

                                    @if($selectedBankModel)
                                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/80 mb-2 flex-shrink-0">
                                            <div>
                                                <p class="text-xs font-bold text-[#0f172a] flex items-center gap-1">
                                                    <span>🏢</span>
                                                    <span>{{ $selectedBankModel->name }}</span>
                                                    <span class="font-mono text-xs text-red-600 font-bold">({{ $selectedBankModel->bank_code }})</span>
                                                </p>
                                                <p class="text-[10px] text-slate-500">Select account numbers to assign to this company:</p>
                                            </div>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-800 flex-shrink-0">
                                                {{ $selectedBankModel->companyBankAccounts->count() }} Accounts
                                            </span>
                                        </div>

                                        @if($selectedBankModel->companyBankAccounts->count() > 0)
                                            <!-- Scrollable Account Items -->
                                            <div style="overflow-y:auto;flex:1;padding-right:4px;min-height:0;" class="space-y-1.5">
                                                @foreach($selectedBankModel->companyBankAccounts as $acct)
                                                    @php
                                                        $isAcctAssigned = in_array((int)$acct->id, array_map('intval', $selectedAccounts)) || $acct->company_id === $editingId;
                                                    @endphp
                                                    <label wire:key="modal-acct-{{ $acct->id }}" class="flex items-center justify-between p-2 rounded-xl border transition-all cursor-pointer {{ $isAcctAssigned ? 'border-emerald-500 bg-emerald-50/50 ring-1 ring-emerald-300' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                                                        <div class="flex items-center gap-2 min-w-0">
                                                            <input wire:model="selectedAccounts" type="checkbox" value="{{ $acct->id }}"
                                                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-3.5 h-3.5 cursor-pointer">
                                                            <div class="truncate">
                                                                <p class="font-mono font-bold text-xs text-[#0f172a] tracking-wider truncate">{{ $acct->account_number }}</p>
                                                                <p class="text-[9px] text-slate-500 truncate">{{ $acct->account_type }} {{ $acct->company ? '• Assigned to '.$acct->company->name : '' }}</p>
                                                            </div>
                                                        </div>
                                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded flex-shrink-0 {{ $acct->currency === 'USD' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700' }}">
                                                            {{ $acct->currency }}
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @else
                                            <!-- Empty state box -->
                                            <div class="my-auto p-5 text-center bg-white rounded-xl border border-dashed border-slate-200 flex flex-col items-center justify-center">
                                                <p class="text-xs text-slate-500 italic">
                                                    No registered accounts found for <strong>{{ $selectedBankModel->name }}</strong>.
                                                </p>
                                                <p class="text-[10px] text-slate-400 italic mt-1">
                                                    Go to <strong>Banks Management</strong> tab to add accounts.
                                                </p>
                                            </div>
                                        @endif
                                    @else
                                        <div class="my-auto text-center text-slate-400 text-xs italic">
                                            Select a bank from the left list to view and assign its account numbers.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </form>
                </div>

                <!-- Modal Footer (Compact) -->
                <div class="flex items-center justify-end gap-2.5 px-5 py-3 border-t border-slate-100 bg-white flex-shrink-0">
                    <button type="button" wire:click="$set('showModal', false)"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" form="companyForm"
                        class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-semibold transition-colors shadow-sm shadow-[#c3122e]/20">
                        <svg wire:loading wire:target="save" class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ $editingId ? 'Save Changes' : 'Create Company' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
    @if($showUserModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-[#f8fafc] flex-shrink-0">
                    <h3 class="font-bold text-[#0f172a] text-base">
                        Add New User &amp; Assign Company
                    </h3>
                    <button wire:click="$set('showUserModal', false)" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto space-y-4 flex-1">
                    <form wire:submit="saveNewUser" id="addUserForm" class="space-y-4">
                        
                        <!-- Company Assigned -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Assigned Sub-Company</label>
                            <select wire:model="userCompanyId" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white font-medium">
                                <option value="">Select Company</option>
                                @foreach($companies as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Azure AD Live User Search -->
                        <div class="space-y-3 p-4 rounded-xl bg-blue-50/70 border border-blue-100 relative">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-blue-900 uppercase tracking-wider">
                                    🔍 Search Azure AD Directory <span class="text-red-500">*</span>
                                </label>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">
                                    🔷 Live Azure Validation
                                </span>
                            </div>

                            <div class="relative">
                                <input wire:model.live.debounce.300ms="userAzureQuery" type="text"
                                    placeholder="Type user name or email (e.g. achala, aaron)..."
                                    class="w-full pl-9 pr-8 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white font-medium shadow-sm">
                                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>

                            <!-- Suggestions Dropdown -->
                            @if(count($userAzureUsers) > 0)
                                <div class="absolute z-50 left-0 right-0 mt-1 bg-white rounded-xl shadow-2xl border border-blue-200 max-h-52 overflow-y-auto divide-y divide-slate-100">
                                    @foreach($userAzureUsers as $u)
                                        <button type="button" wire:click="selectUserAzureUser('{{ $u['email'] }}', '{{ addslashes($u['name']) }}', '{{ addslashes($u['jobTitle'] ?? '') }}', '{{ addslashes($u['department'] ?? '') }}')"
                                            class="w-full px-4 py-2.5 text-left hover:bg-blue-50/80 transition-colors flex items-center justify-between group cursor-pointer">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-slate-800 text-xs group-hover:text-blue-600 transition-colors">{{ $u['name'] }}</span>
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 font-semibold">✓ Verified Azure AD</span>
                                                </div>
                                                <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $u['email'] }}</p>
                                            </div>
                                            <span class="text-xs font-semibold text-blue-600 opacity-0 group-hover:opacity-100 transition-opacity">Select →</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            @if($userSelectedAzureUser)
                                <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="px-1.5 py-0.5 rounded bg-emerald-200 text-emerald-800 font-bold text-[10px]">✓ Verified</span>
                                        <span class="font-bold text-emerald-950">{{ $userSelectedAzureUser['name'] }}</span>
                                        <span class="text-emerald-700 font-mono text-[11px]">({{ $userSelectedAzureUser['email'] }})</span>
                                    </div>
                                    <button type="button" wire:click="clearSelectedUserAzureUser" class="text-xs text-slate-400 hover:text-red-500 font-semibold">✕</button>
                                </div>
                            @else
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Target Email <span class="text-red-500">*</span></label>
                                        <input wire:model="userEmail" type="email" placeholder="user@company.com" required
                                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white font-mono">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name</label>
                                        <input wire:model="userName" type="text" placeholder="User Name"
                                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white">
                                    </div>
                                </div>
                            @endif
                        </div>

                    </form>
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-[#f8fafc] flex-shrink-0">
                    <button type="button" wire:click="$set('showUserModal', false)"
                        class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-sm font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" form="addUserForm"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-sm font-semibold transition-colors shadow-sm shadow-[#c3122e]/20">
                        Create User &amp; Assign
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
