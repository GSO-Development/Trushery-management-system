<?php

use App\Models\Company;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    public ?int $userId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $passwordConfirmation = '';
    public string $authProvider = 'microsoft'; // 'microsoft' or 'local'
    public ?int $companyId = null;
    public ?int $groupId = null;
    public bool $isAdmin = false;

    public function mount(?User $user = null): void
    {
        if ($user && $user->exists) {
            $this->userId          = $user->id;
            $this->name            = $user->name;
            $this->email           = $user->email;
            $this->authProvider    = $user->auth_provider ?? 'microsoft';
            $this->companyId       = $user->company_id;
            $this->groupId         = $user->group_id;
            $this->isAdmin         = $user->is_admin;
        }
    }

    public function with(): array
    {
        return [
            'companies' => Company::all(),
            'groups'    => Group::when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))->get(),
        ];
    }

    public function save(): void
    {
        if ($this->authProvider === 'microsoft') {
            $rules = [
                'email'     => 'required|email|max:255|unique:users,email,' . ($this->userId ?? 'NULL'),
                'companyId' => 'nullable|exists:companies,id',
                'groupId'   => 'nullable|exists:groups,id',
                'isAdmin'   => 'boolean',
            ];
            $this->validate($rules);

            // Name defaults to prefix of email if empty
            $name = $this->name ?: explode('@', $this->email)[0];
            $data = [
                'name'         => Str::title(str_replace('.', ' ', $name)),
                'email'        => $this->email,
                'company_id'   => $this->companyId,
                'group_id'     => $this->groupId,
                'is_admin'     => $this->isAdmin,
                'auth_provider'=> 'microsoft',
            ];

            if (! $this->userId) {
                $data['password'] = Str::random(32);
            }
        } else {
            $rules = [
                'name'      => 'required|string|max:255',
                'email'     => 'required|email|max:255|unique:users,email,' . ($this->userId ?? 'NULL'),
                'companyId' => 'nullable|exists:companies,id',
                'groupId'   => 'nullable|exists:groups,id',
                'isAdmin'   => 'boolean',
            ];

            if (! $this->userId) {
                $rules['password']             = 'required|string|min:8|same:passwordConfirmation';
                $rules['passwordConfirmation'] = 'required|string';
            } elseif ($this->password) {
                $rules['password']             = 'string|min:8|same:passwordConfirmation';
                $rules['passwordConfirmation'] = 'required|string';
            }

            $this->validate($rules);

            $data = [
                'name'         => $this->name,
                'email'        => $this->email,
                'company_id'   => $this->companyId,
                'group_id'     => $this->groupId,
                'is_admin'     => $this->isAdmin,
                'auth_provider'=> 'local',
            ];

            if ($this->password) {
                $data['password'] = $this->password;
            }
        }

        if ($this->userId) {
            User::findOrFail($this->userId)->update($data);
            session()->flash('success', 'User updated successfully.');
        } else {
            User::create($data);
            session()->flash('success', 'User created successfully.');
        }

        $this->redirect(route('admin.users.index'), navigate: false);
    }
}; ?>

<div>
    @slot('header') {{ $userId ? 'Edit User' : 'Create New User' }} @endslot

    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8">
            <h2 class="text-lg font-bold text-[#0f172a] mb-6">
                {{ $userId ? 'Edit User Account' : 'Create User Account' }}
            </h2>

            @if(session('success'))
                <div class="mb-5 p-3.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium">{{ session('success') }}</div>
            @endif

            <form wire:submit="save" class="space-y-6">

                <!-- Account Type Selector (Microsoft vs Local) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Authentication Type</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200 cursor-pointer hover:border-[#c3122e] transition-all {{ $authProvider === 'microsoft' ? 'border-[#c3122e] bg-[#fdf2f4]/60 ring-2 ring-[#c3122e]/20' : 'bg-slate-50/50' }}">
                            <input wire:model.live="authProvider" type="radio" value="microsoft" class="text-[#c3122e] focus:ring-[#c3122e]">
                            <div>
                                <p class="text-sm font-semibold text-[#0f172a] flex items-center gap-1.5">
                                    <svg width="14" height="14" viewBox="0 0 21 21"><rect x="1" y="1" width="9" height="9" fill="#f25022"/><rect x="11" y="1" width="9" height="9" fill="#7fba00"/><rect x="1" y="11" width="9" height="9" fill="#00a4ef"/><rect x="11" y="11" width="9" height="9" fill="#ffb900"/></svg>
                                    Microsoft Entra ID
                                </p>
                                <p class="text-[11px] text-slate-500">Only Email required (SSO)</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200 cursor-pointer hover:border-[#c3122e] transition-all {{ $authProvider === 'local' ? 'border-[#c3122e] bg-[#fdf2f4]/60 ring-2 ring-[#c3122e]/20' : 'bg-slate-50/50' }}">
                            <input wire:model.live="authProvider" type="radio" value="local" class="text-[#c3122e] focus:ring-[#c3122e]">
                            <div>
                                <p class="text-sm font-semibold text-[#0f172a]">Local Account</p>
                                <p class="text-[11px] text-slate-500">Name + Email + Password</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Fields for Microsoft Auth Mode -->
                @if($authProvider === 'microsoft')
                    <div class="space-y-4 p-4 rounded-xl bg-blue-50/60 border border-blue-100">
                        <div class="flex items-center gap-2 text-blue-800 text-xs font-semibold">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                            Microsoft SSO Registration Mode
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Microsoft Account Email <span class="text-red-500">*</span></label>
                            <input wire:model="email" type="email" placeholder="user@company.com" required
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white">
                            @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Full Name (Optional)</label>
                            <input wire:model="name" type="text" placeholder="Auto-fetched on first Microsoft login"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white">
                        </div>
                    </div>
                @else
                    <!-- Fields for Local Auth Mode -->
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                                <input wire:model="name" type="text" required
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                                <input wire:model="email" type="email" required
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                                @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Password {{ $userId ? '(leave blank to keep current)' : '*' }}
                                </label>
                                <input wire:model="password" type="password"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]"
                                    {{ $userId ? '' : 'required' }}>
                                @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm Password</label>
                                <input wire:model="passwordConfirmation" type="password"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Company & Group Selection -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2 border-t border-slate-100">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Sub-Company</label>
                        <select wire:model.live="companyId"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white">
                            <option value="">No Company Assigned</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Access Group</label>
                        <select wire:model="groupId"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] bg-white">
                            <option value="">No Group Assigned</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Admin Access Toggle -->
                <div class="flex items-center gap-3 p-4 rounded-xl bg-[#fdf2f4] border border-[#f8d7da]">
                    <input wire:model="isAdmin" type="checkbox" id="is-admin"
                        class="w-4 h-4 rounded border-[#c3122e] text-[#c3122e] focus:ring-[#c3122e]">
                    <div>
                        <label for="is-admin" class="text-sm font-medium text-[#0f172a]">Grant Administrator Privileges</label>
                        <p class="text-xs text-slate-500">Admins manage all companies, groups, banks, and users. They are restricted from tenant portal views.</p>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-sm font-semibold transition-colors shadow-sm">
                        <svg wire:loading wire:target="save" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ $userId ? 'Update User' : 'Create User' }}
                    </button>
                    <a href="{{ route('admin.users.index') }}"
                       class="px-6 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
