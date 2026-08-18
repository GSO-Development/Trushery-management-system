@extends('layouts.portal')

@section('header', 'My Profile & Account Settings')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Top Alert / Status Messages -->
    @if(session('status'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center gap-3 shadow-sm">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-sm font-semibold space-y-1 shadow-sm">
            <div class="flex items-center gap-2 text-red-900 font-bold">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Please fix the following errors:</span>
            </div>
            <ul class="list-disc list-inside text-xs space-y-1 pl-6 text-red-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Profile Overview Card -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-[#c3122e]/5 rounded-bl-full pointer-events-none"></div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-[#c3122e] text-white flex items-center justify-center font-extrabold text-2xl shadow-lg shadow-[#c3122e]/20">
                    {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-xl font-bold text-[#0f172a]">{{ $user->name }}</h2>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">{{ $user->email }}</p>
                    <div class="flex items-center gap-2 mt-2 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            {{ $company->name }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#fdf2f4] border border-[#f8d7da] text-[#c3122e] text-xs font-semibold">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#c3122e]"></span>
                            {{ $user->group->name ?? 'User Group' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Auth Method Badge -->
            <div class="self-stretch sm:self-auto flex items-center justify-end">
                @if($user->auth_provider === 'microsoft')
                    <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-xs font-bold">
                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 23 23">
                            <path d="M0 0h11v11H0zM12 0h11v11H12zM0 12h11v11H0zM12 12h11v11H12z"/>
                        </svg>
                        Microsoft SSO Account
                    </span>
                @else
                    <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-xs font-bold">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Local Password Account
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Assigned Details Card -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                <svg class="w-5 h-5 text-[#c3122e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-6 0h6"/>
                </svg>
                <h3 class="font-bold text-[#0f172a] text-sm uppercase tracking-wider">Assigned Sub-Company & Group</h3>
            </div>

            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-slate-500 font-medium">Sub-Company Name</span>
                    <span class="font-bold text-[#0f172a]">{{ $company->name }}</span>
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-slate-500 font-medium">Sub-Company Code / Slug</span>
                    <span class="font-mono font-bold text-slate-700">{{ $company->slug }}</span>
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-slate-500 font-medium">Access Group Name</span>
                    <span class="font-bold text-[#c3122e]">{{ $user->group->name ?? 'N/A' }}</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-2">
                    <span class="text-slate-500 font-medium block">Allowed Navigation Modules</span>
                    <div class="flex flex-wrap gap-1.5">
                        @forelse($user->group->getNavKeys() ?? [] as $navKey)
                            <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 font-semibold text-[11px]">
                                {{ Str::of($navKey)->replace('_', ' ')->title() }}
                            </span>
                        @empty
                            <span class="text-slate-400 italic">No modules assigned</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Password Card -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                <svg class="w-5 h-5 text-[#c3122e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 0121 9z"/>
                </svg>
                <h3 class="font-bold text-[#0f172a] text-sm uppercase tracking-wider">Change Password</h3>
            </div>

            @if($user->auth_provider === 'microsoft')
                <div class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-xs font-medium space-y-2">
                    <p class="font-bold">Managed via Microsoft Entra ID</p>
                    <p class="text-blue-700">Your account is connected to Single Sign-On (SSO). Password changes are managed directly by your organization's Microsoft 365 administrator.</p>
                </div>
            @else
                <form method="POST" action="{{ route('tenant.profile.password', ['company_slug' => $company->slug]) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Current Password <span class="text-red-500">*</span></label>
                        <input type="password" name="current_password" required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">New Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" required minlength="8"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Confirm New Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password_confirmation" required minlength="8"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                    </div>

                    <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-sm font-semibold transition-colors shadow-sm shadow-[#c3122e]/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update Password
                    </button>
                </form>
            @endif
        </div>

    </div>

</div>
@endsection
