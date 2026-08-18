<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\WorkingCapitalLoan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkingCapitalController extends Controller
{
        public function index(): View
    {
        $company = auth()->user()->company;
        $banks   = $company->banks()->where('is_active', true)->get();

        $filterYear = (int) request()->query('year', 2026);
        $filterMonth = (int) request()->query('month', 4);
        if ($filterMonth < 1 || $filterMonth > 12) $filterMonth = 4;
        if ($filterYear < 2020 || $filterYear > 2035) $filterYear = 2026;

        $filterDate = \Carbon\Carbon::createFromDate($filterYear, $filterMonth, 1)->endOfMonth();
        $filterMonthName = $filterDate->format('F');
        $filterDateFormatted = $filterDate->format('d F Y');

        $loans = WorkingCapitalLoan::active()
            ->with(['bank', 'user'])
            ->where('company_id', $company->id)
            ->latest('entry_date')
            ->latest('id')
            ->get();

        $totalFacility    = $loans->sum('facility_amount');
        $totalOutstanding = $loans->sum('outstanding_amount');

        $loansGrouped = $loans->groupBy(fn($l) => $l->entry_date ? $l->entry_date->format('F Y') : 'Unspecified Period');

        $existingFacilityTypes = WorkingCapitalLoan::select('facility_type')
            ->distinct()
            ->pluck('facility_type')
            ->merge(['PCL USD', 'PCL LKR', 'IML', 'STL', 'Overdraft', 'Money Market Loan', 'Credit Card'])
            ->unique()
            ->values();

        $existingTenors = WorkingCapitalLoan::select('tenor')
            ->distinct()
            ->pluck('tenor')
            ->merge(['30D', '60D', '90D', '120D', '150D', '270D', 'On Demand'])
            ->unique()
            ->values();

        return view("livewire.tenant.{$company->slug}.working_capital", compact(
            'company', 'banks', 'loans', 'loansGrouped', 'totalFacility', 'totalOutstanding',
            'existingFacilityTypes', 'existingTenors', 'filterMonth', 'filterYear', 'filterMonthName', 'filterDateFormatted'
        ));
    }

    public function store(Request $request, string $company_slug): RedirectResponse
    {
        $company = auth()->user()->company;

        $validated = $request->validate([
            'bank_id'                => 'required|exists:banks,id',
            'facility_type'          => 'required|string|max:100',
            'tenor'                  => 'nullable|string|max:50',
            'facility_amount'        => 'required|numeric|min:0',
            'obtained_date'          => 'nullable|date',
            'settlement_date'        => 'nullable|date',
            'days_extended'          => 'nullable|integer|min:0',
            'revised_settlement_date'=> 'nullable|date',
            'interest_rate'          => 'required|numeric|min:0|max:100',
            'outstanding_amount'     => 'required|numeric|min:0',
            'currency'               => 'required|in:LKR,USD',
            'notes'                  => 'nullable|string|max:1000',
            'entry_date'             => 'required|date',
        ]);

        $compareDate = $validated['revised_settlement_date'] ?? $validated['settlement_date'] ?? null;
        $overdueDays = 0;
        if ($compareDate) {
            $overdueDays = max(0, (int) now()->diffInDays(\Carbon\Carbon::parse($compareDate), false) * -1);
        }

        $loan = WorkingCapitalLoan::create([
            ...$validated,
            'company_id'              => $company->id,
            'user_id'                 => auth()->id(),
            'settlement_days_overdue' => $overdueDays,
            'is_active'               => true,
            'version'                 => 1,
        ]);

        AuditLog::log($company->id, 'CREATE', 'Working Capital Loan', "Added {$loan->facility_type} for LKR " . number_format($loan->facility_amount));

        return redirect()
            ->route('tenant.working-capital', ['company_slug' => $company_slug])
            ->with('success', 'Working capital loan entry saved successfully.');
    }

    public function updateRate(Request $request, string $company_slug, WorkingCapitalLoan $workingCapitalLoan): RedirectResponse
    {
        $company = auth()->user()->company;
        if ($workingCapitalLoan->company_id !== $company->id) abort(403);

        $actionType = $request->input('action_type', 'revise_iml');

        $validated = $request->validate([
            'action_type'            => 'required|in:revise_iml,settle_loan',
            'interest_rate'          => 'required_if:action_type,revise_iml|nullable|numeric|min:0|max:100',
            'outstanding_amount'     => 'required_if:action_type,revise_iml|nullable|numeric|min:0',
            'revised_settlement_date'=> 'nullable|date',
            'is_bank_confirmed'      => 'nullable',
            'bank_confirmed_date'    => 'required_if:is_bank_confirmed,1|nullable|date',
            'settlement_type'        => 'required_if:action_type,settle_loan|nullable|in:all,partial',
            'settled_amount'         => 'required_if:settlement_type,partial|nullable|numeric|min:0.01',
            'settle_using_new_loan'  => 'nullable',
            'settled_via_loan_id'    => 'nullable|exists:working_capital_loans,id',
            'revision_notes'         => 'required|string|max:1000',
        ]);

        $oldRate = $workingCapitalLoan->interest_rate;
        $oldOutstanding = (float) $workingCapitalLoan->outstanding_amount;
        $rootParentId = $workingCapitalLoan->parent_id ?? $workingCapitalLoan->id;

        // Archive current version
        $workingCapitalLoan->update(['is_active' => false]);

        if ($actionType === 'settle_loan') {
            $settlementType = $validated['settlement_type'];
            if ($settlementType === 'all') {
                $settledAmount = $oldOutstanding;
                $newOutstanding = 0.00;
                $newIsActive = false; // Fully settled
            } else {
                $settledAmount = (float) $validated['settled_amount'];
                $newOutstanding = max(0.00, $oldOutstanding - $settledAmount);
                $newIsActive = true;
            }

            $settledViaLoanId = $request->boolean('settle_using_new_loan') ? ($validated['settled_via_loan_id'] ?? null) : null;

            WorkingCapitalLoan::create([
                'company_id'              => $company->id,
                'bank_id'                 => $workingCapitalLoan->bank_id,
                'user_id'                 => auth()->id(),
                'parent_id'               => $rootParentId,
                'is_active'               => $newIsActive,
                'version'                 => $workingCapitalLoan->version + 1,
                'facility_type'           => $workingCapitalLoan->facility_type,
                'tenor'                   => $workingCapitalLoan->tenor,
                'facility_amount'         => $workingCapitalLoan->facility_amount,
                'obtained_date'           => $workingCapitalLoan->obtained_date,
                'settlement_date'         => $workingCapitalLoan->settlement_date,
                'days_extended'           => $workingCapitalLoan->days_extended,
                'revised_settlement_date' => $workingCapitalLoan->revised_settlement_date,
                'settlement_days_overdue' => 0,
                'currency'                => $workingCapitalLoan->currency,
                'notes'                   => $workingCapitalLoan->notes,
                'entry_date'              => now(),
                'interest_rate'           => $workingCapitalLoan->interest_rate,
                'outstanding_amount'      => $newOutstanding,
                'action_type'             => 'settle_loan',
                'settlement_type'         => $settlementType,
                'settled_amount'          => $settledAmount,
                'settled_via_loan_id'     => $settledViaLoanId,
                'revision_notes'          => $validated['revision_notes'],
                'revision_date'           => now(),
            ]);

            AuditLog::log($company->id, 'UPDATE', 'Working Capital Loan Settlement', "Settled LKR " . number_format($settledAmount, 2) . " ({$settlementType}) for {$workingCapitalLoan->facility_type}. Notes: {$validated['revision_notes']}");

            return redirect()
                ->route('tenant.working-capital', ['company_slug' => $company_slug])
                ->with('success', "Loan settlement of LKR " . number_format($settledAmount, 2) . " recorded successfully.");
        }

        // Action Type: Revise IML / Rate
        $isBankConfirmed = $request->boolean('is_bank_confirmed');
        $bankConfirmedDate = $isBankConfirmed ? ($validated['bank_confirmed_date'] ?? null) : null;
        $revisedSettlementDate = $validated['revised_settlement_date'] ?? $workingCapitalLoan->revised_settlement_date;

        $compareDate = $bankConfirmedDate ?? $revisedSettlementDate ?? $workingCapitalLoan->settlement_date;
        $overdueDays = 0;
        if ($compareDate) {
            $overdueDays = max(0, (int) now()->diffInDays(\Carbon\Carbon::parse($compareDate), false) * -1);
        }

        WorkingCapitalLoan::create([
            'company_id'              => $company->id,
            'bank_id'                 => $workingCapitalLoan->bank_id,
            'user_id'                 => auth()->id(),
            'parent_id'               => $rootParentId,
            'is_active'               => true,
            'version'                 => $workingCapitalLoan->version + 1,
            'facility_type'           => $workingCapitalLoan->facility_type,
            'tenor'                   => $workingCapitalLoan->tenor,
            'facility_amount'         => $workingCapitalLoan->facility_amount,
            'obtained_date'           => $workingCapitalLoan->obtained_date,
            'settlement_date'         => $workingCapitalLoan->settlement_date,
            'days_extended'           => $workingCapitalLoan->days_extended,
            'revised_settlement_date' => $revisedSettlementDate,
            'is_bank_confirmed'       => $isBankConfirmed,
            'bank_confirmed_date'     => $bankConfirmedDate,
            'settlement_days_overdue' => $overdueDays,
            'currency'                => $workingCapitalLoan->currency,
            'notes'                   => $workingCapitalLoan->notes,
            'entry_date'              => now(),
            'interest_rate'           => (float) $validated['interest_rate'],
            'outstanding_amount'      => (float) $validated['outstanding_amount'],
            'action_type'             => 'revise_iml',
            'revision_notes'          => $validated['revision_notes'],
            'revision_date'           => now(),
        ]);

        AuditLog::log($company->id, 'UPDATE', 'Working Capital Loan Revision', "Revised {$workingCapitalLoan->facility_type} rate to {$validated['interest_rate']}%" . ($isBankConfirmed ? " (Bank Confirmed Date: {$bankConfirmedDate})" : ''));

        return redirect()
            ->route('tenant.working-capital', ['company_slug' => $company_slug])
            ->with('success', "Working capital facility revised successfully. History archived.");
    }

    public function destroy(string $company_slug, WorkingCapitalLoan $workingCapitalLoan): RedirectResponse
    {
        $company = auth()->user()->company;
        if ($workingCapitalLoan->company_id !== $company->id) abort(403);

        $workingCapitalLoan->delete();

        AuditLog::log($company->id, 'DELETE', 'Working Capital Loan', "Deleted {$workingCapitalLoan->facility_type} entry");

        return redirect()
            ->route('tenant.working-capital', ['company_slug' => $company_slug])
            ->with('success', 'Entry deleted successfully.');
    }
}
