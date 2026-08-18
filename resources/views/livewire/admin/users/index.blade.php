<?php

use App\Models\Company;
use App\Models\Group;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterCompany = '';
    public string $filterGroup = '';

    // Modal state
    public bool $showModal = false;
    public ?int $userId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $passwordConfirmation = '';
    public string $authProvider = 'microsoft'; // 'microsoft' or 'local'
    public ?int $companyId = null;
    public ?int $groupId = null;
    public bool $isAdmin = false;
    public bool $isCeo = false;
    public array $ceoCompanyIds = []; // multi-company for CEO

    // Azure AD Tenant search state
    public string $azureQuery = '';
    public array $azureUsers = [];
    public bool $azureSearching = false;
    public ?array $selectedAzureUser = null;
    public bool $azureValidated = false;

    public function updatedAzureQuery(): void
    {
        $this->azureSearching = true;
        if (strlen(trim($this->azureQuery)) >= 2) {
            $service = new MicrosoftGraphService();
            $this->azureUsers = $service->searchUsers($this->azureQuery);
        } else {
            $this->azureUsers = [];
        }
        $this->azureSearching = false;
    }

    public function selectAzureUser(string $email, string $name, string $jobTitle = '', string $department = ''): void
    {
        $this->email = strtolower($email);
        $this->name  = $name;
        $this->azureQuery = strtolower($email);
        $this->azureUsers = [];
        $this->azureValidated = true;
        $this->selectedAzureUser = [
            'name'       => $name,
            'email'      => strtolower($email),
            'jobTitle'   => $jobTitle,
            'department' => $department,
        ];

        // Auto-match company by department name if available
        if (! empty($department)) {
            $matched = Company::all()->first(function ($c) use ($department) {
                $deptLower     = strtolower($department);
                $compNameLower = strtolower($c->name);
                $compSlugLower = strtolower($c->slug);
                return str_contains($deptLower, $compNameLower)
                    || str_contains($deptLower, $compSlugLower)
                    || str_contains($compNameLower, $deptLower);
            });
            if ($matched) {
                $this->companyId = $matched->id;
            }
        }
    }

    public function clearSelectedAzureUser(): void
    {
        $this->selectedAzureUser = null;
        $this->azureValidated = false;
        $this->azureQuery = '';
        $this->azureUsers = [];
        $this->email = '';
        $this->name = '';
    }

    public function with(): array
    {
        return [
            'users' => User::with(['company', 'group'])
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%"))
                ->when($this->filterCompany, fn($q) => $q->where('company_id', $this->filterCompany))
                ->when($this->filterGroup, fn($q) => $q->where('group_id', $this->filterGroup))
                ->latest()
                ->paginate(10),
            'companies'       => Company::all(),
            'groups'          => Group::when($this->companyId, fn($q) => $q->where('company_id', $this->companyId)->orWhere('group_type', 'group'))->orderBy('name')->get(),
            'allFilterGroups' => Group::with('company')->orderBy('name')->get(),
        ];
    }

    public function openCreate(): void
    {
        $this->reset('userId', 'name', 'email', 'password', 'passwordConfirmation', 'companyId', 'groupId', 'ceoCompanyIds', 'azureQuery', 'azureUsers', 'selectedAzureUser', 'azureValidated');
        $this->authProvider = 'microsoft';
        $this->isAdmin      = false;
        $this->isCeo        = false;
        $this->showModal    = true;
    }

    public function openEdit(int $id): void
    {
        $user                 = User::with('ceoCompanies')->findOrFail($id);
        $this->userId         = $user->id;
        $this->name           = $user->name;
        $this->email          = $user->email;
        $this->authProvider   = $user->auth_provider ?? 'local';
        $this->companyId      = $user->company_id;
        $this->groupId        = $user->group_id;
        $this->isAdmin        = $user->is_admin;
        $this->isCeo          = $user->is_ceo;
        $this->ceoCompanyIds  = $user->ceoCompanies->pluck('id')->map(fn($id) => (string)$id)->toArray();
        $this->password       = '';
        $this->passwordConfirmation = '';
        $this->azureQuery     = $user->email;
        $this->azureUsers     = [];
        $this->selectedAzureUser = null;
        $this->azureValidated = false;
        $this->showModal      = true;
    }

    public function saveUser(): void
    {
        $isCeo = $this->isCeo;

        if ($this->authProvider === 'microsoft') {
            $rules = [
                'email'   => 'required|email|max:255|unique:users,email,' . ($this->userId ?? 'NULL'),
                'isAdmin' => 'boolean',
                'isCeo'   => 'boolean',
            ];
            if (! $isCeo) {
                $rules['companyId'] = 'nullable|exists:companies,id';
                $rules['groupId']   = 'nullable|exists:groups,id';
            }
            $this->validate($rules);

            // Azure Department Mismatch Strict Check
            if ($this->selectedAzureUser && ! empty($this->selectedAzureUser['department']) && $this->companyId) {
                $assignedComp = Company::find($this->companyId);
                $dept         = $this->selectedAzureUser['department'];
                if ($assignedComp) {
                    $deptLower     = strtolower($dept);
                    $compNameLower = strtolower($assignedComp->name);
                    $compSlugLower = strtolower($assignedComp->slug);
                    $isMatch       = str_contains($deptLower, $compNameLower) || str_contains($deptLower, $compSlugLower) || str_contains($compNameLower, $deptLower);
                    if (! $isMatch) {
                        $this->addError('companyId', "Department Mismatch: This Azure AD user belongs to \"{$dept}\", but you selected \"{$assignedComp->name}\". Please select the correct company.");
                        return;
                    }
                }
            }

            $name = $this->name ?: explode('@', $this->email)[0];
            $data = [
                'name'          => Str::title(str_replace('.', ' ', $name)),
                'email'         => $this->email,
                'company_id'    => $isCeo ? null : $this->companyId,
                'group_id'      => $isCeo ? null : $this->groupId,
                'is_admin'      => $this->isAdmin,
                'is_ceo'        => $isCeo,
                'auth_provider' => 'microsoft',
            ];

            if (! $this->userId) {
                $data['password'] = Str::random(32);
            }
        } else {
            $rules = [
                'name'    => 'required|string|max:255',
                'email'   => 'required|email|max:255|unique:users,email,' . ($this->userId ?? 'NULL'),
                'isAdmin' => 'boolean',
                'isCeo'   => 'boolean',
            ];
            if (! $isCeo) {
                $rules['companyId'] = 'nullable|exists:companies,id';
                $rules['groupId']   = 'nullable|exists:groups,id';
            }

            if (! $this->userId || $this->password) {
                $rules['password']             = 'required|min:6';
                $rules['passwordConfirmation'] = 'same:password';
            }

            $this->validate($rules);

            $data = [
                'name'          => $this->name,
                'email'         => $this->email,
                'company_id'    => $isCeo ? null : $this->companyId,
                'group_id'      => $isCeo ? null : $this->groupId,
                'is_admin'      => $this->isAdmin,
                'is_ceo'        => $isCeo,
                'auth_provider' => 'local',
            ];

            if ($this->password) {
                $data['password'] = $this->password;
            }
        }

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $user->update($data);
            if ($isCeo) {
                $user->ceoCompanies()->sync(array_map('intval', $this->ceoCompanyIds));
            } else {
                $user->ceoCompanies()->sync([]);
            }
            session()->flash('success', 'User updated successfully.');
        } else {
            $user = User::create($data);
            if ($isCeo) {
                $user->ceoCompanies()->sync(array_map('intval', $this->ceoCompanyIds));
            }
            session()->flash('success', 'User created successfully.');
        }

        $this->showModal = false;
    }

    public function deleteUser(int $userId): void
    {
        User::findOrFail($userId)->delete();
        session()->flash('success', 'User deleted successfully.');
    }
}; ?>

<div>
    @slot('header') User Management @endslot

    <!-- Top Action Row (Add New User Button placed ABOVE filter bar) -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-bold text-[#0f172a]">User Accounts</h2>
            <p class="text-xs text-slate-500">Manage user logins, company assignments, and access group permissions.</p>
        </div>
        <button wire:click="openCreate"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-sm font-semibold whitespace-nowrap shrink-0 transition-all shadow-sm shadow-[#c3122e]/20">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>+ Add New User</span>
        </button>
    </div>

    <!-- Filter Bar (Search & Filter dropdowns below in separate row) -->
    <div class="flex flex-wrap items-center gap-3 mb-6 bg-white p-3 rounded-2xl border border-slate-100 shadow-sm">
        <div class="relative flex-1 min-w-[220px]">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text"
                placeholder="Search users by name or email..."
                class="pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] w-full transition-all">
        </div>
        
        <!-- Company Filter Dropdown -->
        <select wire:model.live="filterCompany"
            class="px-3.5 py-2 rounded-xl border border-slate-200 text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
            <option value="">All Companies</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}">{{ $company->name }}</option>
            @endforeach
        </select>

        <!-- Group Filter Dropdown -->
        <select wire:model.live="filterGroup"
            class="px-3.5 py-2 rounded-xl border border-slate-200 text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
            <option value="">All Access Groups</option>
            @foreach($allFilterGroups as $group)
                <option value="{{ $group->id }}">{{ $group->name }} {{ $group->company ? '('.$group->company->name.')' : '' }}</option>
            @endforeach
        </select>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <!-- Users Table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-[#f8fafc]">
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Company</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Group</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Auth</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($users as $user)
                        <tr class="hover:bg-[#fdf2f4]/30 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-[#c3122e] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-[#0f172a]">{{ $user->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $user->company->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $user->group->name ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $user->auth_provider === 'microsoft' ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($user->auth_provider ?? 'local') }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($user->is_admin)
                                    <span class="px-2.5 py-1 rounded-full bg-[#fdf2f4] text-[#c3122e] text-xs font-semibold border border-[#f8d7da]">Admin</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-medium">User</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="openEdit({{ $user->id }})"
                                       class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:border-[#c3122e] hover:text-[#c3122e] text-xs font-medium transition-colors">
                                        Edit
                                    </button>
                                    <button wire:click="deleteUser({{ $user->id }})"
                                        wire:confirm="Are you sure you want to delete {{ $user->name }}?"
                                        class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-400 hover:border-red-200 hover:text-red-500 text-xs font-medium transition-colors">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-sm">
                                No users found. <button wire:click="openCreate" class="text-[#c3122e] underline font-semibold">Create first user</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-50">
            {{ $users->links() }}
        </div>
    </div>

    <!-- ── Responsive User Form Modal Popup ────────────────────────── -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-xl my-8 overflow-hidden transform transition-all max-h-[90vh] flex flex-col">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-[#f8fafc] flex-shrink-0">
                    <h3 class="font-bold text-[#0f172a] text-base">
                        {{ $userId ? 'Edit User Account' : 'Add New User' }}
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto space-y-5 flex-1">
                    <form wire:submit="saveUser" id="userForm" class="space-y-5">

                        <!-- Authentication Type -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Authentication Type</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 cursor-pointer hover:border-[#c3122e] transition-all {{ $authProvider === 'microsoft' ? 'border-[#c3122e] bg-[#fdf2f4]/60 ring-2 ring-[#c3122e]/20' : 'bg-slate-50/50' }}">
                                    <input wire:model.live="authProvider" type="radio" value="microsoft" class="text-[#c3122e] focus:ring-[#c3122e]">
                                    <div>
                                        <p class="text-xs font-semibold text-[#0f172a] flex items-center gap-1.5">
                                            <svg width="12" height="12" viewBox="0 0 21 21"><rect x="1" y="1" width="9" height="9" fill="#f25022"/><rect x="11" y="1" width="9" height="9" fill="#7fba00"/><rect x="1" y="11" width="9" height="9" fill="#00a4ef"/><rect x="11" y="11" width="9" height="9" fill="#ffb900"/></svg>
                                            Microsoft SSO
                                        </p>
                                        <p class="text-[10px] text-slate-500">Only Email required</p>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 cursor-pointer hover:border-[#c3122e] transition-all {{ $authProvider === 'local' ? 'border-[#c3122e] bg-[#fdf2f4]/60 ring-2 ring-[#c3122e]/20' : 'bg-slate-50/50' }}">
                                    <input wire:model.live="authProvider" type="radio" value="local" class="text-[#c3122e] focus:ring-[#c3122e]">
                                    <div>
                                        <p class="text-xs font-semibold text-[#0f172a]">Local Password</p>
                                        <p class="text-[10px] text-slate-500">Name + Email + Password</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Microsoft Auth Fields -->
                        @if($authProvider === 'microsoft')
                            <div class="space-y-4 p-4 rounded-xl bg-blue-50/70 border border-blue-100 relative">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-blue-900 uppercase tracking-wider">
                                        🔍 Search Azure AD Directory <span class="text-red-500">*</span>
                                    </label>
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 flex items-center gap-1">
                                        🔷 Live Azure Tenant Validation
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500">
                                    Type user name or email to search verified Microsoft accounts in your George Steuart Azure Tenant:
                                </p>

                                <!-- Live Search Input -->
                                <div class="relative">
                                    <div class="relative">
                                        <input wire:model.live.debounce.300ms="azureQuery" type="text"
                                            placeholder="Type name or email (e.g. achala, aaron, @gshealth.lk)..."
                                            class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white font-medium shadow-sm">
                                        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                        <div wire:loading wire:target="azureQuery" class="absolute right-3.5 top-1/2 -translate-y-1/2">
                                            <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- Suggestions Dropdown -->
                                    @if(count($azureUsers) > 0)
                                        <div class="absolute z-50 left-0 right-0 mt-1 bg-white rounded-xl shadow-2xl border border-blue-200 max-h-60 overflow-y-auto divide-y divide-slate-100">
                                            <div class="px-3 py-1.5 bg-blue-50/80 text-[10px] font-bold uppercase text-blue-700 flex items-center justify-between">
                                                <span>Found {{ count($azureUsers) }} Verified Azure Tenant Accounts</span>
                                                <span class="text-[9px] font-normal text-slate-500">Click to auto-populate &amp; validate</span>
                                            </div>
                                            @foreach($azureUsers as $u)
                                                <button type="button" wire:click="selectAzureUser('{{ $u['email'] }}', '{{ addslashes($u['name']) }}', '{{ addslashes($u['jobTitle'] ?? '') }}', '{{ addslashes($u['department'] ?? '') }}')"
                                                    class="w-full px-4 py-2.5 text-left hover:bg-blue-50/80 transition-colors flex items-center justify-between group cursor-pointer">
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-bold text-slate-800 text-xs group-hover:text-blue-600 transition-colors">{{ $u['name'] }}</span>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 font-semibold">✓ Verified Azure AD</span>
                                                        </div>
                                                        <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $u['email'] }}</p>
                                                        @if(!empty($u['department']) || !empty($u['jobTitle']))
                                                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $u['jobTitle'] }} {{ !empty($u['department']) ? '· '.$u['department'] : '' }}</p>
                                                        @endif
                                                    </div>
                                                    <span class="text-xs font-semibold text-blue-600 opacity-0 group-hover:opacity-100 transition-opacity">Select →</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @elseif(strlen(trim($azureQuery)) >= 2 && !$azureSearching)
                                        <div class="absolute z-50 left-0 right-0 mt-1 bg-white p-3 rounded-xl shadow-lg border border-amber-200 text-center">
                                            <p class="text-xs font-semibold text-amber-800">⚠️ No matching user found in Azure AD Tenant</p>
                                            <p class="text-[11px] text-slate-500 mt-0.5">Please check spelling or verify the account in Microsoft Entra ID.</p>
                                        </div>
                                    @endif
                                </div>

                                <!-- Selected Azure User Card -->
                                @if($selectedAzureUser)
                                    <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs shadow-sm">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-emerald-600 text-white font-bold flex items-center justify-center text-xs flex-shrink-0">
                                                ✓
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <p class="font-bold text-emerald-950">{{ $selectedAzureUser['name'] }}</p>
                                                    <span class="px-1.5 py-0.5 rounded bg-emerald-200 text-emerald-800 font-bold text-[9px]">Verified Account</span>
                                                </div>
                                                <p class="text-emerald-700 font-mono text-xs">{{ $selectedAzureUser['email'] }}</p>
                                                @if(!empty($selectedAzureUser['department']) || !empty($selectedAzureUser['jobTitle']))
                                                    <p class="text-[10px] text-emerald-600">{{ $selectedAzureUser['jobTitle'] }} {{ !empty($selectedAzureUser['department']) ? '· '.$selectedAzureUser['department'] : '' }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <button type="button" wire:click="clearSelectedAzureUser" class="text-xs text-slate-400 hover:text-red-500 font-semibold px-2 py-1 cursor-pointer">
                                            Change ✕
                                        </button>
                                    </div>
                                @else
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-700 mb-1">Target Microsoft Email <span class="text-red-500">*</span></label>
                                            <input wire:model="email" type="email" placeholder="user@company.com" required
                                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white font-mono">
                                            @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name</label>
                                            <input wire:model="name" type="text" placeholder="Auto-fetched on Microsoft login"
                                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white">
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <!-- Local Auth Fields -->
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                                        <input wire:model="name" type="text" required
                                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                                        @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                                        <input wire:model="email" type="email" required
                                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                                        @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                                            Password {{ $userId ? '(leave blank to keep)' : '*' }}
                                        </label>
                                        <input wire:model="password" type="password"
                                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]"
                                            {{ $userId ? '' : 'required' }}>
                                        @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">Confirm Password</label>
                                        <input wire:model="passwordConfirmation" type="password"
                                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- CEO Dashboard Checkbox -->
                        <div class="flex items-center gap-3 p-3.5 rounded-xl bg-amber-50 border border-amber-200 pt-2 border-t border-slate-100">
                            <input wire:model.live="isCeo" type="checkbox" id="is-ceo-check"
                                class="w-4 h-4 rounded border-amber-400 text-amber-600 focus:ring-amber-400">
                            <div>
                                <label for="is-ceo-check" class="text-xs font-semibold text-[#0f172a]">CEO Dashboard Access</label>
                                <p class="text-[11px] text-slate-500">CEO users can view consolidated data from multiple companies.</p>
                            </div>
                        </div>

                        <!-- CEO: Multi-Company selector -->
                        @if($isCeo)
                            <div class="p-4 rounded-xl bg-amber-50/60 border border-amber-200">
                                <label class="block text-xs font-semibold text-slate-700 mb-2">Assigned Companies <span class="text-red-500">*</span></label>
                                <p class="text-[11px] text-slate-500 mb-3">Select all companies this CEO can view.</p>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach($companies as $company)
                                        <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 cursor-pointer hover:border-amber-400 transition-all
                                            {{ in_array((string)$company->id, $ceoCompanyIds) ? 'border-amber-400 bg-amber-50 ring-1 ring-amber-200' : 'bg-white' }}">
                                            <input wire:model.live="ceoCompanyIds" type="checkbox"
                                                value="{{ $company->id }}"
                                                class="w-4 h-4 rounded border-amber-400 text-amber-600 focus:ring-amber-400">
                                            <span class="text-xs font-medium text-[#0f172a]">{{ $company->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <!-- Regular: Company & Group Selection -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sub-Company</label>
                                    <select wire:model.live="companyId"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white">
                                        <option value="">No Company Assigned</option>
                                        @foreach($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('companyId') <p class="text-xs text-red-500 font-semibold mt-1.5">{{ $message }}</p> @enderror

                                    <!-- Department Mismatch Live Indicator -->
                                    @if($selectedAzureUser && !empty($selectedAzureUser['department']) && $companyId)
                                        @php
                                            $assignedComp = $companies->firstWhere('id', $companyId);
                                            $dept = $selectedAzureUser['department'];
                                            $isMatch = false;
                                            if ($assignedComp) {
                                                $deptLower = strtolower($dept);
                                                $compNameLower = strtolower($assignedComp->name);
                                                $compSlugLower = strtolower($assignedComp->slug);
                                                $isMatch = str_contains($deptLower, $compNameLower) || str_contains($deptLower, $compSlugLower) || str_contains($compNameLower, $deptLower);
                                            }
                                        @endphp
                                        @if(!$isMatch)
                                            <div class="mt-2 p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 flex items-start gap-2">
                                                <svg class="w-4 h-4 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                <div>
                                                    <p class="font-bold">⚠️ Department Mismatch Warning!</p>
                                                    <p class="text-[11px] text-red-600 mt-0.5">This Azure AD user belongs to <strong>"{{ $dept }}"</strong>, but you selected <strong>"{{ $assignedComp->name }}"</strong>. Please select the matching company.</p>
                                                </div>
                                            </div>
                                        @else
                                            <div class="mt-2 p-2 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 font-semibold flex items-center gap-1.5">
                                                <span>✓ Verified Department Match: {{ $assignedComp->name }}</span>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Access Group</label>
                                    <select wire:model="groupId"
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white">
                                        <option value="">No Group Assigned</option>
                                        @foreach($groups as $group)
                                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif

                        <!-- Admin Access Toggle -->
                        <div class="flex items-center gap-3 p-3.5 rounded-xl bg-[#fdf2f4] border border-[#f8d7da]">
                            <input wire:model="isAdmin" type="checkbox" id="is-admin"
                                class="w-4 h-4 rounded border-[#c3122e] text-[#c3122e] focus:ring-[#c3122e]">
                            <div>
                                <label for="is-admin" class="text-xs font-semibold text-[#0f172a]">Grant Administrator Access</label>
                                <p class="text-[11px] text-slate-500">Admins manage users, companies, banks &amp; groups. Restricted from tenant views.</p>
                            </div>
                        </div>

                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-[#f8fafc] flex-shrink-0">
                    <button type="button" wire:click="$set('showModal', false)"
                        class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-sm font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" form="userForm"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-sm font-semibold transition-colors shadow-sm shadow-[#c3122e]/20">
                        <svg wire:loading wire:target="saveUser" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ $userId ? 'Save Changes' : 'Create User' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>


