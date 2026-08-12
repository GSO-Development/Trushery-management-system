{{--
    resources/views/livewire/tenant/health/audit_logs.blade.php
    Controller: App\Http\Controllers\Tenant\AuditLogController
--}}
@extends('layouts.portal')
@section('header', 'Security Audit Logs')

@section('content')
<div>

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-bold text-[#0f172a]">Security Audit Logs</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $company->name }} · System Activity &amp; Audit Trail</p>
        </div>
    </div>

    {{-- Main Table --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h2 class="font-bold text-[#0f172a] text-sm">Activity Audit Trail</h2>
                <p class="text-xs text-slate-400 mt-0.5">{{ $logs->total() }} recorded actions</p>
            </div>

            {{-- Filter & Search Form --}}
            <form method="GET" action="{{ route('tenant.audit-logs', ['company_slug' => $company->slug]) }}" class="flex flex-wrap items-center gap-2">
                <div class="relative w-full sm:w-64">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search email, name, description..."
                        class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]">
                </div>

                <select name="module" onchange="this.form.submit()" class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20">
                    <option value="">All Modules</option>
                    <option value="Long Term Loans" {{ request('module') === 'Long Term Loans' ? 'selected' : '' }}>Long Term Loans</option>
                    <option value="Working Capital" {{ request('module') === 'Working Capital' ? 'selected' : '' }}>Working Capital</option>
                    <option value="Fixed Deposits" {{ request('module') === 'Fixed Deposits' ? 'selected' : '' }}>Fixed Deposits</option>
                    <option value="Cash Position" {{ request('module') === 'Cash Position' ? 'selected' : '' }}>Cash Position</option>
                    <option value="Repayment Schedule" {{ request('module') === 'Repayment Schedule' ? 'selected' : '' }}>Repayment Schedule</option>
                    <option value="Transactions" {{ request('module') === 'Transactions' ? 'selected' : '' }}>Transactions</option>
                    <option value="Interest Rates" {{ request('module') === 'Interest Rates' ? 'selected' : '' }}>Interest Rates</option>
                </select>

                <select name="action" onchange="this.form.submit()" class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20">
                    <option value="">All Actions</option>
                    <option value="CREATE" {{ request('action') === 'CREATE' ? 'selected' : '' }}>CREATE</option>
                    <option value="UPDATE" {{ request('action') === 'UPDATE' ? 'selected' : '' }}>UPDATE</option>
                    <option value="DELETE" {{ request('action') === 'DELETE' ? 'selected' : '' }}>DELETE</option>
                    <option value="LOGIN" {{ request('action') === 'LOGIN' ? 'selected' : '' }}>LOGIN</option>
                </select>

                @if(request('search') || request('module') || request('action'))
                    <a href="{{ route('tenant.audit-logs', ['company_slug' => $company->slug]) }}" class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-500 hover:bg-slate-100 transition-colors">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-[#f8fafc] text-slate-500 text-[11px]">
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider min-w-[130px]">Timestamp</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider min-w-[150px]">User &amp; Email</th>
                        <th class="px-4 py-3 text-center font-semibold uppercase tracking-wider min-w-[80px]">Action</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider min-w-[120px]">Module</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider">Description</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider min-w-[100px]">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($logs as $log)
                        <tr class="hover:bg-[#fdf2f4]/30 transition-colors">
                            <td class="px-4 py-3 text-slate-500 font-mono">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                            <td class="px-4 py-3 text-[#0f172a]">
                                <div class="font-bold">{{ $log->user_name ?? $log->user->name ?? 'System' }}</div>
                                @if($log->user && $log->user->email)
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $log->user->email }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $log->action === 'CREATE' ? 'bg-green-100 text-green-700' : ($log->action === 'UPDATE' ? 'bg-blue-100 text-blue-700' : ($log->action === 'DELETE' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-700')) }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-700">{{ $log->module }}</td>
                            <td class="px-4 py-3 text-slate-700 font-medium">{{ $log->description }}</td>
                            <td class="px-4 py-3 text-slate-400 font-mono text-[11px]">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-300 text-xs">No audit logs recorded matching your search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-5 py-3 border-t border-slate-100 bg-[#f8fafc]">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
