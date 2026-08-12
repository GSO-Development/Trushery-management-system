<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LoanRepaymentSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RepaymentScheduleController extends Controller
{
    public function index(): View
    {
        $company = auth()->user()->company;

        $banks = $company->banks()->where('is_active', true)->get();

        $schedules = LoanRepaymentSchedule::with('bank', 'user')
            ->where('company_id', $company->id)
            ->orderBy('due_date', 'asc')
            ->get();

        $totalPending  = $schedules->where('status', 'Pending')->sum('total_installment');
        $totalPaid     = $schedules->where('status', 'Paid')->sum('total_installment');
        $totalOverdue  = $schedules->where('status', 'Overdue')->sum('total_installment');

        return view("livewire.tenant.{$company->slug}.repayment_schedule", compact(
            'company', 'banks', 'schedules', 'totalPending', 'totalPaid', 'totalOverdue'
        ));
    }

    public function store(Request $request, string $company_slug): RedirectResponse
    {
        $company = auth()->user()->company;

        $validated = $request->validate([
            'bank_id'           => 'required|exists:banks,id',
            'loan_category'     => 'required|string|max:50',
            'due_date'          => 'required|date',
            'principal_amount'  => 'required|numeric|min:0',
            'interest_amount'   => 'required|numeric|min:0',
            'currency'          => 'required|string|max:10',
            'remarks'           => 'nullable|string|max:500',
        ]);

        $principal = (float) $validated['principal_amount'];
        $interest  = (float) $validated['interest_amount'];
        $total     = $principal + $interest;

        $schedule = LoanRepaymentSchedule::create([
            'company_id'        => $company->id,
            'bank_id'           => $validated['bank_id'],
            'loan_category'     => $validated['loan_category'],
            'due_date'          => $validated['due_date'],
            'principal_amount'  => $principal,
            'interest_amount'   => $interest,
            'total_installment' => $total,
            'status'            => 'Pending',
            'currency'          => $validated['currency'],
            'remarks'           => $validated['remarks'] ?? null,
            'user_id'           => auth()->id(),
        ]);

        AuditLog::log($company->id, 'CREATE', 'Repayment Schedule', "Added repayment schedule for {$validated['loan_category']} due {$validated['due_date']} (LKR {$total})");

        return redirect()
            ->route('tenant.repayment-schedule', ['company_slug' => $company_slug])
            ->with('success', 'Repayment schedule item added successfully.');
    }

    public function markPaid(Request $request, string $company_slug, LoanRepaymentSchedule $schedule): RedirectResponse
    {
        $company = auth()->user()->company;
        if ($schedule->company_id !== $company->id) abort(403);

        $schedule->update([
            'status'    => 'Paid',
            'paid_date' => now(),
        ]);

        AuditLog::log($company->id, 'UPDATE', 'Repayment Schedule', "Marked installment #{$schedule->id} as Paid on " . now()->format('Y-m-d'));

        return redirect()
            ->route('tenant.repayment-schedule', ['company_slug' => $company_slug])
            ->with('success', 'Installment marked as Paid.');
    }
}
