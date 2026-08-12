<?php

use App\Models\Company;
use App\Models\Group;
use App\Models\User;
use App\Models\CompanyLoan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    public function with(): array
    {
        return [
            'totalUsers'     => User::where('is_admin', false)->count(),
            'adminUsers'     => User::where('is_admin', true)->count(),
            'totalCompanies' => Company::count(),
            'totalGroups'    => Group::count(),
            'recentUsers'    => User::with(['company', 'group'])
                                    ->latest()
                                    ->limit(5)
                                    ->get(),
        ];
    }
}; ?>

<div>
    @slot('header') Admin Dashboard @endslot

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

        <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-[#fdf2f4] flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#c3122e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-[#0f172a]">{{ $totalUsers }}</p>
            <p class="text-sm text-slate-500 mt-1">Portal Users</p>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-[#fdf2f4] flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#c3122e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-[#0f172a]">{{ $adminUsers }}</p>
            <p class="text-sm text-slate-500 mt-1">Administrators</p>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-[#fdf2f4] flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#c3122e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-[#0f172a]">{{ $totalCompanies }}</p>
            <p class="text-sm text-slate-500 mt-1">Sub-Companies</p>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-[#fdf2f4] flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#c3122e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-[#0f172a]">{{ $totalGroups }}</p>
            <p class="text-sm text-slate-500 mt-1">Access Groups</p>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-50">
            <h2 class="font-semibold text-[#0f172a] text-base">Recent Users</h2>
            <a href="{{ route('admin.users.index') }}"
               class="text-xs text-[#c3122e] font-medium hover:underline">View all →</a>
        </div>
        <div class="divide-y divide-slate-50">
            @forelse($recentUsers as $user)
                <div class="flex items-center gap-4 px-6 py-4 hover:bg-[#fdf2f4]/50 transition-colors">
                    <div class="w-9 h-9 rounded-full bg-[#c3122e] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-[#0f172a] truncate">{{ $user->name }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ $user->email }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($user->is_admin)
                            <span class="px-2 py-0.5 rounded-full bg-[#fdf2f4] text-[#c3122e] text-xs font-semibold border border-[#f8d7da]">Admin</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-medium">
                                {{ $user->group->name ?? 'Unassigned' }}
                            </span>
                            <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 text-xs font-medium">
                                {{ $user->company->name ?? '—' }}
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-slate-400 text-sm">No users found. Create your first user.</div>
            @endforelse
        </div>
    </div>
</div>
