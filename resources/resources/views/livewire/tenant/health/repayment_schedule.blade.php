{{--
    resources/views/livewire/tenant/health/repayment_schedule.blade.php
    Controller: App\Http\Controllers\Tenant\RepaymentScheduleController
--}}
@extends('layouts.portal')
@section('header', 'Loan Repayment Schedule')

@section('content')
<div x-data="{ addingRow: false }" @keydown.escape.window="addingRow = false">

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-bold text-[#0f172a]">Loan Repayment Schedule</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $company->name }} · Installments & Debt Payment Schedule</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-amber-500 uppercase tracking-wider mb-1">Pending Installments</p>
            <p class="text-xl font-bold text-amber-600">LKR {{ number_format($totalPending, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-green-600 uppercase tracking-wider mb-1">Total Paid</p>
            <p class="text-xl font-bold text-green-600">LKR {{ number_format($totalPaid, 0) }}</p>
        </div>
        <div class="bg-white rounded-2xl border {{ $totalOverdue > 0 ? 'border-red-200 bg-red-50/20' : 'border-slate-100' }} shadow-sm p-5">
            <p class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1">Overdue Installments</p>
            <p class="text-xl font-bold text-red-600">LKR {{ number_format($totalOverdue, 0) }}</p>
        </div>
    </div>

    {{-- Hidden form --}}
    <form id="sched-new-row" method="POST" action="{{ route('tenant.repayment-schedule.store', ['company_slug' => $company->slug]) }}">
        @csrf
    </form>

    {{-- Main Table --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-[#0f172a] text-sm">Repayment Installments Schedule</h2>
                <p class="text-xs text-slate-400 mt-0.5">{{ $schedules->count() }} total schedule items</p>
            </div>
            <button type="button" @click="addingRow = true" x-show="!addingRow"
                class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-bold transition-colors shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Schedule Item
            </button>
            <div x-show="addingRow" class="flex gap-2">
                <button type="submit" form="sched-new-row" class="px-3.5 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white text-xs font-bold transition-colors">✓ Save</button>
                <button type="button" @click="addingRow = false" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-colors">✕ Cancel</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-[#f8fafc] text-slate-500 text-[11px]">
                        <th class="px-3 py-2.5 text-left font-semibold uppercase tracking-wider min-w-[100px]">Due Date</th>
                        <th class="px-3 py-2.5 text-left font-semibold uppercase tracking-wider min-w-[100px]">Bank</th>
                        <th class="px-3 py-2.5 text-left font-semibold uppercase tracking-wider min-w-[120px]">Loan Category</th>
                        <th class="px-3 py-2.5 text-right font-semibold uppercase tracking-wider min-w-[100px]">Principal</th>
                        <th class="px-3 py-2.5 text-right font-semibold uppercase tracking-wider min-w-[90px]">Interest</th>
                        <th class="px-3 py-2.5 text-right font-semibold uppercase tracking-wider min-w-[110px]">Total Installment</th>
                        <th class="px-3 py-2.5 text-center font-semibold uppercase tracking-wider min-w-[70px]">Status</th>
                        <th class="px-3 py-2.5 text-left font-semibold uppercase tracking-wider min-w-[95px]">Paid Date</th>
                        <th class="px-3 py-2.5 text-left font-semibold uppercase tracking-wider min-w-[110px]">Remarks</th>
                        <th class="px-3 py-2.5 text-center font-semibold uppercase tracking-wider w-16">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($schedules as $item)
                        @php
                            $isOverdue = $item->status === 'Pending' && $item->due_date->isPast();
                            $dispStatus = $isOverdue ? 'Overdue' : $item->status;
                        @endphp
                        <tr class="hover:bg-[#fdf2f4]/30 transition-colors {{ $isOverdue ? 'bg-red-50/30' : '' }}">
                            <td class="px-3 py-2.5 font-bold text-[#0f172a]">{{ $item->due_date->format('d M Y') }}</td>
                            <td class="px-3 py-2.5 font-medium text-[#0f172a]">
                                <span class="inline-block px-1.5 py-0.5 rounded bg-[#fdf2f4] text-[#c3122e] font-bold font-mono text-[10px] border border-[#f8d7da]">{{ $item->bank->bank_code ?? '—' }}</span>
                                <span class="ml-1">{{ $item->bank->short_name ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 font-semibold">{{ $item->loan_category }}</td>
                            <td class="px-3 py-2.5 text-right font-mono text-slate-700">{{ number_format($item->principal_amount, 0) }}</td>
                            <td class="px-3 py-2.5 text-right font-mono text-slate-700">{{ number_format($item->interest_amount, 0) }}</td>
                            <td class="px-3 py-2.5 text-right font-mono font-bold text-[#c3122e]">{{ number_format($item->total_installment, 0) }}</td>
                            <td class="px-3 py-2.5 text-center">
                                @if($dispStatus === 'Paid')
                                    <span class="px-2 py-0.5 rounded bg-green-100 text-green-700 font-bold text-[10px]">Paid</span>
                                @elseif($dispStatus === 'Overdue')
                                    <span class="px-2 py-0.5 rounded bg-red-100 text-red-700 font-bold text-[10px]">Overdue</span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-700 font-bold text-[10px]">Pending</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-500">{{ $item->paid_date?->format('d M Y') ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-slate-500 max-w-[110px] truncate" title="{{ $item->remarks }}">{{ $item->remarks ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-center">
                                @if($item->status !== 'Paid')
                                    <form method="POST" action="{{ route('tenant.repayment-schedule.pay', ['company_slug' => $company->slug, 'schedule' => $item->id]) }}">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 rounded bg-green-600 hover:bg-green-700 text-white font-bold text-[10px]">Mark Paid</button>
                                    </form>
                                @else
                                    <span class="text-slate-300">✓</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr x-show="!addingRow">
                            <td colspan="10" class="px-6 py-10 text-center text-slate-300 text-xs">No repayment schedules yet. Click <strong class="text-[#c3122e]">+ Add Schedule Item</strong> to begin.</td>
                        </tr>
                    @endforelse

                    {{-- Inline add row --}}
                    <tr x-show="addingRow" class="bg-[#fffbf5] border-t-2 border-[#c3122e]/20" style="display:none">
                        <td class="px-2 py-1.5"><input type="date" name="due_date" form="sched-new-row" required class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs focus:outline-none"></td>
                        <td class="px-2 py-1.5">
                            <select name="bank_id" form="sched-new-row" required class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs bg-white">
                                <option value="">— Bank —</option>
                                @foreach($banks as $bank)<option value="{{ $bank->id }}">{{ $bank->short_name }}</option>@endforeach
                            </select>
                        </td>
                        <td class="px-2 py-1.5">
                            <select name="loan_category" form="sched-new-row" required class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs bg-white">
                                <option value="Long Term Loan">Long Term Loan</option>
                                <option value="Working Capital">Working Capital</option>
                            </select>
                        </td>
                        <td class="px-2 py-1.5"><input type="number" step="0.01" min="0" name="principal_amount" form="sched-new-row" required placeholder="0" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs font-mono text-right"></td>
                        <td class="px-2 py-1.5"><input type="number" step="0.01" min="0" name="interest_amount" form="sched-new-row" required placeholder="0" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs font-mono text-right"></td>
                        <td class="px-2 py-1.5 text-center text-slate-300 text-[10px]">auto sum</td>
                        <td class="px-2 py-1.5 text-center text-slate-300 text-[10px]">Pending</td>
                        <td class="px-2 py-1.5 text-center text-slate-300 text-[10px]">—</td>
                        <td class="px-2 py-1.5"><input type="text" name="remarks" form="sched-new-row" placeholder="Remarks…" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs"></td>
                        <input type="hidden" name="currency" form="sched-new-row" value="LKR">
                        <td class="px-2 py-1.5 text-center text-slate-300">—</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
